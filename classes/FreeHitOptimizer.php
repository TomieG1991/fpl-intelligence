<?php

class FreeHitOptimizer
{
    /*
     * ============================================================
     * PUBLIC API
     * ============================================================
     */

    public function optimize(
        array $players = [],
        float $budget = 100.0
    ): array {

        /*
         * --------------------------------------------------------
         * BASIC INPUT VALIDATION
         * --------------------------------------------------------
         */

        if (
            empty(
                $players
            )
            ||
            $budget <= 0
        ) {

            return [
                'status' =>
                    'invalid',

                'message' =>
                    'Invalid Free Hit player pool or budget.'
            ];
        }


        /*
         * --------------------------------------------------------
         * MINIMUM SQUAD SIZE
         * --------------------------------------------------------
         */

        if (
            count(
                $players
            )
            < 15
        ) {

            return [
                'status' =>
                    'invalid',

                'message' =>
                    'Free Hit player pool does not contain enough players.'
            ];
        }


        /*
         * --------------------------------------------------------
         * INITIAL ONE-GAMEWEEK SELECTION
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
                array_key_exists(
                    $position,
                    $playersByPosition
                )
            ) {

                $playersByPosition[
                    $position
                ][] =
                    $player;
            }
        }


        foreach (
            $playersByPosition
            as &$positionPlayers
        ) {

            usort(
                $positionPlayers,
                static function (
                    array $a,
                    array $b
                ): int {

                    $projectedPointsA =
                        is_numeric(
                            $a[
                                'projected_points'
                            ]
                            ?? null
                        )
                            ? (float) $a[
                                'projected_points'
                            ]
                            : 0.0;


                    $projectedPointsB =
                        is_numeric(
                            $b[
                                'projected_points'
                            ]
                            ?? null
                        )
                            ? (float) $b[
                                'projected_points'
                            ]
                            : 0.0;


                    if (
                        $projectedPointsA
                        !==
                        $projectedPointsB
                    ) {

                        return
                            $projectedPointsB
                            <=>
                            $projectedPointsA;
                    }


                    $playerIdA =
                        is_numeric(
                            $a[
                                'player_id'
                            ]
                            ?? null
                        )
                            ? (int) $a[
                                'player_id'
                            ]
                            : PHP_INT_MAX;


                    $playerIdB =
                        is_numeric(
                            $b[
                                'player_id'
                            ]
                            ?? null
                        )
                            ? (int) $b[
                                'player_id'
                            ]
                            : PHP_INT_MAX;


                    return
                        $playerIdA
                        <=>
                        $playerIdB;
                }
            );
        }


        unset(
            $positionPlayers
        );


        /*
         * --------------------------------------------------------
         * BUILD LEGAL POSITIONAL SQUAD
         * --------------------------------------------------------
         */

        $requiredPositionCounts = [
            'GK' =>
                2,

            'DEF' =>
                5,

            'MID' =>
                5,

            'FWD' =>
                3
        ];


        $squad = [];

        $selectionTeamCounts = [];


        foreach (
            $requiredPositionCounts
            as $position =>
                $requiredCount
        ) {

            $selectedForPosition =
                0;


            foreach (
                $playersByPosition[
                    $position
                ]
                as $player
            ) {

                $teamId =
                    $player[
                        'team_id'
                    ]
                    ?? null;


                /*
                 * Invalid team data is still allowed through
                 * selection so the existing validation below
                 * remains responsible for rejecting it.
                 */
                if (
                    !is_numeric(
                        $teamId
                    )
                    ||
                    (int) $teamId <= 0
                ) {

                    $squad[] =
                        $player;


                    $selectedForPosition++;


                    if (
                        $selectedForPosition
                        >=
                        $requiredCount
                    ) {

                        break;
                    }


                    continue;
                }


                $teamId =
                    (int) $teamId;


                $currentTeamCount =
                    $selectionTeamCounts[
                        $teamId
                    ]
                    ?? 0;


                /*
                 * Skip this candidate when selecting them would
                 * create more than three players from one club.
                 *
                 * Because candidates are already ordered by
                 * projected_points, the next candidate becomes
                 * the next-best available alternative.
                 */
                if (
                    $currentTeamCount
                    >=
                    3
                ) {

                    continue;
                }


                $squad[] =
                    $player;


                $selectionTeamCounts[
                    $teamId
                ] =
                    $currentTeamCount
                    +
                    1;


                $selectedForPosition++;


                if (
                    $selectedForPosition
                    >=
                    $requiredCount
                ) {

                    break;
                }
            }
        }


        /*
         * --------------------------------------------------------
         * DUPLICATE PLAYER VALIDATION
         * --------------------------------------------------------
         */

        $playerIds = [];


        foreach (
            $squad
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

                return [
                    'status' =>
                        'invalid',

                    'message' =>
                        'Free Hit squad contains an invalid player ID.'
                ];
            }


            $playerId =
                (int) $playerId;


            if (
                $playerId <= 0
            ) {

                return [
                    'status' =>
                        'invalid',

                    'message' =>
                        'Free Hit squad contains an invalid player ID.'
                ];
            }


            if (
                isset(
                    $playerIds[
                        $playerId
                    ]
                )
            ) {

                return [
                    'status' =>
                        'invalid',

                    'message' =>
                        'Free Hit squad contains duplicate players.'
                ];
            }


            $playerIds[
                $playerId
            ] =
                true;
        }


        /*
         * --------------------------------------------------------
         * POSITION STRUCTURE VALIDATION
         * --------------------------------------------------------
         */

        $positionCounts = [
            'GK' =>
                0,

            'DEF' =>
                0,

            'MID' =>
                0,

            'FWD' =>
                0
        ];


        foreach (
            $squad
            as $player
        ) {

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
                array_key_exists(
                    $position,
                    $positionCounts
                )
            ) {

                $positionCounts[
                    $position
                ]++;
            }
        }


        if (
            $positionCounts[
                'GK'
            ]
            !==
            2
            ||
            $positionCounts[
                'DEF'
            ]
            !==
            5
            ||
            $positionCounts[
                'MID'
            ]
            !==
            5
            ||
            $positionCounts[
                'FWD'
            ]
            !==
            3
        ) {

            return [
                'status' =>
                    'invalid',

                'message' =>
                    'Free Hit squad does not meet the required position structure.'
            ];
        }


        /*
         * --------------------------------------------------------
         * CLUB LIMIT VALIDATION
         * --------------------------------------------------------
         */

        $teamCounts = [];


        foreach (
            $squad
            as $player
        ) {

            $teamId =
                $player[
                    'team_id'
                ]
                ?? null;


            if (
                !is_numeric(
                    $teamId
                )
            ) {

                return [
                    'status' =>
                        'invalid',

                    'message' =>
                        'Free Hit squad contains an invalid team.'
                ];
            }


            $teamId =
                (int) $teamId;


            if (
                $teamId <= 0
            ) {

                return [
                    'status' =>
                        'invalid',

                    'message' =>
                        'Free Hit squad contains an invalid team.'
                ];
            }


            if (
                !isset(
                    $teamCounts[
                        $teamId
                    ]
                )
            ) {

                $teamCounts[
                    $teamId
                ] =
                    0;
            }


            $teamCounts[
                $teamId
            ]++;


            if (
                $teamCounts[
                    $teamId
                ]
                >
                3
            ) {

                return [
                    'status' =>
                        'invalid',

                    'message' =>
                        'Free Hit squad contains more than three players from one club.'
                ];
            }
        }


        /*
         * --------------------------------------------------------
         * BUDGET OPTIMIZATION
         * --------------------------------------------------------
         */

        $totalPrice =
            0.0;


        foreach (
            $squad
            as $player
        ) {

            $price =
                $player[
                    'price'
                ]
                ?? null;


            if (
                !is_numeric(
                    $price
                )
            ) {

                return [
                    'status' =>
                        'invalid',

                    'message' =>
                        'Free Hit squad contains an invalid player price.'
                ];
            }


            $totalPrice +=
                (float) $price;
        }


        if (
            $totalPrice
            >
            $budget
        ) {

            $affordableSquad =
                $this
                    ->findBestAffordableSquad(
                        $squad,
                        $playersByPosition,
                        $budget
                    );


            if (
                $affordableSquad
                !==
                null
            ) {

                $squad =
                    $affordableSquad;


                $totalPrice =
                    0.0;


                foreach (
                    $squad
                    as $player
                ) {

                    $totalPrice +=
                        (float) (
                            $player[
                                'price'
                            ]
                            ?? 0.0
                        );
                }
            }
        }


        /*
         * --------------------------------------------------------
         * FINAL BUDGET VALIDATION
         * --------------------------------------------------------
         */

        if (
            $totalPrice
            >
            $budget
        ) {

            return [
                'status' =>
                    'invalid',

                'message' =>
                    'Free Hit squad exceeds the available budget.'
            ];
        }


        /*
         * --------------------------------------------------------
         * CLUB-LIMIT IMPROVEMENT
         * --------------------------------------------------------
         *
         * The initial position-by-position selection may consume
         * three club slots before a substantially stronger player
         * from that club is considered in a later position.
         *
         * Allow coordinated replacements when they improve the
         * complete squad's projected points while preserving all
         * squad, club and budget rules.
         */

        $squad =
            $this
                ->improveClubLimitSelection(
                    $squad,
                    $playersByPosition,
                    $budget
                );


        /*
         * --------------------------------------------------------
         * SUCCESS
         * --------------------------------------------------------
         */

        return [
            'status' =>
                'success',

            'squad' =>
                $squad
        ];
    }
    
    
    /*
     * ============================================================
     * CLUB-LIMIT IMPROVEMENT
     * ============================================================
     */

    private function improveClubLimitSelection(
        array $squad,
        array $playersByPosition,
        float $budget
    ): array {

        /*
         * Continue until a complete pass finds no
         * projected-points improvement.
         */
        while (
            true
        ) {

            $selectedPlayerIds = [];

            $teamCounts = [];

            $currentPrice =
                0.0;

            $currentProjectedPoints =
                0.0;


            foreach (
                $squad
                as $player
            ) {

                $playerId =
                    $player[
                        'player_id'
                    ]
                    ?? null;


                $teamId =
                    $player[
                        'team_id'
                    ]
                    ?? null;


                $price =
                    $player[
                        'price'
                    ]
                    ?? null;


                $projectedPoints =
                    $player[
                        'projected_points'
                    ]
                    ?? null;


                if (
                    !is_numeric(
                        $playerId
                    )
                    ||
                    !is_numeric(
                        $teamId
                    )
                    ||
                    !is_numeric(
                        $price
                    )
                    ||
                    !is_numeric(
                        $projectedPoints
                    )
                ) {

                    return
                        $squad;
                }


                $playerId =
                    (int) $playerId;

                $teamId =
                    (int) $teamId;


                $selectedPlayerIds[
                    $playerId
                ] =
                    true;


                $teamCounts[
                    $teamId
                ] =
                    (
                        $teamCounts[
                            $teamId
                        ]
                        ?? 0
                    )
                    +
                    1;


                $currentPrice +=
                    (float) $price;


                $currentProjectedPoints +=
                    (float) $projectedPoints;
            }


            $bestSquad =
                null;

            $bestProjectedPoints =
                $currentProjectedPoints;


            /*
             * ----------------------------------------------------
             * DIRECT AND COORDINATED UPGRADES
             * ----------------------------------------------------
             */

            foreach (
                $playersByPosition
                as $position =>
                    $positionPlayers
            ) {

                foreach (
                    $positionPlayers
                    as $incomingPlayer
                ) {

                    $incomingPlayerId =
                        $incomingPlayer[
                            'player_id'
                        ]
                        ?? null;


                    $incomingTeamId =
                        $incomingPlayer[
                            'team_id'
                        ]
                        ?? null;


                    $incomingPrice =
                        $incomingPlayer[
                            'price'
                        ]
                        ?? null;


                    $incomingProjectedPoints =
                        $incomingPlayer[
                            'projected_points'
                        ]
                        ?? null;


                    if (
                        !is_numeric(
                            $incomingPlayerId
                        )
                        ||
                        !is_numeric(
                            $incomingTeamId
                        )
                        ||
                        !is_numeric(
                            $incomingPrice
                        )
                        ||
                        !is_numeric(
                            $incomingProjectedPoints
                        )
                    ) {

                        continue;
                    }


                    $incomingPlayerId =
                        (int) $incomingPlayerId;

                    $incomingTeamId =
                        (int) $incomingTeamId;

                    $incomingPrice =
                        (float) $incomingPrice;

                    $incomingProjectedPoints =
                        (float) $incomingProjectedPoints;


                    if (
                        isset(
                            $selectedPlayerIds[
                                $incomingPlayerId
                            ]
                        )
                    ) {

                        continue;
                    }


                    /*
                     * The incoming player must replace somebody
                     * from the same position.
                     */
                    foreach (
                        $squad
                        as $outgoingIndex =>
                            $outgoingPlayer
                    ) {

                        $outgoingPosition =
                            strtoupper(
                                trim(
                                    (string) (
                                        $outgoingPlayer[
                                            'position'
                                        ]
                                        ?? ''
                                    )
                                )
                            );


                        if (
                            $outgoingPosition
                            !==
                            $position
                        ) {

                            continue;
                        }


                        $outgoingTeamId =
                            $outgoingPlayer[
                                'team_id'
                            ]
                            ?? null;


                        $outgoingPrice =
                            $outgoingPlayer[
                                'price'
                            ]
                            ?? null;


                        $outgoingProjectedPoints =
                            $outgoingPlayer[
                                'projected_points'
                            ]
                            ?? null;


                        if (
                            !is_numeric(
                                $outgoingTeamId
                            )
                            ||
                            !is_numeric(
                                $outgoingPrice
                            )
                            ||
                            !is_numeric(
                                $outgoingProjectedPoints
                            )
                        ) {

                            continue;
                        }


                        $outgoingTeamId =
                            (int) $outgoingTeamId;

                        $outgoingPrice =
                            (float) $outgoingPrice;

                        $outgoingProjectedPoints =
                            (float) $outgoingProjectedPoints;


                        /*
                         * There is no reason to perform this
                         * replacement unless the incoming player
                         * improves this position.
                         */
                        if (
                            $incomingProjectedPoints
                            <=
                            $outgoingProjectedPoints
                        ) {

                            continue;
                        }


                        $firstSwapPrice =
                            $currentPrice
                            -
                            $outgoingPrice
                            +
                            $incomingPrice;


                        $firstSwapProjectedPoints =
                            $currentProjectedPoints
                            -
                            $outgoingProjectedPoints
                            +
                            $incomingProjectedPoints;


                        $firstSwapTeamCounts =
                            $teamCounts;


                        $firstSwapTeamCounts[
                            $outgoingTeamId
                        ]--;


                        $firstSwapTeamCounts[
                            $incomingTeamId
                        ] =
                            (
                                $firstSwapTeamCounts[
                                    $incomingTeamId
                                ]
                                ?? 0
                            )
                            +
                            1;


                        /*
                         * ------------------------------------------------
                         * DIRECT LEGAL UPGRADE
                         * ------------------------------------------------
                         */

                        if (
                            $firstSwapTeamCounts[
                                $incomingTeamId
                            ]
                            <=
                            3
                            &&
                            $firstSwapPrice
                            <=
                            $budget
                        ) {

                            if (
                                $firstSwapProjectedPoints
                                >
                                $bestProjectedPoints
                            ) {

                                $candidateSquad =
                                    $squad;


                                $candidateSquad[
                                    $outgoingIndex
                                ] =
                                    $incomingPlayer;


                                $bestSquad =
                                    $candidateSquad;


                                $bestProjectedPoints =
                                    $firstSwapProjectedPoints;
                            }


                            continue;
                        }


                        /*
                         * If budget is the only problem, leave it
                         * to the dedicated budget optimizer.
                         *
                         * This section specifically solves the
                         * case where the incoming player's club
                         * already has three selected players.
                         */
                        if (
                            $firstSwapTeamCounts[
                                $incomingTeamId
                            ]
                            <=
                            3
                        ) {

                            continue;
                        }


                        /*
                         * ------------------------------------------------
                         * COORDINATED CLUB-SLOT RELEASE
                         * ------------------------------------------------
                         *
                         * The desired incoming player would become
                         * the fourth player from their club.
                         *
                         * Find one existing player from that club
                         * who can be replaced by an unselected player
                         * from the same position.
                         */
                        foreach (
                            $squad
                            as $clubPlayerIndex =>
                                $clubPlayer
                        ) {

                            if (
                                $clubPlayerIndex
                                ===
                                $outgoingIndex
                            ) {

                                continue;
                            }


                            $clubPlayerTeamId =
                                $clubPlayer[
                                    'team_id'
                                ]
                                ?? null;


                            if (
                                !is_numeric(
                                    $clubPlayerTeamId
                                )
                                ||
                                (int) $clubPlayerTeamId
                                !==
                                $incomingTeamId
                            ) {

                                continue;
                            }


                            $clubPlayerPosition =
                                strtoupper(
                                    trim(
                                        (string) (
                                            $clubPlayer[
                                                'position'
                                            ]
                                            ?? ''
                                        )
                                    )
                                );


                            $clubPlayerPrice =
                                $clubPlayer[
                                    'price'
                                ]
                                ?? null;


                            $clubPlayerProjectedPoints =
                                $clubPlayer[
                                    'projected_points'
                                ]
                                ?? null;


                            if (
                                !isset(
                                    $playersByPosition[
                                        $clubPlayerPosition
                                    ]
                                )
                                ||
                                !is_numeric(
                                    $clubPlayerPrice
                                )
                                ||
                                !is_numeric(
                                    $clubPlayerProjectedPoints
                                )
                            ) {

                                continue;
                            }


                            $clubPlayerPrice =
                                (float) $clubPlayerPrice;

                            $clubPlayerProjectedPoints =
                                (float) $clubPlayerProjectedPoints;


                            foreach (
                                $playersByPosition[
                                    $clubPlayerPosition
                                ]
                                as $replacementPlayer
                            ) {

                                $replacementPlayerId =
                                    $replacementPlayer[
                                        'player_id'
                                    ]
                                    ?? null;


                                $replacementTeamId =
                                    $replacementPlayer[
                                        'team_id'
                                    ]
                                    ?? null;


                                $replacementPrice =
                                    $replacementPlayer[
                                        'price'
                                    ]
                                    ?? null;


                                $replacementProjectedPoints =
                                    $replacementPlayer[
                                        'projected_points'
                                    ]
                                    ?? null;


                                if (
                                    !is_numeric(
                                        $replacementPlayerId
                                    )
                                    ||
                                    !is_numeric(
                                        $replacementTeamId
                                    )
                                    ||
                                    !is_numeric(
                                        $replacementPrice
                                    )
                                    ||
                                    !is_numeric(
                                        $replacementProjectedPoints
                                    )
                                ) {

                                    continue;
                                }


                                $replacementPlayerId =
                                    (int) $replacementPlayerId;

                                $replacementTeamId =
                                    (int) $replacementTeamId;

                                $replacementPrice =
                                    (float) $replacementPrice;

                                $replacementProjectedPoints =
                                    (float) $replacementProjectedPoints;


                                /*
                                 * The second incoming player must
                                 * not already be selected and must
                                 * not be the first incoming player.
                                 */
                                if (
                                    isset(
                                        $selectedPlayerIds[
                                            $replacementPlayerId
                                        ]
                                    )
                                    ||
                                    $replacementPlayerId
                                    ===
                                    $incomingPlayerId
                                ) {

                                    continue;
                                }


                                /*
                                 * Replacing one club member only
                                 * helps if the replacement belongs
                                 * to a different club.
                                 */
                                if (
                                    $replacementTeamId
                                    ===
                                    $incomingTeamId
                                ) {

                                    continue;
                                }


                                $finalTeamCounts =
                                    $firstSwapTeamCounts;


                                $finalTeamCounts[
                                    $incomingTeamId
                                ]--;


                                $finalTeamCounts[
                                    $replacementTeamId
                                ] =
                                    (
                                        $finalTeamCounts[
                                            $replacementTeamId
                                        ]
                                        ?? 0
                                    )
                                    +
                                    1;


                                if (
                                    $finalTeamCounts[
                                        $replacementTeamId
                                    ]
                                    >
                                    3
                                ) {

                                    continue;
                                }


                                $finalPrice =
                                    $firstSwapPrice
                                    -
                                    $clubPlayerPrice
                                    +
                                    $replacementPrice;


                                if (
                                    $finalPrice
                                    >
                                    $budget
                                ) {

                                    continue;
                                }


                                $finalProjectedPoints =
                                    $firstSwapProjectedPoints
                                    -
                                    $clubPlayerProjectedPoints
                                    +
                                    $replacementProjectedPoints;


                                if (
                                    $finalProjectedPoints
                                    <=
                                    $bestProjectedPoints
                                ) {

                                    continue;
                                }


                                $candidateSquad =
                                    $squad;


                                $candidateSquad[
                                    $outgoingIndex
                                ] =
                                    $incomingPlayer;


                                $candidateSquad[
                                    $clubPlayerIndex
                                ] =
                                    $replacementPlayer;


                                $bestSquad =
                                    $candidateSquad;


                                $bestProjectedPoints =
                                    $finalProjectedPoints;
                            }
                        }
                    }
                }
            }


            /*
             * No legal improvement was found.
             */
            if (
                $bestSquad === null
            ) {

                return
                    $squad;
            }


            /*
             * Apply the best complete improvement found,
             * then rebuild counts and search again.
             */
            $squad =
                $bestSquad;
        }
    }


    /*
     * ============================================================
     * STARTING XI PROJECTED POINTS
     * ============================================================
     *
     * A Free Hit should primarily maximise the projected points
     * of the best legal Starting XI.
     *
     * Bench players still matter for squad legality and budget,
     * but their projected points must not outweigh improvements
     * to players who would actually start.
     */

    private function calculateStartingXIProjectedPoints(
        array $squad
    ): ?float {

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
            $squad
            as $player
        ) {

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


            $projectedPoints =
                $player[
                    'projected_points'
                ]
                ?? null;


            if (
                !isset(
                    $playersByPosition[
                        $position
                    ]
                )
                ||
                !is_numeric(
                    $projectedPoints
                )
            ) {

                return
                    null;
            }


            $playersByPosition[
                $position
            ][] =
                (float) $projectedPoints;
        }


        /*
         * A legal FPL squad must contain:
         *
         * 2 GK
         * 5 DEF
         * 5 MID
         * 3 FWD
         */
        if (
            count(
                $playersByPosition[
                    'GK'
                ]
            )
            !==
            2
            ||
            count(
                $playersByPosition[
                    'DEF'
                ]
            )
            !==
            5
            ||
            count(
                $playersByPosition[
                    'MID'
                ]
            )
            !==
            5
            ||
            count(
                $playersByPosition[
                    'FWD'
                ]
            )
            !==
            3
        ) {

            return
                null;
        }


        /*
         * Highest projected players first so each formation
         * automatically receives the strongest available
         * players from every position.
         */
        foreach (
            $playersByPosition
            as &$positionPlayers
        ) {

            rsort(
                $positionPlayers,
                SORT_NUMERIC
            );
        }

        unset(
            $positionPlayers
        );


        /*
         * Legal FPL Starting XI formations.
         *
         * Every formation contains exactly:
         *
         * 1 goalkeeper
         * at least 3 defenders
         * at least 2 midfielders
         * at least 1 forward
         */
        $formations = [
            [
                'DEF' =>
                    3,

                'MID' =>
                    4,

                'FWD' =>
                    3
            ],

            [
                'DEF' =>
                    3,

                'MID' =>
                    5,

                'FWD' =>
                    2
            ],

            [
                'DEF' =>
                    4,

                'MID' =>
                    3,

                'FWD' =>
                    3
            ],

            [
                'DEF' =>
                    4,

                'MID' =>
                    4,

                'FWD' =>
                    2
            ],

            [
                'DEF' =>
                    4,

                'MID' =>
                    5,

                'FWD' =>
                    1
            ],

            [
                'DEF' =>
                    5,

                'MID' =>
                    2,

                'FWD' =>
                    3
            ],

            [
                'DEF' =>
                    5,

                'MID' =>
                    3,

                'FWD' =>
                    2
            ],

            [
                'DEF' =>
                    5,

                'MID' =>
                    4,

                'FWD' =>
                    1
            ]
        ];


        $bestProjectedPoints =
            null;


        foreach (
            $formations
            as $formation
        ) {

            /*
             * The highest projected goalkeeper starts.
             */
            $projectedPoints =
                $playersByPosition[
                    'GK'
                ][
                    0
                ];


            foreach (
                [
                    'DEF',
                    'MID',
                    'FWD'
                ]
                as $position
            ) {

                $requiredPlayers =
                    $formation[
                        $position
                    ];


                for (
                    $i = 0;
                    $i < $requiredPlayers;
                    $i++
                ) {

                    $projectedPoints +=
                        $playersByPosition[
                            $position
                        ][
                            $i
                        ];
                }
            }


            if (
                $bestProjectedPoints
                ===
                null
                ||
                $projectedPoints
                >
                $bestProjectedPoints
            ) {

                $bestProjectedPoints =
                    $projectedPoints;
            }
        }


        return
            $bestProjectedPoints;
    }


    

    /*
     * ============================================================
     * BUDGET SEARCH
     * ============================================================
     */

    private function findBestAffordableSquad(
        array $squad,
        array $playersByPosition,
        float $budget
    ): ?array {

        $bestSquad = null;

        $bestProjectedPoints =
            -INF;

        $visitedStates = [];


        $this->searchAffordableSquads(
            $squad,
            $playersByPosition,
            $budget,
            $bestSquad,
            $bestProjectedPoints,
            $visitedStates
        );


        return
            $bestSquad;
    }


    private function searchAffordableSquads(
        array $squad,
        array $playersByPosition,
        float $budget,
        ?array &$bestSquad,
        float &$bestProjectedPoints,
        array &$visitedStates
    ): void {

        $selectedPlayerIds = [];

        $teamCounts = [];

        $totalPrice =
            0.0;

        $totalProjectedPoints =
            0.0;


        foreach (
            $squad
            as $player
        ) {

            $playerId =
                $player[
                    'player_id'
                ]
                ?? null;


            $teamId =
                $player[
                    'team_id'
                ]
                ?? null;


            $price =
                $player[
                    'price'
                ]
                ?? null;


            $projectedPoints =
                $player[
                    'projected_points'
                ]
                ?? null;


            if (
                !is_numeric(
                    $playerId
                )
                ||
                !is_numeric(
                    $teamId
                )
                ||
                !is_numeric(
                    $price
                )
                ||
                !is_numeric(
                    $projectedPoints
                )
            ) {

                return;
            }


            $playerId =
                (int) $playerId;

            $teamId =
                (int) $teamId;


            if (
                $playerId <= 0
                ||
                $teamId <= 0
                ||
                isset(
                    $selectedPlayerIds[
                        $playerId
                    ]
                )
            ) {

                return;
            }


            $selectedPlayerIds[
                $playerId
            ] =
                true;


            $teamCounts[
                $teamId
            ] =
                (
                    $teamCounts[
                        $teamId
                    ]
                    ?? 0
                )
                +
                1;


            if (
                $teamCounts[
                    $teamId
                ]
                >
                3
            ) {

                return;
            }


            $totalPrice +=
                (float) $price;


            $totalProjectedPoints +=
                (float) $projectedPoints;
        }


        /*
         * Create a stable state key so the same squad
         * is never explored more than once.
         */
        $statePlayerIds =
            array_keys(
                $selectedPlayerIds
            );


        sort(
            $statePlayerIds
        );


        $stateKey =
            implode(
                ':',
                $statePlayerIds
            );


        if (
            isset(
                $visitedStates[
                    $stateKey
                ]
            )
        ) {

            return;
        }


        $visitedStates[
            $stateKey
        ] =
            true;


        /*
         * Once an affordable legal squad is reached,
         * compare its complete projected-points total
         * with the best affordable squad found so far.
         */
        if (
            $totalPrice
            <=
            $budget
        ) {

            /*
             * Free Hit affordability is judged across the full
             * 15-player squad, but sporting value is judged by
             * the best legal Starting XI.
             */
            $startingXiProjectedPoints =
                $this
                    ->calculateStartingXIProjectedPoints(
                        $squad
                    );


            if (
                $startingXiProjectedPoints
                ===
                null
            ) {

                return;
            }


            if (
                $bestSquad
                ===
                null
                ||
                $startingXiProjectedPoints
                >
                $bestProjectedPoints
            ) {

                $bestSquad =
                    $squad;

                $bestProjectedPoints =
                    $startingXiProjectedPoints;
            }


            return;
        }


        /*
         * Explore every legal cheaper same-position
         * replacement from the current squad.
         *
         * This allows different downgrade paths to be
         * compared instead of committing to whichever
         * individual swap looks best first.
         */
        foreach (
            $squad
            as $squadIndex =>
                $selectedPlayer
        ) {

            $position =
                strtoupper(
                    trim(
                        (string) (
                            $selectedPlayer[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                );


            $selectedPrice =
                $selectedPlayer[
                    'price'
                ]
                ?? null;


            $selectedTeamId =
                $selectedPlayer[
                    'team_id'
                ]
                ?? null;


            if (
                !isset(
                    $playersByPosition[
                        $position
                    ]
                )
                ||
                !is_numeric(
                    $selectedPrice
                )
                ||
                !is_numeric(
                    $selectedTeamId
                )
            ) {

                continue;
            }


            $selectedPrice =
                (float) $selectedPrice;

            $selectedTeamId =
                (int) $selectedTeamId;


            foreach (
                $playersByPosition[
                    $position
                ]
                as $candidate
            ) {

                $candidatePlayerId =
                    $candidate[
                        'player_id'
                    ]
                    ?? null;


                $candidateTeamId =
                    $candidate[
                        'team_id'
                    ]
                    ?? null;


                $candidatePrice =
                    $candidate[
                        'price'
                    ]
                    ?? null;


                $candidateProjectedPoints =
                    $candidate[
                        'projected_points'
                    ]
                    ?? null;


                if (
                    !is_numeric(
                        $candidatePlayerId
                    )
                    ||
                    !is_numeric(
                        $candidateTeamId
                    )
                    ||
                    !is_numeric(
                        $candidatePrice
                    )
                    ||
                    !is_numeric(
                        $candidateProjectedPoints
                    )
                ) {

                    continue;
                }


                $candidatePlayerId =
                    (int) $candidatePlayerId;

                $candidateTeamId =
                    (int) $candidateTeamId;

                $candidatePrice =
                    (float) $candidatePrice;


                if (
                    $candidatePlayerId <= 0
                    ||
                    $candidateTeamId <= 0
                    ||
                    isset(
                        $selectedPlayerIds[
                            $candidatePlayerId
                        ]
                    )
                ) {

                    continue;
                }


                /*
                 * The search only moves toward cheaper
                 * squads, which guarantees that recursive
                 * paths cannot cycle indefinitely.
                 */
                if (
                    $candidatePrice
                    >=
                    $selectedPrice
                ) {

                    continue;
                }


                /*
                 * Check the three-player club limit after
                 * accounting for the player being removed.
                 */
                $candidateTeamCount =
                    $teamCounts[
                        $candidateTeamId
                    ]
                    ?? 0;


                if (
                    $candidateTeamId
                    ===
                    $selectedTeamId
                ) {

                    $candidateTeamCount--;
                }


                if (
                    $candidateTeamCount
                    >=
                    3
                ) {

                    continue;
                }


                $nextSquad =
                    $squad;


                $nextSquad[
                    $squadIndex
                ] =
                    $candidate;


                $this->searchAffordableSquads(
                    $nextSquad,
                    $playersByPosition,
                    $budget,
                    $bestSquad,
                    $bestProjectedPoints,
                    $visitedStates
                );
            }
        }
    }
}