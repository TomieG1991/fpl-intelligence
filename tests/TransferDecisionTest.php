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


$decision =
    new TransferDecision();


echo "============================================<br>";
echo "Transfer Decision Test<br>";
echo "============================================<br>";


/*
 * ============================================================
 * SCENARIO A
 * CLEAR UPGRADE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Clear Upgrade<br>";
echo "============================================<br>";


$current = [

    'player_id' => 1,
    'name' => 'Current Player',
    'position' => 'MID',
    'price' => 8.0,
    'intelligence_score' => 60.0,
    'strength_rating' => 60.0,
    'value_rating' => 55.0,
    'fixture_rating' => 55.0,
    'sample_confidence' => 1.0
];


$upgrade = [

    'player_id' => 2,
    'name' => 'Upgrade Player',
    'position' => 'MID',
    'price' => 8.0,
    'intelligence_score' => 66.0,
    'strength_rating' => 65.0,
    'value_rating' => 65.0,
    'fixture_rating' => 70.0,
    'sample_confidence' => 1.0
];


$result =
    $decision->evaluateTransfer(
        $current,
        $upgrade
    );


testPass(
    'Transfer evaluation returns an array',
    is_array(
        $result
    )
);


testPass(
    'Clear Intelligence improvement is detected',
    (
        $result[
            'movements'
        ]['intelligence']
        ?? null
    )
    === 6.00
);


testPass(
    'Clear improvement is classified as Upgrade',
    (
        $result[
            'decision_type'
        ]
        ?? null
    )
    === 'Upgrade'
);


/*
 * ============================================================
 * SCENARIO B
 * BUDGET ENABLER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Budget Enabler<br>";
echo "============================================<br>";


$premium = [

    'player_id' => 3,
    'name' => 'Premium Player',
    'position' => 'MID',
    'price' => 12.0,
    'intelligence_score' => 67.0,
    'strength_rating' => 66.0,
    'value_rating' => 36.0,
    'fixture_rating' => 70.0,
    'sample_confidence' => 1.0
];


$budgetOption = [

    'player_id' => 4,
    'name' => 'Budget Option',
    'position' => 'MID',
    'price' => 9.5,
    'intelligence_score' => 65.5,
    'strength_rating' => 60.0,
    'value_rating' => 45.0,
    'fixture_rating' => 77.0,
    'sample_confidence' => 1.0
];


$budgetResult =
    $decision->evaluateTransfer(
        $premium,
        $budgetOption
    );


testPass(
    'Budget movement is calculated as money released',
    (
        $budgetResult[
            'movements'
        ]['budget']
        ?? null
    )
    === 2.50
);


testPass(
    'Small INT loss with useful budget release becomes Budget Enabler',
    (
        $budgetResult[
            'decision_type'
        ]
        ?? null
    )
    === 'Budget Enabler'
);


/*
 * ============================================================
 * SCENARIO C
 * RISKY PUNT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Risky Punt<br>";
echo "============================================<br>";


$riskyPlayer = [

    'player_id' => 5,
    'name' => 'Risky Prospect',
    'position' => 'MID',
    'price' => 6.0,
    'intelligence_score' => 61.0,
    'strength_rating' => 58.0,
    'value_rating' => 75.0,
    'fixture_rating' => 80.0,
    'sample_confidence' => 0.10
];


$riskyResult =
    $decision->evaluateTransfer(
        $current,
        $riskyPlayer
    );


testPass(
    'Very-low-confidence replacement becomes Risky Punt',
    (
        $riskyResult[
            'decision_type'
        ]
        ?? null
    )
    === 'Risky Punt'
);


/*
 * ============================================================
 * SCENARIO D
 * DOWNGRADE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Downgrade<br>";
echo "============================================<br>";


$weakReplacement = [

    'player_id' => 6,
    'name' => 'Weak Replacement',
    'position' => 'MID',
    'price' => 8.0,
    'intelligence_score' => 50.0,
    'strength_rating' => 50.0,
    'value_rating' => 45.0,
    'fixture_rating' => 40.0,
    'sample_confidence' => 1.0
];


$weakResult =
    $decision->evaluateTransfer(
        $current,
        $weakReplacement
    );


testPass(
    'Poor transfer is classified as Downgrade',
    (
        $weakResult[
            'decision_type'
        ]
        ?? null
    )
    === 'Downgrade'
);


/*
 * ============================================================
 * SCENARIO E
 * MOVEMENTS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Movement Calculations<br>";
echo "============================================<br>";


testPass(
    'Strength movement is calculated',
    (
        $result[
            'movements'
        ]['strength']
        ?? null
    )
    === 5.00
);


testPass(
    'Value movement is calculated',
    (
        $result[
            'movements'
        ]['value']
        ?? null
    )
    === 10.00
);


testPass(
    'Fixture movement is calculated',
    (
        $result[
            'movements'
        ]['fixtures']
        ?? null
    )
    === 15.00
);


testPass(
    'Confidence movement is calculated',
    (
        $result[
            'movements'
        ]['sample_confidence']
        ?? null
    )
    === 0.00
);


/*
 * ============================================================
 * SCENARIO F
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Transfer Summary<br>";
echo "============================================<br>";


testPass(
    'Transfer summary contains current player name',
    str_contains(
        (
            $budgetResult[
                'summary'
            ]
            ?? ''
        ),
        'Premium Player'
    )
);


testPass(
    'Transfer summary contains replacement name',
    str_contains(
        (
            $budgetResult[
                'summary'
            ]
            ?? ''
        ),
        'Budget Option'
    )
);


testPass(
    'Transfer summary mentions released budget',
    str_contains(
        (
            $budgetResult[
                'summary'
            ]
            ?? ''
        ),
        'releases £2.5m'
    )
);


/*
 * ============================================================
 * SCENARIO G
 * MISSING DATA
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Missing Data<br>";
echo "============================================<br>";


$missingResult =
    $decision->evaluateTransfer(
        [],
        []
    );


testPass(
    'Missing data returns a decision structure',
    is_array(
        $missingResult
    )
);


testPass(
    'Missing data produces null decision score',
    (
        $missingResult[
            'decision_score'
        ]
        ?? null
    )
    === null
);


testPass(
    'Missing data produces Insufficient Data classification',
    (
        $missingResult[
            'decision_type'
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
echo "Transfer Decision Test Summary<br>";
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