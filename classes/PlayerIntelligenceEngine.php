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

        $this->performance =
            $performance;

        $this->strength =
            $strength;

        $this->value =
            $value;

        $this->availability =
            $availability;

        $this->intelligenceScore =
            $intelligenceScore;
    }


    /**
     * Build a complete intelligence profile
     * for a single player.
     */
    public function analysePlayer(
        array $player,
        ?float $fixtureRating = null,
        ?int $availableMinutes = null
    ): array {

        /*
         * --------------------------------------------------------
         * STEP 1
         * Complete player performance model
         * --------------------------------------------------------
         */

        $performance =
            $this->performance
                ->buildModel(
                    $player,
                    $availableMinutes
                );


        /*
         * --------------------------------------------------------
         * STEP 2
         * Player strength
         * --------------------------------------------------------
         */

        $strength =
            $this->strength
                ->buildModel(
                    $performance
                );


        /*
         * --------------------------------------------------------
         * STEP 3
         * Player value
         * --------------------------------------------------------
         */

        $value =
            $this->value
                ->buildValueModel(
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
            $this->availability
                ->buildAvailabilityModel(
                    $performance
                );


        /*
         * --------------------------------------------------------
         * STEP 5
         * Overall intelligence score
         * --------------------------------------------------------
         */

        $intelligence =
            $this->intelligenceScore
                ->buildModel(
                    $strength,
                    $value,
                    $availability,
                    $fixtureRating
                );


        /*
         * --------------------------------------------------------
         * STEP 6
         * Resolve shared identity
         * --------------------------------------------------------
         */

        $playerId =
            (int) (
                $performance['player_id']
                ??
                $strength['player_id']
                ??
                $value['player_id']
                ??
                $availability['player_id']
                ??
                $intelligence['player_id']
                ??
                0
            );


        $fplPlayerId =
            (int) (
                $performance['fpl_player_id']
                ?? 0
            );


        $teamId =
            (int) (
                $performance['team_id']
                ?? 0
            );


        $name =
            $performance['name']
            ??
            $strength['name']
            ??
            $value['name']
            ??
            $availability['name']
            ??
            $intelligence['name']
            ??
            null;


        $position =
            $performance['position']
            ??
            $strength['position']
            ??
            $value['position']
            ??
            $availability['position']
            ??
            $intelligence['position']
            ??
            null;


        /*
         * --------------------------------------------------------
         * STEP 7
         * Decision-friendly summary
         * --------------------------------------------------------
         */

        $summary = [

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
                $fplPlayerId,

            'team_id' =>
                $teamId,

            'name' =>
                $name,

            'position' =>
                $position,

            'price' =>
                $value['price']
                ?? null,

            'strength_rating' =>
                $strength['strength_rating']
                ?? null,

            'value_rating' =>
                $value['value_rating']
                ?? null,

            'value_label' =>
                $value['value_label']
                ?? 'N/A',

            'availability_rating' =>
                $availability[
                    'availability_rating'
                ]
                ?? null,

            'reliability_rating' =>
                $availability[
                    'reliability_rating'
                ]
                ?? null,

            'availability_label' =>
                $availability[
                    'availability_label'
                ]
                ?? 'Unknown',

            'fixture_rating' =>
                $intelligence[
                    'fixture_rating'
                ]
                ?? null,

            'intelligence_score' =>
                $intelligence[
                    'intelligence_score'
                ]
                ?? null,

            'intelligence_label' =>
                $intelligence[
                    'intelligence_label'
                ]
                ?? 'Unknown'
        ];


        /*
         * --------------------------------------------------------
         * COMPLETE PLAYER PROFILE
         * --------------------------------------------------------
         */

        return [

            'player' => [

                'player_id' =>
                    $playerId,

                'fpl_player_id' =>
                    $fplPlayerId,

                'team_id' =>
                    $teamId,

                'name' =>
                    $name,

                'position' =>
                    $position
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