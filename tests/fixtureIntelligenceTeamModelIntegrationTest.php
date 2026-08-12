<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * FIXTURE INTELLIGENCE TEAM MODEL INTEGRATION TEST
 * ============================================================
 *
 * Tests that FixtureIntelligence correctly consumes the
 * complete TeamStrengthModel output rather than relying only
 * on the original FPL baseline.
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

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 0,
        'home_score' => null,
        'away_score' => null
    ],

    [
        'gameweek' => 2,
        'home_team_id' => 1,
        'away_team_id' => 3,
        'finished' => 0,
        'home_score' => null,
        'away_score' => null
    ],

    [
        'gameweek' => 3,
        'home_team_id' => 2,
        'away_team_id' => 1,
        'finished' => 0,
        'home_score' => null,
        'away_score' => null
    ]

];


/*
 * ============================================================
 * TEAM MODELS
 * ============================================================
 *
 * These deliberately differ between baseline and current
 * overall strength.
 */

$modelStrengths = [

    1 => [
        'id' => 1,
        'name' => 'Arsenal',
        'home' => 95.00,
        'away' => 90.00,
        'overall' => 92.50
    ],

    2 => [
        'id' => 2,
        'name' => 'Weak Team',
        'home' => 25.00,
        'away' => 20.00,
        'overall' => 22.50
    ],

    3 => [
        'id' => 3,
        'name' => 'Strong Team',
        'home' => 85.00,
        'away' => 80.00,
        'overall' => 82.50
    ]

];


$fixtureIntelligence =
    new FixtureIntelligence();


/*
 * ============================================================
 * SCENARIO A
 * ============================================================
 *
 * Arsenal at home against Weak Team.
 */

heading('Scenario A: Arsenal Home vs Weak Team');


$arsenalFixtures =
    $fixtureIntelligence->analyseFixtureRun(
        $fixtures,
        $modelStrengths,
        1
    );


testResult(
    'Arsenal fixture analysis returns fixtures',
    count($arsenalFixtures) === 3
);


$fixture1 =
    $arsenalFixtures[0] ?? null;


testResult(
    'First fixture is identified as Home',
    $fixture1 !== null
    &&
    $fixture1['venue'] === 'Home'
);


testResult(
    'Home team is Arsenal',
    $fixture1 !== null
    &&
    $fixture1['home_team'] === 'Arsenal'
);


testResult(
    'Away opponent is Weak Team',
    $fixture1 !== null
    &&
    $fixture1['away_team'] === 'Weak Team'
);


testResult(
    'Arsenal uses its home team model strength',
    $fixture1 !== null
    &&
    abs(
        $fixture1['team_baseline'] - 95.00
    ) < 0.01
);


testResult(
    'Weak Team uses its away strength',
    $fixture1 !== null
    &&
    abs(
        $fixture1['opponent_baseline'] - 20.00
    ) < 0.01
);


/*
 * ============================================================
 * SCENARIO B
 * ============================================================
 *
 * Arsenal away against Weak Team.
 */

heading('Scenario B: Arsenal Away vs Weak Team');


$fixture3 =
    $arsenalFixtures[2] ?? null;


testResult(
    'Third fixture is identified as Away',
    $fixture3 !== null
    &&
    $fixture3['venue'] === 'Away'
);


testResult(
    'Away opponent is Weak Team',
    $fixture3 !== null
    &&
    $fixture3['home_team'] === 'Weak Team'
);


testResult(
    'Arsenal uses its away team model strength',
    $fixture3 !== null
    &&
    abs(
        $fixture3['team_baseline'] - 90.00
    ) < 0.01
);


testResult(
    'Weak Team uses its home strength',
    $fixture3 !== null
    &&
    abs(
        $fixture3['opponent_baseline'] - 25.00
    ) < 0.01
);


/*
 * ============================================================
 * SCENARIO C
 * ============================================================
 *
 * Strong opposition should produce a different matchup
 * from weak opposition.
 */

heading('Scenario C: Opposition Strength Changes Matchup');


$fixture2 =
    $arsenalFixtures[1] ?? null;


testResult(
    'Strong opposition fixture exists',
    $fixture2 !== null
);


testResult(
    'Strong Team is identified as opponent',
    $fixture2 !== null
    &&
    $fixture2['away_team'] === 'Strong Team'
);


testResult(
    'Strong Team uses its away strength',
    $fixture2 !== null
    &&
    abs(
        $fixture2['opponent_baseline'] - 80.00
    ) < 0.01
);


testResult(
    'Weak opposition produces a different matchup',
    $fixture1 !== null
    &&
    $fixture2 !== null
    &&
    $fixture1['matchup']
    !==
    $fixture2['matchup']
);


/*
 * ============================================================
 * SCENARIO D
 * ============================================================
 *
 * Strong opposition should create a more difficult fixture.
 */

heading('Scenario D: Fixture Difficulty');


testResult(
    'Weak opposition fixture has valid difficulty',
    $fixture1 !== null
    &&
    isset($fixture1['difficulty'])
);


testResult(
    'Strong opposition fixture has valid difficulty',
    $fixture2 !== null
    &&
    isset($fixture2['difficulty'])
);


testResult(
    'Strong opposition is more difficult than weak opposition',
    $fixture1 !== null
    &&
    $fixture2 !== null
    &&
    $fixture2['difficulty']
    >
    $fixture1['difficulty']
);


/*
 * ============================================================
 * SCENARIO E
 * ============================================================
 *
 * Fixture score should respond to team strength.
 */

heading('Scenario E: Fixture Score');


testResult(
    'Weak opposition produces a valid fixture score',
    $fixture1 !== null
    &&
    isset($fixture1['fixture_score'])
);


testResult(
    'Strong opposition produces a valid fixture score',
    $fixture2 !== null
    &&
    isset($fixture2['fixture_score'])
);


testResult(
    'Fixture scores are not identical',
    $fixture1 !== null
    &&
    $fixture2 !== null
    &&
    $fixture1['fixture_score']
    !==
    $fixture2['fixture_score']
);


/*
 * ============================================================
 * SCENARIO F
 * ============================================================
 *
 * Model strength should actually flow through the system.
 */

heading('Scenario F: Complete Team Model Integration');


testResult(
    'Arsenal home strength is available',
    $modelStrengths[1]['home'] === 95.00
);


testResult(
    'Arsenal away strength is available',
    $modelStrengths[1]['away'] === 90.00
);


testResult(
    'Arsenal overall strength is available',
    $modelStrengths[1]['overall'] === 92.50
);


testResult(
    'Fixture Intelligence consumes team model data',
    $fixture1 !== null
    &&
    $fixture1['team_baseline'] === 95.00
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Fixture Intelligence Team Model Integration Summary<br>";
echo "============================================<br>";

echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}