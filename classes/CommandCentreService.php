<?php

/**
 * CommandCentreService
 *
 * Provides the production orchestration boundary for the
 * Gameweek Command Centre.
 *
 * This service must not introduce independent intelligence,
 * scoring, optimisation or recommendation logic.
 *
 * Existing production intelligence services remain
 * authoritative for all Command Centre decisions.
 */
class CommandCentreService
{
    private ?object $playerIntelligenceService;

    private ?object $squadHorizonIntelligenceService;

    private ?object $chipRecommendationProductionOrchestrator;


    /*
     * ============================================================
     * CONSTRUCTOR
     * ============================================================
     */

    public function __construct(
        ?object $playerIntelligenceService = null,
        ?object $squadHorizonIntelligenceService = null,
        ?object $chipRecommendationProductionOrchestrator = null
    ) {

        $this->playerIntelligenceService =
            $playerIntelligenceService;


        $this->squadHorizonIntelligenceService =
            $squadHorizonIntelligenceService;


        $this->chipRecommendationProductionOrchestrator =
            $chipRecommendationProductionOrchestrator;
    }


    /*
     * ============================================================
     * BUILD
     * ============================================================
     */

    public function build(
        array $squad = [],
        float $bank = 0.0,
        array $importedSquad = [],
        ?int $targetGameweek = null,
        array $playerPool = [],
        float $budget = 0.0,
        array $managerContext = [],
        array $gameweekContext = [],
        array $dataHealth = []
    ): array {

        /*
         * --------------------------------------------------------
         * DEFAULT COMMAND CENTRE ENVELOPE
         * --------------------------------------------------------
         */

        $result = [

            'manager' =>
                null,

            'gameweek' =>
                null,

            'decision' =>
                null,

            'starting_xi' =>
                null,

            'captaincy' =>
                null,

            'transfers' =>
                null,

            'squad_horizon' =>
                null,

            'chips' =>
                null,

            'attention' =>
                [],

            'data_health' =>
                null
        ];


        /*
         * --------------------------------------------------------
         * MANAGER CONTEXT
         * --------------------------------------------------------
         *
         * Manager context is prepared by the calling production
         * boundary.
         *
         * The Command Centre preserves that context unchanged.
         */

        if (
            !empty(
                $managerContext
            )
        ) {

            $result[
                'manager'
            ] =
                $managerContext;
        }


        /*
         * --------------------------------------------------------
         * GAMEWEEK CONTEXT
         * --------------------------------------------------------
         *
         * Actionable gameweek resolution remains authoritative
         * outside this orchestration service.
         *
         * Preserve the supplied context unchanged.
         */

        if (
            !empty(
                $gameweekContext
            )
        ) {

            $result[
                'gameweek'
            ] =
                $gameweekContext;
        }


        /*
         * --------------------------------------------------------
         * DATA HEALTH CONTEXT
         * --------------------------------------------------------
         *
         * UpdateHealthService remains authoritative for data
         * freshness and health evaluation.
         *
         * The Command Centre only carries that evidence into its
         * result envelope.
         */

        if (
            !empty(
                $dataHealth
            )
        ) {

            $result[
                'data_health'
            ] =
                $dataHealth;
        }


        /*
         * --------------------------------------------------------
         * AUTHORITATIVE GAMEWEEK DECISION
         * --------------------------------------------------------
         *
         * PlayerIntelligenceService already coordinates:
         *
         * - Starting XI
         * - captaincy
         * - transfer intelligence
         * - final Gameweek Decision Intelligence
         *
         * Request that complete result once and reuse its evidence.
         */

        if (
            $this->playerIntelligenceService
            !==
            null
        ) {

            $gameweekDecision =
                $this
                    ->playerIntelligenceService
                    ->getGameweekDecision(
                        $squad,
                        $bank
                    );


            /*
             * ----------------------------------------------------
             * PRESERVE AUTHORITATIVE GAMEWEEK EVIDENCE
             * ----------------------------------------------------
             */

            $result[
                'decision'
            ] =
                $gameweekDecision[
                    'decision'
                ]
                ?? null;


            $result[
                'starting_xi'
            ] =
                $gameweekDecision[
                    'gameweek'
                ]
                ?? null;


            $result[
                'captaincy'
            ] =
                $gameweekDecision[
                    'captaincy'
                ]
                ?? null;


            $result[
                'transfers'
            ] =
                $gameweekDecision[
                    'transfers'
                ]
                ?? null;
        }


        /*
         * --------------------------------------------------------
         * AUTHORITATIVE SQUAD HORIZON
         * --------------------------------------------------------
         *
         * SquadHorizonIntelligenceService owns multi-gameweek
         * squad analysis.
         *
         * The original imported FPL squad is deliberately passed
         * through unchanged so this service remains responsible
         * for its own player-resolution and validation boundary.
         *
         * The Command Centre uses a five-gameweek horizon to give
         * the manager useful medium-term planning context.
         */

        if (
            $this->squadHorizonIntelligenceService
            !==
            null
        ) {

            $result[
                'squad_horizon'
            ] =
                $this
                    ->squadHorizonIntelligenceService
                    ->buildForImportedSquad(
                        $importedSquad,
                        5,
                        $targetGameweek
                    );
        }


        /*
         * --------------------------------------------------------
         * AUTHORITATIVE CHIP INTELLIGENCE
         * --------------------------------------------------------
         *
         * ChipRecommendationProductionOrchestrator already owns
         * the shared production boundary around:
         *
         * - Wildcard
         * - Free Hit
         * - Bench Boost
         * - Triple Captain
         *
         * Preserve the original imported squad, complete player
         * candidate pool, available budget and actionable
         * gameweek unchanged.
         *
         * No chip recommendation or scoring logic belongs here.
         */

        if (
            $this->chipRecommendationProductionOrchestrator
            !==
            null
            &&
            $targetGameweek
            !==
            null
        ) {

            $result[
                'chips'
            ] =
                $this
                    ->chipRecommendationProductionOrchestrator
                    ->build(
                        $importedSquad,
                        $playerPool,
                        $budget,
                        $targetGameweek
                    );
        }


        /*
         * --------------------------------------------------------
         * ATTENTION REQUIRED
         * --------------------------------------------------------
         *
         * Attention Required is a presentation-oriented evidence
         * bundle.
         *
         * It must not create new thresholds, classifications,
         * priorities, recommendations or scoring.
         *
         * GameweekDecisionEngine remains authoritative for:
         *
         * - squad risks
         * - transfer advice
         *
         * ChipRecommendationProductionOrchestrator remains
         * authoritative for:
         *
         * - Wildcard
         * - Free Hit
         * - Bench Boost
         * - Triple Captain
         *
         * Preserve those results unchanged so the Command Centre
         * can surface them without becoming another intelligence
         * engine.
         */

        $decision =
            is_array(
                $result[
                    'decision'
                ]
                ?? null
            )
                ? $result[
                    'decision'
                ]
                : [];


        $squadRisks =
            $decision[
                'squad_risks'
            ]
            ?? null;


        $transferAdvice =
            $decision[
                'transfer_advice'
            ]
            ?? null;


        $chipEvidence =
            $result[
                'chips'
            ]
            ?? null;


        if (
            $squadRisks !== null
            ||
            $transferAdvice !== null
            ||
            $chipEvidence !== null
        ) {

            $result[
                'attention'
            ] = [

                'squad_risks' =>
                    $squadRisks,

                'transfer_advice' =>
                    $transferAdvice,

                'chips' =>
                    $chipEvidence
            ];
        }


        return $result;
    }
}