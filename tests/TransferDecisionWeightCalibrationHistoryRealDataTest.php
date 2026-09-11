<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Decision Weight Calibration History Real Data Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function transferDecisionHistoryRealDataResult(
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
 * TRANSFER DECISION WEIGHT CANDIDATES
 * ============================================================
 *
 * Candidate dimensions:
 *
 * - Intelligence
 * - Fixture
 * - Value
 * - Strength
 * - Budget
 * - Confidence
 *
 * The sweep explicitly contains the current production weights:
 *
 * 40 / 20 / 15 / 10 / 10 / 5
 *
 * This is observational calibration only.
 *
 * It does NOT:
 *
 * - modify TransferDecision
 * - modify SquadTransferOptimizer
 * - change movement scales
 * - change classification thresholds
 * - change outgoing transfer-priority weights
 * - select a winning candidate
 * - reconstruct missing recommendation snapshots
 * - reconstruct missing outcomes
 * - query current player state to fill historical gaps
 * - require any historical gameweek to be Ready
 */

$weightCandidates = [

    [
        'intelligence_weight' =>
            1.00,

        'fixture_weight' =>
            0.00,

        'value_weight' =>
            0.00,

        'strength_weight' =>
            0.00,

        'budget_weight' =>
            0.00,

        'confidence_weight' =>
            0.00
    ],

    [
        'intelligence_weight' =>
            0.60,

        'fixture_weight' =>
            0.15,

        'value_weight' =>
            0.10,

        'strength_weight' =>
            0.05,

        'budget_weight' =>
            0.05,

        'confidence_weight' =>
            0.05
    ],

    [
        'intelligence_weight' =>
            0.50,

        'fixture_weight' =>
            0.20,

        'value_weight' =>
            0.10,

        'strength_weight' =>
            0.10,

        'budget_weight' =>
            0.05,

        'confidence_weight' =>
            0.05
    ],

    /*
     * Current production TransferDecision weights.
     */
    [
        'intelligence_weight' =>
            0.40,

        'fixture_weight' =>
            0.20,

        'value_weight' =>
            0.15,

        'strength_weight' =>
            0.10,

        'budget_weight' =>
            0.10,

        'confidence_weight' =>
            0.05
    ],

    [
        'intelligence_weight' =>
            0.30,

        'fixture_weight' =>
            0.30,

        'value_weight' =>
            0.15,

        'strength_weight' =>
            0.10,

        'budget_weight' =>
            0.10,

        'confidence_weight' =>
            0.05
    ],

    [
        'intelligence_weight' =>
            0.25,

        'fixture_weight' =>
            0.35,

        'value_weight' =>
            0.15,

        'strength_weight' =>
            0.10,

        'budget_weight' =>
            0.10,

        'confidence_weight' =>
            0.05
    ],

    [
        'intelligence_weight' =>
            0.20,

        'fixture_weight' =>
            0.20,

        'value_weight' =>
            0.25,

        'strength_weight' =>
            0.15,

        'budget_weight' =>
            0.10,

        'confidence_weight' =>
            0.10
    ],

    [
        'intelligence_weight' =>
            0.20,

        'fixture_weight' =>
            0.20,

        'value_weight' =>
            0.10,

        'strength_weight' =>
            0.25,

        'budget_weight' =>
            0.15,

        'confidence_weight' =>
            0.10
    ],

    [
        'intelligence_weight' =>
            0.15,

        'fixture_weight' =>
            0.15,

        'value_weight' =>
            0.15,

        'strength_weight' =>
            0.15,

        'budget_weight' =>
            0.30,

        'confidence_weight' =>
            0.10
    ],

    [
        'intelligence_weight' =>
            0.15,

        'fixture_weight' =>
            0.15,

        'value_weight' =>
            0.15,

        'strength_weight' =>
            0.15,

        'budget_weight' =>
            0.10,

        'confidence_weight' =>
            0.30
    ]
];


/*
 * ============================================================
 * CURRENT PRODUCTION CANDIDATE
 * ============================================================
 */

$currentProductionCandidatePresent =
    false;


foreach (
    $weightCandidates
    as $candidate
) {

    if (
        ($candidate['intelligence_weight'] ?? null) === 0.40
        &&
        ($candidate['fixture_weight'] ?? null) === 0.20
        &&
        ($candidate['value_weight'] ?? null) === 0.15
        &&
        ($candidate['strength_weight'] ?? null) === 0.10
        &&
        ($candidate['budget_weight'] ?? null) === 0.10
        &&
        ($candidate['confidence_weight'] ?? null) === 0.05
    ) {

        $currentProductionCandidatePresent =
            true;

        break;
    }
}


transferDecisionHistoryRealDataResult(
    $currentProductionCandidatePresent,
    'Candidate sweep includes current production TransferDecision weights 40 / 20 / 15 / 10 / 10 / 5.'
);


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


    transferDecisionHistoryRealDataResult(
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
     * Pure incoming TransferDecision weight replay.
     */
    $calibrationService =
        new TransferDecisionWeightCalibrationService();


    /*
     * Historical recommendation evidence orchestration.
     */
    $historyService =
        new TransferDecisionWeightCalibrationHistoryService(
            $gameweekRepository,
            $evidenceService,
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


    /*
     * ========================================================
     * RESULT CONTRACT
     * ========================================================
     */

    transferDecisionHistoryRealDataResult(
        is_array(
            $result
        ),
        'Historical TransferDecision calibration returns an evaluation result.'
    );


    transferDecisionHistoryRealDataResult(
        ($result['entry_id'] ?? null) === $entryId,
        'Historical TransferDecision calibration preserves the real FPL entry ID.'
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


    $historicalTransfers =
        is_array(
            $result[
                'historical_transfers'
            ]
            ?? null
        )
            ? $result[
                'historical_transfers'
            ]
            : [];


    transferDecisionHistoryRealDataResult(
        $totalGameweeks >= 0,
        'Stored gameweek count is available.'
    );


    transferDecisionHistoryRealDataResult(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $totalGameweeks,
        'Ready gameweek count is internally valid.'
    );


    transferDecisionHistoryRealDataResult(
        count(
            $gameweekAudit
        )
        ===
        $totalGameweeks,
        'Gameweek audit contains every valid stored gameweek considered.'
    );


    transferDecisionHistoryRealDataResult(
        count(
            $historicalTransfers
        )
        <=
        $readyGameweeks,
        'Transfer calibration history never exceeds authoritative Ready gameweek coverage.'
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

    echo "Transfer calibration gameweeks: "
        . count(
            $historicalTransfers
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
     * HISTORICAL TRANSFER UNIVERSE STRUCTURE
     * ========================================================
     */

    $structurallyValidHistoricalTransfers =
        0;


    foreach (
        $historicalTransfers
        as $historicalTransfer
    ) {

        if (!is_array($historicalTransfer)) {

            continue;
        }


        $outgoing =
            $historicalTransfer[
                'outgoing'
            ]
            ?? null;


        $replacements =
            $historicalTransfer[
                'replacements'
            ]
            ?? null;


        if (
            is_array($outgoing)
            &&
            isset(
                $outgoing[
                    'player_id'
                ]
            )
            &&
            is_numeric(
                $outgoing[
                    'player_id'
                ]
            )
            &&
            (int) $outgoing[
                'player_id'
            ] > 0
            &&
            is_array($replacements)
        ) {

            $structurallyValidHistoricalTransfers++;
        }
    }


    transferDecisionHistoryRealDataResult(
        $structurallyValidHistoricalTransfers
        ===
        count(
            $historicalTransfers
        ),
        'Every historical TransferDecision universe has a fixed outgoing player and replacement collection.'
    );


    /*
     * ========================================================
     * CALIBRATION RESULT
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


    transferDecisionHistoryRealDataResult(
        count(
            $evaluations
        )
        ===
        count(
            $weightCandidates
        ),
        'Every supplied TransferDecision weight candidate is evaluated.'
    );


    /*
     * Production candidate must be visible in the real evaluation,
     * even when there are currently no Ready historical transfers.
     */
    $productionEvaluationPresent =
        false;


    foreach (
        $evaluations
        as $evaluation
    ) {

        if (!is_array($evaluation)) {

            continue;
        }


        if (
            ($evaluation['intelligence_weight'] ?? null) === 0.40
            &&
            ($evaluation['fixture_weight'] ?? null) === 0.20
            &&
            ($evaluation['value_weight'] ?? null) === 0.15
            &&
            ($evaluation['strength_weight'] ?? null) === 0.10
            &&
            ($evaluation['budget_weight'] ?? null) === 0.10
            &&
            ($evaluation['confidence_weight'] ?? null) === 0.05
        ) {

            $productionEvaluationPresent =
                true;

            break;
        }
    }


    transferDecisionHistoryRealDataResult(
        $productionEvaluationPresent,
        'Real calibration output includes the current production TransferDecision candidate.'
    );


    /*
     * ========================================================
     * CALIBRATION OUTPUT
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "TRANSFER DECISION CALIBRATION CANDIDATES<br>";
    echo "============================================<br>";


    foreach (
        $evaluations
        as $evaluation
    ) {

        if (!is_array($evaluation)) {

            continue;
        }


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


        $comparableTransfers =
            (int) (
                $metrics[
                    'comparable_transfers'
                ]
                ?? 0
            );


        $totalLoss =
            $metrics[
                'total_selection_points_lost'
            ]
            ?? null;


        $meanLoss =
            $metrics[
                'mean_selection_points_lost'
            ]
            ?? null;


        $optimalSelections =
            (int) (
                $metrics[
                    'optimal_replacement_selections'
                ]
                ?? 0
            );


        echo "I "
            . number_format(
                (float) (
                    $evaluation[
                        'intelligence_weight'
                    ]
                    ?? 0
                )
                * 100,
                0
            )
            . "% | F "
            . number_format(
                (float) (
                    $evaluation[
                        'fixture_weight'
                    ]
                    ?? 0
                )
                * 100,
                0
            )
            . "% | V "
            . number_format(
                (float) (
                    $evaluation[
                        'value_weight'
                    ]
                    ?? 0
                )
                * 100,
                0
            )
            . "% | S "
            . number_format(
                (float) (
                    $evaluation[
                        'strength_weight'
                    ]
                    ?? 0
                )
                * 100,
                0
            )
            . "% | B "
            . number_format(
                (float) (
                    $evaluation[
                        'budget_weight'
                    ]
                    ?? 0
                )
                * 100,
                0
            )
            . "% | C "
            . number_format(
                (float) (
                    $evaluation[
                        'confidence_weight'
                    ]
                    ?? 0
                )
                * 100,
                0
            )
            . "% | Comparable: "
            . $comparableTransfers
            . " | Total Loss: "
            . (
                $totalLoss !== null
                    ? htmlspecialchars(
                        (string) $totalLoss,
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    : 'N/A'
            )
            . " | Mean Loss: "
            . (
                $meanLoss !== null
                    ? htmlspecialchars(
                        (string) $meanLoss,
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    : 'N/A'
            )
            . " | Optimal: "
            . $optimalSelections
            . "<br>";
    }


} catch (
    Throwable $throwable
) {

    transferDecisionHistoryRealDataResult(
        false,
        'Unexpected exception: '
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
echo "Transfer Decision Weight Calibration History Real Data Test Summary<br>";
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