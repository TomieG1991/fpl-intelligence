<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Projection Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekProjectionBacktestingTestResult(
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
 * TEST DOUBLE
 * ============================================================
 */

class GameweekProjectionBacktestingServiceDouble
{
    public array $calls = [];

    private array $returnValue;


    public function __construct(
        array $returnValue
    ) {
        $this->returnValue =
            $returnValue;
    }


    public function evaluate(
        array $playerProjections,
        array $playerOutcomes
    ): array {

        $this->calls[] = [
            'player_projections' =>
                $playerProjections,

            'player_outcomes' =>
                $playerOutcomes
        ];


        return
            $this->returnValue;
    }


    public function calculate(
        array $evaluations
    ): array {

        $this->calls[] = [
            'evaluations' =>
                $evaluations
        ];


        return
            $this->returnValue;
    }
}


/*
 * ============================================================
 * SERVICE AVAILABILITY
 * ============================================================
 */

gameweekProjectionBacktestingTestResult(
    class_exists(
        'GameweekProjectionBacktestingService'
    ),
    'GameweekProjectionBacktestingService exists.'
);


if (
    !class_exists(
        'GameweekProjectionBacktestingService'
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
 * STANDARD EVIDENCE
 * ============================================================
 */

$playerProjections = [

    [
        'player_id' => 101,
        'projected_points' => 6.5,
        'projected_minutes' => 90,
        'has_projected_points' => true
    ],

    [
        'player_id' => 102,
        'projected_points' => 8.0,
        'projected_minutes' => 75,
        'has_projected_points' => true
    ]
];


$playerOutcomes = [

    [
        'gameweek_id' => 5,
        'player_id' => 101,
        'fixture_count' => 1,
        'total_points' => 8,
        'minutes' => 90,
        'starts' => 1,
        'goals' => 1,
        'assists' => 0,
        'clean_sheets' => 1,
        'bonus' => 2
    ],

    [
        'gameweek_id' => 5,
        'player_id' => 102,
        'fixture_count' => 1,
        'total_points' => 5,
        'minutes' => 60,
        'starts' => 1,
        'goals' => 0,
        'assists' => 1,
        'clean_sheets' => 0,
        'bonus' => 0
    ]
];


$playerEvaluations = [

    [
        'player_id' => 101,
        'projected_points' => 6.5,
        'actual_points' => 8,
        'points_error' => 1.5,
        'absolute_points_error' => 1.5,
        'projected_minutes' => 90,
        'actual_minutes' => 90,
        'minutes_error' => 0.0,
        'absolute_minutes_error' => 0.0
    ],

    [
        'player_id' => 102,
        'projected_points' => 8.0,
        'actual_points' => 5,
        'points_error' => -3.0,
        'absolute_points_error' => 3.0,
        'projected_minutes' => 75,
        'actual_minutes' => 60,
        'minutes_error' => -15.0,
        'absolute_minutes_error' => 15.0
    ]
];


$metrics = [

    'points' => [
        'sample_size' => 2,
        'mean_error' => -0.75,
        'mean_absolute_error' => 2.25
    ],

    'minutes' => [
        'sample_size' => 2,
        'mean_error' => -7.5,
        'mean_absolute_error' => 7.5
    ]
];


$readyEvidence = [

    'status' => 'Ready',

    'reason' => null,

    'entry_id' => 900001,

    'gameweek_id' => 5,

    'gameweek' => [
        'id' => 5,
        'fpl_gameweek_id' => 1,
        'finished' => 1,
        'data_checked' => 1
    ],

    'recommendation_snapshot' => [

        'gameweek_id' => 5,

        'entry_id' => 900001,

        'player_projections' =>
            $playerProjections,

        'starting_xi' => [],

        'captain_recommendation' => [],

        'transfer_recommendations' => [],

        'gameweek_decision' => [],

        'chip_recommendations' => []
    ],

    'player_outcomes' =>
        $playerOutcomes
];


/*
 * ============================================================
 * SCENARIO A
 * READY EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Ready Backtesting Evidence<br>";
echo "============================================<br>";


$evaluator =
    new GameweekProjectionBacktestingServiceDouble(
        $playerEvaluations
    );


$metricsService =
    new GameweekProjectionBacktestingServiceDouble(
        $metrics
    );


$service =
    new GameweekProjectionBacktestingService(
        $evaluator,
        $metricsService
    );


$result =
    $service->evaluate(
        $readyEvidence
    );


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Ready',
    'Ready historical evidence produces Ready projection backtesting.'
);


gameweekProjectionBacktestingTestResult(
    array_key_exists(
        'reason',
        $result
    )
    &&
    $result[
        'reason'
    ]
    === null,
    'Ready projection backtesting has no incomplete reason.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    ===
    900001,
    'Ready projection backtesting preserves entry identity.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'Ready projection backtesting preserves local gameweek identity.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'player_evaluations'
        ]
        ?? null
    )
    ===
    $playerEvaluations,
    'Ready projection backtesting preserves player-level evaluation evidence.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'metrics'
        ]
        ?? null
    )
    ===
    $metrics,
    'Ready projection backtesting preserves aggregate projection metrics.'
);


/*
 * ============================================================
 * SCENARIO B
 * EVALUATOR RECEIVES EXACT EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Player Evaluator Orchestration<br>";
echo "============================================<br>";


gameweekProjectionBacktestingTestResult(
    count(
        $evaluator->calls
    )
    === 1,
    'Player projection evaluator is called exactly once.'
);


gameweekProjectionBacktestingTestResult(
    (
        $evaluator->calls[
            0
        ][
            'player_projections'
        ]
        ?? null
    )
    ===
    $playerProjections,
    'Player evaluator receives exact preserved projection evidence.'
);


gameweekProjectionBacktestingTestResult(
    (
        $evaluator->calls[
            0
        ][
            'player_outcomes'
        ]
        ?? null
    )
    ===
    $playerOutcomes,
    'Player evaluator receives exact realised outcome evidence.'
);


/*
 * ============================================================
 * SCENARIO C
 * METRICS RECEIVE PLAYER EVALUATIONS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Metrics Orchestration<br>";
echo "============================================<br>";


gameweekProjectionBacktestingTestResult(
    count(
        $metricsService->calls
    )
    === 1,
    'Projection metrics service is called exactly once.'
);


gameweekProjectionBacktestingTestResult(
    (
        $metricsService->calls[
            0
        ][
            'evaluations'
        ]
        ?? null
    )
    ===
    $playerEvaluations,
    'Metrics service receives exact player-level evaluation evidence.'
);


/*
 * ============================================================
 * SCENARIO D
 * UNAVAILABLE HISTORICAL EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Unavailable Historical Evidence<br>";
echo "============================================<br>";


$unusedEvaluator =
    new GameweekProjectionBacktestingServiceDouble(
        []
    );


$unusedMetrics =
    new GameweekProjectionBacktestingServiceDouble(
        []
    );


$service =
    new GameweekProjectionBacktestingService(
        $unusedEvaluator,
        $unusedMetrics
    );


$unavailableEvidence = [

    'status' => 'Unavailable',

    'reason' =>
        'Gameweek outcomes are not yet authoritative',

    'entry_id' => 900001,

    'gameweek_id' => 6,

    'gameweek' => [
        'id' => 6,
        'fpl_gameweek_id' => 2
    ],

    'recommendation_snapshot' => null,

    'player_outcomes' => []
];


$result =
    $service->evaluate(
        $unavailableEvidence
    );


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable',
    'Unavailable historical evidence remains unavailable.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'reason'
        ]
        ?? null
    )
    ===
    'Gameweek outcomes are not yet authoritative',
    'Unavailable reason is preserved.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    ===
    900001,
    'Unavailable result preserves entry identity.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    6,
    'Unavailable result preserves gameweek identity.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'player_evaluations'
        ]
        ?? null
    )
    ===
    [],
    'Unavailable evidence produces no player evaluations.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'metrics'
        ]
        ?? null
    )
    ===
    null,
    'Unavailable evidence produces no aggregate metrics.'
);


gameweekProjectionBacktestingTestResult(
    count(
        $unusedEvaluator->calls
    )
    === 0,
    'Unavailable evidence does not invoke player evaluator.'
);


gameweekProjectionBacktestingTestResult(
    count(
        $unusedMetrics->calls
    )
    === 0,
    'Unavailable evidence does not invoke metrics service.'
);


/*
 * ============================================================
 * SCENARIO E
 * INCOMPLETE HISTORICAL EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Incomplete Historical Evidence<br>";
echo "============================================<br>";


$unusedEvaluator =
    new GameweekProjectionBacktestingServiceDouble(
        []
    );


$unusedMetrics =
    new GameweekProjectionBacktestingServiceDouble(
        []
    );


$service =
    new GameweekProjectionBacktestingService(
        $unusedEvaluator,
        $unusedMetrics
    );


$incompleteEvidence = [

    'status' => 'Incomplete',

    'reason' =>
        'Recommendation snapshot is unavailable',

    'entry_id' => 900002,

    'gameweek_id' => 5,

    'gameweek' => [
        'id' => 5,
        'fpl_gameweek_id' => 1
    ],

    'recommendation_snapshot' => null,

    'player_outcomes' => []
];


$result =
    $service->evaluate(
        $incompleteEvidence
    );


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Incomplete historical evidence remains incomplete.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'reason'
        ]
        ?? null
    )
    ===
    'Recommendation snapshot is unavailable',
    'Incomplete reason is preserved.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    ===
    900002,
    'Incomplete result preserves entry identity.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'Incomplete result preserves gameweek identity.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'player_evaluations'
        ]
        ?? null
    )
    ===
    [],
    'Incomplete evidence produces no player evaluations.'
);


gameweekProjectionBacktestingTestResult(
    (
        $result[
            'metrics'
        ]
        ?? null
    )
    ===
    null,
    'Incomplete evidence produces no aggregate metrics.'
);


gameweekProjectionBacktestingTestResult(
    count(
        $unusedEvaluator->calls
    )
    === 0,
    'Incomplete evidence does not invoke player evaluator.'
);


gameweekProjectionBacktestingTestResult(
    count(
        $unusedMetrics->calls
    )
    === 0,
    'Incomplete evidence does not invoke metrics service.'
);


/*
 * ============================================================
 * SCENARIO F
 * READY EVIDENCE WITHOUT SNAPSHOT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Invalid Ready Evidence Without Snapshot<br>";
echo "============================================<br>";


$unusedEvaluator =
    new GameweekProjectionBacktestingServiceDouble(
        []
    );


$unusedMetrics =
    new GameweekProjectionBacktestingServiceDouble(
        []
    );


$service =
    new GameweekProjectionBacktestingService(
        $unusedEvaluator,
        $unusedMetrics
    );


$invalidReadyEvidence =
    $readyEvidence;


$invalidReadyEvidence[
    'recommendation_snapshot'
] =
    null;


$exceptionThrown =
    false;


try {

    $service->evaluate(
        $invalidReadyEvidence
    );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekProjectionBacktestingTestResult(
    $exceptionThrown,
    'Ready evidence without recommendation snapshot is rejected.'
);


gameweekProjectionBacktestingTestResult(
    count(
        $unusedEvaluator->calls
    )
    === 0,
    'Invalid Ready snapshot evidence does not invoke player evaluator.'
);


/*
 * ============================================================
 * SCENARIO G
 * READY EVIDENCE WITHOUT PLAYER PROJECTIONS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Invalid Ready Evidence Without Projections<br>";
echo "============================================<br>";


$unusedEvaluator =
    new GameweekProjectionBacktestingServiceDouble(
        []
    );


$unusedMetrics =
    new GameweekProjectionBacktestingServiceDouble(
        []
    );


$service =
    new GameweekProjectionBacktestingService(
        $unusedEvaluator,
        $unusedMetrics
    );


$invalidReadyEvidence =
    $readyEvidence;


$invalidReadyEvidence[
    'recommendation_snapshot'
][
    'player_projections'
] =
    [];


$exceptionThrown =
    false;


try {

    $service->evaluate(
        $invalidReadyEvidence
    );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekProjectionBacktestingTestResult(
    $exceptionThrown,
    'Ready evidence without preserved player projections is rejected.'
);


gameweekProjectionBacktestingTestResult(
    count(
        $unusedEvaluator->calls
    )
    === 0,
    'Missing projection evidence does not invoke player evaluator.'
);


/*
 * ============================================================
 * SCENARIO H
 * READY EVIDENCE WITHOUT PLAYER OUTCOMES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Invalid Ready Evidence Without Outcomes<br>";
echo "============================================<br>";


$unusedEvaluator =
    new GameweekProjectionBacktestingServiceDouble(
        []
    );


$unusedMetrics =
    new GameweekProjectionBacktestingServiceDouble(
        []
    );


$service =
    new GameweekProjectionBacktestingService(
        $unusedEvaluator,
        $unusedMetrics
    );


$invalidReadyEvidence =
    $readyEvidence;


$invalidReadyEvidence[
    'player_outcomes'
] =
    [];


$exceptionThrown =
    false;


try {

    $service->evaluate(
        $invalidReadyEvidence
    );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekProjectionBacktestingTestResult(
    $exceptionThrown,
    'Ready evidence without realised player outcomes is rejected.'
);


gameweekProjectionBacktestingTestResult(
    count(
        $unusedEvaluator->calls
    )
    === 0,
    'Missing outcome evidence does not invoke player evaluator.'
);


/*
 * ============================================================
 * SCENARIO I
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


$evaluator =
    new GameweekProjectionBacktestingServiceDouble(
        $playerEvaluations
    );


$metricsService =
    new GameweekProjectionBacktestingServiceDouble(
        $metrics
    );


$service =
    new GameweekProjectionBacktestingService(
        $evaluator,
        $metricsService
    );


$sourceEvidence =
    $readyEvidence;


$originalSourceEvidence =
    $sourceEvidence;


$service->evaluate(
    $sourceEvidence
);


gameweekProjectionBacktestingTestResult(
    $sourceEvidence
        ===
        $originalSourceEvidence,
    'Historical backtesting evidence is not mutated.'
);


/*
 * ============================================================
 * SCENARIO J
 * NARROW ORCHESTRATION RESPONSIBILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Projection Backtesting Only<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $readyEvidence
    );


gameweekProjectionBacktestingTestResult(
    !array_key_exists(
        'captain_result',
        $result
    ),
    'Projection orchestration does not evaluate captain recommendations.'
);


gameweekProjectionBacktestingTestResult(
    !array_key_exists(
        'starting_xi_result',
        $result
    ),
    'Projection orchestration does not evaluate Starting XI recommendations.'
);


gameweekProjectionBacktestingTestResult(
    !array_key_exists(
        'transfer_result',
        $result
    ),
    'Projection orchestration does not evaluate transfer recommendations.'
);


gameweekProjectionBacktestingTestResult(
    !array_key_exists(
        'chip_result',
        $result
    ),
    'Projection orchestration does not evaluate chip recommendations.'
);


gameweekProjectionBacktestingTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Projection orchestration does not create a synthetic overall score.'
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


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}