<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function freeHitDecisionCheck(
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
    'Free Hit Decision Intelligence Test<br>';

echo
    '============================================<br>';

echo
    '<br>';


/*
 * ============================================================
 * SCENARIO A: CLASS CONTRACT
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Free Hit Decision Intelligence Contract<br>';

echo
    '============================================<br>';


$classExists =
    class_exists(
        'FreeHitDecisionIntelligence'
    );


freeHitDecisionCheck(
    'FreeHitDecisionIntelligence class exists',
    $classExists
);


if ($classExists) {

    $intelligence =
        new FreeHitDecisionIntelligence();


    freeHitDecisionCheck(
        'FreeHitDecisionIntelligence can be instantiated',
        $intelligence
        instanceof
        FreeHitDecisionIntelligence
    );


    freeHitDecisionCheck(
        'FreeHitDecisionIntelligence exposes analyseValue',
        method_exists(
            $intelligence,
            'analyseValue'
        )
    );


    freeHitDecisionCheck(
        'FreeHitDecisionIntelligence exposes createDecision',
        method_exists(
            $intelligence,
            'createDecision'
        )
    );

} else {

    freeHitDecisionCheck(
        'FreeHitDecisionIntelligence can be instantiated',
        false
    );


    freeHitDecisionCheck(
        'FreeHitDecisionIntelligence exposes analyseValue',
        false
    );


    freeHitDecisionCheck(
        'FreeHitDecisionIntelligence exposes createDecision',
        false
    );
}


echo
    '<br>';


/*
 * ============================================================
 * FURTHER SCENARIOS REQUIRE CLASS
 * ============================================================
 */

if ($classExists) {

    $intelligence =
        new FreeHitDecisionIntelligence();


    /*
     * ========================================================
     * SCENARIO B: FREE HIT IMPROVES CURRENT STARTING XI
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario B: Free Hit Improves Current Starting XI<br>';

    echo
        '============================================<br>';


    $result =
        $intelligence
            ->analyseValue(
                55.0,
                68.5
            );


    freeHitDecisionCheck(
        'Value analysis returns an array',
        is_array(
            $result
        )
    );


    freeHitDecisionCheck(
        'Current squad projected points are preserved',
        isset(
            $result[
                'current_squad_projected_points'
            ]
        )
        &&
        abs(
            (float) $result[
                'current_squad_projected_points'
            ]
            -
            55.0
        )
        <
        0.0001
    );


    freeHitDecisionCheck(
        'Free Hit projected points are preserved',
        isset(
            $result[
                'free_hit_projected_points'
            ]
        )
        &&
        abs(
            (float) $result[
                'free_hit_projected_points'
            ]
            -
            68.5
        )
        <
        0.0001
    );


    freeHitDecisionCheck(
        'Projected points gain is calculated',
        isset(
            $result[
                'projected_points_gain'
            ]
        )
        &&
        abs(
            (float) $result[
                'projected_points_gain'
            ]
            -
            13.5
        )
        <
        0.0001
    );


    freeHitDecisionCheck(
        'Positive Free Hit gain reports improvement',
        (
            $result[
                'improves_squad'
            ]
            ??
            null
        )
        ===
        true
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO C: FREE HIT DOES NOT IMPROVE CURRENT XI
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario C: Free Hit Does Not Improve Current Starting XI<br>';

    echo
        '============================================<br>';


    $result =
        $intelligence
            ->analyseValue(
                64.0,
                61.5
            );


    freeHitDecisionCheck(
        'Negative Free Hit gain is preserved',
        isset(
            $result[
                'projected_points_gain'
            ]
        )
        &&
        abs(
            (float) $result[
                'projected_points_gain'
            ]
            -
            (-2.5)
        )
        <
        0.0001
    );


    freeHitDecisionCheck(
        'Negative Free Hit gain does not report improvement',
        (
            $result[
                'improves_squad'
            ]
            ??
            null
        )
        ===
        false
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO D: IDENTICAL STARTING XI VALUE
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario D: Identical Starting XI Value<br>';

    echo
        '============================================<br>';


    $result =
        $intelligence
            ->analyseValue(
                60.0,
                60.0
            );


    freeHitDecisionCheck(
        'Equal projected points produce zero gain',
        isset(
            $result[
                'projected_points_gain'
            ]
        )
        &&
        abs(
            (float) $result[
                'projected_points_gain'
            ]
        )
        <
        0.0001
    );


    freeHitDecisionCheck(
        'Zero gain does not report improvement',
        (
            $result[
                'improves_squad'
            ]
            ??
            null
        )
        ===
        false
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO E: INVALID NEGATIVE PROJECTIONS
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario E: Negative Projection Inputs Are Rejected<br>';

    echo
        '============================================<br>';


    $negativeCurrentRejected =
        false;


    try {

        $intelligence
            ->analyseValue(
                -1.0,
                60.0
            );

    } catch (
        InvalidArgumentException $exception
    ) {

        $negativeCurrentRejected =
            true;
    }


    freeHitDecisionCheck(
        'Negative current squad projection is rejected',
        $negativeCurrentRejected
    );


    $negativeFreeHitRejected =
        false;


    try {

        $intelligence
            ->analyseValue(
                60.0,
                -1.0
            );

    } catch (
        InvalidArgumentException $exception
    ) {

        $negativeFreeHitRejected =
            true;
    }


    freeHitDecisionCheck(
        'Negative Free Hit projection is rejected',
        $negativeFreeHitRejected
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO F: HOLD DECISION
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario F: Hold Decision<br>';

    echo
        '============================================<br>';


    $decision =
        $intelligence
            ->createDecision(
                1.0,
                0.80
            );


    freeHitDecisionCheck(
        'Small Free Hit gain returns ChipDecision',
        $decision
        instanceof
        ChipDecision
    );


    freeHitDecisionCheck(
        'Small Free Hit gain identifies Free Hit chip',
        $decision
        instanceof
        ChipDecision
        &&
        $decision->getChip()
        ===
        'Free Hit'
    );


    freeHitDecisionCheck(
        'Small Free Hit gain recommends Hold',
        $decision
        instanceof
        ChipDecision
        &&
        $decision->getRecommendation()
        ===
        'Hold'
    );


    freeHitDecisionCheck(
        'Hold decision preserves supplied confidence',
        $decision
        instanceof
        ChipDecision
        &&
        abs(
            $decision->getConfidence()
            -
            0.80
        )
        <
        0.0001
    );


    freeHitDecisionCheck(
        'Hold decision provides explanation',
        $decision
        instanceof
        ChipDecision
        &&
        trim(
            $decision->getExplanation()
        )
        !==
        ''
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO G: CONSIDER DECISION
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario G: Consider Decision<br>';

    echo
        '============================================<br>';


    $decision =
        $intelligence
            ->createDecision(
                7.0,
                0.75
            );


    freeHitDecisionCheck(
        'Moderate Free Hit gain recommends Consider',
        $decision
        instanceof
        ChipDecision
        &&
        $decision->getRecommendation()
        ===
        'Consider'
    );


    freeHitDecisionCheck(
        'Consider decision preserves supplied confidence',
        $decision
        instanceof
        ChipDecision
        &&
        abs(
            $decision->getConfidence()
            -
            0.75
        )
        <
        0.0001
    );


    freeHitDecisionCheck(
        'Consider decision provides explanation',
        $decision
        instanceof
        ChipDecision
        &&
        trim(
            $decision->getExplanation()
        )
        !==
        ''
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO H: USE DECISION
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario H: Use Decision<br>';

    echo
        '============================================<br>';


    $decision =
        $intelligence
            ->createDecision(
                12.0,
                0.85
            );


    freeHitDecisionCheck(
        'Large Free Hit gain recommends Use',
        $decision
        instanceof
        ChipDecision
        &&
        $decision->getRecommendation()
        ===
        'Use'
    );


    freeHitDecisionCheck(
        'Use decision preserves supplied confidence',
        $decision
        instanceof
        ChipDecision
        &&
        abs(
            $decision->getConfidence()
            -
            0.85
        )
        <
        0.0001
    );


    freeHitDecisionCheck(
        'Use decision provides explanation',
        $decision
        instanceof
        ChipDecision
        &&
        trim(
            $decision->getExplanation()
        )
        !==
        ''
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO I: NON-POSITIVE GAIN ALWAYS HOLDS
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario I: Non-Positive Gain Always Holds<br>';

    echo
        '============================================<br>';


    $zeroGainDecision =
        $intelligence
            ->createDecision(
                0.0,
                0.90
            );


    freeHitDecisionCheck(
        'Zero Free Hit gain recommends Hold',
        $zeroGainDecision
        instanceof
        ChipDecision
        &&
        $zeroGainDecision->getRecommendation()
        ===
        'Hold'
    );


    $negativeGainDecision =
        $intelligence
            ->createDecision(
                -3.0,
                0.90
            );


    freeHitDecisionCheck(
        'Negative Free Hit gain recommends Hold',
        $negativeGainDecision
        instanceof
        ChipDecision
        &&
        $negativeGainDecision->getRecommendation()
        ===
        'Hold'
    );


    echo
        '<br>';


    /*
     * ========================================================
     * SCENARIO J: CONFIDENCE VALIDATION
     * ========================================================
     */

    echo
        '============================================<br>';

    echo
        'Scenario J: Confidence Validation<br>';

    echo
        '============================================<br>';


    $lowConfidenceRejected =
        false;


    try {

        $intelligence
            ->createDecision(
                10.0,
                -0.01
            );

    } catch (
        InvalidArgumentException $exception
    ) {

        $lowConfidenceRejected =
            true;
    }


    freeHitDecisionCheck(
        'Confidence below zero is rejected',
        $lowConfidenceRejected
    );


    $highConfidenceRejected =
        false;


    try {

        $intelligence
            ->createDecision(
                10.0,
                1.01
            );

    } catch (
        InvalidArgumentException $exception
    ) {

        $highConfidenceRejected =
            true;
    }


    freeHitDecisionCheck(
        'Confidence above one is rejected',
        $highConfidenceRejected
    );
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '<br>';

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


if ($failed === 0) {

    echo
        'RESULT: ALL TESTS PASSED ✅<br>';

} else {

    echo
        'RESULT: TESTS FAILED ❌<br>';
}