<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Historical Evidence Lifecycle Coordinator Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function historicalLifecycleCheck(
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


function historicalLifecycleSection(
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
 *
 * Historical-evidence maintenance deliberately remains
 * separate from DataUpdateCoordinator.
 *
 * The lifecycle has three safe operational responsibilities:
 *
 * 1. Promote eligible recommendation candidates.
 * 2. Promote eligible player snapshot candidates.
 * 3. Capture the latest player state for the next deadline.
 *
 * Recommendation candidate CAPTURE is deliberately absent.
 * It requires genuine manager-specific recommendation evidence
 * and must not be fabricated by this lifecycle.
 */

historicalLifecycleSection(
    'A. Class Contract'
);


$classExists =
    class_exists(
        'HistoricalEvidenceLifecycleCoordinator'
    );


historicalLifecycleCheck(
    $classExists,
    'HistoricalEvidenceLifecycleCoordinator exists.'
);


if (!$classExists) {

    echo "<br>";
    echo "<strong>EXPECTED RED: historical evidence lifecycle coordinator does not exist yet.</strong><br>";

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
 * B. SUCCESSFUL LIFECYCLE
 * ============================================================
 */

historicalLifecycleSection(
    'B. Successful Lifecycle'
);


$executionOrder = [];


$recommendationPromotionResult = [
    'status' => 'Success',
    'ready' => 1,
    'promoted' => 1,
    'unchanged' => 0
];


$playerPromotionResult = [
    'status' => 'Success',
    'ready' => 667,
    'promoted' => 667,
    'unchanged' => 0
];


$playerCaptureResult = [
    'status' => 'Success',
    'players_considered' => 667,
    'saved' => 667,
    'unchanged' => 0
];


$coordinator =
    new HistoricalEvidenceLifecycleCoordinator(

        static function () use (
            &$executionOrder,
            $recommendationPromotionResult
        ): array {

            $executionOrder[] =
                'recommendation_promotion';

            return $recommendationPromotionResult;
        },

        static function () use (
            &$executionOrder,
            $playerPromotionResult
        ): array {

            $executionOrder[] =
                'player_snapshot_promotion';

            return $playerPromotionResult;
        },

        static function () use (
            &$executionOrder,
            $playerCaptureResult
        ): array {

            $executionOrder[] =
                'player_snapshot_capture';

            return $playerCaptureResult;
        }
    );


$result =
    $coordinator
        ->run();


historicalLifecycleCheck(
    $executionOrder === [
        'recommendation_promotion',
        'player_snapshot_promotion',
        'player_snapshot_capture'
    ],
    'Historical lifecycle executes promotion before capture in deterministic order.'
);


historicalLifecycleCheck(
    ($result['status'] ?? null) === 'Success',
    'Successful historical lifecycle returns Success.'
);


historicalLifecycleCheck(
    ($result['steps']['recommendation_promotion'] ?? null)
    ===
    $recommendationPromotionResult,
    'Recommendation promotion result is preserved unchanged.'
);


historicalLifecycleCheck(
    ($result['steps']['player_snapshot_promotion'] ?? null)
    ===
    $playerPromotionResult,
    'Player snapshot promotion result is preserved unchanged.'
);


historicalLifecycleCheck(
    ($result['steps']['player_snapshot_capture'] ?? null)
    ===
    $playerCaptureResult,
    'Player snapshot capture result is preserved unchanged.'
);


/*
 * ============================================================
 * C. RECOMMENDATION PROMOTION FAILURE
 * ============================================================
 */

historicalLifecycleSection(
    'C. Recommendation Promotion Failure'
);


$executionOrder = [];


$coordinator =
    new HistoricalEvidenceLifecycleCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'recommendation_promotion';

            return [
                'status' => 'Failed',
                'error_message' => 'Synthetic recommendation failure.'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_snapshot_promotion';

            return [
                'status' => 'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_snapshot_capture';

            return [
                'status' => 'Success'
            ];
        }
    );


$result =
    $coordinator
        ->run();


historicalLifecycleCheck(
    $executionOrder === [
        'recommendation_promotion'
    ],
    'Recommendation promotion failure stops later lifecycle steps.'
);


historicalLifecycleCheck(
    ($result['status'] ?? null) === 'Failed',
    'Recommendation promotion failure returns Failed.'
);


/*
 * ============================================================
 * D. PLAYER PROMOTION FAILURE
 * ============================================================
 */

historicalLifecycleSection(
    'D. Player Snapshot Promotion Failure'
);


$executionOrder = [];


$coordinator =
    new HistoricalEvidenceLifecycleCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'recommendation_promotion';

            return [
                'status' => 'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_snapshot_promotion';

            return [
                'status' => 'Failed',
                'error_message' => 'Synthetic player promotion failure.'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_snapshot_capture';

            return [
                'status' => 'Success'
            ];
        }
    );


$result =
    $coordinator
        ->run();


historicalLifecycleCheck(
    $executionOrder === [
        'recommendation_promotion',
        'player_snapshot_promotion'
    ],
    'Player snapshot promotion failure prevents new candidate capture.'
);


historicalLifecycleCheck(
    ($result['status'] ?? null) === 'Failed',
    'Player snapshot promotion failure returns Failed.'
);


/*
 * ============================================================
 * E. PLAYER CAPTURE FAILURE
 * ============================================================
 */

historicalLifecycleSection(
    'E. Player Snapshot Capture Failure'
);


$executionOrder = [];


$coordinator =
    new HistoricalEvidenceLifecycleCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'recommendation_promotion';

            return [
                'status' => 'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_snapshot_promotion';

            return [
                'status' => 'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_snapshot_capture';

            return [
                'status' => 'Failed',
                'error_message' => 'Synthetic player capture failure.'
            ];
        }
    );


$result =
    $coordinator
        ->run();


historicalLifecycleCheck(
    $executionOrder === [
        'recommendation_promotion',
        'player_snapshot_promotion',
        'player_snapshot_capture'
    ],
    'Player snapshot capture failure occurs after both promotion steps.'
);


historicalLifecycleCheck(
    ($result['status'] ?? null) === 'Failed',
    'Player snapshot capture failure returns Failed.'
);


/*
 * ============================================================
 * F. EXCEPTION HANDLING
 * ============================================================
 */

historicalLifecycleSection(
    'F. Exception Handling'
);


$executionOrder = [];


$coordinator =
    new HistoricalEvidenceLifecycleCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'recommendation_promotion';

            return [
                'status' => 'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_snapshot_promotion';

            throw new RuntimeException(
                'Synthetic lifecycle exception.'
            );
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_snapshot_capture';

            return [
                'status' => 'Success'
            ];
        }
    );


$result =
    $coordinator
        ->run();


historicalLifecycleCheck(
    $executionOrder === [
        'recommendation_promotion',
        'player_snapshot_promotion'
    ],
    'Lifecycle exception stops subsequent steps.'
);


historicalLifecycleCheck(
    ($result['status'] ?? null) === 'Failed',
    'Lifecycle exception returns Failed.'
);


historicalLifecycleCheck(
    (
        $result[
            'steps'
        ][
            'player_snapshot_promotion'
        ][
            'error_message'
        ]
        ?? null
    )
    ===
    'Synthetic lifecycle exception.',
    'Lifecycle preserves exception message for diagnosis.'
);


/*
 * ============================================================
 * G. INVALID STATUS
 * ============================================================
 */

historicalLifecycleSection(
    'G. Invalid Step Status'
);


$executionOrder = [];


$coordinator =
    new HistoricalEvidenceLifecycleCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'recommendation_promotion';

            return [
                'status' => 'Unexpected'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_snapshot_promotion';

            return [
                'status' => 'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_snapshot_capture';

            return [
                'status' => 'Success'
            ];
        }
    );


$result =
    $coordinator
        ->run();


historicalLifecycleCheck(
    $executionOrder === [
        'recommendation_promotion'
    ],
    'Unexpected lifecycle status stops subsequent steps.'
);


historicalLifecycleCheck(
    ($result['status'] ?? null) === 'Failed',
    'Unexpected lifecycle status is treated as Failed.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Historical Evidence Lifecycle Coordinator Test Summary<br>";
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