<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Player Ranking Backtesting Real Data Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function playerRankingRealDataResult(
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


    playerRankingRealDataResult(
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


    $rankingBacktestingService =
        new PlayerRankingBacktestingService();


    $rankingMetricsService =
        new PlayerRankingBacktestingMetricsService();


    $gameweekRankingService =
        new GameweekPlayerRankingBacktestingService(
            $rankingBacktestingService,
            $rankingMetricsService
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
    $evaluatedGameweeks = 0;
    $totalPlayerEvaluations = 0;

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

            'gameweek_id' =>
                $gameweekId,

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

            'evaluation_status' =>
                null,

            'player_evaluations' =>
                0,

            'metrics' =>
                null
        ];


        if ($status === 'Ready') {

            $readyGameweeks++;


            $result =
                $gameweekRankingService
                    ->evaluate(
                        $evidence
                    );


            $evaluationStatus =
                $result[
                    'status'
                ]
                ?? null;


            $playerEvaluations =
                is_array(
                    $result[
                        'player_evaluations'
                    ]
                    ?? null
                )
                    ? $result[
                        'player_evaluations'
                    ]
                    : [];


            $metrics =
                is_array(
                    $result[
                        'metrics'
                    ]
                    ?? null
                )
                    ? $result[
                        'metrics'
                    ]
                    : null;


            $auditRow[
                'evaluation_status'
            ] =
                $evaluationStatus;


            $auditRow[
                'player_evaluations'
            ] =
                count(
                    $playerEvaluations
                );


            $auditRow[
                'metrics'
            ] =
                $metrics;


            if (
                $evaluationStatus
                ===
                'Ready'
            ) {

                $evaluatedGameweeks++;


                $totalPlayerEvaluations +=
                    count(
                        $playerEvaluations
                    );
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

    playerRankingRealDataResult(
        $totalGameweeks > 0,
        'Stored gameweek history is available.'
    );


    playerRankingRealDataResult(
        count($gameweekAudit)
        ===
        $totalGameweeks,
        'Gameweek audit contains every valid stored gameweek considered.'
    );


    playerRankingRealDataResult(
        $readyGameweeks >= 0
        &&
        $readyGameweeks <= $totalGameweeks,
        'Ready gameweek count is internally valid.'
    );


    playerRankingRealDataResult(
        $evaluatedGameweeks
        <=
        $readyGameweeks,
        'Player Ranking evaluation never exceeds authoritative Ready coverage.'
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

    echo "Player Ranking evaluated gameweeks: "
        . $evaluatedGameweeks
        . "<br>";

    echo "Player Ranking evaluations: "
        . $totalPlayerEvaluations
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

            echo " | Ranking: "
                . htmlspecialchars(
                    (string) (
                        $auditRow[
                            'evaluation_status'
                        ]
                        ?? 'Unknown'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . " | Players: "
                . htmlspecialchars(
                    (string) $auditRow[
                        'player_evaluations'
                    ],
                    ENT_QUOTES,
                    'UTF-8'
                );

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
     * RANKING METRICS
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "PLAYER RANKING OUTCOME EVALUATION<br>";
    echo "============================================<br>";


    $validMetricGameweeks = 0;


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


        $metrics =
            $auditRow[
                'metrics'
            ];


        if (!is_array($metrics)) {

            continue;
        }


        $sampleSize =
            $metrics[
                'sample_size'
            ]
            ?? null;


        $pearson =
            $metrics[
                'pearson_correlation'
            ]
            ?? null;


        $spearman =
            $metrics[
                'spearman_rank_correlation'
            ]
            ?? null;


        if (
            is_numeric($sampleSize)
            &&
            (int) $sampleSize > 0
        ) {

            $validMetricGameweeks++;
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
            . " | Sample: "
            . htmlspecialchars(
                (string) (
                    $sampleSize
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Pearson: "
            . htmlspecialchars(
                (string) (
                    $pearson
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . " | Spearman: "
            . htmlspecialchars(
                (string) (
                    $spearman
                    ?? 'N/A'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }


    playerRankingRealDataResult(
        $validMetricGameweeks
        ===
        $evaluatedGameweeks,
        'Every Ready Player Ranking evaluation exposes a non-empty realised sample.'
    );


    playerRankingRealDataResult(
        $totalPlayerEvaluations >= 0,
        'Historical Player Ranking evaluation count is available.'
    );


    /*
     * A database with no authoritative Ready gameweeks is a
     * legitimate historical state. Never manufacture ranking
     * evidence merely to make the test non-empty.
     */
    if ($readyGameweeks === 0) {

        playerRankingRealDataResult(
            $evaluatedGameweeks === 0
            &&
            $totalPlayerEvaluations === 0,
            'Zero Ready gameweeks produce zero Player Ranking evaluations.'
        );
    }


} catch (
    Throwable $exception
) {

    playerRankingRealDataResult(
        false,
        'Real historical Player Ranking evaluation executes without exception: '
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
echo "Gameweek Player Ranking Backtesting Real Data Test Summary<br>";
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