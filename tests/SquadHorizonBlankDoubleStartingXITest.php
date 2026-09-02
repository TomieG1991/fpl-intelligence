<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Squad Horizon Blank & Double Starting XI Test<br>';

echo
    'v0.33.0 — Blank & Double Gameweek Intelligence<br>';

echo
    '============================================<br><br>';


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function blankDoubleStartingXICheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        $passed++;

        return;
    }


    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';

    $failed++;
}


function blankDoubleStartingXIHeading(
    string $title
): void {

    echo
        '============================================<br>';

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';

    echo
        '============================================<br>';
}


/*
 * ============================================================
 * CONTROLLED SQUAD
 * ============================================================
 *
 * Legal 15-player squad:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * The important competition is between:
 *
 * Player 8 — MID
 * Player 12 — MID
 *
 * Player 8:
 *
 * GW2 Normal  = 5.0 xP
 * GW3 Blank   = 0.0 xP
 * GW4 Double  = 8.0 xP
 *
 * Player 12:
 *
 * GW2 Normal  = 4.0 xP
 * GW3 Normal  = 4.0 xP
 * GW4 Normal  = 4.0 xP
 *
 * The other four midfielders are deliberately arranged so
 * exactly four midfield slots are attractive.
 *
 * Therefore:
 *
 * GW2 → Player 8 starts, Player 12 benched
 * GW3 → Player 8 benched, Player 12 starts
 * GW4 → Player 8 starts, Player 12 benched
 *
 * This proves schedule semantics influence selection through
 * projected points rather than through special-case XI rules.
 */

$squad =
    [];


for (
    $playerNumber = 1;
    $playerNumber <= 15;
    $playerNumber++
) {

    $position =
        match (true) {

            $playerNumber <= 2 =>
                'GK',

            $playerNumber <= 7 =>
                'DEF',

            $playerNumber <= 12 =>
                'MID',

            default =>
                'FWD'
        };


    $teamId =
        100
        +
        $playerNumber;


    /*
     * --------------------------------------------------------
     * CONTROLLED MIDFIELDER — PLAYER 8
     * --------------------------------------------------------
     */

    if (
        $playerNumber === 8
    ) {

        $squad[] = [

            'player_id' =>
                8,

            'name' =>
                'Schedule Player',

            'position' =>
                'MID',

            'team_id' =>
                $teamId,

            'gameweeks' => [

                2 => [

                    'gameweek' =>
                        2,

                    'projected_points' =>
                        5.0,

                    'team_id' =>
                        $teamId,

                    'opponent_team_id' =>
                        202,

                    'fixture_count' =>
                        1,

                    'schedule_type' =>
                        'Normal',

                    'fixtures' => [

                        [
                            'fixture_id' =>
                                2008,

                            'gameweek' =>
                                2,

                            'opponent_team_id' =>
                                202,

                            'is_home' =>
                                true
                        ]
                    ]
                ],

                3 => [

                    'gameweek' =>
                        3,

                    'projected_points' =>
                        0.0,

                    'team_id' =>
                        $teamId,

                    'opponent_team_id' =>
                        null,

                    'fixture_count' =>
                        0,

                    'schedule_type' =>
                        'Blank',

                    'fixtures' =>
                        []
                ],

                4 => [

                    'gameweek' =>
                        4,

                    'projected_points' =>
                        8.0,

                    'team_id' =>
                        $teamId,

                    'opponent_team_id' =>
                        null,

                    'fixture_count' =>
                        2,

                    'schedule_type' =>
                        'Double',

                    'fixtures' => [

                        [
                            'fixture_id' =>
                                4008,

                            'gameweek' =>
                                4,

                            'opponent_team_id' =>
                                203,

                            'is_home' =>
                                true
                        ],

                        [
                            'fixture_id' =>
                                4009,

                            'gameweek' =>
                                4,

                            'opponent_team_id' =>
                                204,

                            'is_home' =>
                                false
                        ]
                    ]
                ]
            ]
        ];


        continue;
    }


    /*
     * --------------------------------------------------------
     * CONTROLLED ALTERNATIVE MIDFIELDER — PLAYER 12
     * --------------------------------------------------------
     */

    if (
        $playerNumber === 12
    ) {

        $gameweeks =
            [];


        foreach (
            [2, 3, 4]
            as $gameweek
        ) {

            $gameweeks[
                $gameweek
            ] = [

                'gameweek' =>
                    $gameweek,

                'projected_points' =>
                    4.0,

                'team_id' =>
                    $teamId,

                'opponent_team_id' =>
                    250
                    +
                    $gameweek,

                'fixture_count' =>
                    1,

                'schedule_type' =>
                    'Normal',

                'fixtures' => [

                    [
                        'fixture_id' =>
                            12000
                            +
                            $gameweek,

                        'gameweek' =>
                            $gameweek,

                        'opponent_team_id' =>
                            250
                            +
                            $gameweek,

                        'is_home' =>
                            true
                    ]
                ]
            ];
        }


        $squad[] = [

            'player_id' =>
                12,

            'name' =>
                'Alternative Midfielder',

            'position' =>
                'MID',

            'team_id' =>
                $teamId,

            'gameweeks' =>
                $gameweeks
        ];


        continue;
    }


    /*
     * --------------------------------------------------------
     * ALL OTHER PLAYERS
     * --------------------------------------------------------
     */

    $projectedPoints =
        match ($playerNumber) {

            /*
             * Goalkeepers
             */
            1 =>
                5.0,

            2 =>
                2.0,

            /*
             * Defenders
             *
             * Three strong starters plus two weaker bench
             * defenders.
             */
            3 =>
                6.0,

            4 =>
                5.5,

            5 =>
                5.0,

            6 =>
                2.0,

            7 =>
                1.5,

            /*
             * Other midfielders.
             *
             * Players 9, 10 and 11 should always start.
             */
            9 =>
                7.0,

            10 =>
                6.5,

            11 =>
                6.0,

            /*
             * Forwards.
             *
             * All three are strong enough to form a 3-4-3.
             */
            13 =>
                6.0,

            14 =>
                5.5,

            15 =>
                5.0,

            default =>
                1.0
        };


    $gameweeks =
        [];


    foreach (
        [2, 3, 4]
        as $gameweek
    ) {

        $opponentTeamId =
            300
            +
            (
                $playerNumber
                *
                10
            )
            +
            $gameweek;


        $gameweeks[
            $gameweek
        ] = [

            'gameweek' =>
                $gameweek,

            'projected_points' =>
                $projectedPoints,

            'team_id' =>
                $teamId,

            'opponent_team_id' =>
                $opponentTeamId,

            'fixture_count' =>
                1,

            'schedule_type' =>
                'Normal',

            'fixtures' => [

                [
                    'fixture_id' =>
                        (
                            $playerNumber
                            *
                            1000
                        )
                        +
                        $gameweek,

                    'gameweek' =>
                        $gameweek,

                    'opponent_team_id' =>
                        $opponentTeamId,

                    'is_home' =>
                        true
                ]
            ]
        ];
    }


    $squad[] = [

        'player_id' =>
            $playerNumber,

        'name' =>
            'Player '
            . $playerNumber,

        'position' =>
            $position,

        'team_id' =>
            $teamId,

        'gameweeks' =>
            $gameweeks
    ];
}


/*
 * ============================================================
 * BUILD THREE-GAMEWEEK HORIZON
 * ============================================================
 */

$intelligence =
    new SquadHorizonIntelligence();


$result =
    $intelligence->buildHorizon(
        $squad,
        3
    );


$gameweeks =
    $result[
        'gameweeks'
    ]
    ?? [];


/*
 * ============================================================
 * GENERAL HORIZON CONTRACT
 * ============================================================
 */

blankDoubleStartingXIHeading(
    'Scenario A: Horizon Contract'
);


blankDoubleStartingXICheck(
    'Horizon exposes exactly three gameweeks',
    count(
        $gameweeks
    )
    ===
    3
);


blankDoubleStartingXICheck(
    'Horizon contains GW2',
    isset(
        $gameweeks[2]
    )
);


blankDoubleStartingXICheck(
    'Horizon contains GW3',
    isset(
        $gameweeks[3]
    )
);


blankDoubleStartingXICheck(
    'Horizon contains GW4',
    isset(
        $gameweeks[4]
    )
);


echo
    '<br>';


/*
 * ============================================================
 * HELPER FOR PLAYER LOOKUP
 * ============================================================
 */

$getPlayer =
    static function (
        array $players,
        int $playerId
    ): ?array {

        foreach (
            $players
            as $player
        ) {

            if (
                (int) (
                    $player[
                        'player_id'
                    ]
                    ?? 0
                )
                ===
                $playerId
            ) {

                return
                    $player;
            }
        }


        return
            null;
    };


$getPlayerIds =
    static function (
        array $players
    ): array {

        return
            array_map(
                static function (
                    array $player
                ): int {

                    return
                        (int) (
                            $player[
                                'player_id'
                            ]
                            ?? 0
                        );
                },
                $players
            );
    };


/*
 * ============================================================
 * SCENARIO B
 * GW2 — NORMAL GAMEWEEK
 * ============================================================
 */

blankDoubleStartingXIHeading(
    'Scenario B: GW2 Normal Selection'
);


$gw2 =
    $gameweeks[2]
    ?? [];


$gw2Players =
    $gw2[
        'players'
    ]
    ?? [];


$gw2StartingXI =
    $gw2[
        'starting_xi'
    ]
    ?? [];


$gw2Bench =
    $gw2[
        'bench'
    ]
    ?? [];


$gw2Player8 =
    $getPlayer(
        $gw2Players,
        8
    );


$gw2StartingIds =
    $getPlayerIds(
        $gw2StartingXI
    );


$gw2BenchIds =
    $getPlayerIds(
        $gw2Bench
    );


blankDoubleStartingXICheck(
    'GW2 exposes fifteen squad players',
    count(
        $gw2Players
    )
    ===
    15
);


blankDoubleStartingXICheck(
    'GW2 selects eleven starters',
    count(
        $gw2StartingXI
    )
    ===
    11
);


blankDoubleStartingXICheck(
    'GW2 has four bench players',
    count(
        $gw2Bench
    )
    ===
    4
);


blankDoubleStartingXICheck(
    'Player 8 preserves Normal schedule type in GW2',
    (
        $gw2Player8[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
);


blankDoubleStartingXICheck(
    'Player 8 preserves one fixture in GW2',
    (
        $gw2Player8[
            'fixture_count'
        ]
        ?? null
    )
    ===
    1
);


blankDoubleStartingXICheck(
    'Player 8 starts in normal GW2',
    in_array(
        8,
        $gw2StartingIds,
        true
    )
);


blankDoubleStartingXICheck(
    'Player 12 is benched behind Player 8 in GW2',
    in_array(
        12,
        $gw2BenchIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * GW3 — BLANK GAMEWEEK
 * ============================================================
 */

blankDoubleStartingXIHeading(
    'Scenario C: GW3 Blank Selection'
);


$gw3 =
    $gameweeks[3]
    ?? [];


$gw3Players =
    $gw3[
        'players'
    ]
    ?? [];


$gw3StartingXI =
    $gw3[
        'starting_xi'
    ]
    ?? [];


$gw3Bench =
    $gw3[
        'bench'
    ]
    ?? [];


$gw3Player8 =
    $getPlayer(
        $gw3Players,
        8
    );


$gw3StartingIds =
    $getPlayerIds(
        $gw3StartingXI
    );


$gw3BenchIds =
    $getPlayerIds(
        $gw3Bench
    );


blankDoubleStartingXICheck(
    'Player 8 preserves Blank schedule type in GW3',
    (
        $gw3Player8[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
);


blankDoubleStartingXICheck(
    'Player 8 preserves zero fixtures in GW3',
    (
        $gw3Player8[
            'fixture_count'
        ]
        ?? null
    )
    ===
    0
);


blankDoubleStartingXICheck(
    'Player 8 preserves empty fixture list in GW3',
    (
        $gw3Player8[
            'fixtures'
        ]
        ?? null
    )
    ===
    []
);


blankDoubleStartingXICheck(
    'Player 8 has zero projected points in blank GW3',
    (
        $gw3Player8[
            'projected_points'
        ]
        ?? null
    )
    ===
    0.0
);


blankDoubleStartingXICheck(
    'Blank Player 8 is removed from Starting XI in GW3',
    !in_array(
        8,
        $gw3StartingIds,
        true
    )
);


blankDoubleStartingXICheck(
    'Blank Player 8 moves to the bench in GW3',
    in_array(
        8,
        $gw3BenchIds,
        true
    )
);


blankDoubleStartingXICheck(
    'Available Player 12 replaces Player 8 in GW3 Starting XI',
    in_array(
        12,
        $gw3StartingIds,
        true
    )
);


blankDoubleStartingXICheck(
    'GW3 still selects a legal eleven-player Starting XI',
    count(
        $gw3StartingXI
    )
    ===
    11
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * GW4 — DOUBLE GAMEWEEK
 * ============================================================
 */

blankDoubleStartingXIHeading(
    'Scenario D: GW4 Double Selection'
);


$gw4 =
    $gameweeks[4]
    ?? [];


$gw4Players =
    $gw4[
        'players'
    ]
    ?? [];


$gw4StartingXI =
    $gw4[
        'starting_xi'
    ]
    ?? [];


$gw4Bench =
    $gw4[
        'bench'
    ]
    ?? [];


$gw4Player8 =
    $getPlayer(
        $gw4Players,
        8
    );


$gw4StartingIds =
    $getPlayerIds(
        $gw4StartingXI
    );


$gw4BenchIds =
    $getPlayerIds(
        $gw4Bench
    );


blankDoubleStartingXICheck(
    'Player 8 preserves Double schedule type in GW4',
    (
        $gw4Player8[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


blankDoubleStartingXICheck(
    'Player 8 preserves two fixtures in GW4',
    (
        $gw4Player8[
            'fixture_count'
        ]
        ?? null
    )
    ===
    2
);


blankDoubleStartingXICheck(
    'Player 8 preserves both DGW fixture rows in GW4',
    count(
        $gw4Player8[
            'fixtures'
        ]
        ?? []
    )
    ===
    2
);


blankDoubleStartingXICheck(
    'Player 8 preserves null aggregate opponent in DGW GW4',
    (
        $gw4Player8[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    null
);


blankDoubleStartingXICheck(
    'Player 8 has aggregated 8.0 projected points in DGW GW4',
    (
        $gw4Player8[
            'projected_points'
        ]
        ?? null
    )
    ===
    8.0
);


blankDoubleStartingXICheck(
    'DGW Player 8 returns to Starting XI in GW4',
    in_array(
        8,
        $gw4StartingIds,
        true
    )
);


blankDoubleStartingXICheck(
    'Player 12 returns to bench behind DGW Player 8 in GW4',
    in_array(
        12,
        $gw4BenchIds,
        true
    )
);


blankDoubleStartingXICheck(
    'GW4 still selects a legal eleven-player Starting XI',
    count(
        $gw4StartingXI
    )
    ===
    11
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E
 * CROSS-GAMEWEEK DECISION CONTRACT
 * ============================================================
 */

blankDoubleStartingXIHeading(
    'Scenario E: Cross-Gameweek Selection Behaviour'
);


blankDoubleStartingXICheck(
    'Player 8 selection sequence is start, bench, start',
    in_array(
        8,
        $gw2StartingIds,
        true
    )
    &&
    in_array(
        8,
        $gw3BenchIds,
        true
    )
    &&
    in_array(
        8,
        $gw4StartingIds,
        true
    )
);


blankDoubleStartingXICheck(
    'Player 12 selection sequence is bench, start, bench',
    in_array(
        12,
        $gw2BenchIds,
        true
    )
    &&
    in_array(
        12,
        $gw3StartingIds,
        true
    )
    &&
    in_array(
        12,
        $gw4BenchIds,
        true
    )
);


blankDoubleStartingXICheck(
    'Schedule Player projections follow Normal, Blank, Double sequence',
    (
        $gw2Player8[
            'projected_points'
        ]
        ?? null
    )
    ===
    5.0
    &&
    (
        $gw3Player8[
            'projected_points'
        ]
        ?? null
    )
    ===
    0.0
    &&
    (
        $gw4Player8[
            'projected_points'
        ]
        ?? null
    )
    ===
    8.0
);


blankDoubleStartingXICheck(
    'Schedule Player fixture counts follow 1, 0, 2 sequence',
    (
        $gw2Player8[
            'fixture_count'
        ]
        ?? null
    )
    ===
    1
    &&
    (
        $gw3Player8[
            'fixture_count'
        ]
        ?? null
    )
    ===
    0
    &&
    (
        $gw4Player8[
            'fixture_count'
        ]
        ?? null
    )
    ===
    2
);


blankDoubleStartingXICheck(
    'Schedule Player schedule types follow Normal, Blank, Double sequence',
    (
        $gw2Player8[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
    &&
    (
        $gw3Player8[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
    &&
    (
        $gw4Player8[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


echo
    '<br>';


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'TEST SUMMARY<br>';

echo
    '============================================<br>';


echo
    'Passed: '
    . $passed
    . '<br>';


echo
    'Failed: '
    . $failed
    . '<br><br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅';

} else {

    echo
        'RESULT: TESTS FAILED ❌';
}