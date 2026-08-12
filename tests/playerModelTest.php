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


function section(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";
    echo "{$title}<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * MODELS
 * ============================================================
 */

$playerPerformance =
    new PlayerPerformance();


$playerStrengthModel =
    new PlayerStrengthModel();


$playerValue =
    new PlayerValue();


$playerAvailability =
    new PlayerAvailability();


$playerModel =
    new PlayerModel(
        $playerPerformance,
        $playerStrengthModel,
        $playerValue,
        $playerAvailability
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

    'position' => 'FWD',

    'first_name' => 'Test',

    'second_name' => 'Forward',

    'web_name' => 'Test Forward',

    'price' => 6.0,

    'minutes' => 900,

    'goals' => 10,

    'assists' => 5,

    'clean_sheets' => 3,

    'bonus' => 10,

    'bps' => 80,

    'ict_index' => 100.0,

    'expected_goals' => 8.0,

    'expected_assists' => 4.0,

    'expected_goal_involvements' => 12.0,

    'chance_of_playing' => 100,

    'status' => 'a',

    'news' => ''
];


/*
 * ============================================================
 * BUILD MODEL
 * ============================================================
 */

$model =
    $playerModel->buildModel(
        $player
    );


/*
 * ============================================================
 * SCENARIO A
 * Basic Player Model Structure
 * ============================================================
 */

section(
    'Scenario A: Player Model Structure'
);


testPass(
    'Player model returns an array',
    is_array($model)
);


testPass(
    'Player ID is preserved',
    $model['player_id'] === 1
);


testPass(
    'FPL player ID is preserved',
    $model['fpl_player_id'] === 1001
);


testPass(
    'Team ID is preserved',
    $model['team_id'] === 1
);


testPass(
    'Player name is preserved',
    $model['name'] === 'Test Forward'
);


testPass(
    'Player position is preserved',
    $model['position'] === 'FWD'
);


/*
 * ============================================================
 * SCENARIO B
 * Performance Model
 * ============================================================
 */

section(
    'Scenario B: Performance Model'
);


testPass(
    'Performance model exists',
    isset($model['performance'])
    &&
    is_array($model['performance'])
);


testPass(
    'Performance minutes are correct',
    $model['performance']['minutes'] === 900
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
    'Performance ratings exist',
    isset($model['performance']['goals_rating'])
    &&
    isset($model['performance']['assists_rating'])
);


/*
 * ============================================================
 * SCENARIO C
 * Strength Model
 * ============================================================
 */

section(
    'Scenario C: Strength Model'
);


testPass(
    'Strength model exists',
    isset($model['strength'])
    &&
    is_array($model['strength'])
);


testPass(
    'Strength rating exists',
    isset($model['strength']['strength_rating'])
);


testPass(
    'Strength rating is within 0-100',
    $model['strength']['strength_rating'] >= 0
    &&
    $model['strength']['strength_rating'] <= 100
);


testPass(
    'Strength player ID is preserved',
    $model['strength']['player_id'] === 1
);


testPass(
    'Strength position is preserved',
    $model['strength']['position'] === 'FWD'
);


/*
 * ============================================================
 * SCENARIO D
 * Value Model
 * ============================================================
 */

section(
    'Scenario D: Value Model'
);


testPass(
    'Value model exists',
    isset($model['value'])
    &&
    is_array($model['value'])
);


testPass(
    'Player price is preserved',
    $model['value']['price'] === 6.0
);


testPass(
    'Strength per million exists',
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
    $model['value']['value_label'] !== 'N/A'
);


/*
 * ============================================================
 * SCENARIO E
 * Availability Model
 * ============================================================
 */

section(
    'Scenario E: Availability Model'
);


testPass(
    'Availability model exists',
    isset($model['availability'])
    &&
    is_array($model['availability'])
);


testPass(
    'Availability rating exists',
    $model['availability']['availability_rating'] !== null
);


testPass(
    'Availability rating is 100',
    $model['availability']['availability_rating'] === 100.00
);


testPass(
    'Reliability rating exists',
    $model['availability']['reliability_rating'] !== null
);


testPass(
    'Availability label exists',
    $model['availability']['availability_label'] === 'Available'
);


/*
 * ============================================================
 * SCENARIO F
 * Complete Model Integration
 * ============================================================
 */

section(
    'Scenario F: Complete Player Intelligence'
);


testPass(
    'Performance, strength, value and availability all exist',
    isset($model['performance'])
    &&
    isset($model['strength'])
    &&
    isset($model['value'])
    &&
    isset($model['availability'])
);


testPass(
    'Strength rating is connected to value model',
    $model['value']['strength_rating']
    ===
    $model['strength']['strength_rating']
);


testPass(
    'Player identity remains consistent',
    $model['performance']['player_id']
    ===
    $model['strength']['player_id']
    &&
    $model['strength']['player_id']
    ===
    $model['value']['player_id']
);


testPass(
    'Player position remains consistent',
    $model['performance']['position']
    ===
    $model['strength']['position']
    &&
    $model['strength']['position']
    ===
    $model['value']['position']
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

section(
    'Front-End Friendly Player Model'
);


echo "Player: "
    . $model['name']
    . "<br>\n";


echo "Position: "
    . $model['position']
    . "<br>\n";


echo "Price: £"
    . number_format(
        $model['value']['price'],
        1
    )
    . "m<br>\n";


echo "Strength Rating: "
    . number_format(
        $model['strength']['strength_rating'],
        2
    )
    . " / 100<br>\n";


echo "Strength per £1m: "
    . number_format(
        $model['value']['strength_per_million'],
        2
    )
    . "<br>\n";


echo "Value Rating: "
    . number_format(
        $model['value']['value_rating'],
        2
    )
    . " / 100<br>\n";


echo "Value Label: "
    . $model['value']['value_label']
    . "<br>\n";


echo "Availability: "
    . number_format(
        $model['availability']['availability_rating'],
        2
    )
    . " / 100<br>\n";


echo "Reliability: "
    . number_format(
        $model['availability']['reliability_rating'],
        2
    )
    . " / 100<br>\n";


echo "Status: "
    . $model['availability']['availability_label']
    . "<br>\n";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

section(
    'Player Model Test Summary'
);


echo "Passed: {$passed}<br>\n";
echo "Failed: {$failed}<br>\n";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>\n";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>\n";
}