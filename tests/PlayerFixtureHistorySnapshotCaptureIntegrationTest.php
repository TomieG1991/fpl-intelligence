<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Fixture History Snapshot Capture Integration Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function historySnapshotIntegrationCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


/*
 * ============================================================
 * LOAD HISTORY UPDATER SOURCE
 * ============================================================
 */

$updaterFile =
    __DIR__
    . '/../cron/updatePlayerFixtureHistory.php';


$updaterSource =
    is_file(
        $updaterFile
    )
        ? file_get_contents(
            $updaterFile
        )
        : false;


/*
 * ============================================================
 * SCENARIO A
 * UPDATER FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Updater Foundation<br>";
echo "============================================<br>";


historySnapshotIntegrationCheck(
    'Player fixture history updater exists',
    is_string(
        $updaterSource
    )
);


if (
    !is_string(
        $updaterSource
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


historySnapshotIntegrationCheck(
    'Updater supports full import mode',
    str_contains(
        $updaterSource,
        '$fullImport'
    )
);


historySnapshotIntegrationCheck(
    'Updater tracks processed players',
    str_contains(
        $updaterSource,
        '$playersProcessed'
    )
);


historySnapshotIntegrationCheck(
    'Updater tracks failed players',
    str_contains(
        $updaterSource,
        '$playersFailed'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * SNAPSHOT CAPTURE GATE WIRING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Snapshot Capture Gate Wiring<br>";
echo "============================================<br>";


historySnapshotIntegrationCheck(
    'Updater constructs snapshot capture gate',
    str_contains(
        $updaterSource,
        'new PlayerGameweekSnapshotCaptureGate'
    )
);


historySnapshotIntegrationCheck(
    'Updater invokes snapshot capture gate',
    str_contains(
        $updaterSource,
        '->shouldCapture('
    )
);


historySnapshotIntegrationCheck(
    'Gate receives full import state',
    str_contains(
        $updaterSource,
        '$fullImport,'
    )
);


historySnapshotIntegrationCheck(
    'Gate receives selected player count',
    str_contains(
        $updaterSource,
        '$selectedPlayerCount,'
    )
);


historySnapshotIntegrationCheck(
    'Gate receives processed player count',
    str_contains(
        $updaterSource,
        '$playersProcessed,'
    )
);


historySnapshotIntegrationCheck(
    'Gate receives failed player count',
    str_contains(
        $updaterSource,
        '$playersFailed'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * SNAPSHOT CAPTURE SERVICE WIRING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Snapshot Capture Service Wiring<br>";
echo "============================================<br>";


historySnapshotIntegrationCheck(
    'Updater constructs snapshot capture service',
    str_contains(
        $updaterSource,
        'new PlayerGameweekSnapshotCapture'
    )
);


historySnapshotIntegrationCheck(
    'Updater invokes latest completed gameweek capture',
    str_contains(
        $updaterSource,
        'captureLatestCompletedGameweek'
    )
);


historySnapshotIntegrationCheck(
    'Updater passes database connection to capture service',
    str_contains(
        $updaterSource,
        'new PlayerGameweekSnapshotCapture'
    )
    &&
    str_contains(
        $updaterSource,
        '$db'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * BATCH SAFETY CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Batch Safety Contract<br>";
echo "============================================<br>";


historySnapshotIntegrationCheck(
    'Updater explicitly prevents batch snapshot capture',
    str_contains(
        $updaterSource,
        '!$fullImport'
    )
);


historySnapshotIntegrationCheck(
    'Updater reports batch capture skip',
    str_contains(
        $updaterSource,
        'Batch history imports do not capture snapshots'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * INCOMPLETE FULL IMPORT SAFETY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Incomplete Full Import Safety<br>";
echo "============================================<br>";


historySnapshotIntegrationCheck(
    'Updater checks full import completion before capture',
    str_contains(
        $updaterSource,
        '!$fullImportComplete'
    )
);


historySnapshotIntegrationCheck(
    'Updater reports incomplete full import capture skip',
    str_contains(
        $updaterSource,
        'Full history import did not complete successfully'
    )
);


historySnapshotIntegrationCheck(
    'Updater reports expected player count on incomplete import',
    str_contains(
        $updaterSource,
        'Players expected: '
    )
);


historySnapshotIntegrationCheck(
    'Updater reports processed player count on incomplete import',
    str_contains(
        $updaterSource,
        'Players processed: '
    )
);


historySnapshotIntegrationCheck(
    'Updater reports failed player count on incomplete import',
    str_contains(
        $updaterSource,
        'Players failed: '
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * WRITE OWNERSHIP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Snapshot Write Ownership<br>";
echo "============================================<br>";


historySnapshotIntegrationCheck(
    'History updater does not construct snapshot repository directly',
    !str_contains(
        $updaterSource,
        'new PlayerGameweekSnapshotRepository'
    )
);


historySnapshotIntegrationCheck(
    'History updater does not insert snapshots directly',
    !str_contains(
        $updaterSource,
        '->insertIfAbsent('
    )
);


historySnapshotIntegrationCheck(
    'History updater delegates snapshot writes to capture service',
    str_contains(
        $updaterSource,
        'PlayerGameweekSnapshotCapture'
    )
    &&
    str_contains(
        $updaterSource,
        'captureLatestCompletedGameweek'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * OUTPUT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Output Contract<br>";
echo "============================================<br>";


historySnapshotIntegrationCheck(
    'Updater reports snapshot capture section',
    str_contains(
        $updaterSource,
        'Player Gameweek Snapshot Capture'
    )
);


historySnapshotIntegrationCheck(
    'Updater reports capture status',
    str_contains(
        $updaterSource,
        'Capture status: '
    )
);


historySnapshotIntegrationCheck(
    'Updater reports inserted snapshot count',
    str_contains(
        $updaterSource,
        'Snapshots inserted: '
    )
);


historySnapshotIntegrationCheck(
    'Updater reports existing snapshot count',
    str_contains(
        $updaterSource,
        'Snapshots already present: '
    )
);


historySnapshotIntegrationCheck(
    'Updater reports skipped snapshot count',
    str_contains(
        $updaterSource,
        'Snapshots skipped: '
    )
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Fixture History Snapshot Capture Integration Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}