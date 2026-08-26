<?php

class ExpectedDefensiveContributions
{
    /**
     * Project FPL defensive-contribution points.
     *
     * 2026/27 rules:
     *
     * DEF:
     * 10 CBIT -> 2 FPL points
     *
     * MID / FWD:
     * 12 CBIRT -> 2 FPL points
     *
     * GK:
     * Not applicable
     *
     * Because the scoring event is threshold-based, Expected
     * Points are derived from the estimated probability of
     * reaching the threshold rather than simply scaling the
     * action count linearly.
     */
    public function calculate(
        string $position,
        float $projectedMinutes,
        array $form,
        array $fixtureContext = []
    ): array {

        $position =
            strtoupper(
                trim(
                    $position
                )
            );


        $projectedMinutes =
            max(
                0.0,
                min(
                    90.0,
                    $projectedMinutes
                )
            );


        /*
         * --------------------------------------------------------
         * POSITION CONTRACT
         * --------------------------------------------------------
         */

        if (
            $position === 'GK'
        ) {

            return $this->emptyModel(
                $position,
                'Not Applicable'
            );
        }


        if (
            !in_array(
                $position,
                [
                    'DEF',
                    'MID',
                    'FWD'
                ],
                true
            )
        ) {

            return $this->emptyModel(
                $position,
                'Unsupported Position'
            );
        }


        $weightedMetrics =
            is_array(
                $form[
                    'weighted_metrics'
                ]
                ?? null
            )
                ? $form[
                    'weighted_metrics'
                ]
                : [];


        /*
         * --------------------------------------------------------
         * POSITION-SPECIFIC ACTION RATE
         * --------------------------------------------------------
         */

        if (
            $position === 'DEF'
        ) {

            $metric =
                'cbit_per_90';


            $threshold =
                10;

        } else {

            $metric =
                'cbirt_per_90';


            $threshold =
                12;
        }
        
        /*
         * --------------------------------------------------------
         * POSITION BASELINE
         * --------------------------------------------------------
         *
         * Priors are derived from the complete GW1 2026/27
         * 75+ minute population medians:
         *
         * DEF CBIT / 90  = 7.00
         * MID CBIRT / 90 = 8.00
         * FWD CBIRT / 90 = 3.00
         *
         * These priors prevent one early-season fixture from being
         * treated as a mature estimate of the player's true rate.
         */

        $positionBaseline =
            match (
                $position
            ) {

                'DEF' =>
                    7.0,

                'MID' =>
                    8.0,

                'FWD' =>
                    3.0,

                default =>
                    0.0
            };


        $actionsPer90 =
            $this->nullableNonNegative(
                $weightedMetrics[
                    $metric
                ]
                ?? null
            );
            
        /*
         * --------------------------------------------------------
         * SAMPLE CONFIDENCE
         * --------------------------------------------------------
         *
         * Defensive-action rate is an on-pitch performance metric,
         * so appearance sample size is the relevant evidence count.
         *
         * Five appearances represent a mature recent sample for this
         * first model.
         */

        $appearanceSampleSize =
            max(
                0,
                (int) (
                    $form[
                        'appearance_sample_size'
                    ]
                    ?? 0
                )
            );


        $sampleConfidence =
            min(
                1.0,
                $appearanceSampleSize
                /
                5
            );


        /*
         * Regress the observed player rate toward the position prior.
         */

        $regressedActionsPer90 =
            (
                $actionsPer90
                *
                $sampleConfidence
            )
            +
            (
                $positionBaseline
                *
                (
                    1
                    -
                    $sampleConfidence
                )
            );


        if (
            $actionsPer90 === null
        ) {

            return $this->emptyModel(
                $position,
                'Insufficient Data',
                $threshold
            );
        }


        /*
         * --------------------------------------------------------
         * OPPONENT CONTEXT
         * --------------------------------------------------------
         *
         * Stronger opposition should normally create more
         * defensive-action opportunities.
         *
         * Conservative multiplier:
         *
         * Attack Rating 0   -> 0.85
         * Attack Rating 50  -> 1.00
         * Attack Rating 100 -> 1.15
         */

        $opponentAttackRating =
            $this->nullableBounded(
                $fixtureContext[
                    'opponent_attack_rating'
                ]
                ?? null,
                0,
                100
            );


        $opportunityMultiplier =
            $opponentAttackRating !== null
                ? 0.85
                    +
                    (
                        $opponentAttackRating
                        *
                        0.003
                    )
                : 1.0;


        /*
         * Expected defensive-action count for the upcoming
         * fixture.
         */

        $expectedActions =
            $regressedActionsPer90
            *
            (
                $projectedMinutes
                /
                90
            )
            *
            $opportunityMultiplier;


        $expectedActions =
            max(
                0.0,
                $expectedActions
            );


        /*
         * --------------------------------------------------------
         * THRESHOLD PROBABILITY
         * --------------------------------------------------------
         *
         * Treat defensive actions as a count process.
         *
         * P(X >= threshold)
         *
         * =
         *
         * 1 - P(X <= threshold - 1)
         *
         * using a Poisson distribution with lambda equal to the
         * projected defensive-action count.
         */

        $thresholdProbability =
            $this->poissonAtLeast(
                $expectedActions,
                $threshold
            );


        /*
         * Defensive contribution scoring is capped at two
         * points per match.
         */

        $expectedPoints =
            $thresholdProbability
            *
            2.0;


        return [

            'position' =>
                $position,

            'status' =>
                'Modelled',

            'metric' =>
                $metric,

            'threshold' =>
                $threshold,

            'actions_per_90' =>
                round(
                    $actionsPer90,
                    4
                ),
                
            'position_baseline' =>
                round(
                    $positionBaseline,
                    4
                ),

            'appearance_sample_size' =>
                $appearanceSampleSize,

            'sample_confidence' =>
                round(
                    $sampleConfidence,
                    4
                ),

            'sample_confidence_percent' =>
                round(
                    $sampleConfidence
                    *
                    100,
                    2
                ),

            'regressed_actions_per_90' =>
                round(
                    $regressedActionsPer90,
                    4
                ),

            'projected_minutes' =>
                round(
                    $projectedMinutes,
                    2
                ),

            'opponent_attack_rating' =>
                $opponentAttackRating,

            'opportunity_multiplier' =>
                round(
                    $opportunityMultiplier,
                    4
                ),

            'expected_actions' =>
                round(
                    $expectedActions,
                    4
                ),

            'threshold_probability' =>
                round(
                    $thresholdProbability,
                    4
                ),

            'threshold_probability_percent' =>
                round(
                    $thresholdProbability
                    *
                    100,
                    2
                ),

            'expected_points' =>
                round(
                    max(
                        0.0,
                        min(
                            2.0,
                            $expectedPoints
                        )
                    ),
                    4
                )
        ];
    }


    /**
     * Probability of observing at least $threshold events from
     * a Poisson process with mean $lambda.
     */
    public function poissonAtLeast(
        float $lambda,
        int $threshold
    ): float {

        if (
            $threshold <= 0
        ) {

            return 1.0;
        }


        if (
            $lambda <= 0
        ) {

            return 0.0;
        }


        /*
         * Calculate:
         *
         * P(X = 0) = e^-lambda
         *
         * then recursively derive subsequent terms to avoid
         * repeated factorial calculations.
         */

        $probability =
            exp(
                -$lambda
            );


        $cumulative =
            $probability;


        for (
            $k = 1;
            $k < $threshold;
            $k++
        ) {

            $probability *=
                $lambda
                /
                $k;


            $cumulative +=
                $probability;
        }


        return max(
            0.0,
            min(
                1.0,
                1.0
                -
                $cumulative
            )
        );
    }


    private function emptyModel(
        string $position,
        string $status,
        ?int $threshold = null
    ): array {

        return [

            'position' =>
                $position,

            'status' =>
                $status,

            'metric' =>
                null,

            'threshold' =>
                $threshold,

            'actions_per_90' =>
                null,

            'projected_minutes' =>
                0.0,

            'opponent_attack_rating' =>
                null,

            'opportunity_multiplier' =>
                1.0,

            'expected_actions' =>
                0.0,

            'threshold_probability' =>
                0.0,

            'threshold_probability_percent' =>
                0.0,

            'expected_points' =>
                0.0,
            
            'position_baseline' =>
                null,

            'appearance_sample_size' =>
                0,

            'sample_confidence' =>
                0.0,

            'sample_confidence_percent' =>
                0.0,

            'regressed_actions_per_90' =>
                null,
        ];
    }


    private function nullableNonNegative(
        mixed $value
    ): ?float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return max(
            0.0,
            (float) $value
        );
    }


    private function nullableBounded(
        mixed $value,
        float $minimum,
        float $maximum
    ): ?float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return max(
            $minimum,
            min(
                $maximum,
                (float) $value
            )
        );
    }
}