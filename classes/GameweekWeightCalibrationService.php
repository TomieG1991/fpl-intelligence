<?php

/**
 * Evaluates alternative Gameweek Starting XI core weightings
 * against preserved historical recommendation evidence.
 *
 * The service is deliberately analytical only.
 *
 * It does not:
 *
 * - query live player data
 * - recalculate Player Intelligence
 * - recalculate fixture compression
 * - recalculate Effective Confidence
 * - recalculate availability
 * - change production Gameweek weights
 * - persist calibration results
 * - choose a winning production model
 *
 * Alternative Gameweek scores are replayed from recommendation-
 * time component evidence already preserved historically.
 */
class GameweekWeightCalibrationService
{
    private const WEIGHT_SUM_TOLERANCE =
        0.000001;


    private const FORMATIONS = [

        '3-4-3' => [
            'GK' => 1,
            'DEF' => 3,
            'MID' => 4,
            'FWD' => 3
        ],

        '3-5-2' => [
            'GK' => 1,
            'DEF' => 3,
            'MID' => 5,
            'FWD' => 2
        ],

        '4-3-3' => [
            'GK' => 1,
            'DEF' => 4,
            'MID' => 3,
            'FWD' => 3
        ],

        '4-4-2' => [
            'GK' => 1,
            'DEF' => 4,
            'MID' => 4,
            'FWD' => 2
        ],

        '4-5-1' => [
            'GK' => 1,
            'DEF' => 4,
            'MID' => 5,
            'FWD' => 1
        ],

        '5-2-3' => [
            'GK' => 1,
            'DEF' => 5,
            'MID' => 2,
            'FWD' => 3
        ],

        '5-3-2' => [
            'GK' => 1,
            'DEF' => 5,
            'MID' => 3,
            'FWD' => 2
        ],

        '5-4-1' => [
            'GK' => 1,
            'DEF' => 5,
            'MID' => 4,
            'FWD' => 1
        ]
    ];


    /*
     * ============================================================
     * PUBLIC API
     * ============================================================
     */

    public function evaluate(
        array $historicalGameweeks,
        array $weightCandidates
    ): array {

        /*
         * Validate every candidate before evaluating history.
         *
         * This ensures malformed calibration instructions never
         * produce a partial result.
         */
        foreach (
            $weightCandidates
            as $candidate
        ) {

            $this->validateWeightCandidate(
                $candidate
            );
        }


        $evaluations =
            [];


        foreach (
            $weightCandidates
            as $candidate
        ) {

            $evaluations[] =
                $this->evaluateCandidate(
                    $historicalGameweeks,
                    $candidate
                );
        }


        return [

            'evaluations' =>
                $evaluations
        ];
    }


    /*
     * ============================================================
     * CANDIDATE EVALUATION
     * ============================================================
     */

    private function evaluateCandidate(
        array $historicalGameweeks,
        array $candidate
    ): array {

        $intelligenceWeight =
            (float) $candidate[
                'intelligence_weight'
            ];


        $strengthWeight =
            (float) $candidate[
                'strength_weight'
            ];


        $fixtureWeight =
            (float) $candidate[
                'fixture_weight'
            ];


        $gameweekEvaluations =
            [];


        foreach (
            $historicalGameweeks
            as $historicalGameweek
        ) {

            if (
                !is_array(
                    $historicalGameweek
                )
            ) {

                continue;
            }


            $gameweekEvaluations[] =
                $this->evaluateGameweek(
                    $historicalGameweek,
                    $intelligenceWeight,
                    $strengthWeight,
                    $fixtureWeight
                );
        }


        return [

            'intelligence_weight' =>
                $candidate[
                    'intelligence_weight'
                ],

            'strength_weight' =>
                $candidate[
                    'strength_weight'
                ],

            'fixture_weight' =>
                $candidate[
                    'fixture_weight'
                ],

            'gameweeks' =>
                $gameweekEvaluations,

            'metrics' =>
                $this->calculateMetrics(
                    $gameweekEvaluations
                )
        ];
    }


    /*
     * ============================================================
     * GAMEWEEK EVALUATION
     * ============================================================
     */

    private function evaluateGameweek(
        array $historicalGameweek,
        float $intelligenceWeight,
        float $strengthWeight,
        float $fixtureWeight
    ): array {

        $players =
            $historicalGameweek[
                'players'
            ]
            ?? [];


        if (
            !is_array(
                $players
            )
        ) {

            $players =
                [];
        }


        $playerScores =
            [];


        foreach (
            $players
            as $player
        ) {

            if (
                !is_array(
                    $player
                )
            ) {

                continue;
            }


            $playerScores[] =
                $this->scorePlayer(
                    $player,
                    $intelligenceWeight,
                    $strengthWeight,
                    $fixtureWeight
                );
        }


        $selectedXI =
            $this->selectCandidateStartingXI(
                $playerScores
            );


        $selectedXIScore =
            $this->averageCandidateScore(
                $selectedXI
            );


        $completeOutcomeEvidence =
            $this->hasCompleteOutcomeEvidence(
                $playerScores
            );


        $selectedActualPoints =
            null;


        $bestActualPoints =
            null;


        $bestActualXI =
            [];


        $selectionPointsLost =
            null;


        if (
            $completeOutcomeEvidence
            &&
            count(
                $selectedXI
            )
            ===
            11
        ) {

            $selectedActualPoints =
                $this->sumActualPoints(
                    $selectedXI
                );


            $bestActualXI =
                $this->selectBestRealisedStartingXI(
                    $playerScores
                );


            if (
                count(
                    $bestActualXI
                )
                ===
                11
            ) {

                $bestActualPoints =
                    $this->sumActualPoints(
                        $bestActualXI
                    );


                $selectionPointsLost =
                    max(
                        0,
                        $bestActualPoints
                        -
                        $selectedActualPoints
                    );
            }
        }


        return [

            'gameweek_id' =>
                $historicalGameweek[
                    'gameweek_id'
                ]
                ?? null,

            'player_scores' =>
                $playerScores,

            'selected_xi' =>
                $selectedXI,

            'selected_xi_score' =>
                $selectedXIScore,

            'selected_actual_points' =>
                $selectedActualPoints,

            'best_actual_xi' =>
                $bestActualXI,

            'best_actual_points' =>
                $bestActualPoints,

            'selection_points_lost' =>
                $selectionPointsLost
        ];
    }


    /*
     * ============================================================
     * PLAYER SCORING
     * ============================================================
     */

    private function scorePlayer(
        array $player,
        float $intelligenceWeight,
        float $strengthWeight,
        float $fixtureWeight
    ): array {

        $components =
            $player[
                'components'
            ]
            ?? [];


        if (
            !is_array(
                $components
            )
        ) {

            $components =
                [];
        }


        $intelligence =
            $this->numericOrNull(
                $components[
                    'intelligence'
                ]
                ?? null
            );


        $strength =
            $this->numericOrNull(
                $components[
                    'strength'
                ]
                ?? null
            );


        $fixture =
            $this->numericOrNull(
                $components[
                    'fixture'
                ]
                ?? null
            );


        $confidenceModifier =
            $this->numericOrNull(
                $components[
                    'confidence_modifier'
                ]
                ?? null
            );


        $availabilityModifier =
            $this->numericOrNull(
                $components[
                    'availability_modifier'
                ]
                ?? null
            );


        $coreGameweekScore =
            null;


        $gameweekScore =
            null;


        if (
            $intelligence !== null
            &&
            $strength !== null
            &&
            $fixture !== null
            &&
            $confidenceModifier !== null
            &&
            $availabilityModifier !== null
        ) {

            $coreGameweekScore =
                (
                    $intelligence
                    *
                    $intelligenceWeight
                )
                +
                (
                    $strength
                    *
                    $strengthWeight
                )
                +
                (
                    $fixture
                    *
                    $fixtureWeight
                );


            $coreGameweekScore =
                $this->clampScore(
                    $coreGameweekScore
                );


            $gameweekScore =
                $coreGameweekScore
                *
                $confidenceModifier
                *
                $availabilityModifier;


            $gameweekScore =
                $this->clampScore(
                    $gameweekScore
                );
        }


        $actualPoints =
            null;


        if (
            array_key_exists(
                'actual_points',
                $player
            )
            &&
            $player[
                'actual_points'
            ]
            !==
            null
            &&
            is_numeric(
                $player[
                    'actual_points'
                ]
            )
        ) {

            $actualPoints =
                $player[
                    'actual_points'
                ]
                +
                0;
        }


        return [

            'player_id' =>
                is_numeric(
                    $player[
                        'player_id'
                    ]
                    ?? null
                )
                    ? (int) $player[
                        'player_id'
                    ]
                    : 0,

            'position' =>
                strtoupper(
                    trim(
                        (string) (
                            $player[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                ),

            'components' => [

                'intelligence' =>
                    $intelligence,

                'strength' =>
                    $strength,

                'fixture' =>
                    $fixture,

                'confidence_modifier' =>
                    $confidenceModifier,

                'availability_modifier' =>
                    $availabilityModifier
            ],

            'core_gameweek_score' =>
                $coreGameweekScore,

            'gameweek_score' =>
                $gameweekScore,

            'actual_points' =>
                $actualPoints
        ];
    }


    /*
     * ============================================================
     * CANDIDATE STARTING XI
     * ============================================================
     */

    private function selectCandidateStartingXI(
        array $playerScores
    ): array {

        return
            $this->selectBestLegalXI(
                $playerScores,
                'gameweek_score'
            );
    }


    /*
     * ============================================================
     * BEST REALISED STARTING XI
     * ============================================================
     */

    private function selectBestRealisedStartingXI(
        array $playerScores
    ): array {

        return
            $this->selectBestLegalXI(
                $playerScores,
                'actual_points'
            );
    }


    /*
     * ============================================================
     * LEGAL XI SELECTION
     * ============================================================
     */

    private function selectBestLegalXI(
        array $players,
        string $scoreField
    ): array {

        $byPosition = [

            'GK' => [],
            'DEF' => [],
            'MID' => [],
            'FWD' => []
        ];


        foreach (
            $players
            as $player
        ) {

            if (
                !is_array(
                    $player
                )
            ) {

                continue;
            }


            $position =
                $player[
                    'position'
                ]
                ?? null;


            if (
                !array_key_exists(
                    $position,
                    $byPosition
                )
            ) {

                continue;
            }


            if (
                !array_key_exists(
                    $scoreField,
                    $player
                )
                ||
                $player[
                    $scoreField
                ]
                ===
                null
                ||
                !is_numeric(
                    $player[
                        $scoreField
                    ]
                )
            ) {

                continue;
            }


            $byPosition[
                $position
            ][] =
                $player;
        }


        foreach (
            $byPosition
            as &$positionPlayers
        ) {

            usort(
                $positionPlayers,
                function (
                    array $a,
                    array $b
                ) use (
                    $scoreField
                ): int {

                    return
                        $this->compareForSelection(
                            $a,
                            $b,
                            $scoreField
                        );
                }
            );
        }


        unset(
            $positionPlayers
        );


        $bestXI =
            [];


        $bestTotal =
            null;


        $bestBenchTotal =
            null;


        foreach (
            self::FORMATIONS
            as $requirements
        ) {

            $selectedXI =
                [];


            $selectedIds =
                [];


            foreach (
                $requirements
                as $position => $required
            ) {

                $selected =
                    array_slice(
                        $byPosition[
                            $position
                        ],
                        0,
                        $required
                    );


                if (
                    count(
                        $selected
                    )
                    !==
                    $required
                ) {

                    continue 2;
                }


                foreach (
                    $selected
                    as $player
                ) {

                    $selectedXI[] =
                        $player;


                    $selectedIds[
                        $player[
                            'player_id'
                        ]
                    ] =
                        true;
                }
            }


            if (
                count(
                    $selectedXI
                )
                !==
                11
            ) {

                continue;
            }


            $total =
                $this->sumField(
                    $selectedXI,
                    $scoreField
                );


            $benchTotal =
                0.0;


            foreach (
                $players
                as $player
            ) {

                if (
                    !is_array(
                        $player
                    )
                ) {

                    continue;
                }


                $playerId =
                    $player[
                        'player_id'
                    ]
                    ?? null;


                if (
                    isset(
                        $selectedIds[
                            $playerId
                        ]
                    )
                ) {

                    continue;
                }


                if (
                    !array_key_exists(
                        $scoreField,
                        $player
                    )
                    ||
                    $player[
                        $scoreField
                    ]
                    ===
                    null
                    ||
                    !is_numeric(
                        $player[
                            $scoreField
                        ]
                    )
                ) {

                    continue;
                }


                $benchTotal +=
                    (float) $player[
                        $scoreField
                    ];
            }


            if (
                $bestTotal === null
                ||
                $total > $bestTotal
                ||
                (
                    abs(
                        $total
                        -
                        $bestTotal
                    )
                    <
                    self::WEIGHT_SUM_TOLERANCE
                    &&
                    (
                        $bestBenchTotal === null
                        ||
                        $benchTotal > $bestBenchTotal
                    )
                )
            ) {

                $bestXI =
                    $selectedXI;


                $bestTotal =
                    $total;


                $bestBenchTotal =
                    $benchTotal;
            }
        }


        return
            $bestXI;
    }


    /*
     * ============================================================
     * SELECTION ORDERING
     * ============================================================
     */

    private function compareForSelection(
        array $a,
        array $b,
        string $scoreField
    ): int {

        $scoreA =
            (float) (
                $a[
                    $scoreField
                ]
                ?? 0
            );


        $scoreB =
            (float) (
                $b[
                    $scoreField
                ]
                ?? 0
            );


        if (
            $scoreA
            !==
            $scoreB
        ) {

            return
                $scoreB
                <=>
                $scoreA;
        }


        /*
         * Candidate Gameweek selection mirrors the production
         * GameweekStartingXI ordering where possible.
         *
         * Fixture is the first tie-breaker, followed by underlying
         * Player Intelligence.
         */
        if (
            $scoreField
            ===
            'gameweek_score'
        ) {

            $fixtureA =
                (float) (
                    $a[
                        'components'
                    ][
                        'fixture'
                    ]
                    ?? 0
                );


            $fixtureB =
                (float) (
                    $b[
                        'components'
                    ][
                        'fixture'
                    ]
                    ?? 0
                );


            if (
                $fixtureA
                !==
                $fixtureB
            ) {

                return
                    $fixtureB
                    <=>
                    $fixtureA;
            }


            $intelligenceA =
                (float) (
                    $a[
                        'components'
                    ][
                        'intelligence'
                    ]
                    ?? 0
                );


            $intelligenceB =
                (float) (
                    $b[
                        'components'
                    ][
                        'intelligence'
                    ]
                    ?? 0
                );


            if (
                $intelligenceA
                !==
                $intelligenceB
            ) {

                return
                    $intelligenceB
                    <=>
                    $intelligenceA;
            }
        }


        /*
         * Final deterministic tie-breaker.
         */
        return
            (
                (int) (
                    $a[
                        'player_id'
                    ]
                    ?? 0
                )
            )
            <=>
            (
                (int) (
                    $b[
                        'player_id'
                    ]
                    ?? 0
                )
            );
    }


    /*
     * ============================================================
     * OUTCOME COMPLETENESS
     * ============================================================
     */

    private function hasCompleteOutcomeEvidence(
        array $playerScores
    ): bool {

        /*
         * Fair Starting XI backtesting requires the complete
         * preserved 15-player squad.
         */
        if (
            count(
                $playerScores
            )
            !==
            15
        ) {

            return false;
        }


        $positionCounts = [

            'GK' => 0,
            'DEF' => 0,
            'MID' => 0,
            'FWD' => 0
        ];


        $seenPlayerIds =
            [];


        foreach (
            $playerScores
            as $player
        ) {

            $playerId =
                $player[
                    'player_id'
                ]
                ?? 0;


            if (
                !is_int(
                    $playerId
                )
                ||
                $playerId <= 0
                ||
                isset(
                    $seenPlayerIds[
                        $playerId
                    ]
                )
            ) {

                return false;
            }


            $seenPlayerIds[
                $playerId
            ] =
                true;


            $position =
                $player[
                    'position'
                ]
                ?? null;


            if (
                !array_key_exists(
                    $position,
                    $positionCounts
                )
            ) {

                return false;
            }


            $positionCounts[
                $position
            ]++;


            if (
                $player[
                    'actual_points'
                ]
                ===
                null
                ||
                !is_numeric(
                    $player[
                        'actual_points'
                    ]
                )
            ) {

                return false;
            }
        }


        return
            $positionCounts[
                'GK'
            ]
            ===
            2
            &&
            $positionCounts[
                'DEF'
            ]
            ===
            5
            &&
            $positionCounts[
                'MID'
            ]
            ===
            5
            &&
            $positionCounts[
                'FWD'
            ]
            ===
            3;
    }


    /*
     * ============================================================
     * AGGREGATE METRICS
     * ============================================================
     */

    private function calculateMetrics(
        array $gameweeks
    ): array {

        $totalGameweeks =
            count(
                $gameweeks
            );


        $comparableGameweeks =
            0;


        $totalSelectionPointsLost =
            0;


        $optimalXISelections =
            0;


        foreach (
            $gameweeks
            as $gameweek
        ) {

            $selectionPointsLost =
                $gameweek[
                    'selection_points_lost'
                ]
                ?? null;


            if (
                $selectionPointsLost
                ===
                null
                ||
                !is_numeric(
                    $selectionPointsLost
                )
            ) {

                continue;
            }


            $comparableGameweeks++;


            $totalSelectionPointsLost +=
                $selectionPointsLost;


            if (
                abs(
                    (float) $selectionPointsLost
                )
                <
                self::WEIGHT_SUM_TOLERANCE
            ) {

                $optimalXISelections++;
            }
        }


        return [

            'total_gameweeks' =>
                $totalGameweeks,

            'comparable_gameweeks' =>
                $comparableGameweeks,

            'unavailable_gameweeks' =>
                $totalGameweeks
                -
                $comparableGameweeks,

            'total_selection_points_lost' =>
                $totalSelectionPointsLost,

            'mean_selection_points_lost' =>
                $comparableGameweeks > 0
                    ? (
                        $totalSelectionPointsLost
                        /
                        $comparableGameweeks
                    )
                    : null,

            'optimal_xi_selections' =>
                $optimalXISelections
        ];
    }


    /*
     * ============================================================
     * SCORE HELPERS
     * ============================================================
     */

    private function averageCandidateScore(
        array $players
    ): ?float {

        if (
            count(
                $players
            )
            !==
            11
        ) {

            return null;
        }


        return
            $this->sumField(
                $players,
                'gameweek_score'
            )
            /
            11;
    }


    private function sumActualPoints(
        array $players
    ): int|float {

        $total =
            0;


        foreach (
            $players
            as $player
        ) {

            $total +=
                $player[
                    'actual_points'
                ]
                +
                0;
        }


        return
            $total;
    }


    private function sumField(
        array $players,
        string $field
    ): float {

        $total =
            0.0;


        foreach (
            $players
            as $player
        ) {

            $total +=
                (float) (
                    $player[
                        $field
                    ]
                    ?? 0
                );
        }


        return
            $total;
    }


    private function numericOrNull(
        mixed $value
    ): int|float|null {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return
            $value
            +
            0;
    }


    private function clampScore(
        float $score
    ): float {

        return
            max(
                0.0,
                min(
                    100.0,
                    $score
                )
            );
    }


    /*
     * ============================================================
     * WEIGHT VALIDATION
     * ============================================================
     */

    private function validateWeightCandidate(
        mixed $candidate
    ): void {

        if (
            !is_array(
                $candidate
            )
        ) {

            throw new InvalidArgumentException(
                'Gameweek weight candidate must be an array.'
            );
        }


        $requiredWeights = [

            'intelligence_weight',
            'strength_weight',
            'fixture_weight'
        ];


        $weightSum =
            0.0;


        foreach (
            $requiredWeights
            as $weightName
        ) {

            if (
                !array_key_exists(
                    $weightName,
                    $candidate
                )
                ||
                !is_numeric(
                    $candidate[
                        $weightName
                    ]
                )
            ) {

                throw new InvalidArgumentException(
                    'Gameweek weight candidate contains invalid weights.'
                );
            }


            $weight =
                (float) $candidate[
                    $weightName
                ];


            if (
                $weight < 0.0
                ||
                $weight > 1.0
            ) {

                throw new InvalidArgumentException(
                    'Gameweek weights must be between 0 and 1.'
                );
            }


            $weightSum +=
                $weight;
        }


        if (
            abs(
                $weightSum
                -
                1.0
            )
            >
            self::WEIGHT_SUM_TOLERANCE
        ) {

            throw new InvalidArgumentException(
                'Gameweek weights must sum to 1.'
            );
        }
    }
}