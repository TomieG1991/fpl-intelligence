<?php

/**
 * PlayerRankingBacktestingMetricsService
 *
 * Aggregates player-level Player Ranking backtesting evidence
 * into statistical measures describing the relationship between
 * historical Player Intelligence and realised FPL returns.
 *
 * This class calculates:
 *
 * - sample size
 * - Pearson correlation between Intelligence Score and
 *   realised FPL points
 * - Spearman rank correlation between historical ranking and
 *   realised FPL-points ordering
 *
 * This class does not:
 *
 * - compare ranking evidence with player outcomes
 * - recalculate Intelligence Scores
 * - regenerate historical Intelligence ranks
 * - tune model weights
 * - create an overall model score
 * - mutate source evidence
 */
class PlayerRankingBacktestingMetricsService
{
    /**
     * Calculate aggregate Player Ranking backtesting metrics.
     */
    public function calculate(
        array $evaluations
    ): array {

        /*
         * ========================================================
         * VALID METRIC EVIDENCE
         * ========================================================
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


            $intelligenceScore =
                $evaluation[
                    'intelligence_score'
                ]
                ?? null;


            $rank =
                $evaluation[
                    'rank'
                ]
                ?? null;


            $actualPoints =
                $evaluation[
                    'actual_points'
                ]
                ?? null;


            if (
                !is_numeric(
                    $intelligenceScore
                )
                ||
                !is_numeric(
                    $rank
                )
                ||
                !is_numeric(
                    $actualPoints
                )
            ) {

                continue;
            }


            $historicalRank =
                (int) $rank;


            if (
                $historicalRank <= 0
            ) {

                continue;
            }


            $validEvaluations[] = [

                'intelligence_score' =>
                    (float) $intelligenceScore,

                'rank' =>
                    $historicalRank,

                'actual_points' =>
                    (float) $actualPoints
            ];
        }


        $sampleSize =
            count(
                $validEvaluations
            );


        /*
         * ========================================================
         * INSUFFICIENT SAMPLE
         * ========================================================
         */

        if (
            $sampleSize < 2
        ) {

            return [

                'sample_size' =>
                    $sampleSize,

                'pearson_correlation' =>
                    null,

                'spearman_rank_correlation' =>
                    null
            ];
        }


        /*
         * ========================================================
         * PEARSON CORRELATION
         * ========================================================
         *
         * Pearson measures the linear relationship between the
         * magnitude of historical Intelligence Scores and
         * realised FPL points.
         */

        $intelligenceScores =
            array_column(
                $validEvaluations,
                'intelligence_score'
            );


        $actualPoints =
            array_column(
                $validEvaluations,
                'actual_points'
            );


        $pearsonCorrelation =
            $this->calculatePearsonCorrelation(
                $intelligenceScores,
                $actualPoints
            );


        /*
         * ========================================================
         * SPEARMAN RANK CORRELATION
         * ========================================================
         *
         * Historical Intelligence ranks are preserved evidence.
         *
         * We therefore do not regenerate them from Intelligence
         * Score.
         *
         * Realised FPL points are converted into realised ranks.
         * Tied realised points receive average ranks.
         *
         * Historical rank 1 means strongest predicted player.
         * Realised rank 1 therefore also means strongest realised
         * FPL return.
         */

        $historicalRanks =
            array_map(
                static function (
                    array $evaluation
                ): float {

                    return
                        (float) $evaluation[
                            'rank'
                        ];
                },
                $validEvaluations
            );


        /*
         * Historical rank numbers describe positions in the
         * original full-player-pool ranking.
         *
         * The evaluated sample may contain gaps because some
         * historically ranked players may not have authoritative
         * realised outcome evidence.
         *
         * Spearman measures relative ordering within the sample,
         * so historical rank values must themselves be converted
         * into sample-relative ranks before correlation.
         *
         * Lower historical rank is better. Negating the values
         * allows the existing descending average-rank helper to
         * preserve that ordering:
         *
         * Historical: 2, 7, 15
         * Negated:    -2, -7, -15
         * Sample rank: 1, 2, 3
         */
        $historicalSampleRanks =
            $this->calculateDescendingAverageRanks(
                array_map(
                    static function (
                        float $rank
                    ): float {

                        return
                            -$rank;
                    },
                    $historicalRanks
                )
            );


        $realisedRanks =
            $this->calculateDescendingAverageRanks(
                $actualPoints
            );


        $spearmanRankCorrelation =
            $this->calculatePearsonCorrelation(
                $historicalSampleRanks,
                $realisedRanks
            );


        /*
         * ========================================================
         * METRICS CONTRACT
         * ========================================================
         */

        return [

            'sample_size' =>
                $sampleSize,

            'pearson_correlation' =>
                $pearsonCorrelation,

            'spearman_rank_correlation' =>
                $spearmanRankCorrelation
        ];
    }


    /**
     * Calculate Pearson's product-moment correlation coefficient.
     *
     * Returns null when correlation is mathematically undefined,
     * including when either input has no variation.
     */
    private function calculatePearsonCorrelation(
        array $xValues,
        array $yValues
    ): ?float {

        $sampleSize =
            count(
                $xValues
            );


        if (
            $sampleSize < 2
            ||
            $sampleSize !==
                count(
                    $yValues
                )
        ) {

            return null;
        }


        $xMean =
            array_sum(
                $xValues
            )
            /
            $sampleSize;


        $yMean =
            array_sum(
                $yValues
            )
            /
            $sampleSize;


        $crossProductTotal =
            0.0;


        $xSquaredDeviationTotal =
            0.0;


        $ySquaredDeviationTotal =
            0.0;


        for (
            $index = 0;
            $index < $sampleSize;
            $index++
        ) {

            $xDeviation =
                (float) $xValues[
                    $index
                ]
                -
                $xMean;


            $yDeviation =
                (float) $yValues[
                    $index
                ]
                -
                $yMean;


            $crossProductTotal +=
                $xDeviation
                *
                $yDeviation;


            $xSquaredDeviationTotal +=
                $xDeviation
                *
                $xDeviation;


            $ySquaredDeviationTotal +=
                $yDeviation
                *
                $yDeviation;
        }


        if (
            $xSquaredDeviationTotal <= 0.0
            ||
            $ySquaredDeviationTotal <= 0.0
        ) {

            return null;
        }


        $denominator =
            sqrt(
                $xSquaredDeviationTotal
                *
                $ySquaredDeviationTotal
            );


        if (
            $denominator <= 0.0
        ) {

            return null;
        }


        $correlation =
            $crossProductTotal
            /
            $denominator;


        /*
         * Floating-point arithmetic can very occasionally produce
         * a result microscopically outside the mathematical
         * correlation range.
         */
        if (
            $correlation > 1.0
        ) {

            return 1.0;
        }


        if (
            $correlation < -1.0
        ) {

            return -1.0;
        }


        return
            $correlation;
    }


    /**
     * Convert realised values into descending average ranks.
     *
     * Highest realised value receives rank 1.
     *
     * Tied values receive the average of the positions they
     * occupy.
     *
     * Example:
     *
     * Values:
     * 10, 8, 8, 2
     *
     * Ranks:
     * 1, 2.5, 2.5, 4
     *
     * Returned ranks preserve the original input order.
     */
    private function calculateDescendingAverageRanks(
        array $values
    ): array {

        $indexedValues =
            [];


        foreach (
            $values
            as $index => $value
        ) {

            $indexedValues[] = [

                'index' =>
                    $index,

                'value' =>
                    (float) $value
            ];
        }


        usort(
            $indexedValues,
            static function (
                array $a,
                array $b
            ): int {

                if (
                    $a[
                        'value'
                    ]
                    ===
                    $b[
                        'value'
                    ]
                ) {

                    return
                        $a[
                            'index'
                        ]
                        <=>
                        $b[
                            'index'
                        ];
                }


                return
                    $b[
                        'value'
                    ]
                    <=>
                    $a[
                        'value'
                    ];
            }
        );


        $ranksByOriginalIndex =
            [];


        $count =
            count(
                $indexedValues
            );


        $position =
            0;


        while (
            $position < $count
        ) {

            $tieEnd =
                $position;


            while (
                $tieEnd + 1 < $count
                &&
                $indexedValues[
                    $tieEnd + 1
                ][
                    'value'
                ]
                ===
                $indexedValues[
                    $position
                ][
                    'value'
                ]
            ) {

                $tieEnd++;
            }


            /*
             * Array positions are zero-based while statistical
             * ranks are one-based.
             */
            $firstRank =
                $position + 1;


            $lastRank =
                $tieEnd + 1;


            $averageRank =
                (
                    $firstRank
                    +
                    $lastRank
                )
                /
                2;


            for (
                $tieIndex = $position;
                $tieIndex <= $tieEnd;
                $tieIndex++
            ) {

                $originalIndex =
                    $indexedValues[
                        $tieIndex
                    ][
                        'index'
                    ];


                $ranksByOriginalIndex[
                    $originalIndex
                ] =
                    (float) $averageRank;
            }


            $position =
                $tieEnd + 1;
        }


        ksort(
            $ranksByOriginalIndex
        );


        return
            array_values(
                $ranksByOriginalIndex
            );
    }
}