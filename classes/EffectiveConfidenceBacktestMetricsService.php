<?php

/**
 * EffectiveConfidenceBacktestMetricsService
 *
 * Evaluates historical Effective Confidence against realised
 * player participation.
 *
 * Effective Confidence is a participation / reliability signal,
 * not a prediction of FPL points. The factual comparison target
 * is therefore realised participation:
 *
 *     actual minutes
 *     -------------------------
 *     fixture count × 90
 *
 * Realised participation is capped at 1.0.
 *
 * This service does not:
 *
 * - calculate Effective Confidence
 * - reconstruct recommendation-time confidence evidence
 * - compare confidence with FPL points
 * - query live player data
 * - query fixture history
 * - tune confidence weights
 * - choose a winning calibration
 * - persist calibration results
 */
class EffectiveConfidenceBacktestMetricsService
{
    /**
     * Summarise historical Effective Confidence evidence.
     */
    public function summarise(
        array $backtestRows
    ): array {

        /*
         * ========================================================
         * INITIAL STATE
         * ========================================================
         */

        $totalPlayers =
            0;


        $comparablePlayers =
            0;


        $totalAbsoluteError =
            0.0;


        $totalError =
            0.0;


        $confidenceValues =
            [];


        $actualParticipationValues =
            [];


        $comparisons =
            [];


        /*
         * ========================================================
         * EVALUATE HISTORICAL ROWS
         * ========================================================
         */

        foreach (
            $backtestRows
            as $row
        ) {

            /*
             * Malformed rows are ignored entirely rather than being
             * counted as unavailable player evidence.
             */
            if (!is_array($row)) {

                continue;
            }


            $totalPlayers++;


            /*
             * ====================================================
             * HISTORICAL EFFECTIVE CONFIDENCE
             * ====================================================
             */

            $effectiveConfidence =
                array_key_exists(
                    'effective_confidence',
                    $row
                )
                &&
                $row[
                    'effective_confidence'
                ] !== null
                &&
                is_numeric(
                    $row[
                        'effective_confidence'
                    ]
                )
                    ? (float) $row[
                        'effective_confidence'
                    ]
                    : null;


            /*
             * ====================================================
             * REALISED MINUTES
             * ====================================================
             *
             * Genuine zero minutes remain valid factual evidence.
             */

            $actualMinutes =
                array_key_exists(
                    'actual_minutes',
                    $row
                )
                &&
                $row[
                    'actual_minutes'
                ] !== null
                &&
                is_numeric(
                    $row[
                        'actual_minutes'
                    ]
                )
                    ? (float) $row[
                        'actual_minutes'
                    ]
                    : null;


            /*
             * ====================================================
             * REALISED FIXTURE COUNT
             * ====================================================
             *
             * Fixture count must be positive for a realised
             * participation denominator to exist.
             */

            $actualFixtureCount =
                array_key_exists(
                    'actual_fixture_count',
                    $row
                )
                &&
                $row[
                    'actual_fixture_count'
                ] !== null
                &&
                is_numeric(
                    $row[
                        'actual_fixture_count'
                    ]
                )
                    ? (float) $row[
                        'actual_fixture_count'
                    ]
                    : null;


            /*
             * ====================================================
             * COMPARABLE SAMPLE
             * ====================================================
             */

            if (
                $effectiveConfidence === null
                ||
                $actualMinutes === null
                ||
                $actualFixtureCount === null
                ||
                $actualFixtureCount <= 0.0
            ) {

                continue;
            }


            /*
             * ====================================================
             * REALISED PARTICIPATION RATE
             * ====================================================
             *
             * Use all available fixture minutes so Double
             * Gameweeks are treated correctly.
             *
             * A value greater than 1.0 is capped rather than
             * allowing impossible participation percentages.
             */

            $availableMinutes =
                $actualFixtureCount
                *
                90.0;


            $actualParticipationRate =
                $actualMinutes
                /
                $availableMinutes;


            $actualParticipationRate =
                max(
                    0.0,
                    min(
                        1.0,
                        $actualParticipationRate
                    )
                );


            /*
             * ====================================================
             * ERROR
             * ====================================================
             *
             * Signed error is:
             *
             * predicted - actual
             *
             * Positive:
             * confidence was higher than realised participation.
             *
             * Negative:
             * confidence was lower than realised participation.
             */

            $error =
                $effectiveConfidence
                -
                $actualParticipationRate;


            $absoluteError =
                abs(
                    $error
                );


            $comparablePlayers++;


            $totalError +=
                $error;


            $totalAbsoluteError +=
                $absoluteError;


            $confidenceValues[] =
                $effectiveConfidence;


            $actualParticipationValues[] =
                $actualParticipationRate;


            /*
             * Preserve useful row-level comparison evidence.
             *
             * Derived fields supplied by callers are deliberately
             * ignored and recalculated from primary evidence.
             */

            $comparisons[] = [

                'player_id' =>
                    $row[
                        'player_id'
                    ]
                    ?? null,

                'effective_confidence' =>
                    $effectiveConfidence,

                'actual_minutes' =>
                    $actualMinutes,

                'actual_fixture_count' =>
                    $actualFixtureCount,

                'actual_participation_rate' =>
                    $actualParticipationRate,

                'error' =>
                    $error,

                'absolute_error' =>
                    $absoluteError
            ];
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
         */

        $meanAbsoluteError =
            $comparablePlayers > 0
                ? $totalAbsoluteError
                    /
                    $comparablePlayers
                : null;


        /*
         * ========================================================
         * SIGNED MEAN ERROR
         * ========================================================
         */

        $meanError =
            $comparablePlayers > 0
                ? $totalError
                    /
                    $comparablePlayers
                : null;


        /*
         * ========================================================
         * PEARSON CORRELATION
         * ========================================================
         *
         * Correlation is available only when:
         *
         * - at least two comparable observations exist
         * - Effective Confidence has non-zero variance
         * - realised participation has non-zero variance
         */

        $correlation =
            null;


        if ($comparablePlayers >= 2) {

            $meanConfidence =
                array_sum(
                    $confidenceValues
                )
                /
                $comparablePlayers;


            $meanActualParticipation =
                array_sum(
                    $actualParticipationValues
                )
                /
                $comparablePlayers;


            $sumCrossProducts =
                0.0;


            $sumSquaredConfidenceDifferences =
                0.0;


            $sumSquaredParticipationDifferences =
                0.0;


            for (
                $index = 0;
                $index < $comparablePlayers;
                $index++
            ) {

                $confidenceDifference =
                    $confidenceValues[
                        $index
                    ]
                    -
                    $meanConfidence;


                $participationDifference =
                    $actualParticipationValues[
                        $index
                    ]
                    -
                    $meanActualParticipation;


                $sumCrossProducts +=
                    $confidenceDifference
                    *
                    $participationDifference;


                $sumSquaredConfidenceDifferences +=
                    $confidenceDifference
                    *
                    $confidenceDifference;


                $sumSquaredParticipationDifferences +=
                    $participationDifference
                    *
                    $participationDifference;
            }


            if (
                $sumSquaredConfidenceDifferences > 0.0
                &&
                $sumSquaredParticipationDifferences > 0.0
            ) {

                $denominator =
                    sqrt(
                        $sumSquaredConfidenceDifferences
                        *
                        $sumSquaredParticipationDifferences
                    );


                if ($denominator > 0.0) {

                    $correlation =
                        $sumCrossProducts
                        /
                        $denominator;


                    /*
                     * Protect the mathematical [-1, 1] range from
                     * tiny floating-point overshoots.
                     */
                    $correlation =
                        max(
                            -1.0,
                            min(
                                1.0,
                                $correlation
                            )
                        );
                }
            }
        }


        /*
         * ========================================================
         * STABLE METRICS CONTRACT
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
                $meanAbsoluteError,

            'mean_error' =>
                $meanError,

            'correlation' =>
                $correlation,

            'comparisons' =>
                $comparisons
        ];
    }
}