<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Bench Boost Decision Intelligence Service Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function benchBoostServiceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


function benchBoostServiceHeading(
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


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

benchBoostServiceHeading(
    'Scenario A: Class Contract'
);


$classExists =
    class_exists(
        'BenchBoostDecisionIntelligenceService'
    );


benchBoostServiceCheck(
    'BenchBoostDecisionIntelligenceService class exists',
    $classExists
);


if (
    !$classExists
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Bench Boost Decision Intelligence Service Test Summary<br>";
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


$buildMethodExists =
    method_exists(
        'BenchBoostDecisionIntelligenceService',
        'build'
    );


benchBoostServiceCheck(
    'Service exposes build()',
    $buildMethodExists
);


if (
    !$buildMethodExists
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Bench Boost Decision Intelligence Service Test Summary<br>";
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


/*
 * ============================================================
 * SCENARIO B
 * CONSTRUCTOR CONTRACT
 * ============================================================
 */

benchBoostServiceHeading(
    'Scenario B: Constructor Contract'
);


$constructor =
    new ReflectionMethod(
        'BenchBoostDecisionIntelligenceService',
        '__construct'
    );


$constructorParameters =
    $constructor->getParameters();


benchBoostServiceCheck(
    'Service constructor has exactly two dependencies',
    count(
        $constructorParameters
    )
    ===
    2
);


$constructorTypes =
    [];


foreach (
    $constructorParameters
    as $parameter
) {

    $type =
        $parameter->getType();


    $constructorTypes[] =
        $type instanceof ReflectionNamedType
            ? $type->getName()
            : null;
}


benchBoostServiceCheck(
    'First dependency is SquadHorizonIntelligenceService',
    (
        $constructorTypes[
            0
        ]
        ?? null
    )
    ===
    'SquadHorizonIntelligenceService'
);


benchBoostServiceCheck(
    'Second dependency is BenchBoostIntelligence',
    (
        $constructorTypes[
            1
        ]
        ?? null
    )
    ===
    'BenchBoostIntelligence'
);


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class BenchBoostServiceSquadHorizonStub
    extends SquadHorizonIntelligenceService
{
    public array
        $result;


    public array
        $receivedImportedSquad =
            [];


    public ?int
        $receivedHorizon =
            null;


    public function __construct(
        array $result
    ) {
        $this->result =
            $result;
    }


    public function buildForImportedSquad(
        array $importedSquad,
        int $horizon = 3
    ): array {

        $this->receivedImportedSquad =
            $importedSquad;


        $this->receivedHorizon =
            $horizon;


        return
            $this->result;
    }
}


class BenchBoostServiceModelStub
    extends BenchBoostIntelligence
{
    public array
        $receivedGameweek =
            [];


    public array
        $analysisResult;


    public ?array
        $receivedAnalysis =
            null;


    public ChipDecision
        $decisionResult;


    public function __construct(
        array $analysisResult,
        ChipDecision $decisionResult
    ) {
        $this->analysisResult =
            $analysisResult;

        $this->decisionResult =
            $decisionResult;
    }


    public function analyse(
        array $gameweek
    ): array {

        $this->receivedGameweek =
            $gameweek;


        return
            $this->analysisResult;
    }


    public function createDecision(
        array $analysis
    ): ChipDecision {

        $this->receivedAnalysis =
            $analysis;


        return
            $this->decisionResult;
    }
}


/*
 * ============================================================
 * SHARED FIXTURES
 * ============================================================
 */

$importedSquad = [

    'status' =>
        'success',

    'players' =>
        []
];


for (
    $playerNumber = 1;
    $playerNumber <= 15;
    $playerNumber++
) {

    $importedSquad[
        'players'
    ][] = [

        'fpl_player_id' =>
            1000
            +
            $playerNumber
    ];
}


$gameweekResult = [

    'gameweek' =>
        3,

    'player_count' =>
        15,

    'players' =>
        [],

    'starting_xi' =>
        [],

    'bench' =>
        [],

    'bench_coverage' => [

        'bench_player_count' =>
            4,

        'total_projected_points' =>
            15.0
    ]
];


$availableHorizonResult = [

    'status' =>
        'Available',

    'player_count' =>
        15,

    'players' =>
        [],

    'horizon_result' => [

        'status' =>
            'Available',

        'gameweeks' => [

            3 =>
                $gameweekResult
        ]
    ]
];


$analysisResult = [

    'projected_bench_points' =>
        15.0,

    'bench_reliability' =>
        0.75,

    'fixture_quality' =>
        72.5,

    'full_squad_availability' =>
        0.95
];


$decisionResult =
    new ChipDecision(
        'Bench Boost',
        'Use',
        0.75,
        'Bench Boost service test decision.'
    );


/*
 * ============================================================
 * SCENARIO C
 * ONE-GAMEWEEK HORIZON REQUEST
 * ============================================================
 */

benchBoostServiceHeading(
    'Scenario C: One-Gameweek Horizon Request'
);


$horizonStub =
    new BenchBoostServiceSquadHorizonStub(
        $availableHorizonResult
    );


$modelStub =
    new BenchBoostServiceModelStub(
        $analysisResult,
        $decisionResult
    );


$service =
    new BenchBoostDecisionIntelligenceService(
        $horizonStub,
        $modelStub
    );


$result =
    $service->build(
        $importedSquad
    );


benchBoostServiceCheck(
    'Service passes imported squad to Squad Horizon service',
    $horizonStub
        ->receivedImportedSquad
    ===
    $importedSquad
);


benchBoostServiceCheck(
    'Bench Boost requests exactly one gameweek',
    $horizonStub
        ->receivedHorizon
    ===
    1
);


/*
 * ============================================================
 * SCENARIO D
 * UNAVAILABLE CURRENT SQUAD
 * ============================================================
 */

benchBoostServiceHeading(
    'Scenario D: Unavailable Current Squad'
);


$unavailableHorizonResult = [

    'status' =>
        'Unavailable',

    'player_count' =>
        0,

    'players' =>
        [],

    'horizon_result' =>
        null
];


$unavailableHorizonStub =
    new BenchBoostServiceSquadHorizonStub(
        $unavailableHorizonResult
    );


$unavailableModelStub =
    new BenchBoostServiceModelStub(
        $analysisResult,
        $decisionResult
    );


$unavailableService =
    new BenchBoostDecisionIntelligenceService(
        $unavailableHorizonStub,
        $unavailableModelStub
    );


$unavailableResult =
    $unavailableService->build(
        $importedSquad
    );


benchBoostServiceCheck(
    'Unavailable current squad returns Unavailable',
    (
        $unavailableResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


benchBoostServiceCheck(
    'Unavailable result preserves current horizon result',
    (
        $unavailableResult[
            'current_horizon_result'
        ]
        ?? null
    )
    ===
    $unavailableHorizonResult
);


benchBoostServiceCheck(
    'Unavailable current squad does not run Bench Boost analysis',
    $unavailableModelStub
        ->receivedGameweek
    ===
    []
);


benchBoostServiceCheck(
    'Unavailable result exposes no analysis',
    array_key_exists(
        'analysis',
        $unavailableResult
    )
    &&
    $unavailableResult[
        'analysis'
    ]
    ===
    null
);


benchBoostServiceCheck(
    'Unavailable result exposes no decision',
    array_key_exists(
        'decision',
        $unavailableResult
    )
    &&
    $unavailableResult[
        'decision'
    ]
    ===
    null
);


/*
 * ============================================================
 * SCENARIO E
 * INVALID HORIZON RESULT
 * ============================================================
 */

benchBoostServiceHeading(
    'Scenario E: Invalid Horizon Result'
);


$invalidInnerHorizonResult = [

    'status' =>
        'Available',

    'player_count' =>
        15,

    'players' =>
        [],

    'horizon_result' => [

        'status' =>
            'Available',

        'gameweeks' =>
            []
    ]
];


$invalidInnerHorizonStub =
    new BenchBoostServiceSquadHorizonStub(
        $invalidInnerHorizonResult
    );


$invalidInnerModelStub =
    new BenchBoostServiceModelStub(
        $analysisResult,
        $decisionResult
    );


$invalidInnerService =
    new BenchBoostDecisionIntelligenceService(
        $invalidInnerHorizonStub,
        $invalidInnerModelStub
    );


$invalidInnerResult =
    $invalidInnerService->build(
        $importedSquad
    );


benchBoostServiceCheck(
    'Missing one-gameweek result returns Unavailable',
    (
        $invalidInnerResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


benchBoostServiceCheck(
    'Missing gameweek does not run Bench Boost analysis',
    $invalidInnerModelStub
        ->receivedGameweek
    ===
    []
);


/*
 * ============================================================
 * SCENARIO F
 * ANALYSIS ORCHESTRATION
 * ============================================================
 */

benchBoostServiceHeading(
    'Scenario F: Analysis Orchestration'
);


$analysisHorizonStub =
    new BenchBoostServiceSquadHorizonStub(
        $availableHorizonResult
    );


$analysisModelStub =
    new BenchBoostServiceModelStub(
        $analysisResult,
        $decisionResult
    );


$analysisService =
    new BenchBoostDecisionIntelligenceService(
        $analysisHorizonStub,
        $analysisModelStub
    );


$analysisServiceResult =
    $analysisService->build(
        $importedSquad
    );


benchBoostServiceCheck(
    'Service passes represented gameweek to Bench Boost analysis',
    $analysisModelStub
        ->receivedGameweek
    ===
    $gameweekResult
);


benchBoostServiceCheck(
    'Service exposes Bench Boost analysis result',
    (
        $analysisServiceResult[
            'analysis'
        ]
        ?? null
    )
    ===
    $analysisResult
);


/*
 * ============================================================
 * SCENARIO G
 * DECISION ORCHESTRATION
 * ============================================================
 */

benchBoostServiceHeading(
    'Scenario G: Decision Orchestration'
);


benchBoostServiceCheck(
    'Service passes analysis to decision engine',
    $analysisModelStub
        ->receivedAnalysis
    ===
    $analysisResult
);


benchBoostServiceCheck(
    'Service exposes ChipDecision result',
    (
        $analysisServiceResult[
            'decision'
        ]
        ?? null
    )
    ===
    $decisionResult
);


benchBoostServiceCheck(
    'Successful Bench Boost result is Available',
    (
        $analysisServiceResult[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


benchBoostServiceCheck(
    'Successful result preserves current horizon result',
    (
        $analysisServiceResult[
            'current_horizon_result'
        ]
        ?? null
    )
    ===
    $availableHorizonResult
);


/*
 * ============================================================
 * SCENARIO H
 * DECISION CONTRACT
 * ============================================================
 */

benchBoostServiceHeading(
    'Scenario H: Decision Contract'
);


$decision =
    $analysisServiceResult[
        'decision'
    ]
    ?? null;


benchBoostServiceCheck(
    'Final decision is a ChipDecision',
    $decision instanceof ChipDecision
);


benchBoostServiceCheck(
    'Final decision identifies Bench Boost chip',
    $decision instanceof ChipDecision
    &&
    $decision->getChip()
    ===
    'Bench Boost'
);


benchBoostServiceCheck(
    'Final decision preserves recommendation',
    $decision instanceof ChipDecision
    &&
    $decision->getRecommendation()
    ===
    'Use'
);


benchBoostServiceCheck(
    'Final decision preserves confidence',
    $decision instanceof ChipDecision
    &&
    abs(
        $decision->getConfidence()
        -
        0.75
    )
    <
    0.001
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Bench Boost Decision Intelligence Service Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}