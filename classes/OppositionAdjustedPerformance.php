<?php

class OppositionAdjustedPerformance
{
    /**
     * Calculate expected performance against an opponent.
     *
     * Opponent strength is represented as a 0-100 rating.
     */
    public function calculateExpectedPerformance(
        float $opponentStrength
    ): float {

        $opponentStrength =
            max(
                0,
                min(
                    100,
                    $opponentStrength
                )
            );


        /*
         * 100 strength = 25 expected performance
         *  50 strength = 50 expected performance
         *   0 strength = 75 expected performance
         */
        $expected =
            75
            -
            ($opponentStrength * 0.50);


        return round(
            max(
                0,
                min(
                    100,
                    $expected
                )
            ),
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

            'W' =>
                100.00,

            'D' =>
                50.00,

            'L' =>
                0.00,

            default =>
                throw new InvalidArgumentException(
                    "Invalid match result: {$result}"
                )
        };
    }


    /**
     * Calculate the performance delta against expectation.
     *
     * Positive = better than expected.
     * Negative = worse than expected.
     */
    public function calculateDelta(
        float $actualPerformance,
        float $expectedPerformance
    ): float {

        $actualPerformance =
            max(
                0,
                min(
                    100,
                    $actualPerformance
                )
            );


        $expectedPerformance =
            max(
                0,
                min(
                    100,
                    $expectedPerformance
                )
            );


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
             * Ignore fixtures without scores.
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
             * Determine opponent and venue.
             */
            if ($homeTeamId === $teamId) {

                $opponentId =
                    $awayTeamId;


                $teamScore =
                    (int) $fixture['home_score'];


                $opponentScore =
                    (int) $fixture['away_score'];


                if (
                    !isset(
                        $teamStrengths[$opponentId]['away']
                    )
                ) {

                    continue;
                }


                $opponentStrength =
                    (float)
                        $teamStrengths[
                            $opponentId
                        ]['away'];


                $venue =
                    'Home';

            } else {

                $opponentId =
                    $homeTeamId;


                $teamScore =
                    (int) $fixture['away_score'];


                $opponentScore =
                    (int) $fixture['home_score'];


                if (
                    !isset(
                        $teamStrengths[$opponentId]['home']
                    )
                ) {

                    continue;
                }


                $opponentStrength =
                    (float)
                        $teamStrengths[
                            $opponentId
                        ]['home'];


                $venue =
                    'Away';
            }


            /*
             * Determine result.
             */
            if ($teamScore > $opponentScore) {

                $result =
                    'W';

            } elseif ($teamScore === $opponentScore) {

                $result =
                    'D';

            } else {

                $result =
                    'L';
            }


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


            $matches[] = [

                'gameweek' =>
                    isset($fixture['gameweek'])
                    &&
                    $fixture['gameweek'] !== null
                        ? (int) $fixture['gameweek']
                        : null,

                'opponent_id' =>
                    $opponentId,

                'opponent_name' =>
                    $teamStrengths[
                        $opponentId
                    ]['name']
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
                    round(
                        max(
                            0,
                            min(
                                100,
                                $opponentStrength
                            )
                        ),
                        2
                    ),

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


        return [

            'team_id' =>
                $teamId,

            'played' =>
                count($matches),

            'average_performance' =>
                $this->calculateAverage(
                    $matches,
                    'actual_performance'
                ),

            'average_delta' =>
                $this->calculateAverage(
                    $matches,
                    'performance_delta'
                ),

            'matches' =>
                $matches
        ];
    }


    /**
     * Calculate the average value of a match field.
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