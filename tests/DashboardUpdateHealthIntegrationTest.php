<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Dashboard Update Health Integration Test<br>";
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

function dashboardHealthCheck(
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
 * DASHBOARD SOURCE
 * ============================================================
 */

$dashboardPath =
    __DIR__
    . '/../public/index.php';


dashboardHealthCheck(
    'Dashboard file exists.',
    is_file(
        $dashboardPath
    )
);


$dashboardSource =
    is_file(
        $dashboardPath
    )
        ? file_get_contents(
            $dashboardPath
        )
        : false;


dashboardHealthCheck(
    'Dashboard source can be read.',
    is_string(
        $dashboardSource
    )
);


/*
 * ============================================================
 * HEALTH SERVICE WIRING
 * ============================================================
 */

if (is_string($dashboardSource)) {

    dashboardHealthCheck(
        'Dashboard creates UpdateRunRepository.',
        str_contains(
            $dashboardSource,
            'new UpdateRunRepository'
        )
    );


    dashboardHealthCheck(
        'Dashboard creates UpdateHealthService.',
        str_contains(
            $dashboardSource,
            'new UpdateHealthService'
        )
    );


    dashboardHealthCheck(
        'Dashboard evaluates bootstrap health.',
        str_contains(
            $dashboardSource,
            "'bootstrap'"
        )
    );


    dashboardHealthCheck(
        'Dashboard evaluates fixture health.',
        str_contains(
            $dashboardSource,
            "'fixtures'"
        )
    );


    dashboardHealthCheck(
        'Dashboard evaluates player fixture-history health.',
        str_contains(
            $dashboardSource,
            "'player_fixture_history'"
        )
    );


    dashboardHealthCheck(
        'Dashboard renders the update health display.',
        str_contains(
            $dashboardSource,
            "includes/update-health.php"
        )
    );
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Dashboard Update Health Integration Test Summary<br>";
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