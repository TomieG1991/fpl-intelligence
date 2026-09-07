<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Backtesting Service Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function transferBacktestingIntegrationTestResult(
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


transferBacktestingIntegrationTestResult(
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


$transferBacktestingService =
    new TransferBacktestingService();


transferBacktestingIntegrationTestResult(
    $playerGameweekOutcomeService
        instanceof PlayerGameweekOutcomeService,
    'Real player gameweek outcome service can be constructed.'
);


transferBacktestingIntegrationTestResult(
    $transferBacktestingService
        instanceof TransferBacktestingService,
    'Real transfer backtesting service can be constructed.'
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


transferBacktestingIntegrationTestResult(
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


transferBacktestingIntegrationTestResult(
    count(
        $playerOutcomes
    )
    >= 4,
    'Completed gameweek contains enough genuine outcomes for transfer backtesting.'
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
 * Player identities and realised outcomes are genuine.
 *
 * Transfer recommendation metadata is deliberately synthetic.
 *
 * We are testing the integration between:
 *
 * - preserved-style Transfer Intelligence evidence
 * - real completed-gameweek outcome evidence
 * - TransferBacktestingService
 *
 * We are not claiming that FPL Intelligence genuinely recommended
 * these exact transfers in this historical gameweek.
 * ============================================================
 */

$selectedOutcomes =
    array_slice(
        array_values(
            $outcomesByPlayerId
        ),
        0,
        4
    );


if (
    count(
        $selectedOutcomes
    )
    !== 4
) {

    transferBacktestingIntegrationTestResult(
        false,
        'Exactly four genuine player outcomes can be selected.'
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


$outgoingOutcome =
    $selectedOutcomes[
        0
    ];


$incomingOutcome =
    $selectedOutcomes[
        1
    ];


$secondReplacementOutcome =
    $selectedOutcomes[
        2
    ];


$secondOutgoingOutcome =
    $selectedOutcomes[
        3
    ];


$outgoingPlayerId =
    (int) $outgoingOutcome[
        'player_id'
    ];


$incomingPlayerId =
    (int) $incomingOutcome[
        'player_id'
    ];


$secondReplacementPlayerId =
    (int) $secondReplacementOutcome[
        'player_id'
    ];


$secondOutgoingPlayerId =
    (int) $secondOutgoingOutcome[
        'player_id'
    ];


/*
 * ============================================================
 * BUILD PRESERVED-STYLE TRANSFER INTELLIGENCE
 * ============================================================
 *
 * The shape mirrors the production single-transfer path:
 *
 * transfer_recommendations
 *     -> recommendations
 *         -> recommendations
 *             -> outgoing
 *             -> replacements
 *
 * Array order is recommendation-time ranking.
 * ============================================================
 */

$transferRecommendations =
    [
        'analysis' =>
            [
                'validation' =>
                    [
                        'is_valid' =>
                            true
                    ],

                'bank' =>
                    1.5
            ],

        'recommendations' =>
            [
                'status' =>
                    'success',

                'bank' =>
                    1.5,

                'priority_limit' =>
                    5,

                'replacement_limit' =>
                    5,

                'players_considered' =>
                    2,

                'recommendations' =>
                    [
                        [
                            'outgoing' =>
                                [
                                    'player_id' =>
                                        $outgoingPlayerId,

                                    'name' =>
                                        'Integration Outgoing '
                                        . $outgoingPlayerId,

                                    'position' =>
                                        'MID',

                                    'price' =>
                                        7.5
                                ],

                            'transfer_priority' =>
                                78.0,

                            'priority_label' =>
                                'High',

                            'available_budget' =>
                                9.0,

                            'legal_candidate_count' =>
                                2,

                            'replacement_count' =>
                                2,

                            'replacements' =>
                                [
                                    [
                                        'player' =>
                                            [
                                                'player_id' =>
                                                    $incomingPlayerId,

                                                'name' =>
                                                    'Integration Incoming '
                                                    . $incomingPlayerId,

                                                'position' =>
                                                    'MID',

                                                'price' =>
                                                    8.0
                                            ],

                                        'decision' =>
                                            [
                                                'decision_score' =>
                                                    76.0,

                                                'decision_type' =>
                                                    'Upgrade'
                                            ],

                                        'decision_score' =>
                                            76.0,

                                        'decision_type' =>
                                            'Upgrade',

                                        'budget_after' =>
                                            1.0,

                                        'rank' =>
                                            1
                                    ],

                                    [
                                        'player' =>
                                            [
                                                'player_id' =>
                                                    $secondReplacementPlayerId,

                                                'name' =>
                                                    'Integration Alternative '
                                                    . $secondReplacementPlayerId,

                                                'position' =>
                                                    'MID',

                                                'price' =>
                                                    7.8
                                            ],

                                        'decision' =>
                                            [
                                                'decision_score' =>
                                                    72.0,

                                                'decision_type' =>
                                                    'Upgrade'
                                            ],

                                        'decision_score' =>
                                            72.0,

                                        'decision_type' =>
                                            'Upgrade',

                                        'budget_after' =>
                                            1.2,

                                        'rank' =>
                                            2
                                    ]
                                ]
                        ],

                        [
                            'outgoing' =>
                                [
                                    'player_id' =>
                                        $secondOutgoingPlayerId,

                                    'name' =>
                                        'Integration Second Outgoing '
                                        . $secondOutgoingPlayerId,

                                    'position' =>
                                        'DEF',

                                    'price' =>
                                        5.0
                                ],

                            'transfer_priority' =>
                                65.0,

                            'priority_label' =>
                                'Medium',

                            'available_budget' =>
                                6.5,

                            'legal_candidate_count' =>
                                1,

                            'replacement_count' =>
                                1,

                            'replacements' =>
                                [
                                    [
                                        'player' =>
                                            [
                                                'player_id' =>
                                                    $secondReplacementPlayerId,

                                                'name' =>
                                                    'Integration Other Replacement '
                                                    . $secondReplacementPlayerId,

                                                'position' =>
                                                    'DEF',

                                                'price' =>
                                                    5.5
                                            ],

                                        'decision_score' =>
                                            68.0,

                                        'decision_type' =>
                                            'Upgrade',

                                        'budget_after' =>
                                            1.0,

                                        'rank' =>
                                            1
                                    ]
                                ]
                        ]
                    ]
            ]
    ];


/*
 * ============================================================
 * PRESERVE SOURCE EVIDENCE
 * ============================================================
 */

$originalTransferRecommendations =
    $transferRecommendations;


$originalPlayerOutcomes =
    $playerOutcomes;


/*
 * ============================================================
 * SCENARIO A
 * REAL TRANSFER EVALUATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Real Transfer Evaluation<br>";
echo "============================================<br>";


$result =
    $transferBacktestingService
        ->evaluate(
            $transferRecommendations,
            $playerOutcomes
        );


transferBacktestingIntegrationTestResult(
    !empty(
        $result
    ),
    'Genuine completed-gameweek outcomes produce transfer evaluation.'
);


transferBacktestingIntegrationTestResult(
    (
        $result[
            'outgoing_player_id'
        ]
        ?? null
    )
    ===
    $outgoingPlayerId,
    'Preserved highest-priority outgoing player identity is retained.'
);


transferBacktestingIntegrationTestResult(
    (
        $result[
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    $incomingPlayerId,
    'Preserved rank-one replacement identity is retained.'
);


/*
 * ============================================================
 * SCENARIO B
 * GENUINE OUTGOING OUTCOME
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Genuine Outgoing Outcome<br>";
echo "============================================<br>";


transferBacktestingIntegrationTestResult(
    (
        $result[
            'outgoing_actual_points'
        ]
        ?? null
    )
    ===
    $outgoingOutcome[
        'total_points'
    ],
    'Outgoing realised points exactly match authoritative completed-gameweek evidence.'
);


/*
 * ============================================================
 * SCENARIO C
 * GENUINE INCOMING OUTCOME
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Genuine Incoming Outcome<br>";
echo "============================================<br>";


transferBacktestingIntegrationTestResult(
    (
        $result[
            'incoming_actual_points'
        ]
        ?? null
    )
    ===
    $incomingOutcome[
        'total_points'
    ],
    'Incoming realised points exactly match authoritative completed-gameweek evidence.'
);


/*
 * ============================================================
 * SCENARIO D
 * GENUINE TRANSFER POINTS GAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Genuine Transfer Points Gain<br>";
echo "============================================<br>";


$expectedTransferPointsGain =
    $incomingOutcome[
        'total_points'
    ]
    -
    $outgoingOutcome[
        'total_points'
    ];


transferBacktestingIntegrationTestResult(
    (
        $result[
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    $expectedTransferPointsGain,
    'Transfer points gain is derived exactly from genuine completed-gameweek returns.'
);


/*
 * ============================================================
 * SCENARIO E
 * PRESERVED REPLACEMENT RANKING
 * ============================================================
 *
 * The service must evaluate the player preserved at replacement
 * index zero regardless of whether another preserved replacement
 * happened to score more points.
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Preserved Replacement Ranking<br>";
echo "============================================<br>";


transferBacktestingIntegrationTestResult(
    (
        $result[
            'incoming_player_id'
        ]
        ?? null
    )
    ===
    $incomingPlayerId,
    'Real integration path preserves recommendation-time replacement ordering.'
);


transferBacktestingIntegrationTestResult(
    (
        $result[
            'incoming_player_id'
        ]
        ?? null
    )
    !==
    $secondReplacementPlayerId,
    'Real integration path does not retrospectively select another preserved replacement.'
);


/*
 * ============================================================
 * SCENARIO F
 * PRESERVED OUTGOING PRIORITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Preserved Outgoing Priority<br>";
echo "============================================<br>";


transferBacktestingIntegrationTestResult(
    (
        $result[
            'outgoing_player_id'
        ]
        ?? null
    )
    ===
    $outgoingPlayerId,
    'Real integration path evaluates the first preserved outgoing recommendation group.'
);


transferBacktestingIntegrationTestResult(
    (
        $result[
            'outgoing_player_id'
        ]
        ?? null
    )
    !==
    $secondOutgoingPlayerId,
    'Real integration path does not retrospectively select another outgoing recommendation group.'
);


/*
 * ============================================================
 * SCENARIO G
 * SIGNED REALISED DIFFERENCE
 * ============================================================
 *
 * Genuine database values may happen to produce positive, zero,
 * or negative transfer gain.
 *
 * We verify the service preserves the exact arithmetic result
 * rather than applying a floor or classification.
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Signed Realised Difference<br>";
echo "============================================<br>";


transferBacktestingIntegrationTestResult(
    (
        $result[
            'transfer_points_gain'
        ]
        ?? null
    )
    ===
    (
        $result[
            'incoming_actual_points'
        ]
        -
        $result[
            'outgoing_actual_points'
        ]
    ),
    'Real transfer points gain remains the signed arithmetic difference.'
);


/*
 * ============================================================
 * SCENARIO H
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


transferBacktestingIntegrationTestResult(
    $transferRecommendations
    ===
    $originalTransferRecommendations,
    'Preserved-style Transfer Intelligence evidence remains unchanged.'
);


transferBacktestingIntegrationTestResult(
    $playerOutcomes
    ===
    $originalPlayerOutcomes,
    'Real completed-gameweek outcome evidence remains unchanged.'
);


/*
 * ============================================================
 * SCENARIO I
 * BACKTESTING SCOPE BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Backtesting Scope Boundary<br>";
echo "============================================<br>";


transferBacktestingIntegrationTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Real transfer backtesting does not manufacture an accuracy score.'
);


transferBacktestingIntegrationTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Real transfer backtesting does not manufacture an overall score.'
);


transferBacktestingIntegrationTestResult(
    !array_key_exists(
        'transfer_hit',
        $result
    ),
    'Real transfer backtesting does not manufacture a transfer hit.'
);


transferBacktestingIntegrationTestResult(
    !array_key_exists(
        'net_points_gain',
        $result
    ),
    'Real transfer backtesting does not manufacture hit-adjusted net points.'
);


transferBacktestingIntegrationTestResult(
    !array_key_exists(
        'decision_correct',
        $result
    ),
    'Real transfer candidate backtesting does not judge Make / Consider / Hold.'
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