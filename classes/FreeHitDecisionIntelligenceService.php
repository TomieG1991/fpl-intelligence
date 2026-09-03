<?php

class FreeHitDecisionIntelligenceService
{
    private SquadHorizonIntelligenceService
        $squadHorizonIntelligenceService;


    private FreeHitHorizonIntelligenceService
        $freeHitHorizonIntelligenceService;


    private FreeHitDecisionIntelligence
        $freeHitDecisionIntelligence;


    public function __construct(
        SquadHorizonIntelligenceService $squadHorizonIntelligenceService,
        FreeHitHorizonIntelligenceService $freeHitHorizonIntelligenceService,
        FreeHitDecisionIntelligence $freeHitDecisionIntelligence
    ) {

        $this->squadHorizonIntelligenceService =
            $squadHorizonIntelligenceService;


        $this->freeHitHorizonIntelligenceService =
            $freeHitHorizonIntelligenceService;


        $this->freeHitDecisionIntelligence =
            $freeHitDecisionIntelligence;
    }


    public function build(
        array $importedSquad,
        array $players,
        float $budget = 100.0
    ): array {

        /*
         * ========================================================
         * BUILD CURRENT SQUAD ONE-GAMEWEEK HORIZON
         * ========================================================
         */

        $currentHorizonBuild =
            $this->squadHorizonIntelligenceService
                ->buildForImportedSquad(
                    $importedSquad,
                    1
                );


        if (
            (
                $currentHorizonBuild[
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

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'free_hit_horizon_result' =>
                    null,

                'value_result' =>
                    null,

                'decision' =>
                    null
            ];
        }


        /*
         * ========================================================
         * BUILD FREE HIT ONE-GAMEWEEK HORIZON
         * ========================================================
         */

        $freeHitHorizonBuild =
            $this->freeHitHorizonIntelligenceService
                ->build(
                    $players,
                    $budget
                );


        if (
            (
                $freeHitHorizonBuild[
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

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'free_hit_horizon_result' =>
                    $freeHitHorizonBuild,

                'value_result' =>
                    null,

                'decision' =>
                    null
            ];
        }


        /*
         * ========================================================
         * EXTRACT CURRENT AND FREE HIT HORIZON RESULTS
         * ========================================================
         */

        $currentHorizon =
            $currentHorizonBuild[
                'horizon_result'
            ]
            ??
            null;


        $freeHitHorizon =
            $freeHitHorizonBuild[
                'horizon_result'
            ]
            ??
            null;


        if (
            !is_array(
                $currentHorizon
            )
            ||
            !is_array(
                $freeHitHorizon
            )
        ) {

            return [

                'status' =>
                    'Unavailable',

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'free_hit_horizon_result' =>
                    $freeHitHorizonBuild,

                'value_result' =>
                    null,

                'decision' =>
                    null
            ];
        }


        /*
         * ========================================================
         * REQUIRE ONE REPRESENTED GAMEWEEK ON EACH SIDE
         * ========================================================
         */

        $currentGameweeks =
            $currentHorizon[
                'gameweeks'
            ]
            ??
            [];


        $freeHitGameweeks =
            $freeHitHorizon[
                'gameweeks'
            ]
            ??
            [];


        if (
            !is_array(
                $currentGameweeks
            )
            ||
            !is_array(
                $freeHitGameweeks
            )
            ||
            count(
                $currentGameweeks
            )
            !==
            1
            ||
            count(
                $freeHitGameweeks
            )
            !==
            1
        ) {

            return [

                'status' =>
                    'Unavailable',

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'free_hit_horizon_result' =>
                    $freeHitHorizonBuild,

                'value_result' =>
                    null,

                'decision' =>
                    null
            ];
        }


        $currentGameweekProjection =
            reset(
                $currentGameweeks
            );


        $freeHitGameweekProjection =
            reset(
                $freeHitGameweeks
            );


        if (
            !is_array(
                $currentGameweekProjection
            )
            ||
            !is_array(
                $freeHitGameweekProjection
            )
        ) {

            return [

                'status' =>
                    'Unavailable',

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'free_hit_horizon_result' =>
                    $freeHitHorizonBuild,

                'value_result' =>
                    null,

                'decision' =>
                    null
            ];
        }


        /*
         * ========================================================
         * REQUIRE SAME GAMEWEEK
         * ========================================================
         */

        $currentGameweek =
            isset(
                $currentGameweekProjection[
                    'gameweek'
                ]
            )
            &&
            is_numeric(
                $currentGameweekProjection[
                    'gameweek'
                ]
            )
                ? (int) $currentGameweekProjection[
                    'gameweek'
                ]
                : 0;


        $freeHitGameweek =
            isset(
                $freeHitGameweekProjection[
                    'gameweek'
                ]
            )
            &&
            is_numeric(
                $freeHitGameweekProjection[
                    'gameweek'
                ]
            )
                ? (int) $freeHitGameweekProjection[
                    'gameweek'
                ]
                : 0;


        if (
            $currentGameweek <= 0
            ||
            $freeHitGameweek <= 0
            ||
            $currentGameweek
            !==
            $freeHitGameweek
        ) {

            return [

                'status' =>
                    'Unavailable',

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'free_hit_horizon_result' =>
                    $freeHitHorizonBuild,

                'value_result' =>
                    null,

                'decision' =>
                    null
            ];
        }


        /*
         * ========================================================
         * REQUIRE STARTING XI PROJECTED POINTS
         * ========================================================
         */

        $currentProjectedPoints =
            $currentGameweekProjection[
                'starting_xi_projected_points'
            ]
            ??
            null;


        $freeHitProjectedPoints =
            $freeHitGameweekProjection[
                'starting_xi_projected_points'
            ]
            ??
            null;


        if (
            !is_numeric(
                $currentProjectedPoints
            )
            ||
            !is_numeric(
                $freeHitProjectedPoints
            )
        ) {

            return [

                'status' =>
                    'Unavailable',

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'free_hit_horizon_result' =>
                    $freeHitHorizonBuild,

                'value_result' =>
                    null,

                'decision' =>
                    null
            ];
        }


        /*
         * ========================================================
         * REQUIRE STARTING XI PROJECTION CONFIDENCE
         * ========================================================
         */

        $currentConfidence =
            $currentGameweekProjection[
                'starting_xi_projection_confidence'
            ]
            ??
            null;


        $freeHitConfidence =
            $freeHitGameweekProjection[
                'starting_xi_projection_confidence'
            ]
            ??
            null;


        if (
            !is_numeric(
                $currentConfidence
            )
            ||
            !is_numeric(
                $freeHitConfidence
            )
        ) {

            return [

                'status' =>
                    'Unavailable',

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'free_hit_horizon_result' =>
                    $freeHitHorizonBuild,

                'value_result' =>
                    null,

                'decision' =>
                    null
            ];
        }


        $currentConfidence =
            (float) $currentConfidence;


        $freeHitConfidence =
            (float) $freeHitConfidence;


        if (
            $currentConfidence < 0.0
            ||
            $currentConfidence > 1.0
            ||
            $freeHitConfidence < 0.0
            ||
            $freeHitConfidence > 1.0
        ) {

            return [

                'status' =>
                    'Unavailable',

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'free_hit_horizon_result' =>
                    $freeHitHorizonBuild,

                'value_result' =>
                    null,

                'decision' =>
                    null
            ];
        }


        /*
         * ========================================================
         * COMPARE CURRENT SQUAD AGAINST FREE HIT
         * ========================================================
         */

        $valueResult =
            $this->freeHitDecisionIntelligence
                ->analyseValue(
                    (float) $currentProjectedPoints,
                    (float) $freeHitProjectedPoints
                );


        $projectedPointsGain =
            $valueResult[
                'projected_points_gain'
            ]
            ??
            null;


        if (
            !is_numeric(
                $projectedPointsGain
            )
        ) {

            return [

                'status' =>
                    'Unavailable',

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'free_hit_horizon_result' =>
                    $freeHitHorizonBuild,

                'value_result' =>
                    $valueResult,

                'decision' =>
                    null
            ];
        }


        /*
         * Comparison confidence is limited by the weaker
         * Starting XI projection.
         */
        $decisionConfidence =
            min(
                $currentConfidence,
                $freeHitConfidence
            );


        $decision =
            $this->freeHitDecisionIntelligence
                ->createDecision(
                    (float) $projectedPointsGain,
                    $decisionConfidence
                );


        /*
         * ========================================================
         * SUCCESS
         * ========================================================
         */

        return [

            'status' =>
                'Available',

            'current_horizon_result' =>
                $currentHorizonBuild,

            'free_hit_horizon_result' =>
                $freeHitHorizonBuild,

            'value_result' =>
                $valueResult,

            'decision' =>
                $decision
        ];
    }
}