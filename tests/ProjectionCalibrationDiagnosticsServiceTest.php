<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Projection Calibration Diagnostics Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function projectionCalibrationDiagnosticsTestResult(
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

projectionCalibrationDiagnosticsTestResult(
    class_exists(
        'ProjectionCalibrationDiagnosticsService'
    ),
    'ProjectionCalibrationDiagnosticsService exists.'
);


if (
    !class_exists(
        'ProjectionCalibrationDiagnosticsService'
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


$metricsService =
    new PlayerProjectionBacktestingMetricsService();


$service =
    new ProjectionCalibrationDiagnosticsService(
        $metricsService
    );


projectionCalibrationDiagnosticsTestResult(
    $service
        instanceof ProjectionCalibrationDiagnosticsService,
    'ProjectionCalibrationDiagnosticsService can be constructed.'
);


/*
 * ============================================================
 * CONTROLLED HISTORICAL PLAYER EVALUATIONS
 * ============================================================
 */

$evaluations = [

    [
        'player_id' => 101,
        'position' => 'GK',
        'projection_confidence' => 0.85,
        'projection_confidence_percent' => 85.0,
        'projection_confidence_label' => 'High',

        'projected_points' => 5.0,
        'actual_points' => 6,
        'points_error' => 1.0,
        'absolute_points_error' => 1.0,

        'projected_minutes' => 85,
        'actual_minutes' => 90,
        'minutes_error' => 5.0,
        'absolute_minutes_error' => 5.0
    ],

    [
        'player_id' => 102,
        'position' => 'def',
        'projection_confidence' => 0.70,
        'projection_confidence_percent' => 70.0,
        'projection_confidence_label' => 'Moderate',

        'projected_points' => 6.0,
        'actual_points' => 4,
        'points_error' => -2.0,
        'absolute_points_error' => 2.0,

        'projected_minutes' => 80,
        'actual_minutes' => 70,
        'minutes_error' => -10.0,
        'absolute_minutes_error' => 10.0
    ],

    [
        'player_id' => 103,
        'position' => 'MID',
        'projection_confidence' => 0.50,
        'projection_confidence_percent' => 50.0,
        'projection_confidence_label' => 'Low',

        'projected_points' => 4.0,
        'actual_points' => 7,
        'points_error' => 3.0,
        'absolute_points_error' => 3.0,

        'projected_minutes' => null,
        'actual_minutes' => 90,
        'minutes_error' => null,
        'absolute_minutes_error' => null
    ],

    [
        'player_id' => 104,
        'position' => 'FWD',
        'projection_confidence' => 0.30,
        'projection_confidence_percent' => 30.0,
        'projection_confidence_label' => 'Very Low',

        'projected_points' => 8.0,
        'actual_points' => 4,
        'points_error' => -4.0,
        'absolute_points_error' => 4.0,

        'projected_minutes' => 90,
        'actual_minutes' => 70,
        'minutes_error' => -20.0,
        'absolute_minutes_error' => 20.0
    ],

    [
        'player_id' => 105,
        'position' => null,
        'projection_confidence' => null,
        'projection_confidence_percent' => null,
        'projection_confidence_label' => null,

        'projected_points' => 2.0,
        'actual_points' => 7,
        'points_error' => 5.0,
        'absolute_points_error' => 5.0,

        'projected_minutes' => 60,
        'actual_minutes' => 75,
        'minutes_error' => 15.0,
        'absolute_minutes_error' => 15.0
    ],

    /*
     * Malformed evidence must not create a diagnostic group.
     */
    'malformed'
];


/*
 * ============================================================
 * SCENARIO A
 * EMPTY DIAGNOSTIC EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Empty Diagnostic Evidence<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        []
    );


$emptyMetrics = [

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


projectionCalibrationDiagnosticsTestResult(
    (
        $result[
            'overall'
        ]
        ?? null
    )
    ===
    $emptyMetrics,
    'Empty evidence returns empty overall projection metrics.'
);


projectionCalibrationDiagnosticsTestResult(
    (
        $result[
            'by_position'
        ][
            'GK'
        ]
        ?? null
    )
    ===
    $emptyMetrics,
    'Empty evidence preserves an empty goalkeeper diagnostic group.'
);


projectionCalibrationDiagnosticsTestResult(
    (
        $result[
            'by_position'
        ][
            'Unknown'
        ]
        ?? null
    )
    ===
    $emptyMetrics,
    'Empty evidence preserves an empty unknown-position diagnostic group.'
);


projectionCalibrationDiagnosticsTestResult(
    (
        $result[
            'by_confidence'
        ][
            'High'
        ]
        ?? null
    )
    ===
    $emptyMetrics,
    'Empty evidence preserves an empty High-confidence diagnostic group.'
);


projectionCalibrationDiagnosticsTestResult(
    (
        $result[
            'by_confidence'
        ][
            'Unavailable'
        ]
        ?? null
    )
    ===
    $emptyMetrics,
    'Empty evidence preserves an empty unavailable-confidence diagnostic group.'
);


/*
 * ============================================================
 * SCENARIO B
 * OVERALL PROJECTION ACCURACY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Overall Projection Accuracy<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $evaluations
    );


projectionCalibrationDiagnosticsTestResult(
    $result[
        'overall'
    ][
        'points'
    ][
        'sample_size'
    ]
    === 5,
    'Overall diagnostics use all five valid points evaluations.'
);


projectionCalibrationDiagnosticsTestResult(
    abs(
        $result[
            'overall'
        ][
            'points'
        ][
            'mean_error'
        ]
        -
        0.6
    )
    <
    0.0000001,
    'Overall points directional error is calculated correctly.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'overall'
    ][
        'points'
    ][
        'mean_absolute_error'
    ]
    === 3.0,
    'Overall points MAE is calculated correctly.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'overall'
    ][
        'minutes'
    ][
        'sample_size'
    ]
    === 4,
    'Overall minutes diagnostics retain their independent sample size.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'overall'
    ][
        'minutes'
    ][
        'mean_error'
    ]
    === -2.5,
    'Overall minutes directional error is calculated correctly.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'overall'
    ][
        'minutes'
    ][
        'mean_absolute_error'
    ]
    === 12.5,
    'Overall minutes MAE is calculated correctly.'
);


/*
 * ============================================================
 * SCENARIO C
 * POSITION DIAGNOSTICS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Position Diagnostics<br>";
echo "============================================<br>";


projectionCalibrationDiagnosticsTestResult(
    array_keys(
        $result[
            'by_position'
        ]
    )
    === [
        'GK',
        'DEF',
        'MID',
        'FWD',
        'Unknown'
    ],
    'Position diagnostics expose the complete deterministic group contract.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_position'
    ][
        'GK'
    ][
        'points'
    ][
        'mean_error'
    ]
    === 1.0,
    'Goalkeeper points bias is isolated.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_position'
    ][
        'DEF'
    ][
        'points'
    ][
        'mean_error'
    ]
    === -2.0,
    'Defender position matching is case-insensitive.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_position'
    ][
        'MID'
    ][
        'minutes'
    ][
        'sample_size'
    ]
    === 0,
    'Missing midfielder minutes evidence does not manufacture a minutes sample.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_position'
    ][
        'FWD'
    ][
        'points'
    ][
        'mean_absolute_error'
    ]
    === 4.0,
    'Forward points MAE is isolated.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_position'
    ][
        'Unknown'
    ][
        'points'
    ][
        'sample_size'
    ]
    === 1,
    'Missing or unsupported position evidence remains auditable as Unknown.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_position'
    ][
        'Unknown'
    ][
        'points'
    ][
        'mean_error'
    ]
    === 5.0,
    'Unknown-position player retains genuine points error evidence.'
);


/*
 * ============================================================
 * SCENARIO D
 * PROJECTION CONFIDENCE DIAGNOSTICS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Projection Confidence Diagnostics<br>";
echo "============================================<br>";


projectionCalibrationDiagnosticsTestResult(
    array_keys(
        $result[
            'by_confidence'
        ]
    )
    === [
        'High',
        'Moderate',
        'Low',
        'Very Low',
        'Unavailable'
    ],
    'Confidence diagnostics expose the production confidence labels plus Unavailable.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_confidence'
    ][
        'High'
    ][
        'points'
    ][
        'mean_error'
    ]
    === 1.0,
    'High-confidence points error is isolated.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_confidence'
    ][
        'Moderate'
    ][
        'points'
    ][
        'mean_error'
    ]
    === -2.0,
    'Moderate-confidence points error is isolated.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_confidence'
    ][
        'Low'
    ][
        'points'
    ][
        'mean_error'
    ]
    === 3.0,
    'Low-confidence points error is isolated.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_confidence'
    ][
        'Very Low'
    ][
        'points'
    ][
        'mean_error'
    ]
    === -4.0,
    'Very-Low-confidence points error is isolated.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_confidence'
    ][
        'Unavailable'
    ][
        'points'
    ][
        'mean_error'
    ]
    === 5.0,
    'Missing confidence evidence remains auditable as Unavailable.'
);


/*
 * ============================================================
 * SCENARIO E
 * CONFIDENCE LABEL IS HISTORICAL EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Preserved Confidence Classification<br>";
echo "============================================<br>";


$preservedLabelEvaluation =
    $evaluations[
        0
    ];


/*
 * Deliberately create disagreement between the numeric value and
 * historical label.
 *
 * The diagnostics service must use the recommendation-time label
 * rather than recalculating confidence classifications later.
 */
$preservedLabelEvaluation[
    'projection_confidence'
] = 0.10;


$preservedLabelEvaluation[
    'projection_confidence_percent'
] = 10.0;


$preservedLabelEvaluation[
    'projection_confidence_label'
] = 'High';


$result =
    $service->evaluate(
        [
            $preservedLabelEvaluation
        ]
    );


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_confidence'
    ][
        'High'
    ][
        'points'
    ][
        'sample_size'
    ]
    === 1,
    'Diagnostics use the preserved recommendation-time confidence label.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_confidence'
    ][
        'Very Low'
    ][
        'points'
    ][
        'sample_size'
    ]
    === 0,
    'Diagnostics do not retrospectively reclassify historical confidence.'
);


/*
 * ============================================================
 * SCENARIO F
 * UNKNOWN CONFIDENCE LABEL
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Unknown Confidence Label<br>";
echo "============================================<br>";


$unknownConfidenceEvaluation =
    $evaluations[
        0
    ];


$unknownConfidenceEvaluation[
    'projection_confidence_label'
] =
    'Unexpected Future Label';


$result =
    $service->evaluate(
        [
            $unknownConfidenceEvaluation
        ]
    );


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_confidence'
    ][
        'Unavailable'
    ][
        'points'
    ][
        'sample_size'
    ]
    === 1,
    'Unsupported historical confidence labels remain auditable as Unavailable.'
);


/*
 * ============================================================
 * SCENARIO G
 * MALFORMED EVALUATION EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Malformed Evaluation Evidence<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        [
            'invalid',
            123,
            null
        ]
    );


projectionCalibrationDiagnosticsTestResult(
    $result[
        'overall'
    ]
    ===
    $emptyMetrics,
    'Malformed non-array evaluations do not create projection metrics.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_position'
    ][
        'Unknown'
    ]
    ===
    $emptyMetrics,
    'Malformed evaluations do not create Unknown-position evidence.'
);


projectionCalibrationDiagnosticsTestResult(
    $result[
        'by_confidence'
    ][
        'Unavailable'
    ]
    ===
    $emptyMetrics,
    'Malformed evaluations do not create unavailable-confidence evidence.'
);


/*
 * ============================================================
 * SCENARIO H
 * SOURCE EVIDENCE IS IMMUTABLE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


$sourceEvaluations =
    $evaluations;


$originalEvaluations =
    $sourceEvaluations;


$service->evaluate(
    $sourceEvaluations
);


projectionCalibrationDiagnosticsTestResult(
    $sourceEvaluations
        ===
        $originalEvaluations,
    'Historical player-evaluation evidence is not mutated.'
);


/*
 * ============================================================
 * SCENARIO I
 * DIAGNOSTIC RESPONSIBILITY REMAINS NARROW
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Narrow Diagnostic Responsibility<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $evaluations
    );


projectionCalibrationDiagnosticsTestResult(
    !array_key_exists(
        'model_score',
        $result
    ),
    'Projection diagnostics do not create a synthetic model score.'
);


projectionCalibrationDiagnosticsTestResult(
    !array_key_exists(
        'recommended_parameters',
        $result
    ),
    'Projection diagnostics do not recommend model parameter changes.'
);


projectionCalibrationDiagnosticsTestResult(
    !array_key_exists(
        'best_model',
        $result
    ),
    'Projection diagnostics do not choose a preferred projection model.'
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