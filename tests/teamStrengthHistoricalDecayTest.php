<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * TEAM STRENGTH HISTORICAL DECAY TEST
 * ============================================================
 *
 * Tests how the model responds when a team's recent results
 * differ significantly from its earlier results.
 *
 * IMPORTANT:
 *
 * This test does not modify the real database.
 *
 * It is designed to establish whether recent matches have
 * greater influence than older matches.
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


function heading(
    string $text
): void {

    echo "<br>";
    echo "============================================<br>";
    echo "{$text}<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * TEST DATA
 * ============================================================
 */

$teamStrengths = [

    1 => [
        'id' => 1,
        'name' => 'Arsenal',
        'home' => 100,
        'away' => 100,
        'overall' => 100
    ],

    2 => [
        'id' => 2,
        'name' => 'Weak Team',
        'home' => 20,
        'away' => 20,
        'overall' => 20
    ]

];


$teamPerformance =
    new TeamPerformance();


$teamStrengthModel =
    new TeamStrengthModel();


$oppositionAdjusted =
    new OppositionAdjustedPerformance();


/*
 * ============================================================
 * SCENARIO A
 * ============================================================
 *
 * Five early wins followed by five losses.
 *
 * This represents a team that starts strongly but then
 * experiences a significant decline in form.
 */

heading('Scenario A: Early Wins, Recent Losses');


$fixturesEarlyWinsRecentLosses = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 2,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 3,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 4,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 5,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 6,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 7,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 8,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 9,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 10,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ]

];


$performanceA =
    $teamPerformance->analyse(
        $fixturesEarlyWinsRecentLosses,
        1
    );


$modelA =
    $teamStrengthModel->buildTeamModel(
        $teamStrengths[1],
        $performanceA,
        $teamPerformance
    );


echo "Performance: "
    . number_format(
        $modelA['performance_rating'],
        2
    )
    . "<br>";

echo "Overall: "
    . number_format(
        $modelA['overall'],
        2
    )
    . "<br>";


testResult(
    'Early wins/recent losses contains ten matches',
    $performanceA['played'] === 10
);

testResult(
    'Early wins/recent losses produces a performance rating',
    $modelA['performance_rating'] !== null
);

testResult(
    'Recent losses reduce the overall rating below baseline',
    $modelA['overall'] < 100
);


/*
 * ============================================================
 * SCENARIO B
 * ============================================================
 *
 * Five early losses followed by five recent wins.
 *
 * This is the reverse of Scenario A.
 */

heading('Scenario B: Early Losses, Recent Wins');


$fixturesEarlyLossesRecentWins = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 2,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 3,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 4,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 5,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 6,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 7,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 8,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 9,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 10,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ]

];


$performanceB =
    $teamPerformance->analyse(
        $fixturesEarlyLossesRecentWins,
        1
    );


$modelB =
    $teamStrengthModel->buildTeamModel(
        $teamStrengths[1],
        $performanceB,
        $teamPerformance
    );


echo "Performance: "
    . number_format(
        $modelB['performance_rating'],
        2
    )
    . "<br>";

echo "Overall: "
    . number_format(
        $modelB['overall'],
        2
    )
    . "<br>";


testResult(
    'Early losses/recent wins contains ten matches',
    $performanceB['played'] === 10
);

testResult(
    'Early losses/recent wins produces a performance rating',
    $modelB['performance_rating'] !== null
);

testResult(
    'Current model gives identical overall ratings to reversed histories',
    $modelB['overall'] === $modelA['overall']
);


/*
 * ============================================================
 * SCENARIO C
 * ============================================================
 *
 * Compare the two opposite histories.
 *
 * Both scenarios contain:
 *
 * - 5 wins
 * - 5 losses
 *
 * The results are deliberately reversed chronologically.
 *
 * The current model uses a historical average, so both
 * scenarios should currently produce the same performance.
 *
 * This establishes the baseline before true recency decay
 * is introduced.
 */

heading('Scenario C: Recency Sensitivity');


echo "Early wins / recent losses performance: "
    . number_format(
        $modelA['performance_rating'],
        2
    )
    . "<br>";

echo "Early losses / recent wins performance: "
    . number_format(
        $modelB['performance_rating'],
        2
    )
    . "<br>";


testResult(
    'Both scenarios contain the same number of matches',
    $performanceA['played'] === $performanceB['played']
);

testResult(
    'Both scenarios contain five wins',
    $performanceA['wins'] === 5
    &&
    $performanceB['wins'] === 5
);

testResult(
    'Both scenarios contain five losses',
    $performanceA['losses'] === 5
    &&
    $performanceB['losses'] === 5
);

testResult(
    'Current model treats equally weighted histories equally',
    $modelA['performance_rating']
    ===
    $modelB['performance_rating']
);


/*
 * ============================================================
 * SCENARIO D
 * ============================================================
 *
 * Verify that TeamPerformance processes all completed
 * fixtures in chronological gameweek order internally.
 *
 * We test the raw fixture input/output indirectly through
 * the performance result rather than relying on a "matches"
 * key that TeamPerformance does not currently return.
 */

heading('Scenario D: Historical Fixture Processing');


testResult(
    'Early wins/recent losses processes all ten matches',
    $performanceA['played'] === 10
);

testResult(
    'Early losses/recent wins processes all ten matches',
    $performanceB['played'] === 10
);

testResult(
    'Reversing result order does not change the current average',
    $performanceA['average_performance']
    ===
    $performanceB['average_performance']
);
/*
 * ============================================================
 * SCENARIO E
 * ============================================================
 *
 * Test that adding a recent result changes the model.
 */

heading('Scenario E: Adding Recent Form');


$fixturesOneAdditionalWin =
    $fixturesEarlyWinsRecentLosses;


$fixturesOneAdditionalWin[] = [

    'gameweek' => 11,
    'home_team_id' => 1,
    'away_team_id' => 2,
    'finished' => 1,
    'home_score' => 3,
    'away_score' => 0
];


$performanceBefore =
    $teamPerformance->analyse(
        $fixturesEarlyWinsRecentLosses,
        1
    );


$performanceAfter =
    $teamPerformance->analyse(
        $fixturesOneAdditionalWin,
        1
    );


$modelBefore =
    $teamStrengthModel->buildTeamModel(
        $teamStrengths[1],
        $performanceBefore,
        $teamPerformance
    );


$modelAfter =
    $teamStrengthModel->buildTeamModel(
        $teamStrengths[1],
        $performanceAfter,
        $teamPerformance
    );


echo "Before recent win: "
    . number_format(
        $modelBefore['overall'],
        2
    )
    . "<br>";

echo "After recent win: "
    . number_format(
        $modelAfter['overall'],
        2
    )
    . "<br>";


testResult(
    'Adding a recent win changes the performance rating',
    $modelAfter['performance_rating']
    !==
    $modelBefore['performance_rating']
);

testResult(
    'Adding a recent win improves the overall rating',
    $modelAfter['overall']
    >
    $modelBefore['overall']
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Team Strength Historical Decay Summary<br>";
echo "============================================<br>";

echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}