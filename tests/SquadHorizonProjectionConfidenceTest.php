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

function squadHorizonProjectionConfidenceCheck(
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
    'Squad Horizon Projection Confidence Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


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
     * Player 1 deliberately contains Normal, Blank and Double
     * Gameweek confidence values.
     *
     * Remaining players provide valid projections so the normal
     * Squad Horizon selection logic can run unchanged.
     */

    if (
        $playerNumber === 1
    ) {

        $gameweeks = [

            3 => [

                'gameweek' =>
                    3,

                'projected_points' =>
                    6.5,

                'projection_confidence' =>
                    0.72,

                'team_id' =>
                    1,

                'opponent_team_id' =>
                    2,

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
                    0.0,

                'projection_confidence' =>
                    null,

                'team_id' =>
                    1,

                'opponent_team_id' =>
                    null,

                'fixture_count' =>
                    0,

                'schedule_type' =>
                    'Blank',

                'fixtures' =>
                    []
            ],

            5 => [

                'gameweek' =>
                    5,

                'projected_points' =>
                    10.0,

                'projection_confidence' =>
                    0.60,

                'team_id' =>
                    1,

                'opponent_team_id' =>
                    null,

                'fixture_count' =>
                    2,

                'schedule_type' =>
                    'Double',

                'fixtures' =>
                    []
            ]
        ];

    } else {

        $gameweeks = [

            3 => [

                'gameweek' =>
                    3,

                'projected_points' =>
                    5.0,

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
                    5.0,

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

            5 => [

                'gameweek' =>
                    5,

                'projected_points' =>
                    5.0,

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
            ]
        ];
    }


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

        'gameweeks' =>
            $gameweeks
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


$player3 =
    $gameweek3[
        'players'
    ][0]
    ??
    [];


$player4 =
    $gameweek4[
        'players'
    ][0]
    ??
    [];


$player5 =
    $gameweek5[
        'players'
    ][0]
    ??
    [];


/*
 * ============================================================
 * Scenario A: Existing Horizon behaviour
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Existing Horizon behaviour<br>';

echo
    '============================================<br>';


squadHorizonProjectionConfidenceCheck(
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


squadHorizonProjectionConfidenceCheck(
    'Horizon remains three gameweeks',
    (
        $result[
            'horizon'
        ]
        ??
        null
    )
    ===
    3
);


squadHorizonProjectionConfidenceCheck(
    'GW3 player preserves projected points 6.5',
    isset(
        $player3[
            'projected_points'
        ]
    )
    &&
    abs(
        (
            (float) $player3[
                'projected_points'
            ]
        )
        -
        6.5
    ) < 0.0001
);


squadHorizonProjectionConfidenceCheck(
    'GW3 player remains Normal',
    (
        $player3[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Normal'
);


squadHorizonProjectionConfidenceCheck(
    'GW4 player remains Blank',
    (
        $player4[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Blank'
);


squadHorizonProjectionConfidenceCheck(
    'GW5 player remains Double',
    (
        $player5[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Double'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Player/Gameweek Projection Confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Player/Gameweek Projection Confidence<br>';

echo
    '============================================<br>';


squadHorizonProjectionConfidenceCheck(
    'Normal GW preserves projection confidence 0.72',
    isset(
        $player3[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $player3[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $player3[
                'projection_confidence'
            ]
        )
        -
        0.72
    ) < 0.0001
);


squadHorizonProjectionConfidenceCheck(
    'Blank GW preserves explicit null projection confidence',
    array_key_exists(
        'projection_confidence',
        $player4
    )
    &&
    $player4[
        'projection_confidence'
    ]
    ===
    null
);


squadHorizonProjectionConfidenceCheck(
    'Double GW preserves projection confidence 0.60',
    isset(
        $player5[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $player5[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $player5[
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