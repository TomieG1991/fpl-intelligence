<?php

require_once '../classes/autoload.php';


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed = 0;
$failed = 0;


function testPass(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;

    if ($condition) {

        echo "PASS: {$description}<br>";

        $passed++;

    } else {

        echo "FAIL: {$description}<br>";

        $failed++;
    }
}


/*
 * ============================================================
 * MODELS
 * ============================================================
 */

$fixtureIntelligence =
    new FixtureIntelligence();

$teamFixtureProfile =
    new TeamFixtureProfile();


/*
 * ============================================================
 * TEST FIXTURE INTELLIGENCE DATA
 * ============================================================
 *
 * These represent already analysed fixtures.
 */

$fixtureRun = [

    [
        'gameweek' => 1,
        'home_team' => 'Test Team A',
        'away_team' => 'Test Team B',
        'is_home' => true,
        'venue' => 'Home',
        'team_baseline' => 80.00,
        'opponent_baseline' => 50.00,
        'home_baseline' => 80.00,
        'away_baseline' => 50.00,
        'matchup' => 30.00,
        'fixture_score' => 65.00,
        'difficulty' => 3,
        'difficulty_label' => 'Average'
    ],

    [
        'gameweek' => 2,
        'home_team' => 'Test Team C',
        'away_team' => 'Test Team A',
        'is_home' => false,
        'venue' => 'Away',
        'team_baseline' => 70.00,
        'opponent_baseline' => 40.00,
        'home_baseline' => 40.00,
        'away_baseline' => 70.00,
        'matchup' => 30.00,
        'fixture_score' => 65.00,
        'difficulty' => 3,
        'difficulty_label' => 'Average'
    ],

    [
        'gameweek' => 3,
        'home_team' => 'Test Team A',
        'away_team' => 'Test Team D',
        'is_home' => true,
        'venue' => 'Home',
        'team_baseline' => 80.00,
        'opponent_baseline' => 85.00,
        'home_baseline' => 80.00,
        'away_baseline' => 85.00,
        'matchup' => -5.00,
        'fixture_score' => 47.50,
        'difficulty' => 4,
        'difficulty_label' => 'Difficult'
    ],

    [
        'gameweek' => 4,
        'home_team' => 'Test Team B',
        'away_team' => 'Test Team A',
        'is_home' => false,
        'venue' => 'Away',
        'team_baseline' => 70.00,
        'opponent_baseline' => 60.00,
        'home_baseline' => 60.00,
        'away_baseline' => 70.00,
        'matchup' => 10.00,
        'fixture_score' => 55.00,
        'difficulty' => 3,
        'difficulty_label' => 'Average'
    ],

    [
        'gameweek' => 5,
        'home_team' => 'Test Team A',
        'away_team' => 'Test Team C',
        'is_home' => true,
        'venue' => 'Home',
        'team_baseline' => 80.00,
        'opponent_baseline' => 30.00,
        'home_baseline' => 80.00,
        'away_baseline' => 30.00,
        'matchup' => 50.00,
        'fixture_score' => 75.00,
        'difficulty' => 2,
        'difficulty_label' => 'Good'
    ],

    [
        'gameweek' => 6,
        'home_team' => 'Test Team D',
        'away_team' => 'Test Team A',
        'is_home' => false,
        'venue' => 'Away',
        'team_baseline' => 70.00,
        'opponent_baseline' => 90.00,
        'home_baseline' => 90.00,
        'away_baseline' => 70.00,
        'matchup' => -20.00,
        'fixture_score' => 40.00,
        'difficulty' => 4,
        'difficulty_label' => 'Difficult'
    ],

    [
        'gameweek' => 7,
        'home_team' => 'Test Team A',
        'away_team' => 'Test Team B',
        'is_home' => true,
        'venue' => 'Home',
        'team_baseline' => 80.00,
        'opponent_baseline' => 50.00,
        'home_baseline' => 80.00,
        'away_baseline' => 50.00,
        'matchup' => 30.00,
        'fixture_score' => 65.00,
        'difficulty' => 3,
        'difficulty_label' => 'Average'
    ],

    [
        'gameweek' => 8,
        'home_team' => 'Test Team C',
        'away_team' => 'Test Team A',
        'is_home' => false,
        'venue' => 'Away',
        'team_baseline' => 70.00,
        'opponent_baseline' => 40.00,
        'home_baseline' => 40.00,
        'away_baseline' => 70.00,
        'matchup' => 30.00,
        'fixture_score' => 65.00,
        'difficulty' => 3,
        'difficulty_label' => 'Average'
    ],

    [
        'gameweek' => 9,
        'home_team' => 'Test Team A',
        'away_team' => 'Test Team B',
        'is_home' => true,
        'venue' => 'Home',
        'team_baseline' => 80.00,
        'opponent_baseline' => 50.00,
        'home_baseline' => 80.00,
        'away_baseline' => 50.00,
        'matchup' => 30.00,
        'fixture_score' => 65.00,
        'difficulty' => 3,
        'difficulty_label' => 'Average'
    ],

    [
        'gameweek' => 10,
        'home_team' => 'Test Team D',
        'away_team' => 'Test Team A',
        'is_home' => false,
        'venue' => 'Away',
        'team_baseline' => 70.00,
        'opponent_baseline' => 90.00,
        'home_baseline' => 90.00,
        'away_baseline' => 70.00,
        'matchup' => -20.00,
        'fixture_score' => 40.00,
        'difficulty' => 4,
        'difficulty_label' => 'Difficult'
    ]
];


/*
 * ============================================================
 * SCENARIO A
 * Fixture Labels
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Fixture Rating Labels<br>";
echo "============================================<br>";


$excellent =
    $teamFixtureProfile->getFixtureLabel(90.00);

$good =
    $teamFixtureProfile->getFixtureLabel(75.00);

$average =
    $teamFixtureProfile->getFixtureLabel(60.00);

$difficult =
    $teamFixtureProfile->getFixtureLabel(45.00);

$veryDifficult =
    $teamFixtureProfile->getFixtureLabel(20.00);

$unknown =
    $teamFixtureProfile->getFixtureLabel(null);


echo "90 Rating: {$excellent}<br>";
echo "75 Rating: {$good}<br>";
echo "60 Rating: {$average}<br>";
echo "45 Rating: {$difficult}<br>";
echo "20 Rating: {$veryDifficult}<br>";
echo "NULL Rating: "
    . ($unknown ?? 'NULL')
    . "<br>";


testPass(
    '90 rating produces Excellent',
    $excellent === 'Excellent'
);

testPass(
    '75 rating produces Good',
    $good === 'Good'
);

testPass(
    '60 rating produces Average',
    $average === 'Average'
);

testPass(
    '45 rating produces Difficult',
    $difficult === 'Difficult'
);

testPass(
    '20 rating produces Very Difficult',
    $veryDifficult === 'Very Difficult'
);

testPass(
    'Missing fixture rating produces null label',
    $unknown === null
);


/*
 * ============================================================
 * SCENARIO B
 * Fixture Rating Calculation
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Fixture Rating Calculation<br>";
echo "============================================<br>";


$rating =
    $teamFixtureProfile->calculateFixtureRating(
        [
            'next_5' => 72.50,
            'next_6' => 70.00,
            'next_8' => 68.00,
            'next_10' => 65.00
        ]
    );


echo "Fixture Rating: "
    . number_format($rating, 2)
    . "<br>";


testPass(
    'Fixture rating uses next 5 average',
    $rating === 72.50
);


testPass(
    'Fixture rating remains within 0-100',
    $rating >= 0
    &&
    $rating <= 100
);


$missingRating =
    $teamFixtureProfile->calculateFixtureRating(
        []
    );


testPass(
    'Missing rolling average returns null fixture rating',
    $missingRating === null
);


$nullRating =
    $teamFixtureProfile->calculateFixtureRating(
        [
            'next_5' => null
        ]
    );


testPass(
    'Null next 5 average returns null fixture rating',
    $nullRating === null
);


/*
 * ============================================================
 * SCENARIO C
 * Complete Profile
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Complete Team Fixture Profile<br>";
echo "============================================<br>";


$profile =
    $teamFixtureProfile->buildProfileFromAnalysis(
        1,
        'Test Team A',
        $fixtureRun,
        $fixtureIntelligence
    );


testPass(
    'Profile returns an array',
    is_array($profile)
);

testPass(
    'Team ID is preserved',
    $profile['team_id'] === 1
);

testPass(
    'Team name is preserved',
    $profile['team_name'] === 'Test Team A'
);

testPass(
    'Fixture rating exists',
    $profile['fixture_rating'] !== null
);

testPass(
    'Fixture label exists',
    $profile['fixture_label'] !== null
);

testPass(
    'Rolling averages exist',
    is_array($profile['rolling_averages'])
);

testPass(
    'Next 5 average exists',
    array_key_exists(
        'next_5',
        $profile
    )
);

testPass(
    'Next 6 average exists',
    array_key_exists(
        'next_6',
        $profile
    )
);

testPass(
    'Next 8 average exists',
    array_key_exists(
        'next_8',
        $profile
    )
);

testPass(
    'Next 10 average exists',
    array_key_exists(
        'next_10',
        $profile
    )
);

testPass(
    'Best run exists',
    array_key_exists(
        'best_run',
        $profile
    )
);

testPass(
    'Worst run exists',
    array_key_exists(
        'worst_run',
        $profile
    )
);

testPass(
    'Trend exists',
    array_key_exists(
        'trend',
        $profile
    )
);

testPass(
    'Fixture count is correct',
    $profile['fixture_count'] === 10
);

testPass(
    'Fixtures are included',
    is_array($profile['fixtures'])
);


/*
 * ============================================================
 * SCENARIO D
 * Rolling Averages
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Rolling Fixture Averages<br>";
echo "============================================<br>";


echo "Next 5: "
    . number_format(
        $profile['next_5'],
        2
    )
    . "<br>";

echo "Next 6: "
    . number_format(
        $profile['next_6'],
        2
    )
    . "<br>";

echo "Next 8: "
    . number_format(
        $profile['next_8'],
        2
    )
    . "<br>";

echo "Next 10: "
    . number_format(
        $profile['next_10'],
        2
    )
    . "<br>";


testPass(
    'Next 5 average is calculated correctly',
    $profile['next_5'] === 61.50
);

testPass(
    'Next 6 average is calculated correctly',
    $profile['next_6'] === 57.92
);

testPass(
    'Next 8 average is calculated correctly',
    $profile['next_8'] === 59.69
);

testPass(
    'Next 10 average is calculated correctly',
    $profile['next_10'] === 58.25
);


/*
 * ============================================================
 * SCENARIO E
 * Fixture Rating Integration
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Fixture Rating Integration<br>";
echo "============================================<br>";


testPass(
    'Fixture rating equals next 5 average',
    $profile['fixture_rating']
    ===
    $profile['next_5']
);


testPass(
    'Fixture rating uses a 0-100 scale',
    $profile['fixture_rating'] >= 0
    &&
    $profile['fixture_rating'] <= 100
);


testPass(
    'Fixture label matches fixture rating',
    $profile['fixture_label']
    ===
    $teamFixtureProfile->getFixtureLabel(
        $profile['fixture_rating']
    )
);


/*
 * ============================================================
 * SCENARIO F
 * Best Fixture Run
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Best Fixture Run<br>";
echo "============================================<br>";


$bestRun =
    $profile['best_run'];


testPass(
    'Best run returns an array',
    is_array($bestRun)
);

testPass(
    'Best run contains start gameweek',
    array_key_exists(
        'start_gameweek',
        $bestRun
    )
);

testPass(
    'Best run contains end gameweek',
    array_key_exists(
        'end_gameweek',
        $bestRun
    )
);

testPass(
    'Best run contains average score',
    array_key_exists(
        'average_score',
        $bestRun
    )
);

testPass(
    'Best run contains fixtures',
    array_key_exists(
        'fixtures',
        $bestRun
    )
);

testPass(
    'Best run contains five fixtures',
    count($bestRun['fixtures']) === 5
);


/*
 * ============================================================
 * SCENARIO G
 * Worst Fixture Run
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Worst Fixture Run<br>";
echo "============================================<br>";


$worstRun =
    $profile['worst_run'];


testPass(
    'Worst run returns an array',
    is_array($worstRun)
);

testPass(
    'Worst run contains start gameweek',
    array_key_exists(
        'start_gameweek',
        $worstRun
    )
);

testPass(
    'Worst run contains end gameweek',
    array_key_exists(
        'end_gameweek',
        $worstRun
    )
);

testPass(
    'Worst run contains average score',
    array_key_exists(
        'average_score',
        $worstRun
    )
);

testPass(
    'Worst run contains fixtures',
    array_key_exists(
        'fixtures',
        $worstRun
    )
);

testPass(
    'Worst run contains five fixtures',
    count($worstRun['fixtures']) === 5
);


/*
 * ============================================================
 * SCENARIO H
 * Fixture Trend
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Fixture Trend<br>";
echo "============================================<br>";


echo "Trend: "
    . $profile['trend']
    . "<br>";


testPass(
    'Fixture trend exists',
    is_string($profile['trend'])
);

testPass(
    'Fixture trend is a recognised value',
    in_array(
        $profile['trend'],
        [
            'Improving',
            'Declining',
            'Stable',
            'Insufficient Data'
        ],
        true
    )
);


/*
 * ============================================================
 * SCENARIO I
 * Empty Fixture Data
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Empty Fixture Data<br>";
echo "============================================<br>";


$emptyProfile =
    $teamFixtureProfile->buildProfileFromAnalysis(
        99,
        'Empty Team',
        [],
        $fixtureIntelligence
    );


testPass(
    'Empty fixture profile returns an array',
    is_array($emptyProfile)
);

testPass(
    'Empty team ID is preserved',
    $emptyProfile['team_id'] === 99
);

testPass(
    'Empty team name is preserved',
    $emptyProfile['team_name'] === 'Empty Team'
);

testPass(
    'Empty fixture rating is null',
    $emptyProfile['fixture_rating'] === null
);

testPass(
    'Empty fixture label is null',
    $emptyProfile['fixture_label'] === null
);

testPass(
    'Empty fixture count is zero',
    $emptyProfile['fixture_count'] === 0
);

testPass(
    'Empty fixture list is returned',
    is_array($emptyProfile['fixtures'])
    &&
    count($emptyProfile['fixtures']) === 0
);

testPass(
    'Empty best run is null',
    $emptyProfile['best_run'] === null
);

testPass(
    'Empty worst run is null',
    $emptyProfile['worst_run'] === null
);

testPass(
    'Empty trend reports insufficient data',
    $emptyProfile['trend'] === 'Insufficient Data'
);


/*
 * ============================================================
 * SCENARIO J
 * Fixture Rating Bounds
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario J: Fixture Rating Bounds<br>";
echo "============================================<br>";


$over100 =
    $teamFixtureProfile->calculateFixtureRating(
        [
            'next_5' => 150.00
        ]
    );


$below0 =
    $teamFixtureProfile->calculateFixtureRating(
        [
            'next_5' => -25.00
        ]
    );


echo "Over 100 Input: "
    . number_format($over100, 2)
    . "<br>";

echo "Below 0 Input: "
    . number_format($below0, 2)
    . "<br>";


testPass(
    'Fixture rating above 100 is capped at 100',
    $over100 === 100.00
);

testPass(
    'Fixture rating below 0 is capped at 0',
    $below0 === 0.00
);


/*
 * ============================================================
 * SCENARIO K
 * Front-End Friendly Team Fixture Profile
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Front-End Friendly Team Fixture Profile<br>";
echo "============================================<br>";


echo "Team: "
    . $profile['team_name']
    . "<br>";

echo "Fixture Rating: "
    . number_format(
        $profile['fixture_rating'],
        2
    )
    . " / 100<br>";

echo "Fixture Label: "
    . $profile['fixture_label']
    . "<br>";

echo "Next 5 Average: "
    . number_format(
        $profile['next_5'],
        2
    )
    . " / 100<br>";

echo "Next 6 Average: "
    . number_format(
        $profile['next_6'],
        2
    )
    . " / 100<br>";

echo "Next 8 Average: "
    . number_format(
        $profile['next_8'],
        2
    )
    . " / 100<br>";

echo "Next 10 Average: "
    . number_format(
        $profile['next_10'],
        2
    )
    . " / 100<br>";

echo "Best Run: GW"
    . $profile['best_run']['start_gameweek']
    . " - GW"
    . $profile['best_run']['end_gameweek']
    . "<br>";

echo "Best Run Average: "
    . number_format(
        $profile['best_run']['average_score'],
        2
    )
    . " / 100<br>";

echo "Worst Run: GW"
    . $profile['worst_run']['start_gameweek']
    . " - GW"
    . $profile['worst_run']['end_gameweek']
    . "<br>";

echo "Worst Run Average: "
    . number_format(
        $profile['worst_run']['average_score'],
        2
    )
    . " / 100<br>";

echo "Trend: "
    . $profile['trend']
    . "<br>";

echo "Fixture Count: "
    . $profile['fixture_count']
    . "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Team Fixture Profile Test Summary<br>";
echo "============================================<br>";


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}