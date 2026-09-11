<?php

/**
 * ProjectionCalibrationDiagnosticsService
 *
 * Analyses existing historical player-level projection
 * backtesting evidence.
 *
 * This service does not:
 *
 * - calculate Expected Points
 * - calculate Expected Minutes
 * - recalculate Projection Confidence
 * - alter historical evidence
 * - recommend projection parameter changes
 * - choose a preferred model
 * - create a synthetic model score
 *
 * It only groups existing projection backtesting evidence and
 * delegates aggregate error metrics to the existing
 * PlayerProjectionBacktestingMetricsService.
 */
class ProjectionCalibrationDiagnosticsService
{
    private object
        $metricsService;


    /**
     * Constructor.
     */
    public function __construct(
        object $metricsService
    ) {

        $this->metricsService =
            $metricsService;
    }


    /**
     * Evaluate projection calibration diagnostics.
     */
    public function evaluate(
        array $evaluations
    ): array {

        /*
         * ========================================================
         * CONTROLLED GROUP CONTRACT
         * ========================================================
         */

        $positionGroups = [

            'GK' =>
                [],

            'DEF' =>
                [],

            'MID' =>
                [],

            'FWD' =>
                [],

            'Unknown' =>
                []
        ];


        $confidenceGroups = [

            'High' =>
                [],

            'Moderate' =>
                [],

            'Low' =>
                [],

            'Very Low' =>
                [],

            'Unavailable' =>
                []
        ];


        /*
         * ========================================================
         * NORMALISE VALID PLAYER EVALUATIONS
         * ========================================================
         *
         * Malformed non-array rows are ignored completely.
         *
         * Valid array rows remain eligible for overall metrics,
         * while missing diagnostic dimensions are grouped into
         * their explicit fallback buckets.
         */

        $validEvaluations =
            [];


        foreach (
            $evaluations
            as $evaluation
        ) {

            if (
                !is_array(
                    $evaluation
                )
            ) {

                continue;
            }


            $validEvaluations[] =
                $evaluation;


            /*
             * ----------------------------------------------------
             * POSITION GROUP
             * ----------------------------------------------------
             */

            $position =
                strtoupper(
                    trim(
                        (string) (
                            $evaluation[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                );


            if (
                !array_key_exists(
                    $position,
                    $positionGroups
                )
                ||
                $position ===
                    'UNKNOWN'
            ) {

                $position =
                    'Unknown';
            }


            $positionGroups[
                $position
            ][] =
                $evaluation;


            /*
             * ----------------------------------------------------
             * CONFIDENCE GROUP
             * ----------------------------------------------------
             *
             * Historical classification is authoritative.
             *
             * Do not recalculate labels from the numeric
             * confidence value because that would reinterpret
             * historical recommendation-time evidence using later
             * logic.
             */

            $confidenceLabel =
                trim(
                    (string) (
                        $evaluation[
                            'projection_confidence_label'
                        ]
                        ?? ''
                    )
                );


            if (
                !array_key_exists(
                    $confidenceLabel,
                    $confidenceGroups
                )
                ||
                $confidenceLabel ===
                    'Unavailable'
            ) {

                $confidenceLabel =
                    'Unavailable';
            }


            $confidenceGroups[
                $confidenceLabel
            ][] =
                $evaluation;
        }


        /*
         * ========================================================
         * OVERALL METRICS
         * ========================================================
         */

        $overallMetrics =
            $this
                ->metricsService
                ->calculate(
                    $validEvaluations
                );


        /*
         * ========================================================
         * POSITION METRICS
         * ========================================================
         */

        $positionMetrics =
            [];


        foreach (
            $positionGroups
            as $position =>
                $groupEvaluations
        ) {

            $positionMetrics[
                $position
            ] =
                $this
                    ->metricsService
                    ->calculate(
                        $groupEvaluations
                    );
        }


        /*
         * ========================================================
         * CONFIDENCE METRICS
         * ========================================================
         */

        $confidenceMetrics =
            [];


        foreach (
            $confidenceGroups
            as $confidenceLabel =>
                $groupEvaluations
        ) {

            $confidenceMetrics[
                $confidenceLabel
            ] =
                $this
                    ->metricsService
                    ->calculate(
                        $groupEvaluations
                    );
        }


        /*
         * ========================================================
         * DIAGNOSTIC CONTRACT
         * ========================================================
         */

        return [

            'overall' =>
                $overallMetrics,

            'by_position' =>
                $positionMetrics,

            'by_confidence' =>
                $confidenceMetrics
        ];
    }
}