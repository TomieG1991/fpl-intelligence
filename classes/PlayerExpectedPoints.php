<?php

class PlayerExpectedPoints
{
    private ExpectedMinutes $expectedMinutes;

    private ExpectedPointsInputs $expectedPointsInputs;

    private ExpectedPoints $expectedPoints;

    private ProjectionConfidence $projectionConfidence;


    public function __construct(
        ExpectedMinutes $expectedMinutes,
        ExpectedPointsInputs $expectedPointsInputs,
        ExpectedPoints $expectedPoints,
        ProjectionConfidence $projectionConfidence
    ) {

        $this->expectedMinutes =
            $expectedMinutes;


        $this->expectedPointsInputs =
            $expectedPointsInputs;


        $this->expectedPoints =
            $expectedPoints;


        $this->projectionConfidence =
            $projectionConfidence;
    }


    /**
     * Build one complete, explainable player projection.
     */
    public function project(
        array $player,
        array $form,
        array $fixtureContext = []
    ): array {

        $position =
            strtoupper(
                trim(
                    (string) (
                        $player[
                            'position'
                        ]
                        ?? ''
                    )
                )
            );


        $playerId =
            (int) (
                $player[
                    'id'
                ]
                ?? 0
            );


        $fplPlayerId =
            (int) (
                $player[
                    'fpl_player_id'
                ]
                ?? 0
            );


        /*
         * --------------------------------------------------------
         * EXPECTED MINUTES
         * --------------------------------------------------------
         */

        $minutesModel =
            $this->expectedMinutes
                ->calculate(
                    $player,
                    $form
                );


        /*
         * --------------------------------------------------------
         * EXPECTED-POINTS INPUTS
         * --------------------------------------------------------
         */

        $inputsModel =
            $this->expectedPointsInputs
                ->build(
                    $position,
                    $minutesModel,
                    $form,
                    $fixtureContext
                );


        /*
         * --------------------------------------------------------
         * EXPECTED FPL POINTS
         * --------------------------------------------------------
         */

        $pointsModel =
            $this->expectedPoints
                ->calculate(
                    $position,
                    $inputsModel
                );


        /*
         * --------------------------------------------------------
         * PROJECTION CONFIDENCE
         * --------------------------------------------------------
         */

        $confidenceModel =
            $this->projectionConfidence
                ->calculate(
                    $minutesModel,
                    $form
                );


        return [

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
                $fplPlayerId,

            'position' =>
                $position,

            'projected_points' =>
                $pointsModel[
                    'projected_points'
                ]
                ?? null,

            'projected_minutes' =>
                $minutesModel[
                    'projected_minutes'
                ]
                ?? null,

            'projection_confidence' =>
                $confidenceModel[
                    'confidence'
                ]
                ?? null,

            'projection_confidence_percent' =>
                $confidenceModel[
                    'confidence_percent'
                ]
                ?? null,

            'projection_confidence_label' =>
                $confidenceModel[
                    'confidence_label'
                ]
                ?? null,

            'components' =>
                $pointsModel[
                    'components'
                ]
                ?? [],

            'inputs' =>
                $inputsModel,

            'expected_minutes_model' =>
                $minutesModel,

            'confidence_model' =>
                $confidenceModel
        ];
    }
}