<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardProjectionConfidenceCheck(
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
    'Wildcard Timing Projection Confidence Integration Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: No projection confidence supplied
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: No projection confidence supplied<br>';

echo
    '============================================<br>';


$timingOnly =
    $intelligence
        ->createDecision(
            14.0,
            7.0
        );


wildcardProjectionConfidenceCheck(
    'Existing two-argument decision remains supported',
    $timingOnly
        instanceof
        ChipDecision
);


wildcardProjectionConfidenceCheck(
    'Without projection confidence the timing confidence is preserved',
    abs(
        $timingOnly
            ->getConfidence()
        -
        0.7
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


$projectionLimited =
    $intelligence
        ->createDecision(
            14.0,
            7.0,
            0.4
        );


wildcardProjectionConfidenceCheck(
    'Lower projection confidence limits decision confidence',
    abs(
        $projectionLimited
            ->getConfidence()
        -
        0.4
    ) < 0.0001
);


wildcardProjectionConfidenceCheck(
    'Projection confidence does not change the recommendation',
    $projectionLimited
        ->getRecommendation()
        ===
        'Use'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Projection confidence is higher
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Projection confidence is higher<br>';

echo
    '============================================<br>';


$timingLimited =
    $intelligence
        ->createDecision(
            14.0,
            7.0,
            0.9
        );


wildcardProjectionConfidenceCheck(
    'Higher projection confidence cannot exceed timing confidence',
    abs(
        $timingLimited
            ->getConfidence()
        -
        0.7
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Equal confidence evidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Equal confidence evidence<br>';

echo
    '============================================<br>';


$equalConfidence =
    $intelligence
        ->createDecision(
            15.0,
            7.0,
            0.8
        );


wildcardProjectionConfidenceCheck(
    'Equal timing and projection confidence is preserved',
    abs(
        $equalConfidence
            ->getConfidence()
        -
        0.8
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Zero projection confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Zero projection confidence<br>';

echo
    '============================================<br>';


$zeroProjection =
    $intelligence
        ->createDecision(
            14.0,
            7.0,
            0.0
        );


wildcardProjectionConfidenceCheck(
    'Zero projection confidence produces zero decision confidence',
    $zeroProjection
        ->getConfidence()
        ===
        0.0
);


wildcardProjectionConfidenceCheck(
    'Zero projection confidence does not rewrite recommendation policy',
    $zeroProjection
        ->getRecommendation()
        ===
        'Use'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario F: Waiting recommendation also combines confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Waiting recommendation also combines confidence<br>';

echo
    '============================================<br>';


$waitDecision =
    $intelligence
        ->createDecision(
            4.0,
            9.0,
            0.3
        );


wildcardProjectionConfidenceCheck(
    'Hold decision combines timing and projection confidence',
    abs(
        $waitDecision
            ->getConfidence()
        -
        0.3
    ) < 0.0001
);


wildcardProjectionConfidenceCheck(
    'Confidence integration preserves Hold recommendation',
    $waitDecision
        ->getRecommendation()
        ===
        'Hold'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario G: Common decision contract remains stable
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Common decision contract remains stable<br>';

echo
    '============================================<br>';


$decisionArray =
    $projectionLimited
        ->toArray();


wildcardProjectionConfidenceCheck(
    'Projection-aware decision remains a common ChipDecision',
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


wildcardProjectionConfidenceCheck(
    'Projection-aware decision still identifies Wildcard',
    (
        $decisionArray[
            'chip'
        ]
        ??
        null
    )
        ===
        'Wildcard'
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