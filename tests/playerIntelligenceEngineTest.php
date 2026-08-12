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

        echo "PASS: {$description}<br>\n";

        $passed++;

    } else {

        echo "FAIL: {$description}<br>\n";

        $failed++;
    }
}


/*
 * ============================================================
 * MODELS
 * ============================================================
 */

$engine =
    new PlayerIntelligenceEngine(

        new PlayerPerformance(),

        new PlayerStrengthModel(),

        new PlayerValue(),

        new PlayerAvailability(),

        new PlayerIntelligenceScore()
    );


/*
 * ============================================================
 * TEST PLAYER
 * ============================================================
 */

$player = [

    'id' => 1,

    'fpl_player_id' => 1001,

    'team_id' => 1,

    'web_name' => 'Test Forward',

    'position' => 'FWD',

    'price' => 6.0,

    'minutes' => 900,

    'goals' => 10,

    'assists' => 5,

    'clean_sheets' => 5,

    'bonus' => 10,

    'bps' => 800,

    'ict_index' => 100.0,

    'expected_goals' => 8.0,

    'expected_assists' => 4.0,

    'expected_goal_involvements' => 12.0,

    'chance_of_playing' => 100,

    'status' => 'a',

    'news' => '',

    /*
     * Normalised performance ratings.
     */
    'goals_rating' => 100.00,

    'assists_rating' => 100.00,

    'expected_goals_rating' => 80.00,

    'expected_assists_rating' => 80.00,

    'clean_sheets_rating' => 100.00,

    'bps_rating' => 80.00
];


$fixtureRating = 90.00;


/*
 * ============================================================
 * BUILD PLAYER PROFILE
 * ============================================================
 */

$model =
    $engine->analysePlayer(
        $player,
        $fixtureRating
    );


/*
 * ============================================================
 * SCENARIO A
 * Basic Player Identity
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Player Identity<br>";
echo "============================================<br>";


testPass(
    'Engine returns an array',
    is_array($model)
);


testPass(
    'Player section exists',
    isset($model['player'])
);


testPass(
    'Player ID is preserved',
    $model['player']['player_id'] === 1
);


testPass(
    'FPL player ID is preserved',
    $model['player']['fpl_player_id'] === 1001
);


testPass(
    'Team ID is preserved',
    $model['player']['team_id'] === 1
);


testPass(
    'Player name is preserved',
    $model['player']['name'] === 'Test Forward'
);


testPass(
    'Player position is preserved',
    $model['player']['position'] === 'FWD'
);


/*
 * ============================================================
 * SCENARIO B
 * Performance Model
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Performance Model<br>";
echo "============================================<br>";


testPass(
    'Performance model exists',
    isset($model['performance'])
);


testPass(
    'Performance minutes are preserved',
    $model['performance']['minutes'] === 900
);


testPass(
    'Goals are preserved',
    $model['performance']['goals'] === 10
);


testPass(
    'Assists are preserved',
    $model['performance']['assists'] === 5
);


testPass(
    'Goals per 90 is calculated',
    $model['performance']['goals_per_90'] === 1.00
);


testPass(
    'Assists per 90 is calculated',
    $model['performance']['assists_per_90'] === 0.50
);


testPass(
    'xG per 90 is calculated',
    $model['performance']['expected_goals_per_90'] === 0.80
);


testPass(
    'xA per 90 is calculated',
    $model['performance']['expected_assists_per_90'] === 0.40
);


testPass(
    'xGI per 90 is calculated',
    $model['performance']['expected_goal_involvements_per_90'] === 1.20
);


testPass(
    'Clean sheets per 90 is calculated',
    $model['performance']['clean_sheets_per_90'] === 0.50
);


/*
 * ============================================================
 * SCENARIO C
 * Performance Ratings
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Performance Ratings<br>";
echo "============================================<br>";


testPass(
    'Goals rating is preserved',
    $model['performance']['goals_rating'] === 100.00
);


testPass(
    'Assists rating is preserved',
    $model['performance']['assists_rating'] === 100.00
);


testPass(
    'Expected goals rating is preserved',
    $model['performance']['expected_goals_rating'] === 80.00
);


testPass(
    'Expected assists rating is preserved',
    $model['performance']['expected_assists_rating'] === 80.00
);


testPass(
    'Clean sheet rating is preserved',
    $model['performance']['clean_sheets_rating'] === 100.00
);


testPass(
    'BPS rating is preserved',
    $model['performance']['bps_rating'] === 80.00
);


/*
 * ============================================================
 * SCENARIO D
 * Strength Model
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Player Strength Model<br>";
echo "============================================<br>";


testPass(
    'Strength model exists',
    isset($model['strength'])
);


testPass(
    'Strength player ID is preserved',
    $model['strength']['player_id'] === 1
);


testPass(
    'Strength player name is preserved',
    $model['strength']['name'] === 'Test Forward'
);


testPass(
    'Strength position is preserved',
    $model['strength']['position'] === 'FWD'
);


testPass(
    'Strength rating exists',
    $model['strength']['strength_rating'] !== null
);


testPass(
    'Strength rating is within 0-100',
    $model['strength']['strength_rating'] >= 0
    &&
    $model['strength']['strength_rating'] <= 100
);


/*
 * ============================================================
 * SCENARIO E
 * Value Model
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Player Value Model<br>";
echo "============================================<br>";


testPass(
    'Value model exists',
    isset($model['value'])
);


testPass(
    'Player price is preserved',
    $model['value']['price'] === 6.0
);


testPass(
    'Value model receives strength rating',
    $model['value']['strength_rating']
        === $model['strength']['strength_rating']
);


testPass(
    'Strength per million is calculated',
    $model['value']['strength_per_million'] !== null
);


testPass(
    'Value rating exists',
    $model['value']['value_rating'] !== null
);


testPass(
    'Value rating is within 0-100',
    $model['value']['value_rating'] >= 0
    &&
    $model['value']['value_rating'] <= 100
);


testPass(
    'Value label exists',
    !empty(
        $model['value']['value_label']
    )
);


/*
 * ============================================================
 * SCENARIO F
 * Availability Model
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Player Availability Model<br>";
echo "============================================<br>";


testPass(
    'Availability model exists',
    isset($model['availability'])
);


testPass(
    'Availability rating exists',
    $model['availability']['availability_rating']
        !== null
);


testPass(
    'Availability rating is 100',
    $model['availability']['availability_rating']
        === 100.00
);


testPass(
    'Reliability rating exists',
    $model['availability']['reliability_rating']
        !== null
);


testPass(
    'Availability label exists',
    !empty(
        $model['availability']['availability_label']
    )
);


/*
 * ============================================================
 * SCENARIO G
 * Intelligence Score
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Player Intelligence Score<br>";
echo "============================================<br>";


testPass(
    'Intelligence model exists',
    isset($model['intelligence'])
);


testPass(
    'Intelligence strength rating is connected',
    $model['intelligence']['strength_rating']
        === $model['strength']['strength_rating']
);


testPass(
    'Intelligence value rating is connected',
    $model['intelligence']['value_rating']
        === $model['value']['value_rating']
);


testPass(
    'Intelligence availability rating is connected',
    $model['intelligence']['availability_rating']
        === $model['availability']['availability_rating']
);


testPass(
    'Fixture rating is connected',
    $model['intelligence']['fixture_rating']
        === 90.00
);


testPass(
    'Intelligence score exists',
    $model['intelligence']['intelligence_score']
        !== null
);


testPass(
    'Intelligence score is within 0-100',
    $model['intelligence']['intelligence_score'] >= 0
    &&
    $model['intelligence']['intelligence_score'] <= 100
);


testPass(
    'Intelligence label exists',
    !empty(
        $model['intelligence']['intelligence_label']
    )
);


/*
 * ============================================================
 * SCENARIO H
 * Identity Consistency
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Cross-Model Identity Consistency<br>";
echo "============================================<br>";


$playerId =
    $model['player']['player_id'];


testPass(
    'Performance player ID matches',
    $model['performance']['player_id']
        === $playerId
);


testPass(
    'Strength player ID matches',
    $model['strength']['player_id']
        === $playerId
);


testPass(
    'Value player ID matches',
    $model['value']['player_id']
        === $playerId
);


testPass(
    'Availability player ID matches',
    $model['availability']['player_id']
        === $playerId
);


testPass(
    'Intelligence player ID matches',
    $model['intelligence']['player_id']
        === $playerId
);


testPass(
    'Performance name matches',
    $model['performance']['name']
        === 'Test Forward'
);


testPass(
    'Strength name matches',
    $model['strength']['name']
        === 'Test Forward'
);


testPass(
    'Value name matches',
    $model['value']['name']
        === 'Test Forward'
);


testPass(
    'Availability name matches',
    $model['availability']['name']
        === 'Test Forward'
);


testPass(
    'Intelligence name matches',
    $model['intelligence']['name']
        === 'Test Forward'
);


/*
 * ============================================================
 * SCENARIO I
 * Missing Fixture Rating
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Missing Fixture Rating<br>";
echo "============================================<br>";


$modelWithoutFixture =
    $engine->analysePlayer(
        $player,
        null
    );


testPass(
    'Engine works without fixture rating',
    is_array($modelWithoutFixture)
);


testPass(
    'Fixture rating is null when unavailable',
    $modelWithoutFixture['intelligence']['fixture_rating']
        === null
);


testPass(
    'Intelligence score still exists without fixture rating',
    $modelWithoutFixture['intelligence']['intelligence_score']
        !== null
);


testPass(
    'Intelligence score remains within bounds without fixture rating',
    $modelWithoutFixture['intelligence']['intelligence_score'] >= 0
    &&
    $modelWithoutFixture['intelligence']['intelligence_score'] <= 100
);


/*
 * ============================================================
 * SCENARIO J
 * Complete Profile Structure
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario J: Complete Player Profile<br>";
echo "============================================<br>";


testPass(
    'Complete player section exists',
    isset($model['player'])
);


testPass(
    'Complete performance section exists',
    isset($model['performance'])
);


testPass(
    'Complete strength section exists',
    isset($model['strength'])
);


testPass(
    'Complete value section exists',
    isset($model['value'])
);


testPass(
    'Complete availability section exists',
    isset($model['availability'])
);


testPass(
    'Complete intelligence section exists',
    isset($model['intelligence'])
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Front-End Friendly Player Intelligence Engine<br>";
echo "============================================<br>";


echo "Player: "
    . $model['player']['name']
    . "<br>";


echo "Position: "
    . $model['player']['position']
    . "<br>";


echo "Price: £"
    . number_format(
        $model['value']['price'],
        1
    )
    . "m<br>";


echo "Goals/90: "
    . number_format(
        $model['performance']['goals_per_90'],
        2
    )
    . "<br>";


echo "Assists/90: "
    . number_format(
        $model['performance']['assists_per_90'],
        2
    )
    . "<br>";


echo "xG/90: "
    . number_format(
        $model['performance']['expected_goals_per_90'],
        2
    )
    . "<br>";


echo "xA/90: "
    . number_format(
        $model['performance']['expected_assists_per_90'],
        2
    )
    . "<br>";


echo "Strength Rating: "
    . number_format(
        $model['strength']['strength_rating'],
        2
    )
    . " / 100<br>";


echo "Strength per £1m: "
    . number_format(
        $model['value']['strength_per_million'],
        2
    )
    . "<br>";


echo "Value Rating: "
    . number_format(
        $model['value']['value_rating'],
        2
    )
    . " / 100<br>";


echo "Value Label: "
    . $model['value']['value_label']
    . "<br>";


echo "Availability: "
    . number_format(
        $model['availability']['availability_rating'],
        2
    )
    . " / 100<br>";


echo "Reliability: "
    . number_format(
        $model['availability']['reliability_rating'],
        2
    )
    . " / 100<br>";


echo "Fixture Rating: "
    . number_format(
        $model['intelligence']['fixture_rating'],
        2
    )
    . " / 100<br>";


echo "Intelligence Score: "
    . number_format(
        $model['intelligence']['intelligence_score'],
        2
    )
    . " / 100<br>";


echo "Intelligence Label: "
    . $model['intelligence']['intelligence_label']
    . "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Player Intelligence Engine Test Summary<br>";
echo "============================================<br>";


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}