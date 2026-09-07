<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Starting XI Backtesting Service Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function startingXIBacktestingIntegrationTestResult(
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
 * REAL DATABASE AND PRODUCTION SERVICES
 * ============================================================
 */

$database =
    new Database();


$pdo =
    $database->getConnection();


startingXIBacktestingIntegrationTestResult(
    $pdo instanceof PDO,
    'Real database connection is available.'
);


$playerFixtureHistoryRepository =
    new PlayerFixtureHistoryRepository(
        $pdo
    );


$playerGameweekOutcomeService =
    new PlayerGameweekOutcomeService(
        $playerFixtureHistoryRepository
    );


$startingXIBacktestingService =
    new StartingXIBacktestingService();


startingXIBacktestingIntegrationTestResult(
    $playerGameweekOutcomeService
        instanceof PlayerGameweekOutcomeService,
    'Real player gameweek outcome service can be constructed.'
);


startingXIBacktestingIntegrationTestResult(
    $startingXIBacktestingService
        instanceof StartingXIBacktestingService,
    'Real Starting XI backtesting service can be constructed.'
);


/*
 * ============================================================
 * DISCOVER REAL COMPLETED GAMEWEEK
 * ============================================================
 */

$statement =
    $pdo->query(
        "
        SELECT
            g.id,
            g.fpl_gameweek_id,
            g.name
        FROM
            gameweeks g
        WHERE
            g.finished = 1
            AND
            g.data_checked = 1
            AND EXISTS (
                SELECT
                    1
                FROM
                    player_fixture_history pfh
                WHERE
                    pfh.gameweek_id = g.id
            )
        ORDER BY
            g.id ASC
        LIMIT 1
        "
    );


$gameweek =
    $statement->fetch(
        PDO::FETCH_ASSOC
    );


startingXIBacktestingIntegrationTestResult(
    is_array(
        $gameweek
    ),
    'Authoritative completed gameweek with real outcome evidence is available.'
);


if (
    !is_array(
        $gameweek
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


$gameweekId =
    (int) $gameweek[
        'id'
    ];


/*
 * ============================================================
 * LOAD AUTHORITATIVE OUTCOMES
 * ============================================================
 */

$playerOutcomes =
    $playerGameweekOutcomeService
        ->getByGameweekId(
            $gameweekId
        );


startingXIBacktestingIntegrationTestResult(
    count(
        $playerOutcomes
    )
    >= 15,
    'Completed gameweek contains enough real player outcomes for a 15-player squad.'
);


if (
    count(
        $playerOutcomes
    )
    <
    15
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
 * BUILD PRESERVED RECOMMENDATION EVIDENCE
 * ============================================================
 *
 * We use 15 genuine players from the completed gameweek.
 *
 * The first 11 represent the preserved recommended XI.
 * The remaining 4 represent the preserved bench.
 *
 * We are NOT claiming this was a genuine historical
 * recommendation. This test proves compatibility between
 * genuine outcome evidence and the Starting XI evaluator.
 * ============================================================
 */

$selectedOutcomes =
    array_slice(
        $playerOutcomes,
        0,
        15
    );


$startingXI =
    [];


$bench =
    [];


foreach (
    $selectedOutcomes
    as $index => $outcome
) {

    $recommendationPlayer = [

        'player_id' =>
            (int) $outcome[
                'player_id'
            ],

        'name' =>
            'Integration Player '
            . (int) $outcome[
                'player_id'
            ],

        'position' =>
            null
    ];


    if (
        $index < 11
    ) {

        $startingXI[] =
            $recommendationPlayer;

    } else {

        $bench[] =
            $recommendationPlayer;
    }
}


startingXIBacktestingIntegrationTestResult(
    count(
        $startingXI
    )
    === 11,
    'Synthetic preserved recommendation contains eleven starters.'
);


startingXIBacktestingIntegrationTestResult(
    count(
        $bench
    )
    === 4,
    'Synthetic preserved recommendation contains four bench players.'
);


/*
 * ============================================================
 * PRESERVE SOURCE EVIDENCE
 * ============================================================
 */

$originalStartingXI =
    $startingXI;


$originalBench =
    $bench;


$originalPlayerOutcomes =
    $playerOutcomes;


/*
 * ============================================================
 * SCENARIO A
 * REAL OUTCOME PIPELINE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Real Completed-Gameweek Outcomes<br>";
echo "============================================<br>";


$result =
    $startingXIBacktestingService
        ->evaluate(
            $startingXI,
            $bench,
            $playerOutcomes
        );


startingXIBacktestingIntegrationTestResult(
    !empty(
        $result
    ),
    'Real completed-gameweek outcome evidence produces Starting XI evaluation.'
);


startingXIBacktestingIntegrationTestResult(
    count(
        $result[
            'starting_xi'
        ]
        ?? []
    )
    === 11,
    'All eleven recommended starters match genuine outcome evidence.'
);


startingXIBacktestingIntegrationTestResult(
    count(
        $result[
            'bench'
        ]
        ?? []
    )
    === 4,
    'All four recommended bench players match genuine outcome evidence.'
);


/*
 * ============================================================
 * SCENARIO B
 * EXACT REALISED POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Genuine Realised Points<br>";
echo "============================================<br>";


$expectedStartingXIPoints =
    0;


for (
    $index = 0;
    $index < 11;
    $index++
) {

    $expectedStartingXIPoints +=
        $selectedOutcomes[
            $index
        ][
            'total_points'
        ];
}


$expectedBenchPoints =
    0;


for (
    $index = 11;
    $index < 15;
    $index++
) {

    $expectedBenchPoints +=
        $selectedOutcomes[
            $index
        ][
            'total_points'
        ];
}


startingXIBacktestingIntegrationTestResult(
    (
        $result[
            'starting_xi_points'
        ]
        ?? null
    )
    ===
    $expectedStartingXIPoints,
    'Starting XI total equals genuine realised FPL points.'
);


startingXIBacktestingIntegrationTestResult(
    (
        $result[
            'bench_points'
        ]
        ?? null
    )
    ===
    $expectedBenchPoints,
    'Bench total equals genuine realised FPL points.'
);


/*
 * ============================================================
 * SCENARIO C
 * PLAYER-LEVEL OUTCOME MATCHING
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Genuine Player Outcome Matching<br>";
echo "============================================<br>";


$firstStarter =
    $result[
        'starting_xi'
    ][
        0
    ]
    ?? [];


startingXIBacktestingIntegrationTestResult(
    (
        $firstStarter[
            'player_id'
        ]
        ?? null
    )
    ===
    (int) $selectedOutcomes[
        0
    ][
        'player_id'
    ],
    'Starter identity matches genuine completed-gameweek outcome.'
);


startingXIBacktestingIntegrationTestResult(
    (
        $firstStarter[
            'actual_points'
        ]
        ?? null
    )
    ===
    $selectedOutcomes[
        0
    ][
        'total_points'
    ],
    'Starter realised points come directly from genuine outcome evidence.'
);


startingXIBacktestingIntegrationTestResult(
    (
        $firstStarter[
            'actual_minutes'
        ]
        ?? null
    )
    ===
    $selectedOutcomes[
        0
    ][
        'minutes'
    ],
    'Starter realised minutes come directly from genuine outcome evidence.'
);


$firstBenchPlayer =
    $result[
        'bench'
    ][
        0
    ]
    ?? [];


startingXIBacktestingIntegrationTestResult(
    (
        $firstBenchPlayer[
            'player_id'
        ]
        ?? null
    )
    ===
    (int) $selectedOutcomes[
        11
    ][
        'player_id'
    ],
    'Bench identity matches genuine completed-gameweek outcome.'
);


startingXIBacktestingIntegrationTestResult(
    (
        $firstBenchPlayer[
            'actual_points'
        ]
        ?? null
    )
    ===
    $selectedOutcomes[
        11
    ][
        'total_points'
    ],
    'Bench realised points come directly from genuine outcome evidence.'
);


/*
 * ============================================================
 * SCENARIO D
 * FACTUAL BENCH OUTPERFORMANCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Genuine Bench Outperformance Evidence<br>";
echo "============================================<br>";


$expectedOutperformanceCount =
    0;


foreach (
    array_slice(
        $selectedOutcomes,
        11,
        4
    )
    as $benchOutcome
) {

    foreach (
        array_slice(
            $selectedOutcomes,
            0,
            11
        )
        as $starterOutcome
    ) {

        if (
            $benchOutcome[
                'total_points'
            ]
            >
            $starterOutcome[
                'total_points'
            ]
        ) {

            $expectedOutperformanceCount++;
        }
    }
}


startingXIBacktestingIntegrationTestResult(
    count(
        $result[
            'bench_outperformance'
        ]
        ?? []
    )
    ===
    $expectedOutperformanceCount,
    'Bench outperformance count is derived from genuine realised FPL points.'
);


$allOutperformanceRowsValid =
    true;


foreach (
    $result[
        'bench_outperformance'
    ]
    ?? []
    as $opportunity
) {

    if (
        !isset(
            $opportunity[
                'bench_player_id'
            ],
            $opportunity[
                'starter_player_id'
            ],
            $opportunity[
                'bench_actual_points'
            ],
            $opportunity[
                'starter_actual_points'
            ],
            $opportunity[
                'points_difference'
            ]
        )
    ) {

        $allOutperformanceRowsValid =
            false;

        break;
    }


    if (
        $opportunity[
            'bench_actual_points'
        ]
        <=
        $opportunity[
            'starter_actual_points'
        ]
    ) {

        $allOutperformanceRowsValid =
            false;

        break;
    }


    if (
        $opportunity[
            'points_difference'
        ]
        !==
        (
            $opportunity[
                'bench_actual_points'
            ]
            -
            $opportunity[
                'starter_actual_points'
            ]
        )
    ) {

        $allOutperformanceRowsValid =
            false;

        break;
    }
}


startingXIBacktestingIntegrationTestResult(
    $allOutperformanceRowsValid,
    'Every genuine bench outperformance row contains a factual positive points difference.'
);


/*
 * ============================================================
 * SCENARIO E
 * ZERO AND NEGATIVE OUTCOMES REMAIN FACTUAL
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Real Outcome Value Preservation<br>";
echo "============================================<br>";


$realOutcomeValuesPreserved =
    true;


foreach (
    $result[
        'starting_xi'
    ]
    ?? []
    as $evaluatedPlayer
) {

    $matchingOutcome =
        null;


    foreach (
        $selectedOutcomes
        as $outcome
    ) {

        if (
            (int) $outcome[
                'player_id'
            ]
            ===
            (int) $evaluatedPlayer[
                'player_id'
            ]
        ) {

            $matchingOutcome =
                $outcome;

            break;
        }
    }


    if (
        $matchingOutcome
        ===
        null
        ||
        $evaluatedPlayer[
            'actual_points'
        ]
        !==
        $matchingOutcome[
            'total_points'
        ]
        ||
        $evaluatedPlayer[
            'actual_minutes'
        ]
        !==
        $matchingOutcome[
            'minutes'
        ]
    ) {

        $realOutcomeValuesPreserved =
            false;

        break;
    }
}


startingXIBacktestingIntegrationTestResult(
    $realOutcomeValuesPreserved,
    'Real outcome values are preserved without normalisation or manufacture.'
);


/*
 * ============================================================
 * SCENARIO F
 * SOURCE IMMUTABILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


startingXIBacktestingIntegrationTestResult(
    $startingXI
        ===
        $originalStartingXI,
    'Preserved Starting XI evidence remains unchanged.'
);


startingXIBacktestingIntegrationTestResult(
    $bench
        ===
        $originalBench,
    'Preserved bench evidence remains unchanged.'
);


startingXIBacktestingIntegrationTestResult(
    $playerOutcomes
        ===
        $originalPlayerOutcomes,
    'Authoritative completed-gameweek outcomes remain unchanged.'
);


/*
 * ============================================================
 * SCENARIO G
 * EVALUATION BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Starting XI Evaluation Boundary<br>";
echo "============================================<br>";


startingXIBacktestingIntegrationTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Integration path does not manufacture Starting XI accuracy score.'
);


startingXIBacktestingIntegrationTestResult(
    !array_key_exists(
        'optimal_starting_xi',
        $result
    ),
    'Integration path does not manufacture an optimal Starting XI.'
);


startingXIBacktestingIntegrationTestResult(
    !array_key_exists(
        'automatic_substitutions',
        $result
    ),
    'Integration path does not simulate automatic substitutions.'
);


startingXIBacktestingIntegrationTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Integration path does not manufacture overall backtesting score.'
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
    $failed
    ===
    0
) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}