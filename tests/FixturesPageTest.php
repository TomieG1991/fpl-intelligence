<?php

echo "============================================<br>";
echo "Fixtures Page Test<br>";
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

function fixturesPageCheck(
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
 * PAGE PATH
 * ============================================================
 */

$pagePath =
    __DIR__
    . '/../public/fixtures.php';


echo "Page Path: "
    . htmlspecialchars(
        $pagePath,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO A
 * PUBLIC PAGE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Public Page<br>";
echo "============================================<br>";


$pageExists =
    is_file(
        $pagePath
    );


fixturesPageCheck(
    'Fixtures public page exists',
    $pageExists
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


fixturesPageCheck(
    'Fixtures public page source can be read',
    $pageReadable
);


$pageSource =
    $pageReadable
        ? $pageSource
        : '';


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * APPLICATION SHELL CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Application Shell Contract<br>";
echo "============================================<br>";


fixturesPageCheck(
    'Fixtures page identifies the Fixtures navigation item',
    strpos(
        $pageSource,
        "\$activeNav"
    ) !== false
    &&
    strpos(
        $pageSource,
        "'fixtures'"
    ) !== false
);


fixturesPageCheck(
    'Fixtures page uses the shared sidebar',
    strpos(
        $pageSource,
        "includes/sidebar.php"
    ) !== false
);


fixturesPageCheck(
    'Fixtures page uses the shared application stylesheet',
    strpos(
        $pageSource,
        'assets/css/app.css'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page contains the application shell',
    strpos(
        $pageSource,
        'class="app-shell"'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page contains the application content wrapper',
    strpos(
        $pageSource,
        'class="app-content"'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page contains the shared topbar',
    strpos(
        $pageSource,
        'class="topbar"'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * FIXTURE DATA BOUNDARY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Fixture Data Boundary<br>";
echo "============================================<br>";


fixturesPageCheck(
    'Fixtures page uses FixtureRepository',
    strpos(
        $pageSource,
        'FixtureRepository'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page loads fixture data through the repository',
    strpos(
        $pageSource,
        '->getAll('
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * TEAM DATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Team Data<br>";
echo "============================================<br>";


fixturesPageCheck(
    'Fixtures page uses TeamRepository for team identity',
    strpos(
        $pageSource,
        'TeamRepository'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page provides links to Team Intelligence profiles',
    strpos(
        $pageSource,
        'team.php?id='
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * FIXTURE PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Fixture Presentation<br>";
echo "============================================<br>";


fixturesPageCheck(
    'Fixtures page has the Fixtures document title',
    strpos(
        $pageSource,
        '<title>Fixtures | FPL Intelligence</title>'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page has the Fixtures page heading',
    strpos(
        $pageSource,
        'Fixtures'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page exposes gameweek information',
    strpos(
        $pageSource,
        'gameweek'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page exposes kickoff information',
    strpos(
        $pageSource,
        'kickoff_time'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page exposes home team information',
    strpos(
        $pageSource,
        'home_team_id'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page exposes away team information',
    strpos(
        $pageSource,
        'away_team_id'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page exposes fixture difficulty information',
    strpos(
        $pageSource,
        'home_difficulty'
    ) !== false
    &&
    strpos(
        $pageSource,
        'away_difficulty'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * EMPTY AND ERROR STATES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Empty and Error States<br>";
echo "============================================<br>";


fixturesPageCheck(
    'Fixtures page provides an empty fixture state',
    strpos(
        $pageSource,
        'No fixtures available'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page provides a safe loading error',
    strpos(
        $pageSource,
        'Unable to load fixtures'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * GAMEWEEK NAVIGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Gameweek Navigation<br>";
echo "============================================<br>";


fixturesPageCheck(
    'Fixtures page accepts a selected gameweek',
    strpos(
        $pageSource,
        "\$_GET['gameweek']"
    ) !== false
);


fixturesPageCheck(
    'Fixtures page identifies the first upcoming gameweek',
    strpos(
        $pageSource,
        '$firstUpcomingGameweek'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page exposes a selected gameweek',
    strpos(
        $pageSource,
        '$selectedGameweek'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page renders only the selected gameweek fixture collection',
    strpos(
        $pageSource,
        '$selectedFixtures'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page provides previous gameweek navigation',
    strpos(
        $pageSource,
        '$previousGameweek'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page provides next gameweek navigation',
    strpos(
        $pageSource,
        '$nextGameweek'
    ) !== false
);


fixturesPageCheck(
    'Fixtures page provides a gameweek selector',
    strpos(
        $pageSource,
        'name="gameweek"'
    ) !== false
);


fixturesPageCheck(
    'Gameweek selector submits back to the Fixtures page',
    strpos(
        $pageSource,
        'action="fixtures.php"'
    ) !== false
);


fixturesPageCheck(
    'Previous and next navigation use the gameweek query parameter',
    strpos(
        $pageSource,
        'fixtures.php?gameweek='
    ) !== false
);


fixturesPageCheck(
    'Fixtures page explains the selected gameweek schedule',
    strpos(
        $pageSource,
        'Selected Gameweek'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * FIXTURE PRESENTATION HOOKS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Fixture Presentation Hooks<br>";
echo "============================================<br>";


fixturesPageCheck(
    'Gameweek controls have a dedicated presentation wrapper',
    strpos(
        $pageSource,
        'fixture-gameweek-controls'
    ) !== false
);


fixturesPageCheck(
    'Gameweek navigation has a dedicated presentation wrapper',
    strpos(
        $pageSource,
        'class="fixture-gameweek-navigation"'
    ) !== false
);


fixturesPageCheck(
    'Gameweek navigation links have a dedicated presentation class',
    strpos(
        $pageSource,
        'class="fixture-gameweek-link"'
    ) !== false
);


fixturesPageCheck(
    'Gameweek selector form has a dedicated presentation class',
    strpos(
        $pageSource,
        'class="fixture-gameweek-form"'
    ) !== false
);


fixturesPageCheck(
    'Fixture table has a dedicated presentation class',
    strpos(
        $pageSource,
        'fixture-table'
    ) !== false
);


fixturesPageCheck(
    'Fixture difficulty values use FDR presentation badges',
    strpos(
        $pageSource,
        'class="fixture-fdr fixture-fdr-'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * RESPONSIVE TABLE GUIDANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Responsive Table Guidance<br>";
echo "============================================<br>";


fixturesPageCheck(
    'Fixtures page provides guidance for horizontally scrollable fixture data',
    strpos(
        $pageSource,
        'fixture-table-scroll-hint'
    ) !== false
);


fixturesPageCheck(
    'Fixture table has an accessible fixture schedule label',
    strpos(
        $pageSource,
        'aria-label="Fixture schedule"'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * DATA HEALTH AWARENESS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Data Health Awareness<br>";
echo "============================================<br>";


fixturesPageCheck(
    'Fixtures page loads the shared data-health evaluator.',
    strpos(
        $pageSource,
        "includes/data-health.php"
    )
    !== false
);


fixturesPageCheck(
    'Fixtures page evaluates Bootstrap health.',
    preg_match(
        "/evaluateDataHealth\\s*\\("
        . ".*?['\"]bootstrap['\"]"
        . ".*?\\)/s",
        $pageSource
    )
    === 1
);


fixturesPageCheck(
    'Fixtures page evaluates Fixtures health.',
    preg_match(
        "/evaluateDataHealth\\s*\\("
        . ".*?['\"]fixtures['\"]"
        . ".*?\\)/s",
        $pageSource
    )
    === 1
);


fixturesPageCheck(
    'Fixtures page uses the shared data-health warning component.',
    strpos(
        $pageSource,
        "includes/data-health-warning.php"
    )
    !== false
);


fixturesPageCheck(
    'Fixtures page uses the shared freshness configuration.',
    preg_match(
        "/\\\$config\\s*\\[\\s*['\"]data_health['\"]\\s*\\]"
        . "\\s*\\[\\s*['\"]freshness_seconds['\"]\\s*\\]/s",
        $pageSource
    )
    === 1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * TABLE HEADER ACCESSIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Table Header Accessibility<br>";
echo "============================================<br>";


preg_match(
    '/<table\b[^>]*class=["\'][^"\']*\bfixture-table\b[^"\']*["\'][^>]*>.*?<\/table>/is',
    $pageSource,
    $fixtureTableMatch
);


fixturesPageCheck(
    'Fixtures page contains the fixture schedule table',
    !empty(
        $fixtureTableMatch[0]
        ?? ''
    )
);


$fixtureTableSource =
    $fixtureTableMatch[0]
    ?? '';


preg_match_all(
    '/<th\b[^>]*>/i',
    $fixtureTableSource,
    $fixtureTableHeaderMatches
);


$fixtureTableHeaders =
    $fixtureTableHeaderMatches[0]
    ?? [];


fixturesPageCheck(
    'Fixture schedule contains the expected five column headers',
    count(
        $fixtureTableHeaders
    )
    === 5
);


$fixtureScopedColumnHeaders =
    array_filter(
        $fixtureTableHeaders,
        static function (
            string $header
        ): bool {

            return preg_match(
                '/\bscope\s*=\s*["\']col["\']/i',
                $header
            )
            === 1;
        }
    );


fixturesPageCheck(
    'Every Fixture schedule header is identified as a column header',
    count(
        $fixtureScopedColumnHeaders
    )
    === 5
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Fixtures Page Test Summary<br>";
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