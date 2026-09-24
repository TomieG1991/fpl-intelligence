<?php

/**
 * ChipRecommendationProductionOrchestrator
 *
 * Reuses the four existing production Chip Intelligence
 * decision pipelines and returns their results unchanged.
 *
 * This class deliberately contains no intelligence,
 * scoring, optimisation or recommendation logic.
 *
 * Its purpose is to provide one reusable production
 * orchestration boundary for callers that require the
 * complete set of chip recommendation evidence.
 */
class ChipRecommendationProductionOrchestrator
{
    private object $wildcardDecisionIntelligenceService;


    private object $freeHitDecisionIntelligenceService;


    private object $benchBoostDecisionIntelligenceService;


    private object $tripleCaptainDecisionIntelligenceService;


    /*
     * ============================================================
     * CONSTRUCTOR
     * ============================================================
     */

    public function __construct(
        object $wildcardDecisionIntelligenceService,
        object $freeHitDecisionIntelligenceService,
        object $benchBoostDecisionIntelligenceService,
        object $tripleCaptainDecisionIntelligenceService
    ) {

        $this->wildcardDecisionIntelligenceService =
            $wildcardDecisionIntelligenceService;


        $this->freeHitDecisionIntelligenceService =
            $freeHitDecisionIntelligenceService;


        $this->benchBoostDecisionIntelligenceService =
            $benchBoostDecisionIntelligenceService;


        $this->tripleCaptainDecisionIntelligenceService =
            $tripleCaptainDecisionIntelligenceService;
    }


    /*
     * ============================================================
     * BUILD
     * ============================================================
     *
     * Delegate unchanged production inputs to the four existing
     * Chip Intelligence decision pipelines.
     *
     * Wildcard retains its established three-gameweek horizon.
     *
     * Free Hit, Bench Boost and Triple Captain retain the
     * explicitly resolved actionable gameweek supplied by the
     * caller.
     */

    public function build(
        array $importedSquad,
        array $players,
        float $budget,
        int $targetGameweek
    ): array {

        /*
         * --------------------------------------------------------
         * WILDCARD
         * --------------------------------------------------------
         */

        $wildcardResult =
            $this
                ->wildcardDecisionIntelligenceService
                ->build(
                    $importedSquad,
                    $players,
                    $budget,
                    3
                );


        /*
         * --------------------------------------------------------
         * FREE HIT
         * --------------------------------------------------------
         */

        $freeHitResult =
            $this
                ->freeHitDecisionIntelligenceService
                ->build(
                    $importedSquad,
                    $players,
                    $budget,
                    $targetGameweek
                );


        /*
         * --------------------------------------------------------
         * BENCH BOOST
         * --------------------------------------------------------
         */

        $benchBoostResult =
            $this
                ->benchBoostDecisionIntelligenceService
                ->build(
                    $importedSquad,
                    $targetGameweek
                );


        /*
         * --------------------------------------------------------
         * TRIPLE CAPTAIN
         * --------------------------------------------------------
         */

        $tripleCaptainResult =
            $this
                ->tripleCaptainDecisionIntelligenceService
                ->build(
                    $importedSquad,
                    $targetGameweek
                );


        /*
         * --------------------------------------------------------
         * COMPLETE PRODUCTION EVIDENCE
         * --------------------------------------------------------
         *
         * Preserve each existing result unchanged.
         */

        return [

            'wildcard' =>
                $wildcardResult,

            'free_hit' =>
                $freeHitResult,

            'bench_boost' =>
                $benchBoostResult,

            'triple_captain' =>
                $tripleCaptainResult
        ];
    }
}