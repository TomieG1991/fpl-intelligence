<?php

require_once '../classes/autoload.php';


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed = 0;
$failed = 0;


function testPass(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;

    if ($condition) {

        echo "PASS: {$description}\n";

        $passed++;

    } else {

        echo "FAIL: {$description}\n";

        $failed++;
    }
}


/*
 * ============================================================
 * MODELS
 * ============================================================
 */

$performanceModel =
    new PlayerPerformance();

$ratingModel =
    new PlayerPerformanceModel();


/*
 * ============================================================
 * TEST PLAYER
 * ============================================================
 */

$testPlayer = [

    'id' => 1001,
    'fpl_player_id' => 5001,
    'team_id' => 1,

    'position' => 'FWD',

    'web_name' => 'Test Striker',

    'price' => 10.0,

    'minutes' => 900,

    'goals' => 10,
    'assists' => 5,

    'clean_sheets' => 3,

    'bonus' => 10,
    'bps' => 240,

    'ict_index' => 150.0,

    'expected_goals' => 8.0,
    'expected_assists' => 4.0,
    'expected_goal_involvements' => 12.0,

    'chance_of_playing' => 100,

    'status' => 'a',
    'news' => ''
];


$performance =
    $performanceModel->analyse(
        $testPlayer
    );


$model =
    $ratingModel->buildModel(
        $performance,
        $performanceModel
    );


/*
 * ============================================================
 * SCENARIO A
 * Basic model structure
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario A: Model Structure\n";
echo "============================================\n";


testPass(
    'Player ID is preserved',
    $model['player_id'] === 1001
);

testPass(
    'Player name is preserved',
    $model['name'] === 'Test Striker'
);

testPass(
    'Player position is preserved',
    $model['position'] === 'FWD'
);

testPass(
    'Minutes are preserved',
    $model['minutes'] === 900
);


/*
 * ============================================================
 * SCENARIO B
 * Per-90 calculations
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario B: Per-90 Metrics\n";
echo "============================================\n";


echo "Goals/90: "
    . number_format(
        $model['goals_per_90'],
        2
    )
    . "\n";


echo "Assists/90: "
    . number_format(
        $model['assists_per_90'],
        2
    )
    . "\n";


echo "xG/90: "
    . number_format(
        $model['expected_goals_per_90'],
        2
    )
    . "\n";


echo "xA/90: "
    . number_format(
        $model['expected_assists_per_90'],
        2
    )
    . "\n";


echo "Clean Sheets/90: "
    . number_format(
        $model['clean_sheets_per_90'],
        2
    )
    . "\n";


testPass(
    'Goals per 90 calculated correctly',
    $model['goals_per_90'] === 1.00
);

testPass(
    'Assists per 90 calculated correctly',
    $model['assists_per_90'] === 0.50
);

testPass(
    'Expected goals per 90 calculated correctly',
    $model['expected_goals_per_90'] === 0.80
);

testPass(
    'Expected assists per 90 calculated correctly',
    $model['expected_assists_per_90'] === 0.40
);


/*
 * ============================================================
 * SCENARIO C
 * Normalisation
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario C: Metric Normalisation\n";
echo "============================================\n";


echo "Goals Rating: "
    . number_format(
        $model['goals_rating'],
        2
    )
    . "\n";


echo "Assists Rating: "
    . number_format(
        $model['assists_rating'],
        2
    )
    . "\n";


echo "xG Rating: "
    . number_format(
        $model['expected_goals_rating'],
        2
    )
    . "\n";


echo "xA Rating: "
    . number_format(
        $model['expected_assists_rating'],
        2
    )
    . "\n";


echo "Clean Sheets Rating: "
    . number_format(
        $model['clean_sheets_rating'],
        2
    )
    . "\n";


echo "BPS Rating: "
    . number_format(
        $model['bps_rating'],
        2
    )
    . "\n";


testPass(
    'Goals rating is within 0-100',
    $model['goals_rating'] >= 0
    &&
    $model['goals_rating'] <= 100
);

testPass(
    'Assists rating is within 0-100',
    $model['assists_rating'] >= 0
    &&
    $model['assists_rating'] <= 100
);

testPass(
    'xG rating is within 0-100',
    $model['expected_goals_rating'] >= 0
    &&
    $model['expected_goals_rating'] <= 100
);

testPass(
    'xA rating is within 0-100',
    $model['expected_assists_rating'] >= 0
    &&
    $model['expected_assists_rating'] <= 100
);

testPass(
    'Clean sheet rating is within 0-100',
    $model['clean_sheets_rating'] >= 0
    &&
    $model['clean_sheets_rating'] <= 100
);

testPass(
    'BPS rating is within 0-100',
    $model['bps_rating'] >= 0
    &&
    $model['bps_rating'] <= 100
);


/*
 * ============================================================
 * SCENARIO D
 * Benchmark capping
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario D: Benchmark Capping\n";
echo "============================================\n";


$maximumGoals =
    $ratingModel->calculateGoalsRating(
        2.00,
        'FWD'
    );


$maximumAssists =
    $ratingModel->calculateAssistsRating(
        1.00,
        'FWD'
    );


$maximumBps =
    $ratingModel->calculateBpsRating(
        500
    );


echo "Maximum Goals Rating: "
    . number_format(
        $maximumGoals,
        2
    )
    . "\n";


echo "Maximum Assists Rating: "
    . number_format(
        $maximumAssists,
        2
    )
    . "\n";


echo "Maximum BPS Rating: "
    . number_format(
        $maximumBps,
        2
    )
    . "\n";


testPass(
    'Goals rating is capped at 100',
    $maximumGoals === 100.00
);

testPass(
    'Assists rating is capped at 100',
    $maximumAssists === 100.00
);

testPass(
    'BPS rating is capped at 100',
    $maximumBps === 100.00
);


/*
 * ============================================================
 * SCENARIO E
 * Position-specific benchmarks
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario E: Position-Specific Ratings\n";
echo "============================================\n";


$forwardGoalRating =
    $ratingModel->calculateGoalsRating(
        0.50,
        'FWD'
    );


$midfieldGoalRating =
    $ratingModel->calculateGoalsRating(
        0.50,
        'MID'
    );


$defenderGoalRating =
    $ratingModel->calculateGoalsRating(
        0.50,
        'DEF'
    );


echo "FWD 0.50 goals/90: "
    . number_format(
        $forwardGoalRating,
        2
    )
    . "\n";


echo "MID 0.50 goals/90: "
    . number_format(
        $midfieldGoalRating,
        2
    )
    . "\n";


echo "DEF 0.50 goals/90: "
    . number_format(
        $defenderGoalRating,
        2
    )
    . "\n";


testPass(
    'Position benchmarks affect ratings',
    $forwardGoalRating
    !==
    $midfieldGoalRating
);

testPass(
    'Defender rating recognises 0.50 goals/90 as exceptional',
    $defenderGoalRating === 100.00
);


/*
 * ============================================================
 * SCENARIO F
 * Missing data
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario F: Missing Data Handling\n";
echo "============================================\n";


$missingRating =
    $ratingModel->normalise(
        null,
        1.0
    );


testPass(
    'Missing metric returns null',
    $missingRating === null
);


$zeroRating =
    $ratingModel->normalise(
        0.0,
        1.0
    );


testPass(
    'Zero metric produces zero rating',
    $zeroRating === 0.00
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

echo "\n============================================\n";
echo "Front-End Friendly Player Performance\n";
echo "============================================\n";


echo "Player: "
    . $model['name']
    . "\n";


echo "Position: "
    . $model['position']
    . "\n";


echo "Minutes: "
    . $model['minutes']
    . "\n";


echo "Goals/90: "
    . number_format(
        $model['goals_per_90'],
        2
    )
    . "\n";


echo "Assists/90: "
    . number_format(
        $model['assists_per_90'],
        2
    )
    . "\n";


echo "xG/90: "
    . number_format(
        $model['expected_goals_per_90'],
        2
    )
    . "\n";


echo "xA/90: "
    . number_format(
        $model['expected_assists_per_90'],
        2
    )
    . "\n";


echo "Clean Sheets/90: "
    . number_format(
        $model['clean_sheets_per_90'],
        2
    )
    . "\n";


echo "Goals Rating: "
    . number_format(
        $model['goals_rating'],
        2
    )
    . "\n";


echo "Assists Rating: "
    . number_format(
        $model['assists_rating'],
        2
    )
    . "\n";


echo "xG Rating: "
    . number_format(
        $model['expected_goals_rating'],
        2
    )
    . "\n";


echo "xA Rating: "
    . number_format(
        $model['expected_assists_rating'],
        2
    )
    . "\n";


echo "Clean Sheet Rating: "
    . number_format(
        $model['clean_sheets_rating'],
        2
    )
    . "\n";


echo "BPS Rating: "
    . number_format(
        $model['bps_rating'],
        2
    )
    . "\n";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "\n============================================\n";
echo "Player Performance Model Test Summary\n";
echo "============================================\n";


echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";


if ($failed === 0) {

    echo "\nRESULT: ALL TESTS PASSED ✅\n";

} else {

    echo "\nRESULT: TESTS FAILED ❌\n";
}