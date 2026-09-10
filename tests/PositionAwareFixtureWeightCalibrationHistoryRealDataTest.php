<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Position-Aware Fixture Weight Calibration History Real Data Test<br>";
echo "============================================<br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function positionAwareCalibrationHistoryRealDataResult(
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
 * POSITION-AWARE FIXTURE WEIGHT CANDIDATES
 * ============================================================
 *
 * This deliberately includes:
 *
 * - the Base Fixture-only boundary
 * - a broad sweep through the weighting space
 * - the current production 75 / 25 blend
 * - the Position Performance-only boundary
 *
 * This test observes historical evidence only.
 *
 * It does NOT:
 *
 * - choose a winning candidate
 * - apply a new production weight
 * - modify FixtureIntelligence
 * - reconstruct missing recommendation evidence
 */

$weightCandidates = [

    [
        'base_fixture_weight' => 1.00,
        'position_performance_weight' => 0.00
    ],

    [
        'base_fixture_weight' => 0.90,
        'position_performance_weight' => 0.10
    ],

    [
        'base_fixture_weight' => 0.80,
        'position_performance_weight' => 0.20
    ],

    /*
     * Current production model.
     */
    [
        'base_fixture_weight' => 0.75,
        'position_performance_weight' => 0.25
    ],

    [
        'base_fixture_weight' => 0.70,
        'position_performance_weight' => 0.30
    ],

    [
        'base_fixture_weight' => 0.60,
        'position_performance_weight' => 0.40
    ],

    [
        'base_fixture_weight' => 0.50,
        'position_performance_weight' => 0.50
    ],

    [
        'base_fixture_weight' => 0.40,
        'position_performance_weight' => 0.60
    ],

    [
        'base_fixture_weight' => 0.30,
        'position_performance_weight' => 0.70
    ],

    [
        'base_fixture_weight' => 0.20,
        'position_performance_weight' => 0.80
    ],

    [
        'base_fixture_weight' => 0.10,
        'position_performance_weight' => 0.90
    ],

    [
        'base_fixture_weight' => 0.00,
        'position_performance_weight' => 1.00
    ]
];


/*
 * ============================================================
 * BUILD REAL PRODUCTION DEPENDENCIES
 * ============================================================
 */

try {

    $database =
        new Database();


    $db =
        $database->getConnection();


    positionAwareCalibrationHistoryRealDataResult(
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
     * Authoritative historical gameweek eligibility.
     */
    $evidenceService =
        new GameweekBacktestingEvidenceService(
            $gameweekRepository,
            $availability,
            $snapshotRepository,
            $outcomeService
        );


    /*
     * Shared immutable recommendation-time player evidence
     * joined with realised player outcomes.
     */
    $historicalEvidenceService =
        new PlayerCalibrationHistoricalEvidenceService(
            $snapshotRepository,
            $outcomeService
        );


    /*
     * Position-aware component-level calibration metrics.
     */
    $metricsService =
        new IntelligenceScoreBacktestMetricsService();


    $calibrationService =
        new PositionAwareFixtureWeightCalibrationService(
            $metricsService
        );


    /*
     * The third constructor dependency is retained only for
     * compatibility with the current history-service contract.
     *
     * Position-aware history must NOT invoke a Strength /
     * Fixture evaluation service.
     *
     * Supplying stdClass here makes that architectural boundary
     * explicit: any accidental call to evaluate() would fail
     * this real-data test rather than silently crossing models.
     */
    $historyService =
        new PositionAwareFixtureWeightCalibrationHistoryService(
            $gameweekRepository,
            $evidenceService,
            new stdClass(),
            $calibrationService,
            $historicalEvidenceService
        );


    /*
     * ========================================================
     * RUN REAL HISTORICAL POSITION-AWARE CALIBRATION
     * ========================================================
     */

    $result =
        $historyService->evaluate(
            $entryId,
            $weightCandidates
        );


    /*
     * ========================================================
     * RESULT CONTRACT
     * ========================================================
     */

    positionAwareCalibrationHistoryRealDataResult(
        is_array(
            $result
        ),
        'Historical position-aware calibration returns an evaluation result.'
    );


    positionAwareCalibrationHistoryRealDataResult(
        (
            $result[
                'entry_id'
            ]
            ?? null
        )
        === $entryId,
        'Historical position-aware calibration preserves the real FPL entry ID.'
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


    $historicalRows =
        is_array(
            $result[
                'historical_rows'
            ]
            ?? null
        )
            ? $result[
                'historical_rows'
            ]
            : [];


    positionAwareCalibrationHistoryRealDataResult(
        $totalGameweeks >= 0,
        'Stored gameweek count is available.'
    );


    positionAwareCalibrationHistoryRealDataResult(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $totalGameweeks,
        'Ready gameweek count is internally valid.'
    );


    positionAwareCalibrationHistoryRealDataResult(
        is_array(
            $historicalRows
        ),
        'Combined historical player evidence is available.'
    );


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


    echo "Historical player observations: "
        . count(
            $historicalRows
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


    $gameweekResults =
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


    positionAwareCalibrationHistoryRealDataResult(
        count(
            $gameweekResults
        )
        === $totalGameweeks,
        'Gameweek audit contains every valid stored gameweek considered.'
    );


    foreach (
        $gameweekResults
        as $gameweekResult
    ) {

        if (!is_array($gameweekResult)) {

            continue;
        }


        $fplGameweekId =
            $gameweekResult[
                'fpl_gameweek_id'
            ]
            ?? 'N/A';


        $name =
            $gameweekResult[
                'name'
            ]
            ?? 'Unknown';


        $status =
            $gameweekResult[
                'status'
            ]
            ?? 'Unknown';


        $reason =
            $gameweekResult[
                'reason'
            ]
            ?? null;


        echo "GW "
            . htmlspecialchars(
                (string) $fplGameweekId,
                ENT_QUOTES,
                'UTF-8'
            )
            . " | "
            . htmlspecialchars(
                (string) $name,
                ENT_QUOTES,
                'UTF-8'
            )
            . " | "
            . htmlspecialchars(
                (string) $status,
                ENT_QUOTES,
                'UTF-8'
            );


        if (
            $reason !== null
            &&
            trim(
                (string) $reason
            )
            !== ''
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
     * AGGREGATE POSITION-AWARE FIXTURE CALIBRATION
     * ========================================================
     */

    $calibration =
        is_array(
            $result[
                'calibration'
            ]
            ?? null
        )
            ? $result[
                'calibration'
            ]
            : [];


    $evaluations =
        is_array(
            $calibration[
                'evaluations'
            ]
            ?? null
        )
            ? $calibration[
                'evaluations'
            ]
            : [];


    positionAwareCalibrationHistoryRealDataResult(
        count(
            $evaluations
        )
        === count(
            $weightCandidates
        ),
        'Every explicitly supplied position-aware weight candidate is evaluated.'
    );


    echo "<br>";
    echo "============================================<br>";
    echo "AGGREGATE POSITION-AWARE FIXTURE CALIBRATION<br>";
    echo "============================================<br>";


    foreach (
        $evaluations
        as $evaluation
    ) {

        if (!is_array($evaluation)) {

            continue;
        }


        $baseFixtureWeight =
            $evaluation[
                'base_fixture_weight'
            ]
            ?? null;


        $positionPerformanceWeight =
            $evaluation[
                'position_performance_weight'
            ]
            ?? null;


        $metrics =
            is_array(
                $evaluation[
                    'metrics'
                ]
                ?? null
            )
                ? $evaluation[
                    'metrics'
                ]
                : [];


        $totalPlayers =
            $metrics[
                'total_players'
            ]
            ?? 0;


        $comparablePlayers =
            $metrics[
                'comparable_players'
            ]
            ?? 0;


        $unavailablePlayers =
            $metrics[
                'unavailable_players'
            ]
            ?? 0;


        $correlation =
            $metrics[
                'correlation'
            ]
            ?? null;


        echo "Base Fixture: ";


        echo is_numeric(
            $baseFixtureWeight
        )
            ? number_format(
                (float) $baseFixtureWeight * 100,
                0
            )
                . "%"
            : 'N/A';


        echo " | Position Performance: ";


        echo is_numeric(
            $positionPerformanceWeight
        )
            ? number_format(
                (float) $positionPerformanceWeight * 100,
                0
            )
                . "%"
            : 'N/A';


        echo " | Total: "
            . htmlspecialchars(
                (string) $totalPlayers,
                ENT_QUOTES,
                'UTF-8'
            );


        echo " | Comparable: "
            . htmlspecialchars(
                (string) $comparablePlayers,
                ENT_QUOTES,
                'UTF-8'
            );


        echo " | Unavailable: "
            . htmlspecialchars(
                (string) $unavailablePlayers,
                ENT_QUOTES,
                'UTF-8'
            );


        echo " | Correlation: ";


        echo is_numeric(
            $correlation
        )
            ? number_format(
                (float) $correlation,
                6
            )
            : 'N/A';


        /*
         * Make the current production model obvious in the
         * diagnostic output without treating it as preferred.
         */
        if (
            is_numeric(
                $baseFixtureWeight
            )
            &&
            is_numeric(
                $positionPerformanceWeight
            )
            &&
            abs(
                (float) $baseFixtureWeight
                -
                0.75
            )
            < 0.000001
            &&
            abs(
                (float) $positionPerformanceWeight
                -
                0.25
            )
            < 0.000001
        ) {

            echo " | CURRENT PRODUCTION";
        }


        echo "<br>";
    }


    /*
     * ========================================================
     * POSITION-AWARE CALIBRATION EVIDENCE COMPLETENESS
     * ========================================================
     *
     * A row can distinguish alternative position-aware blends
     * only when it contains:
     *
     * - a valid player position
     * - the historical base next-fixture rating
     * - the position-relevant opponent rating
     *
     * GK / DEF require opponent Attack.
     * MID / FWD require opponent Defence.
     *
     * Realised points are tracked separately because missing
     * actual points prevent correlation comparison but do not
     * invalidate the recommendation-time fixture evidence.
     */

    $completeCalibrationRows =
        0;


    $missingCalibrationRows =
        0;


    $missingPositionRows =
        0;


    $missingBaseFixtureRows =
        0;


    $missingRelevantOpponentRows =
        0;


    $missingActualRows =
        0;


    foreach (
        $historicalRows
        as $row
    ) {

        if (!is_array($row)) {

            continue;
        }


        $position =
            strtoupper(
                trim(
                    (string) (
                        $row[
                            'position'
                        ]
                        ?? ''
                    )
                )
            );


        $validPosition =
            in_array(
                $position,
                [
                    'GK',
                    'DEF',
                    'MID',
                    'FWD'
                ],
                true
            );


        if (!$validPosition) {

            $missingPositionRows++;
        }


        $hasBaseFixture =
            array_key_exists(
                'base_next_fixture_rating',
                $row
            )
            &&
            $row[
                'base_next_fixture_rating'
            ] !== null
            &&
            is_numeric(
                $row[
                    'base_next_fixture_rating'
                ]
            );


        if (!$hasBaseFixture) {

            $missingBaseFixtureRows++;
        }


        $hasRelevantOpponentRating =
            false;


        if (
            $position === 'GK'
            ||
            $position === 'DEF'
        ) {

            $hasRelevantOpponentRating =
                array_key_exists(
                    'next_opponent_attack_rating',
                    $row
                )
                &&
                $row[
                    'next_opponent_attack_rating'
                ] !== null
                &&
                is_numeric(
                    $row[
                        'next_opponent_attack_rating'
                    ]
                );

        } elseif (
            $position === 'MID'
            ||
            $position === 'FWD'
        ) {

            $hasRelevantOpponentRating =
                array_key_exists(
                    'next_opponent_defence_rating',
                    $row
                )
                &&
                $row[
                    'next_opponent_defence_rating'
                ] !== null
                &&
                is_numeric(
                    $row[
                        'next_opponent_defence_rating'
                    ]
                );
        }


        if (!$hasRelevantOpponentRating) {

            $missingRelevantOpponentRows++;
        }


        $hasCalibrationComponents =
            $validPosition
            &&
            $hasBaseFixture
            &&
            $hasRelevantOpponentRating;


        if ($hasCalibrationComponents) {

            $completeCalibrationRows++;

        } else {

            $missingCalibrationRows++;
        }


        if (
            !array_key_exists(
                'actual_points',
                $row
            )
            ||
            $row[
                'actual_points'
            ] === null
            ||
            !is_numeric(
                $row[
                    'actual_points'
                ]
            )
        ) {

            $missingActualRows++;
        }
    }


    echo "<br>";
    echo "============================================<br>";
    echo "POSITION-AWARE CALIBRATION EVIDENCE COMPLETENESS<br>";
    echo "============================================<br>";


    echo "Historical observations: "
        . count(
            $historicalRows
        )
        . "<br>";


    echo "Complete position-aware calibration rows: "
        . $completeCalibrationRows
        . "<br>";


    echo "Rows missing position-aware calibration evidence: "
        . $missingCalibrationRows
        . "<br>";


    echo "Rows with invalid/missing position: "
        . $missingPositionRows
        . "<br>";


    echo "Rows missing base next-fixture rating: "
        . $missingBaseFixtureRows
        . "<br>";


    echo "Rows missing position-relevant opponent rating: "
        . $missingRelevantOpponentRows
        . "<br>";


    echo "Rows missing realised points: "
        . $missingActualRows
        . "<br>";


    /*
     * These are consistency assertions only.
     *
     * We deliberately do NOT require any minimum number of
     * historical rows because the database may legitimately
     * contain zero Ready gameweeks at this stage of the season.
     */

    positionAwareCalibrationHistoryRealDataResult(
        $completeCalibrationRows
        +
        $missingCalibrationRows
        === count(
            $historicalRows
        ),
        'Position-aware evidence completeness accounts for every historical observation.'
    );


    positionAwareCalibrationHistoryRealDataResult(
        $missingActualRows >= 0
        &&
        $missingActualRows <= count(
            $historicalRows
        ),
        'Missing realised-point count is internally valid.'
    );


    /*
     * ========================================================
     * IMPORTANT NON-BRITTLE HISTORICAL RULES
     * ========================================================
     *
     * Do NOT assert:
     *
     * - a minimum number of Ready gameweeks
     * - a minimum number of historical observations
     * - a non-null correlation
     * - that 75 / 25 is best
     * - that any candidate beats another
     *
     * Those facts depend on genuine historical evidence
     * accumulated after immutable recommendation snapshots and
     * authoritative gameweek outcomes become available.
     */

} catch (
    Throwable $exception
) {

    $failed++;


    echo "<br>";
    echo "============================================<br>";
    echo "UNEXPECTED RUNTIME FAILURE<br>";
    echo "============================================<br>";


    echo htmlspecialchars(
        get_class(
            $exception
        )
        . ': '
        . $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    )
        . "<br>";
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Position-Aware Fixture Weight Calibration History Real Data Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}