<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardTimingComparisonEdgeCheck(
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
    'Wildcard Timing Comparison Edge Cases Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: Immediate gain is negative
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Immediate gain is negative<br>';

echo
    '============================================<br>';


$negativeNow =
    $intelligence
        ->compareTiming(
            -3.0,
            5.0
        );


wildcardTimingComparisonEdgeCheck(
    'Negative immediate gain is preserved',
    (
        $negativeNow[
            'immediate_projected_gain'
        ]
        ??
        null
    )
        ===
        -3.0
);


wildcardTimingComparisonEdgeCheck(
    'Future positive gain is preserved',
    (
        $negativeNow[
            'future_projected_gain'
        ]
        ??
        null
    )
        ===
        5.0
);


wildcardTimingComparisonEdgeCheck(
    'Negative immediate versus positive future produces correct advantage',
    (
        $negativeNow[
            'timing_advantage'
        ]
        ??
        null
    )
        ===
        -8.0
);


wildcardTimingComparisonEdgeCheck(
    'Negative immediate versus positive future identifies Wait',
    (
        $negativeNow[
            'better_timing'
        ]
        ??
        null
    )
        ===
        'Wait'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Future gain is negative
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Future gain is negative<br>';

echo
    '============================================<br>';


$negativeFuture =
    $intelligence
        ->compareTiming(
            4.0,
            -2.0
        );


wildcardTimingComparisonEdgeCheck(
    'Negative future gain is preserved',
    (
        $negativeFuture[
            'future_projected_gain'
        ]
        ??
        null
    )
        ===
        -2.0
);


wildcardTimingComparisonEdgeCheck(
    'Positive immediate versus negative future produces correct advantage',
    (
        $negativeFuture[
            'timing_advantage'
        ]
        ??
        null
    )
        ===
        6.0
);


wildcardTimingComparisonEdgeCheck(
    'Positive immediate versus negative future identifies Now',
    (
        $negativeFuture[
            'better_timing'
        ]
        ??
        null
    )
        ===
        'Now'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Both gains are negative
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Both gains are negative<br>';

echo
    '============================================<br>';


$bothNegative =
    $intelligence
        ->compareTiming(
            -2.0,
            -6.0
        );


wildcardTimingComparisonEdgeCheck(
    'Two negative gains still produce correct timing advantage',
    (
        $bothNegative[
            'timing_advantage'
        ]
        ??
        null
    )
        ===
        4.0
);


wildcardTimingComparisonEdgeCheck(
    'Less negative immediate value identifies Now comparatively',
    (
        $bothNegative[
            'better_timing'
        ]
        ??
        null
    )
        ===
        'Now'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Equal negative gains
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Equal negative gains<br>';

echo
    '============================================<br>';


$equalNegative =
    $intelligence
        ->compareTiming(
            -4.0,
            -4.0
        );


wildcardTimingComparisonEdgeCheck(
    'Equal negative gains produce zero timing advantage',
    (
        $equalNegative[
            'timing_advantage'
        ]
        ??
        null
    )
        ===
        0.0
);


wildcardTimingComparisonEdgeCheck(
    'Equal negative gains identify Neutral',
    (
        $equalNegative[
            'better_timing'
        ]
        ??
        null
    )
        ===
        'Neutral'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Fractional precision
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Fractional precision<br>';

echo
    '============================================<br>';


$fractional =
    $intelligence
        ->compareTiming(
            8.75,
            6.25
        );


wildcardTimingComparisonEdgeCheck(
    'Fractional timing advantage is calculated correctly',
    abs(
        (
            $fractional[
                'timing_advantage'
            ]
            ??
            0
        )
        -
        2.5
    ) < 0.0001
);


wildcardTimingComparisonEdgeCheck(
    'Fractional positive advantage identifies Now',
    (
        $fractional[
            'better_timing'
        ]
        ??
        null
    )
        ===
        'Now'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario F: Deterministic repeatability
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Deterministic repeatability<br>';

echo
    '============================================<br>';


$first =
    $intelligence
        ->compareTiming(
            9.5,
            7.25
        );


$second =
    $intelligence
        ->compareTiming(
            9.5,
            7.25
        );


wildcardTimingComparisonEdgeCheck(
    'Repeated timing comparison returns identical output',
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