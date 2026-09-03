<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardConfidenceCombinationEdgeCheck(
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


function wildcardConfidenceCombinationThrowsInvalidArgument(
    callable $callback
): bool {

    try {

        $callback();

    } catch (
        InvalidArgumentException $exception
    ) {

        return true;

    } catch (
        Throwable $exception
    ) {

        return false;
    }


    return false;
}


echo
    '============================================<br>';

echo
    'Wildcard Decision Confidence Combination Edge Cases Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: Lower confidence boundary
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Lower confidence boundary<br>';

echo
    '============================================<br>';


$lowerBoundary =
    $intelligence
        ->combineConfidence(
            0.0,
            0.0
        );


wildcardConfidenceCombinationEdgeCheck(
    'Zero confidence inputs are valid',
    $lowerBoundary
        ===
        0.0
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Upper confidence boundary
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Upper confidence boundary<br>';

echo
    '============================================<br>';


$upperBoundary =
    $intelligence
        ->combineConfidence(
            1.0,
            1.0
        );


wildcardConfidenceCombinationEdgeCheck(
    'Maximum confidence inputs are valid',
    $upperBoundary
        ===
        1.0
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Invalid timing confidence below zero
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Invalid timing confidence below zero<br>';

echo
    '============================================<br>';


wildcardConfidenceCombinationEdgeCheck(
    'Timing confidence below zero is rejected',
    wildcardConfidenceCombinationThrowsInvalidArgument(
        function () use (
            $intelligence
        ): void {

            $intelligence
                ->combineConfidence(
                    -0.01,
                    0.5
                );
        }
    )
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Invalid timing confidence above one
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Invalid timing confidence above one<br>';

echo
    '============================================<br>';


wildcardConfidenceCombinationEdgeCheck(
    'Timing confidence above one is rejected',
    wildcardConfidenceCombinationThrowsInvalidArgument(
        function () use (
            $intelligence
        ): void {

            $intelligence
                ->combineConfidence(
                    1.01,
                    0.5
                );
        }
    )
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Invalid projection confidence below zero
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Invalid projection confidence below zero<br>';

echo
    '============================================<br>';


wildcardConfidenceCombinationEdgeCheck(
    'Projection confidence below zero is rejected',
    wildcardConfidenceCombinationThrowsInvalidArgument(
        function () use (
            $intelligence
        ): void {

            $intelligence
                ->combineConfidence(
                    0.5,
                    -0.01
                );
        }
    )
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario F: Invalid projection confidence above one
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Invalid projection confidence above one<br>';

echo
    '============================================<br>';


wildcardConfidenceCombinationEdgeCheck(
    'Projection confidence above one is rejected',
    wildcardConfidenceCombinationThrowsInvalidArgument(
        function () use (
            $intelligence
        ): void {

            $intelligence
                ->combineConfidence(
                    0.5,
                    1.01
                );
        }
    )
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario G: Fractional confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Fractional confidence<br>';

echo
    '============================================<br>';


$fractional =
    $intelligence
        ->combineConfidence(
            0.625,
            0.875
        );


wildcardConfidenceCombinationEdgeCheck(
    'Valid fractional confidence is preserved without rounding',
    abs(
        $fractional
        -
        0.625
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario H: Deterministic repeatability
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario H: Deterministic repeatability<br>';

echo
    '============================================<br>';


$first =
    $intelligence
        ->combineConfidence(
            0.73,
            0.61
        );


$second =
    $intelligence
        ->combineConfidence(
            0.73,
            0.61
        );


wildcardConfidenceCombinationEdgeCheck(
    'Repeated confidence combination returns identical value',
    $first
        ===
        $second
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