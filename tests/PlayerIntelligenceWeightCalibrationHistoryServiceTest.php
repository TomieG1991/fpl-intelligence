<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * PLAYER INTELLIGENCE WEIGHT CALIBRATION HISTORY SERVICE TEST
 * ============================================================
 *
 * v0.36.0 — Model Calibration & Intelligence Quality
 *
 * This service evaluates explicitly supplied Player Intelligence
 * Strength / Fixture weight candidates across all historically
 * eligible gameweeks for one FPL entry.
 *
 * Historical eligibility must come from:
 *
 * GameweekBacktestingEvidenceService
 *
 * It must:
 *
 * - discover stored gameweeks through GameweekRepository
 * - preserve FPL gameweek order
 * - ask the authoritative backtesting evidence boundary whether
 *   each gameweek is Ready
 * - ignore Unavailable / Incomplete gameweeks
 * - reuse the existing single-gameweek calibration evaluation
 * - combine historical player rows from eligible gameweeks
 * - evaluate supplied weight candidates across the combined sample
 * - preserve gameweek identity in combined historical rows
 *
 * It must not:
 *
 * - infer gameweek eligibility itself
 * - reconstruct missing recommendation evidence
 * - query live Player Intelligence
 * - query fixture history directly
 * - manufacture realised outcomes
 * - choose a winning weight combination
 * - modify production weights
 * - persist calibration results
 */


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed =
    0;


$failed =
    0;


function playerIntelligenceCalibrationHistoryAssert(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


function playerIntelligenceCalibrationHistorySection(
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


function playerIntelligenceCalibrationHistorySummary(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Player Intelligence Weight Calibration History Service Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";


    if ($failed === 0) {

        echo "<strong>RESULT: ALL TESTS PASSED ✅</strong><br>";

    } else {

        echo "<strong>RESULT: TEST FAILURES DETECTED ❌</strong><br>";
    }
}


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class PlayerIntelligenceCalibrationHistoryGameweekRepositoryStub
{
    public array $gameweeks;

    public int $calls =
        0;


    public function __construct(
        array $gameweeks
    ) {

        $this->gameweeks =
            $gameweeks;
    }


    public function getAll(): array
    {
        $this->calls++;

        return
            $this->gameweeks;
    }
}


class PlayerIntelligenceCalibrationHistoryEvidenceServiceStub
{
    public array $evidenceByGameweekId;

    public array $calls =
        [];


    public function __construct(
        array $evidenceByGameweekId
    ) {

        $this->evidenceByGameweekId =
            $evidenceByGameweekId;
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
            $this->evidenceByGameweekId[
                $gameweekId
            ]
            ??
            [
                'status' =>
                    'Unavailable',

                'reason' =>
                    'No controlled evidence'
            ];
    }
}


class PlayerIntelligenceCalibrationHistoryEvaluationServiceStub
{
    public array $resultsByGameweekId;

    public array $calls =
        [];


    public function __construct(
        array $resultsByGameweekId
    ) {

        $this->resultsByGameweekId =
            $resultsByGameweekId;
    }


    public function evaluate(
        int $entryId,
        int $gameweekId,
        array $weightCandidates
    ): ?array {

        $this->calls[] = [

            'entry_id' =>
                $entryId,

            'gameweek_id' =>
                $gameweekId,

            'weight_candidates' =>
                $weightCandidates
        ];


        return
            $this->resultsByGameweekId[
                $gameweekId
            ]
            ??
            null;
    }
}


class PlayerIntelligenceCalibrationHistoryCalibrationServiceStub
{
    public array $result;

    public array $calls =
        [];


    public function __construct(
        array $result
    ) {

        $this->result =
            $result;
    }


    public function evaluate(
        array $backtestRows,
        array $weightCandidates
    ): array {

        $this->calls[] = [

            'backtest_rows' =>
                $backtestRows,

            'weight_candidates' =>
                $weightCandidates
        ];


        return
            $this->result;
    }
}


/*
 * ============================================================
 * CONTROLLED GAMEWEEKS
 * ============================================================
 */

$gameweeks = [

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
    ]
];


/*
 * GW1 and GW3 are Ready.
 *
 * GW2 is authoritative but has no recommendation snapshot.
 *
 * GW4 is not yet authoritative.
 */

$evidenceByGameweekId = [

    1 => [

        'status' =>
            'Ready',

        'reason' =>
            null,

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            1
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
            3
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
    ]
];


$weightCandidates = [

    [
        'strength_weight' =>
            0.50,

        'fixture_weight' =>
            0.50
    ],

    [
        'strength_weight' =>
            0.65,

        'fixture_weight' =>
            0.35
    ],

    [
        'strength_weight' =>
            0.80,

        'fixture_weight' =>
            0.20
    ]
];


$gameweekOneRows = [

    [
        'player_id' =>
            101,

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
    ],

    [
        'player_id' =>
            102,

        'strength_rating' =>
            79.0,

        'fixture_rating' =>
            70.0,

        'next_fixture_rating' =>
            68.0,

        'base_next_fixture_rating' =>
            72.0,

        'next_opponent_attack_rating' =>
            48.0,

        'next_opponent_defence_rating' =>
            56.0,

        'availability_multiplier' =>
            0.85,

        'actual_points' =>
            0
    ]
];


$gameweekThreeRows = [

    [
        'player_id' =>
            101,

        'strength_rating' =>
            82.0,

        'fixture_rating' =>
            60.0,

        'next_fixture_rating' =>
            55.0,

        'base_next_fixture_rating' =>
            58.0,

        'next_opponent_attack_rating' =>
            62.0,

        'next_opponent_defence_rating' =>
            70.0,

        'availability_multiplier' =>
            1.00,

        'actual_points' =>
            5
    ],

    [
        'player_id' =>
            103,

        'strength_rating' =>
            70.0,

        'fixture_rating' =>
            90.0,

        'next_fixture_rating' =>
            94.0,

        'base_next_fixture_rating' =>
            88.0,

        'next_opponent_attack_rating' =>
            20.0,

        'next_opponent_defence_rating' =>
            12.0,

        'availability_multiplier' =>
            0.95,

        'actual_points' =>
            12
    ]
];


$evaluationResults = [

    1 => [

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            1,

        'historical_rows' =>
            $gameweekOneRows,

        'calibration' =>
            []
    ],

    3 => [

        'entry_id' =>
            2702264,

        'gameweek_id' =>
            3,

        'historical_rows' =>
            $gameweekThreeRows,

        'calibration' =>
            []
    ]
];


$combinedCalibrationResult = [

    'evaluations' => [

        [
            'strength_weight' =>
                0.50,

            'fixture_weight' =>
                0.50,

            'player_scores' =>
                [],

            'metrics' => [

                'total_players' =>
                    4,

                'comparable_players' =>
                    4,

                'unavailable_players' =>
                    0,

                'correlation' =>
                    0.42
            ]
        ]
    ]
];


/*
 * ============================================================
 * A. SERVICE CONTRACT
 * ============================================================
 */

playerIntelligenceCalibrationHistorySection(
    'A. Service Contract'
);


playerIntelligenceCalibrationHistoryAssert(
    class_exists(
        'PlayerIntelligenceWeightCalibrationHistoryService'
    ),
    'PlayerIntelligenceWeightCalibrationHistoryService exists.'
);


if (
    class_exists(
        'PlayerIntelligenceWeightCalibrationHistoryService'
    )
) {

    $reflection =
        new ReflectionClass(
            'PlayerIntelligenceWeightCalibrationHistoryService'
        );


    playerIntelligenceCalibrationHistoryAssert(
        $reflection->hasMethod(
            'evaluate'
        ),
        'PlayerIntelligenceWeightCalibrationHistoryService exposes evaluate().'
    );

} else {

    playerIntelligenceCalibrationHistoryAssert(
        false,
        'PlayerIntelligenceWeightCalibrationHistoryService exposes evaluate().'
    );
}


/*
 * ============================================================
 * STOP AT CONTROLLED INITIAL RED
 * ============================================================
 */

if (
    !class_exists(
        'PlayerIntelligenceWeightCalibrationHistoryService'
    )
) {

    playerIntelligenceCalibrationHistorySummary();

    exit;
}


/*
 * ============================================================
 * B. INVALID ENTRY ID
 * ============================================================
 */

playerIntelligenceCalibrationHistorySection(
    'B. Invalid Entry ID'
);


$repository =
    new PlayerIntelligenceCalibrationHistoryGameweekRepositoryStub(
        $gameweeks
    );


$evidenceService =
    new PlayerIntelligenceCalibrationHistoryEvidenceServiceStub(
        $evidenceByGameweekId
    );


$evaluationService =
    new PlayerIntelligenceCalibrationHistoryEvaluationServiceStub(
        $evaluationResults
    );


$calibrationService =
    new PlayerIntelligenceCalibrationHistoryCalibrationServiceStub(
        $combinedCalibrationResult
    );


$service =
    new PlayerIntelligenceWeightCalibrationHistoryService(
        $repository,
        $evidenceService,
        $evaluationService,
        $calibrationService
    );


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


playerIntelligenceCalibrationHistoryAssert(
    $invalidEntryRejected,
    'Non-positive entry ID is rejected.'
);


/*
 * ============================================================
 * C. GAMEWEEK DISCOVERY
 * ============================================================
 */

playerIntelligenceCalibrationHistorySection(
    'C. Gameweek Discovery'
);


$result =
    $service->evaluate(
        2702264,
        $weightCandidates
    );


playerIntelligenceCalibrationHistoryAssert(
    $repository->calls === 1,
    'Stored gameweeks are loaded exactly once.'
);


playerIntelligenceCalibrationHistoryAssert(
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
        ]
    ],
    'Every stored gameweek is checked through the authoritative evidence boundary in repository order.'
);


/*
 * ============================================================
 * D. ONLY READY GAMEWEEKS ARE EVALUATED
 * ============================================================
 */

playerIntelligenceCalibrationHistorySection(
    'D. Historical Eligibility'
);


playerIntelligenceCalibrationHistoryAssert(
    count(
        $evaluationService->calls
    )
    ===
    2,
    'Only Ready historical gameweeks reach single-gameweek calibration evaluation.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $evaluationService->calls[
            0
        ][
            'gameweek_id'
        ]
        ??
        null
    )
    ===
    1
    &&
    (
        $evaluationService->calls[
            1
        ][
            'gameweek_id'
        ]
        ??
        null
    )
    ===
    3,
    'Ready gameweeks preserve repository/FPL order.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $evaluationService->calls[
            0
        ][
            'weight_candidates'
        ]
        ??
        null
    )
    ===
    $weightCandidates
    &&
    (
        $evaluationService->calls[
            1
        ][
            'weight_candidates'
        ]
        ??
        null
    )
    ===
    $weightCandidates,
    'Explicit weight candidates are forwarded unchanged to each eligible gameweek evaluation.'
);


/*
 * ============================================================
 * E. COMBINED HISTORICAL SAMPLE
 * ============================================================
 */

playerIntelligenceCalibrationHistorySection(
    'E. Combined Historical Sample'
);


playerIntelligenceCalibrationHistoryAssert(
    count(
        $calibrationService->calls
    )
    ===
    1,
    'Combined historical sample is calibrated exactly once.'
);


$combinedCall =
    $calibrationService->calls[
        0
    ]
    ??
    [];


$combinedRows =
    $combinedCall[
        'backtest_rows'
    ]
    ??
    [];


playerIntelligenceCalibrationHistoryAssert(
    count(
        $combinedRows
    )
    ===
    4,
    'Player evidence from all Ready gameweeks is combined into one calibration sample.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $combinedRows[
            0
        ][
            'gameweek_id'
        ]
        ??
        null
    )
    ===
    1
    &&
    (
        $combinedRows[
            1
        ][
            'gameweek_id'
        ]
        ??
        null
    )
    ===
    1
    &&
    (
        $combinedRows[
            2
        ][
            'gameweek_id'
        ]
        ??
        null
    )
    ===
    3
    &&
    (
        $combinedRows[
            3
        ][
            'gameweek_id'
        ]
        ??
        null
    )
    ===
    3,
    'Combined historical rows preserve their source gameweek identity.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $combinedRows[
            0
        ][
            'player_id'
        ]
        ??
        null
    )
    ===
    101
    &&
    (
        $combinedRows[
            1
        ][
            'player_id'
        ]
        ??
        null
    )
    ===
    102
    &&
    (
        $combinedRows[
            2
        ][
            'player_id'
        ]
        ??
        null
    )
    ===
    101
    &&
    (
        $combinedRows[
            3
        ][
            'player_id'
        ]
        ??
        null
    )
    ===
    103,
    'Combined sample preserves player evidence and ordering within each historical gameweek.'
);

playerIntelligenceCalibrationHistoryAssert(
    (
        $combinedRows[
            0
        ][
            'next_fixture_rating'
        ]
        ??
        null
    )
    ===
    82.0
    &&
    (
        $combinedRows[
            1
        ][
            'next_fixture_rating'
        ]
        ??
        null
    )
    ===
    68.0
    &&
    (
        $combinedRows[
            2
        ][
            'next_fixture_rating'
        ]
        ??
        null
    )
    ===
    55.0
    &&
    (
        $combinedRows[
            3
        ][
            'next_fixture_rating'
        ]
        ??
        null
    )
    ===
    94.0,
    'Combined historical sample preserves position-aware next Fixture evidence unchanged.'
);

playerIntelligenceCalibrationHistoryAssert(
    (
        $combinedRows[
            0
        ][
            'base_next_fixture_rating'
        ]
        ??
        null
    )
    ===
    78.0
    &&
    (
        $combinedRows[
            1
        ][
            'base_next_fixture_rating'
        ]
        ??
        null
    )
    ===
    72.0
    &&
    (
        $combinedRows[
            2
        ][
            'base_next_fixture_rating'
        ]
        ??
        null
    )
    ===
    58.0
    &&
    (
        $combinedRows[
            3
        ][
            'base_next_fixture_rating'
        ]
        ??
        null
    )
    ===
    88.0,
    'Combined historical sample preserves base next Fixture evidence unchanged.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $combinedRows[
            0
        ][
            'next_opponent_attack_rating'
        ]
        ??
        null
    )
    ===
    35.0
    &&
    (
        $combinedRows[
            1
        ][
            'next_opponent_attack_rating'
        ]
        ??
        null
    )
    ===
    48.0
    &&
    (
        $combinedRows[
            2
        ][
            'next_opponent_attack_rating'
        ]
        ??
        null
    )
    ===
    62.0
    &&
    (
        $combinedRows[
            3
        ][
            'next_opponent_attack_rating'
        ]
        ??
        null
    )
    ===
    20.0,
    'Combined historical sample preserves next opponent Attack evidence unchanged.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $combinedRows[
            0
        ][
            'next_opponent_defence_rating'
        ]
        ??
        null
    )
    ===
    28.0
    &&
    (
        $combinedRows[
            1
        ][
            'next_opponent_defence_rating'
        ]
        ??
        null
    )
    ===
    56.0
    &&
    (
        $combinedRows[
            2
        ][
            'next_opponent_defence_rating'
        ]
        ??
        null
    )
    ===
    70.0
    &&
    (
        $combinedRows[
            3
        ][
            'next_opponent_defence_rating'
        ]
        ??
        null
    )
    ===
    12.0,
    'Combined historical sample preserves next opponent Defence evidence unchanged.'
);


/*
 * ============================================================
 * F. AGGREGATE CALIBRATION
 * ============================================================
 */

playerIntelligenceCalibrationHistorySection(
    'F. Aggregate Calibration'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $combinedCall[
            'weight_candidates'
        ]
        ??
        null
    )
    ===
    $weightCandidates,
    'Aggregate calibration receives the exact explicitly supplied weight candidates.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $result[
            'calibration'
        ]
        ??
        null
    )
    ===
    $combinedCalibrationResult,
    'Aggregate calibration result is returned without choosing or ranking a winning candidate.'
);


/*
 * ============================================================
 * G. AUDITABLE GAMEWEEK STATUS
 * ============================================================
 */

playerIntelligenceCalibrationHistorySection(
    'G. Auditable Historical Coverage'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $result[
            'entry_id'
        ]
        ??
        null
    )
    ===
    2702264,
    'History result preserves FPL entry ID.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $result[
            'total_gameweeks'
        ]
        ??
        null
    )
    ===
    4,
    'History result reports the number of stored gameweeks considered.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $result[
            'ready_gameweeks'
        ]
        ??
        null
    )
    ===
    2,
    'History result reports the number of Ready historical gameweeks.'
);


$gameweekResults =
    $result[
        'gameweeks'
    ]
    ??
    [];


playerIntelligenceCalibrationHistoryAssert(
    count(
        $gameweekResults
    )
    ===
    4,
    'History result exposes one audit record for every considered gameweek.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $gameweekResults[
            0
        ][
            'status'
        ]
        ??
        null
    )
    ===
    'Ready'
    &&
    (
        $gameweekResults[
            1
        ][
            'status'
        ]
        ??
        null
    )
    ===
    'Incomplete'
    &&
    (
        $gameweekResults[
            2
        ][
            'status'
        ]
        ??
        null
    )
    ===
    'Ready'
    &&
    (
        $gameweekResults[
            3
        ][
            'status'
        ]
        ??
        null
    )
    ===
    'Unavailable',
    'Per-gameweek audit preserves authoritative evidence statuses.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $gameweekResults[
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
        $gameweekResults[
            3
        ][
            'reason'
        ]
        ??
        null
    )
    ===
    'Gameweek outcomes are not yet authoritative',
    'Skipped gameweeks preserve the authoritative evidence reason.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $result[
            'historical_rows'
        ]
        ??
        null
    )
    ===
    $combinedRows,
    'Combined historical sample is exposed for calibration auditability.'
);


/*
 * ============================================================
 * H. EMPTY GAMEWEEK HISTORY
 * ============================================================
 */

playerIntelligenceCalibrationHistorySection(
    'H. Empty Historical Universe'
);


$emptyRepository =
    new PlayerIntelligenceCalibrationHistoryGameweekRepositoryStub(
        []
    );


$emptyEvidenceService =
    new PlayerIntelligenceCalibrationHistoryEvidenceServiceStub(
        []
    );


$emptyEvaluationService =
    new PlayerIntelligenceCalibrationHistoryEvaluationServiceStub(
        []
    );


$emptyCalibrationResult = [

    'evaluations' =>
        []
];


$emptyCalibrationService =
    new PlayerIntelligenceCalibrationHistoryCalibrationServiceStub(
        $emptyCalibrationResult
    );


$emptyService =
    new PlayerIntelligenceWeightCalibrationHistoryService(
        $emptyRepository,
        $emptyEvidenceService,
        $emptyEvaluationService,
        $emptyCalibrationService
    );


$emptyResult =
    $emptyService->evaluate(
        2702264,
        $weightCandidates
    );


playerIntelligenceCalibrationHistoryAssert(
    $emptyEvidenceService->calls === [],
    'No evidence checks occur when no stored gameweeks exist.'
);


playerIntelligenceCalibrationHistoryAssert(
    $emptyEvaluationService->calls === [],
    'No single-gameweek evaluation occurs when no gameweeks exist.'
);


playerIntelligenceCalibrationHistoryAssert(
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
            'backtest_rows'
        ]
        ??
        null
    )
    ===
    [],
    'Empty historical universe is passed truthfully as an empty aggregate calibration sample.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $emptyResult[
            'total_gameweeks'
        ]
        ??
        null
    )
    ===
    0
    &&
    (
        $emptyResult[
            'ready_gameweeks'
        ]
        ??
        null
    )
    ===
    0
    &&
    (
        $emptyResult[
            'gameweeks'
        ]
        ??
        null
    )
    ===
    []
    &&
    (
        $emptyResult[
            'historical_rows'
        ]
        ??
        null
    )
    ===
    [],
    'Empty historical universe returns an auditable empty result.'
);


/*
 * ============================================================
 * I. MALFORMED STORED GAMEWEEKS
 * ============================================================
 */

playerIntelligenceCalibrationHistorySection(
    'I. Malformed Stored Gameweeks'
);


$malformedGameweeks = [

    $gameweeks[
        0
    ],

    'malformed gameweek',

    [
        'id' =>
            0,

        'fpl_gameweek_id' =>
            99
    ],

    $gameweeks[
        2
    ]
];


$malformedRepository =
    new PlayerIntelligenceCalibrationHistoryGameweekRepositoryStub(
        $malformedGameweeks
    );


$malformedEvidenceService =
    new PlayerIntelligenceCalibrationHistoryEvidenceServiceStub(
        $evidenceByGameweekId
    );


$malformedEvaluationService =
    new PlayerIntelligenceCalibrationHistoryEvaluationServiceStub(
        $evaluationResults
    );


$malformedCalibrationService =
    new PlayerIntelligenceCalibrationHistoryCalibrationServiceStub(
        $combinedCalibrationResult
    );


$malformedService =
    new PlayerIntelligenceWeightCalibrationHistoryService(
        $malformedRepository,
        $malformedEvidenceService,
        $malformedEvaluationService,
        $malformedCalibrationService
    );


$malformedResult =
    $malformedService->evaluate(
        2702264,
        $weightCandidates
    );


playerIntelligenceCalibrationHistoryAssert(
    $malformedEvidenceService->calls
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
                3
        ]
    ],
    'Malformed stored gameweek rows and non-positive local IDs are ignored safely.'
);


playerIntelligenceCalibrationHistoryAssert(
    (
        $malformedResult[
            'total_gameweeks'
        ]
        ??
        null
    )
    ===
    2,
    'Only valid stored gameweek identities count as considered gameweeks.'
);


/*
 * ============================================================
 * J. SOURCE IMMUTABILITY
 * ============================================================
 */

playerIntelligenceCalibrationHistorySection(
    'J. Source Evidence Immutability'
);


$sourceGameweeks =
    $gameweeks;


$sourceEvidence =
    $evidenceByGameweekId;


$sourceEvaluations =
    $evaluationResults;


$sourceWeights =
    $weightCandidates;


$immutabilityRepository =
    new PlayerIntelligenceCalibrationHistoryGameweekRepositoryStub(
        $sourceGameweeks
    );


$immutabilityEvidenceService =
    new PlayerIntelligenceCalibrationHistoryEvidenceServiceStub(
        $sourceEvidence
    );


$immutabilityEvaluationService =
    new PlayerIntelligenceCalibrationHistoryEvaluationServiceStub(
        $sourceEvaluations
    );


$immutabilityCalibrationService =
    new PlayerIntelligenceCalibrationHistoryCalibrationServiceStub(
        $combinedCalibrationResult
    );


$immutabilityService =
    new PlayerIntelligenceWeightCalibrationHistoryService(
        $immutabilityRepository,
        $immutabilityEvidenceService,
        $immutabilityEvaluationService,
        $immutabilityCalibrationService
    );


$immutabilityService->evaluate(
    2702264,
    $sourceWeights
);


playerIntelligenceCalibrationHistoryAssert(
    $sourceGameweeks
    ===
    $gameweeks,
    'History evaluation does not mutate stored gameweek evidence.'
);


playerIntelligenceCalibrationHistoryAssert(
    $sourceEvidence
    ===
    $evidenceByGameweekId,
    'History evaluation does not mutate authoritative evidence results.'
);


playerIntelligenceCalibrationHistoryAssert(
    $sourceEvaluations
    ===
    $evaluationResults,
    'History evaluation does not mutate single-gameweek evaluation evidence.'
);


playerIntelligenceCalibrationHistoryAssert(
    $sourceWeights
    ===
    $weightCandidates,
    'History evaluation does not mutate supplied weight candidates.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

playerIntelligenceCalibrationHistorySummary();