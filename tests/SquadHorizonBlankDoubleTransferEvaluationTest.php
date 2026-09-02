<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Squad Horizon Blank & Double Transfer Evaluation Test<br>';

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

function horizonTransferCheck(
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


function horizonTransferHeading(
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
 * BASE SQUAD
 * ============================================================
 *
 * Legal 15-player squad:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * Player 8 is the transfer-out player.
 *
 * GW2: Normal  = 6.0 xP
 * GW3: Blank   = 0.0 xP
 * GW4: Normal  = 6.0 xP
 *
 * Player 8 therefore contributes 12.0 projected points across
 * the three-gameweek horizon before the transfer.
 */

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


    $normalPoints =
        $basePoints[
            $playerNumber
        ];


    $gameweeks =
        [];


    foreach (
        [2, 3, 4]
        as $gameweek
    ) {

        /*
         * Player 8 has the controlled Blank Gameweek.
         */
        if (
            $playerNumber === 8
            &&
            $gameweek === 3
        ) {

            $gameweeks[
                $gameweek
            ] =
                $buildProjection(
                    3,
                    0.0,
                    $teamId,
                    null,
                    'Blank',
                    []
                );

            continue;
        }


        $opponentTeamId =
            300
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
                $normalPoints,
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
                ? 'Blank Midfielder'
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


/*
 * ============================================================
 * REPLACEMENT PLAYER
 * ============================================================
 *
 * Player 108 is a MID replacing Player 8.
 *
 * GW2: Normal  = 5.0 xP
 * GW3: Normal  = 5.0 xP
 * GW4: Double  = 9.0 xP
 *
 * Horizon total = 19.0 xP.
 *
 * This is intentionally important:
 *
 * GW2 alone makes the transfer look worse:
 *
 *     incoming 5.0
 *     outgoing 6.0
 *
 * But across the complete horizon the incoming player gains:
 *
 *     +5.0 from covering the Blank GW
 *     +3.0 from the Double GW
 *
 * The horizon should therefore identify the transfer as
 * beneficial overall.
 */

$replacement =
    [

        'player_id' =>
            108,

        'name' =>
            'Blank Double Replacement',

        'position' =>
            'MID',

        'team_id' =>
            208,

        'gameweeks' => [

            2 =>
                $buildProjection(
                    2,
                    5.0,
                    208,
                    402,
                    'Normal',
                    [
                        [
                            'fixture_id' =>
                                2108,

                            'gameweek' =>
                                2,

                            'opponent_team_id' =>
                                402,

                            'is_home' =>
                                true
                        ]
                    ]
                ),

            3 =>
                $buildProjection(
                    3,
                    5.0,
                    208,
                    403,
                    'Normal',
                    [
                        [
                            'fixture_id' =>
                                3108,

                            'gameweek' =>
                                3,

                            'opponent_team_id' =>
                                403,

                            'is_home' =>
                                false
                        ]
                    ]
                ),

            4 =>
                $buildProjection(
                    4,
                    9.0,
                    208,
                    null,
                    'Double',
                    [
                        [
                            'fixture_id' =>
                                4108,

                            'gameweek' =>
                                4,

                            'opponent_team_id' =>
                                404,

                            'is_home' =>
                                true
                        ],

                        [
                            'fixture_id' =>
                                4109,

                            'gameweek' =>
                                4,

                            'opponent_team_id' =>
                                405,

                            'is_home' =>
                                false
                        ]
                    ]
                )
        ]
    ];


/*
 * ============================================================
 * BUILD BEFORE / AFTER HORIZONS
 * ============================================================
 */

$model =
    new SquadHorizonIntelligence();


$before =
    $model->buildHorizon(
        $squad,
        3
    );


$afterSquad =
    [];


foreach (
    $squad
    as $player
) {

    if (
        (
            $player[
                'player_id'
            ]
            ?? null
        )
        ===
        8
    ) {

        $afterSquad[] =
            $replacement;

        continue;
    }


    $afterSquad[] =
        $player;
}


$after =
    $model->buildHorizon(
        $afterSquad,
        3
    );


/*
 * ============================================================
 * SCENARIO A
 * BEFORE-TRANSFER SCHEDULE
 * ============================================================
 */

horizonTransferHeading(
    'Scenario A: Before-Transfer Schedule'
);


$beforeGw2 =
    $before[
        'gameweeks'
    ][2]
    ?? [];


$beforeGw3 =
    $before[
        'gameweeks'
    ][3]
    ?? [];


$beforeGw4 =
    $before[
        'gameweeks'
    ][4]
    ?? [];


$findPlayer =
    static function (
        array $gameweek,
        int $playerId
    ): ?array {

        foreach (
            $gameweek[
                'players'
            ]
            ?? []
            as $player
        ) {

            if (
                (
                    $player[
                        'player_id'
                    ]
                    ?? null
                )
                ===
                $playerId
            ) {

                return
                    $player;
            }
        }


        return
            null;
    };


$beforePlayer8Gw2 =
    $findPlayer(
        $beforeGw2,
        8
    );


$beforePlayer8Gw3 =
    $findPlayer(
        $beforeGw3,
        8
    );


$beforePlayer8Gw4 =
    $findPlayer(
        $beforeGw4,
        8
    );


horizonTransferCheck(
    'Outgoing Player 8 is Normal in GW2',
    (
        $beforePlayer8Gw2[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
);


horizonTransferCheck(
    'Outgoing Player 8 is Blank in GW3',
    (
        $beforePlayer8Gw3[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
);


horizonTransferCheck(
    'Outgoing Player 8 has zero fixtures in Blank GW3',
    (
        $beforePlayer8Gw3[
            'fixture_count'
        ]
        ?? null
    )
    ===
    0
);


horizonTransferCheck(
    'Outgoing Player 8 has 0.0 xP in Blank GW3',
    (
        $beforePlayer8Gw3[
            'projected_points'
        ]
        ?? null
    )
    ===
    0.0
);


horizonTransferCheck(
    'Outgoing Player 8 returns to Normal in GW4',
    (
        $beforePlayer8Gw4[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * AFTER-TRANSFER SCHEDULE
 * ============================================================
 */

horizonTransferHeading(
    'Scenario B: Replacement Schedule'
);


$afterGw2 =
    $after[
        'gameweeks'
    ][2]
    ?? [];


$afterGw3 =
    $after[
        'gameweeks'
    ][3]
    ?? [];


$afterGw4 =
    $after[
        'gameweeks'
    ][4]
    ?? [];


$replacementGw2 =
    $findPlayer(
        $afterGw2,
        108
    );


$replacementGw3 =
    $findPlayer(
        $afterGw3,
        108
    );


$replacementGw4 =
    $findPlayer(
        $afterGw4,
        108
    );


horizonTransferCheck(
    'Replacement is Normal in GW2',
    (
        $replacementGw2[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
);


horizonTransferCheck(
    'Replacement is Normal during outgoing player Blank GW3',
    (
        $replacementGw3[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
);


horizonTransferCheck(
    'Replacement has one fixture in GW3',
    (
        $replacementGw3[
            'fixture_count'
        ]
        ?? null
    )
    ===
    1
);


horizonTransferCheck(
    'Replacement is Double in GW4',
    (
        $replacementGw4[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


horizonTransferCheck(
    'Replacement preserves both DGW fixtures',
    count(
        $replacementGw4[
            'fixtures'
        ]
        ?? []
    )
    ===
    2
);


horizonTransferCheck(
    'Replacement DGW aggregate opponent remains null',
    (
        $replacementGw4[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    null
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * TRANSFER EFFECT ON STARTING XI
 * ============================================================
 */

horizonTransferHeading(
    'Scenario C: Starting XI Transfer Effect'
);


$getStartingIds =
    static function (
        array $gameweek
    ): array {

        return
            array_map(
                static function (
                    array $player
                ): int {

                    return
                        (int) (
                            $player[
                                'player_id'
                            ]
                            ?? 0
                        );
                },
                $gameweek[
                    'starting_xi'
                ]
                ?? []
            );
    };


$beforeGw2StartingIds =
    $getStartingIds(
        $beforeGw2
    );


$beforeGw3StartingIds =
    $getStartingIds(
        $beforeGw3
    );


$beforeGw4StartingIds =
    $getStartingIds(
        $beforeGw4
    );


$afterGw2StartingIds =
    $getStartingIds(
        $afterGw2
    );


$afterGw3StartingIds =
    $getStartingIds(
        $afterGw3
    );


$afterGw4StartingIds =
    $getStartingIds(
        $afterGw4
    );


horizonTransferCheck(
    'Outgoing Player 8 starts in Normal GW2',
    in_array(
        8,
        $beforeGw2StartingIds,
        true
    )
);


horizonTransferCheck(
    'Outgoing Player 8 is benched in Blank GW3',
    !in_array(
        8,
        $beforeGw3StartingIds,
        true
    )
);


horizonTransferCheck(
    'Outgoing Player 8 starts again in Normal GW4',
    in_array(
        8,
        $beforeGw4StartingIds,
        true
    )
);


horizonTransferCheck(
    'Replacement starts in GW2',
    in_array(
        108,
        $afterGw2StartingIds,
        true
    )
);


horizonTransferCheck(
    'Replacement starts during GW3 Blank coverage',
    in_array(
        108,
        $afterGw3StartingIds,
        true
    )
);


horizonTransferCheck(
    'Replacement starts in Double GW4',
    in_array(
        108,
        $afterGw4StartingIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * HORIZON TRANSFER EVALUATION CONTRACT
 * ============================================================
 *
 * This is the new v0.33 contract.
 *
 * We want SquadHorizonIntelligence to compare a transfer over
 * the complete planning horizon rather than judging only the
 * immediate gameweek.
 */

horizonTransferHeading(
    'Scenario D: Horizon Transfer Evaluation'
);


$transferEvaluation =
    $model->evaluateTransfer(
        $squad,
        8,
        $replacement,
        3
    );


horizonTransferCheck(
    'Transfer evaluation returns Available status',
    (
        $transferEvaluation[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


horizonTransferCheck(
    'Transfer evaluation identifies outgoing Player 8',
    (
        $transferEvaluation[
            'outgoing_player_id'
        ]
        ?? null
    )
    ===
    8
);


horizonTransferCheck(
    'Transfer evaluation identifies incoming Player 108',
    (
        $transferEvaluation[
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    108
);


horizonTransferCheck(
    'Transfer evaluation covers three gameweeks',
    (
        $transferEvaluation[
            'gameweek_count'
        ]
        ?? null
    )
    ===
    3
);


$transferGameweeks =
    $transferEvaluation[
        'gameweeks'
    ]
    ?? [];


horizonTransferCheck(
    'Transfer evaluation exposes GW2',
    isset(
        $transferGameweeks[
            2
        ]
    )
);


horizonTransferCheck(
    'Transfer evaluation exposes GW3',
    isset(
        $transferGameweeks[
            3
        ]
    )
);


horizonTransferCheck(
    'Transfer evaluation exposes GW4',
    isset(
        $transferGameweeks[
            4
        ]
    )
);


horizonTransferCheck(
    'GW2 transfer effect is negative 1.0 xP',
    (
        $transferGameweeks[
            2
        ]['starting_xi_xp_gain']
        ?? null
    )
    ===
    -1.0
);


horizonTransferCheck(
    'GW3 Blank coverage produces positive Starting XI gain',
    (
        $transferGameweeks[
            3
        ]['starting_xi_xp_gain']
        ?? null
    )
    >
    0.0
);


horizonTransferCheck(
    'GW4 Double Gameweek produces positive Starting XI gain',
    (
        $transferGameweeks[
            4
        ]['starting_xi_xp_gain']
        ?? null
    )
    >
    0.0
);


horizonTransferCheck(
    'Transfer evaluation records outgoing Blank schedule in GW3',
    (
        $transferGameweeks[
            3
        ]['outgoing_schedule_type']
        ?? null
    )
    ===
    'Blank'
);


horizonTransferCheck(
    'Transfer evaluation records incoming Normal schedule in GW3',
    (
        $transferGameweeks[
            3
        ]['incoming_schedule_type']
        ?? null
    )
    ===
    'Normal'
);


horizonTransferCheck(
    'Transfer evaluation records incoming Double schedule in GW4',
    (
        $transferGameweeks[
            4
        ]['incoming_schedule_type']
        ?? null
    )
    ===
    'Double'
);


horizonTransferCheck(
    'Overall horizon Starting XI gain is positive',
    (
        $transferEvaluation[
            'starting_xi_xp_gain'
        ]
        ?? null
    )
    >
    0.0
);


horizonTransferCheck(
    'Beneficial BGW/DGW transfer is classified as Improvement',
    (
        $transferEvaluation[
            'evaluation'
        ]
        ?? null
    )
    ===
    'Improvement'
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