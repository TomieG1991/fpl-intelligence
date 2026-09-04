<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * PLAYER PROJECTION EVIDENCE TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * Recommendation history must preserve the projection evidence
 * that already existed when the recommendation was generated.
 *
 * This adapter must not:
 *
 * - calculate Expected Points
 * - calculate Expected Minutes
 * - calculate projection confidence
 * - calculate Player Intelligence
 * - alter the manager's squad
 *
 * It only selects existing Player Intelligence summary evidence
 * for the players belonging to the supplied squad.
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


function playerProjectionEvidenceAssert(
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


function playerProjectionEvidenceSection(
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
 * EXISTING PLAYER INTELLIGENCE SUMMARIES
 * ============================================================
 *
 * These are deliberately production-shaped rather than a new
 * projection model.
 */

$playerSummaries = [

    [
        'player_id' =>
            101,

        'fpl_player_id' =>
            1001,

        'name' =>
            'Player One',

        'position' =>
            'GK',

        'team_id' =>
            1,

        'price' =>
            5.5,

        'intelligence_score' =>
            71.25,

        'projected_points' =>
            5.75,

        'projected_minutes' =>
            90.0,

        'projection_confidence' =>
            0.84,

        'projection_confidence_percent' =>
            84.0,

        'projection_confidence_label' =>
            'High',

        'projected_points_components' => [

            'appearance' =>
                2.0,

            'performance' =>
                3.75
        ],

        'projected_points_inputs' => [

            'expected_minutes' =>
                90.0,

            'fixture_rating' =>
                67.5
        ],

        'has_projected_points' =>
            true
    ],

    [
        'player_id' =>
            102,

        'fpl_player_id' =>
            1002,

        'name' =>
            'Player Two',

        'position' =>
            'DEF',

        'team_id' =>
            2,

        'price' =>
            6.0,

        'intelligence_score' =>
            78.50,

        'projected_points' =>
            6.25,

        'projected_minutes' =>
            82.0,

        'projection_confidence' =>
            0.76,

        'projection_confidence_percent' =>
            76.0,

        'projection_confidence_label' =>
            'Medium',

        'projected_points_components' => [

            'appearance' =>
                1.82,

            'performance' =>
                4.43
        ],

        'projected_points_inputs' => [

            'expected_minutes' =>
                82.0,

            'fixture_rating' =>
                72.0
        ],

        'has_projected_points' =>
            true
    ],

    [
        'player_id' =>
            103,

        'fpl_player_id' =>
            1003,

        'name' =>
            'Player Three',

        'position' =>
            'MID',

        'team_id' =>
            3,

        'price' =>
            8.0,

        'intelligence_score' =>
            81.0,

        'projected_points' =>
            null,

        'projected_minutes' =>
            35.0,

        'projection_confidence' =>
            0.41,

        'projection_confidence_percent' =>
            41.0,

        'projection_confidence_label' =>
            'Low',

        'projected_points_components' =>
            [],

        'projected_points_inputs' => [

            'expected_minutes' =>
                35.0
        ],

        'has_projected_points' =>
            false
    ],

    /*
     * This player is deliberately outside the manager's squad.
     */
    [
        'player_id' =>
            999,

        'fpl_player_id' =>
            1999,

        'name' =>
            'Outside Squad',

        'position' =>
            'FWD',

        'team_id' =>
            4,

        'price' =>
            9.5,

        'intelligence_score' =>
            90.0,

        'projected_points' =>
            10.5,

        'projected_minutes' =>
            90.0,

        'projection_confidence' =>
            0.95,

        'projection_confidence_percent' =>
            95.0,

        'projection_confidence_label' =>
            'High',

        'projected_points_components' => [

            'appearance' =>
                2.0,

            'performance' =>
                8.5
        ],

        'projected_points_inputs' => [

            'expected_minutes' =>
                90.0
        ],

        'has_projected_points' =>
            true
    ]
];


/*
 * ============================================================
 * MANAGER SQUAD
 * ============================================================
 *
 * This mirrors the local player identity exposed by the mapped
 * squad used by Gameweek Intelligence.
 */

$squadPlayers = [

    [
        'player_id' =>
            101,

        'squad_position' =>
            1,

        'multiplier' =>
            1
    ],

    [
        'player_id' =>
            102,

        'squad_position' =>
            2,

        'multiplier' =>
            1
    ],

    [
        'player_id' =>
            103,

        'squad_position' =>
            12,

        'multiplier' =>
            0
    ]
];


/*
 * ============================================================
 * ADAPTER
 * ============================================================
 */

$adapter =
    new PlayerProjectionEvidence();


/*
 * ============================================================
 * A. BUILD SQUAD PROJECTION EVIDENCE
 * ============================================================
 */

playerProjectionEvidenceSection(
    'A. Build Squad Projection Evidence'
);


$evidence =
    $adapter
        ->build(
            $squadPlayers,
            $playerSummaries
        );


playerProjectionEvidenceAssert(
    is_array(
        $evidence
    ),
    'Player projection evidence is returned as an array.'
);


playerProjectionEvidenceAssert(
    count(
        $evidence
    )
    ===
    3,
    'Only players belonging to the supplied squad are preserved.'
);


/*
 * ============================================================
 * B. SQUAD ORDER PRESERVED
 * ============================================================
 */

playerProjectionEvidenceSection(
    'B. Squad Order'
);


playerProjectionEvidenceAssert(
    array_column(
        $evidence,
        'player_id'
    )
    ===
    [
        101,
        102,
        103
    ],
    'Projection evidence follows the supplied squad order.'
);


/*
 * ============================================================
 * C. PLAYER IDENTITY PRESERVED
 * ============================================================
 */

playerProjectionEvidenceSection(
    'C. Player Identity'
);


playerProjectionEvidenceAssert(
    $evidence[
        0
    ][
        'player_id'
    ]
    ===
    101,
    'Local player ID is preserved.'
);


playerProjectionEvidenceAssert(
    $evidence[
        0
    ][
        'fpl_player_id'
    ]
    ===
    1001,
    'Official FPL player ID is preserved.'
);


playerProjectionEvidenceAssert(
    $evidence[
        0
    ][
        'name'
    ]
    ===
    'Player One',
    'Player name is preserved.'
);


playerProjectionEvidenceAssert(
    $evidence[
        0
    ][
        'position'
    ]
    ===
    'GK',
    'Player position is preserved.'
);


playerProjectionEvidenceAssert(
    $evidence[
        0
    ][
        'team_id'
    ]
    ===
    1,
    'Local team ID is preserved.'
);


/*
 * ============================================================
 * D. CORE MODEL EVIDENCE PRESERVED
 * ============================================================
 */

playerProjectionEvidenceSection(
    'D. Core Model Evidence'
);


playerProjectionEvidenceAssert(
    $evidence[
        0
    ][
        'price'
    ]
    ===
    5.5,
    'Historical player price is preserved.'
);


playerProjectionEvidenceAssert(
    $evidence[
        0
    ][
        'intelligence_score'
    ]
    ===
    71.25,
    'Historical Intelligence Score is preserved.'
);


playerProjectionEvidenceAssert(
    $evidence[
        0
    ][
        'projected_points'
    ]
    ===
    5.75,
    'Existing projected points are preserved.'
);


playerProjectionEvidenceAssert(
    $evidence[
        1
    ][
        'projected_minutes'
    ]
    ===
    82.0,
    'Existing projected minutes are preserved.'
);


/*
 * ============================================================
 * E. PROJECTION CONFIDENCE PRESERVED
 * ============================================================
 */

playerProjectionEvidenceSection(
    'E. Projection Confidence'
);


playerProjectionEvidenceAssert(
    $evidence[
        1
    ][
        'projection_confidence'
    ]
    ===
    0.76,
    'Raw projection confidence is preserved.'
);


playerProjectionEvidenceAssert(
    $evidence[
        1
    ][
        'projection_confidence_percent'
    ]
    ===
    76.0,
    'Projection confidence percentage is preserved.'
);


playerProjectionEvidenceAssert(
    $evidence[
        1
    ][
        'projection_confidence_label'
    ]
    ===
    'Medium',
    'Projection confidence label is preserved.'
);


/*
 * ============================================================
 * F. SUPPORTING PROJECTION COMPONENTS PRESERVED
 * ============================================================
 */

playerProjectionEvidenceSection(
    'F. Supporting Projection Evidence'
);


playerProjectionEvidenceAssert(
    $evidence[
        0
    ][
        'projected_points_components'
    ]
    ===
    $playerSummaries[
        0
    ][
        'projected_points_components'
    ],
    'Projected-points components are preserved unchanged.'
);


playerProjectionEvidenceAssert(
    $evidence[
        0
    ][
        'projected_points_inputs'
    ]
    ===
    $playerSummaries[
        0
    ][
        'projected_points_inputs'
    ],
    'Projected-points inputs are preserved unchanged.'
);


playerProjectionEvidenceAssert(
    $evidence[
        0
    ][
        'has_projected_points'
    ]
    ===
    true,
    'Projected-points availability flag is preserved.'
);


/*
 * ============================================================
 * G. UNAVAILABLE PROJECTION EVIDENCE PRESERVED
 * ============================================================
 *
 * A player without a usable projection must not disappear from
 * history. The fact that the model had no projected points is
 * itself historical evidence.
 */

playerProjectionEvidenceSection(
    'G. Unavailable Projection Evidence'
);


playerProjectionEvidenceAssert(
    $evidence[
        2
    ][
        'player_id'
    ]
    ===
    103,
    'Squad player without projected points remains in evidence.'
);


playerProjectionEvidenceAssert(
    $evidence[
        2
    ][
        'projected_points'
    ]
    ===
    null,
    'Unavailable projected points remain null.'
);


playerProjectionEvidenceAssert(
    $evidence[
        2
    ][
        'has_projected_points'
    ]
    ===
    false,
    'Unavailable projection state is preserved.'
);


/*
 * ============================================================
 * H. NON-SQUAD PLAYER EXCLUDED
 * ============================================================
 */

playerProjectionEvidenceSection(
    'H. Non-Squad Player Excluded'
);


$evidencePlayerIds =
    array_column(
        $evidence,
        'player_id'
    );


playerProjectionEvidenceAssert(
    !in_array(
        999,
        $evidencePlayerIds,
        true
    ),
    'Player outside the manager squad is not captured.'
);


/*
 * ============================================================
 * I. NO RECALCULATION / STABLE FIELD CONTRACT
 * ============================================================
 */

playerProjectionEvidenceSection(
    'I. Stable Historical Contract'
);


$expectedKeys = [

    'player_id',
    'fpl_player_id',
    'name',
    'position',
    'team_id',
    'price',
    'intelligence_score',
    'projected_points',
    'projected_minutes',
    'projection_confidence',
    'projection_confidence_percent',
    'projection_confidence_label',
    'projected_points_components',
    'projected_points_inputs',
    'has_projected_points'
];


playerProjectionEvidenceAssert(
    array_keys(
        $evidence[
            0
        ]
    )
    ===
    $expectedKeys,
    'Historical projection evidence exposes only the defined stable fields.'
);


/*
 * ============================================================
 * J. MISSING PLAYER SUMMARY REJECTED
 * ============================================================
 *
 * Silently capturing only part of a squad would make later
 * backtesting misleading.
 */

playerProjectionEvidenceSection(
    'J. Missing Player Summary'
);


$missingSummaryRejected =
    false;


try {

    $adapter
        ->build(
            [
                [
                    'player_id' =>
                        101
                ],

                [
                    'player_id' =>
                        777
                ]
            ],
            $playerSummaries
        );

} catch (
    InvalidArgumentException $exception
) {

    $missingSummaryRejected =
        true;
}


playerProjectionEvidenceAssert(
    $missingSummaryRejected,
    'Missing Player Intelligence summary is rejected rather than silently omitted.'
);


/*
 * ============================================================
 * K. INVALID SQUAD PLAYER ID REJECTED
 * ============================================================
 */

playerProjectionEvidenceSection(
    'K. Invalid Squad Player ID'
);


$invalidPlayerIdRejected =
    false;


try {

    $adapter
        ->build(
            [
                [
                    'player_id' =>
                        0
                ]
            ],
            $playerSummaries
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidPlayerIdRejected =
        true;
}


playerProjectionEvidenceAssert(
    $invalidPlayerIdRejected,
    'Invalid local squad player ID is rejected.'
);


/*
 * ============================================================
 * L. EMPTY SQUAD REJECTED
 * ============================================================
 */

playerProjectionEvidenceSection(
    'L. Empty Squad'
);


$emptySquadRejected =
    false;


try {

    $adapter
        ->build(
            [],
            $playerSummaries
        );

} catch (
    InvalidArgumentException $exception
) {

    $emptySquadRejected =
        true;
}


playerProjectionEvidenceAssert(
    $emptySquadRejected,
    'Empty squad evidence is rejected.'
);


/*
 * ============================================================
 * M. EMPTY PLAYER INTELLIGENCE REJECTED
 * ============================================================
 */

playerProjectionEvidenceSection(
    'M. Empty Player Intelligence'
);


$emptySummariesRejected =
    false;


try {

    $adapter
        ->build(
            $squadPlayers,
            []
        );

} catch (
    InvalidArgumentException $exception
) {

    $emptySummariesRejected =
        true;
}


playerProjectionEvidenceAssert(
    $emptySummariesRejected,
    'Empty Player Intelligence summaries are rejected.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

playerProjectionEvidenceSection(
    'Player Projection Evidence Test Summary'
);


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if (
    $failed === 0
) {

    echo "<strong>RESULT: ALL TESTS PASSED ✅</strong><br>";

} else {

    echo "<strong>RESULT: TEST FAILURES DETECTED ❌</strong><br>";
}