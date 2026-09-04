<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION CANDIDATE CAPTURE SERVICE TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * The capture service adapts existing production intelligence
 * output into a RecommendationCandidate.
 *
 * It must not calculate or reinterpret recommendation
 * intelligence.
 *
 * Candidate persistence remains mutable before the deadline:
 *
 * - first recommendation is stored
 * - newer recommendation replaces older recommendation
 * - older recommendation cannot replace newer recommendation
 * - equal-time recommendation cannot replace existing evidence
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


function candidateCaptureAssert(
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


function candidateCaptureSection(
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
        'RecommendationCandidateCaptureServiceTest requires '
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
        'RecommendationCandidateCaptureServiceTest could not '
        . 'parse the selected gameweek deadline.'
    );
}


/*
 * ============================================================
 * CONTROLLED ENTRY ID
 * ============================================================
 */

$entryId =
    935005001;


/*
 * ============================================================
 * CONTROLLED TIMESTAMPS
 * ============================================================
 */

$earlyGeneratedAt =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestamp
        -
        7200
    );


$laterGeneratedAt =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestamp
        -
        3600
    );


$olderGeneratedAt =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestamp
        -
        10800
    );


/*
 * ============================================================
 * EVIDENCE FACTORY
 * ============================================================
 */

function buildCandidateCaptureEvidence(
    string $label,
    float $projectedPoints
): array {

    $playerProjections = [

        [
            'player_id' =>
                101,

            'name' =>
                'Test Player',

            'label' =>
                $label,

            'projected_points' =>
                $projectedPoints,

            'projected_minutes' =>
                82.0,

            'projection_confidence' =>
                0.81
        ]
    ];


    $startingXI = [];


    for (
        $playerId = 101;
        $playerId <= 111;
        $playerId++
    ) {

        $startingXI[] = [

            'player_id' =>
                $playerId,

            'label' =>
                $label,

            'projected_points' =>
                $playerId === 101
                    ? $projectedPoints
                    : 5.0
        ];
    }


    $bench = [];


    for (
        $playerId = 112;
        $playerId <= 115;
        $playerId++
    ) {

        $bench[] = [

            'player_id' =>
                $playerId,

            'label' =>
                $label,

            'projected_points' =>
                3.0
        ];
    }


    /*
     * Production-shaped Gameweek Intelligence output.
     */
    $gameweekOutput = [

        'status' =>
            'success',

        'formation' =>
            '3-4-3',

        'starting_xi_score' =>
            71.5,

        'bench_score' =>
            12.0,

        'starting_xi' =>
            $startingXI,

        'bench' =>
            $bench
    ];


    /*
     * Production-shaped Captain Intelligence output.
     */
    $captainOutput = [

        'status' =>
            'success',

        'label' =>
            $label,

        'captain' => [

            'player_id' =>
                101,

            'captain_score' =>
                72.5,

            'projected_points' =>
                $projectedPoints
        ],

        'vice_captain' => [

            'player_id' =>
                102,

            'captain_score' =>
                66.0,

            'projected_points' =>
                5.0
        ]
    ];


    /*
     * Production-shaped Transfer Intelligence output.
     */
    $transferOutput = [

        'status' =>
            'success',

        'label' =>
            $label,

        'recommendation' =>
            'Hold',

        'confidence' =>
            0.68
    ];


    /*
     * Existing Gameweek Decision Engine output.
     */
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
            71.5,

        'bench_score' =>
            12.0,

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

        'key_insights' =>
            [
                $label
            ]
    ];


    /*
     * Production orchestration result returned by
     * PlayerIntelligenceService::getGameweekDecision().
     */
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


    $chipRecommendations = [

        'Wildcard' => [

            'recommendation' =>
                'Hold',

            'confidence' =>
                0.62,

            'label' =>
                $label
        ],

        'Free Hit' => [

            'recommendation' =>
                'Hold',

            'confidence' =>
                0.70,

            'label' =>
                $label
        ],

        'Bench Boost' => [

            'recommendation' =>
                'Consider',

            'confidence' =>
                0.64,

            'label' =>
                $label
        ],

        'Triple Captain' => [

            'recommendation' =>
                'Hold',

            'confidence' =>
                0.76,

            'label' =>
                $label
        ]
    ];


    return [

        'player_projections' =>
            $playerProjections,

        'starting_xi' =>
            $startingXI,

        'gameweek_decision_result' =>
            $gameweekDecisionResult,

        'captain_recommendation' =>
            $captainOutput,

        'transfer_recommendations' =>
            $transferOutput,

        'gameweek_decision' =>
            $decisionOutput,

        'chip_recommendations' =>
            $chipRecommendations
    ];
}


/*
 * ============================================================
 * CLEAN CONTROLLED DATA
 * ============================================================
 */

$cleanupStatement =
    $db->prepare(
        "
        DELETE FROM recommendation_candidates
        WHERE entry_id = :entry_id
        "
    );


$cleanupStatement->execute(
    [
        'entry_id' =>
            $entryId
    ]
);


/*
 * ============================================================
 * REPOSITORY
 * ============================================================
 */

$repository =
    new RecommendationCandidateRepository(
        $db
    );


/*
 * ============================================================
 * SERVICE
 * ============================================================
 */

$service =
    new RecommendationCandidateCaptureService(
        $repository
    );


/*
 * ============================================================
 * A. CAPTURE EXISTING INTELLIGENCE
 * ============================================================
 */

candidateCaptureSection(
    'A. Capture Existing Intelligence'
);


$earlyEvidence =
    buildCandidateCaptureEvidence(
        'EARLY',
        7.25
    );


$earlyResult =
    $service
        ->capture(
            $gameweekId,
            $entryId,
            $earlyGeneratedAt,
            $deadlineTime,
            $earlyEvidence[
                'player_projections'
            ],
            $earlyEvidence[
                'gameweek_decision_result'
            ],
            $earlyEvidence[
                'chip_recommendations'
            ]
        );


candidateCaptureAssert(
    $earlyResult === true,
    'Existing production intelligence is captured as a candidate.'
);


/*
 * ============================================================
 * B. CANDIDATE PERSISTED
 * ============================================================
 */

candidateCaptureSection(
    'B. Candidate Persisted'
);


$storedEarly =
    $repository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


candidateCaptureAssert(
    is_array(
        $storedEarly
    ),
    'Captured candidate is persisted.'
);


/*
 * ============================================================
 * C. PLAYER PROJECTIONS PRESERVED
 * ============================================================
 */

candidateCaptureSection(
    'C. Player Projection Evidence'
);


candidateCaptureAssert(
    (
        $storedEarly[
            'player_projections'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'player_projections'
    ],
    'Existing player projection evidence is preserved exactly.'
);


/*
 * ============================================================
 * D. STARTING XI EXTRACTED
 * ============================================================
 */

candidateCaptureSection(
    'D. Starting XI Evidence'
);


candidateCaptureAssert(
    (
        $storedEarly[
            'starting_xi'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'starting_xi'
    ],
    'Starting XI is extracted from existing Gameweek Intelligence.'
);


/*
 * ============================================================
 * E. CAPTAINCY PRESERVED
 * ============================================================
 */

candidateCaptureSection(
    'E. Captain Recommendation Evidence'
);


candidateCaptureAssert(
    (
        $storedEarly[
            'captain_recommendation'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'captain_recommendation'
    ],
    'Existing Captain Intelligence evidence is preserved exactly.'
);


/*
 * ============================================================
 * F. TRANSFERS PRESERVED
 * ============================================================
 */

candidateCaptureSection(
    'F. Transfer Recommendation Evidence'
);


candidateCaptureAssert(
    (
        $storedEarly[
            'transfer_recommendations'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'transfer_recommendations'
    ],
    'Existing Transfer Intelligence evidence is preserved exactly.'
);


/*
 * ============================================================
 * G. GAMEWEEK DECISION PRESERVED
 * ============================================================
 */

candidateCaptureSection(
    'G. Gameweek Decision Evidence'
);


candidateCaptureAssert(
    (
        $storedEarly[
            'gameweek_decision'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'gameweek_decision'
    ],
    'Existing Gameweek Decision evidence is preserved exactly.'
);


/*
 * ============================================================
 * H. CHIP RECOMMENDATIONS PRESERVED
 * ============================================================
 */

candidateCaptureSection(
    'H. Chip Recommendation Evidence'
);


candidateCaptureAssert(
    (
        $storedEarly[
            'chip_recommendations'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'chip_recommendations'
    ],
    'Existing Chip Intelligence evidence is preserved exactly.'
);


/*
 * ============================================================
 * I. METADATA PRESERVED
 * ============================================================
 */

candidateCaptureSection(
    'I. Candidate Metadata'
);


candidateCaptureAssert(
    (
        $storedEarly[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    $gameweekId,
    'Candidate preserves local gameweek ID.'
);


candidateCaptureAssert(
    (
        $storedEarly[
            'entry_id'
        ]
        ?? null
    )
    ===
    $entryId,
    'Candidate preserves FPL entry ID.'
);


candidateCaptureAssert(
    (
        $storedEarly[
            'generated_at'
        ]
        ?? null
    )
    ===
    $earlyGeneratedAt,
    'Candidate preserves recommendation generation time.'
);


candidateCaptureAssert(
    (
        $storedEarly[
            'deadline_time'
        ]
        ?? null
    )
    ===
    $deadlineTime,
    'Candidate preserves gameweek deadline.'
);


/*
 * ============================================================
 * J. NEWER INTELLIGENCE REPLACES EARLIER CANDIDATE
 * ============================================================
 */

candidateCaptureSection(
    'J. Newer Intelligence Replaces Earlier Candidate'
);


$laterEvidence =
    buildCandidateCaptureEvidence(
        'LATER',
        9.75
    );


$laterResult =
    $service
        ->capture(
            $gameweekId,
            $entryId,
            $laterGeneratedAt,
            $deadlineTime,
            $laterEvidence[
                'player_projections'
            ],
            $laterEvidence[
                'gameweek_decision_result'
            ],
            $laterEvidence[
                'chip_recommendations'
            ]
        );


candidateCaptureAssert(
    $laterResult === true,
    'Newer production intelligence replaces earlier candidate.'
);


$storedLater =
    $repository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


candidateCaptureAssert(
    (
        $storedLater[
            'generated_at'
        ]
        ?? null
    )
    ===
    $laterGeneratedAt,
    'Newer recommendation generation time is persisted.'
);


candidateCaptureAssert(
    (
        $storedLater[
            'player_projections'
        ]
        ?? null
    )
    ===
    $laterEvidence[
        'player_projections'
    ],
    'Newer player projection evidence replaces earlier evidence.'
);


candidateCaptureAssert(
    (
        $storedLater[
            'captain_recommendation'
        ]
        ?? null
    )
    ===
    $laterEvidence[
        'captain_recommendation'
    ],
    'Newer Captain Intelligence replaces earlier evidence.'
);


candidateCaptureAssert(
    (
        $storedLater[
            'chip_recommendations'
        ]
        ?? null
    )
    ===
    $laterEvidence[
        'chip_recommendations'
    ],
    'Newer Chip Intelligence replaces earlier evidence.'
);


/*
 * ============================================================
 * K. OLDER INTELLIGENCE CANNOT REPLACE LATEST CANDIDATE
 * ============================================================
 */

candidateCaptureSection(
    'K. Older Intelligence Cannot Replace Latest Candidate'
);


$olderEvidence =
    buildCandidateCaptureEvidence(
        'STALE',
        2.0
    );


$olderResult =
    $service
        ->capture(
            $gameweekId,
            $entryId,
            $olderGeneratedAt,
            $deadlineTime,
            $olderEvidence[
                'player_projections'
            ],
            $olderEvidence[
                'gameweek_decision_result'
            ],
            $olderEvidence[
                'chip_recommendations'
            ]
        );


candidateCaptureAssert(
    $olderResult === false,
    'Older production intelligence cannot replace latest candidate.'
);


$storedAfterOlder =
    $repository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


candidateCaptureAssert(
    (
        $storedAfterOlder[
            'player_projections'
        ]
        ?? null
    )
    ===
    $laterEvidence[
        'player_projections'
    ],
    'Older capture leaves latest candidate evidence unchanged.'
);


/*
 * ============================================================
 * L. SAME-TIME INTELLIGENCE DOES NOT REPLACE CANDIDATE
 * ============================================================
 */

candidateCaptureSection(
    'L. Same-Time Intelligence Does Not Replace Candidate'
);


$sameTimeEvidence =
    buildCandidateCaptureEvidence(
        'SAME-TIME',
        50.0
    );


$sameTimeResult =
    $service
        ->capture(
            $gameweekId,
            $entryId,
            $laterGeneratedAt,
            $deadlineTime,
            $sameTimeEvidence[
                'player_projections'
            ],
            $sameTimeEvidence[
                'gameweek_decision_result'
            ],
            $sameTimeEvidence[
                'chip_recommendations'
            ]
        );


candidateCaptureAssert(
    $sameTimeResult === false,
    'Equal-time production intelligence is not treated as newer.'
);


$storedAfterSameTime =
    $repository
        ->getByEntryAndGameweek(
            $entryId,
            $gameweekId
        );


candidateCaptureAssert(
    (
        $storedAfterSameTime[
            'player_projections'
        ]
        ?? null
    )
    ===
    $laterEvidence[
        'player_projections'
    ],
    'Equal-time capture leaves latest candidate evidence unchanged.'
);


/*
 * ============================================================
 * M. UNSUCCESSFUL GAMEWEEK DECISION REJECTED
 * ============================================================
 */

candidateCaptureSection(
    'M. Unsuccessful Gameweek Decision'
);


$invalidDecisionResult =
    $laterEvidence[
        'gameweek_decision_result'
    ];


$invalidDecisionResult[
    'status'
] =
    'error';


$unsuccessfulRejected =
    false;


try {

    $service
        ->capture(
            $gameweekId,
            $entryId,
            $laterGeneratedAt,
            $deadlineTime,
            $laterEvidence[
                'player_projections'
            ],
            $invalidDecisionResult,
            $laterEvidence[
                'chip_recommendations'
            ]
        );

} catch (
    InvalidArgumentException $exception
) {

    $unsuccessfulRejected =
        true;
}


candidateCaptureAssert(
    $unsuccessfulRejected,
    'Unsuccessful Gameweek Decision result is rejected.'
);


/*
 * ============================================================
 * N. MISSING STARTING XI REJECTED
 * ============================================================
 */

candidateCaptureSection(
    'N. Missing Starting XI'
);


$missingStartingXI =
    $laterEvidence[
        'gameweek_decision_result'
    ];


$missingStartingXI[
    'gameweek'
][
    'starting_xi'
] =
    [];


$missingStartingXIRejected =
    false;


try {

    $service
        ->capture(
            $gameweekId,
            $entryId,
            $laterGeneratedAt,
            $deadlineTime,
            $laterEvidence[
                'player_projections'
            ],
            $missingStartingXI,
            $laterEvidence[
                'chip_recommendations'
            ]
        );

} catch (
    InvalidArgumentException $exception
) {

    $missingStartingXIRejected =
        true;
}


candidateCaptureAssert(
    $missingStartingXIRejected,
    'Missing Starting XI evidence is rejected.'
);


/*
 * ============================================================
 * O. MISSING CAPTAINCY REJECTED
 * ============================================================
 */

candidateCaptureSection(
    'O. Missing Captaincy'
);


$missingCaptaincy =
    $laterEvidence[
        'gameweek_decision_result'
    ];


$missingCaptaincy[
    'captaincy'
] =
    [];


$missingCaptaincyRejected =
    false;


try {

    $service
        ->capture(
            $gameweekId,
            $entryId,
            $laterGeneratedAt,
            $deadlineTime,
            $laterEvidence[
                'player_projections'
            ],
            $missingCaptaincy,
            $laterEvidence[
                'chip_recommendations'
            ]
        );

} catch (
    InvalidArgumentException $exception
) {

    $missingCaptaincyRejected =
        true;
}


candidateCaptureAssert(
    $missingCaptaincyRejected,
    'Missing Captain Intelligence evidence is rejected.'
);


/*
 * ============================================================
 * P. MISSING TRANSFERS REJECTED
 * ============================================================
 */

candidateCaptureSection(
    'P. Missing Transfers'
);


$missingTransfers =
    $laterEvidence[
        'gameweek_decision_result'
    ];


$missingTransfers[
    'transfers'
] =
    [];


$missingTransfersRejected =
    false;


try {

    $service
        ->capture(
            $gameweekId,
            $entryId,
            $laterGeneratedAt,
            $deadlineTime,
            $laterEvidence[
                'player_projections'
            ],
            $missingTransfers,
            $laterEvidence[
                'chip_recommendations'
            ]
        );

} catch (
    InvalidArgumentException $exception
) {

    $missingTransfersRejected =
        true;
}


candidateCaptureAssert(
    $missingTransfersRejected,
    'Missing Transfer Intelligence evidence is rejected.'
);


/*
 * ============================================================
 * Q. MISSING DECISION REJECTED
 * ============================================================
 */

candidateCaptureSection(
    'Q. Missing Gameweek Decision'
);


$missingDecision =
    $laterEvidence[
        'gameweek_decision_result'
    ];


$missingDecision[
    'decision'
] =
    [];


$missingDecisionRejected =
    false;


try {

    $service
        ->capture(
            $gameweekId,
            $entryId,
            $laterGeneratedAt,
            $deadlineTime,
            $laterEvidence[
                'player_projections'
            ],
            $missingDecision,
            $laterEvidence[
                'chip_recommendations'
            ]
        );

} catch (
    InvalidArgumentException $exception
) {

    $missingDecisionRejected =
        true;
}


candidateCaptureAssert(
    $missingDecisionRejected,
    'Missing Gameweek Decision evidence is rejected.'
);


/*
 * ============================================================
 * R. EMPTY PLAYER PROJECTIONS REJECTED
 * ============================================================
 */

candidateCaptureSection(
    'R. Empty Player Projections'
);


$emptyProjectionsRejected =
    false;


try {

    $service
        ->capture(
            $gameweekId,
            $entryId,
            $laterGeneratedAt,
            $deadlineTime,
            [],
            $laterEvidence[
                'gameweek_decision_result'
            ],
            $laterEvidence[
                'chip_recommendations'
            ]
        );

} catch (
    InvalidArgumentException $exception
) {

    $emptyProjectionsRejected =
        true;
}


candidateCaptureAssert(
    $emptyProjectionsRejected,
    'Empty player projection evidence is rejected.'
);


/*
 * ============================================================
 * S. EMPTY CHIP EVIDENCE REJECTED
 * ============================================================
 */

candidateCaptureSection(
    'S. Empty Chip Evidence'
);


$emptyChipsRejected =
    false;


try {

    $service
        ->capture(
            $gameweekId,
            $entryId,
            $laterGeneratedAt,
            $deadlineTime,
            $laterEvidence[
                'player_projections'
            ],
            $laterEvidence[
                'gameweek_decision_result'
            ],
            []
        );

} catch (
    InvalidArgumentException $exception
) {

    $emptyChipsRejected =
        true;
}


candidateCaptureAssert(
    $emptyChipsRejected,
    'Empty Chip Intelligence evidence is rejected.'
);


/*
 * ============================================================
 * CLEANUP
 * ============================================================
 */

$cleanupStatement->execute(
    [
        'entry_id' =>
            $entryId
    ]
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

candidateCaptureSection(
    'Recommendation Candidate Capture Service Test Summary'
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