<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function freeHitHorizonCheck(
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

class FreeHitHorizonFreeHitServiceStub
    extends
    FreeHitIntelligenceService
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


class FreeHitHorizonSquadHorizonStub
    extends
    SquadHorizonIntelligence
{
    public int
        $buildCallCount = 0;


    public array
        $receivedSquad = [];


    public ?int
        $receivedHorizon = null;


    public array
        $result = [];


    public function buildHorizon(
        array $squad,
        int $horizon
    ): array {

        $this->buildCallCount++;


        $this->receivedSquad =
            $squad;


        $this->receivedHorizon =
            $horizon;


        return
            $this->result;
    }
}


echo
    '============================================<br>';

echo
    'Free Hit Horizon Intelligence Service Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


/*
 * ============================================================
 * SCENARIO A: SERVICE CONTRACT
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Free Hit Horizon Service Contract<br>';

echo
    '============================================<br>';


$classExists =
    class_exists(
        'FreeHitHorizonIntelligenceService'
    );


freeHitHorizonCheck(
    'FreeHitHorizonIntelligenceService class exists',
    $classExists
);


if ($classExists) {

    freeHitHorizonCheck(
        'FreeHitHorizonIntelligenceService exposes build',
        method_exists(
            'FreeHitHorizonIntelligenceService',
            'build'
        )
    );

} else {

    freeHitHorizonCheck(
        'FreeHitHorizonIntelligenceService exposes build',
        false
    );
}


echo
    '<br>';


/*
 * ============================================================
 * FURTHER SCENARIOS REQUIRE CLASS
 * ============================================================
 */

if ($classExists) {

    /*
     * ========================================================
     * SCENARIO B: SUCCESSFUL FREE HIT HORIZON
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario B: Successful Free Hit Horizon<br>';

    echo
        '============================================<br>';


    $playerPool = [

        [
            'player_id' => 1,
            'name' => 'Player One'
        ],

        [
            'player_id' => 2,
            'name' => 'Player Two'
        ]
    ];


    $optimizedSquad = [];


    for (
        $playerId = 1;
        $playerId <= 15;
        $playerId++
    ) {

        if ($playerId <= 2) {

            $position =
                'GK';

        } elseif ($playerId <= 7) {

            $position =
                'DEF';

        } elseif ($playerId <= 12) {

            $position =
                'MID';

        } else {

            $position =
                'FWD';
        }


        $optimizedSquad[] = [

            'player_id' =>
                $playerId,

            'name' =>
                'Player ' . $playerId,

            'position' =>
                $position,

            'team_id' =>
                $playerId,

            'price' =>
                5.0,

            'projected_points' =>
                5.0
                +
                (
                    $playerId
                    /
                    10
                ),

            'projection_gameweek' =>
                3,

            'projection_confidence' =>
                0.80
        ];
    }


    $freeHitBuild = [

        'status' =>
            'Available',

        'projected_player_count' =>
            15,

        'optimizer_result' => [

            'status' =>
                'success',

            'squad' =>
                $optimizedSquad,

            'starting_xi_projected_points' =>
                66.0
        ]
    ];


    $expectedHorizonResult = [

        'status' =>
            'Available',

        'player_count' =>
            15,

        'horizon' =>
            1,

        'projection_confidence' =>
            0.80,

        'gameweeks' => [

            3 => [

                'gameweek' =>
                    3,

                'starting_xi_projected_points' =>
                    66.0,

                'starting_xi_projection_confidence' =>
                    0.80
            ]
        ]
    ];


    $freeHitService =
        new FreeHitHorizonFreeHitServiceStub();


    $freeHitService->result =
        $freeHitBuild;


    $squadHorizon =
        new FreeHitHorizonSquadHorizonStub();


    $squadHorizon->result =
        $expectedHorizonResult;


    $service =
        new FreeHitHorizonIntelligenceService(
            $freeHitService,
            $squadHorizon
        );


    $result =
        $service
            ->build(
                $playerPool,
                99.5
            );


    freeHitHorizonCheck(
        'Free Hit service is called once',
        $freeHitService
            ->buildCallCount
        ===
        1
    );


    freeHitHorizonCheck(
        'Candidate pool is passed unchanged to Free Hit service',
        $freeHitService
            ->receivedPlayers
        ===
        $playerPool
    );


    freeHitHorizonCheck(
        'Budget is passed unchanged to Free Hit service',
        abs(
            (
                $freeHitService
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


    freeHitHorizonCheck(
        'Squad Horizon is built once',
        $squadHorizon
            ->buildCallCount
        ===
        1
    );


    freeHitHorizonCheck(
        'Free Hit always requests a one-gameweek horizon',
        $squadHorizon
            ->receivedHorizon
        ===
        1
    );


    freeHitHorizonCheck(
        'All fifteen optimized players are adapted for Squad Horizon',
        count(
            $squadHorizon
                ->receivedSquad
        )
        ===
        15
    );


    $firstAdaptedPlayer =
        $squadHorizon
            ->receivedSquad[
                0
            ]
        ??
        [];


    freeHitHorizonCheck(
        'Adapted Free Hit player preserves player ID',
        (
            $firstAdaptedPlayer[
                'player_id'
            ]
            ??
            null
        )
        ===
        1
    );


    freeHitHorizonCheck(
        'Adapted Free Hit player exposes projected gameweek',
        isset(
            $firstAdaptedPlayer[
                'gameweeks'
            ][
                3
            ]
        )
    );


    freeHitHorizonCheck(
        'Adapted Free Hit player preserves projected points',
        isset(
            $firstAdaptedPlayer[
                'gameweeks'
            ][
                3
            ][
                'projected_points'
            ]
        )
        &&
        abs(
            (float) $firstAdaptedPlayer[
                'gameweeks'
            ][
                3
            ][
                'projected_points'
            ]
            -
            5.1
        )
        <
        0.0001
    );


    freeHitHorizonCheck(
        'Adapted Free Hit player preserves projection confidence',
        isset(
            $firstAdaptedPlayer[
                'gameweeks'
            ][
                3
            ][
                'projection_confidence'
            ]
        )
        &&
        abs(
            (float) $firstAdaptedPlayer[
                'gameweeks'
            ][
                3
            ][
                'projection_confidence'
            ]
            -
            0.80
        )
        <
        0.0001
    );


    freeHitHorizonCheck(
        'Successful Free Hit horizon is Available',
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


    freeHitHorizonCheck(
        'Original Free Hit build is exposed',
        (
            $result[
                'free_hit_result'
            ]
            ??
            null
        )
        ===
        $freeHitBuild
    );


    freeHitHorizonCheck(
        'One-gameweek horizon result is exposed',
        (
            $result[
                'horizon_result'
            ]
            ??
            null
        )
        ===
        $expectedHorizonResult
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO C: FREE HIT BUILD UNAVAILABLE
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario C: Free Hit Build Unavailable<br>';

    echo
        '============================================<br>';


    $unavailableFreeHitBuild = [

        'status' =>
            'Unavailable',

        'projected_player_count' =>
            0,

        'optimizer_result' =>
            null
    ];


    $freeHitService =
        new FreeHitHorizonFreeHitServiceStub();


    $freeHitService->result =
        $unavailableFreeHitBuild;


    $squadHorizon =
        new FreeHitHorizonSquadHorizonStub();


    $service =
        new FreeHitHorizonIntelligenceService(
            $freeHitService,
            $squadHorizon
        );


    $result =
        $service
            ->build(
                $playerPool,
                100.0
            );


    freeHitHorizonCheck(
        'Unavailable Free Hit build returns Unavailable',
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


    freeHitHorizonCheck(
        'Squad Horizon is not called when Free Hit build is unavailable',
        $squadHorizon
            ->buildCallCount
        ===
        0
    );


    freeHitHorizonCheck(
        'Unavailable Free Hit result is exposed',
        (
            $result[
                'free_hit_result'
            ]
            ??
            null
        )
        ===
        $unavailableFreeHitBuild
    );


    freeHitHorizonCheck(
        'Horizon result is null when Free Hit build is unavailable',
        array_key_exists(
            'horizon_result',
            $result
        )
        &&
        $result[
            'horizon_result'
        ]
        ===
        null
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO D: INVALID OPTIMIZED SQUAD
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario D: Invalid Optimized Squad<br>';

    echo
        '============================================<br>';


    $invalidFreeHitBuild = [

        'status' =>
            'Available',

        'projected_player_count' =>
            15,

        'optimizer_result' => [

            'status' =>
                'success',

            'squad' => [

                [
                    'player_id' =>
                        1
                ]
            ]
        ]
    ];


    $freeHitService =
        new FreeHitHorizonFreeHitServiceStub();


    $freeHitService->result =
        $invalidFreeHitBuild;


    $squadHorizon =
        new FreeHitHorizonSquadHorizonStub();


    $service =
        new FreeHitHorizonIntelligenceService(
            $freeHitService,
            $squadHorizon
        );


    $result =
        $service
            ->build(
                $playerPool,
                100.0
            );


    freeHitHorizonCheck(
        'Incomplete optimized squad returns Unavailable',
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


    freeHitHorizonCheck(
        'Squad Horizon is not called for incomplete optimized squad',
        $squadHorizon
            ->buildCallCount
        ===
        0
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO E: MISSING PROJECTION METADATA
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario E: Missing Projection Metadata<br>';

    echo
        '============================================<br>';


    $missingProjectionMetadataSquad =
        $optimizedSquad;


    unset(
        $missingProjectionMetadataSquad[
            0
        ][
            'projection_gameweek'
        ]
    );


    $missingMetadataFreeHitBuild = [

        'status' =>
            'Available',

        'projected_player_count' =>
            15,

        'optimizer_result' => [

            'status' =>
                'success',

            'squad' =>
                $missingProjectionMetadataSquad,

            'starting_xi_projected_points' =>
                66.0
        ]
    ];


    $freeHitService =
        new FreeHitHorizonFreeHitServiceStub();


    $freeHitService->result =
        $missingMetadataFreeHitBuild;


    $squadHorizon =
        new FreeHitHorizonSquadHorizonStub();


    $service =
        new FreeHitHorizonIntelligenceService(
            $freeHitService,
            $squadHorizon
        );


    $result =
        $service
            ->build(
                $playerPool,
                100.0
            );


    freeHitHorizonCheck(
        'Missing projection gameweek returns Unavailable',
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


    freeHitHorizonCheck(
        'Squad Horizon is not called when projection metadata is incomplete',
        $squadHorizon
            ->buildCallCount
        ===
        0
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO F: SQUAD HORIZON UNAVAILABLE
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario F: Squad Horizon Unavailable<br>';

    echo
        '============================================<br>';


    $freeHitService =
        new FreeHitHorizonFreeHitServiceStub();


    $freeHitService->result =
        $freeHitBuild;


    $unavailableHorizonResult = [

        'status' =>
            'Unavailable',

        'player_count' =>
            15,

        'horizon' =>
            1,

        'gameweeks' =>
            []
    ];


    $squadHorizon =
        new FreeHitHorizonSquadHorizonStub();


    $squadHorizon->result =
        $unavailableHorizonResult;


    $service =
        new FreeHitHorizonIntelligenceService(
            $freeHitService,
            $squadHorizon
        );


    $result =
        $service
            ->build(
                $playerPool,
                100.0
            );


    freeHitHorizonCheck(
        'Unavailable Squad Horizon returns Unavailable',
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


    freeHitHorizonCheck(
        'Unavailable horizon result is preserved',
        (
            $result[
                'horizon_result'
            ]
            ??
            null
        )
        ===
        $unavailableHorizonResult
    );
}


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


if ($failed === 0) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}