<?php

class PlayerGameweekOutcomeService
{
    private object $fixtureHistoryRepository;


    public function __construct(
        object $fixtureHistoryRepository
    ) {

        $this->fixtureHistoryRepository =
            $fixtureHistoryRepository;
    }


    public function getByGameweekId(
        int $gameweekId
    ): array {

        if ($gameweekId <= 0) {

            throw new InvalidArgumentException(
                'Gameweek ID must be a positive integer.'
            );
        }


        $rows =
            $this->fixtureHistoryRepository
                ->getByGameweekId(
                    $gameweekId
                );


        if (empty($rows)) {

            return [];
        }


        $outcomes = [];


        foreach (
            $rows
            as $row
        ) {

            if (!is_array($row)) {

                continue;
            }


            $playerId =
                (int) (
                    $row[
                        'player_id'
                    ]
                    ?? 0
                );


            if ($playerId <= 0) {

                continue;
            }


            if (
                !isset(
                    $outcomes[
                        $playerId
                    ]
                )
            ) {

                $outcomes[
                    $playerId
                ] = [

                    'gameweek_id' =>
                        $gameweekId,

                    'player_id' =>
                        $playerId,

                    'fixture_count' =>
                        0,

                    'total_points' =>
                        0,

                    'minutes' =>
                        0,

                    'starts' =>
                        0,

                    'goals' =>
                        0,

                    'assists' =>
                        0,

                    'clean_sheets' =>
                        0,

                    'bonus' =>
                        0
                ];
            }


            $outcomes[
                $playerId
            ][
                'fixture_count'
            ]++;


            $outcomes[
                $playerId
            ][
                'total_points'
            ] +=
                (int) (
                    $row[
                        'total_points'
                    ]
                    ?? 0
                );


            $outcomes[
                $playerId
            ][
                'minutes'
            ] +=
                (int) (
                    $row[
                        'minutes'
                    ]
                    ?? 0
                );


            $outcomes[
                $playerId
            ][
                'starts'
            ] +=
                (int) (
                    $row[
                        'starts'
                    ]
                    ?? 0
                );


            $outcomes[
                $playerId
            ][
                'goals'
            ] +=
                (int) (
                    $row[
                        'goals'
                    ]
                    ?? 0
                );


            $outcomes[
                $playerId
            ][
                'assists'
            ] +=
                (int) (
                    $row[
                        'assists'
                    ]
                    ?? 0
                );


            $outcomes[
                $playerId
            ][
                'clean_sheets'
            ] +=
                (int) (
                    $row[
                        'clean_sheets'
                    ]
                    ?? 0
                );


            $outcomes[
                $playerId
            ][
                'bonus'
            ] +=
                (int) (
                    $row[
                        'bonus'
                    ]
                    ?? 0
                );
        }


        ksort(
            $outcomes,
            SORT_NUMERIC
        );


        return array_values(
            $outcomes
        );
    }
}