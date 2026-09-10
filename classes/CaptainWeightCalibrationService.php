<?php

/**
 * CaptainWeightCalibrationService
 *
 * Replays explicitly supplied alternative Captain Intelligence
 * core weightings against preserved historical captain evidence.
 *
 * This service does not:
 *
 * - query live player intelligence
 * - query recommendation history
 * - query realised outcomes
 * - reconstruct missing historical evidence
 * - change production Captain Intelligence weights
 * - choose a preferred calibration candidate
 * - persist calibration results
 *
 * The caller supplies:
 *
 * - preserved recommendation-time Captain component evidence
 * - realised FPL points
 * - explicit alternative Captain core weights
 *
 * Captain core:
 *
 *     Strength
 *     +
 *     Fixture
 *     +
 *     Attacking Threat
 *
 * Preserved recommendation-time confidence and availability
 * modifiers are then applied multiplicatively, matching the
 * existing Captain Intelligence architecture.
 */
class CaptainWeightCalibrationService
{
    /*
     * ============================================================
     * VALIDATION TOLERANCE
     * ============================================================
     */

    private const WEIGHT_SUM_TOLERANCE =
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

        /*
         * Validate every explicitly supplied weight candidate
         * before evaluating historical evidence.
         */
        foreach (
            $weightCandidates
            as $weightCandidate
        ) {

            $this->validateWeightCandidate(
                $weightCandidate
            );
        }


        $evaluations =
            [];


        foreach (
            $weightCandidates
            as $weightCandidate
        ) {

            $evaluations[] =
                $this->evaluateCandidate(
                    $historicalGameweeks,
                    $weightCandidate
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
        array $weightCandidate
    ): array {

        $strengthWeight =
            (float) $weightCandidate[
                'strength_weight'
            ];


        $fixtureWeight =
            (float) $weightCandidate[
                'fixture_weight'
            ];


        $attackingThreatWeight =
            (float) $weightCandidate[
                'attacking_threat_weight'
            ];


        $gameweekEvaluations =
            [];


        foreach (
            $historicalGameweeks
            as $historicalGameweek
        ) {

            /*
             * Malformed gameweek rows are not historical evidence.
             */
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
                    $strengthWeight,
                    $fixtureWeight,
                    $attackingThreatWeight
                );
        }


        return [

            'strength_weight' =>
                $strengthWeight,

            'fixture_weight' =>
                $fixtureWeight,

            'attacking_threat_weight' =>
                $attackingThreatWeight,

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
        float $strengthWeight,
        float $fixtureWeight,
        float $attackingThreatWeight
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
                : 0;


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


        $selectedPlayerId =
            null;


        $selectedCaptainScore =
            null;


        $selectedActualPoints =
            null;


        /*
         * ========================================================
         * SCORE PRESERVED CAPTAIN CANDIDATES
         * ========================================================
         */

        foreach (
            $players
            as $player
        ) {

            /*
             * Non-array rows contain no usable historical evidence.
             */
            if (
                !is_array(
                    $player
                )
            ) {

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


            $attackingThreat =
                $this->numericOrNull(
                    $components[
                        'attacking_threat'
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


            $actualPoints =
                $this->preserveNumericOrNull(
                    $player[
                        'actual_points'
                    ]
                    ?? null
                );


            $coreCaptainScore =
                null;


            $captainScore =
                null;


            /*
             * A candidate Captain Score is only historically
             * reproducible when every component required by the
             * preserved Captain Intelligence formula exists.
             */
            if (
                $strength !== null
                &&
                $fixture !== null
                &&
                $attackingThreat !== null
                &&
                $confidenceModifier !== null
                &&
                $availabilityModifier !== null
            ) {

                $coreCaptainScore =
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
                    )
                    +
                    (
                        $attackingThreat
                        *
                        $attackingThreatWeight
                    );


                $coreCaptainScore =
                    max(
                        0.0,
                        min(
                            100.0,
                            $coreCaptainScore
                        )
                    );


                $captainScore =
                    $coreCaptainScore
                    *
                    $confidenceModifier
                    *
                    $availabilityModifier;


                $captainScore =
                    max(
                        0.0,
                        min(
                            100.0,
                            $captainScore
                        )
                    );
            }


            $playerScores[] = [

                'player_id' =>
                    $playerId,

                'core_captain_score' =>
                    $coreCaptainScore,

                'captain_score' =>
                    $captainScore,

                'actual_points' =>
                    $actualPoints
            ];


            /*
             * Null scores cannot participate in alternative
             * Captain selection.
             *
             * For equal scores, retain the first preserved row.
             * This keeps the result deterministic without inventing
             * an additional tie-breaking model.
             */
            if (
                $captainScore === null
            ) {

                continue;
            }


            if (
                $selectedCaptainScore === null
                ||
                $captainScore
                >
                $selectedCaptainScore
            ) {

                $selectedPlayerId =
                    $playerId;


                $selectedCaptainScore =
                    $captainScore;


                $selectedActualPoints =
                    $actualPoints;
            }
        }


        /*
         * ========================================================
         * REALISED COMPARISON
         * ========================================================
         *
         * A fair "best realised captain" comparison requires
         * realised points for the complete preserved candidate
         * universe.
         *
         * Missing one candidate's outcome means the true best
         * realised option cannot be identified safely.
         */

        $completeRealisedEvidence =
            !empty(
                $playerScores
            );


        foreach (
            $playerScores
            as $playerScore
        ) {

            if (
                $playerScore[
                    'actual_points'
                ]
                ===
                null
            ) {

                $completeRealisedEvidence =
                    false;

                break;
            }
        }


        $bestActualPlayerId =
            null;


        $bestActualPoints =
            null;


        $captainPointsLost =
            null;


        if (
            $completeRealisedEvidence
        ) {

            foreach (
                $playerScores
                as $playerScore
            ) {

                $actualPoints =
                    $playerScore[
                        'actual_points'
                    ];


                if (
                    $bestActualPoints === null
                    ||
                    $actualPoints
                    >
                    $bestActualPoints
                ) {

                    $bestActualPlayerId =
                        $playerScore[
                            'player_id'
                        ];


                    $bestActualPoints =
                        $actualPoints;
                }
            }


            /*
             * A realised comparison also requires a model-selected
             * captain with known realised points.
             */
            if (
                $selectedPlayerId !== null
                &&
                $selectedActualPoints !== null
                &&
                $bestActualPoints !== null
            ) {

                $captainPointsLost =
                    $bestActualPoints
                    -
                    $selectedActualPoints;


                if (
                    $captainPointsLost < 0
                ) {

                    $captainPointsLost =
                        0;
                }
            }
        }


        return [

            'gameweek_id' =>
                $gameweekId,

            'selected_player_id' =>
                $selectedPlayerId,

            'selected_captain_score' =>
                $selectedCaptainScore,

            'selected_actual_points' =>
                $selectedActualPoints,

            'best_actual_player_id' =>
                $bestActualPlayerId,

            'best_actual_points' =>
                $bestActualPoints,

            'captain_points_lost' =>
                $captainPointsLost,

            'player_scores' =>
                $playerScores
        ];
    }


    /*
     * ============================================================
     * AGGREGATE METRICS
     * ============================================================
     */

    private function calculateMetrics(
        array $gameweekEvaluations
    ): array {

        $totalGameweeks =
            count(
                $gameweekEvaluations
            );


        $comparableGameweeks =
            0;


        $unavailableGameweeks =
            0;


        $totalCaptainPointsLost =
            0;


        $optimalCaptainSelections =
            0;


        foreach (
            $gameweekEvaluations
            as $gameweekEvaluation
        ) {

            $captainPointsLost =
                $gameweekEvaluation[
                    'captain_points_lost'
                ]
                ?? null;


            if (
                !is_numeric(
                    $captainPointsLost
                )
            ) {

                $unavailableGameweeks++;

                continue;
            }


            $captainPointsLost =
                $captainPointsLost + 0;


            $comparableGameweeks++;


            $totalCaptainPointsLost +=
                $captainPointsLost;


            if (
                $captainPointsLost === 0
                ||
                $captainPointsLost === 0.0
            ) {

                $optimalCaptainSelections++;
            }
        }


        $meanCaptainPointsLost =
            $comparableGameweeks > 0
                ? (
                    $totalCaptainPointsLost
                    /
                    $comparableGameweeks
                )
                : null;


        return [

            'total_gameweeks' =>
                $totalGameweeks,

            'comparable_gameweeks' =>
                $comparableGameweeks,

            'unavailable_gameweeks' =>
                $unavailableGameweeks,

            'total_captain_points_lost' =>
                $totalCaptainPointsLost,

            'mean_captain_points_lost' =>
                $meanCaptainPointsLost,

            'optimal_captain_selections' =>
                $optimalCaptainSelections
        ];
    }


    /*
     * ============================================================
     * WEIGHT CANDIDATE VALIDATION
     * ============================================================
     */

    private function validateWeightCandidate(
        mixed $weightCandidate
    ): void {

        if (
            !is_array(
                $weightCandidate
            )
        ) {

            throw new InvalidArgumentException(
                'Captain weight candidate must be an array.'
            );
        }


        $requiredFields = [

            'strength_weight',

            'fixture_weight',

            'attacking_threat_weight'
        ];


        foreach (
            $requiredFields
            as $requiredField
        ) {

            if (
                !array_key_exists(
                    $requiredField,
                    $weightCandidate
                )
                ||
                !is_numeric(
                    $weightCandidate[
                        $requiredField
                    ]
                )
            ) {

                throw new InvalidArgumentException(
                    'Captain weight candidate requires numeric Strength, Fixture and Attacking Threat weights.'
                );
            }


            $weight =
                (float) $weightCandidate[
                    $requiredField
                ];


            if (
                $weight < 0.0
                ||
                $weight > 1.0
            ) {

                throw new InvalidArgumentException(
                    'Captain weights must be between zero and one.'
                );
            }
        }


        $weightSum =
            (float) $weightCandidate[
                'strength_weight'
            ]
            +
            (float) $weightCandidate[
                'fixture_weight'
            ]
            +
            (float) $weightCandidate[
                'attacking_threat_weight'
            ];


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
                'Captain weights must sum to one.'
            );
        }
    }


    /*
     * ============================================================
     * NUMERIC HELPERS
     * ============================================================
     */

    private function numericOrNull(
        mixed $value
    ): ?float {

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
            (float) $value;
    }


    private function preserveNumericOrNull(
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
            $value + 0;
    }
}