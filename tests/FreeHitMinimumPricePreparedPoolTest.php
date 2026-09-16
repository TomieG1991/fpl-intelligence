<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed =
    0;

$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function freeHitPreparedPoolCheck(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo
            'PASS: '
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        $passed++;

        return;
    }


    echo
        'FAIL: '
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';

    $failed++;
}


/*
 * ============================================================
 * HEADER
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Free Hit Minimum Price Prepared Pool Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


/*
 * ============================================================
 * SCENARIO A
 * PREPARED POOL CONTRACT
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Prepared Pool Contract<br>';

echo
    '============================================<br>';


$calculator =
    new FreeHitMinimumPriceCalculator();


freeHitPreparedPoolCheck(
    method_exists(
        $calculator,
        'preparePools'
    ),
    'Calculator exposes preparePools method'
);


freeHitPreparedPoolCheck(
    method_exists(
        $calculator,
        'calculatePrepared'
    ),
    'Calculator exposes calculatePrepared method'
);


if (
    !method_exists(
        $calculator,
        'preparePools'
    )
    ||
    !method_exists(
        $calculator,
        'calculatePrepared'
    )
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


    if ($failed === 0) {

        echo
            'RESULT: ALL TESTS PASSED ✅<br>';

    } else {

        echo
            'RESULT: TESTS FAILED ❌<br>';
    }


    exit;
}


echo
    '<br>';


/*
 * ============================================================
 * SHARED RAW POOLS
 * ============================================================
 */

$rawPools = [

    'GK' => [

        [
            'player_id' =>
                1,

            'price' =>
                4.0,

            'projected_points' =>
                3.0
        ],

        [
            'player_id' =>
                2,

            'price' =>
                4.5,

            'projected_points' =>
                4.0
        ],

        [
            'player_id' =>
                3,

            'price' =>
                5.0,

            'projected_points' =>
                5.0
        ]
    ],


    'DEF' => [

        [
            'player_id' =>
                10,

            'price' =>
                4.0
        ],

        [
            'player_id' =>
                11,

            'price' =>
                4.5
        ],

        [
            'player_id' =>
                12,

            'price' =>
                5.0
        ],

        [
            'player_id' =>
                13,

            'price' =>
                5.5
        ]
    ],


    'MID' => [

        [
            'player_id' =>
                20,

            'price' =>
                5.0
        ],

        [
            'player_id' =>
                21,

            'price' =>
                5.5
        ],

        [
            'player_id' =>
                22,

            'price' =>
                6.0
        ]
    ],


    'FWD' => [

        [
            'player_id' =>
                30,

            'price' =>
                5.5
        ],

        [
            'player_id' =>
                31,

            'price' =>
                6.0
        ]
    ]
];


/*
 * ============================================================
 * SCENARIO B
 * RAW POOLS ARE NORMALIZED ONCE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Raw Pools Are Normalized Once<br>';

echo
    '============================================<br>';


$preparedPools =
    $calculator->preparePools(
        $rawPools
    );


freeHitPreparedPoolCheck(
    is_array(
        $preparedPools
    ),
    'Prepared pools are returned as an array'
);


freeHitPreparedPoolCheck(
    isset(
        $preparedPools[
            'GK'
        ]
    )
    &&
    count(
        $preparedPools[
            'GK'
        ]
    )
    ===
    3,
    'Prepared goalkeeper pool retains all valid goalkeepers'
);


freeHitPreparedPoolCheck(
    isset(
        $preparedPools[
            'GK'
        ][
            0
        ][
            'player_id'
        ]
    )
    &&
    $preparedPools[
        'GK'
    ][
        0
    ][
        'player_id'
    ]
    ===
    1,
    'Prepared pool retains player identity'
);


freeHitPreparedPoolCheck(
    isset(
        $preparedPools[
            'GK'
        ][
            0
        ][
            'price'
        ]
    )
    &&
    $preparedPools[
        'GK'
    ][
        0
    ][
        'price'
    ]
    ===
    4.0,
    'Prepared pool retains normalized player price'
);


freeHitPreparedPoolCheck(
    count(
        $preparedPools[
            'GK'
        ][
            0
        ]
    )
    ===
    2,
    'Prepared candidate contains only player ID and price'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * INVALID CANDIDATES ARE REMOVED DURING PREPARATION
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Invalid Candidates Are Removed During Preparation<br>';

echo
    '============================================<br>';


$invalidRawPools =
    $rawPools;


$invalidRawPools[
    'GK'
][] = [
    'player_id' =>
        'invalid',

    'price' =>
        3.0
];


$invalidRawPools[
    'GK'
][] = [
    'player_id' =>
        99,

    'price' =>
        'invalid'
];


$invalidRawPools[
    'GK'
][] =
    'invalid candidate';


$preparedInvalidPools =
    $calculator->preparePools(
        $invalidRawPools
    );


freeHitPreparedPoolCheck(
    count(
        $preparedInvalidPools[
            'GK'
        ]
    )
    ===
    3,
    'Invalid candidates are removed during pool preparation'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * PREPARED CALCULATION USES CHEAPEST PLAYERS
 * ============================================================
 */

echo '============================================<br>';
echo 'Scenario D: Prepared Calculation Uses Cheapest Players<br>';
echo '============================================<br>';


$minimumPrice =
    $calculator->calculatePrepared(
        [],
        [
            'GK' =>
                1,

            'DEF' =>
                3,

            'MID' =>
                2,

            'FWD' =>
                1
        ],
        $preparedPools
    );


freeHitPreparedPoolCheck(
    $minimumPrice
    ===
    33.5,
    'Prepared calculation combines cheapest required players correctly'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E
 * SELECTED PLAYERS ARE EXCLUDED
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Selected Players Are Excluded<br>';

echo
    '============================================<br>';


$minimumPrice =
    $calculator->calculatePrepared(
        [
            1 =>
                true,

            10 =>
                true,

            20 =>
                true
        ],
        [
            'GK' =>
                1,

            'DEF' =>
                2,

            'MID' =>
                1,

            'FWD' =>
                0
        ],
        $preparedPools
    );


freeHitPreparedPoolCheck(
    $minimumPrice
    ===
    19.5,
    'Prepared calculation excludes already-selected players'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO F
 * IMPOSSIBLE COMPLETION RETURNS NULL
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Impossible Completion Returns Null<br>';

echo
    '============================================<br>';


$minimumPrice =
    $calculator->calculatePrepared(
        [
            1 =>
                true,

            2 =>
                true,

            3 =>
                true
        ],
        [
            'GK' =>
                1,

            'DEF' =>
                0,

            'MID' =>
                0,

            'FWD' =>
                0
        ],
        $preparedPools
    );


freeHitPreparedPoolCheck(
    $minimumPrice
    ===
    null,
    'Prepared calculation returns null when completion is impossible'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO G
 * ZERO REQUIREMENTS COST ZERO
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Zero Requirements Cost Zero<br>';

echo
    '============================================<br>';


$minimumPrice =
    $calculator->calculatePrepared(
        [],
        [
            'GK' =>
                0,

            'DEF' =>
                0,

            'MID' =>
                0,

            'FWD' =>
                0
        ],
        $preparedPools
    );


freeHitPreparedPoolCheck(
    $minimumPrice
    ===
    0.0,
    'Prepared calculation returns zero when no players remain'
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


if ($failed === 0) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}