<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "PHP CLI Executable Locator Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


function phpCliExecutableLocatorCheck(
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


function phpCliExecutableLocatorSection(
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


function phpCliExecutableLocatorSummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "PHP CLI Executable Locator Test Summary<br>";
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
 * TEMPORARY TEST ENVIRONMENT
 * ============================================================
 */

$tempRoot =
    __DIR__
    . DIRECTORY_SEPARATOR
    . 'tmp_php_cli_locator';


$preferredVersion =
    '8.2.3';


$preferredDirectory =
    $tempRoot
    . DIRECTORY_SEPARATOR
    . 'php'
    . $preferredVersion;


$fallbackDirectoryA =
    $tempRoot
    . DIRECTORY_SEPARATOR
    . 'php8.1.0';


$fallbackDirectoryB =
    $tempRoot
    . DIRECTORY_SEPARATOR
    . 'php8.3.1';


$preferredExecutable =
    $preferredDirectory
    . DIRECTORY_SEPARATOR
    . 'php.exe';


$fallbackExecutableA =
    $fallbackDirectoryA
    . DIRECTORY_SEPARATOR
    . 'php.exe';


$fallbackExecutableB =
    $fallbackDirectoryB
    . DIRECTORY_SEPARATOR
    . 'php.exe';


if (!is_dir($tempRoot)) {

    mkdir(
        $tempRoot,
        0777,
        true
    );
}


register_shutdown_function(
    static function () use (
        $preferredExecutable,
        $fallbackExecutableA,
        $fallbackExecutableB,
        $preferredDirectory,
        $fallbackDirectoryA,
        $fallbackDirectoryB,
        $tempRoot
    ): void {

        foreach (
            [
                $preferredExecutable,
                $fallbackExecutableA,
                $fallbackExecutableB
            ]
            as $file
        ) {

            if (is_file($file)) {

                unlink(
                    $file
                );
            }
        }


        foreach (
            [
                $preferredDirectory,
                $fallbackDirectoryA,
                $fallbackDirectoryB,
                $tempRoot
            ]
            as $directory
        ) {

            if (is_dir($directory)) {

                @rmdir(
                    $directory
                );
            }
        }
    }
);


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

phpCliExecutableLocatorSection(
    'Scenario A: Class Contract'
);


phpCliExecutableLocatorCheck(
    'PhpCliExecutableLocator exists.',
    class_exists(
        'PhpCliExecutableLocator'
    )
);


/*
 * ============================================================
 * SCENARIO B
 * LOCATE METHOD CONTRACT
 * ============================================================
 */

phpCliExecutableLocatorSection(
    'Scenario B: Locate Method Contract'
);


$locator =
    new PhpCliExecutableLocator();


$hasLocateMethod =
    method_exists(
        $locator,
        'locate'
    );


phpCliExecutableLocatorCheck(
    'Locator exposes locate().',
    $hasLocateMethod
);


if (!$hasLocateMethod) {

    phpCliExecutableLocatorSummary();

    exit;
}


/*
 * ============================================================
 * SCENARIO C
 * PREFERRED VERSION
 * ============================================================
 */

phpCliExecutableLocatorSection(
    'Scenario C: Preferred Version'
);


mkdir(
    $preferredDirectory,
    0777,
    true
);


file_put_contents(
    $preferredExecutable,
    ''
);


$locatedPreferred =
    $locator->locate(
        $tempRoot,
        $preferredVersion
    );


phpCliExecutableLocatorCheck(
    'Preferred matching PHP version is selected.',
    $locatedPreferred
    ===
    $preferredExecutable
);


/*
 * ============================================================
 * SCENARIO D
 * FALLBACK TO NEWEST INSTALLED VERSION
 * ============================================================
 */

phpCliExecutableLocatorSection(
    'Scenario D: Fallback Version'
);


unlink(
    $preferredExecutable
);


rmdir(
    $preferredDirectory
);


mkdir(
    $fallbackDirectoryA,
    0777,
    true
);


mkdir(
    $fallbackDirectoryB,
    0777,
    true
);


file_put_contents(
    $fallbackExecutableA,
    ''
);


file_put_contents(
    $fallbackExecutableB,
    ''
);


$locatedFallback =
    $locator->locate(
        $tempRoot,
        $preferredVersion
    );


phpCliExecutableLocatorCheck(
    'Newest installed PHP version is selected when preferred version is unavailable.',
    $locatedFallback
    ===
    $fallbackExecutableB
);


/*
 * ============================================================
 * SCENARIO E
 * EMPTY ROOT
 * ============================================================
 */

phpCliExecutableLocatorSection(
    'Scenario E: No PHP Installation'
);


unlink(
    $fallbackExecutableA
);


unlink(
    $fallbackExecutableB
);


rmdir(
    $fallbackDirectoryA
);


rmdir(
    $fallbackDirectoryB
);


$missingInstallationThrows =
    false;


try {

    $locator->locate(
        $tempRoot,
        $preferredVersion
    );

} catch (RuntimeException $exception) {

    $missingInstallationThrows =
        true;
}


phpCliExecutableLocatorCheck(
    'Missing PHP installations throw RuntimeException.',
    $missingInstallationThrows
);


/*
 * ============================================================
 * SCENARIO F
 * INVALID ROOT
 * ============================================================
 */

phpCliExecutableLocatorSection(
    'Scenario F: Invalid Root'
);


$invalidRootThrows =
    false;


try {

    $locator->locate(
        $tempRoot
        . DIRECTORY_SEPARATOR
        . 'does-not-exist',
        $preferredVersion
    );

} catch (InvalidArgumentException $exception) {

    $invalidRootThrows =
        true;
}


phpCliExecutableLocatorCheck(
    'Invalid PHP root throws InvalidArgumentException.',
    $invalidRootThrows
);


/*
 * ============================================================
 * SCENARIO G
 * CURRENT PHP CLI EXECUTABLE
 * ============================================================
 */

phpCliExecutableLocatorSection(
    'Scenario G: Current PHP CLI Executable'
);


$currentExecutable =
    PHP_BINARY;


$currentExecutableLocated =
    false;


try {

    $locatedCurrentExecutable =
        $locator->locate(
            $currentExecutable,
            $preferredVersion
        );


    $currentExecutableLocated =
        $locatedCurrentExecutable
        ===
        $currentExecutable;

} catch (Throwable $exception) {

    $currentExecutableLocated =
        false;
}


phpCliExecutableLocatorCheck(
    'An explicit PHP CLI executable can be used directly.',
    $currentExecutableLocated
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

phpCliExecutableLocatorSummary();