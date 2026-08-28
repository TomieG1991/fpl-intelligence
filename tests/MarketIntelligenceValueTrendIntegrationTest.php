<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence Value Trend Integration Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function valueTrendIntegrationCheck(
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


valueTrendIntegrationCheck(
    'Market Intelligence service can be created',
    $service instanceof MarketIntelligenceService
);


valueTrendIntegrationCheck(
    'Service exposes getPlayerMarketIntelligence()',
    method_exists(
        $service,
        'getPlayerMarketIntelligence'
    )
);


valueTrendIntegrationCheck(
    'Service exposes buildValueTrend()',
    method_exists(
        $service,
        'buildValueTrend'
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


$marketResult =
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


    $candidateResult =
        $service
            ->getPlayerMarketIntelligence(
                $playerId
            );


    if (
        (
            $candidateResult[
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


    $marketResult =
        $candidateResult;


    break;
}


valueTrendIntegrationCheck(
    'A real player Market Intelligence result resolves',
    is_array(
        $testPlayer
    )
    &&
    is_array(
        $marketResult
    )
);


if (
    !is_array(
        $testPlayer
    )
    ||
    !is_array(
        $marketResult
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
 * VALUE TREND INTEGRATION CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Value Trend Integration Contract<br>";
echo "============================================<br>";


$valueTrendExists =
    isset(
        $marketResult[
            'value_trend'
        ]
    )
    &&
    is_array(
        $marketResult[
            'value_trend'
        ]
    );


valueTrendIntegrationCheck(
    'Public Market Intelligence exposes value_trend',
    $valueTrendExists
);


if (
    !$valueTrendExists
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "Integrate buildValueTrend() into getPlayerMarketIntelligence()<br><br>";

    echo "Required inputs:<br>";
    echo "- player value_rating<br>";
    echo "- combined_market_signal classification<br><br>";

    echo "Required public output:<br>";
    echo "- value_trend status<br>";
    echo "- value_trend classification<br>";
    echo "- value_trend value_rating<br>";
    echo "- value_trend market_classification<br><br>";


    echo "============================================<br>";
    echo "Market Intelligence Value Trend Integration Test Summary<br>";
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


$valueTrend =
    $marketResult[
        'value_trend'
    ];


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * VALUE TREND STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Value Trend Structure<br>";
echo "============================================<br>";


$requiredFields =
    [
        'status',
        'classification',
        'value_rating',
        'market_classification'
    ];


foreach (
    $requiredFields
    as $field
) {

    valueTrendIntegrationCheck(
        "Value Trend exposes field: {$field}",
        array_key_exists(
            $field,
            $valueTrend
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * VALUE RATING SOURCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Value Rating Source<br>";
echo "============================================<br>";


$playerIntelligenceService =
    new PlayerIntelligenceService(
        $db
    );


$profile =
    $playerIntelligenceService
        ->getPlayerProfile(
            $playerId
        );


$profileValueRating =
    $profile[
        'summary'
    ][
        'value_rating'
    ]
    ?? null;


valueTrendIntegrationCheck(
    'Player profile exposes current value rating',
    $profileValueRating === null
    ||
    is_numeric(
        $profileValueRating
    )
);


valueTrendIntegrationCheck(
    'Value Trend preserves Player Intelligence value rating',
    (
        $valueTrend[
            'value_rating'
        ]
        ?? null
    )
    ===
    (
        $profileValueRating !== null
            ? (float) $profileValueRating
            : null
    )
);


echo "Value Rating: "
    . (
        $profileValueRating !== null
            ? number_format(
                (float) $profileValueRating,
                2
            )
            : 'Unavailable'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * MARKET CLASSIFICATION SOURCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Market Classification Source<br>";
echo "============================================<br>";


$combinedSignal =
    $marketResult[
        'combined_market_signal'
    ]
    ?? [];


$combinedClassification =
    $combinedSignal[
        'classification'
    ]
    ?? null;


valueTrendIntegrationCheck(
    'Combined Market Signal classification exists',
    is_string(
        $combinedClassification
    )
);


valueTrendIntegrationCheck(
    'Value Trend preserves combined market classification',
    (
        $valueTrend[
            'market_classification'
        ]
        ?? null
    )
    ===
    $combinedClassification
);


echo "Combined Market Signal: "
    . htmlspecialchars(
        (string) (
            $combinedClassification
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO G
 * VALUE TREND REPEATABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Value Trend Repeatability<br>";
echo "============================================<br>";


$expectedValueTrend =
    $service
        ->buildValueTrend(
            [
                'value_rating' =>
                    $profileValueRating !== null
                        ? (float) $profileValueRating
                        : null
            ],
            [
                'classification' =>
                    $combinedClassification
            ]
        );


valueTrendIntegrationCheck(
    'Integrated Value Trend matches direct builder result',
    $valueTrend
    ===
    $expectedValueTrend
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * RECOGNISED CLASSIFICATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Recognised Classification<br>";
echo "============================================<br>";


valueTrendIntegrationCheck(
    'Value Trend exposes a recognised classification',
    in_array(
        $valueTrend[
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
            $valueTrend[
                'classification'
            ]
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO I
 * EARLY-SEASON REAL DATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Early-Season Real Data<br>";
echo "============================================<br>";


if (
    $combinedClassification
    ===
    'Insufficient Evidence'
) {

    valueTrendIntegrationCheck(
        'Insufficient combined market evidence produces Insufficient Value Trend evidence',
        (
            $valueTrend[
                'classification'
            ]
            ?? null
        )
        ===
        'Insufficient Evidence'
    );


    valueTrendIntegrationCheck(
        'Early-season Value Trend reports insufficient historical data',
        (
            $valueTrend[
                'status'
            ]
            ?? null
        )
        ===
        'Insufficient Historical Data'
    );

} else {

    valueTrendIntegrationCheck(
        'Available market evidence produces non-insufficient Value Trend',
        (
            $valueTrend[
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
 * SCENARIO J
 * INVALID PLAYER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Invalid Player<br>";
echo "============================================<br>";


$invalidResult =
    $service
        ->getPlayerMarketIntelligence(
            999999999
        );


valueTrendIntegrationCheck(
    'Invalid player remains controlled',
    is_array(
        $invalidResult
    )
);


valueTrendIntegrationCheck(
    'Invalid player reports unavailable Market Intelligence',
    (
        $invalidResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


if (
    isset(
        $invalidResult[
            'value_trend'
        ]
    )
) {

    valueTrendIntegrationCheck(
        'Invalid player does not expose false Value Trend classification',
        in_array(
            $invalidResult[
                'value_trend'
            ][
                'classification'
            ]
            ?? null,
            [
                null,
                'Insufficient Evidence'
            ],
            true
        )
    );

} else {

    valueTrendIntegrationCheck(
        'Invalid player may omit Value Trend entirely',
        true
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Value Trend Integration Diagnostic<br>";
echo "============================================<br><br>";


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Value Rating: "
    . (
        $profileValueRating !== null
            ? number_format(
                (float) $profileValueRating,
                2
            )
            : 'Unavailable'
    )
    . "<br>";


echo "Market Classification: "
    . htmlspecialchars(
        (string) (
            $combinedClassification
            ?? 'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Value Trend: "
    . htmlspecialchars(
        (string) (
            $valueTrend[
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
            $valueTrend[
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
echo "Market Intelligence Value Trend Integration Test Summary<br>";
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