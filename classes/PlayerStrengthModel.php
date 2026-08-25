<?php

class PlayerStrengthModel
{
    /**
     * Position-specific weighting for player performance.
     */
    private array $weights = [

        'GK' => [
            'goals' => 0.05,
            'assists' => 0.05,
            'expected_goals' => 0.05,
            'expected_assists' => 0.05,
            'clean_sheets' => 0.50,
            'bps' => 0.30
        ],

        'DEF' => [
            'goals' => 0.10,
            'assists' => 0.10,
            'expected_goals' => 0.10,
            'expected_assists' => 0.10,
            'clean_sheets' => 0.35,
            'bps' => 0.25
        ],

        'MID' => [
            'goals' => 0.20,
            'assists' => 0.15,
            'expected_goals' => 0.20,
            'expected_assists' => 0.15,
            'clean_sheets' => 0.10,
            'bps' => 0.20
        ],

        'FWD' => [
            'goals' => 0.30,
            'assists' => 0.15,
            'expected_goals' => 0.25,
            'expected_assists' => 0.10,
            'clean_sheets' => 0.00,
            'bps' => 0.20
        ]
    ];


    /**
     * Return the weighting configuration for a position.
     *
     * Unknown or missing positions continue to use
     * MID as the legacy fallback.
     */
    public function getWeights(
        ?string $position
    ): array {

        $position =
            strtoupper(
                (string) $position
            );


        return $this->weights[$position]
            ?? $this->weights['MID'];
    }


    /**
     * Calculate the combined player strength rating.
     *
     * Missing metrics are excluded and their weighting
     * is redistributed proportionally across the metrics
     * that are available.
     */
    public function calculateRating(
        array $model
    ): ?float {

        $position =
            $model['position']
            ?? null;


        $weights =
            $this->getWeights(
                $position
            );


        $ratings = [

            'goals' =>
                $model['adjusted_goals_rating']
                ??
                $model['goals_rating']
                ??
                null,

            'assists' =>
                $model['adjusted_assists_rating']
                ??
                $model['assists_rating']
                ??
                null,

            'expected_goals' =>
                $model['adjusted_expected_goals_rating']
                ??
                $model['expected_goals_rating']
                ??
                null,

            'expected_assists' =>
                $model['adjusted_expected_assists_rating']
                ??
                $model['expected_assists_rating']
                ??
                null,

            'clean_sheets' =>
                $model['adjusted_clean_sheets_rating']
                ??
                $model['clean_sheets_rating']
                ??
                null,

            'bps' =>
                $model['adjusted_bps_rating']
                ??
                $model['bps_rating']
                ??
                null
        ];


        $weightedTotal =
            0.0;

        $weightTotal =
            0.0;


        foreach (
            $ratings
            as $metric => $rating
        ) {

            if (
                $rating === null
                ||
                !is_numeric($rating)
            ) {

                continue;
            }


            /*
             * All component ratings use the
             * standard 0-100 scale.
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
                $weights[$metric]
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


        if ($weightTotal <= 0) {
            return null;
        }


        $rating =
            $weightedTotal
            /
            $weightTotal;


        return round(
            max(
                0,
                min(
                    100,
                    $rating
                )
            ),
            2
        );
    }


    /**
     * Build the complete player strength model.
     */
    public function buildModel(
        array $model
    ): array {

        $rating =
            $this->calculateRating(
                $model
            );


        return [

            'player_id' =>
                (int) (
                    $model['player_id']
                    ?? 0
                ),

            'fpl_player_id' =>
                (int) (
                    $model['fpl_player_id']
                    ?? 0
                ),

            'team_id' =>
                (int) (
                    $model['team_id']
                    ?? 0
                ),

            'name' =>
                $model['name']
                ?? null,

            'position' =>
                $model['position']
                ?? null,

            'minutes' =>
                (int) (
                    $model['minutes']
                    ?? 0
                ),

            'goals_per_90' =>
                $model['goals_per_90']
                ?? null,

            'assists_per_90' =>
                $model['assists_per_90']
                ?? null,

            'expected_goals_per_90' =>
                $model[
                    'expected_goals_per_90'
                ]
                ?? null,

            'expected_assists_per_90' =>
                $model[
                    'expected_assists_per_90'
                ]
                ?? null,

            'clean_sheets_per_90' =>
                $model[
                    'clean_sheets_per_90'
                ]
                ?? null,

            'goals_rating' =>
                $model['goals_rating']
                ?? null,

            'assists_rating' =>
                $model['assists_rating']
                ?? null,

            'expected_goals_rating' =>
                $model[
                    'expected_goals_rating'
                ]
                ?? null,

            'expected_assists_rating' =>
                $model[
                    'expected_assists_rating'
                ]
                ?? null,

            'clean_sheets_rating' =>
                $model[
                    'clean_sheets_rating'
                ]
                ?? null,

            'bps_rating' =>
                $model['bps_rating']
                ?? null,

            'strength_rating' =>
                $rating,
                
            'sample_confidence' =>
                $model['sample_confidence']
                ?? null,

            'effective_confidence' =>
                $model['effective_confidence']
                ?? null,

            'adjusted_goals_rating' =>
                $model['adjusted_goals_rating']
                ?? null,

            'adjusted_assists_rating' =>
                $model['adjusted_assists_rating']
                ?? null,

            'adjusted_expected_goals_rating' =>
                $model['adjusted_expected_goals_rating']
                ?? null,

            'adjusted_expected_assists_rating' =>
                $model['adjusted_expected_assists_rating']
                ?? null,

            'adjusted_clean_sheets_rating' =>
                $model['adjusted_clean_sheets_rating']
                ?? null,

            'adjusted_bps_rating' =>
                $model['adjusted_bps_rating']
                ?? null,
        ];
    }
}