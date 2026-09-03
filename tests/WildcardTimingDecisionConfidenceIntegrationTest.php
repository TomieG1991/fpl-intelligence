<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardTimingDecisionConfidenceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        return;
    }


    $failed++;

    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';
}


echo
    '============================================<br>';

echo
    'Wildcard Timing Decision Confidence Integration Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: Strong timing separation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Strong timing separation<br>';

echo
    '============================================<br>';


$strongDecision =
    $intelligence
        ->createDecision(
            12.0,
            2.0
        );


wildcardTimingDecisionConfidenceCheck(
    'Ten point timing separation produces maximum decision confidence',
    $strongDecision
        ->getConfidence()
        ===
        1.0
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Medium timing separation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Medium timing separation<br>';

echo
    '============================================<br>';


$mediumDecision =
    $intelligence
        ->createDecision(
            10.0,
            5.0
        );


wildcardTimingDecisionConfidenceCheck(
    'Five point timing separation produces 0.50 decision confidence',
    abs(
        $mediumDecision
            ->getConfidence()
        -
        0.5
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Small timing separation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Small timing separation<br>';

echo
    '============================================<br>';


$smallDecision =
    $intelligence
        ->createDecision(
            8.0,
            6.0
        );


wildcardTimingDecisionConfidenceCheck(
    'Two point timing separation produces 0.20 decision confidence',
    abs(
        $smallDecision
            ->getConfidence()
        -
        0.2
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Equal timing value
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Equal timing value<br>';

echo
    '============================================<br>';


$equalDecision =
    $intelligence
        ->createDecision(
            8.0,
            8.0
        );


wildcardTimingDecisionConfidenceCheck(
    'Equal timing value produces zero decision confidence',
    $equalDecision
        ->getConfidence()
        ===
        0.0
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Waiting advantage
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Waiting advantage<br>';

echo
    '============================================<br>';


$waitDecision =
    $intelligence
        ->createDecision(
            4.0,
            9.0
        );


wildcardTimingDecisionConfidenceCheck(
    'Five point waiting advantage also produces 0.50 confidence',
    abs(
        $waitDecision
            ->getConfidence()
        -
        0.5
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario F: Confidence matches dedicated calculation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Confidence matches dedicated calculation<br>';

echo
    '============================================<br>';


$immediateGain =
    13.5;

$futureGain =
    6.0;


$expectedConfidence =
    $intelligence
        ->calculateTimingConfidence(
            $immediateGain,
            $futureGain
        );


$decision =
    $intelligence
        ->createDecision(
            $immediateGain,
            $futureGain
        );


wildcardTimingDecisionConfidenceCheck(
    'Decision confidence is supplied by calculateTimingConfidence',
    abs(
        $decision
            ->getConfidence()
        -
        $expectedConfidence
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario G: Common confidence contract
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Common confidence contract<br>';

echo
    '============================================<br>';


wildcardTimingDecisionConfidenceCheck(
    'Integrated confidence remains within 0.0 and 1.0',
    $decision
        ->getConfidence()
        >=
        0.0
    &&
    $decision
        ->getConfidence()
        <=
        1.0
);


echo
    '<br>';


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'TEST SUMMARY<br>';

echo
    '============================================<br>';

echo
    'Passed: '
    . $passed
    . '<br>';

echo
    'Failed: '
    . $failed
    . '<br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}