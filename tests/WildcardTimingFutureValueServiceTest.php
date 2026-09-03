<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardTimingFutureValueCheck(
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
    'Wildcard Timing Future Value Service Test<br>';

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
 * SCENARIO A: WAITING ONE GAMEWEEK HAS GREATER VALUE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Waiting One Gameweek Has Greater Value<br>';

echo
    '============================================<br>';


$currentHorizon = [

    'status' =>
        'Available',

    'horizon' =>
        3,

    'projection_confidence' =>
        0.80,

    'gameweeks' => [

        3 => [

            'gameweek' =>
                3,

            'starting_xi_projected_points' =>
                60.0
        ],

        4 => [

            'gameweek' =>
                4,

            'starting_xi_projected_points' =>
                50.0
        ],

        5 => [

            'gameweek' =>
                5,

            'starting_xi_projected_points' =>
                50.0
        ]
    ]
];


$wildcardHorizon = [

    'status' =>
        'Available',

    'horizon' =>
        3,

    'projection_confidence' =>
        0.75,

    'gameweeks' => [

        3 => [

            'gameweek' =>
                3,

            'starting_xi_projected_points' =>
                55.0
        ],

        4 => [

            'gameweek' =>
                4,

            'starting_xi_projected_points' =>
                60.0
        ],

        5 => [

            'gameweek' =>
                5,

            'starting_xi_projected_points' =>
                60.0
        ]
    ]
];


$result =
    $service
        ->analyseHorizons(
            $currentHorizon,
            $wildcardHorizon
        );


wildcardTimingFutureValueCheck(
    'Immediate Wildcard gain remains fifteen points',
    abs(
        (
            $result[
                'projected_points_gain'
            ]
            ?? 0.0
        )
        -
        15.0
    ) < 0.0001
);


wildcardTimingFutureValueCheck(
    'Future Wildcard gain after waiting one gameweek is twenty points',
    abs(
        (
            $result[
                'future_projected_gain'
            ]
            ?? 0.0
        )
        -
        20.0
    ) < 0.0001
);


wildcardTimingFutureValueCheck(
    'Waiting one gameweek produces a five-point timing disadvantage for using now',
    abs(
        (
            $result[
                'timing_advantage'
            ]
            ?? 0.0
        )
        -
        (-5.0)
    ) < 0.0001
);


wildcardTimingFutureValueCheck(
    'Service identifies waiting as the better timing',
    (
        $result[
            'better_timing'
        ]
        ?? null
    )
    ===
    'Wait'
);


wildcardTimingFutureValueCheck(
    'Waiting advantage produces a Hold recommendation',
    (
        $result[
            'decision'
        ]
        ?? null
    )
    instanceof
    ChipDecision
    &&
    $result[
        'decision'
    ]
        ->getRecommendation()
    ===
    'Hold'
);


echo
    '<br>';
    
    
/*
 * ============================================================
 * SCENARIO B: USING WILDCARD NOW HAS GREATER VALUE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Using Wildcard Now Has Greater Value<br>';

echo
    '============================================<br>';


$currentHorizonNow = [

    'status' =>
        'Available',

    'horizon' =>
        3,

    'projection_confidence' =>
        0.80,

    'gameweeks' => [

        3 => [

            'gameweek' =>
                3,

            'starting_xi_projected_points' =>
                50.0
        ],

        4 => [

            'gameweek' =>
                4,

            'starting_xi_projected_points' =>
                50.0
        ],

        5 => [

            'gameweek' =>
                5,

            'starting_xi_projected_points' =>
                50.0
        ]
    ]
];


$wildcardHorizonNow = [

    'status' =>
        'Available',

    'horizon' =>
        3,

    'projection_confidence' =>
        0.75,

    'gameweeks' => [

        3 => [

            'gameweek' =>
                3,

            'starting_xi_projected_points' =>
                60.0
        ],

        4 => [

            'gameweek' =>
                4,

            'starting_xi_projected_points' =>
                55.0
        ],

        5 => [

            'gameweek' =>
                5,

            'starting_xi_projected_points' =>
                55.0
        ]
    ]
];


$nowResult =
    $service
        ->analyseHorizons(
            $currentHorizonNow,
            $wildcardHorizonNow
        );


wildcardTimingFutureValueCheck(
    'Immediate Wildcard gain is twenty points',
    abs(
        (
            $nowResult[
                'projected_points_gain'
            ]
            ?? 0.0
        )
        -
        20.0
    ) < 0.0001
);


wildcardTimingFutureValueCheck(
    'Future Wildcard gain after waiting one gameweek is ten points',
    abs(
        (
            $nowResult[
                'future_projected_gain'
            ]
            ?? 0.0
        )
        -
        10.0
    ) < 0.0001
);


wildcardTimingFutureValueCheck(
    'Using now produces a ten-point timing advantage',
    abs(
        (
            $nowResult[
                'timing_advantage'
            ]
            ?? 0.0
        )
        -
        10.0
    ) < 0.0001
);


wildcardTimingFutureValueCheck(
    'Service identifies now as the better timing',
    (
        $nowResult[
            'better_timing'
        ]
        ?? null
    )
    ===
    'Now'
);


wildcardTimingFutureValueCheck(
    'Strong immediate advantage produces a Use recommendation',
    (
        $nowResult[
            'decision'
        ]
        ?? null
    )
    instanceof
    ChipDecision
    &&
    $nowResult[
        'decision'
    ]
        ->getRecommendation()
    ===
    'Use'
);


echo
    '<br>';
    
/*
 * ============================================================
 * SCENARIO C: ONE-GAMEWEEK HORIZON HAS NO FUTURE VALUE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: One-Gameweek Horizon Has No Future Value<br>';

echo
    '============================================<br>';


$currentOneGameweekHorizon = [

    'status' =>
        'Available',

    'horizon' =>
        1,

    'projection_confidence' =>
        0.80,

    'gameweeks' => [

        3 => [

            'gameweek' =>
                3,

            'starting_xi_projected_points' =>
                50.0
        ]
    ]
];


$wildcardOneGameweekHorizon = [

    'status' =>
        'Available',

    'horizon' =>
        1,

    'projection_confidence' =>
        0.75,

    'gameweeks' => [

        3 => [

            'gameweek' =>
                3,

            'starting_xi_projected_points' =>
                62.0
        ]
    ]
];


$oneGameweekResult =
    $service
        ->analyseHorizons(
            $currentOneGameweekHorizon,
            $wildcardOneGameweekHorizon
        );


wildcardTimingFutureValueCheck(
    'One-gameweek horizon retains the twelve-point immediate gain',
    abs(
        (
            $oneGameweekResult[
                'projected_points_gain'
            ]
            ?? 0.0
        )
        -
        12.0
    ) < 0.0001
);


wildcardTimingFutureValueCheck(
    'Waiting beyond a one-gameweek horizon has zero projected Wildcard gain',
    (
        $oneGameweekResult[
            'future_projected_gain'
        ]
        ?? null
    )
    ===
    0.0
);


wildcardTimingFutureValueCheck(
    'One-gameweek horizon gives the full twelve-point timing advantage to using now',
    abs(
        (
            $oneGameweekResult[
                'timing_advantage'
            ]
            ?? 0.0
        )
        -
        12.0
    ) < 0.0001
);


wildcardTimingFutureValueCheck(
    'One-gameweek horizon identifies now as the better timing',
    (
        $oneGameweekResult[
            'better_timing'
        ]
        ?? null
    )
    ===
    'Now'
);


wildcardTimingFutureValueCheck(
    'Strong one-gameweek Wildcard gain produces a Use recommendation',
    (
        $oneGameweekResult[
            'decision'
        ]
        ?? null
    )
    instanceof
    ChipDecision
    &&
    $oneGameweekResult[
        'decision'
    ]
        ->getRecommendation()
    ===
    'Use'
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