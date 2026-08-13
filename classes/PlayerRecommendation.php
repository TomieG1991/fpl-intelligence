<?php

class PlayerRecommendation
{
    /**
     * Recommendation thresholds.
     */
    private array $thresholds = [

        'BUY' => 85,

        'HOLD' => 70,

        'WATCH' => 55,

        'AVOID' => 40
    ];


    /**
     * Convert a player intelligence model into the flat
     * decision-friendly structure expected by this class.
     *
     * Supports:
     *
     * 1. New PlayerIntelligenceEngine profiles:
     *
     *    [
     *        'summary' => [
     *            'player_id' => ...,
     *            'intelligence_score' => ...,
     *            ...
     *        ]
     *    ]
     *
     * 2. Existing flat player intelligence arrays.
     */
    private function normalisePlayerIntelligence(
        array $playerIntelligence
    ): array {

        if (
            isset($playerIntelligence['summary'])
            &&
            is_array($playerIntelligence['summary'])
        ) {

            return
                $playerIntelligence['summary'];
        }


        return
            $playerIntelligence;
    }


    /**
     * Calculate a recommendation from the
     * player's intelligence profile.
     *
     * The intelligence score is the primary
     * decision factor.
     *
     * Additional factors can strengthen or
     * weaken the recommendation.
     */
    public function getRecommendation(
        ?float $intelligenceScore,
        ?float $strengthRating = null,
        ?float $valueRating = null,
        ?float $availabilityRating = null,
        ?float $fixtureRating = null
    ): ?string {

        if ($intelligenceScore === null) {
            return null;
        }


        /*
         * Keep the score within the standard
         * intelligence range.
         */
        $score =
            max(
                0,
                min(
                    100,
                    $intelligenceScore
                )
            );


        /*
         * Critical availability issue.
         */
        if (
            $availabilityRating !== null
            &&
            $availabilityRating < 30
        ) {

            return 'SELL';
        }


        /*
         * Very poor overall intelligence.
         */
        if (
            $score < $this->thresholds['AVOID']
        ) {

            return 'SELL';
        }


        /*
         * Strong overall score.
         */
        if (
            $score >= $this->thresholds['BUY']
        ) {

            if (
                $availabilityRating !== null
                &&
                $availabilityRating < 60
            ) {

                return 'WATCH';
            }


            if (
                $fixtureRating !== null
                &&
                $fixtureRating < 40
            ) {

                return 'WATCH';
            }


            return 'BUY';
        }


        /*
         * Good overall player.
         */
        if (
            $score >= $this->thresholds['HOLD']
        ) {

            if (
                $valueRating !== null
                &&
                $valueRating >= 90
                &&
                $strengthRating !== null
                &&
                $strengthRating >= 80
            ) {

                return 'BUY';
            }


            if (
                $availabilityRating !== null
                &&
                $availabilityRating < 60
            ) {

                return 'WATCH';
            }


            return 'HOLD';
        }


        /*
         * Mid-range player.
         */
        if (
            $score >= $this->thresholds['WATCH']
        ) {

            if (
                $strengthRating !== null
                &&
                $strengthRating >= 80
            ) {

                return 'WATCH';
            }


            return 'AVOID';
        }


        return 'AVOID';
    }


    /**
     * Return a human-readable explanation
     * for the recommendation.
     */
    public function getRecommendationReason(
        ?float $intelligenceScore,
        ?float $strengthRating = null,
        ?float $valueRating = null,
        ?float $availabilityRating = null,
        ?float $fixtureRating = null
    ): string {

        $recommendation =
            $this->getRecommendation(
                $intelligenceScore,
                $strengthRating,
                $valueRating,
                $availabilityRating,
                $fixtureRating
            );


        if ($recommendation === null) {
            return 'Insufficient intelligence data';
        }


        if ($recommendation === 'SELL') {

            if (
                $availabilityRating !== null
                &&
                $availabilityRating < 30
            ) {

                return
                    'Very low availability makes the player a sell risk';
            }


            return
                'Overall intelligence profile is too weak';
        }


        if ($recommendation === 'BUY') {

            if (
                $valueRating !== null
                &&
                $valueRating >= 90
            ) {

                return
                    'Excellent overall profile with exceptional value';
            }


            return
                'Strong overall intelligence profile';
        }


        if ($recommendation === 'HOLD') {

            return
                'Strong player profile with no major warning signs';
        }


        if ($recommendation === 'WATCH') {

            if (
                $availabilityRating !== null
                &&
                $availabilityRating < 60
            ) {

                return
                    'Strong player but availability requires monitoring';
            }


            if (
                $fixtureRating !== null
                &&
                $fixtureRating < 40
            ) {

                return
                    'Strong player but upcoming fixtures are a concern';
            }


            return
                'Potentially useful player requiring further monitoring';
        }


        return
            'Overall profile does not currently justify investment';
    }


    /**
     * Build the complete recommendation model.
     *
     * Supports both:
     *
     * - PlayerIntelligenceEngine profiles
     * - Existing flat intelligence arrays
     */
    public function buildRecommendationModel(
        array $playerIntelligence
    ): array {

        $playerIntelligence =
            $this->normalisePlayerIntelligence(
                $playerIntelligence
            );


        $intelligenceScore =
            isset(
                $playerIntelligence['intelligence_score']
            )
                ? (float)
                    $playerIntelligence['intelligence_score']
                : null;


        $strengthRating =
            isset(
                $playerIntelligence['strength_rating']
            )
                ? (float)
                    $playerIntelligence['strength_rating']
                : null;


        $valueRating =
            isset(
                $playerIntelligence['value_rating']
            )
                ? (float)
                    $playerIntelligence['value_rating']
                : null;


        $availabilityRating =
            isset(
                $playerIntelligence['availability_rating']
            )
                ? (float)
                    $playerIntelligence['availability_rating']
                : null;


        $fixtureRating =
            isset(
                $playerIntelligence['fixture_rating']
            )
                ? (float)
                    $playerIntelligence['fixture_rating']
                : null;


        $recommendation =
            $this->getRecommendation(
                $intelligenceScore,
                $strengthRating,
                $valueRating,
                $availabilityRating,
                $fixtureRating
            );


        $reason =
            $this->getRecommendationReason(
                $intelligenceScore,
                $strengthRating,
                $valueRating,
                $availabilityRating,
                $fixtureRating
            );


        return [

            'player_id' =>
                (int) (
                    $playerIntelligence['player_id']
                    ?? 0
                ),

            'name' =>
                $playerIntelligence['name']
                ?? null,

            'position' =>
                $playerIntelligence['position']
                ?? null,

            'intelligence_score' =>
                $intelligenceScore,

            'strength_rating' =>
                $strengthRating,

            'value_rating' =>
                $valueRating,

            'availability_rating' =>
                $availabilityRating,

            'fixture_rating' =>
                $fixtureRating,

            'recommendation' =>
                $recommendation,

            'reason' =>
                $reason
        ];
    }


    /**
     * Return the configured recommendation
     * thresholds.
     */
    public function getThresholds(): array
    {
        return $this->thresholds;
    }
}