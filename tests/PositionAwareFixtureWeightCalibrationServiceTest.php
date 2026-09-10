<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Position-Aware Fixture Weight Calibration Service Test<br>";
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

function positionAwareFixtureCalibrationCheck(
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


function positionAwareFixtureCalibrationApproximatelyEqual(
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


function positionAwareFixtureCalibrationSummary(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Position-Aware Fixture Weight Calibration Service Test Summary<br>";
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


positionAwareFixtureCalibrationCheck(
    'PositionAwareFixtureWeightCalibrationService class exists',
    class_exists(
        'PositionAwareFixtureWeightCalibrationService'
    )
);


if (
    class_exists(
        'PositionAwareFixtureWeightCalibrationService'
    )
) {

    $reflection =
        new ReflectionClass(
            'PositionAwareFixtureWeightCalibrationService'
        );


    positionAwareFixtureCalibrationCheck(
        'PositionAwareFixtureWeightCalibrationService exposes evaluate()',
        $reflection->hasMethod(
            'evaluate'
        )
    );

} else {

    positionAwareFixtureCalibrationCheck(
        'PositionAwareFixtureWeightCalibrationService exposes evaluate()',
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
        'PositionAwareFixtureWeightCalibrationService'
    )
) {

    positionAwareFixtureCalibrationSummary();

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
    new PositionAwareFixtureWeightCalibrationService(
        $metricsService
    );


/*
 * ============================================================
 * CONTROLLED HISTORICAL EVIDENCE
 * ============================================================
 */

$historicalRows = [

    /*
     * DEF:
     *
     * base = 80
     * opponent Attack = 40
     * performance opportunity = 60
     *
     * 75/25:
     * 80 × .75 + 60 × .25 = 75
     */
    [
        'player_id' => 101,
        'position' => 'DEF',
        'base_next_fixture_rating' => 80.0,
        'next_opponent_attack_rating' => 40.0,
        'next_opponent_defence_rating' => 90.0,
        'actual_points' => 8.0
    ],

    /*
     * MID:
     *
     * base = 70
     * opponent Defence = 20
     * performance opportunity = 80
     *
     * 75/25:
     * 70 × .75 + 80 × .25 = 72.5
     */
    [
        'player_id' => 102,
        'position' => 'MID',
        'base_next_fixture_rating' => 70.0,
        'next_opponent_attack_rating' => 95.0,
        'next_opponent_defence_rating' => 20.0,
        'actual_points' => 6.0
    ],

    /*
     * GK:
     *
     * base = 30
     * opponent Attack = 80
     * performance opportunity = 20
     *
     * 75/25 = 27.5
     */
    [
        'player_id' => 103,
        'position' => 'GK',
        'base_next_fixture_rating' => 30.0,
        'next_opponent_attack_rating' => 80.0,
        'next_opponent_defence_rating' => 10.0,
        'actual_points' => 2.0
    ],

    /*
     * FWD:
     *
     * base = 60
     * opponent Defence = 60
     * performance opportunity = 40
     *
     * 75/25 = 55
     */
    [
        'player_id' => 104,
        'position' => 'FWD',
        'base_next_fixture_rating' => 60.0,
        'next_opponent_attack_rating' => 5.0,
        'next_opponent_defence_rating' => 60.0,
        'actual_points' => 4.0
    ]
];


$weightCandidates = [

    [
        'base_fixture_weight' => 1.00,
        'position_performance_weight' => 0.00
    ],

    [
        'base_fixture_weight' => 0.75,
        'position_performance_weight' => 0.25
    ],

    [
        'base_fixture_weight' => 0.50,
        'position_performance_weight' => 0.50
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
    $service->evaluate(
        [],
        $weightCandidates
    );


positionAwareFixtureCalibrationCheck(
    'Empty historical evidence still evaluates every supplied candidate',
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


$emptyEvaluation =
    $emptyResult[
        'evaluations'
    ][0]
    ?? [];


positionAwareFixtureCalibrationCheck(
    'Empty evidence produces no player scores',
    (
        $emptyEvaluation[
            'player_scores'
        ]
        ?? null
    )
    === []
);


positionAwareFixtureCalibrationCheck(
    'Empty evidence preserves the existing zero-sample metrics contract',
    (
        $emptyEvaluation[
            'metrics'
        ]
        ?? null
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
 * CURRENT PRODUCTION 75/25 CALCULATION
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


positionAwareFixtureCalibrationCheck(
    'One evaluation is returned for each supplied candidate',
    count(
        $evaluations
    )
    === 3
);


$currentEvaluation =
    $evaluations[
        1
    ]
    ?? [];


positionAwareFixtureCalibrationCheck(
    'Evaluation preserves the supplied base Fixture weight',
    positionAwareFixtureCalibrationApproximatelyEqual(
        $currentEvaluation[
            'base_fixture_weight'
        ]
        ?? null,
        0.75
    )
);


positionAwareFixtureCalibrationCheck(
    'Evaluation preserves the supplied position-performance weight',
    positionAwareFixtureCalibrationApproximatelyEqual(
        $currentEvaluation[
            'position_performance_weight'
        ]
        ?? null,
        0.25
    )
);


$currentScores =
    $currentEvaluation[
        'player_scores'
    ]
    ?? [];


positionAwareFixtureCalibrationCheck(
    'DEF uses opponent Attack Rating for the current 75/25 blend',
    positionAwareFixtureCalibrationApproximatelyEqual(
        $currentScores[
            0
        ][
            'candidate_fixture_rating'
        ]
        ?? null,
        75.0
    )
);


positionAwareFixtureCalibrationCheck(
    'MID uses opponent Defence Rating for the current 75/25 blend',
    positionAwareFixtureCalibrationApproximatelyEqual(
        $currentScores[
            1
        ][
            'candidate_fixture_rating'
        ]
        ?? null,
        72.5
    )
);


positionAwareFixtureCalibrationCheck(
    'GK uses opponent Attack Rating for the current 75/25 blend',
    positionAwareFixtureCalibrationApproximatelyEqual(
        $currentScores[
            2
        ][
            'candidate_fixture_rating'
        ]
        ?? null,
        27.5
    )
);


positionAwareFixtureCalibrationCheck(
    'FWD uses opponent Defence Rating for the current 75/25 blend',
    positionAwareFixtureCalibrationApproximatelyEqual(
        $currentScores[
            3
        ][
            'candidate_fixture_rating'
        ]
        ?? null,
        55.0
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


$baseOnlyScores =
    $evaluations[
        0
    ][
        'player_scores'
    ]
    ?? [];


$equalBlendScores =
    $evaluations[
        2
    ][
        'player_scores'
    ]
    ?? [];


positionAwareFixtureCalibrationCheck(
    '100/0 candidate returns the preserved base fixture evidence',
    positionAwareFixtureCalibrationApproximatelyEqual(
        $baseOnlyScores[
            0
        ][
            'candidate_fixture_rating'
        ]
        ?? null,
        80.0
    )
);


positionAwareFixtureCalibrationCheck(
    '50/50 candidate genuinely recalculates the blend',
    positionAwareFixtureCalibrationApproximatelyEqual(
        $equalBlendScores[
            0
        ][
            'candidate_fixture_rating'
        ]
        ?? null,
        70.0
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * RELEVANT OPPONENT EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Relevant Opponent Evidence<br>";
echo "============================================<br>";


$irrelevantEvidenceRows = [

    [
        'player_id' => 201,
        'position' => 'DEF',
        'base_next_fixture_rating' => 70.0,
        'next_opponent_attack_rating' => null,
        'next_opponent_defence_rating' => 10.0,
        'actual_points' => 5.0
    ],

    [
        'player_id' => 202,
        'position' => 'MID',
        'base_next_fixture_rating' => 70.0,
        'next_opponent_attack_rating' => 10.0,
        'next_opponent_defence_rating' => null,
        'actual_points' => 5.0
    ]
];


$irrelevantEvidenceResult =
    $service->evaluate(
        $irrelevantEvidenceRows,
        [
            [
                'base_fixture_weight' => 0.75,
                'position_performance_weight' => 0.25
            ]
        ]
    );


$irrelevantScores =
    $irrelevantEvidenceResult[
        'evaluations'
    ][0][
        'player_scores'
    ]
    ?? [];


positionAwareFixtureCalibrationCheck(
    'DEF calibration is unavailable when opponent Attack evidence is missing',
    array_key_exists(
        'candidate_fixture_rating',
        $irrelevantScores[0] ?? []
    )
    &&
    $irrelevantScores[0][
        'candidate_fixture_rating'
    ] === null
);


positionAwareFixtureCalibrationCheck(
    'MID calibration is unavailable when opponent Defence evidence is missing',
    array_key_exists(
        'candidate_fixture_rating',
        $irrelevantScores[1] ?? []
    )
    &&
    $irrelevantScores[1][
        'candidate_fixture_rating'
    ] === null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * MISSING BASE / INVALID POSITION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Missing Required Evidence<br>";
echo "============================================<br>";


$missingEvidenceRows = [

    [
        'player_id' => 301,
        'position' => 'FWD',
        'base_next_fixture_rating' => null,
        'next_opponent_attack_rating' => 50.0,
        'next_opponent_defence_rating' => 40.0,
        'actual_points' => 3.0
    ],

    [
        'player_id' => 302,
        'position' => 'UNKNOWN',
        'base_next_fixture_rating' => 70.0,
        'next_opponent_attack_rating' => 50.0,
        'next_opponent_defence_rating' => 40.0,
        'actual_points' => 3.0
    ]
];


$missingEvidenceResult =
    $service->evaluate(
        $missingEvidenceRows,
        [
            [
                'base_fixture_weight' => 0.75,
                'position_performance_weight' => 0.25
            ]
        ]
    );


$missingEvidenceScores =
    $missingEvidenceResult[
        'evaluations'
    ][0][
        'player_scores'
    ]
    ?? [];


positionAwareFixtureCalibrationCheck(
    'Missing base fixture evidence produces an unavailable candidate score',
    ($missingEvidenceScores[0]['candidate_fixture_rating'] ?? null)
    === null
);


positionAwareFixtureCalibrationCheck(
    'Invalid historical position produces an unavailable candidate score',
    ($missingEvidenceScores[1]['candidate_fixture_rating'] ?? null)
    === null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * PRODUCTION BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Production Bounds<br>";
echo "============================================<br>";


$boundedRows = [

    [
        'player_id' => 401,
        'position' => 'DEF',
        'base_next_fixture_rating' => 120.0,
        'next_opponent_attack_rating' => -20.0,
        'next_opponent_defence_rating' => 50.0,
        'actual_points' => 5.0
    ]
];


$boundedResult =
    $service->evaluate(
        $boundedRows,
        [
            [
                'base_fixture_weight' => 0.75,
                'position_performance_weight' => 0.25
            ]
        ]
    );


$boundedScore =
    $boundedResult[
        'evaluations'
    ][0][
        'player_scores'
    ][0][
        'candidate_fixture_rating'
    ]
    ?? null;


/*
 * base clamps to 100
 * opponent Attack clamps to 0
 * performance opportunity = 100
 * final = 100
 */
positionAwareFixtureCalibrationCheck(
    'Candidate calculation applies the same 0-100 bounds as production',
    positionAwareFixtureCalibrationApproximatelyEqual(
        $boundedScore,
        100.0
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * REALISED ZERO / NEGATIVE POINTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Zero and Negative Returns<br>";
echo "============================================<br>";


$zeroNegativeRows = [

    [
        'player_id' => 501,
        'position' => 'MID',
        'base_next_fixture_rating' => 80.0,
        'next_opponent_attack_rating' => 50.0,
        'next_opponent_defence_rating' => 20.0,
        'actual_points' => 0
    ],

    [
        'player_id' => 502,
        'position' => 'FWD',
        'base_next_fixture_rating' => 20.0,
        'next_opponent_attack_rating' => 50.0,
        'next_opponent_defence_rating' => 80.0,
        'actual_points' => -1
    ]
];


$zeroNegativeResult =
    $service->evaluate(
        $zeroNegativeRows,
        [
            [
                'base_fixture_weight' => 0.75,
                'position_performance_weight' => 0.25
            ]
        ]
    );


$zeroNegativeMetrics =
    $zeroNegativeResult[
        'evaluations'
    ][0][
        'metrics'
    ]
    ?? [];


positionAwareFixtureCalibrationCheck(
    'Genuine zero and negative FPL returns remain comparable evidence',
    (
        $zeroNegativeMetrics[
            'comparable_players'
        ]
        ?? null
    )
    === 2
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * MISSING REALISED RETURN
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Missing Realised Return<br>";
echo "============================================<br>";


$missingActualRows = [

    [
        'player_id' => 601,
        'position' => 'DEF',
        'base_next_fixture_rating' => 80.0,
        'next_opponent_attack_rating' => 40.0,
        'next_opponent_defence_rating' => 50.0,
        'actual_points' => null
    ]
];


$missingActualResult =
    $service->evaluate(
        $missingActualRows,
        [
            [
                'base_fixture_weight' => 0.75,
                'position_performance_weight' => 0.25
            ]
        ]
    );


$missingActualScore =
    $missingActualResult[
        'evaluations'
    ][0][
        'player_scores'
    ][0]
    ?? [];


positionAwareFixtureCalibrationCheck(
    'Candidate fixture score is still calculated when realised points are unavailable',
    positionAwareFixtureCalibrationApproximatelyEqual(
        $missingActualScore[
            'candidate_fixture_rating'
        ]
        ?? null,
        75.0
    )
);


positionAwareFixtureCalibrationCheck(
    'Missing realised points remain unavailable to correlation metrics',
    (
        $missingActualResult[
            'evaluations'
        ][0][
            'metrics'
        ][
            'comparable_players'
        ]
        ?? null
    )
    === 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * MALFORMED EVIDENCE / SOURCE IMMUTABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Malformed Evidence and Immutability<br>";
echo "============================================<br>";


$sourceRows = $historicalRows;

$malformedRows = $historicalRows;

$malformedRows[] =
    'not-an-array';


$malformedResult =
    $service->evaluate(
        $malformedRows,
        [
            [
                'base_fixture_weight' => 0.75,
                'position_performance_weight' => 0.25
            ]
        ]
    );


positionAwareFixtureCalibrationCheck(
    'Malformed non-array historical evidence is ignored',
    count(
        $malformedResult[
            'evaluations'
        ][0][
            'player_scores'
        ]
        ?? []
    )
    === 4
);


$service->evaluate(
    $historicalRows,
    $weightCandidates
);


positionAwareFixtureCalibrationCheck(
    'Calibration does not mutate the supplied historical evidence',
    $historicalRows === $sourceRows
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


$invalidCandidates = [

    'not-an-array',

    [
        'position_performance_weight' => 0.25
    ],

    [
        'base_fixture_weight' => 0.75
    ],

    [
        'base_fixture_weight' => 'invalid',
        'position_performance_weight' => 0.25
    ],

    [
        'base_fixture_weight' => 0.75,
        'position_performance_weight' => 'invalid'
    ],

    [
        'base_fixture_weight' => -0.10,
        'position_performance_weight' => 1.10
    ],

    [
        'base_fixture_weight' => 1.10,
        'position_performance_weight' => -0.10
    ],

    [
        'base_fixture_weight' => 0.60,
        'position_performance_weight' => 0.30
    ]
];


foreach (
    $invalidCandidates
    as $index => $invalidCandidate
) {

    $threw =
        false;


    try {

        $service->evaluate(
            $historicalRows,
            [
                $invalidCandidate
            ]
        );

    } catch (
        InvalidArgumentException $exception
    ) {

        $threw =
            true;
    }


    positionAwareFixtureCalibrationCheck(
        'Invalid weight candidate '
            . ($index + 1)
            . ' is rejected',
        $threw
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * EMPTY WEIGHT CANDIDATE SET
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Empty Weight Candidate Set<br>";
echo "============================================<br>";


$noCandidates =
    $service->evaluate(
        $historicalRows,
        []
    );


positionAwareFixtureCalibrationCheck(
    'Empty candidate set returns an empty evaluations collection',
    $noCandidates
    === [
        'evaluations' => []
    ]
);


echo "<br>";


positionAwareFixtureCalibrationSummary();