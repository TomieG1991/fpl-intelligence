<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Position-Aware Fixture Weight Calibration History Service Test<br>";
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

function positionAwareFixtureHistoryCheck(
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


function positionAwareFixtureHistorySummary(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Position-Aware Fixture Weight Calibration History Service Test Summary<br>";
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

class PositionAwareFixtureHistoryGameweekRepositoryStub
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


class PositionAwareFixtureHistoryEvidenceServiceStub
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
            'entry_id' => $entryId,
            'gameweek_id' => $gameweekId
        ];


        return
            $this->evidenceByGameweek[
                $gameweekId
            ]
            ?? [
                'status' => 'Unavailable',
                'reason' => 'No controlled evidence.'
            ];
    }
}


class PositionAwareFixtureHistoryHistoricalEvidenceServiceStub
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
            'entry_id' => $entryId,
            'gameweek_id' => $gameweekId
        ];


        return [
            'entry_id' => $entryId,
            'gameweek_id' => $gameweekId,
            'snapshot' => [],
            'player_outcomes' => [],
            'historical_rows' =>
                $this->rowsByGameweek[
                    $gameweekId
                ]
                ?? []
        ];
    }
}


class PositionAwareFixtureHistoryCalibrationServiceStub
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
            'historical_rows' => $historicalRows,
            'weight_candidates' => $weightCandidates
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


positionAwareFixtureHistoryCheck(
    'PositionAwareFixtureWeightCalibrationHistoryService class exists',
    class_exists(
        'PositionAwareFixtureWeightCalibrationHistoryService'
    )
);


if (
    class_exists(
        'PositionAwareFixtureWeightCalibrationHistoryService'
    )
) {

    $reflection =
        new ReflectionClass(
            'PositionAwareFixtureWeightCalibrationHistoryService'
        );


    positionAwareFixtureHistoryCheck(
        'PositionAwareFixtureWeightCalibrationHistoryService exposes evaluate()',
        $reflection->hasMethod(
            'evaluate'
        )
    );

} else {

    positionAwareFixtureHistoryCheck(
        'PositionAwareFixtureWeightCalibrationHistoryService exposes evaluate()',
        false
    );
}


echo "<br>";


/*
 * ============================================================
 * STOP IF PRODUCTION CLASS DOES NOT YET EXIST
 * ============================================================
 */

if (
    !class_exists(
        'PositionAwareFixtureWeightCalibrationHistoryService'
    )
) {

    positionAwareFixtureHistorySummary();

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
    ]
];


$evidenceByGameweek = [

    1 => [
        'status' => 'Ready',
        'reason' => null
    ],

    2 => [
        'status' => 'Incomplete',
        'reason' => 'Recommendation snapshot is unavailable'
    ],

    3 => [
        'status' => 'Ready',
        'reason' => null
    ],

    4 => [
        'status' => 'Unavailable',
        'reason' => 'Gameweek outcomes are not yet authoritative'
    ]
];


$rowsByGameweek = [

    1 => [

        [
            'player_id' => 101,
            'position' => 'DEF',
            'base_next_fixture_rating' => 78.0,
            'next_opponent_attack_rating' => 35.0,
            'next_opponent_defence_rating' => 28.0,
            'actual_points' => 8
        ],

        [
            'player_id' => 102,
            'position' => 'MID',
            'base_next_fixture_rating' => 72.0,
            'next_opponent_attack_rating' => 48.0,
            'next_opponent_defence_rating' => 56.0,
            'actual_points' => 5
        ]
    ],

    3 => [

        [
            'player_id' => 101,
            'position' => 'DEF',
            'base_next_fixture_rating' => 58.0,
            'next_opponent_attack_rating' => 62.0,
            'next_opponent_defence_rating' => 70.0,
            'actual_points' => 2
        ],

        [
            'player_id' => 103,
            'position' => 'FWD',
            'base_next_fixture_rating' => 88.0,
            'next_opponent_attack_rating' => 20.0,
            'next_opponent_defence_rating' => 12.0,
            'actual_points' => 11
        ]
    ]
];


$weightCandidates = [

    [
        'base_fixture_weight' => 1.00,
        'position_performance_weight' => 0.00
    ],

    [
        'base_fixture_weight' => 0.75,
        'position_performance_weight' => 0.25
    ],

    [
        'base_fixture_weight' => 0.50,
        'position_performance_weight' => 0.50
    ]
];


$expectedCalibration = [

    'evaluations' => [

        [
            'base_fixture_weight' => 0.75,
            'position_performance_weight' => 0.25,
            'metrics' => [
                'total_players' => 4,
                'comparable_players' => 4,
                'unavailable_players' => 0,
                'correlation' => 0.75
            ]
        ]
    ]
];


$gameweekRepository =
    new PositionAwareFixtureHistoryGameweekRepositoryStub(
        $gameweeks
    );


$evidenceService =
    new PositionAwareFixtureHistoryEvidenceServiceStub(
        $evidenceByGameweek
    );


$historicalEvidenceService =
    new PositionAwareFixtureHistoryHistoricalEvidenceServiceStub(
        $rowsByGameweek
    );


$calibrationService =
    new PositionAwareFixtureHistoryCalibrationServiceStub(
        $expectedCalibration
    );


$service =
    new PositionAwareFixtureWeightCalibrationHistoryService(
        $gameweekRepository,
        $evidenceService,
        new stdClass(),
        $calibrationService,
        $historicalEvidenceService
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


positionAwareFixtureHistoryCheck(
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


positionAwareFixtureHistoryCheck(
    'Stored gameweeks are discovered exactly once',
    $gameweekRepository->getAllCalls === 1
);


positionAwareFixtureHistoryCheck(
    'All valid stored gameweeks are counted',
    (
        $result[
            'total_gameweeks'
        ]
        ?? null
    )
    === 4
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


positionAwareFixtureHistoryCheck(
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


positionAwareFixtureHistoryCheck(
    'Only Ready gameweeks are counted as calibration-ready',
    (
        $result[
            'ready_gameweeks'
        ]
        ?? null
    )
    === 2
);


positionAwareFixtureHistoryCheck(
    'Only Ready gameweeks reach the shared historical evidence service',
    count(
        $historicalEvidenceService->calls
    )
    === 2
    &&
    ($historicalEvidenceService->calls[0]['gameweek_id'] ?? null) === 1
    &&
    ($historicalEvidenceService->calls[1]['gameweek_id'] ?? null) === 3
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * SHARED HISTORICAL EVIDENCE DELEGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Shared Historical Evidence Delegation<br>";
echo "============================================<br>";


positionAwareFixtureHistoryCheck(
    'Ready gameweek historical evidence receives only entry and gameweek identity',
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
 * SCENARIO F
 * COMBINED HISTORICAL SAMPLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Combined Historical Sample<br>";
echo "============================================<br>";


$historicalRows =
    $result[
        'historical_rows'
    ]
    ?? [];


positionAwareFixtureHistoryCheck(
    'Ready gameweeks are pooled into one historical player-gameweek sample',
    count(
        $historicalRows
    )
    === 4
);


positionAwareFixtureHistoryCheck(
    'Combined historical rows retain gameweek identity',
    array_column(
        $historicalRows,
        'gameweek_id'
    )
    === [
        1,
        1,
        3,
        3
    ]
);


positionAwareFixtureHistoryCheck(
    'The same player in different gameweeks remains a distinct historical observation',
    ($historicalRows[0]['player_id'] ?? null) === 101
    &&
    ($historicalRows[2]['player_id'] ?? null) === 101
    &&
    ($historicalRows[0]['gameweek_id'] ?? null) !==
        ($historicalRows[2]['gameweek_id'] ?? null)
);


positionAwareFixtureHistoryCheck(
    'Pooled rows preserve base next-fixture evidence',
    array_column(
        $historicalRows,
        'base_next_fixture_rating'
    )
    === [
        78.0,
        72.0,
        58.0,
        88.0
    ]
);


positionAwareFixtureHistoryCheck(
    'Pooled rows preserve opponent Attack evidence',
    array_column(
        $historicalRows,
        'next_opponent_attack_rating'
    )
    === [
        35.0,
        48.0,
        62.0,
        20.0
    ]
);


positionAwareFixtureHistoryCheck(
    'Pooled rows preserve opponent Defence evidence',
    array_column(
        $historicalRows,
        'next_opponent_defence_rating'
    )
    === [
        28.0,
        56.0,
        70.0,
        12.0
    ]
);


positionAwareFixtureHistoryCheck(
    'Pooled rows preserve historical player position',
    array_column(
        $historicalRows,
        'position'
    )
    === [
        'DEF',
        'MID',
        'DEF',
        'FWD'
    ]
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * AGGREGATE CALIBRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Aggregate Calibration<br>";
echo "============================================<br>";


positionAwareFixtureHistoryCheck(
    'Position-aware calibration service is called exactly once',
    count(
        $calibrationService->calls
    )
    === 1
);


positionAwareFixtureHistoryCheck(
    'Position-aware calibration receives the complete pooled historical sample',
    (
        $calibrationService->calls[0][
            'historical_rows'
        ]
        ?? null
    )
    === $historicalRows
);


positionAwareFixtureHistoryCheck(
    'Aggregate calibration receives the supplied weight candidates unchanged',
    (
        $calibrationService->calls[0][
            'weight_candidates'
        ]
        ?? null
    )
    === $weightCandidates
);


positionAwareFixtureHistoryCheck(
    'Aggregate calibration result is returned unchanged',
    (
        $result[
            'calibration'
        ]
        ?? null
    )
    === $expectedCalibration
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * AUDITABLE GAMEWEEK COVERAGE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Auditable Gameweek Coverage<br>";
echo "============================================<br>";


$gameweekAudit =
    $result[
        'gameweeks'
    ]
    ?? [];


positionAwareFixtureHistoryCheck(
    'Every considered gameweek remains visible in the audit',
    count(
        $gameweekAudit
    )
    === 4
);


positionAwareFixtureHistoryCheck(
    'Gameweek audit preserves repository order',
    array_column(
        $gameweekAudit,
        'gameweek_id'
    )
    === [
        1,
        2,
        3,
        4
    ]
);


positionAwareFixtureHistoryCheck(
    'Incomplete gameweek retains its authoritative reason',
    (
        $gameweekAudit[1][
            'status'
        ]
        ?? null
    )
    === 'Incomplete'
    &&
    (
        $gameweekAudit[1][
            'reason'
        ]
        ?? null
    )
    === 'Recommendation snapshot is unavailable'
);


positionAwareFixtureHistoryCheck(
    'Unavailable gameweek retains its authoritative reason',
    (
        $gameweekAudit[3][
            'status'
        ]
        ?? null
    )
    === 'Unavailable'
    &&
    (
        $gameweekAudit[3][
            'reason'
        ]
        ?? null
    )
    === 'Gameweek outcomes are not yet authoritative'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * MALFORMED STORED GAMEWEEKS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Malformed Stored Gameweeks<br>";
echo "============================================<br>";


$malformedRepository =
    new PositionAwareFixtureHistoryGameweekRepositoryStub(
        [
            'not-an-array',

            [
                'id' => 0,
                'fpl_gameweek_id' => 5,
                'name' => 'Invalid'
            ],

            [
                'id' => 9,
                'fpl_gameweek_id' => 9,
                'name' => 'Gameweek 9'
            ]
        ]
    );


$malformedEvidenceService =
    new PositionAwareFixtureHistoryEvidenceServiceStub(
        [
            9 => [
                'status' => 'Ready',
                'reason' => null
            ]
        ]
    );


$malformedHistoricalEvidenceService =
    new PositionAwareFixtureHistoryHistoricalEvidenceServiceStub(
        [
            9 => []
        ]
    );


$malformedCalibrationService =
    new PositionAwareFixtureHistoryCalibrationServiceStub(
        [
            'evaluations' => []
        ]
    );


$malformedService =
    new PositionAwareFixtureWeightCalibrationHistoryService(
        $malformedRepository,
        $malformedEvidenceService,
        new stdClass(),
        $malformedCalibrationService,
        $malformedHistoricalEvidenceService
    );


$malformedResult =
    $malformedService->evaluate(
        2702264,
        []
    );


positionAwareFixtureHistoryCheck(
    'Malformed and non-positive stored gameweeks are ignored',
    (
        $malformedResult[
            'total_gameweeks'
        ]
        ?? null
    )
    === 1
    &&
    (
        $malformedResult[
            'ready_gameweeks'
        ]
        ?? null
    )
    === 1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * EMPTY HISTORICAL UNIVERSE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Empty Historical Universe<br>";
echo "============================================<br>";


$emptyRepository =
    new PositionAwareFixtureHistoryGameweekRepositoryStub(
        []
    );


$emptyEvidenceService =
    new PositionAwareFixtureHistoryEvidenceServiceStub(
        []
    );


$emptyHistoricalEvidenceService =
    new PositionAwareFixtureHistoryHistoricalEvidenceServiceStub(
        []
    );


$emptyCalibrationService =
    new PositionAwareFixtureHistoryCalibrationServiceStub(
        [
            'evaluations' => []
        ]
    );


$emptyService =
    new PositionAwareFixtureWeightCalibrationHistoryService(
        $emptyRepository,
        $emptyEvidenceService,
        new stdClass(),
        $emptyCalibrationService,
        $emptyHistoricalEvidenceService
    );


$emptyResult =
    $emptyService->evaluate(
        2702264,
        $weightCandidates
    );


positionAwareFixtureHistoryCheck(
    'Empty history reports zero stored gameweeks',
    ($emptyResult['total_gameweeks'] ?? null) === 0
);


positionAwareFixtureHistoryCheck(
    'Empty history reports zero Ready gameweeks',
    ($emptyResult['ready_gameweeks'] ?? null) === 0
);


positionAwareFixtureHistoryCheck(
    'Empty history produces no historical player rows',
    ($emptyResult['historical_rows'] ?? null) === []
);


positionAwareFixtureHistoryCheck(
    'Calibration is still invoked once for an empty historical sample',
    count(
        $emptyCalibrationService->calls
    )
    === 1
);


positionAwareFixtureHistoryCheck(
    'Empty historical calibration receives the supplied candidates unchanged',
    (
        $emptyCalibrationService->calls[0][
            'weight_candidates'
        ]
        ?? null
    )
    === $weightCandidates
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * SOURCE EVIDENCE IMMUTABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Source Evidence Immutability<br>";
echo "============================================<br>";


$sourceRows =
    $rowsByGameweek;


$immutabilityHistoricalEvidenceService =
    new PositionAwareFixtureHistoryHistoricalEvidenceServiceStub(
        $rowsByGameweek
    );


$immutabilityService =
    new PositionAwareFixtureWeightCalibrationHistoryService(
        new PositionAwareFixtureHistoryGameweekRepositoryStub(
            $gameweeks
        ),
        new PositionAwareFixtureHistoryEvidenceServiceStub(
            $evidenceByGameweek
        ),
        new stdClass(),
        new PositionAwareFixtureHistoryCalibrationServiceStub(
            $expectedCalibration
        ),
        $immutabilityHistoricalEvidenceService
    );


$immutabilityService->evaluate(
    2702264,
    $weightCandidates
);


positionAwareFixtureHistoryCheck(
    'History aggregation does not mutate the supplied single-gameweek evidence',
    $immutabilityHistoricalEvidenceService->rowsByGameweek
    === $sourceRows
);


echo "<br>";


positionAwareFixtureHistorySummary();