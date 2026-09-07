<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Captain Backtesting Service Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function captainBacktestingIntegrationTestResult(
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


captainBacktestingIntegrationTestResult(
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


$captainBacktestingService =
    new CaptainBacktestingService();


captainBacktestingIntegrationTestResult(
    $playerGameweekOutcomeService
        instanceof PlayerGameweekOutcomeService,
    'Real player gameweek outcome service can be constructed.'
);


captainBacktestingIntegrationTestResult(
    $captainBacktestingService
        instanceof CaptainBacktestingService,
    'Real captain backtesting service can be constructed.'
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


captainBacktestingIntegrationTestResult(
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
 * LOAD GENUINE COMPLETED-GAMEWEEK OUTCOMES
 * ============================================================
 */

$playerOutcomes =
    $playerGameweekOutcomeService
        ->getByGameweekId(
            $gameweekId
        );


captainBacktestingIntegrationTestResult(
    count(
        $playerOutcomes
    )
    >= 15,
    'Completed gameweek contains enough genuine outcomes for a full captain candidate universe.'
);


/*
 * ============================================================
 * BUILD GENUINE OUTCOME LOOKUP
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
 * SELECT GENUINE PLAYER OUTCOMES
 * ============================================================
 *
 * These player identities and realised outcomes are genuine.
 *
 * The historical Captain Intelligence recommendation itself is
 * deliberately synthetic because we are testing the integration
 * between:
 *
 * - preserved-style Captain Intelligence evidence
 * - real completed-gameweek outcome evidence
 * - CaptainBacktestingService
 *
 * We are not claiming that FPL Intelligence genuinely ranked
 * these exact players in this historical gameweek.
 * ============================================================
 */

$selectedOutcomes =
    array_slice(
        array_values(
            $outcomesByPlayerId
        ),
        0,
        15
    );


if (
    count(
        $selectedOutcomes
    )
    !== 15
) {

    captainBacktestingIntegrationTestResult(
        false,
        'Exactly fifteen genuine player outcomes can be selected.'
    );


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
 * BUILD PRESERVED-STYLE CAPTAIN INTELLIGENCE RANKINGS
 * ============================================================
 *
 * Ranking metadata is synthetic.
 *
 * Player identities are genuine.
 *
 * The evaluator does not recalculate or use Captain Score when
 * determining realised performance. Captain Score is included
 * only so the evidence resembles the production structure that
 * RecommendationSnapshot preserves.
 * ============================================================
 */

$captainRankings =
    [];


$rank =
    1;


foreach (
    $selectedOutcomes
    as $outcome
) {

    $captainRankings[] = [

        'player_id' =>
            (int) $outcome[
                'player_id'
            ],

        'name' =>
            'Integration Player '
            . (int) $outcome[
                'player_id'
            ],

        'rank' =>
            $rank,

        'captain_score' =>
            100.0
            -
            $rank
    ];


    $rank++;
}


captainBacktestingIntegrationTestResult(
    count(
        $captainRankings
    )
    === 15,
    'Preserved-style Captain Intelligence universe contains fifteen genuine player identities.'
);


/*
 * ============================================================
 * CHOOSE PRESERVED RECOMMENDED CAPTAIN
 * ============================================================
 *
 * The first ranking represents the historical recommendation.
 * ============================================================
 */

$captain =
    $captainRankings[
        0
    ];


$captainPlayerId =
    $captain[
        'player_id'
    ];


/*
 * ============================================================
 * PRESERVE SOURCE EVIDENCE
 * ============================================================
 */

$originalCaptain =
    $captain;


$originalCaptainRankings =
    $captainRankings;


$originalPlayerOutcomes =
    $playerOutcomes;


/*
 * ============================================================
 * SCENARIO A
 * REAL CAPTAIN EVALUATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Real Captain Evaluation<br>";
echo "============================================<br>";


$result =
    $captainBacktestingService
        ->evaluate(
            $captain,
            $captainRankings,
            $playerOutcomes
        );


captainBacktestingIntegrationTestResult(
    !empty(
        $result
    ),
    'Genuine completed-gameweek outcomes produce captain evaluation.'
);


captainBacktestingIntegrationTestResult(
    (
        $result[
            'captain_player_id'
        ]
        ?? null
    )
    ===
    $captainPlayerId,
    'Preserved recommended captain identity is retained.'
);


/*
 * ============================================================
 * SCENARIO B
 * GENUINE CAPTAIN OUTCOME
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Genuine Captain Outcome<br>";
echo "============================================<br>";


$expectedCaptainOutcome =
    $outcomesByPlayerId[
        $captainPlayerId
    ];


captainBacktestingIntegrationTestResult(
    (
        $result[
            'captain_actual_points'
        ]
        ?? null
    )
    ===
    $expectedCaptainOutcome[
        'total_points'
    ],
    'Captain realised points exactly match authoritative completed-gameweek evidence.'
);


$expectedCaptainMinutes =
    is_numeric(
        $expectedCaptainOutcome[
            'minutes'
        ]
        ?? null
    )
        ? $expectedCaptainOutcome[
            'minutes'
        ]
        : null;


captainBacktestingIntegrationTestResult(
    (
        $result[
            'captain_actual_minutes'
        ]
        ?? null
    )
    ===
    $expectedCaptainMinutes,
    'Captain realised minutes exactly match authoritative completed-gameweek evidence.'
);


/*
 * ============================================================
 * SCENARIO C
 * GENUINE BEST CAPTAIN-INTELLIGENCE ALTERNATIVE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Genuine Best Captain Intelligence Alternative<br>";
echo "============================================<br>";


$expectedBestAlternativePlayerId =
    null;


$expectedBestAlternativePoints =
    null;


foreach (
    $captainRankings
    as $ranking
) {

    $playerId =
        (int) $ranking[
            'player_id'
        ];


    if (
        $playerId
        ===
        $captainPlayerId
    ) {

        continue;
    }


    $actualPoints =
        $outcomesByPlayerId[
            $playerId
        ][
            'total_points'
        ];


    if (
        $expectedBestAlternativePoints === null
        ||
        $actualPoints
        >
        $expectedBestAlternativePoints
    ) {

        $expectedBestAlternativePlayerId =
            $playerId;


        $expectedBestAlternativePoints =
            $actualPoints;
    }
}


captainBacktestingIntegrationTestResult(
    (
        $result[
            'best_alternative_player_id'
        ]
        ?? null
    )
    ===
    $expectedBestAlternativePlayerId,
    'Best captain alternative is correctly selected from preserved Captain Intelligence rankings.'
);


captainBacktestingIntegrationTestResult(
    (
        $result[
            'best_alternative_actual_points'
        ]
        ?? null
    )
    ===
    $expectedBestAlternativePoints,
    'Best Captain Intelligence alternative retains genuine realised points.'
);


/*
 * ============================================================
 * SCENARIO D
 * GENUINE CAPTAIN POINTS LOST
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Genuine Captain Points Lost<br>";
echo "============================================<br>";


$expectedCaptainPointsLost =
    $expectedBestAlternativePoints
    -
    $expectedCaptainOutcome[
        'total_points'
    ];


if (
    $expectedCaptainPointsLost < 0
) {

    $expectedCaptainPointsLost =
        0;
}


captainBacktestingIntegrationTestResult(
    (
        $result[
            'captain_points_lost'
        ]
        ?? null
    )
    ===
    $expectedCaptainPointsLost,
    'Captain points lost is derived exactly from genuine completed-gameweek returns.'
);


captainBacktestingIntegrationTestResult(
    (
        $result[
            'captain_points_lost'
        ]
        ?? -1
    )
    >= 0,
    'Real captain points lost is never negative.'
);


/*
 * ============================================================
 * SCENARIO E
 * PRESERVED CAPTAIN-INTELLIGENCE UNIVERSE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Preserved Captain Intelligence Universe<br>";
echo "============================================<br>";


$captainRankingPlayerIds =
    array_map(
        static function (
            array $ranking
        ): int {

            return
                (int) $ranking[
                    'player_id'
                ];
        },
        $captainRankings
    );


captainBacktestingIntegrationTestResult(
    in_array(
        $result[
            'best_alternative_player_id'
        ]
        ?? null,
        $captainRankingPlayerIds,
        true
    ),
    'Best realised alternative belongs to the preserved Captain Intelligence universe.'
);


captainBacktestingIntegrationTestResult(
    (
        $result[
            'best_alternative_player_id'
        ]
        ?? null
    )
    !==
    $captainPlayerId,
    'Recommended captain is excluded from the alternative pool.'
);


/*
 * ============================================================
 * SCENARIO F
 * PARTIAL PRESERVED RANKING UNIVERSE
 * ============================================================
 *
 * Captain Intelligence may preserve fewer than fifteen successful
 * rankings when individual players were rejected.
 *
 * Historical backtesting must evaluate the candidate universe
 * that was genuinely preserved rather than requiring fifteen.
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Partial Preserved Ranking Universe<br>";
echo "============================================<br>";


$partialCaptainRankings =
    array_slice(
        $captainRankings,
        0,
        5
    );


$partialResult =
    $captainBacktestingService
        ->evaluate(
            $captain,
            $partialCaptainRankings,
            $playerOutcomes
        );


captainBacktestingIntegrationTestResult(
    !empty(
        $partialResult
    ),
    'Real integration path accepts a preserved Captain Intelligence universe smaller than fifteen.'
);


$expectedPartialBestPlayerId =
    null;


$expectedPartialBestPoints =
    null;


foreach (
    $partialCaptainRankings
    as $ranking
) {

    $playerId =
        (int) $ranking[
            'player_id'
        ];


    if (
        $playerId
        ===
        $captainPlayerId
    ) {

        continue;
    }


    $actualPoints =
        $outcomesByPlayerId[
            $playerId
        ][
            'total_points'
        ];


    if (
        $expectedPartialBestPoints === null
        ||
        $actualPoints
        >
        $expectedPartialBestPoints
    ) {

        $expectedPartialBestPlayerId =
            $playerId;


        $expectedPartialBestPoints =
            $actualPoints;
    }
}


captainBacktestingIntegrationTestResult(
    (
        $partialResult[
            'best_alternative_player_id'
        ]
        ?? null
    )
    ===
    $expectedPartialBestPlayerId,
    'Partial preserved rankings identify the strongest realised candidate actually preserved.'
);


/*
 * ============================================================
 * SCENARIO G
 * SOURCE IMMUTABILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


captainBacktestingIntegrationTestResult(
    $captain
        ===
        $originalCaptain,
    'Preserved captain evidence remains unchanged.'
);


captainBacktestingIntegrationTestResult(
    $captainRankings
        ===
        $originalCaptainRankings,
    'Preserved Captain Intelligence rankings remain unchanged.'
);


captainBacktestingIntegrationTestResult(
    $playerOutcomes
        ===
        $originalPlayerOutcomes,
    'Authoritative completed-gameweek outcomes remain unchanged.'
);


/*
 * ============================================================
 * SCENARIO H
 * CAPTAIN BACKTESTING BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Captain Backtesting Boundary<br>";
echo "============================================<br>";


captainBacktestingIntegrationTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Real integration path does not manufacture an accuracy score.'
);


captainBacktestingIntegrationTestResult(
    !array_key_exists(
        'doubled_captain_points',
        $result
    ),
    'Real integration path does not manufacture doubled captain points.'
);


captainBacktestingIntegrationTestResult(
    !array_key_exists(
        'vice_captain_result',
        $result
    ),
    'Real integration path does not yet simulate vice-captain fallback.'
);


captainBacktestingIntegrationTestResult(
    !array_key_exists(
        'transfer_result',
        $result
    ),
    'Real integration path does not evaluate transfers.'
);


captainBacktestingIntegrationTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Real integration path does not manufacture an overall backtesting score.'
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