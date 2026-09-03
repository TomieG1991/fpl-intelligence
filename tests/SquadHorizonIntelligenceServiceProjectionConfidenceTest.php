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

function squadHorizonProjectionConfidenceCheck(
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


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class SquadHorizonProjectionConfidenceRepositoryStub
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


class SquadHorizonProjectionConfidencePlayerIntelligenceStub
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
            ?? [];
    }
}


class SquadHorizonProjectionConfidenceModelStub
    extends SquadHorizonIntelligence
{
    public array
        $receivedSquad =
            [];


    public function buildHorizon(
        array $squad,
        int $horizon
    ): array {

        $this->receivedSquad =
            $squad;


        return [

            'status' =>
                'Available'
        ];
    }
}


/*
 * ============================================================
 * TEST DATA
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Squad Horizon Intelligence Service Projection Confidence Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$repositoryPlayers =
    [];


$importedPlayers =
    [];


$projectionsByPlayerId =
    [];


/*
 * Build the complete 15-player squad required by the production
 * SquadHorizonIntelligenceService boundary.
 */

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


    /*
     * Player 1 deliberately carries three schedule types:
     *
     * GW3 Normal  -> 0.72
     * GW4 Blank   -> null
     * GW5 Double  -> 0.60
     *
     * Remaining players only need enough valid projection data
     * for this adapter-focused test.
     */

    if (
        $playerNumber === 1
    ) {

        $projectionsByPlayerId[
            $playerNumber
        ] = [

            'status' =>
                'Available',

            'player_id' =>
                $playerNumber,

            'fixtures' =>
                [],

            'gameweeks' => [

                3 => [

                    'gameweek' =>
                        3,

                    'fixture_count' =>
                        1,

                    'schedule_type' =>
                        'Normal',

                    'projected_points' =>
                        6.5,

                    'projection_confidence' =>
                        0.72,

                    'fixtures' =>
                        []
                ],

                4 => [

                    'gameweek' =>
                        4,

                    'fixture_count' =>
                        0,

                    'schedule_type' =>
                        'Blank',

                    'projected_points' =>
                        0.0,

                    'projection_confidence' =>
                        null,

                    'fixtures' =>
                        []
                ],

                5 => [

                    'gameweek' =>
                        5,

                    'fixture_count' =>
                        2,

                    'schedule_type' =>
                        'Double',

                    'projected_points' =>
                        10.0,

                    'projection_confidence' =>
                        0.60,

                    'fixtures' =>
                        []
                ]
            ]
        ];

    } else {

        $projectionsByPlayerId[
            $playerNumber
        ] = [

            'status' =>
                'Available',

            'player_id' =>
                $playerNumber,

            'fixtures' =>
                [],

            'gameweeks' => [

                3 => [

                    'gameweek' =>
                        3,

                    'fixture_count' =>
                        1,

                    'schedule_type' =>
                        'Normal',

                    'projected_points' =>
                        5.0,

                    'projection_confidence' =>
                        0.80,

                    'fixtures' =>
                        []
                ]
            ]
        ];
    }
}


$repositoryStub =
    new SquadHorizonProjectionConfidenceRepositoryStub(
        $repositoryPlayers
    );


$playerIntelligenceStub =
    new SquadHorizonProjectionConfidencePlayerIntelligenceStub(
        $projectionsByPlayerId
    );


$squadHorizonModelStub =
    new SquadHorizonProjectionConfidenceModelStub();


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


$service->buildForImportedSquad(
    $importedSquad,
    3
);


$adaptedPlayer =
    $squadHorizonModelStub
        ->receivedSquad[0]
    ??
    [];


$gameweek3 =
    $adaptedPlayer[
        'gameweeks'
    ][3]
    ??
    [];


$gameweek4 =
    $adaptedPlayer[
        'gameweeks'
    ][4]
    ??
    [];


$gameweek5 =
    $adaptedPlayer[
        'gameweeks'
    ][5]
    ??
    [];


/*
 * ============================================================
 * Scenario A: Existing adapter behaviour
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Existing adapter behaviour<br>';

echo
    '============================================<br>';


squadHorizonProjectionConfidenceCheck(
    'Player 1 is passed to Squad Horizon',
    (
        $adaptedPlayer[
            'player_id'
        ]
        ??
        null
    )
    ===
    1
);


squadHorizonProjectionConfidenceCheck(
    'GW3 projected points remain 6.5',
    isset(
        $gameweek3[
            'projected_points'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek3[
                'projected_points'
            ]
        )
        -
        6.5
    ) < 0.0001
);


squadHorizonProjectionConfidenceCheck(
    'GW3 schedule type remains Normal',
    (
        $gameweek3[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Normal'
);


squadHorizonProjectionConfidenceCheck(
    'GW4 schedule type remains Blank',
    (
        $gameweek4[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Blank'
);


squadHorizonProjectionConfidenceCheck(
    'GW5 schedule type remains Double',
    (
        $gameweek5[
            'schedule_type'
        ]
        ??
        null
    )
    ===
    'Double'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Projection confidence adapter
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Projection confidence adapter<br>';

echo
    '============================================<br>';


squadHorizonProjectionConfidenceCheck(
    'Normal GW preserves projection confidence 0.72',
    isset(
        $gameweek3[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $gameweek3[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek3[
                'projection_confidence'
            ]
        )
        -
        0.72
    ) < 0.0001
);


squadHorizonProjectionConfidenceCheck(
    'Blank GW preserves explicit null projection confidence',
    array_key_exists(
        'projection_confidence',
        $gameweek4
    )
    &&
    $gameweek4[
        'projection_confidence'
    ]
    ===
    null
);


squadHorizonProjectionConfidenceCheck(
    'Double GW preserves projection confidence 0.60',
    isset(
        $gameweek5[
            'projection_confidence'
        ]
    )
    &&
    is_numeric(
        $gameweek5[
            'projection_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $gameweek5[
                'projection_confidence'
            ]
        )
        -
        0.60
    ) < 0.0001
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