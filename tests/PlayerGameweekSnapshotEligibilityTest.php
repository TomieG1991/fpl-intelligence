<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Eligibility Test<br>";
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

function snapshotEligibilityCheck(
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
 * DATABASE / REPOSITORIES
 * ============================================================
 */

$database =
    new Database();


$connection =
    $database
        ->getConnection();


$gameweekRepository =
    new GameweekRepository(
        $connection
    );


/*
 * ============================================================
 * SCENARIO A
 * CONTROLLED ELIGIBILITY CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Controlled Eligibility Contract<br>";
echo "============================================<br>";


$eligibilityRule =
    static function (
        array $gameweek
    ): bool {

        return
            !empty(
                $gameweek[
                    'finished'
                ]
                ?? false
            )
            &&
            !empty(
                $gameweek[
                    'data_checked'
                ]
                ?? false
            );
    };


$unfinishedUnchecked = [

    'finished' =>
        false,

    'data_checked' =>
        false
];


$finishedUnchecked = [

    'finished' =>
        true,

    'data_checked' =>
        false
];


$unfinishedChecked = [

    'finished' =>
        false,

    'data_checked' =>
        true
];


$finishedChecked = [

    'finished' =>
        true,

    'data_checked' =>
        true
];


snapshotEligibilityCheck(
    'Unfinished and unchecked gameweek is not eligible',
    $eligibilityRule(
        $unfinishedUnchecked
    )
    ===
    false
);


snapshotEligibilityCheck(
    'Finished but unchecked gameweek is not eligible',
    $eligibilityRule(
        $finishedUnchecked
    )
    ===
    false
);


snapshotEligibilityCheck(
    'Checked but unfinished gameweek is not eligible',
    $eligibilityRule(
        $unfinishedChecked
    )
    ===
    false
);


snapshotEligibilityCheck(
    'Finished and checked gameweek is eligible',
    $eligibilityRule(
        $finishedChecked
    )
    ===
    true
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * REAL GAMEWEEK STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Real Gameweek State<br>";
echo "============================================<br>";


$gameweeks =
    $gameweekRepository
        ->getAll();


snapshotEligibilityCheck(
    'Stored gameweeks are available',
    is_array(
        $gameweeks
    )
    &&
    !empty(
        $gameweeks
    )
);


$eligibleGameweeks =
    array_values(
        array_filter(
            $gameweeks,
            $eligibilityRule
        )
    );


snapshotEligibilityCheck(
    'At least one stored gameweek is snapshot eligible',
    !empty(
        $eligibleGameweeks
    )
);


$latestEligibleGameweek =
    null;


foreach (
    $eligibleGameweeks
    as $gameweek
) {

    if (
        $latestEligibleGameweek === null
        ||
        (
            (int) (
                $gameweek[
                    'fpl_gameweek_id'
                ]
                ?? 0
            )
            >
            (int) (
                $latestEligibleGameweek[
                    'fpl_gameweek_id'
                ]
                ?? 0
            )
        )
    ) {

        $latestEligibleGameweek =
            $gameweek;
    }
}


snapshotEligibilityCheck(
    'Latest eligible stored gameweek resolves',
    is_array(
        $latestEligibleGameweek
    )
);


if (
    is_array(
        $latestEligibleGameweek
    )
) {

    snapshotEligibilityCheck(
        'Latest eligible gameweek is finished',
        !empty(
            $latestEligibleGameweek[
                'finished'
            ]
            ?? false
        )
    );


    snapshotEligibilityCheck(
        'Latest eligible gameweek has checked data',
        !empty(
            $latestEligibleGameweek[
                'data_checked'
            ]
            ?? false
        )
    );


    echo "Latest Eligible Gameweek: GW"
        . (
            $latestEligibleGameweek[
                'fpl_gameweek_id'
            ]
            ?? '—'
        )
        . "<br>";


    echo "Finished: "
        . (
            !empty(
                $latestEligibleGameweek[
                    'finished'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        )
        . "<br>";


    echo "Data Checked: "
        . (
            !empty(
                $latestEligibleGameweek[
                    'data_checked'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * CAPTURE SERVICE FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Capture Service Foundation<br>";
echo "============================================<br>";


$serviceExists =
    class_exists(
        'PlayerGameweekSnapshotCapture'
    );


snapshotEligibilityCheck(
    'PlayerGameweekSnapshotCapture service exists',
    $serviceExists
);


if (
    !$serviceExists
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$capture =
    new PlayerGameweekSnapshotCapture(
        $connection
    );


snapshotEligibilityCheck(
    'Capture service still exposes latest completed capture',
    method_exists(
        $capture,
        'captureLatestCompletedGameweek'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * SERVICE ELIGIBILITY CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Service Eligibility Contract<br>";
echo "============================================<br>";


$eligibilityMethodExists =
    method_exists(
        $capture,
        'isGameweekEligible'
    );


snapshotEligibilityCheck(
    'Capture service exposes isGameweekEligible()',
    $eligibilityMethodExists
);


if (
    !$eligibilityMethodExists
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "PlayerGameweekSnapshotCapture::isGameweekEligible()<br><br>";

    echo "Required rule:<br>";
    echo "finished = false / data_checked = false → not eligible<br>";
    echo "finished = true  / data_checked = false → not eligible<br>";
    echo "finished = false / data_checked = true  → not eligible<br>";
    echo "finished = true  / data_checked = true  → eligible<br><br>";


    echo "============================================<br>";
    echo "Player Gameweek Snapshot Eligibility Test Summary<br>";
    echo "============================================<br>";


    echo "Passed: "
        . $passed
        . "<br>";


    echo "Failed: "
        . $failed
        . "<br><br>";


    echo "RESULT: TESTS FAILED ❌";

    exit;
}


/*
 * ============================================================
 * SCENARIO E
 * CONTROLLED SERVICE ELIGIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Controlled Service Eligibility<br>";
echo "============================================<br>";


snapshotEligibilityCheck(
    'Service rejects unfinished and unchecked gameweek',
    $capture
        ->isGameweekEligible(
            $unfinishedUnchecked
        )
    ===
    false
);


snapshotEligibilityCheck(
    'Service rejects finished but unchecked gameweek',
    $capture
        ->isGameweekEligible(
            $finishedUnchecked
        )
    ===
    false
);


snapshotEligibilityCheck(
    'Service rejects checked but unfinished gameweek',
    $capture
        ->isGameweekEligible(
            $unfinishedChecked
        )
    ===
    false
);


snapshotEligibilityCheck(
    'Service accepts finished and checked gameweek',
    $capture
        ->isGameweekEligible(
            $finishedChecked
        )
    ===
    true
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * REAL SERVICE ELIGIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Real Service Eligibility<br>";
echo "============================================<br>";


$realEligible =
    is_array(
        $latestEligibleGameweek
    )
    &&
    $capture
        ->isGameweekEligible(
            $latestEligibleGameweek
        );


snapshotEligibilityCheck(
    'Service recognises latest real eligible gameweek',
    $realEligible
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * CAPTURE STILL OPERATES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Eligible Capture Regression<br>";
echo "============================================<br>";


$captureResult =
    $capture
        ->captureLatestCompletedGameweek();


snapshotEligibilityCheck(
    'Eligible completed-gameweek capture still completes',
    is_array(
        $captureResult
    )
    &&
    (
        $captureResult[
            'status'
        ]
        ?? null
    )
    ===
    'Complete'
);


snapshotEligibilityCheck(
    'Capture reports finished gameweek',
    !empty(
        $captureResult[
            'finished'
        ]
        ?? false
    )
);


snapshotEligibilityCheck(
    'Capture reports data-checked gameweek',
    !empty(
        $captureResult[
            'data_checked'
        ]
        ?? false
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * ELIGIBILITY DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Snapshot Eligibility Diagnostic<br>";
echo "============================================<br><br>";


echo "Required Eligibility:<br>";
echo "Finished: Yes<br>";
echo "Data Checked: Yes<br><br>";


if (
    is_array(
        $latestEligibleGameweek
    )
) {

    echo "Latest Eligible Gameweek: GW"
        . (
            $latestEligibleGameweek[
                'fpl_gameweek_id'
            ]
            ?? '—'
        )
        . "<br>";
}


echo "Eligible Stored Gameweeks: "
    . number_format(
        count(
            $eligibleGameweeks
        )
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Gameweek Snapshot Eligibility Test Summary<br>";
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