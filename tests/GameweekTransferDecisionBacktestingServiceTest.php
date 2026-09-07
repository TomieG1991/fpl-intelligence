<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Transfer Decision Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekTransferDecisionBacktestingTestResult(
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
 * SPY SPECIALIST
 * ============================================================
 */

class GameweekTransferDecisionBacktestingSpy
{
    public int $callCount = 0;

    public array $receivedGameweekDecision = [];

    public array $receivedTransferEvaluation = [];

    public array $result = [];


    public function evaluate(
        array $gameweekDecision,
        array $transferEvaluation
    ): array {

        $this->callCount++;

        $this->receivedGameweekDecision =
            $gameweekDecision;

        $this->receivedTransferEvaluation =
            $transferEvaluation;


        return $this->result;
    }
}


/*
 * ============================================================
 * CLASS EXISTENCE
 * ============================================================
 */

if (
    !class_exists(
        'GameweekTransferDecisionBacktestingService'
    )
) {

    gameweekTransferDecisionBacktestingTestResult(
        false,
        'GameweekTransferDecisionBacktestingService exists.'
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


gameweekTransferDecisionBacktestingTestResult(
    true,
    'GameweekTransferDecisionBacktestingService exists.'
);


/*
 * ============================================================
 * CONSTRUCTION
 * ============================================================
 */

$spy =
    new GameweekTransferDecisionBacktestingSpy();


$service =
    new GameweekTransferDecisionBacktestingService(
        $spy
    );


gameweekTransferDecisionBacktestingTestResult(
    $service
        instanceof GameweekTransferDecisionBacktestingService,
    'Gameweek transfer decision backtesting service can be constructed.'
);


/*
 * ============================================================
 * BASE HISTORICAL EVIDENCE
 * ============================================================
 */

$preservedGameweekDecision =
    [
        'status' =>
            'success',

        'overall_action' =>
            'Urgent Action',

        'transfer_advice' =>
            [
                'action' =>
                    'Make Transfer',

                'priority' =>
                    'High',

                'score' =>
                    78.0,

                'recommendations' =>
                    []
            ]
    ];


$evidence =
    [
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
                'gameweek_decision' =>
                    $preservedGameweekDecision
            ],

        'player_outcomes' =>
            [
                [
                    'player_id' =>
                        101,

                    'total_points' =>
                        3
                ],

                [
                    'player_id' =>
                        201,

                    'total_points' =>
                        9
                ]
            ]
    ];


$transferBacktestingResult =
    [
        'status' =>
            'Ready',

        'reason' =>
            null,

        'entry_id' =>
            935010005,

        'gameweek_id' =>
            5,

        'transfer_evaluation' =>
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
            ]
    ];


$specialistResult =
    [
        'decision_action' =>
            'Make Transfer',

        'decision_priority' =>
            'High',

        'decision_score' =>
            78.0,

        'outgoing_player_id' =>
            101,

        'incoming_player_id' =>
            201,

        'transfer_points_gain' =>
            6,

        'support_status' =>
            'Supported'
    ];


/*
 * ============================================================
 * SCENARIO A
 * NON-READY HISTORICAL EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Non-Ready Historical Evidence<br>";
echo "============================================<br>";


$spy->callCount = 0;


$unavailableEvidence =
    [
        'status' =>
            'Unavailable',

        'reason' =>
            'Gameweek outcome evidence is not authoritative.',

        'entry_id' =>
            935010005,

        'gameweek_id' =>
            5
    ];


$result =
    $service->evaluate(
        $unavailableEvidence,
        []
    );


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable',
    'Unavailable historical evidence status is propagated.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'reason'
        ]
        ?? null
    )
    ===
    'Gameweek outcome evidence is not authoritative.',
    'Unavailable historical evidence reason is propagated.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    ===
    935010005,
    'Unavailable historical evidence preserves entry identity.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'Unavailable historical evidence preserves gameweek identity.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'decision_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Unavailable historical evidence does not manufacture a decision evaluation.'
);


gameweekTransferDecisionBacktestingTestResult(
    $spy->callCount === 0,
    'Specialist decision evaluator is not called for non-Ready historical evidence.'
);


/*
 * ============================================================
 * SCENARIO B
 * NON-READY TRANSFER BACKTESTING RESULT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Non-Ready Transfer Evaluation<br>";
echo "============================================<br>";


$spy->callCount = 0;


$incompleteTransferResult =
    [
        'status' =>
            'Incomplete',

        'reason' =>
            'Preserved transfer recommendation could not be evaluated.',

        'entry_id' =>
            935010005,

        'gameweek_id' =>
            5,

        'transfer_evaluation' =>
            []
    ];


$result =
    $service->evaluate(
        $evidence,
        $incompleteTransferResult
    );


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Incomplete factual transfer evaluation is propagated.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'reason'
        ]
        ?? null
    )
    ===
    'Preserved transfer recommendation could not be evaluated.',
    'Incomplete factual transfer reason is propagated.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'decision_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'No decision evaluation is manufactured without factual transfer evidence.'
);


gameweekTransferDecisionBacktestingTestResult(
    $spy->callCount === 0,
    'Specialist decision evaluator is not called without Ready factual transfer evidence.'
);


/*
 * ============================================================
 * SCENARIO C
 * READY EVIDENCE REQUIRES RECOMMENDATION SNAPSHOT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Ready Evidence Requires Snapshot<br>";
echo "============================================<br>";


$missingSnapshotEvidence =
    $evidence;


unset(
    $missingSnapshotEvidence[
        'recommendation_snapshot'
    ]
);


$exceptionThrown =
    false;


try {

    $service->evaluate(
        $missingSnapshotEvidence,
        $transferBacktestingResult
    );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekTransferDecisionBacktestingTestResult(
    $exceptionThrown,
    'Ready historical evidence without recommendation snapshot is rejected.'
);


/*
 * ============================================================
 * SCENARIO D
 * MISSING PRESERVED GAMEWEEK DECISION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Missing Preserved Gameweek Decision<br>";
echo "============================================<br>";


$spy->callCount = 0;


$missingDecisionEvidence =
    $evidence;


$missingDecisionEvidence[
    'recommendation_snapshot'
] =
    [];


$result =
    $service->evaluate(
        $missingDecisionEvidence,
        $transferBacktestingResult
    );


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Missing preserved gameweek decision produces Incomplete result.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'reason'
        ]
        ?? null
    )
    ===
    'Preserved gameweek decision evidence is unavailable.',
    'Missing preserved gameweek decision has explicit reason.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'decision_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Missing preserved gameweek decision does not manufacture evaluation.'
);


gameweekTransferDecisionBacktestingTestResult(
    $spy->callCount === 0,
    'Specialist is not called when preserved gameweek decision is unavailable.'
);


/*
 * ============================================================
 * SCENARIO E
 * READY FACTUAL TRANSFER RESULT REQUIRES EVALUATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Ready Transfer Result Requires Evaluation<br>";
echo "============================================<br>";


$invalidTransferResult =
    $transferBacktestingResult;


$invalidTransferResult[
    'transfer_evaluation'
] =
    [];


$exceptionThrown =
    false;


try {

    $service->evaluate(
        $evidence,
        $invalidTransferResult
    );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekTransferDecisionBacktestingTestResult(
    $exceptionThrown,
    'Ready factual transfer result without transfer evaluation is rejected.'
);


/*
 * ============================================================
 * SCENARIO F
 * EXACT SPECIALIST DELEGATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Exact Specialist Delegation<br>";
echo "============================================<br>";


$spy->callCount = 0;


$spy->result =
    $specialistResult;


$result =
    $service->evaluate(
        $evidence,
        $transferBacktestingResult
    );


gameweekTransferDecisionBacktestingTestResult(
    $spy->callCount === 1,
    'Specialist decision evaluator is called exactly once for Ready evidence.'
);


gameweekTransferDecisionBacktestingTestResult(
    $spy->receivedGameweekDecision
    ===
    $preservedGameweekDecision,
    'Preserved gameweek decision is delegated unchanged.'
);


gameweekTransferDecisionBacktestingTestResult(
    $spy->receivedTransferEvaluation
    ===
    $transferBacktestingResult[
        'transfer_evaluation'
    ],
    'Factual transfer evaluation is delegated unchanged.'
);


/*
 * ============================================================
 * SCENARIO G
 * READY RESULT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Ready Decision Evaluation<br>";
echo "============================================<br>";


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Ready',
    'Successful transfer decision backtesting is Ready.'
);


gameweekTransferDecisionBacktestingTestResult(
    array_key_exists(
        'reason',
        $result
    )
    &&
    $result[
        'reason'
    ]
    ===
    null,
    'Successful transfer decision backtesting has no failure reason.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    ===
    935010005,
    'Ready result preserves entry identity.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'Ready result preserves gameweek identity.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'decision_evaluation'
        ]
        ?? null
    )
    ===
    $specialistResult,
    'Ready result preserves specialist decision evaluation unchanged.'
);


/*
 * ============================================================
 * SCENARIO H
 * EMPTY SPECIALIST RESULT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Unevaluable Preserved Decision<br>";
echo "============================================<br>";


$spy->result =
    [];


$result =
    $service->evaluate(
        $evidence,
        $transferBacktestingResult
    );


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Unevaluable preserved transfer decision produces Incomplete result.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'reason'
        ]
        ?? null
    )
    ===
    'Preserved transfer decision could not be evaluated.',
    'Unevaluable preserved transfer decision has explicit reason.'
);


gameweekTransferDecisionBacktestingTestResult(
    (
        $result[
            'decision_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Unevaluable preserved transfer decision does not manufacture result.'
);


/*
 * ============================================================
 * SCENARIO I
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Source Evidence Remains Immutable<br>";
echo "============================================<br>";


$spy->result =
    $specialistResult;


$evidenceBefore =
    $evidence;


$transferResultBefore =
    $transferBacktestingResult;


$service->evaluate(
    $evidence,
    $transferBacktestingResult
);


gameweekTransferDecisionBacktestingTestResult(
    $evidence
    ===
    $evidenceBefore,
    'Historical backtesting evidence is not mutated.'
);


gameweekTransferDecisionBacktestingTestResult(
    $transferBacktestingResult
    ===
    $transferResultBefore,
    'Factual transfer backtesting result is not mutated.'
);


/*
 * ============================================================
 * SCENARIO J
 * SCOPE BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Backtesting Scope Boundary<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $evidence,
        $transferBacktestingResult
    );


gameweekTransferDecisionBacktestingTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Gameweek transfer decision backtesting does not manufacture an accuracy score.'
);


gameweekTransferDecisionBacktestingTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Gameweek transfer decision backtesting does not manufacture an overall score.'
);


gameweekTransferDecisionBacktestingTestResult(
    !array_key_exists(
        'transfer_hit',
        $result
    ),
    'Gameweek transfer decision backtesting does not assume a transfer hit.'
);


gameweekTransferDecisionBacktestingTestResult(
    !array_key_exists(
        'net_points_gain',
        $result
    ),
    'Gameweek transfer decision backtesting does not manufacture hit-adjusted gain.'
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