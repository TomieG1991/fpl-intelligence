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
        new TeamRepository(
            $db
        );


    testPass(
        'Database connection is available',
        $db instanceof PDO
    );


    testPass(
        'TeamRepository can be created',
        $repository instanceof TeamRepository
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
    echo "Team Repository Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: {$passed}<br>";
    echo "Failed: {$failed}<br>";

    echo "<br>RESULT: TESTS FAILED ❌<br>";

    exit;
}


/*
 * ============================================================
 * SCENARIO A
 * Get All Teams
 * ============================================================
 */

section(
    'Scenario A: Get All Teams'
);


$teams =
    $repository->getAll();


echo "Teams Found: "
    . count($teams)
    . "<br>";


testPass(
    'getAll returns an array',
    is_array($teams)
);


testPass(
    'Team database contains teams',
    count($teams) > 0
);


/*
 * ============================================================
 * SCENARIO B
 * Team Count
 * ============================================================
 */

section(
    'Scenario B: Premier League Team Count'
);


testPass(
    'Database contains 20 teams',
    count($teams) === 20
);


/*
 * ============================================================
 * SCENARIO C
 * Team Data Structure
 * ============================================================
 */

section(
    'Scenario C: Team Data Structure'
);


$firstTeam =
    $teams[0]
    ?? null;


testPass(
    'A team record is available',
    is_array($firstTeam)
);


$requiredFields = [

    'id',
    'fpl_team_id',
    'name'
];


foreach (
    $requiredFields
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
 * Local Team ID Lookup
 * ============================================================
 */

section(
    'Scenario D: Local Team ID Lookup'
);


$teamById =
    $repository->getById(
        (int) $firstTeam['id']
    );


testPass(
    'getById returns a team',
    is_array($teamById)
);


testPass(
    'getById returns the requested team',
    isset($teamById['id'])
    &&
    (int) $teamById['id']
        ===
    (int) $firstTeam['id']
);


/*
 * ============================================================
 * SCENARIO E
 * FPL Team ID Lookup
 * ============================================================
 */

section(
    'Scenario E: FPL Team ID Lookup'
);


$teamByFplId =
    $repository->getByFplTeamId(
        (int) $firstTeam['fpl_team_id']
    );


testPass(
    'getByFplTeamId returns a team',
    is_array($teamByFplId)
);


testPass(
    'getByFplTeamId returns the requested FPL team',
    isset($teamByFplId['fpl_team_id'])
    &&
    (int) $teamByFplId['fpl_team_id']
        ===
    (int) $firstTeam['fpl_team_id']
);


/*
 * ============================================================
 * SCENARIO F
 * Local ID From FPL ID
 * ============================================================
 */

section(
    'Scenario F: Local Team ID From FPL ID'
);


$localTeamId =
    $repository->getTeamIdByFplId(
        (int) $firstTeam['fpl_team_id']
    );


testPass(
    'getTeamIdByFplId returns an integer ID',
    is_int($localTeamId)
);


testPass(
    'getTeamIdByFplId returns the correct local ID',
    $localTeamId
        ===
    (int) $firstTeam['id']
);


/*
 * ============================================================
 * SCENARIO G
 * Missing Team Lookups
 * ============================================================
 */

section(
    'Scenario G: Missing Team Lookups'
);


$missingTeam =
    $repository->getById(
        PHP_INT_MAX
    );


$missingFplTeam =
    $repository->getByFplTeamId(
        PHP_INT_MAX
    );


$missingLocalId =
    $repository->getTeamIdByFplId(
        PHP_INT_MAX
    );


testPass(
    'Unknown local team ID returns null',
    $missingTeam === null
);


testPass(
    'Unknown FPL team ID returns null team',
    $missingFplTeam === null
);


testPass(
    'Unknown FPL team ID returns null local ID',
    $missingLocalId === null
);


/*
 * ============================================================
 * SCENARIO H
 * Alphabetical Ordering
 * ============================================================
 */

section(
    'Scenario H: Team Ordering'
);


$orderCorrect =
    true;

$previousName =
    null;


foreach (
    $teams
    as $team
) {

    $name =
        (string) (
            $team['name']
            ?? ''
        );


    if (
        $previousName !== null
        &&
        strcasecmp(
            $name,
            $previousName
        ) < 0
    ) {

        $orderCorrect =
            false;

        break;
    }


    $previousName =
        $name;
}


testPass(
    'Teams are ordered alphabetically by name',
    $orderCorrect
);


/*
 * ============================================================
 * SCENARIO I
 * Strength Data Compatibility
 * ============================================================
 */

section(
    'Scenario I: Team Strength Data Compatibility'
);


/*
 * The current team intelligence layer relies on
 * FPL strength data being available from the database.
 *
 * These names reflect the team fields imported from
 * bootstrap-static.
 */

$strengthFields = [

    'strength_overall_home',
    'strength_overall_away',

    'strength_attack_home',
    'strength_attack_away',

    'strength_defence_home',
    'strength_defence_away'
];


foreach (
    $strengthFields
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
 * SCENARIO J
 * Strength Values Are Usable
 * ============================================================
 */

section(
    'Scenario J: Team Strength Values'
);


testPass(
    'Home overall strength is numeric',
    isset($firstTeam['strength_overall_home'])
    &&
    is_numeric(
        $firstTeam['strength_overall_home']
    )
);


testPass(
    'Away overall strength is numeric',
    isset($firstTeam['strength_overall_away'])
    &&
    is_numeric(
        $firstTeam['strength_overall_away']
    )
);


testPass(
    'Home attack strength is numeric',
    isset($firstTeam['strength_attack_home'])
    &&
    is_numeric(
        $firstTeam['strength_attack_home']
    )
);


testPass(
    'Away attack strength is numeric',
    isset($firstTeam['strength_attack_away'])
    &&
    is_numeric(
        $firstTeam['strength_attack_away']
    )
);


testPass(
    'Home defence strength is numeric',
    isset($firstTeam['strength_defence_home'])
    &&
    is_numeric(
        $firstTeam['strength_defence_home']
    )
);


testPass(
    'Away defence strength is numeric',
    isset($firstTeam['strength_defence_away'])
    &&
    is_numeric(
        $firstTeam['strength_defence_away']
    )
);


/*
 * ============================================================
 * SCENARIO K
 * Full Record Lookup Consistency
 * ============================================================
 */

section(
    'Scenario K: Full Record Lookup Consistency'
);


testPass(
    'Local-ID lookup preserves team name',
    isset($teamById['name'])
    &&
    $teamById['name']
        ===
    $firstTeam['name']
);


testPass(
    'FPL-ID lookup preserves team name',
    isset($teamByFplId['name'])
    &&
    $teamByFplId['name']
        ===
    $firstTeam['name']
);


testPass(
    'Local-ID and FPL-ID lookups return same team',
    (int) $teamById['id']
        ===
    (int) $teamByFplId['id']
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

section(
    'Front-End Friendly Team Repository Output'
);


echo "Teams in Database: "
    . count($teams)
    . "<br>";


if ($firstTeam !== null) {

    echo "Example Team: "
        . $firstTeam['name']
        . "<br>";

    echo "Local Team ID: "
        . $firstTeam['id']
        . "<br>";

    echo "FPL Team ID: "
        . $firstTeam['fpl_team_id']
        . "<br>";

    echo "Home Overall Strength: "
        . $firstTeam['strength_overall_home']
        . "<br>";

    echo "Away Overall Strength: "
        . $firstTeam['strength_overall_away']
        . "<br>";

    echo "Home Attack Strength: "
        . $firstTeam['strength_attack_home']
        . "<br>";

    echo "Away Attack Strength: "
        . $firstTeam['strength_attack_away']
        . "<br>";

    echo "Home Defence Strength: "
        . $firstTeam['strength_defence_home']
        . "<br>";

    echo "Away Defence Strength: "
        . $firstTeam['strength_defence_away']
        . "<br>";

    echo "Home Strength: "
        . $firstTeam['strength_overall_home']
        . "<br>";

    echo "Away Strength: "
        . $firstTeam['strength_overall_away']
        . "<br>";
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

section(
    'Team Repository Test Summary'
);


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}