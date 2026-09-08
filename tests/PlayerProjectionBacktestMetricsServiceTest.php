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
        'mean_absolute_error',
        'minutes_comparable_players',
        'minutes_unavailable_players',
        'mean_absolute_minutes_error'
    ];


playerProjectionBacktestMetricsAssert(
    array_keys(
        $multipleResult
    )
    ===
    $expectedKeys,
    'Metrics summary exposes only the defined projected-points and projected-minutes evaluation contract.'
);


/*
 * ============================================================
 * P. PROJECTED MINUTES — SINGLE COMPARABLE PLAYER
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'P. Projected Minutes — Single Comparable Player'
);


$minutesSingleRows = [

    [
        'projected_points' =>
            6.5,

        'actual_points' =>
            8,

        'projected_minutes' =>
            80.0,

        'actual_minutes' =>
            90
    ]
];


$minutesSingleResult =
    $service->summarise(
        $minutesSingleRows
    );


playerProjectionBacktestMetricsAssert(
    array_key_exists(
        'minutes_comparable_players',
        $minutesSingleResult
    ),
    'Metrics expose projected-minutes comparable player count.'
);


playerProjectionBacktestMetricsAssert(
    array_key_exists(
        'minutes_unavailable_players',
        $minutesSingleResult
    ),
    'Metrics expose projected-minutes unavailable player count.'
);


playerProjectionBacktestMetricsAssert(
    array_key_exists(
        'mean_absolute_minutes_error',
        $minutesSingleResult
    ),
    'Metrics expose Mean Absolute Minutes Error.'
);


playerProjectionBacktestMetricsAssert(
    $minutesSingleResult[
        'minutes_comparable_players'
    ]
    ===
    1,
    'Player with projected and actual minutes is comparable.'
);


playerProjectionBacktestMetricsAssert(
    $minutesSingleResult[
        'minutes_unavailable_players'
    ]
    ===
    0,
    'Comparable minutes evidence is not marked unavailable.'
);


playerProjectionBacktestMetricsAssert(
    $minutesSingleResult[
        'mean_absolute_minutes_error'
    ]
    ===
    10.0,
    'Mean Absolute Minutes Error is derived from projected and actual minutes.'
);


/*
 * ============================================================
 * Q. GENUINE ZERO ACTUAL MINUTES
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'Q. Genuine Zero Actual Minutes'
);


$zeroMinutesRows = [

    [
        'projected_points' =>
            5.0,

        'actual_points' =>
            0,

        'projected_minutes' =>
            70.0,

        'actual_minutes' =>
            0
    ]
];


$zeroMinutesResult =
    $service->summarise(
        $zeroMinutesRows
    );


playerProjectionBacktestMetricsAssert(
    $zeroMinutesResult[
        'minutes_comparable_players'
    ]
    ===
    1,
    'Genuine zero actual minutes remain comparable evidence.'
);


playerProjectionBacktestMetricsAssert(
    $zeroMinutesResult[
        'minutes_unavailable_players'
    ]
    ===
    0,
    'Genuine zero actual minutes are not treated as missing.'
);


playerProjectionBacktestMetricsAssert(
    $zeroMinutesResult[
        'mean_absolute_minutes_error'
    ]
    ===
    70.0,
    'Zero-minute appearance produces the correct absolute minutes error.'
);


/*
 * ============================================================
 * R. MISSING PROJECTED MINUTES
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'R. Missing Projected Minutes'
);


$missingProjectedMinutesRows = [

    [
        'projected_points' =>
            5.0,

        'actual_points' =>
            4,

        'projected_minutes' =>
            null,

        'actual_minutes' =>
            90
    ]
];


$missingProjectedMinutesResult =
    $service->summarise(
        $missingProjectedMinutesRows
    );


playerProjectionBacktestMetricsAssert(
    $missingProjectedMinutesResult[
        'minutes_comparable_players'
    ]
    ===
    0,
    'Missing projected minutes are not comparable.'
);


playerProjectionBacktestMetricsAssert(
    $missingProjectedMinutesResult[
        'minutes_unavailable_players'
    ]
    ===
    1,
    'Missing projected minutes are counted as unavailable.'
);


playerProjectionBacktestMetricsAssert(
    $missingProjectedMinutesResult[
        'mean_absolute_minutes_error'
    ]
    ===
    null,
    'Minutes MAE remains unavailable when projected minutes are missing.'
);


/*
 * ============================================================
 * S. MISSING ACTUAL MINUTES
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'S. Missing Actual Minutes'
);


$missingActualMinutesRows = [

    [
        'projected_points' =>
            5.0,

        'actual_points' =>
            null,

        'projected_minutes' =>
            75.0,

        'actual_minutes' =>
            null
    ]
];


$missingActualMinutesResult =
    $service->summarise(
        $missingActualMinutesRows
    );


playerProjectionBacktestMetricsAssert(
    $missingActualMinutesResult[
        'minutes_comparable_players'
    ]
    ===
    0,
    'Missing actual minutes are not comparable.'
);


playerProjectionBacktestMetricsAssert(
    $missingActualMinutesResult[
        'minutes_unavailable_players'
    ]
    ===
    1,
    'Missing actual minutes are counted as unavailable.'
);


playerProjectionBacktestMetricsAssert(
    $missingActualMinutesResult[
        'mean_absolute_minutes_error'
    ]
    ===
    null,
    'Minutes MAE remains unavailable when realised minutes are missing.'
);


/*
 * ============================================================
 * T. POINTS AND MINUTES HAVE INDEPENDENT AVAILABILITY
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'T. Independent Points And Minutes Availability'
);


$independentRows = [

    /*
     * Comparable for both points and minutes.
     */
    [
        'projected_points' =>
            7.0,

        'actual_points' =>
            9,

        'projected_minutes' =>
            80.0,

        'actual_minutes' =>
            90
    ],

    /*
     * Comparable for points, unavailable for minutes.
     */
    [
        'projected_points' =>
            5.0,

        'actual_points' =>
            3,

        'projected_minutes' =>
            null,

        'actual_minutes' =>
            60
    ],

    /*
     * Unavailable for points, comparable for minutes.
     */
    [
        'projected_points' =>
            null,

        'actual_points' =>
            null,

        'projected_minutes' =>
            45.0,

        'actual_minutes' =>
            30
    ]
];


$independentResult =
    $service->summarise(
        $independentRows
    );


playerProjectionBacktestMetricsAssert(
    $independentResult[
        'total_players'
    ]
    ===
    3,
    'Total player sample remains shared across points and minutes metrics.'
);


playerProjectionBacktestMetricsAssert(
    $independentResult[
        'comparable_players'
    ]
    ===
    2,
    'Existing points comparable count remains based only on points evidence.'
);


playerProjectionBacktestMetricsAssert(
    $independentResult[
        'unavailable_players'
    ]
    ===
    1,
    'Existing points unavailable count remains based only on points evidence.'
);


playerProjectionBacktestMetricsAssert(
    $independentResult[
        'mean_absolute_error'
    ]
    ===
    2.0,
    'Existing points MAE remains calculated independently of minutes evidence.'
);


playerProjectionBacktestMetricsAssert(
    $independentResult[
        'minutes_comparable_players'
    ]
    ===
    2,
    'Minutes comparable count is based only on minutes evidence.'
);


playerProjectionBacktestMetricsAssert(
    $independentResult[
        'minutes_unavailable_players'
    ]
    ===
    1,
    'Minutes unavailable count is independent of points availability.'
);


playerProjectionBacktestMetricsAssert(
    $independentResult[
        'mean_absolute_minutes_error'
    ]
    ===
    12.5,
    'Minutes MAE is calculated independently from comparable minutes evidence.'
);


/*
 * ============================================================
 * U. DERIVED MINUTES ERROR IS NOT TRUSTED
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'U. Derived Minutes Error Is Not Trusted'
);


$derivedMinutesRows = [

    [
        'projected_points' =>
            6.0,

        'actual_points' =>
            6,

        'projected_minutes' =>
            70.0,

        'actual_minutes' =>
            90,

        /*
         * Deliberately wrong derived evidence.
         *
         * The metrics service must ignore this and derive the
         * absolute error from the primary evidence itself.
         */
        'absolute_minutes_error' =>
            999.0
    ]
];


$derivedMinutesResult =
    $service->summarise(
        $derivedMinutesRows
    );


playerProjectionBacktestMetricsAssert(
    $derivedMinutesResult[
        'mean_absolute_minutes_error'
    ]
    ===
    20.0,
    'Minutes MAE is derived from primary evidence rather than supplied derived error.'
);


/*
 * ============================================================
 * V. MULTIPLE COMPARABLE MINUTES
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'V. Multiple Comparable Minutes'
);


$multipleMinutesRows = [

    [
        'projected_points' =>
            7.0,

        'actual_points' =>
            8,

        'projected_minutes' =>
            90.0,

        'actual_minutes' =>
            90
    ],

    [
        'projected_points' =>
            5.0,

        'actual_points' =>
            4,

        'projected_minutes' =>
            75.0,

        'actual_minutes' =>
            60
    ],

    [
        'projected_points' =>
            3.0,

        'actual_points' =>
            2,

        'projected_minutes' =>
            30.0,

        'actual_minutes' =>
            0
    ]
];


$multipleMinutesResult =
    $service->summarise(
        $multipleMinutesRows
    );


playerProjectionBacktestMetricsAssert(
    $multipleMinutesResult[
        'minutes_comparable_players'
    ]
    ===
    3,
    'All players with both minutes values are included in the minutes sample.'
);


playerProjectionBacktestMetricsAssert(
    $multipleMinutesResult[
        'minutes_unavailable_players'
    ]
    ===
    0,
    'No minutes evidence is unavailable when every player has both values.'
);


playerProjectionBacktestMetricsAssert(
    $multipleMinutesResult[
        'mean_absolute_minutes_error'
    ]
    ===
    15.0,
    'Minutes MAE is the arithmetic mean of absolute minutes errors.'
);


/*
 * ============================================================
 * W. MALFORMED ROWS DO NOT AFFECT MINUTES SAMPLE
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'W. Malformed Rows'
);


$malformedMinutesRows = [

    'invalid',

    [
        'projected_points' =>
            4.0,

        'actual_points' =>
            5,

        'projected_minutes' =>
            60.0,

        'actual_minutes' =>
            90
    ]
];


$malformedMinutesResult =
    $service->summarise(
        $malformedMinutesRows
    );


playerProjectionBacktestMetricsAssert(
    $malformedMinutesResult[
        'total_players'
    ]
    ===
    1,
    'Malformed rows remain excluded from the total player sample.'
);


playerProjectionBacktestMetricsAssert(
    $malformedMinutesResult[
        'minutes_comparable_players'
    ]
    ===
    1,
    'Malformed rows do not affect the comparable minutes sample.'
);


playerProjectionBacktestMetricsAssert(
    $malformedMinutesResult[
        'minutes_unavailable_players'
    ]
    ===
    0,
    'Malformed rows do not create unavailable minutes evidence.'
);


playerProjectionBacktestMetricsAssert(
    $malformedMinutesResult[
        'mean_absolute_minutes_error'
    ]
    ===
    30.0,
    'Minutes MAE ignores malformed rows.'
);


/*
 * ============================================================
 * X. SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

playerProjectionBacktestMetricsSection(
    'X. Minutes Source Evidence Remains Unchanged'
);


$minutesImmutabilityRows = [

    [
        'projected_points' =>
            5.0,

        'actual_points' =>
            6,

        'projected_minutes' =>
            75.0,

        'actual_minutes' =>
            90
    ]
];


$minutesImmutabilityBefore =
    $minutesImmutabilityRows;


$service->summarise(
    $minutesImmutabilityRows
);


playerProjectionBacktestMetricsAssert(
    $minutesImmutabilityRows
    ===
    $minutesImmutabilityBefore,
    'Calculating projected-minutes metrics does not mutate backtest evidence.'
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