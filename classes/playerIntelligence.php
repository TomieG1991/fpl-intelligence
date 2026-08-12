<?php

class PlayerIntelligence
{
    public function analyseFixtureRun(
        array $player,
        array $fixtures,
        array $teamStrengths,
        FixtureIntelligence $fixtureIntelligence
    ): array {

        if (!isset($player['team_id'])) {
            throw new InvalidArgumentException(
                'Player team_id is required'
            );
        }


        $fixtureRun =
            $fixtureIntelligence->analyseFixtureRun(
                $fixtures,
                $teamStrengths,
                (int) $player['team_id']
            );


        return [

            'player_id' =>
                (int) $player['fpl_player_id'],

            'player_name' =>
                $player['first_name']
                . ' '
                . $player['second_name'],

            'team_id' =>
                (int) $player['team_id'],

            'fixtures' =>
                $fixtureRun,

            'rolling_averages' =>
                $fixtureIntelligence
                    ->calculateRollingAverages(
                        $fixtureRun
                    ),

            'best_run' =>
                $fixtureIntelligence->findBestRun(
                    $fixtureRun,
                    5
                ),

            'worst_run' =>
                $fixtureIntelligence->findWorstRun(
                    $fixtureRun,
                    5
                ),

            'trend' =>
                $fixtureIntelligence->calculateTrend(
                    $fixtureRun
                )
        ];
    }
}