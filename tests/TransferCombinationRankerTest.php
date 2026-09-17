<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Combination Ranker Test<br>";
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

function assertRankerTrue(
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


function assertRankerSame(
    mixed $expected,
    mixed $actual,
    string $message
): void {

    assertRankerTrue(
        $expected === $actual,
        $message
    );
}


/*
 * ============================================================
 * AUTHORITATIVE EXISTING COMPARATOR
 * ============================================================
 *
 * This deliberately reproduces the current TransferOptimizer
 * ranking contract:
 *
 * 1. Classification
 * 2. Combination score
 * 3. Intelligence movement
 * 4. Remaining budget
 *
 * The expected result is produced by:
 *
 * usort(...)
 * array_slice(...)
 *
 * The new ranker must produce exactly the same result.
 * ============================================================
 */

function transferRankerClassificationWeight(
    mixed $classification
): int {

    return match (
        strtolower(
            trim(
                (string) $classification
            )
        )
    ) {

        'strong improvement' =>
            6,

        'improvement' =>
            5,

        'balanced restructure' =>
            4,

        'neutral restructure' =>
            3,

        'risky restructure' =>
            2,

        'downgrade' =>
            1,

        'unaffordable' =>
            0,

        'insufficient data' =>
            -1,

        default =>
            0
    };
}


function transferRankerNumericValue(
    mixed $value
): float {

    if (
        $value === null
        ||
        !is_numeric($value)
    ) {

        return -999999.0;
    }


    return (float) $value;
}


function transferRankerCompare(
    array $a,
    array $b
): int {

    $classificationA =
        transferRankerClassificationWeight(
            $a[
                'classification'
            ]
            ?? null
        );


    $classificationB =
        transferRankerClassificationWeight(
            $b[
                'classification'
            ]
            ?? null
        );


    if (
        $classificationA
        !==
        $classificationB
    ) {

        return
            $classificationB
            <=>
            $classificationA;
    }


    $scoreA =
        transferRankerNumericValue(
            $a[
                'combination_score'
            ]
            ?? null
        );


    $scoreB =
        transferRankerNumericValue(
            $b[
                'combination_score'
            ]
            ?? null
        );


    if ($scoreA !== $scoreB) {

        return
            $scoreB
            <=>
            $scoreA;
    }


    $intelligenceA =
        transferRankerNumericValue(
            $a[
                'combined_movements'
            ]['intelligence']
            ?? null
        );


    $intelligenceB =
        transferRankerNumericValue(
            $b[
                'combined_movements'
            ]['intelligence']
            ?? null
        );


    if (
        $intelligenceA
        !==
        $intelligenceB
    ) {

        return
            $intelligenceB
            <=>
            $intelligenceA;
    }


    $budgetA =
        transferRankerNumericValue(
            $a[
                'optimizer'
            ]['budget_after']
            ?? null
        );


    $budgetB =
        transferRankerNumericValue(
            $b[
                'optimizer'
            ]['budget_after']
            ?? null
        );


    return
        $budgetB
        <=>
        $budgetA;
}


/*
 * ============================================================
 * TEST DATA
 * ============================================================
 */

$combinations = [

    [
        'key' =>
            'downgrade-high-score',

        'classification' =>
            'Downgrade',

        'combination_score' =>
            99.0,

        'combined_movements' => [
            'intelligence' =>
                20.0
        ],

        'optimizer' => [
            'budget_after' =>
                8.0
        ]
    ],

    [
        'key' =>
            'strong-low-score',

        'classification' =>
            'Strong Improvement',

        'combination_score' =>
            60.0,

        'combined_movements' => [
            'intelligence' =>
                2.0
        ],

        'optimizer' => [
            'budget_after' =>
                1.0
        ]
    ],

    [
        'key' =>
            'strong-high-score',

        'classification' =>
            'Strong Improvement',

        'combination_score' =>
            90.0,

        'combined_movements' => [
            'intelligence' =>
                1.0
        ],

        'optimizer' => [
            'budget_after' =>
                0.5
        ]
    ],

    [
        'key' =>
            'strong-score-intelligence',

        'classification' =>
            'Strong Improvement',

        'combination_score' =>
            80.0,

        'combined_movements' => [
            'intelligence' =>
                10.0
        ],

        'optimizer' => [
            'budget_after' =>
                0.5
        ]
    ],

    [
        'key' =>
            'strong-score-intelligence-budget-low',

        'classification' =>
            'Strong Improvement',

        'combination_score' =>
            80.0,

        'combined_movements' => [
            'intelligence' =>
                5.0
        ],

        'optimizer' => [
            'budget_after' =>
                1.0
        ]
    ],

    [
        'key' =>
            'strong-score-intelligence-budget-high',

        'classification' =>
            'Strong Improvement',

        'combination_score' =>
            80.0,

        'combined_movements' => [
            'intelligence' =>
                5.0
        ],

        'optimizer' => [
            'budget_after' =>
                4.0
        ]
    ],

    [
        'key' =>
            'improvement-best',

        'classification' =>
            'Improvement',

        'combination_score' =>
            100.0,

        'combined_movements' => [
            'intelligence' =>
                50.0
        ],

        'optimizer' => [
            'budget_after' =>
                10.0
        ]
    ],

    [
        'key' =>
            'neutral',

        'classification' =>
            'Neutral Restructure',

        'combination_score' =>
            70.0,

        'combined_movements' => [
            'intelligence' =>
                3.0
        ],

        'optimizer' => [
            'budget_after' =>
                2.0
        ]
    ]
];


/*
 * ============================================================
 * AUTHORITATIVE FULL SORT
 * ============================================================
 */

$expected =
    $combinations;


usort(
    $expected,
    'transferRankerCompare'
);


$expectedTopFive =
    array_slice(
        $expected,
        0,
        5
    );


/*
 * ============================================================
 * NEW RANKER
 * ============================================================
 */

try {

    $ranker =
        new TransferCombinationRanker();


    $actualTopFive =
        $ranker
            ->rank(
                $combinations,
                5
            );


    assertRankerSame(
        $expectedTopFive,
        $actualTopFive,
        'Bounded ranking exactly matches full usort plus array_slice'
    );


    assertRankerSame(
        5,
        count(
            $actualTopFive
        ),
        'Ranker returns exactly the requested limit'
    );


    assertRankerSame(
        'strong-high-score',
        $actualTopFive[0]['key']
            ?? null,
        'Classification and combination score priorities are preserved'
    );


    assertRankerSame(
        'strong-score-intelligence',
        $actualTopFive[1]['key']
            ?? null,
        'Intelligence movement tie-break is preserved'
    );


    assertRankerSame(
        'strong-score-intelligence-budget-high',
        $actualTopFive[2]['key']
            ?? null,
        'Remaining budget tie-break is preserved'
    );
    
    
    /*
     * ========================================================
     * EXACT COMPARATOR TIES
     * ========================================================
     *
     * These rows are deliberately identical across every
     * authoritative ranking dimension.
     *
     * The bounded ranker must reproduce the exact result from
     * the existing full usort + array_slice path, including
     * which tied rows survive the limit.
     */

    $tiedCombinations = [

        [
            'key' =>
                'tie-a',

            'classification' =>
                'Strong Improvement',

            'combination_score' =>
                90.0,

            'combined_movements' => [
                'intelligence' =>
                    10.0
            ],

            'optimizer' => [
                'budget_after' =>
                    5.0
            ]
        ],

        [
            'key' =>
                'tie-b',

            'classification' =>
                'Strong Improvement',

            'combination_score' =>
                90.0,

            'combined_movements' => [
                'intelligence' =>
                    10.0
            ],

            'optimizer' => [
                'budget_after' =>
                    5.0
            ]
        ],

        [
            'key' =>
                'tie-c',

            'classification' =>
                'Strong Improvement',

            'combination_score' =>
                90.0,

            'combined_movements' => [
                'intelligence' =>
                    10.0
            ],

            'optimizer' => [
                'budget_after' =>
                    5.0
            ]
        ],

        [
            'key' =>
                'tie-d',

            'classification' =>
                'Strong Improvement',

            'combination_score' =>
                90.0,

            'combined_movements' => [
                'intelligence' =>
                    10.0
            ],

            'optimizer' => [
                'budget_after' =>
                    5.0
            ]
        ]
    ];


    $expectedTies =
        $tiedCombinations;


    usort(
        $expectedTies,
        'transferRankerCompare'
    );


    $expectedTies =
        array_slice(
            $expectedTies,
            0,
            2
        );


    $actualTies =
        $ranker
            ->rank(
                $tiedCombinations,
                2
            );


    assertRankerSame(
        $expectedTies,
        $actualTies,
        'Exact comparator ties match authoritative full usort plus array_slice'
    );


    /*
     * ========================================================
     * LIMIT LARGER THAN INPUT
     * ========================================================
     */

    $allRanked =
        $ranker
            ->rank(
                $combinations,
                50
            );


    assertRankerSame(
        $expected,
        $allRanked,
        'Limit larger than input preserves complete authoritative ranking'
    );


    /*
     * ========================================================
     * EMPTY INPUT
     * ========================================================
     */

    assertRankerSame(
        [],
        $ranker
            ->rank(
                [],
                10
            ),
        'Empty input returns an empty result'
    );


    /*
     * ========================================================
     * INVALID LIMIT
     * ========================================================
     */

    assertRankerSame(
        [],
        $ranker
            ->rank(
                $combinations,
                0
            ),
        'Non-positive limit returns an empty result'
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