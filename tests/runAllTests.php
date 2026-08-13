<?php

/**
 * FPL Intelligence - Complete Test Suite Runner
 *
 * Automatically discovers every *Test.php file in this
 * directory and executes each test in its own PHP process.
 *
 * Running tests separately prevents duplicate helper
 * functions/classes from interfering with each other.
 */

declare(strict_types=1);


/*
 * ============================================================
 * CONFIGURATION
 * ============================================================
 */

$testsDirectory = __DIR__;
$runnerFile = basename(__FILE__);


/*
 * ============================================================
 * LOCATE PHP CLI
 * ============================================================
 *
 * WAMP's Apache PHP configuration does not necessarily point
 * to the standalone PHP installation directory.
 *
 * We therefore search WAMP's PHP directory and prefer the
 * installation matching the currently running PHP version.
 */

$wampPhpRoot =
    'C:\\wamp64\\bin\\php';


$currentPhpVersion =
    PHP_MAJOR_VERSION
    . '.'
    . PHP_MINOR_VERSION
    . '.'
    . PHP_RELEASE_VERSION;


$preferredDirectory =
    $wampPhpRoot
    . DIRECTORY_SEPARATOR
    . 'php'
    . $currentPhpVersion;


$preferredExecutable =
    $preferredDirectory
    . DIRECTORY_SEPARATOR
    . 'php.exe';


/*
 * First try the PHP version currently used by Apache.
 */
if (is_file($preferredExecutable)) {

    $phpExecutable =
        $preferredExecutable;

} else {

    /*
     * Fall back to searching all installed WAMP
     * PHP versions.
     */
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
            'No PHP CLI executable could be found inside '
            . htmlspecialchars(
                $wampPhpRoot,
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }


    /*
     * Sort naturally so newer PHP versions appear last.
     */
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
 * FIND TEST FILES
 * ============================================================
 */

$testFiles =
    glob(
        $testsDirectory
        . DIRECTORY_SEPARATOR
        . '*Test.php'
    );


if ($testFiles === false) {

    $testFiles = [];
}


/*
 * Ensure the runner itself can never accidentally
 * be included.
 */

$testFiles =
    array_filter(
        $testFiles,
        static function (
            string $file
        ) use (
            $runnerFile
        ): bool {

            return
                basename($file)
                !==
                $runnerFile;
        }
    );


sort(
    $testFiles,
    SORT_NATURAL | SORT_FLAG_CASE
);


/*
 * ============================================================
 * HTML
 * ============================================================
 */

echo '<!DOCTYPE html>';

echo '<html lang="en">';

echo '<head>';

echo '<meta charset="UTF-8">';

echo '<meta name="viewport" '
    . 'content="width=device-width, initial-scale=1.0">';

echo '<title>FPL Intelligence Test Suite</title>';


echo '<style>

body {

    margin: 0;

    padding: 30px;

    background: #f4f4f4;

    color: #222;

    font-family: Arial, sans-serif;
}


.container {

    max-width: 1300px;

    margin: 0 auto;
}


h1 {

    margin-bottom: 5px;
}


.summary {

    margin: 20px 0;

    padding: 20px;

    background: #fff;

    border: 1px solid #ddd;

    border-radius: 6px;
}


.test {

    margin-bottom: 15px;

    background: #fff;

    border: 1px solid #ddd;

    border-radius: 6px;

    overflow: hidden;
}


.test-header {

    padding: 12px 15px;

    background: #eee;

    font-weight: bold;
}


.output {

    padding: 15px;

    background: #fafafa;
}


.pass {

    color: #16803c;
}


.fail {

    color: #c62828;
}


.error {

    color: #b26a00;
}


pre {

    margin: 10px 0 0;

    white-space: pre-wrap;

    word-break: break-word;

    font-family: Consolas, monospace;

    font-size: 13px;

    color: #222;
}


.final-result {

    font-size: 20px;

    font-weight: bold;
}

</style>';


echo '</head>';

echo '<body>';

echo '<div class="container">';


echo '<h1>FPL Intelligence Test Suite</h1>';

echo '<p>';
echo 'Automatically discovered and executed test files.';
echo '</p>';


echo '<div class="summary">';

echo '<strong>PHP CLI:</strong> ';

echo htmlspecialchars(
    $phpExecutable,
    ENT_QUOTES,
    'UTF-8'
);

echo '<br>';

echo '<strong>Tests discovered:</strong> ';

echo count($testFiles);

echo '</div>';


/*
 * ============================================================
 * COUNTERS
 * ============================================================
 */

$totalTestFiles =
    count($testFiles);

$passedTestFiles = 0;

$failedTestFiles = 0;

$errorTestFiles = 0;

$totalAssertionsPassed = 0;

$totalAssertionsFailed = 0;


/*
 * ============================================================
 * RUN TESTS
 * ============================================================
 */

foreach ($testFiles as $testFile) {

    $testName =
        basename($testFile);


    /*
     * Each test executes in its OWN PHP process.
     */

    $command =
        escapeshellarg(
            $phpExecutable
        )
        . ' '
        . escapeshellarg(
            $testFile
        );


    $descriptorSpec = [

        0 => [
            'pipe',
            'r'
        ],

        1 => [
            'pipe',
            'w'
        ],

        2 => [
            'pipe',
            'w'
        ]
    ];


    $process =
        proc_open(
            $command,
            $descriptorSpec,
            $pipes,
            $testsDirectory
        );


    echo '<div class="test">';

    echo '<div class="test-header">';

    echo htmlspecialchars(
        $testName,
        ENT_QUOTES,
        'UTF-8'
    );

    echo '</div>';


    if (!is_resource($process)) {

        $errorTestFiles++;

        echo '<div class="output error">';

        echo '<strong>ERROR</strong>';

        echo '<pre>';
        echo 'Unable to start PHP CLI process.';
        echo '</pre>';

        echo '</div>';

        echo '</div>';

        continue;
    }


    /*
     * No stdin is required.
     */

    fclose(
        $pipes[0]
    );


    /*
     * Capture normal output.
     */

    $stdout =
        stream_get_contents(
            $pipes[1]
        );

    fclose(
        $pipes[1]
    );


    /*
     * Capture PHP errors.
     */

    $stderr =
        stream_get_contents(
            $pipes[2]
        );

    fclose(
        $pipes[2]
    );


    $exitCode =
        proc_close(
            $process
        );


    /*
     * --------------------------------------------------------
     * Extract assertion counts where available.
     * --------------------------------------------------------
     */

    if (
        preg_match(
            '/Passed:\s*(\d+)/i',
            $stdout,
            $passedMatch
        )
    ) {

        $totalAssertionsPassed +=
            (int) $passedMatch[1];
    }


    if (
        preg_match(
            '/Failed:\s*(\d+)/i',
            $stdout,
            $failedMatch
        )
    ) {

        $totalAssertionsFailed +=
            (int) $failedMatch[1];
    }


    /*
     * --------------------------------------------------------
     * Determine result.
     * --------------------------------------------------------
     */

    $passed =
        strpos(
            $stdout,
            'RESULT: ALL TESTS PASSED'
        ) !== false;


    $failed =
        strpos(
            $stdout,
            'RESULT: TESTS FAILED'
        ) !== false;


    if (
        $passed
        &&
        $exitCode === 0
        &&
        trim($stderr) === ''
    ) {

        $passedTestFiles++;


        echo '<div class="output pass">';

        echo '<strong>PASS ✅</strong>';

        echo '<pre>';

        echo htmlspecialchars(
            strip_tags(
                str_replace(
                    '<br>',
                    "\n",
                    $stdout
                )
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        echo '</pre>';

        echo '</div>';

    } elseif ($failed) {

        $failedTestFiles++;


        echo '<div class="output fail">';

        echo '<strong>FAIL ❌</strong>';

        echo '<pre>';

        echo htmlspecialchars(
            strip_tags(
                str_replace(
                    '<br>',
                    "\n",
                    $stdout
                )
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        echo '</pre>';


        if (
            trim($stderr) !== ''
        ) {

            echo '<pre>';

            echo htmlspecialchars(
                $stderr,
                ENT_QUOTES,
                'UTF-8'
            );

            echo '</pre>';
        }


        echo '</div>';

    } else {

        $errorTestFiles++;


        echo '<div class="output error">';

        echo '<strong>ERROR ⚠️</strong>';


        if (
            trim($stdout) !== ''
        ) {

            echo '<pre>';

            echo htmlspecialchars(
                strip_tags(
                    str_replace(
                        '<br>',
                        "\n",
                        $stdout
                    )
                ),
                ENT_QUOTES,
                'UTF-8'
            );

            echo '</pre>';
        }


        if (
            trim($stderr) !== ''
        ) {

            echo '<pre>';

            echo htmlspecialchars(
                $stderr,
                ENT_QUOTES,
                'UTF-8'
            );

            echo '</pre>';
        }


        echo '<pre>';

        echo 'Exit Code: '
            . $exitCode;

        echo '</pre>';


        echo '</div>';
    }


    echo '</div>';
}


/*
 * ============================================================
 * FINAL SUMMARY
 * ============================================================
 */

echo '<div class="summary">';

echo '<h2>Complete Test Suite Summary</h2>';


echo '<strong>Test files:</strong> ';

echo $totalTestFiles;

echo '<br>';


echo '<strong>Test files passed:</strong> ';

echo $passedTestFiles;

echo '<br>';


echo '<strong>Test files failed:</strong> ';

echo $failedTestFiles;

echo '<br>';


echo '<strong>Test files with errors:</strong> ';

echo $errorTestFiles;

echo '<br><br>';


echo '<strong>Total assertions passed:</strong> ';

echo $totalAssertionsPassed;

echo '<br>';


echo '<strong>Total assertions failed:</strong> ';

echo $totalAssertionsFailed;

echo '<br><br>';


if (
    $failedTestFiles === 0
    &&
    $errorTestFiles === 0
) {

    echo '<div class="final-result pass">';

    echo 'RESULT: COMPLETE TEST SUITE PASSED ✅';

    echo '</div>';

} else {

    echo '<div class="final-result fail">';

    echo 'RESULT: TEST SUITE FAILED ❌';

    echo '</div>';
}


echo '</div>';


echo '</div>';

echo '</body>';

echo '</html>';