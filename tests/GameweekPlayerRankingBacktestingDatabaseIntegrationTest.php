<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Player Ranking Backtesting Database Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekPlayerRankingDatabaseTestResult(
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
 * Recommendation evidence is synthetic.
 *
 * Gameweek identity and realised player outcomes are genuine
 * production database evidence.
 */

$syntheticEntryId =
    935010002;


/*
 * ============================================================
 * SCENARIO A
 * REAL DATABASE AND PRODUCTION STACK
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Real Database And Production Stack<br>";
echo "============================================<br>";


$database =
    new Database();


$db =
    $database
        ->getConnection();


gameweekPlayerRankingDatabaseTestResult(
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


$playerRankingBacktestingService =
    new PlayerRankingBacktestingService();


$playerRankingBacktestingMetricsService =
    new PlayerRankingBacktestingMetricsService();


$rankingPipeline =
    new GameweekPlayerRankingBacktestingService(
        $playerRankingBacktestingService,
        $playerRankingBacktestingMetricsService
    );


gameweekPlayerRankingDatabaseTestResult(
    $gameweekRepository
        instanceof GameweekRepository,
    'Real GameweekRepository is available.'
);


gameweekPlayerRankingDatabaseTestResult(
    $snapshotRepository
        instanceof RecommendationSnapshotRepository,
    'Real RecommendationSnapshotRepository is available.'
);


gameweekPlayerRankingDatabaseTestResult(
    $fixtureHistoryRepository
        instanceof PlayerFixtureHistoryRepository,
    'Real PlayerFixtureHistoryRepository is available.'
);


gameweekPlayerRankingDatabaseTestResult(
    $outcomeService
        instanceof PlayerGameweekOutcomeService,
    'Real PlayerGameweekOutcomeService is available.'
);


gameweekPlayerRankingDatabaseTestResult(
    $evidenceService
        instanceof GameweekBacktestingEvidenceService,
    'Real GameweekBacktestingEvidenceService is available.'
);


gameweekPlayerRankingDatabaseTestResult(
    $rankingPipeline
        instanceof GameweekPlayerRankingBacktestingService,
    'Real Player Ranking backtesting pipeline is available.'
);


/*
 * ============================================================
 * SCENARIO B
 * FIND AUTHORITATIVE COMPLETED GAMEWEEK
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Authoritative Completed Gameweek<br>";
echo "============================================<br>";


$gameweeks =
    $gameweekRepository
        ->getAll();


$selectedGameweek =
    null;


$selectedOutcomes =
    [];


/*
 * Require at least two genuine realised player outcomes.
 *
 * Two observations are the minimum required for correlation
 * metrics to have the possibility of being calculable.
 */
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
        count(
            $candidateOutcomes
        )
        < 2
    ) {

        continue;
    }


    $selectedGameweek =
        $gameweek;


    $selectedOutcomes =
        $candidateOutcomes;


    break;
}


gameweekPlayerRankingDatabaseTestResult(
    is_array(
        $selectedGameweek
    ),
    'An authoritative completed gameweek with real player outcomes is available.'
);


gameweekPlayerRankingDatabaseTestResult(
    count(
        $selectedOutcomes
    )
    >= 2,
    'Selected gameweek contains at least two genuine realised player outcomes.'
);


if (
    !is_array(
        $selectedGameweek
    )
    ||
    count(
        $selectedOutcomes
    )
    < 2
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
    (int) (
        $selectedGameweek[
            'id'
        ]
        ?? 0
    );


$fplGameweekId =
    (int) (
        $selectedGameweek[
            'fpl_gameweek_id'
        ]
        ?? 0
    );


$deadlineTime =
    (string) (
        $selectedGameweek[
            'deadline_time'
        ]
        ?? ''
    );


gameweekPlayerRankingDatabaseTestResult(
    $gameweekId > 0,
    'Selected gameweek has a valid local gameweek identity.'
);


gameweekPlayerRankingDatabaseTestResult(
    $fplGameweekId > 0,
    'Selected gameweek has a valid official FPL gameweek identity.'
);


gameweekPlayerRankingDatabaseTestResult(
    $deadlineTime !== '',
    'Selected gameweek has a preserved deadline.'
);


/*
 * ============================================================
 * VALID PRE-DEADLINE CAPTURE TIME
 * ============================================================
 */

$deadlineTimestamp =
    strtotime(
        $deadlineTime
    );


gameweekPlayerRankingDatabaseTestResult(
    $deadlineTimestamp !== false,
    'Selected gameweek deadline can be parsed.'
);


if (
    $deadlineTimestamp === false
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


$capturedAt =
    date(
        'Y-m-d H:i:s',
        $deadlineTimestamp - 60
    );


gameweekPlayerRankingDatabaseTestResult(
    strtotime(
        $capturedAt
    )
    <
    $deadlineTimestamp,
    'Synthetic recommendation evidence is captured before the real deadline.'
);


/*
 * ============================================================
 * SCENARIO C
 * BUILD SYNTHETIC HISTORICAL RANKINGS FROM REAL IDENTITIES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Historical Ranking Evidence<br>";
echo "============================================<br>";


/*
 * Use up to five genuine player identities from the completed
 * gameweek.
 *
 * The Intelligence Scores and ranks are deliberately synthetic:
 * this test is proving preservation and backtesting plumbing,
 * not claiming these scores existed historically.
 */

$rankingOutcomeSample =
    array_slice(
        $selectedOutcomes,
        0,
        5
    );


$playerRankings =
    [];


$expectedPlayerIds =
    [];


$expectedActualPoints =
    [];


$syntheticScore =
    90.0;


$syntheticRank =
    2;


foreach (
    $rankingOutcomeSample
    as $outcome
) {

    $playerId =
        (int) (
            $outcome[
                'player_id'
            ]
            ?? 0
        );


    if (
        $playerId <= 0
    ) {

        continue;
    }


    if (
        !array_key_exists(
            'total_points',
            $outcome
        )
        ||
        !is_numeric(
            $outcome[
                'total_points'
            ]
        )
    ) {

        continue;
    }


    $playerRankings[] = [

        'player_id' =>
            $playerId,

        'fpl_player_id' =>
            null,

        'name' =>
            'Historical Player '
            . $playerId,

        'position' =>
            null,

        'team_id' =>
            null,

        'price' =>
            null,

        'intelligence_score' =>
            $syntheticScore,

        'rank' =>
            $syntheticRank
    ];


    $expectedPlayerIds[] =
        $playerId;


    $expectedActualPoints[] =
        (int) $outcome[
            'total_points'
        ];


    $syntheticScore -=
        10.0;


    /*
     * Deliberately leave gaps in historical ranks.
     */
    $syntheticRank +=
        5;
}


gameweekPlayerRankingDatabaseTestResult(
    count(
        $playerRankings
    )
    >= 2,
    'At least two valid real player identities are available for ranking evidence.'
);


gameweekPlayerRankingDatabaseTestResult(
    count(
        $playerRankings
    )
    ===
    count(
        $expectedPlayerIds
    ),
    'Synthetic ranking evidence maps exactly to the selected real player identities.'
);


gameweekPlayerRankingDatabaseTestResult(
    (
        $playerRankings[
            0
        ][
            'rank'
        ]
        ?? null
    )
    ===
    2,
    'Synthetic ranking evidence begins with a preserved non-default historical rank.'
);


if (
    isset(
        $playerRankings[
            1
        ]
    )
) {

    gameweekPlayerRankingDatabaseTestResult(
        $playerRankings[
            1
        ][
            'rank'
        ]
        ===
        7,
        'Synthetic ranking evidence deliberately contains non-contiguous historical ranks.'
    );
}


/*
 * ============================================================
 * OTHER REQUIRED SNAPSHOT EVIDENCE
 * ============================================================
 *
 * The RecommendationSnapshot contract contains several other
 * recommendation evidence fields.
 *
 * They are deliberately minimal because this test concerns
 * Player Ranking Evidence.
 */

$firstPlayerId =
    $expectedPlayerIds[
        0
    ];


$secondPlayerId =
    $expectedPlayerIds[
        1
    ];


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
 * SCENARIO D
 * PROTECT EXISTING DATABASE EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Protect Existing Historical Evidence<br>";
echo "============================================<br>";


$existingSyntheticSnapshot =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $syntheticEntryId,
            $gameweekId
        );


gameweekPlayerRankingDatabaseTestResult(
    $existingSyntheticSnapshot === null,
    'Synthetic recommendation identity is unused before the test.'
);


if (
    $existingSyntheticSnapshot !== null
) {

    echo "<br>";
    echo "Synthetic recommendation identity already exists. ";
    echo "Test stopped to protect existing historical evidence.<br>";

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
 * SCENARIO E
 * DATABASE TRANSACTION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Transactional Immutable Snapshot<br>";
echo "============================================<br>";


$transactionStarted =
    false;


try {

    $transactionStarted =
        $db->beginTransaction();


    gameweekPlayerRankingDatabaseTestResult(
        $transactionStarted === true,
        'Database transaction begins successfully.'
    );


    /*
     * ========================================================
     * CONSTRUCT SNAPSHOT WITH PLAYER RANKINGS
     * ========================================================
     */

    $snapshot =
        new RecommendationSnapshot(
            $gameweekId,
            $syntheticEntryId,
            $capturedAt,
            $deadlineTime,
            $playerRankings,
            $playerProjections,
            $startingXI,
            $captainRecommendation,
            $transferRecommendations,
            $gameweekDecision,
            $chipRecommendations
        );


    gameweekPlayerRankingDatabaseTestResult(
        $snapshot
            instanceof RecommendationSnapshot,
        'RecommendationSnapshot accepts preserved Player Ranking Evidence.'
    );


    /*
     * ========================================================
     * INSERT THROUGH REAL REPOSITORY
     * ========================================================
     */

    $inserted =
        $snapshotRepository
            ->insertIfAbsent(
                $gameweekId,
                $snapshot
            );


    gameweekPlayerRankingDatabaseTestResult(
        $inserted === true,
        'Synthetic immutable recommendation snapshot is inserted through the real repository.'
    );


    /*
     * ========================================================
     * SCENARIO F
     * DATABASE ROUND TRIP
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario F: Player Ranking Database Round Trip<br>";
    echo "============================================<br>";


    $storedSnapshot =
        $snapshotRepository
            ->getByEntryAndGameweek(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekPlayerRankingDatabaseTestResult(
        is_array(
            $storedSnapshot
        ),
        'Synthetic recommendation snapshot is readable through the real repository.'
    );


    gameweekPlayerRankingDatabaseTestResult(
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


    gameweekPlayerRankingDatabaseTestResult(
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


    gameweekPlayerRankingDatabaseTestResult(
        array_key_exists(
            'player_rankings',
            $storedSnapshot
        ),
        'Stored snapshot exposes the Player Ranking Evidence field.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        (
            $storedSnapshot[
                'player_rankings'
            ]
            ?? null
        )
        ===
        $playerRankings,
        'Player Ranking Evidence survives the real database round trip exactly.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        (
            $storedSnapshot[
                'player_projections'
            ]
            ?? null
        )
        ===
        $playerProjections,
        'Squad-only player projections remain separate from Player Ranking Evidence.'
    );


    /*
     * ========================================================
     * SCENARIO G
     * ASSEMBLE REAL BACKTESTING EVIDENCE
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario G: Real Backtesting Evidence Assembly<br>";
    echo "============================================<br>";


    $assembledEvidence =
        $evidenceService
            ->getEvidence(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekPlayerRankingDatabaseTestResult(
        (
            $assembledEvidence[
                'status'
            ]
            ?? null
        )
        ===
        'Ready',
        'Real GameweekBacktestingEvidenceService returns Ready evidence.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        array_key_exists(
            'reason',
            $assembledEvidence
        )
        &&
        $assembledEvidence[
            'reason'
        ]
        ===
        null,
        'Ready assembled evidence has no incomplete reason.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        (
            (int) (
                $assembledEvidence[
                    'entry_id'
                ]
                ?? 0
            )
        )
        ===
        $syntheticEntryId,
        'Assembled evidence preserves synthetic entry identity.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        (
            (int) (
                $assembledEvidence[
                    'gameweek_id'
                ]
                ?? 0
            )
        )
        ===
        $gameweekId,
        'Assembled evidence preserves authoritative local gameweek identity.'
    );


    $assembledSnapshot =
        $assembledEvidence[
            'recommendation_snapshot'
        ]
        ?? null;


    gameweekPlayerRankingDatabaseTestResult(
        is_array(
            $assembledSnapshot
        ),
        'Assembled evidence contains the immutable recommendation snapshot.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        (
            $assembledSnapshot[
                'player_rankings'
            ]
            ?? null
        )
        ===
        $playerRankings,
        'GameweekBacktestingEvidenceService exposes preserved Player Ranking Evidence unchanged.'
    );


    $assembledOutcomes =
        $assembledEvidence[
            'player_outcomes'
        ]
        ?? [];


    gameweekPlayerRankingDatabaseTestResult(
        !empty(
            $assembledOutcomes
        ),
        'Assembled evidence contains genuine realised player outcomes.'
    );


    /*
     * ========================================================
     * SCENARIO H
     * COMPLETE DATABASE-TO-BACKTESTING PIPELINE
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario H: Complete Database-To-Ranking Pipeline<br>";
    echo "============================================<br>";


    $rankingResult =
        $rankingPipeline
            ->evaluate(
                $assembledEvidence
            );


    gameweekPlayerRankingDatabaseTestResult(
        (
            $rankingResult[
                'status'
            ]
            ?? null
        )
        ===
        'Ready',
        'Database-assembled evidence produces Ready Player Ranking backtesting.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        (
            (int) (
                $rankingResult[
                    'entry_id'
                ]
                ?? 0
            )
        )
        ===
        $syntheticEntryId,
        'Ranking pipeline preserves entry identity from database evidence.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        (
            (int) (
                $rankingResult[
                    'gameweek_id'
                ]
                ?? 0
            )
        )
        ===
        $gameweekId,
        'Ranking pipeline preserves authoritative local gameweek identity.'
    );


    $playerEvaluations =
        $rankingResult[
            'player_evaluations'
        ]
        ?? [];


    gameweekPlayerRankingDatabaseTestResult(
        count(
            $playerEvaluations
        )
        ===
        count(
            $playerRankings
        ),
        'Every synthetic historical ranking with a genuine outcome is evaluated.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        array_column(
            $playerEvaluations,
            'player_id'
        )
        ===
        $expectedPlayerIds,
        'Database-backed evaluation matches the correct real player identities.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        array_column(
            $playerEvaluations,
            'actual_points'
        )
        ===
        $expectedActualPoints,
        'Database-backed evaluation attaches the genuine realised FPL points.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        array_column(
            $playerEvaluations,
            'rank'
        )
        ===
        array_column(
            $playerRankings,
            'rank'
        ),
        'Database-backed evaluation preserves historical non-contiguous ranks exactly.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        array_column(
            $playerEvaluations,
            'intelligence_score'
        )
        ===
        array_column(
            $playerRankings,
            'intelligence_score'
        ),
        'Database-backed evaluation preserves historical Intelligence Scores exactly.'
    );


    /*
     * ========================================================
     * SCENARIO I
     * REAL DATABASE-BACKED METRICS
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario I: Database-Backed Ranking Metrics<br>";
    echo "============================================<br>";


    $metrics =
        $rankingResult[
            'metrics'
        ]
        ?? null;


    gameweekPlayerRankingDatabaseTestResult(
        is_array(
            $metrics
        ),
        'Database-backed ranking pipeline produces a metrics contract.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        (
            $metrics[
                'sample_size'
            ]
            ?? null
        )
        ===
        count(
            $playerRankings
        ),
        'Ranking metrics sample size matches genuine evaluated player evidence.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        array_key_exists(
            'pearson_correlation',
            $metrics
        ),
        'Database-backed metrics expose Intelligence Score versus realised-points Pearson correlation.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        array_key_exists(
            'spearman_rank_correlation',
            $metrics
        ),
        'Database-backed metrics expose historical-ranking versus realised-ranking Spearman correlation.'
    );


    /*
     * Correlation may legitimately be null if the selected real
     * outcomes contain no variation.
     *
     * This test therefore verifies the metric contract rather
     * than manufacturing a statistical relationship from the
     * genuine production outcomes.
     */
    gameweekPlayerRankingDatabaseTestResult(
        $metrics[
            'pearson_correlation'
        ]
        ===
        null
        ||
        is_numeric(
            $metrics[
                'pearson_correlation'
            ]
        ),
        'Pearson correlation remains either genuine numeric evidence or mathematically unavailable.'
    );


    gameweekPlayerRankingDatabaseTestResult(
        $metrics[
            'spearman_rank_correlation'
        ]
        ===
        null
        ||
        is_numeric(
            $metrics[
                'spearman_rank_correlation'
            ]
        ),
        'Spearman correlation remains either genuine numeric evidence or mathematically unavailable.'
    );


    /*
     * ========================================================
     * SCENARIO J
     * DATABASE TRANSACTION ROLLBACK
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario J: Database Rollback<br>";
    echo "============================================<br>";


    if (
        $db->inTransaction()
    ) {

        $rolledBack =
            $db->rollBack();


        gameweekPlayerRankingDatabaseTestResult(
            $rolledBack === true,
            'Database transaction rolls back successfully.'
        );

    } else {

        gameweekPlayerRankingDatabaseTestResult(
            false,
            'Database transaction remains active until rollback.'
        );
    }


    $afterRollback =
        $snapshotRepository
            ->getByEntryAndGameweek(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekPlayerRankingDatabaseTestResult(
        $afterRollback === null,
        'Synthetic recommendation snapshot does not remain after rollback.'
    );


} catch (
    Throwable $exception
) {

    if (
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    gameweekPlayerRankingDatabaseTestResult(
        false,
        'Database-backed ranking integration completes without exception: '
        . $exception->getMessage()
    );
}


/*
 * ============================================================
 * FINAL SAFETY CHECK
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Final Database Safety Check<br>";
echo "============================================<br>";


$finalSyntheticSnapshot =
    $snapshotRepository
        ->getByEntryAndGameweek(
            $syntheticEntryId,
            $gameweekId
        );


gameweekPlayerRankingDatabaseTestResult(
    $finalSyntheticSnapshot === null,
    'Database is left without the synthetic Player Ranking snapshot.'
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