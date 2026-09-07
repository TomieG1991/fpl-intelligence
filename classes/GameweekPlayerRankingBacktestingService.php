<?php

/**
 * GameweekPlayerRankingBacktestingService
 *
 * Orchestrates Player Ranking backtesting from an already
 * assembled GameweekBacktestingEvidenceService result.
 *
 * This class does not:
 *
 * - fetch recommendation history
 * - fetch actual player outcomes
 * - determine gameweek availability
 * - reconstruct missing historical Player Ranking Evidence
 * - calculate player-level ranking comparisons itself
 * - calculate ranking metrics itself
 * - recalculate Intelligence Scores
 * - regenerate historical player rankings
 * - tune the Intelligence model
 * - create an overall backtesting score
 *
 * Historical evidence retrieval remains the responsibility of
 * GameweekBacktestingEvidenceService.
 *
 * Player-level comparison remains the responsibility of
 * PlayerRankingBacktestingService.
 *
 * Aggregate ranking metrics remain the responsibility of
 * PlayerRankingBacktestingMetricsService.
 */
class GameweekPlayerRankingBacktestingService
{
    private object $playerRankingBacktestingService;

    private object $playerRankingBacktestingMetricsService;


    /**
     * Constructor.
     */
    public function __construct(
        object $playerRankingBacktestingService,
        object $playerRankingBacktestingMetricsService
    ) {

        $this->playerRankingBacktestingService =
            $playerRankingBacktestingService;


        $this->playerRankingBacktestingMetricsService =
            $playerRankingBacktestingMetricsService;
    }


    /**
     * Evaluate Player Ranking performance from assembled
     * historical evidence.
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
         * Do not attempt to manufacture ranking evaluations from
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


        /*
         * Player Ranking Evidence is deliberately separate from
         * player_projections.
         *
         * player_rankings contains the preserved historical
         * full-player-pool ranking.
         *
         * player_projections remains squad-only evidence and must
         * never be used to reconstruct historical rankings.
         */

        $playerRankings =
            $recommendationSnapshot[
                'player_rankings'
            ]
            ?? null;


        if (
            !is_array(
                $playerRankings
            )
            ||
            empty(
                $playerRankings
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence requires preserved player rankings.'
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
         * PLAYER-LEVEL RANKING EVALUATION
         * ========================================================
         */

        $playerEvaluations =
            $this
                ->playerRankingBacktestingService
                ->evaluate(
                    $playerRankings,
                    $playerOutcomes
                );


        /*
         * ========================================================
         * AGGREGATE RANKING METRICS
         * ========================================================
         */

        $metrics =
            $this
                ->playerRankingBacktestingMetricsService
                ->calculate(
                    $playerEvaluations
                );


        /*
         * ========================================================
         * PLAYER RANKING BACKTESTING CONTRACT
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