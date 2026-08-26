<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Multi-Gameweek Fixture Sensitivity Test<br>";
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

function fixtureSensitivityCheck(
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
 * DATABASE / REPOSITORIES
 * ============================================================
 */

$database =
    new Database();


$connection =
    $database
        ->getConnection();


$playerRepository =
    new PlayerRepository(
        $connection
    );


$historyRepository =
    new PlayerFixtureHistoryRepository(
        $connection
    );


/*
 * ============================================================
 * FORM INTELLIGENCE
 * ============================================================
 */

$formHistory =
    new PlayerFormHistory(
        $historyRepository
    );


$playerForm =
    new PlayerForm(
        $formHistory
    );


/*
 * ============================================================
 * EXPECTED POINTS STACK
 * ============================================================
 */

$playerExpectedPoints =
    new PlayerExpectedPoints(
        new ExpectedMinutes(),
        new ExpectedPointsInputs(),
        new ExpectedPoints(),
        new ProjectionConfidence()
    );


/*
 * ============================================================
 * LOAD REAL PLAYERS
 * ============================================================
 */

$players =
    $playerRepository
        ->getAll();


echo "Players Loaded: "
    . count(
        $players
    )
    . "<br><br>";


/*
 * ============================================================
 * SELECT REAL ATTACKING PLAYER
 * ============================================================
 *
 * Prefer a forward because that gives us the cleanest view of
 * attacking fixture sensitivity.
 *
 * If no suitable forward exists, fall back to a midfielder.
 *
 * We rank candidates using current recency-weighted:
 *
 * xG / 90 + xA / 90
 *
 * so the test uses a player whose attacking expectation should
 * visibly respond to fixture opportunity.
 */

$candidates =
    [];


foreach (
    $players
    as $player
) {

    $position =
        strtoupper(
            trim(
                (string) (
                    $player[
                        'position'
                    ]
                    ?? ''
                )
            )
        );


    if (
        !in_array(
            $position,
            [
                'FWD',
                'MID'
            ],
            true
        )
    ) {

        continue;
    }


    $playerId =
        (int) (
            $player[
                'id'
            ]
            ?? 0
        );


    if (
        $playerId <= 0
    ) {

        continue;
    }


    $form =
        $playerForm
            ->buildModel(
                $playerId,
                $position
            );


    $appearanceSampleSize =
        max(
            0,
            (int) (
                $form[
                    'appearance_sample_size'
                ]
                ?? 0
            )
        );


    if (
        $appearanceSampleSize <= 0
    ) {

        continue;
    }


    $weightedMetrics =
        is_array(
            $form[
                'weighted_metrics'
            ]
            ?? null
        )
            ? $form[
                'weighted_metrics'
            ]
            : [];


    $expectedGoalsPer90 =
        is_numeric(
            $weightedMetrics[
                'expected_goals_per_90'
            ]
            ?? null
        )
            ? max(
                0.0,
                (float) $weightedMetrics[
                    'expected_goals_per_90'
                ]
            )
            : 0.0;


    $expectedAssistsPer90 =
        is_numeric(
            $weightedMetrics[
                'expected_assists_per_90'
            ]
            ?? null
        )
            ? max(
                0.0,
                (float) $weightedMetrics[
                    'expected_assists_per_90'
                ]
            )
            : 0.0;


    $attackingEvidence =
        $expectedGoalsPer90
        +
        $expectedAssistsPer90;


    if (
        $attackingEvidence <= 0
    ) {

        continue;
    }


    $candidates[] = [

        'player' =>
            $player,

        'form' =>
            $form,

        'position' =>
            $position,

        'expected_goals_per_90' =>
            $expectedGoalsPer90,

        'expected_assists_per_90' =>
            $expectedAssistsPer90,

        'attacking_evidence' =>
            $attackingEvidence
    ];
}


/*
 * Prefer FWD, then strongest attacking evidence.
 */

usort(
    $candidates,
    function (
        array $a,
        array $b
    ): int {

        $positionPriorityA =
            (
                $a[
                    'position'
                ]
                ?? null
            )
            ===
            'FWD'
                ? 0
                : 1;


        $positionPriorityB =
            (
                $b[
                    'position'
                ]
                ?? null
            )
            ===
            'FWD'
                ? 0
                : 1;


        if (
            $positionPriorityA
            !==
            $positionPriorityB
        ) {

            return
                $positionPriorityA
                <=>
                $positionPriorityB;
        }


        return
            (
                (float) (
                    $b[
                        'attacking_evidence'
                    ]
                    ?? 0
                )
            )
            <=>
            (
                (float) (
                    $a[
                        'attacking_evidence'
                    ]
                    ?? 0
                )
            );
    }
);


$selected =
    $candidates[
        0
    ]
    ?? null;


/*
 * ============================================================
 * SCENARIO A
 * REAL PLAYER RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Real Attacking Player Resolution<br>";
echo "============================================<br>";


fixtureSensitivityCheck(
    'A real attacking player resolves',
    is_array(
        $selected
    )
);


if (
    $selected === null
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";
    exit;
}


$player =
    $selected[
        'player'
    ];


$form =
    $selected[
        'form'
    ];


$position =
    $selected[
        'position'
    ];


$playerName =
    trim(
        (string) (
            $player[
                'web_name'
            ]
            ??
            $player[
                'player_name'
            ]
            ??
            $player[
                'name'
            ]
            ??
            'Unknown'
        )
    );


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Position: "
    . htmlspecialchars(
        $position,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Appearance Sample: "
    . (
        (int) (
            $form[
                'appearance_sample_size'
            ]
            ?? 0
        )
    )
    . "<br>";


echo "Weighted xG / 90: "
    . number_format(
        (float) $selected[
            'expected_goals_per_90'
        ],
        4
    )
    . "<br>";


echo "Weighted xA / 90: "
    . number_format(
        (float) $selected[
            'expected_assists_per_90'
        ],
        4
    )
    . "<br>";


echo "Combined Attacking Evidence: "
    . number_format(
        (float) $selected[
            'attacking_evidence'
        ],
        4
    )
    . "<br><br>";


fixtureSensitivityCheck(
    'Selected player has positive attacking evidence',
    (
        (float) $selected[
            'attacking_evidence'
        ]
    )
    >
    0
);


/*
 * ============================================================
 * CONTROLLED FIXTURE CONTEXTS
 * ============================================================
 *
 * Only fixture context changes.
 *
 * Player data and current Form evidence remain identical.
 */

$contexts = [

    'favourable' => [

        'label' =>
            'Very Favourable',

        'fixture_opportunity' =>
            100.0,

        'opponent_attack_rating' =>
            0.0
    ],

    'neutral' => [

        'label' =>
            'Neutral',

        'fixture_opportunity' =>
            50.0,

        'opponent_attack_rating' =>
            50.0
    ],

    'difficult' => [

        'label' =>
            'Very Difficult',

        'fixture_opportunity' =>
            0.0,

        'opponent_attack_rating' =>
            100.0
    ]
];


$projections =
    [];


foreach (
    $contexts
    as $key => $context
) {

    $projections[
        $key
    ] =
        $playerExpectedPoints
            ->project(
                $player,
                $form,
                [
                    'fixture_opportunity' =>
                        $context[
                            'fixture_opportunity'
                        ],

                    'opponent_attack_rating' =>
                        $context[
                            'opponent_attack_rating'
                        ]
                ]
            );
}


/*
 * ============================================================
 * SCENARIO B
 * PROJECTION CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Controlled Projection Contract<br>";
echo "============================================<br>";


foreach (
    $projections
    as $key => $projection
) {

    $label =
        $contexts[
            $key
        ][
            'label'
        ];


    fixtureSensitivityCheck(
        $label
        . ' projection has numeric Projected Points',
        is_numeric(
            $projection[
                'projected_points'
            ]
            ?? null
        )
    );


    fixtureSensitivityCheck(
        $label
        . ' projection has numeric Projected Minutes',
        is_numeric(
            $projection[
                'projected_minutes'
            ]
            ?? null
        )
    );


    fixtureSensitivityCheck(
        $label
        . ' projection exposes Expected Points inputs',
        is_array(
            $projection[
                'inputs'
            ]
            ?? null
        )
    );


    fixtureSensitivityCheck(
        $label
        . ' projection exposes component breakdown',
        is_array(
            $projection[
                'components'
            ]
            ?? null
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * PROJECTED MINUTES CONTROL
 * ============================================================
 *
 * Projected Minutes must remain identical because fixture
 * context should not alter our current playing-time estimate.
 */

echo "============================================<br>";
echo "Scenario C: Projected Minutes Control<br>";
echo "============================================<br>";


$favourableMinutes =
    (float) (
        $projections[
            'favourable'
        ][
            'projected_minutes'
        ]
        ?? 0
    );


$neutralMinutes =
    (float) (
        $projections[
            'neutral'
        ][
            'projected_minutes'
        ]
        ?? 0
    );


$difficultMinutes =
    (float) (
        $projections[
            'difficult'
        ][
            'projected_minutes'
        ]
        ?? 0
    );


fixtureSensitivityCheck(
    'Fixture context does not change Projected Minutes',
    abs(
        $favourableMinutes
        -
        $neutralMinutes
    )
    <
    0.001
    &&
    abs(
        $neutralMinutes
        -
        $difficultMinutes
    )
    <
    0.001
);


echo "Projected Minutes: "
    . number_format(
        $neutralMinutes,
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * FIXTURE OPPORTUNITY PROPAGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Fixture Opportunity Propagation<br>";
echo "============================================<br>";


$favourableOpportunity =
    $projections[
        'favourable'
    ][
        'inputs'
    ][
        'evidence'
    ][
        'fixture_opportunity'
    ]
    ?? null;


$neutralOpportunity =
    $projections[
        'neutral'
    ][
        'inputs'
    ][
        'evidence'
    ][
        'fixture_opportunity'
    ]
    ?? null;


$difficultOpportunity =
    $projections[
        'difficult'
    ][
        'inputs'
    ][
        'evidence'
    ][
        'fixture_opportunity'
    ]
    ?? null;


fixtureSensitivityCheck(
    'Favourable fixture preserves 100 opportunity',
    is_numeric(
        $favourableOpportunity
    )
    &&
    abs(
        (float) $favourableOpportunity
        -
        100.0
    )
    <
    0.001
);


fixtureSensitivityCheck(
    'Neutral fixture preserves 50 opportunity',
    is_numeric(
        $neutralOpportunity
    )
    &&
    abs(
        (float) $neutralOpportunity
        -
        50.0
    )
    <
    0.001
);


fixtureSensitivityCheck(
    'Difficult fixture preserves zero opportunity',
    is_numeric(
        $difficultOpportunity
    )
    &&
    abs(
        (float) $difficultOpportunity
    )
    <
    0.001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * ATTACKING INPUT SENSITIVITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Attacking Input Sensitivity<br>";
echo "============================================<br>";


$favourableInputs =
    $projections[
        'favourable'
    ][
        'inputs'
    ];


$neutralInputs =
    $projections[
        'neutral'
    ][
        'inputs'
    ];


$difficultInputs =
    $projections[
        'difficult'
    ][
        'inputs'
    ];


$favourableExpectedGoals =
    (float) (
        $favourableInputs[
            'expected_goals'
        ]
        ?? 0
    );


$neutralExpectedGoals =
    (float) (
        $neutralInputs[
            'expected_goals'
        ]
        ?? 0
    );


$difficultExpectedGoals =
    (float) (
        $difficultInputs[
            'expected_goals'
        ]
        ?? 0
    );


$favourableExpectedAssists =
    (float) (
        $favourableInputs[
            'expected_assists'
        ]
        ?? 0
    );


$neutralExpectedAssists =
    (float) (
        $neutralInputs[
            'expected_assists'
        ]
        ?? 0
    );


$difficultExpectedAssists =
    (float) (
        $difficultInputs[
            'expected_assists'
        ]
        ?? 0
    );


fixtureSensitivityCheck(
    'Favourable fixture does not reduce Expected Goals',
    $favourableExpectedGoals
    >=
    $neutralExpectedGoals
);


fixtureSensitivityCheck(
    'Neutral fixture does not reduce Expected Goals versus difficult fixture',
    $neutralExpectedGoals
    >=
    $difficultExpectedGoals
);


fixtureSensitivityCheck(
    'Favourable fixture does not reduce Expected Assists',
    $favourableExpectedAssists
    >=
    $neutralExpectedAssists
);


fixtureSensitivityCheck(
    'Neutral fixture does not reduce Expected Assists versus difficult fixture',
    $neutralExpectedAssists
    >=
    $difficultExpectedAssists
);


fixtureSensitivityCheck(
    'Attacking fixture context changes at least one expected attacking input',
    (
        abs(
            $favourableExpectedGoals
            -
            $difficultExpectedGoals
        )
        >
        0.0001
    )
    ||
    (
        abs(
            $favourableExpectedAssists
            -
            $difficultExpectedAssists
        )
        >
        0.0001
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * COMPONENT SENSITIVITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Component Sensitivity<br>";
echo "============================================<br>";


$favourableComponents =
    $projections[
        'favourable'
    ][
        'components'
    ]
    ?? [];


$neutralComponents =
    $projections[
        'neutral'
    ][
        'components'
    ]
    ?? [];


$difficultComponents =
    $projections[
        'difficult'
    ][
        'components'
    ]
    ?? [];


fixtureSensitivityCheck(
    'Favourable fixture does not reduce goal points',
    (
        (float) (
            $favourableComponents[
                'goals'
            ]
            ?? 0
        )
    )
    >=
    (
        (float) (
            $neutralComponents[
                'goals'
            ]
            ?? 0
        )
    )
);


fixtureSensitivityCheck(
    'Neutral fixture does not reduce goal points versus difficult fixture',
    (
        (float) (
            $neutralComponents[
                'goals'
            ]
            ?? 0
        )
    )
    >=
    (
        (float) (
            $difficultComponents[
                'goals'
            ]
            ?? 0
        )
    )
);


fixtureSensitivityCheck(
    'Favourable fixture does not reduce assist points',
    (
        (float) (
            $favourableComponents[
                'assists'
            ]
            ?? 0
        )
    )
    >=
    (
        (float) (
            $neutralComponents[
                'assists'
            ]
            ?? 0
        )
    )
);


fixtureSensitivityCheck(
    'Neutral fixture does not reduce assist points versus difficult fixture',
    (
        (float) (
            $neutralComponents[
                'assists'
            ]
            ?? 0
        )
    )
    >=
    (
        (float) (
            $difficultComponents[
                'assists'
            ]
            ?? 0
        )
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * FULL FIXTURE SENSITIVITY DIAGNOSTIC
 * ============================================================
 *
 * We deliberately PRINT total Projected Points rather than
 * asserting strict monotonic ordering here.
 *
 * Some scoring components can legitimately move in opposite
 * directions:
 *
 * - stronger opponents reduce attacking expectation
 * - stronger opponents may increase defensive-contribution
 *   opportunity
 * - GK save opportunity can increase
 * - MID clean-sheet expectation can decrease
 *
 * We want to inspect that interaction before deciding whether
 * any additional model adjustment is warranted.
 */

echo "============================================<br>";
echo "Scenario G: Full Fixture Sensitivity Diagnostic<br>";
echo "============================================<br><br>";


foreach (
    [
        'favourable',
        'neutral',
        'difficult'
    ]
    as $key
) {

    $context =
        $contexts[
            $key
        ];


    $projection =
        $projections[
            $key
        ];


    $inputs =
        $projection[
            'inputs'
        ]
        ?? [];


    $evidence =
        $inputs[
            'evidence'
        ]
        ?? [];


    $components =
        $projection[
            'components'
        ]
        ?? [];


    echo "--------------------------------------------<br>";

    echo htmlspecialchars(
        $context[
            'label'
        ],
        ENT_QUOTES,
        'UTF-8'
    )
        . "<br>";

    echo "--------------------------------------------<br>";


    echo "Fixture Opportunity: "
        . number_format(
            (float) (
                $evidence[
                    'fixture_opportunity'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Fixture Multiplier: "
        . number_format(
            (float) (
                $evidence[
                    'fixture_multiplier'
                ]
                ?? 0
            ),
            3
        )
        . "<br>";


    echo "Opponent Attack Rating: "
        . number_format(
            (float) (
                $evidence[
                    'opponent_attack_rating'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Defensive Fixture Multiplier: "
        . number_format(
            (float) (
                $evidence[
                    'defensive_fixture_multiplier'
                ]
                ?? 0
            ),
            3
        )
        . "<br><br>";


    echo "Projected Minutes: "
        . number_format(
            (float) (
                $projection[
                    'projected_minutes'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Expected Goals: "
        . number_format(
            (float) (
                $inputs[
                    'expected_goals'
                ]
                ?? 0
            ),
            4
        )
        . "<br>";


    echo "Expected Assists: "
        . number_format(
            (float) (
                $inputs[
                    'expected_assists'
                ]
                ?? 0
            ),
            4
        )
        . "<br>";


    echo "Clean Sheet Probability: "
        . number_format(
            (float) (
                $inputs[
                    'clean_sheet_probability'
                ]
                ?? 0
            ),
            2
        )
        . "%<br><br>";


    echo "Appearance: "
        . number_format(
            (float) (
                $components[
                    'appearance'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Goals: "
        . number_format(
            (float) (
                $components[
                    'goals'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Assists: "
        . number_format(
            (float) (
                $components[
                    'assists'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Clean Sheet: "
        . number_format(
            (float) (
                $components[
                    'clean_sheet'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Goals Conceded: "
        . number_format(
            (float) (
                $components[
                    'goals_conceded'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Saves: "
        . number_format(
            (float) (
                $components[
                    'saves'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Bonus: "
        . number_format(
            (float) (
                $components[
                    'bonus'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Defensive Contributions: "
        . number_format(
            (float) (
                $components[
                    'defensive_contributions'
                ]
                ?? 0
            ),
            2
        )
        . "<br><br>";


    echo "TOTAL PROJECTED POINTS: "
        . number_format(
            (float) (
                $projection[
                    'projected_points'
                ]
                ?? 0
            ),
            2
        )
        . "<br><br>";
}


/*
 * ============================================================
 * SCENARIO H
 * TOTAL PROJECTION DIFFERENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Total Projection Difference<br>";
echo "============================================<br>";


$favourableProjectedPoints =
    (float) (
        $projections[
            'favourable'
        ][
            'projected_points'
        ]
        ?? 0
    );


$neutralProjectedPoints =
    (float) (
        $projections[
            'neutral'
        ][
            'projected_points'
        ]
        ?? 0
    );


$difficultProjectedPoints =
    (float) (
        $projections[
            'difficult'
        ][
            'projected_points'
        ]
        ?? 0
    );


echo "Very Favourable: "
    . number_format(
        $favourableProjectedPoints,
        2
    )
    . "<br>";


echo "Neutral: "
    . number_format(
        $neutralProjectedPoints,
        2
    )
    . "<br>";


echo "Very Difficult: "
    . number_format(
        $difficultProjectedPoints,
        2
    )
    . "<br>";


echo "Favourable → Difficult Difference: "
    . number_format(
        $favourableProjectedPoints
        -
        $difficultProjectedPoints,
        2
    )
    . "<br><br>";


fixtureSensitivityCheck(
    'Controlled fixture contexts produce at least two distinct total projections',
    count(
        array_unique(
            [
                round(
                    $favourableProjectedPoints,
                    4
                ),
                round(
                    $neutralProjectedPoints,
                    4
                ),
                round(
                    $difficultProjectedPoints,
                    4
                )
            ]
        )
    )
    >=
    2
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Multi-Gameweek Fixture Sensitivity Test Summary<br>";
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