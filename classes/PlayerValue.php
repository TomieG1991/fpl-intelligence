<?php

class PlayerValue
{
    /**
     * Calculate player strength per £1m.
     */
    public function calculateStrengthPerMillion(
        ?float $strengthRating,
        ?float $price
    ): ?float {

        if (
            $strengthRating === null
            ||
            $price === null
            ||
            $price <= 0
        ) {
            return null;
        }

        return round(
            $strengthRating / $price,
            2
        );
    }


    /**
     * Calculate value rating against a benchmark.
     *
     * The benchmark represents the strength-per-million
     * considered to be a strong FPL value.
     */
    public function calculateValueRating(
        ?float $strengthPerMillion,
        float $benchmark = 15.0
    ): ?float {

        if (
            $strengthPerMillion === null
            ||
            $benchmark <= 0
        ) {
            return null;
        }

        $rating =
            (
                $strengthPerMillion
                / $benchmark
            ) * 100;

        return round(
            min(100, max(0, $rating)),
            2
        );
    }


    /**
     * Convert value rating into a human-readable label.
     */
    public function getValueLabel(
        ?float $valueRating
    ): string {

        if ($valueRating === null) {
            return 'N/A';
        }

        if ($valueRating >= 90) {
            return 'Exceptional';
        }

        if ($valueRating >= 75) {
            return 'Excellent';
        }

        if ($valueRating >= 60) {
            return 'Good';
        }

        if ($valueRating >= 40) {
            return 'Average';
        }

        if ($valueRating >= 20) {
            return 'Poor';
        }

        return 'Very Poor';
    }


    /**
     * Build the complete player value model.
     */
    public function buildValueModel(
        array $playerStrength,
        array $playerPerformance
    ): array {

        $strengthRating =
            isset($playerStrength['strength_rating'])
                ? (float) $playerStrength['strength_rating']
                : null;

        $price =
            isset($playerPerformance['price'])
                ? (float) $playerPerformance['price']
                : null;

        $strengthPerMillion =
            $this->calculateStrengthPerMillion(
                $strengthRating,
                $price
            );

        $valueRating =
            $this->calculateValueRating(
                $strengthPerMillion
            );

        return [

            'player_id' =>
                (int) ($playerStrength['player_id'] ?? 0),

            'name' =>
                $playerStrength['name'] ?? null,

            'position' =>
                $playerStrength['position'] ?? null,

            'price' =>
                $price,

            'strength_rating' =>
                $strengthRating,

            'strength_per_million' =>
                $strengthPerMillion,

            'value_rating' =>
                $valueRating,

            'value_label' =>
                $this->getValueLabel(
                    $valueRating
                )
        ];
    }
}