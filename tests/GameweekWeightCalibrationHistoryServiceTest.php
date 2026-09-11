<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Weight Calibration History Service Test<br>";
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

function gameweekWeightHistoryCheck(
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


function gameweekWeightHistorySummary(): void
{
    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Gameweek Weight Calibration History Service Test Summary<br>";
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


function gameweekWeightHistoryPlayer(
    int $playerId,
    string $position,
    float $intelligence,
    float $strength,
    float $fixture,
    float $confidenceModifier = 1.0,
    float $availabilityModifier = 1.0
): array {

    return [

        'player_id' =>
            $playerId,

        'name' =>
            'Player '
            . $playerId,

        'position' =>
            $position,

        'gameweek_score' =>
            50.0,

        'gameweek_components' => [

            'intelligence' =>
                $intelligence,

            'strength' =>
                $strength,

            'raw_fixture' =>
                $fixture,

            'fixture' =>
                $fixture,

            'availability' =>
                100.0,

            'confidence' =>
                100.0,

            'core_score' =>
                50.0,

            'confidence_modifier' =>
                $confidenceModifier,

            'availability_modifier' =>
                $availabilityModifier
        ]
    ];
}


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class GameweekWeightHistoryGameweekRepositoryStub
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


class GameweekWeightHistoryEvidenceServiceStub
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


class GameweekWeightHistoryCalibrationServiceStub
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

echo "============================================<br>";
echo "Scenario A: Class Contract<br>";
echo "============================================<br>";


$classExists =
    class_exists(
        'GameweekWeightCalibrationHistoryService'
    );


gameweekWeightHistoryCheck(
    'GameweekWeightCalibrationHistoryService class exists',
    $classExists
);


gameweekWeightHistoryCheck(
    'GameweekWeightCalibrationHistoryService exposes evaluate()',
    $classExists
    &&
    method_exists(
        'GameweekWeightCalibrationHistoryService',
        'evaluate'
    )
);


echo "<br>";


/*
 * ============================================================
 * STOP UNTIL PRODUCTION CLASS EXISTS
 * ============================================================
 */

if (!$classExists) {

    gameweekWeightHistorySummary();

    exit;
}


/*
 * ============================================================
 * CONTROLLED STORED GAMEWEEKS
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

    [
        'id' =>
            4,

        'fpl_gameweek_id' =>
            4,

        'name' =>
            'Gameweek 4'
    ],

    /*
     * Malformed repository evidence.
     */
    'malformed gameweek',

    /*
     * Invalid local gameweek identity.
     */
    [
        'id' =>
            0,

        'fpl_gameweek_id' =>
            5,

        'name' =>
            'Invalid Gameweek'
    ]
];


/*
 * ============================================================
 * READY GAMEWEEK 1 — PRESERVED 15-PLAYER SQUAD
 * ============================================================
 */

$gameweek1StartingXI = [

    gameweekWeightHistoryPlayer(
        101,
        'GK',
        82.0,
        80.0,
        75.0
    ),

    gameweekWeightHistoryPlayer(
        102,
        'DEF',
        78.0,
        77.0,
        74.0
    ),

    gameweekWeightHistoryPlayer(
        103,
        'DEF',
        76.0,
        75.0,
        72.0
    ),

    gameweekWeightHistoryPlayer(
        104,
        'DEF',
        74.0,
        73.0,
        70.0
    ),

    gameweekWeightHistoryPlayer(
        105,
        'MID',
        88.0,
        84.0,
        90.0
    ),

    gameweekWeightHistoryPlayer(
        106,
        'MID',
        86.0,
        82.0,
        88.0
    ),

    gameweekWeightHistoryPlayer(
        107,
        'MID',
        80.0,
        79.0,
        82.0
    ),

    gameweekWeightHistoryPlayer(
        108,
        'MID',
        76.0,
        74.0,
        78.0
    ),

    gameweekWeightHistoryPlayer(
        109,
        'FWD',
        90.0,
        88.0,
        92.0
    ),

    gameweekWeightHistoryPlayer(
        110,
        'FWD',
        84.0,
        81.0,
        87.0
    ),

    gameweekWeightHistoryPlayer(
        111,
        'FWD',
        79.0,
        78.0,
        80.0
    )
];


$gameweek1Bench = [

    gameweekWeightHistoryPlayer(
        112,
        'GK',
        62.0,
        64.0,
        58.0,
        0.95,
        1.00
    ),

    gameweekWeightHistoryPlayer(
        113,
        'DEF',
        68.0,
        66.0,
        72.0
    ),

    gameweekWeightHistoryPlayer(
        114,
        'MID',
        72.0,
        70.0,
        76.0
    ),

    gameweekWeightHistoryPlayer(
        115,
        'FWD',
        70.0,
        68.0,
        74.0
    )
];


$gameweek1Outcomes = [];


$gameweek1ActualPoints = [

    101 => 6,
    102 => 2,
    103 => 1,
    104 => 0,
    105 => 12,
    106 => 8,
    107 => 5,
    108 => 3,
    109 => 10,
    110 => 7,
    111 => 4,
    112 => 3,
    113 => 9,
    114 => 11,
    115 => -1
];


foreach (
    $gameweek1ActualPoints
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
 * ============================================================
 * READY GAMEWEEK 3 — SECOND PRESERVED SQUAD
 * ============================================================
 */

$gameweek3StartingXI = [

    gameweekWeightHistoryPlayer(
        301,
        'GK',
        75.0,
        73.0,
        78.0
    ),

    gameweekWeightHistoryPlayer(
        302,
        'DEF',
        80.0,
        78.0,
        82.0
    ),

    gameweekWeightHistoryPlayer(
        303,
        'DEF',
        77.0,
        76.0,
        79.0
    ),

    gameweekWeightHistoryPlayer(
        304,
        'DEF',
        74.0,
        72.0,
        76.0
    ),

    gameweekWeightHistoryPlayer(
        305,
        'MID',
        90.0,
        87.0,
        93.0
    ),

    gameweekWeightHistoryPlayer(
        306,
        'MID',
        85.0,
        82.0,
        89.0
    ),

    gameweekWeightHistoryPlayer(
        307,
        'MID',
        82.0,
        80.0,
        84.0
    ),

    gameweekWeightHistoryPlayer(
        308,
        'MID',
        78.0,
        76.0,
        80.0
    ),

    gameweekWeightHistoryPlayer(
        309,
        'FWD',
        88.0,
        85.0,
        91.0
    ),

    gameweekWeightHistoryPlayer(
        310,
        'FWD',
        83.0,
        81.0,
        86.0
    ),

    gameweekWeightHistoryPlayer(
        311,
        'FWD',
        79.0,
        77.0,
        82.0
    )
];


$gameweek3Bench = [

    gameweekWeightHistoryPlayer(
        312,
        'GK',
        60.0,
        62.0,
        57.0
    ),

    gameweekWeightHistoryPlayer(
        313,
        'DEF',
        69.0,
        67.0,
        73.0
    ),

    gameweekWeightHistoryPlayer(
        314,
        'MID',
        71.0,
        69.0,
        75.0
    ),

    gameweekWeightHistoryPlayer(
        315,
        'FWD',
        68.0,
        66.0,
        72.0,
        0.90,
        0.85
    )
];


$gameweek3Outcomes = [];


$gameweek3ActualPoints = [

    301 => 0,
    302 => 6,
    303 => 5,
    304 => 2,
    305 => 14,
    306 => 7,
    307 => 4,
    308 => 1,
    309 => 9,
    310 => 3,
    311 => 2,
    312 => 8,
    313 => 10,
    314 => 0,
    315 => 6
];


foreach (
    $gameweek3ActualPoints
    as $playerId => $points
) {

    $gameweek3Outcomes[] = [

        'player_id' =>
            $playerId,

        'total_points' =>
            $points,

        'minutes' =>
            90
    ];
}


/*
 * ============================================================
 * CONTROLLED AUTHORITATIVE EVIDENCE
 * ============================================================
 */

$evidenceByGameweek = [

    /*
     * Ready and fully usable.
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
                $gameweek1Bench
        ],

        'player_outcomes' =>
            $gameweek1Outcomes
    ],

    /*
     * Authoritative outcomes exist, but immutable recommendation
     * evidence is unavailable. This gameweek must not contribute.
     */
    2 => [

        'status' =>
            'Incomplete',

        'reason' =>
            'Recommendation snapshot is unavailable',

        'recommendation_snapshot' =>
            null,

        'player_outcomes' =>
            []
    ],

    /*
     * Ready and fully usable.
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
                $gameweek3Bench
        ],

        'player_outcomes' =>
            $gameweek3Outcomes
    ],

    /*
     * Future/not authoritative.
     */
    4 => [

        'status' =>
            'Unavailable',

        'reason' =>
            'Gameweek outcomes are not yet authoritative',

        'recommendation_snapshot' =>
            null,

        'player_outcomes' =>
            []
    ]
];


/*
 * ============================================================
 * CONTROLLED WEIGHT CANDIDATES
 * ============================================================
 */

$weightCandidates = [

    [
        'intelligence_weight' =>
            0.45,

        'strength_weight' =>
            0.25,

        'fixture_weight' =>
            0.30
    ],

    [
        'intelligence_weight' =>
            0.40,

        'strength_weight' =>
            0.30,

        'fixture_weight' =>
            0.30
    ]
];


$calibrationReturnValue = [

    'evaluations' => [

        [
            'candidate' =>
                $weightCandidates[0],

            'metrics' => [

                'total_gameweeks' =>
                    2
            ]
        ]
    ],

    'metrics' => [

        'controlled_result' =>
            true
    ]
];


/*
 * ============================================================
 * SERVICE
 * ============================================================
 */

$gameweekRepository =
    new GameweekWeightHistoryGameweekRepositoryStub(
        $storedGameweeks
    );


$evidenceService =
    new GameweekWeightHistoryEvidenceServiceStub(
        $evidenceByGameweek
    );


$calibrationService =
    new GameweekWeightHistoryCalibrationServiceStub(
        $calibrationReturnValue
    );


$service =
    new GameweekWeightCalibrationHistoryService(
        $gameweekRepository,
        $evidenceService,
        $calibrationService
    );


/*
 * ============================================================
 * SCENARIO B
 * INVALID ENTRY ID
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Invalid Entry ID<br>";
echo "============================================<br>";


$invalidEntryRejected =
    false;


try {

    $service->evaluate(
        0,
        $weightCandidates
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidEntryRejected =
        true;
}


gameweekWeightHistoryCheck(
    'Non-positive entry ID is rejected',
    $invalidEntryRejected
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * BUILD HISTORICAL CALIBRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Historical Calibration<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        2702264,
        $weightCandidates
    );


gameweekWeightHistoryCheck(
    'History result preserves entry ID',
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    ===
    2702264
);


gameweekWeightHistoryCheck(
    'Only valid positive stored gameweek rows count toward total gameweeks',
    (
        $result[
            'total_gameweeks'
        ]
        ?? null
    )
    ===
    4
);


gameweekWeightHistoryCheck(
    'Two authoritative Ready gameweeks are counted',
    (
        $result[
            'ready_gameweeks'
        ]
        ?? null
    )
    ===
    2
);


gameweekWeightHistoryCheck(
    'Every valid stored gameweek remains visible in audit',
    count(
        $result[
            'gameweeks'
        ]
        ??
        []
    )
    ===
    4
);


gameweekWeightHistoryCheck(
    'Only Ready usable gameweeks contribute calibration history',
    count(
        $result[
            'historical_gameweeks'
        ]
        ??
        []
    )
    ===
    2
);


/*
 * ============================================================
 * SCENARIO D
 * AUTHORITATIVE GAMEWEEK AUDIT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Authoritative Gameweek Audit<br>";
echo "============================================<br>";


$gameweekAudit =
    $result[
        'gameweeks'
    ]
    ??
    [];


gameweekWeightHistoryCheck(
    'Gameweek 1 audit remains Ready',
    (
        $gameweekAudit[0][
            'status'
        ]
        ?? null
    )
    ===
    'Ready'
);


gameweekWeightHistoryCheck(
    'Gameweek 2 audit remains Incomplete',
    (
        $gameweekAudit[1][
            'status'
        ]
        ?? null
    )
    ===
    'Incomplete'
);


gameweekWeightHistoryCheck(
    'Gameweek 2 audit preserves authoritative reason',
    (
        $gameweekAudit[1][
            'reason'
        ]
        ?? null
    )
    ===
    'Recommendation snapshot is unavailable'
);


gameweekWeightHistoryCheck(
    'Gameweek 4 audit remains Unavailable',
    (
        $gameweekAudit[3][
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * ============================================================
 * SCENARIO E
 * READY GAMEWEEK PRESERVES FULL 15-PLAYER UNIVERSE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Full Preserved Squad Universe<br>";
echo "============================================<br>";


$historicalGameweeks =
    $result[
        'historical_gameweeks'
    ]
    ??
    [];


$historyGameweek1 =
    $historicalGameweeks[0]
    ??
    [];


$historyGameweek3 =
    $historicalGameweeks[1]
    ??
    [];


gameweekWeightHistoryCheck(
    'First historical calibration row is Gameweek 1',
    (
        $historyGameweek1[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    1
);


gameweekWeightHistoryCheck(
    'Gameweek 1 contains exactly 15 preserved players',
    count(
        $historyGameweek1[
            'players'
        ]
        ??
        []
    )
    ===
    15
);


gameweekWeightHistoryCheck(
    'Second historical calibration row is Gameweek 3',
    (
        $historyGameweek3[
            'gameweek_id'
        ]
        ?? null
    )
    ===
    3
);


gameweekWeightHistoryCheck(
    'Gameweek 3 contains exactly 15 preserved players',
    count(
        $historyGameweek3[
            'players'
        ]
        ??
        []
    )
    ===
    15
);


/*
 * ============================================================
 * SCENARIO F
 * STARTING XI + BENCH IDENTITIES ARE PRESERVED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Starting XI and Bench Preservation<br>";
echo "============================================<br>";


$gameweek1Players =
    $historyGameweek1[
        'players'
    ]
    ??
    [];


$gameweek1PlayerIds =
    array_map(
        static fn (
            array $player
        ): int =>
            (int) (
                $player[
                    'player_id'
                ]
                ??
                0
            ),
        $gameweek1Players
    );


gameweekWeightHistoryCheck(
    'Gameweek 1 preserves all Starting XI player identities',
    array_slice(
        $gameweek1PlayerIds,
        0,
        11
    )
    ===
    range(
        101,
        111
    )
);


gameweekWeightHistoryCheck(
    'Gameweek 1 preserves all four bench player identities',
    array_slice(
        $gameweek1PlayerIds,
        11,
        4
    )
    ===
    range(
        112,
        115
    )
);


/*
 * ============================================================
 * SCENARIO G
 * GAMEWEEK COMPONENTS PRESERVED FOR REPLAY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Gameweek Component Evidence<br>";
echo "============================================<br>";


$player105 =
    null;


$player115 =
    null;


foreach (
    $gameweek1Players
    as $player
) {

    if (
        (
            $player[
                'player_id'
            ]
            ??
            null
        )
        ===
        105
    ) {

        $player105 =
            $player;
    }


    if (
        (
            $player[
                'player_id'
            ]
            ??
            null
        )
        ===
        115
    ) {

        $player115 =
            $player;
    }
}


gameweekWeightHistoryCheck(
    'Starting XI Intelligence component is preserved',
    (
        $player105[
            'components'
        ][
            'intelligence'
        ]
        ?? null
    )
    ===
    88.0
);


gameweekWeightHistoryCheck(
    'Starting XI Strength component is preserved',
    (
        $player105[
            'components'
        ][
            'strength'
        ]
        ?? null
    )
    ===
    84.0
);


gameweekWeightHistoryCheck(
    'Starting XI Fixture component uses preserved compressed fixture evidence',
    (
        $player105[
            'components'
        ][
            'fixture'
        ]
        ?? null
    )
    ===
    90.0
);


gameweekWeightHistoryCheck(
    'Bench confidence modifier is preserved',
    (
        $player115[
            'components'
        ][
            'confidence_modifier'
        ]
        ?? null
    )
    ===
    1.0
);


gameweekWeightHistoryCheck(
    'Bench availability modifier is preserved',
    (
        $player115[
            'components'
        ][
            'availability_modifier'
        ]
        ?? null
    )
    ===
    1.0
);


/*
 * ============================================================
 * SCENARIO H
 * AUTHORITATIVE OUTCOME JOIN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Authoritative Outcome Join<br>";
echo "============================================<br>";


gameweekWeightHistoryCheck(
    'Authoritative realised points are joined by local player ID',
    (
        $player105[
            'actual_points'
        ]
        ?? null
    )
    ===
    12
);


gameweekWeightHistoryCheck(
    'Genuine negative realised FPL points are preserved',
    array_key_exists(
        'actual_points',
        $player115
        ??
        []
    )
    &&
    $player115[
        'actual_points'
    ]
    ===
    -1
);


$gameweek3Players =
    $historyGameweek3[
        'players'
    ]
    ??
    [];


$player301 =
    null;


foreach (
    $gameweek3Players
    as $player
) {

    if (
        (
            $player[
                'player_id'
            ]
            ??
            null
        )
        ===
        301
    ) {

        $player301 =
            $player;

        break;
    }
}


gameweekWeightHistoryCheck(
    'Genuine zero realised FPL points are preserved',
    is_array(
        $player301
    )
    &&
    array_key_exists(
        'actual_points',
        $player301
    )
    &&
    $player301[
        'actual_points'
    ]
    ===
    0
);


/*
 * ============================================================
 * SCENARIO I
 * CALIBRATOR DELEGATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Calibration Delegation<br>";
echo "============================================<br>";


gameweekWeightHistoryCheck(
    'Gameweek repository is queried exactly once',
    $gameweekRepository
        ->getAllCalls
    ===
    1
);


gameweekWeightHistoryCheck(
    'Evidence service is called only for four valid stored gameweeks',
    count(
        $evidenceService
            ->calls
    )
    ===
    4
);


gameweekWeightHistoryCheck(
    'Evidence service receives requested entry ID for every gameweek',
    array_column(
        $evidenceService
            ->calls,
        'entry_id'
    )
    ===
    [
        2702264,
        2702264,
        2702264,
        2702264
    ]
);


gameweekWeightHistoryCheck(
    'Evidence service receives valid local gameweek IDs in repository order',
    array_column(
        $evidenceService
            ->calls,
        'gameweek_id'
    )
    ===
    [
        1,
        2,
        3,
        4
    ]
);


gameweekWeightHistoryCheck(
    'Pure Gameweek calibrator is called exactly once',
    count(
        $calibrationService
            ->calls
    )
    ===
    1
);


gameweekWeightHistoryCheck(
    'Pure calibrator receives the complete pooled Ready history',
    (
        $calibrationService
            ->calls[0][
                'historical_gameweeks'
            ]
        ??
        null
    )
    ===
    $historicalGameweeks
);


gameweekWeightHistoryCheck(
    'Weight candidates are forwarded unchanged to pure calibrator',
    (
        $calibrationService
            ->calls[0][
                'weight_candidates'
            ]
        ??
        null
    )
    ===
    $weightCandidates
);


gameweekWeightHistoryCheck(
    'Calibration result is returned unchanged',
    (
        $result[
            'calibration'
        ]
        ??
        null
    )
    ===
    $calibrationReturnValue
);


/*
 * ============================================================
 * SCENARIO J
 * NO STORED GAMEWEEKS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: No Stored Gameweeks<br>";
echo "============================================<br>";


$emptyRepository =
    new GameweekWeightHistoryGameweekRepositoryStub(
        []
    );


$emptyEvidenceService =
    new GameweekWeightHistoryEvidenceServiceStub(
        []
    );


$emptyCalibrationService =
    new GameweekWeightHistoryCalibrationServiceStub(
        [
            'evaluations' =>
                [],

            'metrics' => [

                'total_gameweeks' =>
                    0,

                'comparable_gameweeks' =>
                    0,

                'unavailable_gameweeks' =>
                    0
            ]
        ]
    );


$emptyService =
    new GameweekWeightCalibrationHistoryService(
        $emptyRepository,
        $emptyEvidenceService,
        $emptyCalibrationService
    );


$emptyResult =
    $emptyService->evaluate(
        2702264,
        $weightCandidates
    );


gameweekWeightHistoryCheck(
    'Empty repository produces zero stored gameweeks',
    (
        $emptyResult[
            'total_gameweeks'
        ]
        ?? null
    )
    ===
    0
);


gameweekWeightHistoryCheck(
    'Empty repository produces zero Ready gameweeks',
    (
        $emptyResult[
            'ready_gameweeks'
        ]
        ?? null
    )
    ===
    0
);


gameweekWeightHistoryCheck(
    'Empty repository produces empty audit',
    (
        $emptyResult[
            'gameweeks'
        ]
        ?? null
    )
    ===
    []
);


gameweekWeightHistoryCheck(
    'Empty repository produces empty historical calibration history',
    (
        $emptyResult[
            'historical_gameweeks'
        ]
        ?? null
    )
    ===
    []
);


gameweekWeightHistoryCheck(
    'Pure calibrator still receives the empty historical collection exactly once',
    count(
        $emptyCalibrationService
            ->calls
    )
    ===
    1
    &&
    (
        $emptyCalibrationService
            ->calls[0][
                'historical_gameweeks'
            ]
        ??
        null
    )
    ===
    []
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

gameweekWeightHistorySummary();