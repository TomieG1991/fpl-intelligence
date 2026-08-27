<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence Price Movement Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function marketPriceMovementCheck(
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


marketPriceMovementCheck(
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


$service =
    new MarketIntelligenceService(
        $db
    );


marketPriceMovementCheck(
    'Market Intelligence service can be created',
    $service instanceof MarketIntelligenceService
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * CONTROLLED RISING PRICE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Controlled Rising Price<br>";
echo "============================================<br>";


$reflection =
    new ReflectionClass(
        MarketIntelligenceService::class
    );


$priceMovementMethod =
    $reflection
        ->getMethod(
            'buildPriceMovement'
        );


$priceMovementMethod
    ->setAccessible(
        true
    );


$risingHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'price' =>
                5.0
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'price' =>
                5.1
        ]
    ];


$risingResult =
    $priceMovementMethod
        ->invoke(
            $service,
            $risingHistory
        );


marketPriceMovementCheck(
    'Two controlled price states are available',
    (
        $risingResult[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


marketPriceMovementCheck(
    '£5.0m to £5.1m produces +£0.1m movement',
    abs(
        (
            (float) (
                $risingResult[
                    'change'
                ]
                ?? 0
            )
        )
        -
        0.1
    )
    <
    0.0001
);


marketPriceMovementCheck(
    'Positive price movement is classified Rising',
    (
        $risingResult[
            'direction'
        ]
        ?? null
    )
    ===
    'Rising'
);


marketPriceMovementCheck(
    'Rising movement preserves start price',
    abs(
        (
            (float) (
                $risingResult[
                    'start_price'
                ]
                ?? 0
            )
        )
        -
        5.0
    )
    <
    0.0001
);


marketPriceMovementCheck(
    'Rising movement preserves latest price',
    abs(
        (
            (float) (
                $risingResult[
                    'latest_price'
                ]
                ?? 0
            )
        )
        -
        5.1
    )
    <
    0.0001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * CONTROLLED FALLING PRICE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Controlled Falling Price<br>";
echo "============================================<br>";


$fallingHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'price' =>
                5.5
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'price' =>
                5.3
        ]
    ];


$fallingResult =
    $priceMovementMethod
        ->invoke(
            $service,
            $fallingHistory
        );


marketPriceMovementCheck(
    '£5.5m to £5.3m produces -£0.2m movement',
    abs(
        (
            (float) (
                $fallingResult[
                    'change'
                ]
                ?? 0
            )
        )
        -
        (-0.2)
    )
    <
    0.0001
);


marketPriceMovementCheck(
    'Negative price movement is classified Falling',
    (
        $fallingResult[
            'direction'
        ]
        ?? null
    )
    ===
    'Falling'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * CONTROLLED STABLE PRICE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Controlled Stable Price<br>";
echo "============================================<br>";


$stableHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'price' =>
                7.0
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'price' =>
                7.0
        ]
    ];


$stableResult =
    $priceMovementMethod
        ->invoke(
            $service,
            $stableHistory
        );


marketPriceMovementCheck(
    'Unchanged price produces zero movement',
    abs(
        (float) (
            $stableResult[
                'change'
            ]
            ?? 999
        )
    )
    <
    0.0001
);


marketPriceMovementCheck(
    'Zero price movement is classified Stable',
    (
        $stableResult[
            'direction'
        ]
        ?? null
    )
    ===
    'Stable'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * INSUFFICIENT HISTORICAL DATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Insufficient Historical Data<br>";
echo "============================================<br>";


$singleStateHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'price' =>
                6.0
        ]
    ];


$singleStateResult =
    $priceMovementMethod
        ->invoke(
            $service,
            $singleStateHistory
        );


marketPriceMovementCheck(
    'Single historical price state reports insufficient data',
    (
        $singleStateResult[
            'status'
        ]
        ?? null
    )
    ===
    'Insufficient Historical Data'
);


marketPriceMovementCheck(
    'Single historical state reports one gameweek',
    (
        (int) (
            $singleStateResult[
                'gameweek_count'
            ]
            ?? 0
        )
    )
    ===
    1
);


marketPriceMovementCheck(
    'Insufficient price movement does not invent a change',
    (
        $singleStateResult[
            'change'
        ]
        ?? null
    )
    ===
    null
);


marketPriceMovementCheck(
    'Insufficient price movement has unavailable direction',
    (
        $singleStateResult[
            'direction'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * CHRONOLOGICAL ORDER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Chronological Ordering<br>";
echo "============================================<br>";


$outOfOrderHistory =
    [

        [
            'fpl_gameweek_id' =>
                3,

            'price' =>
                5.2
        ],

        [
            'fpl_gameweek_id' =>
                1,

            'price' =>
                5.0
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'price' =>
                5.1
        ]
    ];


$outOfOrderResult =
    $priceMovementMethod
        ->invoke(
            $service,
            $outOfOrderHistory
        );


marketPriceMovementCheck(
    'Price movement orders historical states by gameweek',
    abs(
        (
            (float) (
                $outOfOrderResult[
                    'start_price'
                ]
                ?? 0
            )
        )
        -
        5.0
    )
    <
    0.0001
    &&
    abs(
        (
            (float) (
                $outOfOrderResult[
                    'latest_price'
                ]
                ?? 0
            )
        )
        -
        5.2
    )
    <
    0.0001
);


marketPriceMovementCheck(
    'Three ordered historical states report three gameweeks',
    (
        (int) (
            $outOfOrderResult[
                'gameweek_count'
            ]
            ?? 0
        )
    )
    ===
    3
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * DUPLICATE GAMEWEEK PROTECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Duplicate Gameweek Protection<br>";
echo "============================================<br>";


$duplicateGameweekHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'price' =>
                5.0
        ],

        [
            'fpl_gameweek_id' =>
                1,

            'price' =>
                5.0
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'price' =>
                5.1
        ]
    ];


$duplicateGameweekResult =
    $priceMovementMethod
        ->invoke(
            $service,
            $duplicateGameweekHistory
        );


marketPriceMovementCheck(
    'Duplicate gameweek rows do not inflate historical gameweek count',
    (
        (int) (
            $duplicateGameweekResult[
                'gameweek_count'
            ]
            ?? 0
        )
    )
    ===
    2
);


marketPriceMovementCheck(
    'Duplicate gameweek rows preserve correct price movement',
    abs(
        (
            (float) (
                $duplicateGameweekResult[
                    'change'
                ]
                ?? 0
            )
        )
        -
        0.1
    )
    <
    0.0001
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * NULL / INVALID PRICE EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Invalid Price Evidence<br>";
echo "============================================<br>";


$invalidPriceHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'price' =>
                null
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'price' =>
                'invalid'
        ],

        [
            'fpl_gameweek_id' =>
                3,

            'price' =>
                5.0
        ]
    ];


$invalidPriceResult =
    $priceMovementMethod
        ->invoke(
            $service,
            $invalidPriceHistory
        );


marketPriceMovementCheck(
    'Invalid price states are ignored',
    (
        (int) (
            $invalidPriceResult[
                'gameweek_count'
            ]
            ?? 0
        )
    )
    ===
    1
);


marketPriceMovementCheck(
    'Invalid price evidence cannot create a false trend',
    (
        $invalidPriceResult[
            'status'
        ]
        ?? null
    )
    ===
    'Insufficient Historical Data'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * REAL DATABASE PLAYER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Real Database Behaviour<br>";
echo "============================================<br>";


$playerRepository =
    new PlayerRepository(
        $db
    );


$players =
    $playerRepository
        ->getAll();


$realPlayer =
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


    $history =
        $result[
            'history'
        ]
        ?? [];


    if (
        !is_array(
            $history
        )
        ||
        empty(
            $history
        )
    ) {

        continue;
    }


    $realPlayer =
        [

            'player' =>
                $player,

            'result' =>
                $result
        ];

    break;
}


marketPriceMovementCheck(
    'A real player with historical market state resolves',
    is_array(
        $realPlayer
    )
);


if (
    is_array(
        $realPlayer
    )
) {

    $realPlayerRow =
        $realPlayer[
            'player'
        ];


    $realResult =
        $realPlayer[
            'result'
        ];


    $realPriceMovement =
        $realResult[
            'price_movement'
        ]
        ?? [];


    $realHistory =
        $realResult[
            'history'
        ]
        ?? [];


    $realGameweeks =
        [];


    foreach (
        $realHistory
        as $row
    ) {

        $gameweek =
            (int) (
                $row[
                    'fpl_gameweek_id'
                ]
                ?? 0
            );


        if (
            $gameweek > 0
        ) {

            $realGameweeks[
                $gameweek
            ] =
                true;
        }
    }


    $realGameweekCount =
        count(
            $realGameweeks
        );


    echo "Player: "
        . htmlspecialchars(
            (string) (
                $realPlayerRow[
                    'web_name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Historical Gameweeks: "
        . $realGameweekCount
        . "<br>";


    echo "Price Movement Status: "
        . htmlspecialchars(
            (string) (
                $realPriceMovement[
                    'status'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    if (
        $realGameweekCount < 2
    ) {

        marketPriceMovementCheck(
            'Real early-season player does not fabricate price trend',
            (
                $realPriceMovement[
                    'status'
                ]
                ?? null
            )
            ===
            'Insufficient Historical Data'
        );

    } else {

        marketPriceMovementCheck(
            'Real player exposes available price movement once sufficient history exists',
            (
                $realPriceMovement[
                    'status'
                ]
                ?? null
            )
            ===
            'Available'
        );
    }
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * PRICE MOVEMENT DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Price Movement Diagnostic<br>";
echo "============================================<br><br>";


echo "Controlled Rising: £5.0m → £5.1m<br>";
echo "Expected Direction: Rising<br>";
echo "Expected Change: +£0.1m<br><br>";


echo "Controlled Falling: £5.5m → £5.3m<br>";
echo "Expected Direction: Falling<br>";
echo "Expected Change: -£0.2m<br><br>";


echo "Controlled Stable: £7.0m → £7.0m<br>";
echo "Expected Direction: Stable<br>";
echo "Expected Change: £0.0m<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Market Intelligence Price Movement Test Summary<br>";
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