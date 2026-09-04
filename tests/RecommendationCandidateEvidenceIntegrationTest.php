<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION CANDIDATE EVIDENCE INTEGRATION TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * This test proves that the existing v0.35 evidence components
 * compose into one complete persisted recommendation candidate.
 *
 * It deliberately uses:
 *
 * - PlayerProjectionEvidence
 * - ChipRecommendationEvidence
 * - RecommendationCandidateCaptureService
 * - RecommendationCandidateRepository
 *
 * It does not calculate any new intelligence.
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


function candidateIntegrationAssert(
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


function candidateIntegrationSection(
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
 * CONTROLLED TEST ENTRY
 * ============================================================
 *
 * This synthetic ID is deliberately outside normal user data.
 */

$entryId =
    935005002;


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


$candidateRepository =
    new RecommendationCandidateRepository(
        $db
    );


/*
 * ============================================================
 * RESOLVE ONE REAL LOCAL GAMEWEEK
 * ============================================================
 */

$gameweekStatement =
    $db->query(
        "
        SELECT
            id,
            deadline_time
        FROM
            gameweeks
        WHERE
            deadline_time IS NOT NULL
        ORDER BY
            deadline_time DESC
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
) {

    die(
        'No local gameweek with a deadline could be found.'
    );
}


$gameweekId =
    (int) $gameweekRow[
        'id'
    ];


$deadline =
    new DateTimeImmutable(
        (string) $gameweekRow[
            'deadline_time'
        ]
    );


$deadlineTime =
    $deadline
        ->format(
            'Y-m-d H:i:s'
        );


$earlierGeneratedAt =
    $deadline
        ->modify(
            '-2 hours'
        )
        ->format(
            'Y-m-d H:i:s'
        );


$laterGeneratedAt =
    $deadline
        ->modify(
            '-1 hour'
        )
        ->format(
            'Y-m-d H:i:s'
        );


$staleGeneratedAt =
    $deadline
        ->modify(
            '-3 hours'
        )
        ->format(
            'Y-m-d H:i:s'
        );


/*
 * ============================================================
 * CLEAN CONTROLLED ROW
 * ============================================================
 */

$cleanupStatement =
    $db->prepare(
        "
        DELETE FROM
            recommendation_candidates
        WHERE
            gameweek_id = :gameweek_id
        AND
            entry_id = :entry_id
        "
    );


$cleanupStatement->execute(
    [
        'gameweek_id' =>
            $gameweekId,

        'entry_id' =>
            $entryId
    ]
);


/*
 * ============================================================
 * EXISTING MANAGER SQUAD
 * ============================================================
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
 * EXISTING PLAYER INTELLIGENCE EVIDENCE
 * ============================================================
 */

$playerSummaries = [

    [
        'player_id' =>
            101,

        'fpl_player_id' =>
            1001,

        'name' =>
            'Integration Player One',

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
            'Integration Player Two',

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
            'Integration Player Three',

        'position' =>
            'MID',

        'team_id' =>
            3,

        'price' =>
            8.0,

        'intelligence_score' =>
            68.0,

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
    ]
];


/*
 * ============================================================
 * EXISTING GAMEWEEK DECISION RESULT
 * ============================================================
 */

$gameweekDecisionResult = [

    'status' =>
        'success',

    'gameweek' => [

        'formation' =>
            '3-4-3',

        'starting_xi_score' =>
            72.25,

        'bench_score' =>
            14.50,

        'starting_xi' => [

            [
                'player_id' =>
                    101,

                'name' =>
                    'Integration Player One'
            ],

            [
                'player_id' =>
                    102,

                'name' =>
                    'Integration Player Two'
            ]
        ],

        'bench' => [

            [
                'player_id' =>
                    103,

                'name' =>
                    'Integration Player Three'
            ]
        ]
    ],

    'captaincy' => [

        'status' =>
            'success',

        'captain' => [

            'player_id' =>
                102,

            'name' =>
                'Integration Player Two',

            'captain_score' =>
                81.25
        ],

        'vice_captain' => [

            'player_id' =>
                101,

            'name' =>
                'Integration Player One'
        ]
    ],

    'transfers' => [

        'recommendation' =>
            'Hold',

        'transfers' =>
            []
    ],

    'decision' => [

        'status' =>
            'success',

        'overall_action' =>
            'Hold transfer',

        'key_insights' => [

            'Current squad remains competitive.'
        ]
    ]
];


/*
 * ============================================================
 * EXISTING CHIP INTELLIGENCE RESULTS
 * ============================================================
 */

$wildcardDecision =
    new ChipDecision(
        'Wildcard',
        'Consider',
        0.62,
        'Wildcard timing is worth considering.'
    );


$freeHitDecision =
    new ChipDecision(
        'Free Hit',
        'Hold',
        0.71,
        'Free Hit value is currently limited.'
    );


$benchBoostDecision =
    new ChipDecision(
        'Bench Boost',
        'Use',
        0.83,
        'Projected bench contribution is strong.'
    );


$tripleCaptainDecision =
    new ChipDecision(
        'Triple Captain',
        'Hold',
        0.76,
        'Captain projection does not justify the chip.'
    );


$wildcardResult = [

    'timing_result' => [

        'current_squad_projected_points' =>
            68.25,

        'wildcard_squad_projected_points' =>
            74.50,

        'projected_points_gain' =>
            6.25,

        'future_projected_gain' =>
            10.75,

        'better_timing' =>
            'GW8',

        'decision' =>
            $wildcardDecision
    ]
];


$freeHitResult = [

    'value_result' => [

        'current_squad_projected_points' =>
            68.25,

        'free_hit_projected_points' =>
            72.75,

        'projected_points_gain' =>
            4.50
    ],

    'decision' =>
        $freeHitDecision
];


$benchBoostResult = [

    'analysis' => [

        'projected_bench_points' =>
            16.25,

        'bench_reliability' =>
            0.82,

        'fixture_quality' =>
            67.50,

        'full_squad_availability' =>
            0.91
    ],

    'decision' =>
        $benchBoostDecision
];


$tripleCaptainResult = [

    'analysis' => [

        'projected_points' =>
            9.75,

        'captain_score' =>
            74.25,

        'projection_confidence' =>
            0.78
    ],

    'decision' =>
        $tripleCaptainDecision
];


/*
 * ============================================================
 * BUILD EXISTING EVIDENCE
 * ============================================================
 */

$playerProjectionEvidence =
    new PlayerProjectionEvidence();


$chipRecommendationEvidence =
    new ChipRecommendationEvidence();


$playerProjections =
    $playerProjectionEvidence
        ->build(
            $squadPlayers,
            $playerSummaries
        );


$chipRecommendations =
    $chipRecommendationEvidence
        ->build(
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult
        );


/*
 * ============================================================
 * CAPTURE SERVICE
 * ============================================================
 */

$captureService =
    new RecommendationCandidateCaptureService(
        $candidateRepository
    );


/*
 * ============================================================
 * A. EXISTING EVIDENCE COMPOSES
 * ============================================================
 */

candidateIntegrationSection(
    'A. Existing Evidence Composes'
);


candidateIntegrationAssert(
    count(
        $playerProjections
    )
    ===
    3,
    'Player projection adapter produces squad evidence.'
);


candidateIntegrationAssert(
    count(
        $chipRecommendations
    )
    ===
    4,
    'Chip adapter produces all four chip recommendations.'
);


/*
 * ============================================================
 * B. CAPTURE COMPLETE CANDIDATE
 * ============================================================
 */

candidateIntegrationSection(
    'B. Capture Complete Recommendation Candidate'
);


$captured =
    $captureService
        ->capture(
            $gameweekId,
            $entryId,
            $earlierGeneratedAt,
            $deadlineTime,
            $playerProjections,
            $gameweekDecisionResult,
            $chipRecommendations
        );


candidateIntegrationAssert(
    $captured === true,
    'Complete existing recommendation evidence is captured.'
);


/*
 * ============================================================
 * C. CANDIDATE PERSISTED
 * ============================================================
 */

candidateIntegrationSection(
    'C. Complete Candidate Persisted'
);


$persisted =
    $candidateRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


candidateIntegrationAssert(
    is_array(
        $persisted
    ),
    'Complete recommendation candidate is persisted.'
);


/*
 * ============================================================
 * D. METADATA ROUND TRIP
 * ============================================================
 */

candidateIntegrationSection(
    'D. Candidate Metadata'
);


candidateIntegrationAssert(
    $persisted[
        'gameweek_id'
    ]
    ===
    $gameweekId,
    'Local gameweek ID round trips unchanged.'
);


candidateIntegrationAssert(
    $persisted[
        'entry_id'
    ]
    ===
    $entryId,
    'FPL entry ID round trips unchanged.'
);


candidateIntegrationAssert(
    $persisted[
        'generated_at'
    ]
    ===
    $earlierGeneratedAt,
    'Recommendation generation time round trips unchanged.'
);


candidateIntegrationAssert(
    $persisted[
        'deadline_time'
    ]
    ===
    $deadlineTime,
    'Gameweek deadline round trips unchanged.'
);


/*
 * ============================================================
 * E. PLAYER PROJECTION ROUND TRIP
 * ============================================================
 */

candidateIntegrationSection(
    'E. Player Projection Evidence'
);


candidateIntegrationAssert(
    $persisted[
        'player_projections'
    ]
    ===
    $playerProjections,
    'Complete player projection evidence round trips exactly.'
);


candidateIntegrationAssert(
    $persisted[
        'player_projections'
    ][
        2
    ][
        'projected_points'
    ]
    ===
    null,
    'Unavailable projection remains historical evidence.'
);


/*
 * ============================================================
 * F. GAMEWEEK INTELLIGENCE ROUND TRIP
 * ============================================================
 */

candidateIntegrationSection(
    'F. Gameweek Intelligence Evidence'
);


candidateIntegrationAssert(
    $persisted[
        'starting_xi'
    ]
    ===
    $gameweekDecisionResult[
        'gameweek'
    ][
        'starting_xi'
    ],
    'Starting XI evidence round trips exactly.'
);


candidateIntegrationAssert(
    $persisted[
        'captain_recommendation'
    ]
    ===
    $gameweekDecisionResult[
        'captaincy'
    ],
    'Captain Intelligence evidence round trips exactly.'
);


candidateIntegrationAssert(
    $persisted[
        'transfer_recommendations'
    ]
    ===
    $gameweekDecisionResult[
        'transfers'
    ],
    'Transfer Intelligence evidence round trips exactly.'
);


candidateIntegrationAssert(
    $persisted[
        'gameweek_decision'
    ]
    ===
    $gameweekDecisionResult[
        'decision'
    ],
    'Gameweek Decision evidence round trips exactly.'
);


/*
 * ============================================================
 * G. CHIP INTELLIGENCE ROUND TRIP
 * ============================================================
 */

candidateIntegrationSection(
    'G. Chip Intelligence Evidence'
);


candidateIntegrationAssert(
    $persisted[
        'chip_recommendations'
    ]
    ===
    $chipRecommendations,
    'Complete Chip Intelligence evidence round trips exactly.'
);


candidateIntegrationAssert(
    $persisted[
        'chip_recommendations'
    ][
        'bench_boost'
    ][
        'decision'
    ][
        'recommendation'
    ]
    ===
    'Use',
    'Independent Bench Boost recommendation remains unchanged.'
);


candidateIntegrationAssert(
    $persisted[
        'chip_recommendations'
    ][
        'wildcard'
    ][
        'analysis'
    ][
        'projected_points_gain'
    ]
    ===
    6.25,
    'Raw chip projected-points evidence remains numeric.'
);


/*
 * ============================================================
 * H. NEWER RECOMMENDATION REPLACES CANDIDATE
 * ============================================================
 */

candidateIntegrationSection(
    'H. Newer Recommendation Replaces Candidate'
);


$laterPlayerSummaries =
    $playerSummaries;


$laterPlayerSummaries[
    0
][
    'projected_points'
] =
    7.25;


$laterPlayerProjections =
    $playerProjectionEvidence
        ->build(
            $squadPlayers,
            $laterPlayerSummaries
        );


$laterGameweekDecisionResult =
    $gameweekDecisionResult;


$laterGameweekDecisionResult[
    'decision'
][
    'overall_action'
] =
    'Make transfer';


$laterCaptured =
    $captureService
        ->capture(
            $gameweekId,
            $entryId,
            $laterGeneratedAt,
            $deadlineTime,
            $laterPlayerProjections,
            $laterGameweekDecisionResult,
            $chipRecommendations
        );


candidateIntegrationAssert(
    $laterCaptured === true,
    'Later recommendation replaces the earlier mutable candidate.'
);


$laterPersisted =
    $candidateRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


candidateIntegrationAssert(
    $laterPersisted[
        'generated_at'
    ]
    ===
    $laterGeneratedAt,
    'Later recommendation generation time is persisted.'
);


candidateIntegrationAssert(
    $laterPersisted[
        'player_projections'
    ][
        0
    ][
        'projected_points'
    ]
    ===
    7.25,
    'Later player projection evidence replaces earlier evidence.'
);


candidateIntegrationAssert(
    $laterPersisted[
        'gameweek_decision'
    ][
        'overall_action'
    ]
    ===
    'Make transfer',
    'Later Gameweek Decision replaces earlier evidence.'
);


/*
 * ============================================================
 * I. STALE RECOMMENDATION CANNOT REPLACE LATEST
 * ============================================================
 */

candidateIntegrationSection(
    'I. Stale Recommendation Cannot Replace Latest'
);


$staleCaptured =
    $captureService
        ->capture(
            $gameweekId,
            $entryId,
            $staleGeneratedAt,
            $deadlineTime,
            $playerProjections,
            $gameweekDecisionResult,
            $chipRecommendations
        );


candidateIntegrationAssert(
    $staleCaptured === false,
    'Older recommendation cannot replace latest candidate.'
);


$afterStale =
    $candidateRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


candidateIntegrationAssert(
    $afterStale[
        'generated_at'
    ]
    ===
    $laterGeneratedAt,
    'Latest candidate remains unchanged after stale capture.'
);


candidateIntegrationAssert(
    $afterStale[
        'player_projections'
    ][
        0
    ][
        'projected_points'
    ]
    ===
    7.25,
    'Latest projection evidence survives stale capture attempt.'
);


/*
 * ============================================================
 * J. CLEANUP
 * ============================================================
 */

candidateIntegrationSection(
    'J. Controlled Test Cleanup'
);


$cleanupStatement->execute(
    [
        'gameweek_id' =>
            $gameweekId,

        'entry_id' =>
            $entryId
    ]
);


$afterCleanup =
    $candidateRepository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


candidateIntegrationAssert(
    $afterCleanup === null,
    'Controlled synthetic candidate is removed after the test.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

candidateIntegrationSection(
    'Recommendation Candidate Evidence Integration Test Summary'
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