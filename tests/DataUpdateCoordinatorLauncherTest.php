<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Data Update Coordinator Launcher Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function dataUpdateCoordinatorLauncherCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


function dataUpdateCoordinatorLauncherSection(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>";
    echo "============================================<br>";
}


function dataUpdateCoordinatorLauncherSummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Data Update Coordinator Launcher Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br><br>";


    if ($failed === 0) {

        echo "RESULT: ALL TESTS PASSED ✅";

    } else {

        echo "RESULT: TESTS FAILED ❌";
    }
}


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

dataUpdateCoordinatorLauncherSection(
    'Scenario A: Class Contract'
);


dataUpdateCoordinatorLauncherCheck(
    'DataUpdateCoordinatorLauncher exists.',
    class_exists(
        'DataUpdateCoordinatorLauncher'
    )
);


/*
 * ============================================================
 * SCENARIO B
 * PRODUCTION WIRING
 * ============================================================
 *
 * No updater script is executed.
 *
 * The injected runner records what the launcher asks it to run
 * and returns synthetic Success results.
 */

dataUpdateCoordinatorLauncherSection(
    'Scenario B: Production Wiring'
);


$projectRoot =
    'C:\\synthetic\\fpl-intelligence';


$calls =
    [];


$syntheticRunner =
    static function (
        string $updateType,
        string $scriptPath,
        array $arguments = []
    ) use (
        &$calls
    ): array {

        $calls[] = [

            'update_type' =>
                $updateType,

            'script_path' =>
                $scriptPath,

            'arguments' =>
                $arguments
        ];


        return [

            'update_type' =>
                $updateType,

            'status' =>
                'Success',

            'error_message' =>
                null
        ];
    };


$launcher =
    new DataUpdateCoordinatorLauncher(
        $projectRoot,
        $syntheticRunner
    );


$launcherIsRunnable =
    method_exists(
        $launcher,
        'run'
    );


dataUpdateCoordinatorLauncherCheck(
    'Launcher exposes run().',
    $launcherIsRunnable
);


/*
 * Stop cleanly during the expected RED rather than triggering
 * a fatal error by calling a method that does not exist yet.
 */
if (!$launcherIsRunnable) {

    dataUpdateCoordinatorLauncherSummary();

    exit;
}


$result =
    $launcher->run();


dataUpdateCoordinatorLauncherCheck(
    'Launcher returns overall Success when all updates succeed.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Success'
);


dataUpdateCoordinatorLauncherCheck(
    'Launcher executes exactly three update steps.',
    count(
        $calls
    )
    ===
    3
);


/*
 * ============================================================
 * BOOTSTRAP WIRING
 * ============================================================
 */

dataUpdateCoordinatorLauncherCheck(
    'Bootstrap update type is correct.',
    (
        $calls[0][
            'update_type'
        ]
        ?? null
    )
    ===
    'bootstrap'
);


dataUpdateCoordinatorLauncherCheck(
    'Bootstrap script path is correct.',
    (
        $calls[0][
            'script_path'
        ]
        ?? null
    )
    ===
    $projectRoot
    . DIRECTORY_SEPARATOR
    . 'cron'
    . DIRECTORY_SEPARATOR
    . 'updateFPLData.php'
);


dataUpdateCoordinatorLauncherCheck(
    'Bootstrap has no CLI arguments.',
    (
        $calls[0][
            'arguments'
        ]
        ?? null
    )
    ===
    []
);


/*
 * ============================================================
 * FIXTURE WIRING
 * ============================================================
 */

dataUpdateCoordinatorLauncherCheck(
    'Fixture update type is correct.',
    (
        $calls[1][
            'update_type'
        ]
        ?? null
    )
    ===
    'fixtures'
);


dataUpdateCoordinatorLauncherCheck(
    'Fixture script path is correct.',
    (
        $calls[1][
            'script_path'
        ]
        ?? null
    )
    ===
    $projectRoot
    . DIRECTORY_SEPARATOR
    . 'cron'
    . DIRECTORY_SEPARATOR
    . 'updateFixtures.php'
);


dataUpdateCoordinatorLauncherCheck(
    'Fixture update has no CLI arguments.',
    (
        $calls[1][
            'arguments'
        ]
        ?? null
    )
    ===
    []
);


/*
 * ============================================================
 * PLAYER FIXTURE-HISTORY WIRING
 * ============================================================
 */

dataUpdateCoordinatorLauncherCheck(
    'Player fixture-history update type is correct.',
    (
        $calls[2][
            'update_type'
        ]
        ?? null
    )
    ===
    'player_fixture_history'
);


dataUpdateCoordinatorLauncherCheck(
    'Player fixture-history script path is correct.',
    (
        $calls[2][
            'script_path'
        ]
        ?? null
    )
    ===
    $projectRoot
    . DIRECTORY_SEPARATOR
    . 'cron'
    . DIRECTORY_SEPARATOR
    . 'updatePlayerFixtureHistory.php'
);


dataUpdateCoordinatorLauncherCheck(
    'Player fixture-history uses full import mode.',
    (
        $calls[2][
            'arguments'
        ]
        ?? null
    )
    ===
    [
        '--full'
    ]
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

dataUpdateCoordinatorLauncherSummary();