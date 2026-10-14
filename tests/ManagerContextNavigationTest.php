<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Manager Context Navigation Test<br>";
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

function managerNavigationTest(
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
 * SIDEBAR SOURCE
 * ============================================================
 */

$sidebarPath =
    __DIR__
    . '/../public/includes/sidebar.php';


$sidebarSource =
    is_file(
        $sidebarPath
    )
        ? file_get_contents(
            $sidebarPath
        )
        : false;


$sidebarSource =
    is_string(
        $sidebarSource
    )
        ? $sidebarSource
        : '';


/*
 * ============================================================
 * A. CLEAN SIDEBAR NAVIGATION BOUNDARY
 * ============================================================
 */

echo "============================================<br>";
echo "A. Clean Sidebar Navigation Boundary<br>";
echo "============================================<br>";

managerNavigationTest(
    'Sidebar does not embed manager entry IDs in navigation',
    !str_contains($sidebarSource, 'entry_id')
);

managerNavigationTest(
    'Sidebar does not independently resolve manager context',
    !str_contains($sidebarSource, 'ManagerContextService')
);

managerNavigationTest(
    'Sidebar does not use a query-string URL builder',
    !str_contains($sidebarSource, 'buildUrl')
);


echo "<br>";


/*
 * ============================================================
 * B. RENDER WITH MANAGER CONTEXT
 * ============================================================
 */

echo "============================================<br>";
echo "B. Render With Manager Context<br>";
echo "============================================<br>";


$activeNav =
    'dashboard';


$activeManagerEntryId =
    2702264;


ob_start();


require $sidebarPath;


$managerHtml =
    ob_get_clean();


managerNavigationTest(
    'Dashboard navigation remains clean with manager context',
    str_contains(
        $managerHtml,
        'href="index.php"'
    )
);


managerNavigationTest(
    'Squad Intelligence navigation remains clean with manager context',
    str_contains(
        $managerHtml,
        'href="squad.php"'
    )
);


managerNavigationTest(
    'Gameweek Intelligence navigation remains clean with manager context',
    str_contains(
        $managerHtml,
        'href="gameweek.php"'
    )
);


managerNavigationTest(
    'Chip Intelligence navigation remains clean with manager context',
    str_contains(
        $managerHtml,
        'href="chips.php"'
    )
);


managerNavigationTest(
    'Dashboard health link retains its fragment with manager context',
    str_contains(
        $managerHtml,
        'href="index.php#data-health-title"'
    )
);


managerNavigationTest(
    'Manager entry ID does not appear in rendered sidebar links',
    !str_contains($managerHtml, 'entry_id=2702264')
);


echo "<br>";


/*
 * ============================================================
 * C. RENDER WITHOUT MANAGER CONTEXT
 * ============================================================
 */

echo "============================================<br>";
echo "C. Render Without Manager Context<br>";
echo "============================================<br>";


$activeManagerEntryId =
    null;


ob_start();


require $sidebarPath;


$anonymousHtml =
    ob_get_clean();


managerNavigationTest(
    'Dashboard navigation remains clean without manager context',
    str_contains(
        $anonymousHtml,
        'href="index.php"'
    )
);


managerNavigationTest(
    'Squad Intelligence navigation remains clean without manager context',
    str_contains(
        $anonymousHtml,
        'href="squad.php"'
    )
);


managerNavigationTest(
    'Gameweek Intelligence navigation remains clean without manager context',
    str_contains(
        $anonymousHtml,
        'href="gameweek.php"'
    )
);


managerNavigationTest(
    'Chip Intelligence navigation remains clean without manager context',
    str_contains(
        $anonymousHtml,
        'href="chips.php"'
    )
);


managerNavigationTest(
    'Dashboard health link retains its fragment without manager context',
    str_contains(
        $anonymousHtml,
        'href="index.php#data-health-title"'
    )
);


managerNavigationTest(
    'Sidebar markup is identical with or without active manager context',
    $managerHtml === $anonymousHtml
);


echo "<br>";


/*
 * ============================================================
 * D. EXTERNAL NAVIGATION
 * ============================================================
 */

echo "============================================<br>";
echo "D. External Navigation<br>";
echo "============================================<br>";


managerNavigationTest(
    'External Ko-fi URL is not modified by manager context',
    str_contains(
        $managerHtml,
        'href="https://ko-fi.com/fplintelligence"'
    )
);


managerNavigationTest(
    'Manager entry ID is never appended to the external Ko-fi URL',
    !str_contains(
        $managerHtml,
        'ko-fi.com/fplintelligence?entry_id='
    )
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Summary<br>";
echo "============================================<br>";


echo "Assertions passed: {$passed}<br>";
echo "Assertions failed: {$failed}<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}