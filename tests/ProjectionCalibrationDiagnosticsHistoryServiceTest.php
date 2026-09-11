<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Projection Calibration Diagnostics History Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function projectionDiagnosticsHistoryTestResult(
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


function projectionDiagnosticsHistoryTestThrows(
    callable $callback,
    string $expectedClass,
    string $message
): void {

    try {

        $callback();

    } catch (
        Throwable $exception
    ) {

        projectionDiagnosticsHistoryTestResult(
            $exception
                instanceof
                $expectedClass,
            $message
        );

        return;
    }


    projectionDiagnosticsHistoryTestResult(
        false,
        $message
    );
}


/*
 * ============================================================
 * CONTROLLED DEPENDENCIES
 * ============================================================
 */

class ProjectionDiagnosticsHistoryGameweekRepository
{
    private array $gameweeks;

    public int $getAllCalls = 0;


    public function __construct(
        array $gameweeks = []
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


class ProjectionDiagnosticsHistoryEvidenceService
{
    private array $evidenceByGameweek;

    public array $requests = [];


    public function __construct(
        array $evidenceByGameweek = []
    ) {

        $this->evidenceByGameweek =
            $evidenceByGameweek;
    }


    public function getEvidence(
        int $entryId,
        int $gameweekId
    ): array {

        $this->requests[] = [
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


class ProjectionDiagnosticsHistoryProjectionService
{
    private array $resultsByGameweek;

    public array $requests = [];


    public function __construct(
        array $resultsByGameweek = []
    ) {

        $this->resultsByGameweek =
            $resultsByGameweek;
    }


    public function evaluate(
        array $evidence
    ): array {

        $gameweekId =
            (int) (
                $evidence[
                    'gameweek_id'
                ]
                ?? 0
            );


        $this->requests[] =
            $gameweekId;


        return
            $this->resultsByGameweek[
                $gameweekId
            ]
            ?? [
                'status' => 'Ready',
                'player_evaluations' => []
            ];
    }
}


class ProjectionDiagnosticsHistoryDiagnosticsService
{
    public array $requests = [];


    public function evaluate(
        array $evaluations
    ): array {

        $this->requests[] =
            $evaluations;


        return [
            'diagnostic_sample_size' =>
                count(
                    $evaluations
                )
        ];
    }
}


/*
 * ============================================================
 * SERVICE AVAILABILITY
 * ============================================================
 */

projectionDiagnosticsHistoryTestResult(
    class_exists(
        'ProjectionCalibrationDiagnosticsHistoryService'
    ),
    'ProjectionCalibrationDiagnosticsHistoryService exists.'
);


if (
    !class_exists(
        'ProjectionCalibrationDiagnosticsHistoryService'
    )
) {

    echo "<br>";
    echo "============================================<br>";
    echo "TEST SUMMARY<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "RESULT: TESTS FAILED ❌<br>";

    exit;
}


/*
 * ============================================================
 * SCENARIO A
 * CONSTRUCTION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Construction<br>";
echo "============================================<br>";


$gameweekRepository =
    new ProjectionDiagnosticsHistoryGameweekRepository();


$evidenceService =
    new ProjectionDiagnosticsHistoryEvidenceService();


$projectionService =
    new ProjectionDiagnosticsHistoryProjectionService();


$diagnosticsService =
    new ProjectionDiagnosticsHistoryDiagnosticsService();


$service =
    new ProjectionCalibrationDiagnosticsHistoryService(
        $gameweekRepository,
        $evidenceService,
        $projectionService,
        $diagnosticsService
    );


projectionDiagnosticsHistoryTestResult(
    $service
        instanceof
        ProjectionCalibrationDiagnosticsHistoryService,
    'ProjectionCalibrationDiagnosticsHistoryService can be constructed.'
);


/*
 * ============================================================
 * SCENARIO B
 * INVALID ENTRY ID
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Invalid Entry Identity<br>";
echo "============================================<br>";


foreach (
    [
        0,
        -1,
        -999
    ]
    as $invalidEntryId
) {

    projectionDiagnosticsHistoryTestThrows(
        static function () use (
            $service,
            $invalidEntryId
        ): void {

            $service->evaluate(
                $invalidEntryId
            );
        },
        InvalidArgumentException::class,
        'Invalid entry ID '
            . $invalidEntryId
            . ' is rejected.'
    );
}


projectionDiagnosticsHistoryTestResult(
    $gameweekRepository->getAllCalls
        ===
        0,
    'Invalid entry identities do not query stored gameweeks.'
);


/*
 * ============================================================
 * SCENARIO C
 * NO STORED GAMEWEEKS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: No Stored Gameweeks<br>";
echo "============================================<br>";


$gameweekRepository =
    new ProjectionDiagnosticsHistoryGameweekRepository(
        []
    );


$evidenceService =
    new ProjectionDiagnosticsHistoryEvidenceService();


$projectionService =
    new ProjectionDiagnosticsHistoryProjectionService();


$diagnosticsService =
    new ProjectionDiagnosticsHistoryDiagnosticsService();


$service =
    new ProjectionCalibrationDiagnosticsHistoryService(
        $gameweekRepository,
        $evidenceService,
        $projectionService,
        $diagnosticsService
    );


$result =
    $service->evaluate(
        2702264
    );


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'entry_id'
        ]
        ?? null
    )
    ===
    2702264,
    'Entry identity is preserved.'
);


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'total_gameweeks'
        ]
        ?? null
    )
    ===
    0,
    'No stored gameweeks produces a total gameweek count of zero.'
);


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'ready_gameweeks'
        ]
        ?? null
    )
    ===
    0,
    'No stored gameweeks produces zero Ready gameweeks.'
);


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'gameweeks'
        ]
        ?? null
    )
    ===
    [],
    'No stored gameweeks produces an empty audit.'
);


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'player_evaluations'
        ]
        ?? null
    )
    ===
    [],
    'No stored gameweeks produces no historical player evaluations.'
);


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'diagnostics'
        ][
            'diagnostic_sample_size'
        ]
        ?? null
    )
    ===
    0,
    'Empty history is still delegated to the diagnostics service.'
);


projectionDiagnosticsHistoryTestResult(
    count(
        $diagnosticsService->requests
    )
    ===
    1,
    'Diagnostics are evaluated exactly once for empty history.'
);


projectionDiagnosticsHistoryTestResult(
    $diagnosticsService->requests[
        0
    ]
    ===
    [],
    'Empty historical player evidence is passed unchanged to diagnostics.'
);


/*
 * ============================================================
 * SCENARIO D
 * MIXED GAMEWEEK ELIGIBILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Mixed Historical Eligibility<br>";
echo "============================================<br>";


$gameweekRepository =
    new ProjectionDiagnosticsHistoryGameweekRepository([

        [
            'id' => 5,
            'fpl_gameweek_id' => 1,
            'name' => 'Gameweek 1'
        ],

        [
            'id' => 6,
            'fpl_gameweek_id' => 2,
            'name' => 'Gameweek 2'
        ],

        'malformed',

        [
            'id' => 0,
            'fpl_gameweek_id' => 999,
            'name' => 'Invalid Local Gameweek'
        ],

        [
            'id' => 7,
            'fpl_gameweek_id' => 3,
            'name' => 'Gameweek 3'
        ]
    ]);


$evidenceService =
    new ProjectionDiagnosticsHistoryEvidenceService([

        5 => [
            'status' => 'Ready',
            'reason' => null,
            'entry_id' => 2702264,
            'gameweek_id' => 5
        ],

        6 => [
            'status' => 'Missing Recommendation Snapshot',
            'reason' => 'No immutable recommendation snapshot exists.',
            'entry_id' => 2702264,
            'gameweek_id' => 6
        ],

        7 => [
            'status' => 'Ready',
            'reason' => null,
            'entry_id' => 2702264,
            'gameweek_id' => 7
        ]
    ]);


$projectionService =
    new ProjectionDiagnosticsHistoryProjectionService([

        5 => [
            'status' => 'Ready',
            'player_evaluations' => [

                [
                    'player_id' => 101,
                    'position' => 'MID',
                    'points_error' => 2.0,
                    'absolute_points_error' => 2.0,
                    'minutes_error' => 10.0,
                    'absolute_minutes_error' => 10.0
                ],

                [
                    'player_id' => 102,
                    'position' => 'FWD',
                    'points_error' => -1.0,
                    'absolute_points_error' => 1.0,
                    'minutes_error' => -5.0,
                    'absolute_minutes_error' => 5.0
                ]
            ]
        ],

        7 => [
            'status' => 'Ready',
            'player_evaluations' => [

                [
                    'player_id' => 103,
                    'position' => 'DEF',
                    'points_error' => 3.0,
                    'absolute_points_error' => 3.0,
                    'minutes_error' => null,
                    'absolute_minutes_error' => null
                ]
            ]
        ]
    ]);


$diagnosticsService =
    new ProjectionDiagnosticsHistoryDiagnosticsService();


$service =
    new ProjectionCalibrationDiagnosticsHistoryService(
        $gameweekRepository,
        $evidenceService,
        $projectionService,
        $diagnosticsService
    );


$result =
    $service->evaluate(
        2702264
    );


projectionDiagnosticsHistoryTestResult(
    $result[
        'total_gameweeks'
    ]
    ===
    3,
    'Only valid positive stored gameweek identities contribute to total gameweeks.'
);


projectionDiagnosticsHistoryTestResult(
    $result[
        'ready_gameweeks'
    ]
    ===
    2,
    'Only authoritative Ready gameweeks contribute to the Ready count.'
);


projectionDiagnosticsHistoryTestResult(
    count(
        $result[
            'gameweeks'
        ]
    )
    ===
    3,
    'Every valid stored gameweek receives an audit record.'
);


projectionDiagnosticsHistoryTestResult(
    $result[
        'gameweeks'
    ][
        0
    ]
    === [
        'gameweek_id' => 5,
        'fpl_gameweek_id' => 1,
        'name' => 'Gameweek 1',
        'status' => 'Ready',
        'reason' => null
    ],
    'Ready Gameweek 1 audit evidence is preserved exactly.'
);


projectionDiagnosticsHistoryTestResult(
    $result[
        'gameweeks'
    ][
        1
    ]
    === [
        'gameweek_id' => 6,
        'fpl_gameweek_id' => 2,
        'name' => 'Gameweek 2',
        'status' => 'Missing Recommendation Snapshot',
        'reason' => 'No immutable recommendation snapshot exists.'
    ],
    'Unavailable historical gameweek reason remains auditable.'
);


projectionDiagnosticsHistoryTestResult(
    $result[
        'gameweeks'
    ][
        2
    ]
    === [
        'gameweek_id' => 7,
        'fpl_gameweek_id' => 3,
        'name' => 'Gameweek 3',
        'status' => 'Ready',
        'reason' => null
    ],
    'Ready Gameweek 3 audit evidence is preserved exactly.'
);


projectionDiagnosticsHistoryTestResult(
    $evidenceService->requests
    === [
        [
            'entry_id' => 2702264,
            'gameweek_id' => 5
        ],
        [
            'entry_id' => 2702264,
            'gameweek_id' => 6
        ],
        [
            'entry_id' => 2702264,
            'gameweek_id' => 7
        ]
    ],
    'Historical eligibility is checked for every valid stored gameweek.'
);


projectionDiagnosticsHistoryTestResult(
    $projectionService->requests
    === [
        5,
        7
    ],
    'Projection backtesting runs only for authoritative Ready gameweeks.'
);


projectionDiagnosticsHistoryTestResult(
    count(
        $result[
            'player_evaluations'
        ]
    )
    ===
    3,
    'Player evaluations from all Ready gameweeks are pooled.'
);


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'player_evaluations'
        ][
            0
        ][
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'First pooled player evaluation receives its historical gameweek identity.'
);


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'player_evaluations'
        ][
            1
        ][
            'gameweek_id'
        ]
        ?? null
    )
    ===
    5,
    'Second player from the same gameweek retains the same historical identity.'
);


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'player_evaluations'
        ][
            2
        ][
            'gameweek_id'
        ]
        ?? null
    )
    ===
    7,
    'Player from the later Ready gameweek receives the correct gameweek identity.'
);


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'player_evaluations'
        ][
            0
        ][
            'player_id'
        ]
        ?? null
    )
    ===
    101,
    'Original player-level projection evidence is preserved while pooling.'
);


projectionDiagnosticsHistoryTestResult(
    count(
        $diagnosticsService->requests
    )
    ===
    1,
    'Pooled historical diagnostics are calculated exactly once.'
);


projectionDiagnosticsHistoryTestResult(
    $diagnosticsService->requests[
        0
    ]
    ===
    $result[
        'player_evaluations'
    ],
    'Diagnostics receive the exact pooled historical player evaluations.'
);


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'diagnostics'
        ][
            'diagnostic_sample_size'
        ]
        ?? null
    )
    ===
    3,
    'Returned diagnostics describe the complete pooled historical sample.'
);


/*
 * ============================================================
 * SCENARIO E
 * NON-READY GAMEWEEKS NEVER REACH PROJECTION BACKTESTING
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Non-Ready Gameweeks Are Excluded<br>";
echo "============================================<br>";


$gameweekRepository =
    new ProjectionDiagnosticsHistoryGameweekRepository([

        [
            'id' => 10,
            'fpl_gameweek_id' => 8,
            'name' => 'Gameweek 8'
        ]
    ]);


$evidenceService =
    new ProjectionDiagnosticsHistoryEvidenceService([

        10 => [
            'status' => 'Outcomes Not Authoritative',
            'reason' => 'Gameweek outcomes are not authoritative.',
            'gameweek_id' => 10
        ]
    ]);


$projectionService =
    new ProjectionDiagnosticsHistoryProjectionService();


$diagnosticsService =
    new ProjectionDiagnosticsHistoryDiagnosticsService();


$service =
    new ProjectionCalibrationDiagnosticsHistoryService(
        $gameweekRepository,
        $evidenceService,
        $projectionService,
        $diagnosticsService
    );


$result =
    $service->evaluate(
        2702264
    );


projectionDiagnosticsHistoryTestResult(
    $result[
        'ready_gameweeks'
    ]
    ===
    0,
    'Non-Ready history does not increment Ready gameweeks.'
);


projectionDiagnosticsHistoryTestResult(
    $projectionService->requests
    ===
    [],
    'Non-Ready historical evidence never reaches projection backtesting.'
);


projectionDiagnosticsHistoryTestResult(
    $result[
        'player_evaluations'
    ]
    ===
    [],
    'Non-Ready gameweeks cannot manufacture projection calibration observations.'
);


projectionDiagnosticsHistoryTestResult(
    (
        $result[
            'diagnostics'
        ][
            'diagnostic_sample_size'
        ]
        ?? null
    )
    ===
    0,
    'Non-Ready history produces an empty pooled diagnostic sample.'
);


/*
 * ============================================================
 * SCENARIO F
 * MALFORMED PLAYER EVALUATIONS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Malformed Player Evaluations<br>";
echo "============================================<br>";


$gameweekRepository =
    new ProjectionDiagnosticsHistoryGameweekRepository([

        [
            'id' => 11,
            'fpl_gameweek_id' => 9,
            'name' => 'Gameweek 9'
        ]
    ]);


$evidenceService =
    new ProjectionDiagnosticsHistoryEvidenceService([

        11 => [
            'status' => 'Ready',
            'reason' => null,
            'gameweek_id' => 11
        ]
    ]);


$projectionService =
    new ProjectionDiagnosticsHistoryProjectionService([

        11 => [
            'status' => 'Ready',
            'player_evaluations' => [

                'malformed',

                [
                    'player_id' => 201,
                    'position' => 'GK',
                    'points_error' => 0.0,
                    'absolute_points_error' => 0.0,
                    'minutes_error' => 0.0,
                    'absolute_minutes_error' => 0.0
                ],

                null
            ]
        ]
    ]);


$diagnosticsService =
    new ProjectionDiagnosticsHistoryDiagnosticsService();


$service =
    new ProjectionCalibrationDiagnosticsHistoryService(
        $gameweekRepository,
        $evidenceService,
        $projectionService,
        $diagnosticsService
    );


$result =
    $service->evaluate(
        2702264
    );


projectionDiagnosticsHistoryTestResult(
    count(
        $result[
            'player_evaluations'
        ]
    )
    ===
    1,
    'Malformed player-evaluation rows are ignored during historical pooling.'
);


projectionDiagnosticsHistoryTestResult(
    $result[
        'player_evaluations'
    ][
        0
    ][
        'player_id'
    ]
    ===
    201,
    'Valid player evaluation remains available when neighbouring rows are malformed.'
);


projectionDiagnosticsHistoryTestResult(
    $result[
        'player_evaluations'
    ][
        0
    ][
        'gameweek_id'
    ]
    ===
    11,
    'Valid pooled player evaluation receives the correct gameweek identity.'
);


/*
 * ============================================================
 * SCENARIO G
 * SOURCE EVIDENCE IS NOT MUTATED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Source Projection Evidence Remains Unchanged<br>";
echo "============================================<br>";


$sourceEvaluation = [
    'player_id' => 301,
    'position' => 'MID',
    'points_error' => 1.0,
    'absolute_points_error' => 1.0,
    'minutes_error' => 5.0,
    'absolute_minutes_error' => 5.0
];


$originalSourceEvaluation =
    $sourceEvaluation;


$gameweekRepository =
    new ProjectionDiagnosticsHistoryGameweekRepository([

        [
            'id' => 12,
            'fpl_gameweek_id' => 10,
            'name' => 'Gameweek 10'
        ]
    ]);


$evidenceService =
    new ProjectionDiagnosticsHistoryEvidenceService([

        12 => [
            'status' => 'Ready',
            'reason' => null,
            'gameweek_id' => 12
        ]
    ]);


$projectionService =
    new ProjectionDiagnosticsHistoryProjectionService([

        12 => [
            'status' => 'Ready',
            'player_evaluations' => [
                $sourceEvaluation
            ]
        ]
    ]);


$diagnosticsService =
    new ProjectionDiagnosticsHistoryDiagnosticsService();


$service =
    new ProjectionCalibrationDiagnosticsHistoryService(
        $gameweekRepository,
        $evidenceService,
        $projectionService,
        $diagnosticsService
    );


$service->evaluate(
    2702264
);


projectionDiagnosticsHistoryTestResult(
    $sourceEvaluation
        ===
        $originalSourceEvaluation,
    'Historical source player-evaluation evidence is not mutated.'
);


/*
 * ============================================================
 * SCENARIO H
 * NARROW RESPONSIBILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Narrow Historical Responsibility<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        2702264
    );


projectionDiagnosticsHistoryTestResult(
    !array_key_exists(
        'recommended_parameters',
        $result
    ),
    'History diagnostics do not recommend projection parameter changes.'
);


projectionDiagnosticsHistoryTestResult(
    !array_key_exists(
        'best_model',
        $result
    ),
    'History diagnostics do not choose a preferred projection model.'
);


projectionDiagnosticsHistoryTestResult(
    !array_key_exists(
        'model_score',
        $result
    ),
    'History diagnostics do not manufacture an overall model score.'
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "TEST SUMMARY<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}