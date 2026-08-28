<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Starting XI Test<br>";
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

function startingXICheck(
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


function startingXIHeading(
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
 * This squad deliberately creates a situation where the
 * highest-projected legal Starting XI should use a 3-5-2.
 *
 * Expected Starting XI:
 *
 * GK:
 * Goalkeeper A
 *
 * DEF:
 * Defender A
 * Defender B
 * Defender C
 *
 * MID:
 * Midfielder A
 * Midfielder B
 * Midfielder C
 * Midfielder D
 * Midfielder E
 *
 * FWD:
 * Forward A
 * Forward B
 *
 * Expected bench:
 *
 * Goalkeeper B
 * Defender D
 * Defender E
 * Forward C
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
                    2.5
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
                    2.0
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
                    8.0
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
                    7.5
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
                    7.0
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
                    6.5
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
                    6.0
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
                    8.5
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
                    4.0
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


/*
 * ============================================================
 * SCENARIO A
 * STARTING XI STRUCTURE
 * ============================================================
 */

startingXIHeading(
    'Scenario A: Starting XI Structure'
);


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


startingXICheck(
    'GW2 exposes a Starting XI',
    isset(
        $gameweek[
            'starting_xi'
        ]
    )
    &&
    is_array(
        $startingXI
    )
);


startingXICheck(
    'Starting XI contains exactly 11 players',
    count(
        $startingXI
    )
    ===
    11
);


startingXICheck(
    'GW2 exposes a bench',
    isset(
        $gameweek[
            'bench'
        ]
    )
    &&
    is_array(
        $bench
    )
);


startingXICheck(
    'Bench contains exactly 4 players',
    count(
        $bench
    )
    ===
    4
);


/*
 * ============================================================
 * SCENARIO B
 * LEGAL FORMATION
 * ============================================================
 */

startingXIHeading(
    'Scenario B: Legal Formation'
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


startingXICheck(
    'Starting XI contains exactly 1 goalkeeper',
    $positionCounts[
        'GK'
    ]
    ===
    1
);


startingXICheck(
    'Starting XI contains at least 3 defenders',
    $positionCounts[
        'DEF'
    ]
    >=
    3
);


startingXICheck(
    'Starting XI contains at least 2 midfielders',
    $positionCounts[
        'MID'
    ]
    >=
    2
);


startingXICheck(
    'Starting XI contains at least 1 forward',
    $positionCounts[
        'FWD'
    ]
    >=
    1
);


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


startingXICheck(
    'Highest-projected legal formation is 3-5-2',
    $formation
    ===
    '3-5-2'
);


/*
 * ============================================================
 * SCENARIO C
 * BEST PROJECTED PLAYERS
 * ============================================================
 */

startingXIHeading(
    'Scenario C: Best Projected Players'
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


$expectedStartingIds = [
    1,
    3,
    4,
    5,
    8,
    9,
    10,
    11,
    12,
    13,
    14
];


sort(
    $startingIds,
    SORT_NUMERIC
);


sort(
    $expectedStartingIds,
    SORT_NUMERIC
);


startingXICheck(
    'Starting XI contains the expected highest-projected legal players',
    $startingIds
    ===
    $expectedStartingIds
);


/*
 * ============================================================
 * SCENARIO D
 * BENCH SELECTION
 * ============================================================
 */

startingXIHeading(
    'Scenario D: Bench Selection'
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


$expectedBenchIds = [
    2,
    6,
    7,
    15
];


sort(
    $benchIds,
    SORT_NUMERIC
);


sort(
    $expectedBenchIds,
    SORT_NUMERIC
);


startingXICheck(
    'Bench contains the four expected remaining players',
    $benchIds
    ===
    $expectedBenchIds
);


/*
 * ============================================================
 * SCENARIO E
 * PROJECTED STARTING XI POINTS
 * ============================================================
 */

startingXIHeading(
    'Scenario E: Projected Starting XI Points'
);


$expectedStartingPoints =
    74.0;


$startingPoints =
    $gameweek[
        'starting_xi_projected_points'
    ]
    ?? null;


startingXICheck(
    'GW2 exposes Starting XI projected points',
    is_numeric(
        $startingPoints
    )
);


startingXICheck(
    'Starting XI projected points equal 74.0',
    is_numeric(
        $startingPoints
    )
    &&
    abs(
        (float) $startingPoints
        -
        $expectedStartingPoints
    )
    <
    0.001
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