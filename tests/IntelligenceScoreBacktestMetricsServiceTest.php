<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed =
    0;


$failed =
    0;


function intelligenceScoreBacktestMetricsSection(
    string $title
): void {

    echo "============================================<br>";
    echo htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );
    echo "<br>";
    echo "============================================<br>";
}


function intelligenceScoreBacktestMetricsAssert(
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
 * A. SERVICE CONTRACT
 * ============================================================
 */

intelligenceScoreBacktestMetricsSection(
    'A. Service Contract'
);


intelligenceScoreBacktestMetricsAssert(
    class_exists(
        'IntelligenceScoreBacktestMetricsService'
    ),
    'IntelligenceScoreBacktestMetricsService exists.'
);


/*
 * Stop here for the controlled RED when production does not
 * yet exist.
 */

if (
    !class_exists(
        'IntelligenceScoreBacktestMetricsService'
    )
) {

    echo "<br>";

    echo "============================================<br>";
    echo "Intelligence Score Backtest Metrics Service Test Summary<br>";
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
    new IntelligenceScoreBacktestMetricsService();


/*
 * ============================================================
 * B. EMPTY BACKTEST EVIDENCE
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'B. Empty Backtest Evidence'
);


$emptyResult =
    $service->summarise(
        []
    );


intelligenceScoreBacktestMetricsAssert(
    $emptyResult[
        'total_players'
    ]
    ===
    0,
    'Empty evidence reports zero total players.'
);


intelligenceScoreBacktestMetricsAssert(
    $emptyResult[
        'comparable_players'
    ]
    ===
    0,
    'Empty evidence reports zero comparable players.'
);


intelligenceScoreBacktestMetricsAssert(
    $emptyResult[
        'unavailable_players'
    ]
    ===
    0,
    'Empty evidence reports zero unavailable players.'
);


intelligenceScoreBacktestMetricsAssert(
    $emptyResult[
        'correlation'
    ]
    ===
    null,
    'Correlation is null when there is no comparable evidence.'
);


/*
 * ============================================================
 * C. SINGLE COMPARABLE PLAYER
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'C. Single Comparable Player'
);


$singleResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    82.0,

                'actual_points' =>
                    12
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $singleResult[
        'total_players'
    ]
    ===
    1,
    'Single evidence row reports one total player.'
);


intelligenceScoreBacktestMetricsAssert(
    $singleResult[
        'comparable_players'
    ]
    ===
    1,
    'Player with Intelligence Score and actual points is comparable.'
);


intelligenceScoreBacktestMetricsAssert(
    $singleResult[
        'unavailable_players'
    ]
    ===
    0,
    'Complete Intelligence Score evidence is not unavailable.'
);


intelligenceScoreBacktestMetricsAssert(
    $singleResult[
        'correlation'
    ]
    ===
    null,
    'Correlation is null when only one comparable player exists.'
);


/*
 * ============================================================
 * D. PERFECT POSITIVE CORRELATION
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'D. Perfect Positive Correlation'
);


$positiveResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    50.0,

                'actual_points' =>
                    2
            ],
            [
                'intelligence_score' =>
                    60.0,

                'actual_points' =>
                    4
            ],
            [
                'intelligence_score' =>
                    70.0,

                'actual_points' =>
                    6
            ],
            [
                'intelligence_score' =>
                    80.0,

                'actual_points' =>
                    8
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $positiveResult[
        'total_players'
    ]
    ===
    4,
    'Positive sample reports all four players.'
);


intelligenceScoreBacktestMetricsAssert(
    $positiveResult[
        'comparable_players'
    ]
    ===
    4,
    'All positive-correlation rows are comparable.'
);


intelligenceScoreBacktestMetricsAssert(
    $positiveResult[
        'unavailable_players'
    ]
    ===
    0,
    'No complete positive-correlation evidence is unavailable.'
);


intelligenceScoreBacktestMetricsAssert(
    abs(
        $positiveResult[
            'correlation'
        ]
        -
        1.0
    )
    <
    0.000000001,
    'Perfect positive linear relationship produces correlation +1.'
);


/*
 * ============================================================
 * E. PERFECT NEGATIVE CORRELATION
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'E. Perfect Negative Correlation'
);


$negativeResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    50.0,

                'actual_points' =>
                    8
            ],
            [
                'intelligence_score' =>
                    60.0,

                'actual_points' =>
                    6
            ],
            [
                'intelligence_score' =>
                    70.0,

                'actual_points' =>
                    4
            ],
            [
                'intelligence_score' =>
                    80.0,

                'actual_points' =>
                    2
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $negativeResult[
        'comparable_players'
    ]
    ===
    4,
    'All negative-correlation rows are comparable.'
);


intelligenceScoreBacktestMetricsAssert(
    abs(
        $negativeResult[
            'correlation'
        ]
        +
        1.0
    )
    <
    0.000000001,
    'Perfect negative linear relationship produces correlation -1.'
);


/*
 * ============================================================
 * F. GENUINE ZERO ACTUAL POINTS
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'F. Genuine Zero Actual Points'
);


$zeroResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    60.0,

                'actual_points' =>
                    0
            ],
            [
                'intelligence_score' =>
                    80.0,

                'actual_points' =>
                    10
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $zeroResult[
        'comparable_players'
    ]
    ===
    2,
    'Genuine zero actual points remain comparable correlation evidence.'
);


intelligenceScoreBacktestMetricsAssert(
    $zeroResult[
        'unavailable_players'
    ]
    ===
    0,
    'Genuine zero actual points are not treated as missing.'
);


intelligenceScoreBacktestMetricsAssert(
    abs(
        $zeroResult[
            'correlation'
        ]
        -
        1.0
    )
    <
    0.000000001,
    'Genuine zero return participates normally in correlation.'
);


/*
 * ============================================================
 * G. NEGATIVE ACTUAL POINTS
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'G. Negative Actual Points'
);


$negativePointsResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    60.0,

                'actual_points' =>
                    -2
            ],
            [
                'intelligence_score' =>
                    70.0,

                'actual_points' =>
                    3
            ],
            [
                'intelligence_score' =>
                    80.0,

                'actual_points' =>
                    8
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $negativePointsResult[
        'comparable_players'
    ]
    ===
    3,
    'Negative realised FPL points remain comparable correlation evidence.'
);


intelligenceScoreBacktestMetricsAssert(
    abs(
        $negativePointsResult[
            'correlation'
        ]
        -
        1.0
    )
    <
    0.000000001,
    'Negative realised return participates normally in correlation.'
);


/*
 * ============================================================
 * H. MISSING INTELLIGENCE SCORE
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'H. Missing Intelligence Score'
);


$missingScoreResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    null,

                'actual_points' =>
                    6
            ],
            [
                'intelligence_score' =>
                    80.0,

                'actual_points' =>
                    8
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $missingScoreResult[
        'total_players'
    ]
    ===
    2,
    'Player without historical Intelligence Score remains in total evidence.'
);


intelligenceScoreBacktestMetricsAssert(
    $missingScoreResult[
        'comparable_players'
    ]
    ===
    1,
    'Missing historical Intelligence Score is excluded from comparable evidence.'
);


intelligenceScoreBacktestMetricsAssert(
    $missingScoreResult[
        'unavailable_players'
    ]
    ===
    1,
    'Missing historical Intelligence Score is counted as unavailable.'
);


intelligenceScoreBacktestMetricsAssert(
    $missingScoreResult[
        'correlation'
    ]
    ===
    null,
    'Correlation remains unavailable when only one comparable player remains.'
);


/*
 * ============================================================
 * I. MISSING REALISED RETURN
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'I. Missing Realised Return'
);


$missingActualResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    70.0,

                'actual_points' =>
                    null
            ],
            [
                'intelligence_score' =>
                    80.0,

                'actual_points' =>
                    8
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $missingActualResult[
        'total_players'
    ]
    ===
    2,
    'Player without realised return remains in total evidence.'
);


intelligenceScoreBacktestMetricsAssert(
    $missingActualResult[
        'comparable_players'
    ]
    ===
    1,
    'Missing realised return is excluded from comparable evidence.'
);


intelligenceScoreBacktestMetricsAssert(
    $missingActualResult[
        'unavailable_players'
    ]
    ===
    1,
    'Missing realised return is counted as unavailable.'
);


intelligenceScoreBacktestMetricsAssert(
    $missingActualResult[
        'correlation'
    ]
    ===
    null,
    'Missing realised return is not manufactured as zero.'
);


/*
 * ============================================================
 * J. MIXED COMPARABLE AND UNAVAILABLE EVIDENCE
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'J. Mixed Comparable And Unavailable Evidence'
);


$mixedResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    50.0,

                'actual_points' =>
                    2
            ],
            [
                'intelligence_score' =>
                    null,

                'actual_points' =>
                    100
            ],
            [
                'intelligence_score' =>
                    70.0,

                'actual_points' =>
                    null
            ],
            [
                'intelligence_score' =>
                    90.0,

                'actual_points' =>
                    10
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $mixedResult[
        'total_players'
    ]
    ===
    4,
    'Mixed sample reports all valid player evidence rows.'
);


intelligenceScoreBacktestMetricsAssert(
    $mixedResult[
        'comparable_players'
    ]
    ===
    2,
    'Mixed sample uses only players with both primary evidence values.'
);


intelligenceScoreBacktestMetricsAssert(
    $mixedResult[
        'unavailable_players'
    ]
    ===
    2,
    'Mixed sample reports unavailable correlation evidence separately.'
);


intelligenceScoreBacktestMetricsAssert(
    abs(
        $mixedResult[
            'correlation'
        ]
        -
        1.0
    )
    <
    0.000000001,
    'Unavailable rows do not distort correlation.'
);


/*
 * ============================================================
 * K. CONSTANT INTELLIGENCE SCORES
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'K. Constant Intelligence Scores'
);


$constantScoreResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    70.0,

                'actual_points' =>
                    2
            ],
            [
                'intelligence_score' =>
                    70.0,

                'actual_points' =>
                    5
            ],
            [
                'intelligence_score' =>
                    70.0,

                'actual_points' =>
                    9
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $constantScoreResult[
        'comparable_players'
    ]
    ===
    3,
    'Constant-score rows remain comparable primary evidence.'
);


intelligenceScoreBacktestMetricsAssert(
    $constantScoreResult[
        'correlation'
    ]
    ===
    null,
    'Correlation is null when Intelligence Score has zero variance.'
);


/*
 * ============================================================
 * L. CONSTANT REALISED RETURNS
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'L. Constant Realised Returns'
);


$constantActualResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    60.0,

                'actual_points' =>
                    5
            ],
            [
                'intelligence_score' =>
                    70.0,

                'actual_points' =>
                    5
            ],
            [
                'intelligence_score' =>
                    80.0,

                'actual_points' =>
                    5
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $constantActualResult[
        'comparable_players'
    ]
    ===
    3,
    'Constant-return rows remain comparable primary evidence.'
);


intelligenceScoreBacktestMetricsAssert(
    $constantActualResult[
        'correlation'
    ]
    ===
    null,
    'Correlation is null when realised returns have zero variance.'
);


/*
 * ============================================================
 * M. NON-PERFECT CORRELATION
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'M. Non-Perfect Correlation'
);


$nonPerfectRows = [

    [
        'intelligence_score' =>
            50.0,

        'actual_points' =>
            2
    ],
    [
        'intelligence_score' =>
            60.0,

        'actual_points' =>
            7
    ],
    [
        'intelligence_score' =>
            70.0,

        'actual_points' =>
            4
    ],
    [
        'intelligence_score' =>
            80.0,

        'actual_points' =>
            10
    ]
];


$nonPerfectResult =
    $service->summarise(
        $nonPerfectRows
    );


$expectedNonPerfectCorrelation =
    0.7745966692414834;


intelligenceScoreBacktestMetricsAssert(
    $nonPerfectResult[
        'comparable_players'
    ]
    ===
    4,
    'Non-perfect sample includes all comparable players.'
);


intelligenceScoreBacktestMetricsAssert(
    abs(
        $nonPerfectResult[
            'correlation'
        ]
        -
        $expectedNonPerfectCorrelation
    )
    <
    0.000000001,
    'Non-perfect Pearson correlation is calculated correctly.'
);


/*
 * ============================================================
 * N. MALFORMED EVIDENCE ROWS
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'N. Malformed Evidence Rows'
);


$malformedResult =
    $service->summarise(
        [
            'invalid',

            [
                'intelligence_score' =>
                    50.0,

                'actual_points' =>
                    2
            ],

            null,

            [
                'intelligence_score' =>
                    80.0,

                'actual_points' =>
                    8
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $malformedResult[
        'total_players'
    ]
    ===
    2,
    'Non-array evidence rows are ignored.'
);


intelligenceScoreBacktestMetricsAssert(
    $malformedResult[
        'comparable_players'
    ]
    ===
    2,
    'Valid evidence remains comparable when malformed rows are present.'
);


intelligenceScoreBacktestMetricsAssert(
    $malformedResult[
        'unavailable_players'
    ]
    ===
    0,
    'Malformed rows do not create unavailable player evidence.'
);


intelligenceScoreBacktestMetricsAssert(
    abs(
        $malformedResult[
            'correlation'
        ]
        -
        1.0
    )
    <
    0.000000001,
    'Malformed rows do not distort correlation.'
);


/*
 * ============================================================
 * O. NUMERIC STRING EVIDENCE
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'O. Numeric String Evidence'
);


$numericStringResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    '50.0',

                'actual_points' =>
                    '2'
            ],
            [
                'intelligence_score' =>
                    '80.0',

                'actual_points' =>
                    '8'
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    $numericStringResult[
        'comparable_players'
    ]
    ===
    2,
    'Numeric-string primary evidence remains comparable.'
);


intelligenceScoreBacktestMetricsAssert(
    abs(
        $numericStringResult[
            'correlation'
        ]
        -
        1.0
    )
    <
    0.000000001,
    'Numeric-string primary evidence participates normally in correlation.'
);


/*
 * ============================================================
 * P. DERIVED EVIDENCE IS NOT USED
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'P. Derived Evidence Is Not Used'
);


$derivedResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    50.0,

                'actual_points' =>
                    2,

                /*
                 * Deliberately irrelevant derived evidence.
                 */
                'correlation' =>
                    -1.0
            ],
            [
                'intelligence_score' =>
                    80.0,

                'actual_points' =>
                    8,

                'correlation' =>
                    -1.0
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    abs(
        $derivedResult[
            'correlation'
        ]
        -
        1.0
    )
    <
    0.000000001,
    'Correlation is derived from primary historical score and realised return evidence.'
);


/*
 * ============================================================
 * Q. SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'Q. Source Evidence Remains Unchanged'
);


$immutabilityRows = [

    [
        'intelligence_score' =>
            60.0,

        'actual_points' =>
            3
    ],
    [
        'intelligence_score' =>
            80.0,

        'actual_points' =>
            9
    ]
];


$immutabilityBefore =
    $immutabilityRows;


$service->summarise(
    $immutabilityRows
);


intelligenceScoreBacktestMetricsAssert(
    $immutabilityRows
    ===
    $immutabilityBefore,
    'Calculating Intelligence Score correlation does not mutate backtest evidence.'
);


/*
 * ============================================================
 * R. EXACT OUTPUT CONTRACT
 * ============================================================
 */

echo "<br>";

intelligenceScoreBacktestMetricsSection(
    'R. Exact Output Contract'
);


$contractResult =
    $service->summarise(
        [
            [
                'intelligence_score' =>
                    60.0,

                'actual_points' =>
                    3
            ],
            [
                'intelligence_score' =>
                    80.0,

                'actual_points' =>
                    9
            ]
        ]
    );


intelligenceScoreBacktestMetricsAssert(
    array_keys(
        $contractResult
    )
    ===
    [
        'total_players',
        'comparable_players',
        'unavailable_players',
        'correlation'
    ],
    'Summary exposes only the defined initial Intelligence Score correlation contract.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";

echo "============================================<br>";
echo "Intelligence Score Backtest Metrics Service Test Summary<br>";
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