<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "PHP CLI Process Executor Test<br>";
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

function phpCliProcessExecutorCheck(
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


function phpCliProcessExecutorSection(
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


function phpCliProcessExecutorSummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "PHP CLI Process Executor Test Summary<br>";
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
 * LOCATE PHP CLI
 * ============================================================
 *
 * This mirrors the proven WAMP discovery approach used by
 * runAllTests.php.
 *
 * The executor itself will NOT be responsible for discovering
 * PHP. It receives an explicit executable path.
 */

$wampPhpRoot =
    'C:\\wamp64\\bin\\php';


$currentPhpVersion =
    PHP_MAJOR_VERSION
    . '.'
    . PHP_MINOR_VERSION
    . '.'
    . PHP_RELEASE_VERSION;


$preferredExecutable =
    $wampPhpRoot
    . DIRECTORY_SEPARATOR
    . 'php'
    . $currentPhpVersion
    . DIRECTORY_SEPARATOR
    . 'php.exe';


if (is_file($preferredExecutable)) {

    $phpExecutable =
        $preferredExecutable;

} else {

    $phpCandidates =
        glob(
            $wampPhpRoot
            . DIRECTORY_SEPARATOR
            . 'php*'
            . DIRECTORY_SEPARATOR
            . 'php.exe'
        );


    if (
        $phpCandidates === false
        ||
        empty($phpCandidates)
    ) {

        die(
            'Unable to locate a PHP CLI executable for testing.'
        );
    }


    natsort(
        $phpCandidates
    );


    $phpCandidates =
        array_values(
            $phpCandidates
        );


    $phpExecutable =
        end(
            $phpCandidates
        );
}


/*
 * ============================================================
 * SYNTHETIC SCRIPT DIRECTORY
 * ============================================================
 *
 * These scripts exist only for this test.
 *
 * No FPL updater, API or genuine application data is touched.
 */

$tempDirectory =
    __DIR__
    . DIRECTORY_SEPARATOR
    . 'tmp_php_cli_process_executor';


if (!is_dir($tempDirectory)) {

    mkdir(
        $tempDirectory,
        0777,
        true
    );
}


$successScript =
    $tempDirectory
    . DIRECTORY_SEPARATOR
    . 'success.php';


$argumentsScript =
    $tempDirectory
    . DIRECTORY_SEPARATOR
    . 'arguments.php';


$stderrScript =
    $tempDirectory
    . DIRECTORY_SEPARATOR
    . 'stderr.php';


$exitScript =
    $tempDirectory
    . DIRECTORY_SEPARATOR
    . 'exit.php';


file_put_contents(
    $successScript,
    <<<'PHP'
<?php

echo 'Synthetic stdout.';
PHP
);


file_put_contents(
    $argumentsScript,
    <<<'PHP'
<?php

echo json_encode(
    array_slice(
        $argv,
        1
    )
);
PHP
);


file_put_contents(
    $stderrScript,
    <<<'PHP'
<?php

echo 'Synthetic normal output.';

fwrite(
    STDERR,
    'Synthetic error output.'
);
PHP
);


file_put_contents(
    $exitScript,
    <<<'PHP'
<?php

fwrite(
    STDERR,
    'Synthetic exit failure.'
);

exit(
    7
);
PHP
);


/*
 * Ensure temporary scripts are removed even if the test exits
 * early during the first RED.
 */

register_shutdown_function(
    static function () use (
        $successScript,
        $argumentsScript,
        $stderrScript,
        $exitScript,
        $tempDirectory
    ): void {

        foreach (
            [
                $successScript,
                $argumentsScript,
                $stderrScript,
                $exitScript
            ]
            as $file
        ) {

            if (is_file($file)) {

                unlink(
                    $file
                );
            }
        }


        if (is_dir($tempDirectory)) {

            rmdir(
                $tempDirectory
            );
        }
    }
);


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

phpCliProcessExecutorSection(
    'Scenario A: Class Contract'
);


phpCliProcessExecutorCheck(
    'PhpCliProcessExecutor exists.',
    class_exists(
        'PhpCliProcessExecutor'
    )
);


/*
 * ============================================================
 * SCENARIO B
 * CALLABLE CONTRACT
 * ============================================================
 */

phpCliProcessExecutorSection(
    'Scenario B: Callable Contract'
);


$executor =
    new PhpCliProcessExecutor(
        $phpExecutable
    );


$executorIsCallable =
    is_callable(
        $executor
    );


phpCliProcessExecutorCheck(
    'PhpCliProcessExecutor instances are callable.',
    $executorIsCallable
);


/*
 * During the initial RED the class deliberately has no
 * invocation behaviour yet.
 *
 * Stop cleanly rather than causing a PHP fatal error.
 */
if (!$executorIsCallable) {

    phpCliProcessExecutorSummary();

    exit;
}


/*
 * ============================================================
 * SCENARIO C
 * SUCCESSFUL PROCESS
 * ============================================================
 */

phpCliProcessExecutorSection(
    'Scenario C: Successful Process'
);


$successResult =
    $executor(
        $successScript
    );


phpCliProcessExecutorCheck(
    'Successful process returns exit code zero.',
    (
        $successResult[
            'exit_code'
        ]
        ?? null
    )
    ===
    0
);


phpCliProcessExecutorCheck(
    'Successful process captures stdout.',
    (
        $successResult[
            'stdout'
        ]
        ?? null
    )
    ===
    'Synthetic stdout.'
);


phpCliProcessExecutorCheck(
    'Successful process has empty stderr.',
    (
        $successResult[
            'stderr'
        ]
        ?? null
    )
    ===
    ''
);


/*
 * ============================================================
 * SCENARIO D
 * ARGUMENT FORWARDING
 * ============================================================
 */

phpCliProcessExecutorSection(
    'Scenario D: Argument Forwarding'
);


$expectedArguments = [

    '--full',

    '--limit=60',

    'value with spaces',

    'ampersand&value'
];


$argumentsResult =
    $executor(
        $argumentsScript,
        $expectedArguments
    );


$decodedArguments =
    json_decode(
        $argumentsResult[
            'stdout'
        ]
        ?? '',
        true
    );


phpCliProcessExecutorCheck(
    'Argument process exits successfully.',
    (
        $argumentsResult[
            'exit_code'
        ]
        ?? null
    )
    ===
    0
);


phpCliProcessExecutorCheck(
    'Arguments are forwarded without corruption.',
    $decodedArguments
    ===
    $expectedArguments
);


/*
 * ============================================================
 * SCENARIO E
 * STDERR CAPTURE
 * ============================================================
 */

phpCliProcessExecutorSection(
    'Scenario E: STDERR Capture'
);


$stderrResult =
    $executor(
        $stderrScript
    );


phpCliProcessExecutorCheck(
    'STDERR process exits successfully.',
    (
        $stderrResult[
            'exit_code'
        ]
        ?? null
    )
    ===
    0
);


phpCliProcessExecutorCheck(
    'STDERR process preserves normal stdout.',
    (
        $stderrResult[
            'stdout'
        ]
        ?? null
    )
    ===
    'Synthetic normal output.'
);


phpCliProcessExecutorCheck(
    'STDERR process captures error output separately.',
    (
        $stderrResult[
            'stderr'
        ]
        ?? null
    )
    ===
    'Synthetic error output.'
);


/*
 * ============================================================
 * SCENARIO F
 * NON-ZERO EXIT CODE
 * ============================================================
 */

phpCliProcessExecutorSection(
    'Scenario F: Non-Zero Exit Code'
);


$exitResult =
    $executor(
        $exitScript
    );


phpCliProcessExecutorCheck(
    'Non-zero process exit code is preserved.',
    (
        $exitResult[
            'exit_code'
        ]
        ?? null
    )
    ===
    7
);


phpCliProcessExecutorCheck(
    'Non-zero process stderr is preserved.',
    (
        $exitResult[
            'stderr'
        ]
        ?? null
    )
    ===
    'Synthetic exit failure.'
);


/*
 * ============================================================
 * SCENARIO G
 * MISSING SCRIPT
 * ============================================================
 */

phpCliProcessExecutorSection(
    'Scenario G: Missing Script'
);


$missingScriptThrew =
    false;


try {

    $executor(
        $tempDirectory
        . DIRECTORY_SEPARATOR
        . 'does-not-exist.php'
    );

} catch (
    InvalidArgumentException $exception
) {

    $missingScriptThrew =
        true;
}


phpCliProcessExecutorCheck(
    'Missing script is rejected.',
    $missingScriptThrew
);


/*
 * ============================================================
 * SCENARIO H
 * INVALID PHP EXECUTABLE
 * ============================================================
 */

phpCliProcessExecutorSection(
    'Scenario H: Invalid PHP Executable'
);


$invalidExecutableThrew =
    false;


try {

    new PhpCliProcessExecutor(
        $tempDirectory
        . DIRECTORY_SEPARATOR
        . 'missing-php.exe'
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidExecutableThrew =
        true;
}


phpCliProcessExecutorCheck(
    'Missing PHP executable is rejected.',
    $invalidExecutableThrew
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

phpCliProcessExecutorSummary();