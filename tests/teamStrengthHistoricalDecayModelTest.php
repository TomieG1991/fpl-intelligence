<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * TEAM STRENGTH HISTORICAL DECAY MODEL TEST
 * ============================================================
 *
 * Tests the mathematical behaviour of historical decay.
 *
 * This test does not modify the real database.
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


function heading(
    string $text
): void {

    echo "<br>";
    echo "============================================<br>";
    echo "{$text}<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

$decay =
    new TeamStrengthHistoricalDecay();


/*
 * ============================================================
 * SCENARIO A
 * ============================================================
 *
 * Basic weight progression.
 */

heading('Scenario A: Decay Weight Progression');


$weight0 =
    $decay->calculateWeight(0);

$weight1 =
    $decay->calculateWeight(1);

$weight2 =
    $decay->calculateWeight(2);

$weight3 =
    $decay->calculateWeight(3);


echo "Age 0 Weight: {$weight0}<br>";
echo "Age 1 Weight: {$weight1}<br>";
echo "Age 2 Weight: {$weight2}<br>";
echo "Age 3 Weight: {$weight3}<br>";


testResult(
    'Most recent match has maximum weight',
    $weight0 === 1.0
);

testResult(
    'Older match has lower weight',
    $weight1 < $weight0
);

testResult(
    'Age 2 has lower weight than age 1',
    $weight2 < $weight1
);

testResult(
    'Age 3 has lower weight than age 2',
    $weight3 < $weight2
);


/*
 * ============================================================
 * SCENARIO B
 * ============================================================
 *
 * Verify the expected 0.90 decay progression.
 */

heading('Scenario B: Expected Decay Values');


testResult(
    'Age 0 weight equals 1.00',
    $weight0 === 1.0
);

testResult(
    'Age 1 weight equals 0.90',
    $weight1 === 0.9
);

testResult(
    'Age 2 weight equals 0.81',
    $weight2 === 0.81
);

testResult(
    'Age 3 weight equals 0.729',
    $weight3 === 0.729
);


/*
 * ============================================================
 * SCENARIO C
 * ============================================================
 *
 * Verify that a recent result has more influence than
 * an older result.
 */

heading('Scenario C: Recent Result Influence');


$allLosses = [

    ['performance' => 0],
    ['performance' => 0],
    ['performance' => 0],
    ['performance' => 0],
    ['performance' => 0]
];


$fourLossesRecentWin = [

    ['performance' => 0],
    ['performance' => 0],
    ['performance' => 0],
    ['performance' => 0],
    ['performance' => 100]
];


$performanceAllLosses =
    $decay->calculateWeightedPerformance(
        $allLosses
    );


$performanceRecentWin =
    $decay->calculateWeightedPerformance(
        $fourLossesRecentWin
    );


echo "All losses: "
    . number_format(
        $performanceAllLosses,
        2
    )
    . "<br>";

echo "Four losses / recent win: "
    . number_format(
        $performanceRecentWin,
        2
    )
    . "<br>";


testResult(
    'Recent win improves performance',
    $performanceRecentWin
    >
    $performanceAllLosses
);


testResult(
    'Recent win produces a positive performance',
    $performanceRecentWin
    >
    0
);

/*
 * ============================================================
 * SCENARIO D
 * ============================================================
 *
 * Identical performances should produce identical results
 * regardless of object reuse.
 */

heading('Scenario D: Consistency');


$historyA = [

    ['performance' => 100],
    ['performance' => 50],
    ['performance' => 0]
];


$historyB = [

    ['performance' => 100],
    ['performance' => 50],
    ['performance' => 0]
];


$resultA =
    $decay->calculateWeightedPerformance(
        $historyA
    );


$resultB =
    $decay->calculateWeightedPerformance(
        $historyB
    );


testResult(
    'Identical histories produce identical results',
    $resultA === $resultB
);


/*
 * ============================================================
 * SCENARIO E
 * ============================================================
 *
 * A single result should return that result exactly.
 */

heading('Scenario E: Single Match');


$singleWin = [

    [
        'performance' => 100
    ]
];


$singleLoss = [

    [
        'performance' => 0
    ]
];


$singleDraw = [

    [
        'performance' => 50
    ]
];


$winPerformance =
    $decay->calculateWeightedPerformance(
        $singleWin
    );


$lossPerformance =
    $decay->calculateWeightedPerformance(
        $singleLoss
    );


$drawPerformance =
    $decay->calculateWeightedPerformance(
        $singleDraw
    );


testResult(
    'Single win returns 100',
    $winPerformance === 100.0
);

testResult(
    'Single draw returns 50',
    $drawPerformance === 50.0
);

testResult(
    'Single loss returns 0',
    $lossPerformance === 0.0
);


/*
 * ============================================================
 * SCENARIO F
 * ============================================================
 *
 * Empty history should return null.
 */

heading('Scenario F: Empty History');


$emptyResult =
    $decay->calculateWeightedPerformance(
        []
    );


testResult(
    'Empty history returns null',
    $emptyResult === null
);


/*
 * ============================================================
 * SCENARIO G
 * ============================================================
 *
 * Custom decay factor.
 */

heading('Scenario G: Custom Decay Factor');


$noDecayWeight =
    $decay->calculateWeight(
        5,
        1.0
    );


$fastDecayWeight =
    $decay->calculateWeight(
        5,
        0.5
    );


testResult(
    'Decay factor of 1 produces equal weighting',
    $noDecayWeight === 1.0
);

testResult(
    'Faster decay produces a lower older-match weight',
    $fastDecayWeight < $noDecayWeight
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Team Strength Historical Decay Model Summary<br>";
echo "============================================<br>";

echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}