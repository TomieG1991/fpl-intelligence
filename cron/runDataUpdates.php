<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * FPL INTELLIGENCE DATA UPDATE COORDINATOR
 * ============================================================
 *
 * Production entry point for the controlled FPL data refresh.
 *
 * Update order:
 *
 * 1. Bootstrap data
 * 2. Fixtures
 * 3. Player fixture history
 *
 * Each updater executes in its own PHP CLI process.
 */


set_time_limit(
    0
);


try {

    /*
     * ========================================================
     * PROJECT
     * ========================================================
     */

    $projectRoot =
        dirname(
            __DIR__
        );


    /*
     * ========================================================
     * DATABASE
     * ========================================================
     */

    $database =
        new Database();


    $db =
        $database->getConnection();


    $updateRunRepository =
        new UpdateRunRepository(
            $db
        );


    /*
     * ========================================================
     * PHP CLI
     * ========================================================
     */

    $phpCliLocator =
        new PhpCliExecutableLocator();


        $phpExecutable =
        $phpCliLocator->locate(
            PHP_BINARY,
            PHP_MAJOR_VERSION
            . '.'
            . PHP_MINOR_VERSION
            . '.'
            . PHP_RELEASE_VERSION
        );


    /*
     * ========================================================
     * PROCESS EXECUTION
     * ========================================================
     */

    $processExecutor =
        new PhpCliProcessExecutor(
            $phpExecutable
        );


    $processRunner =
        new DataUpdateProcessRunner(
            $updateRunRepository,
            $processExecutor
        );


    /*
     * ========================================================
     * COORDINATOR LAUNCHER
     * ========================================================
     */

    $launcher =
        new DataUpdateCoordinatorLauncher(

            $projectRoot,

            static function (
                string $updateType,
                string $scriptPath,
                array $arguments = []
            ) use (
                $processRunner
            ): array {

                return $processRunner->run(
                    $updateType,
                    $scriptPath,
                    $arguments
                );
            }
        );


    /*
     * ========================================================
     * RUN
     * ========================================================
     */

    $result =
        $launcher->run();


    /*
     * ========================================================
     * OUTPUT
     * ========================================================
     */

    echo "============================================\n";
    echo "FPL Intelligence Data Update\n";
    echo "============================================\n\n";


    echo "Overall Status: "
        . (
            $result['status']
            ?? 'Unknown'
        )
        . "\n\n";


    $steps =
        $result['steps']
        ?? [];


    foreach (
        $steps
        as $updateType =>
            $stepResult
    ) {

        echo $updateType
            . ': '
            . (
                $stepResult['status']
                ?? 'Unknown'
            )
            . "\n";


        $errorMessage =
            trim(
                (string) (
                    $stepResult[
                        'error_message'
                    ]
                    ?? ''
                )
            );


        if ($errorMessage !== '') {

            echo 'Error: '
                . $errorMessage
                . "\n";
        }
    }


    echo "\n";


    if (
        (
            $result['status']
            ?? null
        )
        ===
        'Success'
    ) {

        echo "RESULT: DATA UPDATE PASSED ✅\n";

    } elseif (
        (
            $result['status']
            ?? null
        )
        ===
        'Partial'
    ) {

        echo "RESULT: DATA UPDATE PARTIAL ⚠️\n";

        } else {

        echo "RESULT: DATA UPDATE FAILED ❌\n";

        exit(1);
    }


} catch (Throwable $exception) {

    echo "============================================\n";
    echo "FPL Intelligence Data Update\n";
    echo "============================================\n\n";

    echo "Overall Status: Failed\n\n";

    echo "Error: "
        . $exception->getMessage()
        . "\n\n";

        echo "RESULT: DATA UPDATE FAILED ❌\n";

    exit(1);
}