<?php

/*
 * ============================================================
 * COMMAND CENTRE FINAL DASHBOARD REFINEMENTS TEST
 * ============================================================
 */

echo "============================================<br>";
echo "Command Centre Final Dashboard Refinements Test<br>";
echo "============================================<br><br>";

$passed = 0;
$failed = 0;

/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function commandCentreFinalRefinementsCheck(
    string $description,
    bool $condition
): void {
    global $passed;
    global $failed;

    if ($condition) {
        echo "PASS: "
            . htmlspecialchars($description, ENT_QUOTES, 'UTF-8')
            . "<br>";
        $passed++;
        return;
    }

    echo "FAIL: "
        . htmlspecialchars($description, ENT_QUOTES, 'UTF-8')
        . "<br>";
    $failed++;
}

function commandCentreFinalRefinementsCssRule(
    string $css,
    string $selector
): string {
    $pattern = '~' . preg_quote($selector, '~') . '\s*\{([^}]*)\}~s';
    preg_match_all($pattern, $css, $matches);
    return implode("\n", $matches[1] ?? []);
}

function commandCentreFinalRefinementsNormalise(string $value): string {
    return preg_replace('/\s+/', ' ', trim($value));
}

/*
 * ============================================================
 * DASHBOARD SOURCES
 * ============================================================
 */

$dashboardPath = __DIR__ . '/../public/index.php';
$dashboardCssPath = __DIR__ . '/../public/assets/css/app.css';

$dashboardSource = is_file($dashboardPath)
    ? file_get_contents($dashboardPath)
    : false;
$dashboardCss = is_file($dashboardCssPath)
    ? file_get_contents($dashboardCssPath)
    : false;

/*
 * ============================================================
 * SCENARIO A
 * DASHBOARD SOURCES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Dashboard Sources<br>";
echo "============================================<br>";

commandCentreFinalRefinementsCheck(
    'CSS and Dashboard source files are available',
    is_string($dashboardCss) && is_string($dashboardSource)
);

$dashboardCss = is_string($dashboardCss) ? $dashboardCss : '';
$dashboardSource = is_string($dashboardSource) ? $dashboardSource : '';

echo "<br>";

/*
 * ============================================================
 * SCENARIO B
 * ATTENTION AND OPPORTUNITIES LAYOUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Attention and Opportunities Layout<br>";
echo "============================================<br>";

$attentionGrid = commandCentreFinalRefinementsNormalise(
    commandCentreFinalRefinementsCssRule($dashboardCss, '.command-centre-attention-row')
);
$attentionCards = commandCentreFinalRefinementsNormalise(
    commandCentreFinalRefinementsCssRule($dashboardCss, '.command-centre-attention-row > .dashboard-card')
);

commandCentreFinalRefinementsCheck(
    'Attention grid aligns content at the start',
    preg_match('/align-items\s*:\s*start\s*;/', $attentionGrid) === 1
);

commandCentreFinalRefinementsCheck(
    'Attention and Opportunities cards explicitly avoid stretching',
    preg_match('/align-self\s*:\s*start\s*;/', $attentionCards) === 1
);

echo "<br>";

/*
 * ============================================================
 * SCENARIO C
 * COMPACT DATA HEALTH
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Compact Data Health<br>";
echo "============================================<br>";

$dataHealthCard = commandCentreFinalRefinementsNormalise(
    commandCentreFinalRefinementsCssRule($dashboardCss, '.command-centre-data-health-card')
);
$dataHealthHeader = commandCentreFinalRefinementsNormalise(
    commandCentreFinalRefinementsCssRule($dashboardCss, '.command-centre-data-health-card .card-header')
);

commandCentreFinalRefinementsCheck(
    'Data Health uses compact padding',
    preg_match('/padding\s*:\s*12px\s+16px\s*;/', $dataHealthCard) === 1
);

commandCentreFinalRefinementsCheck(
    'Data Health header spacing is compact',
    preg_match('/margin-bottom\s*:\s*8px\s*;/', $dataHealthHeader) === 1
);

echo "<br>";

/*
 * ============================================================
 * SCENARIO D
 * ADDITIONAL INTELLIGENCE HIERARCHY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Additional Intelligence Hierarchy<br>";
echo "============================================<br>";

commandCentreFinalRefinementsCheck(
    'Legacy Dashboard has Additional Intelligence heading',
    preg_match('/<h2\b[^>]*>\s*Additional Intelligence\s*<\/h2>/s', $dashboardSource) === 1
);

commandCentreFinalRefinementsCheck(
    'Additional Intelligence heading appears inside secondary content',
    preg_match(
        '/class="dashboard-secondary-content"[^>]*>\s*(?:<!--.*?-->\s*)*<header\b[^>]*>.*?<h2\b[^>]*>\s*Additional Intelligence\s*<\/h2>/s',
        $dashboardSource
    ) === 1
);

echo "<br>";

/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Command Centre Final Dashboard Refinements Test Summary<br>";
echo "============================================<br>";
echo "Passed: " . $passed . "<br>";
echo "Failed: " . $failed . "<br><br>";

if ($failed === 0) {
    echo "RESULT: TESTS PASSED ✅<br>";
} else {
    echo "RESULT: TESTS FAILED ❌<br>";
}
