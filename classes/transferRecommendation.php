<?php

class TransferRecommendation
{
    /**
     * Weighting used when comparing a current player
     * against a potential replacement.
     *
     * Total = 100%
     */
    private array $weights = [

        'intelligence' => 0.35,

        'strength' => 0.20,

        'value' => 0.15,

        'availability' => 0.15,

        'fixtures' => 0.15
    ];


    /**
     * Return the transfer comparison weights.
     */
    public function getWeights(): array
    {
        return $this->weights;
    }


    /**
     * Calculate the improvement in a component.
     *
     * Positive = replacement is better.
     * Negative = replacement is worse.
     */
    public function calculateDifference(
        ?float $currentRating,
        ?float $replacementRating
    ): ?float {

        if (
            $currentRating === null
            ||
            $replacementRating === null
        ) {
            return null;
        }


        return round(
            $replacementRating
            - $currentRating,
            2
        );
    }


    /**
     * Calculate the weighted transfer score.
     *
     * The score represents the improvement offered
     * by the replacement player.
     *
     * Positive score = improvement.
     * Negative score = downgrade.
     */
    public function calculateTransferScore(
        ?float $currentIntelligence,
        ?float $replacementIntelligence,
        ?float $currentStrength,
        ?float $replacementStrength,
        ?float $currentValue,
        ?float $replacementValue,
        ?float $currentAvailability,
        ?float $replacementAvailability,
        ?float $currentFixtures,
        ?float $replacementFixtures
    ): ?float {

        $components = [

            'intelligence' => [
                $currentIntelligence,
                $replacementIntelligence
            ],

            'strength' => [
                $currentStrength,
                $replacementStrength
            ],

            'value' => [
                $currentValue,
                $replacementValue
            ],

            'availability' => [
                $currentAvailability,
                $replacementAvailability
            ],

            'fixtures' => [
                $currentFixtures,
                $replacementFixtures
            ]
        ];


        $weightedDifference = 0.0;

        $weightTotal = 0.0;


        foreach ($components as $component => $ratings) {

            [
                $current,
                $replacement
            ] = $ratings;


            if (
                $current === null
                ||
                $replacement === null
            ) {
                continue;
            }


            $difference =
                $replacement
                - $current;


            $weight =
                $this->weights[$component];


            $weightedDifference +=
                $difference * $weight;

            $weightTotal +=
                $weight;
        }


        if ($weightTotal <= 0) {
            return null;
        }


        return round(
            $weightedDifference / $weightTotal,
            2
        );
    }


    /**
     * Convert a transfer score into a recommendation.
     */
    public function getRecommendation(
        ?float $transferScore
    ): ?string {

        if ($transferScore === null) {
            return null;
        }


        if ($transferScore >= 15) {
            return 'STRONG TRANSFER';
        }


        if ($transferScore >= 7.5) {
            return 'TRANSFER';
        }


        if ($transferScore >= 2.5) {
            return 'CONSIDER';
        }


        if ($transferScore > -2.5) {
            return 'HOLD';
        }


        if ($transferScore > -7.5) {
            return 'WEAK TRANSFER';
        }


        return 'AVOID';
    }


    /**
     * Generate a human-readable explanation.
     */
    public function getReason(
        ?float $transferScore,
        ?float $intelligenceDifference,
        ?float $strengthDifference,
        ?float $valueDifference,
        ?float $availabilityDifference,
        ?float $fixtureDifference
    ): string {

        if ($transferScore === null) {
            return 'Insufficient data to assess the transfer';
        }


        if ($transferScore >= 15) {

            return
                'Replacement player provides a major overall improvement';

        }


        if ($transferScore >= 7.5) {

            return
                'Replacement player provides a strong overall improvement';

        }


        if ($transferScore >= 2.5) {

            return
                'Replacement player provides a modest overall improvement';

        }


        if ($transferScore > -2.5) {

            return
                'Replacement player offers no significant overall improvement';

        }


        if ($transferScore > -7.5) {

            return
                'Replacement player provides a weaker overall profile';

        }


        return
            'Replacement player is significantly weaker overall';
    }


    /**
     * Build a complete transfer recommendation model.
     */
    public function buildRecommendation(
        array $currentPlayer,
        array $replacementPlayer
    ): array {

        $currentIntelligence =
            isset($currentPlayer['intelligence_score'])
                ? (float) $currentPlayer['intelligence_score']
                : null;

        $replacementIntelligence =
            isset($replacementPlayer['intelligence_score'])
                ? (float) $replacementPlayer['intelligence_score']
                : null;


        $currentStrength =
            isset($currentPlayer['strength_rating'])
                ? (float) $currentPlayer['strength_rating']
                : null;

        $replacementStrength =
            isset($replacementPlayer['strength_rating'])
                ? (float) $replacementPlayer['strength_rating']
                : null;


        $currentValue =
            isset($currentPlayer['value_rating'])
                ? (float) $currentPlayer['value_rating']
                : null;

        $replacementValue =
            isset($replacementPlayer['value_rating'])
                ? (float) $replacementPlayer['value_rating']
                : null;


        $currentAvailability =
            isset($currentPlayer['availability_rating'])
                ? (float) $currentPlayer['availability_rating']
                : null;

        $replacementAvailability =
            isset($replacementPlayer['availability_rating'])
                ? (float) $replacementPlayer['availability_rating']
                : null;


        $currentFixtures =
            isset($currentPlayer['fixture_rating'])
                ? (float) $currentPlayer['fixture_rating']
                : null;

        $replacementFixtures =
            isset($replacementPlayer['fixture_rating'])
                ? (float) $replacementPlayer['fixture_rating']
                : null;


        $intelligenceDifference =
            $this->calculateDifference(
                $currentIntelligence,
                $replacementIntelligence
            );


        $strengthDifference =
            $this->calculateDifference(
                $currentStrength,
                $replacementStrength
            );


        $valueDifference =
            $this->calculateDifference(
                $currentValue,
                $replacementValue
            );


        $availabilityDifference =
            $this->calculateDifference(
                $currentAvailability,
                $replacementAvailability
            );


        $fixtureDifference =
            $this->calculateDifference(
                $currentFixtures,
                $replacementFixtures
            );


        $transferScore =
            $this->calculateTransferScore(
                $currentIntelligence,
                $replacementIntelligence,
                $currentStrength,
                $replacementStrength,
                $currentValue,
                $replacementValue,
                $currentAvailability,
                $replacementAvailability,
                $currentFixtures,
                $replacementFixtures
            );


        return [

            'current_player_id' =>
                (int) (
                    $currentPlayer['player_id']
                    ?? 0
                ),

            'current_player_name' =>
                $currentPlayer['name']
                ?? null,

            'replacement_player_id' =>
                (int) (
                    $replacementPlayer['player_id']
                    ?? 0
                ),

            'replacement_player_name' =>
                $replacementPlayer['name']
                ?? null,

            'intelligence_difference' =>
                $intelligenceDifference,

            'strength_difference' =>
                $strengthDifference,

            'value_difference' =>
                $valueDifference,

            'availability_difference' =>
                $availabilityDifference,

            'fixture_difference' =>
                $fixtureDifference,

            'transfer_score' =>
                $transferScore,

            'recommendation' =>
                $this->getRecommendation(
                    $transferScore
                ),

            'reason' =>
                $this->getReason(
                    $transferScore,
                    $intelligenceDifference,
                    $strengthDifference,
                    $valueDifference,
                    $availabilityDifference,
                    $fixtureDifference
                )
        ];
    }
}