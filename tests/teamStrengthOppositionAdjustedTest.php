<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * TEAM STRENGTH + OPPOSITION ADJUSTED TEST
 * ============================================================
 *
 * Purpose:
 *
 * Test how normal performance and opposition-adjusted
 * performance should work together.
 *
 * This is a controlled integration test.
 *
 * It does NOT modify production classes.
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


$teamStrengths = $baselines;


$teamPerformance =
    new TeamPerformance();


$oppositionAdjusted =
    new OppositionAdjustedPerformance();


$teamStrengthModel =
    new TeamStrengthModel();


/*
 * ============================================================
 * HELPER
 * ============================================================
 */

function createFixture(
    int $opponentId,
    int $teamScore,
    int $opponentScore
): array {

    return [

        'gameweek' => 1,

        'home_team_id' => 1,

        'away_team_id' => $opponentId,

        'finished' => 1,

        'home_score' => $teamScore,

        'away_score' => $opponentScore
    ];
}


/*
 * ============================================================
 * HELPER
 * ============================================================
 *
 * Run both normal and opposition-adjusted analysis.
 */

function analyseScenario(
    array $fixtures,
    array $baselines,
    array $teamStrengths,
    TeamPerformance $teamPerformance,
    OppositionAdjustedPerformance $oppositionAdjusted,
    TeamStrengthModel $teamStrengthModel
): array {

    $normalPerformance =
        $teamPerformance->analyse(
            $fixtures,
            1
        );


    $normalRating =
        $teamStrengthModel->buildTeamModel(
            $baselines[1],
            $normalPerformance,
            $teamPerformance
        );


    $oppositionPerformance =
        $oppositionAdjusted->analyse(
            $fixtures,
            $teamStrengths,
            1
        );


    return [

        'normal_performance' =>
            $normalPerformance,

        'normal_rating' =>
            $normalRating,

        'opposition_performance' =>
            $oppositionPerformance
    ];
}


/*
 * ============================================================
 * SCENARIO A
 * ============================================================
 *
 * Arsenal 2-0 Weak Team
 */

$weakWin =
    analyseScenario(
        [
            createFixture(
                2,
                2,
                0
            )
        ],
        $baselines,
        $teamStrengths,
        $teamPerformance,
        $oppositionAdjusted,
        $teamStrengthModel
    );


/*
 * ============================================================
 * SCENARIO B
 * ============================================================
 *
 * Arsenal 2-0 Strong Team
 */

$strongWin =
    analyseScenario(
        [
            createFixture(
                4,
                2,
                0
            )
        ],
        $baselines,
        $teamStrengths,
        $teamPerformance,
        $oppositionAdjusted,
        $teamStrengthModel
    );


/*
 * ============================================================
 * DISPLAY
 * ============================================================
 */

echo "<h3>Scenario A: Arsenal 2-0 Weak Team</h3>";

echo "Normal Performance: "
    . $weakWin['normal_rating']['performance_rating']
    . "<br>";

echo "Normal Overall Rating: "
    . $weakWin['normal_rating']['overall']
    . "<br>";

echo "Opposition Expected Performance: "
    . $weakWin['opposition_performance']['matches'][0]['expected_performance']
    . "<br>";

echo "Opposition Delta: "
    . $weakWin['opposition_performance']['average_delta']
    . "<br><br>";


echo "<h3>Scenario B: Arsenal 2-0 Strong Team</h3>";

echo "Normal Performance: "
    . $strongWin['normal_rating']['performance_rating']
    . "<br>";

echo "Normal Overall Rating: "
    . $strongWin['normal_rating']['overall']
    . "<br>";

echo "Opposition Expected Performance: "
    . $strongWin['opposition_performance']['matches'][0]['expected_performance']
    . "<br>";

echo "Opposition Delta: "
    . $strongWin['opposition_performance']['average_delta']
    . "<br><br>";


/*
 * ============================================================
 * TEST GROUP 1
 * ============================================================
 *
 * Normal performance should remain identical.
 */

testResult(
    'Normal performance is identical for weak and strong opposition',
    $weakWin['normal_rating']['performance_rating']
    ===
    $strongWin['normal_rating']['performance_rating']
);


testResult(
    'Normal overall rating is identical for weak and strong opposition',
    $weakWin['normal_rating']['overall']
    ===
    $strongWin['normal_rating']['overall']
);


/*
 * ============================================================
 * TEST GROUP 2
 * ============================================================
 *
 * Opposition adjustment should distinguish the matches.
 */

testResult(
    'Opposition-adjusted results differ',
    $weakWin['opposition_performance']['average_delta']
    !==
    $strongWin['opposition_performance']['average_delta']
);


testResult(
    'Strong-team win produces a larger positive delta',
    $strongWin['opposition_performance']['average_delta']
    >
    $weakWin['opposition_performance']['average_delta']
);


/*
 * ============================================================
 * SCENARIO C
 * ============================================================
 *
 * Arsenal 0-2 Weak Team
 */

$weakLoss =
    analyseScenario(
        [
            createFixture(
                2,
                0,
                2
            )
        ],
        $baselines,
        $teamStrengths,
        $teamPerformance,
        $oppositionAdjusted,
        $teamStrengthModel
    );


/*
 * ============================================================
 * SCENARIO D
 * ============================================================
 *
 * Arsenal 0-2 Strong Team
 */

$strongLoss =
    analyseScenario(
        [
            createFixture(
                4,
                0,
                2
            )
        ],
        $baselines,
        $teamStrengths,
        $teamPerformance,
        $oppositionAdjusted,
        $teamStrengthModel
    );


/*
 * ============================================================
 * DISPLAY LOSS RESULTS
 * ============================================================
 */

echo "<h3>Scenario C: Arsenal 0-2 Weak Team</h3>";

echo "Normal Performance: "
    . $weakLoss['normal_rating']['performance_rating']
    . "<br>";

echo "Normal Overall Rating: "
    . $weakLoss['normal_rating']['overall']
    . "<br>";

echo "Opposition Delta: "
    . $weakLoss['opposition_performance']['average_delta']
    . "<br><br>";


echo "<h3>Scenario D: Arsenal 0-2 Strong Team</h3>";

echo "Normal Performance: "
    . $strongLoss['normal_rating']['performance_rating']
    . "<br>";

echo "Normal Overall Rating: "
    . $strongLoss['normal_rating']['overall']
    . "<br>";

echo "Opposition Delta: "
    . $strongLoss['opposition_performance']['average_delta']
    . "<br><br>";


/*
 * ============================================================
 * TEST GROUP 3
 * ============================================================
 *
 * Losses against stronger opposition should be
 * less damaging.
 */

testResult(
    'Losing to weak opposition produces a worse delta',
    $weakLoss['opposition_performance']['average_delta']
    <
    $strongLoss['opposition_performance']['average_delta']
);


testResult(
    'Weak-team loss produces a lower normal rating than win',
    $weakLoss['normal_rating']['overall']
    <
    $weakWin['normal_rating']['overall']
);


testResult(
    'Strong-team loss produces a lower normal rating than win',
    $strongLoss['normal_rating']['overall']
    <
    $strongWin['normal_rating']['overall']
);


/*
 * ============================================================
 * SCENARIO E
 * ============================================================
 *
 * Arsenal 1-1 Weak Team
 */

$weakDraw =
    analyseScenario(
        [
            createFixture(
                2,
                1,
                1
            )
        ],
        $baselines,
        $teamStrengths,
        $teamPerformance,
        $oppositionAdjusted,
        $teamStrengthModel
    );


/*
 * ============================================================
 * SCENARIO F
 * ============================================================
 *
 * Arsenal 1-1 Strong Team
 */

$strongDraw =
    analyseScenario(
        [
            createFixture(
                4,
                1,
                1
            )
        ],
        $baselines,
        $teamStrengths,
        $teamPerformance,
        $oppositionAdjusted,
        $teamStrengthModel
    );


/*
 * ============================================================
 * DISPLAY DRAW RESULTS
 * ============================================================
 */

echo "<h3>Scenario E: Arsenal 1-1 Weak Team</h3>";

echo "Normal Performance: "
    . $weakDraw['normal_rating']['performance_rating']
    . "<br>";

echo "Opposition Delta: "
    . $weakDraw['opposition_performance']['average_delta']
    . "<br><br>";


echo "<h3>Scenario F: Arsenal 1-1 Strong Team</h3>";

echo "Normal Performance: "
    . $strongDraw['normal_rating']['performance_rating']
    . "<br>";

echo "Opposition Delta: "
    . $strongDraw['opposition_performance']['average_delta']
    . "<br><br>";


/*
 * ============================================================
 * TEST GROUP 4
 * ============================================================
 */

testResult(
    'Drawing against strong opposition produces a higher delta',
    $strongDraw['opposition_performance']['average_delta']
    >
    $weakDraw['opposition_performance']['average_delta']
);


/*
 * ============================================================
 * TEST GROUP 5
 * ============================================================
 *
 * SCORELINE SENSITIVITY MUST STILL EXIST IN THE
 * NORMAL PERFORMANCE MODEL.
 */

$oneNil =
    analyseScenario(
        [
            createFixture(
                4,
                1,
                0
            )
        ],
        $baselines,
        $teamStrengths,
        $teamPerformance,
        $oppositionAdjusted,
        $teamStrengthModel
    );


$twoNil =
    analyseScenario(
        [
            createFixture(
                4,
                2,
                0
            )
        ],
        $baselines,
        $teamStrengths,
        $teamPerformance,
        $oppositionAdjusted,
        $teamStrengthModel
    );


$threeNil =
    analyseScenario(
        [
            createFixture(
                4,
                3,
                0
            )
        ],
        $baselines,
        $teamStrengths,
        $teamPerformance,
        $oppositionAdjusted,
        $teamStrengthModel
    );


testResult(
    'Normal performance preserves scoreline sensitivity: 2-0 > 1-0',
    $twoNil['normal_rating']['performance_rating']
    >
    $oneNil['normal_rating']['performance_rating']
);


testResult(
    'Normal performance preserves scoreline sensitivity: 3-0 > 2-0',
    $threeNil['normal_rating']['performance_rating']
    >
    $twoNil['normal_rating']['performance_rating']
);


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