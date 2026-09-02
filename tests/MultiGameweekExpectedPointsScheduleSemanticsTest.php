<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Multi-Gameweek Expected Points Schedule Semantics Test<br>';

echo
    'v0.33.0 — Blank & Double Gameweek Intelligence<br>';

echo
    '============================================<br><br>';


$passed =
    0;


$failed =
    0;


function multiGwScheduleCheck(
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
 * TEST DOUBLE
 * ============================================================
 *
 * This deliberately mirrors the existing
 * MultiGameweekExpectedPointsTest approach.
 *
 * We are testing multi-gameweek orchestration and schedule
 * semantics here, not the Expected Points mathematics.
 */

class MultiGameweekSchedulePlayerExpectedPointsStub
    extends PlayerExpectedPoints
{
    public function __construct()
    {
        /*
         * Parent constructor is intentionally not called.
         *
         * project() is completely overridden below.
         */
    }


    public function project(
        array $player,
        array $form,
        array $fixtureContext = []
    ): array {

        $points =
            isset(
                $fixtureContext[
                    'test_projected_points'
                ]
            )
            &&
            is_numeric(
                $fixtureContext[
                    'test_projected_points'
                ]
            )
                ? (float) $fixtureContext[
                    'test_projected_points'
                ]
                : 0.0;


        $minutes =
            isset(
                $fixtureContext[
                    'test_projected_minutes'
                ]
            )
            &&
            is_numeric(
                $fixtureContext[
                    'test_projected_minutes'
                ]
            )
                ? (float) $fixtureContext[
                    'test_projected_minutes'
                ]
                : 90.0;


        return [

            'player_id' =>
                (int) (
                    $player[
                        'id'
                    ]
                    ?? 0
                ),

            'fpl_player_id' =>
                (int) (
                    $player[
                        'fpl_player_id'
                    ]
                    ?? 0
                ),

            'position' =>
                $player[
                    'position'
                ]
                ?? null,

            'projected_points' =>
                $points,

            'projected_minutes' =>
                $minutes,

            'projection_confidence_percent' =>
                70.0,

            'projection_confidence_label' =>
                'Moderate',

            'components' => [

                'appearance' =>
                    $points
            ],

            'inputs' => [

                'fixture_opportunity' =>
                    $fixtureContext[
                        'fixture_opportunity'
                    ]
                    ?? null
            ]
        ];
    }
}


/*
 * ============================================================
 * MODEL
 * ============================================================
 */

$model =
    new MultiGameweekExpectedPoints(
        new MultiGameweekSchedulePlayerExpectedPointsStub()
    );


$player = [

    'id' =>
        1,

    'fpl_player_id' =>
        1001,

    'position' =>
        'MID'
];


$form = [

    'appearance_sample_size' =>
        5
];


/*
 * ============================================================
 * CONTROLLED SCHEDULE
 * ============================================================
 *
 * GW2 = Normal
 *      Fixture 10
 *      5.00 xP
 *
 * GW3 = Blank
 *      No fixture
 *
 * GW4 = Double
 *      Fixture 40 = 4.25 xP
 *      Fixture 41 = 3.75 xP
 *
 * Expected GW4 total = 8.00 xP
 *
 * The gap between represented GW2 and GW4 is intentional.
 * v0.33 should preserve GW3 explicitly as a Blank Gameweek.
 */

$fixtures = [

    [
        'id' =>
            10,

        'fpl_fixture_id' =>
            110,

        'gameweek' =>
            2,

        'kickoff_time' =>
            '2026-08-29 15:00:00'
    ],

    [
        'id' =>
            40,

        'fpl_fixture_id' =>
            140,

        'gameweek' =>
            4,

        'kickoff_time' =>
            '2026-09-12 12:30:00'
    ],

    [
        'id' =>
            41,

        'fpl_fixture_id' =>
            141,

        'gameweek' =>
            4,

        'kickoff_time' =>
            '2026-09-15 19:45:00'
    ]
];


$fixtureContexts = [

    'fixture:10' => [

        'test_projected_points' =>
            5.0
    ],

    'fixture:40' => [

        'test_projected_points' =>
            4.25
    ],

    'fixture:41' => [

        'test_projected_points' =>
            3.75
    ]
];


$result =
    $model
        ->projectFixtures(
            $player,
            $form,
            $fixtures,
            $fixtureContexts
        );


/*
 * ============================================================
 * SCENARIO A
 * GAMEWEEK RANGE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Explicit Gameweek Range<br>';

echo
    '============================================<br>';


multiGwScheduleCheck(
    'Projection exposes GW2',
    isset(
        $result[
            'gameweeks'
        ][
            2
        ]
    )
);


multiGwScheduleCheck(
    'Projection explicitly exposes blank GW3',
    isset(
        $result[
            'gameweeks'
        ][
            3
        ]
    )
);


multiGwScheduleCheck(
    'Projection exposes GW4',
    isset(
        $result[
            'gameweeks'
        ][
            4
        ]
    )
);


multiGwScheduleCheck(
    'GW2 to GW4 produces three explicit gameweek rows',
    count(
        $result[
            'gameweeks'
        ]
        ?? []
    )
    ===
    3
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * NORMAL GAMEWEEK SEMANTICS
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Normal Gameweek Semantics<br>';

echo
    '============================================<br>';


$gw2 =
    $result[
        'gameweeks'
    ][
        2
    ]
    ?? [];


multiGwScheduleCheck(
    'Normal GW2 records one fixture',
    (
        (int) (
            $gw2[
                'fixture_count'
            ]
            ?? 0
        )
    )
    ===
    1
);


multiGwScheduleCheck(
    'Normal GW2 is classified as Normal',
    (
        $gw2[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
);


multiGwScheduleCheck(
    'Normal GW2 preserves its fixture projection',
    count(
        $gw2[
            'fixtures'
        ]
        ?? []
    )
    ===
    1
);


multiGwScheduleCheck(
    'Normal GW2 preserves its projected points',
    abs(
        (
            (float) (
                $gw2[
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


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * BLANK GAMEWEEK SEMANTICS
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Blank Gameweek Semantics<br>';

echo
    '============================================<br>';


$gw3 =
    $result[
        'gameweeks'
    ][
        3
    ]
    ?? [];


multiGwScheduleCheck(
    'Blank GW3 has zero fixtures',
    isset(
        $result[
            'gameweeks'
        ][
            3
        ]
    )
    &&
    (
        (int) (
            $gw3[
                'fixture_count'
            ]
            ?? -1
        )
    )
    ===
    0
);


multiGwScheduleCheck(
    'Blank GW3 is classified as Blank',
    (
        $gw3[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
);


multiGwScheduleCheck(
    'Blank GW3 exposes an empty fixture list',
    (
        $gw3[
            'fixtures'
        ]
        ?? null
    )
    ===
    []
);


multiGwScheduleCheck(
    'Blank GW3 explicitly projects zero points',
    isset(
        $result[
            'gameweeks'
        ][
            3
        ]
    )
    &&
    abs(
        (
            (float) (
                $gw3[
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


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * DOUBLE GAMEWEEK SEMANTICS
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Double Gameweek Semantics<br>';

echo
    '============================================<br>';


$gw4 =
    $result[
        'gameweeks'
    ][
        4
    ]
    ?? [];


multiGwScheduleCheck(
    'Double GW4 records two fixtures',
    (
        (int) (
            $gw4[
                'fixture_count'
            ]
            ?? 0
        )
    )
    ===
    2
);


multiGwScheduleCheck(
    'Double GW4 is classified as Double',
    (
        $gw4[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


multiGwScheduleCheck(
    'Double GW4 preserves both fixture projections',
    count(
        $gw4[
            'fixtures'
        ]
        ?? []
    )
    ===
    2
);


$gw4FixtureIds =
    array_map(
        function (
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
        $gw4[
            'fixtures'
        ]
        ?? []
    );


multiGwScheduleCheck(
    'Double GW4 preserves both fixture identities',
    $gw4FixtureIds
    ===
    [
        40,
        41
    ]
);


multiGwScheduleCheck(
    'Double GW4 sums both fixture projections',
    abs(
        (
            (float) (
                $gw4[
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


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E
 * HORIZON TOTAL
 * ============================================================
 *
 * GW2 = 5.00
 * GW3 = 0.00
 * GW4 = 8.00
 *
 * next_3 must therefore remain 13.00.
 */

echo
    '============================================<br>';

echo
    'Scenario E: Horizon Total<br>';

echo
    '============================================<br>';


multiGwScheduleCheck(
    'Next-3 total includes Normal, Blank and Double gameweeks',
    abs(
        (
            (float) (
                $result[
                    'totals'
                ][
                    'next_3'
                ]
                ?? 0.0
            )
        )
        -
        13.0
    )
    <
    0.001
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