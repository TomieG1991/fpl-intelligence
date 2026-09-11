<?php


/**
 * TransferDecisionWeightCalibrationService
 *
 * Evaluates alternative top-level TransferDecision weight
 * combinations against preserved historical replacement universes.
 *
 * This service deliberately does not:
 *
 * - discover legal transfer candidates
 * - recalculate outgoing transfer priority
 * - change movement scales
 * - change TransferDecision classification thresholds
 * - query current Player Intelligence
 * - reconstruct missing historical evidence
 * - choose a winning weight combination
 * - persist calibration results
 */
class TransferDecisionWeightCalibrationService
{
    /**
     * Evaluate explicitly supplied TransferDecision weight candidates.
     */
    public function evaluate(
        array $historicalTransfers,
        array $weightCandidates
    ): array {

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


            $transferEvaluations =
                [];


            foreach (
                $historicalTransfers
                as $historicalTransfer
            ) {

                /*
                 * Match the historical calibration services:
                 * malformed non-array rows are ignored.
                 */
                if (!is_array($historicalTransfer)) {

                    continue;
                }


                $transferEvaluations[] =
                    $this->evaluateHistoricalTransfer(
                        $historicalTransfer,
                        $weights
                    );
            }


            $evaluations[] = [

                'intelligence_weight' =>
                    $weights[
                        'intelligence_weight'
                    ],

                'fixture_weight' =>
                    $weights[
                        'fixture_weight'
                    ],

                'value_weight' =>
                    $weights[
                        'value_weight'
                    ],

                'strength_weight' =>
                    $weights[
                        'strength_weight'
                    ],

                'budget_weight' =>
                    $weights[
                        'budget_weight'
                    ],

                'confidence_weight' =>
                    $weights[
                        'confidence_weight'
                    ],

                'transfers' =>
                    $transferEvaluations,

                'metrics' =>
                    $this->buildMetrics(
                        $transferEvaluations
                    )
            ];
        }


        return [

            'evaluations' =>
                $evaluations
        ];
    }


    /**
     * Replay one preserved legal replacement universe.
     */
    private function evaluateHistoricalTransfer(
        array $historicalTransfer,
        array $weights
    ): array {

        $gameweekId =
            $historicalTransfer[
                'gameweek_id'
            ]
            ?? null;


        $bank =
            $this->numericOrNull(
                $historicalTransfer[
                    'bank'
                ]
                ?? null
            );


        if ($bank === null) {

            $bank =
                0.0;
        }


        $outgoing =
            is_array(
                $historicalTransfer[
                    'outgoing'
                ]
                ?? null
            )
                ? $historicalTransfer[
                    'outgoing'
                ]
                : [];


        $replacements =
            is_array(
                $historicalTransfer[
                    'replacements'
                ]
                ?? null
            )
                ? $historicalTransfer[
                    'replacements'
                ]
                : [];


        $outgoingActualPoints =
            $this->numericOrNull(
                $outgoing[
                    'actual_points'
                ]
                ?? null
            );


        $replacementScores =
            [];


        foreach (
            $replacements
            as $replacement
        ) {

            if (!is_array($replacement)) {

                continue;
            }


            $replacementScores[] =
                $this->evaluateReplacement(
                    $outgoing,
                    $replacement,
                    $bank,
                    $weights
                );
        }


        /*
         * Reproduce SquadTransferOptimizer replacement ranking:
         *
         * 1. decision classification
         * 2. decision score
         * 3. incoming Intelligence
         * 4. remaining budget
         */
        usort(
            $replacementScores,
            function (
                array $a,
                array $b
            ): int {

                $typeWeightA =
                    $this->decisionTypeWeight(
                        $a[
                            'decision_type'
                        ]
                        ?? null
                    );


                $typeWeightB =
                    $this->decisionTypeWeight(
                        $b[
                            'decision_type'
                        ]
                        ?? null
                    );


                if (
                    $typeWeightA
                    !==
                    $typeWeightB
                ) {

                    return $typeWeightB
                        <=>
                        $typeWeightA;
                }


                $scoreA =
                    $this->numericOrNull(
                        $a[
                            'decision_score'
                        ]
                        ?? null
                    );


                $scoreB =
                    $this->numericOrNull(
                        $b[
                            'decision_score'
                        ]
                        ?? null
                    );


                $scoreA =
                    $scoreA
                    ?? -999999.0;


                $scoreB =
                    $scoreB
                    ?? -999999.0;


                if ($scoreA !== $scoreB) {

                    return $scoreB
                        <=>
                        $scoreA;
                }


                $intelligenceA =
                    $this->numericOrNull(
                        $a[
                            'intelligence_score'
                        ]
                        ?? null
                    );


                $intelligenceB =
                    $this->numericOrNull(
                        $b[
                            'intelligence_score'
                        ]
                        ?? null
                    );


                $intelligenceA =
                    $intelligenceA
                    ?? -999999.0;


                $intelligenceB =
                    $intelligenceB
                    ?? -999999.0;


                if (
                    $intelligenceA
                    !==
                    $intelligenceB
                ) {

                    return $intelligenceB
                        <=>
                        $intelligenceA;
                }


                return (
                    (float) (
                        $b[
                            'budget_after'
                        ]
                        ?? 0
                    )
                )
                <=>
                (
                    (float) (
                        $a[
                            'budget_after'
                        ]
                        ?? 0
                    )
                );
            }
        );


        $selected =
            $replacementScores[
                0
            ]
            ?? null;


        $selectedPlayerId =
            is_array($selected)
                ? (
                    $selected[
                        'player_id'
                    ]
                    ?? null
                )
                : null;


        $selectedDecisionScore =
            is_array($selected)
                ? (
                    $selected[
                        'decision_score'
                    ]
                    ?? null
                )
                : null;


        $selectedActualPoints =
            is_array($selected)
                ? (
                    $selected[
                        'actual_points'
                    ]
                    ?? null
                )
                : null;


        /*
         * ========================================================
         * BEST REALISED REPLACEMENT
         * ========================================================
         *
         * Hindsight is allowed only inside the same preserved
         * recommendation-time legal candidate universe.
         */

        $bestActualPlayerId =
            null;

        $bestActualPoints =
            null;


        foreach (
            $replacementScores
            as $replacementScore
        ) {

            $actualPoints =
                $replacementScore[
                    'actual_points'
                ]
                ?? null;


            if (
                $actualPoints === null
                ||
                !is_numeric($actualPoints)
            ) {

                continue;
            }


            $actualPoints =
                $actualPoints + 0;


            if (
                $bestActualPoints === null
                ||
                $actualPoints
                >
                $bestActualPoints
            ) {

                $bestActualPoints =
                    $actualPoints;


                $bestActualPlayerId =
                    $replacementScore[
                        'player_id'
                    ]
                    ?? null;
            }
        }


        $selectedRealisedGain =
            null;


        if (
            $outgoingActualPoints !== null
            &&
            $selectedActualPoints !== null
            &&
            is_numeric($selectedActualPoints)
        ) {

            $selectedRealisedGain =
                ($selectedActualPoints + 0)
                -
                $outgoingActualPoints;
        }


        $bestRealisedGain =
            null;


        if (
            $outgoingActualPoints !== null
            &&
            $bestActualPoints !== null
        ) {

            $bestRealisedGain =
                $bestActualPoints
                -
                $outgoingActualPoints;
        }


        $selectionPointsLost =
            null;


        if (
            $selectedActualPoints !== null
            &&
            is_numeric($selectedActualPoints)
            &&
            $bestActualPoints !== null
        ) {

            $selectionPointsLost =
                $bestActualPoints
                -
                ($selectedActualPoints + 0);
        }


        return [

            'gameweek_id' =>
                $gameweekId,

            'replacement_scores' =>
                $replacementScores,

            'selected_player_id' =>
                $selectedPlayerId,

            'selected_decision_score' =>
                $selectedDecisionScore,

            'selected_actual_points' =>
                $selectedActualPoints,

            'outgoing_actual_points' =>
                $outgoingActualPoints,

            'selected_realised_gain' =>
                $selectedRealisedGain,

            'best_actual_player_id' =>
                $bestActualPlayerId,

            'best_actual_points' =>
                $bestActualPoints,

            'best_realised_gain' =>
                $bestRealisedGain,

            'selection_points_lost' =>
                $selectionPointsLost
        ];
    }


    /**
     * Score one preserved replacement under one weight candidate.
     */
    private function evaluateReplacement(
        array $outgoing,
        array $replacement,
        float $bank,
        array $weights
    ): array {

        $currentIntelligence =
            $this->normaliseRating(
                $outgoing[
                    'intelligence_score'
                ]
                ?? null
            );


        $replacementIntelligence =
            $this->normaliseRating(
                $replacement[
                    'intelligence_score'
                ]
                ?? null
            );


        $currentFixture =
            $this->normaliseRating(
                $outgoing[
                    'fixture_rating'
                ]
                ?? null
            );


        $replacementFixture =
            $this->normaliseRating(
                $replacement[
                    'fixture_rating'
                ]
                ?? null
            );


        $currentValue =
            $this->normaliseRating(
                $outgoing[
                    'value_rating'
                ]
                ?? null
            );


        $replacementValue =
            $this->normaliseRating(
                $replacement[
                    'value_rating'
                ]
                ?? null
            );


        $currentStrength =
            $this->normaliseRating(
                $outgoing[
                    'strength_rating'
                ]
                ?? null
            );


        $replacementStrength =
            $this->normaliseRating(
                $replacement[
                    'strength_rating'
                ]
                ?? null
            );


        $currentConfidence =
            $this->normaliseConfidence(
                $outgoing[
                    'sample_confidence'
                ]
                ?? null
            );


        $replacementConfidence =
            $this->normaliseConfidence(
                $replacement[
                    'sample_confidence'
                ]
                ?? null
            );


        $currentPrice =
            $this->numericOrNull(
                $outgoing[
                    'price'
                ]
                ?? null
            );


        $replacementPrice =
            $this->numericOrNull(
                $replacement[
                    'price'
                ]
                ?? null
            );


        $intelligenceMovement =
            $this->calculateMovement(
                $currentIntelligence,
                $replacementIntelligence
            );


        $fixtureMovement =
            $this->calculateMovement(
                $currentFixture,
                $replacementFixture
            );


        $valueMovement =
            $this->calculateMovement(
                $currentValue,
                $replacementValue
            );


        $strengthMovement =
            $this->calculateMovement(
                $currentStrength,
                $replacementStrength
            );


        $confidenceMovement =
            (
                $currentConfidence !== null
                &&
                $replacementConfidence !== null
            )
                ? round(
                    (
                        $replacementConfidence
                        -
                        $currentConfidence
                    )
                    * 100,
                    2
                )
                : null;


        $budgetMovement =
            (
                $currentPrice !== null
                &&
                $replacementPrice !== null
            )
                ? round(
                    $currentPrice
                    -
                    $replacementPrice,
                    2
                )
                : null;


        $decisionScore =
            $this->calculateDecisionScore(
                $intelligenceMovement,
                $fixtureMovement,
                $valueMovement,
                $strengthMovement,
                $budgetMovement,
                $confidenceMovement,
                $weights
            );


        $decisionType =
            $this->classifyTransfer(
                $decisionScore,
                $intelligenceMovement,
                $budgetMovement,
                $replacementConfidence
            );


        $budgetAfter =
            (
                $currentPrice !== null
                &&
                $replacementPrice !== null
            )
                ? round(
                    (
                        $currentPrice
                        +
                        $bank
                    )
                    -
                    $replacementPrice,
                    1
                )
                : 0.0;


        return [

            'player_id' =>
                $replacement[
                    'player_id'
                ]
                ?? null,

            'decision_score' =>
                $decisionScore,

            'decision_type' =>
                $decisionType,

            'intelligence_score' =>
                $replacementIntelligence,

            'budget_after' =>
                $budgetAfter,

            'movements' => [

                'intelligence' =>
                    $intelligenceMovement,

                'fixtures' =>
                    $fixtureMovement,

                'value' =>
                    $valueMovement,

                'strength' =>
                    $strengthMovement,

                'budget' =>
                    $budgetMovement,

                'sample_confidence' =>
                    $confidenceMovement
            ],

            'actual_points' =>
                $this->numericOrNull(
                    $replacement[
                        'actual_points'
                    ]
                    ?? null
                )
        ];
    }


    /**
     * Replay TransferDecision's fixed movement scales while replacing
     * only the six top-level weights.
     */
    private function calculateDecisionScore(
        ?float $intelligenceMovement,
        ?float $fixtureMovement,
        ?float $valueMovement,
        ?float $strengthMovement,
        ?float $budgetMovement,
        ?float $confidenceMovement,
        array $weights
    ): ?float {

        $components =
            [];


        if ($intelligenceMovement !== null) {

            $components[] = [

                'score' =>
                    $this->movementToScore(
                        $intelligenceMovement,
                        10.0
                    ),

                'weight' =>
                    $weights[
                        'intelligence_weight'
                    ]
            ];
        }


        if ($fixtureMovement !== null) {

            $components[] = [

                'score' =>
                    $this->movementToScore(
                        $fixtureMovement,
                        20.0
                    ),

                'weight' =>
                    $weights[
                        'fixture_weight'
                    ]
            ];
        }


        if ($valueMovement !== null) {

            $components[] = [

                'score' =>
                    $this->movementToScore(
                        $valueMovement,
                        30.0
                    ),

                'weight' =>
                    $weights[
                        'value_weight'
                    ]
            ];
        }


        if ($strengthMovement !== null) {

            $components[] = [

                'score' =>
                    $this->movementToScore(
                        $strengthMovement,
                        15.0
                    ),

                'weight' =>
                    $weights[
                        'strength_weight'
                    ]
            ];
        }


        if ($budgetMovement !== null) {

            $components[] = [

                'score' =>
                    $this->movementToScore(
                        $budgetMovement,
                        5.0
                    ),

                'weight' =>
                    $weights[
                        'budget_weight'
                    ]
            ];
        }


        if ($confidenceMovement !== null) {

            $components[] = [

                'score' =>
                    $this->movementToScore(
                        $confidenceMovement,
                        50.0
                    ),

                'weight' =>
                    $weights[
                        'confidence_weight'
                    ]
            ];
        }


        if (empty($components)) {

            return null;
        }


        $weightedScore =
            0.0;


        $weightTotal =
            0.0;


        foreach (
            $components
            as $component
        ) {

            $weightedScore +=
                $component[
                    'score'
                ]
                *
                $component[
                    'weight'
                ];


            $weightTotal +=
                $component[
                    'weight'
                ];
        }


        if ($weightTotal <= 0.0) {

            return null;
        }


        return round(
            $weightedScore
            /
            $weightTotal,
            2
        );
    }


    /**
     * Production TransferDecision movement conversion.
     */
    private function movementToScore(
        float $movement,
        float $scale
    ): float {

        if ($scale <= 0.0) {

            return 50.0;
        }


        $score =
            50
            +
            (
                (
                    $movement
                    /
                    $scale
                )
                *
                50
            );


        return round(
            max(
                0,
                min(
                    100,
                    $score
                )
            ),
            2
        );
    }


    /**
     * Production TransferDecision classification rules.
     */
    private function classifyTransfer(
        ?float $decisionScore,
        ?float $intelligenceMovement,
        ?float $budgetMovement,
        ?float $replacementConfidence
    ): string {

        if ($decisionScore === null) {

            return 'Insufficient Data';
        }


        if (
            $intelligenceMovement !== null
            &&
            $intelligenceMovement >= 2
            &&
            (
                $replacementConfidence === null
                ||
                $replacementConfidence >= 0.25
            )
        ) {

            return 'Upgrade';
        }


        if (
            $replacementConfidence !== null
            &&
            $replacementConfidence < 0.25
        ) {

            return 'Risky Punt';
        }


        if (
            $intelligenceMovement !== null
            &&
            $intelligenceMovement <= -3
        ) {

            return 'Downgrade';
        }


        if (
            $decisionScore >= 52.0
            &&
            $budgetMovement !== null
            &&
            $budgetMovement >= 2
            &&
            (
                $intelligenceMovement === null
                ||
                $intelligenceMovement > -3
            )
        ) {

            return 'Budget Enabler';
        }


        if ($decisionScore >= 60.0) {

            return 'Strategic Sidegrade';
        }


        if ($decisionScore >= 45.0) {

            return 'Sidegrade';
        }


        return 'Downgrade';
    }


    /**
     * Ranking priority used by SquadTransferOptimizer.
     */
    private function decisionTypeWeight(
        ?string $decisionType
    ): int {

        return match (
            strtolower(
                trim(
                    (string) $decisionType
                )
            )
        ) {

            'upgrade' =>
                6,

            'budget enabler' =>
                5,

            'strategic sidegrade' =>
                4,

            'sidegrade' =>
                3,

            'risky punt' =>
                2,

            'downgrade' =>
                1,

            'insufficient data' =>
                0,

            default =>
                0
        };
    }


    /**
     * Aggregate objective historical selection metrics.
     */
    private function buildMetrics(
        array $transfers
    ): array {

        $totalTransfers =
            count(
                $transfers
            );


        $comparableTransfers =
            0;


        $totalSelectionPointsLost =
            0.0;


        $optimalReplacementSelections =
            0;


        foreach (
            $transfers
            as $transfer
        ) {

            $selectionPointsLost =
                $transfer[
                    'selection_points_lost'
                ]
                ?? null;


            if (
                $selectionPointsLost === null
                ||
                !is_numeric(
                    $selectionPointsLost
                )
            ) {

                continue;
            }


            $selectionPointsLost =
                (float) $selectionPointsLost;


            $comparableTransfers++;


            $totalSelectionPointsLost +=
                $selectionPointsLost;


            if (
                abs(
                    $selectionPointsLost
                )
                <=
                0.000001
            ) {

                $optimalReplacementSelections++;
            }
        }


        $unavailableTransfers =
            $totalTransfers
            -
            $comparableTransfers;


        $meanSelectionPointsLost =
            $comparableTransfers > 0
                ? round(
                    $totalSelectionPointsLost
                    /
                    $comparableTransfers,
                    2
                )
                : null;


        return [

            'total_transfers' =>
                $totalTransfers,

            'comparable_transfers' =>
                $comparableTransfers,

            'unavailable_transfers' =>
                $unavailableTransfers,

            'total_selection_points_lost' =>
                $this->normaliseMetricNumber(
                    $totalSelectionPointsLost
                ),

            'mean_selection_points_lost' =>
                $meanSelectionPointsLost,

            'optimal_replacement_selections' =>
                $optimalReplacementSelections
        ];
    }


    /**
     * Validate one explicitly supplied six-weight candidate.
     */
    private function validateWeightCandidate(
        mixed $weightCandidate
    ): array {

        if (!is_array($weightCandidate)) {

            throw new InvalidArgumentException(
                'Weight candidate must be an array.'
            );
        }


        $requiredWeights = [

            'intelligence_weight',
            'fixture_weight',
            'value_weight',
            'strength_weight',
            'budget_weight',
            'confidence_weight'
        ];


        $validated =
            [];


        foreach (
            $requiredWeights
            as $weightName
        ) {

            if (
                !array_key_exists(
                    $weightName,
                    $weightCandidate
                )
            ) {

                throw new InvalidArgumentException(
                    'Weight candidate must contain '
                    . $weightName
                    . '.'
                );
            }


            if (
                !is_numeric(
                    $weightCandidate[
                        $weightName
                    ]
                )
            ) {

                throw new InvalidArgumentException(
                    'Transfer Decision weights must be numeric.'
                );
            }


            $weight =
                (float) $weightCandidate[
                    $weightName
                ];


            if (
                $weight < 0.0
                ||
                $weight > 1.0
            ) {

                throw new InvalidArgumentException(
                    'Transfer Decision weights must be between zero and one.'
                );
            }


            $validated[
                $weightName
            ] =
                $weight;
        }


        if (
            abs(
                array_sum(
                    $validated
                )
                -
                1.0
            )
            >
            0.000001
        ) {

            throw new InvalidArgumentException(
                'Transfer Decision weights must sum to one.'
            );
        }


        return $validated;
    }


    /**
     * Replacement - current movement.
     */
    private function calculateMovement(
        ?float $current,
        ?float $replacement
    ): ?float {

        if (
            $current === null
            ||
            $replacement === null
        ) {

            return null;
        }


        return round(
            $replacement
            -
            $current,
            2
        );
    }


    /**
     * Match TransferDecision rating normalisation.
     */
    private function normaliseRating(
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


        return round(
            max(
                0,
                min(
                    100,
                    (float) $value
                )
            ),
            2
        );
    }


    /**
     * Sample Confidence remains a zero-to-one value.
     */
    private function normaliseConfidence(
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


        return round(
            max(
                0,
                min(
                    1,
                    (float) $value
                )
            ),
            4
        );
    }


    /**
     * Preserve genuine zero and negative realised values.
     */
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


        return $value + 0;
    }


    /**
     * Keep whole-number aggregate metrics as integers so that the
     * public calibration contract is easy to inspect.
     */
    private function normaliseMetricNumber(
        float $value
    ): int|float {

        if (
            abs(
                $value
                -
                round(
                    $value
                )
            )
            <=
            0.000001
        ) {

            return (int) round(
                $value
            );
        }


        return round(
            $value,
            2
        );
    }
}