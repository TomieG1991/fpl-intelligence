<?php

echo "============================================<br>";
echo "Production Configuration Contract Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function productionConfigurationCheck(
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
 * A. CONFIGURATION SOURCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Configuration Source<br>";
echo "============================================<br>";


$configPath =
    __DIR__
    . '/../config/config.php';


$configSource =
    is_file(
        $configPath
    )
        ? file_get_contents(
            $configPath
        )
        : false;


productionConfigurationCheck(
    'Runtime configuration exists.',
    is_file(
        $configPath
    )
);


productionConfigurationCheck(
    'Runtime configuration source can be read.',
    is_string(
        $configSource
    )
);


$configSource =
    is_string(
        $configSource
    )
        ? $configSource
        : '';


/*
 * ============================================================
 * B. ENVIRONMENT CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "B. Environment Contract<br>";
echo "============================================<br>";


productionConfigurationCheck(
    'Application environment is controlled by FPL_APP_ENV.',
    str_contains(
        $configSource,
        'FPL_APP_ENV'
    )
);


productionConfigurationCheck(
    'Development remains the local default environment.',
    str_contains(
        $configSource,
        "'development'"
    )
);


/*
 * ============================================================
 * C. PRODUCTION DATABASE CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "C. Production Database Contract<br>";
echo "============================================<br>";


productionConfigurationCheck(
    'Production configuration explicitly distinguishes the production environment.',
    preg_match(
        "/['\"]production['\"]/i",
        $configSource
    )
    === 1
);


productionConfigurationCheck(
    'Production database configuration validates required environment variables.',
    preg_match(
        '/FPL_DB_HOST.*FPL_DB_NAME.*FPL_DB_USERNAME.*FPL_DB_PASSWORD/is',
        $configSource
    )
    === 1
    &&
    (
        str_contains(
            $configSource,
            'RuntimeException'
        )
        ||
        str_contains(
            $configSource,
            'throw'
        )
    )
);


productionConfigurationCheck(
    'Development database host fallback remains available.',
    str_contains(
        $configSource,
        "'localhost'"
    )
);


productionConfigurationCheck(
    'Development database name fallback remains available.',
    str_contains(
        $configSource,
        "'fpl_intelligence'"
    )
);


productionConfigurationCheck(
    'Development database username fallback remains available.',
    str_contains(
        $configSource,
        "'root'"
    )
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Production Configuration Contract Test Summary<br>";
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