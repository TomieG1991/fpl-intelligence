<?php

class TeamStrength
{
    public function calculateBaseline(
        int $strength,
        int $minimum,
        int $maximum
    ): float {

        if ($maximum === $minimum) {
            return 50.0;
        }

        return (
            ($strength - $minimum)
            / ($maximum - $minimum)
        ) * 100;
    }

    public function getRange(array $teams, string $field): array
    {
        $values = array_column($teams, $field);

        $values = array_filter(
            $values,
            fn($value) => $value !== null
        );

        return [
            'minimum' => min($values),
            'maximum' => max($values)
        ];
    }
    
    public function calculateOverall(
        float $homeBaseline,
        float $awayBaseline
    ): float {

        return (
            $homeBaseline + $awayBaseline
        ) / 2;
    }
    
    public function calculateTeamStrengths(array $teams): array
    {
        $homeRange = $this->getRange(
            $teams,
            'strength_overall_home'
        );

        $awayRange = $this->getRange(
            $teams,
            'strength_overall_away'
        );

        $results = [];

        foreach ($teams as $team) {

            $homeBaseline = $this->calculateBaseline(
                (int) $team['strength_overall_home'],
                $homeRange['minimum'],
                $homeRange['maximum']
            );

            $awayBaseline = $this->calculateBaseline(
                (int) $team['strength_overall_away'],
                $awayRange['minimum'],
                $awayRange['maximum']
            );

            $overallBaseline = $this->calculateOverall(
                $homeBaseline,
                $awayBaseline
            );

            $results[$team['id']] = [
                'id' => $team['id'],
                'name' => $team['name'],
                'home' => $homeBaseline,
                'away' => $awayBaseline,
                'overall' => $overallBaseline
            ];
        }

        return $results;
    }
}