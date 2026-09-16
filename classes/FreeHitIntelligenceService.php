<?php

class FreeHitIntelligenceService
{
    private PlayerIntelligenceService
        $playerIntelligenceService;


    private FreeHitOptimizer
        $freeHitOptimizer;


    public function __construct(
        PlayerIntelligenceService $playerIntelligenceService,
        FreeHitOptimizer $freeHitOptimizer
    ) {

        $this->playerIntelligenceService =
            $playerIntelligenceService;


        $this->freeHitOptimizer =
            $freeHitOptimizer;
    }


    /*
     * ============================================================
     * BUILD FREE HIT INTELLIGENCE
     * ============================================================
     *
     * Free Hit selection is deliberately based on the existing
     * Expected Points architecture.
     *
     * This service:
     *
     * - requests existing multi-gameweek Expected Points
     * - uses the earliest represented FPL gameweek
     * - preserves already-aggregated BGW / DGW semantics
     * - adapts that gameweek projection to projected_points
     * - passes the resulting candidate pool to FreeHitOptimizer
     *
     * It does NOT calculate Expected Points itself.
     */
    public function build(
        array $players,
        float $budget = 100.0,
        ?int $targetGameweek = null
    ): array {

        /*
         * --------------------------------------------------------
         * BUILD PROJECTED FREE HIT CANDIDATE POOL
         * --------------------------------------------------------
         */

        $projectionCandidates =
            [];


        $explicitTargetGameweek =
            $targetGameweek !== null;


        /*
         * --------------------------------------------------------
         * COLLECT CANDIDATE PROJECTION EVIDENCE
         * --------------------------------------------------------
         *
         * Individual players may expose different earliest
         * represented gameweeks while an FPL gameweek is partially
         * complete.
         *
         * We must first inspect the complete candidate population so
         * Free Hit optimization can use one common FPL gameweek.
         */
        foreach (
            $players
            as $player
        ) {

            /*
             * ----------------------------------------------------
             * VALIDATE LOCAL PLAYER ID
             * ----------------------------------------------------
             */

            $playerId =
                isset(
                    $player[
                        'player_id'
                    ]
                )
                &&
                is_numeric(
                    $player[
                        'player_id'
                    ]
                )
                    ? (int) $player[
                        'player_id'
                    ]
                    : 0;


            if (
                $playerId <= 0
            ) {

                continue;
            }


            /*
             * ----------------------------------------------------
             * REQUEST EXISTING EXPECTED POINTS
             * ----------------------------------------------------
             */

            $projection =
                $this->playerIntelligenceService
                    ->getPlayerMultiGameweekExpectedPoints(
                        $playerId,
                        6
                    );


            if (
                !is_array(
                    $projection
                )
            ) {

                continue;
            }


            $projectionGameweeks =
                isset(
                    $projection[
                        'gameweeks'
                    ]
                )
                &&
                is_array(
                    $projection[
                        'gameweeks'
                    ]
                )
                    ? $projection[
                        'gameweeks'
                    ]
                    : [];


            if (
                empty(
                    $projectionGameweeks
                )
            ) {

                continue;
            }


            /*
             * ----------------------------------------------------
             * NORMALISE USABLE GAMEWEEK PROJECTIONS
             * ----------------------------------------------------
             */

            $usableGameweeks =
                [];


            foreach (
                $projectionGameweeks
                as $gameweekKey => $gameweekProjection
            ) {

                if (
                    !is_array(
                        $gameweekProjection
                    )
                ) {

                    continue;
                }


                $gameweek =
                    isset(
                        $gameweekProjection[
                            'gameweek'
                        ]
                    )
                    &&
                    is_numeric(
                        $gameweekProjection[
                            'gameweek'
                        ]
                    )
                        ? (int) $gameweekProjection[
                            'gameweek'
                        ]
                        : (
                            is_numeric(
                                $gameweekKey
                            )
                                ? (int) $gameweekKey
                                : 0
                        );


                if (
                    $gameweek <= 0
                ) {

                    continue;
                }


                if (
                    !isset(
                        $gameweekProjection[
                            'projected_points'
                        ]
                    )
                    ||
                    !is_numeric(
                        $gameweekProjection[
                            'projected_points'
                        ]
                    )
                ) {

                    continue;
                }


                $usableGameweeks[
                    $gameweek
                ] =
                    $gameweekProjection;


                if (
                    !$explicitTargetGameweek
                    &&
                    (
                        $targetGameweek === null
                        ||
                        $gameweek < $targetGameweek
                    )
                ) {

                    $targetGameweek =
                        $gameweek;
                }
            }


            if (
                empty(
                    $usableGameweeks
                )
            ) {

                continue;
            }


            $projectionCandidates[] = [

                'player' =>
                    $player,

                'gameweeks' =>
                    $usableGameweeks
            ];
        }


        /*
         * --------------------------------------------------------
         * BUILD COMMON-GAMEWEEK FREE HIT CANDIDATE POOL
         * --------------------------------------------------------
         *
         * Free Hit is a one-gameweek chip.
         *
         * Every optimizer candidate must therefore represent the same
         * FPL gameweek. A player without projection evidence for that
         * gameweek is omitted rather than substituted with a later
         * gameweek projection.
         */

        $projectedPlayers =
            [];


        if (
            $targetGameweek !== null
        ) {

            foreach (
                $projectionCandidates
                as $projectionCandidate
            ) {

                $gameweekProjection =
                    $projectionCandidate[
                        'gameweeks'
                    ][
                        $targetGameweek
                    ]
                    ?? null;


                if (
                    !is_array(
                        $gameweekProjection
                    )
                ) {

                    continue;
                }


                $projectedPoints =
                    $gameweekProjection[
                        'projected_points'
                    ]
                    ?? null;


                if (
                    !is_numeric(
                        $projectedPoints
                    )
                ) {

                    continue;
                }


                $projectedPlayer =
                    $projectionCandidate[
                        'player'
                    ];


                $projectedPlayer[
                    'projected_points'
                ] =
                    (float) $projectedPoints;


                $projectedPlayer[
                    'projection_gameweek'
                ] =
                    $targetGameweek;


                $projectedPlayer[
                    'projection_confidence'
                ] =
                    isset(
                        $gameweekProjection[
                            'projection_confidence'
                        ]
                    )
                    &&
                    is_numeric(
                        $gameweekProjection[
                            'projection_confidence'
                        ]
                    )
                        ? (float) $gameweekProjection[
                            'projection_confidence'
                        ]
                        : null;


                $projectedPlayers[] =
                    $projectedPlayer;
            }
        }
        
        /*
         * --------------------------------------------------------
         * REQUIRE USABLE PROJECTION EVIDENCE
         * --------------------------------------------------------
         *
         * If absolutely no candidate can be projected, there is no
         * meaningful optimization to perform.
         */

        if (
            empty(
                $projectedPlayers
            )
        ) {

            return [

                'status' =>
                    'Unavailable',

                'projected_player_count' =>
                    0,

                'optimizer_result' =>
                    null
            ];
        }


        /*
         * --------------------------------------------------------
         * OPTIMIZE ONE-GAMEWEEK FREE HIT SQUAD
         * --------------------------------------------------------
         */

        $optimizerResult =
            $this->freeHitOptimizer
                ->optimize(
                    $projectedPlayers,
                    $budget
                );


        /*
         * --------------------------------------------------------
         * OPTIMIZER FAILURE
         * --------------------------------------------------------
         */

        if (
            (
                $optimizerResult[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            return [

                'status' =>
                    'Unavailable',

                'projected_player_count' =>
                    count(
                        $projectedPlayers
                    ),

                'optimizer_result' =>
                    $optimizerResult
            ];
        }


        /*
         * --------------------------------------------------------
         * SUCCESS
         * --------------------------------------------------------
         */

        return [

            'status' =>
                'Available',

            'projected_player_count' =>
                count(
                    $projectedPlayers
                ),

            'optimizer_result' =>
                $optimizerResult
        ];
    }
}
