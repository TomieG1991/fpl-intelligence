<?php

require_once __DIR__ . '/../classes/autoload.php';


$availability = new PlayerAvailability();

$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function passTest(
    string $message,
    bool $condition
): void {

    global $passed, $failed;

    if ($condition) {

        echo "PASS: {$message}<br>";
        $passed++;

    } else {

        echo "FAIL: {$message}<br>";
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
 * SCENARIO A
 * Fully Available Player
 * ============================================================
 */

section(
    'Scenario A: Fully Available Player'
);

$rating =
    $availability->calculateAvailabilityRating(
        100,
        'a'
    );

echo "Availability Rating: "
    . number_format($rating, 2)
    . "<br>";

passTest(
    '100% chance of playing produces 100 availability',
    $rating === 100.00
);

passTest(
    'Available status produces 100 availability',
    $rating === 100.00
);


/*
 * ============================================================
 * SCENARIO B
 * Doubtful Player
 * ============================================================
 */

section(
    'Scenario B: Doubtful Player'
);

$rating =
    $availability->calculateAvailabilityRating(
        50,
        'd'
    );

echo "Availability Rating: "
    . number_format($rating, 2)
    . "<br>";

passTest(
    '50% chance of playing produces 50 availability',
    $rating === 50.00
);


/*
 * ============================================================
 * SCENARIO C
 * Injured / Unavailable Player
 * ============================================================
 */

section(
    'Scenario C: Unavailable Player'
);

$rating =
    $availability->calculateAvailabilityRating(
        0,
        'i'
    );

echo "Availability Rating: "
    . number_format($rating, 2)
    . "<br>";

passTest(
    '0% chance of playing produces 0 availability',
    $rating === 0.00
);


/*
 * ============================================================
 * SCENARIO D
 * Status-Based Fallback
 * ============================================================
 */

section(
    'Scenario D: Status-Based Availability'
);

$available =
    $availability->calculateAvailabilityRating(
        null,
        'a'
    );

$doubtful =
    $availability->calculateAvailabilityRating(
        null,
        'd'
    );

$injured =
    $availability->calculateAvailabilityRating(
        null,
        'i'
    );

$suspended =
    $availability->calculateAvailabilityRating(
        null,
        's'
    );

echo "Available: "
    . number_format($available, 2)
    . "<br>";

echo "Doubtful: "
    . number_format($doubtful, 2)
    . "<br>";

echo "Injured: "
    . number_format($injured, 2)
    . "<br>";

echo "Suspended: "
    . number_format($suspended, 2)
    . "<br>";

passTest(
    'Available status produces 100',
    $available === 100.00
);

passTest(
    'Doubtful status produces 50',
    $doubtful === 50.00
);

passTest(
    'Injured status produces 25',
    $injured === 25.00
);

passTest(
    'Suspended status produces 0',
    $suspended === 0.00
);


/*
 * ============================================================
 * SCENARIO E
 * Availability Bounds
 * ============================================================
 */

section(
    'Scenario E: Availability Bounds'
);

$negative =
    $availability->calculateAvailabilityRating(
        -20,
        'a'
    );

$over100 =
    $availability->calculateAvailabilityRating(
        150,
        'a'
    );

echo "Negative Input: "
    . number_format($negative, 2)
    . "<br>";

echo "Over 100 Input: "
    . number_format($over100, 2)
    . "<br>";

passTest(
    'Availability cannot fall below 0',
    $negative === 0.00
);

passTest(
    'Availability cannot exceed 100',
    $over100 === 100.00
);


/*
 * ============================================================
 * SCENARIO F
 * Missing Data
 * ============================================================
 */

section(
    'Scenario F: Missing Data'
);

$missing =
    $availability->calculateAvailabilityRating(
        null,
        null
    );

echo "Missing Availability: "
    . (
        $missing === null
            ? 'NULL'
            : number_format($missing, 2)
    )
    . "<br>";

passTest(
    'Missing availability data returns null',
    $missing === null
);


/*
 * ============================================================
 * SCENARIO G
 * Reliability Rating
 * ============================================================
 */

section(
    'Scenario G: Reliability Rating'
);

$fullReliability =
    $availability->calculateReliabilityRating(
        100.00,
        1000
    );

$partialReliability =
    $availability->calculateReliabilityRating(
        100.00,
        500
    );

$noMinutesReliability =
    $availability->calculateReliabilityRating(
        100.00,
        0
    );

echo "1,000 Minutes: "
    . number_format($fullReliability, 2)
    . "<br>";

echo "500 Minutes: "
    . number_format($partialReliability, 2)
    . "<br>";

echo "0 Minutes: "
    . number_format($noMinutesReliability, 2)
    . "<br>";

passTest(
    '1,000 minutes produces full reliability',
    $fullReliability === 100.00
);

passTest(
    '500 minutes produces lower reliability than 1,000 minutes',
    $partialReliability < $fullReliability
);

passTest(
    'Zero minutes preserves availability rating',
    $noMinutesReliability === 100.00
);


/*
 * ============================================================
 * SCENARIO H
 * Reliability Bounds
 * ============================================================
 */

section(
    'Scenario H: Reliability Bounds'
);

$zeroReliability =
    $availability->calculateReliabilityRating(
        0,
        1000
    );

$maximumReliability =
    $availability->calculateReliabilityRating(
        100,
        1000
    );

echo "Zero Availability: "
    . number_format($zeroReliability, 2)
    . "<br>";

echo "Maximum Availability: "
    . number_format($maximumReliability, 2)
    . "<br>";

passTest(
    'Reliability cannot fall below 0',
    $zeroReliability >= 0
);

passTest(
    'Reliability cannot exceed 100',
    $maximumReliability <= 100
);


/*
 * ============================================================
 * SCENARIO I
 * Availability Labels
 * ============================================================
 */

section(
    'Scenario I: Availability Labels'
);

$availableLabel =
    $availability->getAvailabilityLabel(
        95
    );

$likelyLabel =
    $availability->getAvailabilityLabel(
        75
    );

$doubtfulLabel =
    $availability->getAvailabilityLabel(
        40
    );

$unavailableLabel =
    $availability->getAvailabilityLabel(
        10
    );

$unknownLabel =
    $availability->getAvailabilityLabel(
        null
    );

echo "95 Rating: {$availableLabel}<br>";
echo "75 Rating: {$likelyLabel}<br>";
echo "40 Rating: {$doubtfulLabel}<br>";
echo "10 Rating: {$unavailableLabel}<br>";
echo "NULL Rating: {$unknownLabel}<br>";

passTest(
    'High availability produces Available label',
    $availableLabel === 'Available'
);

passTest(
    'Moderate availability produces Likely Available label',
    $likelyLabel === 'Likely Available'
);

passTest(
    'Low availability produces Doubtful label',
    $doubtfulLabel === 'Doubtful'
);

passTest(
    'Very low availability produces Unavailable label',
    $unavailableLabel === 'Unavailable'
);

passTest(
    'Missing availability produces Unknown label',
    $unknownLabel === 'Unknown'
);


/*
 * ============================================================
 * SCENARIO J
 * Complete Player Availability Model
 * ============================================================
 */

section(
    'Scenario J: Complete Player Availability Model'
);

$player = [

    'id' => 123,

    'fpl_player_id' => 456,

    'web_name' => 'Test Forward',

    'position' => 'FWD',

    'minutes' => 750,

    'chance_of_playing' => 100,

    'status' => 'a'
];


$model =
    $availability->buildAvailabilityModel(
        $player
    );

echo "Player: "
    . $model['name']
    . "<br>";

echo "Position: "
    . $model['position']
    . "<br>";

echo "Minutes: "
    . $model['minutes']
    . "<br>";

echo "Chance of Playing: "
    . $model['chance_of_playing']
    . "%<br>";

echo "Availability Rating: "
    . number_format(
        $model['availability_rating'],
        2
    )
    . "<br>";

echo "Reliability Rating: "
    . number_format(
        $model['reliability_rating'],
        2
    )
    . "<br>";

echo "Availability Label: "
    . $model['availability_label']
    . "<br>";

passTest(
    'Complete model preserves player ID',
    $model['player_id'] === 123
);

passTest(
    'Complete model preserves player name',
    $model['name'] === 'Test Forward'
);

passTest(
    'Complete model preserves position',
    $model['position'] === 'FWD'
);

passTest(
    'Complete model calculates availability',
    $model['availability_rating'] === 100.00
);

passTest(
    'Complete model calculates reliability',
    $model['reliability_rating'] !== null
);

passTest(
    'Complete model produces availability label',
    $model['availability_label'] === 'Available'
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

section(
    'Front-End Friendly Player Availability'
);

echo "Player: "
    . $model['name']
    . "<br>";

echo "Position: "
    . $model['position']
    . "<br>";

echo "Minutes: "
    . $model['minutes']
    . "<br>";

echo "Availability: "
    . number_format(
        $model['availability_rating'],
        2
    )
    . " / 100<br>";

echo "Reliability: "
    . number_format(
        $model['reliability_rating'],
        2
    )
    . " / 100<br>";

echo "Status: "
    . $model['availability_label']
    . "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Availability Model Test Summary<br>";
echo "============================================<br>";

echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";

if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}