<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * FPL INTELLIGENCE HISTORICAL EVIDENCE LIFECYCLE
 * ============================================================
 *
 * Production entry point for the controlled historical-evidence
 * lifecycle.
 *
 * Order:
 *
 * 1. Promote eligible recommendation candidates.
 * 2. Promote eligible player snapshot candidates.
 * 3. Capture latest player state for the next deadline.
 *
 * Recommendation candidate capture is deliberately excluded.
 * That operation requires genuine manager-specific production
 * recommendation evidence.
 *
 * This lifecycle deliberately remains separate from the live
 * DataUpdateCoordinator.
 */


set_time_limit(
    0
);


try {

    /*
     * ========================================================
     * PRODUCTION LAUNCHER
     * ========================================================
     *
     * Automated tests may inject a controlled launcher through
     * $historicalEvidenceLifecycleCronLauncher.
     *
     * Normal production execution does not define that variable
     * and therefore constructs the real production dependency
     * chain below.
     */

    if (
        !isset(
            $historicalEvidenceLifecycleCronLauncher
        )
    ) {

        /*
         * ====================================================
         * PROJECT
         * ====================================================
         */

        $projectRoot =
            dirname(
                __DIR__
            );


        /*
         * ====================================================
         * PHP CLI
         * ====================================================
         */

        $phpCliLocator =
            new PhpCliExecutableLocator();


        $phpExecutable =
            $phpCliLocator->locate(
                'C:\\wamp64\\bin\\php',
                PHP_MAJOR_VERSION
                . '.'
                . PHP_MINOR_VERSION
                . '.'
                . PHP_RELEASE_VERSION
            );


        /*
         * ====================================================
         * PROCESS EXECUTION
         * ====================================================
         */

        $processExecutor =
            new PhpCliProcessExecutor(
                $phpExecutable
            );


        $processRunner =
            new HistoricalEvidenceProcessRunner(
                $processExecutor
            );


        /*
         * ====================================================
         * LIFECYCLE LAUNCHER
         * ====================================================
         */

        $historicalEvidenceLifecycleCronLauncher =
            new HistoricalEvidenceLifecycleLauncher(

                $projectRoot,

                static function (
                    string $stepName,
                    string $scriptPath,
                    array $arguments = []
                ) use (
                    $processRunner
                ): array {

                    return $processRunner->run(
                        $stepName,
                        $scriptPath,
                        $arguments
                    );
                }
            );
    }


    /*
     * ========================================================
     * RUN
     * ========================================================
     */

    $result =
        $historicalEvidenceLifecycleCronLauncher
            ->run();


    if (
        !is_array(
            $result
        )
    ) {

        throw new RuntimeException(
            'Historical evidence lifecycle did not return a valid result.'
        );
    }


    /*
     * ========================================================
     * OUTPUT
     * ========================================================
     */

    echo "============================================\n";
    echo "FPL Intelligence Historical Evidence Lifecycle\n";
    echo "============================================\n\n";


    $status =
        (string) (
            $result[
                'status'
            ]
            ?? 'Failed'
        );


    echo "Overall Status: "
        . $status
        . "\n\n";


    $steps =
        $result[
            'steps'
        ]
        ?? [];


    foreach (
        $steps
        as $stepName =>
            $stepResult
    ) {

        echo $stepName
            . ': '
            . (
                $stepResult[
                    'status'
                ]
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


    /*
     * ========================================================
     * FINAL RESULT
     * ========================================================
     */

    if ($status === 'Success') {

        echo "RESULT: HISTORICAL EVIDENCE LIFECYCLE PASSED ✅\n";

    } else {

        echo "RESULT: HISTORICAL EVIDENCE LIFECYCLE FAILED ❌\n";

        exit(1);
    }


} catch (
    Throwable $exception
) {

    echo "============================================\n";
    echo "FPL Intelligence Historical Evidence Lifecycle\n";
    echo "============================================\n\n";

    echo "Overall Status: Failed\n\n";

    echo "Error: "
        . $exception->getMessage()
        . "\n\n";

    echo "RESULT: HISTORICAL EVIDENCE LIFECYCLE FAILED ❌\n";


    exit(1);
}