<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Capture Runner Retirement Test<br>";
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

function snapshotCaptureRunnerCheck(
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


/*
 * ============================================================
 * SCENARIO A
 * LEGACY ENTRY POINT REMAINS SAFE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Legacy Entry Point Remains Safe<br>";
echo "============================================<br>";


$runnerFile =
    __DIR__
    . '/../cron/capturePlayerGameweekSnapshots.php';


$runnerSource =
    is_file(
        $runnerFile
    )
        ? file_get_contents(
            $runnerFile
        )
        : false;


snapshotCaptureRunnerCheck(
    'Legacy snapshot capture entry point still exists during migration',
    is_string(
        $runnerSource
    )
);


if (
    !is_string(
        $runnerSource
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


snapshotCaptureRunnerCheck(
    'Legacy entry point still loads project autoloader',
    str_contains(
        $runnerSource,
        "require_once __DIR__"
    )
);


snapshotCaptureRunnerCheck(
    'Legacy entry point is explicitly marked RETIRED',
    str_contains(
        $runnerSource,
        'RETIRED'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * RETROSPECTIVE RECONSTRUCTION IS DISABLED
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Retrospective Reconstruction Is Disabled<br>";
echo "============================================<br>";


snapshotCaptureRunnerCheck(
    'Legacy entry point does not construct retrospective capture service',
    !str_contains(
        $runnerSource,
        'new PlayerGameweekSnapshotCapture'
    )
);


snapshotCaptureRunnerCheck(
    'Legacy entry point does not invoke completed-gameweek reconstruction',
    !str_contains(
        $runnerSource,
        'captureLatestCompletedGameweek'
    )
);


snapshotCaptureRunnerCheck(
    'Legacy entry point does not construct current player repository',
    !str_contains(
        $runnerSource,
        'new PlayerRepository'
    )
);


snapshotCaptureRunnerCheck(
    'Legacy entry point has no fixture-history dependency',
    !str_contains(
        $runnerSource,
        'player_fixture_history'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * NO DATABASE WRITE PATH
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: No Database Write Path<br>";
echo "============================================<br>";


snapshotCaptureRunnerCheck(
    'Retired entry point does not construct Database',
    !str_contains(
        $runnerSource,
        'new Database'
    )
);


snapshotCaptureRunnerCheck(
    'Retired entry point does not construct immutable snapshot repository',
    !str_contains(
        $runnerSource,
        'new PlayerGameweekSnapshotRepository'
    )
);


snapshotCaptureRunnerCheck(
    'Retired entry point does not call snapshot upsert',
    !str_contains(
        $runnerSource,
        '->upsert('
    )
);


snapshotCaptureRunnerCheck(
    'Retired entry point does not call immutable insert',
    !str_contains(
        $runnerSource,
        '->insertIfAbsent('
    )
);


snapshotCaptureRunnerCheck(
    'Retired entry point contains no INSERT statement',
    stripos(
        $runnerSource,
        'INSERT INTO'
    ) === false
);


snapshotCaptureRunnerCheck(
    'Retired entry point contains no UPDATE statement',
    stripos(
        $runnerSource,
        'UPDATE '
    ) === false
);


snapshotCaptureRunnerCheck(
    'Retired entry point contains no DELETE statement',
    stripos(
        $runnerSource,
        'DELETE FROM'
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * NEW LIFECYCLE GUIDANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: New Lifecycle Guidance<br>";
echo "============================================<br>";


snapshotCaptureRunnerCheck(
    'Retired entry point directs capture to pre-deadline candidate lifecycle',
    str_contains(
        $runnerSource,
        'capturePlayerGameweekSnapshotCandidates.php'
    )
);


snapshotCaptureRunnerCheck(
    'Retired entry point directs freezing to deadline promotion lifecycle',
    str_contains(
        $runnerSource,
        'promotePlayerGameweekSnapshotCandidates.php'
    )
);


snapshotCaptureRunnerCheck(
    'Retired entry point explains that no historical snapshots are written',
    str_contains(
        $runnerSource,
        'No historical snapshots have been written'
    )
);


snapshotCaptureRunnerCheck(
    'Retired entry point exposes explicit retirement result',
    str_contains(
        $runnerSource,
        'RESULT: SNAPSHOT CAPTURE RETIRED'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * TEST-SUITE SAFETY CONTRACT
 * ============================================================
 *
 * This test deliberately performs static inspection only.
 *
 * It must never instantiate PlayerGameweekSnapshotCapture or
 * execute completed-gameweek reconstruction against the real
 * database.
 */

echo "============================================<br>";
echo "Scenario E: Test-Suite Safety Contract<br>";
echo "============================================<br>";


$thisTestSource =
    file_get_contents(
        __FILE__
    );


snapshotCaptureRunnerCheck(
    'Runner retirement test performs no database construction',
    !str_contains(
        $thisTestSource,
        'new ' . 'Database('
    )
);


snapshotCaptureRunnerCheck(
    'Runner retirement test does not instantiate retrospective capture service',
    !str_contains(
        $thisTestSource,
        'new '
        . 'PlayerGameweekSnapshotCapture('
    )
);


snapshotCaptureRunnerCheck(
    'Runner retirement test does not execute completed-gameweek reconstruction',
    !str_contains(
        $thisTestSource,
        '->'
        . 'captureLatestCompletedGameweek('
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * RETIREMENT DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Retirement Diagnostic<br>";
echo "============================================<br><br>";


echo "Legacy Entry Point: Present<br>";
echo "Operational Status: RETIRED<br>";
echo "Database Access: Disabled<br>";
echo "Retrospective Reconstruction: Disabled<br>";
echo "Replacement Capture: Pre-Deadline Candidates<br>";
echo "Replacement Freeze: Deadline Promotion<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Gameweek Snapshot Capture Runner Retirement Test Summary<br>";
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

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}