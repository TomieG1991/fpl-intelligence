<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Transfer Priority Weight Calibration History Service Test<br>";
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

function transferPriorityHistoryCheck(
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


function transferPriorityHistorySection(
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


function transferPriorityHistorySummary(): void
{
    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Transfer Priority Weight Calibration History Service Test Summary<br>";
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


function transferPriorityHistoryPlayer(
    int $playerId,
    string $position,
    int $teamId,
    float $price,
    float $intelligence = 60.0,
    float $value = 60.0,
    float $fixture = 60.0,
    float $availability = 100.0
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
            60.0,

        'value_rating' =>
            $value,

        'availability_rating' =>
            $availability,

        'fixture_rating' =>
            $fixture,

        'sample_confidence' =>
            0.75
    ];
}


function transferPriorityHistoryOutcome(
    int $playerId,
    int|float|null $points
): array {

    return [

        'player_id' =>
            $playerId,

        'total_points' =>
            $points
    ];
}


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class TransferPriorityHistoryGameweekRepositoryStub
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


class TransferPriorityHistoryEvidenceServiceStub
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


class TransferPriorityHistoryCalibrationServiceStub
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
        array $historicalGameweeks,
        array $weightCandidates
    ): array {

        $this->calls[] = [

            'historical_gameweeks' =>
                $historicalGameweeks,

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

transferPriorityHistorySection(
    'Scenario A: Class Contract'
);


$classExists =
    class_exists(
        'TransferPriorityWeightCalibrationHistoryService'
    );


transferPriorityHistoryCheck(
    'TransferPriorityWeightCalibrationHistoryService class exists',
    $classExists
);


transferPriorityHistoryCheck(
    'TransferPriorityWeightCalibrationHistoryService exposes evaluate()',
    $classExists
    &&
    method_exists(
        'TransferPriorityWeightCalibrationHistoryService',
        'evaluate'
    )
);


if (!$classExists) {

    transferPriorityHistorySummary();

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

    'malformed gameweek',

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
 * GAMEWEEK 1 SQUAD
 * ============================================================
 *
 * Valid 15-player FPL structure.
 *
 * Team 3 already has three players:
 *
 * 105
 * 106
 * 111
 *
 * This lets us test the club-limit rule separately for every
 * possible outgoing player.
 */

$gameweek1SquadPlayers = [

    transferPriorityHistoryPlayer(
        101,
        'GK',
        1,
        5.0,
        70.0,
        70.0,
        70.0,
        100.0
    ),

    transferPriorityHistoryPlayer(
        102,
        'GK',
        2,
        4.5
    ),

    transferPriorityHistoryPlayer(
        103,
        'DEF',
        1,
        5.5
    ),

    transferPriorityHistoryPlayer(
        104,
        'DEF',
        2,
        5.0
    ),

    transferPriorityHistoryPlayer(
        105,
        'DEF',
        3,
        4.5
    ),

    transferPriorityHistoryPlayer(
        106,
        'DEF',
        3,
        4.5
    ),

    transferPriorityHistoryPlayer(
        107,
        'DEF',
        5,
        4.0
    ),

    transferPriorityHistoryPlayer(
        108,
        'MID',
        4,
        7.0,
        45.0,
        50.0,
        40.0,
        100.0
    ),

    transferPriorityHistoryPlayer(
        109,
        'MID',
        1,
        8.0
    ),

    transferPriorityHistoryPlayer(
        110,
        'MID',
        2,
        7.5
    ),

    transferPriorityHistoryPlayer(
        111,
        'MID',
        3,
        6.5
    ),

    transferPriorityHistoryPlayer(
        112,
        'MID',
        5,
        6.0
    ),

    transferPriorityHistoryPlayer(
        113,
        'FWD',
        4,
        8.0
    ),

    transferPriorityHistoryPlayer(
        114,
        'FWD',
        5,
        7.0
    ),

    transferPriorityHistoryPlayer(
        115,
        'FWD',
        6,
        6.5
    )
];


$gameweek1StartingXI =
    [];

$gameweek1Bench =
    [];


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
 * GAMEWEEK 1 FULL RANKING UNIVERSE
 * ============================================================
 */

$gameweek1Rankings =
    $gameweek1SquadPlayers;


/*
 * Valid GK replacements.
 */
$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        201,
        'GK',
        7,
        5.5,
        80.0,
        80.0,
        80.0,
        100.0
    );

$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        202,
        'GK',
        8,
        6.5
    );


/*
 * Valid DEF replacement.
 */
$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        203,
        'DEF',
        7,
        5.0
    );


/*
 * Team 3 DEF.
 *
 * Illegal when the outgoing player is NOT from Team 3 because
 * Team 3 already has three owned players.
 *
 * Legal when outgoing is 105 or 106 because one Team 3 player
 * leaves first.
 */
$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        204,
        'DEF',
        3,
        4.5
    );


/*
 * MID replacements.
 */
$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        205,
        'MID',
        7,
        7.5
    );

$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        206,
        'MID',
        8,
        8.0
    );


/*
 * Team 3 MID.
 *
 * Illegal for outgoing 108, 109, 110, 112.
 *
 * Legal for outgoing 111 because Team 3 count falls from 3 to 2
 * before replacement is added.
 */
$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        207,
        'MID',
        3,
        6.0
    );


/*
 * Same-team MID replacement for outgoing 108.
 */
$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        208,
        'MID',
        4,
        6.5
    );


/*
 * FWD replacements.
 */
$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        209,
        'FWD',
        7,
        7.0
    );

$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        210,
        'FWD',
        8,
        8.5
    );


/*
 * Invalid candidate — bad team identity.
 */
$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        211,
        'MID',
        0,
        6.0
    );


/*
 * Invalid candidate — non-numeric price.
 */
$invalidPriceCandidate =
    transferPriorityHistoryPlayer(
        212,
        'MID',
        9,
        6.0
    );

$invalidPriceCandidate[
    'price'
] =
    'invalid';

$gameweek1Rankings[] =
    $invalidPriceCandidate;


/*
 * Duplicate row for an already-owned player.
 *
 * Must not become a legal incoming candidate and must not replace
 * the first preserved ranking row.
 */
$gameweek1Rankings[] =
    transferPriorityHistoryPlayer(
        109,
        'MID',
        10,
        1.0,
        99.0,
        99.0,
        99.0,
        99.0
    );


$gameweek1Rankings[] =
    'malformed ranking row';


/*
 * ============================================================
 * GAMEWEEK 1 OUTCOMES
 * ============================================================
 *
 * Squad actuals:
 *
 * 101 => 2
 * 102 => 4
 * 103 => 3
 * 104 => 2
 * 105 => 1
 * 106 => 5
 * 107 => 2
 * 108 => 2
 * 109 => 6
 * 110 => 4
 * 111 => 3
 * 112 => 5
 * 113 => 7
 * 114 => 2
 * 115 => 1
 *
 * Replacement actuals:
 *
 * 201 => 8
 * 202 => 10
 * 203 => 7
 * 204 => 9
 * 205 => 8
 * 206 => 10
 * 207 => 12
 * 208 => 5
 * 209 => 6
 * 210 => 11
 */

$gameweek1Outcomes = [

    transferPriorityHistoryOutcome(
        101,
        2
    ),

    transferPriorityHistoryOutcome(
        102,
        4
    ),

    transferPriorityHistoryOutcome(
        103,
        3
    ),

    transferPriorityHistoryOutcome(
        104,
        2
    ),

    transferPriorityHistoryOutcome(
        105,
        1
    ),

    transferPriorityHistoryOutcome(
        106,
        5
    ),

    transferPriorityHistoryOutcome(
        107,
        2
    ),

    transferPriorityHistoryOutcome(
        108,
        2
    ),

    transferPriorityHistoryOutcome(
        109,
        6
    ),

    transferPriorityHistoryOutcome(
        110,
        4
    ),

    transferPriorityHistoryOutcome(
        111,
        3
    ),

    transferPriorityHistoryOutcome(
        112,
        5
    ),

    transferPriorityHistoryOutcome(
        113,
        7
    ),

    transferPriorityHistoryOutcome(
        114,
        2
    ),

    transferPriorityHistoryOutcome(
        115,
        1
    ),

    transferPriorityHistoryOutcome(
        201,
        8
    ),

    transferPriorityHistoryOutcome(
        202,
        10
    ),

    transferPriorityHistoryOutcome(
        203,
        7
    ),

    transferPriorityHistoryOutcome(
        204,
        9
    ),

    transferPriorityHistoryOutcome(
        205,
        8
    ),

    transferPriorityHistoryOutcome(
        206,
        10
    ),

    transferPriorityHistoryOutcome(
        207,
        12
    ),

    transferPriorityHistoryOutcome(
        208,
        5
    ),

    transferPriorityHistoryOutcome(
        209,
        6
    ),

    transferPriorityHistoryOutcome(
        210,
        11
    ),

    /*
     * Irrelevant outcome noise.
     */
    transferPriorityHistoryOutcome(
        999,
        20
    ),

    'malformed outcome'
];


/*
 * ============================================================
 * GAMEWEEK 1 EXPECTED REALISED GAINS
 * ============================================================
 *
 * Preserved bank = £1.0m.
 *
 * Player 108:
 *
 * price £7.0 + £1.0 bank = £8.0
 *
 * legal MIDs:
 *
 * 205 => actual 8
 * 206 => actual 10
 * 208 => actual 5
 *
 * 207 is illegal because Team 3 already has 3 players and
 * outgoing 108 is Team 4.
 *
 * Best incoming = 206 with 10.
 *
 * outgoing actual = 2
 *
 * best realised transfer gain = +8.
 *
 *
 * Player 111:
 *
 * price £6.5 + £1.0 = £7.5
 *
 * legal:
 *
 * 205 => actual 8
 * 207 => actual 12
 * 208 => actual 5
 *
 * 207 becomes legal because outgoing 111 itself leaves Team 3.
 *
 * outgoing actual = 3
 *
 * best realised transfer gain = +9.
 *
 *
 * Player 105:
 *
 * price £4.5 + £1.0 = £5.5
 *
 * legal DEF:
 *
 * 203 => 7
 * 204 => 9
 *
 * Team 3 candidate 204 becomes legal because 105 leaves Team 3.
 *
 * outgoing actual = 1
 *
 * best realised transfer gain = +8.
 */


/*
 * ============================================================
 * GAMEWEEK 3
 * SECOND READY HISTORY
 * ============================================================
 */

$gameweek3SquadPlayers = [

    transferPriorityHistoryPlayer(301, 'GK', 1, 5.0),
    transferPriorityHistoryPlayer(302, 'GK', 2, 4.5),

    transferPriorityHistoryPlayer(303, 'DEF', 1, 5.5),
    transferPriorityHistoryPlayer(304, 'DEF', 2, 5.0),
    transferPriorityHistoryPlayer(305, 'DEF', 3, 4.5),
    transferPriorityHistoryPlayer(306, 'DEF', 4, 4.5),
    transferPriorityHistoryPlayer(307, 'DEF', 5, 4.0),

    transferPriorityHistoryPlayer(308, 'MID', 1, 8.0),
    transferPriorityHistoryPlayer(309, 'MID', 2, 7.5),
    transferPriorityHistoryPlayer(310, 'MID', 3, 7.0),
    transferPriorityHistoryPlayer(311, 'MID', 4, 6.5),
    transferPriorityHistoryPlayer(312, 'MID', 5, 6.0),

    transferPriorityHistoryPlayer(
        313,
        'FWD',
        6,
        7.0,
        35.0,
        40.0,
        30.0,
        100.0
    ),

    transferPriorityHistoryPlayer(314, 'FWD', 7, 7.0),
    transferPriorityHistoryPlayer(315, 'FWD', 8, 6.5)
];


$gameweek3StartingXI =
    [];

$gameweek3Bench =
    [];


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
            ]
    ];
}


$gameweek3Rankings =
    $gameweek3SquadPlayers;


$gameweek3Rankings[] =
    transferPriorityHistoryPlayer(
        401,
        'FWD',
        9,
        6.5
    );


$gameweek3Rankings[] =
    transferPriorityHistoryPlayer(
        402,
        'FWD',
        10,
        7.0
    );


$gameweek3Outcomes = [];


foreach (
    $gameweek3SquadPlayers
    as $player
) {

    $points =
        $player[
            'player_id'
        ] === 313
            ? -1
            : 2;


    $gameweek3Outcomes[] =
        transferPriorityHistoryOutcome(
            $player[
                'player_id'
            ],
            $points
        );
}


$gameweek3Outcomes[] =
    transferPriorityHistoryOutcome(
        401,
        0
    );


$gameweek3Outcomes[] =
    transferPriorityHistoryOutcome(
        402,
        6
    );


/*
 * ============================================================
 * CONTROLLED EVIDENCE
 * ============================================================
 */

$evidenceByGameweek = [

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

            'transfer_recommendations' => [

                'bank' =>
                    1.0,

                /*
                 * These preserved recommendations must NOT limit
                 * outgoing calibration.
                 */
                'recommendations' => [

                    [
                        'outgoing' => [

                            'player_id' =>
                                108
                        ],

                        'replacements' => [

                            [
                                'incoming' => [

                                    'player_id' =>
                                        205
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],

        'player_outcomes' =>
            $gameweek1Outcomes
    ],


    /*
     * Incomplete evidence must remain auditable but must not
     * enter calibration history.
     */
    2 => [

        'status' =>
            'Incomplete',

        'reason' =>
            'Recommendation snapshot is unavailable.'
    ],


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

                'bank' =>
                    0.0,

                'recommendations' =>
                    []
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
            0.45,

        'value_weight' =>
            0.20,

        'fixture_weight' =>
            0.15,

        'availability_weight' =>
            0.20
    ],

    [
        'intelligence_weight' =>
            0.25,

        'value_weight' =>
            0.25,

        'fixture_weight' =>
            0.25,

        'availability_weight' =>
            0.25
    ]
];


$calibrationReturnValue = [

    'evaluations' => [

        [
            'controlled_result' =>
                true
        ]
    ]
];


$gameweekRepository =
    new TransferPriorityHistoryGameweekRepositoryStub(
        $storedGameweeks
    );


$evidenceService =
    new TransferPriorityHistoryEvidenceServiceStub(
        $evidenceByGameweek
    );


$calibrationService =
    new TransferPriorityHistoryCalibrationServiceStub(
        $calibrationReturnValue
    );


$service =
    new TransferPriorityWeightCalibrationHistoryService(
        $gameweekRepository,
        $evidenceService,
        $calibrationService
    );


/*
 * ============================================================
 * SCENARIO B
 * HISTORY ORCHESTRATION
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario B: History Orchestration'
);


$result =
    $service->evaluate(
        2702264,
        $weightCandidates
    );


transferPriorityHistoryCheck(
    'Gameweek repository is queried exactly once',
    $gameweekRepository->getAllCalls === 1
);


transferPriorityHistoryCheck(
    'Every valid stored gameweek is evaluated for evidence',
    count(
        $evidenceService->calls
    )
    === 3
);


transferPriorityHistoryCheck(
    'Evidence lookup preserves entry ID',
    ($evidenceService->calls[0]['entry_id'] ?? null) === 2702264
);


transferPriorityHistoryCheck(
    'Evidence lookup preserves local gameweek IDs',
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
 * SCENARIO C
 * RESULT CONTRACT
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario C: Result Contract'
);


transferPriorityHistoryCheck(
    'Result preserves entry ID',
    ($result['entry_id'] ?? null) === 2702264
);


transferPriorityHistoryCheck(
    'Result counts only valid stored gameweek rows',
    ($result['total_gameweeks'] ?? null) === 3
);


transferPriorityHistoryCheck(
    'Result counts both Ready gameweeks',
    ($result['ready_gameweeks'] ?? null) === 2
);


transferPriorityHistoryCheck(
    'Result exposes all valid gameweek audit rows',
    count(
        $result[
            'gameweeks'
        ]
        ?? []
    )
    === 3
);


transferPriorityHistoryCheck(
    'Result exposes historical outgoing calibration gameweeks',
    isset(
        $result[
            'historical_gameweeks'
        ]
    )
    &&
    is_array(
        $result[
            'historical_gameweeks'
        ]
    )
);


transferPriorityHistoryCheck(
    'Only Ready usable gameweeks enter outgoing calibration history',
    count(
        $result[
            'historical_gameweeks'
        ]
        ?? []
    )
    === 2
);


/*
 * ============================================================
 * SCENARIO D
 * GAMEWEEK AUDIT
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario D: Gameweek Audit'
);


$audit =
    $result[
        'gameweeks'
    ]
    ?? [];


transferPriorityHistoryCheck(
    'GW1 audit status is Ready',
    ($audit[0]['status'] ?? null) === 'Ready'
);


transferPriorityHistoryCheck(
    'GW2 audit status is Incomplete',
    ($audit[1]['status'] ?? null) === 'Incomplete'
);


transferPriorityHistoryCheck(
    'GW2 audit reason is preserved',
    (
        $audit[1]['reason']
        ?? null
    )
    ===
    'Recommendation snapshot is unavailable.'
);


transferPriorityHistoryCheck(
    'GW3 audit status is Ready',
    ($audit[2]['status'] ?? null) === 'Ready'
);


/*
 * ============================================================
 * SCENARIO E
 * CALIBRATION DELEGATION
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario E: Calibration Delegation'
);


transferPriorityHistoryCheck(
    'Calibration service is called exactly once',
    count(
        $calibrationService->calls
    )
    === 1
);


transferPriorityHistoryCheck(
    'Caller weight candidates are delegated unchanged',
    (
        $calibrationService->calls[0][
            'weight_candidates'
        ]
        ?? null
    )
    ===
    $weightCandidates
);


transferPriorityHistoryCheck(
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
 * SCENARIO F
 * GAMEWEEK 1 SQUAD COVERAGE
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario F: Gameweek 1 Squad Coverage'
);


$historicalGameweeks =
    $result[
        'historical_gameweeks'
    ]
    ?? [];


$history1 =
    $historicalGameweeks[
        0
    ]
    ?? [];


$history1Players =
    $history1[
        'players'
    ]
    ?? [];


transferPriorityHistoryCheck(
    'GW1 local gameweek ID is preserved',
    ($history1['gameweek_id'] ?? null) === 1
);


transferPriorityHistoryCheck(
    'GW1 outgoing calibration contains all 15 squad players',
    count(
        $history1Players
    )
    === 15
);


transferPriorityHistoryCheck(
    'Outgoing calibration is not limited to preserved top recommendation',
    count(
        $history1Players
    )
    >
    1
);


transferPriorityHistoryCheck(
    'GW1 squad identities preserve the exact 15-player squad',
    array_column(
        $history1Players,
        'player_id'
    )
    ===
    [
        101,
        102,
        103,
        104,
        105,
        106,
        107,
        108,
        109,
        110,
        111,
        112,
        113,
        114,
        115
    ]
);


/*
 * ============================================================
 * LOOKUP CALIBRATION PLAYERS
 * ============================================================
 */

$history1Lookup =
    [];


foreach (
    $history1Players
    as $player
) {

    if (!is_array($player)) {

        continue;
    }


    $playerId =
        $player[
            'player_id'
        ]
        ?? null;


    if (is_int($playerId)) {

        $history1Lookup[
            $playerId
        ] =
            $player;
    }
}


/*
 * ============================================================
 * SCENARIO G
 * OUTGOING PRIORITY EVIDENCE
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario G: Outgoing Priority Evidence'
);


$player108 =
    $history1Lookup[
        108
    ]
    ?? [];


transferPriorityHistoryCheck(
    'Outgoing Intelligence evidence is preserved',
    ($player108['intelligence_score'] ?? null) === 45.0
);


transferPriorityHistoryCheck(
    'Outgoing Value evidence is preserved',
    ($player108['value_rating'] ?? null) === 50.0
);


transferPriorityHistoryCheck(
    'Outgoing Fixture evidence is preserved',
    ($player108['fixture_rating'] ?? null) === 40.0
);


transferPriorityHistoryCheck(
    'Outgoing Availability evidence is preserved',
    ($player108['availability_rating'] ?? null) === 100.0
);


/*
 * ============================================================
 * SCENARIO H
 * FIXED HISTORICAL BANK
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario H: Recommendation-Time Bank'
);


transferPriorityHistoryCheck(
    'GW1 recommendation-time bank is preserved',
    ($history1['bank'] ?? null) === 1.0
);


$history3 =
    $historicalGameweeks[
        1
    ]
    ?? [];


transferPriorityHistoryCheck(
    'GW3 zero recommendation-time bank is preserved',
    ($history3['bank'] ?? null) === 0.0
);


/*
 * ============================================================
 * SCENARIO I
 * REALISED GAIN FOR PLAYER 108
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario I: Player 108 Realised Transfer Opportunity'
);


transferPriorityHistoryCheck(
    'Player 108 outgoing actual points are preserved',
    ($player108['actual_points'] ?? null) === 2
);


transferPriorityHistoryCheck(
    'Player 108 best legal incoming player is 206',
    ($player108['best_replacement_player_id'] ?? null) === 206
);


transferPriorityHistoryCheck(
    'Player 108 best incoming actual points are 10',
    ($player108['best_replacement_actual_points'] ?? null) === 10
);


transferPriorityHistoryCheck(
    'Player 108 best realised transfer gain is +8',
    ($player108['best_realised_transfer_gain'] ?? null) === 8
);


/*
 * ============================================================
 * SCENARIO J
 * AFFORDABILITY
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario J: Affordability'
);


transferPriorityHistoryCheck(
    'Player 108 available transfer budget includes preserved bank',
    ($player108['available_budget'] ?? null) === 8.0
);


transferPriorityHistoryCheck(
    'Player 108 legal replacement IDs exclude unaffordable players',
    !in_array(
        210,
        $player108[
            'legal_replacement_player_ids'
        ]
        ?? [],
        true
    )
);


/*
 * ============================================================
 * SCENARIO K
 * ALREADY-OWNED EXCLUSION
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario K: Already-Owned Exclusion'
);


transferPriorityHistoryCheck(
    'Already-owned player 109 never becomes a legal replacement',
    !in_array(
        109,
        $player108[
            'legal_replacement_player_ids'
        ]
        ?? [],
        true
    )
);


/*
 * ============================================================
 * SCENARIO L
 * POSITION LEGALITY
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario L: Position Legality'
);


transferPriorityHistoryCheck(
    'MID outgoing player only receives MID replacement candidates',
    !in_array(
        203,
        $player108[
            'legal_replacement_player_ids'
        ]
        ?? [],
        true
    )
);


/*
 * ============================================================
 * SCENARIO M
 * CLUB LIMIT WHEN OUTGOING IS DIFFERENT CLUB
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario M: Club Limit'
);


transferPriorityHistoryCheck(
    'Team 3 replacement 207 is illegal when outgoing 108 is Team 4',
    !in_array(
        207,
        $player108[
            'legal_replacement_player_ids'
        ]
        ?? [],
        true
    )
);


/*
 * ============================================================
 * SCENARIO N
 * CLUB LIMIT WHEN OUTGOING LEAVES SAME CLUB
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario N: Same-Club Count Reduction'
);


$player111 =
    $history1Lookup[
        111
    ]
    ?? [];


transferPriorityHistoryCheck(
    'Team 3 replacement 207 becomes legal when outgoing 111 leaves Team 3',
    in_array(
        207,
        $player111[
            'legal_replacement_player_ids'
        ]
        ?? [],
        true
    )
);


transferPriorityHistoryCheck(
    'Player 111 best legal replacement is 207',
    ($player111['best_replacement_player_id'] ?? null) === 207
);


transferPriorityHistoryCheck(
    'Player 111 best realised transfer gain is +9',
    ($player111['best_realised_transfer_gain'] ?? null) === 9
);


/*
 * ============================================================
 * SCENARIO O
 * DEFENDER SAME-CLUB COUNT REDUCTION
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario O: Defender Same-Club Count Reduction'
);


$player105 =
    $history1Lookup[
        105
    ]
    ?? [];


transferPriorityHistoryCheck(
    'Team 3 defender candidate 204 becomes legal when outgoing 105 leaves Team 3',
    in_array(
        204,
        $player105[
            'legal_replacement_player_ids'
        ]
        ?? [],
        true
    )
);


transferPriorityHistoryCheck(
    'Player 105 best realised transfer gain is +8',
    ($player105['best_realised_transfer_gain'] ?? null) === 8
);


/*
 * ============================================================
 * SCENARIO P
 * INVALID REPLACEMENT EVIDENCE
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario P: Invalid Replacement Evidence'
);


transferPriorityHistoryCheck(
    'Invalid team candidate 211 is excluded',
    !in_array(
        211,
        $player108[
            'legal_replacement_player_ids'
        ]
        ?? [],
        true
    )
);


transferPriorityHistoryCheck(
    'Invalid price candidate 212 is excluded',
    !in_array(
        212,
        $player108[
            'legal_replacement_player_ids'
        ]
        ?? [],
        true
    )
);


/*
 * ============================================================
 * SCENARIO Q
 * DUPLICATE RANKING EVIDENCE
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario Q: Duplicate Ranking Evidence'
);


transferPriorityHistoryCheck(
    'Original recommendation-time Player 109 ranking evidence is retained',
    (
        $history1Lookup[
            109
        ]['price']
        ?? null
    )
    === 8.0
);


/*
 * ============================================================
 * SCENARIO R
 * AUTHORITATIVE OUTCOME ONLY
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario R: Authoritative Outcomes'
);


transferPriorityHistoryCheck(
    'Irrelevant outcome player 999 does not enter calibration squad',
    !array_key_exists(
        999,
        $history1Lookup
    )
);


/*
 * ============================================================
 * SCENARIO S
 * GAMEWEEK 3 NEGATIVE / ZERO EVIDENCE
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario S: Negative and Zero Realised Evidence'
);


$history3Players =
    $history3[
        'players'
    ]
    ?? [];


$history3Lookup =
    [];


foreach (
    $history3Players
    as $player
) {

    if (!is_array($player)) {

        continue;
    }


    $playerId =
        $player[
            'player_id'
        ]
        ?? null;


    if (is_int($playerId)) {

        $history3Lookup[
            $playerId
        ] =
            $player;
    }
}


$player313 =
    $history3Lookup[
        313
    ]
    ?? [];


transferPriorityHistoryCheck(
    'GW3 contains all 15 outgoing candidates',
    count(
        $history3Players
    )
    === 15
);


transferPriorityHistoryCheck(
    'Negative outgoing actual points remain valid evidence',
    ($player313['actual_points'] ?? null) === -1
);


transferPriorityHistoryCheck(
    'Zero replacement actual points remain valid evidence',
    in_array(
        401,
        $player313[
            'legal_replacement_player_ids'
        ]
        ?? [],
        true
    )
);


transferPriorityHistoryCheck(
    'Player 313 best replacement is Player 402',
    ($player313['best_replacement_player_id'] ?? null) === 402
);


transferPriorityHistoryCheck(
    'Player 313 realised transfer gain is +7',
    ($player313['best_realised_transfer_gain'] ?? null) === 7
);


/*
 * ============================================================
 * SCENARIO T
 * CALIBRATION INPUT
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario T: Calibration Input'
);


$delegatedHistory =
    $calibrationService->calls[
        0
    ]['historical_gameweeks']
    ?? [];


transferPriorityHistoryCheck(
    'Calibration receives both Ready historical gameweeks',
    count(
        $delegatedHistory
    )
    === 2
);


transferPriorityHistoryCheck(
    'Calibration receives all 15 GW1 outgoing candidates',
    count(
        $delegatedHistory[
            0
        ]['players']
        ?? []
    )
    === 15
);


transferPriorityHistoryCheck(
    'Calibration receives all 15 GW3 outgoing candidates',
    count(
        $delegatedHistory[
            1
        ]['players']
        ?? []
    )
    === 15
);


/*
 * ============================================================
 * SCENARIO U
 * ENTRY VALIDATION
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario U: Entry Validation'
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


transferPriorityHistoryCheck(
    'Non-positive entry ID is rejected',
    $invalidEntryThrew
);


/*
 * ============================================================
 * SCENARIO V
 * EMPTY HISTORY
 * ============================================================
 */

transferPriorityHistorySection(
    'Scenario V: Empty History'
);


$emptyRepository =
    new TransferPriorityHistoryGameweekRepositoryStub(
        []
    );


$emptyEvidence =
    new TransferPriorityHistoryEvidenceServiceStub(
        []
    );


$emptyCalibration =
    new TransferPriorityHistoryCalibrationServiceStub(
        [
            'evaluations' =>
                []
        ]
    );


$emptyService =
    new TransferPriorityWeightCalibrationHistoryService(
        $emptyRepository,
        $emptyEvidence,
        $emptyCalibration
    );


$emptyResult =
    $emptyService->evaluate(
        2702264,
        $weightCandidates
    );


transferPriorityHistoryCheck(
    'Empty history reports zero total gameweeks',
    ($emptyResult['total_gameweeks'] ?? null) === 0
);


transferPriorityHistoryCheck(
    'Empty history reports zero Ready gameweeks',
    ($emptyResult['ready_gameweeks'] ?? null) === 0
);


transferPriorityHistoryCheck(
    'Empty history produces no audit rows',
    ($emptyResult['gameweeks'] ?? null) === []
);


transferPriorityHistoryCheck(
    'Empty history produces no outgoing calibration gameweeks',
    ($emptyResult['historical_gameweeks'] ?? null) === []
);


transferPriorityHistoryCheck(
    'Empty history still delegates calibration exactly once',
    count(
        $emptyCalibration->calls
    )
    === 1
);


transferPriorityHistoryCheck(
    'Empty history delegates an empty historical evidence set',
    (
        $emptyCalibration->calls[
            0
        ]['historical_gameweeks']
        ?? null
    )
    === []
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

transferPriorityHistorySummary();