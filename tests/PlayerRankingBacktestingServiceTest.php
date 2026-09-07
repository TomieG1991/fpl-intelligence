<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Ranking Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function playerRankingBacktestingTestResult(
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

playerRankingBacktestingTestResult(
    class_exists(
        'PlayerRankingBacktestingService'
    ),
    'PlayerRankingBacktestingService exists.'
);


if (
    !class_exists(
        'PlayerRankingBacktestingService'
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
    new PlayerRankingBacktestingService();


playerRankingBacktestingTestResult(
    $service
        instanceof PlayerRankingBacktestingService,
    'PlayerRankingBacktestingService can be constructed.'
);


/*
 * ============================================================
 * CONTROLLED PRESERVED RANKING EVIDENCE
 * ============================================================
 */

$playerRankings = [

    [
        'player_id' => 101,
        'fpl_player_id' => 1001,
        'name' => 'Highest Ranked Player',
        'position' => 'MID',
        'team_id' => 1,
        'price' => 10.0,
        'intelligence_score' => 90.0,
        'rank' => 1
    ],

    [
        'player_id' => 102,
        'fpl_player_id' => 1002,
        'name' => 'Second Ranked Player',
        'position' => 'FWD',
        'team_id' => 2,
        'price' => 9.0,
        'intelligence_score' => 80.0,
        'rank' => 2
    ],

    [
        'player_id' => 103,
        'fpl_player_id' => 1003,
        'name' => 'Third Ranked Player',
        'position' => 'DEF',
        'team_id' => 3,
        'price' => 6.0,
        'intelligence_score' => 70.0,
        'rank' => 3
    ]
];


$playerOutcomes = [

    [
        'player_id' => 101,
        'total_points' => 12,
        'minutes' => 90
    ],

    [
        'player_id' => 102,
        'total_points' => 5,
        'minutes' => 90
    ],

    [
        'player_id' => 103,
        'total_points' => 2,
        'minutes' => 60
    ]
];


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
    $service->evaluate(
        [],
        []
    );


playerRankingBacktestingTestResult(
    $result === [],
    'Empty ranking and outcome evidence returns an empty evaluation.'
);


$result =
    $service->evaluate(
        $playerRankings,
        []
    );


playerRankingBacktestingTestResult(
    $result === [],
    'Ranking evidence without realised outcomes returns an empty evaluation.'
);


$result =
    $service->evaluate(
        [],
        $playerOutcomes
    );


playerRankingBacktestingTestResult(
    $result === [],
    'Realised outcomes without preserved rankings return an empty evaluation.'
);


/*
 * ============================================================
 * SCENARIO B
 * STANDARD PLAYER-LEVEL COMPARISON
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Standard Ranking Comparison<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $playerRankings,
        $playerOutcomes
    );


playerRankingBacktestingTestResult(
    count(
        $result
    ) === 3,
    'All three ranked players with realised outcomes are evaluated.'
);


playerRankingBacktestingTestResult(
    (
        $result[
            0
        ][
            'player_id'
        ]
        ?? null
    )
    === 101,
    'First evaluation preserves the ranked player identity.'
);


playerRankingBacktestingTestResult(
    (
        $result[
            0
        ][
            'intelligence_score'
        ]
        ?? null
    )
    === 90.0,
    'First evaluation preserves the historical Intelligence Score.'
);


playerRankingBacktestingTestResult(
    (
        $result[
            0
        ][
            'rank'
        ]
        ?? null
    )
    === 1,
    'First evaluation preserves the historical player rank.'
);


playerRankingBacktestingTestResult(
    (
        $result[
            0
        ][
            'actual_points'
        ]
        ?? null
    )
    === 12,
    'First evaluation contains the realised FPL points.'
);


playerRankingBacktestingTestResult(
    (
        $result[
            0
        ][
            'actual_minutes'
        ]
        ?? null
    )
    === 90,
    'First evaluation contains the realised minutes.'
);


/*
 * ============================================================
 * SCENARIO C
 * EXACT PLAYER-LEVEL CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Exact Player-Level Contract<br>";
echo "============================================<br>";


$expectedFirstEvaluation = [

    'player_id' => 101,
    'fpl_player_id' => 1001,
    'name' => 'Highest Ranked Player',
    'position' => 'MID',
    'team_id' => 1,
    'price' => 10.0,
    'intelligence_score' => 90.0,
    'rank' => 1,
    'actual_points' => 12,
    'actual_minutes' => 90
];


playerRankingBacktestingTestResult(
    $result[
        0
    ]
    ===
    $expectedFirstEvaluation,
    'Player evaluation exposes the exact ranking backtesting evidence contract.'
);


/*
 * ============================================================
 * SCENARIO D
 * PRESERVED RANKING ORDER
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Preserved Ranking Order<br>";
echo "============================================<br>";


playerRankingBacktestingTestResult(
    array_column(
        $result,
        'player_id'
    )
    ===
    [
        101,
        102,
        103
    ],
    'Evaluation preserves the historical ranking evidence order.'
);


playerRankingBacktestingTestResult(
    array_column(
        $result,
        'rank'
    )
    ===
    [
        1,
        2,
        3
    ],
    'Historical ranks are preserved rather than recalculated from outcomes.'
);


playerRankingBacktestingTestResult(
    array_column(
        $result,
        'intelligence_score'
    )
    ===
    [
        90.0,
        80.0,
        70.0
    ],
    'Historical Intelligence Scores are preserved unchanged.'
);


/*
 * ============================================================
 * SCENARIO E
 * OUTCOMES MUST NOT RE-RANK HISTORICAL EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Realised Returns Do Not Rewrite Rankings<br>";
echo "============================================<br>";


$reversedOutcomes = [

    [
        'player_id' => 101,
        'total_points' => 1,
        'minutes' => 90
    ],

    [
        'player_id' => 102,
        'total_points' => 5,
        'minutes' => 90
    ],

    [
        'player_id' => 103,
        'total_points' => 15,
        'minutes' => 90
    ]
];


$reversedResult =
    $service->evaluate(
        $playerRankings,
        $reversedOutcomes
    );


playerRankingBacktestingTestResult(
    array_column(
        $reversedResult,
        'rank'
    )
    ===
    [
        1,
        2,
        3
    ],
    'Realised points do not retrospectively rewrite historical ranks.'
);


playerRankingBacktestingTestResult(
    array_column(
        $reversedResult,
        'actual_points'
    )
    ===
    [
        1,
        5,
        15
    ],
    'Realised points remain factual outcome evidence independent of rank.'
);


/*
 * ============================================================
 * SCENARIO F
 * ZERO-POINT OUTCOME IS VALID EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Zero-Point Outcome<br>";
echo "============================================<br>";


$zeroPointResult =
    $service->evaluate(
        [
            $playerRankings[
                0
            ]
        ],
        [
            [
                'player_id' => 101,
                'total_points' => 0,
                'minutes' => 0
            ]
        ]
    );


playerRankingBacktestingTestResult(
    count(
        $zeroPointResult
    ) === 1,
    'A genuine zero-point outcome remains valid backtesting evidence.'
);


playerRankingBacktestingTestResult(
    (
        $zeroPointResult[
            0
        ][
            'actual_points'
        ]
        ?? null
    )
    === 0,
    'Zero realised points are preserved exactly.'
);


playerRankingBacktestingTestResult(
    (
        $zeroPointResult[
            0
        ][
            'actual_minutes'
        ]
        ?? null
    )
    === 0,
    'Zero realised minutes are preserved exactly.'
);


/*
 * ============================================================
 * SCENARIO G
 * UNMATCHED RANKED PLAYER
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Ranked Player Without Outcome<br>";
echo "============================================<br>";


$partialResult =
    $service->evaluate(
        $playerRankings,
        [
            $playerOutcomes[
                0
            ],
            $playerOutcomes[
                2
            ]
        ]
    );


playerRankingBacktestingTestResult(
    count(
        $partialResult
    ) === 2,
    'Ranked player without a realised outcome is excluded from evaluation.'
);


playerRankingBacktestingTestResult(
    array_column(
        $partialResult,
        'player_id'
    )
    ===
    [
        101,
        103
    ],
    'Matched ranked players retain their original historical order.'
);


playerRankingBacktestingTestResult(
    array_column(
        $partialResult,
        'rank'
    )
    ===
    [
        1,
        3
    ],
    'Historical rank values are not renumbered when another player lacks an outcome.'
);


/*
 * ============================================================
 * SCENARIO H
 * UNRANKED OUTCOME
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Outcome Without Preserved Ranking<br>";
echo "============================================<br>";


$extraOutcomeResult =
    $service->evaluate(
        [
            $playerRankings[
                0
            ]
        ],
        [
            $playerOutcomes[
                0
            ],
            [
                'player_id' => 999,
                'total_points' => 20,
                'minutes' => 90
            ]
        ]
    );


playerRankingBacktestingTestResult(
    count(
        $extraOutcomeResult
    ) === 1,
    'Outcome without preserved ranking evidence is not retrospectively added.'
);


playerRankingBacktestingTestResult(
    (
        $extraOutcomeResult[
            0
        ][
            'player_id'
        ]
        ?? null
    )
    === 101,
    'Only players genuinely present in preserved ranking evidence are evaluated.'
);


/*
 * ============================================================
 * SCENARIO I
 * INVALID RANKING ROWS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Invalid Ranking Evidence<br>";
echo "============================================<br>";


$invalidRankingResult =
    $service->evaluate(
        [

            'not-an-array',

            [
                'player_id' => 0,
                'intelligence_score' => 95.0,
                'rank' => 1
            ],

            [
                'player_id' => 201,
                'intelligence_score' => null,
                'rank' => 2
            ],

            [
                'player_id' => 202,
                'intelligence_score' => 'invalid',
                'rank' => 3
            ],

            [
                'player_id' => 203,
                'intelligence_score' => 75.0,
                'rank' => 0
            ],

            $playerRankings[
                0
            ]
        ],
        $playerOutcomes
    );


playerRankingBacktestingTestResult(
    count(
        $invalidRankingResult
    ) === 1,
    'Malformed ranking rows are excluded rather than converted into evidence.'
);


playerRankingBacktestingTestResult(
    (
        $invalidRankingResult[
            0
        ][
            'player_id'
        ]
        ?? null
    )
    === 101,
    'Valid ranking evidence remains evaluable when malformed rows are present.'
);


/*
 * ============================================================
 * SCENARIO J
 * INVALID OUTCOME ROWS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Invalid Outcome Evidence<br>";
echo "============================================<br>";


$invalidOutcomeResult =
    $service->evaluate(
        [
            $playerRankings[
                0
            ]
        ],
        [

            'not-an-array',

            [
                'player_id' => 0,
                'total_points' => 20,
                'minutes' => 90
            ],

            [
                'player_id' => 101,
                'total_points' => 'invalid',
                'minutes' => 90
            ]
        ]
    );


playerRankingBacktestingTestResult(
    $invalidOutcomeResult === [],
    'Malformed realised outcome evidence does not manufacture an evaluation.'
);


/*
 * ============================================================
 * SCENARIO K
 * INVALID MINUTES DO NOT INVALIDATE POINTS EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Missing Realised Minutes<br>";
echo "============================================<br>";


$missingMinutesResult =
    $service->evaluate(
        [
            $playerRankings[
                0
            ]
        ],
        [
            [
                'player_id' => 101,
                'total_points' => 7
            ]
        ]
    );


playerRankingBacktestingTestResult(
    count(
        $missingMinutesResult
    ) === 1,
    'Valid realised points remain evaluable when minutes evidence is unavailable.'
);


playerRankingBacktestingTestResult(
    (
        $missingMinutesResult[
            0
        ][
            'actual_points'
        ]
        ?? null
    )
    === 7,
    'Realised points are preserved when minutes evidence is unavailable.'
);


playerRankingBacktestingTestResult(
    array_key_exists(
        'actual_minutes',
        $missingMinutesResult[
            0
        ]
    )
    &&
    $missingMinutesResult[
        0
    ][
        'actual_minutes'
    ]
    === null,
    'Unavailable realised minutes remain null rather than being manufactured as zero.'
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


$rankingSource =
    $playerRankings;


$outcomeSource =
    $playerOutcomes;


$rankingBefore =
    serialize(
        $rankingSource
    );


$outcomesBefore =
    serialize(
        $outcomeSource
    );


$service->evaluate(
    $rankingSource,
    $outcomeSource
);


playerRankingBacktestingTestResult(
    serialize(
        $rankingSource
    )
    ===
    $rankingBefore,
    'Backtesting does not mutate preserved Player Ranking Evidence.'
);


playerRankingBacktestingTestResult(
    serialize(
        $outcomeSource
    )
    ===
    $outcomesBefore,
    'Backtesting does not mutate realised player outcome evidence.'
);


/*
 * ============================================================
 * SCENARIO M
 * SCORE AND RANK ARE HISTORICAL EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario M: Historical Score And Rank Preservation<br>";
echo "============================================<br>";


$historicalEvidence = [

    [
        'player_id' => 301,
        'fpl_player_id' => 3001,
        'name' => 'Historical Player A',
        'position' => 'MID',
        'team_id' => 5,
        'price' => 7.5,
        'intelligence_score' => 64.25,
        'rank' => 17
    ],

    [
        'player_id' => 302,
        'fpl_player_id' => 3002,
        'name' => 'Historical Player B',
        'position' => 'FWD',
        'team_id' => 6,
        'price' => 8.5,
        'intelligence_score' => 61.75,
        'rank' => 24
    ]
];


$historicalResult =
    $service->evaluate(
        $historicalEvidence,
        [

            [
                'player_id' => 301,
                'total_points' => 2,
                'minutes' => 90
            ],

            [
                'player_id' => 302,
                'total_points' => 13,
                'minutes' => 90
            ]
        ]
    );


playerRankingBacktestingTestResult(
    (
        $historicalResult[
            0
        ][
            'intelligence_score'
        ]
        ?? null
    )
    === 64.25,
    'Historical Intelligence Score is used exactly rather than recalculated.'
);


playerRankingBacktestingTestResult(
    (
        $historicalResult[
            0
        ][
            'rank'
        ]
        ?? null
    )
    === 17,
    'Historical rank is used exactly rather than regenerated.'
);


playerRankingBacktestingTestResult(
    (
        $historicalResult[
            1
        ][
            'intelligence_score'
        ]
        ?? null
    )
    === 61.75,
    'Second historical Intelligence Score is preserved exactly.'
);


playerRankingBacktestingTestResult(
    (
        $historicalResult[
            1
        ][
            'rank'
        ]
        ?? null
    )
    === 24,
    'Non-contiguous historical ranks remain unchanged.'
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