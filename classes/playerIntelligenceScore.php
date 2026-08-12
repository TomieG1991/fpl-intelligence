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
     * All component ratings are expected to use
     * the standard 0-100 scale.
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


        $weightedTotal = 0.0;

        $weightTotal = 0.0;


        foreach ($ratings as $component => $rating) {

            if ($rating === null) {
                continue;
            }


            $rating =
                max(
                    0,
                    min(
                        100,
                        $rating
                    )
                );


            $weight =
                $this->weights[$component];


            $weightedTotal +=
                $rating * $weight;

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
         * Redistribute the available weighting
         * proportionally when one or more components
         * are unavailable.
         */
        $score =
            $weightedTotal
            / $weightTotal;


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
     * Convert an intelligence score into a
     * human-readable label.
     */
    public function getLabel(
        ?float $score
    ): string {

        if ($score === null) {
            return 'Unknown';
        }


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
            isset(
                $playerStrength['strength_rating']
            )
                ? (float) $playerStrength['strength_rating']
                : null;


        $valueRating =
            isset(
                $playerValue['value_rating']
            )
                ? (float) $playerValue['value_rating']
                : null;


        $availabilityRating =
            isset(
                $playerAvailability['availability_rating']
            )
                ? (float) $playerAvailability['availability_rating']
                : null;


        $fixtureRating =
            $fixtureRating !== null
                ? (float) $fixtureRating
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
                    ?? $playerValue['player_id']
                    ?? $playerAvailability['player_id']
                    ?? 0
                ),

            'name' =>
                $playerStrength['name']
                ?? $playerValue['name']
                ?? $playerAvailability['name']
                ?? null,

            'position' =>
                $playerStrength['position']
                ?? $playerValue['position']
                ?? $playerAvailability['position']
                ?? null,

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
}