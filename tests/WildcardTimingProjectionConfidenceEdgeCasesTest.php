<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardProjectionConfidenceEdgeCheck(
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


function wildcardProjectionConfidenceThrowsInvalidArgument(
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
    'Wildcard Timing Projection Confidence Edge Cases Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: Explicit null remains valid
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Explicit null remains valid<br>';

echo
    '============================================<br>';


$nullConfidence =
    $intelligence
        ->createDecision(
            14.0,
            7.0,
            null
        );


wildcardProjectionConfidenceEdgeCheck(
    'Explicit null projection confidence remains supported',
    $nullConfidence
        instanceof
        ChipDecision
);


wildcardProjectionConfidenceEdgeCheck(
    'Explicit null preserves timing-only confidence',
    abs(
        $nullConfidence
            ->getConfidence()
        -
        0.7
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Lower confidence boundary
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Lower confidence boundary<br>';

echo
    '============================================<br>';


$zeroConfidence =
    $intelligence
        ->createDecision(
            14.0,
            7.0,
            0.0
        );


wildcardProjectionConfidenceEdgeCheck(
    'Projection confidence of exactly zero is valid',
    $zeroConfidence
        ->getConfidence()
        ===
        0.0
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Upper confidence boundary
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Upper confidence boundary<br>';

echo
    '============================================<br>';


$maximumConfidence =
    $intelligence
        ->createDecision(
            14.0,
            7.0,
            1.0
        );


wildcardProjectionConfidenceEdgeCheck(
    'Projection confidence of exactly one is valid',
    abs(
        $maximumConfidence
            ->getConfidence()
        -
        0.7
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Projection confidence below zero
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Projection confidence below zero<br>';

echo
    '============================================<br>';


wildcardProjectionConfidenceEdgeCheck(
    'Projection confidence below zero is rejected by decision creation',
    wildcardProjectionConfidenceThrowsInvalidArgument(
        function () use (
            $intelligence
        ): void {

            $intelligence
                ->createDecision(
                    14.0,
                    7.0,
                    -0.01
                );
        }
    )
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Projection confidence above one
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Projection confidence above one<br>';

echo
    '============================================<br>';


wildcardProjectionConfidenceEdgeCheck(
    'Projection confidence above one is rejected by decision creation',
    wildcardProjectionConfidenceThrowsInvalidArgument(
        function () use (
            $intelligence
        ): void {

            $intelligence
                ->createDecision(
                    14.0,
                    7.0,
                    1.01
                );
        }
    )
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario F: Fractional confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Fractional confidence<br>';

echo
    '============================================<br>';


$fractionalConfidence =
    $intelligence
        ->createDecision(
            14.0,
            7.0,
            0.625
        );


wildcardProjectionConfidenceEdgeCheck(
    'Fractional projection confidence is preserved without rounding',
    abs(
        $fractionalConfidence
            ->getConfidence()
        -
        0.625
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario G: Invalid confidence cannot alter recommendation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Confidence remains separate from recommendation<br>';

echo
    '============================================<br>';


$lowConfidenceUse =
    $intelligence
        ->createDecision(
            14.0,
            7.0,
            0.1
        );


wildcardProjectionConfidenceEdgeCheck(
    'Low valid projection confidence does not rewrite Use recommendation',
    $lowConfidenceUse
        ->getRecommendation()
        ===
        'Use'
);


wildcardProjectionConfidenceEdgeCheck(
    'Low valid projection confidence limits decision confidence',
    abs(
        $lowConfidenceUse
            ->getConfidence()
        -
        0.1
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
        ->createDecision(
            13.0,
            6.0,
            0.55
        );


$second =
    $intelligence
        ->createDecision(
            13.0,
            6.0,
            0.55
        );


wildcardProjectionConfidenceEdgeCheck(
    'Repeated projection-aware decision returns identical output',
    $first
        ->toArray()
        ===
        $second
            ->toArray()
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