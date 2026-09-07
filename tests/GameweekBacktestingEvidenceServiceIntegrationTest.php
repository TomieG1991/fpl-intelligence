<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Backtesting Evidence Service Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function backtestingEvidenceIntegrationResult(
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
 * SYNTHETIC ENTRY IDENTITY
 * ============================================================
 *
 * Only the recommendation snapshot uses a synthetic identity.
 *
 * Gameweek and player outcome evidence remain genuine
 * production database evidence.
 */

$syntheticEntryId =
    935010001;


/*
 * ============================================================
 * SCENARIO A
 * REAL PRODUCTION STACK
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Real Production Stack<br>";
echo "============================================<br>";


$database =
    new Database();


$db =
    $database
        ->getConnection();


backtestingEvidenceIntegrationResult(
    $db instanceof PDO,
    'Real database connection is available.'
);


$gameweekRepository =
    new GameweekRepository(
        $db
    );


$availability =
    new PlayerGameweekOutcomeAvailability();


$snapshotRepository =
    new RecommendationSnapshotRepository(
        $db
    );


$fixtureHistoryRepository =
    new PlayerFixtureHistoryRepository(
        $db
    );


$outcomeService =
    new PlayerGameweekOutcomeService(
        $fixtureHistoryRepository
    );


$evidenceService =
    new GameweekBacktestingEvidenceService(
        $gameweekRepository,
        $availability,
        $snapshotRepository,
        $outcomeService
    );


backtestingEvidenceIntegrationResult(
    $gameweekRepository
        instanceof GameweekRepository,
    'Real GameweekRepository is available.'
);


backtestingEvidenceIntegrationResult(
    $availability
        instanceof PlayerGameweekOutcomeAvailability,
    'Real PlayerGameweekOutcomeAvailability is available.'
);


backtestingEvidenceIntegrationResult(
    $snapshotRepository
        instanceof RecommendationSnapshotRepository,
    'Real RecommendationSnapshotRepository is available.'
);


backtestingEvidenceIntegrationResult(
    $fixtureHistoryRepository
        instanceof PlayerFixtureHistoryRepository,
    'Real PlayerFixtureHistoryRepository is available.'
);


backtestingEvidenceIntegrationResult(
    $outcomeService
        instanceof PlayerGameweekOutcomeService,
    'Real PlayerGameweekOutcomeService is available.'
);


backtestingEvidenceIntegrationResult(
    $evidenceService
        instanceof GameweekBacktestingEvidenceService,
    'Real GameweekBacktestingEvidenceService is available.'
);


/*
 * ============================================================
 * SCENARIO B
 * FIND AUTHORITATIVE GAMEWEEK WITH REAL OUTCOMES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Authoritative Historical Evidence<br>";
echo "============================================<br>";


$gameweeks =
    $gameweekRepository
        ->getAll();


$selectedGameweek =
    null;


$selectedOutcomes =
    [];


foreach (
    $gameweeks
    as $gameweek
) {

    if (
        !$availability
            ->isAvailable(
                $gameweek
            )
    ) {

        continue;
    }


    $candidateGameweekId =
        (int) (
            $gameweek[
                'id'
            ]
            ?? 0
        );


    if (
        $candidateGameweekId <= 0
    ) {

        continue;
    }


    $candidateOutcomes =
        $outcomeService
            ->getByGameweekId(
                $candidateGameweekId
            );


    if (
        empty(
            $candidateOutcomes
        )
    ) {

        continue;
    }


    $selectedGameweek =
        $gameweek;


    $selectedOutcomes =
        $candidateOutcomes;


    break;
}


backtestingEvidenceIntegrationResult(
    is_array(
        $selectedGameweek
    ),
    'An authoritative completed gameweek with real outcome evidence is available.'
);


backtestingEvidenceIntegrationResult(
    !empty(
        $selectedOutcomes
    ),
    'Selected gameweek has real player outcome evidence.'
);


if (
    !is_array(
        $selectedGameweek
    )
    ||
    empty(
        $selectedOutcomes
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
    (int) $selectedGameweek[
        'id'
    ];


$fplGameweekId =
    (int) $selectedGameweek[
        'fpl_gameweek_id'
    ];


$deadlineTime =
    (string) (
        $selectedGameweek[
            'deadline_time'
        ]
        ?? ''
    );


backtestingEvidenceIntegrationResult(
    $gameweekId > 0,
    'Selected gameweek has a valid local gameweek identity.'
);


backtestingEvidenceIntegrationResult(
    $fplGameweekId > 0,
    'Selected gameweek has a valid official FPL gameweek identity.'
);


backtestingEvidenceIntegrationResult(
    $deadlineTime !== '',
    'Selected gameweek has a preserved deadline.'
);


/*
 * ============================================================
 * BUILD VALID PRE-DEADLINE CAPTURE TIME
 * ============================================================
 */

$deadlineTimestamp =
    strtotime(
        $deadlineTime
    );


if (
    $deadlineTimestamp === false
) {

    backtestingEvidenceIntegrationResult(
        false,
        'Selected gameweek deadline can be parsed.'
    );


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


$capturedAt =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestamp - 60
    );


backtestingEvidenceIntegrationResult(
    strtotime(
        $capturedAt
    )
    <
    $deadlineTimestamp,
    'Synthetic recommendation capture occurs before the real gameweek deadline.'
);


/*
 * ============================================================
 * USE REAL PLAYER IDENTITIES IN SNAPSHOT EVIDENCE
 * ============================================================
 */

$firstOutcome =
    $selectedOutcomes[
        0
    ];


$firstPlayerId =
    (int) (
        $firstOutcome[
            'player_id'
        ]
        ?? 0
    );


$secondPlayerId =
    $firstPlayerId;


if (
    isset(
        $selectedOutcomes[
            1
        ]
    )
) {

    $secondPlayerId =
        (int) (
            $selectedOutcomes[
                1
            ][
                'player_id'
            ]
            ?? $firstPlayerId
        );
}


backtestingEvidenceIntegrationResult(
    $firstPlayerId > 0,
    'Real outcome evidence provides a valid player identity.'
);


/*
 * ============================================================
 * SYNTHETIC RECOMMENDATION EVIDENCE
 * ============================================================
 *
 * These arrays are deliberately simple.
 *
 * This integration test is proving evidence assembly, not
 * captain, transfer or projection evaluation.
 */

$playerProjections = [
    [
        'player_id' =>
            $firstPlayerId,

        'projected_points' =>
            6.5,

        'projected_minutes' =>
            90
    ]
];


$startingXI = [
    [
        'player_id' =>
            $firstPlayerId
    ]
];


$captainRecommendation = [

    'captain' => [
        'player_id' =>
            $firstPlayerId
    ],

    'vice_captain' => [
        'player_id' =>
            $secondPlayerId
    ]
];


$transferRecommendations =
    [];


$gameweekDecision = [
    'overall_action' =>
        'Hold'
];


$chipRecommendations =
    [];


/*
 * ============================================================
 * SCENARIO C
 * BEFORE SNAPSHOT EXISTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Authoritative Gameweek Without Snapshot<br>";
echo "============================================<br>";


$existingSyntheticSnapshot =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $syntheticEntryId,
            $gameweekId
        );


backtestingEvidenceIntegrationResult(
    $existingSyntheticSnapshot === null,
    'Synthetic recommendation identity is unused before the test.'
);


if (
    $existingSyntheticSnapshot !== null
) {

    echo "<br>";
    echo "Synthetic recommendation identity already exists. "
        . "Test stopped to protect historical evidence.<br>";

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


$result =
    $evidenceService
        ->getEvidence(
            $syntheticEntryId,
            $gameweekId
        );


backtestingEvidenceIntegrationResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Authoritative gameweek without recommendation snapshot is incomplete.'
);


backtestingEvidenceIntegrationResult(
    (
        $result[
            'reason'
        ]
        ?? null
    )
    ===
    'Recommendation snapshot is unavailable',
    'Missing snapshot reports the expected incomplete reason.'
);


backtestingEvidenceIntegrationResult(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    $gameweekId,
    'Incomplete result preserves the local gameweek identity.'
);


/*
 * ============================================================
 * SCENARIO D
 * INSERT IMMUTABLE SYNTHETIC SNAPSHOT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Immutable Recommendation Snapshot<br>";
echo "============================================<br>";


$transactionStarted =
    false;


try {

    $transactionStarted =
        $db->beginTransaction();


    backtestingEvidenceIntegrationResult(
        $transactionStarted === true,
        'Database transaction begins successfully.'
    );


    $snapshot =
        new RecommendationSnapshot(
            $gameweekId,
            $syntheticEntryId,
            $capturedAt,
            $deadlineTime,
            [],
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
            );


    backtestingEvidenceIntegrationResult(
        $snapshot
            instanceof RecommendationSnapshot,
        'Valid synthetic RecommendationSnapshot is constructed.'
    );


    $inserted =
        $snapshotRepository
            ->insertIfAbsent(
                $gameweekId,
                $snapshot
            );


    backtestingEvidenceIntegrationResult(
        $inserted === true,
        'Synthetic immutable recommendation snapshot is inserted.'
    );


    $storedSnapshot =
        $snapshotRepository
            ->getByEntryAndGameweek(
                $syntheticEntryId,
                $gameweekId
            );


    backtestingEvidenceIntegrationResult(
        is_array(
            $storedSnapshot
        ),
        'Synthetic recommendation snapshot is readable through the real repository.'
    );


    backtestingEvidenceIntegrationResult(
        (
            (int) (
                $storedSnapshot[
                    'entry_id'
                ]
                ?? 0
            )
        )
        ===
        $syntheticEntryId,
        'Stored snapshot preserves synthetic entry identity.'
    );


    backtestingEvidenceIntegrationResult(
        (
            (int) (
                $storedSnapshot[
                    'gameweek_id'
                ]
                ?? 0
            )
        )
        ===
        $gameweekId,
        'Stored snapshot preserves local gameweek identity.'
    );


    backtestingEvidenceIntegrationResult(
        (
            $storedSnapshot[
                'player_projections'
            ]
            ?? null
        )
        ===
        $playerProjections,
        'Stored snapshot preserves player projection evidence exactly.'
    );


    /*
     * ========================================================
     * SCENARIO E
     * COMPLETE REAL PRODUCTION EVIDENCE
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario E: Complete Production Evidence<br>";
    echo "============================================<br>";


    $readyResult =
        $evidenceService
            ->getEvidence(
                $syntheticEntryId,
                $gameweekId
            );


    backtestingEvidenceIntegrationResult(
        (
            $readyResult[
                'status'
            ]
            ?? null
        )
        ===
        'Ready',
        'Complete production evidence is ready for backtesting.'
    );


    backtestingEvidenceIntegrationResult(
        array_key_exists(
            'reason',
            $readyResult
        )
        &&
        $readyResult[
            'reason'
        ]
        ===
        null,
        'Ready production evidence has no incomplete reason.'
    );


    backtestingEvidenceIntegrationResult(
        (
            (int) (
                $readyResult[
                    'entry_id'
                ]
                ?? 0
            )
        )
        ===
        $syntheticEntryId,
        'Ready evidence preserves entry identity.'
    );


    backtestingEvidenceIntegrationResult(
        (
            (int) (
                $readyResult[
                    'gameweek_id'
                ]
                ?? 0
            )
        )
        ===
        $gameweekId,
        'Ready evidence preserves local gameweek identity.'
    );


    backtestingEvidenceIntegrationResult(
        (
            (int) (
                $readyResult[
                    'gameweek'
                ][
                    'fpl_gameweek_id'
                ]
                ?? 0
            )
        )
        ===
        $fplGameweekId,
        'Ready evidence preserves official FPL gameweek identity.'
    );


    backtestingEvidenceIntegrationResult(
        (
            $readyResult[
                'recommendation_snapshot'
            ][
                'player_projections'
            ]
            ?? null
        )
        ===
        $playerProjections,
        'Ready evidence preserves immutable recommendation projections exactly.'
    );


    backtestingEvidenceIntegrationResult(
        (
            $readyResult[
                'recommendation_snapshot'
            ][
                'starting_xi'
            ]
            ?? null
        )
        ===
        $startingXI,
        'Ready evidence preserves immutable Starting XI evidence exactly.'
    );


    backtestingEvidenceIntegrationResult(
        (
            $readyResult[
                'recommendation_snapshot'
            ][
                'captain_recommendation'
            ]
            ?? null
        )
        ===
        $captainRecommendation,
        'Ready evidence preserves immutable captain evidence exactly.'
    );


    backtestingEvidenceIntegrationResult(
        (
            $readyResult[
                'recommendation_snapshot'
            ][
                'gameweek_decision'
            ]
            ?? null
        )
        ===
        $gameweekDecision,
        'Ready evidence preserves immutable Gameweek Decision evidence exactly.'
    );


    backtestingEvidenceIntegrationResult(
        (
            $readyResult[
                'player_outcomes'
            ]
            ?? null
        )
        ===
        $selectedOutcomes,
        'Ready evidence preserves real aggregated player outcomes exactly.'
    );


    backtestingEvidenceIntegrationResult(
        count(
            $readyResult[
                'player_outcomes'
            ]
            ?? []
        )
        >
        0,
        'Ready evidence contains real completed-gameweek player outcomes.'
    );


    /*
     * ========================================================
     * SCENARIO F
     * NO BACKTESTING CALCULATIONS
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario F: Evidence Assembly Boundary<br>";
    echo "============================================<br>";


    backtestingEvidenceIntegrationResult(
        !array_key_exists(
            'captain_result',
            $readyResult
        ),
        'Production evidence service does not calculate captain success.'
    );


    backtestingEvidenceIntegrationResult(
        !array_key_exists(
            'starting_xi_result',
            $readyResult
        ),
        'Production evidence service does not calculate Starting XI success.'
    );


    backtestingEvidenceIntegrationResult(
        !array_key_exists(
            'transfer_result',
            $readyResult
        ),
        'Production evidence service does not calculate transfer success.'
    );


    backtestingEvidenceIntegrationResult(
        !array_key_exists(
            'projection_error',
            $readyResult
        ),
        'Production evidence service does not calculate projection accuracy.'
    );


    backtestingEvidenceIntegrationResult(
        !array_key_exists(
            'score',
            $readyResult
        ),
        'Production evidence service does not create a synthetic backtesting score.'
    );


} catch (
    Throwable $exception
) {

    backtestingEvidenceIntegrationResult(
        false,
        'Unexpected integration exception: '
            . $exception
                ->getMessage()
    );

} finally {

    /*
     * ========================================================
     * SCENARIO G
     * TRANSACTION ROLLBACK
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario G: Synthetic Evidence Rollback<br>";
    echo "============================================<br>";


    if (
        $db->inTransaction()
    ) {

        $rolledBack =
            $db->rollBack();


        backtestingEvidenceIntegrationResult(
            $rolledBack === true,
            'Database transaction rolls back successfully.'
        );

    } else {

        backtestingEvidenceIntegrationResult(
            false,
            'Database transaction remained available for rollback.'
        );
    }
}


/*
 * ============================================================
 * VERIFY NOTHING PERSISTED
 * ============================================================
 */

$remainingSnapshot =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $syntheticEntryId,
            $gameweekId
        );


backtestingEvidenceIntegrationResult(
    $remainingSnapshot === null,
    'Synthetic recommendation snapshot does not remain after rollback.'
);


/*
 * ============================================================
 * REAL OUTCOME EVIDENCE REMAINS AVAILABLE
 * ============================================================
 */

$outcomesAfterRollback =
    $outcomeService
        ->getByGameweekId(
            $gameweekId
        );


backtestingEvidenceIntegrationResult(
    $outcomesAfterRollback
        ===
        $selectedOutcomes,
    'Real player outcome evidence remains unchanged after the test.'
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


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}