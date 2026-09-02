<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Form Recency Weighting Test<br>";
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

function playerFormRecencyCheck(
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
 * SETUP
 * ============================================================
 */

try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $historyRepository =
        new PlayerFixtureHistoryRepository(
            $db
        );


    $formHistory =
        new PlayerFormHistory(
            $historyRepository
        );


    $playerForm =
        new PlayerForm(
            $formHistory
        );

} catch (
    Throwable $exception
) {

    echo "SETUP FAILED ❌<br>";


    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );


    exit;
}


/*
 * ============================================================
 * SCENARIO A
 * FIVE-MATCH WEIGHTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Five-Match Recency Weights<br>";
echo "============================================<br>";


$fiveWeights =
    $playerForm
        ->getRecencyWeights(
            5
        );


playerFormRecencyCheck(
    'Five-match sample returns exactly five weights',
    count(
        $fiveWeights
    )
    === 5
);


playerFormRecencyCheck(
    'Five-match weights use expected conservative progression',
    $fiveWeights
    ===
    [
        1.0,
        1.1,
        1.2,
        1.3,
        1.4
    ]
);


playerFormRecencyCheck(
    'Oldest five-match weight is neutral 1.0',
    abs(
        (
            (float) (
                $fiveWeights[
                    0
                ]
                ?? 0
            )
        )
        -
        1.0
    )
    < 0.0001
);


playerFormRecencyCheck(
    'Newest five-match weight is capped at 1.4',
    abs(
        (
            (float) (
                $fiveWeights[
                    4
                ]
                ?? 0
            )
        )
        -
        1.4
    )
    < 0.0001
);


echo "Five-Match Weights: "
    . implode(
        ', ',
        $fiveWeights
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * CHRONOLOGICAL PRIORITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Chronological Priority<br>";
echo "============================================<br>";


$weightsIncrease =
    true;


for (
    $index = 1;
    $index < count(
        $fiveWeights
    );
    $index++
) {

    if (
        (
            (float) $fiveWeights[
                $index
            ]
        )
        <=
        (
            (float) $fiveWeights[
                $index - 1
            ]
        )
    ) {

        $weightsIncrease =
            false;

        break;
    }
}


playerFormRecencyCheck(
    'Each newer fixture receives greater weight than the previous fixture',
    $weightsIncrease
);


playerFormRecencyCheck(
    'Newest fixture does not exceed 40 percent uplift over oldest fixture',
    (
        (
            (float) (
                end(
                    $fiveWeights
                )
            )
        )
        /
        (
            (float) (
                $fiveWeights[
                    0
                ]
                ?? 1
            )
        )
    )
    <= 1.4
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * SMALL SAMPLES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Small Samples<br>";
echo "============================================<br>";


$zeroWeights =
    $playerForm
        ->getRecencyWeights(
            0
        );


$negativeWeights =
    $playerForm
        ->getRecencyWeights(
            -5
        );


$singleWeight =
    $playerForm
        ->getRecencyWeights(
            1
        );


$threeWeights =
    $playerForm
        ->getRecencyWeights(
            3
        );


playerFormRecencyCheck(
    'Zero-size sample returns empty weights',
    $zeroWeights
    === []
);


playerFormRecencyCheck(
    'Negative-size sample returns empty weights',
    $negativeWeights
    === []
);


playerFormRecencyCheck(
    'Single-match sample remains neutral',
    $singleWeight
    ===
    [
        1.0
    ]
);


playerFormRecencyCheck(
    'Three-match sample spans the same conservative range',
    $threeWeights
    ===
    [
        1.0,
        1.2,
        1.4
    ]
);


echo "Three-Match Weights: "
    . implode(
        ', ',
        $threeWeights
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * CURRENT REAL PLAYER MODEL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Current Real Player Model<br>";
echo "============================================<br>";


$realModel =
    $playerForm
        ->buildModel(
            1,
            'GK'
        );


playerFormRecencyCheck(
    'Current real player model returns an array',
    is_array(
        $realModel
    )
);


playerFormRecencyCheck(
    'Current real player model exposes recency weights',
    isset(
        $realModel[
            'recency_weights'
        ]
    )
    &&
    is_array(
        $realModel[
            'recency_weights'
        ]
    )
);


playerFormRecencyCheck(
    'Current real player model exposes fixture recency weights',
    isset(
        $realModel[
            'recency_weights'
        ]['fixture']
    )
    &&
    is_array(
        $realModel[
            'recency_weights'
        ]['fixture']
    )
);


playerFormRecencyCheck(
    'Current real player model exposes appearance recency weights',
    isset(
        $realModel[
            'recency_weights'
        ]['appearance']
    )
    &&
    is_array(
        $realModel[
            'recency_weights'
        ]['appearance']
    )
);


playerFormRecencyCheck(
    'Current real player model exposes weighted metrics',
    isset(
        $realModel[
            'weighted_metrics'
        ]
    )
    &&
    is_array(
        $realModel[
            'weighted_metrics'
        ]
    )
);


playerFormRecencyCheck(
    'Current real Form Rating remains bounded',
    (
        $realModel[
            'form_rating'
        ]
        ?? null
    )
    === null
    ||
    (
        is_numeric(
            $realModel[
                'form_rating'
            ]
        )
        &&
        (
            (float) $realModel[
                'form_rating'
            ]
        )
        >= 0
        &&
        (
            (float) $realModel[
                'form_rating'
            ]
        )
        <= 100
    )
);


/*
 * Real database history changes as the season advances.
 *
 * The live-player regression should therefore verify that
 * fixture and appearance weights remain valid recency-weight
 * sequences rather than assuming exactly one stored match.
 */

$realFixtureWeights =
    $realModel[
        'recency_weights'
    ]['fixture']
    ?? [];


$realAppearanceWeights =
    $realModel[
        'recency_weights'
    ]['appearance']
    ?? [];


playerFormRecencyCheck(
    'Real fixture history exposes valid recency weights',
    !empty(
        $realFixtureWeights
    )
    &&
    abs(
        (
            (float) (
                $realFixtureWeights[
                    0
                ]
                ?? 0
            )
        )
        -
        1.0
    )
    <
    0.0001
    &&
    (
        count(
            $realFixtureWeights
        )
        ===
        1
        ||
        abs(
            (
                (float) end(
                    $realFixtureWeights
                )
            )
            -
            1.4
        )
        <
        0.0001
    )
);


playerFormRecencyCheck(
    'Real appearance history exposes valid recency weights',
    !empty(
        $realAppearanceWeights
    )
    &&
    abs(
        (
            (float) (
                $realAppearanceWeights[
                    0
                ]
                ?? 0
            )
        )
        -
        1.0
    )
    <
    0.0001
    &&
    (
        count(
            $realAppearanceWeights
        )
        ===
        1
        ||
        abs(
            (
                (float) end(
                    $realAppearanceWeights
                )
            )
            -
            1.4
        )
        <
        0.0001
    )
);


echo "Current Real Form Rating: "
    . (
        is_numeric(
            $realModel[
                'form_rating'
            ]
            ?? null
        )
            ? number_format(
                (float) $realModel[
                    'form_rating'
                ],
                2
            )
            : 'N/A'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * RAW / WEIGHTED EXPLAINABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Explainability<br>";
echo "============================================<br>";


$rawMetrics =
    $realModel[
        'raw_metrics'
    ]
    ?? null;


$weightedMetrics =
    $realModel[
        'weighted_metrics'
    ]
    ?? null;


playerFormRecencyCheck(
    'Unweighted raw metrics remain available',
    is_array(
        $rawMetrics
    )
);


playerFormRecencyCheck(
    'Weighted metrics remain independently available',
    is_array(
        $weightedMetrics
    )
);


if (
    is_array(
        $rawMetrics
    )
    &&
    is_array(
        $weightedMetrics
    )
) {

    playerFormRecencyCheck(
        'Raw points-per-appearance diagnostic remains available',
        array_key_exists(
            'points_per_appearance',
            $rawMetrics
        )
    );


    playerFormRecencyCheck(
        'Weighted points-per-appearance diagnostic remains available',
        array_key_exists(
            'points_per_appearance',
            $weightedMetrics
        )
    );


    playerFormRecencyCheck(
        'Weighted minutes-per-fixture diagnostic is available',
        array_key_exists(
            'minutes_per_fixture',
            $weightedMetrics
        )
    );


    /*
     * With one stored appearance, raw and weighted values should
     * remain equal because the only weight is 1.0.
     *
     * With multiple appearances, weighted values are allowed to
     * differ because newer evidence receives more weight.
     */

    $realAppearanceCount =
        count(
            $realAppearanceWeights
        );


    playerFormRecencyCheck(
        'Real weighted points remain valid for current appearance history',
        $realAppearanceCount > 1
        ||
        (
            (
                $rawMetrics[
                    'points_per_appearance'
                ]
                ?? null
            )
            ===
            (
                $weightedMetrics[
                    'points_per_appearance'
                ]
                ?? null
            )
        )
    );


    playerFormRecencyCheck(
        'Real weighted BPS remains valid for current appearance history',
        $realAppearanceCount > 1
        ||
        (
            (
                $rawMetrics[
                    'bps_per_90'
                ]
                ?? null
            )
            ===
            (
                $weightedMetrics[
                    'bps_per_90'
                ]
                ?? null
            )
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * RECENCY WEIGHT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Weight Contract<br>";
echo "============================================<br>";


foreach (
    [
        2,
        3,
        4,
        5,
        10,
        20
    ]
    as $sampleSize
) {

    $sampleWeights =
        $playerForm
            ->getRecencyWeights(
                $sampleSize
            );


    playerFormRecencyCheck(
        'Sample size '
        . $sampleSize
        . ' returns matching weight count',
        count(
            $sampleWeights
        )
        ===
        $sampleSize
    );


    playerFormRecencyCheck(
        'Sample size '
        . $sampleSize
        . ' starts at 1.0',
        abs(
            (
                (float) (
                    $sampleWeights[
                        0
                    ]
                    ?? 0
                )
            )
            -
            1.0
        )
        < 0.0001
    );


    playerFormRecencyCheck(
        'Sample size '
        . $sampleSize
        . ' finishes at 1.4',
        abs(
            (
                (float) (
                    $sampleWeights[
                        $sampleSize - 1
                    ]
                    ?? 0
                )
            )
            -
            1.4
        )
        < 0.0001
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * NO DOWNSTREAM INTEGRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Standalone Form Contract<br>";
echo "============================================<br>";


playerFormRecencyCheck(
    'Recency weighting remains inside standalone Player Form output',
    array_key_exists(
        'form_rating',
        $realModel
    )
    &&
    array_key_exists(
        'recency_weights',
        $realModel
    )
    &&
    array_key_exists(
        'weighted_metrics',
        $realModel
    )
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Form Recency Weighting Test Summary<br>";
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