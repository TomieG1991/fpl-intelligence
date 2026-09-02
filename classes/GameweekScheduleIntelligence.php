<?php

class GameweekScheduleIntelligence
{
    /**
     * Analyse fixture scheduling across requested teams
     * and gameweeks.
     *
     * Schedule types:
     *
     * 0 fixtures  = Blank
     * 1 fixture   = Normal
     * 2+ fixtures = Double
     *
     * Individual fixture rows are preserved so downstream
     * intelligence can reason about every fixture explicitly.
     */
    public function analyse(
        array $fixtures,
        array $teamIds,
        array $gameweeks
    ): array {

        /*
         * ========================================================
         * NORMALISE TEAM IDS
         * ========================================================
         */

        $normalisedTeamIds =
            [];


        foreach (
            $teamIds
            as $teamId
        ) {

            if (
                !is_numeric(
                    $teamId
                )
            ) {

                continue;
            }


            $teamId =
                (int) $teamId;


            if (
                $teamId <= 0
            ) {

                continue;
            }


            $normalisedTeamIds[
                $teamId
            ] =
                $teamId;
        }


        $normalisedTeamIds =
            array_values(
                $normalisedTeamIds
            );


        sort(
            $normalisedTeamIds,
            SORT_NUMERIC
        );


        /*
         * ========================================================
         * NORMALISE GAMEWEEKS
         * ========================================================
         */

        $normalisedGameweeks =
            [];


        foreach (
            $gameweeks
            as $gameweek
        ) {

            if (
                !is_numeric(
                    $gameweek
                )
            ) {

                continue;
            }


            $gameweek =
                (int) $gameweek;


            if (
                $gameweek <= 0
            ) {

                continue;
            }


            $normalisedGameweeks[
                $gameweek
            ] =
                $gameweek;
        }


        $normalisedGameweeks =
            array_values(
                $normalisedGameweeks
            );


        sort(
            $normalisedGameweeks,
            SORT_NUMERIC
        );


        /*
         * ========================================================
         * PREPARE SCHEDULE STRUCTURE
         * ========================================================
         */

        $resultGameweeks =
            [];


        foreach (
            $normalisedGameweeks
            as $gameweek
        ) {

            $resultGameweeks[
                $gameweek
            ] = [

                'gameweek' =>
                    $gameweek,

                'teams' =>
                    []
            ];


            foreach (
                $normalisedTeamIds
                as $teamId
            ) {

                $resultGameweeks[
                    $gameweek
                ][
                    'teams'
                ][
                    $teamId
                ] = [

                    'team_id' =>
                        $teamId,

                    'gameweek' =>
                        $gameweek,

                    'fixture_count' =>
                        0,

                    'schedule_type' =>
                        'Blank',

                    'fixtures' =>
                        []
                ];
            }
        }


        /*
         * ========================================================
         * ASSIGN FIXTURES
         * ========================================================
         */

        foreach (
            $fixtures
            as $fixture
        ) {

            if (
                !is_array(
                    $fixture
                )
            ) {

                continue;
            }


            $fixtureGameweek =
                isset(
                    $fixture[
                        'gameweek'
                    ]
                )
                &&
                is_numeric(
                    $fixture[
                        'gameweek'
                    ]
                )
                    ? (int) $fixture[
                        'gameweek'
                    ]
                    : 0;


            if (
                $fixtureGameweek <= 0
                ||
                !isset(
                    $resultGameweeks[
                        $fixtureGameweek
                    ]
                )
            ) {

                continue;
            }


            $homeTeamId =
                isset(
                    $fixture[
                        'home_team_id'
                    ]
                )
                &&
                is_numeric(
                    $fixture[
                        'home_team_id'
                    ]
                )
                    ? (int) $fixture[
                        'home_team_id'
                    ]
                    : 0;


            $awayTeamId =
                isset(
                    $fixture[
                        'away_team_id'
                    ]
                )
                &&
                is_numeric(
                    $fixture[
                        'away_team_id'
                    ]
                )
                    ? (int) $fixture[
                        'away_team_id'
                    ]
                    : 0;


            $fixtureTeamIds = [
                $homeTeamId,
                $awayTeamId
            ];


            foreach (
                $fixtureTeamIds
                as $fixtureTeamId
            ) {

                if (
                    $fixtureTeamId <= 0
                    ||
                    !isset(
                        $resultGameweeks[
                            $fixtureGameweek
                        ][
                            'teams'
                        ][
                            $fixtureTeamId
                        ]
                    )
                ) {

                    continue;
                }


                $resultGameweeks[
                    $fixtureGameweek
                ][
                    'teams'
                ][
                    $fixtureTeamId
                ][
                    'fixtures'
                ][] =
                    $fixture;
            }
        }


        /*
         * ========================================================
         * CLASSIFY SCHEDULES
         * ========================================================
         */

        foreach (
            $resultGameweeks
            as $gameweek =>
                $gameweekSchedule
        ) {

            foreach (
                $gameweekSchedule[
                    'teams'
                ]
                as $teamId =>
                    $teamSchedule
            ) {

                $fixtureCount =
                    count(
                        $teamSchedule[
                            'fixtures'
                        ]
                        ?? []
                    );


                if (
                    $fixtureCount === 0
                ) {

                    $scheduleType =
                        'Blank';

                } elseif (
                    $fixtureCount === 1
                ) {

                    $scheduleType =
                        'Normal';

                } else {

                    $scheduleType =
                        'Double';
                }


                /*
                 * Keep fixture ordering deterministic.
                 *
                 * Primary:
                 * kickoff time
                 *
                 * Secondary:
                 * local fixture ID
                 */

                $teamFixtures =
                    $teamSchedule[
                        'fixtures'
                    ]
                    ?? [];


                usort(
                    $teamFixtures,
                    function (
                        array $a,
                        array $b
                    ): int {

                        $kickoffA =
                            (string) (
                                $a[
                                    'kickoff_time'
                                ]
                                ?? ''
                            );


                        $kickoffB =
                            (string) (
                                $b[
                                    'kickoff_time'
                                ]
                                ?? ''
                            );


                        if (
                            $kickoffA !==
                            $kickoffB
                        ) {

                            return
                                strcmp(
                                    $kickoffA,
                                    $kickoffB
                                );
                        }


                        return
                            (
                                (int) (
                                    $a[
                                        'id'
                                    ]
                                    ?? 0
                                )
                            )
                            <=>
                            (
                                (int) (
                                    $b[
                                        'id'
                                    ]
                                    ?? 0
                                )
                            );
                    }
                );


                $resultGameweeks[
                    $gameweek
                ][
                    'teams'
                ][
                    $teamId
                ][
                    'fixture_count'
                ] =
                    $fixtureCount;


                $resultGameweeks[
                    $gameweek
                ][
                    'teams'
                ][
                    $teamId
                ][
                    'schedule_type'
                ] =
                    $scheduleType;


                $resultGameweeks[
                    $gameweek
                ][
                    'teams'
                ][
                    $teamId
                ][
                    'fixtures'
                ] =
                    $teamFixtures;
            }
        }


        return [

            'gameweeks' =>
                $resultGameweeks
        ];
    }
}