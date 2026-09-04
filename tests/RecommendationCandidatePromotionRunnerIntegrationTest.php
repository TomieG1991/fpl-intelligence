<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION CANDIDATE PROMOTION RUNNER INTEGRATION TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * This test proves the real persistence path:
 *
 * recommendation_candidates
 *      ↓
 * RecommendationCandidateRepository
 *      ↓
 * RecommendationCandidatePromotionRunner
 *      ↓
 * RecommendationCandidatePromotionService
 *      ↓
 * RecommendationSnapshotRepository
 *      ↓
 * recommendation_snapshots
 *
 * The test uses:
 *
 * - real PDO/MySQL persistence
 * - a real local gameweek
 * - a synthetic FPL entry ID
 * - controlled recommendation evidence
 *
 * It must prove:
 *
 * 1. candidate is not promoted before its preserved deadline
 * 2. candidate is promoted exactly at its preserved deadline
 * 3. all recommendation evidence survives promotion exactly
 * 4. repeated promotion is idempotent
 * 5. a later mutable candidate cannot rewrite an immutable snapshot
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


function promotionRunnerIntegrationAssert(
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


function promotionRunnerIntegrationSection(
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
    $database->getConnection();


/*
 * ============================================================
 * CONTROLLED TEST IDENTITY
 * ============================================================
 *
 * Synthetic entry ID only.
 *
 * Do not use a real manager entry ID in automated tests.
 */

$entryId =
    935007001;


/*
 * ============================================================
 * FIND A REAL GAMEWEEK
 * ============================================================
 *
 * We only need a valid local gameweek foreign key.
 *
 * Promotion readiness itself is controlled by the deadline
 * preserved inside our synthetic recommendation candidate.
 */

$gameweekStatement =
    $db->query(
        "
            SELECT
                id,
                fpl_gameweek_id
            FROM
                gameweeks
            ORDER BY
                id ASC
            LIMIT 1
        "
    );


$gameweekRow =
    $gameweekStatement->fetch(
        PDO::FETCH_ASSOC
    );


if (
    $gameweekRow === false
) {

    die(
        'No local gameweek exists for promotion integration test.'
    );
}


/*
 * gameweek_id in recommendation history always refers to the
 * local gameweeks.id primary key.
 *
 * fpl_gameweek_id is retained separately here only so the
 * integration test makes that distinction explicit.
 */

$gameweekId =
    (int) $gameweekRow[
        'id'
    ];


$fplGameweekId =
    (int) $gameweekRow[
        'fpl_gameweek_id'
    ];


/*
 * ============================================================
 * CONTROLLED TIMESTAMPS
 * ============================================================
 */

$generatedAt =
    '2026-08-21 17:30:00';


$deadlineTime =
    '2026-08-21 18:30:00';


$beforeDeadline =
    '2026-08-21 18:29:59';


$atDeadline =
    '2026-08-21 18:30:00';


$newerGeneratedAt =
    '2026-08-21 18:15:00';


/*
 * ============================================================
 * CONTROLLED RECOMMENDATION EVIDENCE
 * ============================================================
 *
 * Values are deliberately distinctive so exact persistence
 * and promotion can be verified.
 */

$playerProjections = [

    [
        'player_id' =>
            7001,

        'name' =>
            'Controlled Goalkeeper',

        'projected_points' =>
            5.75,

        'projected_minutes' =>
            90.0,

        'projection_confidence' =>
            0.82
    ],

    [
        'player_id' =>
            7002,

        'name' =>
            'Controlled Forward',

        'projected_points' =>
            9.25,

        'projected_minutes' =>
            84.0,

        'projection_confidence' =>
            0.91
    ]
];


$startingXI = [

    'formation' =>
        '3-4-3',

    'players' => [

        [
            'player_id' =>
                7001,

            'position' =>
                'GK'
        ],

        [
            'player_id' =>
                7002,

            'position' =>
                'FWD'
        ]
    ],

    'starting_xi_score' =>
        67.50
];


$captainRecommendation = [

    'captain' => [

        'player_id' =>
            7002,

        'name' =>
            'Controlled Forward',

        'projected_points' =>
            9.25
    ],

    'vice_captain' => [

        'player_id' =>
            7001,

        'name' =>
            'Controlled Goalkeeper'
    ],

    'confidence' =>
        0.91
];


$transferRecommendations = [

    'recommendation' =>
        'Make Transfer',

    'transfers' => [

        [
            'out_player_id' =>
                7101,

            'in_player_id' =>
                7102,

            'projected_gain' =>
                3.25
        ]
    ]
];


$gameweekDecision = [

    'status' =>
        'success',

    'overall_action' =>
        'Make Transfer',

    'formation' =>
        '3-4-3',

    'starting_xi_score' =>
        67.50,

    'bench_score' =>
        12.25,

    'key_insights' => [
        'Controlled integration evidence.'
    ]
];


$chipRecommendations = [

    'wildcard' => [
        'recommendation' =>
            'Hold',

        'confidence' =>
            0.75
    ],

    'free_hit' => [
        'recommendation' =>
            'Hold',

        'confidence' =>
            0.80
    ],

    'bench_boost' => [
        'recommendation' =>
            'Consider',

        'confidence' =>
            0.70
    ],

    'triple_captain' => [
        'recommendation' =>
            'Use',

        'confidence' =>
            0.91
    ]
];


/*
 * ============================================================
 * LATER MUTABLE EVIDENCE
 * ============================================================
 *
 * This deliberately differs from the original recommendation.
 *
 * It will be written to recommendation_candidates AFTER the
 * immutable snapshot exists.
 *
 * The snapshot must remain unchanged.
 */

$laterPlayerProjections =
    $playerProjections;


$laterPlayerProjections[
    1
][
    'projected_points'
] =
    15.75;


$laterStartingXI =
    $startingXI;


$laterStartingXI[
    'formation'
] =
    '4-4-2';


$laterCaptainRecommendation =
    $captainRecommendation;


$laterCaptainRecommendation[
    'captain'
][
    'player_id'
] =
    7999;


$laterTransferRecommendations = [

    'recommendation' =>
        'Hold',

    'transfers' =>
        []
];


$laterGameweekDecision =
    $gameweekDecision;


$laterGameweekDecision[
    'overall_action'
] =
    'Hold';


$laterChipRecommendations =
    $chipRecommendations;


$laterChipRecommendations[
    'triple_captain'
][
    'recommendation'
] =
    'Hold';


/*
 * ============================================================
 * CLEANUP STATEMENTS
 * ============================================================
 */

$deleteSnapshotStatement =
    $db->prepare(
        "
            DELETE FROM
                recommendation_snapshots
            WHERE
                entry_id = :entry_id
            AND
                gameweek_id = :gameweek_id
        "
    );


$deleteCandidateStatement =
    $db->prepare(
        "
            DELETE FROM
                recommendation_candidates
            WHERE
                entry_id = :entry_id
            AND
                gameweek_id = :gameweek_id
        "
    );


$cleanupParameters = [

    'entry_id' =>
        $entryId,

    'gameweek_id' =>
        $gameweekId
];


/*
 * ============================================================
 * INITIAL CLEANUP
 * ============================================================
 *
 * Delete snapshot first because recommendation history may
 * reference the same gameweek.
 */

$deleteSnapshotStatement->execute(
    $cleanupParameters
);


$deleteCandidateStatement->execute(
    $cleanupParameters
);


/*
 * ============================================================
 * REAL PRODUCTION OBJECTS
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


$promotionService =
    new RecommendationCandidatePromotionService(
        $candidateRepository,
        $snapshotRepository
    );


$runner =
    new RecommendationCandidatePromotionRunner(
        $candidateRepository,
        $promotionService
    );


/*
 * ============================================================
 * CREATE CONTROLLED CANDIDATE
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


/*
 * ============================================================
 * A. STORE MUTABLE CANDIDATE
 * ============================================================
 */

promotionRunnerIntegrationSection(
    'A. Store Mutable Candidate'
);


$candidateStored =
    $candidateRepository
        ->saveLatest(
            $gameweekId,
            $candidate
        );


promotionRunnerIntegrationAssert(
    $candidateStored,
    'Controlled recommendation candidate is stored.'
);


$storedCandidate =
    $candidateRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


promotionRunnerIntegrationAssert(
    is_array(
        $storedCandidate
    ),
    'Stored recommendation candidate can be retrieved.'
);


promotionRunnerIntegrationAssert(
    (
        $storedCandidate[
            'deadline_time'
        ]
        ?? null
    )
    ===
    $deadlineTime,
    'Candidate preserves controlled deadline.'
);


promotionRunnerIntegrationAssert(
    (
        $storedCandidate[
            'player_projections'
        ]
        ?? null
    )
    ===
    $playerProjections,
    'Candidate preserves controlled projection evidence.'
);


/*
 * ============================================================
 * B. BEFORE DEADLINE
 * ============================================================
 */

promotionRunnerIntegrationSection(
    'B. Before Preserved Deadline'
);


$beforeResult =
    $runner
        ->run(
            $beforeDeadline
        );


promotionRunnerIntegrationAssert(
    (
        $beforeResult[
            'ready'
        ]
        ?? null
    )
    ===
    0,
    'Candidate is not ready before preserved deadline.'
);


promotionRunnerIntegrationAssert(
    (
        $beforeResult[
            'promoted'
        ]
        ?? null
    )
    ===
    0,
    'Candidate is not promoted before preserved deadline.'
);


$beforeSnapshot =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


promotionRunnerIntegrationAssert(
    $beforeSnapshot === null,
    'No immutable snapshot exists before preserved deadline.'
);


/*
 * ============================================================
 * C. EXACTLY AT DEADLINE
 * ============================================================
 */

promotionRunnerIntegrationSection(
    'C. Exactly At Preserved Deadline'
);


$deadlineResult =
    $runner
        ->run(
            $atDeadline
        );


promotionRunnerIntegrationAssert(
    (
        $deadlineResult[
            'ready'
        ]
        ?? null
    )
    ===
    1,
    'Candidate is ready exactly at preserved deadline.'
);


promotionRunnerIntegrationAssert(
    (
        $deadlineResult[
            'promoted'
        ]
        ?? null
    )
    ===
    1,
    'Candidate is promoted exactly at preserved deadline.'
);


promotionRunnerIntegrationAssert(
    (
        $deadlineResult[
            'unchanged'
        ]
        ?? null
    )
    ===
    0,
    'First promotion is not reported as unchanged.'
);


$promotedSnapshot =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


promotionRunnerIntegrationAssert(
    is_array(
        $promotedSnapshot
    ),
    'Immutable recommendation snapshot exists after promotion.'
);


/*
 * ============================================================
 * D. EXACT SNAPSHOT EVIDENCE
 * ============================================================
 */

promotionRunnerIntegrationSection(
    'D. Exact Snapshot Evidence'
);


promotionRunnerIntegrationAssert(
    (
        $promotedSnapshot[
            'entry_id'
        ]
        ?? null
    )
    ===
    $entryId,
    'Snapshot preserves entry identity.'
);


promotionRunnerIntegrationAssert(
    (
        $promotedSnapshot[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    $gameweekId,
    'Snapshot preserves local gameweek identity.'
);


promotionRunnerIntegrationAssert(
    (
        $promotedSnapshot[
            'captured_at'
        ]
        ?? null
    )
    ===
    $generatedAt,
    'Candidate generated timestamp becomes snapshot captured timestamp.'
);


promotionRunnerIntegrationAssert(
    (
        $promotedSnapshot[
            'deadline_time'
        ]
        ?? null
    )
    ===
    $deadlineTime,
    'Snapshot preserves candidate deadline.'
);


promotionRunnerIntegrationAssert(
    (
        $promotedSnapshot[
            'player_projections'
        ]
        ?? null
    )
    ===
    $playerProjections,
    'Snapshot preserves player projection evidence exactly.'
);


promotionRunnerIntegrationAssert(
    (
        $promotedSnapshot[
            'starting_xi'
        ]
        ?? null
    )
    ===
    $startingXI,
    'Snapshot preserves Starting XI evidence exactly.'
);


promotionRunnerIntegrationAssert(
    (
        $promotedSnapshot[
            'captain_recommendation'
        ]
        ?? null
    )
    ===
    $captainRecommendation,
    'Snapshot preserves captain recommendation exactly.'
);


promotionRunnerIntegrationAssert(
    (
        $promotedSnapshot[
            'transfer_recommendations'
        ]
        ?? null
    )
    ===
    $transferRecommendations,
    'Snapshot preserves transfer recommendations exactly.'
);


promotionRunnerIntegrationAssert(
    (
        $promotedSnapshot[
            'gameweek_decision'
        ]
        ?? null
    )
    ===
    $gameweekDecision,
    'Snapshot preserves Gameweek Decision exactly.'
);


promotionRunnerIntegrationAssert(
    (
        $promotedSnapshot[
            'chip_recommendations'
        ]
        ?? null
    )
    ===
    $chipRecommendations,
    'Snapshot preserves chip recommendations exactly.'
);


/*
 * ============================================================
 * E. REPEATED PROMOTION IS IDEMPOTENT
 * ============================================================
 */

promotionRunnerIntegrationSection(
    'E. Repeated Promotion Is Idempotent'
);


$repeatResult =
    $runner
        ->run(
            $atDeadline
        );


promotionRunnerIntegrationAssert(
    (
        $repeatResult[
            'ready'
        ]
        ?? null
    )
    ===
    1,
    'Candidate remains discoverable after first promotion.'
);


promotionRunnerIntegrationAssert(
    (
        $repeatResult[
            'promoted'
        ]
        ?? null
    )
    ===
    0,
    'Repeated run does not create another immutable snapshot.'
);


promotionRunnerIntegrationAssert(
    (
        $repeatResult[
            'unchanged'
        ]
        ?? null
    )
    ===
    1,
    'Repeated promotion is reported as unchanged.'
);


$repeatSnapshot =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


promotionRunnerIntegrationAssert(
    $repeatSnapshot
    ===
    $promotedSnapshot,
    'Repeated promotion leaves immutable snapshot unchanged.'
);


/*
 * ============================================================
 * F. LATER CANDIDATE MAY CHANGE
 * ============================================================
 */

promotionRunnerIntegrationSection(
    'F. Later Mutable Candidate May Change'
);


$laterCandidate =
    new RecommendationCandidate(
        $gameweekId,
        $entryId,
        $newerGeneratedAt,
        $deadlineTime,
        $laterPlayerProjections,
        $laterStartingXI,
        $laterCaptainRecommendation,
        $laterTransferRecommendations,
        $laterGameweekDecision,
        $laterChipRecommendations
    );


$laterCandidateStored =
    $candidateRepository
        ->saveLatest(
            $gameweekId,
            $laterCandidate
        );


promotionRunnerIntegrationAssert(
    $laterCandidateStored,
    'Strictly newer mutable candidate may replace staging evidence.'
);


$storedLaterCandidate =
    $candidateRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


promotionRunnerIntegrationAssert(
    (
        $storedLaterCandidate[
            'generated_at'
        ]
        ?? null
    )
    ===
    $newerGeneratedAt,
    'Mutable candidate now contains newer generated timestamp.'
);


promotionRunnerIntegrationAssert(
    (
        $storedLaterCandidate[
            'player_projections'
        ]
        ?? null
    )
    ===
    $laterPlayerProjections,
    'Mutable candidate now contains later projection evidence.'
);


promotionRunnerIntegrationAssert(
    (
        $storedLaterCandidate[
            'gameweek_decision'
        ]
        ?? null
    )
    ===
    $laterGameweekDecision,
    'Mutable candidate now contains later Gameweek Decision evidence.'
);


/*
 * ============================================================
 * G. IMMUTABLE SNAPSHOT CANNOT BE REWRITTEN
 * ============================================================
 */

promotionRunnerIntegrationSection(
    'G. Immutable Snapshot Cannot Be Rewritten'
);


$afterLaterCandidateResult =
    $runner
        ->run(
            '2026-08-21 19:00:00'
        );


promotionRunnerIntegrationAssert(
    (
        $afterLaterCandidateResult[
            'ready'
        ]
        ?? null
    )
    ===
    1,
    'Later mutable candidate remains promotion-ready.'
);


promotionRunnerIntegrationAssert(
    (
        $afterLaterCandidateResult[
            'promoted'
        ]
        ?? null
    )
    ===
    0,
    'Existing immutable snapshot prevents later candidate promotion.'
);


promotionRunnerIntegrationAssert(
    (
        $afterLaterCandidateResult[
            'unchanged'
        ]
        ?? null
    )
    ===
    1,
    'Later promotion attempt is reported as unchanged.'
);


$finalSnapshot =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


promotionRunnerIntegrationAssert(
    $finalSnapshot
    ===
    $promotedSnapshot,
    'Immutable snapshot remains byte-for-byte equivalent at repository contract level.'
);


promotionRunnerIntegrationAssert(
    (
        $finalSnapshot[
            'captured_at'
        ]
        ?? null
    )
    ===
    $generatedAt,
    'Immutable snapshot retains original pre-deadline captured timestamp.'
);


promotionRunnerIntegrationAssert(
    (
        $finalSnapshot[
            'player_projections'
        ]
        ?? null
    )
    ===
    $playerProjections,
    'Later mutable projections cannot rewrite historical snapshot projections.'
);


promotionRunnerIntegrationAssert(
    (
        $finalSnapshot[
            'starting_xi'
        ]
        ?? null
    )
    ===
    $startingXI,
    'Later mutable Starting XI cannot rewrite historical snapshot.'
);


promotionRunnerIntegrationAssert(
    (
        $finalSnapshot[
            'captain_recommendation'
        ]
        ?? null
    )
    ===
    $captainRecommendation,
    'Later mutable captain recommendation cannot rewrite historical snapshot.'
);


promotionRunnerIntegrationAssert(
    (
        $finalSnapshot[
            'transfer_recommendations'
        ]
        ?? null
    )
    ===
    $transferRecommendations,
    'Later mutable transfer recommendation cannot rewrite historical snapshot.'
);


promotionRunnerIntegrationAssert(
    (
        $finalSnapshot[
            'gameweek_decision'
        ]
        ?? null
    )
    ===
    $gameweekDecision,
    'Later mutable Gameweek Decision cannot rewrite historical snapshot.'
);


promotionRunnerIntegrationAssert(
    (
        $finalSnapshot[
            'chip_recommendations'
        ]
        ?? null
    )
    ===
    $chipRecommendations,
    'Later mutable chip recommendations cannot rewrite historical snapshot.'
);


/*
 * ============================================================
 * H. SNAPSHOT UNIQUENESS
 * ============================================================
 */

promotionRunnerIntegrationSection(
    'H. Snapshot Uniqueness'
);


$snapshotCountStatement =
    $db->prepare(
        "
            SELECT
                COUNT(*)
            FROM
                recommendation_snapshots
            WHERE
                entry_id = :entry_id
            AND
                gameweek_id = :gameweek_id
        "
    );


$snapshotCountStatement->execute(
    $cleanupParameters
);


$snapshotCount =
    (int) $snapshotCountStatement
        ->fetchColumn();


promotionRunnerIntegrationAssert(
    $snapshotCount === 1,
    'Exactly one immutable snapshot exists for controlled entry/gameweek.'
);


/*
 * ============================================================
 * CLEANUP
 * ============================================================
 */

$deleteSnapshotStatement->execute(
    $cleanupParameters
);


$deleteCandidateStatement->execute(
    $cleanupParameters
);


/*
 * ============================================================
 * I. CLEANUP VERIFICATION
 * ============================================================
 */

promotionRunnerIntegrationSection(
    'I. Cleanup Verification'
);


$cleanedCandidate =
    $candidateRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


$cleanedSnapshot =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


promotionRunnerIntegrationAssert(
    $cleanedCandidate === null,
    'Controlled recommendation candidate is removed after test.'
);


promotionRunnerIntegrationAssert(
    $cleanedSnapshot === null,
    'Controlled recommendation snapshot is removed after test.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

promotionRunnerIntegrationSection(
    'Recommendation Candidate Promotion Runner Integration Test Summary'
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