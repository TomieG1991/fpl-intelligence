<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Captain Backtesting Database Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekCaptainBacktestingDatabaseIntegrationTestResult(
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


gameweekCaptainBacktestingDatabaseIntegrationTestResult(
    $pdo
        instanceof PDO,
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


$captainBacktestingService =
    new CaptainBacktestingService();


$gameweekCaptainBacktestingService =
    new GameweekCaptainBacktestingService(
        $captainBacktestingService
    );


gameweekCaptainBacktestingDatabaseIntegrationTestResult(
    $gameweekBacktestingEvidenceService
        instanceof GameweekBacktestingEvidenceService,
    'Real historical evidence service can be constructed.'
);


gameweekCaptainBacktestingDatabaseIntegrationTestResult(
    $captainBacktestingService
        instanceof CaptainBacktestingService,
    'Real captain backtesting service can be constructed.'
);


gameweekCaptainBacktestingDatabaseIntegrationTestResult(
    $gameweekCaptainBacktestingService
        instanceof GameweekCaptainBacktestingService,
    'Real gameweek captain backtesting service can be constructed.'
);


/*
 * ============================================================
 * DISCOVER AUTHORITATIVE COMPLETED GAMEWEEK
 * ============================================================
 *
 * We deliberately discover a real completed gameweek that:
 *
 * - is marked finished
 * - is marked data_checked
 * - contains genuine player fixture history
 *
 * We do not modify real fixture outcome history.
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


gameweekCaptainBacktestingDatabaseIntegrationTestResult(
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
 * LOAD REAL COMPLETED-GAMEWEEK OUTCOMES
 * ============================================================
 */

$realOutcomes =
    $playerGameweekOutcomeService
        ->getByGameweekId(
            $gameweekId
        );


gameweekCaptainBacktestingDatabaseIntegrationTestResult(
    !empty(
        $realOutcomes
    ),
    'Real player outcome evidence is available for discovered gameweek.'
);


/*
 * ============================================================
 * SELECT GENUINE CAPTAIN CANDIDATES
 * ============================================================
 *
 * Captain Intelligence may preserve fewer than fifteen rankings
 * when some squad players are rejected.
 *
 * We therefore use up to fifteen genuine completed-gameweek
 * player identities and require only the minimum needed for a
 * captain-versus-alternative comparison.
 *
 * Ranking metadata is synthetic deterministic evidence.
 * Player identities and realised outcomes are genuine.
 * ============================================================
 */

$selectedOutcomes =
    array_slice(
        $realOutcomes,
        0,
        min(
            15,
            count(
                $realOutcomes
            )
        )
    );


gameweekCaptainBacktestingDatabaseIntegrationTestResult(
    count(
        $selectedOutcomes
    )
    >= 2,
    'At least two genuine player outcomes are available for captain backtesting.'
);


if (
    count(
        $selectedOutcomes
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
 * BUILD SYNTHETIC PRESERVED CAPTAIN INTELLIGENCE
 * ============================================================
 */

$captainRankings =
    [];


foreach (
    $selectedOutcomes
    as $index => $outcome
) {

    $playerId =
        (int) $outcome[
            'player_id'
        ];


    $captainRankings[] = [

        'player_id' =>
            $playerId,

        'name' =>
            'Integration Test Player '
            . $playerId,

        'rank' =>
            $index
            +
            1,

        'captain_score' =>
            100.0
            -
            (float) $index
    ];
}


$captain =
    $captainRankings[
        0
    ];


$viceCaptain =
    $captainRankings[
        1
    ];


$captainRecommendation =
    [
        'status' =>
            'success',

        'message' =>
            'Synthetic preserved captain recommendation for database integration testing.',

        'captain' =>
            $captain,

        'vice_captain' =>
            $viceCaptain,

        'alternatives' =>
            array_slice(
                $captainRankings,
                2
            ),

        'rankings' =>
            $captainRankings,

        'squad_count' =>
            count(
                $captainRankings
            ),

        'evaluated_count' =>
            count(
                $captainRankings
            ),

        'rejected_count' =>
            0,

        'rejected_players' =>
            [],

        'recommendation_limit' =>
            5
    ];


gameweekCaptainBacktestingDatabaseIntegrationTestResult(
    count(
        $captainRankings
    )
    ===
    count(
        $selectedOutcomes
    ),
    'Preserved Captain Intelligence rankings use genuine completed-gameweek player identities.'
);


/*
 * ============================================================
 * INDEPENDENT EXPECTED REALISED CAPTAIN RESULT
 * ============================================================
 *
 * Calculate the expected winner independently from the
 * production CaptainBacktestingService.
 * ============================================================
 */

$outcomeLookup =
    [];


foreach (
    $selectedOutcomes
    as $outcome
) {

    $outcomeLookup[
        (int) $outcome[
            'player_id'
        ]
    ] =
        $outcome;
}


$captainPlayerId =
    (int) $captain[
        'player_id'
    ];


$captainOutcome =
    $outcomeLookup[
        $captainPlayerId
    ];


$expectedBestAlternativePlayerId =
    null;


$expectedBestAlternativePoints =
    null;


foreach (
    $captainRankings
    as $ranking
) {

    $playerId =
        (int) $ranking[
            'player_id'
        ];


    if (
        $playerId
        ===
        $captainPlayerId
    ) {

        continue;
    }


    $actualPoints =
        $outcomeLookup[
            $playerId
        ][
            'total_points'
        ];


    if (
        $expectedBestAlternativePlayerId
        ===
        null
        ||
        $actualPoints
        >
        $expectedBestAlternativePoints
    ) {

        $expectedBestAlternativePlayerId =
            $playerId;


        $expectedBestAlternativePoints =
            $actualPoints;
    }
}


$expectedCaptainPointsLost =
    max(
        0,
        $expectedBestAlternativePoints
        -
        $captainOutcome[
            'total_points'
        ]
    );


/*
 * ============================================================
 * SYNTHETIC ENTRY AND SNAPSHOT TIMESTAMPS
 * ============================================================
 */

$syntheticEntryId =
    935010003;


$deadlineTime =
    (string) $gameweek[
        'deadline_time'
    ];


$deadlineTimestamp =
    strtotime(
        $deadlineTime
    );


$capturedAt =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestamp
        -
        3600
    );


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
        $captainRecommendation,
        [],
        [],
        []
    );


gameweekCaptainBacktestingDatabaseIntegrationTestResult(
    $snapshot
        instanceof RecommendationSnapshot,
    'Synthetic immutable recommendation snapshot containing captain evidence can be constructed.'
);


/*
 * ============================================================
 * RUN INSIDE TRANSACTION
 * ============================================================
 */

$pdo->beginTransaction();


try {

    /*
     * Remove any previous residue for this synthetic entry
     * inside the transaction only.
     *
     * Under normal circumstances there should be none.
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


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $evidenceBeforeInsert[
                'status'
            ]
            ?? null
        )
        ===
        'Incomplete',
        'Authoritative gameweek without recommendation snapshot is incomplete.'
    );


    $captainResultBeforeInsert =
        $gameweekCaptainBacktestingService
            ->evaluate(
                $evidenceBeforeInsert
            );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $captainResultBeforeInsert[
                'status'
            ]
            ?? null
        )
        ===
        'Incomplete',
        'Captain backtesting propagates missing historical recommendation evidence as Incomplete.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $captainResultBeforeInsert[
                'captain_evaluation'
            ]
            ?? null
        )
        ===
        [],
        'No captain evaluation is manufactured before historical snapshot insertion.'
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


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
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


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
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


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
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


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
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


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        !empty(
            $evidence[
                'player_outcomes'
            ]
            ?? []
        ),
        'Real historical evidence contains genuine completed-gameweek outcomes.'
    );


    $assembledCaptainRecommendation =
        $evidence[
            'recommendation_snapshot'
        ][
            'captain_recommendation'
        ]
        ?? null;


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        $assembledCaptainRecommendation
        ===
        $captainRecommendation,
        'Real historical evidence restores preserved captain recommendation unchanged.'
    );


    /*
     * ========================================================
     * SCENARIO C
     * END-TO-END CAPTAIN BACKTESTING
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario C: End-to-End Captain Backtesting<br>";
    echo "============================================<br>";


    $result =
        $gameweekCaptainBacktestingService
            ->evaluate(
                $evidence
            );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $result[
                'status'
            ]
            ?? null
        )
        ===
        'Ready',
        'Database-backed evidence produces Ready captain backtesting.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        array_key_exists(
            'reason',
            $result
        )
        &&
        $result[
            'reason'
        ]
        ===
        null,
        'Ready database-backed captain backtesting has no failure reason.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $result[
                'entry_id'
            ]
            ?? null
        )
        ===
        $syntheticEntryId,
        'End-to-end captain backtesting preserves entry identity.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $result[
                'gameweek_id'
            ]
            ?? null
        )
        ===
        $gameweekId,
        'End-to-end captain backtesting preserves gameweek identity.'
    );


    /*
     * ========================================================
     * SCENARIO D
     * GENUINE CAPTAIN OUTCOME
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario D: Genuine Captain Outcome<br>";
    echo "============================================<br>";


    $captainEvaluation =
        $result[
            'captain_evaluation'
        ]
        ?? [];


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        !empty(
            $captainEvaluation
        ),
        'End-to-end database path produces captain evaluation.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $captainEvaluation[
                'captain_player_id'
            ]
            ?? null
        )
        ===
        $captainPlayerId,
        'Database-backed captain evaluation retains preserved captain identity.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $captainEvaluation[
                'captain_actual_points'
            ]
            ?? null
        )
        ===
        $captainOutcome[
            'total_points'
        ],
        'Preserved captain is matched to genuine realised FPL points.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $captainEvaluation[
                'captain_actual_minutes'
            ]
            ?? null
        )
        ===
        $captainOutcome[
            'minutes'
        ],
        'Preserved captain is matched to genuine realised minutes.'
    );


    /*
     * ========================================================
     * SCENARIO E
     * GENUINE BEST CAPTAIN INTELLIGENCE ALTERNATIVE
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario E: Genuine Best Captain Intelligence Alternative<br>";
    echo "============================================<br>";


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $captainEvaluation[
                'best_alternative_player_id'
            ]
            ?? null
        )
        ===
        $expectedBestAlternativePlayerId,
        'Best realised captain alternative is selected from the preserved Captain Intelligence universe.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $captainEvaluation[
                'best_alternative_actual_points'
            ]
            ?? null
        )
        ===
        $expectedBestAlternativePoints,
        'Best preserved captain alternative retains genuine realised FPL points.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        $expectedBestAlternativePlayerId
        !==
        $captainPlayerId,
        'Recommended captain is excluded from the realised alternative pool.'
    );


    /*
     * ========================================================
     * SCENARIO F
     * GENUINE CAPTAIN POINTS LOST
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario F: Genuine Captain Points Lost<br>";
    echo "============================================<br>";


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $captainEvaluation[
                'captain_points_lost'
            ]
            ?? null
        )
        ===
        $expectedCaptainPointsLost,
        'Captain points lost is derived exactly from preserved recommendation evidence and genuine realised outcomes.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $captainEvaluation[
                'captain_points_lost'
            ]
            ?? -1
        )
        >=
        0,
        'Database-backed captain points lost is never negative.'
    );


    /*
     * ========================================================
     * SCENARIO G
     * SNAPSHOT IMMUTABILITY
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario G: Immutable Historical Captain Snapshot<br>";
    echo "============================================<br>";


    $insertedAgain =
        $recommendationSnapshotRepository
            ->insertIfAbsent(
                $gameweekId,
                $snapshot
            );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        $insertedAgain === false,
        'Existing historical recommendation snapshot cannot be inserted again.'
    );


    $storedSnapshot =
        $recommendationSnapshotRepository
            ->getByEntryAndGameweek(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $storedSnapshot[
                'captain_recommendation'
            ]
            ?? null
        )
        ===
        $captainRecommendation,
        'Stored historical captain recommendation evidence remains unchanged.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        (
            $storedSnapshot[
                'captain_recommendation'
            ][
                'rankings'
            ]
            ?? null
        )
        ===
        $captainRankings,
        'Stored historical Captain Intelligence ranking universe remains unchanged.'
    );


    /*
     * ========================================================
     * SCENARIO H
     * BACKTESTING SCOPE BOUNDARY
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario H: Backtesting Scope Boundary<br>";
    echo "============================================<br>";


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'accuracy_score',
            $result
        ),
        'End-to-end captain backtesting does not manufacture an accuracy score.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'overall_score',
            $result
        ),
        'End-to-end captain backtesting does not manufacture an overall model score.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'doubled_captain_points',
            $captainEvaluation
        ),
        'End-to-end captain backtesting does not manufacture doubled captain points.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'vice_captain_result',
            $result
        )
        &&
        !array_key_exists(
            'vice_captain_result',
            $captainEvaluation
        ),
        'End-to-end captain backtesting does not yet simulate vice-captain fallback.'
    );


    gameweekCaptainBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'transfer_result',
            $result
        ),
        'End-to-end captain backtesting does not evaluate transfer recommendation.'
    );


    /*
     * ========================================================
     * ROLLBACK
     * ========================================================
     */

    $pdo->rollBack();


} catch (
    Throwable $exception
) {

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    echo "<br>";
    echo "ERROR: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    $failed++;
}


/*
 * ============================================================
 * VERIFY ROLLBACK
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Transaction Rollback<br>";
echo "============================================<br>";


$rolledBackSnapshot =
    $recommendationSnapshotRepository
        ->getByEntryAndGameweek(
            $syntheticEntryId,
            $gameweekId
        );


gameweekCaptainBacktestingDatabaseIntegrationTestResult(
    $rolledBackSnapshot
    ===
    null,
    'Synthetic recommendation snapshot is removed by transaction rollback.'
);


$realOutcomesAfterRollback =
    $playerGameweekOutcomeService
        ->getByGameweekId(
            $gameweekId
        );


gameweekCaptainBacktestingDatabaseIntegrationTestResult(
    $realOutcomesAfterRollback
    ===
    $realOutcomes,
    'Real completed-gameweek outcome evidence remains unchanged.'
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