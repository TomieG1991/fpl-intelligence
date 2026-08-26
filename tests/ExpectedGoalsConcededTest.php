<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Expected Goals Conceded Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function expectedGcCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


$model =
    new ExpectedGoalsConceded();


/*
 * ============================================================
 * SCENARIO A
 * POSITION BASELINES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Position Baselines<br>";
echo "============================================<br>";


$baselines = [

    'GK' =>
        1.46,

    'DEF' =>
        1.33
];


foreach (
    $baselines
    as $position => $baseline
) {

    $result =
        $model
            ->calculate(
                $position,
                90,
                [
                    'appearance_sample_size' =>
                        0,

                    'weighted_metrics' => [

                        'expected_goals_conceded_per_90' =>
                            10.0
                    ]
                ]
            );


    expectedGcCheck(
        $position
        . ' uses correct xGC position baseline',
        abs(
            (
                (float) (
                    $result[
                        'position_baseline'
                    ]
                    ?? 0
                )
            )
            -
            $baseline
        )
        <
        0.001
    );


    expectedGcCheck(
        $position
        . ' zero-appearance sample fully regresses to baseline',
        abs(
            (
                (float) (
                    $result[
                        'regressed_xgc_per_90'
                    ]
                    ?? 0
                )
            )
            -
            $baseline
        )
        <
        0.001
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * EARLY-SAMPLE REGRESSION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Early-Sample Regression<br>";
echo "============================================<br>";


$result =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'appearance_sample_size' =>
                    1,

                'weighted_metrics' => [

                    'expected_goals_conceded_per_90' =>
                        4.0
                ]
            ]
        );


expectedGcCheck(
    'One appearance produces 20 percent xGC sample confidence',
    abs(
        (
            (float) (
                $result[
                    'sample_confidence'
                ]
                ?? 0
            )
        )
        -
        0.20
    )
    <
    0.001
);


expectedGcCheck(
    'One-match defender xGC regresses toward defender prior',
    abs(
        (
            (float) (
                $result[
                    'regressed_xgc_per_90'
                ]
                ?? 0
            )
        )
        -
        1.864
    )
    <
    0.001
);


expectedGcCheck(
    'Early defender sample remains below raw 4.0 xGC per 90',
    (
        (float) (
            $result[
                'regressed_xgc_per_90'
            ]
            ?? 999
        )
    )
    <
    4.0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * MATURE SAMPLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Mature Sample<br>";
echo "============================================<br>";


$result =
    $model
        ->calculate(
            'GK',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'expected_goals_conceded_per_90' =>
                        2.25
                ]
            ]
        );


expectedGcCheck(
    'Five appearances produce full xGC sample confidence',
    abs(
        (
            (float) (
                $result[
                    'sample_confidence'
                ]
                ?? 0
            )
        )
        -
        1.0
    )
    <
    0.001
);


expectedGcCheck(
    'Mature sample preserves goalkeeper xGC rate',
    abs(
        (
            (float) (
                $result[
                    'regressed_xgc_per_90'
                ]
                ?? 0
            )
        )
        -
        2.25
    )
    <
    0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * PROJECTED MINUTES SCALING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Projected Minutes Scaling<br>";
echo "============================================<br>";


$result90 =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'expected_goals_conceded_per_90' =>
                        2.0
                ]
            ]
        );


$result45 =
    $model
        ->calculate(
            'DEF',
            45,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'expected_goals_conceded_per_90' =>
                        2.0
                ]
            ]
        );


expectedGcCheck(
    '90 projected minutes preserve xGC rate',
    abs(
        (
            (float) (
                $result90[
                    'projected_xgc'
                ]
                ?? 0
            )
        )
        -
        2.0
    )
    <
    0.001
);


expectedGcCheck(
    '45 projected minutes halve projected xGC',
    abs(
        (
            (float) (
                $result45[
                    'projected_xgc'
                ]
                ?? 0
            )
        )
        -
        1.0
    )
    <
    0.001
);


expectedGcCheck(
    'Reduced projected minutes reduce expected deduction magnitude',
    (
        (float) (
            $result45[
                'expected_deduction_magnitude'
            ]
            ?? 999
        )
    )
    <
    (
        (float) (
            $result90[
                'expected_deduction_magnitude'
            ]
            ?? 0
        )
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * FIXTURE ATTACK EFFECT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Opponent Attack Effect<br>";
echo "============================================<br>";


$weakAttack =
    $model
        ->calculate(
            'GK',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'expected_goals_conceded_per_90' =>
                        1.5
                ]
            ],
            [
                'opponent_attack_rating' =>
                    0
            ]
        );


$neutralAttack =
    $model
        ->calculate(
            'GK',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'expected_goals_conceded_per_90' =>
                        1.5
                ]
            ],
            [
                'opponent_attack_rating' =>
                    50
            ]
        );


$strongAttack =
    $model
        ->calculate(
            'GK',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'expected_goals_conceded_per_90' =>
                        1.5
                ]
            ],
            [
                'opponent_attack_rating' =>
                    100
            ]
        );


expectedGcCheck(
    'Weak opponent attack reduces projected xGC',
    (
        (float) $weakAttack[
            'projected_xgc'
        ]
    )
    <
    (
        (float) $neutralAttack[
            'projected_xgc'
        ]
    )
);


expectedGcCheck(
    'Strong opponent attack increases projected xGC',
    (
        (float) $strongAttack[
            'projected_xgc'
        ]
    )
    >
    (
        (float) $neutralAttack[
            'projected_xgc'
        ]
    )
);


expectedGcCheck(
    'Strong opponent attack increases expected deduction magnitude',
    (
        (float) $strongAttack[
            'expected_deduction_magnitude'
        ]
    )
    >
    (
        (float) $weakAttack[
            'expected_deduction_magnitude'
        ]
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * POISSON DEDUCTION BEHAVIOUR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Poisson Deduction Behaviour<br>";
echo "============================================<br>";


$deduction0 =
    $model
        ->expectedDeductionMagnitude(
            0
        );


$deduction1 =
    $model
        ->expectedDeductionMagnitude(
            1
        );


$deduction2 =
    $model
        ->expectedDeductionMagnitude(
            2
        );


$deduction3 =
    $model
        ->expectedDeductionMagnitude(
            3
        );


expectedGcCheck(
    'Zero projected xGC produces zero expected deduction',
    abs(
        $deduction0
    )
    <
    0.001
);


expectedGcCheck(
    'Expected deduction rises above zero at one projected xGC',
    $deduction1
    >
    0
);


expectedGcCheck(
    'Higher projected xGC increases expected deduction',
    $deduction2
    >
    $deduction1
    &&
    $deduction3
    >
    $deduction2
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * POSITION APPLICABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Position Applicability<br>";
echo "============================================<br>";


foreach (
    [
        'MID',
        'FWD'
    ]
    as $position
) {

    $result =
        $model
            ->calculate(
                $position,
                90,
                [
                    'appearance_sample_size' =>
                        5,

                    'weighted_metrics' => [

                        'expected_goals_conceded_per_90' =>
                            2.0
                    ]
                ]
            );


    expectedGcCheck(
        $position
        . ' goals-conceded deduction is Not Applicable',
        (
            $result[
                'status'
            ]
            ?? null
        )
        ===
        'Not Applicable'
    );


    expectedGcCheck(
        $position
        . ' goals-conceded expected points remain zero',
        abs(
            (
                (float) (
                    $result[
                        'expected_points'
                    ]
                    ?? -1
                )
            )
        )
        <
        0.001
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * MISSING HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Missing Historical xGC<br>";
echo "============================================<br>";


$result =
    $model
        ->calculate(
            'GK',
            90,
            [
                'appearance_sample_size' =>
                    1,

                'weighted_metrics' =>
                    []
            ]
        );


expectedGcCheck(
    'Missing goalkeeper xGC history is Insufficient Data',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Insufficient Data'
);


expectedGcCheck(
    'Missing goalkeeper xGC history produces zero deduction',
    abs(
        (
            (float) (
                $result[
                    'expected_points'
                ]
                ?? -1
            )
        )
    )
    <
    0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * INVALID POSITION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Invalid Position<br>";
echo "============================================<br>";


$result =
    $model
        ->calculate(
            'INVALID',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'expected_goals_conceded_per_90' =>
                        2.0
                ]
            ]
        );


expectedGcCheck(
    'Invalid position is Unsupported Position',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unsupported Position'
);


expectedGcCheck(
    'Invalid position produces zero expected points',
    abs(
        (
            (float) (
                $result[
                    'expected_points'
                ]
                ?? -1
            )
        )
    )
    <
    0.001
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


$result =
    $model
        ->calculate(
            'DEF',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'expected_goals_conceded_per_90' =>
                        1.5
                ]
            ]
        );


foreach (
    [
        'position',
        'status',
        'raw_xgc_per_90',
        'position_baseline',
        'appearance_sample_size',
        'sample_confidence',
        'sample_confidence_percent',
        'regressed_xgc_per_90',
        'opponent_attack_rating',
        'fixture_multiplier',
        'projected_minutes',
        'projected_xgc',
        'expected_deduction_magnitude',
        'expected_points'
    ]
    as $field
) {

    expectedGcCheck(
        'Expected Goals Conceded exposes '
        . $field,
        array_key_exists(
            $field,
            $result
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
echo "Expected Goals Conceded Test Summary<br>";
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