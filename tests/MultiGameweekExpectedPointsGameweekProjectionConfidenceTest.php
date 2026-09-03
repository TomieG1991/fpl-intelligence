<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function multiGameweekGameweekConfidenceCheck(
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
 */

class MultiGameweekGameweekConfidencePlayerExpectedPoints
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

        return [

            'player_id' =>
                1,

            'fpl_player_id' =>
                101,

            'position' =>
                'MID',

            'projected_points' =>
                6.5,

            'projected_minutes' =>
                82.0,

            'projection_confidence' =>
                0.72,

            'projection_confidence_percent' =>
                72.0,

            'projection_confidence_label' =>
                'Moderate',

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
    'Multi-Gameweek Expected Points Gameweek Projection Confidence Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$multiGameweekExpectedPoints =
    new MultiGameweekExpectedPoints(
        new MultiGameweekGameweekConfidencePlayerExpectedPoints()
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
    ]
];


$fixtureContexts = [

    'fixture:501' => [

        'fixture_rating' =>
            70.0
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


/*
 * ============================================================
 * Scenario A: Normal Gameweek projection
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Normal Gameweek projection<br>';

echo
    '============================================<br>';


$gameweekProjection =
    $result[
        'gameweeks'
    ][3]
    ??
    [];


multiGameweekGameweekConfidenceCheck(
    'Gameweek 3 projection exists',
    !empty(
        $gameweekProjection
    )
);


multiGameweekGameweekConfidenceCheck(
    'Gameweek remains classified as Normal',
    (
        $gameweekProjection[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Normal'
);


multiGameweekGameweekConfidenceCheck(
    'Gameweek fixture count remains one',
    (
        $gameweekProjection[
            'fixture_count'
        ]
        ??
        null
    )
    ===
    1
);


multiGameweekGameweekConfidenceCheck(
    'Gameweek projected points remain 6.5',
    abs(
        (
            $gameweekProjection[
                'projected_points'
            ]
            ??
            0.0
        )
        -
        6.5
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Gameweek projection confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Gameweek projection confidence<br>';

echo
    '============================================<br>';


multiGameweekGameweekConfidenceCheck(
    'Normal Gameweek preserves normalized projection confidence',
    isset(
        $gameweekProjection[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $gameweekProjection[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $gameweekProjection[
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