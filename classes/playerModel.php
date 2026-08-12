<?php

class PlayerModel
{
    private PlayerPerformance $playerPerformance;

    private PlayerStrengthModel $playerStrengthModel;

    private PlayerValue $playerValue;

    private PlayerAvailability $playerAvailability;


    public function __construct(
        PlayerPerformance $playerPerformance,
        PlayerStrengthModel $playerStrengthModel,
        PlayerValue $playerValue,
        PlayerAvailability $playerAvailability
    ) {

        $this->playerPerformance =
            $playerPerformance;

        $this->playerStrengthModel =
            $playerStrengthModel;

        $this->playerValue =
            $playerValue;

        $this->playerAvailability =
            $playerAvailability;
    }


    /**
     * Build the complete player intelligence model.
     *
     * This combines:
     *
     * - Player performance
     * - Player strength
     * - Player value
     * - Player availability
     */
    public function buildModel(
        array $player
    ): array {

        /*
         * --------------------------------------------------------
         * Performance
         * --------------------------------------------------------
         */

        $performance =
            $this->playerPerformance->buildModel(
                $player
            );


        /*
         * --------------------------------------------------------
         * Strength
         * --------------------------------------------------------
         */

        $strength =
            $this->playerStrengthModel->buildModel(
                $performance
            );


        /*
         * --------------------------------------------------------
         * Value
         * --------------------------------------------------------
         */

        $value =
            $this->playerValue->buildValueModel(
                $strength,
                $performance
            );


        /*
         * --------------------------------------------------------
         * Availability
         * --------------------------------------------------------
         */

        $availability =
            $this->playerAvailability->buildAvailabilityModel(
                $player
            );


        /*
         * --------------------------------------------------------
         * Combined model
         * --------------------------------------------------------
         */

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
                $performance['position'],

            'performance' =>
                $performance,

            'strength' =>
                $strength,

            'value' =>
                $value,

            'availability' =>
                $availability
        ];
    }
}