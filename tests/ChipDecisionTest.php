<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function chipDecisionCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        return;
    }


    $failed++;

    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';
}


echo
    '============================================<br>';

echo
    'Chip Decision Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


/*
 * ============================================================
 * Scenario A: Use recommendation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Use recommendation<br>';

echo
    '============================================<br>';


$useDecision =
    new ChipDecision(
        'Wildcard',
        'Use',
        0.85,
        'Strong immediate squad improvement.'
    );


chipDecisionCheck(
    'Chip name is preserved',
    $useDecision->getChip()
        ===
        'Wildcard'
);


chipDecisionCheck(
    'Use recommendation is preserved',
    $useDecision->getRecommendation()
        ===
        'Use'
);


chipDecisionCheck(
    'Confidence is preserved',
    abs(
        $useDecision->getConfidence()
        -
        0.85
    ) < 0.0001
);


chipDecisionCheck(
    'Explanation is preserved',
    $useDecision->getExplanation()
        ===
        'Strong immediate squad improvement.'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Consider recommendation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Consider recommendation<br>';

echo
    '============================================<br>';


$considerDecision =
    new ChipDecision(
        'Bench Boost',
        'Consider',
        0.65,
        'Bench projection is useful but not exceptional.'
    );


chipDecisionCheck(
    'Consider recommendation is supported',
    $considerDecision->getRecommendation()
        ===
        'Consider'
);


chipDecisionCheck(
    'Bench Boost chip identity is preserved',
    $considerDecision->getChip()
        ===
        'Bench Boost'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Hold recommendation
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Hold recommendation<br>';

echo
    '============================================<br>';


$holdDecision =
    new ChipDecision(
        'Triple Captain',
        'Hold',
        0.90,
        'A stronger captain opportunity may exist later.'
    );


chipDecisionCheck(
    'Hold recommendation is supported',
    $holdDecision->getRecommendation()
        ===
        'Hold'
);


chipDecisionCheck(
    'Triple Captain chip identity is preserved',
    $holdDecision->getChip()
        ===
        'Triple Captain'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Public decision structure
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Public decision structure<br>';

echo
    '============================================<br>';


$decisionArray =
    $useDecision->toArray();


chipDecisionCheck(
    'Decision converts to an array',
    is_array(
        $decisionArray
    )
);


chipDecisionCheck(
    'Array exposes chip',
    (
        $decisionArray[
            'chip'
        ]
        ??
        null
    )
        ===
        'Wildcard'
);


chipDecisionCheck(
    'Array exposes recommendation',
    (
        $decisionArray[
            'recommendation'
        ]
        ??
        null
    )
        ===
        'Use'
);


chipDecisionCheck(
    'Array exposes confidence',
    abs(
        (
            $decisionArray[
                'confidence'
            ]
            ??
            0
        )
        -
        0.85
    ) < 0.0001
);


chipDecisionCheck(
    'Array exposes explanation',
    (
        $decisionArray[
            'explanation'
        ]
        ??
        null
    )
        ===
        'Strong immediate squad improvement.'
);


echo
    '<br>';


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'TEST SUMMARY<br>';

echo
    '============================================<br>';

echo
    'Passed: '
    . $passed
    . '<br>';

echo
    'Failed: '
    . $failed
    . '<br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}