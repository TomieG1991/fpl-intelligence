<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Expected Points Inputs Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function expectedPointsInputsCheck(
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
 * BASE FORM SAMPLE
 * ============================================================
 */

$form = [

    'fixture_sample_size' =>
        5,

    'appearance_sample_size' =>
        5,

    'weighted_metrics' => [

        'expected_goals_per_90' =>
            0.60,

        'expected_assists_per_90' =>
            0.30,

        'clean_sheet_rate' =>
            40.0
    ]
];


$minutes = [

    'projected_minutes' =>
        90
];


/*
 * ============================================================
 * SCENARIO A
 * NEUTRAL FIXTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Neutral Fixture<br>";
echo "============================================<br>";


$model =
    $builder
        ->build(
            'MID',
            $minutes,
            $form,
            [
                'fixture_opportunity' =>
                    50,

                'opponent_attack_rating' =>
                    50
            ]
        );


expectedPointsInputsCheck(
    'Expected Goals preserve per-90 value over 90 projected minutes',
    abs(
        (
            (float) (
                $model[
                    'expected_goals'
                ]
                ?? 0
            )
        )
        -
        0.60
    )
    < 0.001
);


expectedPointsInputsCheck(
    'Expected Assists preserve per-90 value over 90 projected minutes',
    abs(
        (
            (float) (
                $model[
                    'expected_assists'
                ]
                ?? 0
            )
        )
        -
        0.30
    )
    < 0.001
);


expectedPointsInputsCheck(
    'Neutral fixture multiplier is one',
    abs(
        (
            (float) (
                $model[
                    'evidence'
                ][
                    'fixture_multiplier'
                ]
                ?? 0
            )
        )
        -
        1
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * EXPECTED MINUTES SCALING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Expected Minutes Scaling<br>";
echo "============================================<br>";


$model45 =
    $builder
        ->build(
            'MID',
            [
                'projected_minutes' =>
                    45
            ],
            $form,
            [
                'fixture_opportunity' =>
                    50
            ]
        );


expectedPointsInputsCheck(
    '45 projected minutes halve 90-minute expected goals',
    abs(
        (
            (float) (
                $model45[
                    'expected_goals'
                ]
                ?? 0
            )
        )
        -
        0.30
    )
    < 0.001
);


expectedPointsInputsCheck(
    '45 projected minutes halve 90-minute expected assists',
    abs(
        (
            (float) (
                $model45[
                    'expected_assists'
                ]
                ?? 0
            )
        )
        -
        0.15
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * FIXTURE ATTACKING EFFECT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Fixture Attacking Effect<br>";
echo "============================================<br>";


$poorFixture =
    $builder
        ->build(
            'FWD',
            $minutes,
            $form,
            [
                'fixture_opportunity' =>
                    0
            ]
        );


$neutralFixture =
    $builder
        ->build(
            'FWD',
            $minutes,
            $form,
            [
                'fixture_opportunity' =>
                    50
            ]
        );


$excellentFixture =
    $builder
        ->build(
            'FWD',
            $minutes,
            $form,
            [
                'fixture_opportunity' =>
                    100
            ]
        );


expectedPointsInputsCheck(
    'Poor fixture reduces attacking expectation',
    (
        (float) $poorFixture[
            'expected_goals'
        ]
    )
    <
    (
        (float) $neutralFixture[
            'expected_goals'
        ]
    )
);


expectedPointsInputsCheck(
    'Excellent fixture improves attacking expectation',
    (
        (float) $excellentFixture[
            'expected_goals'
        ]
    )
    >
    (
        (float) $neutralFixture[
            'expected_goals'
        ]
    )
);


expectedPointsInputsCheck(
    'Maximum attacking fixture multiplier is conservative 1.25',
    abs(
        (
            (float) $excellentFixture[
                'evidence'
            ][
                'fixture_multiplier'
            ]
        )
        -
        1.25
    )
    < 0.001
);


expectedPointsInputsCheck(
    'Minimum attacking fixture multiplier is conservative 0.75',
    abs(
        (
            (float) $poorFixture[
                'evidence'
            ][
                'fixture_multiplier'
            ]
        )
        -
        0.75
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * EARLY-SEASON CLEAN-SHEET REGRESSION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Early-Season Clean-Sheet Regression<br>";
echo "============================================<br>";


$earlyCleanSheetForm = [

    'fixture_sample_size' =>
        1,

    'appearance_sample_size' =>
        1,

    'weighted_metrics' => [

        'clean_sheet_rate' =>
            100
    ]
];


$model =
    $builder
        ->build(
            'GK',
            $minutes,
            $earlyCleanSheetForm,
            [
                'opponent_attack_rating' =>
                    50
            ]
        );


expectedPointsInputsCheck(
    'One clean sheet does not become 100 percent clean-sheet probability',
    (
        (float) (
            $model[
                'clean_sheet_probability'
            ]
            ?? 100
        )
    )
    < 100
);


expectedPointsInputsCheck(
    'One-fixture clean-sheet evidence has 20 percent sample confidence',
    abs(
        (
            (float) (
                $model[
                    'evidence'
                ][
                    'clean_sheet_sample_confidence'
                ]
                ?? 0
            )
        )
        -
        0.20
    )
    < 0.001
);


expectedPointsInputsCheck(
    'One perfect clean-sheet sample regresses to 44 percent before opponent adjustment',
    abs(
        (
            (float) (
                $model[
                    'clean_sheet_probability'
                ]
                ?? 0
            )
        )
        -
        44.0
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * OPPONENT ATTACK EFFECT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Opponent Attack Effect<br>";
echo "============================================<br>";


$defensiveForm = [

    'fixture_sample_size' =>
        5,

    'appearance_sample_size' =>
        5,

    'weighted_metrics' => [

        'clean_sheet_rate' =>
            40
    ]
];


$weakAttack =
    $builder
        ->build(
            'DEF',
            $minutes,
            $defensiveForm,
            [
                'opponent_attack_rating' =>
                    0
            ]
        );


$neutralAttack =
    $builder
        ->build(
            'DEF',
            $minutes,
            $defensiveForm,
            [
                'opponent_attack_rating' =>
                    50
            ]
        );


$strongAttack =
    $builder
        ->build(
            'DEF',
            $minutes,
            $defensiveForm,
            [
                'opponent_attack_rating' =>
                    100
            ]
        );


expectedPointsInputsCheck(
    'Weak opponent attack improves clean-sheet probability',
    (
        (float) $weakAttack[
            'clean_sheet_probability'
        ]
    )
    >
    (
        (float) $neutralAttack[
            'clean_sheet_probability'
        ]
    )
);


expectedPointsInputsCheck(
    'Strong opponent attack reduces clean-sheet probability',
    (
        (float) $strongAttack[
            'clean_sheet_probability'
        ]
    )
    <
    (
        (float) $neutralAttack[
            'clean_sheet_probability'
        ]
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * NO ATTACKING HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Missing Attacking History<br>";
echo "============================================<br>";


$model =
    $builder
        ->build(
            'FWD',
            $minutes,
            [
                'fixture_sample_size' =>
                    0,

                'appearance_sample_size' =>
                    0,

                'weighted_metrics' =>
                    []
            ]
        );


expectedPointsInputsCheck(
    'Missing xG history safely produces zero expected goals',
    abs(
        (
            (float) (
                $model[
                    'expected_goals'
                ]
                ?? 999
            )
        )
    )
    < 0.001
);


expectedPointsInputsCheck(
    'Missing xA history safely produces zero expected assists',
    abs(
        (
            (float) (
                $model[
                    'expected_assists'
                ]
                ?? 999
            )
        )
    )
    < 0.001
);


expectedPointsInputsCheck(
    'Missing clean-sheet history uses neutral 30 percent prior',
    abs(
        (
            (float) (
                $model[
                    'clean_sheet_probability'
                ]
                ?? 0
            )
        )
        -
        30
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * SPECIALIST COMPONENT STATUS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Specialist Component Status<br>";
echo "============================================<br>";


$defensiveForm = [

    'fixture_sample_size' =>
        5,

    'appearance_sample_size' =>
        5,

    'weighted_metrics' => [

        'cbit_per_90' =>
            10.0,
            
        'bps_per_90' =>
            35.0,

        'clean_sheet_rate' =>
            40.0
    ]
];


$model =
    $builder
        ->build(
            'DEF',
            $minutes,
            $defensiveForm,
            [
                'opponent_attack_rating' =>
                    50
            ]
        );


expectedPointsInputsCheck(
    'Expected Saves remain zero for outfield player',
    abs(
        (float) (
            $model[
                'expected_saves'
            ]
            ?? -1
        )
    )
    < 0.001
);


expectedPointsInputsCheck(
    'Expected Bonus is produced by the dedicated bonus model',
    (
        (float) (
            $model[
                'expected_bonus'
            ]
            ?? 0
        )
    )
    > 0
);


expectedPointsInputsCheck(
    'Bonus is explicitly Modelled',
    (
        $model[
            'specialist_components'
        ][
            'bonus'
        ]
        ?? null
    )
    ===
    'Modelled'
);


expectedPointsInputsCheck(
    'Bonus evidence is exposed',
    isset(
        $model[
            'evidence'
        ][
            'bonus'
        ]
    )
    &&
    is_array(
        $model[
            'evidence'
        ][
            'bonus'
        ]
    )
);


expectedPointsInputsCheck(
    'Defender receives modelled defensive-contribution expectation',
    (
        (float) (
            $model[
                'expected_defensive_contribution_points'
            ]
            ?? 0
        )
    )
    > 0
);


expectedPointsInputsCheck(
    'Outfield save status is Not Applicable',
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


expectedPointsInputsCheck(
    'Defensive contributions are explicitly Modelled',
    (
        $model[
            'specialist_components'
        ][
            'defensive_contributions'
        ]
        ?? null
    )
    ===
    'Modelled'
);


expectedPointsInputsCheck(
    'Defensive contribution evidence is exposed',
    isset(
        $model[
            'evidence'
        ][
            'defensive_contributions'
        ]
    )
    &&
    is_array(
        $model[
            'evidence'
        ][
            'defensive_contributions'
        ]
    )
);

$model =
    $builder
        ->build(
            'MID',
            $minutes,
            [
                'fixture_sample_size' =>
                    1,

                'appearance_sample_size' =>
                    1,

                'weighted_metrics' => [

                    'expected_goals_per_90' =>
                        0.2,

                    'expected_assists_per_90' =>
                        0.1
                ]
            ]
        );


expectedPointsInputsCheck(
    'Missing BPS history produces zero expected bonus',
    abs(
        (float) (
            $model[
                'expected_bonus'
            ]
            ?? -1
        )
    )
    < 0.001
);


expectedPointsInputsCheck(
    'Missing BPS history is explicitly Insufficient Data',
    (
        $model[
            'specialist_components'
        ][
            'bonus'
        ]
        ?? null
    )
    ===
    'Insufficient Data'
);


echo "<br>";

/*
 * ============================================================
 * SCENARIO G2
 * MISSING DEFENSIVE EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G2: Missing Defensive Evidence<br>";
echo "============================================<br>";


$model =
    $builder
        ->build(
            'DEF',
            $minutes,
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


expectedPointsInputsCheck(
    'Missing CBIT history produces zero expected defensive contribution points',
    abs(
        (float) (
            $model[
                'expected_defensive_contribution_points'
            ]
            ?? -1
        )
    )
    < 0.001
);


expectedPointsInputsCheck(
    'Missing CBIT history is explicitly Insufficient Data',
    (
        $model[
            'specialist_components'
        ][
            'defensive_contributions'
        ]
        ?? null
    )
    ===
    'Insufficient Data'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * INPUT BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Bounds<br>";
echo "============================================<br>";


$model =
    $builder
        ->build(
            'MID',
            [
                'projected_minutes' =>
                    999
            ],
            [
                'fixture_sample_size' =>
                    999,

                'appearance_sample_size' =>
                    999,

                'weighted_metrics' => [

                    'expected_goals_per_90' =>
                        -10,

                    'expected_assists_per_90' =>
                        -10,

                    'clean_sheet_rate' =>
                        999
                ]
            ],
            [
                'fixture_opportunity' =>
                    999,

                'opponent_attack_rating' =>
                    -100
            ]
        );


expectedPointsInputsCheck(
    'Projected Minutes are capped at 90',
    abs(
        (
            (float) $model[
                'projected_minutes'
            ]
        )
        -
        90
    )
    < 0.001
);


expectedPointsInputsCheck(
    'Negative historical expected goals are safely bounded',
    (
        (float) $model[
            'expected_goals'
        ]
    )
    >= 0
);


expectedPointsInputsCheck(
    'Clean-sheet probability remains within 0-100',
    (
        (float) $model[
            'clean_sheet_probability'
        ]
    )
    >= 0
    &&
    (
        (float) $model[
            'clean_sheet_probability'
        ]
    )
    <= 100
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * MODEL CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Model Contract<br>";
echo "============================================<br>";


$model =
    $builder
        ->build(
            'MID',
            $minutes,
            $form
        );


foreach (
    [
        'projected_minutes',
        'expected_goals',
        'expected_assists',
        'clean_sheet_probability',
        'expected_saves',
        'expected_bonus',
        'expected_defensive_contribution_points',
        'evidence',
        'sample',
        'specialist_components'
    ]
    as $field
) {

    expectedPointsInputsCheck(
        'Expected Points Inputs exposes '
        . $field,
        array_key_exists(
            $field,
            $model
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
echo "Expected Points Inputs Test Summary<br>";
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