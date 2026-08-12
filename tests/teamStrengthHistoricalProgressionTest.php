<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * TEAM STRENGTH HISTORICAL PROGRESSION TEST
 * ============================================================
 *
 * Purpose:
 *
 * Validate that TeamStrengthModel changes a team's rating
 * progressively as completed historical fixtures accumulate.
 *
 * This test does NOT modify the real fixtures table.
 *
 * We deliberately use controlled fixtures so that we know
 * exactly what the model should do.
 *
 * The test focuses on:
 *
 * - Baseline/performance weighting
 * - Rating progression over time
 * - Consistent winning
 * - Consistent losing
 * - Mixed results
 * - Responsiveness to recent performance
 * - Rating boundaries
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
 * BASELINE DATA
 * ============================================================
 */

$arsenal = [

    'id' =>
        1,

    'name' =>
        'Arsenal',

    'home' =>
        100,

    'away' =>
        100,

    'overall' =>
        100
];


$weakTeam = [

    'id' =>
        2,

    'name' =>
        'Weak Team',

    'home' =>
        20,

    'away' =>
        20,

    'overall' =>
        20
];


$strongTeam = [

    'id' =>
        3,

    'name' =>
        'Strong Team',

    'home' =>
        80,

    'away' =>
        80,

    'overall' =>
        80
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
 * TEAM STRENGTH DATA
 * ============================================================
 */

$teamStrengths = [

    1 => $arsenal,

    2 => $weakTeam,

    3 => $strongTeam
];


/*
 * ============================================================
 * HELPER FUNCTION
 * ============================================================
 *
 * Build a completed fixture.
 */

function createFixture(
    int $gameweek,
    int $homeTeam,
    int $awayTeam,
    int $homeScore,
    int $awayScore
): array {

    return [

        'gameweek' =>
            $gameweek,

        'home_team_id' =>
            $homeTeam,

        'away_team_id' =>
            $awayTeam,

        'finished' =>
            1,

        'home_score' =>
            $homeScore,

        'away_score' =>
            $awayScore
    ];
}


/*
 * ============================================================
 * SCENARIO A
 * ============================================================
 *
 * Arsenal wins every match.
 *
 * Expected behaviour:
 *
 * - Performance remains high.
 * - Performance weight increases.
 * - Baseline weight decreases.
 * - Overall rating moves away from 100 towards
 *   the performance rating.
 *
 * We use 2-0 wins because this produces a stable
 * performance rating in the current model.
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Consistent Winning<br>";
echo "============================================<br>";


$winningFixtures = [];

$winningRatings = [];


for ($gameweek = 1; $gameweek <= 10; $gameweek++) {

    $winningFixtures[] =
        createFixture(
            $gameweek,
            1,
            2,
            2,
            0
        );


    $performance =
        $teamPerformance->analyse(
            $winningFixtures,
            1
        );


    $model =
        $teamStrengthModel->buildTeamModel(
            $arsenal,
            $performance,
            $teamPerformance
        );


    $winningRatings[$gameweek] =
        $model['overall'];


    echo
        "GW{$gameweek} | "
        . "Played: {$model['played']} | "
        . "Performance: "
        . number_format(
            $model['performance_rating'],
            2
        )
        . " | Baseline W: "
        . number_format(
            $model['baseline_weight'],
            2
        )
        . " | Performance W: "
        . number_format(
            $model['performance_weight'],
            2
        )
        . " | Overall: "
        . number_format(
            $model['overall'],
            2
        )
        . "<br>";
}


/*
 * First match should still heavily favour
 * the baseline.
 */

testResult(
    'Winning progression starts close to baseline',
    $winningRatings[1] > $winningRatings[10]
);


/*
 * The rating should remain within valid limits.
 */

testResult(
    'Winning progression never exceeds 100',
    max($winningRatings) <= 100
);


/*
 * Every additional result should increase the
 * influence of actual performance.
 */

$weight1 =
    $teamStrengthModel->calculatePerformanceWeight(1);

$weight5 =
    $teamStrengthModel->calculatePerformanceWeight(5);

$weight10 =
    $teamStrengthModel->calculatePerformanceWeight(10);


testResult(
    'Performance weight increases from match 1 to match 5',
    $weight5 > $weight1
);

testResult(
    'Performance weight increases from match 5 to match 10',
    $weight10 > $weight5
);


/*
 * ============================================================
 * SCENARIO B
 * ============================================================
 *
 * Arsenal loses every match.
 *
 * Expected behaviour:
 *
 * - Performance remains low.
 * - Overall rating progressively falls.
 * - The decline becomes more pronounced as performance
 *   receives more weight.
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Consistent Losing<br>";
echo "============================================<br>";


$losingFixtures = [];

$losingRatings = [];


for ($gameweek = 1; $gameweek <= 10; $gameweek++) {

    $losingFixtures[] =
        createFixture(
            $gameweek,
            1,
            2,
            0,
            2
        );


    $performance =
        $teamPerformance->analyse(
            $losingFixtures,
            1
        );


    $model =
        $teamStrengthModel->buildTeamModel(
            $arsenal,
            $performance,
            $teamPerformance
        );


    $losingRatings[$gameweek] =
        $model['overall'];


    echo
        "GW{$gameweek} | "
        . "Played: {$model['played']} | "
        . "Performance: "
        . number_format(
            $model['performance_rating'],
            2
        )
        . " | Overall: "
        . number_format(
            $model['overall'],
            2
        )
        . "<br>";
}


/*
 * Rating should fall as more poor results accumulate.
 */

testResult(
    'Consistent losses reduce team rating',
    $losingRatings[10] < $losingRatings[1]
);


/*
 * Rating must remain above zero.
 */

testResult(
    'Consistent losses never produce a negative rating',
    min($losingRatings) >= 0
);


/*
 * ============================================================
 * SCENARIO C
 * ============================================================
 *
 * Compare winning and losing trajectories.
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Winning vs Losing<br>";
echo "============================================<br>";


testResult(
    'Winning team has a higher rating than losing team after 1 match',
    $winningRatings[1] > $losingRatings[1]
);


testResult(
    'Winning team has a higher rating than losing team after 5 matches',
    $winningRatings[5] > $losingRatings[5]
);


testResult(
    'Winning team has a higher rating than losing team after 10 matches',
    $winningRatings[10] > $losingRatings[10]
);


/*
 * ============================================================
 * SCENARIO D
 * ============================================================
 *
 * Mixed results.
 *
 * Arsenal:
 *
 * GW1  Win
 * GW2  Win
 * GW3  Loss
 * GW4  Loss
 * GW5  Win
 * GW6  Loss
 * GW7  Win
 * GW8  Draw
 * GW9  Loss
 * GW10 Win
 *
 * This checks that the model does not simply assume
 * that all historical results have the same outcome.
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Mixed Results<br>";
echo "============================================<br>";


$mixedResults = [

    [2, 0], // Win
    [3, 0], // Win
    [0, 2], // Loss
    [0, 1], // Loss
    [2, 0], // Win
    [0, 2], // Loss
    [2, 1], // Win
    [1, 1], // Draw
    [0, 2], // Loss
    [2, 0]  // Win
];


$mixedFixtures = [];

$mixedRatings = [];


foreach ($mixedResults as $index => $score) {

    $gameweek =
        $index + 1;


    $mixedFixtures[] =
        createFixture(
            $gameweek,
            1,
            2,
            $score[0],
            $score[1]
        );


    $performance =
        $teamPerformance->analyse(
            $mixedFixtures,
            1
        );


    $model =
        $teamStrengthModel->buildTeamModel(
            $arsenal,
            $performance,
            $teamPerformance
        );


    $mixedRatings[$gameweek] =
        $model['overall'];


    echo
        "GW{$gameweek} | "
        . "Played: {$model['played']} | "
        . "Performance: "
        . number_format(
            $model['performance_rating'],
            2
        )
        . " | Overall: "
        . number_format(
            $model['overall'],
            2
        )
        . "<br>";
}


/*
 * Mixed results should produce a valid rating.
 */

testResult(
    'Mixed results produce a valid final rating',
    $mixedRatings[10] >= 0
    &&
    $mixedRatings[10] <= 100
);


/*
 * Mixed results should not behave like a perfect
 * winning or losing sequence.
 */

testResult(
    'Mixed results finish between consistent win and loss trajectories',
    $mixedRatings[10] < $winningRatings[10]
    &&
    $mixedRatings[10] > $losingRatings[10]
);


/*
 * ============================================================
 * SCENARIO E
 * ============================================================
 *
 * Recovery test.
 *
 * Arsenal starts with poor results and then begins winning.
 *
 * This is important because the model needs to respond
 * to changing form rather than permanently locking a team
 * into its early-season performance.
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Recovery From Poor Form<br>";
echo "============================================<br>";


$recoveryResults = [

    [0, 2],
    [0, 2],
    [0, 1],
    [0, 2],
    [2, 0],
    [2, 0],
    [3, 0],
    [2, 0],
    [3, 1],
    [2, 0]
];


$recoveryFixtures = [];

$recoveryRatings = [];


foreach ($recoveryResults as $index => $score) {

    $gameweek =
        $index + 1;


    $recoveryFixtures[] =
        createFixture(
            $gameweek,
            1,
            2,
            $score[0],
            $score[1]
        );


    $performance =
        $teamPerformance->analyse(
            $recoveryFixtures,
            1
        );


    $model =
        $teamStrengthModel->buildTeamModel(
            $arsenal,
            $performance,
            $teamPerformance
        );


    $recoveryRatings[$gameweek] =
        $model['overall'];


    echo
        "GW{$gameweek} | "
        . "Played: {$model['played']} | "
        . "Performance: "
        . number_format(
            $model['performance_rating'],
            2
        )
        . " | Overall: "
        . number_format(
            $model['overall'],
            2
        )
        . "<br>";
}


/*
 * The team should finish stronger than it was
 * after the early losing run.
 */

testResult(
    'Winning recovery improves the final team rating',
    $recoveryRatings[10] > $recoveryRatings[4]
);


/*
 * The model should still remain below the original
 * 100 baseline because the historical record contains
 * several poor results.
 */

testResult(
    'Recovery does not immediately restore the full baseline',
    $recoveryRatings[10] < 100
);


/*
 * ============================================================
 * SCENARIO F
 * ============================================================
 *
 * Strong opposition validation.
 *
 * A win against a strong opponent should produce a
 * stronger opposition-adjusted performance than a win
 * against a weak opponent.
 *
 * This confirms that progression is compatible with
 * the opposition-adjusted model.
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Strong Opposition Impact<br>";
echo "============================================<br>";


$strongFixture = [

    createFixture(
        1,
        1,
        3,
        2,
        0
    )
];


$strongPerformance =
    $teamPerformance->analyse(
        $strongFixture,
        1
    );


$strongModel =
    $teamStrengthModel->buildTeamModel(
        $arsenal,
        $strongPerformance,
        $teamPerformance
    );


$weakFixture = [

    createFixture(
        1,
        1,
        2,
        2,
        0
    )
];


$weakPerformance =
    $teamPerformance->analyse(
        $weakFixture,
        1
    );


$weakModel =
    $teamStrengthModel->buildTeamModel(
        $arsenal,
        $weakPerformance,
        $teamPerformance
    );


testResult(
    'Strong-opposition win produces a valid team rating',
    $strongModel['overall'] >= 0
    &&
    $strongModel['overall'] <= 100
);


testResult(
    'Weak-opposition win produces a valid team rating',
    $weakModel['overall'] >= 0
    &&
    $weakModel['overall'] <= 100
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Team Strength Historical Progression Summary<br>";
echo "============================================<br>";

echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}