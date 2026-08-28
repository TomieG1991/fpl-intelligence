<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Repeated Benching Edge Cases Test<br>";
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

function repeatedBenchingEdgeCheck(
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


function repeatedBenchingEdgeHeading(
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
 * Build one synthetic player with projections across
 * GW2, GW3 and GW4.
 */
function buildRepeatedBenchingEdgePlayer(
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
 * We deliberately use the two goalkeeper slots for the
 * missing-projection cases.
 *
 *
 * Goalkeeper A — ID 1
 *
 * Starts all three gameweeks.
 *
 *
 * Goalkeeper B — ID 2
 *
 * Benched all three gameweeks:
 *
 * GW2 = 4.0
 * GW3 = null
 * GW4 = 5.0
 *
 * Expected:
 *
 * bench_count              = 3
 * benched_projection_count = 2
 * total benched xP         = 9.0
 * average benched xP       = 4.5
 * repeatedly benched       = YES
 * meaningful               = YES
 *
 *
 * Defender E — ID 7
 *
 * Benched all three gameweeks:
 *
 * GW2 = null
 * GW3 = null
 * GW4 = null
 *
 * Expected:
 *
 * bench_count              = 3
 * benched_projection_count = 0
 * total benched xP         = 0.0
 * average benched xP       = null
 * repeatedly benched       = YES
 * meaningful               = NO
 *
 *
 * IMPORTANT:
 *
 * null means unknown.
 *
 * It must never silently become a zero projection when
 * calculating the average benched projected output.
 * ============================================================
 */

$squad = [

    /*
     * --------------------------------------------------------
     * GOALKEEPERS
     * --------------------------------------------------------
     */

    buildRepeatedBenchingEdgePlayer(
        1,
        'Goalkeeper A',
        'GK',
        6.0,
        6.0,
        6.0
    ),

    buildRepeatedBenchingEdgePlayer(
        2,
        'Goalkeeper B',
        'GK',
        4.0,
        null,
        5.0
    ),


    /*
     * --------------------------------------------------------
     * DEFENDERS
     * --------------------------------------------------------
     */

    buildRepeatedBenchingEdgePlayer(
        3,
        'Defender A',
        'DEF',
        7.0,
        7.0,
        7.0
    ),

    buildRepeatedBenchingEdgePlayer(
        4,
        'Defender B',
        'DEF',
        6.5,
        6.5,
        6.5
    ),

    buildRepeatedBenchingEdgePlayer(
        5,
        'Defender C',
        'DEF',
        6.0,
        6.0,
        6.0
    ),

    buildRepeatedBenchingEdgePlayer(
        6,
        'Defender D',
        'DEF',
        1.0,
        1.0,
        1.0
    ),

    buildRepeatedBenchingEdgePlayer(
        7,
        'Defender E',
        'DEF',
        null,
        null,
        null
    ),


    /*
     * --------------------------------------------------------
     * MIDFIELDERS
     * --------------------------------------------------------
     */

    buildRepeatedBenchingEdgePlayer(
        8,
        'Midfielder A',
        'MID',
        9.0,
        9.0,
        9.0
    ),

    buildRepeatedBenchingEdgePlayer(
        9,
        'Midfielder B',
        'MID',
        8.5,
        8.5,
        8.5
    ),

    buildRepeatedBenchingEdgePlayer(
        10,
        'Midfielder C',
        'MID',
        8.0,
        8.0,
        8.0
    ),

    buildRepeatedBenchingEdgePlayer(
        11,
        'Midfielder D',
        'MID',
        7.5,
        7.5,
        7.5
    ),

    buildRepeatedBenchingEdgePlayer(
        12,
        'Midfielder E',
        'MID',
        7.0,
        7.0,
        7.0
    ),


    /*
     * --------------------------------------------------------
     * FORWARDS
     * --------------------------------------------------------
     */

    buildRepeatedBenchingEdgePlayer(
        13,
        'Forward A',
        'FWD',
        10.0,
        10.0,
        10.0
    ),

    buildRepeatedBenchingEdgePlayer(
        14,
        'Forward B',
        'FWD',
        9.0,
        9.0,
        9.0
    ),

    buildRepeatedBenchingEdgePlayer(
        15,
        'Forward C',
        'FWD',
        2.0,
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


$repeatedBenching =
    $result[
        'repeated_benching'
    ]
    ?? [];


$players =
    $repeatedBenching[
        'players'
    ]
    ?? [];


/*
 * ============================================================
 * SCENARIO A
 * PARTIALLY MISSING BENCH PROJECTIONS
 * ============================================================
 */

repeatedBenchingEdgeHeading(
    'Scenario A: Partially Missing Bench Projections'
);


repeatedBenchingEdgeCheck(
    'Goalkeeper B is benched in all three gameweeks',
    (
        $players[
            2
        ][
            'bench_count'
        ]
        ?? null
    )
    ===
    3
);


repeatedBenchingEdgeCheck(
    'Goalkeeper B has two known benched projections',
    (
        $players[
            2
        ][
            'benched_projection_count'
        ]
        ?? null
    )
    ===
    2
);


repeatedBenchingEdgeCheck(
    'Goalkeeper B totals 9.0 known benched projected points',
    is_numeric(
        $players[
            2
        ][
            'total_benched_projected_points'
        ]
        ?? null
    )
    &&
    abs(
        (float) $players[
            2
        ][
            'total_benched_projected_points'
        ]
        -
        9.0
    )
    <
    0.001
);


repeatedBenchingEdgeCheck(
    'Goalkeeper B averages 4.5 across known benched projections',
    is_numeric(
        $players[
            2
        ][
            'average_benched_projected_points'
        ]
        ?? null
    )
    &&
    abs(
        (float) $players[
            2
        ][
            'average_benched_projected_points'
        ]
        -
        4.5
    )
    <
    0.001
);


repeatedBenchingEdgeCheck(
    'Goalkeeper B is repeatedly benched',
    (
        $players[
            2
        ][
            'is_repeatedly_benched'
        ]
        ?? null
    )
    ===
    true
);


repeatedBenchingEdgeCheck(
    'Goalkeeper B is meaningful repeated benching',
    (
        $players[
            2
        ][
            'is_meaningful_repeated_benching'
        ]
        ?? null
    )
    ===
    true
);


/*
 * ============================================================
 * SCENARIO B
 * ALL BENCH PROJECTIONS MISSING
 * ============================================================
 */

repeatedBenchingEdgeHeading(
    'Scenario B: All Bench Projections Missing'
);


repeatedBenchingEdgeCheck(
    'Defender E is benched in all three gameweeks',
    (
        $players[
            7
        ][
            'bench_count'
        ]
        ?? null
    )
    ===
    3
);


repeatedBenchingEdgeCheck(
    'Defender E has zero known benched projections',
    (
        $players[
            7
        ][
            'benched_projection_count'
        ]
        ?? null
    )
    ===
    0
);


repeatedBenchingEdgeCheck(
    'Defender E known benched projected-point total remains zero',
    is_numeric(
        $players[
            7
        ][
            'total_benched_projected_points'
        ]
        ?? null
    )
    &&
    abs(
        (float) $players[
            7
        ][
            'total_benched_projected_points'
        ]
    )
    <
    0.001
);


repeatedBenchingEdgeCheck(
    'Defender E average benched projection remains null',
    array_key_exists(
        'average_benched_projected_points',
        $players[
            7
        ]
        ?? []
    )
    &&
    $players[
        7
    ][
        'average_benched_projected_points'
    ]
    ===
    null
);


repeatedBenchingEdgeCheck(
    'Defender E is still repeatedly benched',
    (
        $players[
            7
        ][
            'is_repeatedly_benched'
        ]
        ?? null
    )
    ===
    true
);


repeatedBenchingEdgeCheck(
    'Defender E is not meaningful repeated benching without projection evidence',
    (
        $players[
            7
        ][
            'is_meaningful_repeated_benching'
        ]
        ?? null
    )
    ===
    false
);


/*
 * ============================================================
 * SCENARIO C
 * HORIZON SUMMARY
 * ============================================================
 */

repeatedBenchingEdgeHeading(
    'Scenario C: Missing Data Summary Behaviour'
);


$meaningfulPlayerIds =
    $repeatedBenching[
        'meaningful_repeated_benching_player_ids'
    ]
    ?? [];


repeatedBenchingEdgeCheck(
    'Goalkeeper B appears in meaningful repeated benching summary',
    in_array(
        2,
        $meaningfulPlayerIds,
        true
    )
);


repeatedBenchingEdgeCheck(
    'Defender E does not appear in meaningful repeated benching summary',
    !in_array(
        7,
        $meaningfulPlayerIds,
        true
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