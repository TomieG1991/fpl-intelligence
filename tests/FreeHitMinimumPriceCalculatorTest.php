<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function freeHitMinimumPriceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        return;
    }


    $failed++;

    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';
}


echo
    '============================================<br>';

echo
    'Free Hit Minimum Price Calculator Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


/*
 * ============================================================
 * SCENARIO A: CLASS CONTRACT
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Class Contract<br>';

echo
    '============================================<br>';


$classExists =
    class_exists(
        'FreeHitMinimumPriceCalculator'
    );


freeHitMinimumPriceCheck(
    'FreeHitMinimumPriceCalculator class exists',
    $classExists
);


if (
    !$classExists
) {

    echo
        '<br>';

    echo
        '============================================<br>';

    echo
        'TEST SUMMARY<br>';

    echo
        '============================================<br>';

    echo
        'Passed: '
        . $passed
        . '<br>';

    echo
        'Failed: '
        . $failed
        . '<br>';

    echo
        'RESULT: TESTS FAILED ❌<br>';

    exit;
}


$calculator =
    new FreeHitMinimumPriceCalculator();


freeHitMinimumPriceCheck(
    'FreeHitMinimumPriceCalculator can be instantiated',
    $calculator
    instanceof
    FreeHitMinimumPriceCalculator
);


freeHitMinimumPriceCheck(
    'FreeHitMinimumPriceCalculator exposes calculate method',
    method_exists(
        $calculator,
        'calculate'
    )
);


echo
    '<br>';


/*
 * ============================================================
 * SHARED PRICE-SORTED PLAYER POOLS
 * ============================================================
 */

$priceSortedPools = [

    'GK' => [

        [
            'player_id' => 1,
            'price' => 4.0
        ],

        [
            'player_id' => 2,
            'price' => 4.5
        ],

        [
            'player_id' => 3,
            'price' => 5.0
        ]
    ],


    'DEF' => [

        [
            'player_id' => 10,
            'price' => 4.0
        ],

        [
            'player_id' => 11,
            'price' => 4.5
        ],

        [
            'player_id' => 12,
            'price' => 5.0
        ],

        [
            'player_id' => 13,
            'price' => 5.5
        ]
    ],


    'MID' => [

        [
            'player_id' => 20,
            'price' => 5.0
        ],

        [
            'player_id' => 21,
            'price' => 5.5
        ],

        [
            'player_id' => 22,
            'price' => 6.0
        ],

        [
            'player_id' => 23,
            'price' => 6.5
        ]
    ],


    'FWD' => [

        [
            'player_id' => 30,
            'price' => 5.5
        ],

        [
            'player_id' => 31,
            'price' => 6.0
        ],

        [
            'player_id' => 32,
            'price' => 6.5
        ]
    ]
];


/*
 * ============================================================
 * SCENARIO B: NO REMAINING PLAYERS COSTS ZERO
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: No Remaining Players Costs Zero<br>';

echo
    '============================================<br>';


$result =
    $calculator->calculate(
        [],
        [
            'GK' => 0,
            'DEF' => 0,
            'MID' => 0,
            'FWD' => 0
        ],
        $priceSortedPools
    );


freeHitMinimumPriceCheck(
    'No remaining players produces zero minimum price',
    $result === 0.0
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C: CHEAPEST PLAYER IS USED
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Cheapest Player Is Used<br>';

echo
    '============================================<br>';


$result =
    $calculator->calculate(
        [],
        [
            'GK' => 1,
            'DEF' => 0,
            'MID' => 0,
            'FWD' => 0
        ],
        $priceSortedPools
    );


freeHitMinimumPriceCheck(
    'Cheapest available goalkeeper determines minimum price',
    abs(
        (float) $result
        - 4.0
    ) < 0.000001
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D: SELECTED PLAYER IS EXCLUDED
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Selected Player Is Excluded<br>';

echo
    '============================================<br>';


$result =
    $calculator->calculate(
        [
            1 => true
        ],
        [
            'GK' => 1,
            'DEF' => 0,
            'MID' => 0,
            'FWD' => 0
        ],
        $priceSortedPools
    );


freeHitMinimumPriceCheck(
    'Already-selected cheapest goalkeeper is excluded',
    abs(
        (float) $result
        - 4.5
    ) < 0.000001
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E: MULTIPLE PLAYERS AT ONE POSITION
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Multiple Players At One Position<br>';

echo
    '============================================<br>';


$result =
    $calculator->calculate(
        [],
        [
            'GK' => 0,
            'DEF' => 3,
            'MID' => 0,
            'FWD' => 0
        ],
        $priceSortedPools
    );


freeHitMinimumPriceCheck(
    'Three defenders use the three cheapest available defenders',
    abs(
        (float) $result
        - 13.5
    ) < 0.000001
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO F: MULTIPLE POSITIONS ARE COMBINED
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Multiple Positions Are Combined<br>';

echo
    '============================================<br>';


$result =
    $calculator->calculate(
        [],
        [
            'GK' => 1,
            'DEF' => 2,
            'MID' => 2,
            'FWD' => 1
        ],
        $priceSortedPools
    );


/*
 * GK:
 * 4.0
 *
 * DEF:
 * 4.0 + 4.5 = 8.5
 *
 * MID:
 * 5.0 + 5.5 = 10.5
 *
 * FWD:
 * 5.5
 *
 * Total:
 * 28.5
 */

freeHitMinimumPriceCheck(
    'Minimum prices across positions are combined correctly',
    abs(
        (float) $result
        - 28.5
    ) < 0.000001
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO G: SELECTED IDS AFFECT ONLY AVAILABLE PLAYERS
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Multiple Selected Players Are Excluded<br>';

echo
    '============================================<br>';


$result =
    $calculator->calculate(
        [
            10 => true,
            20 => true,
            21 => true
        ],
        [
            'GK' => 0,
            'DEF' => 2,
            'MID' => 1,
            'FWD' => 0
        ],
        $priceSortedPools
    );


/*
 * DEF 10 is already selected:
 * 11 + 12 = 4.5 + 5.0
 *
 * MID 20 and 21 are already selected:
 * 22 = 6.0
 *
 * Total:
 * 15.5
 */

freeHitMinimumPriceCheck(
    'Selected IDs are excluded from positional minimum prices',
    abs(
        (float) $result
        - 15.5
    ) < 0.000001
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO H: IMPOSSIBLE COMPLETION RETURNS NULL
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario H: Impossible Completion Returns Null<br>';

echo
    '============================================<br>';


$result =
    $calculator->calculate(
        [
            1 => true,
            2 => true,
            3 => true
        ],
        [
            'GK' => 1,
            'DEF' => 0,
            'MID' => 0,
            'FWD' => 0
        ],
        $priceSortedPools
    );


freeHitMinimumPriceCheck(
    'No available goalkeeper returns null',
    $result === null
);


$result =
    $calculator->calculate(
        [],
        [
            'GK' => 0,
            'DEF' => 5,
            'MID' => 0,
            'FWD' => 0
        ],
        $priceSortedPools
    );


freeHitMinimumPriceCheck(
    'Insufficient positional candidates return null',
    $result === null
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO I: MISSING REQUIRED POSITION POOL RETURNS NULL
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario I: Missing Required Position Pool Returns Null<br>';

echo
    '============================================<br>';


$poolsWithoutForwards =
    $priceSortedPools;


unset(
    $poolsWithoutForwards[
        'FWD'
    ]
);


$result =
    $calculator->calculate(
        [],
        [
            'GK' => 0,
            'DEF' => 0,
            'MID' => 0,
            'FWD' => 1
        ],
        $poolsWithoutForwards
    );


freeHitMinimumPriceCheck(
    'Missing required positional pool returns null',
    $result === null
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO J: INVALID CANDIDATES ARE IGNORED
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario J: Invalid Candidates Are Ignored<br>';

echo
    '============================================<br>';


$invalidCandidatePools =
    $priceSortedPools;


array_unshift(
    $invalidCandidatePools[
        'GK'
    ],
    [
        'player_id' => null,
        'price' => 1.0
    ],
    [
        'player_id' => 99,
        'price' => null
    ],
    [
        'player_id' => 0,
        'price' => 2.0
    ]
);


$result =
    $calculator->calculate(
        [],
        [
            'GK' => 1,
            'DEF' => 0,
            'MID' => 0,
            'FWD' => 0
        ],
        $invalidCandidatePools
    );


freeHitMinimumPriceCheck(
    'Invalid candidates do not replace cheapest valid candidate',
    abs(
        (float) $result
        - 4.0
    ) < 0.000001
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO K: INVALID REQUIREMENT RETURNS NULL
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario K: Invalid Requirement Returns Null<br>';

echo
    '============================================<br>';


$result =
    $calculator->calculate(
        [],
        [
            'GK' => 0,
            'DEF' => -1,
            'MID' => 0,
            'FWD' => 0
        ],
        $priceSortedPools
    );


freeHitMinimumPriceCheck(
    'Negative positional requirement returns null',
    $result === null
);


$result =
    $calculator->calculate(
        [],
        [
            'GK' => 0,
            'DEF' => 'invalid',
            'MID' => 0,
            'FWD' => 0
        ],
        $priceSortedPools
    );


freeHitMinimumPriceCheck(
    'Non-numeric positional requirement returns null',
    $result === null
);


echo
    '<br>';


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'TEST SUMMARY<br>';

echo
    '============================================<br>';

echo
    'Passed: '
    . $passed
    . '<br>';

echo
    'Failed: '
    . $failed
    . '<br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}