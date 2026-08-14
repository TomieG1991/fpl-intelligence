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

$fixtureIntelligence =
    new FixtureIntelligence();


/*
 * ============================================================
 * TEST TEAM STRENGTH DATA
 * ============================================================
 */

$teamStrengths = [

    1 => [
        'name' => 'Test Team A',
        'home' => 80.00,
        'away' => 70.00
    ],

    2 => [
        'name' => 'Test Team B',
        'home' => 60.00,
        'away' => 50.00
    ],

    3 => [
        'name' => 'Test Team C',
        'home' => 40.00,
        'away' => 30.00
    ],

    4 => [
        'name' => 'Test Team D',
        'home' => 90.00,
        'away' => 85.00
    ]
];


/*
 * ============================================================
 * TEST FIXTURES
 * ============================================================
 */

$fixtures = [

    [
        'gameweek' => 1,
        'home_team_id' => 1,
        'away_team_id' => 2
    ],

    [
        'gameweek' => 2,
        'home_team_id' => 3,
        'away_team_id' => 1
    ],

    [
        'gameweek' => 3,
        'home_team_id' => 1,
        'away_team_id' => 4
    ],

    [
        'gameweek' => 4,
        'home_team_id' => 2,
        'away_team_id' => 1
    ],

    [
        'gameweek' => 5,
        'home_team_id' => 1,
        'away_team_id' => 3
    ],

    [
        'gameweek' => 6,
        'home_team_id' => 4,
        'away_team_id' => 1
    ],

    [
        'gameweek' => 7,
        'home_team_id' => 1,
        'away_team_id' => 2
    ],

    [
        'gameweek' => 8,
        'home_team_id' => 3,
        'away_team_id' => 1
    ],

    [
        'gameweek' => 9,
        'home_team_id' => 1,
        'away_team_id' => 2
    ],

    [
        'gameweek' => 10,
        'home_team_id' => 4,
        'away_team_id' => 1
    ]
];


/*
 * ============================================================
 * SCENARIO A
 * Basic Matchup Calculation
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Basic Matchup Calculation<br>";
echo "============================================<br>";


$matchup =
    $fixtureIntelligence->calculateMatchup(
        80.00,
        50.00
    );


echo "Team Strength: 80.00<br>";
echo "Opponent Strength: 50.00<br>";
echo "Matchup: "
    . number_format($matchup, 2)
    . "<br>";


testPass(
    'Positive matchup is calculated correctly',
    $matchup === 30.00
);


$negativeMatchup =
    $fixtureIntelligence->calculateMatchup(
        50.00,
        80.00
    );


echo "Negative Matchup: "
    . number_format($negativeMatchup, 2)
    . "<br>";


testPass(
    'Negative matchup is calculated correctly',
    $negativeMatchup === -30.00
);


$balancedMatchup =
    $fixtureIntelligence->calculateMatchup(
        70.00,
        70.00
    );


testPass(
    'Equal strengths produce zero matchup',
    $balancedMatchup === 0.00
);


/*
 * ============================================================
 * SCENARIO B
 * Fixture Score
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Fixture Score<br>";
echo "============================================<br>";


$favourableScore =
    $fixtureIntelligence->calculateFixtureScore(
        80.00,
        50.00
    );


echo "Favourable Fixture Score: "
    . number_format($favourableScore, 2)
    . "<br>";


testPass(
    'Favourable fixture score is calculated correctly',
    $favourableScore === 65.00
);


$balancedScore =
    $fixtureIntelligence->calculateFixtureScore(
        70.00,
        70.00
    );


echo "Balanced Fixture Score: "
    . number_format($balancedScore, 2)
    . "<br>";


testPass(
    'Balanced fixture produces 50 score',
    $balancedScore === 50.00
);


$unfavourableScore =
    $fixtureIntelligence->calculateFixtureScore(
        40.00,
        90.00
    );


echo "Unfavourable Fixture Score: "
    . number_format($unfavourableScore, 2)
    . "<br>";


testPass(
    'Unfavourable fixture score is calculated correctly',
    $unfavourableScore === 25.00
);


testPass(
    'Fixture score remains between 0 and 100',
    $favourableScore >= 0
    &&
    $favourableScore <= 100
);


/*
 * ============================================================
 * SCENARIO C
 * Difficulty Calculation
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Fixture Difficulty<br>";
echo "============================================<br>";


$difficulty1 =
    $fixtureIntelligence->calculateDifficulty(
        90.00
    );

$difficulty2 =
    $fixtureIntelligence->calculateDifficulty(
        75.00
    );

$difficulty3 =
    $fixtureIntelligence->calculateDifficulty(
        60.00
    );

$difficulty4 =
    $fixtureIntelligence->calculateDifficulty(
        45.00
    );

$difficulty5 =
    $fixtureIntelligence->calculateDifficulty(
        20.00
    );


echo "Score 90: Difficulty {$difficulty1}<br>";
echo "Score 75: Difficulty {$difficulty2}<br>";
echo "Score 60: Difficulty {$difficulty3}<br>";
echo "Score 45: Difficulty {$difficulty4}<br>";
echo "Score 20: Difficulty {$difficulty5}<br>";


testPass(
    'Score 90 produces difficulty 1',
    $difficulty1 === 1
);

testPass(
    'Score 75 produces difficulty 2',
    $difficulty2 === 2
);

testPass(
    'Score 60 produces difficulty 3',
    $difficulty3 === 3
);

testPass(
    'Score 45 produces difficulty 4',
    $difficulty4 === 4
);

testPass(
    'Score 20 produces difficulty 5',
    $difficulty5 === 5
);


/*
 * ============================================================
 * SCENARIO D
 * Difficulty Labels
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Difficulty Labels<br>";
echo "============================================<br>";


$label1 =
    $fixtureIntelligence->getDifficultyLabel(1);

$label2 =
    $fixtureIntelligence->getDifficultyLabel(2);

$label3 =
    $fixtureIntelligence->getDifficultyLabel(3);

$label4 =
    $fixtureIntelligence->getDifficultyLabel(4);

$label5 =
    $fixtureIntelligence->getDifficultyLabel(5);


echo "Difficulty 1: {$label1}<br>";
echo "Difficulty 2: {$label2}<br>";
echo "Difficulty 3: {$label3}<br>";
echo "Difficulty 4: {$label4}<br>";
echo "Difficulty 5: {$label5}<br>";


testPass(
    'Difficulty 1 label is Excellent',
    $label1 === 'Excellent'
);

testPass(
    'Difficulty 2 label is Good',
    $label2 === 'Good'
);

testPass(
    'Difficulty 3 label is Average',
    $label3 === 'Average'
);

testPass(
    'Difficulty 4 label is Difficult',
    $label4 === 'Difficult'
);

testPass(
    'Difficulty 5 label is Very Difficult',
    $label5 === 'Very Difficult'
);

testPass(
    'Invalid difficulty returns Unknown',
    $fixtureIntelligence->getDifficultyLabel(99) === 'Unknown'
);


/*
 * ============================================================
 * SCENARIO E
 * Fixture Run Analysis
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Fixture Run Analysis<br>";
echo "============================================<br>";


$fixtureRun =
    $fixtureIntelligence->analyseFixtureRun(
        $fixtures,
        $teamStrengths,
        1
    );


echo "Fixtures Analysed: "
    . count($fixtureRun)
    . "<br>";


testPass(
    'Fixture run returns an array',
    is_array($fixtureRun)
);

testPass(
    'All relevant fixtures are included',
    count($fixtureRun) === 10
);

testPass(
    'First fixture is Gameweek 1',
    $fixtureRun[0]['gameweek'] === 1
);

testPass(
    'Fixtures are ordered by gameweek',
    $fixtureRun[0]['gameweek']
    <
    $fixtureRun[1]['gameweek']
);


/*
 * ============================================================
 * SCENARIO F
 * Home Fixture Analysis
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Home Fixture Analysis<br>";
echo "============================================<br>";


$homeFixture =
    $fixtureRun[0];


echo "Gameweek: "
    . $homeFixture['gameweek']
    . "<br>";

echo "Home Team: "
    . $homeFixture['home_team']
    . "<br>";

echo "Away Team: "
    . $homeFixture['away_team']
    . "<br>";

echo "Venue: "
    . $homeFixture['venue']
    . "<br>";

echo "Team Baseline: "
    . number_format(
        $homeFixture['team_baseline'],
        2
    )
    . "<br>";

echo "Opponent Baseline: "
    . number_format(
        $homeFixture['opponent_baseline'],
        2
    )
    . "<br>";

echo "Fixture Score: "
    . number_format(
        $homeFixture['fixture_score'],
        2
    )
    . "<br>";

echo "Difficulty: "
    . $homeFixture['difficulty']
    . "<br>";

echo "Difficulty Label: "
    . $homeFixture['difficulty_label']
    . "<br>";


testPass(
    'Home fixture is identified correctly',
    $homeFixture['is_home'] === true
);

testPass(
    'Home fixture venue is Home',
    $homeFixture['venue'] === 'Home'
);

testPass(
    'Home team baseline uses home strength',
    $homeFixture['team_baseline'] === 80.00
);

testPass(
    'Opponent baseline uses away strength',
    $homeFixture['opponent_baseline'] === 50.00
);

testPass(
    'Home fixture score is correct',
    $homeFixture['fixture_score'] === 65.00
);


/*
 * ============================================================
 * SCENARIO G
 * Away Fixture Analysis
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Away Fixture Analysis<br>";
echo "============================================<br>";


$awayFixture =
    $fixtureRun[1];


echo "Gameweek: "
    . $awayFixture['gameweek']
    . "<br>";

echo "Venue: "
    . $awayFixture['venue']
    . "<br>";

echo "Team Baseline: "
    . number_format(
        $awayFixture['team_baseline'],
        2
    )
    . "<br>";

echo "Opponent Baseline: "
    . number_format(
        $awayFixture['opponent_baseline'],
        2
    )
    . "<br>";

echo "Fixture Score: "
    . number_format(
        $awayFixture['fixture_score'],
        2
    )
    . "<br>";


testPass(
    'Away fixture is identified correctly',
    $awayFixture['is_home'] === false
);

testPass(
    'Away fixture venue is Away',
    $awayFixture['venue'] === 'Away'
);

testPass(
    'Away fixture uses away team strength',
    $awayFixture['team_baseline'] === 70.00
);

testPass(
    'Away fixture uses opponent home strength',
    $awayFixture['opponent_baseline'] === 40.00
);

testPass(
    'Away fixture score is correct',
    $awayFixture['fixture_score'] === 65.00
);


/*
 * ============================================================
 * SCENARIO H
 * Fixture Structure
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Fixture Intelligence Structure<br>";
echo "============================================<br>";


$requiredFields = [

    'gameweek',
    'home_team',
    'away_team',
    'is_home',
    'venue',
    'team_baseline',
    'opponent_baseline',
    'home_baseline',
    'away_baseline',
    'matchup',
    'fixture_score',
    'difficulty',
    'difficulty_label'
];


foreach ($requiredFields as $field) {

    testPass(
        "Fixture contains {$field}",
        array_key_exists(
            $field,
            $homeFixture
        )
    );
}


/*
 * ============================================================
 * SCENARIO I
 * Rolling Averages
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Rolling Fixture Averages<br>";
echo "============================================<br>";


$next5 =
    $fixtureIntelligence->calculateRollingAverage(
        $fixtureRun,
        5
    );


echo "Next 5 Average: "
    . number_format(
        $next5,
        2
    )
    . "<br>";


testPass(
    'Next 5 rolling average is calculated',
    $next5 !== null
);

testPass(
    'Next 5 rolling average is within bounds',
    $next5 >= 0
    &&
    $next5 <= 100
);


$rollingAverages =
    $fixtureIntelligence->calculateRollingAverages(
        $fixtureRun
    );


testPass(
    'Rolling averages return an array',
    is_array($rollingAverages)
);

testPass(
    'Next 5 rolling average exists',
    array_key_exists(
        'next_5',
        $rollingAverages
    )
);

testPass(
    'Next 6 rolling average exists',
    array_key_exists(
        'next_6',
        $rollingAverages
    )
);

testPass(
    'Next 8 rolling average exists',
    array_key_exists(
        'next_8',
        $rollingAverages
    )
);

testPass(
    'Next 10 rolling average exists',
    array_key_exists(
        'next_10',
        $rollingAverages
    )
);


/*
 * ============================================================
 * SCENARIO J
 * Rolling Average Edge Cases
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario J: Rolling Average Edge Cases<br>";
echo "============================================<br>";


testPass(
    'Empty fixture list returns null rolling average',
    $fixtureIntelligence->calculateRollingAverage(
        [],
        5
    ) === null
);

testPass(
    'Zero gameweeks returns null',
    $fixtureIntelligence->calculateRollingAverage(
        $fixtureRun,
        0
    ) === null
);

testPass(
    'Negative gameweeks returns null',
    $fixtureIntelligence->calculateRollingAverage(
        $fixtureRun,
        -5
    ) === null
);

testPass(
    'Too few fixtures returns null',
    $fixtureIntelligence->calculateRollingAverage(
        array_slice($fixtureRun, 0, 3),
        5
    ) === null
);


/*
 * ============================================================
 * SCENARIO K
 * Best Fixture Run
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario K: Best Fixture Run<br>";
echo "============================================<br>";


$bestRun =
    $fixtureIntelligence->findBestRun(
        $fixtureRun,
        3
    );


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
    'Best run contains requested number of fixtures',
    count($bestRun['fixtures']) === 3
);

testPass(
    'Best run average is within bounds',
    $bestRun['average_score'] >= 0
    &&
    $bestRun['average_score'] <= 100
);

testPass(
    'Insufficient fixtures return null best run',
    $fixtureIntelligence->findBestRun(
        array_slice($fixtureRun, 0, 2),
        5
    ) === null
);


/*
 * ============================================================
 * SCENARIO L
 * Worst Fixture Run
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario L: Worst Fixture Run<br>";
echo "============================================<br>";


$worstRun =
    $fixtureIntelligence->findWorstRun(
        $fixtureRun,
        3
    );


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
    'Worst run contains requested number of fixtures',
    count($worstRun['fixtures']) === 3
);

testPass(
    'Worst run average is within bounds',
    $worstRun['average_score'] >= 0
    &&
    $worstRun['average_score'] <= 100
);

testPass(
    'Insufficient fixtures return null worst run',
    $fixtureIntelligence->findWorstRun(
        array_slice($fixtureRun, 0, 2),
        5
    ) === null
);


/*
 * ============================================================
 * SCENARIO M
 * Trend Analysis
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario M: Fixture Trend Analysis<br>";
echo "============================================<br>";


$improvingFixtures = [

    ['fixture_score' => 40],
    ['fixture_score' => 40],
    ['fixture_score' => 80],
    ['fixture_score' => 80]
];


$decliningFixtures = [

    ['fixture_score' => 80],
    ['fixture_score' => 80],
    ['fixture_score' => 40],
    ['fixture_score' => 40]
];


$stableFixtures = [

    ['fixture_score' => 60],
    ['fixture_score' => 60],
    ['fixture_score' => 65],
    ['fixture_score' => 65]
];


$improvingTrend =
    $fixtureIntelligence->calculateTrend(
        $improvingFixtures
    );

$decliningTrend =
    $fixtureIntelligence->calculateTrend(
        $decliningFixtures
    );

$stableTrend =
    $fixtureIntelligence->calculateTrend(
        $stableFixtures
    );


echo "Improving Trend: {$improvingTrend}<br>";
echo "Declining Trend: {$decliningTrend}<br>";
echo "Stable Trend: {$stableTrend}<br>";


testPass(
    'Improving fixture run is identified',
    $improvingTrend === 'Improving'
);

testPass(
    'Declining fixture run is identified',
    $decliningTrend === 'Declining'
);

testPass(
    'Stable fixture run is identified',
    $stableTrend === 'Stable'
);


testPass(
    'Insufficient trend data is handled',
    $fixtureIntelligence->calculateTrend(
        array_slice(
            $fixtureRun,
            0,
            3
        )
    ) === 'Insufficient Data'
);


/*
 * ============================================================
 * SCENARIO N
 * Non-Team Fixtures
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario N: Non-Team Fixture Filtering<br>";
echo "============================================<br>";


$teamTwoFixtures =
    $fixtureIntelligence->analyseFixtureRun(
        $fixtures,
        $teamStrengths,
        2
    );


foreach ($teamTwoFixtures as $fixture) {

    testPass(
        'Returned fixture involves requested team',
        $fixture['home_team'] === 'Test Team B'
        ||
        $fixture['away_team'] === 'Test Team B'
    );
}


/*
 * ============================================================
 * SCENARIO O
 * Empty Fixture Run
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario O: Empty Fixture Run<br>";
echo "============================================<br>";


$emptyRun =
    $fixtureIntelligence->analyseFixtureRun(
        [],
        $teamStrengths,
        1
    );


testPass(
    'Empty fixture list returns empty array',
    is_array($emptyRun)
    &&
    count($emptyRun) === 0
);

testPass(
    'Team with no fixtures returns empty array',
    count(
        $fixtureIntelligence->analyseFixtureRun(
            [
                [
                    'gameweek' => 1,
                    'home_team_id' => 2,
                    'away_team_id' => 3
                ]
            ],
            $teamStrengths,
            1
        )
    ) === 0
);

/*
 * ============================================================
 * SCENARIO P - PLAYER OPPORTUNITY RUNS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario P: Player Opportunity Best / Worst Runs<br>";
echo "============================================<br>";


$opportunityFixtures = [

    [
        'gameweek' => 1,
        'fixture_score' => 50.00,
        'opportunity_score' => 100.00
    ],

    [
        'gameweek' => 2,
        'fixture_score' => 50.00,
        'opportunity_score' => 90.00
    ],

    [
        'gameweek' => 3,
        'fixture_score' => 50.00,
        'opportunity_score' => 80.00
    ],

    [
        'gameweek' => 4,
        'fixture_score' => 50.00,
        'opportunity_score' => 70.00
    ],

    [
        'gameweek' => 5,
        'fixture_score' => 50.00,
        'opportunity_score' => 60.00
    ],

    [
        'gameweek' => 6,
        'fixture_score' => 50.00,
        'opportunity_score' => 50.00
    ],

    [
        'gameweek' => 7,
        'fixture_score' => 50.00,
        'opportunity_score' => 40.00
    ],

    [
        'gameweek' => 8,
        'fixture_score' => 50.00,
        'opportunity_score' => 30.00
    ],

    [
        'gameweek' => 9,
        'fixture_score' => 50.00,
        'opportunity_score' => 20.00
    ],

    [
        'gameweek' => 10,
        'fixture_score' => 50.00,
        'opportunity_score' => 10.00
    ]
];


$bestOpportunityRun =
    $fixtureIntelligence
        ->findBestOpportunityRun(
            $opportunityFixtures,
            5
        );


$worstOpportunityRun =
    $fixtureIntelligence
        ->findWorstOpportunityRun(
            $opportunityFixtures,
            5
        );


echo "Best Run: "
    . (
        $bestOpportunityRun !== null
            ? 'GW'
                . $bestOpportunityRun['start_gameweek']
                . '-GW'
                . $bestOpportunityRun['end_gameweek']
            : 'NULL'
    )
    . "<br>";


echo "Best Average: "
    . (
        $bestOpportunityRun !== null
            ? number_format(
                $bestOpportunityRun['average_score'],
                2
            )
            : 'NULL'
    )
    . "<br>";


echo "Worst Run: "
    . (
        $worstOpportunityRun !== null
            ? 'GW'
                . $worstOpportunityRun['start_gameweek']
                . '-GW'
                . $worstOpportunityRun['end_gameweek']
            : 'NULL'
    )
    . "<br>";


echo "Worst Average: "
    . (
        $worstOpportunityRun !== null
            ? number_format(
                $worstOpportunityRun['average_score'],
                2
            )
            : 'NULL'
    )
    . "<br>";


testPass(
    'Best opportunity run is calculated',
    $bestOpportunityRun !== null
);


testPass(
    'Worst opportunity run is calculated',
    $worstOpportunityRun !== null
);


testPass(
    'Best opportunity run contains five fixtures',
    isset(
        $bestOpportunityRun['fixtures']
    )
    &&
    count(
        $bestOpportunityRun['fixtures']
    ) === 5
);


testPass(
    'Worst opportunity run contains five fixtures',
    isset(
        $worstOpportunityRun['fixtures']
    )
    &&
    count(
        $worstOpportunityRun['fixtures']
    ) === 5
);


testPass(
    'Best opportunity run starts at GW1',
    (
        $bestOpportunityRun[
            'start_gameweek'
        ]
        ?? null
    ) === 1
);


testPass(
    'Best opportunity run ends at GW5',
    (
        $bestOpportunityRun[
            'end_gameweek'
        ]
        ?? null
    ) === 5
);


testPass(
    'Worst opportunity run starts at GW6',
    (
        $worstOpportunityRun[
            'start_gameweek'
        ]
        ?? null
    ) === 6
);


testPass(
    'Worst opportunity run ends at GW10',
    (
        $worstOpportunityRun[
            'end_gameweek'
        ]
        ?? null
    ) === 10
);


testPass(
    'Best opportunity average is 80',
    (
        $bestOpportunityRun[
            'average_score'
        ]
        ?? null
    ) === 80.00
);


testPass(
    'Worst opportunity average is 30',
    (
        $worstOpportunityRun[
            'average_score'
        ]
        ?? null
    ) === 30.00
);


testPass(
    'Best opportunity run scores higher than worst run',
    (
        $bestOpportunityRun[
            'average_score'
        ]
        ?? 0
    )
    >
    (
        $worstOpportunityRun[
            'average_score'
        ]
        ?? 0
    )
);


/*
 * ============================================================
 * SCENARIO Q - PLAYER OPPORTUNITY TREND
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario Q: Player Opportunity Trend<br>";
echo "============================================<br>";


$decliningOpportunityTrend =
    $fixtureIntelligence
        ->calculateOpportunityTrend(
            $opportunityFixtures
        );


echo "Declining Trend: "
    . $decliningOpportunityTrend
    . "<br>";


testPass(
    'Declining opportunity run is identified',
    $decliningOpportunityTrend
    === 'Declining'
);


/*
 * Improving fixture opportunity.
 */

$improvingOpportunityFixtures =
    array_reverse(
        $opportunityFixtures
    );


/*
 * array_reverse() also reverses the gameweek numbers,
 * but trend calculation depends on fixture order rather
 * than the actual gameweek values.
 */

$improvingOpportunityTrend =
    $fixtureIntelligence
        ->calculateOpportunityTrend(
            $improvingOpportunityFixtures
        );


echo "Improving Trend: "
    . $improvingOpportunityTrend
    . "<br>";


testPass(
    'Improving opportunity run is identified',
    $improvingOpportunityTrend
    === 'Improving'
);


/*
 * Stable fixture opportunity.
 */

$stableOpportunityFixtures = [

    [
        'gameweek' => 1,
        'opportunity_score' => 60.00
    ],

    [
        'gameweek' => 2,
        'opportunity_score' => 62.00
    ],

    [
        'gameweek' => 3,
        'opportunity_score' => 58.00
    ],

    [
        'gameweek' => 4,
        'opportunity_score' => 61.00
    ],

    [
        'gameweek' => 5,
        'opportunity_score' => 59.00
    ],

    [
        'gameweek' => 6,
        'opportunity_score' => 60.00
    ]
];


$stableOpportunityTrend =
    $fixtureIntelligence
        ->calculateOpportunityTrend(
            $stableOpportunityFixtures
        );


echo "Stable Trend: "
    . $stableOpportunityTrend
    . "<br>";


testPass(
    'Stable opportunity run is identified',
    $stableOpportunityTrend
    === 'Stable'
);


/*
 * Insufficient data.
 */

$insufficientOpportunityTrend =
    $fixtureIntelligence
        ->calculateOpportunityTrend(
            [
                [
                    'gameweek' => 1,
                    'opportunity_score' => 70.00
                ],
                [
                    'gameweek' => 2,
                    'opportunity_score' => 60.00
                ],
                [
                    'gameweek' => 3,
                    'opportunity_score' => 50.00
                ]
            ]
        );


testPass(
    'Opportunity trend requires at least four fixtures',
    $insufficientOpportunityTrend
    === 'Insufficient Data'
);


/*
 * ============================================================
 * SCENARIO R - OPPORTUNITY / FIXTURE SCORE SEPARATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario R: Opportunity Score Separation<br>";
echo "============================================<br>";


/*
 * Every fixture_score below is deliberately identical.
 *
 * Only opportunity_score changes.
 *
 * This proves the player-facing methods are using
 * opportunity_score rather than fixture_score.
 */

$separationFixtures = [

    [
        'gameweek' => 1,
        'fixture_score' => 50.00,
        'opportunity_score' => 100.00
    ],

    [
        'gameweek' => 2,
        'fixture_score' => 50.00,
        'opportunity_score' => 90.00
    ],

    [
        'gameweek' => 3,
        'fixture_score' => 50.00,
        'opportunity_score' => 80.00
    ],

    [
        'gameweek' => 4,
        'fixture_score' => 50.00,
        'opportunity_score' => 70.00
    ],

    [
        'gameweek' => 5,
        'fixture_score' => 50.00,
        'opportunity_score' => 60.00
    ],

    [
        'gameweek' => 6,
        'fixture_score' => 50.00,
        'opportunity_score' => 10.00
    ],

    [
        'gameweek' => 7,
        'fixture_score' => 50.00,
        'opportunity_score' => 20.00
    ],

    [
        'gameweek' => 8,
        'fixture_score' => 50.00,
        'opportunity_score' => 30.00
    ],

    [
        'gameweek' => 9,
        'fixture_score' => 50.00,
        'opportunity_score' => 40.00
    ],

    [
        'gameweek' => 10,
        'fixture_score' => 50.00,
        'opportunity_score' => 50.00
    ]
];


$separationBest =
    $fixtureIntelligence
        ->findBestOpportunityRun(
            $separationFixtures,
            5
        );


$separationWorst =
    $fixtureIntelligence
        ->findWorstOpportunityRun(
            $separationFixtures,
            5
        );


$separationTrend =
    $fixtureIntelligence
        ->calculateOpportunityTrend(
            $separationFixtures
        );


testPass(
    'Opportunity best run does not use fixture_score',
    (
        $separationBest[
            'average_score'
        ]
        ?? null
    ) === 80.00
);


testPass(
    'Opportunity worst run does not use fixture_score',
    (
        $separationWorst[
            'average_score'
        ]
        ?? null
    ) === 30.00
);


testPass(
    'Opportunity trend does not use fixture_score',
    $separationTrend
    === 'Declining'
);


/*
 * ============================================================
 * SCENARIO S - INVALID OPPORTUNITY RUNS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario S: Opportunity Run Edge Cases<br>";
echo "============================================<br>";


testPass(
    'Best opportunity run returns null with too few fixtures',
    $fixtureIntelligence
        ->findBestOpportunityRun(
            array_slice(
                $opportunityFixtures,
                0,
                4
            ),
            5
        )
    === null
);


testPass(
    'Worst opportunity run returns null with too few fixtures',
    $fixtureIntelligence
        ->findWorstOpportunityRun(
            array_slice(
                $opportunityFixtures,
                0,
                4
            ),
            5
        )
    === null
);


testPass(
    'Best opportunity run rejects zero run length',
    $fixtureIntelligence
        ->findBestOpportunityRun(
            $opportunityFixtures,
            0
        )
    === null
);


testPass(
    'Worst opportunity run rejects zero run length',
    $fixtureIntelligence
        ->findWorstOpportunityRun(
            $opportunityFixtures,
            0
        )
    === null
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Front-End Friendly Fixture Intelligence<br>";
echo "============================================<br>";


$displayFixture =
    $fixtureRun[0];


echo "Gameweek: "
    . $displayFixture['gameweek']
    . "<br>";

echo "Fixture: "
    . $displayFixture['home_team']
    . " vs "
    . $displayFixture['away_team']
    . "<br>";

echo "Venue: "
    . $displayFixture['venue']
    . "<br>";

echo "Fixture Score: "
    . number_format(
        $displayFixture['fixture_score'],
        2
    )
    . " / 100<br>";

echo "Difficulty: "
    . $displayFixture['difficulty']
    . " / 5<br>";

echo "Difficulty Label: "
    . $displayFixture['difficulty_label']
    . "<br>";

echo "Matchup: "
    . number_format(
        $displayFixture['matchup'],
        2
    )
    . "<br>";

echo "Next 5 Average: "
    . number_format(
        $rollingAverages['next_5'],
        2
    )
    . " / 100<br>";

echo "Next 6 Average: "
    . number_format(
        $rollingAverages['next_6'],
        2
    )
    . " / 100<br>";

echo "Next 8 Average: "
    . number_format(
        $rollingAverages['next_8'],
        2
    )
    . " / 100<br>";

echo "Next 10 Average: "
    . number_format(
        $rollingAverages['next_10'],
        2
    )
    . " / 100<br>";

echo "Fixture Trend: "
    . $fixtureIntelligence->calculateTrend(
        $fixtureRun
    )
    . "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Fixture Intelligence Test Summary<br>";
echo "============================================<br>";


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}