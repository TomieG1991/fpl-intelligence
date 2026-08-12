<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * TEAM STRENGTH VENUE HISTORICAL TEST
 * ============================================================
 *
 * Tests the interaction between:
 *
 * - Home fixtures
 * - Away fixtures
 * - Home baseline strength
 * - Away baseline strength
 * - Opposition strength
 * - Team performance
 * - Combined team strength
 *
 * This test does NOT modify the database.
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


/*
 * ============================================================
 * TEST BASELINES
 * ============================================================
 *
 * Deliberately give Arsenal different home and away
 * baseline ratings so we can verify that the correct
 * baseline is being used.
 */

$baselines = [

    1 => [
        'id' => 1,
        'name' => 'Arsenal',
        'home' => 100,
        'away' => 80,
        'overall' => 90
    ],

    2 => [
        'id' => 2,
        'name' => 'Weak Team',
        'home' => 30,
        'away' => 20,
        'overall' => 25
    ],

    3 => [
        'id' => 3,
        'name' => 'Average Team',
        'home' => 55,
        'away' => 50,
        'overall' => 52.5
    ],

    4 => [
        'id' => 4,
        'name' => 'Strong Team',
        'home' => 85,
        'away' => 80,
        'overall' => 82.5
    ]
];


/*
 * ============================================================
 * CREATE MODELS
 * ============================================================
 */

$teamPerformance =
    new TeamPerformance();

$teamStrengthModel =
    new TeamStrengthModel();

$oppositionModel =
    new OppositionAdjustedPerformance();


/*
 * ============================================================
 * SCENARIO A
 * ============================================================
 *
 * Arsenal wins 2-0 at home against a weak team.
 *
 * Arsenal is HOME.
 *
 * Therefore the opponent's AWAY strength should be used.
 *
 * Weak Team away strength = 20.
 *
 * Expected performance:
 *
 * 75 - (20 * 0.5) = 65
 *
 * Actual performance:
 *
 * 100
 *
 * Delta:
 *
 * +35
 */


/*
 * ============================================================
 * SCENARIO A: HOME WIN
 * ============================================================
 */

echo "<h3>Scenario A: Arsenal 2-0 Weak Team at Home</h3>";


$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ]
];


$performance =
    $teamPerformance->analyse(
        $fixtures,
        1
    );


$adjusted =
    $oppositionModel->analyse(
        $fixtures,
        $baselines,
        1
    );


$teamModel =
    $teamStrengthModel->buildTeamModel(
        $baselines[1],
        $performance,
        $teamPerformance
    );


testResult(
    'Home win records one match',
    $performance['played'] === 1
);

testResult(
    'Home win records one home match',
    $performance['home_played'] === 1
);

testResult(
    'Home win records zero away matches',
    $performance['away_played'] === 0
);

testResult(
    'Home fixture is identified as Home',
    $adjusted['matches'][0]['venue'] === 'Home'
);

testResult(
    'Home fixture uses opponent away strength',
    $adjusted['matches'][0]['opponent_strength'] === 20.0
);

testResult(
    'Home win produces three points',
    $performance['points'] === 3
);

testResult(
    'Home win produces positive adjusted delta',
    $adjusted['average_delta'] > 0
);


/*
 * ============================================================
 * SCENARIO B
 * ============================================================
 *
 * Arsenal wins 2-0 AWAY against the same weak team.
 *
 * Arsenal is AWAY.
 *
 * Therefore the opponent's HOME strength should be used.
 *
 * Weak Team home strength = 30.
 *
 * Expected performance:
 *
 * 75 - (30 * 0.5) = 60
 *
 * Delta:
 *
 * +40
 */


/*
 * ============================================================
 * SCENARIO B: AWAY WIN
 * ============================================================
 */

echo "<h3>Scenario B: Arsenal 2-0 Weak Team Away</h3>";


$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 2,
        'away_team_id' => 1,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ]
];


$performance =
    $teamPerformance->analyse(
        $fixtures,
        1
    );


$adjusted =
    $oppositionModel->analyse(
        $fixtures,
        $baselines,
        1
    );


testResult(
    'Away win records one match',
    $performance['played'] === 1
);

testResult(
    'Away win records zero home matches',
    $performance['home_played'] === 0
);

testResult(
    'Away win records one away match',
    $performance['away_played'] === 1
);

testResult(
    'Away fixture is identified as Away',
    $adjusted['matches'][0]['venue'] === 'Away'
);

testResult(
    'Away fixture uses opponent home strength',
    $adjusted['matches'][0]['opponent_strength'] === 30.0
);

testResult(
    'Away win produces three points',
    $performance['points'] === 3
);

testResult(
    'Away win produces positive adjusted delta',
    $adjusted['average_delta'] > 0
);


/*
 * ============================================================
 * SCENARIO C
 * ============================================================
 *
 * Verify that the venue changes the opposition adjustment.
 *
 * Home fixture:
 *
 * opponent away = 20
 * expected = 65
 * delta = +35
 *
 * Away fixture:
 *
 * opponent home = 30
 * expected = 60
 * delta = +40
 */


/*
 * ============================================================
 * SCENARIO C: VENUE CHANGES OPPOSITION ADJUSTMENT
 * ============================================================
 */

echo "<h3>Scenario C: Venue Changes Opposition Adjustment</h3>";


$homeDelta =
    $oppositionModel->analyse(
        [
            [
                'gameweek' => 1,
                'home_team_id' => 1,
                'away_team_id' => 2,
                'finished' => 1,
                'home_score' => 2,
                'away_score' => 0
            ]
        ],
        $baselines,
        1
    );


$awayDelta =
    $oppositionModel->analyse(
        [
            [
                'gameweek' => 1,
                'home_team_id' => 2,
                'away_team_id' => 1,
                'finished' => 1,
                'home_score' => 0,
                'away_score' => 2
            ]
        ],
        $baselines,
        1
    );


testResult(
    'Home and away opposition adjustments differ',
    $homeDelta['average_delta']
    !==
    $awayDelta['average_delta']
);

testResult(
    'Away win against this opponent produces higher adjustment',
    $awayDelta['average_delta']
    >
    $homeDelta['average_delta']
);


/*
 * ============================================================
 * SCENARIO D
 * ============================================================
 *
 * Arsenal loses at home to a strong team.
 *
 * Strong Team away strength = 80.
 *
 * Expected:
 *
 * 75 - 40 = 35
 *
 * Actual:
 *
 * 0
 *
 * Delta:
 *
 * -35
 */


/*
 * ============================================================
 * SCENARIO D: HOME LOSS
 * ============================================================
 */

echo "<h3>Scenario D: Arsenal 0-2 Strong Team at Home</h3>";


$fixtures = [

    [
        'gameweek' => 2,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ]
];


$adjusted =
    $oppositionModel->analyse(
        $fixtures,
        $baselines,
        1
    );


testResult(
    'Home loss is identified as Home',
    $adjusted['matches'][0]['venue'] === 'Home'
);

testResult(
    'Home loss uses opponent away strength',
    $adjusted['matches'][0]['opponent_strength'] === 80.0
);

testResult(
    'Home loss produces negative delta',
    $adjusted['average_delta'] < 0
);

testResult(
    'Home loss produces expected -35 delta',
    $adjusted['average_delta'] === -35.0
);


/*
 * ============================================================
 * SCENARIO E
 * ============================================================
 *
 * Arsenal loses away to a strong team.
 *
 * Strong Team home strength = 85.
 *
 * Expected:
 *
 * 75 - 42.5 = 32.5
 *
 * Delta:
 *
 * -32.5
 */


/*
 * ============================================================
 * SCENARIO E: AWAY LOSS
 * ============================================================
 */

echo "<h3>Scenario E: Arsenal 0-2 Strong Team Away</h3>";


$fixtures = [

    [
        'gameweek' => 2,
        'home_team_id' => 4,
        'away_team_id' => 1,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ]
];


$adjusted =
    $oppositionModel->analyse(
        $fixtures,
        $baselines,
        1
    );


testResult(
    'Away loss is identified as Away',
    $adjusted['matches'][0]['venue'] === 'Away'
);

testResult(
    'Away loss uses opponent home strength',
    $adjusted['matches'][0]['opponent_strength'] === 85.0
);

testResult(
    'Away loss produces negative delta',
    $adjusted['average_delta'] < 0
);

testResult(
    'Away loss produces expected -32.5 delta',
    $adjusted['average_delta'] === -32.5
);


/*
 * ============================================================
 * SCENARIO F
 * ============================================================
 *
 * Verify Arsenal's different home and away baselines
 * are retained by TeamStrengthModel.
 */


/*
 * ============================================================
 * SCENARIO F: HOME/AWAY BASELINE SEPARATION
 * ============================================================
 */

echo "<h3>Scenario F: Home/Away Baseline Separation</h3>";


$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ]
];


$performance =
    $teamPerformance->analyse(
        $fixtures,
        1
    );


$model =
    $teamStrengthModel->buildTeamModel(
        $baselines[1],
        $performance,
        $teamPerformance
    );


testResult(
    'Home baseline remains 100',
    $model['baseline_home'] === 100
);

testResult(
    'Away baseline remains 80',
    $model['baseline_away'] === 80
);

testResult(
    'Overall baseline remains 90',
    $model['baseline_overall'] === 90
);

testResult(
    'Home rating is higher than away rating',
    $model['home'] > $model['away']
);


/*
 * ============================================================
 * SCENARIO G
 * ============================================================
 *
 * Mixed home and away fixtures.
 *
 * Arsenal:
 *
 * GW1 Home win
 * GW2 Away win
 * GW3 Home loss
 * GW4 Away draw
 *
 * This verifies that both venues contribute to the same
 * historical performance sample.
 */


/*
 * ============================================================
 * SCENARIO G: MIXED HOME/AWAY HISTORY
 * ============================================================
 */

echo "<h3>Scenario G: Mixed Home/Away History</h3>";


$fixtures = [

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
        'home_team_id' => 3,
        'away_team_id' => 1,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 1
    ],

    [
        'gameweek' => 3,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 4,
        'home_team_id' => 4,
        'away_team_id' => 1,
        'finished' => 1,
        'home_score' => 1,
        'away_score' => 1
    ]
];


$performance =
    $teamPerformance->analyse(
        $fixtures,
        1
    );


testResult(
    'Mixed history contains four matches',
    $performance['played'] === 4
);

testResult(
    'Mixed history contains two home matches',
    $performance['home_played'] === 2
);

testResult(
    'Mixed history contains two away matches',
    $performance['away_played'] === 2
);

testResult(
    'Mixed history records two wins',
    $performance['wins'] === 2
);

testResult(
    'Mixed history records one loss',
    $performance['losses'] === 1
);

testResult(
    'Mixed history records one draw',
    $performance['draws'] === 1
);

testResult(
    'Mixed history records seven points',
    $performance['points'] === 7
);


/*
 * ============================================================
 * SCENARIO H
 * ============================================================
 *
 * Ensure incomplete fixtures are ignored regardless of venue.
 */


/*
 * ============================================================
 * SCENARIO H: INCOMPLETE FIXTURES
 * ============================================================
 */

echo "<h3>Scenario H: Incomplete Home/Away Fixtures</h3>";


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
        'home_team_id' => 3,
        'away_team_id' => 1,
        'finished' => 0,
        'home_score' => null,
        'away_score' => null
    ]
];


$performance =
    $teamPerformance->analyse(
        $fixtures,
        1
    );


testResult(
    'Unfinished home fixture is ignored',
    $performance['home_played'] === 0
);

testResult(
    'Unfinished away fixture is ignored',
    $performance['away_played'] === 0
);

testResult(
    'No completed matches remain',
    $performance['played'] === 0
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Team Strength Venue Historical Test Summary<br>";
echo "============================================<br>";

echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}