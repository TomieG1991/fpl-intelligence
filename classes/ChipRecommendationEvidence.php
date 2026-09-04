<?php

/**
 * ChipRecommendationEvidence
 *
 * Adapts the existing four Chip Intelligence production results
 * into a stable historical evidence structure for recommendation
 * history and backtesting.
 *
 * This class does not:
 *
 * - calculate Chip Intelligence
 * - rank chips against each other
 * - create an overall chip score
 * - change Use / Consider / Hold recommendations
 * - format numeric values for presentation
 */
class ChipRecommendationEvidence
{
    /**
     * Build raw historical evidence from the existing four
     * production chip result contracts.
     */
    public function build(
        array $wildcardResult,
        array $freeHitResult,
        array $benchBoostResult,
        array $tripleCaptainResult
    ): array {

        /*
         * ========================================================
         * WILDCARD
         * ========================================================
         *
         * Wildcard's existing decision lives inside timing_result.
         */

        $wildcardTiming =
            $wildcardResult[
                'timing_result'
            ]
            ??
            null;


        if (
            !is_array(
                $wildcardTiming
            )
        ) {

            throw new InvalidArgumentException(
                'Wildcard timing evidence is required.'
            );
        }


        $wildcardDecision =
            $wildcardTiming[
                'decision'
            ]
            ??
            null;


        if (
            !(
                $wildcardDecision
                instanceof
                ChipDecision
            )
        ) {

            throw new InvalidArgumentException(
                'Wildcard ChipDecision evidence is required.'
            );
        }


        /*
         * Preserve only the existing raw timing evidence required
         * for later evaluation.
         *
         * The ChipDecision object itself is exported separately.
         */
        $wildcardAnalysis =
            $wildcardTiming;


        unset(
            $wildcardAnalysis[
                'decision'
            ]
        );


        /*
         * ========================================================
         * FREE HIT
         * ========================================================
         */

        $freeHitDecision =
            $freeHitResult[
                'decision'
            ]
            ??
            null;


        if (
            !(
                $freeHitDecision
                instanceof
                ChipDecision
            )
        ) {

            throw new InvalidArgumentException(
                'Free Hit ChipDecision evidence is required.'
            );
        }


        $freeHitAnalysis =
            $freeHitResult[
                'value_result'
            ]
            ??
            null;


        if (
            !is_array(
                $freeHitAnalysis
            )
        ) {

            throw new InvalidArgumentException(
                'Free Hit supporting evidence is required.'
            );
        }


        /*
         * ========================================================
         * BENCH BOOST
         * ========================================================
         */

        $benchBoostDecision =
            $benchBoostResult[
                'decision'
            ]
            ??
            null;


        if (
            !(
                $benchBoostDecision
                instanceof
                ChipDecision
            )
        ) {

            throw new InvalidArgumentException(
                'Bench Boost ChipDecision evidence is required.'
            );
        }


        $benchBoostAnalysis =
            $benchBoostResult[
                'analysis'
            ]
            ??
            null;


        if (
            !is_array(
                $benchBoostAnalysis
            )
        ) {

            throw new InvalidArgumentException(
                'Bench Boost supporting evidence is required.'
            );
        }


        /*
         * ========================================================
         * TRIPLE CAPTAIN
         * ========================================================
         */

        $tripleCaptainDecision =
            $tripleCaptainResult[
                'decision'
            ]
            ??
            null;


        if (
            !(
                $tripleCaptainDecision
                instanceof
                ChipDecision
            )
        ) {

            throw new InvalidArgumentException(
                'Triple Captain ChipDecision evidence is required.'
            );
        }


        $tripleCaptainAnalysis =
            $tripleCaptainResult[
                'analysis'
            ]
            ??
            null;


        if (
            !is_array(
                $tripleCaptainAnalysis
            )
        ) {

            throw new InvalidArgumentException(
                'Triple Captain supporting evidence is required.'
            );
        }


        /*
         * ========================================================
         * STABLE HISTORICAL CONTRACT
         * ========================================================
         *
         * Each chip remains independent.
         *
         * No preferred chip, ranking or cross-chip score is
         * introduced.
         */

        return [

            'wildcard' => [

                'decision' =>
                    $wildcardDecision
                        ->toArray(),

                'analysis' =>
                    $wildcardAnalysis
            ],

            'free_hit' => [

                'decision' =>
                    $freeHitDecision
                        ->toArray(),

                'analysis' =>
                    $freeHitAnalysis
            ],

            'bench_boost' => [

                'decision' =>
                    $benchBoostDecision
                        ->toArray(),

                'analysis' =>
                    $benchBoostAnalysis
            ],

            'triple_captain' => [

                'decision' =>
                    $tripleCaptainDecision
                        ->toArray(),

                'analysis' =>
                    $tripleCaptainAnalysis
            ]
        ];
    }
}