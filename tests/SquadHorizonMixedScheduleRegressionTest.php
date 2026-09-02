<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Squad Horizon Mixed Schedule Regression Test<br>';

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

function mixedScheduleCheck(
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


function mixedScheduleHeading(
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
 * FIXTURE BUILDERS
 * ============================================================
 */

$normalProjection =
    static function (
        int $gameweek,
        float $projectedPoints,
        int $teamId,
        int $opponentTeamId,
        int $fixtureId,
        bool $isHome = true
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
                1,

            'schedule_type' =>
                'Normal',

            'fixtures' => [

                [
                    'fixture_id' =>
                        $fixtureId,

                    'gameweek' =>
                        $gameweek,

                    'opponent_team_id' =>
                        $opponentTeamId,

                    'is_home' =>
                        $isHome
                ]
            ]
        ];
    };


$blankProjection =
    static function (
        int $gameweek,
        int $teamId
    ): array {

        return [

            'gameweek' =>
                $gameweek,

            'projected_points' =>
                0.0,

            'team_id' =>
                $teamId,

            'opponent_team_id' =>
                null,

            'fixture_count' =>
                0,

            'schedule_type' =>
                'Blank',

            'fixtures' =>
                []
        ];
    };


$doubleProjection =
    static function (
        int $gameweek,
        float $projectedPoints,
        int $teamId,
        array $fixtures
    ): array {

        return [

            'gameweek' =>
                $gameweek,

            'projected_points' =>
                $projectedPoints,

            'team_id' =>
                $teamId,

            /*
             * Never manufacture one aggregate opponent for a
             * multi-fixture gameweek.
             */
            'opponent_team_id' =>
                null,

            'fixture_count' =>
                count(
                    $fixtures
                ),

            'schedule_type' =>
                'Double',

            'fixtures' =>
                $fixtures
        ];
    };


/*
 * ============================================================
 * CONTROLLED 15-PLAYER SQUAD
 * ============================================================
 *
 * Three-gameweek mixed schedule:
 *
 * ------------------------------------------------------------
 * Player 8 — MID, Team 108
 * ------------------------------------------------------------
 * GW2 Normal  = 10.0
 * GW3 Blank   = 0.0
 * GW4 Double  = 14.0
 *
 * This player should:
 *
 * - start and captain in GW2
 * - drop to the bench in GW3
 * - start and captain in GW4
 *
 *
 * ------------------------------------------------------------
 * Player 13 — FWD, Team 113
 * ------------------------------------------------------------
 * Normal every week = 8.0
 *
 * This player should become captain during Player 8's BGW.
 *
 *
 * ------------------------------------------------------------
 * Players 9 and 10 — DGW fixture clash in GW4
 * ------------------------------------------------------------
 *
 * Player 9:
 * Team 109 plays Team 110 in fixture 4901.
 *
 * Player 10:
 * Team 110 plays Team 109 in the SAME fixture 4901.
 *
 * Both are expected to start in GW4, so the existing
 * fixture-clash intelligence should identify their pair.
 */

$basePoints = [

    1  => 5.0,
    2  => 2.0,

    3  => 6.0,
    4  => 5.5,
    5  => 5.0,
    6  => 2.5,
    7  => 2.0,

    8  => 10.0,
    9  => 7.0,
    10 => 6.5,
    11 => 6.0,
    12 => 2.0,

    13 => 8.0,
    14 => 6.0,
    15 => 2.0
];


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
            $normalProjection(
                $gameweek,
                $basePoints[
                    $playerNumber
                ],
                $teamId,
                $opponentTeamId,
                (
                    $gameweek
                    * 1000
                )
                +
                $playerNumber,
                true
            );
    }


    /*
     * --------------------------------------------------------
     * PLAYER 8: NORMAL -> BLANK -> DOUBLE
     * --------------------------------------------------------
     */

    if ($playerNumber === 8) {

        $gameweeks[2] =
            $normalProjection(
                2,
                10.0,
                108,
                302,
                2808,
                true
            );


        $gameweeks[3] =
            $blankProjection(
                3,
                108
            );


        $gameweeks[4] =
            $doubleProjection(
                4,
                14.0,
                108,
                [
                    [
                        'fixture_id' =>
                            4808,

                        'gameweek' =>
                            4,

                        'opponent_team_id' =>
                            308,

                        'is_home' =>
                            true
                    ],

                    [
                        'fixture_id' =>
                            4809,

                        'gameweek' =>
                            4,

                        'opponent_team_id' =>
                            309,

                        'is_home' =>
                            false
                    ]
                ]
            );
    }


    /*
     * --------------------------------------------------------
     * PLAYERS 9 + 10: REAL SAME-FIXTURE DGW CLASH
     * --------------------------------------------------------
     */

    if ($playerNumber === 9) {

        $gameweeks[4] =
            $doubleProjection(
                4,
                9.0,
                109,
                [
                    [
                        'fixture_id' =>
                            4901,

                        'gameweek' =>
                            4,

                        'opponent_team_id' =>
                            110,

                        'is_home' =>
                            true
                    ],

                    [
                        'fixture_id' =>
                            4902,

                        'gameweek' =>
                            4,

                        'opponent_team_id' =>
                            111,

                        'is_home' =>
                            false
                    ]
                ]
            );
    }


    if ($playerNumber === 10) {

        $gameweeks[4] =
            $doubleProjection(
                4,
                8.5,
                110,
                [
                    [
                        'fixture_id' =>
                            4901,

                        'gameweek' =>
                            4,

                        'opponent_team_id' =>
                            109,

                        'is_home' =>
                            false
                    ],

                    [
                        'fixture_id' =>
                            4903,

                        'gameweek' =>
                            4,

                        'opponent_team_id' =>
                            112,

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
            match ($playerNumber) {

                8 =>
                    'Schedule Captain',

                9 =>
                    'Double Midfielder A',

                10 =>
                    'Double Midfielder B',

                13 =>
                    'Alternative Captain',

                default =>
                    'Player '
                    . $playerNumber
            },

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
 * BUILD MIXED HORIZON
 * ============================================================
 */

$model =
    new SquadHorizonIntelligence();


$result =
    $model->buildHorizon(
        $squad,
        3
    );


$gameweeks =
    $result[
        'gameweeks'
    ]
    ?? [];


$gw2 =
    $gameweeks[2]
    ?? [];


$gw3 =
    $gameweeks[3]
    ?? [];


$gw4 =
    $gameweeks[4]
    ?? [];


/*
 * ============================================================
 * COMMON LOOKUP HELPERS
 * ============================================================
 */

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
                    (int) (
                        $player[
                            'player_id'
                        ]
                        ?? 0
                    )
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


$player8Gw2 =
    $findPlayer(
        $gw2,
        8
    );


$player8Gw3 =
    $findPlayer(
        $gw3,
        8
    );


$player8Gw4 =
    $findPlayer(
        $gw4,
        8
    );


$player9Gw4 =
    $findPlayer(
        $gw4,
        9
    );


$player10Gw4 =
    $findPlayer(
        $gw4,
        10
    );


$gw2StartingIds =
    $getStartingIds(
        $gw2
    );


$gw3StartingIds =
    $getStartingIds(
        $gw3
    );


$gw4StartingIds =
    $getStartingIds(
        $gw4
    );


/*
 * ============================================================
 * SCENARIO A
 * HORIZON STRUCTURE
 * ============================================================
 */

mixedScheduleHeading(
    'Scenario A: Mixed Horizon Structure'
);


mixedScheduleCheck(
    'Horizon result is Available',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


mixedScheduleCheck(
    'Mixed horizon contains three gameweeks',
    count(
        $gameweeks
    )
    ===
    3
);


mixedScheduleCheck(
    'Mixed horizon exposes GW2',
    isset(
        $gameweeks[2]
    )
);


mixedScheduleCheck(
    'Mixed horizon exposes GW3',
    isset(
        $gameweeks[3]
    )
);


mixedScheduleCheck(
    'Mixed horizon exposes GW4',
    isset(
        $gameweeks[4]
    )
);


mixedScheduleCheck(
    'GW2 contains all 15 squad players',
    (
        $gw2[
            'player_count'
        ]
        ?? null
    )
    ===
    15
);


mixedScheduleCheck(
    'GW3 contains all 15 squad players despite one blank',
    (
        $gw3[
            'player_count'
        ]
        ?? null
    )
    ===
    15
);


mixedScheduleCheck(
    'GW4 contains all 15 squad players despite doubles',
    (
        $gw4[
            'player_count'
        ]
        ?? null
    )
    ===
    15
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * NORMAL / BLANK / DOUBLE SEMANTICS
 * ============================================================
 */

mixedScheduleHeading(
    'Scenario B: Normal / Blank / Double Semantics'
);


mixedScheduleCheck(
    'Player 8 is Normal in GW2',
    (
        $player8Gw2[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
);


mixedScheduleCheck(
    'Player 8 has one fixture in GW2',
    (
        $player8Gw2[
            'fixture_count'
        ]
        ?? null
    )
    ===
    1
);


mixedScheduleCheck(
    'Player 8 is Blank in GW3',
    (
        $player8Gw3[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
);


mixedScheduleCheck(
    'Player 8 has zero fixtures in GW3',
    (
        $player8Gw3[
            'fixture_count'
        ]
        ?? null
    )
    ===
    0
);


mixedScheduleCheck(
    'Player 8 Blank GW has zero projected points',
    (
        $player8Gw3[
            'projected_points'
        ]
        ?? null
    )
    ===
    0.0
);


mixedScheduleCheck(
    'Player 8 Blank GW preserves an empty fixture list',
    count(
        $player8Gw3[
            'fixtures'
        ]
        ?? []
    )
    ===
    0
);


mixedScheduleCheck(
    'Player 8 Blank GW has no manufactured opponent',
    (
        $player8Gw3[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    null
);


mixedScheduleCheck(
    'Player 8 is Double in GW4',
    (
        $player8Gw4[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


mixedScheduleCheck(
    'Player 8 has two fixtures in GW4',
    (
        $player8Gw4[
            'fixture_count'
        ]
        ?? null
    )
    ===
    2
);


mixedScheduleCheck(
    'Player 8 preserves both GW4 fixtures',
    count(
        $player8Gw4[
            'fixtures'
        ]
        ?? []
    )
    ===
    2
);


mixedScheduleCheck(
    'Player 8 Double GW has no manufactured aggregate opponent',
    (
        $player8Gw4[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    null
);


mixedScheduleCheck(
    'Player 9 is also Double in GW4',
    (
        $player9Gw4[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


mixedScheduleCheck(
    'Player 10 is also Double in GW4',
    (
        $player10Gw4[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * STARTING XI RESPONSE
 * ============================================================
 */

mixedScheduleHeading(
    'Scenario C: Starting XI Responds to Mixed Schedule'
);


mixedScheduleCheck(
    'GW2 contains exactly 11 starters',
    count(
        $gw2[
            'starting_xi'
        ]
        ?? []
    )
    ===
    11
);


mixedScheduleCheck(
    'GW3 contains exactly 11 starters',
    count(
        $gw3[
            'starting_xi'
        ]
        ?? []
    )
    ===
    11
);


mixedScheduleCheck(
    'GW4 contains exactly 11 starters',
    count(
        $gw4[
            'starting_xi'
        ]
        ?? []
    )
    ===
    11
);


mixedScheduleCheck(
    'Player 8 starts in Normal GW2',
    in_array(
        8,
        $gw2StartingIds,
        true
    )
);


mixedScheduleCheck(
    'Player 8 is benched during Blank GW3',
    !in_array(
        8,
        $gw3StartingIds,
        true
    )
);


mixedScheduleCheck(
    'Player 8 returns to Starting XI for Double GW4',
    in_array(
        8,
        $gw4StartingIds,
        true
    )
);


mixedScheduleCheck(
    'Double Player 9 starts in GW4',
    in_array(
        9,
        $gw4StartingIds,
        true
    )
);


mixedScheduleCheck(
    'Double Player 10 starts in GW4',
    in_array(
        10,
        $gw4StartingIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * CAPTAINCY RESPONSE
 * ============================================================
 */

mixedScheduleHeading(
    'Scenario D: Captaincy Responds to Mixed Schedule'
);


$gw2Captain =
    $gw2[
        'captain'
    ]
    ?? null;


$gw3Captain =
    $gw3[
        'captain'
    ]
    ?? null;


$gw4Captain =
    $gw4[
        'captain'
    ]
    ?? null;


mixedScheduleCheck(
    'GW2 selects Player 8 as captain',
    (
        $gw2Captain[
            'player_id'
        ]
        ?? null
    )
    ===
    8
);


mixedScheduleCheck(
    'GW3 does not captain blank Player 8',
    (
        $gw3Captain[
            'player_id'
        ]
        ?? null
    )
    !==
    8
);


mixedScheduleCheck(
    'GW3 selects Player 13 as captain',
    (
        $gw3Captain[
            'player_id'
        ]
        ?? null
    )
    ===
    13
);


mixedScheduleCheck(
    'GW4 selects Double Player 8 as captain',
    (
        $gw4Captain[
            'player_id'
        ]
        ?? null
    )
    ===
    8
);


mixedScheduleCheck(
    'GW2 captain belongs to Starting XI',
    in_array(
        (
            (int) (
                $gw2Captain[
                    'player_id'
                ]
                ?? 0
            )
        ),
        $gw2StartingIds,
        true
    )
);


mixedScheduleCheck(
    'GW3 captain belongs to Starting XI',
    in_array(
        (
            (int) (
                $gw3Captain[
                    'player_id'
                ]
                ?? 0
            )
        ),
        $gw3StartingIds,
        true
    )
);


mixedScheduleCheck(
    'GW4 captain belongs to Starting XI',
    in_array(
        (
            (int) (
                $gw4Captain[
                    'player_id'
                ]
                ?? 0
            )
        ),
        $gw4StartingIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E
 * DGW FIXTURE CLASH
 * ============================================================
 */

mixedScheduleHeading(
    'Scenario E: Double Gameweek Fixture Clash'
);


$fixtureClashes =
    $result[
        'fixture_clashes'
    ]
    ?? [];


$clashGameweeks =
    $fixtureClashes[
        'gameweeks'
    ]
    ?? [];


$gw4ClashData =
    $clashGameweeks[4]
    ?? [];


$gw4Clashes =
    $gw4ClashData[
        'clashes'
    ]
    ?? [];


$foundPlayer9And10Clash =
    false;


foreach (
    $gw4Clashes
    as $clash
) {

    $playerIds =
        $clash[
            'player_ids'
        ]
        ?? [];


    sort(
        $playerIds
    );


    if (
        $playerIds
        ===
        [9, 10]
    ) {

        $foundPlayer9And10Clash =
            true;

        break;
    }
}


mixedScheduleCheck(
    'Fixture clash intelligence remains available',
    isset(
        $result[
            'fixture_clashes'
        ]
    )
);


mixedScheduleCheck(
    'GW4 fixture clash data exists',
    isset(
        $clashGameweeks[4]
    )
);


mixedScheduleCheck(
    'GW4 records at least one clash',
    (
        $gw4ClashData[
            'clash_count'
        ]
        ?? 0
    )
    >=
    1
);


mixedScheduleCheck(
    'Players 9 and 10 are recognised as a GW4 clash pair',
    $foundPlayer9And10Clash
);


mixedScheduleCheck(
    'DGW clash is detected from the shared real fixture',
    $foundPlayer9And10Clash
    &&
    (
        $player9Gw4[
            'fixtures'
        ][0]['fixture_id']
        ?? null
    )
    ===
    4901
    &&
    (
        $player10Gw4[
            'fixtures'
        ][0]['fixture_id']
        ?? null
    )
    ===
    4901
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO F
 * HORIZON INTELLIGENCE REMAINS AVAILABLE
 * ============================================================
 */

mixedScheduleHeading(
    'Scenario F: Horizon Intelligence Survives Mixed Schedule'
);


mixedScheduleCheck(
    'Bench coverage remains available for GW2',
    isset(
        $gw2[
            'bench_coverage'
        ]
    )
);


mixedScheduleCheck(
    'Bench coverage remains available for GW3',
    isset(
        $gw3[
            'bench_coverage'
        ]
    )
);


mixedScheduleCheck(
    'Bench coverage remains available for GW4',
    isset(
        $gw4[
            'bench_coverage'
        ]
    )
);


mixedScheduleCheck(
    'Defensive rotation remains available',
    isset(
        $result[
            'defensive_rotation'
        ]
    )
);


mixedScheduleCheck(
    'Goalkeeper rotation remains available',
    isset(
        $result[
            'goalkeeper_rotation'
        ]
    )
);


mixedScheduleCheck(
    'Weak fixture clusters remain available',
    isset(
        $result[
            'weak_fixture_clusters'
        ]
    )
);


mixedScheduleCheck(
    'Position depth remains available',
    isset(
        $result[
            'position_depth'
        ]
    )
);


mixedScheduleCheck(
    'Repeated benching remains available',
    isset(
        $result[
            'repeated_benching'
        ]
    )
);


mixedScheduleCheck(
    'Structural weakness remains available',
    isset(
        $result[
            'structural_weakness'
        ]
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO G
 * MIXED-SCHEDULE TRANSFER EVALUATION
 * ============================================================
 *
 * Replace Player 12, who has only 2.0 xP per week, with a
 * stronger MID whose horizon is:
 *
 * GW2 Normal = 4.0
 * GW3 Normal = 5.0
 * GW4 Double = 8.0
 *
 * This checks that transfer evaluation can operate against the
 * same squad that already contains another Blank player and
 * multiple Double players.
 */

mixedScheduleHeading(
    'Scenario G: Transfer Evaluation in Mixed Horizon'
);


$replacement =
    [

        'player_id' =>
            1120,

        'name' =>
            'Mixed Horizon Replacement',

        'position' =>
            'MID',

        'team_id' =>
            220,

        'gameweeks' => [

            2 =>
                $normalProjection(
                    2,
                    4.0,
                    220,
                    602,
                    9202,
                    true
                ),

            3 =>
                $normalProjection(
                    3,
                    5.0,
                    220,
                    603,
                    9203,
                    false
                ),

            4 =>
                $doubleProjection(
                    4,
                    8.0,
                    220,
                    [
                        [
                            'fixture_id' =>
                                9204,

                            'gameweek' =>
                                4,

                            'opponent_team_id' =>
                                604,

                            'is_home' =>
                                true
                        ],

                        [
                            'fixture_id' =>
                                9205,

                            'gameweek' =>
                                4,

                            'opponent_team_id' =>
                                605,

                            'is_home' =>
                                false
                        ]
                    ]
                )
        ]
    ];


$transferEvaluation =
    $model->evaluateTransfer(
        $squad,
        12,
        $replacement,
        3
    );


mixedScheduleCheck(
    'Mixed-schedule transfer evaluation is Available',
    (
        $transferEvaluation[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


mixedScheduleCheck(
    'Mixed-schedule transfer evaluation covers three gameweeks',
    (
        $transferEvaluation[
            'gameweek_count'
        ]
        ?? null
    )
    ===
    3
);


mixedScheduleCheck(
    'Transfer evaluation identifies outgoing Player 12',
    (
        $transferEvaluation[
            'outgoing_player_id'
        ]
        ?? null
    )
    ===
    12
);


mixedScheduleCheck(
    'Transfer evaluation identifies incoming Player 1120',
    (
        $transferEvaluation[
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    1120
);


mixedScheduleCheck(
    'Transfer preserves incoming Normal GW2 schedule',
    (
        $transferEvaluation[
            'gameweeks'
        ][2]['incoming_schedule_type']
        ?? null
    )
    ===
    'Normal'
);


mixedScheduleCheck(
    'Transfer preserves incoming Normal GW3 schedule',
    (
        $transferEvaluation[
            'gameweeks'
        ][3]['incoming_schedule_type']
        ?? null
    )
    ===
    'Normal'
);


mixedScheduleCheck(
    'Transfer preserves incoming Double GW4 schedule',
    (
        $transferEvaluation[
            'gameweeks'
        ][4]['incoming_schedule_type']
        ?? null
    )
    ===
    'Double'
);


mixedScheduleCheck(
    'Mixed-schedule transfer does not produce negative horizon gain',
    (
        $transferEvaluation[
            'starting_xi_xp_gain'
        ]
        ?? null
    )
    >=
    0.0
);


mixedScheduleCheck(
    'Mixed-schedule transfer is not classified as Regression',
    (
        $transferEvaluation[
            'evaluation'
        ]
        ?? null
    )
    !==
    'Regression'
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