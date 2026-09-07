<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Player Ranking Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekPlayerRankingBacktestingTestResult(
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
 * SERVICE AVAILABILITY
 * ============================================================
 */

gameweekPlayerRankingBacktestingTestResult(
    class_exists(
        'GameweekPlayerRankingBacktestingService'
    ),
    'GameweekPlayerRankingBacktestingService exists.'
);


if (
    !class_exists(
        'GameweekPlayerRankingBacktestingService'
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


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class GameweekPlayerRankingBacktestingPlayerServiceSpy
{
    public int $callCount = 0;

    public array $receivedRankings = [];

    public array $receivedOutcomes = [];

    public array $returnValue = [];


    public function evaluate(
        array $playerRankings,
        array $playerOutcomes
    ): array {

        $this->callCount++;

        $this->receivedRankings =
            $playerRankings;

        $this->receivedOutcomes =
            $playerOutcomes;


        return
            $this->returnValue;
    }
}


class GameweekPlayerRankingBacktestingMetricsServiceSpy
{
    public int $callCount = 0;

    public array $receivedEvaluations = [];

    public array $returnValue = [];


    public function calculate(
        array $playerEvaluations
    ): array {

        $this->callCount++;

        $this->receivedEvaluations =
            $playerEvaluations;


        return
            $this->returnValue;
    }
}


function createGameweekPlayerRankingBacktestingService(
    ?GameweekPlayerRankingBacktestingPlayerServiceSpy &$playerService = null,
    ?GameweekPlayerRankingBacktestingMetricsServiceSpy &$metricsService = null
): GameweekPlayerRankingBacktestingService {

    $playerService =
        new GameweekPlayerRankingBacktestingPlayerServiceSpy();


    $metricsService =
        new GameweekPlayerRankingBacktestingMetricsServiceSpy();


    return
        new GameweekPlayerRankingBacktestingService(
            $playerService,
            $metricsService
        );
}


/*
 * ============================================================
 * CONSTRUCTION
 * ============================================================
 */

$service =
    createGameweekPlayerRankingBacktestingService(
        $playerService,
        $metricsService
    );


gameweekPlayerRankingBacktestingTestResult(
    $service
        instanceof GameweekPlayerRankingBacktestingService,
    'GameweekPlayerRankingBacktestingService can be constructed.'
);


/*
 * ============================================================
 * SCENARIO A
 * NON-READY EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Non-Ready Evidence<br>";
echo "============================================<br>";


$service =
    createGameweekPlayerRankingBacktestingService(
        $playerService,
        $metricsService
    );


$result =
    $service->evaluate(
        [
            'status' => 'Unavailable',
            'reason' => 'Gameweek is not completed.',
            'entry_id' => 2702264,
            'gameweek_id' => 12
        ]
    );


$expectedNonReady = [

    'status' => 'Unavailable',

    'reason' => 'Gameweek is not completed.',

    'entry_id' => 2702264,

    'gameweek_id' => 12,

    'player_evaluations' => [],

    'metrics' => null
];


gameweekPlayerRankingBacktestingTestResult(
    $result === $expectedNonReady,
    'Non-Ready evidence returns the exact unavailable ranking backtesting contract.'
);


gameweekPlayerRankingBacktestingTestResult(
    $playerService->callCount === 0,
    'Non-Ready evidence does not invoke player-level ranking evaluation.'
);


gameweekPlayerRankingBacktestingTestResult(
    $metricsService->callCount === 0,
    'Non-Ready evidence does not invoke ranking metrics calculation.'
);


/*
 * ============================================================
 * SCENARIO B
 * NON-READY EVIDENCE PRESERVES STATUS CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Non-Ready Status Preservation<br>";
echo "============================================<br>";


$service =
    createGameweekPlayerRankingBacktestingService(
        $playerService,
        $metricsService
    );


$result =
    $service->evaluate(
        [
            'status' => 'Pending',
            'reason' => 'Recommendation evidence is not available yet.',
            'entry_id' => 99,
            'gameweek_id' => 4,

            /*
             * Even if irrelevant arrays happen to be present,
             * non-Ready evidence must not be evaluated.
             */
            'recommendation_snapshot' => [
                'player_rankings' => [
                    [
                        'player_id' => 1,
                        'intelligence_score' => 90.0,
                        'rank' => 1
                    ]
                ]
            ],

            'player_outcomes' => [
                [
                    'player_id' => 1,
                    'total_points' => 10
                ]
            ]
        ]
    );


gameweekPlayerRankingBacktestingTestResult(
    $result[
        'status'
    ]
    === 'Pending',
    'Non-Ready status is preserved rather than rewritten.'
);


gameweekPlayerRankingBacktestingTestResult(
    $result[
        'reason'
    ]
    === 'Recommendation evidence is not available yet.',
    'Non-Ready reason is preserved rather than rewritten.'
);


gameweekPlayerRankingBacktestingTestResult(
    $playerService->callCount === 0
    &&
    $metricsService->callCount === 0,
    'Non-Ready evidence is never evaluated even when ranking and outcome arrays are present.'
);


/*
 * ============================================================
 * SCENARIO C
 * READY WITHOUT RECOMMENDATION SNAPSHOT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Ready Without Recommendation Snapshot<br>";
echo "============================================<br>";


$service =
    createGameweekPlayerRankingBacktestingService(
        $playerService,
        $metricsService
    );


$thrown = false;
$message = null;


try {

    $service->evaluate(
        [
            'status' => 'Ready',
            'entry_id' => 2702264,
            'gameweek_id' => 3,

            'player_outcomes' => [
                [
                    'player_id' => 101,
                    'total_points' => 10
                ]
            ]
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $thrown = true;

    $message =
        $exception->getMessage();
}


gameweekPlayerRankingBacktestingTestResult(
    $thrown,
    'Ready evidence without a recommendation snapshot is rejected.'
);


gameweekPlayerRankingBacktestingTestResult(
    $message
    ===
    'Ready backtesting evidence requires a recommendation snapshot.',
    'Missing recommendation snapshot exposes the expected validation message.'
);


gameweekPlayerRankingBacktestingTestResult(
    $playerService->callCount === 0
    &&
    $metricsService->callCount === 0,
    'Invalid Ready evidence does not invoke ranking backtesting dependencies.'
);


/*
 * ============================================================
 * SCENARIO D
 * READY WITHOUT PRESERVED PLAYER RANKINGS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Ready Without Preserved Player Rankings<br>";
echo "============================================<br>";


$service =
    createGameweekPlayerRankingBacktestingService(
        $playerService,
        $metricsService
    );


$thrown = false;
$message = null;


try {

    $service->evaluate(
        [
            'status' => 'Ready',
            'entry_id' => 2702264,
            'gameweek_id' => 3,

            'recommendation_snapshot' => [
                'player_projections' => [
                    [
                        'player_id' => 101
                    ]
                ]
            ],

            'player_outcomes' => [
                [
                    'player_id' => 101,
                    'total_points' => 10
                ]
            ]
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $thrown = true;

    $message =
        $exception->getMessage();
}


gameweekPlayerRankingBacktestingTestResult(
    $thrown,
    'Ready evidence without preserved Player Ranking Evidence is rejected.'
);


gameweekPlayerRankingBacktestingTestResult(
    $message
    ===
    'Ready backtesting evidence requires preserved player rankings.',
    'Missing Player Ranking Evidence exposes the expected validation message.'
);


/*
 * ============================================================
 * SCENARIO E
 * READY WITH EMPTY PLAYER RANKINGS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Ready With Empty Player Rankings<br>";
echo "============================================<br>";


$service =
    createGameweekPlayerRankingBacktestingService(
        $playerService,
        $metricsService
    );


$thrown = false;


try {

    $service->evaluate(
        [
            'status' => 'Ready',
            'entry_id' => 2702264,
            'gameweek_id' => 3,

            'recommendation_snapshot' => [
                'player_rankings' => []
            ],

            'player_outcomes' => [
                [
                    'player_id' => 101,
                    'total_points' => 10
                ]
            ]
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $thrown =
        $exception->getMessage()
        ===
        'Ready backtesting evidence requires preserved player rankings.';
}


gameweekPlayerRankingBacktestingTestResult(
    $thrown,
    'Empty preserved Player Ranking Evidence is rejected rather than treated as historical evidence.'
);


/*
 * ============================================================
 * SCENARIO F
 * READY WITHOUT REALISED OUTCOMES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Ready Without Realised Outcomes<br>";
echo "============================================<br>";


$service =
    createGameweekPlayerRankingBacktestingService(
        $playerService,
        $metricsService
    );


$thrown = false;
$message = null;


try {

    $service->evaluate(
        [
            'status' => 'Ready',
            'entry_id' => 2702264,
            'gameweek_id' => 3,

            'recommendation_snapshot' => [
                'player_rankings' => [
                    [
                        'player_id' => 101,
                        'intelligence_score' => 90.0,
                        'rank' => 1
                    ]
                ]
            ]
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $thrown = true;

    $message =
        $exception->getMessage();
}


gameweekPlayerRankingBacktestingTestResult(
    $thrown,
    'Ready evidence without realised player outcomes is rejected.'
);


gameweekPlayerRankingBacktestingTestResult(
    $message
    ===
    'Ready backtesting evidence requires realised player outcomes.',
    'Missing realised outcomes expose the expected validation message.'
);


/*
 * ============================================================
 * SCENARIO G
 * READY WITH EMPTY REALISED OUTCOMES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Ready With Empty Realised Outcomes<br>";
echo "============================================<br>";


$service =
    createGameweekPlayerRankingBacktestingService(
        $playerService,
        $metricsService
    );


$thrown = false;


try {

    $service->evaluate(
        [
            'status' => 'Ready',
            'entry_id' => 2702264,
            'gameweek_id' => 3,

            'recommendation_snapshot' => [
                'player_rankings' => [
                    [
                        'player_id' => 101,
                        'intelligence_score' => 90.0,
                        'rank' => 1
                    ]
                ]
            ],

            'player_outcomes' => []
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $thrown =
        $exception->getMessage()
        ===
        'Ready backtesting evidence requires realised player outcomes.';
}


gameweekPlayerRankingBacktestingTestResult(
    $thrown,
    'Empty realised player outcomes are rejected rather than treated as completed evidence.'
);


/*
 * ============================================================
 * SCENARIO H
 * READY EVIDENCE DELEGATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Ready Evidence Delegation<br>";
echo "============================================<br>";


$service =
    createGameweekPlayerRankingBacktestingService(
        $playerService,
        $metricsService
    );


$playerRankings = [

    [
        'player_id' => 101,
        'fpl_player_id' => 1001,
        'name' => 'Highest Ranked Player',
        'position' => 'MID',
        'team_id' => 1,
        'price' => 10.0,
        'intelligence_score' => 90.0,
        'rank' => 1
    ],

    [
        'player_id' => 102,
        'fpl_player_id' => 1002,
        'name' => 'Second Ranked Player',
        'position' => 'FWD',
        'team_id' => 2,
        'price' => 9.0,
        'intelligence_score' => 80.0,
        'rank' => 2
    ]
];


$playerOutcomes = [

    [
        'player_id' => 101,
        'total_points' => 12,
        'minutes' => 90
    ],

    [
        'player_id' => 102,
        'total_points' => 7,
        'minutes' => 82
    ]
];


$playerEvaluations = [

    [
        'player_id' => 101,
        'intelligence_score' => 90.0,
        'rank' => 1,
        'actual_points' => 12
    ],

    [
        'player_id' => 102,
        'intelligence_score' => 80.0,
        'rank' => 2,
        'actual_points' => 7
    ]
];


$metrics = [

    'sample_size' => 2,

    'pearson_correlation' => 1.0,

    'spearman_rank_correlation' => 1.0
];


$playerService->returnValue =
    $playerEvaluations;


$metricsService->returnValue =
    $metrics;


$readyEvidence = [

    'status' => 'Ready',

    'reason' => null,

    'entry_id' => 2702264,

    'gameweek_id' => 3,

    'recommendation_snapshot' => [

        'player_rankings' =>
            $playerRankings,

        /*
         * Deliberately unrelated evidence.
         *
         * The ranking orchestrator must use player_rankings,
         * not squad-only player_projections.
         */
        'player_projections' => [
            [
                'player_id' => 999,
                'projected_points' => 99.0
            ]
        ]
    ],

    'player_outcomes' =>
        $playerOutcomes
];


$result =
    $service->evaluate(
        $readyEvidence
    );


gameweekPlayerRankingBacktestingTestResult(
    $playerService->callCount === 1,
    'Ready evidence invokes player-level ranking evaluation exactly once.'
);


gameweekPlayerRankingBacktestingTestResult(
    $playerService->receivedRankings
    ===
    $playerRankings,
    'Player-level evaluation receives preserved full-player-pool ranking evidence.'
);


gameweekPlayerRankingBacktestingTestResult(
    $playerService->receivedOutcomes
    ===
    $playerOutcomes,
    'Player-level evaluation receives realised player outcomes unchanged.'
);


gameweekPlayerRankingBacktestingTestResult(
    $metricsService->callCount === 1,
    'Ready evidence invokes ranking metrics calculation exactly once.'
);


gameweekPlayerRankingBacktestingTestResult(
    $metricsService->receivedEvaluations
    ===
    $playerEvaluations,
    'Ranking metrics receive the player-level evaluations returned by the comparison service.'
);


/*
 * ============================================================
 * SCENARIO I
 * EXACT READY CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Exact Ready Contract<br>";
echo "============================================<br>";


$expectedReady = [

    'status' => 'Ready',

    'reason' => null,

    'entry_id' => 2702264,

    'gameweek_id' => 3,

    'player_evaluations' =>
        $playerEvaluations,

    'metrics' =>
        $metrics
];


gameweekPlayerRankingBacktestingTestResult(
    $result === $expectedReady,
    'Ready evidence returns the exact gameweek Player Ranking backtesting contract.'
);


/*
 * ============================================================
 * SCENARIO J
 * NO HINDSIGHT RECONSTRUCTION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: No Hindsight Reconstruction<br>";
echo "============================================<br>";


$service =
    createGameweekPlayerRankingBacktestingService(
        $playerService,
        $metricsService
    );


$thrown = false;


try {

    $service->evaluate(
        [
            'status' => 'Ready',
            'entry_id' => 2702264,
            'gameweek_id' => 2,

            'recommendation_snapshot' => [

                /*
                 * Legacy evidence may still contain projections,
                 * captain data, XI data or other recommendations.
                 *
                 * None of these can reconstruct missing historical
                 * full-player-pool ranking evidence.
                 */
                'player_projections' => [
                    [
                        'player_id' => 101,
                        'projected_points' => 8.0
                    ]
                ],

                'starting_xi' => [
                    101
                ],

                'captain_recommendation' => [
                    'player_id' => 101
                ]
            ],

            'player_outcomes' => [
                [
                    'player_id' => 101,
                    'total_points' => 12,
                    'minutes' => 90
                ]
            ]
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $thrown =
        $exception->getMessage()
        ===
        'Ready backtesting evidence requires preserved player rankings.';
}


gameweekPlayerRankingBacktestingTestResult(
    $thrown,
    'Missing historical rankings are not reconstructed from other recommendation evidence.'
);


gameweekPlayerRankingBacktestingTestResult(
    $playerService->callCount === 0
    &&
    $metricsService->callCount === 0,
    'No ranking dependencies run when genuine preserved ranking evidence is unavailable.'
);


/*
 * ============================================================
 * SCENARIO K
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


$service =
    createGameweekPlayerRankingBacktestingService(
        $playerService,
        $metricsService
    );


$playerService->returnValue =
    $playerEvaluations;


$metricsService->returnValue =
    $metrics;


$sourceEvidence =
    $readyEvidence;


$sourceBefore =
    serialize(
        $sourceEvidence
    );


$service->evaluate(
    $sourceEvidence
);


gameweekPlayerRankingBacktestingTestResult(
    serialize(
        $sourceEvidence
    )
    ===
    $sourceBefore,
    'Gameweek ranking backtesting does not mutate assembled historical evidence.'
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