<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Repeated Benching Test<br>";
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

function repeatedBenchingCheck(
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


function repeatedBenchingHeading(
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
function buildRepeatedBenchingPlayer(
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
 * The projected Starting XI changes enough to create several
 * different benching patterns.
 *
 * Goalkeeper B — ID 2
 *
 * GW2 bench = 2.0
 * GW3 bench = 2.0
 * GW4 bench = 2.0
 *
 * Bench count          = 3
 * Average benched xP   = 2.0
 * Repeatedly benched   = YES
 * Meaningful benching  = NO
 *
 *
 *
 * Defender D — ID 6
 *
 * GW2 bench = 4.5
 * GW3 bench = 4.0
 * GW4 bench = 4.5
 *
 * Bench count          = 3
 * Average benched xP   = 4.333...
 * Repeatedly benched   = YES
 * Meaningful benching  = YES
 *
 *
 * Defender E — ID 7
 *
 * GW2 bench = 1.0
 * GW3 bench = 1.0
 * GW4 bench = 1.0
 *
 * Bench count          = 3
 * Average benched xP   = 1.0
 * Repeatedly benched   = YES
 * Meaningful benching  = NO
 *
 *
 * Forward C — ID 15
 *
 * GW2 bench = 3.5
 * GW3 start = 8.0
 * GW4 bench = 3.5
 *
 * Bench count          = 2
 * Average benched xP   = 3.5
 * Repeatedly benched   = YES
 * Meaningful benching  = YES
 *
 *
 * Repeated bench threshold:
 *
 * bench_count >= 2
 *
 *
 * Meaningful projection threshold:
 *
 * average_benched_projected_points >= 3.0
 *
 * ============================================================
 */

$squad = [

    /*
     * --------------------------------------------------------
     * GOALKEEPERS
     * --------------------------------------------------------
     */

    buildRepeatedBenchingPlayer(
        1,
        'Goalkeeper A',
        'GK',
        5.0,
        5.0,
        5.0
    ),

    buildRepeatedBenchingPlayer(
        2,
        'Goalkeeper B',
        'GK',
        2.0,
        2.0,
        2.0
    ),


    /*
     * --------------------------------------------------------
     * DEFENDERS
     * --------------------------------------------------------
     */

    buildRepeatedBenchingPlayer(
        3,
        'Defender A',
        'DEF',
        7.0,
        7.0,
        7.0
    ),

    buildRepeatedBenchingPlayer(
        4,
        'Defender B',
        'DEF',
        6.5,
        6.5,
        6.5
    ),

    buildRepeatedBenchingPlayer(
        5,
        'Defender C',
        'DEF',
        6.0,
        6.0,
        6.0
    ),

    buildRepeatedBenchingPlayer(
        6,
        'Defender D',
        'DEF',
        4.5,
        4.0,
        4.5
    ),

    buildRepeatedBenchingPlayer(
        7,
        'Defender E',
        'DEF',
        1.0,
        1.0,
        1.0
    ),


    /*
     * --------------------------------------------------------
     * MIDFIELDERS
     * --------------------------------------------------------
     */

    buildRepeatedBenchingPlayer(
        8,
        'Midfielder A',
        'MID',
        9.0,
        9.0,
        9.0
    ),

    buildRepeatedBenchingPlayer(
        9,
        'Midfielder B',
        'MID',
        8.5,
        8.5,
        8.5
    ),

    buildRepeatedBenchingPlayer(
        10,
        'Midfielder C',
        'MID',
        8.0,
        8.0,
        8.0
    ),

    buildRepeatedBenchingPlayer(
        11,
        'Midfielder D',
        'MID',
        7.5,
        7.5,
        7.5
    ),

    buildRepeatedBenchingPlayer(
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

    buildRepeatedBenchingPlayer(
        13,
        'Forward A',
        'FWD',
        10.0,
        10.0,
        10.0
    ),

    buildRepeatedBenchingPlayer(
        14,
        'Forward B',
        'FWD',
        9.0,
        9.0,
        9.0
    ),

    buildRepeatedBenchingPlayer(
        15,
        'Forward C',
        'FWD',
        3.5,
        8.0,
        3.5
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


/*
 * ============================================================
 * SCENARIO A
 * STRUCTURE
 * ============================================================
 */

repeatedBenchingHeading(
    'Scenario A: Repeated Benching Structure'
);


repeatedBenchingCheck(
    'Horizon exposes repeated benching intelligence',
    isset(
        $result[
            'repeated_benching'
        ]
    )
    &&
    is_array(
        $repeatedBenching
    )
);


repeatedBenchingCheck(
    'Repeated benching intelligence covers three gameweeks',
    (
        $repeatedBenching[
            'gameweek_count'
        ]
        ?? null
    )
    ===
    3
);


repeatedBenchingCheck(
    'Repeated bench threshold equals two gameweeks',
    (
        $repeatedBenching[
            'repeated_bench_threshold'
        ]
        ?? null
    )
    ===
    2
);


repeatedBenchingCheck(
    'Meaningful bench projection threshold equals 3.0',
    is_numeric(
        $repeatedBenching[
            'meaningful_projection_threshold'
        ]
        ?? null
    )
    &&
    abs(
        (float) $repeatedBenching[
            'meaningful_projection_threshold'
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
 * PLAYER BENCH COUNTS
 * ============================================================
 */

repeatedBenchingHeading(
    'Scenario B: Player Bench Counts'
);


$players =
    $repeatedBenching[
        'players'
    ]
    ?? [];


repeatedBenchingCheck(
    'Defender D is benched in all three gameweeks',
    (
        $players[
            6
        ][
            'bench_count'
        ]
        ?? null
    )
    ===
    3
);


repeatedBenchingCheck(
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


repeatedBenchingCheck(
    'Forward C is benched in two gameweeks',
    (
        $players[
            15
        ][
            'bench_count'
        ]
        ?? null
    )
    ===
    2
);


repeatedBenchingCheck(
    'Forward C starts in one gameweek',
    (
        $players[
            15
        ][
            'start_count'
        ]
        ?? null
    )
    ===
    1
);


/*
 * ============================================================
 * SCENARIO C
 * BENCHED PROJECTED OUTPUT
 * ============================================================
 */

repeatedBenchingHeading(
    'Scenario C: Benched Projected Output'
);


repeatedBenchingCheck(
    'Defender D strands 13.0 projected points on the bench',
    is_numeric(
        $players[
            6
        ][
            'total_benched_projected_points'
        ]
        ?? null
    )
    &&
    abs(
        (float) $players[
            6
        ][
            'total_benched_projected_points'
        ]
        -
        13.0
    )
    <
    0.001
);


repeatedBenchingCheck(
    'Defender D averages approximately 4.33 projected points when benched',
    is_numeric(
        $players[
            6
        ][
            'average_benched_projected_points'
        ]
        ?? null
    )
    &&
    abs(
        (float) $players[
            6
        ][
            'average_benched_projected_points'
        ]
        -
        (
            13.0
            /
            3.0
        )
    )
    <
    0.001
);


repeatedBenchingCheck(
    'Defender E averages 1.0 projected point when benched',
    is_numeric(
        $players[
            7
        ][
            'average_benched_projected_points'
        ]
        ?? null
    )
    &&
    abs(
        (float) $players[
            7
        ][
            'average_benched_projected_points'
        ]
        -
        1.0
    )
    <
    0.001
);


repeatedBenchingCheck(
    'Forward C averages 3.5 projected points when benched',
    is_numeric(
        $players[
            15
        ][
            'average_benched_projected_points'
        ]
        ?? null
    )
    &&
    abs(
        (float) $players[
            15
        ][
            'average_benched_projected_points'
        ]
        -
        3.5
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO D
 * REPEATED AND MEANINGFUL BENCHING
 * ============================================================
 */

repeatedBenchingHeading(
    'Scenario D: Repeated And Meaningful Benching'
);


repeatedBenchingCheck(
    'Defender D is classified as repeatedly benched',
    (
        $players[
            6
        ][
            'is_repeatedly_benched'
        ]
        ?? null
    )
    ===
    true
);


repeatedBenchingCheck(
    'Defender D is classified as meaningful repeated benching',
    (
        $players[
            6
        ][
            'is_meaningful_repeated_benching'
        ]
        ?? null
    )
    ===
    true
);


repeatedBenchingCheck(
    'Defender E is repeatedly benched',
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


repeatedBenchingCheck(
    'Defender E is not meaningful repeated benching',
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


repeatedBenchingCheck(
    'Forward C is classified as meaningful repeated benching',
    (
        $players[
            15
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
 * SCENARIO E
 * HORIZON SUMMARY
 * ============================================================
 */

repeatedBenchingHeading(
    'Scenario E: Horizon Repeated Benching Summary'
);


$repeatedPlayerIds =
    $repeatedBenching[
        'repeatedly_benched_player_ids'
    ]
    ?? [];


sort(
    $repeatedPlayerIds,
    SORT_NUMERIC
);


$meaningfulPlayerIds =
    $repeatedBenching[
        'meaningful_repeated_benching_player_ids'
    ]
    ?? [];


sort(
    $meaningfulPlayerIds,
    SORT_NUMERIC
);


repeatedBenchingCheck(
    'Four players are repeatedly benched',
    (
        $repeatedBenching[
            'repeatedly_benched_player_count'
        ]
        ?? null
    )
    ===
    4
);


repeatedBenchingCheck(
    'Repeatedly benched players are Goalkeeper B, Defender D, Defender E and Forward C',
    $repeatedPlayerIds
    ===
    [
        2,
        6,
        7,
        15
    ]
);


repeatedBenchingCheck(
    'Two players have meaningful repeated benching',
    (
        $repeatedBenching[
            'meaningful_repeated_benching_player_count'
        ]
        ?? null
    )
    ===
    2
);


repeatedBenchingCheck(
    'Meaningful repeated benching identifies Defender D and Forward C',
    $meaningfulPlayerIds
    ===
    [
        6,
        15
    ]
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