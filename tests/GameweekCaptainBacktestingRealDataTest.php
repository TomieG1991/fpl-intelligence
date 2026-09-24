<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Captain Backtesting Real Data Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function captainRealDataResult(
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


$entryId = 2702264;


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


    captainRealDataResult(
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


    $captainBacktestingService =
        new CaptainBacktestingService();


    $gameweekCaptainBacktestingService =
        new GameweekCaptainBacktestingService(
            $captainBacktestingService
        );


    /*
     * ========================================================
     * HISTORICAL COVERAGE
     * ========================================================
     */

    $storedGameweeks =
        $gameweekRepository->getAll();


    if (!is_array($storedGameweeks)) {

        $storedGameweeks = [];
    }


    $totalGameweeks = 0;
    $readyGameweeks = 0;
    $captainEvaluatedGameweeks = 0;

    $gameweekAudit = [];


    foreach (
        $storedGameweeks
        as $storedGameweek
    ) {

        if (!is_array($storedGameweek)) {

            continue;
        }


        $gameweekId =
            isset($storedGameweek['id'])
            &&
            is_numeric($storedGameweek['id'])
                ? (int) $storedGameweek['id']
                : 0;


        if ($gameweekId <= 0) {

            continue;
        }


        $totalGameweeks++;


        $evidence =
            $evidenceService->getEvidence(
                $entryId,
                $gameweekId
            );


        if (!is_array($evidence)) {

            $evidence = [

                'status' =>
                    'Unavailable',

                'reason' =>
                    'Historical backtesting evidence is unavailable.'
            ];
        }


        $status =
            $evidence[
                'status'
            ]
            ?? 'Unavailable';


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
                $evidence[
                    'reason'
                ]
                ?? null,

            'captain_status' =>
                null,

            'captain_reason' =>
                null,

            'captain_evaluation' =>
                []
        ];


        if ($status === 'Ready') {

            $readyGameweeks++;


            $captainResult =
                $gameweekCaptainBacktestingService
                    ->evaluate(
                        $evidence
                    );


            $auditRow[
                'captain_status'
            ] =
                $captainResult[
                    'status'
                ]
                ?? null;


            $auditRow[
                'captain_reason'
            ] =
                $captainResult[
                    'reason'
                ]
                ?? null;


            $auditRow[
                'captain_evaluation'
            ] =
                is_array(
                    $captainResult[
                        'captain_evaluation'
                    ]
                    ?? null
                )
                    ? $captainResult[
                        'captain_evaluation'
                    ]
                    : [];


            if (
                $auditRow[
                    'captain_status'
                ]
                ===
                'Ready'
            ) {

                $captainEvaluatedGameweeks++;
            }
        }


        $gameweekAudit[] =
            $auditRow;
    }


    /*
     * ========================================================
     * COVERAGE CONTRACT
     * ========================================================
     */

    captainRealDataResult(
        $totalGameweeks > 0,
        'Stored gameweek history is available.'
    );


    captainRealDataResult(
        count($gameweekAudit)
        ===
        $totalGameweeks,
        'Gameweek audit contains every valid stored gameweek considered.'
    );


    captainRealDataResult(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $totalGameweeks,
        'Ready gameweek count is internally valid.'
    );


    captainRealDataResult(
        $captainEvaluatedGameweeks
        <=
        $readyGameweeks,
        'Captain evaluation never exceeds authoritative Ready coverage.'
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

    echo "Captain evaluated gameweeks: "
        . $captainEvaluatedGameweeks
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
            $auditRow[
                'status'
            ]
            ===
            'Ready'
        ) {

            echo " | Captain: "
                . htmlspecialchars(
                    (string) (
                        $auditRow[
                            'captain_status'
                        ]
                        ?? 'Unknown'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                );


            if (
                $auditRow[
                    'captain_status'
                ]
                !==
                'Ready'
                &&
                $auditRow[
                    'captain_reason'
                ]
                !== null
            ) {

                echo " | "
                    . htmlspecialchars(
                        (string) $auditRow[
                            'captain_reason'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    );
            }

        } elseif (
            $auditRow[
                'reason'
            ]
            !== null
        ) {

            echo " | "
                . htmlspecialchars(
                    (string) $auditRow[
                        'reason'
                    ],
                    ENT_QUOTES,
                    'UTF-8'
                );
        }


        echo "<br>";
    }


    /*
     * ========================================================
     * GENUINE CAPTAIN OUTCOME
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "CAPTAIN OUTCOME EVALUATION<br>";
    echo "============================================<br>";


    $validCaptainEvaluations = 0;


    foreach (
        $gameweekAudit
        as $auditRow
    ) {

        if (
            $auditRow[
                'captain_status'
            ]
            !==
            'Ready'
        ) {

            continue;
        }


        $evaluation =
            $auditRow[
                'captain_evaluation'
            ];


        $captainPlayerId =
            $evaluation[
                'captain_player_id'
            ]
            ?? null;


        $captainPoints =
            $evaluation[
                'captain_actual_points'
            ]
            ?? null;


        $captainMinutes =
            $evaluation[
                'captain_actual_minutes'
            ]
            ?? null;


        $bestAlternativePlayerId =
            $evaluation[
                'best_alternative_player_id'
            ]
            ?? null;


        $bestAlternativePoints =
            $evaluation[
                'best_alternative_actual_points'
            ]
            ?? null;


        $captainPointsLost =
            $evaluation[
                'captain_points_lost'
            ]
            ?? null;


        if (
            is_numeric($captainPlayerId)
            &&
            is_numeric($captainPoints)
            &&
            is_numeric($bestAlternativePlayerId)
            &&
            is_numeric($bestAlternativePoints)
            &&
            is_numeric($captainPointsLost)
        ) {

            $validCaptainEvaluations++;
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
            . " | Captain Player: "
            . htmlspecialchars(
                (string) (
                    $captainPlayerId
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Captain Points: "
            . htmlspecialchars(
                (string) (
                    $captainPoints
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Captain Minutes: "
            . htmlspecialchars(
                (string) (
                    $captainMinutes
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Best Alternative: "
            . htmlspecialchars(
                (string) (
                    $bestAlternativePlayerId
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Best Alternative Points: "
            . htmlspecialchars(
                (string) (
                    $bestAlternativePoints
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Captain Points Lost: "
            . htmlspecialchars(
                (string) (
                    $captainPointsLost
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }


    captainRealDataResult(
        $validCaptainEvaluations
        ===
        $captainEvaluatedGameweeks,
        'Every Ready Captain evaluation contains complete realised captain metrics.'
    );


    /*
     * Zero Ready history is legitimate. Never manufacture
     * Captain Intelligence merely to make the test non-empty.
     */
    if ($readyGameweeks === 0) {

        captainRealDataResult(
            $captainEvaluatedGameweeks === 0,
            'Zero Ready gameweeks produce zero Captain evaluations.'
        );
    }


} catch (
    Throwable $exception
) {

    captainRealDataResult(
        false,
        'Real historical Captain evaluation executes without exception: '
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
echo "Gameweek Captain Backtesting Real Data Test Summary<br>";
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