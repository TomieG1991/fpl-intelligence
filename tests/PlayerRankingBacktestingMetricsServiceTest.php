<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Ranking Backtesting Metrics Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function playerRankingBacktestingMetricsTestResult(
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
 * FLOAT COMPARISON HELPER
 * ============================================================
 */

function playerRankingBacktestingMetricsApproximatelyEqual(
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
 * SERVICE AVAILABILITY
 * ============================================================
 */

playerRankingBacktestingMetricsTestResult(
    class_exists(
        'PlayerRankingBacktestingMetricsService'
    ),
    'PlayerRankingBacktestingMetricsService exists.'
);


if (
    !class_exists(
        'PlayerRankingBacktestingMetricsService'
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
    new PlayerRankingBacktestingMetricsService();


playerRankingBacktestingMetricsTestResult(
    $service
        instanceof PlayerRankingBacktestingMetricsService,
    'PlayerRankingBacktestingMetricsService can be constructed.'
);


/*
 * ============================================================
 * SCENARIO A
 * EMPTY EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Empty Evidence<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        []
    );


$expectedEmptyMetrics = [

    'sample_size' => 0,

    'pearson_correlation' => null,

    'spearman_rank_correlation' => null
];


playerRankingBacktestingMetricsTestResult(
    $result === $expectedEmptyMetrics,
    'Empty evidence returns the exact empty ranking metrics contract.'
);


/*
 * ============================================================
 * SCENARIO B
 * SINGLE PLAYER
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Single Player<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [
            [
                'player_id' => 101,
                'intelligence_score' => 90.0,
                'rank' => 1,
                'actual_points' => 12
            ]
        ]
    );


playerRankingBacktestingMetricsTestResult(
    $result[
        'sample_size'
    ]
    === 1,
    'Single valid player contributes to the sample size.'
);


playerRankingBacktestingMetricsTestResult(
    $result[
        'pearson_correlation'
    ]
    === null,
    'Pearson correlation is unavailable for a single observation.'
);


playerRankingBacktestingMetricsTestResult(
    $result[
        'spearman_rank_correlation'
    ]
    === null,
    'Spearman correlation is unavailable for a single observation.'
);


/*
 * ============================================================
 * SCENARIO C
 * PERFECT POSITIVE RELATIONSHIP
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Perfect Positive Relationship<br>";
echo "============================================<br>";


$perfectPositive = [

    [
        'player_id' => 101,
        'intelligence_score' => 90.0,
        'rank' => 1,
        'actual_points' => 12
    ],

    [
        'player_id' => 102,
        'intelligence_score' => 80.0,
        'rank' => 2,
        'actual_points' => 9
    ],

    [
        'player_id' => 103,
        'intelligence_score' => 70.0,
        'rank' => 3,
        'actual_points' => 6
    ],

    [
        'player_id' => 104,
        'intelligence_score' => 60.0,
        'rank' => 4,
        'actual_points' => 3
    ]
];


$result =
    $service->calculate(
        $perfectPositive
    );


playerRankingBacktestingMetricsTestResult(
    $result[
        'sample_size'
    ]
    === 4,
    'All valid observations contribute to the ranking sample.'
);


playerRankingBacktestingMetricsTestResult(
    playerRankingBacktestingMetricsApproximatelyEqual(
        $result[
            'pearson_correlation'
        ],
        1.0
    ),
    'Perfectly aligned Intelligence Scores and realised points produce Pearson +1.'
);


playerRankingBacktestingMetricsTestResult(
    playerRankingBacktestingMetricsApproximatelyEqual(
        $result[
            'spearman_rank_correlation'
        ],
        1.0
    ),
    'Perfectly aligned historical ranking and realised points produce Spearman +1.'
);


/*
 * ============================================================
 * SCENARIO D
 * PERFECT NEGATIVE RELATIONSHIP
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Perfect Negative Relationship<br>";
echo "============================================<br>";


$perfectNegative = [

    [
        'player_id' => 201,
        'intelligence_score' => 90.0,
        'rank' => 1,
        'actual_points' => 1
    ],

    [
        'player_id' => 202,
        'intelligence_score' => 80.0,
        'rank' => 2,
        'actual_points' => 4
    ],

    [
        'player_id' => 203,
        'intelligence_score' => 70.0,
        'rank' => 3,
        'actual_points' => 7
    ],

    [
        'player_id' => 204,
        'intelligence_score' => 60.0,
        'rank' => 4,
        'actual_points' => 10
    ]
];


$result =
    $service->calculate(
        $perfectNegative
    );


playerRankingBacktestingMetricsTestResult(
    playerRankingBacktestingMetricsApproximatelyEqual(
        $result[
            'pearson_correlation'
        ],
        -1.0
    ),
    'Perfect inverse Intelligence Scores and realised points produce Pearson -1.'
);


playerRankingBacktestingMetricsTestResult(
    playerRankingBacktestingMetricsApproximatelyEqual(
        $result[
            'spearman_rank_correlation'
        ],
        -1.0
    ),
    'Perfect inverse historical ranking and realised points produce Spearman -1.'
);


/*
 * ============================================================
 * SCENARIO E
 * PEARSON USES SCORE MAGNITUDE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Pearson Uses Intelligence Score Magnitude<br>";
echo "============================================<br>";


$nonLinearScores = [

    [
        'player_id' => 301,
        'intelligence_score' => 100.0,
        'rank' => 1,
        'actual_points' => 10
    ],

    [
        'player_id' => 302,
        'intelligence_score' => 80.0,
        'rank' => 2,
        'actual_points' => 9
    ],

    [
        'player_id' => 303,
        'intelligence_score' => 79.0,
        'rank' => 3,
        'actual_points' => 8
    ],

    [
        'player_id' => 304,
        'intelligence_score' => 20.0,
        'rank' => 4,
        'actual_points' => 7
    ]
];


$result =
    $service->calculate(
        $nonLinearScores
    );


playerRankingBacktestingMetricsTestResult(
    is_numeric(
        $result[
            'pearson_correlation'
        ]
    )
    &&
    $result[
        'pearson_correlation'
    ]
    >
    0.0
    &&
    $result[
        'pearson_correlation'
    ]
    <
    1.0,
    'Pearson reflects score magnitude and need not equal one for perfectly ordered players.'
);


playerRankingBacktestingMetricsTestResult(
    playerRankingBacktestingMetricsApproximatelyEqual(
        $result[
            'spearman_rank_correlation'
        ],
        1.0
    ),
    'Spearman remains +1 when the player ordering is perfectly correct.'
);


/*
 * ============================================================
 * SCENARIO F
 * REALISED POINTS TIES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Realised Points Ties<br>";
echo "============================================<br>";


$tiedPoints = [

    [
        'player_id' => 401,
        'intelligence_score' => 90.0,
        'rank' => 1,
        'actual_points' => 10
    ],

    [
        'player_id' => 402,
        'intelligence_score' => 80.0,
        'rank' => 2,
        'actual_points' => 8
    ],

    [
        'player_id' => 403,
        'intelligence_score' => 70.0,
        'rank' => 3,
        'actual_points' => 8
    ],

    [
        'player_id' => 404,
        'intelligence_score' => 60.0,
        'rank' => 4,
        'actual_points' => 2
    ]
];


$result =
    $service->calculate(
        $tiedPoints
    );


playerRankingBacktestingMetricsTestResult(
    $result[
        'sample_size'
    ]
    === 4,
    'Tied realised points remain valid ranking evidence.'
);


playerRankingBacktestingMetricsTestResult(
    is_numeric(
        $result[
            'pearson_correlation'
        ]
    ),
    'Pearson correlation remains calculable when realised points contain ties.'
);


playerRankingBacktestingMetricsTestResult(
    is_numeric(
        $result[
            'spearman_rank_correlation'
        ]
    ),
    'Spearman correlation remains calculable when realised points contain ties.'
);


playerRankingBacktestingMetricsTestResult(
    $result[
        'spearman_rank_correlation'
    ]
    <
    1.0
    &&
    $result[
        'spearman_rank_correlation'
    ]
    >
    0.0,
    'Average realised ranks for tied points produce a positive but non-perfect Spearman relationship.'
);


/*
 * ============================================================
 * SCENARIO G
 * CONSTANT INTELLIGENCE SCORE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Constant Intelligence Scores<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [

            [
                'player_id' => 501,
                'intelligence_score' => 75.0,
                'rank' => 1,
                'actual_points' => 10
            ],

            [
                'player_id' => 502,
                'intelligence_score' => 75.0,
                'rank' => 2,
                'actual_points' => 5
            ],

            [
                'player_id' => 503,
                'intelligence_score' => 75.0,
                'rank' => 3,
                'actual_points' => 1
            ]
        ]
    );


playerRankingBacktestingMetricsTestResult(
    $result[
        'sample_size'
    ]
    === 3,
    'Constant Intelligence Scores still contribute valid observations.'
);


playerRankingBacktestingMetricsTestResult(
    $result[
        'pearson_correlation'
    ]
    === null,
    'Pearson is unavailable when Intelligence Score has no variation.'
);


playerRankingBacktestingMetricsTestResult(
    is_numeric(
        $result[
            'spearman_rank_correlation'
        ]
    ),
    'Spearman can still evaluate preserved historical ranks when score magnitude has no variation.'
);


/*
 * ============================================================
 * SCENARIO H
 * CONSTANT REALISED POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Constant Realised Points<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [

            [
                'player_id' => 601,
                'intelligence_score' => 90.0,
                'rank' => 1,
                'actual_points' => 2
            ],

            [
                'player_id' => 602,
                'intelligence_score' => 80.0,
                'rank' => 2,
                'actual_points' => 2
            ],

            [
                'player_id' => 603,
                'intelligence_score' => 70.0,
                'rank' => 3,
                'actual_points' => 2
            ]
        ]
    );


playerRankingBacktestingMetricsTestResult(
    $result[
        'sample_size'
    ]
    === 3,
    'Constant realised points still contribute valid observations.'
);


playerRankingBacktestingMetricsTestResult(
    $result[
        'pearson_correlation'
    ]
    === null,
    'Pearson is unavailable when realised points have no variation.'
);


playerRankingBacktestingMetricsTestResult(
    $result[
        'spearman_rank_correlation'
    ]
    === null,
    'Spearman is unavailable when realised points have no rank variation.'
);


/*
 * ============================================================
 * SCENARIO I
 * INVALID ROWS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Invalid Evaluation Rows<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [

            'not-an-array',

            [
                'player_id' => 701,
                'intelligence_score' => null,
                'rank' => 1,
                'actual_points' => 10
            ],

            [
                'player_id' => 702,
                'intelligence_score' => 80.0,
                'rank' => 0,
                'actual_points' => 8
            ],

            [
                'player_id' => 703,
                'intelligence_score' => 70.0,
                'rank' => 3,
                'actual_points' => 'invalid'
            ],

            [
                'player_id' => 704,
                'intelligence_score' => 60.0,
                'rank' => 4,
                'actual_points' => 4
            ],

            [
                'player_id' => 705,
                'intelligence_score' => 50.0,
                'rank' => 5,
                'actual_points' => 2
            ]
        ]
    );


playerRankingBacktestingMetricsTestResult(
    $result[
        'sample_size'
    ]
    === 2,
    'Only rows with valid score, rank and realised points contribute to metrics.'
);


playerRankingBacktestingMetricsTestResult(
    playerRankingBacktestingMetricsApproximatelyEqual(
        $result[
            'pearson_correlation'
        ],
        1.0
    ),
    'Valid rows still produce Pearson metrics when malformed rows are ignored.'
);


playerRankingBacktestingMetricsTestResult(
    playerRankingBacktestingMetricsApproximatelyEqual(
        $result[
            'spearman_rank_correlation'
        ],
        1.0
    ),
    'Valid rows still produce Spearman metrics when malformed rows are ignored.'
);


/*
 * ============================================================
 * SCENARIO J
 * NON-CONTIGUOUS HISTORICAL RANKS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Non-Contiguous Historical Ranks<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [

            [
                'player_id' => 801,
                'intelligence_score' => 85.0,
                'rank' => 2,
                'actual_points' => 12
            ],

            [
                'player_id' => 802,
                'intelligence_score' => 75.0,
                'rank' => 7,
                'actual_points' => 8
            ],

            [
                'player_id' => 803,
                'intelligence_score' => 65.0,
                'rank' => 15,
                'actual_points' => 3
            ]
        ]
    );


playerRankingBacktestingMetricsTestResult(
    $result[
        'sample_size'
    ]
    === 3,
    'Non-contiguous historical ranks remain valid metric evidence.'
);


playerRankingBacktestingMetricsTestResult(
    playerRankingBacktestingMetricsApproximatelyEqual(
        $result[
            'spearman_rank_correlation'
        ],
        1.0
    ),
    'Spearman uses historical ordering rather than requiring contiguous rank numbers.'
);


/*
 * ============================================================
 * SCENARIO K
 * ZERO AND NEGATIVE REALISED POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Zero And Negative Realised Points<br>";
echo "============================================<br>";


$result =
    $service->calculate(
        [

            [
                'player_id' => 901,
                'intelligence_score' => 90.0,
                'rank' => 1,
                'actual_points' => 5
            ],

            [
                'player_id' => 902,
                'intelligence_score' => 80.0,
                'rank' => 2,
                'actual_points' => 0
            ],

            [
                'player_id' => 903,
                'intelligence_score' => 70.0,
                'rank' => 3,
                'actual_points' => -2
            ]
        ]
    );


playerRankingBacktestingMetricsTestResult(
    $result[
        'sample_size'
    ]
    === 3,
    'Zero and negative realised FPL points remain valid metric evidence.'
);


playerRankingBacktestingMetricsTestResult(
    is_numeric(
        $result[
            'pearson_correlation'
        ]
    ),
    'Pearson supports legitimate zero and negative FPL returns.'
);


playerRankingBacktestingMetricsTestResult(
    playerRankingBacktestingMetricsApproximatelyEqual(
        $result[
            'spearman_rank_correlation'
        ],
        1.0
    ),
    'Spearman correctly evaluates ordered zero and negative FPL returns.'
);


/*
 * ============================================================
 * SCENARIO L
 * SOURCE EVIDENCE IMMUTABILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario L: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


$sourceEvidence =
    $perfectPositive;


$sourceBefore =
    serialize(
        $sourceEvidence
    );


$service->calculate(
    $sourceEvidence
);


playerRankingBacktestingMetricsTestResult(
    serialize(
        $sourceEvidence
    )
    ===
    $sourceBefore,
    'Metrics calculation does not mutate player-level backtesting evidence.'
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