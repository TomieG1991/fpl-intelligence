<?php

class FreeHitHorizonIntelligenceService
{
    private FreeHitIntelligenceService
        $freeHitIntelligenceService;


    private SquadHorizonIntelligence
        $squadHorizonIntelligence;


    public function __construct(
        FreeHitIntelligenceService $freeHitIntelligenceService,
        SquadHorizonIntelligence $squadHorizonIntelligence
    ) {

        $this->freeHitIntelligenceService =
            $freeHitIntelligenceService;


        $this->squadHorizonIntelligence =
            $squadHorizonIntelligence;
    }


    /*
     * ============================================================
     * BUILD FREE HIT HORIZON INTELLIGENCE
     * ============================================================
     *
     * FreeHitIntelligenceService owns:
     *
     * - one-gameweek Expected Points candidate preparation
     * - Free Hit squad optimization
     *
     * SquadHorizonIntelligence owns:
     *
     * - legal Starting XI selection
     * - Starting XI projected points
     * - Starting XI projection confidence
     *
     * This service only adapts the optimized Free Hit squad into
     * the existing Squad Horizon input contract.
     */
    public function build(
        array $players,
        float $budget = 100.0
    ): array {

        /*
         * --------------------------------------------------------
         * BUILD OPTIMIZED FREE HIT SQUAD
         * --------------------------------------------------------
         */

        $freeHitResult =
            $this->freeHitIntelligenceService
                ->build(
                    $players,
                    $budget
                );


        /*
         * --------------------------------------------------------
         * REQUIRE AVAILABLE FREE HIT RESULT
         * --------------------------------------------------------
         */

        if (
            (
                $freeHitResult[
                    'status'
                ]
                ??
                null
            )
            !==
            'Available'
        ) {

            return [

                'status' =>
                    'Unavailable',

                'free_hit_result' =>
                    $freeHitResult,

                'horizon_result' =>
                    null
            ];
        }


        /*
         * --------------------------------------------------------
         * REQUIRE SUCCESSFUL OPTIMIZER RESULT
         * --------------------------------------------------------
         */

        $optimizerResult =
            $freeHitResult[
                'optimizer_result'
            ]
            ??
            null;


        if (
            !is_array(
                $optimizerResult
            )
            ||
            (
                $optimizerResult[
                    'status'
                ]
                ??
                null
            )
            !==
            'success'
        ) {

            return [

                'status' =>
                    'Unavailable',

                'free_hit_result' =>
                    $freeHitResult,

                'horizon_result' =>
                    null
            ];
        }


        /*
         * --------------------------------------------------------
         * REQUIRE COMPLETE OPTIMIZED SQUAD
         * --------------------------------------------------------
         */

        $optimizedSquad =
            $optimizerResult[
                'squad'
            ]
            ??
            [];


        if (
            !is_array(
                $optimizedSquad
            )
            ||
            count(
                $optimizedSquad
            )
            !==
            15
        ) {

            return [

                'status' =>
                    'Unavailable',

                'free_hit_result' =>
                    $freeHitResult,

                'horizon_result' =>
                    null
            ];
        }


        /*
         * --------------------------------------------------------
         * ADAPT FREE HIT SQUAD FOR SQUAD HORIZON
         * --------------------------------------------------------
         */

        $adaptedSquad =
            [];


        foreach (
            $optimizedSquad
            as $player
        ) {

            if (
                !is_array(
                    $player
                )
            ) {

                return [

                    'status' =>
                        'Unavailable',

                    'free_hit_result' =>
                        $freeHitResult,

                    'horizon_result' =>
                        null
                ];
            }


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


            $position =
                isset(
                    $player[
                        'position'
                    ]
                )
                    ? strtoupper(
                        trim(
                            (string) $player[
                                'position'
                            ]
                        )
                    )
                    : '';


            $projectionGameweek =
                isset(
                    $player[
                        'projection_gameweek'
                    ]
                )
                &&
                is_numeric(
                    $player[
                        'projection_gameweek'
                    ]
                )
                    ? (int) $player[
                        'projection_gameweek'
                    ]
                    : 0;


            $projectedPoints =
                isset(
                    $player[
                        'projected_points'
                    ]
                )
                &&
                is_numeric(
                    $player[
                        'projected_points'
                    ]
                )
                    ? (float) $player[
                        'projected_points'
                    ]
                    : null;


            $projectionConfidence =
                isset(
                    $player[
                        'projection_confidence'
                    ]
                )
                &&
                is_numeric(
                    $player[
                        'projection_confidence'
                    ]
                )
                    ? (float) $player[
                        'projection_confidence'
                    ]
                    : null;


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
                $projectionGameweek <= 0
                ||
                $projectedPoints === null
                ||
                $projectionConfidence === null
            ) {

                return [

                    'status' =>
                        'Unavailable',

                    'free_hit_result' =>
                        $freeHitResult,

                    'horizon_result' =>
                        null
                ];
            }


            $adaptedSquad[] = [

                'player_id' =>
                    $playerId,

                'name' =>
                    $player[
                        'name'
                    ]
                    ??
                    null,

                'position' =>
                    $position,

                'gameweeks' => [

                    $projectionGameweek => [

                        'gameweek' =>
                            $projectionGameweek,

                        'projected_points' =>
                            $projectedPoints,

                        'projection_confidence' =>
                            $projectionConfidence,

                        'team_id' =>
                            isset(
                                $player[
                                    'team_id'
                                ]
                            )
                            &&
                            is_numeric(
                                $player[
                                    'team_id'
                                ]
                            )
                                ? (int) $player[
                                    'team_id'
                                ]
                                : null
                    ]
                ]
            ];
        }


        /*
         * --------------------------------------------------------
         * BUILD ONE-GAMEWEEK SQUAD HORIZON
         * --------------------------------------------------------
         */

        $horizonResult =
            $this->squadHorizonIntelligence
                ->buildHorizon(
                    $adaptedSquad,
                    1
                );


        /*
         * --------------------------------------------------------
         * REQUIRE AVAILABLE HORIZON
         * --------------------------------------------------------
         */

        if (
            (
                $horizonResult[
                    'status'
                ]
                ??
                null
            )
            !==
            'Available'
        ) {

            return [

                'status' =>
                    'Unavailable',

                'free_hit_result' =>
                    $freeHitResult,

                'horizon_result' =>
                    $horizonResult
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

            'free_hit_result' =>
                $freeHitResult,

            'horizon_result' =>
                $horizonResult
        ];
    }
}