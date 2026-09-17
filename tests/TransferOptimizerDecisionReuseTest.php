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


/*
 * ============================================================
 * TEST DOUBLE
 * ============================================================
 *
 * Count how many complete player-based combination evaluations
 * the optimizer performs.
 *
 * After decision reuse is introduced, the optimizer should use
 * evaluatePreparedCombination() instead and this counter should
 * remain zero.
 */

class CountingTransferCombination extends TransferCombination
{
    public int $playerCombinationCalls =
        0;

    public int $preparedCombinationCalls =
        0;


    public function evaluateCombination(
        array $currentPlayerA,
        array $replacementA,
        array $currentPlayerB,
        array $replacementB
    ): array {

        $this->playerCombinationCalls++;


        return
            parent::evaluateCombination(
                $currentPlayerA,
                $replacementA,
                $currentPlayerB,
                $replacementB
            );
    }


    public function evaluatePreparedCombination(
        array $decisionA,
        array $decisionB
    ): array {

        $this->preparedCombinationCalls++;


        return
            parent::evaluatePreparedCombination(
                $decisionA,
                $decisionB
            );
    }
}


/*
 * ============================================================
 * PLAYER FACTORY
 * ============================================================
 */

function player(
    int $id,
    string $name,
    string $position,
    float $price,
    float $intelligence
): array {

    return [

        'player_id' =>
            $id,

        'name' =>
            $name,

        'position' =>
            $position,

        'team_name' =>
            'Test Team',

        'price' =>
            $price,

        'intelligence_score' =>
            $intelligence,

        'strength_rating' =>
            $intelligence,

        'value_rating' =>
            $intelligence,

        'fixture_rating' =>
            $intelligence,

        'sample_confidence' =>
            0.80,

        'verdict' =>
            'Test'
    ];
}


/*
 * ============================================================
 * DATA
 * ============================================================
 */

$currentA =
    player(
        1,
        'Current Defender',
        'DEF',
        6.0,
        60.0
    );


$currentB =
    player(
        2,
        'Current Midfielder',
        'MID',
        7.0,
        60.0
    );


$candidatePoolA = [

    player(
        11,
        'Defender A',
        'DEF',
        5.5,
        62.0
    ),

    player(
        12,
        'Defender B',
        'DEF',
        6.0,
        64.0
    ),

    player(
        13,
        'Defender C',
        'DEF',
        6.5,
        66.0
    )
];


$candidatePoolB = [

    player(
        21,
        'Midfielder A',
        'MID',
        6.5,
        62.0
    ),

    player(
        22,
        'Midfielder B',
        'MID',
        7.0,
        64.0
    ),

    player(
        23,
        'Midfielder C',
        'MID',
        7.5,
        66.0
    ),

    player(
        24,
        'Midfielder D',
        'MID',
        8.0,
        68.0
    )
];


/*
 * ============================================================
 * OPTIMIZER WITH COUNTING COMBINATION
 * ============================================================
 */

echo "============================================<br>";
echo "Transfer Optimizer Decision Reuse Test<br>";
echo "============================================<br><br>";


$optimizer =
    new TransferOptimizer();


$countingCombination =
    new CountingTransferCombination();


$reflection =
    new ReflectionProperty(
        TransferOptimizer::class,
        'transferCombination'
    );


$reflection->setAccessible(
    true
);


$reflection->setValue(
    $optimizer,
    $countingCombination
);


$result =
    $optimizer
        ->optimize(
            $currentA,
            $currentB,
            $candidatePoolA,
            $candidatePoolB,
            10.0,
            10
        );


/*
 * ============================================================
 * ASSERTIONS
 * ============================================================
 */

testPass(
    'Optimizer still returns a result',
    is_array(
        $result
    )
);


testPass(
    'All 12 candidate pairs remain discoverable',
    (
        $result[
            'total_found'
        ]
        ?? null
    )
    ===
    12
);


testPass(
    'Optimizer returns the requested top 10 combinations',
    (
        $result[
            'count'
        ]
        ?? null
    )
    ===
    10
);


/*
 * This is the key RED assertion.
 *
 * Current production code calls evaluateCombination() once for
 * every candidate pair, so this should initially fail.
 *
 * The optimized implementation must perform combination-level
 * work from prepared TransferDecision results instead.
 */
testPass(
    'Optimizer does not recalculate player-based combination decisions for every pair',
    $countingCombination
        ->playerCombinationCalls
    ===
    0
);


testPass(
    'Every candidate pair is still evaluated through the prepared combination path',
    $countingCombination
        ->preparedCombinationCalls
    ===
    12
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Summary<br>";
echo "============================================<br>";

echo "Player-based combination calls: "
    . $countingCombination
        ->playerCombinationCalls
    . "<br>";

echo "Prepared combination calls: "
    . $countingCombination
        ->preparedCombinationCalls
    . "<br>";

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