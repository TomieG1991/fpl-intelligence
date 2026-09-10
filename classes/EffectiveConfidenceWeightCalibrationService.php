<?php

/**
 * EffectiveConfidenceWeightCalibrationService
 *
 * Evaluates alternative Sample Confidence / Participation Rate
 * weight combinations against preserved historical evidence.
 *
 * Historical calibration inputs must come from immutable
 * recommendation-time evidence.
 *
 * This service does not:
 *
 * - modify live Effective Confidence weights
 * - query current player data
 * - query current fixture data
 * - reconstruct missing historical evidence
 * - compare confidence with FPL points
 * - rank or select a winning weight combination
 * - persist calibration results
 */
class EffectiveConfidenceWeightCalibrationService
{
    private object $metricsService;


    public function __construct(
        object $metricsService
    ) {

        $this->metricsService =
            $metricsService;
    }


    /**
     * Evaluate explicitly supplied Sample Confidence /
     * Participation Rate weight candidates against historical
     * player evidence.
     */
    public function evaluate(
        array $backtestRows,
        array $weightCandidates
    ): array {

        $evaluations =
            [];


        /*
         * ========================================================
         * WEIGHT CANDIDATES
         * ========================================================
         *
         * This service owns no hidden candidate grid.
         *
         * Every evaluated combination must be supplied explicitly
         * by the caller.
         */

        foreach (
            $weightCandidates
            as $weightCandidate
        ) {

            $validatedWeights =
                $this
                    ->validateWeightCandidate(
                        $weightCandidate
                    );


            $sampleWeight =
                $validatedWeights[
                    'sample_weight'
                ];


            $participationWeight =
                $validatedWeights[
                    'participation_weight'
                ];


            /*
             * ====================================================
             * CANDIDATE PLAYER CONFIDENCE
             * ====================================================
             */

            $playerScores =
                [];


            foreach (
                $backtestRows
                as $row
            ) {

                /*
                 * Malformed historical rows are ignored entirely.
                 */
                if (!is_array($row)) {

                    continue;
                }


                /*
                 * =================================================
                 * PRESERVED CALIBRATION INPUTS
                 * =================================================
                 */

                $sampleConfidence =
                    $this
                        ->numericOrNull(
                            $row[
                                'sample_confidence'
                            ]
                            ?? null
                        );


                $participationRate =
                    $this
                        ->numericOrNull(
                            $row[
                                'participation_rate'
                            ]
                            ?? null
                        );


                $actualMinutes =
                    $this
                        ->preserveNumericOrNull(
                            $row[
                                'actual_minutes'
                            ]
                            ?? null
                        );


                $actualFixtureCount =
                    $this
                        ->preserveNumericOrNull(
                            $row[
                                'actual_fixture_count'
                            ]
                            ?? null
                        );


                /*
                 * =================================================
                 * CANDIDATE EFFECTIVE CONFIDENCE
                 * =================================================
                 *
                 * No hidden 40/60 production blend exists here.
                 *
                 * The candidate is calculated only from the
                 * explicitly supplied weights.
                 *
                 * Missing recommendation-time component evidence
                 * remains unavailable rather than being treated
                 * as zero.
                 */

                $candidateEffectiveConfidence =
                    null;


                if (
                    $sampleConfidence !== null
                    &&
                    $participationRate !== null
                ) {

                    $candidateEffectiveConfidence =
                        (
                            $sampleConfidence
                            *
                            $sampleWeight
                        )
                        +
                        (
                            $participationRate
                            *
                            $participationWeight
                        );


                    /*
                     * Confidence remains a bounded 0-1 signal.
                     */
                    $candidateEffectiveConfidence =
                        max(
                            0.0,
                            min(
                                1.0,
                                $candidateEffectiveConfidence
                            )
                        );
                }


                /*
                 * =================================================
                 * AUDITABLE PLAYER-LEVEL CALIBRATION EVIDENCE
                 * =================================================
                 */

                $playerScores[] = [

                    'player_id' =>
                        $row[
                            'player_id'
                        ]
                        ?? null,

                    'candidate_effective_confidence' =>
                        $candidateEffectiveConfidence,

                    'actual_minutes' =>
                        $actualMinutes,

                    'actual_fixture_count' =>
                        $actualFixtureCount
                ];
            }


            /*
             * ====================================================
             * OBJECTIVE CANDIDATE METRICS
             * ====================================================
             *
             * Delegate realised participation derivation and
             * statistical evaluation to the dedicated Effective
             * Confidence metrics service.
             */

            $metricRows =
                [];


            foreach (
                $playerScores
                as $playerScore
            ) {

                $metricRows[] = [

                    'effective_confidence' =>
                        $playerScore[
                            'candidate_effective_confidence'
                        ]
                        ?? null,

                    'actual_minutes' =>
                        $playerScore[
                            'actual_minutes'
                        ]
                        ?? null,

                    'actual_fixture_count' =>
                        $playerScore[
                            'actual_fixture_count'
                        ]
                        ?? null
                ];
            }


            $metrics =
                $this->metricsService
                    ->summarise(
                        $metricRows
                    );


            /*
             * ====================================================
             * CANDIDATE EVALUATION CONTRACT
             * ====================================================
             */

            $evaluations[] = [

                'sample_weight' =>
                    $sampleWeight,

                'participation_weight' =>
                    $participationWeight,

                'player_scores' =>
                    $playerScores,

                'metrics' =>
                    $metrics
            ];
        }


        /*
         * ========================================================
         * STABLE INITIAL CONTRACT
         * ========================================================
         */

        return [

            'evaluations' =>
                $evaluations
        ];
    }


    /**
     * Validate one explicitly supplied Sample Confidence /
     * Participation Rate weight candidate.
     */
    private function validateWeightCandidate(
        mixed $weightCandidate
    ): array {

        if (!is_array($weightCandidate)) {

            throw new InvalidArgumentException(
                'Weight candidate must be an array.'
            );
        }


        if (
            !array_key_exists(
                'sample_weight',
                $weightCandidate
            )
        ) {

            throw new InvalidArgumentException(
                'Weight candidate must contain sample_weight.'
            );
        }


        if (
            !array_key_exists(
                'participation_weight',
                $weightCandidate
            )
        ) {

            throw new InvalidArgumentException(
                'Weight candidate must contain participation_weight.'
            );
        }


        if (
            !is_numeric(
                $weightCandidate[
                    'sample_weight'
                ]
            )
        ) {

            throw new InvalidArgumentException(
                'Sample weight must be numeric.'
            );
        }


        if (
            !is_numeric(
                $weightCandidate[
                    'participation_weight'
                ]
            )
        ) {

            throw new InvalidArgumentException(
                'Participation weight must be numeric.'
            );
        }


        $sampleWeight =
            (float) $weightCandidate[
                'sample_weight'
            ];


        $participationWeight =
            (float) $weightCandidate[
                'participation_weight'
            ];


        if (
            $sampleWeight < 0.0
            ||
            $sampleWeight > 1.0
        ) {

            throw new InvalidArgumentException(
                'Sample weight must be between zero and one.'
            );
        }


        if (
            $participationWeight < 0.0
            ||
            $participationWeight > 1.0
        ) {

            throw new InvalidArgumentException(
                'Participation weight must be between zero and one.'
            );
        }


        /*
         * Floating-point tolerance prevents mathematically valid
         * decimal combinations from being rejected because of
         * binary representation noise.
         */
        if (
            abs(
                (
                    $sampleWeight
                    +
                    $participationWeight
                )
                -
                1.0
            )
            >
            0.000001
        ) {

            throw new InvalidArgumentException(
                'Sample and Participation weights must sum to one.'
            );
        }


        return [

            'sample_weight' =>
                $sampleWeight,

            'participation_weight' =>
                $participationWeight
        ];
    }


    /**
     * Convert usable historical evidence to float.
     *
     * Missing or non-numeric evidence remains null.
     * Genuine zero values remain valid.
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


        return (float) $value;
    }
    
    
    /**
     * Preserve usable primary numeric evidence without forcing
     * integer outcome values to floats.
     *
     * Missing or non-numeric evidence remains null.
     * Genuine zero values remain valid.
     */
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


        return $value + 0;
    }
    
}