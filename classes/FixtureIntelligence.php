<?php

class FixtureIntelligence
{
    /**
     * Calculate the strength matchup between the player's
     * team and the opponent.
     *
     * Positive = favourable for the team
     * Negative = unfavourable for the team
     */
    public function calculateMatchup(
        float $teamStrength,
        float $opponentStrength
    ): float {

        return $teamStrength - $opponentStrength;
    }


    /**
     * Convert matchup into a 0-100 fixture score.
     *
     * 100 = extremely favourable
     * 50  = balanced
     * 0   = extremely unfavourable
     */
    public function calculateFixtureScore(
        float $teamStrength,
        float $opponentStrength
    ): float {

        return (
            $teamStrength
            + (100 - $opponentStrength)
        ) / 2;
    }


    /**
     * Convert fixture score into a 1-5 difficulty rating.
     */
    public function calculateDifficulty(
        float $fixtureScore
    ): int {

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
     * Convert difficulty number into a human-readable label.
     */
    public function getDifficultyLabel(
        int $difficulty
    ): string {

        return match ($difficulty) {

            1 => 'Excellent',
            2 => 'Good',
            3 => 'Average',
            4 => 'Difficult',
            5 => 'Very Difficult',

            default => 'Unknown'
        };
    }


    /**
     * Analyse all fixtures for a team.
     */
    public function analyseFixtureRun(
        array $fixtures,
        array $teamStrengths,
        int $teamId
    ): array {

        $results = [];

        foreach ($fixtures as $fixture) {

            /*
             * Ignore fixtures that don't involve this team.
             */
            if (
                (int) $fixture['home_team_id'] !== $teamId
                &&
                (int) $fixture['away_team_id'] !== $teamId
            ) {
                continue;
            }


            $homeTeam = $teamStrengths[
                $fixture['home_team_id']
            ];

            $awayTeam = $teamStrengths[
                $fixture['away_team_id']
            ];


            /*
             * Determine whether our team is home or away.
             */
            if (
                (int) $fixture['home_team_id'] === $teamId
            ) {

                $teamStrength = $homeTeam['home'];
                $opponentStrength = $awayTeam['away'];

                $isHome = true;

            } else {

                $teamStrength = $awayTeam['away'];
                $opponentStrength = $homeTeam['home'];

                $isHome = false;
            }


            /*
             * Core fixture calculations.
             */
            $matchup = $this->calculateMatchup(
                $teamStrength,
                $opponentStrength
            );

            $fixtureScore = $this->calculateFixtureScore(
                $teamStrength,
                $opponentStrength
            );

            $difficulty = $this->calculateDifficulty(
                $fixtureScore
            );

            $difficultyLabel = $this->getDifficultyLabel(
                $difficulty
            );


            /*
             * Store fixture intelligence.
             */
            $results[] = [

                'gameweek' => (int) $fixture['gameweek'],

                'home_team' => $homeTeam['name'],
                'away_team' => $awayTeam['name'],

                'is_home' => $isHome,

                'venue' => $isHome
                    ? 'Home'
                    : 'Away',

                'team_baseline' => $teamStrength,

                'opponent_baseline' => $opponentStrength,

                'home_baseline' => $homeTeam['home'],

                'away_baseline' => $awayTeam['away'],

                'matchup' => $matchup,

                'fixture_score' => $fixtureScore,

                'difficulty' => $difficulty,

                'difficulty_label' => $difficultyLabel
            ];
        }


        /*
         * Always return fixtures in gameweek order.
         */
        usort(
            $results,
            fn($a, $b) =>
                $a['gameweek'] <=> $b['gameweek']
        );


        return $results;
    }


    /**
     * Calculate the average fixture score
     * over the next X gameweeks.
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

        $fixtures = array_slice(
            $fixtures,
            0,
            $gameweeks
        );

        $scores = array_column(
            $fixtures,
            'fixture_score'
        );

        return round(
            array_sum($scores) / count($scores),
            2
        );
    }


    /**
     * Calculate rolling fixture averages.
     */
    public function calculateRollingAverages(
        array $fixtures
    ): array {

        return [

            'next_5' => $this->calculateRollingAverage(
                $fixtures,
                5
            ),

            'next_6' => $this->calculateRollingAverage(
                $fixtures,
                6
            ),

            'next_8' => $this->calculateRollingAverage(
                $fixtures,
                8
            ),

            'next_10' => $this->calculateRollingAverage(
                $fixtures,
                10
            )
        ];
    }


    /**
     * Find the strongest consecutive fixture run.
     */
    public function findBestRun(
        array $fixtures,
        int $runLength = 5
    ): ?array {

        if (
            count($fixtures) < $runLength
        ) {
            return null;
        }


        $bestRun = null;
        $bestAverage = -INF;


        for (
            $i = 0;
            $i <= count($fixtures) - $runLength;
            $i++
        ) {

            $run = array_slice(
                $fixtures,
                $i,
                $runLength
            );

            $average = $this->calculateRollingAverage(
                $run,
                $runLength
            );


            if ($average > $bestAverage) {

                $bestAverage = $average;

                $bestRun = [
                    'start_gameweek' =>
                        $run[0]['gameweek'],

                    'end_gameweek' =>
                        $run[count($run) - 1]['gameweek'],

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
     * Find the weakest consecutive fixture run.
     */
    public function findWorstRun(
        array $fixtures,
        int $runLength = 5
    ): ?array {

        if (
            count($fixtures) < $runLength
        ) {
            return null;
        }


        $worstRun = null;
        $worstAverage = INF;


        for (
            $i = 0;
            $i <= count($fixtures) - $runLength;
            $i++
        ) {

            $run = array_slice(
                $fixtures,
                $i,
                $runLength
            );

            $average = $this->calculateRollingAverage(
                $run,
                $runLength
            );


            if ($average < $worstAverage) {

                $worstAverage = $average;

                $worstRun = [
                    'start_gameweek' =>
                        $run[0]['gameweek'],

                    'end_gameweek' =>
                        $run[count($run) - 1]['gameweek'],

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
     * Determine whether the fixture run is improving,
     * declining or stable.
     */
    public function calculateTrend(
        array $fixtures
    ): string {

        if (count($fixtures) < 4) {
            return 'Insufficient Data';
        }


        $midpoint = (int) floor(
            count($fixtures) / 2
        );

        $firstHalf = array_slice(
            $fixtures,
            0,
            $midpoint
        );

        $secondHalf = array_slice(
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


        $difference =
            $secondAverage - $firstAverage;


        if ($difference >= 10) {
            return 'Improving';
        }

        if ($difference <= -10) {
            return 'Declining';
        }

        return 'Stable';
    }
}