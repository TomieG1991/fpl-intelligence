<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed =
    0;

$failed =
    0;


function freeHitSmallRankerCheck(
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


function freeHitOldSmallPathRanking(
    array $descriptors,
    int $limit
): array {

    usort(
        $descriptors,
        static function (
            array $a,
            array $b
        ): int {

            $pointsComparison =
                (
                    (float) (
                        $b[
                            'starting_points'
                        ]
                        ?? 0.0
                    )
                )
                <=>
                (
                    (float) (
                        $a[
                            'starting_points'
                        ]
                        ?? 0.0
                    )
                );


            if (
                $pointsComparison
                !==
                0
            ) {

                return
                    $pointsComparison;
            }


            $priceComparison =
                (
                    (float) (
                        $a[
                            'price'
                        ]
                        ?? 0.0
                    )
                )
                <=>
                (
                    (float) (
                        $b[
                            'price'
                        ]
                        ?? 0.0
                    )
                );


            if (
                $priceComparison
                !==
                0
            ) {

                return
                    $priceComparison;
            }


            return
                strcmp(
                    (string) (
                        $a[
                            'beam_key'
                        ]
                        ?? ''
                    ),
                    (string) (
                        $b[
                            'beam_key'
                        ]
                        ?? ''
                    )
                );
        }
    );


    return
        array_slice(
            $descriptors,
            0,
            $limit
        );
}


echo
    '============================================<br>';

echo
    'Free Hit Descriptor Ranker Small Path Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


/*
 * ============================================================
 * SCENARIO A
 * SMALL-PATH CONTRACT
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Small-Path Contract<br>';

echo
    '============================================<br>';


$ranker =
    new FreeHitDescriptorRanker();


freeHitSmallRankerCheck(
    method_exists(
        $ranker,
        'selectTopStartingXI'
    ),
    'Ranker exposes selectTopStartingXI method'
);


if (
    !method_exists(
        $ranker,
        'selectTopStartingXI'
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

    echo
        'RESULT: TESTS FAILED ❌<br>';

    exit;
}


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * PRIMARY STARTING-XI POINTS ORDER
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Starting XI Points Are Primary<br>';

echo
    '============================================<br>';


$descriptors = [

    [
        'starting_points' =>
            10.0,

        'price' =>
            5.0,

        'beam_key' =>
            'A'
    ],

    [
        'starting_points' =>
            12.0,

        'price' =>
            8.0,

        'beam_key' =>
            'B'
    ],

    [
        'starting_points' =>
            11.0,

        'price' =>
            4.0,

        'beam_key' =>
            'C'
    ]
];


$result =
    $ranker->selectTopStartingXI(
        $descriptors,
        2
    );


freeHitSmallRankerCheck(
    count(
        $result
    )
    ===
    2,
    'Requested number of descriptors is returned'
);


freeHitSmallRankerCheck(
    $result[
        0
    ][
        'beam_key'
    ]
    ===
    'B'
    &&
    $result[
        1
    ][
        'beam_key'
    ]
    ===
    'C',
    'Higher Starting XI projected points rank first'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * PRICE TIE-BREAK
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Lower Price Wins Equal Points<br>';

echo
    '============================================<br>';


$descriptors = [

    [
        'starting_points' =>
            10.0,

        'price' =>
            5.5,

        'beam_key' =>
            'A'
    ],

    [
        'starting_points' =>
            10.0,

        'price' =>
            4.5,

        'beam_key' =>
            'B'
    ]
];


$result =
    $ranker->selectTopStartingXI(
        $descriptors,
        2
    );


freeHitSmallRankerCheck(
    $result[
        0
    ][
        'beam_key'
    ]
    ===
    'B',
    'Lower price wins equal Starting XI points'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * BEAM-KEY TIE-BREAK
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Beam Key Breaks Complete Tie<br>';

echo
    '============================================<br>';


$descriptors = [

    [
        'starting_points' =>
            10.0,

        'price' =>
            5.0,

        'beam_key' =>
            'C'
    ],

    [
        'starting_points' =>
            10.0,

        'price' =>
            5.0,

        'beam_key' =>
            'A'
    ],

    [
        'starting_points' =>
            10.0,

        'price' =>
            5.0,

        'beam_key' =>
            'B'
    ]
];


$result =
    $ranker->selectTopStartingXI(
        $descriptors,
        3
    );


freeHitSmallRankerCheck(
    array_column(
        $result,
        'beam_key'
    )
    ===
    [
        'A',
        'B',
        'C'
    ],
    'Beam key ascending breaks complete ties'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E
 * EXACT BOUNDED EQUIVALENCE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Exact Bounded Equivalence<br>';

echo
    '============================================<br>';


$descriptors =
    [];


for (
    $index =
        0;

    $index
        <
        5000;

    $index++
) {

    $descriptors[] = [

        'state_index' =>
            $index % 400,

        'candidate_index' =>
            $index,

        'player_id' =>
            10000 + $index,

        'team_id' =>
            ($index % 20) + 1,

        'price' =>
            4.0
            +
            (
                ($index * 7) % 60
            )
            /
            10,

        'starting_points' =>
            20.0
            +
            (
                ($index * 13) % 500
            )
            /
            100,

        'beam_key' =>
            str_pad(
                (string) (
                    5000 - $index
                ),
                5,
                '0',
                STR_PAD_LEFT
            )
    ];
}


$expected =
    freeHitOldSmallPathRanking(
        $descriptors,
        400
    );


$actual =
    $ranker->selectTopStartingXI(
        $descriptors,
        400
    );


freeHitSmallRankerCheck(
    $actual
    ===
    $expected,
    'Bounded ranker exactly matches old full-sort top 400'
);


$secondRun =
    $ranker->selectTopStartingXI(
        $descriptors,
        400
    );


freeHitSmallRankerCheck(
    $secondRun
    ===
    $actual,
    'Bounded ranking is deterministic'
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO F
 * BOUNDARY TIE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Beam Boundary Tie Is Exact<br>';

echo
    '============================================<br>';


$descriptors =
    [];


for (
    $index =
        0;

    $index
        <
        500;

    $index++
) {

    $descriptors[] = [

        'starting_points' =>
            10.0,

        'price' =>
            5.0,

        'beam_key' =>
            str_pad(
                (string) (
                    500 - $index
                ),
                3,
                '0',
                STR_PAD_LEFT
            )
    ];
}


$expected =
    freeHitOldSmallPathRanking(
        $descriptors,
        400
    );


$actual =
    $ranker->selectTopStartingXI(
        $descriptors,
        400
    );


freeHitSmallRankerCheck(
    $actual
    ===
    $expected,
    'Complete ties preserve the exact old top-400 boundary'
);


freeHitSmallRankerCheck(
    $actual[
        399
    ][
        'beam_key'
    ]
    ===
    '400',
    '400th descriptor matches the old beam-key boundary'
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
    $failed
    ===
    0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}