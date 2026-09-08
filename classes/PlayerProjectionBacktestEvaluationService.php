<?php

/**
 * PlayerProjectionBacktestEvaluationService
 *
 * Production orchestration boundary for historical projected-
 * points backtesting.
 *
 * This service connects:
 *
 * - immutable recommendation history
 * - completed-gameweek realised outcomes
 * - player-level projection comparison
 * - gameweek-level evaluation metrics
 *
 * It does not:
 *
 * - calculate Expected Points
 * - reconstruct historical recommendation evidence
 * - query live Player Intelligence
 * - query fixture history directly
 * - manufacture missing realised outcomes
 * - persist backtesting results
 * - tune or calibrate the model
 */
class PlayerProjectionBacktestEvaluationService
{
    private object $snapshotRepository;

    private object $outcomeService;

    private object $backtestService;

    private object $metricsService;


    public function __construct(
        object $snapshotRepository,
        object $outcomeService,
        object $backtestService,
        object $metricsService
    ) {

        $this->snapshotRepository =
            $snapshotRepository;


        $this->outcomeService =
            $outcomeService;


        $this->backtestService =
            $backtestService;


        $this->metricsService =
            $metricsService;
    }


    /**
     * Evaluate one immutable historical recommendation snapshot
     * against realised completed-gameweek evidence.
     */
    public function evaluate(
        int $entryId,
        int $gameweekId
    ): ?array {

        /*
         * ========================================================
         * VALIDATE ENTRY
         * ========================================================
         */

        if ($entryId <= 0) {

            throw new InvalidArgumentException(
                'Entry ID must be a positive integer.'
            );
        }


        /*
         * ========================================================
         * VALIDATE GAMEWEEK
         * ========================================================
         */

        if ($gameweekId <= 0) {

            throw new InvalidArgumentException(
                'Gameweek ID must be a positive integer.'
            );
        }


        /*
         * ========================================================
         * LOAD IMMUTABLE HISTORICAL RECOMMENDATION
         * ========================================================
         *
         * RecommendationSnapshotRepository is the source of truth
         * for what the application actually knew and recommended
         * before the deadline.
         */

        $snapshot =
            $this->snapshotRepository
                ->getByEntryAndGameweek(
                    $entryId,
                    $gameweekId
                );


        /*
         * No historical recommendation means there is nothing
         * legitimate to backtest.
         *
         * Do not reconstruct a recommendation from current data.
         */

        if ($snapshot === null) {

            return null;
        }


        /*
         * ========================================================
         * PRESERVED PLAYER PROJECTIONS
         * ========================================================
         *
         * These projections come only from the immutable snapshot.
         *
         * Missing historical projection evidence remains missing.
         */

        $playerProjections =
            is_array(
                $snapshot[
                    'player_projections'
                ]
                ?? null
            )
                ? $snapshot[
                    'player_projections'
                ]
                : [];


        /*
         * ========================================================
         * REALISED GAMEWEEK OUTCOMES
         * ========================================================
         *
         * PlayerGameweekOutcomeService owns aggregation of factual
         * fixture-history evidence.
         *
         * This orchestration layer must not query or aggregate
         * fixture history itself.
         */

        $playerOutcomes =
            $this->outcomeService
                ->getByGameweekId(
                    $gameweekId
                );


        /*
         * ========================================================
         * PLAYER-LEVEL BACKTEST
         * ========================================================
         *
         * Compare the preserved historical projection evidence
         * with the already-aggregated realised outcomes.
         */

        $playerBacktest =
            $this->backtestService
                ->evaluate(
                    $gameweekId,
                    $playerProjections,
                    $playerOutcomes
                );


        /*
         * ========================================================
         * GAMEWEEK-LEVEL METRICS
         * ========================================================
         *
         * The metrics service currently provides the objective
         * initial projected-points evaluation contract, including
         * Mean Absolute Error.
         */

        $metrics =
            $this->metricsService
                ->summarise(
                    $playerBacktest
                );


        /*
         * ========================================================
         * STABLE INITIAL ORCHESTRATION CONTRACT
         * ========================================================
         */

        return [

            'entry_id' =>
                $entryId,

            'gameweek_id' =>
                $gameweekId,

            'snapshot' =>
                $snapshot,

            'player_outcomes' =>
                $playerOutcomes,

            'player_backtest' =>
                $playerBacktest,

            'metrics' =>
                $metrics
        ];
    }
}