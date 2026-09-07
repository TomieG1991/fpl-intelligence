<?php


class GameweekTransferBacktestingService
{
    /*
     * ============================================================
     * GAMEWEEK TRANSFER BACKTESTING SERVICE
     * ============================================================
     *
     * v0.35.0 — Recommendation History & Backtesting
     *
     * Connect immutable completed-gameweek recommendation evidence
     * to the specialist TransferBacktestingService.
     *
     * This service does not:
     *
     * - fetch recommendation history
     * - fetch player outcomes
     * - determine gameweek availability
     * - recalculate Transfer Intelligence
     * - rerank transfer candidates
     * - apply transfer-hit costs
     * - judge Make / Consider / Hold
     * - calculate an accuracy score
     * - calculate an overall backtesting score
     *
     * GameweekBacktestingEvidenceService remains responsible for
     * assembling the historical evidence envelope.
     */


    private object $transferBacktestingService;


    public function __construct(
        object $transferBacktestingService
    ) {

        $this->transferBacktestingService =
            $transferBacktestingService;
    }


    /**
     * Evaluate preserved transfer recommendation evidence from an
     * already-assembled completed-gameweek backtesting envelope.
     */
    public function evaluate(
        array $evidence
    ): array {

        /*
         * ========================================================
         * EVIDENCE STATUS
         * ========================================================
         *
         * GameweekBacktestingEvidenceService owns historical
         * availability/completeness.
         *
         * Do not call the specialist when the evidence envelope is
         * already Unavailable or Incomplete.
         */

        $status =
            $evidence[
                'status'
            ]
            ?? null;


        if (
            $status !==
            'Ready'
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

                'transfer_evaluation' =>
                    []
            ];
        }


        /*
         * ========================================================
         * READY EVIDENCE STRUCTURE
         * ========================================================
         *
         * A Ready envelope must contain the immutable historical
         * recommendation snapshot and authoritative player outcomes.
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
            ||
            empty(
                $recommendationSnapshot
            )
        ) {

            throw new InvalidArgumentException(
                'Ready backtesting evidence must contain a recommendation snapshot.'
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
                'Ready backtesting evidence must contain player outcomes.'
            );
        }


        /*
         * ========================================================
         * PRESERVED TRANSFER INTELLIGENCE
         * ========================================================
         *
         * Unlike the recommendation snapshot itself, transfer
         * intelligence may legitimately be absent.
         *
         * The existing Gameweek Decision architecture supports
         * historical states where transfer intelligence was not
         * supplied.
         *
         * Do not manufacture a transfer recommendation in that case.
         */

        $transferRecommendations =
            $recommendationSnapshot[
                'transfer_recommendations'
            ]
            ?? null;


        if (
            !is_array(
                $transferRecommendations
            )
            ||
            empty(
                $transferRecommendations
            )
        ) {

            return $this->incompleteResult(
                $evidence,
                'Preserved transfer recommendation evidence is unavailable.'
            );
        }


        /*
         * ========================================================
         * SPECIALIST TRANSFER EVALUATION
         * ========================================================
         *
         * Delegate the preserved recommendation and authoritative
         * outcomes unchanged.
         *
         * TransferBacktestingService remains responsible for:
         *
         * - selecting the preserved top outgoing group
         * - selecting its preserved rank-one replacement
         * - matching both players to realised outcomes
         * - calculating the signed realised points difference
         */

        $transferEvaluation =
            $this->transferBacktestingService
                ->evaluate(
                    $transferRecommendations,
                    $playerOutcomes
                );


        /*
         * ========================================================
         * UNEVALUABLE PRESERVED TRANSFER
         * ========================================================
         *
         * Transfer intelligence may exist without a complete
         * factual outgoing -> incoming comparison.
         *
         * Examples:
         *
         * - no legal replacement survived
         * - preserved candidate structure is incomplete
         * - one of the required realised outcomes is unavailable
         *
         * This is not converted into a zero-point transfer result.
         */

        if (
            !is_array(
                $transferEvaluation
            )
            ||
            empty(
                $transferEvaluation
            )
        ) {

            return $this->incompleteResult(
                $evidence,
                'Preserved transfer recommendation could not be evaluated.'
            );
        }


        /*
         * ========================================================
         * READY RESULT
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

            'transfer_evaluation' =>
                $transferEvaluation
        ];
    }


    /*
     * ============================================================
     * INCOMPLETE RESULT
     * ============================================================
     */

    private function incompleteResult(
        array $evidence,
        string $reason
    ): array {

        return [

            'status' =>
                'Incomplete',

            'reason' =>
                $reason,

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

            'transfer_evaluation' =>
                []
        ];
    }
}