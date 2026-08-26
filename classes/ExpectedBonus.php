<?php

class ExpectedBonus
{
    /**
     * Project expected FPL bonus points.
     *
     * The model has three stages:
     *
     * 1. Regress recent BPS / 90 toward a position baseline.
     * 2. Scale the regressed BPS rate by projected minutes.
     * 3. Convert projected BPS into expected bonus using a
     *    monotonic empirical curve derived from complete GW1
     *    2026/27 data.
     *
     * Bonus remains inherently fixture-relative, so this is an
     * expected-value model rather than a prediction of an exact
     * future bonus award.
     */
    public function calculate(
        string $position,
        float $projectedMinutes,
        array $form
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


        $bpsPer90 =
            $this->nullableNumeric(
                $weightedMetrics[
                    'bps_per_90'
                ]
                ?? null
            );


        if (
            $bpsPer90 === null
        ) {

            return $this->emptyModel(
                $position,
                'Insufficient Data'
            );
        }


        /*
         * --------------------------------------------------------
         * POSITION BASELINE
         * --------------------------------------------------------
         *
         * Complete GW1 2026/27 BPS / 90 medians among players
         * with at least 60 minutes.
         */

        $positionBaseline =
            match (
                $position
            ) {

                'GK' =>
                    12.0,

                'DEF' =>
                    11.0,

                'MID' =>
                    17.0,

                'FWD' =>
                    9.13
            };


        /*
         * --------------------------------------------------------
         * SAMPLE REGRESSION
         * --------------------------------------------------------
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


        $regressedBpsPer90 =
            (
                $bpsPer90
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


        /*
         * BPS itself can be negative historically, but negative
         * projected BPS has no useful meaning for expected bonus.
         */

        $projectedBps =
            max(
                0.0,
                $regressedBpsPer90
                *
                (
                    $projectedMinutes
                    /
                    90
                )
            );


        /*
         * --------------------------------------------------------
         * EMPIRICAL BONUS CURVE
         * --------------------------------------------------------
         */

        $expectedBonus =
            $this->expectedBonusFromBps(
                $projectedBps
            );


        return [

            'position' =>
                $position,

            'status' =>
                'Modelled',

            'bps_per_90' =>
                round(
                    $bpsPer90,
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

            'regressed_bps_per_90' =>
                round(
                    $regressedBpsPer90,
                    4
                ),

            'projected_minutes' =>
                round(
                    $projectedMinutes,
                    2
                ),

            'projected_bps' =>
                round(
                    $projectedBps,
                    4
                ),

            'expected_points' =>
                round(
                    $expectedBonus,
                    4
                )
        ];
    }


    /**
     * Convert projected BPS into expected FPL bonus.
     *
     * Bonus is fixture-relative, so projected BPS should not be
     * treated as a deterministic realised BPS score.
     *
     * This logistic expected-value curve is calibrated from the
     * complete GW1 2026/27 BPS-to-bonus relationship.
     *
     * It provides:
     *
     * - small but non-zero upside below historical bonus cutoffs
     * - smooth monotonic growth
     * - no noisy reversals from small exact-BPS samples
     * - a natural asymptotic ceiling at three bonus points
     */
    public function expectedBonusFromBps(
        float $bps
    ): float {

        $bps =
            max(
                0.0,
                $bps
            );


        /*
         * Weighted logistic calibration from complete GW1
         * 2026/27 player-fixture data.
         *
         * Expected Bonus =
         *
         * 3
         * /
         * (
         *     1
         *     +
         *     exp(
         *         -0.2333
         *         *
         *         (
         *             BPS
         *             -
         *             35.5712
         *         )
         *     )
         * )
         */

        $expectedBonus =
            3.0
            /
            (
                1.0
                +
                exp(
                    -0.2333
                    *
                    (
                        $bps
                        -
                        35.5712
                    )
                )
            );


        return max(
            0.0,
            min(
                3.0,
                $expectedBonus
            )
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

            'bps_per_90' =>
                null,

            'position_baseline' =>
                null,

            'appearance_sample_size' =>
                0,

            'sample_confidence' =>
                0.0,

            'sample_confidence_percent' =>
                0.0,

            'regressed_bps_per_90' =>
                null,

            'projected_minutes' =>
                0.0,

            'projected_bps' =>
                0.0,

            'expected_points' =>
                0.0
        ];
    }


    private function nullableNumeric(
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


        return (float) $value;
    }
}