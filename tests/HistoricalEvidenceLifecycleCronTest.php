<?php

/*
 * ============================================================
 * HISTORICAL EVIDENCE LIFECYCLE CRON TEST
 * ============================================================
 *
 * Protects the dedicated production entry point for the
 * historical-evidence lifecycle.
 *
 * The cron must:
 *
 * - remain separate from the live DataUpdateCoordinator
 * - load the project autoloader
 * - use the established PHP CLI infrastructure
 * - construct HistoricalEvidenceProcessRunner
 * - construct HistoricalEvidenceLifecycleLauncher
 * - execute the launcher exactly once
 * - expose lifecycle step results
 * - report Success and Failed outcomes explicitly
 * - support a controlled test seam so tests never touch real
 *   historical evidence
 */


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed = 0;
$failed = 0;


function historicalLifecycleCronAssert(
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


function historicalLifecycleCronSection(
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
 * A. CRON FILE
 * ============================================================
 */

historicalLifecycleCronSection(
    'A. Historical Lifecycle Cron File'
);


$cronPath =
    __DIR__
    . '/../cron/runHistoricalEvidenceLifecycle.php';


$cronExists =
    is_file(
        $cronPath
    );


historicalLifecycleCronAssert(
    $cronExists,
    'Historical evidence lifecycle cron exists.'
);


if (!$cronExists) {

    echo "<br>";
    echo "<strong>EXPECTED RED: historical evidence lifecycle cron does not exist yet.</strong><br>";

    echo "<br>";
    echo "============================================<br>";
    echo "Historical Evidence Lifecycle Cron Test Summary<br>";
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
 * READ SOURCE
 * ============================================================
 */

$cronSource =
    file_get_contents(
        $cronPath
    );


if ($cronSource === false) {

    die(
        'Historical evidence lifecycle cron source could not be read.'
    );
}


/*
 * ============================================================
 * B. PROJECT BOOTSTRAP
 * ============================================================
 */

historicalLifecycleCronSection(
    'B. Project Bootstrap'
);


historicalLifecycleCronAssert(
    str_contains(
        $cronSource,
        "../classes/autoload.php"
    ),
    'Historical lifecycle cron loads the project autoloader.'
);


historicalLifecycleCronAssert(
    str_contains(
        $cronSource,
        'set_time_limit'
    ),
    'Historical lifecycle cron removes the normal execution time limit.'
);


/*
 * ============================================================
 * C. PHP CLI INFRASTRUCTURE
 * ============================================================
 */

historicalLifecycleCronSection(
    'C. PHP CLI Infrastructure'
);


historicalLifecycleCronAssert(
    str_contains(
        $cronSource,
        'new PhpCliExecutableLocator'
    ),
    'Historical lifecycle cron uses the established PHP CLI locator.'
);


historicalLifecycleCronAssert(
    str_contains(
        $cronSource,
        "'C:\\\\wamp64\\\\bin\\\\php'"
    ),
    'Historical lifecycle cron uses the established WAMP PHP root.'
);


historicalLifecycleCronAssert(
    str_contains(
        $cronSource,
        'new PhpCliProcessExecutor'
    ),
    'Historical lifecycle cron uses the established PHP CLI process executor.'
);


historicalLifecycleCronAssert(
    str_contains(
        $cronSource,
        'new HistoricalEvidenceProcessRunner'
    ),
    'Historical lifecycle cron constructs the historical process adapter.'
);


/*
 * ============================================================
 * D. LIFECYCLE LAUNCHER
 * ============================================================
 */

historicalLifecycleCronSection(
    'D. Lifecycle Launcher'
);


historicalLifecycleCronAssert(
    str_contains(
        $cronSource,
        'new HistoricalEvidenceLifecycleLauncher'
    ),
    'Historical lifecycle cron constructs the lifecycle launcher.'
);


historicalLifecycleCronAssert(
    str_contains(
        $cronSource,
        '$historicalEvidenceLifecycleCronLauncher'
    ),
    'Historical lifecycle cron exposes the controlled launcher test seam.'
);


/*
 * ============================================================
 * TEST DOUBLE
 * ============================================================
 */

class HistoricalEvidenceLifecycleCronLauncherDouble
{
    private array $result;

    private int $calls = 0;


    public function __construct(
        array $result
    ) {

        $this->result =
            $result;
    }


    public function run(): array
    {

        $this->calls++;


        return
            $this->result;
    }


    public function getCalls(): int
    {

        return
            $this->calls;
    }
}


/*
 * ============================================================
 * E. CONTROLLED SUCCESSFUL EXECUTION
 * ============================================================
 */

historicalLifecycleCronSection(
    'E. Controlled Successful Execution'
);


$historicalEvidenceLifecycleCronLauncher =
    new HistoricalEvidenceLifecycleCronLauncherDouble(
        [
            'status' =>
                'Success',

            'steps' => [

                'recommendation_promotion' => [
                    'status' =>
                        'Success'
                ],

                'player_snapshot_promotion' => [
                    'status' =>
                        'Success'
                ],

                'player_snapshot_capture' => [
                    'status' =>
                        'Success'
                ]
            ]
        ]
    );


ob_start();


include $cronPath;


$successOutput =
    (string) ob_get_clean();


historicalLifecycleCronAssert(
    $historicalEvidenceLifecycleCronLauncher
        ->getCalls()
    ===
    1,
    'Historical lifecycle cron calls launcher exactly once.'
);


historicalLifecycleCronAssert(
    str_contains(
        $successOutput,
        'FPL Intelligence Historical Evidence Lifecycle'
    ),
    'Historical lifecycle cron displays lifecycle heading.'
);


historicalLifecycleCronAssert(
    str_contains(
        $successOutput,
        'Overall Status: Success'
    ),
    'Successful lifecycle displays overall Success.'
);


historicalLifecycleCronAssert(
    str_contains(
        $successOutput,
        'recommendation_promotion: Success'
    ),
    'Successful lifecycle displays recommendation promotion status.'
);


historicalLifecycleCronAssert(
    str_contains(
        $successOutput,
        'player_snapshot_promotion: Success'
    ),
    'Successful lifecycle displays player snapshot promotion status.'
);


historicalLifecycleCronAssert(
    str_contains(
        $successOutput,
        'player_snapshot_capture: Success'
    ),
    'Successful lifecycle displays player snapshot capture status.'
);


historicalLifecycleCronAssert(
    str_contains(
        $successOutput,
        'RESULT: HISTORICAL EVIDENCE LIFECYCLE PASSED'
    ),
    'Successful lifecycle displays explicit completion result.'
);


/*
 * ============================================================
 * F. FAILURE CONTRACT
 * ============================================================
 *
 * Do not deliberately execute the production failure branch
 * from an included cron because exit(1) would terminate this
 * browser test process.
 *
 * Protect that branch through the source contract instead.
 */

historicalLifecycleCronSection(
    'F. Failure Contract'
);


historicalLifecycleCronAssert(
    str_contains(
        $cronSource,
        'RESULT: HISTORICAL EVIDENCE LIFECYCLE FAILED'
    ),
    'Historical lifecycle cron exposes explicit failure result.'
);


historicalLifecycleCronAssert(
    str_contains(
        $cronSource,
        'exit(1)'
    ),
    'Historical lifecycle cron returns non-zero exit status on failure.'
);


historicalLifecycleCronAssert(
    str_contains(
        $cronSource,
        'error_message'
    ),
    'Historical lifecycle cron exposes lifecycle error messages.'
);


/*
 * ============================================================
 * G. ARCHITECTURE BOUNDARY
 * ============================================================
 */

historicalLifecycleCronSection(
    'G. Architecture Boundary'
);


historicalLifecycleCronAssert(
    !str_contains(
        $cronSource,
        'new DataUpdateCoordinator'
    ),
    'Historical lifecycle remains separate from DataUpdateCoordinator.'
);


historicalLifecycleCronAssert(
    !str_contains(
        $cronSource,
        'new DataUpdateProcessRunner'
    ),
    'Historical lifecycle does not use the update_runs process adapter.'
);


historicalLifecycleCronAssert(
    !str_contains(
        $cronSource,
        'RecommendationCandidateProductionCapture'
    ),
    'Historical lifecycle does not fabricate recommendation candidate capture.'
);


historicalLifecycleCronAssert(
    !str_contains(
        $cronSource,
        'INSERT INTO'
    )
    &&
    !str_contains(
        $cronSource,
        'UPDATE '
    )
    &&
    !str_contains(
        $cronSource,
        'DELETE FROM'
    ),
    'Historical lifecycle cron contains no direct domain SQL.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Historical Evidence Lifecycle Cron Test Summary<br>";
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