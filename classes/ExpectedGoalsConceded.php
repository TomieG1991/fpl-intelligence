<?php

class ExpectedGoalsConceded
{
    /**
     * Project expected FPL goals-conceded deductions.
     *
     * This model is applicable only to:
     *
     * - GK
     * - DEF
     *
     * Official FPL scoring:
     *
     * -1 point for every two goals conceded.
     *
     * Because projected goals conceded is an expectation rather
     * than a known realised score, the deduction is calculated
     * probabilistically using a Poisson distribution.
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


        if (
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
        ) {

            return $this->emptyModel(
                $position,
                'Unsupported Position'
            );
        }


        if (
            !in_array(
                $position,
                [
                    'GK',
                    'DEF'
                ],
                true
            )
        ) {

            return $this->emptyModel(
                $position,
                'Not Applicable'
            );
        }


        $projectedMinutes =
            max(
                0.0,
                min(
                    90.0,
                    $projectedMinutes
                )
            );


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


        $rawXgcPer90 =
            $this->nullableNonNegative(
                $weightedMetrics[
                    'expected_goals_conceded_per_90'
                ]
                ?? null
            );


        if (
            $rawXgcPer90 === null
        ) {

            return $this->emptyModel(
                $position,
                'Insufficient Data'
            );
        }


        /*
         * --------------------------------------------------------
         * POSITION BASELINES
         * --------------------------------------------------------
         *
         * Derived from complete GW1 2026/27 players with at least
         * 60 minutes:
         *
         * GK  median xGC / 90 = 1.46
         * DEF median xGC / 90 = 1.33
         */

        $positionBaseline =
            match (
                $position
            ) {

                'GK' =>
                    1.46,

                'DEF' =>
                    1.33
            };


        /*
         * --------------------------------------------------------
         * EARLY-SAMPLE REGRESSION
         * --------------------------------------------------------
         *
         * Five appearances represent full player-specific
         * confidence for this initial model.
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


        $regressedXgcPer90 =
            (
                $rawXgcPer90
                *
                $sampleConfidence
            )
            +
            (
                $positionBaseline
                *
                (
                    1.0
                    -
                    $sampleConfidence
                )
            );


        /*
         * --------------------------------------------------------
         * FIXTURE ADJUSTMENT
         * --------------------------------------------------------
         *
         * Stronger opponent attacks increase expected goals
         * conceded.
         *
         * 0   -> 0.75
         * 50  -> 1.00
         * 100 -> 1.25
         */

        $opponentAttackRating =
            $this->nullableBounded(
                $fixtureContext[
                    'opponent_attack_rating'
                ]
                ?? null,
                0.0,
                100.0
            );


        $fixtureOpportunity =
            $this->nullableBounded(
                $fixtureContext[
                    'fixture_opportunity'
                ]
                ?? null,
                0.0,
                100.0
            );


        /*
         * Convert player-perspective fixture opportunity into
         * defensive threat.
         *
         * 100 opportunity = 0 defensive threat
         *   0 opportunity = 100 defensive threat
         */
        $fixtureDefensiveThreat =
            $fixtureOpportunity !== null
                ? 100.0
                    -
                    $fixtureOpportunity
                : null;


        /*
         * Blend specialist opponent attacking strength with the
         * broader fixture context.
         *
         * Opponent Attack Rating remains the dominant signal.
         */
        if (
            $opponentAttackRating !== null
            &&
            $fixtureDefensiveThreat !== null
        ) {

            $defensiveThreatRating =
                (
                    $opponentAttackRating
                    *
                    0.75
                )
                +
                (
                    $fixtureDefensiveThreat
                    *
                    0.25
                );

        } elseif (
            $opponentAttackRating !== null
        ) {

            $defensiveThreatRating =
                $opponentAttackRating;

        } elseif (
            $fixtureDefensiveThreat !== null
        ) {

            $defensiveThreatRating =
                $fixtureDefensiveThreat;

        } else {

            $defensiveThreatRating =
                null;
        }


        $fixtureMultiplier =
            $defensiveThreatRating !== null
                ? 0.75
                    +
                    (
                        $defensiveThreatRating
                        /
                        200
                    )
                : 1.0;


        /*
         * --------------------------------------------------------
         * PROJECTED XGC
         * --------------------------------------------------------
         */

        $projectedXgc =
            $regressedXgcPer90
            *
            (
                $projectedMinutes
                /
                90
            )
            *
            $fixtureMultiplier;


        $projectedXgc =
            max(
                0.0,
                $projectedXgc
            );


        /*
         * --------------------------------------------------------
         * EXPECTED FPL DEDUCTION
         * --------------------------------------------------------
         *
         * FPL deducts:
         *
         * 0-1 conceded ->  0
         * 2-3 conceded -> -1
         * 4-5 conceded -> -2
         * 6-7 conceded -> -3
         * etc.
         *
         * We model realised goals conceded as:
         *
         * Poisson(lambda = projected xGC)
         *
         * and calculate:
         *
         * E[floor(goals / 2)]
         */

        $expectedDeductionMagnitude =
            $this->expectedDeductionMagnitude(
                $projectedXgc
            );


        $expectedPoints =
            -1.0
            *
            $expectedDeductionMagnitude;


        return [

            'position' =>
                $position,

            'status' =>
                'Modelled',

            'raw_xgc_per_90' =>
                round(
                    $rawXgcPer90,
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

            'regressed_xgc_per_90' =>
                round(
                    $regressedXgcPer90,
                    4
                ),

            'opponent_attack_rating' =>
                $opponentAttackRating,

            'fixture_opportunity' =>
                $fixtureOpportunity,

            'defensive_threat_rating' =>
                $defensiveThreatRating,

            'fixture_multiplier' =>
                round(
                    $fixtureMultiplier,
                    4
                ),

            'projected_minutes' =>
                round(
                    $projectedMinutes,
                    2
                ),

            'projected_xgc' =>
                round(
                    $projectedXgc,
                    4
                ),

            'expected_deduction_magnitude' =>
                round(
                    $expectedDeductionMagnitude,
                    4
                ),

            'expected_points' =>
                round(
                    $expectedPoints,
                    4
                )
        ];
    }


    /**
     * Calculate the expected number of FPL goals-conceded
     * deduction units.
     *
     * Each deduction unit represents -1 FPL point.
     */
    public function expectedDeductionMagnitude(
        float $lambda
    ): float {

        $lambda =
            max(
                0.0,
                $lambda
            );


        if (
            $lambda <= 0.000001
        ) {

            return 0.0;
        }


        /*
         * Summing to 20 conceded goals is more than sufficient
         * for the realistic xGC range used by this application.
         *
         * Any remaining Poisson tail is negligible.
         */

        $expected =
            0.0;


        $probability =
            exp(
                -$lambda
            );


        for (
            $goals = 0;
            $goals <= 20;
            $goals++
        ) {

            if (
                $goals > 0
            ) {

                $probability *=
                    $lambda
                    /
                    $goals;
            }


            $deductionUnits =
                intdiv(
                    $goals,
                    2
                );


            $expected +=
                $probability
                *
                $deductionUnits;
        }


        return max(
            0.0,
            $expected
        );
    }


    private function emptyModel(
        string $position,
        string $status
    ): array {

        return [

            'position' =>
                $position,

            'status' =>
                $status,

            'raw_xgc_per_90' =>
                null,

            'position_baseline' =>
                null,

            'appearance_sample_size' =>
                0,

            'sample_confidence' =>
                0.0,

            'sample_confidence_percent' =>
                0.0,

            'regressed_xgc_per_90' =>
                null,

            'opponent_attack_rating' =>
                null,

            'fixture_multiplier' =>
                1.0,

            'projected_minutes' =>
                0.0,

            'projected_xgc' =>
                0.0,

            'expected_deduction_magnitude' =>
                0.0,

            'expected_points' =>
                0.0
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