<?php

class TeamFixtureProfile
{
    /**
     * Convert a fixture rating into a human-readable label.
     */
    public function getFixtureLabel(
        ?float $fixtureRating
    ): ?string {

        if ($fixtureRating === null) {
            return null;
        }

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
     * For v1.0 the next 5 fixtures are the primary
     * short-term indicator.
     */
    public function calculateFixtureRating(
        array $rollingAverages
    ): ?float {

        if (
            !isset($rollingAverages['next_5'])
            ||
            $rollingAverages['next_5'] === null
        ) {
            return null;
        }

        return round(
            max(
                0,
                min(
                    100,
                    (float) $rollingAverages['next_5']
                )
            ),
            2
        );
    }


    /**
     * Build a complete fixture profile for a team.
     */
    public function buildProfile(
        int $teamId,
        string $teamName,
        array $fixtures,
        FixtureIntelligence $fixtureIntelligence
    ): array {

        /*
         * Analyse the team's fixtures.
         */
        $fixtureRun =
            $fixtureIntelligence->analyseFixtureRun(
                $fixtures,
                $this->getTeamStrengths($fixtures),
                $teamId
            );

        /*
         * NOTE:
         * This method is intentionally kept separate from the
         * normal build process below. The preferred method is
         * buildProfileFromAnalysis(), which receives the already
         * calculated fixture intelligence data.
         */

        return $this->buildProfileFromAnalysis(
            $teamId,
            $teamName,
            $fixtureRun,
            $fixtureIntelligence
        );
    }


    /**
     * Build a profile from already analysed fixture data.
     *
     * This is the preferred method when the application already
     * has FixtureIntelligence results available.
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
            $fixtureIntelligence->calculateRollingAverages(
                $fixtureRun
            );


        /*
         * Overall fixture rating.
         */
        $fixtureRating =
            $this->calculateFixtureRating(
                $rollingAverages
            );


        /*
         * Best and worst fixture runs.
         */
        $bestRun =
            $fixtureIntelligence->findBestRun(
                $fixtureRun,
                5
            );

        $worstRun =
            $fixtureIntelligence->findWorstRun(
                $fixtureRun,
                5
            );


        /*
         * Fixture trend.
         */
        $trend =
            $fixtureIntelligence->calculateTrend(
                $fixtureRun
            );


        /*
         * Return front-end friendly structure.
         */
        return [

            'team_id' => $teamId,

            'team_name' => $teamName,

            'fixture_rating' => $fixtureRating,

            'fixture_label' =>
                $this->getFixtureLabel(
                    $fixtureRating
                ),

            'rolling_averages' => $rollingAverages,

            'next_5' =>
                $rollingAverages['next_5'] ?? null,

            'next_6' =>
                $rollingAverages['next_6'] ?? null,

            'next_8' =>
                $rollingAverages['next_8'] ?? null,

            'next_10' =>
                $rollingAverages['next_10'] ?? null,

            'best_run' => $bestRun,

            'worst_run' => $worstRun,

            'trend' => $trend,

            'fixture_count' => count($fixtureRun),

            'fixtures' => $fixtureRun
        ];
    }


    /**
     * Return team strengths from fixture data.
     *
     * This helper exists only to support buildProfile()
     * when fixture data contains the required strength values.
     */
    private function getTeamStrengths(
        array $fixtures
    ): array {

        $teamStrengths = [];

        foreach ($fixtures as $fixture) {

            if (
                isset(
                    $fixture['home_team_id'],
                    $fixture['away_team_id']
                )
            ) {

                /*
                 * This method expects raw fixture data to already
                 * contain team strength information.
                 */
                if (
                    isset(
                        $fixture['home_strength'],
                        $fixture['away_strength']
                    )
                ) {

                    $homeId =
                        (int) $fixture['home_team_id'];

                    $awayId =
                        (int) $fixture['away_team_id'];


                    $teamStrengths[$homeId] = [

                        'name' =>
                            $fixture['home_team']
                            ?? 'Unknown',

                        'home' =>
                            (float) $fixture['home_strength'],

                        'away' =>
                            (float) (
                                $fixture['home_away_strength']
                                ?? $fixture['home_strength']
                            )
                    ];


                    $teamStrengths[$awayId] = [

                        'name' =>
                            $fixture['away_team']
                            ?? 'Unknown',

                        'home' =>
                            (float) (
                                $fixture['away_home_strength']
                                ?? $fixture['away_strength']
                            ),

                        'away' =>
                            (float) $fixture['away_strength']
                    ];
                }
            }
        }

        return $teamStrengths;
    }
}