<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION CANDIDATE TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * A RecommendationCandidate represents the latest recommendation
 * evidence known before a gameweek deadline.
 *
 * Unlike RecommendationSnapshot, a candidate is NOT historical
 * evidence yet.
 *
 * It may be replaced by a later candidate before the deadline.
 *
 * The final immutable RecommendationSnapshot remains owned by
 * the existing snapshot capture architecture.
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


function recommendationCandidateAssert(
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


function recommendationCandidateSection(
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
 * VALID EVIDENCE
 * ============================================================
 */

$gameweekId =
    7;


$entryId =
    935002001;


$generatedAt =
    '2026-09-04 10:00:00';


$deadlineTime =
    '2026-09-05 11:00:00';


$playerProjections = [

    [
        'player_id' =>
            101,

        'projected_points' =>
            7.25,

        'projected_minutes' =>
            82.0,

        'projection_confidence' =>
            0.78
    ]
];


$startingXI = [

    [
        'player_id' =>
            101,

        'projected_points' =>
            7.25
    ],

    [
        'player_id' =>
            102,

        'projected_points' =>
            6.8
    ]
];


$captainRecommendation = [

    'status' =>
        'success',

    'captain' => [

        'player_id' =>
            101,

        'captain_score' =>
            72.5
    ],

    'vice_captain' => [

        'player_id' =>
            102,

        'captain_score' =>
            68.0
    ]
];


$transferRecommendations = [

    'status' =>
        'success',

    'recommendation' =>
        'Roll Transfer',

    'projected_gain' =>
        1.4
];


$gameweekDecision = [

    'status' =>
        'success',

    'overall_action' =>
        'Hold',

    'formation' =>
        '3-4-3'
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
            'Consider',

        'confidence' =>
            0.74
    ],

    'Bench Boost' => [

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.81
    ],

    'Triple Captain' => [

        'recommendation' =>
            'Consider',

        'confidence' =>
            0.69
    ]
];


/*
 * ============================================================
 * A. VALID CANDIDATE CONSTRUCTION
 * ============================================================
 */

recommendationCandidateSection(
    'A. Valid Candidate Construction'
);


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


recommendationCandidateAssert(
    $candidate instanceof RecommendationCandidate,
    'A valid recommendation candidate can be constructed.'
);


/*
 * ============================================================
 * B. GAMEWEEK IDENTITY
 * ============================================================
 */

recommendationCandidateSection(
    'B. Gameweek Identity'
);


recommendationCandidateAssert(
    $candidate->getGameweek() === $gameweekId,
    'Candidate exposes its local gameweek ID.'
);


$invalidGameweekRejected =
    false;


try {

    new RecommendationCandidate(
        0,
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

} catch (
    InvalidArgumentException $exception
) {

    $invalidGameweekRejected =
        true;
}


recommendationCandidateAssert(
    $invalidGameweekRejected,
    'Candidate rejects a non-positive gameweek ID.'
);


/*
 * ============================================================
 * C. ENTRY IDENTITY
 * ============================================================
 */

recommendationCandidateSection(
    'C. Entry Identity'
);


recommendationCandidateAssert(
    $candidate->getEntryId() === $entryId,
    'Candidate exposes its FPL entry ID.'
);


$invalidEntryRejected =
    false;


try {

    new RecommendationCandidate(
        $gameweekId,
        0,
        $generatedAt,
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidEntryRejected =
        true;
}


recommendationCandidateAssert(
    $invalidEntryRejected,
    'Candidate rejects a non-positive FPL entry ID.'
);


/*
 * ============================================================
 * D. GENERATED TIMESTAMP
 * ============================================================
 */

recommendationCandidateSection(
    'D. Generated Timestamp'
);


recommendationCandidateAssert(
    $candidate->getGeneratedAt() === $generatedAt,
    'Candidate exposes when its recommendation was generated.'
);


$invalidGeneratedAtRejected =
    false;


try {

    new RecommendationCandidate(
        $gameweekId,
        $entryId,
        'not-a-date',
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidGeneratedAtRejected =
        true;
}


recommendationCandidateAssert(
    $invalidGeneratedAtRejected,
    'Candidate rejects an invalid generated timestamp.'
);


/*
 * ============================================================
 * E. DEADLINE TIMESTAMP
 * ============================================================
 */

recommendationCandidateSection(
    'E. Deadline Timestamp'
);


recommendationCandidateAssert(
    $candidate->getDeadlineTime() === $deadlineTime,
    'Candidate exposes its gameweek deadline.'
);


$invalidDeadlineRejected =
    false;


try {

    new RecommendationCandidate(
        $gameweekId,
        $entryId,
        $generatedAt,
        'not-a-date',
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidDeadlineRejected =
        true;
}


recommendationCandidateAssert(
    $invalidDeadlineRejected,
    'Candidate rejects an invalid deadline timestamp.'
);


/*
 * ============================================================
 * F. CANDIDATE MUST BE PRE-DEADLINE
 * ============================================================
 */

recommendationCandidateSection(
    'F. Candidate Must Be Pre-Deadline'
);


$atDeadlineRejected =
    false;


try {

    new RecommendationCandidate(
        $gameweekId,
        $entryId,
        $deadlineTime,
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    );

} catch (
    InvalidArgumentException $exception
) {

    $atDeadlineRejected =
        true;
}


recommendationCandidateAssert(
    $atDeadlineRejected,
    'Candidate cannot be generated at the deadline.'
);


$afterDeadlineRejected =
    false;


try {

    new RecommendationCandidate(
        $gameweekId,
        $entryId,
        '2026-09-05 11:00:01',
        $deadlineTime,
        $playerProjections,
        $startingXI,
        $captainRecommendation,
        $transferRecommendations,
        $gameweekDecision,
        $chipRecommendations
    );

} catch (
    InvalidArgumentException $exception
) {

    $afterDeadlineRejected =
        true;
}


recommendationCandidateAssert(
    $afterDeadlineRejected,
    'Candidate cannot be generated after the deadline.'
);


/*
 * ============================================================
 * G. RECOMMENDATION EVIDENCE
 * ============================================================
 */

recommendationCandidateSection(
    'G. Recommendation Evidence'
);


recommendationCandidateAssert(
    $candidate->getPlayerProjections() === $playerProjections,
    'Candidate preserves player projection evidence.'
);


recommendationCandidateAssert(
    $candidate->getStartingXI() === $startingXI,
    'Candidate preserves Starting XI evidence.'
);


recommendationCandidateAssert(
    $candidate->getCaptainRecommendation()
        ===
        $captainRecommendation,
    'Candidate preserves Captain Intelligence evidence.'
);


recommendationCandidateAssert(
    $candidate->getTransferRecommendations()
        ===
        $transferRecommendations,
    'Candidate preserves Transfer Intelligence evidence.'
);


recommendationCandidateAssert(
    $candidate->getGameweekDecision()
        ===
        $gameweekDecision,
    'Candidate preserves Gameweek Decision evidence.'
);


recommendationCandidateAssert(
    $candidate->getChipRecommendations()
        ===
        $chipRecommendations,
    'Candidate preserves Chip Intelligence evidence.'
);


/*
 * ============================================================
 * H. EXPORT CONTRACT
 * ============================================================
 */

recommendationCandidateSection(
    'H. Export Contract'
);


$export =
    $candidate->toArray();


$expectedExport = [

    'gameweek' =>
        $gameweekId,

    'entry_id' =>
        $entryId,

    'generated_at' =>
        $generatedAt,

    'deadline_time' =>
        $deadlineTime,

    'player_projections' =>
        $playerProjections,

    'starting_xi' =>
        $startingXI,

    'captain_recommendation' =>
        $captainRecommendation,

    'transfer_recommendations' =>
        $transferRecommendations,

    'gameweek_decision' =>
        $gameweekDecision,

    'chip_recommendations' =>
        $chipRecommendations
];


recommendationCandidateAssert(
    $export === $expectedExport,
    'Candidate exports the complete recommendation evidence contract.'
);


/*
 * ============================================================
 * I. EXPORTED ARRAYS DO NOT MUTATE CANDIDATE
 * ============================================================
 */

recommendationCandidateSection(
    'I. Candidate State Isolation'
);


$modifiedProjections =
    $candidate
        ->getPlayerProjections();


$modifiedProjections[
    0
][
    'projected_points'
] =
    999.0;


recommendationCandidateAssert(
    $candidate
        ->getPlayerProjections()
        ===
        $playerProjections,
    'Modifying returned projection evidence does not mutate candidate state.'
);


$modifiedExport =
    $candidate
        ->toArray();


$modifiedExport[
    'chip_recommendations'
][
    'Wildcard'
][
    'recommendation'
] =
    'Use';


recommendationCandidateAssert(
    $candidate
        ->getChipRecommendations()
        ===
        $chipRecommendations,
    'Modifying exported evidence does not mutate candidate state.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

recommendationCandidateSection(
    'Recommendation Candidate Test Summary'
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