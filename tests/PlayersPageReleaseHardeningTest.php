<?php

echo "============================================<br>";
echo "Players Page Release Hardening Test<br>";
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

function playersPageReleaseHardeningCheck(
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
 * PLAYERS PAGE SOURCE
 * ============================================================
 */

$pagePath =
    __DIR__
    . '/../public/players.php';


echo "Players Page Path: "
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


playersPageReleaseHardeningCheck(
    'Players public page exists',
    $pageExists
);


playersPageReleaseHardeningCheck(
    'Players public page source can be read',
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


playersPageReleaseHardeningCheck(
    'Players page retains the shared sidebar',
    strpos(
        $pageSource,
        '/includes/sidebar.php'
    ) !== false
);


playersPageReleaseHardeningCheck(
    'Players page retains Player Explorer presentation',
    strpos(
        $pageSource,
        'Player Explorer'
    ) !== false
);


playersPageReleaseHardeningCheck(
    'Players page retains the normal dashboard shell',
    strpos(
        $pageSource,
        '<main class="dashboard">'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * DATABASE FAILURE PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Database Failure Presentation<br>";
echo "============================================<br>";


playersPageReleaseHardeningCheck(
    'Players page does not terminate with die on database failure',
    strpos(
        $pageSource,
        'die('
    ) === false
);


playersPageReleaseHardeningCheck(
    'Players page does not terminate with a raw database connection message',
    !preg_match(
        '/die\s*\(\s*[\'"]Unable to connect to the database\.[\'"]\s*\)/s',
        $pageSource
    )
);


playersPageReleaseHardeningCheck(
    'Players page provides an in-page player data error state',
    strpos(
        $pageSource,
        'Player data could not be loaded.'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * SAFE EMPTY DEFAULTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Safe Empty Defaults<br>";
echo "============================================<br>";


playersPageReleaseHardeningCheck(
    'Players page initialises an empty player collection before database access',
    preg_match(
        '/\$players\s*=\s*\[\]\s*;[\s\S]*?new Database\(\)/',
        $pageSource
    ) === 1
);


playersPageReleaseHardeningCheck(
    'Players page initialises an empty ranked-player collection before database access',
    preg_match(
        '/\$rankedPlayers\s*=\s*\[\]\s*;[\s\S]*?new Database\(\)/',
        $pageSource
    ) === 1
);


playersPageReleaseHardeningCheck(
    'Players page initialises the top-rated player before database access',
    preg_match(
        '/\$topRatedPlayer\s*=\s*null\s*;[\s\S]*?new Database\(\)/',
        $pageSource
    ) === 1
);


playersPageReleaseHardeningCheck(
    'Players page initialises the ranked-player count before database access',
    preg_match(
        '/\$rankedPlayerCount\s*=\s*0\s*;[\s\S]*?new Database\(\)/',
        $pageSource
    ) === 1
);


playersPageReleaseHardeningCheck(
    'Players page initialises intelligence ranks before database access',
    preg_match(
        '/\$intelligenceRanks\s*=\s*\[\]\s*;[\s\S]*?new Database\(\)/',
        $pageSource
    ) === 1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * PLAYER INTELLIGENCE REMAINS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Player Intelligence Remains<br>";
echo "============================================<br>";


playersPageReleaseHardeningCheck(
    'Players page retains PlayerIntelligenceService',
    strpos(
        $pageSource,
        'new PlayerIntelligenceService'
    ) !== false
);


playersPageReleaseHardeningCheck(
    'Players page retains all-player summaries',
    strpos(
        $pageSource,
        '->getAllPlayerSummaries()'
    ) !== false
);


playersPageReleaseHardeningCheck(
    'Players page retains ranked players',
    strpos(
        $pageSource,
        '->getRankedPlayers()'
    ) !== false
);


playersPageReleaseHardeningCheck(
    'Players page retains player profile links',
    strpos(
        $pageSource,
        'player.php?id='
    ) !== false
);


playersPageReleaseHardeningCheck(
    'Players page retains player comparison links',
    strpos(
        $pageSource,
        'compare.php?player1='
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * PRODUCTION ERROR HANDLING
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Production Error Handling<br>";
echo "============================================<br>";


playersPageReleaseHardeningCheck(
    'Players page creates the shared ApplicationErrorPresenter',
    strpos(
        $pageSource,
        'new ApplicationErrorPresenter'
    ) !== false
);


playersPageReleaseHardeningCheck(
    'Players page supplies the configured application environment',
    preg_match(
        "/\\\$config\\s*\\[\\s*'environment'\\s*\\]/s",
        $pageSource
    ) === 1
);


playersPageReleaseHardeningCheck(
    'Players page failure is passed through the shared error presenter',
    strpos(
        $pageSource,
        '$errorPresenter->present('
    ) !== false
);


playersPageReleaseHardeningCheck(
    'Players page failure retains its safe user-facing fallback message',
    strpos(
        $pageSource,
        'Player data could not be loaded.'
    ) !== false
);


playersPageReleaseHardeningCheck(
    'Players page does not assign the raw exception message directly',
    preg_match(
        '/\\$pageError\\s*=\\s*\\$exception\\s*->\\s*getMessage\\s*\\(\\s*\\)\\s*;/s',
        $pageSource
    ) !== 1
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Players Page Release Hardening Test Summary<br>";
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