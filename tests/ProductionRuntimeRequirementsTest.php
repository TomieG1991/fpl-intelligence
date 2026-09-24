<?php

echo "============================================<br>";
echo "Production Runtime Requirements Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function productionRuntimeCheck(
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
 * A. PHP VERSION
 * ============================================================
 */

echo "============================================<br>";
echo "A. PHP Version<br>";
echo "============================================<br>";


productionRuntimeCheck(
    'PHP 8.2 or newer is available.',
    PHP_VERSION_ID >= 80200
);


/*
 * ============================================================
 * B. DATABASE SUPPORT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "B. Database Support<br>";
echo "============================================<br>";


productionRuntimeCheck(
    'PDO extension is available.',
    extension_loaded(
        'PDO'
    )
);


productionRuntimeCheck(
    'PDO MySQL driver is available.',
    in_array(
        'mysql',
        PDO::getAvailableDrivers(),
        true
    )
);


/*
 * ============================================================
 * C. APPLICATION RUNTIME SUPPORT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "C. Application Runtime Support<br>";
echo "============================================<br>";


productionRuntimeCheck(
    'JSON support is available.',
    function_exists(
        'json_decode'
    )
    &&
    defined(
        'JSON_THROW_ON_ERROR'
    )
);


productionRuntimeCheck(
    'HTTP stream support is available.',
    function_exists(
        'stream_context_create'
    )
    &&
    function_exists(
        'file_get_contents'
    )
);


productionRuntimeCheck(
    'URL fopen support is enabled for FPL API requests.',
    filter_var(
        ini_get(
            'allow_url_fopen'
        ),
        FILTER_VALIDATE_BOOLEAN
    )
);


productionRuntimeCheck(
    'PHP CLI binary is identifiable.',
    defined(
        'PHP_BINARY'
    )
    &&
    trim(
        (string) PHP_BINARY
    )
    !== ''
);


productionRuntimeCheck(
    'Process execution support is available for coordinated update jobs.',
    function_exists(
        'proc_open'
    )
);


/*
 * ============================================================
 * D. PROJECT DEPLOYMENT BOUNDARIES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "D. Project Deployment Boundaries<br>";
echo "============================================<br>";


$projectRoot =
    dirname(
        __DIR__
    );


productionRuntimeCheck(
    'Public application root exists.',
    is_dir(
        $projectRoot
        . DIRECTORY_SEPARATOR
        . 'public'
    )
);


productionRuntimeCheck(
    'Runtime configuration exists.',
    is_file(
        $projectRoot
        . DIRECTORY_SEPARATOR
        . 'config'
        . DIRECTORY_SEPARATOR
        . 'config.php'
    )
);


productionRuntimeCheck(
    'Database deployment schema exists.',
    is_file(
        $projectRoot
        . DIRECTORY_SEPARATOR
        . 'sql'
        . DIRECTORY_SEPARATOR
        . 'schema.sql'
    )
);


productionRuntimeCheck(
    'Data update production entry point exists.',
    is_file(
        $projectRoot
        . DIRECTORY_SEPARATOR
        . 'cron'
        . DIRECTORY_SEPARATOR
        . 'runDataUpdates.php'
    )
);


productionRuntimeCheck(
    'Historical evidence lifecycle entry point exists.',
    is_file(
        $projectRoot
        . DIRECTORY_SEPARATOR
        . 'cron'
        . DIRECTORY_SEPARATOR
        . 'runHistoricalEvidenceLifecycle.php'
    )
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Production Runtime Requirements Test Summary<br>";
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