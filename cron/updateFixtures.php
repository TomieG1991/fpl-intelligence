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


$updateRunLifecycle =
    null;

$updateRunId =
    null;

$updateStartedMicrotime =
    null;

$recordsReceived =
    0;

$recordsUpdated =
    0;

$recordsSkipped =
    0;


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
     * UPDATE RUN TRACKING
     * --------------------------------------------------------
     */

    $updateRunRepository =
        new UpdateRunRepository(
            $db
        );


    $updateRunLifecycle =
        new UpdateRunLifecycleService(
            $updateRunRepository
        );


    $updateStartedAt =
        date(
            'Y-m-d H:i:s'
        );


    $updateStartedMicrotime =
        microtime(
            true
        );


    $updateRunId =
        $updateRunLifecycle
            ->start(
                'fixtures',
                $updateStartedAt
            );


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


    $recordsReceived =
        count(
            $fixtures
        );


    echo "Fixtures received: "
        . $recordsReceived
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


    $recordsUpdated =
        $updated;


    $recordsSkipped =
        $skipped;


    $updateCompletedAt =
        date(
            'Y-m-d H:i:s'
        );


    $updateDurationMs =
        (int) round(
            (
                microtime(
                    true
                )
                -
                $updateStartedMicrotime
            )
            *
            1000
        );


    /*
     * Complete the persisted update run inside the same
     * transaction as the imported fixture data.
     */
    $updateRunLifecycle
        ->succeed(
            $updateRunId,
            $updateCompletedAt,
            $recordsReceived,
            $recordsUpdated,
            $recordsSkipped,
            $updateDurationMs
        );


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


   /*
     * Persist the failed updater attempt when run tracking
     * was successfully established.
     */
    if (
        $updateRunLifecycle
            instanceof UpdateRunLifecycleService
        &&
        is_int(
            $updateRunId
        )
        &&
        $updateRunId > 0
        &&
        is_float(
            $updateStartedMicrotime
        )
    ) {

        $updateCompletedAt =
            date(
                'Y-m-d H:i:s'
            );


        $updateDurationMs =
            (int) round(
                (
                    microtime(
                        true
                    )
                    -
                    $updateStartedMicrotime
                )
                *
                1000
            );


        try {

            $updateRunLifecycle
                ->fail(
                    $updateRunId,
                    $updateCompletedAt,
                    $recordsReceived,
                    0,
                    $skipped ?? 0,
                    1,
                    $updateDurationMs,
                    $exception->getMessage()
                );

        } catch (Throwable $trackingException) {

            echo "UPDATE TRACKING ERROR: "
                . $trackingException->getMessage()
                . "\n";
        }
    }


    echo "\nERROR: "
        . $exception->getMessage()
        . "\n";


    exit(1);
}