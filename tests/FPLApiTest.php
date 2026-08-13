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
 * MODEL
 * ============================================================
 */

section(
    'FPL API Initialisation'
);


try {

    $api =
        new FPLApi();


    testPass(
        'FPLApi can be created',
        $api instanceof FPLApi
    );

} catch (Throwable $exception) {

    testPass(
        'FPLApi can be created',
        false
    );


    echo "ERROR: "
        . $exception->getMessage()
        . "<br>";


    echo "<br>";
    echo "============================================<br>";
    echo "FPL API Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: {$passed}<br>";
    echo "Failed: {$failed}<br>";

    echo "<br>RESULT: TESTS FAILED ❌<br>";

    exit;
}


/*
 * ============================================================
 * SCENARIO A
 * Bootstrap API Connectivity
 * ============================================================
 */

section(
    'Scenario A: Bootstrap API Connectivity'
);


try {

    $bootstrap =
        $api->getBootstrapData();


    testPass(
        'Bootstrap API returns an array',
        is_array($bootstrap)
    );

} catch (Throwable $exception) {

    $bootstrap = [];


    testPass(
        'Bootstrap API request succeeds',
        false
    );


    echo "ERROR: "
        . $exception->getMessage()
        . "<br>";
}


/*
 * ============================================================
 * SCENARIO B
 * Bootstrap Structure
 * ============================================================
 */

section(
    'Scenario B: Bootstrap Data Structure'
);


$bootstrapFields = [

    'elements',
    'teams',
    'element_types',
    'events'
];


foreach (
    $bootstrapFields
    as $field
) {

    testPass(
        "Bootstrap contains {$field}",
        array_key_exists(
            $field,
            $bootstrap
        )
        &&
        is_array(
            $bootstrap[$field]
        )
    );
}


/*
 * ============================================================
 * SCENARIO C
 * Premier League Teams
 * ============================================================
 */

section(
    'Scenario C: Premier League Teams'
);


$teams =
    $bootstrap['teams']
    ?? [];


echo "Teams Returned: "
    . count($teams)
    . "<br>";


testPass(
    'Bootstrap returns 20 teams',
    count($teams) === 20
);


$firstTeam =
    $teams[0]
    ?? null;


testPass(
    'A team record is available',
    is_array($firstTeam)
);


$teamFields = [

    'id',
    'name',
    'short_name',
    'strength',
    'strength_overall_home',
    'strength_overall_away',
    'strength_attack_home',
    'strength_attack_away',
    'strength_defence_home',
    'strength_defence_away'
];


foreach (
    $teamFields
    as $field
) {

    testPass(
        "Team contains {$field}",
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
 * SCENARIO D
 * Player Data
 * ============================================================
 */

section(
    'Scenario D: Player Data'
);


$players =
    $bootstrap['elements']
    ?? [];


echo "Players Returned: "
    . count($players)
    . "<br>";


testPass(
    'Bootstrap returns player data',
    count($players) > 0
);


$firstPlayer =
    $players[0]
    ?? null;


testPass(
    'A player record is available',
    is_array($firstPlayer)
);


$playerFields = [

    'id',
    'team',
    'element_type',
    'web_name',
    'now_cost',
    'minutes',
    'goals_scored',
    'assists',
    'clean_sheets',
    'bonus',
    'bps',
    'status'
];


foreach (
    $playerFields
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
 * SCENARIO E
 * Position Data
 * ============================================================
 */

section(
    'Scenario E: Position Data'
);


$positions =
    $bootstrap['element_types']
    ?? [];


echo "Positions Returned: "
    . count($positions)
    . "<br>";


testPass(
    'Bootstrap returns four player positions',
    count($positions) === 4
);


$positionIds =
    array_column(
        $positions,
        'id'
    );


sort(
    $positionIds
);


testPass(
    'Position IDs are 1 through 4',
    $positionIds === [
        1,
        2,
        3,
        4
    ]
);


/*
 * ============================================================
 * SCENARIO F
 * Gameweek Data
 * ============================================================
 */

section(
    'Scenario F: Gameweek Data'
);


$events =
    $bootstrap['events']
    ?? [];


echo "Gameweeks Returned: "
    . count($events)
    . "<br>";


testPass(
    'Bootstrap returns 38 gameweeks',
    count($events) === 38
);


$firstEvent =
    $events[0]
    ?? null;


testPass(
    'A gameweek record is available',
    is_array($firstEvent)
);


testPass(
    'Gameweek contains ID',
    is_array($firstEvent)
    &&
    array_key_exists(
        'id',
        $firstEvent
    )
);


testPass(
    'Gameweek contains name',
    is_array($firstEvent)
    &&
    array_key_exists(
        'name',
        $firstEvent
    )
);


/*
 * ============================================================
 * SCENARIO G
 * Fixtures API Connectivity
 * ============================================================
 */

section(
    'Scenario G: Fixtures API Connectivity'
);


try {

    $fixtures =
        $api->getFixtures();


    testPass(
        'Fixtures API returns an array',
        is_array($fixtures)
    );

} catch (Throwable $exception) {

    $fixtures = [];


    testPass(
        'Fixtures API request succeeds',
        false
    );


    echo "ERROR: "
        . $exception->getMessage()
        . "<br>";
}


/*
 * ============================================================
 * SCENARIO H
 * Fixture Count
 * ============================================================
 */

section(
    'Scenario H: Premier League Fixture Count'
);


echo "Fixtures Returned: "
    . count($fixtures)
    . "<br>";


testPass(
    'Fixtures API returns 380 fixtures',
    count($fixtures) === 380
);


/*
 * ============================================================
 * SCENARIO I
 * Fixture Structure
 * ============================================================
 */

section(
    'Scenario I: Fixture Data Structure'
);


$firstFixture =
    $fixtures[0]
    ?? null;


testPass(
    'A fixture record is available',
    is_array($firstFixture)
);


$fixtureFields = [

    'id',
    'event',
    'team_h',
    'team_a',
    'kickoff_time',
    'finished',
    'finished_provisional',
    'team_h_score',
    'team_a_score',
    'team_h_difficulty',
    'team_a_difficulty'
];


foreach (
    $fixtureFields
    as $field
) {

    testPass(
        "Fixture contains {$field}",
        is_array($firstFixture)
        &&
        array_key_exists(
            $field,
            $firstFixture
        )
    );
}


/*
 * ============================================================
 * SCENARIO J
 * Unique Fixture IDs
 * ============================================================
 */

section(
    'Scenario J: Unique Fixture IDs'
);


$fixtureIds =
    array_column(
        $fixtures,
        'id'
    );


$uniqueFixtureIds =
    array_unique(
        $fixtureIds
    );


echo "Fixture IDs: "
    . count($fixtureIds)
    . "<br>";

echo "Unique Fixture IDs: "
    . count($uniqueFixtureIds)
    . "<br>";


testPass(
    'All fixture IDs are unique',
    count($fixtureIds)
        ===
    count($uniqueFixtureIds)
);


/*
 * ============================================================
 * SCENARIO K
 * Fixture Team Relationships
 * ============================================================
 */

section(
    'Scenario K: Fixture Team Relationships'
);


$validTeamIds =
    array_column(
        $teams,
        'id'
    );


$teamRelationshipsValid =
    true;


foreach (
    $fixtures
    as $fixture
) {

    if (
        !in_array(
            $fixture['team_h'],
            $validTeamIds,
            true
        )
        ||
        !in_array(
            $fixture['team_a'],
            $validTeamIds,
            true
        )
    ) {

        $teamRelationshipsValid =
            false;

        break;
    }
}


testPass(
    'Every fixture references valid FPL teams',
    $teamRelationshipsValid
);


/*
 * ============================================================
 * SCENARIO L
 * Fixture Difficulty Values
 * ============================================================
 */

section(
    'Scenario L: Fixture Difficulty Values'
);


$difficultyValuesValid =
    true;


foreach (
    $fixtures
    as $fixture
) {

    $homeDifficulty =
        $fixture['team_h_difficulty']
        ?? null;

    $awayDifficulty =
        $fixture['team_a_difficulty']
        ?? null;


    if (
        $homeDifficulty !== null
        &&
        (
            (int) $homeDifficulty < 1
            ||
            (int) $homeDifficulty > 5
        )
    ) {

        $difficultyValuesValid =
            false;

        break;
    }


    if (
        $awayDifficulty !== null
        &&
        (
            (int) $awayDifficulty < 1
            ||
            (int) $awayDifficulty > 5
        )
    ) {

        $difficultyValuesValid =
            false;

        break;
    }
}


testPass(
    'Fixture difficulty values remain within 1-5',
    $difficultyValuesValid
);


/*
 * ============================================================
 * SCENARIO M
 * Bootstrap Team IDs Are Unique
 * ============================================================
 */

section(
    'Scenario M: Unique Team IDs'
);


$teamIds =
    array_column(
        $teams,
        'id'
    );


testPass(
    'All team IDs are unique',
    count(
        array_unique(
            $teamIds
        )
    )
    ===
    count($teamIds)
);


/*
 * ============================================================
 * SCENARIO N
 * Player IDs Are Unique
 * ============================================================
 */

section(
    'Scenario N: Unique Player IDs'
);


$playerIds =
    array_column(
        $players,
        'id'
    );


testPass(
    'All player IDs are unique',
    count(
        array_unique(
            $playerIds
        )
    )
    ===
    count($playerIds)
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

section(
    'Front-End Friendly FPL API Output'
);


echo "Teams: "
    . count($teams)
    . "<br>";


echo "Players: "
    . count($players)
    . "<br>";


echo "Gameweeks: "
    . count($events)
    . "<br>";


echo "Fixtures: "
    . count($fixtures)
    . "<br>";


if ($firstTeam !== null) {

    echo "Example Team: "
        . $firstTeam['name']
        . "<br>";
}


if ($firstPlayer !== null) {

    echo "Example Player: "
        . $firstPlayer['web_name']
        . "<br>";
}


if ($firstFixture !== null) {

    echo "Example Fixture ID: "
        . $firstFixture['id']
        . "<br>";

    echo "Example Fixture Gameweek: "
        . (
            $firstFixture['event']
            ?? 'TBC'
        )
        . "<br>";
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

section(
    'FPL API Test Summary'
);


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}