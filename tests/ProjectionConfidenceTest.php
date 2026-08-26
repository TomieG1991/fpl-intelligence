<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Projection Confidence Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function projectionConfidenceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


$projectionConfidence =
    new ProjectionConfidence();


/*
 * ============================================================
 * SCENARIO A
 * FULL EVIDENCE REGULAR STARTER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Full Evidence Regular Starter<br>";
echo "============================================<br>";


$model =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    100,

                'chance_of_playing' =>
                    100
            ]
        );


projectionConfidenceCheck(
    'Complete stable starter produces 100 percent projection confidence',
    abs(
        (
            (float) (
                $model[
                    'confidence_percent'
                ]
                ?? 0
            )
        )
        -
        100
    )
    < 0.001
);


projectionConfidenceCheck(
    'Complete stable starter is classified High',
    (
        $model[
            'confidence_label'
        ]
        ?? null
    )
    ===
    'High'
);


echo "Projection Confidence: "
    . number_format(
        (float) (
            $model[
                'confidence_percent'
            ]
            ?? 0
        ),
        2
    )
    . "%<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * EARLY-SEASON STARTER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Early-Season Starter<br>";
echo "============================================<br>";


$model =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    1,

                'appearance_sample_size' =>
                    1,

                'participation_rate' =>
                    100,

                'chance_of_playing' =>
                    100
            ]
        );


projectionConfidenceCheck(
    'GW1-only starter confidence remains below complete-sample confidence',
    (
        (float) (
            $model[
                'confidence_percent'
            ]
            ?? 100
        )
    )
    < 100
);


projectionConfidenceCheck(
    'GW1-only starter still benefits from stable participation and availability',
    (
        (float) (
            $model[
                'confidence_percent'
            ]
            ?? 0
        )
    )
    > 50
);


echo "GW1 Confidence: "
    . number_format(
        (float) (
            $model[
                'confidence_percent'
            ]
            ?? 0
        ),
        2
    )
    . "%<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * ROTATION UNCERTAINTY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Rotation Uncertainty<br>";
echo "============================================<br>";


$stableStarter =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    100,

                'chance_of_playing' =>
                    100
            ]
        );


$rotationPlayer =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    3,

                'participation_rate' =>
                    50,

                'chance_of_playing' =>
                    100
            ]
        );


projectionConfidenceCheck(
    'Rotation player has lower projection confidence than stable starter',
    (
        (float) (
            $rotationPlayer[
                'confidence_percent'
            ]
            ?? 100
        )
    )
    <
    (
        (float) (
            $stableStarter[
                'confidence_percent'
            ]
            ?? 0
        )
    )
);


projectionConfidenceCheck(
    '50 percent participation produces minimum participation stability',
    abs(
        (
            (float) (
                $rotationPlayer[
                    'components'
                ][
                    'participation_stability'
                ]
                ?? 999
            )
        )
        -
        0
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * AVAILABILITY UNCERTAINTY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Availability Uncertainty<br>";
echo "============================================<br>";


$model100 =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    100,

                'chance_of_playing' =>
                    100
            ]
        );


$model75 =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    100,

                'chance_of_playing' =>
                    75
            ]
        );


$model50 =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    100,

                'chance_of_playing' =>
                    50
            ]
        );


projectionConfidenceCheck(
    '75 percent availability is less certain than 100 percent availability',
    (
        (float) (
            $model75[
                'confidence_percent'
            ]
            ?? 100
        )
    )
    <
    (
        (float) (
            $model100[
                'confidence_percent'
            ]
            ?? 0
        )
    )
);


projectionConfidenceCheck(
    '50 percent availability is less certain than 75 percent availability',
    (
        (float) (
            $model50[
                'confidence_percent'
            ]
            ?? 100
        )
    )
    <
    (
        (float) (
            $model75[
                'confidence_percent'
            ]
            ?? 0
        )
    )
);


projectionConfidenceCheck(
    '50 percent chance of playing has zero availability certainty',
    abs(
        (
            (float) (
                $model50[
                    'components'
                ][
                    'availability_certainty'
                ]
                ?? 999
            )
        )
        -
        0
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * CERTAIN UNAVAILABLE PLAYER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Certain Unavailable Player<br>";
echo "============================================<br>";


$model =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    100,

                'chance_of_playing' =>
                    0
            ]
        );


projectionConfidenceCheck(
    'Zero percent availability can still be highly certain',
    abs(
        (
            (float) (
                $model[
                    'components'
                ][
                    'availability_certainty'
                ]
                ?? 0
            )
        )
        -
        100
    )
    < 0.001
);


projectionConfidenceCheck(
    'Projection Confidence measures certainty rather than player quality',
    (
        (float) (
            $model[
                'confidence_percent'
            ]
            ?? 0
        )
    )
    > 75
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * CONSISTENT NON-PARTICIPANT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Consistent Non-Participant<br>";
echo "============================================<br>";


$model =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    0,

                'participation_rate' =>
                    0,

                'chance_of_playing' =>
                    100
            ]
        );


projectionConfidenceCheck(
    'Zero participation can still be highly stable',
    abs(
        (
            (float) (
                $model[
                    'components'
                ][
                    'participation_stability'
                ]
                ?? 0
            )
        )
        -
        100
    )
    < 0.001
);


projectionConfidenceCheck(
    'Non-participation does not automatically mean projection uncertainty',
    (
        (float) (
            $model[
                'confidence_percent'
            ]
            ?? 0
        )
    )
    > 50
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * NO HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: No Historical Evidence<br>";
echo "============================================<br>";


$model =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    0,

                'appearance_sample_size' =>
                    0,

                'participation_rate' =>
                    null,

                'chance_of_playing' =>
                    100
            ]
        );


projectionConfidenceCheck(
    'No-history projection does not receive High confidence',
    (
        $model[
            'confidence_label'
        ]
        ?? null
    )
    !==
    'High'
);


projectionConfidenceCheck(
    'No-history historical sample component is zero',
    abs(
        (
            (float) (
                $model[
                    'components'
                ][
                    'historical_sample'
                ]
                ?? 999
            )
        )
        -
        0
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * CLASSIFICATION BOUNDARIES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Classification Boundaries<br>";
echo "============================================<br>";


projectionConfidenceCheck(
    '80 is High',
    $projectionConfidence
        ->classify(
            80
        )
    ===
    'High'
);


projectionConfidenceCheck(
    '79.99 is Moderate',
    $projectionConfidence
        ->classify(
            79.99
        )
    ===
    'Moderate'
);


projectionConfidenceCheck(
    '60 is Moderate',
    $projectionConfidence
        ->classify(
            60
        )
    ===
    'Moderate'
);


projectionConfidenceCheck(
    '59.99 is Low',
    $projectionConfidence
        ->classify(
            59.99
        )
    ===
    'Low'
);


projectionConfidenceCheck(
    '40 is Low',
    $projectionConfidence
        ->classify(
            40
        )
    ===
    'Low'
);


projectionConfidenceCheck(
    '39.99 is Very Low',
    $projectionConfidence
        ->classify(
            39.99
        )
    ===
    'Very Low'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Bounds<br>";
echo "============================================<br>";


$high =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    999,

                'appearance_sample_size' =>
                    999,

                'participation_rate' =>
                    999,

                'chance_of_playing' =>
                    999
            ]
        );


$low =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    -10,

                'appearance_sample_size' =>
                    -10,

                'participation_rate' =>
                    50,

                'chance_of_playing' =>
                    50
            ]
        );


projectionConfidenceCheck(
    'Projection Confidence cannot exceed 100 percent',
    (
        (float) (
            $high[
                'confidence_percent'
            ]
            ?? 999
        )
    )
    <= 100
);


projectionConfidenceCheck(
    'Projection Confidence cannot fall below zero percent',
    (
        (float) (
            $low[
                'confidence_percent'
            ]
            ?? -999
        )
    )
    >= 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * MODEL CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Model Contract<br>";
echo "============================================<br>";


$model =
    $projectionConfidence
        ->calculate(
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    100,

                'chance_of_playing' =>
                    100
            ]
        );


foreach (
    [
        'confidence',
        'confidence_percent',
        'confidence_label',
        'components',
        'fixture_sample_size',
        'appearance_sample_size'
    ]
    as $field
) {

    projectionConfidenceCheck(
        'Projection Confidence exposes '
        . $field,
        array_key_exists(
            $field,
            $model
        )
    );
}


foreach (
    [
        'historical_sample',
        'participation_stability',
        'availability_certainty'
    ]
    as $component
) {

    projectionConfidenceCheck(
        'Projection Confidence exposes component '
        . $component,
        array_key_exists(
            $component,
            $model[
                'components'
            ]
            ?? []
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Projection Confidence Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}