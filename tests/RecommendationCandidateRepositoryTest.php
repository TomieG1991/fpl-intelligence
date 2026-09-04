<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION CANDIDATE REPOSITORY TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * Recommendation candidates are mutable staging evidence.
 *
 * For each entry/gameweek:
 *
 * - the first candidate is inserted
 * - a newer candidate replaces the existing candidate
 * - an older candidate must NOT replace a newer candidate
 *
 * This is deliberately different from RecommendationSnapshot,
 * which is immutable once captured.
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


function candidateRepositoryAssert(
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


function candidateRepositorySection(
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
 * FIND TWO REAL LOCAL GAMEWEEKS
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
        LIMIT 2
        "
    );


$gameweekRows =
    $gameweekStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


if (
    count(
        $gameweekRows
    )
    <
    2
) {

    die(
        'RecommendationCandidateRepositoryTest requires '
        . 'at least two valid rows in the gameweeks table.'
    );
}


$gameweekIdOne =
    (int) $gameweekRows[
        0
    ][
        'id'
    ];


$gameweekIdTwo =
    (int) $gameweekRows[
        1
    ][
        'id'
    ];


$deadlineOne =
    (string) $gameweekRows[
        0
    ][
        'deadline_time'
    ];


$deadlineTwo =
    (string) $gameweekRows[
        1
    ][
        'deadline_time'
    ];


/*
 * ============================================================
 * TEST ENTRY IDS
 * ============================================================
 */

$entryIdOne =
    935003001;


$entryIdTwo =
    935003002;


/*
 * ============================================================
 * TIMESTAMPS
 * ============================================================
 */

$deadlineTimestampOne =
    strtotime(
        $deadlineOne
    );


$deadlineTimestampTwo =
    strtotime(
        $deadlineTwo
    );


if (
    $deadlineTimestampOne === false
    ||
    $deadlineTimestampTwo === false
) {

    die(
        'RecommendationCandidateRepositoryTest could not parse '
        . 'the selected gameweek deadlines.'
    );
}


$generatedEarlyOne =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampOne
        -
        7200
    );


$generatedLaterOne =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampOne
        -
        3600
    );


$generatedOlderOne =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampOne
        -
        10800
    );


$generatedOneGameweekTwo =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampTwo
        -
        3600
    );


/*
 * ============================================================
 * EVIDENCE FACTORY
 * ============================================================
 */

function buildCandidateEvidence(
    string $label,
    float $projectedPoints
): array {

    return [

        'player_projections' => [

            [
                'player_id' =>
                    101,

                'label' =>
                    $label,

                'projected_points' =>
                    $projectedPoints,

                'projected_minutes' =>
                    82.0,

                'projection_confidence' =>
                    0.78
            ]
        ],

        'starting_xi' => [

            [
                'player_id' =>
                    101,

                'label' =>
                    $label,

                'projected_points' =>
                    $projectedPoints
            ]
        ],

        'captain_recommendation' => [

            'status' =>
                'success',

            'label' =>
                $label,

            'captain' => [

                'player_id' =>
                    101,

                'captain_score' =>
                    72.0
            ]
        ],

        'transfer_recommendations' => [

            'status' =>
                'success',

            'label' =>
                $label,

            'recommendation' =>
                'Hold'
        ],

        'gameweek_decision' => [

            'status' =>
                'success',

            'label' =>
                $label,

            'overall_action' =>
                'Hold'
        ],

        'chip_recommendations' => [

            'Wildcard' => [

                'label' =>
                    $label,

                'recommendation' =>
                    'Hold',

                'confidence' =>
                    0.62
            ]
        ]
    ];
}


/*
 * ============================================================
 * CANDIDATE FACTORY
 * ============================================================
 */

function buildCandidate(
    int $gameweekId,
    int $entryId,
    string $generatedAt,
    string $deadlineTime,
    string $label,
    float $projectedPoints
): RecommendationCandidate {

    $evidence =
        buildCandidateEvidence(
            $label,
            $projectedPoints
        );


    return
        new RecommendationCandidate(
            $gameweekId,
            $entryId,
            $generatedAt,
            $deadlineTime,
            $evidence[
                'player_projections'
            ],
            $evidence[
                'starting_xi'
            ],
            $evidence[
                'captain_recommendation'
            ],
            $evidence[
                'transfer_recommendations'
            ],
            $evidence[
                'gameweek_decision'
            ],
            $evidence[
                'chip_recommendations'
            ]
        );
}


/*
 * ============================================================
 * CLEANUP
 * ============================================================
 */

$cleanupStatement =
    $db->prepare(
        "
        DELETE FROM recommendation_candidates
        WHERE entry_id IN (
            :entry_id_one,
            :entry_id_two
        )
        "
    );


$cleanupStatement->execute(
    [
        'entry_id_one' =>
            $entryIdOne,

        'entry_id_two' =>
            $entryIdTwo
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
 * A. INSERT FIRST CANDIDATE
 * ============================================================
 */

candidateRepositorySection(
    'A. Insert First Candidate'
);


$earlyCandidate =
    buildCandidate(
        $gameweekIdOne,
        $entryIdOne,
        $generatedEarlyOne,
        $deadlineOne,
        'EARLY',
        7.25
    );


$insertResult =
    $repository
        ->saveLatest(
            $gameweekIdOne,
            $earlyCandidate
        );


candidateRepositoryAssert(
    $insertResult === true,
    'First candidate is stored.'
);


/*
 * ============================================================
 * B. RETRIEVE CANDIDATE
 * ============================================================
 */

candidateRepositorySection(
    'B. Retrieve Candidate'
);


$stored =
    $repository
        ->getByEntryAndGameweek(
            $entryIdOne,
            $gameweekIdOne
        );


candidateRepositoryAssert(
    is_array(
        $stored
    ),
    'Stored candidate can be retrieved.'
);


candidateRepositoryAssert(
    (
        $stored[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    $gameweekIdOne,
    'Stored candidate preserves local gameweek ID.'
);


candidateRepositoryAssert(
    (
        $stored[
            'entry_id'
        ]
        ?? null
    )
    ===
    $entryIdOne,
    'Stored candidate preserves FPL entry ID.'
);


candidateRepositoryAssert(
    (
        $stored[
            'generated_at'
        ]
        ?? null
    )
    ===
    $generatedEarlyOne,
    'Stored candidate preserves generated timestamp.'
);


/*
 * ============================================================
 * C. EVIDENCE ROUND TRIP
 * ============================================================
 */

candidateRepositorySection(
    'C. Evidence Round Trip'
);


$earlyEvidence =
    buildCandidateEvidence(
        'EARLY',
        7.25
    );


candidateRepositoryAssert(
    (
        $stored[
            'player_projections'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'player_projections'
    ],
    'Player projections survive exact persistence round trip.'
);


candidateRepositoryAssert(
    (
        $stored[
            'starting_xi'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'starting_xi'
    ],
    'Starting XI survives exact persistence round trip.'
);


candidateRepositoryAssert(
    (
        $stored[
            'captain_recommendation'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'captain_recommendation'
    ],
    'Captain recommendation survives exact persistence round trip.'
);


candidateRepositoryAssert(
    (
        $stored[
            'transfer_recommendations'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'transfer_recommendations'
    ],
    'Transfer recommendations survive exact persistence round trip.'
);


candidateRepositoryAssert(
    (
        $stored[
            'gameweek_decision'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'gameweek_decision'
    ],
    'Gameweek Decision survives exact persistence round trip.'
);


candidateRepositoryAssert(
    (
        $stored[
            'chip_recommendations'
        ]
        ?? null
    )
    ===
    $earlyEvidence[
        'chip_recommendations'
    ],
    'Chip recommendations survive exact persistence round trip.'
);


/*
 * ============================================================
 * D. NEWER CANDIDATE REPLACES OLDER CANDIDATE
 * ============================================================
 */

candidateRepositorySection(
    'D. Newer Candidate Replaces Older Candidate'
);


$laterCandidate =
    buildCandidate(
        $gameweekIdOne,
        $entryIdOne,
        $generatedLaterOne,
        $deadlineOne,
        'LATER',
        9.75
    );


$newerResult =
    $repository
        ->saveLatest(
            $gameweekIdOne,
            $laterCandidate
        );


candidateRepositoryAssert(
    $newerResult === true,
    'Newer candidate is accepted.'
);


$storedLater =
    $repository
        ->getByEntryAndGameweek(
            $entryIdOne,
            $gameweekIdOne
        );


$laterEvidence =
    buildCandidateEvidence(
        'LATER',
        9.75
    );


candidateRepositoryAssert(
    (
        $storedLater[
            'generated_at'
        ]
        ?? null
    )
    ===
    $generatedLaterOne,
    'Newer generated timestamp replaces older timestamp.'
);


candidateRepositoryAssert(
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
    'Newer recommendation evidence replaces older evidence.'
);


/*
 * ============================================================
 * E. OLDER CANDIDATE CANNOT REPLACE NEWER CANDIDATE
 * ============================================================
 */

candidateRepositorySection(
    'E. Older Candidate Cannot Replace Newer Candidate'
);


$olderCandidate =
    buildCandidate(
        $gameweekIdOne,
        $entryIdOne,
        $generatedOlderOne,
        $deadlineOne,
        'STALE',
        2.0
    );


$olderResult =
    $repository
        ->saveLatest(
            $gameweekIdOne,
            $olderCandidate
        );


candidateRepositoryAssert(
    $olderResult === false,
    'Older candidate is rejected when a newer candidate exists.'
);


$storedAfterOlder =
    $repository
        ->getByEntryAndGameweek(
            $entryIdOne,
            $gameweekIdOne
        );


candidateRepositoryAssert(
    (
        $storedAfterOlder[
            'generated_at'
        ]
        ?? null
    )
    ===
    $generatedLaterOne,
    'Older candidate does not replace newer generated timestamp.'
);


candidateRepositoryAssert(
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
    'Older candidate does not replace newer recommendation evidence.'
);


/*
 * ============================================================
 * F. SAME TIMESTAMP DOES NOT REPLACE EXISTING CANDIDATE
 * ============================================================
 */

candidateRepositorySection(
    'F. Same Timestamp Does Not Replace Existing Candidate'
);


$sameTimeCandidate =
    buildCandidate(
        $gameweekIdOne,
        $entryIdOne,
        $generatedLaterOne,
        $deadlineOne,
        'SAME-TIME',
        50.0
    );


$sameTimeResult =
    $repository
        ->saveLatest(
            $gameweekIdOne,
            $sameTimeCandidate
        );


candidateRepositoryAssert(
    $sameTimeResult === false,
    'Candidate with identical generated timestamp is not treated as newer.'
);


$storedAfterSameTime =
    $repository
        ->getByEntryAndGameweek(
            $entryIdOne,
            $gameweekIdOne
        );


candidateRepositoryAssert(
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
    'Same-time candidate does not replace existing evidence.'
);


/*
 * ============================================================
 * G. DIFFERENT ENTRY MAY HAVE ITS OWN CANDIDATE
 * ============================================================
 */

candidateRepositorySection(
    'G. Different Entry'
);


$secondEntryCandidate =
    buildCandidate(
        $gameweekIdOne,
        $entryIdTwo,
        $generatedLaterOne,
        $deadlineOne,
        'SECOND-ENTRY',
        6.5
    );


$secondEntryResult =
    $repository
        ->saveLatest(
            $gameweekIdOne,
            $secondEntryCandidate
        );


candidateRepositoryAssert(
    $secondEntryResult === true,
    'Different entry may store a candidate for the same gameweek.'
);


$storedSecondEntry =
    $repository
        ->getByEntryAndGameweek(
            $entryIdTwo,
            $gameweekIdOne
        );


candidateRepositoryAssert(
    is_array(
        $storedSecondEntry
    ),
    'Different entry candidate can be retrieved independently.'
);


/*
 * ============================================================
 * H. SAME ENTRY MAY HAVE A DIFFERENT GAMEWEEK CANDIDATE
 * ============================================================
 */

candidateRepositorySection(
    'H. Different Gameweek'
);


$secondGameweekCandidate =
    buildCandidate(
        $gameweekIdTwo,
        $entryIdOne,
        $generatedOneGameweekTwo,
        $deadlineTwo,
        'SECOND-GAMEWEEK',
        8.5
    );


$secondGameweekResult =
    $repository
        ->saveLatest(
            $gameweekIdTwo,
            $secondGameweekCandidate
        );


candidateRepositoryAssert(
    $secondGameweekResult === true,
    'Same entry may store a candidate for another gameweek.'
);


$storedSecondGameweek =
    $repository
        ->getByEntryAndGameweek(
            $entryIdOne,
            $gameweekIdTwo
        );


candidateRepositoryAssert(
    is_array(
        $storedSecondGameweek
    ),
    'Different gameweek candidate can be retrieved independently.'
);


/*
 * ============================================================
 * I. MISSING CANDIDATE
 * ============================================================
 */

candidateRepositorySection(
    'I. Missing Candidate'
);


$missing =
    $repository
        ->getByEntryAndGameweek(
            999999999,
            $gameweekIdOne
        );


candidateRepositoryAssert(
    $missing === null,
    'Missing entry/gameweek candidate returns null.'
);


/*
 * ============================================================
 * J. ENTRY CANDIDATES ARE ORDERED BY GAMEWEEK
 * ============================================================
 */

candidateRepositorySection(
    'J. Entry Candidate History'
);


$entryCandidates =
    $repository
        ->getByEntryId(
            $entryIdOne
        );


candidateRepositoryAssert(
    count(
        $entryCandidates
    )
    ===
    2,
    'Entry candidate lookup returns both controlled gameweeks.'
);


candidateRepositoryAssert(
    (
        $entryCandidates[
            0
        ][
            'gameweek_id'
        ]
        ?? null
    )
    ===
    min(
        $gameweekIdOne,
        $gameweekIdTwo
    ),
    'Entry candidates are ordered by local gameweek ID ascending.'
);


candidateRepositoryAssert(
    (
        $entryCandidates[
            1
        ][
            'gameweek_id'
        ]
        ?? null
    )
    ===
    max(
        $gameweekIdOne,
        $gameweekIdTwo
    ),
    'Later local gameweek appears after earlier gameweek.'
);


/*
 * ============================================================
 * K. CANDIDATES READY FOR PROMOTION
 * ============================================================
 *
 * Recommendation candidates become eligible for immutable
 * promotion once their own preserved deadline has been reached.
 *
 * Promotion readiness deliberately uses the deadline stored on
 * recommendation_candidates rather than reconstructing timing
 * from the current gameweeks table.
 */

candidateRepositorySection(
    'K. Candidates Ready For Promotion'
);


/*
 * One second before the first controlled deadline:
 *
 * neither gameweek is ready.
 */

$beforeFirstDeadline =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampOne
        -
        1
    );


$readyBeforeFirstDeadline =
    $repository
        ->getReadyForPromotion(
            $beforeFirstDeadline
        );


$controlledReadyBeforeFirst =
    array_values(
        array_filter(
            $readyBeforeFirstDeadline,
            static function (
                array $candidate
            ) use (
                $entryIdOne,
                $entryIdTwo
            ): bool {

                return
                    in_array(
                        (int) (
                            $candidate[
                                'entry_id'
                            ]
                            ?? 0
                        ),
                        [
                            $entryIdOne,
                            $entryIdTwo
                        ],
                        true
                    );
            }
        )
    );


candidateRepositoryAssert(
    $controlledReadyBeforeFirst === [],
    'Candidates are not ready before their preserved deadline.'
);


/*
 * Exactly at the first deadline:
 *
 * both controlled GW1 candidates are ready.
 *
 * This defines the production boundary as:
 *
 * deadline_time <= supplied timestamp
 */

$readyAtFirstDeadline =
    $repository
        ->getReadyForPromotion(
            $deadlineOne
        );


$controlledReadyAtFirst =
    array_values(
        array_filter(
            $readyAtFirstDeadline,
            static function (
                array $candidate
            ) use (
                $entryIdOne,
                $entryIdTwo
            ): bool {

                return
                    in_array(
                        (int) (
                            $candidate[
                                'entry_id'
                            ]
                            ?? 0
                        ),
                        [
                            $entryIdOne,
                            $entryIdTwo
                        ],
                        true
                    );
            }
        )
    );


candidateRepositoryAssert(
    count(
        $controlledReadyAtFirst
    )
    === 2,
    'Candidates become ready exactly at their preserved deadline.'
);


candidateRepositoryAssert(
    array_reduce(
        $controlledReadyAtFirst,
        static function (
            bool $valid,
            array $candidate
        ) use (
            $gameweekIdOne
        ): bool {

            return
                $valid
                &&
                (
                    (int) (
                        $candidate[
                            'gameweek_id'
                        ]
                        ?? 0
                    )
                    ===
                    $gameweekIdOne
                );
        },
        true
    ),
    'Only first-gameweek controlled candidates are ready at the first deadline.'
);


/*
 * The repository must return complete decoded evidence because
 * the promotion service copies the candidate directly into the
 * immutable RecommendationSnapshot.
 */

$readyEntryOne =
    null;


foreach (
    $controlledReadyAtFirst
    as $candidate
) {

    if (
        (int) (
            $candidate[
                'entry_id'
            ]
            ?? 0
        )
        ===
        $entryIdOne
    ) {

        $readyEntryOne =
            $candidate;

        break;
    }
}


candidateRepositoryAssert(
    is_array(
        $readyEntryOne
    ),
    'Ready candidate preserves entry identity.'
);


candidateRepositoryAssert(
    (
        $readyEntryOne[
            'player_projections'
        ]
        ?? null
    )
    ===
    $laterEvidence[
        'player_projections'
    ],
    'Ready candidate preserves latest player projection evidence.'
);


candidateRepositoryAssert(
    (
        $readyEntryOne[
            'generated_at'
        ]
        ?? null
    )
    ===
    $generatedLaterOne,
    'Ready candidate preserves latest generated timestamp.'
);


/*
 * One second before the second deadline:
 *
 * GW1 remains ready but GW2 must still be excluded.
 */

$beforeSecondDeadline =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestampTwo
        -
        1
    );


$readyBeforeSecondDeadline =
    $repository
        ->getReadyForPromotion(
            $beforeSecondDeadline
        );


$controlledReadyBeforeSecond =
    array_values(
        array_filter(
            $readyBeforeSecondDeadline,
            static function (
                array $candidate
            ) use (
                $entryIdOne,
                $entryIdTwo
            ): bool {

                return
                    in_array(
                        (int) (
                            $candidate[
                                'entry_id'
                            ]
                            ?? 0
                        ),
                        [
                            $entryIdOne,
                            $entryIdTwo
                        ],
                        true
                    );
            }
        )
    );


$controlledSecondGameweekBeforeDeadline =
    array_values(
        array_filter(
            $controlledReadyBeforeSecond,
            static function (
                array $candidate
            ) use (
                $gameweekIdTwo
            ): bool {

                return
                    (int) (
                        $candidate[
                            'gameweek_id'
                        ]
                        ?? 0
                    )
                    ===
                    $gameweekIdTwo;
            }
        )
    );


candidateRepositoryAssert(
    $controlledSecondGameweekBeforeDeadline === [],
    'Later-gameweek candidate is not ready before its preserved deadline.'
);


/*
 * Exactly at the second deadline all three controlled
 * candidates are now eligible.
 */

$readyAtSecondDeadline =
    $repository
        ->getReadyForPromotion(
            $deadlineTwo
        );


$controlledReadyAtSecond =
    array_values(
        array_filter(
            $readyAtSecondDeadline,
            static function (
                array $candidate
            ) use (
                $entryIdOne,
                $entryIdTwo
            ): bool {

                return
                    in_array(
                        (int) (
                            $candidate[
                                'entry_id'
                            ]
                            ?? 0
                        ),
                        [
                            $entryIdOne,
                            $entryIdTwo
                        ],
                        true
                    );
            }
        )
    );


candidateRepositoryAssert(
    count(
        $controlledReadyAtSecond
    )
    === 3,
    'All controlled candidates are ready once both deadlines have been reached.'
);


/*
 * ============================================================
 * L. PROMOTION READINESS ORDER
 * ============================================================
 */

candidateRepositorySection(
    'L. Promotion Readiness Order'
);


$controlledReadyOrder =
    array_map(
        static function (
            array $candidate
        ): string {

            return
                (string) (
                    $candidate[
                        'deadline_time'
                    ]
                    ?? ''
                )
                . '|'
                .
                (string) (
                    $candidate[
                        'gameweek_id'
                    ]
                    ?? ''
                )
                . '|'
                .
                (string) (
                    $candidate[
                        'entry_id'
                    ]
                    ?? ''
                );
        },
        $controlledReadyAtSecond
    );


$expectedReadyOrder =
    $controlledReadyOrder;


sort(
    $expectedReadyOrder,
    SORT_STRING
);


candidateRepositoryAssert(
    $controlledReadyOrder
    ===
    $expectedReadyOrder,
    'Promotion-ready candidates are ordered by deadline, gameweek and entry.'
);


/*
 * ============================================================
 * M. INVALID PROMOTION READINESS TIMESTAMP
 * ============================================================
 */

candidateRepositorySection(
    'M. Invalid Promotion Readiness Timestamp'
);


$invalidTimestampRejected =
    false;


try {

    $repository
        ->getReadyForPromotion(
            'not-a-valid-timestamp'
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidTimestampRejected =
        true;
}


candidateRepositoryAssert(
    $invalidTimestampRejected,
    'Invalid promotion-readiness timestamp is rejected.'
);



/*
 * ============================================================
 * CLEANUP
 * ============================================================
 */

$cleanupStatement->execute(
    [
        'entry_id_one' =>
            $entryIdOne,

        'entry_id_two' =>
            $entryIdTwo
    ]
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

candidateRepositorySection(
    'Recommendation Candidate Repository Test Summary'
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