<?php

class WildcardHorizonIntelligenceService
{
    private WildcardOptimizer
        $wildcardOptimizer;


    private SquadHorizonIntelligenceService
        $squadHorizonIntelligenceService;


    public function __construct(
        WildcardOptimizer $wildcardOptimizer,
        SquadHorizonIntelligenceService $squadHorizonIntelligenceService
    ) {

        $this->wildcardOptimizer =
            $wildcardOptimizer;


        $this->squadHorizonIntelligenceService =
            $squadHorizonIntelligenceService;
    }


    public function build(
        array $players,
        float $budget,
        int $horizon
    ): array {

        /*
         * --------------------------------------------------------
         * OPTIMIZE WILDCARD SQUAD
         * --------------------------------------------------------
         */

        $optimizerResult =
            $this->wildcardOptimizer
                ->optimize(
                    $players,
                    $budget
                );
                
                
        /*
         * ============================================================
         * STOP IF OPTIMIZATION FAILED
         * ============================================================
         */

        if (
            (
                $optimizerResult[
                    'status'
                ]
                ??
                null
            )
            !==
            'success'
        ) {

            return [
                'status' =>
                    'Unavailable',

                'optimizer_result' =>
                    $optimizerResult
            ];
        }


        /*
         * ============================================================
         * STOP IF OPTIMIZER SQUAD IS INVALID
         * ============================================================
         */

        if (
            !array_key_exists(
                'squad',
                $optimizerResult
            )
            ||
            !is_array(
                $optimizerResult[
                    'squad'
                ]
            )
            ||
            count(
                $optimizerResult[
                    'squad'
                ]
            )
            !==
            15
        ) {

            return [
                'status' =>
                    'Unavailable',

                'optimizer_result' =>
                    $optimizerResult
            ];
        }


        /*
         * --------------------------------------------------------
         * ADAPT OPTIMIZER PLAYER CONTRACT
         * --------------------------------------------------------
         *
         * WildcardOptimizer exposes local player identity as
         * player_id.
         *
         * SquadHorizonIntelligenceService expects the resolved
         * local-player contract to expose that same identity as id.
         *
         * Preserve the complete optimizer record and add the
         * resolved-squad identity field.
         */

        $resolvedPlayers =
            [];


        $resolvedPlayerIds =
            [];


        foreach (
            $optimizerResult[
                'squad'
            ]
            as $player
        ) {

            if (
                !isset(
                    $player[
                        'player_id'
                    ]
                )
                ||
                !is_numeric(
                    $player[
                        'player_id'
                    ]
                )
                ||
                (int) $player[
                    'player_id'
                ]
                <=
                0
            ) {

                return [
                    'status' =>
                        'Unavailable',

                    'optimizer_result' =>
                        $optimizerResult
                ];
            }
            
            $playerId =
                (int) $player[
                    'player_id'
                ];


            if (
                in_array(
                    $playerId,
                    $resolvedPlayerIds,
                    true
                )
            ) {

                return [
                    'status' =>
                        'Unavailable',

                    'optimizer_result' =>
                        $optimizerResult
                ];
            }


            $resolvedPlayerIds[] =
                $playerId;


            $resolvedPlayer =
                $player;


            $resolvedPlayer[
                'id'
            ] =
                $playerId;


            $resolvedPlayers[] =
                $resolvedPlayer;
        }


        /*
         * --------------------------------------------------------
         * BUILD WILDCARD SQUAD HORIZON
         * --------------------------------------------------------
         */

        $horizonBuild =
            $this->squadHorizonIntelligenceService
                ->buildForResolvedSquad(
                    $resolvedPlayers,
                    $horizon
                );


        /*
         * --------------------------------------------------------
         * SUCCESS RESULT
         * --------------------------------------------------------
         */

        return [

            'status' =>
                (
                    $horizonBuild[
                        'status'
                    ]
                    ??
                    null
                )
                ===
                'Available'
                    ? 'Available'
                    : 'Unavailable',

            'optimizer_result' =>
                $optimizerResult,

            'horizon_result' =>
                $horizonBuild[
                    'horizon_result'
                ]
                ??
                null
        ];
    }
}