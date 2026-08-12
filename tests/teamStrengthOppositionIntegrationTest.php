<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * TEAM STRENGTH + OPPOSITION INTEGRATION TEST
 * ============================================================
 *
 * Purpose:
 *
 * Test how opposition-adjusted performance should interact
 * with the existing TeamStrengthModel.
 *
 * IMPORTANT:
 *
 * This test does NOT modify the production model.
 *
 * It is designed to expose the behaviour we want before
 * changing TeamStrengthModel.
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


/*
 * ============================================================
 * CREATE MODELS
 * ============================================================
 */

$teamPerformance =
    new TeamPerformance();

$teamStrengthModel =
    new TeamStrengthModel();

$oppositionAdjusted =
    new OppositionAdjustedPerformance();


/*
 * ============================================================
 * HELPER
 * ============================================================
 *
 * Analyse one controlled Arsenal fixture.
 */

function analyseFixture(
    array $fixture,
    array $teamStrengths,
    TeamPerformance $teamPerformance,
    TeamStrengthModel $teamStrengthModel,
    OppositionAdjustedPerformance $oppositionAdjusted
): array {

    /*
     * --------------------------------------------------------
     * Normal TeamPerformance
     * --------------------------------------------------------
     */

    $performance =
        $teamPerformance->analyse(
            [$fixture],
            1
        );


    /*
     * --------------------------------------------------------
     * Existing TeamStrengthModel
     * --------------------------------------------------------
     */

    $normalModel =
        $teamStrengthModel->buildTeamModel(
            $teamStrengths[1],
            $performance,
            $teamPerformance
        );


    /*
     * --------------------------------------------------------
     * Opposition-adjusted performance
     * --------------------------------------------------------
     */

    $adjusted =
        $oppositionAdjusted->analyse(
            [$fixture],
            $teamStrengths,
            1
        );


    return [

        'performance' =>
            $performance,

        'normal_model' =>
            $normalModel,

        'adjusted' =>
            $adjusted
    ];
}


/*
 * ============================================================
 * SCENARIO A
 * ============================================================
 *
 * Arsenal beats a weak team 2-0.
 */

$weakWinFixture = [

    'gameweek' => 1,

    'home_team_id' => 1,

    'away_team_id' => 2,

    'finished' => 1,

    'home_score' => 2,

    'away_score' => 0
];


$weakWin =
    analyseFixture(
        $weakWinFixture,
        $teamStrengths,
        $teamPerformance,
        $teamStrengthModel,
        $oppositionAdjusted
    );


echo "<h3>Scenario A: Arsenal 2-0 Weak Team</h3>";

echo "Normal Performance: "
    . $weakWin['normal_model']['performance_rating']
    . "<br>";

echo "Normal Overall Rating: "
    . $weakWin['normal_model']['overall']
    . "<br>";

echo "Opposition Expected Performance: "
    . $weakWin['adjusted']['matches'][0]['expected_performance']
    . "<br>";

echo "Opposition Delta: "
    . $weakWin['adjusted']['average_delta']
    . "<br>";

echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * ============================================================
 *
 * Arsenal beats a strong team 2-0.
 */

$strongWinFixture = [

    'gameweek' => 1,

    'home_team_id' => 1,

    'away_team_id' => 4,

    'finished' => 1,

    'home_score' => 2,

    'away_score' => 0
];


$strongWin =
    analyseFixture(
        $strongWinFixture,
        $teamStrengths,
        $teamPerformance,
        $teamStrengthModel,
        $oppositionAdjusted
    );


echo "<h3>Scenario B: Arsenal 2-0 Strong Team</h3>";

echo "Normal Performance: "
    . $strongWin['normal_model']['performance_rating']
    . "<br>";

echo "Normal Overall Rating: "
    . $strongWin['normal_model']['overall']
    . "<br>";

echo "Opposition Expected Performance: "
    . $strongWin['adjusted']['matches'][0]['expected_performance']
    . "<br>";

echo "Opposition Delta: "
    . $strongWin['adjusted']['average_delta']
    . "<br>";

echo "<br>";


/*
 * ============================================================
 * TEST 1
 * ============================================================
 *
 * Normal TeamStrengthModel currently treats both wins
 * identically.
 */

testResult(
    'Normal performance is identical for both 2-0 wins',
    $weakWin['normal_model']['performance_rating']
    ===
    $strongWin['normal_model']['performance_rating']
);


testResult(
    'Normal overall rating is identical for both 2-0 wins',
    $weakWin['normal_model']['overall']
    ===
    $strongWin['normal_model']['overall']
);


/*
 * ============================================================
 * TEST 2
 * ============================================================
 *
 * Opposition adjustment must distinguish the matches.
 */

testResult(
    'Opposition-adjusted results differ',
    $weakWin['adjusted']['average_delta']
    !==
    $strongWin['adjusted']['average_delta']
);


testResult(
    'Strong-team win produces a larger positive delta',
    $strongWin['adjusted']['average_delta']
    >
    $weakWin['adjusted']['average_delta']
);


/*
 * ============================================================
 * SCENARIO C
 * ============================================================
 *
 * Arsenal loses 0-2 to a weak team.
 */

$weakLossFixture = [

    'gameweek' => 1,

    'home_team_id' => 1,

    'away_team_id' => 2,

    'finished' => 1,

    'home_score' => 0,

    'away_score' => 2
];


$weakLoss =
    analyseFixture(
        $weakLossFixture,
        $teamStrengths,
        $teamPerformance,
        $teamStrengthModel,
        $oppositionAdjusted
    );


/*
 * ============================================================
 * SCENARIO D
 * ============================================================
 *
 * Arsenal loses 0-2 to a strong team.
 */

$strongLossFixture = [

    'gameweek' => 1,

    'home_team_id' => 1,

    'away_team_id' => 4,

    'finished' => 1,

    'home_score' => 0,

    'away_score' => 2
];


$strongLoss =
    analyseFixture(
        $strongLossFixture,
        $teamStrengths,
        $teamPerformance,
        $teamStrengthModel,
        $oppositionAdjusted
    );


echo "<h3>Scenario C: Arsenal 0-2 Weak Team</h3>";

echo "Opposition Delta: "
    . $weakLoss['adjusted']['average_delta']
    . "<br>";

echo "<br>";


echo "<h3>Scenario D: Arsenal 0-2 Strong Team</h3>";

echo "Opposition Delta: "
    . $strongLoss['adjusted']['average_delta']
    . "<br>";

echo "<br>";


/*
 * ============================================================
 * TEST 3
 * ============================================================
 */

testResult(
    'Losing to weak opposition produces a worse delta',
    $weakLoss['adjusted']['average_delta']
    <
    $strongLoss['adjusted']['average_delta']
);


/*
 * ============================================================
 * TEST 4
 * ============================================================
 *
 * The normal TeamStrengthModel should still behave
 * consistently.
 */

testResult(
    'Weak-team loss produces a lower normal rating than win',
    $weakLoss['normal_model']['overall']
    <
    $weakWin['normal_model']['overall']
);


testResult(
    'Strong-team loss produces a lower normal rating than win',
    $strongLoss['normal_model']['overall']
    <
    $strongWin['normal_model']['overall']
);


/*
 * ============================================================
 * CURRENT MODEL LIMITATION
 * ============================================================
 *
 * This test deliberately checks whether the existing
 * TeamStrengthModel can distinguish:
 *
 * 2-0 vs Weak
 * 2-0 vs Strong
 *
 * It should currently FAIL because the existing model
 * does not consume opposition-adjusted performance.
 */


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";

echo "============================================<br>";

echo "Team Strength + Opposition Integration Test<br>";

echo "============================================<br>";

echo "Passed: {$passed}<br>";

echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}