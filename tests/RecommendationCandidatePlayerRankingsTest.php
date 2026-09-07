<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Recommendation Candidate Player Rankings Test<br>";
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

function recommendationCandidatePlayerRankingsCheck(
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
 * CURRENT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Player Rankings Contract<br>";
echo "============================================<br>";


$reflection =
    new ReflectionClass(
        'RecommendationCandidate'
    );


$constructor =
    $reflection
        ->getConstructor();


$constructorParameters =
    $constructor !== null
        ? $constructor->getParameters()
        : [];


$parameterNames =
    array_map(
        static function (
            ReflectionParameter $parameter
        ): string {

            return
                $parameter->getName();
        },
        $constructorParameters
    );


recommendationCandidatePlayerRankingsCheck(
    'RecommendationCandidate constructor accepts playerRankings',
    in_array(
        'playerRankings',
        $parameterNames,
        true
    )
);


recommendationCandidatePlayerRankingsCheck(
    'RecommendationCandidate exposes getPlayerRankings()',
    $reflection->hasMethod(
        'getPlayerRankings'
    )
);


echo "<br>";


/*
 * ============================================================
 * STOP UNTIL PRODUCTION CONTRACT EXISTS
 * ============================================================
 */

if (
    !in_array(
        'playerRankings',
        $parameterNames,
        true
    )
    ||
    !$reflection->hasMethod(
        'getPlayerRankings'
    )
) {

    echo "============================================<br>";
    echo "Recommendation Candidate Player Rankings Test Summary<br>";
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
 * SCENARIO B
 * PRESERVE PLAYER RANKINGS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Preserve Player Rankings<br>";
echo "============================================<br>";


$playerRankings = [

    [
        'player_id' => 10,
        'fpl_player_id' => 1010,
        'name' => 'Player Ten',
        'position' => 'FWD',
        'team_id' => 1,
        'price' => 10.0,
        'intelligence_score' => 91.0,
        'rank' => 1
    ],

    [
        'player_id' => 20,
        'fpl_player_id' => 1020,
        'name' => 'Player Twenty',
        'position' => 'DEF',
        'team_id' => 2,
        'price' => 6.0,
        'intelligence_score' => 81.0,
        'rank' => 2
    ]
];


$playerProjections = [
    [
        'player_id' => 10,
        'projected_points' => 8.4
    ]
];


$gameweekDecision = [
    'status' => 'success',
    'starting_xi' => [],
    'captain' => [],
    'vice_captain' => [],
    'transfer_advice' => []
];


$chipRecommendations = [];


/*
 * The constructor invocation below deliberately represents the
 * desired new contract.
 *
 * Existing RecommendationCandidate validation remains
 * authoritative for all other fields.
 */
$candidate =
    new RecommendationCandidate(
        3,
        2702264,
        '2026-09-03 12:00:00',
        '2026-09-04 18:30:00',
        $playerRankings,
        $playerProjections,
        [],
        [],
        [],
        $gameweekDecision,
        $chipRecommendations
    );


recommendationCandidatePlayerRankingsCheck(
    'getPlayerRankings() returns preserved ranking evidence unchanged',
    $candidate
        ->getPlayerRankings()
    ===
    $playerRankings
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * ARRAY EXPORT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Array Export<br>";
echo "============================================<br>";


$candidateArray =
    $candidate
        ->toArray();


recommendationCandidatePlayerRankingsCheck(
    'toArray() contains player_rankings',
    array_key_exists(
        'player_rankings',
        $candidateArray
    )
);


recommendationCandidatePlayerRankingsCheck(
    'toArray() preserves player rankings unchanged',
    (
        $candidateArray[
            'player_rankings'
        ]
        ??
        null
    )
    ===
    $playerRankings
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * RANKINGS REMAIN DISTINCT FROM PROJECTIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Rankings Remain Distinct From Projections<br>";
echo "============================================<br>";


recommendationCandidatePlayerRankingsCheck(
    'Player rankings and player projections remain separate evidence',
    $candidate
        ->getPlayerRankings()
    !==
    $candidate
        ->getPlayerProjections()
);


recommendationCandidatePlayerRankingsCheck(
    'Player projections retain their existing contract',
    $candidate
        ->getPlayerProjections()
    ===
    $playerProjections
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * SOURCE EVIDENCE IS NOT ALTERED
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Source Evidence Is Not Altered<br>";
echo "============================================<br>";


recommendationCandidatePlayerRankingsCheck(
    'RecommendationCandidate does not reinterpret ranking evidence',
    (
        $candidate
            ->getPlayerRankings()[
                0
            ][
                'intelligence_score'
            ]
        ??
        null
    )
    === 91.0
    &&
    (
        $candidate
            ->getPlayerRankings()[
                0
            ][
                'rank'
            ]
        ??
        null
    )
    === 1
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Recommendation Candidate Player Rankings Test Summary<br>";
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