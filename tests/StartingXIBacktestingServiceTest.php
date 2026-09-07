<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Starting XI Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function startingXIBacktestingTestResult(
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
 * SERVICE AVAILABILITY
 * ============================================================
 */

startingXIBacktestingTestResult(
    class_exists(
        'StartingXIBacktestingService'
    ),
    'StartingXIBacktestingService exists.'
);


if (
    !class_exists(
        'StartingXIBacktestingService'
    )
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
    new StartingXIBacktestingService();


startingXIBacktestingTestResult(
    $service
        instanceof StartingXIBacktestingService,
    'StartingXIBacktestingService can be constructed.'
);


/*
 * ============================================================
 * STANDARD STARTING XI
 * ============================================================
 */

$startingXI = [

    [
        'player_id' => 101,
        'name' => 'Starter One',
        'position' => 'GK'
    ],

    [
        'player_id' => 102,
        'name' => 'Starter Two',
        'position' => 'DEF'
    ],

    [
        'player_id' => 103,
        'name' => 'Starter Three',
        'position' => 'DEF'
    ],

    [
        'player_id' => 104,
        'name' => 'Starter Four',
        'position' => 'DEF'
    ],

    [
        'player_id' => 105,
        'name' => 'Starter Five',
        'position' => 'MID'
    ],

    [
        'player_id' => 106,
        'name' => 'Starter Six',
        'position' => 'MID'
    ],

    [
        'player_id' => 107,
        'name' => 'Starter Seven',
        'position' => 'MID'
    ],

    [
        'player_id' => 108,
        'name' => 'Starter Eight',
        'position' => 'MID'
    ],

    [
        'player_id' => 109,
        'name' => 'Starter Nine',
        'position' => 'FWD'
    ],

    [
        'player_id' => 110,
        'name' => 'Starter Ten',
        'position' => 'FWD'
    ],

    [
        'player_id' => 111,
        'name' => 'Starter Eleven',
        'position' => 'FWD'
    ]
];


$bench = [

    [
        'player_id' => 201,
        'name' => 'Bench One',
        'position' => 'GK'
    ],

    [
        'player_id' => 202,
        'name' => 'Bench Two',
        'position' => 'DEF'
    ],

    [
        'player_id' => 203,
        'name' => 'Bench Three',
        'position' => 'MID'
    ],

    [
        'player_id' => 204,
        'name' => 'Bench Four',
        'position' => 'FWD'
    ]
];


/*
 * ============================================================
 * STANDARD OUTCOMES
 * ============================================================
 */

$playerOutcomes = [

    [
        'gameweek_id' => 5,
        'player_id' => 101,
        'total_points' => 2,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 102,
        'total_points' => 6,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 103,
        'total_points' => 1,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 104,
        'total_points' => 5,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 105,
        'total_points' => 3,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 106,
        'total_points' => 8,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 107,
        'total_points' => 2,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 108,
        'total_points' => 10,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 109,
        'total_points' => 2,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 110,
        'total_points' => 5,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 111,
        'total_points' => 9,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 201,
        'total_points' => 3,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 202,
        'total_points' => 7,
        'minutes' => 90
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 203,
        'total_points' => 1,
        'minutes' => 20
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 204,
        'total_points' => 6,
        'minutes' => 90
    ]
];


/*
 * ============================================================
 * SCENARIO A
 * EMPTY STARTING XI
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Empty Starting XI<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        [],
        $bench,
        $playerOutcomes
    );


startingXIBacktestingTestResult(
    $result === [],
    'Empty Starting XI returns no evaluation.'
);


/*
 * ============================================================
 * SCENARIO B
 * EMPTY OUTCOMES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Empty Outcome Evidence<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $startingXI,
        $bench,
        []
    );


startingXIBacktestingTestResult(
    $result === [],
    'Empty outcome evidence returns no evaluation.'
);


/*
 * ============================================================
 * SCENARIO C
 * COMPLETE STARTING XI EVALUATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Complete Starting XI Evaluation<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $startingXI,
        $bench,
        $playerOutcomes
    );


startingXIBacktestingTestResult(
    (
        $result[
            'starting_xi'
        ]
        ?? null
    )
    !== null,
    'Complete evidence produces Starting XI evaluation.'
);


startingXIBacktestingTestResult(
    count(
        $result[
            'starting_xi'
        ]
        ?? []
    )
    === 11,
    'All eleven recommended starters are evaluated.'
);


startingXIBacktestingTestResult(
    count(
        $result[
            'bench'
        ]
        ?? []
    )
    === 4,
    'All four recommended bench players are evaluated.'
);


/*
 * ============================================================
 * SCENARIO D
 * PLAYER OUTCOMES MATCH BY LOCAL PLAYER ID
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Local Player Identity Matching<br>";
echo "============================================<br>";


startingXIBacktestingTestResult(
    (
        $result[
            'starting_xi'
        ][
            0
        ][
            'player_id'
        ]
        ?? null
    )
    === 101,
    'Starter identity is preserved.'
);


startingXIBacktestingTestResult(
    (
        $result[
            'starting_xi'
        ][
            0
        ][
            'actual_points'
        ]
        ?? null
    )
    === 2,
    'Starter is matched to realised points by local player ID.'
);


startingXIBacktestingTestResult(
    (
        $result[
            'bench'
        ][
            1
        ][
            'player_id'
        ]
        ?? null
    )
    === 202,
    'Bench player identity is preserved.'
);


startingXIBacktestingTestResult(
    (
        $result[
            'bench'
        ][
            1
        ][
            'actual_points'
        ]
        ?? null
    )
    === 7,
    'Bench player is matched to realised points by local player ID.'
);


/*
 * ============================================================
 * SCENARIO E
 * REALISED POINT TOTALS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Realised Starting XI And Bench Totals<br>";
echo "============================================<br>";


$expectedStartingXIPoints =
    2
    + 6
    + 1
    + 5
    + 3
    + 8
    + 2
    + 10
    + 2
    + 5
    + 9;


$expectedBenchPoints =
    3
    + 7
    + 1
    + 6;


startingXIBacktestingTestResult(
    (
        $result[
            'starting_xi_points'
        ]
        ?? null
    )
    ===
    $expectedStartingXIPoints,
    'Realised Starting XI points are summed correctly.'
);


startingXIBacktestingTestResult(
    (
        $result[
            'bench_points'
        ]
        ?? null
    )
    ===
    $expectedBenchPoints,
    'Realised bench points are summed correctly.'
);


/*
 * ============================================================
 * SCENARIO F
 * BENCH PLAYER OUTSCORES STARTER
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Bench Outperforms Starter<br>";
echo "============================================<br>";


$opportunities =
    $result[
        'bench_outperformance'
    ]
    ?? [];


startingXIBacktestingTestResult(
    !empty(
        $opportunities
    ),
    'Bench outperformance evidence is recorded when a bench player outscores a starter.'
);


$foundBench202Starter103 =
    false;


foreach (
    $opportunities
    as $opportunity
) {

    if (
        (
            $opportunity[
                'bench_player_id'
            ]
            ?? null
        )
        === 202
        &&
        (
            $opportunity[
                'starter_player_id'
            ]
            ?? null
        )
        === 103
    ) {

        $foundBench202Starter103 =
            true;


        startingXIBacktestingTestResult(
            (
                $opportunity[
                    'bench_actual_points'
                ]
                ?? null
            )
            === 7,
            'Bench outperformance evidence preserves bench realised points.'
        );


        startingXIBacktestingTestResult(
            (
                $opportunity[
                    'starter_actual_points'
                ]
                ?? null
            )
            === 1,
            'Bench outperformance evidence preserves starter realised points.'
        );


        startingXIBacktestingTestResult(
            (
                $opportunity[
                    'points_difference'
                ]
                ?? null
            )
            === 6,
            'Bench outperformance evidence preserves factual points difference.'
        );


        break;
    }
}


startingXIBacktestingTestResult(
    $foundBench202Starter103,
    'Specific bench-versus-starter outperformance is discoverable.'
);


/*
 * ============================================================
 * SCENARIO G
 * BENCH PLAYER DOES NOT OUTSCORE STRONGER STARTER
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: No False Bench Outperformance<br>";
echo "============================================<br>";


$foundBench203Starter108 =
    false;


foreach (
    $opportunities
    as $opportunity
) {

    if (
        (
            $opportunity[
                'bench_player_id'
            ]
            ?? null
        )
        === 203
        &&
        (
            $opportunity[
                'starter_player_id'
            ]
            ?? null
        )
        === 108
    ) {

        $foundBench203Starter108 =
            true;

        break;
    }
}


startingXIBacktestingTestResult(
    !$foundBench203Starter108,
    'Bench player is not marked as outperforming a starter who scored more.'
);


/*
 * ============================================================
 * SCENARIO H
 * EQUAL POINTS ARE NOT OUTPERFORMANCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Equal Realised Points<br>";
echo "============================================<br>";


$equalBench = [

    [
        'player_id' => 301,
        'name' => 'Equal Bench Player',
        'position' => 'MID'
    ]
];


$equalOutcomes =
    $playerOutcomes;


$equalOutcomes[] = [

    'gameweek_id' => 5,
    'player_id' => 301,
    'total_points' => 2,
    'minutes' => 90
];


$resultEqual =
    $service->evaluate(
        $startingXI,
        $equalBench,
        $equalOutcomes
    );


$equalMarkedAsOutperformance =
    false;


foreach (
    $resultEqual[
        'bench_outperformance'
    ]
    ?? []
    as $opportunity
) {

    if (
        (
            $opportunity[
                'bench_player_id'
            ]
            ?? null
        )
        === 301
        &&
        (
            $opportunity[
                'starter_player_id'
            ]
            ?? null
        )
        === 101
    ) {

        $equalMarkedAsOutperformance =
            true;

        break;
    }
}


startingXIBacktestingTestResult(
    !$equalMarkedAsOutperformance,
    'Equal realised points are not treated as bench outperformance.'
);


/*
 * ============================================================
 * SCENARIO I
 * ZERO-MINUTE PLAYER REMAINS FACTUAL EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Zero-Minute Realised Outcome<br>";
echo "============================================<br>";


$zeroMinuteOutcomes =
    $playerOutcomes;


foreach (
    $zeroMinuteOutcomes
    as $index => $outcome
) {

    if (
        (
            $outcome[
                'player_id'
            ]
            ?? null
        )
        === 105
    ) {

        $zeroMinuteOutcomes[
            $index
        ][
            'total_points'
        ] =
            0;


        $zeroMinuteOutcomes[
            $index
        ][
            'minutes'
        ] =
            0;
    }
}


$resultZeroMinute =
    $service->evaluate(
        $startingXI,
        $bench,
        $zeroMinuteOutcomes
    );


$zeroMinuteStarter =
    null;


foreach (
    $resultZeroMinute[
        'starting_xi'
    ]
    ?? []
    as $starter
) {

    if (
        (
            $starter[
                'player_id'
            ]
            ?? null
        )
        === 105
    ) {

        $zeroMinuteStarter =
            $starter;

        break;
    }
}


startingXIBacktestingTestResult(
    $zeroMinuteStarter
        !==
        null,
    'Zero-minute recommended starter remains in factual evaluation.'
);


startingXIBacktestingTestResult(
    (
        $zeroMinuteStarter[
            'actual_points'
        ]
        ?? null
    )
    === 0,
    'Zero realised points are preserved for recommended starter.'
);


startingXIBacktestingTestResult(
    (
        $zeroMinuteStarter[
            'actual_minutes'
        ]
        ?? null
    )
    === 0,
    'Zero realised minutes are preserved for recommended starter.'
);


/*
 * ============================================================
 * SCENARIO J
 * NEGATIVE FPL POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Negative Realised Points<br>";
echo "============================================<br>";


$negativeOutcomes =
    $playerOutcomes;


foreach (
    $negativeOutcomes
    as $index => $outcome
) {

    if (
        (
            $outcome[
                'player_id'
            ]
            ?? null
        )
        === 102
    ) {

        $negativeOutcomes[
            $index
        ][
            'total_points'
        ] =
            -1;
    }
}


$resultNegative =
    $service->evaluate(
        $startingXI,
        $bench,
        $negativeOutcomes
    );


$negativeStarter =
    null;


foreach (
    $resultNegative[
        'starting_xi'
    ]
    ?? []
    as $starter
) {

    if (
        (
            $starter[
                'player_id'
            ]
            ?? null
        )
        === 102
    ) {

        $negativeStarter =
            $starter;

        break;
    }
}


startingXIBacktestingTestResult(
    (
        $negativeStarter[
            'actual_points'
        ]
        ?? null
    )
    === -1,
    'Negative realised FPL points are preserved.'
);


/*
 * ============================================================
 * SCENARIO K
 * MISSING OUTCOME IS NOT MANUFACTURED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Missing Player Outcome<br>";
echo "============================================<br>";


$missingOutcomeEvidence =
    array_values(
        array_filter(
            $playerOutcomes,
            static function (
                array $outcome
            ): bool {

                return
                    (
                        $outcome[
                            'player_id'
                        ]
                        ?? null
                    )
                    !== 111;
            }
        )
    );


$resultMissing =
    $service->evaluate(
        $startingXI,
        $bench,
        $missingOutcomeEvidence
    );


$starter111Found =
    false;


foreach (
    $resultMissing[
        'starting_xi'
    ]
    ?? []
    as $starter
) {

    if (
        (
            $starter[
                'player_id'
            ]
            ?? null
        )
        === 111
    ) {

        $starter111Found =
            true;

        break;
    }
}


startingXIBacktestingTestResult(
    !$starter111Found,
    'Recommended starter without authoritative outcome is not evaluated.'
);


/*
 * ============================================================
 * SCENARIO L
 * INVALID PLAYER ROWS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario L: Invalid Player Rows<br>";
echo "============================================<br>";


$resultInvalid =
    $service->evaluate(
        [
            'invalid',
            [
                'player_id' => 0
            ],
            [
                'player_id' => 101
            ]
        ],
        [
            'invalid',
            [
                'player_id' => -1
            ],
            [
                'player_id' => 202
            ]
        ],
        $playerOutcomes
    );


startingXIBacktestingTestResult(
    count(
        $resultInvalid[
            'starting_xi'
        ]
        ?? []
    )
    === 1,
    'Invalid Starting XI rows are ignored rather than manufactured.'
);


startingXIBacktestingTestResult(
    count(
        $resultInvalid[
            'bench'
        ]
        ?? []
    )
    === 1,
    'Invalid bench rows are ignored rather than manufactured.'
);


/*
 * ============================================================
 * SCENARIO M
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario M: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


$sourceStartingXI =
    $startingXI;


$sourceBench =
    $bench;


$sourceOutcomes =
    $playerOutcomes;


$originalStartingXI =
    $sourceStartingXI;


$originalBench =
    $sourceBench;


$originalOutcomes =
    $sourceOutcomes;


$service->evaluate(
    $sourceStartingXI,
    $sourceBench,
    $sourceOutcomes
);


startingXIBacktestingTestResult(
    $sourceStartingXI
        ===
        $originalStartingXI,
    'Historical Starting XI evidence is not mutated.'
);


startingXIBacktestingTestResult(
    $sourceBench
        ===
        $originalBench,
    'Historical bench evidence is not mutated.'
);


startingXIBacktestingTestResult(
    $sourceOutcomes
        ===
        $originalOutcomes,
    'Actual outcome evidence is not mutated.'
);


/*
 * ============================================================
 * SCENARIO N
 * FACTUAL EVALUATION ONLY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario N: Starting XI Evaluation Only<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $startingXI,
        $bench,
        $playerOutcomes
    );


startingXIBacktestingTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Starting XI evaluator does not create an accuracy score.'
);


startingXIBacktestingTestResult(
    !array_key_exists(
        'optimal_starting_xi',
        $result
    ),
    'Starting XI evaluator does not solve a synthetic optimal XI.'
);


startingXIBacktestingTestResult(
    !array_key_exists(
        'automatic_substitutions',
        $result
    ),
    'Starting XI evaluator does not simulate automatic substitutions.'
);


startingXIBacktestingTestResult(
    !array_key_exists(
        'captain_result',
        $result
    ),
    'Starting XI evaluator does not evaluate captain recommendation.'
);


startingXIBacktestingTestResult(
    !array_key_exists(
        'transfer_result',
        $result
    ),
    'Starting XI evaluator does not evaluate transfer recommendation.'
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


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}