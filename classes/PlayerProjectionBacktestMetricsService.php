<?php

/**
 * PlayerProjectionBacktestMetricsService
 *
 * Summarises existing player-level projection backtest evidence
 * into initial objective gameweek-level evaluation metrics.
 *
 * This class does not:
 *
 * - calculate Expected Points
 * - reconstruct historical recommendation evidence
 * - query live player data
 * - query fixture history
 * - manufacture missing outcomes
 * - assign accuracy grades
 * - tune or calibrate the model
 *
 * It evaluates preserved historical projected points against
 * realised points already supplied by the backtesting pipeline.
 */
class PlayerProjectionBacktestMetricsService
{
    /**
     * Summarise player-level projection backtest evidence.
     */
    public function summarise(
        array $backtestRows
    ): array {

        /*
         * ========================================================
         * INITIAL COUNTERS
         * ========================================================
         */

        $totalPlayers =
            0;


        $comparablePlayers =
            0;


        $totalAbsoluteError =
            0.0;


        /*
         * ========================================================
         * EVALUATE BACKTEST ROWS
         * ========================================================
         */

        foreach (
            $backtestRows
            as $row
        ) {

            /*
             * Ignore malformed evidence rather than allowing it
             * to affect the historical evaluation sample.
             */
            if (
                !is_array(
                    $row
                )
            ) {

                continue;
            }


            $totalPlayers++;


            /*
             * ====================================================
             * HISTORICAL PROJECTED POINTS
             * ====================================================
             */

            $projectedPoints =
                array_key_exists(
                    'projected_points',
                    $row
                )
                &&
                $row[
                    'projected_points'
                ] !== null
                &&
                is_numeric(
                    $row[
                        'projected_points'
                    ]
                )
                    ? (float) $row[
                        'projected_points'
                    ]
                    : null;


            /*
             * ====================================================
             * REALISED POINTS
             * ====================================================
             *
             * A genuine zero or negative FPL return is numerical
             * evidence and therefore remains comparable.
             *
             * Missing evidence remains null and is excluded.
             */

            $actualPoints =
                array_key_exists(
                    'actual_points',
                    $row
                )
                &&
                $row[
                    'actual_points'
                ] !== null
                &&
                is_numeric(
                    $row[
                        'actual_points'
                    ]
                )
                    ? (float) $row[
                        'actual_points'
                    ]
                    : null;


            /*
             * ====================================================
             * COMPARABLE SAMPLE
             * ====================================================
             *
             * Both sides of the historical comparison must exist.
             */

            if (
                $projectedPoints === null
                ||
                $actualPoints === null
            ) {

                continue;
            }


            $comparablePlayers++;


            /*
             * ====================================================
             * ABSOLUTE ERROR
             * ====================================================
             *
             * Derive this directly from the primary evidence.
             *
             * Do not trust a supplied absolute_points_error field,
             * because that field is itself derived evidence.
             */

            $totalAbsoluteError +=
                abs(
                    $actualPoints
                    -
                    $projectedPoints
                );
        }


        /*
         * ========================================================
         * UNAVAILABLE SAMPLE
         * ========================================================
         */

        $unavailablePlayers =
            $totalPlayers
            -
            $comparablePlayers;


        /*
         * ========================================================
         * MEAN ABSOLUTE ERROR
         * ========================================================
         *
         * No comparable evidence means the metric is unavailable,
         * not zero.
         */

        $meanAbsoluteError =
            $comparablePlayers > 0
                ? $totalAbsoluteError
                    /
                    $comparablePlayers
                : null;


        /*
         * ========================================================
         * STABLE INITIAL METRICS CONTRACT
         * ========================================================
         */

        return [

            'total_players' =>
                $totalPlayers,

            'comparable_players' =>
                $comparablePlayers,

            'unavailable_players' =>
                $unavailablePlayers,

            'mean_absolute_error' =>
                $meanAbsoluteError
        ];
    }
}