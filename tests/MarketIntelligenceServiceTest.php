<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence Service Test<br>";
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

function marketIntelligenceServiceCheck(
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


$serviceFile =
    __DIR__
    . '/../classes/MarketIntelligenceService.php';


$serviceExists =
    is_file(
        $serviceFile
    );


marketIntelligenceServiceCheck(
    'Market Intelligence service exists',
    $serviceExists
);


if (
    !$serviceExists
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "classes/MarketIntelligenceService.php<br><br>";

    echo "Required initial responsibilities:<br>";
    echo "- resolve current player market state<br>";
    echo "- resolve historical snapshot state<br>";
    echo "- calculate price movement when possible<br>";
    echo "- calculate ownership movement when possible<br>";
    echo "- expose transfer momentum readiness<br>";
    echo "- return explicit insufficient-history states<br><br>";


    echo "============================================<br>";
    echo "Market Intelligence Service Test Summary<br>";
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


require_once $serviceFile;


marketIntelligenceServiceCheck(
    'MarketIntelligenceService class is available',
    class_exists(
        'MarketIntelligenceService'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * DATABASE FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Database Foundation<br>";
echo "============================================<br>";


$database =
    new Database();


$db =
    $database
        ->getConnection();


marketIntelligenceServiceCheck(
    'Database connection is available',
    $db instanceof PDO
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * REAL PLAYER RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Real Player Resolution<br>";
echo "============================================<br>";


$playerRepository =
    new PlayerRepository(
        $db
    );


$players =
    $playerRepository
        ->getAll();


marketIntelligenceServiceCheck(
    'Current player repository contains real players',
    !empty(
        $players
    )
);


$testPlayer =
    null;


foreach (
    $players
    as $player
) {

    if (
        !is_numeric(
            $player[
                'id'
            ]
            ?? null
        )
    ) {

        continue;
    }


    if (
        !is_numeric(
            $player[
                'price'
            ]
            ?? null
        )
    ) {

        continue;
    }


    if (
        !is_numeric(
            $player[
                'selected_by_percent'
            ]
            ?? null
        )
    ) {

        continue;
    }


    $testPlayer =
        $player;

    break;
}


marketIntelligenceServiceCheck(
    'A real player with current market data resolves',
    is_array(
        $testPlayer
    )
);


if (
    !is_array(
        $testPlayer
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
    . $playerId
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * SERVICE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Service Contract<br>";
echo "============================================<br>";


$service =
    new MarketIntelligenceService(
        $db
    );


marketIntelligenceServiceCheck(
    'Market Intelligence service can be created',
    $service instanceof MarketIntelligenceService
);


marketIntelligenceServiceCheck(
    'Service exposes getPlayerMarketIntelligence()',
    method_exists(
        $service,
        'getPlayerMarketIntelligence'
    )
);


if (
    !method_exists(
        $service,
        'getPlayerMarketIntelligence'
    )
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "MarketIntelligenceService::getPlayerMarketIntelligence(int \$playerId)<br><br>";

    echo "RESULT: TESTS FAILED ❌";

    exit;
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * REAL PLAYER MARKET RESULT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Real Player Market Result<br>";
echo "============================================<br>";


$result =
    $service
        ->getPlayerMarketIntelligence(
            $playerId
        );


marketIntelligenceServiceCheck(
    'Real player market intelligence returns an array',
    is_array(
        $result
    )
);


marketIntelligenceServiceCheck(
    'Real player market intelligence returns Available status',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * CURRENT MARKET STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Current Market State<br>";
echo "============================================<br>";


$current =
    $result[
        'current'
    ]
    ?? null;


marketIntelligenceServiceCheck(
    'Current market state is returned',
    is_array(
        $current
    )
);


marketIntelligenceServiceCheck(
    'Current market price is numeric',
    is_array(
        $current
    )
    &&
    is_numeric(
        $current[
            'price'
        ]
        ?? null
    )
);


marketIntelligenceServiceCheck(
    'Current ownership percentage is numeric',
    is_array(
        $current
    )
    &&
    is_numeric(
        $current[
            'selected_by_percent'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * HISTORICAL MARKET STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Historical Market State<br>";
echo "============================================<br>";


$history =
    $result[
        'history'
    ]
    ?? null;


marketIntelligenceServiceCheck(
    'Historical market state is returned',
    is_array(
        $history
    )
);


$historyCount =
    is_array(
        $history
    )
        ? count(
            $history
        )
        : 0;


marketIntelligenceServiceCheck(
    'At least one historical market state exists',
    $historyCount > 0
);


echo "Historical States: "
    . $historyCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * PRICE MOVEMENT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Price Movement Contract<br>";
echo "============================================<br>";


$priceMovement =
    $result[
        'price_movement'
    ]
    ?? null;


marketIntelligenceServiceCheck(
    'Price movement result is returned',
    is_array(
        $priceMovement
    )
);


$priceStatus =
    is_array(
        $priceMovement
    )
        ? (
            $priceMovement[
                'status'
            ]
            ?? null
        )
        : null;


marketIntelligenceServiceCheck(
    'Price movement exposes a recognised status',
    in_array(
        $priceStatus,
        [
            'Available',
            'Insufficient Historical Data'
        ],
        true
    )
);


echo "Price Movement Status: "
    . htmlspecialchars(
        (string) (
            $priceStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO I
 * OWNERSHIP MOVEMENT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Ownership Movement Contract<br>";
echo "============================================<br>";


$ownershipMovement =
    $result[
        'ownership_movement'
    ]
    ?? null;


marketIntelligenceServiceCheck(
    'Ownership movement result is returned',
    is_array(
        $ownershipMovement
    )
);


$ownershipStatus =
    is_array(
        $ownershipMovement
    )
        ? (
            $ownershipMovement[
                'status'
            ]
            ?? null
        )
        : null;


marketIntelligenceServiceCheck(
    'Ownership movement exposes a recognised status',
    in_array(
        $ownershipStatus,
        [
            'Available',
            'Insufficient Historical Data'
        ],
        true
    )
);


echo "Ownership Movement Status: "
    . htmlspecialchars(
        (string) (
            $ownershipStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO J
 * TRANSFER MOMENTUM CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Transfer Momentum Contract<br>";
echo "============================================<br>";


$transferMomentum =
    $result[
        'transfer_momentum'
    ]
    ?? null;


marketIntelligenceServiceCheck(
    'Transfer momentum result is returned',
    is_array(
        $transferMomentum
    )
);


$transferStatus =
    is_array(
        $transferMomentum
    )
        ? (
            $transferMomentum[
                'status'
            ]
            ?? null
        )
        : null;


marketIntelligenceServiceCheck(
    'Transfer momentum exposes a recognised status',
    in_array(
        $transferStatus,
        [
            'Available',
            'Insufficient Historical Data'
        ],
        true
    )
);


echo "Transfer Momentum Status: "
    . htmlspecialchars(
        (string) (
            $transferStatus
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO K
 * EARLY-SEASON READINESS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Early-Season Readiness<br>";
echo "============================================<br>";


$historicalGameweeks =
    [];


foreach (
    $history
    as $historyRow
) {

    $historicalGameweekId =
        (int) (
            $historyRow[
                'fpl_gameweek_id'
            ]
            ?? 0
        );


    if (
        $historicalGameweekId > 0
    ) {

        $historicalGameweeks[
            $historicalGameweekId
        ] =
            true;
    }
}


$historicalGameweekCount =
    count(
        $historicalGameweeks
    );


echo "Distinct Historical Gameweeks: "
    . $historicalGameweekCount
    . "<br>";


if (
    $historicalGameweekCount < 2
) {

    marketIntelligenceServiceCheck(
        'Price trend reports insufficient history when fewer than two snapshots exist',
        $priceStatus
        ===
        'Insufficient Historical Data'
    );


    marketIntelligenceServiceCheck(
        'Ownership trend reports insufficient history when fewer than two snapshots exist',
        $ownershipStatus
        ===
        'Insufficient Historical Data'
    );


    marketIntelligenceServiceCheck(
        'Transfer momentum reports insufficient history when fewer than two gameweeks exist',
        $transferStatus
        ===
        'Insufficient Historical Data'
    );

} else {

    marketIntelligenceServiceCheck(
        'Price trend becomes available with sufficient history',
        $priceStatus
        ===
        'Available'
    );


    marketIntelligenceServiceCheck(
        'Ownership trend becomes available with sufficient history',
        $ownershipStatus
        ===
        'Available'
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * NO INVENTED OWNERSHIP DATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Ownership Evidence Integrity<br>";
echo "============================================<br>";


$ownershipEvidenceValid =
    true;


foreach (
    $history
    as $historyRow
) {

    $selected =
        $historyRow[
            'selected'
        ]
        ?? null;


    $selectedByPercent =
        $historyRow[
            'selected_by_percent'
        ]
        ?? null;


    if (
        $selected !== null
        &&
        !is_numeric(
            $selected
        )
    ) {

        $ownershipEvidenceValid =
            false;

        break;
    }


    if (
        $selectedByPercent !== null
        &&
        !is_numeric(
            $selectedByPercent
        )
    ) {

        $ownershipEvidenceValid =
            false;

        break;
    }
}


marketIntelligenceServiceCheck(
    'Historical ownership evidence remains numeric or null',
    $ownershipEvidenceValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO M
 * INVALID PLAYER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario M: Invalid Player<br>";
echo "============================================<br>";


$invalidResult =
    $service
        ->getPlayerMarketIntelligence(
            999999999
        );


marketIntelligenceServiceCheck(
    'Invalid player returns controlled result',
    is_array(
        $invalidResult
    )
);


marketIntelligenceServiceCheck(
    'Invalid player returns Unavailable status',
    (
        $invalidResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO N
 * MARKET INTELLIGENCE DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario N: Market Intelligence Diagnostic<br>";
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


echo "Current Price: £"
    . number_format(
        (float) (
            $current[
                'price'
            ]
            ?? 0
        ),
        1
    )
    . "m<br>";


echo "Current Ownership: "
    . number_format(
        (float) (
            $current[
                'selected_by_percent'
            ]
            ?? 0
        ),
        2
    )
    . "%<br>";


echo "Historical States: "
    . $historyCount
    . "<br>";


echo "Distinct Historical Gameweeks: "
    . $historicalGameweekCount
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
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Market Intelligence Service Test Summary<br>";
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