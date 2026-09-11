<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Update Health Service Test<br>";
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

function updateHealthCheck(
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


function updateHealthSection(
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


function updateHealthSummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Update Health Service Test Summary<br>";
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
 * TEST UPDATE TYPES
 * ============================================================
 *
 * Every scenario uses a synthetic update type so the test
 * cannot interfere with genuine production update history.
 */

$unavailableType =
    'test_health_unavailable';


$healthyType =
    'test_health_healthy';


$staleType =
    'test_health_stale';


$failedType =
    'test_health_failed';


$partialType =
    'test_health_partial';


$runningType =
    'test_health_running';


$stuckType =
    'test_health_stuck';


$failedAfterSuccessType =
    'test_health_failed_after_success';


$partialAfterSuccessType =
    'test_health_partial_after_success';


$metadataType =
    'test_health_metadata';


$testTypes = [
    $unavailableType,
    $healthyType,
    $staleType,
    $failedType,
    $partialType,
    $runningType,
    $stuckType,
    $failedAfterSuccessType,
    $partialAfterSuccessType,
    $metadataType
];


/*
 * ============================================================
 * PRE-TEST CLEANUP
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
            {$placeholders}
        )
        "
    );


$cleanupStatement
    ->execute(
        $testTypes
    );


/*
 * ============================================================
 * CONTROLLED HEALTH TIME
 * ============================================================
 *
 * Health evaluation must be deterministic.
 *
 * Tests therefore supply the current time rather than relying
 * on the machine clock.
 */

$now =
    '2026-09-11 12:00:00';


$freshnessSeconds =
    86400;


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

updateHealthSection(
    'Scenario A: Class Contract'
);


updateHealthCheck(
    'UpdateHealthService exists.',
    class_exists(
        'UpdateHealthService'
    )
);


if (
    !class_exists(
        'UpdateHealthService'
    )
) {

    updateHealthSummary();

    exit;
}


/*
 * ============================================================
 * SERVICE
 * ============================================================
 */

$service =
    new UpdateHealthService(
        $repository
    );


/*
 * ============================================================
 * SCENARIO B
 * NO HISTORY -> UNAVAILABLE
 * ============================================================
 */

updateHealthSection(
    'Scenario B: No History -> Unavailable'
);


$unavailable =
    $service
        ->evaluate(
            $unavailableType,
            $now,
            $freshnessSeconds
        );


updateHealthCheck(
    'Health evaluation returns an array.',
    is_array(
        $unavailable
    )
);


updateHealthCheck(
    'Update type with no history is Unavailable.',
    (
        $unavailable[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


updateHealthCheck(
    'Unavailable health preserves update type.',
    (
        $unavailable[
            'update_type'
        ]
        ?? null
    )
    ===
    $unavailableType
);


updateHealthCheck(
    'Unavailable health has no latest run.',
    array_key_exists(
        'latest_run',
        $unavailable
    )
    &&
    $unavailable[
        'latest_run'
    ]
    ===
    null
);


updateHealthCheck(
    'Unavailable health has no latest successful run.',
    array_key_exists(
        'latest_successful_run',
        $unavailable
    )
    &&
    $unavailable[
        'latest_successful_run'
    ]
    ===
    null
);


updateHealthCheck(
    'Unavailable health has no last-success timestamp.',
    array_key_exists(
        'last_success_at',
        $unavailable
    )
    &&
    $unavailable[
        'last_success_at'
    ]
    ===
    null
);


updateHealthCheck(
    'Unavailable health has no success age.',
    array_key_exists(
        'age_seconds',
        $unavailable
    )
    &&
    $unavailable[
        'age_seconds'
    ]
    ===
    null
);


/*
 * ============================================================
 * SCENARIO C
 * FRESH SUCCESS -> HEALTHY
 * ============================================================
 */

updateHealthSection(
    'Scenario C: Fresh Success -> Healthy'
);


$healthyRunId =
    $repository
        ->start(
            $healthyType,
            '2026-09-11 10:59:50'
        );


$repository
    ->complete(
        $healthyRunId,
        'Success',
        '2026-09-11 11:00:00',
        380,
        380,
        0,
        0,
        10000,
        null
    );


$healthy =
    $service
        ->evaluate(
            $healthyType,
            $now,
            $freshnessSeconds
        );


updateHealthCheck(
    'Fresh successful update is Healthy.',
    (
        $healthy[
            'status'
        ]
        ?? null
    )
    ===
    'Healthy'
);


updateHealthCheck(
    'Healthy result preserves latest successful run.',
    (
        $healthy[
            'latest_successful_run'
        ][
            'id'
        ]
        ?? null
    )
    ===
    $healthyRunId
);


updateHealthCheck(
    'Healthy result preserves last-success timestamp.',
    (
        $healthy[
            'last_success_at'
        ]
        ?? null
    )
    ===
    '2026-09-11 11:00:00'
);


updateHealthCheck(
    'Healthy result calculates success age in seconds.',
    (
        $healthy[
            'age_seconds'
        ]
        ?? null
    )
    ===
    3600
);


/*
 * ============================================================
 * SCENARIO D
 * OLD SUCCESS -> STALE
 * ============================================================
 */

updateHealthSection(
    'Scenario D: Old Success -> Stale'
);


$staleRunId =
    $repository
        ->start(
            $staleType,
            '2026-09-09 11:59:50'
        );


$repository
    ->complete(
        $staleRunId,
        'Success',
        '2026-09-09 12:00:00',
        380,
        380,
        0,
        0,
        10000,
        null
    );


$stale =
    $service
        ->evaluate(
            $staleType,
            $now,
            $freshnessSeconds
        );


updateHealthCheck(
    'Successful update older than freshness threshold is Stale.',
    (
        $stale[
            'status'
        ]
        ?? null
    )
    ===
    'Stale'
);


updateHealthCheck(
    'Stale result preserves latest successful run.',
    (
        $stale[
            'latest_successful_run'
        ][
            'id'
        ]
        ?? null
    )
    ===
    $staleRunId
);


updateHealthCheck(
    'Stale result calculates success age.',
    (
        $stale[
            'age_seconds'
        ]
        ?? null
    )
    ===
    172800
);


/*
 * ============================================================
 * SCENARIO E
 * LATEST FAILED -> FAILED
 * ============================================================
 */

updateHealthSection(
    'Scenario E: Latest Failed -> Failed'
);


$failedRunId =
    $repository
        ->start(
            $failedType,
            '2026-09-11 11:29:50'
        );


$repository
    ->complete(
        $failedRunId,
        'Failed',
        '2026-09-11 11:30:00',
        0,
        0,
        0,
        1,
        10000,
        'FPL API unavailable.'
    );


$failedHealth =
    $service
        ->evaluate(
            $failedType,
            $now,
            $freshnessSeconds
        );


updateHealthCheck(
    'Latest failed update produces Failed health.',
    (
        $failedHealth[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


updateHealthCheck(
    'Failed health preserves latest failed run.',
    (
        $failedHealth[
            'latest_run'
        ][
            'id'
        ]
        ?? null
    )
    ===
    $failedRunId
);


updateHealthCheck(
    'Failed health preserves failure message.',
    (
        $failedHealth[
            'error_message'
        ]
        ?? null
    )
    ===
    'FPL API unavailable.'
);


updateHealthCheck(
    'Failed health has no last success when none exists.',
    array_key_exists(
        'last_success_at',
        $failedHealth
    )
    &&
    $failedHealth[
        'last_success_at'
    ]
    ===
    null
);


/*
 * ============================================================
 * SCENARIO F
 * LATEST PARTIAL -> PARTIAL
 * ============================================================
 */

updateHealthSection(
    'Scenario F: Latest Partial -> Partial'
);


$partialRunId =
    $repository
        ->start(
            $partialType,
            '2026-09-11 11:19:50'
        );


$repository
    ->complete(
        $partialRunId,
        'Partial',
        '2026-09-11 11:20:00',
        700,
        695,
        3,
        2,
        10000,
        'Two player records failed.'
    );


$partialHealth =
    $service
        ->evaluate(
            $partialType,
            $now,
            $freshnessSeconds
        );


updateHealthCheck(
    'Latest partial update produces Partial health.',
    (
        $partialHealth[
            'status'
        ]
        ?? null
    )
    ===
    'Partial'
);


updateHealthCheck(
    'Partial health preserves latest partial run.',
    (
        $partialHealth[
            'latest_run'
        ][
            'id'
        ]
        ?? null
    )
    ===
    $partialRunId
);


updateHealthCheck(
    'Partial health preserves explanatory message.',
    (
        $partialHealth[
            'error_message'
        ]
        ?? null
    )
    ===
    'Two player records failed.'
);


/*
 * ============================================================
 * SCENARIO G
 * ACTIVE RUNNING -> RUNNING
 * ============================================================
 */

updateHealthSection(
    'Scenario G: Active Running -> Running'
);


$runningRunId =
    $repository
        ->start(
            $runningType,
            '2026-09-11 11:55:00'
        );


$runningHealth =
    $service
        ->evaluate(
            $runningType,
            $now,
            $freshnessSeconds
        );


updateHealthCheck(
    'Recent incomplete run produces Running health.',
    (
        $runningHealth[
            'status'
        ]
        ?? null
    )
    ===
    'Running'
);


updateHealthCheck(
    'Running health preserves active run.',
    (
        $runningHealth[
            'latest_run'
        ][
            'id'
        ]
        ?? null
    )
    ===
    $runningRunId
);


/*
 * ============================================================
 * SCENARIO H
 * STUCK RUNNING -> FAILED
 * ============================================================
 */

updateHealthSection(
    'Scenario H: Stuck Running -> Failed'
);


/*
 * The service contract allows a Running update to remain active
 * for 30 minutes. Older Running records are treated as an
 * incomplete/stuck process.
 */

$stuckRunId =
    $repository
        ->start(
            $stuckType,
            '2026-09-11 10:00:00'
        );


$stuckHealth =
    $service
        ->evaluate(
            $stuckType,
            $now,
            $freshnessSeconds
        );


updateHealthCheck(
    'Old incomplete Running update produces Failed health.',
    (
        $stuckHealth[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


updateHealthCheck(
    'Stuck health preserves original Running record.',
    (
        $stuckHealth[
            'latest_run'
        ][
            'id'
        ]
        ?? null
    )
    ===
    $stuckRunId
);


updateHealthCheck(
    'Stuck Running record remains unchanged in persistence.',
    (
        $stuckHealth[
            'latest_run'
        ][
            'status'
        ]
        ?? null
    )
    ===
    'Running'
);


updateHealthCheck(
    'Stuck health provides an incomplete-run reason.',
    (
        $stuckHealth[
            'reason'
        ]
        ?? null
    )
    ===
    'Update run appears stuck or incomplete.'
);


/*
 * ============================================================
 * SCENARIO I
 * FAILED LATEST STILL PRESERVES LAST SUCCESS
 * ============================================================
 */

updateHealthSection(
    'Scenario I: Failed Latest Still Preserves Last Success'
);


$previousSuccessId =
    $repository
        ->start(
            $failedAfterSuccessType,
            '2026-09-11 08:59:50'
        );


$repository
    ->complete(
        $previousSuccessId,
        'Success',
        '2026-09-11 09:00:00',
        380,
        380,
        0,
        0,
        10000,
        null
    );


$latestFailureId =
    $repository
        ->start(
            $failedAfterSuccessType,
            '2026-09-11 10:59:50'
        );


$repository
    ->complete(
        $latestFailureId,
        'Failed',
        '2026-09-11 11:00:00',
        0,
        0,
        0,
        1,
        10000,
        'Second update failed.'
    );


$failedAfterSuccess =
    $service
        ->evaluate(
            $failedAfterSuccessType,
            $now,
            $freshnessSeconds
        );


updateHealthCheck(
    'Latest failure takes precedence over previous success.',
    (
        $failedAfterSuccess[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


updateHealthCheck(
    'Failed health identifies the latest failed attempt.',
    (
        $failedAfterSuccess[
            'latest_run'
        ][
            'id'
        ]
        ?? null
    )
    ===
    $latestFailureId
);


updateHealthCheck(
    'Failed health preserves previous successful run.',
    (
        $failedAfterSuccess[
            'latest_successful_run'
        ][
            'id'
        ]
        ?? null
    )
    ===
    $previousSuccessId
);


updateHealthCheck(
    'Failed health preserves previous last-success timestamp.',
    (
        $failedAfterSuccess[
            'last_success_at'
        ]
        ?? null
    )
    ===
    '2026-09-11 09:00:00'
);


updateHealthCheck(
    'Failed health still calculates age of last good data.',
    (
        $failedAfterSuccess[
            'age_seconds'
        ]
        ?? null
    )
    ===
    10800
);


/*
 * ============================================================
 * SCENARIO J
 * PARTIAL LATEST STILL PRESERVES LAST SUCCESS
 * ============================================================
 */

updateHealthSection(
    'Scenario J: Partial Latest Still Preserves Last Success'
);


$partialPreviousSuccessId =
    $repository
        ->start(
            $partialAfterSuccessType,
            '2026-09-11 07:59:50'
        );


$repository
    ->complete(
        $partialPreviousSuccessId,
        'Success',
        '2026-09-11 08:00:00',
        700,
        700,
        0,
        0,
        10000,
        null
    );


$latestPartialId =
    $repository
        ->start(
            $partialAfterSuccessType,
            '2026-09-11 10:29:50'
        );


$repository
    ->complete(
        $latestPartialId,
        'Partial',
        '2026-09-11 10:30:00',
        700,
        698,
        0,
        2,
        10000,
        'Two records failed.'
    );


$partialAfterSuccess =
    $service
        ->evaluate(
            $partialAfterSuccessType,
            $now,
            $freshnessSeconds
        );


updateHealthCheck(
    'Latest partial attempt takes precedence over previous success.',
    (
        $partialAfterSuccess[
            'status'
        ]
        ?? null
    )
    ===
    'Partial'
);


updateHealthCheck(
    'Partial health preserves previous successful run.',
    (
        $partialAfterSuccess[
            'latest_successful_run'
        ][
            'id'
        ]
        ?? null
    )
    ===
    $partialPreviousSuccessId
);


updateHealthCheck(
    'Partial health preserves previous last-success timestamp.',
    (
        $partialAfterSuccess[
            'last_success_at'
        ]
        ?? null
    )
    ===
    '2026-09-11 08:00:00'
);


updateHealthCheck(
    'Partial health calculates age of last good data.',
    (
        $partialAfterSuccess[
            'age_seconds'
        ]
        ?? null
    )
    ===
    14400
);


/*
 * ============================================================
 * SCENARIO K
 * HEALTH METADATA PRESERVATION
 * ============================================================
 */

updateHealthSection(
    'Scenario K: Health Metadata Preservation'
);


$metadataRunId =
    $repository
        ->start(
            $metadataType,
            '2026-09-11 11:44:55'
        );


$repository
    ->complete(
        $metadataRunId,
        'Success',
        '2026-09-11 11:45:00',
        572,
        560,
        12,
        0,
        5432,
        null
    );


$metadata =
    $service
        ->evaluate(
            $metadataType,
            $now,
            $freshnessSeconds
        );


updateHealthCheck(
    'Health output preserves records received.',
    (
        $metadata[
            'records_received'
        ]
        ?? null
    )
    ===
    572
);


updateHealthCheck(
    'Health output preserves records updated.',
    (
        $metadata[
            'records_updated'
        ]
        ?? null
    )
    ===
    560
);


updateHealthCheck(
    'Health output preserves records skipped.',
    (
        $metadata[
            'records_skipped'
        ]
        ?? null
    )
    ===
    12
);


updateHealthCheck(
    'Health output preserves records failed.',
    (
        $metadata[
            'records_failed'
        ]
        ?? null
    )
    ===
    0
);


updateHealthCheck(
    'Health output preserves update duration.',
    (
        $metadata[
            'duration_ms'
        ]
        ?? null
    )
    ===
    5432
);


updateHealthCheck(
    'Healthy result exposes no error message.',
    array_key_exists(
        'error_message',
        $metadata
    )
    &&
    $metadata[
        'error_message'
    ]
    ===
    null
);


/*
 * ============================================================
 * SCENARIO L
 * INVALID INPUT
 * ============================================================
 */

updateHealthSection(
    'Scenario L: Invalid Input'
);


$emptyTypeRejected =
    false;


try {

    $service
        ->evaluate(
            '',
            $now,
            $freshnessSeconds
        );

} catch (
    InvalidArgumentException $exception
) {

    $emptyTypeRejected =
        true;
}


updateHealthCheck(
    'Empty update type is rejected.',
    $emptyTypeRejected
);


$invalidNowRejected =
    false;


try {

    $service
        ->evaluate(
            $healthyType,
            'not-a-date',
            $freshnessSeconds
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidNowRejected =
        true;
}


updateHealthCheck(
    'Invalid current timestamp is rejected.',
    $invalidNowRejected
);


$zeroFreshnessRejected =
    false;


try {

    $service
        ->evaluate(
            $healthyType,
            $now,
            0
        );

} catch (
    InvalidArgumentException $exception
) {

    $zeroFreshnessRejected =
        true;
}


updateHealthCheck(
    'Zero freshness threshold is rejected.',
    $zeroFreshnessRejected
);


$negativeFreshnessRejected =
    false;


try {

    $service
        ->evaluate(
            $healthyType,
            $now,
            -1
        );

} catch (
    InvalidArgumentException $exception
) {

    $negativeFreshnessRejected =
        true;
}


updateHealthCheck(
    'Negative freshness threshold is rejected.',
    $negativeFreshnessRejected
);


/*
 * ============================================================
 * SCENARIO M
 * CLEANUP
 * ============================================================
 */

updateHealthSection(
    'Scenario M: Cleanup'
);


$cleanupStatement =
    $db->prepare(
        "
        DELETE FROM update_runs
        WHERE update_type IN (
            {$placeholders}
        )
        "
    );


$cleanupStatement
    ->execute(
        $testTypes
    );


$remainingStatement =
    $db->prepare(
        "
        SELECT COUNT(*)
        FROM update_runs
        WHERE update_type IN (
            {$placeholders}
        )
        "
    );


$remainingStatement
    ->execute(
        $testTypes
    );


$remaining =
    (int) $remainingStatement
        ->fetchColumn();


updateHealthCheck(
    'Synthetic update-health test rows are removed.',
    $remaining === 0
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

updateHealthSummary();