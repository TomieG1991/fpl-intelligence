<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Starting XI Selection Backtesting Service Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function startingXISelectionIntegrationTestResult(
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
 * REAL DATABASE
 * ============================================================
 */

$database =
    new Database();


$pdo =
    $database->getConnection();


startingXISelectionIntegrationTestResult(
    $pdo instanceof PDO,
    'Real database connection is available.'
);


/*
 * ============================================================
 * REAL PRODUCTION SERVICES
 * ============================================================
 */

$playerFixtureHistoryRepository =
    new PlayerFixtureHistoryRepository(
        $pdo
    );


$playerGameweekOutcomeService =
    new PlayerGameweekOutcomeService(
        $playerFixtureHistoryRepository
    );


$selectionBacktestingService =
    new StartingXISelectionBacktestingService();


startingXISelectionIntegrationTestResult(
    $playerGameweekOutcomeService
        instanceof PlayerGameweekOutcomeService,
    'Real player gameweek outcome service can be constructed.'
);


startingXISelectionIntegrationTestResult(
    $selectionBacktestingService
        instanceof StartingXISelectionBacktestingService,
    'Real Starting XI selection backtesting service can be constructed.'
);


/*
 * ============================================================
 * DISCOVER AUTHORITATIVE COMPLETED GAMEWEEK
 * ============================================================
 */

$statement =
    $pdo->query(
        "
        SELECT
            g.id,
            g.fpl_gameweek_id,
            g.name
        FROM
            gameweeks g
        WHERE
            g.finished = 1
            AND
            g.data_checked = 1
            AND EXISTS (
                SELECT
                    1
                FROM
                    player_fixture_history pfh
                WHERE
                    pfh.gameweek_id = g.id
            )
        ORDER BY
            g.id ASC
        LIMIT 1
        "
    );


$gameweek =
    $statement->fetch(
        PDO::FETCH_ASSOC
    );


startingXISelectionIntegrationTestResult(
    is_array(
        $gameweek
    ),
    'Authoritative completed gameweek with real outcome evidence is available.'
);


if (
    !is_array(
        $gameweek
    )
) {

    echo "<br>";
    echo "============================================<br>";
    echo "TEST SUMMARY<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "RESULT: TESTS FAILED ❌<br>";

    exit;
}


$gameweekId =
    (int) $gameweek[
        'id'
    ];


/*
 * ============================================================
 * LOAD REAL COMPLETED-GAMEWEEK OUTCOMES
 * ============================================================
 */

$playerOutcomes =
    $playerGameweekOutcomeService
        ->getByGameweekId(
            $gameweekId
        );


startingXISelectionIntegrationTestResult(
    !empty(
        $playerOutcomes
    ),
    'Real completed-gameweek player outcomes are available.'
);


/*
 * ============================================================
 * BUILD OUTCOME LOOKUP
 * ============================================================
 */

$outcomesByPlayerId =
    [];


foreach (
    $playerOutcomes
    as $outcome
) {

    $playerId =
        $outcome[
            'player_id'
        ]
        ?? null;


    if (
        !is_numeric(
            $playerId
        )
        ||
        (int) $playerId <= 0
    ) {

        continue;
    }


    $outcomesByPlayerId[
        (int) $playerId
    ] =
        $outcome;
}


/*
 * ============================================================
 * DISCOVER REAL PLAYERS WITH POSITION EVIDENCE
 * ============================================================
 *
 * The players table is used only for preserved player
 * identity and FPL position.
 *
 * Realised points/minutes still come exclusively from
 * PlayerGameweekOutcomeService.
 *
 * We select a normal 15-player FPL squad shape:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 * ============================================================
 */

$positionRequirements =
    [
        'GK' => 2,
        'DEF' => 5,
        'MID' => 5,
        'FWD' => 3
    ];


$playersByPosition =
    [
        'GK' => [],
        'DEF' => [],
        'MID' => [],
        'FWD' => []
    ];


$playerStatement =
    $pdo->query(
        "
        SELECT
            id,
            first_name,
            second_name,
            web_name,
            position
        FROM
            players
        WHERE
            position IN ('GK', 'DEF', 'MID', 'FWD')
        ORDER BY
            id ASC
        "
    );


$databasePlayers =
    $playerStatement->fetchAll(
        PDO::FETCH_ASSOC
    );


foreach (
    $databasePlayers
    as $player
) {

    $playerId =
        (int) (
            $player[
                'id'
            ]
            ?? 0
        );


    $position =
        strtoupper(
            trim(
                (string) (
                    $player[
                        'position'
                    ]
                    ?? ''
                )
            )
        );


    if (
        $playerId <= 0
        ||
        !array_key_exists(
            $playerId,
            $outcomesByPlayerId
        )
        ||
        !array_key_exists(
            $position,
            $positionRequirements
        )
    ) {

        continue;
    }


    if (
        count(
            $playersByPosition[
                $position
            ]
        )
        >=
        $positionRequirements[
            $position
        ]
    ) {

        continue;
    }


    $name =
        trim(
            (string) (
                $player[
                    'web_name'
                ]
                ?? ''
            )
        );


    if (
        $name === ''
    ) {

        $name =
            trim(
                (string) (
                    $player[
                        'first_name'
                    ]
                    ?? ''
                )
                . ' '
                . (string) (
                    $player[
                        'second_name'
                    ]
                    ?? ''
                )
            );
    }


    $playersByPosition[
        $position
    ][] = [

        'player_id' =>
            $playerId,

        'name' =>
            $name,

        'position' =>
            $position
    ];
}


/*
 * ============================================================
 * VERIFY COMPLETE REAL SQUAD CAN BE BUILT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Real Squad Position Evidence<br>";
echo "============================================<br>";


foreach (
    $positionRequirements
    as $position => $requiredCount
) {

    startingXISelectionIntegrationTestResult(
        count(
            $playersByPosition[
                $position
            ]
        )
        ===
        $requiredCount,
        'Real completed-gameweek evidence provides required '
            . $position
            . ' players.'
    );
}


$completeSquadAvailable =
    count(
        $playersByPosition[
            'GK'
        ]
    )
    === 2
    &&
    count(
        $playersByPosition[
            'DEF'
        ]
    )
    === 5
    &&
    count(
        $playersByPosition[
            'MID'
        ]
    )
    === 5
    &&
    count(
        $playersByPosition[
            'FWD'
        ]
    )
    === 3;


if (
    !$completeSquadAvailable
) {

    echo "<br>";
    echo "============================================<br>";
    echo "TEST SUMMARY<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "RESULT: TESTS FAILED ❌<br>";

    exit;
}


/*
 * ============================================================
 * BUILD LEGAL PRESERVED RECOMMENDED XI
 * ============================================================
 *
 * Recommended formation:
 *
 * 1 GK
 * 3 DEF
 * 4 MID
 * 3 FWD
 *
 * Remaining players form the four-player bench:
 *
 * 1 GK
 * 2 DEF
 * 1 MID
 * ============================================================
 */

$startingXI =
    [
        $playersByPosition['GK'][0],

        $playersByPosition['DEF'][0],
        $playersByPosition['DEF'][1],
        $playersByPosition['DEF'][2],

        $playersByPosition['MID'][0],
        $playersByPosition['MID'][1],
        $playersByPosition['MID'][2],
        $playersByPosition['MID'][3],

        $playersByPosition['FWD'][0],
        $playersByPosition['FWD'][1],
        $playersByPosition['FWD'][2]
    ];


$bench =
    [
        $playersByPosition['GK'][1],

        $playersByPosition['DEF'][3],
        $playersByPosition['DEF'][4],

        $playersByPosition['MID'][4]
    ];


startingXISelectionIntegrationTestResult(
    count(
        $startingXI
    )
    === 11,
    'Real preserved recommendation contains eleven players.'
);


startingXISelectionIntegrationTestResult(
    count(
        $bench
    )
    === 4,
    'Real preserved recommendation contains four bench players.'
);


/*
 * ============================================================
 * PRESERVE SOURCE EVIDENCE
 * ============================================================
 */

$originalStartingXI =
    $startingXI;


$originalBench =
    $bench;


$originalOutcomes =
    $playerOutcomes;


/*
 * ============================================================
 * SCENARIO B
 * REAL LEGAL-XI EVALUATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Real Legal-XI Evaluation<br>";
echo "============================================<br>";


$result =
    $selectionBacktestingService
        ->evaluate(
            $startingXI,
            $bench,
            $playerOutcomes
        );


startingXISelectionIntegrationTestResult(
    !empty(
        $result
    ),
    'Real player positions and outcomes produce legal-XI evaluation.'
);


startingXISelectionIntegrationTestResult(
    count(
        $result[
            'best_legal_xi'
        ]
        ?? []
    )
    === 11,
    'Real best legal XI contains exactly eleven players.'
);


/*
 * ============================================================
 * SCENARIO C
 * RECOMMENDED XI REALISED POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Real Recommended XI Points<br>";
echo "============================================<br>";


$expectedRecommendedPoints =
    0;


foreach (
    $startingXI
    as $player
) {

    $expectedRecommendedPoints +=
        $outcomesByPlayerId[
            $player[
                'player_id'
            ]
        ][
            'total_points'
        ];
}


startingXISelectionIntegrationTestResult(
    (
        $result[
            'recommended_xi_points'
        ]
        ?? null
    )
    ===
    $expectedRecommendedPoints,
    'Recommended XI total uses genuine realised FPL points.'
);


/*
 * ============================================================
 * SCENARIO D
 * BEST XI FORMATION LEGALITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Real Best-XI Formation Legality<br>";
echo "============================================<br>";


$positionCounts =
    [
        'GK' => 0,
        'DEF' => 0,
        'MID' => 0,
        'FWD' => 0
    ];


foreach (
    $result[
        'best_legal_xi'
    ]
    ?? []
    as $player
) {

    $position =
        $player[
            'position'
        ]
        ?? null;


    if (
        array_key_exists(
            $position,
            $positionCounts
        )
    ) {

        $positionCounts[
            $position
        ]++;
    }
}


startingXISelectionIntegrationTestResult(
    $positionCounts['GK'] === 1,
    'Real best legal XI contains exactly one goalkeeper.'
);


startingXISelectionIntegrationTestResult(
    $positionCounts['DEF'] >= 3
        &&
    $positionCounts['DEF'] <= 5,
    'Real best legal XI contains a legal number of defenders.'
);


startingXISelectionIntegrationTestResult(
    $positionCounts['MID'] >= 2
        &&
    $positionCounts['MID'] <= 5,
    'Real best legal XI contains a legal number of midfielders.'
);


startingXISelectionIntegrationTestResult(
    $positionCounts['FWD'] >= 1
        &&
    $positionCounts['FWD'] <= 3,
    'Real best legal XI contains a legal number of forwards.'
);


/*
 * ============================================================
 * SCENARIO E
 * REAL BEST-XI POINT TOTAL
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Real Best-XI Point Total<br>";
echo "============================================<br>";


$calculatedBestLegalPoints =
    0;


foreach (
    $result[
        'best_legal_xi'
    ]
    ?? []
    as $player
) {

    $calculatedBestLegalPoints +=
        $player[
            'actual_points'
        ];
}


startingXISelectionIntegrationTestResult(
    (
        $result[
            'best_legal_xi_points'
        ]
        ?? null
    )
    ===
    $calculatedBestLegalPoints,
    'Best legal XI total equals its genuine realised player points.'
);


startingXISelectionIntegrationTestResult(
    (
        $result[
            'best_legal_xi_points'
        ]
        ?? null
    )
    >=
    (
        $result[
            'recommended_xi_points'
        ]
        ?? PHP_INT_MAX
    ),
    'Best legal XI does not score fewer points than legal recommended XI.'
);


/*
 * ============================================================
 * SCENARIO F
 * REAL SELECTION POINTS LOST
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Real Selection Points Lost<br>";
echo "============================================<br>";


$expectedSelectionPointsLost =
    $result[
        'best_legal_xi_points'
    ]
    -
    $result[
        'recommended_xi_points'
    ];


startingXISelectionIntegrationTestResult(
    (
        $result[
            'selection_points_lost'
        ]
        ?? null
    )
    ===
    $expectedSelectionPointsLost,
    'Selection points lost equals genuine best-XI points minus recommended-XI points.'
);


startingXISelectionIntegrationTestResult(
    (
        $result[
            'selection_points_lost'
        ]
        ?? -1
    )
    >= 0,
    'Real selection points lost is never negative.'
);


/*
 * ============================================================
 * SCENARIO G
 * EVERY BEST-XI PLAYER HAS GENUINE OUTCOME
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Genuine Outcome Provenance<br>";
echo "============================================<br>";


$allBestXIPlayersHaveRealOutcomes =
    true;


foreach (
    $result[
        'best_legal_xi'
    ]
    ?? []
    as $player
) {

    $playerId =
        $player[
            'player_id'
        ]
        ?? null;


    if (
        !array_key_exists(
            $playerId,
            $outcomesByPlayerId
        )
    ) {

        $allBestXIPlayersHaveRealOutcomes =
            false;

        break;
    }


    if (
        $player[
            'actual_points'
        ]
        !==
        $outcomesByPlayerId[
            $playerId
        ][
            'total_points'
        ]
    ) {

        $allBestXIPlayersHaveRealOutcomes =
            false;

        break;
    }
}


startingXISelectionIntegrationTestResult(
    $allBestXIPlayersHaveRealOutcomes,
    'Every best-XI player retains genuine completed-gameweek outcome evidence.'
);


/*
 * ============================================================
 * SCENARIO H
 * SOURCE IMMUTABILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


startingXISelectionIntegrationTestResult(
    $startingXI
        ===
        $originalStartingXI,
    'Real preserved Starting XI evidence remains unchanged.'
);


startingXISelectionIntegrationTestResult(
    $bench
        ===
        $originalBench,
    'Real preserved bench evidence remains unchanged.'
);


startingXISelectionIntegrationTestResult(
    $playerOutcomes
        ===
        $originalOutcomes,
    'Real completed-gameweek outcomes remain unchanged.'
);


/*
 * ============================================================
 * SCENARIO I
 * BACKTESTING BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Legal-XI Backtesting Boundary<br>";
echo "============================================<br>";


startingXISelectionIntegrationTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Real integration path does not manufacture an accuracy score.'
);


startingXISelectionIntegrationTestResult(
    !array_key_exists(
        'automatic_substitutions',
        $result
    ),
    'Real integration path does not simulate automatic substitutions.'
);


startingXISelectionIntegrationTestResult(
    !array_key_exists(
        'captain_result',
        $result
    ),
    'Real integration path does not evaluate captain recommendation.'
);


startingXISelectionIntegrationTestResult(
    !array_key_exists(
        'transfer_result',
        $result
    ),
    'Real integration path does not evaluate transfer recommendation.'
);


startingXISelectionIntegrationTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Real integration path does not manufacture overall backtesting score.'
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "TEST SUMMARY<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if (
    $failed === 0
) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}