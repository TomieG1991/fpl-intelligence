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


function section(
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

$model =
    new PlayerIntelligenceScore();


/*
 * ============================================================
 * SCENARIO A
 * Core Intelligence Weights
 * ============================================================
 */

section(
    'Scenario A: Core Intelligence Weights'
);


$weights =
    $model->getWeights();


echo "Strength Weight: "
    . number_format(
        $weights['strength'],
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
    'Strength weight is 65%',
    $weights['strength'] === 0.65
);


testPass(
    'Fixture weight is 35%',
    $weights['fixtures'] === 0.35
);


testPass(
    'Core intelligence weights total 1.00',
    abs(
        $totalWeight - 1.00
    ) < 0.0001
);


/*
 * ============================================================
 * SCENARIO B
 * Standard Core Intelligence Score
 * ============================================================
 */

section(
    'Scenario B: Standard Core Intelligence Score'
);


$coreScore =
    $model->calculateCoreScore(
        90.00,
        80.00
    );


$expectedCoreScore =
    round(
        (90.00 * 0.65)
        +
        (80.00 * 0.35),
        2
    );


echo "Core Intelligence Score: "
    . number_format(
        $coreScore,
        2
    )
    . "<br>";


echo "Expected Core Score: "
    . number_format(
        $expectedCoreScore,
        2
    )
    . "<br>";


testPass(
    'Core intelligence score is calculated correctly',
    $coreScore === $expectedCoreScore
);


testPass(
    'Core intelligence score remains between 0 and 100',
    $coreScore >= 0
    &&
    $coreScore <= 100
);


/*
 * ============================================================
 * SCENARIO C
 * Fully Available Player
 * ============================================================
 */

section(
    'Scenario C: Fully Available Player'
);


$score =
    $model->calculateScore(
        90.00,
        100.00,
        100.00,
        80.00
    );


echo "Final Intelligence Score: "
    . number_format(
        $score,
        2
    )
    . "<br>";


testPass(
    'Fully available player receives no availability penalty',
    $score === $expectedCoreScore
);


/*
 * ============================================================
 * SCENARIO D
 * Availability Multipliers
 * ============================================================
 */

section(
    'Scenario D: Availability Multipliers'
);


$availability100 =
    $model->calculateAvailabilityMultiplier(
        100.00
    );


$availability80 =
    $model->calculateAvailabilityMultiplier(
        80.00
    );


$availability60 =
    $model->calculateAvailabilityMultiplier(
        60.00
    );


$availability30 =
    $model->calculateAvailabilityMultiplier(
        30.00
    );


$availability10 =
    $model->calculateAvailabilityMultiplier(
        10.00
    );


$availability0 =
    $model->calculateAvailabilityMultiplier(
        0.00
    );


$availabilityUnknown =
    $model->calculateAvailabilityMultiplier(
        null
    );


echo "100 Availability: "
    . number_format(
        $availability100,
        2
    )
    . "<br>";


echo "80 Availability: "
    . number_format(
        $availability80,
        2
    )
    . "<br>";


echo "60 Availability: "
    . number_format(
        $availability60,
        2
    )
    . "<br>";


echo "30 Availability: "
    . number_format(
        $availability30,
        2
    )
    . "<br>";


echo "10 Availability: "
    . number_format(
        $availability10,
        2
    )
    . "<br>";


echo "0 Availability: "
    . number_format(
        $availability0,
        2
    )
    . "<br>";


echo "Unknown Availability: "
    . number_format(
        $availabilityUnknown,
        2
    )
    . "<br>";


testPass(
    '100 availability produces multiplier 1.00',
    $availability100 === 1.00
);


testPass(
    '80 availability produces multiplier 0.95',
    $availability80 === 0.95
);


testPass(
    '60 availability produces multiplier 0.85',
    $availability60 === 0.85
);


testPass(
    '30 availability produces multiplier 0.60',
    $availability30 === 0.60
);


testPass(
    '10 availability produces multiplier 0.35',
    $availability10 === 0.35
);


testPass(
    '0 availability produces multiplier 0.10',
    $availability0 === 0.10
);


testPass(
    'Unknown availability produces no penalty',
    $availabilityUnknown === 1.00
);


/*
 * ============================================================
 * SCENARIO E
 * Availability Penalty
 * ============================================================
 */

section(
    'Scenario E: Availability Penalty'
);


$fullyAvailable =
    $model->calculateScore(
        90.00,
        100.00,
        100.00,
        80.00
    );


$doubtful =
    $model->calculateScore(
        90.00,
        100.00,
        50.00,
        80.00
    );


$unavailable =
    $model->calculateScore(
        90.00,
        100.00,
        0.00,
        80.00
    );


echo "Fully Available Score: "
    . number_format(
        $fullyAvailable,
        2
    )
    . "<br>";


echo "Doubtful Score: "
    . number_format(
        $doubtful,
        2
    )
    . "<br>";


echo "Unavailable Score: "
    . number_format(
        $unavailable,
        2
    )
    . "<br>";


testPass(
    'Availability risk reduces intelligence score',
    $fullyAvailable
    >
    $doubtful
);


testPass(
    'Unavailable player scores lower than doubtful player',
    $doubtful
    >
    $unavailable
);


/*
 * ============================================================
 * SCENARIO F
 * Value Does Not Change Overall Intelligence
 * ============================================================
 */

section(
    'Scenario F: Value Separation'
);


$highValue =
    $model->calculateScore(
        80.00,
        100.00,
        100.00,
        70.00
    );


$lowValue =
    $model->calculateScore(
        80.00,
        10.00,
        100.00,
        70.00
    );


echo "High Value Score: "
    . number_format(
        $highValue,
        2
    )
    . "<br>";


echo "Low Value Score: "
    . number_format(
        $lowValue,
        2
    )
    . "<br>";


testPass(
    'Value rating does not directly change overall intelligence score',
    $highValue === $lowValue
);


/*
 * ============================================================
 * SCENARIO G
 * Strength Influence
 * ============================================================
 */

section(
    'Scenario G: Strength Influence'
);


$strongPlayer =
    $model->calculateScore(
        90.00,
        50.00,
        100.00,
        70.00
    );


$weakPlayer =
    $model->calculateScore(
        40.00,
        100.00,
        100.00,
        70.00
    );


testPass(
    'Higher strength rating produces higher intelligence score',
    $strongPlayer
    >
    $weakPlayer
);


/*
 * ============================================================
 * SCENARIO H
 * Fixture Influence
 * ============================================================
 */

section(
    'Scenario H: Fixture Influence'
);


$goodFixtures =
    $model->calculateScore(
        70.00,
        50.00,
        100.00,
        90.00
    );


$poorFixtures =
    $model->calculateScore(
        70.00,
        50.00,
        100.00,
        30.00
    );


testPass(
    'Better fixture opportunity produces higher intelligence score',
    $goodFixtures
    >
    $poorFixtures
);


/*
 * ============================================================
 * SCENARIO I
 * Missing Fixture Handling
 * ============================================================
 */

section(
    'Scenario I: Missing Fixture Handling'
);


$missingFixture =
    $model->calculateScore(
        82.00,
        90.00,
        100.00,
        null
    );


echo "Score Without Fixture Rating: "
    . number_format(
        $missingFixture,
        2
    )
    . "<br>";


testPass(
    'Missing fixture rating redistributes fully to strength',
    $missingFixture === 82.00
);


/*
 * ============================================================
 * SCENARIO J
 * Missing Strength Handling
 * ============================================================
 */

section(
    'Scenario J: Missing Strength Handling'
);


$missingStrength =
    $model->calculateScore(
        null,
        90.00,
        100.00,
        74.00
    );


echo "Score Without Strength Rating: "
    . (
        $missingStrength !== null
            ? number_format(
                $missingStrength,
                2
            )
            : 'NULL'
    )
    . "<br>";


testPass(
    'Missing strength rating prevents an overall intelligence score',
    $missingStrength === null
);


/*
 * ============================================================
 * SCENARIO K
 * Completely Missing Core Data
 * ============================================================
 */

section(
    'Scenario K: Completely Missing Core Data'
);


$missingData =
    $model->calculateScore(
        null,
        100.00,
        100.00,
        null
    );


echo "Missing Data Score: "
    . (
        $missingData
        ?? 'NULL'
    )
    . "<br>";


testPass(
    'Completely missing core ratings return null',
    $missingData === null
);


/*
 * ============================================================
 * SCENARIO L
 * Rating Bounds
 * ============================================================
 */

section(
    'Scenario L: Rating Bounds'
);


$over100 =
    $model->calculateScore(
        150.00,
        100.00,
        100.00,
        150.00
    );


$below0 =
    $model->calculateScore(
        -50.00,
        100.00,
        100.00,
        -50.00
    );


echo "Over 100 Input: "
    . number_format(
        $over100,
        2
    )
    . "<br>";


echo "Below 0 Input: "
    . number_format(
        $below0,
        2
    )
    . "<br>";


testPass(
    'Ratings above 100 are capped',
    $over100 === 100.00
);


testPass(
    'Ratings below 0 are capped',
    $below0 === 0.00
);


/*
 * ============================================================
 * SCENARIO M
 * Intelligence Labels
 * ============================================================
 */

section(
    'Scenario M: Intelligence Labels'
);


testPass(
    '90 rating produces Elite label',
    $model->getLabel(
        90.00
    ) === 'Elite'
);


testPass(
    '75 rating produces Strong label',
    $model->getLabel(
        75.00
    ) === 'Strong'
);


testPass(
    '60 rating produces Average label',
    $model->getLabel(
        60.00
    ) === 'Average'
);


testPass(
    '45 rating produces Below Average label',
    $model->getLabel(
        45.00
    ) === 'Below Average'
);


testPass(
    '20 rating produces Weak label',
    $model->getLabel(
        20.00
    ) === 'Weak'
);


testPass(
    'Missing rating produces Unknown label',
    $model->getLabel(
        null
    ) === 'Unknown'
);


/*
 * ============================================================
 * SCENARIO N
 * Complete Intelligence Model
 * ============================================================
 */

section(
    'Scenario N: Complete Intelligence Model'
);


$playerStrength = [

    'player_id' =>
        1,

    'name' =>
        'Test Forward',

    'position' =>
        'FWD',

    'strength_rating' =>
        90.00
];


$playerValue = [

    'player_id' =>
        1,

    'name' =>
        'Test Forward',

    'position' =>
        'FWD',

    'value_rating' =>
        100.00
];


$playerAvailability = [

    'player_id' =>
        1,

    'name' =>
        'Test Forward',

    'position' =>
        'FWD',

    'availability_rating' =>
        100.00
];


$completeModel =
    $model->buildModel(
        $playerStrength,
        $playerValue,
        $playerAvailability,
        80.00
    );


testPass(
    'Complete intelligence model returns an array',
    is_array(
        $completeModel
    )
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
    'Value rating remains available separately',
    $completeModel['value_rating'] === 100.00
);


testPass(
    'Availability rating is connected',
    $completeModel['availability_rating'] === 100.00
);


testPass(
    'Fixture rating is connected',
    $completeModel['fixture_rating'] === 80.00
);


testPass(
    'Core score exists',
    isset(
        $completeModel['core_score']
    )
);


testPass(
    'Availability multiplier exists',
    isset(
        $completeModel[
            'availability_multiplier'
        ]
    )
);


testPass(
    'Fully available player has multiplier 1.00',
    $completeModel[
        'availability_multiplier'
    ] === 1.00
);


testPass(
    'Intelligence score exists',
    isset(
        $completeModel[
            'intelligence_score'
        ]
    )
);


testPass(
    'Intelligence score equals core score when fully available',
    $completeModel[
        'intelligence_score'
    ]
    ===
    $completeModel[
        'core_score'
    ]
);


testPass(
    'Intelligence label exists',
    $completeModel[
        'intelligence_label'
    ] !== ''
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

section(
    'Front-End Friendly Player Intelligence'
);


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
        $completeModel[
            'availability_rating'
        ],
        2
    )
    . " / 100<br>";


echo "Fixture Opportunity: "
    . number_format(
        $completeModel['fixture_rating'],
        2
    )
    . " / 100<br>";


echo "Core Score: "
    . number_format(
        $completeModel['core_score'],
        2
    )
    . " / 100<br>";


echo "Availability Multiplier: "
    . number_format(
        $completeModel[
            'availability_multiplier'
        ],
        2
    )
    . "<br>";


echo "Intelligence Score: "
    . number_format(
        $completeModel[
            'intelligence_score'
        ],
        2
    )
    . " / 100<br>";


echo "Intelligence Label: "
    . $completeModel[
        'intelligence_label'
    ]
    . "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

section(
    'Player Intelligence Score Test Summary'
);


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}