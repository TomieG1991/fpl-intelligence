<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Captain Weight Calibration History Service Test<br>";
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

function captainWeightHistoryCheck(
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


function captainWeightHistorySummary(): void
{
    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Captain Weight Calibration History Service Test Summary<br>";
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
 * TEST DOUBLES
 * ============================================================
 */

class CaptainWeightHistoryGameweekRepositoryStub
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


class CaptainWeightHistoryEvidenceServiceStub
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


class CaptainWeightHistoryCalibrationServiceStub
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
        'CaptainWeightCalibrationHistoryService'
    );


captainWeightHistoryCheck(
    'CaptainWeightCalibrationHistoryService class exists',
    $classExists
);


captainWeightHistoryCheck(
    'CaptainWeightCalibrationHistoryService exposes evaluate()',
    $classExists
    &&
    method_exists(
        'CaptainWeightCalibrationHistoryService',
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

    captainWeightHistorySummary();

    exit;
}


/*
 * ============================================================
 * CONTROLLED STORED GAMEWEEKS
 * ============================================================
 */

$gameweeks = [

    [
        'id' => 1,
        'fpl_gameweek_id' => 1,
        'name' => 'Gameweek 1'
    ],

    [
        'id' => 2,
        'fpl_gameweek_id' => 2,
        'name' => 'Gameweek 2'
    ],

    [
        'id' => 3,
        'fpl_gameweek_id' => 3,
        'name' => 'Gameweek 3'
    ],

    [
        'id' => 4,
        'fpl_gameweek_id' => 4,
        'name' => 'Gameweek 4'
    ],

    [
        'id' => 5,
        'fpl_gameweek_id' => 5,
        'name' => 'Gameweek 5'
    ],

    /*
     * Malformed repository evidence.
     */
    'malformed gameweek',

    /*
     * Invalid local gameweek identity.
     */
    [
        'id' => 0,
        'fpl_gameweek_id' => 6,
        'name' => 'Invalid Gameweek'
    ]
];


/*
 * ============================================================
 * CONTROLLED CAPTAIN RANKINGS
 * ============================================================
 */

$gameweek1CaptainRankings = [

    [
        'status' =>
            'success',

        'player_id' =>
            101,

        'name' =>
            'Player 101',

        'components' => [

            'strength' =>
                90.0,

            'fixture' =>
                60.0,

            'attacking_threat' =>
                85.0,

            'confidence_modifier' =>
                1.00,

            'availability_modifier' =>
                1.00
        ]
    ],

    [
        'status' =>
            'success',

        'player_id' =>
            102,

        'name' =>
            'Player 102',

        'components' => [

            'strength' =>
                70.0,

            'fixture' =>
                95.0,

            'attacking_threat' =>
                60.0,

            'confidence_modifier' =>
                1.00,

            'availability_modifier' =>
                1.00
        ]
    ]
];


$gameweek3CaptainRankings = [

    [
        'status' =>
            'success',

        'player_id' =>
            301,

        'name' =>
            'Player 301',

        'components' => [

            'strength' =>
                80.0,

            'fixture' =>
                75.0,

            'attacking_threat' =>
                85.0,

            'confidence_modifier' =>
                0.96,

            'availability_modifier' =>
                1.00
        ]
    ],

    [
        'status' =>
            'success',

        'player_id' =>
            302,

        'name' =>
            'Player 302',

        'components' => [

            'strength' =>
                85.0,

            'fixture' =>
                80.0,

            'attacking_threat' =>
                70.0,

            'confidence_modifier' =>
                1.00,

            'availability_modifier' =>
                0.90
        ]
    ]
];


/*
 * ============================================================
 * CONTROLLED AUTHORITATIVE PLAYER OUTCOMES
 * ============================================================
 */

$gameweek1Outcomes = [

    [
        'player_id' =>
            101,

        'total_points' =>
            12,

        'minutes' =>
            90
    ],

    [
        'player_id' =>
            102,

        'total_points' =>
            6,

        'minutes' =>
            90
    ]
];


$gameweek3Outcomes = [

    [
        'player_id' =>
            301,

        'total_points' =>
            0,

        'minutes' =>
            90
    ],

    [
        'player_id' =>
            302,

        'total_points' =>
            -1,

        'minutes' =>
            90
    ],

    /*
     * Outcome for a player who was NOT in the preserved
     * Captain Intelligence universe.
     *
     * This must not be invented into Captain calibration history.
     */
    [
        'player_id' =>
            399,

        'total_points' =>
            20,

        'minutes' =>
            90
    ]
];


/*
 * ============================================================
 * CONTROLLED BACKTESTING EVIDENCE
 * ============================================================
 *
 * GW1 = Ready and complete.
 * GW2 = Incomplete.
 * GW3 = Ready and complete.
 * GW4 = Unavailable.
 * GW5 = Ready at the Gameweek level, but Captain evidence is
 *       structurally unavailable for calibration.
 */

$evidenceByGameweek = [

    1 => [

        'status' =>
            'Ready',

        'reason' =>
            null,

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            1,

        'recommendation_snapshot' => [

            'captain_recommendation' => [

                'status' =>
                    'success',

                'captain' =>
                    $gameweek1CaptainRankings[0],

                'vice_captain' =>
                    $gameweek1CaptainRankings[1],

                'rankings' =>
                    $gameweek1CaptainRankings
            ]
        ],

        'player_outcomes' =>
            $gameweek1Outcomes
    ],


    2 => [

        'status' =>
            'Incomplete',

        'reason' =>
            'Recommendation snapshot is unavailable',

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            2
    ],


    3 => [

        'status' =>
            'Ready',

        'reason' =>
            null,

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            3,

        'recommendation_snapshot' => [

            'captain_recommendation' => [

                'status' =>
                    'success',

                'captain' =>
                    $gameweek3CaptainRankings[0],

                'vice_captain' =>
                    $gameweek3CaptainRankings[1],

                'rankings' =>
                    $gameweek3CaptainRankings
            ]
        ],

        'player_outcomes' =>
            $gameweek3Outcomes
    ],


    4 => [

        'status' =>
            'Unavailable',

        'reason' =>
            'Gameweek outcomes are not yet authoritative',

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            4
    ],


    5 => [

        'status' =>
            'Ready',

        'reason' =>
            null,

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            5,

        'recommendation_snapshot' => [

            'captain_recommendation' => [

                'status' =>
                    'success',

                'captain' => [
                    'player_id' => 501
                ],

                /*
                 * Rankings deliberately unavailable.
                 */
                'rankings' =>
                    []
            ]
        ],

        'player_outcomes' => [

            [
                'player_id' =>
                    501,

                'total_points' =>
                    10,

                'minutes' =>
                    90
            ]
        ]
    ]
];


/*
 * ============================================================
 * WEIGHT CANDIDATES
 * ============================================================
 */

$weightCandidates = [

    [
        'strength_weight' =>
            0.35,

        'fixture_weight' =>
            0.35,

        'attacking_threat_weight' =>
            0.30
    ],

    [
        'strength_weight' =>
            0.10,

        'fixture_weight' =>
            0.80,

        'attacking_threat_weight' =>
            0.10
    ]
];


/*
 * ============================================================
 * CONTROLLED CALIBRATION RESULT
 * ============================================================
 */

$expectedCalibration = [

    'evaluations' => [

        [
            'strength_weight' =>
                0.35,

            'fixture_weight' =>
                0.35,

            'attacking_threat_weight' =>
                0.30,

            'gameweeks' =>
                [],

            'metrics' => [

                'total_gameweeks' =>
                    2,

                'comparable_gameweeks' =>
                    2,

                'unavailable_gameweeks' =>
                    0,

                'total_captain_points_lost' =>
                    0,

                'mean_captain_points_lost' =>
                    0.0,

                'optimal_captain_selections' =>
                    2
            ]
        ]
    ]
];


/*
 * ============================================================
 * BUILD CONTROLLED SERVICE
 * ============================================================
 */

$gameweekRepository =
    new CaptainWeightHistoryGameweekRepositoryStub(
        $gameweeks
    );


$evidenceService =
    new CaptainWeightHistoryEvidenceServiceStub(
        $evidenceByGameweek
    );


$calibrationService =
    new CaptainWeightHistoryCalibrationServiceStub(
        $expectedCalibration
    );


$service =
    new CaptainWeightCalibrationHistoryService(
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


$invalidEntryThrown =
    false;


try {

    $service->evaluate(
        0,
        $weightCandidates
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidEntryThrown =
        true;
}


captainWeightHistoryCheck(
    'Non-positive entry ID is rejected',
    $invalidEntryThrown
);


echo "<br>";


/*
 * ============================================================
 * RUN CONTROLLED HISTORY
 * ============================================================
 */

$result =
    $service->evaluate(
        2702264,
        $weightCandidates
    );


/*
 * ============================================================
 * SCENARIO C
 * GAMEWEEK DISCOVERY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Gameweek Discovery<br>";
echo "============================================<br>";


captainWeightHistoryCheck(
    'Stored gameweeks are discovered exactly once',
    $gameweekRepository->getAllCalls
    ===
    1
);


captainWeightHistoryCheck(
    'Only valid positive local gameweeks are counted',
    (
        $result[
            'total_gameweeks'
        ]
        ??
        null
    )
    ===
    5
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * AUTHORITATIVE ELIGIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Historical Eligibility<br>";
echo "============================================<br>";


captainWeightHistoryCheck(
    'Historical eligibility is checked for every valid stored gameweek',
    $evidenceService->calls
    ===
    [

        [
            'entry_id' =>
                2702264,

            'gameweek_id' =>
                1
        ],

        [
            'entry_id' =>
                2702264,

            'gameweek_id' =>
                2
        ],

        [
            'entry_id' =>
                2702264,

            'gameweek_id' =>
                3
        ],

        [
            'entry_id' =>
                2702264,

            'gameweek_id' =>
                4
        ],

        [
            'entry_id' =>
                2702264,

            'gameweek_id' =>
                5
        ]
    ]
);


captainWeightHistoryCheck(
    'Gameweek-level Ready evidence is counted independently of Captain calibration completeness',
    (
        $result[
            'ready_gameweeks'
        ]
        ??
        null
    )
    ===
    3
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * GAMEWEEK AUDIT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Gameweek Audit<br>";
echo "============================================<br>";


$gameweekAudit =
    $result[
        'gameweeks'
    ]
    ??
    [];


captainWeightHistoryCheck(
    'Every valid stored gameweek remains visible in the audit',
    count(
        $gameweekAudit
    )
    ===
    5
);


captainWeightHistoryCheck(
    'Gameweek audit preserves local gameweek identity',
    array_column(
        $gameweekAudit,
        'gameweek_id'
    )
    ===
    [
        1,
        2,
        3,
        4,
        5
    ]
);


captainWeightHistoryCheck(
    'Gameweek audit preserves official FPL gameweek identity',
    array_column(
        $gameweekAudit,
        'fpl_gameweek_id'
    )
    ===
    [
        1,
        2,
        3,
        4,
        5
    ]
);


captainWeightHistoryCheck(
    'Gameweek audit preserves gameweek names',
    array_column(
        $gameweekAudit,
        'name'
    )
    ===
    [
        'Gameweek 1',
        'Gameweek 2',
        'Gameweek 3',
        'Gameweek 4',
        'Gameweek 5'
    ]
);


captainWeightHistoryCheck(
    'Gameweek audit preserves authoritative eligibility statuses',
    array_column(
        $gameweekAudit,
        'status'
    )
    ===
    [
        'Ready',
        'Incomplete',
        'Ready',
        'Unavailable',
        'Ready'
    ]
);


captainWeightHistoryCheck(
    'Non-Ready audit reasons are preserved',
    (
        $gameweekAudit[
            1
        ][
            'reason'
        ]
        ??
        null
    )
    ===
    'Recommendation snapshot is unavailable'
    &&
    (
        $gameweekAudit[
            3
        ][
            'reason'
        ]
        ??
        null
    )
    ===
    'Gameweek outcomes are not yet authoritative'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * CAPTAIN HISTORY EXTRACTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Captain History Extraction<br>";
echo "============================================<br>";


$historicalGameweeks =
    $result[
        'historical_gameweeks'
    ]
    ??
    [];


captainWeightHistoryCheck(
    'Only Ready gameweeks with usable Captain ranking evidence become calibration gameweeks',
    count(
        $historicalGameweeks
    )
    ===
    2
);


captainWeightHistoryCheck(
    'Calibration history preserves Ready local gameweek identities',
    array_column(
        $historicalGameweeks,
        'gameweek_id'
    )
    ===
    [
        1,
        3
    ]
);


captainWeightHistoryCheck(
    'Gameweek 1 preserves exactly the Captain Intelligence candidate universe',
    count(
        $historicalGameweeks[
            0
        ][
            'players'
        ]
        ??
        []
    )
    ===
    2
);


captainWeightHistoryCheck(
    'Gameweek 3 preserves exactly the Captain Intelligence candidate universe',
    count(
        $historicalGameweeks[
            1
        ][
            'players'
        ]
        ??
        []
    )
    ===
    2
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * PRESERVED CAPTAIN COMPONENTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Preserved Captain Components<br>";
echo "============================================<br>";


$gw1Player101 =
    $historicalGameweeks[
        0
    ][
        'players'
    ][
        0
    ]
    ??
    [];


captainWeightHistoryCheck(
    'Player identity is preserved',
    (
        $gw1Player101[
            'player_id'
        ]
        ??
        null
    )
    ===
    101
);


captainWeightHistoryCheck(
    'Preserved Captain Strength component is retained',
    (
        $gw1Player101[
            'components'
        ][
            'strength'
        ]
        ??
        null
    )
    ===
    90.0
);


captainWeightHistoryCheck(
    'Preserved Captain Fixture component is retained',
    (
        $gw1Player101[
            'components'
        ][
            'fixture'
        ]
        ??
        null
    )
    ===
    60.0
);


captainWeightHistoryCheck(
    'Preserved Captain Attacking Threat component is retained',
    (
        $gw1Player101[
            'components'
        ][
            'attacking_threat'
        ]
        ??
        null
    )
    ===
    85.0
);


captainWeightHistoryCheck(
    'Preserved confidence modifier is retained',
    (
        $gw1Player101[
            'components'
        ][
            'confidence_modifier'
        ]
        ??
        null
    )
    ===
    1.00
);


captainWeightHistoryCheck(
    'Preserved availability modifier is retained',
    (
        $gw1Player101[
            'components'
        ][
            'availability_modifier'
        ]
        ??
        null
    )
    ===
    1.00
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * AUTHORITATIVE OUTCOME MAPPING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Authoritative Outcome Mapping<br>";
echo "============================================<br>";


captainWeightHistoryCheck(
    'Authoritative realised points are mapped onto the matching preserved Captain candidate',
    (
        $historicalGameweeks[
            0
        ][
            'players'
        ][
            0
        ][
            'actual_points'
        ]
        ??
        null
    )
    ===
    12
);


captainWeightHistoryCheck(
    'Second Gameweek 1 Captain candidate receives its own authoritative realised points',
    (
        $historicalGameweeks[
            0
        ][
            'players'
        ][
            1
        ][
            'actual_points'
        ]
        ??
        null
    )
    ===
    6
);


captainWeightHistoryCheck(
    'Zero realised Captain candidate points remain valid',
    array_key_exists(
        'actual_points',
        $historicalGameweeks[
            1
        ][
            'players'
        ][
            0
        ]
    )
    &&
    $historicalGameweeks[
        1
    ][
        'players'
    ][
        0
    ][
        'actual_points'
    ]
    ===
    0
);


captainWeightHistoryCheck(
    'Negative realised Captain candidate points remain valid',
    (
        $historicalGameweeks[
            1
        ][
            'players'
        ][
            1
        ][
            'actual_points'
        ]
        ??
        null
    )
    ===
    -1
);


captainWeightHistoryCheck(
    'Outcome players outside the preserved Captain Intelligence universe are not added to calibration history',
    !in_array(
        399,
        array_column(
            $historicalGameweeks[
                1
            ][
                'players'
            ],
            'player_id'
        ),
        true
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * STRUCTURALLY INCOMPLETE READY CAPTAIN EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Structurally Incomplete Ready Captain Evidence<br>";
echo "============================================<br>";


captainWeightHistoryCheck(
    'Ready gameweek without preserved Captain rankings is excluded from calibration history',
    !in_array(
        5,
        array_column(
            $historicalGameweeks,
            'gameweek_id'
        ),
        true
    )
);


captainWeightHistoryCheck(
    'Ready gameweek without usable Captain rankings remains visible as Ready in the gameweek audit',
    (
        $gameweekAudit[
            4
        ][
            'status'
        ]
        ??
        null
    )
    ===
    'Ready'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * SINGLE CALIBRATION DELEGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Calibration Delegation<br>";
echo "============================================<br>";


captainWeightHistoryCheck(
    'Captain calibrator is invoked exactly once across pooled historical gameweeks',
    count(
        $calibrationService->calls
    )
    ===
    1
);


captainWeightHistoryCheck(
    'Captain calibrator receives the complete extracted historical gameweek collection',
    (
        $calibrationService->calls[
            0
        ][
            'historical_gameweeks'
        ]
        ??
        null
    )
    ===
    $historicalGameweeks
);


captainWeightHistoryCheck(
    'Captain calibrator receives supplied weight candidates unchanged',
    (
        $calibrationService->calls[
            0
        ][
            'weight_candidates'
        ]
        ??
        null
    )
    ===
    $weightCandidates
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * RESULT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Result Contract<br>";
echo "============================================<br>";


captainWeightHistoryCheck(
    'Result preserves entry identity',
    (
        $result[
            'entry_id'
        ]
        ??
        null
    )
    ===
    2702264
);


captainWeightHistoryCheck(
    'Result exposes total stored gameweeks',
    (
        $result[
            'total_gameweeks'
        ]
        ??
        null
    )
    ===
    5
);


captainWeightHistoryCheck(
    'Result exposes Ready gameweek count',
    (
        $result[
            'ready_gameweeks'
        ]
        ??
        null
    )
    ===
    3
);


captainWeightHistoryCheck(
    'Result exposes extracted Captain calibration gameweeks',
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


captainWeightHistoryCheck(
    'Result exposes gameweek eligibility audit',
    isset(
        $result[
            'gameweeks'
        ]
    )
    &&
    is_array(
        $result[
            'gameweeks'
        ]
    )
);


captainWeightHistoryCheck(
    'Result exposes Captain calibration result unchanged',
    (
        $result[
            'calibration'
        ]
        ??
        null
    )
    ===
    $expectedCalibration
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * READY GAMEWEEK WITH MISSING PLAYER OUTCOME
 * ============================================================
 *
 * The history layer must not reconstruct missing realised
 * outcomes.
 *
 * Instead it preserves the Captain candidate with
 * actual_points = null.
 *
 * CaptainWeightCalibrationService then owns the decision that
 * the gameweek cannot support a fair realised comparison.
 */

echo "============================================<br>";
echo "Scenario L: Missing Player Outcome<br>";
echo "============================================<br>";


$missingOutcomeRepository =
    new CaptainWeightHistoryGameweekRepositoryStub(
        [
            [
                'id' =>
                    10,

                'fpl_gameweek_id' =>
                    10,

                'name' =>
                    'Gameweek 10'
            ]
        ]
    );


$missingOutcomeEvidenceService =
    new CaptainWeightHistoryEvidenceServiceStub(
        [

            10 => [

                'status' =>
                    'Ready',

                'reason' =>
                    null,

                'recommendation_snapshot' => [

                    'captain_recommendation' => [

                        'rankings' => [

                            [
                                'player_id' =>
                                    1001,

                                'components' => [

                                    'strength' =>
                                        80.0,

                                    'fixture' =>
                                        80.0,

                                    'attacking_threat' =>
                                        80.0,

                                    'confidence_modifier' =>
                                        1.0,

                                    'availability_modifier' =>
                                        1.0
                                ]
                            ],

                            [
                                'player_id' =>
                                    1002,

                                'components' => [

                                    'strength' =>
                                        75.0,

                                    'fixture' =>
                                        75.0,

                                    'attacking_threat' =>
                                        75.0,

                                    'confidence_modifier' =>
                                        1.0,

                                    'availability_modifier' =>
                                        1.0
                                ]
                            ]
                        ]
                    ]
                ],

                'player_outcomes' => [

                    [
                        'player_id' =>
                            1001,

                        'total_points' =>
                            8
                    ]

                    /*
                     * Player 1002 deliberately absent.
                     */
                ]
            ]
        ]
    );


$missingOutcomeCalibrationService =
    new CaptainWeightHistoryCalibrationServiceStub(
        [
            'evaluations' =>
                []
        ]
    );


$missingOutcomeService =
    new CaptainWeightCalibrationHistoryService(
        $missingOutcomeRepository,
        $missingOutcomeEvidenceService,
        $missingOutcomeCalibrationService
    );


$missingOutcomeResult =
    $missingOutcomeService->evaluate(
        2702264,
        $weightCandidates
    );


$missingOutcomeHistory =
    $missingOutcomeResult[
        'historical_gameweeks'
    ]
    ??
    [];


captainWeightHistoryCheck(
    'Ready Captain history is retained when one realised candidate outcome is unavailable',
    count(
        $missingOutcomeHistory
    )
    ===
    1
);


captainWeightHistoryCheck(
    'Known realised Captain candidate outcome remains preserved',
    (
        $missingOutcomeHistory[
            0
        ][
            'players'
        ][
            0
        ][
            'actual_points'
        ]
        ??
        null
    )
    ===
    8
);


captainWeightHistoryCheck(
    'Missing realised Captain candidate outcome remains explicitly null',
    array_key_exists(
        'actual_points',
        $missingOutcomeHistory[
            0
        ][
            'players'
        ][
            1
        ]
    )
    &&
    $missingOutcomeHistory[
        0
    ][
        'players'
    ][
        1
    ][
        'actual_points'
    ]
    ===
    null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO M
 * MALFORMED CAPTAIN RANKING ROW
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario M: Malformed Captain Ranking Row<br>";
echo "============================================<br>";


$malformedRankingRepository =
    new CaptainWeightHistoryGameweekRepositoryStub(
        [
            [
                'id' =>
                    11,

                'fpl_gameweek_id' =>
                    11,

                'name' =>
                    'Gameweek 11'
            ]
        ]
    );


$malformedRankingEvidenceService =
    new CaptainWeightHistoryEvidenceServiceStub(
        [

            11 => [

                'status' =>
                    'Ready',

                'reason' =>
                    null,

                'recommendation_snapshot' => [

                    'captain_recommendation' => [

                        'rankings' => [

                            'malformed ranking',

                            [
                                'player_id' =>
                                    1101,

                                'components' => [

                                    'strength' =>
                                        80.0,

                                    'fixture' =>
                                        80.0,

                                    'attacking_threat' =>
                                        80.0,

                                    'confidence_modifier' =>
                                        1.0,

                                    'availability_modifier' =>
                                        1.0
                                ]
                            ]
                        ]
                    ]
                ],

                'player_outcomes' => [

                    [
                        'player_id' =>
                            1101,

                        'total_points' =>
                            7
                    ]
                ]
            ]
        ]
    );


$malformedRankingCalibrationService =
    new CaptainWeightHistoryCalibrationServiceStub(
        [
            'evaluations' =>
                []
        ]
    );


$malformedRankingService =
    new CaptainWeightCalibrationHistoryService(
        $malformedRankingRepository,
        $malformedRankingEvidenceService,
        $malformedRankingCalibrationService
    );


$malformedRankingResult =
    $malformedRankingService->evaluate(
        2702264,
        $weightCandidates
    );


$malformedRankingHistory =
    $malformedRankingResult[
        'historical_gameweeks'
    ]
    ??
    [];


captainWeightHistoryCheck(
    'Malformed non-array Captain ranking rows are ignored',
    count(
        $malformedRankingHistory[
            0
        ][
            'players'
        ]
        ??
        []
    )
    ===
    1
);


captainWeightHistoryCheck(
    'Valid Captain ranking survives alongside malformed ranking evidence',
    (
        $malformedRankingHistory[
            0
        ][
            'players'
        ][
            0
        ][
            'player_id'
        ]
        ??
        null
    )
    ===
    1101
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO N
 * INVALID PLAYER IDENTITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario N: Invalid Player Identity<br>";
echo "============================================<br>";


$invalidPlayerRepository =
    new CaptainWeightHistoryGameweekRepositoryStub(
        [
            [
                'id' =>
                    12,

                'fpl_gameweek_id' =>
                    12,

                'name' =>
                    'Gameweek 12'
            ]
        ]
    );


$invalidPlayerEvidenceService =
    new CaptainWeightHistoryEvidenceServiceStub(
        [

            12 => [

                'status' =>
                    'Ready',

                'reason' =>
                    null,

                'recommendation_snapshot' => [

                    'captain_recommendation' => [

                        'rankings' => [

                            [
                                'player_id' =>
                                    0,

                                'components' => [

                                    'strength' =>
                                        90.0,

                                    'fixture' =>
                                        90.0,

                                    'attacking_threat' =>
                                        90.0,

                                    'confidence_modifier' =>
                                        1.0,

                                    'availability_modifier' =>
                                        1.0
                                ]
                            ],

                            [
                                'player_id' =>
                                    1201,

                                'components' => [

                                    'strength' =>
                                        70.0,

                                    'fixture' =>
                                        70.0,

                                    'attacking_threat' =>
                                        70.0,

                                    'confidence_modifier' =>
                                        1.0,

                                    'availability_modifier' =>
                                        1.0
                                ]
                            ]
                        ]
                    ]
                ],

                'player_outcomes' => [

                    [
                        'player_id' =>
                            1201,

                        'total_points' =>
                            6
                    ]
                ]
            ]
        ]
    );


$invalidPlayerCalibrationService =
    new CaptainWeightHistoryCalibrationServiceStub(
        [
            'evaluations' =>
                []
        ]
    );


$invalidPlayerService =
    new CaptainWeightCalibrationHistoryService(
        $invalidPlayerRepository,
        $invalidPlayerEvidenceService,
        $invalidPlayerCalibrationService
    );


$invalidPlayerResult =
    $invalidPlayerService->evaluate(
        2702264,
        $weightCandidates
    );


$invalidPlayerHistory =
    $invalidPlayerResult[
        'historical_gameweeks'
    ]
    ??
    [];


captainWeightHistoryCheck(
    'Captain ranking with non-positive player identity is ignored',
    count(
        $invalidPlayerHistory[
            0
        ][
            'players'
        ]
        ??
        []
    )
    ===
    1
);


captainWeightHistoryCheck(
    'Valid positive Captain candidate identity is preserved',
    (
        $invalidPlayerHistory[
            0
        ][
            'players'
        ][
            0
        ][
            'player_id'
        ]
        ??
        null
    )
    ===
    1201
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO O
 * EMPTY STORED HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario O: Empty Stored History<br>";
echo "============================================<br>";


$emptyRepository =
    new CaptainWeightHistoryGameweekRepositoryStub(
        []
    );


$emptyEvidenceService =
    new CaptainWeightHistoryEvidenceServiceStub(
        []
    );


$emptyCalibrationReturn = [

    'evaluations' =>
        []
];


$emptyCalibrationService =
    new CaptainWeightHistoryCalibrationServiceStub(
        $emptyCalibrationReturn
    );


$emptyService =
    new CaptainWeightCalibrationHistoryService(
        $emptyRepository,
        $emptyEvidenceService,
        $emptyCalibrationService
    );


$emptyResult =
    $emptyService->evaluate(
        2702264,
        $weightCandidates
    );


captainWeightHistoryCheck(
    'Empty repository reports zero stored gameweeks',
    (
        $emptyResult[
            'total_gameweeks'
        ]
        ??
        null
    )
    ===
    0
);


captainWeightHistoryCheck(
    'Empty repository reports zero Ready gameweeks',
    (
        $emptyResult[
            'ready_gameweeks'
        ]
        ??
        null
    )
    ===
    0
);


captainWeightHistoryCheck(
    'Empty repository exposes empty gameweek audit',
    (
        $emptyResult[
            'gameweeks'
        ]
        ??
        null
    )
    ===
    []
);


captainWeightHistoryCheck(
    'Empty repository exposes no Captain calibration history',
    (
        $emptyResult[
            'historical_gameweeks'
        ]
        ??
        null
    )
    ===
    []
);


captainWeightHistoryCheck(
    'Captain calibrator is still invoked exactly once for empty history',
    count(
        $emptyCalibrationService->calls
    )
    ===
    1
);


captainWeightHistoryCheck(
    'Empty historical collection is delegated explicitly to Captain calibrator',
    (
        $emptyCalibrationService->calls[
            0
        ][
            'historical_gameweeks'
        ]
        ??
        null
    )
    ===
    []
);


captainWeightHistoryCheck(
    'Empty-history calibration result is returned unchanged',
    (
        $emptyResult[
            'calibration'
        ]
        ??
        null
    )
    ===
    $emptyCalibrationReturn
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

captainWeightHistorySummary();