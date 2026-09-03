<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function horizonProjectionConfidenceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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
 * BUILD VALID 15-PLAYER SQUAD
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Squad Horizon Projection Confidence Aggregation Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$squad =
    [];


/*
 * We create a deterministic legal Starting XI for all
 * three gameweeks.
 *
 * GW3:
 *   Starting XI = 60 projected points
 *   confidence = 0.80
 *
 * GW4:
 *   Starting XI = 40 projected points
 *   confidence = 0.60
 *
 * GW5:
 *   Starting XI = 0 projected points
 *   confidence = null
 *
 * Horizon confidence should therefore ignore GW5 and be:
 *
 * ((60 × 0.80) + (40 × 0.60)) / (60 + 40)
 *
 * = (48 + 24) / 100
 * = 0.72
 */

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
     * Four deliberately weak bench players.
     */

    $isBenchPlayer =
        in_array(
            $playerNumber,
            [
                2,
                7,
                13,
                15
            ],
            true
        );


    $gw3Points =
        $isBenchPlayer
            ? 1.0
            : (
                $playerNumber === 1
                    ? 10.0
                    : 5.0
            );


    /*
     * GW4 selected XI totals 40:
     *
     * Player 1 = 0
     * Other ten starters = 4 each
     */

    $gw4Points =
        $isBenchPlayer
            ? -1.0
            : (
                $playerNumber === 1
                    ? 0.0
                    : 4.0
            );


    $squad[] = [

        'player_id' =>
            $playerNumber,

        'name' =>
            'Player '
            . $playerNumber,

        'position' =>
            $position,

        'team_id' =>
            $playerNumber,

        'gameweeks' => [

            3 => [

                'gameweek' =>
                    3,

                'projected_points' =>
                    $gw3Points,

                'projection_confidence' =>
                    0.80,

                'team_id' =>
                    $playerNumber,

                'opponent_team_id' =>
                    20
                    +
                    $playerNumber,

                'fixture_count' =>
                    1,

                'schedule_type' =>
                    'Normal',

                'fixtures' =>
                    []
            ],

            4 => [

                'gameweek' =>
                    4,

                'projected_points' =>
                    $gw4Points,

                'projection_confidence' =>
                    $playerNumber === 1
                        ? null
                        : 0.60,

                'team_id' =>
                    $playerNumber,

                'opponent_team_id' =>
                    $playerNumber === 1
                        ? null
                        : (
                            20
                            +
                            $playerNumber
                        ),

                'fixture_count' =>
                    $playerNumber === 1
                        ? 0
                        : 1,

                'schedule_type' =>
                    $playerNumber === 1
                        ? 'Blank'
                        : 'Normal',

                'fixtures' =>
                    []
            ],

            5 => [

                'gameweek' =>
                    5,

                'projected_points' =>
                    0.0,

                'projection_confidence' =>
                    null,

                'team_id' =>
                    $playerNumber,

                'opponent_team_id' =>
                    null,

                'fixture_count' =>
                    0,

                'schedule_type' =>
                    'Blank',

                'fixtures' =>
                    []
            ]
        ]
    ];
}


/*
 * ============================================================
 * BUILD HORIZON
 * ============================================================
 */

$intelligence =
    new SquadHorizonIntelligence();


$result =
    $intelligence->buildHorizon(
        $squad,
        3
    );


/*
 * ============================================================
 * Scenario A: Existing gameweek confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Existing gameweek confidence<br>';

echo
    '============================================<br>';


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


horizonProjectionConfidenceCheck(
    'Horizon remains available',
    (
        $result[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


horizonProjectionConfidenceCheck(
    'GW3 Starting XI projected points remain 60',
    isset(
        $gameweek3[
            'starting_xi_projected_points'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek3[
                'starting_xi_projected_points'
            ]
        )
        -
        60.0
    ) < 0.0001
);


horizonProjectionConfidenceCheck(
    'GW3 Starting XI confidence remains 0.80',
    isset(
        $gameweek3[
            'starting_xi_projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek3[
                'starting_xi_projection_confidence'
            ]
        )
        -
        0.80
    ) < 0.0001
);


horizonProjectionConfidenceCheck(
    'GW4 Starting XI projected points remain 40',
    isset(
        $gameweek4[
            'starting_xi_projected_points'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek4[
                'starting_xi_projected_points'
            ]
        )
        -
        40.0
    ) < 0.0001
);


horizonProjectionConfidenceCheck(
    'GW4 Starting XI confidence remains 0.60',
    isset(
        $gameweek4[
            'starting_xi_projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek4[
                'starting_xi_projection_confidence'
            ]
        )
        -
        0.60
    ) < 0.0001
);


horizonProjectionConfidenceCheck(
    'GW5 Starting XI projected points remain zero',
    isset(
        $gameweek5[
            'starting_xi_projected_points'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek5[
                'starting_xi_projected_points'
            ]
        )
        -
        0.0
    ) < 0.0001
);


horizonProjectionConfidenceCheck(
    'GW5 Starting XI confidence remains null',
    array_key_exists(
        'starting_xi_projection_confidence',
        $gameweek5
    )
    &&
    $gameweek5[
        'starting_xi_projection_confidence'
    ]
    ===
    null
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Horizon projection confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Horizon projection confidence<br>';

echo
    '============================================<br>';


horizonProjectionConfidenceCheck(
    'Horizon exposes projection confidence',
    array_key_exists(
        'projection_confidence',
        $result
    )
);


horizonProjectionConfidenceCheck(
    'Horizon projection confidence is numeric',
    isset(
        $result[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $result[
            'projection_confidence'
        ]
    )
);


horizonProjectionConfidenceCheck(
    'Horizon confidence uses projected-points weighting',
    isset(
        $result[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $result[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $result[
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