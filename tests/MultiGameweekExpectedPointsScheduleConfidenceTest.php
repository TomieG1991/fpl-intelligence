<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function multiGameweekScheduleConfidenceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        return;
    }


    $failed++;

    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';
}


/*
 * ============================================================
 * STUB PLAYER EXPECTED POINTS
 * ============================================================
 *
 * Confidence is supplied through the fixture context so we can
 * deliberately test different confidence values in a DGW.
 */

class MultiGameweekScheduleConfidencePlayerExpectedPoints
    extends PlayerExpectedPoints
{
    public function __construct()
    {
        /*
         * Parent dependencies are unnecessary because project()
         * is completely overridden for this isolated test.
         */
    }


    public function project(
        array $player,
        array $form,
        array $fixtureContext = []
    ): array {

        $confidence =
            isset(
                $fixtureContext[
                    'test_projection_confidence'
                ]
            )
            &&
            is_numeric(
                $fixtureContext[
                    'test_projection_confidence'
                ]
            )
                ? (float) $fixtureContext[
                    'test_projection_confidence'
                ]
                : 0.5;


        return [

            'player_id' =>
                1,

            'fpl_player_id' =>
                101,

            'position' =>
                'MID',

            'projected_points' =>
                5.0,

            'projected_minutes' =>
                80.0,

            'projection_confidence' =>
                $confidence,

            'projection_confidence_percent' =>
                $confidence
                *
                100.0,

            'projection_confidence_label' =>
                'Test',

            'components' =>
                [],

            'inputs' =>
                []
        ];
    }
}


echo
    '============================================<br>';

echo
    'Multi-Gameweek Expected Points Schedule Confidence Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$multiGameweekExpectedPoints =
    new MultiGameweekExpectedPoints(
        new MultiGameweekScheduleConfidencePlayerExpectedPoints()
    );


$player = [

    'id' =>
        1,

    'fpl_player_id' =>
        101,

    'position' =>
        'MID'
];


$form =
    [];


/*
 * GW3 = Normal
 * GW4 = Blank
 * GW5 = Double
 */

$fixtures = [

    [
        'id' =>
            501,

        'fpl_fixture_id' =>
            1501,

        'gameweek' =>
            3,

        'kickoff_time' =>
            '2026-09-12 15:00:00'
    ],

    [
        'id' =>
            502,

        'fpl_fixture_id' =>
            1502,

        'gameweek' =>
            5,

        'kickoff_time' =>
            '2026-09-26 12:30:00'
    ],

    [
        'id' =>
            503,

        'fpl_fixture_id' =>
            1503,

        'gameweek' =>
            5,

        'kickoff_time' =>
            '2026-09-29 19:45:00'
    ]
];


$fixtureContexts = [

    'fixture:501' => [

        'test_projection_confidence' =>
            0.72
    ],

    'fixture:502' => [

        'test_projection_confidence' =>
            0.80
    ],

    'fixture:503' => [

        'test_projection_confidence' =>
            0.60
    ]
];


$result =
    $multiGameweekExpectedPoints
        ->projectFixtures(
            $player,
            $form,
            $fixtures,
            $fixtureContexts
        );


$gameweek3 =
    $result[
        'gameweeks'
    ][3]
    ??
    [];


$gameweek4 =
    $result[
        'gameweeks'
    ][4]
    ??
    [];


$gameweek5 =
    $result[
        'gameweeks'
    ][5]
    ??
    [];


/*
 * ============================================================
 * Scenario A: Normal Gameweek
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Normal Gameweek confidence<br>';

echo
    '============================================<br>';


multiGameweekScheduleConfidenceCheck(
    'GW3 remains Normal',
    (
        $gameweek3[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Normal'
);


multiGameweekScheduleConfidenceCheck(
    'GW3 preserves confidence 0.72',
    isset(
        $gameweek3[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $gameweek3[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek3[
                'projection_confidence'
            ]
        )
        -
        0.72
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Blank Gameweek
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Blank Gameweek confidence<br>';

echo
    '============================================<br>';


multiGameweekScheduleConfidenceCheck(
    'GW4 exists explicitly',
    !empty(
        $gameweek4
    )
);


multiGameweekScheduleConfidenceCheck(
    'GW4 remains Blank',
    (
        $gameweek4[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Blank'
);


multiGameweekScheduleConfidenceCheck(
    'GW4 projected points remain zero',
    abs(
        (
            $gameweek4[
                'projected_points'
            ]
            ??
            -1.0
        )
        -
        0.0
    ) < 0.0001
);


multiGameweekScheduleConfidenceCheck(
    'Blank Gameweek has explicit null projection confidence',
    array_key_exists(
        'projection_confidence',
        $gameweek4
    )
    &&
    $gameweek4[
        'projection_confidence'
    ]
    ===
    null
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Double Gameweek
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Double Gameweek confidence<br>';

echo
    '============================================<br>';


multiGameweekScheduleConfidenceCheck(
    'GW5 remains Double',
    (
        $gameweek5[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Double'
);


multiGameweekScheduleConfidenceCheck(
    'GW5 preserves both fixture projections',
    count(
        $gameweek5[
            'fixtures'
        ]
        ??
        []
    )
    ===
    2
);


multiGameweekScheduleConfidenceCheck(
    'GW5 projected points include both fixtures',
    abs(
        (
            $gameweek5[
                'projected_points'
            ]
            ??
            0.0
        )
        -
        10.0
    ) < 0.0001
);


multiGameweekScheduleConfidenceCheck(
    'Double Gameweek confidence uses weaker fixture confidence',
    isset(
        $gameweek5[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $gameweek5[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek5[
                'projection_confidence'
            ]
        )
        -
        0.60
    ) < 0.0001
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
    . '<br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}