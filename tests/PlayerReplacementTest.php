<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function testPass(
    string $message,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . $message
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . $message
        . "<br>";

    $failed++;
}


$replacement =
    new PlayerReplacement();


echo "============================================<br>";
echo "Player Replacement Test<br>";
echo "============================================<br>";


/*
 * ============================================================
 * CURRENT PLAYER
 * ============================================================
 */

$currentPlayer = [

    'player_id' => 100,

    'name' => 'Current Midfielder',

    'position' => 'MID',

    'price' => 8.0,

    'intelligence_score' => 60.0
];


/*
 * ============================================================
 * CANDIDATES
 * ============================================================
 */

$candidates = [

    /*
     * Best overall candidate.
     */
    [
        'player_id' => 1,
        'name' => 'Candidate Elite',
        'team_name' => 'Arsenal',
        'position' => 'MID',
        'price' => 7.5,
        'intelligence_score' => 70.0,
        'strength_rating' => 68.0,
        'value_rating' => 75.0,
        'fixture_rating' => 80.0,
        'availability_rating' => 100.0,
        'sample_confidence' => 1.0,
        'verdict' => 'Strong FPL Option'
    ],

    /*
     * Second-best intelligence.
     */
    [
        'player_id' => 2,
        'name' => 'Candidate Strong',
        'team_name' => 'Liverpool',
        'position' => 'MID',
        'price' => 8.0,
        'intelligence_score' => 66.0,
        'strength_rating' => 65.0,
        'value_rating' => 68.0,
        'fixture_rating' => 70.0,
        'availability_rating' => 100.0,
        'sample_confidence' => 1.0,
        'verdict' => 'Strong FPL Option'
    ],

    /*
     * Same intelligence as Candidate Strong but weaker
     * strength, proving Strength acts as the first tiebreaker.
     */
    [
        'player_id' => 3,
        'name' => 'Candidate Tie Strength',
        'team_name' => 'Chelsea',
        'position' => 'MID',
        'price' => 6.5,
        'intelligence_score' => 66.0,
        'strength_rating' => 60.0,
        'value_rating' => 80.0,
        'fixture_rating' => 72.0,
        'availability_rating' => 100.0,
        'sample_confidence' => 1.0,
        'verdict' => 'Strong FPL Option'
    ],

    /*
     * Wrong position.
     */
    [
        'player_id' => 4,
        'name' => 'Wrong Position',
        'position' => 'FWD',
        'price' => 7.0,
        'intelligence_score' => 90.0,
        'strength_rating' => 90.0,
        'value_rating' => 90.0,
        'availability_rating' => 100.0
    ],

    /*
     * Too expensive.
     */
    [
        'player_id' => 5,
        'name' => 'Too Expensive',
        'position' => 'MID',
        'price' => 9.0,
        'intelligence_score' => 90.0,
        'strength_rating' => 90.0,
        'value_rating' => 90.0,
        'availability_rating' => 100.0
    ],

    /*
     * Unavailable.
     */
    [
        'player_id' => 6,
        'name' => 'Unavailable Player',
        'position' => 'MID',
        'price' => 7.0,
        'intelligence_score' => 85.0,
        'strength_rating' => 85.0,
        'value_rating' => 85.0,
        'availability_rating' => 20.0
    ],

    /*
     * Missing intelligence score.
     */
    [
        'player_id' => 7,
        'name' => 'Missing Intelligence',
        'position' => 'MID',
        'price' => 5.0,
        'intelligence_score' => null,
        'strength_rating' => 70.0,
        'value_rating' => 90.0,
        'availability_rating' => 100.0
    ],

    /*
     * Current player should never be returned.
     */
    [
        'player_id' => 100,
        'name' => 'Current Midfielder',
        'position' => 'MID',
        'price' => 8.0,
        'intelligence_score' => 60.0,
        'strength_rating' => 60.0,
        'value_rating' => 60.0,
        'availability_rating' => 100.0
    ]
];


/*
 * ============================================================
 * SCENARIO A
 * STANDARD REPLACEMENTS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Standard Replacement Search<br>";
echo "============================================<br>";


$results =
    $replacement
        ->findReplacements(
            $currentPlayer,
            $candidates,
            8.0,
            10
        );


testPass(
    'Replacement search returns an array',
    is_array(
        $results
    )
);


testPass(
    'Three eligible replacements are returned',
    count(
        $results
    )
    === 3
);


testPass(
    'Highest intelligence candidate ranks first',
    (
        $results[0]['name']
        ?? null
    )
    === 'Candidate Elite'
);


testPass(
    'Strength breaks tied intelligence score',
    (
        $results[1]['name']
        ?? null
    )
    === 'Candidate Strong'
);


testPass(
    'Lower-strength tied candidate ranks third',
    (
        $results[2]['name']
        ?? null
    )
    === 'Candidate Tie Strength'
);


/*
 * ============================================================
 * SCENARIO B
 * HARD CONSTRAINTS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Replacement Constraints<br>";
echo "============================================<br>";


$resultNames =
    array_column(
        $results,
        'name'
    );


testPass(
    'Wrong position is excluded',
    !in_array(
        'Wrong Position',
        $resultNames,
        true
    )
);


testPass(
    'Over-budget player is excluded',
    !in_array(
        'Too Expensive',
        $resultNames,
        true
    )
);


testPass(
    'Unavailable player is excluded',
    !in_array(
        'Unavailable Player',
        $resultNames,
        true
    )
);


testPass(
    'Player without intelligence score is excluded',
    !in_array(
        'Missing Intelligence',
        $resultNames,
        true
    )
);


testPass(
    'Current player is excluded',
    !in_array(
        'Current Midfielder',
        $resultNames,
        true
    )
);


/*
 * ============================================================
 * SCENARIO C
 * REPLACEMENT DATA
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Replacement Data<br>";
echo "============================================<br>";


$elite =
    $results[0];


testPass(
    'Player ID is preserved',
    (
        $elite['player_id']
        ?? null
    )
    === 1
);


testPass(
    'Price is preserved',
    (
        $elite['price']
        ?? null
    )
    === 7.50
);


testPass(
    'Intelligence score is preserved',
    (
        $elite[
            'intelligence_score'
        ]
        ?? null
    )
    === 70.00
);


testPass(
    'Intelligence gain is calculated',
    (
        $elite[
            'intelligence_gain'
        ]
        ?? null
    )
    === 10.00
);


testPass(
    'Price difference is calculated',
    (
        $elite[
            'price_difference'
        ]
        ?? null
    )
    === -0.50
);


testPass(
    'Strength rating is preserved',
    (
        $elite[
            'strength_rating'
        ]
        ?? null
    )
    === 68.00
);


testPass(
    'Value rating is preserved',
    (
        $elite[
            'value_rating'
        ]
        ?? null
    )
    === 75.00
);


testPass(
    'Fixture rating is preserved',
    (
        $elite[
            'fixture_rating'
        ]
        ?? null
    )
    === 80.00
);


testPass(
    'Availability rating is preserved',
    (
        $elite[
            'availability_rating'
        ]
        ?? null
    )
    === 100.00
);


testPass(
    'Sample confidence is preserved',
    (
        $elite[
            'sample_confidence'
        ]
        ?? null
    )
    === 1.00
);


testPass(
    'Assessment verdict is preserved',
    (
        $elite['verdict']
        ?? null
    )
    === 'Strong FPL Option'
);


/*
 * ============================================================
 * SCENARIO D
 * LIMIT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Result Limit<br>";
echo "============================================<br>";


$limitedResults =
    $replacement
        ->findReplacements(
            $currentPlayer,
            $candidates,
            8.0,
            2
        );


testPass(
    'Result limit is respected',
    count(
        $limitedResults
    )
    === 2
);


/*
 * ============================================================
 * SCENARIO E
 * PRICE LIMIT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Price Limit<br>";
echo "============================================<br>";


$cheapResults =
    $replacement
        ->findReplacements(
            $currentPlayer,
            $candidates,
            7.0,
            10
        );


testPass(
    'Maximum price is respected',
    count(
        array_filter(
            $cheapResults,
            function (
                array $candidate
            ): bool {

                return (
                    $candidate['price']
                    ?? 999
                )
                >
                7.0;
            }
        )
    )
    === 0
);


/*
 * ============================================================
 * SCENARIO F
 * REPLACEMENT TYPES
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Replacement Types<br>";
echo "============================================<br>";


testPass(
    'Positive intelligence gain produces Upgrade',
    $replacement
        ->getReplacementType(
            5.0
        )
    === 'Upgrade'
);


testPass(
    'Small positive movement produces Sidegrade',
    $replacement
        ->getReplacementType(
            1.0
        )
    === 'Sidegrade'
);


testPass(
    'Small negative movement produces Sidegrade',
    $replacement
        ->getReplacementType(
            -1.0
        )
    === 'Sidegrade'
);


testPass(
    'Large negative movement produces Downgrade',
    $replacement
        ->getReplacementType(
            -5.0
        )
    === 'Downgrade'
);


testPass(
    'Missing intelligence movement produces Unknown',
    $replacement
        ->getReplacementType(
            null
        )
    === 'Unknown'
);


/*
 * ============================================================
 * SCENARIO G
 * REPLACEMENT SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Replacement Summary<br>";
echo "============================================<br>";


$replacementSummary =
    $replacement
        ->buildReplacementSummary(
            $elite
        );


testPass(
    'Replacement summary contains player name',
    str_contains(
        $replacementSummary,
        'Candidate Elite'
    )
);


testPass(
    'Replacement summary identifies upgrade',
    str_contains(
        $replacementSummary,
        'upgrade'
    )
);


testPass(
    'Replacement summary includes intelligence movement',
    str_contains(
        $replacementSummary,
        '+10.0 Intelligence'
    )
);


testPass(
    'Replacement summary includes saving',
    str_contains(
        $replacementSummary,
        'saving £0.5m'
    )
);


/*
 * ============================================================
 * SCENARIO H
 * INVALID INPUT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Invalid Input<br>";
echo "============================================<br>";


testPass(
    'Negative max price returns empty result',
    $replacement
        ->findReplacements(
            $currentPlayer,
            $candidates,
            -1,
            10
        )
    === []
);


testPass(
    'Zero result limit returns empty result',
    $replacement
        ->findReplacements(
            $currentPlayer,
            $candidates,
            8.0,
            0
        )
    === []
);


$missingPositionPlayer = [

    'player_id' => 999,

    'price' => 6.0,

    'intelligence_score' => 50.0
];


testPass(
    'Current player without position returns empty result',
    $replacement
        ->findReplacements(
            $missingPositionPlayer,
            $candidates,
            8.0,
            10
        )
    === []
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Player Replacement Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}