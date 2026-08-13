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


/*
 * ============================================================
 * MODEL
 * ============================================================
 */

$transferModel =
    new TransferRecommendation();


/*
 * ============================================================
 * TEST DATA
 * ============================================================
 */

$currentPlayer = [

    'player_id' => 1,
    'name' => 'Current Player',

    'intelligence_score' => 70.00,
    'strength_rating' => 70.00,
    'value_rating' => 60.00,
    'availability_rating' => 90.00,
    'fixture_rating' => 60.00
];


$strongReplacement = [

    'player_id' => 2,
    'name' => 'Strong Replacement',

    'intelligence_score' => 90.00,
    'strength_rating' => 90.00,
    'value_rating' => 90.00,
    'availability_rating' => 95.00,
    'fixture_rating' => 85.00
];


$modestReplacement = [

    'player_id' => 3,
    'name' => 'Modest Replacement',

    'intelligence_score' => 73.00,
    'strength_rating' => 72.00,
    'value_rating' => 65.00,
    'availability_rating' => 92.00,
    'fixture_rating' => 62.00
];


$weakReplacement = [

    'player_id' => 4,
    'name' => 'Weak Replacement',

    'intelligence_score' => 50.00,
    'strength_rating' => 50.00,
    'value_rating' => 40.00,
    'availability_rating' => 70.00,
    'fixture_rating' => 40.00
];


/*
 * ============================================================
 * SCENARIO A
 * Transfer recommendation weights
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Transfer Recommendation Weights<br>";
echo "============================================<br>";


$weights =
    $transferModel->getWeights();


echo "Intelligence Weight: "
    . number_format(
        $weights['intelligence'],
        2
    )
    . "<br>";


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


$totalWeight =
    array_sum(
        $weights
    );


echo "Total Weight: "
    . number_format(
        $totalWeight,
        2
    )
    . "<br>";


testPass(
    'Intelligence weight is 35%',
    $weights['intelligence'] === 0.35
);


testPass(
    'Strength weight is 20%',
    $weights['strength'] === 0.20
);


testPass(
    'Value weight is 15%',
    $weights['value'] === 0.15
);


testPass(
    'Availability weight is 15%',
    $weights['availability'] === 0.15
);


testPass(
    'Fixture weight is 15%',
    $weights['fixtures'] === 0.15
);


testPass(
    'All transfer weights total 1.00',
    abs($totalWeight - 1.00) < 0.0001
);


/*
 * ============================================================
 * SCENARIO B
 * Component difference
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Component Difference<br>";
echo "============================================<br>";


$positiveDifference =
    $transferModel->calculateDifference(
        70.00,
        90.00
    );


echo "Current Rating: 70.00<br>";
echo "Replacement Rating: 90.00<br>";
echo "Difference: "
    . number_format(
        $positiveDifference,
        2
    )
    . "<br>";


testPass(
    'Positive component difference is calculated correctly',
    $positiveDifference === 20.00
);


$negativeDifference =
    $transferModel->calculateDifference(
        90.00,
        70.00
    );


echo "Negative Difference: "
    . number_format(
        $negativeDifference,
        2
    )
    . "<br>";


testPass(
    'Negative component difference is calculated correctly',
    $negativeDifference === -20.00
);


$missingDifference =
    $transferModel->calculateDifference(
        null,
        70.00
    );


echo "Missing Difference: "
    . (
        $missingDifference === null
            ? 'NULL'
            : number_format(
                $missingDifference,
                2
            )
    )
    . "<br>";


testPass(
    'Missing component difference returns null',
    $missingDifference === null
);


/*
 * ============================================================
 * SCENARIO C
 * Strong transfer
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Strong Transfer<br>";
echo "============================================<br>";


$strongScore =
    $transferModel->calculateTransferScore(
        70,
        90,
        70,
        90,
        60,
        90,
        90,
        95,
        60,
        85
    );


$strongRecommendation =
    $transferModel->getRecommendation(
        $strongScore
    );


echo "Transfer Score: "
    . number_format(
        $strongScore,
        2
    )
    . "<br>";


echo "Recommendation: "
    . $strongRecommendation
    . "<br>";


testPass(
    'Strong replacement produces a positive transfer score',
    $strongScore > 0
);


testPass(
    'Strong replacement produces STRONG TRANSFER',
    $strongRecommendation === 'STRONG TRANSFER'
);


/*
 * ============================================================
 * SCENARIO D
 * Modest transfer
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Modest Transfer<br>";
echo "============================================<br>";


$modestScore =
    $transferModel->calculateTransferScore(
        70,
        73,
        70,
        72,
        60,
        65,
        90,
        92,
        60,
        62
    );


$modestRecommendation =
    $transferModel->getRecommendation(
        $modestScore
    );


echo "Transfer Score: "
    . number_format(
        $modestScore,
        2
    )
    . "<br>";


echo "Recommendation: "
    . $modestRecommendation
    . "<br>";


testPass(
    'Modest replacement produces a positive score',
    $modestScore > 0
);


testPass(
    'Modest replacement produces CONSIDER',
    $modestRecommendation === 'CONSIDER'
);


/*
 * ============================================================
 * SCENARIO E
 * Minimal improvement
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Minimal Improvement<br>";
echo "============================================<br>";


$minimalScore =
    $transferModel->calculateTransferScore(
        70,
        70.5,
        70,
        70.5,
        60,
        60.5,
        90,
        90.5,
        60,
        60.5
    );


$minimalRecommendation =
    $transferModel->getRecommendation(
        $minimalScore
    );


echo "Transfer Score: "
    . number_format(
        $minimalScore,
        2
    )
    . "<br>";


echo "Recommendation: "
    . $minimalRecommendation
    . "<br>";


testPass(
    'Minimal improvement produces HOLD',
    $minimalRecommendation === 'HOLD'
);


/*
 * ============================================================
 * SCENARIO F
 * Weak replacement
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Weak Replacement<br>";
echo "============================================<br>";


$weakScore =
    $transferModel->calculateTransferScore(
        70,
        50,
        70,
        50,
        60,
        40,
        90,
        70,
        60,
        40
    );


$weakRecommendation =
    $transferModel->getRecommendation(
        $weakScore
    );


echo "Transfer Score: "
    . number_format(
        $weakScore,
        2
    )
    . "<br>";


echo "Recommendation: "
    . $weakRecommendation
    . "<br>";


testPass(
    'Weak replacement produces a negative score',
    $weakScore < 0
);


testPass(
    'Weak replacement produces AVOID',
    $weakRecommendation === 'AVOID'
);


/*
 * ============================================================
 * SCENARIO G
 * Recommendation boundaries
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Recommendation Boundaries<br>";
echo "============================================<br>";


$boundaryTests = [

    [
        'score' => 15.00,
        'expected' => 'STRONG TRANSFER'
    ],

    [
        'score' => 7.50,
        'expected' => 'TRANSFER'
    ],

    [
        'score' => 2.50,
        'expected' => 'CONSIDER'
    ],

    [
        'score' => 0.00,
        'expected' => 'HOLD'
    ],

    [
        'score' => -2.50,
        'expected' => 'WEAK TRANSFER'
    ],

    [
        'score' => -7.50,
        'expected' => 'AVOID'
    ]
];


foreach ($boundaryTests as $test) {

    $score =
        $test['score'];

    $expected =
        $test['expected'];

    $actual =
        $transferModel->getRecommendation(
            $score
        );


    echo "Score "
        . number_format(
            $score,
            2
        )
        . ": "
        . $actual
        . "<br>";


    testPass(
        "Score {$score} produces {$expected}",
        $actual === $expected
    );
}


/*
 * ============================================================
 * SCENARIO H
 * Missing data
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Missing Data<br>";
echo "============================================<br>";


$missingScore =
    $transferModel->calculateTransferScore(
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null
    );


echo "Missing Data Score: "
    . (
        $missingScore === null
            ? 'NULL'
            : number_format(
                $missingScore,
                2
            )
    )
    . "<br>";


testPass(
    'Completely missing ratings return null',
    $missingScore === null
);


$partialScore =
    $transferModel->calculateTransferScore(
        70,
        90,
        70,
        90,
        null,
        null,
        null,
        null,
        null,
        null
    );


echo "Partial Data Score: "
    . number_format(
        $partialScore,
        2
    )
    . "<br>";


testPass(
    'Partial transfer data still produces a score',
    $partialScore !== null
);


/*
 * ============================================================
 * SCENARIO I
 * Component influence
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Component Influence<br>";
echo "============================================<br>";


$baseScore =
    $transferModel->calculateTransferScore(
        70,
        70,
        70,
        70,
        70,
        70,
        70,
        70,
        70,
        70
    );


$intelligenceScore =
    $transferModel->calculateTransferScore(
        70,
        90,
        70,
        70,
        70,
        70,
        70,
        70,
        70,
        70
    );


$strengthScore =
    $transferModel->calculateTransferScore(
        70,
        70,
        70,
        90,
        70,
        70,
        70,
        70,
        70,
        70
    );


$valueScore =
    $transferModel->calculateTransferScore(
        70,
        70,
        70,
        70,
        70,
        90,
        70,
        70,
        70,
        70
    );


$availabilityScore =
    $transferModel->calculateTransferScore(
        70,
        70,
        70,
        70,
        70,
        70,
        70,
        90,
        70,
        70
    );


$fixtureScore =
    $transferModel->calculateTransferScore(
        70,
        70,
        70,
        70,
        70,
        70,
        70,
        70,
        70,
        90
    );


echo "Base Score: "
    . number_format(
        $baseScore,
        2
    )
    . "<br>";


echo "Intelligence Improvement Score: "
    . number_format(
        $intelligenceScore,
        2
    )
    . "<br>";


echo "Strength Improvement Score: "
    . number_format(
        $strengthScore,
        2
    )
    . "<br>";


echo "Value Improvement Score: "
    . number_format(
        $valueScore,
        2
    )
    . "<br>";


echo "Availability Improvement Score: "
    . number_format(
        $availabilityScore,
        2
    )
    . "<br>";


echo "Fixture Improvement Score: "
    . number_format(
        $fixtureScore,
        2
    )
    . "<br>";


testPass(
    'Intelligence improvement influences transfer score',
    $intelligenceScore > $baseScore
);


testPass(
    'Strength improvement influences transfer score',
    $strengthScore > $baseScore
);


testPass(
    'Value improvement influences transfer score',
    $valueScore > $baseScore
);


testPass(
    'Availability improvement influences transfer score',
    $availabilityScore > $baseScore
);


testPass(
    'Fixture improvement influences transfer score',
    $fixtureScore > $baseScore
);


/*
 * ============================================================
 * SCENARIO J
 * Complete recommendation model
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario J: Complete Recommendation Model<br>";
echo "============================================<br>";


$recommendation =
    $transferModel->buildRecommendation(
        $currentPlayer,
        $strongReplacement
    );


testPass(
    'Complete recommendation returns an array',
    is_array($recommendation)
);


testPass(
    'Current player ID is preserved',
    $recommendation['current_player_id'] === 1
);


testPass(
    'Current player name is preserved',
    $recommendation['current_player_name']
        === 'Current Player'
);


testPass(
    'Replacement player ID is preserved',
    $recommendation['replacement_player_id'] === 2
);


testPass(
    'Replacement player name is preserved',
    $recommendation['replacement_player_name']
        === 'Strong Replacement'
);


testPass(
    'Intelligence difference exists',
    $recommendation['intelligence_difference'] !== null
);


testPass(
    'Strength difference exists',
    $recommendation['strength_difference'] !== null
);


testPass(
    'Value difference exists',
    $recommendation['value_difference'] !== null
);


testPass(
    'Availability difference exists',
    $recommendation['availability_difference'] !== null
);


testPass(
    'Fixture difference exists',
    $recommendation['fixture_difference'] !== null
);


testPass(
    'Transfer score exists',
    $recommendation['transfer_score'] !== null
);


testPass(
    'Recommendation exists',
    $recommendation['recommendation'] !== null
);


testPass(
    'Reason exists',
    !empty(
        $recommendation['reason']
    )
);


/*
 * ============================================================
 * SCENARIO K
 * Reverse transfer
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario K: Reverse Transfer<br>";
echo "============================================<br>";


$reverseRecommendation =
    $transferModel->buildRecommendation(
        $strongReplacement,
        $currentPlayer
    );


echo "Reverse Transfer Score: "
    . number_format(
        $reverseRecommendation['transfer_score'],
        2
    )
    . "<br>";


echo "Reverse Recommendation: "
    . $reverseRecommendation['recommendation']
    . "<br>";


testPass(
    'Reverse transfer produces a negative score',
    $reverseRecommendation['transfer_score'] < 0
);


testPass(
    'Reverse transfer is not recommended',
    $reverseRecommendation['recommendation']
        === 'AVOID'
);

/*
 * ============================================================
 * SCENARIO M
 * PlayerIntelligenceEngine Summary Integration
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario M: Player Intelligence Engine Summary Integration<br>";
echo "============================================<br>";


$currentEngineProfile = [

    'player' => [

        'player_id' =>
            201,

        'fpl_player_id' =>
            2001,

        'team_id' =>
            1,

        'name' =>
            'Engine Current Player',

        'position' =>
            'MID'
    ],

    'summary' => [

        'player_id' =>
            201,

        'fpl_player_id' =>
            2001,

        'team_id' =>
            1,

        'name' =>
            'Engine Current Player',

        'position' =>
            'MID',

        'price' =>
            7.0,

        'strength_rating' =>
            70.00,

        'value_rating' =>
            60.00,

        'value_label' =>
            'Good',

        'availability_rating' =>
            90.00,

        'reliability_rating' =>
            88.00,

        'availability_label' =>
            'Available',

        'fixture_rating' =>
            60.00,

        'intelligence_score' =>
            70.00,

        'intelligence_label' =>
            'Strong'
    ]
];


$replacementEngineProfile = [

    'player' => [

        'player_id' =>
            202,

        'fpl_player_id' =>
            2002,

        'team_id' =>
            2,

        'name' =>
            'Engine Replacement Player',

        'position' =>
            'MID'
    ],

    'summary' => [

        'player_id' =>
            202,

        'fpl_player_id' =>
            2002,

        'team_id' =>
            2,

        'name' =>
            'Engine Replacement Player',

        'position' =>
            'MID',

        'price' =>
            7.5,

        'strength_rating' =>
            90.00,

        'value_rating' =>
            90.00,

        'value_label' =>
            'Exceptional',

        'availability_rating' =>
            95.00,

        'reliability_rating' =>
            93.00,

        'availability_label' =>
            'Available',

        'fixture_rating' =>
            85.00,

        'intelligence_score' =>
            90.00,

        'intelligence_label' =>
            'Elite'
    ]
];


$engineTransferRecommendation =
    $transferModel->buildRecommendation(
        $currentEngineProfile,
        $replacementEngineProfile
    );


echo "Current Player: "
    . $engineTransferRecommendation['current_player_name']
    . "<br>";

echo "Replacement Player: "
    . $engineTransferRecommendation['replacement_player_name']
    . "<br>";

echo "Transfer Score: "
    . number_format(
        $engineTransferRecommendation['transfer_score'],
        2
    )
    . "<br>";

echo "Recommendation: "
    . $engineTransferRecommendation['recommendation']
    . "<br>";


testPass(
    'Engine profile transfer recommendation returns an array',
    is_array($engineTransferRecommendation)
);


testPass(
    'Engine current player ID is preserved',
    $engineTransferRecommendation['current_player_id'] === 201
);


testPass(
    'Engine current player name is preserved',
    $engineTransferRecommendation['current_player_name']
        === 'Engine Current Player'
);


testPass(
    'Engine replacement player ID is preserved',
    $engineTransferRecommendation['replacement_player_id'] === 202
);


testPass(
    'Engine replacement player name is preserved',
    $engineTransferRecommendation['replacement_player_name']
        === 'Engine Replacement Player'
);


testPass(
    'Engine intelligence difference is calculated',
    $engineTransferRecommendation['intelligence_difference'] === 20.00
);


testPass(
    'Engine strength difference is calculated',
    $engineTransferRecommendation['strength_difference'] === 20.00
);


testPass(
    'Engine value difference is calculated',
    $engineTransferRecommendation['value_difference'] === 30.00
);


testPass(
    'Engine availability difference is calculated',
    $engineTransferRecommendation['availability_difference'] === 5.00
);


testPass(
    'Engine fixture difference is calculated',
    $engineTransferRecommendation['fixture_difference'] === 25.00
);


testPass(
    'Engine transfer score is calculated',
    $engineTransferRecommendation['transfer_score'] === 20.00
);


testPass(
    'Engine profiles produce STRONG TRANSFER',
    $engineTransferRecommendation['recommendation']
        === 'STRONG TRANSFER'
);


testPass(
    'Engine transfer recommendation produces a reason',
    !empty(
        $engineTransferRecommendation['reason']
    )
);


/*
 * ============================================================
 * SCENARIO L
 * Front-end friendly output
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Front-End Friendly Transfer Recommendation<br>";
echo "============================================<br>";


echo "Current Player: "
    . $recommendation['current_player_name']
    . "<br>";


echo "Replacement Player: "
    . $recommendation['replacement_player_name']
    . "<br>";


echo "Intelligence Improvement: "
    . number_format(
        $recommendation['intelligence_difference'],
        2
    )
    . " / 100<br>";


echo "Strength Improvement: "
    . number_format(
        $recommendation['strength_difference'],
        2
    )
    . " / 100<br>";


echo "Value Improvement: "
    . number_format(
        $recommendation['value_difference'],
        2
    )
    . " / 100<br>";


echo "Availability Improvement: "
    . number_format(
        $recommendation['availability_difference'],
        2
    )
    . " / 100<br>";


echo "Fixture Improvement: "
    . number_format(
        $recommendation['fixture_difference'],
        2
    )
    . " / 100<br>";


echo "Transfer Score: "
    . number_format(
        $recommendation['transfer_score'],
        2
    )
    . " / 100<br>";


echo "Recommendation: "
    . $recommendation['recommendation']
    . "<br>";


echo "Reason: "
    . $recommendation['reason']
    . "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Transfer Recommendation Test Summary<br>";
echo "============================================<br>";


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}