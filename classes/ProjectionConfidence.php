<?php

class ProjectionConfidence
{
    /**
     * Calculate confidence in an Expected Points projection.
     *
     * Projection Confidence answers:
     *
     * "How much evidence supports this projection?"
     *
     * It remains separate from:
     *
     * - Sample Confidence
     * - Effective Confidence
     * - Player Form
     *
     * Components:
     *
     * 40% Historical Sample
     * 35% Participation Stability
     * 25% Availability Certainty
     */
    public function calculate(
        array $expectedMinutes,
        array $form = []
    ): array {

        /*
         * --------------------------------------------------------
         * HISTORICAL SAMPLE CONFIDENCE
         * --------------------------------------------------------
         *
         * Five recent fixtures represents the complete intended
         * short-term projection window.
         */

        $fixtureSampleSize =
            max(
                0,
                (int) (
                    $expectedMinutes[
                        'fixture_sample_size'
                    ]
                    ??
                    $form[
                        'fixture_sample_size'
                    ]
                    ??
                    0
                )
            );


        $appearanceSampleSize =
            max(
                0,
                (int) (
                    $expectedMinutes[
                        'appearance_sample_size'
                    ]
                    ??
                    $form[
                        'appearance_sample_size'
                    ]
                    ??
                    0
                )
            );


        $fixtureSampleConfidence =
            min(
                1.0,
                $fixtureSampleSize
                /
                5
            );


        /*
         * Appearance evidence also matters, but a player who has
         * genuine zero-minute records must not receive artificial
         * confidence simply because five fixtures exist.
         */
        $appearanceSampleConfidence =
            min(
                1.0,
                $appearanceSampleSize
                /
                5
            );


        $historicalSampleConfidence =
            (
                $fixtureSampleConfidence
                *
                0.60
            )
            +
            (
                $appearanceSampleConfidence
                *
                0.40
            );


        /*
         * --------------------------------------------------------
         * PARTICIPATION STABILITY
         * --------------------------------------------------------
         *
         * Participation close to either extreme is easier to
         * project than inconsistent involvement.
         *
         * Examples:
         *
         * 100% participation = highly stable starter
         *   0% participation = highly stable non-participant
         *  50% participation = uncertain / rotating
         *
         * Projection confidence measures predictability rather
         * than desirability.
         */

        $participationRate =
            $this->normalisePercentage(
                $expectedMinutes[
                    'participation_rate'
                ]
                ??
                $form[
                    'participation_rate'
                ]
                ??
                null
            );


        if (
            $participationRate === null
        ) {

            $participationStability =
                0.50;

        } else {

            $distanceFromMidpoint =
                abs(
                    $participationRate
                    -
                    50
                );


            $participationStability =
                $distanceFromMidpoint
                /
                50;
        }


        /*
         * --------------------------------------------------------
         * AVAILABILITY CERTAINTY
         * --------------------------------------------------------
         *
         * 100% and 0% availability are both highly certain.
         *
         * A player with a 50% chance of playing is maximally
         * uncertain.
         */

        $chanceOfPlaying =
            $this->normalisePercentage(
                $expectedMinutes[
                    'chance_of_playing'
                ]
                ?? null
            );


        if (
            $chanceOfPlaying === null
        ) {

            $availabilityCertainty =
                0.50;

        } else {

            $availabilityCertainty =
                abs(
                    $chanceOfPlaying
                    -
                    50
                )
                /
                50;
        }


        /*
         * --------------------------------------------------------
         * FINAL PROJECTION CONFIDENCE
         * --------------------------------------------------------
         */

        $confidence =
            (
                $historicalSampleConfidence
                *
                0.40
            )
            +
            (
                $participationStability
                *
                0.35
            )
            +
            (
                $availabilityCertainty
                *
                0.25
            );


        $confidence =
            max(
                0.0,
                min(
                    1.0,
                    $confidence
                )
            );


        $confidencePercent =
            round(
                $confidence
                *
                100,
                2
            );


        return [

            'confidence' =>
                round(
                    $confidence,
                    4
                ),

            'confidence_percent' =>
                $confidencePercent,

            'confidence_label' =>
                $this->classify(
                    $confidencePercent
                ),

            'components' => [

                'historical_sample' =>
                    round(
                        $historicalSampleConfidence
                        *
                        100,
                        2
                    ),

                'participation_stability' =>
                    round(
                        $participationStability
                        *
                        100,
                        2
                    ),

                'availability_certainty' =>
                    round(
                        $availabilityCertainty
                        *
                        100,
                        2
                    )
            ],

            'fixture_sample_size' =>
                $fixtureSampleSize,

            'appearance_sample_size' =>
                $appearanceSampleSize
        ];
    }


    /**
     * Convert numeric projection confidence into a human-readable
     * classification.
     */
    public function classify(
        float $confidencePercent
    ): string {

        $confidencePercent =
            max(
                0.0,
                min(
                    100.0,
                    $confidencePercent
                )
            );


        if (
            $confidencePercent >= 80
        ) {

            return 'High';
        }


        if (
            $confidencePercent >= 60
        ) {

            return 'Moderate';
        }


        if (
            $confidencePercent >= 40
        ) {

            return 'Low';
        }


        return 'Very Low';
    }


    /**
     * Normalise percentage values.
     */
    private function normalisePercentage(
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
            min(
                100.0,
                (float) $value
            )
        );
    }
}