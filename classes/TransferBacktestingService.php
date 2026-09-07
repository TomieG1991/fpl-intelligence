<?php


class TransferBacktestingService
{
    /*
     * ============================================================
     * TRANSFER BACKTESTING SERVICE
     * ============================================================
     *
     * v0.35.0 — Recommendation History & Backtesting
     *
     * Compare the top preserved single-transfer recommendation
     * with authoritative completed-gameweek player outcomes.
     *
     * This service does not:
     *
     * - recalculate Transfer Intelligence
     * - rerank preserved transfer recommendations
     * - choose a hindsight replacement
     * - apply transfer-hit costs
     * - judge Make / Consider / Hold
     * - calculate an accuracy score
     * - calculate an overall backtesting score
     *
     * The preserved recommendation-time ordering remains
     * authoritative.
     */


    /**
     * Evaluate the realised result of the top preserved
     * single-transfer recommendation.
     */
    public function evaluate(
        array $transferRecommendations,
        array $playerOutcomes
    ): array {

        /*
         * ========================================================
         * PRESERVED TRANSFER INTELLIGENCE
         * ========================================================
         */

        if (
            empty(
                $transferRecommendations
            )
        ) {

            return [];
        }


        $optimizerResult =
            $transferRecommendations[
                'recommendations'
            ]
            ?? null;


        if (
            !is_array(
                $optimizerResult
            )
        ) {

            return [];
        }


        $recommendationGroups =
            $optimizerResult[
                'recommendations'
            ]
            ?? null;


        if (
            !is_array(
                $recommendationGroups
            )
            ||
            empty(
                $recommendationGroups
            )
        ) {

            return [];
        }


        /*
         * The production optimizer preserves transfer-priority
         * ordering, so the first group is the recommendation-time
         * highest-priority outgoing player.
         */
        $topRecommendation =
            $recommendationGroups[
                0
            ]
            ?? null;


        if (
            !is_array(
                $topRecommendation
            )
        ) {

            return [];
        }


        /*
         * ========================================================
         * OUTGOING PLAYER
         * ========================================================
         */

        $outgoing =
            $topRecommendation[
                'outgoing'
            ]
            ?? null;


        if (
            !is_array(
                $outgoing
            )
        ) {

            return [];
        }


        $outgoingPlayerId =
            $this->normalisePlayerId(
                $outgoing[
                    'player_id'
                ]
                ?? null
            );


        if (
            $outgoingPlayerId === null
        ) {

            return [];
        }


        /*
         * ========================================================
         * TOP PRESERVED REPLACEMENT
         * ========================================================
         */

        $replacements =
            $topRecommendation[
                'replacements'
            ]
            ?? null;


        if (
            !is_array(
                $replacements
            )
            ||
            empty(
                $replacements
            )
        ) {

            return [];
        }


        /*
         * Do not rerank using realised outcomes.
         *
         * Replacement index zero is the strongest replacement
         * according to the intelligence preserved before the
         * deadline.
         */
        $topReplacement =
            $replacements[
                0
            ]
            ?? null;


        if (
            !is_array(
                $topReplacement
            )
        ) {

            return [];
        }


        $incoming =
            $topReplacement[
                'player'
            ]
            ?? null;


        if (
            !is_array(
                $incoming
            )
        ) {

            return [];
        }


        $incomingPlayerId =
            $this->normalisePlayerId(
                $incoming[
                    'player_id'
                ]
                ?? null
            );


        if (
            $incomingPlayerId === null
        ) {

            return [];
        }


        if (
            $incomingPlayerId
            ===
            $outgoingPlayerId
        ) {

            return [];
        }


        /*
         * ========================================================
         * AUTHORITATIVE PLAYER OUTCOMES
         * ========================================================
         */

        if (
            empty(
                $playerOutcomes
            )
        ) {

            return [];
        }


        $outcomesByPlayerId =
            [];


        foreach (
            $playerOutcomes
            as $outcome
        ) {

            if (
                !is_array(
                    $outcome
                )
            ) {

                continue;
            }


            $playerId =
                $this->normalisePlayerId(
                    $outcome[
                        'player_id'
                    ]
                    ?? null
                );


            if (
                $playerId === null
            ) {

                continue;
            }


            $outcomesByPlayerId[
                $playerId
            ] =
                $outcome;
        }


        $outgoingOutcome =
            $outcomesByPlayerId[
                $outgoingPlayerId
            ]
            ?? null;


        $incomingOutcome =
            $outcomesByPlayerId[
                $incomingPlayerId
            ]
            ?? null;


        if (
            !is_array(
                $outgoingOutcome
            )
            ||
            !is_array(
                $incomingOutcome
            )
        ) {

            return [];
        }


        /*
         * ========================================================
         * REALISED POINTS
         * ========================================================
         */

        $outgoingActualPoints =
            $this->normalisePoints(
                $outgoingOutcome[
                    'total_points'
                ]
                ?? null
            );


        $incomingActualPoints =
            $this->normalisePoints(
                $incomingOutcome[
                    'total_points'
                ]
                ?? null
            );


        if (
            $outgoingActualPoints === null
            ||
            $incomingActualPoints === null
        ) {

            return [];
        }


        /*
         * ========================================================
         * REALISED TRANSFER DIFFERENCE
         * ========================================================
         *
         * Deliberately NOT bounded at zero.
         *
         * Positive:
         *     recommended incoming player outscored outgoing player.
         *
         * Zero:
         *     both players returned the same points.
         *
         * Negative:
         *     outgoing player outscored recommended replacement.
         *
         * No transfer hit is applied because the preserved evidence
         * does not establish whether this move would have cost one.
         */

        $transferPointsGain =
            $incomingActualPoints
            -
            $outgoingActualPoints;


        /*
         * ========================================================
         * RESULT
         * ========================================================
         */

        return [

            'outgoing_player_id' =>
                $outgoingPlayerId,

            'incoming_player_id' =>
                $incomingPlayerId,

            'outgoing_actual_points' =>
                $outgoingActualPoints,

            'incoming_actual_points' =>
                $incomingActualPoints,

            'transfer_points_gain' =>
                $transferPointsGain
        ];
    }


    /*
     * ============================================================
     * PLAYER ID NORMALISATION
     * ============================================================
     */

    private function normalisePlayerId(
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


        /*
         * Player IDs must represent positive whole numbers.
         */
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
     * POINTS NORMALISATION
     * ============================================================
     */

    private function normalisePoints(
        mixed $value
    ): int|float|null {

        if (
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        $points =
            (float) $value;


        /*
         * Preserve integer FPL points as integers while remaining
         * tolerant of numeric evidence if the outcome contract is
         * expanded later.
         */
        if (
            floor(
                $points
            )
            ===
            $points
        ) {

            return (int) $points;
        }


        return $points;
    }
}