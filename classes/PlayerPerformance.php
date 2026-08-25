<?php

class PlayerPerformance
{
    /**
     * Position-specific benchmarks used to normalise
     * player performance metrics onto a 0-100 scale.
     */
    private array $benchmarks = [

        'GK' => [
            'goals_per_90' => 0.10,
            'assists_per_90' => 0.10,
            'expected_goals_per_90' => 0.10,
            'expected_assists_per_90' => 0.10,
            'clean_sheets_per_90' => 0.50,
            'bps_per_90' => 30.0
        ],

        'DEF' => [
            'goals_per_90' => 0.50,
            'assists_per_90' => 0.40,
            'expected_goals_per_90' => 0.40,
            'expected_assists_per_90' => 0.30,
            'clean_sheets_per_90' => 0.50,
            'bps_per_90' => 30.0
        ],

        'MID' => [
            'goals_per_90' => 0.70,
            'assists_per_90' => 0.60,
            'expected_goals_per_90' => 0.70,
            'expected_assists_per_90' => 0.60,
            'clean_sheets_per_90' => 0.40,
            'bps_per_90' => 30.0
        ],

        'FWD' => [
            'goals_per_90' => 1.00,
            'assists_per_90' => 0.50,
            'expected_goals_per_90' => 1.00,
            'expected_assists_per_90' => 0.50,
            'clean_sheets_per_90' => 0.30,
            'bps_per_90' => 30.0
        ]
    ];


    /**
     * Analyse a player's current FPL statistics.
     */
    public function analyse(
        array $player
    ): array {

        return [

            'player_id' =>
                (int) (
                    $player['id']
                    ?? 0
                ),

            'fpl_player_id' =>
                (int) (
                    $player['fpl_player_id']
                    ?? 0
                ),

            'team_id' =>
                (int) (
                    $player['team_id']
                    ?? 0
                ),

            'position' =>
                isset($player['position'])
                    ? strtoupper(
                        (string)
                            $player['position']
                    )
                    : null,

            'name' =>
                $player['web_name']
                ?? null,

            'price' =>
                $this->getNullableFloat(
                    $player,
                    'price'
                ),

            'minutes' =>
                $this->getNonNegativeInt(
                    $player,
                    'minutes'
                ),

            'goals' =>
                $this->getNonNegativeInt(
                    $player,
                    'goals'
                ),

            'assists' =>
                $this->getNonNegativeInt(
                    $player,
                    'assists'
                ),

            'clean_sheets' =>
                $this->getNonNegativeInt(
                    $player,
                    'clean_sheets'
                ),

            'bonus' =>
                $this->getNonNegativeInt(
                    $player,
                    'bonus'
                ),

            'bps' =>
                $this->getNonNegativeInt(
                    $player,
                    'bps'
                ),

            'ict_index' =>
                $this->getNullableFloat(
                    $player,
                    'ict_index'
                ),

            'expected_goals' =>
                $this->getNullableNonNegativeFloat(
                    $player,
                    'expected_goals'
                ),

            'expected_assists' =>
                $this->getNullableNonNegativeFloat(
                    $player,
                    'expected_assists'
                ),

            'expected_goal_involvements' =>
                $this->getNullableNonNegativeFloat(
                    $player,
                    'expected_goal_involvements'
                ),

            'chance_of_playing' =>
                isset($player['chance_of_playing'])
                &&
                is_numeric(
                    $player['chance_of_playing']
                )
                    ? (int) max(
                        0,
                        min(
                            100,
                            $player[
                                'chance_of_playing'
                            ]
                        )
                    )
                    : null,

            'status' =>
                $player['status']
                ?? null,

            'news' =>
                $player['news']
                ?? null
        ];
    }


    /**
     * Calculate goals per 90 minutes.
     */
    public function calculateGoalsPer90(
        array $performance
    ): ?float {

        return $this->calculatePer90(
            $performance['goals']
            ?? null,
            $performance['minutes']
            ?? null
        );
    }


    /**
     * Calculate assists per 90 minutes.
     */
    public function calculateAssistsPer90(
        array $performance
    ): ?float {

        return $this->calculatePer90(
            $performance['assists']
            ?? null,
            $performance['minutes']
            ?? null
        );
    }


    /**
     * Calculate expected goals per 90 minutes.
     */
    public function calculateExpectedGoalsPer90(
        array $performance
    ): ?float {

        return $this->calculatePer90(
            $performance['expected_goals']
            ?? null,
            $performance['minutes']
            ?? null
        );
    }


    /**
     * Calculate expected assists per 90 minutes.
     */
    public function calculateExpectedAssistsPer90(
        array $performance
    ): ?float {

        return $this->calculatePer90(
            $performance['expected_assists']
            ?? null,
            $performance['minutes']
            ?? null
        );
    }


    /**
     * Calculate expected goal involvements per 90 minutes.
     */
    public function calculateExpectedGoalInvolvementsPer90(
        array $performance
    ): ?float {

        return $this->calculatePer90(
            $performance[
                'expected_goal_involvements'
            ]
            ?? null,
            $performance['minutes']
            ?? null
        );
    }


    /**
     * Calculate clean sheets per 90 minutes.
     */
    public function calculateCleanSheetsPer90(
        array $performance
    ): ?float {

        return $this->calculatePer90(
            $performance['clean_sheets']
            ?? null,
            $performance['minutes']
            ?? null
        );
    }
    
    /**
     * Calculate BPS per 90 minutes.
     */
    public function calculateBpsPer90(
        array $performance
    ): ?float {

        return $this->calculatePer90(
            $performance['bps']
            ?? null,
            $performance['minutes']
            ?? null
        );
    }


    /**
     * Get benchmark configuration for a position.
     *
     * Unknown/missing positions continue to use MID
     * as the legacy fallback.
     */
    public function getBenchmarks(
        ?string $position
    ): array {

        $position =
            strtoupper(
                (string) $position
            );


        return $this->benchmarks[$position]
            ?? $this->benchmarks['MID'];
    }


    /**
     * Normalise a metric against a benchmark.
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
            max(
                0,
                min(
                    100,
                    ($value / $benchmark)
                    * 100
                )
            ),
            2
        );
    }


    public function calculateGoalsRating(
        array $performance
    ): ?float {

        $benchmarks =
            $this->getBenchmarks(
                $performance['position']
                ?? null
            );


        return $this->normaliseMetric(
            $this->calculateGoalsPer90(
                $performance
            ),
            $benchmarks[
                'goals_per_90'
            ]
        );
    }


    public function calculateAssistsRating(
        array $performance
    ): ?float {

        $benchmarks =
            $this->getBenchmarks(
                $performance['position']
                ?? null
            );


        return $this->normaliseMetric(
            $this->calculateAssistsPer90(
                $performance
            ),
            $benchmarks[
                'assists_per_90'
            ]
        );
    }


    public function calculateExpectedGoalsRating(
        array $performance
    ): ?float {

        $benchmarks =
            $this->getBenchmarks(
                $performance['position']
                ?? null
            );


        return $this->normaliseMetric(
            $this->calculateExpectedGoalsPer90(
                $performance
            ),
            $benchmarks[
                'expected_goals_per_90'
            ]
        );
    }


    public function calculateExpectedAssistsRating(
        array $performance
    ): ?float {

        $benchmarks =
            $this->getBenchmarks(
                $performance['position']
                ?? null
            );


        return $this->normaliseMetric(
            $this->calculateExpectedAssistsPer90(
                $performance
            ),
            $benchmarks[
                'expected_assists_per_90'
            ]
        );
    }


    public function calculateCleanSheetsRating(
        array $performance
    ): ?float {

        $benchmarks =
            $this->getBenchmarks(
                $performance['position']
                ?? null
            );


        return $this->normaliseMetric(
            $this->calculateCleanSheetsPer90(
                $performance
            ),
            $benchmarks[
                'clean_sheets_per_90'
            ]
        );
    }


    public function calculateBpsRating(
        array $performance
    ): ?float {

        $benchmarks =
            $this->getBenchmarks(
                $performance['position']
                ?? null
            );


        return $this->normaliseMetric(
            $this->calculateBpsPer90(
                $performance
            ),
            $benchmarks[
                'bps_per_90'
            ]
        );
    }
    
    /**
     * Calculate confidence in performance data
     * based on the player's minutes sample.
     *
     * 900 minutes represents full confidence.
     */
    public function calculateSampleConfidence(
        int $minutes,
        int $fullConfidenceMinutes = 900
    ): float {

        $minutes =
            max(
                0,
                $minutes
            );


        if ($fullConfidenceMinutes <= 0) {
            return 1.00;
        }


        return round(
            min(
                1,
                $minutes
                /
                $fullConfidenceMinutes
            ),
            4
        );
    }
    
    
    /**
     * Calculate effective decision confidence.
     *
     * Sample confidence measures how mature the player's
     * current-season statistical sample is.
     *
     * Participation confidence measures how much of the
     * team's currently available Premier League minutes
     * the player has actually played.
     *
     * These concepts are intentionally kept separate:
     *
     * - 40% long-term sample maturity
     * - 60% current participation reliability
     *
     * This allows an established 90-minute GW1 starter to
     * be treated as more decision-reliable than a substitute,
     * without pretending that one match represents a mature
     * statistical sample.
     *
     * A null result means the player's team has not yet
     * produced any current-season participation evidence.
     */
    public function calculateEffectiveConfidence(
        int $minutes,
        int $availableMinutes
    ): ?float {

        $minutes =
            max(
                0,
                $minutes
            );


        $availableMinutes =
            max(
                0,
                $availableMinutes
            );


        /*
         * No team-level playing evidence exists yet.
         *
         * Do not classify the player as unreliable merely
         * because their team has not played.
         */
        if (
            $availableMinutes <= 0
        ) {

            return null;
        }


        /*
         * A player's participation cannot exceed the amount
         * of league football currently available to the team.
         */
        $participationMinutes =
            min(
                $minutes,
                $availableMinutes
            );


        /*
         * Existing season-sample maturity.
         *
         * 900 minutes remains full statistical confidence.
         */
        $sampleConfidence =
            $this->calculateSampleConfidence(
                $minutes
            );


        /*
         * Current participation reliability.
         *
         * Example during GW1:
         *
         * 90 / 90 = 100%
         * 45 / 90 = 50%
         *  5 / 90 = 5.6%
         */
        $participationConfidence =
            $participationMinutes
            /
            $availableMinutes;


        $participationConfidence =
            max(
                0.0,
                min(
                    1.0,
                    $participationConfidence
                )
            );


        /*
         * Blend maturity and participation.
         *
         * Participation receives the greater early-season
         * influence because it distinguishes genuine starters
         * from substitutes while the league-wide sample is young.
         */
        $effectiveConfidence =
            (
                $sampleConfidence
                *
                0.40
            )
            +
            (
                $participationConfidence
                *
                0.60
            );


        return round(
            max(
                0.0,
                min(
                    1.0,
                    $effectiveConfidence
                )
            ),
            4
        );
    }


    /**
     * Adjust a performance rating according to
     * the size of the player's minutes sample.
     *
     * Small samples are pulled towards the
     * neutral rating of 50 rather than zero.
     */
    public function applySampleConfidence(
        ?float $rating,
        int $minutes
    ): ?float {

        if ($rating === null) {
            return null;
        }


        $rating =
            max(
                0,
                min(
                    100,
                    $rating
                )
            );


        $confidence =
            $this->calculateSampleConfidence(
                $minutes
            );


        $adjustedRating =
            50
            +
            (
                ($rating - 50)
                *
                $confidence
            );


        return round(
            max(
                0,
                min(
                    100,
                    $adjustedRating
                )
            ),
            2
        );
    }
    
    /**
     * Adjust a performance rating using an explicitly supplied
     * confidence value.
     *
     * Confidence uses the standard internal 0.0-1.0 scale.
     *
     * The caller chooses the appropriate confidence signal.
     * Player Strength currently uses Sample Confidence so
     * statistical maturity remains separate from Effective
     * Decision Confidence.
     */
    public function applyConfidence(
        ?float $rating,
        ?float $confidence
    ): ?float {

        if ($rating === null) {
            return null;
        }


        $rating =
            max(
                0,
                min(
                    100,
                    $rating
                )
            );


        if (
            $confidence === null
            ||
            !is_numeric(
                $confidence
            )
        ) {

            return round(
                $rating,
                2
            );
        }


        $confidence =
            max(
                0.0,
                min(
                    1.0,
                    (float) $confidence
                )
            );


        $adjustedRating =
            50
            +
            (
                ($rating - 50)
                *
                $confidence
            );


        return round(
            max(
                0,
                min(
                    100,
                    $adjustedRating
                )
            ),
            2
        );
    }


    /**
     * Build the complete player performance model.
     */
    public function buildModel(
        array $player,
        ?int $availableMinutes = null
    ): array {

        $performance =
            $this->analyse(
                $player
            );


        $performance['goals_per_90'] =
            $this->calculateGoalsPer90(
                $performance
            );


        $performance['assists_per_90'] =
            $this->calculateAssistsPer90(
                $performance
            );


        $performance[
            'expected_goals_per_90'
        ] =
            $this->calculateExpectedGoalsPer90(
                $performance
            );


        $performance[
            'expected_assists_per_90'
        ] =
            $this->calculateExpectedAssistsPer90(
                $performance
            );


        $performance[
            'expected_goal_involvements_per_90'
        ] =
            $this
                ->calculateExpectedGoalInvolvementsPer90(
                    $performance
                );


        $performance[
            'clean_sheets_per_90'
        ] =
            $this->calculateCleanSheetsPer90(
                $performance
            );


        $performance['goals_rating'] =
            $this->calculateGoalsRating(
                $performance
            );


        $performance['assists_rating'] =
            $this->calculateAssistsRating(
                $performance
            );


        $performance[
            'expected_goals_rating'
        ] =
            $this->calculateExpectedGoalsRating(
                $performance
            );


        $performance[
            'expected_assists_rating'
        ] =
            $this->calculateExpectedAssistsRating(
                $performance
            );


        $performance[
            'clean_sheets_rating'
        ] =
            $this->calculateCleanSheetsRating(
                $performance
            );
            
        $performance['bps_per_90'] =
            $this->calculateBpsPer90(
                $performance
            );


        $performance['bps_rating'] =
            $this->calculateBpsRating(
                $performance
            );
            
        $minutes =
            (int) (
                $performance['minutes']
                ?? 0
            );


        $performance['sample_confidence'] =
            $this->calculateSampleConfidence(
                $minutes
            );


        /*
         * --------------------------------------------------------
         * EFFECTIVE DECISION CONFIDENCE
         * --------------------------------------------------------
         *
         * When team-level available minutes are supplied by the
         * application service, use them to distinguish established
         * current starters from substitutes during small early-season
         * samples.
         *
         * If that context is unavailable, preserve the existing
         * sample-confidence behaviour for backwards compatibility.
         */

        $performance['effective_confidence'] =
            $availableMinutes !== null
                ? $this->calculateEffectiveConfidence(
                    $minutes,
                    $availableMinutes
                )
                : null;


        $performance['adjusted_goals_rating'] =
            $this->applyConfidence(
                $performance['goals_rating'],
                $performance['sample_confidence']
            );


        $performance['adjusted_assists_rating'] =
            $this->applyConfidence(
                $performance['assists_rating'],
                $performance['sample_confidence']
            );


        $performance['adjusted_expected_goals_rating'] =
            $this->applyConfidence(
                $performance['expected_goals_rating'],
                $performance['sample_confidence']
            );


        $performance['adjusted_expected_assists_rating'] =
            $this->applyConfidence(
                $performance['expected_assists_rating'],
                $performance['sample_confidence']
            );


        $performance['adjusted_clean_sheets_rating'] =
            $this->applyConfidence(
                $performance['clean_sheets_rating'],
                $performance['sample_confidence']
            );


        $performance['adjusted_bps_rating'] =
            $this->applyConfidence(
                $performance['bps_rating'],
                $performance['sample_confidence']
            );


        return $performance;
    }


    /**
     * Calculate a per-90 value safely.
     */
    private function calculatePer90(
        mixed $value,
        mixed $minutes
    ): ?float {

        if (
            !is_numeric($value)
            ||
            !is_numeric($minutes)
            ||
            (float) $minutes <= 0
        ) {

            return null;
        }


        $value =
            max(
                0,
                (float) $value
            );


        return round(
            (
                $value
                /
                (float) $minutes
            )
            * 90,
            2
        );
    }


    /**
     * Read a non-negative integer value.
     */
    private function getNonNegativeInt(
        array $data,
        string $field
    ): int {

        if (
            !isset($data[$field])
            ||
            !is_numeric(
                $data[$field]
            )
        ) {

            return 0;
        }


        return max(
            0,
            (int) $data[$field]
        );
    }


    /**
     * Read a nullable float.
     */
    private function getNullableFloat(
        array $data,
        string $field
    ): ?float {

        if (
            !isset($data[$field])
            ||
            !is_numeric(
                $data[$field]
            )
        ) {

            return null;
        }


        return (float)
            $data[$field];
    }


    /**
     * Read a nullable, non-negative float.
     */
    private function getNullableNonNegativeFloat(
        array $data,
        string $field
    ): ?float {

        $value =
            $this->getNullableFloat(
                $data,
                $field
            );


        if ($value === null) {
            return null;
        }


        return max(
            0,
            $value
        );
    }
}