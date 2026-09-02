<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Capture Gate Test<br>";
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

function snapshotCaptureGateCheck(
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


$gate =
    new PlayerGameweekSnapshotCaptureGate();


/*
 * ============================================================
 * SCENARIO A
 * BATCH IMPORT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Batch Import<br>";
echo "============================================<br>";


snapshotCaptureGateCheck(
    'Batch import does not allow snapshot capture',
    $gate->shouldCapture(
        false,
        25,
        25,
        0
    )
    ===
    false
);


snapshotCaptureGateCheck(
    'Complete batch import still does not allow snapshot capture',
    $gate->shouldCapture(
        false,
        629,
        629,
        0
    )
    ===
    false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * SUCCESSFUL FULL IMPORT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Successful Full Import<br>";
echo "============================================<br>";


snapshotCaptureGateCheck(
    'Successful full import allows snapshot capture',
    $gate->shouldCapture(
        true,
        629,
        629,
        0
    )
    ===
    true
);


snapshotCaptureGateCheck(
    'Successful smaller synthetic full import allows capture',
    $gate->shouldCapture(
        true,
        10,
        10,
        0
    )
    ===
    true
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * FAILED FULL IMPORT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Failed Full Import<br>";
echo "============================================<br>";


snapshotCaptureGateCheck(
    'Full import with failed player does not allow snapshot capture',
    $gate->shouldCapture(
        true,
        629,
        628,
        1
    )
    ===
    false
);


snapshotCaptureGateCheck(
    'Full import with multiple failures does not allow snapshot capture',
    $gate->shouldCapture(
        true,
        629,
        625,
        4
    )
    ===
    false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * INCOMPLETE FULL IMPORT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Incomplete Full Import<br>";
echo "============================================<br>";


snapshotCaptureGateCheck(
    'Incomplete full import does not allow snapshot capture',
    $gate->shouldCapture(
        true,
        629,
        628,
        0
    )
    ===
    false
);


snapshotCaptureGateCheck(
    'Partially processed full import does not allow snapshot capture',
    $gate->shouldCapture(
        true,
        629,
        300,
        0
    )
    ===
    false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * EMPTY IMPORT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Empty Full Import<br>";
echo "============================================<br>";


snapshotCaptureGateCheck(
    'Empty full import does not allow snapshot capture',
    $gate->shouldCapture(
        true,
        0,
        0,
        0
    )
    ===
    false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * STRICT COMPLETION CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Strict Completion Contract<br>";
echo "============================================<br>";


snapshotCaptureGateCheck(
    'Processed count greater than selected does not allow capture',
    $gate->shouldCapture(
        true,
        629,
        630,
        0
    )
    ===
    false
);


snapshotCaptureGateCheck(
    'Failure count blocks capture even when processed count matches',
    $gate->shouldCapture(
        true,
        629,
        629,
        1
    )
    ===
    false
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Gameweek Snapshot Capture Gate Test Summary<br>";
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