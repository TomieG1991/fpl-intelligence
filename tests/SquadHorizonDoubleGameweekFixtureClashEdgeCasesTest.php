<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Squad Horizon Double Gameweek Fixture Clash Edge Cases Test<br>';

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

function squadHorizonDgwClashEdgeCheck(
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


function squadHorizonDgwClashEdgeHeading(
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
 * SQUAD BUILDER
 * ============================================================
 *
 * Build a legal 15-player FPL squad:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * Players 8 and 9 are the controlled DGW midfielders.
 * Their high projections guarantee that both are selected in
 * the Starting XI.
 *
 * All other players use unrelated Normal Gameweek fixtures so
 * they cannot accidentally create another fixture clash.
 */

function buildDgwClashEdgeSquad(
    array $player8Fixtures,
    array $player9Fixtures
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


        if (
            $playerNumber === 8
        ) {

            $squad[] = [

                'player_id' =>
                    8,

                'name' =>
                    'DGW Player A',

                'position' =>
                    'MID',

                'team_id' =>
                    1,

                'gameweeks' => [

                    2 => [

                        'gameweek' =>
                            2,

                        'projected_points' =>
                            10.0,

                        'team_id' =>
                            1,

                        'opponent_team_id' =>
                            null,

                        'fixture_count' =>
                            count(
                                $player8Fixtures
                            ),

                        'schedule_type' =>
                            'Double',

                        'fixtures' =>
                            $player8Fixtures
                    ]
                ]
            ];


            continue;
        }


        if (
            $playerNumber === 9
        ) {

            $squad[] = [

                'player_id' =>
                    9,

                'name' =>
                    'DGW Player B',

                'position' =>
                    'MID',

                'team_id' =>
                    2,

                'gameweeks' => [

                    2 => [

                        'gameweek' =>
                            2,

                        'projected_points' =>
                            9.0,

                        'team_id' =>
                            2,

                        'opponent_team_id' =>
                            null,

                        'fixture_count' =>
                            count(
                                $player9Fixtures
                            ),

                        'schedule_type' =>
                            'Double',

                        'fixtures' =>
                            $player9Fixtures
                    ]
                ]
            ];


            continue;
        }


        $teamId =
            100
            +
            $playerNumber;


        $opponentTeamId =
            200
            +
            $playerNumber;


        $fixtureId =
            3000
            +
            $playerNumber;


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

            'gameweeks' => [

                2 => [

                    'gameweek' =>
                        2,

                    'projected_points' =>
                        7.0
                        -
                        (
                            $playerNumber
                            *
                            0.1
                        ),

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

                            'fpl_fixture_id' =>
                                4000
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
}


/*
 * ============================================================
 * SCENARIO A
 * NO RELATIONSHIP BETWEEN DGW PLAYERS
 * ============================================================
 *
 * Player A:
 *
 * Team 1 → Teams 3 and 4
 *
 * Player B:
 *
 * Team 2 → Teams 5 and 6
 *
 * Neither player faces the other's team.
 */

squadHorizonDgwClashEdgeHeading(
    'Scenario A: Unrelated Double Gameweek Players'
);


$scenarioASquad =
    buildDgwClashEdgeSquad(

        [
            [
                'fixture_id' =>
                    501,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    3,

                'is_home' =>
                    true
            ],

            [
                'fixture_id' =>
                    502,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    4,

                'is_home' =>
                    false
            ]
        ],

        [
            [
                'fixture_id' =>
                    503,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    5,

                'is_home' =>
                    true
            ],

            [
                'fixture_id' =>
                    504,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    6,

                'is_home' =>
                    false
            ]
        ]
    );


$scenarioAResult =
    (
        new SquadHorizonIntelligence()
    )->buildHorizon(
        $scenarioASquad,
        1
    );


$scenarioAStartingXI =
    $scenarioAResult[
        'gameweeks'
    ][2][
        'starting_xi'
    ]
    ?? [];


$scenarioAStartingIds =
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
        $scenarioAStartingXI
    );


$scenarioAClashes =
    $scenarioAResult[
        'fixture_clashes'
    ]
    ?? [];


$scenarioAGw2 =
    $scenarioAClashes[
        'gameweeks'
    ][2]
    ?? [];


squadHorizonDgwClashEdgeCheck(
    'Scenario A produces eleven starters',
    count(
        $scenarioAStartingXI
    )
    ===
    11
);


squadHorizonDgwClashEdgeCheck(
    'Scenario A includes both controlled DGW players in Starting XI',
    in_array(
        8,
        $scenarioAStartingIds,
        true
    )
    &&
    in_array(
        9,
        $scenarioAStartingIds,
        true
    )
);


squadHorizonDgwClashEdgeCheck(
    'Unrelated DGW players do not create a clash',
    (
        $scenarioAGw2[
            'clash_count'
        ]
        ?? null
    )
    ===
    0
);


squadHorizonDgwClashEdgeCheck(
    'Unrelated DGW gameweek exposes no clash rows',
    (
        $scenarioAGw2[
            'clashes'
        ]
        ?? null
    )
    ===
    []
);


squadHorizonDgwClashEdgeCheck(
    'Unrelated DGW players do not increase horizon clash total',
    (
        $scenarioAClashes[
            'total_clash_count'
        ]
        ?? null
    )
    ===
    0
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * ONE-WAY RELATIONSHIP IS NOT A CLASH
 * ============================================================
 *
 * Player A:
 *
 * Team 1 → Team 2 and Team 3
 *
 * Player B:
 *
 * Team 2 → Team 4 and Team 5
 *
 * Player A claims to face Player B's team.
 *
 * Player B does NOT claim to face Player A's team.
 *
 * The reciprocal requirement must therefore reject this as a
 * clash.
 */

squadHorizonDgwClashEdgeHeading(
    'Scenario B: One-Way Double Gameweek Relationship'
);


$scenarioBSquad =
    buildDgwClashEdgeSquad(

        [
            [
                'fixture_id' =>
                    601,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    2,

                'is_home' =>
                    true
            ],

            [
                'fixture_id' =>
                    602,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    3,

                'is_home' =>
                    false
            ]
        ],

        [
            [
                'fixture_id' =>
                    603,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    4,

                'is_home' =>
                    true
            ],

            [
                'fixture_id' =>
                    604,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    5,

                'is_home' =>
                    false
            ]
        ]
    );


$scenarioBResult =
    (
        new SquadHorizonIntelligence()
    )->buildHorizon(
        $scenarioBSquad,
        1
    );


$scenarioBClashes =
    $scenarioBResult[
        'fixture_clashes'
    ]
    ?? [];


$scenarioBGw2 =
    $scenarioBClashes[
        'gameweeks'
    ][2]
    ?? [];


squadHorizonDgwClashEdgeCheck(
    'One-way DGW relationship does not create a clash',
    (
        $scenarioBGw2[
            'clash_count'
        ]
        ?? null
    )
    ===
    0
);


squadHorizonDgwClashEdgeCheck(
    'One-way DGW relationship exposes no clash rows',
    (
        $scenarioBGw2[
            'clashes'
        ]
        ?? null
    )
    ===
    []
);


squadHorizonDgwClashEdgeCheck(
    'One-way DGW relationship leaves horizon clash total at zero',
    (
        $scenarioBClashes[
            'total_clash_count'
        ]
        ?? null
    )
    ===
    0
);


squadHorizonDgwClashEdgeCheck(
    'One-way DGW relationship reports no gameweek with clashes',
    (
        $scenarioBClashes[
            'gameweeks_with_clashes'
        ]
        ?? null
    )
    ===
    0
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * RECIPROCAL TEAMS ACROSS DIFFERENT FIXTURE IDS
 * ============================================================
 *
 * This is deliberately malformed/inconsistent fixture data:
 *
 * Player A:
 *
 * Fixture 701 — Team 1 vs Team 2
 *
 * Player B:
 *
 * Fixture 703 — Team 2 vs Team 1
 *
 * The opponent sets are reciprocal, but the fixture identities
 * disagree.
 *
 * v0.33 fixture intelligence should not manufacture a real
 * shared fixture from opponent IDs alone.
 */

squadHorizonDgwClashEdgeHeading(
    'Scenario C: Reciprocal Teams But Different Fixture IDs'
);


$scenarioCSquad =
    buildDgwClashEdgeSquad(

        [
            [
                'fixture_id' =>
                    701,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    2,

                'is_home' =>
                    true
            ],

            [
                'fixture_id' =>
                    702,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    3,

                'is_home' =>
                    false
            ]
        ],

        [
            [
                'fixture_id' =>
                    703,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    1,

                'is_home' =>
                    false
            ],

            [
                'fixture_id' =>
                    704,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    4,

                'is_home' =>
                    true
            ]
        ]
    );


$scenarioCResult =
    (
        new SquadHorizonIntelligence()
    )->buildHorizon(
        $scenarioCSquad,
        1
    );


$scenarioCClashes =
    $scenarioCResult[
        'fixture_clashes'
    ]
    ?? [];


$scenarioCGw2 =
    $scenarioCClashes[
        'gameweeks'
    ][2]
    ?? [];


squadHorizonDgwClashEdgeCheck(
    'Different fixture IDs do not manufacture a DGW clash',
    (
        $scenarioCGw2[
            'clash_count'
        ]
        ?? null
    )
    ===
    0
);


squadHorizonDgwClashEdgeCheck(
    'Different fixture IDs expose no manufactured clash rows',
    (
        $scenarioCGw2[
            'clashes'
        ]
        ?? null
    )
    ===
    []
);


squadHorizonDgwClashEdgeCheck(
    'Different fixture IDs leave horizon clash total at zero',
    (
        $scenarioCClashes[
            'total_clash_count'
        ]
        ?? null
    )
    ===
    0
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * DUPLICATE OPPONENT DOES NOT DOUBLE-COUNT PLAYER PAIR
 * ============================================================
 *
 * This synthetic case deliberately gives both players two
 * fixture rows against one another.
 *
 * Fixture 801 and Fixture 802 are both reciprocal.
 *
 * The established Squad Horizon contract counts clashing
 * PLAYER PAIRS, not football fixtures.
 *
 * Therefore Players 8 and 9 must still produce exactly one
 * clash row.
 */

squadHorizonDgwClashEdgeHeading(
    'Scenario D: Duplicate Reciprocal Opponents'
);


$scenarioDSquad =
    buildDgwClashEdgeSquad(

        [
            [
                'fixture_id' =>
                    801,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    2,

                'is_home' =>
                    true
            ],

            [
                'fixture_id' =>
                    802,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    2,

                'is_home' =>
                    false
            ]
        ],

        [
            [
                'fixture_id' =>
                    801,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    1,

                'is_home' =>
                    false
            ],

            [
                'fixture_id' =>
                    802,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    1,

                'is_home' =>
                    true
            ]
        ]
    );


$scenarioDResult =
    (
        new SquadHorizonIntelligence()
    )->buildHorizon(
        $scenarioDSquad,
        1
    );


$scenarioDClashes =
    $scenarioDResult[
        'fixture_clashes'
    ]
    ?? [];


$scenarioDGw2 =
    $scenarioDClashes[
        'gameweeks'
    ][2]
    ?? [];


$scenarioDClashRows =
    $scenarioDGw2[
        'clashes'
    ]
    ?? [];


squadHorizonDgwClashEdgeCheck(
    'Duplicate reciprocal fixtures produce one player-pair clash',
    (
        $scenarioDGw2[
            'clash_count'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonDgwClashEdgeCheck(
    'Duplicate reciprocal fixtures expose one clash row',
    count(
        $scenarioDClashRows
    )
    ===
    1
);


squadHorizonDgwClashEdgeCheck(
    'Duplicate reciprocal fixture clash identifies Players 8 and 9',
    (
        $scenarioDClashRows[
            0
        ][
            'player_ids'
        ]
        ?? null
    )
    ===
    [
        8,
        9
    ]
);


squadHorizonDgwClashEdgeCheck(
    'Duplicate reciprocal fixtures count once in horizon total',
    (
        $scenarioDClashes[
            'total_clash_count'
        ]
        ?? null
    )
    ===
    1
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