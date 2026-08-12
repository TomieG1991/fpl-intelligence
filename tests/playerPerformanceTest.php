<?php

require_once __DIR__ . '/../classes/PlayerPerformance.php';

$playerPerformance = new PlayerPerformance();

$passed = 0;
$failed = 0;


function testPass(
    string $message,
    bool $condition
): void {

    global $passed;
    global $failed;

    if ($condition) {

        echo "PASS: {$message}\n";
        $passed++;

    } else {

        echo "FAIL: {$message}\n";
        $failed++;
    }
}


function testSection(
    string $title
): void {

    echo "\n============================================\n";
    echo "{$title}\n";
    echo "============================================\n";
}


/*
 * ============================================================
 * TEST PLAYER
 * ============================================================
 */

$player = [

    'id' => 123,

    'fpl_player_id' => 456,

    'team_id' => 1,

    'position' => 'MID',

    'web_name' => 'Test Player',

    'price' => 8.5,

    'minutes' => 900,

    'goals' => 8,

    'assists' => 4,

    'clean_sheets' => 5,

    'bonus' => 10,

    'bps' => 250,

    'ict_index' => 125.5,

    'expected_goals' => 7.20,

    'expected_assists' => 3.60,

    'expected_goal_involvements' => 10.80,

    'chance_of_playing' => 100,

    'status' => 'a',

    'news' => ''
];


$performance =
    $playerPerformance->analyse(
        $player
    );


/*
 * ============================================================
 * SCENARIO A
 * Basic Player Analysis
 * ============================================================
 */

testSection(
    'Scenario A: Basic Player Analysis'
);


testPass(
    'Player ID is correct',
    $performance['player_id'] === 123
);


testPass(
    'FPL player ID is correct',
    $performance['fpl_player_id'] === 456
);


testPass(
    'Team ID is correct',
    $performance['team_id'] === 1
);


testPass(
    'Position is correct',
    $performance['position'] === 'MID'
);


testPass(
    'Player name is correct',
    $performance['name'] === 'Test Player'
);


testPass(
    'Price is correct',
    $performance['price'] === 8.5
);


testPass(
    'Minutes are correct',
    $performance['minutes'] === 900
);


testPass(
    'Goals are correct',
    $performance['goals'] === 8
);


testPass(
    'Assists are correct',
    $performance['assists'] === 4
);


testPass(
    'Clean sheets are correct',
    $performance['clean_sheets'] === 5
);


testPass(
    'Bonus is correct',
    $performance['bonus'] === 10
);


testPass(
    'BPS is correct',
    $performance['bps'] === 250
);


/*
 * ============================================================
 * SCENARIO B
 * Optional Data
 * ============================================================
 */

testSection(
    'Scenario B: Optional Player Data'
);


testPass(
    'ICT index is correct',
    $performance['ict_index'] === 125.5
);


testPass(
    'Expected goals are correct',
    $performance['expected_goals'] === 7.2
);


testPass(
    'Expected assists are correct',
    $performance['expected_assists'] === 3.6
);


testPass(
    'Expected goal involvements are correct',
    $performance['expected_goal_involvements'] === 10.8
);


testPass(
    'Chance of playing is correct',
    $performance['chance_of_playing'] === 100
);


testPass(
    'Status is correct',
    $performance['status'] === 'a'
);


/*
 * ============================================================
 * SCENARIO C
 * Goals Per 90
 * ============================================================
 */

testSection(
    'Scenario C: Goals Per 90'
);


$goalsPer90 =
    $playerPerformance->calculateGoalsPer90(
        $performance
    );


echo "Goals Per 90: {$goalsPer90}\n";


testPass(
    'Goals per 90 calculated correctly',
    $goalsPer90 === 0.8
);


/*
 * ============================================================
 * SCENARIO D
 * Assists Per 90
 * ============================================================
 */

testSection(
    'Scenario D: Assists Per 90'
);


$assistsPer90 =
    $playerPerformance->calculateAssistsPer90(
        $performance
    );


echo "Assists Per 90: {$assistsPer90}\n";


testPass(
    'Assists per 90 calculated correctly',
    $assistsPer90 === 0.4
);


/*
 * ============================================================
 * SCENARIO E
 * Expected Goals Per 90
 * ============================================================
 */

testSection(
    'Scenario E: Expected Goals Per 90'
);


$xgPer90 =
    $playerPerformance->calculateExpectedGoalsPer90(
        $performance
    );


echo "xG Per 90: {$xgPer90}\n";


testPass(
    'Expected goals per 90 calculated correctly',
    $xgPer90 === 0.72
);


/*
 * ============================================================
 * SCENARIO F
 * Expected Assists Per 90
 * ============================================================
 */

testSection(
    'Scenario F: Expected Assists Per 90'
);


$xaPer90 =
    $playerPerformance->calculateExpectedAssistsPer90(
        $performance
    );


echo "xA Per 90: {$xaPer90}\n";


testPass(
    'Expected assists per 90 calculated correctly',
    $xaPer90 === 0.36
);


/*
 * ============================================================
 * SCENARIO G
 * Expected Goal Involvements Per 90
 * ============================================================
 */

testSection(
    'Scenario G: Expected Goal Involvements Per 90'
);


$xgiPer90 =
    $playerPerformance->calculateExpectedGoalInvolvementsPer90(
        $performance
    );


echo "xGI Per 90: {$xgiPer90}\n";


testPass(
    'Expected goal involvements per 90 calculated correctly',
    $xgiPer90 === 1.08
);


/*
 * ============================================================
 * SCENARIO H
 * Clean Sheets Per 90
 * ============================================================
 */

testSection(
    'Scenario H: Clean Sheets Per 90'
);


$cleanSheetsPer90 =
    $playerPerformance->calculateCleanSheetsPer90(
        $performance
    );


echo "Clean Sheets Per 90: {$cleanSheetsPer90}\n";


testPass(
    'Clean sheets per 90 calculated correctly',
    $cleanSheetsPer90 === 0.5
);


/*
 * ============================================================
 * SCENARIO I
 * Zero Minutes
 * ============================================================
 */

testSection(
    'Scenario I: Zero Minutes'
);


$zeroMinutePlayer = [

    'id' => 999,

    'fpl_player_id' => 999,

    'team_id' => 1,

    'position' => 'MID',

    'web_name' => 'Unused Player',

    'price' => 5.0,

    'minutes' => 0,

    'goals' => 0,

    'assists' => 0,

    'clean_sheets' => 0,

    'bonus' => 0,

    'bps' => 0,

    'ict_index' => null,

    'expected_goals' => null,

    'expected_assists' => null,

    'expected_goal_involvements' => null,

    'chance_of_playing' => 0,

    'status' => 'i',

    'news' => 'Unavailable'
];


$zeroPerformance =
    $playerPerformance->analyse(
        $zeroMinutePlayer
    );


testPass(
    'Zero-minute player is analysed',
    $zeroPerformance['minutes'] === 0
);


testPass(
    'Zero-minute goals per 90 returns null',
    $playerPerformance->calculateGoalsPer90(
        $zeroPerformance
    ) === null
);


testPass(
    'Zero-minute assists per 90 returns null',
    $playerPerformance->calculateAssistsPer90(
        $zeroPerformance
    ) === null
);


testPass(
    'Zero-minute xG per 90 returns null',
    $playerPerformance->calculateExpectedGoalsPer90(
        $zeroPerformance
    ) === null
);


testPass(
    'Zero-minute xA per 90 returns null',
    $playerPerformance->calculateExpectedAssistsPer90(
        $zeroPerformance
    ) === null
);


testPass(
    'Zero-minute xGI per 90 returns null',
    $playerPerformance->calculateExpectedGoalInvolvementsPer90(
        $zeroPerformance
    ) === null
);


testPass(
    'Zero-minute clean sheets per 90 returns null',
    $playerPerformance->calculateCleanSheetsPer90(
        $zeroPerformance
    ) === null
);


/*
 * ============================================================
 * SCENARIO J
 * Missing Optional Fields
 * ============================================================
 */

testSection(
    'Scenario J: Missing Optional Fields'
);


$minimalPlayer = [

    'id' => 1000,

    'team_id' => 2,

    'web_name' => 'Minimal Player',

    'minutes' => 90,

    'goals' => 1,

    'assists' => 0,

    'clean_sheets' => 1,

    'bonus' => 1,

    'bps' => 20
];


$minimalPerformance =
    $playerPerformance->analyse(
        $minimalPlayer
    );


testPass(
    'Missing FPL player ID defaults to zero',
    $minimalPerformance['fpl_player_id'] === 0
);


testPass(
    'Missing position defaults to null',
    $minimalPerformance['position'] === null
);


testPass(
    'Missing price defaults to null',
    $minimalPerformance['price'] === null
);


testPass(
    'Missing ICT index defaults to null',
    $minimalPerformance['ict_index'] === null
);


testPass(
    'Missing expected goals defaults to null',
    $minimalPerformance['expected_goals'] === null
);


testPass(
    'Missing expected assists defaults to null',
    $minimalPerformance['expected_assists'] === null
);


testPass(
    'Missing expected goal involvements defaults to null',
    $minimalPerformance['expected_goal_involvements'] === null
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "\n============================================\n";
echo "Player Performance Test Summary\n";
echo "============================================\n";

echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";


if ($failed === 0) {

    echo "\nRESULT: ALL TESTS PASSED ✅\n";

} else {

    echo "\nRESULT: TESTS FAILED ❌\n";
}