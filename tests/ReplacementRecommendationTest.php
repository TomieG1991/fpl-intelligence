<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function testPass(
    string $message,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . $message
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . $message
        . "<br>";

    $failed++;
}


$recommendation =
    new ReplacementRecommendation();


echo "============================================<br>";
echo "Replacement Recommendation Test<br>";
echo "============================================<br>";


$replacements = [

    [
        'player_id' => 1,
        'name' => 'Best Overall',
        'intelligence_score' => 72.0,
        'value_rating' => 60.0,
        'fixture_rating' => 65.0,
        'availability_rating' => 100.0,
        'sample_confidence' => 1.0
    ],

    [
        'player_id' => 2,
        'name' => 'Best Value',
        'intelligence_score' => 65.0,
        'value_rating' => 90.0,
        'fixture_rating' => 60.0,
        'availability_rating' => 100.0,
        'sample_confidence' => 1.0
    ],

    [
        'player_id' => 3,
        'name' => 'Best Fixtures',
        'intelligence_score' => 64.0,
        'value_rating' => 65.0,
        'fixture_rating' => 95.0,
        'availability_rating' => 100.0,
        'sample_confidence' => 1.0
    ],

    [
        'player_id' => 4,
        'name' => 'High Upside',
        'intelligence_score' => 69.0,
        'value_rating' => 80.0,
        'fixture_rating' => 85.0,
        'availability_rating' => 100.0,
        'sample_confidence' => 0.40
    ],

    [
        'player_id' => 5,
        'name' => 'Unsafe Punt',
        'intelligence_score' => 75.0,
        'value_rating' => 95.0,
        'fixture_rating' => 95.0,
        'availability_rating' => 100.0,
        'sample_confidence' => 0.02
    ]
];


$result =
    $recommendation
        ->buildRecommendations(
            $replacements
        );


/*
 * ============================================================
 * SCENARIO A
 * STRUCTURE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Recommendation Structure<br>";
echo "============================================<br>";


testPass(
    'Recommendation result returns an array',
    is_array(
        $result
    )
);


testPass(
    'Best overall recommendation exists',
    array_key_exists(
        'best_overall',
        $result
    )
);


testPass(
    'Best value recommendation exists',
    array_key_exists(
        'best_value',
        $result
    )
);


testPass(
    'Best fixtures recommendation exists',
    array_key_exists(
        'best_fixtures',
        $result
    )
);


testPass(
    'Safest pick recommendation exists',
    array_key_exists(
        'safest_pick',
        $result
    )
);


testPass(
    'High-upside recommendation exists',
    array_key_exists(
        'high_upside',
        $result
    )
);


/*
 * ============================================================
 * SCENARIO B
 * CATEGORY SELECTION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Category Selection<br>";
echo "============================================<br>";


testPass(
    'Highest Intelligence becomes Best Overall',
    (
        $result[
            'best_overall'
        ]['name']
        ?? null
    )
    === 'Unsafe Punt'
);


testPass(
    'Highest eligible Value becomes Best Value',
    (
        $result[
            'best_value'
        ]['name']
        ?? null
    )
    === 'Best Value'
);

testPass(
    'Best Value excludes extremely tiny samples',
    (
        $result[
            'best_value'
        ]['name']
        ?? null
    )
    !== 'Unsafe Punt'
);


testPass(
    'Highest Fixtures becomes Best Fixtures',
    (
        $result[
            'best_fixtures'
        ]['name']
        ?? null
    )
    === 'Unsafe Punt'
);


/*
 * Safest Pick should favour the full-sample
 * established candidate despite lower raw INT.
 */

testPass(
    'Full-sample established player becomes Safest Pick',
    (
        $result[
            'safest_pick'
        ]['name']
        ?? null
    )
    === 'Best Overall'
);


/*
 * Unsafe Punt has only 2% confidence and must be excluded
 * from High Upside.
 */

testPass(
    'High Upside excludes extremely tiny samples',
    (
        $result[
            'high_upside'
        ]['name']
        ?? null
    )
    === 'High Upside'
);


/*
 * ============================================================
 * SCENARIO C
 * EMPTY DATA
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Empty Replacement Data<br>";
echo "============================================<br>";


$emptyResult =
    $recommendation
        ->buildRecommendations(
            []
        );


testPass(
    'Empty replacements return null Best Overall',
    $emptyResult[
        'best_overall'
    ]
    === null
);


testPass(
    'Empty replacements return null Best Value',
    $emptyResult[
        'best_value'
    ]
    === null
);


testPass(
    'Empty replacements return null Best Fixtures',
    $emptyResult[
        'best_fixtures'
    ]
    === null
);


testPass(
    'Empty replacements return null Safest Pick',
    $emptyResult[
        'safest_pick'
    ]
    === null
);


testPass(
    'Empty replacements return null High Upside',
    $emptyResult[
        'high_upside'
    ]
    === null
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Replacement Recommendation Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}