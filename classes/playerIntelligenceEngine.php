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
         * Complete player performance model
         * --------------------------------------------------------
         *
         * PlayerPerformance::buildModel() is responsible for:
         *
         * - Raw player statistics
         * - Per-90 calculations
         * - Normalised performance ratings
         *
         * The intelligence engine should use the complete model
         * rather than calling analyse() and then attempting to
         * reconstruct the ratings itself.
         */

        $performance =
            $this->performance->buildModel(
                $player
            );


        /*
         * --------------------------------------------------------
         * STEP 2
         * Player strength
         * --------------------------------------------------------
         *
         * PlayerStrengthModel applies the position-specific
         * weighting to the performance ratings.
         */

        $strength =
            $this->strength->buildModel(
                $performance
            );


        /*
         * --------------------------------------------------------
         * STEP 3
         * Player value
         * --------------------------------------------------------
         *
         * Value is calculated from player strength and price.
         */

        $value =
            $this->value->buildValueModel(
                $strength,
                $performance
            );


        /*
         * --------------------------------------------------------
         * STEP 4
         * Player availability
         * --------------------------------------------------------
         */

        $availability =
            $this->availability->buildAvailabilityModel(
                $performance
            );


        /*
         * --------------------------------------------------------
         * STEP 5
         * Overall intelligence score
         * --------------------------------------------------------
         *
         * The intelligence score combines:
         *
         * - Strength
         * - Value
         * - Availability
         * - Fixture rating
         *
         * Missing components are handled by
         * PlayerIntelligenceScore.
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