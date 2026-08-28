<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence Value Trend Summary Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function valueTrendSummaryCheck(
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
 * SERVICE FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Service Foundation<br>";
echo "============================================<br>";


$database =
    new Database();


$db =
    $database
        ->getConnection();


$service =
    new MarketIntelligenceService(
        $db
    );


valueTrendSummaryCheck(
    'Market Intelligence service can be created',
    $service instanceof MarketIntelligenceService
);


valueTrendSummaryCheck(
    'Service exposes getPlayerMarketSummary()',
    method_exists(
        $service,
        'getPlayerMarketSummary'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * REAL PLAYER RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Real Player Resolution<br>";
echo "============================================<br>";


$playerRepository =
    new PlayerRepository(
        $db
    );


$players =
    $playerRepository
        ->getAll();


$testPlayer =
    null;


$summary =
    null;


foreach (
    $players
    as $player
) {

    $playerId =
        (int) (
            $player[
                'id'
            ]
            ?? 0
        );


    if (
        $playerId <= 0
    ) {

        continue;
    }


    $candidateSummary =
        $service
            ->getPlayerMarketSummary(
                $playerId
            );


    if (
        (
            $candidateSummary[
                'status'
            ]
            ?? null
        )
        !==
        'Available'
    ) {

        continue;
    }


    $testPlayer =
        $player;


    $summary =
        $candidateSummary;


    break;
}


valueTrendSummaryCheck(
    'A real player Market Intelligence summary resolves',
    is_array(
        $testPlayer
    )
    &&
    is_array(
        $summary
    )
);


if (
    !is_array(
        $testPlayer
    )
    ||
    !is_array(
        $summary
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$playerId =
    (int) (
        $testPlayer[
            'id'
        ]
        ?? 0
    );


$playerName =
    (string) (
        $testPlayer[
            'web_name'
        ]
        ?? 'Unknown'
    );


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Player ID: "
    . $playerId
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * VALUE TREND SUMMARY CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Value Trend Summary Contract<br>";
echo "============================================<br>";


$valueTrendExists =
    isset(
        $summary[
            'value_trend'
        ]
    )
    &&
    is_array(
        $summary[
            'value_trend'
        ]
    );


valueTrendSummaryCheck(
    'Public Market summary exposes value_trend',
    $valueTrendExists
);


if (
    !$valueTrendExists
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "Expose Value Trend through getPlayerMarketSummary()<br><br>";

    echo "Required public fields:<br>";
    echo "- value_trend status<br>";
    echo "- value_trend classification<br><br>";

    echo "Internal Value Trend fields should remain hidden:<br>";
    echo "- value_rating<br>";
    echo "- market_classification<br><br>";


    echo "============================================<br>";
    echo "Market Intelligence Value Trend Summary Test Summary<br>";
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


$valueTrendSummary =
    $summary[
        'value_trend'
    ];


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * COMPACT PUBLIC STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Compact Public Structure<br>";
echo "============================================<br>";


valueTrendSummaryCheck(
    'Value Trend summary exposes status',
    array_key_exists(
        'status',
        $valueTrendSummary
    )
);


valueTrendSummaryCheck(
    'Value Trend summary exposes classification',
    array_key_exists(
        'classification',
        $valueTrendSummary
    )
);


valueTrendSummaryCheck(
    'Value Trend summary does not expose value_rating',
    !array_key_exists(
        'value_rating',
        $valueTrendSummary
    )
);


valueTrendSummaryCheck(
    'Value Trend summary does not expose market_classification',
    !array_key_exists(
        'market_classification',
        $valueTrendSummary
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * FULL RESULT CONSISTENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Full Result Consistency<br>";
echo "============================================<br>";


$fullResult =
    $service
        ->getPlayerMarketIntelligence(
            $playerId
        );


$fullValueTrend =
    $fullResult[
        'value_trend'
    ]
    ?? [];


valueTrendSummaryCheck(
    'Full Market Intelligence exposes Value Trend',
    is_array(
        $fullValueTrend
    )
    &&
    !empty(
        $fullValueTrend
    )
);


valueTrendSummaryCheck(
    'Summary Value Trend status matches full result',
    (
        $valueTrendSummary[
            'status'
        ]
        ?? null
    )
    ===
    (
        $fullValueTrend[
            'status'
        ]
        ?? null
    )
);


valueTrendSummaryCheck(
    'Summary Value Trend classification matches full result',
    (
        $valueTrendSummary[
            'classification'
        ]
        ?? null
    )
    ===
    (
        $fullValueTrend[
            'classification'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * RECOGNISED CLASSIFICATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Recognised Classification<br>";
echo "============================================<br>";


valueTrendSummaryCheck(
    'Value Trend summary exposes recognised classification',
    in_array(
        $valueTrendSummary[
            'classification'
        ]
        ?? null,
        [
            'Improving Value',
            'Stable Value',
            'Deteriorating Value',
            'Mixed Value Signal',
            'Insufficient Evidence'
        ],
        true
    )
);


echo "Value Trend: "
    . htmlspecialchars(
        (string) (
            $valueTrendSummary[
                'classification'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Value Trend Status: "
    . htmlspecialchars(
        (string) (
            $valueTrendSummary[
                'status'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO G
 * EARLY-SEASON STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Early-Season State<br>";
echo "============================================<br>";


$marketClassification =
    $summary[
        'classification'
    ]
    ?? null;


if (
    $marketClassification
    ===
    'Insufficient Evidence'
) {

    valueTrendSummaryCheck(
        'Insufficient Market Signal produces Insufficient Value Trend',
        (
            $valueTrendSummary[
                'classification'
            ]
            ?? null
        )
        ===
        'Insufficient Evidence'
    );


    valueTrendSummaryCheck(
        'Early-season Value Trend keeps insufficient historical status',
        (
            $valueTrendSummary[
                'status'
            ]
            ?? null
        )
        ===
        'Insufficient Historical Data'
    );

} else {

    valueTrendSummaryCheck(
        'Available market direction produces available Value Trend classification',
        (
            $valueTrendSummary[
                'classification'
            ]
            ?? null
        )
        !==
        'Insufficient Evidence'
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * SUMMARY BOUNDARY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Summary Boundary<br>";
echo "============================================<br>";


valueTrendSummaryCheck(
    'Summary does not expose full historical market state',
    !array_key_exists(
        'history',
        $summary
    )
);


valueTrendSummaryCheck(
    'Summary does not expose raw price movement result',
    !array_key_exists(
        'price_movement',
        $summary
    )
);


valueTrendSummaryCheck(
    'Summary does not expose raw ownership movement result',
    !array_key_exists(
        'ownership_movement',
        $summary
    )
);


valueTrendSummaryCheck(
    'Summary does not expose raw transfer momentum result',
    !array_key_exists(
        'transfer_momentum',
        $summary
    )
);


valueTrendSummaryCheck(
    'Summary does not expose raw combined market signal result',
    !array_key_exists(
        'combined_market_signal',
        $summary
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * INVALID PLAYER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Invalid Player<br>";
echo "============================================<br>";


$invalidSummary =
    $service
        ->getPlayerMarketSummary(
            999999999
        );


valueTrendSummaryCheck(
    'Invalid player summary remains controlled',
    is_array(
        $invalidSummary
    )
);


valueTrendSummaryCheck(
    'Invalid player summary remains unavailable',
    (
        $invalidSummary[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


if (
    isset(
        $invalidSummary[
            'value_trend'
        ]
    )
) {

    valueTrendSummaryCheck(
        'Invalid player does not expose false Value Trend',
        (
            $invalidSummary[
                'value_trend'
            ][
                'classification'
            ]
            ?? null
        )
        !==
        'Improving Value'
        &&
        (
            $invalidSummary[
                'value_trend'
            ][
                'classification'
            ]
            ?? null
        )
        !==
        'Stable Value'
        &&
        (
            $invalidSummary[
                'value_trend'
            ][
                'classification'
            ]
            ?? null
        )
        !==
        'Deteriorating Value'
    );

} else {

    valueTrendSummaryCheck(
        'Invalid player may omit Value Trend',
        true
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Value Trend Summary Diagnostic<br>";
echo "============================================<br><br>";


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Market Classification: "
    . htmlspecialchars(
        (string) (
            $marketClassification
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Value Trend: "
    . htmlspecialchars(
        (string) (
            $valueTrendSummary[
                'classification'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Value Trend Status: "
    . htmlspecialchars(
        (string) (
            $valueTrendSummary[
                'status'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Market Intelligence Value Trend Summary Test Summary<br>";
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