<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * FPL INTELLIGENCE
 * APPLICATION ENTRY POINT
 * ============================================================
 */

$databaseConnected =
    false;

$databaseError =
    null;

$teamCount =
    0;

$playerCount =
    0;

$fixtureCount =
    0;


/*
 * ============================================================
 * DATABASE HEALTH
 * ============================================================
 */

try {

    $database =
        new Database();


    $db =
        $database->getConnection();


    $databaseConnected =
        true;


    /*
     * --------------------------------------------------------
     * REPOSITORIES
     * --------------------------------------------------------
     */

    $teamRepository =
        new TeamRepository(
            $db
        );


    $playerRepository =
        new PlayerRepository(
            $db
        );


    $fixtureRepository =
        new FixtureRepository(
            $db
        );


    /*
     * --------------------------------------------------------
     * APPLICATION DATA COUNTS
     * --------------------------------------------------------
     */

    $teamCount =
        count(
            $teamRepository->getAll()
        );


    $playerCount =
        count(
            $playerRepository->getAll()
        );


    $fixtureCount =
        count(
            $fixtureRepository->getAll()
        );

} catch (Throwable $exception) {

    $databaseError =
        $exception->getMessage();
}


?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>FPL Intelligence</title>

</head>

<body>

    <main>

        <h1>FPL Intelligence</h1>


        <section>

            <h2>Application Status</h2>


            <?php if ($databaseConnected): ?>

                <p>
                    Database:
                    <strong>CONNECTED ✅</strong>
                </p>

            <?php else: ?>

                <p>
                    Database:
                    <strong>FAILED ❌</strong>
                </p>

                <?php if ($databaseError !== null): ?>

                    <p>
                        <?= htmlspecialchars(
                            $databaseError,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </p>

                <?php endif; ?>

            <?php endif; ?>

        </section>


        <?php if ($databaseConnected): ?>

            <section>

                <h2>FPL Data</h2>

                <p>
                    Teams:
                    <strong>
                        <?= $teamCount; ?>
                    </strong>
                </p>

                <p>
                    Players:
                    <strong>
                        <?= $playerCount; ?>
                    </strong>
                </p>

                <p>
                    Fixtures:
                    <strong>
                        <?= $fixtureCount; ?>
                    </strong>
                </p>

            </section>

        <?php endif; ?>

    </main>

</body>

</html>