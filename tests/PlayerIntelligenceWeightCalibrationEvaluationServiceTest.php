<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * PLAYER INTELLIGENCE WEIGHT CALIBRATION EVALUATION TEST
 * ============================================================
 *
 * v0.36.0 — Model Calibration & Intelligence Quality
 *
 * This service is the production orchestration boundary between:
 *
 * - immutable recommendation-time Player Ranking evidence
 * - realised completed-gameweek player outcomes
 * - PlayerIntelligenceWeightCalibrationService
 *
 * It must not:
 *
 * - reconstruct historical Player Intelligence
 * - query live Player Intelligence
 * - calculate candidate Intelligence Scores itself
 * - query fixture history directly
 * - manufacture missing realised outcomes
 * - choose or rank a winning weight combination
 * - modify production model weights
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


function playerIntelligenceCalibrationEvaluationAssert(
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


function playerIntelligenceCalibrationEvaluationSection(
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


function playerIntelligenceCalibrationEvaluationSummary(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Player Intelligence Weight Calibration Evaluation Service Test Summary<br>";
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

class PlayerIntelligenceCalibrationEvaluationSnapshotRepositoryStub
{
    public ?array $snapshot;

    public array $calls =
        [];


    public function __construct(
        ?array $snapshot
    ) {

        $this->snapshot =
            $snapshot;
    }


    public function getByEntryAndGameweek(
        int $entryId,
        int $gameweekId
    ): ?array {

        $this->calls[] = [

            'entry_id' =>
                $entryId,

            'gameweek_id' =>
                $gameweekId
        ];


        return
            $this->snapshot;
    }
}


class PlayerIntelligenceCalibrationEvaluationOutcomeServiceStub
{
    public array $outcomes;

    public array $calls =
        [];


    public function __construct(
        array $outcomes
    ) {

        $this->outcomes =
            $outcomes;
    }


    public function getByGameweekId(
        int $gameweekId
    ): array {

        $this->calls[] =
            $gameweekId;


        return
            $this->outcomes;
    }
}


class PlayerIntelligenceCalibrationEvaluationCalibrationServiceStub
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
 * CONTROLLED HISTORICAL EVIDENCE
 * ============================================================
 */

$playerRankings = [

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

        'availability_multiplier' =>
            0.95,

        'intelligence_score' =>
            82.5,

        'rank' =>
            1
    ],

    [
        'player_id' =>
            102,

        'fpl_player_id' =>
            1002,

        'name' =>
            'Player Two',

        'position' =>
            'FWD',

        'strength_rating' =>
            79.0,

        'fixture_rating' =>
            70.0,

        'availability_multiplier' =>
            0.85,

        'intelligence_score' =>
            76.0,

        'rank' =>
            2
    ],

    [
        'player_id' =>
            103,

        'fpl_player_id' =>
            1003,

        'name' =>
            'Player Three',

        'position' =>
            'DEF',

        'strength_rating' =>
            null,

        'fixture_rating' =>
            65.0,

        'availability_multiplier' =>
            1.00,

        'intelligence_score' =>
            61.0,

        'rank' =>
            3
    ]
];


$snapshot = [

    'id' =>
        500,

    'gameweek_id' =>
        5,

    'entry_id' =>
        2702264,

    'captured_at' =>
        '2026-08-21 17:15:00',

    'deadline_time' =>
        '2026-08-21 17:30:00',

    'player_rankings' =>
        $playerRankings,

    'player_projections' =>
        [],

    'starting_xi' =>
        [],

    'captain_recommendation' =>
        [],

    'transfer_recommendations' =>
        [],

    'gameweek_decision' =>
        [],

    'chip_recommendations' =>
        []
];


$outcomes = [

    [
        'gameweek_id' =>
            5,

        'player_id' =>
            101,

        'fixture_count' =>
            1,

        'total_points' =>
            10,

        'minutes' =>
            90
    ],

    [
        'gameweek_id' =>
            5,

        'player_id' =>
            102,

        'fixture_count' =>
            2,

        'total_points' =>
            0,

        'minutes' =>
            135
    ]

    /*
     * Player 103 deliberately has no realised outcome.
     */
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
    ]
];


$calibrationResult = [

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
                    3,

                'comparable_players' =>
                    2,

                'unavailable_players' =>
                    1,

                'correlation' =>
                    0.75
            ]
        ]
    ]
];


/*
 * ============================================================
 * A. SERVICE CONTRACT
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'A. Service Contract'
);


playerIntelligenceCalibrationEvaluationAssert(
    class_exists(
        'PlayerIntelligenceWeightCalibrationEvaluationService'
    ),
    'PlayerIntelligenceWeightCalibrationEvaluationService exists.'
);


if (
    class_exists(
        'PlayerIntelligenceWeightCalibrationEvaluationService'
    )
) {

    $reflection =
        new ReflectionClass(
            'PlayerIntelligenceWeightCalibrationEvaluationService'
        );


    playerIntelligenceCalibrationEvaluationAssert(
        $reflection->hasMethod(
            'evaluate'
        ),
        'PlayerIntelligenceWeightCalibrationEvaluationService exposes evaluate().'
    );

} else {

    playerIntelligenceCalibrationEvaluationAssert(
        false,
        'PlayerIntelligenceWeightCalibrationEvaluationService exposes evaluate().'
    );
}


/*
 * ============================================================
 * STOP AT CONTROLLED INITIAL RED
 * ============================================================
 */

if (
    !class_exists(
        'PlayerIntelligenceWeightCalibrationEvaluationService'
    )
) {

    playerIntelligenceCalibrationEvaluationSummary();

    exit;
}


/*
 * ============================================================
 * B. INVALID ENTRY ID
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'B. Invalid Entry ID'
);


$repository =
    new PlayerIntelligenceCalibrationEvaluationSnapshotRepositoryStub(
        $snapshot
    );


$outcomeService =
    new PlayerIntelligenceCalibrationEvaluationOutcomeServiceStub(
        $outcomes
    );


$calibrationService =
    new PlayerIntelligenceCalibrationEvaluationCalibrationServiceStub(
        $calibrationResult
    );


$service =
    new PlayerIntelligenceWeightCalibrationEvaluationService(
        $repository,
        $outcomeService,
        $calibrationService
    );


$invalidEntryRejected =
    false;


try {

    $service->evaluate(
        0,
        5,
        $weightCandidates
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidEntryRejected =
        true;
}


playerIntelligenceCalibrationEvaluationAssert(
    $invalidEntryRejected,
    'Non-positive entry ID is rejected.'
);


/*
 * ============================================================
 * C. INVALID GAMEWEEK ID
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'C. Invalid Gameweek ID'
);


$invalidGameweekRejected =
    false;


try {

    $service->evaluate(
        2702264,
        0,
        $weightCandidates
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidGameweekRejected =
        true;
}


playerIntelligenceCalibrationEvaluationAssert(
    $invalidGameweekRejected,
    'Non-positive gameweek ID is rejected.'
);


/*
 * ============================================================
 * D. MISSING HISTORICAL SNAPSHOT
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'D. Missing Historical Snapshot'
);


$missingRepository =
    new PlayerIntelligenceCalibrationEvaluationSnapshotRepositoryStub(
        null
    );


$missingOutcomeService =
    new PlayerIntelligenceCalibrationEvaluationOutcomeServiceStub(
        $outcomes
    );


$missingCalibrationService =
    new PlayerIntelligenceCalibrationEvaluationCalibrationServiceStub(
        $calibrationResult
    );


$missingService =
    new PlayerIntelligenceWeightCalibrationEvaluationService(
        $missingRepository,
        $missingOutcomeService,
        $missingCalibrationService
    );


$missingResult =
    $missingService->evaluate(
        2702264,
        5,
        $weightCandidates
    );


playerIntelligenceCalibrationEvaluationAssert(
    $missingResult === null,
    'Missing immutable recommendation snapshot returns null.'
);


playerIntelligenceCalibrationEvaluationAssert(
    $missingRepository->calls
    ===
    [
        [
            'entry_id' =>
                2702264,

            'gameweek_id' =>
                5
        ]
    ],
    'Snapshot lookup uses the requested entry and local gameweek IDs.'
);


playerIntelligenceCalibrationEvaluationAssert(
    $missingOutcomeService->calls
    ===
    [],
    'Realised outcomes are not loaded when no historical snapshot exists.'
);


playerIntelligenceCalibrationEvaluationAssert(
    $missingCalibrationService->calls
    ===
    [],
    'Calibration is not run when no historical snapshot exists.'
);


/*
 * ============================================================
 * E. IMMUTABLE PLAYER RANKINGS ARE THE CALIBRATION SOURCE
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'E. Immutable Player Ranking Evidence'
);


$result =
    $service->evaluate(
        2702264,
        5,
        $weightCandidates
    );


playerIntelligenceCalibrationEvaluationAssert(
    $repository->calls
    ===
    [
        [
            'entry_id' =>
                2702264,

            'gameweek_id' =>
                5
        ]
    ],
    'Evaluation loads the immutable recommendation snapshot once.'
);


playerIntelligenceCalibrationEvaluationAssert(
    $outcomeService->calls
    ===
    [
        5
    ],
    'Evaluation requests already-aggregated realised outcomes for the local gameweek.'
);


playerIntelligenceCalibrationEvaluationAssert(
    count(
        $calibrationService->calls
    )
    ===
    1,
    'Calibration service is invoked exactly once.'
);


$calibrationCall =
    $calibrationService->calls[
        0
    ]
    ??
    [];


$historicalRows =
    $calibrationCall[
        'backtest_rows'
    ]
    ??
    [];


playerIntelligenceCalibrationEvaluationAssert(
    count(
        $historicalRows
    )
    ===
    3,
    'Historical calibration rows preserve recommendation ranking membership.'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $historicalRows[
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
        $historicalRows[
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
        $historicalRows[
            2
        ][
            'player_id'
        ]
        ??
        null
    )
    ===
    103,
    'Historical calibration rows preserve recommendation ranking order.'
);


/*
 * ============================================================
 * F. CALIBRATION COMPONENTS ARE COPIED FROM SNAPSHOT
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'F. Preserved Calibration Components'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $historicalRows[
            0
        ][
            'strength_rating'
        ]
        ??
        null
    )
    ===
    86.0,
    'Historical Strength rating comes from immutable ranking evidence.'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $historicalRows[
            0
        ][
            'fixture_rating'
        ]
        ??
        null
    )
    ===
    76.0,
    'Historical Fixture rating comes from immutable ranking evidence.'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $historicalRows[
            0
        ][
            'availability_multiplier'
        ]
        ??
        null
    )
    ===
    0.95,
    'Historical Availability multiplier comes from immutable ranking evidence.'
);


playerIntelligenceCalibrationEvaluationAssert(
    array_key_exists(
        'strength_rating',
        $historicalRows[
            2
        ]
        ??
        []
    )
    &&
    $historicalRows[
        2
    ][
        'strength_rating'
    ]
    ===
    null,
    'Missing historical Strength evidence remains null rather than being reconstructed.'
);


/*
 * ============================================================
 * G. REALISED RETURNS ARE JOINED BY LOCAL PLAYER ID
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'G. Realised Outcome Join'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $historicalRows[
            0
        ][
            'actual_points'
        ]
        ??
        null
    )
    ===
    10,
    'Realised FPL points are joined to the matching local player ID.'
);


playerIntelligenceCalibrationEvaluationAssert(
    array_key_exists(
        'actual_points',
        $historicalRows[
            1
        ]
        ??
        []
    )
    &&
    $historicalRows[
        1
    ][
        'actual_points'
    ]
    ===
    0,
    'Genuine zero realised FPL points are preserved.'
);


playerIntelligenceCalibrationEvaluationAssert(
    array_key_exists(
        'actual_points',
        $historicalRows[
            2
        ]
        ??
        []
    )
    &&
    $historicalRows[
        2
    ][
        'actual_points'
    ]
    ===
    null,
    'Missing realised player outcome remains null rather than being manufactured as zero.'
);


/*
 * ============================================================
 * H. NON-CALIBRATION RANKING DATA IS NOT RECONSTRUCTED
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'H. Historical Identity Evidence'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $historicalRows[
            0
        ][
            'fpl_player_id'
        ]
        ??
        null
    )
    ===
    1001
    &&
    (
        $historicalRows[
            0
        ][
            'name'
        ]
        ??
        null
    )
    ===
    'Player One'
    &&
    (
        $historicalRows[
            0
        ][
            'position'
        ]
        ??
        null
    )
    ===
    'MID',
    'Useful preserved player identity accompanies calibration evidence.'
);


/*
 * ============================================================
 * I. WEIGHT CANDIDATES ARE FORWARDED UNCHANGED
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'I. Weight Candidate Delegation'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $calibrationCall[
            'weight_candidates'
        ]
        ??
        null
    )
    ===
    $weightCandidates,
    'Explicitly supplied weight candidates are forwarded unchanged.'
);


/*
 * ============================================================
 * J. CALIBRATION RESULT IS RETURNED WITHOUT REINTERPRETATION
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'J. Calibration Result'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $result[
            'calibration'
        ]
        ??
        null
    )
    ===
    $calibrationResult,
    'Calibration service result is returned without ranking or reinterpretation.'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $result[
            'entry_id'
        ]
        ??
        null
    )
    ===
    2702264,
    'Evaluation result preserves FPL entry ID.'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $result[
            'gameweek_id'
        ]
        ??
        null
    )
    ===
    5,
    'Evaluation result preserves local gameweek ID.'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $result[
            'snapshot'
        ]
        ??
        null
    )
    ===
    $snapshot,
    'Evaluation result preserves the immutable source snapshot.'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $result[
            'player_outcomes'
        ]
        ??
        null
    )
    ===
    $outcomes,
    'Evaluation result preserves the realised outcome evidence.'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $result[
            'historical_rows'
        ]
        ??
        null
    )
    ===
    $historicalRows,
    'Evaluation exposes the joined historical calibration rows for auditability.'
);


/*
 * ============================================================
 * K. LEGACY SNAPSHOT WITHOUT RANKING EVIDENCE
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'K. Missing Historical Ranking Evidence'
);


$legacySnapshot =
    $snapshot;


$legacySnapshot[
    'player_rankings'
] =
    [];


$legacyRepository =
    new PlayerIntelligenceCalibrationEvaluationSnapshotRepositoryStub(
        $legacySnapshot
    );


$legacyOutcomeService =
    new PlayerIntelligenceCalibrationEvaluationOutcomeServiceStub(
        $outcomes
    );


$legacyCalibrationResult = [

    'evaluations' =>
        []
];


$legacyCalibrationService =
    new PlayerIntelligenceCalibrationEvaluationCalibrationServiceStub(
        $legacyCalibrationResult
    );


$legacyService =
    new PlayerIntelligenceWeightCalibrationEvaluationService(
        $legacyRepository,
        $legacyOutcomeService,
        $legacyCalibrationService
    );


$legacyResult =
    $legacyService->evaluate(
        2702264,
        5,
        $weightCandidates
    );


playerIntelligenceCalibrationEvaluationAssert(
    count(
        $legacyCalibrationService->calls
    )
    ===
    1,
    'Legacy snapshot with no ranking evidence still reaches calibration truthfully.'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $legacyCalibrationService->calls[
            0
        ][
            'backtest_rows'
        ]
        ??
        null
    )
    ===
    [],
    'Missing historical ranking evidence is passed as an empty calibration sample.'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $legacyResult[
            'historical_rows'
        ]
        ??
        null
    )
    ===
    [],
    'Legacy snapshot does not cause historical ranking evidence to be reconstructed.'
);


/*
 * ============================================================
 * L. EMPTY REALISED OUTCOMES
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'L. Missing Realised Outcomes'
);


$noOutcomeRepository =
    new PlayerIntelligenceCalibrationEvaluationSnapshotRepositoryStub(
        $snapshot
    );


$noOutcomeService =
    new PlayerIntelligenceCalibrationEvaluationOutcomeServiceStub(
        []
    );


$noOutcomeCalibrationService =
    new PlayerIntelligenceCalibrationEvaluationCalibrationServiceStub(
        $calibrationResult
    );


$noOutcomeEvaluationService =
    new PlayerIntelligenceWeightCalibrationEvaluationService(
        $noOutcomeRepository,
        $noOutcomeService,
        $noOutcomeCalibrationService
    );


$noOutcomeResult =
    $noOutcomeEvaluationService->evaluate(
        2702264,
        5,
        $weightCandidates
    );


$noOutcomeRows =
    $noOutcomeResult[
        'historical_rows'
    ]
    ??
    [];


playerIntelligenceCalibrationEvaluationAssert(
    count(
        $noOutcomeRows
    )
    ===
    3,
    'Missing realised outcomes do not remove preserved historical ranking evidence.'
);


playerIntelligenceCalibrationEvaluationAssert(
    array_key_exists(
        'actual_points',
        $noOutcomeRows[
            0
        ]
        ??
        []
    )
    &&
    $noOutcomeRows[
        0
    ][
        'actual_points'
    ]
    ===
    null
    &&
    $noOutcomeRows[
        1
    ][
        'actual_points'
    ]
    ===
    null
    &&
    $noOutcomeRows[
        2
    ][
        'actual_points'
    ]
    ===
    null,
    'Unavailable realised returns remain null for every historical ranking row.'
);


/*
 * ============================================================
 * M. MALFORMED RANKING AND OUTCOME EVIDENCE
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'M. Malformed Historical Evidence'
);


$malformedSnapshot =
    $snapshot;


$malformedSnapshot[
    'player_rankings'
] = [

    $playerRankings[
        0
    ],

    'malformed ranking',

    [
        'player_id' =>
            0,

        'strength_rating' =>
            50.0,

        'fixture_rating' =>
            50.0,

        'availability_multiplier' =>
            1.0
    ],

    $playerRankings[
        1
    ]
];


$malformedOutcomes = [

    'malformed outcome',

    [
        'player_id' =>
            0,

        'total_points' =>
            100
    ],

    [
        'player_id' =>
            101,

        'total_points' =>
            10
    ],

    [
        'player_id' =>
            102,

        'total_points' =>
            0
    ]
];


$malformedRepository =
    new PlayerIntelligenceCalibrationEvaluationSnapshotRepositoryStub(
        $malformedSnapshot
    );


$malformedOutcomeService =
    new PlayerIntelligenceCalibrationEvaluationOutcomeServiceStub(
        $malformedOutcomes
    );


$malformedCalibrationService =
    new PlayerIntelligenceCalibrationEvaluationCalibrationServiceStub(
        $calibrationResult
    );


$malformedService =
    new PlayerIntelligenceWeightCalibrationEvaluationService(
        $malformedRepository,
        $malformedOutcomeService,
        $malformedCalibrationService
    );


$malformedResult =
    $malformedService->evaluate(
        2702264,
        5,
        $weightCandidates
    );


$malformedHistoricalRows =
    $malformedResult[
        'historical_rows'
    ]
    ??
    [];


playerIntelligenceCalibrationEvaluationAssert(
    count(
        $malformedHistoricalRows
    )
    ===
    2,
    'Malformed ranking rows and non-positive player identities are ignored.'
);


playerIntelligenceCalibrationEvaluationAssert(
    (
        $malformedHistoricalRows[
            0
        ][
            'actual_points'
        ]
        ??
        null
    )
    ===
    10
    &&
    array_key_exists(
        'actual_points',
        $malformedHistoricalRows[
            1
        ]
        ??
        []
    )
    &&
    $malformedHistoricalRows[
        1
    ][
        'actual_points'
    ]
    ===
    0,
    'Malformed outcome rows cannot corrupt valid player outcome joins.'
);


/*
 * ============================================================
 * N. SOURCE EVIDENCE IS NOT MUTATED
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSection(
    'N. Source Evidence Immutability'
);


$sourceSnapshot =
    $snapshot;


$sourceOutcomes =
    $outcomes;


$sourceWeights =
    $weightCandidates;


$immutabilityRepository =
    new PlayerIntelligenceCalibrationEvaluationSnapshotRepositoryStub(
        $sourceSnapshot
    );


$immutabilityOutcomeService =
    new PlayerIntelligenceCalibrationEvaluationOutcomeServiceStub(
        $sourceOutcomes
    );


$immutabilityCalibrationService =
    new PlayerIntelligenceCalibrationEvaluationCalibrationServiceStub(
        $calibrationResult
    );


$immutabilityService =
    new PlayerIntelligenceWeightCalibrationEvaluationService(
        $immutabilityRepository,
        $immutabilityOutcomeService,
        $immutabilityCalibrationService
    );


$immutabilityService->evaluate(
    2702264,
    5,
    $sourceWeights
);


playerIntelligenceCalibrationEvaluationAssert(
    $sourceSnapshot
    ===
    $snapshot,
    'Evaluation does not mutate immutable snapshot evidence.'
);


playerIntelligenceCalibrationEvaluationAssert(
    $sourceOutcomes
    ===
    $outcomes,
    'Evaluation does not mutate realised outcome evidence.'
);


playerIntelligenceCalibrationEvaluationAssert(
    $sourceWeights
    ===
    $weightCandidates,
    'Evaluation does not mutate supplied weight candidates.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

playerIntelligenceCalibrationEvaluationSummary();