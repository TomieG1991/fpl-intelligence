<?php

/**
 * CaptainBacktestingService
 *
 * Evaluates a preserved Captain Intelligence recommendation
 * against authoritative completed-gameweek player outcomes.
 *
 * The comparison universe is the preserved Captain Intelligence
 * rankings that existed before the deadline.
 *
 * This is important because Captain Intelligence evaluates its
 * own candidate universe independently of Starting XI selection.
 *
 * Historical backtesting must therefore evaluate the captain
 * recommendation against the candidates Captain Intelligence
 * genuinely considered at recommendation time.
 *
 * This service does not:
 *
 * - recalculate Captain Intelligence
 * - recalculate Expected Points
 * - recalculate Player Intelligence
 * - reconstruct rejected captain candidates
 * - require a fixed 15-player ranking universe
 * - restrict alternatives to the recommended Starting XI
 * - double captain points
 * - simulate vice-captain fallback
 * - evaluate transfers
 * - manufacture an accuracy score
 * - manufacture an overall backtesting score
 *
 * Captain points lost is measured using base realised points:
 *
 *     best alternative actual points
 *     -
 *     recommended captain actual points
 *
 * The result is bounded at zero because a captain who
 * outperformed every preserved alternative lost no points.
 */
class CaptainBacktestingService
{
    public function evaluate(
        array $captain,
        array $captainRankings,
        array $playerOutcomes
    ): array {

        /*
         * ========================================================
         * REQUIRE INPUT EVIDENCE
         * ========================================================
         */

        if (
            empty($captain)
            ||
            empty($captainRankings)
            ||
            empty($playerOutcomes)
        ) {

            return [];
        }


        /*
         * ========================================================
         * VALIDATE CAPTAIN IDENTITY
         * ========================================================
         */

        $captainPlayerId =
            $captain[
                'player_id'
            ]
            ??
            null;


        if (
            !is_numeric(
                $captainPlayerId
            )
            ||
            (int) $captainPlayerId <= 0
        ) {

            return [];
        }


        $captainPlayerId =
            (int) $captainPlayerId;


        /*
         * ========================================================
         * VALIDATE PRESERVED CAPTAIN INTELLIGENCE RANKINGS
         * ========================================================
         *
         * We deliberately do not require exactly fifteen rows.
         *
         * Captain Intelligence may reject individual players
         * while still producing a successful recommendation.
         *
         * The historical candidate universe is therefore exactly
         * the successful rankings that were preserved before the
         * deadline.
         */

        $rankingsByPlayerId =
            [];


        foreach (
            $captainRankings
            as $ranking
        ) {

            if (
                !is_array(
                    $ranking
                )
            ) {

                return [];
            }


            $playerId =
                $ranking[
                    'player_id'
                ]
                ??
                null;


            if (
                !is_numeric(
                    $playerId
                )
                ||
                (int) $playerId <= 0
            ) {

                return [];
            }


            $playerId =
                (int) $playerId;


            /*
             * Duplicate player identity means the preserved
             * ranking universe is structurally ambiguous.
             */
            if (
                array_key_exists(
                    $playerId,
                    $rankingsByPlayerId
                )
            ) {

                return [];
            }


            $rankingsByPlayerId[
                $playerId
            ] =
                $ranking;
        }


        /*
         * A comparison requires:
         *
         * - the recommended captain
         * - at least one alternative candidate
         */
        if (
            count(
                $rankingsByPlayerId
            )
            < 2
        ) {

            return [];
        }


        /*
         * The recommended captain must belong to the historical
         * Captain Intelligence universe being evaluated.
         */
        if (
            !array_key_exists(
                $captainPlayerId,
                $rankingsByPlayerId
            )
        ) {

            return [];
        }


        /*
         * ========================================================
         * BUILD AUTHORITATIVE OUTCOME LOOKUP
         * ========================================================
         */

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
                $outcome[
                    'player_id'
                ]
                ??
                null;


            if (
                !is_numeric(
                    $playerId
                )
                ||
                (int) $playerId <= 0
            ) {

                continue;
            }


            $playerId =
                (int) $playerId;


            $outcomesByPlayerId[
                $playerId
            ] =
                $outcome;
        }


        /*
         * ========================================================
         * REQUIRE COMPLETE RANKING OUTCOME EVIDENCE
         * ========================================================
         *
         * We cannot identify the strongest realised candidate
         * fairly when one or more preserved Captain Intelligence
         * candidates has no completed-gameweek outcome evidence.
         *
         * Zero-minute and negative-point outcomes remain valid.
         */

        foreach (
            $rankingsByPlayerId
            as $playerId => $ranking
        ) {

            if (
                !array_key_exists(
                    $playerId,
                    $outcomesByPlayerId
                )
            ) {

                return [];
            }


            $actualPoints =
                $outcomesByPlayerId[
                    $playerId
                ][
                    'total_points'
                ]
                ??
                null;


            if (
                !is_numeric(
                    $actualPoints
                )
            ) {

                return [];
            }
        }


        /*
         * ========================================================
         * RECOMMENDED CAPTAIN OUTCOME
         * ========================================================
         */

        $captainOutcome =
            $outcomesByPlayerId[
                $captainPlayerId
            ];


        $captainActualPoints =
            $captainOutcome[
                'total_points'
            ];


        $captainActualMinutes =
            $captainOutcome[
                'minutes'
            ]
            ??
            null;


        if (
            !is_numeric(
                $captainActualMinutes
            )
        ) {

            $captainActualMinutes =
                null;
        }


        /*
         * ========================================================
         * BEST REALISED CAPTAIN INTELLIGENCE ALTERNATIVE
         * ========================================================
         *
         * Search only the preserved Captain Intelligence ranking
         * universe.
         *
         * The recommended captain is excluded because we are
         * looking for the strongest realised alternative that
         * Captain Intelligence genuinely considered.
         */

        $bestAlternativePlayerId =
            null;


        $bestAlternativeActualPoints =
            null;


        foreach (
            $rankingsByPlayerId
            as $playerId => $ranking
        ) {

            if (
                $playerId
                ===
                $captainPlayerId
            ) {

                continue;
            }


            $actualPoints =
                $outcomesByPlayerId[
                    $playerId
                ][
                    'total_points'
                ];


            if (
                $bestAlternativeActualPoints === null
                ||
                $actualPoints
                >
                $bestAlternativeActualPoints
            ) {

                $bestAlternativePlayerId =
                    $playerId;


                $bestAlternativeActualPoints =
                    $actualPoints;
            }
        }


        if (
            $bestAlternativePlayerId === null
            ||
            $bestAlternativeActualPoints === null
        ) {

            return [];
        }


        /*
         * ========================================================
         * CAPTAIN POINTS LOST
         * ========================================================
         */

        $captainPointsLost =
            $bestAlternativeActualPoints
            -
            $captainActualPoints;


        if (
            $captainPointsLost < 0
        ) {

            $captainPointsLost =
                0;
        }


        /*
         * ========================================================
         * RESULT
         * ========================================================
         */

        return [

            'captain_player_id' =>
                $captainPlayerId,

            'captain_actual_points' =>
                $captainActualPoints,

            'captain_actual_minutes' =>
                $captainActualMinutes,

            'best_alternative_player_id' =>
                $bestAlternativePlayerId,

            'best_alternative_actual_points' =>
                $bestAlternativeActualPoints,

            'captain_points_lost' =>
                $captainPointsLost
        ];
    }
}