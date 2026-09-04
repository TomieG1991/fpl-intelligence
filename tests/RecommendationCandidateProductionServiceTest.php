<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION CANDIDATE PRODUCTION SERVICE TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * This test defines the production orchestration boundary that
 * converts existing live recommendation outputs into the latest
 * mutable Recommendation Candidate.
 *
 * The production service must NOT:
 *
 * - calculate Expected Points itself
 * - calculate Chip Intelligence itself
 * - rank chips
 * - create a new Gameweek scoring model
 * - persist preview / integration entry 0
 *
 * It must reuse the existing production intelligence services
 * and the historical evidence adapters introduced in v0.35.
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


function productionCandidateAssert(
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


function productionCandidateSection(
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
 *
 * These deliberately expose only the existing methods that the
 * production orchestration service is allowed to consume.
 */

class ProductionCandidatePlayerIntelligenceService
{
    public int $mapCalls =
        0;


    public int $summaryCalls =
        0;


    public int $decisionCalls =
        0;


    public array $mappedSquad;


    public array $summaries;


    public array $decisionResult;


    public function __construct(
        array $mappedSquad,
        array $summaries,
        array $decisionResult
    ) {

        $this->mappedSquad =
            $mappedSquad;


        $this->summaries =
            $summaries;


        $this->decisionResult =
            $decisionResult;
    }


    public function buildSquadFromFPLImport(
        array $importedSquad
    ): ?array {

        $this->mapCalls++;


        return
            $this->mappedSquad;
    }


    public function getAllPlayerSummaries(): array
    {
        $this->summaryCalls++;


        return
            $this->summaries;
    }


    public function getGameweekDecision(
        array $players,
        float $bank = 0.0
    ): array {

        $this->decisionCalls++;


        return
            $this->decisionResult;
    }
}


class ProductionCandidateCaptureService
{
    public int $captureCalls =
        0;


    public ?array $lastCapture =
        null;


    public bool $result =
        true;


    public function capture(
        int $gameweekId,
        int $entryId,
        string $generatedAt,
        string $deadlineTime,
        array $playerProjections,
        array $gameweekDecisionResult,
        array $chipRecommendations
    ): bool {

        $this->captureCalls++;


        $this->lastCapture = [

            'gameweek_id' =>
                $gameweekId,

            'entry_id' =>
                $entryId,

            'generated_at' =>
                $generatedAt,

            'deadline_time' =>
                $deadlineTime,

            'player_projections' =>
                $playerProjections,

            'gameweek_decision_result' =>
                $gameweekDecisionResult,

            'chip_recommendations' =>
                $chipRecommendations
        ];


        return
            $this->result;
    }
}


/*
 * ============================================================
 * FIXED HISTORICAL CONTEXT
 * ============================================================
 */

$gameweekId =
    7;


$entryId =
    2702264;


$generatedAt =
    '2026-09-04 10:00:00';


$deadlineTime =
    '2026-09-05 11:00:00';


/*
 * ============================================================
 * IMPORTED FPL SQUAD
 * ============================================================
 */

$importedSquad = [

    'status' =>
        'success',

    'entry' => [

        'entry_id' =>
            $entryId
    ],

    'bank' =>
        1.3,

    'team_value' =>
        99.6,

    'player_count' =>
        15,

    'players' => [

        [
            'fpl_player_id' =>
                1001,

            'squad_position' =>
                1,

            'multiplier' =>
                1
        ],

        [
            'fpl_player_id' =>
                1002,

            'squad_position' =>
                2,

            'multiplier' =>
                1
        ],

        [
            'fpl_player_id' =>
                1003,

            'squad_position' =>
                12,

            'multiplier' =>
                0
        ]
    ]
];


/*
 * ============================================================
 * EXISTING MAPPED SQUAD
 * ============================================================
 *
 * The real PlayerIntelligenceService performs this mapping.
 */

$mappedSquad = [

    'is_complete' =>
        true,

    'entry' => [

        'entry_id' =>
            $entryId
    ],

    'bank' =>
        1.3,

    'players' => [

        [
            'player_id' =>
                101,

            'fpl_player_id' =>
                1001,

            'squad_position' =>
                1,

            'multiplier' =>
                1
        ],

        [
            'player_id' =>
                102,

            'fpl_player_id' =>
                1002,

            'squad_position' =>
                2,

            'multiplier' =>
                1
        ],

        [
            'player_id' =>
                103,

            'fpl_player_id' =>
                1003,

            'squad_position' =>
                12,

            'multiplier' =>
                0
        ]
    ]
];


/*
 * ============================================================
 * EXISTING PLAYER INTELLIGENCE
 * ============================================================
 */

$playerSummaries = [

    [
        'player_id' =>
            101,

        'fpl_player_id' =>
            1001,

        'name' =>
            'Production Player One',

        'position' =>
            'GK',

        'team_id' =>
            1,

        'price' =>
            5.5,

        'intelligence_score' =>
            71.25,

        'projected_points' =>
            5.75,

        'projected_minutes' =>
            90.0,

        'projection_confidence' =>
            0.84,

        'projection_confidence_percent' =>
            84.0,

        'projection_confidence_label' =>
            'High',

        'projected_points_components' => [

            'appearance' =>
                2.0,

            'performance' =>
                3.75
        ],

        'projected_points_inputs' => [

            'expected_minutes' =>
                90.0
        ],

        'has_projected_points' =>
            true
    ],

    [
        'player_id' =>
            102,

        'fpl_player_id' =>
            1002,

        'name' =>
            'Production Player Two',

        'position' =>
            'DEF',

        'team_id' =>
            2,

        'price' =>
            6.0,

        'intelligence_score' =>
            78.50,

        'projected_points' =>
            6.25,

        'projected_minutes' =>
            82.0,

        'projection_confidence' =>
            0.76,

        'projection_confidence_percent' =>
            76.0,

        'projection_confidence_label' =>
            'Medium',

        'projected_points_components' => [

            'appearance' =>
                1.82,

            'performance' =>
                4.43
        ],

        'projected_points_inputs' => [

            'expected_minutes' =>
                82.0
        ],

        'has_projected_points' =>
            true
    ],

    [
        'player_id' =>
            103,

        'fpl_player_id' =>
            1003,

        'name' =>
            'Production Player Three',

        'position' =>
            'MID',

        'team_id' =>
            3,

        'price' =>
            8.0,

        'intelligence_score' =>
            68.0,

        'projected_points' =>
            null,

        'projected_minutes' =>
            35.0,

        'projection_confidence' =>
            0.41,

        'projection_confidence_percent' =>
            41.0,

        'projection_confidence_label' =>
            'Low',

        'projected_points_components' =>
            [],

        'projected_points_inputs' => [

            'expected_minutes' =>
                35.0
        ],

        'has_projected_points' =>
            false
    ]
];


/*
 * ============================================================
 * EXISTING GAMEWEEK DECISION
 * ============================================================
 */

$gameweekDecisionResult = [

    'status' =>
        'success',

    'gameweek' => [

        'formation' =>
            '3-4-3',

        'starting_xi' => [

            [
                'player_id' =>
                    101,

                'name' =>
                    'Production Player One'
            ],

            [
                'player_id' =>
                    102,

                'name' =>
                    'Production Player Two'
            ]
        ],

        'bench' => [

            [
                'player_id' =>
                    103,

                'name' =>
                    'Production Player Three'
            ]
        ]
    ],

    'captaincy' => [

        'status' =>
            'success',

        'captain' => [

            'player_id' =>
                102,

            'name' =>
                'Production Player Two',

            'captain_score' =>
                81.25
        ],

        'vice_captain' => [

            'player_id' =>
                101,

            'name' =>
                'Production Player One'
        ]
    ],

    'transfers' => [

        'recommendation' =>
            'Hold',

        'transfers' =>
            []
    ],

    'decision' => [

        'status' =>
            'success',

        'overall_action' =>
            'Hold transfer'
    ]
];


/*
 * ============================================================
 * EXISTING CHIP RESULTS
 * ============================================================
 */

$wildcardResult = [

    'timing_result' => [

        'current_squad_projected_points' =>
            68.25,

        'wildcard_squad_projected_points' =>
            74.50,

        'projected_points_gain' =>
            6.25,

        'future_projected_gain' =>
            10.75,

        'better_timing' =>
            'GW8',

        'decision' =>
            new ChipDecision(
                'Wildcard',
                'Consider',
                0.62,
                'Wildcard timing is worth considering.'
            )
    ]
];


$freeHitResult = [

    'value_result' => [

        'current_squad_projected_points' =>
            68.25,

        'free_hit_projected_points' =>
            72.75,

        'projected_points_gain' =>
            4.50
    ],

    'decision' =>
        new ChipDecision(
            'Free Hit',
            'Hold',
            0.71,
            'Free Hit value is currently limited.'
        )
];


$benchBoostResult = [

    'analysis' => [

        'projected_bench_points' =>
            16.25,

        'bench_reliability' =>
            0.82,

        'fixture_quality' =>
            67.50,

        'full_squad_availability' =>
            0.91
    ],

    'decision' =>
        new ChipDecision(
            'Bench Boost',
            'Use',
            0.83,
            'Projected bench contribution is strong.'
        )
];


$tripleCaptainResult = [

    'analysis' => [

        'projected_points' =>
            9.75,

        'captain_score' =>
            74.25,

        'projection_confidence' =>
            0.78
    ],

    'decision' =>
        new ChipDecision(
            'Triple Captain',
            'Hold',
            0.76,
            'Captain projection does not justify the chip.'
        )
];


/*
 * ============================================================
 * BUILD DEPENDENCIES
 * ============================================================
 */

$playerIntelligence =
    new ProductionCandidatePlayerIntelligenceService(
        $mappedSquad,
        $playerSummaries,
        $gameweekDecisionResult
    );


$playerProjectionEvidence =
    new PlayerProjectionEvidence();


$chipRecommendationEvidence =
    new ChipRecommendationEvidence();


$captureService =
    new ProductionCandidateCaptureService();


/*
 * ============================================================
 * A. CLASS CONTRACT
 * ============================================================
 */

productionCandidateSection(
    'A. Production Service Contract'
);


productionCandidateAssert(
    class_exists(
        'RecommendationCandidateProductionService'
    ),
    'RecommendationCandidateProductionService exists.'
);


if (
    !class_exists(
        'RecommendationCandidateProductionService'
    )
) {

    echo "<br>";
    echo "<strong>EXPECTED RED: production service does not exist yet.</strong><br>";

    echo "<br>";
    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    exit;
}


/*
 * ============================================================
 * CREATE SERVICE
 * ============================================================
 */

$productionService =
    new RecommendationCandidateProductionService(
        $playerIntelligence,
        $playerProjectionEvidence,
        $chipRecommendationEvidence,
        $captureService
    );


/*
 * ============================================================
 * B. CAPTURE EXISTING PRODUCTION INTELLIGENCE
 * ============================================================
 */

productionCandidateSection(
    'B. Capture Existing Production Intelligence'
);


$result =
    $productionService
        ->capture(
            $gameweekId,
            $entryId,
            $generatedAt,
            $deadlineTime,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult
        );


productionCandidateAssert(
    $result === true,
    'Complete existing production intelligence is captured.'
);


productionCandidateAssert(
    $captureService
        ->captureCalls
    ===
    1,
    'Candidate capture service is called exactly once.'
);


/*
 * ============================================================
 * C. EXISTING SQUAD MAPPING
 * ============================================================
 */

productionCandidateSection(
    'C. Existing Squad Mapping'
);


productionCandidateAssert(
    $playerIntelligence
        ->mapCalls
    ===
    1,
    'Existing Player Intelligence squad mapper is used.'
);


/*
 * ============================================================
 * D. EXISTING GAMEWEEK DECISION
 * ============================================================
 */

productionCandidateSection(
    'D. Existing Gameweek Decision'
);


productionCandidateAssert(
    $playerIntelligence
        ->decisionCalls
    ===
    1,
    'Existing Gameweek Decision pipeline is used exactly once.'
);


productionCandidateAssert(
    $captureService
        ->lastCapture[
            'gameweek_decision_result'
        ]
    ===
    $gameweekDecisionResult,
    'Existing Gameweek Decision result is passed unchanged.'
);


/*
 * ============================================================
 * E. PLAYER PROJECTION EVIDENCE
 * ============================================================
 */

productionCandidateSection(
    'E. Existing Player Projection Evidence'
);


productionCandidateAssert(
    $playerIntelligence
        ->summaryCalls
    ===
    1,
    'Existing Player Intelligence summaries are requested once.'
);


productionCandidateAssert(
    count(
        $captureService
            ->lastCapture[
                'player_projections'
            ]
    )
    ===
    3,
    'Projection evidence contains only mapped squad players.'
);


productionCandidateAssert(
    $captureService
        ->lastCapture[
            'player_projections'
        ][
            0
        ][
            'projected_points'
        ]
    ===
    5.75,
    'Existing projected points are preserved.'
);


productionCandidateAssert(
    $captureService
        ->lastCapture[
            'player_projections'
        ][
            2
        ][
            'projected_points'
        ]
    ===
    null,
    'Unavailable projected points remain unavailable.'
);


/*
 * ============================================================
 * F. EXISTING CHIP EVIDENCE
 * ============================================================
 */

productionCandidateSection(
    'F. Existing Chip Intelligence Evidence'
);


$capturedChips =
    $captureService
        ->lastCapture[
            'chip_recommendations'
        ];


productionCandidateAssert(
    array_keys(
        $capturedChips
    )
    ===
    [
        'wildcard',
        'free_hit',
        'bench_boost',
        'triple_captain'
    ],
    'All four existing chip recommendations are preserved.'
);


productionCandidateAssert(
    $capturedChips[
        'bench_boost'
    ][
        'decision'
    ][
        'recommendation'
    ]
    ===
    'Use',
    'Existing Bench Boost recommendation is unchanged.'
);


productionCandidateAssert(
    $capturedChips[
        'wildcard'
    ][
        'analysis'
    ][
        'projected_points_gain'
    ]
    ===
    6.25,
    'Raw Wildcard supporting evidence is preserved.'
);


/*
 * ============================================================
 * G. HISTORICAL METADATA
 * ============================================================
 */

productionCandidateSection(
    'G. Historical Metadata'
);


productionCandidateAssert(
    $captureService
        ->lastCapture[
            'gameweek_id'
        ]
    ===
    $gameweekId,
    'Local gameweek ID is passed unchanged.'
);


productionCandidateAssert(
    $captureService
        ->lastCapture[
            'entry_id'
        ]
    ===
    $entryId,
    'FPL entry ID is passed unchanged.'
);


productionCandidateAssert(
    $captureService
        ->lastCapture[
            'generated_at'
        ]
    ===
    $generatedAt,
    'Generation timestamp is passed unchanged.'
);


productionCandidateAssert(
    $captureService
        ->lastCapture[
            'deadline_time'
        ]
    ===
    $deadlineTime,
    'Deadline timestamp is passed unchanged.'
);


/*
 * ============================================================
 * H. BANK PASSED TO EXISTING GAMEWEEK DECISION
 * ============================================================
 */

productionCandidateSection(
    'H. Existing Manager Budget Context'
);


/*
 * The production test double does not calculate with the bank,
 * so replace it briefly with an inspecting double.
 */

class ProductionCandidateBankInspectingService
    extends ProductionCandidatePlayerIntelligenceService
{
    public ?float $lastBank =
        null;


    public function getGameweekDecision(
        array $players,
        float $bank = 0.0
    ): array {

        $this->lastBank =
            $bank;


        return
            parent::getGameweekDecision(
                $players,
                $bank
            );
    }
}


$bankInspectingIntelligence =
    new ProductionCandidateBankInspectingService(
        $mappedSquad,
        $playerSummaries,
        $gameweekDecisionResult
    );


$bankCaptureService =
    new ProductionCandidateCaptureService();


$bankProductionService =
    new RecommendationCandidateProductionService(
        $bankInspectingIntelligence,
        $playerProjectionEvidence,
        $chipRecommendationEvidence,
        $bankCaptureService
    );


$bankProductionService
    ->capture(
        $gameweekId,
        $entryId,
        $generatedAt,
        $deadlineTime,
        $importedSquad,
        $wildcardResult,
        $freeHitResult,
        $benchBoostResult,
        $tripleCaptainResult
    );


productionCandidateAssert(
    $bankInspectingIntelligence
        ->lastBank
    ===
    1.3,
    'Existing manager bank is passed into Gameweek Decision.'
);


/*
 * ============================================================
 * I. PREVIEW / INTEGRATION ENTRY PROTECTION
 * ============================================================
 */

productionCandidateSection(
    'I. Preview And Integration Protection'
);


$previewIntelligence =
    new ProductionCandidatePlayerIntelligenceService(
        $mappedSquad,
        $playerSummaries,
        $gameweekDecisionResult
    );


$previewCaptureService =
    new ProductionCandidateCaptureService();


$previewProductionService =
    new RecommendationCandidateProductionService(
        $previewIntelligence,
        $playerProjectionEvidence,
        $chipRecommendationEvidence,
        $previewCaptureService
    );


$previewRejected =
    false;


try {

    $previewProductionService
        ->capture(
            $gameweekId,
            0,
            $generatedAt,
            $deadlineTime,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult
        );

} catch (
    InvalidArgumentException $exception
) {

    $previewRejected =
        true;
}


productionCandidateAssert(
    $previewRejected,
    'Entry ID zero is rejected.'
);


productionCandidateAssert(
    $previewCaptureService
        ->captureCalls
    ===
    0,
    'Preview or integration entry cannot write recommendation history.'
);


/*
 * ============================================================
 * J. INCOMPLETE IMPORTED SQUAD
 * ============================================================
 */

productionCandidateSection(
    'J. Incomplete Imported Squad'
);


$incompleteImportedSquad =
    $importedSquad;


$incompleteImportedSquad[
    'status'
] =
    'no_public_squad';


$incompleteCaptureService =
    new ProductionCandidateCaptureService();


$incompleteProductionService =
    new RecommendationCandidateProductionService(
        new ProductionCandidatePlayerIntelligenceService(
            $mappedSquad,
            $playerSummaries,
            $gameweekDecisionResult
        ),
        $playerProjectionEvidence,
        $chipRecommendationEvidence,
        $incompleteCaptureService
    );


$incompleteRejected =
    false;


try {

    $incompleteProductionService
        ->capture(
            $gameweekId,
            $entryId,
            $generatedAt,
            $deadlineTime,
            $incompleteImportedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult
        );

} catch (
    InvalidArgumentException $exception
) {

    $incompleteRejected =
        true;
}


productionCandidateAssert(
    $incompleteRejected,
    'Unsuccessful imported squad is rejected.'
);


productionCandidateAssert(
    $incompleteCaptureService
        ->captureCalls
    ===
    0,
    'Invalid imported squad cannot write recommendation history.'
);


/*
 * ============================================================
 * K. INCOMPLETE MAPPED SQUAD
 * ============================================================
 */

productionCandidateSection(
    'K. Incomplete Mapped Squad'
);


$incompleteMappedSquad =
    $mappedSquad;


$incompleteMappedSquad[
    'is_complete'
] =
    false;


$mappedCaptureService =
    new ProductionCandidateCaptureService();


$mappedProductionService =
    new RecommendationCandidateProductionService(
        new ProductionCandidatePlayerIntelligenceService(
            $incompleteMappedSquad,
            $playerSummaries,
            $gameweekDecisionResult
        ),
        $playerProjectionEvidence,
        $chipRecommendationEvidence,
        $mappedCaptureService
    );


$mappedRejected =
    false;


try {

    $mappedProductionService
        ->capture(
            $gameweekId,
            $entryId,
            $generatedAt,
            $deadlineTime,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult
        );

} catch (
    RuntimeException $exception
) {

    $mappedRejected =
        true;
}


productionCandidateAssert(
    $mappedRejected,
    'Incomplete mapped squad is rejected.'
);


productionCandidateAssert(
    $mappedCaptureService
        ->captureCalls
    ===
    0,
    'Incomplete mapped squad cannot write recommendation history.'
);


/*
 * ============================================================
 * L. UNSUCCESSFUL GAMEWEEK DECISION
 * ============================================================
 */

productionCandidateSection(
    'L. Unsuccessful Gameweek Decision'
);


$failedDecision =
    [
        'status' =>
            'error',

        'message' =>
            'Unable to generate Gameweek Decision.'
    ];


$decisionCaptureService =
    new ProductionCandidateCaptureService();


$decisionProductionService =
    new RecommendationCandidateProductionService(
        new ProductionCandidatePlayerIntelligenceService(
            $mappedSquad,
            $playerSummaries,
            $failedDecision
        ),
        $playerProjectionEvidence,
        $chipRecommendationEvidence,
        $decisionCaptureService
    );


$decisionRejected =
    false;


try {

    $decisionProductionService
        ->capture(
            $gameweekId,
            $entryId,
            $generatedAt,
            $deadlineTime,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult
        );

} catch (
    RuntimeException $exception
) {

    $decisionRejected =
        true;
}


productionCandidateAssert(
    $decisionRejected,
    'Unsuccessful existing Gameweek Decision is rejected.'
);


productionCandidateAssert(
    $decisionCaptureService
        ->captureCalls
    ===
    0,
    'Failed Gameweek Decision cannot write recommendation history.'
);


/*
 * ============================================================
 * M. DEADLINE PROTECTION
 * ============================================================
 */

productionCandidateSection(
    'M. Deadline Protection'
);


$deadlineCaptureService =
    new ProductionCandidateCaptureService();


$deadlineProductionService =
    new RecommendationCandidateProductionService(
        new ProductionCandidatePlayerIntelligenceService(
            $mappedSquad,
            $playerSummaries,
            $gameweekDecisionResult
        ),
        $playerProjectionEvidence,
        $chipRecommendationEvidence,
        $deadlineCaptureService
    );


$deadlineRejected =
    false;


try {

    $deadlineProductionService
        ->capture(
            $gameweekId,
            $entryId,
            '2026-09-05 11:00:00',
            $deadlineTime,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult
        );

} catch (
    InvalidArgumentException $exception
) {

    $deadlineRejected =
        true;
}


productionCandidateAssert(
    $deadlineRejected,
    'Recommendation generated at the deadline is rejected.'
);


productionCandidateAssert(
    $deadlineCaptureService
        ->captureCalls
    ===
    0,
    'At-deadline recommendation cannot write history.'
);


/*
 * ============================================================
 * N. POST-DEADLINE PROTECTION
 * ============================================================
 */

productionCandidateSection(
    'N. Post-Deadline Protection'
);


$postDeadlineCaptureService =
    new ProductionCandidateCaptureService();


$postDeadlineProductionService =
    new RecommendationCandidateProductionService(
        new ProductionCandidatePlayerIntelligenceService(
            $mappedSquad,
            $playerSummaries,
            $gameweekDecisionResult
        ),
        $playerProjectionEvidence,
        $chipRecommendationEvidence,
        $postDeadlineCaptureService
    );


$postDeadlineRejected =
    false;


try {

    $postDeadlineProductionService
        ->capture(
            $gameweekId,
            $entryId,
            '2026-09-05 11:00:01',
            $deadlineTime,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult
        );

} catch (
    InvalidArgumentException $exception
) {

    $postDeadlineRejected =
        true;
}


productionCandidateAssert(
    $postDeadlineRejected,
    'Post-deadline recommendation is rejected.'
);


productionCandidateAssert(
    $postDeadlineCaptureService
        ->captureCalls
    ===
    0,
    'Post-deadline recommendation cannot write history.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

productionCandidateSection(
    'Recommendation Candidate Production Service Test Summary'
);


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if (
    $failed === 0
) {

    echo "<strong>RESULT: ALL TESTS PASSED ✅</strong><br>";

} else {

    echo "<strong>RESULT: TEST FAILURES DETECTED ❌</strong><br>";
}