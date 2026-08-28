<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * MARKET INTELLIGENCE VALUE TREND TEST
 * ============================================================
 */

echo "============================================<br>";
echo "Market Intelligence Value Trend Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function testResult(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo
            "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        $passed++;

    } else {

        echo
            "FAIL: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        $failed++;
    }
}


function scenarioHeading(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "============================================<br>";
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


    $service =
        new MarketIntelligenceService(
            $db
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
 * Service foundation.
 * ============================================================
 */

scenarioHeading(
    'Scenario A: Service Foundation'
);


testResult(
    'MarketIntelligenceService class exists',
    class_exists(
        'MarketIntelligenceService'
    )
);


testResult(
    'Market Intelligence service can be created',
    $service
        instanceof
        MarketIntelligenceService
);


$methodExists =
    method_exists(
        $service,
        'buildValueTrend'
    );


testResult(
    'Service exposes buildValueTrend()',
    $methodExists
);


/*
 * ============================================================
 * STOP CLEANLY BEFORE IMPLEMENTATION
 * ============================================================
 *
 * This is intentionally the first failing contract.
 *
 * Once buildValueTrend() exists, the controlled classification
 * scenarios below become active.
 * ============================================================
 */

if (!$methodExists) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "MarketIntelligenceService::buildValueTrend()<br><br>";

    echo "Required classifications:<br>";
    echo "- Improving Value<br>";
    echo "- Stable Value<br>";
    echo "- Deteriorating Value<br>";
    echo "- Mixed Value Signal<br>";
    echo "- Insufficient Evidence<br><br>";

    echo "Required evidence boundary:<br>";
    echo "- market movement must not equal value by itself<br>";
    echo "- value evidence must participate in the classification<br>";
    echo "- unavailable evidence must remain unavailable<br>";
    echo "- early-season data must not fabricate a trend<br>";


    echo "<br>";
    echo "============================================<br>";
    echo "Market Intelligence Value Trend Test Summary<br>";
    echo "============================================<br>";

    echo
        "Passed: "
        . $passed
        . "<br>";

    echo
        "Failed: "
        . $failed
        . "<br><br>";

    echo "RESULT: TESTS FAILED ❌<br>";


    exit(
        1
    );
}


/*
 * ============================================================
 * CONTROLLED VALUE-TREND BUILDER
 * ============================================================
 *
 * The public service method receives:
 *
 * - current Value Intelligence classification
 * - current combined Market Intelligence classification
 *
 * We deliberately use classifications rather than reaching
 * into internal model calculations.
 * ============================================================
 */

$buildValueTrend =
    static function (
        MarketIntelligenceService $service,
        ?float $valueRating,
        string $marketClassification
    ): array {

        return
            $service
                ->buildValueTrend(
                    [
                        'value_rating' =>
                            $valueRating
                    ],
                    [
                        'classification' =>
                            $marketClassification
                    ]
                );
    };


/*
 * ============================================================
 * SCENARIO B
 * Strong value plus rising market.
 * ============================================================
 */

scenarioHeading(
    'Scenario B: Improving Value'
);


$improving =
    $buildValueTrend(
        $service,
        80.0,
        'Rising'
    );


testResult(
    'Strong Value with Rising market produces Improving Value',
    (
        $improving[
            'classification'
        ]
        ??
        null
    )
    ===
    'Improving Value'
);


testResult(
    'Improving Value reports sufficient evidence',
    (
        $improving[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


/*
 * ============================================================
 * SCENARIO C
 * Strong value plus strong market rise.
 * ============================================================
 */

scenarioHeading(
    'Scenario C: Strong Market Support'
);


$strongImproving =
    $buildValueTrend(
        $service,
        90.0,
        'Strong Rising'
    );


testResult(
    'Strong Value with Strong Rising market remains Improving Value',
    (
        $strongImproving[
            'classification'
        ]
        ??
        null
    )
    ===
    'Improving Value'
);


/*
 * ============================================================
 * SCENARIO D
 * Stable market and healthy value.
 * ============================================================
 */

scenarioHeading(
    'Scenario D: Stable Value'
);


$stable =
    $buildValueTrend(
        $service,
        80.0,
        'Stable'
    );


testResult(
    'Strong Value with Stable market produces Stable Value',
    (
        $stable[
            'classification'
        ]
        ??
        null
    )
    ===
    'Stable Value'
);


/*
 * ============================================================
 * SCENARIO E
 * Weak value plus falling market.
 * ============================================================
 */

scenarioHeading(
    'Scenario E: Deteriorating Value'
);


$deteriorating =
    $buildValueTrend(
        $service,
        30.0,
        'Falling'
    );


testResult(
    'Weak Value with Falling market produces Deteriorating Value',
    (
        $deteriorating[
            'classification'
        ]
        ??
        null
    )
    ===
    'Deteriorating Value'
);


$strongDeteriorating =
    $buildValueTrend(
        $service,
        20.0,
        'Strong Falling'
    );


testResult(
    'Weak Value with Strong Falling market produces Deteriorating Value',
    (
        $strongDeteriorating[
            'classification'
        ]
        ??
        null
    )
    ===
    'Deteriorating Value'
);


/*
 * ============================================================
 * SCENARIO F
 * Market hype must not automatically equal good value.
 * ============================================================
 */

scenarioHeading(
    'Scenario F: Market Hype Protection'
);


$marketHype =
    $buildValueTrend(
        $service,
       25.0,
        'Strong Rising'
    );


testResult(
    'Weak Value with Strong Rising market does not produce Improving Value',
    (
        $marketHype[
            'classification'
        ]
        ??
        null
    )
    !==
    'Improving Value'
);


testResult(
    'Conflicting weak value and rising market produces Mixed Value Signal',
    (
        $marketHype[
            'classification'
        ]
        ??
        null
    )
    ===
    'Mixed Value Signal'
);


/*
 * ============================================================
 * SCENARIO G
 * Good underlying value with falling market is also mixed.
 * ============================================================
 */

scenarioHeading(
    'Scenario G: Conflicting Value Evidence'
);


$conflicting =
    $buildValueTrend(
        $service,
        85.0,
        'Falling'
    );


testResult(
    'Strong Value with Falling market produces Mixed Value Signal',
    (
        $conflicting[
            'classification'
        ]
        ??
        null
    )
    ===
    'Mixed Value Signal'
);


/*
 * ============================================================
 * SCENARIO H
 * Insufficient market evidence.
 * ============================================================
 */

scenarioHeading(
    'Scenario H: Insufficient Market Evidence'
);


$insufficientMarket =
    $buildValueTrend(
        $service,
        80.00,
        'Insufficient Evidence'
    );


testResult(
    'Insufficient market evidence produces Insufficient Evidence',
    (
        $insufficientMarket[
            'classification'
        ]
        ??
        null
    )
    ===
    'Insufficient Evidence'
);


testResult(
    'Insufficient value trend reports unavailable status',
    (
        $insufficientMarket[
            'status'
        ]
        ??
        null
    )
    ===
    'Insufficient Historical Data'
);


/*
 * ============================================================
 * SCENARIO I
 * Insufficient value evidence.
 * ============================================================
 */

scenarioHeading(
    'Scenario I: Insufficient Value Evidence'
);


$insufficientValue =
    $buildValueTrend(
        $service,
        null,
        'Rising'
    );


testResult(
    'Unavailable value evidence cannot create a value trend',
    (
        $insufficientValue[
            'classification'
        ]
        ??
        null
    )
    ===
    'Insufficient Evidence'
);


/*
 * ============================================================
 * SCENARIO J
 * Unknown evidence must fail safely.
 * ============================================================
 */

scenarioHeading(
    'Scenario J: Unknown Evidence Protection'
);


$unknownValue =
    $buildValueTrend(
        $service,
        150.0,
        'Rising'
    );


testResult(
    'Unknown value classification produces Insufficient Evidence',
    (
        $unknownValue[
            'classification'
        ]
        ??
        null
    )
    ===
    'Insufficient Evidence'
);


$unknownMarket =
    $buildValueTrend(
        $service,
        80.0,
        'Something Unexpected'
    );


testResult(
    'Unknown market classification produces Insufficient Evidence',
    (
        $unknownMarket[
            'classification'
        ]
        ??
        null
    )
    ===
    'Insufficient Evidence'
);


/*
 * ============================================================
 * SCENARIO K
 * Result contract.
 * ============================================================
 */

scenarioHeading(
    'Scenario K: Value Trend Contract'
);


testResult(
    'Value trend exposes classification',
    array_key_exists(
        'classification',
        $improving
    )
);


testResult(
    'Value trend exposes status',
    array_key_exists(
        'status',
        $improving
    )
);


testResult(
    'Value trend exposes value rating',
    array_key_exists(
        'value_rating',
        $improving
    )
);


testResult(
    'Value trend exposes market classification',
    array_key_exists(
        'market_classification',
        $improving
    )
);


/*
 * ============================================================
 * SCENARIO L
 * Diagnostic.
 * ============================================================
 */

scenarioHeading(
    'Scenario L: Value Trend Diagnostic'
);


echo "Strong Value + Rising → "
    . htmlspecialchars(
        (string) (
            $improving[
                'classification'
            ]
            ??
            'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Strong Value + Stable → "
    . htmlspecialchars(
        (string) (
            $stable[
                'classification'
            ]
            ??
            'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Weak Value + Falling → "
    . htmlspecialchars(
        (string) (
            $deteriorating[
                'classification'
            ]
            ??
            'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Weak Value + Strong Rising → "
    . htmlspecialchars(
        (string) (
            $marketHype[
                'classification'
            ]
            ??
            'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Strong Value + Falling → "
    . htmlspecialchars(
        (string) (
            $conflicting[
                'classification'
            ]
            ??
            'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Strong Value + Insufficient Evidence → "
    . htmlspecialchars(
        (string) (
            $insufficientMarket[
                'classification'
            ]
            ??
            'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Market Intelligence Value Trend Test Summary<br>";
echo "============================================<br>";


echo
    "Passed: "
    . $passed
    . "<br>";


echo
    "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

    exit(
        0
    );
}


echo "RESULT: TESTS FAILED ❌<br>";


exit(
    1
);