<?php

class TransferDecisionBacktestingService
{
    /*
     * ============================================================
     * TRANSFER DECISION BACKTESTING
     * ============================================================
     *
     * This service evaluates whether the preserved manager-facing
     * transfer decision was supported by the realised outcome of
     * the preserved transfer candidate.
     *
     * It does not:
     *
     * - recalculate Transfer Intelligence
     * - rerank transfer candidates with hindsight
     * - calculate realised player outcomes
     * - assume a transfer hit
     * - calculate hit-adjusted net gain
     * - manufacture an accuracy score
     * - manufacture an overall score
     *
     * The factual transfer comparison must already have been
     * produced by TransferBacktestingService.
     *
     * Supported directional decisions:
     *
     * Make Transfer:
     *     positive realised gain     -> Supported
     *     zero / negative gain       -> Not Supported
     *
     * Hold:
     *     zero / negative gain       -> Supported
     *     positive gain              -> Not Supported
     *
     * Consider Transfer:
     *     always Inconclusive
     *
     * Review / No Transfer Data:
     *     always Inconclusive
     */


    /*
     * ============================================================
     * PUBLIC EVALUATION
     * ============================================================
     */

    public function evaluate(
        array $gameweekDecision,
        array $transferEvaluation
    ): array {

        /*
         * --------------------------------------------------------
         * PRESERVED GAMEWEEK DECISION
         * --------------------------------------------------------
         */

        if (
            empty(
                $gameweekDecision
            )
        ) {

            return [];
        }


        $transferAdvice =
            $gameweekDecision[
                'transfer_advice'
            ]
            ?? null;


        if (
            !is_array(
                $transferAdvice
            )
            ||
            empty(
                $transferAdvice
            )
        ) {

            return [];
        }


        /*
         * --------------------------------------------------------
         * PRESERVED TRANSFER ACTION
         * --------------------------------------------------------
         */

        $action =
            trim(
                (string) (
                    $transferAdvice[
                        'action'
                    ]
                    ?? ''
                )
            );


        $validActions = [
            'Make Transfer',
            'Consider Transfer',
            'Hold',
            'Review',
            'No Transfer Data'
        ];


        if (
            $action === ''
            ||
            !in_array(
                $action,
                $validActions,
                true
            )
        ) {

            return [];
        }


        /*
         * --------------------------------------------------------
         * REALISED TRANSFER EVALUATION
         * --------------------------------------------------------
         */

        if (
            empty(
                $transferEvaluation
            )
        ) {

            return [];
        }


        $outgoingPlayerId =
            $this->normalisePositiveInteger(
                $transferEvaluation[
                    'outgoing_player_id'
                ]
                ?? null
            );


        $incomingPlayerId =
            $this->normalisePositiveInteger(
                $transferEvaluation[
                    'incoming_player_id'
                ]
                ?? null
            );


        if (
            $outgoingPlayerId === null
            ||
            $incomingPlayerId === null
            ||
            $outgoingPlayerId ===
            $incomingPlayerId
        ) {

            return [];
        }


        $transferPointsGain =
            $transferEvaluation[
                'transfer_points_gain'
            ]
            ?? null;


        if (
            !is_numeric(
                $transferPointsGain
            )
        ) {

            return [];
        }


        $transferPointsGain =
            $this->normaliseNumber(
                $transferPointsGain
            );


        /*
         * --------------------------------------------------------
         * PRESERVED DECISION METADATA
         * --------------------------------------------------------
         */

        $priority =
            $transferAdvice[
                'priority'
            ]
            ?? null;


        $score =
            $transferAdvice[
                'score'
            ]
            ?? null;


        if (
            is_numeric(
                $score
            )
        ) {

            $score =
                (float) $score;

        } else {

            $score =
                null;
        }


        /*
         * --------------------------------------------------------
         * SUPPORT CLASSIFICATION
         * --------------------------------------------------------
         *
         * This is deliberately a simple directional comparison.
         *
         * No arbitrary realised-points threshold is introduced.
         */

        $supportStatus =
            $this->classifySupport(
                $action,
                $transferPointsGain
            );


        /*
         * --------------------------------------------------------
         * RESULT
         * --------------------------------------------------------
         */

        return [

            'decision_action' =>
                $action,

            'decision_priority' =>
                $priority,

            'decision_score' =>
                $score,

            'outgoing_player_id' =>
                $outgoingPlayerId,

            'incoming_player_id' =>
                $incomingPlayerId,

            'transfer_points_gain' =>
                $transferPointsGain,

            'support_status' =>
                $supportStatus
        ];
    }


    /*
     * ============================================================
     * SUPPORT CLASSIFICATION
     * ============================================================
     */

    private function classifySupport(
        string $action,
        int|float $transferPointsGain
    ): string {

        /*
         * --------------------------------------------------------
         * MAKE TRANSFER
         * --------------------------------------------------------
         */

        if (
            $action ===
            'Make Transfer'
        ) {

            return $transferPointsGain > 0
                ? 'Supported'
                : 'Not Supported';
        }


        /*
         * --------------------------------------------------------
         * HOLD
         * --------------------------------------------------------
         */

        if (
            $action ===
            'Hold'
        ) {

            return $transferPointsGain <= 0
                ? 'Supported'
                : 'Not Supported';
        }


        /*
         * --------------------------------------------------------
         * NON-DIRECTIONAL ADVICE
         * --------------------------------------------------------
         *
         * Consider Transfer does not tell the manager definitively
         * to make or avoid the move.
         *
         * Review and No Transfer Data are likewise not directional
         * transfer recommendations.
         */

        return 'Inconclusive';
    }


    /*
     * ============================================================
     * POSITIVE INTEGER NORMALISATION
     * ============================================================
     */

    private function normalisePositiveInteger(
        mixed $value
    ): ?int {

        if (
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        $numericValue =
            (float) $value;


        if (
            $numericValue <= 0
            ||
            floor(
                $numericValue
            )
            !==
            $numericValue
        ) {

            return null;
        }


        return (int) $numericValue;
    }


    /*
     * ============================================================
     * NUMERIC NORMALISATION
     * ============================================================
     */

    private function normaliseNumber(
        mixed $value
    ): int|float {

        $numericValue =
            (float) $value;


        if (
            floor(
                $numericValue
            )
            ===
            $numericValue
        ) {

            return (int) $numericValue;
        }


        return $numericValue;
    }
}