<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Position Depth Test<br>";
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

function positionDepthCheck(
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


function positionDepthHeading(
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


/**
 * Build one synthetic squad player with projections
 * across GW2, GW3 and GW4.
 */
function buildPositionDepthPlayer(
    int $playerId,
    string $name,
    string $position,
    $gw2,
    $gw3,
    $gw4
): array {

    return [
        'player_id' =>
            $playerId,

        'name' =>
            $name,

        'position' =>
            $position,

        'gameweeks' => [

            2 => [
                'gameweek' =>
                    2,

                'projected_points' =>
                    $gw2
            ],

            3 => [
                'gameweek' =>
                    3,

                'projected_points' =>
                    $gw3
            ],

            4 => [
                'gameweek' =>
                    4,

                'projected_points' =>
                    $gw4
            ]
        ]
    ];
}


/*
 * ============================================================
 * SYNTHETIC SQUAD
 * ============================================================
 *
 * Usable threshold:
 *
 * projected_points >= 3.0
 *
 *
 * ------------------------------------------------------------
 * EXPECTED USABLE OPTIONS
 * ------------------------------------------------------------
 *
 *             GW2    GW3    GW4
 *
 * GK            2      1      2
 * DEF           5      3      4
 * MID           5      4      5
 * FWD           3      2      1
 *
 *
 * ------------------------------------------------------------
 * MINIMUM LEGAL XI REQUIREMENTS
 * ------------------------------------------------------------
 *
 * GK  = 1
 * DEF = 3
 * MID = 2
 * FWD = 1
 *
 *
 * ------------------------------------------------------------
 * EXPECTED DEPTH BEYOND MINIMUM
 * ------------------------------------------------------------
 *
 *             GW2    GW3    GW4
 *
 * GK            1      0      1
 * DEF           2      0      1
 * MID           3      2      3
 * FWD           2      1      0
 *
 *
 * Weak depth positions:
 *
 * GW2 = []
 * GW3 = [GK, DEF]
 * GW4 = [FWD]
 *
 * ============================================================
 */

$squad = [

    /*
     * --------------------------------------------------------
     * GOALKEEPERS
     * --------------------------------------------------------
     */

    buildPositionDepthPlayer(
        1,
        'Goalkeeper A',
        'GK',
        5.0,
        5.0,
        5.0
    ),

    buildPositionDepthPlayer(
        2,
        'Goalkeeper B',
        'GK',
        4.0,
        2.0,
        4.0
    ),


    /*
     * --------------------------------------------------------
     * DEFENDERS
     * --------------------------------------------------------
     */

    buildPositionDepthPlayer(
        3,
        'Defender A',
        'DEF',
        6.0,
        6.0,
        6.0
    ),

    buildPositionDepthPlayer(
        4,
        'Defender B',
        'DEF',
        5.5,
        5.5,
        5.5
    ),

    buildPositionDepthPlayer(
        5,
        'Defender C',
        'DEF',
        5.0,
        5.0,
        5.0
    ),

    buildPositionDepthPlayer(
        6,
        'Defender D',
        'DEF',
        4.5,
        2.5,
        4.5
    ),

    buildPositionDepthPlayer(
        7,
        'Defender E',
        'DEF',
        4.0,
        2.0,
        2.0
    ),


    /*
     * --------------------------------------------------------
     * MIDFIELDERS
     * --------------------------------------------------------
     */

    buildPositionDepthPlayer(
        8,
        'Midfielder A',
        'MID',
        8.0,
        8.0,
        8.0
    ),

    buildPositionDepthPlayer(
        9,
        'Midfielder B',
        'MID',
        7.5,
        7.5,
        7.5
    ),

    buildPositionDepthPlayer(
        10,
        'Midfielder C',
        'MID',
        7.0,
        7.0,
        7.0
    ),

    buildPositionDepthPlayer(
        11,
        'Midfielder D',
        'MID',
        6.5,
        6.5,
        6.5
    ),

    buildPositionDepthPlayer(
        12,
        'Midfielder E',
        'MID',
        6.0,
        2.5,
        6.0
    ),


    /*
     * --------------------------------------------------------
     * FORWARDS
     * --------------------------------------------------------
     */

    buildPositionDepthPlayer(
        13,
        'Forward A',
        'FWD',
        9.0,
        9.0,
        9.0
    ),

    buildPositionDepthPlayer(
        14,
        'Forward B',
        'FWD',
        8.0,
        8.0,
        2.5
    ),

    buildPositionDepthPlayer(
        15,
        'Forward C',
        'FWD',
        4.0,
        2.0,
        2.0
    )
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
        3
    );


$positionDepth =
    $result[
        'position_depth'
    ]
    ?? [];


/*
 * ============================================================
 * SCENARIO A
 * STRUCTURE
 * ============================================================
 */

positionDepthHeading(
    'Scenario A: Position Depth Structure'
);


positionDepthCheck(
    'Horizon exposes position depth intelligence',
    isset(
        $result[
            'position_depth'
        ]
    )
    &&
    is_array(
        $positionDepth
    )
);


positionDepthCheck(
    'Position depth intelligence covers three gameweeks',
    (
        $positionDepth[
            'gameweek_count'
        ]
        ?? null
    )
    ===
    3
);


positionDepthCheck(
    'Usable projection threshold equals 3.0',
    is_numeric(
        $positionDepth[
            'usable_projection_threshold'
        ]
        ?? null
    )
    &&
    abs(
        (float) $positionDepth[
            'usable_projection_threshold'
        ]
        -
        3.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO B
 * USABLE OPTIONS
 * ============================================================
 */

positionDepthHeading(
    'Scenario B: Usable Options By Position'
);


$depthGameweeks =
    $positionDepth[
        'gameweeks'
    ]
    ?? [];


positionDepthCheck(
    'GW2 has two usable goalkeepers',
    (
        $depthGameweeks[
            2
        ][
            'positions'
        ][
            'GK'
        ][
            'usable_player_count'
        ]
        ?? null
    )
    ===
    2
);


positionDepthCheck(
    'GW3 has three usable defenders',
    (
        $depthGameweeks[
            3
        ][
            'positions'
        ][
            'DEF'
        ][
            'usable_player_count'
        ]
        ?? null
    )
    ===
    3
);


positionDepthCheck(
    'GW3 has four usable midfielders',
    (
        $depthGameweeks[
            3
        ][
            'positions'
        ][
            'MID'
        ][
            'usable_player_count'
        ]
        ?? null
    )
    ===
    4
);


positionDepthCheck(
    'GW4 has one usable forward',
    (
        $depthGameweeks[
            4
        ][
            'positions'
        ][
            'FWD'
        ][
            'usable_player_count'
        ]
        ?? null
    )
    ===
    1
);


/*
 * ============================================================
 * SCENARIO C
 * DEPTH BEYOND MINIMUM LEGAL XI
 * ============================================================
 */

positionDepthHeading(
    'Scenario C: Depth Beyond Minimum Legal XI'
);


positionDepthCheck(
    'GW2 goalkeeper depth equals one',
    (
        $depthGameweeks[
            2
        ][
            'positions'
        ][
            'GK'
        ][
            'depth_count'
        ]
        ?? null
    )
    ===
    1
);


positionDepthCheck(
    'GW2 defender depth equals two',
    (
        $depthGameweeks[
            2
        ][
            'positions'
        ][
            'DEF'
        ][
            'depth_count'
        ]
        ?? null
    )
    ===
    2
);


positionDepthCheck(
    'GW3 goalkeeper depth equals zero',
    (
        $depthGameweeks[
            3
        ][
            'positions'
        ][
            'GK'
        ][
            'depth_count'
        ]
        ?? null
    )
    ===
    0
);


positionDepthCheck(
    'GW3 defender depth equals zero',
    (
        $depthGameweeks[
            3
        ][
            'positions'
        ][
            'DEF'
        ][
            'depth_count'
        ]
        ?? null
    )
    ===
    0
);


positionDepthCheck(
    'GW3 midfielder depth equals two',
    (
        $depthGameweeks[
            3
        ][
            'positions'
        ][
            'MID'
        ][
            'depth_count'
        ]
        ?? null
    )
    ===
    2
);


positionDepthCheck(
    'GW4 forward depth equals zero',
    (
        $depthGameweeks[
            4
        ][
            'positions'
        ][
            'FWD'
        ][
            'depth_count'
        ]
        ?? null
    )
    ===
    0
);


/*
 * ============================================================
 * SCENARIO D
 * WEAK DEPTH POSITIONS
 * ============================================================
 */

positionDepthHeading(
    'Scenario D: Weak Depth Positions'
);


positionDepthCheck(
    'GW2 has no weak depth positions',
    (
        $depthGameweeks[
            2
        ][
            'weak_depth_positions'
        ]
        ?? null
    )
    ===
    []
);


positionDepthCheck(
    'GW3 identifies goalkeeper and defender depth weaknesses',
    (
        $depthGameweeks[
            3
        ][
            'weak_depth_positions'
        ]
        ?? null
    )
    ===
    [
        'GK',
        'DEF'
    ]
);


positionDepthCheck(
    'GW4 identifies forward depth weakness',
    (
        $depthGameweeks[
            4
        ][
            'weak_depth_positions'
        ]
        ?? null
    )
    ===
    [
        'FWD'
    ]
);


/*
 * ============================================================
 * SCENARIO E
 * HORIZON SUMMARY
 * ============================================================
 */

positionDepthHeading(
    'Scenario E: Horizon Position Depth Summary'
);


positionDepthCheck(
    'Two gameweeks contain at least one position depth weakness',
    (
        $positionDepth[
            'gameweeks_with_depth_weakness'
        ]
        ?? null
    )
    ===
    2
);


positionDepthCheck(
    'GW3 is identified as the worst depth gameweek',
    (
        $positionDepth[
            'worst_gameweek'
        ]
        ?? null
    )
    ===
    3
);


positionDepthCheck(
    'Maximum simultaneous position depth weaknesses equals two',
    (
        $positionDepth[
            'max_weak_position_count'
        ]
        ?? null
    )
    ===
    2
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