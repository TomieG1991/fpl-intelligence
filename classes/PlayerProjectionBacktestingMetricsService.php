<?php

/**
 * PlayerProjectionBacktestingMetricsService
 *
 * Aggregates existing player-level projection backtesting
 * evidence into simple projection-accuracy metrics.
 *
 * This class does not:
 *
 * - compare projections with actual outcomes
 * - calculate player-level errors
 * - calculate RMSE
 * - calculate correlation
 * - calculate calibration
 * - create an overall model score
 * - evaluate captain recommendations
 * - evaluate transfer recommendations
 *
 * It only aggregates existing player-level comparison evidence.
 */
class PlayerProjectionBacktestingMetricsService
{
    /**
     * Calculate aggregate projection backtesting metrics.
     */
    public function calculate(
        array $evaluations
    ): array {

        /*
         * ========================================================
         * AGGREGATION STATE
         * ========================================================
         */

        $pointsSampleSize =
            0;


        $pointsErrorTotal =
            0.0;


        $pointsAbsoluteErrorTotal =
            0.0;


        $minutesSampleSize =
            0;


        $minutesErrorTotal =
            0.0;


        $minutesAbsoluteErrorTotal =
            0.0;


        /*
         * ========================================================
         * AGGREGATE PLAYER-LEVEL EVIDENCE
         * ========================================================
         */

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


            /*
             * ----------------------------------------------------
             * POINTS EVIDENCE
             * ----------------------------------------------------
             *
             * Both directional and absolute error evidence must
             * exist before the row can contribute to the sample.
             *
             * Explicit zero values remain valid evidence.
             */

            $pointsError =
                $evaluation[
                    'points_error'
                ]
                ?? null;


            $pointsAbsoluteError =
                $evaluation[
                    'absolute_points_error'
                ]
                ?? null;


            if (
                is_numeric(
                    $pointsError
                )
                &&
                is_numeric(
                    $pointsAbsoluteError
                )
            ) {

                $pointsSampleSize++;


                $pointsErrorTotal +=
                    (float) $pointsError;


                $pointsAbsoluteErrorTotal +=
                    (float) $pointsAbsoluteError;
            }


            /*
             * ----------------------------------------------------
             * MINUTES EVIDENCE
             * ----------------------------------------------------
             *
             * Minutes use an independent sample because valid
             * points evidence may exist without projected minutes.
             */

            $minutesError =
                $evaluation[
                    'minutes_error'
                ]
                ?? null;


            $minutesAbsoluteError =
                $evaluation[
                    'absolute_minutes_error'
                ]
                ?? null;


            if (
                is_numeric(
                    $minutesError
                )
                &&
                is_numeric(
                    $minutesAbsoluteError
                )
            ) {

                $minutesSampleSize++;


                $minutesErrorTotal +=
                    (float) $minutesError;


                $minutesAbsoluteErrorTotal +=
                    (float) $minutesAbsoluteError;
            }
        }


        /*
         * ========================================================
         * POINTS METRICS
         * ========================================================
         */

        $pointsMeanError =
            null;


        $pointsMeanAbsoluteError =
            null;


        if (
            $pointsSampleSize > 0
        ) {

            $pointsMeanError =
                $pointsErrorTotal
                /
                $pointsSampleSize;


            $pointsMeanAbsoluteError =
                $pointsAbsoluteErrorTotal
                /
                $pointsSampleSize;
        }


        /*
         * ========================================================
         * MINUTES METRICS
         * ========================================================
         */

        $minutesMeanError =
            null;


        $minutesMeanAbsoluteError =
            null;


        if (
            $minutesSampleSize > 0
        ) {

            $minutesMeanError =
                $minutesErrorTotal
                /
                $minutesSampleSize;


            $minutesMeanAbsoluteError =
                $minutesAbsoluteErrorTotal
                /
                $minutesSampleSize;
        }


        /*
         * ========================================================
         * METRICS CONTRACT
         * ========================================================
         */

        return [

            'points' => [

                'sample_size' =>
                    $pointsSampleSize,

                'mean_error' =>
                    $pointsMeanError,

                'mean_absolute_error' =>
                    $pointsMeanAbsoluteError
            ],

            'minutes' => [

                'sample_size' =>
                    $minutesSampleSize,

                'mean_error' =>
                    $minutesMeanError,

                'mean_absolute_error' =>
                    $minutesMeanAbsoluteError
            ]
        ];
    }
}