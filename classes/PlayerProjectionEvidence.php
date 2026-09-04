<?php

/**
 * PlayerProjectionEvidence
 *
 * Adapts existing Player Intelligence summaries into a stable
 * historical projection-evidence structure for the players in
 * a manager's squad.
 *
 * This class does not:
 *
 * - calculate Expected Points
 * - calculate Expected Minutes
 * - calculate projection confidence
 * - calculate Player Intelligence
 * - alter or optimise the manager's squad
 *
 * It only selects and preserves existing model evidence.
 */
class PlayerProjectionEvidence
{
    /**
     * Build historical projection evidence for the supplied squad.
     */
    public function build(
        array $squadPlayers,
        array $playerSummaries
    ): array {

        /*
         * ========================================================
         * REQUIRE SQUAD
         * ========================================================
         */

        if (
            empty(
                $squadPlayers
            )
        ) {

            throw new InvalidArgumentException(
                'Squad player evidence is required.'
            );
        }


        /*
         * ========================================================
         * REQUIRE PLAYER INTELLIGENCE
         * ========================================================
         */

        if (
            empty(
                $playerSummaries
            )
        ) {

            throw new InvalidArgumentException(
                'Player Intelligence summaries are required.'
            );
        }


        /*
         * ========================================================
         * BUILD PLAYER SUMMARY LOOKUP
         * ========================================================
         *
         * Recommendation history uses the local player ID as the
         * connection between the manager squad and the existing
         * Player Intelligence summaries.
         */

        $summaryLookup =
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


            $summaryLookup[
                $playerId
            ] =
                $summary;
        }


        /*
         * ========================================================
         * BUILD SQUAD HISTORICAL EVIDENCE
         * ========================================================
         */

        $evidence =
            [];


        foreach (
            $squadPlayers
            as $squadPlayer
        ) {

            if (
                !is_array(
                    $squadPlayer
                )
            ) {

                throw new InvalidArgumentException(
                    'Each squad player must be an array.'
                );
            }


            $playerId =
                isset(
                    $squadPlayer[
                        'player_id'
                    ]
                )
                &&
                is_numeric(
                    $squadPlayer[
                        'player_id'
                    ]
                )
                    ? (int) $squadPlayer[
                        'player_id'
                    ]
                    : 0;


            if (
                $playerId <= 0
            ) {

                throw new InvalidArgumentException(
                    'Each squad player must have a positive local player ID.'
                );
            }


            if (
                !isset(
                    $summaryLookup[
                        $playerId
                    ]
                )
            ) {

                throw new InvalidArgumentException(
                    'Player Intelligence summary is missing for squad player ID '
                    . $playerId
                    . '.'
                );
            }


            $summary =
                $summaryLookup[
                    $playerId
                ];


            /*
             * Preserve only the explicitly defined historical
             * projection contract.
             *
             * We deliberately do not copy the complete Player
             * Intelligence summary because unrelated future fields
             * should not silently become part of recommendation
             * history.
             */
            $evidence[] = [

                'player_id' =>
                    $summary[
                        'player_id'
                    ]
                    ?? null,

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

                'intelligence_score' =>
                    $summary[
                        'intelligence_score'
                    ]
                    ?? null,

                'projected_points' =>
                    $summary[
                        'projected_points'
                    ]
                    ?? null,

                'projected_minutes' =>
                    $summary[
                        'projected_minutes'
                    ]
                    ?? null,

                'projection_confidence' =>
                    $summary[
                        'projection_confidence'
                    ]
                    ?? null,

                'projection_confidence_percent' =>
                    $summary[
                        'projection_confidence_percent'
                    ]
                    ?? null,

                'projection_confidence_label' =>
                    $summary[
                        'projection_confidence_label'
                    ]
                    ?? null,

                'projected_points_components' =>
                    $summary[
                        'projected_points_components'
                    ]
                    ?? [],

                'projected_points_inputs' =>
                    $summary[
                        'projected_points_inputs'
                    ]
                    ?? [],

                'has_projected_points' =>
                    $summary[
                        'has_projected_points'
                    ]
                    ?? false
            ];
        }


        return
            $evidence;
    }
}