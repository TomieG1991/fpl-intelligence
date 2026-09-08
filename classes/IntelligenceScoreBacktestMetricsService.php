<?php

/**
 * IntelligenceScoreBacktestMetricsService
 *
 * Calculates objective historical evaluation metrics for the
 * relationship between preserved Intelligence Scores and
 * realised FPL returns.
 *
 * Historical Intelligence Scores must come from immutable
 * recommendation evidence. Realised returns must come from
 * completed-gameweek outcome evidence.
 *
 * This service does not:
 *
 * - calculate or reconstruct Intelligence Scores
 * - calculate Expected Points
 * - query current Player Intelligence
 * - query fixture history
 * - manufacture missing realised returns
 * - interpret correlation as good or bad
 * - tune or calibrate model weights
 * - persist backtesting results
 */
class IntelligenceScoreBacktestMetricsService
{
    /**
     * Summarise the relationship between historical
     * Intelligence Scores and realised FPL points.
     */
    public function summarise(
        array $backtestRows
    ): array {

        /*
         * ========================================================
         * VALID PLAYER EVIDENCE
         * ========================================================
         *
         * Non-array rows are malformed evidence and are ignored
         * entirely rather than being counted as unavailable
         * players.
         */

        $totalPlayers =
            0;


        $comparablePlayers =
            0;


        $intelligenceScores =
            [];


        $actualPoints =
            [];


        foreach (
            $backtestRows
            as $row
        ) {

            if (!is_array($row)) {

                continue;
            }


            $totalPlayers++;


            /*
             * ====================================================
             * HISTORICAL INTELLIGENCE SCORE
             * ====================================================
             */

            $intelligenceScore =
                array_key_exists(
                    'intelligence_score',
                    $row
                )
                &&
                $row[
                    'intelligence_score'
                ] !== null
                &&
                is_numeric(
                    $row[
                        'intelligence_score'
                    ]
                )
                    ? (float) $row[
                        'intelligence_score'
                    ]
                    : null;


            /*
             * ====================================================
             * REALISED FPL RETURN
             * ====================================================
             *
             * Genuine zero and negative FPL points are valid
             * realised evidence.
             */

            $actualReturn =
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
             */

            if (
                $intelligenceScore === null
                ||
                $actualReturn === null
            ) {

                continue;
            }


            $comparablePlayers++;


            $intelligenceScores[] =
                $intelligenceScore;


            $actualPoints[] =
                $actualReturn;
        }


        /*
         * ========================================================
         * UNAVAILABLE EVIDENCE
         * ========================================================
         */

        $unavailablePlayers =
            $totalPlayers
            -
            $comparablePlayers;


        /*
         * ========================================================
         * PEARSON CORRELATION
         * ========================================================
         *
         * Pearson's correlation coefficient is calculated only
         * when at least two comparable observations exist and
         * both variables have non-zero variance.
         *
         * Otherwise the correlation is mathematically undefined
         * and remains null rather than being manufactured as zero.
         */

        $correlation =
            null;


        if ($comparablePlayers >= 2) {

            $meanIntelligenceScore =
                array_sum(
                    $intelligenceScores
                )
                /
                $comparablePlayers;


            $meanActualPoints =
                array_sum(
                    $actualPoints
                )
                /
                $comparablePlayers;


            $sumCrossProducts =
                0.0;


            $sumSquaredScoreDifferences =
                0.0;


            $sumSquaredPointsDifferences =
                0.0;


            for (
                $index = 0;
                $index < $comparablePlayers;
                $index++
            ) {

                $scoreDifference =
                    $intelligenceScores[
                        $index
                    ]
                    -
                    $meanIntelligenceScore;


                $pointsDifference =
                    $actualPoints[
                        $index
                    ]
                    -
                    $meanActualPoints;


                $sumCrossProducts +=
                    $scoreDifference
                    *
                    $pointsDifference;


                $sumSquaredScoreDifferences +=
                    $scoreDifference
                    *
                    $scoreDifference;


                $sumSquaredPointsDifferences +=
                    $pointsDifference
                    *
                    $pointsDifference;
            }


            /*
             * Zero variance in either variable makes Pearson
             * correlation undefined.
             */

            if (
                $sumSquaredScoreDifferences > 0.0
                &&
                $sumSquaredPointsDifferences > 0.0
            ) {

                $denominator =
                    sqrt(
                        $sumSquaredScoreDifferences
                        *
                        $sumSquaredPointsDifferences
                    );


                if ($denominator > 0.0) {

                    $correlation =
                        $sumCrossProducts
                        /
                        $denominator;


                    /*
                     * Floating-point arithmetic can theoretically
                     * produce an infinitesimal value outside the
                     * mathematical [-1, 1] range.
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
         * STABLE INITIAL CONTRACT
         * ========================================================
         */

        return [

            'total_players' =>
                $totalPlayers,

            'comparable_players' =>
                $comparablePlayers,

            'unavailable_players' =>
                $unavailablePlayers,

            'correlation' =>
                $correlation
        ];
    }
}