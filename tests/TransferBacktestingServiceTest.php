<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function transferBacktestingTestResult(
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
 * CLASS AVAILABILITY
 * ============================================================
 */

$classExists =
    class_exists(
        'TransferBacktestingService'
    );


transferBacktestingTestResult(
    $classExists,
    'TransferBacktestingService exists.'
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


$service =
    new TransferBacktestingService();


/*
 * ============================================================
 * PRESERVED PRODUCTION-SHAPED TRANSFER INTELLIGENCE
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
                                10,

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
                                                    'Top Replacement',

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
                                                    'Second Replacement',

                                                'position' =>
                                                    'MID',

                                                'price' =>
                                                    7.8
                                            ],

                                        'decision_score' =>
                                            73.0,

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
                                                    'Other Replacement',

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
 * AUTHORITATIVE PLAYER OUTCOMES
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


$originalTransferRecommendations =
    $transferRecommendations;


$originalPlayerOutcomes =
    $playerOutcomes;


/*
 * ============================================================
 * SCENARIO A
 * TOP PRESERVED TRANSFER PAIR
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Top Preserved Transfer Pair<br>";
echo "============================================<br>";


$result =
    $service
        ->evaluate(
            $transferRecommendations,
            $playerOutcomes
        );


transferBacktestingTestResult(
    !empty(
        $result
    ),
    'Complete preserved transfer evidence produces an evaluation.'
);


transferBacktestingTestResult(
    (
        $result[
            'outgoing_player_id'
        ]
        ?? null
    )
    ===
    101,
    'Highest-priority preserved outgoing player is evaluated.'
);


transferBacktestingTestResult(
    (
        $result[
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    201,
    'Top-ranked preserved replacement is evaluated.'
);


/*
 * ============================================================
 * SCENARIO B
 * REALISED POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Realised Transfer Points<br>";
echo "============================================<br>";


transferBacktestingTestResult(
    (
        $result[
            'outgoing_actual_points'
        ]
        ?? null
    )
    ===
    4,
    'Outgoing player realised points are preserved.'
);


transferBacktestingTestResult(
    (
        $result[
            'incoming_actual_points'
        ]
        ?? null
    )
    ===
    9,
    'Incoming player realised points are preserved.'
);


transferBacktestingTestResult(
    (
        $result[
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    5,
    'Transfer points gain equals incoming points minus outgoing points.'
);


/*
 * ============================================================
 * SCENARIO C
 * ONLY TOP-RANKED PRESERVED REPLACEMENT IS THE RECOMMENDED PAIR
 * ============================================================
 *
 * Player 202 actually scores more than Player 201.
 *
 * The service must not retrospectively choose Player 202 merely
 * because that player produced the stronger realised return.
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Preserve Recommendation-Time Ranking<br>";
echo "============================================<br>";


transferBacktestingTestResult(
    (
        $result[
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    201,
    'Backtesting does not replace the preserved top recommendation with the best hindsight scorer.'
);


transferBacktestingTestResult(
    (
        $result[
            'incoming_actual_points'
        ]
        ?? null
    )
    ===
    9,
    'Backtesting evaluates the preserved rank-one incoming player rather than a hindsight alternative.'
);


/*
 * ============================================================
 * SCENARIO D
 * ONLY HIGHEST-PRIORITY OUTGOING GROUP IS THE TOP TRANSFER
 * ============================================================
 *
 * The second outgoing group happens to contain stronger realised
 * outcomes. Backtesting must still preserve recommendation order.
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Preserve Outgoing Priority Order<br>";
echo "============================================<br>";


transferBacktestingTestResult(
    (
        $result[
            'outgoing_player_id'
        ]
        ?? null
    )
    ===
    101,
    'Backtesting evaluates the preserved highest-priority outgoing group.'
);


transferBacktestingTestResult(
    (
        $result[
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    201,
    'Backtesting does not retrospectively choose another outgoing recommendation group.'
);


/*
 * ============================================================
 * SCENARIO E
 * NEGATIVE TRANSFER RETURN IS VALID
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Negative Transfer Return<br>";
echo "============================================<br>";


$negativeOutcomes =
    $playerOutcomes;


foreach (
    $negativeOutcomes
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
            10;
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
            3;
    }
}


unset(
    $outcome
);


$negativeResult =
    $service
        ->evaluate(
            $transferRecommendations,
            $negativeOutcomes
        );


transferBacktestingTestResult(
    (
        $negativeResult[
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    -7,
    'A transfer that underperforms the outgoing player retains a negative realised points gain.'
);


/*
 * ============================================================
 * SCENARIO F
 * ZERO-MINUTE / NEGATIVE FPL OUTCOMES REMAIN VALID
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Valid Outcome Boundaries<br>";
echo "============================================<br>";


$boundaryOutcomes =
    $playerOutcomes;


foreach (
    $boundaryOutcomes
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
            -1;

        $outcome[
            'minutes'
        ] =
            90;
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
            0;

        $outcome[
            'minutes'
        ] =
            0;
    }
}


unset(
    $outcome
);


$boundaryResult =
    $service
        ->evaluate(
            $transferRecommendations,
            $boundaryOutcomes
        );


transferBacktestingTestResult(
    (
        $boundaryResult[
            'outgoing_actual_points'
        ]
        ?? null
    )
    ===
    -1,
    'Negative outgoing FPL points remain valid realised evidence.'
);


transferBacktestingTestResult(
    (
        $boundaryResult[
            'incoming_actual_points'
        ]
        ?? null
    )
    ===
    0,
    'Zero-point zero-minute incoming outcome remains valid realised evidence.'
);


transferBacktestingTestResult(
    (
        $boundaryResult[
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    1,
    'Boundary outcome comparison remains arithmetic rather than classification-based.'
);


/*
 * ============================================================
 * SCENARIO G
 * REQUIRED PRESERVED STRUCTURE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Required Preserved Structure<br>";
echo "============================================<br>";


transferBacktestingTestResult(
    $service
        ->evaluate(
            [],
            $playerOutcomes
        )
    ===
    [],
    'Missing preserved Transfer Intelligence evidence cannot be evaluated.'
);


$missingOptimizerResult =
    $transferRecommendations;


unset(
    $missingOptimizerResult[
        'recommendations'
    ]
);


transferBacktestingTestResult(
    $service
        ->evaluate(
            $missingOptimizerResult,
            $playerOutcomes
        )
    ===
    [],
    'Missing preserved optimizer result cannot be evaluated.'
);


$missingRecommendationGroups =
    $transferRecommendations;


$missingRecommendationGroups[
    'recommendations'
][
    'recommendations'
] =
    [];


transferBacktestingTestResult(
    $service
        ->evaluate(
            $missingRecommendationGroups,
            $playerOutcomes
        )
    ===
    [],
    'Missing preserved outgoing recommendation groups cannot be evaluated.'
);


$missingReplacements =
    $transferRecommendations;


$missingReplacements[
    'recommendations'
][
    'recommendations'
][
    0
][
    'replacements'
] =
    [];


transferBacktestingTestResult(
    $service
        ->evaluate(
            $missingReplacements,
            $playerOutcomes
        )
    ===
    [],
    'Top outgoing recommendation without a preserved replacement cannot be evaluated.'
);


/*
 * ============================================================
 * SCENARIO H
 * PLAYER IDENTITY VALIDATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Player Identity Validation<br>";
echo "============================================<br>";


$invalidOutgoing =
    $transferRecommendations;


$invalidOutgoing[
    'recommendations'
][
    'recommendations'
][
    0
][
    'outgoing'
][
    'player_id'
] =
    0;


transferBacktestingTestResult(
    $service
        ->evaluate(
            $invalidOutgoing,
            $playerOutcomes
        )
    ===
    [],
    'Invalid outgoing player identity cannot be evaluated.'
);


$invalidIncoming =
    $transferRecommendations;


$invalidIncoming[
    'recommendations'
][
    'recommendations'
][
    0
][
    'replacements'
][
    0
][
    'player'
][
    'player_id'
] =
    'invalid';


transferBacktestingTestResult(
    $service
        ->evaluate(
            $invalidIncoming,
            $playerOutcomes
        )
    ===
    [],
    'Invalid incoming player identity cannot be evaluated.'
);


$samePlayer =
    $transferRecommendations;


$samePlayer[
    'recommendations'
][
    'recommendations'
][
    0
][
    'replacements'
][
    0
][
    'player'
][
    'player_id'
] =
    101;


transferBacktestingTestResult(
    $service
        ->evaluate(
            $samePlayer,
            $playerOutcomes
        )
    ===
    [],
    'Outgoing and incoming player identities must be different.'
);


/*
 * ============================================================
 * SCENARIO I
 * REQUIRED OUTCOME EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Required Outcome Evidence<br>";
echo "============================================<br>";


transferBacktestingTestResult(
    $service
        ->evaluate(
            $transferRecommendations,
            []
        )
    ===
    [],
    'Missing completed-gameweek player outcomes cannot be evaluated.'
);


$missingOutgoingOutcome =
    array_values(
        array_filter(
            $playerOutcomes,
            static function (
                array $outcome
            ): bool {

                return (
                    $outcome[
                        'player_id'
                    ]
                    ?? null
                )
                !==
                101;
            }
        )
    );


transferBacktestingTestResult(
    $service
        ->evaluate(
            $transferRecommendations,
            $missingOutgoingOutcome
        )
    ===
    [],
    'Missing outgoing-player outcome evidence cannot be evaluated.'
);


$missingIncomingOutcome =
    array_values(
        array_filter(
            $playerOutcomes,
            static function (
                array $outcome
            ): bool {

                return (
                    $outcome[
                        'player_id'
                    ]
                    ?? null
                )
                !==
                201;
            }
        )
    );


transferBacktestingTestResult(
    $service
        ->evaluate(
            $transferRecommendations,
            $missingIncomingOutcome
        )
    ===
    [],
    'Missing incoming-player outcome evidence cannot be evaluated.'
);


/*
 * ============================================================
 * SCENARIO J
 * NUMERIC REALISED POINTS REQUIRED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Numeric Realised Points Required<br>";
echo "============================================<br>";


$invalidPoints =
    $playerOutcomes;


foreach (
    $invalidPoints
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
        201
    ) {

        $outcome[
            'total_points'
        ] =
            null;
    }
}


unset(
    $outcome
);


transferBacktestingTestResult(
    $service
        ->evaluate(
            $transferRecommendations,
            $invalidPoints
        )
    ===
    [],
    'Non-numeric realised points cannot be evaluated.'
);


/*
 * ============================================================
 * SCENARIO K
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


transferBacktestingTestResult(
    $transferRecommendations
    ===
    $originalTransferRecommendations,
    'Preserved Transfer Intelligence evidence is not mutated.'
);


transferBacktestingTestResult(
    $playerOutcomes
    ===
    $originalPlayerOutcomes,
    'Authoritative player outcomes are not mutated.'
);


/*
 * ============================================================
 * SCENARIO L
 * BACKTESTING BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario L: Transfer Backtesting Boundary<br>";
echo "============================================<br>";


transferBacktestingTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Transfer backtesting does not manufacture an accuracy score.'
);


transferBacktestingTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Transfer backtesting does not manufacture an overall score.'
);


transferBacktestingTestResult(
    !array_key_exists(
        'transfer_hit',
        $result
    ),
    'Transfer backtesting does not manufacture a transfer hit.'
);


transferBacktestingTestResult(
    !array_key_exists(
        'net_points_gain',
        $result
    ),
    'Transfer backtesting does not manufacture hit-adjusted net points.'
);


transferBacktestingTestResult(
    !array_key_exists(
        'decision_correct',
        $result
    ),
    'Transfer candidate backtesting does not yet judge whether Make / Consider / Hold was correct.'
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