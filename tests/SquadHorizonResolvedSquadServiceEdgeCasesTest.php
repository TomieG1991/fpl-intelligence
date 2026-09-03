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

function resolvedSquadEdgeCheck(
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


function resolvedSquadEdgeHeading(
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

class ResolvedSquadEdgePlayerRepositoryStub
    extends PlayerRepository
{
    public function __construct()
    {
    }
}


class ResolvedSquadEdgePlayerIntelligenceStub
    extends PlayerIntelligenceService
{
    public array
        $requestedPlayerIds =
            [];


    public array
        $requestedFixtureLimits =
            [];


    public function __construct()
    {
    }


    public function getPlayerMultiGameweekExpectedPoints(
        int $playerId,
        int $fixtureLimit = 6
    ): array {

        $this->requestedPlayerIds[] =
            $playerId;


        $this->requestedFixtureLimits[] =
            $fixtureLimit;


        return [

            'status' =>
                'Available',

            'player_id' =>
                $playerId,

            'fixtures' =>
                [],

            'gameweeks' => [

                3 => [

                    'gameweek' =>
                        3,

                    'projected_points' =>
                        5.0,

                    'projection_confidence' =>
                        0.80,

                    'team_id' =>
                        $playerId,

                    'opponent_team_id' =>
                        null,

                    'fixture_count' =>
                        1,

                    'schedule_type' =>
                        'Normal'
                ]
            ]
        ];
    }
}


class ResolvedSquadEdgeModelStub
    extends SquadHorizonIntelligence
{
    public int
        $buildCallCount =
            0;


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

        $this->buildCallCount++;


        $this->receivedSquad =
            $squad;


        $this->receivedHorizon =
            $horizon;


        return [

            'status' =>
                'Available',

            'edge_test' =>
                true
        ];
    }
}


/*
 * ============================================================
 * SQUAD FACTORY
 * ============================================================
 */

function buildResolvedSquadEdgePlayers(
    int $count
): array {

    $players =
        [];


    for (
        $playerNumber = 1;
        $playerNumber <= $count;
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


        $players[] = [

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
    }


    return
        $players;
}


/*
 * ============================================================
 * SERVICE FACTORY
 * ============================================================
 */

function buildResolvedSquadEdgeService(
    ?ResolvedSquadEdgePlayerIntelligenceStub &$playerIntelligenceStub = null,
    ?ResolvedSquadEdgeModelStub &$modelStub = null
): SquadHorizonIntelligenceService {

    $repositoryStub =
        new ResolvedSquadEdgePlayerRepositoryStub();


    $playerIntelligenceStub =
        new ResolvedSquadEdgePlayerIntelligenceStub();


    $modelStub =
        new ResolvedSquadEdgeModelStub();


    return
        new SquadHorizonIntelligenceService(
            $repositoryStub,
            $playerIntelligenceStub,
            $modelStub
        );
}


/*
 * ============================================================
 * START
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Squad Horizon Resolved Squad Service Edge Cases Test<br>';

echo
    '============================================<br>';


/*
 * ============================================================
 * Scenario A: Fourteen-player squad
 * ============================================================
 */

resolvedSquadEdgeHeading(
    'Scenario A: Fourteen-player squad'
);


$service =
    buildResolvedSquadEdgeService(
        $playerIntelligenceStub,
        $modelStub
    );


$result =
    $service->buildForResolvedSquad(
        buildResolvedSquadEdgePlayers(
            14
        ),
        3
    );


resolvedSquadEdgeCheck(
    'Fourteen-player squad is unavailable',
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


resolvedSquadEdgeCheck(
    'Fourteen-player squad reports supplied player count',
    (
        $result[
            'resolved_player_count'
        ]
        ??
        null
    )
    ===
    14
);


resolvedSquadEdgeCheck(
    'Fourteen-player squad reports required player count',
    (
        $result[
            'required_player_count'
        ]
        ??
        null
    )
    ===
    15
);


resolvedSquadEdgeCheck(
    'Fourteen-player squad requests no projections',
    $playerIntelligenceStub->requestedPlayerIds
    ===
    []
);


resolvedSquadEdgeCheck(
    'Fourteen-player squad does not build Squad Horizon',
    $modelStub->buildCallCount
    ===
    0
);


/*
 * ============================================================
 * Scenario B: Sixteen-player squad
 * ============================================================
 */

resolvedSquadEdgeHeading(
    'Scenario B: Sixteen-player squad'
);


$service =
    buildResolvedSquadEdgeService(
        $playerIntelligenceStub,
        $modelStub
    );


$result =
    $service->buildForResolvedSquad(
        buildResolvedSquadEdgePlayers(
            16
        ),
        3
    );


resolvedSquadEdgeCheck(
    'Sixteen-player squad is unavailable',
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


resolvedSquadEdgeCheck(
    'Sixteen-player squad reports supplied player count',
    (
        $result[
            'resolved_player_count'
        ]
        ??
        null
    )
    ===
    16
);


resolvedSquadEdgeCheck(
    'Sixteen-player squad reports required player count',
    (
        $result[
            'required_player_count'
        ]
        ??
        null
    )
    ===
    15
);


resolvedSquadEdgeCheck(
    'Sixteen-player squad requests no projections',
    $playerIntelligenceStub->requestedPlayerIds
    ===
    []
);


resolvedSquadEdgeCheck(
    'Sixteen-player squad does not build Squad Horizon',
    $modelStub->buildCallCount
    ===
    0
);


/*
 * ============================================================
 * Scenario C: Invalid local player ID
 * ============================================================
 */

resolvedSquadEdgeHeading(
    'Scenario C: Invalid local player ID'
);


$invalidIdSquad =
    buildResolvedSquadEdgePlayers(
        15
    );


$invalidIdSquad[7][
    'id'
] =
    0;


$service =
    buildResolvedSquadEdgeService(
        $playerIntelligenceStub,
        $modelStub
    );


$result =
    $service->buildForResolvedSquad(
        $invalidIdSquad,
        3
    );


resolvedSquadEdgeCheck(
    'Squad with invalid local player ID is unavailable',
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


resolvedSquadEdgeCheck(
    'Invalid-ID squad reports fifteen supplied players',
    (
        $result[
            'resolved_player_count'
        ]
        ??
        null
    )
    ===
    15
);


resolvedSquadEdgeCheck(
    'Invalid-ID squad reports invalid local player IDs',
    (
        $result[
            'invalid_local_player_ids'
        ]
        ??
        null
    )
    ===
    [
        0
    ]
);


resolvedSquadEdgeCheck(
    'Invalid-ID squad requests no projections',
    $playerIntelligenceStub->requestedPlayerIds
    ===
    []
);


resolvedSquadEdgeCheck(
    'Invalid-ID squad does not build Squad Horizon',
    $modelStub->buildCallCount
    ===
    0
);


/*
 * ============================================================
 * Scenario D: Missing local player ID
 * ============================================================
 */

resolvedSquadEdgeHeading(
    'Scenario D: Missing local player ID'
);


$missingIdSquad =
    buildResolvedSquadEdgePlayers(
        15
    );


unset(
    $missingIdSquad[7][
        'id'
    ]
);


$service =
    buildResolvedSquadEdgeService(
        $playerIntelligenceStub,
        $modelStub
    );


$result =
    $service->buildForResolvedSquad(
        $missingIdSquad,
        3
    );


resolvedSquadEdgeCheck(
    'Squad with missing local player ID is unavailable',
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


resolvedSquadEdgeCheck(
    'Missing-ID squad reports fifteen supplied players',
    (
        $result[
            'resolved_player_count'
        ]
        ??
        null
    )
    ===
    15
);


resolvedSquadEdgeCheck(
    'Missing-ID squad reports invalid local player IDs',
    (
        $result[
            'invalid_local_player_ids'
        ]
        ??
        null
    )
    ===
    [
        0
    ]
);


resolvedSquadEdgeCheck(
    'Missing-ID squad requests no projections',
    $playerIntelligenceStub->requestedPlayerIds
    ===
    []
);


resolvedSquadEdgeCheck(
    'Missing-ID squad does not build Squad Horizon',
    $modelStub->buildCallCount
    ===
    0
);


/*
 * ============================================================
 * Scenario E: Duplicate local player ID
 * ============================================================
 */

resolvedSquadEdgeHeading(
    'Scenario E: Duplicate local player ID'
);


$duplicateIdSquad =
    buildResolvedSquadEdgePlayers(
        15
    );


$duplicateIdSquad[7][
    'id'
] =
    7;


$service =
    buildResolvedSquadEdgeService(
        $playerIntelligenceStub,
        $modelStub
    );


$result =
    $service->buildForResolvedSquad(
        $duplicateIdSquad,
        3
    );


resolvedSquadEdgeCheck(
    'Squad with duplicate local player ID is unavailable',
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


resolvedSquadEdgeCheck(
    'Duplicate-ID squad reports fifteen supplied players',
    (
        $result[
            'resolved_player_count'
        ]
        ??
        null
    )
    ===
    15
);


resolvedSquadEdgeCheck(
    'Duplicate-ID squad reports duplicate local player IDs',
    (
        $result[
            'duplicate_local_player_ids'
        ]
        ??
        null
    )
    ===
    [
        7
    ]
);


resolvedSquadEdgeCheck(
    'Duplicate-ID squad requests no projections',
    $playerIntelligenceStub->requestedPlayerIds
    ===
    []
);


resolvedSquadEdgeCheck(
    'Duplicate-ID squad does not build Squad Horizon',
    $modelStub->buildCallCount
    ===
    0
);


/*
 * ============================================================
 * Scenario F: Complete valid resolved squad
 * ============================================================
 */

resolvedSquadEdgeHeading(
    'Scenario F: Complete valid resolved squad'
);


$service =
    buildResolvedSquadEdgeService(
        $playerIntelligenceStub,
        $modelStub
    );


$result =
    $service->buildForResolvedSquad(
        buildResolvedSquadEdgePlayers(
            15
        ),
        3
    );


resolvedSquadEdgeCheck(
    'Complete fifteen-player squad remains available',
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


resolvedSquadEdgeCheck(
    'Complete squad requests all fifteen projections',
    $playerIntelligenceStub->requestedPlayerIds
    ===
    range(
        1,
        15
    )
);


resolvedSquadEdgeCheck(
    'Complete squad uses six-fixture projection limit',
    $playerIntelligenceStub->requestedFixtureLimits
    ===
    array_fill(
        0,
        15,
        6
    )
);


resolvedSquadEdgeCheck(
    'Complete squad builds Squad Horizon once',
    $modelStub->buildCallCount
    ===
    1
);


resolvedSquadEdgeCheck(
    'Complete squad passes all fifteen players to Squad Horizon',
    count(
        $modelStub->receivedSquad
    )
    ===
    15
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