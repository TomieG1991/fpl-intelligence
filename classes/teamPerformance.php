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
            'team_id' => $teamId,

            'played' => 0,
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,

            'points' => 0,

            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,

            'home_played' => 0,
            'home_wins' => 0,
            'home_draws' => 0,
            'home_losses' => 0,
            'home_points' => 0,

            'away_played' => 0,
            'away_wins' => 0,
            'away_draws' => 0,
            'away_losses' => 0,
            'away_points' => 0,

            'recent_form' => [],

            'home_goals_for' => 0,
            'home_goals_against' => 0,

            'away_goals_for' => 0,
            'away_goals_against' => 0
        ];


        /*
         * Analyse completed fixtures involving this team.
         */
        foreach ($fixtures as $fixture) {

            if ((int) $fixture['finished'] !== 1) {
                continue;
            }


            $homeTeamId = (int) $fixture['home_team_id'];
            $awayTeamId = (int) $fixture['away_team_id'];


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
                $fixture['home_score'] === null
                ||
                $fixture['away_score'] === null
            ) {
                continue;
            }


            $homeScore = (int) $fixture['home_score'];
            $awayScore = (int) $fixture['away_score'];


            $stats['played']++;


            /*
             * Determine whether our team was home or away.
             */
            if ($homeTeamId === $teamId) {

                $teamScore = $homeScore;
                $opponentScore = $awayScore;

                $stats['home_played']++;

                $stats['home_goals_for'] += $teamScore;
                $stats['home_goals_against'] += $opponentScore;

                if ($teamScore > $opponentScore) {

                    $result = 'W';

                    $stats['wins']++;
                    $stats['home_wins']++;

                    $stats['points'] += 3;
                    $stats['home_points'] += 3;

                } elseif ($teamScore === $opponentScore) {

                    $result = 'D';

                    $stats['draws']++;
                    $stats['home_draws']++;

                    $stats['points']++;
                    $stats['home_points']++;

                } else {

                    $result = 'L';

                    $stats['losses']++;
                    $stats['home_losses']++;
                }

            } else {

                $teamScore = $awayScore;
                $opponentScore = $homeScore;

                $stats['away_played']++;

                $stats['away_goals_for'] += $teamScore;
                $stats['away_goals_against'] += $opponentScore;

                if ($teamScore > $opponentScore) {

                    $result = 'W';

                    $stats['wins']++;
                    $stats['away_wins']++;

                    $stats['points'] += 3;
                    $stats['away_points'] += 3;

                } elseif ($teamScore === $opponentScore) {

                    $result = 'D';

                    $stats['draws']++;
                    $stats['away_draws']++;

                    $stats['points']++;
                    $stats['away_points']++;

                } else {

                    $result = 'L';

                    $stats['losses']++;
                    $stats['away_losses']++;
                }
            }


            /*
             * Store result for recent-form calculations.
             */
            $stats['recent_form'][] = [
                'gameweek' => (int) $fixture['gameweek'],
                'result' => $result,
                'goals_for' => $teamScore,
                'goals_against' => $opponentScore
            ];
        }


        /*
         * Calculate goal difference.
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
         */
        usort(
            $stats['recent_form'],
            fn($a, $b) =>
                $a['gameweek'] <=> $b['gameweek']
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

        if (empty($performance['recent_form'])) {
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

        if ($performance['played'] === 0) {
            return null;
        }

        return round(
            $performance['points']
            / $performance['played'],
            2
        );
    }


    /**
     * Calculate goals scored per game.
     */
    public function calculateGoalsPerGame(
        array $performance
    ): ?float {

        if ($performance['played'] === 0) {
            return null;
        }

        return round(
            $performance['goals_for']
            / $performance['played'],
            2
        );
    }


    /**
     * Calculate goals conceded per game.
     */
    public function calculateGoalsAgainstPerGame(
        array $performance
    ): ?float {

        if ($performance['played'] === 0) {
            return null;
        }

        return round(
            $performance['goals_against']
            / $performance['played'],
            2
        );
    }
    
    /**
     * Calculate points performance as a 0-100 percentage.
     */
    public function calculatePointsRating(
        array $performance
    ): ?float {

        if ($performance['played'] === 0) {
            return null;
        }

        $maximumPoints =
            $performance['played'] * 3;

        return round(
            (
                $performance['points']
                / $maximumPoints
            ) * 100,
            2
        );
    }


    /**
     * Calculate goal difference rating.
     *
     * Uses goal difference per game and converts
     * it into a 0-100 score.
     *
     * +2 GD/game = 100
     *  0 GD/game = 50
     * -2 GD/game = 0
     */
    public function calculateGoalDifferenceRating(
        array $performance
    ): ?float {

        if ($performance['played'] === 0) {
            return null;
        }

        $goalDifferencePerGame =
            $performance['goal_difference']
            / $performance['played'];

        $rating =
            50
            + (
                $goalDifferencePerGame
                * 25
            );

        return round(
            max(0, min(100, $rating)),
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

        if ($performance['played'] === 0) {
            return null;
        }

        $goalsPerGame =
            $performance['goals_for']
            / $performance['played'];

        $rating =
            ($goalsPerGame / 3) * 100;

        return round(
            max(0, min(100, $rating)),
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

        if ($performance['played'] === 0) {
            return null;
        }

        $goalsAgainstPerGame =
            $performance['goals_against']
            / $performance['played'];

        $rating =
            100
            - (
                ($goalsAgainstPerGame / 3)
                * 100
            );

        return round(
            max(0, min(100, $rating)),
            2
        );
    }


    /**
     * Calculate the combined performance rating.
     */
    public function calculatePerformanceRating(
        array $performance
    ): ?float {

        if ($performance['played'] === 0) {
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
         * Points = 40%
         * Goal difference = 25%
         * Attack = 15%
         * Defence = 20%
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
            $rating,
            2
        );
    }
}