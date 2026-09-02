<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Squad Horizon Captaincy Edge Cases Test<br>';

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

function captaincyEdgeCheck(
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


function captaincyEdgeHeading(
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
 * PLAYER ID HELPER
 * ============================================================
 */

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
 * SQUAD BUILDER
 * ============================================================
 *
 * Build a normal 15-player squad:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * Player 8 and Player 13 both receive 10.0 xP.
 *
 * They are therefore equal highest-projected Starting XI
 * players.
 *
 * Captaincy should use player_id as the deterministic
 * tie-break and select Player 8.
 */

$buildCaptaincyEdgeSquad =
    static function (
        bool $includeUnselectableHighXpPlayer = false
    ): array {

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
             * ------------------------------------------------
             * SYNTHETIC UNSELECTABLE PLAYER
             * ------------------------------------------------
             *
             * Scenario B changes Player 15 to an unsupported
             * position and gives them 50.0 xP.
             *
             * SquadHorizonIntelligence cannot place this player
             * into a legal Starting XI.
             *
             * This allows us to prove captaincy is selected from
             * the Starting XI rather than the full player list.
             */

            if (
                $includeUnselectableHighXpPlayer
                &&
                $playerNumber === 15
            ) {

                $position =
                    'UNKNOWN';
            }


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
                     * Player 8 is one captaincy candidate.
                     */
                    8 =>
                        10.0,

                    /*
                     * Other midfielders
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
                     * Player 13 is the equal captaincy candidate.
                     */
                    13 =>
                        10.0,

                    14 =>
                        6.0,

                    /*
                     * Normally a low-value third forward.
                     *
                     * Scenario B overrides this to 50.0 below.
                     */
                    15 =>
                        5.0,

                    default =>
                        1.0
                };


            if (
                $includeUnselectableHighXpPlayer
                &&
                $playerNumber === 15
            ) {

                $projectedPoints =
                    50.0;
            }


            $teamId =
                100
                +
                $playerNumber;


            $opponentTeamId =
                200
                +
                $playerNumber;


            $squad[] = [

                'player_id' =>
                    $playerNumber,

                'name' =>
                    $playerNumber === 8
                        ? 'Tie Captain A'
                        : (
                            $playerNumber === 13
                                ? 'Tie Captain B'
                                : (
                                    $playerNumber === 15
                                    &&
                                    $includeUnselectableHighXpPlayer
                                        ? 'Unselectable High XP Player'
                                        : 'Player '
                                            . $playerNumber
                                )
                        ),

                'position' =>
                    $position,

                'team_id' =>
                    $teamId,

                'gameweeks' => [

                    2 => [

                        'gameweek' =>
                            2,

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
                                    2000
                                    +
                                    $playerNumber,

                                'gameweek' =>
                                    2,

                                'opponent_team_id' =>
                                    $opponentTeamId,

                                'is_home' =>
                                    true
                            ]
                        ]
                    ]
                ]
            ];
        }


        return
            $squad;
    };


/*
 * ============================================================
 * SCENARIO A
 * DETERMINISTIC CAPTAINCY TIE-BREAK
 * ============================================================
 */

captaincyEdgeHeading(
    'Scenario A: Equal Projected Points Tie-Break'
);


$scenarioASquad =
    $buildCaptaincyEdgeSquad();


$scenarioAResult =
    (
        new SquadHorizonIntelligence()
    )->buildHorizon(
        $scenarioASquad,
        1
    );


$scenarioAGw2 =
    $scenarioAResult[
        'gameweeks'
    ][2]
    ?? [];


$scenarioAStartingXI =
    $scenarioAGw2[
        'starting_xi'
    ]
    ?? [];


$scenarioAStartingIds =
    $getPlayerIds(
        $scenarioAStartingXI
    );


$scenarioACaptain =
    $scenarioAGw2[
        'captain'
    ]
    ?? null;


captaincyEdgeCheck(
    'Scenario A selects eleven starters',
    count(
        $scenarioAStartingXI
    )
    ===
    11
);


captaincyEdgeCheck(
    'Player 8 belongs to Scenario A Starting XI',
    in_array(
        8,
        $scenarioAStartingIds,
        true
    )
);


captaincyEdgeCheck(
    'Player 13 belongs to Scenario A Starting XI',
    in_array(
        13,
        $scenarioAStartingIds,
        true
    )
);


captaincyEdgeCheck(
    'Scenario A exposes a captain row',
    is_array(
        $scenarioACaptain
    )
);


captaincyEdgeCheck(
    'Equal highest-xP captaincy candidates use lower player ID',
    (
        $scenarioACaptain[
            'player_id'
        ]
        ?? null
    )
    ===
    8
);


captaincyEdgeCheck(
    'Tie-break captain preserves 10.0 projected points',
    (
        $scenarioACaptain[
            'projected_points'
        ]
        ?? null
    )
    ===
    10.0
);


captaincyEdgeCheck(
    'Tie-break captain belongs to Starting XI',
    in_array(
        (int) (
            $scenarioACaptain[
                'player_id'
            ]
            ?? 0
        ),
        $scenarioAStartingIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * CAPTAIN MUST COME FROM STARTING XI
 * ============================================================
 *
 * Player 15 has:
 *
 * projected_points = 50.0
 * position         = UNKNOWN
 *
 * Therefore the player cannot enter a legal FPL Starting XI.
 *
 * Captaincy must completely ignore that player's much larger
 * projection and still select the best player from the XI.
 */

captaincyEdgeHeading(
    'Scenario B: Unselectable High-XP Player'
);


$scenarioBSquad =
    $buildCaptaincyEdgeSquad(
        true
    );


$scenarioBResult =
    (
        new SquadHorizonIntelligence()
    )->buildHorizon(
        $scenarioBSquad,
        1
    );


$scenarioBGw2 =
    $scenarioBResult[
        'gameweeks'
    ][2]
    ?? [];


$scenarioBPlayers =
    $scenarioBGw2[
        'players'
    ]
    ?? [];


$scenarioBStartingXI =
    $scenarioBGw2[
        'starting_xi'
    ]
    ?? [];


$scenarioBBench =
    $scenarioBGw2[
        'bench'
    ]
    ?? [];


$scenarioBStartingIds =
    $getPlayerIds(
        $scenarioBStartingXI
    );


$scenarioBBenchIds =
    $getPlayerIds(
        $scenarioBBench
    );


$scenarioBCaptain =
    $scenarioBGw2[
        'captain'
    ]
    ?? null;


$scenarioBPlayer15 =
    null;


foreach (
    $scenarioBPlayers
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
        15
    ) {

        $scenarioBPlayer15 =
            $player;

        break;
    }
}


captaincyEdgeCheck(
    'Scenario B still selects eleven legal starters',
    count(
        $scenarioBStartingXI
    )
    ===
    11
);


captaincyEdgeCheck(
    'High-xP Player 15 remains outside Starting XI',
    !in_array(
        15,
        $scenarioBStartingIds,
        true
    )
);


captaincyEdgeCheck(
    'High-xP Player 15 is placed on the bench',
    in_array(
        15,
        $scenarioBBenchIds,
        true
    )
);


captaincyEdgeCheck(
    'High-xP Player 15 preserves 50.0 projected points',
    (
        $scenarioBPlayer15[
            'projected_points'
        ]
        ?? null
    )
    ===
    50.0
);


captaincyEdgeCheck(
    'Scenario B exposes a captain row',
    is_array(
        $scenarioBCaptain
    )
);


captaincyEdgeCheck(
    'Unselectable 50.0-xP player is not captain',
    (
        $scenarioBCaptain[
            'player_id'
        ]
        ?? null
    )
    !==
    15
);


captaincyEdgeCheck(
    'Scenario B captain remains Player 8',
    (
        $scenarioBCaptain[
            'player_id'
        ]
        ?? null
    )
    ===
    8
);


captaincyEdgeCheck(
    'Scenario B captain belongs to Starting XI',
    in_array(
        (int) (
            $scenarioBCaptain[
                'player_id'
            ]
            ?? 0
        ),
        $scenarioBStartingIds,
        true
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * NO VALID STARTING XI
 * ============================================================
 *
 * Remove the forwards entirely.
 *
 * selectStartingXI() cannot construct a legal FPL formation and
 * therefore returns an empty Starting XI.
 *
 * selectCaptain() must safely return null.
 */

captaincyEdgeHeading(
    'Scenario C: No Valid Starting XI'
);


$scenarioCSquad =
    array_values(
        array_filter(
            $buildCaptaincyEdgeSquad(),
            static function (
                array $player
            ): bool {

                return
                    (
                        $player[
                            'position'
                        ]
                        ?? null
                    )
                    !==
                    'FWD';
            }
        )
    );


$scenarioCResult =
    (
        new SquadHorizonIntelligence()
    )->buildHorizon(
        $scenarioCSquad,
        1
    );


$scenarioCGw2 =
    $scenarioCResult[
        'gameweeks'
    ][2]
    ?? [];


$scenarioCStartingXI =
    $scenarioCGw2[
        'starting_xi'
    ]
    ?? null;


$scenarioCCaptain =
    array_key_exists(
        'captain',
        $scenarioCGw2
    )
        ? $scenarioCGw2[
            'captain'
        ]
        : 'missing';


captaincyEdgeCheck(
    'Scenario C cannot construct a legal Starting XI',
    $scenarioCStartingXI
    ===
    []
);


captaincyEdgeCheck(
    'Scenario C exposes captain key',
    array_key_exists(
        'captain',
        $scenarioCGw2
    )
);


captaincyEdgeCheck(
    'Empty Starting XI produces null captain',
    $scenarioCCaptain
    ===
    null
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