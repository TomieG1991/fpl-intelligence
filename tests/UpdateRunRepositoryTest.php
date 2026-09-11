<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Update Run Repository Test<br>";
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

function updateRunRepositoryCheck(
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


function updateRunRepositorySection(
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


function updateRunRepositorySummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Update Run Repository Test Summary<br>";
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


/*
 * ============================================================
 * TEST UPDATE TYPES
 * ============================================================
 *
 * Synthetic update types are used so this test cannot interfere
 * with genuine production update history.
 */

$bootstrapType =
    'test_bootstrap_repository';


$fixtureType =
    'test_fixtures_repository';


/*
 * ============================================================
 * PRE-TEST CLEANUP
 * ============================================================
 *
 * The first RED may occur before update_runs exists.
 *
 * Cleanup therefore deliberately tolerates a missing table so
 * the test can continue to the repository class contract.
 */

try {

    $cleanupStatement =
        $db->prepare(
            "
            DELETE FROM update_runs
            WHERE update_type IN (
                :bootstrap_type,
                :fixture_type
            )
            "
        );


    $cleanupStatement
        ->execute(
            [
                'bootstrap_type' =>
                    $bootstrapType,

                'fixture_type' =>
                    $fixtureType
            ]
        );

} catch (
    Throwable $exception
) {

    /*
     * Expected during the first RED before the update_runs
     * persistence table exists.
     */
}


/*
 * ============================================================
 * SCENARIO A
 * REPOSITORY EXISTS
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario A: Repository Exists'
);


updateRunRepositoryCheck(
    'UpdateRunRepository exists.',
    class_exists(
        'UpdateRunRepository'
    )
);


if (
    !class_exists(
        'UpdateRunRepository'
    )
) {

    updateRunRepositorySummary();

    exit;
}


/*
 * ============================================================
 * REPOSITORY
 * ============================================================
 */

$repository =
    new UpdateRunRepository(
        $db
    );


/*
 * ============================================================
 * CONTROLLED TIMESTAMPS
 * ============================================================
 */

$startedOne =
    '2026-09-11 12:00:00';


$completedOne =
    '2026-09-11 12:00:04';


$startedTwo =
    '2026-09-11 12:05:00';


$completedTwo =
    '2026-09-11 12:05:07';


$startedThree =
    '2026-09-11 12:10:00';


$completedThree =
    '2026-09-11 12:10:03';


$startedFixture =
    '2026-09-11 12:15:00';


/*
 * ============================================================
 * SCENARIO B
 * START RUN
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario B: Start Run'
);


$runIdOne =
    $repository
        ->start(
            $bootstrapType,
            $startedOne
        );


updateRunRepositoryCheck(
    'Starting a run returns a positive integer run ID.',
    is_int(
        $runIdOne
    )
    &&
    $runIdOne > 0
);


$startedRun =
    $repository
        ->getById(
            $runIdOne
        );


updateRunRepositoryCheck(
    'Started run can be retrieved by ID.',
    is_array(
        $startedRun
    )
);


updateRunRepositoryCheck(
    'Started run preserves its repository ID.',
    (
        $startedRun[
            'id'
        ]
        ?? null
    )
    ===
    $runIdOne
);


updateRunRepositoryCheck(
    'Started run preserves update type.',
    (
        $startedRun[
            'update_type'
        ]
        ?? null
    )
    ===
    $bootstrapType
);


updateRunRepositoryCheck(
    'New run starts with Running status.',
    (
        $startedRun[
            'status'
        ]
        ?? null
    )
    ===
    'Running'
);


updateRunRepositoryCheck(
    'Started run preserves start timestamp.',
    (
        $startedRun[
            'started_at'
        ]
        ?? null
    )
    ===
    $startedOne
);


updateRunRepositoryCheck(
    'Running run has null completion timestamp.',
    array_key_exists(
        'completed_at',
        $startedRun
    )
    &&
    $startedRun[
        'completed_at'
    ]
    ===
    null
);


updateRunRepositoryCheck(
    'Running run starts with zero received records.',
    (
        $startedRun[
            'records_received'
        ]
        ?? null
    )
    ===
    0
);


updateRunRepositoryCheck(
    'Running run starts with zero updated records.',
    (
        $startedRun[
            'records_updated'
        ]
        ?? null
    )
    ===
    0
);


updateRunRepositoryCheck(
    'Running run starts with zero skipped records.',
    (
        $startedRun[
            'records_skipped'
        ]
        ?? null
    )
    ===
    0
);


updateRunRepositoryCheck(
    'Running run starts with zero failed records.',
    (
        $startedRun[
            'records_failed'
        ]
        ?? null
    )
    ===
    0
);


updateRunRepositoryCheck(
    'Running run has null duration.',
    array_key_exists(
        'duration_ms',
        $startedRun
    )
    &&
    $startedRun[
        'duration_ms'
    ]
    ===
    null
);


updateRunRepositoryCheck(
    'Running run has null error message.',
    array_key_exists(
        'error_message',
        $startedRun
    )
    &&
    $startedRun[
        'error_message'
    ]
    ===
    null
);


/*
 * ============================================================
 * SCENARIO C
 * COMPLETE SUCCESS
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario C: Complete Success'
);


$successResult =
    $repository
        ->complete(
            $runIdOne,
            'Success',
            $completedOne,
            700,
            700,
            0,
            0,
            4123,
            null
        );


updateRunRepositoryCheck(
    'Successful run completion returns true.',
    $successResult === true
);


$successfulRun =
    $repository
        ->getById(
            $runIdOne
        );


updateRunRepositoryCheck(
    'Successful run stores Success status.',
    (
        $successfulRun[
            'status'
        ]
        ?? null
    )
    ===
    'Success'
);


updateRunRepositoryCheck(
    'Successful run preserves completion timestamp.',
    (
        $successfulRun[
            'completed_at'
        ]
        ?? null
    )
    ===
    $completedOne
);


updateRunRepositoryCheck(
    'Successful run preserves received count.',
    (
        $successfulRun[
            'records_received'
        ]
        ?? null
    )
    ===
    700
);


updateRunRepositoryCheck(
    'Successful run preserves updated count.',
    (
        $successfulRun[
            'records_updated'
        ]
        ?? null
    )
    ===
    700
);


updateRunRepositoryCheck(
    'Successful run preserves zero skipped count.',
    (
        $successfulRun[
            'records_skipped'
        ]
        ?? null
    )
    ===
    0
);


updateRunRepositoryCheck(
    'Successful run preserves zero failed count.',
    (
        $successfulRun[
            'records_failed'
        ]
        ?? null
    )
    ===
    0
);


updateRunRepositoryCheck(
    'Successful run preserves duration in milliseconds.',
    (
        $successfulRun[
            'duration_ms'
        ]
        ?? null
    )
    ===
    4123
);


updateRunRepositoryCheck(
    'Successful run preserves null error message.',
    array_key_exists(
        'error_message',
        $successfulRun
    )
    &&
    $successfulRun[
        'error_message'
    ]
    ===
    null
);


/*
 * ============================================================
 * SCENARIO D
 * COMPLETE PARTIAL
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario D: Complete Partial'
);


$runIdTwo =
    $repository
        ->start(
            $bootstrapType,
            $startedTwo
        );


$partialResult =
    $repository
        ->complete(
            $runIdTwo,
            'Partial',
            $completedTwo,
            700,
            695,
            3,
            2,
            7100,
            'Two player records failed during update.'
        );


updateRunRepositoryCheck(
    'Partial run completion returns true.',
    $partialResult === true
);


$partialRun =
    $repository
        ->getById(
            $runIdTwo
        );


updateRunRepositoryCheck(
    'Partial run stores Partial status.',
    (
        $partialRun[
            'status'
        ]
        ?? null
    )
    ===
    'Partial'
);


updateRunRepositoryCheck(
    'Partial run preserves received count.',
    (
        $partialRun[
            'records_received'
        ]
        ?? null
    )
    ===
    700
);


updateRunRepositoryCheck(
    'Partial run preserves updated count.',
    (
        $partialRun[
            'records_updated'
        ]
        ?? null
    )
    ===
    695
);


updateRunRepositoryCheck(
    'Partial run preserves skipped count.',
    (
        $partialRun[
            'records_skipped'
        ]
        ?? null
    )
    ===
    3
);


updateRunRepositoryCheck(
    'Partial run preserves failed count.',
    (
        $partialRun[
            'records_failed'
        ]
        ?? null
    )
    ===
    2
);


updateRunRepositoryCheck(
    'Partial run preserves explanatory error message.',
    (
        $partialRun[
            'error_message'
        ]
        ?? null
    )
    ===
    'Two player records failed during update.'
);


/*
 * ============================================================
 * SCENARIO E
 * COMPLETE FAILED
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario E: Complete Failed'
);


$runIdThree =
    $repository
        ->start(
            $bootstrapType,
            $startedThree
        );


$failedResult =
    $repository
        ->complete(
            $runIdThree,
            'Failed',
            $completedThree,
            0,
            0,
            0,
            1,
            3020,
            'FPL API unavailable.'
        );


updateRunRepositoryCheck(
    'Failed run completion returns true.',
    $failedResult === true
);


$failedRun =
    $repository
        ->getById(
            $runIdThree
        );


updateRunRepositoryCheck(
    'Failed run stores Failed status.',
    (
        $failedRun[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


updateRunRepositoryCheck(
    'Failed run preserves zero received records.',
    (
        $failedRun[
            'records_received'
        ]
        ?? null
    )
    ===
    0
);


updateRunRepositoryCheck(
    'Failed run preserves zero updated records.',
    (
        $failedRun[
            'records_updated'
        ]
        ?? null
    )
    ===
    0
);


updateRunRepositoryCheck(
    'Failed run preserves failed count.',
    (
        $failedRun[
            'records_failed'
        ]
        ?? null
    )
    ===
    1
);


updateRunRepositoryCheck(
    'Failed run preserves failure message.',
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
 * SCENARIO F
 * LATEST RUN BY TYPE
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario F: Latest Run By Type'
);


$latestBootstrap =
    $repository
        ->getLatestByType(
            $bootstrapType
        );


updateRunRepositoryCheck(
    'Latest bootstrap run can be retrieved.',
    is_array(
        $latestBootstrap
    )
);


updateRunRepositoryCheck(
    'Latest-by-type returns newest bootstrap run.',
    (
        $latestBootstrap[
            'id'
        ]
        ?? null
    )
    ===
    $runIdThree
);


$fixtureRunId =
    $repository
        ->start(
            $fixtureType,
            $startedFixture
        );


$latestFixture =
    $repository
        ->getLatestByType(
            $fixtureType
        );


updateRunRepositoryCheck(
    'Latest-by-type remains isolated between update types.',
    (
        $latestFixture[
            'id'
        ]
        ?? null
    )
    ===
    $fixtureRunId
);


$missingLatest =
    $repository
        ->getLatestByType(
            'test_nonexistent_update_type'
        );


updateRunRepositoryCheck(
    'Latest-by-type returns null when no run exists.',
    $missingLatest === null
);


/*
 * ============================================================
 * SCENARIO G
 * LATEST SUCCESSFUL RUN BY TYPE
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario G: Latest Successful Run By Type'
);


$latestSuccessfulBootstrap =
    $repository
        ->getLatestSuccessfulByType(
            $bootstrapType
        );


updateRunRepositoryCheck(
    'Latest successful bootstrap run can be retrieved.',
    is_array(
        $latestSuccessfulBootstrap
    )
);


updateRunRepositoryCheck(
    'Latest-successful-by-type ignores newer Partial and Failed runs.',
    (
        $latestSuccessfulBootstrap[
            'id'
        ]
        ?? null
    )
    ===
    $runIdOne
);


updateRunRepositoryCheck(
    'Latest-successful-by-type returns a Success run.',
    (
        $latestSuccessfulBootstrap[
            'status'
        ]
        ?? null
    )
    ===
    'Success'
);


updateRunRepositoryCheck(
    'Latest-successful-by-type preserves the successful completion timestamp.',
    (
        $latestSuccessfulBootstrap[
            'completed_at'
        ]
        ?? null
    )
    ===
    $completedOne
);


$latestSuccessfulFixture =
    $repository
        ->getLatestSuccessfulByType(
            $fixtureType
        );


updateRunRepositoryCheck(
    'Latest-successful-by-type remains isolated between update types.',
    $latestSuccessfulFixture === null
);


$emptySuccessfulTypeRejected =
    false;


try {

    $repository
        ->getLatestSuccessfulByType(
            ''
        );

} catch (
    InvalidArgumentException $exception
) {

    $emptySuccessfulTypeRejected =
        true;
}


updateRunRepositoryCheck(
    'getLatestSuccessfulByType rejects an empty update type.',
    $emptySuccessfulTypeRejected
);


/*
 * ============================================================
 * SCENARIO H
 * RECENT RUNS
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario H: Recent Runs'
);


$recentRuns =
    $repository
        ->getRecentByType(
            $bootstrapType,
            2
        );


updateRunRepositoryCheck(
    'Recent-by-type returns an array.',
    is_array(
        $recentRuns
    )
);


updateRunRepositoryCheck(
    'Recent-by-type respects supplied limit.',
    count(
        $recentRuns
    )
    ===
    2
);


updateRunRepositoryCheck(
    'Recent runs are ordered newest first.',
    (
        $recentRuns[
            0
        ][
            'id'
        ]
        ?? null
    )
    ===
    $runIdThree
);


updateRunRepositoryCheck(
    'Recent runs preserve deterministic descending order.',
    (
        $recentRuns[
            1
        ][
            'id'
        ]
        ?? null
    )
    ===
    $runIdTwo
);


/*
 * ============================================================
 * SCENARIO I
 * INVALID START INPUT
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario I: Invalid Start Input'
);


$invalidTypeRejected =
    false;


try {

    $repository
        ->start(
            '',
            $startedOne
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidTypeRejected =
        true;
}


updateRunRepositoryCheck(
    'Empty update type is rejected.',
    $invalidTypeRejected
);


$invalidStartTimestampRejected =
    false;


try {

    $repository
        ->start(
            $bootstrapType,
            'not-a-date'
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidStartTimestampRejected =
        true;
}


updateRunRepositoryCheck(
    'Invalid start timestamp is rejected.',
    $invalidStartTimestampRejected
);


/*
 * ============================================================
 * SCENARIO J
 * INVALID COMPLETE INPUT
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario J: Invalid Complete Input'
);


$invalidRunIdRejected =
    false;


try {

    $repository
        ->complete(
            0,
            'Success',
            $completedOne,
            0,
            0,
            0,
            0,
            0,
            null
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidRunIdRejected =
        true;
}


updateRunRepositoryCheck(
    'Non-positive run ID is rejected.',
    $invalidRunIdRejected
);


$invalidStatusRejected =
    false;


try {

    $repository
        ->complete(
            $runIdOne,
            'Unknown',
            $completedOne,
            0,
            0,
            0,
            0,
            0,
            null
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidStatusRejected =
        true;
}


updateRunRepositoryCheck(
    'Unsupported completion status is rejected.',
    $invalidStatusRejected
);


$runningCompletionRejected =
    false;


try {

    $repository
        ->complete(
            $runIdOne,
            'Running',
            $completedOne,
            0,
            0,
            0,
            0,
            0,
            null
        );

} catch (
    InvalidArgumentException $exception
) {

    $runningCompletionRejected =
        true;
}


updateRunRepositoryCheck(
    'Running cannot be used as a terminal completion status.',
    $runningCompletionRejected
);


$invalidCompletedTimestampRejected =
    false;


try {

    $repository
        ->complete(
            $runIdOne,
            'Success',
            'not-a-date',
            0,
            0,
            0,
            0,
            0,
            null
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidCompletedTimestampRejected =
        true;
}


updateRunRepositoryCheck(
    'Invalid completion timestamp is rejected.',
    $invalidCompletedTimestampRejected
);


$negativeReceivedRejected =
    false;


try {

    $repository
        ->complete(
            $runIdOne,
            'Success',
            $completedOne,
            -1,
            0,
            0,
            0,
            0,
            null
        );

} catch (
    InvalidArgumentException $exception
) {

    $negativeReceivedRejected =
        true;
}


updateRunRepositoryCheck(
    'Negative received count is rejected.',
    $negativeReceivedRejected
);


$negativeUpdatedRejected =
    false;


try {

    $repository
        ->complete(
            $runIdOne,
            'Success',
            $completedOne,
            0,
            -1,
            0,
            0,
            0,
            null
        );

} catch (
    InvalidArgumentException $exception
) {

    $negativeUpdatedRejected =
        true;
}


updateRunRepositoryCheck(
    'Negative updated count is rejected.',
    $negativeUpdatedRejected
);


$negativeSkippedRejected =
    false;


try {

    $repository
        ->complete(
            $runIdOne,
            'Success',
            $completedOne,
            0,
            0,
            -1,
            0,
            0,
            null
        );

} catch (
    InvalidArgumentException $exception
) {

    $negativeSkippedRejected =
        true;
}


updateRunRepositoryCheck(
    'Negative skipped count is rejected.',
    $negativeSkippedRejected
);


$negativeFailedRejected =
    false;


try {

    $repository
        ->complete(
            $runIdOne,
            'Success',
            $completedOne,
            0,
            0,
            0,
            -1,
            0,
            null
        );

} catch (
    InvalidArgumentException $exception
) {

    $negativeFailedRejected =
        true;
}


updateRunRepositoryCheck(
    'Negative failed count is rejected.',
    $negativeFailedRejected
);


$negativeDurationRejected =
    false;


try {

    $repository
        ->complete(
            $runIdOne,
            'Success',
            $completedOne,
            0,
            0,
            0,
            0,
            -1,
            null
        );

} catch (
    InvalidArgumentException $exception
) {

    $negativeDurationRejected =
        true;
}


updateRunRepositoryCheck(
    'Negative duration is rejected.',
    $negativeDurationRejected
);


/*
 * ============================================================
 * SCENARIO K
 * INVALID RETRIEVAL INPUT
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario K: Invalid Retrieval Input'
);


$invalidGetIdRejected =
    false;


try {

    $repository
        ->getById(
            0
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidGetIdRejected =
        true;
}


updateRunRepositoryCheck(
    'getById rejects a non-positive run ID.',
    $invalidGetIdRejected
);


$invalidLatestTypeRejected =
    false;


try {

    $repository
        ->getLatestByType(
            ''
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidLatestTypeRejected =
        true;
}


updateRunRepositoryCheck(
    'getLatestByType rejects an empty update type.',
    $invalidLatestTypeRejected
);


$invalidRecentTypeRejected =
    false;


try {

    $repository
        ->getRecentByType(
            '',
            10
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidRecentTypeRejected =
        true;
}


updateRunRepositoryCheck(
    'getRecentByType rejects an empty update type.',
    $invalidRecentTypeRejected
);


$invalidRecentLimitRejected =
    false;


try {

    $repository
        ->getRecentByType(
            $bootstrapType,
            0
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidRecentLimitRejected =
        true;
}


updateRunRepositoryCheck(
    'getRecentByType rejects a non-positive limit.',
    $invalidRecentLimitRejected
);


/*
 * ============================================================
 * SCENARIO L
 * CLEANUP
 * ============================================================
 */

updateRunRepositorySection(
    'Scenario L: Cleanup'
);


$cleanupStatement =
    $db->prepare(
        "
        DELETE FROM update_runs
        WHERE update_type IN (
            :bootstrap_type,
            :fixture_type
        )
        "
    );


$cleanupStatement
    ->execute(
        [
            'bootstrap_type' =>
                $bootstrapType,

            'fixture_type' =>
                $fixtureType
        ]
    );


$remainingStatement =
    $db->prepare(
        "
        SELECT COUNT(*)
        FROM update_runs
        WHERE update_type IN (
            :bootstrap_type,
            :fixture_type
        )
        "
    );


$remainingStatement
    ->execute(
        [
            'bootstrap_type' =>
                $bootstrapType,

            'fixture_type' =>
                $fixtureType
        ]
    );


$remaining =
    (int) $remainingStatement
        ->fetchColumn();


updateRunRepositoryCheck(
    'Synthetic update-run test rows are removed.',
    $remaining === 0
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

updateRunRepositorySummary();