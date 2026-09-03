<?php

class WildcardDecisionIntelligenceService
{
    private SquadHorizonIntelligenceService
        $squadHorizonIntelligenceService;


    private WildcardHorizonIntelligenceService
        $wildcardHorizonIntelligenceService;


    private WildcardTimingIntelligenceService
        $wildcardTimingIntelligenceService;


    public function __construct(
        SquadHorizonIntelligenceService $squadHorizonIntelligenceService,
        WildcardHorizonIntelligenceService $wildcardHorizonIntelligenceService,
        WildcardTimingIntelligenceService $wildcardTimingIntelligenceService
    ) {

        $this->squadHorizonIntelligenceService =
            $squadHorizonIntelligenceService;


        $this->wildcardHorizonIntelligenceService =
            $wildcardHorizonIntelligenceService;


        $this->wildcardTimingIntelligenceService =
            $wildcardTimingIntelligenceService;
    }


    public function build(
        array $importedSquad,
        array $players,
        float $budget,
        int $horizon
    ): array {

        /*
         * --------------------------------------------------------
         * BUILD CURRENT SQUAD HORIZON
         * --------------------------------------------------------
         */

        $currentHorizonBuild =
            $this->squadHorizonIntelligenceService
                ->buildForImportedSquad(
                    $importedSquad,
                    $horizon
                );


        /*
         * --------------------------------------------------------
         * REQUIRE CURRENT SQUAD HORIZON
         * --------------------------------------------------------
         */

        if (
            (
                $currentHorizonBuild[
                    'status'
                ]
                ??
                null
            )
            !==
            'Available'
        ) {

            return [

                'status' =>
                    'Unavailable',

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'wildcard_horizon_result' =>
                    null,

                'timing_result' =>
                    null
            ];
        }


        /*
         * --------------------------------------------------------
         * BUILD WILDCARD SQUAD HORIZON
         * --------------------------------------------------------
         */

        $wildcardHorizonBuild =
            $this->wildcardHorizonIntelligenceService
                ->build(
                    $players,
                    $budget,
                    $horizon
                );
                
                
        /*
         * --------------------------------------------------------
         * REQUIRE WILDCARD SQUAD HORIZON
         * --------------------------------------------------------
         */

        if (
            (
                $wildcardHorizonBuild[
                    'status'
                ]
                ??
                null
            )
            !==
            'Available'
        ) {

            return [

                'status' =>
                    'Unavailable',

                'current_horizon_result' =>
                    $currentHorizonBuild,

                'wildcard_horizon_result' =>
                    $wildcardHorizonBuild,

                'timing_result' =>
                    null
            ];
        }
        

        /*
         * --------------------------------------------------------
         * COMPARE PROJECTED HORIZONS
         * --------------------------------------------------------
         */

        $timingResult =
            $this->wildcardTimingIntelligenceService
                ->analyseHorizons(
                    $currentHorizonBuild[
                        'horizon_result'
                    ],
                    $wildcardHorizonBuild[
                        'horizon_result'
                    ]
                );


        /*
         * --------------------------------------------------------
         * RESULT
         * --------------------------------------------------------
         */

        return [

            'status' =>
                (
                    (
                        $timingResult[
                            'status'
                        ]
                        ??
                        null
                    )
                    ===
                    'Available'
                )
                    ? 'Available'
                    : 'Unavailable',

            'current_horizon_result' =>
                $currentHorizonBuild,

            'wildcard_horizon_result' =>
                $wildcardHorizonBuild,

            'timing_result' =>
                $timingResult
        ];
    }
}