<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * PLAYER PROJECTION BACKTEST METRICS SERVICE TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * This service summarises existing player-level backtest
 * comparison rows.
 *
 * It must not:
 *
 * - recalculate Expected Points
 * - reconstruct historical recommendations
 * - query live player data
 * - query fixture history
 * - manufacture missing outcomes
 * - assign arbitrary accuracy grades
 * - tune or calibrate the model
 *
 * Initial metric:
 *
 * Mean Absolute Error (MAE)
 *
 * Only rows containing both a numerical historical projection
 * and a numerical realised outcome belong to the comparable
 * sample.
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


function playerProjectionBacktestMetricsAssert(
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


function playerProjectionBacktestMetricsSection(
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

playerProjectionBacktestMetricsSection(
    'A. Service Contract'
);


if (
    !class_exists(
        'PlayerProjectionBacktestMetricsService'
    )
) {

    playerProjectionBacktestMetricsAssert(
        false,
        'PlayerProjectionBacktestMetricsService exists.'
    );


    echo "<br>";
    echo "============================================<br>";
    echo "Player Projection Backtest Metrics Service Test Summary<br>";
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


playerProjectionBacktestMetricsAssert(
    true,
    'PlayerProjectionBacktestMetricsService exists.'
);


$service =
    new PlayerProjectionBacktestMetricsService();


/*
 * ============================================================
 * B. EMPTY BACKTEST EVIDENCE
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'B. Empty Backtest Evidence'
);


$emptyResult =
    $service->summarise(
        []
    );


playerProjectionBacktestMetricsAssert(
    $emptyResult[
        'total_players'
    ]
    ===
    0,
    'Empty evidence reports zero total players.'
);


playerProjectionBacktestMetricsAssert(
    $emptyResult[
        'comparable_players'
    ]
    ===
    0,
    'Empty evidence reports zero comparable players.'
);


playerProjectionBacktestMetricsAssert(
    $emptyResult[
        'unavailable_players'
    ]
    ===
    0,
    'Empty evidence reports zero unavailable players.'
);


playerProjectionBacktestMetricsAssert(
    $emptyResult[
        'mean_absolute_error'
    ]
    ===
    null,
    'Mean absolute error is null when there is no comparable evidence.'
);


/*
 * ============================================================
 * C. SINGLE COMPARABLE PLAYER
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'C. Single Comparable Player'
);


$singleRow = [

    [
        'gameweek_id' =>
            5,

        'player_id' =>
            101,

        'fpl_player_id' =>
            1001,

        'name' =>
            'Player One',

        'position' =>
            'MID',

        'intelligence_score' =>
            75.0,

        'projected_points' =>
            6.5,

        'actual_points' =>
            9,

        'points_error' =>
            2.5,

        'absolute_points_error' =>
            2.5,

        'projected_minutes' =>
            90.0,

        'actual_minutes' =>
            90,

        'projection_confidence' =>
            0.80,

        'fixture_count' =>
            1
    ]
];


$singleResult =
    $service->summarise(
        $singleRow
    );


playerProjectionBacktestMetricsAssert(
    $singleResult[
        'total_players'
    ]
    ===
    1,
    'Single evidence row reports one total player.'
);


playerProjectionBacktestMetricsAssert(
    $singleResult[
        'comparable_players'
    ]
    ===
    1,
    'Single complete evidence row is comparable.'
);


playerProjectionBacktestMetricsAssert(
    $singleResult[
        'unavailable_players'
    ]
    ===
    0,
    'Single complete evidence row is not unavailable.'
);


playerProjectionBacktestMetricsAssert(
    $singleResult[
        'mean_absolute_error'
    ]
    ===
    2.5,
    'Single-player MAE equals that player absolute error.'
);


/*
 * ============================================================
 * D. MULTIPLE COMPARABLE PLAYERS
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'D. Multiple Comparable Players'
);


$multipleRows = [

    array_merge(
        $singleRow[0],
        [
            'player_id' =>
                101,

            'projected_points' =>
                6.5,

            'actual_points' =>
                9,

            'points_error' =>
                2.5,

            'absolute_points_error' =>
                2.5
        ]
    ),

    array_merge(
        $singleRow[0],
        [
            'player_id' =>
                102,

            'projected_points' =>
                8.0,

            'actual_points' =>
                3,

            'points_error' =>
                -5.0,

            'absolute_points_error' =>
                5.0
        ]
    ),

    array_merge(
        $singleRow[0],
        [
            'player_id' =>
                103,

            'projected_points' =>
                4.5,

            'actual_points' =>
                6,

            'points_error' =>
                1.5,

            'absolute_points_error' =>
                1.5
        ]
    )
];


$multipleResult =
    $service->summarise(
        $multipleRows
    );


playerProjectionBacktestMetricsAssert(
    $multipleResult[
        'total_players'
    ]
    ===
    3,
    'Three evidence rows report three total players.'
);


playerProjectionBacktestMetricsAssert(
    $multipleResult[
        'comparable_players'
    ]
    ===
    3,
    'All three complete evidence rows are comparable.'
);


playerProjectionBacktestMetricsAssert(
    $multipleResult[
        'unavailable_players'
    ]
    ===
    0,
    'No complete evidence rows are unavailable.'
);


playerProjectionBacktestMetricsAssert(
    $multipleResult[
        'mean_absolute_error'
    ]
    ===
    3.0,
    'MAE is the arithmetic mean of player absolute errors.'
);


/*
 * ============================================================
 * E. MISSING HISTORICAL PROJECTION
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'E. Missing Historical Projection'
);


$missingProjectionRows = [

    $singleRow[0],

    array_merge(
        $singleRow[0],
        [
            'player_id' =>
                102,

            'projected_points' =>
                null,

            'actual_points' =>
                5,

            'points_error' =>
                null,

            'absolute_points_error' =>
                null
        ]
    )
];


$missingProjectionResult =
    $service->summarise(
        $missingProjectionRows
    );


playerProjectionBacktestMetricsAssert(
    $missingProjectionResult[
        'total_players'
    ]
    ===
    2,
    'Player without historical projection remains in total evidence count.'
);


playerProjectionBacktestMetricsAssert(
    $missingProjectionResult[
        'comparable_players'
    ]
    ===
    1,
    'Player without historical projection is excluded from comparable sample.'
);


playerProjectionBacktestMetricsAssert(
    $missingProjectionResult[
        'unavailable_players'
    ]
    ===
    1,
    'Player without historical projection is counted as unavailable.'
);


playerProjectionBacktestMetricsAssert(
    $missingProjectionResult[
        'mean_absolute_error'
    ]
    ===
    2.5,
    'Unavailable historical projection does not distort MAE.'
);


/*
 * ============================================================
 * F. MISSING REALISED OUTCOME
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'F. Missing Realised Outcome'
);


$missingOutcomeRows = [

    $singleRow[0],

    array_merge(
        $singleRow[0],
        [
            'player_id' =>
                102,

            'projected_points' =>
                4.0,

            'actual_points' =>
                null,

            'points_error' =>
                null,

            'absolute_points_error' =>
                null,

            'actual_minutes' =>
                null,

            'fixture_count' =>
                null
        ]
    )
];


$missingOutcomeResult =
    $service->summarise(
        $missingOutcomeRows
    );


playerProjectionBacktestMetricsAssert(
    $missingOutcomeResult[
        'total_players'
    ]
    ===
    2,
    'Player without realised outcome remains in total evidence count.'
);


playerProjectionBacktestMetricsAssert(
    $missingOutcomeResult[
        'comparable_players'
    ]
    ===
    1,
    'Player without realised outcome is excluded from comparable sample.'
);


playerProjectionBacktestMetricsAssert(
    $missingOutcomeResult[
        'unavailable_players'
    ]
    ===
    1,
    'Player without realised outcome is counted as unavailable.'
);


playerProjectionBacktestMetricsAssert(
    $missingOutcomeResult[
        'mean_absolute_error'
    ]
    ===
    2.5,
    'Missing realised outcome does not become zero or distort MAE.'
);


/*
 * ============================================================
 * G. GENUINE ZERO ACTUAL POINTS
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'G. Genuine Zero Actual Points'
);


$zeroActualRows = [

    array_merge(
        $singleRow[0],
        [
            'projected_points' =>
                5.0,

            'actual_points' =>
                0,

            'points_error' =>
                -5.0,

            'absolute_points_error' =>
                5.0,

            'actual_minutes' =>
                0,

            'fixture_count' =>
                1
        ]
    )
];


$zeroActualResult =
    $service->summarise(
        $zeroActualRows
    );


playerProjectionBacktestMetricsAssert(
    $zeroActualResult[
        'comparable_players'
    ]
    ===
    1,
    'Genuine zero actual points remain comparable evidence.'
);


playerProjectionBacktestMetricsAssert(
    $zeroActualResult[
        'unavailable_players'
    ]
    ===
    0,
    'Genuine zero actual points are not classified as unavailable.'
);


playerProjectionBacktestMetricsAssert(
    $zeroActualResult[
        'mean_absolute_error'
    ]
    ===
    5.0,
    'Genuine zero actual points contribute normally to MAE.'
);


/*
 * ============================================================
 * H. NEGATIVE ACTUAL POINTS
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'H. Negative Actual Points'
);


$negativeActualRows = [

    array_merge(
        $singleRow[0],
        [
            'projected_points' =>
                4.0,

            'actual_points' =>
                -2,

            'points_error' =>
                -6.0,

            'absolute_points_error' =>
                6.0
        ]
    )
];


$negativeActualResult =
    $service->summarise(
        $negativeActualRows
    );


playerProjectionBacktestMetricsAssert(
    $negativeActualResult[
        'comparable_players'
    ]
    ===
    1,
    'Negative actual points remain comparable evidence.'
);


playerProjectionBacktestMetricsAssert(
    $negativeActualResult[
        'mean_absolute_error'
    ]
    ===
    6.0,
    'Negative actual points contribute normally to MAE.'
);


/*
 * ============================================================
 * I. EXACT PROJECTION
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'I. Exact Projection'
);


$exactRows = [

    array_merge(
        $singleRow[0],
        [
            'projected_points' =>
                7.0,

            'actual_points' =>
                7,

            'points_error' =>
                0.0,

            'absolute_points_error' =>
                0.0
        ]
    )
];


$exactResult =
    $service->summarise(
        $exactRows
    );


playerProjectionBacktestMetricsAssert(
    $exactResult[
        'comparable_players'
    ]
    ===
    1,
    'Exact projection remains comparable evidence.'
);


playerProjectionBacktestMetricsAssert(
    $exactResult[
        'mean_absolute_error'
    ]
    ===
    0.0,
    'Exact projection produces zero MAE.'
);


/*
 * ============================================================
 * J. ALL EVIDENCE UNAVAILABLE
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'J. All Evidence Unavailable'
);


$allUnavailableRows = [

    array_merge(
        $singleRow[0],
        [
            'player_id' =>
                101,

            'projected_points' =>
                null,

            'actual_points' =>
                5,

            'points_error' =>
                null,

            'absolute_points_error' =>
                null
        ]
    ),

    array_merge(
        $singleRow[0],
        [
            'player_id' =>
                102,

            'projected_points' =>
                4.0,

            'actual_points' =>
                null,

            'points_error' =>
                null,

            'absolute_points_error' =>
                null
        ]
    )
];


$allUnavailableResult =
    $service->summarise(
        $allUnavailableRows
    );


playerProjectionBacktestMetricsAssert(
    $allUnavailableResult[
        'total_players'
    ]
    ===
    2,
    'All unavailable rows remain represented in total evidence.'
);


playerProjectionBacktestMetricsAssert(
    $allUnavailableResult[
        'comparable_players'
    ]
    ===
    0,
    'No comparable players are reported when all evidence is unavailable.'
);


playerProjectionBacktestMetricsAssert(
    $allUnavailableResult[
        'unavailable_players'
    ]
    ===
    2,
    'All non-comparable players are counted as unavailable.'
);


playerProjectionBacktestMetricsAssert(
    $allUnavailableResult[
        'mean_absolute_error'
    ]
    ===
    null,
    'MAE remains null rather than zero when no comparison is possible.'
);


/*
 * ============================================================
 * K. MIXED SAMPLE
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'K. Mixed Comparable And Unavailable Sample'
);


$mixedRows = [

    array_merge(
        $singleRow[0],
        [
            'player_id' =>
                101,

            'projected_points' =>
                7.0,

            'actual_points' =>
                9,

            'points_error' =>
                2.0,

            'absolute_points_error' =>
                2.0
        ]
    ),

    array_merge(
        $singleRow[0],
        [
            'player_id' =>
                102,

            'projected_points' =>
                null,

            'actual_points' =>
                4,

            'points_error' =>
                null,

            'absolute_points_error' =>
                null
        ]
    ),

    array_merge(
        $singleRow[0],
        [
            'player_id' =>
                103,

            'projected_points' =>
                6.0,

            'actual_points' =>
                2,

            'points_error' =>
                -4.0,

            'absolute_points_error' =>
                4.0
        ]
    ),

    array_merge(
        $singleRow[0],
        [
            'player_id' =>
                104,

            'projected_points' =>
                3.0,

            'actual_points' =>
                null,

            'points_error' =>
                null,

            'absolute_points_error' =>
                null
        ]
    )
];


$mixedResult =
    $service->summarise(
        $mixedRows
    );


playerProjectionBacktestMetricsAssert(
    $mixedResult[
        'total_players'
    ]
    ===
    4,
    'Mixed sample reports all four historical player rows.'
);


playerProjectionBacktestMetricsAssert(
    $mixedResult[
        'comparable_players'
    ]
    ===
    2,
    'Mixed sample reports only two comparable players.'
);


playerProjectionBacktestMetricsAssert(
    $mixedResult[
        'unavailable_players'
    ]
    ===
    2,
    'Mixed sample reports two unavailable players.'
);


playerProjectionBacktestMetricsAssert(
    $mixedResult[
        'mean_absolute_error'
    ]
    ===
    3.0,
    'Mixed sample MAE uses only comparable player errors.'
);


/*
 * ============================================================
 * L. DERIVE ERROR FROM PRIMARY EVIDENCE
 * ============================================================
 *
 * The metrics service should not blindly trust a supplied
 * absolute_points_error value.
 *
 * Its source evidence is the preserved projection and realised
 * points. This protects the aggregate metric from a stale or
 * malformed derived comparison field.
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'L. Derive Error From Primary Evidence'
);


$derivedErrorRows = [

    array_merge(
        $singleRow[0],
        [
            'projected_points' =>
                10.0,

            'actual_points' =>
                6,

            'points_error' =>
                999.0,

            'absolute_points_error' =>
                999.0
        ]
    )
];


$derivedErrorResult =
    $service->summarise(
        $derivedErrorRows
    );


playerProjectionBacktestMetricsAssert(
    $derivedErrorResult[
        'mean_absolute_error'
    ]
    ===
    4.0,
    'MAE is derived from projected and actual points rather than trusting supplied error fields.'
);


/*
 * ============================================================
 * M. NON-ARRAY ROWS
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'M. Invalid Evidence Rows'
);


$invalidRowsResult =
    $service->summarise(
        [
            $singleRow[0],
            'invalid row',
            null,
            123
        ]
    );


playerProjectionBacktestMetricsAssert(
    $invalidRowsResult[
        'total_players'
    ]
    ===
    1,
    'Non-array evidence rows are ignored.'
);


playerProjectionBacktestMetricsAssert(
    $invalidRowsResult[
        'comparable_players'
    ]
    ===
    1,
    'Valid evidence remains comparable when invalid rows are present.'
);


playerProjectionBacktestMetricsAssert(
    $invalidRowsResult[
        'mean_absolute_error'
    ]
    ===
    2.5,
    'Invalid evidence rows do not distort MAE.'
);


/*
 * ============================================================
 * N. SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'N. Source Evidence Remains Unchanged'
);


$sourceRows =
    $multipleRows;


$sourceRowsBefore =
    $sourceRows;


$service->summarise(
    $sourceRows
);


playerProjectionBacktestMetricsAssert(
    $sourceRows
    ===
    $sourceRowsBefore,
    'Player-level backtest evidence is not mutated.'
);


/*
 * ============================================================
 * O. EXACT OUTPUT CONTRACT
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'O. Exact Output Contract'
);


$expectedKeys = [

    'total_players',
    'comparable_players',
    'unavailable_players',
    'mean_absolute_error'
];


playerProjectionBacktestMetricsAssert(
    array_keys(
        $multipleResult
    )
    ===
    $expectedKeys,
    'Metrics summary exposes only the defined initial evaluation contract.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Projection Backtest Metrics Service Test Summary<br>";
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