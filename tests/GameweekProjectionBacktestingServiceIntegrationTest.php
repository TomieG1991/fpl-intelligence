<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Projection Backtesting Service Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekProjectionBacktestingIntegrationTestResult(
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
 * PRODUCTION SERVICES
 * ============================================================
 */

$playerProjectionBacktestingService =
    new PlayerProjectionBacktestingService();


$playerProjectionBacktestingMetricsService =
    new PlayerProjectionBacktestingMetricsService();


$service =
    new GameweekProjectionBacktestingService(
        $playerProjectionBacktestingService,
        $playerProjectionBacktestingMetricsService
    );


gameweekProjectionBacktestingIntegrationTestResult(
    $service
        instanceof GameweekProjectionBacktestingService,
    'Production projection backtesting pipeline can be constructed.'
);


/*
 * ============================================================
 * HISTORICAL RECOMMENDATION EVIDENCE
 * ============================================================
 */

$playerProjections = [

    [
        'player_id' => 101,
        'fpl_player_id' => 1001,
        'name' => 'Player One',
        'position' => 'MID',
        'team_id' => 1,
        'price' => 7.5,
        'intelligence_score' => 72.0,
        'projected_points' => 6.5,
        'projected_minutes' => 90,
        'projection_confidence' => 0.80,
        'projection_confidence_percent' => 80.0,
        'projection_confidence_label' => 'High',
        'projected_points_components' => [],
        'projected_points_inputs' => [],
        'has_projected_points' => true
    ],

    [
        'player_id' => 102,
        'fpl_player_id' => 1002,
        'name' => 'Player Two',
        'position' => 'FWD',
        'team_id' => 2,
        'price' => 8.0,
        'intelligence_score' => 68.0,
        'projected_points' => 8.0,
        'projected_minutes' => 75,
        'projection_confidence' => 0.70,
        'projection_confidence_percent' => 70.0,
        'projection_confidence_label' => 'Medium',
        'projected_points_components' => [],
        'projected_points_inputs' => [],
        'has_projected_points' => true
    ],

    [
        'player_id' => 103,
        'fpl_player_id' => 1003,
        'name' => 'Player Three',
        'position' => 'DEF',
        'team_id' => 3,
        'price' => 5.0,
        'intelligence_score' => 61.0,
        'projected_points' => 4.5,
        'projected_minutes' => null,
        'projection_confidence' => 0.55,
        'projection_confidence_percent' => 55.0,
        'projection_confidence_label' => 'Medium',
        'projected_points_components' => [],
        'projected_points_inputs' => [],
        'has_projected_points' => true
    ],

    [
        'player_id' => 104,
        'fpl_player_id' => 1004,
        'name' => 'Player Four',
        'position' => 'DEF',
        'team_id' => 4,
        'price' => 4.5,
        'intelligence_score' => 50.0,
        'projected_points' => null,
        'projected_minutes' => null,
        'projection_confidence' => null,
        'projection_confidence_percent' => null,
        'projection_confidence_label' => null,
        'projected_points_components' => [],
        'projected_points_inputs' => [],
        'has_projected_points' => false
    ]
];


/*
 * ============================================================
 * COMPLETED GAMEWEEK OUTCOME EVIDENCE
 * ============================================================
 */

$playerOutcomes = [

    [
        'gameweek_id' => 5,
        'player_id' => 101,
        'fixture_count' => 1,
        'total_points' => 8,
        'minutes' => 90,
        'starts' => 1,
        'goals' => 1,
        'assists' => 0,
        'clean_sheets' => 1,
        'bonus' => 2
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 102,
        'fixture_count' => 1,
        'total_points' => 5,
        'minutes' => 60,
        'starts' => 1,
        'goals' => 0,
        'assists' => 1,
        'clean_sheets' => 0,
        'bonus' => 0
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 103,
        'fixture_count' => 1,
        'total_points' => 5,
        'minutes' => 90,
        'starts' => 1,
        'goals' => 0,
        'assists' => 0,
        'clean_sheets' => 1,
        'bonus' => 1
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 104,
        'fixture_count' => 1,
        'total_points' => 2,
        'minutes' => 90,
        'starts' => 1,
        'goals' => 0,
        'assists' => 0,
        'clean_sheets' => 0,
        'bonus' => 0
    ]
];


$evidence = [

    'status' => 'Ready',

    'reason' => null,

    'entry_id' => 900001,

    'gameweek_id' => 5,

    'gameweek' => [
        'id' => 5,
        'fpl_gameweek_id' => 1,
        'finished' => 1,
        'data_checked' => 1
    ],

    'recommendation_snapshot' => [

        'gameweek_id' => 5,

        'entry_id' => 900001,

        'player_projections' =>
            $playerProjections,

        'starting_xi' => [],

        'captain_recommendation' => [],

        'transfer_recommendations' => [],

        'gameweek_decision' => [],

        'chip_recommendations' => []
    ],

    'player_outcomes' =>
        $playerOutcomes
];


$originalEvidence =
    $evidence;


/*
 * ============================================================
 * SCENARIO A
 * COMPLETE PRODUCTION PIPELINE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Complete Production Projection Pipeline<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $evidence
    );


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Ready',
    'Real production services produce Ready projection backtesting.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    array_key_exists(
        'reason',
        $result
    )
    &&
    $result[
        'reason'
    ]
    === null,
    'Ready production result preserves explicit null reason.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    === 900001,
    'Production pipeline preserves entry identity.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    === 5,
    'Production pipeline preserves local gameweek identity.'
);


/*
 * ============================================================
 * SCENARIO B
 * PLAYER-LEVEL PROJECTION COMPARISONS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Real Player-Level Comparisons<br>";
echo "============================================<br>";


$evaluations =
    $result[
        'player_evaluations'
    ]
    ?? [];


gameweekProjectionBacktestingIntegrationTestResult(
    count(
        $evaluations
    )
    === 3,
    'Only players with genuine projected-points evidence are evaluated.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $evaluations[
            0
        ][
            'player_id'
        ]
        ?? null
    )
    === 101,
    'First preserved projection is matched to correct realised player outcome.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $evaluations[
            0
        ][
            'points_error'
        ]
        ?? null
    )
    === 1.5,
    'Real evaluator calculates first player under-projection correctly.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $evaluations[
            1
        ][
            'player_id'
        ]
        ?? null
    )
    === 102,
    'Second preserved projection is matched to correct realised player outcome.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $evaluations[
            1
        ][
            'points_error'
        ]
        ?? null
    )
    === -3.0,
    'Real evaluator calculates second player over-projection correctly.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $evaluations[
            1
        ][
            'minutes_error'
        ]
        ?? null
    )
    === -15.0,
    'Real evaluator calculates minutes over-projection correctly.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $evaluations[
            2
        ][
            'player_id'
        ]
        ?? null
    )
    === 103,
    'Points evaluation remains available when projected minutes are absent.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $evaluations[
            2
        ][
            'points_error'
        ]
        ?? null
    )
    === 0.5,
    'Player without projected minutes still receives correct points comparison.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    array_key_exists(
        'minutes_error',
        $evaluations[
            2
        ]
    )
    &&
    $evaluations[
        2
    ][
        'minutes_error'
    ]
    === null,
    'Player without projected minutes does not receive manufactured minutes error.'
);


/*
 * ============================================================
 * SCENARIO C
 * REAL AGGREGATE POINTS METRICS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Real Aggregate Points Metrics<br>";
echo "============================================<br>";


$pointsMetrics =
    $result[
        'metrics'
    ][
        'points'
    ]
    ?? [];


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $pointsMetrics[
            'sample_size'
        ]
        ?? null
    )
    === 3,
    'Real metrics service counts all valid points comparisons.'
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


gameweekProjectionBacktestingIntegrationTestResult(
    abs(
        $pointsMetrics[
            'mean_error'
        ]
        -
        $expectedPointsMeanError
    )
    <
    0.0000001,
    'Real metrics service calculates points directional bias correctly.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    abs(
        $pointsMetrics[
            'mean_absolute_error'
        ]
        -
        $expectedPointsMae
    )
    <
    0.0000001,
    'Real metrics service calculates points MAE correctly.'
);


/*
 * ============================================================
 * SCENARIO D
 * REAL AGGREGATE MINUTES METRICS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Real Aggregate Minutes Metrics<br>";
echo "============================================<br>";


$minutesMetrics =
    $result[
        'metrics'
    ][
        'minutes'
    ]
    ?? [];


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $minutesMetrics[
            'sample_size'
        ]
        ?? null
    )
    === 2,
    'Real metrics service keeps independent minutes sample size.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $minutesMetrics[
            'mean_error'
        ]
        ?? null
    )
    === -7.5,
    'Real metrics service calculates minutes directional bias correctly.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $minutesMetrics[
            'mean_absolute_error'
        ]
        ?? null
    )
    === 7.5,
    'Real metrics service calculates minutes MAE correctly.'
);


/*
 * ============================================================
 * SCENARIO E
 * UNAVAILABLE PROJECTION REMAINS EXCLUDED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Unavailable Projection Evidence<br>";
echo "============================================<br>";


$evaluatedPlayerIds =
    array_map(
        static function (
            array $evaluation
        ): int {

            return
                (int) $evaluation[
                    'player_id'
                ];
        },
        $evaluations
    );


gameweekProjectionBacktestingIntegrationTestResult(
    !in_array(
        104,
        $evaluatedPlayerIds,
        true
    ),
    'Player without projected-points evidence is excluded by real evaluator.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    (
        $pointsMetrics[
            'sample_size'
        ]
        ?? null
    )
    === 3,
    'Unavailable projection does not inflate aggregate points sample.'
);


/*
 * ============================================================
 * SCENARIO F
 * HISTORICAL EVIDENCE IMMUTABILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Historical Evidence Remains Unchanged<br>";
echo "============================================<br>";


gameweekProjectionBacktestingIntegrationTestResult(
    $evidence
        ===
        $originalEvidence,
    'Complete real projection pipeline does not mutate historical evidence.'
);


/*
 * ============================================================
 * SCENARIO G
 * RESULT REMAINS PROJECTION-ONLY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Projection Evaluation Boundary<br>";
echo "============================================<br>";


gameweekProjectionBacktestingIntegrationTestResult(
    !array_key_exists(
        'captain_result',
        $result
    ),
    'Production projection pipeline does not evaluate captain recommendation.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    !array_key_exists(
        'starting_xi_result',
        $result
    ),
    'Production projection pipeline does not evaluate Starting XI recommendation.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    !array_key_exists(
        'transfer_result',
        $result
    ),
    'Production projection pipeline does not evaluate transfer recommendations.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    !array_key_exists(
        'chip_result',
        $result
    ),
    'Production projection pipeline does not evaluate chip recommendations.'
);


gameweekProjectionBacktestingIntegrationTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Production projection pipeline does not manufacture overall model score.'
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