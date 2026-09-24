<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Transfer Decision Backtesting Real Data Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function transferDecisionRealDataResult(
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


    transferDecisionRealDataResult(
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


    $transferBacktestingService =
        new TransferBacktestingService();


    $gameweekTransferBacktestingService =
        new GameweekTransferBacktestingService(
            $transferBacktestingService
        );


    $transferDecisionBacktestingService =
        new TransferDecisionBacktestingService();


    $gameweekTransferDecisionBacktestingService =
        new GameweekTransferDecisionBacktestingService(
            $transferDecisionBacktestingService
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
    $transferEvaluatedGameweeks = 0;
    $decisionEvaluatedGameweeks = 0;

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

            'transfer_status' =>
                null,

            'transfer_reason' =>
                null,

            'transfer_evaluation' =>
                [],

            'decision_status' =>
                null,

            'decision_reason' =>
                null,

            'decision_evaluation' =>
                []
        ];


        if ($status === 'Ready') {

            $readyGameweeks++;


            /*
             * First evaluate the exact top preserved transfer
             * recommendation against authoritative outcomes.
             */
            $transferResult =
                $gameweekTransferBacktestingService
                    ->evaluate(
                        $evidence
                    );


            $auditRow[
                'transfer_status'
            ] =
                $transferResult[
                    'status'
                ]
                ?? null;


            $auditRow[
                'transfer_reason'
            ] =
                $transferResult[
                    'reason'
                ]
                ?? null;


            $auditRow[
                'transfer_evaluation'
            ] =
                is_array(
                    $transferResult[
                        'transfer_evaluation'
                    ]
                    ?? null
                )
                    ? $transferResult[
                        'transfer_evaluation'
                    ]
                    : [];


            if (
                $auditRow[
                    'transfer_status'
                ]
                ===
                'Ready'
            ) {

                $transferEvaluatedGameweeks++;
            }


            /*
             * Then evaluate the exact preserved manager-facing
             * transfer decision against that factual transfer
             * outcome.
             */
            $decisionResult =
                $gameweekTransferDecisionBacktestingService
                    ->evaluate(
                        $evidence,
                        $transferResult
                    );


            $auditRow[
                'decision_status'
            ] =
                $decisionResult[
                    'status'
                ]
                ?? null;


            $auditRow[
                'decision_reason'
            ] =
                $decisionResult[
                    'reason'
                ]
                ?? null;


            $auditRow[
                'decision_evaluation'
            ] =
                is_array(
                    $decisionResult[
                        'decision_evaluation'
                    ]
                    ?? null
                )
                    ? $decisionResult[
                        'decision_evaluation'
                    ]
                    : [];


            if (
                $auditRow[
                    'decision_status'
                ]
                ===
                'Ready'
            ) {

                $decisionEvaluatedGameweeks++;
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

    transferDecisionRealDataResult(
        $totalGameweeks > 0,
        'Stored gameweek history is available.'
    );


    transferDecisionRealDataResult(
        count($gameweekAudit)
        ===
        $totalGameweeks,
        'Gameweek audit contains every valid stored gameweek considered.'
    );


    transferDecisionRealDataResult(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $totalGameweeks,
        'Ready gameweek count is internally valid.'
    );


    transferDecisionRealDataResult(
        $transferEvaluatedGameweeks
        <=
        $readyGameweeks,
        'Transfer evaluation never exceeds authoritative Ready coverage.'
    );


    transferDecisionRealDataResult(
        $decisionEvaluatedGameweeks
        <=
        $transferEvaluatedGameweeks,
        'Transfer Decision evaluation never exceeds factual transfer evaluation coverage.'
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

    echo "Transfer evaluated gameweeks: "
        . $transferEvaluatedGameweeks
        . "<br>";

    echo "Transfer Decision evaluated gameweeks: "
        . $decisionEvaluatedGameweeks
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

            echo " | Transfer: "
                . htmlspecialchars(
                    (string) (
                        $auditRow[
                            'transfer_status'
                        ]
                        ?? 'Unknown'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . " | Decision: "
                . htmlspecialchars(
                    (string) (
                        $auditRow[
                            'decision_status'
                        ]
                        ?? 'Unknown'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                );


            if (
                $auditRow[
                    'transfer_status'
                ]
                !==
                'Ready'
                &&
                $auditRow[
                    'transfer_reason'
                ]
                !== null
            ) {

                echo " | "
                    . htmlspecialchars(
                        (string) $auditRow[
                            'transfer_reason'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    );

            } elseif (
                $auditRow[
                    'decision_status'
                ]
                !==
                'Ready'
                &&
                $auditRow[
                    'decision_reason'
                ]
                !== null
            ) {

                echo " | "
                    . htmlspecialchars(
                        (string) $auditRow[
                            'decision_reason'
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
     * FACTUAL TRANSFER OUTCOME
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "TRANSFER OUTCOME EVALUATION<br>";
    echo "============================================<br>";


    $validTransferEvaluations = 0;


    foreach (
        $gameweekAudit
        as $auditRow
    ) {

        if (
            $auditRow[
                'transfer_status'
            ]
            !==
            'Ready'
        ) {

            continue;
        }


        $evaluation =
            $auditRow[
                'transfer_evaluation'
            ];


        $outgoingId =
            $evaluation[
                'outgoing_player_id'
            ]
            ?? null;


        $incomingId =
            $evaluation[
                'incoming_player_id'
            ]
            ?? null;


        $outgoingPoints =
            $evaluation[
                'outgoing_actual_points'
            ]
            ?? null;


        $incomingPoints =
            $evaluation[
                'incoming_actual_points'
            ]
            ?? null;


        $pointsGain =
            $evaluation[
                'transfer_points_gain'
            ]
            ?? null;


        if (
            is_numeric($outgoingId)
            &&
            is_numeric($incomingId)
            &&
            is_numeric($outgoingPoints)
            &&
            is_numeric($incomingPoints)
            &&
            is_numeric($pointsGain)
        ) {

            $validTransferEvaluations++;
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
            . " | Outgoing Player: "
            . htmlspecialchars(
                (string) (
                    $outgoingId
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Incoming Player: "
            . htmlspecialchars(
                (string) (
                    $incomingId
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Outgoing Points: "
            . htmlspecialchars(
                (string) (
                    $outgoingPoints
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Incoming Points: "
            . htmlspecialchars(
                (string) (
                    $incomingPoints
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Transfer Points Gain: "
            . htmlspecialchars(
                (string) (
                    $pointsGain
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }


    transferDecisionRealDataResult(
        $validTransferEvaluations
        ===
        $transferEvaluatedGameweeks,
        'Every Ready transfer evaluation contains complete realised transfer metrics.'
    );


    /*
     * ========================================================
     * MANAGER-FACING DECISION OUTCOME
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "TRANSFER DECISION OUTCOME EVALUATION<br>";
    echo "============================================<br>";


    $validDecisionEvaluations = 0;


    foreach (
        $gameweekAudit
        as $auditRow
    ) {

        if (
            $auditRow[
                'decision_status'
            ]
            !==
            'Ready'
        ) {

            continue;
        }


        $evaluation =
            $auditRow[
                'decision_evaluation'
            ];


        $action =
            $evaluation[
                'decision_action'
            ]
            ?? null;


        $priority =
            $evaluation[
                'decision_priority'
            ]
            ?? null;


        $score =
            $evaluation[
                'decision_score'
            ]
            ?? null;


        $pointsGain =
            $evaluation[
                'transfer_points_gain'
            ]
            ?? null;


        $supportStatus =
            $evaluation[
                'support_status'
            ]
            ?? null;


        if (
            is_string($action)
            &&
            trim($action) !== ''
            &&
            is_numeric($pointsGain)
            &&
            is_string($supportStatus)
            &&
            trim($supportStatus) !== ''
        ) {

            $validDecisionEvaluations++;
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
            . " | Action: "
            . htmlspecialchars(
                (string) (
                    $action
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Priority: "
            . htmlspecialchars(
                (string) (
                    $priority
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Score: "
            . htmlspecialchars(
                (string) (
                    $score
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Transfer Points Gain: "
            . htmlspecialchars(
                (string) (
                    $pointsGain
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Support: "
            . htmlspecialchars(
                (string) (
                    $supportStatus
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }


    transferDecisionRealDataResult(
        $validDecisionEvaluations
        ===
        $decisionEvaluatedGameweeks,
        'Every Ready Transfer Decision evaluation contains complete realised decision evidence.'
    );


    /*
     * Zero Ready history is legitimate and must remain empty.
     */
    if ($readyGameweeks === 0) {

        transferDecisionRealDataResult(
            $transferEvaluatedGameweeks === 0
            &&
            $decisionEvaluatedGameweeks === 0,
            'Zero Ready gameweeks produce zero transfer evaluations.'
        );
    }


} catch (
    Throwable $exception
) {

    transferDecisionRealDataResult(
        false,
        'Real historical Transfer Decision evaluation executes without exception: '
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
echo "Gameweek Transfer Decision Backtesting Real Data Test Summary<br>";
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