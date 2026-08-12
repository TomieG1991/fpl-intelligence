<?php

require_once '../classes/autoload.php';


/*
 * ============================================================
 * APPLICATION HEADER
 * ============================================================
 */

echo "<h1>FPL Intelligence v1.0</h1>";


/*
 * ============================================================
 * DATABASE HEALTH CHECK
 * ============================================================
 */

try {

    $database = new Database();

    $db = $database->getConnection();

    echo "<p>Database: CONNECTED ✅</p>";

} catch (Exception $e) {

    echo "<p>Database: FAILED ❌</p>";

    echo $e->getMessage();

    exit;
}


/*
 * ============================================================
 * FPL API HEALTH CHECK
 * ============================================================
 */

try {

    $fpl = new FPLApi();

    $data = $fpl->getBootstrapData();

    echo "<p>FPL API: CONNECTED ✅</p>";

    echo "<p>Players Found: "
        . count($data['elements'])
        . "</p>";

    echo "<p>Teams Found: "
        . count($data['teams'])
        . "</p>";

} catch (Exception $e) {

    echo "<p>FPL API: FAILED ❌</p>";

}


/*
 * ============================================================
 * REPOSITORIES
 * ============================================================
 */

$fixtureRepository =
    new FixtureRepository($db);

$teamRepository =
    new TeamRepository($db);


/*
 * ============================================================
 * LOAD DATABASE DATA
 * ============================================================
 */

$fixtures =
    $fixtureRepository->getAll();

$teams =
    $teamRepository->getAll();


/*
 * ============================================================
 * TEAM BASELINE STRENGTH
 * ============================================================
 */

$teamStrength =
    new TeamStrength();

$teamStrengths =
    $teamStrength->calculateTeamStrengths(
        $teams
    );


/*
 * ============================================================
 * TEAM PERFORMANCE
 * ============================================================
 */

$teamPerformance =
    new TeamPerformance();


/*
 * ============================================================
 * TEAM STRENGTH MODEL
 * ============================================================
 */

$teamStrengthModel =
    new TeamStrengthModel();


/*
 * ============================================================
 * BUILD COMPLETE TEAM MODELS
 * ============================================================
 */

$completeTeamModels = [];


foreach (
    $teamStrengths
    as $teamId => $baseline
) {

    $performance =
        $teamPerformance->analyse(
            $fixtures,
            (int) $teamId
        );


    $completeTeamModels[$teamId] =
        $teamStrengthModel->buildTeamModel(
            $baseline,
            $performance,
            $teamPerformance
        );
}

/*
 * ============================================================
 * MODEL-BASED FIXTURE INTELLIGENCE
 * ============================================================
 *
 * Convert the complete team models into the structure
 * expected by FixtureIntelligence.
 */

$fixtureIntelligence =
    new FixtureIntelligence();


$modelStrengths = [];


foreach (
    $completeTeamModels
    as $teamId => $teamModel
) {

    $modelStrengths[$teamId] = [

        'id' =>
            $teamModel['id'],

        'name' =>
            $teamModel['name'],

        'home' =>
            $teamModel['home'],

        'away' =>
            $teamModel['away'],

        'overall' =>
            $teamModel['overall']
    ];
}

