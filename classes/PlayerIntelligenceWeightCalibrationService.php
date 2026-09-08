<?php

/**
 * PlayerIntelligenceWeightCalibrationService
 *
 * Evaluates alternative Player Intelligence Strength / Fixture
 * weight combinations against preserved historical recommendation
 * evidence and realised FPL points.
 *
 * Historical calibration inputs must come from immutable
 * recommendation-time evidence.
 *
 * This service does not:
 *
 * - modify live Player Intelligence weights
 * - query current Player Intelligence
 * - query current fixture data
 * - reconstruct missing historical evidence
 * - use position-aware fixture ratings
 * - use Player Value
 * - change Availability rules
 * - rank or select a winning weight combination
 * - persist calibration results
 */
class PlayerIntelligenceWeightCalibrationService
{
    private object $metricsService;


    public function __construct(
        object $metricsService
    ) {

        $this->metricsService =
            $metricsService;
    }


    /**
     * Evaluate explicitly supplied Strength / Fixture weight
     * candidates against historical player evidence.
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
         * The calibration service does not own a hidden candidate
         * grid.
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


            $strengthWeight =
                $validatedWeights[
                    'strength_weight'
                ];


            $fixtureWeight =
                $validatedWeights[
                    'fixture_weight'
                ];


            /*
             * ====================================================
             * CANDIDATE PLAYER SCORES
             * ====================================================
             */

            $playerScores =
                [];


            foreach (
                $backtestRows
                as $row
            ) {

                /*
                 * Match the existing historical metrics semantics:
                 * malformed non-array rows are ignored entirely.
                 */

                if (!is_array($row)) {

                    continue;
                }


                /*
                 * =================================================
                 * PRESERVED CALIBRATION INPUTS
                 * =================================================
                 */

                $strengthRating =
                    $this
                        ->numericOrNull(
                            $row[
                                'strength_rating'
                            ]
                            ?? null
                        );


                $fixtureRating =
                    $this
                        ->numericOrNull(
                            $row[
                                'fixture_rating'
                            ]
                            ?? null
                        );


                $availabilityMultiplier =
                    $this
                        ->numericOrNull(
                            $row[
                                'availability_multiplier'
                            ]
                            ?? null
                        );


                $actualPoints =
                    $this
                        ->numericOrNull(
                            $row[
                                'actual_points'
                            ]
                            ?? null
                        );


                /*
                 * =================================================
                 * CANDIDATE INTELLIGENCE SCORE
                 * =================================================
                 *
                 * The historical production ordering is preserved:
                 *
                 * 1. Strength / Fixture weighted core
                 * 2. Availability multiplier
                 *
                 * Missing historical component evidence remains
                 * unavailable rather than being treated as zero.
                 */

                $candidateIntelligenceScore =
                    null;


                if (
                    $strengthRating !== null
                    &&
                    $fixtureRating !== null
                    &&
                    $availabilityMultiplier !== null
                ) {

                    $coreScore =
                        (
                            $strengthRating
                            *
                            $strengthWeight
                        )
                        +
                        (
                            $fixtureRating
                            *
                            $fixtureWeight
                        );


                    $candidateIntelligenceScore =
                        $coreScore
                        *
                        $availabilityMultiplier;
                }


                /*
                 * =================================================
                 * AUDITABLE PLAYER-LEVEL CALIBRATION EVIDENCE
                 * =================================================
                 *
                 * Preserve player identity where available so that
                 * candidate results remain inspectable.
                 */

                $playerScores[] = [

                    'player_id' =>
                        $row[
                            'player_id'
                        ]
                        ?? null,

                    'candidate_intelligence_score' =>
                        $candidateIntelligenceScore,

                    'actual_points' =>
                        $actualPoints
                ];
            }


            /*
             * ====================================================
             * OBJECTIVE CANDIDATE METRICS
             * ====================================================
             *
             * Reuse the established Intelligence Score historical
             * metrics service rather than calculating Pearson
             * correlation independently here.
             */

            $metricRows =
                [];


            foreach (
                $playerScores
                as $playerScore
            ) {

                $metricRows[] = [

                    'intelligence_score' =>
                        $playerScore[
                            'candidate_intelligence_score'
                        ]
                        ?? null,

                    'actual_points' =>
                        $playerScore[
                            'actual_points'
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

                'strength_weight' =>
                    $strengthWeight,

                'fixture_weight' =>
                    $fixtureWeight,

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
     * Validate one explicitly supplied Strength / Fixture
     * candidate.
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
                'strength_weight',
                $weightCandidate
            )
        ) {

            throw new InvalidArgumentException(
                'Weight candidate must contain strength_weight.'
            );
        }


        if (
            !array_key_exists(
                'fixture_weight',
                $weightCandidate
            )
        ) {

            throw new InvalidArgumentException(
                'Weight candidate must contain fixture_weight.'
            );
        }


        if (
            !is_numeric(
                $weightCandidate[
                    'strength_weight'
                ]
            )
        ) {

            throw new InvalidArgumentException(
                'Strength weight must be numeric.'
            );
        }


        if (
            !is_numeric(
                $weightCandidate[
                    'fixture_weight'
                ]
            )
        ) {

            throw new InvalidArgumentException(
                'Fixture weight must be numeric.'
            );
        }


        $strengthWeight =
            (float) $weightCandidate[
                'strength_weight'
            ];


        $fixtureWeight =
            (float) $weightCandidate[
                'fixture_weight'
            ];


        if (
            $strengthWeight < 0.0
            ||
            $strengthWeight > 1.0
        ) {

            throw new InvalidArgumentException(
                'Strength weight must be between zero and one.'
            );
        }


        if (
            $fixtureWeight < 0.0
            ||
            $fixtureWeight > 1.0
        ) {

            throw new InvalidArgumentException(
                'Fixture weight must be between zero and one.'
            );
        }


        /*
         * Floating-point tolerance avoids rejecting mathematically
         * valid decimal combinations because of representation
         * noise.
         */

        if (
            abs(
                (
                    $strengthWeight
                    +
                    $fixtureWeight
                )
                -
                1.0
            )
            >
            0.000001
        ) {

            throw new InvalidArgumentException(
                'Strength and Fixture weights must sum to one.'
            );
        }


        return [

            'strength_weight' =>
                $strengthWeight,

            'fixture_weight' =>
                $fixtureWeight
        ];
    }


    /**
     * Return a numeric historical value as float.
     *
     * Missing or unusable historical evidence remains null.
     *
     * Genuine zero and negative realised FPL points remain valid.
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
}