<?php

// Keep headers available while this test exercises repeated session starts.
ob_start();

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Manager Context Integration Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * ASSERTION HELPER
 * ============================================================
 */

function managerContextIntegrationTest(
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
 * SOURCE
 * ============================================================
 */

$includePath =
    __DIR__
    . '/../public/includes/manager-context.php';


$source =
    is_file(
        $includePath
    )
        ? file_get_contents(
            $includePath
        )
        : false;


$source =
    is_string(
        $source
    )
        ? $source
        : '';


/*
 * ============================================================
 * A. SHARED INTEGRATION BOUNDARY
 * ============================================================
 */

echo "============================================<br>";
echo "A. Shared Integration Boundary<br>";
echo "============================================<br>";


managerContextIntegrationTest(
    'Shared manager-context include exists',
    is_file(
        $includePath
    )
);


managerContextIntegrationTest(
    'Shared manager-context include uses ManagerContextService',
    str_contains(
        $source,
        'ManagerContextService'
    )
);


managerContextIntegrationTest(
    'Shared manager-context include initialises PHP session state',
    str_contains(
        $source,
        'session_start'
    )
);


managerContextIntegrationTest(
    'Shared manager-context include reads entry_id request context',
    str_contains(
        $source,
        "'entry_id'"
    )
    ||
    str_contains(
        $source,
        '"entry_id"'
    )
);


echo "<br>";


/*
 * ============================================================
 * B. EXPLICIT ENTRY RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "B. Explicit Entry Resolution<br>";
echo "============================================<br>";


if (
    session_status()
    ===
    PHP_SESSION_ACTIVE
) {

    session_write_close();
}


$originalGet =
    $_GET;

$originalPost =
    $_POST;


$originalSession =
    $_SESSION
    ?? [];


$_GET = [];

$_POST = [

    'entry_id' =>
        '2702264'
];


$_SESSION =
    [];


$activeManagerEntryId =
    null;


if (
    is_file(
        $includePath
    )
) {

    require $includePath;
}


managerContextIntegrationTest(
    'Explicit request entry ID becomes the active manager context',
    $activeManagerEntryId === 2702264
);


managerContextIntegrationTest(
    'Explicit request entry ID is preserved in session state',
    (
        $_SESSION[
            'fpl_entry_id'
        ]
        ?? null
    )
    ===
    2702264
);


if (
    session_status()
    ===
    PHP_SESSION_ACTIVE
) {

    session_write_close();
}


echo "<br>";


/*
 * ============================================================
 * C. PRESERVED ENTRY RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "C. Preserved Entry Resolution<br>";
echo "============================================<br>";


$_GET =
    [];

$_POST =
    [];


$_SESSION = [

    'fpl_entry_id' =>
        2702264
];


$activeManagerEntryId =
    null;


if (
    is_file(
        $includePath
    )
) {

    require $includePath;
}


managerContextIntegrationTest(
    'Preserved manager context is reused without an entry_id query parameter',
    $activeManagerEntryId === 2702264
);


managerContextIntegrationTest(
    'Preserved manager context remains available in session state',
    (
        $_SESSION[
            'fpl_entry_id'
        ]
        ?? null
    )
    ===
    2702264
);


if (
    session_status()
    ===
    PHP_SESSION_ACTIVE
) {

    session_write_close();
}


echo "<br>";


/*
 * ============================================================
 * D. INVALID REQUEST FALLBACK
 * ============================================================
 */

echo "============================================<br>";
echo "D. Invalid Request Fallback<br>";
echo "============================================<br>";


$_GET = [];

$_POST = [

    'entry_id' =>
        'invalid'
];


$_SESSION = [

    'fpl_entry_id' =>
        2702264
];


$activeManagerEntryId =
    null;


if (
    is_file(
        $includePath
    )
) {

    require $includePath;
}


managerContextIntegrationTest(
    'Invalid request input falls back to preserved manager context',
    $activeManagerEntryId === 2702264
);


managerContextIntegrationTest(
    'Invalid request input does not overwrite preserved manager context',
    (
        $_SESSION[
            'fpl_entry_id'
        ]
        ?? null
    )
    ===
    2702264
);


if (
    session_status()
    ===
    PHP_SESSION_ACTIVE
) {

    session_write_close();
}


/*
 * ============================================================
 * RESTORE GLOBAL STATE
 * ============================================================
 */

$_GET =
    $originalGet;

$_POST =
    $originalPost;


$_SESSION =
    $originalSession;


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Summary<br>";
echo "============================================<br>";


echo "Assertions passed: {$passed}<br>";
echo "Assertions failed: {$failed}<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}
// Flush captured browser-compatible test output.
ob_end_flush();
