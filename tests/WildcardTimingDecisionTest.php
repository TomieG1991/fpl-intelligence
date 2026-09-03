<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function wildcardTimingDecisionCheck(
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
    'Wildcard Timing Decision Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


$intelligence =
    new WildcardTimingIntelligence();


/*
 * ============================================================
 * Scenario A: Strong immediate Wildcard opportunity
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Strong immediate Wildcard opportunity<br>';

echo
    '============================================<br>';


$useDecision =
    $intelligence
        ->createDecision(
            14.0,
            7.0
        );


wildcardTimingDecisionCheck(
    'Wildcard decision returns a ChipDecision',
    $useDecision
        instanceof
        ChipDecision
);


wildcardTimingDecisionCheck(
    'Decision identifies the Wildcard chip',
    $useDecision
        ->getChip()
        ===
        'Wildcard'
);


wildcardTimingDecisionCheck(
    'Strong immediate opportunity recommends Use',
    $useDecision
        ->getRecommendation()
        ===
        'Use'
);


wildcardTimingDecisionCheck(
    'Strong immediate opportunity uses calculated timing confidence',
    abs(
        $useDecision
            ->getConfidence()
        -
        0.7
    ) < 0.0001
);


wildcardTimingDecisionCheck(
    'Use decision provides an explanation',
    trim(
        $useDecision
            ->getExplanation()
    )
        !==
        ''
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario B: Positive but moderate immediate value
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Positive but moderate immediate value<br>';

echo
    '============================================<br>';


$considerDecision =
    $intelligence
        ->createDecision(
            7.0,
            4.0
        );


wildcardTimingDecisionCheck(
    'Moderate immediate opportunity recommends Consider',
    $considerDecision
        ->getRecommendation()
        ===
        'Consider'
);


wildcardTimingDecisionCheck(
    'Consider decision still identifies Wildcard',
    $considerDecision
        ->getChip()
        ===
        'Wildcard'
);


wildcardTimingDecisionCheck(
    'Consider decision provides an explanation',
    trim(
        $considerDecision
            ->getExplanation()
    )
        !==
        ''
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario C: Waiting is projected to be better
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Waiting is projected to be better<br>';

echo
    '============================================<br>';


$waitDecision =
    $intelligence
        ->createDecision(
            6.0,
            11.0
        );


wildcardTimingDecisionCheck(
    'Greater future value recommends Hold',
    $waitDecision
        ->getRecommendation()
        ===
        'Hold'
);


wildcardTimingDecisionCheck(
    'Waiting explanation is provided',
    trim(
        $waitDecision
            ->getExplanation()
    )
        !==
        ''
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario D: Wildcard does not improve squad now
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario D: Wildcard does not improve squad now<br>';

echo
    '============================================<br>';


$noGainDecision =
    $intelligence
        ->createDecision(
            0.0,
            5.0
        );


wildcardTimingDecisionCheck(
    'No immediate improvement recommends Hold',
    $noGainDecision
        ->getRecommendation()
        ===
        'Hold'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario E: Strong gain without enough timing advantage
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Strong gain without enough timing advantage<br>';

echo
    '============================================<br>';


$closeTimingDecision =
    $intelligence
        ->createDecision(
            12.0,
            9.0
        );


wildcardTimingDecisionCheck(
    'Strong gain without clear timing advantage recommends Consider',
    $closeTimingDecision
        ->getRecommendation()
        ===
        'Consider'
);


echo
    '<br>';


/*
 * ============================================================
 * Scenario F: Decision array contract
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Decision array contract<br>';

echo
    '============================================<br>';


$decisionArray =
    $useDecision
        ->toArray();


wildcardTimingDecisionCheck(
    'Wildcard decision exports the common decision fields',
    array_key_exists(
        'chip',
        $decisionArray
    )
    &&
    array_key_exists(
        'recommendation',
        $decisionArray
    )
    &&
    array_key_exists(
        'confidence',
        $decisionArray
    )
    &&
    array_key_exists(
        'explanation',
        $decisionArray
    )
);


wildcardTimingDecisionCheck(
    'Exported recommendation matches the decision object',
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