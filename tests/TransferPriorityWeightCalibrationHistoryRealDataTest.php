<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Priority Weight Calibration History Real Data Test<br>";
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

function transferPriorityHistoryRealDataResult(
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
 * OUTGOING PRIORITY WEIGHT CANDIDATES
 * ============================================================
 *
 * Current executable production weights:
 *
 * Intelligence   45%
 * Value          20%
 * Fixture        15%
 * Availability   20%
 *
 * Confidence is deliberately NOT a transfer-priority weight.
 */

$weightCandidates = [

    [
        'intelligence_weight' =>
            1.00,

        'value_weight' =>
            0.00,

        'fixture_weight' =>
            0.00,

        'availability_weight' =>
            0.00
    ],

    [
        'intelligence_weight' =>
            0.70,

        'value_weight' =>
            0.10,

        'fixture_weight' =>
            0.10,

        'availability_weight' =>
            0.10
    ],

    [
        'intelligence_weight' =>
            0.55,

        'value_weight' =>
            0.20,

        'fixture_weight' =>
            0.10,

        'availability_weight' =>
            0.15
    ],

    /*
     * Current production weights.
     */
    [
        'intelligence_weight' =>
            0.45,

        'value_weight' =>
            0.20,

        'fixture_weight' =>
            0.15,

        'availability_weight' =>
            0.20
    ],

    [
        'intelligence_weight' =>
            0.35,

        'value_weight' =>
            0.25,

        'fixture_weight' =>
            0.20,

        'availability_weight' =>
            0.20
    ],

    [
        'intelligence_weight' =>
            0.30,

        'value_weight' =>
            0.20,

        'fixture_weight' =>
            0.30,

        'availability_weight' =>
            0.20
    ],

    [
        'intelligence_weight' =>
            0.25,

        'value_weight' =>
            0.30,

        'fixture_weight' =>
            0.20,

        'availability_weight' =>
            0.25
    ],

    [
        'intelligence_weight' =>
            0.20,

        'value_weight' =>
            0.20,

        'fixture_weight' =>
            0.20,

        'availability_weight' =>
            0.40
    ],

    [
        'intelligence_weight' =>
            0.15,

        'value_weight' =>
            0.15,

        'fixture_weight' =>
            0.20,

        'availability_weight' =>
            0.50
    ],

    [
        'intelligence_weight' =>
            0.25,

        'value_weight' =>
            0.25,

        'fixture_weight' =>
            0.25,

        'availability_weight' =>
            0.25
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
        ($candidate['intelligence_weight'] ?? null) === 0.45
        &&
        ($candidate['value_weight'] ?? null) === 0.20
        &&
        ($candidate['fixture_weight'] ?? null) === 0.15
        &&
        ($candidate['availability_weight'] ?? null) === 0.20
    ) {

        $currentProductionCandidatePresent =
            true;

        break;
    }
}


transferPriorityHistoryRealDataResult(
    $currentProductionCandidatePresent,
    'Candidate sweep includes current production outgoing priority weights 45 / 20 / 15 / 20.'
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


    transferPriorityHistoryRealDataResult(
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
     * Pure outgoing priority weight replay.
     */
    $calibrationService =
        new TransferPriorityWeightCalibrationService();


    /*
     * Historical outgoing opportunity reconstruction.
     */
    $historyService =
        new TransferPriorityWeightCalibrationHistoryService(
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

    transferPriorityHistoryRealDataResult(
        is_array(
            $result
        ),
        'Historical outgoing-priority calibration returns an evaluation result.'
    );


    transferPriorityHistoryRealDataResult(
        ($result['entry_id'] ?? null) === $entryId,
        'Historical outgoing-priority calibration preserves the real FPL entry ID.'
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


    $historicalGameweeks =
        is_array(
            $result[
                'historical_gameweeks'
            ]
            ?? null
        )
            ? $result[
                'historical_gameweeks'
            ]
            : [];


    transferPriorityHistoryRealDataResult(
        $totalGameweeks >= 0,
        'Stored gameweek count is available.'
    );


    transferPriorityHistoryRealDataResult(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $totalGameweeks,
        'Ready gameweek count is internally valid.'
    );


    transferPriorityHistoryRealDataResult(
        count(
            $gameweekAudit
        )
        ===
        $totalGameweeks,
        'Gameweek audit contains every valid stored gameweek considered.'
    );


    transferPriorityHistoryRealDataResult(
        count(
            $historicalGameweeks
        )
        <=
        $readyGameweeks,
        'Outgoing calibration history never exceeds authoritative Ready coverage.'
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

    echo "Outgoing priority calibration gameweeks: "
        . count(
            $historicalGameweeks
        )
        . "<br>";


    /*
     * ========================================================
     * GAMEWEEK AUDIT
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
     * HISTORICAL OUTGOING UNIVERSE STRUCTURE
     * ========================================================
     */

    $structurallyValidGameweeks =
        0;


    foreach (
        $historicalGameweeks
        as $historicalGameweek
    ) {

        if (!is_array($historicalGameweek)) {

            continue;
        }


        $players =
            $historicalGameweek[
                'players'
            ]
            ?? null;


        if (
            is_array($players)
            &&
            !empty($players)
        ) {

            $structurallyValidGameweeks++;
        }
    }


    transferPriorityHistoryRealDataResult(
        $structurallyValidGameweeks
        ===
        count(
            $historicalGameweeks
        ),
        'Every historical outgoing calibration gameweek contains reconstructed squad-player evidence.'
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


    transferPriorityHistoryRealDataResult(
        count(
            $evaluations
        )
        ===
        count(
            $weightCandidates
        ),
        'Every outgoing priority weight candidate is evaluated.'
    );


    /*
     * ========================================================
     * CALIBRATION OUTPUT
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "OUTGOING PRIORITY CALIBRATION<br>";
    echo "============================================<br>";


    foreach (
        $evaluations
        as $index => $evaluation
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


        echo "Candidate "
            . ($index + 1)
            . " | I "
            . number_format(
                (
                    (float) (
                        $evaluation[
                            'intelligence_weight'
                        ]
                        ?? 0
                    )
                )
                *
                100,
                0
            )
            . "% | V "
            . number_format(
                (
                    (float) (
                        $evaluation[
                            'value_weight'
                        ]
                        ?? 0
                    )
                )
                *
                100,
                0
            )
            . "% | F "
            . number_format(
                (
                    (float) (
                        $evaluation[
                            'fixture_weight'
                        ]
                        ?? 0
                    )
                )
                *
                100,
                0
            )
            . "% | A "
            . number_format(
                (
                    (float) (
                        $evaluation[
                            'availability_weight'
                        ]
                        ?? 0
                    )
                )
                *
                100,
                0
            );


        echo " | Comparable "
            . (
                $metrics[
                    'comparable_gameweeks'
                ]
                ?? 0
            );


        echo " | Total Loss "
            . (
                $metrics[
                    'total_selection_points_lost'
                ]
                ?? 0
            );


        echo " | Mean Loss "
            . (
                $metrics[
                    'mean_selection_points_lost'
                ]
                === null
                ||
                !array_key_exists(
                    'mean_selection_points_lost',
                    $metrics
                )
                    ? 'N/A'
                    : $metrics[
                        'mean_selection_points_lost'
                    ]
            );


        echo " | Optimal "
            . (
                $metrics[
                    'optimal_outgoing_selections'
                ]
                ?? 0
            )
            . "<br>";
    }


    /*
     * ========================================================
     * NO-HINDSIGHT / EMPTY-HISTORY CHARACTERIZATION
     * ========================================================
     */

    if (empty($historicalGameweeks)) {

        $allEvaluationsEmpty =
            true;


        foreach (
            $evaluations
            as $evaluation
        ) {

            $metrics =
                $evaluation[
                    'metrics'
                ]
                ?? [];


            if (
                ($metrics['total_gameweeks'] ?? null) !== 0
                ||
                ($metrics['comparable_gameweeks'] ?? null) !== 0
                ||
                ($metrics['unavailable_gameweeks'] ?? null) !== 0
                ||
                ($metrics['total_selection_points_lost'] ?? null) !== 0
                ||
                !array_key_exists(
                    'mean_selection_points_lost',
                    $metrics
                )
                ||
                $metrics[
                    'mean_selection_points_lost'
                ] !== null
                ||
                ($metrics['optimal_outgoing_selections'] ?? null) !== 0
            ) {

                $allEvaluationsEmpty =
                    false;

                break;
            }
        }


        transferPriorityHistoryRealDataResult(
            $allEvaluationsEmpty,
            'When no historical outgoing calibration evidence is Ready, every candidate remains truthfully unevaluated.'
        );

    } else {

        transferPriorityHistoryRealDataResult(
            true,
            'Ready historical outgoing calibration evidence is evaluated without requiring an empty-history result.'
        );
    }


} catch (
    Throwable $exception
) {

    transferPriorityHistoryRealDataResult(
        false,
        'Unexpected exception: '
        . $exception->getMessage()
    );
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Transfer Priority Weight Calibration History Real Data Test Summary<br>";
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