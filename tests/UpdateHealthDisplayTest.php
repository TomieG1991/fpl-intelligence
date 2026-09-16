<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Update Health Display Test<br>";
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

function updateHealthDisplayCheck(
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


function updateHealthDisplaySection(
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


function updateHealthDisplaySummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Update Health Display Test Summary<br>";
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
 * DISPLAY FILE CONTRACT
 * ============================================================
 */

updateHealthDisplaySection(
    'Scenario A: Display File Contract'
);


$displayPath =
    __DIR__
    . '/../public/includes/update-health.php';


updateHealthDisplayCheck(
    'Update health display exists.',
    is_file(
        $displayPath
    )
);


if (!is_file($displayPath)) {

    updateHealthDisplaySummary();

    exit;
}


/*
 * ============================================================
 * SCENARIO B
 * HEALTH STATES
 * ============================================================
 */

updateHealthDisplaySection(
    'Scenario B: Health States'
);


$updateHealth = [

    'bootstrap' => [

        'update_type' =>
            'bootstrap',

        'status' =>
            'Healthy',

        'reason' =>
            'Latest update completed successfully.',

        'last_success_at' =>
            '2026-09-14 15:02:20',

        'age_seconds' =>
            300,

        'records_received' =>
            716,

        'records_updated' =>
            716,

        'records_skipped' =>
            0,

        'records_failed' =>
            0,

        'duration_ms' =>
            960,

        'error_message' =>
            null
    ],


    'fixtures' => [

        'update_type' =>
            'fixtures',

        'status' =>
            'Stale',

        'reason' =>
            'Latest successful update is stale.',

        'last_success_at' =>
            '2026-09-12 15:02:20',

        'age_seconds' =>
            172800,

        'records_received' =>
            380,

        'records_updated' =>
            380,

        'records_skipped' =>
            0,

        'records_failed' =>
            0,

        'duration_ms' =>
            565,

        'error_message' =>
            null
    ],


    'player_fixture_history' => [

        'update_type' =>
            'player_fixture_history',

        'status' =>
            'Partial',

        'reason' =>
            'Latest update completed only partially.',

        'last_success_at' =>
            '2026-09-14 15:04:34',

        'age_seconds' =>
            166,

        'records_received' =>
            658,

        'records_updated' =>
            2548,

        'records_skipped' =>
            0,

        'records_failed' =>
            2,

        'duration_ms' =>
            133735,

        'error_message' =>
            'Two players could not be updated.'
    ]
];


ob_start();

require $displayPath;

$output =
    ob_get_clean();


updateHealthDisplayCheck(
    'Display contains Data Health heading.',
    str_contains(
        $output,
        'Data Health'
    )
);


updateHealthDisplayCheck(
    'Display contains Bootstrap label.',
    str_contains(
        $output,
        'Bootstrap'
    )
);


updateHealthDisplayCheck(
    'Display contains Fixtures label.',
    str_contains(
        $output,
        'Fixtures'
    )
);


updateHealthDisplayCheck(
    'Display contains Player Fixture History label.',
    str_contains(
        $output,
        'Player Fixture History'
    )
);


updateHealthDisplayCheck(
    'Display renders Healthy status.',
    str_contains(
        $output,
        'Healthy'
    )
);


updateHealthDisplayCheck(
    'Display renders Stale status.',
    str_contains(
        $output,
        'Stale'
    )
);


updateHealthDisplayCheck(
    'Display renders Partial status.',
    str_contains(
        $output,
        'Partial'
    )
);


updateHealthDisplayCheck(
    'Display renders latest successful update timestamp.',
    str_contains(
        $output,
        '2026-09-14 15:02:20'
    )
);


updateHealthDisplayCheck(
    'Display renders update record metadata.',
    str_contains(
        $output,
        '716'
    )
    &&
    str_contains(
        $output,
        '2,548'
    )
);


updateHealthDisplayCheck(
    'Display renders failure metadata.',
    str_contains(
        $output,
        '2'
    )
);


updateHealthDisplayCheck(
    'Display renders error message when available.',
    str_contains(
        $output,
        'Two players could not be updated.'
    )
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

updateHealthDisplaySummary();