<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Transfer Backtesting Service Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekTransferBacktestingIntegrationTestResult(
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
 * REAL PRODUCTION SERVICES
 * ============================================================
 */

$transferBacktestingService =
    new TransferBacktestingService();


$gameweekTransferBacktestingService =
    new GameweekTransferBacktestingService(
        $transferBacktestingService
    );


gameweekTransferBacktestingIntegrationTestResult(
    $transferBacktestingService
        instanceof TransferBacktestingService,
    'Real transfer backtesting service can be constructed.'
);


gameweekTransferBacktestingIntegrationTestResult(
    $gameweekTransferBacktestingService
        instanceof GameweekTransferBacktestingService,
    'Real gameweek transfer backtesting service can be constructed.'
);


/*
 * ============================================================
 * PRESERVED TRANSFER RECOMMENDATION
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
                    ],

                'bank' =>
                    1.5
            ],

        'recommendations' =>
            [
                'status' =>
                    'success',

                'bank' =>
                    1.5,

                'priority_limit' =>
                    5,

                'replacement_limit' =>
                    5,

                'players_considered' =>
                    2,

                'recommendations' =>
                    [
                        [
                            'outgoing' =>
                                [
                                    'player_id' =>
                                        101,

                                    'name' =>
                                        'Outgoing Player',

                                    'position' =>
                                        'MID',

                                    'price' =>
                                        7.5
                                ],

                            'transfer_priority' =>
                                78.0,

                            'priority_label' =>
                                'High',

                            'available_budget' =>
                                9.0,

                            'legal_candidate_count' =>
                                2,

                            'replacement_count' =>
                                2,

                            'replacements' =>
                                [
                                    [
                                        'player' =>
                                            [
                                                'player_id' =>
                                                    201,

                                                'name' =>
                                                    'Recommended Replacement',

                                                'position' =>
                                                    'MID',

                                                'price' =>
                                                    8.0
                                            ],

                                        'decision' =>
                                            [
                                                'decision_score' =>
                                                    76.0,

                                                'decision_type' =>
                                                    'Upgrade'
                                            ],

                                        'decision_score' =>
                                            76.0,

                                        'decision_type' =>
                                            'Upgrade',

                                        'budget_after' =>
                                            1.0,

                                        'rank' =>
                                            1
                                    ],

                                    [
                                        'player' =>
                                            [
                                                'player_id' =>
                                                    202,

                                                'name' =>
                                                    'Alternative Replacement',

                                                'position' =>
                                                    'MID',

                                                'price' =>
                                                    7.8
                                            ],

                                        'decision_score' =>
                                            72.0,

                                        'decision_type' =>
                                            'Upgrade',

                                        'budget_after' =>
                                            1.2,

                                        'rank' =>
                                            2
                                    ]
                                ]
                        ],

                        [
                            'outgoing' =>
                                [
                                    'player_id' =>
                                        102,

                                    'name' =>
                                        'Second Outgoing Player',

                                    'position' =>
                                        'DEF',

                                    'price' =>
                                        5.0
                                ],

                            'transfer_priority' =>
                                65.0,

                            'priority_label' =>
                                'Medium',

                            'replacements' =>
                                [
                                    [
                                        'player' =>
                                            [
                                                'player_id' =>
                                                    203,

                                                'name' =>
                                                    'Second Group Replacement',

                                                'position' =>
                                                    'DEF'
                                            ],

                                        'decision_score' =>
                                            68.0,

                                        'rank' =>
                                            1
                                    ]
                                ]
                        ]
                    ]
            ]
    ];


/*
 * ============================================================
 * AUTHORITATIVE-STYLE PLAYER OUTCOMES
 * ============================================================
 */

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
        ],

        [
            'gameweek_id' =>
                5,

            'player_id' =>
                202,

            'fixture_count' =>
                1,

            'total_points' =>
                14,

            'minutes' =>
                90
        ],

        [
            'gameweek_id' =>
                5,

            'player_id' =>
                102,

            'fixture_count' =>
                1,

            'total_points' =>
                12,

            'minutes' =>
                90
        ],

        [
            'gameweek_id' =>
                5,

            'player_id' =>
                203,

            'fixture_count' =>
                1,

            'total_points' =>
                15,

            'minutes' =>
                90
        ]
    ];


/*
 * ============================================================
 * READY HISTORICAL EVIDENCE
 * ============================================================
 */

$evidence =
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


$originalEvidence =
    $evidence;


/*
 * ============================================================
 * SCENARIO A
 * FULL REAL-SERVICE CHAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Full Real-Service Chain<br>";
echo "============================================<br>";


$result =
    $gameweekTransferBacktestingService
        ->evaluate(
            $evidence
        );


gameweekTransferBacktestingIntegrationTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Ready',
    'Real gameweek and transfer services produce Ready evaluation.'
);


gameweekTransferBacktestingIntegrationTestResult(
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
    'Successful real-service evaluation has no failure reason.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    ===
    2702264,
    'Real-service chain preserves entry identity.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'Real-service chain preserves gameweek identity.'
);


/*
 * ============================================================
 * SCENARIO B
 * PRESERVED RECOMMENDATION PAIR
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Preserved Recommendation Pair<br>";
echo "============================================<br>";


$transferEvaluation =
    $result[
        'transfer_evaluation'
    ]
    ?? [];


gameweekTransferBacktestingIntegrationTestResult(
    (
        $transferEvaluation[
            'outgoing_player_id'
        ]
        ?? null
    )
    ===
    101,
    'Real-service chain evaluates highest-priority preserved outgoing player.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $transferEvaluation[
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    201,
    'Real-service chain evaluates preserved rank-one replacement.'
);


/*
 * ============================================================
 * SCENARIO C
 * REALISED TRANSFER COMPARISON
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Realised Transfer Comparison<br>";
echo "============================================<br>";


gameweekTransferBacktestingIntegrationTestResult(
    (
        $transferEvaluation[
            'outgoing_actual_points'
        ]
        ?? null
    )
    ===
    4,
    'Real-service chain preserves outgoing realised points.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $transferEvaluation[
            'incoming_actual_points'
        ]
        ?? null
    )
    ===
    9,
    'Real-service chain preserves incoming realised points.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $transferEvaluation[
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    5,
    'Real-service chain calculates signed realised transfer gain.'
);


/*
 * ============================================================
 * SCENARIO D
 * NO HINDSIGHT RERANKING
 * ============================================================
 *
 * Player 202 scored more than Player 201 but was preserved as
 * replacement rank two.
 *
 * The real service chain must still evaluate Player 201.
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: No Hindsight Reranking<br>";
echo "============================================<br>";


gameweekTransferBacktestingIntegrationTestResult(
    (
        $transferEvaluation[
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    201,
    'Real-service chain retains recommendation-time incoming ranking.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $transferEvaluation[
            'incoming_actual_points'
        ]
        ?? null
    )
    ===
    9,
    'Higher-scoring hindsight replacement does not replace preserved recommendation.'
);


/*
 * ============================================================
 * SCENARIO E
 * NEGATIVE REALISED TRANSFER RETURN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Negative Realised Transfer Return<br>";
echo "============================================<br>";


$negativeEvidence =
    $evidence;


foreach (
    $negativeEvidence[
        'player_outcomes'
    ]
    as &$outcome
) {

    if (
        (
            $outcome[
                'player_id'
            ]
            ?? null
        )
        ===
        101
    ) {

        $outcome[
            'total_points'
        ] =
            11;
    }


    if (
        (
            $outcome[
                'player_id'
            ]
            ?? null
        )
        ===
        201
    ) {

        $outcome[
            'total_points'
        ] =
            2;
    }
}


unset(
    $outcome
);


$negativeResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $negativeEvidence
        );


gameweekTransferBacktestingIntegrationTestResult(
    (
        $negativeResult[
            'transfer_evaluation'
        ][
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    -9,
    'Real-service chain preserves negative transfer return.'
);


/*
 * ============================================================
 * SCENARIO F
 * NO PRESERVED TRANSFER INTELLIGENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: No Preserved Transfer Intelligence<br>";
echo "============================================<br>";


$noTransferEvidence =
    $evidence;


$noTransferEvidence[
    'recommendation_snapshot'
][
    'transfer_recommendations'
] =
    [];


$noTransferResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $noTransferEvidence
        );


gameweekTransferBacktestingIntegrationTestResult(
    (
        $noTransferResult[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Missing preserved transfer intelligence remains Incomplete through real services.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $noTransferResult[
            'reason'
        ]
        ?? null
    )
    ===
    'Preserved transfer recommendation evidence is unavailable.',
    'Missing transfer intelligence retains explicit historical-evidence reason.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $noTransferResult[
            'transfer_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Missing transfer intelligence does not manufacture transfer evaluation.'
);


/*
 * ============================================================
 * SCENARIO G
 * PRESERVED TRANSFER WITHOUT REPLACEMENT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Preserved Transfer Without Replacement<br>";
echo "============================================<br>";


$noReplacementEvidence =
    $evidence;


$noReplacementEvidence[
    'recommendation_snapshot'
][
    'transfer_recommendations'
][
    'recommendations'
][
    'recommendations'
][
    0
][
    'replacements'
] =
    [];


$noReplacementResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $noReplacementEvidence
        );


gameweekTransferBacktestingIntegrationTestResult(
    (
        $noReplacementResult[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Preserved outgoing recommendation without replacement remains Incomplete.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $noReplacementResult[
            'reason'
        ]
        ?? null
    )
    ===
    'Preserved transfer recommendation could not be evaluated.',
    'Unevaluable preserved transfer pair receives specialist-evaluation reason.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $noReplacementResult[
            'transfer_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Unevaluable preserved transfer pair does not manufacture result.'
);


/*
 * ============================================================
 * SCENARIO H
 * NON-READY EVIDENCE PROPAGATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Non-Ready Evidence Propagation<br>";
echo "============================================<br>";


$incompleteEvidence =
    [
        'status' =>
            'Incomplete',

        'reason' =>
            'Historical recommendation snapshot is unavailable.',

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            5
    ];


$incompleteResult =
    $gameweekTransferBacktestingService
        ->evaluate(
            $incompleteEvidence
        );


gameweekTransferBacktestingIntegrationTestResult(
    (
        $incompleteResult[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Real-service chain propagates Incomplete evidence status.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $incompleteResult[
            'reason'
        ]
        ?? null
    )
    ===
    'Historical recommendation snapshot is unavailable.',
    'Real-service chain propagates Incomplete evidence reason.'
);


gameweekTransferBacktestingIntegrationTestResult(
    (
        $incompleteResult[
            'transfer_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Non-Ready evidence does not produce transfer evaluation.'
);


/*
 * ============================================================
 * SCENARIO I
 * SOURCE EVIDENCE IMMUTABILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Source Evidence Immutability<br>";
echo "============================================<br>";


gameweekTransferBacktestingIntegrationTestResult(
    $evidence
    ===
    $originalEvidence,
    'Full real-service chain does not mutate historical evidence.'
);


/*
 * ============================================================
 * SCENARIO J
 * BACKTESTING SCOPE BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Backtesting Scope Boundary<br>";
echo "============================================<br>";


gameweekTransferBacktestingIntegrationTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Real-service orchestration does not manufacture an accuracy score.'
);


gameweekTransferBacktestingIntegrationTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Real-service orchestration does not manufacture an overall score.'
);


gameweekTransferBacktestingIntegrationTestResult(
    !array_key_exists(
        'transfer_hit',
        $result
    ),
    'Real-service orchestration does not manufacture a transfer hit.'
);


gameweekTransferBacktestingIntegrationTestResult(
    !array_key_exists(
        'net_points_gain',
        $result
    ),
    'Real-service orchestration does not manufacture hit-adjusted points.'
);


gameweekTransferBacktestingIntegrationTestResult(
    !array_key_exists(
        'decision_correct',
        $result
    ),
    'Real-service orchestration does not judge Make / Consider / Hold.'
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