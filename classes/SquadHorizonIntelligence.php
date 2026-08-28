<?php

class SquadHorizonIntelligence
{
    /**
     * Build a squad-level view across a requested number
     * of upcoming gameweeks.
     *
     * This class does not calculate player Expected Points.
     * It consumes projections already produced by the
     * player-level intelligence models and organises them
     * for squad-level analysis.
     */
    public function buildHorizon(
        array $squad,
        int $horizon
    ): array {

        /*
         * --------------------------------------------------------
         * BASIC INPUT VALIDATION
         * --------------------------------------------------------
         */

        if (
            empty(
                $squad
            )
            ||
            $horizon <= 0
        ) {

            return [
                'status' =>
                    'Unavailable',

                'player_count' =>
                    count(
                        $squad
                    ),

                'horizon' =>
                    $horizon,

                'gameweeks' =>
                    []
            ];
        }


        /*
         * --------------------------------------------------------
         * DISCOVER AVAILABLE GAMEWEEKS
         * --------------------------------------------------------
         *
         * The squad input contains player projections indexed by
         * gameweek.
         *
         * We collect the available gameweek numbers first so the
         * model does not assume that the horizon begins at a
         * particular FPL gameweek.
         */

        $availableGameweeks =
            [];


        foreach (
            $squad
            as $player
        ) {

            $playerGameweeks =
                $player[
                    'gameweeks'
                ]
                ?? [];


            if (
                !is_array(
                    $playerGameweeks
                )
            ) {

                continue;
            }


            foreach (
                $playerGameweeks
                as $gameweekKey => $projection
            ) {

                $gameweek =
                    null;


                if (
                    is_array(
                        $projection
                    )
                    &&
                    isset(
                        $projection[
                            'gameweek'
                        ]
                    )
                    &&
                    is_numeric(
                        $projection[
                            'gameweek'
                        ]
                    )
                ) {

                    $gameweek =
                        (int) $projection[
                            'gameweek'
                        ];

                } elseif (
                    is_numeric(
                        $gameweekKey
                    )
                ) {

                    $gameweek =
                        (int) $gameweekKey;
                }


                if (
                    $gameweek !== null
                    &&
                    $gameweek > 0
                ) {

                    $availableGameweeks[
                        $gameweek
                    ] =
                        $gameweek;
                }
            }
        }


        if (
            empty(
                $availableGameweeks
            )
        ) {

            return [
                'status' =>
                    'Unavailable',

                'player_count' =>
                    count(
                        $squad
                    ),

                'horizon' =>
                    $horizon,

                'gameweeks' =>
                    []
            ];
        }


        ksort(
            $availableGameweeks,
            SORT_NUMERIC
        );


        /*
         * --------------------------------------------------------
         * DETERMINE HORIZON
         * --------------------------------------------------------
         *
         * A planning horizon represents consecutive FPL gameweeks,
         * not simply the first N gameweeks for which some player
         * happens to have a projection.
         *
         * This distinction will become important when Blank
         * Gameweeks are introduced into squad analysis.
         */

        $firstGameweek =
            (int) reset(
                $availableGameweeks
            );


        $lastGameweek =
            $firstGameweek
            +
            $horizon
            -
            1;


        /*
         * --------------------------------------------------------
         * BUILD GAMEWEEK SQUAD VIEWS
         * --------------------------------------------------------
         */

        $gameweeks =
            [];


        for (
            $gameweek =
                $firstGameweek;
            $gameweek <= $lastGameweek;
            $gameweek++
        ) {

            $players =
                [];


            foreach (
                $squad
                as $player
            ) {

                $playerGameweeks =
                    $player[
                        'gameweeks'
                    ]
                    ?? [];


                $projection =
                    is_array(
                        $playerGameweeks
                    )
                        ? (
                            $playerGameweeks[
                                $gameweek
                            ]
                            ?? null
                        )
                        : null;


                $projectedPoints =
                    null;


                if (
                    is_array(
                        $projection
                    )
                    &&
                    isset(
                        $projection[
                            'projected_points'
                        ]
                    )
                    &&
                    is_numeric(
                        $projection[
                            'projected_points'
                        ]
                    )
                ) {

                    $projectedPoints =
                        (float) $projection[
                            'projected_points'
                        ];
                }


                /*
                 * ------------------------------------------------
                 * FIXTURE IDENTITY
                 * ------------------------------------------------
                 *
                 * Preserve the player's team and opponent for this
                 * gameweek when that information is available.
                 *
                 * Missing fixture identity remains null. We do not
                 * invent fixture information.
                 */

                $teamId =
                    null;


                $opponentTeamId =
                    null;


                if (
                    is_array(
                        $projection
                    )
                ) {

                    if (
                        isset(
                            $projection[
                                'team_id'
                            ]
                        )
                        &&
                        is_numeric(
                            $projection[
                                'team_id'
                            ]
                        )
                    ) {

                        $teamId =
                            (int) $projection[
                                'team_id'
                            ];
                    }


                    if (
                        isset(
                            $projection[
                                'opponent_team_id'
                            ]
                        )
                        &&
                        is_numeric(
                            $projection[
                                'opponent_team_id'
                            ]
                        )
                    ) {

                        $opponentTeamId =
                            (int) $projection[
                                'opponent_team_id'
                            ];
                    }
                }


                $players[] = [

                    'player_id' =>
                        isset(
                            $player[
                                'player_id'
                            ]
                        )
                            ? (int) $player[
                                'player_id'
                            ]
                            : null,

                    'name' =>
                        $player[
                            'name'
                        ]
                        ?? null,

                    'position' =>
                        $player[
                            'position'
                        ]
                        ?? null,

                    'projected_points' =>
                        $projectedPoints,

                    'team_id' =>
                        $teamId,

                    'opponent_team_id' =>
                        $opponentTeamId
                ];
            }


                        /*
             * ----------------------------------------------------
             * SELECT BEST LEGAL STARTING XI
             * ----------------------------------------------------
             */

            $selection =
                $this->selectStartingXI(
                    $players
                );


            $benchCoverage =
                $this->buildBenchCoverage(
                    $selection[
                        'starting_xi'
                    ],
                    $selection[
                        'bench'
                    ]
                );


            $gameweeks[
                $gameweek
            ] = [

                'gameweek' =>
                    $gameweek,

                'player_count' =>
                    count(
                        $players
                    ),

                'players' =>
                    $players,

                'starting_xi' =>
                    $selection[
                        'starting_xi'
                    ],

                'bench' =>
                    $selection[
                        'bench'
                    ],

                'starting_xi_projected_points' =>
                    $selection[
                        'starting_xi_projected_points'
                    ],

                'bench_coverage' =>
                    $benchCoverage
            ];
        }


         /*
         * --------------------------------------------------------
         * DEFENSIVE ROTATION
         * --------------------------------------------------------
         */

        $defensiveRotation =
            $this->buildDefensiveRotation(
                $gameweeks
            );


        /*
         * --------------------------------------------------------
         * GOALKEEPER ROTATION
         * --------------------------------------------------------
         */

        $goalkeeperRotation =
            $this->buildGoalkeeperRotation(
                $gameweeks
            );


        /*
         * --------------------------------------------------------
         * FIXTURE CLASHES
         * --------------------------------------------------------
         */

        $fixtureClashes =
            $this->buildFixtureClashes(
                $gameweeks
            );


                /*
         * --------------------------------------------------------
         * WEAK FIXTURE CLUSTERS
         * --------------------------------------------------------
         */

        $weakFixtureClusters =
            $this->buildWeakFixtureClusters(
                $gameweeks
            );


        /*
         * --------------------------------------------------------
         * POSITION DEPTH
         * --------------------------------------------------------
         */

        $positionDepth =
            $this->buildPositionDepth(
                $gameweeks
            );


        /*
         * --------------------------------------------------------
         * REPEATED BENCHING
         * --------------------------------------------------------
         */

        $repeatedBenching =
            $this->buildRepeatedBenching(
                $gameweeks
            );


        /*
         * --------------------------------------------------------
         * STRUCTURAL WEAKNESS
         * --------------------------------------------------------
         */

        $structuralWeakness =
            $this->buildStructuralWeakness(
                $gameweeks,
                $weakFixtureClusters,
                $positionDepth,
                $fixtureClashes
            );


        /*
         * --------------------------------------------------------
         * RESULT
         * --------------------------------------------------------
         */

        return [

            'status' =>
                'Available',

            'player_count' =>
                count(
                    $squad
                ),

            'horizon' =>
                $horizon,

            'gameweeks' =>
                $gameweeks,

            'defensive_rotation' =>
                $defensiveRotation,

            'goalkeeper_rotation' =>
                $goalkeeperRotation,

            'fixture_clashes' =>
                $fixtureClashes,

            'weak_fixture_clusters' =>
                $weakFixtureClusters,

            'position_depth' =>
                $positionDepth,

            'repeated_benching' =>
                $repeatedBenching,

            'structural_weakness' =>
                $structuralWeakness
        ];
    }    


    /**
     * Select the highest-projected legal FPL Starting XI
     * from one gameweek's squad projections.
     *
     * Legal formations require:
     *
     * - exactly 1 goalkeeper
     * - 3 to 5 defenders
     * - 2 to 5 midfielders
     * - 1 to 3 forwards
     * - exactly 11 starters
     *
     * All legal formations are evaluated so the selection is
     * based on projected points rather than a preferred shape.
     */
    private function selectStartingXI(
        array $players
    ): array {

        /*
         * --------------------------------------------------------
         * GROUP PLAYERS BY POSITION
         * --------------------------------------------------------
         */

        $playersByPosition = [
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

            $position =
                $player[
                    'position'
                ]
                ?? null;


            if (
                !isset(
                    $playersByPosition[
                        $position
                    ]
                )
            ) {

                continue;
            }


            $playersByPosition[
                $position
            ][] =
                $player;
        }


        /*
         * --------------------------------------------------------
         * SORT EACH POSITION BY PROJECTED POINTS
         * --------------------------------------------------------
         *
         * Highest projected player appears first.
         *
         * Player ID is used as a deterministic tie-breaker.
         */

        foreach (
            $playersByPosition
            as &$positionPlayers
        ) {

            usort(
                $positionPlayers,
                static function (
                    array $playerA,
                    array $playerB
                ): int {

                    $pointsA =
                        is_numeric(
                            $playerA[
                                'projected_points'
                            ]
                            ?? null
                        )
                            ? (float) $playerA[
                                'projected_points'
                            ]
                            : 0.0;


                    $pointsB =
                        is_numeric(
                            $playerB[
                                'projected_points'
                            ]
                            ?? null
                        )
                            ? (float) $playerB[
                                'projected_points'
                            ]
                            : 0.0;


                    if (
                        abs(
                            $pointsA
                            -
                            $pointsB
                        )
                        >
                        0.000001
                    ) {

                        return
                            $pointsA
                            <
                            $pointsB
                                ? 1
                                : -1;
                    }


                    return
                        (
                            (int) (
                                $playerA[
                                    'player_id'
                                ]
                                ?? PHP_INT_MAX
                            )
                        )
                        <=>
                        (
                            (int) (
                                $playerB[
                                    'player_id'
                                ]
                                ?? PHP_INT_MAX
                            )
                        );
                }
            );
        }


        unset(
            $positionPlayers
        );


        /*
         * --------------------------------------------------------
         * CHECK BASIC SQUAD AVAILABILITY
         * --------------------------------------------------------
         */

        if (
            count(
                $playersByPosition[
                    'GK'
                ]
            )
            <
            1
            ||
            count(
                $playersByPosition[
                    'DEF'
                ]
            )
            <
            3
            ||
            count(
                $playersByPosition[
                    'MID'
                ]
            )
            <
            2
            ||
            count(
                $playersByPosition[
                    'FWD'
                ]
            )
            <
            1
        ) {

            return [
                'starting_xi' =>
                    [],

                'bench' =>
                    $players,

                'starting_xi_projected_points' =>
                    null
            ];
        }


        /*
         * --------------------------------------------------------
         * EVALUATE EVERY LEGAL FORMATION
         * --------------------------------------------------------
         *
         * Possible FPL outfield formations are determined by:
         *
         * DEF: 3 to 5
         * MID: 2 to 5
         * FWD: 1 to 3
         *
         * and must total 10 outfield players.
         */

        $bestStartingXI =
            [];


        $bestProjectedPoints =
            null;


        for (
            $defenders = 3;
            $defenders <= 5;
            $defenders++
        ) {

            for (
                $midfielders = 2;
                $midfielders <= 5;
                $midfielders++
            ) {

                for (
                    $forwards = 1;
                    $forwards <= 3;
                    $forwards++
                ) {

                    if (
                        $defenders
                        +
                        $midfielders
                        +
                        $forwards
                        !==
                        10
                    ) {

                        continue;
                    }


                    if (
                        count(
                            $playersByPosition[
                                'DEF'
                            ]
                        )
                        <
                        $defenders
                        ||
                        count(
                            $playersByPosition[
                                'MID'
                            ]
                        )
                        <
                        $midfielders
                        ||
                        count(
                            $playersByPosition[
                                'FWD'
                            ]
                        )
                        <
                        $forwards
                    ) {

                        continue;
                    }


                    /*
                     * Highest projected players from each
                     * required position produce the best XI
                     * for this particular formation.
                     */

                    $candidateXI =
                        array_merge(
                            array_slice(
                                $playersByPosition[
                                    'GK'
                                ],
                                0,
                                1
                            ),

                            array_slice(
                                $playersByPosition[
                                    'DEF'
                                ],
                                0,
                                $defenders
                            ),

                            array_slice(
                                $playersByPosition[
                                    'MID'
                                ],
                                0,
                                $midfielders
                            ),

                            array_slice(
                                $playersByPosition[
                                    'FWD'
                                ],
                                0,
                                $forwards
                            )
                        );


                    $candidateProjectedPoints =
                        0.0;


                    foreach (
                        $candidateXI
                        as $player
                    ) {

                        if (
                            is_numeric(
                                $player[
                                    'projected_points'
                                ]
                                ?? null
                            )
                        ) {

                            $candidateProjectedPoints +=
                                (float) $player[
                                    'projected_points'
                                ];
                        }
                    }


                    if (
                        $bestProjectedPoints === null
                        ||
                        $candidateProjectedPoints
                        >
                        $bestProjectedPoints
                    ) {

                        $bestProjectedPoints =
                            $candidateProjectedPoints;


                        $bestStartingXI =
                            $candidateXI;
                    }
                }
            }
        }


        /*
         * --------------------------------------------------------
         * BUILD BENCH
         * --------------------------------------------------------
         */

        $startingPlayerIds =
            [];


        foreach (
            $bestStartingXI
            as $player
        ) {

            if (
                isset(
                    $player[
                        'player_id'
                    ]
                )
            ) {

                $startingPlayerIds[
                    (int) $player[
                        'player_id'
                    ]
                ] =
                    true;
            }
        }


        $bench =
            [];


        foreach (
            $players
            as $player
        ) {

            $playerId =
                isset(
                    $player[
                        'player_id'
                    ]
                )
                    ? (int) $player[
                        'player_id'
                    ]
                    : null;


            if (
                $playerId !== null
                &&
                isset(
                    $startingPlayerIds[
                        $playerId
                    ]
                )
            ) {

                continue;
            }


            $bench[] =
                $player;
        }


        /*
         * --------------------------------------------------------
         * RESULT
         * --------------------------------------------------------
         */

                return [
            'starting_xi' =>
                $bestStartingXI,

            'bench' =>
                $bench,

            'starting_xi_projected_points' =>
                $bestProjectedPoints
        ];
    }


    /**
     * Build objective bench coverage measurements for one
     * gameweek's selected Starting XI and bench.
     *
     * This method deliberately exposes factual measurements
     * only. Subjective squad-strength labels will be derived
     * later from these measurements.
     */
    private function buildBenchCoverage(
        array $startingXI,
        array $bench
    ): array {

        /*
         * --------------------------------------------------------
         * TOTAL BENCH PROJECTED POINTS
         * --------------------------------------------------------
         */

        $totalProjectedPoints =
            0.0;


        foreach (
            $bench
            as $player
        ) {

            if (
                is_numeric(
                    $player[
                        'projected_points'
                    ]
                    ?? null
                )
            ) {

                $totalProjectedPoints +=
                    (float) $player[
                        'projected_points'
                    ];
            }
        }


        /*
         * --------------------------------------------------------
         * OUTFIELD BENCH PLAYERS
         * --------------------------------------------------------
         *
         * Goalkeepers cannot substitute for outfield players,
         * so they are excluded when measuring immediate
         * outfield bench coverage.
         */

        $outfieldBench =
            array_values(
                array_filter(
                    $bench,
                    static function (
                        array $player
                    ): bool {

                        return
                            (
                                $player[
                                    'position'
                                ]
                                ?? null
                            )
                            !==
                            'GK';
                    }
                )
            );


        /*
         * --------------------------------------------------------
         * FIRST OUTFIELD SUBSTITUTE
         * --------------------------------------------------------
         *
         * For this first coverage contract, the strongest
         * projected outfield bench player represents the
         * squad's best immediate replacement option.
         */

        usort(
            $outfieldBench,
            static function (
                array $playerA,
                array $playerB
            ): int {

                $pointsA =
                    is_numeric(
                        $playerA[
                            'projected_points'
                        ]
                        ?? null
                    )
                        ? (float) $playerA[
                            'projected_points'
                        ]
                        : 0.0;


                $pointsB =
                    is_numeric(
                        $playerB[
                            'projected_points'
                        ]
                        ?? null
                    )
                        ? (float) $playerB[
                            'projected_points'
                        ]
                        : 0.0;


                if (
                    abs(
                        $pointsA
                        -
                        $pointsB
                    )
                    >
                    0.000001
                ) {

                    return
                        $pointsA
                        <
                        $pointsB
                            ? 1
                            : -1;
                }


                return
                    (
                        (int) (
                            $playerA[
                                'player_id'
                            ]
                            ?? PHP_INT_MAX
                        )
                    )
                    <=>
                    (
                        (int) (
                            $playerB[
                                'player_id'
                            ]
                            ?? PHP_INT_MAX
                        )
                    );
            }
        );


        $firstOutfieldSubstitute =
            $outfieldBench[
                0
            ]
            ?? null;


        /*
         * --------------------------------------------------------
         * WEAKEST OUTFIELD STARTER
         * --------------------------------------------------------
         */

        $outfieldStarters =
            array_values(
                array_filter(
                    $startingXI,
                    static function (
                        array $player
                    ): bool {

                        return
                            (
                                $player[
                                    'position'
                                ]
                                ?? null
                            )
                            !==
                            'GK';
                    }
                )
            );


        usort(
            $outfieldStarters,
            static function (
                array $playerA,
                array $playerB
            ): int {

                $pointsA =
                    is_numeric(
                        $playerA[
                            'projected_points'
                        ]
                        ?? null
                    )
                        ? (float) $playerA[
                            'projected_points'
                        ]
                        : 0.0;


                $pointsB =
                    is_numeric(
                        $playerB[
                            'projected_points'
                        ]
                        ?? null
                    )
                        ? (float) $playerB[
                            'projected_points'
                        ]
                        : 0.0;


                if (
                    abs(
                        $pointsA
                        -
                        $pointsB
                    )
                    >
                    0.000001
                ) {

                    return
                        $pointsA
                        <=>
                        $pointsB;
                }


                return
                    (
                        (int) (
                            $playerA[
                                'player_id'
                            ]
                            ?? PHP_INT_MAX
                        )
                    )
                    <=>
                    (
                        (int) (
                            $playerB[
                                'player_id'
                            ]
                            ?? PHP_INT_MAX
                        )
                    );
            }
        );


        $weakestOutfieldStarter =
            $outfieldStarters[
                0
            ]
            ?? null;


        /*
         * --------------------------------------------------------
         * COVERAGE GAP
         * --------------------------------------------------------
         *
         * This measures the projected-points drop from the
         * weakest outfield starter to the strongest available
         * outfield bench player.
         */

        $coverageGap =
            null;


        if (
            is_array(
                $weakestOutfieldStarter
            )
            &&
            is_array(
                $firstOutfieldSubstitute
            )
            &&
            is_numeric(
                $weakestOutfieldStarter[
                    'projected_points'
                ]
                ?? null
            )
            &&
            is_numeric(
                $firstOutfieldSubstitute[
                    'projected_points'
                ]
                ?? null
            )
        ) {

            $coverageGap =
                (float) $weakestOutfieldStarter[
                    'projected_points'
                ]
                -
                (float) $firstOutfieldSubstitute[
                    'projected_points'
                ];
        }


        /*
         * --------------------------------------------------------
         * RESULT
         * --------------------------------------------------------
         */

                return [
            'bench_player_count' =>
                count(
                    $bench
                ),

            'total_projected_points' =>
                $totalProjectedPoints,

            'first_outfield_substitute' =>
                $firstOutfieldSubstitute,

            'weakest_outfield_starter' =>
                $weakestOutfieldStarter,

            'coverage_gap' =>
                $coverageGap
        ];
    }


    /**
     * Build defensive rotation intelligence across the
     * complete squad-planning horizon.
     *
     * This records which defenders start and are benched in
     * each gameweek, then identifies defender pairs whose
     * projected preference changes across the horizon.
     */
    private function buildDefensiveRotation(
        array $gameweeks
    ): array {

        /*
         * --------------------------------------------------------
         * GAMEWEEK DEFENSIVE SELECTIONS
         * --------------------------------------------------------
         */

        $rotationGameweeks =
            [];


        $defenders =
            [];


        foreach (
            $gameweeks
            as $gameweekNumber => $gameweek
        ) {

            $startingDefenderIds =
                [];


            $benchedDefenderIds =
                [];


            foreach (
                $gameweek[
                    'starting_xi'
                ]
                ?? []
                as $player
            ) {

                if (
                    (
                        $player[
                            'position'
                        ]
                        ?? null
                    )
                    !==
                    'DEF'
                ) {

                    continue;
                }


                if (
                    isset(
                        $player[
                            'player_id'
                        ]
                    )
                ) {

                    $playerId =
                        (int) $player[
                            'player_id'
                        ];


                    $startingDefenderIds[] =
                        $playerId;


                    $defenders[
                        $playerId
                    ] =
                        true;
                }
            }


            foreach (
                $gameweek[
                    'bench'
                ]
                ?? []
                as $player
            ) {

                if (
                    (
                        $player[
                            'position'
                        ]
                        ?? null
                    )
                    !==
                    'DEF'
                ) {

                    continue;
                }


                if (
                    isset(
                        $player[
                            'player_id'
                        ]
                    )
                ) {

                    $playerId =
                        (int) $player[
                            'player_id'
                        ];


                    $benchedDefenderIds[] =
                        $playerId;


                    $defenders[
                        $playerId
                    ] =
                        true;
                }
            }


            sort(
                $startingDefenderIds,
                SORT_NUMERIC
            );


            sort(
                $benchedDefenderIds,
                SORT_NUMERIC
            );


            $rotationGameweeks[
                (int) $gameweekNumber
            ] = [
                'gameweek' =>
                    (int) $gameweekNumber,

                'starting_defender_ids' =>
                    $startingDefenderIds,

                'benched_defender_ids' =>
                    $benchedDefenderIds
            ];
        }


        /*
         * --------------------------------------------------------
         * DEFENDER PROJECTIONS BY GAMEWEEK
         * --------------------------------------------------------
         */

        $defenderProjections =
            [];


        foreach (
            $gameweeks
            as $gameweekNumber => $gameweek
        ) {

            foreach (
                $gameweek[
                    'players'
                ]
                ?? []
                as $player
            ) {

                if (
                    (
                        $player[
                            'position'
                        ]
                        ?? null
                    )
                    !==
                    'DEF'
                ) {

                    continue;
                }


                if (
                    !isset(
                        $player[
                            'player_id'
                        ]
                    )
                ) {

                    continue;
                }


                $playerId =
                    (int) $player[
                        'player_id'
                    ];


                $defenders[
                    $playerId
                ] =
                    true;


                $defenderProjections[
                    $playerId
                ][
                    (int) $gameweekNumber
                ] =
                    is_numeric(
                        $player[
                            'projected_points'
                        ]
                        ?? null
                    )
                        ? (float) $player[
                            'projected_points'
                        ]
                        : null;
            }
        }


        /*
         * --------------------------------------------------------
         * ROTATION PAIRS
         * --------------------------------------------------------
         *
         * Every pair of defenders is compared across the
         * horizon.
         *
         * A pair is considered a rotation pair only when the
         * preferred player changes at least once.
         */

        $defenderIds =
            array_map(
                'intval',
                array_keys(
                    $defenders
                )
            );


        sort(
            $defenderIds,
            SORT_NUMERIC
        );


        $rotationPairs =
            [];


        $defenderCount =
            count(
                $defenderIds
            );


        for (
            $firstIndex = 0;
            $firstIndex < $defenderCount;
            $firstIndex++
        ) {

            for (
                $secondIndex =
                    $firstIndex + 1;
                $secondIndex < $defenderCount;
                $secondIndex++
            ) {

                $firstPlayerId =
                    $defenderIds[
                        $firstIndex
                    ];


                $secondPlayerId =
                    $defenderIds[
                        $secondIndex
                    ];


                $preferredPlayerIds =
                    [];


                $previousPreferredPlayerId =
                    null;


                $alternationCount =
                    0;


                foreach (
                    $rotationGameweeks
                    as $gameweekNumber => $rotationGameweek
                ) {

                    $firstProjection =
                        $defenderProjections[
                            $firstPlayerId
                        ][
                            $gameweekNumber
                        ]
                        ?? null;


                    $secondProjection =
                        $defenderProjections[
                            $secondPlayerId
                        ][
                            $gameweekNumber
                        ]
                        ?? null;


                    /*
                     * A missing projection is not converted
                     * into a real zero-point forecast.
                     *
                     * If both projections are missing there is
                     * no defensible preference for that week.
                     */

                    if (
                        $firstProjection === null
                        &&
                        $secondProjection === null
                    ) {

                        $preferredPlayerIds[] =
                            null;

                        $previousPreferredPlayerId =
                            null;

                        continue;
                    }


                    if (
                        $firstProjection === null
                    ) {

                        $preferredPlayerId =
                            $secondPlayerId;

                    } elseif (
                        $secondProjection === null
                    ) {

                        $preferredPlayerId =
                            $firstPlayerId;

                    } elseif (
                        abs(
                            $firstProjection
                            -
                            $secondProjection
                        )
                        <=
                        0.000001
                    ) {

                        /*
                         * Deterministic tie-break.
                         */

                        $preferredPlayerId =
                            min(
                                $firstPlayerId,
                                $secondPlayerId
                            );

                    } else {

                        $preferredPlayerId =
                            $firstProjection
                            >
                            $secondProjection
                                ? $firstPlayerId
                                : $secondPlayerId;
                    }


                    $preferredPlayerIds[] =
                        $preferredPlayerId;


                    if (
                        $previousPreferredPlayerId !== null
                        &&
                        $preferredPlayerId
                        !==
                        $previousPreferredPlayerId
                    ) {

                        $alternationCount++;
                    }


                    $previousPreferredPlayerId =
                        $preferredPlayerId;
                }


                if (
                    $alternationCount <= 0
                ) {

                    continue;
                }


                $rotationPairs[] = [
                    'player_ids' => [
                        $firstPlayerId,
                        $secondPlayerId
                    ],

                    'preferred_player_ids' =>
                        $preferredPlayerIds,

                    'alternation_count' =>
                        $alternationCount
                ];
            }
        }


        /*
         * --------------------------------------------------------
         * RESULT
         * --------------------------------------------------------
         */

                return [
            'gameweek_count' =>
                count(
                    $rotationGameweeks
                ),

            'gameweeks' =>
                $rotationGameweeks,

            'rotation_pairs' =>
                $rotationPairs
        ];
    }


    /**
     * Build goalkeeper rotation intelligence across the
     * complete squad-planning horizon.
     *
     * This compares the two goalkeepers week by week,
     * identifies the preferred goalkeeper, measures how often
     * that preference changes, and compares rotating them with
     * simply using the best single goalkeeper throughout.
     */
    private function buildGoalkeeperRotation(
        array $gameweeks
    ): array {

        /*
         * --------------------------------------------------------
         * DISCOVER GOALKEEPERS
         * --------------------------------------------------------
         */

        $goalkeepers =
            [];


        foreach (
            $gameweeks
            as $gameweekNumber => $gameweek
        ) {

            foreach (
                $gameweek[
                    'players'
                ]
                ?? []
                as $player
            ) {

                if (
                    (
                        $player[
                            'position'
                        ]
                        ?? null
                    )
                    !==
                    'GK'
                ) {

                    continue;
                }


                if (
                    !isset(
                        $player[
                            'player_id'
                        ]
                    )
                ) {

                    continue;
                }


                $playerId =
                    (int) $player[
                        'player_id'
                    ];


                if (
                    !isset(
                        $goalkeepers[
                            $playerId
                        ]
                    )
                ) {

                    $goalkeepers[
                        $playerId
                    ] = [
                        'player_id' =>
                            $playerId,

                        'name' =>
                            $player[
                                'name'
                            ]
                            ?? null,

                        'gameweeks' =>
                            []
                    ];
                }


                $goalkeepers[
                    $playerId
                ][
                    'gameweeks'
                ][
                    (int) $gameweekNumber
                ] =
                    is_numeric(
                        $player[
                            'projected_points'
                        ]
                        ?? null
                    )
                        ? (float) $player[
                            'projected_points'
                        ]
                        : null;
            }
        }


        /*
         * --------------------------------------------------------
         * NORMALISE GOALKEEPER ORDER
         * --------------------------------------------------------
         */

        ksort(
            $goalkeepers,
            SORT_NUMERIC
        );


        $goalkeeperIds =
            array_map(
                'intval',
                array_keys(
                    $goalkeepers
                )
            );


        /*
         * --------------------------------------------------------
         * PREFERRED GOALKEEPER BY GAMEWEEK
         * --------------------------------------------------------
         */

        $preferredGoalkeeperIds =
            [];


        $previousPreferredGoalkeeperId =
            null;


        $alternationCount =
            0;


        $rotatingProjectedPoints =
            0.0;


        foreach (
            $gameweeks
            as $gameweekNumber => $gameweek
        ) {

            $bestGoalkeeperId =
                null;


            $bestProjection =
                null;


            foreach (
                $goalkeeperIds
                as $goalkeeperId
            ) {

                $projection =
                    $goalkeepers[
                        $goalkeeperId
                    ][
                        'gameweeks'
                    ][
                        (int) $gameweekNumber
                    ]
                    ?? null;


                /*
                 * Missing projection remains unknown.
                 * It is not converted into a genuine zero.
                 */

                if (
                    $projection === null
                ) {

                    continue;
                }


                if (
                    $bestProjection === null
                    ||
                    $projection
                    >
                    $bestProjection
                ) {

                    $bestProjection =
                        $projection;


                    $bestGoalkeeperId =
                        $goalkeeperId;

                    continue;
                }


                if (
                    abs(
                        $projection
                        -
                        $bestProjection
                    )
                    <=
                    0.000001
                    &&
                    $bestGoalkeeperId !== null
                    &&
                    $goalkeeperId
                    <
                    $bestGoalkeeperId
                ) {

                    /*
                     * Deterministic tie-break.
                     */

                    $bestGoalkeeperId =
                        $goalkeeperId;
                }
            }


            $preferredGoalkeeperIds[] =
                $bestGoalkeeperId;


            if (
                $bestProjection !== null
            ) {

                $rotatingProjectedPoints +=
                    $bestProjection;
            }


            if (
                $previousPreferredGoalkeeperId !== null
                &&
                $bestGoalkeeperId !== null
                &&
                $bestGoalkeeperId
                !==
                $previousPreferredGoalkeeperId
            ) {

                $alternationCount++;
            }


            $previousPreferredGoalkeeperId =
                $bestGoalkeeperId;
        }


        /*
         * --------------------------------------------------------
         * BEST SINGLE GOALKEEPER
         * --------------------------------------------------------
         */

        $bestSingleGoalkeeper =
            null;


        $bestSingleProjectedPoints =
            null;


        foreach (
            $goalkeepers
            as $goalkeeper
        ) {

            $projectedPoints =
                0.0;


            $hasProjection =
                false;


            foreach (
                $goalkeeper[
                    'gameweeks'
                ]
                as $projection
            ) {

                if (
                    $projection === null
                ) {

                    continue;
                }


                $projectedPoints +=
                    $projection;


                $hasProjection =
                    true;
            }


            if (
                !$hasProjection
            ) {

                continue;
            }


            if (
                $bestSingleProjectedPoints === null
                ||
                $projectedPoints
                >
                $bestSingleProjectedPoints
            ) {

                $bestSingleProjectedPoints =
                    $projectedPoints;


                $bestSingleGoalkeeper = [
                    'player_id' =>
                        $goalkeeper[
                            'player_id'
                        ],

                    'name' =>
                        $goalkeeper[
                            'name'
                        ],

                    'projected_points' =>
                        $projectedPoints
                ];

                continue;
            }


            if (
                abs(
                    $projectedPoints
                    -
                    $bestSingleProjectedPoints
                )
                <=
                0.000001
                &&
                is_array(
                    $bestSingleGoalkeeper
                )
                &&
                $goalkeeper[
                    'player_id'
                ]
                <
                $bestSingleGoalkeeper[
                    'player_id'
                ]
            ) {

                $bestSingleGoalkeeper = [
                    'player_id' =>
                        $goalkeeper[
                            'player_id'
                        ],

                    'name' =>
                        $goalkeeper[
                            'name'
                        ],

                    'projected_points' =>
                        $projectedPoints
                ];
            }
        }


        /*
         * --------------------------------------------------------
         * ROTATION GAIN
         * --------------------------------------------------------
         */

        $rotationGain =
            null;


        if (
            $bestSingleProjectedPoints !== null
        ) {

            $rotationGain =
                $rotatingProjectedPoints
                -
                $bestSingleProjectedPoints;
        }


        /*
         * --------------------------------------------------------
         * RESULT
         * --------------------------------------------------------
         */

        return [
            'gameweek_count' =>
                count(
                    $gameweeks
                ),

            'goalkeeper_count' =>
                count(
                    $goalkeepers
                ),

            'preferred_goalkeeper_ids' =>
                $preferredGoalkeeperIds,

            'alternation_count' =>
                $alternationCount,

            'rotating_projected_points' =>
                $rotatingProjectedPoints,

            'best_single_goalkeeper' =>
                $bestSingleGoalkeeper,

            'rotation_gain' =>
                $rotationGain
        ];
    }
    
    /**
     * Build Starting XI fixture-clash intelligence across
     * the complete squad-planning horizon.
     *
     * A clash exists when two Starting XI players directly
     * oppose one another in the same real fixture:
     *
     * Player A team     = Player B opponent
     * Player A opponent = Player B team
     */
    private function buildFixtureClashes(
        array $gameweeks
    ): array {

        $clashGameweeks =
            [];


        $totalClashCount =
            0;


        $gameweeksWithClashes =
            0;


        $worstGameweek =
            null;


        $maxClashCount =
            0;


        foreach (
            $gameweeks
            as $gameweekNumber => $gameweek
        ) {

            $startingXI =
                $gameweek[
                    'starting_xi'
                ]
                ?? [];


            $clashes =
                [];


            $startingCount =
                count(
                    $startingXI
                );


            /*
             * Compare each Starting XI pair exactly once.
             */

            for (
                $firstIndex = 0;
                $firstIndex < $startingCount;
                $firstIndex++
            ) {

                $firstPlayer =
                    $startingXI[
                        $firstIndex
                    ];


                $firstTeamId =
                    $firstPlayer[
                        'team_id'
                    ]
                    ?? null;


                $firstOpponentTeamId =
                    $firstPlayer[
                        'opponent_team_id'
                    ]
                    ?? null;


                if (
                    !is_numeric(
                        $firstTeamId
                    )
                    ||
                    !is_numeric(
                        $firstOpponentTeamId
                    )
                ) {

                    continue;
                }


                $firstTeamId =
                    (int) $firstTeamId;


                $firstOpponentTeamId =
                    (int) $firstOpponentTeamId;


                for (
                    $secondIndex =
                        $firstIndex + 1;

                    $secondIndex <
                        $startingCount;

                    $secondIndex++
                ) {

                    $secondPlayer =
                        $startingXI[
                            $secondIndex
                        ];


                    $secondTeamId =
                        $secondPlayer[
                            'team_id'
                        ]
                        ?? null;


                    $secondOpponentTeamId =
                        $secondPlayer[
                            'opponent_team_id'
                        ]
                        ?? null;


                    if (
                        !is_numeric(
                            $secondTeamId
                        )
                        ||
                        !is_numeric(
                            $secondOpponentTeamId
                        )
                    ) {

                        continue;
                    }


                    $secondTeamId =
                        (int) $secondTeamId;


                    $secondOpponentTeamId =
                        (int) $secondOpponentTeamId;


                    /*
                     * Both directions must agree before this is
                     * treated as a genuine opposing fixture.
                     */

                    if (
                        $firstTeamId
                        !==
                        $secondOpponentTeamId
                        ||
                        $firstOpponentTeamId
                        !==
                        $secondTeamId
                    ) {

                        continue;
                    }


                    $playerIds = [
                        (int) (
                            $firstPlayer[
                                'player_id'
                            ]
                            ?? 0
                        ),

                        (int) (
                            $secondPlayer[
                                'player_id'
                            ]
                            ?? 0
                        )
                    ];


                    sort(
                        $playerIds,
                        SORT_NUMERIC
                    );


                    $clashes[] = [
                        'player_ids' =>
                            $playerIds,

                        'players' => [
                            $firstPlayer,
                            $secondPlayer
                        ],

                        'team_ids' => [
                            $firstTeamId,
                            $secondTeamId
                        ]
                    ];
                }
            }


            $clashCount =
                count(
                    $clashes
                );


            $clashGameweeks[
                (int) $gameweekNumber
            ] = [
                'gameweek' =>
                    (int) $gameweekNumber,

                'clash_count' =>
                    $clashCount,

                'clashes' =>
                    $clashes
            ];


            $totalClashCount +=
                $clashCount;


            if (
                $clashCount > 0
            ) {

                $gameweeksWithClashes++;
            }


            /*
             * A tie keeps the earlier gameweek.
             */

            if (
                $clashCount
                >
                $maxClashCount
            ) {

                $maxClashCount =
                    $clashCount;


                $worstGameweek =
                    (int) $gameweekNumber;
            }
        }


        return [
            'gameweek_count' =>
                count(
                    $clashGameweeks
                ),

            'gameweeks' =>
                $clashGameweeks,

            'total_clash_count' =>
                $totalClashCount,

            'gameweeks_with_clashes' =>
                $gameweeksWithClashes,

            'worst_gameweek' =>
                $worstGameweek,

            'max_clash_count' =>
                $maxClashCount
        ];
    }
    
    /**
     * Build weak fixture cluster intelligence across the
     * complete squad-planning horizon.
     *
     * A weak player is a Starting XI player whose projected
     * points are below the weak projection threshold.
     *
     * A weak fixture cluster occurs when at least the cluster
     * threshold number of Starting XI players are weak in the
     * same gameweek.
     */
    private function buildWeakFixtureClusters(
        array $gameweeks
    ): array {

        /*
         * --------------------------------------------------------
         * THRESHOLDS
         * --------------------------------------------------------
         */

        $weakProjectionThreshold =
            3.0;


        $clusterThreshold =
            3;


        /*
         * --------------------------------------------------------
         * HORIZON STATE
         * --------------------------------------------------------
         */

        $clusterGameweekDetails =
            [];


        $clusterGameweeks =
            [];


        $clusterGameweekCount =
            0;


        $worstGameweek =
            null;


        $maxWeakPlayerCount =
            0;


        /*
         * --------------------------------------------------------
         * ANALYSE EACH GAMEWEEK
         * --------------------------------------------------------
         */

        foreach (
            $gameweeks
            as $gameweekNumber => $gameweek
        ) {

            $startingXI =
                $gameweek[
                    'starting_xi'
                ]
                ?? [];


            $weakPlayerIds =
                [];


            $weakPlayers =
                [];


            /*
             * ----------------------------------------------------
             * FIND WEAK STARTERS
             * ----------------------------------------------------
             */

            foreach (
                $startingXI
                as $player
            ) {

                $projectedPoints =
                    $player[
                        'projected_points'
                    ]
                    ?? null;


                /*
                 * Missing projection remains unknown.
                 *
                 * It must not be converted into zero and therefore
                 * must not automatically classify the player as
                 * weak.
                 */

                if (
                    !is_numeric(
                        $projectedPoints
                    )
                ) {

                    continue;
                }


                $projectedPoints =
                    (float) $projectedPoints;


                /*
                 * The threshold is strictly below 3.0.
                 *
                 * A player projected at exactly 3.0 is therefore
                 * not classified as weak.
                 */

                if (
                    $projectedPoints
                    >=
                    $weakProjectionThreshold
                ) {

                    continue;
                }


                $playerId =
                    isset(
                        $player[
                            'player_id'
                        ]
                    )
                        ? (int) $player[
                            'player_id'
                        ]
                        : null;


                if (
                    $playerId !== null
                ) {

                    $weakPlayerIds[] =
                        $playerId;
                }


                $weakPlayers[] =
                    $player;
            }


            sort(
                $weakPlayerIds,
                SORT_NUMERIC
            );


            $weakPlayerCount =
                count(
                    $weakPlayers
                );


            $isCluster =
                $weakPlayerCount
                >=
                $clusterThreshold;


            /*
             * ----------------------------------------------------
             * GAMEWEEK RESULT
             * ----------------------------------------------------
             */

            $clusterGameweekDetails[
                (int) $gameweekNumber
            ] = [

                'gameweek' =>
                    (int) $gameweekNumber,

                'weak_player_count' =>
                    $weakPlayerCount,

                'weak_player_ids' =>
                    $weakPlayerIds,

                'weak_players' =>
                    $weakPlayers,

                'is_cluster' =>
                    $isCluster
            ];


            /*
             * ----------------------------------------------------
             * HORIZON SUMMARY
             * ----------------------------------------------------
             */

            if (
                $isCluster
            ) {

                $clusterGameweeks[] =
                    (int) $gameweekNumber;


                $clusterGameweekCount++;
            }


            /*
             * Strictly greater means equal weak-player counts
             * retain the earliest gameweek encountered.
             */

            if (
                $weakPlayerCount
                >
                $maxWeakPlayerCount
            ) {

                $maxWeakPlayerCount =
                    $weakPlayerCount;


                $worstGameweek =
                    (int) $gameweekNumber;
            }
        }


        /*
         * --------------------------------------------------------
         * RESULT
         * --------------------------------------------------------
         */

        return [
            'gameweek_count' =>
                count(
                    $clusterGameweekDetails
                ),

            'weak_projection_threshold' =>
                $weakProjectionThreshold,

            'cluster_threshold' =>
                $clusterThreshold,

            'gameweeks' =>
                $clusterGameweekDetails,

            'cluster_gameweek_count' =>
                $clusterGameweekCount,

            'cluster_gameweeks' =>
                $clusterGameweeks,

            'worst_gameweek' =>
                $worstGameweek,

            'max_weak_player_count' =>
                $maxWeakPlayerCount
        ];
    }
    
    /**
     * Build position depth intelligence across the complete
     * squad-planning horizon.
     *
     * A usable player has projected points greater than or
     * equal to the usable projection threshold.
     *
     * Depth is measured beyond the minimum legal Starting XI
     * requirement for each FPL position.
     */
    private function buildPositionDepth(
        array $gameweeks
    ): array {

        /*
         * --------------------------------------------------------
         * THRESHOLD
         * --------------------------------------------------------
         */

        $usableProjectionThreshold =
            3.0;


        /*
         * --------------------------------------------------------
         * MINIMUM LEGAL XI REQUIREMENTS
         * --------------------------------------------------------
         */

        $minimumRequirements = [
            'GK' =>
                1,

            'DEF' =>
                3,

            'MID' =>
                2,

            'FWD' =>
                1
        ];


        /*
         * --------------------------------------------------------
         * HORIZON STATE
         * --------------------------------------------------------
         */

        $positionDepthGameweeks =
            [];


        $gameweeksWithDepthWeakness =
            0;


        $worstGameweek =
            null;


        $maxWeakPositionCount =
            0;


        /*
         * --------------------------------------------------------
         * ANALYSE EACH GAMEWEEK
         * --------------------------------------------------------
         */

        foreach (
            $gameweeks
            as $gameweekNumber => $gameweek
        ) {

            $players =
                $gameweek[
                    'players'
                ]
                ?? [];


            $positions = [
                'GK' => [
                    'minimum_required' =>
                        $minimumRequirements[
                            'GK'
                        ],

                    'usable_player_count' =>
                        0,

                    'depth_count' =>
                        0,

                    'usable_player_ids' =>
                        []
                ],

                'DEF' => [
                    'minimum_required' =>
                        $minimumRequirements[
                            'DEF'
                        ],

                    'usable_player_count' =>
                        0,

                    'depth_count' =>
                        0,

                    'usable_player_ids' =>
                        []
                ],

                'MID' => [
                    'minimum_required' =>
                        $minimumRequirements[
                            'MID'
                        ],

                    'usable_player_count' =>
                        0,

                    'depth_count' =>
                        0,

                    'usable_player_ids' =>
                        []
                ],

                'FWD' => [
                    'minimum_required' =>
                        $minimumRequirements[
                            'FWD'
                        ],

                    'usable_player_count' =>
                        0,

                    'depth_count' =>
                        0,

                    'usable_player_ids' =>
                        []
                ]
            ];


            /*
             * ----------------------------------------------------
             * FIND USABLE PLAYERS
             * ----------------------------------------------------
             */

            foreach (
                $players
                as $player
            ) {

                $position =
                    $player[
                        'position'
                    ]
                    ?? null;


                if (
                    !isset(
                        $positions[
                            $position
                        ]
                    )
                ) {

                    continue;
                }


                $projectedPoints =
                    $player[
                        'projected_points'
                    ]
                    ?? null;


                /*
                 * Missing projection remains unknown.
                 *
                 * It must not be treated as zero and must not be
                 * counted as usable.
                 */

                if (
                    !is_numeric(
                        $projectedPoints
                    )
                ) {

                    continue;
                }


                $projectedPoints =
                    (float) $projectedPoints;


                if (
                    $projectedPoints
                    <
                    $usableProjectionThreshold
                ) {

                    continue;
                }


                $positions[
                    $position
                ][
                    'usable_player_count'
                ]++;


                if (
                    isset(
                        $player[
                            'player_id'
                        ]
                    )
                ) {

                    $positions[
                        $position
                    ][
                        'usable_player_ids'
                    ][] =
                        (int) $player[
                            'player_id'
                        ];
                }
            }


            /*
             * ----------------------------------------------------
             * CALCULATE DEPTH
             * ----------------------------------------------------
             */

            $weakDepthPositions =
                [];


            foreach (
                $positions
                as $position => &$positionData
            ) {

                sort(
                    $positionData[
                        'usable_player_ids'
                    ],
                    SORT_NUMERIC
                );


                $depthCount =
                    $positionData[
                        'usable_player_count'
                    ]
                    -
                    $positionData[
                        'minimum_required'
                    ];


                /*
                 * Depth cannot be negative.
                 *
                 * If usable options fall below the legal minimum,
                 * the position still has zero available depth.
                 */

                if (
                    $depthCount < 0
                ) {

                    $depthCount =
                        0;
                }


                $positionData[
                    'depth_count'
                ] =
                    $depthCount;


                if (
                    $depthCount === 0
                ) {

                    $weakDepthPositions[] =
                        $position;
                }
            }


            unset(
                $positionData
            );


            $weakPositionCount =
                count(
                    $weakDepthPositions
                );


            /*
             * ----------------------------------------------------
             * GAMEWEEK RESULT
             * ----------------------------------------------------
             */

            $positionDepthGameweeks[
                (int) $gameweekNumber
            ] = [

                'gameweek' =>
                    (int) $gameweekNumber,

                'positions' =>
                    $positions,

                'weak_depth_positions' =>
                    $weakDepthPositions,

                'weak_position_count' =>
                    $weakPositionCount
            ];


            /*
             * ----------------------------------------------------
             * HORIZON SUMMARY
             * ----------------------------------------------------
             */

            if (
                $weakPositionCount > 0
            ) {

                $gameweeksWithDepthWeakness++;
            }


            /*
             * Strictly greater keeps the earliest gameweek when
             * two gameweeks have the same number of weaknesses.
             */

            if (
                $weakPositionCount
                >
                $maxWeakPositionCount
            ) {

                $maxWeakPositionCount =
                    $weakPositionCount;


                $worstGameweek =
                    (int) $gameweekNumber;
            }
        }


        /*
         * --------------------------------------------------------
         * RESULT
         * --------------------------------------------------------
         */

        return [
            'gameweek_count' =>
                count(
                    $positionDepthGameweeks
                ),

            'usable_projection_threshold' =>
                $usableProjectionThreshold,

            'minimum_requirements' =>
                $minimumRequirements,

            'gameweeks' =>
                $positionDepthGameweeks,

            'gameweeks_with_depth_weakness' =>
                $gameweeksWithDepthWeakness,

            'worst_gameweek' =>
                $worstGameweek,

            'max_weak_position_count' =>
                $maxWeakPositionCount
        ];
    }
    
    /**
     * Build repeated benching intelligence across the complete
     * squad-planning horizon.
     *
     * Repeated benching identifies players who are repeatedly
     * excluded from the projected optimal Starting XI.
     *
     * Meaningful repeated benching additionally requires the
     * player's average projected output while benched to meet
     * the meaningful projection threshold.
     */
    private function buildRepeatedBenching(
        array $gameweeks
    ): array {

        /*
         * --------------------------------------------------------
         * THRESHOLDS
         * --------------------------------------------------------
         */

        $repeatedBenchThreshold =
            2;


        $meaningfulProjectionThreshold =
            3.0;


        /*
         * --------------------------------------------------------
         * PLAYER STATE
         * --------------------------------------------------------
         */

        $players =
            [];


        /*
         * --------------------------------------------------------
         * ANALYSE EACH GAMEWEEK
         * --------------------------------------------------------
         */

        foreach (
            $gameweeks
            as $gameweekNumber => $gameweek
        ) {

            $gameweekPlayers =
                $gameweek[
                    'players'
                ]
                ?? [];


            $startingXI =
                $gameweek[
                    'starting_xi'
                ]
                ?? [];


            $bench =
                $gameweek[
                    'bench'
                ]
                ?? [];


            /*
             * ----------------------------------------------------
             * INITIALISE PLAYER RECORDS
             * ----------------------------------------------------
             *
             * Use the complete squad view so every player can be
             * represented even if they never appear on the bench.
             */

            foreach (
                $gameweekPlayers
                as $player
            ) {

                $playerId =
                    $player[
                        'player_id'
                    ]
                    ?? null;


                if (
                    !is_numeric(
                        $playerId
                    )
                ) {

                    continue;
                }


                $playerId =
                    (int) $playerId;


                if (
                    !isset(
                        $players[
                            $playerId
                        ]
                    )
                ) {

                    $players[
                        $playerId
                    ] = [

                        'player_id' =>
                            $playerId,

                        'name' =>
                            $player[
                                'name'
                            ]
                            ?? null,

                        'position' =>
                            $player[
                                'position'
                            ]
                            ?? null,

                        'start_count' =>
                            0,

                        'bench_count' =>
                            0,

                        'benched_gameweeks' =>
                            [],

                        'total_benched_projected_points' =>
                            0.0,

                        'benched_projection_count' =>
                            0,

                        'average_benched_projected_points' =>
                            null,

                        'is_repeatedly_benched' =>
                            false,

                        'is_meaningful_repeated_benching' =>
                            false
                    ];
                }
            }


            /*
             * ----------------------------------------------------
             * COUNT STARTS
             * ----------------------------------------------------
             */

            foreach (
                $startingXI
                as $player
            ) {

                $playerId =
                    $player[
                        'player_id'
                    ]
                    ?? null;


                if (
                    !is_numeric(
                        $playerId
                    )
                ) {

                    continue;
                }


                $playerId =
                    (int) $playerId;


                if (
                    isset(
                        $players[
                            $playerId
                        ]
                    )
                ) {

                    $players[
                        $playerId
                    ][
                        'start_count'
                    ]++;
                }
            }


            /*
             * ----------------------------------------------------
             * COUNT BENCH APPEARANCES AND BENCHED OUTPUT
             * ----------------------------------------------------
             */

            foreach (
                $bench
                as $player
            ) {

                $playerId =
                    $player[
                        'player_id'
                    ]
                    ?? null;


                if (
                    !is_numeric(
                        $playerId
                    )
                ) {

                    continue;
                }


                $playerId =
                    (int) $playerId;


                if (
                    !isset(
                        $players[
                            $playerId
                        ]
                    )
                ) {

                    continue;
                }


                $players[
                    $playerId
                ][
                    'bench_count'
                ]++;


                $players[
                    $playerId
                ][
                    'benched_gameweeks'
                ][] =
                    (int) $gameweekNumber;


                $projectedPoints =
                    $player[
                        'projected_points'
                    ]
                    ?? null;


                /*
                 * Missing projection is unknown.
                 *
                 * It still counts as a bench appearance, but it
                 * does not contribute zero to the projected-points
                 * average.
                 */

                if (
                    !is_numeric(
                        $projectedPoints
                    )
                ) {

                    continue;
                }


                $players[
                    $playerId
                ][
                    'total_benched_projected_points'
                ] +=
                    (float) $projectedPoints;


                $players[
                    $playerId
                ][
                    'benched_projection_count'
                ]++;
            }
        }


        /*
         * --------------------------------------------------------
         * CLASSIFY PLAYERS
         * --------------------------------------------------------
         */

        $repeatedlyBenchedPlayerIds =
            [];


        $meaningfulRepeatedBenchingPlayerIds =
            [];


        foreach (
            $players
            as $playerId => &$player
        ) {

            if (
                $player[
                    'benched_projection_count'
                ]
                >
                0
            ) {

                $player[
                    'average_benched_projected_points'
                ] =
                    $player[
                        'total_benched_projected_points'
                    ]
                    /
                    $player[
                        'benched_projection_count'
                    ];
            }


            $player[
                'is_repeatedly_benched'
            ] =
                $player[
                    'bench_count'
                ]
                >=
                $repeatedBenchThreshold;


            $player[
                'is_meaningful_repeated_benching'
            ] =
                $player[
                    'is_repeatedly_benched'
                ]
                &&
                is_numeric(
                    $player[
                        'average_benched_projected_points'
                    ]
                )
                &&
                $player[
                    'average_benched_projected_points'
                ]
                >=
                $meaningfulProjectionThreshold;


            if (
                $player[
                    'is_repeatedly_benched'
                ]
            ) {

                $repeatedlyBenchedPlayerIds[] =
                    (int) $playerId;
            }


            if (
                $player[
                    'is_meaningful_repeated_benching'
                ]
            ) {

                $meaningfulRepeatedBenchingPlayerIds[] =
                    (int) $playerId;
            }
        }


        unset(
            $player
        );


        sort(
            $repeatedlyBenchedPlayerIds,
            SORT_NUMERIC
        );


        sort(
            $meaningfulRepeatedBenchingPlayerIds,
            SORT_NUMERIC
        );


        /*
         * --------------------------------------------------------
         * RESULT
         * --------------------------------------------------------
         */

        return [
            'gameweek_count' =>
                count(
                    $gameweeks
                ),

            'repeated_bench_threshold' =>
                $repeatedBenchThreshold,

            'meaningful_projection_threshold' =>
                $meaningfulProjectionThreshold,

            'players' =>
                $players,

            'repeatedly_benched_player_count' =>
                count(
                    $repeatedlyBenchedPlayerIds
                ),

            'repeatedly_benched_player_ids' =>
                $repeatedlyBenchedPlayerIds,

            'meaningful_repeated_benching_player_count' =>
                count(
                    $meaningfulRepeatedBenchingPlayerIds
                ),

            'meaningful_repeated_benching_player_ids' =>
                $meaningfulRepeatedBenchingPlayerIds
        ];
    }
    
    /**
     * Build explainable structural weakness intelligence across
     * the squad-planning horizon.
     *
     * This layer does not create new projection models.
     *
     * Instead it combines existing squad-horizon intelligence
     * to identify when several structural problems align within
     * the same gameweek.
     */
    private function buildStructuralWeakness(
        array $gameweeks,
        array $weakFixtureClusters,
        array $positionDepth,
        array $fixtureClashes
    ): array {

        /*
         * --------------------------------------------------------
         * SEVERITY LEVELS
         * --------------------------------------------------------
         *
         * Severity is deliberately transparent.
         *
         * No weighting is applied at this stage:
         *
         * 0 problems = None
         * 1 problem  = Low
         * 2 problems = Moderate
         * 3 problems = High
         * 4 problems = Severe
         */

        $severityLevels = [
            0 =>
                'None',

            1 =>
                'Low',

            2 =>
                'Moderate',

            3 =>
                'High',

            4 =>
                'Severe'
        ];


        /*
         * --------------------------------------------------------
         * SOURCE GAMEWEEKS
         * --------------------------------------------------------
         */

        $weakFixtureGameweeks =
            $weakFixtureClusters[
                'gameweeks'
            ]
            ?? [];


        $positionDepthGameweeks =
            $positionDepth[
                'gameweeks'
            ]
            ?? [];


        $fixtureClashGameweeks =
            $fixtureClashes[
                'gameweeks'
            ]
            ?? [];


        /*
         * --------------------------------------------------------
         * HORIZON STATE
         * --------------------------------------------------------
         */

        $structuralGameweeks =
            [];


        $gameweeksWithProblems =
            0;


        $worstGameweek =
            null;


        $maxProblemCount =
            0;


        /*
         * --------------------------------------------------------
         * ANALYSE EACH GAMEWEEK
         * --------------------------------------------------------
         */

        foreach (
            $gameweeks
            as $gameweekNumber => $gameweek
        ) {

            $weakFixtureGameweek =
                $weakFixtureGameweeks[
                    $gameweekNumber
                ]
                ?? [];


            $positionDepthGameweek =
                $positionDepthGameweeks[
                    $gameweekNumber
                ]
                ?? [];


            $fixtureClashGameweek =
                $fixtureClashGameweeks[
                    $gameweekNumber
                ]
                ?? [];


            /*
             * ----------------------------------------------------
             * WEAK FIXTURE CLUSTER
             * ----------------------------------------------------
             */

            $hasWeakFixtureCluster =
                (
                    $weakFixtureGameweek[
                        'is_cluster'
                    ]
                    ?? false
                )
                ===
                true;


            /*
             * ----------------------------------------------------
             * POSITION DEPTH WEAKNESS
             * ----------------------------------------------------
             */

            $weakPositionCount =
                $positionDepthGameweek[
                    'weak_position_count'
                ]
                ?? 0;


            $hasPositionDepthWeakness =
                is_numeric(
                    $weakPositionCount
                )
                &&
                (int) $weakPositionCount
                >
                0;


            /*
             * ----------------------------------------------------
             * UNCOVERED WEAK XI
             * ----------------------------------------------------
             *
             * A weak Starting XI player is structurally uncovered
             * when that player's position has no spare usable
             * depth beyond the minimum legal positional
             * requirement.
             *
             * This does NOT attempt to find a better substitution.
             * The Starting XI optimiser has already selected the
             * highest-projected legal XI.
             */

            $hasUncoveredWeakXI =
                false;


            $weakPlayers =
                $weakFixtureGameweek[
                    'weak_players'
                ]
                ?? [];


            $positionData =
                $positionDepthGameweek[
                    'positions'
                ]
                ?? [];


            foreach (
                $weakPlayers
                as $weakPlayer
            ) {

                $position =
                    $weakPlayer[
                        'position'
                    ]
                    ?? null;


                if (
                    !is_string(
                        $position
                    )
                    ||
                    $position
                    === ''
                ) {

                    continue;
                }


                $depthCount =
                    $positionData[
                        $position
                    ][
                        'depth_count'
                    ]
                    ?? null;


                if (
                    is_numeric(
                        $depthCount
                    )
                    &&
                    (int) $depthCount
                    ===
                    0
                ) {

                    $hasUncoveredWeakXI =
                        true;

                    break;
                }
            }


            /*
             * ----------------------------------------------------
             * FIXTURE CLASHES
             * ----------------------------------------------------
             */

            $clashCount =
                $fixtureClashGameweek[
                    'clash_count'
                ]
                ?? 0;


            $hasFixtureClashes =
                is_numeric(
                    $clashCount
                )
                &&
                (int) $clashCount
                >
                0;


            /*
             * ----------------------------------------------------
             * EXPLAINABLE PROBLEM LABELS
             * ----------------------------------------------------
             */

            $problems =
                [];


            if (
                $hasWeakFixtureCluster
            ) {

                $problems[] =
                    'Weak Fixture Cluster';
            }


            if (
                $hasPositionDepthWeakness
            ) {

                $problems[] =
                    'Position Depth Weakness';
            }


            if (
                $hasUncoveredWeakXI
            ) {

                $problems[] =
                    'Uncovered Weak XI';
            }


            if (
                $hasFixtureClashes
            ) {

                $problems[] =
                    'Fixture Clash';
            }


            /*
             * ----------------------------------------------------
             * SEVERITY
             * ----------------------------------------------------
             */

            $problemCount =
                count(
                    $problems
                );


            $severity =
                $severityLevels[
                    $problemCount
                ]
                ??
                'Severe';


            /*
             * ----------------------------------------------------
             * HORIZON SUMMARY
             * ----------------------------------------------------
             */

            if (
                $problemCount
                >
                0
            ) {

                $gameweeksWithProblems++;
            }


            /*
             * Only replace the worst gameweek when the problem
             * count is strictly greater.
             *
             * Therefore equal severity retains the earliest
             * gameweek, matching our other horizon summaries.
             */

            if (
                $problemCount
                >
                $maxProblemCount
            ) {

                $maxProblemCount =
                    $problemCount;


                $worstGameweek =
                    (int) $gameweekNumber;
            }


            /*
             * ----------------------------------------------------
             * GAMEWEEK RESULT
             * ----------------------------------------------------
             */

            $structuralGameweeks[
                $gameweekNumber
            ] = [

                'gameweek' =>
                    (int) $gameweekNumber,

                'has_weak_fixture_cluster' =>
                    $hasWeakFixtureCluster,

                'has_position_depth_weakness' =>
                    $hasPositionDepthWeakness,

                'has_uncovered_weak_xi' =>
                    $hasUncoveredWeakXI,

                'has_fixture_clashes' =>
                    $hasFixtureClashes,

                'problem_count' =>
                    $problemCount,

                'severity' =>
                    $severity,

                'problems' =>
                    $problems
            ];
        }


        /*
         * --------------------------------------------------------
         * MAXIMUM SEVERITY
         * --------------------------------------------------------
         */

        $maxSeverity =
            $severityLevels[
                $maxProblemCount
            ]
            ??
            'Severe';


        /*
         * --------------------------------------------------------
         * RESULT
         * --------------------------------------------------------
         */

        return [
            'gameweek_count' =>
                count(
                    $gameweeks
                ),

            'severity_levels' =>
                $severityLevels,

            'gameweeks' =>
                $structuralGameweeks,

            'gameweeks_with_problems' =>
                $gameweeksWithProblems,

            'worst_gameweek' =>
                $worstGameweek,

            'max_problem_count' =>
                $maxProblemCount,

            'max_severity' =>
                $maxSeverity
        ];
    }
    
}