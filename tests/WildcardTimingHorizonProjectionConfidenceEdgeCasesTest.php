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

function wildcardHorizonConfidenceEdgeCheck(
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
 * HORIZON BUILDER
 * ============================================================
 */

function buildWildcardConfidenceHorizon(
    float $gameweek3Points,
    float $gameweek4Points,
    ?float $projectionConfidence
): array {

    return [

        'status' =>
            'Available',

        'horizon' =>
            2,

        'projection_confidence' =>
            $projectionConfidence,

        'gameweeks' => [

            3 => [

                'gameweek' =>
                    3,

                'starting_xi_projected_points' =>
                    $gameweek3Points
            ],

            4 => [

                'gameweek' =>
                    4,

                'starting_xi_projected_points' =>
                    $gameweek4Points
            ]
        ]
    ];
}


/*
 * ============================================================
 * START
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Wildcard Timing Horizon Projection Confidence Edge Cases Test<br>';

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
 * Scenario A: Current confidence missing
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Current confidence missing<br>';

echo
    '============================================<br>';


$currentMissingResult =
    $service->analyseHorizons(
        buildWildcardConfidenceHorizon(
            50.0,
            50.0,
            null
        ),
        buildWildcardConfidenceHorizon(
            57.5,
            57.5,
            0.67
        )
    );


wildcardHorizonConfidenceEdgeCheck(
    'Current-missing comparison is unavailable',
    (
        $currentMissingResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


wildcardHorizonConfidenceEdgeCheck(
    'Current-missing comparison has no decision',
    (
        $currentMissingResult[
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
 * Scenario B: Wildcard confidence missing
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Wildcard confidence missing<br>';

echo
    '============================================<br>';


$wildcardMissingResult =
    $service->analyseHorizons(
        buildWildcardConfidenceHorizon(
            50.0,
            50.0,
            0.82
        ),
        buildWildcardConfidenceHorizon(
            57.5,
            57.5,
            null
        )
    );


wildcardHorizonConfidenceEdgeCheck(
    'Wildcard-missing comparison is unavailable',
    (
        $wildcardMissingResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


wildcardHorizonConfidenceEdgeCheck(
    'Wildcard-missing comparison has no decision',
    (
        $wildcardMissingResult[
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
 * Scenario C: Both confidences missing
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Both confidences missing<br>';

echo
    '============================================<br>';


$bothMissingResult =
    $service->analyseHorizons(
        buildWildcardConfidenceHorizon(
            50.0,
            50.0,
            null
        ),
        buildWildcardConfidenceHorizon(
            57.5,
            57.5,
            null
        )
    );


wildcardHorizonConfidenceEdgeCheck(
    'Both-missing comparison is unavailable',
    (
        $bothMissingResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


wildcardHorizonConfidenceEdgeCheck(
    'Both-missing comparison has no decision',
    (
        $bothMissingResult[
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
 * Scenario D: Both confidences available
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Both confidences available<br>';

echo
    '============================================<br>';


$bothAvailableResult =
    $service->analyseHorizons(
        buildWildcardConfidenceHorizon(
            50.0,
            50.0,
            0.82
        ),
        buildWildcardConfidenceHorizon(
            57.5,
            57.5,
            0.67
        )
    );


$bothAvailableDecision =
    $bothAvailableResult[
        'decision'
    ]
    ??
    null;


wildcardHorizonConfidenceEdgeCheck(
    'Both-available comparison remains available',
    (
        $bothAvailableResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


wildcardHorizonConfidenceEdgeCheck(
    'Both-available comparison produces ChipDecision',
    $bothAvailableDecision
    instanceof
    ChipDecision
);


wildcardHorizonConfidenceEdgeCheck(
    'Both-available comparison uses weaker confidence 0.67',
    $bothAvailableDecision
    instanceof
    ChipDecision
    &&
    abs(
        $bothAvailableDecision->getConfidence()
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