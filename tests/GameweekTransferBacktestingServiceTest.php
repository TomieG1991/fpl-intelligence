<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Transfer Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekTransferBacktestingTestResult(
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
 * TEST DOUBLE
 * ============================================================
 */

class GameweekTransferBacktestingServiceTestDouble
{
    public int $callCount = 0;

    public array $receivedTransferRecommendations = [];

    public array $receivedPlayerOutcomes = [];

    private array $result;


    public function __construct(
        array $result
    ) {

        $this->result =
            $result;
    }


    public function evaluate(
        array $transferRecommendations,
        array $playerOutcomes
    ): array {

        $this->callCount++;


        $this->receivedTransferRecommendations =
            $transferRecommendations;


        $this->receivedPlayerOutcomes =
            $playerOutcomes;


        return $this->result;
    }
}


/*
 * ============================================================
 * CLASS AVAILABILITY
 * ============================================================
 */

$classExists =
    class_exists(
        'GameweekTransferBacktestingService'
    );


gameweekTransferBacktestingTestResult(
    $classExists,
    'GameweekTransferBacktestingService exists.'
);


if (
    !$classExists
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


/*
 * ============================================================
 * PRESERVED TRANSFER EVIDENCE
 * ============================================================
 */

$transferRecommendations =
    [
        'analysis' =>
            [
                'validation' =>
                    [
                        'is_valid' =>
                            true
                    ]
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
                                        101,

                                    'name' =>
                                        'Outgoing Player'
                                ],

                            'transfer_priority' =>
                                78.0,

                            'priority_label' =>
                                'High',

                            'replacements' =>
                                [
                                    [
                                        'player' =>
                                            [
                                                'player_id' =>
                                                    201,

                                                'name' =>
                                                    'Incoming Player'
                                            ],

                                        'decision_score' =>
                                            76.0,

                                        'rank' =>
                                            1
                                    ]
                                ]
                        ]
                    ]
            ]
    ];


$playerOutcomes =
    [
        [
            'gameweek_id' =>
                5,

            'player_id' =>
                101,

            'fixture_count' =>
                1,

            'total_points' =>
                4,

            'minutes' =>
                90
        ],

        [
            'gameweek_id' =>
                5,

            'player_id' =>
                201,

            'fixture_count' =>
                1,

            'total_points' =>
                9,

            'minutes' =>
                90
        ]
    ];


$specialistResult =
    [
        'outgoing_player_id' =>
            101,

        'incoming_player_id' =>
            201,

        'outgoing_actual_points' =>
            4,

        'incoming_actual_points' =>
            9,

        'transfer_points_gain' =>
            5
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


$nonReadySpecialist =
    new GameweekTransferBacktestingServiceTestDouble(
        $specialistResult
    );


$nonReadyService =
    new GameweekTransferBacktestingService(
        $nonReadySpecialist
    );


$nonReadyEvidence =
    [
        'status' =>
            'Incomplete',

        'reason' =>
            'Historical recommendation snapshot is unavailable.',

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            5,

        'recommendation_snapshot' =>
            [],

        'player_outcomes' =>
            []
    ];


$nonReadyResult =
    $nonReadyService
        ->evaluate(
            $nonReadyEvidence
        );


gameweekTransferBacktestingTestResult(
    (
        $nonReadyResult[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Non-Ready historical evidence status is propagated.'
);


gameweekTransferBacktestingTestResult(
    (
        $nonReadyResult[
            'reason'
        ]
        ?? null
    )
    ===
    'Historical recommendation snapshot is unavailable.',
    'Non-Ready historical evidence reason is propagated.'
);


gameweekTransferBacktestingTestResult(
    (
        $nonReadyResult[
            'entry_id'
        ]
        ?? null
    )
    ===
    2702264,
    'Non-Ready historical evidence preserves entry identity.'
);


gameweekTransferBacktestingTestResult(
    (
        $nonReadyResult[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'Non-Ready historical evidence preserves gameweek identity.'
);


gameweekTransferBacktestingTestResult(
    (
        $nonReadyResult[
            'transfer_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Non-Ready historical evidence does not manufacture transfer evaluation.'
);


gameweekTransferBacktestingTestResult(
    $nonReadySpecialist
        ->callCount
    ===
    0,
    'Transfer specialist is not called for Non-Ready historical evidence.'
);


/*
 * ============================================================
 * SCENARIO B
 * READY EVIDENCE DELEGATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Ready Evidence Delegation<br>";
echo "============================================<br>";


$readySpecialist =
    new GameweekTransferBacktestingServiceTestDouble(
        $specialistResult
    );


$readyService =
    new GameweekTransferBacktestingService(
        $readySpecialist
    );


$readyEvidence =
    [
        'status' =>
            'Ready',

        'reason' =>
            null,

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            5,

        'recommendation_snapshot' =>
            [
                'gameweek' =>
                    1,

                'entry_id' =>
                    2702264,

                'transfer_recommendations' =>
                    $transferRecommendations
            ],

        'player_outcomes' =>
            $playerOutcomes
    ];


$originalReadyEvidence =
    $readyEvidence;


$readyResult =
    $readyService
        ->evaluate(
            $readyEvidence
        );


gameweekTransferBacktestingTestResult(
    $readySpecialist
        ->callCount
    ===
    1,
    'Ready historical evidence calls transfer specialist exactly once.'
);


gameweekTransferBacktestingTestResult(
    $readySpecialist
        ->receivedTransferRecommendations
    ===
    $transferRecommendations,
    'Preserved transfer recommendation evidence is delegated unchanged.'
);


gameweekTransferBacktestingTestResult(
    $readySpecialist
        ->receivedPlayerOutcomes
    ===
    $playerOutcomes,
    'Authoritative player outcomes are delegated unchanged.'
);


/*
 * ============================================================
 * SCENARIO C
 * READY RESULT CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Ready Result Contract<br>";
echo "============================================<br>";


gameweekTransferBacktestingTestResult(
    (
        $readyResult[
            'status'
        ]
        ?? null
    )
    ===
    'Ready',
    'Evaluable transfer evidence produces Ready result.'
);


gameweekTransferBacktestingTestResult(
    array_key_exists(
        'reason',
        $readyResult
    )
    &&
    $readyResult[
        'reason'
    ]
    ===
    null,
    'Ready transfer backtesting has no failure reason.'
);


gameweekTransferBacktestingTestResult(
    (
        $readyResult[
            'entry_id'
        ]
        ?? null
    )
    ===
    2702264,
    'Ready transfer backtesting preserves entry identity.'
);


gameweekTransferBacktestingTestResult(
    (
        $readyResult[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'Ready transfer backtesting preserves gameweek identity.'
);


gameweekTransferBacktestingTestResult(
    (
        $readyResult[
            'transfer_evaluation'
        ]
        ?? null
    )
    ===
    $specialistResult,
    'Ready transfer backtesting preserves specialist evaluation unchanged.'
);


/*
 * ============================================================
 * SCENARIO D
 * READY EVIDENCE REQUIRES SNAPSHOT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Ready Evidence Requires Snapshot<br>";
echo "============================================<br>";


$missingSnapshot =
    $readyEvidence;


$missingSnapshot[
    'recommendation_snapshot'
] =
    [];


$exceptionThrown =
    false;


try {

    $readyService
        ->evaluate(
            $missingSnapshot
        );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekTransferBacktestingTestResult(
    $exceptionThrown,
    'Ready evidence without recommendation snapshot is rejected.'
);


/*
 * ============================================================
 * SCENARIO E
 * NO PRESERVED TRANSFER INTELLIGENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: No Preserved Transfer Intelligence<br>";
echo "============================================<br>";


$missingTransferEvidence =
    $readyEvidence;


$missingTransferEvidence[
    'recommendation_snapshot'
][
    'transfer_recommendations'
] =
    [];


$missingTransferSpecialist =
    new GameweekTransferBacktestingServiceTestDouble(
        $specialistResult
    );


$missingTransferService =
    new GameweekTransferBacktestingService(
        $missingTransferSpecialist
    );


$missingTransferResult =
    $missingTransferService
        ->evaluate(
            $missingTransferEvidence
        );


gameweekTransferBacktestingTestResult(
    (
        $missingTransferResult[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Ready historical evidence without preserved transfer intelligence becomes Incomplete.'
);


gameweekTransferBacktestingTestResult(
    (
        $missingTransferResult[
            'reason'
        ]
        ?? null
    )
    ===
    'Preserved transfer recommendation evidence is unavailable.',
    'Missing preserved transfer intelligence receives explicit reason.'
);


gameweekTransferBacktestingTestResult(
    (
        $missingTransferResult[
            'transfer_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Missing preserved transfer intelligence does not manufacture evaluation.'
);


gameweekTransferBacktestingTestResult(
    $missingTransferSpecialist
        ->callCount
    ===
    0,
    'Transfer specialist is not called when preserved transfer intelligence is absent.'
);


/*
 * ============================================================
 * SCENARIO F
 * READY EVIDENCE REQUIRES PLAYER OUTCOMES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Ready Evidence Requires Player Outcomes<br>";
echo "============================================<br>";


$missingOutcomes =
    $readyEvidence;


$missingOutcomes[
    'player_outcomes'
] =
    [];


$exceptionThrown =
    false;


try {

    $readyService
        ->evaluate(
            $missingOutcomes
        );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekTransferBacktestingTestResult(
    $exceptionThrown,
    'Ready evidence without authoritative player outcomes is rejected.'
);


/*
 * ============================================================
 * SCENARIO G
 * PRESERVED TRANSFER CANNOT BE EVALUATED
 * ============================================================
 *
 * This covers valid historical transfer intelligence which does
 * not contain a complete outgoing -> replacement pair.
 *
 * Examples include:
 *
 * - no legal replacement survived
 * - malformed/incomplete preserved pair
 * - required realised outcome is unavailable
 *
 * The specialist remains responsible for candidate-level
 * validation and returns [] when no factual pair can be compared.
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Preserved Transfer Cannot Be Evaluated<br>";
echo "============================================<br>";


$emptySpecialist =
    new GameweekTransferBacktestingServiceTestDouble(
        []
    );


$emptyService =
    new GameweekTransferBacktestingService(
        $emptySpecialist
    );


$emptyResult =
    $emptyService
        ->evaluate(
            $readyEvidence
        );


gameweekTransferBacktestingTestResult(
    $emptySpecialist
        ->callCount
    ===
    1,
    'Preserved transfer intelligence is delegated before evaluability is determined.'
);


gameweekTransferBacktestingTestResult(
    (
        $emptyResult[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Unevaluable preserved transfer recommendation becomes Incomplete.'
);


gameweekTransferBacktestingTestResult(
    (
        $emptyResult[
            'reason'
        ]
        ?? null
    )
    ===
    'Preserved transfer recommendation could not be evaluated.',
    'Unevaluable preserved transfer recommendation receives explicit reason.'
);


gameweekTransferBacktestingTestResult(
    (
        $emptyResult[
            'transfer_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Unevaluable preserved transfer recommendation does not manufacture result.'
);


/*
 * ============================================================
 * SCENARIO H
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


gameweekTransferBacktestingTestResult(
    $readyEvidence
    ===
    $originalReadyEvidence,
    'Gameweek transfer backtesting does not mutate historical evidence.'
);


/*
 * ============================================================
 * SCENARIO I
 * ORCHESTRATION BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Orchestration Boundary<br>";
echo "============================================<br>";


gameweekTransferBacktestingTestResult(
    !array_key_exists(
        'accuracy_score',
        $readyResult
    ),
    'Gameweek transfer backtesting does not manufacture an accuracy score.'
);


gameweekTransferBacktestingTestResult(
    !array_key_exists(
        'overall_score',
        $readyResult
    ),
    'Gameweek transfer backtesting does not manufacture an overall score.'
);


gameweekTransferBacktestingTestResult(
    !array_key_exists(
        'transfer_hit',
        $readyResult
    ),
    'Gameweek transfer backtesting does not manufacture a transfer hit.'
);


gameweekTransferBacktestingTestResult(
    !array_key_exists(
        'net_points_gain',
        $readyResult
    ),
    'Gameweek transfer backtesting does not manufacture hit-adjusted points.'
);


gameweekTransferBacktestingTestResult(
    !array_key_exists(
        'decision_correct',
        $readyResult
    ),
    'Gameweek transfer backtesting does not judge Make / Consider / Hold.'
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