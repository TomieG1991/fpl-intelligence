<?php

class CaptainIntelligence
{
    /*
     * ============================================================
     * CAPTAIN SCORE WEIGHTS
     * ============================================================
     */

    private const STRENGTH_WEIGHT =
    0.35;

    private const FIXTURE_WEIGHT =
        0.35;

    private const ATTACKING_THREAT_WEIGHT =
        0.30;


    /*
     * ============================================================
     * PUBLIC API
     * ============================================================
     */

    public function evaluate(
        array $player
    ): array {

        $validation =
            $this->validatePlayer(
                $player
            );


        if (
            !$validation[
                'valid'
            ]
        ) {

            return [

                'status' =>
                    'invalid',

                'message' =>
                    $validation[
                        'message'
                    ],

                'captain_score' =>
                    null,

                'classification' =>
                    null,

                'components' =>
                    []
            ];
        }


        $strength =
            $this->normaliseScore(
                $player[
                    'strength_score'
                ]
                ?? null
            );


        $rawFixture =
            $this->normaliseScore(
                $player[
                    'fixture_score'
                ]
                ?? null
            );


        $fixture =
            $this->calculateCaptainFixtureScore(
                $rawFixture
            );


        $attackingThreat =
            $this->calculateAttackingThreat(
                $player
            );


        /*
         * ========================================================
         * DECISION CONFIDENCE
         * ========================================================
         *
         * Prefer Effective Confidence when available.
         *
         * Effective Confidence combines:
         *
         * - current-season sample maturity
         * - share of team-available minutes played
         *
         * Raw sample confidence remains the fallback for:
         *
         * - older callers
         * - synthetic tests
         * - teams with no completed match evidence yet
         */

        $confidence =
            $this->normalisePercentage(
                $player[
                    'effective_confidence'
                ]
                ??
                $player[
                    'sample_confidence'
                ]
                ??
                null
            );


        $availability =
            $this->normalisePercentage(
                $player[
                    'availability'
                ]
                ?? null
            );


        /*
         * ============================================================
         * CORE CAPTAIN SCORE
         * ============================================================
         *
         * Captain quality is determined by three footballing factors:
         *
         * - underlying player strength
         * - upcoming fixture opportunity
         * - attacking threat
         *
         * Confidence and availability are deliberately excluded from
         * the additive score. They are risk modifiers applied after
         * the underlying captain quality has been established.
         */

        $coreCaptainScore =
            (
                $strength
                *
                self::STRENGTH_WEIGHT
            )
            +
            (
                $fixture
                *
                self::FIXTURE_WEIGHT
            )
            +
            (
                $attackingThreat
                *
                self::ATTACKING_THREAT_WEIGHT
            );


        $coreCaptainScore =
            max(
                0.0,
                min(
                    100.0,
                    $coreCaptainScore
                )
            );


        /*
         * ============================================================
         * RISK MODIFIERS
         * ============================================================
         */

        $confidenceModifier =
            $this->calculateConfidenceModifier(
                $confidence
            );


        $availabilityModifier =
            $this->calculateAvailabilityModifier(
                $availability
            );


        /*
         * ============================================================
         * FINAL CAPTAIN SCORE
         * ============================================================
         */

        $captainScore =
            $coreCaptainScore
            *
            $confidenceModifier
            *
            $availabilityModifier;


        $captainScore =
            round(
                max(
                    0.0,
                    min(
                        100.0,
                        $captainScore
                    )
                ),
                2
            );


        $coreCaptainScore =
            round(
                $coreCaptainScore,
                2
            );


        return [

            'status' =>
                'success',

            'player_id' =>
                (int) (
                    $player[
                        'player_id'
                    ]
                    ?? 0
                ),

            'name' =>
                (string) (
                    $player[
                        'name'
                    ]
                    ?? ''
                ),

            'position' =>
                (string) (
                    $player[
                        'position'
                    ]
                    ?? ''
                ),

            'captain_score' =>
                $captainScore,

            'classification' =>
                $this->classify(
                    $captainScore
                ),

            'components' => [

            'strength' =>
                $strength,

            'raw_fixture' =>
                $rawFixture,

            'fixture' =>
                $fixture,

            'attacking_threat' =>
                $attackingThreat,

            'core_score' =>
                $coreCaptainScore,

            'confidence' =>
                $confidence,

            'confidence_modifier' =>
                $confidenceModifier,

            'availability' =>
                $availability,

            'availability_modifier' =>
                $availabilityModifier
        ],

            'summary' =>
                $this->buildSummary(
                    $player,
                    $captainScore,
                    $fixture,
                    $attackingThreat,
                    $confidence,
                    $availability
                )
        ];
    }


    /*
     * ============================================================
     * VALIDATION
     * ============================================================
     */

    private function validatePlayer(
        array $player
    ): array {

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
                [
                    'GK',
                    'DEF',
                    'MID',
                    'FWD'
                ],
                true
            )
        ) {

            return [

                'valid' =>
                    false,

                'message' =>
                    'Player position is invalid.'
            ];
        }


        if (
            !is_numeric(
                $player[
                    'strength_score'
                ]
                ?? null
            )
        ) {

            return [

                'valid' =>
                    false,

                'message' =>
                    'Player strength is required.'
            ];
        }


        if (
            !is_numeric(
                $player[
                    'fixture_score'
                ]
                ?? null
            )
        ) {

            return [

                'valid' =>
                    false,

                'message' =>
                    'Next fixture opportunity is required.'
            ];
        }


        if (
            !is_numeric(
                $player[
                    'sample_confidence'
                ]
                ?? null
            )
        ) {

            return [

                'valid' =>
                    false,

                'message' =>
                    'Sample confidence is required.'
            ];
        }


        if (
            !is_numeric(
                $player[
                    'availability'
                ]
                ?? null
            )
        ) {

            return [

                'valid' =>
                    false,

                'message' =>
                    'Player availability is required.'
            ];
        }


        return [

            'valid' =>
                true,

            'message' =>
                null
        ];
    }


    /*
     * ============================================================
     * ATTACKING THREAT
     * ============================================================
     */

    private function calculateAttackingThreat(
        array $player
    ): float {

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


        /*
         * ========================================================
         * CONFIDENCE-ADJUSTED ATTACKING INPUTS
         * ========================================================
         *
         * Prefer the sample-adjusted ratings generated by
         * PlayerPerformance.
         *
         * Raw ratings remain as a backwards-compatible fallback for
         * existing direct callers and synthetic tests.
         */

        $goals =
            $this->normaliseScore(
                $player[
                    'adjusted_goals_rating'
                ]
                ??
                $player[
                    'goals_rating'
                ]
                ??
                null
            );


        $assists =
            $this->normaliseScore(
                $player[
                    'adjusted_assists_rating'
                ]
                ??
                $player[
                    'assists_rating'
                ]
                ??
                null
            );


        $expectedGoals =
            $this->normaliseScore(
                $player[
                    'adjusted_expected_goals_rating'
                ]
                ??
                $player[
                    'expected_goals_rating'
                ]
                ??
                null
            );


        $expectedAssists =
            $this->normaliseScore(
                $player[
                    'adjusted_expected_assists_rating'
                ]
                ??
                $player[
                    'expected_assists_rating'
                ]
                ??
                null
            );


        /*
         * ========================================================
         * GOALKEEPER
         * ========================================================
         *
         * Goalkeepers receive only a small attacking-threat
         * representation. Captain Intelligence should naturally
         * favour players with realistic attacking return potential.
         */

        if (
            $position === 'GK'
        ) {

            $rawAttackingThreat =
                (
                    $expectedGoals
                    *
                    0.40
                )
                +
                (
                    $goals
                    *
                    0.25
                )
                +
                (
                    $expectedAssists
                    *
                    0.20
                )
                +
                (
                    $assists
                    *
                    0.15
                );


            /*
             * Goalkeeper attacking returns are exceptionally rare.
             *
             * The upstream PlayerPerformance ratings are normalised
             * relative to position, so a goalkeeper can theoretically
             * receive a very high attacking rating from a tiny number
             * of unusual events.
             *
             * Captain Intelligence therefore applies an explicit
             * goalkeeper attacking-threat reduction so those
             * position-relative ratings cannot make goalkeepers look
             * like genuine attacking captain options.
             */

            return round(
                $rawAttackingThreat
                *
                0.25,
                2
            );
        }


        /*
         * ========================================================
         * DEFENDER
         * ========================================================
         *
         * Expected attacking output receives slightly more weight
         * than historic returns to reduce the impact of isolated
         * defender goals or assists.
         */

        if (
            $position === 'DEF'
        ) {

            return round(
                (
                    $expectedGoals
                    *
                    0.40
                )
                +
                (
                    $goals
                    *
                    0.25
                )
                +
                (
                    $expectedAssists
                    *
                    0.20
                )
                +
                (
                    $assists
                    *
                    0.15
                ),
                2
            );
        }


        /*
         * ========================================================
         * MIDFIELDERS / FORWARDS
         * ========================================================
         *
         * Captaincy should prioritise sustainable goal threat.
         *
         * Expected goals therefore receives the largest individual
         * weighting, followed by actual goal production.
         */

        return round(
            (
                $expectedGoals
                *
                0.40
            )
            +
            (
                $goals
                *
                0.25
            )
            +
            (
                $expectedAssists
                *
                0.20
            )
            +
            (
                $assists
                *
                0.15
            ),
            2
        );
    }


    /*
     * ============================================================
     * CLASSIFICATION
     * ============================================================
     */

    private function classify(
        float $score
    ): string {

        if (
            $score >= 65.0
        ) {

            return 'Elite Captain';
        }


        if (
            $score >= 60.0
        ) {

            return 'Strong Captain';
        }


        if (
            $score >= 55.0
        ) {

            return 'Good Option';
        }


        if (
            $score >= 50.0
        ) {

            return 'Differential';
        }


        return 'Avoid';
    }


    /*
     * ============================================================
     * SUMMARY
     * ============================================================
     */

    private function buildSummary(
        array $player,
        float $score,
        float $fixture,
        float $attackingThreat,
        float $confidence,
        float $availability
    ): string {

        $name =
            (string) (
                $player[
                    'name'
                ]
                ?? 'This player'
            );


        $classification =
            $this->classify(
                $score
            );


        $parts = [

            $name
            . ' is classified as '
            . strtolower(
                $classification
            )
            . '.'
        ];


        if (
            $fixture >= 70.0
        ) {

            $parts[] =
                'The upcoming fixture is favourable.';
        }


        if (
            $attackingThreat >= 70.0
        ) {

            $parts[] =
                'Attacking threat is strong.';
        }


        if (
            $confidence < 50.0
        ) {

            $parts[] =
                'The performance sample is still relatively uncertain.';
        }


        if (
            $availability < 75.0
        ) {

            $parts[] =
                'Availability reduces the captain appeal.';
        }


        return implode(
            ' ',
            $parts
        );
    }
    
    /*
     * ============================================================
     * CAPTAIN FIXTURE CALIBRATION
     * ============================================================
     */

    private function calculateCaptainFixtureScore(
        float $fixture
    ): float {

        $fixture =
            max(
                0.0,
                min(
                    100.0,
                    $fixture
                )
            );


        /*
         * Single-gameweek fixture opportunity can be much more
         * extreme than the rolling five-fixture rating used by
         * general Player Intelligence.
         *
         * Captain Intelligence therefore compresses fixture scores
         * towards the neutral midpoint of 50.
         *
         * This preserves fixture ordering while preventing a single
         * favourable fixture from overwhelming player strength and
         * attacking threat.
         *
         * Examples:
         *
         * Raw 0.0   -> 20.0
         * Raw 33.3  -> 40.0
         * Raw 50.0  -> 50.0
         * Raw 66.7  -> 60.0
         * Raw 100.0 -> 80.0
         */

        $calibrated =
            50.0
            +
            (
                (
                    $fixture
                    -
                    50.0
                )
                *
                0.60
            );


        return round(
            max(
                0.0,
                min(
                    100.0,
                    $calibrated
                )
            ),
            2
        );
    }
    
    
    /*
     * ============================================================
     * CONFIDENCE MODIFIER
     * ============================================================
     */

    private function calculateConfidenceModifier(
        float $confidence
    ): float {

        $confidence =
            max(
                0.0,
                min(
                    100.0,
                    $confidence
                )
            );


        /*
         * 75% to 100%
         *
         * 75% confidence = 0.96
         * 100% confidence = 1.00
         */

        if (
            $confidence >= 75.0
        ) {

            return round(
                0.96
                +
                (
                    (
                        $confidence
                        -
                        75.0
                    )
                    /
                    25.0
                )
                *
                0.04,
                4
            );
        }


        /*
         * 50% to 75%
         *
         * 50% confidence = 0.90
         * 75% confidence = 0.96
         */

        if (
            $confidence >= 50.0
        ) {

            return round(
                0.90
                +
                (
                    (
                        $confidence
                        -
                        50.0
                    )
                    /
                    25.0
                )
                *
                0.06,
                4
            );
        }


        /*
         * 25% to 50%
         *
         * 25% confidence = 0.80
         * 50% confidence = 0.90
         */

        if (
            $confidence >= 25.0
        ) {

            return round(
                0.80
                +
                (
                    (
                        $confidence
                        -
                        25.0
                    )
                    /
                    25.0
                )
                *
                0.10,
                4
            );
        }


        /*
         * 0% to 25%
         *
         * 0% confidence = 0.70
         * 25% confidence = 0.80
         */

        return round(
            0.70
            +
            (
                $confidence
                /
                25.0
            )
            *
            0.10,
            4
        );
    }
    
    /*
     * ============================================================
     * AVAILABILITY MODIFIER
     * ============================================================
     */

    private function calculateAvailabilityModifier(
        float $availability
    ): float {

        $availability =
            max(
                0.0,
                min(
                    100.0,
                    $availability
                )
            );


        /*
         * Fully available players receive no penalty.
         */

        if (
            $availability >= 100.0
        ) {

            return 1.0;
        }


        /*
         * 75% to 100%
         *
         * 75% availability = 0.95
         * 100% availability = 1.00
         */

        if (
            $availability >= 75.0
        ) {

            return round(
                0.95
                +
                (
                    (
                        $availability
                        -
                        75.0
                    )
                    /
                    25.0
                )
                *
                0.05,
                4
            );
        }


        /*
         * 50% to 75%
         *
         * 50% availability = 0.85
         * 75% availability = 0.95
         */

        if (
            $availability >= 50.0
        ) {

            return round(
                0.85
                +
                (
                    (
                        $availability
                        -
                        50.0
                    )
                    /
                    25.0
                )
                *
                0.10,
                4
            );
        }


        /*
         * 25% to 50%
         *
         * 25% availability = 0.65
         * 50% availability = 0.85
         */

        if (
            $availability >= 25.0
        ) {

            return round(
                0.65
                +
                (
                    (
                        $availability
                        -
                        25.0
                    )
                    /
                    25.0
                )
                *
                0.20,
                4
            );
        }


        /*
         * 0% to 25%
         *
         * 0% availability = 0.40
         * 25% availability = 0.65
         */

        return round(
            0.40
            +
            (
                $availability
                /
                25.0
            )
            *
            0.25,
            4
        );
    }


    /*
     * ============================================================
     * NORMALISATION
     * ============================================================
     */

    private function normaliseScore(
        mixed $value
    ): float {

        if (
            !is_numeric(
                $value
            )
        ) {

            return 0.0;
        }


        return max(
            0.0,
            min(
                100.0,
                (float) $value
            )
        );
    }


    private function normalisePercentage(
        mixed $value
    ): float {

        if (
            !is_numeric(
                $value
            )
        ) {

            return 0.0;
        }


        $value =
            (float) $value;


        /*
         * Support both:
         *
         * 1.0   = 100%
         * 0.75  = 75%
         * 75    = 75%
         */

        if (
            $value >= 0.0
            &&
            $value <= 1.0
        ) {

            $value *=
                100.0;
        }


        return max(
            0.0,
            min(
                100.0,
                $value
            )
        );
    }
}