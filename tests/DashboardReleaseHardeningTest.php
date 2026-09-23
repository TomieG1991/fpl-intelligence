<?php

echo "============================================<br>";
echo "Dashboard Release Hardening Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function dashboardReleaseHardeningCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


/*
 * ============================================================
 * DASHBOARD SOURCE
 * ============================================================
 */

$dashboardPath =
    __DIR__
    . '/../public/index.php';


echo "Dashboard Path: "
    . htmlspecialchars(
        $dashboardPath,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


$dashboardExists =
    is_file(
        $dashboardPath
    );


$dashboardSource =
    $dashboardExists
        ? file_get_contents(
            $dashboardPath
        )
        : false;


$dashboardReadable =
    is_string(
        $dashboardSource
    );


$dashboardSource =
    $dashboardReadable
        ? $dashboardSource
        : '';


/*
 * ============================================================
 * SCENARIO A
 * DASHBOARD SOURCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Dashboard Source<br>";
echo "============================================<br>";


dashboardReleaseHardeningCheck(
    'Dashboard public page exists',
    $dashboardExists
);


dashboardReleaseHardeningCheck(
    'Dashboard public page source can be read',
    $dashboardReadable
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * TEMPORARY DEVELOPMENT MARKERS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Temporary Development Markers<br>";
echo "============================================<br>";


dashboardReleaseHardeningCheck(
    'Dashboard contains no temporary testhere marker',
    strpos(
        $dashboardSource,
        'testhere'
    ) === false
);


dashboardReleaseHardeningCheck(
    'Dashboard contains no Calibration Diagnostic presentation',
    strpos(
        $dashboardSource,
        'Calibration Diagnostic'
    ) === false
);


dashboardReleaseHardeningCheck(
    'Dashboard contains no positional strength diagnostic presentation',
    strpos(
        $dashboardSource,
        'strength-diagnostic'
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * TEMPORARY DIAGNOSTIC CALCULATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Temporary Diagnostic Calculation<br>";
echo "============================================<br>";


dashboardReleaseHardeningCheck(
    'Dashboard does not build a temporary positional strength collection',
    strpos(
        $dashboardSource,
        '$topStrengthByPosition'
    ) === false
);


dashboardReleaseHardeningCheck(
    'Dashboard contains no positional strength diagnostic source heading',
    strpos(
        $dashboardSource,
        'POSITIONAL STRENGTH DIAGNOSTIC'
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * PRODUCT PLAYER RANKING REMAINS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Product Player Ranking Remains<br>";
echo "============================================<br>";


dashboardReleaseHardeningCheck(
    'Dashboard retains PlayerRanking',
    strpos(
        $dashboardSource,
        'new PlayerRanking'
    ) !== false
);


dashboardReleaseHardeningCheck(
    'Dashboard retains the top-player ranking boundary',
    strpos(
        $dashboardSource,
        '->getTopPlayers('
    ) !== false
);


dashboardReleaseHardeningCheck(
    'Dashboard retains player intelligence presentation',
    strpos(
        $dashboardSource,
        'Player Intelligence'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * STALE DEVELOPMENT PLACEHOLDERS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Stale Development Placeholders<br>";
echo "============================================<br>";


dashboardReleaseHardeningCheck(
    'Dashboard contains no Next phase development badge',
    strpos(
        $dashboardSource,
        'Next phase'
    ) === false
);


dashboardReleaseHardeningCheck(
    'Dashboard does not describe fixture intelligence as future functionality',
    strpos(
        $dashboardSource,
        'will be ranked here'
    ) === false
);


dashboardReleaseHardeningCheck(
    'Dashboard does not describe value intelligence as future functionality',
    strpos(
        $dashboardSource,
        'will highlight'
    ) === false
);


dashboardReleaseHardeningCheck(
    'Dashboard does not describe transfer intelligence as future functionality',
    strpos(
        $dashboardSource,
        'appear here'
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * DEVELOPMENT STATUS PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Development Status Presentation<br>";
echo "============================================<br>";


dashboardReleaseHardeningCheck(
    'Dashboard contains no development status source heading',
    strpos(
        $dashboardSource,
        'DEVELOPMENT STATUS'
    ) === false
);


dashboardReleaseHardeningCheck(
    'Dashboard contains no redundant System Status presentation',
    strpos(
        $dashboardSource,
        'System Status'
    ) === false
);


dashboardReleaseHardeningCheck(
    'Dashboard does not hard-code Team Models as ready',
    strpos(
        $dashboardSource,
        'Team Models'
    ) === false
);


dashboardReleaseHardeningCheck(
    'Dashboard does not hard-code Player Models as ready',
    strpos(
        $dashboardSource,
        'Player Models'
    ) === false
);


dashboardReleaseHardeningCheck(
    'Dashboard does not hard-code Fixture Intelligence status as ready',
    !preg_match(
        '/<span>\s*Fixture Intelligence\s*<\/span>\s*'
        . '<strong class="status-success">\s*Ready\s*<\/strong>/s',
        $dashboardSource
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * DASHBOARD GATEWAY PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Dashboard Gateway Presentation<br>";
echo "============================================<br>";


$dashboardCssPath =
    __DIR__
    . '/../public/assets/css/app.css';


$dashboardCss =
    is_file(
        $dashboardCssPath
    )
        ? file_get_contents(
            $dashboardCssPath
        )
        : false;


$dashboardCss =
    is_string(
        $dashboardCss
    )
        ? $dashboardCss
        : '';


dashboardReleaseHardeningCheck(
    'Dashboard gateway links use the shared button-link presentation hook',
    substr_count(
        $dashboardSource,
        'class="button-link"'
    )
    === 3
);


dashboardReleaseHardeningCheck(
    'Shared button-link presentation is defined in application CSS',
    strpos(
        $dashboardCss,
        '.button-link'
    )
    !== false
);


dashboardReleaseHardeningCheck(
    'Shared button-link presentation includes keyboard focus styling',
    preg_match(
        '/\.button-link:focus-visible\s*\{/',
        $dashboardCss
    )
    === 1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * ACCESSIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Accessibility<br>";
echo "============================================<br>";


dashboardReleaseHardeningCheck(
    'Dashboard data availability exposes status semantics',
    strpos(
        $dashboardSource,
        '<span class="data-status" role="status">'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Dashboard Release Hardening Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}