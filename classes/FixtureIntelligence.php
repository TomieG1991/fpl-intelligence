<?php

class FixtureIntelligence
{
    /**
     * Calculate the strength matchup between
     * a team and its opponent.
     *
     * Positive = favourable.
     * Negative = unfavourable.
     */
    public function calculateMatchup(
        float $teamStrength,
        float $opponentStrength
    ): float {

        $teamStrength =
            $this->normaliseStrength(
                $teamStrength
            );


        $opponentStrength =
            $this->normaliseStrength(
                $opponentStrength
            );


        return round(
            $teamStrength
            -
            $opponentStrength,
            2
        );
    }


    /**
     * Convert team/opponent strength into
     * a 0-100 fixture score.
     *
     * 100 = extremely favourable.
     * 50  = balanced.
     * 0   = extremely unfavourable.
     */
    public function calculateFixtureScore(
        float $teamStrength,
        float $opponentStrength
    ): float {

        $teamStrength =
            $this->normaliseStrength(
                $teamStrength
            );


        $opponentStrength =
            $this->normaliseStrength(
                $opponentStrength
            );


        $score =
            (
                $teamStrength
                +
                (
                    100
                    -
                    $opponentStrength
                )
            )
            / 2;


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
     * Calculate fixture opportunity based purely
     * on opposition strength.
     *
     * This deliberately excludes the selected team's
     * own strength so that player/team strength is not
     * counted twice in overall intelligence.
     *
     * Weak opposition = high opportunity.
     * Strong opposition = low opportunity.
     */
    public function calculateOpportunityScore(
        float $opponentStrength
    ): float {

        $opponentStrength =
            $this->normaliseStrength(
                $opponentStrength
            );


        return round(
            max(
                0,
                min(
                    100,
                    100
                    -
                    $opponentStrength
                )
            ),
            2
        );
    }


    /**
     * Convert fixture score into a
     * 1-5 difficulty rating.
     */
    public function calculateDifficulty(
        float $fixtureScore
    ): int {

        $fixtureScore =
            max(
                0,
                min(
                    100,
                    $fixtureScore
                )
            );


        if ($fixtureScore >= 85) {
            return 1;
        }


        if ($fixtureScore >= 70) {
            return 2;
        }


        if ($fixtureScore >= 55) {
            return 3;
        }


        if ($fixtureScore >= 40) {
            return 4;
        }


        return 5;
    }


    /**
     * Convert difficulty number into
     * a human-readable label.
     */
    public function getDifficultyLabel(
        int $difficulty
    ): string {

        return match ($difficulty) {

            1 =>
                'Excellent',

            2 =>
                'Good',

            3 =>
                'Average',

            4 =>
                'Difficult',

            5 =>
                'Very Difficult',

            default =>
                'Unknown'
        };
    }


    /**
     * Analyse all fixtures for one team.
     */
    public function analyseFixtureRun(
        array $fixtures,
        array $teamStrengths,
        int $teamId
    ): array {

        $results = [];


        foreach ($fixtures as $fixture) {

            /*
             * Fixture identity must be available.
             */
            if (
                !isset(
                    $fixture['home_team_id'],
                    $fixture['away_team_id']
                )
            ) {

                continue;
            }


            $homeTeamId =
                (int)
                    $fixture[
                        'home_team_id'
                    ];


            $awayTeamId =
                (int)
                    $fixture[
                        'away_team_id'
                    ];


            /*
             * Ignore fixtures that do not
             * involve the requested team.
             */
            if (
                $homeTeamId !== $teamId
                &&
                $awayTeamId !== $teamId
            ) {

                continue;
            }


            /*
             * Both team-strength models are required.
             */
            if (
                !isset(
                    $teamStrengths[$homeTeamId],
                    $teamStrengths[$awayTeamId]
                )
            ) {

                continue;
            }


            $homeTeam =
                $teamStrengths[
                    $homeTeamId
                ];


            $awayTeam =
                $teamStrengths[
                    $awayTeamId
                ];


            /*
             * Venue-specific strength data is required.
             */
            if (
                !isset(
                    $homeTeam['home'],
                    $awayTeam['away']
                )
                ||
                !is_numeric(
                    $homeTeam['home']
                )
                ||
                !is_numeric(
                    $awayTeam['away']
                )
            ) {

                continue;
            }


            /*
             * Determine whether the selected team
             * is home or away.
             */
            if ($homeTeamId === $teamId) {

                $teamStrength =
                    $this->normaliseStrength(
                        (float)
                            $homeTeam['home']
                    );


                $opponentStrength =
                    $this->normaliseStrength(
                        (float)
                            $awayTeam['away']
                    );


                $isHome =
                    true;

            } else {

                $teamStrength =
                    $this->normaliseStrength(
                        (float)
                            $awayTeam['away']
                    );


                $opponentStrength =
                    $this->normaliseStrength(
                        (float)
                            $homeTeam['home']
                    );


                $isHome =
                    false;
            }


            /*
             * Core fixture calculations.
             */
            $matchup =
                $this->calculateMatchup(
                    $teamStrength,
                    $opponentStrength
                );


            $fixtureScore =
                $this->calculateFixtureScore(
                    $teamStrength,
                    $opponentStrength
                );
                
            $opportunityScore =
                $this->calculateOpportunityScore(
                    $opponentStrength
                );


            $difficulty =
                $this->calculateDifficulty(
                    $fixtureScore
                );


            $difficultyLabel =
                $this->getDifficultyLabel(
                    $difficulty
                );


            $results[] = [

                'gameweek' =>
                    isset($fixture['gameweek'])
                    &&
                    $fixture['gameweek'] !== null
                        ? (int)
                            $fixture[
                                'gameweek'
                            ]
                        : null,

                'home_team' =>
                    $homeTeam['name']
                    ?? 'Unknown',

                'away_team' =>
                    $awayTeam['name']
                    ?? 'Unknown',

                'is_home' =>
                    $isHome,

                'venue' =>
                    $isHome
                        ? 'Home'
                        : 'Away',

                'team_baseline' =>
                    $isHome
                        ? $homeTeam['home']
                        : $awayTeam['away'],

                'opponent_baseline' =>
                    $isHome
                        ? $awayTeam['away']
                        : $homeTeam['home'],

                'home_baseline' =>
                    $homeTeam['home'],

                'away_baseline' =>
                    $awayTeam['away'],

                'matchup' =>
                    $matchup,

                'fixture_score' =>
                    $fixtureScore,
                    
                'opportunity_score' =>
                    $opportunityScore,

                'difficulty' =>
                    $difficulty,

                'difficulty_label' =>
                    $difficultyLabel
            ];
        }


        /*
         * Always return fixtures in chronological order.
         * Null gameweeks are placed last.
         */
        usort(
            $results,
            function (
                array $a,
                array $b
            ): int {

                $gameweekA =
                    $a['gameweek'];

                $gameweekB =
                    $b['gameweek'];


                if (
                    $gameweekA === null
                    &&
                    $gameweekB === null
                ) {

                    return 0;
                }


                if ($gameweekA === null) {
                    return 1;
                }


                if ($gameweekB === null) {
                    return -1;
                }


                return
                    $gameweekA
                    <=>
                    $gameweekB;
            }
        );


        return $results;
    }


    /**
     * Calculate the average fixture score
     * over the next X fixtures.
     */
    public function calculateRollingAverage(
        array $fixtures,
        int $gameweeks
    ): ?float {

        if (
            empty($fixtures)
            ||
            $gameweeks <= 0
            ||
            count($fixtures) < $gameweeks
        ) {

            return null;
        }


        $selectedFixtures =
            array_slice(
                $fixtures,
                0,
                $gameweeks
            );


        $scores = [];


        foreach (
            $selectedFixtures
            as $fixture
        ) {

            if (
                !isset(
                    $fixture['fixture_score']
                )
                ||
                !is_numeric(
                    $fixture[
                        'fixture_score'
                    ]
                )
            ) {

                return null;
            }


            $scores[] =
                max(
                    0,
                    min(
                        100,
                        (float)
                            $fixture[
                                'fixture_score'
                            ]
                    )
                );
        }


        if (empty($scores)) {
            return null;
        }


        return round(
            array_sum($scores)
            /
            count($scores),
            2
        );
    }
    
    /**
     * Calculate the average opposition opportunity
     * over the next X fixtures.
     */
    public function calculateOpportunityAverage(
        array $fixtures,
        int $fixtureCount
    ): ?float {

        if (
            empty($fixtures)
            ||
            $fixtureCount <= 0
            ||
            count($fixtures) < $fixtureCount
        ) {

            return null;
        }


        $selectedFixtures =
            array_slice(
                $fixtures,
                0,
                $fixtureCount
            );


        $scores =
            [];


        foreach (
            $selectedFixtures
            as $fixture
        ) {

            if (
                !isset(
                    $fixture['opportunity_score']
                )
                ||
                !is_numeric(
                    $fixture[
                        'opportunity_score'
                    ]
                )
            ) {

                return null;
            }


            $scores[] =
                max(
                    0,
                    min(
                        100,
                        (float)
                            $fixture[
                                'opportunity_score'
                            ]
                    )
                );
        }


        return round(
            array_sum($scores)
            /
            count($scores),
            2
        );
    }

    /**
     * Calculate standard player-facing
     * fixture opportunity averages.
     */
    public function calculateOpportunityAverages(
        array $fixtures
    ): array {

        return [

            'next_5' =>
                $this->calculateOpportunityAverage(
                    $fixtures,
                    5
                ),

            'next_6' =>
                $this->calculateOpportunityAverage(
                    $fixtures,
                    6
                ),

            'next_8' =>
                $this->calculateOpportunityAverage(
                    $fixtures,
                    8
                ),

            'next_10' =>
                $this->calculateOpportunityAverage(
                    $fixtures,
                    10
                )
        ];
    }


    /**
     * Calculate standard rolling
     * fixture averages.
     */
    public function calculateRollingAverages(
        array $fixtures
    ): array {

        return [

            'next_5' =>
                $this->calculateRollingAverage(
                    $fixtures,
                    5
                ),

            'next_6' =>
                $this->calculateRollingAverage(
                    $fixtures,
                    6
                ),

            'next_8' =>
                $this->calculateRollingAverage(
                    $fixtures,
                    8
                ),

            'next_10' =>
                $this->calculateRollingAverage(
                    $fixtures,
                    10
                )
        ];
    }


    /**
     * Find the strongest consecutive
     * fixture run.
     */
    public function findBestRun(
        array $fixtures,
        int $runLength = 5
    ): ?array {

        if (
            $runLength <= 0
            ||
            count($fixtures) < $runLength
        ) {

            return null;
        }


        $bestRun =
            null;


        $bestAverage =
            -INF;


        $maximumStart =
            count($fixtures)
            -
            $runLength;


        for (
            $i = 0;
            $i <= $maximumStart;
            $i++
        ) {

            $run =
                array_slice(
                    $fixtures,
                    $i,
                    $runLength
                );


            $average =
                $this->calculateRollingAverage(
                    $run,
                    $runLength
                );


            if ($average === null) {
                continue;
            }


            if ($average > $bestAverage) {

                $bestAverage =
                    $average;


                $bestRun = [

                    'start_gameweek' =>
                        $run[0]['gameweek']
                        ?? null,

                    'end_gameweek' =>
                        $run[
                            count($run) - 1
                        ]['gameweek']
                        ?? null,

                    'average_score' =>
                        $average,

                    'fixtures' =>
                        $run
                ];
            }
        }


        return $bestRun;
    }


    /**
     * Find the weakest consecutive
     * fixture run.
     */
    public function findWorstRun(
        array $fixtures,
        int $runLength = 5
    ): ?array {

        if (
            $runLength <= 0
            ||
            count($fixtures) < $runLength
        ) {

            return null;
        }


        $worstRun =
            null;


        $worstAverage =
            INF;


        $maximumStart =
            count($fixtures)
            -
            $runLength;


        for (
            $i = 0;
            $i <= $maximumStart;
            $i++
        ) {

            $run =
                array_slice(
                    $fixtures,
                    $i,
                    $runLength
                );


            $average =
                $this->calculateRollingAverage(
                    $run,
                    $runLength
                );


            if ($average === null) {
                continue;
            }


            if ($average < $worstAverage) {

                $worstAverage =
                    $average;


                $worstRun = [

                    'start_gameweek' =>
                        $run[0]['gameweek']
                        ?? null,

                    'end_gameweek' =>
                        $run[
                            count($run) - 1
                        ]['gameweek']
                        ?? null,

                    'average_score' =>
                        $average,

                    'fixtures' =>
                        $run
                ];
            }
        }


        return $worstRun;
    }


    /**
     * Determine whether the fixture run
     * is improving, declining or stable.
     */
    public function calculateTrend(
        array $fixtures
    ): string {

        if (count($fixtures) < 4) {
            return 'Insufficient Data';
        }


        $midpoint =
            (int) floor(
                count($fixtures)
                /
                2
            );


        $firstHalf =
            array_slice(
                $fixtures,
                0,
                $midpoint
            );


        $secondHalf =
            array_slice(
                $fixtures,
                $midpoint
            );


        $firstAverage =
            $this->calculateRollingAverage(
                $firstHalf,
                count($firstHalf)
            );


        $secondAverage =
            $this->calculateRollingAverage(
                $secondHalf,
                count($secondHalf)
            );


        if (
            $firstAverage === null
            ||
            $secondAverage === null
        ) {

            return 'Insufficient Data';
        }


        $difference =
            $secondAverage
            -
            $firstAverage;


        if ($difference >= 10) {
            return 'Improving';
        }


        if ($difference <= -10) {
            return 'Declining';
        }


        return 'Stable';
    }


    /**
     * Keep strength ratings inside
     * the standard 0-100 scale.
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