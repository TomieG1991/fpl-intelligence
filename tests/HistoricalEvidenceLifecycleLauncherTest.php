<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Historical Evidence Lifecycle Launcher Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function historicalLauncherCheck(
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


function historicalLauncherSection(
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

historicalLauncherSection(
    'A. Class Contract'
);


$classExists =
    class_exists(
        'HistoricalEvidenceLifecycleLauncher'
    );


historicalLauncherCheck(
    $classExists,
    'HistoricalEvidenceLifecycleLauncher exists.'
);


if (!$classExists) {

    echo "<br>";
    echo "<strong>EXPECTED RED: historical evidence lifecycle launcher does not exist yet.</strong><br>";

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
 * B. PRODUCTION WIRING
 * ============================================================
 *
 * The launcher maps the lifecycle coordinator onto the three
 * existing thin operational entry points.
 *
 * No real cron script is executed here.
 */

historicalLauncherSection(
    'B. Production Wiring'
);


$projectRoot =
    'C:\\synthetic\\fpl-intelligence';


$calls = [];


$runner =
    static function (
        string $stepName,
        string $scriptPath
    ) use (
        &$calls
    ): array {

        $calls[] = [
            'step_name' =>
                $stepName,

            'script_path' =>
                $scriptPath
        ];


        return [
            'status' =>
                'Success',

            'step_name' =>
                $stepName
        ];
    };


$launcher =
    new HistoricalEvidenceLifecycleLauncher(
        $projectRoot,
        $runner
    );


historicalLauncherCheck(
    method_exists(
        $launcher,
        'run'
    ),
    'Launcher exposes run().'
);


$result =
    $launcher
        ->run();


historicalLauncherCheck(
    ($result['status'] ?? null) === 'Success',
    'Launcher returns successful coordinator result.'
);


historicalLauncherCheck(
    count(
        $calls
    )
    ===
    3,
    'Launcher executes exactly three historical lifecycle steps.'
);


/*
 * ============================================================
 * C. RECOMMENDATION PROMOTION
 * ============================================================
 */

historicalLauncherSection(
    'C. Recommendation Promotion'
);


historicalLauncherCheck(
    ($calls[0]['step_name'] ?? null)
    ===
    'recommendation_promotion',
    'First lifecycle step is recommendation promotion.'
);


historicalLauncherCheck(
    ($calls[0]['script_path'] ?? null)
    ===
    $projectRoot
    . DIRECTORY_SEPARATOR
    . 'cron'
    . DIRECTORY_SEPARATOR
    . 'promoteRecommendationCandidates.php',
    'Recommendation promotion uses the existing production cron.'
);


/*
 * ============================================================
 * D. PLAYER SNAPSHOT PROMOTION
 * ============================================================
 */

historicalLauncherSection(
    'D. Player Snapshot Promotion'
);


historicalLauncherCheck(
    ($calls[1]['step_name'] ?? null)
    ===
    'player_snapshot_promotion',
    'Second lifecycle step is player snapshot promotion.'
);


historicalLauncherCheck(
    ($calls[1]['script_path'] ?? null)
    ===
    $projectRoot
    . DIRECTORY_SEPARATOR
    . 'cron'
    . DIRECTORY_SEPARATOR
    . 'promotePlayerGameweekSnapshotCandidates.php',
    'Player snapshot promotion uses the existing production cron.'
);


/*
 * ============================================================
 * E. PLAYER SNAPSHOT CAPTURE
 * ============================================================
 */

historicalLauncherSection(
    'E. Player Snapshot Capture'
);


historicalLauncherCheck(
    ($calls[2]['step_name'] ?? null)
    ===
    'player_snapshot_capture',
    'Third lifecycle step is player snapshot candidate capture.'
);


historicalLauncherCheck(
    ($calls[2]['script_path'] ?? null)
    ===
    $projectRoot
    . DIRECTORY_SEPARATOR
    . 'cron'
    . DIRECTORY_SEPARATOR
    . 'capturePlayerGameweekSnapshotCandidates.php',
    'Player snapshot capture uses the existing production cron.'
);


/*
 * ============================================================
 * F. RETIRED CAPTURE MUST REMAIN UNUSED
 * ============================================================
 */

historicalLauncherSection(
    'F. Retired Snapshot Capture'
);


$usedScripts =
    array_map(
        static function (
            array $call
        ): string {

            return
                (string) (
                    $call[
                        'script_path'
                    ]
                    ?? ''
                );
        },
        $calls
    );


historicalLauncherCheck(
    !in_array(
        $projectRoot
        . DIRECTORY_SEPARATOR
        . 'cron'
        . DIRECTORY_SEPARATOR
        . 'capturePlayerGameweekSnapshots.php',
        $usedScripts,
        true
    ),
    'Launcher never invokes retired retrospective snapshot capture.'
);


/*
 * ============================================================
 * G. RECOMMENDATION CAPTURE REMAINS EXCLUDED
 * ============================================================
 *
 * Recommendation capture requires genuine manager-specific
 * production recommendation evidence.
 *
 * This generic lifecycle must never invent or reconstruct it.
 */

historicalLauncherSection(
    'G. Recommendation Capture Boundary'
);


$recommendationCaptureUsed =
    false;


foreach (
    $calls
    as $call
) {

    $scriptPath =
        strtolower(
            (string) (
                $call[
                    'script_path'
                ]
                ?? ''
            )
        );


    if (
        str_contains(
            $scriptPath,
            'capturerecommendation'
        )
    ) {

        $recommendationCaptureUsed =
            true;

        break;
    }
}


historicalLauncherCheck(
    !$recommendationCaptureUsed,
    'Generic historical lifecycle does not invoke recommendation candidate capture.'
);


/*
 * ============================================================
 * H. FAILURE STOPS LATER SCRIPTS
 * ============================================================
 */

historicalLauncherSection(
    'H. Failure Propagation'
);


$failureCalls = [];


$failureRunner =
    static function (
        string $stepName,
        string $scriptPath
    ) use (
        &$failureCalls
    ): array {

        $failureCalls[] =
            $stepName;


        if (
            $stepName
            ===
            'player_snapshot_promotion'
        ) {

            return [
                'status' =>
                    'Failed',

                'error_message' =>
                    'Synthetic promotion failure.'
            ];
        }


        return [
            'status' =>
                'Success'
        ];
    };


$failureLauncher =
    new HistoricalEvidenceLifecycleLauncher(
        $projectRoot,
        $failureRunner
    );


$failureResult =
    $failureLauncher
        ->run();


historicalLauncherCheck(
    $failureCalls === [
        'recommendation_promotion',
        'player_snapshot_promotion'
    ],
    'Launcher preserves coordinator fail-closed ordering.'
);


historicalLauncherCheck(
    ($failureResult['status'] ?? null)
    ===
    'Failed',
    'Launcher returns Failed when a lifecycle step fails.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Historical Evidence Lifecycle Launcher Test Summary<br>";
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