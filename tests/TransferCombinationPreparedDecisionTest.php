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


/*
 * ============================================================
 * FIXTURES
 * ============================================================
 */

$currentA = [

    'player_id' => 1,
    'name' => 'Current Defender',
    'position' => 'DEF',
    'team_name' => 'Team A',
    'price' => 6.0,
    'intelligence_score' => 60.0,
    'strength_rating' => 61.0,
    'value_rating' => 55.0,
    'fixture_rating' => 58.0,
    'sample_confidence' => 0.80,
    'verdict' => 'Hold'
];


$replacementA = [

    'player_id' => 2,
    'name' => 'Replacement Defender',
    'position' => 'DEF',
    'team_name' => 'Team B',
    'price' => 6.5,
    'intelligence_score' => 65.0,
    'strength_rating' => 64.0,
    'value_rating' => 57.0,
    'fixture_rating' => 62.0,
    'sample_confidence' => 0.85,
    'verdict' => 'Buy'
];


$currentB = [

    'player_id' => 3,
    'name' => 'Current Midfielder',
    'position' => 'MID',
    'team_name' => 'Team C',
    'price' => 7.0,
    'intelligence_score' => 62.0,
    'strength_rating' => 63.0,
    'value_rating' => 58.0,
    'fixture_rating' => 60.0,
    'sample_confidence' => 0.75,
    'verdict' => 'Hold'
];


$replacementB = [

    'player_id' => 4,
    'name' => 'Replacement Midfielder',
    'position' => 'MID',
    'team_name' => 'Team D',
    'price' => 7.2,
    'intelligence_score' => 68.0,
    'strength_rating' => 67.0,
    'value_rating' => 61.0,
    'fixture_rating' => 65.0,
    'sample_confidence' => 0.90,
    'verdict' => 'Buy'
];


/*
 * ============================================================
 * AUTHORITATIVE CURRENT RESULT
 * ============================================================
 */

echo "============================================<br>";
echo "Transfer Combination Prepared Decision Test<br>";
echo "============================================<br><br>";


$transferDecision =
    new TransferDecision();


$transferCombination =
    new TransferCombination();


$authoritative =
    $transferCombination
        ->evaluateCombination(
            $currentA,
            $replacementA,
            $currentB,
            $replacementB
        );


$decisionA =
    $transferDecision
        ->evaluateTransfer(
            $currentA,
            $replacementA
        );


$decisionB =
    $transferDecision
        ->evaluateTransfer(
            $currentB,
            $replacementB
        );


/*
 * ============================================================
 * PREPARED DECISION PATH
 * ============================================================
 */

try {

    $prepared =
        $transferCombination
            ->evaluatePreparedCombination(
                $decisionA,
                $decisionB
            );


    testPass(
        'Prepared combination returns an array',
        is_array(
            $prepared
        )
    );


    testPass(
        'Prepared combination exactly matches authoritative combination output',
        $prepared
        ===
        $authoritative
    );


    testPass(
        'Prepared combination preserves Transfer A decision exactly',
        (
            $prepared[
                'transfer_a'
            ]
            ?? null
        )
        ===
        $decisionA
    );


    testPass(
        'Prepared combination preserves Transfer B decision exactly',
        (
            $prepared[
                'transfer_b'
            ]
            ?? null
        )
        ===
        $decisionB
    );


    testPass(
        'Prepared combination preserves combined movements',
        (
            $prepared[
                'combined_movements'
            ]
            ?? null
        )
        ===
        (
            $authoritative[
                'combined_movements'
            ]
            ?? null
        )
    );


    testPass(
        'Prepared combination preserves combination score',
        (
            $prepared[
                'combination_score'
            ]
            ?? null
        )
        ===
        (
            $authoritative[
                'combination_score'
            ]
            ?? null
        )
    );


    testPass(
        'Prepared combination preserves classification',
        (
            $prepared[
                'classification'
            ]
            ?? null
        )
        ===
        (
            $authoritative[
                'classification'
            ]
            ?? null
        )
    );


    testPass(
        'Prepared combination preserves summary',
        (
            $prepared[
                'summary'
            ]
            ?? null
        )
        ===
        (
            $authoritative[
                'summary'
            ]
            ?? null
        )
    );

} catch (
    Throwable $exception
) {

    echo "EXPECTED RED: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Summary<br>";
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