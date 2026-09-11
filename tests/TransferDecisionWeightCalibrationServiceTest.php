<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Decision Weight Calibration Service Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function transferDecisionWeightCalibrationCheck(
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


function transferDecisionWeightCalibrationSection(
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


function transferDecisionWeightCalibrationSummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Transfer Decision Weight Calibration Service Test Summary<br>";
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

transferDecisionWeightCalibrationSection(
    'Scenario A: Class Contract'
);


transferDecisionWeightCalibrationCheck(
    'TransferDecisionWeightCalibrationService class exists',
    class_exists(
        'TransferDecisionWeightCalibrationService'
    )
);


if (
    class_exists(
        'TransferDecisionWeightCalibrationService'
    )
) {

    $reflection =
        new ReflectionClass(
            'TransferDecisionWeightCalibrationService'
        );


    transferDecisionWeightCalibrationCheck(
        'TransferDecisionWeightCalibrationService exposes evaluate()',
        $reflection->hasMethod(
            'evaluate'
        )
    );

} else {

    transferDecisionWeightCalibrationCheck(
        'TransferDecisionWeightCalibrationService exposes evaluate()',
        false
    );
}


/*
 * ============================================================
 * STOP UNTIL PRODUCTION CLASS EXISTS
 * ============================================================
 */

if (
    !class_exists(
        'TransferDecisionWeightCalibrationService'
    )
) {

    transferDecisionWeightCalibrationSummary();

    exit;
}


$service =
    new TransferDecisionWeightCalibrationService();


/*
 * ============================================================
 * CONTROLLED HISTORICAL TRANSFER EVIDENCE
 * ============================================================
 *
 * Each row already represents one recommendation-time legal
 * replacement universe for one fixed outgoing player.
 *
 * The calibration service must not manufacture extra candidates.
 */

$historicalTransfers = [

    /*
     * --------------------------------------------------------
     * TRANSFER 1
     *
     * Tests:
     * - fixed movement scales
     * - classification ordering before raw score
     * - realised selection loss
     * --------------------------------------------------------
     */
    [
        'gameweek_id' =>
            5,

        'bank' =>
            0.0,

        'outgoing' => [

            'player_id' =>
                101,

            'name' =>
                'Outgoing One',

            'position' =>
                'MID',

            'price' =>
                7.0,

            'intelligence_score' =>
                60.0,

            'strength_rating' =>
                60.0,

            'value_rating' =>
                60.0,

            'fixture_rating' =>
                50.0,

            'sample_confidence' =>
                0.50,

            'actual_points' =>
                2
        ],

        'replacements' => [

            /*
             * Every movement reaches the maximum contribution.
             *
             * Intelligence +10 => 100
             * Fixture      +20 => 100
             * Value        +30 => 100
             * Strength     +15 => 100
             * Budget        +5 => 100
             * Confidence   +50 => 100
             *
             * Decision score = 100.
             * Intelligence +10 => Upgrade.
             */
            [
                'player_id' =>
                    201,

                'name' =>
                    'Maximum Upgrade',

                'position' =>
                    'MID',

                'price' =>
                    2.0,

                'intelligence_score' =>
                    70.0,

                'strength_rating' =>
                    75.0,

                'value_rating' =>
                    90.0,

                'fixture_rating' =>
                    70.0,

                'sample_confidence' =>
                    1.00,

                'actual_points' =>
                    7
            ],

            /*
             * Small Intelligence improvement only.
             *
             * Intelligence +1 => 55
             * Everything else neutral => 50.
             *
             * Production score:
             * 55*.40 + 50*.60 = 52.
             *
             * Not enough Intelligence movement for Upgrade.
             * Score 52 => Sidegrade.
             */
            [
                'player_id' =>
                    202,

                'name' =>
                    'Ordinary Sidegrade',

                'position' =>
                    'MID',

                'price' =>
                    7.0,

                'intelligence_score' =>
                    61.0,

                'strength_rating' =>
                    60.0,

                'value_rating' =>
                    60.0,

                'fixture_rating' =>
                    50.0,

                'sample_confidence' =>
                    0.50,

                'actual_points' =>
                    5
            ],

            /*
             * Strong component score but Intelligence falls by 3.
             *
             * Raw production score = 74.
             *
             * TransferDecision classifies this as Downgrade before
             * strategic score classifications are considered.
             *
             * Therefore the Sidegrade above must outrank this
             * candidate despite 52 < 74.
             */
            [
                'player_id' =>
                    203,

                'name' =>
                    'High Score Downgrade',

                'position' =>
                    'MID',

                'price' =>
                    2.0,

                'intelligence_score' =>
                    57.0,

                'strength_rating' =>
                    75.0,

                'value_rating' =>
                    90.0,

                'fixture_rating' =>
                    90.0,

                'sample_confidence' =>
                    1.00,

                'actual_points' =>
                    12
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * TRANSFER 2
     *
     * Tests that changing only top-level weights can change the
     * selected replacement while every preserved input remains
     * identical.
     * --------------------------------------------------------
     */
    [
        'gameweek_id' =>
            6,

        'bank' =>
            0.0,

        'outgoing' => [

            'player_id' =>
                102,

            'name' =>
                'Outgoing Two',

            'position' =>
                'DEF',

            'price' =>
                7.0,

            'intelligence_score' =>
                60.0,

            'strength_rating' =>
                60.0,

            'value_rating' =>
                60.0,

            'fixture_rating' =>
                50.0,

            'sample_confidence' =>
                0.50,

            'actual_points' =>
                2
        ],

        'replacements' => [

            /*
             * Fixture-led candidate.
             *
             * Production:
             * Intelligence 55
             * Fixture      100
             * Others        50
             *
             * Score = 62.
             */
            [
                'player_id' =>
                    204,

                'name' =>
                    'Fixture Specialist',

                'position' =>
                    'DEF',

                'price' =>
                    7.0,

                'intelligence_score' =>
                    61.0,

                'strength_rating' =>
                    60.0,

                'value_rating' =>
                    60.0,

                'fixture_rating' =>
                    70.0,

                'sample_confidence' =>
                    0.50,

                'actual_points' =>
                    10
            ],

            /*
             * Broad all-round candidate.
             *
             * Production component scores:
             * Intelligence 55
             * Fixture       50
             * Value        100
             * Strength     100
             * Budget        50
             * Confidence   100
             *
             * Production score = 67.
             *
             * With production weights both candidates are Strategic
             * Sidegrades, so the higher raw score selects this player.
             *
             * With Fixture-heavy weights this player falls to Sidegrade
             * while the Fixture specialist remains Strategic Sidegrade.
             */
            [
                'player_id' =>
                    205,

                'name' =>
                    'All Round Candidate',

                'position' =>
                    'DEF',

                'price' =>
                    7.0,

                'intelligence_score' =>
                    61.0,

                'strength_rating' =>
                    75.0,

                'value_rating' =>
                    90.0,

                'fixture_rating' =>
                    50.0,

                'sample_confidence' =>
                    1.00,

                'actual_points' =>
                    4
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * TRANSFER 3
     *
     * Value evidence is unavailable for both players.
     *
     * Production must omit Value and renormalise the remaining
     * weights exactly as TransferDecision currently does.
     * --------------------------------------------------------
     */
    [
        'gameweek_id' =>
            7,

        'bank' =>
            0.0,

        'outgoing' => [

            'player_id' =>
                103,

            'name' =>
                'Outgoing Three',

            'position' =>
                'FWD',

            'price' =>
                7.0,

            'intelligence_score' =>
                60.0,

            'strength_rating' =>
                60.0,

            'value_rating' =>
                null,

            'fixture_rating' =>
                50.0,

            'sample_confidence' =>
                0.50,

            /*
             * Negative realised return is valid historical evidence.
             */
            'actual_points' =>
                -1
        ],

        'replacements' => [

            [
                'player_id' =>
                    206,

                'name' =>
                    'Missing Value Candidate',

                'position' =>
                    'FWD',

                'price' =>
                    7.0,

                'intelligence_score' =>
                    61.0,

                'strength_rating' =>
                    60.0,

                'value_rating' =>
                    null,

                'fixture_rating' =>
                    70.0,

                'sample_confidence' =>
                    0.50,

                /*
                 * Zero realised points is valid evidence.
                 */
                'actual_points' =>
                    0
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * TRANSFER 4
     *
     * Recommendation evidence exists but realised replacement
     * outcome is unavailable.
     *
     * This must remain an unavailable historical comparison.
     * --------------------------------------------------------
     */
    [
        'gameweek_id' =>
            8,

        'bank' =>
            0.0,

        'outgoing' => [

            'player_id' =>
                104,

            'name' =>
                'Outgoing Four',

            'position' =>
                'GK',

            'price' =>
                5.0,

            'intelligence_score' =>
                60.0,

            'strength_rating' =>
                60.0,

            'value_rating' =>
                60.0,

            'fixture_rating' =>
                50.0,

            'sample_confidence' =>
                0.50,

            'actual_points' =>
                3
        ],

        'replacements' => [

            [
                'player_id' =>
                    207,

                'name' =>
                    'Outcome Unavailable',

                'position' =>
                    'GK',

                'price' =>
                    5.0,

                'intelligence_score' =>
                    61.0,

                'strength_rating' =>
                    60.0,

                'value_rating' =>
                    60.0,

                'fixture_rating' =>
                    60.0,

                'sample_confidence' =>
                    0.50,

                'actual_points' =>
                    null
            ]
        ]
    ],

    /*
     * Malformed historical rows must be ignored entirely.
     */
    'not-an-array'
];


/*
 * ============================================================
 * WEIGHT CANDIDATES
 * ============================================================
 */

$productionWeights = [

    'intelligence_weight' =>
        0.40,

    'fixture_weight' =>
        0.20,

    'value_weight' =>
        0.15,

    'strength_weight' =>
        0.10,

    'budget_weight' =>
        0.10,

    'confidence_weight' =>
        0.05
];


$fixtureHeavyWeights = [

    'intelligence_weight' =>
        0.10,

    'fixture_weight' =>
        0.70,

    'value_weight' =>
        0.05,

    'strength_weight' =>
        0.05,

    'budget_weight' =>
        0.05,

    'confidence_weight' =>
        0.05
];


$weightCandidates = [

    $productionWeights,
    $fixtureHeavyWeights
];


/*
 * ============================================================
 * SCENARIO B
 * RESULT CONTRACT
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario B: Result Contract'
);


$result =
    $service->evaluate(
        $historicalTransfers,
        $weightCandidates
    );


transferDecisionWeightCalibrationCheck(
    'Evaluation result contains evaluations',
    is_array(
        $result[
            'evaluations'
        ]
        ?? null
    )
);


$evaluations =
    $result[
        'evaluations'
    ]
    ?? [];


transferDecisionWeightCalibrationCheck(
    'Every explicitly supplied weight candidate is evaluated',
    count(
        $evaluations
    )
    === 2
);


$productionEvaluation =
    $evaluations[
        0
    ]
    ?? [];


$fixtureHeavyEvaluation =
    $evaluations[
        1
    ]
    ?? [];


/*
 * ============================================================
 * SCENARIO C
 * WEIGHT EVIDENCE
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario C: Weight Evidence'
);


transferDecisionWeightCalibrationCheck(
    'Production Intelligence weight is preserved',
    ($productionEvaluation['intelligence_weight'] ?? null) === 0.40
);


transferDecisionWeightCalibrationCheck(
    'Production Fixture weight is preserved',
    ($productionEvaluation['fixture_weight'] ?? null) === 0.20
);


transferDecisionWeightCalibrationCheck(
    'Production Value weight is preserved',
    ($productionEvaluation['value_weight'] ?? null) === 0.15
);


transferDecisionWeightCalibrationCheck(
    'Production Strength weight is preserved',
    ($productionEvaluation['strength_weight'] ?? null) === 0.10
);


transferDecisionWeightCalibrationCheck(
    'Production Budget weight is preserved',
    ($productionEvaluation['budget_weight'] ?? null) === 0.10
);


transferDecisionWeightCalibrationCheck(
    'Production Confidence weight is preserved',
    ($productionEvaluation['confidence_weight'] ?? null) === 0.05
);


/*
 * ============================================================
 * SCENARIO D
 * HISTORICAL TRANSFER MEMBERSHIP
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario D: Historical Transfer Membership'
);


$productionTransfers =
    $productionEvaluation[
        'transfers'
    ]
    ?? [];


transferDecisionWeightCalibrationCheck(
    'Malformed historical transfer rows are ignored',
    count(
        $productionTransfers
    )
    === 4
);


transferDecisionWeightCalibrationCheck(
    'Historical gameweek identity is preserved',
    array_column(
        $productionTransfers,
        'gameweek_id'
    )
    === [
        5,
        6,
        7,
        8
    ]
);


/*
 * ============================================================
 * SCENARIO E
 * MOVEMENT SCALE REPLAY
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario E: Movement Scale Replay'
);


$transferOne =
    $productionTransfers[
        0
    ]
    ?? [];


$transferOneScores =
    $transferOne[
        'replacement_scores'
    ]
    ?? [];


$replacement201 =
    $transferOneScores[
        0
    ]
    ?? [];


transferDecisionWeightCalibrationCheck(
    'Maximum component movements reproduce decision score 100',
    ($replacement201['decision_score'] ?? null) === 100.0
);


transferDecisionWeightCalibrationCheck(
    'Maximum Intelligence improvement remains classified as Upgrade',
    ($replacement201['decision_type'] ?? null) === 'Upgrade'
);


transferDecisionWeightCalibrationCheck(
    'Intelligence movement is preserved for audit',
    (
        $replacement201[
            'movements'
        ]['intelligence']
        ?? null
    )
    === 10.0
);


transferDecisionWeightCalibrationCheck(
    'Fixture movement is preserved for audit',
    (
        $replacement201[
            'movements'
        ]['fixtures']
        ?? null
    )
    === 20.0
);


transferDecisionWeightCalibrationCheck(
    'Value movement is preserved for audit',
    (
        $replacement201[
            'movements'
        ]['value']
        ?? null
    )
    === 30.0
);


transferDecisionWeightCalibrationCheck(
    'Strength movement is preserved for audit',
    (
        $replacement201[
            'movements'
        ]['strength']
        ?? null
    )
    === 15.0
);


transferDecisionWeightCalibrationCheck(
    'Budget movement is preserved for audit',
    (
        $replacement201[
            'movements'
        ]['budget']
        ?? null
    )
    === 5.0
);


transferDecisionWeightCalibrationCheck(
    'Confidence movement is preserved for audit',
    (
        $replacement201[
            'movements'
        ]['sample_confidence']
        ?? null
    )
    === 50.0
);


/*
 * ============================================================
 * SCENARIO F
 * CLASSIFICATION BEFORE RAW SCORE
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario F: Classification Ordering'
);


$replacement202 =
    $transferOneScores[
        1
    ]
    ?? [];


$replacement203 =
    $transferOneScores[
        2
    ]
    ?? [];


transferDecisionWeightCalibrationCheck(
    'Ordinary Sidegrade reproduces production score 52',
    ($replacement202['decision_score'] ?? null) === 52.0
);


transferDecisionWeightCalibrationCheck(
    'Ordinary candidate is classified as Sidegrade',
    ($replacement202['decision_type'] ?? null) === 'Sidegrade'
);


transferDecisionWeightCalibrationCheck(
    'High-score Intelligence loss reproduces raw score 74',
    ($replacement203['decision_score'] ?? null) === 74.0
);


transferDecisionWeightCalibrationCheck(
    'Material Intelligence loss is classified as Downgrade',
    ($replacement203['decision_type'] ?? null) === 'Downgrade'
);


transferDecisionWeightCalibrationCheck(
    'Replacement ranking prefers classification before raw score',
    array_column(
        $transferOneScores,
        'player_id'
    )
    === [
        201,
        202,
        203
    ]
);


transferDecisionWeightCalibrationCheck(
    'Production candidate selects the Upgrade',
    ($transferOne['selected_player_id'] ?? null) === 201
);


/*
 * ============================================================
 * SCENARIO G
 * WEIGHT-DEPENDENT REPLACEMENT SELECTION
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario G: Weight-Dependent Selection'
);


$productionTransferTwo =
    $productionTransfers[
        1
    ]
    ?? [];


$fixtureHeavyTransfers =
    $fixtureHeavyEvaluation[
        'transfers'
    ]
    ?? [];


$fixtureHeavyTransferTwo =
    $fixtureHeavyTransfers[
        1
    ]
    ?? [];


transferDecisionWeightCalibrationCheck(
    'Production weights select the broader all-round candidate',
    ($productionTransferTwo['selected_player_id'] ?? null) === 205
);


transferDecisionWeightCalibrationCheck(
    'Production-selected all-round candidate scores 67',
    ($productionTransferTwo['selected_decision_score'] ?? null) === 67.0
);


transferDecisionWeightCalibrationCheck(
    'Fixture-heavy weights select the Fixture specialist',
    ($fixtureHeavyTransferTwo['selected_player_id'] ?? null) === 204
);


transferDecisionWeightCalibrationCheck(
    'Fixture-heavy specialist score is 85.5',
    ($fixtureHeavyTransferTwo['selected_decision_score'] ?? null) === 85.5
);


/*
 * ============================================================
 * SCENARIO H
 * MISSING COMPONENT RENORMALISATION
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario H: Missing Component Renormalisation'
);


$productionTransferThree =
    $productionTransfers[
        2
    ]
    ?? [];


$transferThreeScores =
    $productionTransferThree[
        'replacement_scores'
    ]
    ?? [];


$replacement206 =
    $transferThreeScores[
        0
    ]
    ?? [];


/*
 * Value is unavailable.
 *
 * Remaining production weight total:
 *
 * 0.40 + 0.20 + 0.10 + 0.10 + 0.05 = 0.85
 *
 * Weighted numerator:
 *
 * Intelligence 55 * .40 = 22.0
 * Fixture     100 * .20 = 20.0
 * Strength     50 * .10 =  5.0
 * Budget       50 * .10 =  5.0
 * Confidence   50 * .05 =  2.5
 *
 * Total = 54.5
 *
 * 54.5 / .85 = 64.117647...
 * Rounded = 64.12
 */

transferDecisionWeightCalibrationCheck(
    'Missing Value evidence is omitted and remaining weights renormalise',
    ($replacement206['decision_score'] ?? null) === 64.12
);


transferDecisionWeightCalibrationCheck(
    'Missing Value movement remains explicitly null',
    array_key_exists(
        'value',
        $replacement206[
            'movements'
        ]
        ?? []
    )
    &&
    $replacement206[
        'movements'
    ]['value'] === null
);


/*
 * ============================================================
 * SCENARIO I
 * REALISED TRANSFER EVIDENCE
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario I: Realised Transfer Evidence'
);


transferDecisionWeightCalibrationCheck(
    'Selected realised points are preserved',
    ($productionTransferTwo['selected_actual_points'] ?? null) === 4
);


transferDecisionWeightCalibrationCheck(
    'Outgoing realised points are preserved',
    ($productionTransferTwo['outgoing_actual_points'] ?? null) === 2
);


transferDecisionWeightCalibrationCheck(
    'Selected realised transfer gain is calculated',
    ($productionTransferTwo['selected_realised_gain'] ?? null) === 2
);


transferDecisionWeightCalibrationCheck(
    'Best realised replacement is identified from the same preserved universe',
    ($productionTransferTwo['best_actual_player_id'] ?? null) === 204
);


transferDecisionWeightCalibrationCheck(
    'Best realised replacement points are preserved',
    ($productionTransferTwo['best_actual_points'] ?? null) === 10
);


transferDecisionWeightCalibrationCheck(
    'Best realised transfer gain is calculated',
    ($productionTransferTwo['best_realised_gain'] ?? null) === 8
);


transferDecisionWeightCalibrationCheck(
    'Production selection loses six realised points in Transfer 2',
    ($productionTransferTwo['selection_points_lost'] ?? null) === 6
);


transferDecisionWeightCalibrationCheck(
    'Fixture-heavy selection loses zero realised points in Transfer 2',
    ($fixtureHeavyTransferTwo['selection_points_lost'] ?? null) === 0
);


/*
 * ============================================================
 * SCENARIO J
 * ZERO AND NEGATIVE REALISED POINTS
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario J: Zero / Negative Realised Points'
);


transferDecisionWeightCalibrationCheck(
    'Negative outgoing realised points remain valid',
    ($productionTransferThree['outgoing_actual_points'] ?? null) === -1
);


transferDecisionWeightCalibrationCheck(
    'Zero replacement realised points remain valid',
    array_key_exists(
        'selected_actual_points',
        $productionTransferThree
    )
    &&
    $productionTransferThree[
        'selected_actual_points'
    ] === 0
);


transferDecisionWeightCalibrationCheck(
    'Zero-point replacement still records a positive realised gain over minus one',
    ($productionTransferThree['selected_realised_gain'] ?? null) === 1
);


transferDecisionWeightCalibrationCheck(
    'Single comparable replacement has zero selection loss',
    ($productionTransferThree['selection_points_lost'] ?? null) === 0
);


/*
 * ============================================================
 * SCENARIO K
 * UNAVAILABLE REALISED OUTCOME
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario K: Unavailable Realised Outcome'
);


$productionTransferFour =
    $productionTransfers[
        3
    ]
    ?? [];


transferDecisionWeightCalibrationCheck(
    'Missing selected realised outcome remains null',
    array_key_exists(
        'selected_actual_points',
        $productionTransferFour
    )
    &&
    $productionTransferFour[
        'selected_actual_points'
    ] === null
);


transferDecisionWeightCalibrationCheck(
    'Unavailable realised comparison has null selection loss',
    array_key_exists(
        'selection_points_lost',
        $productionTransferFour
    )
    &&
    $productionTransferFour[
        'selection_points_lost'
    ] === null
);


/*
 * ============================================================
 * SCENARIO L
 * AGGREGATE METRICS
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario L: Aggregate Metrics'
);


$productionMetrics =
    $productionEvaluation[
        'metrics'
    ]
    ?? [];


$fixtureHeavyMetrics =
    $fixtureHeavyEvaluation[
        'metrics'
    ]
    ?? [];


transferDecisionWeightCalibrationCheck(
    'Production metrics count all valid historical transfer universes',
    ($productionMetrics['total_transfers'] ?? null) === 4
);


transferDecisionWeightCalibrationCheck(
    'Production metrics count three comparable transfers',
    ($productionMetrics['comparable_transfers'] ?? null) === 3
);


transferDecisionWeightCalibrationCheck(
    'Production metrics count one unavailable transfer',
    ($productionMetrics['unavailable_transfers'] ?? null) === 1
);


transferDecisionWeightCalibrationCheck(
    'Production total selection points lost is eleven',
    ($productionMetrics['total_selection_points_lost'] ?? null) === 11
);


transferDecisionWeightCalibrationCheck(
    'Production mean selection points lost is 3.67',
    ($productionMetrics['mean_selection_points_lost'] ?? null) === 3.67
);


transferDecisionWeightCalibrationCheck(
    'Production weights make one optimal comparable selection',
    ($productionMetrics['optimal_replacement_selections'] ?? null) === 1
);


transferDecisionWeightCalibrationCheck(
    'Fixture-heavy total selection points lost is five',
    ($fixtureHeavyMetrics['total_selection_points_lost'] ?? null) === 5
);


transferDecisionWeightCalibrationCheck(
    'Fixture-heavy mean selection points lost is 1.67',
    ($fixtureHeavyMetrics['mean_selection_points_lost'] ?? null) === 1.67
);


transferDecisionWeightCalibrationCheck(
    'Fixture-heavy weights make two optimal comparable selections',
    ($fixtureHeavyMetrics['optimal_replacement_selections'] ?? null) === 2
);


/*
 * ============================================================
 * SCENARIO M
 * EMPTY CANDIDATE SET
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario M: Empty Candidate Set'
);


$emptyCandidateResult =
    $service->evaluate(
        $historicalTransfers,
        []
    );


transferDecisionWeightCalibrationCheck(
    'Empty weight candidate set returns no evaluations',
    ($emptyCandidateResult['evaluations'] ?? null) === []
);


/*
 * ============================================================
 * SCENARIO N
 * EMPTY HISTORICAL EVIDENCE
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario N: Empty Historical Evidence'
);


$emptyHistoryResult =
    $service->evaluate(
        [],
        [
            $productionWeights
        ]
    );


$emptyHistoryEvaluation =
    $emptyHistoryResult[
        'evaluations'
    ][0]
    ?? [];


$emptyHistoryMetrics =
    $emptyHistoryEvaluation[
        'metrics'
    ]
    ?? [];


transferDecisionWeightCalibrationCheck(
    'Valid candidate is still evaluated when history is empty',
    count(
        $emptyHistoryResult[
            'evaluations'
        ]
        ?? []
    )
    === 1
);


transferDecisionWeightCalibrationCheck(
    'Empty history produces no transfer evaluations',
    ($emptyHistoryEvaluation['transfers'] ?? null) === []
);


transferDecisionWeightCalibrationCheck(
    'Empty history has zero total transfers',
    ($emptyHistoryMetrics['total_transfers'] ?? null) === 0
);


transferDecisionWeightCalibrationCheck(
    'Empty history has zero comparable transfers',
    ($emptyHistoryMetrics['comparable_transfers'] ?? null) === 0
);


transferDecisionWeightCalibrationCheck(
    'Empty history has zero unavailable transfers',
    ($emptyHistoryMetrics['unavailable_transfers'] ?? null) === 0
);


transferDecisionWeightCalibrationCheck(
    'Empty history has zero total selection loss',
    ($emptyHistoryMetrics['total_selection_points_lost'] ?? null) === 0
);


transferDecisionWeightCalibrationCheck(
    'Empty history mean selection loss remains null',
    array_key_exists(
        'mean_selection_points_lost',
        $emptyHistoryMetrics
    )
    &&
    $emptyHistoryMetrics[
        'mean_selection_points_lost'
    ] === null
);


transferDecisionWeightCalibrationCheck(
    'Empty history has zero optimal selections',
    ($emptyHistoryMetrics['optimal_replacement_selections'] ?? null) === 0
);


/*
 * ============================================================
 * SCENARIO O
 * INVALID WEIGHT CANDIDATES
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario O: Invalid Weight Candidates'
);


$invalidNonArrayThrew =
    false;


try {

    $service->evaluate(
        [],
        [
            'invalid'
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidNonArrayThrew =
        true;
}


transferDecisionWeightCalibrationCheck(
    'Non-array weight candidate is rejected',
    $invalidNonArrayThrew
);


$invalidMissingWeightThrew =
    false;


try {

    $service->evaluate(
        [],
        [
            [
                'intelligence_weight' => 0.40,
                'fixture_weight' => 0.20,
                'value_weight' => 0.15,
                'strength_weight' => 0.10,
                'budget_weight' => 0.10
            ]
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidMissingWeightThrew =
        true;
}


transferDecisionWeightCalibrationCheck(
    'Missing required weight is rejected',
    $invalidMissingWeightThrew
);


$invalidNonNumericWeightThrew =
    false;


try {

    $service->evaluate(
        [],
        [
            [
                'intelligence_weight' => 'bad',
                'fixture_weight' => 0.20,
                'value_weight' => 0.15,
                'strength_weight' => 0.10,
                'budget_weight' => 0.10,
                'confidence_weight' => 0.05
            ]
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidNonNumericWeightThrew =
        true;
}


transferDecisionWeightCalibrationCheck(
    'Non-numeric weight is rejected',
    $invalidNonNumericWeightThrew
);


$invalidRangeWeightThrew =
    false;


try {

    $service->evaluate(
        [],
        [
            [
                'intelligence_weight' => 1.10,
                'fixture_weight' => 0.00,
                'value_weight' => 0.00,
                'strength_weight' => 0.00,
                'budget_weight' => 0.00,
                'confidence_weight' => -0.10
            ]
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidRangeWeightThrew =
        true;
}


transferDecisionWeightCalibrationCheck(
    'Weight outside zero-to-one range is rejected',
    $invalidRangeWeightThrew
);


$invalidWeightTotalThrew =
    false;


try {

    $service->evaluate(
        [],
        [
            [
                'intelligence_weight' => 0.40,
                'fixture_weight' => 0.20,
                'value_weight' => 0.15,
                'strength_weight' => 0.10,
                'budget_weight' => 0.10,
                'confidence_weight' => 0.10
            ]
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidWeightTotalThrew =
        true;
}


transferDecisionWeightCalibrationCheck(
    'Six Transfer Decision weights must sum to one',
    $invalidWeightTotalThrew
);


/*
 * ============================================================
 * SCENARIO P
 * NO HIDDEN PRODUCTION CANDIDATE
 * ============================================================
 */

transferDecisionWeightCalibrationSection(
    'Scenario P: Caller Owns Candidate Grid'
);


transferDecisionWeightCalibrationCheck(
    'Calibration service does not manufacture a hidden production candidate',
    count(
        $emptyCandidateResult[
            'evaluations'
        ]
        ?? []
    )
    === 0
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

transferDecisionWeightCalibrationSummary();