<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * TEAM STRENGTH HISTORICAL TEST HARNESS
 * ============================================================
 *
 * This test uses controlled historical fixtures.
 *
 * It does NOT modify the real fixtures table.
 *
 * The purpose is to validate the behaviour of:
 *
 * TeamPerformance
 * TeamStrengthModel
 *
 * before we start relying on the model for live
 * fixture intelligence.
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

        echo "PASS: {$name}\n";

        $passed++;

    } else {

        echo "FAIL: {$name}\n";

        $failed++;
    }
}


/*
 * ============================================================
 * TEST BASELINE DATA
 * ============================================================
 *
 * These values deliberately avoid the extreme 0-100
 * values currently present in the live FPL baseline.
 */

$baselines = [

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
    ],

    3 => [
        'id' => 3,
        'name' => 'Average Team',
        'home' => 50,
        'away' => 50,
        'overall' => 50
    ],

    4 => [
        'id' => 4,
        'name' => 'Strong Team',
        'home' => 80,
        'away' => 80,
        'overall' => 80
    ],

    5 => [
        'id' => 5,
        'name' => 'Elite Team',
        'home' => 100,
        'away' => 100,
        'overall' => 100
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


/*
 * ============================================================
 * TEST 1
 * ============================================================
 *
 * No matches.
 *
 * Expected:
 *
 * Performance = null
 * Baseline weight = 1
 * Performance weight = 0
 * Combined rating = baseline
 */

$noFixtures = [];

$performance =
    $teamPerformance->analyse(
        $noFixtures,
        1
    );

$model =
    $teamStrengthModel->buildTeamModel(
        $baselines[1],
        $performance,
        $teamPerformance
    );


testResult(
    'No matches returns zero games played',
    $model['played'] === 0
);

testResult(
    'No matches returns no performance rating',
    $model['performance_rating'] === null
);

testResult(
    'No matches gives 100% baseline weight',
    $model['baseline_weight'] === 1.00
);

testResult(
    'No matches gives 0% performance weight',
    $model['performance_weight'] === 0.00
);

testResult(
    'No matches keeps Arsenal at baseline strength',
    $model['overall'] === 100.00
);


/*
 * ============================================================
 * TEST 2
 * ============================================================
 *
 * Arsenal beats a weak team.
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 3,
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
    'Win against weak team records one match',
    $model['played'] === 1
);

testResult(
    'Win against weak team records three points',
    $performance['points'] === 3
);

testResult(
    'Win produces a performance rating',
    $model['performance_rating'] !== null
);

testResult(
    'Baseline weight decreases after first match',
    $model['baseline_weight'] === 0.90
);

testResult(
    'Performance weight increases after first match',
    $model['performance_weight'] === 0.10
);


/*
 * ============================================================
 * TEST 3
 * ============================================================
 *
 * Arsenal loses to a weak team.
 *
 * This should produce a worse performance rating than
 * beating the weak team.
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 1
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


$lossPerformanceRating =
    $model['performance_rating'];


testResult(
    'Loss against weak team records zero points',
    $performance['points'] === 0
);


/*
 * ============================================================
 * TEST 4
 * ============================================================
 *
 * Compare win against weak team with loss against weak team.
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 3,
        'away_score' => 0
    ]
];


$winPerformance =
    $teamPerformance->analyse(
        $fixtures,
        1
    );


$winModel =
    $teamStrengthModel->buildTeamModel(
        $baselines[1],
        $winPerformance,
        $teamPerformance
    );


testResult(
    'Winning produces a higher performance rating than losing',
    $winModel['performance_rating'] > $lossPerformanceRating
);


/*
 * ============================================================
 * TEST 5
 * ============================================================
 *
 * Arsenal beats a strong team.
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 3,
        'away_score' => 0
    ]
];


$strongWinPerformance =
    $teamPerformance->analyse(
        $fixtures,
        1
    );


$strongWinModel =
    $teamStrengthModel->buildTeamModel(
        $baselines[1],
        $strongWinPerformance,
        $teamPerformance
    );


testResult(
    'Win against strong team records three points',
    $strongWinPerformance['points'] === 3
);

testResult(
    'Win against strong team produces a performance rating',
    $strongWinModel['performance_rating'] !== null
);


/*
 * ============================================================
 * TEST 6
 * ============================================================
 *
 * Arsenal loses to a strong team.
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 4,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 1
    ]
];


$strongLossPerformance =
    $teamPerformance->analyse(
        $fixtures,
        1
    );


$strongLossModel =
    $teamStrengthModel->buildTeamModel(
        $baselines[1],
        $strongLossPerformance,
        $teamPerformance
    );


testResult(
    'Loss against strong team records zero points',
    $strongLossPerformance['points'] === 0
);

testResult(
    'Loss against strong team produces a performance rating',
    $strongLossModel['performance_rating'] !== null
);


/*
 * ============================================================
 * TEST 7
 * ============================================================
 *
 * Check the weighting progression.
 */

$weight1 =
    $teamStrengthModel->calculateBaselineWeight(1);

$weight5 =
    $teamStrengthModel->calculateBaselineWeight(5);

$weight10 =
    $teamStrengthModel->calculateBaselineWeight(10);


testResult(
    'Baseline weight decreases from match 1 to match 5',
    $weight5 < $weight1
);

testResult(
    'Baseline weight decreases further after ten matches',
    $weight10 < $weight5
);


/*
 * ============================================================
 * TEST 8
 * ============================================================
 *
 * Home/away processing.
 */

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
    ]
];


$performance =
    $teamPerformance->analyse(
        $fixtures,
        1
    );


testResult(
    'Home fixture is counted correctly',
    $performance['home_played'] === 1
);

testResult(
    'Away fixture is counted correctly',
    $performance['away_played'] === 1
);

testResult(
    'Two wins produce six points',
    $performance['points'] === 6
);

/*
 * ============================================================
 * TEST 9
 * ============================================================
 *
 * TEAM RATING PROGRESSION
 *
 * Test how the combined team rating changes as completed
 * matches are added.
 *
 * This deliberately uses controlled results so we can inspect
 * the behaviour of the weighting model.
 */


/*
 * ------------------------------------------------------------
 * Helper: build a fixture
 * ------------------------------------------------------------
 */

function createTestFixture(
    int $gameweek,
    int $homeTeamId,
    int $awayTeamId,
    int $homeScore,
    int $awayScore
): array {

    return [

        'gameweek' =>
            $gameweek,

        'home_team_id' =>
            $homeTeamId,

        'away_team_id' =>
            $awayTeamId,

        'finished' =>
            1,

        'home_score' =>
            $homeScore,

        'away_score' =>
            $awayScore
    ];
}


/*
 * ------------------------------------------------------------
 * Scenario A
 *
 * Arsenal wins every match.
 * ------------------------------------------------------------
 */

echo "\n<br>";
echo "============================================\n";
echo "Scenario A: Arsenal Wins Every Match\n";
echo "============================================\n";
echo "\n<br>";

$fixtures = [];

$ratingHistory = [];

for ($i = 1; $i <= 10; $i++) {

    $fixtures[] =
        createTestFixture(
            $i,
            1,
            2,
            2,
            0
        );


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


    $ratingHistory[] = $model['overall'];


    echo sprintf(
        "GW%02d | Played: %2d | Performance: %6.2f | Baseline W: %.2f | Performance W: %.2f | Overall: %6.2f\n<br>",

        $i,

        $model['played'],

        $model['performance_rating'],

        $model['baseline_weight'],

        $model['performance_weight'],

        $model['overall']
    );
}


/*
 * Rating should remain at or below the baseline
 * because Arsenal starts at 100.
 */
testResult(
    'Winning team does not exceed 100 rating<br>',
    max($ratingHistory) <= 100
);


/*
 * ------------------------------------------------------------
 * Scenario B
 *
 * Arsenal loses every match.
 * ------------------------------------------------------------
 */

echo "\n<br>";
echo "============================================\n";
echo "Scenario B: Arsenal Loses Every Match\n";
echo "============================================\n<br>";

$fixtures = [];

$ratingHistory = [];

for ($i = 1; $i <= 10; $i++) {

    $fixtures[] =
        createTestFixture(
            $i,
            1,
            2,
            0,
            2
        );


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


    $ratingHistory[] = $model['overall'];


    echo sprintf(
        "GW%02d | Played: %2d | Performance: %6.2f | Baseline W: %.2f | Performance W: %.2f | Overall: %6.2f\n<br>",

        $i,

        $model['played'],

        $model['performance_rating'],

        $model['baseline_weight'],

        $model['performance_weight'],

        $model['overall']
    );
}


/*
 * A team consistently losing should become weaker.
 */
testResult(
    'Consistent losses reduce team rating<br>',
    $ratingHistory[9] < $ratingHistory[0]
);


/*
 * Rating should never become negative.
 */
testResult(
    'Team rating never becomes negative<br>',
    min($ratingHistory) >= 0
);


/*
 * ------------------------------------------------------------
 * Scenario C
 *
 * Arsenal has mixed results.
 *
 * W W D L W L D W L W
 * ------------------------------------------------------------
 */

echo "\n<br>";
echo "============================================\n";
echo "Scenario C: Mixed Results\n";
echo "============================================\n<br>";

$results = [

    [2, 0], // Win
    [3, 1], // Win
    [1, 1], // Draw
    [0, 1], // Loss
    [2, 0], // Win
    [0, 2], // Loss
    [1, 1], // Draw
    [3, 0], // Win
    [0, 1], // Loss
    [2, 0]  // Win
];

$fixtures = [];

$ratingHistory = [];

foreach ($results as $index => $result) {

    $gameweek = $index + 1;

    $fixtures[] =
        createTestFixture(
            $gameweek,
            1,
            3,
            $result[0],
            $result[1]
        );


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


    $ratingHistory[] = $model['overall'];


    echo sprintf(
        "GW%02d | Played: %2d | Performance: %6.2f | Baseline W: %.2f | Performance W: %.2f | Overall: %6.2f\n<br>",

        $gameweek,

        $model['played'],

        $model['performance_rating'],

        $model['baseline_weight'],

        $model['performance_weight'],

        $model['overall']
    );
}


/*
 * Mixed results should still produce a valid rating.
 */
testResult(
    'Mixed results produce a valid final rating<br>',
    $ratingHistory[9] >= 0
    &&
    $ratingHistory[9] <= 100
);


/*
 * ------------------------------------------------------------
 * Scenario D
 *
 * Weak team with consistently good results.
 *
 * This is particularly important.
 *
 * The model should allow actual performance to pull
 * a weak baseline upward over time.
 * ------------------------------------------------------------
 */

echo "\n<br>";
echo "============================================\n";
echo "Scenario D: Weak Team Overperforming\n";
echo "============================================\n<br>";

$fixtures = [];

$ratingHistory = [];

for ($i = 1; $i <= 10; $i++) {

    $fixtures[] =
        createTestFixture(
            $i,
            2,
            1,
            2,
            0
        );


    $performance =
        $teamPerformance->analyse(
            $fixtures,
            2
        );


    $model =
        $teamStrengthModel->buildTeamModel(
            $baselines[2],
            $performance,
            $teamPerformance
        );


    $ratingHistory[] = $model['overall'];


    echo sprintf(
        "GW%02d | Played: %2d | Performance: %6.2f | Baseline W: %.2f | Performance W: %.2f | Overall: %6.2f\n<br>",

        $i,

        $model['played'],

        $model['performance_rating'],

        $model['baseline_weight'],

        $model['performance_weight'],

        $model['overall']
    );
}


testResult(
    'Weak team improves when consistently winning<br>',
    $ratingHistory[9] > $ratingHistory[0]
);


/*
 * ------------------------------------------------------------
 * Scenario E
 *
 * Strong team consistently underperforming.
 * ------------------------------------------------------------
 */

echo "\n<br>";
echo "============================================\n";
echo "Scenario E: Strong Team Underperforming\n";
echo "============================================\n<br>";

$fixtures = [];

$ratingHistory = [];

for ($i = 1; $i <= 10; $i++) {

    $fixtures[] =
        createTestFixture(
            $i,
            4,
            1,
            0,
            2
        );


    $performance =
        $teamPerformance->analyse(
            $fixtures,
            4
        );


    $model =
        $teamStrengthModel->buildTeamModel(
            $baselines[4],
            $performance,
            $teamPerformance
        );


    $ratingHistory[] = $model['overall'];


    echo sprintf(
        "GW%02d | Played: %2d | Performance: %6.2f | Baseline W: %.2f | Performance W: %.2f | Overall: %6.2f\n<br>",

        $i,

        $model['played'],

        $model['performance_rating'],

        $model['baseline_weight'],

        $model['performance_weight'],

        $model['overall']
    );
}


testResult(
    'Strong team falls when consistently losing<br>',
    $ratingHistory[9] < $ratingHistory[0]
);

/*
 * ============================================================
 * TEST 10
 * ============================================================
 *
 * SCORELINE SENSITIVITY
 *
 * Test how different scorelines affect the performance rating.
 *
 * The purpose is to make sure:
 *
 * - A win is better than a draw
 * - A draw is better than a loss
 * - A bigger win generally produces a better rating
 * - A clean sheet improves the rating
 * - Heavy defeats produce lower ratings
 *
 * These tests are deliberately isolated from opposition
 * adjustment.
 */


/*
 * ------------------------------------------------------------
 * Helper: analyse one scoreline
 * ------------------------------------------------------------
 */

function analyseTestScoreline(
    TeamPerformance $teamPerformance,
    TeamStrengthModel $teamStrengthModel,
    array $baseline,
    int $homeScore,
    int $awayScore
): array {

    $fixtures = [

        [
            'gameweek' => 1,

            'home_team_id' => 1,

            'away_team_id' => 3,

            'finished' => 1,

            'home_score' => $homeScore,

            'away_score' => $awayScore
        ]
    ];


    $performance =
        $teamPerformance->analyse(
            $fixtures,
            1
        );


    $model =
        $teamStrengthModel->buildTeamModel(
            $baseline,
            $performance,
            $teamPerformance
        );


    return [

        'performance' =>
            $model['performance_rating'],

        'overall' =>
            $model['overall'],

        'points' =>
            $performance['points'],

        'goal_difference' =>
            $performance['goal_difference'],

        'goals_for' =>
            $performance['goals_for'],

        'goals_against' =>
            $performance['goals_against']
    ];
}


/*
 * ============================================================
 * SCORELINE TESTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scoreline Sensitivity Tests<br>";
echo "============================================<br>";


/*
 * ------------------------------------------------------------
 * Basic results
 * ------------------------------------------------------------
 */

$win10 =
    analyseTestScoreline(
        $teamPerformance,
        $teamStrengthModel,
        $baselines[1],
        1,
        0
    );


$win20 =
    analyseTestScoreline(
        $teamPerformance,
        $teamStrengthModel,
        $baselines[1],
        2,
        0
    );


$win30 =
    analyseTestScoreline(
        $teamPerformance,
        $teamStrengthModel,
        $baselines[1],
        3,
        0
    );


$draw00 =
    analyseTestScoreline(
        $teamPerformance,
        $teamStrengthModel,
        $baselines[1],
        0,
        0
    );


$draw11 =
    analyseTestScoreline(
        $teamPerformance,
        $teamStrengthModel,
        $baselines[1],
        1,
        1
    );


$loss01 =
    analyseTestScoreline(
        $teamPerformance,
        $teamStrengthModel,
        $baselines[1],
        0,
        1
    );


$loss02 =
    analyseTestScoreline(
        $teamPerformance,
        $teamStrengthModel,
        $baselines[1],
        0,
        2
    );


$loss03 =
    analyseTestScoreline(
        $teamPerformance,
        $teamStrengthModel,
        $baselines[1],
        0,
        3
    );


/*
 * ------------------------------------------------------------
 * Display scoreline ratings
 * ------------------------------------------------------------
 */

echo "1-0  | Performance: "
    . number_format($win10['performance'], 2)
    . "<br>";

echo "2-0  | Performance: "
    . number_format($win20['performance'], 2)
    . "<br>";

echo "3-0  | Performance: "
    . number_format($win30['performance'], 2)
    . "<br>";

echo "0-0  | Performance: "
    . number_format($draw00['performance'], 2)
    . "<br>";

echo "1-1  | Performance: "
    . number_format($draw11['performance'], 2)
    . "<br>";

echo "0-1  | Performance: "
    . number_format($loss01['performance'], 2)
    . "<br>";

echo "0-2  | Performance: "
    . number_format($loss02['performance'], 2)
    . "<br>";

echo "0-3  | Performance: "
    . number_format($loss03['performance'], 2)
    . "<br>";


/*
 * ------------------------------------------------------------
 * Result ordering
 * ------------------------------------------------------------
 */

testResult(
    'A win produces a higher rating than a draw',
    $win10['performance'] > $draw11['performance']
);


testResult(
    'A draw produces a higher rating than a loss',
    $draw11['performance'] > $loss01['performance']
);


/*
 * ------------------------------------------------------------
 * Increasing winning margin
 * ------------------------------------------------------------
 */

testResult(
    '2-0 produces a higher rating than 1-0',
    $win20['performance'] > $win10['performance']
);


testResult(
    '3-0 produces a higher rating than 2-0',
    $win30['performance'] > $win20['performance']
);


/*
 * ------------------------------------------------------------
 * Increasing defeat margin
 * ------------------------------------------------------------
 */

testResult(
    '2-0 defeat is worse than 1-0 defeat',
    $loss02['performance'] < $loss01['performance']
);


testResult(
    '3-0 defeat is worse than 2-0 defeat',
    $loss03['performance'] < $loss02['performance']
);


/*
 * ------------------------------------------------------------
 * Clean-sheet test
 * ------------------------------------------------------------
 *
 * Compare:
 *
 * 2-0
 * 2-1
 *
 * Both are wins with the same goals scored.
 *
 * The clean sheet should produce the higher
 * defensive/performance rating.
 */

$win21 =
    analyseTestScoreline(
        $teamPerformance,
        $teamStrengthModel,
        $baselines[1],
        2,
        1
    );


testResult(
    '2-0 produces a higher rating than 2-1',
    $win20['performance'] > $win21['performance']
);


/*
 * ------------------------------------------------------------
 * Draw comparison
 * ------------------------------------------------------------
 *
 * 0-0 and 1-1 are both draws.
 *
 * The 1-1 result should generally produce
 * a different performance rating because
 * attacking output and defensive output differ.
 */

testResult(
    '0-0 and 1-1 produce different performance ratings',
    $draw00['performance'] !== $draw11['performance']
);


/*
 * ------------------------------------------------------------
 * Rating boundaries
 * ------------------------------------------------------------
 */

$scorelineRatings = [

    $win10['performance'],
    $win20['performance'],
    $win30['performance'],
    $draw00['performance'],
    $draw11['performance'],
    $loss01['performance'],
    $loss02['performance'],
    $loss03['performance']
];


testResult(
    'All scoreline ratings are between 0 and 100',
    min($scorelineRatings) >= 0
    &&
    max($scorelineRatings) <= 100
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "\n<br>";
echo "============================================\n";
echo "Team Strength Historical Test Summary\n";
echo "============================================\n<br>";

echo "Passed: {$passed}\n<br>";
echo "Failed: {$failed}\n<br>";

echo "\n";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅\n";

} else {

    echo "RESULT: TESTS FAILED ❌\n";
}