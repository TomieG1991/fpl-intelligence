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
         * Explicit chance-of-playing data takes
         * priority over the broader FPL status.
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


        if ($status === null) {
            return null;
        }


        $status =
            strtolower(
                trim($status)
            );


        return match ($status) {

            'a' =>
                100.00,

            'd' =>
                50.00,

            'i' =>
                25.00,

            's' =>
                0.00,

            'u' =>
                0.00,

            default =>
                null
        };
    }


    /**
     * Calculate an adjusted reliability rating
     * using availability and playing history.
     *
     * Players with no minutes retain their
     * availability rating.
     */
    public function calculateReliabilityRating(
        ?float $availabilityRating,
        int $minutes
    ): ?float {

        if ($availabilityRating === null) {
            return null;
        }


        $availabilityRating =
            max(
                0,
                min(
                    100,
                    $availabilityRating
                )
            );


        $minutes =
            max(
                0,
                $minutes
            );


        /*
         * Zero minutes does not automatically mean
         * the player is unavailable.
         */
        if ($minutes === 0) {

            return round(
                $availabilityRating,
                2
            );
        }


        /*
         * 1,000 minutes or more represents a fully
         * established playing sample.
         */
        $minutesFactor =
            min(
                1,
                $minutes / 1000
            );


        /*
         * Current availability = 80%
         * Playing involvement = 20%
         */
        $reliability =
            (
                $availabilityRating
                * 0.80
            )
            +
            (
                (
                    $availabilityRating
                    * $minutesFactor
                )
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
     * Convert availability rating into
     * a human-readable label.
     */
    public function getAvailabilityLabel(
        ?float $availabilityRating
    ): string {

        if ($availabilityRating === null) {
            return 'Unknown';
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
            isset(
                $player['chance_of_playing']
            )
            &&
            is_numeric(
                $player['chance_of_playing']
            )
                ? (int)
                    $player[
                        'chance_of_playing'
                    ]
                : null;


        $status =
            isset($player['status'])
                ? (string)
                    $player['status']
                : null;


        $minutes =
            isset($player['minutes'])
            &&
            is_numeric(
                $player['minutes']
            )
                ? max(
                    0,
                    (int)
                        $player['minutes']
                )
                : 0;


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
                (int) (
                    $player['player_id']
                    ?? 0
                ),

            'fpl_player_id' =>
                (int) (
                    $player['fpl_player_id']
                    ?? 0
                ),

            'name' =>
                $player['name']
                ?? null,

            'position' =>
                $player['position']
                ?? null,

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