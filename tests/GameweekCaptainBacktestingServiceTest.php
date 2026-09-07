<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Captain Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekCaptainBacktestingTestResult(
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

class GameweekCaptainBacktestingServiceTestDouble
{
    public int $callCount = 0;

    public array $receivedCaptain = [];

    public array $receivedRankings = [];

    public array $receivedOutcomes = [];

    private array $result;


    public function __construct(
        array $result
    ) {
        $this->result =
            $result;
    }


    public function evaluate(
        array $captain,
        array $captainRankings,
        array $playerOutcomes
    ): array {

        $this->callCount++;


        $this->receivedCaptain =
            $captain;


        $this->receivedRankings =
            $captainRankings;


        $this->receivedOutcomes =
            $playerOutcomes;


        return
            $this->result;
    }
}


/*
 * ============================================================
 * CLASS AVAILABILITY
 * ============================================================
 */

$classExists =
    class_exists(
        'GameweekCaptainBacktestingService'
    );


gameweekCaptainBacktestingTestResult(
    $classExists,
    'GameweekCaptainBacktestingService exists.'
);


if (!$classExists) {

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
 * SYNTHETIC PRESERVED CAPTAIN EVIDENCE
 * ============================================================
 */

$captain =
    [
        'player_id' => 108,
        'name' => 'Player 108'
    ];


$captainRankings =
    [
        [
            'player_id' => 108,
            'name' => 'Player 108'
        ],
        [
            'player_id' => 105,
            'name' => 'Player 105'
        ],
        [
            'player_id' => 114,
            'name' => 'Player 114'
        ]
    ];


$captainRecommendation =
    [
        'status' => 'success',
        'captain' => $captain,
        'vice_captain' => [
            'player_id' => 105,
            'name' => 'Player 105'
        ],
        'alternatives' => [],
        'rankings' => $captainRankings
    ];


/*
 * ============================================================
 * SYNTHETIC AUTHORITATIVE OUTCOMES
 * ============================================================
 */

$playerOutcomes =
    [
        [
            'player_id' => 108,
            'total_points' => 8,
            'minutes' => 90
        ],
        [
            'player_id' => 105,
            'total_points' => 12,
            'minutes' => 90
        ],
        [
            'player_id' => 114,
            'total_points' => 15,
            'minutes' => 90
        ]
    ];


/*
 * ============================================================
 * SPECIALIST RESULT
 * ============================================================
 */

$captainEvaluation =
    [
        'captain_player_id' => 108,
        'captain_actual_points' => 8,
        'captain_actual_minutes' => 90,
        'best_alternative_player_id' => 114,
        'best_alternative_actual_points' => 15,
        'captain_points_lost' => 7
    ];


/*
 * ============================================================
 * SCENARIO A
 * NON-READY EVIDENCE IS PROPAGATED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Non-Ready Evidence<br>";
echo "============================================<br>";


$nonReadyStatuses =
    [
        'Unavailable',
        'Incomplete'
    ];


foreach (
    $nonReadyStatuses
    as $nonReadyStatus
) {

    $double =
        new GameweekCaptainBacktestingServiceTestDouble(
            $captainEvaluation
        );


    $service =
        new GameweekCaptainBacktestingService(
            $double
        );


    $evidence =
        [
            'status' => $nonReadyStatus,
            'reason' => 'Synthetic non-ready reason.',
            'entry_id' => 2702264,
            'gameweek_id' => 5
        ];


    $result =
        $service->evaluate(
            $evidence
        );


    gameweekCaptainBacktestingTestResult(
        (
            $result[
                'status'
            ]
            ?? null
        )
        ===
        $nonReadyStatus,
        $nonReadyStatus
            . ' evidence status is propagated.'
    );


    gameweekCaptainBacktestingTestResult(
        (
            $result[
                'reason'
            ]
            ?? null
        )
        ===
        'Synthetic non-ready reason.',
        $nonReadyStatus
            . ' evidence reason is propagated.'
    );


    gameweekCaptainBacktestingTestResult(
        (
            $result[
                'entry_id'
            ]
            ?? null
        )
        ===
        2702264,
        $nonReadyStatus
            . ' evidence entry identity is propagated.'
    );


    gameweekCaptainBacktestingTestResult(
        (
            $result[
                'gameweek_id'
            ]
            ?? null
        )
        ===
        5,
        $nonReadyStatus
            . ' evidence gameweek identity is propagated.'
    );


    gameweekCaptainBacktestingTestResult(
        (
            $result[
                'captain_evaluation'
            ]
            ?? null
        )
        ===
        [],
        $nonReadyStatus
            . ' evidence produces no captain evaluation.'
    );


    gameweekCaptainBacktestingTestResult(
        $double->callCount
        ===
        0,
        $nonReadyStatus
            . ' evidence does not invoke CaptainBacktestingService.'
    );
}


/*
 * ============================================================
 * READY EVIDENCE FACTORY
 * ============================================================
 */

$readyEvidence =
    [
        'status' => 'Ready',
        'reason' => null,
        'entry_id' => 2702264,
        'gameweek_id' => 5,
        'recommendation_snapshot' => [
            'captain_recommendation' =>
                $captainRecommendation
        ],
        'player_outcomes' =>
            $playerOutcomes
    ];


/*
 * ============================================================
 * SCENARIO B
 * READY EVIDENCE DELEGATES PRESERVED CAPTAIN DATA
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Ready Evidence Delegation<br>";
echo "============================================<br>";


$double =
    new GameweekCaptainBacktestingServiceTestDouble(
        $captainEvaluation
    );


$service =
    new GameweekCaptainBacktestingService(
        $double
    );


$result =
    $service->evaluate(
        $readyEvidence
    );


gameweekCaptainBacktestingTestResult(
    $double->callCount
    ===
    1,
    'Ready evidence invokes CaptainBacktestingService exactly once.'
);


gameweekCaptainBacktestingTestResult(
    $double->receivedCaptain
    ===
    $captain,
    'Preserved recommended captain is passed unchanged to CaptainBacktestingService.'
);


gameweekCaptainBacktestingTestResult(
    $double->receivedRankings
    ===
    $captainRankings,
    'Preserved Captain Intelligence rankings are passed unchanged to CaptainBacktestingService.'
);


gameweekCaptainBacktestingTestResult(
    $double->receivedOutcomes
    ===
    $playerOutcomes,
    'Authoritative player outcomes are passed unchanged to CaptainBacktestingService.'
);


/*
 * ============================================================
 * SCENARIO C
 * READY RESULT CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Ready Result Contract<br>";
echo "============================================<br>";


gameweekCaptainBacktestingTestResult(
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Ready',
    'Ready captain backtesting result retains Ready status.'
);


gameweekCaptainBacktestingTestResult(
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
    'Ready captain backtesting result has no failure reason.'
);


gameweekCaptainBacktestingTestResult(
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    ===
    2702264,
    'Ready captain backtesting result retains entry identity.'
);


gameweekCaptainBacktestingTestResult(
    (
        $result[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'Ready captain backtesting result retains gameweek identity.'
);


gameweekCaptainBacktestingTestResult(
    (
        $result[
            'captain_evaluation'
        ]
        ?? null
    )
    ===
    $captainEvaluation,
    'Ready result exposes specialist captain evaluation unchanged.'
);


/*
 * ============================================================
 * SCENARIO D
 * READY SNAPSHOT REQUIRED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Ready Snapshot Required<br>";
echo "============================================<br>";


$missingSnapshotEvidence =
    $readyEvidence;


unset(
    $missingSnapshotEvidence[
        'recommendation_snapshot'
    ]
);


$exceptionThrown =
    false;


try {

    $service->evaluate(
        $missingSnapshotEvidence
    );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekCaptainBacktestingTestResult(
    $exceptionThrown,
    'Ready evidence without recommendation snapshot is rejected.'
);


/*
 * ============================================================
 * SCENARIO E
 * PRESERVED CAPTAIN RECOMMENDATION REQUIRED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Captain Recommendation Required<br>";
echo "============================================<br>";


$missingCaptainRecommendationEvidence =
    $readyEvidence;


unset(
    $missingCaptainRecommendationEvidence[
        'recommendation_snapshot'
    ][
        'captain_recommendation'
    ]
);


$exceptionThrown =
    false;


try {

    $service->evaluate(
        $missingCaptainRecommendationEvidence
    );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekCaptainBacktestingTestResult(
    $exceptionThrown,
    'Ready evidence without preserved captain recommendation is rejected.'
);


/*
 * ============================================================
 * SCENARIO F
 * PRESERVED RECOMMENDED CAPTAIN REQUIRED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Recommended Captain Required<br>";
echo "============================================<br>";


$missingCaptainEvidence =
    $readyEvidence;


unset(
    $missingCaptainEvidence[
        'recommendation_snapshot'
    ][
        'captain_recommendation'
    ][
        'captain'
    ]
);


$exceptionThrown =
    false;


try {

    $service->evaluate(
        $missingCaptainEvidence
    );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekCaptainBacktestingTestResult(
    $exceptionThrown,
    'Ready evidence without preserved recommended captain is rejected.'
);


/*
 * ============================================================
 * SCENARIO G
 * PRESERVED CAPTAIN RANKINGS REQUIRED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Captain Rankings Required<br>";
echo "============================================<br>";


$missingRankingsEvidence =
    $readyEvidence;


unset(
    $missingRankingsEvidence[
        'recommendation_snapshot'
    ][
        'captain_recommendation'
    ][
        'rankings'
    ]
);


$exceptionThrown =
    false;


try {

    $service->evaluate(
        $missingRankingsEvidence
    );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekCaptainBacktestingTestResult(
    $exceptionThrown,
    'Ready evidence without preserved Captain Intelligence rankings is rejected.'
);


/*
 * ============================================================
 * SCENARIO H
 * REALISED PLAYER OUTCOMES REQUIRED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Realised Player Outcomes Required<br>";
echo "============================================<br>";


$missingOutcomesEvidence =
    $readyEvidence;


$missingOutcomesEvidence[
    'player_outcomes'
] =
    [];


$exceptionThrown =
    false;


try {

    $service->evaluate(
        $missingOutcomesEvidence
    );

} catch (
    InvalidArgumentException $exception
) {

    $exceptionThrown =
        true;
}


gameweekCaptainBacktestingTestResult(
    $exceptionThrown,
    'Ready evidence without realised player outcomes is rejected.'
);


/*
 * ============================================================
 * SCENARIO I
 * EMPTY SPECIALIST EVALUATION
 * ============================================================
 *
 * Ready historical evidence does not guarantee that the captain
 * evidence itself is structurally evaluable.
 *
 * CaptainBacktestingService remains responsible for that
 * low-level structural decision.
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Empty Specialist Evaluation<br>";
echo "============================================<br>";


$emptyDouble =
    new GameweekCaptainBacktestingServiceTestDouble(
        []
    );


$emptyService =
    new GameweekCaptainBacktestingService(
        $emptyDouble
    );


$emptyResult =
    $emptyService->evaluate(
        $readyEvidence
    );


gameweekCaptainBacktestingTestResult(
    (
        $emptyResult[
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete',
    'Empty specialist captain evaluation produces Incomplete status.'
);


gameweekCaptainBacktestingTestResult(
    (
        $emptyResult[
            'reason'
        ]
        ?? null
    )
    ===
    'Preserved captain evidence could not be evaluated.',
    'Empty specialist captain evaluation explains why captain backtesting is incomplete.'
);


gameweekCaptainBacktestingTestResult(
    (
        $emptyResult[
            'captain_evaluation'
        ]
        ?? null
    )
    ===
    [],
    'Incomplete captain evaluation remains empty.'
);


/*
 * ============================================================
 * SCENARIO J
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


$sourceEvidence =
    $readyEvidence;


$originalSourceEvidence =
    $sourceEvidence;


$immutabilityDouble =
    new GameweekCaptainBacktestingServiceTestDouble(
        $captainEvaluation
    );


$immutabilityService =
    new GameweekCaptainBacktestingService(
        $immutabilityDouble
    );


$immutabilityService->evaluate(
    $sourceEvidence
);


gameweekCaptainBacktestingTestResult(
    $sourceEvidence
    ===
    $originalSourceEvidence,
    'Assembled historical backtesting evidence is not mutated.'
);


/*
 * ============================================================
 * SCENARIO K
 * GAMEWEEK CAPTAIN BACKTESTING BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Gameweek Captain Backtesting Boundary<br>";
echo "============================================<br>";


gameweekCaptainBacktestingTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Gameweek captain backtesting does not manufacture an accuracy score.'
);


gameweekCaptainBacktestingTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Gameweek captain backtesting does not manufacture an overall score.'
);


gameweekCaptainBacktestingTestResult(
    !array_key_exists(
        'vice_captain_result',
        $result
    ),
    'Gameweek captain backtesting does not yet simulate vice-captain fallback.'
);


gameweekCaptainBacktestingTestResult(
    !array_key_exists(
        'transfer_result',
        $result
    ),
    'Gameweek captain backtesting does not evaluate transfer recommendations.'
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