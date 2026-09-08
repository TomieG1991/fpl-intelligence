<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Intelligence Weight Calibration History Real Data Test<br>";
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

function calibrationHistoryRealDataResult(
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
 * WEIGHT CANDIDATES
 * ============================================================
 *
 * This deliberately includes:
 *
 * - extreme Strength-only / Fixture-only boundaries
 * - a coarse sweep through the space
 * - the current production 65 / 35 model
 *
 * This test observes the evidence.
 *
 * It does NOT select or apply a winning model.
 */

$weightCandidates = [

    [
        'strength_weight' => 0.00,
        'fixture_weight' => 1.00
    ],

    [
        'strength_weight' => 0.10,
        'fixture_weight' => 0.90
    ],

    [
        'strength_weight' => 0.20,
        'fixture_weight' => 0.80
    ],

    [
        'strength_weight' => 0.30,
        'fixture_weight' => 0.70
    ],

    [
        'strength_weight' => 0.40,
        'fixture_weight' => 0.60
    ],

    [
        'strength_weight' => 0.50,
        'fixture_weight' => 0.50
    ],

    [
        'strength_weight' => 0.60,
        'fixture_weight' => 0.40
    ],

    /*
     * Current production model.
     */
    [
        'strength_weight' => 0.65,
        'fixture_weight' => 0.35
    ],

    [
        'strength_weight' => 0.70,
        'fixture_weight' => 0.30
    ],

    [
        'strength_weight' => 0.80,
        'fixture_weight' => 0.20
    ],

    [
        'strength_weight' => 0.90,
        'fixture_weight' => 0.10
    ],

    [
        'strength_weight' => 1.00,
        'fixture_weight' => 0.00
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


    calibrationHistoryRealDataResult(
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


    $evidenceService =
        new GameweekBacktestingEvidenceService(
            $gameweekRepository,
            $availability,
            $snapshotRepository,
            $outcomeService
        );


    $metricsService =
        new IntelligenceScoreBacktestMetricsService();


    $calibrationService =
        new PlayerIntelligenceWeightCalibrationService(
            $metricsService
        );


    $evaluationService =
        new PlayerIntelligenceWeightCalibrationEvaluationService(
            $snapshotRepository,
            $outcomeService,
            $calibrationService
        );


    $historyService =
        new PlayerIntelligenceWeightCalibrationHistoryService(
            $gameweekRepository,
            $evidenceService,
            $evaluationService,
            $calibrationService
        );


    /*
     * ========================================================
     * RUN REAL HISTORICAL CALIBRATION
     * ========================================================
     */

    $result =
        $historyService->evaluate(
            $entryId,
            $weightCandidates
        );


    calibrationHistoryRealDataResult(
        is_array(
            $result
        ),
        'Historical calibration returns an evaluation result.'
    );


    calibrationHistoryRealDataResult(
        (
            $result[
                'entry_id'
            ]
            ??
            null
        )
        ===
        $entryId,
        'Historical calibration preserves the real FPL entry ID.'
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
            ??
            0
        );


    $readyGameweeks =
        (int) (
            $result[
                'ready_gameweeks'
            ]
            ??
            0
        );


    $historicalRows =
        is_array(
            $result[
                'historical_rows'
            ]
            ??
            null
        )
            ? $result[
                'historical_rows'
            ]
            : [];


    calibrationHistoryRealDataResult(
        $totalGameweeks >= 0,
        'Stored gameweek count is available.'
    );


    calibrationHistoryRealDataResult(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $totalGameweeks,
        'Ready gameweek count is internally valid.'
    );


    calibrationHistoryRealDataResult(
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
            ??
            null
        )
            ? $result[
                'gameweeks'
            ]
            : [];


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
            ??
            'N/A';


        $name =
            $gameweekResult[
                'name'
            ]
            ??
            'Unknown';


        $status =
            $gameweekResult[
                'status'
            ]
            ??
            'Unknown';


        $reason =
            $gameweekResult[
                'reason'
            ]
            ??
            null;


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
     * CALIBRATION OUTPUT
     * ========================================================
     */

    $calibration =
        is_array(
            $result[
                'calibration'
            ]
            ??
            null
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
            ??
            null
        )
            ? $calibration[
                'evaluations'
            ]
            : [];


    calibrationHistoryRealDataResult(
        count(
            $evaluations
        )
        ===
        count(
            $weightCandidates
        ),
        'Every explicitly supplied weight candidate is evaluated.'
    );


    echo "<br>";
    echo "============================================<br>";
    echo "AGGREGATE WEIGHT CALIBRATION<br>";
    echo "============================================<br>";


    foreach (
        $evaluations
        as $evaluation
    ) {

        if (!is_array($evaluation)) {

            continue;
        }


        $strengthWeight =
            $evaluation[
                'strength_weight'
            ]
            ??
            null;


        $fixtureWeight =
            $evaluation[
                'fixture_weight'
            ]
            ??
            null;


        $metrics =
            is_array(
                $evaluation[
                    'metrics'
                ]
                ??
                null
            )
                ? $evaluation[
                    'metrics'
                ]
                : [];


        $totalPlayers =
            $metrics[
                'total_players'
            ]
            ??
            0;


        $comparablePlayers =
            $metrics[
                'comparable_players'
            ]
            ??
            0;


        $unavailablePlayers =
            $metrics[
                'unavailable_players'
            ]
            ??
            0;


        $correlation =
            $metrics[
                'correlation'
            ]
            ??
            null;


        echo "Strength: ";


        echo is_numeric(
            $strengthWeight
        )
            ? number_format(
                (float) $strengthWeight * 100,
                0
            )
                . "%"
            : 'N/A';


        echo " | Fixture: ";


        echo is_numeric(
            $fixtureWeight
        )
            ? number_format(
                (float) $fixtureWeight * 100,
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
                $strengthWeight
            )
            &&
            is_numeric(
                $fixtureWeight
            )
            &&
            abs(
                (float) $strengthWeight
                -
                0.65
            )
            < 0.000001
            &&
            abs(
                (float) $fixtureWeight
                -
                0.35
            )
            < 0.000001
        ) {

            echo " | CURRENT PRODUCTION";
        }


        echo "<br>";
    }


    /*
     * ========================================================
     * CALIBRATION EVIDENCE COMPLETENESS
     * ========================================================
     */

    $completeCalibrationRows =
        0;


    $missingCalibrationRows =
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


        $hasCalibrationComponents =
            array_key_exists(
                'strength_rating',
                $row
            )
            &&
            $row[
                'strength_rating'
            ] !== null
            &&
            is_numeric(
                $row[
                    'strength_rating'
                ]
            )
            &&
            array_key_exists(
                'fixture_rating',
                $row
            )
            &&
            $row[
                'fixture_rating'
            ] !== null
            &&
            is_numeric(
                $row[
                    'fixture_rating'
                ]
            )
            &&
            array_key_exists(
                'availability_multiplier',
                $row
            )
            &&
            $row[
                'availability_multiplier'
            ] !== null
            &&
            is_numeric(
                $row[
                    'availability_multiplier'
                ]
            );


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
    echo "CALIBRATION EVIDENCE COMPLETENESS<br>";
    echo "============================================<br>";

    echo "Complete Strength/Fixture/Availability rows: "
        . $completeCalibrationRows
        . "<br>";

    echo "Rows missing calibration components: "
        . $missingCalibrationRows
        . "<br>";

    echo "Rows missing realised points: "
        . $missingActualRows
        . "<br>";


    /*
     * ========================================================
     * IMPORTANT
     * ========================================================
     *
     * We deliberately make no assertion that:
     *
     * - a minimum number of Ready gameweeks exists
     * - a minimum number of player rows exists
     * - correlation must be non-null
     * - 65/35 must win
     * - any alternative must beat production
     *
     * Those are findings from the historical evidence,
     * not requirements of the software contract.
     */

} catch (
    Throwable $exception
) {

    calibrationHistoryRealDataResult(
        false,
        'Real-data calibration completed without exception.'
    );


    echo "<br>";
    echo "Exception: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "File: "
        . htmlspecialchars(
            $exception->getFile(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Line: "
        . $exception->getLine()
        . "<br>";
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Intelligence Weight Calibration History Real Data Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "<strong>RESULT: ALL TESTS PASSED ✅</strong><br>";

} else {

    echo "<strong>RESULT: TEST FAILURES DETECTED ❌</strong><br>";
}