<?php

require_once __DIR__ . '/../classes/autoload.php';


echo "<h1>FPL Intelligence - Fixture Update</h1>";


try {

    // Create core objects

    $database = new Database();

    $fpl = new FPLApi();

    $teamRepository = new TeamRepository(
        $database->getConnection()
    );

    $fixtureRepository = new FixtureRepository(
        $database->getConnection()
    );


    // Get fixtures from FPL API

    $fixtures = $fpl->getFixtures();

    echo "<p>Fixtures received from FPL API: "
        . count($fixtures)
        . "</p>";


    $updated = 0;


    // Process each fixture

    foreach ($fixtures as $fixture) {

        $homeTeamId = $teamRepository->getTeamIdByFplId(
            (int) $fixture['team_h']
        );

        $awayTeamId = $teamRepository->getTeamIdByFplId(
            (int) $fixture['team_a']
        );


        if ($homeTeamId === null || $awayTeamId === null) {

            echo "<p>⚠️ Skipping fixture "
                . $fixture['id']
                . " because a team could not be found.</p>";

            continue;

        }


        $fixtureRepository->upsert(
            $fixture,
            $homeTeamId,
            $awayTeamId
        );


        $updated++;

    }


    echo "<p>Fixtures inserted/updated: "
        . $updated
        . "</p>";


    echo "<p><strong>Fixture update complete ✅</strong></p>";


}
catch (Exception $e) {

    echo "<p><strong>Fixture update failed ❌</strong></p>";

    echo "<p>"
        . htmlspecialchars($e->getMessage())
        . "</p>";

}