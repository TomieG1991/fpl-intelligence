<?php

echo "============================================<br>";
echo "Player Profile Release Hardening Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function playerProfileReleaseHardeningCheck(
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
    . '/../public/player.php';


echo "Player Profile Path: "
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


playerProfileReleaseHardeningCheck(
    'Player profile public page exists',
    $pageExists
);


playerProfileReleaseHardeningCheck(
    'Player profile public page source can be read',
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


playerProfileReleaseHardeningCheck(
    'Player profile retains the shared sidebar',
    strpos(
        $pageSource,
        '/includes/sidebar.php'
    ) !== false
);


playerProfileReleaseHardeningCheck(
    'Player profile retains the normal dashboard shell',
    strpos(
        $pageSource,
        '<main class="dashboard">'
    ) !== false
);


playerProfileReleaseHardeningCheck(
    'Player profile retains a return link to Player Explorer',
    strpos(
        $pageSource,
        'Return to Player Explorer'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * DATABASE FAILURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Database Failure<br>";
echo "============================================<br>";


playerProfileReleaseHardeningCheck(
    'Player profile does not terminate with die on database failure',
    strpos(
        $pageSource,
        'die('
    ) === false
);


playerProfileReleaseHardeningCheck(
    'Player profile does not terminate with a raw database message',
    !preg_match(
        '/die\s*\(\s*[\'"]Unable to connect to the database\.[\'"]\s*\)/s',
        $pageSource
    )
);


playerProfileReleaseHardeningCheck(
    'Player profile initialises the database connection before attempting connection',
    preg_match(
        '/\$db\s*=\s*null\s*;[\s\S]*?new Database\(\)/',
        $pageSource
    ) === 1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * PROFILE FAILURE PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Profile Failure Presentation<br>";
echo "============================================<br>";


playerProfileReleaseHardeningCheck(
    'Player profile retains safe null profile state',
    strpos(
        $pageSource,
        '$profile ='
    ) !== false
);


playerProfileReleaseHardeningCheck(
    'Player profile retains page error state',
    strpos(
        $pageSource,
        '$pageError ='
    ) !== false
);


playerProfileReleaseHardeningCheck(
    'Player profile retains Player Not Found presentation',
    strpos(
        $pageSource,
        'Player Not Found'
    ) !== false
);


playerProfileReleaseHardeningCheck(
    'Player profile renders error detail inside the application',
    strpos(
        $pageSource,
        'profile-error-detail'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * PROFILE INTELLIGENCE REMAINS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Profile Intelligence Remains<br>";
echo "============================================<br>";


playerProfileReleaseHardeningCheck(
    'Player profile retains PlayerIntelligenceService',
    strpos(
        $pageSource,
        'new PlayerIntelligenceService'
    ) !== false
);


playerProfileReleaseHardeningCheck(
    'Player profile retains getPlayerProfile',
    strpos(
        $pageSource,
        '->getPlayerProfile('
    ) !== false
);


playerProfileReleaseHardeningCheck(
    'Player profile retains MarketIntelligenceService',
    strpos(
        $pageSource,
        'new MarketIntelligenceService'
    ) !== false
);


playerProfileReleaseHardeningCheck(
    'Player profile retains player market summary',
    strpos(
        $pageSource,
        '->getPlayerMarketSummary('
    ) !== false
);


playerProfileReleaseHardeningCheck(
    'Player profile retains Compare Player navigation',
    strpos(
        $pageSource,
        'compare.php?player1='
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


playerProfileReleaseHardeningCheck(
    'Player profile contains the Fixture Outlook table',
    strpos(
        $pageSource,
        '<table class="fixture-outlook-table">'
    ) !== false
);


playerProfileReleaseHardeningCheck(
    'Player profile Fixture Outlook contains five column headers',
    preg_match_all(
        '/<th(?:\s[^>]*)?>/i',
        $pageSource,
        $fixtureOutlookHeaderMatches
    ) === 5
);


playerProfileReleaseHardeningCheck(
    'Player profile Fixture Outlook headers expose column scope',
    substr_count(
        $pageSource,
        '<th scope="col">'
    ) === 5
);


playerProfileReleaseHardeningCheck(
    'Player profile error detail exposes alert semantics',
    strpos(
        $pageSource,
        '<p class="profile-error-detail" role="alert">'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Profile Release Hardening Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}