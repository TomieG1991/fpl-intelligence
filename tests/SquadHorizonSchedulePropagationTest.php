<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Squad Horizon Schedule Propagation Test<br>';

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

function squadHorizonPropagationCheck(
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


function squadHorizonPropagationHeading(
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
 * CONTROLLED 15-PLAYER SQUAD
 * ============================================================
 *
 * The squad satisfies normal FPL positional structure:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * Every player has GW2, GW3 and GW4 projections.
 *
 * Player 1 is the controlled schedule player:
 *
 * GW2 = Normal
 * GW3 = Blank
 * GW4 = Double
 *
 * All other players use simple Normal rows so the model can
 * still construct legal Starting XIs throughout the horizon.
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
        $playerNumber;


    $basePoints =
        6.0
        -
        (
            $playerNumber
            *
            0.1
        );


    if (
        $playerNumber === 1
    ) {

        $gameweeks = [

            2 => [

                'gameweek' =>
                    2,

                'projected_points' =>
                    5.0,

                'team_id' =>
                    1,

                'opponent_team_id' =>
                    20,

                'fixture_count' =>
                    1,

                'schedule_type' =>
                    'Normal',

                'fixtures' => [

                    [
                        'fixture_id' =>
                            201,

                        'fpl_fixture_id' =>
                            1201,

                        'gameweek' =>
                            2,

                        'kickoff_time' =>
                            '2026-08-29 15:00:00',

                        'opponent_team_id' =>
                            20,

                        'opponent_name' =>
                            'Opponent 20',

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
                    1,

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
                    1,

                'opponent_team_id' =>
                    null,

                'fixture_count' =>
                    2,

                'schedule_type' =>
                    'Double',

                'fixtures' => [

                    [
                        'fixture_id' =>
                            401,

                        'fpl_fixture_id' =>
                            1401,

                        'gameweek' =>
                            4,

                        'kickoff_time' =>
                            '2026-09-12 12:30:00',

                        'opponent_team_id' =>
                            18,

                        'opponent_name' =>
                            'Opponent 18',

                        'is_home' =>
                            true
                    ],

                    [
                        'fixture_id' =>
                            402,

                        'fpl_fixture_id' =>
                            1402,

                        'gameweek' =>
                            4,

                        'kickoff_time' =>
                            '2026-09-15 19:45:00',

                        'opponent_team_id' =>
                            19,

                        'opponent_name' =>
                            'Opponent 19',

                        'is_home' =>
                            false
                    ]
                ]
            ]
        ];

    } else {

        $gameweeks =
            [];


        foreach (
            [2, 3, 4]
            as $gameweek
        ) {

            $opponentTeamId =
                100
                +
                $playerNumber
                +
                $gameweek;


            $fixtureId =
                (
                    $gameweek
                    *
                    1000
                )
                +
                $playerNumber;


            $gameweeks[
                $gameweek
            ] = [

                'gameweek' =>
                    $gameweek,

                'projected_points' =>
                    $basePoints,

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
                            $fixtureId,

                        'gameweek' =>
                            $gameweek,

                        'opponent_team_id' =>
                            $opponentTeamId,

                        'opponent_name' =>
                            'Opponent '
                            . $opponentTeamId,

                        'is_home' =>
                            true
                    ]
                ]
            ];
        }
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
 * BUILD HORIZON
 * ============================================================
 */

$model =
    new SquadHorizonIntelligence();


$result =
    $model->buildHorizon(
        $squad,
        3
    );


$gameweeks =
    $result[
        'gameweeks'
    ]
    ?? [];


/*
 * Helper to locate one player inside one gameweek result.
 */

$findPlayer =
    static function (
        array $gameweekRow,
        int $playerId
    ): ?array {

        $players =
            $gameweekRow[
                'players'
            ]
            ?? [];


        foreach (
            $players
            as $player
        ) {

            if (
                (
                    $player[
                        'player_id'
                    ]
                    ?? null
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


/*
 * ============================================================
 * SCENARIO A
 * HORIZON BASELINE
 * ============================================================
 */

squadHorizonPropagationHeading(
    'Scenario A: Horizon Baseline'
);


squadHorizonPropagationCheck(
    'Squad Horizon result is available',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


squadHorizonPropagationCheck(
    'Squad Horizon preserves fifteen players',
    (
        $result[
            'player_count'
        ]
        ?? null
    )
    ===
    15
);


squadHorizonPropagationCheck(
    'Squad Horizon preserves three gameweeks',
    count(
        $gameweeks
    )
    ===
    3
);


squadHorizonPropagationCheck(
    'Horizon gameweeks are GW2, GW3 and GW4',
    array_keys(
        $gameweeks
    )
    ===
    [
        2,
        3,
        4
    ]
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * NORMAL GAMEWEEK PROPAGATION
 * ============================================================
 */

squadHorizonPropagationHeading(
    'Scenario B: Normal Gameweek Propagation'
);


$gw2Player =
    $findPlayer(
        $gameweeks[
            2
        ]
        ?? [],
        1
    );


squadHorizonPropagationCheck(
    'GW2 controlled player is present',
    is_array(
        $gw2Player
    )
);


squadHorizonPropagationCheck(
    'GW2 preserves projected points',
    abs(
        (
            (float) (
                $gw2Player[
                    'projected_points'
                ]
                ?? 0.0
            )
        )
        -
        5.0
    )
    <
    0.001
);


squadHorizonPropagationCheck(
    'GW2 preserves aggregate opponent',
    (
        $gw2Player[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    20
);


squadHorizonPropagationCheck(
    'GW2 preserves fixture count',
    (
        $gw2Player[
            'fixture_count'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonPropagationCheck(
    'GW2 preserves Normal schedule type',
    (
        $gw2Player[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
);


squadHorizonPropagationCheck(
    'GW2 preserves one fixture',
    count(
        $gw2Player[
            'fixtures'
        ]
        ?? []
    )
    ===
    1
);


squadHorizonPropagationCheck(
    'GW2 fixture preserves opponent metadata',
    (
        $gw2Player[
            'fixtures'
        ][0][
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    20
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * BLANK GAMEWEEK PROPAGATION
 * ============================================================
 */

squadHorizonPropagationHeading(
    'Scenario C: Blank Gameweek Propagation'
);


$gw3Player =
    $findPlayer(
        $gameweeks[
            3
        ]
        ?? [],
        1
    );


squadHorizonPropagationCheck(
    'Blank GW3 controlled player remains present',
    is_array(
        $gw3Player
    )
);


squadHorizonPropagationCheck(
    'Blank GW3 preserves zero projected points',
    abs(
        (
            (float) (
                $gw3Player[
                    'projected_points'
                ]
                ?? -1.0
            )
        )
        -
        0.0
    )
    <
    0.001
);


squadHorizonPropagationCheck(
    'Blank GW3 has no aggregate opponent',
    !array_key_exists(
        'opponent_team_id',
        $gw3Player
    )
    ||
    $gw3Player[
        'opponent_team_id'
    ]
    ===
    null
);


squadHorizonPropagationCheck(
    'Blank GW3 preserves fixture count zero',
    (
        $gw3Player[
            'fixture_count'
        ]
        ?? null
    )
    ===
    0
);


squadHorizonPropagationCheck(
    'Blank GW3 preserves Blank schedule type',
    (
        $gw3Player[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
);


squadHorizonPropagationCheck(
    'Blank GW3 preserves an empty fixture list',
    (
        $gw3Player[
            'fixtures'
        ]
        ?? null
    )
    ===
    []
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * DOUBLE GAMEWEEK PROPAGATION
 * ============================================================
 */

squadHorizonPropagationHeading(
    'Scenario D: Double Gameweek Propagation'
);


$gw4Player =
    $findPlayer(
        $gameweeks[
            4
        ]
        ?? [],
        1
    );


squadHorizonPropagationCheck(
    'Double GW4 controlled player is present',
    is_array(
        $gw4Player
    )
);


squadHorizonPropagationCheck(
    'Double GW4 preserves aggregate projected points',
    abs(
        (
            (float) (
                $gw4Player[
                    'projected_points'
                ]
                ?? 0.0
            )
        )
        -
        8.0
    )
    <
    0.001
);


squadHorizonPropagationCheck(
    'Double GW4 has no aggregate opponent',
    !array_key_exists(
        'opponent_team_id',
        $gw4Player
    )
    ||
    $gw4Player[
        'opponent_team_id'
    ]
    ===
    null
);


squadHorizonPropagationCheck(
    'Double GW4 preserves fixture count two',
    (
        $gw4Player[
            'fixture_count'
        ]
        ?? null
    )
    ===
    2
);


squadHorizonPropagationCheck(
    'Double GW4 preserves Double schedule type',
    (
        $gw4Player[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


squadHorizonPropagationCheck(
    'Double GW4 preserves both fixtures',
    count(
        $gw4Player[
            'fixtures'
        ]
        ?? []
    )
    ===
    2
);


$doubleFixtureIds =
    array_map(
        static function (
            array $fixture
        ): int {

            return
                (int) (
                    $fixture[
                        'fixture_id'
                    ]
                    ?? 0
                );
        },
        $gw4Player[
            'fixtures'
        ]
        ?? []
    );


squadHorizonPropagationCheck(
    'Double GW4 preserves fixture identities',
    $doubleFixtureIds
    ===
    [
        401,
        402
    ]
);


$doubleOpponentIds =
    array_map(
        static function (
            array $fixture
        ): int {

            return
                (int) (
                    $fixture[
                        'opponent_team_id'
                    ]
                    ?? 0
                );
        },
        $gw4Player[
            'fixtures'
        ]
        ?? []
    );


squadHorizonPropagationCheck(
    'Double GW4 preserves both individual opponents',
    $doubleOpponentIds
    ===
    [
        18,
        19
    ]
);


squadHorizonPropagationCheck(
    'Double GW4 preserves home and away context',
    (
        $gw4Player[
            'fixtures'
        ][0][
            'is_home'
        ]
        ?? null
    )
    ===
    true
    &&
    (
        $gw4Player[
            'fixtures'
        ][1][
            'is_home'
        ]
        ?? null
    )
    ===
    false
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E
 * STARTING XI COMPATIBILITY
 * ============================================================
 *
 * A Blank Gameweek must not cause the player to disappear from
 * the squad structure.
 *
 * Player 1 has zero projected points in GW3, so the model may
 * bench that player. The important contract here is that the
 * model can still produce a legal Starting XI and complete bench
 * while preserving the blank player in the overall gameweek row.
 */

squadHorizonPropagationHeading(
    'Scenario E: Starting XI Compatibility'
);


$gw2 =
    $gameweeks[
        2
    ]
    ?? [];


$gw3 =
    $gameweeks[
        3
    ]
    ?? [];


$gw4 =
    $gameweeks[
        4
    ]
    ?? [];


squadHorizonPropagationCheck(
    'GW2 produces eleven starters',
    count(
        $gw2[
            'starting_xi'
        ]
        ?? []
    )
    ===
    11
);


squadHorizonPropagationCheck(
    'GW3 produces eleven starters despite controlled blank',
    count(
        $gw3[
            'starting_xi'
        ]
        ?? []
    )
    ===
    11
);


squadHorizonPropagationCheck(
    'GW4 produces eleven starters',
    count(
        $gw4[
            'starting_xi'
        ]
        ?? []
    )
    ===
    11
);


squadHorizonPropagationCheck(
    'GW3 still contains all fifteen squad players',
    count(
        $gw3[
            'players'
        ]
        ?? []
    )
    ===
    15
);


squadHorizonPropagationCheck(
    'GW3 bench contains four players',
    count(
        $gw3[
            'bench'
        ]
        ?? []
    )
    ===
    4
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