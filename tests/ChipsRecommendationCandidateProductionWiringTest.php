<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Chips Recommendation Candidate Production Wiring Test<br>";
echo "============================================<br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function chipsRecommendationWiringResult(
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


/*
 * ============================================================
 * LOAD PRODUCTION PAGE SOURCE
 * ============================================================
 *
 * This is deliberately a narrow wiring regression test.
 *
 * RecommendationCandidateProductionService is already protected
 * independently by its behavioural unit tests.
 *
 * The regression discovered in production is specifically that
 * chips.php did not keep its constructor wiring in sync when
 * PlayerRankingEvidence became a required dependency.
 */

$chipsPagePath =
    __DIR__
    . '/../public/chips.php';


echo "<br>";
echo "============================================<br>";
echo "A. Production Page<br>";
echo "============================================<br>";


chipsRecommendationWiringResult(
    is_file(
        $chipsPagePath
    ),
    'public/chips.php exists.'
);


$chipsSource =
    is_file(
        $chipsPagePath
    )
        ? file_get_contents(
            $chipsPagePath
        )
        : false;


chipsRecommendationWiringResult(
    is_string(
        $chipsSource
    )
    &&
    $chipsSource !== '',
    'public/chips.php source can be inspected.'
);


/*
 * ============================================================
 * B. RECOMMENDATION PRODUCTION SERVICE WIRING
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "B. Recommendation Production Wiring<br>";
echo "============================================<br>";


if (
    !is_string(
        $chipsSource
    )
) {

    $chipsSource =
        '';
}


chipsRecommendationWiringResult(
    strpos(
        $chipsSource,
        'new RecommendationCandidateProductionService('
    )
    !==
    false,
    'chips.php constructs RecommendationCandidateProductionService.'
);


chipsRecommendationWiringResult(
    strpos(
        $chipsSource,
        'new PlayerRankingEvidence()'
    )
    !==
    false,
    'chips.php supplies PlayerRankingEvidence to recommendation candidate production.'
);


/*
 * ============================================================
 * C. DEPENDENCY ORDER
 * ============================================================
 *
 * Constructor contract:
 *
 * 1. PlayerIntelligenceService
 * 2. PlayerRankingEvidence
 * 3. PlayerProjectionEvidence
 * 4. ChipRecommendationEvidence
 * 5. Capture service
 *
 * Protect the production composition root from silently drifting
 * behind the service constructor again.
 */

echo "<br>";
echo "============================================<br>";
echo "C. Recommendation Dependency Order<br>";
echo "============================================<br>";


$productionServicePattern =
    '/new\s+RecommendationCandidateProductionService\s*'
    . '\(\s*'
    . '\$playerIntelligenceService\s*,\s*'
    . 'new\s+PlayerRankingEvidence\s*\(\s*\)\s*,\s*'
    . 'new\s+PlayerProjectionEvidence\s*\(\s*\)\s*,\s*'
    . 'new\s+ChipRecommendationEvidence\s*\(\s*\)\s*,\s*'
    . '\$recommendationCandidateCaptureService\s*'
    . '\)/s';


chipsRecommendationWiringResult(
    preg_match(
        $productionServicePattern,
        $chipsSource
    )
    ===
    1,
    'chips.php supplies all recommendation production dependencies in constructor order.'
);


/*
 * ============================================================
 * D. PRODUCTION CAPTURE WIRING REMAINS PRESENT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "D. Production Capture Wiring<br>";
echo "============================================<br>";


chipsRecommendationWiringResult(
    strpos(
        $chipsSource,
        'new RecommendationCandidateProductionCapture('
    )
    !==
    false,
    'chips.php still constructs RecommendationCandidateProductionCapture.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Chips Recommendation Candidate Production Wiring Test Summary<br>";
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