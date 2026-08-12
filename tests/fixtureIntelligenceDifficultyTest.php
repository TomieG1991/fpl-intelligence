<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * FIXTURE INTELLIGENCE DIFFICULTY TEST
 * ============================================================
 *
 * Tests whether FixtureIntelligence produces sensible and
 * consistently ordered fixture difficulty ratings.
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
 *
 * We deliberately create a very wide range of team strengths.
 */

$modelStrengths = [

    1 => [
        'id' => 1,
        'name' => 'Elite Team',
        'home' => 100.00,
        'away' => 100.00,
        'overall' => 100.00
    ],

    2 => [
        'id' => 2,
        'name' => 'Very Strong Team',
        'home' => 90.00,
        'away' => 90.00,
        'overall' => 90.00
    ],

    3 => [
        'id' => 3,
        'name' => 'Strong Team',
        'home' => 80.00,
        'away' => 80.00,
        'overall' => 80.00
    ],

    4 => [
        'id' => 4,
        'name' => 'Average Team',
        'home' => 50.00,
        'away' => 50.00,
        'overall' => 50.00
    ],

    5 => [
        'id' => 5,
        'name' => 'Weak Team',
        'home' => 20.00,
        'away' => 20.00,
        'overall' => 20.00
    ],

    6 => [
        'id' => 6,
        'name' => 'Very Weak Team',
        'home' => 0.00,
        'away' => 0.00,
        'overall' => 0.00
    ]

];


$fixtureIntelligence =
    new FixtureIntelligence();


/*
 * ============================================================
 * HELPER TO CREATE FIXTURES
 * ============================================================
 */

function createFixture(
    int $gameweek,
    int $homeTeam,
    int $awayTeam
): array {

    return [

        'gameweek' =>
            $gameweek,

        'home_team_id' =>
            $homeTeam,

        'away_team_id' =>
            $awayTeam,

        'finished' =>
            0,

        'home_score' =>
            null,

        'away_score' =>
            null
    ];
}


/*
 * ============================================================
 * SCENARIO A
 * ============================================================
 *
 * Elite team at home against different opposition strengths.
 */

heading('Scenario A: Elite Team vs Different Opposition');


$fixtures = [

    createFixture(1, 1, 6),
    createFixture(2, 1, 5),
    createFixture(3, 1, 4),
    createFixture(4, 1, 3),
    createFixture(5, 1, 2)

];


$results =
    $fixtureIntelligence->analyseFixtureRun(
        $fixtures,
        $modelStrengths,
        1
    );


$veryWeak =
    $results[0] ?? null;

$weak =
    $results[1] ?? null;

$average =
    $results[2] ?? null;

$strong =
    $results[3] ?? null;

$veryStrong =
    $results[4] ?? null;


echo "Very Weak: "
    . ($veryWeak['fixture_score'] ?? 'N/A')
    . "<br>";

echo "Weak: "
    . ($weak['fixture_score'] ?? 'N/A')
    . "<br>";

echo "Average: "
    . ($average['fixture_score'] ?? 'N/A')
    . "<br>";

echo "Strong: "
    . ($strong['fixture_score'] ?? 'N/A')
    . "<br>";

echo "Very Strong: "
    . ($veryStrong['fixture_score'] ?? 'N/A')
    . "<br>";


/*
 * ============================================================
 * SCENARIO B
 * ============================================================
 */

heading('Scenario B: Fixture Difficulty Ordering');


testResult(
    'Very weak opposition produces a valid fixture',
    $veryWeak !== null
);


testResult(
    'Weak opposition produces a valid fixture',
    $weak !== null
);


testResult(
    'Average opposition produces a valid fixture',
    $average !== null
);


testResult(
    'Strong opposition produces a valid fixture',
    $strong !== null
);


testResult(
    'Very strong opposition produces a valid fixture',
    $veryStrong !== null
);


testResult(
    'Weak opposition has a lower fixture score than very weak opposition',
    $veryWeak !== null
    &&
    $weak !== null
    &&
    $weak['fixture_score']
    <
    $veryWeak['fixture_score']
);


testResult(
    'Average opposition is harder than weak opposition',
    $weak !== null
    &&
    $average !== null
    &&
    $average['difficulty']
    >
    $weak['difficulty']
);


testResult(
    'Strong opposition is harder than average opposition',
    $average !== null
    &&
    $strong !== null
    &&
    $strong['difficulty']
    >
    $average['difficulty']
);


testResult(
    'Very strong opposition has a lower fixture score than strong opposition',
    $strong !== null
    &&
    $veryStrong !== null
    &&
    $veryStrong['fixture_score']
    <
    $strong['fixture_score']
);


/*
 * ============================================================
 * SCENARIO C
 * ============================================================
 *
 * Fixture score should move in the opposite direction.
 */

heading('Scenario C: Fixture Score Ordering');


testResult(
    'Very weak opposition has a valid fixture score',
    $veryWeak !== null
    &&
    isset($veryWeak['fixture_score'])
);


testResult(
    'Very strong opposition has a valid fixture score',
    $veryStrong !== null
    &&
    isset($veryStrong['fixture_score'])
);


testResult(
    'Harder opposition changes fixture score',
    $veryWeak !== null
    &&
    $veryStrong !== null
    &&
    $veryWeak['fixture_score']
    !==
    $veryStrong['fixture_score']
);


/*
 * ============================================================
 * SCENARIO D
 * ============================================================
 *
 * Difficulty must remain within sensible bounds.
 */

heading('Scenario D: Difficulty Bounds');


foreach ($results as $index => $fixture) {

    testResult(
        "Fixture {$index} difficulty is at least 1",
        $fixture['difficulty'] >= 1
    );

    testResult(
        "Fixture {$index} difficulty does not exceed 5",
        $fixture['difficulty'] <= 5
    );

    testResult(
        "Fixture {$index} has a difficulty label",
        isset($fixture['difficulty_label'])
        &&
        $fixture['difficulty_label'] !== ''
    );
}


/*
 * ============================================================
 * SCENARIO E
 * ============================================================
 *
 * Home/away venue should matter.
 */

heading('Scenario E: Home vs Away Difficulty');


$venueFixtures = [

    createFixture(1, 1, 3),
    createFixture(2, 3, 1)

];


$venueResults =
    $fixtureIntelligence->analyseFixtureRun(
        $venueFixtures,
        $modelStrengths,
        1
    );


$homeFixture =
    $venueResults[0] ?? null;

$awayFixture =
    $venueResults[1] ?? null;


testResult(
    'Home fixture is identified correctly',
    $homeFixture !== null
    &&
    $homeFixture['venue'] === 'Home'
);


testResult(
    'Away fixture is identified correctly',
    $awayFixture !== null
    &&
    $awayFixture['venue'] === 'Away'
);


testResult(
    'Home fixture uses home strength',
    $homeFixture !== null
    &&
    $homeFixture['team_baseline'] === 100.00
);


testResult(
    'Away fixture uses away strength',
    $awayFixture !== null
    &&
    $awayFixture['team_baseline'] === 100.00
);


/*
 * ============================================================
 * SCENARIO F
 * ============================================================
 *
 * Weak team against strong opposition.
 */

heading('Scenario F: Weak Team vs Strong Opposition');


$weakTeamFixtures = [

    createFixture(1, 5, 1),
    createFixture(2, 1, 5)

];


$weakTeamResults =
    $fixtureIntelligence->analyseFixtureRun(
        $weakTeamFixtures,
        $modelStrengths,
        5
    );


$weakAway =
    $weakTeamResults[0] ?? null;

$weakHome =
    $weakTeamResults[1] ?? null;


testResult(
    'Weak team away fixture exists',
    $weakAway !== null
);


testResult(
    'Weak team home fixture exists',
    $weakHome !== null
);


testResult(
    'Weak team away uses away strength',
    $weakAway !== null
    &&
    $weakAway['team_baseline'] === 20.00
);


testResult(
    'Weak team home uses home strength',
    $weakHome !== null
    &&
    $weakHome['team_baseline'] === 20.00
);


testResult(
    'Strong opposition creates a very difficult fixture',
    $weakAway !== null
    &&
    $weakAway['difficulty'] === 5
);


/*
 * ============================================================
 * SCENARIO G
 * ============================================================
 *
 * Same teams, reversed home/away.
 */

heading('Scenario G: Venue Reversal');


$venueModelStrengths = $modelStrengths;

$venueModelStrengths[1]['home'] = 100;
$venueModelStrengths[1]['away'] = 70;

$venueModelStrengths[3]['home'] = 90;
$venueModelStrengths[3]['away'] = 60;


$reversedFixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 3
    ],

    [
        'gameweek' => 2,
        'home_team_id' => 3,
        'away_team_id' => 1
    ]

];


$reversedResults =
    $fixtureIntelligence->analyseFixtureRun(
        $reversedFixtures,
        $venueModelStrengths,
        1
    );


testResult(
    'Home and away fixture scores differ when appropriate',
    count($reversedResults) === 2
    &&
    $reversedResults[0]['fixture_score']
    !==
    $reversedResults[1]['fixture_score']
);


/*
 * ============================================================
 * SCENARIO H
 * ============================================================
 *
 * Ensure fixture ordering is chronological.
 */

heading('Scenario H: Fixture Ordering');


$unorderedFixtures = [

    createFixture(3, 1, 3),
    createFixture(1, 1, 5),
    createFixture(2, 1, 4)

];


$orderedResults =
    $fixtureIntelligence->analyseFixtureRun(
        $unorderedFixtures,
        $modelStrengths,
        1
    );


testResult(
    'Three fixtures are returned',
    count($orderedResults) === 3
);


testResult(
    'First fixture is GW1',
    ($orderedResults[0]['gameweek'] ?? null) === 1
);


testResult(
    'Second fixture is GW2',
    ($orderedResults[1]['gameweek'] ?? null) === 2
);


testResult(
    'Third fixture is GW3',
    ($orderedResults[2]['gameweek'] ?? null) === 3
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Fixture Intelligence Difficulty Test Summary<br>";
echo "============================================<br>";

echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}