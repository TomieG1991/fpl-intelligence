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

function squadHorizonStartingXIConfidenceCheck(
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
    'Squad Horizon Starting XI Projection Confidence Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$squad =
    [];


/*
 * The legal Starting XI will become:
 *
 * 1 GK
 * 3 DEF
 * 5 MID
 * 2 FWD
 *
 * Every selected starter except Player 1 projects 5 points
 * at 0.80 confidence.
 *
 * Player 1 projects 10 points at 0.60 confidence.
 *
 * Therefore:
 *
 * total Starting XI points
 * = 10 + (10 × 5)
 * = 60
 *
 * confidence weighted points
 * = (10 × 0.60) + (50 × 0.80)
 * = 46
 *
 * Starting XI projection confidence
 * = 46 / 60
 * = 0.766666...
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
     * Make the intended eleven players clearly stronger than
     * the four bench players.
     */

    if (
        in_array(
            $playerNumber,
            [
                2,
                7,
                13,
                15
            ],
            true
        )
    ) {

        $projectedPoints =
            1.0;

    } elseif (
        $playerNumber === 1
    ) {

        $projectedPoints =
            10.0;

    } else {

        $projectedPoints =
            5.0;
    }


    $projectionConfidence =
        $playerNumber === 1
            ? 0.60
            : 0.80;


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
                    $projectedPoints,

                'projection_confidence' =>
                    $projectionConfidence,

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
        1
    );


$gameweek =
    $result[
        'gameweeks'
    ][3]
    ??
    [];


/*
 * ============================================================
 * Scenario A: Existing Starting XI behaviour
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Existing Starting XI behaviour<br>';

echo
    '============================================<br>';


squadHorizonStartingXIConfidenceCheck(
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


squadHorizonStartingXIConfidenceCheck(
    'Starting XI contains eleven players',
    count(
        $gameweek[
            'starting_xi'
        ]
        ??
        []
    )
    ===
    11
);


squadHorizonStartingXIConfidenceCheck(
    'Starting XI projected points remain 60.0',
    isset(
        $gameweek[
            'starting_xi_projected_points'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek[
                'starting_xi_projected_points'
            ]
        )
        -
        60.0
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Weighted Starting XI confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Weighted Starting XI confidence<br>';

echo
    '============================================<br>';


$expectedConfidence =
    46.0
    /
    60.0;


squadHorizonStartingXIConfidenceCheck(
    'Gameweek exposes Starting XI projection confidence',
    array_key_exists(
        'starting_xi_projection_confidence',
        $gameweek
    )
);


squadHorizonStartingXIConfidenceCheck(
    'Starting XI projection confidence is numeric',
    isset(
        $gameweek[
            'starting_xi_projection_confidence'
        ]
    )
    &&
    is_numeric(
        $gameweek[
            'starting_xi_projection_confidence'
        ]
    )
);


squadHorizonStartingXIConfidenceCheck(
    'Starting XI confidence uses projected-points weighting',
    isset(
        $gameweek[
            'starting_xi_projection_confidence'
        ]
    )
    &&
    is_numeric(
        $gameweek[
            'starting_xi_projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek[
                'starting_xi_projection_confidence'
            ]
        )
        -
        $expectedConfidence
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