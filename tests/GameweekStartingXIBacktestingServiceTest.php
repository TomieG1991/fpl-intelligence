<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Starting XI Backtesting Service Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function gameweekStartingXIBacktestingCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;

    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }

    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


function gameweekStartingXIBacktestingSection(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>";
    echo "============================================<br>";
}


class GameweekStartingXIBacktestingSpecialistStub
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
        array $startingXI,
        array $bench,
        array $playerOutcomes
    ): array {

        $this->calls[] = [

            'starting_xi' =>
                $startingXI,

            'bench' =>
                $bench,

            'player_outcomes' =>
                $playerOutcomes
        ];


        return
            $this->returnValue;
    }
}


/*
 * ============================================================
 * CONTROLLED EVIDENCE
 * ============================================================
 */

$startingXI = [

    [
        'player_id' => 1,
        'position' => 'GK'
    ],

    [
        'player_id' => 2,
        'position' => 'DEF'
    ],

    [
        'player_id' => 3,
        'position' => 'DEF'
    ]
];


$bench = [

    [
        'player_id' => 12,
        'position' => 'GK'
    ],

    [
        'player_id' => 13,
        'position' => 'DEF'
    ]
];


$playerOutcomes = [

    [
        'player_id' => 1,
        'total_points' => 6,
        'minutes' => 90
    ],

    [
        'player_id' => 2,
        'total_points' => 2,
        'minutes' => 90
    ]
];


$specialistResult = [

    'starting_xi_points' =>
        8,

    'bench_points' =>
        4
];


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

gameweekStartingXIBacktestingSection(
    'Scenario A: Class Contract'
);


gameweekStartingXIBacktestingCheck(
    'GameweekStartingXIBacktestingService class exists',
    class_exists(
        'GameweekStartingXIBacktestingService'
    )
);


if (
    !class_exists(
        'GameweekStartingXIBacktestingService'
    )
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Gameweek Starting XI Backtesting Service Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br><br>";

    echo "RESULT: TESTS FAILED ❌";

    exit;
}


gameweekStartingXIBacktestingCheck(
    'GameweekStartingXIBacktestingService exposes evaluate()',
    method_exists(
        'GameweekStartingXIBacktestingService',
        'evaluate'
    )
);


/*
 * ============================================================
 * SCENARIO B
 * NON-READY EVIDENCE
 * ============================================================
 */

gameweekStartingXIBacktestingSection(
    'Scenario B: Non-Ready Evidence'
);


$nonReadySpecialist =
    new GameweekStartingXIBacktestingSpecialistStub(
        $specialistResult
    );


$service =
    new GameweekStartingXIBacktestingService(
        $nonReadySpecialist
    );


$incompleteResult =
    $service->evaluate(
        [
            'status' =>
                'Incomplete',

            'reason' =>
                'Recommendation snapshot is unavailable.',

            'entry_id' =>
                2702264,

            'gameweek_id' =>
                8
        ]
    );


gameweekStartingXIBacktestingCheck(
    'Incomplete evidence status is preserved',
    ($incompleteResult['status'] ?? null)
        ===
        'Incomplete'
);


gameweekStartingXIBacktestingCheck(
    'Incomplete evidence reason is preserved',
    ($incompleteResult['reason'] ?? null)
        ===
        'Recommendation snapshot is unavailable.'
);


gameweekStartingXIBacktestingCheck(
    'Incomplete evidence does not call the specialist evaluator',
    count(
        $nonReadySpecialist->calls
    )
    === 0
);


gameweekStartingXIBacktestingCheck(
    'Incomplete evidence contains no manufactured Starting XI evaluation',
    ($incompleteResult['starting_xi_evaluation'] ?? null)
        ===
        []
);


/*
 * ============================================================
 * SCENARIO C
 * READY EVIDENCE DELEGATION
 * ============================================================
 */

gameweekStartingXIBacktestingSection(
    'Scenario C: Ready Evidence Delegation'
);


$readySpecialist =
    new GameweekStartingXIBacktestingSpecialistStub(
        $specialistResult
    );


$service =
    new GameweekStartingXIBacktestingService(
        $readySpecialist
    );


$readyEvidence = [

    'status' =>
        'Ready',

    'reason' =>
        null,

    'entry_id' =>
        2702264,

    'gameweek_id' =>
        8,

    'recommendation_snapshot' => [

        'starting_xi' =>
            $startingXI,

        'bench' =>
            $bench
    ],

    'player_outcomes' =>
        $playerOutcomes
];


$readyResult =
    $service->evaluate(
        $readyEvidence
    );


gameweekStartingXIBacktestingCheck(
    'Ready evidence calls the specialist exactly once',
    count(
        $readySpecialist->calls
    )
    === 1
);


$specialistCall =
    $readySpecialist->calls[0]
    ?? [];


gameweekStartingXIBacktestingCheck(
    'Preserved Starting XI is delegated unchanged',
    ($specialistCall['starting_xi'] ?? null)
        ===
        $startingXI
);


gameweekStartingXIBacktestingCheck(
    'Preserved bench is delegated unchanged',
    ($specialistCall['bench'] ?? null)
        ===
        $bench
);


gameweekStartingXIBacktestingCheck(
    'Authoritative player outcomes are delegated unchanged',
    ($specialistCall['player_outcomes'] ?? null)
        ===
        $playerOutcomes
);


gameweekStartingXIBacktestingCheck(
    'Ready result preserves entry identity',
    ($readyResult['entry_id'] ?? null)
        ===
        2702264
);


gameweekStartingXIBacktestingCheck(
    'Ready result preserves gameweek identity',
    ($readyResult['gameweek_id'] ?? null)
        ===
        8
);


gameweekStartingXIBacktestingCheck(
    'Ready result returns specialist Starting XI evaluation unchanged',
    ($readyResult['starting_xi_evaluation'] ?? null)
        ===
        $specialistResult
);


/*
 * ============================================================
 * SCENARIO D
 * UNEVALUABLE READY EVIDENCE
 * ============================================================
 */

gameweekStartingXIBacktestingSection(
    'Scenario D: Unevaluable Ready Evidence'
);


$emptySpecialist =
    new GameweekStartingXIBacktestingSpecialistStub(
        []
    );


$service =
    new GameweekStartingXIBacktestingService(
        $emptySpecialist
    );


$unevaluableResult =
    $service->evaluate(
        $readyEvidence
    );


gameweekStartingXIBacktestingCheck(
    'Structurally unevaluable Starting XI becomes Incomplete',
    ($unevaluableResult['status'] ?? null)
        ===
        'Incomplete'
);


gameweekStartingXIBacktestingCheck(
    'Structurally unevaluable Starting XI has an auditable reason',
    ($unevaluableResult['reason'] ?? null)
        ===
        'Preserved Starting XI evidence could not be evaluated.'
);


gameweekStartingXIBacktestingCheck(
    'Structurally unevaluable Starting XI does not manufacture an evaluation',
    ($unevaluableResult['starting_xi_evaluation'] ?? null)
        ===
        []
);


/*
 * ============================================================
 * SCENARIO E
 * READY EVIDENCE VALIDATION
 * ============================================================
 */

gameweekStartingXIBacktestingSection(
    'Scenario E: Ready Evidence Validation'
);


$validationSpecialist =
    new GameweekStartingXIBacktestingSpecialistStub(
        $specialistResult
    );


$service =
    new GameweekStartingXIBacktestingService(
        $validationSpecialist
    );


$missingSnapshotThrew =
    false;


try {

    $service->evaluate(
        [
            'status' =>
                'Ready',

            'entry_id' =>
                2702264,

            'gameweek_id' =>
                8,

            'player_outcomes' =>
                $playerOutcomes
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $missingSnapshotThrew =
        true;
}


gameweekStartingXIBacktestingCheck(
    'Ready evidence requires a recommendation snapshot',
    $missingSnapshotThrew
);


$missingStartingXIThrew =
    false;


try {

    $service->evaluate(
        [
            'status' =>
                'Ready',

            'entry_id' =>
                2702264,

            'gameweek_id' =>
                8,

            'recommendation_snapshot' => [

                'starting_xi' =>
                    [],

                'bench' =>
                    $bench
            ],

            'player_outcomes' =>
                $playerOutcomes
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $missingStartingXIThrew =
        true;
}


gameweekStartingXIBacktestingCheck(
    'Ready evidence requires preserved Starting XI evidence',
    $missingStartingXIThrew
);


$missingOutcomesThrew =
    false;


try {

    $service->evaluate(
        [
            'status' =>
                'Ready',

            'entry_id' =>
                2702264,

            'gameweek_id' =>
                8,

            'recommendation_snapshot' => [

                'starting_xi' =>
                    $startingXI,

                'bench' =>
                    $bench
            ],

            'player_outcomes' =>
                []
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $missingOutcomesThrew =
        true;
}


gameweekStartingXIBacktestingCheck(
    'Ready evidence requires authoritative player outcomes',
    $missingOutcomesThrew
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Gameweek Starting XI Backtesting Service Test Summary<br>";
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