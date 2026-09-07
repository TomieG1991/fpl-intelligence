<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Transfer Decision Backtesting Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekTransferDecisionIntegrationTestResult(
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

$transferBacktestingService =
    new TransferBacktestingService();


$gameweekTransferBacktestingService =
    new GameweekTransferBacktestingService(
        $transferBacktestingService
    );


$transferDecisionBacktestingService =
    new TransferDecisionBacktestingService();


$gameweekTransferDecisionBacktestingService =
    new GameweekTransferDecisionBacktestingService(
        $transferDecisionBacktestingService
    );


gameweekTransferDecisionIntegrationTestResult(
    $transferBacktestingService
        instanceof TransferBacktestingService,
    'Real TransferBacktestingService is constructed.'
);


gameweekTransferDecisionIntegrationTestResult(
    $gameweekTransferBacktestingService
        instanceof GameweekTransferBacktestingService,
    'Real GameweekTransferBacktestingService is constructed.'
);


gameweekTransferDecisionIntegrationTestResult(
    $transferDecisionBacktestingService
        instanceof TransferDecisionBacktestingService,
    'Real TransferDecisionBacktestingService is constructed.'
);


gameweekTransferDecisionIntegrationTestResult(
    $gameweekTransferDecisionBacktestingService
        instanceof GameweekTransferDecisionBacktestingService,
    'Real GameweekTransferDecisionBacktestingService is constructed.'
);


/*
 * ============================================================
 * FIXTURE HELPERS
 * ============================================================
 */

function buildTransferRecommendationEvidence(
    int $outgoingPlayerId,
    int $incomingPlayerId
): array {

    return [

        'analysis' =>
            [
                'status' =>
                    'success'
            ],

        'recommendations' =>
            [
                'status' =>
                    'success',

                'recommendations' =>
                    [
                        [
                            'outgoing' =>
                                [
                                    'player_id' =>
                                        $outgoingPlayerId,

                                    'name' =>
                                        'Outgoing Player'
                                ],

                            'transfer_priority' =>
                                78.0,

                            'priority_label' =>
                                'High',

                            'available_budget' =>
                                8.5,

                            'legal_candidate_count' =>
                                1,

                            'replacement_count' =>
                                1,

                            'replacements' =>
                                [
                                    [
                                        'player' =>
                                            [
                                                'player_id' =>
                                                    $incomingPlayerId,

                                                'name' =>
                                                    'Incoming Player'
                                            ],

                                        'decision' =>
                                            [
                                                'decision_score' =>
                                                    82.0,

                                                'decision_type' =>
                                                    'Strong'
                                            ],

                                        'decision_score' =>
                                            82.0,

                                        'decision_type' =>
                                            'Strong',

                                        'budget_after' =>
                                            0.5,

                                        'rank' =>
                                            1
                                    ]
                                ]
                        ]
                    ]
            ]
    ];
}


function buildTransferPlayerOutcomes(
    int $outgoingPlayerId,
    int|float $outgoingPoints,
    int $incomingPlayerId,
    int|float $incomingPoints
): array {

    return [

        [
            'gameweek_id' =>
                5,

            'player_id' =>
                $outgoingPlayerId,

            'fixture_count' =>
                1,

            'total_points' =>
                $outgoingPoints,

            'minutes' =>
                90,

            'starts' =>
                1,

            'goals' =>
                0,

            'assists' =>
                0,

            'clean_sheets' =>
                0,

            'bonus' =>
                0
        ],

        [
            'gameweek_id' =>
                5,

            'player_id' =>
                $incomingPlayerId,

            'fixture_count' =>
                1,

            'total_points' =>
                $incomingPoints,

            'minutes' =>
                90,

            'starts' =>
                1,

            'goals' =>
                0,

            'assists' =>
                0,

            'clean_sheets' =>
                0,

            'bonus' =>
                0
        ]
    ];
}


function buildTransferDecisionEvidence(
    string $action,
    string $priority,
    ?float $score,
    string $overallAction,
    array $transferRecommendations,
    array $playerOutcomes
): array {

    return [

        'status' =>
            'Ready',

        'reason' =>
            null,

        'entry_id' =>
            935010005,

        'gameweek_id' =>
            5,

        'recommendation_snapshot' =>
            [
                'transfer_recommendations' =>
                    $transferRecommendations,

                'gameweek_decision' =>
                    [
                        'status' =>
                            'success',

                        'overall_action' =>
                            $overallAction,

                        'transfer_advice' =>
                            [
                                'action' =>
                                    $action,

                                'priority' =>
                                    $priority,

                                'score' =>
                                    $score,

                                'recommendations' =>
                                    []
                            ]
                    ]
            ],

        'player_outcomes' =>
            $playerOutcomes
    ];
}


/*
 * ============================================================
 * SCENARIO A
 * MAKE TRANSFER + POSITIVE REALISED GAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Make Transfer With Positive Gain<br>";
echo "============================================<br>";


$transferRecommendations =
    buildTransferRecommendationEvidence(
        101,
        201
    );


$playerOutcomes =
    buildTransferPlayerOutcomes(
        101,
        3,
        201,
        9
    );


$evidence =
    buildTransferDecisionEvidence(
        'Make Transfer',
        'High',
        78.0,
        'Make Transfer',
        $transferRecommendations,
        $playerOutcomes
    );


$transferResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $evidence
        );


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'status'
        ]
        ?? null
    )
    ===
    'Ready',
    'Real factual transfer orchestration is Ready.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'transfer_evaluation'
        ][
            'outgoing_player_id'
        ]
        ?? null
    )
    ===
    101,
    'Real factual transfer orchestration preserves outgoing player.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'transfer_evaluation'
        ][
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    201,
    'Real factual transfer orchestration preserves incoming player.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'transfer_evaluation'
        ][
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    6,
    'Real factual transfer orchestration calculates positive realised gain.'
);


$decisionResult =
    $gameweekTransferDecisionBacktestingService
        ->evaluate(
            $evidence,
            $transferResult
        );


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'status'
        ]
        ?? null
    )
    ===
    'Ready',
    'Make Transfer decision orchestration is Ready.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'decision_action'
        ]
        ?? null
    )
    ===
    'Make Transfer',
    'Preserved Make Transfer action reaches real decision evaluator.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'decision_priority'
        ]
        ?? null
    )
    ===
    'High',
    'Preserved High priority reaches real decision evaluator.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'decision_score'
        ]
        ?? null
    )
    ===
    78.0,
    'Preserved decision score reaches real decision evaluator.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    6,
    'Realised transfer gain reaches real decision evaluator.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'support_status'
        ]
        ?? null
    )
    ===
    'Supported',
    'Make Transfer is supported when real factual comparison produces positive gain.'
);


/*
 * ============================================================
 * SCENARIO B
 * MAKE TRANSFER + NEGATIVE REALISED GAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Make Transfer With Negative Gain<br>";
echo "============================================<br>";


$playerOutcomes =
    buildTransferPlayerOutcomes(
        101,
        10,
        201,
        2
    );


$evidence =
    buildTransferDecisionEvidence(
        'Make Transfer',
        'High',
        78.0,
        'Make Transfer',
        $transferRecommendations,
        $playerOutcomes
    );


$transferResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $evidence
        );


$decisionResult =
    $gameweekTransferDecisionBacktestingService
        ->evaluate(
            $evidence,
            $transferResult
        );


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'transfer_evaluation'
        ][
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    -8,
    'Real factual transfer service preserves negative gain.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'support_status'
        ]
        ?? null
    )
    ===
    'Not Supported',
    'Make Transfer is not supported when real factual comparison produces negative gain.'
);


/*
 * ============================================================
 * SCENARIO C
 * HOLD + POSITIVE REALISED GAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Hold With Positive Gain<br>";
echo "============================================<br>";


$playerOutcomes =
    buildTransferPlayerOutcomes(
        101,
        2,
        201,
        8
    );


$evidence =
    buildTransferDecisionEvidence(
        'Hold',
        'Low',
        42.0,
        'Hold',
        $transferRecommendations,
        $playerOutcomes
    );


$transferResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $evidence
        );


$decisionResult =
    $gameweekTransferDecisionBacktestingService
        ->evaluate(
            $evidence,
            $transferResult
        );


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'transfer_evaluation'
        ][
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    6,
    'Real factual transfer service produces positive gain for Hold scenario.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'decision_action'
        ]
        ?? null
    )
    ===
    'Hold',
    'Preserved Hold action reaches real decision evaluator.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'support_status'
        ]
        ?? null
    )
    ===
    'Not Supported',
    'Hold is not supported when preserved candidate would have gained points.'
);


/*
 * ============================================================
 * SCENARIO D
 * HOLD + NEGATIVE REALISED GAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Hold With Negative Gain<br>";
echo "============================================<br>";


$playerOutcomes =
    buildTransferPlayerOutcomes(
        101,
        9,
        201,
        4
    );


$evidence =
    buildTransferDecisionEvidence(
        'Hold',
        'Low',
        42.0,
        'Hold',
        $transferRecommendations,
        $playerOutcomes
    );


$transferResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $evidence
        );


$decisionResult =
    $gameweekTransferDecisionBacktestingService
        ->evaluate(
            $evidence,
            $transferResult
        );


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'transfer_evaluation'
        ][
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    -5,
    'Real factual transfer service produces negative gain for Hold scenario.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'support_status'
        ]
        ?? null
    )
    ===
    'Supported',
    'Hold is supported when preserved candidate would have lost points.'
);


/*
 * ============================================================
 * SCENARIO E
 * CONSIDER TRANSFER REMAINS INCONCLUSIVE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Consider Transfer Is Inconclusive<br>";
echo "============================================<br>";


$playerOutcomes =
    buildTransferPlayerOutcomes(
        101,
        3,
        201,
        12
    );


$evidence =
    buildTransferDecisionEvidence(
        'Consider Transfer',
        'Medium',
        61.0,
        'Consider Transfer',
        $transferRecommendations,
        $playerOutcomes
    );


$transferResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $evidence
        );


$decisionResult =
    $gameweekTransferDecisionBacktestingService
        ->evaluate(
            $evidence,
            $transferResult
        );


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'transfer_evaluation'
        ][
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    9,
    'Consider Transfer retains its factual positive realised gain.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'decision_action'
        ]
        ?? null
    )
    ===
    'Consider Transfer',
    'Preserved Consider Transfer action reaches real decision evaluator.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'support_status'
        ]
        ?? null
    )
    ===
    'Inconclusive',
    'Consider Transfer remains inconclusive despite positive realised gain.'
);


/*
 * ============================================================
 * SCENARIO F
 * OVERALL ACTION MUST NOT OVERRIDE TRANSFER ADVICE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Overall Action Does Not Override Transfer Advice<br>";
echo "============================================<br>";


$playerOutcomes =
    buildTransferPlayerOutcomes(
        101,
        10,
        201,
        3
    );


$evidence =
    buildTransferDecisionEvidence(
        'Hold',
        'Low',
        42.0,
        'Urgent Action',
        $transferRecommendations,
        $playerOutcomes
    );


$transferResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $evidence
        );


$decisionResult =
    $gameweekTransferDecisionBacktestingService
        ->evaluate(
            $evidence,
            $transferResult
        );


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'decision_action'
        ]
        ?? null
    )
    ===
    'Hold',
    'Real decision chain evaluates transfer_advice rather than overall_action.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'support_status'
        ]
        ?? null
    )
    ===
    'Supported',
    'Urgent overall action does not contaminate supported Hold evaluation.'
);


/*
 * ============================================================
 * SCENARIO G
 * RANK-ONE PRESERVED CANDIDATE REMAINS AUTHORITATIVE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Preserved Rank-One Candidate Is Evaluated<br>";
echo "============================================<br>";


$rankedTransferRecommendations =
    buildTransferRecommendationEvidence(
        101,
        201
    );


$rankedTransferRecommendations[
    'recommendations'
][
    'recommendations'
][
    0
][
    'replacements'
][] =
    [
        'player' =>
            [
                'player_id' =>
                    202,

                'name' =>
                    'Alternative Player'
            ],

        'decision' =>
            [
                'decision_score' =>
                    70.0,

                'decision_type' =>
                    'Consider'
            ],

        'decision_score' =>
            70.0,

        'decision_type' =>
            'Consider',

        'budget_after' =>
            0.7,

        'rank' =>
            2
    ];


$playerOutcomes =
    buildTransferPlayerOutcomes(
        101,
        4,
        201,
        6
    );


$playerOutcomes[] =
    [
        'gameweek_id' =>
            5,

        'player_id' =>
            202,

        'fixture_count' =>
            1,

        'total_points' =>
            15,

        'minutes' =>
            90,

        'starts' =>
            1,

        'goals' =>
            2,

        'assists' =>
            0,

        'clean_sheets' =>
            0,

        'bonus' =>
            3
    ];


$evidence =
    buildTransferDecisionEvidence(
        'Make Transfer',
        'High',
        78.0,
        'Make Transfer',
        $rankedTransferRecommendations,
        $playerOutcomes
    );


$transferResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $evidence
        );


$decisionResult =
    $gameweekTransferDecisionBacktestingService
        ->evaluate(
            $evidence,
            $transferResult
        );


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'transfer_evaluation'
        ][
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    201,
    'Real factual transfer service evaluates preserved rank-one replacement.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'transfer_evaluation'
        ][
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    2,
    'Later higher-scoring alternative does not replace preserved rank-one candidate with hindsight.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    201,
    'Decision backtesting uses the same preserved rank-one candidate.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ][
            'support_status'
        ]
        ?? null
    )
    ===
    'Supported',
    'Preserved Make Transfer decision is evaluated against rank-one candidate only.'
);


/*
 * ============================================================
 * SCENARIO H
 * INCOMPLETE FACTUAL TRANSFER STOPS DECISION EVALUATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Incomplete Factual Transfer Stops Decision Layer<br>";
echo "============================================<br>";


$missingIncomingOutcomes =
    buildTransferPlayerOutcomes(
        101,
        5,
        999,
        8
    );


$evidence =
    buildTransferDecisionEvidence(
        'Make Transfer',
        'High',
        78.0,
        'Make Transfer',
        $transferRecommendations,
        $missingIncomingOutcomes
    );


$transferResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $evidence
        );


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Missing incoming realised outcome makes factual transfer evaluation Incomplete.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $transferResult[
            'transfer_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Incomplete factual transfer evaluation remains empty.'
);


$decisionResult =
    $gameweekTransferDecisionBacktestingService
        ->evaluate(
            $evidence,
            $transferResult
        );


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Decision layer propagates factual transfer incompleteness.'
);


gameweekTransferDecisionIntegrationTestResult(
    (
        $decisionResult[
            'decision_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Decision layer does not manufacture evaluation without factual transfer result.'
);


/*
 * ============================================================
 * SCENARIO I
 * SOURCE EVIDENCE REMAINS IMMUTABLE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Source Evidence Remains Immutable<br>";
echo "============================================<br>";


$playerOutcomes =
    buildTransferPlayerOutcomes(
        101,
        3,
        201,
        9
    );


$evidence =
    buildTransferDecisionEvidence(
        'Make Transfer',
        'High',
        78.0,
        'Make Transfer',
        $transferRecommendations,
        $playerOutcomes
    );


$evidenceBefore =
    $evidence;


$transferResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $evidence
        );


$transferResultBefore =
    $transferResult;


$gameweekTransferDecisionBacktestingService
    ->evaluate(
        $evidence,
        $transferResult
    );


gameweekTransferDecisionIntegrationTestResult(
    $evidence
    ===
    $evidenceBefore,
    'Real transfer backtesting chain does not mutate historical evidence.'
);


gameweekTransferDecisionIntegrationTestResult(
    $transferResult
    ===
    $transferResultBefore,
    'Real decision backtesting chain does not mutate factual transfer result.'
);


/*
 * ============================================================
 * SCENARIO J
 * NO OUT-OF-SCOPE DECISION METRICS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Integration Scope Boundary<br>";
echo "============================================<br>";


$decisionResult =
    $gameweekTransferDecisionBacktestingService
        ->evaluate(
            $evidence,
            $transferResult
        );


gameweekTransferDecisionIntegrationTestResult(
    !array_key_exists(
        'accuracy_score',
        $decisionResult
    ),
    'Integrated decision result does not manufacture an accuracy score.'
);


gameweekTransferDecisionIntegrationTestResult(
    !array_key_exists(
        'overall_score',
        $decisionResult
    ),
    'Integrated decision result does not manufacture an overall score.'
);


gameweekTransferDecisionIntegrationTestResult(
    !array_key_exists(
        'transfer_hit',
        $decisionResult
    ),
    'Integrated decision result does not assume a transfer hit.'
);


gameweekTransferDecisionIntegrationTestResult(
    !array_key_exists(
        'net_points_gain',
        $decisionResult
    ),
    'Integrated decision result does not manufacture hit-adjusted gain.'
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


if (
    $failed === 0
) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}