<?php

class TeamPerformanceAdjusted
{
    /**
     * Calculate the expected result advantage based
     * on the relative strength of the two teams.
     *
     * Positive = our team is stronger
     * Negative = opponent is stronger
     */
    public function calculateStrengthDifference(
        float $teamStrength,
        float $opponentStrength
    ): float {

        return $teamStrength - $opponentStrength;
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

        $difference =
            $teamStrength - $opponentStrength;

        /*
         * Convert the strength difference into a
         * controlled 0-100 expectation.
         *
         * Every 100 points of strength difference
         * represents the full range.
         */
        $score =
            50 + ($difference * 0.5);

        return round(
            max(0, min(100, $score)),
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
            return 100.0;
        }

        if ($teamScore === $opponentScore) {
            return 50.0;
        }

        return 0.0;
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
            $resultScore - $expectedScore,
            2
        );
    }


    /**
     * Calculate a single match's opposition-adjusted
     * performance score.
     */
    public function calculateMatchPerformance(
        float $teamStrength,
        float $opponentStrength,
        int $teamScore,
        int $opponentScore
    ): array {

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
                round($teamStrength, 2),

            'opponent_strength' =>
                round($opponentStrength, 2),

            'strength_difference' =>
                round($strengthDifference, 2),

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

            if ((int) $fixture['finished'] !== 1) {
                continue;
            }

            $homeTeamId =
                (int) $fixture['home_team_id'];

            $awayTeamId =
                (int) $fixture['away_team_id'];


            if (
                $homeTeamId !== $teamId
                &&
                $awayTeamId !== $teamId
            ) {
                continue;
            }


            if (
                $fixture['home_score'] === null
                ||
                $fixture['away_score'] === null
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
                    $teamStrengths[$homeTeamId]['home'];

                $opponentStrength =
                    $teamStrengths[$awayTeamId]['away'];

                $teamScore =
                    $homeScore;

                $opponentScore =
                    $awayScore;

                $isHome = true;

            } else {

                $teamStrength =
                    $teamStrengths[$awayTeamId]['away'];

                $opponentStrength =
                    $teamStrengths[$homeTeamId]['home'];

                $teamScore =
                    $awayScore;

                $opponentScore =
                    $homeScore;

                $isHome = false;
            }


            $match =
                $this->calculateMatchPerformance(
                    $teamStrength,
                    $opponentStrength,
                    $teamScore,
                    $opponentScore
                );


            $matches[] = array_merge(
                [
                    'gameweek' =>
                        (int) $fixture['gameweek'],

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
         */
        usort(
            $matches,
            fn($a, $b) =>
                $a['gameweek']
                <=>
                $b['gameweek']
        );


        /*
         * Calculate the overall adjusted performance.
         */
        $averagePerformance = null;

        if (!empty($matches)) {

            $total =
                array_sum(
                    array_column(
                        $matches,
                        'result_score'
                    )
                );

            $averagePerformance =
                round(
                    $total / count($matches),
                    2
                );
        }


        /*
         * Calculate average performance delta.
         */
        $averageDelta = null;

        if (!empty($matches)) {

            $totalDelta =
                array_sum(
                    array_column(
                        $matches,
                        'performance_delta'
                    )
                );

            $averageDelta =
                round(
                    $totalDelta / count($matches),
                    2
                );
        }


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
}