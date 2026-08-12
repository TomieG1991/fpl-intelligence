<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * OPPOSITION ADJUSTED PERFORMANCE MODEL TEST
 * ============================================================
 *
 * Purpose:
 *
 * Validate how opposition-adjusted performance should behave
 * before integrating it into TeamStrengthModel.
 *
 * IMPORTANT:
 *
 * This test does NOT modify the production model.
 *
 * It uses the existing OppositionAdjustedPerformance class
 * and tests the mathematical behaviour we expect.
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
 * CONTROLLED TEAM STRENGTHS
 * ============================================================
 */

$teamStrengths = [

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


$oppositionAdjusted =
    new OppositionAdjustedPerformance();


/*
 * ============================================================
 * HELPER
 * ============================================================
 *
 * Analyse one controlled fixture.
 */

function analyseTestFixture(
    int $opponentId,
    int $teamScore,
    int $opponentScore,
    array $teamStrengths,
    OppositionAdjustedPerformance $model
): array {

    $fixture = [

        'gameweek' => 1,

        'home_team_id' => 1,

        'away_team_id' => $opponentId,

        'finished' => 1,

        'home_score' => $teamScore,

        'away_score' => $opponentScore
    ];


    return $model->analyse(
        [$fixture],
        $teamStrengths,
        1
    );
}


/*
 * ============================================================
 * CREATE CONTROLLED RESULTS
 * ============================================================
 */


/*
 * ------------------------------------------------------------
 * Weak opposition
 * ------------------------------------------------------------
 */

$weakWin =
    analyseTestFixture(
        2,
        2,
        0,
        $teamStrengths,
        $oppositionAdjusted
    );


$weakDraw =
    analyseTestFixture(
        2,
        1,
        1,
        $teamStrengths,
        $oppositionAdjusted
    );


$weakLoss =
    analyseTestFixture(
        2,
        0,
        2,
        $teamStrengths,
        $oppositionAdjusted
    );


/*
 * ------------------------------------------------------------
 * Average opposition
 * ------------------------------------------------------------
 */

$averageWin =
    analyseTestFixture(
        3,
        2,
        0,
        $teamStrengths,
        $oppositionAdjusted
    );


$averageDraw =
    analyseTestFixture(
        3,
        1,
        1,
        $teamStrengths,
        $oppositionAdjusted
    );


$averageLoss =
    analyseTestFixture(
        3,
        0,
        2,
        $teamStrengths,
        $oppositionAdjusted
    );


/*
 * ------------------------------------------------------------
 * Strong opposition
 * ------------------------------------------------------------
 */

$strongWin =
    analyseTestFixture(
        4,
        2,
        0,
        $teamStrengths,
        $oppositionAdjusted
    );


$strongDraw =
    analyseTestFixture(
        4,
        1,
        1,
        $teamStrengths,
        $oppositionAdjusted
    );


$strongLoss =
    analyseTestFixture(
        4,
        0,
        2,
        $teamStrengths,
        $oppositionAdjusted
    );


/*
 * ============================================================
 * DISPLAY RAW OPPOSITION RESULTS
 * ============================================================
 */

echo "<h3>Weak Opposition</h3>";

echo "Win Delta: "
    . $weakWin['average_delta']
    . "<br>";

echo "Draw Delta: "
    . $weakDraw['average_delta']
    . "<br>";

echo "Loss Delta: "
    . $weakLoss['average_delta']
    . "<br>";

echo "<br>";


echo "<h3>Average Opposition</h3>";

echo "Win Delta: "
    . $averageWin['average_delta']
    . "<br>";

echo "Draw Delta: "
    . $averageDraw['average_delta']
    . "<br>";

echo "Loss Delta: "
    . $averageLoss['average_delta']
    . "<br>";

echo "<br>";


echo "<h3>Strong Opposition</h3>";

echo "Win Delta: "
    . $strongWin['average_delta']
    . "<br>";

echo "Draw Delta: "
    . $strongDraw['average_delta']
    . "<br>";

echo "Loss Delta: "
    . $strongLoss['average_delta']
    . "<br>";

echo "<br>";


/*
 * ============================================================
 * TEST GROUP 1
 * ============================================================
 *
 * RESULT ORDER
 *
 * Regardless of opposition:
 *
 * Win > Draw > Loss
 */


/*
 * Weak opposition.
 */

testResult(
    'Weak opposition: win > draw',
    $weakWin['average_delta']
    >
    $weakDraw['average_delta']
);


testResult(
    'Weak opposition: draw > loss',
    $weakDraw['average_delta']
    >
    $weakLoss['average_delta']
);


/*
 * Average opposition.
 */

testResult(
    'Average opposition: win > draw',
    $averageWin['average_delta']
    >
    $averageDraw['average_delta']
);


testResult(
    'Average opposition: draw > loss',
    $averageDraw['average_delta']
    >
    $averageLoss['average_delta']
);


/*
 * Strong opposition.
 */

testResult(
    'Strong opposition: win > draw',
    $strongWin['average_delta']
    >
    $strongDraw['average_delta']
);


testResult(
    'Strong opposition: draw > loss',
    $strongDraw['average_delta']
    >
    $strongLoss['average_delta']
);


/*
 * ============================================================
 * TEST GROUP 2
 * ============================================================
 *
 * OPPOSITION SENSITIVITY
 *
 * The same result should produce different deltas
 * against different opposition.
 */


/*
 * Wins.
 */

testResult(
    'Winning against strong opposition produces a higher delta than winning against weak opposition',
    $strongWin['average_delta']
    >
    $weakWin['average_delta']
);


testResult(
    'Winning against average opposition sits between weak and strong',
    $averageWin['average_delta']
    >
    $weakWin['average_delta']
    &&
    $averageWin['average_delta']
    <
    $strongWin['average_delta']
);


/*
 * Draws.
 */

testResult(
    'Drawing against strong opposition produces a higher delta than drawing against weak opposition',
    $strongDraw['average_delta']
    >
    $weakDraw['average_delta']
);


testResult(
    'Drawing against average opposition sits between weak and strong',
    $averageDraw['average_delta']
    >
    $weakDraw['average_delta']
    &&
    $averageDraw['average_delta']
    <
    $strongDraw['average_delta']
);


/*
 * Losses.
 */

testResult(
    'Losing to strong opposition produces a higher delta than losing to weak opposition',
    $strongLoss['average_delta']
    >
    $weakLoss['average_delta']
);


testResult(
    'Losing to average opposition sits between weak and strong',
    $averageLoss['average_delta']
    >
    $weakLoss['average_delta']
    &&
    $averageLoss['average_delta']
    <
    $strongLoss['average_delta']
);


/*
 * ============================================================
 * TEST GROUP 3
 * ============================================================
 *
 * DELTA SIGN
 *
 * Against weak opposition:
 *
 * Win  = positive
 * Draw  = negative
 * Loss  = more negative
 *
 * Against strong opposition:
 *
 * Win  = positive
 * Draw  = positive
 * Loss  = negative
 */


/*
 * Weak opposition.
 */

testResult(
    'Weak opposition win produces positive delta',
    $weakWin['average_delta'] > 0
);


testResult(
    'Weak opposition draw produces negative delta',
    $weakDraw['average_delta'] < 0
);


testResult(
    'Weak opposition loss produces negative delta',
    $weakLoss['average_delta'] < 0
);


/*
 * Strong opposition.
 */

testResult(
    'Strong opposition win produces positive delta',
    $strongWin['average_delta'] > 0
);


testResult(
    'Strong opposition draw produces positive delta',
    $strongDraw['average_delta'] > 0
);


testResult(
    'Strong opposition loss produces negative delta',
    $strongLoss['average_delta'] < 0
);


/*
 * ============================================================
 * TEST GROUP 4
 * ============================================================
 *
 * OPPOSITION ADJUSTMENT MAGNITUDE
 *
 * Strong opposition should create a greater adjustment
 * than weak opposition.
 */


/*
 * Wins.
 */

$weakWinDelta =
    $weakWin['average_delta'];

$strongWinDelta =
    $strongWin['average_delta'];


testResult(
    'Strong-opposition win has greater positive adjustment',
    $strongWinDelta > $weakWinDelta
);


/*
 * Losses.
 */

$weakLossDelta =
    $weakLoss['average_delta'];

$strongLossDelta =
    $strongLoss['average_delta'];


testResult(
    'Weak-opposition loss has greater negative adjustment',
    $weakLossDelta < $strongLossDelta
);


/*
 * ============================================================
 * TEST GROUP 5
 * ============================================================
 *
 * EXPECTED PERFORMANCE
 *
 * Strong opposition should have a lower expected performance
 * than weak opposition.
 */

$weakExpected =
    $weakWin['matches'][0]['expected_performance'];

$averageExpected =
    $averageWin['matches'][0]['expected_performance'];

$strongExpected =
    $strongWin['matches'][0]['expected_performance'];


testResult(
    'Weak opposition has higher expected performance',
    $weakExpected > $averageExpected
);


testResult(
    'Average opposition has higher expected performance than strong opposition',
    $averageExpected > $strongExpected
);


/*
 * ============================================================
 * TEST GROUP 6
 * ============================================================
 *
 * INTERNAL DATA VALIDITY
 * ============================================================
 */

$allResults = [

    $weakWin,
    $weakDraw,
    $weakLoss,

    $averageWin,
    $averageDraw,
    $averageLoss,

    $strongWin,
    $strongDraw,
    $strongLoss
];


foreach ($allResults as $result) {

    testResult(
        'Adjusted result contains exactly one analysed match',
        $result['played'] === 1
    );

    testResult(
        'Adjusted result contains a performance delta',
        $result['average_delta'] !== null
    );
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";

echo "============================================<br>";

echo "Opposition Adjusted Performance Model Test<br>";

echo "============================================<br>";

echo "Passed: {$passed}<br>";

echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}