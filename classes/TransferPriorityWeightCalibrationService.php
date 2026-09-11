<?php


/**
 * TransferPriorityWeightCalibrationService
 *
 * Pure analytical calibration of the top-level outgoing-player
 * priority weights used by SquadTransferIntelligence.
 *
 * This service does not:
 *
 * - query live player data
 * - reconstruct historical recommendation evidence
 * - determine legal replacement universes
 * - evaluate TransferDecision replacement quality
 * - alter production weights
 * - persist calibration results
 * - select a winning candidate
 *
 * Historical callers provide:
 *
 * - preserved recommendation-time squad player components
 * - each player's best realised legal transfer gain
 *
 * The service replays alternative outgoing-priority weights and
 * measures how much realised transfer opportunity was lost by
 * the selected outgoing player.
 */
class TransferPriorityWeightCalibrationService
{
    private const WEIGHT_TOLERANCE =
        0.000001;


    /*
     * ============================================================
     * PUBLIC API
     * ============================================================
     */

    public function evaluate(
        array $historicalGameweeks,
        array $weightCandidates
    ): array {

        if (empty($weightCandidates)) {

            return [

                'evaluations' =>
                    []
            ];
        }


        $evaluations =
            [];


        foreach (
            $weightCandidates
            as $weightCandidate
        ) {

            $weights =
                $this->validateWeightCandidate(
                    $weightCandidate
                );


            $gameweekEvaluations =
                [];


            foreach (
                $historicalGameweeks
                as $historicalGameweek
            ) {

                if (!is_array($historicalGameweek)) {

                    continue;
                }


                $gameweekEvaluations[] =
                    $this->evaluateGameweek(
                        $historicalGameweek,
                        $weights
                    );
            }


            $evaluations[] = [

                'intelligence_weight' =>
                    $weights[
                        'intelligence_weight'
                    ],

                'value_weight' =>
                    $weights[
                        'value_weight'
                    ],

                'fixture_weight' =>
                    $weights[
                        'fixture_weight'
                    ],

                'availability_weight' =>
                    $weights[
                        'availability_weight'
                    ],

                'gameweeks' =>
                    $gameweekEvaluations,

                'metrics' =>
                    $this->buildMetrics(
                        $gameweekEvaluations
                    )
            ];
        }


        return [

            'evaluations' =>
                $evaluations
        ];
    }


    /*
     * ============================================================
     * WEIGHT VALIDATION
     * ============================================================
     */

    private function validateWeightCandidate(
        mixed $candidate
    ): array {

        if (!is_array($candidate)) {

            throw new InvalidArgumentException(
                'Transfer priority weight candidate must be an array.'
            );
        }


        $requiredWeights = [

            'intelligence_weight',
            'value_weight',
            'fixture_weight',
            'availability_weight'
        ];


        $weights =
            [];


        foreach (
            $requiredWeights
            as $weightName
        ) {

            if (
                !array_key_exists(
                    $weightName,
                    $candidate
                )
            ) {

                throw new InvalidArgumentException(
                    'Transfer priority weight candidate is incomplete.'
                );
            }


            $value =
                $candidate[
                    $weightName
                ];


            if (!is_numeric($value)) {

                throw new InvalidArgumentException(
                    'Transfer priority weights must be numeric.'
                );
            }


            $value =
                (float) $value;


            if (
                $value < 0.0
                ||
                $value > 1.0
            ) {

                throw new InvalidArgumentException(
                    'Transfer priority weights must be between 0 and 1.'
                );
            }


            $weights[
                $weightName
            ] =
                $value;
        }


        $weightSum =
            array_sum(
                $weights
            );


        if (
            abs(
                $weightSum
                -
                1.0
            )
            >
            self::WEIGHT_TOLERANCE
        ) {

            throw new InvalidArgumentException(
                'Transfer priority weights must sum to 1.'
            );
        }


        return
            $weights;
    }


    /*
     * ============================================================
     * GAMEWEEK EVALUATION
     * ============================================================
     */

    private function evaluateGameweek(
        array $historicalGameweek,
        array $weights
    ): array {

        $gameweekId =
            isset(
                $historicalGameweek[
                    'gameweek_id'
                ]
            )
            &&
            is_numeric(
                $historicalGameweek[
                    'gameweek_id'
                ]
            )
                ? (int) $historicalGameweek[
                    'gameweek_id'
                ]
                : null;


        $players =
            $historicalGameweek[
                'players'
            ]
            ?? [];


        if (!is_array($players)) {

            $players =
                [];
        }


        $playerPriorities =
            [];


        foreach (
            $players
            as $player
        ) {

            if (!is_array($player)) {

                continue;
            }


            $playerId =
                isset(
                    $player[
                        'player_id'
                    ]
                )
                &&
                is_numeric(
                    $player[
                        'player_id'
                    ]
                )
                    ? (int) $player[
                        'player_id'
                    ]
                    : 0;


            if ($playerId <= 0) {

                continue;
            }


            $playerPriorities[] =
                $this->buildPlayerPriority(
                    $player,
                    $weights
                );
        }


        /*
         * Determine the candidate-weight outgoing selection.
         */
        $scoreablePlayers =
            array_values(
                array_filter(
                    $playerPriorities,
                    static function (
                        array $player
                    ): bool {

                        return is_numeric(
                            $player[
                                'transfer_priority'
                            ]
                            ?? null
                        );
                    }
                )
            );


        usort(
            $scoreablePlayers,
            function (
                array $a,
                array $b
            ): int {

                return
                    $this->comparePriorityPlayers(
                        $a,
                        $b
                    );
            }
        );


        $selectedOutgoing =
            $scoreablePlayers[
                0
            ]
            ?? null;


        /*
         * Determine the best realised outgoing opportunity
         * independently of candidate priority.
         */
        $bestRealisedOutgoing =
            null;


        foreach (
            $playerPriorities
            as $player
        ) {

            $realisedGain =
                $player[
                    'best_realised_transfer_gain'
                ]
                ?? null;


            if (!is_numeric($realisedGain)) {

                continue;
            }


            if (
                $bestRealisedOutgoing === null
                ||
                $realisedGain
                >
                $bestRealisedOutgoing[
                    'best_realised_transfer_gain'
                ]
                ||
                (
                    $realisedGain
                    ===
                    $bestRealisedOutgoing[
                        'best_realised_transfer_gain'
                    ]
                    &&
                    (
                        $player[
                            'player_id'
                        ]
                        ?? PHP_INT_MAX
                    )
                    <
                    (
                        $bestRealisedOutgoing[
                            'player_id'
                        ]
                        ?? PHP_INT_MAX
                    )
                )
            ) {

                $bestRealisedOutgoing =
                    $player;
            }
        }


        $selectedRealisedGain =
            null;


        if (
            is_array($selectedOutgoing)
            &&
            is_numeric(
                $selectedOutgoing[
                    'best_realised_transfer_gain'
                ]
                ?? null
            )
        ) {

            $selectedRealisedGain =
                $selectedOutgoing[
                    'best_realised_transfer_gain'
                ];
        }


        $bestRealisedGain =
            null;


        if (
            is_array($bestRealisedOutgoing)
            &&
            is_numeric(
                $bestRealisedOutgoing[
                    'best_realised_transfer_gain'
                ]
                ?? null
            )
        ) {

            $bestRealisedGain =
                $bestRealisedOutgoing[
                    'best_realised_transfer_gain'
                ];
        }


        $selectionPointsLost =
            null;


        if (
            $selectedRealisedGain !== null
            &&
            $bestRealisedGain !== null
        ) {

            $selectionPointsLost =
                $this->preserveDerivedNumber(
                    round(
                        $bestRealisedGain
                        -
                        $selectedRealisedGain,
                        2
                    )
                );


            /*
             * The selected player is part of the same realised
             * universe, so floating-point noise must never create
             * a negative "points lost" value.
             */
            if ($selectionPointsLost < 0) {

                $selectionPointsLost =
                    0;
            }
        }


        return [

            'gameweek_id' =>
                $gameweekId,

            'player_priorities' =>
                $playerPriorities,

            'selected_outgoing' =>
                $selectedOutgoing,

            'selected_realised_gain' =>
                $selectedRealisedGain,

            'best_realised_outgoing' =>
                $bestRealisedOutgoing,

            'best_realised_gain' =>
                $bestRealisedGain,

            'selection_points_lost' =>
                $selectionPointsLost
        ];
    }


    /*
     * ============================================================
     * PLAYER PRIORITY
     * ============================================================
     */

    private function buildPlayerPriority(
        array $player,
        array $weights
    ): array {

        $playerId =
            (int) (
                $player[
                    'player_id'
                ]
                ?? 0
            );


        $intelligence =
            $this->numericOrNull(
                $player[
                    'intelligence_score'
                ]
                ?? null
            );


        $value =
            $this->numericOrNull(
                $player[
                    'value_rating'
                ]
                ?? null
            );


        $fixture =
            $this->numericOrNull(
                $player[
                    'fixture_rating'
                ]
                ?? null
            );


        $availability =
            $this->numericOrNull(
                $player[
                    'availability_rating'
                ]
                ?? null
            );


        $intelligenceWeakness =
            $this->weakness(
                $intelligence
            );


        $valueWeakness =
            $this->weakness(
                $value
            );


        $fixtureWeakness =
            $this->weakness(
                $fixture
            );


        $availabilityWeakness =
            $this->weakness(
                $availability
            );


        /*
         * Historical calibration does not manufacture maximum
         * weakness from unavailable evidence.
         *
         * Candidate weights are renormalised across only the
         * recommendation-time components that actually exist.
         */

        $weightedWeakness =
            0.0;


        $availableWeight =
            0.0;


        $componentDefinitions = [

            [
                'weakness' =>
                    $intelligenceWeakness,

                'weight' =>
                    $weights[
                        'intelligence_weight'
                    ]
            ],

            [
                'weakness' =>
                    $valueWeakness,

                'weight' =>
                    $weights[
                        'value_weight'
                    ]
            ],

            [
                'weakness' =>
                    $fixtureWeakness,

                'weight' =>
                    $weights[
                        'fixture_weight'
                    ]
            ],

            [
                'weakness' =>
                    $availabilityWeakness,

                'weight' =>
                    $weights[
                        'availability_weight'
                    ]
            ]
        ];


        foreach (
            $componentDefinitions
            as $component
        ) {

            if (
                $component[
                    'weakness'
                ]
                ===
                null
            ) {

                continue;
            }


            $weightedWeakness +=
                $component[
                    'weakness'
                ]
                *
                $component[
                    'weight'
                ];


            $availableWeight +=
                $component[
                    'weight'
                ];
        }


        $transferPriority =
            null;


        if ($availableWeight > 0.0) {

            $transferPriority =
                round(
                    max(
                        0.0,
                        min(
                            100.0,
                            $weightedWeakness
                            /
                            $availableWeight
                        )
                    ),
                    1
                );
        }


        $bestRealisedTransferGain =
            null;


        if (
            array_key_exists(
                'best_realised_transfer_gain',
                $player
            )
            &&
            $player[
                'best_realised_transfer_gain'
            ]
            !== null
            &&
            is_numeric(
                $player[
                    'best_realised_transfer_gain'
                ]
            )
        ) {

            /*
             * Preserve genuine integer, zero and negative evidence.
             */
            $bestRealisedTransferGain =
                $player[
                    'best_realised_transfer_gain'
                ]
                + 0;
        }


        return [

            'player_id' =>
                $playerId,

            'name' =>
                $player[
                    'name'
                ]
                ?? null,

            'intelligence_score' =>
                $intelligence,

            'value_rating' =>
                $value,

            'fixture_rating' =>
                $fixture,

            'availability_rating' =>
                $availability,

            'intelligence_weakness' =>
                $intelligenceWeakness,

            'value_weakness' =>
                $valueWeakness,

            'fixture_weakness' =>
                $fixtureWeakness,

            'availability_weakness' =>
                $availabilityWeakness,

            'transfer_priority' =>
                $transferPriority,

            'best_realised_transfer_gain' =>
                $bestRealisedTransferGain
        ];
    }


    /*
     * ============================================================
     * DETERMINISTIC PRIORITY ORDER
     * ============================================================
     *
     * Production SquadTransferIntelligence sorts by transfer
     * priority only.
     *
     * Alternative calibration candidates can create exact ties.
     * A deterministic secondary order makes historical replay
     * stable without changing the primary model signal.
     */

    private function comparePriorityPlayers(
        array $a,
        array $b
    ): int {

        $priorityA =
            $this->sortableNumeric(
                $a[
                    'transfer_priority'
                ]
                ?? null
            );


        $priorityB =
            $this->sortableNumeric(
                $b[
                    'transfer_priority'
                ]
                ?? null
            );


        if ($priorityA !== $priorityB) {

            return
                $priorityB
                <=>
                $priorityA;
        }


        /*
         * 2. Intelligence weakness
         */
        $comparison =
            $this->compareNullableDescending(
                $a[
                    'intelligence_weakness'
                ]
                ?? null,
                $b[
                    'intelligence_weakness'
                ]
                ?? null
            );


        if ($comparison !== 0) {

            return
                $comparison;
        }


        /*
         * 3. Fixture weakness
         */
        $comparison =
            $this->compareNullableDescending(
                $a[
                    'fixture_weakness'
                ]
                ?? null,
                $b[
                    'fixture_weakness'
                ]
                ?? null
            );


        if ($comparison !== 0) {

            return
                $comparison;
        }


        /*
         * 4. Value weakness
         */
        $comparison =
            $this->compareNullableDescending(
                $a[
                    'value_weakness'
                ]
                ?? null,
                $b[
                    'value_weakness'
                ]
                ?? null
            );


        if ($comparison !== 0) {

            return
                $comparison;
        }


        /*
         * 5. Availability weakness
         */
        $comparison =
            $this->compareNullableDescending(
                $a[
                    'availability_weakness'
                ]
                ?? null,
                $b[
                    'availability_weakness'
                ]
                ?? null
            );


        if ($comparison !== 0) {

            return
                $comparison;
        }


        /*
         * 6. Stable local player identity
         */
        return
            (
                (int) (
                    $a[
                        'player_id'
                    ]
                    ?? PHP_INT_MAX
                )
            )
            <=>
            (
                (int) (
                    $b[
                        'player_id'
                    ]
                    ?? PHP_INT_MAX
                )
            );
    }


    /*
     * ============================================================
     * AGGREGATE METRICS
     * ============================================================
     */

    private function buildMetrics(
        array $gameweeks
    ): array {

        $totalGameweeks =
            count(
                $gameweeks
            );


        $comparableGameweeks =
            0;


        $unavailableGameweeks =
            0;


        $totalSelectionPointsLost =
            0.0;


        $optimalOutgoingSelections =
            0;


        foreach (
            $gameweeks
            as $gameweek
        ) {

            if (!is_array($gameweek)) {

                continue;
            }


            $selectionPointsLost =
                $gameweek[
                    'selection_points_lost'
                ]
                ?? null;


            if (!is_numeric($selectionPointsLost)) {

                $unavailableGameweeks++;

                continue;
            }


            $comparableGameweeks++;


            $totalSelectionPointsLost +=
                (float) $selectionPointsLost;


            if (
                abs(
                    (float) $selectionPointsLost
                )
                <=
                self::WEIGHT_TOLERANCE
            ) {

                $optimalOutgoingSelections++;
            }
        }


        $totalSelectionPointsLost =
            $this->preserveDerivedNumber(
                round(
                    $totalSelectionPointsLost,
                    2
                )
            );


        $meanSelectionPointsLost =
            $comparableGameweeks > 0
                ? round(
                    $totalSelectionPointsLost
                    /
                    $comparableGameweeks,
                    2
                )
                : null;


        return [

            'total_gameweeks' =>
                $totalGameweeks,

            'comparable_gameweeks' =>
                $comparableGameweeks,

            'unavailable_gameweeks' =>
                $unavailableGameweeks,

            'total_selection_points_lost' =>
                $totalSelectionPointsLost,

            'mean_selection_points_lost' =>
                $meanSelectionPointsLost,

            'optimal_outgoing_selections' =>
                $optimalOutgoingSelections
        ];
    }
    
    
    /*
     * ============================================================
     * DERIVED NUMERIC RESULT
     * ============================================================
     *
     * Preserve whole-number analytical results as integers while
     * retaining genuine decimal results as floats.
     *
     * Examples:
     *
     * 6.0  => 6
     * 0.0  => 0
     * 4.67 => 4.67
     */

    private function preserveDerivedNumber(
        int|float $value
    ): int|float {

        $roundedInteger =
            round(
                $value
            );


        if (
            abs(
                (float) $value
                -
                (float) $roundedInteger
            )
            <=
            self::WEIGHT_TOLERANCE
        ) {

            return
                (int) $roundedInteger;
        }


        return
            (float) $value;
    }


    /*
     * ============================================================
     * WEAKNESS
     * ============================================================
     */

    private function weakness(
        int|float|null $value
    ): ?float {

        if ($value === null) {

            return null;
        }


        $value =
            max(
                0.0,
                min(
                    100.0,
                    (float) $value
                )
            );


        return
            round(
                100.0
                -
                $value,
                2
            );
    }


    /*
     * ============================================================
     * NUMERIC EVIDENCE
     * ============================================================
     */

    private function numericOrNull(
        mixed $value
    ): int|float|null {

        if (
            $value === null
            ||
            !is_numeric($value)
        ) {

            return null;
        }


        return
            $value + 0;
    }


    /*
     * ============================================================
     * SORT SUPPORT
     * ============================================================
     */

    private function sortableNumeric(
        mixed $value
    ): float {

        return is_numeric($value)
            ? (float) $value
            : -INF;
    }


    private function compareNullableDescending(
        mixed $a,
        mixed $b
    ): int {

        $numericA =
            $this->sortableNumeric(
                $a
            );


        $numericB =
            $this->sortableNumeric(
                $b
            );


        return
            $numericB
            <=>
            $numericA;
    }
}