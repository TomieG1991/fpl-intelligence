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
        new FixtureRepository(
            $db
        );


    testPass(
        'Database connection is available',
        $db instanceof PDO
    );


    testPass(
        'FixtureRepository can be created',
        $repository instanceof FixtureRepository
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
    echo "Fixture Repository Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: {$passed}<br>";
    echo "Failed: {$failed}<br>";

    echo "<br>RESULT: TESTS FAILED ❌<br>";

    exit;
}


/*
 * ============================================================
 * SCENARIO A
 * Get All Fixtures
 * ============================================================
 */

section(
    'Scenario A: Get All Fixtures'
);


$fixtures =
    $repository->getAll();


echo "Fixtures Found: "
    . count($fixtures)
    . "<br>";


testPass(
    'getAll returns an array',
    is_array($fixtures)
);


testPass(
    'Fixture database contains fixtures',
    count($fixtures) > 0
);


/*
 * ============================================================
 * SCENARIO B
 * Premier League Fixture Count
 * ============================================================
 */

section(
    'Scenario B: Premier League Fixture Count'
);


testPass(
    'Database contains 380 Premier League fixtures',
    count($fixtures) === 380
);


/*
 * ============================================================
 * SCENARIO C
 * Fixture Data Structure
 * ============================================================
 */

section(
    'Scenario C: Fixture Data Structure'
);


$firstFixture =
    $fixtures[0]
    ?? null;


testPass(
    'A fixture record is available',
    is_array($firstFixture)
);


$requiredFields = [

    'id',
    'fpl_fixture_id',
    'gameweek',

    'home_team_id',
    'away_team_id',

    'kickoff_time',

    'finished',
    'finished_provisional',

    'home_score',
    'away_score',

    'home_difficulty',
    'away_difficulty'
];


foreach (
    $requiredFields
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
 * SCENARIO D
 * Unique FPL Fixture IDs
 * ============================================================
 */

section(
    'Scenario D: Unique FPL Fixture IDs'
);


$fplFixtureIds =
    array_column(
        $fixtures,
        'fpl_fixture_id'
    );


$uniqueFplFixtureIds =
    array_unique(
        $fplFixtureIds
    );


echo "Fixture IDs: "
    . count($fplFixtureIds)
    . "<br>";

echo "Unique Fixture IDs: "
    . count($uniqueFplFixtureIds)
    . "<br>";


testPass(
    'Every fixture has an FPL fixture ID',
    count($fplFixtureIds)
        ===
    count($fixtures)
);


testPass(
    'All FPL fixture IDs are unique',
    count($uniqueFplFixtureIds)
        ===
    count($fixtures)
);


/*
 * ============================================================
 * SCENARIO E
 * Local Fixture ID Lookup
 * ============================================================
 */

section(
    'Scenario E: Local Fixture ID Lookup'
);


$fixtureById =
    $repository->getById(
        (int) $firstFixture['id']
    );


testPass(
    'getById returns a fixture',
    is_array($fixtureById)
);


testPass(
    'getById returns the requested fixture',
    isset($fixtureById['id'])
    &&
    (int) $fixtureById['id']
        ===
    (int) $firstFixture['id']
);


/*
 * ============================================================
 * SCENARIO F
 * FPL Fixture ID Lookup
 * ============================================================
 */

section(
    'Scenario F: FPL Fixture ID Lookup'
);


$fixtureByFplId =
    $repository->getByFplFixtureId(
        (int) $firstFixture['fpl_fixture_id']
    );


testPass(
    'getByFplFixtureId returns a fixture',
    is_array($fixtureByFplId)
);


testPass(
    'getByFplFixtureId returns the requested FPL fixture',
    isset($fixtureByFplId['fpl_fixture_id'])
    &&
    (int) $fixtureByFplId['fpl_fixture_id']
        ===
    (int) $firstFixture['fpl_fixture_id']
);


/*
 * ============================================================
 * SCENARIO G
 * Missing Fixture Lookups
 * ============================================================
 */

section(
    'Scenario G: Missing Fixture Lookups'
);


$missingFixture =
    $repository->getById(
        PHP_INT_MAX
    );


$missingFplFixture =
    $repository->getByFplFixtureId(
        PHP_INT_MAX
    );


testPass(
    'Unknown local fixture ID returns null',
    $missingFixture === null
);


testPass(
    'Unknown FPL fixture ID returns null',
    $missingFplFixture === null
);


/*
 * ============================================================
 * SCENARIO H
 * Team Relationships
 * ============================================================
 */

section(
    'Scenario H: Fixture Team Relationships'
);


$teamRepository =
    new TeamRepository(
        $db
    );


$homeTeam =
    $teamRepository->getById(
        (int) $firstFixture['home_team_id']
    );


$awayTeam =
    $teamRepository->getById(
        (int) $firstFixture['away_team_id']
    );


testPass(
    'Fixture home team ID references a valid team',
    is_array($homeTeam)
);


testPass(
    'Fixture away team ID references a valid team',
    is_array($awayTeam)
);


testPass(
    'Fixture home and away teams are different',
    (int) $firstFixture['home_team_id']
        !==
    (int) $firstFixture['away_team_id']
);


/*
 * ============================================================
 * SCENARIO I
 * Upcoming Fixtures For Team
 * ============================================================
 */

section(
    'Scenario I: Upcoming Fixtures For Team'
);


$testTeamId =
    (int) $firstFixture['home_team_id'];


$upcomingFixtures =
    $repository->getUpcomingForTeam(
        $testTeamId,
        5
    );


echo "Upcoming Fixtures Found: "
    . count($upcomingFixtures)
    . "<br>";


testPass(
    'getUpcomingForTeam returns an array',
    is_array($upcomingFixtures)
);


testPass(
    'Upcoming fixture query respects limit',
    count($upcomingFixtures) <= 5
);


foreach (
    $upcomingFixtures
    as $fixture
) {

    testPass(
        'Upcoming fixture involves requested team',
        (int) $fixture['home_team_id']
            ===
        $testTeamId
        ||
        (int) $fixture['away_team_id']
            ===
        $testTeamId
    );


    testPass(
        'Upcoming fixture is unfinished',
        (int) $fixture['finished'] === 0
    );
}


/*
 * ============================================================
 * SCENARIO J
 * Upcoming Fixture Limit Handling
 * ============================================================
 */

section(
    'Scenario J: Upcoming Fixture Limit Handling'
);


$topThree =
    $repository->getUpcomingForTeam(
        $testTeamId,
        3
    );


$topZero =
    $repository->getUpcomingForTeam(
        $testTeamId,
        0
    );


$topNegative =
    $repository->getUpcomingForTeam(
        $testTeamId,
        -5
    );


testPass(
    'Upcoming fixture limit of 3 returns no more than three fixtures',
    count($topThree) <= 3
);


testPass(
    'Upcoming fixture limit of 0 returns empty array',
    $topZero === []
);


testPass(
    'Negative upcoming fixture limit returns empty array',
    $topNegative === []
);


/*
 * ============================================================
 * SCENARIO K
 * Finished Fixtures
 * ============================================================
 */

section(
    'Scenario K: Finished Fixtures'
);


$finishedFixtures =
    $repository->getFinishedFixtures();


echo "Finished Fixtures Found: "
    . count($finishedFixtures)
    . "<br>";


testPass(
    'getFinishedFixtures returns an array',
    is_array($finishedFixtures)
);


foreach (
    $finishedFixtures
    as $fixture
) {

    testPass(
        'Finished fixture is marked as finished',
        (int) $fixture['finished'] === 1
    );
}


/*
 * ============================================================
 * SCENARIO L
 * Finished Fixtures For Team
 * ============================================================
 */

section(
    'Scenario L: Finished Fixtures For Team'
);


$finishedForTeam =
    $repository->getFinishedForTeam(
        $testTeamId
    );


echo "Finished Team Fixtures Found: "
    . count($finishedForTeam)
    . "<br>";


testPass(
    'getFinishedForTeam returns an array',
    is_array($finishedForTeam)
);


foreach (
    $finishedForTeam
    as $fixture
) {

    testPass(
        'Finished team fixture involves requested team',
        (int) $fixture['home_team_id']
            ===
        $testTeamId
        ||
        (int) $fixture['away_team_id']
            ===
        $testTeamId
    );


    testPass(
        'Finished team fixture is marked finished',
        (int) $fixture['finished'] === 1
    );
}


/*
 * ============================================================
 * SCENARIO M
 * Fixture Ordering
 * ============================================================
 */

section(
    'Scenario M: Fixture Ordering'
);


$orderCorrect =
    true;

$previousGameweek =
    null;

$previousKickoff =
    null;


foreach (
    $fixtures
    as $fixture
) {

    $gameweek =
        $fixture['gameweek'] !== null
            ? (int) $fixture['gameweek']
            : null;


    $kickoff =
        $fixture['kickoff_time']
        ?? null;


    /*
     * Ignore unscheduled fixtures without a gameweek.
     */
    if ($gameweek === null) {
        continue;
    }


    if (
        $previousGameweek !== null
        &&
        $gameweek < $previousGameweek
    ) {

        $orderCorrect =
            false;

        break;
    }


    if (
        $previousGameweek !== null
        &&
        $gameweek === $previousGameweek
        &&
        $previousKickoff !== null
        &&
        $kickoff !== null
        &&
        $kickoff < $previousKickoff
    ) {

        $orderCorrect =
            false;

        break;
    }


    $previousGameweek =
        $gameweek;

    $previousKickoff =
        $kickoff;
}


testPass(
    'Fixtures are returned in chronological gameweek order',
    $orderCorrect
);


/*
 * ============================================================
 * SCENARIO N
 * Difficulty Values
 * ============================================================
 */

section(
    'Scenario N: FPL Fixture Difficulty'
);


$difficultyValid =
    true;


foreach (
    $fixtures
    as $fixture
) {

    $homeDifficulty =
        $fixture['home_difficulty'];

    $awayDifficulty =
        $fixture['away_difficulty'];


    if (
        $homeDifficulty !== null
        &&
        (
            (int) $homeDifficulty < 1
            ||
            (int) $homeDifficulty > 5
        )
    ) {

        $difficultyValid =
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

        $difficultyValid =
            false;

        break;
    }
}


testPass(
    'Stored FPL difficulty ratings remain within 1-5',
    $difficultyValid
);


/*
 * ============================================================
 * SCENARIO O
 * Full Record Lookup Consistency
 * ============================================================
 */

section(
    'Scenario O: Full Record Lookup Consistency'
);


testPass(
    'Local-ID and FPL-ID lookups return same fixture',
    (int) $fixtureById['id']
        ===
    (int) $fixtureByFplId['id']
);


testPass(
    'Fixture lookup preserves home team',
    (int) $fixtureById['home_team_id']
        ===
    (int) $firstFixture['home_team_id']
);


testPass(
    'Fixture lookup preserves away team',
    (int) $fixtureById['away_team_id']
        ===
    (int) $firstFixture['away_team_id']
);


/*
 * ============================================================
 * SCENARIO P
 * Fixture Intelligence Compatibility
 * ============================================================
 */

section(
    'Scenario P: Fixture Intelligence Compatibility'
);


/*
 * Build the team-strength structure expected by
 * FixtureIntelligence using the project's existing
 * TeamStrength model.
 */

$teams =
    $teamRepository->getAll();


$teamStrengthModel =
    new TeamStrength();


$teamStrengths =
    $teamStrengthModel->calculateTeamStrengths(
        $teams
    );


testPass(
    'TeamStrength produces strength data for all teams',
    is_array($teamStrengths)
    &&
    count($teamStrengths) === 20
);


testPass(
    'Requested team has calculated strength data',
    isset(
        $teamStrengths[$testTeamId]
    )
);


testPass(
    'Calculated team strength contains home baseline',
    isset(
        $teamStrengths[$testTeamId]['home']
    )
);


testPass(
    'Calculated team strength contains away baseline',
    isset(
        $teamStrengths[$testTeamId]['away']
    )
);


testPass(
    'Calculated team strength contains overall baseline',
    isset(
        $teamStrengths[$testTeamId]['overall']
    )
);


$fixtureIntelligence =
    new FixtureIntelligence();


$fixtureRun =
    $fixtureIntelligence->analyseFixtureRun(
        $fixtures,
        $teamStrengths,
        $testTeamId
    );


testPass(
    'Repository fixtures can be passed into FixtureIntelligence',
    is_array($fixtureRun)
);


testPass(
    'FixtureIntelligence returns fixtures for database team',
    count($fixtureRun) > 0
);


if (!empty($fixtureRun)) {

    testPass(
        'Fixture intelligence contains fixture score',
        array_key_exists(
            'fixture_score',
            $fixtureRun[0]
        )
    );


    testPass(
        'Fixture intelligence contains difficulty',
        array_key_exists(
            'difficulty',
            $fixtureRun[0]
        )
    );


    testPass(
        'Fixture intelligence contains venue',
        array_key_exists(
            'venue',
            $fixtureRun[0]
        )
    );


    testPass(
        'Fixture intelligence contains team baseline',
        array_key_exists(
            'team_baseline',
            $fixtureRun[0]
        )
    );


    testPass(
        'Fixture intelligence contains opponent baseline',
        array_key_exists(
            'opponent_baseline',
            $fixtureRun[0]
        )
    );
}


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

section(
    'Front-End Friendly Fixture Repository Output'
);


echo "Fixtures in Database: "
    . count($fixtures)
    . "<br>";


echo "Unique FPL Fixtures: "
    . count($uniqueFplFixtureIds)
    . "<br>";


if ($firstFixture !== null) {

    echo "Example Fixture ID: "
        . $firstFixture['id']
        . "<br>";

    echo "FPL Fixture ID: "
        . $firstFixture['fpl_fixture_id']
        . "<br>";

    echo "Gameweek: "
        . (
            $firstFixture['gameweek']
            ?? 'TBC'
        )
        . "<br>";

    echo "Home Team: "
        . (
            $homeTeam['name']
            ?? 'Unknown'
        )
        . "<br>";

    echo "Away Team: "
        . (
            $awayTeam['name']
            ?? 'Unknown'
        )
        . "<br>";

    echo "Kickoff: "
        . (
            $firstFixture['kickoff_time']
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
    'Fixture Repository Test Summary'
);


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}