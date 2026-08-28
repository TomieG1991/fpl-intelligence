<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Weak Fixture Clusters Test<br>";
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

function weakClusterCheck(
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


function weakClusterHeading(
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
 * Build one synthetic player with separate projections
 * for GW2, GW3 and GW4.
 */
function buildWeakClusterPlayer(
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
 * The strongest legal XI remains 3-5-2 throughout.
 *
 * Weak threshold:
 *
 * projected_points < 3.0
 *
 *
 * ------------------------------------------------------------
 * GW2
 * ------------------------------------------------------------
 *
 * Weak Starting XI:
 *
 * Defender C = 2.5
 *
 * Weak count = 1
 * Cluster    = NO
 *
 *
 * ------------------------------------------------------------
 * GW3
 * ------------------------------------------------------------
 *
 * Weak Starting XI:
 *
 * Goalkeeper A = 2.0
 * Defender A   = 2.5
 * Midfielder A = 2.0
 * Forward A    = 2.5
 *
 * Weak count = 4
 * Cluster    = YES
 *
 *
 * ------------------------------------------------------------
 * GW4
 * ------------------------------------------------------------
 *
 * Weak Starting XI:
 *
 * Defender B   = 2.0
 * Midfielder B = 2.5
 *
 * Weak count = 2
 * Cluster    = NO
 *
 *
 * Expected horizon:
 *
 * Cluster threshold       = 3
 * Cluster gameweeks       = [3]
 * Cluster gameweek count  = 1
 * Worst gameweek          = 3
 * Maximum weak starters   = 4
 * ============================================================
 */

$squad = [

    /*
     * --------------------------------------------------------
     * GOALKEEPERS
     * --------------------------------------------------------
     */

    buildWeakClusterPlayer(
        1,
        'Goalkeeper A',
        'GK',
        5.0,
        2.0,
        5.0
    ),

    buildWeakClusterPlayer(
        2,
        'Goalkeeper B',
        'GK',
        1.0,
        1.0,
        1.0
    ),


    /*
     * --------------------------------------------------------
     * DEFENDERS
     * --------------------------------------------------------
     */

    buildWeakClusterPlayer(
        3,
        'Defender A',
        'DEF',
        6.0,
        2.5,
        6.0
    ),

    buildWeakClusterPlayer(
        4,
        'Defender B',
        'DEF',
        5.5,
        5.5,
        2.0
    ),

    buildWeakClusterPlayer(
        5,
        'Defender C',
        'DEF',
        2.5,
        5.0,
        5.0
    ),

    buildWeakClusterPlayer(
        6,
        'Defender D',
        'DEF',
        0.5,
        0.5,
        0.5
    ),

    buildWeakClusterPlayer(
        7,
        'Defender E',
        'DEF',
        0.25,
        0.25,
        0.25
    ),


    /*
     * --------------------------------------------------------
     * MIDFIELDERS
     * --------------------------------------------------------
     */

    buildWeakClusterPlayer(
        8,
        'Midfielder A',
        'MID',
        8.0,
        2.0,
        8.0
    ),

    buildWeakClusterPlayer(
        9,
        'Midfielder B',
        'MID',
        7.5,
        7.5,
        2.5
    ),

    buildWeakClusterPlayer(
        10,
        'Midfielder C',
        'MID',
        7.0,
        7.0,
        7.0
    ),

    buildWeakClusterPlayer(
        11,
        'Midfielder D',
        'MID',
        6.5,
        6.5,
        6.5
    ),

    buildWeakClusterPlayer(
        12,
        'Midfielder E',
        'MID',
        6.0,
        6.0,
        6.0
    ),


    /*
     * --------------------------------------------------------
     * FORWARDS
     * --------------------------------------------------------
     */

    buildWeakClusterPlayer(
        13,
        'Forward A',
        'FWD',
        9.0,
        2.5,
        9.0
    ),

    buildWeakClusterPlayer(
        14,
        'Forward B',
        'FWD',
        8.5,
        8.5,
        8.5
    ),

    buildWeakClusterPlayer(
        15,
        'Forward C',
        'FWD',
        0.5,
        0.5,
        0.5
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


$weakFixtureClusters =
    $result[
        'weak_fixture_clusters'
    ]
    ?? [];


/*
 * ============================================================
 * SCENARIO A
 * STRUCTURE
 * ============================================================
 */

weakClusterHeading(
    'Scenario A: Weak Fixture Cluster Structure'
);


weakClusterCheck(
    'Horizon exposes weak fixture cluster intelligence',
    isset(
        $result[
            'weak_fixture_clusters'
        ]
    )
    &&
    is_array(
        $weakFixtureClusters
    )
);


weakClusterCheck(
    'Weak fixture intelligence covers three gameweeks',
    (
        $weakFixtureClusters[
            'gameweek_count'
        ]
        ?? null
    )
    ===
    3
);


weakClusterCheck(
    'Weak projection threshold equals 3.0',
    is_numeric(
        $weakFixtureClusters[
            'weak_projection_threshold'
        ]
        ?? null
    )
    &&
    abs(
        (float) $weakFixtureClusters[
            'weak_projection_threshold'
        ]
        -
        3.0
    )
    <
    0.001
);


weakClusterCheck(
    'Cluster threshold equals three weak starters',
    (
        $weakFixtureClusters[
            'cluster_threshold'
        ]
        ?? null
    )
    ===
    3
);


/*
 * ============================================================
 * SCENARIO B
 * WEAK STARTERS BY GAMEWEEK
 * ============================================================
 */

weakClusterHeading(
    'Scenario B: Weak Starters By Gameweek'
);


$clusterGameweeks =
    $weakFixtureClusters[
        'gameweeks'
    ]
    ?? [];


weakClusterCheck(
    'GW2 contains one weak Starting XI player',
    (
        $clusterGameweeks[
            2
        ][
            'weak_player_count'
        ]
        ?? null
    )
    ===
    1
);


weakClusterCheck(
    'GW3 contains four weak Starting XI players',
    (
        $clusterGameweeks[
            3
        ][
            'weak_player_count'
        ]
        ?? null
    )
    ===
    4
);


weakClusterCheck(
    'GW4 contains two weak Starting XI players',
    (
        $clusterGameweeks[
            4
        ][
            'weak_player_count'
        ]
        ?? null
    )
    ===
    2
);


/*
 * ============================================================
 * SCENARIO C
 * WEAK PLAYER IDENTITIES
 * ============================================================
 */

weakClusterHeading(
    'Scenario C: Weak Player Identities'
);


$gw3WeakPlayerIds =
    $clusterGameweeks[
        3
    ][
        'weak_player_ids'
    ]
    ?? [];


sort(
    $gw3WeakPlayerIds,
    SORT_NUMERIC
);


weakClusterCheck(
    'GW3 identifies the four expected weak starters',
    $gw3WeakPlayerIds
    ===
    [
        1,
        3,
        8,
        13
    ]
);


weakClusterCheck(
    'GW2 is not classified as a weak fixture cluster',
    (
        $clusterGameweeks[
            2
        ][
            'is_cluster'
        ]
        ?? null
    )
    ===
    false
);


weakClusterCheck(
    'GW3 is classified as a weak fixture cluster',
    (
        $clusterGameweeks[
            3
        ][
            'is_cluster'
        ]
        ?? null
    )
    ===
    true
);


weakClusterCheck(
    'GW4 is not classified as a weak fixture cluster',
    (
        $clusterGameweeks[
            4
        ][
            'is_cluster'
        ]
        ?? null
    )
    ===
    false
);


/*
 * ============================================================
 * SCENARIO D
 * HORIZON SUMMARY
 * ============================================================
 */

weakClusterHeading(
    'Scenario D: Horizon Cluster Summary'
);


weakClusterCheck(
    'One gameweek is classified as a weak fixture cluster',
    (
        $weakFixtureClusters[
            'cluster_gameweek_count'
        ]
        ?? null
    )
    ===
    1
);


weakClusterCheck(
    'Cluster gameweek list contains only GW3',
    (
        $weakFixtureClusters[
            'cluster_gameweeks'
        ]
        ?? null
    )
    ===
    [
        3
    ]
);


weakClusterCheck(
    'GW3 is identified as the worst weak-projection gameweek',
    (
        $weakFixtureClusters[
            'worst_gameweek'
        ]
        ?? null
    )
    ===
    3
);


weakClusterCheck(
    'Maximum weak Starting XI count equals four',
    (
        $weakFixtureClusters[
            'max_weak_player_count'
        ]
        ?? null
    )
    ===
    4
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