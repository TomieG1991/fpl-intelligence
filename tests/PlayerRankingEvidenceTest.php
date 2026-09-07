<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Ranking Evidence Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function playerRankingEvidenceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Class Contract<br>";
echo "============================================<br>";


playerRankingEvidenceCheck(
    'PlayerRankingEvidence class exists',
    class_exists(
        'PlayerRankingEvidence'
    )
);


if (
    class_exists(
        'PlayerRankingEvidence'
    )
) {

    $reflection =
        new ReflectionClass(
            'PlayerRankingEvidence'
        );


    playerRankingEvidenceCheck(
        'PlayerRankingEvidence exposes build()',
        $reflection->hasMethod(
            'build'
        )
    );

} else {

    playerRankingEvidenceCheck(
        'PlayerRankingEvidence exposes build()',
        false
    );
}


echo "<br>";


/*
 * ============================================================
 * STOP IF PRODUCTION CLASS DOES NOT YET EXIST
 * ============================================================
 */

if (
    !class_exists(
        'PlayerRankingEvidence'
    )
) {

    echo "============================================<br>";
    echo "Player Ranking Evidence Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br><br>";

    echo "RESULT: TESTS FAILED ❌";

    exit;
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

$service =
    new PlayerRankingEvidence();


$playerSummaries = [

    [
        'player_id' => 30,
        'fpl_player_id' => 1030,
        'name' => 'Player Thirty',
        'position' => 'MID',
        'team_id' => 3,
        'price' => 8.5,
        'intelligence_score' => 72.5,
        'projected_points' => 5.8
    ],

    [
        'player_id' => 10,
        'fpl_player_id' => 1010,
        'name' => 'Player Ten',
        'position' => 'FWD',
        'team_id' => 1,
        'price' => 10.0,
        'intelligence_score' => 91.0,
        'projected_points' => 8.4
    ],

    [
        'player_id' => 20,
        'fpl_player_id' => 1020,
        'name' => 'Player Twenty',
        'position' => 'DEF',
        'team_id' => 2,
        'price' => 6.0,
        'intelligence_score' => 81.0,
        'projected_points' => 6.2
    ]
];


/*
 * ============================================================
 * SCENARIO B
 * COMPLETE PLAYER POOL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Complete Player Pool<br>";
echo "============================================<br>";


$evidence =
    $service
        ->build(
            $playerSummaries
        );


playerRankingEvidenceCheck(
    'Ranking evidence returns one row per supplied player',
    count(
        $evidence
    )
    === 3
);


$playerIds =
    array_column(
        $evidence,
        'player_id'
    );


sort(
    $playerIds
);


playerRankingEvidenceCheck(
    'Ranking evidence preserves every supplied player',
    $playerIds
    === [
        10,
        20,
        30
    ]
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * INTELLIGENCE RANKING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Intelligence Ranking<br>";
echo "============================================<br>";


playerRankingEvidenceCheck(
    'Highest Intelligence Score is ranked first',
    (
        $evidence[
            0
        ][
            'player_id'
        ]
        ??
        null
    )
    === 10
    &&
    (
        $evidence[
            0
        ][
            'rank'
        ]
        ??
        null
    )
    === 1
);


playerRankingEvidenceCheck(
    'Second-highest Intelligence Score is ranked second',
    (
        $evidence[
            1
        ][
            'player_id'
        ]
        ??
        null
    )
    === 20
    &&
    (
        $evidence[
            1
        ][
            'rank'
        ]
        ??
        null
    )
    === 2
);


playerRankingEvidenceCheck(
    'Lowest Intelligence Score is ranked third',
    (
        $evidence[
            2
        ][
            'player_id'
        ]
        ??
        null
    )
    === 30
    &&
    (
        $evidence[
            2
        ][
            'rank'
        ]
        ??
        null
    )
    === 3
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * PRESERVED HISTORICAL CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Preserved Historical Contract<br>";
echo "============================================<br>";


$first =
    $evidence[
        0
    ]
    ??
    [];


playerRankingEvidenceCheck(
    'Player identity is preserved',
    (
        $first[
            'player_id'
        ]
        ??
        null
    )
    === 10
    &&
    (
        $first[
            'fpl_player_id'
        ]
        ??
        null
    )
    === 1010
);


playerRankingEvidenceCheck(
    'Player descriptive evidence is preserved',
    (
        $first[
            'name'
        ]
        ??
        null
    )
    === 'Player Ten'
    &&
    (
        $first[
            'position'
        ]
        ??
        null
    )
    === 'FWD'
    &&
    (
        $first[
            'team_id'
        ]
        ??
        null
    )
    === 1
);


playerRankingEvidenceCheck(
    'Recommendation-time price is preserved',
    (
        $first[
            'price'
        ]
        ??
        null
    )
    === 10.0
);


playerRankingEvidenceCheck(
    'Recommendation-time Intelligence Score is preserved',
    (
        $first[
            'intelligence_score'
        ]
        ??
        null
    )
    === 91.0
);


playerRankingEvidenceCheck(
    'Unrelated Player Intelligence fields are not copied automatically',
    !array_key_exists(
        'projected_points',
        $first
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * DETERMINISTIC TIE BREAKING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Deterministic Tie Breaking<br>";
echo "============================================<br>";


$tiedEvidence =
    $service
        ->build(
            [
                [
                    'player_id' => 200,
                    'fpl_player_id' => 1200,
                    'name' => 'Higher ID',
                    'position' => 'MID',
                    'team_id' => 2,
                    'price' => 7.0,
                    'intelligence_score' => 80.0
                ],
                [
                    'player_id' => 100,
                    'fpl_player_id' => 1100,
                    'name' => 'Lower ID',
                    'position' => 'MID',
                    'team_id' => 1,
                    'price' => 7.0,
                    'intelligence_score' => 80.0
                ]
            ]
        );


playerRankingEvidenceCheck(
    'Equal Intelligence Scores use lower player ID first',
    (
        $tiedEvidence[
            0
        ][
            'player_id'
        ]
        ??
        null
    )
    === 100
    &&
    (
        $tiedEvidence[
            1
        ][
            'player_id'
        ]
        ??
        null
    )
    === 200
);


playerRankingEvidenceCheck(
    'Tied players still receive deterministic sequential ranks',
    (
        $tiedEvidence[
            0
        ][
            'rank'
        ]
        ??
        null
    )
    === 1
    &&
    (
        $tiedEvidence[
            1
        ][
            'rank'
        ]
        ??
        null
    )
    === 2
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * INVALID / UNUSABLE EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Invalid / Unusable Evidence<br>";
echo "============================================<br>";


playerRankingEvidenceCheck(
    'Empty Player Intelligence summaries produce no ranking evidence',
    $service
        ->build(
            []
        )
    === []
);


$partiallyInvalidEvidence =
    $service
        ->build(
            [
                [
                    'player_id' => 1,
                    'fpl_player_id' => 1001,
                    'name' => 'Valid Player',
                    'position' => 'GK',
                    'team_id' => 1,
                    'price' => 5.0,
                    'intelligence_score' => 60.0
                ],
                [
                    'player_id' => 2,
                    'fpl_player_id' => 1002,
                    'name' => 'Missing Score',
                    'position' => 'DEF',
                    'team_id' => 1,
                    'price' => 5.0,
                    'intelligence_score' => null
                ],
                [
                    'player_id' => 0,
                    'fpl_player_id' => 1003,
                    'name' => 'Invalid Identity',
                    'position' => 'MID',
                    'team_id' => 2,
                    'price' => 6.0,
                    'intelligence_score' => 90.0
                ]
            ]
        );


playerRankingEvidenceCheck(
    'Players without usable ranking evidence are excluded',
    count(
        $partiallyInvalidEvidence
    )
    === 1
    &&
    (
        $partiallyInvalidEvidence[
            0
        ][
            'player_id'
        ]
        ??
        null
    )
    === 1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * SOURCE EVIDENCE IS NOT MUTATED
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Source Evidence Is Not Mutated<br>";
echo "============================================<br>";


$sourceEvidence =
    $playerSummaries;


$service
    ->build(
        $playerSummaries
    );


playerRankingEvidenceCheck(
    'Building ranking evidence does not alter source summaries',
    $playerSummaries
    ===
    $sourceEvidence
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Ranking Evidence Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}