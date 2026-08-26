<?php

class ExpectedPointsInputs
{
    
        private ExpectedDefensiveContributions
            $expectedDefensiveContributions;

        private ExpectedBonus
            $expectedBonus;
            
        private ExpectedGoalsConceded
            $expectedGoalsConceded;

    /**
     * Initialise Expected Points input modelling.
     *
     * The dependency remains optional so existing callers and
     * tests using new ExpectedPointsInputs() continue to work.
     */
     
   public function __construct(
    ?ExpectedDefensiveContributions
        $expectedDefensiveContributions = null,
    ?ExpectedBonus
        $expectedBonus = null,
    ?ExpectedGoalsConceded
        $expectedGoalsConceded = null
) {

    $this->expectedDefensiveContributions =
        $expectedDefensiveContributions
        ??
        new ExpectedDefensiveContributions();


    $this->expectedBonus =
        $expectedBonus
        ??
        new ExpectedBonus();


    $this->expectedGoalsConceded =
        $expectedGoalsConceded
        ??
        new ExpectedGoalsConceded();
}
    
    /**
     * Build the inputs consumed by ExpectedPoints.
     *
     * This class converts existing intelligence evidence into
     * expected football outcomes for one upcoming fixture.
     *
     * It deliberately remains separate from ExpectedPoints,
     * which owns the translation from expected outcomes into
     * official FPL scoring.
     */
    public function build(
        string $position,
        array $expectedMinutes,
        array $form,
        array $fixtureContext = []
    ): array {

        $position =
            strtoupper(
                trim(
                    $position
                )
            );


        $projectedMinutes =
            $this->bounded(
                $expectedMinutes[
                    'projected_minutes'
                ]
                ?? 0,
                0,
                90
            );


        $weightedMetrics =
            is_array(
                $form[
                    'weighted_metrics'
                ]
                ?? null
            )
                ? $form[
                    'weighted_metrics'
                ]
                : [];


        $fixtureSampleSize =
            max(
                0,
                (int) (
                    $form[
                        'fixture_sample_size'
                    ]
                    ?? 0
                )
            );


        $appearanceSampleSize =
            max(
                0,
                (int) (
                    $form[
                        'appearance_sample_size'
                    ]
                    ?? 0
                )
            );


        /*
         * ========================================================
         * FIXTURE OPPORTUNITY
         * ========================================================
         *
         * 50 is neutral.
         *
         * 0   -> 0.75 multiplier
         * 50  -> 1.00 multiplier
         * 100 -> 1.25 multiplier
         *
         * This deliberately keeps fixture influence conservative.
         */

        $fixtureOpportunity =
            $this->nullableBounded(
                $fixtureContext[
                    'fixture_opportunity'
                ]
                ?? null,
                0,
                100
            );


        $fixtureMultiplier =
            $fixtureOpportunity !== null
                ? 0.75
                    +
                    (
                        $fixtureOpportunity
                        /
                        200
                    )
                : 1.0;


        /*
         * ========================================================
         * ATTACKING EXPECTATION
         * ========================================================
         */

        $expectedGoalsPer90 =
            $this->nullableNonNegative(
                $weightedMetrics[
                    'expected_goals_per_90'
                ]
                ?? null
            );


        $expectedAssistsPer90 =
            $this->nullableNonNegative(
                $weightedMetrics[
                    'expected_assists_per_90'
                ]
                ?? null
            );


        $expectedGoals =
            $expectedGoalsPer90 !== null
                ? (
                    $expectedGoalsPer90
                    *
                    (
                        $projectedMinutes
                        /
                        90
                    )
                    *
                    $fixtureMultiplier
                )
                : 0.0;


        $expectedAssists =
            $expectedAssistsPer90 !== null
                ? (
                    $expectedAssistsPer90
                    *
                    (
                        $projectedMinutes
                        /
                        90
                    )
                    *
                    $fixtureMultiplier
                )
                : 0.0;


        /*
         * ========================================================
         * CLEAN-SHEET EXPECTATION
         * ========================================================
         *
         * Recent clean-sheet rate is useful evidence, but small
         * samples must not be treated as mature probability.
         *
         * Regress toward a conservative 30% neutral prior.
         *
         * Five fixture-history rows represents full recent-window
         * evidence for this first model.
         */

        $recentCleanSheetRate =
            $this->nullableBounded(
                $weightedMetrics[
                    'clean_sheet_rate'
                ]
                ?? null,
                0,
                100
            );


        $cleanSheetSampleConfidence =
            min(
                1.0,
                $fixtureSampleSize
                /
                5
            );


        $neutralCleanSheetProbability =
            30.0;


        if (
            $recentCleanSheetRate !== null
        ) {

            $baseCleanSheetProbability =
                (
                    $recentCleanSheetRate
                    *
                    $cleanSheetSampleConfidence
                )
                +
                (
                    $neutralCleanSheetProbability
                    *
                    (
                        1
                        -
                        $cleanSheetSampleConfidence
                    )
                );

        } else {

            $baseCleanSheetProbability =
                $neutralCleanSheetProbability;
        }


        /*
         * Defensive fixture context is position-independent at
         * team level:
         *
         * weak opponent attack -> better clean-sheet probability
         * strong opponent attack -> worse clean-sheet probability
         */

        $opponentAttackRating =
            $this->nullableBounded(
                $fixtureContext[
                    'opponent_attack_rating'
                ]
                ?? null,
                0,
                100
            );


        /*
         * --------------------------------------------------------
         * DEFENSIVE FIXTURE CONTEXT
         * --------------------------------------------------------
         *
         * Defensive projections should primarily respond to the
         * opponent's attacking strength, but should also retain
         * broader Fixture Intelligence context.
         *
         * Fixture opportunity is expressed from the player's
         * perspective:
         *
         * 100 = very favourable
         *   0 = very difficult
         *
         * Convert it into defensive threat before blending:
         *
         * 100 opportunity -> 0 threat
         *   0 opportunity -> 100 threat
         *
         * Opponent Attack Rating remains the dominant signal.
         */

        $fixtureDefensiveThreat =
            $fixtureOpportunity !== null
                ? 100.0 - $fixtureOpportunity
                : null;


        if (
            $opponentAttackRating !== null
            &&
            $fixtureDefensiveThreat !== null
        ) {

            $defensiveThreatRating =
                (
                    $opponentAttackRating
                    *
                    0.75
                )
                +
                (
                    $fixtureDefensiveThreat
                    *
                    0.25
                );

        } elseif (
            $opponentAttackRating !== null
        ) {

            $defensiveThreatRating =
                $opponentAttackRating;

        } elseif (
            $fixtureDefensiveThreat !== null
        ) {

            $defensiveThreatRating =
                $fixtureDefensiveThreat;

        } else {

            $defensiveThreatRating =
                null;
        }


        $defensiveFixtureMultiplier =
            $defensiveThreatRating !== null
                ? 1.25
                    -
                    (
                        $defensiveThreatRating
                        /
                        200
                    )
                : 1.0;


        $cleanSheetProbability =
            $baseCleanSheetProbability
            *
            $defensiveFixtureMultiplier;


        $cleanSheetProbability =
            max(
                0.0,
                min(
                    100.0,
                    $cleanSheetProbability
                )
            );

        /*
         * ========================================================
         * GOALS-CONCEDED EXPECTATION
         * ========================================================
         *
         * Goalkeepers and defenders lose one FPL point for every
         * two goals conceded.
         *
         * The dedicated model uses:
         *
         * - recency-weighted xGC / 90
         * - position baseline regression
         * - projected minutes
         * - opponent Attack Rating
         * - probabilistic Poisson scoring
         */

        $goalsConcededModel =
            $this->expectedGoalsConceded
                ->calculate(
                    $position,
                    $projectedMinutes,
                    $form,
                    [
                        'opponent_attack_rating' =>
                            $opponentAttackRating,

                        'fixture_opportunity' =>
                            $fixtureOpportunity
                    ]
                );


        $expectedGoalsConcededPoints =
            min(
                0.0,
                (float) (
                    $goalsConcededModel[
                        'expected_points'
                    ]
                    ?? 0
                )
            );

        /*
         * ========================================================
         * GOALKEEPER SAVE EXPECTATION
         * ========================================================
         *
         * Goalkeeper save expectation uses recent recency-weighted
         * saves per 90, scaled by projected minutes.
         *
         * Stronger opponent attacks can generate more save
         * opportunities, so opponent Attack Rating applies a
         * conservative multiplier:
         *
         * 0   -> 0.85
         * 50  -> 1.00
         * 100 -> 1.15
         */

        $weightedSavesPer90 =
            $position === 'GK'
                ? $this->nullableNonNegative(
                    $weightedMetrics[
                        'saves_per_90'
                    ]
                    ?? null
                )
                : null;


        $saveOpportunityMultiplier =
            $opponentAttackRating !== null
                ? 0.85
                    +
                    (
                        $opponentAttackRating
                        *
                        0.003
                    )
                : 1.0;


        $expectedSaves =
            (
                $position === 'GK'
                &&
                $weightedSavesPer90 !== null
            )
                ? (
                    $weightedSavesPer90
                    *
                    (
                        $projectedMinutes
                        /
                        90
                    )
                    *
                    $saveOpportunityMultiplier
                )
                : 0.0;


        /*
         * ========================================================
         * BONUS EXPECTATION
         * ========================================================
         *
         * Expected Bonus uses:
         *
         * - recency-weighted BPS / 90
         * - position baseline regression
         * - projected minutes
         * - empirical 2026/27 BPS-to-bonus curve
         */

        $bonusModel =
            $this->expectedBonus
                ->calculate(
                    $position,
                    $projectedMinutes,
                    $form
                );


        $expectedBonus =
            max(
                0.0,
                min(
                    3.0,
                    (float) (
                        $bonusModel[
                            'expected_points'
                        ]
                        ?? 0
                    )
                )
            );


        /*
         * ========================================================
         * DEFENSIVE CONTRIBUTION EXPECTATION
         * ========================================================
         *
         * Uses the dedicated 2026/27 threshold model:
         *
         * DEF
         * -> CBIT >= 10
         *
         * MID / FWD
         * -> CBIRT >= 12
         *
         * GK
         * -> Not Applicable
         */

        $defensiveContributionModel =
            $this->expectedDefensiveContributions
                ->calculate(
                    $position,
                    $projectedMinutes,
                    $form,
                    [
                        'opponent_attack_rating' =>
                            $opponentAttackRating
                    ]
                );


        $expectedDefensiveContributionPoints =
            max(
                0.0,
                min(
                    2.0,
                    (float) (
                        $defensiveContributionModel[
                            'expected_points'
                        ]
                        ?? 0
                    )
                )
            );


        return [

            'projected_minutes' =>
                round(
                    $projectedMinutes,
                    2
                ),

            'expected_goals' =>
                round(
                    max(
                        0.0,
                        $expectedGoals
                    ),
                    4
                ),

            'expected_assists' =>
                round(
                    max(
                        0.0,
                        $expectedAssists
                    ),
                    4
                ),

            'clean_sheet_probability' =>
                round(
                    $cleanSheetProbability,
                    2
                ),
                
            'expected_goals_conceded_points' =>
                round(
                    $expectedGoalsConcededPoints,
                    4
                ),

            'expected_saves' =>
                $expectedSaves,

            'expected_bonus' =>
                $expectedBonus,

            'expected_defensive_contribution_points' =>
                $expectedDefensiveContributionPoints,

            /*
             * Explainability.
             */
            'evidence' => [

                'expected_goals_per_90' =>
                    $expectedGoalsPer90,

                'expected_assists_per_90' =>
                    $expectedAssistsPer90,

                'recent_clean_sheet_rate' =>
                    $recentCleanSheetRate,

                'fixture_opportunity' =>
                    $fixtureOpportunity,

                'fixture_multiplier' =>
                    round(
                        $fixtureMultiplier,
                        4
                    ),

                'opponent_attack_rating' =>
                    $opponentAttackRating,

                'defensive_fixture_multiplier' =>
                    round(
                        $defensiveFixtureMultiplier,
                        4
                    ),

                'clean_sheet_sample_confidence' =>
                    round(
                        $cleanSheetSampleConfidence,
                        4
                    ),
                    
                'saves_per_90' =>
                    $weightedSavesPer90,

                'save_opportunity_multiplier' =>
                    round(
                        $saveOpportunityMultiplier,
                        4
                    ),
                    
                'defensive_contributions' =>
                    $defensiveContributionModel,
                    
                'goals_conceded' =>
                    $goalsConcededModel,
                    
                'bonus' =>
                    $bonusModel,
            ],

            'sample' => [

                'fixture_sample_size' =>
                    $fixtureSampleSize,

                'appearance_sample_size' =>
                    $appearanceSampleSize
            ],

            'specialist_components' => [

                'saves' =>
                    $position === 'GK'
                        ? (
                            $weightedSavesPer90 !== null
                                ? 'Modelled'
                                : 'Insufficient Data'
                        )
                        : 'Not Applicable',

                'bonus' =>
                    $bonusModel[
                        'status'
                    ]
                    ?? 'Insufficient Data',

                'defensive_contributions' =>
                    $defensiveContributionModel[
                        'status'
                    ]
                    ?? 'Insufficient Data',
                    
                'goals_conceded' =>
                    $goalsConcededModel[
                        'status'
                    ]
                    ?? 'Insufficient Data',
            ]
        ];
    }


    /**
     * Return a non-negative nullable number.
     */
    private function nullableNonNegative(
        mixed $value
    ): ?float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return max(
            0.0,
            (float) $value
        );
    }


    /**
     * Return a bounded nullable number.
     */
    private function nullableBounded(
        mixed $value,
        float $minimum,
        float $maximum
    ): ?float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return max(
            $minimum,
            min(
                $maximum,
                (float) $value
            )
        );
    }


    /**
     * Return a bounded numeric value.
     */
    private function bounded(
        mixed $value,
        float $minimum,
        float $maximum
    ): float {

        if (
            !is_numeric(
                $value
            )
        ) {

            return $minimum;
        }


        return max(
            $minimum,
            min(
                $maximum,
                (float) $value
            )
        );
    }
}