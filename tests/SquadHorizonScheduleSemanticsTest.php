<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo
    '============================================<br>';

echo
    'Squad Horizon Schedule Semantics Test<br>';

echo
    'v0.33.0 — Blank & Double Gameweek Intelligence<br>';

echo
    '============================================<br><br>';


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function squadHorizonScheduleCheck(
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


function squadHorizonScheduleHeading(
    string $title
): void {

    echo
        '============================================<br>';

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';

    echo
        '============================================<br>';
}


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 *
 * The production SquadHorizonIntelligenceService requires a
 * complete 15-player imported squad.
 *
 * These doubles allow us to pass through that real production
 * boundary while isolating the adapter behaviour under test.
 */

class SquadHorizonScheduleRepositoryStub
    extends PlayerRepository
{
    private array
        $playersByFplId;


    public function __construct(
        array $playersByFplId
    ) {

        $this->playersByFplId =
            $playersByFplId;
    }


    public function getByFplPlayerId(
        int $fplPlayerId
    ): ?array {

        return
            $this->playersByFplId[
                $fplPlayerId
            ]
            ?? null;
    }
}


class SquadHorizonSchedulePlayerIntelligenceStub
    extends PlayerIntelligenceService
{
    private array
        $projectionsByPlayerId;


    public function __construct(
        array $projectionsByPlayerId
    ) {

        $this->projectionsByPlayerId =
            $projectionsByPlayerId;
    }


    public function getPlayerMultiGameweekExpectedPoints(
        int $playerId,
        int $fixtureLimit = 6
    ): array {

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

                'fixtures' =>
                    [],

                'gameweeks' =>
                    []
            ];
    }
}


class SquadHorizonScheduleModelStub
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

            'schedule_semantics_test' =>
                true
        ];
    }
}


/*
 * ============================================================
 * COMPLETE IMPORTED SQUAD
 * ============================================================
 *
 * Player 1 is the controlled schedule player.
 *
 * Players 2-15 simply allow the request to satisfy the real
 * production requirement for a complete 15-player squad.
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
        1000
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


/*
 * ============================================================
 * CONTROLLED PLAYER PROJECTION
 * ============================================================
 *
 * Player 1:
 *
 * GW2 = Normal
 *      1 fixture
 *      opponent team 20
 *      5.00 xP
 *
 * GW3 = Blank
 *      0 fixtures
 *      0.00 xP
 *
 * GW4 = Double
 *      fixture 401 vs team 18
 *      fixture 402 vs team 19
 *      8.00 aggregate xP
 *
 * The top-level fixture rows deliberately contain the richer
 * opponent metadata used by the production adapter.
 */

$controlledProjection = [

    'status' =>
        'Available',

    'player_id' =>
        1,

    'team_id' =>
        1,

    'fixtures' => [

        [
            'fixture_id' =>
                201,

            'fpl_fixture_id' =>
                1201,

            'gameweek' =>
                2,

            'kickoff_time' =>
                '2026-08-29 15:00:00',

            'opponent_team_id' =>
                20,

            'opponent_name' =>
                'Opponent 20',

            'is_home' =>
                true
        ],

        [
            'fixture_id' =>
                401,

            'fpl_fixture_id' =>
                1401,

            'gameweek' =>
                4,

            'kickoff_time' =>
                '2026-09-12 12:30:00',

            'opponent_team_id' =>
                18,

            'opponent_name' =>
                'Opponent 18',

            'is_home' =>
                true
        ],

        [
            'fixture_id' =>
                402,

            'fpl_fixture_id' =>
                1402,

            'gameweek' =>
                4,

            'kickoff_time' =>
                '2026-09-15 19:45:00',

            'opponent_team_id' =>
                19,

            'opponent_name' =>
                'Opponent 19',

            'is_home' =>
                false
        ]
    ],

    'gameweeks' => [

        2 => [

            'gameweek' =>
                2,

            'fixture_count' =>
                1,

            'schedule_type' =>
                'Normal',

            'projected_points' =>
                5.0,

            'fixtures' => [

                [
                    'fixture_id' =>
                        201,

                    'gameweek' =>
                        2,

                    'projected_points' =>
                        5.0
                ]
            ]
        ],

        3 => [

            'gameweek' =>
                3,

            'fixture_count' =>
                0,

            'schedule_type' =>
                'Blank',

            'projected_points' =>
                0.0,

            'fixtures' =>
                []
        ],

        4 => [

            'gameweek' =>
                4,

            'fixture_count' =>
                2,

            'schedule_type' =>
                'Double',

            'projected_points' =>
                8.0,

            'fixtures' => [

                [
                    'fixture_id' =>
                        401,

                    'gameweek' =>
                        4,

                    'projected_points' =>
                        4.25
                ],

                [
                    'fixture_id' =>
                        402,

                    'gameweek' =>
                        4,

                    'projected_points' =>
                        3.75
                ]
            ]
        ]
    ]
];


$projectionsByPlayerId = [

    1 =>
        $controlledProjection
];


/*
 * Players 2-15 receive a simple Normal GW2 projection.
 *
 * Their exact values are irrelevant to this test. They exist only
 * so the production adapter receives a complete squad.
 */

for (
    $playerNumber = 2;
    $playerNumber <= 15;
    $playerNumber++
) {

    $fixtureId =
        2000
        +
        $playerNumber;


    $opponentTeamId =
        100
        +
        $playerNumber;


    $projectionsByPlayerId[
        $playerNumber
    ] = [

        'status' =>
            'Available',

        'player_id' =>
            $playerNumber,

        'team_id' =>
            $playerNumber,

        'fixtures' => [

            [
                'fixture_id' =>
                    $fixtureId,

                'gameweek' =>
                    2,

                'opponent_team_id' =>
                    $opponentTeamId,

                'opponent_name' =>
                    'Opponent '
                    . $opponentTeamId,

                'is_home' =>
                    true
            ]
        ],

        'gameweeks' => [

            2 => [

                'gameweek' =>
                    2,

                'fixture_count' =>
                    1,

                'schedule_type' =>
                    'Normal',

                'projected_points' =>
                    3.0,

                'fixtures' => [

                    [
                        'fixture_id' =>
                            $fixtureId,

                        'gameweek' =>
                            2,

                        'projected_points' =>
                            3.0
                    ]
                ]
            ]
        ]
    ];
}


/*
 * ============================================================
 * BUILD PRODUCTION SERVICE
 * ============================================================
 */

$repositoryStub =
    new SquadHorizonScheduleRepositoryStub(
        $repositoryPlayers
    );


$playerIntelligenceStub =
    new SquadHorizonSchedulePlayerIntelligenceStub(
        $projectionsByPlayerId
    );


$modelStub =
    new SquadHorizonScheduleModelStub();


$service =
    new SquadHorizonIntelligenceService(
        $repositoryStub,
        $playerIntelligenceStub,
        $modelStub
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


/*
 * The model stub captures the exact adapted squad produced by
 * SquadHorizonIntelligenceService before SquadHorizonIntelligence
 * performs any calculations.
 */

$adaptedPlayer =
    $modelStub->receivedSquad[
        0
    ]
    ?? [];


$adaptedGameweeks =
    $adaptedPlayer[
        'gameweeks'
    ]
    ?? [];


/*
 * ============================================================
 * SCENARIO A
 * ADAPTER BASELINE
 * ============================================================
 */

squadHorizonScheduleHeading(
    'Scenario A: Adapter Baseline'
);


squadHorizonScheduleCheck(
    'Production service remains available',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


squadHorizonScheduleCheck(
    'Production service adapts all fifteen players',
    count(
        $modelStub->receivedSquad
    )
    ===
    15
);


squadHorizonScheduleCheck(
    'Controlled player preserves all three gameweeks',
    count(
        $adaptedGameweeks
    )
    ===
    3
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO B
 * NORMAL GAMEWEEK
 * ============================================================
 */

squadHorizonScheduleHeading(
    'Scenario B: Normal Gameweek'
);


$gw2 =
    $adaptedGameweeks[
        2
    ]
    ?? [];


squadHorizonScheduleCheck(
    'Normal GW2 preserves projected points',
    abs(
        (
            (float) (
                $gw2[
                    'projected_points'
                ]
                ?? 0.0
            )
        )
        -
        5.0
    )
    <
    0.001
);


squadHorizonScheduleCheck(
    'Normal GW2 preserves aggregate opponent',
    (
        $gw2[
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    20
);


squadHorizonScheduleCheck(
    'Normal GW2 records one fixture',
    (
        $gw2[
            'fixture_count'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonScheduleCheck(
    'Normal GW2 is classified as Normal',
    (
        $gw2[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Normal'
);


squadHorizonScheduleCheck(
    'Normal GW2 preserves one fixture row',
    count(
        $gw2[
            'fixtures'
        ]
        ?? []
    )
    ===
    1
);


squadHorizonScheduleCheck(
    'Normal GW2 fixture preserves opponent metadata',
    (
        $gw2[
            'fixtures'
        ][0][
            'opponent_team_id'
        ]
        ?? null
    )
    ===
    20
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO C
 * BLANK GAMEWEEK
 * ============================================================
 */

squadHorizonScheduleHeading(
    'Scenario C: Blank Gameweek'
);


$gw3 =
    $adaptedGameweeks[
        3
    ]
    ?? [];


squadHorizonScheduleCheck(
    'Blank GW3 preserves zero projected points',
    isset(
        $adaptedGameweeks[
            3
        ]
    )
    &&
    abs(
        (
            (float) (
                $gw3[
                    'projected_points'
                ]
                ?? -1.0
            )
        )
        -
        0.0
    )
    <
    0.001
);


squadHorizonScheduleCheck(
    'Blank GW3 has no aggregate opponent',
    !array_key_exists(
        'opponent_team_id',
        $gw3
    )
    ||
    $gw3[
        'opponent_team_id'
    ]
    ===
    null
);


squadHorizonScheduleCheck(
    'Blank GW3 records zero fixtures',
    (
        $gw3[
            'fixture_count'
        ]
        ?? null
    )
    ===
    0
);


squadHorizonScheduleCheck(
    'Blank GW3 is classified as Blank',
    (
        $gw3[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Blank'
);


squadHorizonScheduleCheck(
    'Blank GW3 preserves an empty fixture list',
    (
        $gw3[
            'fixtures'
        ]
        ?? null
    )
    ===
    []
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO D
 * DOUBLE GAMEWEEK
 * ============================================================
 */

squadHorizonScheduleHeading(
    'Scenario D: Double Gameweek'
);


$gw4 =
    $adaptedGameweeks[
        4
    ]
    ?? [];


squadHorizonScheduleCheck(
    'Double GW4 preserves aggregate projected points',
    abs(
        (
            (float) (
                $gw4[
                    'projected_points'
                ]
                ?? 0.0
            )
        )
        -
        8.0
    )
    <
    0.001
);


squadHorizonScheduleCheck(
    'Double GW4 deliberately has no aggregate opponent',
    !array_key_exists(
        'opponent_team_id',
        $gw4
    )
    ||
    $gw4[
        'opponent_team_id'
    ]
    ===
    null
);


squadHorizonScheduleCheck(
    'Double GW4 records two fixtures',
    (
        $gw4[
            'fixture_count'
        ]
        ?? null
    )
    ===
    2
);


squadHorizonScheduleCheck(
    'Double GW4 is classified as Double',
    (
        $gw4[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


squadHorizonScheduleCheck(
    'Double GW4 preserves both fixture rows',
    count(
        $gw4[
            'fixtures'
        ]
        ?? []
    )
    ===
    2
);


$doubleFixtureIds =
    array_map(
        function (
            array $fixture
        ): int {

            return
                (int) (
                    $fixture[
                        'fixture_id'
                    ]
                    ?? 0
                );
        },
        $gw4[
            'fixtures'
        ]
        ?? []
    );


squadHorizonScheduleCheck(
    'Double GW4 preserves both fixture identities',
    $doubleFixtureIds
    ===
    [
        401,
        402
    ]
);


$doubleOpponentIds =
    array_map(
        function (
            array $fixture
        ): int {

            return
                (int) (
                    $fixture[
                        'opponent_team_id'
                    ]
                    ?? 0
                );
        },
        $gw4[
            'fixtures'
        ]
        ?? []
    );


squadHorizonScheduleCheck(
    'Double GW4 preserves both individual opponents',
    $doubleOpponentIds
    ===
    [
        18,
        19
    ]
);


squadHorizonScheduleCheck(
    'Double GW4 preserves home and away fixture context',
    (
        $gw4[
            'fixtures'
        ][0][
            'is_home'
        ]
        ?? null
    )
    ===
    true
    &&
    (
        $gw4[
            'fixtures'
        ][1][
            'is_home'
        ]
        ?? null
    )
    ===
    false
);


echo
    '<br>';


/*
 * ============================================================
 * SCENARIO E
 * EXISTING AGGREGATE CONTRACT
 * ============================================================
 */

squadHorizonScheduleHeading(
    'Scenario E: Existing Aggregate Contract'
);


squadHorizonScheduleCheck(
    'Controlled player preserves local team ID',
    (
        $gw2[
            'team_id'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonScheduleCheck(
    'Blank gameweek preserves local team ID',
    (
        $gw3[
            'team_id'
        ]
        ?? null
    )
    ===
    1
);


squadHorizonScheduleCheck(
    'Double gameweek preserves local team ID',
    (
        $gw4[
            'team_id'
        ]
        ?? null
    )
    ===
    1
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