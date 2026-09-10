<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Effective Confidence Weight Calibration History Real Data Test<br>";
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

function effectiveConfidenceHistoryRealDataResult(
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
 * EFFECTIVE CONFIDENCE WEIGHT CANDIDATES
 * ============================================================
 *
 * This deliberately includes:
 *
 * - the Sample Confidence-only boundary
 * - a broad sweep through the weighting space
 * - the current production 40 / 60 blend
 * - the Participation Rate-only boundary
 *
 * This test observes historical evidence only.
 *
 * It does NOT:
 *
 * - choose a winning candidate
 * - apply a new production weight
 * - modify PlayerPerformance
 * - reconstruct missing recommendation evidence
 * - compare confidence against FPL points
 */

$weightCandidates = [

    [
        'sample_weight' => 1.00,
        'participation_weight' => 0.00
    ],

    [
        'sample_weight' => 0.80,
        'participation_weight' => 0.20
    ],

    [
        'sample_weight' => 0.60,
        'participation_weight' => 0.40
    ],

    [
        'sample_weight' => 0.50,
        'participation_weight' => 0.50
    ],

    /*
     * Current production model.
     */
    [
        'sample_weight' => 0.40,
        'participation_weight' => 0.60
    ],

    [
        'sample_weight' => 0.30,
        'participation_weight' => 0.70
    ],

    [
        'sample_weight' => 0.20,
        'participation_weight' => 0.80
    ],

    [
        'sample_weight' => 0.00,
        'participation_weight' => 1.00
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


    effectiveConfidenceHistoryRealDataResult(
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
     * Shared immutable recommendation-time evidence joined with
     * realised player outcomes.
     */
    $historicalEvidenceService =
        new PlayerCalibrationHistoricalEvidenceService(
            $snapshotRepository,
            $outcomeService
        );


    /*
     * Effective Confidence is evaluated against realised
     * participation rather than FPL points.
     */
    $metricsService =
        new EffectiveConfidenceBacktestMetricsService();


    $calibrationService =
        new EffectiveConfidenceWeightCalibrationService(
            $metricsService
        );


    /*
     * Clean four-dependency history orchestration.
     *
     * Unlike the older transitional position-aware history
     * service, no Strength / Fixture evaluator is involved.
     */
    $historyService =
        new EffectiveConfidenceWeightCalibrationHistoryService(
            $gameweekRepository,
            $evidenceService,
            $historicalEvidenceService,
            $calibrationService
        );


    /*
     * ========================================================
     * RUN REAL HISTORICAL EFFECTIVE CONFIDENCE CALIBRATION
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

    effectiveConfidenceHistoryRealDataResult(
        is_array(
            $result
        ),
        'Historical Effective Confidence calibration returns an evaluation result.'
    );


    effectiveConfidenceHistoryRealDataResult(
        (
            $result[
                'entry_id'
            ]
            ??
            null
        )
        ===
        $entryId,
        'Historical Effective Confidence calibration preserves the real FPL entry ID.'
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


    effectiveConfidenceHistoryRealDataResult(
        $totalGameweeks >= 0,
        'Stored gameweek count is available.'
    );


    effectiveConfidenceHistoryRealDataResult(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $totalGameweeks,
        'Ready gameweek count is internally valid.'
    );


    effectiveConfidenceHistoryRealDataResult(
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


    effectiveConfidenceHistoryRealDataResult(
        count(
            $gameweekResults
        )
        ===
        $totalGameweeks,
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
     * AGGREGATE EFFECTIVE CONFIDENCE CALIBRATION
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


    effectiveConfidenceHistoryRealDataResult(
        count(
            $evaluations
        )
        ===
        count(
            $weightCandidates
        ),
        'Every explicitly supplied Effective Confidence weight candidate is evaluated.'
    );


    echo "<br>";
    echo "============================================<br>";
    echo "AGGREGATE EFFECTIVE CONFIDENCE CALIBRATION<br>";
    echo "============================================<br>";


    foreach (
        $evaluations
        as $evaluation
    ) {

        if (!is_array($evaluation)) {

            continue;
        }


        $sampleWeight =
            $evaluation[
                'sample_weight'
            ]
            ??
            null;


        $participationWeight =
            $evaluation[
                'participation_weight'
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


        $meanAbsoluteError =
            $metrics[
                'mean_absolute_error'
            ]
            ??
            null;


        $meanError =
            $metrics[
                'mean_error'
            ]
            ??
            null;


        $correlation =
            $metrics[
                'correlation'
            ]
            ??
            null;


        echo "Sample Confidence: ";


        echo is_numeric(
            $sampleWeight
        )
            ? number_format(
                (float) $sampleWeight * 100,
                0
            )
                . "%"
            : 'N/A';


        echo " | Participation Rate: ";


        echo is_numeric(
            $participationWeight
        )
            ? number_format(
                (float) $participationWeight * 100,
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


        echo " | MAE: ";


        echo is_numeric(
            $meanAbsoluteError
        )
            ? number_format(
                (float) $meanAbsoluteError,
                6
            )
            : 'N/A';


        echo " | Mean Error: ";


        echo is_numeric(
            $meanError
        )
            ? number_format(
                (float) $meanError,
                6
            )
            : 'N/A';


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
         * Highlight the current production model for diagnostic
         * purposes only.
         *
         * It is not treated as preferred or as a winner.
         */
        if (
            is_numeric(
                $sampleWeight
            )
            &&
            is_numeric(
                $participationWeight
            )
            &&
            abs(
                (float) $sampleWeight
                -
                0.40
            )
            <
            0.000001
            &&
            abs(
                (float) $participationWeight
                -
                0.60
            )
            <
            0.000001
        ) {

            echo " | CURRENT PRODUCTION";
        }


        echo "<br>";
    }


    /*
     * ========================================================
     * EFFECTIVE CONFIDENCE CALIBRATION EVIDENCE COMPLETENESS
     * ========================================================
     *
     * Alternative Effective Confidence blends can be replayed
     * only when recommendation-time evidence contains:
     *
     * - Sample Confidence
     * - Participation Rate
     *
     * Realised comparison requires:
     *
     * - actual minutes
     * - positive realised fixture count
     *
     * Recommendation evidence and outcome evidence are tracked
     * separately so missing realised outcomes do not imply that
     * the historical confidence signal itself was unavailable.
     */

    $completeCalibrationRows =
        0;


    $missingCalibrationRows =
        0;


    $missingSampleConfidenceRows =
        0;


    $missingParticipationRateRows =
        0;


    $completeOutcomeRows =
        0;


    $missingOutcomeRows =
        0;


    $missingActualMinutesRows =
        0;


    $missingActualFixtureCountRows =
        0;


    foreach (
        $historicalRows
        as $row
    ) {

        if (!is_array($row)) {

            continue;
        }


        $hasSampleConfidence =
            array_key_exists(
                'sample_confidence',
                $row
            )
            &&
            $row[
                'sample_confidence'
            ]
            !== null
            &&
            is_numeric(
                $row[
                    'sample_confidence'
                ]
            );


        $hasParticipationRate =
            array_key_exists(
                'participation_rate',
                $row
            )
            &&
            $row[
                'participation_rate'
            ]
            !== null
            &&
            is_numeric(
                $row[
                    'participation_rate'
                ]
            );


        if (!$hasSampleConfidence) {

            $missingSampleConfidenceRows++;
        }


        if (!$hasParticipationRate) {

            $missingParticipationRateRows++;
        }


        if (
            $hasSampleConfidence
            &&
            $hasParticipationRate
        ) {

            $completeCalibrationRows++;

        } else {

            $missingCalibrationRows++;
        }


        $hasActualMinutes =
            array_key_exists(
                'actual_minutes',
                $row
            )
            &&
            $row[
                'actual_minutes'
            ]
            !== null
            &&
            is_numeric(
                $row[
                    'actual_minutes'
                ]
            );


        $hasActualFixtureCount =
            array_key_exists(
                'actual_fixture_count',
                $row
            )
            &&
            $row[
                'actual_fixture_count'
            ]
            !== null
            &&
            is_numeric(
                $row[
                    'actual_fixture_count'
                ]
            )
            &&
            (float) $row[
                'actual_fixture_count'
            ]
            >
            0.0;


        if (!$hasActualMinutes) {

            $missingActualMinutesRows++;
        }


        if (!$hasActualFixtureCount) {

            $missingActualFixtureCountRows++;
        }


        if (
            $hasActualMinutes
            &&
            $hasActualFixtureCount
        ) {

            $completeOutcomeRows++;

        } else {

            $missingOutcomeRows++;
        }
    }


    effectiveConfidenceHistoryRealDataResult(
        $completeCalibrationRows
        +
        $missingCalibrationRows
        ===
        count(
            $historicalRows
        ),
        'Effective Confidence calibration evidence completeness is internally consistent.'
    );


    effectiveConfidenceHistoryRealDataResult(
        $completeOutcomeRows
        +
        $missingOutcomeRows
        ===
        count(
            $historicalRows
        ),
        'Realised participation evidence completeness is internally consistent.'
    );


    echo "<br>";
    echo "============================================<br>";
    echo "EFFECTIVE CONFIDENCE CALIBRATION EVIDENCE COMPLETENESS<br>";
    echo "============================================<br>";


    echo "Historical observations: "
        . count(
            $historicalRows
        )
        . "<br>";


    echo "Complete calibration rows: "
        . $completeCalibrationRows
        . "<br>";


    echo "Missing calibration rows: "
        . $missingCalibrationRows
        . "<br>";


    echo "Missing Sample Confidence: "
        . $missingSampleConfidenceRows
        . "<br>";


    echo "Missing Participation Rate: "
        . $missingParticipationRateRows
        . "<br>";


    echo "Complete realised participation rows: "
        . $completeOutcomeRows
        . "<br>";


    echo "Missing realised participation rows: "
        . $missingOutcomeRows
        . "<br>";


    echo "Missing Actual Minutes: "
        . $missingActualMinutesRows
        . "<br>";


    echo "Missing Actual Fixture Count: "
        . $missingActualFixtureCountRows
        . "<br>";


} catch (
    Throwable $throwable
) {

    effectiveConfidenceHistoryRealDataResult(
        false,
        'Real historical Effective Confidence calibration completed without exception: '
        . $throwable->getMessage()
    );
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Effective Confidence Weight Calibration History Real Data Test Summary<br>";
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