<?php


class GameweekTransferDecisionBacktestingService
{
    /*
     * ============================================================
     * GAMEWEEK TRANSFER DECISION BACKTESTING SERVICE
     * ============================================================
     *
     * v0.35.0 — Recommendation History & Backtesting
     *
     * Connect preserved completed-gameweek recommendation evidence
     * and an already-completed factual transfer evaluation to the
     * specialist TransferDecisionBacktestingService.
     *
     * This service does not:
     *
     * - fetch recommendation history
     * - fetch player outcomes
     * - calculate factual transfer points gain
     * - recalculate Transfer Intelligence
     * - rerank transfer candidates
     * - apply transfer-hit costs
     * - manufacture an accuracy score
     * - manufacture an overall backtesting score
     *
     * GameweekBacktestingEvidenceService remains responsible for
     * assembling the historical evidence envelope.
     *
     * GameweekTransferBacktestingService remains responsible for
     * producing the factual outgoing -> incoming comparison.
     *
     * TransferDecisionBacktestingService remains responsible for
     * evaluating whether the preserved transfer advice was
     * supported by that realised comparison.
     */


    private object $transferDecisionBacktestingService;


    public function __construct(
        object $transferDecisionBacktestingService
    ) {

        $this->transferDecisionBacktestingService =
            $transferDecisionBacktestingService;
    }


    /**
     * Evaluate the preserved manager-facing transfer decision
     * against an already-completed factual transfer evaluation.
     */
    public function evaluate(
        array $evidence,
        array $transferBacktestingResult
    ): array {

        /*
         * ========================================================
         * HISTORICAL EVIDENCE STATUS
         * ========================================================
         *
         * GameweekBacktestingEvidenceService owns historical
         * availability and completeness.
         *
         * If that evidence is not Ready, there is nothing further
         * for this decision layer to evaluate.
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

                'decision_evaluation' =>
                    []
            ];
        }


        /*
         * ========================================================
         * FACTUAL TRANSFER BACKTESTING STATUS
         * ========================================================
         *
         * The transfer decision cannot be judged unless the
         * preserved outgoing -> incoming recommendation has already
         * produced a Ready factual comparison.
         */

        $transferStatus =
            $transferBacktestingResult[
                'status'
            ]
            ?? null;


        if (
            $transferStatus !==
            'Ready'
        ) {

            return [

                'status' =>
                    $transferStatus,

                'reason' =>
                    $transferBacktestingResult[
                        'reason'
                    ]
                    ?? null,

                'entry_id' =>
                    $transferBacktestingResult[
                        'entry_id'
                    ]
                    ??
                    $evidence[
                        'entry_id'
                    ]
                    ??
                    null,

                'gameweek_id' =>
                    $transferBacktestingResult[
                        'gameweek_id'
                    ]
                    ??
                    $evidence[
                        'gameweek_id'
                    ]
                    ??
                    null,

                'decision_evaluation' =>
                    []
            ];
        }


        /*
         * ========================================================
         * READY HISTORICAL EVIDENCE STRUCTURE
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
                'Ready backtesting evidence must contain a recommendation snapshot.'
            );
        }


        /*
         * ========================================================
         * PRESERVED GAMEWEEK DECISION
         * ========================================================
         *
         * The recommendation snapshot itself may exist without a
         * preserved Gameweek Decision.
         *
         * That is historical incompleteness rather than malformed
         * Ready envelope structure.
         */

        $gameweekDecision =
            $recommendationSnapshot[
                'gameweek_decision'
            ]
            ?? null;


        if (
            !is_array(
                $gameweekDecision
            )
            ||
            empty(
                $gameweekDecision
            )
        ) {

            return $this->incompleteResult(
                $evidence,
                'Preserved gameweek decision evidence is unavailable.'
            );
        }


        /*
         * ========================================================
         * FACTUAL TRANSFER EVALUATION
         * ========================================================
         */

        $transferEvaluation =
            $transferBacktestingResult[
                'transfer_evaluation'
            ]
            ?? null;


        if (
            !is_array(
                $transferEvaluation
            )
            ||
            empty(
                $transferEvaluation
            )
        ) {

            throw new InvalidArgumentException(
                'Ready transfer backtesting result must contain a transfer evaluation.'
            );
        }


        /*
         * ========================================================
         * SPECIALIST DECISION EVALUATION
         * ========================================================
         *
         * Delegate both historical inputs unchanged.
         *
         * Do not reinterpret overall_action here.
         *
         * TransferDecisionBacktestingService owns the distinction
         * between:
         *
         * - Make Transfer
         * - Consider Transfer
         * - Hold
         * - Review
         * - No Transfer Data
         */

        $decisionEvaluation =
            $this->transferDecisionBacktestingService
                ->evaluate(
                    $gameweekDecision,
                    $transferEvaluation
                );


        /*
         * ========================================================
         * UNEVALUABLE PRESERVED DECISION
         * ========================================================
         */

        if (
            !is_array(
                $decisionEvaluation
            )
            ||
            empty(
                $decisionEvaluation
            )
        ) {

            return $this->incompleteResult(
                $evidence,
                'Preserved transfer decision could not be evaluated.'
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

            'decision_evaluation' =>
                $decisionEvaluation
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

            'decision_evaluation' =>
                []
        ];
    }
}