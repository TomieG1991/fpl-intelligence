<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Triple Captain Intelligence Test<br>";
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

function tripleCaptainCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


function tripleCaptainHeading(
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
 * TEST DATA BUILDER
 * ============================================================
 *
 * Triple Captain Intelligence receives an already-prepared
 * captain opportunity.
 *
 * It must not:
 *
 * - calculate Expected Points
 * - calculate Captain Intelligence
 * - invent a Double Gameweek bonus
 *
 * Those values are produced by existing upstream systems.
 */

function buildTripleCaptainOpportunity(
    float $projectedPoints = 10.0,
    float $projectionConfidence = 0.80,
    float $captainScore = 65.0,
    float $captainConfidence = 0.75,
    int $fixtureCount = 1,
    string $scheduleType = 'Normal'
): array {

    return [

        'player_id' =>
            101,

        'name' =>
            'Test Captain',

        'position' =>
            'FWD',

        /*
         * Existing Expected Points / Squad Horizon output.
         */
        'projected_points' =>
            $projectedPoints,

        'projection_confidence' =>
            $projectionConfidence,

        /*
         * Existing CaptainIntelligence output.
         */
        'captain_score' =>
            $captainScore,

        /*
         * Normalised 0.0–1.0 confidence supplied by the
         * orchestration layer from Captain Intelligence evidence.
         */
        'captain_confidence' =>
            $captainConfidence,

        /*
         * Schedule metadata is diagnostic only.
         *
         * Double Gameweek value must already be represented in
         * projected_points.
         */
        'fixture_count' =>
            $fixtureCount,

        'schedule_type' =>
            $scheduleType
    ];
}


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario A: Class Contract'
);


$classExists =
    class_exists(
        'TripleCaptainIntelligence'
    );


tripleCaptainCheck(
    'TripleCaptainIntelligence class exists',
    $classExists
);


/*
 * Stop cleanly during the first RED stage.
 *
 * The complete future contract is already written below, but
 * PHP must not instantiate a class that does not yet exist.
 */
if (
    !$classExists
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Triple Captain Intelligence Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br><br>";

    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$analyseMethodExists =
    method_exists(
        'TripleCaptainIntelligence',
        'analyse'
    );


$createDecisionMethodExists =
    method_exists(
        'TripleCaptainIntelligence',
        'createDecision'
    );


tripleCaptainCheck(
    'TripleCaptainIntelligence exposes analyse()',
    $analyseMethodExists
);


tripleCaptainCheck(
    'TripleCaptainIntelligence exposes createDecision()',
    $createDecisionMethodExists
);


if (
    !$analyseMethodExists
    ||
    !$createDecisionMethodExists
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Triple Captain Intelligence Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br><br>";

    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$model =
    new TripleCaptainIntelligence();


/*
 * ============================================================
 * SCENARIO B
 * CAPTAIN IDENTITY
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario B: Captain Identity'
);


$opportunity =
    buildTripleCaptainOpportunity();


$analysis =
    $model->analyse(
        $opportunity
    );


tripleCaptainCheck(
    'Analysis returns an array',
    is_array(
        $analysis
    )
);


tripleCaptainCheck(
    'Captain player ID is preserved',
    isset(
        $analysis[
            'player_id'
        ]
    )
    &&
    (int) $analysis[
        'player_id'
    ]
    ===
    101
);


tripleCaptainCheck(
    'Captain name is preserved',
    isset(
        $analysis[
            'name'
        ]
    )
    &&
    $analysis[
        'name'
    ]
    ===
    'Test Captain'
);


/*
 * ============================================================
 * SCENARIO C
 * PROJECTED CAPTAIN POINTS
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario C: Projected Captain Points'
);


$opportunity =
    buildTripleCaptainOpportunity(
        11.25
    );


$analysis =
    $model->analyse(
        $opportunity
    );


tripleCaptainCheck(
    'Projected captain points preserve existing Expected Points',
    isset(
        $analysis[
            'projected_captain_points'
        ]
    )
    &&
    is_numeric(
        $analysis[
            'projected_captain_points'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'projected_captain_points'
            ]
        )
        -
        11.25
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO D
 * CAPTAIN INTELLIGENCE SCORE
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario D: Captain Intelligence Score'
);


$opportunity =
    buildTripleCaptainOpportunity(
        10.0,
        0.80,
        67.5
    );


$analysis =
    $model->analyse(
        $opportunity
    );


tripleCaptainCheck(
    'Captain score preserves existing Captain Intelligence result',
    isset(
        $analysis[
            'captain_score'
        ]
    )
    &&
    is_numeric(
        $analysis[
            'captain_score'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'captain_score'
            ]
        )
        -
        67.5
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO E
 * PROJECTION CONFIDENCE
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario E: Projection Confidence'
);


$opportunity =
    buildTripleCaptainOpportunity(
        10.0,
        0.72
    );


$analysis =
    $model->analyse(
        $opportunity
    );


tripleCaptainCheck(
    'Projection confidence is preserved',
    isset(
        $analysis[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $analysis[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'projection_confidence'
            ]
        )
        -
        0.72
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO F
 * NORMAL GAMEWEEK METADATA
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario F: Normal Gameweek'
);


$opportunity =
    buildTripleCaptainOpportunity(
        10.0,
        0.80,
        65.0,
        0.75,
        1,
        'Normal'
    );


$analysis =
    $model->analyse(
        $opportunity
    );


tripleCaptainCheck(
    'Normal Gameweek fixture count is preserved',
    isset(
        $analysis[
            'fixture_count'
        ]
    )
    &&
    (int) $analysis[
        'fixture_count'
    ]
    ===
    1
);


tripleCaptainCheck(
    'Normal Gameweek schedule type is preserved',
    isset(
        $analysis[
            'schedule_type'
        ]
    )
    &&
    $analysis[
        'schedule_type'
    ]
    ===
    'Normal'
);


/*
 * ============================================================
 * SCENARIO G
 * DOUBLE GAMEWEEK METADATA
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario G: Double Gameweek'
);


/*
 * The 14.0 projected points already contain the value of both
 * fixtures.
 *
 * Triple Captain Intelligence must preserve 14.0 exactly.
 * It must NOT multiply this because fixture_count is two.
 */
$opportunity =
    buildTripleCaptainOpportunity(
        14.0,
        0.80,
        68.0,
        0.75,
        2,
        'Double'
    );


$analysis =
    $model->analyse(
        $opportunity
    );


tripleCaptainCheck(
    'Double Gameweek fixture count is preserved',
    isset(
        $analysis[
            'fixture_count'
        ]
    )
    &&
    (int) $analysis[
        'fixture_count'
    ]
    ===
    2
);


tripleCaptainCheck(
    'Double Gameweek schedule type is preserved',
    isset(
        $analysis[
            'schedule_type'
        ]
    )
    &&
    $analysis[
        'schedule_type'
    ]
    ===
    'Double'
);


tripleCaptainCheck(
    'Double Gameweek projected points are not artificially multiplied',
    isset(
        $analysis[
            'projected_captain_points'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'projected_captain_points'
            ]
        )
        -
        14.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO H
 * HOLD DECISION
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario H: Hold Decision'
);


/*
 * Eight projected points is not exceptional enough for a
 * Triple Captain recommendation.
 *
 * Thresholds introduced here are provisional v0.34 decision
 * policy, not empirical FPL truths.
 */
$opportunity =
    buildTripleCaptainOpportunity(
        8.0,
        0.80,
        65.0,
        0.75
    );


$analysis =
    $model->analyse(
        $opportunity
    );


$decision =
    $model->createDecision(
        $analysis
    );


tripleCaptainCheck(
    'Eight projected captain points recommends Hold',
    $decision instanceof ChipDecision
    &&
    $decision->getChip()
    ===
    'Triple Captain'
    &&
    $decision->getRecommendation()
    ===
    'Hold'
);


/*
 * ============================================================
 * SCENARIO I
 * CONSIDER DECISION
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario I: Consider Decision'
);


$opportunity =
    buildTripleCaptainOpportunity(
        10.0,
        0.80,
        62.0,
        0.75
    );


$analysis =
    $model->analyse(
        $opportunity
    );


$decision =
    $model->createDecision(
        $analysis
    );


tripleCaptainCheck(
    'Strong but non-exceptional captain opportunity recommends Consider',
    $decision instanceof ChipDecision
    &&
    $decision->getRecommendation()
    ===
    'Consider'
);


/*
 * ============================================================
 * SCENARIO J
 * USE DECISION
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario J: Use Decision'
);


/*
 * The initial v0.34 Use policy requires both:
 *
 * - exceptional projected points
 * - Elite Captain-level Captain Intelligence
 *
 * CaptainIntelligence currently classifies 65+ as Elite.
 */
$opportunity =
    buildTripleCaptainOpportunity(
        12.0,
        0.85,
        65.0,
        0.80
    );


$analysis =
    $model->analyse(
        $opportunity
    );


$decision =
    $model->createDecision(
        $analysis
    );


tripleCaptainCheck(
    'Exceptional projected points with elite captain quality recommends Use',
    $decision instanceof ChipDecision
    &&
    $decision->getRecommendation()
    ===
    'Use'
);


/*
 * ============================================================
 * SCENARIO K
 * CAPTAIN QUALITY GUARD
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario K: Captain Quality Guard'
);


/*
 * High Expected Points alone should not create an automatic
 * Use recommendation if Captain Intelligence does not classify
 * the player as an elite captain opportunity.
 */
$opportunity =
    buildTripleCaptainOpportunity(
        12.0,
        0.85,
        60.0,
        0.80
    );


$analysis =
    $model->analyse(
        $opportunity
    );


$decision =
    $model->createDecision(
        $analysis
    );


tripleCaptainCheck(
    'High projected points without elite captain score does not automatically recommend Use',
    $decision instanceof ChipDecision
    &&
    $decision->getRecommendation()
    ===
    'Consider'
);


/*
 * ============================================================
 * SCENARIO L
 * DECISION CONFIDENCE
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario L: Decision Confidence'
);


$opportunity =
    buildTripleCaptainOpportunity(
        12.0,
        0.82,
        67.0,
        0.71
    );


$analysis =
    $model->analyse(
        $opportunity
    );


$decision =
    $model->createDecision(
        $analysis
    );


tripleCaptainCheck(
    'Decision confidence uses the weaker projection and captain evidence',
    abs(
        $decision->getConfidence()
        -
        0.71
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO M
 * CHIP DECISION CONTRACT
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario M: Chip Decision Contract'
);


tripleCaptainCheck(
    'Triple Captain decision is a ChipDecision',
    $decision instanceof ChipDecision
);


tripleCaptainCheck(
    'Decision identifies Triple Captain chip',
    $decision instanceof ChipDecision
    &&
    $decision->getChip()
    ===
    'Triple Captain'
);


tripleCaptainCheck(
    'Recommendation uses supported chip decision state',
    $decision instanceof ChipDecision
    &&
    in_array(
        $decision->getRecommendation(),
        [
            'Use',
            'Consider',
            'Hold'
        ],
        true
    )
);


/*
 * ============================================================
 * SCENARIO N
 * DECISION EXPLANATION
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario N: Decision Explanation'
);


$decisionArray =
    $decision->toArray();


tripleCaptainCheck(
    'Triple Captain decision exposes non-empty explanation',
    isset(
        $decisionArray[
            'explanation'
        ]
    )
    &&
    is_string(
        $decisionArray[
            'explanation'
        ]
    )
    &&
    trim(
        $decisionArray[
            'explanation'
        ]
    )
    !==
    ''
);


/*
 * ============================================================
 * SCENARIO O
 * MISSING PROJECTION CONFIDENCE
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario O: Missing Projection Confidence'
);


$opportunity =
    buildTripleCaptainOpportunity();


$opportunity[
    'projection_confidence'
] =
    null;


$analysis =
    $model->analyse(
        $opportunity
    );


$decision =
    $model->createDecision(
        $analysis
    );


tripleCaptainCheck(
    'Missing projection confidence produces zero decision confidence',
    $decision instanceof ChipDecision
    &&
    abs(
        $decision->getConfidence()
        -
        0.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO P
 * MISSING CAPTAIN CONFIDENCE
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario P: Missing Captain Confidence'
);


$opportunity =
    buildTripleCaptainOpportunity();


$opportunity[
    'captain_confidence'
] =
    null;


$analysis =
    $model->analyse(
        $opportunity
    );


$decision =
    $model->createDecision(
        $analysis
    );


tripleCaptainCheck(
    'Missing Captain Intelligence confidence produces zero decision confidence',
    $decision instanceof ChipDecision
    &&
    abs(
        $decision->getConfidence()
        -
        0.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO Q
 * ZERO PROJECTION CONFIDENCE
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario Q: Zero Projection Confidence'
);


$opportunity =
    buildTripleCaptainOpportunity(
        12.0,
        0.0,
        68.0,
        0.80
    );


$analysis =
    $model->analyse(
        $opportunity
    );


$decision =
    $model->createDecision(
        $analysis
    );


tripleCaptainCheck(
    'Zero projection confidence remains real evidence',
    $decision instanceof ChipDecision
    &&
    abs(
        $decision->getConfidence()
        -
        0.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO R
 * NEGATIVE PROJECTED POINTS
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario R: Negative Projected Points'
);


$negativeProjectionRejected =
    false;


try {

    $model->analyse(
        buildTripleCaptainOpportunity(
            -1.0
        )
    );

} catch (
    InvalidArgumentException $exception
) {

    $negativeProjectionRejected =
        true;
}


tripleCaptainCheck(
    'Negative projected captain points are rejected',
    $negativeProjectionRejected
);


/*
 * ============================================================
 * SCENARIO S
 * INVALID PROJECTION CONFIDENCE
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario S: Invalid Projection Confidence'
);


$invalidProjectionConfidenceRejected =
    false;


try {

    $model->analyse(
        buildTripleCaptainOpportunity(
            10.0,
            1.20
        )
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidProjectionConfidenceRejected =
        true;
}


tripleCaptainCheck(
    'Projection confidence above one is rejected',
    $invalidProjectionConfidenceRejected
);


/*
 * ============================================================
 * SCENARIO T
 * INVALID CAPTAIN CONFIDENCE
 * ============================================================
 */

tripleCaptainHeading(
    'Scenario T: Invalid Captain Confidence'
);


$invalidCaptainConfidenceRejected =
    false;


try {

    $model->analyse(
        buildTripleCaptainOpportunity(
            10.0,
            0.80,
            65.0,
            -0.10
        )
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidCaptainConfidenceRejected =
        true;
}


tripleCaptainCheck(
    'Negative Captain Intelligence confidence is rejected',
    $invalidCaptainConfidenceRejected
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Triple Captain Intelligence Test Summary<br>";
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

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}