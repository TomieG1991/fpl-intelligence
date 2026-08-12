<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * TEAM STRENGTH HISTORICAL DECAY INTEGRATION TEST
 * ============================================================
 *
 * Tests the interaction between:
 *
 * TeamPerformance
 * OppositionAdjustedPerformance
 * TeamStrengthHistoricalDecay
 * TeamStrengthModel
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
 * MODELS
 * ============================================================
 */

$teamPerformance =
    new TeamPerformance();

$oppositionAdjusted =
    new OppositionAdjustedPerformance();

$historicalDecay =
    new TeamStrengthHistoricalDecay();

$teamStrengthModel =
    new TeamStrengthModel();


/*
 * ============================================================
 * TEST TEAM STRENGTHS
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
    ],

    3 => [
        'id' => 3,
        'name' => 'Strong Team',
        'home' => 80,
        'away' => 80,
        'overall' => 80
    ]

];


/*
 * ============================================================
 * SCENARIO A
 * ============================================================
 *
 * Arsenal beats a strong team 2-0.
 *
 * Normal performance:
 *     95
 *
 * Opposition expected:
 *     35
 *
 * Opposition delta:
 *     +65
 */

heading('Scenario A: Strong Opposition Win');


$strongWinFixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 3,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ]

];


$performanceA =
    $teamPerformance->analyse(
        $strongWinFixtures,
        1
    );


$performanceRatingA =
    $teamPerformance->calculatePerformanceRating(
        $performanceA
    );


$oppositionA =
    $oppositionAdjusted->analyse(
        $strongWinFixtures,
        $teamStrengths,
        1
    );


$decayA =
    $historicalDecay->calculateWeightedPerformance(
        [
            [
                'performance' =>
                    $performanceRatingA
            ]
        ]
    );


$modelA =
    $teamStrengthModel->buildTeamModel(
        $teamStrengths[1],
        $performanceA,
        $teamPerformance
    );


echo "Performance: "
    . number_format(
        $performanceRatingA,
        2
    )
    . "<br>";

echo "Opposition Expected: "
    . number_format(
        $oppositionA['matches'][0]['expected_performance'],
        2
    )
    . "<br>";

echo "Opposition Delta: "
    . number_format(
        $oppositionA['average_delta'],
        2
    )
    . "<br>";

echo "Decay Performance: "
    . number_format(
        $decayA,
        2
    )
    . "<br>";

echo "Overall Rating: "
    . number_format(
        $modelA['overall'],
        2
    )
    . "<br>";


testResult(
    'Strong-opposition win analyses one match',
    $performanceA['played'] === 1
);

testResult(
    'Strong-opposition win produces performance rating',
    $performanceRatingA !== null
);

testResult(
    'Strong-opposition win produces positive performance',
    $performanceRatingA > 0
);

testResult(
    'Strong-opposition win produces +65 opposition delta',
    $oppositionA['average_delta'] === 65.0
);

testResult(
    'Decay preserves single-match performance',
    $decayA === $performanceRatingA
);

testResult(
    'Team model uses the calculated performance rating',
    $modelA['performance_rating'] === $performanceRatingA
);


/*
 * ============================================================
 * SCENARIO B
 * ============================================================
 *
 * Arsenal loses 0-2 to a weak team.
 *
 * Normal performance:
 *     6.67
 *
 * Opposition expected:
 *     65
 *
 * Opposition delta:
 *     -65
 */

heading('Scenario B: Weak Opposition Loss');


$weakLossFixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2,
        'finished' => 1,
        'home_score' => 0,
        'away_score' => 2
    ]

];


$performanceB =
    $teamPerformance->analyse(
        $weakLossFixtures,
        1
    );


$performanceRatingB =
    $teamPerformance->calculatePerformanceRating(
        $performanceB
    );


$oppositionB =
    $oppositionAdjusted->analyse(
        $weakLossFixtures,
        $teamStrengths,
        1
    );


$decayB =
    $historicalDecay->calculateWeightedPerformance(
        [
            [
                'performance' =>
                    $performanceRatingB
            ]
        ]
    );


$modelB =
    $teamStrengthModel->buildTeamModel(
        $teamStrengths[1],
        $performanceB,
        $teamPerformance
    );


echo "Performance: "
    . number_format(
        $performanceRatingB,
        2
    )
    . "<br>";

echo "Opposition Expected: "
    . number_format(
        $oppositionB['matches'][0]['expected_performance'],
        2
    )
    . "<br>";

echo "Opposition Delta: "
    . number_format(
        $oppositionB['average_delta'],
        2
    )
    . "<br>";

echo "Overall Rating: "
    . number_format(
        $modelB['overall'],
        2
    )
    . "<br>";


testResult(
    'Weak-opposition loss analyses one match',
    $performanceB['played'] === 1
);

testResult(
    'Weak-opposition loss produces performance rating',
    $performanceRatingB !== null
);

testResult(
    'Weak-opposition loss produces negative opposition delta',
    $oppositionB['average_delta'] < 0
);

testResult(
    'Weak-opposition loss produces -65 opposition delta',
    $oppositionB['average_delta'] === -65.0
);

testResult(
    'Weak-opposition loss produces lower performance than strong win',
    $performanceRatingB < $performanceRatingA
);

testResult(
    'Weak-opposition loss produces lower overall rating',
    $modelB['overall'] < $modelA['overall']
);


/*
 * ============================================================
 * SCENARIO C
 * ============================================================
 *
 * Historical decay:
 *
 * Old win + recent loss
 *
 * The recent loss should have greater influence.
 */

heading('Scenario C: Historical Decay - Recent Loss');


$oldWinRecentLoss = [

    [
        'performance' => 100
    ],

    [
        'performance' => 0
    ]

];


$decayC =
    $historicalDecay->calculateWeightedPerformance(
        $oldWinRecentLoss
    );


echo "Weighted Performance: "
    . number_format(
        $decayC,
        2
    )
    . "<br>";


testResult(
    'Recent loss receives greater influence',
    $decayC < 50
);

testResult(
    'Decay produces a valid performance rating',
    $decayC >= 0
    &&
    $decayC <= 100
);


/*
 * ============================================================
 * SCENARIO D
 * ============================================================
 *
 * Old loss + recent win.
 */

heading('Scenario D: Historical Decay - Recent Win');


$oldLossRecentWin = [

    [
        'performance' => 0
    ],

    [
        'performance' => 100
    ]

];


$decayD =
    $historicalDecay->calculateWeightedPerformance(
        $oldLossRecentWin
    );


echo "Weighted Performance: "
    . number_format(
        $decayD,
        2
    )
    . "<br>";


testResult(
    'Recent win receives greater influence',
    $decayD > 50
);

testResult(
    'Recent win produces higher rating than recent loss',
    $decayD > $decayC
);


/*
 * ============================================================
 * SCENARIO E
 * ============================================================
 *
 * Two identical wins against strong opposition.
 *
 * The decay model should process both matches and preserve
 * the performance value because both performances are equal.
 */

heading('Scenario E: Two-Match Strong Opposition History');


$twoStrongWins = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 3,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ],

    [
        'gameweek' => 2,
        'home_team_id' => 1,
        'away_team_id' => 3,
        'finished' => 1,
        'home_score' => 2,
        'away_score' => 0
    ]

];


$performanceE =
    $teamPerformance->analyse(
        $twoStrongWins,
        1
    );


$performanceRatingE =
    $teamPerformance->calculatePerformanceRating(
        $performanceE
    );


$oppositionE =
    $oppositionAdjusted->analyse(
        $twoStrongWins,
        $teamStrengths,
        1
    );


$decayE =
    $historicalDecay->calculateWeightedPerformance(
        [
            [
                'performance' =>
                    $performanceRatingE
            ],
            [
                'performance' =>
                    $performanceRatingE
            ]
        ]
    );


echo "Matches: "
    . $performanceE['played']
    . "<br>";

echo "Performance: "
    . number_format(
        $performanceRatingE,
        2
    )
    . "<br>";

echo "Opposition Delta: "
    . number_format(
        $oppositionE['average_delta'],
        2
    )
    . "<br>";

echo "Decay Performance: "
    . number_format(
        $decayE,
        2
    )
    . "<br>";


testResult(
    'Two strong-opposition wins are analysed',
    $performanceE['played'] === 2
);

testResult(
    'Both opposition-adjusted deltas are positive',
    $oppositionE['average_delta'] > 0
);

testResult(
    'Decay processes both matches',
    $decayE !== null
);

testResult(
    'Identical performances remain unchanged by decay',
    $decayE === $performanceRatingE
);


/*
 * ============================================================
 * SCENARIO F
 * ============================================================
 *
 * Venue + opposition + decay.
 *
 * GW1:
 * Arsenal home vs Weak Team
 *
 * GW2:
 * Strong Team home vs Arsenal
 *
 * The second match is away for Arsenal and therefore uses
 * Strong Team's HOME strength.
 */

heading('Scenario F: Venue + Opposition + Decay');


$venueFixtures = [

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
        'away_score' => 2
    ]

];


$oppositionF =
    $oppositionAdjusted->analyse(
        $venueFixtures,
        $teamStrengths,
        1
    );


$firstMatch =
    $oppositionF['matches'][0];

$secondMatch =
    $oppositionF['matches'][1];


echo "GW1 Venue: "
    . $firstMatch['venue']
    . "<br>";

echo "GW1 Opponent Strength: "
    . $firstMatch['opponent_strength']
    . "<br>";

echo "GW2 Venue: "
    . $secondMatch['venue']
    . "<br>";

echo "GW2 Opponent Strength: "
    . $secondMatch['opponent_strength']
    . "<br>";


testResult(
    'Venue history contains two matches',
    $oppositionF['played'] === 2
);

testResult(
    'GW1 is identified as Home',
    $firstMatch['venue'] === 'Home'
);

testResult(
    'GW2 is identified as Away',
    $secondMatch['venue'] === 'Away'
);

testResult(
    'Home fixture uses opponent away strength',
    $firstMatch['opponent_strength'] === 20.0
);

testResult(
    'Away fixture uses opponent home strength',
    $secondMatch['opponent_strength'] === 80.0
);

testResult(
    'Recent away win against strong opposition has positive delta',
    $secondMatch['performance_delta'] > 0
);


/*
 * ============================================================
 * SCENARIO G
 * ============================================================
 *
 * Full TeamStrengthModel integration.
 *
 * A single strong-opposition win should move the rating away
 * from the baseline while remaining below or equal to 100.
 */

heading('Scenario G: Full Team Strength Integration');


$modelG =
    $teamStrengthModel->buildTeamModel(
        $teamStrengths[1],
        $performanceA,
        $teamPerformance
    );


echo "Baseline: "
    . $teamStrengths[1]['overall']
    . "<br>";

echo "Performance: "
    . number_format(
        $modelG['performance_rating'],
        2
    )
    . "<br>";

echo "Overall: "
    . number_format(
        $modelG['overall'],
        2
    )
    . "<br>";


testResult(
    'Team model contains performance rating',
    $modelG['performance_rating'] !== null
);

testResult(
    'Team model remains at or below 100',
    $modelG['overall'] <= 100
);

testResult(
    'Team model remains above baseline protection threshold',
    $modelG['overall'] > 90
);


/*
 * ============================================================
 * SCENARIO H
 * ============================================================
 *
 * Recovery:
 *
 * Three poor performances followed by two strong
 * performances.
 *
 * The recent performances should have greater influence.
 */

heading('Scenario H: Historical Recovery');


$recoveryMatches = [

    [
        'performance' => 0
    ],

    [
        'performance' => 0
    ],

    [
        'performance' => 0
    ],

    [
        'performance' => 100
    ],

    [
        'performance' => 100
    ]

];


$recoveryPerformance =
    $historicalDecay->calculateWeightedPerformance(
        $recoveryMatches
    );


echo "Recovery Performance: "
    . number_format(
        $recoveryPerformance,
        2
    )
    . "<br>";


testResult(
    'Recent wins improve recovery performance',
    $recoveryPerformance > 0
);

testResult(
    'Recovery remains below 100',
    $recoveryPerformance < 100
);


/*
 * ============================================================
 * SCENARIO I
 * ============================================================
 *
 * Ten-match progression:
 *
 * Five losses followed by five wins.
 *
 * The recent wins should dominate because of decay.
 */

heading('Scenario I: Ten-Match Historical Progression');


$progressionMatches = [

    [
        'performance' => 0
    ],

    [
        'performance' => 0
    ],

    [
        'performance' => 0
    ],

    [
        'performance' => 0
    ],

    [
        'performance' => 0
    ],

    [
        'performance' => 100
    ],

    [
        'performance' => 100
    ],

    [
        'performance' => 100
    ],

    [
        'performance' => 100
    ],

    [
        'performance' => 100
    ]

];


$progressionPerformance =
    $historicalDecay->calculateWeightedPerformance(
        $progressionMatches
    );


echo "Weighted Performance: "
    . number_format(
        $progressionPerformance,
        2
    )
    . "<br>";


testResult(
    'Ten-match progression contains ten matches',
    count($progressionMatches) === 10
);

testResult(
    'Ten-match progression produces a rating',
    $progressionPerformance !== null
);

testResult(
    'Recent five wins outweigh early five losses',
    $progressionPerformance > 50
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Team Strength Historical Decay Integration Summary<br>";
echo "============================================<br>";

echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";

echo "<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}