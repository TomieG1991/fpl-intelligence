<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Update Run Lifecycle Service Test<br>";
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

function updateRunLifecycleCheck(
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


function updateRunLifecycleSection(
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


function updateRunLifecycleSummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Update Run Lifecycle Service Test Summary<br>";
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
    'test_lifecycle_success';


$failedType =
    'test_lifecycle_failed';
    
    
$partialType =
    'test_lifecycle_partial';


/*
 * ============================================================
 * PRE-TEST CLEANUP
 * ============================================================
 */

$cleanupStatement =
    $db->prepare(
        "
        DELETE FROM update_runs
        WHERE update_type IN (
            :success_type,
            :failed_type,
            :partial_type
        )
        "
    );


$cleanupStatement
    ->execute(
        [
            'success_type' =>
                $successType,

            'failed_type' =>
                $failedType,

            'partial_type' =>
                $partialType
        ]
    );


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

updateRunLifecycleSection(
    'Scenario A: Class Contract'
);


updateRunLifecycleCheck(
    'UpdateRunLifecycleService exists.',
    class_exists(
        'UpdateRunLifecycleService'
    )
);


if (
    !class_exists(
        'UpdateRunLifecycleService'
    )
) {

    updateRunLifecycleSummary();

    exit;
}


/*
 * ============================================================
 * SERVICE
 * ============================================================
 */

$service =
    new UpdateRunLifecycleService(
        $repository
    );


/*
 * ============================================================
 * SCENARIO B
 * START RUN
 * ============================================================
 */

updateRunLifecycleSection(
    'Scenario B: Start Run'
);


$runId =
    $service
        ->start(
            $successType,
            '2026-09-14 12:00:00'
        );


updateRunLifecycleCheck(
    'Starting lifecycle returns a positive run ID.',
    is_int(
        $runId
    )
    &&
    $runId > 0
);


$running =
    $repository
        ->getById(
            $runId
        );


updateRunLifecycleCheck(
    'Started lifecycle persists Running status.',
    (
        $running[
            'status'
        ]
        ?? null
    )
    ===
    'Running'
);


updateRunLifecycleCheck(
    'Started lifecycle preserves update type.',
    (
        $running[
            'update_type'
        ]
        ?? null
    )
    ===
    $successType
);


/*
 * ============================================================
 * SCENARIO C
 * COMPLETE SUCCESS
 * ============================================================
 */

updateRunLifecycleSection(
    'Scenario C: Complete Success'
);


$successResult =
    $service
        ->succeed(
            $runId,
            '2026-09-14 12:00:05',
            630,
            625,
            5,
            5000
        );


updateRunLifecycleCheck(
    'Successful lifecycle completion returns true.',
    $successResult === true
);


$successful =
    $repository
        ->getById(
            $runId
        );


updateRunLifecycleCheck(
    'Successful lifecycle stores Success status.',
    (
        $successful[
            'status'
        ]
        ?? null
    )
    ===
    'Success'
);


updateRunLifecycleCheck(
    'Successful lifecycle preserves received count.',
    (
        $successful[
            'records_received'
        ]
        ?? null
    )
    ===
    630
);


updateRunLifecycleCheck(
    'Successful lifecycle preserves updated count.',
    (
        $successful[
            'records_updated'
        ]
        ?? null
    )
    ===
    625
);


updateRunLifecycleCheck(
    'Successful lifecycle preserves skipped count.',
    (
        $successful[
            'records_skipped'
        ]
        ?? null
    )
    ===
    5
);


updateRunLifecycleCheck(
    'Successful lifecycle stores zero failed records.',
    (
        $successful[
            'records_failed'
        ]
        ?? null
    )
    ===
    0
);


updateRunLifecycleCheck(
    'Successful lifecycle preserves duration.',
    (
        $successful[
            'duration_ms'
        ]
        ?? null
    )
    ===
    5000
);


updateRunLifecycleCheck(
    'Successful lifecycle stores no error message.',
    array_key_exists(
        'error_message',
        $successful
    )
    &&
    $successful[
        'error_message'
    ]
    ===
    null
);


/*
 * ============================================================
 * SCENARIO D
 * COMPLETE FAILURE
 * ============================================================
 */

updateRunLifecycleSection(
    'Scenario D: Complete Failure'
);


$failedRunId =
    $service
        ->start(
            $failedType,
            '2026-09-14 12:10:00'
        );


$failureResult =
    $service
        ->fail(
            $failedRunId,
            '2026-09-14 12:10:03',
            0,
            0,
            0,
            1,
            3000,
            'FPL API unavailable.'
        );


updateRunLifecycleCheck(
    'Failed lifecycle completion returns true.',
    $failureResult === true
);


$failedRun =
    $repository
        ->getById(
            $failedRunId
        );


updateRunLifecycleCheck(
    'Failed lifecycle stores Failed status.',
    (
        $failedRun[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


updateRunLifecycleCheck(
    'Failed lifecycle preserves failed record count.',
    (
        $failedRun[
            'records_failed'
        ]
        ?? null
    )
    ===
    1
);


updateRunLifecycleCheck(
    'Failed lifecycle preserves duration.',
    (
        $failedRun[
            'duration_ms'
        ]
        ?? null
    )
    ===
    3000
);


updateRunLifecycleCheck(
    'Failed lifecycle preserves error message.',
    (
        $failedRun[
            'error_message'
        ]
        ?? null
    )
    ===
    'FPL API unavailable.'
);


/*
 * ============================================================
 * SCENARIO E
 * COMPLETE PARTIAL
 * ============================================================
 */

updateRunLifecycleSection(
    'Scenario E: Complete Partial'
);


$partialRunId =
    $service
        ->start(
            $partialType,
            '2026-09-14 12:20:00'
        );


$partialResult =
    $service
        ->partial(
            $partialRunId,
            '2026-09-14 12:20:08',
            25,
            22,
            1,
            2,
            8000,
            'Two player summary requests failed.'
        );


updateRunLifecycleCheck(
    'Partial lifecycle completion returns true.',
    $partialResult === true
);


$partialRun =
    $repository
        ->getById(
            $partialRunId
        );


updateRunLifecycleCheck(
    'Partial lifecycle stores Partial status.',
    (
        $partialRun[
            'status'
        ]
        ?? null
    )
    ===
    'Partial'
);


updateRunLifecycleCheck(
    'Partial lifecycle preserves received count.',
    (
        $partialRun[
            'records_received'
        ]
        ?? null
    )
    ===
    25
);


updateRunLifecycleCheck(
    'Partial lifecycle preserves updated count.',
    (
        $partialRun[
            'records_updated'
        ]
        ?? null
    )
    ===
    22
);


updateRunLifecycleCheck(
    'Partial lifecycle preserves skipped count.',
    (
        $partialRun[
            'records_skipped'
        ]
        ?? null
    )
    ===
    1
);


updateRunLifecycleCheck(
    'Partial lifecycle preserves failed record count.',
    (
        $partialRun[
            'records_failed'
        ]
        ?? null
    )
    ===
    2
);


updateRunLifecycleCheck(
    'Partial lifecycle preserves duration.',
    (
        $partialRun[
            'duration_ms'
        ]
        ?? null
    )
    ===
    8000
);


updateRunLifecycleCheck(
    'Partial lifecycle preserves error message.',
    (
        $partialRun[
            'error_message'
        ]
        ?? null
    )
    ===
    'Two player summary requests failed.'
);


/*
 * ============================================================
 * SCENARIO F
 * CLEANUP
 * ============================================================
 */

updateRunLifecycleSection(
    'Scenario F: Cleanup'
);


$cleanupStatement
    ->execute(
        [
            'success_type' =>
                $successType,

            'failed_type' =>
                $failedType,

            'partial_type' =>
                $partialType
        ]
    );


$remainingStatement =
    $db->prepare(
        "
        SELECT COUNT(*)
        FROM update_runs
        WHERE update_type IN (
            :success_type,
            :failed_type,
            :partial_type
        )
        "
    );


$remainingStatement
    ->execute(
        [
            'success_type' =>
                $successType,

            'failed_type' =>
                $failedType,

            'partial_type' =>
                $partialType
        ]
    );


$remaining =
    (int) $remainingStatement
        ->fetchColumn();


updateRunLifecycleCheck(
    'Synthetic lifecycle runs are removed after test.',
    $remaining === 0
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

updateRunLifecycleSummary();