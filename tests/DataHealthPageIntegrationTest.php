<?php

echo "============================================<br>";
echo "Data Health Page Integration Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function dataHealthPageCheck(
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


function dataHealthPageSource(
    string $filename
): string {

    $path =
        __DIR__
        . '/../public/'
        . $filename;


    if (
        !is_file(
            $path
        )
    ) {

        return '';
    }


    $source =
        file_get_contents(
            $path
        );


    return is_string(
        $source
    )
        ? $source
        : '';
}


function dataHealthPageUsesFeed(
    string $source,
    string $updateType
): bool {

    $pattern =
        "/evaluateDataHealth\\s*\\("
        . ".*?['\"]"
        . preg_quote(
            $updateType,
            '/'
        )
        . "['\"]"
        . ".*?\\)/s";


    return preg_match(
        $pattern,
        $source
    )
    === 1;
}


function dataHealthPageUsesFreshnessConfig(
    string $source
): bool {

    return preg_match(
        "/\\\$config\\s*\\[\\s*['\"]data_health['\"]\\s*\\]"
        . "\\s*\\[\\s*['\"]freshness_seconds['\"]\\s*\\]/s",
        $source
    )
    === 1;
}


/*
 * ============================================================
 * SCENARIO A
 * TEAM INTELLIGENCE LIST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Team Intelligence List<br>";
echo "============================================<br>";


$teamsSource =
    dataHealthPageSource(
        'teams.php'
    );


dataHealthPageCheck(
    'Teams page source is available.',
    $teamsSource !== ''
);


dataHealthPageCheck(
    'Teams page loads the shared data-health evaluator.',
    strpos(
        $teamsSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Teams page evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $teamsSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Teams page evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $teamsSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Teams page does not request Player Fixture History health.',
    !dataHealthPageUsesFeed(
        $teamsSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Teams page uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $teamsSource
    )
);


dataHealthPageCheck(
    'Teams page renders the shared data-health warning.',
    strpos(
        $teamsSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * TEAM INTELLIGENCE PROFILE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Team Intelligence Profile<br>";
echo "============================================<br>";


$teamSource =
    dataHealthPageSource(
        'team.php'
    );


dataHealthPageCheck(
    'Team profile page source is available.',
    $teamSource !== ''
);


dataHealthPageCheck(
    'Team profile page loads the shared data-health evaluator.',
    strpos(
        $teamSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Team profile page evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $teamSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Team profile page evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $teamSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Team profile page does not request Player Fixture History health.',
    !dataHealthPageUsesFeed(
        $teamSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Team profile page uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $teamSource
    )
);


dataHealthPageCheck(
    'Team profile page renders the shared data-health warning.',
    strpos(
        $teamSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * PLAYER INTELLIGENCE LIST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Player Intelligence List<br>";
echo "============================================<br>";


$playersSource =
    dataHealthPageSource(
        'players.php'
    );


dataHealthPageCheck(
    'Players page source is available.',
    $playersSource !== ''
);


dataHealthPageCheck(
    'Players page loads the shared data-health evaluator.',
    strpos(
        $playersSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Players page evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $playersSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Players page evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $playersSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Players page evaluates Player Fixture History health.',
    dataHealthPageUsesFeed(
        $playersSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Players page uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $playersSource
    )
);


dataHealthPageCheck(
    'Players page renders the shared data-health warning.',
    strpos(
        $playersSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * PLAYER INTELLIGENCE PROFILE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Player Intelligence Profile<br>";
echo "============================================<br>";


$playerSource =
    dataHealthPageSource(
        'player.php'
    );


dataHealthPageCheck(
    'Player profile page source is available.',
    $playerSource !== ''
);


dataHealthPageCheck(
    'Player profile page loads the shared data-health evaluator.',
    strpos(
        $playerSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Player profile page evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $playerSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Player profile page evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $playerSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Player profile page evaluates Player Fixture History health.',
    dataHealthPageUsesFeed(
        $playerSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Player profile page uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $playerSource
    )
);


dataHealthPageCheck(
    'Player profile page renders the shared data-health warning.',
    strpos(
        $playerSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * PLAYER COMPARISON
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Player Comparison<br>";
echo "============================================<br>";


$compareSource =
    dataHealthPageSource(
        'compare.php'
    );


dataHealthPageCheck(
    'Player comparison page source is available.',
    $compareSource !== ''
);


dataHealthPageCheck(
    'Player comparison page loads the shared data-health evaluator.',
    strpos(
        $compareSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Player comparison page evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $compareSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Player comparison page evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $compareSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Player comparison page evaluates Player Fixture History health.',
    dataHealthPageUsesFeed(
        $compareSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Player comparison page uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $compareSource
    )
);


dataHealthPageCheck(
    'Player comparison page renders the shared data-health warning.',
    strpos(
        $compareSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";

/*
 * ============================================================
 * SCENARIO F
 * SQUAD INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Squad Intelligence<br>";
echo "============================================<br>";


$squadSource =
    dataHealthPageSource(
        'squad.php'
    );


dataHealthPageCheck(
    'Squad page source is available.',
    $squadSource !== ''
);


dataHealthPageCheck(
    'Squad page loads the shared data-health evaluator.',
    strpos(
        $squadSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Squad page evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $squadSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Squad page evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $squadSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Squad page evaluates Player Fixture History health.',
    dataHealthPageUsesFeed(
        $squadSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Squad page uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $squadSource
    )
);


dataHealthPageCheck(
    'Squad page renders the shared data-health warning.',
    strpos(
        $squadSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * GAMEWEEK INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Gameweek Intelligence<br>";
echo "============================================<br>";


$gameweekSource =
    dataHealthPageSource(
        'gameweek.php'
    );


dataHealthPageCheck(
    'Gameweek page source is available.',
    $gameweekSource !== ''
);


dataHealthPageCheck(
    'Gameweek page loads the shared data-health evaluator.',
    strpos(
        $gameweekSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Gameweek page evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $gameweekSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Gameweek page evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $gameweekSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Gameweek page evaluates Player Fixture History health.',
    dataHealthPageUsesFeed(
        $gameweekSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Gameweek page uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $gameweekSource
    )
);


dataHealthPageCheck(
    'Gameweek page renders the shared data-health warning.',
    strpos(
        $gameweekSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * TRANSFER INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Transfer Intelligence<br>";
echo "============================================<br>";


$transfersSource =
    dataHealthPageSource(
        'transfers.php'
    );


dataHealthPageCheck(
    'Transfers page source is available.',
    $transfersSource !== ''
);


dataHealthPageCheck(
    'Transfers page loads the shared data-health evaluator.',
    strpos(
        $transfersSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Transfers page evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $transfersSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Transfers page evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $transfersSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Transfers page evaluates Player Fixture History health.',
    dataHealthPageUsesFeed(
        $transfersSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Transfers page uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $transfersSource
    )
);


dataHealthPageCheck(
    'Transfers page renders the shared data-health warning.',
    strpos(
        $transfersSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * TRANSFER PLANNER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Transfer Planner<br>";
echo "============================================<br>";


$transferPlannerSource =
    dataHealthPageSource(
        'transfer-planner.php'
    );


dataHealthPageCheck(
    'Transfer Planner source is available.',
    $transferPlannerSource !== ''
);


dataHealthPageCheck(
    'Transfer Planner loads the shared data-health evaluator.',
    strpos(
        $transferPlannerSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Transfer Planner evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $transferPlannerSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Transfer Planner evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $transferPlannerSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Transfer Planner evaluates Player Fixture History health.',
    dataHealthPageUsesFeed(
        $transferPlannerSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Transfer Planner uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $transferPlannerSource
    )
);


dataHealthPageCheck(
    'Transfer Planner renders the shared data-health warning.',
    strpos(
        $transferPlannerSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * TRANSFER OPTIMIZER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Transfer Optimizer<br>";
echo "============================================<br>";


$transferOptimizerSource =
    dataHealthPageSource(
        'transfer-optimizer.php'
    );


dataHealthPageCheck(
    'Transfer Optimizer source is available.',
    $transferOptimizerSource !== ''
);


dataHealthPageCheck(
    'Transfer Optimizer loads the shared data-health evaluator.',
    strpos(
        $transferOptimizerSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Transfer Optimizer evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $transferOptimizerSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Transfer Optimizer evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $transferOptimizerSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Transfer Optimizer evaluates Player Fixture History health.',
    dataHealthPageUsesFeed(
        $transferOptimizerSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Transfer Optimizer uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $transferOptimizerSource
    )
);


dataHealthPageCheck(
    'Transfer Optimizer renders the shared data-health warning.',
    strpos(
        $transferOptimizerSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * WILDCARD INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Wildcard Intelligence<br>";
echo "============================================<br>";


$wildcardSource =
    dataHealthPageSource(
        'wildcard.php'
    );


dataHealthPageCheck(
    'Wildcard page source is available.',
    $wildcardSource !== ''
);


dataHealthPageCheck(
    'Wildcard page loads the shared data-health evaluator.',
    strpos(
        $wildcardSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Wildcard page evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $wildcardSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Wildcard page evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $wildcardSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Wildcard page evaluates Player Fixture History health.',
    dataHealthPageUsesFeed(
        $wildcardSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Wildcard page uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $wildcardSource
    )
);


dataHealthPageCheck(
    'Wildcard page renders the shared data-health warning.',
    strpos(
        $wildcardSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * CHIP INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Chip Intelligence<br>";
echo "============================================<br>";


$chipsSource =
    dataHealthPageSource(
        'chips.php'
    );


dataHealthPageCheck(
    'Chips page source is available.',
    $chipsSource !== ''
);


dataHealthPageCheck(
    'Chips page loads the shared data-health evaluator.',
    strpos(
        $chipsSource,
        'includes/data-health.php'
    )
    !== false
);


dataHealthPageCheck(
    'Chips page evaluates Bootstrap health.',
    dataHealthPageUsesFeed(
        $chipsSource,
        'bootstrap'
    )
);


dataHealthPageCheck(
    'Chips page evaluates Fixtures health.',
    dataHealthPageUsesFeed(
        $chipsSource,
        'fixtures'
    )
);


dataHealthPageCheck(
    'Chips page evaluates Player Fixture History health.',
    dataHealthPageUsesFeed(
        $chipsSource,
        'player_fixture_history'
    )
);


dataHealthPageCheck(
    'Chips page uses the shared freshness configuration.',
    dataHealthPageUsesFreshnessConfig(
        $chipsSource
    )
);


dataHealthPageCheck(
    'Chips page renders the shared data-health warning.',
    strpos(
        $chipsSource,
        'includes/data-health-warning.php'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Data Health Page Integration Test Summary<br>";
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

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}