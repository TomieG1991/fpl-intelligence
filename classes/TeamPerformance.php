<?php

class TeamPerformance
{
    /**
     * Analyse a team's completed fixtures.
     *
     * Returns a consistent structure even when
     * no matches have been played yet.
     */
    public function analyse(
        array $fixtures,
        int $teamId
    ): array {

        $stats = [

            'team_id' =>
                $teamId,

            'played' =>
                0,

            'wins' =>
                0,

            'draws' =>
                0,

            'losses' =>
                0,

            'points' =>
                0,

            'goals_for' =>
                0,

            'goals_against' =>
                0,

            'goal_difference' =>
                0,

            'home_played' =>
                0,

            'home_wins' =>
                0,

            'home_draws' =>
                0,

            'home_losses' =>
                0,

            'home_points' =>
                0,

            'away_played' =>
                0,

            'away_wins' =>
                0,

            'away_draws' =>
                0,

            'away_losses' =>
                0,

            'away_points' =>
                0,

            'recent_form' =>
                [],

            'home_goals_for' =>
                0,

            'home_goals_against' =>
                0,

            'away_goals_for' =>
                0,

            'away_goals_against' =>
                0
        ];


        /*
         * Analyse completed fixtures involving this team.
         */
        foreach ($fixtures as $fixture) {

            /*
             * Ignore incomplete or malformed fixtures.
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
             * Ignore fixtures not involving the selected team.
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


            $homeScore =
                (int) $fixture['home_score'];

            $awayScore =
                (int) $fixture['away_score'];


            $stats['played']++;


            /*
             * Determine venue and scores from the
             * selected team's perspective.
             */
            if ($homeTeamId === $teamId) {

                $teamScore =
                    $homeScore;

                $opponentScore =
                    $awayScore;

                $venue =
                    'home';

                $stats['home_played']++;

                $stats['home_goals_for'] +=
                    $teamScore;

                $stats['home_goals_against'] +=
                    $opponentScore;

            } else {

                $teamScore =
                    $awayScore;

                $opponentScore =
                    $homeScore;

                $venue =
                    'away';

                $stats['away_played']++;

                $stats['away_goals_for'] +=
                    $teamScore;

                $stats['away_goals_against'] +=
                    $opponentScore;
            }


            /*
             * Determine result.
             */
            if ($teamScore > $opponentScore) {

                $result =
                    'W';

                $stats['wins']++;

                $stats['points'] +=
                    3;


                if ($venue === 'home') {

                    $stats['home_wins']++;

                    $stats['home_points'] +=
                        3;

                } else {

                    $stats['away_wins']++;

                    $stats['away_points'] +=
                        3;
                }

            } elseif ($teamScore === $opponentScore) {

                $result =
                    'D';

                $stats['draws']++;

                $stats['points']++;


                if ($venue === 'home') {

                    $stats['home_draws']++;

                    $stats['home_points']++;

                } else {

                    $stats['away_draws']++;

                    $stats['away_points']++;
                }

            } else {

                $result =
                    'L';

                $stats['losses']++;


                if ($venue === 'home') {

                    $stats['home_losses']++;

                } else {

                    $stats['away_losses']++;
                }
            }


            /*
             * Store result for recent-form calculations.
             */
            $stats['recent_form'][] = [

                'gameweek' =>
                    isset($fixture['gameweek'])
                    &&
                    $fixture['gameweek'] !== null
                        ? (int) $fixture['gameweek']
                        : null,

                'result' =>
                    $result,

                'goals_for' =>
                    $teamScore,

                'goals_against' =>
                    $opponentScore
            ];
        }


        /*
         * Calculate aggregate goals.
         */
        $stats['goals_for'] =
            $stats['home_goals_for']
            +
            $stats['away_goals_for'];


        $stats['goals_against'] =
            $stats['home_goals_against']
            +
            $stats['away_goals_against'];


        $stats['goal_difference'] =
            $stats['goals_for']
            -
            $stats['goals_against'];


        /*
         * Ensure recent form is chronological.
         *
         * Unscheduled/null-gameweek fixtures are placed last,
         * although completed fixtures would normally have a
         * gameweek assigned.
         */
        usort(
            $stats['recent_form'],
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


        return $stats;
    }


    /**
     * Return the last X results.
     */
    public function getRecentForm(
        array $performance,
        int $matches = 5
    ): array {

        if (
            $matches <= 0
            ||
            empty(
                $performance['recent_form']
            )
            ||
            !is_array(
                $performance['recent_form']
            )
        ) {

            return [];
        }


        return array_slice(
            $performance['recent_form'],
            -$matches
        );
    }


    /**
     * Calculate points per game.
     */
    public function calculatePointsPerGame(
        array $performance
    ): ?float {

        $played =
            (int) (
                $performance['played']
                ?? 0
            );


        if ($played <= 0) {
            return null;
        }


        return round(
            (
                (float) (
                    $performance['points']
                    ?? 0
                )
            )
            /
            $played,
            2
        );
    }


    /**
     * Calculate goals scored per game.
     */
    public function calculateGoalsPerGame(
        array $performance
    ): ?float {

        $played =
            (int) (
                $performance['played']
                ?? 0
            );


        if ($played <= 0) {
            return null;
        }


        return round(
            (
                (float) (
                    $performance['goals_for']
                    ?? 0
                )
            )
            /
            $played,
            2
        );
    }


    /**
     * Calculate goals conceded per game.
     */
    public function calculateGoalsAgainstPerGame(
        array $performance
    ): ?float {

        $played =
            (int) (
                $performance['played']
                ?? 0
            );


        if ($played <= 0) {
            return null;
        }


        return round(
            (
                (float) (
                    $performance['goals_against']
                    ?? 0
                )
            )
            /
            $played,
            2
        );
    }


    /**
     * Calculate points performance as a 0-100 percentage.
     */
    public function calculatePointsRating(
        array $performance
    ): ?float {

        $played =
            (int) (
                $performance['played']
                ?? 0
            );


        if ($played <= 0) {
            return null;
        }


        $maximumPoints =
            $played * 3;


        $points =
            (float) (
                $performance['points']
                ?? 0
            );


        $rating =
            ($points / $maximumPoints)
            * 100;


        return round(
            max(
                0,
                min(
                    100,
                    $rating
                )
            ),
            2
        );
    }


    /**
     * Calculate goal difference rating.
     *
     * +2 GD/game = 100
     *  0 GD/game = 50
     * -2 GD/game = 0
     */
    public function calculateGoalDifferenceRating(
        array $performance
    ): ?float {

        $played =
            (int) (
                $performance['played']
                ?? 0
            );


        if ($played <= 0) {
            return null;
        }


        $goalDifference =
            (float) (
                $performance['goal_difference']
                ?? 0
            );


        $goalDifferencePerGame =
            $goalDifference
            /
            $played;


        $rating =
            50
            +
            (
                $goalDifferencePerGame
                * 25
            );


        return round(
            max(
                0,
                min(
                    100,
                    $rating
                )
            ),
            2
        );
    }


    /**
     * Calculate attacking rating.
     *
     * 3 goals/game = 100
     * 1.5 goals/game = 50
     * 0 goals/game = 0
     */
    public function calculateAttackRating(
        array $performance
    ): ?float {

        $played =
            (int) (
                $performance['played']
                ?? 0
            );


        if ($played <= 0) {
            return null;
        }


        $goalsFor =
            (float) (
                $performance['goals_for']
                ?? 0
            );


        $goalsPerGame =
            $goalsFor
            /
            $played;


        $rating =
            ($goalsPerGame / 3)
            * 100;


        return round(
            max(
                0,
                min(
                    100,
                    $rating
                )
            ),
            2
        );
    }


    /**
     * Calculate defensive rating.
     *
     * 0 goals conceded/game = 100
     * 1.5 goals conceded/game = 50
     * 3 goals conceded/game = 0
     */
    public function calculateDefenceRating(
        array $performance
    ): ?float {

        $played =
            (int) (
                $performance['played']
                ?? 0
            );


        if ($played <= 0) {
            return null;
        }


        $goalsAgainst =
            (float) (
                $performance['goals_against']
                ?? 0
            );


        $goalsAgainstPerGame =
            $goalsAgainst
            /
            $played;


        $rating =
            100
            -
            (
                ($goalsAgainstPerGame / 3)
                * 100
            );


        return round(
            max(
                0,
                min(
                    100,
                    $rating
                )
            ),
            2
        );
    }


    /**
     * Calculate the combined performance rating.
     */
    public function calculatePerformanceRating(
        array $performance
    ): ?float {

        $played =
            (int) (
                $performance['played']
                ?? 0
            );


        if ($played <= 0) {
            return null;
        }


        $pointsRating =
            $this->calculatePointsRating(
                $performance
            );


        $goalDifferenceRating =
            $this->calculateGoalDifferenceRating(
                $performance
            );


        $attackRating =
            $this->calculateAttackRating(
                $performance
            );


        $defenceRating =
            $this->calculateDefenceRating(
                $performance
            );


        /*
         * Performance weighting.
         *
         * Points          = 40%
         * Goal difference = 25%
         * Attack          = 15%
         * Defence         = 20%
         */
        $rating =
            ($pointsRating * 0.40)
            +
            ($goalDifferenceRating * 0.25)
            +
            ($attackRating * 0.15)
            +
            ($defenceRating * 0.20);


        return round(
            max(
                0,
                min(
                    100,
                    $rating
                )
            ),
            2
        );
    }
}