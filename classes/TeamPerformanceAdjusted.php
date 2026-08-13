<?php

class TeamPerformanceAdjusted
{
    /**
     * Calculate the expected result advantage based
     * on the relative strength of the two teams.
     *
     * Positive = our team is stronger.
     * Negative = opponent is stronger.
     */
    public function calculateStrengthDifference(
        float $teamStrength,
        float $opponentStrength
    ): float {

        return round(
            $teamStrength
            -
            $opponentStrength,
            2
        );
    }


    /**
     * Convert strength difference into an expected
     * performance score from 0-100.
     *
     * 50 = evenly matched
     * >50 = our team expected to perform better
     * <50 = opponent expected to perform better
     */
    public function calculateExpectedScore(
        float $teamStrength,
        float $opponentStrength
    ): float {

        /*
         * Team strength models use the standard
         * 0-100 rating scale.
         */
        $teamStrength =
            max(
                0,
                min(
                    100,
                    $teamStrength
                )
            );


        $opponentStrength =
            max(
                0,
                min(
                    100,
                    $opponentStrength
                )
            );


        $difference =
            $teamStrength
            -
            $opponentStrength;


        /*
         * Every point of strength difference moves
         * expectation by half a point.
         *
         * Therefore:
         *
         * +100 difference = 100 expected score
         *    0 difference = 50 expected score
         * -100 difference = 0 expected score
         */
        $score =
            50
            +
            ($difference * 0.5);


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
     * Calculate the actual result score.
     *
     * Win  = 100
     * Draw = 50
     * Loss = 0
     */
    public function calculateResultScore(
        int $teamScore,
        int $opponentScore
    ): float {

        if ($teamScore > $opponentScore) {
            return 100.00;
        }


        if ($teamScore === $opponentScore) {
            return 50.00;
        }


        return 0.00;
    }


    /**
     * Calculate how much the actual result exceeded
     * or fell below expectations.
     */
    public function calculatePerformanceDelta(
        float $resultScore,
        float $expectedScore
    ): float {

        return round(
            $resultScore
            -
            $expectedScore,
            2
        );
    }


    /**
     * Calculate a single match's opposition-adjusted
     * performance model.
     */
    public function calculateMatchPerformance(
        float $teamStrength,
        float $opponentStrength,
        int $teamScore,
        int $opponentScore
    ): array {

        /*
         * Keep supplied strength values inside the
         * standard team-strength scale.
         */
        $teamStrength =
            max(
                0,
                min(
                    100,
                    $teamStrength
                )
            );


        $opponentStrength =
            max(
                0,
                min(
                    100,
                    $opponentStrength
                )
            );


        $strengthDifference =
            $this->calculateStrengthDifference(
                $teamStrength,
                $opponentStrength
            );


        $expectedScore =
            $this->calculateExpectedScore(
                $teamStrength,
                $opponentStrength
            );


        $resultScore =
            $this->calculateResultScore(
                $teamScore,
                $opponentScore
            );


        $performanceDelta =
            $this->calculatePerformanceDelta(
                $resultScore,
                $expectedScore
            );


        return [

            'team_strength' =>
                round(
                    $teamStrength,
                    2
                ),

            'opponent_strength' =>
                round(
                    $opponentStrength,
                    2
                ),

            'strength_difference' =>
                $strengthDifference,

            'expected_score' =>
                $expectedScore,

            'result_score' =>
                $resultScore,

            'performance_delta' =>
                $performanceDelta
        ];
    }


    /**
     * Analyse all completed fixtures for a team.
     */
    public function analyse(
        array $fixtures,
        array $teamStrengths,
        int $teamId
    ): array {

        $matches = [];


        foreach ($fixtures as $fixture) {

            /*
             * Ignore malformed or unfinished fixtures.
             */
            if (
                !isset(
                    $fixture['finished'],
                    $fixture['home_team_id'],
                    $fixture['away_team_id']
                )
                ||
                (int) $fixture['finished'] !== 1
            ) {

                continue;
            }


            $homeTeamId =
                (int) $fixture['home_team_id'];


            $awayTeamId =
                (int) $fixture['away_team_id'];


            /*
             * Ignore fixtures that do not involve
             * the selected team.
             */
            if (
                $homeTeamId !== $teamId
                &&
                $awayTeamId !== $teamId
            ) {

                continue;
            }


            /*
             * Ignore fixtures where scores are unavailable.
             */
            if (
                !array_key_exists(
                    'home_score',
                    $fixture
                )
                ||
                !array_key_exists(
                    'away_score',
                    $fixture
                )
                ||
                $fixture['home_score'] === null
                ||
                $fixture['away_score'] === null
            ) {

                continue;
            }


            /*
             * Both teams require strength models before
             * opposition adjustment can be calculated.
             */
            if (
                !isset(
                    $teamStrengths[$homeTeamId],
                    $teamStrengths[$awayTeamId]
                )
            ) {

                continue;
            }


            $homeStrengthModel =
                $teamStrengths[$homeTeamId];


            $awayStrengthModel =
                $teamStrengths[$awayTeamId];


            if (
                !isset(
                    $homeStrengthModel['home'],
                    $awayStrengthModel['away']
                )
            ) {

                continue;
            }


            $homeScore =
                (int) $fixture['home_score'];


            $awayScore =
                (int) $fixture['away_score'];


            /*
             * Determine home/away context.
             */
            if ($homeTeamId === $teamId) {

                $teamStrength =
                    (float)
                        $homeStrengthModel[
                            'home'
                        ];


                $opponentStrength =
                    (float)
                        $awayStrengthModel[
                            'away'
                        ];


                $teamScore =
                    $homeScore;


                $opponentScore =
                    $awayScore;


                $isHome =
                    true;

            } else {

                $teamStrength =
                    (float)
                        $awayStrengthModel[
                            'away'
                        ];


                $opponentStrength =
                    (float)
                        $homeStrengthModel[
                            'home'
                        ];


                $teamScore =
                    $awayScore;


                $opponentScore =
                    $homeScore;


                $isHome =
                    false;
            }


            $match =
                $this->calculateMatchPerformance(
                    $teamStrength,
                    $opponentStrength,
                    $teamScore,
                    $opponentScore
                );


            $matches[] =
                array_merge(
                    [

                        'gameweek' =>
                            isset($fixture['gameweek'])
                            &&
                            $fixture['gameweek'] !== null
                                ? (int)
                                    $fixture['gameweek']
                                : null,

                        'home_team_id' =>
                            $homeTeamId,

                        'away_team_id' =>
                            $awayTeamId,

                        'team_score' =>
                            $teamScore,

                        'opponent_score' =>
                            $opponentScore,

                        'is_home' =>
                            $isHome
                    ],
                    $match
                );
        }


        /*
         * Keep chronological order.
         *
         * Null gameweeks are placed last.
         */
        usort(
            $matches,
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


        /*
         * Calculate average actual-result performance.
         */
        $averagePerformance =
            $this->calculateAverage(
                $matches,
                'result_score'
            );


        /*
         * Calculate average performance against expectation.
         */
        $averageDelta =
            $this->calculateAverage(
                $matches,
                'performance_delta'
            );


        return [

            'team_id' =>
                $teamId,

            'played' =>
                count($matches),

            'average_performance' =>
                $averagePerformance,

            'average_delta' =>
                $averageDelta,

            'matches' =>
                $matches
        ];
    }


    /**
     * Calculate the average value of a field
     * across analysed matches.
     */
    private function calculateAverage(
        array $matches,
        string $field
    ): ?float {

        if (empty($matches)) {
            return null;
        }


        $values = [];


        foreach ($matches as $match) {

            if (
                !array_key_exists(
                    $field,
                    $match
                )
                ||
                !is_numeric(
                    $match[$field]
                )
            ) {

                continue;
            }


            $values[] =
                (float) $match[$field];
        }


        if (empty($values)) {
            return null;
        }


        return round(
            array_sum($values)
            /
            count($values),
            2
        );
    }
}