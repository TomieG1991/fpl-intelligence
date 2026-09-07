<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Projection Backtesting Database Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekProjectionBacktestingDatabaseIntegrationTestResult(
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


gameweekProjectionBacktestingDatabaseIntegrationTestResult(
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


$playerProjectionBacktestingService =
    new PlayerProjectionBacktestingService();


$playerProjectionBacktestingMetricsService =
    new PlayerProjectionBacktestingMetricsService();


$gameweekProjectionBacktestingService =
    new GameweekProjectionBacktestingService(
        $playerProjectionBacktestingService,
        $playerProjectionBacktestingMetricsService
    );


gameweekProjectionBacktestingDatabaseIntegrationTestResult(
    $gameweekBacktestingEvidenceService
        instanceof GameweekBacktestingEvidenceService,
    'Real historical evidence service can be constructed.'
);


gameweekProjectionBacktestingDatabaseIntegrationTestResult(
    $gameweekProjectionBacktestingService
        instanceof GameweekProjectionBacktestingService,
    'Real projection backtesting service can be constructed.'
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


gameweekProjectionBacktestingDatabaseIntegrationTestResult(
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


gameweekProjectionBacktestingDatabaseIntegrationTestResult(
    !empty(
        $realOutcomes
    ),
    'Real player outcome evidence is available for discovered gameweek.'
);


/*
 * ============================================================
 * SELECT REAL PLAYERS FOR SYNTHETIC HISTORICAL PROJECTION
 * ============================================================
 *
 * We use genuine realised outcomes, but create a temporary
 * recommendation snapshot for a synthetic FPL entry.
 *
 * The snapshot is inserted inside a database transaction and
 * rolled back after the test.
 * ============================================================
 */

$selectedOutcomes =
    array_slice(
        $realOutcomes,
        0,
        min(
            4,
            count(
                $realOutcomes
            )
        )
    );


gameweekProjectionBacktestingDatabaseIntegrationTestResult(
    count(
        $selectedOutcomes
    )
    >= 2,
    'At least two real player outcomes are available for projection backtesting.'
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
 * BUILD SYNTHETIC PRE-DEADLINE PROJECTION EVIDENCE
 * ============================================================
 *
 * These projection numbers are deterministic test inputs.
 *
 * The realised outcomes remain genuine database evidence.
 * ============================================================
 */

$playerProjections =
    [];


foreach (
    $selectedOutcomes
    as $index => $outcome
) {

    $playerId =
        (int) $outcome[
            'player_id'
        ];


    $actualPoints =
        (int) $outcome[
            'total_points'
        ];


    $actualMinutes =
        (int) $outcome[
            'minutes'
        ];


    /*
     * Alternate under- and over-projection so the resulting
     * metrics prove directional and absolute error behaviour.
     */
    if (
        $index % 2 === 0
    ) {

        $projectedPoints =
            (float) $actualPoints
            -
            1.5;

        $projectedMinutes =
            $actualMinutes
            +
            10;

    } else {

        $projectedPoints =
            (float) $actualPoints
            +
            2.0;

        $projectedMinutes =
            max(
                0,
                $actualMinutes
                -
                15
            );
    }


    /*
     * One player deliberately has no projected minutes so we
     * prove points and minutes maintain independent samples.
     */
    if (
        $index
        ===
        count(
            $selectedOutcomes
        )
        -
        1
    ) {

        $projectedMinutes =
            null;
    }


    $playerProjections[] = [

        'player_id' =>
            $playerId,

        'fpl_player_id' =>
            null,

        'name' =>
            'Integration Test Player '
            . $playerId,

        'position' =>
            null,

        'team_id' =>
            null,

        'price' =>
            null,

        'intelligence_score' =>
            null,

        'projected_points' =>
            $projectedPoints,

        'projected_minutes' =>
            $projectedMinutes,

        'projection_confidence' =>
            0.75,

        'projection_confidence_percent' =>
            75.0,

        'projection_confidence_label' =>
            'High',

        'projected_points_components' =>
            [],

        'projected_points_inputs' =>
            [],

        'has_projected_points' =>
            true
    ];
}


/*
 * ============================================================
 * SYNTHETIC ENTRY AND SNAPSHOT TIMESTAMPS
 * ============================================================
 */

$syntheticEntryId =
    935010002;


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
        $playerProjections,
        [],
        [],
        [],
        [],
        []
    );


gameweekProjectionBacktestingDatabaseIntegrationTestResult(
    $snapshot
        instanceof RecommendationSnapshot,
    'Synthetic immutable recommendation snapshot can be constructed.'
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


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
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


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
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


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
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


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
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


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
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


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        !empty(
            $evidence[
                'player_outcomes'
            ]
            ?? []
        ),
        'Real historical evidence contains genuine completed-gameweek outcomes.'
    );


    /*
     * ========================================================
     * SCENARIO C
     * END-TO-END PROJECTION BACKTESTING
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario C: End-to-End Projection Backtesting<br>";
    echo "============================================<br>";


    $result =
        $gameweekProjectionBacktestingService
            ->evaluate(
                $evidence
            );


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        (
            $result[
                'status'
            ]
            ?? null
        )
        ===
        'Ready',
        'Database-backed evidence produces Ready projection backtesting.'
    );


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        (
            $result[
                'entry_id'
            ]
            ?? null
        )
        ===
        $syntheticEntryId,
        'End-to-end projection backtesting preserves entry identity.'
    );


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        (
            $result[
                'gameweek_id'
            ]
            ?? null
        )
        ===
        $gameweekId,
        'End-to-end projection backtesting preserves gameweek identity.'
    );


    /*
     * ========================================================
     * SCENARIO D
     * PLAYER-LEVEL COMPARISONS
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario D: Real Outcome Comparison Evidence<br>";
    echo "============================================<br>";


    $evaluations =
        $result[
            'player_evaluations'
        ]
        ?? [];


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        count(
            $evaluations
        )
        ===
        count(
            $selectedOutcomes
        ),
        'Every preserved projection with a real outcome is evaluated.'
    );


    $expectedPointsErrors =
        [];


    $expectedMinutesErrors =
        [];


    foreach (
        $playerProjections
        as $projection
    ) {

        $matchingOutcome =
            null;


        foreach (
            $selectedOutcomes
            as $outcome
        ) {

            if (
                (int) $outcome[
                    'player_id'
                ]
                ===
                (int) $projection[
                    'player_id'
                ]
            ) {

                $matchingOutcome =
                    $outcome;

                break;
            }
        }


        if (
            $matchingOutcome
            ===
            null
        ) {

            continue;
        }


        $expectedPointsErrors[] =
            (float) (
                (int) $matchingOutcome[
                    'total_points'
                ]
                -
                (float) $projection[
                    'projected_points'
                ]
            );


        if (
            $projection[
                'projected_minutes'
            ]
            !==
            null
        ) {

            $expectedMinutesErrors[] =
                (float) (
                    (int) $matchingOutcome[
                        'minutes'
                    ]
                    -
                    (float) $projection[
                        'projected_minutes'
                    ]
                );
        }
    }


    foreach (
        $evaluations
        as $evaluation
    ) {

        $playerId =
            (int) $evaluation[
                'player_id'
            ];


        $matchingProjection =
            null;


        $matchingOutcome =
            null;


        foreach (
            $playerProjections
            as $projection
        ) {

            if (
                (int) $projection[
                    'player_id'
                ]
                ===
                $playerId
            ) {

                $matchingProjection =
                    $projection;

                break;
            }
        }


        foreach (
            $selectedOutcomes
            as $outcome
        ) {

            if (
                (int) $outcome[
                    'player_id'
                ]
                ===
                $playerId
            ) {

                $matchingOutcome =
                    $outcome;

                break;
            }
        }


        gameweekProjectionBacktestingDatabaseIntegrationTestResult(
            $matchingProjection
                !==
                null
            &&
            $matchingOutcome
                !==
                null,
            'Evaluation player is backed by both preserved projection and genuine outcome evidence.'
        );


        if (
            $matchingProjection
            !==
            null
            &&
            $matchingOutcome
            !==
            null
        ) {

            $expectedPointsError =
                (float) (
                    (int) $matchingOutcome[
                        'total_points'
                    ]
                    -
                    (float) $matchingProjection[
                        'projected_points'
                    ]
                );


            gameweekProjectionBacktestingDatabaseIntegrationTestResult(
                abs(
                    $evaluation[
                        'points_error'
                    ]
                    -
                    $expectedPointsError
                )
                <
                0.0000001,
                'Player directional points error uses genuine realised FPL points.'
            );
        }
    }


    /*
     * ========================================================
     * SCENARIO E
     * AGGREGATE POINTS METRICS
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario E: Database-Backed Points Metrics<br>";
    echo "============================================<br>";


    $pointsMetrics =
        $result[
            'metrics'
        ][
            'points'
        ]
        ?? [];


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        (
            $pointsMetrics[
                'sample_size'
            ]
            ?? null
        )
        ===
        count(
            $expectedPointsErrors
        ),
        'Points metrics sample size matches evaluated real outcomes.'
    );


    $expectedPointsMeanError =
        array_sum(
            $expectedPointsErrors
        )
        /
        count(
            $expectedPointsErrors
        );


    $expectedPointsAbsoluteErrors =
        array_map(
            static function (
                float $error
            ): float {

                return
                    abs(
                        $error
                    );
            },
            $expectedPointsErrors
        );


    $expectedPointsMae =
        array_sum(
            $expectedPointsAbsoluteErrors
        )
        /
        count(
            $expectedPointsAbsoluteErrors
        );


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        abs(
            $pointsMetrics[
                'mean_error'
            ]
            -
            $expectedPointsMeanError
        )
        <
        0.0000001,
        'Points mean error is derived from genuine realised outcomes.'
    );


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        abs(
            $pointsMetrics[
                'mean_absolute_error'
            ]
            -
            $expectedPointsMae
        )
        <
        0.0000001,
        'Points MAE is derived from genuine realised outcomes.'
    );


    /*
     * ========================================================
     * SCENARIO F
     * INDEPENDENT MINUTES METRICS
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario F: Database-Backed Minutes Metrics<br>";
    echo "============================================<br>";


    $minutesMetrics =
        $result[
            'metrics'
        ][
            'minutes'
        ]
        ?? [];


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        (
            $minutesMetrics[
                'sample_size'
            ]
            ?? null
        )
        ===
        count(
            $expectedMinutesErrors
        ),
        'Minutes sample excludes projection with unavailable projected minutes.'
    );


    if (
        !empty(
            $expectedMinutesErrors
        )
    ) {

        $expectedMinutesMeanError =
            array_sum(
                $expectedMinutesErrors
            )
            /
            count(
                $expectedMinutesErrors
            );


        $expectedMinutesAbsoluteErrors =
            array_map(
                static function (
                    float $error
                ): float {

                    return
                        abs(
                            $error
                        );
                },
                $expectedMinutesErrors
            );


        $expectedMinutesMae =
            array_sum(
                $expectedMinutesAbsoluteErrors
            )
            /
            count(
                $expectedMinutesAbsoluteErrors
            );


        gameweekProjectionBacktestingDatabaseIntegrationTestResult(
            abs(
                $minutesMetrics[
                    'mean_error'
                ]
                -
                $expectedMinutesMeanError
            )
            <
            0.0000001,
            'Minutes mean error is derived from genuine realised minutes.'
        );


        gameweekProjectionBacktestingDatabaseIntegrationTestResult(
            abs(
                $minutesMetrics[
                    'mean_absolute_error'
                ]
                -
                $expectedMinutesMae
            )
            <
            0.0000001,
            'Minutes MAE is derived from genuine realised minutes.'
        );
    }


    /*
     * ========================================================
     * SCENARIO G
     * SNAPSHOT IMMUTABILITY
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario G: Immutable Historical Snapshot<br>";
    echo "============================================<br>";


    $insertedAgain =
        $recommendationSnapshotRepository
            ->insertIfAbsent(
                $gameweekId,
                $snapshot
            );


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        $insertedAgain === false,
        'Existing historical recommendation snapshot cannot be inserted again.'
    );


    $storedSnapshot =
        $recommendationSnapshotRepository
            ->getByEntryAndGameweek(
                $syntheticEntryId,
                $gameweekId
            );


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        (
            $storedSnapshot[
                'player_projections'
            ]
            ?? null
        )
        ===
        $playerProjections,
        'Stored historical player projection evidence remains unchanged.'
    );


    /*
     * ========================================================
     * SCENARIO H
     * NO SYNTHETIC OVERALL SCORE
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario H: Backtesting Scope Boundary<br>";
    echo "============================================<br>";


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'overall_score',
            $result
        ),
        'End-to-end backtesting does not manufacture an overall model score.'
    );


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'captain_result',
            $result
        ),
        'End-to-end projection backtesting does not evaluate captain recommendation.'
    );


    gameweekProjectionBacktestingDatabaseIntegrationTestResult(
        !array_key_exists(
            'transfer_result',
            $result
        ),
        'End-to-end projection backtesting does not evaluate transfer recommendation.'
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


gameweekProjectionBacktestingDatabaseIntegrationTestResult(
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


gameweekProjectionBacktestingDatabaseIntegrationTestResult(
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


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}