<?php

class PlayerAvailability
{
    /**
     * Calculate an availability rating from FPL
     * chance-of-playing information.
     *
     * 100 = fully available
     * 0   = unavailable
     */
    public function calculateAvailabilityRating(
        ?int $chanceOfPlaying,
        ?string $status
    ): ?float {

        /*
         * If FPL provides a specific chance of playing,
         * use that as the primary availability indicator.
         */
        if ($chanceOfPlaying !== null) {

            return round(
                max(
                    0,
                    min(
                        100,
                        $chanceOfPlaying
                    )
                ),
                2
            );
        }


        /*
         * If no explicit chance is available,
         * use the player's FPL status.
         */
        if ($status === null) {
            return null;
        }


        return match (strtolower($status)) {

            'a' => 100.00,

            'd' => 50.00,

            'i' => 25.00,

            's' => 0.00,

            'u' => 0.00,

            default => null
        };
    }


    /**
     * Calculate an adjusted availability rating
     * using both availability and playing history.
     *
     * Players with no minutes retain their availability
     * rating, because zero minutes does not automatically
     * mean the player is unavailable.
     */
    public function calculateReliabilityRating(
        ?float $availabilityRating,
        int $minutes
    ): ?float {

        if ($availabilityRating === null) {
            return null;
        }


        if ($minutes <= 0) {
            return round(
                $availabilityRating,
                2
            );
        }


        /*
         * Playing minutes provide a small reliability
         * confirmation rather than replacing the FPL
         * availability rating.
         *
         * 1,000 minutes or more represents a fully
         * established playing sample for this model.
         */
        $minutesFactor =
            min(
                1,
                $minutes / 1000
            );


        /*
         * 80% of the rating comes from current availability.
         * 20% comes from demonstrated playing involvement.
         */
        $reliability =
            (
                $availabilityRating * 0.80
            )
            +
            (
                ($availabilityRating * $minutesFactor)
                * 0.20
            );


        return round(
            max(
                0,
                min(
                    100,
                    $reliability
                )
            ),
            2
        );
    }


    /**
     * Convert availability rating into a
     * human-readable label.
     */
    public function getAvailabilityLabel(
        ?float $availabilityRating
    ): string {

        if ($availabilityRating === null) {
            return 'Unknown';
        }

        if ($availabilityRating >= 90) {
            return 'Available';
        }

        if ($availabilityRating >= 60) {
            return 'Likely Available';
        }

        if ($availabilityRating >= 30) {
            return 'Doubtful';
        }

        return 'Unavailable';
    }


    /**
     * Build the complete player availability model.
     */
    public function buildAvailabilityModel(
        array $player
    ): array {

        $chanceOfPlaying =
            isset($player['chance_of_playing'])
                ? (int) $player['chance_of_playing']
                : null;


        $status =
            $player['status'] ?? null;


        $minutes =
            (int) ($player['minutes'] ?? 0);


        $availabilityRating =
            $this->calculateAvailabilityRating(
                $chanceOfPlaying,
                $status
            );


        $reliabilityRating =
            $this->calculateReliabilityRating(
                $availabilityRating,
                $minutes
            );


        return [

            'player_id' =>
                (int) ($player['id'] ?? 0),

            'fpl_player_id' =>
                (int) ($player['fpl_player_id'] ?? 0),

            'name' =>
                $player['web_name'] ?? null,

            'position' =>
                $player['position'] ?? null,

            'minutes' =>
                $minutes,

            'chance_of_playing' =>
                $chanceOfPlaying,

            'status' =>
                $status,

            'availability_rating' =>
                $availabilityRating,

            'reliability_rating' =>
                $reliabilityRating,

            'availability_label' =>
                $this->getAvailabilityLabel(
                    $availabilityRating
                )
        ];
    }
}