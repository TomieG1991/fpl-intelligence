<?php

echo "============================================<br>";
echo "Production Web Root Contract Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function productionWebRootCheck(
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
 * PROJECT STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Project Structure<br>";
echo "============================================<br>";


$projectRoot =
    dirname(
        __DIR__
    );


$publicRoot =
    $projectRoot
    . DIRECTORY_SEPARATOR
    . 'public';


productionWebRootCheck(
    'Dedicated public web root exists.',
    is_dir(
        $publicRoot
    )
);


productionWebRootCheck(
    'Public web root contains the application entry point.',
    is_file(
        $publicRoot
        . DIRECTORY_SEPARATOR
        . 'index.php'
    )
);


/*
 * ============================================================
 * NON-PUBLIC APPLICATION AREAS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "B. Non-Public Application Areas<br>";
echo "============================================<br>";


$nonPublicDirectories = [
    'classes',
    'config',
    'cron',
    'sql',
    'tests'
];


foreach (
    $nonPublicDirectories
    as $directory
) {

    productionWebRootCheck(
        $directory
            . ' remains outside the public web root.',
        is_dir(
            $projectRoot
            . DIRECTORY_SEPARATOR
            . $directory
        )
        &&
        !is_dir(
            $publicRoot
            . DIRECTORY_SEPARATOR
            . $directory
        )
    );
}


/*
 * ============================================================
 * PUBLIC ROOT CONTENT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "C. Public Root Content<br>";
echo "============================================<br>";


productionWebRootCheck(
    'Public root does not contain configuration files.',
    !is_dir(
        $publicRoot
        . DIRECTORY_SEPARATOR
        . 'config'
    )
);


productionWebRootCheck(
    'Public root does not contain test files.',
    !is_dir(
        $publicRoot
        . DIRECTORY_SEPARATOR
        . 'tests'
    )
);


productionWebRootCheck(
    'Public root does not contain cron entry points.',
    !is_dir(
        $publicRoot
        . DIRECTORY_SEPARATOR
        . 'cron'
    )
);


productionWebRootCheck(
    'Public root does not contain SQL deployment files.',
    !is_dir(
        $publicRoot
        . DIRECTORY_SEPARATOR
        . 'sql'
    )
);


/*
 * ============================================================
 * APPLICATION ENVIRONMENT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "D. Application Environment<br>";
echo "============================================<br>";


$configPath =
    $projectRoot
    . DIRECTORY_SEPARATOR
    . 'config'
    . DIRECTORY_SEPARATOR
    . 'config.php';


$configSource =
    file_get_contents(
        $configPath
    );


productionWebRootCheck(
    'Application configuration supports an explicit environment setting.',
    $configSource !== false
    &&
    str_contains(
        $configSource,
        "'environment'"
    )
);


productionWebRootCheck(
    'Application environment can be supplied through FPL_APP_ENV.',
    $configSource !== false
    &&
    str_contains(
        $configSource,
        'FPL_APP_ENV'
    )
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Production Web Root Contract Test Summary<br>";
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