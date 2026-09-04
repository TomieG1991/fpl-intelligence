<?php

class BenchBoostIntelligence
{
    /*
     * ============================================================
     * PROVISIONAL v0.34 DECISION POLICY
     * ============================================================
     *
     * The roadmap requires Use / Consider / Hold output but does
     * not prescribe empirical Bench Boost thresholds.
     *
     * These therefore belong to the current application decision
     * policy and can be recalibrated later without changing the
     * underlying Bench Boost measurements.
     */
    private const HOLD_MAX_BENCH_POINTS =
        8.0;

    private const USE_MIN_BENCH_POINTS =
        15.0;


    /**
     * Analyse one already-built Squad Horizon gameweek for
     * Bench Boost suitability.
     *
     * This class does not calculate Expected Points.
     *
     * It consumes existing squad-horizon and projection evidence:
     *
     * - projected bench points
     * - projection confidence
     * - fixture opportunity
     * - chance of playing
     */
    public function analyse(
        array $gameweek
    ): array {

        /*
         * --------------------------------------------------------
         * PROJECTED BENCH POINTS
         * --------------------------------------------------------
         *
         * SquadHorizonIntelligence remains the source of truth.
         *
         * Do not recalculate the total from individual players.
         */
        $projectedBenchPoints =
            isset(
                $gameweek[
                    'bench_coverage'
                ][
                    'total_projected_points'
                ]
            )
            &&
            is_numeric(
                $gameweek[
                    'bench_coverage'
                ][
                    'total_projected_points'
                ]
            )
                ? (float) $gameweek[
                    'bench_coverage'
                ][
                    'total_projected_points'
                ]
                : null;


        /*
         * --------------------------------------------------------
         * BENCH RELIABILITY
         * --------------------------------------------------------
         *
         * Reliability is the arithmetic mean of the existing
         * projection confidence for all four bench players.
         *
         * If any bench player lacks confidence evidence, the
         * aggregate reliability remains unavailable rather than
         * pretending the incomplete sample is complete.
         */
        $bench =
            isset(
                $gameweek[
                    'bench'
                ]
            )
            &&
            is_array(
                $gameweek[
                    'bench'
                ]
            )
                ? $gameweek[
                    'bench'
                ]
                : [];


        $benchReliability =
            $this->calculateBenchReliability(
                $bench
            );


        /*
         * --------------------------------------------------------
         * FIXTURE QUALITY
         * --------------------------------------------------------
         *
         * Expected Points already incorporates fixture context.
         *
         * Fixture quality is therefore diagnostic information only.
         * It must not be applied as another points multiplier.
         */
        $fixtureQuality =
            $this->calculateFixtureQuality(
                $bench
            );


        /*
         * --------------------------------------------------------
         * FULL-SQUAD AVAILABILITY
         * --------------------------------------------------------
         *
         * Availability is measured once per player.
         *
         * Double Gameweeks must not count one player twice merely
         * because that player has two fixture rows.
         */
        $players =
            isset(
                $gameweek[
                    'players'
                ]
            )
            &&
            is_array(
                $gameweek[
                    'players'
                ]
            )
                ? $gameweek[
                    'players'
                ]
                : [];


        $fullSquadAvailability =
            $this->calculateFullSquadAvailability(
                $players
            );


        return [

            'projected_bench_points' =>
                $projectedBenchPoints,

            'bench_reliability' =>
                $benchReliability,

            'fixture_quality' =>
                $fixtureQuality,

            'full_squad_availability' =>
                $fullSquadAvailability
        ];
    }


    /**
     * Convert Bench Boost measurements into the common
     * ChipDecision contract.
     */
    public function createDecision(
        array $analysis
    ): ChipDecision {

        $projectedBenchPoints =
            isset(
                $analysis[
                    'projected_bench_points'
                ]
            )
            &&
            is_numeric(
                $analysis[
                    'projected_bench_points'
                ]
            )
                ? (float) $analysis[
                    'projected_bench_points'
                ]
                : 0.0;


        $benchReliability =
            isset(
                $analysis[
                    'bench_reliability'
                ]
            )
            &&
            is_numeric(
                $analysis[
                    'bench_reliability'
                ]
            )
                ? $this->boundRatio(
                    (float) $analysis[
                        'bench_reliability'
                    ]
                )
                : 0.0;


        $fullSquadAvailability =
            isset(
                $analysis[
                    'full_squad_availability'
                ]
            )
            &&
            is_numeric(
                $analysis[
                    'full_squad_availability'
                ]
            )
                ? $this->boundRatio(
                    (float) $analysis[
                        'full_squad_availability'
                    ]
                )
                : 0.0;


        /*
         * A Bench Boost recommendation cannot be more confident
         * than its weaker reliability / availability signal.
         */
        $confidence =
            min(
                $benchReliability,
                $fullSquadAvailability
            );


        if (
            $projectedBenchPoints
            <=
            self::HOLD_MAX_BENCH_POINTS
        ) {

            return new ChipDecision(
                'Bench Boost',
                'Hold',
                $confidence,
                'The projected bench return is too small to justify using the Bench Boost chip.'
            );
        }


        if (
            $projectedBenchPoints
            >=
            self::USE_MIN_BENCH_POINTS
        ) {

            return new ChipDecision(
                'Bench Boost',
                'Use',
                $confidence,
                'The projected bench return is strong enough to justify using the Bench Boost chip.'
            );
        }


        return new ChipDecision(
            'Bench Boost',
            'Consider',
            $confidence,
            'The projected bench return is meaningful but not strong enough for an automatic Use recommendation.'
        );
    }


    /**
     * Average projection confidence across the complete bench.
     */
    private function calculateBenchReliability(
        array $bench
    ): ?float {

        if (
            count(
                $bench
            )
            !==
            4
        ) {

            return null;
        }


        $confidences =
            [];


        foreach (
            $bench
            as $benchPlayer
        ) {

            $confidence =
                $benchPlayer[
                    'projection_confidence'
                ]
                ?? null;


            if (
                !is_numeric(
                    $confidence
                )
            ) {

                return null;
            }


            $confidences[] =
                $this->boundRatio(
                    (float) $confidence
                );
        }


        return
            array_sum(
                $confidences
            )
            /
            count(
                $confidences
            );
    }


    /**
     * Measure the average existing fixture opportunity across
     * the four bench players.
     *
     * For a player with multiple fixtures in the gameweek,
     * each fixture contributes to that player's own fixture
     * quality before the four player values are averaged.
     */
    private function calculateFixtureQuality(
        array $bench
    ): ?float {

        if (
            count(
                $bench
            )
            !==
            4
        ) {

            return null;
        }


        $playerFixtureQualities =
            [];


        foreach (
            $bench
            as $benchPlayer
        ) {

            $fixtures =
                isset(
                    $benchPlayer[
                        'fixtures'
                    ]
                )
                &&
                is_array(
                    $benchPlayer[
                        'fixtures'
                    ]
                )
                    ? $benchPlayer[
                        'fixtures'
                    ]
                    : [];


            $fixtureOpportunities =
                [];


            foreach (
                $fixtures
                as $fixture
            ) {

                $fixtureOpportunity =
                    $fixture[
                        'fixture_opportunity'
                    ]
                    ?? null;


                if (
                    !is_numeric(
                        $fixtureOpportunity
                    )
                ) {

                    continue;
                }


                $fixtureOpportunities[] =
                    max(
                        0.0,
                        min(
                            100.0,
                            (float) $fixtureOpportunity
                        )
                    );
            }


            if (
                empty(
                    $fixtureOpportunities
                )
            ) {

                return null;
            }


            $playerFixtureQualities[] =
                array_sum(
                    $fixtureOpportunities
                )
                /
                count(
                    $fixtureOpportunities
                );
        }


        return
            array_sum(
                $playerFixtureQualities
            )
            /
            count(
                $playerFixtureQualities
            );
    }


    /**
     * Measure availability once per squad player.
     *
     * A player's current chance-of-playing evidence is produced
     * upstream by ExpectedMinutes and merely consumed here.
     */
    private function calculateFullSquadAvailability(
        array $players
    ): ?float {

        if (
            count(
                $players
            )
            !==
            15
        ) {

            return null;
        }


        $playerAvailability =
            [];


        foreach (
            $players
            as $player
        ) {

            $fixtures =
                isset(
                    $player[
                        'fixtures'
                    ]
                )
                &&
                is_array(
                    $player[
                        'fixtures'
                    ]
                )
                    ? $player[
                        'fixtures'
                    ]
                    : [];


            $fixtureAvailability =
                [];


            foreach (
                $fixtures
                as $fixture
            ) {

                $chanceOfPlaying =
                    $fixture[
                        'projection'
                    ][
                        'chance_of_playing'
                    ]
                    ?? null;


                if (
                    !is_numeric(
                        $chanceOfPlaying
                    )
                ) {

                    continue;
                }


                $fixtureAvailability[] =
                    max(
                        0.0,
                        min(
                            100.0,
                            (float) $chanceOfPlaying
                        )
                    );
            }


            if (
                empty(
                    $fixtureAvailability
                )
            ) {

                return null;
            }


            /*
             * Availability describes the player, not fixture count.
             *
             * In normal production data these values should be the
             * same across fixtures because they originate from the
             * same current availability evidence.
             *
             * Using the weakest value remains conservative if the
             * evidence ever differs.
             */
            $playerAvailability[] =
                min(
                    $fixtureAvailability
                );
        }


        return
            (
                array_sum(
                    $playerAvailability
                )
                /
                count(
                    $playerAvailability
                )
            )
            /
            100.0;
    }


    /**
     * Keep confidence-style values on the common 0.0-1.0 scale.
     */
    private function boundRatio(
        float $value
    ): float {

        return
            max(
                0.0,
                min(
                    1.0,
                    $value
                )
            );
    }
}