<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Intelligence Weight Calibration Evidence Delegation Test<br>";
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

function playerCalibrationDelegationCheck(
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


function playerCalibrationDelegationSummary(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Player Intelligence Weight Calibration Evidence Delegation Test Summary<br>";
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

class PlayerCalibrationDelegationForbiddenSnapshotRepository
{
    public function getByEntryAndGameweek(
        int $entryId,
        int $gameweekId
    ): ?array {

        throw new RuntimeException(
            'Evaluation service queried snapshot repository directly.'
        );
    }
}


class PlayerCalibrationDelegationForbiddenOutcomeService
{
    public function getByGameweekId(
        int $gameweekId
    ): array {

        throw new RuntimeException(
            'Evaluation service queried outcome service directly.'
        );
    }
}


class PlayerCalibrationDelegationHistoricalEvidenceStub
{
    public array $calls =
        [];


    public array $result;


    public function __construct(
        array $result
    ) {

        $this->result =
            $result;
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


        return
            $this->result;
    }
}


class PlayerCalibrationDelegationCalibrationStub
{
    public array $calls =
        [];


    public array $result;


    public function __construct(
        array $result
    ) {

        $this->result =
            $result;
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


        return
            $this->result;
    }
}


/*
 * ============================================================
 * CONTROLLED EVIDENCE
 * ============================================================
 */

$snapshot = [

    'id' =>
        500,

    'gameweek_id' =>
        5,

    'entry_id' =>
        2702264
];


$outcomes = [

    [
        'player_id' =>
            101,

        'total_points' =>
            10
    ]
];


$historicalRows = [

    [
        'player_id' =>
            101,

        'fpl_player_id' =>
            1001,

        'name' =>
            'Player One',

        'position' =>
            'MID',

        'strength_rating' =>
            86.0,

        'fixture_rating' =>
            76.0,

        'next_fixture_rating' =>
            82.0,

        'base_next_fixture_rating' =>
            78.0,

        'next_opponent_attack_rating' =>
            35.0,

        'next_opponent_defence_rating' =>
            28.0,

        'availability_multiplier' =>
            0.95,

        'actual_points' =>
            10
    ]
];


$evidenceResult = [

    'entry_id' =>
        2702264,

    'gameweek_id' =>
        5,

    'snapshot' =>
        $snapshot,

    'player_outcomes' =>
        $outcomes,

    'historical_rows' =>
        $historicalRows
];


$weightCandidates = [

    [
        'strength_weight' =>
            0.65,

        'fixture_weight' =>
            0.35
    ]
];


$calibrationResult = [

    'evaluations' => [

        [
            'strength_weight' =>
                0.65,

            'fixture_weight' =>
                0.35,

            'player_scores' =>
                [],

            'metrics' => [

                'total_players' =>
                    1,

                'comparable_players' =>
                    1,

                'unavailable_players' =>
                    0,

                'correlation' =>
                    null
            ]
        ]
    ]
];


/*
 * ============================================================
 * SCENARIO A
 * REUSABLE HISTORICAL EVIDENCE DELEGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Historical Evidence Delegation<br>";
echo "============================================<br>";


$historicalEvidenceService =
    new PlayerCalibrationDelegationHistoricalEvidenceStub(
        $evidenceResult
    );


$calibrationService =
    new PlayerCalibrationDelegationCalibrationStub(
        $calibrationResult
    );


$service =
    new PlayerIntelligenceWeightCalibrationEvaluationService(
        new PlayerCalibrationDelegationForbiddenSnapshotRepository(),
        new PlayerCalibrationDelegationForbiddenOutcomeService(),
        $calibrationService,
        $historicalEvidenceService
    );


$threw =
    false;


$result =
    null;


try {

    $result =
        $service->evaluate(
            2702264,
            5,
            $weightCandidates
        );

} catch (
    RuntimeException $exception
) {

    $threw =
        true;


    echo "Observed runtime failure: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


playerCalibrationDelegationCheck(
    'Evaluation uses the reusable historical evidence service instead of querying source repositories directly',
    !$threw
);


playerCalibrationDelegationCheck(
    'Historical evidence service receives the requested entry and gameweek',
    $historicalEvidenceService->calls === [

        [
            'entry_id' =>
                2702264,

            'gameweek_id' =>
                5
        ]
    ]
);


playerCalibrationDelegationCheck(
    'Calibration receives historical rows from the reusable evidence service unchanged',
    (
        $calibrationService->calls[
            0
        ][
            'historical_rows'
        ]
        ?? null
    )
    === $historicalRows
);


playerCalibrationDelegationCheck(
    'Calibration receives supplied Strength/Fixture candidates unchanged',
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


playerCalibrationDelegationCheck(
    'Evaluation preserves the existing result contract',
    $result === [

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            5,

        'snapshot' =>
            $snapshot,

        'player_outcomes' =>
            $outcomes,

        'historical_rows' =>
            $historicalRows,

        'calibration' =>
            $calibrationResult
    ]
);


echo "<br>";


playerCalibrationDelegationSummary();