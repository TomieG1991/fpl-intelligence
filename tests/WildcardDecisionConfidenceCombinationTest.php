<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardConfidenceCombinationCheck(
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
    'Wildcard Decision Confidence Combination Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: Timing confidence is lower
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Timing confidence is lower<br>';

echo
    '============================================<br>';


$timingLower =
    $intelligence
        ->combineConfidence(
            0.8,
            0.9
        );


wildcardConfidenceCombinationCheck(
    'Lower timing confidence limits overall confidence',
    abs(
        $timingLower
        -
        0.8
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Projection confidence is lower
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Projection confidence is lower<br>';

echo
    '============================================<br>';


$projectionLower =
    $intelligence
        ->combineConfidence(
            0.8,
            0.4
        );


wildcardConfidenceCombinationCheck(
    'Lower projection confidence limits overall confidence',
    abs(
        $projectionLower
        -
        0.4
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Equal confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Equal confidence<br>';

echo
    '============================================<br>';


$equal =
    $intelligence
        ->combineConfidence(
            0.65,
            0.65
        );


wildcardConfidenceCombinationCheck(
    'Equal evidence confidence is preserved',
    abs(
        $equal
        -
        0.65
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Maximum confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Maximum confidence<br>';

echo
    '============================================<br>';


$maximum =
    $intelligence
        ->combineConfidence(
            1.0,
            1.0
        );


wildcardConfidenceCombinationCheck(
    'Two maximum confidence inputs produce maximum overall confidence',
    $maximum
        ===
        1.0
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Zero timing confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Zero timing confidence<br>';

echo
    '============================================<br>';


$zeroTiming =
    $intelligence
        ->combineConfidence(
            0.0,
            0.9
        );


wildcardConfidenceCombinationCheck(
    'Zero timing confidence produces zero overall confidence',
    $zeroTiming
        ===
        0.0
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario F: Zero projection confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Zero projection confidence<br>';

echo
    '============================================<br>';


$zeroProjection =
    $intelligence
        ->combineConfidence(
            0.9,
            0.0
        );


wildcardConfidenceCombinationCheck(
    'Zero projection confidence produces zero overall confidence',
    $zeroProjection
        ===
        0.0
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario G: Result is symmetric
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Result is symmetric<br>';

echo
    '============================================<br>';


$forward =
    $intelligence
        ->combineConfidence(
            0.75,
            0.45
        );


$reverse =
    $intelligence
        ->combineConfidence(
            0.45,
            0.75
        );


wildcardConfidenceCombinationCheck(
    'Confidence combination is symmetric',
    abs(
        $forward
        -
        $reverse
    ) < 0.0001
);


wildcardConfidenceCombinationCheck(
    'Symmetric combination preserves the weaker confidence',
    abs(
        $forward
        -
        0.45
    ) < 0.0001
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