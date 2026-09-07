<?php

/**
 * GameweekProjectionBacktestingService
 *
 * Orchestrates projection backtesting from an already assembled
 * GameweekBacktestingEvidenceService result.
 *
 * This class does not:
 *
 * - fetch recommendation history
 * - fetch actual player outcomes
 * - determine gameweek availability
 * - calculate player projection errors itself
 * - calculate aggregate metrics itself
 * - evaluate captain recommendations
 * - evaluate Starting XI recommendations
 * - evaluate transfer recommendations
 * - evaluate chip recommendations
 * - create an overall backtesting score
 *
 * Historical evidence retrieval remains the responsibility of
 * GameweekBacktestingEvidenceService.
 *
 * Player-level comparison remains the responsibility of
 * PlayerProjectionBacktestingService.
 *
 * Aggregate projection metrics remain the responsibility of
 * PlayerProjectionBacktestingMetricsService.
 */
class GameweekProjectionBacktestingService
{
    private object $playerProjectionBacktestingService;

    private object $playerProjectionBacktestingMetricsService;


    /**
     * Constructor.
     */
    public function __construct(
        object $playerProjectionBacktestingService,
        object $playerProjectionBacktestingMetricsService
    ) {
        $this->playerProjectionBacktestingService =
            $playerProjectionBacktestingService;


        $this->playerProjectionBacktestingMetricsService =
            $playerProjectionBacktestingMetricsService;
    }


    /**
     * Evaluate projection accuracy from assembled historical evidence.
     */
    public function evaluate(
        array $evidence
    ): array {

        $status =
            $evidence[
                'status'
            ]
            ?? null;


        /*
         * ========================================================
         * NON-READY EVIDENCE
         * ========================================================
         *
         * Availability and completeness have already been decided
         * by GameweekBacktestingEvidenceService.
         *
         * Do not attempt to manufacture projection evaluations from
         * evidence that is not Ready.
         */

        if (
            $status !== 'Ready'
        ) {

            return [

                'status' =>
                    $status,

                'reason' =>
                    $evidence[
                        'reason'
                    ]
                    ?? null,

                'entry_id' =>
                    $evidence[
                        'entry_id'
                    ]
                    ?? null,

                'gameweek_id' =>
                    $evidence[
                        'gameweek_id'
                    ]
                    ?? null,

                'player_evaluations' =>
                    [],

                'metrics' =>
                    null
            ];
        }


        /*
         * ========================================================
         * READY EVIDENCE VALIDATION
         * ========================================================
         */

        $recommendationSnapshot =
            $evidence[
                'recommendation_snapshot'
            ]
            ?? null;


        if (
            !is_array(
                $recommendationSnapshot
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence requires a recommendation snapshot.'
            );
        }


        $playerProjections =
            $recommendationSnapshot[
                'player_projections'
            ]
            ?? null;


        if (
            !is_array(
                $playerProjections
            )
            ||
            empty(
                $playerProjections
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence requires preserved player projections.'
            );
        }


        $playerOutcomes =
            $evidence[
                'player_outcomes'
            ]
            ?? null;


        if (
            !is_array(
                $playerOutcomes
            )
            ||
            empty(
                $playerOutcomes
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence requires realised player outcomes.'
            );
        }


        /*
         * ========================================================
         * PLAYER-LEVEL PROJECTION EVALUATION
         * ========================================================
         */

        $playerEvaluations =
            $this
                ->playerProjectionBacktestingService
                ->evaluate(
                    $playerProjections,
                    $playerOutcomes
                );


        /*
         * ========================================================
         * AGGREGATE PROJECTION METRICS
         * ========================================================
         */

        $metrics =
            $this
                ->playerProjectionBacktestingMetricsService
                ->calculate(
                    $playerEvaluations
                );


        /*
         * ========================================================
         * PROJECTION BACKTESTING CONTRACT
         * ========================================================
         */

        return [

            'status' =>
                'Ready',

            'reason' =>
                null,

            'entry_id' =>
                $evidence[
                    'entry_id'
                ]
                ?? null,

            'gameweek_id' =>
                $evidence[
                    'gameweek_id'
                ]
                ?? null,

            'player_evaluations' =>
                $playerEvaluations,

            'metrics' =>
                $metrics
        ];
    }
}