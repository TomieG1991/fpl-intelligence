<?php

class PlayerPerformanceModel
{
    /**
     * Position-specific benchmark ceilings.
     *
     * These represent an excellent level of
     * performance rather than an average.
     */
    private array $benchmarks = [

        'GK' => [
            'goals_per_90' => 0.30,
            'assists_per_90' => 0.30,
            'expected_goals_per_90' => 0.30,
            'expected_assists_per_90' => 0.30,
            'clean_sheets_per_90' => 0.60
        ],

        'DEF' => [
            'goals_per_90' => 0.30,
            'assists_per_90' => 0.30,
            'expected_goals_per_90' => 0.30,
            'expected_assists_per_90' => 0.30,
            'clean_sheets_per_90' => 0.60
        ],

        'MID' => [
            'goals_per_90' => 0.70,
            'assists_per_90' => 0.60,
            'expected_goals_per_90' => 0.70,
            'expected_assists_per_90' => 0.60,
            'clean_sheets_per_90' => 0.40
        ],

        'FWD' => [
            'goals_per_90' => 1.00,
            'assists_per_90' => 0.50,
            'expected_goals_per_90' => 1.00,
            'expected_assists_per_90' => 0.50,
            'clean_sheets_per_90' => 0.20
        ]
    ];


    /**
     * Convert a metric into a 0-100 rating.
     */
    public function normalise(
        ?float $value,
        float $benchmark
    ): ?float {

        if ($value === null) {
            return null;
        }

        if ($benchmark <= 0) {
            return null;
        }

        $rating =
            ($value / $benchmark) * 100;

        return round(
            max(0, min(100, $rating)),
            2
        );
    }


    /**
     * Get benchmarks for a position.
     */
    public function getBenchmarks(
        ?string $position
    ): array {

        return $this->benchmarks[$position]
            ?? $this->benchmarks['MID'];
    }


    /**
     * Goals per 90 rating.
     */
    public function calculateGoalsRating(
        ?float $goalsPer90,
        ?string $position
    ): ?float {

        $benchmarks =
            $this->getBenchmarks($position);

        return $this->normalise(
            $goalsPer90,
            $benchmarks['goals_per_90']
        );
    }


    /**
     * Assists per 90 rating.
     */
    public function calculateAssistsRating(
        ?float $assistsPer90,
        ?string $position
    ): ?float {

        $benchmarks =
            $this->getBenchmarks($position);

        return $this->normalise(
            $assistsPer90,
            $benchmarks['assists_per_90']
        );
    }


    /**
     * Expected goals per 90 rating.
     */
    public function calculateExpectedGoalsRating(
        ?float $expectedGoalsPer90,
        ?string $position
    ): ?float {

        $benchmarks =
            $this->getBenchmarks($position);

        return $this->normalise(
            $expectedGoalsPer90,
            $benchmarks['expected_goals_per_90']
        );
    }


    /**
     * Expected assists per 90 rating.
     */
    public function calculateExpectedAssistsRating(
        ?float $expectedAssistsPer90,
        ?string $position
    ): ?float {

        $benchmarks =
            $this->getBenchmarks($position);

        return $this->normalise(
            $expectedAssistsPer90,
            $benchmarks['expected_assists_per_90']
        );
    }


    /**
     * Clean sheets per 90 rating.
     */
    public function calculateCleanSheetsRating(
        ?float $cleanSheetsPer90,
        ?string $position
    ): ?float {

        $benchmarks =
            $this->getBenchmarks($position);

        return $this->normalise(
            $cleanSheetsPer90,
            $benchmarks['clean_sheets_per_90']
        );
    }


    /**
     * Convert BPS into a 0-100 rating.
     *
     * 300 BPS = 100.
     */
    public function calculateBpsRating(
        ?int $bps
    ): ?float {

        if ($bps === null) {
            return null;
        }

        $rating =
            ($bps / 300) * 100;

        return round(
            max(0, min(100, $rating)),
            2
        );
    }


    /**
     * Build the complete normalised player model.
     *
     * This does not calculate the final player
     * performance rating yet.
     */
    public function buildModel(
        array $performance,
        PlayerPerformance $performanceModel
    ): array {

        $position =
            $performance['position']
            ?? 'MID';


        $goalsPer90 =
            $performanceModel->calculateGoalsPer90(
                $performance
            );

        $assistsPer90 =
            $performanceModel->calculateAssistsPer90(
                $performance
            );

        $expectedGoalsPer90 =
            $performanceModel->calculateExpectedGoalsPer90(
                $performance
            );

        $expectedAssistsPer90 =
            $performanceModel->calculateExpectedAssistsPer90(
                $performance
            );

        $cleanSheetsPer90 =
            $performanceModel->calculateCleanSheetsPer90(
                $performance
            );


        return [

            'player_id' =>
                $performance['player_id'],

            'fpl_player_id' =>
                $performance['fpl_player_id'],

            'team_id' =>
                $performance['team_id'],

            'name' =>
                $performance['name'],

            'position' =>
                $position,

            'minutes' =>
                $performance['minutes'],

            'goals_per_90' =>
                $goalsPer90,

            'assists_per_90' =>
                $assistsPer90,

            'expected_goals_per_90' =>
                $expectedGoalsPer90,

            'expected_assists_per_90' =>
                $expectedAssistsPer90,

            'clean_sheets_per_90' =>
                $cleanSheetsPer90,

            'goals_rating' =>
                $this->calculateGoalsRating(
                    $goalsPer90,
                    $position
                ),

            'assists_rating' =>
                $this->calculateAssistsRating(
                    $assistsPer90,
                    $position
                ),

            'expected_goals_rating' =>
                $this->calculateExpectedGoalsRating(
                    $expectedGoalsPer90,
                    $position
                ),

            'expected_assists_rating' =>
                $this->calculateExpectedAssistsRating(
                    $expectedAssistsPer90,
                    $position
                ),

            'clean_sheets_rating' =>
                $this->calculateCleanSheetsRating(
                    $cleanSheetsPer90,
                    $position
                ),

            'bps_rating' =>
                $this->calculateBpsRating(
                    $performance['bps'] ?? null
                )
        ];
    }
}