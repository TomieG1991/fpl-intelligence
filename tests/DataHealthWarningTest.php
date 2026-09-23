<?php

echo "============================================<br>";
echo "Data Health Warning Test<br>";
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

function dataHealthWarningCheck(
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


function dataHealthWarningSection(
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


function renderDataHealthWarning(
    array $health
): string {

    $dataHealth =
        $health;


    ob_start();


    require __DIR__
        . '/../public/includes/data-health-warning.php';


    return (string) ob_get_clean();
}


/*
 * ============================================================
 * SCENARIO A
 * SHARED COMPONENT CONTRACT
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario A: Shared Component Contract'
);


$componentPath =
    __DIR__
    . '/../public/includes/data-health-warning.php';


dataHealthWarningCheck(
    'Shared data-health warning component exists.',
    is_file(
        $componentPath
    )
);


/*
 * Stop cleanly during the controlled RED phase.
 *
 * The remaining scenarios define the component contract but
 * cannot execute until the production include exists.
 */
if (
    !is_file(
        $componentPath
    )
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Data Health Warning Test Summary<br>";
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
 * SCENARIO B
 * HEALTHY DATA
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario B: Healthy Data'
);


$healthyHtml =
    renderDataHealthWarning(
        [
            'bootstrap' => [
                'status' => 'Healthy',
                'reason' =>
                    'Latest update completed successfully.',
                'last_success_at' =>
                    '2026-09-22 08:00:00'
            ],

            'fixtures' => [
                'status' => 'Healthy',
                'reason' =>
                    'Latest update completed successfully.',
                'last_success_at' =>
                    '2026-09-22 08:05:00'
            ]
        ]
    );


dataHealthWarningCheck(
    'Healthy data renders no warning.',
    trim(
        $healthyHtml
    )
    === ''
);


/*
 * ============================================================
 * SCENARIO C
 * STALE DATA
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario C: Stale Data'
);


$staleHtml =
    renderDataHealthWarning(
        [
            'fixtures' => [
                'status' => 'Stale',
                'reason' =>
                    'Latest successful update is stale.',
                'last_success_at' =>
                    '2026-09-20 08:00:00'
            ]
        ]
    );


dataHealthWarningCheck(
    'Stale data renders a warning.',
    stripos(
        $staleHtml,
        'Data may be out of date'
    )
    !== false
);


dataHealthWarningCheck(
    'Stale warning identifies Fixtures.',
    stripos(
        $staleHtml,
        'Fixtures'
    )
    !== false
);


dataHealthWarningCheck(
    'Stale warning exposes the last successful update.',
    stripos(
        $staleHtml,
        '2026-09-20 08:00:00'
    )
    !== false
);


/*
 * ============================================================
 * SCENARIO D
 * FAILED DATA UPDATE
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario D: Failed Data Update'
);


$failedHtml =
    renderDataHealthWarning(
        [
            'bootstrap' => [
                'status' => 'Failed',
                'reason' =>
                    'Latest update failed.',
                'last_success_at' =>
                    '2026-09-22 07:00:00'
            ]
        ]
    );


dataHealthWarningCheck(
    'Failed update renders a warning.',
    stripos(
        $failedHtml,
        'Data update issue'
    )
    !== false
);


dataHealthWarningCheck(
    'Failed warning identifies Bootstrap.',
    stripos(
        $failedHtml,
        'Bootstrap'
    )
    !== false
);


/*
 * ============================================================
 * SCENARIO E
 * PARTIAL DATA UPDATE
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario E: Partial Data Update'
);


$partialHtml =
    renderDataHealthWarning(
        [
            'player_fixture_history' => [
                'status' => 'Partial',
                'reason' =>
                    'Latest update completed only partially.',
                'last_success_at' =>
                    '2026-09-22 06:00:00'
            ]
        ]
    );


dataHealthWarningCheck(
    'Partial update renders a warning.',
    stripos(
        $partialHtml,
        'Data update issue'
    )
    !== false
);


dataHealthWarningCheck(
    'Partial warning identifies Player Fixture History.',
    stripos(
        $partialHtml,
        'Player Fixture History'
    )
    !== false
);


/*
 * ============================================================
 * SCENARIO F
 * UNAVAILABLE HEALTH
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario F: Unavailable Health'
);


$unavailableHtml =
    renderDataHealthWarning(
        [
            'fixtures' => [
                'status' => 'Unavailable',
                'reason' =>
                    'No update history is available.',
                'last_success_at' =>
                    null
            ]
        ]
    );


dataHealthWarningCheck(
    'Unavailable health renders a warning.',
    stripos(
        $unavailableHtml,
        'Data health unavailable'
    )
    !== false
);


/*
 * ============================================================
 * SCENARIO G
 * RUNNING UPDATE
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario G: Running Update'
);


$runningHtml =
    renderDataHealthWarning(
        [
            'fixtures' => [
                'status' => 'Running',
                'reason' =>
                    'Update is currently running.',
                'last_success_at' =>
                    '2026-09-22 08:00:00'
            ]
        ]
    );


dataHealthWarningCheck(
    'Running update renders an informational notice.',
    stripos(
        $runningHtml,
        'Data update in progress'
    )
    !== false
);


/*
 * ============================================================
 * SCENARIO H
 * MIXED HEALTH
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario H: Mixed Health'
);


$mixedHtml =
    renderDataHealthWarning(
        [
            'bootstrap' => [
                'status' => 'Healthy',
                'reason' =>
                    'Latest update completed successfully.',
                'last_success_at' =>
                    '2026-09-22 08:00:00'
            ],

            'fixtures' => [
                'status' => 'Stale',
                'reason' =>
                    'Latest successful update is stale.',
                'last_success_at' =>
                    '2026-09-20 08:00:00'
            ],

            'player_fixture_history' => [
                'status' => 'Failed',
                'reason' =>
                    'Latest update failed.',
                'last_success_at' =>
                    '2026-09-22 07:00:00'
            ]
        ]
    );


dataHealthWarningCheck(
    'Mixed health renders degraded feeds.',
    stripos(
        $mixedHtml,
        'Fixtures'
    )
    !== false
    &&
    stripos(
        $mixedHtml,
        'Player Fixture History'
    )
    !== false
);


dataHealthWarningCheck(
    'Mixed health does not list healthy feeds.',
    stripos(
        $mixedHtml,
        'Bootstrap'
    )
    === false
);


/*
 * ============================================================
 * SCENARIO I
 * DASHBOARD NAVIGATION
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario I: Dashboard Navigation'
);


dataHealthWarningCheck(
    'Degraded health links to detailed Dashboard Data Health.',
    stripos(
        $mixedHtml,
        'index.php#data-health-title'
    )
    !== false
);


/*
 * ============================================================
 * SCENARIO J
 * SAFE OUTPUT
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario J: Safe Output'
);


$unsafeHtml =
    renderDataHealthWarning(
        [
            '<script>alert(1)</script>' => [
                'status' => 'Stale',
                'reason' =>
                    '<script>alert(2)</script>',
                'last_success_at' =>
                    '<script>alert(3)</script>'
            ]
        ]
    );


dataHealthWarningCheck(
    'Health warning escapes dynamic output.',
    stripos(
        $unsafeHtml,
        '<script>'
    )
    === false
);


/*
 * ============================================================
 * SCENARIO K
 * SHARED FRESHNESS CONFIGURATION
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario K: Shared Freshness Configuration'
);


$config =
    require __DIR__
        . '/../config/config.php';


dataHealthWarningCheck(
    'Application config defines data-health settings.',
    isset(
        $config[
            'data_health'
        ]
    )
    &&
    is_array(
        $config[
            'data_health'
        ]
    )
);


dataHealthWarningCheck(
    'Data-health config defines a positive freshness threshold.',
    isset(
        $config[
            'data_health'
        ][
            'freshness_seconds'
        ]
    )
    &&
    is_int(
        $config[
            'data_health'
        ][
            'freshness_seconds'
        ]
    )
    &&
    $config[
        'data_health'
    ][
        'freshness_seconds'
    ]
    > 0
);


dataHealthWarningCheck(
    'Data-health freshness threshold preserves the existing 24-hour policy.',
    (
        $config[
            'data_health'
        ][
            'freshness_seconds'
        ]
        ?? null
    )
    ===
    86400
);


/*
 * ============================================================
 * SCENARIO L
 * DASHBOARD SHARED FRESHNESS POLICY
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario L: Dashboard Shared Freshness Policy'
);


$dashboardSource =
    file_get_contents(
        __DIR__
        . '/../public/index.php'
    );


dataHealthWarningCheck(
    'Dashboard loads the shared application configuration.',
    strpos(
        $dashboardSource,
        "config/config.php"
    )
    !== false
);


dataHealthWarningCheck(
    'Dashboard uses the shared data-health freshness threshold.',
    preg_match(
        "/\\\$config\\s*\\[\\s*['\"]data_health['\"]\\s*\\]"
        . "\\s*\\[\\s*['\"]freshness_seconds['\"]\\s*\\]/s",
        $dashboardSource
    )
    === 1
);


dataHealthWarningCheck(
    'Dashboard no longer hard-codes the 24-hour freshness threshold.',
    !preg_match(
        '/\$healthFreshnessSeconds\s*=\s*86400\s*;/',
        $dashboardSource
    )
);


/*
 * ============================================================
 * SCENARIO M
 * SHARED WARNING PRESENTATION
 * ============================================================
 */

dataHealthWarningSection(
    'Scenario M: Shared Warning Presentation'
);


$stylesheetSource =
    file_get_contents(
        __DIR__
        . '/../public/assets/css/app.css'
    );


dataHealthWarningCheck(
    'Application stylesheet defines the shared degraded-data warning.',
    preg_match(
        '/\.data-health-warning\s*\{/',
        $stylesheetSource
    )
    === 1
);


dataHealthWarningCheck(
    'Application stylesheet defines the shared running-update notice.',
    preg_match(
        '/\.data-health-notice\s*\{/',
        $stylesheetSource
    )
    === 1
);


dataHealthWarningCheck(
    'Shared health presentation styles the warning heading.',
    preg_match(
        '/\.data-health-(?:warning|notice)\s+h2\s*\{/',
        $stylesheetSource
    )
    === 1
);


dataHealthWarningCheck(
    'Shared health presentation styles the degraded-feed list.',
    preg_match(
        '/\.data-health-(?:warning|notice)\s+ul\s*\{/',
        $stylesheetSource
    )
    === 1
);


dataHealthWarningCheck(
    'Shared health presentation styles the Dashboard detail link.',
    preg_match(
        '/\.data-health-(?:warning|notice)\s+a\s*\{/',
        $stylesheetSource
    )
    === 1
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Data Health Warning Test Summary<br>";
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