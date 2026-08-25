<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Captain Effective Confidence Test<br>";
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

function captainEffectiveCheck(
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


$captainIntelligence =
    new CaptainIntelligence();


captainEffectiveCheck(
    'Captain Intelligence model is available',
    $captainIntelligence instanceof CaptainIntelligence
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * EXISTING SAMPLE CONFIDENCE BASELINE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Existing Sample Confidence Baseline<br>";
echo "============================================<br>";


$basePlayer = [

    'player_id' =>
        1,

    'name' =>
        'Effective Confidence Player',

    'position' =>
        'MID',

    'strength_score' =>
        60.0,

    'fixture_score' =>
        70.0,

    'sample_confidence' =>
        0.10,

    'availability' =>
        1.00,

    'adjusted_goals_rating' =>
        55.0,

    'adjusted_assists_rating' =>
        55.0,

    'adjusted_expected_goals_rating' =>
        55.0,

    'adjusted_expected_assists_rating' =>
        55.0
];


$sampleOnlyResult =
    $captainIntelligence
        ->evaluate(
            $basePlayer
        );


captainEffectiveCheck(
    'Sample-confidence-only captain evaluation succeeds',
    (
        $sampleOnlyResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


$sampleOnlyModifier =
    $sampleOnlyResult[
        'components'
    ][
        'confidence_modifier'
    ]
    ?? null;


captainEffectiveCheck(
    'Low raw sample confidence produces a penalty',
    is_numeric(
        $sampleOnlyModifier
    )
    &&
    (float) $sampleOnlyModifier < 0.80
);


echo "Sample Confidence Modifier: "
    . number_format(
        (float) $sampleOnlyModifier,
        3
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * EFFECTIVE CONFIDENCE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Effective Confidence Contract<br>";
echo "============================================<br>";


$effectivePlayer =
    $basePlayer;


$effectivePlayer[
    'effective_confidence'
] =
    0.64;


$effectiveResult =
    $captainIntelligence
        ->evaluate(
            $effectivePlayer
        );


captainEffectiveCheck(
    'Effective-confidence captain evaluation succeeds',
    (
        $effectiveResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


$effectiveModifier =
    $effectiveResult[
        'components'
    ][
        'confidence_modifier'
    ]
    ?? null;


$effectiveComponent =
    $effectiveResult[
        'components'
    ][
        'confidence'
    ]
    ?? null;


captainEffectiveCheck(
    'Captain Intelligence prefers Effective Confidence over raw sample confidence',
    is_numeric(
        $effectiveComponent
    )
    &&
    abs(
        (float) $effectiveComponent
        -
        64.0
    )
    <
    0.01
);


captainEffectiveCheck(
    '64% Effective Confidence produces a healthier modifier than 10% raw sample confidence',
    is_numeric(
        $effectiveModifier
    )
    &&
    is_numeric(
        $sampleOnlyModifier
    )
    &&
    (float) $effectiveModifier
    >
    (float) $sampleOnlyModifier
);


echo "Effective Confidence Component: "
    . number_format(
        (float) $effectiveComponent,
        1
    )
    . "%<br>";


echo "Effective Confidence Modifier: "
    . number_format(
        (float) $effectiveModifier,
        3
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * SHORT CAMEO REMAINS LOW CONFIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Short Cameo Behaviour<br>";
echo "============================================<br>";


$cameoPlayer =
    $basePlayer;


$cameoPlayer[
    'sample_confidence'
] =
    0.0056;


$cameoPlayer[
    'effective_confidence'
] =
    0.036;


$cameoResult =
    $captainIntelligence
        ->evaluate(
            $cameoPlayer
        );


$cameoModifier =
    $cameoResult[
        'components'
    ][
        'confidence_modifier'
    ]
    ?? null;


captainEffectiveCheck(
    'Short cameo evaluation succeeds',
    (
        $cameoResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


captainEffectiveCheck(
    'Very low Effective Confidence remains meaningfully penalised',
    is_numeric(
        $cameoModifier
    )
    &&
    (float) $cameoModifier < 0.75
);


captainEffectiveCheck(
    'Full-match starter receives higher confidence modifier than short cameo',
    is_numeric(
        $effectiveModifier
    )
    &&
    is_numeric(
        $cameoModifier
    )
    &&
    (float) $effectiveModifier
    >
    (float) $cameoModifier
);


echo "Cameo Confidence Modifier: "
    . number_format(
        (float) $cameoModifier,
        3
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * NULL EFFECTIVE CONFIDENCE FALLBACK
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Null Effective Confidence Fallback<br>";
echo "============================================<br>";


$nullEffectivePlayer =
    $basePlayer;


$nullEffectivePlayer[
    'effective_confidence'
] =
    null;


$nullEffectiveResult =
    $captainIntelligence
        ->evaluate(
            $nullEffectivePlayer
        );


$nullEffectiveComponent =
    $nullEffectiveResult[
        'components'
    ][
        'confidence'
    ]
    ?? null;


captainEffectiveCheck(
    'Null Effective Confidence still returns a valid captain evaluation',
    (
        $nullEffectiveResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


captainEffectiveCheck(
    'Null Effective Confidence falls back to raw sample confidence',
    is_numeric(
        $nullEffectiveComponent
    )
    &&
    abs(
        (float) $nullEffectiveComponent
        -
        10.0
    )
    <
    0.01
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * FULL MATURITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Full Confidence Preservation<br>";
echo "============================================<br>";


$fullConfidencePlayer =
    $basePlayer;


$fullConfidencePlayer[
    'sample_confidence'
] =
    1.00;


$fullConfidencePlayer[
    'effective_confidence'
] =
    1.00;


$fullConfidenceResult =
    $captainIntelligence
        ->evaluate(
            $fullConfidencePlayer
        );


$fullConfidenceModifier =
    $fullConfidenceResult[
        'components'
    ][
        'confidence_modifier'
    ]
    ?? null;


captainEffectiveCheck(
    'Full Effective Confidence produces neutral confidence modifier',
    is_numeric(
        $fullConfidenceModifier
    )
    &&
    abs(
        (float) $fullConfidenceModifier
        -
        1.00
    )
    <
    0.0001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * SCORE EFFECT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Captain Score Effect<br>";
echo "============================================<br>";


$sampleOnlyScore =
    $sampleOnlyResult[
        'captain_score'
    ]
    ?? null;


$effectiveScore =
    $effectiveResult[
        'captain_score'
    ]
    ?? null;


captainEffectiveCheck(
    'Higher Effective Confidence improves Captain Score',
    is_numeric(
        $sampleOnlyScore
    )
    &&
    is_numeric(
        $effectiveScore
    )
    &&
    (float) $effectiveScore
    >
    (float) $sampleOnlyScore
);


echo "Sample-Only Captain Score: "
    . number_format(
        (float) $sampleOnlyScore,
        2
    )
    . "<br>";


echo "Effective-Confidence Captain Score: "
    . number_format(
        (float) $effectiveScore,
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Captain Effective Confidence Test Summary<br>";
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