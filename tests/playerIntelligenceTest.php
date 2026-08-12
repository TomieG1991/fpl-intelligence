<?php

require_once __DIR__ . '/../classes/autoload.php';


$playerIntelligence = new PlayerIntelligence();
$fixtureIntelligence = new FixtureIntelligence();

$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function passTest(
    string $message,
    bool $condition
): void {

    global $passed, $failed;

    if ($condition) {

        echo "PASS: {$message}<br>";
        $passed++;

    } else {

        echo "FAIL: {$message}<br>";
        $failed++;
    }
}


function section(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";
    echo "{$title}<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * TEST DATA
 * ============================================================
 *
 * Team strengths deliberately include different home/away
 * ratings so venue handling can be verified.
 */

$teamStrengths = [

    1 => [
        'id' => 1,
        'name' => 'Arsenal',
        'home' => 90,
        'away' => 80,
        'overall' => 85
    ],

    2 => [
        'id' => 2,
        'name' => 'Weak Team',
        'home' => 20,
        'away' => 15,
        'overall' => 17.5
    ],

    3 => [
        'id' => 3,
        'name' => 'Strong Team',
        'home' => 85,
        'away' => 75,
        'overall' => 80
    ],

    4 => [
        'id' => 4,
        'name' => 'Average Team',
        'home' => 55,
        'away' => 50,
        'overall' => 52.5
    ]
];


/*
 * ============================================================
 * FIXTURE DATA
 * ============================================================
 */

$fixtures = [

    [
        'id' => 1,
        'fpl_fixture_id' => 1001,
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'kickoff_time' => null,
        'finished' => 0,
        'finished_provisional' => 0,
        'home_score' => null,
        'away_score' => null
    ],

    [
        'id' => 2,
        'fpl_fixture_id' => 1002,
        'gameweek' => 2,
        'home_team_id' => 3,
        'away_team_id' => 1,
        'kickoff_time' => null,
        'finished' => 0,
        'finished_provisional' => 0,
        'home_score' => null,
        'away_score' => null
    ],

    [
        'id' => 3,
        'fpl_fixture_id' => 1003,
        'gameweek' => 3,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'kickoff_time' => null,
        'finished' => 0,
        'finished_provisional' => 0,
        'home_score' => null,
        'away_score' => null
    ],

    [
        'id' => 4,
        'fpl_fixture_id' => 1004,
        'gameweek' => 4,
        'home_team_id' => 4,
        'away_team_id' => 1,
        'kickoff_time' => null,
        'finished' => 0,
        'finished_provisional' => 0,
        'home_score' => null,
        'away_score' => null
    ],

    [
        'id' => 5,
        'fpl_fixture_id' => 1005,
        'gameweek' => 5,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'kickoff_time' => null,
        'finished' => 0,
        'finished_provisional' => 0,
        'home_score' => null,
        'away_score' => null
    ]
];


/*
 * ============================================================
 * PLAYER DATA
 * ============================================================
 */

$player = [

    'id' => 999,

    'fpl_player_id' => 9999,

    'team_id' => 1,

    'first_name' => 'Test',

    'second_name' => 'Forward',

    'web_name' => 'Test Forward',

    'position' => 'FWD'
];


/*
 * ============================================================
 * SCENARIO A
 * Fixture Run Exists
 * ============================================================
 */

section(
    'Scenario A: Player Fixture Run'
);

$model =
    $playerIntelligence->analyseFixtureRun(
        $player,
        $fixtures,
        $teamStrengths,
        $fixtureIntelligence
    );

echo "Player: "
    . $model['player_name']
    . "<br>";

echo "Team ID: "
    . $model['team_id']
    . "<br>";

echo "Fixtures Found: "
    . count($model['fixtures'])
    . "<br>";

passTest(
    'Player fixture analysis returns a model',
    is_array($model)
);

passTest(
    'Player name is preserved',
    $model['player_name'] === 'Test Forward'
);

passTest(
    'Player team ID is preserved',
    $model['team_id'] === 1
);

passTest(
    'Player fixture run contains five fixtures',
    count($model['fixtures']) === 5
);


/*
 * ============================================================
 * SCENARIO B
 * Home/Away Fixture Detection
 * ============================================================
 */

section(
    'Scenario B: Home and Away Fixtures'
);

$firstFixture =
    $model['fixtures'][0];

$secondFixture =
    $model['fixtures'][1];

echo "GW1 Venue: "
    . $firstFixture['venue']
    . "<br>";

echo "GW2 Venue: "
    . $secondFixture['venue']
    . "<br>";

passTest(
    'GW1 is identified as Home',
    $firstFixture['venue'] === 'Home'
);

passTest(
    'GW2 is identified as Away',
    $secondFixture['venue'] === 'Away'
);

passTest(
    'GW1 uses Arsenal home strength',
    $firstFixture['team_baseline'] === 90
);

passTest(
    'GW2 uses Arsenal away strength',
    $secondFixture['team_baseline'] === 80
);


/*
 * ============================================================
 * SCENARIO C
 * Opposition Strength
 * ============================================================
 */

section(
    'Scenario C: Opposition Strength'
);

echo "GW1 Opposition: "
    . $firstFixture['opponent_baseline']
    . "<br>";

echo "GW2 Opposition: "
    . $secondFixture['opponent_baseline']
    . "<br>";

passTest(
    'GW1 uses opponent away strength',
    $firstFixture['opponent_baseline'] === 15
);

passTest(
    'GW2 uses opponent home strength',
    $secondFixture['opponent_baseline'] === 85
);

passTest(
    'Different opposition produces different matchup',
    $firstFixture['matchup']
    !==
    $secondFixture['matchup']
);


/*
 * ============================================================
 * SCENARIO D
 * Rolling Averages
 * ============================================================
 */

section(
    'Scenario D: Rolling Averages'
);

echo "Next 5 Average: "
    . number_format(
        $model['rolling_averages']['next_5'],
        2
    )
    . "<br>";

echo "Next 6 Average: "
    . (
        $model['rolling_averages']['next_6'] === null
            ? 'NULL'
            : number_format(
                $model['rolling_averages']['next_6'],
                2
            )
    )
    . "<br>";

passTest(
    'Next 5 rolling average is calculated',
    $model['rolling_averages']['next_5'] !== null
);

passTest(
    'Next 6 rolling average is null with only five fixtures',
    $model['rolling_averages']['next_6'] === null
);

passTest(
    'Next 8 rolling average is null with only five fixtures',
    $model['rolling_averages']['next_8'] === null
);

passTest(
    'Next 10 rolling average is null with only five fixtures',
    $model['rolling_averages']['next_10'] === null
);


/*
 * ============================================================
 * SCENARIO E
 * Best/Worst Runs
 * ============================================================
 */

section(
    'Scenario E: Best and Worst Fixture Runs'
);

echo "Best Run: "
    . (
        $model['best_run'] === null
            ? 'NULL'
            : (
                'GW'
                . $model['best_run']['start_gameweek']
                . '-GW'
                . $model['best_run']['end_gameweek']
            )
    )
    . "<br>";

echo "Worst Run: "
    . (
        $model['worst_run'] === null
            ? 'NULL'
            : (
                'GW'
                . $model['worst_run']['start_gameweek']
                . '-GW'
                . $model['worst_run']['end_gameweek']
            )
    )
    . "<br>";

passTest(
    'Best run is calculated when five fixtures exist',
    is_array($model['best_run'])
);

passTest(
    'Worst run is calculated when five fixtures exist',
    is_array($model['worst_run'])
);

passTest(
    'Best run contains five fixtures',
    count($model['best_run']['fixtures']) === 5
);

passTest(
    'Worst run contains five fixtures',
    count($model['worst_run']['fixtures']) === 5
);


/*
 * ============================================================
 * SCENARIO F
 * Trend
 * ============================================================
 */

section(
    'Scenario F: Fixture Trend'
);

echo "Trend: "
    . $model['trend']
    . "<br>";

passTest(
    'Fixture trend returns a valid result',
    in_array(
        $model['trend'],
        [
            'Improving',
            'Declining',
            'Stable',
            'Insufficient Data'
        ],
        true
    )
);

passTest(
    'Five fixtures provide enough data for a trend',
    $model['trend'] !== 'Insufficient Data'
);


/*
 * ============================================================
 * SCENARIO G
 * Missing Team ID
 * ============================================================
 */

section(
    'Scenario G: Missing Team ID Handling'
);

$invalidPlayer = [

    'fpl_player_id' => 8888,

    'first_name' => 'Invalid',

    'second_name' => 'Player'
];

$exceptionThrown = false;

try {

    $playerIntelligence->analyseFixtureRun(
        $invalidPlayer,
        $fixtures,
        $teamStrengths,
        $fixtureIntelligence
    );

} catch (InvalidArgumentException $e) {

    $exceptionThrown = true;

    echo "Exception: "
        . $e->getMessage()
        . "<br>";
}

passTest(
    'Missing team ID throws an InvalidArgumentException',
    $exceptionThrown
);


/*
 * ============================================================
 * SCENARIO H
 * Empty Fixture Run
 * ============================================================
 */

section(
    'Scenario H: Empty Fixture Run'
);

$emptyFixtureModel =
    $playerIntelligence->analyseFixtureRun(
        $player,
        [],
        $teamStrengths,
        $fixtureIntelligence
    );

echo "Fixtures Found: "
    . count($emptyFixtureModel['fixtures'])
    . "<br>";

echo "Trend: "
    . $emptyFixtureModel['trend']
    . "<br>";

passTest(
    'Empty fixture list produces zero fixtures',
    count($emptyFixtureModel['fixtures']) === 0
);

passTest(
    'Empty fixture list produces null rolling average',
    $emptyFixtureModel['rolling_averages']['next_5'] === null
);

passTest(
    'Empty fixture list produces no best run',
    $emptyFixtureModel['best_run'] === null
);

passTest(
    'Empty fixture list produces no worst run',
    $emptyFixtureModel['worst_run'] === null
);

passTest(
    'Empty fixture list reports insufficient data',
    $emptyFixtureModel['trend'] === 'Insufficient Data'
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

section(
    'Front-End Friendly Player Fixture Intelligence'
);

echo "Player: "
    . $model['player_name']
    . "<br>";

echo "Fixtures Analysed: "
    . count($model['fixtures'])
    . "<br>";

echo "Next 5 Average: "
    . number_format(
        $model['rolling_averages']['next_5'],
        2
    )
    . "<br>";

echo "Best Run: "
    . (
        $model['best_run']['start_gameweek']
        . " - GW"
        . $model['best_run']['end_gameweek']
    )
    . "<br>";

echo "Worst Run: "
    . (
        $model['worst_run']['start_gameweek']
        . " - GW"
        . $model['worst_run']['end_gameweek']
    )
    . "<br>";

echo "Trend: "
    . $model['trend']
    . "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Intelligence Test Summary<br>";
echo "============================================<br>";

echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}