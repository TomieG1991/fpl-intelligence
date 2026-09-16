<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Free Hit Descriptor Ranker Test<br>";
echo "============================================<br>";
echo "<br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function testResult(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        echo
            "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        $passed++;

    } else {

        echo
            "FAIL: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        $failed++;
    }
}


/*
 * ============================================================
 * EXISTING FREE HIT DESCRIPTOR ORDERING
 * ============================================================
 *
 * This deliberately reproduces the ordering currently used by
 * FreeHitOptimizer.
 *
 * The new bounded ranker must return exactly the same ordered
 * top-K result as:
 *
 *     usort(...)
 *     array_slice(..., 0, $limit)
 *
 * We therefore use this only as the characterization oracle for
 * the tests below.
 */

function existingFreeHitDescriptorTopK(
    array $descriptors,
    int $limit
): array {

    usort(
        $descriptors,
        static function (
            array $a,
            array $b
        ): int {

            $pointsA =
                (float) $a[
                    'projected_points'
                ];


            $pointsB =
                (float) $b[
                    'projected_points'
                ];


            if (
                $pointsA
                !==
                $pointsB
            ) {

                return
                    $pointsB
                    <=>
                    $pointsA;
            }


            $priceA =
                (float) $a[
                    'price'
                ];


            $priceB =
                (float) $b[
                    'price'
                ];


            if (
                $priceA
                !==
                $priceB
            ) {

                return
                    $priceA
                    <=>
                    $priceB;
            }


            return
                strcmp(
                    (string) $a[
                        'key'
                    ],
                    (string) $b[
                        'key'
                    ]
                );
        }
    );


    if (
        count(
            $descriptors
        )
        >
        $limit
    ) {

        $descriptors =
            array_slice(
                $descriptors,
                0,
                $limit
            );
    }


    return
        $descriptors;
}


/*
 * ============================================================
 * CLASS CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Class Contract<br>";
echo "============================================<br>";


$classExists =
    class_exists(
        'FreeHitDescriptorRanker'
    );


testResult(
    $classExists,
    'FreeHitDescriptorRanker class exists'
);


if (
    !$classExists
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Free Hit Descriptor Ranker Test Summary<br>";
    echo "============================================<br>";
    echo "Passed: "
        . $passed
        . "<br>";
    echo "Failed: "
        . $failed
        . "<br>";
    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";


    exit;
}


$ranker =
    new FreeHitDescriptorRanker();


testResult(
    method_exists(
        $ranker,
        'selectTop'
    ),
    'FreeHitDescriptorRanker exposes selectTop()'
);


if (
    !method_exists(
        $ranker,
        'selectTop'
    )
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Free Hit Descriptor Ranker Test Summary<br>";
    echo "============================================<br>";
    echo "Passed: "
        . $passed
        . "<br>";
    echo "Failed: "
        . $failed
        . "<br>";
    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";


    exit;
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * FEWER DESCRIPTORS THAN LIMIT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Fewer Descriptors Than Limit<br>";
echo "============================================<br>";


$descriptors = [
    [
        'projected_points' => 8.0,
        'price' => 6.0,
        'key' => ':0000000003'
    ],
    [
        'projected_points' => 10.0,
        'price' => 7.0,
        'key' => ':0000000001'
    ],
    [
        'projected_points' => 9.0,
        'price' => 5.0,
        'key' => ':0000000002'
    ]
];


$expected =
    existingFreeHitDescriptorTopK(
        $descriptors,
        5
    );


$actual =
    $ranker->selectTop(
        $descriptors,
        5
    );


testResult(
    $actual
    ===
    $expected,
    'Fewer-than-limit descriptors exactly match existing ordering'
);


testResult(
    count(
        $actual
    )
    ===
    3,
    'Fewer-than-limit selection retains every descriptor'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * EXACTLY THE LIMIT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Exactly The Limit<br>";
echo "============================================<br>";


$descriptors = [
    [
        'projected_points' => 7.0,
        'price' => 4.5,
        'key' => ':0000000004'
    ],
    [
        'projected_points' => 10.0,
        'price' => 7.0,
        'key' => ':0000000001'
    ],
    [
        'projected_points' => 8.0,
        'price' => 5.5,
        'key' => ':0000000003'
    ],
    [
        'projected_points' => 9.0,
        'price' => 6.0,
        'key' => ':0000000002'
    ]
];


$expected =
    existingFreeHitDescriptorTopK(
        $descriptors,
        4
    );


$actual =
    $ranker->selectTop(
        $descriptors,
        4
    );


testResult(
    $actual
    ===
    $expected,
    'Exactly-limit descriptors exactly match existing ordering'
);


testResult(
    count(
        $actual
    )
    ===
    4,
    'Exactly-limit selection retains the requested number'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * PROJECTED POINTS ARE PRIMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Projected Points Priority<br>";
echo "============================================<br>";


$descriptors = [
    [
        'projected_points' => 7.0,
        'price' => 4.0,
        'key' => ':0000000001'
    ],
    [
        'projected_points' => 10.0,
        'price' => 9.0,
        'key' => ':0000000002'
    ],
    [
        'projected_points' => 9.0,
        'price' => 5.0,
        'key' => ':0000000003'
    ],
    [
        'projected_points' => 8.0,
        'price' => 4.5,
        'key' => ':0000000004'
    ]
];


$expected =
    existingFreeHitDescriptorTopK(
        $descriptors,
        2
    );


$actual =
    $ranker->selectTop(
        $descriptors,
        2
    );


testResult(
    $actual
    ===
    $expected,
    'Projected-points priority exactly matches existing ranking'
);


testResult(
    $actual[
        0
    ][
        'projected_points'
    ]
    ===
    10.0,
    'Highest projected-points descriptor ranks first'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * PRICE BREAKS PROJECTED-POINT TIES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Price Tie-Break<br>";
echo "============================================<br>";


$descriptors = [
    [
        'projected_points' => 10.0,
        'price' => 7.0,
        'key' => ':0000000001'
    ],
    [
        'projected_points' => 10.0,
        'price' => 5.0,
        'key' => ':0000000002'
    ],
    [
        'projected_points' => 10.0,
        'price' => 6.0,
        'key' => ':0000000003'
    ]
];


$expected =
    existingFreeHitDescriptorTopK(
        $descriptors,
        2
    );


$actual =
    $ranker->selectTop(
        $descriptors,
        2
    );


testResult(
    $actual
    ===
    $expected,
    'Price tie-break exactly matches existing ranking'
);


testResult(
    $actual[
        0
    ][
        'price'
    ]
    ===
    5.0,
    'Lower price wins an equal-points tie'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * KEY BREAKS POINTS AND PRICE TIES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Key Tie-Break<br>";
echo "============================================<br>";


$descriptors = [
    [
        'projected_points' => 10.0,
        'price' => 5.0,
        'key' => ':0000000003'
    ],
    [
        'projected_points' => 10.0,
        'price' => 5.0,
        'key' => ':0000000001'
    ],
    [
        'projected_points' => 10.0,
        'price' => 5.0,
        'key' => ':0000000002'
    ]
];


$expected =
    existingFreeHitDescriptorTopK(
        $descriptors,
        2
    );


$actual =
    $ranker->selectTop(
        $descriptors,
        2
    );


testResult(
    $actual
    ===
    $expected,
    'Key tie-break exactly matches existing ranking'
);


testResult(
    $actual[
        0
    ][
        'key'
    ]
    ===
    ':0000000001',
    'Lexically lower key wins an equal-points equal-price tie'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * LARGE DETERMINISTIC DESCRIPTOR SET
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Large Deterministic Set<br>";
echo "============================================<br>";


$descriptors =
    [];


for (
    $index = 1;
    $index <= 5000;
    $index++
) {

    /*
     * Deliberately create repeated projected-points and price
     * values so all three ranking fields are exercised heavily.
     */

    $projectedPoints =
        4.0
        +
        (
            (
                $index
                *
                37
            )
            %
            401
        )
        /
        50;


    $price =
        4.0
        +
        (
            (
                $index
                *
                17
            )
            %
            41
        )
        /
        10;


    $descriptors[] = [
        'state_index' =>
            $index
            %
            240,

        'candidate_index' =>
            $index
            %
            60,

        'player_id' =>
            $index,

        'team_id' =>
            (
                $index
                %
                20
            )
            +
            1,

        'price' =>
            $price,

        'projected_points' =>
            $projectedPoints,

        'key' =>
            ':'
            .
            str_pad(
                (string) $index,
                10,
                '0',
                STR_PAD_LEFT
            )
    ];
}


$expected =
    existingFreeHitDescriptorTopK(
        $descriptors,
        240
    );


$actual =
    $ranker->selectTop(
        $descriptors,
        240
    );


testResult(
    count(
        $actual
    )
    ===
    240,
    'Large descriptor set returns exactly the requested top 240'
);


testResult(
    $actual
    ===
    $expected,
    'Large descriptor set exactly matches full-sort top 240'
);


$secondRun =
    $ranker->selectTop(
        $descriptors,
        240
    );


testResult(
    $secondRun
    ===
    $actual,
    'Large descriptor selection is deterministic across repeated runs'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * BOUNDARY AROUND THE FINAL SURVIVOR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Top-K Boundary<br>";
echo "============================================<br>";


$descriptors =
    [];


for (
    $index = 1;
    $index <= 300;
    $index++
) {

    $descriptors[] = [
        'projected_points' =>
            10.0,

        'price' =>
            5.0,

        'key' =>
            ':'
            .
            str_pad(
                (string) (
                    301
                    -
                    $index
                ),
                10,
                '0',
                STR_PAD_LEFT
            )
    ];
}


$expected =
    existingFreeHitDescriptorTopK(
        $descriptors,
        240
    );


$actual =
    $ranker->selectTop(
        $descriptors,
        240
    );


testResult(
    $actual
    ===
    $expected,
    'Descriptor immediately around the top-K boundary matches existing ranking'
);


testResult(
    $actual[
        239
    ][
        'key'
    ]
    ===
    ':0000000240',
    'The exact 240th-ranked descriptor is retained'
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Free Hit Descriptor Ranker Test Summary<br>";
echo "============================================<br>";


echo
    "Passed: "
    . $passed
    . "<br>";


echo
    "Failed: "
    . $failed
    . "<br>";


echo "<br>";


if (
    $failed
    ===
    0
) {

    echo
        "RESULT: ALL TESTS PASSED ✅";

} else {

    echo
        "RESULT: TESTS FAILED ❌";
}