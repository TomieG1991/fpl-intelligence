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
 * MODEL
 * ============================================================
 */

$strengthModel =
    new PlayerStrengthModel();


/*
 * ============================================================
 * TEST DATA
 * ============================================================
 */

$forward = [

    'player_id' => 1,
    'fpl_player_id' => 1001,
    'team_id' => 1,

    'name' => 'Test Forward',
    'position' => 'FWD',

    'minutes' => 900,

    'goals_per_90' => 1.00,
    'assists_per_90' => 0.50,
    'expected_goals_per_90' => 0.80,
    'expected_assists_per_90' => 0.40,
    'clean_sheets_per_90' => 0.30,

    'goals_rating' => 100.00,
    'assists_rating' => 100.00,
    'expected_goals_rating' => 80.00,
    'expected_assists_rating' => 80.00,
    'clean_sheets_rating' => 100.00,
    'bps_rating' => 80.00
];


$defender = [

    'player_id' => 2,
    'fpl_player_id' => 1002,
    'team_id' => 1,

    'name' => 'Test Defender',
    'position' => 'DEF',

    'minutes' => 900,

    'goals_per_90' => 0.50,
    'assists_per_90' => 0.30,
    'expected_goals_per_90' => 0.40,
    'expected_assists_per_90' => 0.30,
    'clean_sheets_per_90' => 0.60,

    'goals_rating' => 100.00,
    'assists_rating' => 100.00,
    'expected_goals_rating' => 100.00,
    'expected_assists_rating' => 100.00,
    'clean_sheets_rating' => 100.00,
    'bps_rating' => 100.00
];


$midfielder = [

    'player_id' => 3,
    'fpl_player_id' => 1003,
    'team_id' => 1,

    'name' => 'Test Midfielder',
    'position' => 'MID',

    'minutes' => 900,

    'goals_per_90' => 0.50,
    'assists_per_90' => 0.40,
    'expected_goals_per_90' => 0.50,
    'expected_assists_per_90' => 0.40,
    'clean_sheets_per_90' => 0.30,

    'goals_rating' => 71.43,
    'assists_rating' => 66.67,
    'expected_goals_rating' => 71.43,
    'expected_assists_rating' => 66.67,
    'clean_sheets_rating' => 75.00,
    'bps_rating' => 70.00
];


/*
 * ============================================================
 * SCENARIO A
 * Forward weighting
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario A: Forward Strength Rating\n";
echo "============================================\n";


$forwardRating =
    $strengthModel->calculateRating(
        $forward
    );


echo "Forward Strength Rating: "
    . number_format(
        $forwardRating,
        2
    )
    . "\n";


testPass(
    'Forward produces a strength rating',
    $forwardRating !== null
);

testPass(
    'Forward rating remains between 0 and 100',
    $forwardRating >= 0
    &&
    $forwardRating <= 100
);


/*
 * ============================================================
 * SCENARIO B
 * Defender weighting
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario B: Defender Strength Rating\n";
echo "============================================\n";


$defenderRating =
    $strengthModel->calculateRating(
        $defender
    );


echo "Defender Strength Rating: "
    . number_format(
        $defenderRating,
        2
    )
    . "\n";


testPass(
    'Defender produces a strength rating',
    $defenderRating !== null
);

testPass(
    'Defender rating remains between 0 and 100',
    $defenderRating >= 0
    &&
    $defenderRating <= 100
);


/*
 * ============================================================
 * SCENARIO C
 * Midfielder weighting
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario C: Midfielder Strength Rating\n";
echo "============================================\n";


$midfielderRating =
    $strengthModel->calculateRating(
        $midfielder
    );


echo "Midfielder Strength Rating: "
    . number_format(
        $midfielderRating,
        2
    )
    . "\n";


testPass(
    'Midfielder produces a strength rating',
    $midfielderRating !== null
);

testPass(
    'Midfielder rating remains between 0 and 100',
    $midfielderRating >= 0
    &&
    $midfielderRating <= 100
);


/*
 * ============================================================
 * SCENARIO D
 * Position weighting comparison
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario D: Position Weighting Comparison\n";
echo "============================================\n";


echo "Forward: "
    . number_format(
        $forwardRating,
        2
    )
    . "\n";


echo "Midfielder: "
    . number_format(
        $midfielderRating,
        2
    )
    . "\n";


testPass(
    'Different positions can produce different ratings',
    $forwardRating !== $midfielderRating
);


/*
 * ============================================================
 * SCENARIO E
 * Perfect player
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario E: Perfect Player\n";
echo "============================================\n";


$perfectPlayer = $forward;

$perfectPlayer['goals_rating'] = 100;
$perfectPlayer['assists_rating'] = 100;
$perfectPlayer['expected_goals_rating'] = 100;
$perfectPlayer['expected_assists_rating'] = 100;
$perfectPlayer['clean_sheets_rating'] = 100;
$perfectPlayer['bps_rating'] = 100;


$perfectRating =
    $strengthModel->calculateRating(
        $perfectPlayer
    );


echo "Perfect Player Rating: "
    . number_format(
        $perfectRating,
        2
    )
    . "\n";


testPass(
    'Perfect player produces 100 strength rating',
    $perfectRating === 100.00
);


/*
 * ============================================================
 * SCENARIO F
 * Poor player
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario F: Poor Player\n";
echo "============================================\n";


$poorPlayer = $forward;

$poorPlayer['goals_rating'] = 0;
$poorPlayer['assists_rating'] = 0;
$poorPlayer['expected_goals_rating'] = 0;
$poorPlayer['expected_assists_rating'] = 0;
$poorPlayer['clean_sheets_rating'] = 0;
$poorPlayer['bps_rating'] = 0;


$poorRating =
    $strengthModel->calculateRating(
        $poorPlayer
    );


echo "Poor Player Rating: "
    . number_format(
        $poorRating,
        2
    )
    . "\n";


testPass(
    'Poor player produces 0 strength rating',
    $poorRating === 0.00
);


/*
 * ============================================================
 * SCENARIO G
 * Missing metric handling
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario G: Missing Metric Handling\n";
echo "============================================\n";


$missingData = $forward;

$missingData['goals_rating'] = null;
$missingData['expected_goals_rating'] = null;


$missingRating =
    $strengthModel->calculateRating(
        $missingData
    );


echo "Rating With Missing Metrics: "
    . number_format(
        $missingRating,
        2
    )
    . "\n";


testPass(
    'Missing metrics do not produce null rating',
    $missingRating !== null
);

testPass(
    'Missing metric rating remains within bounds',
    $missingRating >= 0
    &&
    $missingRating <= 100
);


/*
 * ============================================================
 * SCENARIO H
 * Completely missing data
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario H: Completely Missing Data\n";
echo "============================================\n";


$emptyPlayer = [

    'position' => 'FWD',

    'goals_rating' => null,
    'assists_rating' => null,
    'expected_goals_rating' => null,
    'expected_assists_rating' => null,
    'clean_sheets_rating' => null,
    'bps_rating' => null
];


$emptyRating =
    $strengthModel->calculateRating(
        $emptyPlayer
    );


testPass(
    'Completely missing metrics return null',
    $emptyRating === null
);


/*
 * ============================================================
 * SCENARIO I
 * Weight totals
 * ============================================================
 */

echo "\n============================================\n";
echo "Scenario I: Position Weight Totals\n";
echo "============================================\n";


foreach (
    ['GK', 'DEF', 'MID', 'FWD']
    as $position
) {

    $weights =
        $strengthModel->getWeights(
            $position
        );

    $total =
        array_sum(
            $weights
        );

    echo "{$position} Weight Total: "
        . number_format(
            $total,
            2
        )
        . "\n";


    testPass(
        "{$position} weights total 1.00",
        abs($total - 1.00) < 0.0001
    );
}


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

echo "\n============================================\n";
echo "Front-End Friendly Player Strength\n";
echo "============================================\n";


echo "Player: "
    . $forward['name']
    . "\n";


echo "Position: "
    . $forward['position']
    . "\n";


echo "Strength Rating: "
    . number_format(
        $forwardRating,
        2
    )
    . " / 100\n";


echo "Rating Label: ";


if ($forwardRating >= 85) {

    echo "Elite\n";

} elseif ($forwardRating >= 70) {

    echo "Strong\n";

} elseif ($forwardRating >= 55) {

    echo "Average\n";

} elseif ($forwardRating >= 40) {

    echo "Below Average\n";

} else {

    echo "Weak\n";
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "\n============================================\n";
echo "Player Strength Model Test Summary\n";
echo "============================================\n";


echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";


if ($failed === 0) {

    echo "\nRESULT: ALL TESTS PASSED ✅\n";

} else {

    echo "\nRESULT: TESTS FAILED ❌\n";
}