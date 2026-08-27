<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence Combined Signal Integration Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function marketCombinedIntegrationCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


marketCombinedIntegrationCheck(
    'MarketIntelligenceService class exists',
    class_exists(
        'MarketIntelligenceService'
    )
);


$database =
    new Database();


$db =
    $database
        ->getConnection();


marketCombinedIntegrationCheck(
    'Database connection is available',
    $db instanceof PDO
);


$service =
    new MarketIntelligenceService(
        $db
    );


marketCombinedIntegrationCheck(
    'Market Intelligence service can be created',
    $service instanceof MarketIntelligenceService
);


marketCombinedIntegrationCheck(
    'Service exposes getPlayerMarketIntelligence()',
    method_exists(
        $service,
        'getPlayerMarketIntelligence'
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


$testResult =
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


    $result =
        $service
            ->getPlayerMarketIntelligence(
                $playerId
            );


    if (
        (
            $result[
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


    $testResult =
        $result;


    break;
}


marketCombinedIntegrationCheck(
    'A real player market intelligence result resolves',
    is_array(
        $testPlayer
    )
    &&
    is_array(
        $testResult
    )
);


if (
    !is_array(
        $testPlayer
    )
    ||
    !is_array(
        $testResult
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$testPlayerId =
    (int) (
        $testPlayer[
            'id'
        ]
        ?? 0
    );


echo "Player: "
    . htmlspecialchars(
        (string) (
            $testPlayer[
                'web_name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Player ID: "
    . $testPlayerId
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * COMPONENT SIGNAL INTEGRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Component Signal Integration<br>";
echo "============================================<br>";


marketCombinedIntegrationCheck(
    'Public result exposes price movement',
    isset(
        $testResult[
            'price_movement'
        ]
    )
    &&
    is_array(
        $testResult[
            'price_movement'
        ]
    )
);


marketCombinedIntegrationCheck(
    'Public result exposes ownership movement',
    isset(
        $testResult[
            'ownership_movement'
        ]
    )
    &&
    is_array(
        $testResult[
            'ownership_movement'
        ]
    )
);


marketCombinedIntegrationCheck(
    'Public result exposes transfer momentum',
    isset(
        $testResult[
            'transfer_momentum'
        ]
    )
    &&
    is_array(
        $testResult[
            'transfer_momentum'
        ]
    )
);


marketCombinedIntegrationCheck(
    'Public result exposes combined market signal',
    isset(
        $testResult[
            'combined_market_signal'
        ]
    )
    &&
    is_array(
        $testResult[
            'combined_market_signal'
        ]
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * COMBINED SIGNAL CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Combined Signal Contract<br>";
echo "============================================<br>";


$combinedSignal =
    $testResult[
        'combined_market_signal'
    ]
    ?? [];


marketCombinedIntegrationCheck(
    'Combined signal exposes classification',
    array_key_exists(
        'classification',
        $combinedSignal
    )
);


marketCombinedIntegrationCheck(
    'Combined signal exposes available signal count',
    array_key_exists(
        'available_signals',
        $combinedSignal
    )
);


marketCombinedIntegrationCheck(
    'Combined signal exposes rising signal count',
    array_key_exists(
        'rising_signals',
        $combinedSignal
    )
);


marketCombinedIntegrationCheck(
    'Combined signal exposes falling signal count',
    array_key_exists(
        'falling_signals',
        $combinedSignal
    )
);


marketCombinedIntegrationCheck(
    'Combined signal exposes stable signal count',
    array_key_exists(
        'stable_signals',
        $combinedSignal
    )
);


$classification =
    $combinedSignal[
        'classification'
    ]
    ?? null;


marketCombinedIntegrationCheck(
    'Combined signal exposes a recognised classification',
    in_array(
        $classification,
        [
            'Strong Rising',
            'Rising',
            'Stable',
            'Falling',
            'Strong Falling',
            'Mixed',
            'Insufficient Evidence'
        ],
        true
    )
);


echo "Classification: "
    . htmlspecialchars(
        (string) (
            $classification
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * PUBLIC RESULT MATCHES COMPONENT EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Component Evidence Accounting<br>";
echo "============================================<br>";


$componentSignals =
    [
        $testResult[
            'price_movement'
        ]
        ?? [],

        $testResult[
            'ownership_movement'
        ]
        ?? [],

        $testResult[
            'transfer_momentum'
        ]
        ?? []
    ];


$expectedAvailable =
    0;


$expectedRising =
    0;


$expectedFalling =
    0;


$expectedStable =
    0;


foreach (
    $componentSignals
    as $componentSignal
) {

    if (
        (
            $componentSignal[
                'status'
            ]
            ?? null
        )
        !==
        'Available'
    ) {

        continue;
    }


    $direction =
        $componentSignal[
            'direction'
        ]
        ?? null;


    if (
        !in_array(
            $direction,
            [
                'Rising',
                'Falling',
                'Stable'
            ],
            true
        )
    ) {

        continue;
    }


    $expectedAvailable++;


    if (
        $direction
        ===
        'Rising'
    ) {

        $expectedRising++;

    } elseif (
        $direction
        ===
        'Falling'
    ) {

        $expectedFalling++;

    } else {

        $expectedStable++;
    }
}


marketCombinedIntegrationCheck(
    'Combined available count matches component evidence',
    (
        (int) (
            $combinedSignal[
                'available_signals'
            ]
            ?? -1
        )
    )
    ===
    $expectedAvailable
);


marketCombinedIntegrationCheck(
    'Combined rising count matches component evidence',
    (
        (int) (
            $combinedSignal[
                'rising_signals'
            ]
            ?? -1
        )
    )
    ===
    $expectedRising
);


marketCombinedIntegrationCheck(
    'Combined falling count matches component evidence',
    (
        (int) (
            $combinedSignal[
                'falling_signals'
            ]
            ?? -1
        )
    )
    ===
    $expectedFalling
);


marketCombinedIntegrationCheck(
    'Combined stable count matches component evidence',
    (
        (int) (
            $combinedSignal[
                'stable_signals'
            ]
            ?? -1
        )
    )
    ===
    $expectedStable
);


echo "Available Signals: "
    . $expectedAvailable
    . "<br>";


echo "Rising Signals: "
    . $expectedRising
    . "<br>";


echo "Falling Signals: "
    . $expectedFalling
    . "<br>";


echo "Stable Signals: "
    . $expectedStable
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * EARLY-SEASON REAL DATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Early-Season Real Data<br>";
echo "============================================<br>";


$priceStatus =
    $testResult[
        'price_movement'
    ][
        'status'
    ]
    ?? null;


$ownershipStatus =
    $testResult[
        'ownership_movement'
    ][
        'status'
    ]
    ?? null;


$transferStatus =
    $testResult[
        'transfer_momentum'
    ][
        'status'
    ]
    ?? null;


echo "Price Movement: "
    . htmlspecialchars(
        (string) (
            $priceStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Ownership Movement: "
    . htmlspecialchars(
        (string) (
            $ownershipStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Transfer Momentum: "
    . htmlspecialchars(
        (string) (
            $transferStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Combined Market Signal: "
    . htmlspecialchars(
        (string) (
            $classification
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


if (
    $priceStatus
    !==
    'Available'
    &&
    $ownershipStatus
    !==
    'Available'
    &&
    $transferStatus
    !==
    'Available'
) {

    marketCombinedIntegrationCheck(
        'No available real component evidence produces Insufficient Evidence',
        $classification
        ===
        'Insufficient Evidence'
    );


    marketCombinedIntegrationCheck(
        'Early-season combined signal reports zero available components',
        (
            (int) (
                $combinedSignal[
                    'available_signals'
                ]
                ?? -1
            )
        )
        ===
        0
    );

} else {

    marketCombinedIntegrationCheck(
        'Real combined signal responds to available component evidence',
        $classification
        !==
        'Insufficient Evidence'
        ||
        $expectedAvailable < 2
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * REPEATABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Repeatability<br>";
echo "============================================<br>";


$repeatResult =
    $service
        ->getPlayerMarketIntelligence(
            $testPlayerId
        );


$repeatCombinedSignal =
    $repeatResult[
        'combined_market_signal'
    ]
    ?? null;


marketCombinedIntegrationCheck(
    'Repeated public service call still exposes combined signal',
    is_array(
        $repeatCombinedSignal
    )
);


marketCombinedIntegrationCheck(
    'Repeated public service call preserves combined classification',
    (
        $repeatCombinedSignal[
            'classification'
        ]
        ?? null
    )
    ===
    $classification
);


marketCombinedIntegrationCheck(
    'Repeated public service call preserves signal accounting',
    (
        $repeatCombinedSignal[
            'available_signals'
        ]
        ?? null
    )
    ===
    (
        $combinedSignal[
            'available_signals'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * INVALID PLAYER CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Invalid Player Contract<br>";
echo "============================================<br>";


$invalidResult =
    $service
        ->getPlayerMarketIntelligence(
            999999999
        );


marketCombinedIntegrationCheck(
    'Invalid player returns controlled service result',
    is_array(
        $invalidResult
    )
);


marketCombinedIntegrationCheck(
    'Invalid player remains unavailable',
    (
        $invalidResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * We have not yet required unavailableResult() to expose a
 * combined_market_signal object. If it does, it must be safe.
 * If it does not, the existing public invalid-player contract
 * remains valid.
 */

$invalidCombined =
    $invalidResult[
        'combined_market_signal'
    ]
    ?? null;


$invalidCombinedSafe =
    $invalidCombined === null
    ||
    (
        is_array(
            $invalidCombined
        )
        &&
        in_array(
            $invalidCombined[
                'classification'
            ]
            ?? null,
            [
                'Insufficient Evidence',
                'Unavailable'
            ],
            true
        )
    );


marketCombinedIntegrationCheck(
    'Invalid player does not expose a false combined market classification',
    $invalidCombinedSafe
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * INTEGRATION DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Combined Signal Integration Diagnostic<br>";
echo "============================================<br><br>";


echo "Player: "
    . htmlspecialchars(
        (string) (
            $testPlayer[
                'web_name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Price Movement: "
    . htmlspecialchars(
        (string) (
            $priceStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Ownership Movement: "
    . htmlspecialchars(
        (string) (
            $ownershipStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Transfer Momentum: "
    . htmlspecialchars(
        (string) (
            $transferStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Combined Classification: "
    . htmlspecialchars(
        (string) (
            $classification
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Available Signals: "
    . (
        (int) (
            $combinedSignal[
                'available_signals'
            ]
            ?? 0
        )
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Market Intelligence Combined Signal Integration Test Summary<br>";
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