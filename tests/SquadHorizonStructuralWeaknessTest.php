<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Structural Weakness Test<br>";
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

function structuralWeaknessCheck(
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


function structuralWeaknessHeading(
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
 * Build one synthetic player across GW2, GW3 and GW4.
 */
function buildStructuralWeaknessPlayer(
    int $playerId,
    string $name,
    string $position,
    array $projections
): array {

    $gameweeks =
        [];


    foreach (
        $projections
        as $gameweek => $projection
    ) {

        $gameweeks[
            $gameweek
        ] = [
            'gameweek' =>
                $gameweek,

            'projected_points' =>
                $projection[
                    'projected_points'
                ],

            'team_id' =>
                $projection[
                    'team_id'
                ],

            'opponent_team_id' =>
                $projection[
                    'opponent_team_id'
                ]
        ];
    }


    return [
        'player_id' =>
            $playerId,

        'name' =>
            $name,

        'position' =>
            $position,

        'gameweeks' =>
            $gameweeks
    ];
}


/*
 * ============================================================
 * PROJECTION HELPER
 * ============================================================
 */

function structuralProjection(
    float $points,
    int $teamId,
    int $opponentTeamId
): array {

    return [
        'projected_points' =>
            $points,

        'team_id' =>
            $teamId,

        'opponent_team_id' =>
            $opponentTeamId
    ];
}


/*
 * ============================================================
 * SYNTHETIC SQUAD
 * ============================================================
 *
 * GW2
 * ------------------------------------------------------------
 * Healthy gameweek.
 *
 * No weak fixture cluster.
 * No position-depth weakness.
 * No fixture clash.
 *
 * Expected severity:
 *     None
 *
 *
 * GW3
 * ------------------------------------------------------------
 * Structural problems deliberately align.
 *
 * Weak projected starters remain in the optimal XI.
 * DEF usable depth falls to the legal minimum.
 * Multiple weak starters create a weak fixture cluster.
 *
 * Defender A and Forward A also play directly against
 * each other, creating one fixture clash.
 *
 * Expected problems:
 *     Weak Fixture Cluster
 *     Position Depth Weakness
 *     Uncovered Weak XI
 *     Fixture Clash
 *
 * Expected severity:
 *     Severe
 *
 *
 * GW4
 * ------------------------------------------------------------
 * Only one fixture clash.
 *
 * Expected severity:
 *     Low
 * ============================================================
 */

$squad = [

    /*
     * --------------------------------------------------------
     * GOALKEEPERS
     * --------------------------------------------------------
     */

    buildStructuralWeaknessPlayer(
        1,
        'Goalkeeper A',
        'GK',
        [
            2 => structuralProjection(5.0, 101, 201),
            3 => structuralProjection(2.5, 101, 201),
            4 => structuralProjection(5.0, 101, 201)
        ]
    ),

    buildStructuralWeaknessPlayer(
        2,
        'Goalkeeper B',
        'GK',
        [
            2 => structuralProjection(4.0, 102, 202),
            3 => structuralProjection(3.5, 102, 202),
            4 => structuralProjection(4.0, 102, 202)
        ]
    ),


    /*
     * --------------------------------------------------------
     * DEFENDERS
     * --------------------------------------------------------
     */

    buildStructuralWeaknessPlayer(
        3,
        'Defender A',
        'DEF',
        [
            2 => structuralProjection(6.0, 103, 203),
            3 => structuralProjection(2.5, 301, 302),
            4 => structuralProjection(6.0, 103, 203)
        ]
    ),

    buildStructuralWeaknessPlayer(
        4,
        'Defender B',
        'DEF',
        [
            2 => structuralProjection(5.5, 104, 204),
            3 => structuralProjection(4.0, 104, 204),
            4 => structuralProjection(5.5, 104, 204)
        ]
    ),

    buildStructuralWeaknessPlayer(
        5,
        'Defender C',
        'DEF',
        [
            2 => structuralProjection(5.0, 105, 205),
            3 => structuralProjection(3.5, 105, 205),
            4 => structuralProjection(5.0, 105, 205)
        ]
    ),

    buildStructuralWeaknessPlayer(
        6,
        'Defender D',
        'DEF',
        [
            2 => structuralProjection(4.5, 106, 206),
            3 => structuralProjection(2.0, 106, 206),
            4 => structuralProjection(4.5, 106, 206)
        ]
    ),

    buildStructuralWeaknessPlayer(
        7,
        'Defender E',
        'DEF',
        [
            2 => structuralProjection(4.0, 107, 207),
            3 => structuralProjection(1.5, 107, 207),
            4 => structuralProjection(4.0, 107, 207)
        ]
    ),


    /*
     * --------------------------------------------------------
     * MIDFIELDERS
     * --------------------------------------------------------
     */

    buildStructuralWeaknessPlayer(
        8,
        'Midfielder A',
        'MID',
        [
            2 => structuralProjection(8.0, 108, 208),
            3 => structuralProjection(2.5, 108, 208),
            4 => structuralProjection(8.0, 108, 208)
        ]
    ),

    buildStructuralWeaknessPlayer(
        9,
        'Midfielder B',
        'MID',
        [
            2 => structuralProjection(7.5, 109, 209),
            3 => structuralProjection(7.5, 109, 209),
            4 => structuralProjection(7.5, 109, 209)
        ]
    ),

    buildStructuralWeaknessPlayer(
        10,
        'Midfielder C',
        'MID',
        [
            2 => structuralProjection(7.0, 110, 210),
            3 => structuralProjection(7.0, 110, 210),
            4 => structuralProjection(7.0, 110, 210)
        ]
    ),

    buildStructuralWeaknessPlayer(
        11,
        'Midfielder D',
        'MID',
        [
            2 => structuralProjection(6.5, 111, 211),
            3 => structuralProjection(2.0, 111, 211),
            4 => structuralProjection(6.5, 401, 402)
        ]
    ),

    buildStructuralWeaknessPlayer(
        12,
        'Midfielder E',
        'MID',
        [
            2 => structuralProjection(6.0, 112, 212),
            3 => structuralProjection(1.5, 112, 212),
            4 => structuralProjection(6.0, 112, 212)
        ]
    ),


    /*
     * --------------------------------------------------------
     * FORWARDS
     * --------------------------------------------------------
     */

    buildStructuralWeaknessPlayer(
        13,
        'Forward A',
        'FWD',
        [
            2 => structuralProjection(9.0, 113, 213),
            3 => structuralProjection(2.5, 302, 301),
            4 => structuralProjection(9.0, 402, 401)
        ]
    ),

    buildStructuralWeaknessPlayer(
        14,
        'Forward B',
        'FWD',
        [
            2 => structuralProjection(8.5, 114, 214),
            3 => structuralProjection(8.5, 114, 214),
            4 => structuralProjection(8.5, 114, 214)
        ]
    ),

    buildStructuralWeaknessPlayer(
        15,
        'Forward C',
        'FWD',
        [
            2 => structuralProjection(4.0, 115, 215),
            3 => structuralProjection(1.0, 115, 215),
            4 => structuralProjection(4.0, 115, 215)
        ]
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


$structuralWeakness =
    $result[
        'structural_weakness'
    ]
    ?? [];


$gameweeks =
    $structuralWeakness[
        'gameweeks'
    ]
    ?? [];


/*
 * ============================================================
 * SCENARIO A
 * STRUCTURE
 * ============================================================
 */

structuralWeaknessHeading(
    'Scenario A: Structural Weakness Structure'
);


structuralWeaknessCheck(
    'Horizon exposes structural weakness intelligence',
    isset(
        $result[
            'structural_weakness'
        ]
    )
);


structuralWeaknessCheck(
    'Structural weakness covers three gameweeks',
    (
        $structuralWeakness[
            'gameweek_count'
        ]
        ?? null
    )
    ===
    3
);


structuralWeaknessCheck(
    'Structural weakness exposes severity levels',
    (
        $structuralWeakness[
            'severity_levels'
        ]
        ?? null
    )
    ===
    [
        0 => 'None',
        1 => 'Low',
        2 => 'Moderate',
        3 => 'High',
        4 => 'Severe'
    ]
);


/*
 * ============================================================
 * SCENARIO B
 * GW2 — HEALTHY STRUCTURE
 * ============================================================
 */

structuralWeaknessHeading(
    'Scenario B: GW2 Healthy Structure'
);


$gw2 =
    $gameweeks[
        2
    ]
    ?? [];


structuralWeaknessCheck(
    'GW2 has zero structural problems',
    (
        $gw2[
            'problem_count'
        ]
        ?? null
    )
    ===
    0
);


structuralWeaknessCheck(
    'GW2 severity is None',
    (
        $gw2[
            'severity'
        ]
        ?? null
    )
    ===
    'None'
);


structuralWeaknessCheck(
    'GW2 has no problem labels',
    (
        $gw2[
            'problems'
        ]
        ?? null
    )
    ===
    []
);


/*
 * ============================================================
 * SCENARIO C
 * GW3 — SEVERE STRUCTURAL WEAKNESS
 * ============================================================
 */

structuralWeaknessHeading(
    'Scenario C: GW3 Severe Structural Weakness'
);


$gw3 =
    $gameweeks[
        3
    ]
    ?? [];


structuralWeaknessCheck(
    'GW3 has a weak fixture cluster',
    (
        $gw3[
            'has_weak_fixture_cluster'
        ]
        ?? null
    )
    ===
    true
);


structuralWeaknessCheck(
    'GW3 has position depth weakness',
    (
        $gw3[
            'has_position_depth_weakness'
        ]
        ?? null
    )
    ===
    true
);


structuralWeaknessCheck(
    'GW3 has uncovered weak XI structure',
    (
        $gw3[
            'has_uncovered_weak_xi'
        ]
        ?? null
    )
    ===
    true
);


structuralWeaknessCheck(
    'GW3 has fixture clashes',
    (
        $gw3[
            'has_fixture_clashes'
        ]
        ?? null
    )
    ===
    true
);


structuralWeaknessCheck(
    'GW3 has four structural problems',
    (
        $gw3[
            'problem_count'
        ]
        ?? null
    )
    ===
    4
);


structuralWeaknessCheck(
    'GW3 severity is Severe',
    (
        $gw3[
            'severity'
        ]
        ?? null
    )
    ===
    'Severe'
);


structuralWeaknessCheck(
    'GW3 exposes all four problem labels',
    (
        $gw3[
            'problems'
        ]
        ?? null
    )
    ===
    [
        'Weak Fixture Cluster',
        'Position Depth Weakness',
        'Uncovered Weak XI',
        'Fixture Clash'
    ]
);


/*
 * ============================================================
 * SCENARIO D
 * GW4 — SINGLE STRUCTURAL PROBLEM
 * ============================================================
 */

structuralWeaknessHeading(
    'Scenario D: GW4 Single Structural Problem'
);


$gw4 =
    $gameweeks[
        4
    ]
    ?? [];


structuralWeaknessCheck(
    'GW4 has no weak fixture cluster',
    (
        $gw4[
            'has_weak_fixture_cluster'
        ]
        ?? null
    )
    ===
    false
);


structuralWeaknessCheck(
    'GW4 has no position depth weakness',
    (
        $gw4[
            'has_position_depth_weakness'
        ]
        ?? null
    )
    ===
    false
);


structuralWeaknessCheck(
    'GW4 has no uncovered weak XI structure',
    (
        $gw4[
            'has_uncovered_weak_xi'
        ]
        ?? null
    )
    ===
    false
);


structuralWeaknessCheck(
    'GW4 has fixture clashes',
    (
        $gw4[
            'has_fixture_clashes'
        ]
        ?? null
    )
    ===
    true
);


structuralWeaknessCheck(
    'GW4 has one structural problem',
    (
        $gw4[
            'problem_count'
        ]
        ?? null
    )
    ===
    1
);


structuralWeaknessCheck(
    'GW4 severity is Low',
    (
        $gw4[
            'severity'
        ]
        ?? null
    )
    ===
    'Low'
);


/*
 * ============================================================
 * SCENARIO E
 * HORIZON SUMMARY
 * ============================================================
 */

structuralWeaknessHeading(
    'Scenario E: Horizon Structural Summary'
);


structuralWeaknessCheck(
    'Two gameweeks contain structural problems',
    (
        $structuralWeakness[
            'gameweeks_with_problems'
        ]
        ?? null
    )
    ===
    2
);


structuralWeaknessCheck(
    'Worst structural gameweek is GW3',
    (
        $structuralWeakness[
            'worst_gameweek'
        ]
        ?? null
    )
    ===
    3
);


structuralWeaknessCheck(
    'Maximum structural problem count is four',
    (
        $structuralWeakness[
            'max_problem_count'
        ]
        ?? null
    )
    ===
    4
);


structuralWeaknessCheck(
    'Maximum structural severity is Severe',
    (
        $structuralWeakness[
            'max_severity'
        ]
        ?? null
    )
    ===
    'Severe'
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