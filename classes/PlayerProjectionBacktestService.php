<?php

/**
 * PlayerProjectionBacktestService
 *
 * Compares immutable historical player-projection evidence with
 * realised completed-gameweek player outcomes.
 *
 * This class does not:
 *
 * - calculate Expected Points
 * - calculate Expected Minutes
 * - calculate Player Intelligence
 * - reconstruct historical recommendation evidence
 * - query fixture history
 * - manufacture missing realised outcomes
 * - tune or calibrate the model
 *
 * It only compares evidence that has already been produced by
 * the recommendation-history and completed-gameweek pipelines.
 */
class PlayerProjectionBacktestService
{
    /**
     * Compare historical player projections with realised outcomes.
     */
    public function evaluate(
        int $gameweekId,
        array $playerProjections,
        array $playerOutcomes
    ): array {

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
         * NO HISTORICAL PROJECTIONS
         * ========================================================
         */

        if (
            empty(
                $playerProjections
            )
        ) {

            return [];
        }


        /*
         * ========================================================
         * BUILD REALISED OUTCOME LOOKUP
         * ========================================================
         *
         * PlayerGameweekOutcomeService has already aggregated
         * fixture-level evidence into one row per player.
         *
         * Local player ID is therefore the only join required here.
         */

        $outcomeLookup =
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
                isset(
                    $outcome[
                        'player_id'
                    ]
                )
                &&
                is_numeric(
                    $outcome[
                        'player_id'
                    ]
                )
                    ? (int) $outcome[
                        'player_id'
                    ]
                    : 0;


            if ($playerId <= 0) {

                continue;
            }


            $outcomeLookup[
                $playerId
            ] =
                $outcome;
        }


        /*
         * ========================================================
         * BUILD BACKTEST EVIDENCE
         * ========================================================
         *
         * Historical projection order is preserved deliberately.
         *
         * Recommendation history is the subject being evaluated;
         * realised outcomes are matched onto that evidence rather
         * than becoming the source of output membership/order.
         */

        $results =
            [];


        foreach (
            $playerProjections
            as $projection
        ) {

            if (
                !is_array(
                    $projection
                )
            ) {

                continue;
            }


            $playerId =
                isset(
                    $projection[
                        'player_id'
                    ]
                )
                &&
                is_numeric(
                    $projection[
                        'player_id'
                    ]
                )
                    ? (int) $projection[
                        'player_id'
                    ]
                    : 0;


            if ($playerId <= 0) {

                continue;
            }


            $outcome =
                $outcomeLookup[
                    $playerId
                ]
                ?? null;


            /*
             * ====================================================
             * HISTORICAL PROJECTED POINTS
             * ====================================================
             *
             * has_projected_points is part of the historical
             * evidence contract. A missing/unavailable projection
             * must not be converted into zero.
             */

            $hasProjectedPoints =
                (
                    $projection[
                        'has_projected_points'
                    ]
                    ?? false
                )
                ===
                true;


            $projectedPoints =
                $hasProjectedPoints
                &&
                isset(
                    $projection[
                        'projected_points'
                    ]
                )
                &&
                is_numeric(
                    $projection[
                        'projected_points'
                    ]
                )
                    ? (float) $projection[
                        'projected_points'
                    ]
                    : null;


            /*
             * ====================================================
             * REALISED POINTS
             * ====================================================
             *
             * Absence of an outcome row is not equivalent to zero
             * FPL points. Missing completed-gameweek evidence must
             * remain distinguishable from a genuine zero return.
             */

            $actualPoints =
                is_array(
                    $outcome
                )
                &&
                array_key_exists(
                    'total_points',
                    $outcome
                )
                &&
                is_numeric(
                    $outcome[
                        'total_points'
                    ]
                )
                    ? (int) $outcome[
                        'total_points'
                    ]
                    : null;


            /*
             * ====================================================
             * POINTS ERROR
             * ====================================================
             *
             * Signed error:
             *
             *     actual - projected
             *
             * Positive = player outperformed projection.
             * Negative = model over-projected player.
             */

            $pointsError =
                null;


            $absolutePointsError =
                null;


            if (
                $projectedPoints !== null
                &&
                $actualPoints !== null
            ) {

                $pointsError =
                    (float) (
                        $actualPoints
                        -
                        $projectedPoints
                    );


                $absolutePointsError =
                    abs(
                        $pointsError
                    );
            }


            /*
             * ====================================================
             * SUPPORTING HISTORICAL / REALISED EVIDENCE
             * ====================================================
             */

            $projectedMinutes =
                isset(
                    $projection[
                        'projected_minutes'
                    ]
                )
                &&
                is_numeric(
                    $projection[
                        'projected_minutes'
                    ]
                )
                    ? (float) $projection[
                        'projected_minutes'
                    ]
                    : null;


            $actualMinutes =
                is_array(
                    $outcome
                )
                &&
                array_key_exists(
                    'minutes',
                    $outcome
                )
                &&
                is_numeric(
                    $outcome[
                        'minutes'
                    ]
                )
                    ? (int) $outcome[
                        'minutes'
                    ]
                    : null;


            $intelligenceScore =
                isset(
                    $projection[
                        'intelligence_score'
                    ]
                )
                &&
                is_numeric(
                    $projection[
                        'intelligence_score'
                    ]
                )
                    ? (float) $projection[
                        'intelligence_score'
                    ]
                    : null;


            $projectionConfidence =
                isset(
                    $projection[
                        'projection_confidence'
                    ]
                )
                &&
                is_numeric(
                    $projection[
                        'projection_confidence'
                    ]
                )
                    ? (float) $projection[
                        'projection_confidence'
                    ]
                    : null;


            $fixtureCount =
                is_array(
                    $outcome
                )
                &&
                array_key_exists(
                    'fixture_count',
                    $outcome
                )
                &&
                is_numeric(
                    $outcome[
                        'fixture_count'
                    ]
                )
                    ? (int) $outcome[
                        'fixture_count'
                    ]
                    : null;


            /*
             * ====================================================
             * STABLE INITIAL BACKTEST CONTRACT
             * ====================================================
             */

            $results[] = [

                'gameweek_id' =>
                    $gameweekId,

                'player_id' =>
                    $playerId,

                'fpl_player_id' =>
                    $projection[
                        'fpl_player_id'
                    ]
                    ?? null,

                'name' =>
                    $projection[
                        'name'
                    ]
                    ?? null,

                'position' =>
                    $projection[
                        'position'
                    ]
                    ?? null,

                'intelligence_score' =>
                    $intelligenceScore,

                'projected_points' =>
                    $projectedPoints,

                'actual_points' =>
                    $actualPoints,

                'points_error' =>
                    $pointsError,

                'absolute_points_error' =>
                    $absolutePointsError,

                'projected_minutes' =>
                    $projectedMinutes,

                'actual_minutes' =>
                    $actualMinutes,

                'projection_confidence' =>
                    $projectionConfidence,

                'fixture_count' =>
                    $fixtureCount
            ];
        }


        return
            $results;
    }
}