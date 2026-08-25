<?php

class PlayerForm
{
    private PlayerFormHistory $formHistory;


    /**
     * Initialise Player Form Intelligence.
     */
    public function __construct(
        PlayerFormHistory $formHistory
    ) {

        $this->formHistory =
            $formHistory;
    }


    /**
     * Build a standalone recent-form model.
     *
     * This model deliberately remains separate from:
     *
     * - Player Strength
     * - Sample Confidence
     * - Effective Confidence
     * - Player Intelligence
     *
     * at this stage of v0.28.
     */
    public function buildModel(
        int $playerId,
        ?string $position = null,
        int $fixtureLimit = 5,
        int $appearanceLimit = 5
    ): array {

        $position =
            $this->normalisePosition(
                $position
            );


        $history =
            $this->formHistory
                ->buildHistory(
                    $playerId,
                    $fixtureLimit,
                    $appearanceLimit
                );


        $fixtureWindow =
            $history[
                'fixture_window'
            ]
            ?? [];


        $appearanceWindow =
            $history[
                'appearance_window'
            ]
            ?? [];


        /*
         * --------------------------------------------------------
         * RAW RECENT METRICS
         * --------------------------------------------------------
         */

        $appearanceCount =
            count(
                $appearanceWindow
            );


        $fixtureCount =
            count(
                $fixtureWindow
            );
            
        $appearanceWeights =
            $this->getRecencyWeights(
                $appearanceCount
            );


        $fixtureWeights =
            $this->getRecencyWeights(
                $fixtureCount
            );


        $totalPoints =
            0;


        $totalMinutes =
            0;


        $totalExpectedGoals =
            0.0;


        $totalExpectedAssists =
            0.0;


        $totalExpectedGoalInvolvements =
            0.0;


        $totalBps =
            0;


        $cleanSheets =
            0;


        $totalExpectedGoalsConceded =
            0.0;
            
        $weightedPoints =
            0.0;


        $appearanceWeightTotal =
            0.0;


        $weightedAppearanceMinutes =
            0.0;


        $weightedExpectedGoals =
            0.0;


        $weightedExpectedAssists =
            0.0;


        $weightedExpectedGoalInvolvements =
            0.0;


        $weightedBps =
            0.0;


        $weightedCleanSheets =
            0.0;


        $weightedExpectedGoalsConceded =
            0.0;


        $weightedExpectedGoalsMinutes =
            0.0;


        $weightedExpectedAssistsMinutes =
            0.0;


        $weightedExpectedGoalInvolvementMinutes =
            0.0;


        $weightedBpsMinutes =
            0.0;


        $weightedExpectedGoalsConcededMinutes =
            0.0;


        $expectedGoalsSamples =
            0;


        $expectedAssistsSamples =
            0;


        $expectedGoalInvolvementSamples =
            0;


        $bpsSamples =
            0;


        $expectedGoalsConcededSamples =
            0;


        foreach (
            $appearanceWindow
            as $index => $row
        ) {

            $weight =
                (float) (
                    $appearanceWeights[
                        $index
                    ]
                    ?? 1.0
                );


            $appearanceWeightTotal +=
                $weight;


            $minutes =
                max(
                    0,
                    (int) (
                        $row[
                            'minutes'
                        ]
                        ?? 0
                    )
                );


            $totalMinutes +=
                $minutes;


            $weightedAppearanceMinutes +=
                $minutes
                *
                $weight;


            $points =
                (int) (
                    $row[
                        'total_points'
                    ]
                    ?? 0
                );


            $totalPoints +=
                $points;


            $weightedPoints +=
                $points
                *
                $weight;


            $bpsValue =
                (int) (
                    $row[
                        'bps'
                    ]
                    ?? 0
                );


            $totalBps +=
                $bpsValue;


            $weightedBps +=
                $bpsValue
                *
                $weight;


            $bpsSamples++;


            $weightedBpsMinutes +=
                $minutes
                *
                $weight;


            $cleanSheetValue =
                (int) (
                    $row[
                        'clean_sheets'
                    ]
                    ?? 0
                );


            $cleanSheets +=
                $cleanSheetValue;


            $weightedCleanSheets +=
                $cleanSheetValue
                *
                $weight;


            if (
                is_numeric(
                    $row[
                        'expected_goals'
                    ]
                    ?? null
                )
            ) {

                $expectedGoals =
                    (float) $row[
                        'expected_goals'
                    ];


                $totalExpectedGoals +=
                    $expectedGoals;


                $weightedExpectedGoals +=
                    $expectedGoals
                    *
                    $weight;


                $expectedGoalsSamples++;


                $weightedExpectedGoalsMinutes +=
                    $minutes
                    *
                    $weight;
            }


            if (
                is_numeric(
                    $row[
                        'expected_assists'
                    ]
                    ?? null
                )
            ) {

                $expectedAssists =
                    (float) $row[
                        'expected_assists'
                    ];


                $totalExpectedAssists +=
                    $expectedAssists;


                $weightedExpectedAssists +=
                    $expectedAssists
                    *
                    $weight;


                $expectedAssistsSamples++;


                $weightedExpectedAssistsMinutes +=
                    $minutes
                    *
                    $weight;
            }


            if (
                is_numeric(
                    $row[
                        'expected_goal_involvements'
                    ]
                    ?? null
                )
            ) {

                $expectedGoalInvolvements =
                    (float) $row[
                        'expected_goal_involvements'
                    ];


                $totalExpectedGoalInvolvements +=
                    $expectedGoalInvolvements;


                $weightedExpectedGoalInvolvements +=
                    $expectedGoalInvolvements
                    *
                    $weight;


                $expectedGoalInvolvementSamples++;


                $weightedExpectedGoalInvolvementMinutes +=
                    $minutes
                    *
                    $weight;
            }


            if (
                is_numeric(
                    $row[
                        'expected_goals_conceded'
                    ]
                    ?? null
                )
            ) {

                $expectedGoalsConceded =
                    (float) $row[
                        'expected_goals_conceded'
                    ];


                $totalExpectedGoalsConceded +=
                    $expectedGoalsConceded;


                $weightedExpectedGoalsConceded +=
                    $expectedGoalsConceded
                    *
                    $weight;


                $expectedGoalsConcededSamples++;


                $weightedExpectedGoalsConcededMinutes +=
                    $minutes
                    *
                    $weight;
            }
        }


        /*
         * --------------------------------------------------------
         * RAW AVERAGES
         * --------------------------------------------------------
         */

        $pointsPerAppearance =
            $appearanceCount > 0
                ? $totalPoints
                    /
                    $appearanceCount
                : null;


        $averageAppearanceMinutes =
            $appearanceCount > 0
                ? $totalMinutes
                    /
                    $appearanceCount
                : null;


        $expectedGoalsPer90 =
            (
                $totalMinutes > 0
                &&
                $expectedGoalsSamples > 0
            )
                ? (
                    $totalExpectedGoals
                    /
                    $totalMinutes
                    *
                    90
                )
                : null;


        $expectedAssistsPer90 =
            (
                $totalMinutes > 0
                &&
                $expectedAssistsSamples > 0
            )
                ? (
                    $totalExpectedAssists
                    /
                    $totalMinutes
                    *
                    90
                )
                : null;


        $expectedGoalInvolvementsPer90 =
            (
                $totalMinutes > 0
                &&
                $expectedGoalInvolvementSamples > 0
            )
                ? (
                    $totalExpectedGoalInvolvements
                    /
                    $totalMinutes
                    *
                    90
                )
                : null;


        $bpsPer90 =
            (
                $totalMinutes > 0
                &&
                $bpsSamples > 0
            )
                ? (
                    $totalBps
                    /
                    $totalMinutes
                    *
                    90
                )
                : null;


        $cleanSheetRate =
            $appearanceCount > 0
                ? (
                    $cleanSheets
                    /
                    $appearanceCount
                    *
                    100
                )
                : null;


        $expectedGoalsConcededPer90 =
            (
                $totalMinutes > 0
                &&
                $expectedGoalsConcededSamples > 0
            )
                ? (
                    $totalExpectedGoalsConceded
                    /
                    $totalMinutes
                    *
                    90
                )
                : null;
                
                
        /*
         * --------------------------------------------------------
         * RECENCY-WEIGHTED METRICS
         * --------------------------------------------------------
         */

        $weightedPointsPerAppearance =
            $appearanceWeightTotal > 0
                ? $weightedPoints
                    /
                    $appearanceWeightTotal
                : null;


        $weightedAverageAppearanceMinutes =
            $appearanceWeightTotal > 0
                ? $weightedAppearanceMinutes
                    /
                    $appearanceWeightTotal
                : null;


        $weightedExpectedGoalsPer90 =
            $weightedExpectedGoalsMinutes > 0
                ? $weightedExpectedGoals
                    /
                    $weightedExpectedGoalsMinutes
                    *
                    90
                : null;


        $weightedExpectedAssistsPer90 =
            $weightedExpectedAssistsMinutes > 0
                ? $weightedExpectedAssists
                    /
                    $weightedExpectedAssistsMinutes
                    *
                    90
                : null;


        $weightedExpectedGoalInvolvementsPer90 =
            $weightedExpectedGoalInvolvementMinutes > 0
                ? $weightedExpectedGoalInvolvements
                    /
                    $weightedExpectedGoalInvolvementMinutes
                    *
                    90
                : null;


        $weightedBpsPer90 =
            $weightedBpsMinutes > 0
                ? $weightedBps
                    /
                    $weightedBpsMinutes
                    *
                    90
                : null;


        $weightedCleanSheetRate =
            $appearanceWeightTotal > 0
                ? $weightedCleanSheets
                    /
                    $appearanceWeightTotal
                    *
                    100
                : null;


        $weightedExpectedGoalsConcededPer90 =
            $weightedExpectedGoalsConcededMinutes > 0
                ? $weightedExpectedGoalsConceded
                    /
                    $weightedExpectedGoalsConcededMinutes
                    *
                    90
                : null;


        /*
         * Minutes form uses the complete recent fixture window,
         * including official zero-minute history rows.
         */

        $weightedFixtureMinutes =
            0.0;


        $fixtureWeightTotal =
            0.0;


        foreach (
            $fixtureWindow
            as $index => $row
        ) {

            $weight =
                (float) (
                    $fixtureWeights[
                        $index
                    ]
                    ?? 1.0
                );


            $weightedFixtureMinutes +=
                max(
                    0,
                    (int) (
                        $row[
                            'minutes'
                        ]
                        ?? 0
                    )
                )
                *
                $weight;


            $fixtureWeightTotal +=
                $weight;
        }


        $weightedMinutesPerFixture =
            $fixtureWeightTotal > 0
                ? $weightedFixtureMinutes
                    /
                    $fixtureWeightTotal
                : null;


        /*
         * --------------------------------------------------------
         * COMPONENT RATINGS
         * --------------------------------------------------------
         */

        $pointsRating =
        $weightedPointsPerAppearance !== null
            ? $this->scale(
                $weightedPointsPerAppearance,
                0,
                10
            )
            : null;


    /*
     * Minutes Rating uses the complete recent fixture window,
     * including official zero-minute history.
     *
     * Recency weighting means newer participation evidence has
     * slightly more influence than older participation evidence.
     */
    $minutesRating =
        $weightedMinutesPerFixture !== null
            ? $this->scale(
                $weightedMinutesPerFixture,
                0,
                90
            )
            : null;


    $xgiRating =
        $weightedExpectedGoalInvolvementsPer90 !== null
            ? $this->scale(
                $weightedExpectedGoalInvolvementsPer90,
                0,
                1.0
            )
            : null;


    $bpsRating =
        $weightedBpsPer90 !== null
            ? $this->scale(
                $weightedBpsPer90,
                0,
                40
            )
            : null;


    $defensiveRating =
        $this->calculateDefensiveRating(
            $position,
            $weightedCleanSheetRate,
            $weightedExpectedGoalsConcededPer90
        );


        /*
         * --------------------------------------------------------
         * POSITION-AWARE FORM WEIGHTS
         * --------------------------------------------------------
         */

        $weights =
            $this->getWeights(
                $position
            );


        $components = [

            'points' =>
                $pointsRating,

            'minutes' =>
                $minutesRating,

            'xgi' =>
                $xgiRating,

            'bps' =>
                $bpsRating,

            'defensive' =>
                $defensiveRating
        ];


        $formRating =
            $this->weightedRating(
                $components,
                $weights
            );
            
            
        /*
         * --------------------------------------------------------
         * PERFORMANCE-ONLY FORM RATING
         * --------------------------------------------------------
         *
         * This deliberately excludes the minutes component.
         *
         * Form Rating remains the holistic recent FPL form score,
         * including recent participation.
         *
         * Performance Rating measures how well the player has
         * performed when actually on the pitch.
         *
         * This separation allows PlayerFormTrend to distinguish:
         *
         * performance quality
         * from
         * participation / playing-time direction.
         */

        $performanceComponents = [

            'points' =>
                $pointsRating,

            'xgi' =>
                $xgiRating,

            'bps' =>
                $bpsRating,

            'defensive' =>
                $defensiveRating
        ];


        $performanceWeights = [

            'points' =>
                $weights[
                    'points'
                ]
                ?? 0,

            'xgi' =>
                $weights[
                    'xgi'
                ]
                ?? 0,

            'bps' =>
                $weights[
                    'bps'
                ]
                ?? 0,

            'defensive' =>
                $weights[
                    'defensive'
                ]
                ?? 0
        ];


        $performanceRating =
            $this->weightedRating(
                $performanceComponents,
                $performanceWeights
            );


        return [

            'player_id' =>
                $playerId,

            'position' =>
                $position,

            'form_rating' =>
                $formRating,
                
            'performance_rating' =>
                $performanceRating,

            'fixture_sample_size' =>
                (int) (
                    $history[
                        'fixture_sample_size'
                    ]
                    ?? 0
                ),

            'appearance_sample_size' =>
                (int) (
                    $history[
                        'appearance_sample_size'
                    ]
                    ?? 0
                ),

            'zero_minute_rows' =>
                (int) (
                    $history[
                        'zero_minute_rows'
                    ]
                    ?? 0
                ),

            'participation_rate' =>
                $history[
                    'participation_rate'
                ]
                ?? null,

            'recency_weights' => [

                'fixture' =>
                    $fixtureWeights,

                'appearance' =>
                    $appearanceWeights
            ],

            'raw_metrics' => [

                'total_points' =>
                    $totalPoints,

                'total_minutes' =>
                    $totalMinutes,

                'points_per_appearance' =>
                    $this->roundNullable(
                        $pointsPerAppearance
                    ),

                'average_appearance_minutes' =>
                    $this->roundNullable(
                        $averageAppearanceMinutes
                    ),

                'expected_goals_per_90' =>
                    $this->roundNullable(
                        $expectedGoalsPer90
                    ),

                'expected_assists_per_90' =>
                    $this->roundNullable(
                        $expectedAssistsPer90
                    ),

                'expected_goal_involvements_per_90' =>
                    $this->roundNullable(
                        $expectedGoalInvolvementsPer90
                    ),

                'bps_per_90' =>
                    $this->roundNullable(
                        $bpsPer90
                    ),

                'clean_sheet_rate' =>
                    $this->roundNullable(
                        $cleanSheetRate
                    ),

                'expected_goals_conceded_per_90' =>
                    $this->roundNullable(
                        $expectedGoalsConcededPer90
                    )
            ],

            'weighted_metrics' => [

                'points_per_appearance' =>
                    $this->roundNullable(
                        $weightedPointsPerAppearance
                    ),

                'average_appearance_minutes' =>
                    $this->roundNullable(
                        $weightedAverageAppearanceMinutes
                    ),

                'minutes_per_fixture' =>
                    $this->roundNullable(
                        $weightedMinutesPerFixture
                    ),

                'expected_goals_per_90' =>
                    $this->roundNullable(
                        $weightedExpectedGoalsPer90
                    ),

                'expected_assists_per_90' =>
                    $this->roundNullable(
                        $weightedExpectedAssistsPer90
                    ),

                'expected_goal_involvements_per_90' =>
                    $this->roundNullable(
                        $weightedExpectedGoalInvolvementsPer90
                    ),

                'bps_per_90' =>
                    $this->roundNullable(
                        $weightedBpsPer90
                    ),

                'clean_sheet_rate' =>
                    $this->roundNullable(
                        $weightedCleanSheetRate
                    ),

                'expected_goals_conceded_per_90' =>
                    $this->roundNullable(
                        $weightedExpectedGoalsConcededPer90
                    )
            ],

            'component_ratings' => [

                'points_rating' =>
                    $pointsRating,

                'minutes_rating' =>
                    $minutesRating,

                'xgi_rating' =>
                    $xgiRating,

                'bps_rating' =>
                    $bpsRating,

                'defensive_rating' =>
                    $defensiveRating
            ],

            'weights' =>
                $weights,

            'history' =>
                $history
        ];
    }
    
    /**
     * Build conservative chronological recency weights.
     *
     * History windows arrive oldest to newest.
     *
     * Example for five matches:
     *
     * 1.00
     * 1.10
     * 1.20
     * 1.30
     * 1.40
     *
     * The newest match therefore carries more influence than
     * the oldest, without allowing one recent fixture to
     * dominate the whole sample.
     */
    public function getRecencyWeights(
        int $sampleSize
    ): array {

        if ($sampleSize <= 0) {

            return [];
        }


        if ($sampleSize === 1) {

            return [
                1.0
            ];
        }


        $minimumWeight =
            1.0;


        $maximumWeight =
            1.4;


        $step =
            (
                $maximumWeight
                -
                $minimumWeight
            )
            /
            (
                $sampleSize
                -
                1
            );


        $weights =
            [];


        for (
            $index = 0;
            $index < $sampleSize;
            $index++
        ) {

            $weights[] =
                round(
                    $minimumWeight
                    +
                    (
                        $step
                        *
                        $index
                    ),
                    4
                );
        }


        return $weights;
    }


    /**
     * Return position-specific Form Rating weights.
     */
    public function getWeights(
        ?string $position
    ): array {

        $position =
            $this->normalisePosition(
                $position
            );


        return match ($position) {

            'GK' => [

                'points' =>
                    0.25,

                'minutes' =>
                    0.25,

                'xgi' =>
                    0.00,

                'bps' =>
                    0.20,

                'defensive' =>
                    0.30
            ],

            'DEF' => [

                'points' =>
                    0.30,

                'minutes' =>
                    0.20,

                'xgi' =>
                    0.15,

                'bps' =>
                    0.15,

                'defensive' =>
                    0.20
            ],

            'FWD' => [

                'points' =>
                    0.35,

                'minutes' =>
                    0.20,

                'xgi' =>
                    0.35,

                'bps' =>
                    0.10,

                'defensive' =>
                    0.00
            ],

            default => [

                /*
                 * MID is also the safe fallback.
                 */

                'points' =>
                    0.35,

                'minutes' =>
                    0.20,

                'xgi' =>
                    0.30,

                'bps' =>
                    0.15,

                'defensive' =>
                    0.00
            ]
        };
    }


    /**
     * Defensive recent-form component.
     *
     * Only goalkeepers and defenders currently use it.
     */
    private function calculateDefensiveRating(
        string $position,
        ?float $cleanSheetRate,
        ?float $expectedGoalsConcededPer90
    ): ?float {

        if (
            !in_array(
                $position,
                [
                    'GK',
                    'DEF'
                ],
                true
            )
        ) {

            return null;
        }


        $cleanSheetComponent =
            $cleanSheetRate !== null
                ? $this->clamp(
                    $cleanSheetRate
                )
                : null;


        /*
         * 0 xGC/90 = 100
         * 3 xGC/90 = 0
         */
        $xgcComponent =
            $expectedGoalsConcededPer90 !== null
                ? 100
                    -
                    $this->scale(
                        $expectedGoalsConcededPer90,
                        0,
                        3
                    )
                : null;


        return $this->weightedRating(
            [

                'clean_sheet' =>
                    $cleanSheetComponent,

                'xgc' =>
                    $xgcComponent
            ],
            [

                'clean_sheet' =>
                    0.70,

                'xgc' =>
                    0.30
            ]
        );
    }


    /**
     * Calculate a weighted score while redistributing the
     * weighting of unavailable components.
     */
    private function weightedRating(
        array $components,
        array $weights
    ): ?float {

        $weightedTotal =
            0.0;


        $weightTotal =
            0.0;


        foreach (
            $components
            as $component => $rating
        ) {

            if (
                $rating === null
                ||
                !is_numeric(
                    $rating
                )
            ) {

                continue;
            }


            $weight =
                (float) (
                    $weights[
                        $component
                    ]
                    ?? 0
                );


            if ($weight <= 0) {

                continue;
            }


            $weightedTotal +=
                $this->clamp(
                    (float) $rating
                )
                *
                $weight;


            $weightTotal +=
                $weight;
        }


        if ($weightTotal <= 0) {

            return null;
        }


        return round(
            $this->clamp(
                $weightedTotal
                /
                $weightTotal
            ),
            2
        );
    }


    /**
     * Convert a raw value between a lower and upper benchmark
     * into the common 0-100 rating scale.
     */
    private function scale(
        float $value,
        float $minimum,
        float $maximum
    ): float {

        if (
            $maximum <= $minimum
        ) {

            return 0.0;
        }


        $rating =
            (
                $value
                -
                $minimum
            )
            /
            (
                $maximum
                -
                $minimum
            )
            *
            100;


        return round(
            $this->clamp(
                $rating
            ),
            2
        );
    }


    /**
     * Keep a rating inside the standard 0-100 scale.
     */
    private function clamp(
        float $value
    ): float {

        return max(
            0,
            min(
                100,
                $value
            )
        );
    }


    /**
     * Normalise FPL position.
     */
    private function normalisePosition(
        ?string $position
    ): string {

        $position =
            strtoupper(
                trim(
                    (string) $position
                )
            );


        return in_array(
            $position,
            [
                'GK',
                'DEF',
                'MID',
                'FWD'
            ],
            true
        )
            ? $position
            : 'MID';
    }


    /**
     * Round numeric diagnostics while preserving NULL.
     */
    private function roundNullable(
        ?float $value
    ): ?float {

        return $value !== null
            ? round(
                $value,
                2
            )
            : null;
    }
}