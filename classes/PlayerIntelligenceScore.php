<?php

class PlayerIntelligenceScore
{
    /**
     * Weighting used for the overall player intelligence score.
     *
     * Total = 100%
     */
    private array $weights = [

        'strength' => 0.35,

        'value' => 0.25,

        'availability' => 0.20,

        'fixtures' => 0.20
    ];


    /**
     * Return the configured intelligence weights.
     */
    public function getWeights(): array
    {
        return $this->weights;
    }


    /**
     * Calculate the overall player intelligence score.
     *
     * Missing component ratings are excluded and
     * the remaining weights are redistributed
     * proportionally.
     */
    public function calculateScore(
        ?float $strengthRating,
        ?float $valueRating,
        ?float $availabilityRating,
        ?float $fixtureRating
    ): ?float {

        $ratings = [

            'strength' =>
                $strengthRating,

            'value' =>
                $valueRating,

            'availability' =>
                $availabilityRating,

            'fixtures' =>
                $fixtureRating
        ];


        $weightedTotal =
            0.0;


        $weightTotal =
            0.0;


        foreach (
            $ratings
            as $component => $rating
        ) {

            if (
                $rating === null
                ||
                !is_numeric($rating)
            ) {

                continue;
            }


            /*
             * Every intelligence component uses
             * the standard 0-100 scale.
             */
            $rating =
                max(
                    0,
                    min(
                        100,
                        (float) $rating
                    )
                );


            $weight =
                $this->weights[$component]
                ?? 0;


            if ($weight <= 0) {
                continue;
            }


            $weightedTotal +=
                $rating
                *
                $weight;


            $weightTotal +=
                $weight;
        }


        /*
         * No usable intelligence components.
         */
        if ($weightTotal <= 0) {
            return null;
        }


        /*
         * Redistribute available weighting
         * proportionally when components are missing.
         */
        $score =
            $weightedTotal
            /
            $weightTotal;


        return round(
            max(
                0,
                min(
                    100,
                    $score
                )
            ),
            2
        );
    }


    /**
     * Convert an intelligence score into
     * a human-readable label.
     */
    public function getLabel(
        ?float $score
    ): string {

        if ($score === null) {
            return 'Unknown';
        }


        $score =
            max(
                0,
                min(
                    100,
                    $score
                )
            );


        if ($score >= 85) {
            return 'Elite';
        }


        if ($score >= 70) {
            return 'Strong';
        }


        if ($score >= 55) {
            return 'Average';
        }


        if ($score >= 40) {
            return 'Below Average';
        }


        return 'Weak';
    }


    /**
     * Build the complete player intelligence score model.
     */
    public function buildModel(
        array $playerStrength,
        array $playerValue,
        array $playerAvailability,
        ?float $fixtureRating
    ): array {

        $strengthRating =
            $this->getNullableRating(
                $playerStrength,
                'strength_rating'
            );


        $valueRating =
            $this->getNullableRating(
                $playerValue,
                'value_rating'
            );


        $availabilityRating =
            $this->getNullableRating(
                $playerAvailability,
                'availability_rating'
            );


        $fixtureRating =
            $fixtureRating !== null
                ? max(
                    0,
                    min(
                        100,
                        $fixtureRating
                    )
                )
                : null;


        $score =
            $this->calculateScore(
                $strengthRating,
                $valueRating,
                $availabilityRating,
                $fixtureRating
            );


        return [

            'player_id' =>
                (int) (
                    $playerStrength['player_id']
                    ??
                    $playerValue['player_id']
                    ??
                    $playerAvailability['player_id']
                    ??
                    0
                ),

            'name' =>
                $playerStrength['name']
                ??
                $playerValue['name']
                ??
                $playerAvailability['name']
                ??
                null,

            'position' =>
                $playerStrength['position']
                ??
                $playerValue['position']
                ??
                $playerAvailability['position']
                ??
                null,

            'strength_rating' =>
                $strengthRating,

            'value_rating' =>
                $valueRating,

            'availability_rating' =>
                $availabilityRating,

            'fixture_rating' =>
                $fixtureRating,

            'intelligence_score' =>
                $score,

            'intelligence_label' =>
                $this->getLabel(
                    $score
                )
        ];
    }


    /**
     * Read a nullable 0-100 rating
     * from an intelligence model.
     */
    private function getNullableRating(
        array $model,
        string $field
    ): ?float {

        if (
            !isset($model[$field])
            ||
            !is_numeric(
                $model[$field]
            )
        ) {

            return null;
        }


        return max(
            0,
            min(
                100,
                (float)
                    $model[$field]
            )
        );
    }
}