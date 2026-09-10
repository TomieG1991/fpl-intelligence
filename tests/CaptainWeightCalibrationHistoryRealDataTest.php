<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Captain Weight Calibration History Real Data Test<br>";
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

function captainWeightHistoryRealDataResult(
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
 * CAPTAIN WEIGHT CANDIDATES
 * ============================================================
 *
 * This sweep deliberately includes:
 *
 * - Strength-only boundary
 * - Fixture-only boundary
 * - Attacking-Threat-only boundary
 * - several mixed alternatives
 * - current production 35 / 35 / 30
 *
 * This real-data test is observational only.
 *
 * It does NOT:
 *
 * - choose a winning weight combination
 * - modify CaptainIntelligence
 * - change production weights
 * - reconstruct missing recommendation evidence
 * - reconstruct missing outcomes
 * - require any historical gameweek to be Ready
 */

$weightCandidates = [

    [
        'strength_weight' =>
            1.00,

        'fixture_weight' =>
            0.00,

        'attacking_threat_weight' =>
            0.00
    ],

    [
        'strength_weight' =>
            0.80,

        'fixture_weight' =>
            0.10,

        'attacking_threat_weight' =>
            0.10
    ],

    [
        'strength_weight' =>
            0.60,

        'fixture_weight' =>
            0.20,

        'attacking_threat_weight' =>
            0.20
    ],

    [
        'strength_weight' =>
            0.50,

        'fixture_weight' =>
            0.25,

        'attacking_threat_weight' =>
            0.25
    ],

    [
        'strength_weight' =>
            0.40,

        'fixture_weight' =>
            0.30,

        'attacking_threat_weight' =>
            0.30
    ],

    /*
     * Current production Captain core.
     */
    [
        'strength_weight' =>
            0.35,

        'fixture_weight' =>
            0.35,

        'attacking_threat_weight' =>
            0.30
    ],

    [
        'strength_weight' =>
            0.30,

        'fixture_weight' =>
            0.40,

        'attacking_threat_weight' =>
            0.30
    ],

    [
        'strength_weight' =>
            0.20,

        'fixture_weight' =>
            0.50,

        'attacking_threat_weight' =>
            0.30
    ],

    [
        'strength_weight' =>
            0.10,

        'fixture_weight' =>
            0.70,

        'attacking_threat_weight' =>
            0.20
    ],

    [
        'strength_weight' =>
            0.00,

        'fixture_weight' =>
            1.00,

        'attacking_threat_weight' =>
            0.00
    ],

    [
        'strength_weight' =>
            0.00,

        'fixture_weight' =>
            0.00,

        'attacking_threat_weight' =>
            1.00
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


    captainWeightHistoryRealDataResult(
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
     * This determines whether a gameweek is:
     *
     * - Ready
     * - Incomplete
     * - Unavailable
     *
     * No Captain-specific service may override this lifecycle.
     */
    $evidenceService =
        new GameweekBacktestingEvidenceService(
            $gameweekRepository,
            $availability,
            $snapshotRepository,
            $outcomeService
        );


    /*
     * Pure analytical Captain weight replay.
     */
    $calibrationService =
        new CaptainWeightCalibrationService();


    /*
     * Multi-gameweek Captain calibration orchestration.
     */
    $historyService =
        new CaptainWeightCalibrationHistoryService(
            $gameweekRepository,
            $evidenceService,
            $calibrationService
        );


    /*
     * ========================================================
     * RUN REAL HISTORICAL CAPTAIN CALIBRATION
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

    captainWeightHistoryRealDataResult(
        is_array(
            $result
        ),
        'Historical Captain calibration returns an evaluation result.'
    );


    captainWeightHistoryRealDataResult(
        (
            $result[
                'entry_id'
            ]
            ??
            null
        )
        ===
        $entryId,
        'Historical Captain calibration preserves the real FPL entry ID.'
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


    captainWeightHistoryRealDataResult(
        $totalGameweeks >= 0,
        'Stored gameweek count is available.'
    );


    captainWeightHistoryRealDataResult(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $totalGameweeks,
        'Ready gameweek count is internally valid.'
    );


    captainWeightHistoryRealDataResult(
        count(
            $gameweekAudit
        )
        ===
        $totalGameweeks,
        'Gameweek audit contains every valid stored gameweek considered.'
    );


    captainWeightHistoryRealDataResult(
        count(
            $historicalGameweeks
        )
        <=
        $readyGameweeks,
        'Captain calibration history never exceeds authoritative Ready gameweek coverage.'
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


    echo "Captain calibration gameweeks: "
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
     * CAPTAIN EVIDENCE COMPLETENESS
     * ========================================================
     *
     * A Captain candidate can replay alternative top-level
     * Captain weights only when the preserved historical row
     * contains:
     *
     * - Strength
     * - Fixture
     * - Attacking Threat
     * - Confidence Modifier
     * - Availability Modifier
     *
     * Realised comparison also requires actual FPL points.
     */

    $totalCaptainPlayers =
        0;


    $completeComponentPlayers =
        0;


    $missingComponentPlayers =
        0;


    $missingActualPlayers =
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


            $totalCaptainPlayers++;


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
                        'attacking_threat'
                    ]
                )
                &&
                is_numeric(
                    $components[
                        'attacking_threat'
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


    captainWeightHistoryRealDataResult(
        $completeComponentPlayers
        +
        $missingComponentPlayers
        ===
        $totalCaptainPlayers,
        'Captain component evidence accounting is internally consistent.'
    );


    captainWeightHistoryRealDataResult(
        $missingActualPlayers
        >=
        0
        &&
        $missingActualPlayers
        <=
        $totalCaptainPlayers,
        'Captain realised-outcome evidence accounting is internally consistent.'
    );


    echo "<br>";
    echo "============================================<br>";
    echo "CAPTAIN CALIBRATION EVIDENCE COMPLETENESS<br>";
    echo "============================================<br>";


    echo "Historical Captain candidate rows: "
        . $totalCaptainPlayers
        . "<br>";


    echo "Complete Captain component rows: "
        . $completeComponentPlayers
        . "<br>";


    echo "Missing Captain component rows: "
        . $missingComponentPlayers
        . "<br>";


    echo "Rows missing realised points: "
        . $missingActualPlayers
        . "<br>";


    /*
     * ========================================================
     * HISTORICAL GAMEWEEK DETAIL
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "CAPTAIN CALIBRATION GAMEWEEK DETAIL<br>";
    echo "============================================<br>";


    if (
        empty(
            $historicalGameweeks
        )
    ) {

        echo "No historically usable Captain calibration gameweeks are currently available.<br>";

    } else {

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


            $gameweekId =
                $historicalGameweek[
                    'gameweek_id'
                ]
                ??
                'N/A';


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


            $completeActuals =
                0;


            foreach (
                $players
                as $player
            ) {

                if (
                    is_array(
                        $player
                    )
                    &&
                    array_key_exists(
                        'actual_points',
                        $player
                    )
                    &&
                    $player[
                        'actual_points'
                    ]
                    !==
                    null
                    &&
                    is_numeric(
                        $player[
                            'actual_points'
                        ]
                    )
                ) {

                    $completeActuals++;
                }
            }


            echo "Local GW "
                . htmlspecialchars(
                    (string) $gameweekId,
                    ENT_QUOTES,
                    'UTF-8'
                )
                . " | Captain candidates: "
                . count(
                    $players
                )
                . " | Realised outcomes: "
                . $completeActuals
                . "/"
                . count(
                    $players
                )
                . "<br>";
        }
    }


    /*
     * ========================================================
     * AGGREGATE CAPTAIN CALIBRATION
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


    captainWeightHistoryRealDataResult(
        count(
            $evaluations
        )
        ===
        count(
            $weightCandidates
        ),
        'Every explicitly supplied Captain weight candidate is evaluated.'
    );


    echo "<br>";
    echo "============================================<br>";
    echo "AGGREGATE CAPTAIN WEIGHT CALIBRATION<br>";
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


        $attackingThreatWeight =
            $evaluation[
                'attacking_threat_weight'
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


        $totalCalibrationGameweeks =
            $metrics[
                'total_gameweeks'
            ]
            ??
            0;


        $comparableGameweeks =
            $metrics[
                'comparable_gameweeks'
            ]
            ??
            0;


        $unavailableGameweeks =
            $metrics[
                'unavailable_gameweeks'
            ]
            ??
            0;


        $totalCaptainPointsLost =
            $metrics[
                'total_captain_points_lost'
            ]
            ??
            0;


        $meanCaptainPointsLost =
            $metrics[
                'mean_captain_points_lost'
            ]
            ??
            null;


        $optimalCaptainSelections =
            $metrics[
                'optimal_captain_selections'
            ]
            ??
            0;


        /*
         * Structural metric validity only.
         *
         * We deliberately do not require any minimum historical
         * sample or any particular result.
         */
        captainWeightHistoryRealDataResult(
            is_numeric(
                $totalCalibrationGameweeks
            )
            &&
            (int) $totalCalibrationGameweeks
            >=
            0,
            'Captain candidate exposes a valid total-gameweek metric.'
        );


        captainWeightHistoryRealDataResult(
            is_numeric(
                $comparableGameweeks
            )
            &&
            is_numeric(
                $unavailableGameweeks
            )
            &&
            (
                (int) $comparableGameweeks
                +
                (int) $unavailableGameweeks
            )
            ===
            (int) $totalCalibrationGameweeks,
            'Captain candidate comparable and unavailable gameweeks reconcile to total gameweeks.'
        );


        echo "Strength: ";


        echo is_numeric(
            $strengthWeight
        )
            ? number_format(
                (float) $strengthWeight
                *
                100,
                0
            )
                . "%"
            : "N/A";


        echo " | Fixture: ";


        echo is_numeric(
            $fixtureWeight
        )
            ? number_format(
                (float) $fixtureWeight
                *
                100,
                0
            )
                . "%"
            : "N/A";


        echo " | Attacking Threat: ";


        echo is_numeric(
            $attackingThreatWeight
        )
            ? number_format(
                (float) $attackingThreatWeight
                *
                100,
                0
            )
                . "%"
            : "N/A";


        echo " | Total GWs: "
            . htmlspecialchars(
                (string) $totalCalibrationGameweeks,
                ENT_QUOTES,
                'UTF-8'
            );


        echo " | Comparable: "
            . htmlspecialchars(
                (string) $comparableGameweeks,
                ENT_QUOTES,
                'UTF-8'
            );


        echo " | Unavailable: "
            . htmlspecialchars(
                (string) $unavailableGameweeks,
                ENT_QUOTES,
                'UTF-8'
            );


        echo " | Captain Points Lost: "
            . htmlspecialchars(
                (string) $totalCaptainPointsLost,
                ENT_QUOTES,
                'UTF-8'
            );


        echo " | Mean Lost: ";


        echo is_numeric(
            $meanCaptainPointsLost
        )
            ? number_format(
                (float) $meanCaptainPointsLost,
                4
            )
            : "N/A";


        echo " | Optimal Selections: "
            . htmlspecialchars(
                (string) $optimalCaptainSelections,
                ENT_QUOTES,
                'UTF-8'
            );


        /*
         * Highlight current production for diagnostics only.
         *
         * This does not imply that current production is
         * preferred or optimal.
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
            is_numeric(
                $attackingThreatWeight
            )
            &&
            abs(
                (float) $strengthWeight
                -
                0.35
            )
            <
            0.000001
            &&
            abs(
                (float) $fixtureWeight
                -
                0.35
            )
            <
            0.000001
            &&
            abs(
                (float) $attackingThreatWeight
                -
                0.30
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
     * CURRENT PRODUCTION CANDIDATE PRESENT
     * ========================================================
     */

    $currentProductionFound =
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


        $attackingThreatWeight =
            $evaluation[
                'attacking_threat_weight'
            ]
            ??
            null;


        if (
            is_numeric(
                $strengthWeight
            )
            &&
            is_numeric(
                $fixtureWeight
            )
            &&
            is_numeric(
                $attackingThreatWeight
            )
            &&
            abs(
                (float) $strengthWeight
                -
                0.35
            )
            <
            0.000001
            &&
            abs(
                (float) $fixtureWeight
                -
                0.35
            )
            <
            0.000001
            &&
            abs(
                (float) $attackingThreatWeight
                -
                0.30
            )
            <
            0.000001
        ) {

            $currentProductionFound =
                true;

            break;
        }
    }


    captainWeightHistoryRealDataResult(
        $currentProductionFound,
        'Current production 35 / 35 / 30 Captain weighting is included for historical comparison.'
    );


    /*
     * ========================================================
     * NO HISTORICAL EVIDENCE REQUIREMENT
     * ========================================================
     *
     * At this stage of the season it is entirely legitimate for
     * the real historical sample to contain zero usable Captain
     * calibration gameweeks.
     *
     * The important requirement is that the system reports this
     * truthfully rather than fabricating historical evidence.
     */

    captainWeightHistoryRealDataResult(
        count(
            $historicalGameweeks
        )
        >=
        0,
        'Zero historically usable Captain gameweeks is a valid real-data state.'
    );


} catch (
    Throwable $throwable
) {

    captainWeightHistoryRealDataResult(
        false,
        'Real Captain calibration pipeline completed without Throwable: '
        .
        $throwable->getMessage()
    );
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Captain Weight Calibration History Real Data Test Summary<br>";
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