<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Historical Evidence Process Runner Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


function historicalProcessCheck(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


function historicalProcessSection(
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


/*
 * ============================================================
 * A. CLASS CONTRACT
 * ============================================================
 */

historicalProcessSection(
    'A. Class Contract'
);


$classExists =
    class_exists(
        'HistoricalEvidenceProcessRunner'
    );


historicalProcessCheck(
    $classExists,
    'HistoricalEvidenceProcessRunner exists.'
);


if (!$classExists) {

    echo "<br>";
    echo "<strong>EXPECTED RED: historical evidence process runner does not exist yet.</strong><br>";

    echo "<br>";
    echo "============================================<br>";
    echo "Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "<strong>RESULT: EXPECTED RED ❌</strong><br>";

    exit;
}


/*
 * ============================================================
 * B. SUCCESSFUL PROCESS
 * ============================================================
 */

historicalProcessSection(
    'B. Successful Process'
);


$calls = [];


$successExecutor =
    static function (
        string $scriptPath,
        array $arguments = []
    ) use (
        &$calls
    ): array {

        $calls[] = [
            'script_path' =>
                $scriptPath,

            'arguments' =>
                $arguments
        ];


        return [
            'exit_code' =>
                0,

            'stdout' =>
                "RESULT: RECOMMENDATION PROMOTION COMPLETE",

            'stderr' =>
                ''
        ];
    };


$runner =
    new HistoricalEvidenceProcessRunner(
        $successExecutor
    );


$result =
    $runner->run(
        'recommendation_promotion',
        'syntheticPromotion.php'
    );


historicalProcessCheck(
    $calls === [
        [
            'script_path' =>
                'syntheticPromotion.php',

            'arguments' =>
                []
        ]
    ],
    'Process runner invokes executor exactly once with unchanged script path.'
);


historicalProcessCheck(
    ($result['status'] ?? null) === 'Success',
    'Zero exit code returns Success.'
);


historicalProcessCheck(
    ($result['step_name'] ?? null)
    ===
    'recommendation_promotion',
    'Successful result preserves lifecycle step name.'
);


historicalProcessCheck(
    ($result['stdout'] ?? null)
    ===
    "RESULT: RECOMMENDATION PROMOTION COMPLETE",
    'Successful result preserves stdout.'
);


/*
 * ============================================================
 * C. NON-ZERO EXIT
 * ============================================================
 */

historicalProcessSection(
    'C. Non-Zero Exit'
);


$failureExecutor =
    static function (
        string $scriptPath,
        array $arguments = []
    ): array {

        return [
            'exit_code' =>
                1,

            'stdout' =>
                'RESULT: PLAYER SNAPSHOT PROMOTION FAILED',

            'stderr' =>
                'Synthetic process failure.'
        ];
    };


$runner =
    new HistoricalEvidenceProcessRunner(
        $failureExecutor
    );


$result =
    $runner->run(
        'player_snapshot_promotion',
        'syntheticFailure.php'
    );


historicalProcessCheck(
    ($result['status'] ?? null) === 'Failed',
    'Non-zero exit code returns Failed.'
);


historicalProcessCheck(
    ($result['step_name'] ?? null)
    ===
    'player_snapshot_promotion',
    'Failed result preserves lifecycle step name.'
);


historicalProcessCheck(
    ($result['error_message'] ?? null)
    ===
    'Synthetic process failure.',
    'Non-zero exit preserves stderr as error message.'
);


/*
 * ============================================================
 * D. NON-ZERO EXIT WITHOUT STDERR
 * ============================================================
 */

historicalProcessSection(
    'D. Non-Zero Exit Without STDERR'
);


$quietFailureExecutor =
    static function (
        string $scriptPath,
        array $arguments = []
    ): array {

        return [
            'exit_code' =>
                7,

            'stdout' =>
                'Synthetic failed output.',

            'stderr' =>
                ''
        ];
    };


$runner =
    new HistoricalEvidenceProcessRunner(
        $quietFailureExecutor
    );


$result =
    $runner->run(
        'player_snapshot_capture',
        'syntheticQuietFailure.php'
    );


historicalProcessCheck(
    ($result['status'] ?? null) === 'Failed',
    'Non-zero exit without stderr still returns Failed.'
);


historicalProcessCheck(
    ($result['error_message'] ?? null)
    ===
    'Historical evidence process exited with code 7.',
    'Non-zero exit without stderr exposes deterministic error message.'
);


/*
 * ============================================================
 * E. EXECUTOR EXCEPTION
 * ============================================================
 */

historicalProcessSection(
    'E. Executor Exception'
);


$exceptionExecutor =
    static function (
        string $scriptPath,
        array $arguments = []
    ): array {

        throw new RuntimeException(
            'Synthetic launch exception.'
        );
    };


$runner =
    new HistoricalEvidenceProcessRunner(
        $exceptionExecutor
    );


$result =
    $runner->run(
        'recommendation_promotion',
        'syntheticException.php'
    );


historicalProcessCheck(
    ($result['status'] ?? null) === 'Failed',
    'Executor exception returns Failed.'
);


historicalProcessCheck(
    ($result['error_message'] ?? null)
    ===
    'Synthetic launch exception.',
    'Executor exception message is preserved.'
);


/*
 * ============================================================
 * F. ARGUMENT FORWARDING
 * ============================================================
 */

historicalProcessSection(
    'F. Argument Forwarding'
);


$capturedArguments = null;


$argumentExecutor =
    static function (
        string $scriptPath,
        array $arguments = []
    ) use (
        &$capturedArguments
    ): array {

        $capturedArguments =
            $arguments;


        return [
            'exit_code' =>
                0,

            'stdout' =>
                '',

            'stderr' =>
                ''
        ];
    };


$runner =
    new HistoricalEvidenceProcessRunner(
        $argumentExecutor
    );


$runner->run(
    'synthetic_step',
    'syntheticArguments.php',
    [
        '--example',
        'value with spaces'
    ]
);


historicalProcessCheck(
    $capturedArguments === [
        '--example',
        'value with spaces'
    ],
    'Process runner forwards supplied CLI arguments unchanged.'
);


/*
 * ============================================================
 * G. INVALID PROCESS RESULT
 * ============================================================
 */

historicalProcessSection(
    'G. Invalid Process Result'
);


$invalidExecutor =
    static function (
        string $scriptPath,
        array $arguments = []
    ): array {

        return [];
    };


$runner =
    new HistoricalEvidenceProcessRunner(
        $invalidExecutor
    );


$result =
    $runner->run(
        'synthetic_step',
        'syntheticInvalid.php'
    );


historicalProcessCheck(
    ($result['status'] ?? null) === 'Failed',
    'Missing process exit code returns Failed.'
);


historicalProcessCheck(
    ($result['error_message'] ?? null)
    ===
    'Historical evidence process did not return a valid exit code.',
    'Missing process exit code exposes explicit error.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Historical Evidence Process Runner Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "<strong>RESULT: ALL TESTS PASSED ✅</strong><br>";

} else {

    echo "<strong>RESULT: TEST FAILURES DETECTED ❌</strong><br>";
}