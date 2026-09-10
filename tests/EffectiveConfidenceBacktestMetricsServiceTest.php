<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Effective Confidence Backtest Metrics Service Test<br>";
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

function effectiveConfidenceMetricsCheck(
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


function effectiveConfidenceMetricsApproximatelyEqual(
    mixed $actual,
    float $expected,
    float $tolerance = 0.000001
): bool {

    if (!is_numeric($actual)) {

        return false;
    }


    return abs(
        (float) $actual
        -
        $expected
    )
    <=
    $tolerance;
}


function effectiveConfidenceMetricsSummary(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Effective Confidence Backtest Metrics Service Test Summary<br>";
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
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Class Contract<br>";
echo "============================================<br>";


effectiveConfidenceMetricsCheck(
    'EffectiveConfidenceBacktestMetricsService class exists',
    class_exists(
        'EffectiveConfidenceBacktestMetricsService'
    )
);


if (
    class_exists(
        'EffectiveConfidenceBacktestMetricsService'
    )
) {

    $reflection =
        new ReflectionClass(
            'EffectiveConfidenceBacktestMetricsService'
        );


    effectiveConfidenceMetricsCheck(
        'EffectiveConfidenceBacktestMetricsService exposes summarise()',
        $reflection->hasMethod(
            'summarise'
        )
    );

} else {

    effectiveConfidenceMetricsCheck(
        'EffectiveConfidenceBacktestMetricsService exposes summarise()',
        false
    );
}


echo "<br>";


/*
 * ============================================================
 * STOP UNTIL PRODUCTION CLASS EXISTS
 * ============================================================
 */

if (
    !class_exists(
        'EffectiveConfidenceBacktestMetricsService'
    )
) {

    effectiveConfidenceMetricsSummary();

    exit;
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

$service =
    new EffectiveConfidenceBacktestMetricsService();


/*
 * ============================================================
 * SCENARIO B
 * EMPTY EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Empty Evidence<br>";
echo "============================================<br>";


$emptyResult =
    $service->summarise(
        []
    );


effectiveConfidenceMetricsCheck(
    'Empty evidence contains zero total players',
    (
        $emptyResult[
            'total_players'
        ]
        ??
        null
    )
    ===
    0
);


effectiveConfidenceMetricsCheck(
    'Empty evidence contains zero comparable players',
    (
        $emptyResult[
            'comparable_players'
        ]
        ??
        null
    )
    ===
    0
);


effectiveConfidenceMetricsCheck(
    'Empty evidence contains zero unavailable players',
    (
        $emptyResult[
            'unavailable_players'
        ]
        ??
        null
    )
    ===
    0
);


effectiveConfidenceMetricsCheck(
    'Empty evidence has no mean absolute error',
    array_key_exists(
        'mean_absolute_error',
        $emptyResult
    )
    &&
    $emptyResult[
        'mean_absolute_error'
    ]
    ===
    null
);


effectiveConfidenceMetricsCheck(
    'Empty evidence has no mean error',
    array_key_exists(
        'mean_error',
        $emptyResult
    )
    &&
    $emptyResult[
        'mean_error'
    ]
    ===
    null
);


effectiveConfidenceMetricsCheck(
    'Empty evidence has no correlation',
    array_key_exists(
        'correlation',
        $emptyResult
    )
    &&
    $emptyResult[
        'correlation'
    ]
    ===
    null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * REALISED PARTICIPATION DERIVATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Realised Participation Derivation<br>";
echo "============================================<br>";


$participationRows = [

    /*
     * 90 / (1 × 90) = 1.00
     */
    [
        'player_id' => 101,
        'effective_confidence' => 0.80,
        'actual_minutes' => 90,
        'actual_fixture_count' => 1
    ],

    /*
     * 45 / (1 × 90) = 0.50
     */
    [
        'player_id' => 102,
        'effective_confidence' => 0.60,
        'actual_minutes' => 45,
        'actual_fixture_count' => 1
    ],

    /*
     * DGW:
     * 135 / (2 × 90) = 0.75
     */
    [
        'player_id' => 103,
        'effective_confidence' => 0.70,
        'actual_minutes' => 135,
        'actual_fixture_count' => 2
    ],

    /*
     * Genuine zero-minute outcome.
     */
    [
        'player_id' => 104,
        'effective_confidence' => 0.20,
        'actual_minutes' => 0,
        'actual_fixture_count' => 1
    ]
];


$participationResult =
    $service->summarise(
        $participationRows
    );


$participationComparisons =
    $participationResult[
        'comparisons'
    ]
    ?? [];


effectiveConfidenceMetricsCheck(
    'Every valid row becomes comparable',
    (
        $participationResult[
            'comparable_players'
        ]
        ??
        null
    )
    ===
    4
);


effectiveConfidenceMetricsCheck(
    'Single-fixture 90-minute outcome becomes 100% realised participation',
    effectiveConfidenceMetricsApproximatelyEqual(
        $participationComparisons[
            0
        ]['actual_participation_rate']
        ??
        null,
        1.00
    )
);


effectiveConfidenceMetricsCheck(
    'Single-fixture 45-minute outcome becomes 50% realised participation',
    effectiveConfidenceMetricsApproximatelyEqual(
        $participationComparisons[
            1
        ]['actual_participation_rate']
        ??
        null,
        0.50
    )
);


effectiveConfidenceMetricsCheck(
    'Double Gameweek minutes use total available fixture minutes',
    effectiveConfidenceMetricsApproximatelyEqual(
        $participationComparisons[
            2
        ]['actual_participation_rate']
        ??
        null,
        0.75
    )
);


effectiveConfidenceMetricsCheck(
    'Genuine zero minutes remain valid zero realised participation',
    effectiveConfidenceMetricsApproximatelyEqual(
        $participationComparisons[
            3
        ]['actual_participation_rate']
        ??
        null,
        0.00
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * REALISED PARTICIPATION CLAMP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Realised Participation Clamp<br>";
echo "============================================<br>";


$clampResult =
    $service->summarise(
        [
            [
                'player_id' => 201,
                'effective_confidence' => 0.90,
                'actual_minutes' => 100,
                'actual_fixture_count' => 1
            ]
        ]
    );


$clampComparison =
    $clampResult[
        'comparisons'
    ][0]
    ?? [];


effectiveConfidenceMetricsCheck(
    'Realised participation cannot exceed 100%',
    effectiveConfidenceMetricsApproximatelyEqual(
        $clampComparison[
            'actual_participation_rate'
        ]
        ??
        null,
        1.00
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * ERROR METRICS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Error Metrics<br>";
echo "============================================<br>";


/*
 * Comparisons:
 *
 * Player 1: predicted .80, actual 1.00
 * error = -.20
 * absolute error = .20
 *
 * Player 2: predicted .60, actual .50
 * error = +.10
 * absolute error = .10
 *
 * Player 3: predicted .70, actual .75
 * error = -.05
 * absolute error = .05
 *
 * Player 4: predicted .20, actual .00
 * error = +.20
 * absolute error = .20
 *
 * MAE:
 * (.20 + .10 + .05 + .20) / 4
 * = .1375
 *
 * Mean Error:
 * (-.20 + .10 -.05 + .20) / 4
 * = .0125
 */

effectiveConfidenceMetricsCheck(
    'Mean absolute error is calculated from confidence versus realised participation',
    effectiveConfidenceMetricsApproximatelyEqual(
        $participationResult[
            'mean_absolute_error'
        ]
        ??
        null,
        0.1375
    )
);


effectiveConfidenceMetricsCheck(
    'Mean error uses predicted minus actual participation',
    effectiveConfidenceMetricsApproximatelyEqual(
        $participationResult[
            'mean_error'
        ]
        ??
        null,
        0.0125
    )
);


effectiveConfidenceMetricsCheck(
    'Positive individual error means confidence exceeded realised participation',
    effectiveConfidenceMetricsApproximatelyEqual(
        $participationComparisons[
            1
        ]['error']
        ??
        null,
        0.10
    )
);


effectiveConfidenceMetricsCheck(
    'Negative individual error means confidence was below realised participation',
    effectiveConfidenceMetricsApproximatelyEqual(
        $participationComparisons[
            0
        ]['error']
        ??
        null,
        -0.20
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * PEARSON CORRELATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Pearson Correlation<br>";
echo "============================================<br>";


$perfectCorrelationResult =
    $service->summarise(
        [
            [
                'effective_confidence' => 0.20,
                'actual_minutes' => 18,
                'actual_fixture_count' => 1
            ],
            [
                'effective_confidence' => 0.50,
                'actual_minutes' => 45,
                'actual_fixture_count' => 1
            ],
            [
                'effective_confidence' => 0.80,
                'actual_minutes' => 72,
                'actual_fixture_count' => 1
            ]
        ]
    );


effectiveConfidenceMetricsCheck(
    'Perfectly aligned confidence and participation produce correlation of 1',
    effectiveConfidenceMetricsApproximatelyEqual(
        $perfectCorrelationResult[
            'correlation'
        ]
        ??
        null,
        1.00
    )
);


$inverseCorrelationResult =
    $service->summarise(
        [
            [
                'effective_confidence' => 0.20,
                'actual_minutes' => 72,
                'actual_fixture_count' => 1
            ],
            [
                'effective_confidence' => 0.50,
                'actual_minutes' => 45,
                'actual_fixture_count' => 1
            ],
            [
                'effective_confidence' => 0.80,
                'actual_minutes' => 18,
                'actual_fixture_count' => 1
            ]
        ]
    );


effectiveConfidenceMetricsCheck(
    'Perfect inverse relationship produces correlation of -1',
    effectiveConfidenceMetricsApproximatelyEqual(
        $inverseCorrelationResult[
            'correlation'
        ]
        ??
        null,
        -1.00
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * UNAVAILABLE EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Unavailable Evidence<br>";
echo "============================================<br>";


$unavailableResult =
    $service->summarise(
        [
            /*
             * Valid.
             */
            [
                'player_id' => 301,
                'effective_confidence' => 0.70,
                'actual_minutes' => 63,
                'actual_fixture_count' => 1
            ],

            /*
             * Missing confidence.
             */
            [
                'player_id' => 302,
                'effective_confidence' => null,
                'actual_minutes' => 90,
                'actual_fixture_count' => 1
            ],

            /*
             * Missing minutes.
             */
            [
                'player_id' => 303,
                'effective_confidence' => 0.80,
                'actual_minutes' => null,
                'actual_fixture_count' => 1
            ],

            /*
             * Missing fixture count.
             */
            [
                'player_id' => 304,
                'effective_confidence' => 0.80,
                'actual_minutes' => 45,
                'actual_fixture_count' => null
            ],

            /*
             * Zero fixture count.
             */
            [
                'player_id' => 305,
                'effective_confidence' => 0.80,
                'actual_minutes' => 0,
                'actual_fixture_count' => 0
            ],

            /*
             * Negative fixture count.
             */
            [
                'player_id' => 306,
                'effective_confidence' => 0.80,
                'actual_minutes' => 0,
                'actual_fixture_count' => -1
            ],

            /*
             * Non-numeric confidence.
             */
            [
                'player_id' => 307,
                'effective_confidence' => 'unknown',
                'actual_minutes' => 90,
                'actual_fixture_count' => 1
            ],

            /*
             * Non-numeric minutes.
             */
            [
                'player_id' => 308,
                'effective_confidence' => 0.80,
                'actual_minutes' => 'unknown',
                'actual_fixture_count' => 1
            ],

            /*
             * Non-numeric fixture count.
             */
            [
                'player_id' => 309,
                'effective_confidence' => 0.80,
                'actual_minutes' => 90,
                'actual_fixture_count' => 'unknown'
            ],

            /*
             * Malformed row must be ignored entirely.
             */
            'malformed'
        ]
    );


effectiveConfidenceMetricsCheck(
    'Malformed non-array rows are ignored entirely',
    (
        $unavailableResult[
            'total_players'
        ]
        ??
        null
    )
    ===
    9
);


effectiveConfidenceMetricsCheck(
    'Only rows with complete valid confidence and outcome evidence are comparable',
    (
        $unavailableResult[
            'comparable_players'
        ]
        ??
        null
    )
    ===
    1
);


effectiveConfidenceMetricsCheck(
    'Incomplete valid player rows are counted as unavailable',
    (
        $unavailableResult[
            'unavailable_players'
        ]
        ??
        null
    )
    ===
    8
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * ZERO CONFIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Genuine Zero Confidence<br>";
echo "============================================<br>";


$zeroConfidenceResult =
    $service->summarise(
        [
            [
                'player_id' => 401,
                'effective_confidence' => 0,
                'actual_minutes' => 0,
                'actual_fixture_count' => 1
            ]
        ]
    );


effectiveConfidenceMetricsCheck(
    'Genuine zero Effective Confidence remains valid evidence',
    (
        $zeroConfidenceResult[
            'comparable_players'
        ]
        ??
        null
    )
    ===
    1
);


effectiveConfidenceMetricsCheck(
    'Matching zero confidence and zero participation produce zero MAE',
    effectiveConfidenceMetricsApproximatelyEqual(
        $zeroConfidenceResult[
            'mean_absolute_error'
        ]
        ??
        null,
        0.0
    )
);


effectiveConfidenceMetricsCheck(
    'Matching zero confidence and zero participation produce zero mean error',
    effectiveConfidenceMetricsApproximatelyEqual(
        $zeroConfidenceResult[
            'mean_error'
        ]
        ??
        null,
        0.0
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * CORRELATION AVAILABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Correlation Availability<br>";
echo "============================================<br>";


$singleObservationResult =
    $service->summarise(
        [
            [
                'effective_confidence' => 0.70,
                'actual_minutes' => 63,
                'actual_fixture_count' => 1
            ]
        ]
    );


effectiveConfidenceMetricsCheck(
    'Correlation is unavailable with fewer than two observations',
    array_key_exists(
        'correlation',
        $singleObservationResult
    )
    &&
    $singleObservationResult[
        'correlation'
    ]
    ===
    null
);


$zeroConfidenceVarianceResult =
    $service->summarise(
        [
            [
                'effective_confidence' => 0.50,
                'actual_minutes' => 18,
                'actual_fixture_count' => 1
            ],
            [
                'effective_confidence' => 0.50,
                'actual_minutes' => 72,
                'actual_fixture_count' => 1
            ]
        ]
    );


effectiveConfidenceMetricsCheck(
    'Correlation is unavailable when confidence has zero variance',
    array_key_exists(
        'correlation',
        $zeroConfidenceVarianceResult
    )
    &&
    $zeroConfidenceVarianceResult[
        'correlation'
    ]
    ===
    null
);


$zeroParticipationVarianceResult =
    $service->summarise(
        [
            [
                'effective_confidence' => 0.20,
                'actual_minutes' => 45,
                'actual_fixture_count' => 1
            ],
            [
                'effective_confidence' => 0.80,
                'actual_minutes' => 45,
                'actual_fixture_count' => 1
            ]
        ]
    );


effectiveConfidenceMetricsCheck(
    'Correlation is unavailable when realised participation has zero variance',
    array_key_exists(
        'correlation',
        $zeroParticipationVarianceResult
    )
    &&
    $zeroParticipationVarianceResult[
        'correlation'
    ]
    ===
    null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * PRIMARY EVIDENCE OWNERSHIP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Primary Evidence Ownership<br>";
echo "============================================<br>";


$derivedEvidenceResult =
    $service->summarise(
        [
            [
                'effective_confidence' => 0.80,
                'actual_minutes' => 45,
                'actual_fixture_count' => 1,

                /*
                 * Deliberately incorrect derived values.
                 *
                 * The metrics service must calculate these itself
                 * from primary evidence rather than trusting them.
                 */
                'actual_participation_rate' => 1.00,
                'error' => 999,
                'absolute_error' => 999
            ]
        ]
    );


$derivedComparison =
    $derivedEvidenceResult[
        'comparisons'
    ][0]
    ?? [];


effectiveConfidenceMetricsCheck(
    'Realised participation is derived from actual minutes and fixture count',
    effectiveConfidenceMetricsApproximatelyEqual(
        $derivedComparison[
            'actual_participation_rate'
        ]
        ??
        null,
        0.50
    )
);


effectiveConfidenceMetricsCheck(
    'Prediction error is derived from primary evidence',
    effectiveConfidenceMetricsApproximatelyEqual(
        $derivedComparison[
            'error'
        ]
        ??
        null,
        0.30
    )
);


effectiveConfidenceMetricsCheck(
    'Absolute error is derived from primary evidence',
    effectiveConfidenceMetricsApproximatelyEqual(
        $derivedComparison[
            'absolute_error'
        ]
        ??
        null,
        0.30
    )
);


effectiveConfidenceMetricsSummary();