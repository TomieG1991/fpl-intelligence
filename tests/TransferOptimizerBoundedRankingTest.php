<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Optimizer Bounded Ranking Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * ASSERTION HELPERS
 * ============================================================
 */

function assertOptimizerRankerTrue(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


function assertOptimizerRankerSame(
    mixed $expected,
    mixed $actual,
    string $message
): void {

    assertOptimizerRankerTrue(
        $expected === $actual,
        $message
    );
}


/*
 * ============================================================
 * COUNTING RANKER
 * ============================================================
 */

class CountingTransferCombinationRanker
    extends TransferCombinationRanker
{

    public int $rankCalls =
        0;


    public int $lastInputCount =
        0;


    public ?int $lastLimit =
        null;


    public function rank(
        array $combinations,
        int $limit
    ): array {

        $this->rankCalls++;


        $this->lastInputCount =
            count(
                $combinations
            );


        $this->lastLimit =
            $limit;


        return
            parent::rank(
                $combinations,
                $limit
            );
    }
}


/*
 * ============================================================
 * CURRENT PLAYERS
 * ============================================================
 */

$currentPlayerA = [

    'player_id' =>
        1,

    'player_name' =>
        'Current DEF',

    'position' =>
        'DEF',

    'price' =>
        5.0,

    'intelligence_score' =>
        50.0,

    'strength_score' =>
        50.0,

    'value_score' =>
        50.0,

    'fixture_rating' =>
        50.0
];


$currentPlayerB = [

    'player_id' =>
        2,

    'player_name' =>
        'Current MID',

    'position' =>
        'MID',

    'price' =>
        5.0,

    'intelligence_score' =>
        50.0,

    'strength_score' =>
        50.0,

    'value_score' =>
        50.0,

    'fixture_rating' =>
        50.0
];


/*
 * ============================================================
 * CANDIDATE POOLS
 * ============================================================
 *
 * 3 DEF x 4 MID = 12 valid candidate pairs.
 * All candidates are deliberately affordable.
 * ============================================================
 */

$candidatePoolA = [];


for (
    $index = 0;
    $index < 3;
    $index++
) {

    $candidatePoolA[] = [

        'player_id' =>
            100 + $index,

        'player_name' =>
            'DEF '
            . ($index + 1),

        'position' =>
            'DEF',

        'price' =>
            4.0,

        'intelligence_score' =>
            51.0 + $index,

        'strength_score' =>
            51.0 + $index,

        'value_score' =>
            55.0 + $index,

        'fixture_rating' =>
            52.0 + $index
    ];
}


$candidatePoolB = [];


for (
    $index = 0;
    $index < 4;
    $index++
) {

    $candidatePoolB[] = [

        'player_id' =>
            200 + $index,

        'player_name' =>
            'MID '
            . ($index + 1),

        'position' =>
            'MID',

        'price' =>
            4.0,

        'intelligence_score' =>
            51.0 + $index,

        'strength_score' =>
            51.0 + $index,

        'value_score' =>
            55.0 + $index,

        'fixture_rating' =>
            52.0 + $index
    ];
}


/*
 * ============================================================
 * OPTIMIZER + INJECTED RANKER
 * ============================================================
 */

$optimizer =
    new TransferOptimizer();


$countingRanker =
    new CountingTransferCombinationRanker();


try {

    $reflection =
        new ReflectionClass(
            $optimizer
        );


    $property =
        $reflection
            ->getProperty(
                'transferCombinationRanker'
            );


    $property
        ->setAccessible(
            true
        );


    $property
        ->setValue(
            $optimizer,
            $countingRanker
        );


    $result =
        $optimizer
            ->optimize(
                $currentPlayerA,
                $currentPlayerB,
                $candidatePoolA,
                $candidatePoolB,
                10.0,
                5
            );


    /*
     * ========================================================
     * ASSERTIONS
     * ========================================================
     */

    assertOptimizerRankerSame(
        12,
        $result[
            'total_found'
        ]
        ?? null,
        'All 12 affordable combinations remain discoverable'
    );


    assertOptimizerRankerSame(
        5,
        $result[
            'count'
        ]
        ?? null,
        'Optimizer still returns the requested top five'
    );


    assertOptimizerRankerSame(
        1,
        $countingRanker
            ->rankCalls,
        'Optimizer delegates final ranking to TransferCombinationRanker exactly once'
    );


    assertOptimizerRankerSame(
        12,
        $countingRanker
            ->lastInputCount,
        'Every affordable combination reaches the bounded ranking boundary'
    );


    assertOptimizerRankerSame(
        5,
        $countingRanker
            ->lastLimit,
        'Optimizer forwards the requested result limit unchanged'
    );


} catch (Throwable $exception) {

    echo "EXPECTED RED: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}