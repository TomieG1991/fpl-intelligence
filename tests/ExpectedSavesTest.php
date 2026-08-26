<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Expected Saves Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function expectedSavesCheck(
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


$builder =
    new ExpectedPointsInputs();


/*
 * ============================================================
 * BASE GOALKEEPER FORM
 * ============================================================
 */

$form = [

    'fixture_sample_size' =>
        5,

    'appearance_sample_size' =>
        5,

    'weighted_metrics' => [

        'saves_per_90' =>
            4.0,

        'clean_sheet_rate' =>
            40
    ]
];


$minutes90 = [

    'projected_minutes' =>
        90
];


/*
 * ============================================================
 * SCENARIO A
 * NEUTRAL OPPONENT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Neutral Opponent<br>";
echo "============================================<br>";


$model =
    $builder
        ->build(
            'GK',
            $minutes90,
            $form,
            [
                'opponent_attack_rating' =>
                    50
            ]
        );


expectedSavesCheck(
    'Neutral opponent preserves goalkeeper saves per 90',
    abs(
        (
            (float) (
                $model[
                    'expected_saves'
                ]
                ?? 0
            )
        )
        -
        4.0
    )
    < 0.001
);


expectedSavesCheck(
    'Neutral save multiplier is one',
    abs(
        (
            (float) (
                $model[
                    'evidence'
                ][
                    'save_opportunity_multiplier'
                ]
                ?? 0
            )
        )
        -
        1.0
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * MINUTES SCALING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Minutes Scaling<br>";
echo "============================================<br>";


$model =
    $builder
        ->build(
            'GK',
            [
                'projected_minutes' =>
                    45
            ],
            $form,
            [
                'opponent_attack_rating' =>
                    50
            ]
        );


expectedSavesCheck(
    '45 projected minutes halve expected saves',
    abs(
        (
            (float) (
                $model[
                    'expected_saves'
                ]
                ?? 0
            )
        )
        -
        2.0
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * OPPONENT ATTACK EFFECT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Opponent Attack Effect<br>";
echo "============================================<br>";


$weakAttack =
    $builder
        ->build(
            'GK',
            $minutes90,
            $form,
            [
                'opponent_attack_rating' =>
                    0
            ]
        );


$neutralAttack =
    $builder
        ->build(
            'GK',
            $minutes90,
            $form,
            [
                'opponent_attack_rating' =>
                    50
            ]
        );


$strongAttack =
    $builder
        ->build(
            'GK',
            $minutes90,
            $form,
            [
                'opponent_attack_rating' =>
                    100
            ]
        );


expectedSavesCheck(
    'Weak opponent attack reduces expected saves',
    (
        (float) $weakAttack[
            'expected_saves'
        ]
    )
    <
    (
        (float) $neutralAttack[
            'expected_saves'
        ]
    )
);


expectedSavesCheck(
    'Strong opponent attack increases expected saves',
    (
        (float) $strongAttack[
            'expected_saves'
        ]
    )
    >
    (
        (float) $neutralAttack[
            'expected_saves'
        ]
    )
);


expectedSavesCheck(
    'Minimum save multiplier is 0.85',
    abs(
        (
            (float) $weakAttack[
                'evidence'
            ][
                'save_opportunity_multiplier'
            ]
        )
        -
        0.85
    )
    < 0.001
);


expectedSavesCheck(
    'Maximum save multiplier is 1.15',
    abs(
        (
            (float) $strongAttack[
                'evidence'
            ][
                'save_opportunity_multiplier'
            ]
        )
        -
        1.15
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * NON-GOALKEEPER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Non-Goalkeeper<br>";
echo "============================================<br>";


$model =
    $builder
        ->build(
            'DEF',
            $minutes90,
            $form,
            [
                'opponent_attack_rating' =>
                    100
            ]
        );


expectedSavesCheck(
    'Outfield player receives zero expected saves',
    abs(
        (
            (float) (
                $model[
                    'expected_saves'
                ]
                ?? -1
            )
        )
    )
    < 0.001
);


expectedSavesCheck(
    'Outfield save component is Not Applicable',
    (
        $model[
            'specialist_components'
        ][
            'saves'
        ]
        ?? null
    )
    ===
    'Not Applicable'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * MISSING SAVE HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Missing Save History<br>";
echo "============================================<br>";


$model =
    $builder
        ->build(
            'GK',
            $minutes90,
            [
                'fixture_sample_size' =>
                    1,

                'appearance_sample_size' =>
                    1,

                'weighted_metrics' => [

                    'clean_sheet_rate' =>
                        100
                ]
            ]
        );


expectedSavesCheck(
    'Missing goalkeeper save history safely returns zero expected saves',
    abs(
        (
            (float) (
                $model[
                    'expected_saves'
                ]
                ?? -1
            )
        )
    )
    < 0.001
);


expectedSavesCheck(
    'Missing goalkeeper save history is explicit Insufficient Data',
    (
        $model[
            'specialist_components'
        ][
            'saves'
        ]
        ?? null
    )
    ===
    'Insufficient Data'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * MODELLED STATUS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Modelled Status<br>";
echo "============================================<br>";


$model =
    $builder
        ->build(
            'GK',
            $minutes90,
            $form
        );


expectedSavesCheck(
    'Goalkeeper with save history is explicitly Modelled',
    (
        $model[
            'specialist_components'
        ][
            'saves'
        ]
        ?? null
    )
    ===
    'Modelled'
);


expectedSavesCheck(
    'Expected Saves remains non-negative',
    (
        (float) (
            $model[
                'expected_saves'
            ]
            ?? -1
        )
    )
    >= 0
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Expected Saves Test Summary<br>";
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