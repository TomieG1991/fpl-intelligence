<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function multiGameweekProjectionConfidenceCheck(
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
 * This test is concerned only with whether
 * MultiGameweekExpectedPoints preserves an already-calculated
 * projection confidence.
 *
 * ProjectionConfidence itself is already tested separately.
 * We therefore supply a deterministic PlayerExpectedPoints
 * result rather than recalculating confidence here.
 */

class MultiGameweekProjectionConfidencePlayerExpectedPoints
    extends PlayerExpectedPoints
{
    public function __construct()
    {
        /*
         * Deliberately do not call the parent constructor.
         *
         * project() is fully overridden below, so none of the
         * parent's dependencies are required for this isolated
         * preservation test.
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
    'Multi-Gameweek Expected Points Projection Confidence Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$multiGameweekExpectedPoints =
    new MultiGameweekExpectedPoints(
        new MultiGameweekProjectionConfidencePlayerExpectedPoints()
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

    'fixture:501' =>
        [
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
 * Scenario A: Existing fixture projection contract
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Existing fixture projection contract<br>';

echo
    '============================================<br>';


$fixtureProjection =
    $result[
        'fixtures'
    ][0]
    ??
    [];


multiGameweekProjectionConfidenceCheck(
    'One fixture projection is returned',
    count(
        $result[
            'fixtures'
        ]
        ??
        []
    )
    ===
    1
);


multiGameweekProjectionConfidenceCheck(
    'Fixture remains projected',
    (
        $fixtureProjection[
            'status'
        ]
        ??
        null
    )
    ===
    'Projected'
);


multiGameweekProjectionConfidenceCheck(
    'Projected points remain unchanged',
    abs(
        (
            $fixtureProjection[
                'projected_points'
            ]
            ??
            0.0
        )
        -
        6.5
    ) < 0.0001
);


multiGameweekProjectionConfidenceCheck(
    'Projection confidence percent remains preserved',
    abs(
        (
            $fixtureProjection[
                'projection_confidence_percent'
            ]
            ??
            0.0
        )
        -
        72.0
    ) < 0.0001
);


multiGameweekProjectionConfidenceCheck(
    'Projection confidence label remains preserved',
    (
        $fixtureProjection[
            'projection_confidence_label'
        ]
        ??
        null
    )
    ===
    'Moderate'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Normalized projection confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Normalized projection confidence<br>';

echo
    '============================================<br>';


multiGameweekProjectionConfidenceCheck(
    'Normalized projection confidence is preserved exactly',
    isset(
        $fixtureProjection[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $fixtureProjection[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $fixtureProjection[
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