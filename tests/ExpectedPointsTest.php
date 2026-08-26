<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Expected Points Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function expectedPointsCheck(
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


$expectedPoints =
    new ExpectedPoints();


/*
 * ============================================================
 * SCENARIO A
 * MODEL STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Model Structure<br>";
echo "============================================<br>";


$model =
    $expectedPoints
        ->calculate(
            'MID',
            [
                'projected_minutes' =>
                    90,

                'expected_goals' =>
                    0.4,

                'expected_assists' =>
                    0.3,

                'clean_sheet_probability' =>
                    40,

                'expected_saves' =>
                    0,

                'expected_bonus' =>
                    0.5,

                'expected_defensive_contribution_points' =>
                    0.2
            ]
        );


expectedPointsCheck(
    'Expected Points returns an array',
    is_array(
        $model
    )
);


expectedPointsCheck(
    'Expected Points preserves position',
    (
        $model[
            'position'
        ]
        ?? null
    )
    ===
    'MID'
);


expectedPointsCheck(
    'Projected Points is numeric',
    is_numeric(
        $model[
            'projected_points'
        ]
        ?? null
    )
);


expectedPointsCheck(
    'Projected Minutes is numeric',
    is_numeric(
        $model[
            'projected_minutes'
        ]
        ?? null
    )
);


expectedPointsCheck(
    'Expected Points exposes components',
    isset(
        $model[
            'components'
        ]
    )
    &&
    is_array(
        $model[
            'components'
        ]
    )
);


expectedPointsCheck(
    'Expected Points exposes normalised inputs',
    isset(
        $model[
            'inputs'
        ]
    )
    &&
    is_array(
        $model[
            'inputs'
        ]
    )
);


echo "Projected Points: "
    . number_format(
        (float) (
            $model[
                'projected_points'
            ]
            ?? 0
        ),
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * OFFICIAL GOAL SCORING BY POSITION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Goal Scoring By Position<br>";
echo "============================================<br>";


$goalPoints = [

    'GK' =>
        10.0,

    'DEF' =>
        6.0,

    'MID' =>
        5.0,

    'FWD' =>
        4.0
];


foreach (
    $goalPoints
    as $position => $expectedGoalPoints
) {

    expectedPointsCheck(
        $position
        . ' goal value matches FPL scoring',
        abs(
            $expectedPoints
                ->goalPointsForPosition(
                    $position
                )
            -
            $expectedGoalPoints
        )
        < 0.001
    );


    $goalModel =
        $expectedPoints
            ->calculate(
                $position,
                [
                    'projected_minutes' =>
                        0,

                    'expected_goals' =>
                        1,

                    'expected_assists' =>
                        0,

                    'clean_sheet_probability' =>
                        0,

                    'expected_saves' =>
                        0,

                    'expected_bonus' =>
                        0,

                    'expected_defensive_contribution_points' =>
                        0
                ]
            );


    expectedPointsCheck(
        $position
        . ' one expected goal contributes correct projected points',
        abs(
            (
                (float) (
                    $goalModel[
                        'components'
                    ][
                        'goals'
                    ]
                    ?? 0
                )
            )
            -
            $expectedGoalPoints
        )
        < 0.001
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * ASSIST SCORING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Assist Scoring<br>";
echo "============================================<br>";


$model =
    $expectedPoints
        ->calculate(
            'MID',
            [
                'projected_minutes' =>
                    0,

                'expected_goals' =>
                    0,

                'expected_assists' =>
                    1,

                'clean_sheet_probability' =>
                    0,

                'expected_saves' =>
                    0,

                'expected_bonus' =>
                    0,

                'expected_defensive_contribution_points' =>
                    0
            ]
        );


expectedPointsCheck(
    'One expected assist contributes three points',
    abs(
        (
            (float) (
                $model[
                    'components'
                ][
                    'assists'
                ]
                ?? 0
            )
        )
        -
        3
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * CLEAN-SHEET SCORING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Clean-Sheet Scoring<br>";
echo "============================================<br>";


foreach (
    [
        'GK' =>
            4.0,

        'DEF' =>
            4.0,

        'MID' =>
            1.0,

        'FWD' =>
            0.0
    ]
    as $position => $cleanSheetValue
) {

    expectedPointsCheck(
        $position
        . ' clean-sheet value matches FPL scoring',
        abs(
            $expectedPoints
                ->cleanSheetPointsForPosition(
                    $position
                )
            -
            $cleanSheetValue
        )
        < 0.001
    );


    $cleanSheetModel =
        $expectedPoints
            ->calculate(
                $position,
                [
                    'projected_minutes' =>
                        90,

                    'expected_goals' =>
                        0,

                    'expected_assists' =>
                        0,

                    'clean_sheet_probability' =>
                        100,

                    'expected_saves' =>
                        0,

                    'expected_bonus' =>
                        0,

                    'expected_defensive_contribution_points' =>
                        0
                ]
            );


    expectedPointsCheck(
        $position
        . ' receives correct full clean-sheet component',
        abs(
            (
                (float) (
                    $cleanSheetModel[
                        'components'
                    ][
                        'clean_sheet'
                    ]
                    ?? 0
                )
            )
            -
            $cleanSheetValue
        )
        < 0.001
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * CLEAN-SHEET MINUTES ELIGIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Clean-Sheet Minutes Eligibility<br>";
echo "============================================<br>";


$model30 =
    $expectedPoints
        ->calculate(
            'DEF',
            [
                'projected_minutes' =>
                    30,

                'clean_sheet_probability' =>
                    100
            ]
        );


$model60 =
    $expectedPoints
        ->calculate(
            'DEF',
            [
                'projected_minutes' =>
                    60,

                'clean_sheet_probability' =>
                    100
            ]
        );


$model90 =
    $expectedPoints
        ->calculate(
            'DEF',
            [
                'projected_minutes' =>
                    90,

                'clean_sheet_probability' =>
                    100
            ]
        );


expectedPointsCheck(
    '30 projected minutes produce partial clean-sheet eligibility',
    abs(
        (
            (float) (
                $model30[
                    'components'
                ][
                    'clean_sheet'
                ]
                ?? 0
            )
        )
        -
        2
    )
    < 0.001
);


expectedPointsCheck(
    '60 projected minutes produce full clean-sheet eligibility',
    abs(
        (
            (float) (
                $model60[
                    'components'
                ][
                    'clean_sheet'
                ]
                ?? 0
            )
        )
        -
        4
    )
    < 0.001
);


expectedPointsCheck(
    '90 projected minutes retain full clean-sheet eligibility',
    abs(
        (
            (float) (
                $model90[
                    'components'
                ][
                    'clean_sheet'
                ]
                ?? 0
            )
        )
        -
        4
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * APPEARANCE POINTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Appearance Points<br>";
echo "============================================<br>";


expectedPointsCheck(
    'Zero minutes produces zero appearance points',
    abs(
        $expectedPoints
            ->calculateAppearancePoints(
                0
            )
    )
    < 0.001
);


expectedPointsCheck(
    '30 projected minutes produces one appearance point',
    abs(
        $expectedPoints
            ->calculateAppearancePoints(
                30
            )
        -
        1
    )
    < 0.001
);


expectedPointsCheck(
    '60 projected minutes produces one appearance point at the threshold',
    abs(
        $expectedPoints
            ->calculateAppearancePoints(
                60
            )
        -
        1
    )
    < 0.001
);


expectedPointsCheck(
    '75 projected minutes produces 1.5 appearance points',
    abs(
        $expectedPoints
            ->calculateAppearancePoints(
                75
            )
        -
        1.5
    )
    < 0.001
);


expectedPointsCheck(
    '90 projected minutes produces two appearance points',
    abs(
        $expectedPoints
            ->calculateAppearancePoints(
                90
            )
        -
        2
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * GOALKEEPER SAVES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Goalkeeper Saves<br>";
echo "============================================<br>";


$model =
    $expectedPoints
        ->calculate(
            'GK',
            [
                'projected_minutes' =>
                    0,

                'expected_saves' =>
                    6
            ]
        );


expectedPointsCheck(
    'Six expected goalkeeper saves contribute two points',
    abs(
        (
            (float) (
                $model[
                    'components'
                ][
                    'saves'
                ]
                ?? 0
            )
        )
        -
        2
    )
    < 0.001
);


$model =
    $expectedPoints
        ->calculate(
            'DEF',
            [
                'projected_minutes' =>
                    0,

                'expected_saves' =>
                    6
            ]
        );


expectedPointsCheck(
    'Outfield players receive no projected goalkeeper save points',
    abs(
        (
            (float) (
                $model[
                    'components'
                ][
                    'saves'
                ]
                ?? 999
            )
        )
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * BONUS POINTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Bonus Points<br>";
echo "============================================<br>";


$model =
    $expectedPoints
        ->calculate(
            'FWD',
            [
                'projected_minutes' =>
                    0,

                'expected_bonus' =>
                    1.75
            ]
        );


expectedPointsCheck(
    'Expected bonus is preserved as continuous projection',
    abs(
        (
            (float) (
                $model[
                    'components'
                ][
                    'bonus'
                ]
                ?? 0
            )
        )
        -
        1.75
    )
    < 0.001
);


$model =
    $expectedPoints
        ->calculate(
            'FWD',
            [
                'projected_minutes' =>
                    0,

                'expected_bonus' =>
                    10
            ]
        );


expectedPointsCheck(
    'Expected bonus cannot exceed three points',
    abs(
        (
            (float) (
                $model[
                    'components'
                ][
                    'bonus'
                ]
                ?? 0
            )
        )
        -
        3
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * DEFENSIVE CONTRIBUTIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Defensive Contributions<br>";
echo "============================================<br>";


foreach (
    [
        'DEF',
        'MID',
        'FWD'
    ]
    as $position
) {

    $model =
        $expectedPoints
            ->calculate(
                $position,
                [
                    'projected_minutes' =>
                        0,

                    'expected_defensive_contribution_points' =>
                        1.4
                ]
            );


    expectedPointsCheck(
        $position
        . ' can receive projected defensive-contribution points',
        abs(
            (
                (float) (
                    $model[
                        'components'
                    ][
                        'defensive_contributions'
                    ]
                    ?? 0
                )
            )
            -
            1.4
        )
        < 0.001
    );
}


$model =
    $expectedPoints
        ->calculate(
            'GK',
            [
                'projected_minutes' =>
                    0,

                'expected_defensive_contribution_points' =>
                    2
            ]
        );


expectedPointsCheck(
    'Goalkeeper receives no defensive-contribution points',
    abs(
        (
            (float) (
                $model[
                    'components'
                ][
                    'defensive_contributions'
                ]
                ?? 999
            )
        )
    )
    < 0.001
);


$model =
    $expectedPoints
        ->calculate(
            'DEF',
            [
                'projected_minutes' =>
                    0,

                'expected_defensive_contribution_points' =>
                    5
            ]
        );


expectedPointsCheck(
    'Defensive-contribution projection is capped at two points',
    abs(
        (
            (float) (
                $model[
                    'components'
                ][
                    'defensive_contributions'
                ]
                ?? 0
            )
        )
        -
        2
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * COMPONENT TOTAL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Component Total<br>";
echo "============================================<br>";


$model =
    $expectedPoints
        ->calculate(
            'MID',
            [
                'projected_minutes' =>
                    90,

                'expected_goals' =>
                    0.5,

                'expected_assists' =>
                    0.25,

                'clean_sheet_probability' =>
                    50,

                'expected_saves' =>
                    0,

                'expected_bonus' =>
                    0.5,

                'expected_defensive_contribution_points' =>
                    0.4
            ]
        );


$componentTotal =
    array_sum(
        $model[
            'components'
        ]
        ?? []
    );


expectedPointsCheck(
    'Projected Points equals sum of projected components',
    abs(
        (
            (float) (
                $model[
                    'projected_points'
                ]
                ?? 0
            )
        )
        -
        $componentTotal
    )
    < 0.011
);


echo "Component Total: "
    . number_format(
        $componentTotal,
        2
    )
    . "<br>";

echo "Projected Points: "
    . number_format(
        (float) (
            $model[
                'projected_points'
            ]
            ?? 0
        ),
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO K
 * ATTACKING EXPECTATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Attacking Expectation<br>";
echo "============================================<br>";


$lowAttack =
    $expectedPoints
        ->calculate(
            'FWD',
            [
                'projected_minutes' =>
                    90,

                'expected_goals' =>
                    0.1,

                'expected_assists' =>
                    0.05
            ]
        );


$highAttack =
    $expectedPoints
        ->calculate(
            'FWD',
            [
                'projected_minutes' =>
                    90,

                'expected_goals' =>
                    0.8,

                'expected_assists' =>
                    0.3
            ]
        );


expectedPointsCheck(
    'Higher attacking expectation produces higher projected points',
    (
        (float) (
            $highAttack[
                'projected_points'
            ]
            ?? 0
        )
    )
    >
    (
        (float) (
            $lowAttack[
                'projected_points'
            ]
            ?? 0
        )
    )
);


echo "Low Attack Projection: "
    . number_format(
        (float) (
            $lowAttack[
                'projected_points'
            ]
            ?? 0
        ),
        2
    )
    . "<br>";

echo "High Attack Projection: "
    . number_format(
        (float) (
            $highAttack[
                'projected_points'
            ]
            ?? 0
        ),
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO L
 * POSITION-AWARE ATTACKING RETURNS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Position-Aware Attack<br>";
echo "============================================<br>";


$defender =
    $expectedPoints
        ->calculate(
            'DEF',
            [
                'projected_minutes' =>
                    0,

                'expected_goals' =>
                    0.5
            ]
        );


$midfielder =
    $expectedPoints
        ->calculate(
            'MID',
            [
                'projected_minutes' =>
                    0,

                'expected_goals' =>
                    0.5
            ]
        );


$forward =
    $expectedPoints
        ->calculate(
            'FWD',
            [
                'projected_minutes' =>
                    0,

                'expected_goals' =>
                    0.5
            ]
        );


expectedPointsCheck(
    'Same goal expectation is worth more to defender than midfielder',
    (
        (float) (
            $defender[
                'components'
            ][
                'goals'
            ]
            ?? 0
        )
    )
    >
    (
        (float) (
            $midfielder[
                'components'
            ][
                'goals'
            ]
            ?? 0
        )
    )
);


expectedPointsCheck(
    'Same goal expectation is worth more to midfielder than forward',
    (
        (float) (
            $midfielder[
                'components'
            ][
                'goals'
            ]
            ?? 0
        )
    )
    >
    (
        (float) (
            $forward[
                'components'
            ][
                'goals'
            ]
            ?? 0
        )
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO M
 * INPUT BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario M: Input Bounds<br>";
echo "============================================<br>";


$model =
    $expectedPoints
        ->calculate(
            'GK',
            [
                'projected_minutes' =>
                    500,

                'expected_goals' =>
                    -5,

                'expected_assists' =>
                    -2,

                'clean_sheet_probability' =>
                    500,

                'expected_saves' =>
                    -10,

                'expected_bonus' =>
                    20,

                'expected_defensive_contribution_points' =>
                    20
            ]
        );


expectedPointsCheck(
    'Projected Minutes cannot exceed 90',
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


expectedPointsCheck(
    'Negative expected goals become zero',
    abs(
        (
            (float) (
                $model[
                    'inputs'
                ][
                    'expected_goals'
                ]
                ?? 999
            )
        )
    )
    < 0.001
);


expectedPointsCheck(
    'Negative expected assists become zero',
    abs(
        (
            (float) (
                $model[
                    'inputs'
                ][
                    'expected_assists'
                ]
                ?? 999
            )
        )
    )
    < 0.001
);


expectedPointsCheck(
    'Clean-sheet probability is capped at 100',
    abs(
        (
            (float) (
                $model[
                    'inputs'
                ][
                    'clean_sheet_probability'
                ]
                ?? 0
            )
        )
        -
        100
    )
    < 0.001
);


expectedPointsCheck(
    'Negative expected saves become zero',
    abs(
        (
            (float) (
                $model[
                    'inputs'
                ][
                    'expected_saves'
                ]
                ?? 999
            )
        )
    )
    < 0.001
);


expectedPointsCheck(
    'Bonus input is capped at three',
    abs(
        (
            (float) (
                $model[
                    'inputs'
                ][
                    'expected_bonus'
                ]
                ?? 0
            )
        )
        -
        3
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO N
 * INVALID POSITION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario N: Invalid Position<br>";
echo "============================================<br>";


$model =
    $expectedPoints
        ->calculate(
            'INVALID',
            [
                'projected_minutes' =>
                    90,

                'expected_goals' =>
                    1
            ]
        );


expectedPointsCheck(
    'Invalid position has unavailable Projected Points',
    array_key_exists(
        'projected_points',
        $model
    )
    &&
    $model[
        'projected_points'
    ]
    === null
);


expectedPointsCheck(
    'Invalid position preserves controlled model output',
    isset(
        $model[
            'components'
        ]
    )
    &&
    is_array(
        $model[
            'components'
        ]
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO O
 * COMPLETE COMPONENT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario O: Complete Component Contract<br>";
echo "============================================<br>";


$model =
    $expectedPoints
        ->calculate(
            'DEF',
            [
                'projected_minutes' =>
                    90
            ]
        );


foreach (
    [
        'appearance',
        'goals',
        'assists',
        'clean_sheet',
        'saves',
        'bonus',
        'defensive_contributions'
    ]
    as $component
) {

    expectedPointsCheck(
        'Expected Points exposes component '
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
echo "Expected Points Test Summary<br>";
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