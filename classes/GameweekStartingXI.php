<?php

class GameweekStartingXI
{
    /*
     * ============================================================
     * LEGAL FPL STARTING FORMATIONS
     * ============================================================
     *
     * Every valid FPL Starting XI contains:
     *
     * 1 goalkeeper
     * 3-5 defenders
     * 2-5 midfielders
     * 1-3 forwards
     *
     * Total = 11 starters.
     */

    private const FORMATIONS = [

        '3-4-3' => [

            'GK' =>
                1,

            'DEF' =>
                3,

            'MID' =>
                4,

            'FWD' =>
                3
        ],

        '3-5-2' => [

            'GK' =>
                1,

            'DEF' =>
                3,

            'MID' =>
                5,

            'FWD' =>
                2
        ],

        '4-3-3' => [

            'GK' =>
                1,

            'DEF' =>
                4,

            'MID' =>
                3,

            'FWD' =>
                3
        ],

        '4-4-2' => [

            'GK' =>
                1,

            'DEF' =>
                4,

            'MID' =>
                4,

            'FWD' =>
                2
        ],

        '4-5-1' => [

            'GK' =>
                1,

            'DEF' =>
                4,

            'MID' =>
                5,

            'FWD' =>
                1
        ],

        '5-2-3' => [

            'GK' =>
                1,

            'DEF' =>
                5,

            'MID' =>
                2,

            'FWD' =>
                3
        ],

        '5-3-2' => [

            'GK' =>
                1,

            'DEF' =>
                5,

            'MID' =>
                3,

            'FWD' =>
                2
        ],

        '5-4-1' => [

            'GK' =>
                1,

            'DEF' =>
                5,

            'MID' =>
                4,

            'FWD' =>
                1
        ]
    ];


    /*
     * ============================================================
     * REQUIRED 15-PLAYER SQUAD STRUCTURE
     * ============================================================
     */

    private const SQUAD_REQUIREMENTS = [

        'GK' =>
            2,

        'DEF' =>
            5,

        'MID' =>
            5,

        'FWD' =>
            3
    ];


    /*
     * ============================================================
     * GAMEWEEK CORE SCORE WEIGHTS
     * ============================================================
     *
     * The Gameweek core answers:
     *
     *     "How attractive is this player as a starter
     *      in the immediate gameweek?"
     *
     * Unlike Wildcard Intelligence, price/value has no role here.
     *
     * Intelligence     45%
     * Strength         25%
     * Immediate fixture 30%
     */

    private const INTELLIGENCE_WEIGHT =
        0.45;


    private const STRENGTH_WEIGHT =
        0.25;


    private const FIXTURE_WEIGHT =
        0.30;


    /*
     * ============================================================
     * GAMEWEEK CALIBRATION
     * ============================================================
     *
     * Fixture ratings are compressed toward the neutral midpoint
     * before they enter the Gameweek core score.
     *
     * Examples:
     *
     * raw 100.0 -> 80.0
     * raw  66.7 -> 60.0
     * raw  50.0 -> 50.0
     * raw  33.3 -> 40.0
     * raw   0.0 -> 20.0
     */

    private const FIXTURE_COMPRESSION =
        0.60;


    /*
     * Confidence is intentionally allowed to penalise very small
     * samples more strongly than in the first Gameweek model.
     */

    private const MIN_CONFIDENCE_MODIFIER =
        0.75;


    private const MIN_AVAILABILITY_MODIFIER =
        0.30;


    /*
     * ============================================================
     * PUBLIC API
     * ============================================================
     */

    public function optimize(
        array $squad
    ): array {

        /*
         * --------------------------------------------------------
         * BASIC SQUAD VALIDATION
         * --------------------------------------------------------
         */

        if (
            count(
                $squad
            )
            !== 15
        ) {

            return $this->invalidResult(
                'Gameweek Starting XI requires exactly 15 players.'
            );
        }


        /*
         * --------------------------------------------------------
         * NORMALISE AND SCORE PLAYERS
         * --------------------------------------------------------
         */

        $players =
            [];


        $playerIds =
            [];


        foreach (
            $squad
            as $player
        ) {

            if (
                !is_array(
                    $player
                )
            ) {

                return $this->invalidResult(
                    'Gameweek squad contains invalid player data.'
                );
            }


            $normalised =
                $this->normalisePlayer(
                    $player
                );


            if (
                $normalised === null
            ) {

                return $this->invalidResult(
                    'Gameweek squad contains an invalid player.'
                );
            }


            $playerId =
                $normalised[
                    'player_id'
                ];


            if (
                isset(
                    $playerIds[
                        $playerId
                    ]
                )
            ) {

                return $this->invalidResult(
                    'Gameweek squad contains duplicate players.'
                );
            }


            $playerIds[
                $playerId
            ] =
                true;


            $players[] =
                $normalised;
        }


        /*
         * --------------------------------------------------------
         * SPLIT PLAYERS BY POSITION
         * --------------------------------------------------------
         */

        $byPosition = [

            'GK' =>
                [],

            'DEF' =>
                [],

            'MID' =>
                [],

            'FWD' =>
                []
        ];


        foreach (
            $players
            as $player
        ) {

            $byPosition[
                $player[
                    'position'
                ]
            ][] =
                $player;
        }


        /*
         * --------------------------------------------------------
         * VERIFY LEGAL FPL SQUAD STRUCTURE
         * --------------------------------------------------------
         */

        foreach (
            self::SQUAD_REQUIREMENTS
            as $position => $required
        ) {

            if (
                count(
                    $byPosition[
                        $position
                    ]
                )
                !==
                $required
            ) {

                return $this->invalidResult(
                    'Invalid FPL squad position structure.'
                );
            }
        }


        /*
         * --------------------------------------------------------
         * RANK EACH POSITION BY GAMEWEEK SCORE
         * --------------------------------------------------------
         */

        foreach (
            $byPosition
            as &$positionPlayers
        ) {

            usort(
                $positionPlayers,
                function (
                    array $a,
                    array $b
                ): int {

                    return $this->comparePlayers(
                        $a,
                        $b
                    );
                }
            );
        }


        unset(
            $positionPlayers
        );


        /*
         * ========================================================
         * EVALUATE EVERY LEGAL FORMATION
         * ========================================================
         */

        $formationResults =
            [];


        foreach (
            self::FORMATIONS
            as $formation => $requirements
        ) {

            $startingXI =
                [];


            $starterIds =
                [];


            /*
             * Select the strongest required players
             * from each positional group.
             */

            foreach (
                $requirements
                as $position => $required
            ) {

                $selected =
                    array_slice(
                        $byPosition[
                            $position
                        ],
                        0,
                        $required
                    );


                if (
                    count(
                        $selected
                    )
                    !==
                    $required
                ) {

                    continue 2;
                }


                foreach (
                    $selected
                    as $player
                ) {

                    $startingXI[] =
                        $player;


                    $starterIds[
                        $player[
                            'player_id'
                        ]
                    ] =
                        true;
                }
            }


            /*
             * ----------------------------------------------------
             * BUILD BENCH
             * ----------------------------------------------------
             */

            $benchGoalkeeper =
                null;


            $benchOutfield =
                [];


            foreach (
                $players
                as $player
            ) {

                if (
                    isset(
                        $starterIds[
                            $player[
                                'player_id'
                            ]
                        ]
                    )
                ) {

                    continue;
                }


                if (
                    $player[
                        'position'
                    ]
                    ===
                    'GK'
                ) {

                    $benchGoalkeeper =
                        $player;

                } else {

                    $benchOutfield[] =
                        $player;
                }
            }


            /*
             * Strongest outfield substitute first.
             */

            usort(
                $benchOutfield,
                function (
                    array $a,
                    array $b
                ): int {

                    return $this->comparePlayers(
                        $a,
                        $b
                    );
                }
            );


            /*
             * ----------------------------------------------------
             * SCORE FORMATION
             * ----------------------------------------------------
             *
             * The Starting XI itself is the only thing used to
             * choose the formation in v1.
             *
             * Bench strength is returned diagnostically, but we
             * deliberately do not allow a stronger bench to make
             * a weaker Starting XI win.
             */

            $startingXIScore =
                $this->averageGameweekScore(
                    $startingXI
                );


            $benchScore =
                $this->averageGameweekScore(
                    array_merge(
                        $benchOutfield,
                        $benchGoalkeeper !== null
                            ? [
                                $benchGoalkeeper
                            ]
                            : []
                    )
                );


            $formationResults[] = [

                'formation' =>
                    $formation,

                'starting_xi_score' =>
                    round(
                        $startingXIScore,
                        2
                    ),

                'bench_score' =>
                    round(
                        $benchScore,
                        2
                    ),

                'starting_xi' =>
                    $this->sortForDisplay(
                        $startingXI
                    ),

                'bench' =>
                    $this->buildOrderedBench(
                        $benchOutfield,
                        $benchGoalkeeper
                    )
            ];
        }


        /*
         * ========================================================
         * CHOOSE BEST FORMATION
         * ========================================================
         */

        usort(
            $formationResults,
            static function (
                array $a,
                array $b
            ): int {

                $scoreA =
                    (float) (
                        $a[
                            'starting_xi_score'
                        ]
                        ?? 0
                    );


                $scoreB =
                    (float) (
                        $b[
                            'starting_xi_score'
                        ]
                        ?? 0
                    );


                if (
                    $scoreA
                    !==
                    $scoreB
                ) {

                    return $scoreB
                        <=>
                        $scoreA;
                }


                /*
                 * If two formations produce exactly the same
                 * Starting XI average, prefer the stronger bench.
                 */

                return (
                    (float) (
                        $b[
                            'bench_score'
                        ]
                        ?? 0
                    )
                )
                <=>
                (
                    (float) (
                        $a[
                            'bench_score'
                        ]
                        ?? 0
                    )
                );
            }
        );


        $best =
            $formationResults[
                0
            ]
            ?? null;


        if (
            $best === null
        ) {

            return $this->invalidResult(
                'Unable to determine a legal Gameweek Starting XI.'
            );
        }


        /*
         * ========================================================
         * RETURN
         * ========================================================
         */

        return [

            'status' =>
                'success',

            'message' =>
                'Gameweek Starting XI generated successfully.',

            'formation' =>
                $best[
                    'formation'
                ],

            'starting_xi_score' =>
                $best[
                    'starting_xi_score'
                ],

            'bench_score' =>
                $best[
                    'bench_score'
                ],

            'starting_xi' =>
                $best[
                    'starting_xi'
                ],

            'bench' =>
                $best[
                    'bench'
                ],

            'formations' =>
                $formationResults,

            'formation_count' =>
                count(
                    $formationResults
                )
        ];
    }


    /*
     * ============================================================
     * PLAYER NORMALISATION
     * ============================================================
     */

    private function normalisePlayer(
        array $player
    ): ?array {

        $playerId =
            (int) (
                $player[
                    'player_id'
                ]
                ?? 0
            );


        $position =
            strtoupper(
                trim(
                    (string) (
                        $player[
                            'position'
                        ]
                        ?? ''
                    )
                )
            );


        if (
            $playerId <= 0
            ||
            !in_array(
                $position,
                [
                    'GK',
                    'DEF',
                    'MID',
                    'FWD'
                ],
                true
            )
        ) {

            return null;
        }


        /*
         * --------------------------------------------------------
         * GAMEWEEK INPUTS
         * --------------------------------------------------------
         *
         * Missing player-quality evidence is scored conservatively.
         *
         * Missing fixture or availability information is treated
         * as neutral rather than automatically making the player
         * unusable.
         */

        $intelligence =
            $this->normaliseScore(
                $player[
                    'intelligence_score'
                ]
                ?? 0.0,
                0.0
            );


        $strength =
            $this->normaliseScore(
                $player[
                    'strength_rating'
                ]
                ?? 0.0,
                0.0
            );


        $rawFixture =
            $this->normaliseScore(
                $player[
                    'next_fixture_rating'
                ]
                ?? 50.0,
                50.0
            );


        $fixture =
            $this->calculateFixtureScore(
                $rawFixture
            );


        $availability =
            $this->normalisePercentage(
                $player[
                    'availability_rating'
                ]
                ?? 50.0,
                50.0
            );


        $confidence =
            $this->normalisePercentage(
                $player[
                    'sample_confidence'
                ]
                ?? 0.0,
                0.0
            );


        /*
         * --------------------------------------------------------
         * CORE GAMEWEEK SCORE
         * --------------------------------------------------------
         */

        $coreScore =
            (
                $intelligence
                *
                self::INTELLIGENCE_WEIGHT
            )
            +
            (
                $strength
                *
                self::STRENGTH_WEIGHT
            )
            +
            (
                $fixture
                *
                self::FIXTURE_WEIGHT
            );


        /*
         * --------------------------------------------------------
         * RISK MODIFIERS
         * --------------------------------------------------------
         */

        $confidenceModifier =
            $this->calculateConfidenceModifier(
                $confidence
            );


        $availabilityModifier =
            self::MIN_AVAILABILITY_MODIFIER
            +
            (
                (
                    1.0
                    -
                    self::MIN_AVAILABILITY_MODIFIER
                )
                *
                (
                    $availability
                    /
                    100.0
                )
            );


        $gameweekScore =
            $coreScore
            *
            $confidenceModifier
            *
            $availabilityModifier;


        $gameweekScore =
            round(
                max(
                    0.0,
                    min(
                        100.0,
                        $gameweekScore
                    )
                ),
                2
            );


        /*
         * Preserve useful incoming metadata while ensuring
         * the normalised Gameweek fields are authoritative.
         */

        $normalised =
            $player;


        $normalised[
            'player_id'
        ] =
            $playerId;


        $normalised[
            'position'
        ] =
            $position;


        $normalised[
            'gameweek_score'
        ] =
            $gameweekScore;


        $normalised[
            'gameweek_components'
        ] = [

            'intelligence' =>
                $intelligence,

            'strength' =>
                $strength,

            'raw_fixture' =>
                $rawFixture,

            'fixture' =>
                $fixture,

            'availability' =>
                $availability,

            'confidence' =>
                $confidence,

            'core_score' =>
                round(
                    $coreScore,
                    2
                ),

            'confidence_modifier' =>
                round(
                    $confidenceModifier,
                    3
                ),

            'availability_modifier' =>
                round(
                    $availabilityModifier,
                    3
                )
        ];


        return $normalised;
    }


    /*
     * ============================================================
     * PLAYER ORDERING
     * ============================================================
     */

    private function comparePlayers(
        array $a,
        array $b
    ): int {

        $scoreA =
            (float) (
                $a[
                    'gameweek_score'
                ]
                ?? 0
            );


        $scoreB =
            (float) (
                $b[
                    'gameweek_score'
                ]
                ?? 0
            );


        if (
            $scoreA
            !==
            $scoreB
        ) {

            return $scoreB
                <=>
                $scoreA;
        }


        /*
         * Tie-breaker 1:
         * immediate fixture opportunity.
         */

        $fixtureA =
            (float) (
                $a[
                    'gameweek_components'
                ][
                    'fixture'
                ]
                ?? 0
            );


        $fixtureB =
            (float) (
                $b[
                    'gameweek_components'
                ][
                    'fixture'
                ]
                ?? 0
            );


        if (
            $fixtureA
            !==
            $fixtureB
        ) {

            return $fixtureB
                <=>
                $fixtureA;
        }


        /*
         * Tie-breaker 2:
         * underlying Player Intelligence.
         */

        return (
            (float) (
                $b[
                    'gameweek_components'
                ][
                    'intelligence'
                ]
                ?? 0
            )
        )
        <=>
        (
            (float) (
                $a[
                    'gameweek_components'
                ][
                    'intelligence'
                ]
                ?? 0
            )
        );
    }


    /*
     * ============================================================
     * AVERAGE SCORE
     * ============================================================
     */

    private function averageGameweekScore(
        array $players
    ): float {

        if (
            empty(
                $players
            )
        ) {

            return 0.0;
        }


        $total =
            0.0;


        foreach (
            $players
            as $player
        ) {

            $total +=
                (float) (
                    $player[
                        'gameweek_score'
                    ]
                    ?? 0
                );
        }


        return $total
            /
            count(
                $players
            );
    }


    /*
     * ============================================================
     * DISPLAY ORDER
     * ============================================================
     */

    private function sortForDisplay(
        array $players
    ): array {

        $positionOrder = [

            'GK' =>
                1,

            'DEF' =>
                2,

            'MID' =>
                3,

            'FWD' =>
                4
        ];


        usort(
            $players,
            static function (
                array $a,
                array $b
            ) use (
                $positionOrder
            ): int {

                $positionA =
                    $positionOrder[
                        $a[
                            'position'
                        ]
                        ?? ''
                    ]
                    ?? 99;


                $positionB =
                    $positionOrder[
                        $b[
                            'position'
                        ]
                        ?? ''
                    ]
                    ?? 99;


                if (
                    $positionA
                    !==
                    $positionB
                ) {

                    return $positionA
                        <=>
                        $positionB;
                }


                return (
                    (float) (
                        $b[
                            'gameweek_score'
                        ]
                        ?? 0
                    )
                )
                <=>
                (
                    (float) (
                        $a[
                            'gameweek_score'
                        ]
                        ?? 0
                    )
                );
            }
        );


        return $players;
    }


    /*
     * ============================================================
     * BENCH ORDER
     * ============================================================
     */

    private function buildOrderedBench(
        array $outfieldBench,
        ?array $goalkeeper
    ): array {

        $bench =
            [];


        foreach (
            $outfieldBench
            as $index => $player
        ) {

            $player[
                'bench_order'
            ] =
                $index + 1;


            $bench[] =
                $player;
        }


        /*
         * Backup goalkeeper is always returned after the three
         * ordered outfield substitutes.
         */

        if (
            $goalkeeper !== null
        ) {

            $goalkeeper[
                'bench_order'
            ] =
                4;


            $bench[] =
                $goalkeeper;
        }


        return $bench;
    }
    
    /*
     * ============================================================
     * FIXTURE CALIBRATION
     * ============================================================
     *
     * Immediate fixture opportunity remains important, but raw
     * extremes should not overwhelm underlying player quality.
     *
     * The raw 0-100 scale is compressed 60% of the distance away
     * from neutral 50.
     *
     * Examples:
     *
     * 100.0 -> 80.0
     *  66.7 -> 60.02
     *  50.0 -> 50.0
     *  33.3 -> 39.98
     *   0.0 -> 20.0
     */

    private function calculateFixtureScore(
        float $rawFixture
    ): float {

        $fixture =
            50.0
            +
            (
                (
                    $rawFixture
                    -
                    50.0
                )
                *
                self::FIXTURE_COMPRESSION
            );


        return round(
            max(
                0.0,
                min(
                    100.0,
                    $fixture
                )
            ),
            2
        );
    }


    /*
     * ============================================================
     * CONFIDENCE MODIFIER
     * ============================================================
     *
     * Confidence is deliberately non-linear.
     *
     * Very small samples receive a more meaningful penalty while
     * established players converge progressively toward 1.000.
     *
     * Approximate calibration:
     *
     *   0% -> 0.750
     *  25% -> 0.820
     *  50% -> 0.900
     *  75% -> 0.960
     * 100% -> 1.000
     */

    private function calculateConfidenceModifier(
        float $confidence
    ): float {

        $confidence =
            max(
                0.0,
                min(
                    100.0,
                    $confidence
                )
            );


        if (
            $confidence <= 25.0
        ) {

            $modifier =
                0.75
                +
                (
                    $confidence
                    /
                    25.0
                )
                *
                0.07;

        } elseif (
            $confidence <= 50.0
        ) {

            $modifier =
                0.82
                +
                (
                    (
                        $confidence
                        -
                        25.0
                    )
                    /
                    25.0
                )
                *
                0.08;

        } elseif (
            $confidence <= 75.0
        ) {

            $modifier =
                0.90
                +
                (
                    (
                        $confidence
                        -
                        50.0
                    )
                    /
                    25.0
                )
                *
                0.06;

        } else {

            $modifier =
                0.96
                +
                (
                    (
                        $confidence
                        -
                        75.0
                    )
                    /
                    25.0
                )
                *
                0.04;
        }


        return round(
            max(
                self::MIN_CONFIDENCE_MODIFIER,
                min(
                    1.0,
                    $modifier
                )
            ),
            3
        );
    }


    /*
     * ============================================================
     * SCORE NORMALISATION
     * ============================================================
     */

    private function normaliseScore(
        mixed $value,
        float $fallback
    ): float {

        if (
            !is_numeric(
                $value
            )
        ) {

            return $fallback;
        }


        return max(
            0.0,
            min(
                100.0,
                (float) $value
            )
        );
    }


    /*
     * ============================================================
     * PERCENTAGE NORMALISATION
     * ============================================================
     *
     * Supports both:
     *
     * 0.75 = 75%
     * 75   = 75%
     */

    private function normalisePercentage(
        mixed $value,
        float $fallback
    ): float {

        if (
            !is_numeric(
                $value
            )
        ) {

            return $fallback;
        }


        $value =
            (float) $value;


        if (
            $value >= 0.0
            &&
            $value <= 1.0
        ) {

            $value *=
                100.0;
        }


        return max(
            0.0,
            min(
                100.0,
                $value
            )
        );
    }


    /*
     * ============================================================
     * INVALID RESULT
     * ============================================================
     */

    private function invalidResult(
        string $message
    ): array {

        return [

            'status' =>
                'invalid',

            'message' =>
                $message,

            'formation' =>
                null,

            'starting_xi_score' =>
                null,

            'bench_score' =>
                null,

            'starting_xi' =>
                [],

            'bench' =>
                [],

            'formations' =>
                [],

            'formation_count' =>
                0
        ];
    }
}