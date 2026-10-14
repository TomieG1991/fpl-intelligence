<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Command Centre Service Contract Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * ASSERTION HELPER
 * ============================================================
 */

function assertCommandCentreContract(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: {$message}<br>";

    } else {

        $failed++;

        echo "FAIL: {$message}<br>";
    }
}


/*
 * ============================================================
 * PLAYER INTELLIGENCE TEST DOUBLE
 * ============================================================
 */

class CommandCentrePlayerIntelligenceDouble
{
    public int $callCount =
        0;


    public array $receivedSquad =
        [];


    public ?float $receivedBank =
        null;


    public function getGameweekDecision(
        array $squad,
        float $bank = 0.0
    ): array {

        $this->callCount++;


        $this->receivedSquad =
            $squad;


        $this->receivedBank =
            $bank;


        return [

            'status' =>
                'success',

            'message' =>
                'Authoritative gameweek decision.',

            'overall_action' =>
                'hold',

            'gameweek' => [

                'status' =>
                    'success',

                'formation' =>
                    '3-4-3',

                'starting_xi' => [

                    [
                        'player_id' =>
                            101
                    ]
                ],

                'bench' => [

                    [
                        'player_id' =>
                            202
                    ]
                ]
            ],

            'captaincy' => [

                'status' =>
                    'success',

                'captain' => [

                    'player_id' =>
                        303
                ],

                'vice_captain' => [

                    'player_id' =>
                        404
                ]
            ],

            'transfers' => [

                'status' =>
                    'success',

                'recommendations' =>
                    []
            ],

            'decision' => [

                'status' =>
                    'success',

                'overall_action' =>
                    'hold'
            ]
        ];
    }
}


/*
 * ============================================================
 * SQUAD HORIZON TEST DOUBLE
 * ============================================================
 */

class CommandCentreSquadHorizonDouble
{
    public int $callCount =
        0;


    public array $receivedImportedSquad =
        [];


    public ?int $receivedHorizon =
        null;


    public ?int $receivedTargetGameweek =
        null;


    public function buildForImportedSquad(
        array $importedSquad,
        int $horizon = 3,
        ?int $targetGameweek = null
    ): array {

        $this->callCount++;


        $this->receivedImportedSquad =
            $importedSquad;


        $this->receivedHorizon =
            $horizon;


        $this->receivedTargetGameweek =
            $targetGameweek;


        return [

            'status' =>
                'Ready',

            'player_count' =>
                15,

            'players' => [

                [
                    'player_id' =>
                        501
                ]
            ],

            'horizon_result' => [

                'status' =>
                    'Ready',

                'horizon' =>
                    $horizon
            ]
        ];
    }
}


/*
 * ============================================================
 * CHIP ORCHESTRATOR TEST DOUBLE
 * ============================================================
 */

class CommandCentreChipOrchestratorDouble
{
    public int $callCount =
        0;


    public array $receivedImportedSquad =
        [];


    public array $receivedPlayers =
        [];


    public ?float $receivedBudget =
        null;


    public ?int $receivedTargetGameweek =
        null;


    public function build(
        array $importedSquad,
        array $players,
        float $budget,
        int $targetGameweek
    ): array {

        $this->callCount++;


        $this->receivedImportedSquad =
            $importedSquad;


        $this->receivedPlayers =
            $players;


        $this->receivedBudget =
            $budget;


        $this->receivedTargetGameweek =
            $targetGameweek;


        return [

            'wildcard' => [

                'status' =>
                    'Ready',

                'recommendation' =>
                    'Hold'
            ],

            'free_hit' => [

                'status' =>
                    'Ready',

                'recommendation' =>
                    'Hold'
            ],

            'bench_boost' => [

                'status' =>
                    'Ready',

                'recommendation' =>
                    'Consider'
            ],

            'triple_captain' => [

                'status' =>
                    'Ready',

                'recommendation' =>
                    'Hold'
            ]
        ];
    }
}


/*
 * ============================================================
 * A. SERVICE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "A. Service Contract<br>";
echo "============================================<br>";


assertCommandCentreContract(
    class_exists(
        'CommandCentreService'
    ),
    'CommandCentreService exists.'
);


assertCommandCentreContract(
    class_exists(
        'CommandCentreService'
    )
    &&
    method_exists(
        'CommandCentreService',
        'build'
    ),
    'CommandCentreService exposes build().'
);


/*
 * ============================================================
 * B. EMPTY COMMAND CENTRE CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "B. Empty Command Centre Contract<br>";
echo "============================================<br>";


$service =
    new CommandCentreService();


$result =
    $service
        ->build();


assertCommandCentreContract(
    is_array(
        $result
    ),
    'Command Centre build result is an array.'
);


$requiredSections = [

    'manager',
    'gameweek',
    'decision',
    'starting_xi',
    'captaincy',
    'transfers',
    'squad_horizon',
    'chips',
    'attention',
    'data_health'
];


foreach (
    $requiredSections
    as $requiredSection
) {

    assertCommandCentreContract(
        array_key_exists(
            $requiredSection,
            $result
        ),
        "Command Centre result exposes {$requiredSection}."
    );
}


/*
 * ============================================================
 * C. AUTHORITATIVE GAMEWEEK DECISION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "C. Authoritative Gameweek Decision<br>";
echo "============================================<br>";


$playerIntelligenceDouble =
    new CommandCentrePlayerIntelligenceDouble();


$service =
    new CommandCentreService(
        $playerIntelligenceDouble
    );


$squad = [

    [
        'player_id' =>
            1
    ],

    [
        'player_id' =>
            2
    ]
];


$bank =
    1.7;


$result =
    $service
        ->build(
            $squad,
            $bank
        );


assertCommandCentreContract(
    $playerIntelligenceDouble->callCount === 1,
    'Authoritative Gameweek Decision Intelligence is requested exactly once.'
);


assertCommandCentreContract(
    $playerIntelligenceDouble->receivedSquad === $squad,
    'Command Centre forwards the supplied squad unchanged.'
);


assertCommandCentreContract(
    $playerIntelligenceDouble->receivedBank === $bank,
    'Command Centre forwards the supplied bank unchanged.'
);


$expectedDecision = [

    'status' =>
        'success',

    'overall_action' =>
        'hold'
];


assertCommandCentreContract(
    (
        $result[
            'decision'
        ]
        ?? null
    )
    ===
    $expectedDecision,
    'Command Centre preserves the authoritative decision result unchanged.'
);


$expectedStartingXI = [

    'status' =>
        'success',

    'formation' =>
        '3-4-3',

    'starting_xi' => [

        [
            'player_id' =>
                101
        ]
    ],

    'bench' => [

        [
            'player_id' =>
                202
        ]
    ]
];


assertCommandCentreContract(
    (
        $result[
            'starting_xi'
        ]
        ?? null
    )
    ===
    $expectedStartingXI,
    'Command Centre reuses authoritative Starting XI evidence unchanged.'
);


$expectedCaptaincy = [

    'status' =>
        'success',

    'captain' => [

        'player_id' =>
            303
    ],

    'vice_captain' => [

        'player_id' =>
            404
    ]
];


assertCommandCentreContract(
    (
        $result[
            'captaincy'
        ]
        ?? null
    )
    ===
    $expectedCaptaincy,
    'Command Centre reuses authoritative captaincy evidence unchanged.'
);


$expectedTransfers = [

    'status' =>
        'success',

    'recommendations' =>
        []
];


assertCommandCentreContract(
    (
        $result[
            'transfers'
        ]
        ?? null
    )
    ===
    $expectedTransfers,
    'Command Centre reuses authoritative transfer evidence unchanged.'
);


/*
 * ============================================================
 * D. AUTHORITATIVE SQUAD HORIZON
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "D. Authoritative Squad Horizon<br>";
echo "============================================<br>";


$playerIntelligenceDouble =
    new CommandCentrePlayerIntelligenceDouble();


$squadHorizonDouble =
    new CommandCentreSquadHorizonDouble();


$service =
    new CommandCentreService(
        $playerIntelligenceDouble,
        $squadHorizonDouble
    );


$mappedSquad = [

    [
        'player_id' =>
            11
    ],

    [
        'player_id' =>
            12
    ]
];


$importedSquad = [

    'status' =>
        'success',

    'gameweek' =>
        6,

    'players' => [

        [
            'fpl_player_id' =>
                111
        ],

        [
            'fpl_player_id' =>
                222
        ]
    ]
];


$bank =
    2.4;


$targetGameweek =
    6;


$result =
    $service
        ->build(
            $mappedSquad,
            $bank,
            $importedSquad,
            $targetGameweek
        );


assertCommandCentreContract(
    $playerIntelligenceDouble->callCount === 1,
    'Gameweek Decision Intelligence remains requested exactly once when Squad Horizon is enabled.'
);


assertCommandCentreContract(
    $squadHorizonDouble->callCount === 1,
    'Authoritative Squad Horizon Intelligence is requested exactly once.'
);


assertCommandCentreContract(
    $squadHorizonDouble->receivedImportedSquad === $importedSquad,
    'Command Centre forwards the original imported squad to Squad Horizon unchanged.'
);


assertCommandCentreContract(
    $squadHorizonDouble->receivedHorizon === 5,
    'Command Centre requests the five-gameweek squad horizon.'
);


assertCommandCentreContract(
    $squadHorizonDouble->receivedTargetGameweek === $targetGameweek,
    'Command Centre forwards the actionable gameweek to Squad Horizon unchanged.'
);


$expectedSquadHorizon = [

    'status' =>
        'Ready',

    'player_count' =>
        15,

    'players' => [

        [
            'player_id' =>
                501
        ]
    ],

    'horizon_result' => [

        'status' =>
            'Ready',

        'horizon' =>
            5
    ]
];


assertCommandCentreContract(
    (
        $result[
            'squad_horizon'
        ]
        ?? null
    )
    ===
    $expectedSquadHorizon,
    'Command Centre preserves authoritative Squad Horizon evidence unchanged.'
);


/*
 * ============================================================
 * E. AUTHORITATIVE CHIP INTELLIGENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "E. Authoritative Chip Intelligence<br>";
echo "============================================<br>";


$playerIntelligenceDouble =
    new CommandCentrePlayerIntelligenceDouble();


$squadHorizonDouble =
    new CommandCentreSquadHorizonDouble();


$chipOrchestratorDouble =
    new CommandCentreChipOrchestratorDouble();


$service =
    new CommandCentreService(
        $playerIntelligenceDouble,
        $squadHorizonDouble,
        $chipOrchestratorDouble
    );


$mappedSquad = [

    [
        'player_id' =>
            21
    ],

    [
        'player_id' =>
            22
    ]
];


$importedSquad = [

    'status' =>
        'success',

    'gameweek' =>
        6,

    'team_value' =>
        99.8,

    'bank' =>
        1.4,

    'players' => [

        [
            'fpl_player_id' =>
                321
        ],

        [
            'fpl_player_id' =>
                654
        ]
    ]
];


$playerPool = [

    [
        'player_id' =>
            701,

        'position' =>
            'MID',

        'price' =>
            8.5
    ],

    [
        'player_id' =>
            702,

        'position' =>
            'FWD',

        'price' =>
            9.0
    ]
];


$bank =
    1.4;


$budget =
    101.2;


$targetGameweek =
    6;


$result =
    $service
        ->build(
            $mappedSquad,
            $bank,
            $importedSquad,
            $targetGameweek,
            $playerPool,
            $budget
        );


assertCommandCentreContract(
    $playerIntelligenceDouble->callCount === 1,
    'Gameweek Decision Intelligence remains requested exactly once when Chip Intelligence is enabled.'
);


assertCommandCentreContract(
    $squadHorizonDouble->callCount === 1,
    'Squad Horizon Intelligence remains requested exactly once when Chip Intelligence is enabled.'
);


assertCommandCentreContract(
    $chipOrchestratorDouble->callCount === 1,
    'Authoritative Chip Intelligence is requested exactly once.'
);


assertCommandCentreContract(
    $chipOrchestratorDouble->receivedImportedSquad === $importedSquad,
    'Command Centre forwards the original imported squad to Chip Intelligence unchanged.'
);


assertCommandCentreContract(
    $chipOrchestratorDouble->receivedPlayers === $playerPool,
    'Command Centre forwards the complete player candidate pool to Chip Intelligence unchanged.'
);


assertCommandCentreContract(
    $chipOrchestratorDouble->receivedBudget === $budget,
    'Command Centre forwards the available squad budget to Chip Intelligence unchanged.'
);


assertCommandCentreContract(
    $chipOrchestratorDouble->receivedTargetGameweek === $targetGameweek,
    'Command Centre forwards the actionable gameweek to Chip Intelligence unchanged.'
);


$expectedChips = [

    'wildcard' => [

        'status' =>
            'Ready',

        'recommendation' =>
            'Hold'
    ],

    'free_hit' => [

        'status' =>
            'Ready',

        'recommendation' =>
            'Hold'
    ],

    'bench_boost' => [

        'status' =>
            'Ready',

        'recommendation' =>
            'Consider'
    ],

    'triple_captain' => [

        'status' =>
            'Ready',

        'recommendation' =>
            'Hold'
    ]
];


assertCommandCentreContract(
    (
        $result[
            'chips'
        ]
        ?? null
    )
    ===
    $expectedChips,
    'Command Centre preserves authoritative Chip Intelligence evidence unchanged.'
);


/*
 * ============================================================
 * F. MANAGER AND GAMEWEEK CONTEXT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "F. Manager and Gameweek Context<br>";
echo "============================================<br>";


$service =
    new CommandCentreService();


$managerContext = [

    'entry_id' =>
        2702264,

    'team_name' =>
        'Command Centre Test',

    'manager_first_name' =>
        'Test',

    'manager_last_name' =>
        'Manager',

    'bank' =>
        1.4,

    'team_value' =>
        99.8
];


$gameweekContext = [

    'target_gameweek' =>
        6,

    'imported_gameweek' =>
        5,

    'status' =>
        'actionable'
];


$result =
    $service
        ->build(
            [],
            0.0,
            [],
            null,
            [],
            0.0,
            $managerContext,
            $gameweekContext
        );


assertCommandCentreContract(
    (
        $result[
            'manager'
        ]
        ?? null
    )
    ===
    $managerContext,
    'Command Centre preserves manager context unchanged.'
);


assertCommandCentreContract(
    (
        $result[
            'gameweek'
        ]
        ?? null
    )
    ===
    $gameweekContext,
    'Command Centre preserves gameweek context unchanged.'
);


/*
 * ============================================================
 * G. DATA HEALTH CONTEXT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "G. Data Health Context<br>";
echo "============================================<br>";


$service =
    new CommandCentreService();


$dataHealth = [

    'bootstrap' => [

        'status' =>
            'healthy',

        'is_fresh' =>
            true
    ],

    'fixtures' => [

        'status' =>
            'healthy',

        'is_fresh' =>
            true
    ],

    'player_fixture_history' => [

        'status' =>
            'healthy',

        'is_fresh' =>
            true
    ]
];


$result =
    $service
        ->build(
            [],
            0.0,
            [],
            null,
            [],
            0.0,
            [],
            [],
            $dataHealth
        );


assertCommandCentreContract(
    (
        $result[
            'data_health'
        ]
        ?? null
    )
    ===
    $dataHealth,
    'Command Centre preserves Data Health evidence unchanged.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Summary<br>";
echo "============================================<br>";


echo "Assertions passed: {$passed}<br>";
echo "Assertions failed: {$failed}<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}