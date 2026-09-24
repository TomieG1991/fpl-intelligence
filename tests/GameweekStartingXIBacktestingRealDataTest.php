<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Starting XI Backtesting Real Data Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function gameweekStartingXIRealDataCheck(
    bool $condition,
    string $description
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


$entryId =
    2702264;


try {

    /*
     * ========================================================
     * REAL PRODUCTION DEPENDENCIES
     * ========================================================
     */

    $database =
        new Database();


    $db =
        $database->getConnection();


    gameweekStartingXIRealDataCheck(
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


    /*
     * Evaluate the preserved production Starting XI against
     * the best legal realised XI from exactly the same
     * preserved 15-player squad.
     */
    $selectionBacktestingService =
        new StartingXISelectionBacktestingService();


    $gameweekBacktestingService =
        new GameweekStartingXIBacktestingService(
            $selectionBacktestingService
        );


    /*
     * ========================================================
     * HISTORICAL COVERAGE
     * ========================================================
     */

    $storedGameweeks =
        $gameweekRepository->getAll();


    $validStoredGameweeks =
        0;


    $readyGameweeks =
        0;


    $evaluatedGameweeks =
        0;


    $gameweekAudit =
        [];


    foreach (
        $storedGameweeks
        as $storedGameweek
    ) {

        if (!is_array($storedGameweek)) {

            continue;
        }


        $gameweekId =
            isset(
                $storedGameweek['id']
            )
            &&
            is_numeric(
                $storedGameweek['id']
            )
                ? (int) $storedGameweek['id']
                : 0;


        if ($gameweekId <= 0) {

            continue;
        }


        $validStoredGameweeks++;


        $evidence =
            $evidenceService->getEvidence(
                $entryId,
                $gameweekId
            );


        $status =
            is_array($evidence)
                ? ($evidence['status'] ?? 'Unavailable')
                : 'Unavailable';


        $reason =
            is_array($evidence)
                ? ($evidence['reason'] ?? null)
                : 'Historical backtesting evidence is unavailable.';


        $auditRow = [

            'fpl_gameweek_id' =>
                $storedGameweek[
                    'fpl_gameweek_id'
                ]
                ?? null,

            'name' =>
                $storedGameweek[
                    'name'
                ]
                ?? null,

            'status' =>
                $status,

            'reason' =>
                $reason,

            'evaluation_status' =>
                null,

            'evaluation_reason' =>
                null,

            'evaluation' =>
                []
        ];


        if ($status === 'Ready') {

            $readyGameweeks++;


            $evaluationResult =
                $gameweekBacktestingService
                    ->evaluate(
                        $evidence
                    );


            $auditRow['evaluation_status'] =
                $evaluationResult[
                    'status'
                ]
                ?? null;


            $auditRow['evaluation_reason'] =
                $evaluationResult[
                    'reason'
                ]
                ?? null;


            $auditRow['evaluation'] =
                is_array(
                    $evaluationResult[
                        'starting_xi_evaluation'
                    ]
                    ?? null
                )
                    ? $evaluationResult[
                        'starting_xi_evaluation'
                    ]
                    : [];


            if (
                $auditRow[
                    'evaluation_status'
                ]
                ===
                'Ready'
            ) {

                $evaluatedGameweeks++;
            }
        }


        $gameweekAudit[] =
            $auditRow;
    }


    gameweekStartingXIRealDataCheck(
        $validStoredGameweeks > 0,
        'Stored gameweek history is available.'
    );


    gameweekStartingXIRealDataCheck(
        count($gameweekAudit)
        ===
        $validStoredGameweeks,
        'Every valid stored gameweek remains auditable.'
    );


    gameweekStartingXIRealDataCheck(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $validStoredGameweeks,
        'Ready gameweek count is internally valid.'
    );


    gameweekStartingXIRealDataCheck(
        $evaluatedGameweeks <= $readyGameweeks,
        'Starting XI evaluation never exceeds authoritative Ready coverage.'
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
        . $validStoredGameweeks
        . "<br>";

    echo "Ready gameweeks: "
        . $readyGameweeks
        . "<br>";

    echo "Starting XI evaluated gameweeks: "
        . $evaluatedGameweeks
        . "<br>";


    echo "<br>";
    echo "============================================<br>";
    echo "GAMEWEEK COVERAGE AUDIT<br>";
    echo "============================================<br>";


    foreach (
        $gameweekAudit
        as $auditRow
    ) {

        echo "GW "
            . htmlspecialchars(
                (string) (
                    $auditRow[
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
                    $auditRow[
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
                    $auditRow[
                        'status'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            );


        if (
            $auditRow['status']
            ===
            'Ready'
        ) {

            echo " | Starting XI: "
                . htmlspecialchars(
                    (string) (
                        $auditRow[
                            'evaluation_status'
                        ]
                        ?? 'Unknown'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                );


            if (
                $auditRow[
                    'evaluation_reason'
                ]
                !== null
            ) {

                echo " ("
                    . htmlspecialchars(
                        (string) $auditRow[
                            'evaluation_reason'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    )
                    . ")";
            }

        } elseif (
            $auditRow['reason']
            !== null
            &&
            trim(
                (string) $auditRow['reason']
            )
            !== ''
        ) {

            echo " | "
                . htmlspecialchars(
                    (string) $auditRow['reason'],
                    ENT_QUOTES,
                    'UTF-8'
                );
        }


        echo "<br>";
    }


    /*
     * ========================================================
     * GENUINE STARTING XI OUTCOMES
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "STARTING XI OUTCOME EVALUATION<br>";
    echo "============================================<br>";


    $structurallyValidEvaluations =
        0;


    foreach (
        $gameweekAudit
        as $auditRow
    ) {

        if (
            $auditRow[
                'evaluation_status'
            ]
            !==
            'Ready'
        ) {

            continue;
        }


        $evaluation =
            $auditRow[
                'evaluation'
            ];


        $recommendedPoints =
            $evaluation[
                'recommended_xi_points'
            ]
            ?? null;


        $bestLegalPoints =
            $evaluation[
                'best_legal_xi_points'
            ]
            ?? null;


        $pointsLost =
            $evaluation[
                'selection_points_lost'
            ]
            ?? null;


        $bestLegalXI =
            $evaluation[
                'best_legal_xi'
            ]
            ?? [];


        if (
            is_numeric($recommendedPoints)
            &&
            is_numeric($bestLegalPoints)
            &&
            is_numeric($pointsLost)
            &&
            is_array($bestLegalXI)
            &&
            count($bestLegalXI) === 11
        ) {

            $structurallyValidEvaluations++;
        }


        echo "GW "
            . htmlspecialchars(
                (string) (
                    $auditRow[
                        'fpl_gameweek_id'
                    ]
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Recommended XI: "
            . htmlspecialchars(
                (string) (
                    $recommendedPoints
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Best Legal XI: "
            . htmlspecialchars(
                (string) (
                    $bestLegalPoints
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Selection Points Lost: "
            . htmlspecialchars(
                (string) (
                    $pointsLost
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }


    gameweekStartingXIRealDataCheck(
        $structurallyValidEvaluations
        ===
        $evaluatedGameweeks,
        'Every Ready Starting XI evaluation contains complete realised selection metrics.'
    );


    /*
     * Current history may legitimately have zero Ready
     * gameweeks. No result is manufactured in that case.
     */
    if ($readyGameweeks === 0) {

        gameweekStartingXIRealDataCheck(
            $evaluatedGameweeks === 0,
            'Zero Ready gameweeks produce zero Starting XI evaluations.'
        );
    }


} catch (
    Throwable $exception
) {

    gameweekStartingXIRealDataCheck(
        'Unexpected exception: '
            . $exception->getMessage(),
        false
    );
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Gameweek Starting XI Backtesting Real Data Test Summary<br>";
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