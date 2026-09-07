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
         * ============================================================
         * LARGE-POOL FREE HIT OPTIMIZATION
         * ============================================================
         *
         * Small controlled pools continue through the original
         * implementation below.
         *
         * Production-sized pools use a Starting-XI-first search which
         * avoids treating bench places as equal optimization decisions.
         *
         * The public Free Hit objective remains unchanged:
         *
         * maximise the strongest legal Starting XI projected points
         * while still constructing a legal 15-player squad within the
         * supplied budget.
         */

        $useScalableOptimizer =
            false;


        foreach (
            $playersByPosition
            as $positionPlayers
        ) {

            if (
                count(
                    $positionPlayers
                )
                >
                40
            ) {

                $useScalableOptimizer =
                    true;

                break;
            }
        }


        if (
            $useScalableOptimizer
        ) {

            return
                $this->optimizeLargeFreeHitPool(
                    $playersByPosition,
                    $budget
                );
        }


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

        $startingXiProjectedPoints =
            $this->calculateStartingXIProjectedPoints(
                $squad
            );


        return [

            'status' =>
                'success',

            'squad' =>
                $squad,

            'starting_xi_projected_points' =>
                $startingXiProjectedPoints
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
     *
     * Free Hit optimization is fundamentally a Starting XI problem.
     *
     * The complete squad must still contain:
     *
     * 2 GK
     * 5 DEF
     * 5 MID
     * 3 FWD
     *
     * and must obey:
     *
     * - the available budget;
     * - maximum three players per club;
     * - no duplicate players.
     *
     * However, only the strongest legal Starting XI contributes to
     * the Free Hit optimization objective.
     *
     * Searching every possible fifteen-player squad from the full
     * FPL player pool is combinatorial and does not scale to the
     * production-sized player universe.
     *
     * Instead, evaluate each legal Starting XI formation separately.
     *
     * For each formation:
     *
     * 1. build a controlled but strategically diverse search pool;
     * 2. select the eleven Starting XI slots;
     * 3. select the four required bench slots;
     * 4. retain only the strongest bounded set of partial states;
     * 5. compare completed legal squads using the existing
     *    calculateStartingXIProjectedPoints() objective.
     *
     * Search-pool construction deliberately retains:
     *
     * - strongest projected players;
     * - cheapest budget enablers;
     * - strongest points-per-price value players;
     * - club-diverse alternatives.
     *
     * This avoids arbitrary "top N projected points only" pruning,
     * which would incorrectly remove cheap bench players and useful
     * club-limit alternatives.
     */


    /*
     * ============================================================
     * FIND BEST AFFORDABLE SQUAD
     * ============================================================
     */

    private function findBestAffordableSquad(
        array $squad,
        array $playersByPosition,
        float $budget
    ): ?array {

        /*
         * The incoming $squad remains part of the method contract.
         *
         * It represents the strongest initial positional selection
         * produced by optimize().
         *
         * The bounded search below searches from the relevant player
         * universe rather than recursively downgrading that squad.
         */
        unset(
            $squad
        );


        $searchPools =
            [];


        foreach (
            [
                'GK',
                'DEF',
                'MID',
                'FWD'
            ]
            as $position
        ) {

            $searchPools[
                $position
            ] =
                $this->buildFreeHitSearchPool(
                    $playersByPosition[
                        $position
                    ]
                    ??
                    []
                );
        }


        /*
         * Every legal FPL Starting XI formation.
         */
        $formations = [

            [
                'GK' => 1,
                'DEF' => 3,
                'MID' => 4,
                'FWD' => 3
            ],

            [
                'GK' => 1,
                'DEF' => 3,
                'MID' => 5,
                'FWD' => 2
            ],

            [
                'GK' => 1,
                'DEF' => 4,
                'MID' => 3,
                'FWD' => 3
            ],

            [
                'GK' => 1,
                'DEF' => 4,
                'MID' => 4,
                'FWD' => 2
            ],

            [
                'GK' => 1,
                'DEF' => 4,
                'MID' => 5,
                'FWD' => 1
            ],

            [
                'GK' => 1,
                'DEF' => 5,
                'MID' => 2,
                'FWD' => 3
            ],

            [
                'GK' => 1,
                'DEF' => 5,
                'MID' => 3,
                'FWD' => 2
            ],

            [
                'GK' => 1,
                'DEF' => 5,
                'MID' => 4,
                'FWD' => 1
            ]
        ];


        $bestSquad =
            null;


        $bestStartingXiProjectedPoints =
            -INF;


        foreach (
            $formations
            as $formation
        ) {

            $formationSquad =
                $this->searchFreeHitFormation(
                    $searchPools,
                    $formation,
                    $budget
                );


            if (
                $formationSquad
                ===
                null
            ) {

                continue;
            }


            $startingXiProjectedPoints =
                $this->calculateStartingXIProjectedPoints(
                    $formationSquad
                );


            if (
                $startingXiProjectedPoints
                ===
                null
            ) {

                continue;
            }


            if (
                $bestSquad
                ===
                null
                ||
                $startingXiProjectedPoints
                >
                $bestStartingXiProjectedPoints
            ) {

                $bestSquad =
                    $formationSquad;


                $bestStartingXiProjectedPoints =
                    $startingXiProjectedPoints;
            }
        }


        return
            $bestSquad;
    }


    /*
     * ============================================================
     * BUILD FREE HIT SEARCH POOL
     * ============================================================
     *
     * A production FPL position may contain hundreds of players.
     *
     * Retaining every player makes complete-squad search
     * combinatorial.
     *
     * The search pool therefore preserves four useful candidate
     * families:
     *
     * 1. strongest projected players;
     * 2. cheapest players;
     * 3. strongest points-per-price players;
     * 4. strongest players from each individual club.
     *
     * Small controlled test pools remain completely intact.
     */

    private function buildFreeHitSearchPool(
        array $players
    ): array {

        /*
         * Small pools are cheap to search and should remain exact.
         *
         * This is also important for the controlled contract tests:
         * no candidate is discarded from those deliberately small
         * scenarios.
         */
        if (
            count(
                $players
            )
            <=
            40
        ) {

            return
                array_values(
                    $players
                );
        }


        $retained =
            [];


        /*
         * --------------------------------------------------------
         * STRONGEST PROJECTED PLAYERS
         * --------------------------------------------------------
         */

        $byProjectedPoints =
            $players;


        usort(
            $byProjectedPoints,
            static function (
                array $a,
                array $b
            ): int {

                $pointsA =
                    is_numeric(
                        $a[
                            'projected_points'
                        ]
                        ??
                        null
                    )
                        ? (float) $a[
                            'projected_points'
                        ]
                        : -INF;


                $pointsB =
                    is_numeric(
                        $b[
                            'projected_points'
                        ]
                        ??
                        null
                    )
                        ? (float) $b[
                            'projected_points'
                        ]
                        : -INF;


                if (
                    $pointsA
                    !==
                    $pointsB
                ) {

                    return
                        $pointsB
                        <=>
                        $pointsA;
                }


                $playerIdA =
                    is_numeric(
                        $a[
                            'player_id'
                        ]
                        ??
                        null
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
                        ??
                        null
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


        foreach (
            array_slice(
                $byProjectedPoints,
                0,
                24
            )
            as $player
        ) {

            $this->retainFreeHitSearchPlayer(
                $retained,
                $player
            );
        }


        /*
         * --------------------------------------------------------
         * CHEAPEST PLAYERS
         * --------------------------------------------------------
         *
         * These are particularly important for bench slots.
         */

        $byPrice =
            $players;


        usort(
            $byPrice,
            static function (
                array $a,
                array $b
            ): int {

                $priceA =
                    is_numeric(
                        $a[
                            'price'
                        ]
                        ??
                        null
                    )
                        ? (float) $a[
                            'price'
                        ]
                        : INF;


                $priceB =
                    is_numeric(
                        $b[
                            'price'
                        ]
                        ??
                        null
                    )
                        ? (float) $b[
                            'price'
                        ]
                        : INF;


                if (
                    $priceA
                    !==
                    $priceB
                ) {

                    return
                        $priceA
                        <=>
                        $priceB;
                }


                $pointsA =
                    is_numeric(
                        $a[
                            'projected_points'
                        ]
                        ??
                        null
                    )
                        ? (float) $a[
                            'projected_points'
                        ]
                        : -INF;


                $pointsB =
                    is_numeric(
                        $b[
                            'projected_points'
                        ]
                        ??
                        null
                    )
                        ? (float) $b[
                            'projected_points'
                        ]
                        : -INF;


                return
                    $pointsB
                    <=>
                    $pointsA;
            }
        );


        foreach (
            array_slice(
                $byPrice,
                0,
                12
            )
            as $player
        ) {

            $this->retainFreeHitSearchPlayer(
                $retained,
                $player
            );
        }


        /*
         * --------------------------------------------------------
         * POINTS-PER-PRICE VALUE
         * --------------------------------------------------------
         */

        $byValue =
            $players;


        usort(
            $byValue,
            static function (
                array $a,
                array $b
            ): int {

                $priceA =
                    is_numeric(
                        $a[
                            'price'
                        ]
                        ??
                        null
                    )
                        ? (float) $a[
                            'price'
                        ]
                        : 0.0;


                $priceB =
                    is_numeric(
                        $b[
                            'price'
                        ]
                        ??
                        null
                    )
                        ? (float) $b[
                            'price'
                        ]
                        : 0.0;


                $pointsA =
                    is_numeric(
                        $a[
                            'projected_points'
                        ]
                        ??
                        null
                    )
                        ? (float) $a[
                            'projected_points'
                        ]
                        : 0.0;


                $pointsB =
                    is_numeric(
                        $b[
                            'projected_points'
                        ]
                        ??
                        null
                    )
                        ? (float) $b[
                            'projected_points'
                        ]
                        : 0.0;


                $valueA =
                    $priceA > 0
                        ? $pointsA / $priceA
                        : -INF;


                $valueB =
                    $priceB > 0
                        ? $pointsB / $priceB
                        : -INF;


                if (
                    $valueA
                    !==
                    $valueB
                ) {

                    return
                        $valueB
                        <=>
                        $valueA;
                }


                return
                    $pointsB
                    <=>
                    $pointsA;
            }
        );


        foreach (
            array_slice(
                $byValue,
                0,
                16
            )
            as $player
        ) {

            $this->retainFreeHitSearchPlayer(
                $retained,
                $player
            );
        }


        /*
         * --------------------------------------------------------
         * CLUB-DIVERSE ALTERNATIVES
         * --------------------------------------------------------
         *
         * Keep the two strongest candidates from every club so the
         * search can escape three-player club-limit conflicts.
         */

        $byClub =
            [];


        foreach (
            $byProjectedPoints
            as $player
        ) {

            $teamId =
                $player[
                    'team_id'
                ]
                ??
                null;


            if (
                !is_numeric(
                    $teamId
                )
                ||
                (int) $teamId <= 0
            ) {

                continue;
            }


            $teamId =
                (int) $teamId;


            if (
                !isset(
                    $byClub[
                        $teamId
                    ]
                )
            ) {

                $byClub[
                    $teamId
                ] =
                    [];
            }


            if (
                count(
                    $byClub[
                        $teamId
                    ]
                )
                >=
                2
            ) {

                continue;
            }


            $byClub[
                $teamId
            ][] =
                $player;
        }


        foreach (
            $byClub
            as $clubPlayers
        ) {

            foreach (
                $clubPlayers
                as $player
            ) {

                $this->retainFreeHitSearchPlayer(
                    $retained,
                    $player
                );
            }
        }


        return
            array_values(
                $retained
            );
    }


    /*
     * ============================================================
     * RETAIN SEARCH PLAYER
     * ============================================================
     */

    private function retainFreeHitSearchPlayer(
        array &$retained,
        array $player
    ): void {

        $playerId =
            $player[
                'player_id'
            ]
            ??
            null;


        if (
            !is_numeric(
                $playerId
            )
            ||
            (int) $playerId <= 0
        ) {

            return;
        }


        $retained[
            (int) $playerId
        ] =
            $player;
    }


    /*
     * ============================================================
     * SEARCH ONE LEGAL FORMATION
     * ============================================================
     */

    private function searchFreeHitFormation(
        array $searchPools,
        array $formation,
        float $budget
    ): ?array {

        /*
         * ============================================================
         * BUILD FORMATION SLOTS
         * ============================================================
         *
         * Starting XI slots are deliberately processed first because
         * Starting XI projected points are the Free Hit objective.
         */

        $slots =
            [];


        $slots[] = [
            'position' => 'GK',
            'starting' => true
        ];


        foreach (
            [
                'DEF',
                'MID',
                'FWD'
            ]
            as $position
        ) {

            $starterCount =
                (int) (
                    $formation[
                        $position
                    ]
                    ??
                    0
                );


            for (
                $i = 0;
                $i < $starterCount;
                $i++
            ) {

                $slots[] = [
                    'position' =>
                        $position,

                    'starting' =>
                        true
                ];
            }
        }


        /*
         * Complete the required 2 / 5 / 5 / 3 squad shape with
         * non-starting bench slots.
         */

        $requiredSquadCounts = [
            'GK' => 2,
            'DEF' => 5,
            'MID' => 5,
            'FWD' => 3
        ];


        foreach (
            $requiredSquadCounts
            as $position =>
                $requiredCount
        ) {

            $starterCount =
                $position === 'GK'
                    ? 1
                    : (int) (
                        $formation[
                            $position
                        ]
                        ??
                        0
                    );


            $benchCount =
                $requiredCount
                -
                $starterCount;


            for (
                $i = 0;
                $i < $benchCount;
                $i++
            ) {

                $slots[] = [
                    'position' =>
                        $position,

                    'starting' =>
                        false
                ];
            }
        }


        /*
         * ============================================================
         * PRICE-SORTED FEASIBILITY POOLS
         * ============================================================
         *
         * These are used only by the optimistic minimum-completion
         * budget check.
         */

        $priceSortedSearchPools =
            $searchPools;


        foreach (
            $priceSortedSearchPools
            as $position =>
                &$positionPool
        ) {

            usort(
                $positionPool,
                static function (
                    array $a,
                    array $b
                ): int {

                    $priceA =
                        is_numeric(
                            $a[
                                'price'
                            ]
                            ??
                            null
                        )
                            ? (float) $a[
                                'price'
                            ]
                            : INF;


                    $priceB =
                        is_numeric(
                            $b[
                                'price'
                            ]
                            ??
                            null
                        )
                            ? (float) $b[
                                'price'
                            ]
                            : INF;


                    if (
                        $priceA
                        !==
                        $priceB
                    ) {

                        return
                            $priceA
                            <=>
                            $priceB;
                    }


                    $pointsA =
                        is_numeric(
                            $a[
                                'projected_points'
                            ]
                            ??
                            null
                        )
                            ? (float) $a[
                                'projected_points'
                            ]
                            : -INF;


                    $pointsB =
                        is_numeric(
                            $b[
                                'projected_points'
                            ]
                            ??
                            null
                        )
                            ? (float) $b[
                                'projected_points'
                            ]
                            : -INF;


                    if (
                        $pointsA
                        !==
                        $pointsB
                    ) {

                        return
                            $pointsB
                            <=>
                            $pointsA;
                    }


                    return
                        (
                            (int) (
                                $a[
                                    'player_id'
                                ]
                                ??
                                PHP_INT_MAX
                            )
                        )
                        <=>
                        (
                            (int) (
                                $b[
                                    'player_id'
                                ]
                                ??
                                PHP_INT_MAX
                            )
                        );
                }
            );
        }


        unset(
            $positionPool
        );


        /*
         * ============================================================
         * INITIAL STATE
         * ============================================================
         */

        $states = [
            [
                'players' =>
                    [],

                'player_ids' =>
                    [],

                'team_counts' =>
                    [],

                'price' =>
                    0.0,

                'starting_points' =>
                    0.0,

                /*
                 * Lightweight deterministic beam-search tie-break.
                 *
                 * This is NOT a Free Hit score and has no effect on
                 * final squad evaluation.
                 */
                'beam_key' =>
                    ''
            ]
        ];


        /*
         * Preserve the existing bounded-search width.
         */
        $beamWidth =
            400;


        /*
         * ============================================================
         * SLOT SEARCH
         * ============================================================
         *
         * Important performance rule:
         *
         * Do NOT copy a full squad state for every possible candidate.
         *
         * First create lightweight descriptors, rank them once, retain
         * the best descriptors, and only then materialise the surviving
         * full states.
         */

        foreach (
            $slots
            as $slotIndex =>
                $slot
        ) {

            $position =
                $slot[
                    'position'
                ];


            $isStarting =
                (bool) (
                    $slot[
                        'starting'
                    ]
                    ??
                    false
                );


            $remainingSlots =
                array_slice(
                    $slots,
                    $slotIndex + 1
                );


            $descriptors =
                [];


            foreach (
                $states
                as $stateIndex =>
                    $state
            ) {

                foreach (
                    $searchPools[
                        $position
                    ]
                    ??
                    []
                    as $candidateIndex =>
                        $candidate
                ) {

                    $playerId =
                        $candidate[
                            'player_id'
                        ]
                        ??
                        null;


                    $teamId =
                        $candidate[
                            'team_id'
                        ]
                        ??
                        null;


                    $price =
                        $candidate[
                            'price'
                        ]
                        ??
                        null;


                    $projectedPoints =
                        $candidate[
                            'projected_points'
                        ]
                        ??
                        null;


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

                        continue;
                    }


                    $playerId =
                        (int) $playerId;


                    $teamId =
                        (int) $teamId;


                    $price =
                        (float) $price;


                    $projectedPoints =
                        (float) $projectedPoints;


                    if (
                        $playerId <= 0
                        ||
                        $teamId <= 0
                        ||
                        isset(
                            $state[
                                'player_ids'
                            ][
                                $playerId
                            ]
                        )
                    ) {

                        continue;
                    }


                    $teamCount =
                        $state[
                            'team_counts'
                        ][
                            $teamId
                        ]
                        ??
                        0;


                    if (
                        $teamCount >= 3
                    ) {

                        continue;
                    }


                    $nextPrice =
                        (float) $state[
                            'price'
                        ]
                        +
                        $price;


                    if (
                        $nextPrice
                        >
                        $budget
                    ) {

                        continue;
                    }


                    $nextStartingPoints =
                        (float) $state[
                            'starting_points'
                        ];


                    if (
                        $isStarting
                    ) {

                        $nextStartingPoints +=
                            $projectedPoints;
                    }


                    /*
                     * We need a temporary lightweight view of the
                     * selected IDs for the feasibility calculation.
                     *
                     * Copying this small associative ID map is much
                     * cheaper than copying the entire squad state.
                     */

                    $nextPlayerIds =
                        $state[
                            'player_ids'
                        ];


                    $nextPlayerIds[
                        $playerId
                    ] =
                        true;


                    if (
                        !empty(
                            $remainingSlots
                        )
                    ) {

                        $minimumRemainingPrice =
                            $this->calculateMinimumRemainingFreeHitPrice(
                                [
                                    'player_ids' =>
                                        $nextPlayerIds
                                ],
                                $remainingSlots,
                                $priceSortedSearchPools
                            );


                        if (
                            $minimumRemainingPrice
                            ===
                            null
                            ||
                            (
                                $nextPrice
                                +
                                $minimumRemainingPrice
                            )
                            >
                            $budget
                        ) {

                            continue;
                        }
                    }


                    /*
                     * ------------------------------------------------
                     * LIGHTWEIGHT SEARCH DESCRIPTOR
                     * ------------------------------------------------
                     *
                     * No complete player list, no complete team-count
                     * map and no full state copy is stored here.
                     */

                    $descriptors[] = [
                        'state_index' =>
                            $stateIndex,

                        'candidate_index' =>
                            $candidateIndex,

                        'player_id' =>
                            $playerId,

                        'team_id' =>
                            $teamId,

                        'price' =>
                            $nextPrice,

                        'starting_points' =>
                            $nextStartingPoints,

                        /*
                         * Deterministic search-only key.
                         *
                         * str_pad keeps numeric IDs lexically ordered.
                         */
                        'beam_key' =>
                            (
                                $state[
                                    'beam_key'
                                ]
                                ??
                                ''
                            )
                            .
                            ':'
                            .
                            str_pad(
                                (string) $playerId,
                                10,
                                '0',
                                STR_PAD_LEFT
                            )
                    ];
                }
            }


            if (
                empty(
                    $descriptors
                )
            ) {

                return
                    null;
            }


            /*
             * ========================================================
             * ONE RANKING PASS PER SLOT
             * ========================================================
             *
             * Previous versions repeatedly sorted full PHP state
             * structures during expansion.
             *
             * This sorts lightweight descriptors exactly once.
             */

            usort(
                $descriptors,
                static function (
                    array $a,
                    array $b
                ): int {

                    $startingPointsA =
                        (float) $a[
                            'starting_points'
                        ];


                    $startingPointsB =
                        (float) $b[
                            'starting_points'
                        ];


                    if (
                        $startingPointsA
                        !==
                        $startingPointsB
                    ) {

                        return
                            $startingPointsB
                            <=>
                            $startingPointsA;
                    }


                    $priceA =
                        (float) $a[
                            'price'
                        ];


                    $priceB =
                        (float) $b[
                            'price'
                        ];


                    if (
                        $priceA
                        !==
                        $priceB
                    ) {

                        return
                            $priceA
                            <=>
                            $priceB;
                    }


                    return
                        strcmp(
                            (string) $a[
                                'beam_key'
                            ],
                            (string) $b[
                                'beam_key'
                            ]
                        );
                }
            );


            if (
                count(
                    $descriptors
                )
                >
                $beamWidth
            ) {

                $descriptors =
                    array_slice(
                        $descriptors,
                        0,
                        $beamWidth
                    );
            }


            /*
             * ========================================================
             * MATERIALISE SURVIVING STATES ONLY
             * ========================================================
             */

            $nextStates =
                [];


            foreach (
                $descriptors
                as $descriptor
            ) {

                $parentState =
                    $states[
                        $descriptor[
                            'state_index'
                        ]
                    ];


                $candidate =
                    $searchPools[
                        $position
                    ][
                        $descriptor[
                            'candidate_index'
                        ]
                    ];


                $playerId =
                    (int) $descriptor[
                        'player_id'
                    ];


                $teamId =
                    (int) $descriptor[
                        'team_id'
                    ];


                $nextState =
                    $parentState;


                $nextState[
                    'players'
                ][] =
                    $candidate;


                $nextState[
                    'player_ids'
                ][
                    $playerId
                ] =
                    true;


                $nextState[
                    'team_counts'
                ][
                    $teamId
                ] =
                    (
                        $nextState[
                            'team_counts'
                        ][
                            $teamId
                        ]
                        ??
                        0
                    )
                    +
                    1;


                $nextState[
                    'price'
                ] =
                    (float) $descriptor[
                        'price'
                    ];


                $nextState[
                    'starting_points'
                ] =
                    (float) $descriptor[
                        'starting_points'
                    ];


                $nextState[
                    'beam_key'
                ] =
                    (string) $descriptor[
                        'beam_key'
                    ];


                $nextStates[] =
                    $nextState;
            }


            $states =
                $nextStates;
        }


        /*
         * ============================================================
         * FINAL COMPLETE-SQUAD EVALUATION
         * ============================================================
         *
         * Beam ordering is only a search mechanism.
         *
         * Final comparison remains the existing authoritative Free Hit
         * objective: strongest legal Starting XI projected points.
         */

        $bestSquad =
            null;


        $bestProjectedPoints =
            -INF;


        foreach (
            $states
            as $state
        ) {

            $candidateSquad =
                $state[
                    'players'
                ]
                ??
                [];


            if (
                count(
                    $candidateSquad
                )
                !==
                15
            ) {

                continue;
            }


            $projectedPoints =
                $this->calculateStartingXIProjectedPoints(
                    $candidateSquad
                );


            if (
                $projectedPoints
                ===
                null
            ) {

                continue;
            }


            if (
                $bestSquad
                ===
                null
                ||
                $projectedPoints
                >
                $bestProjectedPoints
            ) {

                $bestSquad =
                    $candidateSquad;


                $bestProjectedPoints =
                    $projectedPoints;
            }
        }


        return
            $bestSquad;
    }
    


    /*
     * ============================================================
     * MINIMUM REMAINING FREE HIT PRICE
     * ============================================================
     *
     * Calculate an optimistic lower bound for completing the
     * remaining slots.
     *
     * Duplicate-player and club-limit interactions are deliberately
     * ignored here. That can only make the theoretical completion
     * cheaper, making the bound safe for rejecting states that are
     * already mathematically unable to fit inside the budget.
     */

    private function calculateMinimumRemainingFreeHitPrice(
        array $state,
        array $remainingSlots,
        array $priceSortedSearchPools
    ): ?float {

        /*
         * Count the number of remaining players required at each
         * position.
         */
        $requiredByPosition = [
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
            $remainingSlots
            as $slot
        ) {

            $position =
                $slot[
                    'position'
                ]
                ??
                null;


            if (
                !is_string(
                    $position
                )
                ||
                !array_key_exists(
                    $position,
                    $requiredByPosition
                )
            ) {

                return
                    null;
            }


            $requiredByPosition[
                $position
            ]++;
        }


        $minimumPrice =
            0.0;


        foreach (
            $requiredByPosition
            as $position =>
                $requiredCount
        ) {

            if (
                $requiredCount <= 0
            ) {

                continue;
            }


            if (
                !isset(
                    $priceSortedSearchPools[
                        $position
                    ]
                )
            ) {

                return
                    null;
            }


            /*
             * The pool is already sorted cheapest-first.
             *
             * We therefore only need to walk forward until we have
             * found the required number of distinct unused players.
             *
             * There is no need to build an intermediate price array
             * and no need to sort on every call.
             */
            $foundCount =
                0;


            foreach (
                $priceSortedSearchPools[
                    $position
                ]
                as $candidate
            ) {

                $playerId =
                    $candidate[
                        'player_id'
                    ]
                    ??
                    null;


                $price =
                    $candidate[
                        'price'
                    ]
                    ??
                    null;


                if (
                    !is_numeric(
                        $playerId
                    )
                    ||
                    !is_numeric(
                        $price
                    )
                ) {

                    continue;
                }


                $playerId =
                    (int) $playerId;


                if (
                    $playerId <= 0
                    ||
                    isset(
                        $state[
                            'player_ids'
                        ][
                            $playerId
                        ]
                    )
                ) {

                    continue;
                }


                $minimumPrice +=
                    (float) $price;


                $foundCount++;


                /*
                 * Because the pool is already price ordered, once the
                 * required number has been found we already know the
                 * mathematically cheapest distinct completion for this
                 * position.
                 */
                if (
                    $foundCount
                    >=
                    $requiredCount
                ) {

                    break;
                }
            }


            if (
                $foundCount
                <
                $requiredCount
            ) {

                return
                    null;
            }
        }


        return
            $minimumPrice;
    }
    
    
    /*
     * ============================================================
     * LARGE-POOL FREE HIT OPTIMIZER
     * ============================================================
     *
     * Production-sized Free Hit optimization is separated into two
     * fundamentally different decisions:
     *
     * 1. identify a strong legal Starting XI;
     * 2. prove that XI can be completed into a legal 15-player squad.
     *
     * Bench projected points do not participate in the objective.
     */

    private function optimizeLargeFreeHitPool(
        array $playersByPosition,
        float $budget
    ): array {

        /*
         * ========================================================
         * CONTROLLED CANDIDATE UNIVERSE
         * ========================================================
         */

        $searchPools =
            [];


        foreach (
            [
                'GK',
                'DEF',
                'MID',
                'FWD'
            ]
            as $position
        ) {

            $searchPools[
                $position
            ] =
                $this->buildFreeHitSearchPool(
                    $playersByPosition[
                        $position
                    ]
                    ??
                    []
                );


            if (
                empty(
                    $searchPools[
                        $position
                    ]
                )
            ) {

                return [
                    'status' =>
                        'invalid',

                    'message' =>
                        'Free Hit player pool cannot provide a legal squad.'
                ];
            }
            
            /*
             * Starting-XI search pools must have deterministic strongest-first
             * ordering.
             */

            usort(
                $searchPools[
                    $position
                ],
                static function (
                    array $a,
                    array $b
                ): int {

                    $pointsA =
                        is_numeric(
                            $a[
                                'projected_points'
                            ]
                            ??
                            null
                        )
                            ? (float) $a[
                                'projected_points'
                            ]
                            : -INF;


                    $pointsB =
                        is_numeric(
                            $b[
                                'projected_points'
                            ]
                            ??
                            null
                        )
                            ? (float) $b[
                                'projected_points'
                            ]
                            : -INF;


                    if (
                        $pointsA
                        !==
                        $pointsB
                    ) {

                        return
                            $pointsB
                            <=>
                            $pointsA;
                    }


                    $priceA =
                        is_numeric(
                            $a[
                                'price'
                            ]
                            ??
                            null
                        )
                            ? (float) $a[
                                'price'
                            ]
                            : INF;


                    $priceB =
                        is_numeric(
                            $b[
                                'price'
                            ]
                            ??
                            null
                        )
                            ? (float) $b[
                                'price'
                            ]
                            : INF;


                    if (
                        $priceA
                        !==
                        $priceB
                    ) {

                        return
                            $priceA
                            <=>
                            $priceB;
                    }


                    return
                        (
                            (int) (
                                $a[
                                    'player_id'
                                ]
                                ??
                                PHP_INT_MAX
                            )
                        )
                        <=>
                        (
                            (int) (
                                $b[
                                    'player_id'
                                ]
                                ??
                                PHP_INT_MAX
                            )
                        );
                }
            );
            
        }


        /*
         * Bench construction always wants cheap players first.
         */

        $benchPools =
            $searchPools;


        foreach (
            $benchPools
            as &$positionPlayers
        ) {

            usort(
                $positionPlayers,
                static function (
                    array $a,
                    array $b
                ): int {

                    $priceA =
                        (float) (
                            $a[
                                'price'
                            ]
                            ??
                            INF
                        );


                    $priceB =
                        (float) (
                            $b[
                                'price'
                            ]
                            ??
                            INF
                        );


                    if (
                        $priceA
                        !==
                        $priceB
                    ) {

                        return
                            $priceA
                            <=>
                            $priceB;
                    }


                    $pointsA =
                        (float) (
                            $a[
                                'projected_points'
                            ]
                            ??
                            -INF
                        );


                    $pointsB =
                        (float) (
                            $b[
                                'projected_points'
                            ]
                            ??
                            -INF
                        );


                    if (
                        $pointsA
                        !==
                        $pointsB
                    ) {

                        return
                            $pointsB
                            <=>
                            $pointsA;
                    }


                    return
                        (
                            (int) (
                                $a[
                                    'player_id'
                                ]
                                ??
                                PHP_INT_MAX
                            )
                        )
                        <=>
                        (
                            (int) (
                                $b[
                                    'player_id'
                                ]
                                ??
                                PHP_INT_MAX
                            )
                        );
                }
            );
        }


        unset(
            $positionPlayers
        );


        /*
         * Every legal FPL Starting XI formation.
         */

        $formations = [
            [
                'GK' => 1,
                'DEF' => 3,
                'MID' => 4,
                'FWD' => 3
            ],
            [
                'GK' => 1,
                'DEF' => 3,
                'MID' => 5,
                'FWD' => 2
            ],
            [
                'GK' => 1,
                'DEF' => 4,
                'MID' => 3,
                'FWD' => 3
            ],
            [
                'GK' => 1,
                'DEF' => 4,
                'MID' => 4,
                'FWD' => 2
            ],
            [
                'GK' => 1,
                'DEF' => 4,
                'MID' => 5,
                'FWD' => 1
            ],
            [
                'GK' => 1,
                'DEF' => 5,
                'MID' => 2,
                'FWD' => 3
            ],
            [
                'GK' => 1,
                'DEF' => 5,
                'MID' => 3,
                'FWD' => 2
            ],
            [
                'GK' => 1,
                'DEF' => 5,
                'MID' => 4,
                'FWD' => 1
            ]
        ];


        $bestSquad =
            null;


        $bestStartingXiProjectedPoints =
            -INF;


        foreach (
            $formations
            as $formation
        ) {

            $formationResult =
                $this->searchLargeFreeHitFormation(
                    $searchPools,
                    $benchPools,
                    $formation,
                    $budget
                );


            if (
                $formationResult
                ===
                null
            ) {

                continue;
            }


            $startingXiProjectedPoints =
                (float) $formationResult[
                    'starting_xi_projected_points'
                ];


            if (
                $bestSquad
                ===
                null
                ||
                $startingXiProjectedPoints
                >
                $bestStartingXiProjectedPoints
            ) {

                $bestSquad =
                    $formationResult[
                        'squad'
                    ];


                $bestStartingXiProjectedPoints =
                    $startingXiProjectedPoints;
            }
        }


        if (
            $bestSquad
            ===
            null
        ) {

            return [
                'status' =>
                    'invalid',

                'message' =>
                    'Free Hit optimizer could not construct a legal affordable squad.'
            ];
        }


        return [
            'status' =>
                'success',

            'squad' =>
                $bestSquad,

            'starting_xi_projected_points' =>
                $bestStartingXiProjectedPoints
        ];
    }


    /*
     * ============================================================
     * SEARCH LARGE-POOL STARTING XI
     * ============================================================
     */

    private function searchLargeFreeHitFormation(
        array $searchPools,
        array $benchPools,
        array $formation,
        float $budget
    ): ?array {

        /*
         * ============================================================
         * STARTING XI SLOT PLAN
         * ============================================================
         *
         * Only the eleven starting places are searched here.
         *
         * Repeated positions use a canonical next candidate index so:
         *
         * A + B + C
         *
         * is explored once rather than also exploring:
         *
         * B + A + C
         * C + B + A
         * etc.
         */

        $startingSlots =
            [
                'GK'
            ];


        foreach (
            [
                'DEF',
                'MID',
                'FWD'
            ]
            as $position
        ) {

            $requiredCount =
                (int) (
                    $formation[
                        $position
                    ]
                    ??
                    0
                );


            for (
                $i = 0;
                $i < $requiredCount;
                $i++
            ) {

                $startingSlots[] =
                    $position;
            }
        }


        if (
            count(
                $startingSlots
            )
            !==
            11
        ) {

            return
                null;
        }
        
        
        /*
         * ============================================================
         * PRECOMPUTE REMAINING STARTER PRICE BOUNDS
         * ============================================================
         *
         * The previous implementation calculated and sorted positional
         * prices inside every candidate expansion.
         *
         * Remaining starter requirements depend only on the slot index
         * and formation, not on the individual search state.
         *
         * Calculate each lower bound once per formation instead.
         */

        $remainingStarterPriceBounds =
            [];


        for (
            $slotIndex = 0;
            $slotIndex < count($startingSlots);
            $slotIndex++
        ) {

            $remainingStartingSlots =
                array_slice(
                    $startingSlots,
                    $slotIndex + 1
                );


            $minimumRemainingStarterPrice =
                $this->calculateLargeFreeHitMinimumRemainingStarterPrice(
                    $remainingStartingSlots,
                    $searchPools
                );


            if (
                $minimumRemainingStarterPrice
                ===
                null
            ) {

                return
                    null;
            }


            $remainingStarterPriceBounds[
                $slotIndex
            ] =
                $minimumRemainingStarterPrice;
        }


        /*
         * ============================================================
         * ABSOLUTE MINIMUM BENCH COST
         * ============================================================
         *
         * This intentionally ignores:
         *
         * - already selected player conflicts;
         * - club-limit conflicts.
         *
         * It is therefore optimistic and safe for pruning.
         *
         * Expensive partial XIs that cannot possibly leave enough
         * budget for even the cheapest theoretical bench are removed
         * immediately.
         */

        $absoluteMinimumBenchPrice =
            $this->calculateLargeFreeHitAbsoluteMinimumBenchPrice(
                $benchPools,
                $formation
            );


        if (
            $absoluteMinimumBenchPrice
            ===
            null
        ) {

            return
                null;
        }


        /*
         * ============================================================
         * INITIAL SEARCH STATE
         * ============================================================
         */

        $states = [
            [
                'players' =>
                    [],

                'player_ids' =>
                    [],

                'team_counts' =>
                    [],

                'price' =>
                    0.0,

                'projected_points' =>
                    0.0,

                /*
                 * For each position, remember the minimum candidate
                 * index that may be selected next.
                 *
                 * This is what removes positional permutations.
                 */
                'next_indices' => [
                    'GK' => 0,
                    'DEF' => 0,
                    'MID' => 0,
                    'FWD' => 0
                ],

                'key' =>
                    ''
            ]
        ];


        /*
         * Bounded production search.
         *
         * Unlike the previous implementation this bound is applied
         * after every single Starting XI selection rather than after
         * millions of complete positional combinations have already
         * been materialised.
         */

        $beamWidth =
            240;


        /*
         * ============================================================
         * STARTING XI SEARCH
         * ============================================================
         */

        foreach (
            $startingSlots
            as $slotIndex =>
                $position
        ) {

            $states =
                $this->expandLargeFreeHitStarterSlot(
                    $states,
                    $searchPools[
                        $position
                    ]
                    ??
                    [],
                    $position,
                    $budget,
                    $absoluteMinimumBenchPrice,
                    (float) $remainingStarterPriceBounds[
                        $slotIndex
                    ],
                    $beamWidth
                );


            if (
                empty(
                    $states
                )
            ) {

                return
                    null;
            }
        }


        /*
         * ============================================================
         * BENCH COMPLETION
         * ============================================================
         *
         * States are already strongest-first.
         *
         * Try each candidate XI until the first legal 15-player
         * completion is found.
         */

        foreach (
            $states
            as $state
        ) {

            $completedSquad =
                $this->completeLargeFreeHitBench(
                    $state,
                    $benchPools,
                    $formation,
                    $budget
                );


            if (
                $completedSquad
                ===
                null
            ) {

                continue;
            }


            return [
                'squad' =>
                    $completedSquad,

                'starting_xi_projected_points' =>
                    (float) $state[
                        'projected_points'
                    ]
            ];
        }


        return
            null;
    }


    /*
     * ============================================================
     * EXPAND ONE LARGE-POOL STARTER SLOT
     * ============================================================
     *
     * Search states are bounded after every individual starter.
     *
     * A canonical candidate index is carried for each position so
     * repeated positional selections represent combinations rather
     * than permutations.
     */

    private function expandLargeFreeHitStarterSlot(
        array $states,
        array $positionPlayers,
        string $position,
        float $budget,
        float $absoluteMinimumBenchPrice,
        float $minimumRemainingStarterPrice,
        int $beamWidth
    ): array {

        if (
            empty(
                $states
            )
            ||
            empty(
                $positionPlayers
            )
        ) {

            return
                [];
        }


        /*
         * ============================================================
         * LIGHTWEIGHT DESCRIPTORS
         * ============================================================
         *
         * Do not copy full squad states for every candidate.
         *
         * Only surviving beam entries are materialised below.
         */

        $descriptors =
            [];


        foreach (
            $states
            as $stateIndex =>
                $state
        ) {

            $minimumCandidateIndex =
                (int) (
                    $state[
                        'next_indices'
                    ][
                        $position
                    ]
                    ??
                    0
                );


            foreach (
                $positionPlayers
                as $candidateIndex =>
                    $player
            ) {

                /*
                 * Canonical combination ordering.
                 */
                if (
                    $candidateIndex
                    <
                    $minimumCandidateIndex
                ) {

                    continue;
                }


                $playerId =
                    $player[
                        'player_id'
                    ]
                    ??
                    null;


                $teamId =
                    $player[
                        'team_id'
                    ]
                    ??
                    null;


                $price =
                    $player[
                        'price'
                    ]
                    ??
                    null;


                $projectedPoints =
                    $player[
                        'projected_points'
                    ]
                    ??
                    null;


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

                    continue;
                }


                $playerId =
                    (int) $playerId;


                $teamId =
                    (int) $teamId;


                $price =
                    (float) $price;


                $projectedPoints =
                    (float) $projectedPoints;


                if (
                    $playerId <= 0
                    ||
                    $teamId <= 0
                    ||
                    isset(
                        $state[
                            'player_ids'
                        ][
                            $playerId
                        ]
                    )
                ) {

                    continue;
                }


                $currentTeamCount =
                    (int) (
                        $state[
                            'team_counts'
                        ][
                            $teamId
                        ]
                        ??
                        0
                    );


                if (
                    $currentTeamCount
                    >=
                    3
                ) {

                    continue;
                }


                $nextPrice =
                    (float) $state[
                        'price'
                    ]
                    +
                    $price;


                /*
                 * ============================================================
                 * OPTIMISTIC COMPLETE-SQUAD BUDGET BOUND
                 * ============================================================
                 *
                 * Reserve enough money for:
                 *
                 * - every Starting XI slot still to be selected;
                 * - the absolute cheapest theoretical bench.
                 *
                 * Duplicate-player and club-limit conflicts are deliberately
                 * ignored here, which means this remains an optimistic lower
                 * bound and is therefore safe for pruning.
                 */

                if (
                    (
                        $nextPrice
                        +
                        $minimumRemainingStarterPrice
                        +
                        $absoluteMinimumBenchPrice
                    )
                    >
                    $budget
                ) {

                    continue;
                }


                $nextProjectedPoints =
                    (float) $state[
                        'projected_points'
                    ]
                    +
                    $projectedPoints;


                $nextKey =
                    (
                        (string) (
                            $state[
                                'key'
                            ]
                            ??
                            ''
                        )
                    )
                    .
                    ':'
                    .
                    str_pad(
                        (string) $playerId,
                        10,
                        '0',
                        STR_PAD_LEFT
                    );


                $descriptors[] = [
                    'state_index' =>
                        $stateIndex,

                    'candidate_index' =>
                        $candidateIndex,

                    'player_id' =>
                        $playerId,

                    'team_id' =>
                        $teamId,

                    'price' =>
                        $nextPrice,

                    'projected_points' =>
                        $nextProjectedPoints,

                    'key' =>
                        $nextKey
                ];
            }
        }


        if (
            empty(
                $descriptors
            )
        ) {

            return
                [];
        }


        /*
         * ============================================================
         * BEAM RANKING
         * ============================================================
         *
         * Starting XI projected points remain the primary objective.
         *
         * Lower price wins equal-point ties because it preserves more
         * room for subsequent starters and the required bench.
         */

        usort(
            $descriptors,
            static function (
                array $a,
                array $b
            ): int {

                $pointsA =
                    (float) $a[
                        'projected_points'
                    ];


                $pointsB =
                    (float) $b[
                        'projected_points'
                    ];


                if (
                    $pointsA
                    !==
                    $pointsB
                ) {

                    return
                        $pointsB
                        <=>
                        $pointsA;
                }


                $priceA =
                    (float) $a[
                        'price'
                    ];


                $priceB =
                    (float) $b[
                        'price'
                    ];


                if (
                    $priceA
                    !==
                    $priceB
                ) {

                    return
                        $priceA
                        <=>
                        $priceB;
                }


                return
                    strcmp(
                        (string) $a[
                            'key'
                        ],
                        (string) $b[
                            'key'
                        ]
                    );
            }
        );


        if (
            count(
                $descriptors
            )
            >
            $beamWidth
        ) {

            $descriptors =
                array_slice(
                    $descriptors,
                    0,
                    $beamWidth
                );
        }


        /*
         * ============================================================
         * MATERIALISE SURVIVING STATES
         * ============================================================
         */

        $nextStates =
            [];


        foreach (
            $descriptors
            as $descriptor
        ) {

            $state =
                $states[
                    $descriptor[
                        'state_index'
                    ]
                ];


            $candidateIndex =
                (int) $descriptor[
                    'candidate_index'
                ];


            $player =
                $positionPlayers[
                    $candidateIndex
                ];


            $playerId =
                (int) $descriptor[
                    'player_id'
                ];


            $teamId =
                (int) $descriptor[
                    'team_id'
                ];


            $nextState =
                $state;


            $nextState[
                'players'
            ][] =
                $player;


            $nextState[
                'player_ids'
            ][
                $playerId
            ] =
                true;


            $nextState[
                'team_counts'
            ][
                $teamId
            ] =
                (
                    $nextState[
                        'team_counts'
                    ][
                        $teamId
                    ]
                    ??
                    0
                )
                +
                1;


            $nextState[
                'price'
            ] =
                (float) $descriptor[
                    'price'
                ];


            $nextState[
                'projected_points'
            ] =
                (float) $descriptor[
                    'projected_points'
                ];


            /*
             * Any later player from the same position must occur after
             * this candidate in the ordered search pool.
             */
            $nextState[
                'next_indices'
            ][
                $position
            ] =
                $candidateIndex
                +
                1;


            $nextState[
                'key'
            ] =
                (string) $descriptor[
                    'key'
                ];


            $nextStates[] =
                $nextState;
        }


        return
            $nextStates;
    }
    
    
    /*
     * ============================================================
     * MINIMUM REMAINING STARTER PRICE
     * ============================================================
     *
     * Returns an optimistic lower bound for all Starting XI slots
     * that remain to be selected.
     *
     * Search pools are strongest-first rather than price-first, so
     * determine the cheapest required players independently here.
     *
     * Duplicate and club constraints are intentionally ignored.
     * Therefore this can only underestimate the true completion
     * cost and is safe for pruning.
     */

    private function calculateLargeFreeHitMinimumRemainingStarterPrice(
        array $remainingStartingSlots,
        array $searchPools
    ): ?float {

        if (
            empty(
                $remainingStartingSlots
            )
        ) {

            return
                0.0;
        }


        /*
         * Convert the remaining slot sequence into positional counts.
         */
        $requiredCounts = [
            'GK' => 0,
            'DEF' => 0,
            'MID' => 0,
            'FWD' => 0
        ];


        foreach (
            $remainingStartingSlots
            as $position
        ) {

            if (
                !array_key_exists(
                    $position,
                    $requiredCounts
                )
            ) {

                return
                    null;
            }


            $requiredCounts[
                $position
            ]++;
        }


        $minimumPrice =
            0.0;


        foreach (
            $requiredCounts
            as $position =>
                $requiredCount
        ) {

            if (
                $requiredCount <= 0
            ) {

                continue;
            }


            $prices =
                [];


            foreach (
                $searchPools[
                    $position
                ]
                ??
                []
                as $player
            ) {

                $price =
                    $player[
                        'price'
                    ]
                    ??
                    null;


                if (
                    !is_numeric(
                        $price
                    )
                ) {

                    continue;
                }


                $prices[] =
                    (float) $price;
            }


            if (
                count(
                    $prices
                )
                <
                $requiredCount
            ) {

                return
                    null;
            }


            sort(
                $prices,
                SORT_NUMERIC
            );


            for (
                $i = 0;
                $i < $requiredCount;
                $i++
            ) {

                $minimumPrice +=
                    $prices[
                        $i
                    ];
            }
        }


        return
            $minimumPrice;
    }
    
    
    
    /*
     * ============================================================
     * ABSOLUTE MINIMUM LARGE-POOL BENCH PRICE
     * ============================================================
     *
     * This is deliberately an optimistic lower bound.
     *
     * It ignores:
     *
     * - starter/bench duplicate conflicts;
     * - club-limit conflicts.
     *
     * Therefore it can only underestimate the real bench cost,
     * making it safe to use for budget pruning.
     */

    private function calculateLargeFreeHitAbsoluteMinimumBenchPrice(
        array $benchPools,
        array $formation
    ): ?float {

        $requiredBench = [
            'GK' =>
                1,

            'DEF' =>
                5
                -
                (int) (
                    $formation[
                        'DEF'
                    ]
                    ??
                    0
                ),

            'MID' =>
                5
                -
                (int) (
                    $formation[
                        'MID'
                    ]
                    ??
                    0
                ),

            'FWD' =>
                3
                -
                (int) (
                    $formation[
                        'FWD'
                    ]
                    ??
                    0
                )
        ];


        $minimumPrice =
            0.0;


        foreach (
            $requiredBench
            as $position =>
                $requiredCount
        ) {

            if (
                $requiredCount <= 0
            ) {

                continue;
            }


            $positionPlayers =
                $benchPools[
                    $position
                ]
                ??
                [];


            if (
                count(
                    $positionPlayers
                )
                <
                $requiredCount
            ) {

                return
                    null;
            }


            for (
                $i = 0;
                $i < $requiredCount;
                $i++
            ) {

                $price =
                    $positionPlayers[
                        $i
                    ][
                        'price'
                    ]
                    ??
                    null;


                if (
                    !is_numeric(
                        $price
                    )
                ) {

                    return
                        null;
                }


                $minimumPrice +=
                    (float) $price;
            }
        }


        return
            $minimumPrice;
    }


    /*
     * ============================================================
     * MINIMUM REQUIRED BENCH PRICE
     * ============================================================
     */

    private function calculateLargeFreeHitMinimumBenchPrice(
        array $selectedPlayerIds,
        array $benchPools,
        array $formation
    ): ?float {

        $requiredBench = [
            'GK' =>
                1,

            'DEF' =>
                5
                -
                (int) $formation[
                    'DEF'
                ],

            'MID' =>
                5
                -
                (int) $formation[
                    'MID'
                ],

            'FWD' =>
                3
                -
                (int) $formation[
                    'FWD'
                ]
        ];


        $minimumPrice =
            0.0;


        foreach (
            $requiredBench
            as $position =>
                $requiredCount
        ) {

            if (
                $requiredCount <= 0
            ) {

                continue;
            }


            $found =
                0;


            foreach (
                $benchPools[
                    $position
                ]
                as $player
            ) {

                $playerId =
                    (int) (
                        $player[
                            'player_id'
                        ]
                        ??
                        0
                    );


                if (
                    $playerId <= 0
                    ||
                    isset(
                        $selectedPlayerIds[
                            $playerId
                        ]
                    )
                ) {

                    continue;
                }


                $minimumPrice +=
                    (float) $player[
                        'price'
                    ];


                $found++;


                if (
                    $found
                    >=
                    $requiredCount
                ) {

                    break;
                }
            }


            if (
                $found
                <
                $requiredCount
            ) {

                return
                    null;
            }
        }


        return
            $minimumPrice;
    }


    /*
     * ============================================================
     * COMPLETE LARGE-POOL BENCH
     * ============================================================
     */

    private function completeLargeFreeHitBench(
        array $startingState,
        array $benchPools,
        array $formation,
        float $budget
    ): ?array {

        $benchSlots =
            [];


        $requiredBench = [
            'GK' =>
                1,

            'DEF' =>
                5
                -
                (int) $formation[
                    'DEF'
                ],

            'MID' =>
                5
                -
                (int) $formation[
                    'MID'
                ],

            'FWD' =>
                3
                -
                (int) $formation[
                    'FWD'
                ]
        ];


        foreach (
            $requiredBench
            as $position =>
                $count
        ) {

            for (
                $i = 0;
                $i < $count;
                $i++
            ) {

                $benchSlots[] =
                    $position;
            }
        }


        $completion =
            $this->searchLargeFreeHitBenchSlots(
                $benchSlots,
                0,
                $startingState,
                $benchPools,
                $budget,
                []
            );


        if (
            $completion
            ===
            null
        ) {

            return
                null;
        }


        return
            $completion[
                'players'
            ];
    }


    /*
     * ============================================================
     * SEARCH FOUR BENCH PLACES
     * ============================================================
     *
     * Bench pools are price ordered and only four places remain.
     *
     * The search returns the first legal completion because projected
     * bench points are not part of the Free Hit objective.
     */

    private function searchLargeFreeHitBenchSlots(
        array $benchSlots,
        int $slotIndex,
        array $state,
        array $benchPools,
        float $budget,
        array $minimumIndexes
    ): ?array {

        if (
            $slotIndex
            >=
            count(
                $benchSlots
            )
        ) {

            if (
                count(
                    $state[
                        'players'
                    ]
                )
                !==
                15
            ) {

                return
                    null;
            }


            return
                $state;
        }


        $position =
            $benchSlots[
                $slotIndex
            ];


        $startIndex =
            $minimumIndexes[
                $position
            ]
            ??
            0;


        foreach (
            $benchPools[
                $position
            ]
            as $candidateIndex =>
                $player
        ) {

            /*
             * Repeated bench positions are combination choices rather
             * than permutations.
             */
            if (
                $candidateIndex
                <
                $startIndex
            ) {

                continue;
            }


            $playerId =
                $player[
                    'player_id'
                ]
                ??
                null;


            $teamId =
                $player[
                    'team_id'
                ]
                ??
                null;


            $price =
                $player[
                    'price'
                ]
                ??
                null;


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
            ) {

                continue;
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
                    $state[
                        'player_ids'
                    ][
                        $playerId
                    ]
                )
            ) {

                continue;
            }


            $teamCount =
                $state[
                    'team_counts'
                ][
                    $teamId
                ]
                ??
                0;


            if (
                $teamCount >= 3
            ) {

                continue;
            }


            $nextPrice =
                (float) $state[
                    'price'
                ]
                +
                (float) $price;


            if (
                $nextPrice
                >
                $budget
            ) {

                continue;
            }


            $nextState =
                $state;


            $nextState[
                'players'
            ][] =
                $player;


            $nextState[
                'player_ids'
            ][
                $playerId
            ] =
                true;


            $nextState[
                'team_counts'
            ][
                $teamId
            ] =
                $teamCount
                +
                1;


            $nextState[
                'price'
            ] =
                $nextPrice;


            $nextMinimumIndexes =
                $minimumIndexes;


            $nextMinimumIndexes[
                $position
            ] =
                $candidateIndex + 1;


            $result =
                $this->searchLargeFreeHitBenchSlots(
                    $benchSlots,
                    $slotIndex + 1,
                    $nextState,
                    $benchPools,
                    $budget,
                    $nextMinimumIndexes
                );


            if (
                $result
                !==
                null
            ) {

                return
                    $result;
            }
        }


        return
            null;
    }
    
    
}