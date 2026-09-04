<?php

/*
 * ============================================================
 * RECOMMENDATION CANDIDATE PROMOTION CRON TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * This test protects the thin automatic promotion entry point.
 *
 * The cron must:
 *
 * - load the project autoloader
 * - construct the real production dependency chain
 * - use the current UTC time during normal execution
 * - call RecommendationCandidatePromotionRunner exactly once
 * - display ready/promoted/unchanged accounting
 * - validate that ready = promoted + unchanged
 * - handle failures cleanly
 *
 * IMPORTANT:
 *
 * The test must never run the real promotion stack against
 * live recommendation candidates.
 *
 * The cron therefore supports a small controlled test seam:
 *
 * $recommendationPromotionCronRunner
 * $recommendationPromotionCronTimestamp
 *
 * When those variables are supplied before inclusion, the cron
 * uses them instead of constructing/running the live stack.
 *
 * Normal production execution does not define those variables
 * and therefore uses the real production objects.
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


function promotionCronAssert(
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


function promotionCronSection(
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
 * CRON PATH
 * ============================================================
 */

$cronPath =
    __DIR__
    . '/../cron/promoteRecommendationCandidates.php';


/*
 * ============================================================
 * A. CRON FILE EXISTS
 * ============================================================
 */

promotionCronSection(
    'A. Promotion Cron File'
);


$cronExists =
    is_file(
        $cronPath
    );


promotionCronAssert(
    $cronExists,
    'Recommendation candidate promotion cron exists.'
);


if (
    !$cronExists
) {

    /*
     * The first TDD run is expected to stop here because the
     * production cron has not been created yet.
     */

    promotionCronSection(
        'Recommendation Candidate Promotion Cron Test Summary'
    );


    echo "Passed: "
        . $passed
        . "<br>";


    echo "Failed: "
        . $failed
        . "<br>";


    echo "<strong>RESULT: TEST FAILURES DETECTED ❌</strong><br>";

    exit;
}


/*
 * ============================================================
 * READ CRON SOURCE
 * ============================================================
 */

$cronSource =
    file_get_contents(
        $cronPath
    );


if (
    $cronSource === false
) {

    die(
        'Promotion cron source could not be read.'
    );
}


/*
 * ============================================================
 * B. PROJECT BOOTSTRAP
 * ============================================================
 */

promotionCronSection(
    'B. Project Bootstrap'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        "../classes/autoload.php"
    ),
    'Promotion cron loads the project autoloader.'
);


/*
 * ============================================================
 * C. PRODUCTION DEPENDENCY WIRING
 * ============================================================
 */

promotionCronSection(
    'C. Production Dependency Wiring'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        'new Database'
    ),
    'Promotion cron constructs Database during normal execution.'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        'new RecommendationCandidateRepository'
    ),
    'Promotion cron constructs RecommendationCandidateRepository.'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        'new RecommendationSnapshotRepository'
    ),
    'Promotion cron constructs RecommendationSnapshotRepository.'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        'new RecommendationCandidatePromotionService'
    ),
    'Promotion cron constructs RecommendationCandidatePromotionService.'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        'new RecommendationCandidatePromotionRunner'
    ),
    'Promotion cron constructs RecommendationCandidatePromotionRunner.'
);


/*
 * ============================================================
 * D. UTC EXECUTION TIME
 * ============================================================
 */

promotionCronSection(
    'D. UTC Execution Time'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        "gmdate("
    ),
    'Promotion cron derives normal execution time in UTC.'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        "'Y-m-d H:i:s'"
    ),
    'Promotion cron uses MySQL-compatible UTC timestamp format.'
);


/*
 * ============================================================
 * TEST DOUBLE — PROMOTION RUNNER
 * ============================================================
 */

class PromotionCronRunnerDouble
{
    private array $result;

    private array $calls =
        [];


    public function __construct(
        array $result
    ) {

        $this->result =
            $result;
    }


    public function run(
        string $timestamp
    ): array {

        $this->calls[] =
            $timestamp;


        return
            $this->result;
    }


    public function getCalls(): array
    {
        return $this->calls;
    }
}


/*
 * ============================================================
 * E. CONTROLLED SUCCESSFUL EXECUTION
 * ============================================================
 *
 * The controlled runner prevents the test from touching real
 * recommendation history.
 */

promotionCronSection(
    'E. Controlled Successful Execution'
);


$recommendationPromotionCronRunner =
    new PromotionCronRunnerDouble(
        [
            'status' =>
                'Complete',

            'ready' =>
                3,

            'promoted' =>
                2,

            'unchanged' =>
                1
        ]
    );


$recommendationPromotionCronTimestamp =
    '2026-09-04 13:30:00';


ob_start();


include $cronPath;


$successOutput =
    (string) ob_get_clean();


promotionCronAssert(
    $recommendationPromotionCronRunner
        ->getCalls()
    ===
    [
        '2026-09-04 13:30:00'
    ],
    'Promotion cron calls runner exactly once with controlled timestamp.'
);


promotionCronAssert(
    str_contains(
        $successOutput,
        'Recommendation Candidate Promotion'
    ),
    'Promotion cron displays promotion heading.'
);


promotionCronAssert(
    str_contains(
        $successOutput,
        'Candidates Ready: 3'
    ),
    'Promotion cron displays ready candidate count.'
);


promotionCronAssert(
    str_contains(
        $successOutput,
        'Snapshots Promoted: 2'
    ),
    'Promotion cron displays promoted candidate count.'
);


promotionCronAssert(
    str_contains(
        $successOutput,
        'Candidates Unchanged: 1'
    ),
    'Promotion cron displays unchanged candidate count.'
);


promotionCronAssert(
    str_contains(
        $successOutput,
        'RESULT: RECOMMENDATION PROMOTION COMPLETE'
    ),
    'Successful cron execution displays completion result.'
);


/*
 * ============================================================
 * F. ZERO-CANDIDATE EXECUTION
 * ============================================================
 */

promotionCronSection(
    'F. Zero-Candidate Execution'
);


$recommendationPromotionCronRunner =
    new PromotionCronRunnerDouble(
        [
            'status' =>
                'Complete',

            'ready' =>
                0,

            'promoted' =>
                0,

            'unchanged' =>
                0
        ]
    );


$recommendationPromotionCronTimestamp =
    '2026-09-04 13:31:00';


ob_start();


include $cronPath;


$emptyOutput =
    (string) ob_get_clean();


promotionCronAssert(
    $recommendationPromotionCronRunner
        ->getCalls()
    ===
    [
        '2026-09-04 13:31:00'
    ],
    'Zero-candidate cron execution still calls runner exactly once.'
);


promotionCronAssert(
    str_contains(
        $emptyOutput,
        'Candidates Ready: 0'
    ),
    'Zero-candidate cron execution reports zero ready candidates.'
);


promotionCronAssert(
    str_contains(
        $emptyOutput,
        'Snapshots Promoted: 0'
    ),
    'Zero-candidate cron execution reports zero promotions.'
);


promotionCronAssert(
    str_contains(
        $emptyOutput,
        'Candidates Unchanged: 0'
    ),
    'Zero-candidate cron execution reports zero unchanged candidates.'
);


promotionCronAssert(
    str_contains(
        $emptyOutput,
        'RESULT: RECOMMENDATION PROMOTION COMPLETE'
    ),
    'Zero-candidate cron execution is a successful completion.'
);


/*
 * ============================================================
 * G. ACCOUNTING VALIDATION CONTRACT
 * ============================================================
 *
 * The cron must independently verify:
 *
 * ready === promoted + unchanged
 *
 * This protects the operational output from silently reporting
 * an impossible batch result.
 */

promotionCronSection(
    'G. Accounting Validation Contract'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        '$ready'
    ),
    'Promotion cron reads ready accounting.'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        '$promoted'
    ),
    'Promotion cron reads promoted accounting.'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        '$unchanged'
    ),
    'Promotion cron reads unchanged accounting.'
);


promotionCronAssert(
    (
        str_contains(
            $cronSource,
            '$promoted'
        )
        &&
        str_contains(
            $cronSource,
            '$unchanged'
        )
    ),
    'Promotion cron contains accounting components required for balance validation.'
);


/*
 * ============================================================
 * H. FAILURE HANDLING CONTRACT
 * ============================================================
 *
 * We inspect the source contract here rather than deliberately
 * triggering exit(1) from an included cron, because exit would
 * terminate this entire browser test process.
 */

promotionCronSection(
    'H. Failure Handling Contract'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        'catch'
    ),
    'Promotion cron has failure handling.'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        'Throwable'
    ),
    'Promotion cron catches Throwable failures.'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        'RESULT: RECOMMENDATION PROMOTION FAILED'
    ),
    'Promotion cron exposes an explicit failure result.'
);


promotionCronAssert(
    str_contains(
        $cronSource,
        'exit(1)'
    ),
    'Promotion cron returns failure exit status after an exception.'
);


/*
 * ============================================================
 * I. THIN ENTRY-POINT BOUNDARY
 * ============================================================
 */

promotionCronSection(
    'I. Thin Entry-Point Boundary'
);


promotionCronAssert(
    !str_contains(
        $cronSource,
        'INSERT INTO recommendation_snapshots'
    ),
    'Promotion cron does not write recommendation snapshots directly.'
);


promotionCronAssert(
    !str_contains(
        $cronSource,
        'UPDATE recommendation_candidates'
    ),
    'Promotion cron does not mutate recommendation candidates directly.'
);


promotionCronAssert(
    !str_contains(
        $cronSource,
        'DELETE FROM recommendation_candidates'
    ),
    'Promotion cron does not delete recommendation candidates.'
);


promotionCronAssert(
    !str_contains(
        $cronSource,
        'SELECT'
    ),
    'Promotion cron contains no domain SQL.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

promotionCronSection(
    'Recommendation Candidate Promotion Cron Test Summary'
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