<?php

echo "============================================<br>";
echo "Compare Page Release Hardening Test<br>";
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

function comparePageReleaseHardeningCheck(
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
 * PAGE SOURCE
 * ============================================================
 */

$pagePath =
    __DIR__
    . '/../public/compare.php';


echo "Compare Page Path: "
    . htmlspecialchars(
        $pagePath,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


$pageExists =
    is_file(
        $pagePath
    );


$pageSource =
    $pageExists
        ? file_get_contents(
            $pagePath
        )
        : false;


$pageReadable =
    is_string(
        $pageSource
    );


$pageSource =
    $pageReadable
        ? $pageSource
        : '';


/*
 * ============================================================
 * SCENARIO A
 * PAGE SOURCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Page Source<br>";
echo "============================================<br>";


comparePageReleaseHardeningCheck(
    'Compare public page exists',
    $pageExists
);


comparePageReleaseHardeningCheck(
    'Compare public page source can be read',
    $pageReadable
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * APPLICATION SHELL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Application Shell<br>";
echo "============================================<br>";


comparePageReleaseHardeningCheck(
    'Compare page retains the shared sidebar',
    strpos(
        $pageSource,
        '/includes/sidebar.php'
    ) !== false
);


comparePageReleaseHardeningCheck(
    'Compare page retains Player Comparison presentation',
    strpos(
        $pageSource,
        'Player Comparison'
    ) !== false
);


comparePageReleaseHardeningCheck(
    'Compare page retains the normal dashboard shell',
    strpos(
        $pageSource,
        '<main class="dashboard">'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * INITIALISATION FAILURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Initialisation Failure<br>";
echo "============================================<br>";


comparePageReleaseHardeningCheck(
    'Compare page does not terminate with die on initialisation failure',
    strpos(
        $pageSource,
        'die('
    ) === false
);


comparePageReleaseHardeningCheck(
    'Compare page does not terminate with a raw initialisation message',
    !preg_match(
        '/die\s*\(\s*[\'"]Unable to initialise player comparison\.[\'"]\s*\)/s',
        $pageSource
    )
);


comparePageReleaseHardeningCheck(
    'Compare page provides an in-page initialisation error state',
    strpos(
        $pageSource,
        'Player comparison data could not be loaded.'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * SAFE INITIAL STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Safe Initial State<br>";
echo "============================================<br>";


comparePageReleaseHardeningCheck(
    'Compare page initialises an empty player collection before database access',
    preg_match(
        '/\$players\s*=\s*\[\]\s*;[\s\S]*?new Database\(\)/',
        $pageSource
    ) === 1
);


comparePageReleaseHardeningCheck(
    'Compare page initialises the intelligence service before database access',
    preg_match(
        '/\$playerIntelligenceService\s*=\s*null\s*;[\s\S]*?new Database\(\)/',
        $pageSource
    ) === 1
);


comparePageReleaseHardeningCheck(
    'Compare page initialises a page error before database access',
    preg_match(
        '/\$pageError\s*=\s*null\s*;[\s\S]*?new Database\(\)/',
        $pageSource
    ) === 1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * COMPARISON FUNCTIONALITY REMAINS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Comparison Functionality Remains<br>";
echo "============================================<br>";


comparePageReleaseHardeningCheck(
    'Compare page retains PlayerIntelligenceService',
    strpos(
        $pageSource,
        'new PlayerIntelligenceService'
    ) !== false
);


comparePageReleaseHardeningCheck(
    'Compare page retains player summaries',
    strpos(
        $pageSource,
        '->getAllPlayerSummaries()'
    ) !== false
);


comparePageReleaseHardeningCheck(
    'Compare page retains player comparison',
    strpos(
        $pageSource,
        '->comparePlayers('
    ) !== false
);


comparePageReleaseHardeningCheck(
    'Compare page retains the player-one selector',
    strpos(
        $pageSource,
        'name="player1"'
    ) !== false
);


comparePageReleaseHardeningCheck(
    'Compare page retains the player-two selector',
    strpos(
        $pageSource,
        'name="player2"'
    ) !== false
);


comparePageReleaseHardeningCheck(
    'Compare page retains player profile links',
    strpos(
        $pageSource,
        'player.php?id='
    ) !== false
);


echo "<br>";

/*
 * ============================================================
 * SCENARIO F
 * ACCESSIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Accessibility<br>";
echo "============================================<br>";


comparePageReleaseHardeningCheck(
    'Compare page-level error exposes alert semantics',
    strpos(
        $pageSource,
        '<div class="alert alert-error" role="alert">'
    ) !== false
);


comparePageReleaseHardeningCheck(
    'Compare comparison error exposes alert semantics',
    strpos(
        $pageSource,
        '<div class="comparison-error" role="alert">'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Compare Page Release Hardening Test Summary<br>";
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