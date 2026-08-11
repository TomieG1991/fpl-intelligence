<?php

class FixtureIntelligence
{
    public function calculateMatchup(
        float $homeTeamStrength,
        float $awayTeamStrength
    ): float {

        return $homeTeamStrength - $awayTeamStrength;
    }
}