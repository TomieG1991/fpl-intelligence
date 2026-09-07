<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Player Ranking Backtesting Service Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function gameweekPlayerRankingIntegrationTestResult(
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


function gameweekPlayerRankingIntegrationApproximatelyEqual(
    mixed $actual,
    float $expected,
    float $tolerance = 0.0000001
): bool {

    return
        is_numeric(
            $actual
        )
        &&
        abs(
            (float) $actual
            -
            $expected
        )
        <
        $tolerance;
}


/*
 * ============================================================
 * PRODUCTION SERVICES
 * ============================================================
 */

$playerRankingBacktestingService =
    new PlayerRankingBacktestingService();


$playerRankingBacktestingMetricsService =
    new PlayerRankingBacktestingMetricsService();


$service =
    new GameweekPlayerRankingBacktestingService(
        $playerRankingBacktestingService,
        $playerRankingBacktestingMetricsService
    );


gameweekPlayerRankingIntegrationTestResult(
    $playerRankingBacktestingService
        instanceof PlayerRankingBacktestingService,
    'Production player-level ranking backtesting service is available.'
);


gameweekPlayerRankingIntegrationTestResult(
    $playerRankingBacktestingMetricsService
        instanceof PlayerRankingBacktestingMetricsService,
    'Production ranking metrics service is available.'
);


gameweekPlayerRankingIntegrationTestResult(
    $service
        instanceof GameweekPlayerRankingBacktestingService,
    'Production gameweek ranking backtesting pipeline can be constructed.'
);


/*
 * ============================================================
 * HISTORICAL PLAYER RANKING EVIDENCE
 * ============================================================
 *
 * These ranks deliberately contain gaps.
 *
 * This represents a realistic backtesting sample where the
 * immutable full-player-pool ranking contained more players than
 * those for whom this controlled outcome sample has evidence.
 *
 * Historical ordering:
 *
 * Player 101 = rank 2
 * Player 102 = rank 7
 * Player 103 = rank 15
 * Player 104 = rank 22
 * Player 105 = rank 31
 */

$playerRankings = [

    [
        'player_id' => 101,
        'fpl_player_id' => 1001,
        'name' => 'Player One',
        'position' => 'MID',
        'team_id' => 1,
        'price' => 10.0,
        'intelligence_score' => 90.0,
        'rank' => 2
    ],

    [
        'player_id' => 102,
        'fpl_player_id' => 1002,
        'name' => 'Player Two',
        'position' => 'FWD',
        'team_id' => 2,
        'price' => 9.0,
        'intelligence_score' => 80.0,
        'rank' => 7
    ],

    [
        'player_id' => 103,
        'fpl_player_id' => 1003,
        'name' => 'Player Three',
        'position' => 'MID',
        'team_id' => 3,
        'price' => 7.5,
        'intelligence_score' => 70.0,
        'rank' => 15
    ],

    [
        'player_id' => 104,
        'fpl_player_id' => 1004,
        'name' => 'Player Four',
        'position' => 'DEF',
        'team_id' => 4,
        'price' => 5.5,
        'intelligence_score' => 60.0,
        'rank' => 22
    ],

    [
        'player_id' => 105,
        'fpl_player_id' => 1005,
        'name' => 'Player Without Outcome',
        'position' => 'DEF',
        'team_id' => 5,
        'price' => 4.5,
        'intelligence_score' => 50.0,
        'rank' => 31
    ]
];


/*
 * ============================================================
 * COMPLETED GAMEWEEK OUTCOME EVIDENCE
 * ============================================================
 *
 * Player 105 deliberately has no outcome and must therefore be
 * excluded without rewriting any historical ranks.
 *
 * Player 999 deliberately has an outcome but no preserved
 * ranking and must not be retrospectively added.
 *
 * Players 102 and 103 deliberately tie on realised points.
 */

$playerOutcomes = [

    [
        'gameweek_id' => 5,
        'player_id' => 101,
        'fixture_count' => 1,
        'total_points' => 12,
        'minutes' => 90,
        'starts' => 1,
        'goals' => 1,
        'assists' => 1,
        'clean_sheets' => 1,
        'bonus' => 3
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 102,
        'fixture_count' => 1,
        'total_points' => 8,
        'minutes' => 85,
        'starts' => 1,
        'goals' => 1,
        'assists' => 0,
        'clean_sheets' => 0,
        'bonus' => 1
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 103,
        'fixture_count' => 1,
        'total_points' => 8,
        'minutes' => 90,
        'starts' => 1,
        'goals' => 0,
        'assists' => 2,
        'clean_sheets' => 0,
        'bonus' => 2
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 104,
        'fixture_count' => 1,
        'total_points' => 2,
        'minutes' => 90,
        'starts' => 1,
        'goals' => 0,
        'assists' => 0,
        'clean_sheets' => 0,
        'bonus' => 0
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 999,
        'fixture_count' => 1,
        'total_points' => 20,
        'minutes' => 90,
        'starts' => 1,
        'goals' => 3,
        'assists' => 1,
        'clean_sheets' => 0,
        'bonus' => 3
    ]
];


/*
 * ============================================================
 * ASSEMBLED HISTORICAL EVIDENCE
 * ============================================================
 */

$evidence = [

    'status' =>
        'Ready',

    'reason' =>
        null,

    'entry_id' =>
        900001,

    'gameweek_id' =>
        5,

    'gameweek' => [

        'id' =>
            5,

        'fpl_gameweek_id' =>
            1,

        'finished' =>
            1,

        'data_checked' =>
            1
    ],

    'recommendation_snapshot' => [

        'gameweek_id' =>
            5,

        'entry_id' =>
            900001,

        'player_rankings' =>
            $playerRankings,

        /*
         * Deliberately unrelated squad-only projections.
         *
         * The ranking pipeline must not use these as a substitute
         * for full-player-pool ranking evidence.
         */
        'player_projections' => [

            [
                'player_id' => 999,
                'intelligence_score' => 100.0,
                'projected_points' => 20.0
            ]
        ],

        'starting_xi' =>
            [],

        'captain_recommendation' =>
            [],

        'transfer_recommendations' =>
            [],

        'gameweek_decision' =>
            [],

        'chip_recommendations' =>
            []
    ],

    'player_outcomes' =>
        $playerOutcomes
];


$originalEvidence =
    $evidence;


/*
 * ============================================================
 * SCENARIO A
 * COMPLETE PRODUCTION PIPELINE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Complete Production Ranking Pipeline<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $evidence
    );


gameweekPlayerRankingIntegrationTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Ready',
    'Real production services produce Ready Player Ranking backtesting.'
);


gameweekPlayerRankingIntegrationTestResult(
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
    'Ready production result preserves explicit null reason.'
);


gameweekPlayerRankingIntegrationTestResult(
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    ===
    900001,
    'Production pipeline preserves entry identity.'
);


gameweekPlayerRankingIntegrationTestResult(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'Production pipeline preserves local gameweek identity.'
);


/*
 * ============================================================
 * SCENARIO B
 * REAL PLAYER-LEVEL RANKING COMPARISONS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Real Player-Level Ranking Comparisons<br>";
echo "============================================<br>";


$evaluations =
    $result[
        'player_evaluations'
    ]
    ?? [];


gameweekPlayerRankingIntegrationTestResult(
    count(
        $evaluations
    )
    ===
    4,
    'Only historically ranked players with genuine realised outcomes are evaluated.'
);


gameweekPlayerRankingIntegrationTestResult(
    array_column(
        $evaluations,
        'player_id'
    )
    ===
    [
        101,
        102,
        103,
        104
    ],
    'Production evaluator preserves historical Player Ranking Evidence order.'
);


gameweekPlayerRankingIntegrationTestResult(
    array_column(
        $evaluations,
        'rank'
    )
    ===
    [
        2,
        7,
        15,
        22
    ],
    'Production evaluator preserves non-contiguous historical ranks exactly.'
);


gameweekPlayerRankingIntegrationTestResult(
    array_column(
        $evaluations,
        'intelligence_score'
    )
    ===
    [
        90.0,
        80.0,
        70.0,
        60.0
    ],
    'Production evaluator preserves historical Intelligence Scores exactly.'
);


gameweekPlayerRankingIntegrationTestResult(
    array_column(
        $evaluations,
        'actual_points'
    )
    ===
    [
        12,
        8,
        8,
        2
    ],
    'Production evaluator attaches the correct realised FPL points.'
);


gameweekPlayerRankingIntegrationTestResult(
    array_column(
        $evaluations,
        'actual_minutes'
    )
    ===
    [
        90,
        85,
        90,
        90
    ],
    'Production evaluator attaches the correct realised minutes.'
);


/*
 * ============================================================
 * SCENARIO C
 * UNMATCHED HISTORICAL RANKING
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Ranked Player Without Outcome<br>";
echo "============================================<br>";


$evaluatedPlayerIds =
    array_column(
        $evaluations,
        'player_id'
    );


gameweekPlayerRankingIntegrationTestResult(
    !in_array(
        105,
        $evaluatedPlayerIds,
        true
    ),
    'Historically ranked player without realised outcome evidence is excluded.'
);


gameweekPlayerRankingIntegrationTestResult(
    array_column(
        $evaluations,
        'rank'
    )
    ===
    [
        2,
        7,
        15,
        22
    ],
    'Missing outcome does not cause surviving historical ranks to be renumbered.'
);


/*
 * ============================================================
 * SCENARIO D
 * OUTCOME WITHOUT HISTORICAL RANKING
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Outcome Without Historical Ranking<br>";
echo "============================================<br>";


gameweekPlayerRankingIntegrationTestResult(
    !in_array(
        999,
        $evaluatedPlayerIds,
        true
    ),
    'Outcome without preserved Player Ranking Evidence is not retrospectively added.'
);


gameweekPlayerRankingIntegrationTestResult(
    count(
        $evaluations
    )
    ===
    4,
    'Unranked realised outcome does not inflate the ranking backtesting sample.'
);


/*
 * ============================================================
 * SCENARIO E
 * REAL AGGREGATE RANKING METRICS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Real Aggregate Ranking Metrics<br>";
echo "============================================<br>";


$metrics =
    $result[
        'metrics'
    ]
    ?? [];


gameweekPlayerRankingIntegrationTestResult(
    (
        $metrics[
            'sample_size'
        ]
        ?? null
    )
    ===
    4,
    'Production metrics service counts all valid ranking comparisons.'
);


gameweekPlayerRankingIntegrationTestResult(
    is_numeric(
        $metrics[
            'pearson_correlation'
        ]
        ?? null
    ),
    'Production pipeline calculates Intelligence Score versus realised-points Pearson correlation.'
);


gameweekPlayerRankingIntegrationTestResult(
    (
        $metrics[
            'pearson_correlation'
        ]
        ?? 0
    )
    >
    0.0
    &&
    (
        $metrics[
            'pearson_correlation'
        ]
        ?? 0
    )
    <
    1.0,
    'Pearson correlation is positive but non-perfect for the controlled historical evidence.'
);


gameweekPlayerRankingIntegrationTestResult(
    is_numeric(
        $metrics[
            'spearman_rank_correlation'
        ]
        ?? null
    ),
    'Production pipeline calculates historical-ranking versus realised-ranking Spearman correlation.'
);


gameweekPlayerRankingIntegrationTestResult(
    (
        $metrics[
            'spearman_rank_correlation'
        ]
        ?? 0
    )
    >
    0.0
    &&
    (
        $metrics[
            'spearman_rank_correlation'
        ]
        ?? 0
    )
    <
    1.0,
    'Realised-points tie produces a positive but non-perfect Spearman relationship.'
);


/*
 * ============================================================
 * SCENARIO F
 * NON-CONTIGUOUS HISTORICAL RANKS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Non-Contiguous Historical Rank Ordering<br>";
echo "============================================<br>";


$perfectOrderEvidence =
    $evidence;


$perfectOrderEvidence[
    'player_outcomes'
] = [

    [
        'player_id' => 101,
        'total_points' => 12,
        'minutes' => 90
    ],

    [
        'player_id' => 102,
        'total_points' => 9,
        'minutes' => 90
    ],

    [
        'player_id' => 103,
        'total_points' => 6,
        'minutes' => 90
    ],

    [
        'player_id' => 104,
        'total_points' => 3,
        'minutes' => 90
    ]
];


$perfectOrderResult =
    $service->evaluate(
        $perfectOrderEvidence
    );


$perfectOrderMetrics =
    $perfectOrderResult[
        'metrics'
    ]
    ?? [];


gameweekPlayerRankingIntegrationTestResult(
    gameweekPlayerRankingIntegrationApproximatelyEqual(
        $perfectOrderMetrics[
            'spearman_rank_correlation'
        ]
        ?? null,
        1.0
    ),
    'Non-contiguous historical ranks produce Spearman +1 when realised ordering is perfect.'
);


/*
 * ============================================================
 * SCENARIO G
 * PERFECT REVERSED ORDER
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Reversed Historical Ranking Performance<br>";
echo "============================================<br>";


$reversedEvidence =
    $evidence;


$reversedEvidence[
    'player_outcomes'
] = [

    [
        'player_id' => 101,
        'total_points' => 1,
        'minutes' => 90
    ],

    [
        'player_id' => 102,
        'total_points' => 4,
        'minutes' => 90
    ],

    [
        'player_id' => 103,
        'total_points' => 7,
        'minutes' => 90
    ],

    [
        'player_id' => 104,
        'total_points' => 10,
        'minutes' => 90
    ]
];


$reversedResult =
    $service->evaluate(
        $reversedEvidence
    );


$reversedMetrics =
    $reversedResult[
        'metrics'
    ]
    ?? [];


gameweekPlayerRankingIntegrationTestResult(
    gameweekPlayerRankingIntegrationApproximatelyEqual(
        $reversedMetrics[
            'pearson_correlation'
        ]
        ?? null,
        -1.0
    ),
    'Perfect inverse Intelligence Score returns produce Pearson -1.'
);


gameweekPlayerRankingIntegrationTestResult(
    gameweekPlayerRankingIntegrationApproximatelyEqual(
        $reversedMetrics[
            'spearman_rank_correlation'
        ]
        ?? null,
        -1.0
    ),
    'Perfect reversed historical ordering produces Spearman -1.'
);


/*
 * ============================================================
 * SCENARIO H
 * SQUAD PROJECTIONS CANNOT CONTAMINATE RANKINGS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Ranking Evidence Isolation<br>";
echo "============================================<br>";


gameweekPlayerRankingIntegrationTestResult(
    !in_array(
        999,
        $evaluatedPlayerIds,
        true
    ),
    'Squad-only player projection evidence cannot enter Player Ranking backtesting.'
);


gameweekPlayerRankingIntegrationTestResult(
    (
        $metrics[
            'sample_size'
        ]
        ?? null
    )
    ===
    4,
    'Squad-only projection evidence cannot alter Player Ranking metrics sample size.'
);


/*
 * ============================================================
 * SCENARIO I
 * SOURCE EVIDENCE IMMUTABILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


gameweekPlayerRankingIntegrationTestResult(
    $evidence === $originalEvidence,
    'Complete production ranking pipeline does not mutate assembled historical evidence.'
);


/*
 * ============================================================
 * SCENARIO J
 * EXACT RESULT CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Production Result Contract<br>";
echo "============================================<br>";


gameweekPlayerRankingIntegrationTestResult(
    array_keys(
        $result
    )
    ===
    [
        'status',
        'reason',
        'entry_id',
        'gameweek_id',
        'player_evaluations',
        'metrics'
    ],
    'Production pipeline exposes only the gameweek Player Ranking backtesting contract.'
);


gameweekPlayerRankingIntegrationTestResult(
    array_keys(
        $metrics
    )
    ===
    [
        'sample_size',
        'pearson_correlation',
        'spearman_rank_correlation'
    ],
    'Production metrics expose only the defined Player Ranking metrics contract.'
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