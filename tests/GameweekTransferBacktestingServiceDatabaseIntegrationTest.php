<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Transfer Backtesting Database Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekTransferBacktestingDatabaseIntegrationTestResult(
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


/*
 * ============================================================
 * REAL DATABASE
 * ============================================================
 */

$database =
    new Database();


$pdo =
    $database->getConnection();


gameweekTransferBacktestingDatabaseIntegrationTestResult(
    $pdo instanceof PDO,
    'Real database connection is available.'
);


/*
 * ============================================================
 * REAL REPOSITORIES AND SERVICES
 * ============================================================
 */

$gameweekRepository =
    new GameweekRepository(
        $pdo
    );


$recommendationSnapshotRepository =
    new RecommendationSnapshotRepository(
        $pdo
    );


$playerFixtureHistoryRepository =
    new PlayerFixtureHistoryRepository(
        $pdo
    );


$outcomeAvailability =
    new PlayerGameweekOutcomeAvailability();


$playerGameweekOutcomeService =
    new PlayerGameweekOutcomeService(
        $playerFixtureHistoryRepository
    );


$gameweekBacktestingEvidenceService =
    new GameweekBacktestingEvidenceService(
        $gameweekRepository,
        $outcomeAvailability,
        $recommendationSnapshotRepository,
        $playerGameweekOutcomeService
    );


$transferBacktestingService =
    new TransferBacktestingService();


$gameweekTransferBacktestingService =
    new GameweekTransferBacktestingService(
        $transferBacktestingService
    );


gameweekTransferBacktestingDatabaseIntegrationTestResult(
    $gameweekBacktestingEvidenceService
        instanceof GameweekBacktestingEvidenceService,
    'Real historical evidence service can be constructed.'
);


gameweekTransferBacktestingDatabaseIntegrationTestResult(
    $transferBacktestingService
        instanceof TransferBacktestingService,
    'Real transfer backtesting service can be constructed.'
);


gameweekTransferBacktestingDatabaseIntegrationTestResult(
    $gameweekTransferBacktestingService
        instanceof GameweekTransferBacktestingService,
    'Real gameweek transfer backtesting service can be constructed.'
);


/*
 * ============================================================
 * DISCOVER AUTHORITATIVE COMPLETED GAMEWEEK
 * ============================================================
 */

$statement =
    $pdo->query(
        "
        SELECT
            g.id,
            g.fpl_gameweek_id,
            g.name,
            g.deadline_time,
            g.finished,
            g.data_checked
        FROM
            gameweeks g
        WHERE
            g.finished = 1
            AND
            g.data_checked = 1
            AND EXISTS (
                SELECT
                    1
                FROM
                    player_fixture_history pfh
                WHERE
                    pfh.gameweek_id = g.id
            )
        ORDER BY
            g.id ASC
        LIMIT 1
        "
    );


$gameweek =
    $statement->fetch(
        PDO::FETCH_ASSOC
    );


gameweekTransferBacktestingDatabaseIntegrationTestResult(
    is_array(
        $gameweek
    ),
    'An authoritative completed gameweek with real player outcome evidence is available.'
);


if (
    !is_array(
        $gameweek
    )
) {

    echo "<br>";
    echo "============================================<br>";
    echo "TEST SUMMARY<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "RESULT: TESTS FAILED ❌<br>";

    exit;
}


$gameweekId =
    (int) $gameweek[
        'id'
    ];


/*
 * ============================================================
 * LOAD GENUINE COMPLETED-GAMEWEEK OUTCOMES
 * ============================================================
 */

$realOutcomes =
    $playerGameweekOutcomeService
        ->getByGameweekId(
            $gameweekId
        );


gameweekTransferBacktestingDatabaseIntegrationTestResult(
    count(
        $realOutcomes
    )
    >= 3,
    'At least three genuine player outcomes are available for transfer backtesting.'
);


if (
    count(
        $realOutcomes
    )
    <
    3
) {

    echo "<br>";
    echo "============================================<br>";
    echo "TEST SUMMARY<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "RESULT: TESTS FAILED ❌<br>";

    exit;
}


/*
 * ============================================================
 * SELECT GENUINE PLAYER IDENTITIES
 * ============================================================
 *
 * Player identities and realised outcomes are genuine.
 *
 * Transfer recommendation metadata is synthetic deterministic
 * historical evidence.
 *
 * We require:
 *
 * 1. original outgoing player
 * 2. original recommended incoming player
 * 3. attempted later replacement used to prove immutability
 * ============================================================
 */

$selectedOutcomes =
    array_slice(
        $realOutcomes,
        0,
        3
    );


$outgoingOutcome =
    $selectedOutcomes[
        0
    ];


$incomingOutcome =
    $selectedOutcomes[
        1
    ];


$laterIncomingOutcome =
    $selectedOutcomes[
        2
    ];


$outgoingPlayerId =
    (int) $outgoingOutcome[
        'player_id'
    ];


$incomingPlayerId =
    (int) $incomingOutcome[
        'player_id'
    ];


$laterIncomingPlayerId =
    (int) $laterIncomingOutcome[
        'player_id'
    ];


gameweekTransferBacktestingDatabaseIntegrationTestResult(
    $outgoingPlayerId > 0
    &&
    $incomingPlayerId > 0
    &&
    $laterIncomingPlayerId > 0,
    'Selected transfer players use genuine positive player identities.'
);


gameweekTransferBacktestingDatabaseIntegrationTestResult(
    count(
        array_unique(
            [
                $outgoingPlayerId,
                $incomingPlayerId,
                $laterIncomingPlayerId
            ]
        )
    )
    ===
    3,
    'Selected outgoing and incoming player identities are distinct.'
);


/*
 * ============================================================
 * BUILD ORIGINAL PRESERVED TRANSFER INTELLIGENCE
 * ============================================================
 */

$transferRecommendations =
    [
        'analysis' =>
            [
                'validation' =>
                    [
                        'is_valid' =>
                            true
                    ],

                'bank' =>
                    1.5
            ],

        'recommendations' =>
            [
                'status' =>
                    'success',

                'bank' =>
                    1.5,

                'priority_limit' =>
                    5,

                'replacement_limit' =>
                    5,

                'players_considered' =>
                    1,

                'recommendations' =>
                    [
                        [
                            'outgoing' =>
                                [
                                    'player_id' =>
                                        $outgoingPlayerId,

                                    'name' =>
                                        'Database Integration Outgoing '
                                        . $outgoingPlayerId,

                                    'position' =>
                                        'MID',

                                    'price' =>
                                        7.5
                                ],

                            'transfer_priority' =>
                                78.0,

                            'priority_label' =>
                                'High',

                            'available_budget' =>
                                9.0,

                            'legal_candidate_count' =>
                                1,

                            'replacement_count' =>
                                1,

                            'replacements' =>
                                [
                                    [
                                        'player' =>
                                            [
                                                'player_id' =>
                                                    $incomingPlayerId,

                                                'name' =>
                                                    'Database Integration Incoming '
                                                    . $incomingPlayerId,

                                                'position' =>
                                                    'MID',

                                                'price' =>
                                                    8.0
                                            ],

                                        'decision' =>
                                            [
                                                'decision_score' =>
                                                    76.0,

                                                'decision_type' =>
                                                    'Upgrade'
                                            ],

                                        'decision_score' =>
                                            76.0,

                                        'decision_type' =>
                                            'Upgrade',

                                        'budget_after' =>
                                            1.0,

                                        'rank' =>
                                            1
                                    ]
                                ]
                        ]
                    ]
            ]
    ];


/*
 * ============================================================
 * INDEPENDENT EXPECTED REALISED RESULT
 * ============================================================
 */

$expectedOutgoingPoints =
    $outgoingOutcome[
        'total_points'
    ];


$expectedIncomingPoints =
    $incomingOutcome[
        'total_points'
    ];


$expectedTransferPointsGain =
    $expectedIncomingPoints
    -
    $expectedOutgoingPoints;


/*
 * ============================================================
 * SYNTHETIC ENTRY AND SNAPSHOT TIMESTAMPS
 * ============================================================
 */

$syntheticEntryId =
    935010004;


$deadlineTime =
    (string) $gameweek[
        'deadline_time'
    ];


$deadlineTimestamp =
    strtotime(
        $deadlineTime
    );


gameweekTransferBacktestingDatabaseIntegrationTestResult(
    $deadlineTimestamp !== false,
    'Real gameweek deadline can be parsed for immutable snapshot construction.'
);


$capturedAt =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestamp
        -
        3600
    );


/*
 * ============================================================
 * ORIGINAL IMMUTABLE SNAPSHOT
 * ============================================================
 */

$snapshot =
    new RecommendationSnapshot(
        (int) $gameweek[
            'fpl_gameweek_id'
        ],
        $syntheticEntryId,
        $capturedAt,
        $deadlineTime,
        [],
        [],
        [],
        $transferRecommendations,
        [],
        []
    );


gameweekTransferBacktestingDatabaseIntegrationTestResult(
    $snapshot instanceof RecommendationSnapshot,
    'Synthetic immutable recommendation snapshot containing transfer evidence can be constructed.'
);


/*
 * ============================================================
 * LATER CONFLICTING TRANSFER INTELLIGENCE
 * ============================================================
 *
 * This represents later intelligence trying to rewrite the same
 * historical gameweek recommendation.
 *
 * The incoming player is deliberately changed.
 * ============================================================
 */

$laterTransferRecommendations =
    $transferRecommendations;


$laterTransferRecommendations[
    'recommendations'
][
    'recommendations'
][
    0
][
    'replacements'
][
    0
][
    'player'
][
    'player_id'
] =
    $laterIncomingPlayerId;


$laterTransferRecommendations[
    'recommendations'
][
    'recommendations'
][
    0
][
    'replacements'
][
    0
][
    'player'
][
    'name'
] =
    'Later Conflicting Incoming '
    . $laterIncomingPlayerId;


$laterSnapshot =
    new RecommendationSnapshot(
        (int) $gameweek[
            'fpl_gameweek_id'
        ],
        $syntheticEntryId,
        $capturedAt,
        $deadlineTime,
        [],
        [],
        [],
        $laterTransferRecommendations,
        [],
        []
    );


gameweekTransferBacktestingDatabaseIntegrationTestResult(
    $laterSnapshot instanceof RecommendationSnapshot,
    'Conflicting later recommendation snapshot can be constructed for immutability test.'
);


/*
 * ============================================================
 * RUN INSIDE TRANSACTION
 * ============================================================
 */

$pdo->beginTransaction();


try {

    /*
     * Remove any residue for this synthetic entry.
     *
     * The deletion itself is inside the transaction and will
     * therefore also be rolled back.
     */

    $cleanupStatement =
        $pdo->prepare(
            "
            DELETE FROM
                recommendation_snapshots
            WHERE
                gameweek_id = :gameweek_id
                AND
                entry_id = :entry_id
            "
        );


    $cleanupStatement->execute(
        [
            ':gameweek_id' =>
                $gameweekId,

            ':entry_id' =>
                $syntheticEntryId
        ]
    );


    /*
     * ========================================================
     * SCENARIO A
     * MISSING HISTORICAL SNAPSHOT
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario A: Missing Historical Snapshot<br>";
    echo "============================================<br>";


    $evidenceBeforeInsert =
        $gameweekBacktestingEvidenceService
            ->getEvidence(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $evidenceBeforeInsert[
                'status'
            ]
            ?? null
        )
        ===
        'Incomplete',
        'Authoritative gameweek without recommendation snapshot is Incomplete.'
    );


    $transferResultBeforeInsert =
        $gameweekTransferBacktestingService
            ->evaluate(
                $evidenceBeforeInsert
            );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferResultBeforeInsert[
                'status'
            ]
            ?? null
        )
        ===
        'Incomplete',
        'Transfer backtesting propagates missing historical recommendation evidence as Incomplete.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferResultBeforeInsert[
                'transfer_evaluation'
            ]
            ?? null
        )
        ===
        [],
        'No transfer evaluation is manufactured before historical snapshot insertion.'
    );


    /*
     * ========================================================
     * INSERT ORIGINAL IMMUTABLE SNAPSHOT
     * ========================================================
     */

    $inserted =
        $recommendationSnapshotRepository
            ->insertIfAbsent(
                $gameweekId,
                $snapshot
            );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        $inserted === true,
        'Synthetic recommendation snapshot is inserted once.'
    );


    /*
     * ========================================================
     * SCENARIO B
     * REAL HISTORICAL EVIDENCE ASSEMBLY
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario B: Real Historical Evidence Assembly<br>";
    echo "============================================<br>";


    $evidence =
        $gameweekBacktestingEvidenceService
            ->getEvidence(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $evidence[
                'status'
            ]
            ?? null
        )
        ===
        'Ready',
        'Real database-backed historical evidence becomes Ready.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $evidence[
                'entry_id'
            ]
            ?? null
        )
        ===
        $syntheticEntryId,
        'Real historical evidence preserves synthetic entry identity.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $evidence[
                'gameweek_id'
            ]
            ?? null
        )
        ===
        $gameweekId,
        'Real historical evidence preserves local gameweek identity.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        !empty(
            $evidence[
                'recommendation_snapshot'
            ][
                'transfer_recommendations'
            ]
            ?? []
        ),
        'Real historical evidence contains preserved transfer recommendation evidence.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        !empty(
            $evidence[
                'player_outcomes'
            ]
            ?? []
        ),
        'Real historical evidence contains authoritative completed-gameweek player outcomes.'
    );


    /*
     * ========================================================
     * SCENARIO C
     * FULL DATABASE-BACKED TRANSFER EVALUATION
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario C: Full Database-Backed Transfer Evaluation<br>";
    echo "============================================<br>";


    $transferResult =
        $gameweekTransferBacktestingService
            ->evaluate(
                $evidence
            );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferResult[
                'status'
            ]
            ?? null
        )
        ===
        'Ready',
        'Full database-backed transfer backtesting becomes Ready.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        array_key_exists(
            'reason',
            $transferResult
        )
        &&
        $transferResult[
            'reason'
        ]
        ===
        null,
        'Successful database-backed transfer evaluation has no failure reason.'
    );


    $transferEvaluation =
        $transferResult[
            'transfer_evaluation'
        ]
        ?? [];


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferEvaluation[
                'outgoing_player_id'
            ]
            ?? null
        )
        ===
        $outgoingPlayerId,
        'Database-backed evaluation uses preserved outgoing player identity.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferEvaluation[
                'incoming_player_id'
            ]
            ?? null
        )
        ===
        $incomingPlayerId,
        'Database-backed evaluation uses preserved incoming player identity.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferEvaluation[
                'outgoing_actual_points'
            ]
            ?? null
        )
        ===
        $expectedOutgoingPoints,
        'Outgoing realised points match genuine completed-gameweek evidence.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferEvaluation[
                'incoming_actual_points'
            ]
            ?? null
        )
        ===
        $expectedIncomingPoints,
        'Incoming realised points match genuine completed-gameweek evidence.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferEvaluation[
                'transfer_points_gain'
            ]
            ?? null
        )
        ===
        $expectedTransferPointsGain,
        'Transfer points gain matches independent genuine-outcome calculation.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferEvaluation[
                'transfer_points_gain'
            ]
            ?? null
        )
        ===
        (
            (
                $transferEvaluation[
                    'incoming_actual_points'
                ]
                ?? 0
            )
            -
            (
                $transferEvaluation[
                    'outgoing_actual_points'
                ]
                ?? 0
            )
        ),
        'Database-backed transfer gain remains the signed arithmetic difference.'
    );


    /*
     * ========================================================
     * SCENARIO D
     * IMMUTABLE SECOND INSERT
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario D: Immutable Second Insert<br>";
    echo "============================================<br>";


    $secondInserted =
        $recommendationSnapshotRepository
            ->insertIfAbsent(
                $gameweekId,
                $laterSnapshot
            );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        $secondInserted === false,
        'Second recommendation snapshot insert is rejected by immutable history.'
    );


    /*
     * ========================================================
     * SCENARIO E
     * HISTORICAL TRANSFER CANNOT BE REWRITTEN
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario E: Historical Transfer Cannot Be Rewritten<br>";
    echo "============================================<br>";


    $evidenceAfterSecondInsert =
        $gameweekBacktestingEvidenceService
            ->getEvidence(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $evidenceAfterSecondInsert[
                'status'
            ]
            ?? null
        )
        ===
        'Ready',
        'Historical evidence remains Ready after rejected rewrite attempt.'
    );


    $preservedIncomingPlayerId =
        $evidenceAfterSecondInsert[
            'recommendation_snapshot'
        ][
            'transfer_recommendations'
        ][
            'recommendations'
        ][
            'recommendations'
        ][
            0
        ][
            'replacements'
        ][
            0
        ][
            'player'
        ][
            'player_id'
        ]
        ?? null;


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        $preservedIncomingPlayerId
        ===
        $incomingPlayerId,
        'Original incoming player remains preserved after rejected rewrite attempt.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        $preservedIncomingPlayerId
        !==
        $laterIncomingPlayerId,
        'Later conflicting incoming player does not replace immutable historical recommendation.'
    );


    $transferResultAfterSecondInsert =
        $gameweekTransferBacktestingService
            ->evaluate(
                $evidenceAfterSecondInsert
            );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferResultAfterSecondInsert[
                'transfer_evaluation'
            ][
                'incoming_player_id'
            ]
            ?? null
        )
        ===
        $incomingPlayerId,
        'Backtesting continues to evaluate original preserved incoming player.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferResultAfterSecondInsert[
                'transfer_evaluation'
            ][
                'incoming_player_id'
            ]
            ?? null
        )
        !==
        $laterIncomingPlayerId,
        'Backtesting does not evaluate later rejected incoming recommendation.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        (
            $transferResultAfterSecondInsert[
                'transfer_evaluation'
            ][
                'transfer_points_gain'
            ]
            ?? null
        )
        ===
        $expectedTransferPointsGain,
        'Historical realised transfer comparison remains unchanged after rejected rewrite.'
    );


    /*
     * ========================================================
     * SCENARIO F
     * REAL OUTCOME EVIDENCE REMAINS UNCHANGED
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario F: Real Outcome Evidence Remains Unchanged<br>";
    echo "============================================<br>";


    $realOutcomesAfterEvaluation =
        $playerGameweekOutcomeService
            ->getByGameweekId(
                $gameweekId
            );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        $realOutcomesAfterEvaluation
        ===
        $realOutcomes,
        'Transfer backtesting does not mutate genuine player outcome evidence.'
    );


    /*
     * ========================================================
     * SCENARIO G
     * BACKTESTING SCOPE BOUNDARY
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario G: Backtesting Scope Boundary<br>";
    echo "============================================<br>";


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'accuracy_score',
            $transferResult
        ),
        'Database-backed transfer backtesting does not manufacture an accuracy score.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'overall_score',
            $transferResult
        ),
        'Database-backed transfer backtesting does not manufacture an overall score.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'transfer_hit',
            $transferResult
        ),
        'Database-backed transfer backtesting does not manufacture a transfer hit.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'net_points_gain',
            $transferResult
        ),
        'Database-backed transfer backtesting does not manufacture hit-adjusted points.'
    );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'decision_correct',
            $transferResult
        ),
        'Database-backed transfer backtesting does not judge Make / Consider / Hold.'
    );


    /*
     * ========================================================
     * ROLLBACK
     * ========================================================
     */

    $pdo->rollBack();


    /*
     * ========================================================
     * SCENARIO H
     * TRANSACTION ROLLBACK
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario H: Transaction Rollback<br>";
    echo "============================================<br>";


    $snapshotAfterRollback =
        $recommendationSnapshotRepository
            ->getByEntryAndGameweek(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        $snapshotAfterRollback === null,
        'Synthetic recommendation snapshot does not remain after transaction rollback.'
    );


    $realOutcomesAfterRollback =
        $playerGameweekOutcomeService
            ->getByGameweekId(
                $gameweekId
            );


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        $realOutcomesAfterRollback
        ===
        $realOutcomes,
        'Transaction rollback leaves genuine player outcome evidence unchanged.'
    );

} catch (
    Throwable $exception
) {

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    gameweekTransferBacktestingDatabaseIntegrationTestResult(
        false,
        'Unexpected exception: '
        . $exception->getMessage()
    );
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "TEST SUMMARY<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if (
    $failed === 0
) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}