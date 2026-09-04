<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * CHIP RECOMMENDATION EVIDENCE TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * Recommendation history must preserve the existing four chip
 * decisions as raw historical evidence.
 *
 * This adapter must not:
 *
 * - calculate Chip Intelligence
 * - rank chips against each other
 * - create a fifth overall chip score
 * - format values for presentation
 * - change Use / Consider / Hold recommendations
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


function chipEvidenceAssert(
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


function chipEvidenceSection(
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
 * EXISTING CHIP DECISIONS
 * ============================================================
 */

$wildcardDecision =
    new ChipDecision(
        'Wildcard',
        'Consider',
        0.62,
        'Wildcard timing is worth considering.'
    );


$freeHitDecision =
    new ChipDecision(
        'Free Hit',
        'Hold',
        0.71,
        'Free Hit value is currently limited.'
    );


$benchBoostDecision =
    new ChipDecision(
        'Bench Boost',
        'Use',
        0.83,
        'Projected bench contribution is strong.'
    );


$tripleCaptainDecision =
    new ChipDecision(
        'Triple Captain',
        'Hold',
        0.76,
        'Captain projection does not justify the chip.'
    );


/*
 * ============================================================
 * PRODUCTION-SHAPED RESULT ARRAYS
 * ============================================================
 *
 * These reflect where public/chips.php currently receives the
 * existing ChipDecision objects.
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
            $wildcardDecision
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
        $freeHitDecision
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
        $benchBoostDecision
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
        $tripleCaptainDecision
];


/*
 * ============================================================
 * ADAPTER
 * ============================================================
 */

$adapter =
    new ChipRecommendationEvidence();


/*
 * ============================================================
 * A. BUILD COMPLETE EVIDENCE
 * ============================================================
 */

chipEvidenceSection(
    'A. Build Complete Chip Evidence'
);


$evidence =
    $adapter
        ->build(
            $wildcardResult,
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult
        );


chipEvidenceAssert(
    is_array(
        $evidence
    ),
    'Chip recommendation evidence is returned as an array.'
);


chipEvidenceAssert(
    count(
        $evidence
    )
    ===
    4,
    'Exactly four existing chip recommendations are preserved.'
);


/*
 * ============================================================
 * B. STABLE CHIP KEYS
 * ============================================================
 */

chipEvidenceSection(
    'B. Stable Chip Keys'
);


chipEvidenceAssert(
    array_keys(
        $evidence
    )
    ===
    [
        'wildcard',
        'free_hit',
        'bench_boost',
        'triple_captain'
    ],
    'Evidence exposes stable keys for all four FPL chips.'
);


/*
 * ============================================================
 * C. WILDCARD DECISION
 * ============================================================
 */

chipEvidenceSection(
    'C. Wildcard Decision'
);


chipEvidenceAssert(
    $evidence[
        'wildcard'
    ][
        'decision'
    ]
    ===
    $wildcardDecision->toArray(),
    'Wildcard decision is preserved without reinterpretation.'
);


chipEvidenceAssert(
    $evidence[
        'wildcard'
    ][
        'analysis'
    ]
    ===
    [
        'current_squad_projected_points' =>
            68.25,

        'wildcard_squad_projected_points' =>
            74.50,

        'projected_points_gain' =>
            6.25,

        'future_projected_gain' =>
            10.75,

        'better_timing' =>
            'GW8'
    ],
    'Wildcard supporting evidence is preserved as raw values.'
);


/*
 * ============================================================
 * D. FREE HIT DECISION
 * ============================================================
 */

chipEvidenceSection(
    'D. Free Hit Decision'
);


chipEvidenceAssert(
    $evidence[
        'free_hit'
    ][
        'decision'
    ]
    ===
    $freeHitDecision->toArray(),
    'Free Hit decision is preserved without reinterpretation.'
);


chipEvidenceAssert(
    $evidence[
        'free_hit'
    ][
        'analysis'
    ]
    ===
    $freeHitResult[
        'value_result'
    ],
    'Free Hit supporting evidence is preserved unchanged.'
);


/*
 * ============================================================
 * E. BENCH BOOST DECISION
 * ============================================================
 */

chipEvidenceSection(
    'E. Bench Boost Decision'
);


chipEvidenceAssert(
    $evidence[
        'bench_boost'
    ][
        'decision'
    ]
    ===
    $benchBoostDecision->toArray(),
    'Bench Boost decision is preserved without reinterpretation.'
);


chipEvidenceAssert(
    $evidence[
        'bench_boost'
    ][
        'analysis'
    ]
    ===
    $benchBoostResult[
        'analysis'
    ],
    'Bench Boost supporting evidence is preserved unchanged.'
);


/*
 * ============================================================
 * F. TRIPLE CAPTAIN DECISION
 * ============================================================
 */

chipEvidenceSection(
    'F. Triple Captain Decision'
);


chipEvidenceAssert(
    $evidence[
        'triple_captain'
    ][
        'decision'
    ]
    ===
    $tripleCaptainDecision->toArray(),
    'Triple Captain decision is preserved without reinterpretation.'
);


chipEvidenceAssert(
    $evidence[
        'triple_captain'
    ][
        'analysis'
    ]
    ===
    $tripleCaptainResult[
        'analysis'
    ],
    'Triple Captain supporting evidence is preserved unchanged.'
);


/*
 * ============================================================
 * G. NO PRESENTATION FORMATTING
 * ============================================================
 */

chipEvidenceSection(
    'G. Raw Historical Values'
);


chipEvidenceAssert(
    $evidence[
        'bench_boost'
    ][
        'analysis'
    ][
        'projected_bench_points'
    ]
    ===
    16.25,
    'Projected points remain numeric historical evidence.'
);


chipEvidenceAssert(
    $evidence[
        'wildcard'
    ][
        'analysis'
    ][
        'projected_points_gain'
    ]
    ===
    6.25,
    'Projected gain is not converted to presentation text.'
);


chipEvidenceAssert(
    $evidence[
        'triple_captain'
    ][
        'analysis'
    ][
        'projection_confidence'
    ]
    ===
    0.78,
    'Confidence evidence remains numeric.'
);


/*
 * ============================================================
 * H. MULTIPLE USE DECISIONS ARE NOT RANKED
 * ============================================================
 */

chipEvidenceSection(
    'H. No Cross-Chip Ranking'
);


$secondUseDecision =
    new ChipDecision(
        'Free Hit',
        'Use',
        0.90,
        'Free Hit also independently qualifies for Use.'
    );


$multipleUseEvidence =
    $adapter
        ->build(
            $wildcardResult,
            [
                'value_result' =>
                    $freeHitResult[
                        'value_result'
                    ],

                'decision' =>
                    $secondUseDecision
            ],
            $benchBoostResult,
            $tripleCaptainResult
        );


chipEvidenceAssert(
    $multipleUseEvidence[
        'free_hit'
    ][
        'decision'
    ][
        'recommendation'
    ]
    ===
    'Use',
    'Free Hit Use recommendation remains unchanged.'
);


chipEvidenceAssert(
    $multipleUseEvidence[
        'bench_boost'
    ][
        'decision'
    ][
        'recommendation'
    ]
    ===
    'Use',
    'Bench Boost Use recommendation remains unchanged.'
);


chipEvidenceAssert(
    !array_key_exists(
        'recommended_chip',
        $multipleUseEvidence
    ),
    'Adapter does not invent a preferred chip.'
);


chipEvidenceAssert(
    !array_key_exists(
        'overall_score',
        $multipleUseEvidence
    ),
    'Adapter does not create a cross-chip score.'
);


/*
 * ============================================================
 * I. INVALID CHIP DECISION REJECTED
 * ============================================================
 */

chipEvidenceSection(
    'I. Invalid Chip Decision'
);


$invalidRejected =
    false;


try {

    $adapter
        ->build(
            $wildcardResult,
            [
                'value_result' =>
                    $freeHitResult[
                        'value_result'
                    ],

                'decision' =>
                    null
            ],
            $benchBoostResult,
            $tripleCaptainResult
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidRejected =
        true;
}


chipEvidenceAssert(
    $invalidRejected,
    'Missing existing ChipDecision evidence is rejected.'
);


/*
 * ============================================================
 * J. WILDCARD DECISION LOCATION
 * ============================================================
 */

chipEvidenceSection(
    'J. Wildcard Production Contract'
);


$invalidWildcardRejected =
    false;


try {

    $adapter
        ->build(
            [
                'decision' =>
                    $wildcardDecision
            ],
            $freeHitResult,
            $benchBoostResult,
            $tripleCaptainResult
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidWildcardRejected =
        true;
}


chipEvidenceAssert(
    $invalidWildcardRejected,
    'Wildcard decision must come from the existing timing_result contract.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

chipEvidenceSection(
    'Chip Recommendation Evidence Test Summary'
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