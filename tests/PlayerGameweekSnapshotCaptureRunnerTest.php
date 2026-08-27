<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Capture Runner Test<br>";
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
 * SCENARIO A
 * RUNNER FILE FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Runner File Foundation<br>";
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
    'Snapshot capture runner exists',
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
    'Runner loads project autoloader',
    str_contains(
        $runnerSource,
        "require_once __DIR__"
    )
);


snapshotCaptureRunnerCheck(
    'Runner constructs PlayerGameweekSnapshotCapture service',
    str_contains(
        $runnerSource,
        'new PlayerGameweekSnapshotCapture'
    )
);


snapshotCaptureRunnerCheck(
    'Runner invokes captureLatestCompletedGameweek()',
    str_contains(
        $runnerSource,
        'captureLatestCompletedGameweek'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * IMMUTABLE WRITE PATH
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Immutable Write Path<br>";
echo "============================================<br>";


snapshotCaptureRunnerCheck(
    'Runner does not construct PlayerGameweekSnapshotRepository directly',
    !str_contains(
        $runnerSource,
        'new PlayerGameweekSnapshotRepository'
    )
);


snapshotCaptureRunnerCheck(
    'Runner does not call snapshot upsert directly',
    !str_contains(
        $runnerSource,
        '->upsert('
    )
);


snapshotCaptureRunnerCheck(
    'Runner does not call insertIfAbsent directly',
    !str_contains(
        $runnerSource,
        '->insertIfAbsent('
    )
);


snapshotCaptureRunnerCheck(
    'Runner delegates all snapshot writes to capture service',
    str_contains(
        $runnerSource,
        'PlayerGameweekSnapshotCapture'
    )
    &&
    str_contains(
        $runnerSource,
        'captureLatestCompletedGameweek'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * OUTPUT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Output Contract<br>";
echo "============================================<br>";


snapshotCaptureRunnerCheck(
    'Runner reports gameweek',
    str_contains(
        $runnerSource,
        'Gameweek: GW'
    )
);


snapshotCaptureRunnerCheck(
    'Runner reports finished state',
    str_contains(
        $runnerSource,
        'Finished: '
    )
);


snapshotCaptureRunnerCheck(
    'Runner reports data-checked state',
    str_contains(
        $runnerSource,
        'Data Checked: '
    )
);


snapshotCaptureRunnerCheck(
    'Runner reports players considered',
    str_contains(
        $runnerSource,
        'Players Considered: '
    )
);


snapshotCaptureRunnerCheck(
    'Runner reports inserted snapshots',
    str_contains(
        $runnerSource,
        'Snapshots Inserted: '
    )
);


snapshotCaptureRunnerCheck(
    'Runner reports existing snapshots',
    str_contains(
        $runnerSource,
        'Snapshots Already Present: '
    )
);


snapshotCaptureRunnerCheck(
    'Runner reports skipped snapshots',
    str_contains(
        $runnerSource,
        'Snapshots Skipped: '
    )
);


snapshotCaptureRunnerCheck(
    'Runner reports successful completion',
    str_contains(
        $runnerSource,
        'RESULT: SNAPSHOT CAPTURE COMPLETE'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * ACCOUNTING VALIDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Accounting Validation<br>";
echo "============================================<br>";


snapshotCaptureRunnerCheck(
    'Runner validates players considered against result counts',
    str_contains(
        $runnerSource,
        '$playersConsidered'
    )
    &&
    str_contains(
        $runnerSource,
        '$inserted'
    )
    &&
    str_contains(
        $runnerSource,
        '$existing'
    )
    &&
    str_contains(
        $runnerSource,
        '$skipped'
    )
);


snapshotCaptureRunnerCheck(
    'Runner throws when accounting does not match',
    str_contains(
        $runnerSource,
        'Snapshot capture accounting does not match players considered'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * REAL SERVICE REGRESSION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Real Service Regression<br>";
echo "============================================<br>";


$database =
    new Database();


$connection =
    $database
        ->getConnection();


$capture =
    new PlayerGameweekSnapshotCapture(
        $connection
    );


$result =
    $capture
        ->captureLatestCompletedGameweek();


snapshotCaptureRunnerCheck(
    'Real capture service still returns result array',
    is_array(
        $result
    )
);


snapshotCaptureRunnerCheck(
    'Real capture service reports complete status',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Complete'
);


$playersConsidered =
    (int) (
        $result[
            'players_considered'
        ]
        ?? -1
    );


$inserted =
    (int) (
        $result[
            'inserted'
        ]
        ?? -1
    );


$existing =
    (int) (
        $result[
            'existing'
        ]
        ?? -1
    );


$skipped =
    (int) (
        $result[
            'skipped'
        ]
        ?? -1
    );


snapshotCaptureRunnerCheck(
    'Real capture accounting remains consistent',
    $playersConsidered
    ===
    (
        $inserted
        +
        $existing
        +
        $skipped
    )
);


snapshotCaptureRunnerCheck(
    'Real capture does not duplicate existing gameweek snapshots',
    $inserted === 0
);


snapshotCaptureRunnerCheck(
    'Real capture recognises existing snapshots',
    $existing > 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * RUNNER DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Capture Runner Diagnostic<br>";
echo "============================================<br><br>";


echo "Capture Status: "
    . htmlspecialchars(
        (string) (
            $result[
                'status'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Gameweek: GW"
    . (
        $result[
            'fpl_gameweek_id'
        ]
        ?? '—'
    )
    . "<br>";


echo "Players Considered: "
    . number_format(
        $playersConsidered
    )
    . "<br>";


echo "Inserted: "
    . number_format(
        $inserted
    )
    . "<br>";


echo "Existing: "
    . number_format(
        $existing
    )
    . "<br>";


echo "Skipped: "
    . number_format(
        $skipped
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Gameweek Snapshot Capture Runner Test Summary<br>";
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