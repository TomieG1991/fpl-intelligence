<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Intelligence Service Test<br>";
echo "v0.32.0 — Production Service Contract<br>";
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

function squadHorizonServiceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        $passed++;

        return;
    }


    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';

    $failed++;
}


function squadHorizonServiceHeading(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "============================================<br>";
}


/*
 * ============================================================
 * CONTRACT AVAILABILITY
 * ============================================================
 */

squadHorizonServiceHeading(
    'Scenario A: Service Availability'
);


$serviceClassExists =
    class_exists(
        'SquadHorizonIntelligenceService'
    );


squadHorizonServiceCheck(
    'SquadHorizonIntelligenceService class exists',
    $serviceClassExists
);


/*
 * ============================================================
 * STOP CLEANLY DURING INITIAL RED STAGE
 * ============================================================
 *
 * The production class intentionally does not exist yet.
 *
 * Do not attempt to instantiate a missing class because that
 * would turn the expected RED contract into a fatal PHP error.
 */

if (
    !$serviceClassExists
) {

    echo "<br>";
    echo "============================================<br>";
    echo "TEST SUMMARY<br>";
    echo "============================================<br>";

    echo
        'Passed: '
        . $passed
        . '<br>';

    echo
        'Failed: '
        . $failed
        . '<br><br>';

    echo
        'RESULT: TESTS FAILED ❌';

    exit;
}


/*
 * ============================================================
 * FUTURE CONTRACT GUARD
 * ============================================================
 *
 * Once the production class exists, the next controlled test
 * stage will define its constructor and buildForImportedSquad()
 * contract.
 */

$buildMethodExists =
    method_exists(
        'SquadHorizonIntelligenceService',
        'buildForImportedSquad'
    );


squadHorizonServiceCheck(
    'Service exposes buildForImportedSquad()',
    $buildMethodExists
);

/*
 * ============================================================
 * SCENARIO B: INVALID IMPORTED SQUAD
 * ============================================================
 */

squadHorizonServiceHeading(
    'Scenario B: Invalid Imported Squad'
);


/*
 * Construct the service without invoking its production
 * constructor.
 *
 * Scenario B deliberately tests only the input-validation
 * boundary. Invalid input returns before any collaborator is
 * required.
 */

$serviceReflection =
    new ReflectionClass(
        'SquadHorizonIntelligenceService'
    );


$service =
    $serviceReflection
        ->newInstanceWithoutConstructor();


$invalidResult =
    $service->buildForImportedSquad(
        [],
        3
    );


squadHorizonServiceCheck(
    'Invalid imported squad returns an array',
    is_array(
        $invalidResult
    )
);


squadHorizonServiceCheck(
    'Invalid imported squad is unavailable',
    (
        $invalidResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


squadHorizonServiceCheck(
    'Invalid imported squad reports zero players',
    (
        $invalidResult[
            'player_count'
        ]
        ?? null
    )
    ===
    0
);


squadHorizonServiceCheck(
    'Invalid imported squad returns no players',
    (
        $invalidResult[
            'players'
        ]
        ?? null
    )
    ===
    []
);


squadHorizonServiceCheck(
    'Invalid imported squad returns no horizon result',
    (
        $invalidResult[
            'horizon_result'
        ]
        ?? null
    )
    ===
    null
);

/*
 * ============================================================
 * SCENARIO C: PRODUCTION DEPENDENCIES
 * ============================================================
 */

squadHorizonServiceHeading(
    'Scenario C: Production Dependencies'
);


$constructor =
    new ReflectionMethod(
        'SquadHorizonIntelligenceService',
        '__construct'
    );


$constructorParameters =
    $constructor->getParameters();


squadHorizonServiceCheck(
    'Service constructor has exactly three dependencies',
    count(
        $constructorParameters
    )
    ===
    3
);


$expectedConstructorTypes = [

    'PlayerRepository',

    'PlayerIntelligenceService',

    'SquadHorizonIntelligence'
];


$actualConstructorTypes =
    [];


foreach (
    $constructorParameters
    as $parameter
) {

    $type =
        $parameter->getType();


    $actualConstructorTypes[] =
        $type instanceof ReflectionNamedType
            ? $type->getName()
            : null;
}


squadHorizonServiceCheck(
    'Service constructor receives PlayerRepository first',
    (
        $actualConstructorTypes[
            0
        ]
        ?? null
    )
    ===
    'PlayerRepository'
);


squadHorizonServiceCheck(
    'Service constructor receives PlayerIntelligenceService second',
    (
        $actualConstructorTypes[
            1
        ]
        ?? null
    )
    ===
    'PlayerIntelligenceService'
);


squadHorizonServiceCheck(
    'Service constructor receives SquadHorizonIntelligence third',
    (
        $actualConstructorTypes[
            2
        ]
        ?? null
    )
    ===
    'SquadHorizonIntelligence'
);


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 *
 * These lightweight test doubles isolate the production
 * orchestration service from the database, Expected Points
 * calculations and Squad Horizon model.
 *
 * They are shared by the service orchestration scenarios
 * below.
 */

class SquadHorizonServicePlayerRepositoryStub
    extends PlayerRepository
{
    private array
        $playersByFplId;


    public array
        $requestedFplPlayerIds =
            [];


    public function __construct(
        array $playersByFplId
    ) {
        $this->playersByFplId =
            $playersByFplId;
    }


    public function getByFplPlayerId(
        int $fplPlayerId
    ): ?array {
        $this->requestedFplPlayerIds[] =
            $fplPlayerId;


        return
            $this->playersByFplId[
                $fplPlayerId
            ]
            ?? null;
    }
}


class SquadHorizonServicePlayerIntelligenceStub
    extends PlayerIntelligenceService
{
    public array
        $requestedPlayerIds =
            [];


    public array
        $requestedFixtureLimits =
            [];


    private array
        $projectionsByPlayerId;


    public function __construct(
        array $projectionsByPlayerId = []
    ) {
        $this->projectionsByPlayerId =
            $projectionsByPlayerId;
    }


    public function getPlayerMultiGameweekExpectedPoints(
        int $playerId,
        int $fixtureLimit = 6
    ): array {
        $this->requestedPlayerIds[] =
            $playerId;


        $this->requestedFixtureLimits[] =
            $fixtureLimit;


        return
            $this->projectionsByPlayerId[
                $playerId
            ]
            ??
            [

                'status' =>
                    'Available',

                'player_id' =>
                    $playerId,

                'gameweeks' =>
                    []
            ];
    }
}


class SquadHorizonServiceModelStub
    extends SquadHorizonIntelligence
{
    public array
        $receivedSquad =
            [];


    public ?int
        $receivedHorizon =
            null;


    public function buildHorizon(
        array $squad,
        int $horizon
    ): array {
        $this->receivedSquad =
            $squad;


        $this->receivedHorizon =
            $horizon;


        return [

            'status' =>
                'Available',

            'adapter_test' =>
                true
        ];
    }
}


/*
 * ============================================================
 * SCENARIO D: RESOLVE IMPORTED PLAYERS
 * ============================================================
 */

squadHorizonServiceHeading(
    'Scenario D: Resolve Imported Players'
);


/*
 * Build a complete 15-player imported squad and matching
 * local player records.
 *
 * This allows the test to pass through the production
 * 15-player squad boundary and isolate player resolution.
 */

$repositoryPlayers =
    [];


$importedPlayers =
    [];


for (
    $playerNumber = 1;
    $playerNumber <= 15;
    $playerNumber++
) {

    $fplPlayerId =
        100
        +
        $playerNumber;


    $position =
        match (true) {

            $playerNumber <= 2 =>
                'GK',

            $playerNumber <= 7 =>
                'DEF',

            $playerNumber <= 12 =>
                'MID',

            default =>
                'FWD'
        };


    $repositoryPlayers[
        $fplPlayerId
    ] = [

        'id' =>
            $playerNumber,

        'fpl_player_id' =>
            $fplPlayerId,

        'web_name' =>
            'Player '
            . $playerNumber,

        'position' =>
            $position,

        'team_id' =>
            $playerNumber
    ];


    $importedPlayers[] = [

        'fpl_player_id' =>
            $fplPlayerId,

        'squad_position' =>
            $playerNumber
    ];
}


$repositoryStub =
    new SquadHorizonServicePlayerRepositoryStub(
        $repositoryPlayers
    );


$playerIntelligenceStub =
    new SquadHorizonServicePlayerIntelligenceStub();


$squadHorizonModelStub =
    new SquadHorizonServiceModelStub();


$service =
    new SquadHorizonIntelligenceService(
        $repositoryStub,
        $playerIntelligenceStub,
        $squadHorizonModelStub
    );


$importedSquad = [

    'status' =>
        'success',

    'players' =>
        $importedPlayers
];


$result =
    $service->buildForImportedSquad(
        $importedSquad,
        3
    );


$expectedRequestedFplPlayerIds =
    [];


for (
    $playerNumber = 1;
    $playerNumber <= 15;
    $playerNumber++
) {

    $expectedRequestedFplPlayerIds[] =
        100
        +
        $playerNumber;
}


squadHorizonServiceCheck(
    'Service requests each imported FPL player ID',
    $repositoryStub->requestedFplPlayerIds
    ===
    $expectedRequestedFplPlayerIds
);


squadHorizonServiceCheck(
    'Resolved result reports fifteen players',
    (
        $result[
            'player_count'
        ]
        ?? null
    )
    ===
    15
);


squadHorizonServiceCheck(
    'Resolved result contains fifteen player rows',
    count(
        $result[
            'players'
        ]
        ?? []
    )
    ===
    15
);


squadHorizonServiceCheck(
    'First resolved player preserves local player ID',
    (
        $result[
            'players'
        ][0][
            'id'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonServiceCheck(
    'Last resolved player preserves local player ID',
    (
        $result[
            'players'
        ][14][
            'id'
        ]
        ?? null
    )
    ===
    15
);

/*
 * ============================================================
 * SCENARIO E: UNRESOLVED IMPORTED PLAYER
 * ============================================================
 */

squadHorizonServiceHeading(
    'Scenario E: Unresolved Imported Player'
);


/*
 * Use the same complete 15-player import from Scenario D,
 * but deliberately remove the final player from the local
 * repository.
 *
 * The imported squad is structurally complete, so the
 * service must attempt resolution before discovering that
 * one player cannot be mapped locally.
 */

$partialRepositoryPlayers =
    $repositoryPlayers;


unset(
    $partialRepositoryPlayers[
        115
    ]
);


$partialRepositoryStub =
    new SquadHorizonServicePlayerRepositoryStub(
        $partialRepositoryPlayers
    );


$partialService =
    new SquadHorizonIntelligenceService(
        $partialRepositoryStub,
        $playerIntelligenceStub,
        $squadHorizonModelStub
    );


$partialResult =
    $partialService->buildForImportedSquad(
        $importedSquad,
        3
    );


squadHorizonServiceCheck(
    'Service still attempts to resolve every imported player',
    $partialRepositoryStub->requestedFplPlayerIds
    ===
    $expectedRequestedFplPlayerIds
);


squadHorizonServiceCheck(
    'Partially resolved squad is unavailable',
    (
        $partialResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


squadHorizonServiceCheck(
    'Partial result reports imported player count',
    (
        $partialResult[
            'imported_player_count'
        ]
        ?? null
    )
    ===
    15
);


squadHorizonServiceCheck(
    'Partial result reports resolved player count',
    (
        $partialResult[
            'resolved_player_count'
        ]
        ?? null
    )
    ===
    14
);


squadHorizonServiceCheck(
    'Partial result identifies unresolved FPL player ID',
    (
        $partialResult[
            'unresolved_fpl_player_ids'
        ]
        ?? null
    )
    ===
    [
        115
    ]
);


squadHorizonServiceCheck(
    'Partial result does not expose an incomplete player squad',
    (
        $partialResult[
            'players'
        ]
        ?? null
    )
    ===
    []
);


squadHorizonServiceCheck(
    'Partial result does not build horizon intelligence',
    (
        $partialResult[
            'horizon_result'
        ]
        ?? null
    )
    ===
    null
);


/*
 * ============================================================
 * SCENARIO F: FULL SQUAD INTEGRITY
 * ============================================================
 */

squadHorizonServiceHeading(
    'Scenario F: Full Squad Integrity'
);


/*
 * A production FPL squad must contain exactly 15 imported
 * player rows before Squad Horizon Intelligence can proceed.
 */

$incompleteImportedSquad = [

    'status' =>
        'success',

    'players' =>
        []
];


for (
    $playerNumber = 1;
    $playerNumber <= 14;
    $playerNumber++
) {

    $incompleteImportedSquad[
        'players'
    ][] = [

        'fpl_player_id' =>
            1000
            +
            $playerNumber,

        'squad_position' =>
            $playerNumber
    ];
}


$incompleteRepositoryStub =
    new SquadHorizonServicePlayerRepositoryStub(
        []
    );


$incompleteService =
    new SquadHorizonIntelligenceService(
        $incompleteRepositoryStub,
        $playerIntelligenceStub,
        $squadHorizonModelStub
    );


$incompleteResult =
    $incompleteService->buildForImportedSquad(
        $incompleteImportedSquad,
        3
    );


squadHorizonServiceCheck(
    'Fourteen-player imported squad is unavailable',
    (
        $incompleteResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


squadHorizonServiceCheck(
    'Invalid squad reports imported player count',
    (
        $incompleteResult[
            'imported_player_count'
        ]
        ?? null
    )
    ===
    14
);


squadHorizonServiceCheck(
    'Invalid squad requires fifteen players',
    (
        $incompleteResult[
            'required_player_count'
        ]
        ?? null
    )
    ===
    15
);


squadHorizonServiceCheck(
    'Invalid squad does not attempt player resolution',
    $incompleteRepositoryStub->requestedFplPlayerIds
    ===
    []
);


squadHorizonServiceCheck(
    'Invalid squad exposes no players',
    (
        $incompleteResult[
            'players'
        ]
        ?? null
    )
    ===
    []
);


squadHorizonServiceCheck(
    'Invalid squad does not build horizon intelligence',
    (
        $incompleteResult[
            'horizon_result'
        ]
        ?? null
    )
    ===
    null
);


/*
 * ============================================================
 * SCENARIO G: REQUEST MULTI-GAMEWEEK PROJECTIONS
 * ============================================================
 */

squadHorizonServiceHeading(
    'Scenario G: Request Multi-Gameweek Projections'
);


$projectionRepositoryStub =
    new SquadHorizonServicePlayerRepositoryStub(
        $repositoryPlayers
    );


$projectionPlayerIntelligenceStub =
    new SquadHorizonServicePlayerIntelligenceStub();


$projectionModelStub =
    new SquadHorizonServiceModelStub();


$projectionService =
    new SquadHorizonIntelligenceService(
        $projectionRepositoryStub,
        $projectionPlayerIntelligenceStub,
        $projectionModelStub
    );


$projectionResult =
    $projectionService->buildForImportedSquad(
        $importedSquad,
        3
    );


$expectedLocalPlayerIds =
    [];


for (
    $playerNumber = 1;
    $playerNumber <= 15;
    $playerNumber++
) {

    $expectedLocalPlayerIds[] =
        $playerNumber;
}


squadHorizonServiceCheck(
    'Projection service is called for every resolved player',
    $projectionPlayerIntelligenceStub->requestedPlayerIds
    ===
    $expectedLocalPlayerIds
);


squadHorizonServiceCheck(
    'Projection service receives fifteen player requests',
    count(
        $projectionPlayerIntelligenceStub
            ->requestedPlayerIds
    )
    ===
    15
);


squadHorizonServiceCheck(
    'Projection requests use existing six-fixture limit',
    $projectionPlayerIntelligenceStub
        ->requestedFixtureLimits
    ===
    array_fill(
        0,
        15,
        6
    )
);


squadHorizonServiceCheck(
    'Projection stage still preserves fifteen resolved players',
    (
        $projectionResult[
            'player_count'
        ]
        ?? null
    )
    ===
    15
);


/*
 * ============================================================
 * SCENARIO H: ADAPT MULTI-GAMEWEEK PROJECTIONS
 * ============================================================
 */

squadHorizonServiceHeading(
    'Scenario H: Adapt Multi-Gameweek Projections'
);


$adapterProjections =
    [];


for (
    $playerNumber = 1;
    $playerNumber <= 15;
    $playerNumber++
) {

    $adapterProjections[
        $playerNumber
    ] = [

        'status' =>
            'Available',

        'player_id' =>
            $playerNumber,

        'gameweeks' =>
            [

                2 => [

                    'gameweek' =>
                        2,

                    'projected_points' =>
                        2.0
                        +
                        $playerNumber
                ],

                3 => [

                    'gameweek' =>
                        3,

                    'projected_points' =>
                        3.0
                        +
                        $playerNumber
                ],

                4 => [

                    'gameweek' =>
                        4,

                    'projected_points' =>
                        4.0
                        +
                        $playerNumber
                ]
            ]
    ];
}


$adapterRepositoryStub =
    new SquadHorizonServicePlayerRepositoryStub(
        $repositoryPlayers
    );


$adapterPlayerIntelligenceStub =
    new SquadHorizonServicePlayerIntelligenceStub(
        $adapterProjections
    );


$adapterModelStub =
    new SquadHorizonServiceModelStub();


$adapterService =
    new SquadHorizonIntelligenceService(
        $adapterRepositoryStub,
        $adapterPlayerIntelligenceStub,
        $adapterModelStub
    );


$adapterResult =
    $adapterService->buildForImportedSquad(
        $importedSquad,
        3
    );


squadHorizonServiceCheck(
    'Squad Horizon model receives fifteen adapted players',
    count(
        $adapterModelStub
            ->receivedSquad
    )
    ===
    15
);


squadHorizonServiceCheck(
    'Adapted player preserves local player ID',
    (
        $adapterModelStub
            ->receivedSquad[0][
                'player_id'
            ]
        ?? null
    )
    ===
    1
);


squadHorizonServiceCheck(
    'Adapted player preserves player name',
    (
        $adapterModelStub
            ->receivedSquad[0][
                'name'
            ]
        ?? null
    )
    ===
    'Player 1'
);


squadHorizonServiceCheck(
    'Adapted player preserves position',
    (
        $adapterModelStub
            ->receivedSquad[0][
                'position'
            ]
        ?? null
    )
    ===
    'GK'
);


squadHorizonServiceCheck(
    'Adapted player preserves team ID',
    (
        $adapterModelStub
            ->receivedSquad[0][
                'team_id'
            ]
        ?? null
    )
    ===
    1
);


squadHorizonServiceCheck(
    'Adapted player contains three projected gameweeks',
    count(
        $adapterModelStub
            ->receivedSquad[0][
                'gameweeks'
            ]
        ?? []
    )
    ===
    3
);


squadHorizonServiceCheck(
    'GW2 projected points come directly from multi-gameweek projection',
    (
        $adapterModelStub
            ->receivedSquad[0][
                'gameweeks'
            ][2][
                'projected_points'
            ]
        ?? null
    )
    ===
    3.0
);


squadHorizonServiceCheck(
    'GW4 projected points come directly from multi-gameweek projection',
    (
        $adapterModelStub
            ->receivedSquad[0][
                'gameweeks'
            ][4][
                'projected_points'
            ]
        ?? null
    )
    ===
    5.0
);


squadHorizonServiceCheck(
    'Requested horizon is passed to Squad Horizon model',
    $adapterModelStub
        ->receivedHorizon
    ===
    3
);


squadHorizonServiceCheck(
    'Service exposes Squad Horizon model result',
    (
        $adapterResult[
            'horizon_result'
        ][
            'adapter_test'
        ]
        ?? null
    )
    ===
    true
);


/*
 * ============================================================
 * SCENARIO I: FIXTURE METADATA ADAPTATION
 * ============================================================
 */

squadHorizonServiceHeading(
    'Scenario I: Fixture Metadata Adaptation'
);


$fixtureMetadataProjections =
    [];


for (
    $playerNumber = 1;
    $playerNumber <= 15;
    $playerNumber++
) {

    $fixtureMetadataProjections[
    $playerNumber
] = [

    'status' =>
        'Available',

    'player_id' =>
        $playerNumber,

    /*
     * PlayerIntelligenceService exposes fixture metadata at
     * the top level of the multi-gameweek response.
     */

    'fixtures' => [

        [
            'fixture_id' =>
                200 + $playerNumber,

            'gameweek' =>
                2,

            'opponent_team_id' =>
                20
        ],

        [
            'fixture_id' =>
                300 + $playerNumber,

            'gameweek' =>
                3,

            'opponent_team_id' =>
                18
        ],

        [
            'fixture_id' =>
                400 + $playerNumber,

            'gameweek' =>
                3,

            'opponent_team_id' =>
                19
        ],

        [
            'fixture_id' =>
                500 + $playerNumber,

            'gameweek' =>
                4,

            'opponent_team_id' =>
                17
        ]
    ],

    /*
     * MultiGameweekExpectedPoints remains the source of truth
     * for the aggregated gameweek projected-points totals.
     */

    'gameweeks' => [

        2 => [

            'gameweek' =>
                2,

            'fixture_count' =>
                1,

            'projected_points' =>
                5.0
        ],

        3 => [

            'gameweek' =>
                3,

            'fixture_count' =>
                2,

            'projected_points' =>
                6.0
        ],

        4 => [

            'gameweek' =>
                4,

            'fixture_count' =>
                1,

            'projected_points' =>
                4.0
        ]
    ]
];
}


$fixtureMetadataRepositoryStub =
    new SquadHorizonServicePlayerRepositoryStub(
        $repositoryPlayers
    );


$fixtureMetadataPlayerIntelligenceStub =
    new SquadHorizonServicePlayerIntelligenceStub(
        $fixtureMetadataProjections
    );


$fixtureMetadataModelStub =
    new SquadHorizonServiceModelStub();


$fixtureMetadataService =
    new SquadHorizonIntelligenceService(
        $fixtureMetadataRepositoryStub,
        $fixtureMetadataPlayerIntelligenceStub,
        $fixtureMetadataModelStub
    );


$fixtureMetadataResult =
    $fixtureMetadataService
        ->buildForImportedSquad(
            $importedSquad,
            3
        );


$fixtureMetadataPlayer =
    $fixtureMetadataModelStub
        ->receivedSquad[0]
        ?? [];


squadHorizonServiceCheck(
    'Single-fixture GW preserves player team ID',
    (
        $fixtureMetadataPlayer[
            'gameweeks'
        ][2][
            'team_id'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonServiceCheck(
    'Single-fixture GW preserves opponent team ID',
    (
        $fixtureMetadataPlayer[
            'gameweeks'
        ][2][
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    20
);


squadHorizonServiceCheck(
    'Second single-fixture GW preserves opponent team ID',
    (
        $fixtureMetadataPlayer[
            'gameweeks'
        ][4][
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    17
);


squadHorizonServiceCheck(
    'Double Gameweek preserves player team ID',
    (
        $fixtureMetadataPlayer[
            'gameweeks'
        ][3][
            'team_id'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonServiceCheck(
    'Double Gameweek does not invent a single opponent',
    array_key_exists(
        'opponent_team_id',
        $fixtureMetadataPlayer[
            'gameweeks'
        ][3]
        ?? []
    )
    &&
    $fixtureMetadataPlayer[
        'gameweeks'
    ][3][
        'opponent_team_id'
    ]
    ===
    null
);


squadHorizonServiceCheck(
    'Fixture metadata does not alter GW2 projected points',
    (
        $fixtureMetadataPlayer[
            'gameweeks'
        ][2][
            'projected_points'
        ]
        ?? null
    )
    ===
    5.0
);


squadHorizonServiceCheck(
    'Fixture metadata does not alter DGW projected points',
    (
        $fixtureMetadataPlayer[
            'gameweeks'
        ][3][
            'projected_points'
        ]
        ?? null
    )
    ===
    6.0
);


/*
 * ============================================================
 * SCENARIO J: MISSING PROJECTION DATA
 * ============================================================
 */

squadHorizonServiceHeading(
    'Scenario J: Missing Projection Data'
);


$missingProjectionData =
    $adapterProjections;


/*
 * Player 1 has a known GW2 projection of exactly zero.
 *
 * Player 2 has an explicit GW2 row, but its projection is
 * unavailable.
 *
 * Player 3 has no GW3 projection row at all.
 */

$missingProjectionData[1][
    'gameweeks'
][2][
    'projected_points'
] =
    0.0;


$missingProjectionData[2][
    'gameweeks'
][2][
    'projected_points'
] =
    null;


unset(
    $missingProjectionData[3][
        'gameweeks'
    ][3]
);


$missingProjectionRepositoryStub =
    new SquadHorizonServicePlayerRepositoryStub(
        $repositoryPlayers
    );


$missingProjectionPlayerIntelligenceStub =
    new SquadHorizonServicePlayerIntelligenceStub(
        $missingProjectionData
    );


$missingProjectionModelStub =
    new SquadHorizonServiceModelStub();


$missingProjectionService =
    new SquadHorizonIntelligenceService(
        $missingProjectionRepositoryStub,
        $missingProjectionPlayerIntelligenceStub,
        $missingProjectionModelStub
    );


$missingProjectionResult =
    $missingProjectionService
        ->buildForImportedSquad(
            $importedSquad,
            3
        );


$missingProjectionSquad =
    $missingProjectionModelStub
        ->receivedSquad;


/*
 * Known zero must remain numeric zero.
 */

squadHorizonServiceCheck(
    'Known zero projection remains zero',
    (
        $missingProjectionSquad[0][
            'gameweeks'
        ][2][
            'projected_points'
        ]
        ?? null
    )
    ===
    0.0
);


/*
 * Explicit unavailable projection must remain null.
 *
 * array_key_exists() is important here because ?? cannot
 * distinguish a present null value from a missing key.
 */

squadHorizonServiceCheck(
    'Unavailable projection retains projected points key',
    array_key_exists(
        'projected_points',
        $missingProjectionSquad[1][
            'gameweeks'
        ][2]
        ?? []
    )
);


squadHorizonServiceCheck(
    'Unavailable projection remains null',
    $missingProjectionSquad[1][
        'gameweeks'
    ][2][
        'projected_points'
    ]
    ===
    null
);


/*
 * A genuinely absent gameweek must not be fabricated by the
 * adapter. SquadHorizonIntelligence itself is responsible
 * for representing that missing projection as null when it
 * constructs the consecutive horizon.
 */

squadHorizonServiceCheck(
    'Missing gameweek is not fabricated by adapter',
    !array_key_exists(
        3,
        $missingProjectionSquad[2][
            'gameweeks'
        ]
        ?? []
    )
);


squadHorizonServiceCheck(
    'Missing projection data does not remove player from squad',
    count(
        $missingProjectionSquad
    )
    ===
    15
);


squadHorizonServiceCheck(
    'Missing projection data still reaches Squad Horizon model',
    $missingProjectionModelStub
        ->receivedHorizon
    ===
    3
);


squadHorizonServiceCheck(
    'Service still exposes Squad Horizon result',
    (
        $missingProjectionResult[
            'horizon_result'
        ][
            'adapter_test'
        ]
        ?? null
    )
    ===
    true
);


/*
 * ============================================================
 * SCENARIO K: UNAVAILABLE PLAYER PROJECTION
 * ============================================================
 */

squadHorizonServiceHeading(
    'Scenario K: Unavailable Player Projection'
);


$unavailablePlayerProjections =
    $adapterProjections;


/*
 * Player 1 resolves correctly from the local repository but
 * has no available multi-gameweek Expected Points data.
 */

$unavailablePlayerProjections[1] = [

    'status' =>
        'Unavailable',

    'player_id' =>
        1,

    'gameweeks' =>
        []
];


$unavailableProjectionRepositoryStub =
    new SquadHorizonServicePlayerRepositoryStub(
        $repositoryPlayers
    );


$unavailableProjectionPlayerIntelligenceStub =
    new SquadHorizonServicePlayerIntelligenceStub(
        $unavailablePlayerProjections
    );


$unavailableProjectionModelStub =
    new SquadHorizonServiceModelStub();


$unavailableProjectionService =
    new SquadHorizonIntelligenceService(
        $unavailableProjectionRepositoryStub,
        $unavailableProjectionPlayerIntelligenceStub,
        $unavailableProjectionModelStub
    );


$unavailableProjectionResult =
    $unavailableProjectionService
        ->buildForImportedSquad(
            $importedSquad,
            3
        );


$unavailableProjectionSquad =
    $unavailableProjectionModelStub
        ->receivedSquad;


/*
 * Projection availability must not determine whether the
 * resolved player remains part of the squad.
 */

squadHorizonServiceCheck(
    'Unavailable projection does not remove player from squad',
    count(
        $unavailableProjectionSquad
    )
    ===
    15
);


squadHorizonServiceCheck(
    'Unavailable player preserves local player ID',
    (
        $unavailableProjectionSquad[0][
            'player_id'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonServiceCheck(
    'Unavailable player preserves player name',
    (
        $unavailableProjectionSquad[0][
            'name'
        ]
        ?? null
    )
    ===
    'Player 1'
);


squadHorizonServiceCheck(
    'Unavailable player preserves position',
    (
        $unavailableProjectionSquad[0][
            'position'
        ]
        ?? null
    )
    ===
    'GK'
);


squadHorizonServiceCheck(
    'Unavailable player preserves team ID',
    (
        $unavailableProjectionSquad[0][
            'team_id'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonServiceCheck(
    'Unavailable player has no fabricated gameweeks',
    (
        $unavailableProjectionSquad[0][
            'gameweeks'
        ]
        ?? null
    )
    ===
    []
);


squadHorizonServiceCheck(
    'Unavailable projection still reaches Squad Horizon model',
    $unavailableProjectionModelStub
        ->receivedHorizon
    ===
    3
);


squadHorizonServiceCheck(
    'Service still exposes Squad Horizon result',
    (
        $unavailableProjectionResult[
            'horizon_result'
        ][
            'adapter_test'
        ]
        ?? null
    )
    ===
    true
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

echo
    'Passed: '
    . $passed
    . '<br>';

echo
    'Failed: '
    . $failed
    . '<br><br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅';

} else {

    echo
        'RESULT: TESTS FAILED ❌';
}