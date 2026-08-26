<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Expected Points Defensive Fixture Context Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function defensiveFixtureContextCheck(
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


/*
 * ============================================================
 * MODEL
 * ============================================================
 */

$model =
    new ExpectedPointsInputs();


/*
 * ============================================================
 * CONTROLLED PLAYER EVIDENCE
 * ============================================================
 *
 * Both projections use exactly the same goalkeeper evidence.
 *
 * The only fixture-context difference is fixture opportunity.
 *
 * Opponent Attack Rating deliberately remains identical so we
 * can prove whether the richer fixture context contributes
 * anything independently to defensive Expected Points.
 */

$position =
    'GK';


$expectedMinutes = [

    'projected_minutes' =>
        90.0
];


$form = [

    'fixture_sample_size' =>
        5,

    'appearance_sample_size' =>
        5,

    'weighted_metrics' => [

        'clean_sheet_rate' =>
            30.0,

        'saves_per_90' =>
            3.0,

        /*
         * Controlled xGC evidence so the dedicated
         * ExpectedGoalsConceded model is genuinely active.
         */
        'expected_goals_conceded_per_90' =>
            1.50,

        'expected_goals_per_90' =>
            0.0,

        'expected_assists_per_90' =>
            0.0
    ]
];


/*
 * Same opponent attack.
 *
 * Only fixture opportunity changes.
 */

$favourableFixture = [

    'fixture_opportunity' =>
        90.0,

    'opponent_attack_rating' =>
        66.67
];


$difficultFixture = [

    'fixture_opportunity' =>
        10.0,

    'opponent_attack_rating' =>
        66.67
];


$favourable =
    $model->build(
        $position,
        $expectedMinutes,
        $form,
        $favourableFixture
    );


$difficult =
    $model->build(
        $position,
        $expectedMinutes,
        $form,
        $difficultFixture
    );


/*
 * ============================================================
 * SCENARIO A
 * CONTROLLED FIXTURE CONTEXT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Controlled Fixture Context<br>";
echo "============================================<br>";


defensiveFixtureContextCheck(
    'Favourable fixture opportunity is preserved',
    (
        (float) (
            $favourable[
                'evidence'
            ][
                'fixture_opportunity'
            ]
            ?? -1
        )
    )
    ===
    90.0
);


defensiveFixtureContextCheck(
    'Difficult fixture opportunity is preserved',
    (
        (float) (
            $difficult[
                'evidence'
            ][
                'fixture_opportunity'
            ]
            ?? -1
        )
    )
    ===
    10.0
);


defensiveFixtureContextCheck(
    'Opponent Attack Rating is identical in both fixtures',
    (
        (float) (
            $favourable[
                'evidence'
            ][
                'opponent_attack_rating'
            ]
            ?? -1
        )
    )
    ===
    (
        (float) (
            $difficult[
                'evidence'
            ][
                'opponent_attack_rating'
            ]
            ?? -2
        )
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * CURRENT ATTACKING FIXTURE MULTIPLIER
 * ============================================================
 *
 * This proves fixture opportunity already reaches the model
 * and produces different general fixture multipliers.
 */

echo "============================================<br>";
echo "Scenario B: Existing Fixture Sensitivity<br>";
echo "============================================<br>";


$favourableFixtureMultiplier =
    (float) (
        $favourable[
            'evidence'
        ][
            'fixture_multiplier'
        ]
        ?? 0
    );


$difficultFixtureMultiplier =
    (float) (
        $difficult[
            'evidence'
        ][
            'fixture_multiplier'
        ]
        ?? 0
    );


defensiveFixtureContextCheck(
    'Favourable fixture has the larger fixture multiplier',
    $favourableFixtureMultiplier
    >
    $difficultFixtureMultiplier
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * CLEAN-SHEET FIXTURE SENSITIVITY
 * ============================================================
 *
 * Desired contract:
 *
 * With player evidence and opponent Attack Rating held equal,
 * substantially better fixture opportunity should improve the
 * defensive clean-sheet expectation.
 *
 * This is expected to FAIL before the model is improved.
 */

echo "============================================<br>";
echo "Scenario C: Clean-Sheet Fixture Sensitivity<br>";
echo "============================================<br>";


$favourableCleanSheetProbability =
    (float) (
        $favourable[
            'clean_sheet_probability'
        ]
        ?? 0
    );


$difficultCleanSheetProbability =
    (float) (
        $difficult[
            'clean_sheet_probability'
        ]
        ?? 0
    );


defensiveFixtureContextCheck(
    'Favourable fixture produces higher clean-sheet probability',
    $favourableCleanSheetProbability
    >
    $difficultCleanSheetProbability
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * GOALS-CONCEDED FIXTURE SENSITIVITY
 * ============================================================
 *
 * FPL goals-conceded expectation is zero or negative.
 *
 * Therefore a favourable defensive fixture should produce a
 * LESS negative expected deduction than a difficult fixture.
 *
 * This is also expected to FAIL before the model is improved.
 */

echo "============================================<br>";
echo "Scenario D: Goals-Conceded Fixture Sensitivity<br>";
echo "============================================<br>";


$favourableGoalsConcededPoints =
    (float) (
        $favourable[
            'expected_goals_conceded_points'
        ]
        ?? 0
    );


$difficultGoalsConcededPoints =
    (float) (
        $difficult[
            'expected_goals_conceded_points'
        ]
        ?? 0
    );


defensiveFixtureContextCheck(
    'Favourable fixture produces a smaller goals-conceded deduction',
    $favourableGoalsConcededPoints
    >
    $difficultGoalsConcededPoints
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * SAVE MODEL INDEPENDENCE
 * ============================================================
 *
 * Saves should continue to respond to opponent attacking
 * strength rather than general fixture opportunity.
 *
 * Because opponent Attack Rating and player save evidence are
 * identical, expected saves should remain identical.
 */

echo "============================================<br>";
echo "Scenario E: Save Model Independence<br>";
echo "============================================<br>";


$favourableExpectedSaves =
    (float) (
        $favourable[
            'expected_saves'
        ]
        ?? 0
    );


$difficultExpectedSaves =
    (float) (
        $difficult[
            'expected_saves'
        ]
        ?? 0
    );


defensiveFixtureContextCheck(
    'Equal opponent Attack Rating preserves equal expected saves',
    abs(
        $favourableExpectedSaves
        -
        $difficultExpectedSaves
    )
    <
    0.0001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * DEFENSIVE FIXTURE DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Defensive Fixture Diagnostic<br>";
echo "============================================<br><br>";


echo "FAVOURABLE FIXTURE<br>";
echo "Fixture Opportunity: "
    . number_format(
        90.0,
        2
    )
    . "<br>";

echo "Opponent Attack Rating: "
    . number_format(
        66.67,
        2
    )
    . "<br>";

echo "Fixture Multiplier: "
    . number_format(
        $favourableFixtureMultiplier,
        4
    )
    . "<br>";

echo "Defensive Fixture Multiplier: "
    . number_format(
        (float) (
            $favourable[
                'evidence'
            ][
                'defensive_fixture_multiplier'
            ]
            ?? 0
        ),
        4
    )
    . "<br>";

echo "Clean Sheet Probability: "
    . number_format(
        $favourableCleanSheetProbability,
        2
    )
    . "%<br>";

echo "Expected Goals-Conceded Points: "
    . number_format(
        $favourableGoalsConcededPoints,
        4
    )
    . "<br>";

echo "Expected Saves: "
    . number_format(
        $favourableExpectedSaves,
        4
    )
    . "<br><br>";


echo "DIFFICULT FIXTURE<br>";
echo "Fixture Opportunity: "
    . number_format(
        10.0,
        2
    )
    . "<br>";

echo "Opponent Attack Rating: "
    . number_format(
        66.67,
        2
    )
    . "<br>";

echo "Fixture Multiplier: "
    . number_format(
        $difficultFixtureMultiplier,
        4
    )
    . "<br>";

echo "Defensive Fixture Multiplier: "
    . number_format(
        (float) (
            $difficult[
                'evidence'
            ][
                'defensive_fixture_multiplier'
            ]
            ?? 0
        ),
        4
    )
    . "<br>";

echo "Clean Sheet Probability: "
    . number_format(
        $difficultCleanSheetProbability,
        2
    )
    . "%<br>";

echo "Expected Goals-Conceded Points: "
    . number_format(
        $difficultGoalsConcededPoints,
        4
    )
    . "<br>";

echo "Expected Saves: "
    . number_format(
        $difficultExpectedSaves,
        4
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Expected Points Defensive Fixture Context Test Summary<br>";
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