<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Multi-Gameweek Expected Points Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function multiGwCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


/*
 * ============================================================
 * TEST DOUBLE
 * ============================================================
 *
 * We deliberately use a tiny PlayerExpectedPoints test double
 * here so this test verifies orchestration rather than re-testing
 * v0.29 scoring mathematics.
 */

class MultiGameweekPlayerExpectedPointsStub
    extends PlayerExpectedPoints
{
    public function __construct()
    {
        /*
         * Parent constructor is intentionally not called because
         * the stub overrides project() completely.
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
            ],

            'expected_minutes_model' => [

                'projected_minutes' =>
                    $minutes,

                'chance_of_playing' =>
                    isset(
                        $fixtureContext[
                            'test_chance_of_playing'
                        ]
                    )
                    &&
                    is_numeric(
                        $fixtureContext[
                            'test_chance_of_playing'
                        ]
                    )
                        ? (float) $fixtureContext[
                            'test_chance_of_playing'
                        ]
                        : 100.0
            ]
        ];
    }
}


$model =
    new MultiGameweekExpectedPoints(
        new MultiGameweekPlayerExpectedPointsStub()
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
 * SCENARIO A
 * SINGLE FIXTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Single Fixture<br>";
echo "============================================<br>";


$result =
    $model
        ->projectFixtures(
            $player,
            $form,
            [
                [
                    'id' =>
                        10,

                    'fpl_fixture_id' =>
                        110,

                    'gameweek' =>
                        2,

                    'kickoff_time' =>
                        '2026-08-29 15:00:00'
                ]
            ],
            [
                'fixture:10' => [

                    'test_projected_points' =>
                        5.25,

                    'fixture_opportunity' =>
                        60
                ]
            ]
        );


multiGwCheck(
    'Single fixture produces one fixture projection',
    count(
        $result[
            'fixtures'
        ]
        ?? []
    )
    ===
    1
);


multiGwCheck(
    'Single fixture projection uses PlayerExpectedPoints output',
    abs(
        (
            (float) (
                $result[
                    'fixtures'
                ][
                    0
                ][
                    'projected_points'
                ]
                ?? 0
            )
        )
        -
        5.25
    )
    <
    0.001
);


multiGwCheck(
    'Single fixture creates one gameweek summary',
    count(
        $result[
            'gameweeks'
        ]
        ?? []
    )
    ===
    1
);


multiGwCheck(
    'Single fixture next-3 total equals projected points',
    abs(
        (
            (float) (
                $result[
                    'totals'
                ][
                    'next_3'
                ]
                ?? 0
            )
        )
        -
        5.25
    )
    <
    0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * CHRONOLOGICAL ORDERING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Chronological Ordering<br>";
echo "============================================<br>";


$result =
    $model
        ->projectFixtures(
            $player,
            $form,
            [
                [
                    'id' =>
                        30,

                    'gameweek' =>
                        4,

                    'kickoff_time' =>
                        '2026-09-12 15:00:00'
                ],
                [
                    'id' =>
                        10,

                    'gameweek' =>
                        2,

                    'kickoff_time' =>
                        '2026-08-29 15:00:00'
                ],
                [
                    'id' =>
                        20,

                    'gameweek' =>
                        3,

                    'kickoff_time' =>
                        '2026-09-05 15:00:00'
                ]
            ],
            [
                'fixture:10' => [
                    'test_projected_points' =>
                        2.0
                ],
                'fixture:20' => [
                    'test_projected_points' =>
                        3.0
                ],
                'fixture:30' => [
                    'test_projected_points' =>
                        4.0
                ]
            ]
        );


$orderedGameweeks =
    array_map(
        function (
            array $fixture
        ): ?int {

            return
                isset(
                    $fixture[
                        'gameweek'
                    ]
                )
                    ? (int) $fixture[
                        'gameweek'
                    ]
                    : null;
        },
        $result[
            'fixtures'
        ]
        ?? []
    );


multiGwCheck(
    'Fixtures are returned in chronological gameweek order',
    $orderedGameweeks
    ===
    [
        2,
        3,
        4
    ]
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * BLANK GAMEWEEK
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Blank Gameweek<br>";
echo "============================================<br>";


$result =
    $model
        ->projectFixtures(
            $player,
            $form,
            [
                [
                    'id' =>
                        10,

                    'gameweek' =>
                        2
                ],
                [
                    'id' =>
                        30,

                    'gameweek' =>
                        4
                ]
            ],
            [
                'fixture:10' => [
                    'test_projected_points' =>
                        4.0
                ],
                'fixture:30' => [
                    'test_projected_points' =>
                        6.0
                ]
            ]
        );


multiGwCheck(
    'Blank GW3 is preserved as an explicit blank gameweek',
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
            $result[
                'gameweeks'
            ][
                3
            ][
                'fixture_count'
            ]
            ?? -1
        )
    )
    ===
    0
    &&
    (
        $result[
            'gameweeks'
        ][
            3
        ][
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
    &&
    (
        $result[
            'gameweeks'
        ][
            3
        ][
            'fixtures'
        ]
        ?? null
    )
    ===
    []
    &&
    abs(
        (
            (float) (
                $result[
                    'gameweeks'
                ][
                    3
                ][
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


multiGwCheck(
    'Next-3 total includes GW2 and GW4 around blank GW3',
    abs(
        (
            (float) (
                $result[
                    'totals'
                ][
                    'next_3'
                ]
                ?? 0
            )
        )
        -
        10.0
    )
    <
    0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * DOUBLE GAMEWEEK
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Double Gameweek<br>";
echo "============================================<br>";


$result =
    $model
        ->projectFixtures(
            $player,
            $form,
            [
                [
                    'id' =>
                        10,

                    'gameweek' =>
                        2,

                    'kickoff_time' =>
                        '2026-08-29 12:30:00'
                ],
                [
                    'id' =>
                        11,

                    'gameweek' =>
                        2,

                    'kickoff_time' =>
                        '2026-09-01 19:45:00'
                ]
            ],
            [
                'fixture:10' => [
                    'test_projected_points' =>
                        4.25
                ],
                'fixture:11' => [
                    'test_projected_points' =>
                        3.75
                ]
            ]
        );


multiGwCheck(
    'Double Gameweek preserves both fixture projections',
    count(
        $result[
            'fixtures'
        ]
        ?? []
    )
    ===
    2
);


multiGwCheck(
    'Double Gameweek records two fixtures in one gameweek',
    (
        (int) (
            $result[
                'gameweeks'
            ][
                2
            ][
                'fixture_count'
            ]
            ?? 0
        )
    )
    ===
    2
);


multiGwCheck(
    'Double Gameweek sums both fixture projections',
    abs(
        (
            (float) (
                $result[
                    'gameweeks'
                ][
                    2
                ][
                    'projected_points'
                ]
                ?? 0
            )
        )
        -
        8.0
    )
    <
    0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * HORIZON TOTALS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Horizon Totals<br>";
echo "============================================<br>";


$fixtures =
    [];


$contexts =
    [];


for (
    $gameweek = 2;
    $gameweek <= 7;
    $gameweek++
) {

    $fixtureId =
        100
        +
        $gameweek;


    $fixtures[] = [

        'id' =>
            $fixtureId,

        'gameweek' =>
            $gameweek
    ];


    $contexts[
        'fixture:'
        . $fixtureId
    ] = [

        'test_projected_points' =>
            (float) $gameweek
    ];
}


$result =
    $model
        ->projectFixtures(
            $player,
            $form,
            $fixtures,
            $contexts
        );


multiGwCheck(
    'Next-3 total uses first three gameweeks',
    abs(
        (
            (float) (
                $result[
                    'totals'
                ][
                    'next_3'
                ]
                ?? 0
            )
        )
        -
        (
            2
            +
            3
            +
            4
        )
    )
    <
    0.001
);


multiGwCheck(
    'Next-5 total uses first five gameweeks',
    abs(
        (
            (float) (
                $result[
                    'totals'
                ][
                    'next_5'
                ]
                ?? 0
            )
        )
        -
        (
            2
            +
            3
            +
            4
            +
            5
            +
            6
        )
    )
    <
    0.001
);


multiGwCheck(
    'Next-6 total uses first six gameweeks',
    abs(
        (
            (float) (
                $result[
                    'totals'
                ][
                    'next_6'
                ]
                ?? 0
            )
        )
        -
        (
            2
            +
            3
            +
            4
            +
            5
            +
            6
            +
            7
        )
    )
    <
    0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * MISSING FIXTURE CONTEXT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Missing Fixture Context<br>";
echo "============================================<br>";


$result =
    $model
        ->projectFixtures(
            $player,
            $form,
            [
                [
                    'id' =>
                        10,

                    'gameweek' =>
                        2
                ],
                [
                    'id' =>
                        20,

                    'gameweek' =>
                        3
                ]
            ],
            [
                'fixture:10' => [
                    'test_projected_points' =>
                        4.0
                ]
            ]
        );


multiGwCheck(
    'Missing fixture context remains explicit',
    (
        $result[
            'fixtures'
        ][
            1
        ][
            'status'
        ]
        ?? null
    )
    ===
    'Missing Fixture Context'
);


multiGwCheck(
    'Missing fixture context does not receive projected points',
    (
        $result[
            'fixtures'
        ][
            1
        ][
            'projected_points'
        ]
        ?? null
    )
    ===
    null
);


multiGwCheck(
    'Missing fixture context is excluded from gameweek projection totals',
    !isset(
        $result[
            'gameweeks'
        ][
            3
        ]
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * PROJECTION CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Projection Contract<br>";
echo "============================================<br>";


$result =
    $model
        ->projectFixtures(
            $player,
            $form,
            [
                [
                    'id' =>
                        10,

                    'gameweek' =>
                        2
                ]
            ],
            [
                'fixture:10' => [

                    'test_projected_points' =>
                        5.0,

                    'test_projected_minutes' =>
                        82.0,

                    'fixture_opportunity' =>
                        72.0
                ]
            ]
        );


$fixtureProjection =
    $result[
        'fixtures'
    ][
        0
    ]
    ?? [];


foreach (
    [
        'fixture_id',
        'gameweek',
        'status',
        'projected_points',
        'projected_minutes',
        'projection_confidence_percent',
        'projection_confidence_label',
        'components',
        'inputs'
    ]
    as $field
) {

    multiGwCheck(
        'Fixture projection exposes '
        . $field,
        array_key_exists(
            $field,
            $fixtureProjection
        )
    );
}


multiGwCheck(
    'Fixture projection preserves v0.29 component output',
    isset(
        $fixtureProjection[
            'components'
        ][
            'appearance'
        ]
    )
);


multiGwCheck(
    'Fixture projection preserves v0.29 input output',
    isset(
        $fixtureProjection[
            'inputs'
        ][
            'fixture_opportunity'
        ]
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * AVAILABILITY METADATA PRESERVATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Availability Metadata Preservation<br>";
echo "============================================<br>";


$result =
    $model
        ->projectFixtures(
            $player,
            $form,
            [
                [
                    'id' =>
                        10,

                    'gameweek' =>
                        2
                ],
                [
                    'id' =>
                        20,

                    'gameweek' =>
                        3
                ]
            ],
            [
                'fixture:10' => [

                    'test_projected_points' =>
                        4.0,

                    'test_projected_minutes' =>
                        45.0,

                    'test_chance_of_playing' =>
                        75.0
                ],

                'fixture:20' => [

                    'test_projected_points' =>
                        0.0,

                    'test_projected_minutes' =>
                        0.0,

                    'test_chance_of_playing' =>
                        0.0
                ]
            ]
        );


$firstAvailabilityProjection =
    $result[
        'fixtures'
    ][
        0
    ]
    ?? [];


$secondAvailabilityProjection =
    $result[
        'fixtures'
    ][
        1
    ]
    ?? [];


multiGwCheck(
    'Fixture projection exposes chance of playing',
    array_key_exists(
        'chance_of_playing',
        $firstAvailabilityProjection
    )
);


multiGwCheck(
    'Fixture projection preserves uncertain availability percentage',
    isset(
        $firstAvailabilityProjection[
            'chance_of_playing'
        ]
    )
    &&
    abs(
        (
            (float) $firstAvailabilityProjection[
                'chance_of_playing'
            ]
        )
        -
        75.0
    )
    <
    0.001
);


multiGwCheck(
    'Fixture projection preserves zero percent availability',
    array_key_exists(
        'chance_of_playing',
        $secondAvailabilityProjection
    )
    &&
    is_numeric(
        $secondAvailabilityProjection[
            'chance_of_playing'
        ]
        ?? null
    )
    &&
    abs(
        (
            (float) $secondAvailabilityProjection[
                'chance_of_playing'
            ]
        )
        -
        0.0
    )
    <
    0.001
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Multi-Gameweek Expected Points Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}