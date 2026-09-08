<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * PLAYER PROJECTION BACKTEST EVALUATION SERVICE TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * This service is the production orchestration boundary for
 * projected-points backtesting.
 *
 * It must:
 *
 * - load the immutable recommendation snapshot
 * - use only its preserved player projection evidence
 * - obtain already-aggregated realised gameweek outcomes
 * - run the existing player-level backtest
 * - run the existing gameweek-level metrics summary
 *
 * It must not:
 *
 * - calculate Expected Points
 * - reconstruct missing historical projections
 * - query live Player Intelligence
 * - query fixture history directly
 * - manufacture missing realised outcomes
 * - persist backtesting results
 * - tune or calibrate the model
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


function playerProjectionBacktestEvaluationAssert(
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


function playerProjectionBacktestEvaluationSection(
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


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class PlayerProjectionBacktestEvaluationSnapshotRepositoryStub
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
            'entry_id' => $entryId,
            'gameweek_id' => $gameweekId
        ];


        return
            $this->snapshot;
    }
}


class PlayerProjectionBacktestEvaluationOutcomeServiceStub
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


class PlayerProjectionBacktestEvaluationBacktestServiceStub
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
        int $gameweekId,
        array $playerProjections,
        array $playerOutcomes
    ): array {

        $this->calls[] = [

            'gameweek_id' =>
                $gameweekId,

            'player_projections' =>
                $playerProjections,

            'player_outcomes' =>
                $playerOutcomes
        ];


        return
            $this->result;
    }
}


class PlayerProjectionBacktestEvaluationMetricsServiceStub
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


    public function summarise(
        array $backtestRows
    ): array {

        $this->calls[] =
            $backtestRows;


        return
            $this->result;
    }
}


class PlayerProjectionBacktestEvaluationIntelligenceScoreMetricsServiceStub
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


    public function summarise(
        array $backtestRows
    ): array {

        $this->calls[] =
            $backtestRows;


        return
            $this->result;
    }
}


/*
 * ============================================================
 * SHARED EVIDENCE
 * ============================================================
 */

$playerProjections = [

    [
        'player_id' =>
            101,

        'fpl_player_id' =>
            1001,

        'name' =>
            'Player One',

        'position' =>
            'MID',

        'projected_points' =>
            7.5,

        'projected_minutes' =>
            90.0,

        'intelligence_score' =>
            82.0,

        'projection_confidence' =>
            0.88,

        'has_projected_points' =>
            true
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

        'projected_points' =>
            5.0,

        'projected_minutes' =>
            75.0,

        'intelligence_score' =>
            70.0,

        'projection_confidence' =>
            0.72,

        'has_projected_points' =>
            true
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
        [],

    'player_projections' =>
        $playerProjections,

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
            90,

        'starts' =>
            1,

        'goals' =>
            1,

        'assists' =>
            0,

        'clean_sheets' =>
            1,

        'bonus' =>
            2
    ],

    [
        'gameweek_id' =>
            5,

        'player_id' =>
            102,

        'fixture_count' =>
            1,

        'total_points' =>
            3,

        'minutes' =>
            70,

        'starts' =>
            1,

        'goals' =>
            0,

        'assists' =>
            1,

        'clean_sheets' =>
            0,

        'bonus' =>
            0
    ]
];


$backtestRows = [

    [
        'gameweek_id' =>
            5,

        'player_id' =>
            101,

        'fpl_player_id' =>
            1001,

        'name' =>
            'Player One',

        'position' =>
            'MID',

        'intelligence_score' =>
            82.0,

        'projected_points' =>
            7.5,

        'actual_points' =>
            10,

        'points_error' =>
            2.5,

        'absolute_points_error' =>
            2.5,

        'projected_minutes' =>
            90.0,

        'actual_minutes' =>
            90,

        'projection_confidence' =>
            0.88,

        'fixture_count' =>
            1
    ],

    [
        'gameweek_id' =>
            5,

        'player_id' =>
            102,

        'fpl_player_id' =>
            1002,

        'name' =>
            'Player Two',

        'position' =>
            'FWD',

        'intelligence_score' =>
            70.0,

        'projected_points' =>
            5.0,

        'actual_points' =>
            3,

        'points_error' =>
            -2.0,

        'absolute_points_error' =>
            2.0,

        'projected_minutes' =>
            75.0,

        'actual_minutes' =>
            70,

        'projection_confidence' =>
            0.72,

        'fixture_count' =>
            1
    ]
];


$metrics = [

    'total_players' =>
        2,

    'comparable_players' =>
        2,

    'unavailable_players' =>
        0,

    'mean_absolute_error' =>
        2.25
];


$intelligenceScoreMetrics = [

    'total_players' =>
        2,

    'comparable_players' =>
        2,

    'unavailable_players' =>
        0,

    'correlation' =>
        1.0
];

/*
 * ============================================================
 * A. SERVICE EXISTS
 * ============================================================
 */

playerProjectionBacktestEvaluationSection(
    'A. Service Contract'
);


if (
    !class_exists(
        'PlayerProjectionBacktestEvaluationService'
    )
) {

    playerProjectionBacktestEvaluationAssert(
        false,
        'PlayerProjectionBacktestEvaluationService exists.'
    );


    echo "<br>";
    echo "============================================<br>";
    echo "Player Projection Backtest Evaluation Service Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "RESULT: TESTS FAILED ❌";

    exit;
}


playerProjectionBacktestEvaluationAssert(
    true,
    'PlayerProjectionBacktestEvaluationService exists.'
);


/*
 * ============================================================
 * B. INVALID ENTRY ID
 * ============================================================
 */

playerProjectionBacktestEvaluationSection(
    'B. Invalid Entry ID'
);


$repository =
    new PlayerProjectionBacktestEvaluationSnapshotRepositoryStub(
        $snapshot
    );


$outcomeService =
    new PlayerProjectionBacktestEvaluationOutcomeServiceStub(
        $outcomes
    );


$backtestService =
    new PlayerProjectionBacktestEvaluationBacktestServiceStub(
        $backtestRows
    );


$metricsService =
    new PlayerProjectionBacktestEvaluationMetricsServiceStub(
        $metrics
    );


$intelligenceScoreMetricsService =
    new PlayerProjectionBacktestEvaluationIntelligenceScoreMetricsServiceStub(
        $intelligenceScoreMetrics
    );


$service =
    new PlayerProjectionBacktestEvaluationService(
        $repository,
        $outcomeService,
        $backtestService,
        $metricsService,
        $intelligenceScoreMetricsService
    );


$invalidEntryRejected =
    false;


try {

    $service->evaluate(
        0,
        5
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidEntryRejected =
        true;
}


playerProjectionBacktestEvaluationAssert(
    $invalidEntryRejected,
    'Non-positive entry ID is rejected.'
);


/*
 * ============================================================
 * C. INVALID GAMEWEEK ID
 * ============================================================
 */

playerProjectionBacktestEvaluationSection(
    'C. Invalid Gameweek ID'
);


$invalidGameweekRejected =
    false;


try {

    $service->evaluate(
        2702264,
        0
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidGameweekRejected =
        true;
}


playerProjectionBacktestEvaluationAssert(
    $invalidGameweekRejected,
    'Non-positive gameweek ID is rejected.'
);


/*
 * ============================================================
 * D. MISSING HISTORICAL SNAPSHOT
 * ============================================================
 */

playerProjectionBacktestEvaluationSection(
    'D. Missing Historical Snapshot'
);


$missingRepository =
    new PlayerProjectionBacktestEvaluationSnapshotRepositoryStub(
        null
    );


$missingOutcomeService =
    new PlayerProjectionBacktestEvaluationOutcomeServiceStub(
        $outcomes
    );


$missingBacktestService =
    new PlayerProjectionBacktestEvaluationBacktestServiceStub(
        $backtestRows
    );


$missingMetricsService =
    new PlayerProjectionBacktestEvaluationMetricsServiceStub(
        $metrics
    );


$missingIntelligenceScoreMetricsService =
    new PlayerProjectionBacktestEvaluationIntelligenceScoreMetricsServiceStub(
        $intelligenceScoreMetrics
    );


$missingService =
    new PlayerProjectionBacktestEvaluationService(
        $missingRepository,
        $missingOutcomeService,
        $missingBacktestService,
        $missingMetricsService,
        $missingIntelligenceScoreMetricsService
    );


$missingResult =
    $missingService->evaluate(
        2702264,
        5
    );


playerProjectionBacktestEvaluationAssert(
    $missingResult === null,
    'Missing immutable recommendation snapshot returns null.'
);


playerProjectionBacktestEvaluationAssert(
    count(
        $missingRepository->calls
    )
    ===
    1,
    'Snapshot repository is queried once for missing historical evidence.'
);


playerProjectionBacktestEvaluationAssert(
    $missingRepository->calls[0]
    ===
    [
        'entry_id' => 2702264,
        'gameweek_id' => 5
    ],
    'Snapshot lookup uses the requested entry and local gameweek IDs.'
);


playerProjectionBacktestEvaluationAssert(
    $missingOutcomeService->calls
    ===
    [],
    'Realised outcomes are not loaded when no historical snapshot exists.'
);


playerProjectionBacktestEvaluationAssert(
    $missingBacktestService->calls
    ===
    [],
    'Player backtest is not run when no historical snapshot exists.'
);


playerProjectionBacktestEvaluationAssert(
    $missingMetricsService->calls
    ===
    [],
    'Metrics are not calculated when no historical snapshot exists.'
);


playerProjectionBacktestEvaluationAssert(
    $missingIntelligenceScoreMetricsService->calls
    ===
    [],
    'Intelligence Score metrics are not calculated when no historical snapshot exists.'
);


/*
 * ============================================================
 * E. COMPLETE ORCHESTRATION
 * ============================================================
 */

playerProjectionBacktestEvaluationSection(
    'E. Complete Orchestration'
);


$repository =
    new PlayerProjectionBacktestEvaluationSnapshotRepositoryStub(
        $snapshot
    );


$outcomeService =
    new PlayerProjectionBacktestEvaluationOutcomeServiceStub(
        $outcomes
    );


$backtestService =
    new PlayerProjectionBacktestEvaluationBacktestServiceStub(
        $backtestRows
    );


$metricsService =
    new PlayerProjectionBacktestEvaluationMetricsServiceStub(
        $metrics
    );


$intelligenceScoreMetricsService =
    new PlayerProjectionBacktestEvaluationIntelligenceScoreMetricsServiceStub(
        $intelligenceScoreMetrics
    );


$service =
    new PlayerProjectionBacktestEvaluationService(
        $repository,
        $outcomeService,
        $backtestService,
        $metricsService,
        $intelligenceScoreMetricsService
    );


$result =
    $service->evaluate(
        2702264,
        5
    );


playerProjectionBacktestEvaluationAssert(
    count(
        $repository->calls
    )
    ===
    1,
    'Immutable snapshot repository is queried exactly once.'
);


playerProjectionBacktestEvaluationAssert(
    $repository->calls[0]
    ===
    [
        'entry_id' => 2702264,
        'gameweek_id' => 5
    ],
    'Snapshot repository receives the requested entry and gameweek.'
);


playerProjectionBacktestEvaluationAssert(
    $outcomeService->calls
    ===
    [
        5
    ],
    'Realised outcome service receives the requested local gameweek ID.'
);


playerProjectionBacktestEvaluationAssert(
    count(
        $backtestService->calls
    )
    ===
    1,
    'Player projection backtest runs exactly once.'
);


playerProjectionBacktestEvaluationAssert(
    $backtestService->calls[0][
        'gameweek_id'
    ]
    ===
    5,
    'Player projection backtest receives the requested gameweek.'
);


playerProjectionBacktestEvaluationAssert(
    $backtestService->calls[0][
        'player_projections'
    ]
    ===
    $playerProjections,
    'Player projection backtest receives exact immutable projection evidence from the snapshot.'
);


playerProjectionBacktestEvaluationAssert(
    $backtestService->calls[0][
        'player_outcomes'
    ]
    ===
    $outcomes,
    'Player projection backtest receives realised outcomes unchanged.'
);


playerProjectionBacktestEvaluationAssert(
    count(
        $metricsService->calls
    )
    ===
    1,
    'Gameweek-level metrics are calculated exactly once.'
);


playerProjectionBacktestEvaluationAssert(
    $metricsService->calls[0]
    ===
    $backtestRows,
    'Metrics service receives exact player-level backtest evidence.'
);


playerProjectionBacktestEvaluationAssert(
    count(
        $intelligenceScoreMetricsService->calls
    )
    ===
    1,
    'Intelligence Score metrics are calculated exactly once.'
);


playerProjectionBacktestEvaluationAssert(
    (
        $intelligenceScoreMetricsService->calls[0]
        ?? null
    )
    ===
    $backtestRows,
    'Intelligence Score metrics receive exact player-level backtest evidence.'
);


/*
 * ============================================================
 * F. OUTPUT CONTRACT
 * ============================================================
 */

playerProjectionBacktestEvaluationSection(
    'F. Output Contract'
);


playerProjectionBacktestEvaluationAssert(
    is_array(
        $result
    ),
    'Successful evaluation returns an array.'
);


playerProjectionBacktestEvaluationAssert(
    array_keys(
        $result
    )
    ===
    [
        'entry_id',
        'gameweek_id',
        'snapshot',
        'player_outcomes',
        'player_backtest',
        'metrics',
        'intelligence_score_metrics'
    ],
    'Evaluation exposes only the defined historical backtest orchestration contract.'
);


playerProjectionBacktestEvaluationAssert(
    $result[
        'entry_id'
    ]
    ===
    2702264,
    'Evaluation preserves requested entry ID.'
);


playerProjectionBacktestEvaluationAssert(
    $result[
        'gameweek_id'
    ]
    ===
    5,
    'Evaluation preserves requested local gameweek ID.'
);


playerProjectionBacktestEvaluationAssert(
    $result[
        'snapshot'
    ]
    ===
    $snapshot,
    'Evaluation returns the exact immutable historical snapshot.'
);


playerProjectionBacktestEvaluationAssert(
    $result[
        'player_outcomes'
    ]
    ===
    $outcomes,
    'Evaluation returns realised player outcomes unchanged.'
);


playerProjectionBacktestEvaluationAssert(
    $result[
        'player_backtest'
    ]
    ===
    $backtestRows,
    'Evaluation returns player-level backtest evidence unchanged.'
);


playerProjectionBacktestEvaluationAssert(
    $result[
        'metrics'
    ]
    ===
    $metrics,
    'Evaluation returns gameweek-level metrics unchanged.'
);


playerProjectionBacktestEvaluationAssert(
    array_key_exists(
        'intelligence_score_metrics',
        $result
    ),
    'Evaluation exposes Intelligence Score backtest metrics.'
);


playerProjectionBacktestEvaluationAssert(
    (
        $result[
            'intelligence_score_metrics'
        ]
        ?? null
    )
    ===
    $intelligenceScoreMetrics,
    'Evaluation returns Intelligence Score metrics unchanged.'
);


/*
 * ============================================================
 * G. SNAPSHOT PROJECTION EVIDENCE IS NOT RECONSTRUCTED
 * ============================================================
 */

playerProjectionBacktestEvaluationSection(
    'G. Historical Evidence Preservation'
);


$snapshotWithUnavailableProjection =
    $snapshot;


$snapshotWithUnavailableProjection[
    'player_projections'
][0][
    'projected_points'
] =
    null;


$snapshotWithUnavailableProjection[
    'player_projections'
][0][
    'has_projected_points'
] =
    false;


$preservationRepository =
    new PlayerProjectionBacktestEvaluationSnapshotRepositoryStub(
        $snapshotWithUnavailableProjection
    );


$preservationOutcomeService =
    new PlayerProjectionBacktestEvaluationOutcomeServiceStub(
        $outcomes
    );


$preservationBacktestService =
    new PlayerProjectionBacktestEvaluationBacktestServiceStub(
        $backtestRows
    );


$preservationMetricsService =
    new PlayerProjectionBacktestEvaluationMetricsServiceStub(
        $metrics
    );


$preservationIntelligenceScoreMetricsService =
    new PlayerProjectionBacktestEvaluationIntelligenceScoreMetricsServiceStub(
        $intelligenceScoreMetrics
    );


$preservationService =
    new PlayerProjectionBacktestEvaluationService(
        $preservationRepository,
        $preservationOutcomeService,
        $preservationBacktestService,
        $preservationMetricsService,
        $preservationIntelligenceScoreMetricsService
    );


$preservationService->evaluate(
    2702264,
    5
);


playerProjectionBacktestEvaluationAssert(
    $preservationBacktestService->calls[0][
        'player_projections'
    ]
    ===
    $snapshotWithUnavailableProjection[
        'player_projections'
    ],
    'Unavailable historical projection evidence is passed through unchanged rather than reconstructed.'
);


/*
 * ============================================================
 * H. SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

playerProjectionBacktestEvaluationSection(
    'H. Source Evidence Remains Unchanged'
);


$sourceSnapshot =
    $snapshot;


$sourceOutcomes =
    $outcomes;


$sourceSnapshotBefore =
    $sourceSnapshot;


$sourceOutcomesBefore =
    $sourceOutcomes;


$immutabilityRepository =
    new PlayerProjectionBacktestEvaluationSnapshotRepositoryStub(
        $sourceSnapshot
    );


$immutabilityOutcomeService =
    new PlayerProjectionBacktestEvaluationOutcomeServiceStub(
        $sourceOutcomes
    );


$immutabilityBacktestService =
    new PlayerProjectionBacktestEvaluationBacktestServiceStub(
        $backtestRows
    );


$immutabilityMetricsService =
    new PlayerProjectionBacktestEvaluationMetricsServiceStub(
        $metrics
    );


$immutabilityIntelligenceScoreMetricsService =
    new PlayerProjectionBacktestEvaluationIntelligenceScoreMetricsServiceStub(
        $intelligenceScoreMetrics
    );


$immutabilityService =
    new PlayerProjectionBacktestEvaluationService(
        $immutabilityRepository,
        $immutabilityOutcomeService,
        $immutabilityBacktestService,
        $immutabilityMetricsService,
        $immutabilityIntelligenceScoreMetricsService
    );


$immutabilityService->evaluate(
    2702264,
    5
);


playerProjectionBacktestEvaluationAssert(
    $sourceSnapshot
    ===
    $sourceSnapshotBefore,
    'Historical recommendation snapshot is not mutated.'
);


playerProjectionBacktestEvaluationAssert(
    $sourceOutcomes
    ===
    $sourceOutcomesBefore,
    'Realised outcome evidence is not mutated.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Projection Backtest Evaluation Service Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}