<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function freeHitDecisionServiceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;


        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';


        return;
    }


    $failed++;


    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';
}


function freeHitDecisionServiceHeading(
    string $heading
): void {

    echo
        '<br>'
        . '============================================<br>'
        . htmlspecialchars(
            $heading,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>'
        . '============================================<br>';
}


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class FreeHitDecisionCurrentHorizonStub
    extends
    SquadHorizonIntelligenceService
{
    public int
        $buildCallCount = 0;


    public array
        $receivedImportedSquad = [];


    public ?int
        $receivedHorizon = null;


    public array
        $result = [];


    public function __construct()
    {
    }


    public function buildForImportedSquad(
        array $importedSquad,
        int $horizon = 3
    ): array {

        $this->buildCallCount++;


        $this->receivedImportedSquad =
            $importedSquad;


        $this->receivedHorizon =
            $horizon;


        return
            $this->result;
    }
}


class FreeHitDecisionHorizonStub
    extends
    FreeHitHorizonIntelligenceService
{
    public int
        $buildCallCount = 0;


    public array
        $receivedPlayers = [];


    public ?float
        $receivedBudget = null;


    public array
        $result = [];


    public function __construct()
    {
    }


    public function build(
        array $players,
        float $budget = 100.0
    ): array {

        $this->buildCallCount++;


        $this->receivedPlayers =
            $players;


        $this->receivedBudget =
            $budget;


        return
            $this->result;
    }
}


class FreeHitDecisionIntelligenceStub
    extends
    FreeHitDecisionIntelligence
{
    public int
        $analyseCallCount = 0;


    public ?float
        $receivedCurrentProjectedPoints = null;


    public ?float
        $receivedFreeHitProjectedPoints = null;


    public int
        $decisionCallCount = 0;


    public ?float
        $receivedProjectedPointsGain = null;


    public ?float
        $receivedConfidence = null;


    public array
        $analysisResult = [];


    public ?ChipDecision
        $decisionResult = null;


    public function analyseValue(
        float $currentSquadProjectedPoints,
        float $freeHitProjectedPoints
    ): array {

        $this->analyseCallCount++;


        $this->receivedCurrentProjectedPoints =
            $currentSquadProjectedPoints;


        $this->receivedFreeHitProjectedPoints =
            $freeHitProjectedPoints;


        return
            $this->analysisResult;
    }


    public function createDecision(
        float $projectedPointsGain,
        float $confidence
    ): ChipDecision {

        $this->decisionCallCount++;


        $this->receivedProjectedPointsGain =
            $projectedPointsGain;


        $this->receivedConfidence =
            $confidence;


        if (
            $this->decisionResult
            instanceof
            ChipDecision
        ) {

            return
                $this->decisionResult;
        }


        return
            new ChipDecision(
                'Free Hit',
                'Hold',
                $confidence,
                'Test decision.'
            );
    }
}


/*
 * ============================================================
 * HEADER
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Free Hit Decision Intelligence Service Test<br>';

echo
    '============================================<br>';


/*
 * ============================================================
 * SCENARIO A: SERVICE CONTRACT
 * ============================================================
 */

freeHitDecisionServiceHeading(
    'Scenario A: Free Hit Decision Service Contract'
);


$classExists =
    class_exists(
        'FreeHitDecisionIntelligenceService'
    );


freeHitDecisionServiceCheck(
    'FreeHitDecisionIntelligenceService class exists',
    $classExists
);


$hasBuildMethod =
    false;


if (
    $classExists
) {

    $reflection =
        new ReflectionClass(
            'FreeHitDecisionIntelligenceService'
        );


    $hasBuildMethod =
        $reflection
            ->hasMethod(
                'build'
            );
}


freeHitDecisionServiceCheck(
    'FreeHitDecisionIntelligenceService exposes build',
    $hasBuildMethod
);


/*
 * ============================================================
 * REMAINING SCENARIOS REQUIRE CLASS
 * ============================================================
 */

if (
    !$classExists
    ||
    !$hasBuildMethod
) {

    echo
        '<br>'
        . 'Remaining scenarios skipped until '
        . 'FreeHitDecisionIntelligenceService contract exists.<br>';


    echo
        '<br>'
        . '============================================<br>';

    echo
        'TEST SUMMARY<br>';

    echo
        '============================================<br>';


    echo
        'Passed: '
        . $passed
        . '<br>';


    echo
        'Failed: '
        . $failed
        . '<br>';


    echo
        $failed === 0
            ? 'RESULT: ALL TESTS PASSED ✅<br>'
            : 'RESULT: TESTS FAILED ❌<br>';


    exit;
}


/*
 * ============================================================
 * COMMON FIXTURES
 * ============================================================
 */

$importedSquad = [

    [
        'element' =>
            1
    ],

    [
        'element' =>
            2
    ]
];


$playerPool = [

    [
        'player_id' =>
            1,

        'name' =>
            'Player One'
    ],

    [
        'player_id' =>
            2,

        'name' =>
            'Player Two'
    ]
];


$currentHorizonResult = [

    'status' =>
        'Available',

    'horizon_result' => [

        'status' =>
            'Available',

        'horizon' =>
            1,

        'gameweeks' => [

            3 => [

                'gameweek' =>
                    3,

                'starting_xi_projected_points' =>
                    55.0,

                'starting_xi_projection_confidence' =>
                    0.82
            ]
        ]
    ]
];


$freeHitHorizonResult = [

    'status' =>
        'Available',

    'free_hit_result' => [

        'status' =>
            'Available'
    ],

    'horizon_result' => [

        'status' =>
            'Available',

        'horizon' =>
            1,

        'gameweeks' => [

            3 => [

                'gameweek' =>
                    3,

                'starting_xi_projected_points' =>
                    67.0,

                'starting_xi_projection_confidence' =>
                    0.76
            ]
        ]
    ]
];


/*
 * ============================================================
 * SCENARIO B: SUCCESSFUL FREE HIT DECISION
 * ============================================================
 */

freeHitDecisionServiceHeading(
    'Scenario B: Successful Free Hit Decision'
);


$currentHorizonService =
    new FreeHitDecisionCurrentHorizonStub();


$currentHorizonService->result =
    $currentHorizonResult;


$freeHitHorizonService =
    new FreeHitDecisionHorizonStub();


$freeHitHorizonService->result =
    $freeHitHorizonResult;


$decisionIntelligence =
    new FreeHitDecisionIntelligenceStub();


$decisionIntelligence->analysisResult = [

    'current_squad_projected_points' =>
        55.0,

    'free_hit_projected_points' =>
        67.0,

    'projected_points_gain' =>
        12.0,

    'improves_squad' =>
        true
];


$expectedDecision =
    new ChipDecision(
        'Free Hit',
        'Use',
        0.76,
        'The projected Free Hit improvement is large enough to justify using the chip.'
    );


$decisionIntelligence->decisionResult =
    $expectedDecision;


$service =
    new FreeHitDecisionIntelligenceService(
        $currentHorizonService,
        $freeHitHorizonService,
        $decisionIntelligence
    );


$result =
    $service
        ->build(
            $importedSquad,
            $playerPool,
            99.5
        );


freeHitDecisionServiceCheck(
    'Current squad horizon is built exactly once',
    $currentHorizonService
        ->buildCallCount
    ===
    1
);


freeHitDecisionServiceCheck(
    'Imported squad is passed unchanged to current horizon service',
    $currentHorizonService
        ->receivedImportedSquad
    ===
    $importedSquad
);


freeHitDecisionServiceCheck(
    'Current squad always uses a one-gameweek horizon',
    $currentHorizonService
        ->receivedHorizon
    ===
    1
);


freeHitDecisionServiceCheck(
    'Free Hit horizon is built exactly once',
    $freeHitHorizonService
        ->buildCallCount
    ===
    1
);


freeHitDecisionServiceCheck(
    'Candidate pool is passed unchanged to Free Hit horizon service',
    $freeHitHorizonService
        ->receivedPlayers
    ===
    $playerPool
);


freeHitDecisionServiceCheck(
    'Budget is passed unchanged to Free Hit horizon service',
    abs(
        (
            $freeHitHorizonService
                ->receivedBudget
            ??
            0.0
        )
        -
        99.5
    )
    <
    0.0001
);


freeHitDecisionServiceCheck(
    'Current Starting XI projected points feed Free Hit value analysis',
    abs(
        (
            $decisionIntelligence
                ->receivedCurrentProjectedPoints
            ??
            -999.0
        )
        -
        55.0
    )
    <
    0.0001
);


freeHitDecisionServiceCheck(
    'Free Hit Starting XI projected points feed Free Hit value analysis',
    abs(
        (
            $decisionIntelligence
                ->receivedFreeHitProjectedPoints
            ??
            -999.0
        )
        -
        67.0
    )
    <
    0.0001
);


freeHitDecisionServiceCheck(
    'Projected points gain feeds Free Hit decision creation',
    abs(
        (
            $decisionIntelligence
                ->receivedProjectedPointsGain
            ??
            -999.0
        )
        -
        12.0
    )
    <
    0.0001
);


freeHitDecisionServiceCheck(
    'Decision confidence uses the weaker Starting XI projection confidence',
    abs(
        (
            $decisionIntelligence
                ->receivedConfidence
            ??
            -999.0
        )
        -
        0.76
    )
    <
    0.0001
);


freeHitDecisionServiceCheck(
    'Successful comparison returns Available',
    (
        $result[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


freeHitDecisionServiceCheck(
    'Current horizon result is exposed',
    (
        $result[
            'current_horizon_result'
        ]
        ??
        null
    )
    ===
    $currentHorizonResult
);


freeHitDecisionServiceCheck(
    'Free Hit horizon result is exposed',
    (
        $result[
            'free_hit_horizon_result'
        ]
        ??
        null
    )
    ===
    $freeHitHorizonResult
);


freeHitDecisionServiceCheck(
    'Value analysis is exposed',
    (
        $result[
            'value_result'
        ]
        ??
        null
    )
    ===
    $decisionIntelligence
        ->analysisResult
);


freeHitDecisionServiceCheck(
    'Chip decision is exposed',
    (
        $result[
            'decision'
        ]
        ??
        null
    )
    ===
    $expectedDecision
);


/*
 * ============================================================
 * SCENARIO C: CURRENT SQUAD HORIZON UNAVAILABLE
 * ============================================================
 */

freeHitDecisionServiceHeading(
    'Scenario C: Current Squad Horizon Unavailable'
);


$currentHorizonService =
    new FreeHitDecisionCurrentHorizonStub();


$currentUnavailable = [

    'status' =>
        'Unavailable'
];


$currentHorizonService->result =
    $currentUnavailable;


$freeHitHorizonService =
    new FreeHitDecisionHorizonStub();


$decisionIntelligence =
    new FreeHitDecisionIntelligenceStub();


$service =
    new FreeHitDecisionIntelligenceService(
        $currentHorizonService,
        $freeHitHorizonService,
        $decisionIntelligence
    );


$result =
    $service
        ->build(
            $importedSquad,
            $playerPool,
            100.0
        );


freeHitDecisionServiceCheck(
    'Unavailable current squad returns Unavailable',
    (
        $result[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


freeHitDecisionServiceCheck(
    'Free Hit horizon is not built when current squad horizon is unavailable',
    $freeHitHorizonService
        ->buildCallCount
    ===
    0
);


freeHitDecisionServiceCheck(
    'Decision analysis is not called when current squad horizon is unavailable',
    $decisionIntelligence
        ->analyseCallCount
    ===
    0
);


/*
 * ============================================================
 * SCENARIO D: FREE HIT HORIZON UNAVAILABLE
 * ============================================================
 */

freeHitDecisionServiceHeading(
    'Scenario D: Free Hit Horizon Unavailable'
);


$currentHorizonService =
    new FreeHitDecisionCurrentHorizonStub();


$currentHorizonService->result =
    $currentHorizonResult;


$freeHitHorizonService =
    new FreeHitDecisionHorizonStub();


$freeHitUnavailable = [

    'status' =>
        'Unavailable'
];


$freeHitHorizonService->result =
    $freeHitUnavailable;


$decisionIntelligence =
    new FreeHitDecisionIntelligenceStub();


$service =
    new FreeHitDecisionIntelligenceService(
        $currentHorizonService,
        $freeHitHorizonService,
        $decisionIntelligence
    );


$result =
    $service
        ->build(
            $importedSquad,
            $playerPool,
            100.0
        );


freeHitDecisionServiceCheck(
    'Unavailable Free Hit horizon returns Unavailable',
    (
        $result[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


freeHitDecisionServiceCheck(
    'Decision analysis is not called when Free Hit horizon is unavailable',
    $decisionIntelligence
        ->analyseCallCount
    ===
    0
);


/*
 * ============================================================
 * SCENARIO E: DIFFERENT GAMEWEEKS ARE NOT COMPARED
 * ============================================================
 */

freeHitDecisionServiceHeading(
    'Scenario E: Different Gameweeks Are Not Compared'
);


$currentHorizonService =
    new FreeHitDecisionCurrentHorizonStub();


$currentHorizonService->result =
    $currentHorizonResult;


$differentGameweekFreeHit =
    $freeHitHorizonResult;


$differentGameweekFreeHit[
    'horizon_result'
][
    'gameweeks'
] = [

    4 => [

        'gameweek' =>
            4,

        'starting_xi_projected_points' =>
            67.0,

        'starting_xi_projection_confidence' =>
            0.76
    ]
];


$freeHitHorizonService =
    new FreeHitDecisionHorizonStub();


$freeHitHorizonService->result =
    $differentGameweekFreeHit;


$decisionIntelligence =
    new FreeHitDecisionIntelligenceStub();


$service =
    new FreeHitDecisionIntelligenceService(
        $currentHorizonService,
        $freeHitHorizonService,
        $decisionIntelligence
    );


$result =
    $service
        ->build(
            $importedSquad,
            $playerPool,
            100.0
        );


freeHitDecisionServiceCheck(
    'Different represented gameweeks return Unavailable',
    (
        $result[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


freeHitDecisionServiceCheck(
    'Different gameweeks never reach value analysis',
    $decisionIntelligence
        ->analyseCallCount
    ===
    0
);


/*
 * ============================================================
 * SCENARIO F: MISSING STARTING XI PROJECTION
 * ============================================================
 */

freeHitDecisionServiceHeading(
    'Scenario F: Missing Starting XI Projection'
);


$currentMissingProjection =
    $currentHorizonResult;


unset(
    $currentMissingProjection[
        'horizon_result'
    ][
        'gameweeks'
    ][
        3
    ][
        'starting_xi_projected_points'
    ]
);


$currentHorizonService =
    new FreeHitDecisionCurrentHorizonStub();


$currentHorizonService->result =
    $currentMissingProjection;


$freeHitHorizonService =
    new FreeHitDecisionHorizonStub();


$freeHitHorizonService->result =
    $freeHitHorizonResult;


$decisionIntelligence =
    new FreeHitDecisionIntelligenceStub();


$service =
    new FreeHitDecisionIntelligenceService(
        $currentHorizonService,
        $freeHitHorizonService,
        $decisionIntelligence
    );


$result =
    $service
        ->build(
            $importedSquad,
            $playerPool,
            100.0
        );


freeHitDecisionServiceCheck(
    'Missing Starting XI projected points returns Unavailable',
    (
        $result[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


freeHitDecisionServiceCheck(
    'Missing Starting XI projected points never reach value analysis',
    $decisionIntelligence
        ->analyseCallCount
    ===
    0
);


/*
 * ============================================================
 * SCENARIO G: MISSING PROJECTION CONFIDENCE
 * ============================================================
 */

freeHitDecisionServiceHeading(
    'Scenario G: Missing Projection Confidence'
);


$freeHitMissingConfidence =
    $freeHitHorizonResult;


unset(
    $freeHitMissingConfidence[
        'horizon_result'
    ][
        'gameweeks'
    ][
        3
    ][
        'starting_xi_projection_confidence'
    ]
);


$currentHorizonService =
    new FreeHitDecisionCurrentHorizonStub();


$currentHorizonService->result =
    $currentHorizonResult;


$freeHitHorizonService =
    new FreeHitDecisionHorizonStub();


$freeHitHorizonService->result =
    $freeHitMissingConfidence;


$decisionIntelligence =
    new FreeHitDecisionIntelligenceStub();


$service =
    new FreeHitDecisionIntelligenceService(
        $currentHorizonService,
        $freeHitHorizonService,
        $decisionIntelligence
    );


$result =
    $service
        ->build(
            $importedSquad,
            $playerPool,
            100.0
        );


freeHitDecisionServiceCheck(
    'Missing Starting XI projection confidence returns Unavailable',
    (
        $result[
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable'
);


freeHitDecisionServiceCheck(
    'Missing projection confidence never creates a chip decision',
    $decisionIntelligence
        ->decisionCallCount
    ===
    0
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '<br>'
    . '============================================<br>';

echo
    'TEST SUMMARY<br>';

echo
    '============================================<br>';


echo
    'Passed: '
    . $passed
    . '<br>';


echo
    'Failed: '
    . $failed
    . '<br>';


echo
    $failed === 0
        ? 'RESULT: ALL TESTS PASSED ✅<br>'
        : 'RESULT: TESTS FAILED ❌<br>';