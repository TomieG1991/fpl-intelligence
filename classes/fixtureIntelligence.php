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
}