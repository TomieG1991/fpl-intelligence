<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Chip Recommendation Production Orchestrator Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function chipProductionOrchestratorAssert(
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


function chipProductionOrchestratorSection(
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
 * A. SERVICE CONTRACT
 * ============================================================
 *
 * v1.1.0 — Intelligence Quality & Outcome Evaluation
 *
 * Recommendation-history capture currently depends on the
 * manager visiting public/chips.php.
 *
 * Historical evidence needs a reusable production orchestration
 * boundary so the same existing production calculations can
 * later be invoked by either:
 *
 * - the Chips page; or
 * - a dedicated historical-evidence process.
 *
 * The orchestrator must NOT introduce new intelligence logic.
 * It must delegate to the four existing decision services.
 */

chipProductionOrchestratorSection(
    'A. Service Contract'
);


$classExists =
    class_exists(
        'ChipRecommendationProductionOrchestrator'
    );


chipProductionOrchestratorAssert(
    $classExists,
    'ChipRecommendationProductionOrchestrator exists.'
);


if (!$classExists) {

    echo "<br>";
    echo "<strong>EXPECTED RED: reusable production orchestrator does not exist yet.</strong><br>";

    echo "<br>";
    echo "============================================<br>";
    echo "Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "<strong>RESULT: EXPECTED RED ❌</strong><br>";

    exit;
}


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class ChipProductionWildcardService
{
    public int $calls = 0;
    public ?array $arguments = null;

    public array $result = [
        'status' => 'Available',
        'source' => 'wildcard'
    ];


    public function build(
        array $importedSquad,
        array $players,
        float $budget = 100.0,
        int $horizon = 3
    ): array {

        $this->calls++;

        $this->arguments = [
            'imported_squad' => $importedSquad,
            'players' => $players,
            'budget' => $budget,
            'horizon' => $horizon
        ];

        return $this->result;
    }
}


class ChipProductionFreeHitService
{
    public int $calls = 0;
    public ?array $arguments = null;

    public array $result = [
        'status' => 'Available',
        'source' => 'free_hit'
    ];


    public function build(
        array $importedSquad,
        array $players,
        float $budget = 100.0,
        ?int $targetGameweek = null
    ): array {

        $this->calls++;

        $this->arguments = [
            'imported_squad' => $importedSquad,
            'players' => $players,
            'budget' => $budget,
            'target_gameweek' => $targetGameweek
        ];

        return $this->result;
    }
}


class ChipProductionBenchBoostService
{
    public int $calls = 0;
    public ?array $arguments = null;

    public array $result = [
        'status' => 'Available',
        'source' => 'bench_boost'
    ];


    public function build(
        array $importedSquad,
        ?int $targetGameweek = null
    ): array {

        $this->calls++;

        $this->arguments = [
            'imported_squad' => $importedSquad,
            'target_gameweek' => $targetGameweek
        ];

        return $this->result;
    }
}


class ChipProductionTripleCaptainService
{
    public int $calls = 0;
    public ?array $arguments = null;

    public array $result = [
        'status' => 'Available',
        'source' => 'triple_captain'
    ];


    public function build(
        array $importedSquad,
        ?int $targetGameweek = null
    ): array {

        $this->calls++;

        $this->arguments = [
            'imported_squad' => $importedSquad,
            'target_gameweek' => $targetGameweek
        ];

        return $this->result;
    }
}


/*
 * ============================================================
 * CONTROLLED INPUT
 * ============================================================
 */

$importedSquad = [
    'status' => 'success',
    'players' => [
        [
            'fpl_player_id' => 101
        ]
    ]
];


$playerPool = [
    [
        'player_id' => 1,
        'name' => 'Controlled Player'
    ]
];


$budget = 101.7;
$targetGameweek = 6;


/*
 * ============================================================
 * B. CONSTRUCTION
 * ============================================================
 */

chipProductionOrchestratorSection(
    'B. Construction'
);


$wildcardService =
    new ChipProductionWildcardService();


$freeHitService =
    new ChipProductionFreeHitService();


$benchBoostService =
    new ChipProductionBenchBoostService();


$tripleCaptainService =
    new ChipProductionTripleCaptainService();


$orchestrator =
    new ChipRecommendationProductionOrchestrator(
        $wildcardService,
        $freeHitService,
        $benchBoostService,
        $tripleCaptainService
    );


chipProductionOrchestratorAssert(
    $orchestrator
        instanceof
        ChipRecommendationProductionOrchestrator,
    'Reusable production orchestrator can be constructed from the four existing decision-service boundaries.'
);


/*
 * ============================================================
 * C. BUILD COMPLETE PRODUCTION EVIDENCE
 * ============================================================
 */

chipProductionOrchestratorSection(
    'C. Production Evidence'
);


$result =
    $orchestrator
        ->build(
            $importedSquad,
            $playerPool,
            $budget,
            $targetGameweek
        );


chipProductionOrchestratorAssert(
    is_array(
        $result
    ),
    'Production orchestration returns an array result.'
);


chipProductionOrchestratorAssert(
    ($result['wildcard'] ?? null)
    ===
    $wildcardService->result,
    'Existing Wildcard result is returned unchanged.'
);


chipProductionOrchestratorAssert(
    ($result['free_hit'] ?? null)
    ===
    $freeHitService->result,
    'Existing Free Hit result is returned unchanged.'
);


chipProductionOrchestratorAssert(
    ($result['bench_boost'] ?? null)
    ===
    $benchBoostService->result,
    'Existing Bench Boost result is returned unchanged.'
);


chipProductionOrchestratorAssert(
    ($result['triple_captain'] ?? null)
    ===
    $tripleCaptainService->result,
    'Existing Triple Captain result is returned unchanged.'
);


/*
 * ============================================================
 * D. EXACTLY ONE CALL PER PIPELINE
 * ============================================================
 */

chipProductionOrchestratorSection(
    'D. Existing Pipeline Delegation'
);


chipProductionOrchestratorAssert(
    $wildcardService->calls === 1,
    'Wildcard production pipeline is called exactly once.'
);


chipProductionOrchestratorAssert(
    $freeHitService->calls === 1,
    'Free Hit production pipeline is called exactly once.'
);


chipProductionOrchestratorAssert(
    $benchBoostService->calls === 1,
    'Bench Boost production pipeline is called exactly once.'
);


chipProductionOrchestratorAssert(
    $tripleCaptainService->calls === 1,
    'Triple Captain production pipeline is called exactly once.'
);


/*
 * ============================================================
 * E. INPUT PRESERVATION
 * ============================================================
 */

chipProductionOrchestratorSection(
    'E. Input Preservation'
);


chipProductionOrchestratorAssert(
    $wildcardService->arguments === [
        'imported_squad' => $importedSquad,
        'players' => $playerPool,
        'budget' => $budget,
        'horizon' => 3
    ],
    'Wildcard receives the existing production inputs and three-gameweek horizon unchanged.'
);


chipProductionOrchestratorAssert(
    $freeHitService->arguments === [
        'imported_squad' => $importedSquad,
        'players' => $playerPool,
        'budget' => $budget,
        'target_gameweek' => $targetGameweek
    ],
    'Free Hit receives the existing production inputs unchanged.'
);


chipProductionOrchestratorAssert(
    $benchBoostService->arguments === [
        'imported_squad' => $importedSquad,
        'target_gameweek' => $targetGameweek
    ],
    'Bench Boost receives the existing production inputs unchanged.'
);


chipProductionOrchestratorAssert(
    $tripleCaptainService->arguments === [
        'imported_squad' => $importedSquad,
        'target_gameweek' => $targetGameweek
    ],
    'Triple Captain receives the existing production inputs unchanged.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Chip Recommendation Production Orchestrator Test Summary<br>";
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