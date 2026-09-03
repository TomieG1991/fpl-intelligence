<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardTimingComparisonCheck(
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
    'Wildcard Timing Comparison Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: Using Wildcard now has greater value
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Using Wildcard now has greater value<br>';

echo
    '============================================<br>';


$now =
    $intelligence
        ->compareTiming(
            12.0,
            7.0
        );


wildcardTimingComparisonCheck(
    'Timing comparison returns an array',
    is_array(
        $now
    )
);


wildcardTimingComparisonCheck(
    'Immediate Wildcard gain is preserved',
    abs(
        (
            $now[
                'immediate_projected_gain'
            ]
            ??
            0
        )
        -
        12.0
    ) < 0.0001
);


wildcardTimingComparisonCheck(
    'Future Wildcard gain is preserved',
    abs(
        (
            $now[
                'future_projected_gain'
            ]
            ??
            0
        )
        -
        7.0
    ) < 0.0001
);


wildcardTimingComparisonCheck(
    'Positive timing advantage is calculated correctly',
    abs(
        (
            $now[
                'timing_advantage'
            ]
            ??
            0
        )
        -
        5.0
    ) < 0.0001
);


wildcardTimingComparisonCheck(
    'Greater immediate value identifies Now',
    (
        $now[
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
 * Scenario B: Waiting has greater value
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Waiting has greater value<br>';

echo
    '============================================<br>';


$wait =
    $intelligence
        ->compareTiming(
            5.0,
            11.0
        );


wildcardTimingComparisonCheck(
    'Negative timing advantage is preserved',
    abs(
        (
            $wait[
                'timing_advantage'
            ]
            ??
            0
        )
        -
        (-6.0)
    ) < 0.0001
);


wildcardTimingComparisonCheck(
    'Greater future value identifies Wait',
    (
        $wait[
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
 * Scenario C: Timing value is equal
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Timing value is equal<br>';

echo
    '============================================<br>';


$neutral =
    $intelligence
        ->compareTiming(
            8.0,
            8.0
        );


wildcardTimingComparisonCheck(
    'Equal timing value produces zero advantage',
    abs(
        (
            $neutral[
                'timing_advantage'
            ]
            ??
            -999
        )
    ) < 0.0001
);


wildcardTimingComparisonCheck(
    'Equal timing value identifies Neutral',
    (
        $neutral[
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
 * Scenario D: No Wildcard value now or later
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: No Wildcard value now or later<br>';

echo
    '============================================<br>';


$zero =
    $intelligence
        ->compareTiming(
            0.0,
            0.0
        );


wildcardTimingComparisonCheck(
    'Zero versus zero produces zero timing advantage',
    (
        $zero[
            'timing_advantage'
        ]
        ??
        null
    )
        ===
        0.0
);


wildcardTimingComparisonCheck(
    'Zero versus zero identifies Neutral',
    (
        $zero[
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
 * Scenario E: Result contract
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Result contract<br>';

echo
    '============================================<br>';


wildcardTimingComparisonCheck(
    'Result identifies Wildcard timing comparison',
    (
        $now[
            'analysis'
        ]
        ??
        null
    )
        ===
        'Wildcard Timing Comparison'
);


wildcardTimingComparisonCheck(
    'Result exposes all required timing fields',
    array_key_exists(
        'immediate_projected_gain',
        $now
    )
    &&
    array_key_exists(
        'future_projected_gain',
        $now
    )
    &&
    array_key_exists(
        'timing_advantage',
        $now
    )
    &&
    array_key_exists(
        'better_timing',
        $now
    )
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