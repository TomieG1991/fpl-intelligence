<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Captain Backtesting Service Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekCaptainBacktestingIntegrationTestResult(
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
 * REAL PRODUCTION SERVICES
 * ============================================================
 */

$captainBacktestingService =
    new CaptainBacktestingService();


$gameweekCaptainBacktestingService =
    new GameweekCaptainBacktestingService(
        $captainBacktestingService
    );


gameweekCaptainBacktestingIntegrationTestResult(
    $captainBacktestingService
        instanceof CaptainBacktestingService,
    'Real CaptainBacktestingService can be constructed.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    $gameweekCaptainBacktestingService
        instanceof GameweekCaptainBacktestingService,
    'Real GameweekCaptainBacktestingService can be constructed.'
);


/*
 * ============================================================
 * PRESERVED CAPTAIN INTELLIGENCE EVIDENCE
 * ============================================================
 *
 * This structure mirrors the captain_recommendation evidence
 * preserved inside RecommendationSnapshot.
 *
 * The rankings represent the historical candidate universe that
 * Captain Intelligence genuinely knew about at recommendation
 * time.
 *
 * No Starting XI is supplied to captain backtesting because
 * Captain Intelligence owns its own candidate universe.
 * ============================================================
 */

$captain =
    [
        'player_id' => 108,
        'name' => 'Player 108',
        'rank' => 1,
        'captain_score' => 90.0
    ];


$viceCaptain =
    [
        'player_id' => 105,
        'name' => 'Player 105',
        'rank' => 2,
        'captain_score' => 88.0
    ];


$captainRankings =
    [
        $captain,
        $viceCaptain,
        [
            'player_id' => 109,
            'name' => 'Player 109',
            'rank' => 3,
            'captain_score' => 84.0
        ],
        [
            'player_id' => 106,
            'name' => 'Player 106',
            'rank' => 4,
            'captain_score' => 82.0
        ],
        [
            'player_id' => 103,
            'name' => 'Player 103',
            'rank' => 5,
            'captain_score' => 80.0
        ],
        [
            'player_id' => 114,
            'name' => 'Player 114',
            'rank' => 6,
            'captain_score' => 70.0
        ]
    ];


$captainRecommendation =
    [
        'status' => 'success',
        'message' =>
            'Captain Intelligence recommendations generated successfully.',
        'captain' =>
            $captain,
        'vice_captain' =>
            $viceCaptain,
        'alternatives' =>
            array_slice(
                $captainRankings,
                2
            ),
        'rankings' =>
            $captainRankings,
        'squad_count' =>
            15,
        'evaluated_count' =>
            6,
        'rejected_count' =>
            9,
        'rejected_players' =>
            [],
        'recommendation_limit' =>
            5
    ];


/*
 * ============================================================
 * AUTHORITATIVE COMPLETED-GAMEWEEK OUTCOMES
 * ============================================================
 *
 * Player 114 deliberately records the strongest realised return.
 *
 * The preserved captain remains Player 108.
 * ============================================================
 */

$playerOutcomes =
    [
        [
            'gameweek_id' => 5,
            'player_id' => 108,
            'fixture_count' => 1,
            'total_points' => 8,
            'minutes' => 90,
            'starts' => 1,
            'goals' => 0,
            'assists' => 1,
            'clean_sheets' => 0,
            'bonus' => 1
        ],
        [
            'gameweek_id' => 5,
            'player_id' => 105,
            'fixture_count' => 1,
            'total_points' => 12,
            'minutes' => 90,
            'starts' => 1,
            'goals' => 1,
            'assists' => 1,
            'clean_sheets' => 0,
            'bonus' => 2
        ],
        [
            'gameweek_id' => 5,
            'player_id' => 109,
            'fixture_count' => 1,
            'total_points' => 7,
            'minutes' => 90,
            'starts' => 1,
            'goals' => 0,
            'assists' => 1,
            'clean_sheets' => 0,
            'bonus' => 0
        ],
        [
            'gameweek_id' => 5,
            'player_id' => 106,
            'fixture_count' => 1,
            'total_points' => 6,
            'minutes' => 90,
            'starts' => 1,
            'goals' => 0,
            'assists' => 0,
            'clean_sheets' => 1,
            'bonus' => 0
        ],
        [
            'gameweek_id' => 5,
            'player_id' => 103,
            'fixture_count' => 1,
            'total_points' => 5,
            'minutes' => 90,
            'starts' => 1,
            'goals' => 0,
            'assists' => 0,
            'clean_sheets' => 0,
            'bonus' => 0
        ],
        [
            'gameweek_id' => 5,
            'player_id' => 114,
            'fixture_count' => 1,
            'total_points' => 15,
            'minutes' => 90,
            'starts' => 1,
            'goals' => 2,
            'assists' => 0,
            'clean_sheets' => 0,
            'bonus' => 3
        ]
    ];


/*
 * ============================================================
 * READY HISTORICAL EVIDENCE
 * ============================================================
 *
 * This represents the contract already assembled by
 * GameweekBacktestingEvidenceService.
 *
 * Additional snapshot sections are included to demonstrate that
 * captain orchestration reads only the captain evidence it owns.
 * ============================================================
 */

$evidence =
    [
        'status' =>
            'Ready',

        'reason' =>
            null,

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            5,

        'gameweek' =>
            [
                'id' => 5,
                'fpl_gameweek_id' => 1,
                'name' => 'Gameweek 1',
                'finished' => 1,
                'data_checked' => 1
            ],

        'recommendation_snapshot' =>
            [
                'gameweek' => 1,
                'entry_id' => 2702264,
                'captured_at' => '2026-08-21 16:30:00',
                'deadline_time' => '2026-08-21 17:30:00',

                'player_projections' =>
                    [
                        [
                            'player_id' => 108,
                            'projected_points' => 8.5
                        ]
                    ],

                'starting_xi' =>
                    [
                        [
                            'player_id' => 108
                        ]
                    ],

                'captain_recommendation' =>
                    $captainRecommendation,

                'transfer_recommendations' =>
                    [
                        'status' => 'success'
                    ],

                'gameweek_decision' =>
                    [
                        'status' => 'success'
                    ],

                'chip_recommendations' =>
                    []
            ],

        'player_outcomes' =>
            $playerOutcomes
    ];


/*
 * ============================================================
 * PRESERVE SOURCE EVIDENCE
 * ============================================================
 */

$originalEvidence =
    $evidence;


$originalCaptainRecommendation =
    $captainRecommendation;


$originalPlayerOutcomes =
    $playerOutcomes;


/*
 * ============================================================
 * SCENARIO A
 * REAL SERVICE CHAIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Real Service Chain<br>";
echo "============================================<br>";


$result =
    $gameweekCaptainBacktestingService
        ->evaluate(
            $evidence
        );


gameweekCaptainBacktestingIntegrationTestResult(
    !empty(
        $result
    ),
    'Real gameweek captain service chain produces an evaluation.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Ready',
    'Real service chain returns Ready captain backtesting status.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    array_key_exists(
        'reason',
        $result
    )
    &&
    $result[
        'reason'
    ]
    ===
    null,
    'Ready real-service captain evaluation has no failure reason.'
);


/*
 * ============================================================
 * SCENARIO B
 * HISTORICAL IDENTITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Historical Identity<br>";
echo "============================================<br>";


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    ===
    2702264,
    'Historical entry identity is preserved through the real service chain.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'Historical local gameweek identity is preserved through the real service chain.'
);


/*
 * ============================================================
 * SCENARIO C
 * PRESERVED CAPTAIN IS EVALUATED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Preserved Captain Evaluation<br>";
echo "============================================<br>";


$captainEvaluation =
    $result[
        'captain_evaluation'
    ]
    ?? [];


gameweekCaptainBacktestingIntegrationTestResult(
    !empty(
        $captainEvaluation
    ),
    'Real service chain exposes captain evaluation.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $captainEvaluation[
            'captain_player_id'
        ]
        ?? null
    )
    ===
    108,
    'Preserved recommended captain identity reaches the real CaptainBacktestingService.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $captainEvaluation[
            'captain_actual_points'
        ]
        ?? null
    )
    ===
    8,
    'Preserved captain is matched to authoritative realised points.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $captainEvaluation[
            'captain_actual_minutes'
        ]
        ?? null
    )
    ===
    90,
    'Preserved captain is matched to authoritative realised minutes.'
);


/*
 * ============================================================
 * SCENARIO D
 * PRESERVED CAPTAIN-INTELLIGENCE UNIVERSE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Preserved Captain Intelligence Universe<br>";
echo "============================================<br>";


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $captainEvaluation[
            'best_alternative_player_id'
        ]
        ?? null
    )
    ===
    114,
    'Real service chain selects the strongest realised alternative from preserved Captain Intelligence rankings.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $captainEvaluation[
            'best_alternative_actual_points'
        ]
        ?? null
    )
    ===
    15,
    'Strongest preserved Captain Intelligence alternative retains authoritative realised points.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $captainEvaluation[
            'captain_points_lost'
        ]
        ?? null
    )
    ===
    7,
    'Real service chain calculates captain points lost from preserved recommendation evidence and realised outcomes.'
);


/*
 * ============================================================
 * SCENARIO E
 * NO STARTING-XI DEPENDENCY
 * ============================================================
 *
 * The snapshot deliberately contains only one synthetic
 * starting_xi row.
 *
 * Captain Backtesting must still succeed because its comparison
 * universe is the preserved Captain Intelligence rankings, not
 * Starting XI selection.
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: No Starting-XI Dependency<br>";
echo "============================================<br>";


gameweekCaptainBacktestingIntegrationTestResult(
    count(
        $evidence[
            'recommendation_snapshot'
        ][
            'starting_xi'
        ]
    )
    ===
    1,
    'Synthetic snapshot deliberately does not provide a complete Starting XI.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Ready',
    'Captain backtesting remains Ready without using Starting XI as its candidate universe.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $captainEvaluation[
            'best_alternative_player_id'
        ]
        ?? null
    )
    ===
    114,
    'Captain alternative selection remains driven by preserved Captain Intelligence rankings.'
);


/*
 * ============================================================
 * SCENARIO F
 * CURRENT RANKING SCORE DOES NOT DETERMINE REALISED WINNER
 * ============================================================
 *
 * Player 114 has the weakest synthetic captain_score in this
 * preserved ranking set but the strongest realised return.
 *
 * Backtesting must compare realised evidence rather than
 * recalculating or reusing the recommendation score as outcome.
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Realised Evidence Determines Winner<br>";
echo "============================================<br>";


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $captainRankings[
            5
        ][
            'captain_score'
        ]
        ?? null
    )
    ===
    70.0,
    'Best realised alternative deliberately has the weakest preserved synthetic captain score.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    (
        $captainEvaluation[
            'best_alternative_player_id'
        ]
        ?? null
    )
    ===
    114,
    'Backtesting uses realised outcomes rather than preserved Captain Intelligence score to identify the realised winner.'
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


gameweekCaptainBacktestingIntegrationTestResult(
    $evidence
    ===
    $originalEvidence,
    'Assembled historical evidence remains unchanged after real-service evaluation.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    $captainRecommendation
    ===
    $originalCaptainRecommendation,
    'Preserved captain recommendation remains unchanged after real-service evaluation.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    $playerOutcomes
    ===
    $originalPlayerOutcomes,
    'Authoritative player outcomes remain unchanged after real-service evaluation.'
);


/*
 * ============================================================
 * SCENARIO H
 * BACKTESTING BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Backtesting Boundary<br>";
echo "============================================<br>";


gameweekCaptainBacktestingIntegrationTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Real gameweek captain path does not manufacture an accuracy score.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Real gameweek captain path does not manufacture an overall score.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    !array_key_exists(
        'doubled_captain_points',
        $captainEvaluation
    ),
    'Real captain evaluation does not manufacture doubled captain points.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    !array_key_exists(
        'vice_captain_result',
        $result
    )
    &&
    !array_key_exists(
        'vice_captain_result',
        $captainEvaluation
    ),
    'Real gameweek captain path does not yet simulate vice-captain fallback.'
);


gameweekCaptainBacktestingIntegrationTestResult(
    !array_key_exists(
        'transfer_result',
        $result
    ),
    'Real gameweek captain path does not evaluate transfer recommendations.'
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