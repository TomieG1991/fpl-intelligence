<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * RECOMMENDATION CANDIDATE PRODUCTION CAPTURE TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * This test defines the boundary between production page
 * orchestration and Recommendation Candidate generation.
 *
 * Its responsibility is deliberately narrow:
 *
 * 1. identify the first gameweek deadline still in the future;
 * 2. pass that historical identity to the existing
 *    RecommendationCandidateProductionService;
 * 3. preserve the already-calculated production evidence;
 * 4. refuse preview / integration entry IDs;
 * 5. do nothing when no future gameweek exists.
 *
 * This class must NOT calculate:
 *
 * - Player Intelligence
 * - Expected Points
 * - Gameweek Intelligence
 * - Captain Intelligence
 * - Transfer Intelligence
 * - Chip Intelligence
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


function productionCaptureAssert(
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


function productionCaptureSection(
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
 * TEST DOUBLE — GAMEWEEK REPOSITORY
 * ============================================================
 *
 * Exposes only the deadline lookup that this coordinator is
 * allowed to use.
 */

class ProductionCaptureGameweekRepository
{
    public int $lookupCalls =
        0;


    public ?string $lastTimestamp =
        null;


    public ?array $result;


    public function __construct(
        ?array $result
    ) {

        $this->result =
            $result;
    }


    public function getNextDeadlineAfter(
        string $timestamp
    ): ?array {

        $this->lookupCalls++;


        $this->lastTimestamp =
            $timestamp;


        return
            $this->result;
    }
}


/*
 * ============================================================
 * TEST DOUBLE — PRODUCTION SERVICE
 * ============================================================
 *
 * Records exactly what the coordinator delegates.
 */

class ProductionCaptureRecommendationService
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
        array $importedSquad,
        array $wildcardResult,
        array $freeHitResult,
        array $benchBoostResult,
        array $tripleCaptainResult
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

            'imported_squad' =>
                $importedSquad,

            'wildcard_result' =>
                $wildcardResult,

            'free_hit_result' =>
                $freeHitResult,

            'bench_boost_result' =>
                $benchBoostResult,

            'triple_captain_result' =>
                $tripleCaptainResult
        ];


        return
            $this->result;
    }
}


/*
 * ============================================================
 * CONTROLLED PRODUCTION EVIDENCE
 * ============================================================
 */

$entryId =
    2702264;


$generatedAt =
    '2026-09-04 10:00:00';


$targetGameweek = [

    'id' =>
        7,

    'fpl_gameweek_id' =>
        3,

    'name' =>
        'Gameweek 3',

    'deadline_time' =>
        '2026-09-05 11:00:00',

    'is_current' =>
        0,

    'is_next' =>
        1
];


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

    'players' => [

        [
            'fpl_player_id' =>
                1001,

            'squad_position' =>
                1
        ],

        [
            'fpl_player_id' =>
                1002,

            'squad_position' =>
                2
        ]
    ]
];


$wildcardResult = [

    'timing_result' => [

        'projected_points_gain' =>
            6.25,

        'decision' =>
            'existing-wildcard-decision'
    ]
];


$freeHitResult = [

    'value_result' => [

        'projected_points_gain' =>
            4.50
    ],

    'decision' =>
        'existing-free-hit-decision'
];


$benchBoostResult = [

    'analysis' => [

        'projected_bench_points' =>
            16.25
    ],

    'decision' =>
        'existing-bench-boost-decision'
];


$tripleCaptainResult = [

    'analysis' => [

        'projected_points' =>
            9.75
    ],

    'decision' =>
        'existing-triple-captain-decision'
];


/*
 * ============================================================
 * A. CLASS CONTRACT
 * ============================================================
 */

productionCaptureSection(
    'A. Production Capture Contract'
);


$classExists =
    class_exists(
        'RecommendationCandidateProductionCapture'
    );


productionCaptureAssert(
    $classExists,
    'RecommendationCandidateProductionCapture exists.'
);


if (
    !$classExists
) {

    echo "<br>";
    echo "<strong>EXPECTED RED: production capture coordinator does not exist yet.</strong><br>";

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
 * B. VALID REAL ENTRY
 * ============================================================
 */

productionCaptureSection(
    'B. Valid Real Entry'
);


$gameweekRepository =
    new ProductionCaptureGameweekRepository(
        $targetGameweek
    );


$productionService =
    new ProductionCaptureRecommendationService();


$capture =
    new RecommendationCandidateProductionCapture(
        $gameweekRepository,
        $productionService
    );


$result =
    $capture
        ->capture(
            $entryId,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult,
            $generatedAt
        );


productionCaptureAssert(
    $result === true,
    'Valid real entry delegates recommendation candidate capture.'
);


productionCaptureAssert(
    $gameweekRepository
        ->lookupCalls
    ===
    1,
    'Upcoming recommendation gameweek is resolved exactly once.'
);


productionCaptureAssert(
    $productionService
        ->captureCalls
    ===
    1,
    'Production recommendation service is called exactly once.'
);


/*
 * ============================================================
 * C. GENERATION TIME DRIVES GAMEWEEK LOOKUP
 * ============================================================
 */

productionCaptureSection(
    'C. Generation Time'
);


productionCaptureAssert(
    $gameweekRepository
        ->lastTimestamp
    ===
    $generatedAt,
    'Generation timestamp is used for deadline lookup.'
);


/*
 * ============================================================
 * D. LOCAL GAMEWEEK ID
 * ============================================================
 */

productionCaptureSection(
    'D. Local Gameweek Identity'
);


productionCaptureAssert(
    $productionService
        ->lastCapture[
            'gameweek_id'
        ]
    ===
    7,
    'Resolved local gameweeks.id is delegated unchanged.'
);


/*
 * ============================================================
 * E. STORED DEADLINE
 * ============================================================
 */

productionCaptureSection(
    'E. Authoritative Deadline'
);


productionCaptureAssert(
    $productionService
        ->lastCapture[
            'deadline_time'
        ]
    ===
    '2026-09-05 11:00:00',
    'Stored gameweek deadline is delegated unchanged.'
);


productionCaptureAssert(
    $productionService
        ->lastCapture[
            'generated_at'
        ]
    ===
    $generatedAt,
    'Original generation timestamp is delegated unchanged.'
);


/*
 * ============================================================
 * F. ENTRY ID
 * ============================================================
 */

productionCaptureSection(
    'F. FPL Entry Identity'
);


productionCaptureAssert(
    $productionService
        ->lastCapture[
            'entry_id'
        ]
    ===
    $entryId,
    'Real FPL entry ID is delegated unchanged.'
);


/*
 * ============================================================
 * G. IMPORTED SQUAD
 * ============================================================
 */

productionCaptureSection(
    'G. Existing Imported Squad'
);


productionCaptureAssert(
    $productionService
        ->lastCapture[
            'imported_squad'
        ]
    ===
    $importedSquad,
    'Existing imported squad is delegated unchanged.'
);


/*
 * ============================================================
 * H. EXISTING CHIP RESULTS
 * ============================================================
 */

productionCaptureSection(
    'H. Existing Chip Results'
);


productionCaptureAssert(
    $productionService
        ->lastCapture[
            'wildcard_result'
        ]
    ===
    $wildcardResult,
    'Existing Wildcard result is delegated unchanged.'
);


productionCaptureAssert(
    $productionService
        ->lastCapture[
            'free_hit_result'
        ]
    ===
    $freeHitResult,
    'Existing Free Hit result is delegated unchanged.'
);


productionCaptureAssert(
    $productionService
        ->lastCapture[
            'bench_boost_result'
        ]
    ===
    $benchBoostResult,
    'Existing Bench Boost result is delegated unchanged.'
);


productionCaptureAssert(
    $productionService
        ->lastCapture[
            'triple_captain_result'
        ]
    ===
    $tripleCaptainResult,
    'Existing Triple Captain result is delegated unchanged.'
);


/*
 * ============================================================
 * I. PRODUCTION SERVICE RESULT
 * ============================================================
 */

productionCaptureSection(
    'I. Production Service Result'
);


$falseRepository =
    new ProductionCaptureGameweekRepository(
        $targetGameweek
    );


$falseProductionService =
    new ProductionCaptureRecommendationService();


$falseProductionService
    ->result =
    false;


$falseCapture =
    new RecommendationCandidateProductionCapture(
        $falseRepository,
        $falseProductionService
    );


$falseResult =
    $falseCapture
        ->capture(
            $entryId,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult,
            $generatedAt
        );


productionCaptureAssert(
    $falseResult === false,
    'Production service false result is preserved.'
);


productionCaptureAssert(
    $falseProductionService
        ->captureCalls
    ===
    1,
    'Production service is still called once when it returns false.'
);


/*
 * ============================================================
 * J. PREVIEW / INTEGRATION ENTRY PROTECTION
 * ============================================================
 */

productionCaptureSection(
    'J. Preview And Integration Protection'
);


$previewRepository =
    new ProductionCaptureGameweekRepository(
        $targetGameweek
    );


$previewProductionService =
    new ProductionCaptureRecommendationService();


$previewCapture =
    new RecommendationCandidateProductionCapture(
        $previewRepository,
        $previewProductionService
    );


$previewRejected =
    false;


try {

    $previewCapture
        ->capture(
            0,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult,
            $generatedAt
        );

} catch (
    InvalidArgumentException $exception
) {

    $previewRejected =
        true;
}


productionCaptureAssert(
    $previewRejected,
    'Entry ID zero is rejected before recommendation capture.'
);


productionCaptureAssert(
    $previewRepository
        ->lookupCalls
    ===
    0,
    'Preview entry does not perform historical gameweek lookup.'
);


productionCaptureAssert(
    $previewProductionService
        ->captureCalls
    ===
    0,
    'Preview entry cannot write recommendation history.'
);


/*
 * ============================================================
 * K. NEGATIVE ENTRY PROTECTION
 * ============================================================
 */

productionCaptureSection(
    'K. Invalid Entry Protection'
);


$negativeRepository =
    new ProductionCaptureGameweekRepository(
        $targetGameweek
    );


$negativeProductionService =
    new ProductionCaptureRecommendationService();


$negativeCapture =
    new RecommendationCandidateProductionCapture(
        $negativeRepository,
        $negativeProductionService
    );


$negativeRejected =
    false;


try {

    $negativeCapture
        ->capture(
            -10,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult,
            $generatedAt
        );

} catch (
    InvalidArgumentException $exception
) {

    $negativeRejected =
        true;
}


productionCaptureAssert(
    $negativeRejected,
    'Negative FPL entry ID is rejected.'
);


productionCaptureAssert(
    $negativeRepository
        ->lookupCalls
    ===
    0,
    'Invalid entry does not perform gameweek lookup.'
);


productionCaptureAssert(
    $negativeProductionService
        ->captureCalls
    ===
    0,
    'Invalid entry cannot write recommendation history.'
);


/*
 * ============================================================
 * L. NO FUTURE GAMEWEEK
 * ============================================================
 */

productionCaptureSection(
    'L. No Future Gameweek'
);


$noFutureRepository =
    new ProductionCaptureGameweekRepository(
        null
    );


$noFutureProductionService =
    new ProductionCaptureRecommendationService();


$noFutureCapture =
    new RecommendationCandidateProductionCapture(
        $noFutureRepository,
        $noFutureProductionService
    );


$noFutureResult =
    $noFutureCapture
        ->capture(
            $entryId,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult,
            $generatedAt
        );


productionCaptureAssert(
    $noFutureResult === false,
    'No future gameweek returns false without creating a candidate.'
);


productionCaptureAssert(
    $noFutureRepository
        ->lookupCalls
    ===
    1,
    'Future deadline lookup is attempted once.'
);


productionCaptureAssert(
    $noFutureProductionService
        ->captureCalls
    ===
    0,
    'No future gameweek cannot write recommendation history.'
);


/*
 * ============================================================
 * M. INVALID RESOLVED GAMEWEEK ID
 * ============================================================
 */

productionCaptureSection(
    'M. Invalid Resolved Gameweek Identity'
);


$invalidGameweek = [

    'id' =>
        0,

    'fpl_gameweek_id' =>
        3,

    'deadline_time' =>
        '2026-09-05 11:00:00'
];


$invalidGameweekRepository =
    new ProductionCaptureGameweekRepository(
        $invalidGameweek
    );


$invalidGameweekProductionService =
    new ProductionCaptureRecommendationService();


$invalidGameweekCapture =
    new RecommendationCandidateProductionCapture(
        $invalidGameweekRepository,
        $invalidGameweekProductionService
    );


$invalidGameweekRejected =
    false;


try {

    $invalidGameweekCapture
        ->capture(
            $entryId,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult,
            $generatedAt
        );

} catch (
    RuntimeException $exception
) {

    $invalidGameweekRejected =
        true;
}


productionCaptureAssert(
    $invalidGameweekRejected,
    'Invalid resolved local gameweek ID is rejected.'
);


productionCaptureAssert(
    $invalidGameweekProductionService
        ->captureCalls
    ===
    0,
    'Invalid resolved gameweek cannot write recommendation history.'
);


/*
 * ============================================================
 * N. MISSING RESOLVED DEADLINE
 * ============================================================
 */

productionCaptureSection(
    'N. Missing Resolved Deadline'
);


$missingDeadlineGameweek = [

    'id' =>
        7,

    'fpl_gameweek_id' =>
        3,

    'deadline_time' =>
        null
];


$missingDeadlineRepository =
    new ProductionCaptureGameweekRepository(
        $missingDeadlineGameweek
    );


$missingDeadlineProductionService =
    new ProductionCaptureRecommendationService();


$missingDeadlineCapture =
    new RecommendationCandidateProductionCapture(
        $missingDeadlineRepository,
        $missingDeadlineProductionService
    );


$missingDeadlineRejected =
    false;


try {

    $missingDeadlineCapture
        ->capture(
            $entryId,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult,
            $generatedAt
        );

} catch (
    RuntimeException $exception
) {

    $missingDeadlineRejected =
        true;
}


productionCaptureAssert(
    $missingDeadlineRejected,
    'Resolved gameweek without a deadline is rejected.'
);


productionCaptureAssert(
    $missingDeadlineProductionService
        ->captureCalls
    ===
    0,
    'Gameweek without deadline cannot write recommendation history.'
);


/*
 * ============================================================
 * O. INVALID RESOLVED DEADLINE
 * ============================================================
 */

productionCaptureSection(
    'O. Invalid Resolved Deadline'
);


$invalidDeadlineGameweek = [

    'id' =>
        7,

    'fpl_gameweek_id' =>
        3,

    'deadline_time' =>
        'not-a-date'
];


$invalidDeadlineRepository =
    new ProductionCaptureGameweekRepository(
        $invalidDeadlineGameweek
    );


$invalidDeadlineProductionService =
    new ProductionCaptureRecommendationService();


$invalidDeadlineCapture =
    new RecommendationCandidateProductionCapture(
        $invalidDeadlineRepository,
        $invalidDeadlineProductionService
    );


$invalidDeadlineRejected =
    false;


try {

    $invalidDeadlineCapture
        ->capture(
            $entryId,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult,
            $generatedAt
        );

} catch (
    RuntimeException $exception
) {

    $invalidDeadlineRejected =
        true;
}


productionCaptureAssert(
    $invalidDeadlineRejected,
    'Invalid resolved gameweek deadline is rejected.'
);


productionCaptureAssert(
    $invalidDeadlineProductionService
        ->captureCalls
    ===
    0,
    'Invalid gameweek deadline cannot write recommendation history.'
);


/*
 * ============================================================
 * P. INVALID GENERATION TIMESTAMP
 * ============================================================
 */

productionCaptureSection(
    'P. Invalid Generation Timestamp'
);


$invalidTimeRepository =
    new ProductionCaptureGameweekRepository(
        $targetGameweek
    );


$invalidTimeProductionService =
    new ProductionCaptureRecommendationService();


$invalidTimeCapture =
    new RecommendationCandidateProductionCapture(
        $invalidTimeRepository,
        $invalidTimeProductionService
    );


$invalidTimeRejected =
    false;


try {

    $invalidTimeCapture
        ->capture(
            $entryId,
            $importedSquad,
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult,
            'not-a-date'
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidTimeRejected =
        true;
}


productionCaptureAssert(
    $invalidTimeRejected,
    'Invalid generation timestamp is rejected.'
);


productionCaptureAssert(
    $invalidTimeRepository
        ->lookupCalls
    ===
    0,
    'Invalid generation timestamp does not perform gameweek lookup.'
);


productionCaptureAssert(
    $invalidTimeProductionService
        ->captureCalls
    ===
    0,
    'Invalid generation timestamp cannot write recommendation history.'
);


/*
 * ============================================================
 * Q. NO INTELLIGENCE CALCULATION RESPONSIBILITY
 * ============================================================
 *
 * The coordinator receives all recommendation evidence as
 * inputs. Its dependencies expose only deadline resolution and
 * candidate delegation.
 *
 * Successful completion therefore demonstrates that this
 * coordinator requires no Player / Gameweek / Chip calculation
 * dependency of its own.
 */

productionCaptureSection(
    'Q. No Intelligence Recalculation'
);


productionCaptureAssert(
    $gameweekRepository
        ->lookupCalls
    ===
    1
    &&
    $productionService
        ->captureCalls
    ===
    1,
    'Coordinator performs only gameweek resolution and production capture delegation.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

productionCaptureSection(
    'Recommendation Candidate Production Capture Test Summary'
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