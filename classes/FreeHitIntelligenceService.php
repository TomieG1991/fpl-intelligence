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
        float $budget = 100.0
    ): array {

        /*
         * --------------------------------------------------------
         * BUILD PROJECTED FREE HIT CANDIDATE POOL
         * --------------------------------------------------------
         */

        $projectedPlayers =
            [];


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
             *
             * Keep the existing six-fixture source horizon.
             *
             * MultiGameweekExpectedPoints owns fixture grouping,
             * including multiple fixtures belonging to one FPL
             * gameweek.
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


            /*
             * ----------------------------------------------------
             * REQUIRE GAMEWEEK PROJECTION EVIDENCE
             * ----------------------------------------------------
             */

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
             * FIND EARLIEST REPRESENTED GAMEWEEK
             * ----------------------------------------------------
             *
             * Free Hit is a one-gameweek chip.
             *
             * We therefore use the earliest valid FPL gameweek
             * represented by the existing projection response.
             *
             * We do not sum future gameweeks and we do not split
             * Double Gameweeks back into individual fixtures.
             */

            $earliestGameweek =
                null;


            $earliestGameweekProjection =
                null;


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


                if (
                    $earliestGameweek === null
                    ||
                    $gameweek < $earliestGameweek
                ) {

                    $earliestGameweek =
                        $gameweek;


                    $earliestGameweekProjection =
                        $gameweekProjection;
                }
            }


            /*
             * ----------------------------------------------------
             * UNSUPPORTED PROJECTION
             * ----------------------------------------------------
             *
             * Never manufacture zero Expected Points.
             *
             * A candidate without usable projection evidence is
             * omitted from the Free Hit optimizer pool.
             */

            if (
                $earliestGameweekProjection === null
            ) {

                continue;
            }


            $projectedPoints =
                $earliestGameweekProjection[
                    'projected_points'
                ];


            if (
                !is_numeric(
                    $projectedPoints
                )
            ) {

                continue;
            }


            /*
             * ----------------------------------------------------
             * ADAPT PLAYER CONTRACT
             * ----------------------------------------------------
             *
             * Preserve the complete existing candidate row and add
             * only the one-gameweek Expected Points value required
             * by FreeHitOptimizer.
             */

            $projectedPlayer =
                $player;


            $projectedPlayer[
                'projected_points'
            ] =
                (float) $projectedPoints;


            $projectedPlayer[
                'projection_gameweek'
            ] =
                (int) $earliestGameweek;


            $projectedPlayer[
                'projection_confidence'
            ] =
                isset(
                    $earliestGameweekProjection[
                        'projection_confidence'
                    ]
                )
                &&
                is_numeric(
                    $earliestGameweekProjection[
                        'projection_confidence'
                    ]
                )
                    ? (float) $earliestGameweekProjection[
                        'projection_confidence'
                    ]
                    : null;


            $projectedPlayers[] =
                $projectedPlayer;

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
