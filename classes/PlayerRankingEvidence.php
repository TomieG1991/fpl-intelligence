<?php

/**
 * PlayerRankingEvidence
 *
 * Adapts existing Player Intelligence summaries into stable
 * historical ranking evidence.
 *
 * This class does not:
 *
 * - calculate Player Intelligence
 * - calculate Expected Points
 * - alter Player Intelligence scores
 * - filter to a manager's squad
 * - use realised gameweek outcomes
 *
 * It only preserves and ranks existing recommendation-time
 * Player Intelligence evidence.
 */
class PlayerRankingEvidence
{
    /**
     * Build historical ranking evidence from existing
     * Player Intelligence summaries.
     */
    public function build(
        array $playerSummaries
    ): array {

        /*
         * ========================================================
         * EMPTY EVIDENCE
         * ========================================================
         */

        if (
            empty(
                $playerSummaries
            )
        ) {

            return [];
        }


        /*
         * ========================================================
         * PRESERVE USABLE RANKING EVIDENCE
         * ========================================================
         *
         * Only players with:
         *
         * - a positive local player ID
         * - a numeric Intelligence Score
         *
         * can participate in the historical ranking.
         *
         * We deliberately preserve only the explicitly defined
         * ranking contract rather than copying complete Player
         * Intelligence summaries.
         */

        $evidence =
            [];


        foreach (
            $playerSummaries
            as $summary
        ) {

            if (
                !is_array(
                    $summary
                )
            ) {

                continue;
            }


            $playerId =
                isset(
                    $summary[
                        'player_id'
                    ]
                )
                &&
                is_numeric(
                    $summary[
                        'player_id'
                    ]
                )
                    ? (int) $summary[
                        'player_id'
                    ]
                    : 0;


            if (
                $playerId <= 0
            ) {

                continue;
            }


            if (
                !isset(
                    $summary[
                        'intelligence_score'
                    ]
                )
                ||
                !is_numeric(
                    $summary[
                        'intelligence_score'
                    ]
                )
            ) {

                continue;
            }


            $evidence[] = [

                'player_id' =>
                    $playerId,

                'fpl_player_id' =>
                    $summary[
                        'fpl_player_id'
                    ]
                    ?? null,

                'name' =>
                    $summary[
                        'name'
                    ]
                    ?? null,

                'position' =>
                    $summary[
                        'position'
                    ]
                    ?? null,

                'team_id' =>
                    $summary[
                        'team_id'
                    ]
                    ?? null,

                'price' =>
                    $summary[
                        'price'
                    ]
                    ?? null,

                'strength_rating' =>
                    $summary[
                        'strength_rating'
                    ]
                    ?? null,

                'fixture_rating' =>
                    $summary[
                        'fixture_rating'
                    ]
                    ?? null,

                'next_fixture_rating' =>
                    $summary[
                        'next_fixture_rating'
                    ]
                    ?? null,

                'base_next_fixture_rating' =>
                    $summary[
                        'base_next_fixture_rating'
                    ]
                    ?? null,

                'next_opponent_attack_rating' =>
                    $summary[
                        'next_opponent_attack_rating'
                    ]
                    ?? null,

                'next_opponent_defence_rating' =>
                    $summary[
                        'next_opponent_defence_rating'
                    ]
                    ?? null,

                'availability_multiplier' =>
                    $summary[
                        'availability_multiplier'
                    ]
                    ?? null,

                'intelligence_score' =>
                    (float) $summary[
                        'intelligence_score'
                    ]
            ];
        }


        /*
         * ========================================================
         * RANK BY EXISTING INTELLIGENCE SCORE
         * ========================================================
         *
         * Higher Intelligence Scores rank first.
         *
         * Equal scores use the lower local player ID as the
         * deterministic tie-break.
         *
         * This does not create a new scoring model. It only orders
         * the existing Player Intelligence output.
         */

        usort(
            $evidence,
            static function (
                array $a,
                array $b
            ): int {

                $scoreComparison =
                    (
                        (float) $b[
                            'intelligence_score'
                        ]
                    )
                    <=>
                    (
                        (float) $a[
                            'intelligence_score'
                        ]
                    );


                if (
                    $scoreComparison !== 0
                ) {

                    return
                        $scoreComparison;
                }


                return
                    (
                        (int) $a[
                            'player_id'
                        ]
                    )
                    <=>
                    (
                        (int) $b[
                            'player_id'
                        ]
                    );
            }
        );


        /*
         * ========================================================
         * ASSIGN DETERMINISTIC RANK
         * ========================================================
         *
         * Rank represents ordering position rather than a new
         * model score.
         *
         * Equal Intelligence Scores therefore still receive
         * sequential deterministic positions.
         */

        foreach (
            $evidence
            as $index => &$player
        ) {

            $player[
                'rank'
            ] =
                $index
                +
                1;
        }


        unset(
            $player
        );


        return
            $evidence;
    }
}