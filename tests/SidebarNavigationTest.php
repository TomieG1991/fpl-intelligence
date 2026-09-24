<?php

echo "============================================<br>";
echo "Sidebar Navigation Test<br>";
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

function sidebarNavigationCheck(
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


echo "Sidebar Path: "
    . htmlspecialchars(
        $sidebarPath,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


$sidebarExists =
    is_file(
        $sidebarPath
    );


$sidebarSource =
    $sidebarExists
        ? file_get_contents(
            $sidebarPath
        )
        : false;


$sidebarReadable =
    is_string(
        $sidebarSource
    );


$sidebarSource =
    $sidebarReadable
        ? $sidebarSource
        : '';


/*
 * ============================================================
 * SCENARIO A
 * SHARED SIDEBAR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Shared Sidebar<br>";
echo "============================================<br>";


sidebarNavigationCheck(
    'Shared sidebar exists',
    $sidebarExists
);


sidebarNavigationCheck(
    'Shared sidebar source can be read',
    $sidebarReadable
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * ACCESSIBLE NAVIGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Accessible Navigation<br>";
echo "============================================<br>";


sidebarNavigationCheck(
    'Main navigation has an accessible label',
    strpos(
        $sidebarSource,
        'aria-label="Main navigation"'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * APPLICATION VERSION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Application Version<br>";
echo "============================================<br>";


sidebarNavigationCheck(
    'Sidebar displays the current stable application version',
    strpos(
        $sidebarSource,
        'v1.2.0'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * EXPECTED PRIMARY DESTINATIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Expected Primary Destinations<br>";
echo "============================================<br>";


$expectedDestinations = [

    'index.php',
    'players.php',
    'compare.php',
    'teams.php',
    'transfers.php',
    'transfer-planner.php',
    'transfer-optimizer.php',
    'squad.php',
    'gameweek.php',
    'wildcard.php',
    'chips.php'
];


foreach (
    $expectedDestinations
    as $destination
) {

    sidebarNavigationCheck(
        'Sidebar contains destination '
            . $destination,
        strpos(
            $sidebarSource,
            'href="'
            . $destination
            . '"'
        ) !== false
    );


    sidebarNavigationCheck(
        'Public destination '
            . $destination
            . ' exists',
        is_file(
            __DIR__
            . '/../public/'
            . $destination
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * PLACEHOLDER NAVIGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Placeholder Navigation<br>";
echo "============================================<br>";


sidebarNavigationCheck(
    'Sidebar contains no placeholder navigation destinations',
    strpos(
        $sidebarSource,
        'href="#"'
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * PHP DESTINATION INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: PHP Destination Integrity<br>";
echo "============================================<br>";


preg_match_all(
    '/href="([^"]+\.php)"/',
    $sidebarSource,
    $destinationMatches
);


$sidebarDestinations =
    array_values(
        array_unique(
            $destinationMatches[
                1
            ]
            ?? []
        )
    );


sidebarNavigationCheck(
    'Sidebar contains public PHP destinations',
    !empty(
        $sidebarDestinations
    )
);


foreach (
    $sidebarDestinations
    as $destination
) {

    sidebarNavigationCheck(
        'Sidebar destination '
            . $destination
            . ' resolves to a public page',
        is_file(
            __DIR__
            . '/../public/'
            . $destination
        )
    );
}


echo "PHP Destinations Checked: "
    . count(
        $sidebarDestinations
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO G
 * PRIMARY NAVIGATION IDENTITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Primary Navigation Identity<br>";
echo "============================================<br>";


$expectedLabels = [

    'Dashboard',
    'Players',
    'Compare',
    'Teams',
    'Fixtures',
    'Transfers',
    'Transfer Planner',
    'Transfer Optimizer',
    'Squad Intelligence',
    'Gameweek Intelligence',
    'Wildcard Intelligence',
    'Chip Intelligence'
];


foreach (
    $expectedLabels
    as $label
) {

    sidebarNavigationCheck(
        'Sidebar contains navigation item '
            . $label,
        strpos(
            $sidebarSource,
            $label
        ) !== false
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * MOBILE NAVIGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Mobile Navigation<br>";
echo "============================================<br>";


sidebarNavigationCheck(
    'Sidebar provides a mobile navigation toggle',
    strpos(
        $sidebarSource,
        'class="mobile-navigation-toggle"'
    ) !== false
);


sidebarNavigationCheck(
    'Mobile navigation toggle identifies its controlled navigation',
    strpos(
        $sidebarSource,
        'aria-controls="main-navigation"'
    ) !== false
);


sidebarNavigationCheck(
    'Mobile navigation toggle exposes its expanded state',
    strpos(
        $sidebarSource,
        'aria-expanded="false"'
    ) !== false
);


sidebarNavigationCheck(
    'Main navigation has a stable navigation ID',
    strpos(
        $sidebarSource,
        'id="main-navigation"'
    ) !== false
);


sidebarNavigationCheck(
    'Mobile navigation toggle has an accessible label',
    strpos(
        $sidebarSource,
        'aria-label="Toggle main navigation"'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * SIDEBAR STATUS PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Sidebar Status Presentation<br>";
echo "============================================<br>";


sidebarNavigationCheck(
    'Sidebar does not claim that the overall system is online',
    strpos(
        $sidebarSource,
        'System Online'
    ) === false
);


sidebarNavigationCheck(
    'Sidebar does not hard-code an online status indicator',
    strpos(
        $sidebarSource,
        '<span class="status-dot online"></span>'
    ) === false
);


sidebarNavigationCheck(
    'Sidebar directs users to Dashboard for application health',
    strpos(
        $sidebarSource,
        'Health on Dashboard'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * OBSOLETE PLACEHOLDER NAVIGATION JAVASCRIPT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Obsolete Placeholder Navigation JavaScript<br>";
echo "============================================<br>";


$navigationScriptPath =
    __DIR__
    . '/../public/assets/js/app.js';


$navigationScriptExists =
    is_file(
        $navigationScriptPath
    );


$navigationScriptSource =
    $navigationScriptExists
        ? file_get_contents(
            $navigationScriptPath
        )
        : false;


$navigationScriptReadable =
    is_string(
        $navigationScriptSource
    );


$navigationScriptSource =
    $navigationScriptReadable
        ? $navigationScriptSource
        : '';


sidebarNavigationCheck(
    'Application navigation script exists',
    $navigationScriptExists
);


sidebarNavigationCheck(
    'Application navigation script source can be read',
    $navigationScriptReadable
);


sidebarNavigationCheck(
    'Navigation script contains no obsolete placeholder-link explanation',
    strpos(
        $navigationScriptSource,
        'Pages that do not exist yet still use'
    ) === false
);


sidebarNavigationCheck(
    'Navigation script no longer checks for placeholder hash destinations',
    preg_match(
        '/getAttribute\s*\(\s*[\'"]href[\'"]\s*\)\s*===\s*[\'"]#[\'"]/',
        $navigationScriptSource
    ) !== 1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * SHARED NAVIGATION JAVASCRIPT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Shared Navigation JavaScript<br>";
echo "============================================<br>";


$publicPagePaths =
    glob(
        __DIR__
        . '/../public/*.php'
    );


$sidebarPageCount =
    0;


foreach (
    $publicPagePaths
    as $publicPagePath
) {

    $publicPageSource =
        file_get_contents(
            $publicPagePath
        );


    if (
        !is_string(
            $publicPageSource
        )
    ) {

        continue;
    }


    if (
        strpos(
            $publicPageSource,
            '/includes/sidebar.php'
        ) === false
    ) {

        continue;
    }


    $sidebarPageCount++;


    $publicPageName =
        basename(
            $publicPagePath
        );


    sidebarNavigationCheck(
        $publicPageName
            . ' loads the shared application JavaScript',
        strpos(
            $publicPageSource,
            'src="assets/js/app.js"'
        ) !== false
    );
}


sidebarNavigationCheck(
    'Shared navigation JavaScript coverage includes public sidebar pages',
    $sidebarPageCount > 0
);


echo "Sidebar Pages Checked: "
    . $sidebarPageCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO L
 * ACTIVE PAGE ACCESSIBILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario L: Active Page Accessibility<br>";
echo "============================================<br>";


sidebarNavigationCheck(
    'Active navigation links expose the current page with aria-current.',
    preg_match(
        '/aria-current\s*=\s*["\']page["\']/i',
        $sidebarSource
    ) === 1
);

echo "<br><br>";

/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Sidebar Navigation Test Summary<br>";
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