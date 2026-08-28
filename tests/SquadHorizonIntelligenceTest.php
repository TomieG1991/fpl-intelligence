<?php
require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Intelligence Test<br>";
echo "v0.32.0 — Squad Horizon & Rotation Intelligence<br>";
echo "============================================<br><br>";


/*
 * ============================================================
 * SQUAD HORIZON INTELLIGENCE TEST
 * ============================================================
 *
 * v0.32.0 — Squad Horizon & Rotation Intelligence
 *
 * This test establishes the first contract for squad-level
 * multi-gameweek intelligence.
 *
 * It deliberately uses synthetic projection data so the squad
 * horizon model can be tested independently from:
 *
 * - live FPL data
 * - fixture imports
 * - PlayerExpectedPoints
 * - MultiGameweekExpectedPoints
 *
 * Player-level projection models remain the source of truth for
 * expected points. SquadHorizonIntelligence is responsible only
 * for organising and analysing those projections at squad level.
 */


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function testResult(
    bool $condition,
    string $message
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
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

    } else {

        $failed++;

        echo
            'FAIL: '
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';
    }
}

function testHeading(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "============================================<br>";
}


/*
 * ============================================================
 * SYNTHETIC 15-PLAYER SQUAD
 * ============================================================
 *
 * Standard FPL squad structure:
 *
 * 2 Goalkeepers
 * 5 Defenders
 * 5 Midfielders
 * 3 Forwards
 *
 * Each player has projections for GW2, GW3 and GW4.
 */

$squad = [

    /*
     * --------------------------------------------------------
     * GOALKEEPERS
     * --------------------------------------------------------
     */

    [
        'player_id' => 1,
        'name' => 'Goalkeeper A',
        'position' => 'GK',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 4.5
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 3.8
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 5.1
            ]
        ]
    ],

    [
        'player_id' => 2,
        'name' => 'Goalkeeper B',
        'position' => 'GK',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 3.2
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 4.6
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 3.5
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * DEFENDERS
     * --------------------------------------------------------
     */

    [
        'player_id' => 3,
        'name' => 'Defender A',
        'position' => 'DEF',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 5.0
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 4.2
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 5.4
            ]
        ]
    ],

    [
        'player_id' => 4,
        'name' => 'Defender B',
        'position' => 'DEF',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 4.6
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 5.1
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 3.7
            ]
        ]
    ],

    [
        'player_id' => 5,
        'name' => 'Defender C',
        'position' => 'DEF',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 3.9
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 3.5
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 4.8
            ]
        ]
    ],

    [
        'player_id' => 6,
        'name' => 'Defender D',
        'position' => 'DEF',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 3.1
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 4.4
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 3.3
            ]
        ]
    ],

    [
        'player_id' => 7,
        'name' => 'Defender E',
        'position' => 'DEF',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 2.8
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 2.9
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 4.1
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * MIDFIELDERS
     * --------------------------------------------------------
     */

    [
        'player_id' => 8,
        'name' => 'Midfielder A',
        'position' => 'MID',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 7.2
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 6.8
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 7.5
            ]
        ]
    ],

    [
        'player_id' => 9,
        'name' => 'Midfielder B',
        'position' => 'MID',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 6.5
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 5.9
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 6.3
            ]
        ]
    ],

    [
        'player_id' => 10,
        'name' => 'Midfielder C',
        'position' => 'MID',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 5.4
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 6.1
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 5.7
            ]
        ]
    ],

    [
        'player_id' => 11,
        'name' => 'Midfielder D',
        'position' => 'MID',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 4.8
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 4.3
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 5.0
            ]
        ]
    ],

    [
        'player_id' => 12,
        'name' => 'Midfielder E',
        'position' => 'MID',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 3.7
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 4.0
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 3.6
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * FORWARDS
     * --------------------------------------------------------
     */

    [
        'player_id' => 13,
        'name' => 'Forward A',
        'position' => 'FWD',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 8.0
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 7.4
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 8.3
            ]
        ]
    ],

    [
        'player_id' => 14,
        'name' => 'Forward B',
        'position' => 'FWD',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 6.1
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 6.7
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 5.8
            ]
        ]
    ],

    [
        'player_id' => 15,
        'name' => 'Forward C',
        'position' => 'FWD',

        'gameweeks' => [

            2 => [
                'gameweek' => 2,
                'projected_points' => 4.2
            ],

            3 => [
                'gameweek' => 3,
                'projected_points' => 4.9
            ],

            4 => [
                'gameweek' => 4,
                'projected_points' => 4.4
            ]
        ]
    ]
];


/*
 * ============================================================
 * CREATE MODEL
 * ============================================================
 */

$model =
    new SquadHorizonIntelligence();


/*
 * ============================================================
 * BUILD THREE-GAMEWEEK HORIZON
 * ============================================================
 */

$result =
    $model->buildHorizon(
        $squad,
        3
    );


/*
 * ============================================================
 * TOP-LEVEL CONTRACT
 * ============================================================
 */

testHeading(
    'Scenario A: Top-Level Contract'
);


testResult(
    is_array(
        $result
    ),
    'Squad horizon result is an array'
);


testResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Available',
    'Squad horizon reports Available status'
);


testResult(
    (
        $result[
            'player_count'
        ]
        ?? null
    )
    ===
    15,
    'Squad horizon preserves all 15 players'
);


testResult(
    (
        $result[
            'horizon'
        ]
        ?? null
    )
    ===
    3,
    'Squad horizon preserves requested three-gameweek horizon'
);


/*
 * ============================================================
 * GAMEWEEK CONTRACT
 * ============================================================
 */

testHeading(
    'Scenario B: Gameweek Contract'
);


$gameweeks =
    $result[
        'gameweeks'
    ]
    ?? [];


testResult(
    is_array(
        $gameweeks
    ),
    'Squad horizon exposes gameweek data'
);


testResult(
    count(
        $gameweeks
    )
    ===
    3,
    'Three-gameweek horizon contains exactly three gameweeks'
);


testResult(
    isset(
        $gameweeks[
            2
        ],
        $gameweeks[
            3
        ],
        $gameweeks[
            4
        ]
    ),
    'Squad horizon contains GW2, GW3 and GW4'
);


foreach (
    [
        2,
        3,
        4
    ]
    as $gameweek
) {

    testResult(
        (
            $gameweeks[
                $gameweek
            ][
                'gameweek'
            ]
            ?? null
        )
        ===
        $gameweek,
        'GW'
        . $gameweek
        . ' preserves its gameweek number'
    );


    testResult(
        (
            $gameweeks[
                $gameweek
            ][
                'player_count'
            ]
            ?? null
        )
        ===
        15,
        'GW'
        . $gameweek
        . ' contains all 15 squad players'
    );


    testResult(
        isset(
            $gameweeks[
                $gameweek
            ][
                'players'
            ]
        )
        &&
        is_array(
            $gameweeks[
                $gameweek
            ][
                'players'
            ]
        ),
        'GW'
        . $gameweek
        . ' exposes player projections'
    );
}


/*
 * ============================================================
 * PLAYER PROJECTION PRESERVATION
 * ============================================================
 */

testHeading(
    'Scenario C: Player Projection Preservation'
);


$gw2Players =
    $gameweeks[
        2
    ][
        'players'
    ]
    ?? [];


$gw2PlayerOne =
    null;


foreach (
    $gw2Players
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
        1
    ) {

        $gw2PlayerOne =
            $player;

        break;
    }
}


testResult(
    is_array(
        $gw2PlayerOne
    ),
    'GW2 contains Goalkeeper A'
);


testResult(
    (
        $gw2PlayerOne[
            'name'
        ]
        ?? null
    )
    ===
    'Goalkeeper A',
    'Squad horizon preserves player name'
);


testResult(
    (
        $gw2PlayerOne[
            'position'
        ]
        ?? null
    )
    ===
    'GK',
    'Squad horizon preserves player position'
);


testResult(
    isset(
        $gw2PlayerOne[
            'projected_points'
        ]
    )
    &&
    abs(
        (
            (float) $gw2PlayerOne[
                'projected_points'
            ]
        )
        -
        4.5
    )
    <
    0.001,
    'Squad horizon preserves projected points'
);


/*
 * ============================================================
 * RESULT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "TEST SUMMARY<br>";
echo "============================================<br>";

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