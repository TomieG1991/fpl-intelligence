<?php

class BenchBoostDecisionIntelligenceService
{
    private SquadHorizonIntelligenceService
        $squadHorizonIntelligenceService;

    private BenchBoostIntelligence
        $benchBoostIntelligence;


    public function __construct(
        SquadHorizonIntelligenceService $squadHorizonIntelligenceService,
        BenchBoostIntelligence $benchBoostIntelligence
    ) {
        $this->squadHorizonIntelligenceService =
            $squadHorizonIntelligenceService;

        $this->benchBoostIntelligence =
            $benchBoostIntelligence;
    }


    public function build(
        array $importedSquad
    ): array {

        /*
         * --------------------------------------------------------
         * BUILD CURRENT SQUAD ONE-GAMEWEEK HORIZON
         * --------------------------------------------------------
         */

        $currentHorizonResult =
            $this->squadHorizonIntelligenceService
                ->buildForImportedSquad(
                    $importedSquad,
                    1
                );


        if (
            (
                $currentHorizonResult[
                    'status'
                ]
                ?? null
            )
            !==
            'Available'
        ) {

            return $this->unavailableResult(
                $currentHorizonResult
            );
        }


        $horizonResult =
            $currentHorizonResult[
                'horizon_result'
            ]
            ?? null;


        if (
            !is_array(
                $horizonResult
            )
            ||
            (
                $horizonResult[
                    'status'
                ]
                ?? null
            )
            !==
            'Available'
        ) {

            return $this->unavailableResult(
                $currentHorizonResult
            );
        }


        /*
         * --------------------------------------------------------
         * REQUIRE EXACTLY ONE REPRESENTED GAMEWEEK
         * --------------------------------------------------------
         *
         * Bench Boost is a one-gameweek chip.
         *
         * The service requests a horizon of one, so a valid result
         * must expose exactly one represented gameweek.
         */

        $gameweeks =
            isset(
                $horizonResult[
                    'gameweeks'
                ]
            )
            &&
            is_array(
                $horizonResult[
                    'gameweeks'
                ]
            )
                ? $horizonResult[
                    'gameweeks'
                ]
                : [];


        if (
            count(
                $gameweeks
            )
            !==
            1
        ) {

            return $this->unavailableResult(
                $currentHorizonResult
            );
        }


        $gameweek =
            reset(
                $gameweeks
            );


        if (
            !is_array(
                $gameweek
            )
        ) {

            return $this->unavailableResult(
                $currentHorizonResult
            );
        }


        /*
         * --------------------------------------------------------
         * RUN BENCH BOOST ANALYSIS
         * --------------------------------------------------------
         */

        $analysis =
            $this->benchBoostIntelligence
                ->analyse(
                    $gameweek
                );


        /*
         * --------------------------------------------------------
         * CREATE CHIP DECISION
         * --------------------------------------------------------
         */

        $decision =
            $this->benchBoostIntelligence
                ->createDecision(
                    $analysis
                );


        return [

            'status' =>
                'Available',

            'current_horizon_result' =>
                $currentHorizonResult,

            'analysis' =>
                $analysis,

            'decision' =>
                $decision
        ];
    }


    private function unavailableResult(
        array $currentHorizonResult
    ): array {

        return [

            'status' =>
                'Unavailable',

            'current_horizon_result' =>
                $currentHorizonResult,

            'analysis' =>
                null,

            'decision' =>
                null
        ];
    }
}