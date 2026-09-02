<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Squad Horizon Double Gameweek Fixture Clash Test<br>';

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

function squadHorizonDgwClashCheck(
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


function squadHorizonDgwClashHeading(
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
 * Standard FPL squad:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * Players 8 and 9 are controlled Double Gameweek midfielders.
 *
 * Player 8:
 * Team 1
 * Fixture 501 vs Team 2
 * Fixture 502 vs Team 3
 *
 * Player 9:
 * Team 2
 * Fixture 501 vs Team 1
 * Fixture 503 vs Team 4
 *
 * Therefore Players 8 and 9 directly oppose one another in
 * fixture 501.
 *
 * Their aggregate opponent_team_id values deliberately remain
 * null because neither player has one truthful opponent across
 * the complete Double Gameweek.
 *
 * Existing v0.32 fixture-clash logic therefore cannot detect
 * this clash without inspecting the preserved fixture lists.
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


    /*
     * Controlled DGW Player 8.
     */
    if (
        $playerNumber === 8
    ) {

        $squad[] = [

            'player_id' =>
                8,

            'name' =>
                'DGW Player A',

            'position' =>
                'MID',

            'team_id' =>
                1,

            'gameweeks' => [

                2 => [

                    'gameweek' =>
                        2,

                    'projected_points' =>
                        10.0,

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
                                501,

                            'fpl_fixture_id' =>
                                1501,

                            'gameweek' =>
                                2,

                            'opponent_team_id' =>
                                2,

                            'opponent_name' =>
                                'Team 2',

                            'is_home' =>
                                true
                        ],

                        [
                            'fixture_id' =>
                                502,

                            'fpl_fixture_id' =>
                                1502,

                            'gameweek' =>
                                2,

                            'opponent_team_id' =>
                                3,

                            'opponent_name' =>
                                'Team 3',

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
     * Controlled DGW Player 9.
     */
    if (
        $playerNumber === 9
    ) {

        $squad[] = [

            'player_id' =>
                9,

            'name' =>
                'DGW Player B',

            'position' =>
                'MID',

            'team_id' =>
                2,

            'gameweeks' => [

                2 => [

                    'gameweek' =>
                        2,

                    'projected_points' =>
                        9.0,

                    'team_id' =>
                        2,

                    'opponent_team_id' =>
                        null,

                    'fixture_count' =>
                        2,

                    'schedule_type' =>
                        'Double',

                    'fixtures' => [

                        [
                            'fixture_id' =>
                                501,

                            'fpl_fixture_id' =>
                                1501,

                            'gameweek' =>
                                2,

                            'opponent_team_id' =>
                                1,

                            'opponent_name' =>
                                'Team 1',

                            'is_home' =>
                                false
                        ],

                        [
                            'fixture_id' =>
                                503,

                            'fpl_fixture_id' =>
                                1503,

                            'gameweek' =>
                                2,

                            'opponent_team_id' =>
                                4,

                            'opponent_name' =>
                                'Team 4',

                            'is_home' =>
                                true
                        ]
                    ]
                ]
            ]
        ];


        continue;
    }


    /*
     * All remaining players receive simple Normal Gameweek
     * fixtures.
     *
     * Team and opponent IDs are deliberately separated from
     * Teams 1–4 so no accidental reciprocal fixture clash is
     * created.
     */

    $teamId =
        100
        +
        $playerNumber;


    $opponentTeamId =
        200
        +
        $playerNumber;


    $projectedPoints =
        7.0
        -
        (
            $playerNumber
            *
            0.1
        );


    $fixtureId =
        2000
        +
        $playerNumber;


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

        'gameweeks' => [

            2 => [

                'gameweek' =>
                    2,

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
                            $fixtureId,

                        'fpl_fixture_id' =>
                            3000
                            +
                            $playerNumber,

                        'gameweek' =>
                            2,

                        'opponent_team_id' =>
                            $opponentTeamId,

                        'opponent_name' =>
                            'Opponent '
                            . $opponentTeamId,

                        'is_home' =>
                            true
                    ]
                ]
            ]
        ]
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
        1
    );


$gw2 =
    $result[
        'gameweeks'
    ][
        2
    ]
    ?? [];


$startingXI =
    $gw2[
        'starting_xi'
    ]
    ?? [];


$startingPlayerIds =
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
        $startingXI
    );


sort(
    $startingPlayerIds,
    SORT_NUMERIC
);


/*
 * ============================================================
 * SCENARIO A
 * HORIZON AND STARTING XI BASELINE
 * ============================================================
 */

squadHorizonDgwClashHeading(
    'Scenario A: Horizon and Starting XI Baseline'
);


squadHorizonDgwClashCheck(
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


squadHorizonDgwClashCheck(
    'GW2 contains all fifteen squad players',
    count(
        $gw2[
            'players'
        ]
        ?? []
    )
    ===
    15
);


squadHorizonDgwClashCheck(
    'GW2 produces eleven starters',
    count(
        $startingXI
    )
    ===
    11
);


squadHorizonDgwClashCheck(
    'DGW Player 8 is selected in the Starting XI',
    in_array(
        8,
        $startingPlayerIds,
        true
    )
);


squadHorizonDgwClashCheck(
    'DGW Player 9 is selected in the Starting XI',
    in_array(
        9,
        $startingPlayerIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * DOUBLE GAMEWEEK INPUT CONTRACT
 * ============================================================
 */

squadHorizonDgwClashHeading(
    'Scenario B: Double Gameweek Input Contract'
);


$player8 =
    null;


$player9 =
    null;


foreach (
    $gw2[
        'players'
    ]
    ?? []
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
        8
    ) {

        $player8 =
            $player;
    }


    if (
        (
            $player[
                'player_id'
            ]
            ?? null
        )
        ===
        9
    ) {

        $player9 =
            $player;
    }
}


squadHorizonDgwClashCheck(
    'DGW Player 8 preserves Double schedule type',
    (
        $player8[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


squadHorizonDgwClashCheck(
    'DGW Player 8 preserves two fixtures',
    count(
        $player8[
            'fixtures'
        ]
        ?? []
    )
    ===
    2
);


squadHorizonDgwClashCheck(
    'DGW Player 8 has no aggregate opponent',
    (
        $player8[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    null
);


squadHorizonDgwClashCheck(
    'DGW Player 9 preserves Double schedule type',
    (
        $player9[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


squadHorizonDgwClashCheck(
    'DGW Player 9 preserves two fixtures',
    count(
        $player9[
            'fixtures'
        ]
        ?? []
    )
    ===
    2
);


squadHorizonDgwClashCheck(
    'DGW Player 9 has no aggregate opponent',
    (
        $player9[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    null
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * SHARED REAL FIXTURE
 * ============================================================
 *
 * Before testing the clash detector itself, prove that the two
 * controlled players preserve reciprocal fixture metadata for
 * the same real fixture.
 */

squadHorizonDgwClashHeading(
    'Scenario C: Shared Double Gameweek Fixture'
);


$player8Fixtures =
    $player8[
        'fixtures'
    ]
    ?? [];


$player9Fixtures =
    $player9[
        'fixtures'
    ]
    ?? [];


$player8SharedFixture =
    null;


$player9SharedFixture =
    null;


foreach (
    $player8Fixtures
    as $fixture
) {

    if (
        (
            $fixture[
                'fixture_id'
            ]
            ?? null
        )
        ===
        501
    ) {

        $player8SharedFixture =
            $fixture;

        break;
    }
}


foreach (
    $player9Fixtures
    as $fixture
) {

    if (
        (
            $fixture[
                'fixture_id'
            ]
            ?? null
        )
        ===
        501
    ) {

        $player9SharedFixture =
            $fixture;

        break;
    }
}


squadHorizonDgwClashCheck(
    'Both DGW players preserve shared fixture 501',
    is_array(
        $player8SharedFixture
    )
    &&
    is_array(
        $player9SharedFixture
    )
);


squadHorizonDgwClashCheck(
    'Shared fixture preserves reciprocal opponents',
    (
        $player8SharedFixture[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    2
    &&
    (
        $player9SharedFixture[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonDgwClashCheck(
    'Shared fixture preserves opposing home and away context',
    (
        $player8SharedFixture[
            'is_home'
        ]
        ?? null
    )
    ===
    true
    &&
    (
        $player9SharedFixture[
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
 * SCENARIO D
 * DOUBLE GAMEWEEK FIXTURE CLASH DETECTION
 * ============================================================
 */

squadHorizonDgwClashHeading(
    'Scenario D: Double Gameweek Fixture Clash Detection'
);


$fixtureClashes =
    $result[
        'fixture_clashes'
    ]
    ?? [];


$gw2Clashes =
    $fixtureClashes[
        'gameweeks'
    ][
        2
    ]
    ?? [];


$clashes =
    $gw2Clashes[
        'clashes'
    ]
    ?? [];


squadHorizonDgwClashCheck(
    'GW2 reports exactly one fixture clash',
    (
        $gw2Clashes[
            'clash_count'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonDgwClashCheck(
    'GW2 exposes exactly one clash row',
    count(
        $clashes
    )
    ===
    1
);


$firstClash =
    $clashes[
        0
    ]
    ?? [];


squadHorizonDgwClashCheck(
    'DGW clash identifies Players 8 and 9',
    (
        $firstClash[
            'player_ids'
        ]
        ?? null
    )
    ===
    [
        8,
        9
    ]
);


$clashTeamIds =
    $firstClash[
        'team_ids'
    ]
    ?? [];


sort(
    $clashTeamIds,
    SORT_NUMERIC
);


squadHorizonDgwClashCheck(
    'DGW clash identifies Teams 1 and 2',
    $clashTeamIds
    ===
    [
        1,
        2
    ]
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E
 * HORIZON CLASH SUMMARY
 * ============================================================
 */

squadHorizonDgwClashHeading(
    'Scenario E: Horizon Clash Summary'
);


squadHorizonDgwClashCheck(
    'Horizon reports one total fixture clash',
    (
        $fixtureClashes[
            'total_clash_count'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonDgwClashCheck(
    'Horizon reports one gameweek with clashes',
    (
        $fixtureClashes[
            'gameweeks_with_clashes'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonDgwClashCheck(
    'GW2 is the worst clash gameweek',
    (
        $fixtureClashes[
            'worst_gameweek'
        ]
        ?? null
    )
    ===
    2
);


squadHorizonDgwClashCheck(
    'Maximum clash count is one',
    (
        $fixtureClashes[
            'max_clash_count'
        ]
        ?? null
    )
    ===
    1
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