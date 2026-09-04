<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION CANDIDATE PROMOTION SERVICE TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * A recommendation candidate is mutable staging evidence.
 *
 * At the historical capture boundary, the latest candidate must
 * be copied into RecommendationSnapshot storage without
 * recalculating or changing any recommendation evidence.
 *
 * RecommendationSnapshot remains the immutable historical
 * record.
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


function candidatePromotionAssert(
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


function candidatePromotionSection(
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
 * FIND ONE REAL LOCAL GAMEWEEK
 * ============================================================
 */

$gameweekStatement =
    $db->query(
        "
        SELECT
            id,
            deadline_time
        FROM gameweeks
        WHERE deadline_time IS NOT NULL
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
    $gameweekRow === false
) {

    die(
        'RecommendationCandidatePromotionServiceTest requires '
        . 'a valid row in the gameweeks table.'
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


$deadlineTimestamp =
    strtotime(
        $deadlineTime
    );


if (
    $deadlineTimestamp === false
) {

    die(
        'RecommendationCandidatePromotionServiceTest could not '
        . 'parse the selected gameweek deadline.'
    );
}


/*
 * ============================================================
 * CONTROLLED ENTRY IDS
 * ============================================================
 */

$entryId =
    935004001;


$missingEntryId =
    935004002;


/*
 * ============================================================
 * CANDIDATE TIMESTAMP
 * ============================================================
 */

$generatedAt =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestamp
        -
        3600
    );


/*
 * ============================================================
 * CONTROLLED EVIDENCE
 * ============================================================
 */

$playerProjections = [

    [
        'player_id' =>
            101,

        'projected_points' =>
            8.75,

        'projected_minutes' =>
            82.0,

        'projection_confidence' =>
            0.81
    ],

    [
        'player_id' =>
            102,

        'projected_points' =>
            6.25,

        'projected_minutes' =>
            90.0,

        'projection_confidence' =>
            0.74
    ]
];


$startingXI = [

    [
        'player_id' =>
            101,

        'projected_points' =>
            8.75
    ],

    [
        'player_id' =>
            102,

        'projected_points' =>
            6.25
    ]
];


$captainRecommendation = [

    'status' =>
        'success',

    'captain' => [

        'player_id' =>
            101,

        'captain_score' =>
            72.5,

        'projected_points' =>
            8.75
    ],

    'vice_captain' => [

        'player_id' =>
            102,

        'captain_score' =>
            66.0,

        'projected_points' =>
            6.25
    ]
];


$transferRecommendations = [

    'status' =>
        'success',

    'recommendation' =>
        'Hold',

    'confidence' =>
        0.68
];


$gameweekDecision = [

    'status' =>
        'success',

    'overall_action' =>
        'Hold',

    'formation' =>
        '3-4-3',

    'starting_xi_score' =>
        71.5,

    'bench_score' =>
        14.25
];


$chipRecommendations = [

    'Wildcard' => [

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.62
    ],

    'Free Hit' => [

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.70
    ],

    'Bench Boost' => [

        'recommendation' =>
            'Consider',

        'confidence' =>
            0.64
    ],

    'Triple Captain' => [

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.76
    ]
];


/*
 * ============================================================
 * CLEAN CONTROLLED DATA
 * ============================================================
 */

$cleanupCandidates =
    $db->prepare(
        "
        DELETE FROM recommendation_candidates
        WHERE entry_id IN (
            :entry_id,
            :missing_entry_id
        )
        "
    );


$cleanupSnapshots =
    $db->prepare(
        "
        DELETE FROM recommendation_snapshots
        WHERE entry_id IN (
            :entry_id,
            :missing_entry_id
        )
        "
    );


$cleanupCandidates->execute(
    [
        'entry_id' =>
            $entryId,

        'missing_entry_id' =>
            $missingEntryId
    ]
);


$cleanupSnapshots->execute(
    [
        'entry_id' =>
            $entryId,

        'missing_entry_id' =>
            $missingEntryId
    ]
);


/*
 * ============================================================
 * REPOSITORIES
 * ============================================================
 */

$candidateRepository =
    new RecommendationCandidateRepository(
        $db
    );


$snapshotRepository =
    new RecommendationSnapshotRepository(
        $db
    );


/*
 * ============================================================
 * STORE CANDIDATE
 * ============================================================
 */

$candidate =
    new RecommendationCandidate(
        $gameweekId,
        $entryId,
        $generatedAt,
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    );


$candidateStored =
    $candidateRepository
        ->saveLatest(
            $gameweekId,
            $candidate
        );


if (
    $candidateStored !== true
) {

    die(
        'RecommendationCandidatePromotionServiceTest could not '
        . 'store its controlled candidate.'
    );
}


/*
 * ============================================================
 * SERVICE
 * ============================================================
 */

$service =
    new RecommendationCandidatePromotionService(
        $candidateRepository,
        $snapshotRepository
    );


/*
 * ============================================================
 * A. PROMOTE EXISTING CANDIDATE
 * ============================================================
 */

candidatePromotionSection(
    'A. Promote Existing Candidate'
);


$promotionResult =
    $service
        ->promote(
            $entryId,
            $gameweekId
        );


candidatePromotionAssert(
    $promotionResult === true,
    'Existing recommendation candidate is promoted.'
);


/*
 * ============================================================
 * B. SNAPSHOT WAS CREATED
 * ============================================================
 */

candidatePromotionSection(
    'B. Snapshot Created'
);


$snapshot =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


candidatePromotionAssert(
    is_array(
        $snapshot
    ),
    'Promotion creates an immutable recommendation snapshot.'
);


candidatePromotionAssert(
    (
        $snapshot[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    $gameweekId,
    'Snapshot preserves local gameweek ID.'
);


candidatePromotionAssert(
    (
        $snapshot[
            'entry_id'
        ]
        ?? null
    )
    ===
    $entryId,
    'Snapshot preserves FPL entry ID.'
);


/*
 * ============================================================
 * C. CAPTURE TIME IS CANDIDATE GENERATION TIME
 * ============================================================
 */

candidatePromotionSection(
    'C. Historical Capture Timestamp'
);


candidatePromotionAssert(
    (
        $snapshot[
            'captured_at'
        ]
        ?? null
    )
    ===
    $generatedAt,
    'Snapshot capture time preserves when the recommendation was generated.'
);


candidatePromotionAssert(
    (
        $snapshot[
            'deadline_time'
        ]
        ?? null
    )
    ===
    $deadlineTime,
    'Snapshot preserves the candidate deadline.'
);


/*
 * ============================================================
 * D. PLAYER PROJECTIONS PRESERVED EXACTLY
 * ============================================================
 */

candidatePromotionSection(
    'D. Player Projection Evidence'
);


candidatePromotionAssert(
    (
        $snapshot[
            'player_projections'
        ]
        ?? null
    )
    ===
    $playerProjections,
    'Player projection evidence is promoted without recalculation.'
);


/*
 * ============================================================
 * E. STARTING XI PRESERVED EXACTLY
 * ============================================================
 */

candidatePromotionSection(
    'E. Starting XI Evidence'
);


candidatePromotionAssert(
    (
        $snapshot[
            'starting_xi'
        ]
        ?? null
    )
    ===
    $startingXI,
    'Starting XI evidence is promoted unchanged.'
);


/*
 * ============================================================
 * F. CAPTAIN RECOMMENDATION PRESERVED EXACTLY
 * ============================================================
 */

candidatePromotionSection(
    'F. Captain Recommendation Evidence'
);


candidatePromotionAssert(
    (
        $snapshot[
            'captain_recommendation'
        ]
        ?? null
    )
    ===
    $captainRecommendation,
    'Captain recommendation is promoted unchanged.'
);


/*
 * ============================================================
 * G. TRANSFER RECOMMENDATIONS PRESERVED EXACTLY
 * ============================================================
 */

candidatePromotionSection(
    'G. Transfer Recommendation Evidence'
);


candidatePromotionAssert(
    (
        $snapshot[
            'transfer_recommendations'
        ]
        ?? null
    )
    ===
    $transferRecommendations,
    'Transfer recommendation evidence is promoted unchanged.'
);


/*
 * ============================================================
 * H. GAMEWEEK DECISION PRESERVED EXACTLY
 * ============================================================
 */

candidatePromotionSection(
    'H. Gameweek Decision Evidence'
);


candidatePromotionAssert(
    (
        $snapshot[
            'gameweek_decision'
        ]
        ?? null
    )
    ===
    $gameweekDecision,
    'Gameweek Decision evidence is promoted unchanged.'
);


/*
 * ============================================================
 * I. CHIP RECOMMENDATIONS PRESERVED EXACTLY
 * ============================================================
 */

candidatePromotionSection(
    'I. Chip Recommendation Evidence'
);


candidatePromotionAssert(
    (
        $snapshot[
            'chip_recommendations'
        ]
        ?? null
    )
    ===
    $chipRecommendations,
    'Chip Intelligence evidence is promoted unchanged.'
);


/*
 * ============================================================
 * J. CANDIDATE REMAINS AVAILABLE
 * ============================================================
 */

candidatePromotionSection(
    'J. Candidate Retention'
);


$candidateAfterPromotion =
    $candidateRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


candidatePromotionAssert(
    is_array(
        $candidateAfterPromotion
    ),
    'Promotion does not delete candidate staging evidence.'
);


/*
 * ============================================================
 * K. DUPLICATE PROMOTION CANNOT REWRITE SNAPSHOT
 * ============================================================
 */

candidatePromotionSection(
    'K. Snapshot Immutability'
);


$duplicatePromotion =
    $service
        ->promote(
            $entryId,
            $gameweekId
        );


candidatePromotionAssert(
    $duplicatePromotion === false,
    'Candidate cannot be promoted over an existing snapshot.'
);


$snapshotAfterDuplicate =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


candidatePromotionAssert(
    (
        $snapshotAfterDuplicate[
            'captured_at'
        ]
        ?? null
    )
    ===
    $generatedAt,
    'Duplicate promotion leaves original snapshot timestamp unchanged.'
);


candidatePromotionAssert(
    (
        $snapshotAfterDuplicate[
            'player_projections'
        ]
        ?? null
    )
    ===
    $playerProjections,
    'Duplicate promotion leaves original snapshot evidence unchanged.'
);


/*
 * ============================================================
 * L. MISSING CANDIDATE
 * ============================================================
 */

candidatePromotionSection(
    'L. Missing Candidate'
);


$missingPromotion =
    $service
        ->promote(
            $missingEntryId,
            $gameweekId
        );


candidatePromotionAssert(
    $missingPromotion === false,
    'Missing recommendation candidate is not promoted.'
);


$missingSnapshot =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $missingEntryId,
            $gameweekId
        );


candidatePromotionAssert(
    $missingSnapshot === null,
    'Missing candidate does not create a snapshot.'
);


/*
 * ============================================================
 * CLEANUP
 * ============================================================
 */

$cleanupSnapshots->execute(
    [
        'entry_id' =>
            $entryId,

        'missing_entry_id' =>
            $missingEntryId
    ]
);


$cleanupCandidates->execute(
    [
        'entry_id' =>
            $entryId,

        'missing_entry_id' =>
            $missingEntryId
    ]
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

candidatePromotionSection(
    'Recommendation Candidate Promotion Service Test Summary'
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