<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Projection Calibration Diagnostics History Real Data Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function projectionDiagnosticsHistoryRealDataResult(
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
 * REAL ENTRY
 * ============================================================
 */

$entryId =
    2702264;


/*
 * ============================================================
 * REAL PRODUCTION DEPENDENCIES
 * ============================================================
 */

try {

    $database =
        new Database();


    $db =
        $database->getConnection();


    projectionDiagnosticsHistoryRealDataResult(
        $db instanceof PDO,
        'Database connection is available.'
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


    /*
     * Authoritative historical eligibility.
     */
    $evidenceService =
        new GameweekBacktestingEvidenceService(
            $gameweekRepository,
            $availability,
            $snapshotRepository,
            $outcomeService
        );


    /*
     * Existing player-level projection comparison.
     */
    $playerProjectionBacktestingService =
        new PlayerProjectionBacktestingService();


    /*
     * Existing projection aggregate metrics.
     */
    $projectionMetricsService =
        new PlayerProjectionBacktestingMetricsService();


    /*
     * Existing Ready-gameweek projection orchestration.
     */
    $gameweekProjectionBacktestingService =
        new GameweekProjectionBacktestingService(
            $playerProjectionBacktestingService,
            $projectionMetricsService
        );


    /*
     * New pooled diagnostic analysis.
     */
    $diagnosticsService =
        new ProjectionCalibrationDiagnosticsService(
            $projectionMetricsService
        );


    /*
     * New historical diagnostic orchestration.
     */
    $historyService =
        new ProjectionCalibrationDiagnosticsHistoryService(
            $gameweekRepository,
            $evidenceService,
            $gameweekProjectionBacktestingService,
            $diagnosticsService
        );


    /*
     * ========================================================
     * RUN REAL HISTORICAL DIAGNOSTICS
     * ========================================================
     */

    $result =
        $historyService->evaluate(
            $entryId
        );


    /*
     * ========================================================
     * TOP-LEVEL RESULT CONTRACT
     * ========================================================
     */

    projectionDiagnosticsHistoryRealDataResult(
        is_array(
            $result
        ),
        'Historical projection diagnostics return an evaluation result.'
    );


    projectionDiagnosticsHistoryRealDataResult(
        (
            $result[
                'entry_id'
            ]
            ?? null
        )
        ===
        $entryId,
        'Historical projection diagnostics preserve the real FPL entry ID.'
    );


    /*
     * ========================================================
     * HISTORICAL COVERAGE
     * ========================================================
     */

    $totalGameweeks =
        (int) (
            $result[
                'total_gameweeks'
            ]
            ?? 0
        );


    $readyGameweeks =
        (int) (
            $result[
                'ready_gameweeks'
            ]
            ?? 0
        );


    $gameweekAudit =
        is_array(
            $result[
                'gameweeks'
            ]
            ?? null
        )
            ? $result[
                'gameweeks'
            ]
            : [];


    $playerEvaluations =
        is_array(
            $result[
                'player_evaluations'
            ]
            ?? null
        )
            ? $result[
                'player_evaluations'
            ]
            : [];


    projectionDiagnosticsHistoryRealDataResult(
        $totalGameweeks > 0,
        'Stored gameweek history is available.'
    );


    projectionDiagnosticsHistoryRealDataResult(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $totalGameweeks,
        'Ready gameweek count is internally valid.'
    );


    projectionDiagnosticsHistoryRealDataResult(
        count(
            $gameweekAudit
        )
        ===
        $totalGameweeks,
        'Gameweek audit contains every valid stored gameweek considered.'
    );


    /*
     * ========================================================
     * COVERAGE OUTPUT
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "REAL HISTORICAL COVERAGE<br>";
    echo "============================================<br>";

    echo "Entry ID: "
        . $entryId
        . "<br>";

    echo "Stored gameweeks considered: "
        . $totalGameweeks
        . "<br>";

    echo "Ready gameweeks: "
        . $readyGameweeks
        . "<br>";

    echo "Pooled player projection evaluations: "
        . count(
            $playerEvaluations
        )
        . "<br>";


    /*
     * ========================================================
     * GAMEWEEK COVERAGE AUDIT
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "GAMEWEEK COVERAGE AUDIT<br>";
    echo "============================================<br>";


    foreach (
        $gameweekAudit
        as $gameweekResult
    ) {

        if (!is_array($gameweekResult)) {

            continue;
        }


        echo "GW "
            . htmlspecialchars(
                (string) (
                    $gameweekResult[
                        'fpl_gameweek_id'
                    ]
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | "
            . htmlspecialchars(
                (string) (
                    $gameweekResult[
                        'name'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | "
            . htmlspecialchars(
                (string) (
                    $gameweekResult[
                        'status'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            );


        $reason =
            $gameweekResult[
                'reason'
            ]
            ?? null;


        if (
            $reason !== null
            &&
            trim(
                (string) $reason
            ) !== ''
        ) {

            echo " | "
                . htmlspecialchars(
                    (string) $reason,
                    ENT_QUOTES,
                    'UTF-8'
                );
        }


        echo "<br>";
    }


    /*
     * ========================================================
     * PLAYER-EVALUATION STRUCTURE
     * ========================================================
     */

    $structurallyValidEvaluations =
        0;


    foreach (
        $playerEvaluations
        as $evaluation
    ) {

        if (
            !is_array(
                $evaluation
            )
        ) {

            continue;
        }


        if (
            (int) (
                $evaluation[
                    'gameweek_id'
                ]
                ?? 0
            )
            > 0
            &&
            (int) (
                $evaluation[
                    'player_id'
                ]
                ?? 0
            )
            > 0
            &&
            is_numeric(
                $evaluation[
                    'projected_points'
                ]
                ?? null
            )
            &&
            is_numeric(
                $evaluation[
                    'actual_points'
                ]
                ?? null
            )
        ) {

            $structurallyValidEvaluations++;
        }
    }


    projectionDiagnosticsHistoryRealDataResult(
        $structurallyValidEvaluations
        ===
        count(
            $playerEvaluations
        ),
        'Every pooled historical projection evaluation has valid gameweek, player and points evidence.'
    );


    /*
     * ========================================================
     * DIAGNOSTIC CONTRACT
     * ========================================================
     */

    $diagnostics =
        is_array(
            $result[
                'diagnostics'
            ]
            ?? null
        )
            ? $result[
                'diagnostics'
            ]
            : [];


    projectionDiagnosticsHistoryRealDataResult(
        is_array(
            $diagnostics[
                'overall'
            ]
            ?? null
        ),
        'Real historical diagnostics expose overall metrics.'
    );


    projectionDiagnosticsHistoryRealDataResult(
        is_array(
            $diagnostics[
                'by_position'
            ]
            ?? null
        ),
        'Real historical diagnostics expose position groups.'
    );


    projectionDiagnosticsHistoryRealDataResult(
        is_array(
            $diagnostics[
                'by_confidence'
            ]
            ?? null
        ),
        'Real historical diagnostics expose Projection Confidence groups.'
    );


    projectionDiagnosticsHistoryRealDataResult(
        array_keys(
            $diagnostics[
                'by_position'
            ]
            ?? []
        )
        === [
            'GK',
            'DEF',
            'MID',
            'FWD',
            'Unknown'
        ],
        'Real position diagnostics preserve the deterministic group contract.'
    );


    projectionDiagnosticsHistoryRealDataResult(
        array_keys(
            $diagnostics[
                'by_confidence'
            ]
            ?? []
        )
        === [
            'High',
            'Moderate',
            'Low',
            'Very Low',
            'Unavailable'
        ],
        'Real confidence diagnostics preserve the deterministic group contract.'
    );


    /*
     * ========================================================
     * SAMPLE CONSISTENCY
     * ========================================================
     */

    $overallPointsSample =
        (int) (
            $diagnostics[
                'overall'
            ][
                'points'
            ][
                'sample_size'
            ]
            ?? 0
        );


    projectionDiagnosticsHistoryRealDataResult(
        $overallPointsSample
        ===
        count(
            $playerEvaluations
        ),
        'Overall diagnostic points sample matches pooled player evaluations.'
    );


    /*
     * Current history may legitimately contain no Ready
     * gameweeks yet.
     *
     * When that is true, no observations or synthetic accuracy
     * values may be manufactured.
     */
    if ($readyGameweeks === 0) {

        projectionDiagnosticsHistoryRealDataResult(
            $playerEvaluations
            ===
            [],
            'Zero Ready gameweeks produce no historical player projection evaluations.'
        );


        projectionDiagnosticsHistoryRealDataResult(
            $overallPointsSample
            ===
            0,
            'Zero Ready gameweeks produce a zero points diagnostic sample.'
        );


        projectionDiagnosticsHistoryRealDataResult(
            (
                $diagnostics[
                    'overall'
                ][
                    'points'
                ][
                    'mean_error'
                ]
                ?? null
            )
            ===
            null,
            'Zero Ready gameweeks do not manufacture a points mean error.'
        );


        projectionDiagnosticsHistoryRealDataResult(
            (
                $diagnostics[
                    'overall'
                ][
                    'points'
                ][
                    'mean_absolute_error'
                ]
                ?? null
            )
            ===
            null,
            'Zero Ready gameweeks do not manufacture a points MAE.'
        );
    }


    /*
     * ========================================================
     * DIAGNOSTIC OUTPUT
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "PROJECTION CALIBRATION DIAGNOSTICS<br>";
    echo "============================================<br>";

    echo "Points sample: "
        . $overallPointsSample
        . "<br>";

    echo "Points mean error: "
        . (
            $diagnostics[
                'overall'
            ][
                'points'
            ][
                'mean_error'
            ]
            ?? 'N/A'
        )
        . "<br>";

    echo "Points MAE: "
        . (
            $diagnostics[
                'overall'
            ][
                'points'
            ][
                'mean_absolute_error'
            ]
            ?? 'N/A'
        )
        . "<br>";

    echo "Minutes sample: "
        . (
            $diagnostics[
                'overall'
            ][
                'minutes'
            ][
                'sample_size'
            ]
            ?? 0
        )
        . "<br>";

    echo "Minutes mean error: "
        . (
            $diagnostics[
                'overall'
            ][
                'minutes'
            ][
                'mean_error'
            ]
            ?? 'N/A'
        )
        . "<br>";

    echo "Minutes MAE: "
        . (
            $diagnostics[
                'overall'
            ][
                'minutes'
            ][
                'mean_absolute_error'
            ]
            ?? 'N/A'
        )
        . "<br>";


} catch (
    Throwable $exception
) {

    projectionDiagnosticsHistoryRealDataResult(
        false,
        'Real historical projection diagnostics execute without exception: '
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


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}