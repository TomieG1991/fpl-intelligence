<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Expected Minutes Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function expectedMinutesCheck(
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


$expectedMinutes =
    new ExpectedMinutes();


/*
 * ============================================================
 * SCENARIO A
 * NAILED 90-MINUTE STARTER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Nailed 90-Minute Starter<br>";
echo "============================================<br>";


$model =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'a',

                'chance_of_playing' =>
                    null
            ],
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    100,

                'raw_metrics' => [

                    'average_appearance_minutes' =>
                        90
                ]
            ]
        );


expectedMinutesCheck(
    'Projected Minutes are 90 for a fully available 90-minute regular',
    abs(
        (
            (float) (
                $model[
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


expectedMinutesCheck(
    'Base Minutes are 90',
    abs(
        (
            (float) (
                $model[
                    'base_minutes'
                ]
                ?? 0
            )
        )
        -
        90
    )
    < 0.001
);


expectedMinutesCheck(
    'Fully available player resolves to 100 percent chance of playing',
    abs(
        (
            (float) (
                $model[
                    'chance_of_playing'
                ]
                ?? 0
            )
        )
        -
        100
    )
    < 0.001
);


expectedMinutesCheck(
    'Historical starter uses Recent History evidence',
    (
        $model[
            'evidence_source'
        ]
        ?? null
    )
    ===
    'Recent History'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * REGULAR 60-MINUTE STARTER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Regular 60-Minute Starter<br>";
echo "============================================<br>";


$model =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'a'
            ],
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    100,

                'raw_metrics' => [

                    'average_appearance_minutes' =>
                        60
                ]
            ]
        );


expectedMinutesCheck(
    'Regular 60-minute starter projects 60 minutes',
    abs(
        (
            (float) (
                $model[
                    'projected_minutes'
                ]
                ?? 0
            )
        )
        -
        60
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * ROTATION PLAYER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Rotation Player<br>";
echo "============================================<br>";


$model =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'a'
            ],
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    4,

                'participation_rate' =>
                    80,

                'raw_metrics' => [

                    'average_appearance_minutes' =>
                        90
                ]
            ]
        );


expectedMinutesCheck(
    '90-minute player with 80 percent participation projects 72 minutes',
    abs(
        (
            (float) (
                $model[
                    'projected_minutes'
                ]
                ?? 0
            )
        )
        -
        72
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * REGULAR SUBSTITUTE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Regular Substitute<br>";
echo "============================================<br>";


$model =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'a'
            ],
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    3,

                'participation_rate' =>
                    60,

                'raw_metrics' => [

                    'average_appearance_minutes' =>
                        30
                ]
            ]
        );


expectedMinutesCheck(
    '30-minute substitute with 60 percent participation projects 18 minutes',
    abs(
        (
            (float) (
                $model[
                    'projected_minutes'
                ]
                ?? 0
            )
        )
        -
        18
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * ZERO PARTICIPATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Zero Participation<br>";
echo "============================================<br>";


$model =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'a'
            ],
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    1,

                'participation_rate' =>
                    0,

                'raw_metrics' => [

                    'average_appearance_minutes' =>
                        90
                ]
            ]
        );


expectedMinutesCheck(
    'Zero recent participation produces zero projected minutes',
    abs(
        (
            (float) (
                $model[
                    'projected_minutes'
                ]
                ?? -1
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
 * SCENARIO F
 * AVAILABILITY ADJUSTMENT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Availability Adjustment<br>";
echo "============================================<br>";


$baseForm = [

    'fixture_sample_size' =>
        5,

    'appearance_sample_size' =>
        5,

    'participation_rate' =>
        100,

    'raw_metrics' => [

        'average_appearance_minutes' =>
            80
    ]
];


$model75 =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'd',

                'chance_of_playing' =>
                    75
            ],
            $baseForm
        );


$model50 =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'd',

                'chance_of_playing' =>
                    50
            ],
            $baseForm
        );


$model25 =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'd',

                'chance_of_playing' =>
                    25
            ],
            $baseForm
        );


expectedMinutesCheck(
    '75 percent chance reduces 80 base minutes to 60',
    abs(
        (
            (float) (
                $model75[
                    'projected_minutes'
                ]
                ?? 0
            )
        )
        -
        60
    )
    < 0.001
);


expectedMinutesCheck(
    '50 percent chance reduces 80 base minutes to 40',
    abs(
        (
            (float) (
                $model50[
                    'projected_minutes'
                ]
                ?? 0
            )
        )
        -
        40
    )
    < 0.001
);


expectedMinutesCheck(
    '25 percent chance reduces 80 base minutes to 20',
    abs(
        (
            (float) (
                $model25[
                    'projected_minutes'
                ]
                ?? 0
            )
        )
        -
        20
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * UNAVAILABLE PLAYER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Unavailable Player<br>";
echo "============================================<br>";


foreach (
    [
        'i',
        's',
        'u'
    ]
    as $status
) {

    $model =
        $expectedMinutes
            ->calculate(
                [
                    'status' =>
                        $status,

                    'chance_of_playing' =>
                        null
                ],
                $baseForm
            );


    expectedMinutesCheck(
        'Unavailable status '
        . $status
        . ' projects zero minutes',
        abs(
            (
                (float) (
                    $model[
                        'projected_minutes'
                    ]
                    ?? -1
                )
            )
            -
            0
        )
        < 0.001
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * NO HISTORICAL EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: No Historical Evidence<br>";
echo "============================================<br>";


$model =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'a'
            ],
            []
        );


expectedMinutesCheck(
    'No historical evidence uses 60-minute fallback',
    abs(
        (
            (float) (
                $model[
                    'projected_minutes'
                ]
                ?? 0
            )
        )
        -
        60
    )
    < 0.001
);


expectedMinutesCheck(
    'No historical evidence is labelled as Fallback',
    (
        $model[
            'evidence_source'
        ]
        ?? null
    )
    ===
    'Fallback'
);


expectedMinutesCheck(
    'Fallback has zero fixture sample',
    (
        (int) (
            $model[
                'fixture_sample_size'
            ]
            ?? -1
        )
    )
    === 0
);


expectedMinutesCheck(
    'Fallback has zero appearance sample',
    (
        (int) (
            $model[
                'appearance_sample_size'
            ]
            ?? -1
        )
    )
    === 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * FORM RATING MUST NOT DRIVE MINUTES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Form Rating Separation<br>";
echo "============================================<br>";


$model =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'a'
            ],
            [
                'form_rating' =>
                    100,

                'performance_rating' =>
                    100,

                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    1,

                'participation_rate' =>
                    0,

                'raw_metrics' => [

                    'average_appearance_minutes' =>
                        90
                ]
            ]
        );


expectedMinutesCheck(
    'Perfect Form Rating cannot override zero participation',
    abs(
        (
            (float) (
                $model[
                    'projected_minutes'
                ]
                ?? -1
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
 * SCENARIO J
 * VALUE BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Bounds<br>";
echo "============================================<br>";


$extremeHigh =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'a',

                'chance_of_playing' =>
                    150
            ],
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    150,

                'raw_metrics' => [

                    'average_appearance_minutes' =>
                        150
                ]
            ]
        );


$extremeLow =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'a',

                'chance_of_playing' =>
                    -50
            ],
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    -100,

                'raw_metrics' => [

                    'average_appearance_minutes' =>
                        -30
                ]
            ]
        );


expectedMinutesCheck(
    'Extreme high inputs cannot exceed 90 projected minutes',
    (
        (float) (
            $extremeHigh[
                'projected_minutes'
            ]
            ?? 999
        )
    )
    <= 90
);


expectedMinutesCheck(
    'Extreme low inputs cannot fall below zero projected minutes',
    (
        (float) (
            $extremeLow[
                'projected_minutes'
            ]
            ?? -999
        )
    )
    >= 0
);


expectedMinutesCheck(
    'Chance of playing is bounded at 100',
    abs(
        (
            (float) (
                $extremeHigh[
                    'chance_of_playing'
                ]
                ?? 0
            )
        )
        -
        100
    )
    < 0.001
);


expectedMinutesCheck(
    'Chance of playing is bounded at zero',
    abs(
        (
            (float) (
                $extremeLow[
                    'chance_of_playing'
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
 * SCENARIO K
 * MODEL CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Model Contract<br>";
echo "============================================<br>";


$model =
    $expectedMinutes
        ->calculate(
            [
                'status' =>
                    'a'
            ],
            [
                'fixture_sample_size' =>
                    5,

                'appearance_sample_size' =>
                    5,

                'participation_rate' =>
                    100,

                'raw_metrics' => [

                    'average_appearance_minutes' =>
                        90
                ]
            ]
        );


foreach (
    [
        'projected_minutes',
        'base_minutes',
        'chance_of_playing',
        'participation_rate',
        'average_appearance_minutes',
        'fixture_sample_size',
        'appearance_sample_size',
        'evidence_source'
    ]
    as $field
) {

    expectedMinutesCheck(
        'Expected Minutes exposes '
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
echo "Expected Minutes Test Summary<br>";
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