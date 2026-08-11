<?php

class FixtureIntelligence
{
    public function calculateMatchup(
        float $homeTeamStrength,
        float $awayTeamStrength
    ): float {

        return $homeTeamStrength - $awayTeamStrength;
    }
    
    public function calculateDifficulty(
        float $matchup
    ): int {

        $difficulty = 3 - ($matchup / 50);

        return max(
            1,
            min(
                5,
                (int) round($difficulty)
            )
        );
    }
    
    public function analyseFixtureRun(
        array $fixtures,
        array $teamStrengths,
        int $teamId
    ): array {

        $results = [];

        foreach ($fixtures as $fixture) {

            $homeTeam = $teamStrengths[
                $fixture['home_team_id']
            ];

            $awayTeam = $teamStrengths[
                $fixture['away_team_id']
            ];

            if ($fixture['home_team_id'] === $teamId) {

                $teamStrength = $homeTeam['home'];
                $opponentStrength = $awayTeam['away'];

                $isHome = true;

            } else {

                $teamStrength = $awayTeam['away'];
                $opponentStrength = $homeTeam['home'];

                $isHome = false;
            }

            $matchup = $this->calculateMatchup(
                $teamStrength,
                $opponentStrength
            );

            $difficulty = $this->calculateDifficulty(
                $matchup
            );

            $results[] = [
                'gameweek' => $fixture['gameweek'],
                'home_team' => $homeTeam['name'],
                'away_team' => $awayTeam['name'],
                'home_baseline' => $homeTeam['home'],
                'away_baseline' => $awayTeam['away'],
                'is_home' => $isHome,
                'matchup' => $matchup,
                'difficulty' => $difficulty
            ];
        }

        return $results;
    }
}