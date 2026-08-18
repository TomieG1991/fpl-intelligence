<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function testPass(
    string $message,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . $message
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . $message
        . "<br>";

    $failed++;
}


$optimizer =
    new TransferOptimizer();


echo "============================================<br>";
echo "Transfer Optimizer Test<br>";
echo "============================================<br>";


/*
 * ============================================================
 * CURRENT PLAYERS
 * ============================================================
 */

$currentPlayerA = [

    'player_id' => 1,
    'name' => 'Current Mid',
    'position' => 'MID',
    'price' => 10.0,
    'intelligence_score' => 65.0,
    'strength_rating' => 62.0,
    'value_rating' => 45.0,
    'fixture_rating' => 60.0,
    'sample_confidence' => 1.0
];


$currentPlayerB = [

    'player_id' => 2,
    'name' => 'Current Forward',
    'position' => 'FWD',
    'price' => 6.0,
    'intelligence_score' => 55.0,
    'strength_rating' => 52.0,
    'value_rating' => 60.0,
    'fixture_rating' => 55.0,
    'sample_confidence' => 1.0
];


/*
 * ============================================================
 * CANDIDATE POOL A - MIDFIELDERS
 * ============================================================
 */

$candidatePoolA = [

    [
        'player_id' => 10,
        'name' => 'Mid Upgrade',
        'position' => 'MID',
        'price' => 9.0,
        'intelligence_score' => 70.0,
        'strength_rating' => 68.0,
        'value_rating' => 60.0,
        'fixture_rating' => 70.0,
        'sample_confidence' => 1.0
    ],

    [
        'player_id' => 11,
        'name' => 'Mid Value',
        'position' => 'MID',
        'price' => 7.0,
        'intelligence_score' => 63.0,
        'strength_rating' => 60.0,
        'value_rating' => 78.0,
        'fixture_rating' => 68.0,
        'sample_confidence' => 1.0
    ],

    /*
     * Wrong position - must be ignored.
     */
    [
        'player_id' => 12,
        'name' => 'Wrong Position Forward',
        'position' => 'FWD',
        'price' => 7.0,
        'intelligence_score' => 90.0,
        'strength_rating' => 90.0,
        'value_rating' => 90.0,
        'fixture_rating' => 90.0,
        'sample_confidence' => 1.0
    ],

    /*
     * Current player A - must be ignored.
     */
    [
        'player_id' => 1,
        'name' => 'Current Mid',
        'position' => 'MID',
        'price' => 10.0,
        'intelligence_score' => 65.0,
        'strength_rating' => 62.0,
        'value_rating' => 45.0,
        'fixture_rating' => 60.0,
        'sample_confidence' => 1.0
    ]
];


/*
 * ============================================================
 * CANDIDATE POOL B - FORWARDS
 * ============================================================
 */

$candidatePoolB = [

    [
        'player_id' => 20,
        'name' => 'Forward Upgrade',
        'position' => 'FWD',
        'price' => 7.0,
        'intelligence_score' => 63.0,
        'strength_rating' => 60.0,
        'value_rating' => 62.0,
        'fixture_rating' => 65.0,
        'sample_confidence' => 1.0
    ],

    [
        'player_id' => 21,
        'name' => 'Forward Budget',
        'position' => 'FWD',
        'price' => 5.0,
        'intelligence_score' => 58.0,
        'strength_rating' => 55.0,
        'value_rating' => 72.0,
        'fixture_rating' => 62.0,
        'sample_confidence' => 1.0
    ],

    /*
     * Wrong position - must be ignored.
     */
    [
        'player_id' => 22,
        'name' => 'Wrong Position Mid',
        'position' => 'MID',
        'price' => 5.0,
        'intelligence_score' => 90.0,
        'strength_rating' => 90.0,
        'value_rating' => 90.0,
        'fixture_rating' => 90.0,
        'sample_confidence' => 1.0
    ],

    /*
     * Current player B - must be ignored.
     */
    [
        'player_id' => 2,
        'name' => 'Current Forward',
        'position' => 'FWD',
        'price' => 6.0,
        'intelligence_score' => 55.0,
        'strength_rating' => 52.0,
        'value_rating' => 60.0,
        'fixture_rating' => 55.0,
        'sample_confidence' => 1.0
    ]
];


/*
 * ============================================================
 * SCENARIO A
 * STANDARD OPTIMIZATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Standard Optimization<br>";
echo "============================================<br>";


$result =
    $optimizer
        ->optimize(
            $currentPlayerA,
            $currentPlayerB,
            $candidatePoolA,
            $candidatePoolB,
            0.0,
            10
        );


testPass(
    'Optimizer returns an array',
    is_array(
        $result
    )
);


testPass(
    'Optimizer returns combinations array',
    isset(
        $result[
            'combinations'
        ]
    )
    &&
    is_array(
        $result[
            'combinations'
        ]
    )
);


testPass(
    'Optimizer finds valid affordable combinations',
    (
        $result[
            'total_found'
        ]
        ?? 0
    )
    > 0
);


testPass(
    'Returned count matches combinations array',
    (
        $result[
            'count'
        ]
        ?? -1
    )
    ===
    count(
        $result[
            'combinations'
        ]
    )
);


/*
 * ============================================================
 * SCENARIO B
 * SAME-POSITION FILTERING
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Position Filtering<br>";
echo "============================================<br>";


$wrongPositionFound =
    false;


foreach (
    $result[
        'combinations'
    ]
    as $combination
) {

    $incomingA =
        $combination[
            'transfer_a'
        ]['replacement']
        ?? [];


    $incomingB =
        $combination[
            'transfer_b'
        ]['replacement']
        ?? [];


    if (
        (
            $incomingA[
                'position'
            ]
            ?? null
        )
        !== 'MID'
        ||
        (
            $incomingB[
                'position'
            ]
            ?? null
        )
        !== 'FWD'
    ) {

        $wrongPositionFound =
            true;

        break;
    }
}


testPass(
    'Wrong-position candidates are excluded',
    $wrongPositionFound
    === false
);


/*
 * ============================================================
 * SCENARIO C
 * UNAFFORDABLE COMBINATIONS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Affordability Filtering<br>";
echo "============================================<br>";


$expensivePoolA = [

    [
        'player_id' => 30,
        'name' => 'Expensive Mid',
        'position' => 'MID',
        'price' => 13.0,
        'intelligence_score' => 75.0,
        'strength_rating' => 72.0,
        'value_rating' => 50.0,
        'fixture_rating' => 72.0,
        'sample_confidence' => 1.0
    ]
];


$expensivePoolB = [

    [
        'player_id' => 31,
        'name' => 'Expensive Forward',
        'position' => 'FWD',
        'price' => 8.0,
        'intelligence_score' => 68.0,
        'strength_rating' => 65.0,
        'value_rating' => 55.0,
        'fixture_rating' => 68.0,
        'sample_confidence' => 1.0
    ]
];


$unaffordable =
    $optimizer
        ->optimize(
            $currentPlayerA,
            $currentPlayerB,
            $expensivePoolA,
            $expensivePoolB,
            0.0,
            10
        );


testPass(
    'Unaffordable combinations are filtered out',
    (
        $unaffordable[
            'count'
        ]
        ?? -1
    )
    === 0
);


/*
 * ============================================================
 * SCENARIO D
 * BANK MAKES COMBINATION AFFORDABLE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Bank Support<br>";
echo "============================================<br>";


$withBank =
    $optimizer
        ->optimize(
            $currentPlayerA,
            $currentPlayerB,
            $expensivePoolA,
            $expensivePoolB,
            5.0,
            10
        );


testPass(
    'Existing bank can make combination affordable',
    (
        $withBank[
            'count'
        ]
        ?? 0
    )
    === 1
);


if (
    !empty(
        $withBank[
            'combinations'
        ]
    )
) {

    $bankCombination =
        $withBank[
            'combinations'
        ][0];


    testPass(
        'Bank before transfer is preserved',
        (
            $bankCombination[
                'optimizer'
            ]['bank_before']
            ?? null
        )
        === 5.00
    );


    testPass(
        'Budget after transfer is calculated correctly',
        (
            $bankCombination[
                'optimizer'
            ]['budget_after']
            ?? null
        )
        === 0.00
    );
}


/*
 * ============================================================
 * SCENARIO E
 * DUPLICATE INCOMING PLAYER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Duplicate Incoming Player<br>";
echo "============================================<br>";


$samePositionCurrentA = [

    'player_id' => 40,
    'name' => 'Current Mid A',
    'position' => 'MID',
    'price' => 8.0,
    'intelligence_score' => 60.0,
    'strength_rating' => 60.0,
    'value_rating' => 60.0,
    'fixture_rating' => 60.0,
    'sample_confidence' => 1.0
];


$samePositionCurrentB = [

    'player_id' => 41,
    'name' => 'Current Mid B',
    'position' => 'MID',
    'price' => 8.0,
    'intelligence_score' => 60.0,
    'strength_rating' => 60.0,
    'value_rating' => 60.0,
    'fixture_rating' => 60.0,
    'sample_confidence' => 1.0
];


$sharedIncoming = [

    'player_id' => 42,
    'name' => 'Shared Incoming Mid',
    'position' => 'MID',
    'price' => 7.0,
    'intelligence_score' => 65.0,
    'strength_rating' => 65.0,
    'value_rating' => 65.0,
    'fixture_rating' => 65.0,
    'sample_confidence' => 1.0
];


$duplicateIncomingResult =
    $optimizer
        ->optimize(
            $samePositionCurrentA,
            $samePositionCurrentB,
            [
                $sharedIncoming
            ],
            [
                $sharedIncoming
            ],
            0.0,
            10
        );


testPass(
    'Same incoming player cannot fill both slots',
    (
        $duplicateIncomingResult[
            'count'
        ]
        ?? -1
    )
    === 0
);


/*
 * ============================================================
 * SCENARIO F
 * RESULT RANKING
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Combination Ranking<br>";
echo "============================================<br>";


$rankedCombinations =
    $result[
        'combinations'
    ]
    ?? [];


$rankingValid =
    true;


for (
    $i = 1;
    $i < count(
        $rankedCombinations
    );
    $i++
) {

    $previousRank =
        $rankedCombinations[
            $i - 1
        ]['optimizer']['rank']
        ?? null;


    $currentRank =
        $rankedCombinations[
            $i
        ]['optimizer']['rank']
        ?? null;


    if (
        $previousRank !== $i
        ||
        $currentRank !== $i + 1
    ) {

        $rankingValid =
            false;

        break;
    }
}


testPass(
    'Returned combinations have sequential ranks',
    $rankingValid
);


/*
 * Strongest combination should be first according to the
 * optimizer's ranking rules.
 */

if (
    count(
        $rankedCombinations
    )
    >= 2
) {

    $firstClassification =
        $rankedCombinations[
            0
        ]['classification']
        ?? '';


    $secondClassification =
        $rankedCombinations[
            1
        ]['classification']
        ?? '';


    testPass(
        'Highest-ranked combination has a valid classification',
        $firstClassification
        !== ''
    );


    testPass(
        'Second-ranked combination has a valid classification',
        $secondClassification
        !== ''
    );
}


/*
 * ============================================================
 * SCENARIO G
 * RESULT LIMIT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Result Limit<br>";
echo "============================================<br>";


$limitedResult =
    $optimizer
        ->optimize(
            $currentPlayerA,
            $currentPlayerB,
            $candidatePoolA,
            $candidatePoolB,
            0.0,
            1
        );


testPass(
    'Optimizer respects requested result limit',
    (
        $limitedResult[
            'count'
        ]
        ?? 0
    )
    <= 1
);


/*
 * ============================================================
 * SCENARIO H
 * INVALID INPUT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Invalid Input<br>";
echo "============================================<br>";


$negativeBank =
    $optimizer
        ->optimize(
            $currentPlayerA,
            $currentPlayerB,
            $candidatePoolA,
            $candidatePoolB,
            -1.0,
            10
        );


testPass(
    'Negative bank returns empty result',
    (
        $negativeBank[
            'count'
        ]
        ?? -1
    )
    === 0
);


$zeroLimit =
    $optimizer
        ->optimize(
            $currentPlayerA,
            $currentPlayerB,
            $candidatePoolA,
            $candidatePoolB,
            0.0,
            0
        );


testPass(
    'Zero limit returns empty result',
    (
        $zeroLimit[
            'count'
        ]
        ?? -1
    )
    === 0
);


$duplicateOutgoing =
    $optimizer
        ->optimize(
            $currentPlayerA,
            $currentPlayerA,
            $candidatePoolA,
            $candidatePoolB,
            0.0,
            10
        );


testPass(
    'Duplicate outgoing player returns empty result',
    (
        $duplicateOutgoing[
            'count'
        ]
        ?? -1
    )
    === 0
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Transfer Optimizer Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}