<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Data Health Evaluation Test<br>";
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

function dataHealthEvaluationCheck(
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


function dataHealthEvaluationSection(
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


function dataHealthEvaluationSummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Data Health Evaluation Test Summary<br>";
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
 * SHARED EVALUATION BOUNDARY
 * ============================================================
 */

dataHealthEvaluationSection(
    'Scenario A: Shared Evaluation Boundary'
);


$helperPath =
    __DIR__
    . '/../public/includes/data-health.php';


dataHealthEvaluationCheck(
    'Shared data-health evaluation helper exists.',
    is_file(
        $helperPath
    )
);


if (
    !is_file(
        $helperPath
    )
) {

    dataHealthEvaluationSummary();

    exit;
}


require_once $helperPath;


dataHealthEvaluationCheck(
    'Shared helper exposes evaluateDataHealth().',
    function_exists(
        'evaluateDataHealth'
    )
);


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
 * CONTROLLED TEST DATA
 * ============================================================
 */

$bootstrapType =
    'test_shared_health_bootstrap';

$fixturesType =
    'test_shared_health_fixtures';

$historyType =
    'test_shared_health_history';


$testTypes = [
    $bootstrapType,
    $fixturesType,
    $historyType
];


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


$cleanupStatement->execute(
    $testTypes
);


$now =
    '2026-09-22 12:00:00';

$freshnessSeconds =
    86400;


/*
 * Bootstrap:
 * fresh successful update.
 */

$bootstrapRunId =
    $repository->start(
        $bootstrapType,
        '2026-09-22 10:59:50'
    );


$repository->complete(
    $bootstrapRunId,
    'Success',
    '2026-09-22 11:00:00',
    600,
    600,
    0,
    0,
    10000,
    null
);


/*
 * Fixtures:
 * stale successful update.
 */

$fixturesRunId =
    $repository->start(
        $fixturesType,
        '2026-09-20 09:59:50'
    );


$repository->complete(
    $fixturesRunId,
    'Success',
    '2026-09-20 10:00:00',
    380,
    380,
    0,
    0,
    10000,
    null
);


/*
 * Player history:
 * latest attempt failed after a previous success.
 */

$historySuccessId =
    $repository->start(
        $historyType,
        '2026-09-22 07:59:50'
    );


$repository->complete(
    $historySuccessId,
    'Success',
    '2026-09-22 08:00:00',
    700,
    700,
    0,
    0,
    10000,
    null
);


$historyFailureId =
    $repository->start(
        $historyType,
        '2026-09-22 10:29:50'
    );


$repository->complete(
    $historyFailureId,
    'Failed',
    '2026-09-22 10:30:00',
    0,
    0,
    0,
    1,
    10000,
    'Controlled test failure.'
);


/*
 * ============================================================
 * SCENARIO B
 * REQUESTED FEEDS ONLY
 * ============================================================
 */

dataHealthEvaluationSection(
    'Scenario B: Requested Feeds Only'
);


$teamHealth =
    evaluateDataHealth(
        $db,
        [
            $bootstrapType,
            $fixturesType
        ],
        $now,
        $freshnessSeconds
    );


dataHealthEvaluationCheck(
    'Evaluation returns requested Bootstrap feed.',
    array_key_exists(
        $bootstrapType,
        $teamHealth
    )
);


dataHealthEvaluationCheck(
    'Evaluation returns requested Fixtures feed.',
    array_key_exists(
        $fixturesType,
        $teamHealth
    )
);


dataHealthEvaluationCheck(
    'Evaluation does not add unrequested Player Fixture History feed.',
    !array_key_exists(
        $historyType,
        $teamHealth
    )
);


dataHealthEvaluationCheck(
    'Fresh requested feed preserves Healthy status.',
    (
        $teamHealth[
            $bootstrapType
        ][
            'status'
        ]
        ?? null
    )
    ===
    'Healthy'
);


dataHealthEvaluationCheck(
    'Stale requested feed preserves Stale status.',
    (
        $teamHealth[
            $fixturesType
        ][
            'status'
        ]
        ?? null
    )
    ===
    'Stale'
);


/*
 * ============================================================
 * SCENARIO C
 * THREE-FEED INTELLIGENCE SURFACE
 * ============================================================
 */

dataHealthEvaluationSection(
    'Scenario C: Three-Feed Intelligence Surface'
);


$playerHealth =
    evaluateDataHealth(
        $db,
        [
            $bootstrapType,
            $fixturesType,
            $historyType
        ],
        $now,
        $freshnessSeconds
    );


dataHealthEvaluationCheck(
    'Three-feed evaluation preserves all requested feeds.',
    count(
        $playerHealth
    )
    ===
    3
);


dataHealthEvaluationCheck(
    'Latest failed update remains Failed through shared evaluation.',
    (
        $playerHealth[
            $historyType
        ][
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataHealthEvaluationCheck(
    'Shared evaluation preserves last successful timestamp after failure.',
    (
        $playerHealth[
            $historyType
        ][
            'last_success_at'
        ]
        ?? null
    )
    ===
    '2026-09-22 08:00:00'
);


/*
 * ============================================================
 * SCENARIO D
 * EMPTY DEPENDENCY SET
 * ============================================================
 */

dataHealthEvaluationSection(
    'Scenario D: Empty Dependency Set'
);


$emptyHealth =
    evaluateDataHealth(
        $db,
        [],
        $now,
        $freshnessSeconds
    );


dataHealthEvaluationCheck(
    'No requested feeds returns an empty health result.',
    $emptyHealth === []
);


/*
 * ============================================================
 * SCENARIO E
 * INVALID UPDATE TYPE
 * ============================================================
 */

dataHealthEvaluationSection(
    'Scenario E: Invalid Update Type'
);


$invalidTypeRejected =
    false;


try {

    evaluateDataHealth(
        $db,
        [
            ''
        ],
        $now,
        $freshnessSeconds
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidTypeRejected =
        true;
}


dataHealthEvaluationCheck(
    'Empty requested update type is rejected.',
    $invalidTypeRejected
);


/*
 * ============================================================
 * SCENARIO F
 * DUPLICATE UPDATE TYPE
 * ============================================================
 */

dataHealthEvaluationSection(
    'Scenario F: Duplicate Update Type'
);


$duplicateHealth =
    evaluateDataHealth(
        $db,
        [
            $bootstrapType,
            $bootstrapType
        ],
        $now,
        $freshnessSeconds
    );


dataHealthEvaluationCheck(
    'Duplicate requested feeds are evaluated once.',
    count(
        $duplicateHealth
    )
    ===
    1
);


dataHealthEvaluationCheck(
    'Duplicate handling preserves requested feed result.',
    (
        $duplicateHealth[
            $bootstrapType
        ][
            'status'
        ]
        ?? null
    )
    ===
    'Healthy'
);


/*
 * ============================================================
 * SCENARIO G
 * CLEANUP
 * ============================================================
 */

dataHealthEvaluationSection(
    'Scenario G: Cleanup'
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


$cleanupStatement->execute(
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


$remainingStatement->execute(
    $testTypes
);


$remaining =
    (int) $remainingStatement
        ->fetchColumn();


dataHealthEvaluationCheck(
    'Synthetic shared-health rows are removed.',
    $remaining === 0
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

dataHealthEvaluationSummary();