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
         * STEP 6
         * Decision-friendly summary
         * --------------------------------------------------------
         *
         * The complete intelligence engine deliberately keeps
         * the detailed models separated.
         *
         * Ranking, recommendation and transfer layers do not
         * need all of that detail, so expose a flat summary
         * containing the fields those models require.
         *
         * This allows the detailed profile to remain the source
         * of truth while providing a stable interface for later
         * decision layers.
         */

        $summary = [

            'player_id' =>
                $performance['player_id'],

            'fpl_player_id' =>
                $performance['fpl_player_id'],

            'team_id' =>
                $performance['team_id'],

            'name' =>
                $performance['name'],

            'position' =>
                $performance['position'],

            'price' =>
                $value['price'],

            'strength_rating' =>
                $strength['strength_rating'],

            'value_rating' =>
                $value['value_rating'],

            'value_label' =>
                $value['value_label'],

            'availability_rating' =>
                $availability['availability_rating'],

            'reliability_rating' =>
                $availability['reliability_rating'],

            'availability_label' =>
                $availability['availability_label'],

            'fixture_rating' =>
                $intelligence['fixture_rating'],

            'intelligence_score' =>
                $intelligence['intelligence_score'],

            'intelligence_label' =>
                $intelligence['intelligence_label']
        ];


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
                $intelligence,

            'summary' =>
                $summary
        ];
    }
}