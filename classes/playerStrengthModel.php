<?php

class PlayerStrengthModel
{
    /**
     * Position-specific weighting for player performance.
     *
     * The weighting reflects which metrics are most relevant
     * to each position.
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
            'clean_sheets' => 0.05,
            'bps' => 0.15
        ]
    ];


    /**
     * Return the weighting configuration for a position.
     */
    public function getWeights(
        ?string $position
    ): array {

        return $this->weights[$position]
            ?? $this->weights['MID'];
    }


    /**
     * Calculate the combined player strength rating.
     *
     * All component ratings are expected to be
     * normalised to a 0-100 scale.
     */
    public function calculateRating(
        array $model
    ): ?float {

        $position =
            $model['position']
            ?? 'MID';


        $weights =
            $this->getWeights(
                $position
            );


        /*
         * Required component ratings.
         *
         * If a metric is unavailable we exclude it
         * and redistribute its weighting proportionally
         * across the metrics that are available.
         */
        $ratings = [

            'goals' =>
                $model['goals_rating'] ?? null,

            'assists' =>
                $model['assists_rating'] ?? null,

            'expected_goals' =>
                $model['expected_goals_rating'] ?? null,

            'expected_assists' =>
                $model['expected_assists_rating'] ?? null,

            'clean_sheets' =>
                $model['clean_sheets_rating'] ?? null,

            'bps' =>
                $model['bps_rating'] ?? null
        ];


        $weightedTotal = 0.0;
        $weightTotal = 0.0;


        foreach ($ratings as $metric => $rating) {

            if ($rating === null) {
                continue;
            }


            $weight =
                $weights[$metric];


            $weightedTotal +=
                $rating * $weight;

            $weightTotal +=
                $weight;
        }


        if ($weightTotal <= 0) {
            return null;
        }


        return round(
            $weightedTotal / $weightTotal,
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
                $model['player_id'],

            'fpl_player_id' =>
                $model['fpl_player_id'],

            'team_id' =>
                $model['team_id'],

            'name' =>
                $model['name'],

            'position' =>
                $model['position'],

            'minutes' =>
                $model['minutes'],

            'goals_per_90' =>
                $model['goals_per_90'],

            'assists_per_90' =>
                $model['assists_per_90'],

            'expected_goals_per_90' =>
                $model['expected_goals_per_90'],

            'expected_assists_per_90' =>
                $model['expected_assists_per_90'],

            'clean_sheets_per_90' =>
                $model['clean_sheets_per_90'],

            'goals_rating' =>
                $model['goals_rating'],

            'assists_rating' =>
                $model['assists_rating'],

            'expected_goals_rating' =>
                $model['expected_goals_rating'],

            'expected_assists_rating' =>
                $model['expected_assists_rating'],

            'clean_sheets_rating' =>
                $model['clean_sheets_rating'],

            'bps_rating' =>
                $model['bps_rating'],

            'strength_rating' =>
                $rating
        ];
    }
}