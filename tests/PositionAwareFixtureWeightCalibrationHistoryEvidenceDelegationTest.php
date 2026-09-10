<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Position-Aware Fixture Calibration History Evidence Delegation Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function positionAwareHistoryDelegationCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


function positionAwareHistoryDelegationSummary(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Position-Aware Fixture Calibration History Evidence Delegation Test Summary<br>";
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

class PositionAwareHistoryDelegationGameweekRepositoryStub
{
    public int $calls = 0;


    public function getAll(): array {

        $this->calls++;


        return [

            [
                'id' => 5,
                'fpl_gameweek_id' => 4,
                'name' => 'Gameweek 4'
            ]
        ];
    }
}


class PositionAwareHistoryDelegationEvidenceStatusStub
{
    public array $calls = [];


    public function getEvidence(
        int $entryId,
        int $gameweekId
    ): array {

        $this->calls[] = [

            'entry_id' => $entryId,
            'gameweek_id' => $gameweekId
        ];


        return [

            'status' => 'Ready',
            'reason' => null
        ];
    }
}


class PositionAwareHistoryDelegationHistoricalEvidenceStub
{
    public array $calls = [];


    public array $result;


    public function __construct(
        array $result
    ) {

        $this->result = $result;
    }


    public function build(
        int $entryId,
        int $gameweekId
    ): array {

        $this->calls[] = [

            'entry_id' => $entryId,
            'gameweek_id' => $gameweekId
        ];


        return $this->result;
    }
}


class PositionAwareHistoryDelegationForbiddenEvaluationStub
{
    public function evaluate(
        int $entryId,
        int $gameweekId,
        array $weightCandidates
    ): array {

        throw new RuntimeException(
            'Position-aware history service invoked Strength/Fixture evaluation service.'
        );
    }
}


class PositionAwareHistoryDelegationCalibrationStub
{
    public array $calls = [];


    public array $result;


    public function __construct(
        array $result
    ) {

        $this->result = $result;
    }


    public function evaluate(
        array $historicalRows,
        array $weightCandidates
    ): array {

        $this->calls[] = [

            'historical_rows' => $historicalRows,
            'weight_candidates' => $weightCandidates
        ];


        return $this->result;
    }
}


/*
 * ============================================================
 * CONTROLLED DATA
 * ============================================================
 */

$historicalRows = [

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
];


$historicalEvidenceResult = [

    'entry_id' => 2702264,
    'gameweek_id' => 5,
    'snapshot' => [],
    'player_outcomes' => [],
    'historical_rows' => $historicalRows
];


$weightCandidates = [

    [
        'base_fixture_weight' => 0.75,
        'position_performance_weight' => 0.25
    ],

    [
        'base_fixture_weight' => 0.50,
        'position_performance_weight' => 0.50
    ]
];


$calibrationResult = [

    'evaluations' => []
];


/*
 * ============================================================
 * SCENARIO A
 * SHARED HISTORICAL EVIDENCE DELEGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Shared Historical Evidence Delegation<br>";
echo "============================================<br>";


$gameweekRepository =
    new PositionAwareHistoryDelegationGameweekRepositoryStub();


$statusEvidenceService =
    new PositionAwareHistoryDelegationEvidenceStatusStub();


$historicalEvidenceService =
    new PositionAwareHistoryDelegationHistoricalEvidenceStub(
        $historicalEvidenceResult
    );


$calibrationService =
    new PositionAwareHistoryDelegationCalibrationStub(
        $calibrationResult
    );


$service =
    new PositionAwareFixtureWeightCalibrationHistoryService(
        $gameweekRepository,
        $statusEvidenceService,
        new PositionAwareHistoryDelegationForbiddenEvaluationStub(),
        $calibrationService,
        $historicalEvidenceService
    );


$threw = false;
$result = null;


try {

    $result =
        $service->evaluate(
            2702264,
            $weightCandidates
        );

} catch (
    RuntimeException $exception
) {

    $threw = true;


    echo "Observed runtime failure: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


positionAwareHistoryDelegationCheck(
    'Position-aware history uses shared historical evidence instead of Strength/Fixture evaluation',
    !$threw
);


positionAwareHistoryDelegationCheck(
    'Shared historical evidence is requested for the Ready gameweek',
    $historicalEvidenceService->calls === [

        [
            'entry_id' => 2702264,
            'gameweek_id' => 5
        ]
    ]
);


positionAwareHistoryDelegationCheck(
    'Pooled evidence receives source gameweek identity',
    (
        $result[
            'historical_rows'
        ][0][
            'gameweek_id'
        ]
        ?? null
    )
    === 5
);


$expectedPooledRows =
    $historicalRows;


foreach (
    $expectedPooledRows
    as &$row
) {

    $row[
        'gameweek_id'
    ] = 5;
}

unset(
    $row
);


positionAwareHistoryDelegationCheck(
    'Position-aware calibrator receives pooled historical evidence directly',
    (
        $calibrationService->calls[
            0
        ][
            'historical_rows'
        ]
        ?? null
    )
    === $expectedPooledRows
);


positionAwareHistoryDelegationCheck(
    'Position-aware candidates reach only the position-aware calibrator unchanged',
    (
        $calibrationService->calls[
            0
        ][
            'weight_candidates'
        ]
        ?? null
    )
    === $weightCandidates
);


positionAwareHistoryDelegationCheck(
    'Aggregate calibration result is preserved unchanged',
    (
        $result[
            'calibration'
        ]
        ?? null
    )
    === $calibrationResult
);


echo "<br>";


positionAwareHistoryDelegationSummary();