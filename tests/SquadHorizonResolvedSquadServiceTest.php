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

function resolvedSquadServiceFunctionalCheck(
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


function resolvedSquadServiceFunctionalHeading(
    string $title
): void {

    echo
        '<br>';

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
 */

class ResolvedSquadServicePlayerRepositoryStub
    extends PlayerRepository
{
    public function __construct()
    {
    }
}


class ResolvedSquadServicePlayerIntelligenceStub
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
        array $projectionsByPlayerId
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
                    'Unavailable',

                'gameweeks' =>
                    []
            ];
    }
}


class ResolvedSquadServiceModelStub
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

            'projection_confidence' =>
                0.72,

            'adapter_test' =>
                true
        ];
    }
}


/*
 * ============================================================
 * START
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Squad Horizon Resolved Squad Service Test<br>';

echo
    '============================================<br>';


/*
 * ============================================================
 * BUILD RESOLVED SQUAD AND PROJECTIONS
 * ============================================================
 */

$resolvedPlayers =
    [];


$projectionsByPlayerId =
    [];


for (
    $playerNumber = 1;
    $playerNumber <= 15;
    $playerNumber++
) {

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


    $resolvedPlayers[] = [

        'id' =>
            $playerNumber,

        'fpl_player_id' =>
            100
            +
            $playerNumber,

        'web_name' =>
            'Player '
            . $playerNumber,

        'position' =>
            $position,

        'team_id' =>
            $playerNumber
    ];


    $projectionsByPlayerId[
        $playerNumber
    ] = [

        'status' =>
            'Available',

        'player_id' =>
            $playerNumber,
            
        'fixtures' => [

            [

                'fixture_id' =>
                    1000
                    +
                    $playerNumber,

                'gameweek' =>
                    3,

                'team_id' =>
                    $playerNumber,

                'opponent_team_id' =>
                    20
                    +
                    $playerNumber,

                'is_home' =>
                    true,

                'projected_points' =>
                    5.0
                    +
                    (
                        $playerNumber
                        /
                        10
                    )
            ]
        ],


        'gameweeks' => [

            3 => [

                'gameweek' =>
                    3,

                'projected_points' =>
                    5.0
                    +
                    (
                        $playerNumber
                        /
                        10
                    ),

                'projection_confidence' =>
                    $playerNumber === 1
                        ? 0.72
                        : 0.80,

                'team_id' =>
                    $playerNumber,

                'opponent_team_id' =>
                    20
                    +
                    $playerNumber,

                'fixture_count' =>
                    1,

                'schedule_type' =>
                    'Normal',

                'fixtures' => [

                    [

                        'fixture_id' =>
                            1000
                            +
                            $playerNumber,

                        'gameweek' =>
                            3,

                        'team_id' =>
                            $playerNumber,

                        'opponent_team_id' =>
                            20
                            +
                            $playerNumber,

                        'is_home' =>
                            true,

                        'projected_points' =>
                            5.0
                            +
                            (
                                $playerNumber
                                /
                                10
                            )
                    ]
                ]
            ],

            4 => [

                'gameweek' =>
                    4,

                'projected_points' =>
                    $playerNumber === 1
                        ? 0.0
                        : 6.0,

                'projection_confidence' =>
                    $playerNumber === 1
                        ? null
                        : 0.75,

                'team_id' =>
                    $playerNumber,

                'opponent_team_id' =>
                    $playerNumber === 1
                        ? null
                        : (
                            30
                            +
                            $playerNumber
                        ),

                'fixture_count' =>
                    $playerNumber === 1
                        ? 0
                        : 1,

                'schedule_type' =>
                    $playerNumber === 1
                        ? 'Blank'
                        : 'Normal',

                'fixtures' =>
                    []
            ]
        ]
    ];
}


/*
 * ============================================================
 * BUILD SERVICE
 * ============================================================
 */

$repositoryStub =
    new ResolvedSquadServicePlayerRepositoryStub();


$playerIntelligenceStub =
    new ResolvedSquadServicePlayerIntelligenceStub(
        $projectionsByPlayerId
    );


$modelStub =
    new ResolvedSquadServiceModelStub();


$service =
    new SquadHorizonIntelligenceService(
        $repositoryStub,
        $playerIntelligenceStub,
        $modelStub
    );


$result =
    $service->buildForResolvedSquad(
        $resolvedPlayers,
        2
    );


/*
 * ============================================================
 * Scenario A: Resolved players use projection pipeline
 * ============================================================
 */

resolvedSquadServiceFunctionalHeading(
    'Scenario A: Resolved players use projection pipeline'
);


resolvedSquadServiceFunctionalCheck(
    'Resolved squad analysis is available',
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


resolvedSquadServiceFunctionalCheck(
    'Resolved result reports fifteen players',
    (
        $result[
            'player_count'
        ]
        ??
        null
    )
    ===
    15
);


resolvedSquadServiceFunctionalCheck(
    'Player Intelligence is requested for all fifteen local players',
    $playerIntelligenceStub->requestedPlayerIds
    ===
    range(
        1,
        15
    )
);


resolvedSquadServiceFunctionalCheck(
    'Every projection request uses the existing six-fixture limit',
    $playerIntelligenceStub->requestedFixtureLimits
    ===
    array_fill(
        0,
        15,
        6
    )
);


/*
 * ============================================================
 * Scenario B: Resolved squad reaches Squad Horizon model
 * ============================================================
 */

resolvedSquadServiceFunctionalHeading(
    'Scenario B: Resolved squad reaches Squad Horizon model'
);


resolvedSquadServiceFunctionalCheck(
    'Squad Horizon receives all fifteen players',
    count(
        $modelStub->receivedSquad
    )
    ===
    15
);


resolvedSquadServiceFunctionalCheck(
    'Squad Horizon receives requested two-gameweek horizon',
    $modelStub->receivedHorizon
    ===
    2
);


resolvedSquadServiceFunctionalCheck(
    'First adapted player preserves local player ID',
    (
        $modelStub->receivedSquad[0][
            'player_id'
        ]
        ??
        null
    )
    ===
    1
);


resolvedSquadServiceFunctionalCheck(
    'First adapted player preserves name',
    (
        $modelStub->receivedSquad[0][
            'name'
        ]
        ??
        null
    )
    ===
    'Player 1'
);


resolvedSquadServiceFunctionalCheck(
    'First adapted player preserves position',
    (
        $modelStub->receivedSquad[0][
            'position'
        ]
        ??
        null
    )
    ===
    'GK'
);


/*
 * ============================================================
 * Scenario C: Projection semantics are preserved
 * ============================================================
 */

resolvedSquadServiceFunctionalHeading(
    'Scenario C: Projection semantics are preserved'
);


$adaptedPlayer =
    $modelStub->receivedSquad[0]
    ??
    [];


$adaptedGw3 =
    $adaptedPlayer[
        'gameweeks'
    ][3]
    ??
    [];


$adaptedGw4 =
    $adaptedPlayer[
        'gameweeks'
    ][4]
    ??
    [];


resolvedSquadServiceFunctionalCheck(
    'Normal gameweek preserves projected points',
    isset(
        $adaptedGw3[
            'projected_points'
        ]
    )
    &&
    abs(
        (
            (float) $adaptedGw3[
                'projected_points'
            ]
        )
        -
        5.1
    ) < 0.0001
);


resolvedSquadServiceFunctionalCheck(
    'Normal gameweek preserves projection confidence',
    isset(
        $adaptedGw3[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $adaptedGw3[
                'projection_confidence'
            ]
        )
        -
        0.72
    ) < 0.0001
);


resolvedSquadServiceFunctionalCheck(
    'Normal gameweek preserves fixture count',
    (
        $adaptedGw3[
            'fixture_count'
        ]
        ??
        null
    )
    ===
    1
);


resolvedSquadServiceFunctionalCheck(
    'Normal gameweek preserves schedule type',
    (
        $adaptedGw3[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Normal'
);


resolvedSquadServiceFunctionalCheck(
    'Normal gameweek preserves rich fixture rows',
    count(
        $adaptedGw3[
            'fixtures'
        ]
        ??
        []
    )
    ===
    1
);


resolvedSquadServiceFunctionalCheck(
    'Blank gameweek preserves zero projected points',
    array_key_exists(
        'projected_points',
        $adaptedGw4
    )
    &&
    abs(
        (
            (float) $adaptedGw4[
                'projected_points'
            ]
        )
        -
        0.0
    ) < 0.0001
);


resolvedSquadServiceFunctionalCheck(
    'Blank gameweek preserves null projection confidence',
    array_key_exists(
        'projection_confidence',
        $adaptedGw4
    )
    &&
    $adaptedGw4[
        'projection_confidence'
    ]
    ===
    null
);


resolvedSquadServiceFunctionalCheck(
    'Blank gameweek preserves zero fixture count',
    (
        $adaptedGw4[
            'fixture_count'
        ]
        ??
        null
    )
    ===
    0
);


resolvedSquadServiceFunctionalCheck(
    'Blank gameweek preserves Blank schedule type',
    (
        $adaptedGw4[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Blank'
);


/*
 * ============================================================
 * Scenario D: Horizon result is returned unchanged
 * ============================================================
 */

resolvedSquadServiceFunctionalHeading(
    'Scenario D: Horizon result is returned unchanged'
);


resolvedSquadServiceFunctionalCheck(
    'Service exposes Squad Horizon result',
    (
        $result[
            'horizon_result'
        ]
        ??
        null
    )
    ===
    [
        'status' =>
            'Available',

        'projection_confidence' =>
            0.72,

        'adapter_test' =>
            true
    ]
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '<br>';

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