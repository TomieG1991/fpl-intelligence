<?php

/**
 * PlayerRankingBacktestingService
 *
 * Compares preserved historical Player Ranking Evidence with
 * realised completed-gameweek player outcomes.
 *
 * This service deliberately performs player-level factual
 * comparisons only.
 *
 * It does not:
 *
 * - calculate correlation
 * - calculate aggregate ranking metrics
 * - recalculate Intelligence Scores
 * - regenerate historical ranks
 * - rank players by realised points
 * - tune the Intelligence model
 * - mutate source evidence
 */
class PlayerRankingBacktestingService
{
    /**
     * Compare preserved player ranking evidence with
     * realised completed-gameweek player outcomes.
     */
    public function evaluate(
        array $playerRankings,
        array $playerOutcomes
    ): array {

        if (
            empty(
                $playerRankings
            )
            ||
            empty(
                $playerOutcomes
            )
        ) {

            return [];
        }


        /*
         * ========================================================
         * BUILD OUTCOME LOOKUP
         * ========================================================
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


            if (
                $playerId <= 0
            ) {

                continue;
            }


            /*
             * Realised points are required for ranking
             * backtesting.
             *
             * Missing or malformed points must not be
             * manufactured as zero.
             */
            if (
                !array_key_exists(
                    'total_points',
                    $outcome
                )
                ||
                !is_numeric(
                    $outcome[
                        'total_points'
                    ]
                )
            ) {

                continue;
            }


            $outcomeLookup[
                $playerId
            ] =
                $outcome;
        }


        /*
         * ========================================================
         * BUILD PLAYER-LEVEL RANKING COMPARISONS
         * ========================================================
         */

        $evaluations =
            [];


        foreach (
            $playerRankings
            as $ranking
        ) {

            if (
                !is_array(
                    $ranking
                )
            ) {

                continue;
            }


            $playerId =
                isset(
                    $ranking[
                        'player_id'
                    ]
                )
                &&
                is_numeric(
                    $ranking[
                        'player_id'
                    ]
                )
                    ? (int) $ranking[
                        'player_id'
                    ]
                    : 0;


            if (
                $playerId <= 0
            ) {

                continue;
            }


            /*
             * Intelligence Score and historical rank are the
             * essential preserved ranking evidence.
             */
            if (
                !array_key_exists(
                    'intelligence_score',
                    $ranking
                )
                ||
                !is_numeric(
                    $ranking[
                        'intelligence_score'
                    ]
                )
            ) {

                continue;
            }


            $rank =
                isset(
                    $ranking[
                        'rank'
                    ]
                )
                &&
                is_numeric(
                    $ranking[
                        'rank'
                    ]
                )
                    ? (int) $ranking[
                        'rank'
                    ]
                    : 0;


            if (
                $rank <= 0
            ) {

                continue;
            }


            if (
                !isset(
                    $outcomeLookup[
                        $playerId
                    ]
                )
            ) {

                continue;
            }


            $outcome =
                $outcomeLookup[
                    $playerId
                ];


            /*
             * ----------------------------------------------------
             * REALISED OUTCOME EVIDENCE
             * ----------------------------------------------------
             */

            $actualPoints =
                (int) $outcome[
                    'total_points'
                ];


            $actualMinutes =
                null;


            if (
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
            ) {

                $actualMinutes =
                    (int) $outcome[
                        'minutes'
                    ];
            }


            /*
             * ----------------------------------------------------
             * PRESERVE HISTORICAL RANKING EVIDENCE
             * ----------------------------------------------------
             *
             * These values are copied from the immutable
             * recommendation snapshot.
             *
             * They are never recalculated from realised results.
             */

            $evaluations[] = [

                'player_id' =>
                    $playerId,

                'fpl_player_id' =>
                    isset(
                        $ranking[
                            'fpl_player_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $ranking[
                            'fpl_player_id'
                        ]
                    )
                        ? (int) $ranking[
                            'fpl_player_id'
                        ]
                        : null,

                'name' =>
                    $ranking[
                        'name'
                    ]
                    ?? null,

                'position' =>
                    $ranking[
                        'position'
                    ]
                    ?? null,

                'team_id' =>
                    isset(
                        $ranking[
                            'team_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $ranking[
                            'team_id'
                        ]
                    )
                        ? (int) $ranking[
                            'team_id'
                        ]
                        : null,

                'price' =>
                    isset(
                        $ranking[
                            'price'
                        ]
                    )
                    &&
                    is_numeric(
                        $ranking[
                            'price'
                        ]
                    )
                        ? (float) $ranking[
                            'price'
                        ]
                        : null,

                'intelligence_score' =>
                    (float) $ranking[
                        'intelligence_score'
                    ],

                'rank' =>
                    $rank,

                'actual_points' =>
                    $actualPoints,

                'actual_minutes' =>
                    $actualMinutes
            ];
        }


        return
            $evaluations;
    }
}