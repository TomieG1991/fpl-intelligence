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

function freeHitIntelligenceServiceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


function freeHitIntelligenceServiceHeading(
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


function buildFreeHitServiceCandidatePool(): array
{
    $players =
        [];


    /*
     * --------------------------------------------------------
     * GOALKEEPERS
     * --------------------------------------------------------
     */

    for (
        $i = 1;
        $i <= 2;
        $i++
    ) {

        $players[] = [
            'player_id' =>
                $i,

            'name' =>
                'Goalkeeper '
                . $i,

            'position' =>
                'GK',

            'team_id' =>
                $i,

            'price' =>
                5.0
        ];
    }


    /*
     * --------------------------------------------------------
     * DEFENDERS
     * --------------------------------------------------------
     */

    for (
        $i = 3;
        $i <= 7;
        $i++
    ) {

        $players[] = [
            'player_id' =>
                $i,

            'name' =>
                'Defender '
                . $i,

            'position' =>
                'DEF',

            'team_id' =>
                $i,

            'price' =>
                5.0
        ];
    }


    /*
     * --------------------------------------------------------
     * MIDFIELDERS
     * --------------------------------------------------------
     */

    for (
        $i = 8;
        $i <= 12;
        $i++
    ) {

        $players[] = [
            'player_id' =>
                $i,

            'name' =>
                'Midfielder '
                . $i,

            'position' =>
                'MID',

            'team_id' =>
                $i,

            'price' =>
                5.0
        ];
    }


    /*
     * --------------------------------------------------------
     * FORWARDS
     * --------------------------------------------------------
     */

    for (
        $i = 13;
        $i <= 15;
        $i++
    ) {

        $players[] = [
            'player_id' =>
                $i,

            'name' =>
                'Forward '
                . $i,

            'position' =>
                'FWD',

            'team_id' =>
                $i,

            'price' =>
                5.0
        ];
    }


    return
        $players;
}


function buildFreeHitServiceProjectionResults(
    array $players,
    int $gameweek = 3
): array {

    $results =
        [];


    foreach (
        $players
        as $player
    ) {

        $playerId =
            (int) (
                $player[
                    'player_id'
                ]
                ?? 0
            );


        if (
            $playerId <= 0
        ) {

            continue;
        }


        $results[
            $playerId
        ] = [

            'status' =>
                'Available',

            'gameweeks' =>
                [

                    $gameweek => [

                        'gameweek' =>
                            $gameweek,

                        'projected_points' =>
                            5.0
                            +
                            (
                                $playerId
                                /
                                10
                            ),

                        'projection_confidence' =>
                            0.80
                    ],

                    $gameweek + 1 => [

                        'gameweek' =>
                            $gameweek + 1,

                        'projected_points' =>
                            50.0
                            +
                            $playerId,

                        'projection_confidence' =>
                            0.80
                    ]
                ]
        ];
    }


    return
        $results;
}


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class FreeHitServicePlayerIntelligenceStub
    extends PlayerIntelligenceService
{
    public array $results =
        [];


    public array $calls =
        [];


    public function __construct()
    {
        /*
         * Parent database dependencies are deliberately
         * unnecessary for this orchestration test.
         */
    }


    public function getPlayerMultiGameweekExpectedPoints(
        int $playerId,
        int $fixtureLimit = 6
    ): array {

        $this->calls[] = [

            'player_id' =>
                $playerId,

            'fixture_limit' =>
                $fixtureLimit
        ];


        return
            $this->results[
                $playerId
            ]
            ?? [];
    }
}


class FreeHitServiceOptimizerStub
    extends FreeHitOptimizer
{
    public array $receivedPlayers =
        [];


    public ?float $receivedBudget =
        null;


    public int $callCount =
        0;


    public array $result =
        [];


    public function optimize(
        array $players = [],
        float $budget = 100.0
    ): array {

        $this->callCount++;


        $this->receivedPlayers =
            $players;


        $this->receivedBudget =
            $budget;


        return
            $this->result;
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
    'Free Hit Intelligence Service Test<br>';

echo
    '============================================<br>';


/*
 * ============================================================
 * SCENARIO A: SERVICE CONTRACT
 * ============================================================
 */

freeHitIntelligenceServiceHeading(
    'Scenario A: Free Hit Intelligence Service Contract'
);


$serviceClassExists =
    class_exists(
        'FreeHitIntelligenceService'
    );


freeHitIntelligenceServiceCheck(
    'FreeHitIntelligenceService class exists',
    $serviceClassExists
);


$serviceHasBuildMethod =
    false;


if (
    $serviceClassExists
) {

    $serviceReflection =
        new ReflectionClass(
            'FreeHitIntelligenceService'
        );


    $serviceHasBuildMethod =
        $serviceReflection
            ->hasMethod(
                'build'
            );
}


freeHitIntelligenceServiceCheck(
    'FreeHitIntelligenceService exposes a build method',
    $serviceHasBuildMethod
);


/*
 * The remaining scenarios describe the complete initial service
 * contract.
 *
 * Until the production class exists they cannot safely execute,
 * so Scenario A remains the first RED boundary.
 */
if (
    !$serviceClassExists
    ||
    !$serviceHasBuildMethod
) {

    echo
        '<br>'
        . 'Remaining service scenarios skipped until '
        . 'FreeHitIntelligenceService contract exists.<br>';


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


    exit;
}


/*
 * ============================================================
 * SCENARIO B: EXISTING EXPECTED POINTS FEED THE OPTIMIZER
 * ============================================================
 */

freeHitIntelligenceServiceHeading(
    'Scenario B: Existing Expected Points Feed The Optimizer'
);


$candidatePool =
    buildFreeHitServiceCandidatePool();


$playerIntelligenceStub =
    new FreeHitServicePlayerIntelligenceStub();


$playerIntelligenceStub->results =
    buildFreeHitServiceProjectionResults(
        $candidatePool,
        3
    );


$optimizerStub =
    new FreeHitServiceOptimizerStub();


$optimizerStub->result = [

    'status' =>
        'success',

    'squad' =>
        $candidatePool
];


$service =
    new FreeHitIntelligenceService(
        $playerIntelligenceStub,
        $optimizerStub
    );


$result =
    $service
        ->build(
            $candidatePool,
            99.5
        );


freeHitIntelligenceServiceCheck(
    'Free Hit service can be instantiated',
    $service
    instanceof
    FreeHitIntelligenceService
);


freeHitIntelligenceServiceCheck(
    'Every valid candidate requests an Expected Points projection',
    count(
        $playerIntelligenceStub->calls
    )
    ===
    15
);


$allProjectionCallsUseSixFixtureLimit =
    true;


foreach (
    $playerIntelligenceStub->calls
    as $call
) {

    if (
        (
            $call[
                'fixture_limit'
            ]
            ?? null
        )
        !==
        6
    ) {

        $allProjectionCallsUseSixFixtureLimit =
            false;


        break;
    }
}


freeHitIntelligenceServiceCheck(
    'Projection requests retain the six-fixture DGW-safe source horizon',
    $allProjectionCallsUseSixFixtureLimit
);


freeHitIntelligenceServiceCheck(
    'Optimizer is called exactly once',
    $optimizerStub->callCount
    ===
    1
);


freeHitIntelligenceServiceCheck(
    'Available budget is passed unchanged to the optimizer',
    $optimizerStub->receivedBudget
    ===
    99.5
);


freeHitIntelligenceServiceCheck(
    'All projected candidates are passed to the optimizer',
    count(
        $optimizerStub->receivedPlayers
    )
    ===
    15
);


$firstProjectedCandidate =
    $optimizerStub->receivedPlayers[
        0
    ]
    ?? [];


freeHitIntelligenceServiceCheck(
    'Existing candidate identity is preserved',
    (
        $firstProjectedCandidate[
            'player_id'
        ]
        ?? null
    )
    ===
    1
);


freeHitIntelligenceServiceCheck(
    'Existing candidate position is preserved',
    (
        $firstProjectedCandidate[
            'position'
        ]
        ?? null
    )
    ===
    'GK'
);


freeHitIntelligenceServiceCheck(
    'Existing candidate price is preserved',
    (
        $firstProjectedCandidate[
            'price'
        ]
        ?? null
    )
    ===
    5.0
);


freeHitIntelligenceServiceCheck(
    'Earliest represented gameweek projected points are adapted for the optimizer',
    abs(
        (
            (float) (
                $firstProjectedCandidate[
                    'projected_points'
                ]
                ?? -999.0
            )
        )
        -
        5.1
    )
    <
    0.0001
);


freeHitIntelligenceServiceCheck(
    'Successful optimizer result produces Available service status',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


freeHitIntelligenceServiceCheck(
    'Successful optimizer result is preserved by the service',
    (
        $result[
            'optimizer_result'
        ]
        ?? null
    )
    ===
    $optimizerStub->result
);


/*
 * ============================================================
 * SCENARIO C: LATER GAMEWEEKS DO NOT DRIVE FREE HIT SELECTION
 * ============================================================
 */

freeHitIntelligenceServiceHeading(
    'Scenario C: Later Gameweeks Do Not Drive Free Hit Selection'
);


$playerIntelligenceStub =
    new FreeHitServicePlayerIntelligenceStub();


$playerIntelligenceStub->results =
    buildFreeHitServiceProjectionResults(
        $candidatePool,
        3
    );


/*
 * Player 1 is deliberately much stronger in GW4 than GW3.
 *
 * Free Hit selection for the immediate represented gameweek
 * must still use GW3 only.
 */
$playerIntelligenceStub->results[
    1
][
    'gameweeks'
][
    3
][
    'projected_points'
] =
    4.0;


$playerIntelligenceStub->results[
    1
][
    'gameweeks'
][
    4
][
    'projected_points'
] =
    40.0;


$optimizerStub =
    new FreeHitServiceOptimizerStub();


$optimizerStub->result = [

    'status' =>
        'success',

    'squad' =>
        $candidatePool
];


$service =
    new FreeHitIntelligenceService(
        $playerIntelligenceStub,
        $optimizerStub
    );


$service
    ->build(
        $candidatePool,
        100.0
    );


$playerOneProjectedPoints =
    null;


foreach (
    $optimizerStub->receivedPlayers
    as $projectedCandidate
) {

    if (
        (
            $projectedCandidate[
                'player_id'
            ]
            ?? null
        )
        ===
        1
    ) {

        $playerOneProjectedPoints =
            $projectedCandidate[
                'projected_points'
            ]
            ?? null;


        break;
    }
}


freeHitIntelligenceServiceCheck(
    'Free Hit uses the earliest represented gameweek rather than a later horizon total',
    is_numeric(
        $playerOneProjectedPoints
    )
    &&
    abs(
        (float) $playerOneProjectedPoints
        -
        4.0
    )
    <
    0.0001
);


/*
 * ============================================================
 * SCENARIO D: DOUBLE GAMEWEEK AGGREGATION IS PRESERVED
 * ============================================================
 */

freeHitIntelligenceServiceHeading(
    'Scenario D: Double Gameweek Aggregation Is Preserved'
);


$playerIntelligenceStub =
    new FreeHitServicePlayerIntelligenceStub();


$playerIntelligenceStub->results =
    buildFreeHitServiceProjectionResults(
        $candidatePool,
        3
    );


/*
 * MultiGameweekExpectedPoints already owns DGW fixture
 * aggregation.
 *
 * The Free Hit service must preserve its complete gameweek
 * projected-points value rather than attempting to recalculate
 * or split it.
 */
$playerIntelligenceStub->results[
    1
][
    'gameweeks'
][
    3
] = [

    'gameweek' =>
        3,

    'schedule_type' =>
        'Double',

    'fixture_count' =>
        2,

    'projected_points' =>
        12.4,

    'projection_confidence' =>
        0.78,

    'fixtures' =>
        [

            [
                'fixture_id' => 101,
                'projected_points' => 5.9
            ],

            [
                'fixture_id' => 102,
                'projected_points' => 6.5
            ]
        ]
];


$optimizerStub =
    new FreeHitServiceOptimizerStub();


$optimizerStub->result = [

    'status' =>
        'success',

    'squad' =>
        $candidatePool
];


$service =
    new FreeHitIntelligenceService(
        $playerIntelligenceStub,
        $optimizerStub
    );


$service
    ->build(
        $candidatePool,
        100.0
    );


$dgwProjectedPoints =
    null;


foreach (
    $optimizerStub->receivedPlayers
    as $projectedCandidate
) {

    if (
        (
            $projectedCandidate[
                'player_id'
            ]
            ?? null
        )
        ===
        1
    ) {

        $dgwProjectedPoints =
            $projectedCandidate[
                'projected_points'
            ]
            ?? null;


        break;
    }
}


freeHitIntelligenceServiceCheck(
    'Already-aggregated Double Gameweek projected points are preserved exactly',
    is_numeric(
        $dgwProjectedPoints
    )
    &&
    abs(
        (float) $dgwProjectedPoints
        -
        12.4
    )
    <
    0.0001
);


/*
 * ============================================================
 * SCENARIO E: UNSUPPORTED PROJECTION IS NOT INVENTED
 * ============================================================
 */

freeHitIntelligenceServiceHeading(
    'Scenario E: Unsupported Projection Is Not Invented'
);


$playerIntelligenceStub =
    new FreeHitServicePlayerIntelligenceStub();


$playerIntelligenceStub->results =
    buildFreeHitServiceProjectionResults(
        $candidatePool,
        3
    );


/*
 * Player 15 has no usable gameweek projection.
 *
 * The service must not manufacture zero points or another
 * replacement value. The unsupported candidate should simply
 * not reach the optimizer.
 */
$playerIntelligenceStub->results[
    15
] = [

    'status' =>
        'Unavailable',

    'gameweeks' =>
        []
];


$optimizerStub =
    new FreeHitServiceOptimizerStub();


$optimizerStub->result = [

    'status' =>
        'invalid',

    'message' =>
        'Free Hit player pool does not contain enough players.'
];


$service =
    new FreeHitIntelligenceService(
        $playerIntelligenceStub,
        $optimizerStub
    );


$result =
    $service
        ->build(
            $candidatePool,
            100.0
        );


$optimizerPlayerIds =
    [];


foreach (
    $optimizerStub->receivedPlayers
    as $projectedCandidate
) {

    $optimizerPlayerIds[] =
        (int) (
            $projectedCandidate[
                'player_id'
            ]
            ?? 0
        );
}


freeHitIntelligenceServiceCheck(
    'Unsupported projection candidate is excluded from optimizer input',
    !in_array(
        15,
        $optimizerPlayerIds,
        true
    )
);


freeHitIntelligenceServiceCheck(
    'Remaining supported candidates still reach the optimizer',
    count(
        $optimizerStub->receivedPlayers
    )
    ===
    14
);


freeHitIntelligenceServiceCheck(
    'Optimizer failure after projection filtering produces Unavailable service status',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * ============================================================
 * SCENARIO F: INVALID PLAYER ID IS NEVER PROJECTED
 * ============================================================
 */

freeHitIntelligenceServiceHeading(
    'Scenario F: Invalid Player ID Is Never Projected'
);


$invalidIdCandidatePool =
    $candidatePool;


$invalidIdCandidatePool[] = [

    'player_id' =>
        0,

    'name' =>
        'Invalid Candidate',

    'position' =>
        'MID',

    'team_id' =>
        20,

    'price' =>
        4.5
];


$playerIntelligenceStub =
    new FreeHitServicePlayerIntelligenceStub();


$playerIntelligenceStub->results =
    buildFreeHitServiceProjectionResults(
        $candidatePool,
        3
    );


$optimizerStub =
    new FreeHitServiceOptimizerStub();


$optimizerStub->result = [

    'status' =>
        'success',

    'squad' =>
        $candidatePool
];


$service =
    new FreeHitIntelligenceService(
        $playerIntelligenceStub,
        $optimizerStub
    );


$service
    ->build(
        $invalidIdCandidatePool,
        100.0
    );


$invalidProjectionRequested =
    false;


foreach (
    $playerIntelligenceStub->calls
    as $call
) {

    if (
        (
            $call[
                'player_id'
            ]
            ?? null
        )
        <=
        0
    ) {

        $invalidProjectionRequested =
            true;


        break;
    }
}


freeHitIntelligenceServiceCheck(
    'Invalid player ID never reaches Player Intelligence projection pipeline',
    !$invalidProjectionRequested
);


freeHitIntelligenceServiceCheck(
    'Invalid player ID is excluded from optimizer input',
    count(
        $optimizerStub->receivedPlayers
    )
    ===
    15
);


/*
 * ============================================================
 * SCENARIO G: OPTIMIZER FAILURE IS PRESERVED
 * ============================================================
 */

freeHitIntelligenceServiceHeading(
    'Scenario G: Optimizer Failure Is Preserved'
);


$playerIntelligenceStub =
    new FreeHitServicePlayerIntelligenceStub();


$playerIntelligenceStub->results =
    buildFreeHitServiceProjectionResults(
        $candidatePool,
        3
    );


$optimizerStub =
    new FreeHitServiceOptimizerStub();


$optimizerStub->result = [

    'status' =>
        'invalid',

    'message' =>
        'No legal Free Hit squad could be generated.'
];


$service =
    new FreeHitIntelligenceService(
        $playerIntelligenceStub,
        $optimizerStub
    );


$result =
    $service
        ->build(
            $candidatePool,
            100.0
        );


freeHitIntelligenceServiceCheck(
    'Optimizer failure produces Unavailable service status',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


freeHitIntelligenceServiceCheck(
    'Optimizer failure result is preserved for explanation',
    (
        $result[
            'optimizer_result'
        ]
        ?? null
    )
    ===
    $optimizerStub->result
);


/*
 * ============================================================
 * SCENARIO H: NO USABLE PROJECTION EVIDENCE
 * ============================================================
 */

freeHitIntelligenceServiceHeading(
    'Scenario H: No Usable Projection Evidence'
);


$playerIntelligenceStub =
    new FreeHitServicePlayerIntelligenceStub();


foreach (
    $candidatePool
    as $candidate
) {

    $playerId =
        (int) (
            $candidate[
                'player_id'
            ]
            ?? 0
        );


    $playerIntelligenceStub->results[
        $playerId
    ] = [

        'status' =>
            'Unavailable',

        'gameweeks' =>
            []
    ];
}


$optimizerStub =
    new FreeHitServiceOptimizerStub();


$optimizerStub->result = [

    'status' =>
        'success',

    'squad' =>
        []
];


$service =
    new FreeHitIntelligenceService(
        $playerIntelligenceStub,
        $optimizerStub
    );


$result =
    $service
        ->build(
            $candidatePool,
            100.0
        );


freeHitIntelligenceServiceCheck(
    'No usable projection evidence returns Unavailable',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


freeHitIntelligenceServiceCheck(
    'Optimizer is not called when no candidate has usable projection evidence',
    $optimizerStub->callCount
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


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}