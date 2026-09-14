<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Data Update Process Runner Test<br>";
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

function dataUpdateProcessRunnerCheck(
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


function dataUpdateProcessRunnerSection(
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


function dataUpdateProcessRunnerSummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Data Update Process Runner Test Summary<br>";
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
 * DATABASE
 * ============================================================
 */

$database =
    new Database();


$db =
    $database
        ->getConnection();


$repository =
    new UpdateRunRepository(
        $db
    );


/*
 * ============================================================
 * SYNTHETIC UPDATE TYPES
 * ============================================================
 */

$successType =
    'test_process_runner_success';

$partialType =
    'test_process_runner_partial';

$failedType =
    'test_process_runner_failed';

$staleType =
    'test_process_runner_stale';

$exceptionType =
    'test_process_runner_exception';

$exitCodeType =
    'test_process_runner_exit_code';

$runningType =
    'test_process_runner_running';


$testTypes = [

    $successType,
    $partialType,
    $failedType,
    $staleType,
    $exceptionType,
    $exitCodeType,
    $runningType
];


/*
 * ============================================================
 * CLEANUP
 * ============================================================
 */

$placeholders =
    implode(
        ', ',
        array_fill(
            0,
            count(
                $testTypes
            ),
            '?'
        )
    );


$cleanupStatement =
    $db->prepare(
        "
        DELETE FROM update_runs
        WHERE update_type IN (
            $placeholders
        )
        "
    );


$cleanupStatement->execute(
    $testTypes
);


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

dataUpdateProcessRunnerSection(
    'Scenario A: Class Contract'
);


dataUpdateProcessRunnerCheck(
    'DataUpdateProcessRunner exists.',
    class_exists(
        'DataUpdateProcessRunner'
    )
);


/*
 * ============================================================
 * SCENARIO B
 * SUCCESSFUL UPDATE RUN
 * ============================================================
 */

dataUpdateProcessRunnerSection(
    'Scenario B: Successful Update Run'
);


$capturedScript =
    null;

$capturedArguments =
    null;


$successExecutor =
    function (
        string $scriptPath,
        array $arguments
    ) use (
        &$capturedScript,
        &$capturedArguments,
        $repository,
        $successType
    ): array {

        $capturedScript =
            $scriptPath;

        $capturedArguments =
            $arguments;


        $runId =
            $repository->start(
                $successType,
                '2026-09-14 14:00:00'
            );


        $repository->complete(
            $runId,
            'Success',
            '2026-09-14 14:00:01',
            100,
            95,
            5,
            0,
            1000,
            null
        );


        return [

            'exit_code' =>
                0,

            'stdout' =>
                'Synthetic success.',

            'stderr' =>
                ''
        ];
    };


$runner =
    new DataUpdateProcessRunner(
        $repository,
        $successExecutor
    );


$successResult =
    $runner->run(
        $successType,
        'syntheticSuccess.php',
        [
            '--full'
        ]
    );


dataUpdateProcessRunnerCheck(
    'Success executor receives script path.',
    $capturedScript
    ===
    'syntheticSuccess.php'
);


dataUpdateProcessRunnerCheck(
    'Success executor receives arguments.',
    $capturedArguments
    ===
    [
        '--full'
    ]
);


dataUpdateProcessRunnerCheck(
    'Successful persisted run is returned.',
    (
        $successResult[
            'status'
        ]
        ?? null
    )
    ===
    'Success'
);


dataUpdateProcessRunnerCheck(
    'Successful result preserves update type.',
    (
        $successResult[
            'update_type'
        ]
        ?? null
    )
    ===
    $successType
);


dataUpdateProcessRunnerCheck(
    'Successful result preserves received count.',
    (
        $successResult[
            'records_received'
        ]
        ?? null
    )
    ===
    100
);


dataUpdateProcessRunnerCheck(
    'Successful result preserves updated count.',
    (
        $successResult[
            'records_updated'
        ]
        ?? null
    )
    ===
    95
);


/*
 * ============================================================
 * SCENARIO C
 * PARTIAL UPDATE RUN
 * ============================================================
 */

dataUpdateProcessRunnerSection(
    'Scenario C: Partial Update Run'
);


$partialExecutor =
    function (
        string $scriptPath,
        array $arguments
    ) use (
        $repository,
        $partialType
    ): array {

        $runId =
            $repository->start(
                $partialType,
                '2026-09-14 14:01:00'
            );


        $repository->complete(
            $runId,
            'Partial',
            '2026-09-14 14:01:02',
            20,
            18,
            0,
            2,
            2000,
            'Two synthetic players failed.'
        );


        return [

            'exit_code' =>
                0,

            'stdout' =>
                'Synthetic partial.',

            'stderr' =>
                ''
        ];
    };


$runner =
    new DataUpdateProcessRunner(
        $repository,
        $partialExecutor
    );


$partialResult =
    $runner->run(
        $partialType,
        'syntheticPartial.php'
    );


dataUpdateProcessRunnerCheck(
    'Partial persisted run is returned as Partial.',
    (
        $partialResult[
            'status'
        ]
        ?? null
    )
    ===
    'Partial'
);


dataUpdateProcessRunnerCheck(
    'Partial result preserves failed count.',
    (
        $partialResult[
            'records_failed'
        ]
        ?? null
    )
    ===
    2
);


dataUpdateProcessRunnerCheck(
    'Partial result preserves error message.',
    (
        $partialResult[
            'error_message'
        ]
        ?? null
    )
    ===
    'Two synthetic players failed.'
);


/*
 * ============================================================
 * SCENARIO D
 * FAILED UPDATE RUN
 * ============================================================
 */

dataUpdateProcessRunnerSection(
    'Scenario D: Failed Update Run'
);


$failedExecutor =
    function (
        string $scriptPath,
        array $arguments
    ) use (
        $repository,
        $failedType
    ): array {

        $runId =
            $repository->start(
                $failedType,
                '2026-09-14 14:02:00'
            );


        $repository->complete(
            $runId,
            'Failed',
            '2026-09-14 14:02:01',
            50,
            0,
            0,
            50,
            1000,
            'Synthetic updater failure.'
        );


        return [

            'exit_code' =>
                0,

            'stdout' =>
                'Synthetic failed update.',

            'stderr' =>
                ''
        ];
    };


$runner =
    new DataUpdateProcessRunner(
        $repository,
        $failedExecutor
    );


$failedResult =
    $runner->run(
        $failedType,
        'syntheticFailed.php'
    );


dataUpdateProcessRunnerCheck(
    'Failed persisted run is returned as Failed.',
    (
        $failedResult[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataUpdateProcessRunnerCheck(
    'Failed result preserves updater error message.',
    (
        $failedResult[
            'error_message'
        ]
        ?? null
    )
    ===
    'Synthetic updater failure.'
);


/*
 * ============================================================
 * SCENARIO E
 * STALE RUN PROTECTION
 * ============================================================
 */

dataUpdateProcessRunnerSection(
    'Scenario E: Stale Run Protection'
);


$staleRunId =
    $repository->start(
        $staleType,
        '2026-09-14 14:03:00'
    );


$repository->complete(
    $staleRunId,
    'Success',
    '2026-09-14 14:03:01',
    10,
    10,
    0,
    0,
    1000,
    null
);


$staleExecutor =
    static function (
        string $scriptPath,
        array $arguments
    ): array {

        /*
         * Deliberately records no new update run.
         */

        return [

            'exit_code' =>
                0,

            'stdout' =>
                'Process ended without lifecycle record.',

            'stderr' =>
                ''
        ];
    };


$runner =
    new DataUpdateProcessRunner(
        $repository,
        $staleExecutor
    );


$staleResult =
    $runner->run(
        $staleType,
        'syntheticStale.php'
    );


dataUpdateProcessRunnerCheck(
    'Existing stale Success is not reused.',
    (
        $staleResult[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataUpdateProcessRunnerCheck(
    'Missing new run reports explicit lifecycle error.',
    (
        $staleResult[
            'error_message'
        ]
        ?? null
    )
    ===
    'Updater process completed without recording a new update run.'
);


/*
 * ============================================================
 * SCENARIO F
 * PROCESS EXECUTOR THROWS
 * ============================================================
 */

dataUpdateProcessRunnerSection(
    'Scenario F: Process Executor Throws'
);


$exceptionExecutor =
    static function (
        string $scriptPath,
        array $arguments
    ): array {

        throw new RuntimeException(
            'Synthetic process launch failure.'
        );
    };


$runner =
    new DataUpdateProcessRunner(
        $repository,
        $exceptionExecutor
    );


$exceptionResult =
    $runner->run(
        $exceptionType,
        'syntheticException.php'
    );


dataUpdateProcessRunnerCheck(
    'Process exception returns Failed.',
    (
        $exceptionResult[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataUpdateProcessRunnerCheck(
    'Process exception message is preserved.',
    (
        $exceptionResult[
            'error_message'
        ]
        ?? null
    )
    ===
    'Synthetic process launch failure.'
);


/*
 * ============================================================
 * SCENARIO G
 * NON-ZERO EXIT WITHOUT NEW RUN
 * ============================================================
 */

dataUpdateProcessRunnerSection(
    'Scenario G: Non-Zero Exit Without New Run'
);


$exitCodeExecutor =
    static function (
        string $scriptPath,
        array $arguments
    ): array {

        return [

            'exit_code' =>
                1,

            'stdout' =>
                '',

            'stderr' =>
                'Synthetic PHP process failure.'
        ];
    };


$runner =
    new DataUpdateProcessRunner(
        $repository,
        $exitCodeExecutor
    );


$exitCodeResult =
    $runner->run(
        $exitCodeType,
        'syntheticExitCode.php'
    );


dataUpdateProcessRunnerCheck(
    'Non-zero process exit returns Failed.',
    (
        $exitCodeResult[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataUpdateProcessRunnerCheck(
    'Non-zero process exit preserves stderr.',
    (
        $exitCodeResult[
            'error_message'
        ]
        ?? null
    )
    ===
    'Synthetic PHP process failure.'
);


/*
 * ============================================================
 * SCENARIO H
 * NEW RUN LEFT RUNNING
 * ============================================================
 */

dataUpdateProcessRunnerSection(
    'Scenario H: New Run Left Running'
);


$runningExecutor =
    function (
        string $scriptPath,
        array $arguments
    ) use (
        $repository,
        $runningType
    ): array {

        $repository->start(
            $runningType,
            '2026-09-14 14:04:00'
        );


        return [

            'exit_code' =>
                0,

            'stdout' =>
                'Synthetic incomplete updater.',

            'stderr' =>
                ''
        ];
    };


$runner =
    new DataUpdateProcessRunner(
        $repository,
        $runningExecutor
    );


$runningResult =
    $runner->run(
        $runningType,
        'syntheticRunning.php'
    );


dataUpdateProcessRunnerCheck(
    'Incomplete Running lifecycle is returned as Failed.',
    (
        $runningResult[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataUpdateProcessRunnerCheck(
    'Incomplete lifecycle reports explicit error.',
    (
        $runningResult[
            'error_message'
        ]
        ?? null
    )
    ===
    'Updater process ended before its update run was completed.'
);


/*
 * ============================================================
 * POST-TEST CLEANUP
 * ============================================================
 */

$cleanupStatement->execute(
    $testTypes
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

dataUpdateProcessRunnerSummary();