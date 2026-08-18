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


$combination =
    new TransferCombination();


echo "============================================<br>";
echo "Transfer Combination Test<br>";
echo "============================================<br>";


/*
 * ============================================================
 * BASE PLAYERS
 * ============================================================
 */

$premiumMid = [

    'player_id' => 1,
    'name' => 'Premium Mid',
    'position' => 'MID',
    'price' => 12.0,
    'intelligence_score' => 67.0,
    'strength_rating' => 66.0,
    'value_rating' => 36.0,
    'fixture_rating' => 70.0,
    'sample_confidence' => 1.0
];


$cheaperMid = [

    'player_id' => 2,
    'name' => 'Cheaper Mid',
    'position' => 'MID',
    'price' => 9.5,
    'intelligence_score' => 65.5,
    'strength_rating' => 60.0,
    'value_rating' => 45.0,
    'fixture_rating' => 77.0,
    'sample_confidence' => 1.0
];


$weakForward = [

    'player_id' => 3,
    'name' => 'Weak Forward',
    'position' => 'FWD',
    'price' => 5.5,
    'intelligence_score' => 52.0,
    'strength_rating' => 50.0,
    'value_rating' => 55.0,
    'fixture_rating' => 55.0,
    'sample_confidence' => 1.0
];


$strongForward = [

    'player_id' => 4,
    'name' => 'Strong Forward',
    'position' => 'FWD',
    'price' => 8.0,
    'intelligence_score' => 60.0,
    'strength_rating' => 60.0,
    'value_rating' => 60.0,
    'fixture_rating' => 65.0,
    'sample_confidence' => 1.0
];


/*
 * ============================================================
 * SCENARIO A
 * BALANCED TWO-TRANSFER IMPROVEMENT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Balanced Improvement<br>";
echo "============================================<br>";


$result =
    $combination
        ->evaluateCombination(
            $premiumMid,
            $cheaperMid,
            $weakForward,
            $strongForward
        );


testPass(
    'Combination returns an array',
    is_array(
        $result
    )
);


testPass(
    'Transfer A decision exists',
    isset(
        $result[
            'transfer_a'
        ]
    )
);


testPass(
    'Transfer B decision exists',
    isset(
        $result[
            'transfer_b'
        ]
    )
);


testPass(
    'Combined movements exist',
    isset(
        $result[
            'combined_movements'
        ]
    )
    &&
    is_array(
        $result[
            'combined_movements'
        ]
    )
);


testPass(
    'Combined Intelligence movement is calculated',
    (
        $result[
            'combined_movements'
        ]['intelligence']
        ?? null
    )
    === 6.50
);


testPass(
    'Combined budget movement is zero',
    (
        $result[
            'combined_movements'
        ]['budget']
        ?? null
    )
    === 0.00
);


testPass(
    'Balanced combination is affordable',
    (
        $result[
            'is_affordable'
        ]
        ?? false
    )
    === true
);


testPass(
    'Combination score exists',
    is_numeric(
        $result[
            'combination_score'
        ]
        ?? null
    )
);


testPass(
    'Strong combined Intelligence gain becomes Strong Improvement',
    (
        $result[
            'classification'
        ]
        ?? null
    )
    === 'Strong Improvement'
);


/*
 * ============================================================
 * SCENARIO B
 * UNAFFORDABLE COMBINATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Unaffordable Combination<br>";
echo "============================================<br>";


$expensiveForward =
    $strongForward;


$expensiveForward[
    'price'
] =
    10.0;


$unaffordable =
    $combination
        ->evaluateCombination(
            $premiumMid,
            $cheaperMid,
            $weakForward,
            $expensiveForward
        );


testPass(
    'Negative combined budget is detected',
    (
        $unaffordable[
            'combined_movements'
        ]['budget']
        ?? 0
    )
    < 0
);


testPass(
    'Unaffordable combination flag is false',
    (
        $unaffordable[
            'is_affordable'
        ]
        ?? true
    )
    === false
);


testPass(
    'Unaffordable combination is classified correctly',
    (
        $unaffordable[
            'classification'
        ]
        ?? null
    )
    === 'Unaffordable'
);


/*
 * ============================================================
 * SCENARIO C
 * RISKY RESTRUCTURE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Risky Restructure<br>";
echo "============================================<br>";


$riskyForward = [

    'player_id' => 5,
    'name' => 'Risky Forward',
    'position' => 'FWD',
    'price' => 6.0,
    'intelligence_score' => 58.0,
    'strength_rating' => 58.0,
    'value_rating' => 75.0,
    'fixture_rating' => 75.0,
    'sample_confidence' => 0.10
];


$riskyResult =
    $combination
        ->evaluateCombination(
            $premiumMid,
            $cheaperMid,
            $weakForward,
            $riskyForward
        );


testPass(
    'Low-confidence transfer can make combination risky',
    (
        $riskyResult[
            'classification'
        ]
        ?? null
    )
    === 'Risky Restructure'
);


/*
 * ============================================================
 * SCENARIO D
 * COMBINED DOWNGRADE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Combined Downgrade<br>";
echo "============================================<br>";


$badMid = [

    'player_id' => 6,
    'name' => 'Bad Mid',
    'position' => 'MID',
    'price' => 7.0,
    'intelligence_score' => 55.0,
    'strength_rating' => 55.0,
    'value_rating' => 45.0,
    'fixture_rating' => 50.0,
    'sample_confidence' => 1.0
];


$badForward = [

    'player_id' => 7,
    'name' => 'Bad Forward',
    'position' => 'FWD',
    'price' => 5.0,
    'intelligence_score' => 48.0,
    'strength_rating' => 48.0,
    'value_rating' => 50.0,
    'fixture_rating' => 45.0,
    'sample_confidence' => 1.0
];


$badResult =
    $combination
        ->evaluateCombination(
            $premiumMid,
            $badMid,
            $weakForward,
            $badForward
        );


testPass(
    'Material combined INT loss becomes Downgrade',
    (
        $badResult[
            'classification'
        ]
        ?? null
    )
    === 'Downgrade'
);


/*
 * ============================================================
 * SCENARIO E
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Combination Summary<br>";
echo "============================================<br>";


testPass(
    'Summary contains first outgoing player',
    str_contains(
        (
            $result[
                'summary'
            ]
            ?? ''
        ),
        'Premium Mid'
    )
);


testPass(
    'Summary contains first incoming player',
    str_contains(
        (
            $result[
                'summary'
            ]
            ?? ''
        ),
        'Cheaper Mid'
    )
);


testPass(
    'Summary contains second outgoing player',
    str_contains(
        (
            $result[
                'summary'
            ]
            ?? ''
        ),
        'Weak Forward'
    )
);


testPass(
    'Summary contains second incoming player',
    str_contains(
        (
            $result[
                'summary'
            ]
            ?? ''
        ),
        'Strong Forward'
    )
);


/*
 * ============================================================
 * SCENARIO F
 * MISSING DATA
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Missing Data<br>";
echo "============================================<br>";


$missing =
    $combination
        ->evaluateCombination(
            [],
            [],
            [],
            []
        );


testPass(
    'Missing data still returns structure',
    is_array(
        $missing
    )
);


testPass(
    'Missing data produces null combination score',
    (
        $missing[
            'combination_score'
        ]
        ?? null
    )
    === null
);


testPass(
    'Missing data produces Insufficient Data',
    (
        $missing[
            'classification'
        ]
        ?? null
    )
    === 'Insufficient Data'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Transfer Combination Test Summary<br>";
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