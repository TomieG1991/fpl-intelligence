<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Effective Confidence Weight Calibration Service Test<br>";
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

function effectiveConfidenceCalibrationCheck(
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


function effectiveConfidenceCalibrationApproximatelyEqual(
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


function effectiveConfidenceCalibrationThrows(
    callable $callback
): bool {

    try {

        $callback();

    } catch (InvalidArgumentException $exception) {

        return true;

    } catch (Throwable $throwable) {

        return false;
    }


    return false;
}


function effectiveConfidenceCalibrationSummary(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Effective Confidence Weight Calibration Service Test Summary<br>";
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


effectiveConfidenceCalibrationCheck(
    'EffectiveConfidenceWeightCalibrationService class exists',
    class_exists(
        'EffectiveConfidenceWeightCalibrationService'
    )
);


if (
    class_exists(
        'EffectiveConfidenceWeightCalibrationService'
    )
) {

    $reflection =
        new ReflectionClass(
            'EffectiveConfidenceWeightCalibrationService'
        );


    effectiveConfidenceCalibrationCheck(
        'EffectiveConfidenceWeightCalibrationService exposes evaluate()',
        $reflection->hasMethod(
            'evaluate'
        )
    );

} else {

    effectiveConfidenceCalibrationCheck(
        'EffectiveConfidenceWeightCalibrationService exposes evaluate()',
        false
    );
}


echo "<br>";


/*
 * ============================================================
 * STOP IF PRODUCTION CLASS DOES NOT YET EXIST
 * ============================================================
 */

if (
    !class_exists(
        'EffectiveConfidenceWeightCalibrationService'
    )
) {

    effectiveConfidenceCalibrationSummary();

    exit;
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

$metricsService =
    new EffectiveConfidenceBacktestMetricsService();


$service =
    new EffectiveConfidenceWeightCalibrationService(
        $metricsService
    );


/*
 * ============================================================
 * CONTROLLED HISTORICAL EVIDENCE
 * ============================================================
 */

$historicalRows = [

    /*
     * Current production 40/60:
     *
     * .30 × .40 + .80 × .60
     * = .12 + .48
     * = .60
     */
    [
        'player_id' => 101,
        'sample_confidence' => 0.30,
        'participation_rate' => 0.80,
        'actual_minutes' => 90,
        'actual_fixture_count' => 1
    ],

    /*
     * .40 × .40 + 1.00 × .60
     * = .16 + .60
     * = .76
     */
    [
        'player_id' => 102,
        'sample_confidence' => 0.40,
        'participation_rate' => 1.00,
        'actual_minutes' => 45,
        'actual_fixture_count' => 1
    ],

    /*
     * .35 × .40 + .75 × .60
     * = .14 + .45
     * = .59
     */
    [
        'player_id' => 103,
        'sample_confidence' => 0.35,
        'participation_rate' => 0.75,
        'actual_minutes' => 135,
        'actual_fixture_count' => 2
    ]
];


$weightCandidates = [

    [
        'sample_weight' => 1.00,
        'participation_weight' => 0.00
    ],

    [
        'sample_weight' => 0.40,
        'participation_weight' => 0.60
    ],

    [
        'sample_weight' => 0.00,
        'participation_weight' => 1.00
    ]
];


/*
 * ============================================================
 * SCENARIO B
 * EMPTY CANDIDATE SET
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Empty Candidate Set<br>";
echo "============================================<br>";


$emptyCandidateResult =
    $service->evaluate(
        $historicalRows,
        []
    );


effectiveConfidenceCalibrationCheck(
    'Empty candidate set returns an empty evaluations array',
    (
        $emptyCandidateResult[
            'evaluations'
        ]
        ??
        null
    )
    ===
    []
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * CURRENT PRODUCTION 40/60 BLEND
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Current Production Blend<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $historicalRows,
        $weightCandidates
    );


$evaluations =
    $result[
        'evaluations'
    ]
    ?? [];


effectiveConfidenceCalibrationCheck(
    'One evaluation is returned for every supplied candidate',
    count(
        $evaluations
    )
    ===
    3
);


$currentEvaluation =
    $evaluations[
        1
    ]
    ?? [];


effectiveConfidenceCalibrationCheck(
    'Current candidate preserves the supplied Sample Confidence weight',
    effectiveConfidenceCalibrationApproximatelyEqual(
        $currentEvaluation[
            'sample_weight'
        ]
        ?? null,
        0.40
    )
);


effectiveConfidenceCalibrationCheck(
    'Current candidate preserves the supplied Participation Rate weight',
    effectiveConfidenceCalibrationApproximatelyEqual(
        $currentEvaluation[
            'participation_weight'
        ]
        ?? null,
        0.60
    )
);


$currentScores =
    $currentEvaluation[
        'player_scores'
    ]
    ?? [];


effectiveConfidenceCalibrationCheck(
    '40/60 blend recalculates Player 101 Effective Confidence',
    effectiveConfidenceCalibrationApproximatelyEqual(
        $currentScores[
            0
        ][
            'candidate_effective_confidence'
        ]
        ?? null,
        0.60
    )
);


effectiveConfidenceCalibrationCheck(
    '40/60 blend recalculates Player 102 Effective Confidence',
    effectiveConfidenceCalibrationApproximatelyEqual(
        $currentScores[
            1
        ][
            'candidate_effective_confidence'
        ]
        ?? null,
        0.76
    )
);


effectiveConfidenceCalibrationCheck(
    '40/60 blend recalculates Player 103 Effective Confidence',
    effectiveConfidenceCalibrationApproximatelyEqual(
        $currentScores[
            2
        ][
            'candidate_effective_confidence'
        ]
        ?? null,
        0.59
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * ALTERNATIVE WEIGHTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Alternative Weights<br>";
echo "============================================<br>";


$sampleOnlyScores =
    $evaluations[
        0
    ][
        'player_scores'
    ]
    ?? [];


$participationOnlyScores =
    $evaluations[
        2
    ][
        'player_scores'
    ]
    ?? [];


effectiveConfidenceCalibrationCheck(
    '100/0 candidate returns preserved Sample Confidence',
    effectiveConfidenceCalibrationApproximatelyEqual(
        $sampleOnlyScores[
            0
        ][
            'candidate_effective_confidence'
        ]
        ?? null,
        0.30
    )
);


effectiveConfidenceCalibrationCheck(
    '0/100 candidate returns preserved Participation Rate',
    effectiveConfidenceCalibrationApproximatelyEqual(
        $participationOnlyScores[
            0
        ][
            'candidate_effective_confidence'
        ]
        ?? null,
        0.80
    )
);


effectiveConfidenceCalibrationCheck(
    'Alternative candidates genuinely produce different confidence values',
    !effectiveConfidenceCalibrationApproximatelyEqual(
        $sampleOnlyScores[
            0
        ][
            'candidate_effective_confidence'
        ]
        ?? null,
        $participationOnlyScores[
            0
        ][
            'candidate_effective_confidence'
        ]
        ?? 0.30
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * AUDITABLE PLAYER EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Auditable Player Evidence<br>";
echo "============================================<br>";


effectiveConfidenceCalibrationCheck(
    'Player identity is preserved in calibration output',
    (
        $currentScores[
            0
        ][
            'player_id'
        ]
        ??
        null
    )
    ===
    101
);


effectiveConfidenceCalibrationCheck(
    'Realised minutes are preserved for metrics evaluation',
    (
        $currentScores[
            0
        ][
            'actual_minutes'
        ]
        ??
        null
    )
    ===
    90
);


effectiveConfidenceCalibrationCheck(
    'Realised fixture count is preserved for metrics evaluation',
    (
        $currentScores[
            0
        ][
            'actual_fixture_count'
        ]
        ??
        null
    )
    ===
    1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * MISSING CALIBRATION INPUTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Missing Calibration Inputs<br>";
echo "============================================<br>";


$missingInputResult =
    $service->evaluate(
        [
            [
                'player_id' => 201,
                'sample_confidence' => null,
                'participation_rate' => 0.80,
                'actual_minutes' => 90,
                'actual_fixture_count' => 1
            ],
            [
                'player_id' => 202,
                'sample_confidence' => 0.40,
                'participation_rate' => null,
                'actual_minutes' => 90,
                'actual_fixture_count' => 1
            ],
            [
                'player_id' => 203,
                'sample_confidence' => 'unknown',
                'participation_rate' => 0.80,
                'actual_minutes' => 90,
                'actual_fixture_count' => 1
            ],
            [
                'player_id' => 204,
                'sample_confidence' => 0.40,
                'participation_rate' => 'unknown',
                'actual_minutes' => 90,
                'actual_fixture_count' => 1
            ]
        ],
        [
            [
                'sample_weight' => 0.40,
                'participation_weight' => 0.60
            ]
        ]
    );


$missingScores =
    $missingInputResult[
        'evaluations'
    ][0][
        'player_scores'
    ]
    ?? [];


effectiveConfidenceCalibrationCheck(
    'Missing Sample Confidence makes candidate confidence unavailable',
    array_key_exists(
        'candidate_effective_confidence',
        $missingScores[0] ?? []
    )
    &&
    $missingScores[0][
        'candidate_effective_confidence'
    ]
    === null
);


effectiveConfidenceCalibrationCheck(
    'Missing Participation Rate makes candidate confidence unavailable',
    array_key_exists(
        'candidate_effective_confidence',
        $missingScores[1] ?? []
    )
    &&
    $missingScores[1][
        'candidate_effective_confidence'
    ]
    === null
);


effectiveConfidenceCalibrationCheck(
    'Non-numeric Sample Confidence makes candidate confidence unavailable',
    (
        $missingScores[
            2
        ][
            'candidate_effective_confidence'
        ]
        ??
        null
    )
    ===
    null
);


effectiveConfidenceCalibrationCheck(
    'Non-numeric Participation Rate makes candidate confidence unavailable',
    (
        $missingScores[
            3
        ][
            'candidate_effective_confidence'
        ]
        ??
        null
    )
    ===
    null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * OUTCOME EVIDENCE INDEPENDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Outcome Evidence Independence<br>";
echo "============================================<br>";


$missingOutcomeResult =
    $service->evaluate(
        [
            [
                'player_id' => 301,
                'sample_confidence' => 0.40,
                'participation_rate' => 1.00,
                'actual_minutes' => null,
                'actual_fixture_count' => null
            ]
        ],
        [
            [
                'sample_weight' => 0.40,
                'participation_weight' => 0.60
            ]
        ]
    );


$missingOutcomeScore =
    $missingOutcomeResult[
        'evaluations'
    ][0][
        'player_scores'
    ][0]
    ?? [];


effectiveConfidenceCalibrationCheck(
    'Missing realised outcome does not prevent candidate confidence calculation',
    effectiveConfidenceCalibrationApproximatelyEqual(
        $missingOutcomeScore[
            'candidate_effective_confidence'
        ]
        ?? null,
        0.76
    )
);


effectiveConfidenceCalibrationCheck(
    'Missing realised minutes remain explicitly null',
    array_key_exists(
        'actual_minutes',
        $missingOutcomeScore
    )
    &&
    $missingOutcomeScore[
        'actual_minutes'
    ]
    ===
    null
);


effectiveConfidenceCalibrationCheck(
    'Missing realised fixture count remains explicitly null',
    array_key_exists(
        'actual_fixture_count',
        $missingOutcomeScore
    )
    &&
    $missingOutcomeScore[
        'actual_fixture_count'
    ]
    ===
    null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * GENUINE ZERO VALUES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Genuine Zero Values<br>";
echo "============================================<br>";


$zeroResult =
    $service->evaluate(
        [
            [
                'player_id' => 401,
                'sample_confidence' => 0.00,
                'participation_rate' => 0.00,
                'actual_minutes' => 0,
                'actual_fixture_count' => 1
            ]
        ],
        [
            [
                'sample_weight' => 0.40,
                'participation_weight' => 0.60
            ]
        ]
    );


$zeroScore =
    $zeroResult[
        'evaluations'
    ][0][
        'player_scores'
    ][0]
    ?? [];


effectiveConfidenceCalibrationCheck(
    'Genuine zero Sample Confidence remains valid',
    effectiveConfidenceCalibrationApproximatelyEqual(
        $zeroScore[
            'candidate_effective_confidence'
        ]
        ?? null,
        0.0
    )
);


effectiveConfidenceCalibrationCheck(
    'Genuine zero realised minutes are preserved',
    array_key_exists(
        'actual_minutes',
        $zeroScore
    )
    &&
    $zeroScore[
        'actual_minutes'
    ]
    ===
    0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * MALFORMED HISTORICAL ROWS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Malformed Historical Rows<br>";
echo "============================================<br>";


$malformedResult =
    $service->evaluate(
        [
            'malformed',
            null,
            [
                'player_id' => 501,
                'sample_confidence' => 0.40,
                'participation_rate' => 0.80,
                'actual_minutes' => 90,
                'actual_fixture_count' => 1
            ]
        ],
        [
            [
                'sample_weight' => 0.40,
                'participation_weight' => 0.60
            ]
        ]
    );


$malformedScores =
    $malformedResult[
        'evaluations'
    ][0][
        'player_scores'
    ]
    ?? [];


effectiveConfidenceCalibrationCheck(
    'Malformed non-array historical rows are ignored entirely',
    count(
        $malformedScores
    )
    ===
    1
);


effectiveConfidenceCalibrationCheck(
    'Valid evidence after malformed rows is still evaluated',
    (
        $malformedScores[
            0
        ][
            'player_id'
        ]
        ??
        null
    )
    ===
    501
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * METRICS DELEGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Metrics Delegation<br>";
echo "============================================<br>";


$currentMetrics =
    $currentEvaluation[
        'metrics'
    ]
    ?? [];


effectiveConfidenceCalibrationCheck(
    'Candidate metrics are returned by the calibration result',
    is_array(
        $currentMetrics
    )
);


effectiveConfidenceCalibrationCheck(
    'Metrics evaluate all controlled players',
    (
        $currentMetrics[
            'total_players'
        ]
        ??
        null
    )
    ===
    3
);


effectiveConfidenceCalibrationCheck(
    'All controlled players have comparable participation evidence',
    (
        $currentMetrics[
            'comparable_players'
        ]
        ??
        null
    )
    ===
    3
);


effectiveConfidenceCalibrationCheck(
    'Candidate metrics expose participation MAE',
    array_key_exists(
        'mean_absolute_error',
        $currentMetrics
    )
);


effectiveConfidenceCalibrationCheck(
    'Candidate metrics expose signed mean error',
    array_key_exists(
        'mean_error',
        $currentMetrics
    )
);


effectiveConfidenceCalibrationCheck(
    'Candidate metrics expose correlation',
    array_key_exists(
        'correlation',
        $currentMetrics
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * INVALID WEIGHT CANDIDATES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Invalid Weight Candidates<br>";
echo "============================================<br>";


effectiveConfidenceCalibrationCheck(
    'Non-array weight candidate is rejected',
    effectiveConfidenceCalibrationThrows(
        static function () use (
            $service,
            $historicalRows
        ): void {

            $service->evaluate(
                $historicalRows,
                [
                    'invalid'
                ]
            );
        }
    )
);


effectiveConfidenceCalibrationCheck(
    'Missing Sample weight is rejected',
    effectiveConfidenceCalibrationThrows(
        static function () use (
            $service,
            $historicalRows
        ): void {

            $service->evaluate(
                $historicalRows,
                [
                    [
                        'participation_weight' => 1.00
                    ]
                ]
            );
        }
    )
);


effectiveConfidenceCalibrationCheck(
    'Missing Participation weight is rejected',
    effectiveConfidenceCalibrationThrows(
        static function () use (
            $service,
            $historicalRows
        ): void {

            $service->evaluate(
                $historicalRows,
                [
                    [
                        'sample_weight' => 1.00
                    ]
                ]
            );
        }
    )
);


effectiveConfidenceCalibrationCheck(
    'Non-numeric Sample weight is rejected',
    effectiveConfidenceCalibrationThrows(
        static function () use (
            $service,
            $historicalRows
        ): void {

            $service->evaluate(
                $historicalRows,
                [
                    [
                        'sample_weight' => 'invalid',
                        'participation_weight' => 0.60
                    ]
                ]
            );
        }
    )
);


effectiveConfidenceCalibrationCheck(
    'Non-numeric Participation weight is rejected',
    effectiveConfidenceCalibrationThrows(
        static function () use (
            $service,
            $historicalRows
        ): void {

            $service->evaluate(
                $historicalRows,
                [
                    [
                        'sample_weight' => 0.40,
                        'participation_weight' => 'invalid'
                    ]
                ]
            );
        }
    )
);


effectiveConfidenceCalibrationCheck(
    'Sample weight below zero is rejected',
    effectiveConfidenceCalibrationThrows(
        static function () use (
            $service,
            $historicalRows
        ): void {

            $service->evaluate(
                $historicalRows,
                [
                    [
                        'sample_weight' => -0.10,
                        'participation_weight' => 1.10
                    ]
                ]
            );
        }
    )
);


effectiveConfidenceCalibrationCheck(
    'Sample weight above one is rejected',
    effectiveConfidenceCalibrationThrows(
        static function () use (
            $service,
            $historicalRows
        ): void {

            $service->evaluate(
                $historicalRows,
                [
                    [
                        'sample_weight' => 1.10,
                        'participation_weight' => -0.10
                    ]
                ]
            );
        }
    )
);


effectiveConfidenceCalibrationCheck(
    'Participation weight below zero is rejected',
    effectiveConfidenceCalibrationThrows(
        static function () use (
            $service,
            $historicalRows
        ): void {

            $service->evaluate(
                $historicalRows,
                [
                    [
                        'sample_weight' => 1.10,
                        'participation_weight' => -0.10
                    ]
                ]
            );
        }
    )
);


effectiveConfidenceCalibrationCheck(
    'Participation weight above one is rejected',
    effectiveConfidenceCalibrationThrows(
        static function () use (
            $service,
            $historicalRows
        ): void {

            $service->evaluate(
                $historicalRows,
                [
                    [
                        'sample_weight' => -0.10,
                        'participation_weight' => 1.10
                    ]
                ]
            );
        }
    )
);


effectiveConfidenceCalibrationCheck(
    'Weights that do not sum to one are rejected',
    effectiveConfidenceCalibrationThrows(
        static function () use (
            $service,
            $historicalRows
        ): void {

            $service->evaluate(
                $historicalRows,
                [
                    [
                        'sample_weight' => 0.40,
                        'participation_weight' => 0.40
                    ]
                ]
            );
        }
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * FLOATING-POINT WEIGHT TOLERANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Floating-Point Weight Tolerance<br>";
echo "============================================<br>";


$floatingPointResult =
    $service->evaluate(
        $historicalRows,
        [
            [
                'sample_weight' => 0.3000001,
                'participation_weight' => 0.6999999
            ]
        ]
    );


effectiveConfidenceCalibrationCheck(
    'Mathematically valid decimal weights survive floating-point representation noise',
    count(
        $floatingPointResult[
            'evaluations'
        ]
        ??
        []
    )
    ===
    1
);


effectiveConfidenceCalibrationSummary();