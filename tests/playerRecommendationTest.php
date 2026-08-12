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

        echo "PASS: {$description}<br>";

        $passed++;

    } else {

        echo "FAIL: {$description}<br>";

        $failed++;
    }
}


function displaySection(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";
    echo "{$title}<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * MODEL
 * ============================================================
 */

$recommendationModel =
    new PlayerRecommendation();


/*
 * ============================================================
 * SCENARIO A
 * Recommendation thresholds
 * ============================================================
 */

displaySection(
    'Scenario A: Recommendation Thresholds'
);


$thresholds =
    $recommendationModel->getThresholds();


echo "BUY: "
    . $thresholds['BUY']
    . "<br>";

echo "HOLD: "
    . $thresholds['HOLD']
    . "<br>";

echo "WATCH: "
    . $thresholds['WATCH']
    . "<br>";

echo "AVOID: "
    . $thresholds['AVOID']
    . "<br>";


testPass(
    'BUY threshold is 85',
    $thresholds['BUY'] === 85
);

testPass(
    'HOLD threshold is 70',
    $thresholds['HOLD'] === 70
);

testPass(
    'WATCH threshold is 55',
    $thresholds['WATCH'] === 55
);

testPass(
    'AVOID threshold is 40',
    $thresholds['AVOID'] === 40
);


/*
 * ============================================================
 * SCENARIO B
 * BUY recommendation
 * ============================================================
 */

displaySection(
    'Scenario B: BUY Recommendation'
);


$buyRecommendation =
    $recommendationModel->getRecommendation(
        94.50,
        90.00,
        100.00,
        100.00,
        90.00
    );


echo "Recommendation: "
    . $buyRecommendation
    . "<br>";


testPass(
    'Elite player produces BUY recommendation',
    $buyRecommendation === 'BUY'
);


/*
 * ============================================================
 * SCENARIO C
 * HOLD recommendation
 * ============================================================
 */

displaySection(
    'Scenario C: HOLD Recommendation'
);


$holdRecommendation =
    $recommendationModel->getRecommendation(
        78.00,
        75.00,
        70.00,
        100.00,
        75.00
    );


echo "Recommendation: "
    . $holdRecommendation
    . "<br>";


testPass(
    'Strong player produces HOLD recommendation',
    $holdRecommendation === 'HOLD'
);


/*
 * ============================================================
 * SCENARIO D
 * WATCH recommendation
 * ============================================================
 */

displaySection(
    'Scenario D: WATCH Recommendation'
);


$watchRecommendation =
    $recommendationModel->getRecommendation(
        65.00,
        85.00,
        70.00,
        100.00,
        60.00
    );


echo "Recommendation: "
    . $watchRecommendation
    . "<br>";


testPass(
    'Mid-range player with strong underlying strength produces WATCH',
    $watchRecommendation === 'WATCH'
);


/*
 * ============================================================
 * SCENARIO E
 * AVOID recommendation
 * ============================================================
 */

displaySection(
    'Scenario E: AVOID Recommendation'
);


$avoidRecommendation =
    $recommendationModel->getRecommendation(
        50.00,
        50.00,
        40.00,
        100.00,
        60.00
    );


echo "Recommendation: "
    . $avoidRecommendation
    . "<br>";


testPass(
    'Low intelligence player produces AVOID',
    $avoidRecommendation === 'AVOID'
);


/*
 * ============================================================
 * SCENARIO F
 * SELL recommendation
 * ============================================================
 */

displaySection(
    'Scenario F: SELL Recommendation'
);


$sellRecommendation =
    $recommendationModel->getRecommendation(
        80.00,
        85.00,
        80.00,
        10.00,
        80.00
    );


echo "Recommendation: "
    . $sellRecommendation
    . "<br>";


testPass(
    'Very low availability produces SELL',
    $sellRecommendation === 'SELL'
);


/*
 * ============================================================
 * SCENARIO G
 * Strong player with poor fixtures
 * ============================================================
 */

displaySection(
    'Scenario G: Poor Fixture Override'
);


$poorFixtureRecommendation =
    $recommendationModel->getRecommendation(
        90.00,
        90.00,
        90.00,
        100.00,
        30.00
    );


echo "Recommendation: "
    . $poorFixtureRecommendation
    . "<br>";


testPass(
    'Strong player with poor fixtures produces WATCH',
    $poorFixtureRecommendation === 'WATCH'
);


/*
 * ============================================================
 * SCENARIO H
 * Strong player with questionable availability
 * ============================================================
 */

displaySection(
    'Scenario H: Availability Warning'
);


$availabilityWarning =
    $recommendationModel->getRecommendation(
        90.00,
        90.00,
        90.00,
        50.00,
        90.00
    );


echo "Recommendation: "
    . $availabilityWarning
    . "<br>";


testPass(
    'Strong player with moderate availability produces WATCH',
    $availabilityWarning === 'WATCH'
);


/*
 * ============================================================
 * SCENARIO I
 * Value upgrade
 * ============================================================
 */

displaySection(
    'Scenario I: Exceptional Value Upgrade'
);


$valueUpgrade =
    $recommendationModel->getRecommendation(
        72.00,
        85.00,
        95.00,
        100.00,
        80.00
    );


echo "Recommendation: "
    . $valueUpgrade
    . "<br>";


testPass(
    'Strong strength and exceptional value can produce BUY',
    $valueUpgrade === 'BUY'
);


/*
 * ============================================================
 * SCENARIO J
 * Boundary testing
 * ============================================================
 */

displaySection(
    'Scenario J: Recommendation Boundaries'
);


$score85 =
    $recommendationModel->getRecommendation(
        85.00,
        80.00,
        80.00,
        100.00,
        80.00
    );


$score70 =
    $recommendationModel->getRecommendation(
        70.00,
        70.00,
        70.00,
        100.00,
        70.00
    );


$score55 =
    $recommendationModel->getRecommendation(
        55.00,
        55.00,
        55.00,
        100.00,
        55.00
    );


$score40 =
    $recommendationModel->getRecommendation(
        40.00,
        40.00,
        40.00,
        100.00,
        40.00
    );


echo "Score 85: "
    . $score85
    . "<br>";

echo "Score 70: "
    . $score70
    . "<br>";

echo "Score 55: "
    . $score55
    . "<br>";

echo "Score 40: "
    . $score40
    . "<br>";


testPass(
    'Score of 85 produces BUY',
    $score85 === 'BUY'
);

testPass(
    'Score of 70 produces HOLD',
    $score70 === 'HOLD'
);

testPass(
    'Score of 55 produces WATCH or AVOID according to model rules',
    in_array(
        $score55,
        ['WATCH', 'AVOID'],
        true
    )
);

testPass(
    'Score of 40 produces AVOID',
    $score40 === 'AVOID'
);


/*
 * ============================================================
 * SCENARIO K
 * Missing intelligence data
 * ============================================================
 */

displaySection(
    'Scenario K: Missing Intelligence Data'
);


$missingRecommendation =
    $recommendationModel->getRecommendation(
        null,
        null,
        null,
        null,
        null
    );


echo "Recommendation: "
    . (
        $missingRecommendation
        ?? 'NULL'
    )
    . "<br>";


testPass(
    'Missing intelligence data returns null',
    $missingRecommendation === null
);


/*
 * ============================================================
 * SCENARIO L
 * Score bounds
 * ============================================================
 */

displaySection(
    'Scenario L: Intelligence Score Bounds'
);


$over100 =
    $recommendationModel->getRecommendation(
        150.00,
        100.00,
        100.00,
        100.00,
        100.00
    );


$belowZero =
    $recommendationModel->getRecommendation(
        -50.00,
        0.00,
        0.00,
        100.00,
        0.00
    );


echo "Over 100 Input: "
    . $over100
    . "<br>";

echo "Below 0 Input: "
    . $belowZero
    . "<br>";


testPass(
    'Over 100 intelligence score is safely handled',
    $over100 === 'BUY'
);

testPass(
    'Below 0 intelligence score is safely handled',
    $belowZero === 'SELL'
);


/*
 * ============================================================
 * SCENARIO M
 * Recommendation reasons
 * ============================================================
 */

displaySection(
    'Scenario M: Recommendation Reasons'
);


$buyReason =
    $recommendationModel->getRecommendationReason(
        94.50,
        90.00,
        100.00,
        100.00,
        90.00
    );


$holdReason =
    $recommendationModel->getRecommendationReason(
        78.00,
        75.00,
        70.00,
        100.00,
        75.00
    );


$sellReason =
    $recommendationModel->getRecommendationReason(
        80.00,
        85.00,
        80.00,
        10.00,
        80.00
    );


echo "BUY Reason: "
    . $buyReason
    . "<br>";

echo "HOLD Reason: "
    . $holdReason
    . "<br>";

echo "SELL Reason: "
    . $sellReason
    . "<br>";


testPass(
    'BUY recommendation produces a reason',
    $buyReason !== ''
);

testPass(
    'HOLD recommendation produces a reason',
    $holdReason !== ''
);

testPass(
    'SELL recommendation produces a reason',
    $sellReason !== ''
);


/*
 * ============================================================
 * SCENARIO N
 * Complete recommendation model
 * ============================================================
 */

displaySection(
    'Scenario N: Complete Recommendation Model'
);


$playerIntelligence = [

    'player_id' =>
        1,

    'name' =>
        'Test Forward',

    'position' =>
        'FWD',

    'intelligence_score' =>
        94.50,

    'strength_rating' =>
        90.00,

    'value_rating' =>
        100.00,

    'availability_rating' =>
        100.00,

    'fixture_rating' =>
        90.00
];


$completeModel =
    $recommendationModel
        ->buildRecommendationModel(
            $playerIntelligence
        );


testPass(
    'Complete recommendation model returns an array',
    is_array($completeModel)
);

testPass(
    'Player ID is preserved',
    $completeModel['player_id'] === 1
);

testPass(
    'Player name is preserved',
    $completeModel['name'] === 'Test Forward'
);

testPass(
    'Player position is preserved',
    $completeModel['position'] === 'FWD'
);

testPass(
    'Intelligence score is preserved',
    $completeModel['intelligence_score'] === 94.50
);

testPass(
    'Strength rating is preserved',
    $completeModel['strength_rating'] === 90.00
);

testPass(
    'Value rating is preserved',
    $completeModel['value_rating'] === 100.00
);

testPass(
    'Availability rating is preserved',
    $completeModel['availability_rating'] === 100.00
);

testPass(
    'Fixture rating is preserved',
    $completeModel['fixture_rating'] === 90.00
);

testPass(
    'Recommendation is generated',
    $completeModel['recommendation'] === 'BUY'
);

testPass(
    'Recommendation reason exists',
    $completeModel['reason'] !== ''
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

displaySection(
    'Front-End Friendly Player Recommendation'
);


echo "Player: "
    . $completeModel['name']
    . "<br>";

echo "Position: "
    . $completeModel['position']
    . "<br>";

echo "Intelligence Score: "
    . number_format(
        $completeModel['intelligence_score'],
        2
    )
    . " / 100<br>";

echo "Strength Rating: "
    . number_format(
        $completeModel['strength_rating'],
        2
    )
    . " / 100<br>";

echo "Value Rating: "
    . number_format(
        $completeModel['value_rating'],
        2
    )
    . " / 100<br>";

echo "Availability: "
    . number_format(
        $completeModel['availability_rating'],
        2
    )
    . " / 100<br>";

echo "Fixture Rating: "
    . number_format(
        $completeModel['fixture_rating'],
        2
    )
    . " / 100<br>";

echo "Recommendation: "
    . $completeModel['recommendation']
    . "<br>";

echo "Reason: "
    . $completeModel['reason']
    . "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

displaySection(
    'Player Recommendation Test Summary'
);


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}