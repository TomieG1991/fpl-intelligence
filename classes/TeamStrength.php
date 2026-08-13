<?php

class TeamStrength
{
    /**
     * Convert an FPL strength value into a
     * normalised 0-100 baseline.
     */
    public function calculateBaseline(
        int $strength,
        int $minimum,
        int $maximum
    ): float {

        /*
         * If every team has the same strength,
         * use a neutral baseline.
         */
        if ($maximum === $minimum) {
            return 50.00;
        }


        $baseline =
            (
                ($strength - $minimum)
                /
                ($maximum - $minimum)
            )
            * 100;


        return round(
            max(
                0,
                min(
                    100,
                    $baseline
                )
            ),
            2
        );
    }


    /**
     * Find the minimum and maximum values
     * for a supplied team strength field.
     */
    public function getRange(
        array $teams,
        string $field
    ): array {

        $values = [];


        foreach ($teams as $team) {

            if (
                !array_key_exists(
                    $field,
                    $team
                )
                ||
                $team[$field] === null
                ||
                !is_numeric(
                    $team[$field]
                )
            ) {

                continue;
            }


            $values[] =
                (int) $team[$field];
        }


        if (empty($values)) {

            throw new InvalidArgumentException(
                "No usable values found for team strength field: {$field}"
            );
        }


        return [

            'minimum' =>
                min($values),

            'maximum' =>
                max($values)
        ];
    }


    /**
     * Calculate overall team strength from
     * home and away baselines.
     */
    public function calculateOverall(
        float $homeBaseline,
        float $awayBaseline
    ): float {

        return round(
            (
                $homeBaseline
                +
                $awayBaseline
            )
            / 2,
            2
        );
    }


    /**
     * Calculate home, away and overall strength
     * baselines for all supplied teams.
     */
    public function calculateTeamStrengths(
        array $teams
    ): array {

        if (empty($teams)) {
            return [];
        }


        $homeRange =
            $this->getRange(
                $teams,
                'strength_overall_home'
            );


        $awayRange =
            $this->getRange(
                $teams,
                'strength_overall_away'
            );


        $results = [];


        foreach ($teams as $team) {

            /*
             * A complete strength model requires
             * identity plus usable home/away values.
             */
            if (
                !isset(
                    $team['id'],
                    $team['name']
                )
                ||
                !array_key_exists(
                    'strength_overall_home',
                    $team
                )
                ||
                !array_key_exists(
                    'strength_overall_away',
                    $team
                )
                ||
                $team['strength_overall_home'] === null
                ||
                $team['strength_overall_away'] === null
                ||
                !is_numeric(
                    $team['strength_overall_home']
                )
                ||
                !is_numeric(
                    $team['strength_overall_away']
                )
            ) {

                continue;
            }


            $homeBaseline =
                $this->calculateBaseline(
                    (int)
                        $team[
                            'strength_overall_home'
                        ],
                    $homeRange['minimum'],
                    $homeRange['maximum']
                );


            $awayBaseline =
                $this->calculateBaseline(
                    (int)
                        $team[
                            'strength_overall_away'
                        ],
                    $awayRange['minimum'],
                    $awayRange['maximum']
                );


            $overallBaseline =
                $this->calculateOverall(
                    $homeBaseline,
                    $awayBaseline
                );


            $teamId =
                (int) $team['id'];


            $results[$teamId] = [

                'id' =>
                    $teamId,

                'name' =>
                    $team['name'],

                'home' =>
                    $homeBaseline,

                'away' =>
                    $awayBaseline,

                'overall' =>
                    $overallBaseline
            ];
        }


        return $results;
    }
}