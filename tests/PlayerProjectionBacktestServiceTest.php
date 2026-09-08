<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * PLAYER PROJECTION BACKTEST SERVICE TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * This service compares immutable historical player projection
 * evidence with realised completed-gameweek outcomes.
 *
 * It must not:
 *
 * - recalculate Expected Points
 * - recalculate Expected Minutes
 * - recalculate Player Intelligence
 * - reconstruct historical recommendation evidence
 * - manufacture realised outcomes where none exist
 * - tune or calibrate the model
 *
 * It measures the model evidence that was actually preserved.
 */


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed =
    0;


$failed =
    0;


function playerProjectionBacktestAssert(
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


function playerProjectionBacktestSection(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * A. SERVICE EXISTS
 * ============================================================
 */

playerProjectionBacktestSection(
    'A. Service Contract'
);


if (
    !class_exists(
        'PlayerProjectionBacktestService'
    )
) {

    playerProjectionBacktestAssert(
        false,
        'PlayerProjectionBacktestService exists.'
    );


    echo "<br>";
    echo "============================================<br>";
    echo "Player Projection Backtest Service Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "RESULT: TESTS FAILED ❌";

    exit;
}


playerProjectionBacktestAssert(
    true,
    'PlayerProjectionBacktestService exists.'
);


$service =
    new PlayerProjectionBacktestService();


/*
 * ============================================================
 * B. INVALID GAMEWEEK ID
 * ============================================================
 */

playerProjectionBacktestSection(
    'B. Invalid Gameweek Identity'
);


$invalidGameweekRejected =
    false;


try {

    $service->evaluate(
        0,
        [],
        []
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidGameweekRejected =
        true;
}


playerProjectionBacktestAssert(
    $invalidGameweekRejected,
    'Non-positive gameweek ID is rejected.'
);


/*
 * ============================================================
 * C. EMPTY PROJECTION EVIDENCE
 * ============================================================
 */

playerProjectionBacktestSection(
    'C. Empty Projection Evidence'
);


$emptyProjectionResult =
    $service->evaluate(
        5,
        [],
        []
    );


playerProjectionBacktestAssert(
    $emptyProjectionResult === [],
    'No historical player projections produce no backtest rows.'
);


/*
 * ============================================================
 * D. SINGLE PLAYER POINTS COMPARISON
 * ============================================================
 */

playerProjectionBacktestSection(
    'D. Single Player Points Comparison'
);


$singleProjection = [

    [
        'player_id' =>
            101,

        'fpl_player_id' =>
            1001,

        'name' =>
            'Player One',

        'position' =>
            'MID',

        'team_id' =>
            1,

        'price' =>
            8.0,

        'intelligence_score' =>
            75.0,

        'projected_points' =>
            6.5,

        'projected_minutes' =>
            90.0,

        'projection_confidence' =>
            0.80,

        'projection_confidence_percent' =>
            80.0,

        'projection_confidence_label' =>
            'High',

        'projected_points_components' =>
            [],

        'projected_points_inputs' =>
            [],

        'has_projected_points' =>
            true
    ]
];


$singleOutcome = [

    [
        'gameweek_id' =>
            5,

        'player_id' =>
            101,

        'fixture_count' =>
            1,

        'total_points' =>
            9,

        'minutes' =>
            90,

        'starts' =>
            1,

        'goals' =>
            1,

        'assists' =>
            0,

        'clean_sheets' =>
            1,

        'bonus' =>
            2
    ]
];


$singleResult =
    $service->evaluate(
        5,
        $singleProjection,
        $singleOutcome
    );


playerProjectionBacktestAssert(
    count(
        $singleResult
    )
    ===
    1,
    'One matched player produces one backtest row.'
);


playerProjectionBacktestAssert(
    $singleResult[0][
        'gameweek_id'
    ]
    ===
    5,
    'Backtest row preserves local gameweek ID.'
);


playerProjectionBacktestAssert(
    $singleResult[0][
        'player_id'
    ]
    ===
    101,
    'Backtest row preserves local player ID.'
);


playerProjectionBacktestAssert(
    $singleResult[0][
        'fpl_player_id'
    ]
    ===
    1001,
    'Backtest row preserves official FPL player ID.'
);


playerProjectionBacktestAssert(
    $singleResult[0][
        'projected_points'
    ]
    ===
    6.5,
    'Historical projected points are preserved unchanged.'
);


playerProjectionBacktestAssert(
    $singleResult[0][
        'actual_points'
    ]
    ===
    9,
    'Realised FPL points are preserved unchanged.'
);


playerProjectionBacktestAssert(
    $singleResult[0][
        'points_error'
    ]
    ===
    2.5,
    'Signed points error is actual points minus projected points.'
);


playerProjectionBacktestAssert(
    $singleResult[0][
        'absolute_points_error'
    ]
    ===
    2.5,
    'Absolute points error is preserved.'
);


/*
 * ============================================================
 * E. OVER-PROJECTION
 * ============================================================
 */

playerProjectionBacktestSection(
    'E. Over-Projection'
);


$overProjection =
    $singleProjection;


$overProjection[0][
    'projected_points'
] =
    8.0;


$overOutcome =
    $singleOutcome;


$overOutcome[0][
    'total_points'
] =
    3;


$overResult =
    $service->evaluate(
        5,
        $overProjection,
        $overOutcome
    );


playerProjectionBacktestAssert(
    $overResult[0][
        'points_error'
    ]
    ===
    -5.0,
    'Over-projection produces a negative signed points error.'
);


playerProjectionBacktestAssert(
    $overResult[0][
        'absolute_points_error'
    ]
    ===
    5.0,
    'Over-projection absolute error remains positive.'
);


/*
 * ============================================================
 * F. EXACT PROJECTION
 * ============================================================
 */

playerProjectionBacktestSection(
    'F. Exact Projection'
);


$exactProjection =
    $singleProjection;


$exactProjection[0][
    'projected_points'
] =
    7.0;


$exactOutcome =
    $singleOutcome;


$exactOutcome[0][
    'total_points'
] =
    7;


$exactResult =
    $service->evaluate(
        5,
        $exactProjection,
        $exactOutcome
    );


playerProjectionBacktestAssert(
    $exactResult[0][
        'points_error'
    ]
    ===
    0.0,
    'Exact projection has zero signed error.'
);


playerProjectionBacktestAssert(
    $exactResult[0][
        'absolute_points_error'
    ]
    ===
    0.0,
    'Exact projection has zero absolute error.'
);


/*
 * ============================================================
 * G. NEGATIVE ACTUAL POINTS
 * ============================================================
 */

playerProjectionBacktestSection(
    'G. Negative Actual Points'
);


$negativeOutcome =
    $singleOutcome;


$negativeOutcome[0][
    'total_points'
] =
    -2;


$negativeResult =
    $service->evaluate(
        5,
        $singleProjection,
        $negativeOutcome
    );


playerProjectionBacktestAssert(
    $negativeResult[0][
        'actual_points'
    ]
    ===
    -2,
    'Negative realised FPL points remain negative.'
);


playerProjectionBacktestAssert(
    $negativeResult[0][
        'points_error'
    ]
    ===
    -8.5,
    'Negative realised points participate in signed error normally.'
);


playerProjectionBacktestAssert(
    $negativeResult[0][
        'absolute_points_error'
    ]
    ===
    8.5,
    'Negative realised points produce the correct absolute error.'
);


/*
 * ============================================================
 * H. UNAVAILABLE HISTORICAL PROJECTION
 * ============================================================
 *
 * The fact that no projection was available is itself historical
 * evidence, but there is no numerical projection to grade.
 *
 * Therefore the player remains in the backtest evidence with
 * null error values.
 * ============================================================
 */

playerProjectionBacktestSection(
    'H. Unavailable Historical Projection'
);


$unavailableProjection =
    $singleProjection;


$unavailableProjection[0][
    'projected_points'
] =
    null;


$unavailableProjection[0][
    'has_projected_points'
] =
    false;


$unavailableResult =
    $service->evaluate(
        5,
        $unavailableProjection,
        $singleOutcome
    );


playerProjectionBacktestAssert(
    count(
        $unavailableResult
    )
    ===
    1,
    'Player without historical projected points remains in backtest evidence.'
);


playerProjectionBacktestAssert(
    $unavailableResult[0][
        'projected_points'
    ]
    ===
    null,
    'Unavailable historical projected points remain null.'
);


playerProjectionBacktestAssert(
    $unavailableResult[0][
        'actual_points'
    ]
    ===
    9,
    'Realised points remain available when historical projection was unavailable.'
);


playerProjectionBacktestAssert(
    $unavailableResult[0][
        'points_error'
    ]
    ===
    null,
    'Signed error is null when no numerical projection existed.'
);


playerProjectionBacktestAssert(
    $unavailableResult[0][
        'absolute_points_error'
    ]
    ===
    null,
    'Absolute error is null when no numerical projection existed.'
);


/*
 * ============================================================
 * I. MISSING ACTUAL OUTCOME
 * ============================================================
 *
 * Absence of fixture-history evidence must not be interpreted
 * as zero FPL points.
 * ============================================================
 */

playerProjectionBacktestSection(
    'I. Missing Actual Outcome'
);


$missingOutcomeResult =
    $service->evaluate(
        5,
        $singleProjection,
        []
    );


playerProjectionBacktestAssert(
    count(
        $missingOutcomeResult
    )
    ===
    1,
    'Historical projection remains present when actual outcome evidence is missing.'
);


playerProjectionBacktestAssert(
    $missingOutcomeResult[0][
        'actual_points'
    ]
    ===
    null,
    'Missing actual outcome is not manufactured as zero points.'
);


playerProjectionBacktestAssert(
    $missingOutcomeResult[0][
        'points_error'
    ]
    ===
    null,
    'Signed error is null when actual outcome evidence is missing.'
);


playerProjectionBacktestAssert(
    $missingOutcomeResult[0][
        'absolute_points_error'
    ]
    ===
    null,
    'Absolute error is null when actual outcome evidence is missing.'
);


/*
 * ============================================================
 * J. MULTIPLE PLAYERS / LOCAL ID MATCHING
 * ============================================================
 */

playerProjectionBacktestSection(
    'J. Multiple Players And Local Identity'
);


$multipleProjections = [

    $singleProjection[0],

    array_merge(
        $singleProjection[0],
        [
            'player_id' =>
                102,

            'fpl_player_id' =>
                1002,

            'name' =>
                'Player Two',

            'projected_points' =>
                4.0
        ]
    ),

    array_merge(
        $singleProjection[0],
        [
            'player_id' =>
                103,

            'fpl_player_id' =>
                1003,

            'name' =>
                'Player Three',

            'projected_points' =>
                2.0
        ]
    )
];


$multipleOutcomes = [

    [
        'gameweek_id' =>
            5,

        'player_id' =>
            103,

        'fixture_count' =>
            1,

        'total_points' =>
            5,

        'minutes' =>
            30,

        'starts' =>
            0,

        'goals' =>
            1,

        'assists' =>
            0,

        'clean_sheets' =>
            0,

        'bonus' =>
            0
    ],

    [
        'gameweek_id' =>
            5,

        'player_id' =>
            101,

        'fixture_count' =>
            1,

        'total_points' =>
            8,

        'minutes' =>
            90,

        'starts' =>
            1,

        'goals' =>
            0,

        'assists' =>
            1,

        'clean_sheets' =>
            1,

        'bonus' =>
            2
    ],

    [
        'gameweek_id' =>
            5,

        'player_id' =>
            102,

        'fixture_count' =>
            1,

        'total_points' =>
            2,

        'minutes' =>
            90,

        'starts' =>
            1,

        'goals' =>
            0,

        'assists' =>
            0,

        'clean_sheets' =>
            0,

        'bonus' =>
            0
    ]
];


$multipleResult =
    $service->evaluate(
        5,
        $multipleProjections,
        $multipleOutcomes
    );


playerProjectionBacktestAssert(
    array_column(
        $multipleResult,
        'player_id'
    )
    ===
    [
        101,
        102,
        103
    ],
    'Backtest output follows immutable projection-evidence order.'
);


playerProjectionBacktestAssert(
    $multipleResult[0][
        'actual_points'
    ]
    ===
    8
    &&
    $multipleResult[1][
        'actual_points'
    ]
    ===
    2
    &&
    $multipleResult[2][
        'actual_points'
    ]
    ===
    5,
    'Actual outcomes are matched to projections by local player ID.'
);


/*
 * ============================================================
 * K. DOUBLE GAMEWEEK OUTCOME
 * ============================================================
 *
 * PlayerGameweekOutcomeService has already aggregated fixture
 * rows. The backtest service must consume that result directly
 * rather than attempting another fixture calculation.
 * ============================================================
 */

playerProjectionBacktestSection(
    'K. Double Gameweek Outcome'
);


$dgwOutcome =
    $singleOutcome;


$dgwOutcome[0][
    'fixture_count'
] =
    2;


$dgwOutcome[0][
    'total_points'
] =
    14;


$dgwOutcome[0][
    'minutes'
] =
    175;


$dgwResult =
    $service->evaluate(
        5,
        $singleProjection,
        $dgwOutcome
    );


playerProjectionBacktestAssert(
    $dgwResult[0][
        'actual_points'
    ]
    ===
    14,
    'Already-aggregated Double Gameweek points are consumed directly.'
);


playerProjectionBacktestAssert(
    $dgwResult[0][
        'fixture_count'
    ]
    ===
    2,
    'Realised fixture count is preserved for later analysis.'
);


/*
 * ============================================================
 * L. PROJECTION DIAGNOSTIC EVIDENCE
 * ============================================================
 */

playerProjectionBacktestSection(
    'L. Historical Diagnostic Evidence'
);


playerProjectionBacktestAssert(
    $singleResult[0][
        'projected_minutes'
    ]
    ===
    90.0,
    'Historical projected minutes are preserved for later minutes evaluation.'
);


playerProjectionBacktestAssert(
    $singleResult[0][
        'actual_minutes'
    ]
    ===
    90,
    'Realised minutes are preserved for later minutes evaluation.'
);


playerProjectionBacktestAssert(
    $singleResult[0][
        'intelligence_score'
    ]
    ===
    75.0,
    'Historical Intelligence Score is preserved for later correlation analysis.'
);


playerProjectionBacktestAssert(
    $singleResult[0][
        'projection_confidence'
    ]
    ===
    0.80,
    'Historical projection confidence is preserved.'
);


/*
 * ============================================================
 * M. SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

playerProjectionBacktestSection(
    'M. Source Evidence Remains Unchanged'
);


$projectionBefore =
    $singleProjection;


$outcomeBefore =
    $singleOutcome;


$service->evaluate(
    5,
    $singleProjection,
    $singleOutcome
);


playerProjectionBacktestAssert(
    $singleProjection
    ===
    $projectionBefore,
    'Historical projection evidence is not mutated.'
);


playerProjectionBacktestAssert(
    $singleOutcome
    ===
    $outcomeBefore,
    'Realised outcome evidence is not mutated.'
);


/*
 * ============================================================
 * N. EXACT OUTPUT CONTRACT
 * ============================================================
 */

playerProjectionBacktestSection(
    'N. Exact Output Contract'
);


$expectedKeys = [

    'gameweek_id',
    'player_id',
    'fpl_player_id',
    'name',
    'position',
    'intelligence_score',
    'projected_points',
    'actual_points',
    'points_error',
    'absolute_points_error',
    'projected_minutes',
    'actual_minutes',
    'projection_confidence',
    'fixture_count'
];


playerProjectionBacktestAssert(
    array_keys(
        $singleResult[0]
    )
    ===
    $expectedKeys,
    'Backtest row exposes only the defined initial evaluation contract.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Projection Backtest Service Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}