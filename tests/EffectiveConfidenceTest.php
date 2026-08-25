<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Effective Confidence Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function effectiveConfidenceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


/*
 * ============================================================
 * SCENARIO A
 * SETUP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Setup<br>";
echo "============================================<br>";


$performance =
    new PlayerPerformance();


effectiveConfidenceCheck(
    'Player Performance model is available',
    $performance instanceof PlayerPerformance
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * EFFECTIVE CONFIDENCE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Effective Confidence Contract<br>";
echo "============================================<br>";


$methodAvailable =
    method_exists(
        $performance,
        'calculateEffectiveConfidence'
    );


effectiveConfidenceCheck(
    'Player Performance exposes calculateEffectiveConfidence()',
    $methodAvailable
);


if (
    !$methodAvailable
) {

    echo "<br>";
    echo "Effective Confidence has not been implemented yet.<br>";
    echo "The remaining scenarios will run after the new method is added.<br><br>";


    echo "============================================<br>";
    echo "Effective Confidence Test Summary<br>";
    echo "============================================<br>";


    echo "Passed: "
        . $passed
        . "<br>";


    echo "Failed: "
        . $failed
        . "<br><br>";


    echo "RESULT: TESTS FAILED ❌";

    exit;
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * EXISTING SAMPLE CONFIDENCE PRESERVED
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Existing Sample Confidence Preserved<br>";
echo "============================================<br>";


$sample90 =
    $performance
        ->calculateSampleConfidence(
            90
        );


effectiveConfidenceCheck(
    'Existing 90-minute sample confidence remains 10%',
    abs(
        $sample90
        -
        0.10
    )
    <
    0.0001
);


echo "90-Minute Sample Confidence: "
    . number_format(
        $sample90
        *
        100,
        1
    )
    . "%<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * FULL PARTICIPATION IN EARLY SEASON
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Full Early-Season Participation<br>";
echo "============================================<br>";


$fullStarter =
    $performance
        ->calculateEffectiveConfidence(
            90,
            90
        );


effectiveConfidenceCheck(
    '90 of 90 available minutes produces numeric effective confidence',
    is_numeric(
        $fullStarter
    )
);


effectiveConfidenceCheck(
    '90-minute starter receives materially higher confidence than raw 10% sample confidence',
    is_numeric(
        $fullStarter
    )
    &&
    $fullStarter
    >
    $sample90
);


effectiveConfidenceCheck(
    '90 of 90 available minutes produces 64% effective confidence',
    is_numeric(
        $fullStarter
    )
    &&
    abs(
        $fullStarter
        -
        0.64
    )
    <
    0.0001
);


echo "90 / 90 Effective Confidence: "
    . number_format(
        $fullStarter
        *
        100,
        1
    )
    . "%<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * PARTIAL PARTICIPATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Partial Participation<br>";
echo "============================================<br>";


$halfMatch =
    $performance
        ->calculateEffectiveConfidence(
            45,
            90
        );


$shortCameo =
    $performance
        ->calculateEffectiveConfidence(
            5,
            90
        );


effectiveConfidenceCheck(
    '45 of 90 available minutes produces approximately 32% effective confidence',
    is_numeric(
        $halfMatch
    )
    &&
    abs(
        $halfMatch
        -
        0.32
    )
    <
    0.0001
);


effectiveConfidenceCheck(
    'Five-minute cameo retains very low effective confidence',
    is_numeric(
        $shortCameo
    )
    &&
    $shortCameo
    <
    0.05
);


effectiveConfidenceCheck(
    'Full-match starter has higher effective confidence than half-match player',
    $fullStarter
    >
    $halfMatch
);


effectiveConfidenceCheck(
    'Half-match player has higher effective confidence than short cameo',
    $halfMatch
    >
    $shortCameo
);


echo "45 / 90 Effective Confidence: "
    . number_format(
        $halfMatch
        *
        100,
        1
    )
    . "%<br>";


echo "5 / 90 Effective Confidence: "
    . number_format(
        $shortCameo
        *
        100,
        1
    )
    . "%<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * ZERO PARTICIPATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Zero Participation<br>";
echo "============================================<br>";


$zeroParticipation =
    $performance
        ->calculateEffectiveConfidence(
            0,
            90
        );


effectiveConfidenceCheck(
    'Zero minutes from available team minutes produces zero effective confidence',
    is_numeric(
        $zeroParticipation
    )
    &&
    abs(
        $zeroParticipation
    )
    <
    0.0001
);


echo "0 / 90 Effective Confidence: "
    . number_format(
        $zeroParticipation
        *
        100,
        1
    )
    . "%<br><br>";


/*
 * ============================================================
 * SCENARIO G
 * TEAM HAS NOT PLAYED
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: No Team Participation Evidence<br>";
echo "============================================<br>";


$noTeamEvidence =
    $performance
        ->calculateEffectiveConfidence(
            0,
            0
        );


effectiveConfidenceCheck(
    'No available team minutes returns null effective confidence',
    $noTeamEvidence === null
);


echo "0 / 0 Effective Confidence: "
    . (
        $noTeamEvidence === null
            ? 'N/A'
            : number_format(
                $noTeamEvidence
                *
                100,
                1
            )
            . '%'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * MATURE SAMPLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Mature Sample<br>";
echo "============================================<br>";


$matureStarter =
    $performance
        ->calculateEffectiveConfidence(
            900,
            900
        );


effectiveConfidenceCheck(
    '900 of 900 available minutes produces full effective confidence',
    is_numeric(
        $matureStarter
    )
    &&
    abs(
        $matureStarter
        -
        1.00
    )
    <
    0.0001
);


echo "900 / 900 Effective Confidence: "
    . number_format(
        $matureStarter
        *
        100,
        1
    )
    . "%<br><br>";


/*
 * ============================================================
 * SCENARIO I
 * MID-SEASON PARTIAL PARTICIPATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Mid-Season Partial Participation<br>";
echo "============================================<br>";


$midSeasonHalf =
    $performance
        ->calculateEffectiveConfidence(
            450,
            900
        );


effectiveConfidenceCheck(
    '450 of 900 available minutes produces 50% effective confidence',
    is_numeric(
        $midSeasonHalf
    )
    &&
    abs(
        $midSeasonHalf
        -
        0.50
    )
    <
    0.0001
);


echo "450 / 900 Effective Confidence: "
    . number_format(
        $midSeasonHalf
        *
        100,
        1
    )
    . "%<br><br>";


/*
 * ============================================================
 * SCENARIO J
 * SCORE BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Score Bounds<br>";
echo "============================================<br>";


$testCases = [

    [
        0,
        90
    ],

    [
        5,
        90
    ],

    [
        45,
        90
    ],

    [
        90,
        90
    ],

    [
        180,
        180
    ],

    [
        450,
        900
    ],

    [
        900,
        900
    ],

    [
        1000,
        1000
    ]
];


$allBounded =
    true;


foreach (
    $testCases
    as $testCase
) {

    $result =
        $performance
            ->calculateEffectiveConfidence(
                $testCase[
                    0
                ],
                $testCase[
                    1
                ]
            );


    if (
        !is_numeric(
            $result
        )
        ||
        $result < 0
        ||
        $result > 1
    ) {

        $allBounded =
            false;

        break;
    }
}


effectiveConfidenceCheck(
    'All effective confidence results remain between 0 and 1',
    $allBounded
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * ORDERING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Participation Ordering<br>";
echo "============================================<br>";


$confidence10 =
    $performance
        ->calculateEffectiveConfidence(
            10,
            90
        );


$confidence30 =
    $performance
        ->calculateEffectiveConfidence(
            30,
            90
        );


$confidence60 =
    $performance
        ->calculateEffectiveConfidence(
            60,
            90
        );


$confidence90 =
    $performance
        ->calculateEffectiveConfidence(
            90,
            90
        );


effectiveConfidenceCheck(
    'Greater participation produces progressively greater effective confidence',
    $confidence10
    <
    $confidence30
    &&
    $confidence30
    <
    $confidence60
    &&
    $confidence60
    <
    $confidence90
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Effective Confidence Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}