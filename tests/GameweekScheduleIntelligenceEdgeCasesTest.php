<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Gameweek Schedule Intelligence Edge Cases Test<br>';

echo
    'v0.33.0 — Blank & Double Gameweek Intelligence<br>';

echo
    '============================================<br><br>';


$passed =
    0;


$failed =
    0;


function gameweekScheduleEdgeCheck(
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


$model =
    new GameweekScheduleIntelligence();


/*
 * ============================================================
 * SCENARIO A
 * DUPLICATE AND INVALID TEAM IDS
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Team ID Normalisation<br>';

echo
    '============================================<br>';


$result =
    $model
        ->analyse(
            [],
            [
                3,
                '3',
                2,
                0,
                -1,
                'invalid',
                2
            ],
            [
                10
            ]
        );


$teams =
    $result[
        'gameweeks'
    ][
        10
    ][
        'teams'
    ]
    ?? [];


gameweekScheduleEdgeCheck(
    'Duplicate team IDs are removed',
    count(
        $teams
    )
    ===
    2
);


gameweekScheduleEdgeCheck(
    'Team IDs are returned in numeric order',
    array_keys(
        $teams
    )
    ===
    [
        2,
        3
    ]
);


gameweekScheduleEdgeCheck(
    'Invalid team IDs are ignored',
    !isset(
        $teams[
            0
        ]
    )
    &&
    !isset(
        $teams[
            -1
        ]
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * DUPLICATE AND INVALID GAMEWEEKS
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Gameweek Normalisation<br>';

echo
    '============================================<br>';


$result =
    $model
        ->analyse(
            [],
            [
                1
            ],
            [
                12,
                '12',
                10,
                0,
                -2,
                'invalid',
                11
            ]
        );


$gameweeks =
    $result[
        'gameweeks'
    ]
    ?? [];


gameweekScheduleEdgeCheck(
    'Duplicate gameweeks are removed',
    count(
        $gameweeks
    )
    ===
    3
);


gameweekScheduleEdgeCheck(
    'Gameweeks are returned in numeric order',
    array_keys(
        $gameweeks
    )
    ===
    [
        10,
        11,
        12
    ]
);


gameweekScheduleEdgeCheck(
    'Invalid gameweeks are ignored',
    !isset(
        $gameweeks[
            0
        ]
    )
    &&
    !isset(
        $gameweeks[
            -2
        ]
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * FIXTURES OUTSIDE REQUESTED GAMEWEEKS
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Outside Horizon Fixtures<br>';

echo
    '============================================<br>';


$result =
    $model
        ->analyse(
            [
                [
                    'id' =>
                        100,

                    'gameweek' =>
                        9,

                    'home_team_id' =>
                        1,

                    'away_team_id' =>
                        2
                ],

                [
                    'id' =>
                        101,

                    'gameweek' =>
                        10,

                    'home_team_id' =>
                        1,

                    'away_team_id' =>
                        2
                ],

                [
                    'id' =>
                        102,

                    'gameweek' =>
                        11,

                    'home_team_id' =>
                        1,

                    'away_team_id' =>
                        2
                ]
            ],
            [
                1,
                2
            ],
            [
                10
            ]
        );


gameweekScheduleEdgeCheck(
    'Fixtures before requested horizon are ignored',
    (
        (int) (
            $result[
                'gameweeks'
            ][
                10
            ][
                'teams'
            ][
                1
            ][
                'fixture_count'
            ]
            ?? 0
        )
    )
    ===
    1
);


gameweekScheduleEdgeCheck(
    'Fixtures after requested horizon are ignored',
    count(
        $result[
            'gameweeks'
        ]
        ?? []
    )
    ===
    1
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * UNREQUESTED TEAMS
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Unrequested Teams<br>';

echo
    '============================================<br>';


$result =
    $model
        ->analyse(
            [
                [
                    'id' =>
                        200,

                    'gameweek' =>
                        10,

                    'home_team_id' =>
                        1,

                    'away_team_id' =>
                        99
                ]
            ],
            [
                1,
                2
            ],
            [
                10
            ]
        );


gameweekScheduleEdgeCheck(
    'Requested team receives matching fixture',
    (
        (int) (
            $result[
                'gameweeks'
            ][
                10
            ][
                'teams'
            ][
                1
            ][
                'fixture_count'
            ]
            ?? 0
        )
    )
    ===
    1
);


gameweekScheduleEdgeCheck(
    'Unrequested opponent is not added to team schedule',
    !isset(
        $result[
            'gameweeks'
        ][
            10
        ][
            'teams'
        ][
            99
        ]
    )
);


gameweekScheduleEdgeCheck(
    'Requested team without fixture remains Blank',
    (
        $result[
            'gameweeks'
        ][
            10
        ][
            'teams'
        ][
            2
        ][
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E
 * INVALID FIXTURE ROWS
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Invalid Fixture Rows<br>';

echo
    '============================================<br>';


$result =
    $model
        ->analyse(
            [
                'invalid',

                [
                    'id' =>
                        300,

                    'home_team_id' =>
                        1,

                    'away_team_id' =>
                        2
                ],

                [
                    'id' =>
                        301,

                    'gameweek' =>
                        'invalid',

                    'home_team_id' =>
                        1,

                    'away_team_id' =>
                        2
                ],

                [
                    'id' =>
                        302,

                    'gameweek' =>
                        10,

                    'home_team_id' =>
                        0,

                    'away_team_id' =>
                        0
                ]
            ],
            [
                1,
                2
            ],
            [
                10
            ]
        );


gameweekScheduleEdgeCheck(
    'Invalid fixture rows do not create fixtures',
    (
        (int) (
            $result[
                'gameweeks'
            ][
                10
            ][
                'teams'
            ][
                1
            ][
                'fixture_count'
            ]
            ?? -1
        )
    )
    ===
    0
);


gameweekScheduleEdgeCheck(
    'Teams remain Blank when all matching fixture rows are invalid',
    (
        $result[
            'gameweeks'
        ][
            10
        ][
            'teams'
        ][
            1
        ][
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO F
 * THREE-FIXTURE GAMEWEEK
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Three-Fixture Gameweek<br>';

echo
    '============================================<br>';


$result =
    $model
        ->analyse(
            [
                [
                    'id' =>
                        401,

                    'gameweek' =>
                        10,

                    'home_team_id' =>
                        1,

                    'away_team_id' =>
                        2,

                    'kickoff_time' =>
                        '2026-11-25 20:00:00'
                ],

                [
                    'id' =>
                        400,

                    'gameweek' =>
                        10,

                    'home_team_id' =>
                        3,

                    'away_team_id' =>
                        1,

                    'kickoff_time' =>
                        '2026-11-21 15:00:00'
                ],

                [
                    'id' =>
                        402,

                    'gameweek' =>
                        10,

                    'home_team_id' =>
                        1,

                    'away_team_id' =>
                        4,

                    'kickoff_time' =>
                        '2026-11-29 14:00:00'
                ]
            ],
            [
                1,
                2,
                3,
                4
            ],
            [
                10
            ]
        );


$team1 =
    $result[
        'gameweeks'
    ][
        10
    ][
        'teams'
    ][
        1
    ]
    ?? [];


gameweekScheduleEdgeCheck(
    'Three fixtures are preserved',
    (
        (int) (
            $team1[
                'fixture_count'
            ]
            ?? 0
        )
    )
    ===
    3
);


gameweekScheduleEdgeCheck(
    'Three or more fixtures are classified as Double',
    (
        $team1[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


$fixtureIds =
    array_map(
        function (
            array $fixture
        ): int {

            return
                (int) (
                    $fixture[
                        'id'
                    ]
                    ?? 0
                );
        },
        $team1[
            'fixtures'
        ]
        ?? []
    );


gameweekScheduleEdgeCheck(
    'Multiple fixtures are sorted chronologically',
    $fixtureIds
    ===
    [
        400,
        401,
        402
    ]
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO G
 * EMPTY INPUT
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Empty Input<br>';

echo
    '============================================<br>';


$result =
    $model
        ->analyse(
            [],
            [],
            []
        );


gameweekScheduleEdgeCheck(
    'Empty input returns a valid result',
    is_array(
        $result
    )
);


gameweekScheduleEdgeCheck(
    'Empty input exposes an empty gameweek collection',
    (
        $result[
            'gameweeks'
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