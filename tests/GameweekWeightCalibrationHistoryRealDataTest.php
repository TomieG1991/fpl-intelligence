<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Weight Calibration History Real Data Test<br>";
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

function gameweekWeightHistoryRealDataResult(
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
 * GAMEWEEK WEIGHT CANDIDATES
 * ============================================================
 *
 * This sweep deliberately includes:
 *
 * - Intelligence-only boundary
 * - Strength-only boundary
 * - Fixture-only boundary
 * - several mixed alternatives
 * - current production 45 / 25 / 30
 *
 * This is observational calibration only.
 *
 * It does NOT:
 *
 * - select a winning weight combination
 * - modify GameweekStartingXI
 * - change production weights
 * - recalibrate fixture compression
 * - recalibrate confidence modifiers
 * - recalibrate availability modifiers
 * - reconstruct missing recommendation evidence
 * - reconstruct historical squads
 * - reconstruct missing outcomes
 * - require any historical gameweek to be Ready
 */

$weightCandidates = [

    [
        'intelligence_weight' =>
            1.00,

        'strength_weight' =>
            0.00,

        'fixture_weight' =>
            0.00
    ],

    [
        'intelligence_weight' =>
            0.80,

        'strength_weight' =>
            0.10,

        'fixture_weight' =>
            0.10
    ],

    [
        'intelligence_weight' =>
            0.60,

        'strength_weight' =>
            0.20,

        'fixture_weight' =>
            0.20
    ],

    [
        'intelligence_weight' =>
            0.50,

        'strength_weight' =>
            0.25,

        'fixture_weight' =>
            0.25
    ],

    /*
     * Current production Gameweek Starting XI core:
     *
     * Intelligence 45%
     * Strength     25%
     * Fixture      30%
     */
    [
        'intelligence_weight' =>
            0.45,

        'strength_weight' =>
            0.25,

        'fixture_weight' =>
            0.30
    ],

    [
        'intelligence_weight' =>
            0.40,

        'strength_weight' =>
            0.30,

        'fixture_weight' =>
            0.30
    ],

    [
        'intelligence_weight' =>
            0.30,

        'strength_weight' =>
            0.30,

        'fixture_weight' =>
            0.40
    ],

    [
        'intelligence_weight' =>
            0.20,

        'strength_weight' =>
            0.40,

        'fixture_weight' =>
            0.40
    ],

    [
        'intelligence_weight' =>
            0.00,

        'strength_weight' =>
            1.00,

        'fixture_weight' =>
            0.00
    ],

    [
        'intelligence_weight' =>
            0.00,

        'strength_weight' =>
            0.00,

        'fixture_weight' =>
            1.00
    ]
];


/*
 * ============================================================
 * VERIFY CURRENT PRODUCTION CANDIDATE IS PRESENT
 * ============================================================
 */

$currentProductionCandidatePresent =
    false;


foreach (
    $weightCandidates
    as $candidate
) {

    if (
        (
            $candidate[
                'intelligence_weight'
            ]
            ??
            null
        )
        ===
        0.45
        &&
        (
            $candidate[
                'strength_weight'
            ]
            ??
            null
        )
        ===
        0.25
        &&
        (
            $candidate[
                'fixture_weight'
            ]
            ??
            null
        )
        ===
        0.30
    ) {

        $currentProductionCandidatePresent =
            true;

        break;
    }
}


gameweekWeightHistoryRealDataResult(
    $currentProductionCandidatePresent,
    'Candidate sweep includes current production Gameweek weights 45 / 25 / 30.'
);


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


    gameweekWeightHistoryRealDataResult(
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
     *
     * This determines whether each stored gameweek is:
     *
     * - Ready
     * - Incomplete
     * - Unavailable
     *
     * Gameweek calibration must not override this lifecycle.
     */
    $evidenceService =
        new GameweekBacktestingEvidenceService(
            $gameweekRepository,
            $availability,
            $snapshotRepository,
            $outcomeService
        );


    /*
     * Pure analytical Gameweek top-level weight replay.
     */
    $calibrationService =
        new GameweekWeightCalibrationService();


    /*
     * Multi-gameweek Gameweek calibration orchestration.
     */
    $historyService =
        new GameweekWeightCalibrationHistoryService(
            $gameweekRepository,
            $evidenceService,
            $calibrationService
        );


    /*
     * ========================================================
     * RUN REAL HISTORICAL GAMEWEEK CALIBRATION
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

    gameweekWeightHistoryRealDataResult(
        is_array(
            $result
        ),
        'Historical Gameweek calibration returns an evaluation result.'
    );


    gameweekWeightHistoryRealDataResult(
        (
            $result[
                'entry_id'
            ]
            ??
            null
        )
        ===
        $entryId,
        'Historical Gameweek calibration preserves the real FPL entry ID.'
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


    $gameweekAudit =
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


    $historicalGameweeks =
        is_array(
            $result[
                'historical_gameweeks'
            ]
            ??
            null
        )
            ? $result[
                'historical_gameweeks'
            ]
            : [];


    gameweekWeightHistoryRealDataResult(
        $totalGameweeks >= 0,
        'Stored gameweek count is available.'
    );


    gameweekWeightHistoryRealDataResult(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $totalGameweeks,
        'Ready gameweek count is internally valid.'
    );


    gameweekWeightHistoryRealDataResult(
        count(
            $gameweekAudit
        )
        ===
        $totalGameweeks,
        'Gameweek audit contains every valid stored gameweek considered.'
    );


    gameweekWeightHistoryRealDataResult(
        count(
            $historicalGameweeks
        )
        <=
        $readyGameweeks,
        'Gameweek calibration history never exceeds authoritative Ready gameweek coverage.'
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


    echo "Gameweek calibration gameweeks: "
        . count(
            $historicalGameweeks
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

        if (
            !is_array(
                $gameweekResult
            )
        ) {

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
     * HISTORICAL SQUAD EVIDENCE
     * ========================================================
     *
     * A complete Gameweek calibration universe should represent
     * the preserved recommendation-time 15-player squad.
     *
     * Each player requires:
     *
     * - local player identity
     * - position
     * - Intelligence component
     * - Strength component
     * - already-compressed Fixture component
     * - Confidence Modifier
     * - Availability Modifier
     * - realised FPL points for comparison
     *
     * We do not require historicalGameweeks to be non-empty.
     */

    $totalHistoricalPlayers =
        0;


    $completeComponentPlayers =
        0;


    $missingComponentPlayers =
        0;


    $missingActualPlayers =
        0;


    $completeFifteenPlayerGameweeks =
        0;


    foreach (
        $historicalGameweeks
        as $historicalGameweek
    ) {

        if (
            !is_array(
                $historicalGameweek
            )
        ) {

            continue;
        }


        $players =
            is_array(
                $historicalGameweek[
                    'players'
                ]
                ??
                null
            )
                ? $historicalGameweek[
                    'players'
                ]
                : [];


        if (
            count(
                $players
            )
            ===
            15
        ) {

            $completeFifteenPlayerGameweeks++;
        }


        foreach (
            $players
            as $player
        ) {

            if (
                !is_array(
                    $player
                )
            ) {

                continue;
            }


            $totalHistoricalPlayers++;


            $components =
                is_array(
                    $player[
                        'components'
                    ]
                    ??
                    null
                )
                    ? $player[
                        'components'
                    ]
                    : [];


            $hasCompleteComponents =
                isset(
                    $components[
                        'intelligence'
                    ]
                )
                &&
                is_numeric(
                    $components[
                        'intelligence'
                    ]
                )
                &&
                isset(
                    $components[
                        'strength'
                    ]
                )
                &&
                is_numeric(
                    $components[
                        'strength'
                    ]
                )
                &&
                isset(
                    $components[
                        'fixture'
                    ]
                )
                &&
                is_numeric(
                    $components[
                        'fixture'
                    ]
                )
                &&
                isset(
                    $components[
                        'confidence_modifier'
                    ]
                )
                &&
                is_numeric(
                    $components[
                        'confidence_modifier'
                    ]
                )
                &&
                isset(
                    $components[
                        'availability_modifier'
                    ]
                )
                &&
                is_numeric(
                    $components[
                        'availability_modifier'
                    ]
                );


            if (
                $hasCompleteComponents
            ) {

                $completeComponentPlayers++;

            } else {

                $missingComponentPlayers++;
            }


            if (
                !array_key_exists(
                    'actual_points',
                    $player
                )
                ||
                $player[
                    'actual_points'
                ]
                ===
                null
                ||
                !is_numeric(
                    $player[
                        'actual_points'
                    ]
                )
            ) {

                $missingActualPlayers++;
            }
        }
    }


    gameweekWeightHistoryRealDataResult(
        $completeComponentPlayers
        +
        $missingComponentPlayers
        ===
        $totalHistoricalPlayers,
        'Gameweek component evidence accounting is internally consistent.'
    );


    gameweekWeightHistoryRealDataResult(
        $missingActualPlayers >= 0
        &&
        $missingActualPlayers <= $totalHistoricalPlayers,
        'Gameweek realised-outcome evidence accounting is internally consistent.'
    );


    gameweekWeightHistoryRealDataResult(
        $completeFifteenPlayerGameweeks
        <=
        count(
            $historicalGameweeks
        ),
        'Complete 15-player squad accounting is internally valid.'
    );


    echo "<br>";
    echo "============================================<br>";
    echo "GAMEWEEK CALIBRATION EVIDENCE COMPLETENESS<br>";
    echo "============================================<br>";


    echo "Historical Gameweek player rows: "
        . $totalHistoricalPlayers
        . "<br>";


    echo "Complete component rows: "
        . $completeComponentPlayers
        . "<br>";


    echo "Missing component rows: "
        . $missingComponentPlayers
        . "<br>";


    echo "Missing realised outcome rows: "
        . $missingActualPlayers
        . "<br>";


    echo "Complete 15-player calibration gameweeks: "
        . $completeFifteenPlayerGameweeks
        . "<br>";


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


    gameweekWeightHistoryRealDataResult(
        count(
            $evaluations
        )
        ===
        count(
            $weightCandidates
        ),
        'Pure Gameweek calibrator evaluates every supplied weight candidate.'
    );


    /*
     * Verify that the production candidate survives unchanged
     * into the calibration result.
     */
    $productionEvaluationFound =
        false;


    foreach (
        $evaluations
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
            (
                $evaluation[
                    'intelligence_weight'
                ]
                ??
                null
            )
            ===
            0.45
            &&
            (
                $evaluation[
                    'strength_weight'
                ]
                ??
                null
            )
            ===
            0.25
            &&
            (
                $evaluation[
                    'fixture_weight'
                ]
                ??
                null
            )
            ===
            0.30
        ) {

            $productionEvaluationFound =
                true;

            break;
        }
    }


    gameweekWeightHistoryRealDataResult(
        $productionEvaluationFound,
        'Calibration result includes current production Gameweek weights 45 / 25 / 30.'
    );


    /*
     * ========================================================
     * CALIBRATION OUTPUT
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "GAMEWEEK WEIGHT CALIBRATION<br>";
    echo "============================================<br>";


    foreach (
        $evaluations
        as $evaluation
    ) {

        if (
            !is_array(
                $evaluation
            )
        ) {

            continue;
        }


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


        $intelligenceWeight =
            $evaluation[
                'intelligence_weight'
            ]
            ??
            'N/A';


        $strengthWeight =
            $evaluation[
                'strength_weight'
            ]
            ??
            'N/A';


        $fixtureWeight =
            $evaluation[
                'fixture_weight'
            ]
            ??
            'N/A';


        $fixtureWeight =
            $candidate[
                'fixture_weight'
            ]
            ??
            'N/A';


        $comparableGameweeks =
            $metrics[
                'comparable_gameweeks'
            ]
            ??
            0;


        $totalSelectionPointsLost =
            $metrics[
                'total_selection_points_lost'
            ]
            ??
            null;


        $meanSelectionPointsLost =
            $metrics[
                'mean_selection_points_lost'
            ]
            ??
            null;


        $optimalXISelections =
            $metrics[
                'optimal_xi_selections'
            ]
            ??
            0;


        echo "Intelligence "
            . htmlspecialchars(
                (string) $intelligenceWeight,
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Strength "
            . htmlspecialchars(
                (string) $strengthWeight,
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Fixture "
            . htmlspecialchars(
                (string) $fixtureWeight,
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Comparable GWs: "
            . htmlspecialchars(
                (string) $comparableGameweeks,
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Total Selection Loss: "
            . htmlspecialchars(
                $totalSelectionPointsLost === null
                    ? 'N/A'
                    : (string) $totalSelectionPointsLost,
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Mean Selection Loss: "
            . htmlspecialchars(
                $meanSelectionPointsLost === null
                    ? 'N/A'
                    : (string) $meanSelectionPointsLost,
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Optimal XI: "
            . htmlspecialchars(
                (string) $optimalXISelections,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }


    /*
     * ========================================================
     * NO FALSE HISTORICAL CLAIMS
     * ========================================================
     *
     * Zero Ready gameweeks is a valid result.
     *
     * In that situation there must be no fabricated historical
     * calibration universe.
     */

    if (
        $readyGameweeks === 0
    ) {

        gameweekWeightHistoryRealDataResult(
            $historicalGameweeks === [],
            'Zero Ready gameweeks produces no reconstructed Gameweek calibration history.'
        );
    }


} catch (
    Throwable $exception
) {

    $failed++;


    echo "FAIL: Unexpected exception: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Gameweek Weight Calibration History Real Data Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}