<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * TEAM STRENGTH MODEL INTEGRATION TEST
 * ============================================================
 *
 * Tests the complete TeamStrengthModel pipeline using
 * multiple teams and multiple historical scenarios.
 *
 * This test does not modify the real database.
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
 * TEST MODELS
 * ============================================================
 */

$teamStrength =
    new TeamStrength();

$teamPerformance =
    new TeamPerformance();

$teamStrengthModel =
    new TeamStrengthModel();


/*
 * ============================================================
 * BASELINE TEAMS
 * ============================================================
 *
 * These represent four deliberately different teams.
 *
 * Arsenal       = strongest
 * Liverpool     = strong
 * Mid-table     = average
 * Weak Team     = weakest
 */

$teams = [

    [
        'id' => 1,
        'name' => 'Arsenal',
        'strength_overall_home' => 100,
        'strength_overall_away' => 100
    ],

    [
        'id' => 2,
        'name' => 'Liverpool',
        'strength_overall_home' => 90,
        'strength_overall_away' => 90
    ],

    [
        'id' => 3,
        'name' => 'Mid-table',
        'strength_overall_home' => 70,
        'strength_overall_away' => 70
    ],

    [
        'id' => 4,
        'name' => 'Weak Team',
        'strength_overall_home' => 50,
        'strength_overall_away' => 50
    ]

];


$baselines =
    $teamStrength->calculateTeamStrengths(
        $teams
    );


/*
 * ============================================================
 * SCENARIO A
 * Baseline generation
 * ============================================================
 */

heading('Scenario A: Baseline Generation');


testResult(
    'Strongest team receives 100 home baseline',
    $baselines[1]['home'] === 100.0
);

testResult(
    'Strongest team receives 100 away baseline',
    $baselines[1]['away'] === 100.0
);

testResult(
    'Weakest team receives 0 home baseline',
    $baselines[4]['home'] === 0.0
);

testResult(
    'Weakest team receives 0 away baseline',
    $baselines[4]['away'] === 0.0
);

testResult(
    'Liverpool baseline is above Mid-table',
    $baselines[2]['overall']
    >
    $baselines[3]['overall']
);

testResult(
    'Mid-table baseline is above Weak Team',
    $baselines[3]['overall']
    >
    $baselines[4]['overall']
);


/*
 * ============================================================
 * SCENARIO B
 * No historical performance
 * ============================================================
 */

heading('Scenario B: No Historical Performance');


$emptyPerformance =
    $teamPerformance->analyse(
        [],
        1
    );


$arsenalModel =
    $teamStrengthModel->buildTeamModel(
        $baselines[1],
        $emptyPerformance,
        $teamPerformance
    );


echo "Arsenal Baseline: "
    . number_format(
        $baselines[1]['overall'],
        2
    )
    . "<br>";

echo "Arsenal Overall: "
    . number_format(
        $arsenalModel['overall'],
        2
    )
    . "<br>";


testResult(
    'No matches preserves baseline',
    $arsenalModel['overall']
    ===
    $baselines[1]['overall']
);

testResult(
    'No matches produces zero played',
    $arsenalModel['played'] === 0
);

testResult(
    'No matches produces null performance rating',
    $arsenalModel['performance_rating'] === null
);


/*
 * ============================================================
 * SCENARIO C
 * Strong team wins
 * ============================================================
 */

heading('Scenario C: Strong Team Performing Well');


$strongTeamFixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 2,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 3,
        'away_score' => 0
    ],

    [
        'gameweek' => 3,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ]

];


$strongPerformance =
    $teamPerformance->analyse(
        $strongTeamFixtures,
        1
    );


$strongModel =
    $teamStrengthModel->buildTeamModel(
        $baselines[1],
        $strongPerformance,
        $teamPerformance
    );


echo "Performance: "
    . number_format(
        $strongModel['performance_rating'],
        2
    )
    . "<br>";

echo "Overall: "
    . number_format(
        $strongModel['overall'],
        2
    )
    . "<br>";


testResult(
    'Strong team analyses three matches',
    $strongModel['played'] === 3
);

testResult(
    'Strong team produces a performance rating',
    $strongModel['performance_rating'] !== null
);

testResult(
    'Strong team rating does not exceed 100',
    $strongModel['overall'] <= 100
);

testResult(
    'Strong team remains highly rated',
    $strongModel['overall'] > 90
);


/*
 * ============================================================
 * SCENARIO D
 * Strong team performs badly
 * ============================================================
 */

heading('Scenario D: Strong Team Performing Badly');


$poorStrongTeamFixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 2,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ],

    [
        'gameweek' => 3,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ]

];


$poorPerformance =
    $teamPerformance->analyse(
        $poorStrongTeamFixtures,
        1
    );


$poorModel =
    $teamStrengthModel->buildTeamModel(
        $baselines[1],
        $poorPerformance,
        $teamPerformance
    );


echo "Performance: "
    . number_format(
        $poorModel['performance_rating'],
        2
    )
    . "<br>";

echo "Overall: "
    . number_format(
        $poorModel['overall'],
        2
    )
    . "<br>";


testResult(
    'Poorly performing strong team produces a rating',
    $poorModel['performance_rating'] !== null
);

testResult(
    'Poor performance reduces the team rating',
    $poorModel['overall']
    <
    $baselines[1]['overall']
);

testResult(
    'Poorly performing team remains non-negative',
    $poorModel['overall'] >= 0
);


/*
 * ============================================================
 * SCENARIO E
 * Weak team overperforming
 * ============================================================
 */

heading('Scenario E: Weak Team Overperforming');


$weakTeamFixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 4,
        'away_team_id' => 1,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 2,
        'home_team_id' => 4,
        'away_team_id' => 1,
        'finished' => 1,
        'home_score' => 3,
        'away_score' => 0
    ],

    [
        'gameweek' => 3,
        'home_team_id' => 4,
        'away_team_id' => 1,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ]

];


$weakPerformance =
    $teamPerformance->analyse(
        $weakTeamFixtures,
        4
    );


$weakModel =
    $teamStrengthModel->buildTeamModel(
        $baselines[4],
        $weakPerformance,
        $teamPerformance
    );


echo "Performance: "
    . number_format(
        $weakModel['performance_rating'],
        2
    )
    . "<br>";

echo "Overall: "
    . number_format(
        $weakModel['overall'],
        2
    )
    . "<br>";


testResult(
    'Weak team produces a performance rating',
    $weakModel['performance_rating'] !== null
);

testResult(
    'Weak team improves above its baseline',
    $weakModel['overall']
    >
    $baselines[4]['overall']
);

testResult(
    'Weak team rating remains within 0-100',
    $weakModel['overall'] >= 0
    &&
    $weakModel['overall'] <= 100
);


/*
 * ============================================================
 * SCENARIO F
 * Compare winning and losing teams
 * ============================================================
 */

heading('Scenario F: Winning vs Losing Teams');


testResult(
    'Strong team winning rates higher than strong team losing',
    $strongModel['overall']
    >
    $poorModel['overall']
);

testResult(
    'Weak team overperforming rates higher than its baseline',
    $weakModel['overall']
    >
    $baselines[4]['overall']
);


/*
 * ============================================================
 * SCENARIO G
 * Rating bounds
 * ============================================================
 */

heading('Scenario G: Rating Bounds');


$models = [

    $strongModel,
    $poorModel,
    $weakModel

];


foreach ($models as $model) {

    testResult(
        'Team rating remains between 0 and 100',
        $model['overall'] >= 0
        &&
        $model['overall'] <= 100
    );
}


/*
 * ============================================================
 * SCENARIO H
 * Home/Away separation
 * ============================================================
 */

heading('Scenario H: Home/Away Baseline Separation');


$splitTeams = [

    [
        'id' => 10,
        'name' => 'Home Strong',
        'strength_overall_home' => 100,
        'strength_overall_away' => 80
    ],

    [
        'id' => 11,
        'name' => 'Away Strong',
        'strength_overall_home' => 80,
        'strength_overall_away' => 100
    ],

    [
        'id' => 12,
        'name' => 'Average Team',
        'strength_overall_home' => 60,
        'strength_overall_away' => 60
    ]

];


$splitBaselines =
    $teamStrength->calculateTeamStrengths(
        $splitTeams
    );


testResult(
    'Home strength remains above away strength',
    $splitBaselines[10]['home']
    >
    $splitBaselines[10]['away']
);

testResult(
    'Overall rating is midpoint of home and away',
    $splitBaselines[10]['overall']
    ===
    (
        (
            $splitBaselines[10]['home']
            +
            $splitBaselines[10]['away']
        )
        / 2
    )
);


/*
 * ============================================================
 * SCENARIO I
 * Multi-team independence
 * ============================================================
 */

heading('Scenario I: Multi-Team Independence');


$liverpoolFixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 2,
        'away_team_id' => 3,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ]

];


$midTableFixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 3,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ]

];


$liverpoolPerformance =
    $teamPerformance->analyse(
        $liverpoolFixtures,
        2
    );


$midTablePerformance =
    $teamPerformance->analyse(
        $midTableFixtures,
        3
    );


$liverpoolModel =
    $teamStrengthModel->buildTeamModel(
        $baselines[2],
        $liverpoolPerformance,
        $teamPerformance
    );


$midTableModel =
    $teamStrengthModel->buildTeamModel(
        $baselines[3],
        $midTablePerformance,
        $teamPerformance
    );


testResult(
    'Liverpool model has correct team id',
    $liverpoolModel['id'] === 2
);

testResult(
    'Mid-table model has correct team id',
    $midTableModel['id'] === 3
);

testResult(
    'Liverpool model does not inherit Mid-table performance',
    $liverpoolModel['performance_rating']
    !==
    $midTableModel['performance_rating']
);

testResult(
    'Team names remain correct',
    $liverpoolModel['name'] === 'Liverpool'
    &&
    $midTableModel['name'] === 'Mid-table'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Team Strength Model Integration Summary<br>";
echo "============================================<br>";

echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}