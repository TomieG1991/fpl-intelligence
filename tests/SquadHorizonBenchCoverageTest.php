<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Bench Coverage Test<br>";
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

function benchCoverageCheck(
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


function benchCoverageHeading(
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
 * This squad is designed to produce a 3-5-2 Starting XI.
 *
 * Expected bench:
 *
 * Goalkeeper B = 3.0
 * Defender D   = 4.5
 * Defender E   = 2.0
 * Forward C    = 4.0
 *
 * Expected total bench projected points:
 *
 * 3.0 + 4.5 + 2.0 + 4.0 = 13.5
 *
 * Strongest outfield bench player:
 *
 * Defender D = 4.5
 *
 * Weakest outfield starter:
 *
 * Defender C = 5.0
 *
 * Coverage gap:
 *
 * 5.0 - 4.5 = 0.5
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
                    4.5
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
 * BENCH COVERAGE STRUCTURE
 * ============================================================
 */

benchCoverageHeading(
    'Scenario A: Bench Coverage Structure'
);


$benchCoverage =
    $gameweek[
        'bench_coverage'
    ]
    ?? [];


benchCoverageCheck(
    'GW2 exposes bench coverage intelligence',
    isset(
        $gameweek[
            'bench_coverage'
        ]
    )
    &&
    is_array(
        $benchCoverage
    )
);


benchCoverageCheck(
    'Bench coverage records four bench players',
    (
        $benchCoverage[
            'bench_player_count'
        ]
        ?? null
    )
    ===
    4
);


/*
 * ============================================================
 * SCENARIO B
 * TOTAL BENCH PROJECTED POINTS
 * ============================================================
 */

benchCoverageHeading(
    'Scenario B: Total Bench Projected Points'
);


$totalBenchPoints =
    $benchCoverage[
        'total_projected_points'
    ]
    ?? null;


benchCoverageCheck(
    'Bench coverage exposes total projected points',
    is_numeric(
        $totalBenchPoints
    )
);


benchCoverageCheck(
    'Total bench projected points equal 13.5',
    is_numeric(
        $totalBenchPoints
    )
    &&
    abs(
        (float) $totalBenchPoints
        -
        13.5
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO C
 * FIRST OUTFIELD SUBSTITUTE
 * ============================================================
 */

benchCoverageHeading(
    'Scenario C: First Outfield Substitute'
);


$firstOutfieldSubstitute =
    $benchCoverage[
        'first_outfield_substitute'
    ]
    ?? null;


benchCoverageCheck(
    'Bench coverage exposes first outfield substitute',
    is_array(
        $firstOutfieldSubstitute
    )
);


benchCoverageCheck(
    'Defender D is the first outfield substitute',
    (
        $firstOutfieldSubstitute[
            'player_id'
        ]
        ?? null
    )
    ===
    6
);


benchCoverageCheck(
    'First outfield substitute preserves projected points',
    isset(
        $firstOutfieldSubstitute[
            'projected_points'
        ]
    )
    &&
    abs(
        (float) $firstOutfieldSubstitute[
            'projected_points'
        ]
        -
        4.5
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO D
 * WEAKEST OUTFIELD STARTER
 * ============================================================
 */

benchCoverageHeading(
    'Scenario D: Weakest Outfield Starter'
);


$weakestOutfieldStarter =
    $benchCoverage[
        'weakest_outfield_starter'
    ]
    ?? null;


benchCoverageCheck(
    'Bench coverage exposes weakest outfield starter',
    is_array(
        $weakestOutfieldStarter
    )
);


benchCoverageCheck(
    'Defender C is the weakest outfield starter',
    (
        $weakestOutfieldStarter[
            'player_id'
        ]
        ?? null
    )
    ===
    5
);


benchCoverageCheck(
    'Weakest outfield starter preserves projected points',
    isset(
        $weakestOutfieldStarter[
            'projected_points'
        ]
    )
    &&
    abs(
        (float) $weakestOutfieldStarter[
            'projected_points'
        ]
        -
        5.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO E
 * COVERAGE GAP
 * ============================================================
 */

benchCoverageHeading(
    'Scenario E: Coverage Gap'
);


$coverageGap =
    $benchCoverage[
        'coverage_gap'
    ]
    ?? null;


benchCoverageCheck(
    'Bench coverage exposes coverage gap',
    is_numeric(
        $coverageGap
    )
);


benchCoverageCheck(
    'Coverage gap equals 0.5 projected points',
    is_numeric(
        $coverageGap
    )
    &&
    abs(
        (float) $coverageGap
        -
        0.5
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