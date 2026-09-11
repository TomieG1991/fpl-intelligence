<?php

class PlayerProjectionBacktestingService
{
    /**
     * Compare preserved player projection evidence with
     * realised completed-gameweek player outcomes.
     *
     * This service deliberately performs player-level
     * comparisons only.
     *
     * It does not:
     *
     * - calculate aggregate model metrics
     * - calculate MAE
     * - create a model score
     * - evaluate captain recommendations
     * - evaluate transfer recommendations
     * - alter source evidence
     */
    public function evaluate(
        array $playerProjections,
        array $playerOutcomes
    ): array {

        if (
            empty(
                $playerProjections
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


            $outcomeLookup[
                $playerId
            ] =
                $outcome;
        }


        /*
         * ========================================================
         * BUILD PLAYER-LEVEL COMPARISONS
         * ========================================================
         */

        $evaluations =
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


            if (
                $playerId <= 0
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


            if (
                empty(
                    $projection[
                        'has_projected_points'
                    ]
                    ?? false
                )
            ) {

                continue;
            }


            if (
                !isset(
                    $projection[
                        'projected_points'
                    ]
                )
                ||
                !is_numeric(
                    $projection[
                        'projected_points'
                    ]
                )
            ) {

                continue;
            }


            $outcome =
                $outcomeLookup[
                    $playerId
                ];


            $projectedPoints =
                (float) $projection[
                    'projected_points'
                ];


            $actualPoints =
                (int) (
                    $outcome[
                        'total_points'
                    ]
                    ?? 0
                );


            $pointsError =
                (float) (
                    $actualPoints
                    -
                    $projectedPoints
                );


            /*
             * ----------------------------------------------------
             * MINUTES EVIDENCE
             * ----------------------------------------------------
             *
             * Missing projected minutes remain unavailable.
             * We do not reinterpret missing evidence as zero.
             */

            $projectedMinutes =
                null;


            $actualMinutes =
                (int) (
                    $outcome[
                        'minutes'
                    ]
                    ?? 0
                );


            $minutesError =
                null;


            $absoluteMinutesError =
                null;


            if (
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
            ) {

                $projectedMinutes =
                    $projection[
                        'projected_minutes'
                    ];


                $minutesError =
                    (float) (
                        $actualMinutes
                        -
                        $projectedMinutes
                    );


                $absoluteMinutesError =
                    abs(
                        $minutesError
                    );
            }


            /*
             * ----------------------------------------------------
             * PRESERVE COMPARISON EVIDENCE
             * ----------------------------------------------------
             */

            $evaluations[] = [

            'player_id' =>
                $playerId,

            /*
             * Preserve recommendation-time projection context required
             * for later calibration diagnostics.
             *
             * These values are copied from immutable historical
             * projection evidence. They are not recalculated from live
             * player state.
             */
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

            'projection_confidence' =>
                $projection[
                    'projection_confidence'
                ]
                ?? null,

            'projection_confidence_percent' =>
                $projection[
                    'projection_confidence_percent'
                ]
                ?? null,

            'projection_confidence_label' =>
                $projection[
                    'projection_confidence_label'
                ]
                ?? null,

            'projected_points_components' =>
                is_array(
                    $projection[
                        'projected_points_components'
                    ]
                    ?? null
                )
                    ? $projection[
                        'projected_points_components'
                    ]
                    : [],

            'projected_points_inputs' =>
                is_array(
                    $projection[
                        'projected_points_inputs'
                    ]
                    ?? null
                )
                    ? $projection[
                        'projected_points_inputs'
                    ]
                    : [],

            'projected_points' =>
                $projectedPoints,

                'actual_points' =>
                    $actualPoints,

                'points_error' =>
                    $pointsError,

                'absolute_points_error' =>
                    abs(
                        $pointsError
                    ),

                'projected_minutes' =>
                    $projectedMinutes,

                'actual_minutes' =>
                    $actualMinutes,

                'minutes_error' =>
                    $minutesError,

                'absolute_minutes_error' =>
                    $absoluteMinutesError
            ];
        }


        return
            $evaluations;
    }
}