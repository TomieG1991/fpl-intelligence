<?php

require_once __DIR__ . '/../classes/autoload.php';
require_once __DIR__ . '/../config/config.php';


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

    global $passed, $failed;

    if ($condition) {

        echo "PASS: {$description}<br>";
        $passed++;

    } else {

        echo "FAIL: {$description}<br>";
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
 * DATABASE
 * ============================================================
 */

section(
    'Database Connection'
);


try {

    $database =
        new Database();

    $db =
        $database->getConnection();

    $repository =
        new PlayerRepository(
            $db
        );


    testPass(
        'Database connection is available',
        $db instanceof PDO
    );


    testPass(
        'PlayerRepository can be created',
        $repository instanceof PlayerRepository
    );

} catch (Throwable $exception) {

    testPass(
        'Database connection is available',
        false
    );

    echo "ERROR: "
        . $exception->getMessage()
        . "<br>";


    echo "<br>";
    echo "============================================<br>";
    echo "Player Repository Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: {$passed}<br>";
    echo "Failed: {$failed}<br>";

    echo "<br>RESULT: TESTS FAILED ❌<br>";

    exit;
}


/*
 * ============================================================
 * SCENARIO A
 * Get All Players
 * ============================================================
 */

section(
    'Scenario A: Get All Players'
);


$players =
    $repository->getAll();


echo "Players Found: "
    . count($players)
    . "<br>";


testPass(
    'getAll returns an array',
    is_array($players)
);


testPass(
    'Player database contains players',
    count($players) > 0
);


/*
 * ============================================================
 * SCENARIO B
 * Player Data Structure
 * ============================================================
 */

section(
    'Scenario B: Player Data Structure'
);


$firstPlayer =
    $players[0]
    ?? null;


testPass(
    'A player record is available',
    is_array($firstPlayer)
);


$requiredFields = [

    'id',
    'fpl_player_id',
    'team_id',
    'web_name',
    'position',
    'price',
    'minutes',
    'goals',
    'assists',
    'clean_sheets',
    'bonus',
    'bps'
];


foreach (
    $requiredFields
    as $field
) {

    testPass(
        "Player contains {$field}",
        is_array($firstPlayer)
        &&
        array_key_exists(
            $field,
            $firstPlayer
        )
    );
}


/*
 * ============================================================
 * SCENARIO C
 * Local Player ID Lookup
 * ============================================================
 */

section(
    'Scenario C: Local Player ID Lookup'
);


$playerById =
    $repository->getById(
        (int) $firstPlayer['id']
    );


testPass(
    'getById returns a player',
    is_array($playerById)
);


testPass(
    'getById returns the requested player',
    isset($playerById['id'])
    &&
    (int) $playerById['id']
        ===
    (int) $firstPlayer['id']
);


/*
 * ============================================================
 * SCENARIO D
 * FPL Player ID Lookup
 * ============================================================
 */

section(
    'Scenario D: FPL Player ID Lookup'
);


$playerByFplId =
    $repository->getByFplPlayerId(
        (int) $firstPlayer['fpl_player_id']
    );


testPass(
    'getByFplPlayerId returns a player',
    is_array($playerByFplId)
);


testPass(
    'getByFplPlayerId returns the requested FPL player',
    isset($playerByFplId['fpl_player_id'])
    &&
    (int) $playerByFplId['fpl_player_id']
        ===
    (int) $firstPlayer['fpl_player_id']
);


/*
 * ============================================================
 * SCENARIO E
 * Missing Player Lookups
 * ============================================================
 */

section(
    'Scenario E: Missing Player Lookups'
);


$missingPlayer =
    $repository->getById(
        PHP_INT_MAX
    );


$missingFplPlayer =
    $repository->getByFplPlayerId(
        PHP_INT_MAX
    );


testPass(
    'Unknown local player ID returns null',
    $missingPlayer === null
);


testPass(
    'Unknown FPL player ID returns null',
    $missingFplPlayer === null
);


/*
 * ============================================================
 * SCENARIO F
 * Most Expensive Players
 * ============================================================
 */

section(
    'Scenario F: Most Expensive Players'
);


$mostExpensive =
    $repository->getMostExpensive(
        10
    );


echo "Players Returned: "
    . count($mostExpensive)
    . "<br>";


testPass(
    'getMostExpensive returns an array',
    is_array($mostExpensive)
);


testPass(
    'getMostExpensive returns no more than requested limit',
    count($mostExpensive) <= 10
);


testPass(
    'getMostExpensive returns players',
    count($mostExpensive) > 0
);


/*
 * ============================================================
 * SCENARIO G
 * Price Ordering
 * ============================================================
 */

section(
    'Scenario G: Price Ordering'
);


$priceOrderCorrect =
    true;

$previousPrice =
    null;


foreach (
    $mostExpensive
    as $player
) {

    $price =
        (float) (
            $player['price']
            ?? 0
        );


    if (
        $previousPrice !== null
        &&
        $price > $previousPrice
    ) {

        $priceOrderCorrect =
            false;

        break;
    }


    $previousPrice =
        $price;
}


testPass(
    'Most expensive players are ordered by price descending',
    $priceOrderCorrect
);


/*
 * ============================================================
 * SCENARIO H
 * Complete Player Records
 * ============================================================
 */

section(
    'Scenario H: Complete Player Records'
);


$expensivePlayer =
    $mostExpensive[0]
    ?? null;


testPass(
    'Most expensive result contains local player ID',
    is_array($expensivePlayer)
    &&
    array_key_exists(
        'id',
        $expensivePlayer
    )
);


testPass(
    'Most expensive result contains FPL player ID',
    is_array($expensivePlayer)
    &&
    array_key_exists(
        'fpl_player_id',
        $expensivePlayer
    )
);


testPass(
    'Most expensive result contains team ID',
    is_array($expensivePlayer)
    &&
    array_key_exists(
        'team_id',
        $expensivePlayer
    )
);


testPass(
    'Most expensive result contains position',
    is_array($expensivePlayer)
    &&
    array_key_exists(
        'position',
        $expensivePlayer
    )
);


testPass(
    'Most expensive result contains performance data',
    is_array($expensivePlayer)
    &&
    array_key_exists(
        'minutes',
        $expensivePlayer
    )
    &&
    array_key_exists(
        'goals',
        $expensivePlayer
    )
    &&
    array_key_exists(
        'assists',
        $expensivePlayer
    )
);


/*
 * ============================================================
 * SCENARIO I
 * Limit Handling
 * ============================================================
 */

section(
    'Scenario I: Limit Handling'
);


$topThree =
    $repository->getMostExpensive(
        3
    );


$topZero =
    $repository->getMostExpensive(
        0
    );


$topNegative =
    $repository->getMostExpensive(
        -5
    );


testPass(
    'Limit of 3 returns exactly three players',
    count($topThree) === 3
);


testPass(
    'Limit of 0 returns empty array',
    $topZero === []
);


testPass(
    'Negative limit returns empty array',
    $topNegative === []
);


/*
 * ============================================================
 * SCENARIO J
 * Intelligence Model Compatibility
 * ============================================================
 */

section(
    'Scenario J: Player Intelligence Compatibility'
);


$performance =
    new PlayerPerformance();


$performanceModel =
    $performance->buildModel(
        $firstPlayer
    );


testPass(
    'Database player can be passed into PlayerPerformance',
    is_array($performanceModel)
);


testPass(
    'PlayerPerformance preserves database player ID',
    isset($performanceModel['player_id'])
    &&
    $performanceModel['player_id']
        ===
    (int) $firstPlayer['id']
);


testPass(
    'PlayerPerformance preserves FPL player ID',
    isset($performanceModel['fpl_player_id'])
    &&
    $performanceModel['fpl_player_id']
        ===
    (int) $firstPlayer['fpl_player_id']
);


testPass(
    'PlayerPerformance preserves team ID',
    isset($performanceModel['team_id'])
    &&
    $performanceModel['team_id']
        ===
    (int) $firstPlayer['team_id']
);

/*
 * ============================================================
 * SCENARIO K
 * Imported Player Position Contract
 * ============================================================
 */

section(
    'Scenario K: Imported Player Position Contract'
);


$validPositions = [

    'GK',
    'DEF',
    'MID',
    'FWD'
];


$allPositionsValid =
    true;


$positionCounts = [

    'GK' => 0,
    'DEF' => 0,
    'MID' => 0,
    'FWD' => 0
];


foreach (
    $players
    as $player
) {

    $position =
        $player['position']
        ?? null;


    if (
        !in_array(
            $position,
            $validPositions,
            true
        )
    ) {

        $allPositionsValid =
            false;

        break;
    }


    $positionCounts[$position]++;
}


echo "GK: "
    . $positionCounts['GK']
    . "<br>";

echo "DEF: "
    . $positionCounts['DEF']
    . "<br>";

echo "MID: "
    . $positionCounts['MID']
    . "<br>";

echo "FWD: "
    . $positionCounts['FWD']
    . "<br>";


testPass(
    'Every imported player uses GK, DEF, MID or FWD',
    $allPositionsValid
);


testPass(
    'Database contains goalkeepers',
    $positionCounts['GK'] > 0
);


testPass(
    'Database contains defenders',
    $positionCounts['DEF'] > 0
);


testPass(
    'Database contains midfielders',
    $positionCounts['MID'] > 0
);


testPass(
    'Database contains forwards',
    $positionCounts['FWD'] > 0
);


/*
 * ============================================================
 * SCENARIO L
 * Complete Player Intelligence Engine Compatibility
 * ============================================================
 */

section(
    'Scenario L: Complete Player Intelligence Engine Compatibility'
);


/*
 * Use a real database player with a price.
 *
 * Fixture rating is deliberately null here because this test
 * is checking the repository -> player intelligence pipeline,
 * not fixture generation.
 */

$enginePlayer =
    null;


foreach (
    $players
    as $player
) {

    if (
        isset($player['price'])
        &&
        $player['price'] !== null
        &&
        (float) $player['price'] > 0
        &&
        isset($player['position'])
        &&
        in_array(
            $player['position'],
            $validPositions,
            true
        )
    ) {

        $enginePlayer =
            $player;

        break;
    }
}


testPass(
    'A usable database player is available for engine testing',
    is_array($enginePlayer)
);


if ($enginePlayer !== null) {

    $engine =
        new PlayerIntelligenceEngine(

            new PlayerPerformance(),

            new PlayerStrengthModel(),

            new PlayerValue(),

            new PlayerAvailability(),

            new PlayerIntelligenceScore()
        );


    $engineModel =
        $engine->analysePlayer(
            $enginePlayer,
            null
        );


    testPass(
        'Database player can be passed into PlayerIntelligenceEngine',
        is_array($engineModel)
    );


    testPass(
        'Engine returns player section',
        isset(
            $engineModel['player']
        )
        &&
        is_array(
            $engineModel['player']
        )
    );


    testPass(
        'Engine returns performance section',
        isset(
            $engineModel['performance']
        )
        &&
        is_array(
            $engineModel['performance']
        )
    );


    testPass(
        'Engine returns strength section',
        isset(
            $engineModel['strength']
        )
        &&
        is_array(
            $engineModel['strength']
        )
    );


    testPass(
        'Engine returns value section',
        isset(
            $engineModel['value']
        )
        &&
        is_array(
            $engineModel['value']
        )
    );


    testPass(
        'Engine returns availability section',
        isset(
            $engineModel['availability']
        )
        &&
        is_array(
            $engineModel['availability']
        )
    );


    testPass(
        'Engine returns intelligence section',
        isset(
            $engineModel['intelligence']
        )
        &&
        is_array(
            $engineModel['intelligence']
        )
    );


    testPass(
        'Engine returns decision-friendly summary',
        isset(
            $engineModel['summary']
        )
        &&
        is_array(
            $engineModel['summary']
        )
    );


    testPass(
        'Engine preserves database player ID',
        $engineModel['player']['player_id']
            ===
        (int) $enginePlayer['id']
    );


    testPass(
        'Engine preserves FPL player ID',
        $engineModel['player']['fpl_player_id']
            ===
        (int) $enginePlayer['fpl_player_id']
    );


    testPass(
        'Engine preserves team ID',
        $engineModel['player']['team_id']
            ===
        (int) $enginePlayer['team_id']
    );


    testPass(
        'Engine preserves imported position',
        $engineModel['player']['position']
            ===
        $enginePlayer['position']
    );


    testPass(
        'Engine position is intelligence-compatible',
        in_array(
            $engineModel['player']['position'],
            $validPositions,
            true
        )
    );


    testPass(
        'Summary preserves imported position',
        $engineModel['summary']['position']
            ===
        $enginePlayer['position']
    );


    testPass(
        'Summary fixture rating is null when unavailable',
        $engineModel['summary']['fixture_rating']
            ===
        null
    );


    testPass(
        'Engine produces an intelligence score from database data',
        $engineModel['summary']['intelligence_score']
            !==
        null
    );


    testPass(
        'Database intelligence score remains within 0-100',
        $engineModel['summary']['intelligence_score'] >= 0
        &&
        $engineModel['summary']['intelligence_score'] <= 100
    );


    echo "Engine Test Player: "
        . $engineModel['player']['name']
        . "<br>";


    echo "Position: "
        . $engineModel['player']['position']
        . "<br>";


    echo "Strength Rating: "
        . (
            $engineModel['summary']['strength_rating'] === null
                ? 'NULL'
                : number_format(
                    $engineModel['summary']['strength_rating'],
                    2
                )
        )
        . "<br>";


    echo "Value Rating: "
        . (
            $engineModel['summary']['value_rating'] === null
                ? 'NULL'
                : number_format(
                    $engineModel['summary']['value_rating'],
                    2
                )
        )
        . "<br>";


    echo "Intelligence Score: "
        . number_format(
            $engineModel['summary']['intelligence_score'],
            2
        )
        . "<br>";
}


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

section(
    'Front-End Friendly Repository Output'
);


echo "Players in Database: "
    . count($players)
    . "<br>";


if ($expensivePlayer !== null) {

    echo "Most Expensive Player: "
        . $expensivePlayer['web_name']
        . "<br>";

    echo "Position: "
        . $expensivePlayer['position']
        . "<br>";

    echo "Price: £"
        . number_format(
            (float) $expensivePlayer['price'],
            1
        )
        . "m<br>";
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

section(
    'Player Repository Test Summary'
);


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}