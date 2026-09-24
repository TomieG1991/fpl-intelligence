<?php

echo "============================================<br>";
echo "Data Update Cron Portability Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function dataUpdateCronPortabilityCheck(
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
 * CRON SOURCE
 * ============================================================
 */

$cronPath =
    __DIR__
    . '/../cron/runDataUpdates.php';


$cronExists =
    is_file(
        $cronPath
    );


dataUpdateCronPortabilityCheck(
    'Data update cron exists.',
    $cronExists
);


if (!$cronExists) {

    echo "<br>";
    echo "============================================<br>";
    echo "Data Update Cron Portability Test Summary<br>";
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


$cronSource =
    file_get_contents(
        $cronPath
    );


if ($cronSource === false) {

    die(
        'Data update cron source could not be read.'
    );
}


/*
 * ============================================================
 * PORTABLE PHP CLI
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Portable PHP CLI<br>";
echo "============================================<br>";


dataUpdateCronPortabilityCheck(
    'Data update cron uses the established PHP CLI locator.',
    str_contains(
        $cronSource,
        'new PhpCliExecutableLocator'
    )
);


dataUpdateCronPortabilityCheck(
    'Data update cron uses the current PHP CLI executable portably.',
    str_contains(
        $cronSource,
        'PHP_BINARY'
    )
);


dataUpdateCronPortabilityCheck(
    'Data update cron contains no hard-coded WAMP installation path.',
    !str_contains(
        $cronSource,
        'C:\\\\wamp64'
    )
);


dataUpdateCronPortabilityCheck(
    'Data update cron uses the established PHP CLI process executor.',
    str_contains(
        $cronSource,
        'new PhpCliProcessExecutor'
    )
);


dataUpdateCronPortabilityCheck(
    'Data update cron preserves the established DataUpdateProcessRunner.',
    str_contains(
        $cronSource,
        'new DataUpdateProcessRunner'
    )
);


dataUpdateCronPortabilityCheck(
    'Data update cron preserves the established DataUpdateCoordinatorLauncher.',
    str_contains(
        $cronSource,
        'new DataUpdateCoordinatorLauncher'
    )
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Data Update Cron Portability Test Summary<br>";
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