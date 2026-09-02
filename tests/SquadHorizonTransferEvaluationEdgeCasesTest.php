<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Squad Horizon Transfer Evaluation Edge Cases Test<br>';

echo
    'v0.33.0 — Blank & Double Gameweek Intelligence<br>';

echo
    '============================================<br><br>';


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function transferEdgeCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

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


function transferEdgeHeading(
    string $title
): void {

    echo
        '============================================<br>';

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';

    echo
        '============================================<br>';
}


/*
 * ============================================================
 * PROJECTION BUILDER
 * ============================================================
 */

$buildProjection =
    static function (
        int $gameweek,
        float $projectedPoints,
        int $teamId,
        ?int $opponentTeamId,
        string $scheduleType,
        array $fixtures
    ): array {

        return [

            'gameweek' =>
                $gameweek,

            'projected_points' =>
                $projectedPoints,

            'team_id' =>
                $teamId,

            'opponent_team_id' =>
                $opponentTeamId,

            'fixture_count' =>
                count(
                    $fixtures
                ),

            'schedule_type' =>
                $scheduleType,

            'fixtures' =>
                $fixtures
        ];
    };


/*
 * ============================================================
 * BASE SQUAD BUILDER
 * ============================================================
 *
 * Player 8 is the controlled outgoing MID.
 *
 * All three GWs:
 * Player 8 = 6.0 xP
 *
 * The remaining squad is deliberately stable.
 */

$buildBaseSquad =
    static function () use (
        $buildProjection
    ): array {

        $squad =
            [];


        $basePoints = [

            1  => 5.0,
            2  => 2.0,

            3  => 5.5,
            4  => 5.0,
            5  => 4.5,
            6  => 2.5,
            7  => 2.0,

            8  => 6.0,
            9  => 5.5,
            10 => 5.0,
            11 => 4.5,
            12 => 2.0,

            13 => 6.5,
            14 => 5.0,
            15 => 2.0
        ];


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


            $teamId =
                100
                +
                $playerNumber;


            $gameweeks =
                [];


            foreach (
                [2, 3, 4]
                as $gameweek
            ) {

                $opponentTeamId =
                    500
                    +
                    (
                        $playerNumber
                        * 10
                    )
                    +
                    $gameweek;


                $gameweeks[
                    $gameweek
                ] =
                    $buildProjection(
                        $gameweek,
                        $basePoints[
                            $playerNumber
                        ],
                        $teamId,
                        $opponentTeamId,
                        'Normal',
                        [
                            [
                                'fixture_id' =>
                                    (
                                        $gameweek
                                        * 1000
                                    )
                                    +
                                    $playerNumber,

                                'gameweek' =>
                                    $gameweek,

                                'opponent_team_id' =>
                                    $opponentTeamId,

                                'is_home' =>
                                    true
                            ]
                        ]
                    );
            }


            $squad[] = [

                'player_id' =>
                    $playerNumber,

                'name' =>
                    $playerNumber === 8
                        ? 'Outgoing Midfielder'
                        : 'Player '
                            . $playerNumber,

                'position' =>
                    $position,

                'team_id' =>
                    $teamId,

                'gameweeks' =>
                    $gameweeks
            ];
        }


        return
            $squad;
    };


$model =
    new SquadHorizonIntelligence();


/*
 * ============================================================
 * SCENARIO A
 * NEUTRAL TRANSFER
 * ============================================================
 *
 * Incoming player matches Player 8 exactly:
 *
 * GW2 6.0
 * GW3 6.0
 * GW4 6.0
 *
 * Horizon effect should be exactly 0.0.
 */

transferEdgeHeading(
    'Scenario A: Neutral Horizon Transfer'
);


$scenarioASquad =
    $buildBaseSquad();


$scenarioAReplacement =
    [

        'player_id' =>
            108,

        'name' =>
            'Neutral Replacement',

        'position' =>
            'MID',

        'team_id' =>
            208,

        'gameweeks' => []
    ];


foreach (
    [2, 3, 4]
    as $gameweek
) {

    $opponentTeamId =
        700
        +
        $gameweek;


    $scenarioAReplacement[
        'gameweeks'
    ][
        $gameweek
    ] =
        $buildProjection(
            $gameweek,
            6.0,
            208,
            $opponentTeamId,
            'Normal',
            [
                [
                    'fixture_id' =>
                        6000
                        +
                        $gameweek,

                    'gameweek' =>
                        $gameweek,

                    'opponent_team_id' =>
                        $opponentTeamId,

                    'is_home' =>
                        false
                ]
            ]
        );
}


$scenarioA =
    $model->evaluateTransfer(
        $scenarioASquad,
        8,
        $scenarioAReplacement,
        3
    );


transferEdgeCheck(
    'Neutral transfer returns Available status',
    (
        $scenarioA[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


transferEdgeCheck(
    'Neutral transfer covers three gameweeks',
    (
        $scenarioA[
            'gameweek_count'
        ]
        ?? null
    )
    ===
    3
);


transferEdgeCheck(
    'Neutral transfer has 0.0 overall Starting XI gain',
    (
        $scenarioA[
            'starting_xi_xp_gain'
        ]
        ?? null
    )
    ===
    0.0
);


transferEdgeCheck(
    'Neutral transfer is classified as Neutral',
    (
        $scenarioA[
            'evaluation'
        ]
        ?? null
    )
    ===
    'Neutral'
);


transferEdgeCheck(
    'Neutral GW2 gain is 0.0',
    (
        $scenarioA[
            'gameweeks'
        ][2]['starting_xi_xp_gain']
        ?? null
    )
    ===
    0.0
);


transferEdgeCheck(
    'Neutral GW3 gain is 0.0',
    (
        $scenarioA[
            'gameweeks'
        ][3]['starting_xi_xp_gain']
        ?? null
    )
    ===
    0.0
);


transferEdgeCheck(
    'Neutral GW4 gain is 0.0',
    (
        $scenarioA[
            'gameweeks'
        ][4]['starting_xi_xp_gain']
        ?? null
    )
    ===
    0.0
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * WORSE BGW/DGW TRANSFER
 * ============================================================
 *
 * Incoming player:
 *
 * GW2 Normal = 5.0
 * GW3 Blank  = 0.0
 * GW4 Double = 4.0
 *
 * A Double Gameweek alone must NOT make the transfer good.
 *
 * The incoming player is worse across the full horizon.
 */

transferEdgeHeading(
    'Scenario B: Worse Blank/Double Transfer'
);


$scenarioBSquad =
    $buildBaseSquad();


$scenarioBReplacement =
    [

        'player_id' =>
            109,

        'name' =>
            'Worse Blank Double Replacement',

        'position' =>
            'MID',

        'team_id' =>
            209,

        'gameweeks' => [

            2 =>
                $buildProjection(
                    2,
                    5.0,
                    209,
                    802,
                    'Normal',
                    [
                        [
                            'fixture_id' =>
                                7102,

                            'gameweek' =>
                                2,

                            'opponent_team_id' =>
                                802,

                            'is_home' =>
                                true
                        ]
                    ]
                ),

            3 =>
                $buildProjection(
                    3,
                    0.0,
                    209,
                    null,
                    'Blank',
                    []
                ),

            4 =>
                $buildProjection(
                    4,
                    4.0,
                    209,
                    null,
                    'Double',
                    [
                        [
                            'fixture_id' =>
                                7104,

                            'gameweek' =>
                                4,

                            'opponent_team_id' =>
                                804,

                            'is_home' =>
                                true
                        ],

                        [
                            'fixture_id' =>
                                7105,

                            'gameweek' =>
                                4,

                            'opponent_team_id' =>
                                805,

                            'is_home' =>
                                false
                        ]
                    ]
                )
        ]
    ];


$scenarioB =
    $model->evaluateTransfer(
        $scenarioBSquad,
        8,
        $scenarioBReplacement,
        3
    );


transferEdgeCheck(
    'Regression transfer returns Available status',
    (
        $scenarioB[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


transferEdgeCheck(
    'Regression transfer records incoming Blank GW3',
    (
        $scenarioB[
            'gameweeks'
        ][3]['incoming_schedule_type']
        ?? null
    )
    ===
    'Blank'
);


transferEdgeCheck(
    'Regression transfer records incoming Double GW4',
    (
        $scenarioB[
            'gameweeks'
        ][4]['incoming_schedule_type']
        ?? null
    )
    ===
    'Double'
);


transferEdgeCheck(
    'Regression transfer has negative overall Starting XI gain',
    (
        $scenarioB[
            'starting_xi_xp_gain'
        ]
        ?? null
    )
    <
    0.0
);


transferEdgeCheck(
    'Regression transfer is classified as Regression',
    (
        $scenarioB[
            'evaluation'
        ]
        ?? null
    )
    ===
    'Regression'
);


transferEdgeCheck(
    'Incoming Double does not force Improvement classification',
    (
        $scenarioB[
            'evaluation'
        ]
        ?? null
    )
    !==
    'Improvement'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * INVALID INPUTS
 * ============================================================
 */

transferEdgeHeading(
    'Scenario C: Invalid Transfer Inputs'
);


$validSquad =
    $buildBaseSquad();


$validReplacement =
    [

        'player_id' =>
            110,

        'name' =>
            'Valid Replacement',

        'position' =>
            'MID',

        'team_id' =>
            210,

        'gameweeks' => [

            2 =>
                $buildProjection(
                    2,
                    6.0,
                    210,
                    902,
                    'Normal',
                    [
                        [
                            'fixture_id' =>
                                8102,

                            'gameweek' =>
                                2,

                            'opponent_team_id' =>
                                902,

                            'is_home' =>
                                true
                        ]
                    ]
                ),

            3 =>
                $buildProjection(
                    3,
                    6.0,
                    210,
                    903,
                    'Normal',
                    [
                        [
                            'fixture_id' =>
                                8103,

                            'gameweek' =>
                                3,

                            'opponent_team_id' =>
                                903,

                            'is_home' =>
                                true
                        ]
                    ]
                ),

            4 =>
                $buildProjection(
                    4,
                    6.0,
                    210,
                    904,
                    'Normal',
                    [
                        [
                            'fixture_id' =>
                                8104,

                            'gameweek' =>
                                4,

                            'opponent_team_id' =>
                                904,

                            'is_home' =>
                                true
                        ]
                    ]
                )
        ]
    ];


/*
 * Empty squad.
 */
$emptySquadResult =
    $model->evaluateTransfer(
        [],
        8,
        $validReplacement,
        3
    );


transferEdgeCheck(
    'Empty squad returns Unavailable status',
    (
        $emptySquadResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * Invalid outgoing ID.
 */
$invalidOutgoingIdResult =
    $model->evaluateTransfer(
        $validSquad,
        0,
        $validReplacement,
        3
    );


transferEdgeCheck(
    'Zero outgoing player ID returns Unavailable status',
    (
        $invalidOutgoingIdResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * Invalid horizon.
 */
$invalidHorizonResult =
    $model->evaluateTransfer(
        $validSquad,
        8,
        $validReplacement,
        0
    );


transferEdgeCheck(
    'Zero horizon returns Unavailable status',
    (
        $invalidHorizonResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * Invalid replacement player ID.
 */
$invalidReplacement =
    $validReplacement;


$invalidReplacement[
    'player_id'
] =
    0;


$invalidReplacementResult =
    $model->evaluateTransfer(
        $validSquad,
        8,
        $invalidReplacement,
        3
    );


transferEdgeCheck(
    'Invalid replacement player ID returns Unavailable status',
    (
        $invalidReplacementResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * Outgoing player does not exist.
 */
$missingOutgoingResult =
    $model->evaluateTransfer(
        $validSquad,
        9999,
        $validReplacement,
        3
    );


transferEdgeCheck(
    'Missing outgoing player returns Unavailable status',
    (
        $missingOutgoingResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


transferEdgeCheck(
    'Missing outgoing player reports a reason',
    is_string(
        $missingOutgoingResult[
            'reason'
        ]
        ?? null
    )
    &&
    (
        $missingOutgoingResult[
            'reason'
        ]
        ?? ''
    )
    !==
    ''
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