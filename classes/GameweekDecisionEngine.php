<?php

class GameweekDecisionEngine
{
    /*
     * ============================================================
     * GAMEWEEK DECISION ENGINE
     * ============================================================
     *
     * This class does not recalculate Player Intelligence,
     * Gameweek Score, Captain Intelligence or transfer scores.
     *
     * Its responsibility is to combine already-calculated
     * intelligence into one manager-facing gameweek decision.
     *
     * It answers:
     *
     *     "What should I actually do this gameweek?"
     *
     * The engine currently combines:
     *
     * - Gameweek Starting XI intelligence
     * - Captain Intelligence
     * - squad reliability / availability risk
     * - transfer recommendation intelligence
     *
     * Later application-service integration will provide these
     * inputs from the existing production intelligence pipeline.
     */


    /*
     * ============================================================
     * AVAILABILITY RISK THRESHOLDS
     * ============================================================
     */

    private const UNAVAILABLE_THRESHOLD =
        25.0;


    private const MAJOR_AVAILABILITY_THRESHOLD =
        75.0;


    private const MINOR_AVAILABILITY_THRESHOLD =
        100.0;


    /*
     * ============================================================
     * CONFIDENCE RISK THRESHOLDS
     * ============================================================
     */

    private const VERY_LOW_CONFIDENCE_THRESHOLD =
        25.0;


    private const LOW_CONFIDENCE_THRESHOLD =
        50.0;


    /*
     * ============================================================
     * TRANSFER PRIORITY THRESHOLDS
     * ============================================================
     *
     * Transfer recommendation services have evolved independently,
     * so this engine accepts both textual priority information and
     * numeric recommendation / combination scores.
     */

    private const HIGH_TRANSFER_SCORE =
        70.0;


    private const MEDIUM_TRANSFER_SCORE =
        55.0;


    /*
     * ============================================================
     * PUBLIC DECISION METHOD
     * ============================================================
     */

    public function evaluate(
        array $gameweekResult,
        array $captainResult,
        array $transferResult = []
    ): array {

        /*
         * --------------------------------------------------------
         * VALIDATE GAMEWEEK INTELLIGENCE
         * --------------------------------------------------------
         */

        if (
            (
                $gameweekResult[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            return $this->invalidResult(
                'Valid Gameweek Starting XI intelligence is required.'
            );
        }


        $startingXI =
            $gameweekResult[
                'starting_xi'
            ]
            ?? [];


        $bench =
            $gameweekResult[
                'bench'
            ]
            ?? [];


        if (
            !is_array(
                $startingXI
            )
            ||
            count(
                $startingXI
            )
            !== 11
            ||
            !is_array(
                $bench
            )
            ||
            count(
                $bench
            )
            !== 4
        ) {

            return $this->invalidResult(
                'Gameweek Intelligence must contain a complete Starting XI and bench.'
            );
        }


        /*
         * --------------------------------------------------------
         * VALIDATE CAPTAIN INTELLIGENCE
         * --------------------------------------------------------
         */

        if (
            (
                $captainResult[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            return $this->invalidResult(
                'Valid Captain Intelligence is required.'
            );
        }


        $captain =
            $captainResult[
                'captain'
            ]
            ?? null;


        $viceCaptain =
            $captainResult[
                'vice_captain'
            ]
            ?? null;


        if (
            !is_array(
                $captain
            )
            ||
            !is_array(
                $viceCaptain
            )
        ) {

            return $this->invalidResult(
                'Captain Intelligence must contain captain and vice-captain recommendations.'
            );
        }


        /*
         * ========================================================
         * SQUAD RISK ANALYSIS
         * ========================================================
         */

        $squadRisks =
            $this->analyseSquadRisks(
                $startingXI,
                $bench
            );


        /*
         * ========================================================
         * TRANSFER ADVICE
         * ========================================================
         */

        $transferAdvice =
            $this->analyseTransferAdvice(
                $transferResult,
                $squadRisks
            );


        /*
         * ========================================================
         * OVERALL ACTION
         * ========================================================
         */

        $overallAction =
            $this->determineOverallAction(
                $squadRisks,
                $transferAdvice
            );


        /*
         * ========================================================
         * KEY INSIGHTS
         * ========================================================
         */

        $keyInsights =
            $this->buildKeyInsights(
                $gameweekResult,
                $captain,
                $viceCaptain,
                $squadRisks,
                $transferAdvice,
                $overallAction
            );


        /*
         * ========================================================
         * RESULT
         * ========================================================
         */

        return [

            'status' =>
                'success',

            'message' =>
                'Gameweek decision generated successfully.',

            'overall_action' =>
                $overallAction,

            'formation' =>
                $gameweekResult[
                    'formation'
                ]
                ?? null,

            'starting_xi_score' =>
                $gameweekResult[
                    'starting_xi_score'
                ]
                ?? null,

            'bench_score' =>
                $gameweekResult[
                    'bench_score'
                ]
                ?? null,

            'starting_xi' =>
                $startingXI,

            'bench' =>
                $bench,

            'captain' =>
                $captain,

            'vice_captain' =>
                $viceCaptain,

            'transfer_advice' =>
                $transferAdvice,

            'squad_risks' =>
                $squadRisks,

            'key_insights' =>
                $keyInsights
        ];
    }


    /*
     * ============================================================
     * SQUAD RISK ANALYSIS
     * ============================================================
     */

    private function analyseSquadRisks(
        array $startingXI,
        array $bench
    ): array {

        $risks =
            [];


        foreach (
            $startingXI
            as $player
        ) {

            if (
                !is_array(
                    $player
                )
            ) {

                continue;
            }


            $this->appendPlayerRisks(
                $risks,
                $player,
                'starting_xi'
            );
        }


        foreach (
            $bench
            as $player
        ) {

            if (
                !is_array(
                    $player
                )
            ) {

                continue;
            }


            $this->appendPlayerRisks(
                $risks,
                $player,
                'bench'
            );
        }


        /*
         * --------------------------------------------------------
         * RISK COUNTS
         * --------------------------------------------------------
         */

        $criticalCount =
            0;


        $highCount =
            0;


        $mediumCount =
            0;


        $lowCount =
            0;


        $startingRiskCount =
            0;


        foreach (
            $risks
            as $risk
        ) {

            $severity =
                $risk[
                    'severity'
                ]
                ?? null;


            if (
                $severity ===
                'critical'
            ) {

                $criticalCount++;

            } elseif (
                $severity ===
                'high'
            ) {

                $highCount++;

            } elseif (
                $severity ===
                'medium'
            ) {

                $mediumCount++;

            } else {

                $lowCount++;
            }


            if (
                (
                    $risk[
                        'location'
                    ]
                    ?? null
                )
                ===
                'starting_xi'
            ) {

                $startingRiskCount++;
            }
        }


        return [

            'count' =>
                count(
                    $risks
                ),

            'critical_count' =>
                $criticalCount,

            'high_count' =>
                $highCount,

            'medium_count' =>
                $mediumCount,

            'low_count' =>
                $lowCount,

            'starting_xi_risk_count' =>
                $startingRiskCount,

            'has_critical_risk' =>
                $criticalCount > 0,

            'has_high_risk' =>
                $highCount > 0,

            'risks' =>
                $risks
        ];
    }


    /*
     * ============================================================
     * PLAYER RISK DETECTION
     * ============================================================
     */

    private function appendPlayerRisks(
        array &$risks,
        array $player,
        string $location
    ): void {

        $playerId =
            (int) (
                $player[
                    'player_id'
                ]
                ?? 0
            );


        $name =
            (string) (
                $player[
                    'name'
                ]
                ?? 'Unknown player'
            );


        $availability =
            $this->normalisePercentage(
                $player[
                    'availability'
                ]
                ??
                $player[
                    'availability_rating'
                ]
                ??
                $player[
                    'components'
                ][
                    'availability'
                ]
                ??
                null
            );


        $confidence =
            $this->normalisePercentage(
                $player[
                    'sample_confidence'
                ]
                ??
                $player[
                    'confidence'
                ]
                ??
                $player[
                    'components'
                ][
                    'confidence'
                ]
                ??
                null
            );


        /*
         * --------------------------------------------------------
         * AVAILABILITY
         * --------------------------------------------------------
         */

        if (
            $availability !== null
            &&
            $availability
            <
            self::UNAVAILABLE_THRESHOLD
        ) {

            $risks[] = [

                'player_id' =>
                    $playerId,

                'name' =>
                    $name,

                'location' =>
                    $location,

                'type' =>
                    'availability',

                'severity' =>
                    $location === 'starting_xi'
                        ? 'critical'
                        : 'high',

                'value' =>
                    $availability,

                'message' =>
                    $name
                    . ' has a major availability concern.'
            ];

        } elseif (
            $availability !== null
            &&
            $availability
            <
            self::MAJOR_AVAILABILITY_THRESHOLD
        ) {

            $risks[] = [

                'player_id' =>
                    $playerId,

                'name' =>
                    $name,

                'location' =>
                    $location,

                'type' =>
                    'availability',

                'severity' =>
                    $location === 'starting_xi'
                        ? 'high'
                        : 'medium',

                'value' =>
                    $availability,

                'message' =>
                    $name
                    . ' has a significant availability concern.'
            ];

        } elseif (
            $availability !== null
            &&
            $availability
            <
            self::MINOR_AVAILABILITY_THRESHOLD
        ) {

            $risks[] = [

                'player_id' =>
                    $playerId,

                'name' =>
                    $name,

                'location' =>
                    $location,

                'type' =>
                    'availability',

                'severity' =>
                    'medium',

                'value' =>
                    $availability,

                'message' =>
                    $name
                    . ' is not currently rated fully available.'
            ];
        }


        /*
         * --------------------------------------------------------
         * SAMPLE CONFIDENCE
         * --------------------------------------------------------
         */

        if (
            $confidence !== null
            &&
            $confidence
            <
            self::VERY_LOW_CONFIDENCE_THRESHOLD
        ) {

            $risks[] = [

                'player_id' =>
                    $playerId,

                'name' =>
                    $name,

                'location' =>
                    $location,

                'type' =>
                    'confidence',

                'severity' =>
                    $location === 'starting_xi'
                        ? 'high'
                        : 'medium',

                'value' =>
                    $confidence,

                'message' =>
                    $name
                    . ' has very low sample confidence.'
            ];

        } elseif (
            $confidence !== null
            &&
            $confidence
            <
            self::LOW_CONFIDENCE_THRESHOLD
        ) {

            $risks[] = [

                'player_id' =>
                    $playerId,

                'name' =>
                    $name,

                'location' =>
                    $location,

                'type' =>
                    'confidence',

                'severity' =>
                    'medium',

                'value' =>
                    $confidence,

                'message' =>
                    $name
                    . ' has limited sample confidence.'
            ];
        }
    }


    /*
     * ============================================================
     * TRANSFER ADVICE
     * ============================================================
     */

    private function analyseTransferAdvice(
        array $transferResult,
        array $squadRisks
    ): array {

        if (
            empty(
                $transferResult
            )
        ) {

            return [

                'action' =>
                    'No Transfer Data',

                'priority' =>
                    'Unknown',

                'score' =>
                    null,

                'recommendations' =>
                    [],

                'message' =>
                    'Transfer intelligence was not supplied.'
            ];
        }


        $recommendations =
            $this->extractTransferRecommendations(
                $transferResult
            );


        $score =
            $this->extractTransferScore(
                $transferResult,
                $recommendations
            );


        $explicitPriority =
            $this->extractTransferPriority(
                $transferResult,
                $recommendations
            );


        if (
            $explicitPriority !== null
        ) {

            $priority =
                $explicitPriority;

        } elseif (
            $score !== null
            &&
            $score
            >=
            self::HIGH_TRANSFER_SCORE
        ) {

            $priority =
                'High';

        } elseif (
            $score !== null
            &&
            $score
            >=
            self::MEDIUM_TRANSFER_SCORE
        ) {

            $priority =
                'Medium';

        } elseif (
            $score !== null
        ) {

            $priority =
                'Low';

        } elseif (
            (
                $squadRisks[
                    'has_critical_risk'
                ]
                ?? false
            )
        ) {

            $priority =
                'High';

        } else {

            $priority =
                'Unknown';
        }


        if (
            $priority === 'High'
        ) {

            $action =
                'Make Transfer';

        } elseif (
            $priority === 'Medium'
        ) {

            $action =
                'Consider Transfer';

        } elseif (
            $priority === 'Low'
        ) {

            $action =
                'Hold';

        } else {

            $action =
                'Review';
        }


        return [

            'action' =>
                $action,

            'priority' =>
                $priority,

            'score' =>
                $score,

            'recommendations' =>
                $recommendations,

            'message' =>
                $this->buildTransferMessage(
                    $action,
                    $priority
                )
        ];
    }


    /*
     * ============================================================
     * OVERALL ACTION
     * ============================================================
     */

    private function determineOverallAction(
        array $squadRisks,
        array $transferAdvice
    ): string {

        if (
            (
                $squadRisks[
                    'has_critical_risk'
                ]
                ?? false
            )
        ) {

            return 'Urgent Action';
        }


        if (
            (
                $transferAdvice[
                    'priority'
                ]
                ?? null
            )
            ===
            'High'
        ) {

            return 'Make Transfer';
        }


        if (
            (
                $squadRisks[
                    'has_high_risk'
                ]
                ?? false
            )
            ||
            (
                $transferAdvice[
                    'priority'
                ]
                ?? null
            )
            ===
            'Medium'
        ) {

            return 'Consider Transfer';
        }


        return 'Hold';
    }


    /*
     * ============================================================
     * KEY INSIGHTS
     * ============================================================
     */

    private function buildKeyInsights(
        array $gameweekResult,
        array $captain,
        array $viceCaptain,
        array $squadRisks,
        array $transferAdvice,
        string $overallAction
    ): array {

        $insights =
            [];


        $formation =
            (string) (
                $gameweekResult[
                    'formation'
                ]
                ?? ''
            );


        if (
            $formation !== ''
        ) {

            $insights[] =
                'The strongest immediate-gameweek formation is '
                . $formation
                . '.';
        }


        $captainName =
            trim(
                (string) (
                    $captain[
                        'name'
                    ]
                    ?? ''
                )
            );


        $captainScore =
            $captain[
                'captain_score'
            ]
            ?? null;


        if (
            $captainName !== ''
        ) {

            $captainInsight =
                $captainName
                . ' is the recommended captain';


            if (
                is_numeric(
                    $captainScore
                )
            ) {

                $captainInsight .=
                    ' with a Captain Score of '
                    . number_format(
                        (float) $captainScore,
                        2
                    );
            }


            $captainInsight .=
                '.';


            $insights[] =
                $captainInsight;
        }


        $viceCaptainName =
            trim(
                (string) (
                    $viceCaptain[
                        'name'
                    ]
                    ?? ''
                )
            );


        if (
            $viceCaptainName !== ''
        ) {

            $insights[] =
                $viceCaptainName
                . ' is the recommended vice-captain.';
        }


        $riskCount =
            (int) (
                $squadRisks[
                    'count'
                ]
                ?? 0
            );


        if (
            $riskCount === 0
        ) {

            $insights[] =
                'No material availability or confidence risks were identified in the recommended squad structure.';

        } else {

            $insights[] =
                $riskCount
                . ' squad reliability risk'
                . (
                    $riskCount === 1
                        ? ''
                        : 's'
                )
                . ' require attention.';
        }


        $transferPriority =
            (string) (
                $transferAdvice[
                    'priority'
                ]
                ?? 'Unknown'
            );


        if (
            $transferPriority !==
            'Unknown'
        ) {

            $insights[] =
                'Transfer priority is currently '
                . strtolower(
                    $transferPriority
                )
                . '.';
        }


        $insights[] =
            'Overall gameweek recommendation: '
            . $overallAction
            . '.';


        return $insights;
    }


    /*
     * ============================================================
     * TRANSFER RESULT HELPERS
     * ============================================================
     */

    private function extractTransferRecommendations(
        array $transferResult
    ): array {

        /*
         * ========================================================
         * REAL SQUAD TRANSFER INTELLIGENCE STRUCTURE
         * ========================================================
         *
         * PlayerIntelligenceService currently returns:
         *
         * [
         *     'analysis' => [...],
         *     'recommendations' => [
         *         'status' => 'success',
         *         'recommendations' => [...]
         *     ]
         * ]
         *
         * Unwrap the optimizer result so the decision layer receives
         * the actual outgoing-player recommendation groups.
         */

        $optimizerResult =
            $transferResult[
                'recommendations'
            ]
            ?? null;


        if (
            is_array(
                $optimizerResult
            )
            &&
            isset(
                $optimizerResult[
                    'recommendations'
                ]
            )
            &&
            is_array(
                $optimizerResult[
                    'recommendations'
                ]
            )
        ) {

            return $optimizerResult[
                'recommendations'
            ];
        }


        /*
         * ========================================================
         * GENERIC / LEGACY STRUCTURES
         * ========================================================
         */

        $possibleKeys = [
            'recommendations',
            'transfers',
            'combinations'
        ];


        foreach (
            $possibleKeys
            as $key
        ) {

            if (
                isset(
                    $transferResult[
                        $key
                    ]
                )
                &&
                is_array(
                    $transferResult[
                        $key
                    ]
                )
            ) {

                return $transferResult[
                    $key
                ];
            }
        }


        return [];
    }


    private function extractTransferScore(
        array $transferResult,
        array $recommendations
    ): ?float {

        /*
         * ========================================================
         * DIRECT SCORE STRUCTURES
         * ========================================================
         */

        $possibleKeys = [
            'score',
            'transfer_score',
            'combination_score',
            'squad_score'
        ];


        foreach (
            $possibleKeys
            as $key
        ) {

            if (
                isset(
                    $transferResult[
                        $key
                    ]
                )
                &&
                is_numeric(
                    $transferResult[
                        $key
                    ]
                )
            ) {

                return $this->boundScore(
                    (float) $transferResult[
                        $key
                    ]
                );
            }
        }


        /*
         * ========================================================
         * REAL SQUAD TRANSFER RECOMMENDATION
         * ========================================================
         *
         * The first outgoing player is the highest transfer-priority
         * squad member because SquadTransferOptimizer preserves the
         * SquadTransferIntelligence priority ordering.
         */

        $topRecommendation =
            $recommendations[
                0
            ]
            ?? null;


        if (
            is_array(
                $topRecommendation
            )
        ) {

            if (
                is_numeric(
                    $topRecommendation[
                        'transfer_priority'
                    ]
                    ?? null
                )
            ) {

                return $this->boundScore(
                    (float) $topRecommendation[
                        'transfer_priority'
                    ]
                );
            }


            /*
             * Generic fallback.
             */

            foreach (
                $possibleKeys
                as $key
            ) {

                if (
                    isset(
                        $topRecommendation[
                            $key
                        ]
                    )
                    &&
                    is_numeric(
                        $topRecommendation[
                            $key
                        ]
                    )
                ) {

                    return $this->boundScore(
                        (float) $topRecommendation[
                            $key
                        ]
                    );
                }
            }


            /*
             * Final fallback: use the strongest replacement's
             * TransferDecision score.
             */

            $bestReplacement =
                $topRecommendation[
                    'replacements'
                ][
                    0
                ]
                ?? null;


            if (
                is_array(
                    $bestReplacement
                )
                &&
                is_numeric(
                    $bestReplacement[
                        'decision_score'
                    ]
                    ?? null
                )
            ) {

                return $this->boundScore(
                    (float) $bestReplacement[
                        'decision_score'
                    ]
                );
            }
        }


        return null;
    }


    private function extractTransferPriority(
        array $transferResult,
        array $recommendations
    ): ?string {

        $possibleValues =
            [];


        /*
         * ========================================================
         * DIRECT STRUCTURES
         * ========================================================
         */

        foreach (
            [
                'priority',
                'transfer_priority',
                'priority_label',
                'classification',
                'decision_type',
                'action'
            ]
            as $key
        ) {

            if (
                isset(
                    $transferResult[
                        $key
                    ]
                )
            ) {

                $possibleValues[] =
                    $transferResult[
                        $key
                    ];
            }
        }


        /*
         * ========================================================
         * REAL SQUAD TRANSFER RECOMMENDATION
         * ========================================================
         */

        $topRecommendation =
            $recommendations[
                0
            ]
            ?? null;


        if (
            is_array(
                $topRecommendation
            )
        ) {

            foreach (
                [
                    'priority_label',
                    'classification',
                    'decision_type',
                    'action'
                ]
                as $key
            ) {

                if (
                    isset(
                        $topRecommendation[
                            $key
                        ]
                    )
                ) {

                    $possibleValues[] =
                        $topRecommendation[
                            $key
                        ];
                }
            }


            /*
             * Fall back to numeric transfer priority when no textual
             * label is available.
             */

            if (
                is_numeric(
                    $topRecommendation[
                        'transfer_priority'
                    ]
                    ?? null
                )
            ) {

                $numericPriority =
                    (float) $topRecommendation[
                        'transfer_priority'
                    ];


                if (
                    $numericPriority
                    >=
                    self::HIGH_TRANSFER_SCORE
                ) {

                    return 'High';
                }


                if (
                    $numericPriority
                    >=
                    self::MEDIUM_TRANSFER_SCORE
                ) {

                    return 'Medium';
                }


                return 'Low';
            }
        }


        /*
         * ========================================================
         * TEXT CLASSIFICATION
         * ========================================================
         */

        foreach (
            $possibleValues
            as $value
        ) {

            $normalised =
                strtolower(
                    trim(
                        (string) $value
                    )
                );


            if (
                $normalised === ''
            ) {

                continue;
            }


            if (
                str_contains(
                    $normalised,
                    'urgent'
                )
                ||
                str_contains(
                    $normalised,
                    'high'
                )
                ||
                str_contains(
                    $normalised,
                    'strong'
                )
                ||
                str_contains(
                    $normalised,
                    'make'
                )
            ) {

                return 'High';
            }


            if (
                str_contains(
                    $normalised,
                    'moderate'
                )
                ||
                str_contains(
                    $normalised,
                    'medium'
                )
                ||
                str_contains(
                    $normalised,
                    'consider'
                )
            ) {

                return 'Medium';
            }


            if (
                str_contains(
                    $normalised,
                    'low'
                )
                ||
                str_contains(
                    $normalised,
                    'hold'
                )
                ||
                str_contains(
                    $normalised,
                    'avoid'
                )
            ) {

                return 'Low';
            }
        }


        return null;
    }


    private function buildTransferMessage(
        string $action,
        string $priority
    ): string {

        if (
            $action ===
            'Make Transfer'
        ) {

            return 'Transfer Intelligence identifies a high-priority move.';
        }


        if (
            $action ===
            'Consider Transfer'
        ) {

            return 'Transfer Intelligence identifies a move worth considering.';
        }


        if (
            $action ===
            'Hold'
        ) {

            return 'Transfer Intelligence does not currently justify forcing a move.';
        }


        return 'Transfer Intelligence should be reviewed before making a move.';
    }


    /*
     * ============================================================
     * NORMALISATION HELPERS
     * ============================================================
     */

    private function normalisePercentage(
        mixed $value
    ): ?float {

        if (
            $value === null
            ||
            $value === ''
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        $value =
            (float) $value;


        /*
         * Existing project intelligence may expose confidence
         * either as 0-1 or 0-100.
         */

        if (
            $value >= 0.0
            &&
            $value <= 1.0
        ) {

            $value *=
                100.0;
        }


        return round(
            max(
                0.0,
                min(
                    100.0,
                    $value
                )
            ),
            2
        );
    }


    private function boundScore(
        float $score
    ): float {

        return round(
            max(
                0.0,
                min(
                    100.0,
                    $score
                )
            ),
            2
        );
    }


    /*
     * ============================================================
     * INVALID RESULT
     * ============================================================
     */

    private function invalidResult(
        string $message
    ): array {

        return [

            'status' =>
                'invalid',

            'message' =>
                $message,

            'overall_action' =>
                null,

            'formation' =>
                null,

            'starting_xi_score' =>
                null,

            'bench_score' =>
                null,

            'starting_xi' =>
                [],

            'bench' =>
                [],

            'captain' =>
                null,

            'vice_captain' =>
                null,

            'transfer_advice' =>
                [],

            'squad_risks' =>
                [],

            'key_insights' =>
                []
        ];
    }
}