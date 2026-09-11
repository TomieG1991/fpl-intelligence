<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Decision Weight Calibration History Service Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function transferDecisionHistoryCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


function transferDecisionHistorySection(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>";
    echo "============================================<br>";
}


function transferDecisionHistorySummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Transfer Decision Weight Calibration History Service Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br><br>";


    if ($failed === 0) {

        echo "RESULT: ALL TESTS PASSED ✅";

    } else {

        echo "RESULT: TESTS FAILED ❌";
    }
}


/*
 * ============================================================
 * CONTROLLED PLAYER FACTORY
 * ============================================================
 */

function transferDecisionHistoryPlayer(
    int $playerId,
    string $position,
    int $teamId,
    float $price,
    float $intelligence = 60.0,
    float $strength = 60.0,
    float $value = 60.0,
    float $fixture = 50.0,
    float $confidence = 0.50
): array {

    return [

        'player_id' =>
            $playerId,

        'fpl_player_id' =>
            10000 + $playerId,

        'name' =>
            'Player '
            . $playerId,

        'position' =>
            $position,

        'team_id' =>
            $teamId,

        'price' =>
            $price,

        'intelligence_score' =>
            $intelligence,

        'strength_rating' =>
            $strength,

        'value_rating' =>
            $value,

        'availability_rating' =>
            100.0,

        'fixture_rating' =>
            $fixture,

        'sample_confidence' =>
            $confidence
    ];
}


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class TransferDecisionHistoryGameweekRepositoryStub
{
    public array $gameweeks;


    public int $getAllCalls =
        0;


    public function __construct(
        array $gameweeks
    ) {

        $this->gameweeks =
            $gameweeks;
    }


    public function getAll(): array
    {
        $this->getAllCalls++;

        return
            $this->gameweeks;
    }
}


class TransferDecisionHistoryEvidenceServiceStub
{
    public array $evidenceByGameweek;


    public array $calls =
        [];


    public function __construct(
        array $evidenceByGameweek
    ) {

        $this->evidenceByGameweek =
            $evidenceByGameweek;
    }


    public function getEvidence(
        int $entryId,
        int $gameweekId
    ): array {

        $this->calls[] = [

            'entry_id' =>
                $entryId,

            'gameweek_id' =>
                $gameweekId
        ];


        return
            $this->evidenceByGameweek[
                $gameweekId
            ]
            ??
            [

                'status' =>
                    'Unavailable',

                'reason' =>
                    'No controlled evidence.'
            ];
    }
}


class TransferDecisionHistoryCalibrationServiceStub
{
    public array $calls =
        [];


    public array $returnValue;


    public function __construct(
        array $returnValue
    ) {

        $this->returnValue =
            $returnValue;
    }


    public function evaluate(
        array $historicalTransfers,
        array $weightCandidates
    ): array {

        $this->calls[] = [

            'historical_transfers' =>
                $historicalTransfers,

            'weight_candidates' =>
                $weightCandidates
        ];


        return
            $this->returnValue;
    }
}


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario A: Class Contract'
);


$classExists =
    class_exists(
        'TransferDecisionWeightCalibrationHistoryService'
    );


transferDecisionHistoryCheck(
    'TransferDecisionWeightCalibrationHistoryService class exists',
    $classExists
);


transferDecisionHistoryCheck(
    'TransferDecisionWeightCalibrationHistoryService exposes evaluate()',
    $classExists
    &&
    method_exists(
        'TransferDecisionWeightCalibrationHistoryService',
        'evaluate'
    )
);


if (!$classExists) {

    transferDecisionHistorySummary();

    exit;
}


/*
 * ============================================================
 * STORED GAMEWEEKS
 * ============================================================
 */

$storedGameweeks = [

    [
        'id' =>
            1,

        'fpl_gameweek_id' =>
            1,

        'name' =>
            'Gameweek 1'
    ],

    [
        'id' =>
            2,

        'fpl_gameweek_id' =>
            2,

        'name' =>
            'Gameweek 2'
    ],

    [
        'id' =>
            3,

        'fpl_gameweek_id' =>
            3,

        'name' =>
            'Gameweek 3'
    ],

    /*
     * Malformed repository row.
     */
    'malformed gameweek',

    /*
     * Invalid local identity.
     */
    [
        'id' =>
            0,

        'fpl_gameweek_id' =>
            4,

        'name' =>
            'Invalid Gameweek'
    ]
];


/*
 * ============================================================
 * READY GAMEWEEK 1
 * PRESERVED 15-PLAYER SQUAD
 * ============================================================
 *
 * Position structure:
 *
 * GK  = 2
 * DEF = 5
 * MID = 5
 * FWD = 3
 *
 * The fixed outgoing player for Transfer Decision calibration
 * will be player 108.
 *
 * Player 108:
 *
 * MID
 * Team 4
 * £7.0m
 */

$gameweek1SquadPlayers = [

    transferDecisionHistoryPlayer(
        101,
        'GK',
        1,
        5.0
    ),

    transferDecisionHistoryPlayer(
        102,
        'GK',
        2,
        4.5
    ),

    transferDecisionHistoryPlayer(
        103,
        'DEF',
        1,
        5.5
    ),

    transferDecisionHistoryPlayer(
        104,
        'DEF',
        2,
        5.0
    ),

    transferDecisionHistoryPlayer(
        105,
        'DEF',
        3,
        4.5
    ),

    transferDecisionHistoryPlayer(
        106,
        'DEF',
        3,
        4.5
    ),

    transferDecisionHistoryPlayer(
        107,
        'DEF',
        5,
        4.0
    ),

    transferDecisionHistoryPlayer(
        108,
        'MID',
        4,
        7.0,
        55.0,
        56.0,
        58.0,
        45.0,
        0.60
    ),

    transferDecisionHistoryPlayer(
        109,
        'MID',
        1,
        8.0
    ),

    transferDecisionHistoryPlayer(
        110,
        'MID',
        2,
        7.5
    ),

    transferDecisionHistoryPlayer(
        111,
        'MID',
        3,
        6.5
    ),

    transferDecisionHistoryPlayer(
        112,
        'MID',
        5,
        6.0
    ),

    transferDecisionHistoryPlayer(
        113,
        'FWD',
        4,
        8.0
    ),

    transferDecisionHistoryPlayer(
        114,
        'FWD',
        5,
        7.0
    ),

    transferDecisionHistoryPlayer(
        115,
        'FWD',
        6,
        6.5
    )
];


/*
 * Starting XI and bench deliberately contain only recommendation
 * identity evidence.
 *
 * Transfer attributes must come from preserved player_rankings,
 * not be reconstructed from live player state.
 */

$gameweek1StartingXI = [];

$gameweek1Bench = [];


foreach (
    array_slice(
        $gameweek1SquadPlayers,
        0,
        11
    )
    as $player
) {

    $gameweek1StartingXI[] = [

        'player_id' =>
            $player[
                'player_id'
            ],

        'position' =>
            $player[
                'position'
            ]
    ];
}


foreach (
    array_slice(
        $gameweek1SquadPlayers,
        11,
        4
    )
    as $player
) {

    $gameweek1Bench[] = [

        'player_id' =>
            $player[
                'player_id'
            ],

        'position' =>
            $player[
                'position'
            ]
    ];
}


/*
 * ============================================================
 * GAMEWEEK 1 FULL PRESERVED RANKING UNIVERSE
 * ============================================================
 */

$gameweek1Rankings =
    $gameweek1SquadPlayers;


/*
 * Candidate 201:
 *
 * VALID.
 *
 * Same position, affordable, unowned, valid club.
 */
$gameweek1Rankings[] =
    transferDecisionHistoryPlayer(
        201,
        'MID',
        7,
        7.5,
        70.0,
        72.0,
        75.0,
        80.0,
        0.80
    );


/*
 * Candidate 202:
 *
 * VALID because bank = £1.0m.
 *
 * Outgoing £7.0m + bank £1.0m = £8.0m.
 */
$gameweek1Rankings[] =
    transferDecisionHistoryPlayer(
        202,
        'MID',
        8,
        8.0,
        72.0,
        70.0,
        68.0,
        78.0,
        0.75
    );


/*
 * Candidate 203:
 *
 * INVALID — unaffordable at £8.1m.
 */
$gameweek1Rankings[] =
    transferDecisionHistoryPlayer(
        203,
        'MID',
        9,
        8.1,
        95.0,
        95.0,
        95.0,
        95.0,
        1.00
    );


/*
 * Candidate 204:
 *
 * INVALID — wrong position.
 */
$gameweek1Rankings[] =
    transferDecisionHistoryPlayer(
        204,
        'DEF',
        10,
        5.0,
        95.0,
        95.0,
        95.0,
        95.0,
        1.00
    );


/*
 * Candidate 205:
 *
 * INVALID — Team 3 already has three players after this MID
 * transfer:
 *
 * 105
 * 106
 * 111
 *
 * Outgoing 108 belongs to Team 4, so Team 3 count is not reduced.
 */
$gameweek1Rankings[] =
    transferDecisionHistoryPlayer(
        205,
        'MID',
        3,
        6.0,
        95.0,
        95.0,
        95.0,
        95.0,
        1.00
    );


/*
 * Candidate 206:
 *
 * VALID — same club as outgoing player 108.
 *
 * Team 4 currently contains:
 *
 * 108
 * 113
 *
 * The outgoing player leaves before the incoming player is
 * counted, so this remains legal.
 */
$gameweek1Rankings[] =
    transferDecisionHistoryPlayer(
        206,
        'MID',
        4,
        6.5,
        67.0,
        66.0,
        70.0,
        68.0,
        0.70
    );


/*
 * Candidate 207:
 *
 * INVALID — player already owned.
 *
 * A duplicate ranking row for owned player 109 must not become
 * an incoming replacement.
 */
$gameweek1Rankings[] =
    transferDecisionHistoryPlayer(
        109,
        'MID',
        1,
        8.0,
        99.0,
        99.0,
        99.0,
        99.0,
        1.00
    );


/*
 * Candidate 208:
 *
 * INVALID — bad team identity.
 */
$invalidTeamCandidate =
    transferDecisionHistoryPlayer(
        208,
        'MID',
        0,
        6.0,
        90.0,
        90.0,
        90.0,
        90.0,
        0.90
    );


$gameweek1Rankings[] =
    $invalidTeamCandidate;


/*
 * Candidate 209:
 *
 * INVALID — non-numeric price.
 */
$invalidPriceCandidate =
    transferDecisionHistoryPlayer(
        209,
        'MID',
        11,
        6.0,
        90.0,
        90.0,
        90.0,
        90.0,
        0.90
    );


$invalidPriceCandidate[
    'price'
] =
    'not-a-price';


$gameweek1Rankings[] =
    $invalidPriceCandidate;


/*
 * Malformed ranking rows must not become candidates.
 */
$gameweek1Rankings[] =
    'malformed ranking';


/*
 * ============================================================
 * GAMEWEEK 1 OUTCOMES
 * ============================================================
 */

$gameweek1OutcomePoints = [

    108 => 2,

    201 => 8,

    202 => 10,

    206 => 5
];


$gameweek1Outcomes = [];


foreach (
    $gameweek1OutcomePoints
    as $playerId => $points
) {

    $gameweek1Outcomes[] = [

        'player_id' =>
            $playerId,

        'total_points' =>
            $points,

        'minutes' =>
            90
    ];
}


/*
 * Noise outcome — not part of the legal transfer universe.
 */
$gameweek1Outcomes[] = [

    'player_id' =>
        999,

    'total_points' =>
        20,

    'minutes' =>
        90
];


/*
 * ============================================================
 * READY GAMEWEEK 3
 * SECOND PRESERVED UNIVERSE
 * ============================================================
 */

$gameweek3SquadPlayers = [

    transferDecisionHistoryPlayer(
        301,
        'GK',
        1,
        5.0
    ),

    transferDecisionHistoryPlayer(
        302,
        'GK',
        2,
        4.5
    ),

    transferDecisionHistoryPlayer(
        303,
        'DEF',
        1,
        5.5
    ),

    transferDecisionHistoryPlayer(
        304,
        'DEF',
        2,
        5.0
    ),

    transferDecisionHistoryPlayer(
        305,
        'DEF',
        3,
        4.5
    ),

    transferDecisionHistoryPlayer(
        306,
        'DEF',
        4,
        4.5
    ),

    transferDecisionHistoryPlayer(
        307,
        'DEF',
        5,
        4.0
    ),

    transferDecisionHistoryPlayer(
        308,
        'MID',
        1,
        8.0
    ),

    transferDecisionHistoryPlayer(
        309,
        'MID',
        2,
        7.5
    ),

    transferDecisionHistoryPlayer(
        310,
        'MID',
        3,
        7.0
    ),

    transferDecisionHistoryPlayer(
        311,
        'MID',
        4,
        6.5
    ),

    transferDecisionHistoryPlayer(
        312,
        'MID',
        5,
        6.0
    ),

    /*
     * Fixed outgoing.
     */
    transferDecisionHistoryPlayer(
        313,
        'FWD',
        6,
        7.0,
        50.0,
        55.0,
        52.0,
        48.0,
        0.50
    ),

    transferDecisionHistoryPlayer(
        314,
        'FWD',
        7,
        8.0
    ),

    transferDecisionHistoryPlayer(
        315,
        'FWD',
        8,
        6.5
    )
];


$gameweek3StartingXI = [];

$gameweek3Bench = [];


foreach (
    array_slice(
        $gameweek3SquadPlayers,
        0,
        11
    )
    as $player
) {

    $gameweek3StartingXI[] = [

        'player_id' =>
            $player[
                'player_id'
            ],

        'position' =>
            $player[
                'position'
            ]
    ];
}


foreach (
    array_slice(
        $gameweek3SquadPlayers,
        11,
        4
    )
    as $player
) {

    $gameweek3Bench[] = [

        'player_id' =>
            $player[
                'player_id'
            ],

        'position' =>
            $player[
                'position'
            ]
    ];
}


$gameweek3Rankings =
    $gameweek3SquadPlayers;


/*
 * Two legal FWD replacements.
 */
$gameweek3Rankings[] =
    transferDecisionHistoryPlayer(
        401,
        'FWD',
        9,
        7.0,
        65.0,
        66.0,
        67.0,
        70.0,
        0.70
    );


$gameweek3Rankings[] =
    transferDecisionHistoryPlayer(
        402,
        'FWD',
        10,
        6.0,
        68.0,
        65.0,
        72.0,
        74.0,
        0.80
    );


$gameweek3Outcomes = [

    [
        'player_id' =>
            313,

        /*
         * Negative realised points remain valid evidence.
         */
        'total_points' =>
            -1,

        'minutes' =>
            90
    ],

    [
        'player_id' =>
            401,

        /*
         * Zero is genuine evidence.
         */
        'total_points' =>
            0,

        'minutes' =>
            90
    ],

    [
        'player_id' =>
            402,

        'total_points' =>
            6,

        'minutes' =>
            90
    ]
];


/*
 * ============================================================
 * AUTHORITATIVE EVIDENCE
 * ============================================================
 */

$evidenceByGameweek = [

    /*
     * --------------------------------------------------------
     * GW1 READY
     * --------------------------------------------------------
     */
    1 => [

        'status' =>
            'Ready',

        'reason' =>
            null,

        'recommendation_snapshot' => [

            'starting_xi' =>
                $gameweek1StartingXI,

            'bench' =>
                $gameweek1Bench,

            'player_rankings' =>
                $gameweek1Rankings,

            /*
             * This is the preserved production transfer result.
             *
             * Bank belongs to the historical recommendation-time
             * state.
             *
             * Only recommendations[0].outgoing fixes the outgoing
             * player for this first TransferDecision calibration
             * layer.
             */
            'transfer_recommendations' => [

                'status' =>
                    'success',

                'bank' =>
                    1.0,

                'recommendations' => [

                    [
                        'outgoing' => [

                            'player_id' =>
                                108
                        ],

                        /*
                         * Preserved replacement list is deliberately
                         * incomplete.
                         *
                         * History calibration must reconstruct the
                         * complete legal universe from player_rankings
                         * rather than calibrating only against the
                         * production top-five replacements.
                         */
                        'replacements' => [

                            [
                                'player' => [

                                    'player_id' =>
                                        201
                                ]
                            ]
                        ]
                    ],

                    /*
                     * A second outgoing recommendation exists but
                     * must not enter this first incoming-weight
                     * calibration layer.
                     */
                    [
                        'outgoing' => [

                            'player_id' =>
                                109
                        ],

                        'replacements' =>
                            []
                    ]
                ]
            ]
        ],

        'player_outcomes' =>
            $gameweek1Outcomes
    ],


    /*
     * --------------------------------------------------------
     * GW2 NOT READY
     * --------------------------------------------------------
     */
    2 => [

        'status' =>
            'Incomplete',

        'reason' =>
            'Recommendation snapshot is unavailable.'
    ],


    /*
     * --------------------------------------------------------
     * GW3 READY
     * --------------------------------------------------------
     */
    3 => [

        'status' =>
            'Ready',

        'reason' =>
            null,

        'recommendation_snapshot' => [

            'starting_xi' =>
                $gameweek3StartingXI,

            'bench' =>
                $gameweek3Bench,

            'player_rankings' =>
                $gameweek3Rankings,

            'transfer_recommendations' => [

                'status' =>
                    'success',

                /*
                 * Exact zero bank must remain valid.
                 */
                'bank' =>
                    0.0,

                'recommendations' => [

                    [
                        'outgoing' => [

                            'player_id' =>
                                313
                        ],

                        'replacements' =>
                            []
                    ]
                ]
            ]
        ],

        'player_outcomes' =>
            $gameweek3Outcomes
    ]
];


/*
 * ============================================================
 * WEIGHT CANDIDATES
 * ============================================================
 */

$weightCandidates = [

    [
        'intelligence_weight' =>
            0.40,

        'fixture_weight' =>
            0.20,

        'value_weight' =>
            0.15,

        'strength_weight' =>
            0.10,

        'budget_weight' =>
            0.10,

        'confidence_weight' =>
            0.05
    ],

    [
        'intelligence_weight' =>
            0.10,

        'fixture_weight' =>
            0.70,

        'value_weight' =>
            0.05,

        'strength_weight' =>
            0.05,

        'budget_weight' =>
            0.05,

        'confidence_weight' =>
            0.05
    ]
];


$calibrationReturnValue = [

    'evaluations' => [

        [
            'marker' =>
                'controlled calibration result'
        ]
    ]
];


$gameweekRepository =
    new TransferDecisionHistoryGameweekRepositoryStub(
        $storedGameweeks
    );


$evidenceService =
    new TransferDecisionHistoryEvidenceServiceStub(
        $evidenceByGameweek
    );


$calibrationService =
    new TransferDecisionHistoryCalibrationServiceStub(
        $calibrationReturnValue
    );


/*
 * ============================================================
 * SCENARIO B
 * CONSTRUCTOR / ORCHESTRATION CONTRACT
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario B: History Orchestration'
);


$service =
    new TransferDecisionWeightCalibrationHistoryService(
        $gameweekRepository,
        $evidenceService,
        $calibrationService
    );


$result =
    $service->evaluate(
        2702264,
        $weightCandidates
    );


transferDecisionHistoryCheck(
    'Entry ID is preserved',
    ($result['entry_id'] ?? null) === 2702264
);


transferDecisionHistoryCheck(
    'Gameweek repository is queried exactly once',
    $gameweekRepository->getAllCalls === 1
);


transferDecisionHistoryCheck(
    'Only valid positive stored gameweeks are counted',
    ($result['total_gameweeks'] ?? null) === 3
);


transferDecisionHistoryCheck(
    'Two authoritative Ready gameweeks are counted',
    ($result['ready_gameweeks'] ?? null) === 2
);


/*
 * ============================================================
 * SCENARIO C
 * AUTHORITATIVE EVIDENCE LOOKUPS
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario C: Authoritative Evidence'
);


transferDecisionHistoryCheck(
    'Evidence service is called once for every valid stored gameweek',
    count(
        $evidenceService->calls
    )
    === 3
);


transferDecisionHistoryCheck(
    'Evidence lookups use the requested FPL entry',
    array_column(
        $evidenceService->calls,
        'entry_id'
    )
    === [
        2702264,
        2702264,
        2702264
    ]
);


transferDecisionHistoryCheck(
    'Evidence lookups preserve local gameweek IDs',
    array_column(
        $evidenceService->calls,
        'gameweek_id'
    )
    === [
        1,
        2,
        3
    ]
);


/*
 * ============================================================
 * SCENARIO D
 * GAMEWEEK AUDIT
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario D: Gameweek Audit'
);


$gameweekAudit =
    $result[
        'gameweeks'
    ]
    ?? [];


transferDecisionHistoryCheck(
    'Every valid stored gameweek remains in the audit',
    count(
        $gameweekAudit
    )
    === 3
);


transferDecisionHistoryCheck(
    'Audit preserves local gameweek identity',
    array_column(
        $gameweekAudit,
        'gameweek_id'
    )
    === [
        1,
        2,
        3
    ]
);


transferDecisionHistoryCheck(
    'Audit preserves FPL gameweek identity',
    array_column(
        $gameweekAudit,
        'fpl_gameweek_id'
    )
    === [
        1,
        2,
        3
    ]
);


transferDecisionHistoryCheck(
    'Audit preserves gameweek names',
    array_column(
        $gameweekAudit,
        'name'
    )
    === [
        'Gameweek 1',
        'Gameweek 2',
        'Gameweek 3'
    ]
);


transferDecisionHistoryCheck(
    'Audit preserves authoritative status',
    array_column(
        $gameweekAudit,
        'status'
    )
    === [
        'Ready',
        'Incomplete',
        'Ready'
    ]
);


transferDecisionHistoryCheck(
    'Incomplete reason remains auditable',
    (
        $gameweekAudit[
            1
        ]['reason']
        ?? null
    )
    ===
    'Recommendation snapshot is unavailable.'
);


/*
 * ============================================================
 * SCENARIO E
 * READY-ONLY CALIBRATION HISTORY
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario E: Ready-Only Historical Transfers'
);


$historicalTransfers =
    $result[
        'historical_transfers'
    ]
    ?? [];


transferDecisionHistoryCheck(
    'Only Ready gameweeks contribute historical transfer universes',
    count(
        $historicalTransfers
    )
    === 2
);


transferDecisionHistoryCheck(
    'Historical universes preserve Ready gameweek identity',
    array_column(
        $historicalTransfers,
        'gameweek_id'
    )
    === [
        1,
        3
    ]
);


/*
 * ============================================================
 * SCENARIO F
 * PRESERVED BANK + FIXED OUTGOING
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario F: Bank and Fixed Outgoing'
);


$historicalTransferOne =
    $historicalTransfers[
        0
    ]
    ?? [];


$historicalTransferThree =
    $historicalTransfers[
        1
    ]
    ?? [];


transferDecisionHistoryCheck(
    'GW1 recommendation-time bank is preserved',
    ($historicalTransferOne['bank'] ?? null) === 1.0
);


transferDecisionHistoryCheck(
    'Exact zero bank remains valid',
    array_key_exists(
        'bank',
        $historicalTransferThree
    )
    &&
    $historicalTransferThree[
        'bank'
    ] === 0.0
);


transferDecisionHistoryCheck(
    'GW1 highest-priority preserved outgoing player is fixed',
    (
        $historicalTransferOne[
            'outgoing'
        ]['player_id']
        ?? null
    )
    === 108
);


transferDecisionHistoryCheck(
    'GW3 highest-priority preserved outgoing player is fixed',
    (
        $historicalTransferThree[
            'outgoing'
        ]['player_id']
        ?? null
    )
    === 313
);


transferDecisionHistoryCheck(
    'Second preserved outgoing recommendation does not become another calibration universe',
    count(
        $historicalTransfers
    )
    === 2
);


/*
 * ============================================================
 * SCENARIO G
 * OUTGOING EVIDENCE FROM PRESERVED PLAYER RANKINGS
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario G: Outgoing Calibration Evidence'
);


$gw1Outgoing =
    $historicalTransferOne[
        'outgoing'
    ]
    ?? [];


transferDecisionHistoryCheck(
    'Outgoing position comes from preserved ranking evidence',
    ($gw1Outgoing['position'] ?? null) === 'MID'
);


transferDecisionHistoryCheck(
    'Outgoing club comes from preserved ranking evidence',
    ($gw1Outgoing['team_id'] ?? null) === 4
);


transferDecisionHistoryCheck(
    'Outgoing price comes from preserved ranking evidence',
    ($gw1Outgoing['price'] ?? null) === 7.0
);


transferDecisionHistoryCheck(
    'Outgoing Intelligence is preserved',
    ($gw1Outgoing['intelligence_score'] ?? null) === 55.0
);


transferDecisionHistoryCheck(
    'Outgoing Strength is preserved',
    ($gw1Outgoing['strength_rating'] ?? null) === 56.0
);


transferDecisionHistoryCheck(
    'Outgoing Value is preserved',
    ($gw1Outgoing['value_rating'] ?? null) === 58.0
);


transferDecisionHistoryCheck(
    'Outgoing Fixture is preserved',
    ($gw1Outgoing['fixture_rating'] ?? null) === 45.0
);


transferDecisionHistoryCheck(
    'Outgoing Sample Confidence is preserved',
    ($gw1Outgoing['sample_confidence'] ?? null) === 0.60
);


/*
 * ============================================================
 * SCENARIO H
 * AUTHORITATIVE OUTGOING OUTCOME
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario H: Outgoing Outcome'
);


transferDecisionHistoryCheck(
    'GW1 outgoing realised points are mapped',
    ($gw1Outgoing['actual_points'] ?? null) === 2
);


transferDecisionHistoryCheck(
    'Negative outgoing realised points remain valid',
    (
        $historicalTransferThree[
            'outgoing'
        ]['actual_points']
        ?? null
    )
    === -1
);


/*
 * ============================================================
 * SCENARIO I
 * COMPLETE LEGAL REPLACEMENT RECONSTRUCTION
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario I: Legal Replacement Reconstruction'
);


$gw1Replacements =
    $historicalTransferOne[
        'replacements'
    ]
    ?? [];


$gw1ReplacementIds =
    array_column(
        $gw1Replacements,
        'player_id'
    );


sort(
    $gw1ReplacementIds
);


transferDecisionHistoryCheck(
    'Complete GW1 legal replacement universe is reconstructed',
    $gw1ReplacementIds
    === [
        201,
        202,
        206
    ]
);


transferDecisionHistoryCheck(
    'Calibration universe is not restricted to preserved production replacement top-N',
    in_array(
        202,
        $gw1ReplacementIds,
        true
    )
);


transferDecisionHistoryCheck(
    'Same-club incoming player remains legal after outgoing player leaves',
    in_array(
        206,
        $gw1ReplacementIds,
        true
    )
);


/*
 * ============================================================
 * SCENARIO J
 * OWNED-PLAYER EXCLUSION
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario J: Already-Owned Exclusion'
);


transferDecisionHistoryCheck(
    'Outgoing player cannot replace itself',
    !in_array(
        108,
        $gw1ReplacementIds,
        true
    )
);


transferDecisionHistoryCheck(
    'Already-owned player cannot enter the replacement universe',
    !in_array(
        109,
        $gw1ReplacementIds,
        true
    )
);


/*
 * ============================================================
 * SCENARIO K
 * POSITION FILTER
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario K: Position Legality'
);


transferDecisionHistoryCheck(
    'Wrong-position candidate is excluded',
    !in_array(
        204,
        $gw1ReplacementIds,
        true
    )
);


transferDecisionHistoryCheck(
    'Every reconstructed GW1 replacement preserves outgoing position',
    count(
        array_filter(
            $gw1Replacements,
            static function (
                array $replacement
            ): bool {

                return (
                    $replacement[
                        'position'
                    ]
                    ?? null
                )
                !==
                'MID';
            }
        )
    )
    === 0
);


/*
 * ============================================================
 * SCENARIO L
 * AFFORDABILITY
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario L: Affordability'
);


transferDecisionHistoryCheck(
    'Candidate affordable only because of preserved bank is included',
    in_array(
        202,
        $gw1ReplacementIds,
        true
    )
);


transferDecisionHistoryCheck(
    'Candidate above outgoing price plus bank is excluded',
    !in_array(
        203,
        $gw1ReplacementIds,
        true
    )
);


/*
 * ============================================================
 * SCENARIO M
 * CLUB LIMIT
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario M: Club Limit'
);


transferDecisionHistoryCheck(
    'Incoming player creating a fourth player from one club is excluded',
    !in_array(
        205,
        $gw1ReplacementIds,
        true
    )
);


transferDecisionHistoryCheck(
    'Same-club outgoing replacement correctly reduces club count before incoming is counted',
    in_array(
        206,
        $gw1ReplacementIds,
        true
    )
);


/*
 * ============================================================
 * SCENARIO N
 * INVALID CANDIDATE EVIDENCE
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario N: Invalid Candidate Evidence'
);


transferDecisionHistoryCheck(
    'Candidate with invalid team ID is excluded',
    !in_array(
        208,
        $gw1ReplacementIds,
        true
    )
);


transferDecisionHistoryCheck(
    'Candidate with non-numeric price is excluded',
    !in_array(
        209,
        $gw1ReplacementIds,
        true
    )
);


/*
 * ============================================================
 * SCENARIO O
 * REPLACEMENT EVIDENCE
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario O: Replacement Calibration Evidence'
);


$replacement201 =
    null;


foreach (
    $gw1Replacements
    as $replacement
) {

    if (
        (
            $replacement[
                'player_id'
            ]
            ?? null
        )
        ===
        201
    ) {

        $replacement201 =
            $replacement;

        break;
    }
}


transferDecisionHistoryCheck(
    'Legal replacement Intelligence is preserved',
    ($replacement201['intelligence_score'] ?? null) === 70.0
);


transferDecisionHistoryCheck(
    'Legal replacement Strength is preserved',
    ($replacement201['strength_rating'] ?? null) === 72.0
);


transferDecisionHistoryCheck(
    'Legal replacement Value is preserved',
    ($replacement201['value_rating'] ?? null) === 75.0
);


transferDecisionHistoryCheck(
    'Legal replacement Fixture is preserved',
    ($replacement201['fixture_rating'] ?? null) === 80.0
);


transferDecisionHistoryCheck(
    'Legal replacement Sample Confidence is preserved',
    ($replacement201['sample_confidence'] ?? null) === 0.80
);


transferDecisionHistoryCheck(
    'Legal replacement price is preserved',
    ($replacement201['price'] ?? null) === 7.5
);


transferDecisionHistoryCheck(
    'Legal replacement club identity is preserved',
    ($replacement201['team_id'] ?? null) === 7
);


/*
 * ============================================================
 * SCENARIO P
 * REPLACEMENT OUTCOMES
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario P: Replacement Outcomes'
);


transferDecisionHistoryCheck(
    'GW1 legal replacement realised points are mapped',
    ($replacement201['actual_points'] ?? null) === 8
);


$gw3Replacements =
    $historicalTransferThree[
        'replacements'
    ]
    ?? [];


$replacement401 =
    null;


foreach (
    $gw3Replacements
    as $replacement
) {

    if (
        (
            $replacement[
                'player_id'
            ]
            ?? null
        )
        ===
        401
    ) {

        $replacement401 =
            $replacement;

        break;
    }
}


transferDecisionHistoryCheck(
    'Zero replacement realised points remain valid',
    array_key_exists(
        'actual_points',
        $replacement401
        ?? []
    )
    &&
    $replacement401[
        'actual_points'
    ] === 0
);


/*
 * ============================================================
 * SCENARIO Q
 * PURE CALIBRATOR DELEGATION
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario Q: Calibration Delegation'
);


transferDecisionHistoryCheck(
    'Transfer Decision calibration service is called exactly once',
    count(
        $calibrationService->calls
    )
    === 1
);


$calibrationCall =
    $calibrationService->calls[
        0
    ]
    ?? [];


transferDecisionHistoryCheck(
    'All Ready historical transfer universes are delegated together',
    (
        $calibrationCall[
            'historical_transfers'
        ]
        ?? null
    )
    ===
    $historicalTransfers
);


transferDecisionHistoryCheck(
    'Caller-supplied weight candidates are delegated unchanged',
    (
        $calibrationCall[
            'weight_candidates'
        ]
        ?? null
    )
    ===
    $weightCandidates
);


transferDecisionHistoryCheck(
    'Calibration result is returned unchanged',
    (
        $result[
            'calibration'
        ]
        ?? null
    )
    ===
    $calibrationReturnValue
);


/*
 * ============================================================
 * SCENARIO R
 * INVALID ENTRY ID
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario R: Entry Validation'
);


$invalidEntryThrew =
    false;


try {

    $service->evaluate(
        0,
        $weightCandidates
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidEntryThrew =
        $exception->getMessage()
        ===
        'FPL entry ID must be positive.';
}


transferDecisionHistoryCheck(
    'Non-positive FPL entry ID is rejected',
    $invalidEntryThrew
);


/*
 * ============================================================
 * SCENARIO S
 * EMPTY STORED GAMEWEEK COLLECTION
 * ============================================================
 */

transferDecisionHistorySection(
    'Scenario S: Empty History'
);


$emptyGameweekRepository =
    new TransferDecisionHistoryGameweekRepositoryStub(
        []
    );


$emptyEvidenceService =
    new TransferDecisionHistoryEvidenceServiceStub(
        []
    );


$emptyCalibrationService =
    new TransferDecisionHistoryCalibrationServiceStub(
        [
            'evaluations' =>
                []
        ]
    );


$emptyService =
    new TransferDecisionWeightCalibrationHistoryService(
        $emptyGameweekRepository,
        $emptyEvidenceService,
        $emptyCalibrationService
    );


$emptyResult =
    $emptyService->evaluate(
        2702264,
        $weightCandidates
    );


transferDecisionHistoryCheck(
    'Empty repository produces zero stored gameweeks',
    ($emptyResult['total_gameweeks'] ?? null) === 0
);


transferDecisionHistoryCheck(
    'Empty repository produces zero Ready gameweeks',
    ($emptyResult['ready_gameweeks'] ?? null) === 0
);


transferDecisionHistoryCheck(
    'Empty repository produces empty audit',
    ($emptyResult['gameweeks'] ?? null) === []
);


transferDecisionHistoryCheck(
    'Empty repository produces no historical transfer universes',
    ($emptyResult['historical_transfers'] ?? null) === []
);


transferDecisionHistoryCheck(
    'Pure calibrator is still called exactly once for empty history',
    count(
        $emptyCalibrationService->calls
    )
    === 1
);


transferDecisionHistoryCheck(
    'Empty historical transfer collection is delegated',
    (
        $emptyCalibrationService
            ->calls[
                0
            ]['historical_transfers']
        ?? null
    )
    === []
);


transferDecisionHistoryCheck(
    'Weight candidates still pass through when history is empty',
    (
        $emptyCalibrationService
            ->calls[
                0
            ]['weight_candidates']
        ?? null
    )
    ===
    $weightCandidates
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

transferDecisionHistorySummary();