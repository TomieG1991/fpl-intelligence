<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Decision Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function transferDecisionBacktestingTestResult(
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
 * CLASS EXISTENCE
 * ============================================================
 */

if (
    !class_exists(
        'TransferDecisionBacktestingService'
    )
) {

    transferDecisionBacktestingTestResult(
        false,
        'TransferDecisionBacktestingService exists.'
    );


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


transferDecisionBacktestingTestResult(
    true,
    'TransferDecisionBacktestingService exists.'
);


$service =
    new TransferDecisionBacktestingService();


transferDecisionBacktestingTestResult(
    $service
        instanceof TransferDecisionBacktestingService,
    'Transfer decision backtesting service can be constructed.'
);


/*
 * ============================================================
 * BASE PRESERVED GAMEWEEK DECISION
 * ============================================================
 */

$makeTransferDecision =
    [
        'status' =>
            'success',

        'overall_action' =>
            'Make Transfer',

        'transfer_advice' =>
            [
                'action' =>
                    'Make Transfer',

                'priority' =>
                    'High',

                'score' =>
                    78.0,

                'recommendations' =>
                    [
                        [
                            'outgoing' =>
                                [
                                    'player_id' =>
                                        101
                                ],

                            'replacements' =>
                                [
                                    [
                                        'player' =>
                                            [
                                                'player_id' =>
                                                    201
                                            ]
                                    ]
                                ]
                        ]
                    ],

                'message' =>
                    'Transfer Intelligence identifies a high-priority move.'
            ]
    ];


$positiveTransferEvaluation =
    [
        'outgoing_player_id' =>
            101,

        'incoming_player_id' =>
            201,

        'outgoing_actual_points' =>
            3,

        'incoming_actual_points' =>
            9,

        'transfer_points_gain' =>
            6
    ];


/*
 * ============================================================
 * SCENARIO A
 * MAKE TRANSFER + POSITIVE GAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Make Transfer With Positive Gain<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $makeTransferDecision,
        $positiveTransferEvaluation
    );


transferDecisionBacktestingTestResult(
    (
        $result[
            'decision_action'
        ]
        ?? null
    )
    ===
    'Make Transfer',
    'Preserved Make Transfer action is retained.'
);


transferDecisionBacktestingTestResult(
    (
        $result[
            'decision_priority'
        ]
        ?? null
    )
    ===
    'High',
    'Preserved High transfer priority is retained.'
);


transferDecisionBacktestingTestResult(
    (
        $result[
            'decision_score'
        ]
        ?? null
    )
    ===
    78.0,
    'Preserved transfer decision score is retained.'
);


transferDecisionBacktestingTestResult(
    (
        $result[
            'outgoing_player_id'
        ]
        ?? null
    )
    ===
    101,
    'Outgoing player identity comes from realised transfer evaluation.'
);


transferDecisionBacktestingTestResult(
    (
        $result[
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    201,
    'Incoming player identity comes from realised transfer evaluation.'
);


transferDecisionBacktestingTestResult(
    (
        $result[
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    6,
    'Realised transfer points gain is preserved.'
);


transferDecisionBacktestingTestResult(
    (
        $result[
            'support_status'
        ]
        ?? null
    )
    ===
    'Supported',
    'Make Transfer is supported when preserved transfer candidate produces a positive realised gain.'
);


/*
 * ============================================================
 * SCENARIO B
 * MAKE TRANSFER + ZERO GAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Make Transfer With Zero Gain<br>";
echo "============================================<br>";


$zeroGainEvaluation =
    $positiveTransferEvaluation;


$zeroGainEvaluation[
    'incoming_actual_points'
] =
    3;


$zeroGainEvaluation[
    'transfer_points_gain'
] =
    0;


$result =
    $service->evaluate(
        $makeTransferDecision,
        $zeroGainEvaluation
    );


transferDecisionBacktestingTestResult(
    (
        $result[
            'support_status'
        ]
        ?? null
    )
    ===
    'Not Supported',
    'Make Transfer is not supported when incoming player only matches outgoing player.'
);


/*
 * ============================================================
 * SCENARIO C
 * MAKE TRANSFER + NEGATIVE GAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Make Transfer With Negative Gain<br>";
echo "============================================<br>";


$negativeTransferEvaluation =
    [
        'outgoing_player_id' =>
            101,

        'incoming_player_id' =>
            201,

        'outgoing_actual_points' =>
            10,

        'incoming_actual_points' =>
            2,

        'transfer_points_gain' =>
            -8
    ];


$result =
    $service->evaluate(
        $makeTransferDecision,
        $negativeTransferEvaluation
    );


transferDecisionBacktestingTestResult(
    (
        $result[
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    -8,
    'Negative realised transfer gain is preserved.'
);


transferDecisionBacktestingTestResult(
    (
        $result[
            'support_status'
        ]
        ?? null
    )
    ===
    'Not Supported',
    'Make Transfer is not supported when preserved transfer candidate loses points.'
);


/*
 * ============================================================
 * SCENARIO D
 * HOLD + NEGATIVE GAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Hold With Negative Transfer Gain<br>";
echo "============================================<br>";


$holdDecision =
    $makeTransferDecision;


$holdDecision[
    'overall_action'
] =
    'Hold';


$holdDecision[
    'transfer_advice'
][
    'action'
] =
    'Hold';


$holdDecision[
    'transfer_advice'
][
    'priority'
] =
    'Low';


$holdDecision[
    'transfer_advice'
][
    'score'
] =
    42.0;


$result =
    $service->evaluate(
        $holdDecision,
        $negativeTransferEvaluation
    );


transferDecisionBacktestingTestResult(
    (
        $result[
            'decision_action'
        ]
        ?? null
    )
    ===
    'Hold',
    'Preserved Hold action is retained.'
);


transferDecisionBacktestingTestResult(
    (
        $result[
            'support_status'
        ]
        ?? null
    )
    ===
    'Supported',
    'Hold is supported when preserved transfer candidate would have lost points.'
);


/*
 * ============================================================
 * SCENARIO E
 * HOLD + ZERO GAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Hold With Zero Transfer Gain<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $holdDecision,
        $zeroGainEvaluation
    );


transferDecisionBacktestingTestResult(
    (
        $result[
            'support_status'
        ]
        ?? null
    )
    ===
    'Supported',
    'Hold is supported when preserved transfer candidate produces no realised gain.'
);


/*
 * ============================================================
 * SCENARIO F
 * HOLD + POSITIVE GAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Hold With Positive Transfer Gain<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $holdDecision,
        $positiveTransferEvaluation
    );


transferDecisionBacktestingTestResult(
    (
        $result[
            'support_status'
        ]
        ?? null
    )
    ===
    'Not Supported',
    'Hold is not supported when preserved transfer candidate produces a positive realised gain.'
);


/*
 * ============================================================
 * SCENARIO G
 * CONSIDER TRANSFER
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Consider Transfer Remains Inconclusive<br>";
echo "============================================<br>";


$considerDecision =
    $makeTransferDecision;


$considerDecision[
    'overall_action'
] =
    'Consider Transfer';


$considerDecision[
    'transfer_advice'
][
    'action'
] =
    'Consider Transfer';


$considerDecision[
    'transfer_advice'
][
    'priority'
] =
    'Medium';


$considerDecision[
    'transfer_advice'
][
    'score'
] =
    61.0;


$result =
    $service->evaluate(
        $considerDecision,
        $positiveTransferEvaluation
    );


transferDecisionBacktestingTestResult(
    (
        $result[
            'decision_action'
        ]
        ?? null
    )
    ===
    'Consider Transfer',
    'Preserved Consider Transfer action is retained.'
);


transferDecisionBacktestingTestResult(
    (
        $result[
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    6,
    'Consider Transfer still preserves factual realised transfer gain.'
);


transferDecisionBacktestingTestResult(
    (
        $result[
            'support_status'
        ]
        ?? null
    )
    ===
    'Inconclusive',
    'Consider Transfer is not retrospectively forced into supported or unsupported classification.'
);


$result =
    $service->evaluate(
        $considerDecision,
        $negativeTransferEvaluation
    );


transferDecisionBacktestingTestResult(
    (
        $result[
            'support_status'
        ]
        ?? null
    )
    ===
    'Inconclusive',
    'Consider Transfer remains inconclusive when transfer candidate loses points.'
);


/*
 * ============================================================
 * SCENARIO H
 * USE TRANSFER ADVICE, NOT OVERALL ACTION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Transfer Advice Is The Historical Decision<br>";
echo "============================================<br>";


$mismatchedOverallDecision =
    $holdDecision;


$mismatchedOverallDecision[
    'overall_action'
] =
    'Urgent Action';


$result =
    $service->evaluate(
        $mismatchedOverallDecision,
        $negativeTransferEvaluation
    );


transferDecisionBacktestingTestResult(
    (
        $result[
            'decision_action'
        ]
        ?? null
    )
    ===
    'Hold',
    'Transfer decision evaluation uses preserved transfer_advice rather than overall_action.'
);


transferDecisionBacktestingTestResult(
    (
        $result[
            'support_status'
        ]
        ?? null
    )
    ===
    'Supported',
    'Unrelated overall gameweek action does not alter transfer decision support classification.'
);


/*
 * ============================================================
 * SCENARIO I
 * REVIEW / NO TRANSFER DATA ARE NOT GRADED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Non-Directional Transfer Advice<br>";
echo "============================================<br>";


$reviewDecision =
    $makeTransferDecision;


$reviewDecision[
    'transfer_advice'
][
    'action'
] =
    'Review';


$reviewDecision[
    'transfer_advice'
][
    'priority'
] =
    'Unknown';


$reviewDecision[
    'transfer_advice'
][
    'score'
] =
    null;


$result =
    $service->evaluate(
        $reviewDecision,
        $positiveTransferEvaluation
    );


transferDecisionBacktestingTestResult(
    (
        $result[
            'support_status'
        ]
        ?? null
    )
    ===
    'Inconclusive',
    'Review transfer advice is not retrospectively graded as supported or unsupported.'
);


$noTransferDataDecision =
    [
        'status' =>
            'success',

        'overall_action' =>
            'Hold',

        'transfer_advice' =>
            [
                'action' =>
                    'No Transfer Data',

                'priority' =>
                    'Unknown',

                'score' =>
                    null,

                'recommendations' =>
                    [],

                'message' =>
                    'Transfer intelligence was not supplied.'
            ]
    ];


$result =
    $service->evaluate(
        $noTransferDataDecision,
        $positiveTransferEvaluation
    );


transferDecisionBacktestingTestResult(
    (
        $result[
            'support_status'
        ]
        ?? null
    )
    ===
    'Inconclusive',
    'No Transfer Data is not treated as a successful Hold recommendation.'
);


/*
 * ============================================================
 * SCENARIO J
 * INVALID / MISSING HISTORICAL EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Invalid Or Missing Historical Evidence<br>";
echo "============================================<br>";


transferDecisionBacktestingTestResult(
    $service->evaluate(
        [],
        $positiveTransferEvaluation
    )
    ===
    [],
    'Missing preserved gameweek decision cannot be evaluated.'
);


transferDecisionBacktestingTestResult(
    $service->evaluate(
        [
            'status' =>
                'success'
        ],
        $positiveTransferEvaluation
    )
    ===
    [],
    'Missing preserved transfer advice cannot be evaluated.'
);


transferDecisionBacktestingTestResult(
    $service->evaluate(
        $makeTransferDecision,
        []
    )
    ===
    [],
    'Missing realised transfer evaluation cannot be evaluated.'
);


$invalidActionDecision =
    $makeTransferDecision;


$invalidActionDecision[
    'transfer_advice'
][
    'action'
] =
    'Something Else';


transferDecisionBacktestingTestResult(
    $service->evaluate(
        $invalidActionDecision,
        $positiveTransferEvaluation
    )
    ===
    [],
    'Unknown preserved transfer action cannot be evaluated.'
);


$invalidGainEvaluation =
    $positiveTransferEvaluation;


$invalidGainEvaluation[
    'transfer_points_gain'
] =
    'not numeric';


transferDecisionBacktestingTestResult(
    $service->evaluate(
        $makeTransferDecision,
        $invalidGainEvaluation
    )
    ===
    [],
    'Nonnumeric realised transfer gain cannot be evaluated.'
);


/*
 * ============================================================
 * SCENARIO K
 * PLAYER IDENTITIES MUST BE VALID
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Transfer Player Identity Validation<br>";
echo "============================================<br>";


$invalidOutgoingEvaluation =
    $positiveTransferEvaluation;


$invalidOutgoingEvaluation[
    'outgoing_player_id'
] =
    0;


transferDecisionBacktestingTestResult(
    $service->evaluate(
        $makeTransferDecision,
        $invalidOutgoingEvaluation
    )
    ===
    [],
    'Invalid outgoing player identity cannot be evaluated.'
);


$samePlayerEvaluation =
    $positiveTransferEvaluation;


$samePlayerEvaluation[
    'incoming_player_id'
] =
    101;


transferDecisionBacktestingTestResult(
    $service->evaluate(
        $makeTransferDecision,
        $samePlayerEvaluation
    )
    ===
    [],
    'Outgoing and incoming player must remain distinct.'
);


/*
 * ============================================================
 * SCENARIO L
 * SOURCE EVIDENCE MUST NOT BE MUTATED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario L: Source Evidence Remains Immutable<br>";
echo "============================================<br>";


$decisionBefore =
    $makeTransferDecision;


$evaluationBefore =
    $positiveTransferEvaluation;


$service->evaluate(
    $makeTransferDecision,
    $positiveTransferEvaluation
);


transferDecisionBacktestingTestResult(
    $makeTransferDecision
    ===
    $decisionBefore,
    'Preserved gameweek decision evidence is not mutated.'
);


transferDecisionBacktestingTestResult(
    $positiveTransferEvaluation
    ===
    $evaluationBefore,
    'Realised transfer evaluation evidence is not mutated.'
);


/*
 * ============================================================
 * SCENARIO M
 * BACKTESTING SCOPE BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario M: Backtesting Scope Boundary<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $makeTransferDecision,
        $positiveTransferEvaluation
    );


transferDecisionBacktestingTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Transfer decision backtesting does not manufacture an accuracy score.'
);


transferDecisionBacktestingTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Transfer decision backtesting does not manufacture an overall score.'
);


transferDecisionBacktestingTestResult(
    !array_key_exists(
        'transfer_hit',
        $result
    ),
    'Transfer decision backtesting does not assume a transfer hit.'
);


transferDecisionBacktestingTestResult(
    !array_key_exists(
        'net_points_gain',
        $result
    ),
    'Transfer decision backtesting does not manufacture hit-adjusted net gain.'
);


transferDecisionBacktestingTestResult(
    !array_key_exists(
        'correct',
        $result
    ),
    'Transfer decision backtesting does not reduce historical evidence to a correctness boolean.'
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