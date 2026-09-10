<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Effective Confidence Weight Calibration History Service Test<br>";
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

function effectiveConfidenceHistoryCheck(
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


function effectiveConfidenceHistorySummary(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Effective Confidence Weight Calibration History Service Test Summary<br>";
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

class EffectiveConfidenceHistoryGameweekRepositoryStub
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

        return $this->gameweeks;
    }
}


class EffectiveConfidenceHistoryEvidenceServiceStub
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


class EffectiveConfidenceHistoryHistoricalEvidenceServiceStub
{
    public array $rowsByGameweek;

    public array $calls =
        [];


    public function __construct(
        array $rowsByGameweek
    ) {

        $this->rowsByGameweek =
            $rowsByGameweek;
    }


    public function build(
        int $entryId,
        int $gameweekId
    ): array {

        $this->calls[] = [

            'entry_id' =>
                $entryId,

            'gameweek_id' =>
                $gameweekId
        ];


        return [

            'entry_id' =>
                $entryId,

            'gameweek_id' =>
                $gameweekId,

            'snapshot' =>
                [],

            'player_outcomes' =>
                [],

            'historical_rows' =>
                $this->rowsByGameweek[
                    $gameweekId
                ]
                ??
                []
        ];
    }
}


class EffectiveConfidenceHistoryCalibrationServiceStub
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
        array $historicalRows,
        array $weightCandidates
    ): array {

        $this->calls[] = [

            'historical_rows' =>
                $historicalRows,

            'weight_candidates' =>
                $weightCandidates
        ];


        return $this->returnValue;
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


effectiveConfidenceHistoryCheck(
    'EffectiveConfidenceWeightCalibrationHistoryService class exists',
    class_exists(
        'EffectiveConfidenceWeightCalibrationHistoryService'
    )
);


if (
    class_exists(
        'EffectiveConfidenceWeightCalibrationHistoryService'
    )
) {

    $reflection =
        new ReflectionClass(
            'EffectiveConfidenceWeightCalibrationHistoryService'
        );


    effectiveConfidenceHistoryCheck(
        'EffectiveConfidenceWeightCalibrationHistoryService exposes evaluate()',
        $reflection->hasMethod(
            'evaluate'
        )
    );

} else {

    effectiveConfidenceHistoryCheck(
        'EffectiveConfidenceWeightCalibrationHistoryService exposes evaluate()',
        false
    );
}


echo "<br>";


/*
 * ============================================================
 * STOP UNTIL PRODUCTION CLASS EXISTS
 * ============================================================
 */

if (
    !class_exists(
        'EffectiveConfidenceWeightCalibrationHistoryService'
    )
) {

    effectiveConfidenceHistorySummary();

    exit;
}


/*
 * ============================================================
 * CONTROLLED HISTORY
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

    /*
     * Malformed / unusable repository rows.
     */
    'malformed',

    [
        'id' => 0,
        'fpl_gameweek_id' => 5,
        'name' => 'Invalid local gameweek'
    ]
];


$evidenceByGameweek = [

    1 => [
        'status' => 'Ready',
        'reason' => null
    ],

    2 => [
        'status' => 'Incomplete',
        'reason' =>
            'Recommendation snapshot is unavailable'
    ],

    3 => [
        'status' => 'Ready',
        'reason' => null
    ],

    4 => [
        'status' => 'Unavailable',
        'reason' =>
            'Gameweek outcomes are not yet authoritative'
    ]
];


$rowsByGameweek = [

    1 => [

        [
            'player_id' => 101,
            'sample_confidence' => 0.30,
            'participation_rate' => 0.80,
            'actual_minutes' => 90,
            'actual_fixture_count' => 1
        ],

        [
            'player_id' => 102,
            'sample_confidence' => 0.40,
            'participation_rate' => 1.00,
            'actual_minutes' => 45,
            'actual_fixture_count' => 1
        ]
    ],

    3 => [

        [
            'player_id' => 101,
            'sample_confidence' => 0.55,
            'participation_rate' => 0.70,
            'actual_minutes' => 135,
            'actual_fixture_count' => 2
        ],

        [
            'player_id' => 103,
            'sample_confidence' => 0.20,
            'participation_rate' => 0.60,
            'actual_minutes' => 0,
            'actual_fixture_count' => 1
        ],

        /*
         * Shared evidence may contain malformed rows.
         * History orchestration must ignore them when pooling.
         */
        'malformed'
    ]
];


$weightCandidates = [

    [
        'sample_weight' => 1.00,
        'participation_weight' => 0.00
    ],

    [
        'sample_weight' => 0.40,
        'participation_weight' => 0.60
    ],

    [
        'sample_weight' => 0.00,
        'participation_weight' => 1.00
    ]
];


$expectedCalibration = [

    'evaluations' => [

        [
            'sample_weight' => 0.40,
            'participation_weight' => 0.60,

            'metrics' => [

                'total_players' => 4,
                'comparable_players' => 4,
                'unavailable_players' => 0,
                'mean_absolute_error' => 0.20,
                'mean_error' => 0.05,
                'correlation' => 0.75
            ]
        ]
    ]
];


$gameweekRepository =
    new EffectiveConfidenceHistoryGameweekRepositoryStub(
        $gameweeks
    );


$evidenceService =
    new EffectiveConfidenceHistoryEvidenceServiceStub(
        $evidenceByGameweek
    );


$historicalEvidenceService =
    new EffectiveConfidenceHistoryHistoricalEvidenceServiceStub(
        $rowsByGameweek
    );


$calibrationService =
    new EffectiveConfidenceHistoryCalibrationServiceStub(
        $expectedCalibration
    );


$service =
    new EffectiveConfidenceWeightCalibrationHistoryService(
        $gameweekRepository,
        $evidenceService,
        $historicalEvidenceService,
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
        true;
}


effectiveConfidenceHistoryCheck(
    'Non-positive entry ID is rejected',
    $invalidEntryThrew
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


effectiveConfidenceHistoryCheck(
    'Stored gameweeks are discovered exactly once',
    $gameweekRepository->getAllCalls === 1
);


effectiveConfidenceHistoryCheck(
    'Only valid positive local gameweeks are counted',
    (
        $result[
            'total_gameweeks'
        ]
        ??
        null
    )
    ===
    4
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * AUTHORITATIVE HISTORICAL ELIGIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Historical Eligibility<br>";
echo "============================================<br>";


effectiveConfidenceHistoryCheck(
    'Historical eligibility is checked for every valid stored gameweek',
    $evidenceService->calls === [

        [
            'entry_id' => 2702264,
            'gameweek_id' => 1
        ],

        [
            'entry_id' => 2702264,
            'gameweek_id' => 2
        ],

        [
            'entry_id' => 2702264,
            'gameweek_id' => 3
        ],

        [
            'entry_id' => 2702264,
            'gameweek_id' => 4
        ]
    ]
);


effectiveConfidenceHistoryCheck(
    'Only Ready gameweeks are counted as calibration-ready',
    (
        $result[
            'ready_gameweeks'
        ]
        ??
        null
    )
    ===
    2
);


effectiveConfidenceHistoryCheck(
    'Only Ready gameweeks reach shared historical evidence extraction',
    $historicalEvidenceService->calls === [

        [
            'entry_id' => 2702264,
            'gameweek_id' => 1
        ],

        [
            'entry_id' => 2702264,
            'gameweek_id' => 3
        ]
    ]
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


effectiveConfidenceHistoryCheck(
    'Every valid gameweek remains visible in the audit',
    count(
        $gameweekAudit
    )
    ===
    4
);


effectiveConfidenceHistoryCheck(
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
        4
    ]
);


effectiveConfidenceHistoryCheck(
    'Gameweek audit preserves authoritative statuses',
    array_column(
        $gameweekAudit,
        'status'
    )
    ===
    [
        'Ready',
        'Incomplete',
        'Ready',
        'Unavailable'
    ]
);


effectiveConfidenceHistoryCheck(
    'Gameweek audit preserves authoritative reasons',
    ($gameweekAudit[1]['reason'] ?? null)
        ===
        'Recommendation snapshot is unavailable'
    &&
    ($gameweekAudit[3]['reason'] ?? null)
        ===
        'Gameweek outcomes are not yet authoritative'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * POOLED HISTORICAL SAMPLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Pooled Historical Sample<br>";
echo "============================================<br>";


$historicalRows =
    $result[
        'historical_rows'
    ]
    ??
    [];


effectiveConfidenceHistoryCheck(
    'Ready gameweeks are pooled into one player-gameweek sample',
    count(
        $historicalRows
    )
    ===
    4
);


effectiveConfidenceHistoryCheck(
    'Malformed shared historical rows are ignored while pooling',
    count(
        $historicalRows
    )
    ===
    4
);


effectiveConfidenceHistoryCheck(
    'Combined historical rows retain local gameweek identity',
    array_column(
        $historicalRows,
        'gameweek_id'
    )
    ===
    [
        1,
        1,
        3,
        3
    ]
);


effectiveConfidenceHistoryCheck(
    'The same player in different gameweeks remains a distinct observation',
    ($historicalRows[0]['player_id'] ?? null) === 101
    &&
    ($historicalRows[2]['player_id'] ?? null) === 101
    &&
    ($historicalRows[0]['gameweek_id'] ?? null)
        !==
        ($historicalRows[2]['gameweek_id'] ?? null)
);


effectiveConfidenceHistoryCheck(
    'Recommendation-time Sample Confidence is preserved in pooled rows',
    array_column(
        $historicalRows,
        'sample_confidence'
    )
    ===
    [
        0.30,
        0.40,
        0.55,
        0.20
    ]
);


effectiveConfidenceHistoryCheck(
    'Recommendation-time Participation Rate is preserved in pooled rows',
    array_column(
        $historicalRows,
        'participation_rate'
    )
    ===
    [
        0.80,
        1.00,
        0.70,
        0.60
    ]
);


effectiveConfidenceHistoryCheck(
    'Realised minutes are preserved in pooled rows',
    array_column(
        $historicalRows,
        'actual_minutes'
    )
    ===
    [
        90,
        45,
        135,
        0
    ]
);


effectiveConfidenceHistoryCheck(
    'Realised fixture counts are preserved in pooled rows',
    array_column(
        $historicalRows,
        'actual_fixture_count'
    )
    ===
    [
        1,
        1,
        2,
        1
    ]
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * AGGREGATE CALIBRATION DELEGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Aggregate Calibration Delegation<br>";
echo "============================================<br>";


effectiveConfidenceHistoryCheck(
    'Calibration service is called exactly once across pooled history',
    count(
        $calibrationService->calls
    )
    ===
    1
);


effectiveConfidenceHistoryCheck(
    'Calibration receives the complete pooled historical sample',
    (
        $calibrationService->calls[
            0
        ][
            'historical_rows'
        ]
        ??
        null
    )
    ===
    $historicalRows
);


effectiveConfidenceHistoryCheck(
    'Calibration receives the caller-supplied weight candidates unchanged',
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


effectiveConfidenceHistoryCheck(
    'History service returns calibration output unchanged',
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
 * SCENARIO H
 * ENTRY ID CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Result Contract<br>";
echo "============================================<br>";


effectiveConfidenceHistoryCheck(
    'Result preserves entry ID',
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


effectiveConfidenceHistoryCheck(
    'Result exposes total gameweek count',
    array_key_exists(
        'total_gameweeks',
        $result
    )
);


effectiveConfidenceHistoryCheck(
    'Result exposes Ready gameweek count',
    array_key_exists(
        'ready_gameweeks',
        $result
    )
);


effectiveConfidenceHistoryCheck(
    'Result exposes gameweek audit',
    array_key_exists(
        'gameweeks',
        $result
    )
);


effectiveConfidenceHistoryCheck(
    'Result exposes pooled historical rows',
    array_key_exists(
        'historical_rows',
        $result
    )
);


effectiveConfidenceHistoryCheck(
    'Result exposes aggregate calibration',
    array_key_exists(
        'calibration',
        $result
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * NO STORED GAMEWEEKS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: No Stored Gameweeks<br>";
echo "============================================<br>";


$emptyRepository =
    new EffectiveConfidenceHistoryGameweekRepositoryStub(
        []
    );


$emptyEvidenceService =
    new EffectiveConfidenceHistoryEvidenceServiceStub(
        []
    );


$emptyHistoricalEvidenceService =
    new EffectiveConfidenceHistoryHistoricalEvidenceServiceStub(
        []
    );


$emptyCalibrationService =
    new EffectiveConfidenceHistoryCalibrationServiceStub(
        [
            'evaluations' => []
        ]
    );


$emptyHistoryService =
    new EffectiveConfidenceWeightCalibrationHistoryService(
        $emptyRepository,
        $emptyEvidenceService,
        $emptyHistoricalEvidenceService,
        $emptyCalibrationService
    );


$emptyResult =
    $emptyHistoryService->evaluate(
        2702264,
        $weightCandidates
    );


effectiveConfidenceHistoryCheck(
    'No stored gameweeks returns zero total gameweeks',
    ($emptyResult['total_gameweeks'] ?? null) === 0
);


effectiveConfidenceHistoryCheck(
    'No stored gameweeks returns zero Ready gameweeks',
    ($emptyResult['ready_gameweeks'] ?? null) === 0
);


effectiveConfidenceHistoryCheck(
    'No stored gameweeks returns an empty audit',
    ($emptyResult['gameweeks'] ?? null) === []
);


effectiveConfidenceHistoryCheck(
    'No stored gameweeks returns an empty historical sample',
    ($emptyResult['historical_rows'] ?? null) === []
);


effectiveConfidenceHistoryCheck(
    'Calibration still runs once against the empty historical sample',
    count(
        $emptyCalibrationService->calls
    )
    ===
    1
    &&
    (
        $emptyCalibrationService->calls[
            0
        ][
            'historical_rows'
        ]
        ??
        null
    )
    ===
    []
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * READY GAMEWEEK WITH NO HISTORICAL ROWS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Ready Gameweek Without Rows<br>";
echo "============================================<br>";


$noRowsRepository =
    new EffectiveConfidenceHistoryGameweekRepositoryStub(
        [
            [
                'id' => 10,
                'fpl_gameweek_id' => 10,
                'name' => 'Gameweek 10'
            ]
        ]
    );


$noRowsEvidence =
    new EffectiveConfidenceHistoryEvidenceServiceStub(
        [
            10 => [
                'status' => 'Ready',
                'reason' => null
            ]
        ]
    );


$noRowsHistoricalEvidence =
    new EffectiveConfidenceHistoryHistoricalEvidenceServiceStub(
        [
            10 => []
        ]
    );


$noRowsCalibration =
    new EffectiveConfidenceHistoryCalibrationServiceStub(
        [
            'evaluations' => []
        ]
    );


$noRowsService =
    new EffectiveConfidenceWeightCalibrationHistoryService(
        $noRowsRepository,
        $noRowsEvidence,
        $noRowsHistoricalEvidence,
        $noRowsCalibration
    );


$noRowsResult =
    $noRowsService->evaluate(
        2702264,
        $weightCandidates
    );


effectiveConfidenceHistoryCheck(
    'Authoritative Ready status is retained even when no historical rows are returned',
    ($noRowsResult['ready_gameweeks'] ?? null) === 1
);


effectiveConfidenceHistoryCheck(
    'Ready gameweek without rows contributes no manufactured evidence',
    ($noRowsResult['historical_rows'] ?? null) === []
);


effectiveConfidenceHistoryCheck(
    'Ready gameweek without rows remains visible as Ready in audit',
    ($noRowsResult['gameweeks'][0]['status'] ?? null)
        ===
        'Ready'
);


effectiveConfidenceHistorySummary();