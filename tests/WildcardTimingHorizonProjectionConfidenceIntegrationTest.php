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

function wildcardHorizonConfidenceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


/*
 * ============================================================
 * TEST DATA
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Wildcard Timing Horizon Projection Confidence Integration Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$currentHorizon = [

    'status' =>
        'Available',

    'horizon' =>
        2,

    'projection_confidence' =>
        0.82,

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
        ]
    ]
];


$wildcardHorizon = [

    'status' =>
        'Available',

    'horizon' =>
        2,

    'projection_confidence' =>
        0.67,

    'gameweeks' => [

        3 => [

            'gameweek' =>
                3,

            'starting_xi_projected_points' =>
                57.5
        ],

        4 => [

            'gameweek' =>
                4,

            'starting_xi_projected_points' =>
                57.5
        ]
    ]
];


/*
 * ============================================================
 * ANALYSE
 * ============================================================
 */

$service =
    new WildcardTimingIntelligenceService(
        new WildcardTimingIntelligence()
    );


/*
 * Deliberately do NOT supply the old third
 * projection-confidence argument.
 *
 * The service should now derive comparison confidence
 * from the two Squad Horizon results.
 */

$result =
    $service->analyseHorizons(
        $currentHorizon,
        $wildcardHorizon
    );


$decision =
    $result[
        'decision'
    ]
    ??
    null;


/*
 * ============================================================
 * Scenario A: Existing Wildcard comparison
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Existing Wildcard comparison<br>';

echo
    '============================================<br>';


wildcardHorizonConfidenceCheck(
    'Wildcard analysis remains available',
    (
        $result[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


wildcardHorizonConfidenceCheck(
    'Current squad projected points remain 100',
    isset(
        $result[
            'current_squad_projected_points'
        ]
    )
    &&
    abs(
        (
            (float) $result[
                'current_squad_projected_points'
            ]
        )
        -
        100.0
    ) < 0.0001
);


wildcardHorizonConfidenceCheck(
    'Wildcard squad projected points remain 115',
    isset(
        $result[
            'wildcard_squad_projected_points'
        ]
    )
    &&
    abs(
        (
            (float) $result[
                'wildcard_squad_projected_points'
            ]
        )
        -
        115.0
    ) < 0.0001
);


wildcardHorizonConfidenceCheck(
    'Projected Wildcard gain remains 15',
    isset(
        $result[
            'projected_points_gain'
        ]
    )
    &&
    abs(
        (
            (float) $result[
                'projected_points_gain'
            ]
        )
        -
        15.0
    ) < 0.0001
);


wildcardHorizonConfidenceCheck(
    'Wildcard still improves squad',
    (
        $result[
            'improves_squad'
        ]
        ??
        null
    )
    ===
    true
);


wildcardHorizonConfidenceCheck(
    'Decision remains a ChipDecision',
    $decision
    instanceof
    ChipDecision
);


wildcardHorizonConfidenceCheck(
    'Fifteen-point gain still produces Use recommendation',
    $decision
    instanceof
    ChipDecision
    &&
    $decision->getRecommendation()
    ===
    'Use'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Horizon-derived projection confidence
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Horizon-derived projection confidence<br>';

echo
    '============================================<br>';


wildcardHorizonConfidenceCheck(
    'Decision confidence uses weaker horizon confidence 0.67',
    $decision
    instanceof
    ChipDecision
    &&
    abs(
        $decision->getConfidence()
        -
        0.67
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