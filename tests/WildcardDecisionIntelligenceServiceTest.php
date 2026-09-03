<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardDecisionServiceCheck(
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


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class WildcardDecisionSquadHorizonStub
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


class WildcardDecisionWildcardHorizonStub
    extends
    WildcardHorizonIntelligenceService
{
    public int
        $buildCallCount = 0;


    public array
        $receivedPlayers = [];


    public ?float
        $receivedBudget = null;


    public ?int
        $receivedHorizon = null;


    public array
        $result = [];


    public function __construct()
    {
    }


    public function build(
        array $players,
        float $budget,
        int $horizon
    ): array {

        $this->buildCallCount++;


        $this->receivedPlayers =
            $players;


        $this->receivedBudget =
            $budget;


        $this->receivedHorizon =
            $horizon;


        return
            $this->result;
    }
}


class WildcardDecisionTimingServiceStub
    extends
    WildcardTimingIntelligenceService
{
    public int
        $analyseCallCount = 0;


    public array
        $receivedCurrentHorizon = [];


    public array
        $receivedWildcardHorizon = [];


    public array
        $result = [];


    public function __construct()
    {
    }


    public function analyseHorizons(
        array $currentHorizon,
        array $wildcardHorizon
    ): array {

        $this->analyseCallCount++;


        $this->receivedCurrentHorizon =
            $currentHorizon;


        $this->receivedWildcardHorizon =
            $wildcardHorizon;


        return
            $this->result;
    }
}


echo
    '============================================<br>';

echo
    'Wildcard Decision Intelligence Service Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


/*
 * ============================================================
 * Scenario A: Successful Wildcard decision orchestration
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Successful Wildcard decision orchestration<br>';

echo
    '============================================<br>';


$importedSquad = [

    'status' =>
        'success',

    'players' => [

        [
            'fpl_player_id' =>
                101
        ],

        [
            'fpl_player_id' =>
                102
        ]
    ]
];


$playerPool = [

    [
        'id' =>
            1,

        'name' =>
            'Candidate One'
    ],

    [
        'id' =>
            2,

        'name' =>
            'Candidate Two'
    ]
];


$currentHorizon = [

    'status' =>
        'Available',

    'horizon' =>
        3,

    'projection_confidence' =>
        0.82,

    'gameweeks' =>
        []
];


$wildcardHorizon = [

    'status' =>
        'Available',

    'horizon' =>
        3,

    'projection_confidence' =>
        0.76,

    'gameweeks' =>
        []
];


$currentHorizonBuild = [

    'status' =>
        'Available',

    'player_count' =>
        15,

    'players' =>
        [],

    'horizon_result' =>
        $currentHorizon
];


$wildcardHorizonBuild = [

    'status' =>
        'Available',

    'optimizer_result' => [

        'status' =>
            'success'
    ],

    'horizon_result' =>
        $wildcardHorizon
];


$expectedTimingResult = [

    'status' =>
        'Available',

    'current_squad_projected_points' =>
        165.0,

    'wildcard_squad_projected_points' =>
        183.0,

    'projected_points_gain' =>
        18.0,

    'improves_squad' =>
        true,

    'decision' =>
        null
];


$squadHorizonService =
    new WildcardDecisionSquadHorizonStub();


$squadHorizonService->result =
    $currentHorizonBuild;


$wildcardHorizonService =
    new WildcardDecisionWildcardHorizonStub();


$wildcardHorizonService->result =
    $wildcardHorizonBuild;


$timingService =
    new WildcardDecisionTimingServiceStub();


$timingService->result =
    $expectedTimingResult;


$service =
    new WildcardDecisionIntelligenceService(
        $squadHorizonService,
        $wildcardHorizonService,
        $timingService
    );


$result =
    $service
        ->build(
            $importedSquad,
            $playerPool,
            99.5,
            3
        );


wildcardDecisionServiceCheck(
    'Current squad horizon is built once',
    $squadHorizonService
        ->buildCallCount
    ===
    1
);


wildcardDecisionServiceCheck(
    'Imported squad is passed unchanged to current squad horizon',
    $squadHorizonService
        ->receivedImportedSquad
    ===
    $importedSquad
);


wildcardDecisionServiceCheck(
    'Requested horizon is passed to current squad horizon',
    $squadHorizonService
        ->receivedHorizon
    ===
    3
);



wildcardDecisionServiceCheck(
    'Wildcard horizon is built once',
    $wildcardHorizonService
        ->buildCallCount
    ===
    1
);


wildcardDecisionServiceCheck(
    'Candidate player pool is passed unchanged to Wildcard horizon',
    $wildcardHorizonService
        ->receivedPlayers
    ===
    $playerPool
);


wildcardDecisionServiceCheck(
    'Budget is passed unchanged to Wildcard horizon',
    abs(
        (
            $wildcardHorizonService
                ->receivedBudget
            ??
            0.0
        )
        -
        99.5
    ) < 0.0001
);


wildcardDecisionServiceCheck(
    'Requested horizon is passed to Wildcard horizon',
    $wildcardHorizonService
        ->receivedHorizon
    ===
    3
);


wildcardDecisionServiceCheck(
    'Timing comparison is performed once',
    $timingService
        ->analyseCallCount
    ===
    1
);


wildcardDecisionServiceCheck(
    'Timing service receives current projected horizon',
    $timingService
        ->receivedCurrentHorizon
    ===
    $currentHorizon
);


wildcardDecisionServiceCheck(
    'Timing service receives Wildcard projected horizon',
    $timingService
        ->receivedWildcardHorizon
    ===
    $wildcardHorizon
);


wildcardDecisionServiceCheck(
    'Successful orchestration is Available',
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


wildcardDecisionServiceCheck(
    'Current squad horizon build is exposed',
    (
        $result[
            'current_horizon_result'
        ]
        ??
        null
    )
    ===
    $currentHorizonBuild
);


wildcardDecisionServiceCheck(
    'Wildcard horizon build is exposed',
    (
        $result[
            'wildcard_horizon_result'
        ]
        ??
        null
    )
    ===
    $wildcardHorizonBuild
);


wildcardDecisionServiceCheck(
    'Timing result is exposed',
    (
        $result[
            'timing_result'
        ]
        ??
        null
    )
    ===
    $expectedTimingResult
);


echo
    '<br>';
    
    
/*
 * ============================================================
 * Scenario B: Current squad horizon unavailable
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Current squad horizon unavailable<br>';

echo
    '============================================<br>';


$unavailableCurrentHorizonBuild = [

    'status' =>
        'Unavailable',

    'player_count' =>
        0,

    'players' =>
        [],

    'horizon_result' =>
        null
];


$squadHorizonService =
    new WildcardDecisionSquadHorizonStub();


$squadHorizonService->result =
    $unavailableCurrentHorizonBuild;


$wildcardHorizonService =
    new WildcardDecisionWildcardHorizonStub();


$wildcardHorizonService->result = [

    'status' =>
        'Available',

    'horizon_result' => [

        'status' =>
            'Available'
    ]
];


$timingService =
    new WildcardDecisionTimingServiceStub();


$service =
    new WildcardDecisionIntelligenceService(
        $squadHorizonService,
        $wildcardHorizonService,
        $timingService
    );


$result =
    $service
        ->build(
            $importedSquad,
            $playerPool,
            99.5,
            3
        );


wildcardDecisionServiceCheck(
    'Unavailable current squad horizon is built once',
    $squadHorizonService
        ->buildCallCount
    ===
    1
);


wildcardDecisionServiceCheck(
    'Wildcard horizon is not built when current squad horizon is unavailable',
    $wildcardHorizonService
        ->buildCallCount
    ===
    0
);


wildcardDecisionServiceCheck(
    'Timing comparison is not performed when current squad horizon is unavailable',
    $timingService
        ->analyseCallCount
    ===
    0
);


wildcardDecisionServiceCheck(
    'Unavailable current squad horizon returns Unavailable',
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


wildcardDecisionServiceCheck(
    'Unavailable current squad horizon result is exposed',
    (
        $result[
            'current_horizon_result'
        ]
        ??
        null
    )
    ===
    $unavailableCurrentHorizonBuild
);


wildcardDecisionServiceCheck(
    'Wildcard horizon result is null when current squad horizon is unavailable',
    array_key_exists(
        'wildcard_horizon_result',
        $result
    )
    &&
    $result[
        'wildcard_horizon_result'
    ]
    ===
    null
);


wildcardDecisionServiceCheck(
    'Timing result is null when current squad horizon is unavailable',
    array_key_exists(
        'timing_result',
        $result
    )
    &&
    $result[
        'timing_result'
    ]
    ===
    null
);


echo
    '<br>';
    
    
/*
 * ============================================================
 * Scenario C: Wildcard horizon unavailable
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Wildcard horizon unavailable<br>';

echo
    '============================================<br>';


$availableCurrentHorizonBuild = [

    'status' =>
        'Available',

    'player_count' =>
        15,

    'players' =>
        [],

    'horizon_result' => [

        'status' =>
            'Available',

        'horizon' =>
            3,

        'projection_confidence' =>
            0.82,

        'gameweeks' =>
            []
    ]
];


$unavailableWildcardHorizonBuild = [

    'status' =>
        'Unavailable',

    'optimizer_result' => [

        'status' =>
            'failed'
    ],

    'horizon_result' =>
        null
];


$squadHorizonService =
    new WildcardDecisionSquadHorizonStub();


$squadHorizonService->result =
    $availableCurrentHorizonBuild;


$wildcardHorizonService =
    new WildcardDecisionWildcardHorizonStub();


$wildcardHorizonService->result =
    $unavailableWildcardHorizonBuild;


$timingService =
    new WildcardDecisionTimingServiceStub();


$service =
    new WildcardDecisionIntelligenceService(
        $squadHorizonService,
        $wildcardHorizonService,
        $timingService
    );


$result =
    $service
        ->build(
            $importedSquad,
            $playerPool,
            99.5,
            3
        );


wildcardDecisionServiceCheck(
    'Available current squad horizon is built once before Wildcard failure',
    $squadHorizonService
        ->buildCallCount
    ===
    1
);


wildcardDecisionServiceCheck(
    'Unavailable Wildcard horizon is built once',
    $wildcardHorizonService
        ->buildCallCount
    ===
    1
);


wildcardDecisionServiceCheck(
    'Timing comparison is not performed when Wildcard horizon is unavailable',
    $timingService
        ->analyseCallCount
    ===
    0
);


wildcardDecisionServiceCheck(
    'Unavailable Wildcard horizon returns Unavailable',
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


wildcardDecisionServiceCheck(
    'Current squad horizon result is exposed when Wildcard horizon is unavailable',
    (
        $result[
            'current_horizon_result'
        ]
        ??
        null
    )
    ===
    $availableCurrentHorizonBuild
);


wildcardDecisionServiceCheck(
    'Unavailable Wildcard horizon result is exposed',
    (
        $result[
            'wildcard_horizon_result'
        ]
        ??
        null
    )
    ===
    $unavailableWildcardHorizonBuild
);


wildcardDecisionServiceCheck(
    'Timing result is null when Wildcard horizon is unavailable',
    array_key_exists(
        'timing_result',
        $result
    )
    &&
    $result[
        'timing_result'
    ]
    ===
    null
);


echo
    '<br>';
    
    
/*
 * ============================================================
 * Scenario D: Timing comparison unavailable
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Timing comparison unavailable<br>';

echo
    '============================================<br>';


$availableCurrentHorizonBuild = [

    'status' =>
        'Available',

    'player_count' =>
        15,

    'players' =>
        [],

    'horizon_result' => [

        'status' =>
            'Available',

        'horizon' =>
            3,

        'projection_confidence' =>
            0.82,

        'gameweeks' =>
            []
    ]
];


$availableWildcardHorizonBuild = [

    'status' =>
        'Available',

    'optimizer_result' => [

        'status' =>
            'success'
    ],

    'horizon_result' => [

        'status' =>
            'Available',

        'horizon' =>
            3,

        'projection_confidence' =>
            0.76,

        'gameweeks' =>
            []
    ]
];


$unavailableTimingResult = [

    'status' =>
        'Unavailable',

    'reason' =>
        'Projected horizons cannot be compared.',

    'current_squad_projected_points' =>
        null,

    'wildcard_squad_projected_points' =>
        null,

    'projected_points_gain' =>
        null,

    'improves_squad' =>
        null,

    'decision' =>
        null
];


$squadHorizonService =
    new WildcardDecisionSquadHorizonStub();


$squadHorizonService->result =
    $availableCurrentHorizonBuild;


$wildcardHorizonService =
    new WildcardDecisionWildcardHorizonStub();


$wildcardHorizonService->result =
    $availableWildcardHorizonBuild;


$timingService =
    new WildcardDecisionTimingServiceStub();


$timingService->result =
    $unavailableTimingResult;


$service =
    new WildcardDecisionIntelligenceService(
        $squadHorizonService,
        $wildcardHorizonService,
        $timingService
    );


$result =
    $service
        ->build(
            $importedSquad,
            $playerPool,
            99.5,
            3
        );


wildcardDecisionServiceCheck(
    'Current squad horizon is built once before timing failure',
    $squadHorizonService
        ->buildCallCount
    ===
    1
);


wildcardDecisionServiceCheck(
    'Wildcard horizon is built once before timing failure',
    $wildcardHorizonService
        ->buildCallCount
    ===
    1
);


wildcardDecisionServiceCheck(
    'Timing comparison is performed once when both horizons are available',
    $timingService
        ->analyseCallCount
    ===
    1
);


wildcardDecisionServiceCheck(
    'Unavailable timing comparison returns Unavailable',
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


wildcardDecisionServiceCheck(
    'Current squad horizon result is exposed when timing comparison is unavailable',
    (
        $result[
            'current_horizon_result'
        ]
        ??
        null
    )
    ===
    $availableCurrentHorizonBuild
);


wildcardDecisionServiceCheck(
    'Wildcard horizon result is exposed when timing comparison is unavailable',
    (
        $result[
            'wildcard_horizon_result'
        ]
        ??
        null
    )
    ===
    $availableWildcardHorizonBuild
);


wildcardDecisionServiceCheck(
    'Unavailable timing result is exposed',
    (
        $result[
            'timing_result'
        ]
        ??
        null
    )
    ===
    $unavailableTimingResult
);


echo
    '<br>';


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '============================================<br>';

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


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}