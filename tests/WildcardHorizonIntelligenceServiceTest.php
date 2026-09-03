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

function wildcardHorizonCheck(
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


function wildcardHorizonHeading(
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

class WildcardHorizonOptimizerStub
    extends WildcardOptimizer
{
    public array $receivedPlayers =
        [];


    public ?float $receivedBudget =
        null;


    public array $result =
        [];


    public function optimize(
        array $players,
        float $budget = 100.0
    ): array {

        $this->receivedPlayers =
            $players;


        $this->receivedBudget =
            $budget;


        return
            $this->result;
    }
}


class WildcardHorizonSquadServiceStub
    extends SquadHorizonIntelligenceService
{
    public array $receivedPlayers =
        [];


    public int $buildCallCount =
        0;


    public ?int $receivedHorizon =
        null;


    public array $result =
        [];


    public function __construct()
    {
        /*
         * Parent dependencies are deliberately unnecessary for
         * this orchestration test.
         */
    }


        public function buildForResolvedSquad(
            array $resolvedPlayers,
            int $horizon = 3
        ): array {

            $this->buildCallCount++;


            $this->receivedPlayers =
                $resolvedPlayers;


        $this->receivedHorizon =
            $horizon;


        return
            $this->result;
    }
}


/*
 * ============================================================
 * TEST DATA
 * ============================================================
 */

$playerPool = [

    [
        'player_id' => 101,
        'name' => 'Candidate One'
    ],

    [
        'player_id' => 102,
        'name' => 'Candidate Two'
    ]
];


$optimizedSquad =
    [];


for (
    $playerNumber = 1;
    $playerNumber <= 15;
    $playerNumber++
) {

    $optimizedSquad[] = [

        'player_id' =>
            $playerNumber,

        'name' =>
            'Wildcard Player '
            . $playerNumber,

        'team_id' =>
            (($playerNumber - 1) % 5)
            + 1,

        'position' =>
            $playerNumber <= 2
                ? 'GK'
                : (
                    $playerNumber <= 7
                        ? 'DEF'
                        : (
                            $playerNumber <= 12
                                ? 'MID'
                                : 'FWD'
                        )
                ),

        'price' =>
            5.0,

        'wildcard_score' =>
            70.0 + $playerNumber
    ];
}


$expectedHorizon = [

    'status' =>
        'Available',

    'horizon' =>
        3,

    'projection_confidence' =>
        0.81,

    'gameweeks' =>
        [
            1 => [
                'gameweek' => 1,
                'starting_xi_projected_points' => 55.0
            ],

            2 => [
                'gameweek' => 2,
                'starting_xi_projected_points' => 57.0
            ],

            3 => [
                'gameweek' => 3,
                'starting_xi_projected_points' => 59.0
            ]
        ]
];


/*
 * ============================================================
 * Scenario A: Successful optimizer result is adapted and sent
 *             through resolved-squad horizon pipeline
 * ============================================================
 */

wildcardHorizonHeading(
    'Scenario A: Successful Wildcard squad builds horizon'
);


$optimizer =
    new WildcardHorizonOptimizerStub();


$optimizer->result = [

    'status' =>
        'success',

    'squad' =>
        $optimizedSquad
];


$squadService =
    new WildcardHorizonSquadServiceStub();


$squadService->result = [

    'status' =>
        'Available',

    'player_count' =>
        15,

    'players' =>
        [],

    'horizon_result' =>
        $expectedHorizon
];


$service =
    new WildcardHorizonIntelligenceService(
        $optimizer,
        $squadService
    );


$result =
    $service->build(
        $playerPool,
        99.5,
        3
    );


wildcardHorizonCheck(
    'Player pool is passed unchanged to Wildcard Optimizer',
    $optimizer->receivedPlayers
    ===
    $playerPool
);


wildcardHorizonCheck(
    'Budget is passed unchanged to Wildcard Optimizer',
    $optimizer->receivedBudget
    ===
    99.5
);


wildcardHorizonCheck(
    'Resolved-squad horizon receives fifteen players',
    count(
        $squadService->receivedPlayers
    )
    ===
    15
);


wildcardHorizonCheck(
    'Optimizer player_id becomes resolved local id',
    (
        $squadService->receivedPlayers[0][
            'id'
        ]
        ??
        null
    )
    ===
    1
);


wildcardHorizonCheck(
    'Optimizer player name is preserved',
    (
        $squadService->receivedPlayers[0][
            'name'
        ]
        ??
        null
    )
    ===
    'Wildcard Player 1'
);


wildcardHorizonCheck(
    'Optimizer team ID is preserved',
    (
        $squadService->receivedPlayers[0][
            'team_id'
        ]
        ??
        null
    )
    ===
    1
);


wildcardHorizonCheck(
    'Optimizer position is preserved',
    (
        $squadService->receivedPlayers[0][
            'position'
        ]
        ??
        null
    )
    ===
    'GK'
);


wildcardHorizonCheck(
    'Optimizer diagnostic fields remain available',
    (
        $squadService->receivedPlayers[0][
            'wildcard_score'
        ]
        ??
        null
    )
    ===
    71.0
);


wildcardHorizonCheck(
    'Requested horizon is passed unchanged',
    $squadService->receivedHorizon
    ===
    3
);


wildcardHorizonCheck(
    'Successful build is Available',
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


wildcardHorizonCheck(
    'Optimizer result is exposed by orchestration result',
    (
        $result[
            'optimizer_result'
        ]
        ??
        null
    )
    ===
    $optimizer->result
);


wildcardHorizonCheck(
    'Wildcard horizon is exposed by orchestration result',
    (
        $result[
            'horizon_result'
        ]
        ??
        null
    )
    ===
    $expectedHorizon
);


/*
 * ============================================================
 * Scenario B: Non-success optimizer result stops orchestration
 * ============================================================
 */

wildcardHorizonHeading(
    'Scenario B: Failed Wildcard optimization stops horizon build'
);


$optimizer =
    new WildcardHorizonOptimizerStub();


$optimizer->result = [

    'status' =>
        'failure',

    /*
     * A squad is deliberately present so this scenario tests
     * optimizer status handling only.
     *
     * Missing or invalid optimizer squad data will be covered
     * by a separate edge-case test.
     */
    'squad' =>
        $optimizedSquad
];


$squadService =
    new WildcardHorizonSquadServiceStub();


$squadService->result = [

    'status' =>
        'Available',

    'player_count' =>
        15,

    'players' =>
        [],

    'horizon_result' =>
        $expectedHorizon
];


$service =
    new WildcardHorizonIntelligenceService(
        $optimizer,
        $squadService
    );


$result =
    $service->build(
        $playerPool,
        99.5,
        3
    );


wildcardHorizonCheck(
    'Failed Wildcard optimization makes build Unavailable',
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


wildcardHorizonCheck(
    'Failed optimizer result is exposed by orchestration result',
    (
        $result[
            'optimizer_result'
        ]
        ??
        null
    )
    ===
    $optimizer->result
);


wildcardHorizonCheck(
    'Failed Wildcard optimization does not build squad horizon',
    $squadService->buildCallCount
    ===
    0
);


/*
 * ============================================================
 * Scenario C: Successful optimizer status without squad is
 *             unavailable
 * ============================================================
 */

wildcardHorizonHeading(
    'Scenario C: Successful optimizer without squad is unavailable'
);


$optimizer =
    new WildcardHorizonOptimizerStub();


$optimizer->result = [

    'status' =>
        'success'
];


$squadService =
    new WildcardHorizonSquadServiceStub();


$service =
    new WildcardHorizonIntelligenceService(
        $optimizer,
        $squadService
    );


$result =
    $service->build(
        $playerPool,
        99.5,
        3
    );


wildcardHorizonCheck(
    'Missing optimizer squad makes build Unavailable',
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


wildcardHorizonCheck(
    'Missing optimizer squad still exposes optimizer result',
    (
        $result[
            'optimizer_result'
        ]
        ??
        null
    )
    ===
    $optimizer->result
);


wildcardHorizonCheck(
    'Missing optimizer squad does not build squad horizon',
    $squadService->buildCallCount
    ===
    0
);


/*
 * ============================================================
 * Scenario D: Successful optimizer with empty squad is
 *             unavailable
 * ============================================================
 */

wildcardHorizonHeading(
    'Scenario D: Successful optimizer with empty squad is unavailable'
);


$optimizer =
    new WildcardHorizonOptimizerStub();


$optimizer->result = [

    'status' =>
        'success',

    'squad' =>
        []
];


$squadService =
    new WildcardHorizonSquadServiceStub();


$service =
    new WildcardHorizonIntelligenceService(
        $optimizer,
        $squadService
    );


$result =
    $service->build(
        $playerPool,
        99.5,
        3
    );


wildcardHorizonCheck(
    'Empty optimizer squad makes build Unavailable',
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


wildcardHorizonCheck(
    'Empty optimizer squad still exposes optimizer result',
    (
        $result[
            'optimizer_result'
        ]
        ??
        null
    )
    ===
    $optimizer->result
);


wildcardHorizonCheck(
    'Empty optimizer squad does not build squad horizon',
    $squadService->buildCallCount
    ===
    0
);


/*
 * ============================================================
 * Scenario E: Successful optimizer with incomplete squad is
 *             unavailable
 * ============================================================
 */

wildcardHorizonHeading(
    'Scenario E: Successful optimizer with fourteen-player squad is unavailable'
);


$incompleteOptimizedSquad =
    array_slice(
        $optimizedSquad,
        0,
        14
    );


$optimizer =
    new WildcardHorizonOptimizerStub();


$optimizer->result = [

    'status' =>
        'success',

    'squad' =>
        $incompleteOptimizedSquad
];


$squadService =
    new WildcardHorizonSquadServiceStub();


$service =
    new WildcardHorizonIntelligenceService(
        $optimizer,
        $squadService
    );


$result =
    $service->build(
        $playerPool,
        99.5,
        3
    );


wildcardHorizonCheck(
    'Fourteen-player optimizer squad makes build Unavailable',
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


wildcardHorizonCheck(
    'Fourteen-player optimizer squad still exposes optimizer result',
    (
        $result[
            'optimizer_result'
        ]
        ??
        null
    )
    ===
    $optimizer->result
);


wildcardHorizonCheck(
    'Fourteen-player optimizer squad does not build squad horizon',
    $squadService->buildCallCount
    ===
    0
);


/*
 * ============================================================
 * Scenario F: Optimizer squad with missing player_id is
 *             unavailable
 * ============================================================
 */

wildcardHorizonHeading(
    'Scenario F: Optimizer squad with missing player_id is unavailable'
);


$missingPlayerIdSquad =
    $optimizedSquad;


unset(
    $missingPlayerIdSquad[0][
        'player_id'
    ]
);


$optimizer =
    new WildcardHorizonOptimizerStub();


$optimizer->result = [

    'status' =>
        'success',

    'squad' =>
        $missingPlayerIdSquad
];


$squadService =
    new WildcardHorizonSquadServiceStub();


$service =
    new WildcardHorizonIntelligenceService(
        $optimizer,
        $squadService
    );


$result =
    $service->build(
        $playerPool,
        99.5,
        3
    );


wildcardHorizonCheck(
    'Missing optimizer player_id makes build Unavailable',
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


wildcardHorizonCheck(
    'Missing optimizer player_id still exposes optimizer result',
    (
        $result[
            'optimizer_result'
        ]
        ??
        null
    )
    ===
    $optimizer->result
);


wildcardHorizonCheck(
    'Missing optimizer player_id does not build squad horizon',
    $squadService->buildCallCount
    ===
    0
);


/*
 * ============================================================
 * Scenario G: Optimizer squad with duplicate player_id is
 *             unavailable
 * ============================================================
 */

wildcardHorizonHeading(
    'Scenario G: Optimizer squad with duplicate player_id is unavailable'
);


$duplicatePlayerIdSquad =
    $optimizedSquad;


$duplicatePlayerIdSquad[1][
    'player_id'
] =
    $duplicatePlayerIdSquad[0][
        'player_id'
    ];


$optimizer =
    new WildcardHorizonOptimizerStub();


$optimizer->result = [

    'status' =>
        'success',

    'squad' =>
        $duplicatePlayerIdSquad
];


$squadService =
    new WildcardHorizonSquadServiceStub();


$service =
    new WildcardHorizonIntelligenceService(
        $optimizer,
        $squadService
    );


$result =
    $service->build(
        $playerPool,
        99.5,
        3
    );


wildcardHorizonCheck(
    'Duplicate optimizer player_id makes build Unavailable',
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


wildcardHorizonCheck(
    'Duplicate optimizer player_id still exposes optimizer result',
    (
        $result[
            'optimizer_result'
        ]
        ??
        null
    )
    ===
    $optimizer->result
);


wildcardHorizonCheck(
    'Duplicate optimizer player_id does not build squad horizon',
    $squadService->buildCallCount
    ===
    0
);


/*
 * ============================================================
 * Scenario H: Horizon pipeline unavailable is propagated
 * ============================================================
 */

wildcardHorizonHeading(
    'Scenario H: Wildcard squad horizon unavailable'
);


$optimizer =
    new WildcardHorizonOptimizerStub();


$optimizer->result = [

    'status' =>
        'success',

    'squad' =>
        $optimizedSquad
];


$squadService =
    new WildcardHorizonSquadServiceStub();


$squadService->result = [

    'status' =>
        'Unavailable',

    'reason' =>
        'Projection pipeline unavailable'
];


$service =
    new WildcardHorizonIntelligenceService(
        $optimizer,
        $squadService
    );


$result =
    $service->build(
        $playerPool,
        99.5,
        3
    );


wildcardHorizonCheck(
    'Unavailable horizon makes Wildcard build Unavailable',
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


wildcardHorizonCheck(
    'Unavailable horizon is still called once for valid optimizer squad',
    $squadService->buildCallCount
    ===
    1
);


wildcardHorizonCheck(
    'Unavailable horizon still exposes optimizer result',
    (
        $result[
            'optimizer_result'
        ]
        ??
        null
    )
    ===
    $optimizer->result
);


wildcardHorizonCheck(
    'Unavailable horizon exposes no projected horizon result',
    (
        $result[
            'horizon_result'
        ]
        ??
        null
    )
    ===
    null
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '<br>'
    . '============================================<br>'
    . 'TEST SUMMARY<br>'
    . '============================================<br>'
    . 'Passed: '
    . $passed
    . '<br>'
    . 'Failed: '
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