<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Projection Backtesting Metrics Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function playerProjectionBacktestingMetricsTestResult(
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


/*
 * ============================================================
 * SERVICE AVAILABILITY
 * ============================================================
 */

playerProjectionBacktestingMetricsTestResult(
    class_exists(
        'PlayerProjectionBacktestingMetricsService'
    ),
    'PlayerProjectionBacktestingMetricsService exists.'
);


if (
    !class_exists(
        'PlayerProjectionBacktestingMetricsService'
    )
) {

    echo "<br>";
    echo "============================================<br>";
    echo "TEST SUMMARY<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "RESULT: TESTS FAILED ❌<br>";

    exit;
}


$service =
    new PlayerProjectionBacktestingMetricsService();


playerProjectionBacktestingMetricsTestResult(
    $service
        instanceof PlayerProjectionBacktestingMetricsService,
    'PlayerProjectionBacktestingMetricsService can be constructed.'
);


/*
 * ============================================================
 * STANDARD PLAYER-LEVEL BACKTESTING EVIDENCE
 * ============================================================
 */

$standardEvaluations = [

    [
        'player_id' => 101,
        'projected_points' => 6.5,
        'actual_points' => 8,
        'points_error' => 1.5,
        'absolute_points_error' => 1.5,
        'projected_minutes' => 90,
        'actual_minutes' => 90,
        'minutes_error' => 0.0,
        'absolute_minutes_error' => 0.0
    ],

    [
        'player_id' => 102,
        'projected_points' => 8.0,
        'actual_points' => 5,
        'points_error' => -3.0,
        'absolute_points_error' => 3.0,
        'projected_minutes' => 75,
        'actual_minutes' => 60,
        'minutes_error' => -15.0,
        'absolute_minutes_error' => 15.0
    ],

    [
        'player_id' => 103,
        'projected_points' => 4.5,
        'actual_points' => 5,
        'points_error' => 0.5,
        'absolute_points_error' => 0.5,
        'projected_minutes' => null,
        'actual_minutes' => 90,
        'minutes_error' => null,
        'absolute_minutes_error' => null
    ]
];


/*
 * ============================================================
 * SCENARIO A
 * EMPTY EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Empty Evaluation Evidence<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        []
    );


$expectedEmptyMetrics = [

    'points' => [
        'sample_size' => 0,
        'mean_error' => null,
        'mean_absolute_error' => null
    ],

    'minutes' => [
        'sample_size' => 0,
        'mean_error' => null,
        'mean_absolute_error' => null
    ]
];


playerProjectionBacktestingMetricsTestResult(
    $result === $expectedEmptyMetrics,
    'Empty evidence returns the exact empty metrics contract.'
);


/*
 * ============================================================
 * SCENARIO B
 * SINGLE PLAYER
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Single Player Metrics<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [
            $standardEvaluations[
                0
            ]
        ]
    );


playerProjectionBacktestingMetricsTestResult(
    (
        $result[
            'points'
        ][
            'sample_size'
        ]
        ?? null
    )
    === 1,
    'Single player produces a points sample size of one.'
);


playerProjectionBacktestingMetricsTestResult(
    (
        $result[
            'points'
        ][
            'mean_error'
        ]
        ?? null
    )
    === 1.5,
    'Single player points mean error equals the player directional error.'
);


playerProjectionBacktestingMetricsTestResult(
    (
        $result[
            'points'
        ][
            'mean_absolute_error'
        ]
        ?? null
    )
    === 1.5,
    'Single player points MAE equals the player absolute error.'
);


playerProjectionBacktestingMetricsTestResult(
    (
        $result[
            'minutes'
        ][
            'sample_size'
        ]
        ?? null
    )
    === 1,
    'Single player produces a minutes sample size of one.'
);


playerProjectionBacktestingMetricsTestResult(
    (
        $result[
            'minutes'
        ][
            'mean_error'
        ]
        ?? null
    )
    === 0.0,
    'Single player minutes mean error preserves zero directional error.'
);


playerProjectionBacktestingMetricsTestResult(
    (
        $result[
            'minutes'
        ][
            'mean_absolute_error'
        ]
        ?? null
    )
    === 0.0,
    'Single player minutes MAE preserves zero absolute error.'
);


/*
 * ============================================================
 * SCENARIO C
 * MULTIPLE PLAYER POINTS METRICS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Multiple Player Points Metrics<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        $standardEvaluations
    );


playerProjectionBacktestingMetricsTestResult(
    $result[
        'points'
    ][
        'sample_size'
    ]
    === 3,
    'All valid points evaluations contribute to the points sample.'
);


$expectedPointsMeanError =
    (
        1.5
        +
        -3.0
        +
        0.5
    )
    /
    3;


$expectedPointsMae =
    (
        1.5
        +
        3.0
        +
        0.5
    )
    /
    3;


playerProjectionBacktestingMetricsTestResult(
    abs(
        $result[
            'points'
        ][
            'mean_error'
        ]
        -
        $expectedPointsMeanError
    )
    <
    0.0000001,
    'Points mean error is calculated from directional player errors.'
);


playerProjectionBacktestingMetricsTestResult(
    abs(
        $result[
            'points'
        ][
            'mean_absolute_error'
        ]
        -
        $expectedPointsMae
    )
    <
    0.0000001,
    'Points MAE is calculated from absolute player errors.'
);


/*
 * ============================================================
 * SCENARIO D
 * MINUTES USE THEIR OWN SAMPLE SIZE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Independent Minutes Sample<br>";
echo "============================================<br>";


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'sample_size'
    ]
    === 2,
    'Player without minutes projection is excluded from minutes sample only.'
);


$expectedMinutesMeanError =
    (
        0.0
        +
        -15.0
    )
    /
    2;


$expectedMinutesMae =
    (
        0.0
        +
        15.0
    )
    /
    2;


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'mean_error'
    ]
    ===
    $expectedMinutesMeanError,
    'Minutes mean error uses only players with valid minutes comparison evidence.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'mean_absolute_error'
    ]
    ===
    $expectedMinutesMae,
    'Minutes MAE uses only players with valid minutes comparison evidence.'
);


/*
 * ============================================================
 * SCENARIO E
 * POSITIVE MEAN ERROR
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Positive Directional Bias<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [

            [
                'player_id' => 201,
                'projected_points' => 4.0,
                'actual_points' => 6,
                'points_error' => 2.0,
                'absolute_points_error' => 2.0,
                'projected_minutes' => 80,
                'actual_minutes' => 90,
                'minutes_error' => 10.0,
                'absolute_minutes_error' => 10.0
            ],

            [
                'player_id' => 202,
                'projected_points' => 5.0,
                'actual_points' => 6,
                'points_error' => 1.0,
                'absolute_points_error' => 1.0,
                'projected_minutes' => 85,
                'actual_minutes' => 90,
                'minutes_error' => 5.0,
                'absolute_minutes_error' => 5.0
            ]
        ]
    );


playerProjectionBacktestingMetricsTestResult(
    $result[
        'points'
    ][
        'mean_error'
    ]
    === 1.5,
    'Positive points mean error represents systematic under-projection.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'mean_error'
    ]
    === 7.5,
    'Positive minutes mean error represents systematic minutes under-projection.'
);


/*
 * ============================================================
 * SCENARIO F
 * NEGATIVE MEAN ERROR
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Negative Directional Bias<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [

            [
                'player_id' => 301,
                'projected_points' => 8.0,
                'actual_points' => 5,
                'points_error' => -3.0,
                'absolute_points_error' => 3.0,
                'projected_minutes' => 90,
                'actual_minutes' => 60,
                'minutes_error' => -30.0,
                'absolute_minutes_error' => 30.0
            ],

            [
                'player_id' => 302,
                'projected_points' => 6.0,
                'actual_points' => 5,
                'points_error' => -1.0,
                'absolute_points_error' => 1.0,
                'projected_minutes' => 80,
                'actual_minutes' => 70,
                'minutes_error' => -10.0,
                'absolute_minutes_error' => 10.0
            ]
        ]
    );


playerProjectionBacktestingMetricsTestResult(
    $result[
        'points'
    ][
        'mean_error'
    ]
    === -2.0,
    'Negative points mean error represents systematic over-projection.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'mean_error'
    ]
    === -20.0,
    'Negative minutes mean error represents systematic minutes over-projection.'
);


/*
 * ============================================================
 * SCENARIO G
 * ERRORS CANCEL BUT MAE DOES NOT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Bias Cancellation Versus MAE<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [

            [
                'player_id' => 401,
                'projected_points' => 5.0,
                'actual_points' => 7,
                'points_error' => 2.0,
                'absolute_points_error' => 2.0,
                'projected_minutes' => 80,
                'actual_minutes' => 90,
                'minutes_error' => 10.0,
                'absolute_minutes_error' => 10.0
            ],

            [
                'player_id' => 402,
                'projected_points' => 7.0,
                'actual_points' => 5,
                'points_error' => -2.0,
                'absolute_points_error' => 2.0,
                'projected_minutes' => 90,
                'actual_minutes' => 80,
                'minutes_error' => -10.0,
                'absolute_minutes_error' => 10.0
            ]
        ]
    );


playerProjectionBacktestingMetricsTestResult(
    $result[
        'points'
    ][
        'mean_error'
    ]
    === 0.0,
    'Opposing points errors can produce zero mean directional bias.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'points'
    ][
        'mean_absolute_error'
    ]
    === 2.0,
    'Points MAE remains non-zero when directional errors cancel.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'mean_error'
    ]
    === 0.0,
    'Opposing minutes errors can produce zero mean directional bias.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'mean_absolute_error'
    ]
    === 10.0,
    'Minutes MAE remains non-zero when directional errors cancel.'
);


/*
 * ============================================================
 * SCENARIO H
 * POINTS ONLY EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Points Evidence Without Minutes<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [

            [
                'player_id' => 501,
                'projected_points' => 5.0,
                'actual_points' => 6,
                'points_error' => 1.0,
                'absolute_points_error' => 1.0,
                'projected_minutes' => null,
                'actual_minutes' => 90,
                'minutes_error' => null,
                'absolute_minutes_error' => null
            ]
        ]
    );


playerProjectionBacktestingMetricsTestResult(
    $result[
        'points'
    ][
        'sample_size'
    ]
    === 1,
    'Points-only evidence remains part of points sample.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'sample_size'
    ]
    === 0,
    'Points-only evidence does not create a minutes sample.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'mean_error'
    ]
    === null,
    'Minutes mean error remains null when no minutes sample exists.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'mean_absolute_error'
    ]
    === null,
    'Minutes MAE remains null when no minutes sample exists.'
);


/*
 * ============================================================
 * SCENARIO I
 * ZERO ERRORS ARE VALID EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Exact Projection Evidence<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [

            [
                'player_id' => 601,
                'projected_points' => 6.0,
                'actual_points' => 6,
                'points_error' => 0.0,
                'absolute_points_error' => 0.0,
                'projected_minutes' => 90,
                'actual_minutes' => 90,
                'minutes_error' => 0.0,
                'absolute_minutes_error' => 0.0
            ]
        ]
    );


playerProjectionBacktestingMetricsTestResult(
    $result[
        'points'
    ][
        'sample_size'
    ]
    === 1,
    'Zero points error still counts as valid backtesting evidence.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'points'
    ][
        'mean_absolute_error'
    ]
    === 0.0,
    'Exact points projection produces zero MAE.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'sample_size'
    ]
    === 1,
    'Zero minutes error still counts as valid backtesting evidence.'
);


playerProjectionBacktestingMetricsTestResult(
    $result[
        'minutes'
    ][
        'mean_absolute_error'
    ]
    === 0.0,
    'Exact minutes projection produces zero MAE.'
);


/*
 * ============================================================
 * SCENARIO J
 * INVALID ROWS DO NOT CREATE SYNTHETIC EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Invalid Evaluation Rows<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [

            'not-an-array',

            [
                'player_id' => 701,
                'points_error' => null,
                'absolute_points_error' => null,
                'minutes_error' => null,
                'absolute_minutes_error' => null
            ],

            [
                'player_id' => 702,
                'points_error' => 'invalid',
                'absolute_points_error' => 'invalid',
                'minutes_error' => 'invalid',
                'absolute_minutes_error' => 'invalid'
            ]
        ]
    );


playerProjectionBacktestingMetricsTestResult(
    $result === $expectedEmptyMetrics,
    'Invalid comparison rows do not manufacture aggregate metrics.'
);


/*
 * ============================================================
 * SCENARIO K
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


$evaluationEvidence =
    $standardEvaluations;


$originalEvaluationEvidence =
    $evaluationEvidence;


$service->calculate(
    $evaluationEvidence
);


playerProjectionBacktestingMetricsTestResult(
    $evaluationEvidence
        ===
        $originalEvaluationEvidence,
    'Player-level backtesting evidence is not mutated.'
);


/*
 * ============================================================
 * SCENARIO L
 * NARROW METRICS RESPONSIBILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario L: Aggregate Metrics Only<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        $standardEvaluations
    );


playerProjectionBacktestingMetricsTestResult(
    !array_key_exists(
        'rmse',
        $result[
            'points'
        ]
    ),
    'Metrics service does not introduce RMSE yet.'
);


playerProjectionBacktestingMetricsTestResult(
    !array_key_exists(
        'correlation',
        $result[
            'points'
        ]
    ),
    'Metrics service does not introduce correlation yet.'
);


playerProjectionBacktestingMetricsTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Metrics service does not create a synthetic overall accuracy score.'
);


playerProjectionBacktestingMetricsTestResult(
    !array_key_exists(
        'captain',
        $result
    ),
    'Metrics service does not evaluate captain recommendations.'
);


playerProjectionBacktestingMetricsTestResult(
    !array_key_exists(
        'transfers',
        $result
    ),
    'Metrics service does not evaluate transfer recommendations.'
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "TEST SUMMARY<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}