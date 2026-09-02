<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Gameweek Schedule Intelligence Test<br>';

echo
    'v0.33.0 — Blank & Double Gameweek Intelligence<br>';

echo
    '============================================<br><br>';


$passed =
    0;


$failed =
    0;


function gameweekScheduleCheck(
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


/*
 * ============================================================
 * MODEL
 * ============================================================
 */

$model =
    new GameweekScheduleIntelligence();


/*
 * ============================================================
 * CONTROLLED FIXTURE DATA
 * ============================================================
 *
 * GW10
 *
 * Team 1 = Blank
 *
 * Team 2 = Normal
 * Team 2 vs Team 3
 *
 * Team 3 = Double
 * Team 3 vs Team 2
 * Team 3 vs Team 4
 *
 * Team 4 = Normal
 * Team 4 vs Team 3
 */

$fixtures = [

    [
        'id' =>
            100,

        'fpl_fixture_id' =>
            1000,

        'gameweek' =>
            10,

        'home_team_id' =>
            2,

        'away_team_id' =>
            3,

        'kickoff_time' =>
            '2026-11-21 15:00:00'
    ],

    [
        'id' =>
            101,

        'fpl_fixture_id' =>
            1001,

        'gameweek' =>
            10,

        'home_team_id' =>
            3,

        'away_team_id' =>
            4,

        'kickoff_time' =>
            '2026-11-24 19:45:00'
    ]
];


$teamIds = [
    1,
    2,
    3,
    4
];


$gameweeks = [
    10
];


/*
 * ============================================================
 * SCENARIO A
 * RESULT STRUCTURE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Result Structure<br>';

echo
    '============================================<br>';


$result =
    $model
        ->analyse(
            $fixtures,
            $teamIds,
            $gameweeks
        );


gameweekScheduleCheck(
    'Schedule analysis returns an array',
    is_array(
        $result
    )
);


gameweekScheduleCheck(
    'Schedule analysis exposes gameweeks',
    isset(
        $result[
            'gameweeks'
        ]
    )
    &&
    is_array(
        $result[
            'gameweeks'
        ]
    )
);


gameweekScheduleCheck(
    'Requested gameweek is preserved',
    isset(
        $result[
            'gameweeks'
        ][
            10
        ]
    )
);


gameweekScheduleCheck(
    'Requested gameweek exposes team schedules',
    isset(
        $result[
            'gameweeks'
        ][
            10
        ][
            'teams'
        ]
    )
    &&
    is_array(
        $result[
            'gameweeks'
        ][
            10
        ][
            'teams'
        ]
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * BLANK GAMEWEEK
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Blank Gameweek<br>';

echo
    '============================================<br>';


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


gameweekScheduleCheck(
    'Blank team is preserved in the schedule',
    !empty(
        $team1
    )
);


gameweekScheduleCheck(
    'Blank team has zero fixtures',
    (
        (int) (
            $team1[
                'fixture_count'
            ]
            ?? -1
        )
    )
    ===
    0
);


gameweekScheduleCheck(
    'Blank team is classified as Blank',
    (
        $team1[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
);


gameweekScheduleCheck(
    'Blank team exposes an empty fixture list',
    (
        $team1[
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
 * SCENARIO C
 * NORMAL GAMEWEEK
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Normal Gameweek<br>';

echo
    '============================================<br>';


$team2 =
    $result[
        'gameweeks'
    ][
        10
    ][
        'teams'
    ][
        2
    ]
    ?? [];


gameweekScheduleCheck(
    'Normal team has one fixture',
    (
        (int) (
            $team2[
                'fixture_count'
            ]
            ?? 0
        )
    )
    ===
    1
);


gameweekScheduleCheck(
    'Normal team is classified as Normal',
    (
        $team2[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
);


gameweekScheduleCheck(
    'Normal team preserves its fixture',
    count(
        $team2[
            'fixtures'
        ]
        ?? []
    )
    ===
    1
);


gameweekScheduleCheck(
    'Normal team fixture preserves fixture identity',
    (
        (int) (
            $team2[
                'fixtures'
            ][
                0
            ][
                'id'
            ]
            ?? 0
        )
    )
    ===
    100
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * DOUBLE GAMEWEEK
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Double Gameweek<br>';

echo
    '============================================<br>';


$team3 =
    $result[
        'gameweeks'
    ][
        10
    ][
        'teams'
    ][
        3
    ]
    ?? [];


gameweekScheduleCheck(
    'Double Gameweek team has two fixtures',
    (
        (int) (
            $team3[
                'fixture_count'
            ]
            ?? 0
        )
    )
    ===
    2
);


gameweekScheduleCheck(
    'Double Gameweek team is classified as Double',
    (
        $team3[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


gameweekScheduleCheck(
    'Double Gameweek preserves both fixtures',
    count(
        $team3[
            'fixtures'
        ]
        ?? []
    )
    ===
    2
);


$team3FixtureIds =
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
        $team3[
            'fixtures'
        ]
        ?? []
    );


gameweekScheduleCheck(
    'Double Gameweek preserves both fixture identities',
    $team3FixtureIds
    ===
    [
        100,
        101
    ]
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E
 * TEAM IDENTITY
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Team Identity<br>';

echo
    '============================================<br>';


gameweekScheduleCheck(
    'Blank schedule preserves team ID',
    (
        (int) (
            $team1[
                'team_id'
            ]
            ?? 0
        )
    )
    ===
    1
);


gameweekScheduleCheck(
    'Normal schedule preserves team ID',
    (
        (int) (
            $team2[
                'team_id'
            ]
            ?? 0
        )
    )
    ===
    2
);


gameweekScheduleCheck(
    'Double schedule preserves team ID',
    (
        (int) (
            $team3[
                'team_id'
            ]
            ?? 0
        )
    )
    ===
    3
);


gameweekScheduleCheck(
    'Schedules preserve gameweek identity',
    (
        (int) (
            $team3[
                'gameweek'
            ]
            ?? 0
        )
    )
    ===
    10
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