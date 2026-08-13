<?php

class OppositionAdjustedPerformance
{
    /**
     * Calculate expected performance against an opponent.
     *
     * Opponent strength is represented as a 0-100 rating.
     *
     * Stronger opponents produce a higher expected difficulty.
     */
    public function calculateExpectedPerformance(
        float $opponentStrength
    ): float {

        /*
         * Convert opponent strength into an expected
         * performance baseline.
         *
         * 100 strength = 25 expected performance
         * 50 strength  = 50 expected performance
         * 0 strength   = 75 expected performance
         *
         * This means beating a strong team is considered
         * more impressive than beating a weak team.
         */
        return round(
            75 - ($opponentStrength * 0.50),
            2
        );
    }


    /**
     * Convert a match result into an actual performance score.
     *
     * Win  = 100
     * Draw = 50
     * Loss = 0
     */
    public function calculateActualPerformance(
        string $result
    ): float {

        return match ($result) {

            'W' => 100.0,

            'D' => 50.0,

            'L' => 0.0,

            default => 50.0
        };
    }


    /**
     * Calculate the performance delta against the opponent.
     *
     * Positive = performed better than expected.
     * Negative = performed worse than expected.
     */
    public function calculateDelta(
        float $actualPerformance,
        float $expectedPerformance
    ): float {

        return round(
            $actualPerformance
            -
            $expectedPerformance,
            2
        );
    }


    /**
     * Analyse a team's completed fixtures while
     * accounting for opposition strength.
     */
    public function analyse(
        array $fixtures,
        array $teamStrengths,
        int $teamId
    ): array {

        $matches = [];

        foreach ($fixtures as $fixture) {

            /*
             * Only analyse completed fixtures.
             */
            if ((int) $fixture['finished'] !== 1) {
                continue;
            }


            $homeTeamId =
                (int) $fixture['home_team_id'];

            $awayTeamId =
                (int) $fixture['away_team_id'];


            /*
             * Ignore fixtures that don't involve
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
             * Ignore fixtures without scores.
             */
            if (
                $fixture['home_score'] === null
                ||
                $fixture['away_score'] === null
            ) {
                continue;
            }


            /*
             * Determine opponent and result.
             */
            if ($homeTeamId === $teamId) {

                $opponentId = $awayTeamId;

                $teamScore =
                    (int) $fixture['home_score'];

                $opponentScore =
                    (int) $fixture['away_score'];

                $opponentStrength =
                    (float) $teamStrengths[$opponentId]['away'];

                $venue = 'Home';

            } else {

                $opponentId = $homeTeamId;

                $teamScore =
                    (int) $fixture['away_score'];

                $opponentScore =
                    (int) $fixture['home_score'];

                $opponentStrength =
                    (float) $teamStrengths[$opponentId]['home'];

                $venue = 'Away';
            }


            /*
             * Determine result.
             */
            if ($teamScore > $opponentScore) {

                $result = 'W';

            } elseif ($teamScore === $opponentScore) {

                $result = 'D';

            } else {

                $result = 'L';
            }


            /*
             * Calculate actual and expected performance.
             */
            $actualPerformance =
                $this->calculateActualPerformance(
                    $result
                );

            $expectedPerformance =
                $this->calculateExpectedPerformance(
                    $opponentStrength
                );

            $delta =
                $this->calculateDelta(
                    $actualPerformance,
                    $expectedPerformance
                );


            /*
             * Store match intelligence.
             */
            $matches[] = [

                'gameweek' =>
                    (int) $fixture['gameweek'],

                'opponent_id' =>
                    $opponentId,

                'opponent_name' =>
                    $teamStrengths[$opponentId]['name']
                    ?? 'Unknown',

                'venue' =>
                    $venue,

                'result' =>
                    $result,

                'team_score' =>
                    $teamScore,

                'opponent_score' =>
                    $opponentScore,

                'opponent_strength' =>
                    $opponentStrength,

                'expected_performance' =>
                    $expectedPerformance,

                'actual_performance' =>
                    $actualPerformance,

                'performance_delta' =>
                    $delta
            ];
        }


        /*
         * Ensure chronological order.
         */
        usort(
            $matches,
            fn($a, $b) =>
                $a['gameweek'] <=> $b['gameweek']
        );


        /*
         * No completed matches.
         */
        if (empty($matches)) {

            return [

                'team_id' =>
                    $teamId,

                'played' =>
                    0,

                'average_performance' =>
                    null,

                'average_delta' =>
                    null,

                'matches' =>
                    []
            ];
        }


        /*
         * Calculate averages.
         */
        $performances =
            array_column(
                $matches,
                'actual_performance'
            );

        $deltas =
            array_column(
                $matches,
                'performance_delta'
            );


        return [

            'team_id' =>
                $teamId,

            'played' =>
                count($matches),

            'average_performance' =>
                round(
                    array_sum($performances)
                    /
                    count($performances),
                    2
                ),

            'average_delta' =>
                round(
                    array_sum($deltas)
                    /
                    count($deltas),
                    2
                ),

            'matches' =>
                $matches
        ];
    }
}