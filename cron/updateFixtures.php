<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * FPL FIXTURE UPDATE
 * ============================================================
 */

echo "Starting FPL Fixture Update...\n\n";


$database = null;
$db = null;


try {

    /*
     * --------------------------------------------------------
     * DATABASE
     * --------------------------------------------------------
     */

    $database =
        new Database();


    $db =
        $database->getConnection();


    echo "Database connection successful\n";


    /*
     * --------------------------------------------------------
     * SERVICES
     * --------------------------------------------------------
     */

    $fpl =
        new FPLApi();


    $teamRepository =
        new TeamRepository(
            $db
        );


    $fixtureRepository =
        new FixtureRepository(
            $db
        );


    /*
     * --------------------------------------------------------
     * FETCH FIXTURES
     * --------------------------------------------------------
     */

    $fixtures =
        $fpl->getFixtures();


    echo "FPL API connection successful\n";


    echo "Fixtures received: "
        . count($fixtures)
        . "\n\n";


    if (empty($fixtures)) {

        throw new RuntimeException(
            'No fixtures were returned by the FPL API'
        );
    }


    /*
     * --------------------------------------------------------
     * IMPORT FIXTURES
     * --------------------------------------------------------
     */

    $updated =
        0;


    $skipped =
        0;


    $db->beginTransaction();


    foreach ($fixtures as $fixture) {

        /*
         * A valid FPL fixture requires identity
         * and both participating teams.
         */
        if (
            !isset(
                $fixture['id'],
                $fixture['team_h'],
                $fixture['team_a']
            )
        ) {

            $skipped++;

            echo "Skipping malformed fixture\n";

            continue;
        }


        $fixtureId =
            (int) $fixture['id'];


        $homeTeamId =
            $teamRepository
                ->getTeamIdByFplId(
                    (int) $fixture['team_h']
                );


        $awayTeamId =
            $teamRepository
                ->getTeamIdByFplId(
                    (int) $fixture['team_a']
                );


        if (
            $homeTeamId === null
            ||
            $awayTeamId === null
        ) {

            $skipped++;


            echo "Skipping fixture "
                . $fixtureId
                . " - team not found\n";


            continue;
        }


        $fixtureRepository
            ->upsert(
                $fixture,
                $homeTeamId,
                $awayTeamId
            );


        $updated++;
    }


    $db->commit();


    /*
     * --------------------------------------------------------
     * SUMMARY
     * --------------------------------------------------------
     */

    echo "\nFixtures inserted/updated: "
        . $updated
        . "\n";


    echo "Fixtures skipped: "
        . $skipped
        . "\n";


    echo "\nFixture update complete\n";

} catch (Throwable $exception) {

    /*
     * Roll back the entire fixture update if a genuine
     * import failure occurs.
     */
    if (
        $db instanceof PDO
        &&
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    echo "\nERROR: "
        . $exception->getMessage()
        . "\n";


    exit(1);
}