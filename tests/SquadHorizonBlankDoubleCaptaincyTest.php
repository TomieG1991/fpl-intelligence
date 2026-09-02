<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Squad Horizon Blank & Double Captaincy Test<br>';

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

function blankDoubleCaptaincyCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


function blankDoubleCaptaincyHeading(
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
 * CONTROLLED SQUAD
 * ============================================================
 *
 * Legal 15-player squad:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * Captaincy competition:
 *
 * Player 8 — Schedule Captain
 *
 * GW2 Normal  = 10.0 xP
 * GW3 Blank   =  0.0 xP
 * GW4 Double  = 14.0 xP
 *
 * Player 13 — Alternative Captain
 *
 * GW2 Normal  = 8.0 xP
 * GW3 Normal  = 8.0 xP
 * GW4 Normal  = 8.0 xP
 *
 * Expected captain:
 *
 * GW2 → Player 8
 * GW3 → Player 13
 * GW4 → Player 8
 *
 * This deliberately tests the same architectural principle as
 * Starting XI selection:
 *
 * schedule
 *   ↓
 * aggregated projected points
 *   ↓
 * captaincy decision
 *
 * There should be no hard-coded rule saying that a Double
 * Gameweek player must be captain.
 */

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


    /*
     * --------------------------------------------------------
     * PLAYER 8 — SCHEDULE CAPTAIN
     * --------------------------------------------------------
     */

    if (
        $playerNumber === 8
    ) {

        $squad[] = [

            'player_id' =>
                8,

            'name' =>
                'Schedule Captain',

            'position' =>
                'MID',

            'team_id' =>
                $teamId,

            'gameweeks' => [

                2 => [

                    'gameweek' =>
                        2,

                    'projected_points' =>
                        10.0,

                    'team_id' =>
                        $teamId,

                    'opponent_team_id' =>
                        202,

                    'fixture_count' =>
                        1,

                    'schedule_type' =>
                        'Normal',

                    'fixtures' => [

                        [
                            'fixture_id' =>
                                2008,

                            'gameweek' =>
                                2,

                            'opponent_team_id' =>
                                202,

                            'is_home' =>
                                true
                        ]
                    ]
                ],

                3 => [

                    'gameweek' =>
                        3,

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
                ],

                4 => [

                    'gameweek' =>
                        4,

                    'projected_points' =>
                        14.0,

                    'team_id' =>
                        $teamId,

                    'opponent_team_id' =>
                        null,

                    'fixture_count' =>
                        2,

                    'schedule_type' =>
                        'Double',

                    'fixtures' => [

                        [
                            'fixture_id' =>
                                4008,

                            'gameweek' =>
                                4,

                            'opponent_team_id' =>
                                203,

                            'is_home' =>
                                true
                        ],

                        [
                            'fixture_id' =>
                                4009,

                            'gameweek' =>
                                4,

                            'opponent_team_id' =>
                                204,

                            'is_home' =>
                                false
                        ]
                    ]
                ]
            ]
        ];


        continue;
    }


    /*
     * --------------------------------------------------------
     * PLAYER 13 — ALTERNATIVE CAPTAIN
     * --------------------------------------------------------
     */

    if (
        $playerNumber === 13
    ) {

        $gameweeks =
            [];


        foreach (
            [2, 3, 4]
            as $gameweek
        ) {

            $opponentTeamId =
                500
                +
                $gameweek;


            $gameweeks[
                $gameweek
            ] = [

                'gameweek' =>
                    $gameweek,

                'projected_points' =>
                    8.0,

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
                            13000
                            +
                            $gameweek,

                        'gameweek' =>
                            $gameweek,

                        'opponent_team_id' =>
                            $opponentTeamId,

                        'is_home' =>
                            true
                    ]
                ]
            ];
        }


        $squad[] = [

            'player_id' =>
                13,

            'name' =>
                'Alternative Captain',

            'position' =>
                'FWD',

            'team_id' =>
                $teamId,

            'gameweeks' =>
                $gameweeks
        ];


        continue;
    }


    /*
     * --------------------------------------------------------
     * ALL OTHER PLAYERS
     * --------------------------------------------------------
     *
     * These projections keep a legal XI available while ensuring
     * nobody exceeds Player 13's 8.0 xP.
     */

    $projectedPoints =
        match ($playerNumber) {

            /*
             * Goalkeepers
             */
            1 =>
                5.0,

            2 =>
                2.0,

            /*
             * Defenders
             */
            3 =>
                6.0,

            4 =>
                5.5,

            5 =>
                5.0,

            6 =>
                2.5,

            7 =>
                2.0,

            /*
             * Midfielders
             */
            9 =>
                7.0,

            10 =>
                6.5,

            11 =>
                6.0,

            12 =>
                5.5,

            /*
             * Other forwards
             */
            14 =>
                6.0,

            15 =>
                5.5,

            default =>
                1.0
        };


    $gameweeks =
        [];


    foreach (
        [2, 3, 4]
        as $gameweek
    ) {

        $opponentTeamId =
            600
            +
            (
                $playerNumber
                *
                10
            )
            +
            $gameweek;


        $gameweeks[
            $gameweek
        ] = [

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
                        (
                            $playerNumber
                            *
                            1000
                        )
                        +
                        $gameweek,

                    'gameweek' =>
                        $gameweek,

                    'opponent_team_id' =>
                        $opponentTeamId,

                    'is_home' =>
                        true
                ]
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
            $teamId,

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


$gameweeks =
    $result[
        'gameweeks'
    ]
    ?? [];


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

$getPlayer =
    static function (
        array $players,
        int $playerId
    ): ?array {

        foreach (
            $players
            as $player
        ) {

            if (
                (int) (
                    $player[
                        'player_id'
                    ]
                    ?? 0
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


$getPlayerIds =
    static function (
        array $players
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
                $players
            );
    };


/*
 * ============================================================
 * SCENARIO A
 * INPUT AND STARTING XI PRECONDITIONS
 * ============================================================
 */

blankDoubleCaptaincyHeading(
    'Scenario A: Captaincy Preconditions'
);


blankDoubleCaptaincyCheck(
    'Horizon exposes three gameweeks',
    count(
        $gameweeks
    )
    ===
    3
);


$gw2 =
    $gameweeks[2]
    ?? [];


$gw3 =
    $gameweeks[3]
    ?? [];


$gw4 =
    $gameweeks[4]
    ?? [];


$gw2StartingXI =
    $gw2[
        'starting_xi'
    ]
    ?? [];


$gw3StartingXI =
    $gw3[
        'starting_xi'
    ]
    ?? [];


$gw4StartingXI =
    $gw4[
        'starting_xi'
    ]
    ?? [];


$gw2StartingIds =
    $getPlayerIds(
        $gw2StartingXI
    );


$gw3StartingIds =
    $getPlayerIds(
        $gw3StartingXI
    );


$gw4StartingIds =
    $getPlayerIds(
        $gw4StartingXI
    );


blankDoubleCaptaincyCheck(
    'GW2 selects eleven starters',
    count(
        $gw2StartingXI
    )
    ===
    11
);


blankDoubleCaptaincyCheck(
    'GW3 selects eleven starters',
    count(
        $gw3StartingXI
    )
    ===
    11
);


blankDoubleCaptaincyCheck(
    'GW4 selects eleven starters',
    count(
        $gw4StartingXI
    )
    ===
    11
);


blankDoubleCaptaincyCheck(
    'Player 8 starts in Normal GW2',
    in_array(
        8,
        $gw2StartingIds,
        true
    )
);


blankDoubleCaptaincyCheck(
    'Player 8 is benched in Blank GW3',
    !in_array(
        8,
        $gw3StartingIds,
        true
    )
);


blankDoubleCaptaincyCheck(
    'Player 8 starts in Double GW4',
    in_array(
        8,
        $gw4StartingIds,
        true
    )
);


blankDoubleCaptaincyCheck(
    'Alternative Captain Player 13 starts in GW2',
    in_array(
        13,
        $gw2StartingIds,
        true
    )
);


blankDoubleCaptaincyCheck(
    'Alternative Captain Player 13 starts in GW3',
    in_array(
        13,
        $gw3StartingIds,
        true
    )
);


blankDoubleCaptaincyCheck(
    'Alternative Captain Player 13 starts in GW4',
    in_array(
        13,
        $gw4StartingIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * SCHEDULE SEMANTICS
 * ============================================================
 */

blankDoubleCaptaincyHeading(
    'Scenario B: Schedule Semantics'
);


$gw2Player8 =
    $getPlayer(
        $gw2[
            'players'
        ]
        ?? [],
        8
    );


$gw3Player8 =
    $getPlayer(
        $gw3[
            'players'
        ]
        ?? [],
        8
    );


$gw4Player8 =
    $getPlayer(
        $gw4[
            'players'
        ]
        ?? [],
        8
    );


blankDoubleCaptaincyCheck(
    'Player 8 schedule sequence is Normal, Blank, Double',
    (
        $gw2Player8[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
    &&
    (
        $gw3Player8[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
    &&
    (
        $gw4Player8[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


blankDoubleCaptaincyCheck(
    'Player 8 fixture-count sequence is 1, 0, 2',
    (
        $gw2Player8[
            'fixture_count'
        ]
        ?? null
    )
    ===
    1
    &&
    (
        $gw3Player8[
            'fixture_count'
        ]
        ?? null
    )
    ===
    0
    &&
    (
        $gw4Player8[
            'fixture_count'
        ]
        ?? null
    )
    ===
    2
);


blankDoubleCaptaincyCheck(
    'Player 8 projected-points sequence is 10.0, 0.0, 14.0',
    (
        $gw2Player8[
            'projected_points'
        ]
        ?? null
    )
    ===
    10.0
    &&
    (
        $gw3Player8[
            'projected_points'
        ]
        ?? null
    )
    ===
    0.0
    &&
    (
        $gw4Player8[
            'projected_points'
        ]
        ?? null
    )
    ===
    14.0
);


blankDoubleCaptaincyCheck(
    'Player 8 has no aggregate opponent in Blank GW3',
    (
        $gw3Player8[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    null
);


blankDoubleCaptaincyCheck(
    'Player 8 has no aggregate opponent in Double GW4',
    (
        $gw4Player8[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    null
);


blankDoubleCaptaincyCheck(
    'Player 8 preserves both Double Gameweek fixtures',
    count(
        $gw4Player8[
            'fixtures'
        ]
        ?? []
    )
    ===
    2
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * CAPTAINCY OUTPUT CONTRACT
 * ============================================================
 *
 * We deliberately inspect the model rather than inventing an
 * expected structure in production first.
 *
 * v0.33 requires each gameweek to expose:
 *
 * captain
 *
 * containing the selected Starting XI player row.
 *
 * If this is not currently implemented, these assertions should
 * fail cleanly and give us the RED evidence for the next small
 * production change.
 */

blankDoubleCaptaincyHeading(
    'Scenario C: Captaincy Output Contract'
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


blankDoubleCaptaincyCheck(
    'GW2 exposes a captain row',
    is_array(
        $gw2Captain
    )
);


blankDoubleCaptaincyCheck(
    'GW3 exposes a captain row',
    is_array(
        $gw3Captain
    )
);


blankDoubleCaptaincyCheck(
    'GW4 exposes a captain row',
    is_array(
        $gw4Captain
    )
);


blankDoubleCaptaincyCheck(
    'GW2 captain is Player 8',
    (
        $gw2Captain[
            'player_id'
        ]
        ?? null
    )
    ===
    8
);


blankDoubleCaptaincyCheck(
    'GW3 captain is Player 13',
    (
        $gw3Captain[
            'player_id'
        ]
        ?? null
    )
    ===
    13
);


blankDoubleCaptaincyCheck(
    'GW4 captain is Player 8',
    (
        $gw4Captain[
            'player_id'
        ]
        ?? null
    )
    ===
    8
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * CAPTAIN MUST COME FROM STARTING XI
 * ============================================================
 */

blankDoubleCaptaincyHeading(
    'Scenario D: Captain Starting XI Contract'
);


$gw2CaptainId =
    (int) (
        $gw2Captain[
            'player_id'
        ]
        ?? 0
    );


$gw3CaptainId =
    (int) (
        $gw3Captain[
            'player_id'
        ]
        ?? 0
    );


$gw4CaptainId =
    (int) (
        $gw4Captain[
            'player_id'
        ]
        ?? 0
    );


blankDoubleCaptaincyCheck(
    'GW2 captain belongs to Starting XI',
    $gw2CaptainId > 0
    &&
    in_array(
        $gw2CaptainId,
        $gw2StartingIds,
        true
    )
);


blankDoubleCaptaincyCheck(
    'GW3 captain belongs to Starting XI',
    $gw3CaptainId > 0
    &&
    in_array(
        $gw3CaptainId,
        $gw3StartingIds,
        true
    )
);


blankDoubleCaptaincyCheck(
    'GW4 captain belongs to Starting XI',
    $gw4CaptainId > 0
    &&
    in_array(
        $gw4CaptainId,
        $gw4StartingIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E
 * CAPTAINCY DECISION SEQUENCE
 * ============================================================
 */

blankDoubleCaptaincyHeading(
    'Scenario E: Cross-Gameweek Captaincy Behaviour'
);


blankDoubleCaptaincyCheck(
    'Captain sequence is Player 8, Player 13, Player 8',
    $gw2CaptainId === 8
    &&
    $gw3CaptainId === 13
    &&
    $gw4CaptainId === 8
);


blankDoubleCaptaincyCheck(
    'Blank Player 8 is never selected as GW3 captain',
    $gw3CaptainId !== 8
);


blankDoubleCaptaincyCheck(
    'DGW Player 8 becomes captain because 14.0 xP exceeds alternative 8.0 xP',
    $gw4CaptainId === 8
    &&
    (
        $gw4Player8[
            'projected_points'
        ]
        ?? null
    )
    ===
    14.0
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