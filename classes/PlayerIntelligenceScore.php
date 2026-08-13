<?php

class PlayerIntelligenceScore
{
    /**
     * Core weighting used for overall player intelligence.
     *
     * Player quality is the primary factor.
     * Upcoming fixture opportunity provides the
     * short-term FPL context.
     */
    private array $weights = [

        'strength' => 0.65,

        'fixtures' => 0.35
    ];


    /**
     * Return the configured intelligence weights.
     */
    public function getWeights(): array
    {
        return $this->weights;
    }


    /**
     * Convert availability into a risk multiplier.
     *
     * Availability should not reward a healthy player
     * with additional intelligence points.
     *
     * Instead:
     *
     * fully available = no penalty
     * doubtful/injured = progressively larger penalty
     */
    public function calculateAvailabilityMultiplier(
        ?float $availabilityRating
    ): float {

        /*
         * Missing availability information should not
         * automatically penalise the player.
         */
        if ($availabilityRating === null) {
            return 1.00;
        }


        $availabilityRating =
            max(
                0,
                min(
                    100,
                    $availabilityRating
                )
            );


        if ($availabilityRating >= 90) {
            return 1.00;
        }


        if ($availabilityRating >= 75) {
            return 0.95;
        }


        if ($availabilityRating >= 50) {
            return 0.85;
        }


        if ($availabilityRating >= 25) {
            return 0.60;
        }


        if ($availabilityRating > 0) {
            return 0.35;
        }


        return 0.10;
    }


    /**
     * Calculate the core intelligence score.
     *
     * Strength = 65%
     * Fixtures = 35%
     *
     * Missing components redistribute the available
     * weighting proportionally.
     */
    public function calculateCoreScore(
        ?float $strengthRating,
        ?float $fixtureRating
    ): ?float {
        /*
         * Player strength is the foundation of the
         * overall intelligence score.
         *
         * Fixture opportunity alone is not enough to
         * establish that a player is a viable FPL option.
         */
        if ($strengthRating === null) {
            return null;
        }

        $ratings = [

            'strength' =>
                $strengthRating,

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


            $rating =
                max(
                    0,
                    min(
                        100,
                        (float) $rating
                    )
                );


            $weight =
                $this->weights[$component];


            $weightedTotal +=
                $rating
                *
                $weight;


            $weightTotal +=
                $weight;
        }


        if ($weightTotal <= 0) {
            return null;
        }


        return round(
            max(
                0,
                min(
                    100,
                    $weightedTotal
                    /
                    $weightTotal
                )
            ),
            2
        );
    }


    /**
     * Calculate the final player intelligence score.
     *
     * Value is deliberately NOT included here.
     *
     * Value remains available as a separate decision metric
     * for value picks, transfers and squad optimisation.
     *
     * Availability acts as a risk modifier rather than
     * contributing free intelligence points.
     */
    public function calculateScore(
        ?float $strengthRating,
        ?float $valueRating,
        ?float $availabilityRating,
        ?float $fixtureRating
    ): ?float {

        $coreScore =
            $this->calculateCoreScore(
                $strengthRating,
                $fixtureRating
            );


        if ($coreScore === null) {
            return null;
        }


        $availabilityMultiplier =
            $this->calculateAvailabilityMultiplier(
                $availabilityRating
            );


        return round(
            max(
                0,
                min(
                    100,
                    $coreScore
                    *
                    $availabilityMultiplier
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
     * Build the complete player intelligence model.
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


        /*
         * Value remains in the output model even though
         * it is no longer part of the overall intelligence
         * score calculation.
         */
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


        $coreScore =
            $this->calculateCoreScore(
                $strengthRating,
                $fixtureRating
            );


        $availabilityMultiplier =
            $this->calculateAvailabilityMultiplier(
                $availabilityRating
            );


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

            'core_score' =>
                $coreScore,

            'availability_multiplier' =>
                $availabilityMultiplier,

            'intelligence_score' =>
                $score,

            'intelligence_label' =>
                $this->getLabel(
                    $score
                )
        ];
    }


    /**
     * Read a nullable 0-100 rating.
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