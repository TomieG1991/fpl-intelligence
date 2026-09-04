<?php

class TripleCaptainIntelligence
{
    /*
     * ============================================================
     * PROVISIONAL v0.34 DECISION POLICY
     * ============================================================
     *
     * These thresholds are application policy for the first
     * Triple Captain implementation.
     *
     * They are not empirical or official FPL thresholds.
     */
    private const HOLD_MAX_PROJECTED_POINTS =
        8.0;

    private const USE_MIN_PROJECTED_POINTS =
        12.0;

    private const USE_MIN_CAPTAIN_SCORE =
        65.0;


    /*
     * ============================================================
     * ANALYSE
     * ============================================================
     */

    public function analyse(
        array $opportunity
    ): array {

        $projectedPoints =
            $opportunity[
                'projected_points'
            ]
            ?? null;


        if (
            !is_numeric(
                $projectedPoints
            )
        ) {

            throw new InvalidArgumentException(
                'Triple Captain projected points are required.'
            );
        }


        $projectedPoints =
            (float) $projectedPoints;


        if (
            $projectedPoints
            <
            0.0
        ) {

            throw new InvalidArgumentException(
                'Triple Captain projected points cannot be negative.'
            );
        }


        $captainScore =
            $opportunity[
                'captain_score'
            ]
            ?? null;


        if (
            !is_numeric(
                $captainScore
            )
        ) {

            throw new InvalidArgumentException(
                'Triple Captain captain score is required.'
            );
        }


        $captainScore =
            (float) $captainScore;


        $projectionConfidence =
            $this->normaliseOptionalConfidence(
                $opportunity[
                    'projection_confidence'
                ]
                ?? null,
                'Projection confidence'
            );


        $captainConfidence =
            $this->normaliseOptionalConfidence(
                $opportunity[
                    'captain_confidence'
                ]
                ?? null,
                'Captain Intelligence confidence'
            );


        return [

            'player_id' =>
                isset(
                    $opportunity[
                        'player_id'
                    ]
                )
                    ? (int) $opportunity[
                        'player_id'
                    ]
                    : 0,

            'name' =>
                (string) (
                    $opportunity[
                        'name'
                    ]
                    ?? ''
                ),

            'position' =>
                (string) (
                    $opportunity[
                        'position'
                    ]
                    ?? ''
                ),

            /*
             * Preserve the existing Expected Points result.
             *
             * Do not recalculate or multiply for Double Gameweeks.
             */
            'projected_captain_points' =>
                $projectedPoints,

            /*
             * Preserve the existing CaptainIntelligence result.
             */
            'captain_score' =>
                $captainScore,

            'projection_confidence' =>
                $projectionConfidence,

            'captain_confidence' =>
                $captainConfidence,

            'fixture_count' =>
                isset(
                    $opportunity[
                        'fixture_count'
                    ]
                )
                &&
                is_numeric(
                    $opportunity[
                        'fixture_count'
                    ]
                )
                    ? (int) $opportunity[
                        'fixture_count'
                    ]
                    : 0,

            'schedule_type' =>
                (string) (
                    $opportunity[
                        'schedule_type'
                    ]
                    ?? ''
                )
        ];
    }


    /*
     * ============================================================
     * CREATE DECISION
     * ============================================================
     */

    public function createDecision(
        array $analysis
    ): ChipDecision {

        $projectedPoints =
            isset(
                $analysis[
                    'projected_captain_points'
                ]
            )
            &&
            is_numeric(
                $analysis[
                    'projected_captain_points'
                ]
            )
                ? (float) $analysis[
                    'projected_captain_points'
                ]
                : 0.0;


        $captainScore =
            isset(
                $analysis[
                    'captain_score'
                ]
            )
            &&
            is_numeric(
                $analysis[
                    'captain_score'
                ]
            )
                ? (float) $analysis[
                    'captain_score'
                ]
                : 0.0;


        $projectionConfidence =
            isset(
                $analysis[
                    'projection_confidence'
                ]
            )
            &&
            is_numeric(
                $analysis[
                    'projection_confidence'
                ]
            )
                ? $this->boundRatio(
                    (float) $analysis[
                        'projection_confidence'
                    ]
                )
                : 0.0;


        $captainConfidence =
            isset(
                $analysis[
                    'captain_confidence'
                ]
            )
            &&
            is_numeric(
                $analysis[
                    'captain_confidence'
                ]
            )
                ? $this->boundRatio(
                    (float) $analysis[
                        'captain_confidence'
                    ]
                )
                : 0.0;


        $decisionConfidence =
            min(
                $projectionConfidence,
                $captainConfidence
            );


        if (
            $projectedPoints
            <=
            self::HOLD_MAX_PROJECTED_POINTS
        ) {

            return new ChipDecision(
                'Triple Captain',
                'Hold',
                $decisionConfidence,
                'The projected captain return is not exceptional enough to justify using the Triple Captain chip.'
            );
        }


        if (
            $projectedPoints
            >=
            self::USE_MIN_PROJECTED_POINTS
            &&
            $captainScore
            >=
            self::USE_MIN_CAPTAIN_SCORE
        ) {

            return new ChipDecision(
                'Triple Captain',
                'Use',
                $decisionConfidence,
                'The captain combines exceptional projected points with elite Captain Intelligence, making this a strong Triple Captain opportunity.'
            );
        }


        return new ChipDecision(
            'Triple Captain',
            'Consider',
            $decisionConfidence,
            'The captain opportunity is strong, but the projected return or Captain Intelligence is not exceptional enough for an automatic Use recommendation.'
        );
    }


    /*
     * ============================================================
     * OPTIONAL CONFIDENCE VALIDATION
     * ============================================================
     */

    private function normaliseOptionalConfidence(
        mixed $value,
        string $label
    ): ?float {

        if (
            $value === null
        ) {

            return null;
        }


        if (
            !is_numeric(
                $value
            )
        ) {

            throw new InvalidArgumentException(
                $label
                . ' must be numeric when provided.'
            );
        }


        $value =
            (float) $value;


        if (
            $value
            <
            0.0
            ||
            $value
            >
            1.0
        ) {

            throw new InvalidArgumentException(
                $label
                . ' must be between 0 and 1.'
            );
        }


        return $value;
    }


    /*
     * ============================================================
     * RATIO BOUNDING
     * ============================================================
     */

    private function boundRatio(
        float $value
    ): float {

        return max(
            0.0,
            min(
                1.0,
                $value
            )
        );
    }
}