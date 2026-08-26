<?php

class ExpectedPoints
{
    /**
     * Calculate projected FPL points for one upcoming fixture.
     *
     * This class translates expected football outcomes into
     * official FPL scoring.
     *
     * It deliberately does NOT decide how the underlying
     * expectations are generated.
     *
     * Inputs such as:
     *
     * - expected goals
     * - expected assists
     * - clean-sheet probability
     * - expected saves
     * - expected bonus
     *
     * are supplied by upstream projection components.
     */
    public function calculate(
        string $position,
        array $inputs
    ): array {

        $position =
            strtoupper(
                trim(
                    $position
                )
            );


        if (
            !in_array(
                $position,
                [
                    'GK',
                    'DEF',
                    'MID',
                    'FWD'
                ],
                true
            )
        ) {

            return $this->emptyModel(
                $position
            );
        }


        $projectedMinutes =
            $this->bounded(
                $inputs[
                    'projected_minutes'
                ]
                ?? 0,
                0,
                90
            );


        $expectedGoals =
            $this->nonNegative(
                $inputs[
                    'expected_goals'
                ]
                ?? 0
            );


        $expectedAssists =
            $this->nonNegative(
                $inputs[
                    'expected_assists'
                ]
                ?? 0
            );


        $cleanSheetProbability =
            $this->bounded(
                $inputs[
                    'clean_sheet_probability'
                ]
                ?? 0,
                0,
                100
            );


        $expectedSaves =
            $this->nonNegative(
                $inputs[
                    'expected_saves'
                ]
                ?? 0
            );


        $expectedBonus =
            $this->bounded(
                $inputs[
                    'expected_bonus'
                ]
                ?? 0,
                0,
                3
            );


        $expectedDefensiveContributionPoints =
            $this->bounded(
                $inputs[
                    'expected_defensive_contribution_points'
                ]
                ?? 0,
                0,
                2
            );


        /*
         * --------------------------------------------------------
         * APPEARANCE POINTS
         * --------------------------------------------------------
         *
         * Expected minutes is continuous, so appearance points
         * are projected proportionally.
         *
         * 0 minutes  -> 0 points
         * 30 minutes -> approximately 1 point
         * 60+        -> approaches 2 points
         *
         * This avoids a hard discontinuity in a probabilistic
         * projection.
         */

        $appearancePoints =
            $this->calculateAppearancePoints(
                $projectedMinutes
            );


        /*
         * --------------------------------------------------------
         * GOAL POINTS
         * --------------------------------------------------------
         */

        $goalPointsPerGoal =
            $this->goalPointsForPosition(
                $position
            );


        $goalPoints =
            $expectedGoals
            *
            $goalPointsPerGoal;


        /*
         * --------------------------------------------------------
         * ASSISTS
         * --------------------------------------------------------
         */

        $assistPoints =
            $expectedAssists
            *
            3.0;


        /*
         * --------------------------------------------------------
         * CLEAN SHEETS
         * --------------------------------------------------------
         *
         * Clean-sheet points require at least 60 minutes in FPL.
         *
         * We therefore scale clean-sheet eligibility according
         * to the player's projected probability of reaching the
         * 60-minute threshold.
         */

        $cleanSheetEligibility =
            $this->calculateSixtyMinuteEligibility(
                $projectedMinutes
            );


        $cleanSheetPointsPerCleanSheet =
            $this->cleanSheetPointsForPosition(
                $position
            );


        $cleanSheetPoints =
            (
                $cleanSheetProbability
                /
                100
            )
            *
            $cleanSheetEligibility
            *
            $cleanSheetPointsPerCleanSheet;


        /*
         * --------------------------------------------------------
         * GOALKEEPER SAVES
         * --------------------------------------------------------
         *
         * One FPL point is awarded for each complete group
         * of three saves.
         *
         * For projection purposes we use the continuous expected
         * equivalent rather than flooring the value.
         */

        $savePoints =
            $position === 'GK'
                ? $expectedSaves
                    /
                    3
                : 0.0;


        /*
         * --------------------------------------------------------
         * BONUS
         * --------------------------------------------------------
         */

        $bonusPoints =
            $expectedBonus;


        /*
         * --------------------------------------------------------
         * DEFENSIVE CONTRIBUTIONS
         * --------------------------------------------------------
         *
         * Upstream models will later estimate the probability
         * of achieving the official defensive-contribution
         * threshold.
         *
         * The core points engine simply accepts the expected
         * points contribution on the legal 0-2 scale.
         */

        $defensiveContributionPoints =
            $position === 'GK'
                ? 0.0
                : $expectedDefensiveContributionPoints;


        /*
         * --------------------------------------------------------
         * TOTAL
         * --------------------------------------------------------
         */

        $projectedPoints =
            $appearancePoints
            +
            $goalPoints
            +
            $assistPoints
            +
            $cleanSheetPoints
            +
            $savePoints
            +
            $bonusPoints
            +
            $defensiveContributionPoints;


        return [

            'position' =>
                $position,

            'projected_points' =>
                round(
                    max(
                        0.0,
                        $projectedPoints
                    ),
                    2
                ),

            'projected_minutes' =>
                round(
                    $projectedMinutes,
                    2
                ),

            'components' => [

                'appearance' =>
                    round(
                        $appearancePoints,
                        2
                    ),

                'goals' =>
                    round(
                        $goalPoints,
                        2
                    ),

                'assists' =>
                    round(
                        $assistPoints,
                        2
                    ),

                'clean_sheet' =>
                    round(
                        $cleanSheetPoints,
                        2
                    ),

                'saves' =>
                    round(
                        $savePoints,
                        2
                    ),

                'bonus' =>
                    round(
                        $bonusPoints,
                        2
                    ),

                'defensive_contributions' =>
                    round(
                        $defensiveContributionPoints,
                        2
                    )
            ],

            'inputs' => [

                'expected_goals' =>
                    round(
                        $expectedGoals,
                        4
                    ),

                'expected_assists' =>
                    round(
                        $expectedAssists,
                        4
                    ),

                'clean_sheet_probability' =>
                    round(
                        $cleanSheetProbability,
                        2
                    ),

                'expected_saves' =>
                    round(
                        $expectedSaves,
                        2
                    ),

                'expected_bonus' =>
                    round(
                        $expectedBonus,
                        2
                    ),

                'expected_defensive_contribution_points' =>
                    round(
                        $expectedDefensiveContributionPoints,
                        2
                    )
            ]
        ];
    }


    /**
     * Expected appearance points from projected minutes.
     *
     * The projection is continuous rather than binary because
     * projected minutes represent an expectation, not a known
     * realised match duration.
     */
    public function calculateAppearancePoints(
        float $minutes
    ): float {

        $minutes =
            max(
                0.0,
                min(
                    90.0,
                    $minutes
                )
            );


        if (
            $minutes <= 0
        ) {

            return 0.0;
        }


        if (
            $minutes < 60
        ) {

            return min(
                1.0,
                $minutes
                /
                30
            );
        }


        return min(
            2.0,
            1.0
            +
            (
                (
                    $minutes
                    -
                    60
                )
                /
                30
            )
        );
    }


    /**
     * Estimate eligibility for clean-sheet scoring.
     *
     * The player needs 60 minutes for clean-sheet points.
     *
     * At 60+ projected minutes we treat eligibility as complete.
     * Below that, eligibility ramps toward the threshold.
     */
    public function calculateSixtyMinuteEligibility(
        float $minutes
    ): float {

        $minutes =
            max(
                0.0,
                min(
                    90.0,
                    $minutes
                )
            );


        if (
            $minutes >= 60
        ) {

            return 1.0;
        }


        return $minutes
            /
            60;
    }


    /**
     * Official FPL goal points by position.
     */
    public function goalPointsForPosition(
        string $position
    ): float {

        return match (
            strtoupper(
                trim(
                    $position
                )
            )
        ) {

            'GK' =>
                10.0,

            'DEF' =>
                6.0,

            'MID' =>
                5.0,

            'FWD' =>
                4.0,

            default =>
                0.0
        };
    }


    /**
     * Official FPL clean-sheet points by position.
     */
    public function cleanSheetPointsForPosition(
        string $position
    ): float {

        return match (
            strtoupper(
                trim(
                    $position
                )
            )
        ) {

            'GK',
            'DEF' =>
                4.0,

            'MID' =>
                1.0,

            default =>
                0.0
        };
    }


    /**
     * Empty controlled output for unsupported positions.
     */
    private function emptyModel(
        string $position
    ): array {

        return [

            'position' =>
                $position,

            'projected_points' =>
                null,

            'projected_minutes' =>
                0.0,

            'components' => [

                'appearance' =>
                    0.0,

                'goals' =>
                    0.0,

                'assists' =>
                    0.0,

                'clean_sheet' =>
                    0.0,

                'saves' =>
                    0.0,

                'bonus' =>
                    0.0,

                'defensive_contributions' =>
                    0.0
            ],

            'inputs' =>
                []
        ];
    }


    /**
     * Convert a value to a non-negative float.
     */
    private function nonNegative(
        mixed $value
    ): float {

        if (
            !is_numeric(
                $value
            )
        ) {

            return 0.0;
        }


        return max(
            0.0,
            (float) $value
        );
    }


    /**
     * Clamp a numeric value.
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