<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Captain Confidence-Adjusted Threat Test<br>";
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

function captainAdjustedCheck(
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


$captainIntelligence =
    new CaptainIntelligence();


captainAdjustedCheck(
    'Player Performance model is available',
    $performance instanceof PlayerPerformance
);


captainAdjustedCheck(
    'Captain Intelligence model is available',
    $captainIntelligence instanceof CaptainIntelligence
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * EXISTING CONFIDENCE ADJUSTMENT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Existing Confidence Adjustment<br>";
echo "============================================<br>";


$rawRating =
    100.0;


$adjusted90 =
    $performance
        ->applySampleConfidence(
            $rawRating,
            90
        );


$adjusted450 =
    $performance
        ->applySampleConfidence(
            $rawRating,
            450
        );


$adjusted900 =
    $performance
        ->applySampleConfidence(
            $rawRating,
            900
        );


captainAdjustedCheck(
    '90-minute 100 rating is pulled strongly toward neutral',
    is_numeric(
        $adjusted90
    )
    &&
    abs(
        $adjusted90 - 55.0
    )
    < 0.01
);


captainAdjustedCheck(
    '450-minute 100 rating is partially regressed toward neutral',
    is_numeric(
        $adjusted450
    )
    &&
    abs(
        $adjusted450 - 75.0
    )
    < 0.01
);


captainAdjustedCheck(
    '900-minute 100 rating retains full value',
    is_numeric(
        $adjusted900
    )
    &&
    abs(
        $adjusted900 - 100.0
    )
    < 0.01
);


echo "90 Minutes: "
    . number_format(
        $adjusted90,
        2
    )
    . "<br>";


echo "450 Minutes: "
    . number_format(
        $adjusted450,
        2
    )
    . "<br>";


echo "900 Minutes: "
    . number_format(
        $adjusted900,
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * RAW THREAT BASELINE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Raw Threat Baseline<br>";
echo "============================================<br>";


$rawPlayer = [

    'player_id' =>
        1,

    'name' =>
        'Raw Sample Player',

    'position' =>
        'MID',

    'strength_score' =>
        50.0,

    'fixture_score' =>
        50.0,

    'sample_confidence' =>
        0.10,

    'availability' =>
        1.00,

    'goals_rating' =>
        100.0,

    'assists_rating' =>
        100.0,

    'expected_goals_rating' =>
        100.0,

    'expected_assists_rating' =>
        100.0
];


$rawResult =
    $captainIntelligence
        ->evaluate(
            $rawPlayer
        );


captainAdjustedCheck(
    'Raw captain evaluation succeeds',
    (
        $rawResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


$rawThreat =
    $rawResult[
        'components'
    ][
        'attacking_threat'
    ]
    ?? null;


captainAdjustedCheck(
    'Raw maximum attacking ratings produce maximum threat baseline',
    is_numeric(
        $rawThreat
    )
    &&
    abs(
        (float) $rawThreat
        -
        100.0
    )
    <
    0.01
);


echo "Raw Threat: "
    . number_format(
        (float) $rawThreat,
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * ADJUSTED RATING CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Adjusted Rating Contract<br>";
echo "============================================<br>";


$adjustedPlayer =
    $rawPlayer;


$adjustedPlayer[
    'name'
] =
    'Adjusted Sample Player';


$adjustedPlayer[
    'adjusted_goals_rating'
] =
    55.0;


$adjustedPlayer[
    'adjusted_assists_rating'
] =
    55.0;


$adjustedPlayer[
    'adjusted_expected_goals_rating'
] =
    55.0;


$adjustedPlayer[
    'adjusted_expected_assists_rating'
] =
    55.0;


$adjustedResult =
    $captainIntelligence
        ->evaluate(
            $adjustedPlayer
        );


captainAdjustedCheck(
    'Adjusted captain evaluation succeeds',
    (
        $adjustedResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


$adjustedThreat =
    $adjustedResult[
        'components'
    ][
        'attacking_threat'
    ]
    ?? null;


captainAdjustedCheck(
    'Captain Intelligence prefers confidence-adjusted attacking ratings',
    is_numeric(
        $adjustedThreat
    )
    &&
    abs(
        (float) $adjustedThreat
        -
        55.0
    )
    <
    0.01
);


captainAdjustedCheck(
    'Adjusted threat is lower than raw tiny-sample threat',
    is_numeric(
        $adjustedThreat
    )
    &&
    is_numeric(
        $rawThreat
    )
    &&
    (float) $adjustedThreat
    <
    (float) $rawThreat
);


echo "Adjusted Threat: "
    . number_format(
        (float) $adjustedThreat,
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * RAW FALLBACK
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Raw Rating Fallback<br>";
echo "============================================<br>";


$fallbackResult =
    $captainIntelligence
        ->evaluate(
            $rawPlayer
        );


$fallbackThreat =
    $fallbackResult[
        'components'
    ][
        'attacking_threat'
    ]
    ?? null;


captainAdjustedCheck(
    'Captain Intelligence retains raw-rating fallback when adjusted fields are absent',
    is_numeric(
        $fallbackThreat
    )
    &&
    abs(
        (float) $fallbackThreat
        -
        100.0
    )
    <
    0.01
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * MATURE SAMPLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Mature Sample Preservation<br>";
echo "============================================<br>";


$maturePlayer =
    $rawPlayer;


$maturePlayer[
    'name'
] =
    'Mature Sample Player';


$maturePlayer[
    'sample_confidence'
] =
    1.00;


$maturePlayer[
    'adjusted_goals_rating'
] =
    100.0;


$maturePlayer[
    'adjusted_assists_rating'
] =
    100.0;


$maturePlayer[
    'adjusted_expected_goals_rating'
] =
    100.0;


$maturePlayer[
    'adjusted_expected_assists_rating'
] =
    100.0;


$matureResult =
    $captainIntelligence
        ->evaluate(
            $maturePlayer
        );


$matureThreat =
    $matureResult[
        'components'
    ][
        'attacking_threat'
    ]
    ?? null;


captainAdjustedCheck(
    'Fully mature attacking ratings retain maximum threat',
    is_numeric(
        $matureThreat
    )
    &&
    abs(
        (float) $matureThreat
        -
        100.0
    )
    <
    0.01
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * SERVICE INTEGRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Player Intelligence Service Integration<br>";
echo "============================================<br>";


try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $service =
        new PlayerIntelligenceService(
            $db
        );


    $summaries =
        $service
            ->getAllPlayerSummaries();


    captainAdjustedCheck(
        'Player Intelligence summaries are available',
        is_array(
            $summaries
        )
        &&
        !empty(
            $summaries
        )
    );


    $requiredFields = [

        'adjusted_goals_rating',

        'adjusted_assists_rating',

        'adjusted_expected_goals_rating',

        'adjusted_expected_assists_rating'
    ];


    foreach (
        $requiredFields
        as $field
    ) {

        $fieldExists =
            false;


        foreach (
            $summaries
            as $summary
        ) {

            if (
                array_key_exists(
                    $field,
                    $summary
                )
            ) {

                $fieldExists =
                    true;

                break;
            }
        }


        captainAdjustedCheck(
            'Player Intelligence summary exposes '
            . $field,
            $fieldExists
        );
    }


    /*
     * Verify that a real player with a partial sample can expose
     * both the raw and adjusted forms simultaneously.
     */

    $partialSampleFound =
        false;


    foreach (
        $summaries
        as $summary
    ) {

        $minutes =
            (int) (
                $summary[
                    'minutes'
                ]
                ?? 0
            );


        if (
            $minutes <= 0
            ||
            $minutes >= 900
        ) {

            continue;
        }


        if (
            !is_numeric(
                $summary[
                    'goals_rating'
                ]
                ?? null
            )
            ||
            !is_numeric(
                $summary[
                    'adjusted_goals_rating'
                ]
                ?? null
            )
        ) {

            continue;
        }


        $partialSampleFound =
            true;

        break;
    }


    captainAdjustedCheck(
        'Real partial-sample player exposes raw and adjusted attacking ratings',
        $partialSampleFound
    );

} catch (
    Throwable $exception
) {

    captainAdjustedCheck(
        'Player Intelligence summaries are available',
        false
    );


    echo "Service Error: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Captain Confidence-Adjusted Threat Test Summary<br>";
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