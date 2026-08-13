<?php

class TeamFixtureProfile
{
    /**
     * Convert a fixture rating into a
     * human-readable label.
     */
    public function getFixtureLabel(
        ?float $fixtureRating
    ): ?string {

        if ($fixtureRating === null) {
            return null;
        }


        $fixtureRating =
            max(
                0,
                min(
                    100,
                    $fixtureRating
                )
            );


        if ($fixtureRating >= 85) {
            return 'Excellent';
        }


        if ($fixtureRating >= 70) {
            return 'Good';
        }


        if ($fixtureRating >= 55) {
            return 'Average';
        }


        if ($fixtureRating >= 40) {
            return 'Difficult';
        }


        return 'Very Difficult';
    }


    /**
     * Calculate the overall fixture rating.
     *
     * For the current model, the next five
     * fixtures are the primary short-term indicator.
     */
    public function calculateFixtureRating(
        array $rollingAverages
    ): ?float {

        if (
            !array_key_exists(
                'next_5',
                $rollingAverages
            )
            ||
            $rollingAverages['next_5'] === null
            ||
            !is_numeric(
                $rollingAverages['next_5']
            )
        ) {

            return null;
        }


        return round(
            max(
                0,
                min(
                    100,
                    (float)
                        $rollingAverages[
                            'next_5'
                        ]
                )
            ),
            2
        );
    }


    /**
     * Build a fixture profile directly from fixture data.
     *
     * This method exists for backward compatibility.
     *
     * Modern application code should prefer
     * buildProfileFromAnalysis(), using fixture intelligence
     * that has already been calculated from TeamStrengthModel.
     */
    public function buildProfile(
        int $teamId,
        string $teamName,
        array $fixtures,
        FixtureIntelligence $fixtureIntelligence
    ): array {

        $teamStrengths =
            $this->getTeamStrengths(
                $fixtures
            );


        /*
         * Legacy fixture analysis cannot operate safely
         * without strength information for the requested team.
         */
        if (
            !isset(
                $teamStrengths[$teamId]
            )
        ) {

            return $this->buildProfileFromAnalysis(
                $teamId,
                $teamName,
                [],
                $fixtureIntelligence
            );
        }


        $fixtureRun =
            $fixtureIntelligence
                ->analyseFixtureRun(
                    $fixtures,
                    $teamStrengths,
                    $teamId
                );


        return $this->buildProfileFromAnalysis(
            $teamId,
            $teamName,
            $fixtureRun,
            $fixtureIntelligence
        );
    }


    /**
     * Build a profile from already analysed
     * fixture intelligence.
     *
     * This is the preferred application path.
     */
    public function buildProfileFromAnalysis(
        int $teamId,
        string $teamName,
        array $fixtureRun,
        FixtureIntelligence $fixtureIntelligence
    ): array {

        /*
         * Rolling fixture averages.
         */
        $rollingAverages =
            $fixtureIntelligence
                ->calculateRollingAverages(
                    $fixtureRun
                );


        if (!is_array($rollingAverages)) {
            $rollingAverages = [];
        }


        /*
         * Overall fixture rating.
         */
        $fixtureRating =
            $this->calculateFixtureRating(
                $rollingAverages
            );


        /*
         * Best and worst five-fixture runs.
         */
        $bestRun =
            $fixtureIntelligence
                ->findBestRun(
                    $fixtureRun,
                    5
                );


        $worstRun =
            $fixtureIntelligence
                ->findWorstRun(
                    $fixtureRun,
                    5
                );


        /*
         * Fixture trend.
         */
        $trend =
            $fixtureIntelligence
                ->calculateTrend(
                    $fixtureRun
                );


        /*
         * Return a consistent front-end-friendly structure.
         */
        return [

            'team_id' =>
                $teamId,

            'team_name' =>
                $teamName,

            'fixture_rating' =>
                $fixtureRating,

            'fixture_label' =>
                $this->getFixtureLabel(
                    $fixtureRating
                ),

            'rolling_averages' =>
                $rollingAverages,

            'next_5' =>
                $rollingAverages['next_5']
                ?? null,

            'next_6' =>
                $rollingAverages['next_6']
                ?? null,

            'next_8' =>
                $rollingAverages['next_8']
                ?? null,

            'next_10' =>
                $rollingAverages['next_10']
                ?? null,

            'best_run' =>
                $bestRun,

            'worst_run' =>
                $worstRun,

            'trend' =>
                $trend
                ?? 'Insufficient Data',

            'fixture_count' =>
                count(
                    $fixtureRun
                ),

            'fixtures' =>
                array_values(
                    $fixtureRun
                )
        ];
    }


    /**
     * Reconstruct team strengths from legacy
     * fixture data.
     *
     * Modern application code should not normally
     * rely on this method.
     */
    private function getTeamStrengths(
        array $fixtures
    ): array {

        $teamStrengths = [];


        foreach ($fixtures as $fixture) {

            if (
                !isset(
                    $fixture['home_team_id'],
                    $fixture['away_team_id']
                )
            ) {

                continue;
            }


            /*
             * Legacy fixture data requires explicit
             * home and away strength values.
             */
            if (
                !isset(
                    $fixture['home_strength'],
                    $fixture['away_strength']
                )
                ||
                !is_numeric(
                    $fixture['home_strength']
                )
                ||
                !is_numeric(
                    $fixture['away_strength']
                )
            ) {

                continue;
            }


            $homeId =
                (int)
                    $fixture[
                        'home_team_id'
                    ];


            $awayId =
                (int)
                    $fixture[
                        'away_team_id'
                    ];


            $homeStrength =
                $this->normaliseStrength(
                    (float)
                        $fixture[
                            'home_strength'
                        ]
                );


            $awayStrength =
                $this->normaliseStrength(
                    (float)
                        $fixture[
                            'away_strength'
                        ]
                );


            $teamStrengths[$homeId] = [

                'id' =>
                    $homeId,

                'name' =>
                    $fixture['home_team']
                    ?? 'Unknown',

                'home' =>
                    $homeStrength,

                'away' =>
                    isset(
                        $fixture[
                            'home_away_strength'
                        ]
                    )
                    &&
                    is_numeric(
                        $fixture[
                            'home_away_strength'
                        ]
                    )
                        ? $this->normaliseStrength(
                            (float)
                                $fixture[
                                    'home_away_strength'
                                ]
                        )
                        : $homeStrength
            ];


            $teamStrengths[$awayId] = [

                'id' =>
                    $awayId,

                'name' =>
                    $fixture['away_team']
                    ?? 'Unknown',

                'home' =>
                    isset(
                        $fixture[
                            'away_home_strength'
                        ]
                    )
                    &&
                    is_numeric(
                        $fixture[
                            'away_home_strength'
                        ]
                    )
                        ? $this->normaliseStrength(
                            (float)
                                $fixture[
                                    'away_home_strength'
                                ]
                        )
                        : $awayStrength,

                'away' =>
                    $awayStrength
            ];
        }


        return $teamStrengths;
    }


    /**
     * Constrain a team-strength value to the
     * standard 0-100 scale.
     */
    private function normaliseStrength(
        float $strength
    ): float {

        return round(
            max(
                0,
                min(
                    100,
                    $strength
                )
            ),
            2
        );
    }
}