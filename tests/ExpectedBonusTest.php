<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Expected Bonus Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function expectedBonusCheck(
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
    new ExpectedBonus();


/*
 * ============================================================
 * SCENARIO A
 * POSITION BASELINES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Position Baselines<br>";
echo "============================================<br>";


$positionBaselines = [

    'GK' =>
        12.0,

    'DEF' =>
        11.0,

    'MID' =>
        17.0,

    'FWD' =>
        9.13
];


foreach (
    $positionBaselines
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

                        'bps_per_90' =>
                            100
                    ]
                ]
            );


    expectedBonusCheck(
        $position
        . ' uses correct position BPS baseline',
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
        < 0.001
    );


    expectedBonusCheck(
        $position
        . ' zero-appearance sample fully regresses to baseline',
        abs(
            (
                (float) (
                    $result[
                        'regressed_bps_per_90'
                    ]
                    ?? 0
                )
            )
            -
            $baseline
        )
        < 0.001
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
            'MID',
            90,
            [
                'appearance_sample_size' =>
                    1,

                'weighted_metrics' => [

                    'bps_per_90' =>
                        40
                ]
            ]
        );


expectedBonusCheck(
    'One appearance produces 20 percent BPS sample confidence',
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
    < 0.001
);


expectedBonusCheck(
    'One-match midfielder BPS regresses toward midfielder prior',
    abs(
        (
            (float) (
                $result[
                    'regressed_bps_per_90'
                ]
                ?? 0
            )
        )
        -
        21.60
    )
    < 0.001
);


expectedBonusCheck(
    'Early midfielder sample remains below raw 40 BPS per 90',
    (
        (float) (
            $result[
                'regressed_bps_per_90'
            ]
            ?? 999
        )
    )
    <
    40
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
            'DEF',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'bps_per_90' =>
                        35
                ]
            ]
        );


expectedBonusCheck(
    'Five appearances produce full BPS sample confidence',
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
    < 0.001
);


expectedBonusCheck(
    'Mature sample preserves player BPS per 90',
    abs(
        (
            (float) (
                $result[
                    'regressed_bps_per_90'
                ]
                ?? 0
            )
        )
        -
        35
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * PROJECTED MINUTES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Projected Minutes Scaling<br>";
echo "============================================<br>";


$result90 =
    $model
        ->calculate(
            'MID',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'bps_per_90' =>
                        30
                ]
            ]
        );


$result45 =
    $model
        ->calculate(
            'MID',
            45,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'bps_per_90' =>
                        30
                ]
            ]
        );


expectedBonusCheck(
    '90 projected minutes preserve projected BPS',
    abs(
        (
            (float) (
                $result90[
                    'projected_bps'
                ]
                ?? 0
            )
        )
        -
        30
    )
    < 0.001
);


expectedBonusCheck(
    '45 projected minutes halve projected BPS',
    abs(
        (
            (float) (
                $result45[
                    'projected_bps'
                ]
                ?? 0
            )
        )
        -
        15
    )
    < 0.001
);


expectedBonusCheck(
    'Reduced projected minutes reduce expected bonus',
    (
        (float) (
            $result45[
                'expected_points'
            ]
            ?? 999
        )
    )
    <
    (
        (float) (
            $result90[
                'expected_points'
            ]
            ?? 0
        )
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * LOGISTIC CURVE CALIBRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Logistic Curve Calibration<br>";
echo "============================================<br>";


$calibrationPoints = [

    20 =>
        0.077,

    24 =>
        0.187,

    25 =>
        0.232,

    27 =>
        0.362,

    30 =>
        0.641,

    32 =>
        0.912,

    35 =>
        1.400,

    37 =>
        1.749,

    40 =>
        2.214,

    42 =>
        2.450,

    45 =>
        2.696,

    50 =>
        2.899,

    60 =>
        2.990
];


foreach (
    $calibrationPoints
    as $bps => $expected
) {

    expectedBonusCheck(
        'BPS '
        . $bps
        . ' matches calibrated expected bonus',
        abs(
            $model
                ->expectedBonusFromBps(
                    (float) $bps
                )
            -
            $expected
        )
        < 0.03
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * SMOOTH CURVE BEHAVIOUR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Smooth Curve Behaviour<br>";
echo "============================================<br>";


$bonus24 =
    $model
        ->expectedBonusFromBps(
            24
        );


$bonus25 =
    $model
        ->expectedBonusFromBps(
            25
        );


$bonus30 =
    $model
        ->expectedBonusFromBps(
            30
        );


$bonus35 =
    $model
        ->expectedBonusFromBps(
            35
        );


$bonus40 =
    $model
        ->expectedBonusFromBps(
            40
        );


expectedBonusCheck(
    'BPS 25 produces more expected bonus than BPS 24',
    $bonus25
    >
    $bonus24
);


expectedBonusCheck(
    'BPS 30 produces more expected bonus than BPS 25',
    $bonus30
    >
    $bonus25
);


expectedBonusCheck(
    'BPS 35 produces more expected bonus than BPS 30',
    $bonus35
    >
    $bonus30
);


expectedBonusCheck(
    'BPS 40 produces more expected bonus than BPS 35',
    $bonus40
    >
    $bonus35
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * MONOTONICITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Curve Monotonicity<br>";
echo "============================================<br>";


$previousBonus =
    -1.0;


$monotonic =
    true;


for (
    $bps = 0;
    $bps <= 70;
    $bps++
) {

    $bonus =
        $model
            ->expectedBonusFromBps(
                (float) $bps
            );


    if (
        $bonus
        <
        $previousBonus
    ) {

        $monotonic =
            false;

        break;
    }


    $previousBonus =
        $bonus;
}


expectedBonusCheck(
    'Expected Bonus curve never decreases as projected BPS rises',
    $monotonic
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * LOW-BPS BEHAVIOUR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Low-BPS Behaviour<br>";
echo "============================================<br>";


$bonus0 =
    $model
        ->expectedBonusFromBps(
            0
        );


$bonus10 =
    $model
        ->expectedBonusFromBps(
            10
        );


$bonus20 =
    $model
        ->expectedBonusFromBps(
            20
        );


expectedBonusCheck(
    'Very low BPS produces very small expected bonus',
    $bonus0
    <
    0.01
);


expectedBonusCheck(
    'BPS 10 remains below 0.01 expected bonus',
    $bonus10
    <
    0.01
);


expectedBonusCheck(
    'BPS 20 remains below 0.10 expected bonus',
    $bonus20
    <
    0.10
);


expectedBonusCheck(
    'Low-BPS expected bonus remains non-negative',
    $bonus0
    >= 0
    &&
    $bonus10
    >= 0
    &&
    $bonus20
    >= 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * HIGH-BPS BEHAVIOUR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: High-BPS Behaviour<br>";
echo "============================================<br>";


$bonus50 =
    $model
        ->expectedBonusFromBps(
            50
        );


$bonus60 =
    $model
        ->expectedBonusFromBps(
            60
        );


$bonus100 =
    $model
        ->expectedBonusFromBps(
            100
        );


expectedBonusCheck(
    'BPS 50 produces strong expected bonus',
    $bonus50
    >
    2.8
);


expectedBonusCheck(
    'BPS 60 approaches the three-point ceiling',
    $bonus60
    >
    2.95
);


expectedBonusCheck(
    'Extreme BPS remains below or equal to three expected bonus',
    $bonus100
    <= 3
);


expectedBonusCheck(
    'Higher BPS continues to increase expected bonus',
    $bonus60
    >
    $bonus50
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * MISSING BPS HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Missing BPS History<br>";
echo "============================================<br>";


$result =
    $model
        ->calculate(
            'MID',
            90,
            [
                'appearance_sample_size' =>
                    1,

                'weighted_metrics' =>
                    []
            ]
        );


expectedBonusCheck(
    'Missing BPS history is explicitly Insufficient Data',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Insufficient Data'
);


expectedBonusCheck(
    'Missing BPS history produces zero expected bonus',
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
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * INVALID POSITION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Invalid Position<br>";
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

                    'bps_per_90' =>
                        50
                ]
            ]
        );


expectedBonusCheck(
    'Invalid position is explicitly Unsupported Position',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unsupported Position'
);


expectedBonusCheck(
    'Invalid position produces zero expected bonus',
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
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Bounds<br>";
echo "============================================<br>";


$result =
    $model
        ->calculate(
            'DEF',
            999,
            [
                'appearance_sample_size' =>
                    999,

                'weighted_metrics' => [

                    'bps_per_90' =>
                        999
                ]
            ]
        );


expectedBonusCheck(
    'Projected minutes are capped at 90',
    abs(
        (
            (float) (
                $result[
                    'projected_minutes'
                ]
                ?? 0
            )
        )
        -
        90
    )
    < 0.001
);


expectedBonusCheck(
    'Sample confidence is capped at one',
    (
        (float) (
            $result[
                'sample_confidence'
            ]
            ?? 999
        )
    )
    <= 1
);


expectedBonusCheck(
    'Expected Bonus remains between zero and three',
    (
        (float) (
            $result[
                'expected_points'
            ]
            ?? -1
        )
    )
    >= 0
    &&
    (
        (float) (
            $result[
                'expected_points'
            ]
            ?? 999
        )
    )
    <= 3
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO M
 * MODEL CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario M: Model Contract<br>";
echo "============================================<br>";


$result =
    $model
        ->calculate(
            'MID',
            90,
            [
                'appearance_sample_size' =>
                    5,

                'weighted_metrics' => [

                    'bps_per_90' =>
                        35
                ]
            ]
        );


foreach (
    [
        'position',
        'status',
        'bps_per_90',
        'position_baseline',
        'appearance_sample_size',
        'sample_confidence',
        'sample_confidence_percent',
        'regressed_bps_per_90',
        'projected_minutes',
        'projected_bps',
        'expected_points'
    ]
    as $field
) {

    expectedBonusCheck(
        'Expected Bonus exposes '
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
echo "Expected Bonus Test Summary<br>";
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