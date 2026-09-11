<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Projection Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function playerProjectionBacktestingTestResult(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


/*
 * ============================================================
 * SERVICE AVAILABILITY
 * ============================================================
 */

playerProjectionBacktestingTestResult(
    class_exists(
        'PlayerProjectionBacktestingService'
    ),
    'PlayerProjectionBacktestingService exists.'
);


if (
    !class_exists(
        'PlayerProjectionBacktestingService'
    )
) {

    echo "<br>";
    echo "============================================<br>";
    echo "TEST SUMMARY<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "RESULT: TESTS FAILED ❌<br>";

    exit;
}


$service =
    new PlayerProjectionBacktestingService();


playerProjectionBacktestingTestResult(
    $service
        instanceof PlayerProjectionBacktestingService,
    'PlayerProjectionBacktestingService can be constructed.'
);


/*
 * ============================================================
 * STANDARD EVIDENCE
 * ============================================================
 */

$standardProjections = [

    [
        'player_id' => 101,
        'fpl_player_id' => 501,
        'name' => 'Player One',
        'position' => 'MID',
        'team_id' => 1,
        'price' => 7.5,
        'intelligence_score' => 72.5,
        'projected_points' => 6.5,
        'projected_minutes' => 90,
        'projection_confidence' => 0.80,
        'projection_confidence_percent' => 80.0,
        'projection_confidence_label' => 'High',
        'projected_points_components' => [],
        'projected_points_inputs' => [],
        'has_projected_points' => true
    ],

    [
        'player_id' => 102,
        'fpl_player_id' => 502,
        'name' => 'Player Two',
        'position' => 'FWD',
        'team_id' => 2,
        'price' => 8.0,
        'intelligence_score' => 65.0,
        'projected_points' => 8.0,
        'projected_minutes' => 75,
        'projection_confidence' => 0.70,
        'projection_confidence_percent' => 70.0,
        'projection_confidence_label' => 'Medium',
        'projected_points_components' => [],
        'projected_points_inputs' => [],
        'has_projected_points' => true
    ]
];


$standardOutcomes = [

    [
        'gameweek_id' => 5,
        'player_id' => 101,
        'fixture_count' => 1,
        'total_points' => 8,
        'minutes' => 90,
        'starts' => 1,
        'goals' => 1,
        'assists' => 0,
        'clean_sheets' => 1,
        'bonus' => 2
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 102,
        'fixture_count' => 1,
        'total_points' => 5,
        'minutes' => 60,
        'starts' => 1,
        'goals' => 0,
        'assists' => 1,
        'clean_sheets' => 0,
        'bonus' => 0
    ]
];


/*
 * ============================================================
 * SCENARIO A
 * EMPTY PROJECTION EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Empty Projection Evidence<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        [],
        $standardOutcomes
    );


playerProjectionBacktestingTestResult(
    $result === [],
    'Empty projection evidence returns no evaluations.'
);


/*
 * ============================================================
 * SCENARIO B
 * EMPTY OUTCOME EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Empty Outcome Evidence<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $standardProjections,
        []
    );


playerProjectionBacktestingTestResult(
    $result === [],
    'Empty outcome evidence returns no evaluations.'
);


/*
 * ============================================================
 * SCENARIO C
 * SINGLE PLAYER UNDER-PROJECTION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Single Player Under-Projection<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        [
            $standardProjections[
                0
            ]
        ],
        [
            $standardOutcomes[
                0
            ]
        ]
    );


$expectedSingleEvaluation = [

    [
        'player_id' => 101,

        'fpl_player_id' => 501,

        'name' => 'Player One',

        'position' => 'MID',

        'projection_confidence' => 0.80,

        'projection_confidence_percent' => 80.0,

        'projection_confidence_label' => 'High',

        'projected_points_components' => [],

        'projected_points_inputs' => [],

        'projected_points' => 6.5,

        'actual_points' => 8,

        'points_error' => 1.5,

        'absolute_points_error' => 1.5,

        'projected_minutes' => 90,

        'actual_minutes' => 90,

        'minutes_error' => 0.0,

        'absolute_minutes_error' => 0.0
    ]
];


playerProjectionBacktestingTestResult(
    $result === $expectedSingleEvaluation,
    'Single player produces the exact projection comparison contract.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'points_error'
    ] === 1.5,
    'Positive points error represents model under-projection.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'absolute_points_error'
    ] === 1.5,
    'Absolute points error preserves error magnitude.'
);


/*
 * ============================================================
 * SCENARIO D
 * PLAYER OVER-PROJECTION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Player Over-Projection<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        [
            $standardProjections[
                1
            ]
        ],
        [
            $standardOutcomes[
                1
            ]
        ]
    );


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'points_error'
    ] === -3.0,
    'Negative points error represents model over-projection.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'absolute_points_error'
    ] === 3.0,
    'Over-projection absolute points error remains positive.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'minutes_error'
    ] === -15.0,
    'Negative minutes error represents projected minutes above actual minutes.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'absolute_minutes_error'
    ] === 15.0,
    'Absolute minutes error preserves minutes error magnitude.'
);


/*
 * ============================================================
 * SCENARIO E
 * MULTIPLE PLAYER MATCHING
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Multiple Player Matching<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $standardProjections,
        $standardOutcomes
    );


playerProjectionBacktestingTestResult(
    count(
        $result
    ) === 2,
    'All matched players are evaluated.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'player_id'
    ] === 101,
    'First evaluation preserves first projection player identity.'
);


playerProjectionBacktestingTestResult(
    $result[
        1
    ][
        'player_id'
    ] === 102,
    'Second evaluation preserves second projection player identity.'
);


/*
 * ============================================================
 * SCENARIO F
 * MATCH BY LOCAL PLAYER ID
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Local Player Identity Matching<br>";
echo "============================================<br>";


$reversedOutcomes = [
    $standardOutcomes[
        1
    ],
    $standardOutcomes[
        0
    ]
];


$result =
    $service->evaluate(
        $standardProjections,
        $reversedOutcomes
    );


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'player_id'
    ] === 101,
    'Projection evaluation matches outcomes by local player ID rather than array position.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'actual_points'
    ] === 8,
    'Correct realised points are matched to first player.'
);


playerProjectionBacktestingTestResult(
    $result[
        1
    ][
        'actual_points'
    ] === 5,
    'Correct realised points are matched to second player.'
);


/*
 * ============================================================
 * SCENARIO G
 * PROJECTION WITHOUT MATCHING OUTCOME
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Missing Player Outcome<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $standardProjections,
        [
            $standardOutcomes[
                0
            ]
        ]
    );


playerProjectionBacktestingTestResult(
    count(
        $result
    ) === 1,
    'Projection without matching actual outcome is not evaluated.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'player_id'
    ] === 101,
    'Matched player remains available when another outcome is missing.'
);


/*
 * ============================================================
 * SCENARIO H
 * OUTCOME WITHOUT MATCHING PROJECTION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Missing Player Projection<br>";
echo "============================================<br>";


$extraOutcome = [
    'gameweek_id' => 5,
    'player_id' => 999,
    'fixture_count' => 1,
    'total_points' => 12,
    'minutes' => 90,
    'starts' => 1,
    'goals' => 2,
    'assists' => 0,
    'clean_sheets' => 0,
    'bonus' => 3
];


$result =
    $service->evaluate(
        [
            $standardProjections[
                0
            ]
        ],
        [
            $standardOutcomes[
                0
            ],
            $extraOutcome
        ]
    );


playerProjectionBacktestingTestResult(
    count(
        $result
    ) === 1,
    'Outcome without preserved projection is not evaluated.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'player_id'
    ] === 101,
    'Only players with both prediction and outcome evidence are compared.'
);


/*
 * ============================================================
 * SCENARIO I
 * ZERO-MINUTE REALISED OUTCOME
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Zero-Minute Realised Outcome<br>";
echo "============================================<br>";


$zeroMinuteOutcome =
    $standardOutcomes[
        0
    ];


$zeroMinuteOutcome[
    'total_points'
] =
    0;


$zeroMinuteOutcome[
    'minutes'
] =
    0;


$result =
    $service->evaluate(
        [
            $standardProjections[
                0
            ]
        ],
        [
            $zeroMinuteOutcome
        ]
    );


playerProjectionBacktestingTestResult(
    count(
        $result
    ) === 1,
    'Zero-minute player remains valid backtesting evidence.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'actual_points'
    ] === 0,
    'Zero realised points are preserved.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'actual_minutes'
    ] === 0,
    'Zero realised minutes are preserved.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'minutes_error'
    ] === -90.0,
    'Zero-minute outcome records full projected-minutes overestimate.'
);


/*
 * ============================================================
 * SCENARIO J
 * NEGATIVE ACTUAL FPL POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Negative Actual Points<br>";
echo "============================================<br>";


$negativeOutcome =
    $standardOutcomes[
        0
    ];


$negativeOutcome[
    'total_points'
] =
    -2;


$result =
    $service->evaluate(
        [
            $standardProjections[
                0
            ]
        ],
        [
            $negativeOutcome
        ]
    );


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'actual_points'
    ] === -2,
    'Negative realised FPL points are preserved.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'points_error'
    ] === -8.5,
    'Negative realised points produce the correct directional error.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'absolute_points_error'
    ] === 8.5,
    'Negative realised points produce the correct absolute error.'
);


/*
 * ============================================================
 * SCENARIO K
 * ZERO PROJECTION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Zero Projection<br>";
echo "============================================<br>";


$zeroProjection =
    $standardProjections[
        0
    ];


$zeroProjection[
    'projected_points'
] =
    0.0;


$zeroProjection[
    'projected_minutes'
] =
    0.0;


$zeroProjection[
    'has_projected_points'
] =
    true;


$result =
    $service->evaluate(
        [
            $zeroProjection
        ],
        [
            $standardOutcomes[
                0
            ]
        ]
    );


playerProjectionBacktestingTestResult(
    count(
        $result
    ) === 1,
    'Explicit zero projection remains valid model evidence.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'points_error'
    ] === 8.0,
    'Zero projected points are compared with realised points.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'minutes_error'
    ] === 90.0,
    'Zero projected minutes are compared with realised minutes.'
);


/*
 * ============================================================
 * SCENARIO L
 * NO PROJECTED POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario L: Projection Not Available<br>";
echo "============================================<br>";


$unavailableProjection =
    $standardProjections[
        0
    ];


$unavailableProjection[
    'projected_points'
] =
    null;


$unavailableProjection[
    'has_projected_points'
] =
    false;


$result =
    $service->evaluate(
        [
            $unavailableProjection
        ],
        [
            $standardOutcomes[
                0
            ]
        ]
    );


playerProjectionBacktestingTestResult(
    $result === [],
    'Player without projected-points evidence is not evaluated.'
);


/*
 * ============================================================
 * SCENARIO M
 * NULL PROJECTED MINUTES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario M: Missing Projected Minutes<br>";
echo "============================================<br>";


$missingMinutesProjection =
    $standardProjections[
        0
    ];


$missingMinutesProjection[
    'projected_minutes'
] =
    null;


$result =
    $service->evaluate(
        [
            $missingMinutesProjection
        ],
        [
            $standardOutcomes[
                0
            ]
        ]
    );


playerProjectionBacktestingTestResult(
    count(
        $result
    ) === 1,
    'Points projection can still be evaluated when projected minutes are unavailable.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'projected_minutes'
    ] === null,
    'Missing projected minutes remain null.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'actual_minutes'
    ] === 90,
    'Actual minutes remain available when projected minutes are missing.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'minutes_error'
    ] === null,
    'Minutes error is not manufactured when projected minutes are unavailable.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'absolute_minutes_error'
    ] === null,
    'Absolute minutes error is not manufactured when projected minutes are unavailable.'
);


/*
 * ============================================================
 * SCENARIO N
 * PRESERVE ZERO-FRACTION NUMERIC CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario N: Numeric Comparison Contract<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        [
            $standardProjections[
                1
            ]
        ],
        [
            $standardOutcomes[
                1
            ]
        ]
    );


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'projected_points'
    ] === 8.0,
    'Projected points remain floating-point model evidence.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'actual_points'
    ] === 5,
    'Actual points remain integer FPL evidence.'
);


playerProjectionBacktestingTestResult(
    $result[
        0
    ][
        'points_error'
    ] === -3.0,
    'Calculated points error uses floating-point comparison.'
);


/*
 * ============================================================
 * SCENARIO O
 * SOURCE EVIDENCE IS NOT MUTATED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario O: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


$projectionEvidence =
    $standardProjections;


$outcomeEvidence =
    $standardOutcomes;


$originalProjectionEvidence =
    $projectionEvidence;


$originalOutcomeEvidence =
    $outcomeEvidence;


$service->evaluate(
    $projectionEvidence,
    $outcomeEvidence
);


playerProjectionBacktestingTestResult(
    $projectionEvidence
        ===
        $originalProjectionEvidence,
    'Historical projection evidence is not mutated.'
);


playerProjectionBacktestingTestResult(
    $outcomeEvidence
        ===
        $originalOutcomeEvidence,
    'Actual outcome evidence is not mutated.'
);


/*
 * ============================================================
 * SCENARIO P
 * NARROW PLAYER-LEVEL RESPONSIBILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario P: Player-Level Evaluation Only<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $standardProjections,
        $standardOutcomes
    );


$firstEvaluation =
    $result[
        0
    ];


playerProjectionBacktestingTestResult(
    !array_key_exists(
        'mean_absolute_error',
        $firstEvaluation
    ),
    'Player evaluator does not calculate aggregate MAE.'
);


playerProjectionBacktestingTestResult(
    !array_key_exists(
        'model_score',
        $firstEvaluation
    ),
    'Player evaluator does not create a synthetic model score.'
);


playerProjectionBacktestingTestResult(
    !array_key_exists(
        'captain_result',
        $firstEvaluation
    ),
    'Player evaluator does not calculate captain recommendation success.'
);


playerProjectionBacktestingTestResult(
    !array_key_exists(
        'transfer_result',
        $firstEvaluation
    ),
    'Player evaluator does not calculate transfer recommendation success.'
);


/*
 * ============================================================
 * SCENARIO Q
 * PRESERVE PROJECTION CALIBRATION EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario Q: Projection Calibration Evidence<br>";
echo "============================================<br>";


$calibrationProjection =
    $standardProjections[
        0
    ];


$calibrationProjection[
    'projected_points_components'
] = [

    'appearance' =>
        2.0,

    'goals' =>
        1.5,

    'assists' =>
        0.9,

    'clean_sheet' =>
        0.6,

    'goals_conceded' =>
        0.0,

    'saves' =>
        0.0,

    'bonus' =>
        0.8,

    'defensive_contributions' =>
        0.7
];


$calibrationProjection[
    'projected_points_inputs'
] = [

    'expected_goals' =>
        0.30,

    'expected_assists' =>
        0.30,

    'clean_sheet_probability' =>
        30.0,

    'expected_saves' =>
        0.0,

    'expected_bonus' =>
        0.8,

    'expected_defensive_contribution_points' =>
        0.7,

    'expected_goals_conceded_points' =>
        0.0
];


$result =
    $service->evaluate(
        [
            $calibrationProjection
        ],
        [
            $standardOutcomes[
                0
            ]
        ]
    );


playerProjectionBacktestingTestResult(
    (
        $result[
            0
        ][
            'position'
        ]
        ?? null
    )
    ===
    'MID',
    'Recommendation-time player position is preserved for projection calibration.'
);


playerProjectionBacktestingTestResult(
    (
        $result[
            0
        ][
            'projection_confidence'
        ]
        ?? null
    )
    ===
    0.80,
    'Recommendation-time Projection Confidence is preserved.'
);


playerProjectionBacktestingTestResult(
    (
        $result[
            0
        ][
            'projection_confidence_percent'
        ]
        ?? null
    )
    ===
    80.0,
    'Recommendation-time Projection Confidence percent is preserved.'
);


playerProjectionBacktestingTestResult(
    (
        $result[
            0
        ][
            'projection_confidence_label'
        ]
        ?? null
    )
    ===
    'High',
    'Recommendation-time Projection Confidence label is preserved.'
);


playerProjectionBacktestingTestResult(
    (
        $result[
            0
        ][
            'projected_points_components'
        ]
        ?? null
    )
    ===
    $calibrationProjection[
        'projected_points_components'
    ],
    'Recommendation-time projected-points component breakdown is preserved unchanged.'
);


playerProjectionBacktestingTestResult(
    (
        $result[
            0
        ][
            'projected_points_inputs'
        ]
        ?? null
    )
    ===
    $calibrationProjection[
        'projected_points_inputs'
    ],
    'Recommendation-time projected-points input evidence is preserved unchanged.'
);


playerProjectionBacktestingTestResult(
    (
        $result[
            0
        ][
            'fpl_player_id'
        ]
        ?? null
    )
    ===
    501,
    'FPL player identity is preserved for projection calibration diagnostics.'
);


playerProjectionBacktestingTestResult(
    (
        $result[
            0
        ][
            'name'
        ]
        ?? null
    )
    ===
    'Player One',
    'Player name is preserved for projection calibration diagnostics.'
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "TEST SUMMARY<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}