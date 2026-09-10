<?php

/**
 * PositionAwareFixtureWeightCalibrationService
 *
 * Evaluates alternative position-aware immediate Fixture
 * Intelligence weight combinations against preserved historical
 * recommendation evidence and realised FPL points.
 *
 * Historical calibration inputs must come from immutable
 * recommendation-time evidence.
 *
 * This service does not:
 *
 * - modify live Fixture Intelligence weights
 * - query current Player Intelligence
 * - query current fixture data
 * - reconstruct missing historical evidence
 * - calculate Expected Points
 * - calculate Captain Intelligence
 * - select a winning weight combination
 * - persist calibration results
 */
class PositionAwareFixtureWeightCalibrationService
{
    private object $metricsService;


    public function __construct(
        object $metricsService
    ) {

        $this->metricsService =
            $metricsService;
    }


    /**
     * Evaluate explicitly supplied base-Fixture /
     * position-performance weight candidates against
     * historical player evidence.
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


            $baseFixtureWeight =
                $validatedWeights[
                    'base_fixture_weight'
                ];


            $positionPerformanceWeight =
                $validatedWeights[
                    'position_performance_weight'
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
                 * Match the established historical-metrics
                 * semantics:
                 *
                 * malformed non-array rows are ignored entirely.
                 */
                if (!is_array($row)) {

                    continue;
                }


                /*
                 * =================================================
                 * PRESERVED HISTORICAL INPUTS
                 * =================================================
                 */

                $position =
                    strtoupper(
                        trim(
                            (string) (
                                $row[
                                    'position'
                                ]
                                ?? ''
                            )
                        )
                    );


                $baseFixtureRating =
                    $this
                        ->numericOrNull(
                            $row[
                                'base_next_fixture_rating'
                            ]
                            ?? null
                        );


                $opponentAttackRating =
                    $this
                        ->numericOrNull(
                            $row[
                                'next_opponent_attack_rating'
                            ]
                            ?? null
                        );


                $opponentDefenceRating =
                    $this
                        ->numericOrNull(
                            $row[
                                'next_opponent_defence_rating'
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
                 * CANDIDATE POSITION-AWARE FIXTURE SCORE
                 * =================================================
                 *
                 * Reproduce the production Fixture Intelligence
                 * calculation using preserved recommendation-time
                 * evidence.
                 *
                 * GK / DEF:
                 *     opponent Attack Rating
                 *
                 * MID / FWD:
                 *     opponent Defence Rating
                 *
                 * Missing required historical evidence remains
                 * unavailable rather than falling back to the base
                 * score.
                 *
                 * That distinction is deliberate for calibration:
                 * without both blend components, the historical row
                 * cannot compare alternative weight combinations.
                 */

                $candidateFixtureRating =
                    null;


                if (
                    $baseFixtureRating !== null
                    &&
                    in_array(
                        $position,
                        [
                            'GK',
                            'DEF',
                            'MID',
                            'FWD'
                        ],
                        true
                    )
                ) {

                    $relevantOpponentRating =
                        null;


                    if (
                        $position === 'GK'
                        ||
                        $position === 'DEF'
                    ) {

                        $relevantOpponentRating =
                            $opponentAttackRating;

                    } else {

                        $relevantOpponentRating =
                            $opponentDefenceRating;
                    }


                    if (
                        $relevantOpponentRating !== null
                    ) {

                        /*
                         * Production Fixture Intelligence bounds
                         * both component inputs to 0-100.
                         */

                        $boundedBaseFixtureRating =
                            $this
                                ->boundScore(
                                    $baseFixtureRating
                                );


                        $boundedOpponentRating =
                            $this
                                ->boundScore(
                                    $relevantOpponentRating
                                );


                        /*
                         * Opponent strength is inverted into
                         * player-facing opportunity.
                         */

                        $performanceOpportunity =
                            100.0
                            -
                            $boundedOpponentRating;


                        $candidateFixtureRating =
                            (
                                $boundedBaseFixtureRating
                                *
                                $baseFixtureWeight
                            )
                            +
                            (
                                $performanceOpportunity
                                *
                                $positionPerformanceWeight
                            );


                        $candidateFixtureRating =
                            $this
                                ->boundScore(
                                    $candidateFixtureRating
                                );


                        /*
                         * Match production Fixture Intelligence
                         * precision.
                         */
                        $candidateFixtureRating =
                            round(
                                $candidateFixtureRating,
                                2
                            );
                    }
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

                    'candidate_fixture_rating' =>
                        $candidateFixtureRating,

                    'actual_points' =>
                        $actualPoints
                ];
            }


            /*
             * ====================================================
             * OBJECTIVE HISTORICAL METRICS
             * ====================================================
             *
             * Reuse the established score-versus-realised-return
             * correlation implementation.
             *
             * The metrics service expects the analytical score
             * under the generic historical intelligence_score key.
             * No live Intelligence Score is being reconstructed.
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
                            'candidate_fixture_rating'
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

                'base_fixture_weight' =>
                    $baseFixtureWeight,

                'position_performance_weight' =>
                    $positionPerformanceWeight,

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
     * Validate one explicitly supplied position-aware Fixture
     * weight candidate.
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
                'base_fixture_weight',
                $weightCandidate
            )
        ) {

            throw new InvalidArgumentException(
                'Weight candidate must contain base_fixture_weight.'
            );
        }


        if (
            !array_key_exists(
                'position_performance_weight',
                $weightCandidate
            )
        ) {

            throw new InvalidArgumentException(
                'Weight candidate must contain position_performance_weight.'
            );
        }


        if (
            !is_numeric(
                $weightCandidate[
                    'base_fixture_weight'
                ]
            )
        ) {

            throw new InvalidArgumentException(
                'Base Fixture weight must be numeric.'
            );
        }


        if (
            !is_numeric(
                $weightCandidate[
                    'position_performance_weight'
                ]
            )
        ) {

            throw new InvalidArgumentException(
                'Position-performance weight must be numeric.'
            );
        }


        $baseFixtureWeight =
            (float) $weightCandidate[
                'base_fixture_weight'
            ];


        $positionPerformanceWeight =
            (float) $weightCandidate[
                'position_performance_weight'
            ];


        if (
            $baseFixtureWeight < 0.0
            ||
            $baseFixtureWeight > 1.0
        ) {

            throw new InvalidArgumentException(
                'Base Fixture weight must be between zero and one.'
            );
        }


        if (
            $positionPerformanceWeight < 0.0
            ||
            $positionPerformanceWeight > 1.0
        ) {

            throw new InvalidArgumentException(
                'Position-performance weight must be between zero and one.'
            );
        }


        /*
         * Floating-point tolerance avoids rejecting valid
         * combinations whose binary representation is infinitesimally
         * above or below one.
         */

        if (
            abs(
                (
                    $baseFixtureWeight
                    +
                    $positionPerformanceWeight
                )
                -
                1.0
            )
            >
            0.000001
        ) {

            throw new InvalidArgumentException(
                'Position-aware Fixture weights must sum to one.'
            );
        }


        return [

            'base_fixture_weight' =>
                $baseFixtureWeight,

            'position_performance_weight' =>
                $positionPerformanceWeight
        ];
    }


    /**
     * Return numeric historical evidence or null when unavailable.
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


    /**
     * Keep Fixture Intelligence evidence inside the production
     * 0-100 score range.
     */
    private function boundScore(
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
}