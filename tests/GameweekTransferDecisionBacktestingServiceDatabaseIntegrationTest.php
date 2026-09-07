<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Transfer Decision Backtesting Database Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekTransferDecisionDatabaseTestResult(
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


gameweekTransferDecisionDatabaseTestResult(
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


$transferDecisionBacktestingService =
    new TransferDecisionBacktestingService();


$gameweekTransferDecisionBacktestingService =
    new GameweekTransferDecisionBacktestingService(
        $transferDecisionBacktestingService
    );


gameweekTransferDecisionDatabaseTestResult(
    $gameweekBacktestingEvidenceService
        instanceof GameweekBacktestingEvidenceService,
    'Real historical evidence service can be constructed.'
);


gameweekTransferDecisionDatabaseTestResult(
    $gameweekTransferBacktestingService
        instanceof GameweekTransferBacktestingService,
    'Real factual transfer backtesting chain can be constructed.'
);


gameweekTransferDecisionDatabaseTestResult(
    $gameweekTransferDecisionBacktestingService
        instanceof GameweekTransferDecisionBacktestingService,
    'Real transfer decision backtesting chain can be constructed.'
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


gameweekTransferDecisionDatabaseTestResult(
    is_array(
        $gameweek
    ),
    'An authoritative completed gameweek with real player outcomes is available.'
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


gameweekTransferDecisionDatabaseTestResult(
    count(
        $realOutcomes
    )
    >=
    2,
    'At least two genuine player outcomes are available.'
);


if (
    count(
        $realOutcomes
    )
    <
    2
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
 * CHOOSE A DETERMINISTIC OUTGOING / INCOMING PAIR
 * ============================================================
 *
 * Sort by realised points so the incoming player has at least
 * as many points as the outgoing player.
 *
 * We then prefer a strictly positive realised gain when the
 * genuine completed-gameweek evidence contains one.
 * ============================================================
 */

$sortedOutcomes =
    $realOutcomes;


usort(
    $sortedOutcomes,
    static function (
        array $a,
        array $b
    ): int {

        $pointsComparison =
            (
                (float) (
                    $a[
                        'total_points'
                    ]
                    ?? 0
                )
            )
            <=>
            (
                (float) (
                    $b[
                        'total_points'
                    ]
                    ?? 0
                )
            );


        if (
            $pointsComparison !==
            0
        ) {

            return $pointsComparison;
        }


        return
            (
                (int) (
                    $a[
                        'player_id'
                    ]
                    ?? 0
                )
            )
            <=>
            (
                (int) (
                    $b[
                        'player_id'
                    ]
                    ?? 0
                )
            );
    }
);


$outgoingOutcome =
    $sortedOutcomes[
        0
    ];


$incomingOutcome =
    $sortedOutcomes[
        count(
            $sortedOutcomes
        )
        -
        1
    ];


$outgoingPlayerId =
    (int) $outgoingOutcome[
        'player_id'
    ];


$incomingPlayerId =
    (int) $incomingOutcome[
        'player_id'
    ];


$outgoingActualPoints =
    $outgoingOutcome[
        'total_points'
    ];


$incomingActualPoints =
    $incomingOutcome[
        'total_points'
    ];


$expectedTransferGain =
    $incomingActualPoints
    -
    $outgoingActualPoints;


gameweekTransferDecisionDatabaseTestResult(
    $outgoingPlayerId > 0
    &&
    $incomingPlayerId > 0
    &&
    $outgoingPlayerId !== $incomingPlayerId,
    'Genuine outgoing and incoming players are distinct positive identities.'
);


gameweekTransferDecisionDatabaseTestResult(
    $expectedTransferGain >= 0,
    'Selected genuine incoming player does not score fewer points than outgoing player.'
);


/*
 * ============================================================
 * EXPECTED DECISION SUPPORT
 * ============================================================
 *
 * Make Transfer requires a strictly positive realised gain.
 * A genuine zero-point tie therefore remains Not Supported.
 * ============================================================
 */

$expectedSupportStatus =
    $expectedTransferGain > 0
        ? 'Supported'
        : 'Not Supported';


/*
 * ============================================================
 * SYNTHETIC PRESERVED TRANSFER RECOMMENDATION
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
                                        'Decision DB Outgoing '
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
                                                    'Decision DB Incoming '
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
 * PRESERVED GAMEWEEK DECISION
 * ============================================================
 *
 * overall_action is deliberately different from transfer_advice.
 *
 * The backtester must evaluate transfer_advice.action and must
 * not reinterpret Urgent Action as the transfer recommendation.
 * ============================================================
 */

$gameweekDecision =
    [
        'status' =>
            'success',

        'message' =>
            'Synthetic database integration decision.',

        'overall_action' =>
            'Urgent Action',

        'formation' =>
            '3-4-3',

        'starting_xi_score' =>
            70.0,

        'bench_score' =>
            20.0,

        'starting_xi' =>
            [],

        'bench' =>
            [],

        'captain' =>
            [],

        'vice_captain' =>
            [],

        'transfer_advice' =>
            [
                'action' =>
                    'Make Transfer',

                'priority' =>
                    'High',

                'score' =>
                    78.0,

                'recommendations' =>
                    []
            ],

        'squad_risks' =>
            [],

        'key_insights' =>
            []
    ];


/*
 * ============================================================
 * SYNTHETIC ENTRY AND SNAPSHOT TIMESTAMPS
 * ============================================================
 */

$syntheticEntryId =
    935010006;


$deadlineTime =
    (string) $gameweek[
        'deadline_time'
    ];


$deadlineTimestamp =
    strtotime(
        $deadlineTime
    );


gameweekTransferDecisionDatabaseTestResult(
    $deadlineTimestamp !== false,
    'Real gameweek deadline can be parsed.'
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
 * IMMUTABLE RECOMMENDATION SNAPSHOT
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
        $gameweekDecision,
        []
    );


gameweekTransferDecisionDatabaseTestResult(
    $snapshot
        instanceof RecommendationSnapshot,
    'Synthetic immutable recommendation snapshot containing transfer decision evidence can be constructed.'
);


/*
 * ============================================================
 * RUN INSIDE TRANSACTION
 * ============================================================
 */

$pdo->beginTransaction();


try {

    /*
     * ========================================================
     * CLEAN SYNTHETIC ENTRY INSIDE TRANSACTION
     * ========================================================
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
     * BEFORE SNAPSHOT INSERTION
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


    gameweekTransferDecisionDatabaseTestResult(
        (
            $evidenceBeforeInsert[
                'status'
            ]
            ?? null
        )
        ===
        'Incomplete',
        'Authoritative gameweek without immutable recommendation snapshot is Incomplete.'
    );


    $transferBeforeInsert =
        $gameweekTransferBacktestingService
            ->evaluate(
                $evidenceBeforeInsert
            );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $transferBeforeInsert[
                'status'
            ]
            ?? null
        )
        ===
        'Incomplete',
        'Factual transfer backtesting remains Incomplete before snapshot insertion.'
    );


    $decisionBeforeInsert =
        $gameweekTransferDecisionBacktestingService
            ->evaluate(
                $evidenceBeforeInsert,
                $transferBeforeInsert
            );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionBeforeInsert[
                'status'
            ]
            ?? null
        )
        ===
        'Incomplete',
        'Transfer decision backtesting remains Incomplete before snapshot insertion.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionBeforeInsert[
                'decision_evaluation'
            ]
            ?? null
        )
        ===
        [],
        'No transfer decision evaluation is manufactured before snapshot insertion.'
    );


    /*
     * ========================================================
     * INSERT IMMUTABLE SNAPSHOT
     * ========================================================
     */

    $inserted =
        $recommendationSnapshotRepository
            ->insertIfAbsent(
                $gameweekId,
                $snapshot
            );


    gameweekTransferDecisionDatabaseTestResult(
        $inserted === true,
        'Synthetic recommendation snapshot is inserted once.'
    );


    /*
     * ========================================================
     * SCENARIO B
     * DATABASE ROUND TRIP
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario B: Database Historical Evidence Round Trip<br>";
    echo "============================================<br>";


    $storedSnapshot =
        $recommendationSnapshotRepository
            ->getByEntryAndGameweek(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekTransferDecisionDatabaseTestResult(
        is_array(
            $storedSnapshot
        ),
        'Inserted recommendation snapshot can be read from real database.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $storedSnapshot[
                'gameweek_decision'
            ][
                'transfer_advice'
            ][
                'action'
            ]
            ?? null
        )
        ===
        'Make Transfer',
        'Preserved Make Transfer advice survives database round trip.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $storedSnapshot[
                'gameweek_decision'
            ][
                'transfer_advice'
            ][
                'priority'
            ]
            ?? null
        )
        ===
        'High',
        'Preserved transfer priority survives database round trip.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $storedSnapshot[
                'gameweek_decision'
            ][
                'overall_action'
            ]
            ?? null
        )
        ===
        'Urgent Action',
        'Distinct overall action also survives database round trip.'
    );


    /*
     * ========================================================
     * SCENARIO C
     * REAL HISTORICAL EVIDENCE ASSEMBLY
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario C: Real Historical Evidence Assembly<br>";
    echo "============================================<br>";


    $evidence =
        $gameweekBacktestingEvidenceService
            ->getEvidence(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $evidence[
                'status'
            ]
            ?? null
        )
        ===
        'Ready',
        'Real historical evidence service returns Ready after snapshot insertion.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $evidence[
                'entry_id'
            ]
            ?? null
        )
        ===
        $syntheticEntryId,
        'Historical evidence preserves synthetic entry identity.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $evidence[
                'gameweek_id'
            ]
            ?? null
        )
        ===
        $gameweekId,
        'Historical evidence preserves real local gameweek identity.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        !empty(
            $evidence[
                'player_outcomes'
            ]
            ?? []
        ),
        'Historical evidence contains genuine completed-gameweek player outcomes.'
    );


    /*
     * ========================================================
     * SCENARIO D
     * REAL FACTUAL TRANSFER BACKTESTING
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario D: Real Factual Transfer Backtesting<br>";
    echo "============================================<br>";


    $transferResult =
        $gameweekTransferBacktestingService
            ->evaluate(
                $evidence
            );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $transferResult[
                'status'
            ]
            ?? null
        )
        ===
        'Ready',
        'Real factual transfer backtesting is Ready.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $transferResult[
                'transfer_evaluation'
            ][
                'outgoing_player_id'
            ]
            ?? null
        )
        ===
        $outgoingPlayerId,
        'Factual transfer evaluation uses preserved outgoing player.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $transferResult[
                'transfer_evaluation'
            ][
                'incoming_player_id'
            ]
            ?? null
        )
        ===
        $incomingPlayerId,
        'Factual transfer evaluation uses preserved incoming player.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $transferResult[
                'transfer_evaluation'
            ][
                'outgoing_actual_points'
            ]
            ?? null
        )
        ==
        $outgoingActualPoints,
        'Factual transfer evaluation uses genuine outgoing realised points.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $transferResult[
                'transfer_evaluation'
            ][
                'incoming_actual_points'
            ]
            ?? null
        )
        ==
        $incomingActualPoints,
        'Factual transfer evaluation uses genuine incoming realised points.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $transferResult[
                'transfer_evaluation'
            ][
                'transfer_points_gain'
            ]
            ?? null
        )
        ==
        $expectedTransferGain,
        'Factual transfer evaluation calculates expected genuine realised gain.'
    );


    /*
     * ========================================================
     * SCENARIO E
     * REAL TRANSFER DECISION BACKTESTING
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario E: Real Transfer Decision Backtesting<br>";
    echo "============================================<br>";


    $decisionResult =
        $gameweekTransferDecisionBacktestingService
            ->evaluate(
                $evidence,
                $transferResult
            );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionResult[
                'status'
            ]
            ?? null
        )
        ===
        'Ready',
        'Real transfer decision backtesting is Ready.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        array_key_exists(
            'reason',
            $decisionResult
        )
        &&
        $decisionResult[
            'reason'
        ]
        ===
        null,
        'Ready transfer decision backtesting has no failure reason.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionResult[
                'entry_id'
            ]
            ?? null
        )
        ===
        $syntheticEntryId,
        'Transfer decision result preserves entry identity.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionResult[
                'gameweek_id'
            ]
            ?? null
        )
        ===
        $gameweekId,
        'Transfer decision result preserves gameweek identity.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionResult[
                'decision_evaluation'
            ][
                'decision_action'
            ]
            ?? null
        )
        ===
        'Make Transfer',
        'Database-backed decision evaluation uses preserved transfer advice action.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionResult[
                'decision_evaluation'
            ][
                'decision_priority'
            ]
            ?? null
        )
        ===
        'High',
        'Database-backed decision evaluation preserves transfer priority.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionResult[
                'decision_evaluation'
            ][
                'decision_score'
            ]
            ?? null
        )
        ===
        78.0,
        'Database-backed decision evaluation preserves transfer decision score.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionResult[
                'decision_evaluation'
            ][
                'outgoing_player_id'
            ]
            ?? null
        )
        ===
        $outgoingPlayerId,
        'Database-backed decision evaluation preserves outgoing player identity.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionResult[
                'decision_evaluation'
            ][
                'incoming_player_id'
            ]
            ?? null
        )
        ===
        $incomingPlayerId,
        'Database-backed decision evaluation preserves incoming player identity.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionResult[
                'decision_evaluation'
            ][
                'transfer_points_gain'
            ]
            ?? null
        )
        ==
        $expectedTransferGain,
        'Database-backed decision evaluation preserves genuine realised transfer gain.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionResult[
                'decision_evaluation'
            ][
                'support_status'
            ]
            ?? null
        )
        ===
        $expectedSupportStatus,
        'Preserved Make Transfer advice is classified from genuine realised transfer gain.'
    );


    /*
     * ========================================================
     * SCENARIO F
     * OVERALL ACTION REMAINS IRRELEVANT
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario F: Overall Action Remains Separate<br>";
    echo "============================================<br>";


    gameweekTransferDecisionDatabaseTestResult(
        (
            $evidence[
                'recommendation_snapshot'
            ][
                'gameweek_decision'
            ][
                'overall_action'
            ]
            ?? null
        )
        ===
        'Urgent Action',
        'Historical evidence retains deliberately different overall action.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionResult[
                'decision_evaluation'
            ][
                'decision_action'
            ]
            ?? null
        )
        ===
        'Make Transfer',
        'Transfer decision evaluation does not substitute overall action for transfer advice.'
    );


    /*
     * ========================================================
     * SCENARIO G
     * IMMUTABLE INSERT-IF-ABSENT
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario G: Historical Decision Remains Immutable<br>";
    echo "============================================<br>";


    $conflictingGameweekDecision =
        $gameweekDecision;


    $conflictingGameweekDecision[
        'overall_action'
    ] =
        'Hold';


    $conflictingGameweekDecision[
        'transfer_advice'
    ][
        'action'
    ] =
        'Hold';


    $conflictingGameweekDecision[
        'transfer_advice'
    ][
        'priority'
    ] =
        'Low';


    $conflictingGameweekDecision[
        'transfer_advice'
    ][
        'score'
    ] =
        40.0;


    $conflictingSnapshot =
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
            $conflictingGameweekDecision,
            []
        );


    $secondInsert =
        $recommendationSnapshotRepository
            ->insertIfAbsent(
                $gameweekId,
                $conflictingSnapshot
            );


    gameweekTransferDecisionDatabaseTestResult(
        $secondInsert === false,
        'Conflicting later snapshot cannot overwrite existing historical recommendation.'
    );


    $evidenceAfterConflict =
        $gameweekBacktestingEvidenceService
            ->getEvidence(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $evidenceAfterConflict[
                'recommendation_snapshot'
            ][
                'gameweek_decision'
            ][
                'transfer_advice'
            ][
                'action'
            ]
            ?? null
        )
        ===
        'Make Transfer',
        'Original Make Transfer advice remains immutable after conflicting insert attempt.'
    );


    $transferAfterConflict =
        $gameweekTransferBacktestingService
            ->evaluate(
                $evidenceAfterConflict
            );


    $decisionAfterConflict =
        $gameweekTransferDecisionBacktestingService
            ->evaluate(
                $evidenceAfterConflict,
                $transferAfterConflict
            );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionAfterConflict[
                'decision_evaluation'
            ][
                'decision_action'
            ]
            ?? null
        )
        ===
        'Make Transfer',
        'Backtesting continues to evaluate original immutable transfer decision.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        (
            $decisionAfterConflict[
                'decision_evaluation'
            ][
                'support_status'
            ]
            ?? null
        )
        ===
        $expectedSupportStatus,
        'Conflicting later intelligence cannot rewrite historical support classification.'
    );


    /*
     * ========================================================
     * SCENARIO H
     * SCOPE BOUNDARY
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario H: Database Backtesting Scope Boundary<br>";
    echo "============================================<br>";


    gameweekTransferDecisionDatabaseTestResult(
        !array_key_exists(
            'accuracy_score',
            $decisionResult
        ),
        'Database-backed decision evaluation does not manufacture an accuracy score.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        !array_key_exists(
            'overall_score',
            $decisionResult
        ),
        'Database-backed decision evaluation does not manufacture an overall score.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        !array_key_exists(
            'transfer_hit',
            $decisionResult
        ),
        'Database-backed decision evaluation does not assume a transfer hit.'
    );


    gameweekTransferDecisionDatabaseTestResult(
        !array_key_exists(
            'net_points_gain',
            $decisionResult
        ),
        'Database-backed decision evaluation does not manufacture hit-adjusted gain.'
    );


    /*
     * ========================================================
     * ROLLBACK
     * ========================================================
     */

    $pdo->rollBack();


    gameweekTransferDecisionDatabaseTestResult(
        !$pdo->inTransaction(),
        'Database transaction is rolled back after integration test.'
    );


} catch (
    Throwable $exception
) {

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    gameweekTransferDecisionDatabaseTestResult(
        false,
        'Database integration completed without exception: '
        . $exception->getMessage()
    );
}


/*
 * ============================================================
 * VERIFY SYNTHETIC SNAPSHOT DID NOT PERSIST
 * ============================================================
 */

$persistedSnapshot =
    $recommendationSnapshotRepository
        ->getByEntryAndGameweek(
            $syntheticEntryId,
            $gameweekId
        );


gameweekTransferDecisionDatabaseTestResult(
    $persistedSnapshot === null,
    'Synthetic recommendation snapshot does not persist after rollback.'
);


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