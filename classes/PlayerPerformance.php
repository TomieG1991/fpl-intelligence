<?php

class PlayerPerformance
{
    /**
     * Position-specific benchmarks used to normalise
     * player performance metrics onto a 0-100 scale.
     *
     * These represent the benchmark level considered
     * to be excellent performance for each position.
     */
    private array $benchmarks = [

        'GK' => [
            'goals_per_90' => 0.10,
            'assists_per_90' => 0.10,
            'expected_goals_per_90' => 0.10,
            'expected_assists_per_90' => 0.10,
            'clean_sheets_per_90' => 0.50,
            'bps' => 1000
        ],

        'DEF' => [
            'goals_per_90' => 0.50,
            'assists_per_90' => 0.40,
            'expected_goals_per_90' => 0.40,
            'expected_assists_per_90' => 0.30,
            'clean_sheets_per_90' => 0.50,
            'bps' => 1000
        ],

        'MID' => [
            'goals_per_90' => 0.70,
            'assists_per_90' => 0.60,
            'expected_goals_per_90' => 0.70,
            'expected_assists_per_90' => 0.60,
            'clean_sheets_per_90' => 0.40,
            'bps' => 1000
        ],

        'FWD' => [
            'goals_per_90' => 1.00,
            'assists_per_90' => 0.50,
            'expected_goals_per_90' => 1.00,
            'expected_assists_per_90' => 0.50,
            'clean_sheets_per_90' => 0.30,
            'bps' => 1000
        ]
    ];


    /**
     * Analyse a player's current FPL statistics.
     *
     * Returns a consistent structure so later
     * intelligence calculations have a reliable
     * data source.
     */
    public function analyse(array $player): array
    {
        return [

            'player_id' =>
                (int) ($player['id'] ?? 0),

            'fpl_player_id' =>
                (int) ($player['fpl_player_id'] ?? 0),

            'team_id' =>
                (int) ($player['team_id'] ?? 0),

            'position' =>
                $player['position'] ?? null,

            'name' =>
                $player['web_name'] ?? null,

            'price' =>
                isset($player['price'])
                    ? (float) $player['price']
                    : null,

            'minutes' =>
                (int) ($player['minutes'] ?? 0),

            'goals' =>
                (int) ($player['goals'] ?? 0),

            'assists' =>
                (int) ($player['assists'] ?? 0),

            'clean_sheets' =>
                (int) ($player['clean_sheets'] ?? 0),

            'bonus' =>
                (int) ($player['bonus'] ?? 0),

            'bps' =>
                (int) ($player['bps'] ?? 0),

            'ict_index' =>
                isset($player['ict_index'])
                    ? (float) $player['ict_index']
                    : null,

            'expected_goals' =>
                isset($player['expected_goals'])
                    ? (float) $player['expected_goals']
                    : null,

            'expected_assists' =>
                isset($player['expected_assists'])
                    ? (float) $player['expected_assists']
                    : null,

            'expected_goal_involvements' =>
                isset($player['expected_goal_involvements'])
                    ? (float) $player['expected_goal_involvements']
                    : null,

            'chance_of_playing' =>
                isset($player['chance_of_playing'])
                    ? (int) $player['chance_of_playing']
                    : null,

            'status' =>
                $player['status'] ?? null,

            'news' =>
                $player['news'] ?? null
        ];
    }


    /**
     * Calculate goals per 90 minutes.
     */
    public function calculateGoalsPer90(
        array $performance
    ): ?float {

        if ($performance['minutes'] <= 0) {
            return null;
        }

        return round(
            (
                $performance['goals']
                / $performance['minutes']
            ) * 90,
            2
        );
    }


    /**
     * Calculate assists per 90 minutes.
     */
    public function calculateAssistsPer90(
        array $performance
    ): ?float {

        if ($performance['minutes'] <= 0) {
            return null;
        }

        return round(
            (
                $performance['assists']
                / $performance['minutes']
            ) * 90,
            2
        );
    }


    /**
     * Calculate expected goals per 90 minutes.
     */
    public function calculateExpectedGoalsPer90(
        array $performance
    ): ?float {

        if (
            $performance['minutes'] <= 0
            ||
            $performance['expected_goals'] === null
        ) {
            return null;
        }

        return round(
            (
                $performance['expected_goals']
                / $performance['minutes']
            ) * 90,
            2
        );
    }


    /**
     * Calculate expected assists per 90 minutes.
     */
    public function calculateExpectedAssistsPer90(
        array $performance
    ): ?float {

        if (
            $performance['minutes'] <= 0
            ||
            $performance['expected_assists'] === null
        ) {
            return null;
        }

        return round(
            (
                $performance['expected_assists']
                / $performance['minutes']
            ) * 90,
            2
        );
    }


    /**
     * Calculate expected goal involvements per 90 minutes.
     */
    public function calculateExpectedGoalInvolvementsPer90(
        array $performance
    ): ?float {

        if (
            $performance['minutes'] <= 0
            ||
            $performance['expected_goal_involvements'] === null
        ) {
            return null;
        }

        return round(
            (
                $performance['expected_goal_involvements']
                / $performance['minutes']
            ) * 90,
            2
        );
    }


    /**
     * Calculate clean sheets per 90 minutes.
     */
    public function calculateCleanSheetsPer90(
        array $performance
    ): ?float {

        if ($performance['minutes'] <= 0) {
            return null;
        }

        return round(
            (
                $performance['clean_sheets']
                / $performance['minutes']
            ) * 90,
            2
        );
    }


    /**
     * Get the benchmark configuration for a position.
     */
    public function getBenchmarks(
        ?string $position
    ): array {

        return $this->benchmarks[$position]
            ?? $this->benchmarks['MID'];
    }


    /**
     * Normalise a metric against its position-specific
     * benchmark.
     *
     * The result is constrained to 0-100.
     */
    public function normaliseMetric(
        ?float $value,
        ?float $benchmark
    ): ?float {

        if (
            $value === null
            ||
            $benchmark === null
            ||
            $benchmark <= 0
        ) {
            return null;
        }

        return round(
            min(
                100,
                max(
                    0,
                    ($value / $benchmark) * 100
                )
            ),
            2
        );
    }


    /**
     * Calculate the normalised goals rating.
     */
    public function calculateGoalsRating(
        array $performance
    ): ?float {

        $position =
            $performance['position']
            ?? 'MID';

        $benchmarks =
            $this->getBenchmarks(
                $position
            );

        $goalsPer90 =
            $this->calculateGoalsPer90(
                $performance
            );

        return $this->normaliseMetric(
            $goalsPer90,
            $benchmarks['goals_per_90']
        );
    }


    /**
     * Calculate the normalised assists rating.
     */
    public function calculateAssistsRating(
        array $performance
    ): ?float {

        $position =
            $performance['position']
            ?? 'MID';

        $benchmarks =
            $this->getBenchmarks(
                $position
            );

        $assistsPer90 =
            $this->calculateAssistsPer90(
                $performance
            );

        return $this->normaliseMetric(
            $assistsPer90,
            $benchmarks['assists_per_90']
        );
    }


    /**
     * Calculate the normalised expected goals rating.
     */
    public function calculateExpectedGoalsRating(
        array $performance
    ): ?float {

        $position =
            $performance['position']
            ?? 'MID';

        $benchmarks =
            $this->getBenchmarks(
                $position
            );

        $expectedGoalsPer90 =
            $this->calculateExpectedGoalsPer90(
                $performance
            );

        return $this->normaliseMetric(
            $expectedGoalsPer90,
            $benchmarks['expected_goals_per_90']
        );
    }


    /**
     * Calculate the normalised expected assists rating.
     */
    public function calculateExpectedAssistsRating(
        array $performance
    ): ?float {

        $position =
            $performance['position']
            ?? 'MID';

        $benchmarks =
            $this->getBenchmarks(
                $position
            );

        $expectedAssistsPer90 =
            $this->calculateExpectedAssistsPer90(
                $performance
            );

        return $this->normaliseMetric(
            $expectedAssistsPer90,
            $benchmarks['expected_assists_per_90']
        );
    }


    /**
     * Calculate the normalised clean sheets rating.
     */
    public function calculateCleanSheetsRating(
        array $performance
    ): ?float {

        $position =
            $performance['position']
            ?? 'MID';

        $benchmarks =
            $this->getBenchmarks(
                $position
            );

        $cleanSheetsPer90 =
            $this->calculateCleanSheetsPer90(
                $performance
            );

        return $this->normaliseMetric(
            $cleanSheetsPer90,
            $benchmarks['clean_sheets_per_90']
        );
    }


    /**
     * Calculate the normalised BPS rating.
     */
    public function calculateBpsRating(
        array $performance
    ): ?float {

        $position =
            $performance['position']
            ?? 'MID';

        $benchmarks =
            $this->getBenchmarks(
                $position
            );

        return $this->normaliseMetric(
            isset($performance['bps'])
                ? (float) $performance['bps']
                : null,
            $benchmarks['bps']
        );
    }


    /**
     * Build the complete player performance model.
     *
     * This combines raw FPL statistics, per-90 metrics
     * and normalised performance ratings.
     */
    public function buildModel(
        array $player
    ): array {

        $performance =
            $this->analyse(
                $player
            );


        /*
         * Per-90 metrics.
         */

        $performance['goals_per_90'] =
            $this->calculateGoalsPer90(
                $performance
            );

        $performance['assists_per_90'] =
            $this->calculateAssistsPer90(
                $performance
            );

        $performance['expected_goals_per_90'] =
            $this->calculateExpectedGoalsPer90(
                $performance
            );

        $performance['expected_assists_per_90'] =
            $this->calculateExpectedAssistsPer90(
                $performance
            );

        $performance['expected_goal_involvements_per_90'] =
            $this->calculateExpectedGoalInvolvementsPer90(
                $performance
            );

        $performance['clean_sheets_per_90'] =
            $this->calculateCleanSheetsPer90(
                $performance
            );


        /*
         * Normalised ratings.
         */

        $performance['goals_rating'] =
            $this->calculateGoalsRating(
                $performance
            );

        $performance['assists_rating'] =
            $this->calculateAssistsRating(
                $performance
            );

        $performance['expected_goals_rating'] =
            $this->calculateExpectedGoalsRating(
                $performance
            );

        $performance['expected_assists_rating'] =
            $this->calculateExpectedAssistsRating(
                $performance
            );

        $performance['clean_sheets_rating'] =
            $this->calculateCleanSheetsRating(
                $performance
            );

        $performance['bps_rating'] =
            $this->calculateBpsRating(
                $performance
            );


        return $performance;
    }
}