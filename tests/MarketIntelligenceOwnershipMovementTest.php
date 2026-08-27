<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence Ownership Movement Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function marketOwnershipMovementCheck(
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


marketOwnershipMovementCheck(
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


marketOwnershipMovementCheck(
    'Market Intelligence service can be created',
    $service instanceof MarketIntelligenceService
);


$reflection =
    new ReflectionClass(
        MarketIntelligenceService::class
    );


$ownershipMovementMethod =
    $reflection
        ->getMethod(
            'buildOwnershipMovement'
        );


$ownershipMovementMethod
    ->setAccessible(
        true
    );


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * CONTROLLED RISING OWNERSHIP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Controlled Rising Ownership<br>";
echo "============================================<br>";


$risingHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'selected' =>
                1000000
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'selected' =>
                1250000
        ]
    ];


$risingResult =
    $ownershipMovementMethod
        ->invoke(
            $service,
            $risingHistory
        );


marketOwnershipMovementCheck(
    'Two controlled ownership states are available',
    (
        $risingResult[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


marketOwnershipMovementCheck(
    '1,000,000 to 1,250,000 produces +250,000 managers',
    (
        (int) (
            $risingResult[
                'change'
            ]
            ?? 0
        )
    )
    ===
    250000
);


marketOwnershipMovementCheck(
    'Positive ownership movement is classified Rising',
    (
        $risingResult[
            'direction'
        ]
        ?? null
    )
    ===
    'Rising'
);


marketOwnershipMovementCheck(
    'Rising ownership preserves start selected count',
    (
        (int) (
            $risingResult[
                'start_selected'
            ]
            ?? 0
        )
    )
    ===
    1000000
);


marketOwnershipMovementCheck(
    'Rising ownership preserves latest selected count',
    (
        (int) (
            $risingResult[
                'latest_selected'
            ]
            ?? 0
        )
    )
    ===
    1250000
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * CONTROLLED FALLING OWNERSHIP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Controlled Falling Ownership<br>";
echo "============================================<br>";


$fallingHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'selected' =>
                2000000
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'selected' =>
                1700000
        ]
    ];


$fallingResult =
    $ownershipMovementMethod
        ->invoke(
            $service,
            $fallingHistory
        );


marketOwnershipMovementCheck(
    '2,000,000 to 1,700,000 produces -300,000 managers',
    (
        (int) (
            $fallingResult[
                'change'
            ]
            ?? 0
        )
    )
    ===
    -300000
);


marketOwnershipMovementCheck(
    'Negative ownership movement is classified Falling',
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
 * CONTROLLED STABLE OWNERSHIP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Controlled Stable Ownership<br>";
echo "============================================<br>";


$stableHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'selected' =>
                1500000
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'selected' =>
                1500000
        ]
    ];


$stableResult =
    $ownershipMovementMethod
        ->invoke(
            $service,
            $stableHistory
        );


marketOwnershipMovementCheck(
    'Unchanged selected count produces zero movement',
    (
        (int) (
            $stableResult[
                'change'
            ]
            ?? 999
        )
    )
    ===
    0
);


marketOwnershipMovementCheck(
    'Zero ownership movement is classified Stable',
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

            'selected' =>
                2500000
        ]
    ];


$singleStateResult =
    $ownershipMovementMethod
        ->invoke(
            $service,
            $singleStateHistory
        );


marketOwnershipMovementCheck(
    'Single ownership state reports insufficient historical data',
    (
        $singleStateResult[
            'status'
        ]
        ?? null
    )
    ===
    'Insufficient Historical Data'
);


marketOwnershipMovementCheck(
    'Single ownership state reports one gameweek',
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


marketOwnershipMovementCheck(
    'Insufficient ownership history does not invent movement',
    (
        $singleStateResult[
            'change'
        ]
        ?? null
    )
    ===
    null
);


marketOwnershipMovementCheck(
    'Insufficient ownership history has unavailable direction',
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
 * CHRONOLOGICAL ORDERING
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

            'selected' =>
                1300000
        ],

        [
            'fpl_gameweek_id' =>
                1,

            'selected' =>
                1000000
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'selected' =>
                1150000
        ]
    ];


$outOfOrderResult =
    $ownershipMovementMethod
        ->invoke(
            $service,
            $outOfOrderHistory
        );


marketOwnershipMovementCheck(
    'Ownership movement orders historical states by gameweek',
    (
        (int) (
            $outOfOrderResult[
                'start_selected'
            ]
            ?? 0
        )
    )
    ===
    1000000
    &&
    (
        (int) (
            $outOfOrderResult[
                'latest_selected'
            ]
            ?? 0
        )
    )
    ===
    1300000
);


marketOwnershipMovementCheck(
    'Three ownership states report three gameweeks',
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


marketOwnershipMovementCheck(
    'Chronological ownership movement produces correct total change',
    (
        (int) (
            $outOfOrderResult[
                'change'
            ]
            ?? 0
        )
    )
    ===
    300000
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

            'selected' =>
                1000000
        ],

        [
            'fpl_gameweek_id' =>
                1,

            'selected' =>
                1000000
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'selected' =>
                1200000
        ]
    ];


$duplicateGameweekResult =
    $ownershipMovementMethod
        ->invoke(
            $service,
            $duplicateGameweekHistory
        );


marketOwnershipMovementCheck(
    'Duplicate gameweek rows do not inflate ownership gameweek count',
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


marketOwnershipMovementCheck(
    'Duplicate gameweek rows preserve correct ownership movement',
    (
        (int) (
            $duplicateGameweekResult[
                'change'
            ]
            ?? 0
        )
    )
    ===
    200000
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * INVALID OWNERSHIP EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Invalid Ownership Evidence<br>";
echo "============================================<br>";


$invalidOwnershipHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'selected' =>
                null
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'selected' =>
                'invalid'
        ],

        [
            'fpl_gameweek_id' =>
                3,

            'selected' =>
                1000000
        ]
    ];


$invalidOwnershipResult =
    $ownershipMovementMethod
        ->invoke(
            $service,
            $invalidOwnershipHistory
        );


marketOwnershipMovementCheck(
    'Invalid ownership states are ignored',
    (
        (int) (
            $invalidOwnershipResult[
                'gameweek_count'
            ]
            ?? 0
        )
    )
    ===
    1
);


marketOwnershipMovementCheck(
    'Invalid ownership evidence cannot create a false trend',
    (
        $invalidOwnershipResult[
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
 * RAW SELECTED COUNT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Raw Selected Count Contract<br>";
echo "============================================<br>";


$percentageConflictHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'selected' =>
                1000000,

            'selected_by_percent' =>
                99.9
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'selected' =>
                1250000,

            'selected_by_percent' =>
                1.0
        ]
    ];


$percentageConflictResult =
    $ownershipMovementMethod
        ->invoke(
            $service,
            $percentageConflictHistory
        );


marketOwnershipMovementCheck(
    'Ownership movement uses raw selected counts rather than stored percentages',
    (
        (int) (
            $percentageConflictResult[
                'change'
            ]
            ?? 0
        )
    )
    ===
    250000
);


marketOwnershipMovementCheck(
    'Conflicting percentage evidence does not alter ownership direction',
    (
        $percentageConflictResult[
            'direction'
        ]
        ?? null
    )
    ===
    'Rising'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * ZERO OWNERSHIP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Zero Ownership<br>";
echo "============================================<br>";


$zeroOwnershipHistory =
    [

        [
            'fpl_gameweek_id' =>
                1,

            'selected' =>
                0
        ],

        [
            'fpl_gameweek_id' =>
                2,

            'selected' =>
                100
        ]
    ];


$zeroOwnershipResult =
    $ownershipMovementMethod
        ->invoke(
            $service,
            $zeroOwnershipHistory
        );


marketOwnershipMovementCheck(
    'Zero selected managers is treated as valid historical evidence',
    (
        $zeroOwnershipResult[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


marketOwnershipMovementCheck(
    'Zero to 100 managers produces correct movement',
    (
        (int) (
            $zeroOwnershipResult[
                'change'
            ]
            ?? 0
        )
    )
    ===
    100
);


marketOwnershipMovementCheck(
    'Zero to positive ownership is classified Rising',
    (
        $zeroOwnershipResult[
            'direction'
        ]
        ?? null
    )
    ===
    'Rising'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * REAL DATABASE BEHAVIOUR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Real Database Behaviour<br>";
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


    $selectedGameweeks =
        [];


    foreach (
        $history
        as $historyRow
    ) {

        if (
            !is_numeric(
                $historyRow[
                    'selected'
                ]
                ?? null
            )
        ) {

            continue;
        }


        $gameweekId =
            (int) (
                $historyRow[
                    'fpl_gameweek_id'
                ]
                ?? 0
            );


        if (
            $gameweekId > 0
        ) {

            $selectedGameweeks[
                $gameweekId
            ] =
                true;
        }
    }


    if (
        empty(
            $selectedGameweeks
        )
    ) {

        continue;
    }


    $realPlayer = [

        'player' =>
            $player,

        'result' =>
            $result,

        'selected_gameweeks' =>
            $selectedGameweeks
    ];


    break;
}


marketOwnershipMovementCheck(
    'A real player with historical selected-count evidence resolves',
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


    $realOwnershipMovement =
        $realResult[
            'ownership_movement'
        ]
        ?? [];


    $realGameweekCount =
        count(
            $realPlayer[
                'selected_gameweeks'
            ]
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


    echo "Historical Selected Gameweeks: "
        . $realGameweekCount
        . "<br>";


    echo "Ownership Movement Status: "
        . htmlspecialchars(
            (string) (
                $realOwnershipMovement[
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

        marketOwnershipMovementCheck(
            'Real early-season player does not fabricate ownership trend',
            (
                $realOwnershipMovement[
                    'status'
                ]
                ?? null
            )
            ===
            'Insufficient Historical Data'
        );

    } else {

        marketOwnershipMovementCheck(
            'Real player exposes ownership movement once sufficient history exists',
            (
                $realOwnershipMovement[
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
 * SCENARIO L
 * OWNERSHIP MOVEMENT DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Ownership Movement Diagnostic<br>";
echo "============================================<br><br>";


echo "Controlled Rising: 1,000,000 → 1,250,000<br>";
echo "Expected Direction: Rising<br>";
echo "Expected Change: +250,000 managers<br><br>";


echo "Controlled Falling: 2,000,000 → 1,700,000<br>";
echo "Expected Direction: Falling<br>";
echo "Expected Change: -300,000 managers<br><br>";


echo "Controlled Stable: 1,500,000 → 1,500,000<br>";
echo "Expected Direction: Stable<br>";
echo "Expected Change: 0 managers<br><br>";


echo "Ownership Evidence Source: Raw selected-manager count<br>";
echo "Historical Percentage Required: No<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Market Intelligence Ownership Movement Test Summary<br>";
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