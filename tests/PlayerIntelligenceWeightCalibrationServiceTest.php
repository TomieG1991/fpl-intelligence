<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Intelligence Weight Calibration Service Test<br>";
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

function playerIntelligenceWeightCalibrationCheck(
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


function playerIntelligenceWeightCalibrationApproximatelyEqual(
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


function playerIntelligenceWeightCalibrationSummary(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Player Intelligence Weight Calibration Service Test Summary<br>";
    echo "============================================<br>";


    echo "Passed: "
        . $passed
        . "<br>";


    echo "Failed: "
        . $failed
        . "<br><br>";


    if (
        $failed === 0
    ) {

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


playerIntelligenceWeightCalibrationCheck(
    'PlayerIntelligenceWeightCalibrationService class exists',
    class_exists(
        'PlayerIntelligenceWeightCalibrationService'
    )
);


if (
    class_exists(
        'PlayerIntelligenceWeightCalibrationService'
    )
) {

    $reflection =
        new ReflectionClass(
            'PlayerIntelligenceWeightCalibrationService'
        );


    playerIntelligenceWeightCalibrationCheck(
        'PlayerIntelligenceWeightCalibrationService exposes evaluate()',
        $reflection->hasMethod(
            'evaluate'
        )
    );

} else {

    playerIntelligenceWeightCalibrationCheck(
        'PlayerIntelligenceWeightCalibrationService exposes evaluate()',
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
        'PlayerIntelligenceWeightCalibrationService'
    )
) {

    playerIntelligenceWeightCalibrationSummary();

    exit;
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

$metricsService =
    new IntelligenceScoreBacktestMetricsService();


$service =
    new PlayerIntelligenceWeightCalibrationService(
        $metricsService
    );


/*
 * ============================================================
 * CONTROLLED HISTORICAL EVIDENCE
 * ============================================================
 *
 * These are recommendation-time component values paired with
 * realised completed-gameweek FPL points.
 */

$historicalRows = [

    [
        'player_id' => 101,
        'strength_rating' => 90.0,
        'fixture_rating' => 70.0,
        'availability_multiplier' => 1.00,
        'actual_points' => 10.0
    ],

    [
        'player_id' => 102,
        'strength_rating' => 80.0,
        'fixture_rating' => 90.0,
        'availability_multiplier' => 0.95,
        'actual_points' => 7.0
    ],

    [
        'player_id' => 103,
        'strength_rating' => 60.0,
        'fixture_rating' => 80.0,
        'availability_multiplier' => 0.85,
        'actual_points' => 3.0
    ]
];


$weightCandidates = [

    [
        'strength_weight' => 0.50,
        'fixture_weight' => 0.50
    ],

    [
        'strength_weight' => 0.65,
        'fixture_weight' => 0.35
    ],

    [
        'strength_weight' => 0.80,
        'fixture_weight' => 0.20
    ]
];


/*
 * ============================================================
 * SCENARIO B
 * EMPTY HISTORICAL EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Empty Historical Evidence<br>";
echo "============================================<br>";


$emptyResult =
    $service
        ->evaluate(
            [],
            $weightCandidates
        );


playerIntelligenceWeightCalibrationCheck(
    'Empty historical evidence still evaluates every supplied weight candidate',
    isset(
        $emptyResult[
            'evaluations'
        ]
    )
    &&
    is_array(
        $emptyResult[
            'evaluations'
        ]
    )
    &&
    count(
        $emptyResult[
            'evaluations'
        ]
    )
    === 3
);


$emptyFirstEvaluation =
    $emptyResult[
        'evaluations'
    ][
        0
    ]
    ??
    [];


playerIntelligenceWeightCalibrationCheck(
    'Empty evidence produces no candidate player scores',
    (
        $emptyFirstEvaluation[
            'player_scores'
        ]
        ??
        null
    )
    === []
);


playerIntelligenceWeightCalibrationCheck(
    'Empty evidence delegates to the existing zero-sample metrics contract',
    (
        $emptyFirstEvaluation[
            'metrics'
        ]
        ??
        null
    )
    === [
        'total_players' => 0,
        'comparable_players' => 0,
        'unavailable_players' => 0,
        'correlation' => null
    ]
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * CANDIDATE SCORE CALCULATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Candidate Score Calculation<br>";
echo "============================================<br>";


$calibrationResult =
    $service
        ->evaluate(
            $historicalRows,
            $weightCandidates
        );


$evaluations =
    $calibrationResult[
        'evaluations'
    ]
    ??
    [];


playerIntelligenceWeightCalibrationCheck(
    'One evaluation is returned for each supplied weight candidate',
    count(
        $evaluations
    )
    === 3
);


$currentStyleEvaluation =
    $evaluations[
        1
    ]
    ??
    [];


playerIntelligenceWeightCalibrationCheck(
    'Evaluation preserves the supplied Strength weight',
    playerIntelligenceWeightCalibrationApproximatelyEqual(
        $currentStyleEvaluation[
            'strength_weight'
        ]
        ??
        null,
        0.65
    )
);


playerIntelligenceWeightCalibrationCheck(
    'Evaluation preserves the supplied Fixture weight',
    playerIntelligenceWeightCalibrationApproximatelyEqual(
        $currentStyleEvaluation[
            'fixture_weight'
        ]
        ??
        null,
        0.35
    )
);


$currentStyleScores =
    $currentStyleEvaluation[
        'player_scores'
    ]
    ??
    [];


/*
 * Player 101:
 *
 * core =
 * (90 × 0.65) + (70 × 0.35)
 * = 58.5 + 24.5
 * = 83.0
 *
 * availability multiplier = 1.00
 *
 * candidate score = 83.0
 */

playerIntelligenceWeightCalibrationCheck(
    'Candidate score applies the supplied 65/35 weights',
    playerIntelligenceWeightCalibrationApproximatelyEqual(
        $currentStyleScores[
            0
        ][
            'candidate_intelligence_score'
        ]
        ??
        null,
        83.0
    )
);


/*
 * Player 102:
 *
 * core =
 * (80 × 0.65) + (90 × 0.35)
 * = 52 + 31.5
 * = 83.5
 *
 * candidate score =
 * 83.5 × 0.95
 * = 79.325
 */

playerIntelligenceWeightCalibrationCheck(
    'Candidate score applies the preserved historical Availability multiplier after the weighted core',
    playerIntelligenceWeightCalibrationApproximatelyEqual(
        $currentStyleScores[
            1
        ][
            'candidate_intelligence_score'
        ]
        ??
        null,
        79.325
    )
);


/*
 * Player 103:
 *
 * core =
 * (60 × 0.65) + (80 × 0.35)
 * = 39 + 28
 * = 67
 *
 * candidate score =
 * 67 × 0.85
 * = 56.95
 */

playerIntelligenceWeightCalibrationCheck(
    'Candidate calculation uses each player historical Availability multiplier independently',
    playerIntelligenceWeightCalibrationApproximatelyEqual(
        $currentStyleScores[
            2
        ][
            'candidate_intelligence_score'
        ]
        ??
        null,
        56.95
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * ALTERNATIVE WEIGHTS ARE ACTUALLY EVALUATED
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Alternative Weight Evaluation<br>";
echo "============================================<br>";


$fiftyFiftyEvaluation =
    $evaluations[
        0
    ]
    ??
    [];


$eightyTwentyEvaluation =
    $evaluations[
        2
    ]
    ??
    [];


playerIntelligenceWeightCalibrationCheck(
    '50/50 candidate uses its own supplied weights',
    playerIntelligenceWeightCalibrationApproximatelyEqual(
        $fiftyFiftyEvaluation[
            'player_scores'
        ][
            0
        ][
            'candidate_intelligence_score'
        ]
        ??
        null,
        80.0
    )
);


playerIntelligenceWeightCalibrationCheck(
    '80/20 candidate uses its own supplied weights',
    playerIntelligenceWeightCalibrationApproximatelyEqual(
        $eightyTwentyEvaluation[
            'player_scores'
        ][
            0
        ][
            'candidate_intelligence_score'
        ]
        ??
        null,
        86.0
    )
);


playerIntelligenceWeightCalibrationCheck(
    'Different supplied weight combinations can produce different candidate Intelligence Scores',
    !playerIntelligenceWeightCalibrationApproximatelyEqual(
        $fiftyFiftyEvaluation[
            'player_scores'
        ][
            0
        ][
            'candidate_intelligence_score'
        ]
        ??
        null,
        (
            $eightyTwentyEvaluation[
                'player_scores'
            ][
                0
            ][
                'candidate_intelligence_score'
            ]
            ??
            80.0
        )
    )
);


playerIntelligenceWeightCalibrationCheck(
    'Calibration service does not inject additional hidden weight candidates',
    count(
        $evaluations
    )
    ===
    count(
        $weightCandidates
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * METRICS ARE CALCULATED FOR EACH CANDIDATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Candidate Metrics<br>";
echo "============================================<br>";


foreach (
    $evaluations
    as $evaluationIndex =>
    $evaluation
) {

    $metrics =
        $evaluation[
            'metrics'
        ]
        ??
        [];


    playerIntelligenceWeightCalibrationCheck(
        'Candidate '
        . ($evaluationIndex + 1)
        . ' metrics count all valid historical rows',
        (
            $metrics[
                'total_players'
            ]
            ??
            null
        )
        === 3
    );


    playerIntelligenceWeightCalibrationCheck(
        'Candidate '
        . ($evaluationIndex + 1)
        . ' metrics recognise all controlled rows as comparable',
        (
            $metrics[
                'comparable_players'
            ]
            ??
            null
        )
        === 3
    );


    playerIntelligenceWeightCalibrationCheck(
        'Candidate '
        . ($evaluationIndex + 1)
        . ' metrics report no unavailable controlled rows',
        (
            $metrics[
                'unavailable_players'
            ]
            ??
            null
        )
        === 0
    );


    playerIntelligenceWeightCalibrationCheck(
        'Candidate '
        . ($evaluationIndex + 1)
        . ' exposes an objective Pearson correlation',
        is_numeric(
            $metrics[
                'correlation'
            ]
            ??
            null
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * MISSING CALIBRATION COMPONENTS ARE NOT MANUFACTURED
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Missing Calibration Components<br>";
echo "============================================<br>";


$incompleteHistoricalRows = [

    [
        'player_id' => 201,
        'strength_rating' => 90.0,
        'fixture_rating' => 80.0,
        'availability_multiplier' => 1.00,
        'actual_points' => 8.0
    ],

    [
        'player_id' => 202,
        'strength_rating' => null,
        'fixture_rating' => 80.0,
        'availability_multiplier' => 1.00,
        'actual_points' => 6.0
    ],

    [
        'player_id' => 203,
        'strength_rating' => 80.0,
        'fixture_rating' => null,
        'availability_multiplier' => 1.00,
        'actual_points' => 5.0
    ],

    [
        'player_id' => 204,
        'strength_rating' => 80.0,
        'fixture_rating' => 70.0,
        'availability_multiplier' => null,
        'actual_points' => 4.0
    ],

    [
        'player_id' => 205,
        'strength_rating' => 70.0,
        'fixture_rating' => 60.0,
        'availability_multiplier' => 1.00,
        'actual_points' => null
    ]
];


$incompleteResult =
    $service
        ->evaluate(
            $incompleteHistoricalRows,
            [
                [
                    'strength_weight' => 0.65,
                    'fixture_weight' => 0.35
                ]
            ]
        );


$incompleteEvaluation =
    $incompleteResult[
        'evaluations'
    ][
        0
    ]
    ??
    [];


$incompleteScores =
    $incompleteEvaluation[
        'player_scores'
    ]
    ??
    [];


playerIntelligenceWeightCalibrationCheck(
    'Valid historical calibration evidence still produces a candidate score',
    is_numeric(
        $incompleteScores[
            0
        ][
            'candidate_intelligence_score'
        ]
        ??
        null
    )
);


playerIntelligenceWeightCalibrationCheck(
    'Missing Player Strength is not treated as zero',
    array_key_exists(
        'candidate_intelligence_score',
        $incompleteScores[
            1
        ]
        ??
        []
    )
    &&
    $incompleteScores[
        1
    ][
        'candidate_intelligence_score'
    ]
    === null
);


playerIntelligenceWeightCalibrationCheck(
    'Missing Fixture rating is not treated as zero',
    array_key_exists(
        'candidate_intelligence_score',
        $incompleteScores[
            2
        ]
        ??
        []
    )
    &&
    $incompleteScores[
        2
    ][
        'candidate_intelligence_score'
    ]
    === null
);


playerIntelligenceWeightCalibrationCheck(
    'Missing Availability multiplier is not manufactured',
    array_key_exists(
        'candidate_intelligence_score',
        $incompleteScores[
            3
        ]
        ??
        []
    )
    &&
    $incompleteScores[
        3
    ][
        'candidate_intelligence_score'
    ]
    === null
);


playerIntelligenceWeightCalibrationCheck(
    'Missing realised points leave the candidate score calculable but the row unavailable for correlation',
    is_numeric(
        $incompleteScores[
            4
        ][
            'candidate_intelligence_score'
        ]
        ??
        null
    )
    &&
    (
        $incompleteScores[
            4
        ][
            'actual_points'
        ]
        ??
        null
    )
    === null
);


$incompleteMetrics =
    $incompleteEvaluation[
        'metrics'
    ]
    ??
    [];


playerIntelligenceWeightCalibrationCheck(
    'Rows missing any required calibration or realised evidence are unavailable to candidate metrics',
    (
        $incompleteMetrics[
            'total_players'
        ]
        ??
        null
    )
    === 5
    &&
    (
        $incompleteMetrics[
            'comparable_players'
        ]
        ??
        null
    )
    === 1
    &&
    (
        $incompleteMetrics[
            'unavailable_players'
        ]
        ??
        null
    )
    === 4
);


playerIntelligenceWeightCalibrationCheck(
    'One comparable observation leaves Pearson correlation undefined',
    array_key_exists(
        'correlation',
        $incompleteMetrics
    )
    &&
    $incompleteMetrics[
        'correlation'
    ]
    === null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * ZERO AND NEGATIVE REALISED RETURNS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Zero and Negative Realised Returns<br>";
echo "============================================<br>";


$zeroNegativeResult =
    $service
        ->evaluate(
            [
                [
                    'player_id' => 301,
                    'strength_rating' => 90.0,
                    'fixture_rating' => 90.0,
                    'availability_multiplier' => 1.00,
                    'actual_points' => 5.0
                ],
                [
                    'player_id' => 302,
                    'strength_rating' => 70.0,
                    'fixture_rating' => 70.0,
                    'availability_multiplier' => 1.00,
                    'actual_points' => 0.0
                ],
                [
                    'player_id' => 303,
                    'strength_rating' => 50.0,
                    'fixture_rating' => 50.0,
                    'availability_multiplier' => 1.00,
                    'actual_points' => -2.0
                ]
            ],
            [
                [
                    'strength_weight' => 0.65,
                    'fixture_weight' => 0.35
                ]
            ]
        );


$zeroNegativeMetrics =
    $zeroNegativeResult[
        'evaluations'
    ][
        0
    ][
        'metrics'
    ]
    ??
    [];


playerIntelligenceWeightCalibrationCheck(
    'Zero and negative realised FPL points remain valid comparable evidence',
    (
        $zeroNegativeMetrics[
            'total_players'
        ]
        ??
        null
    )
    === 3
    &&
    (
        $zeroNegativeMetrics[
            'comparable_players'
        ]
        ??
        null
    )
    === 3
    &&
    (
        $zeroNegativeMetrics[
            'unavailable_players'
        ]
        ??
        null
    )
    === 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * MALFORMED EVIDENCE AND SOURCE IMMUTABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Malformed Evidence and Source Immutability<br>";
echo "============================================<br>";


$sourceRows = [

    [
        'player_id' => 401,
        'strength_rating' => 90.0,
        'fixture_rating' => 80.0,
        'availability_multiplier' => 1.00,
        'actual_points' => 8.0
    ],

    'malformed row',

    [
        'player_id' => 402,
        'strength_rating' => 70.0,
        'fixture_rating' => 60.0,
        'availability_multiplier' => 1.00,
        'actual_points' => 4.0
    ]
];


$sourceRowsBeforeEvaluation =
    $sourceRows;


$sourceCandidates = [

    [
        'strength_weight' => 0.60,
        'fixture_weight' => 0.40
    ]
];


$sourceCandidatesBeforeEvaluation =
    $sourceCandidates;


$malformedResult =
    $service
        ->evaluate(
            $sourceRows,
            $sourceCandidates
        );


$malformedEvaluation =
    $malformedResult[
        'evaluations'
    ][
        0
    ]
    ??
    [];


playerIntelligenceWeightCalibrationCheck(
    'Malformed non-array evidence is ignored rather than manufactured into a player',
    count(
        $malformedEvaluation[
            'player_scores'
        ]
        ??
        []
    )
    === 2
);


playerIntelligenceWeightCalibrationCheck(
    'Malformed non-array evidence follows existing metrics semantics and is not counted as unavailable',
    (
        $malformedEvaluation[
            'metrics'
        ][
            'total_players'
        ]
        ??
        null
    )
    === 2
    &&
    (
        $malformedEvaluation[
            'metrics'
        ][
            'comparable_players'
        ]
        ??
        null
    )
    === 2
);


playerIntelligenceWeightCalibrationCheck(
    'Calibration does not mutate historical source evidence',
    $sourceRows
    ===
    $sourceRowsBeforeEvaluation
);


playerIntelligenceWeightCalibrationCheck(
    'Calibration does not mutate supplied weight candidates',
    $sourceCandidates
    ===
    $sourceCandidatesBeforeEvaluation
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * UNDEFINED CORRELATION IS PRESERVED
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Undefined Correlation<br>";
echo "============================================<br>";


$constantScoreResult =
    $service
        ->evaluate(
            [
                [
                    'player_id' => 501,
                    'strength_rating' => 80.0,
                    'fixture_rating' => 80.0,
                    'availability_multiplier' => 1.00,
                    'actual_points' => 10.0
                ],
                [
                    'player_id' => 502,
                    'strength_rating' => 80.0,
                    'fixture_rating' => 80.0,
                    'availability_multiplier' => 1.00,
                    'actual_points' => 5.0
                ],
                [
                    'player_id' => 503,
                    'strength_rating' => 80.0,
                    'fixture_rating' => 80.0,
                    'availability_multiplier' => 1.00,
                    'actual_points' => 1.0
                ]
            ],
            [
                [
                    'strength_weight' => 0.65,
                    'fixture_weight' => 0.35
                ]
            ]
        );


$constantScoreMetrics =
    $constantScoreResult[
        'evaluations'
    ][
        0
    ][
        'metrics'
    ]
    ??
    [];


playerIntelligenceWeightCalibrationCheck(
    'Zero candidate-score variance leaves Pearson correlation null rather than manufacturing zero',
    array_key_exists(
        'correlation',
        $constantScoreMetrics
    )
    &&
    $constantScoreMetrics[
        'correlation'
    ]
    === null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * INVALID WEIGHT CANDIDATES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Invalid Weight Candidates<br>";
echo "============================================<br>";


$invalidWeightCases = [

    [
        'description' =>
            'Missing Strength weight is rejected',

        'candidate' => [
            'fixture_weight' => 0.35
        ]
    ],

    [
        'description' =>
            'Missing Fixture weight is rejected',

        'candidate' => [
            'strength_weight' => 0.65
        ]
    ],

    [
        'description' =>
            'Non-numeric Strength weight is rejected',

        'candidate' => [
            'strength_weight' => 'invalid',
            'fixture_weight' => 0.35
        ]
    ],

    [
        'description' =>
            'Non-numeric Fixture weight is rejected',

        'candidate' => [
            'strength_weight' => 0.65,
            'fixture_weight' => 'invalid'
        ]
    ],

    [
        'description' =>
            'Negative Strength weight is rejected',

        'candidate' => [
            'strength_weight' => -0.10,
            'fixture_weight' => 1.10
        ]
    ],

    [
        'description' =>
            'Fixture weight above one is rejected',

        'candidate' => [
            'strength_weight' => 0.00,
            'fixture_weight' => 1.10
        ]
    ],

    [
        'description' =>
            'Weights that do not sum to one are rejected',

        'candidate' => [
            'strength_weight' => 0.60,
            'fixture_weight' => 0.30
        ]
    ]
];


foreach (
    $invalidWeightCases
    as $invalidWeightCase
) {

    $exceptionThrown =
        false;


    try {

        $service
            ->evaluate(
                $historicalRows,
                [
                    $invalidWeightCase[
                        'candidate'
                    ]
                ]
            );

    } catch (
        InvalidArgumentException $exception
    ) {

        $exceptionThrown =
            true;
    }


    playerIntelligenceWeightCalibrationCheck(
        $invalidWeightCase[
            'description'
        ],
        $exceptionThrown
    );
}


$malformedWeightCandidateRejected =
    false;


try {

    $service
        ->evaluate(
            $historicalRows,
            [
                'not an array'
            ]
        );

} catch (
    InvalidArgumentException $exception
) {

    $malformedWeightCandidateRejected =
        true;
}


playerIntelligenceWeightCalibrationCheck(
    'Malformed non-array weight candidate is rejected',
    $malformedWeightCandidateRejected
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * EMPTY WEIGHT CANDIDATE SET
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Empty Weight Candidate Set<br>";
echo "============================================<br>";


$emptyCandidateResult =
    $service
        ->evaluate(
            $historicalRows,
            []
        );


playerIntelligenceWeightCalibrationCheck(
    'No supplied weight candidates produce no evaluations',
    (
        $emptyCandidateResult[
            'evaluations'
        ]
        ??
        null
    )
    === []
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

playerIntelligenceWeightCalibrationSummary();