<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardTimingDecisionEdgeCheck(
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
    'Wildcard Timing Decision Edge Cases Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: Exact Use thresholds
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Exact Use thresholds<br>';

echo
    '============================================<br>';


$exactThreshold =
    $intelligence
        ->createDecision(
            10.0,
            5.0
        );


wildcardTimingDecisionEdgeCheck(
    'Exact immediate gain and timing advantage thresholds recommend Use',
    $exactThreshold
        ->getRecommendation()
        ===
        'Use'
);


wildcardTimingDecisionEdgeCheck(
    'Exact threshold decision identifies Wildcard',
    $exactThreshold
        ->getChip()
        ===
        'Wildcard'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Immediate gain just below Use threshold
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Immediate gain just below Use threshold<br>';

echo
    '============================================<br>';


$belowImmediateThreshold =
    $intelligence
        ->createDecision(
            9.99,
            4.0
        );


wildcardTimingDecisionEdgeCheck(
    'Immediate gain below 10 does not recommend Use',
    $belowImmediateThreshold
        ->getRecommendation()
        ===
        'Consider'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Timing advantage just below Use threshold
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Timing advantage just below Use threshold<br>';

echo
    '============================================<br>';


$belowTimingThreshold =
    $intelligence
        ->createDecision(
            10.0,
            5.01
        );


wildcardTimingDecisionEdgeCheck(
    'Timing advantage below 5 does not recommend Use',
    $belowTimingThreshold
        ->getRecommendation()
        ===
        'Consider'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Equal immediate and future value
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Equal immediate and future value<br>';

echo
    '============================================<br>';


$equalTiming =
    $intelligence
        ->createDecision(
            8.0,
            8.0
        );


wildcardTimingDecisionEdgeCheck(
    'Equal positive immediate and future value recommends Consider',
    $equalTiming
        ->getRecommendation()
        ===
        'Consider'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Future value is only slightly greater
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Future value is only slightly greater<br>';

echo
    '============================================<br>';


$slightlyBetterFuture =
    $intelligence
        ->createDecision(
            8.0,
            8.01
        );


wildcardTimingDecisionEdgeCheck(
    'Any greater future projected value recommends Hold',
    $slightlyBetterFuture
        ->getRecommendation()
        ===
        'Hold'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario F: Tiny positive immediate value
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Tiny positive immediate value<br>';

echo
    '============================================<br>';


$tinyPositive =
    $intelligence
        ->createDecision(
            0.01,
            0.0
        );


wildcardTimingDecisionEdgeCheck(
    'Any positive immediate gain can reach Consider',
    $tinyPositive
        ->getRecommendation()
        ===
        'Consider'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario G: Zero immediate value
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Zero immediate value<br>';

echo
    '============================================<br>';


$zeroImmediate =
    $intelligence
        ->createDecision(
            0.0,
            0.0
        );


wildcardTimingDecisionEdgeCheck(
    'Zero immediate gain recommends Hold',
    $zeroImmediate
        ->getRecommendation()
        ===
        'Hold'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario H: Negative immediate value
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario H: Negative immediate value<br>';

echo
    '============================================<br>';


$negativeImmediate =
    $intelligence
        ->createDecision(
            -1.0,
            -5.0
        );


wildcardTimingDecisionEdgeCheck(
    'Negative immediate gain recommends Hold even when future is worse',
    $negativeImmediate
        ->getRecommendation()
        ===
        'Hold'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario I: Negative future value
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario I: Negative future value<br>';

echo
    '============================================<br>';


$negativeFuture =
    $intelligence
        ->createDecision(
            10.0,
            -1.0
        );


wildcardTimingDecisionEdgeCheck(
    'Strong immediate gain with negative future value recommends Use',
    $negativeFuture
        ->getRecommendation()
        ===
        'Use'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario J: Large immediate gain but future is better
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario J: Large immediate gain but future is better<br>';

echo
    '============================================<br>';


$largeButWait =
    $intelligence
        ->createDecision(
            20.0,
            21.0
        );


wildcardTimingDecisionEdgeCheck(
    'Large immediate gain still recommends Hold when waiting is better',
    $largeButWait
        ->getRecommendation()
        ===
        'Hold'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario K: Decision contract remains stable
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario K: Decision contract remains stable<br>';

echo
    '============================================<br>';


$decisionArray =
    $exactThreshold
        ->toArray();


wildcardTimingDecisionEdgeCheck(
    'Edge-case decision exposes exactly the common decision fields',
    array_keys(
        $decisionArray
    )
        ===
        [
            'chip',
            'recommendation',
            'confidence',
            'explanation'
        ]
);


wildcardTimingDecisionEdgeCheck(
    'Edge-case decision confidence remains within common contract',
    $exactThreshold
        ->getConfidence()
        >=
        0.0
    &&
    $exactThreshold
        ->getConfidence()
        <=
        1.0
);


wildcardTimingDecisionEdgeCheck(
    'Edge-case decision explanation remains non-empty',
    trim(
        $exactThreshold
            ->getExplanation()
    )
        !==
        ''
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