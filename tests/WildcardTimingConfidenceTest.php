<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardTimingConfidenceCheck(
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
    'Wildcard Timing Confidence Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: No timing separation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: No timing separation<br>';

echo
    '============================================<br>';


$noSeparation =
    $intelligence
        ->calculateTimingConfidence(
            10.0,
            10.0
        );


wildcardTimingConfidenceCheck(
    'Equal Wildcard value produces zero timing confidence',
    $noSeparation
        ===
        0.0
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Small timing separation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Small timing separation<br>';

echo
    '============================================<br>';


$smallSeparation =
    $intelligence
        ->calculateTimingConfidence(
            10.0,
            7.5
        );


wildcardTimingConfidenceCheck(
    '2.5 point separation produces 0.25 confidence',
    abs(
        $smallSeparation
        -
        0.25
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Medium timing separation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Medium timing separation<br>';

echo
    '============================================<br>';


$mediumSeparation =
    $intelligence
        ->calculateTimingConfidence(
            10.0,
            5.0
        );


wildcardTimingConfidenceCheck(
    '5 point separation produces 0.50 confidence',
    abs(
        $mediumSeparation
        -
        0.5
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Strong timing separation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Strong timing separation<br>';

echo
    '============================================<br>';


$strongSeparation =
    $intelligence
        ->calculateTimingConfidence(
            10.0,
            2.5
        );


wildcardTimingConfidenceCheck(
    '7.5 point separation produces 0.75 confidence',
    abs(
        $strongSeparation
        -
        0.75
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Maximum timing confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Maximum timing confidence<br>';

echo
    '============================================<br>';


$maximumSeparation =
    $intelligence
        ->calculateTimingConfidence(
            10.0,
            0.0
        );


wildcardTimingConfidenceCheck(
    '10 point separation produces maximum confidence',
    $maximumSeparation
        ===
        1.0
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario F: Confidence is capped
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Confidence is capped<br>';

echo
    '============================================<br>';


$cappedConfidence =
    $intelligence
        ->calculateTimingConfidence(
            20.0,
            0.0
        );


wildcardTimingConfidenceCheck(
    'Separation above 10 points remains capped at 1.0',
    $cappedConfidence
        ===
        1.0
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario G: Direction does not affect confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Direction does not affect confidence<br>';

echo
    '============================================<br>';


$waitingAdvantage =
    $intelligence
        ->calculateTimingConfidence(
            5.0,
            10.0
        );


wildcardTimingConfidenceCheck(
    'Five point advantage for waiting also produces 0.50 confidence',
    abs(
        $waitingAdvantage
        -
        0.5
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario H: Negative gains remain valid
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario H: Negative gains remain valid<br>';

echo
    '============================================<br>';


$negativeGain =
    $intelligence
        ->calculateTimingConfidence(
            -3.0,
            2.0
        );


wildcardTimingConfidenceCheck(
    'Negative and positive gains use absolute timing separation',
    abs(
        $negativeGain
        -
        0.5
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario I: Symmetry
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario I: Symmetry<br>';

echo
    '============================================<br>';


$forward =
    $intelligence
        ->calculateTimingConfidence(
            12.0,
            4.0
        );


$reverse =
    $intelligence
        ->calculateTimingConfidence(
            4.0,
            12.0
        );


wildcardTimingConfidenceCheck(
    'Reversing timing values does not change confidence',
    abs(
        $forward
        -
        $reverse
    ) < 0.0001
);


wildcardTimingConfidenceCheck(
    'Eight point separation produces 0.80 confidence',
    abs(
        $forward
        -
        0.8
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario J: Deterministic repeatability
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario J: Deterministic repeatability<br>';

echo
    '============================================<br>';


$first =
    $intelligence
        ->calculateTimingConfidence(
            9.25,
            3.75
        );


$second =
    $intelligence
        ->calculateTimingConfidence(
            9.25,
            3.75
        );


wildcardTimingConfidenceCheck(
    'Repeated confidence calculation returns identical value',
    $first
        ===
        $second
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario K: Confidence contract
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario K: Confidence contract<br>';

echo
    '============================================<br>';


wildcardTimingConfidenceCheck(
    'Calculated confidence remains within common confidence bounds',
    $first
        >=
        0.0
    &&
    $first
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