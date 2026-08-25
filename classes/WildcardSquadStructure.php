<?php

class WildcardSquadStructure
{
    /*
     * ============================================================
     * LEGAL STARTING FORMATIONS
     * ============================================================
     *
     * All formations use:
     *
     * 1 GK
     * 3-5 DEF
     * 2-5 MID
     * 1-3 FWD
     *
     * Total = 11 starters
     */

    private const FORMATIONS = [

        '3-4-3' => [
            'GK' => 1,
            'DEF' => 3,
            'MID' => 4,
            'FWD' => 3
        ],

        '3-5-2' => [
            'GK' => 1,
            'DEF' => 3,
            'MID' => 5,
            'FWD' => 2
        ],

        '4-3-3' => [
            'GK' => 1,
            'DEF' => 4,
            'MID' => 3,
            'FWD' => 3
        ],

        '4-4-2' => [
            'GK' => 1,
            'DEF' => 4,
            'MID' => 4,
            'FWD' => 2
        ],

        '4-5-1' => [
            'GK' => 1,
            'DEF' => 4,
            'MID' => 5,
            'FWD' => 1
        ],

        '5-2-3' => [
            'GK' => 1,
            'DEF' => 5,
            'MID' => 2,
            'FWD' => 3
        ],

        '5-3-2' => [
            'GK' => 1,
            'DEF' => 5,
            'MID' => 3,
            'FWD' => 2
        ],

        '5-4-1' => [
            'GK' => 1,
            'DEF' => 5,
            'MID' => 4,
            'FWD' => 1
        ]
    ];


    /*
     * ============================================================
     * PUBLIC API
     * ============================================================
     */

    public function analyze(
        array $squad
    ): array {

        if (
            count(
                $squad
            )
            !== 15
        ) {

            return $this->invalidResult(
                'Wildcard squad must contain exactly 15 players.'
            );
        }


        /*
         * --------------------------------------------------------
         * NORMALISE PLAYERS
         * --------------------------------------------------------
         */

        $players =
            [];


        foreach (
            $squad
            as $player
        ) {

            $normalised =
                $this->normalisePlayer(
                    $player
                );


            if ($normalised === null) {

                return $this->invalidResult(
                    'Wildcard squad contains invalid player data.'
                );
            }


            $players[] =
                $normalised;
        }


        /*
         * --------------------------------------------------------
         * SPLIT BY POSITION
         * --------------------------------------------------------
         */

        $byPosition = [

            'GK' => [],
            'DEF' => [],
            'MID' => [],
            'FWD' => []
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
         * VERIFY 15-PLAYER SQUAD STRUCTURE
         * --------------------------------------------------------
         */

        $requiredSquadCounts = [

            'GK' => 2,
            'DEF' => 5,
            'MID' => 5,
            'FWD' => 3
        ];


        foreach (
            $requiredSquadCounts
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
                    'Invalid wildcard squad position structure.'
                );
            }
        }


        /*
         * --------------------------------------------------------
         * RANK PLAYERS WITHIN POSITION
         * --------------------------------------------------------
         */

        foreach (
            $byPosition
            as &$positionPlayers
        ) {

            usort(
                $positionPlayers,
                static function (
                    array $a,
                    array $b
                ): int {

                    $scoreA =
                        (float) (
                            $a[
                                'wildcard_score'
                            ]
                            ?? 0
                        );


                    $scoreB =
                        (float) (
                            $b[
                                'wildcard_score'
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


                    $intelligenceA =
                        (float) (
                            $a[
                                'intelligence_score'
                            ]
                            ?? 0
                        );


                    $intelligenceB =
                        (float) (
                            $b[
                                'intelligence_score'
                            ]
                            ?? 0
                        );


                    return $intelligenceB
                        <=>
                        $intelligenceA;
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


            foreach (
                $requirements
                as $position => $required
            ) {

                if (
                    $position === 'GK'
                ) {

                    $selected =
                        [];


                    foreach (
                        $byPosition[
                            'GK'
                        ]
                        as $goalkeeper
                    ) {

                        if (
                            (
                                $goalkeeper[
                                    'starter_eligible'
                                ]
                                ?? true
                            )
                            !== true
                        ) {

                            continue;
                        }


                        $selected[] =
                            $goalkeeper;

                        break;
                    }


                    if (
                        count(
                            $selected
                        )
                        !== 1
                    ) {

                        continue 2;
                    }

                } else {

                    $selected =
                        array_slice(
                            $byPosition[
                                $position
                            ],
                            0,
                            $required
                        );
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
             * Bench is every non-starting player.
             */

            $bench =
                [];


            foreach (
                $players
                as $player
            ) {

                if (
                    !isset(
                        $starterIds[
                            $player[
                                'player_id'
                            ]
                        ]
                    )
                ) {

                    $bench[] =
                        $player;
                }
            }


            /*
             * ----------------------------------------------------
             * ORDER BENCH
             * ----------------------------------------------------
             *
             * Backup goalkeeper is kept separate.
             * Outfield substitutes are ordered strongest first.
             */

            $benchGoalkeeper =
                null;


            $benchOutfield =
                [];


            foreach (
                $bench
                as $player
            ) {

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


            usort(
                $benchOutfield,
                static function (
                    array $a,
                    array $b
                ): int {

                    return (
                        (float) (
                            $b[
                                'wildcard_score'
                            ]
                            ?? 0
                        )
                    )
                    <=>
                    (
                        (float) (
                            $a[
                                'wildcard_score'
                            ]
                            ?? 0
                        )
                    );
                }
            );


            /*
             * ----------------------------------------------------
             * SCORE STARTING XI
             * ----------------------------------------------------
             */

            $startingScore =
                $this->averageScore(
                    $startingXI
                );


            /*
             * ----------------------------------------------------
             * SCORE BENCH
             * ----------------------------------------------------
             *
             * Outfield substitutes are weighted differently:
             *
             * 1st bench = 50%
             * 2nd bench = 30%
             * 3rd bench = 15%
             *
             * Backup GK = 5%
             *
             * These weights are intentionally relative rather than
             * pretending all four bench players have equal impact.
             */

            $benchScore =
                0.0;


            $benchWeightTotal =
                0.0;


            $outfieldBenchWeights = [

                0.50,
                0.30,
                0.15
            ];


            foreach (
                $benchOutfield
                as $index => $player
            ) {

                $weight =
                    $outfieldBenchWeights[
                        $index
                    ]
                    ?? 0.0;


                if ($weight <= 0) {
                    continue;
                }


                $benchScore +=
                    (
                        (float) $player[
                            'wildcard_score'
                        ]
                    )
                    *
                    $weight;


                $benchWeightTotal +=
                    $weight;
            }


            if (
                $benchGoalkeeper !== null
            ) {

                $benchScore +=
                    (
                        (float) $benchGoalkeeper[
                            'wildcard_score'
                        ]
                    )
                    *
                    0.05;


                $benchWeightTotal +=
                    0.05;
            }


            if (
                $benchWeightTotal > 0
            ) {

                $benchScore /=
                    $benchWeightTotal;
            }


            /*
             * ----------------------------------------------------
             * FORMATION SCORE
             * ----------------------------------------------------
             *
             * For structure analysis:
             *
             * 85% starting XI
             * 15% bench
             */

            $formationScore =
                (
                    $startingScore
                    *
                    0.85
                )
                +
                (
                    $benchScore
                    *
                    0.15
                );


            $formationResults[] = [

                'formation' =>
                    $formation,

                'score' =>
                    round(
                        $formationScore,
                        2
                    ),

                'starting_xi_score' =>
                    round(
                        $startingScore,
                        2
                    ),

                'bench_score' =>
                    round(
                        $benchScore,
                        2
                    ),

                'starting_xi' =>
                    $this->sortPlayersForDisplay(
                        $startingXI
                    ),

                'bench' =>
                    $this->buildOrderedBench(
                        $benchGoalkeeper,
                        $benchOutfield
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
                            'score'
                        ]
                        ?? 0
                    );


                $scoreB =
                    (float) (
                        $b[
                            'score'
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


                return (
                    (float) (
                        $b[
                            'starting_xi_score'
                        ]
                        ?? 0
                    )
                )
                <=>
                (
                    (float) (
                        $a[
                            'starting_xi_score'
                        ]
                        ?? 0
                    )
                );
            }
        );


        $best =
            $formationResults[0]
            ?? null;


        if ($best === null) {

            return $this->invalidResult(
                'Unable to determine wildcard squad structure.'
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

            'formation' =>
                $best[
                    'formation'
                ],

            'structure_score' =>
                $best[
                    'score'
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
                $formationResults
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


        $wildcardScore =
            $player[
                'wildcard_score'
            ]
            ?? null;


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
            ||
            !is_numeric(
                $wildcardScore
            )
        ) {

            return null;
        }


        return [

            'player_id' =>
                $playerId,

            'name' =>
                $player[
                    'name'
                ]
                ?? (
                    'Player '
                    . $playerId
                ),

            'team_id' =>
                (int) (
                    $player[
                        'team_id'
                    ]
                    ?? 0
                ),

            'team_name' =>
                $player[
                    'team_name'
                ]
                ?? null,

            'position' =>
                $position,

            'price' =>
                is_numeric(
                    $player[
                        'price'
                    ]
                    ?? null
                )
                    ? (float) $player[
                        'price'
                    ]
                    : null,

            'intelligence_score' =>
                is_numeric(
                    $player[
                        'intelligence_score'
                    ]
                    ?? null
                )
                    ? (float) $player[
                        'intelligence_score'
                    ]
                    : null,

            'wildcard_score' =>
                (float) $wildcardScore,
                
            'starter_eligible' =>
                array_key_exists(
                    'starter_eligible',
                    $player
                )
                    ? (bool) $player[
                        'starter_eligible'
                    ]
                    : true,
        ];
    }


    /*
     * ============================================================
     * SCORE HELPERS
     * ============================================================
     */

    private function averageScore(
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
                        'wildcard_score'
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
     * DISPLAY ORDER HELPERS
     * ============================================================
     */

    private function sortPlayersForDisplay(
        array $players
    ): array {

        $positionOrder = [

            'GK' => 1,
            'DEF' => 2,
            'MID' => 3,
            'FWD' => 4
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
                    ?? 999;


                $positionB =
                    $positionOrder[
                        $b[
                            'position'
                        ]
                        ?? ''
                    ]
                    ?? 999;


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
                            'wildcard_score'
                        ]
                        ?? 0
                    )
                )
                <=>
                (
                    (float) (
                        $a[
                            'wildcard_score'
                        ]
                        ?? 0
                    )
                );
            }
        );


        return $players;
    }


    private function buildOrderedBench(
        ?array $goalkeeper,
        array $outfield
    ): array {

        $bench =
            [];


        /*
         * FPL displays the backup goalkeeper in the first
         * visual bench slot.
         */
        if (
            $goalkeeper !== null
        ) {

            $goalkeeper[
                'bench_order'
            ] =
                1;


            $bench[] =
                $goalkeeper;
        }


        /*
         * Outfield substitutes retain their existing priority order,
         * but move to visual bench positions 2-4.
         */
        foreach (
            $outfield
            as $index => $player
        ) {

            $player[
                'bench_order'
            ] =
                $index + 2;


            $bench[] =
                $player;
        }


        return $bench;
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

            'structure_score' =>
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
                []
        ];
    }
}