<?php

/**
 * Evaluates the quality of a preserved Starting XI selection
 * against the best legally selectable XI from the same
 * preserved 15-player squad using realised gameweek outcomes.
 *
 * This service does not:
 *
 * - recalculate Expected Points
 * - recalculate Player Intelligence
 * - change the preserved squad
 * - simulate automatic substitutions
 * - evaluate captaincy
 * - evaluate transfers
 * - manufacture an accuracy score
 * - manufacture an overall backtesting score
 *
 * The comparison is strictly:
 *
 *     recommended XI realised points
 *
 * versus
 *
 *     best legal XI realised points
 *
 * from the same preserved 15-player squad.
 */
class StartingXISelectionBacktestingService
{
    /**
     * Evaluate a preserved Starting XI against the best legal
     * realised XI available from the same 15-player squad.
     *
     * A legal FPL Starting XI contains:
     *
     * - exactly 1 goalkeeper
     * - 3 to 5 defenders
     * - 2 to 5 midfielders
     * - 1 to 3 forwards
     * - exactly 11 players
     *
     * @param array $startingXI
     * @param array $bench
     * @param array $playerOutcomes
     *
     * @return array
     */
    public function evaluate(
        array $startingXI,
        array $bench,
        array $playerOutcomes
    ): array {

        /*
         * ====================================================
         * BASIC EVIDENCE REQUIREMENTS
         * ====================================================
         */

        if (
            empty($startingXI)
            ||
            empty($playerOutcomes)
        ) {

            return [];
        }


        /*
         * A legal-XI comparison requires the complete
         * preserved squad:
         *
         * 11 recommended starters + 4 bench players.
         */
        if (
            count($startingXI) !== 11
            ||
            count($bench) !== 4
        ) {

            return [];
        }


        $squad =
            array_merge(
                $startingXI,
                $bench
            );


        if (
            count($squad) !== 15
        ) {

            return [];
        }


        /*
         * ====================================================
         * BUILD AUTHORITATIVE OUTCOME LOOKUP
         * ====================================================
         */

        $outcomesByPlayerId =
            [];


        foreach (
            $playerOutcomes
            as $outcome
        ) {

            if (
                !is_array($outcome)
            ) {

                continue;
            }


            $playerId =
                $outcome[
                    'player_id'
                ]
                ?? null;


            if (
                !is_numeric($playerId)
                ||
                (int) $playerId <= 0
            ) {

                continue;
            }


            $outcomesByPlayerId[
                (int) $playerId
            ] =
                $outcome;
        }


        /*
         * ====================================================
         * VALIDATE AND ASSEMBLE PRESERVED SQUAD
         * ====================================================
         *
         * Every preserved player must have:
         *
         * - a valid unique local player ID
         * - a recognised FPL position
         * - authoritative realised points
         *
         * Missing evidence must not be manufactured as zero.
         * ====================================================
         */

        $evaluatedSquad =
            [];


        $seenPlayerIds =
            [];


        $validPositions =
            [
                'GK',
                'DEF',
                'MID',
                'FWD'
            ];


        foreach (
            $squad
            as $player
        ) {

            if (
                !is_array($player)
            ) {

                return [];
            }


            $playerId =
                $player[
                    'player_id'
                ]
                ?? null;


            if (
                !is_numeric($playerId)
                ||
                (int) $playerId <= 0
            ) {

                return [];
            }


            $playerId =
                (int) $playerId;


            /*
             * The same preserved player cannot occupy more
             * than one squad place.
             */
            if (
                isset(
                    $seenPlayerIds[
                        $playerId
                    ]
                )
            ) {

                return [];
            }


            $seenPlayerIds[
                $playerId
            ] =
                true;


            $position =
                strtoupper(
                    trim(
                        (string) (
                            $player[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                );


            if (
                !in_array(
                    $position,
                    $validPositions,
                    true
                )
            ) {

                return [];
            }


            if (
                !array_key_exists(
                    $playerId,
                    $outcomesByPlayerId
                )
            ) {

                /*
                 * We cannot claim to know the best realised XI
                 * if one of the 15 squad outcomes is missing.
                 */
                return [];
            }


            $outcome =
                $outcomesByPlayerId[
                    $playerId
                ];


            $actualPoints =
                $outcome[
                    'total_points'
                ]
                ?? null;


            if (
                !is_numeric(
                    $actualPoints
                )
            ) {

                return [];
            }


            $evaluatedSquad[] = [

                'player_id' =>
                    $playerId,

                'name' =>
                    $player[
                        'name'
                    ]
                    ?? null,

                'position' =>
                    $position,

                'actual_points' =>
                    $actualPoints,

                'actual_minutes' =>
                    is_numeric(
                        $outcome[
                            'minutes'
                        ]
                        ?? null
                    )
                        ? $outcome[
                            'minutes'
                        ]
                        : null
            ];
        }


        /*
         * ====================================================
         * VALIDATE RECOMMENDED XI
         * ====================================================
         *
         * The historical recommendation itself must be a legal
         * FPL Starting XI before it can be compared fairly with
         * the best legal realised XI.
         *
         * A legal recommended XI contains:
         *
         * - exactly 1 goalkeeper
         * - 3 to 5 defenders
         * - 2 to 5 midfielders
         * - 1 to 3 forwards
         * - exactly 11 players
         * ====================================================
         */

        $recommendedPlayerIds =
            [];


        $recommendedPositionCounts =
            [
                'GK' => 0,
                'DEF' => 0,
                'MID' => 0,
                'FWD' => 0
            ];


        foreach (
            $startingXI
            as $player
        ) {

            $playerId =
                (int) $player[
                    'player_id'
                ];


            $recommendedPlayerIds[] =
                $playerId;


            /*
             * The full preserved squad has already been validated
             * above, so the matching evaluated squad row provides
             * the canonical validated position.
             */
            foreach (
                $evaluatedSquad
                as $evaluatedPlayer
            ) {

                if (
                    $evaluatedPlayer[
                        'player_id'
                    ]
                    !==
                    $playerId
                ) {

                    continue;
                }


                $recommendedPositionCounts[
                    $evaluatedPlayer[
                        'position'
                    ]
                ]++;


                break;
            }
        }


        if (
            count(
                $recommendedPlayerIds
            )
            !== 11
            ||
            $recommendedPositionCounts['GK'] !== 1
            ||
            $recommendedPositionCounts['DEF'] < 3
            ||
            $recommendedPositionCounts['DEF'] > 5
            ||
            $recommendedPositionCounts['MID'] < 2
            ||
            $recommendedPositionCounts['MID'] > 5
            ||
            $recommendedPositionCounts['FWD'] < 1
            ||
            $recommendedPositionCounts['FWD'] > 3
        ) {

            return [];
        }


        /*
         * ====================================================
         * CALCULATE RECOMMENDED XI REALISED POINTS
         * ====================================================
         */


        $recommendedXIPoints =
            0;


        foreach (
            $evaluatedSquad
            as $player
        ) {

            if (
                in_array(
                    $player[
                        'player_id'
                    ],
                    $recommendedPlayerIds,
                    true
                )
            ) {

                $recommendedXIPoints +=
                    $player[
                        'actual_points'
                    ];
            }
        }


        /*
         * ====================================================
         * FIND BEST LEGAL XI
         * ====================================================
         *
         * There are only 15 players in an FPL squad.
         *
         * C(15, 11) = 1365 possible eleven-player subsets.
         *
         * Exhaustively checking those combinations is small,
         * deterministic and avoids introducing a second
         * scoring or optimisation model.
         *
         * We therefore enumerate every 11-player subset and
         * retain the highest-scoring legal FPL formation.
         * ====================================================
         */

        $bestLegalXI =
            [];


        $bestLegalXIPoints =
            null;


        $squadCount =
            count(
                $evaluatedSquad
            );


        /*
         * Represent a subset using a 15-bit mask.
         *
         * 2^15 = 32768 possible masks. Only masks containing
         * exactly eleven players proceed to formation checks.
         *
         * This remains tiny for a fixed FPL squad.
         */
        $maximumMask =
            1 << $squadCount;


        for (
            $mask = 0;
            $mask < $maximumMask;
            $mask++
        ) {

            /*
             * Count selected players without relying on a
             * PHP extension or version-specific helper.
             */
            $selectedCount =
                0;


            for (
                $index = 0;
                $index < $squadCount;
                $index++
            ) {

                if (
                    (
                        $mask
                        &
                        (
                            1 << $index
                        )
                    )
                    !== 0
                ) {

                    $selectedCount++;
                }
            }


            if (
                $selectedCount !== 11
            ) {

                continue;
            }


            $candidateXI =
                [];


            $positionCounts =
                [
                    'GK' => 0,
                    'DEF' => 0,
                    'MID' => 0,
                    'FWD' => 0
                ];


            $candidatePoints =
                0;


            for (
                $index = 0;
                $index < $squadCount;
                $index++
            ) {

                if (
                    (
                        $mask
                        &
                        (
                            1 << $index
                        )
                    )
                    === 0
                ) {

                    continue;
                }


                $player =
                    $evaluatedSquad[
                        $index
                    ];


                $candidateXI[] =
                    $player;


                $positionCounts[
                    $player[
                        'position'
                    ]
                ]++;


                $candidatePoints +=
                    $player[
                        'actual_points'
                    ];
            }


            /*
             * Normal FPL Starting XI formation constraints.
             */
            if (
                $positionCounts['GK'] !== 1
                ||
                $positionCounts['DEF'] < 3
                ||
                $positionCounts['DEF'] > 5
                ||
                $positionCounts['MID'] < 2
                ||
                $positionCounts['MID'] > 5
                ||
                $positionCounts['FWD'] < 1
                ||
                $positionCounts['FWD'] > 3
            ) {

                continue;
            }


            /*
             * Keep the highest-scoring legal XI.
             *
             * On equal realised points, retain the first legal
             * XI encountered. We do not invent a secondary
             * backtesting tiebreak.
             */
            if (
                $bestLegalXIPoints === null
                ||
                $candidatePoints
                    >
                    $bestLegalXIPoints
            ) {

                $bestLegalXIPoints =
                    $candidatePoints;


                $bestLegalXI =
                    $candidateXI;
            }
        }


        /*
         * A valid FPL 15-player squad should always permit a
         * legal XI. If the preserved evidence does not, the
         * historical comparison is unavailable.
         */
        if (
            $bestLegalXIPoints === null
            ||
            count($bestLegalXI) !== 11
        ) {

            return [];
        }


        /*
         * ====================================================
         * FACTUAL SELECTION OPPORTUNITY
         * ====================================================
         */

        $selectionPointsLost =
            $bestLegalXIPoints
            -
            $recommendedXIPoints;


        /*
         * A valid recommended legal XI should never score more
         * than the exhaustive best legal XI.
         *
         * If inconsistent historical evidence somehow causes
         * that situation, do not manufacture a negative
         * "points lost" result.
         */
        if (
            $selectionPointsLost < 0
        ) {

            return [];
        }


        return [

            'recommended_xi_points' =>
                $recommendedXIPoints,

            'best_legal_xi' =>
                $bestLegalXI,

            'best_legal_xi_points' =>
                $bestLegalXIPoints,

            'selection_points_lost' =>
                $selectionPointsLost
        ];
    }
}