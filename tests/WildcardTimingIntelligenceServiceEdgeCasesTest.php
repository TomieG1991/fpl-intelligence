<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardTimingServiceEdgeCheck(
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
    'Wildcard Timing Intelligence Service Edge Cases Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$service =
    new WildcardTimingIntelligenceService(
        new WildcardTimingIntelligence()
    );


/*
 * ============================================================
 * Scenario A: Current horizon unavailable
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Current horizon unavailable<br>';

echo
    '============================================<br>';


$currentUnavailable =
    $service
        ->analyseHorizons(
            [
                'status' =>
                    'Unavailable',

                'horizon' =>
                    3,

                'gameweeks' =>
                    []
            ],
            [
                'status' =>
                    'Available',

                'horizon' =>
                    3,

                'gameweeks' => [

                    3 => [
                        'gameweek' => 3,
                        'starting_xi_projected_points' => 50.0
                    ],

                    4 => [
                        'gameweek' => 4,
                        'starting_xi_projected_points' => 50.0
                    ],

                    5 => [
                        'gameweek' => 5,
                        'starting_xi_projected_points' => 50.0
                    ]
                ]
            ]
        );


wildcardTimingServiceEdgeCheck(
    'Unavailable current horizon produces unavailable analysis',
    (
        $currentUnavailable[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


wildcardTimingServiceEdgeCheck(
    'Unavailable current horizon produces no decision',
    (
        $currentUnavailable[
            'decision'
        ]
        ??
        null
    )
    ===
    null
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Wildcard horizon unavailable
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Wildcard horizon unavailable<br>';

echo
    '============================================<br>';


$wildcardUnavailable =
    $service
        ->analyseHorizons(
            [
                'status' =>
                    'Available',

                'horizon' =>
                    3,

                'gameweeks' => [

                    3 => [
                        'gameweek' => 3,
                        'starting_xi_projected_points' => 50.0
                    ],

                    4 => [
                        'gameweek' => 4,
                        'starting_xi_projected_points' => 50.0
                    ],

                    5 => [
                        'gameweek' => 5,
                        'starting_xi_projected_points' => 50.0
                    ]
                ]
            ],
            [
                'status' =>
                    'Unavailable',

                'horizon' =>
                    3,

                'gameweeks' =>
                    []
            ]
        );


wildcardTimingServiceEdgeCheck(
    'Unavailable Wildcard horizon produces unavailable analysis',
    (
        $wildcardUnavailable[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


wildcardTimingServiceEdgeCheck(
    'Unavailable Wildcard horizon produces no decision',
    (
        $wildcardUnavailable[
            'decision'
        ]
        ??
        null
    )
    ===
    null
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Different horizon lengths
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Different horizon lengths<br>';

echo
    '============================================<br>';


$differentLengths =
    $service
        ->analyseHorizons(
            [
                'status' =>
                    'Available',

                'horizon' =>
                    3,

                'gameweeks' => [

                    3 => [
                        'gameweek' => 3,
                        'starting_xi_projected_points' => 50.0
                    ],

                    4 => [
                        'gameweek' => 4,
                        'starting_xi_projected_points' => 50.0
                    ],

                    5 => [
                        'gameweek' => 5,
                        'starting_xi_projected_points' => 50.0
                    ]
                ]
            ],
            [
                'status' =>
                    'Available',

                'horizon' =>
                    2,

                'gameweeks' => [

                    3 => [
                        'gameweek' => 3,
                        'starting_xi_projected_points' => 55.0
                    ],

                    4 => [
                        'gameweek' => 4,
                        'starting_xi_projected_points' => 55.0
                    ]
                ]
            ]
        );


wildcardTimingServiceEdgeCheck(
    'Different horizon lengths are rejected',
    (
        $differentLengths[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Different gameweek ranges
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Different gameweek ranges<br>';

echo
    '============================================<br>';


$differentGameweeks =
    $service
        ->analyseHorizons(
            [
                'status' =>
                    'Available',

                'horizon' =>
                    3,

                'gameweeks' => [

                    3 => [
                        'gameweek' => 3,
                        'starting_xi_projected_points' => 50.0
                    ],

                    4 => [
                        'gameweek' => 4,
                        'starting_xi_projected_points' => 50.0
                    ],

                    5 => [
                        'gameweek' => 5,
                        'starting_xi_projected_points' => 50.0
                    ]
                ]
            ],
            [
                'status' =>
                    'Available',

                'horizon' =>
                    3,

                'gameweeks' => [

                    4 => [
                        'gameweek' => 4,
                        'starting_xi_projected_points' => 55.0
                    ],

                    5 => [
                        'gameweek' => 5,
                        'starting_xi_projected_points' => 55.0
                    ],

                    6 => [
                        'gameweek' => 6,
                        'starting_xi_projected_points' => 55.0
                    ]
                ]
            ]
        );


wildcardTimingServiceEdgeCheck(
    'Different gameweek ranges are rejected',
    (
        $differentGameweeks[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Missing projected points
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Missing projected points<br>';

echo
    '============================================<br>';


$missingProjection =
    $service
        ->analyseHorizons(
            [
                'status' =>
                    'Available',

                'horizon' =>
                    3,

                'gameweeks' => [

                    3 => [
                        'gameweek' => 3,
                        'starting_xi_projected_points' => 50.0
                    ],

                    4 => [
                        'gameweek' => 4
                    ],

                    5 => [
                        'gameweek' => 5,
                        'starting_xi_projected_points' => 50.0
                    ]
                ]
            ],
            [
                'status' =>
                    'Available',

                'horizon' =>
                    3,

                'gameweeks' => [

                    3 => [
                        'gameweek' => 3,
                        'starting_xi_projected_points' => 55.0
                    ],

                    4 => [
                        'gameweek' => 4,
                        'starting_xi_projected_points' => 55.0
                    ],

                    5 => [
                        'gameweek' => 5,
                        'starting_xi_projected_points' => 55.0
                    ]
                ]
            ]
        );


wildcardTimingServiceEdgeCheck(
    'Missing Starting XI projection is rejected rather than treated as zero',
    (
        $missingProjection[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario F: Genuine zero projected points
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Genuine zero projected points<br>';

echo
    '============================================<br>';


$zeroProjection =
    $service
        ->analyseHorizons(
            [
                'status' =>
                    'Available',

                'horizon' =>
                    1,
                    
                'projection_confidence' =>
                    0.75,

                'gameweeks' => [

                    3 => [
                        'gameweek' => 3,
                        'starting_xi_projected_points' => 0.0
                    ]
                ]
            ],
            [
                'status' =>
                    'Available',

                'horizon' =>
                    1,
                    
                'projection_confidence' =>
                    0.75,

                'gameweeks' => [

                    3 => [
                        'gameweek' => 3,
                        'starting_xi_projected_points' => 5.0
                    ]
                ]
            ]
        );


wildcardTimingServiceEdgeCheck(
    'A genuine zero projection remains valid evidence',
    (
        $zeroProjection[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


wildcardTimingServiceEdgeCheck(
    'Valid zero projection still produces a decision',
    (
        $zeroProjection[
            'decision'
        ]
        ??
        null
    )
    instanceof
    ChipDecision
);


wildcardTimingServiceEdgeCheck(
    'Valid zero projection produces the correct five-point gain',
    abs(
        (
            $zeroProjection[
                'projected_points_gain'
            ]
            ??
            -999.0
        )
        -
        5.0
    ) < 0.0001
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario G: Valid matching horizons expose status
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Valid matching horizons expose status<br>';

echo
    '============================================<br>';


$validResult =
    $service
        ->analyseHorizons(
            [
                'status' =>
                    'Available',

                'horizon' =>
                    2,
                    
                'projection_confidence' =>
                    0.80,

                'gameweeks' => [

                    7 => [
                        'gameweek' => 7,
                        'starting_xi_projected_points' => 48.0
                    ],

                    8 => [
                        'gameweek' => 8,
                        'starting_xi_projected_points' => 52.0
                    ]
                ]
            ],
            [
                'status' =>
                    'Available',

                'horizon' =>
                    2,
                    
                'projection_confidence' =>
                    0.70,

                'gameweeks' => [

                    7 => [
                        'gameweek' => 7,
                        'starting_xi_projected_points' => 54.0
                    ],

                    8 => [
                        'gameweek' => 8,
                        'starting_xi_projected_points' => 58.0
                    ]
                ]
            ]
        );


wildcardTimingServiceEdgeCheck(
    'Valid matching horizons return Available status',
    (
        $validResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


wildcardTimingServiceEdgeCheck(
    'Valid matching horizons retain correct current total',
    abs(
        (
            $validResult[
                'current_squad_projected_points'
            ]
            ??
            0.0
        )
        -
        100.0
    ) < 0.0001
);


wildcardTimingServiceEdgeCheck(
    'Valid matching horizons retain correct Wildcard total',
    abs(
        (
            $validResult[
                'wildcard_squad_projected_points'
            ]
            ??
            0.0
        )
        -
        112.0
    ) < 0.0001
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