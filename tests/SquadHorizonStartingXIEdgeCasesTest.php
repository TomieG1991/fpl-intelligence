<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Starting XI Edge Cases Test<br>";
echo "v0.32.0 — Squad Horizon & Rotation Intelligence<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function edgeCheck(
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


function edgeHeading(
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
 * SYNTHETIC SQUAD
 * ============================================================
 *
 * This squad is deliberately designed so:
 *
 * - 3-4-3 is the highest-projected legal formation
 * - one defender has no projection
 * - two forwards have identical projected points
 *
 * Expected Starting XI:
 *
 * GK
 * 1
 *
 * DEF
 * 3, 4, 5
 *
 * MID
 * 8, 9, 10, 11
 *
 * FWD
 * 13, 14, 15
 *
 * Expected formation:
 *
 * 3-4-3
 *
 * Defender E has a null projection and must remain null.
 *
 * Forward B and Forward C both project 7.0.
 * Lower player ID must win the deterministic tie-break.
 */


$squad = [

    /*
     * --------------------------------------------------------
     * GOALKEEPERS
     * --------------------------------------------------------
     */

    [
        'player_id' =>
            1,

        'name' =>
            'Goalkeeper A',

        'position' =>
            'GK',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    5.0
            ]
        ]
    ],

    [
        'player_id' =>
            2,

        'name' =>
            'Goalkeeper B',

        'position' =>
            'GK',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    3.0
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * DEFENDERS
     * --------------------------------------------------------
     */

    [
        'player_id' =>
            3,

        'name' =>
            'Defender A',

        'position' =>
            'DEF',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    6.0
            ]
        ]
    ],

    [
        'player_id' =>
            4,

        'name' =>
            'Defender B',

        'position' =>
            'DEF',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    5.5
            ]
        ]
    ],

    [
        'player_id' =>
            5,

        'name' =>
            'Defender C',

        'position' =>
            'DEF',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    5.0
            ]
        ]
    ],

    [
        'player_id' =>
            6,

        'name' =>
            'Defender D',

        'position' =>
            'DEF',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    1.0
            ]
        ]
    ],

    [
        'player_id' =>
            7,

        'name' =>
            'Defender E',

        'position' =>
            'DEF',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    null
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * MIDFIELDERS
     * --------------------------------------------------------
     */

    [
        'player_id' =>
            8,

        'name' =>
            'Midfielder A',

        'position' =>
            'MID',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    8.5
            ]
        ]
    ],

    [
        'player_id' =>
            9,

        'name' =>
            'Midfielder B',

        'position' =>
            'MID',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    8.0
            ]
        ]
    ],

    [
        'player_id' =>
            10,

        'name' =>
            'Midfielder C',

        'position' =>
            'MID',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    7.5
            ]
        ]
    ],

    [
        'player_id' =>
            11,

        'name' =>
            'Midfielder D',

        'position' =>
            'MID',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    7.0
            ]
        ]
    ],

    [
        'player_id' =>
            12,

        'name' =>
            'Midfielder E',

        'position' =>
            'MID',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    2.0
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * FORWARDS
     * --------------------------------------------------------
     */

    [
        'player_id' =>
            13,

        'name' =>
            'Forward A',

        'position' =>
            'FWD',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    9.0
            ]
        ]
    ],

    [
        'player_id' =>
            14,

        'name' =>
            'Forward B',

        'position' =>
            'FWD',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    7.0
            ]
        ]
    ],

    [
        'player_id' =>
            15,

        'name' =>
            'Forward C',

        'position' =>
            'FWD',

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    7.0
            ]
        ]
    ]
];


/*
 * ============================================================
 * BUILD HORIZON
 * ============================================================
 */

$model =
    new SquadHorizonIntelligence();


$result =
    $model->buildHorizon(
        $squad,
        1
    );


$gameweek =
    $result[
        'gameweeks'
    ][
        2
    ]
    ?? [];


$startingXI =
    $gameweek[
        'starting_xi'
    ]
    ?? [];


$bench =
    $gameweek[
        'bench'
    ]
    ?? [];


/*
 * ============================================================
 * SCENARIO A
 * DIFFERENT OPTIMAL FORMATION
 * ============================================================
 */

edgeHeading(
    'Scenario A: Different Optimal Formation'
);


$positionCounts = [
    'GK' =>
        0,

    'DEF' =>
        0,

    'MID' =>
        0,

    'FWD' =>
        0
];


foreach (
    $startingXI
    as $player
) {

    $position =
        $player[
            'position'
        ]
        ?? null;


    if (
        isset(
            $positionCounts[
                $position
            ]
        )
    ) {

        $positionCounts[
            $position
        ]++;
    }
}


$formation =
    $positionCounts[
        'DEF'
    ]
    . '-'
    . $positionCounts[
        'MID'
    ]
    . '-'
    . $positionCounts[
        'FWD'
    ];


edgeCheck(
    'Highest-projected legal formation is 3-4-3',
    $formation
    ===
    '3-4-3'
);


$startingIds =
    array_map(
        static function (
            array $player
        ): ?int {

            return
                isset(
                    $player[
                        'player_id'
                    ]
                )
                    ? (int) $player[
                        'player_id'
                    ]
                    : null;
        },
        $startingXI
    );


sort(
    $startingIds,
    SORT_NUMERIC
);


$expectedStartingIds = [
    1,
    3,
    4,
    5,
    8,
    9,
    10,
    11,
    13,
    14,
    15
];


edgeCheck(
    '3-4-3 contains the expected highest-projected players',
    $startingIds
    ===
    $expectedStartingIds
);


/*
 * ============================================================
 * SCENARIO B
 * NULL PROJECTION PRESERVATION
 * ============================================================
 */

edgeHeading(
    'Scenario B: Null Projection Preservation'
);


$defenderE =
    null;


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
        7
    ) {

        $defenderE =
            $player;

        break;
    }
}


edgeCheck(
    'Player with missing projection remains present in gameweek data',
    is_array(
        $defenderE
    )
);


edgeCheck(
    'Missing projection remains null',
    is_array(
        $defenderE
    )
    &&
    array_key_exists(
        'projected_points',
        $defenderE
    )
    &&
    $defenderE[
        'projected_points'
    ]
    ===
    null
);


/*
 * ============================================================
 * SCENARIO C
 * MISSING PROJECTION RANKING
 * ============================================================
 */

edgeHeading(
    'Scenario C: Missing Projection Ranking'
);


$benchIds =
    array_map(
        static function (
            array $player
        ): ?int {

            return
                isset(
                    $player[
                        'player_id'
                    ]
                )
                    ? (int) $player[
                        'player_id'
                    ]
                    : null;
        },
        $bench
    );


edgeCheck(
    'Null-projection Defender E is not selected ahead of positively projected defenders',
    !in_array(
        7,
        $startingIds,
        true
    )
);


edgeCheck(
    'Null-projection Defender E remains on the bench',
    in_array(
        7,
        $benchIds,
        true
    )
);


/*
 * ============================================================
 * SCENARIO D
 * DETERMINISTIC TIE HANDLING
 * ============================================================
 */

edgeHeading(
    'Scenario D: Deterministic Tie Handling'
);


$forwardB =
    null;


$forwardC =
    null;


foreach (
    $startingXI
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
        14
    ) {

        $forwardB =
            $player;
    }


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

        $forwardC =
            $player;
    }
}


edgeCheck(
    'Forward B is selected when tied on projected points',
    is_array(
        $forwardB
    )
);


edgeCheck(
    'Forward C is also selected because 3-4-3 uses three forwards',
    is_array(
        $forwardC
    )
);


/*
 * ============================================================
 * TEST SUMMARY
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