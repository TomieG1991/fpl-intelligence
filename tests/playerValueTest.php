<?php

require_once __DIR__ . '/../classes/autoload.php';


$playerValue =
    new PlayerValue();


$passed = 0;
$failed = 0;


function testResult(
    string $description,
    bool $result
): void {

    global $passed, $failed;

    if ($result) {

        echo "PASS: {$description}<br>";

        $passed++;

    } else {

        echo "FAIL: {$description}<br>";

        $failed++;
    }
}


echo "<h1>Player Value Model Tests</h1>";

echo "<hr>";


/*
 * ============================================================
 * SCENARIO A: Strength Per Million
 * ============================================================
 */

echo "<h2>Scenario A: Strength Per Million</h2>";

$strengthPerMillion =
    $playerValue->calculateStrengthPerMillion(
        90.0,
        6.0
    );

echo "Strength Per £1m: "
    . number_format(
        $strengthPerMillion,
        2
    )
    . "<br>";

testResult(
    'Strength per million is calculated correctly',
    $strengthPerMillion === 15.00
);


/*
 * ============================================================
 * SCENARIO B: Strong Value
 * ============================================================
 */

echo "<hr>";

echo "<h2>Scenario B: Strong Value</h2>";

$strongValue =
    $playerValue->calculateValueRating(
        15.0
    );

echo "Value Rating: "
    . number_format(
        $strongValue,
        2
    )
    . "<br>";

testResult(
    'Benchmark strength-per-million produces 100 value rating',
    $strongValue === 100.00
);


/*
 * ============================================================
 * SCENARIO C: Exceptional Value
 * ============================================================
 */

echo "<hr>";

echo "<h2>Scenario C: Exceptional Value</h2>";

$exceptionalValue =
    $playerValue->calculateValueRating(
        20.0
    );

echo "Value Rating: "
    . number_format(
        $exceptionalValue,
        2
    )
    . "<br>";

testResult(
    'Value rating is capped at 100',
    $exceptionalValue === 100.00
);


/*
 * ============================================================
 * SCENARIO D: Poor Value
 * ============================================================
 */

echo "<hr>";

echo "<h2>Scenario D: Poor Value</h2>";

$poorValue =
    $playerValue->calculateValueRating(
        6.0
    );

echo "Value Rating: "
    . number_format(
        $poorValue,
        2
    )
    . "<br>";

testResult(
    'Lower strength-per-million produces lower value rating',
    $poorValue === 40.00
);

testResult(
    'Poor value remains within 0-100',
    $poorValue >= 0
    &&
    $poorValue <= 100
);


/*
 * ============================================================
 * SCENARIO E: Value Ordering
 * ============================================================
 */

echo "<hr>";

echo "<h2>Scenario E: Value Ordering</h2>";

$excellent =
    $playerValue->calculateValueRating(
        18.0
    );

$average =
    $playerValue->calculateValueRating(
        10.0
    );

$poor =
    $playerValue->calculateValueRating(
        5.0
    );

echo "Excellent: "
    . number_format($excellent, 2)
    . "<br>";

echo "Average: "
    . number_format($average, 2)
    . "<br>";

echo "Poor: "
    . number_format($poor, 2)
    . "<br>";

testResult(
    'Higher strength-per-million produces higher value',
    $excellent > $average
    &&
    $average > $poor
);


/*
 * ============================================================
 * SCENARIO F: Value Labels
 * ============================================================
 */

echo "<hr>";

echo "<h2>Scenario F: Value Labels</h2>";

$labels = [

    'Exceptional' =>
        $playerValue->getValueLabel(95),

    'Excellent' =>
        $playerValue->getValueLabel(80),

    'Good' =>
        $playerValue->getValueLabel(65),

    'Average' =>
        $playerValue->getValueLabel(50),

    'Poor' =>
        $playerValue->getValueLabel(30),

    'Very Poor' =>
        $playerValue->getValueLabel(10)
];


foreach ($labels as $expected => $actual) {

    echo "{$expected}: {$actual}<br>";

    testResult(
        "{$expected} label is correct",
        $expected === $actual
    );
}


/*
 * ============================================================
 * SCENARIO G: Missing Data
 * ============================================================
 */

echo "<hr>";

echo "<h2>Scenario G: Missing Data</h2>";

$missingStrength =
    $playerValue->calculateStrengthPerMillion(
        null,
        6.0
    );

$missingPrice =
    $playerValue->calculateStrengthPerMillion(
        90.0,
        null
    );

$zeroPrice =
    $playerValue->calculateStrengthPerMillion(
        90.0,
        0.0
    );

echo "Missing Strength: "
    . var_export(
        $missingStrength,
        true
    )
    . "<br>";

echo "Missing Price: "
    . var_export(
        $missingPrice,
        true
    )
    . "<br>";

echo "Zero Price: "
    . var_export(
        $zeroPrice,
        true
    )
    . "<br>";

testResult(
    'Missing strength returns null',
    $missingStrength === null
);

testResult(
    'Missing price returns null',
    $missingPrice === null
);

testResult(
    'Zero price returns null',
    $zeroPrice === null
);


/*
 * ============================================================
 * SCENARIO H: Complete Player Value Model
 * ============================================================
 */

echo "<hr>";

echo "<h2>Scenario H: Complete Player Value Model</h2>";

$testStrength = [

    'player_id' =>
        101,

    'name' =>
        'Test Forward',

    'position' =>
        'FWD',

    'strength_rating' =>
        90.0
];


$testPerformance = [

    'price' =>
        6.0
];


$valueModel =
    $playerValue->buildValueModel(
        $testStrength,
        $testPerformance
    );


echo "Player: "
    . $valueModel['name']
    . "<br>";

echo "Position: "
    . $valueModel['position']
    . "<br>";

echo "Price: £"
    . number_format(
        $valueModel['price'],
        1
    )
    . "m<br>";

echo "Strength Rating: "
    . number_format(
        $valueModel['strength_rating'],
        2
    )
    . "<br>";

echo "Strength Per £1m: "
    . number_format(
        $valueModel['strength_per_million'],
        2
    )
    . "<br>";

echo "Value Rating: "
    . number_format(
        $valueModel['value_rating'],
        2
    )
    . "<br>";

echo "Value Label: "
    . $valueModel['value_label']
    . "<br>";


testResult(
    'Complete value model preserves player ID',
    $valueModel['player_id'] === 101
);

testResult(
    'Complete value model preserves player name',
    $valueModel['name'] === 'Test Forward'
);

testResult(
    'Complete value model calculates strength per million',
    $valueModel['strength_per_million'] === 15.00
);

testResult(
    'Complete value model calculates value rating',
    $valueModel['value_rating'] === 100.00
);

testResult(
    'Complete value model produces value label',
    $valueModel['value_label'] === 'Exceptional'
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

echo "<hr>";

echo "<h2>Front-End Friendly Player Value</h2>";

echo "Player: "
    . $valueModel['name']
    . "<br>";

echo "Position: "
    . $valueModel['position']
    . "<br>";

echo "Price: £"
    . number_format(
        $valueModel['price'],
        1
    )
    . "m<br>";

echo "Strength Rating: "
    . number_format(
        $valueModel['strength_rating'],
        2
    )
    . " / 100<br>";

echo "Strength Per £1m: "
    . number_format(
        $valueModel['strength_per_million'],
        2
    )
    . "<br>";

echo "Value Rating: "
    . number_format(
        $valueModel['value_rating'],
        2
    )
    . " / 100<br>";

echo "Value Label: "
    . $valueModel['value_label']
    . "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<hr>";

echo "<h2>Player Value Model Test Summary</h2>";

echo "Passed: {$passed}<br>";

echo "Failed: {$failed}<br>";

if ($failed === 0) {

    echo "<strong>RESULT: ALL TESTS PASSED ✅</strong>";

} else {

    echo "<strong>RESULT: TESTS FAILED ❌</strong>";
}