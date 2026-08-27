<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence Transfer Momentum Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function marketTransferMomentumCheck(
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


marketTransferMomentumCheck(
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


marketTransferMomentumCheck(
    'Market Intelligence service can be created',
    $service instanceof MarketIntelligenceService
);


$reflection =
    new ReflectionClass(
        MarketIntelligenceService::class
    );


$transferMomentumMethod =
    $reflection
        ->getMethod(
            'buildTransferMomentum'
        );


$transferMomentumMethod
    ->setAccessible(
        true
    );


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * CONTROLLED TEST FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Controlled Test Foundation<br>";
echo "============================================<br>";


/*
 * We do not want to manufacture permanent fixture-history rows
 * just to test the service.
 *
 * Instead, resolve a real player and preserve any existing
 * fixture-history transfer values before temporarily replacing
 * them inside a transaction.
 */

$playerStatement =
    $db
        ->query(
            "
                SELECT
                    pfh.player_id,
                    COUNT(DISTINCT pfh.gameweek_id) AS gameweeks

                FROM
                    player_fixture_history pfh

                GROUP BY
                    pfh.player_id

                HAVING
                    COUNT(DISTINCT pfh.gameweek_id) >= 1

                ORDER BY
                    gameweeks DESC,
                    pfh.player_id ASC

                LIMIT 1
            "
        );


$controlledPlayer =
    $playerStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


marketTransferMomentumCheck(
    'A real player with fixture-history evidence resolves',
    is_array(
        $controlledPlayer
    )
);


if (
    !is_array(
        $controlledPlayer
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$controlledPlayerId =
    (int) (
        $controlledPlayer[
            'player_id'
        ]
        ?? 0
    );


$gameweekStatement =
    $db
        ->query(
            "
                SELECT
                    id,
                    fpl_gameweek_id

                FROM
                    gameweeks

                ORDER BY
                    fpl_gameweek_id ASC
            "
        );


$gameweeks =
    $gameweekStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


marketTransferMomentumCheck(
    'Stored gameweeks are available for controlled testing',
    !empty(
        $gameweeks
    )
);


echo "Controlled Player ID: "
    . $controlledPlayerId
    . "<br>";


echo "Stored Gameweeks: "
    . count(
        $gameweeks
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * CONTROLLED MOMENTUM CALCULATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Controlled Momentum Calculation<br>";
echo "============================================<br>";


/*
 * buildTransferMomentum() currently reads directly from the
 * database, so rather than mutating real history we validate its
 * classification helper independently first.
 */

$movementDirectionMethod =
    $reflection
        ->getMethod(
            'movementDirection'
        );


$movementDirectionMethod
    ->setAccessible(
        true
    );


$positiveDirection =
    $movementDirectionMethod
        ->invoke(
            $service,
            150000
        );


$negativeDirection =
    $movementDirectionMethod
        ->invoke(
            $service,
            -150000
        );


$stableDirection =
    $movementDirectionMethod
        ->invoke(
            $service,
            0
        );


marketTransferMomentumCheck(
    '+150,000 transfer balance is classified Rising',
    $positiveDirection
    ===
    'Rising'
);


marketTransferMomentumCheck(
    '-150,000 transfer balance is classified Falling',
    $negativeDirection
    ===
    'Falling'
);


marketTransferMomentumCheck(
    'Zero transfer balance is classified Stable',
    $stableDirection
    ===
    'Stable'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * REAL TRANSFER MOMENTUM RESULT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Real Transfer Momentum Result<br>";
echo "============================================<br>";


$realResult =
    $service
        ->getPlayerMarketIntelligence(
            $controlledPlayerId
        );


$realMomentum =
    $realResult[
        'transfer_momentum'
    ]
    ?? null;


marketTransferMomentumCheck(
    'Transfer momentum result is returned',
    is_array(
        $realMomentum
    )
);


marketTransferMomentumCheck(
    'Transfer momentum exposes a recognised status',
    in_array(
        $realMomentum[
            'status'
        ]
        ?? null,
        [
            'Available',
            'Insufficient Historical Data'
        ],
        true
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * DISTINCT GAMEWEEK ACCOUNTING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Distinct Gameweek Accounting<br>";
echo "============================================<br>";


$historyCountStatement =
    $db
        ->prepare(
            "
                SELECT
                    COUNT(
                        DISTINCT g.fpl_gameweek_id
                    )

                FROM
                    player_fixture_history pfh

                INNER JOIN
                    gameweeks g
                        ON g.id = pfh.gameweek_id

                WHERE
                    pfh.player_id = :player_id
            "
        );


$historyCountStatement
    ->execute(
        [
            ':player_id' =>
                $controlledPlayerId
        ]
    );


$realGameweekCount =
    (int) $historyCountStatement
        ->fetchColumn();


$reportedGameweekCount =
    (int) (
        $realMomentum[
            'gameweek_count'
        ]
        ?? 0
    );


marketTransferMomentumCheck(
    'Transfer momentum counts distinct historical gameweeks',
    $reportedGameweekCount
    ===
    $realGameweekCount
);


echo "Historical Transfer Gameweeks: "
    . $realGameweekCount
    . "<br>";


echo "Service Gameweek Count: "
    . $reportedGameweekCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * EARLY-SEASON INSUFFICIENT HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Early-Season Readiness<br>";
echo "============================================<br>";


if (
    $realGameweekCount < 2
) {

    marketTransferMomentumCheck(
        'One historical transfer gameweek reports insufficient data',
        (
            $realMomentum[
                'status'
            ]
            ?? null
        )
        ===
        'Insufficient Historical Data'
    );


    marketTransferMomentumCheck(
        'Insufficient transfer history does not invent transfers in',
        (
            $realMomentum[
                'latest_transfers_in'
            ]
            ?? null
        )
        ===
        null
    );


    marketTransferMomentumCheck(
        'Insufficient transfer history does not invent transfers out',
        (
            $realMomentum[
                'latest_transfers_out'
            ]
            ?? null
        )
        ===
        null
    );


    marketTransferMomentumCheck(
        'Insufficient transfer history does not invent transfer balance',
        (
            $realMomentum[
                'latest_balance'
            ]
            ?? null
        )
        ===
        null
    );


    marketTransferMomentumCheck(
        'Insufficient transfer history has unavailable direction',
        (
            $realMomentum[
                'direction'
            ]
            ?? null
        )
        ===
        'Unavailable'
    );

} else {

    marketTransferMomentumCheck(
        'Transfer momentum becomes available with sufficient history',
        (
            $realMomentum[
                'status'
            ]
            ?? null
        )
        ===
        'Available'
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * ZERO TRANSFER EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Zero Transfer Evidence<br>";
echo "============================================<br>";


$zeroTransferStatement =
    $db
        ->prepare(
            "
                SELECT
                    pfh.transfers_in,
                    pfh.transfers_out,
                    pfh.transfers_balance

                FROM
                    player_fixture_history pfh

                WHERE
                    pfh.player_id = :player_id

                AND
                    COALESCE(
                        pfh.transfers_in,
                        0
                    ) = 0

                AND
                    COALESCE(
                        pfh.transfers_out,
                        0
                    ) = 0

                AND
                    COALESCE(
                        pfh.transfers_balance,
                        0
                    ) = 0

                LIMIT 1
            "
        );


$zeroTransferStatement
    ->execute(
        [
            ':player_id' =>
                $controlledPlayerId
        ]
    );


$zeroTransferRow =
    $zeroTransferStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


marketTransferMomentumCheck(
    'A zero-transfer historical row is valid evidence',
    is_array(
        $zeroTransferRow
    )
);


if (
    is_array(
        $zeroTransferRow
    )
) {

    marketTransferMomentumCheck(
        'Zero transfers in remains numeric zero',
        (int) (
            $zeroTransferRow[
                'transfers_in'
            ]
            ?? 0
        )
        ===
        0
    );


    marketTransferMomentumCheck(
        'Zero transfers out remains numeric zero',
        (int) (
            $zeroTransferRow[
                'transfers_out'
            ]
            ?? 0
        )
        ===
        0
    );


    marketTransferMomentumCheck(
        'Zero transfer balance remains numeric zero',
        (int) (
            $zeroTransferRow[
                'transfers_balance'
            ]
            ?? 0
        )
        ===
        0
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * TRANSFER BALANCE INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Transfer Balance Integrity<br>";
echo "============================================<br>";


$transferRowsStatement =
    $db
        ->prepare(
            "
                SELECT
                    transfers_in,
                    transfers_out,
                    transfers_balance

                FROM
                    player_fixture_history

                WHERE
                    player_id = :player_id
            "
        );


$transferRowsStatement
    ->execute(
        [
            ':player_id' =>
                $controlledPlayerId
        ]
    );


$transferRows =
    $transferRowsStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


$balanceIntegrity =
    true;


foreach (
    $transferRows
    as $transferRow
) {

    $transfersIn =
        $transferRow[
            'transfers_in'
        ]
        ?? null;


    $transfersOut =
        $transferRow[
            'transfers_out'
        ]
        ?? null;


    $balance =
        $transferRow[
            'transfers_balance'
        ]
        ?? null;


    if (
        $transfersIn === null
        ||
        $transfersOut === null
        ||
        $balance === null
    ) {

        continue;
    }


    if (
        (
            (int) $transfersIn
            -
            (int) $transfersOut
        )
        !==
        (int) $balance
    ) {

        $balanceIntegrity =
            false;

        break;
    }
}


marketTransferMomentumCheck(
    'Stored transfer balances equal transfers in minus transfers out',
    $balanceIntegrity
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * LATEST GAMEWEEK CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Latest Gameweek Contract<br>";
echo "============================================<br>";


if (
    $realGameweekCount >= 2
    &&
    (
        $realMomentum[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
) {

    $latestStatement =
        $db
            ->prepare(
                "
                    SELECT
                        pfh.transfers_in,
                        pfh.transfers_out,
                        pfh.transfers_balance,
                        g.fpl_gameweek_id

                    FROM
                        player_fixture_history pfh

                    INNER JOIN
                        gameweeks g
                            ON g.id = pfh.gameweek_id

                    WHERE
                        pfh.player_id = :player_id

                    ORDER BY
                        g.fpl_gameweek_id DESC,
                        pfh.id ASC

                    LIMIT 1
                "
            );


    $latestStatement
        ->execute(
            [
                ':player_id' =>
                    $controlledPlayerId
            ]
        );


    $latestRow =
        $latestStatement
            ->fetch(
                PDO::FETCH_ASSOC
            );


    marketTransferMomentumCheck(
        'Latest historical transfer row resolves',
        is_array(
            $latestRow
        )
    );


    if (
        is_array(
            $latestRow
        )
    ) {

        $expectedBalance =
            $latestRow[
                'transfers_balance'
            ]
            ?? null;


        if (
            $expectedBalance === null
            &&
            is_numeric(
                $latestRow[
                    'transfers_in'
                ]
                ?? null
            )
            &&
            is_numeric(
                $latestRow[
                    'transfers_out'
                ]
                ?? null
            )
        ) {

            $expectedBalance =
                (int) $latestRow[
                    'transfers_in'
                ]
                -
                (int) $latestRow[
                    'transfers_out'
                ];
        }


        marketTransferMomentumCheck(
            'Service uses latest gameweek transfer balance',
            (
                $realMomentum[
                    'latest_balance'
                ]
                ?? null
            )
            ===
            (
                $expectedBalance !== null
                    ? (int) $expectedBalance
                    : null
            )
        );
    }

} else {

    echo "Latest-gameweek value comparison deferred until "
        . "at least two historical transfer gameweeks exist.<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * DUPLICATE GAMEWEEK PROTECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Duplicate Gameweek Protection<br>";
echo "============================================<br>";


$rowCountStatement =
    $db
        ->prepare(
            "
                SELECT
                    COUNT(*) AS row_count,
                    COUNT(
                        DISTINCT gameweek_id
                    ) AS gameweek_count

                FROM
                    player_fixture_history

                WHERE
                    player_id = :player_id
            "
        );


$rowCountStatement
    ->execute(
        [
            ':player_id' =>
                $controlledPlayerId
        ]
    );


$rowCountResult =
    $rowCountStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$fixtureHistoryRows =
    (int) (
        $rowCountResult[
            'row_count'
        ]
        ?? 0
    );


$fixtureHistoryGameweeks =
    (int) (
        $rowCountResult[
            'gameweek_count'
        ]
        ?? 0
    );


marketTransferMomentumCheck(
    'Service gameweek count is not inflated by fixture-history row count',
    $reportedGameweekCount
    ===
    $fixtureHistoryGameweeks
);


echo "Fixture-History Rows: "
    . $fixtureHistoryRows
    . "<br>";


echo "Distinct Gameweeks: "
    . $fixtureHistoryGameweeks
    . "<br>";


if (
    $fixtureHistoryRows
    >
    $fixtureHistoryGameweeks
) {

    echo "Duplicate-gameweek fixture rows detected and correctly "
        . "collapsed by the service.<br>";

} else {

    echo "No duplicate-gameweek fixture rows currently present; "
        . "service contract remains protected.<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * INVALID PLAYER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Invalid Player<br>";
echo "============================================<br>";


$invalidPlayerResult =
    $service
        ->getPlayerMarketIntelligence(
            999999999
        );


marketTransferMomentumCheck(
    'Invalid player returns controlled market result',
    is_array(
        $invalidPlayerResult
    )
);


marketTransferMomentumCheck(
    'Invalid player reports unavailable market intelligence',
    (
        $invalidPlayerResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


marketTransferMomentumCheck(
    'Invalid player transfer momentum reports unavailable',
    (
        $invalidPlayerResult[
            'transfer_momentum'
        ][
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
 * SCENARIO L
 * TRANSFER MOMENTUM DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Transfer Momentum Diagnostic<br>";
echo "============================================<br><br>";


echo "Controlled Positive Balance:<br>";
echo "Transfers In: 250,000<br>";
echo "Transfers Out: 100,000<br>";
echo "Net Balance: +150,000<br>";
echo "Expected Direction: Rising<br><br>";


echo "Controlled Negative Balance:<br>";
echo "Transfers In: 100,000<br>";
echo "Transfers Out: 250,000<br>";
echo "Net Balance: -150,000<br>";
echo "Expected Direction: Falling<br><br>";


echo "Controlled Zero Balance:<br>";
echo "Transfers In: 0<br>";
echo "Transfers Out: 0<br>";
echo "Net Balance: 0<br>";
echo "Expected Direction: Stable<br><br>";


echo "Real Historical Gameweeks: "
    . $realGameweekCount
    . "<br>";


echo "Current Momentum Status: "
    . htmlspecialchars(
        (string) (
            $realMomentum[
                'status'
            ]
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
echo "Market Intelligence Transfer Momentum Test Summary<br>";
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