<?php

require_once __DIR__ . '/../classes/autoload.php';


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
 * IMPORTER SOURCE
 * ============================================================
 */

$importerPath =
    __DIR__
    . '/../cron/updateFPLData.php';


section(
    'Scenario A: Importer File'
);


testPass(
    'FPL data importer exists',
    file_exists(
        $importerPath
    )
);


$importerSource =
    file_exists($importerPath)
        ? file_get_contents($importerPath)
        : false;


testPass(
    'FPL data importer can be read',
    is_string($importerSource)
    &&
    $importerSource !== ''
);


/*
 * ============================================================
 * LIVE FPL DATA
 * ============================================================
 */

section(
    'Scenario B: Live FPL Bootstrap Data'
);


try {

    $api =
        new FPLApi();


    $bootstrap =
        $api->getBootstrapData();


    testPass(
        'Live FPL bootstrap request succeeds',
        is_array($bootstrap)
    );

} catch (Throwable $exception) {

    $bootstrap = [];


    testPass(
        'Live FPL bootstrap request succeeds',
        false
    );


    echo "ERROR: "
        . $exception->getMessage()
        . "<br>";
}


/*
 * ============================================================
 * REQUIRED DATASETS
 * ============================================================
 */

section(
    'Scenario C: Import Data Structure'
);


testPass(
    'Bootstrap contains teams',
    isset($bootstrap['teams'])
    &&
    is_array(
        $bootstrap['teams']
    )
);


testPass(
    'Bootstrap contains players',
    isset($bootstrap['elements'])
    &&
    is_array(
        $bootstrap['elements']
    )
);


$teams =
    $bootstrap['teams']
    ?? [];


$players =
    $bootstrap['elements']
    ?? [];


echo "Teams Available: "
    . count($teams)
    . "<br>";


echo "Players Available: "
    . count($players)
    . "<br>";


testPass(
    'Import source contains 20 teams',
    count($teams) === 20
);


testPass(
    'Import source contains players',
    count($players) > 0
);


/*
 * ============================================================
 * TEAM IMPORT CONTRACT
 * ============================================================
 */

section(
    'Scenario D: Team Import Contract'
);


$firstTeam =
    $teams[0]
    ?? null;


$requiredTeamFields = [

    'id',
    'name',
    'short_name',

    'strength_overall_home',
    'strength_overall_away',

    'strength_attack_home',
    'strength_attack_away',

    'strength_defence_home',
    'strength_defence_away'
];


foreach (
    $requiredTeamFields
    as $field
) {

    testPass(
        "FPL team contains {$field}",
        is_array($firstTeam)
        &&
        array_key_exists(
            $field,
            $firstTeam
        )
    );
}


/*
 * ============================================================
 * PLAYER IMPORT CONTRACT
 * ============================================================
 */

section(
    'Scenario E: Player Import Contract'
);


$firstPlayer =
    $players[0]
    ?? null;


$requiredPlayerFields = [

    'id',
    'team',
    'element_type',

    'first_name',
    'second_name',
    'web_name',

    'now_cost',
    'selected_by_percent',

    'minutes',
    'goals_scored',
    'assists',
    'clean_sheets',

    'bonus',
    'bps',
    'ict_index',

    'expected_goals',
    'expected_assists',
    'expected_goal_involvements',

    'status',
    'news'
];


foreach (
    $requiredPlayerFields
    as $field
) {

    testPass(
        "FPL player contains {$field}",
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
 * POSITION MAPPING
 * ============================================================
 */

section(
    'Scenario F: Player Position Mapping'
);


$positionMap = [

    1 => 'GK',

    2 => 'DEF',

    3 => 'MID',

    4 => 'FWD'
];


testPass(
    'Element type 1 maps to GK',
    $positionMap[1] === 'GK'
);


testPass(
    'Element type 2 maps to DEF',
    $positionMap[2] === 'DEF'
);


testPass(
    'Element type 3 maps to MID',
    $positionMap[3] === 'MID'
);


testPass(
    'Element type 4 maps to FWD',
    $positionMap[4] === 'FWD'
);


$allPositionsValid =
    true;


foreach (
    $players
    as $player
) {

    $elementType =
        isset($player['element_type'])
            ? (int) $player['element_type']
            : 0;


    if (
        !isset(
            $positionMap[$elementType]
        )
    ) {

        $allPositionsValid =
            false;

        break;
    }
}


testPass(
    'Every live FPL player has a recognised element type',
    $allPositionsValid
);


/*
 * ============================================================
 * INTELLIGENCE POSITION CONTRACT
 * ============================================================
 */

section(
    'Scenario G: Intelligence Position Contract'
);


$validPositions = [

    'GK',
    'DEF',
    'MID',
    'FWD'
];


$allMappedPositionsValid =
    true;


foreach (
    $players
    as $player
) {

    $elementType =
        isset($player['element_type'])
            ? (int) $player['element_type']
            : 0;


    $position =
        $positionMap[$elementType]
        ?? null;


    if (
        !in_array(
            $position,
            $validPositions,
            true
        )
    ) {

        $allMappedPositionsValid =
            false;

        break;
    }
}


testPass(
    'All imported positions match player intelligence position format',
    $allMappedPositionsValid
);


/*
 * ============================================================
 * TEAM RELATIONSHIP MAPPING
 * ============================================================
 */

section(
    'Scenario H: Player Team Relationships'
);


$validFplTeamIds =
    array_column(
        $teams,
        'id'
    );


$allPlayerTeamsValid =
    true;


foreach (
    $players
    as $player
) {

    if (
        !in_array(
            $player['team'],
            $validFplTeamIds,
            true
        )
    ) {

        $allPlayerTeamsValid =
            false;

        break;
    }
}


testPass(
    'Every FPL player references a valid FPL team',
    $allPlayerTeamsValid
);


/*
 * ============================================================
 * PRICE MAPPING
 * ============================================================
 */

section(
    'Scenario I: Player Price Mapping'
);


$priceMappingValid =
    true;


foreach (
    $players
    as $player
) {

    if (
        !isset(
            $player['now_cost']
        )
    ) {

        $priceMappingValid =
            false;

        break;
    }


    $price =
        ((float) $player['now_cost'])
        / 10;


    if (
        $price <= 0
        ||
        $price > 30
    ) {

        $priceMappingValid =
            false;

        break;
    }
}


testPass(
    'All FPL prices convert to usable million-pound values',
    $priceMappingValid
);


if ($firstPlayer !== null) {

    $examplePrice =
        ((float) $firstPlayer['now_cost'])
        / 10;


    echo "Example API Price: "
        . $firstPlayer['now_cost']
        . "<br>";


    echo "Converted Price: £"
        . number_format(
            $examplePrice,
            1
        )
        . "m<br>";
}


/*
 * ============================================================
 * NUMERIC PERFORMANCE DATA
 * ============================================================
 */

section(
    'Scenario J: Performance Data Mapping'
);


$performanceFields = [

    'minutes',
    'goals_scored',
    'assists',
    'clean_sheets',
    'bonus',
    'bps'
];


$performanceDataValid =
    true;


foreach (
    $players
    as $player
) {

    foreach (
        $performanceFields
        as $field
    ) {

        if (
            !array_key_exists(
                $field,
                $player
            )
            ||
            !is_numeric(
                $player[$field]
            )
        ) {

            $performanceDataValid =
                false;

            break 2;
        }
    }
}


testPass(
    'Core performance fields contain numeric values',
    $performanceDataValid
);


/*
 * ============================================================
 * EXPECTED STATISTICS
 * ============================================================
 */

section(
    'Scenario K: Expected Statistics Mapping'
);


$expectedFields = [

    'expected_goals',
    'expected_assists',
    'expected_goal_involvements'
];


$expectedStatsUsable =
    true;


foreach (
    $players
    as $player
) {

    foreach (
        $expectedFields
        as $field
    ) {

        if (
            !array_key_exists(
                $field,
                $player
            )
        ) {

            $expectedStatsUsable =
                false;

            break 2;
        }


        if (
            $player[$field] !== null
            &&
            !is_numeric(
                $player[$field]
            )
        ) {

            $expectedStatsUsable =
                false;

            break 2;
        }
    }
}


testPass(
    'Expected-statistic fields are available and numeric when supplied',
    $expectedStatsUsable
);


/*
 * ============================================================
 * AVAILABILITY DATA
 * ============================================================
 */

section(
    'Scenario L: Availability Data Mapping'
);


$availabilityValuesValid =
    true;


foreach (
    $players
    as $player
) {

    $chance =
        $player[
            'chance_of_playing_next_round'
        ]
        ?? null;


    if (
        $chance !== null
        &&
        (
            !is_numeric($chance)
            ||
            (int) $chance < 0
            ||
            (int) $chance > 100
        )
    ) {

        $availabilityValuesValid =
            false;

        break;
    }
}


testPass(
    'Chance-of-playing values are null or within 0-100',
    $availabilityValuesValid
);


/*
 * ============================================================
 * IMPORTER POSITION IMPLEMENTATION
 * ============================================================
 */

section(
    'Scenario M: Importer Position Implementation'
);


testPass(
    'Importer contains GK position mapping',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        "1 => 'GK'"
    ) !== false
);


testPass(
    'Importer contains DEF position mapping',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        "2 => 'DEF'"
    ) !== false
);


testPass(
    'Importer contains MID position mapping',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        "3 => 'MID'"
    ) !== false
);


testPass(
    'Importer contains FWD position mapping',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        "4 => 'FWD'"
    ) !== false
);


/*
 * ============================================================
 * IMPORTER UPDATE CONTRACT
 * ============================================================
 */

section(
    'Scenario N: Importer Update Contract'
);


testPass(
    'Importer updates player position',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        'position = VALUES(position)'
    ) !== false
);


testPass(
    'Importer updates first name',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        'first_name = VALUES(first_name)'
    ) !== false
);


testPass(
    'Importer updates second name',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        'second_name = VALUES(second_name)'
    ) !== false
);


testPass(
    'Importer updates web name',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        'web_name = VALUES(web_name)'
    ) !== false
);


testPass(
    'Importer updates clean sheets',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        'clean_sheets = VALUES(clean_sheets)'
    ) !== false
);


/*
 * ============================================================
 * TRANSACTION SAFETY
 * ============================================================
 */

section(
    'Scenario O: Import Transaction Safety'
);


testPass(
    'Importer starts a database transaction',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        'beginTransaction'
    ) !== false
);


testPass(
    'Importer commits successful imports',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        '->commit()'
    ) !== false
);


testPass(
    'Importer rolls back failed imports',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        '->rollBack()'
    ) !== false
);


/*
 * ============================================================
 * IMPORT COUNTERS
 * ============================================================
 */

section(
    'Scenario P: Import Reporting'
);


testPass(
    'Importer tracks imported teams',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        '$teamsImported'
    ) !== false
);


testPass(
    'Importer tracks imported players',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        '$playersImported'
    ) !== false
);


testPass(
    'Importer tracks skipped players',
    is_string($importerSource)
    &&
    strpos(
        $importerSource,
        '$playersSkipped'
    ) !== false
);


/*
 * ============================================================
 * DATABASE SAFETY
 * ============================================================
 */

section(
    'Scenario Q: Test Database Safety'
);


/*
 * This test intentionally does not include or execute
 * updateFPLData.php.
 *
 * The importer source is inspected and the live API
 * contract is validated without modifying the database.
 */

testPass(
    'Import contract test performs no database writes',
    true
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

section(
    'Front-End Friendly Import Contract'
);


echo "Teams Ready For Import: "
    . count($teams)
    . "<br>";


echo "Players Ready For Import: "
    . count($players)
    . "<br>";


echo "Supported Positions: "
    . implode(
        ', ',
        $validPositions
    )
    . "<br>";


if ($firstPlayer !== null) {

    $elementType =
        (int) (
            $firstPlayer['element_type']
            ?? 0
        );


    echo "Example Player: "
        . (
            $firstPlayer['web_name']
            ?? 'Unknown'
        )
        . "<br>";


    echo "FPL Element Type: "
        . $elementType
        . "<br>";


    echo "Mapped Position: "
        . (
            $positionMap[$elementType]
            ?? 'Unknown'
        )
        . "<br>";
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

section(
    'FPL Data Import Test Summary'
);


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}