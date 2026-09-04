<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION SNAPSHOT CAPTURE SERVICE TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * This test defines the orchestration contract for capturing
 * already-calculated recommendation intelligence.
 *
 * The capture service must:
 *
 * - NOT calculate Player Intelligence
 * - NOT calculate Expected Points
 * - NOT calculate Gameweek Intelligence
 * - NOT calculate Captain Intelligence
 * - NOT calculate Transfer Intelligence
 * - NOT calculate Chip Intelligence
 *
 * It receives existing outputs, maps them into an immutable
 * RecommendationSnapshot and persists that snapshot through the
 * existing RecommendationSnapshotRepository.
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


function captureServiceAssert(
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


function captureServiceSection(
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
 * DATABASE
 * ============================================================
 */

$database =
    new Database();


$db =
    $database
        ->getConnection();


/*
 * ============================================================
 * FIND A REAL LOCAL GAMEWEEK
 * ============================================================
 *
 * Recommendation snapshots use the existing local gameweek ID.
 *
 * The test deliberately uses an existing gameweeks row rather
 * than inventing another gameweek identity.
 */

$gameweekStatement =
    $db->query(
        "
        SELECT
            id,
            fpl_gameweek_id,
            deadline_time
        FROM gameweeks
        ORDER BY id ASC
        LIMIT 1
        "
    );


$gameweekRow =
    $gameweekStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


if (
    !is_array(
        $gameweekRow
    )
    ||
    (int) (
        $gameweekRow[
            'id'
        ]
        ?? 0
    )
    <= 0
    ||
    empty(
        $gameweekRow[
            'deadline_time'
        ]
        ?? null
    )
) {

    die(
        'RecommendationSnapshotCaptureServiceTest requires '
        . 'at least one valid row in the gameweeks table.'
    );
}


$gameweekId =
    (int) $gameweekRow[
        'id'
    ];


$deadlineTime =
    (string) $gameweekRow[
        'deadline_time'
    ];


/*
 * Capture one hour before the real stored deadline.
 *
 * This keeps the test deterministic and satisfies the domain
 * requirement that recommendation evidence must be captured
 * before the deadline.
 */

$deadlineTimestamp =
    strtotime(
        $deadlineTime
    );


if (
    $deadlineTimestamp === false
) {

    die(
        'RecommendationSnapshotCaptureServiceTest could not parse '
        . 'the selected gameweek deadline.'
    );
}


$capturedAt =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestamp
        -
        3600
    );


/*
 * ============================================================
 * TEST ENTRY IDS
 * ============================================================
 *
 * Deliberately synthetic. Automated tests must not depend on a
 * real manager's FPL entry.
 */

$entryId =
    935001001;


$duplicateEntryId =
    $entryId;


/*
 * ============================================================
 * CLEAN TEST ROW
 * ============================================================
 */

$cleanupStatement =
    $db->prepare(
        "
        DELETE FROM recommendation_snapshots
        WHERE entry_id = :entry_id
        AND gameweek_id = :gameweek_id
        "
    );


$cleanupStatement->execute(
    [
        'entry_id' =>
            $entryId,

        'gameweek_id' =>
            $gameweekId
    ]
);


/*
 * ============================================================
 * EXISTING PLAYER PROJECTION EVIDENCE
 * ============================================================
 *
 * These are already-calculated model outputs.
 *
 * The capture service must preserve them exactly.
 */

$playerProjections = [

    [
        'player_id' =>
            101,

        'name' =>
            'Projection Player',

        'projected_points' =>
            7.25,

        'projected_minutes' =>
            82.0,

        'projection_confidence' =>
            0.78,

        'intelligence_score' =>
            73.4,

        'components' => [

            'appearance' =>
                1.8,

            'attacking' =>
                4.2
        ]
    ],

    [
        'player_id' =>
            102,

        'name' =>
            'Second Projection Player',

        'projected_points' =>
            5.75,

        'projected_minutes' =>
            76.0,

        'projection_confidence' =>
            0.71,

        'intelligence_score' =>
            68.2
    ]
];


/*
 * ============================================================
 * EXISTING GAMEWEEK INTELLIGENCE OUTPUT
 * ============================================================
 *
 * This mirrors the production orchestration shape returned by
 * PlayerIntelligenceService::getGameweekDecision().
 */

$startingXI =
    [];


for (
    $i = 1;
    $i <= 11;
    $i++
) {

    $startingXI[] = [

        'player_id' =>
            $i,

        'name' =>
            'Starting Player '
            . $i,

        'projected_points' =>
            4.0
            +
            (
                $i
                /
                10
            )
    ];
}


$bench = [

    [
        'player_id' =>
            12,

        'name' =>
            'Bench Player 12'
    ],

    [
        'player_id' =>
            13,

        'name' =>
            'Bench Player 13'
    ],

    [
        'player_id' =>
            14,

        'name' =>
            'Bench Player 14'
    ],

    [
        'player_id' =>
            15,

        'name' =>
            'Bench Player 15'
    ]
];


$gameweekOutput = [

    'status' =>
        'success',

    'formation' =>
        '3-4-3',

    'starting_xi_score' =>
        71.25,

    'bench_score' =>
        18.5,

    'starting_xi' =>
        $startingXI,

    'bench' =>
        $bench
];


$captainOutput = [

    'status' =>
        'success',

    'captain' => [

        'player_id' =>
            5,

        'name' =>
            'Captain Player',

        'captain_score' =>
            72.5
    ],

    'vice_captain' => [

        'player_id' =>
            9,

        'name' =>
            'Vice Captain Player',

        'captain_score' =>
            68.0
    ]
];


$transferOutput = [

    'status' =>
        'success',

    'recommendation' =>
        'Roll Transfer',

    'projected_gain' =>
        1.4,

    'confidence' =>
        0.67
];


$decisionOutput = [

    'status' =>
        'success',

    'message' =>
        'Gameweek decision generated successfully.',

    'overall_action' =>
        'Hold',

    'formation' =>
        '3-4-3',

    'starting_xi_score' =>
        71.25,

    'bench_score' =>
        18.5,

    'starting_xi' =>
        $startingXI,

    'bench' =>
        $bench,

    'captain' =>
        $captainOutput[
            'captain'
        ],

    'vice_captain' =>
        $captainOutput[
            'vice_captain'
        ],

    'transfer_advice' =>
        $transferOutput,

    'squad_risks' =>
        [],

    'key_insights' => [

        'Synthetic existing decision evidence.'
    ]
];


$gameweekDecisionResult = [

    'status' =>
        'success',

    'overall_action' =>
        'Hold',

    'gameweek' =>
        $gameweekOutput,

    'captaincy' =>
        $captainOutput,

    'transfers' =>
        $transferOutput,

    'decision' =>
        $decisionOutput
];


/*
 * ============================================================
 * EXISTING CHIP INTELLIGENCE OUTPUT
 * ============================================================
 */

$chipRecommendations = [

    'Wildcard' => [

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.62,

        'explanation' =>
            'Existing Wildcard decision.'
    ],

    'Free Hit' => [

        'recommendation' =>
            'Consider',

        'confidence' =>
            0.74,

        'explanation' =>
            'Existing Free Hit decision.'
    ],

    'Bench Boost' => [

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.81,

        'explanation' =>
            'Existing Bench Boost decision.'
    ],

    'Triple Captain' => [

        'recommendation' =>
            'Consider',

        'confidence' =>
            0.69,

        'explanation' =>
            'Existing Triple Captain decision.'
    ]
];


/*
 * ============================================================
 * SERVICE
 * ============================================================
 */

$repository =
    new RecommendationSnapshotRepository(
        $db
    );


$service =
    new RecommendationSnapshotCaptureService(
        $repository
    );


/*
 * ============================================================
 * A. CAPTURE EXISTING INTELLIGENCE
 * ============================================================
 */

captureServiceSection(
    'A. Capture Existing Intelligence'
);


$captureResult =
    $service->capture(
        $gameweekId,
        $entryId,
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $gameweekDecisionResult,
        $chipRecommendations
    );


captureServiceAssert(
    $captureResult === true,
    'A complete recommendation snapshot is captured.'
);


/*
 * ============================================================
 * B. SNAPSHOT IS PERSISTED
 * ============================================================
 */

captureServiceSection(
    'B. Snapshot Is Persisted'
);


$stored =
    $repository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


captureServiceAssert(
    is_array(
        $stored
    ),
    'Captured recommendation snapshot is persisted.'
);


captureServiceAssert(
    (
        $stored[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    $gameweekId,
    'Captured snapshot preserves the local gameweek ID.'
);


captureServiceAssert(
    (
        $stored[
            'entry_id'
        ]
        ?? null
    )
    ===
    $entryId,
    'Captured snapshot preserves the FPL entry ID.'
);


/*
 * ============================================================
 * C. PLAYER PROJECTIONS ARE PRESERVED
 * ============================================================
 */

captureServiceSection(
    'C. Player Projections Are Preserved'
);


captureServiceAssert(
    (
        $stored[
            'player_projections'
        ]
        ?? null
    )
    ===
    $playerProjections,
    'Existing player projection evidence is preserved unchanged.'
);


/*
 * ============================================================
 * D. STARTING XI IS EXTRACTED FROM EXISTING GAMEWEEK OUTPUT
 * ============================================================
 */

captureServiceSection(
    'D. Starting XI Is Preserved'
);


captureServiceAssert(
    (
        $stored[
            'starting_xi'
        ]
        ?? null
    )
    ===
    $startingXI,
    'Starting XI is preserved from existing Gameweek Intelligence.'
);


/*
 * ============================================================
 * E. CAPTAINCY IS PRESERVED
 * ============================================================
 */

captureServiceSection(
    'E. Captain Recommendation Is Preserved'
);


captureServiceAssert(
    (
        $stored[
            'captain_recommendation'
        ]
        ?? null
    )
    ===
    $captainOutput,
    'Existing Captain Intelligence output is preserved unchanged.'
);


/*
 * ============================================================
 * F. TRANSFER INTELLIGENCE IS PRESERVED
 * ============================================================
 */

captureServiceSection(
    'F. Transfer Recommendations Are Preserved'
);


captureServiceAssert(
    (
        $stored[
            'transfer_recommendations'
        ]
        ?? null
    )
    ===
    $transferOutput,
    'Existing Transfer Intelligence output is preserved unchanged.'
);


/*
 * ============================================================
 * G. GAMEWEEK DECISION IS PRESERVED
 * ============================================================
 */

captureServiceSection(
    'G. Gameweek Decision Is Preserved'
);


captureServiceAssert(
    (
        $stored[
            'gameweek_decision'
        ]
        ?? null
    )
    ===
    $decisionOutput,
    'Existing Gameweek Decision output is preserved unchanged.'
);


/*
 * ============================================================
 * H. CHIP INTELLIGENCE IS PRESERVED
 * ============================================================
 */

captureServiceSection(
    'H. Chip Recommendations Are Preserved'
);


captureServiceAssert(
    (
        $stored[
            'chip_recommendations'
        ]
        ?? null
    )
    ===
    $chipRecommendations,
    'Existing Chip Intelligence output is preserved unchanged.'
);


/*
 * ============================================================
 * I. CAPTURE METADATA IS PRESERVED
 * ============================================================
 */

captureServiceSection(
    'I. Capture Metadata Is Preserved'
);


captureServiceAssert(
    (
        $stored[
            'captured_at'
        ]
        ?? null
    )
    ===
    $capturedAt,
    'Capture timestamp is preserved.'
);


captureServiceAssert(
    (
        $stored[
            'deadline_time'
        ]
        ?? null
    )
    ===
    $deadlineTime,
    'Deadline timestamp is preserved.'
);


/*
 * ============================================================
 * J. DUPLICATE CAPTURE DOES NOT OVERWRITE HISTORY
 * ============================================================
 */

captureServiceSection(
    'J. Duplicate Capture Does Not Overwrite History'
);


$replacementProjections = [

    [
        'player_id' =>
            999,

        'projected_points' =>
            99.0
    ]
];


$duplicateResult =
    $service->capture(
        $gameweekId,
        $duplicateEntryId,
        date(
            'Y-m-d H:i:s',
            $deadlineTimestamp
            -
            1800
        ),
        $deadlineTime,
        $replacementProjections,
        $gameweekDecisionResult,
        $chipRecommendations
    );


captureServiceAssert(
    $duplicateResult === false,
    'A duplicate entry/gameweek capture is rejected.'
);


$storedAfterDuplicate =
    $repository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


captureServiceAssert(
    (
        $storedAfterDuplicate[
            'player_projections'
        ]
        ?? null
    )
    ===
    $playerProjections,
    'Duplicate capture does not replace original projection evidence.'
);


captureServiceAssert(
    (
        $storedAfterDuplicate[
            'captured_at'
        ]
        ?? null
    )
    ===
    $capturedAt,
    'Duplicate capture does not replace the original capture timestamp.'
);


/*
 * ============================================================
 * K. INVALID GAMEWEEK DECISION RESULT IS REJECTED
 * ============================================================
 */

captureServiceSection(
    'K. Invalid Gameweek Decision Result Is Rejected'
);


$invalidDecisionRejected =
    false;


try {

    $service->capture(
        $gameweekId,
        $entryId + 1,
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        [
            'status' =>
                'error'
        ],
        $chipRecommendations
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidDecisionRejected =
        true;
}


captureServiceAssert(
    $invalidDecisionRejected,
    'An unsuccessful Gameweek Decision result is rejected.'
);


/*
 * ============================================================
 * L. MISSING STARTING XI IS REJECTED
 * ============================================================
 */

captureServiceSection(
    'L. Missing Starting XI Is Rejected'
);


$missingStartingXIRejected =
    false;


try {

    $invalidGameweekDecision =
        $gameweekDecisionResult;


    $invalidGameweekDecision[
        'gameweek'
    ] = [

        'status' =>
            'success'
    ];


    $service->capture(
        $gameweekId,
        $entryId + 2,
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $invalidGameweekDecision,
        $chipRecommendations
    );

} catch (
    InvalidArgumentException $exception
) {

    $missingStartingXIRejected =
        true;
}


captureServiceAssert(
    $missingStartingXIRejected,
    'A Gameweek result without a Starting XI is rejected.'
);


/*
 * ============================================================
 * M. MISSING CAPTAINCY OUTPUT IS REJECTED
 * ============================================================
 */

captureServiceSection(
    'M. Missing Captaincy Output Is Rejected'
);


$missingCaptaincyRejected =
    false;


try {

    $invalidGameweekDecision =
        $gameweekDecisionResult;


    $invalidGameweekDecision[
        'captaincy'
    ] =
        null;


    $service->capture(
        $gameweekId,
        $entryId + 3,
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $invalidGameweekDecision,
        $chipRecommendations
    );

} catch (
    InvalidArgumentException $exception
) {

    $missingCaptaincyRejected =
        true;
}


captureServiceAssert(
    $missingCaptaincyRejected,
    'Missing Captain Intelligence output is rejected.'
);


/*
 * ============================================================
 * N. MISSING TRANSFER OUTPUT IS REJECTED
 * ============================================================
 */

captureServiceSection(
    'N. Missing Transfer Output Is Rejected'
);


$missingTransfersRejected =
    false;


try {

    $invalidGameweekDecision =
        $gameweekDecisionResult;


    $invalidGameweekDecision[
        'transfers'
    ] =
        null;


    $service->capture(
        $gameweekId,
        $entryId + 4,
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $invalidGameweekDecision,
        $chipRecommendations
    );

} catch (
    InvalidArgumentException $exception
) {

    $missingTransfersRejected =
        true;
}


captureServiceAssert(
    $missingTransfersRejected,
    'Missing Transfer Intelligence output is rejected.'
);


/*
 * ============================================================
 * O. MISSING GAMEWEEK DECISION OUTPUT IS REJECTED
 * ============================================================
 */

captureServiceSection(
    'O. Missing Gameweek Decision Output Is Rejected'
);


$missingDecisionRejected =
    false;


try {

    $invalidGameweekDecision =
        $gameweekDecisionResult;


    $invalidGameweekDecision[
        'decision'
    ] =
        null;


    $service->capture(
        $gameweekId,
        $entryId + 5,
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $invalidGameweekDecision,
        $chipRecommendations
    );

} catch (
    InvalidArgumentException $exception
) {

    $missingDecisionRejected =
        true;
}


captureServiceAssert(
    $missingDecisionRejected,
    'Missing Gameweek Decision output is rejected.'
);


/*
 * ============================================================
 * P. MISSING PLAYER PROJECTION EVIDENCE IS REJECTED
 * ============================================================
 */

captureServiceSection(
    'P. Missing Player Projection Evidence Is Rejected'
);


$missingProjectionsRejected =
    false;


try {

    $service->capture(
        $gameweekId,
        $entryId + 6,
        $capturedAt,
        $deadlineTime,
        [],
        $gameweekDecisionResult,
        $chipRecommendations
    );

} catch (
    InvalidArgumentException $exception
) {

    $missingProjectionsRejected =
        true;
}


captureServiceAssert(
    $missingProjectionsRejected,
    'A snapshot without player projection evidence is rejected.'
);


/*
 * ============================================================
 * Q. MISSING CHIP EVIDENCE IS REJECTED
 * ============================================================
 */

captureServiceSection(
    'Q. Missing Chip Evidence Is Rejected'
);


$missingChipsRejected =
    false;


try {

    $service->capture(
        $gameweekId,
        $entryId + 7,
        $capturedAt,
        $deadlineTime,
        $playerProjections,
        $gameweekDecisionResult,
        []
    );

} catch (
    InvalidArgumentException $exception
) {

    $missingChipsRejected =
        true;
}


captureServiceAssert(
    $missingChipsRejected,
    'A snapshot without Chip Intelligence evidence is rejected.'
);


/*
 * ============================================================
 * CLEANUP
 * ============================================================
 */

$cleanupStatement->execute(
    [
        'entry_id' =>
            $entryId,

        'gameweek_id' =>
            $gameweekId
    ]
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

captureServiceSection(
    'Recommendation Snapshot Capture Service Test Summary'
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