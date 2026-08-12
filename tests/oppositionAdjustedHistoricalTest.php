<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * OPPOSITION ADJUSTED HISTORICAL TEST HARNESS
 * ============================================================
 *
 * This test uses controlled historical fixtures.
 *
 * It does NOT modify the real fixtures table.
 *
 * The purpose is to validate that:
 *
 * - Strong opposition increases the value of a good result
 * - Weak opposition decreases the value of a good result
 * - Strong opposition reduces the punishment for a poor result
 * - Weak opposition increases the punishment for a poor result
 * - Results are correctly classified
 * - Match counts are correct
 */


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed = 0;
$failed = 0;


function testResult(
    string $name,
    bool $condition
): void {

    global $passed;
    global $failed;

    if ($condition) {

        echo "PASS: {$name}<br>";

        $passed++;

    } else {

        echo "FAIL: {$name}<br>";

        $failed++;
    }
}


/*
 * ============================================================
 * TEST BASELINE DATA
 * ============================================================
 */

$baselines = [

    1 => [
        'id' => 1,
        'name' => 'Arsenal',
        'home' => 100,
        'away' => 100,
        'overall' => 100
    ],

    2 => [
        'id' => 2,
        'name' => 'Weak Team',
        'home' => 20,
        'away' => 20,
        'overall' => 20
    ],

    3 => [
        'id' => 3,
        'name' => 'Average Team',
        'home' => 50,
        'away' => 50,
        'overall' => 50
    ],

    4 => [
        'id' => 4,
        'name' => 'Strong Team',
        'home' => 80,
        'away' => 80,
        'overall' => 80
    ]
];


/*
 * ============================================================
 * CREATE MODELS
 * ============================================================
 */

$oppositionAdjustedPerformance =
    new OppositionAdjustedPerformance();


$teamStrengths = $baselines;


/*
 * ============================================================
 * SCENARIO 1
 * ============================================================
 *
 * Arsenal beats a weak team 2-0.
 *
 * Expected:
 *
 * Opponent strength = 20
 * Expected performance = 65
 * Actual performance = 100
 * Delta = +35
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ]
];


$weakWin =
    $oppositionAdjustedPerformance->analyse(
        $fixtures,
        $teamStrengths,
        1
    );


echo "<h3>2-0 Win Against Weak Team</h3>";

echo "<pre>";

print_r(
    $weakWin
);

echo "</pre>";


/*
 * ============================================================
 * SCENARIO 2
 * ============================================================
 *
 * Arsenal beats a strong team 2-0.
 *
 * Expected:
 *
 * Opponent strength = 80
 * Expected performance = 35
 * Actual performance = 100
 * Delta = +65
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ]
];


$strongWin =
    $oppositionAdjustedPerformance->analyse(
        $fixtures,
        $teamStrengths,
        1
    );


echo "<h3>2-0 Win Against Strong Team</h3>";

echo "<pre>";

print_r(
    $strongWin
);

echo "</pre>";


/*
 * ============================================================
 * WIN COMPARISON
 * ============================================================
 */

testResult(
    '2-0 win against weak and strong teams produce different adjusted results',
    $weakWin['average_delta'] !== $strongWin['average_delta']
);

testResult(
    '2-0 win against strong team produces higher adjusted performance',
    $strongWin['average_delta'] > $weakWin['average_delta']
);


/*
 * ============================================================
 * SCENARIO 3
 * ============================================================
 *
 * Arsenal loses 0-2 to a weak team.
 *
 * Expected delta = -65
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ]
];


$weakLoss =
    $oppositionAdjustedPerformance->analyse(
        $fixtures,
        $teamStrengths,
        1
    );


echo "<h3>0-2 Loss Against Weak Team</h3>";

echo "<pre>";

print_r(
    $weakLoss
);

echo "</pre>";


/*
 * ============================================================
 * SCENARIO 4
 * ============================================================
 *
 * Arsenal loses 0-2 to a strong team.
 *
 * Expected delta = -35
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ]
];


$strongLoss =
    $oppositionAdjustedPerformance->analyse(
        $fixtures,
        $teamStrengths,
        1
    );


echo "<h3>0-2 Loss Against Strong Team</h3>";

echo "<pre>";

print_r(
    $strongLoss
);

echo "</pre>";


/*
 * ============================================================
 * LOSS COMPARISON
 * ============================================================
 */

testResult(
    '0-2 loss against weak and strong teams produce different adjusted results',
    $weakLoss['average_delta'] !== $strongLoss['average_delta']
);

testResult(
    '0-2 loss against strong team produces higher adjusted performance',
    $strongLoss['average_delta'] > $weakLoss['average_delta']
);


/*
 * ============================================================
 * SCENARIO 5
 * ============================================================
 *
 * Arsenal draws 1-1 with a weak team.
 *
 * Expected delta = -15
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 1,
        'away_score' => 1
    ]
];


$weakDraw =
    $oppositionAdjustedPerformance->analyse(
        $fixtures,
        $teamStrengths,
        1
    );


echo "<h3>1-1 Draw Against Weak Team</h3>";

echo "<pre>";

print_r(
    $weakDraw
);

echo "</pre>";


/*
 * ============================================================
 * SCENARIO 6
 * ============================================================
 *
 * Arsenal draws 1-1 with a strong team.
 *
 * Expected delta = +15
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 1,
        'away_score' => 1
    ]
];


$strongDraw =
    $oppositionAdjustedPerformance->analyse(
        $fixtures,
        $teamStrengths,
        1
    );


echo "<h3>1-1 Draw Against Strong Team</h3>";

echo "<pre>";

print_r(
    $strongDraw
);

echo "</pre>";


/*
 * ============================================================
 * DRAW COMPARISON
 * ============================================================
 */

testResult(
    '1-1 draw against weak and strong teams produce different adjusted results',
    $weakDraw['average_delta'] !== $strongDraw['average_delta']
);

testResult(
    '1-1 draw against strong team produces higher adjusted performance',
    $strongDraw['average_delta'] > $weakDraw['average_delta']
);


/*
 * ============================================================
 * RESULT ORDERING
 * ============================================================
 *
 * A win should always outperform a draw.
 *
 * A draw should always outperform a loss.
 *
 * This should hold regardless of opponent strength.
 */


/*
 * Strong opposition
 */

testResult(
    'Against strong opposition, win > draw',
    $strongWin['average_performance'] >
    $strongDraw['average_performance']
);

testResult(
    'Against strong opposition, draw > loss',
    $strongDraw['average_performance'] >
    $strongLoss['average_performance']
);


/*
 * Weak opposition
 */

testResult(
    'Against weak opposition, win > draw',
    $weakWin['average_performance'] >
    $weakDraw['average_performance']
);

testResult(
    'Against weak opposition, draw > loss',
    $weakDraw['average_performance'] >
    $weakLoss['average_performance']
);


/*
 * ============================================================
 * MATCH COUNT TESTS
 * ============================================================
 */

testResult(
    'Weak-team win analyses one match',
    $weakWin['played'] === 1
);

testResult(
    'Strong-team win analyses one match',
    $strongWin['played'] === 1
);

testResult(
    'Weak-team loss analyses one match',
    $weakLoss['played'] === 1
);

testResult(
    'Strong-team loss analyses one match',
    $strongLoss['played'] === 1
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";

echo "============================================<br>";
echo "Opposition Adjusted Historical Test Summary<br>";
echo "============================================<br>";

echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}