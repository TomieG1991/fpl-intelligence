<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * LEGACY TEAM PERFORMANCE ADJUSTED REMOVAL TEST
 * ============================================================
 *
 * Release-hardening boundary:
 *
 * TeamPerformanceAdjusted was superseded by the separated
 * TeamPerformance, TeamStrengthModel and
 * OppositionAdjustedPerformance architecture.
 *
 * The legacy implementation must not remain available.
 */


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed = 0;
$failed = 0;


function testResult(
    string $name,
    bool $condition
): void {

    global $passed;
    global $failed;

    if ($condition) {

        echo "PASS: {$name}<br>";
        $passed++;

    } else {

        echo "FAIL: {$name}<br>";
        $failed++;
    }
}


/*
 * ============================================================
 * MODERN ARCHITECTURE
 * ============================================================
 */

testResult(
    'TeamPerformance remains available',
    class_exists('TeamPerformance')
);


testResult(
    'TeamStrengthModel remains available',
    class_exists('TeamStrengthModel')
);


testResult(
    'OppositionAdjustedPerformance remains available',
    class_exists('OppositionAdjustedPerformance')
);


/*
 * ============================================================
 * LEGACY IMPLEMENTATION
 * ============================================================
 */

$legacyFile =
    __DIR__
    . '/../classes/TeamPerformanceAdjusted.php';


testResult(
    'Legacy TeamPerformanceAdjusted implementation has been removed',
    !file_exists($legacyFile)
);


testResult(
    'Legacy TeamPerformanceAdjusted class is no longer available',
    !class_exists('TeamPerformanceAdjusted')
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";

echo "============================================<br>";

echo "Legacy Team Performance Adjusted Removal Test<br>";

echo "============================================<br>";

echo "Passed: {$passed}<br>";

echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}