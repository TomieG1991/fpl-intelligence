<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Starting XI Selection Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function startingXISelectionBacktestingTestResult(
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

startingXISelectionBacktestingTestResult(
    class_exists(
        'StartingXISelectionBacktestingService'
    ),
    'StartingXISelectionBacktestingService exists.'
);


if (
    !class_exists(
        'StartingXISelectionBacktestingService'
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
    new StartingXISelectionBacktestingService();


startingXISelectionBacktestingTestResult(
    $service
        instanceof StartingXISelectionBacktestingService,
    'StartingXISelectionBacktestingService can be constructed.'
);


/*
 * ============================================================
 * PRESERVED 15-PLAYER SQUAD
 * ============================================================
 *
 * Recommended XI formation:
 *
 * 1 GK
 * 3 DEF
 * 4 MID
 * 3 FWD
 *
 * Bench:
 *
 * 1 GK
 * 2 DEF
 * 1 MID
 *
 * Bench Defender 202 scores 9.
 * Bench Midfielder 203 scores 8.
 *
 * The best legal XI should therefore replace:
 *
 * DEF 103 (1) -> DEF 202 (9)
 * MID 107 (2) -> MID 203 (8)
 *
 * giving 14 additional realised points.
 *
 * Bench goalkeeper 201 scores 20, but cannot replace an
 * outfield player. The recommended goalkeeper 101 scores 5,
 * so the best legal XI can use goalkeeper 201 instead.
 *
 * That adds another 15 points.
 *
 * Total selection points lost = 29.
 * ============================================================
 */

$startingXI = [

    [
        'player_id' => 101,
        'name' => 'Starting Goalkeeper',
        'position' => 'GK'
    ],

    [
        'player_id' => 102,
        'name' => 'Defender One',
        'position' => 'DEF'
    ],

    [
        'player_id' => 103,
        'name' => 'Defender Two',
        'position' => 'DEF'
    ],

    [
        'player_id' => 104,
        'name' => 'Defender Three',
        'position' => 'DEF'
    ],

    [
        'player_id' => 105,
        'name' => 'Midfielder One',
        'position' => 'MID'
    ],

    [
        'player_id' => 106,
        'name' => 'Midfielder Two',
        'position' => 'MID'
    ],

    [
        'player_id' => 107,
        'name' => 'Midfielder Three',
        'position' => 'MID'
    ],

    [
        'player_id' => 108,
        'name' => 'Midfielder Four',
        'position' => 'MID'
    ],

    [
        'player_id' => 109,
        'name' => 'Forward One',
        'position' => 'FWD'
    ],

    [
        'player_id' => 110,
        'name' => 'Forward Two',
        'position' => 'FWD'
    ],

    [
        'player_id' => 111,
        'name' => 'Forward Three',
        'position' => 'FWD'
    ]
];


$bench = [

    [
        'player_id' => 201,
        'name' => 'Bench Goalkeeper',
        'position' => 'GK'
    ],

    [
        'player_id' => 202,
        'name' => 'Bench Defender',
        'position' => 'DEF'
    ],

    [
        'player_id' => 203,
        'name' => 'Bench Midfielder',
        'position' => 'MID'
    ],

    [
        'player_id' => 204,
        'name' => 'Bench Defender Two',
        'position' => 'DEF'
    ]
];


$playerOutcomes = [

    [
        'player_id' => 101,
        'total_points' => 5,
        'minutes' => 90
    ],

    [
        'player_id' => 102,
        'total_points' => 6,
        'minutes' => 90
    ],

    [
        'player_id' => 103,
        'total_points' => 1,
        'minutes' => 90
    ],

    [
        'player_id' => 104,
        'total_points' => 5,
        'minutes' => 90
    ],

    [
        'player_id' => 105,
        'total_points' => 7,
        'minutes' => 90
    ],

    [
        'player_id' => 106,
        'total_points' => 6,
        'minutes' => 90
    ],

    [
        'player_id' => 107,
        'total_points' => 2,
        'minutes' => 90
    ],

    [
        'player_id' => 108,
        'total_points' => 5,
        'minutes' => 90
    ],

    [
        'player_id' => 109,
        'total_points' => 8,
        'minutes' => 90
    ],

    [
        'player_id' => 110,
        'total_points' => 7,
        'minutes' => 90
    ],

    [
        'player_id' => 111,
        'total_points' => 6,
        'minutes' => 90
    ],

    [
        'player_id' => 201,
        'total_points' => 20,
        'minutes' => 90
    ],

    [
        'player_id' => 202,
        'total_points' => 9,
        'minutes' => 90
    ],

    [
        'player_id' => 203,
        'total_points' => 8,
        'minutes' => 90
    ],

    [
        'player_id' => 204,
        'total_points' => 0,
        'minutes' => 0
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
        [],
        $playerOutcomes
    );


startingXISelectionBacktestingTestResult(
    $result === [],
    'Empty preserved squad returns no selection evaluation.'
);


$result =
    $service->evaluate(
        $startingXI,
        $bench,
        []
    );


startingXISelectionBacktestingTestResult(
    $result === [],
    'Empty outcome evidence returns no selection evaluation.'
);


/*
 * ============================================================
 * SCENARIO B
 * RECOMMENDED XI REALISED POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Recommended XI Realised Points<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $startingXI,
        $bench,
        $playerOutcomes
    );


$expectedRecommendedPoints =
    5
    + 6
    + 1
    + 5
    + 7
    + 6
    + 2
    + 5
    + 8
    + 7
    + 6;


startingXISelectionBacktestingTestResult(
    (
        $result[
            'recommended_xi_points'
        ]
        ?? null
    )
    ===
    $expectedRecommendedPoints,
    'Recommended XI realised points are calculated correctly.'
);


/*
 * ============================================================
 * SCENARIO C
 * BEST LEGAL XI
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Best Legal XI<br>";
echo "============================================<br>";


$bestLegalXI =
    $result[
        'best_legal_xi'
    ]
    ?? [];


startingXISelectionBacktestingTestResult(
    count(
        $bestLegalXI
    )
    === 11,
    'Best legal XI contains exactly eleven players.'
);


$positionCounts = [

    'GK' => 0,
    'DEF' => 0,
    'MID' => 0,
    'FWD' => 0
];


foreach (
    $bestLegalXI
    as $player
) {

    $position =
        $player[
            'position'
        ]
        ?? null;


    if (
        array_key_exists(
            $position,
            $positionCounts
        )
    ) {

        $positionCounts[
            $position
        ]++;
    }
}


startingXISelectionBacktestingTestResult(
    $positionCounts[
        'GK'
    ]
    === 1,
    'Best legal XI contains exactly one goalkeeper.'
);


startingXISelectionBacktestingTestResult(
    $positionCounts[
        'DEF'
    ]
    >= 3
    &&
    $positionCounts[
        'DEF'
    ]
    <= 5,
    'Best legal XI contains between three and five defenders.'
);


startingXISelectionBacktestingTestResult(
    $positionCounts[
        'MID'
    ]
    >= 2
    &&
    $positionCounts[
        'MID'
    ]
    <= 5,
    'Best legal XI contains between two and five midfielders.'
);


startingXISelectionBacktestingTestResult(
    $positionCounts[
        'FWD'
    ]
    >= 1
    &&
    $positionCounts[
        'FWD'
    ]
    <= 3,
    'Best legal XI contains between one and three forwards.'
);


/*
 * ============================================================
 * SCENARIO D
 * BEST LEGAL XI SELECTS STRONGER BENCH OPTIONS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Stronger Bench Options<br>";
echo "============================================<br>";


$bestLegalPlayerIds =
    array_column(
        $bestLegalXI,
        'player_id'
    );


startingXISelectionBacktestingTestResult(
    in_array(
        201,
        $bestLegalPlayerIds,
        true
    ),
    'Higher-scoring bench goalkeeper is selected into best legal XI.'
);


startingXISelectionBacktestingTestResult(
    in_array(
        202,
        $bestLegalPlayerIds,
        true
    ),
    'Higher-scoring bench defender is selected into best legal XI.'
);


startingXISelectionBacktestingTestResult(
    in_array(
        203,
        $bestLegalPlayerIds,
        true
    ),
    'Higher-scoring bench midfielder is selected into best legal XI.'
);


startingXISelectionBacktestingTestResult(
    !in_array(
        101,
        $bestLegalPlayerIds,
        true
    ),
    'Lower-scoring starting goalkeeper is excluded from best legal XI.'
);


startingXISelectionBacktestingTestResult(
    !in_array(
        103,
        $bestLegalPlayerIds,
        true
    ),
    'Lower-scoring starting defender is excluded from best legal XI.'
);


startingXISelectionBacktestingTestResult(
    !in_array(
        107,
        $bestLegalPlayerIds,
        true
    ),
    'Lower-scoring starting midfielder is excluded from best legal XI.'
);


/*
 * ============================================================
 * SCENARIO E
 * SELECTION POINTS LOST
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Selection Points Lost<br>";
echo "============================================<br>";


$expectedBestLegalPoints =
    $expectedRecommendedPoints
    +
    15
    +
    8
    +
    6;


startingXISelectionBacktestingTestResult(
    (
        $result[
            'best_legal_xi_points'
        ]
        ?? null
    )
    ===
    $expectedBestLegalPoints,
    'Best legal XI realised points are calculated correctly.'
);


startingXISelectionBacktestingTestResult(
    (
        $result[
            'selection_points_lost'
        ]
        ?? null
    )
    === 29,
    'Selection points lost equals best legal XI points minus recommended XI points.'
);


/*
 * ============================================================
 * SCENARIO F
 * PERFECT RECOMMENDATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Perfect Recommended XI<br>";
echo "============================================<br>";


$perfectStartingXI = [

    $bench[0],
    $startingXI[1],
    $bench[1],
    $startingXI[3],
    $startingXI[4],
    $startingXI[5],
    $bench[2],
    $startingXI[7],
    $startingXI[8],
    $startingXI[9],
    $startingXI[10]
];


$perfectBench = [

    $startingXI[0],
    $startingXI[2],
    $startingXI[6],
    $bench[3]
];


$perfectResult =
    $service->evaluate(
        $perfectStartingXI,
        $perfectBench,
        $playerOutcomes
    );


startingXISelectionBacktestingTestResult(
    (
        $perfectResult[
            'selection_points_lost'
        ]
        ?? null
    )
    === 0,
    'Best legal recommendation records zero selection points lost.'
);


/*
 * ============================================================
 * SCENARIO G
 * GOALKEEPER CANNOT REPLACE OUTFIELD PLAYER
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Goalkeeper Position Legality<br>";
echo "============================================<br>";


$goalkeeperCount =
    0;


foreach (
    $bestLegalXI
    as $player
) {

    if (
        (
            $player[
                'position'
            ]
            ?? null
        )
        ===
        'GK'
    ) {

        $goalkeeperCount++;
    }
}


startingXISelectionBacktestingTestResult(
    $goalkeeperCount === 1,
    'High-scoring goalkeeper cannot occupy an outfield place.'
);


/*
 * ============================================================
 * SCENARIO H
 * FORMATION CHANGE IS ALLOWED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Legal Formation May Change<br>";
echo "============================================<br>";


startingXISelectionBacktestingTestResult(
    $positionCounts[
        'DEF'
    ]
        +
    $positionCounts[
        'MID'
    ]
        +
    $positionCounts[
        'FWD'
    ]
    === 10,
    'Best legal XI contains ten outfield players regardless of recommended formation.'
);


/*
 * ============================================================
 * SCENARIO I
 * ZERO AND NEGATIVE POINTS REMAIN VALID
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Zero And Negative Points<br>";
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
        === 204
    ) {

        $negativeOutcomes[
            $index
        ][
            'total_points'
        ] =
            -2;
    }
}


$negativeResult =
    $service->evaluate(
        $startingXI,
        $bench,
        $negativeOutcomes
    );


startingXISelectionBacktestingTestResult(
    !empty(
        $negativeResult
    ),
    'Negative realised points remain valid selection evidence.'
);


/*
 * ============================================================
 * SCENARIO J
 * MISSING OUTCOME PREVENTS COMPLETE LEGAL EVALUATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Incomplete Squad Outcome Evidence<br>";
echo "============================================<br>";


$incompleteOutcomes =
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
                    !== 204;
            }
        )
    );


$incompleteResult =
    $service->evaluate(
        $startingXI,
        $bench,
        $incompleteOutcomes
    );


startingXISelectionBacktestingTestResult(
    $incompleteResult === [],
    'Missing outcome for preserved squad player prevents legal-XI comparison.'
);


/*
 * ============================================================
 * SCENARIO K
 * INVALID POSITION PREVENTS LEGAL EVALUATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Invalid Position Evidence<br>";
echo "============================================<br>";


$invalidBench =
    $bench;


$invalidBench[
    1
][
    'position'
] =
    'UNKNOWN';


$invalidPositionResult =
    $service->evaluate(
        $startingXI,
        $invalidBench,
        $playerOutcomes
    );


startingXISelectionBacktestingTestResult(
    $invalidPositionResult === [],
    'Invalid preserved player position prevents legal-XI comparison.'
);


/*
 * ============================================================
 * SCENARIO L
 * COMPLETE FIFTEEN-PLAYER SQUAD REQUIRED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario L: Complete Preserved Squad<br>";
echo "============================================<br>";


$shortBench =
    array_slice(
        $bench,
        0,
        3
    );


$shortSquadResult =
    $service->evaluate(
        $startingXI,
        $shortBench,
        $playerOutcomes
    );


startingXISelectionBacktestingTestResult(
    $shortSquadResult === [],
    'Legal-XI comparison requires the complete preserved 15-player squad.'
);


/*
 * ============================================================
 * SCENARIO M
 * DUPLICATE PLAYER ID PREVENTS LEGAL EVALUATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario M: Duplicate Player Identity<br>";
echo "============================================<br>";


$duplicateBench =
    $bench;


$duplicateBench[
    0
][
    'player_id'
] =
    101;


$duplicateResult =
    $service->evaluate(
        $startingXI,
        $duplicateBench,
        $playerOutcomes
    );


startingXISelectionBacktestingTestResult(
    $duplicateResult === [],
    'Duplicate preserved player identity prevents legal-XI comparison.'
);


/*
 * ============================================================
 * SCENARIO N
 * ILLEGAL RECOMMENDED XI
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario N: Illegal Recommended XI<br>";
echo "============================================<br>";


/*
 * Move the bench goalkeeper into the recommended XI
 * in place of a defender.
 *
 * The resulting recommended XI contains:
 *
 * 2 GK
 * 2 DEF
 * 4 MID
 * 3 FWD
 *
 * It is therefore not a legal FPL Starting XI.
 */
$illegalStartingXI =
    $startingXI;


$illegalBench =
    $bench;


$illegalStartingXI[
    1
] =
    $bench[
        0
    ];


$illegalBench[
    0
] =
    $startingXI[
        1
    ];


$illegalRecommendedResult =
    $service->evaluate(
        $illegalStartingXI,
        $illegalBench,
        $playerOutcomes
    );


startingXISelectionBacktestingTestResult(
    $illegalRecommendedResult === [],
    'Illegal preserved recommended XI prevents selection-quality comparison.'
);


/*
 * ============================================================
 * SCENARIO O
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario O: Source Evidence Remains Unchanged<br>";
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


startingXISelectionBacktestingTestResult(
    $sourceStartingXI
        ===
        $originalStartingXI,
    'Preserved Starting XI evidence is not mutated.'
);


startingXISelectionBacktestingTestResult(
    $sourceBench
        ===
        $originalBench,
    'Preserved bench evidence is not mutated.'
);


startingXISelectionBacktestingTestResult(
    $sourceOutcomes
        ===
        $originalOutcomes,
    'Authoritative outcome evidence is not mutated.'
);


/*
 * ============================================================
 * SCENARIO P
 * SELECTION QUALITY BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario P: Selection Quality Boundary<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $startingXI,
        $bench,
        $playerOutcomes
    );


startingXISelectionBacktestingTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Legal-XI evaluator does not manufacture an accuracy score.'
);


startingXISelectionBacktestingTestResult(
    !array_key_exists(
        'automatic_substitutions',
        $result
    ),
    'Legal-XI evaluator does not simulate automatic substitutions.'
);


startingXISelectionBacktestingTestResult(
    !array_key_exists(
        'captain_result',
        $result
    ),
    'Legal-XI evaluator does not evaluate captain recommendation.'
);


startingXISelectionBacktestingTestResult(
    !array_key_exists(
        'transfer_result',
        $result
    ),
    'Legal-XI evaluator does not evaluate transfer recommendation.'
);


startingXISelectionBacktestingTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Legal-XI evaluator does not manufacture overall backtesting score.'
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