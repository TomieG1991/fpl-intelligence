<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardTimingServiceCheck(
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
    'Wildcard Timing Intelligence Service Test<br>';

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
 * Scenario A: Compare two valid Squad Horizon results
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Compare valid Squad Horizon results<br>';

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
                52.0
        ],

        4 => [

            'gameweek' =>
                4,

            'starting_xi_projected_points' =>
                58.0
        ],

        5 => [

            'gameweek' =>
                5,

            'starting_xi_projected_points' =>
                55.0
        ]
    ]
];


$wildcardHorizon = [

    'status' =>
        'Available',

    'horizon' =>
        3,
        
    'projection_confidence' =>
        0.70,

    'gameweeks' => [

        3 => [

            'gameweek' =>
                3,

            'starting_xi_projected_points' =>
                58.0
        ],

        4 => [

            'gameweek' =>
                4,

            'starting_xi_projected_points' =>
                64.0
        ],

        5 => [

            'gameweek' =>
                5,

            'starting_xi_projected_points' =>
                61.0
        ]
    ]
];


$result =
    $service
        ->analyseHorizons(
            $currentHorizon,
            $wildcardHorizon
        );


wildcardTimingServiceCheck(
    'Service returns an array',
    is_array(
        $result
    )
);


wildcardTimingServiceCheck(
    'Current squad projected points total is 165',
    abs(
        (
            $result[
                'current_squad_projected_points'
            ]
            ??
            0.0
        )
        -
        165.0
    ) < 0.0001
);


wildcardTimingServiceCheck(
    'Wildcard squad projected points total is 183',
    abs(
        (
            $result[
                'wildcard_squad_projected_points'
            ]
            ??
            0.0
        )
        -
        183.0
    ) < 0.0001
);


wildcardTimingServiceCheck(
    'Projected Wildcard gain is 18 points',
    abs(
        (
            $result[
                'projected_points_gain'
            ]
            ??
            0.0
        )
        -
        18.0
    ) < 0.0001
);


wildcardTimingServiceCheck(
    'Immediate value reports that Wildcard improves the squad',
    (
        $result[
            'improves_squad'
        ]
        ??
        false
    )
    === true
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Service delegates decision creation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Service delegates decision creation<br>';

echo
    '============================================<br>';


wildcardTimingServiceCheck(
    'Service returns a ChipDecision',
    (
        $result[
            'decision'
        ]
        ??
        null
    )
    instanceof
    ChipDecision
);


wildcardTimingServiceCheck(
    'Decision identifies the Wildcard chip',
    (
        $result[
            'decision'
        ]
        ??
        null
    )
    instanceof
    ChipDecision
    &&
    $result[
        'decision'
    ]
        ->getChip()
    ===
    'Wildcard'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Projection confidence comes from horizons
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Projection confidence comes from horizons<br>';

echo
    '============================================<br>';


$confidenceResult =
    $service
        ->analyseHorizons(
            $currentHorizon,
            $wildcardHorizon
        );


wildcardTimingServiceCheck(
    'Weaker horizon confidence limits final decision confidence',
    (
        $confidenceResult[
            'decision'
        ]
        ??
        null
    )
    instanceof
    ChipDecision
    &&
    abs(
        $confidenceResult[
            'decision'
        ]
            ->getConfidence()
        -
        0.70
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