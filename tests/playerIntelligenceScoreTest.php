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

        echo "PASS: {$description}<br>\n";

        $passed++;

    } else {

        echo "FAIL: {$description}<br>\n";

        $failed++;
    }
}


/*
 * ============================================================
 * MODEL
 * ============================================================
 */

$intelligenceScore =
    new PlayerIntelligenceScore();


/*
 * ============================================================
 * TEST DATA
 * ============================================================
 */

$playerStrength = [

    'player_id' => 1,

    'fpl_player_id' => 1001,

    'team_id' => 1,

    'name' => 'Test Forward',

    'position' => 'FWD',

    'strength_rating' => 90.00
];


$playerValue = [

    'player_id' => 1,

    'name' => 'Test Forward',

    'position' => 'FWD',

    'price' => 6.0,

    'strength_rating' => 90.00,

    'strength_per_million' => 15.00,

    'value_rating' => 100.00,

    'value_label' => 'Exceptional'
];


$playerAvailability = [

    'player_id' => 1,

    'fpl_player_id' => 1001,

    'name' => 'Test Forward',

    'position' => 'FWD',

    'minutes' => 750,

    'chance_of_playing' => 100,

    'availability_rating' => 100.00,

    'reliability_rating' => 95.00,

    'availability_label' => 'Available'
];


$fixtureRating = 90.00;


/*
 * ============================================================
 * SCENARIO A
 * Weight Configuration
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Intelligence Score Weights<br>";
echo "============================================<br>";


$weights =
    $intelligenceScore->getWeights();


echo "Strength Weight: "
    . number_format(
        $weights['strength'],
        2
    )
    . "<br>";


echo "Value Weight: "
    . number_format(
        $weights['value'],
        2
    )
    . "<br>";


echo "Availability Weight: "
    . number_format(
        $weights['availability'],
        2
    )
    . "<br>";


echo "Fixture Weight: "
    . number_format(
        $weights['fixtures'],
        2
    )
    . "<br>";


$weightTotal =
    array_sum(
        $weights
    );


echo "Total Weight: "
    . number_format(
        $weightTotal,
        2
    )
    . "<br>";


testPass(
    'Strength weight is 35%',
    $weights['strength'] === 0.35
);


testPass(
    'Value weight is 25%',
    $weights['value'] === 0.25
);


testPass(
    'Availability weight is 20%',
    $weights['availability'] === 0.20
);


testPass(
    'Fixture weight is 20%',
    $weights['fixtures'] === 0.20
);


testPass(
    'All intelligence weights total 1.00',
    abs($weightTotal - 1.00) < 0.0001
);


/*
 * ============================================================
 * SCENARIO B
 * Standard Intelligence Score
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Standard Intelligence Score<br>";
echo "============================================<br>";


$standardScore =
    $intelligenceScore->calculateScore(
        90.00,
        100.00,
        100.00,
        90.00
    );


echo "Intelligence Score: "
    . number_format(
        $standardScore,
        2
    )
    . "<br>";


$expectedScore =
    (
        90.00 * 0.35
    )
    +
    (
        100.00 * 0.25
    )
    +
    (
        100.00 * 0.20
    )
    +
    (
        90.00 * 0.20
    );


$expectedScore =
    round(
        $expectedScore,
        2
    );


echo "Expected Score: "
    . number_format(
        $expectedScore,
        2
    )
    . "<br>";


testPass(
    'Standard intelligence score is calculated correctly',
    $standardScore === $expectedScore
);


testPass(
    'Standard intelligence score remains between 0 and 100',
    $standardScore >= 0
    &&
    $standardScore <= 100
);


/*
 * ============================================================
 * SCENARIO C
 * Perfect Player
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Perfect Player<br>";
echo "============================================<br>";


$perfectScore =
    $intelligenceScore->calculateScore(
        100.00,
        100.00,
        100.00,
        100.00
    );


echo "Perfect Player Score: "
    . number_format(
        $perfectScore,
        2
    )
    . "<br>";


testPass(
    'Perfect player produces 100 intelligence score',
    $perfectScore === 100.00
);


/*
 * ============================================================
 * SCENARIO D
 * Poor Player
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Poor Player<br>";
echo "============================================<br>";


$poorScore =
    $intelligenceScore->calculateScore(
        0.00,
        0.00,
        0.00,
        0.00
    );


echo "Poor Player Score: "
    . number_format(
        $poorScore,
        2
    )
    . "<br>";


testPass(
    'Poor player produces 0 intelligence score',
    $poorScore === 0.00
);


/*
 * ============================================================
 * SCENARIO E
 * Score Ordering
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Intelligence Score Ordering<br>";
echo "============================================<br>";


$eliteScore =
    $intelligenceScore->calculateScore(
        90.00,
        100.00,
        100.00,
        90.00
    );


$averageScore =
    $intelligenceScore->calculateScore(
        70.00,
        66.67,
        75.00,
        60.00
    );


$weakScore =
    $intelligenceScore->calculateScore(
        30.00,
        33.33,
        25.00,
        30.00
    );


echo "Elite: "
    . number_format(
        $eliteScore,
        2
    )
    . "<br>";


echo "Average: "
    . number_format(
        $averageScore,
        2
    )
    . "<br>";


echo "Weak: "
    . number_format(
        $weakScore,
        2
    )
    . "<br>";


testPass(
    'Elite player scores higher than average player',
    $eliteScore > $averageScore
);


testPass(
    'Average player scores higher than weak player',
    $averageScore > $weakScore
);


/*
 * ============================================================
 * SCENARIO F
 * Component Influence
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Component Influence<br>";
echo "============================================<br>";


$strongStrengthScore =
    $intelligenceScore->calculateScore(
        100.00,
        70.00,
        70.00,
        70.00
    );


$weakStrengthScore =
    $intelligenceScore->calculateScore(
        0.00,
        70.00,
        70.00,
        70.00
    );


echo "Strong Strength Component: "
    . number_format(
        $strongStrengthScore,
        2
    )
    . "<br>";


echo "Weak Strength Component: "
    . number_format(
        $weakStrengthScore,
        2
    )
    . "<br>";


testPass(
    'Higher strength rating improves intelligence score',
    $strongStrengthScore > $weakStrengthScore
);


$strongValueScore =
    $intelligenceScore->calculateScore(
        70.00,
        100.00,
        70.00,
        70.00
    );


$weakValueScore =
    $intelligenceScore->calculateScore(
        70.00,
        0.00,
        70.00,
        70.00
    );


testPass(
    'Higher value rating improves intelligence score',
    $strongValueScore > $weakValueScore
);


$strongAvailabilityScore =
    $intelligenceScore->calculateScore(
        70.00,
        70.00,
        100.00,
        70.00
    );


$weakAvailabilityScore =
    $intelligenceScore->calculateScore(
        70.00,
        70.00,
        0.00,
        70.00
    );


testPass(
    'Higher availability rating improves intelligence score',
    $strongAvailabilityScore > $weakAvailabilityScore
);


$easyFixtureScore =
    $intelligenceScore->calculateScore(
        70.00,
        70.00,
        70.00,
        100.00
    );


$hardFixtureScore =
    $intelligenceScore->calculateScore(
        70.00,
        70.00,
        70.00,
        0.00
    );


testPass(
    'Higher fixture rating improves intelligence score',
    $easyFixtureScore > $hardFixtureScore
);


/*
 * ============================================================
 * SCENARIO G
 * Missing Component Redistribution
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Missing Component Handling<br>";
echo "============================================<br>";


$missingFixtureScore =
    $intelligenceScore->calculateScore(
        90.00,
        100.00,
        100.00,
        null
    );


echo "Score Without Fixture Rating: "
    . number_format(
        $missingFixtureScore,
        2
    )
    . "<br>";


$expectedWithoutFixture =
    (
        (90.00 * 0.35)
        +
        (100.00 * 0.25)
        +
        (100.00 * 0.20)
    )
    /
    (
        0.35
        +
        0.25
        +
        0.20
    );


$expectedWithoutFixture =
    round(
        $expectedWithoutFixture,
        2
    );


testPass(
    'Missing fixture rating is redistributed correctly',
    $missingFixtureScore === $expectedWithoutFixture
);


testPass(
    'Missing fixture rating does not produce null',
    $missingFixtureScore !== null
);


/*
 * ============================================================
 * SCENARIO H
 * Completely Missing Data
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Completely Missing Data<br>";
echo "============================================<br>";


$missingScore =
    $intelligenceScore->calculateScore(
        null,
        null,
        null,
        null
    );


echo "Missing Data Score: ";


if ($missingScore === null) {

    echo "NULL<br>";

} else {

    echo number_format(
        $missingScore,
        2
    )
    . "<br>";
}


testPass(
    'Completely missing ratings return null',
    $missingScore === null
);


/*
 * ============================================================
 * SCENARIO I
 * Rating Bounds
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Rating Bounds<br>";
echo "============================================<br>";


$overMaximum =
    $intelligenceScore->calculateScore(
        150.00,
        150.00,
        150.00,
        150.00
    );


$belowMinimum =
    $intelligenceScore->calculateScore(
        -50.00,
        -50.00,
        -50.00,
        -50.00
    );


echo "Over 100 Input: "
    . number_format(
        $overMaximum,
        2
    )
    . "<br>";


echo "Below 0 Input: "
    . number_format(
        $belowMinimum,
        2
    )
    . "<br>";


testPass(
    'Ratings above 100 are capped',
    $overMaximum === 100.00
);


testPass(
    'Ratings below 0 are capped',
    $belowMinimum === 0.00
);


/*
 * ============================================================
 * SCENARIO J
 * Intelligence Labels
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario J: Intelligence Labels<br>";
echo "============================================<br>";


$eliteLabel =
    $intelligenceScore->getLabel(
        90.00
    );


$strongLabel =
    $intelligenceScore->getLabel(
        75.00
    );


$averageLabel =
    $intelligenceScore->getLabel(
        60.00
    );


$belowAverageLabel =
    $intelligenceScore->getLabel(
        45.00
    );


$weakLabel =
    $intelligenceScore->getLabel(
        20.00
    );


$unknownLabel =
    $intelligenceScore->getLabel(
        null
    );


echo "90 Rating: {$eliteLabel}<br>";
echo "75 Rating: {$strongLabel}<br>";
echo "60 Rating: {$averageLabel}<br>";
echo "45 Rating: {$belowAverageLabel}<br>";
echo "20 Rating: {$weakLabel}<br>";
echo "NULL Rating: {$unknownLabel}<br>";


testPass(
    '90 rating produces Elite label',
    $eliteLabel === 'Elite'
);


testPass(
    '75 rating produces Strong label',
    $strongLabel === 'Strong'
);


testPass(
    '60 rating produces Average label',
    $averageLabel === 'Average'
);


testPass(
    '45 rating produces Below Average label',
    $belowAverageLabel === 'Below Average'
);


testPass(
    '20 rating produces Weak label',
    $weakLabel === 'Weak'
);


testPass(
    'Missing rating produces Unknown label',
    $unknownLabel === 'Unknown'
);


/*
 * ============================================================
 * SCENARIO K
 * Complete Intelligence Model
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario K: Complete Intelligence Model<br>";
echo "============================================<br>";


$completeModel =
    $intelligenceScore->buildModel(
        $playerStrength,
        $playerValue,
        $playerAvailability,
        $fixtureRating
    );


testPass(
    'Complete intelligence model returns an array',
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
    'Strength rating is connected',
    $completeModel['strength_rating'] === 90.00
);


testPass(
    'Value rating is connected',
    $completeModel['value_rating'] === 100.00
);


testPass(
    'Availability rating is connected',
    $completeModel['availability_rating'] === 100.00
);


testPass(
    'Fixture rating is connected',
    $completeModel['fixture_rating'] === 90.00
);


testPass(
    'Intelligence score exists',
    $completeModel['intelligence_score'] !== null
);


testPass(
    'Intelligence score is within 0-100',
    $completeModel['intelligence_score'] >= 0
    &&
    $completeModel['intelligence_score'] <= 100
);


testPass(
    'Intelligence label exists',
    !empty(
        $completeModel['intelligence_label']
    )
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Front-End Friendly Player Intelligence<br>";
echo "============================================<br>";


echo "Player: "
    . $completeModel['name']
    . "<br>";


echo "Position: "
    . $completeModel['position']
    . "<br>";


echo "Strength: "
    . number_format(
        $completeModel['strength_rating'],
        2
    )
    . " / 100<br>";


echo "Value: "
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


echo "Intelligence Score: "
    . number_format(
        $completeModel['intelligence_score'],
        2
    )
    . " / 100<br>";


echo "Intelligence Label: "
    . $completeModel['intelligence_label']
    . "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Player Intelligence Score Test Summary<br>";
echo "============================================<br>";


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}