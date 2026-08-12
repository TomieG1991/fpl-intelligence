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
 * MODEL
 * ============================================================
 */

$teamIntelligence =
    new TeamIntelligence();


/*
 * ============================================================
 * TEST TEAM DATA
 * ============================================================
 */

$team = [

    'team_id' => 1,

    'name' => 'Test Team A',

    'strength' => 80.00,

    'home' => 85.00,

    'away' => 75.00
];


$fixtureProfile = [

    'team_id' => 1,

    'team_name' => 'Test Team A',

    'fixture_rating' => 70.00,

    'fixture_label' => 'Good',

    'rolling_averages' => [

        'next_5' => 70.00,

        'next_6' => 65.00,

        'next_8' => 60.00,

        'next_10' => 58.00
    ],

    'best_run' => [

        'start_gameweek' => 5,

        'end_gameweek' => 9,

        'average_score' => 75.00,

        'fixtures' => []
    ],

    'worst_run' => [

        'start_gameweek' => 10,

        'end_gameweek' => 14,

        'average_score' => 55.00,

        'fixtures' => []
    ],

    'trend' => 'Improving',

    'fixture_count' => 10,

    'fixtures' => []
];


/*
 * ============================================================
 * SCENARIO A
 * Team Strength Inputs
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Team Strength Inputs<br>";
echo "============================================<br>";


$strength =
    $teamIntelligence->calculateStrengthScore(
        80.00
    );


echo "Strength Score: "
    . number_format($strength, 2)
    . "<br>";


testPass(
    'Strength score is preserved',
    $strength === 80.00
);


testPass(
    'Strength score remains within 0-100',
    $strength >= 0
    &&
    $strength <= 100
);


/*
 * ============================================================
 * SCENARIO B
 * Team Intelligence Score
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Team Intelligence Score<br>";
echo "============================================<br>";


$intelligenceScore =
    $teamIntelligence->calculateIntelligenceScore(
        80.00,
        70.00
    );


echo "Strength: 80.00<br>";
echo "Fixture Rating: 70.00<br>";
echo "Intelligence Score: "
    . number_format(
        $intelligenceScore,
        2
    )
    . "<br>";


$expectedScore =
    (80.00 * 0.60)
    +
    (70.00 * 0.40);


testPass(
    'Intelligence score uses strength and fixture rating',
    $intelligenceScore === $expectedScore
);


testPass(
    'Intelligence score remains within 0-100',
    $intelligenceScore >= 0
    &&
    $intelligenceScore <= 100
);


/*
 * ============================================================
 * SCENARIO C
 * Intelligence Score Weighting
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Intelligence Score Weighting<br>";
echo "============================================<br>";


$strengthOnly =
    $teamIntelligence->calculateIntelligenceScore(
        100.00,
        0.00
    );


$fixtureOnly =
    $teamIntelligence->calculateIntelligenceScore(
        0.00,
        100.00
    );


echo "Strength-heavy score: "
    . number_format(
        $strengthOnly,
        2
    )
    . "<br>";

echo "Fixture-heavy score: "
    . number_format(
        $fixtureOnly,
        2
    )
    . "<br>";


testPass(
    'Strength contributes 60%',
    $strengthOnly === 60.00
);


testPass(
    'Fixture rating contributes 40%',
    $fixtureOnly === 40.00
);


/*
 * ============================================================
 * SCENARIO D
 * Intelligence Labels
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Intelligence Labels<br>";
echo "============================================<br>";


$elite =
    $teamIntelligence->getIntelligenceLabel(
        90.00
    );

$strong =
    $teamIntelligence->getIntelligenceLabel(
        75.00
    );

$average =
    $teamIntelligence->getIntelligenceLabel(
        60.00
    );

$weak =
    $teamIntelligence->getIntelligenceLabel(
        45.00
    );

$poor =
    $teamIntelligence->getIntelligenceLabel(
        20.00
    );


echo "90 Rating: {$elite}<br>";
echo "75 Rating: {$strong}<br>";
echo "60 Rating: {$average}<br>";
echo "45 Rating: {$weak}<br>";
echo "20 Rating: {$poor}<br>";


testPass(
    '90 produces Elite',
    $elite === 'Elite'
);

testPass(
    '75 produces Strong',
    $strong === 'Strong'
);

testPass(
    '60 produces Average',
    $average === 'Average'
);

testPass(
    '45 produces Weak',
    $weak === 'Weak'
);

testPass(
    '20 produces Poor',
    $poor === 'Poor'
);


/*
 * ============================================================
 * SCENARIO E
 * Intelligence Label Boundaries
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Intelligence Label Boundaries<br>";
echo "============================================<br>";


testPass(
    'Score 85 produces Elite',
    $teamIntelligence->getIntelligenceLabel(
        85.00
    ) === 'Elite'
);

testPass(
    'Score 70 produces Strong',
    $teamIntelligence->getIntelligenceLabel(
        70.00
    ) === 'Strong'
);

testPass(
    'Score 55 produces Average',
    $teamIntelligence->getIntelligenceLabel(
        55.00
    ) === 'Average'
);

testPass(
    'Score 40 produces Weak',
    $teamIntelligence->getIntelligenceLabel(
        40.00
    ) === 'Weak'
);

testPass(
    'Below 40 produces Poor',
    $teamIntelligence->getIntelligenceLabel(
        39.99
    ) === 'Poor'
);


/*
 * ============================================================
 * SCENARIO F
 * Score Bounds
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Intelligence Score Bounds<br>";
echo "============================================<br>";


$over100 =
    $teamIntelligence->calculateIntelligenceScore(
        120.00,
        100.00
    );


$below0 =
    $teamIntelligence->calculateIntelligenceScore(
        -20.00,
        0.00
    );


echo "Over 100 Input: "
    . number_format(
        $over100,
        2
    )
    . "<br>";

echo "Below 0 Input: "
    . number_format(
        $below0,
        2
    )
    . "<br>";


testPass(
    'Score above 100 is capped at 100',
    $over100 === 100.00
);


testPass(
    'Score below 0 is capped at 0',
    $below0 === 0.00
);


/*
 * ============================================================
 * SCENARIO G
 * Missing Fixture Rating
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Missing Fixture Rating<br>";
echo "============================================<br>";


$missingFixture =
    $teamIntelligence->calculateIntelligenceScore(
        80.00,
        null
    );


echo "Missing Fixture Score: "
    . (
        $missingFixture === null
            ? 'NULL'
            : number_format(
                $missingFixture,
                2
            )
    )
    . "<br>";


testPass(
    'Missing fixture rating is handled',
    $missingFixture === null
);


/*
 * ============================================================
 * SCENARIO H
 * Complete Team Intelligence Profile
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Complete Team Intelligence Profile<br>";
echo "============================================<br>";


$profile =
    $teamIntelligence->buildProfile(
        $team,
        $fixtureProfile
    );


testPass(
    'Complete profile returns an array',
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
    'Strength rating exists',
    array_key_exists(
        'strength_rating',
        $profile
    )
);

testPass(
    'Fixture rating exists',
    array_key_exists(
        'fixture_rating',
        $profile
    )
);

testPass(
    'Fixture label exists',
    array_key_exists(
        'fixture_label',
        $profile
    )
);

testPass(
    'Intelligence score exists',
    array_key_exists(
        'intelligence_score',
        $profile
    )
);

testPass(
    'Intelligence label exists',
    array_key_exists(
        'intelligence_label',
        $profile
    )
);


/*
 * ============================================================
 * SCENARIO I
 * Profile Data Integration
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Profile Data Integration<br>";
echo "============================================<br>";


testPass(
    'Strength rating matches team strength',
    $profile['strength_rating'] === 80.00
);

testPass(
    'Fixture rating matches fixture profile',
    $profile['fixture_rating'] === 70.00
);

testPass(
    'Fixture label matches fixture profile',
    $profile['fixture_label'] === 'Good'
);


$expectedProfileScore =
    (80.00 * 0.60)
    +
    (70.00 * 0.40);


testPass(
    'Profile intelligence score is calculated correctly',
    $profile['intelligence_score']
    ===
    $expectedProfileScore
);


/*
 * ============================================================
 * SCENARIO J
 * Fixture Profile Integration
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario J: Fixture Profile Integration<br>";
echo "============================================<br>";


testPass(
    'Rolling averages are included',
    array_key_exists(
        'rolling_averages',
        $profile
    )
);

testPass(
    'Next 5 average is included',
    array_key_exists(
        'next_5',
        $profile['rolling_averages']
    )
);

testPass(
    'Next 6 average is included',
    array_key_exists(
        'next_6',
        $profile['rolling_averages']
    )
);

testPass(
    'Next 8 average is included',
    array_key_exists(
        'next_8',
        $profile['rolling_averages']
    )
);

testPass(
    'Next 10 average is included',
    array_key_exists(
        'next_10',
        $profile['rolling_averages']
    )
);

testPass(
    'Best fixture run is included',
    array_key_exists(
        'best_run',
        $profile
    )
);

testPass(
    'Worst fixture run is included',
    array_key_exists(
        'worst_run',
        $profile
    )
);

testPass(
    'Fixture trend is included',
    array_key_exists(
        'trend',
        $profile
    )
);

testPass(
    'Fixture count is included',
    array_key_exists(
        'fixture_count',
        $profile
    )
);


/*
 * ============================================================
 * SCENARIO K
 * Empty Profile
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario K: Empty Profile<br>";
echo "============================================<br>";


$emptyTeam = [

    'team_id' => 99,

    'name' => 'Empty Team',

    'strength' => 70.00,

    'home' => 70.00,

    'away' => 70.00
];


$emptyFixtureProfile = [

    'team_id' => 99,

    'team_name' => 'Empty Team',

    'fixture_rating' => null,

    'fixture_label' => null,

    'rolling_averages' => [

        'next_5' => null,

        'next_6' => null,

        'next_8' => null,

        'next_10' => null
    ],

    'best_run' => null,

    'worst_run' => null,

    'trend' => 'Insufficient Data',

    'fixture_count' => 0,

    'fixtures' => []
];


$emptyProfile =
    $teamIntelligence->buildProfile(
        $emptyTeam,
        $emptyFixtureProfile
    );


testPass(
    'Empty profile returns an array',
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
    'Missing fixture rating is null',
    $emptyProfile['fixture_rating'] === null
);

testPass(
    'Missing fixture label is null',
    $emptyProfile['fixture_label'] === null
);

testPass(
    'Missing intelligence score is null',
    $emptyProfile['intelligence_score'] === null
);

testPass(
    'Empty fixture count is preserved',
    $emptyProfile['fixture_count'] === 0
);


/*
 * ============================================================
 * SCENARIO L
 * Complete Front-End Friendly Output
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Front-End Friendly Team Intelligence<br>";
echo "============================================<br>";


echo "Team: "
    . $profile['team_name']
    . "<br>";

echo "Strength Rating: "
    . number_format(
        $profile['strength_rating'],
        2
    )
    . " / 100<br>";

echo "Fixture Rating: "
    . number_format(
        $profile['fixture_rating'],
        2
    )
    . " / 100<br>";

echo "Fixture Label: "
    . $profile['fixture_label']
    . "<br>";

echo "Intelligence Score: "
    . number_format(
        $profile['intelligence_score'],
        2
    )
    . " / 100<br>";

echo "Intelligence Label: "
    . $profile['intelligence_label']
    . "<br>";

echo "Next 5 Average: "
    . number_format(
        $profile['rolling_averages']['next_5'],
        2
    )
    . " / 100<br>";

echo "Next 6 Average: "
    . number_format(
        $profile['rolling_averages']['next_6'],
        2
    )
    . " / 100<br>";

echo "Next 8 Average: "
    . number_format(
        $profile['rolling_averages']['next_8'],
        2
    )
    . " / 100<br>";

echo "Next 10 Average: "
    . number_format(
        $profile['rolling_averages']['next_10'],
        2
    )
    . " / 100<br>";

echo "Fixture Trend: "
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
echo "Team Intelligence Test Summary<br>";
echo "============================================<br>";


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}