<?php

class PlayerIntelligenceEngine
{
    private PlayerPerformance $performance;

    private PlayerStrengthModel $strength;

    private PlayerValue $value;

    private PlayerAvailability $availability;

    private PlayerIntelligenceScore $intelligenceScore;


    /**
     * Initialise the player intelligence engine.
     *
     * The engine coordinates the existing player models
     * without duplicating their calculation logic.
     */
    public function __construct(
        PlayerPerformance $performance,
        PlayerStrengthModel $strength,
        PlayerValue $value,
        PlayerAvailability $availability,
        PlayerIntelligenceScore $intelligenceScore
    ) {

        $this->performance = $performance;

        $this->strength = $strength;

        $this->value = $value;

        $this->availability = $availability;

        $this->intelligenceScore = $intelligenceScore;
    }


    /**
     * Build a complete intelligence profile
     * for a single player.
     */
    public function analysePlayer(
        array $player,
        ?float $fixtureRating = null
    ): array {

        /*
         * --------------------------------------------------------
         * STEP 1
         * Player performance
         * --------------------------------------------------------
         */

        $performance =
            $this->performance->analyse(
                $player
            );


        /*
         * Add per-90 performance metrics.
         */

        $performance['goals_per_90'] =
            $this->performance
                ->calculateGoalsPer90(
                    $performance
                );

        $performance['assists_per_90'] =
            $this->performance
                ->calculateAssistsPer90(
                    $performance
                );

        $performance['expected_goals_per_90'] =
            $this->performance
                ->calculateExpectedGoalsPer90(
                    $performance
                );

        $performance['expected_assists_per_90'] =
            $this->performance
                ->calculateExpectedAssistsPer90(
                    $performance
                );

        $performance['expected_goal_involvements_per_90'] =
            $this->performance
                ->calculateExpectedGoalInvolvementsPer90(
                    $performance
                );

        $performance['clean_sheets_per_90'] =
            $this->performance
                ->calculateCleanSheetsPer90(
                    $performance
                );


        /*
         * --------------------------------------------------------
         * STEP 2
         * Performance ratings
         * --------------------------------------------------------
         *
         * These are expected to already be supplied by the
         * performance/rating model used by the project.
         *
         * For the initial engine implementation, preserve
         * any existing rating values on the player data.
         */

        $performance['goals_rating'] =
            $player['goals_rating'] ?? null;

        $performance['assists_rating'] =
            $player['assists_rating'] ?? null;

        $performance['expected_goals_rating'] =
            $player['expected_goals_rating'] ?? null;

        $performance['expected_assists_rating'] =
            $player['expected_assists_rating'] ?? null;

        $performance['clean_sheets_rating'] =
            $player['clean_sheets_rating'] ?? null;

        $performance['bps_rating'] =
            $player['bps_rating'] ?? null;


        /*
         * --------------------------------------------------------
         * STEP 3
         * Player strength
         * --------------------------------------------------------
         */

        $strength =
            $this->strength->buildModel(
                $performance
            );


        /*
         * --------------------------------------------------------
         * STEP 4
         * Player value
         * --------------------------------------------------------
         */

        $value =
            $this->value->buildValueModel(
                $strength,
                $performance
            );


        /*
         * --------------------------------------------------------
         * STEP 5
         * Player availability
         * --------------------------------------------------------
         */

        $availability =
            $this->availability->buildAvailabilityModel(
                $performance
            );


        /*
         * --------------------------------------------------------
         * STEP 6
         * Overall intelligence score
         * --------------------------------------------------------
         */

        $intelligence =
            $this->intelligenceScore->buildModel(
                $strength,
                $value,
                $availability,
                $fixtureRating
            );


        /*
         * --------------------------------------------------------
         * COMPLETE PLAYER PROFILE
         * --------------------------------------------------------
         */

        return [

            'player' => [

                'player_id' =>
                    $performance['player_id'],

                'fpl_player_id' =>
                    $performance['fpl_player_id'],

                'team_id' =>
                    $performance['team_id'],

                'name' =>
                    $performance['name'],

                'position' =>
                    $performance['position']
            ],

            'performance' =>
                $performance,

            'strength' =>
                $strength,

            'value' =>
                $value,

            'availability' =>
                $availability,

            'intelligence' =>
                $intelligence
        ];
    }
}