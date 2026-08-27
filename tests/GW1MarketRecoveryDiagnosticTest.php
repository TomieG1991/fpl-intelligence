<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "GW1 Market Recovery Diagnostic Test<br>";
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

function gw1MarketRecoveryCheck(
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
 * DATABASE
 * ============================================================
 */

$database =
    new Database();


$connection =
    $database
        ->getConnection();


/*
 * ============================================================
 * SCENARIO A
 * RESOLVE GW1
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Resolve GW1<br>";
echo "============================================<br>";


$gw1Statement =
    $connection
        ->prepare(
            "
                SELECT *
                FROM gameweeks
                WHERE fpl_gameweek_id = 1
                LIMIT 1
            "
        );


$gw1Statement
    ->execute();


$gw1 =
    $gw1Statement
        ->fetch(
            PDO::FETCH_ASSOC
        );


gw1MarketRecoveryCheck(
    'GW1 resolves from local gameweek storage',
    is_array(
        $gw1
    )
);


if (
    !is_array(
        $gw1
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$gw1Id =
    (int) (
        $gw1[
            'id'
        ]
        ?? 0
    );


echo "Local GW1 ID: "
    . $gw1Id
    . "<br>";


echo "Finished: "
    . (
        !empty(
            $gw1[
                'finished'
            ]
            ?? false
        )
            ? 'Yes'
            : 'No'
    )
    . "<br>";


echo "Data Checked: "
    . (
        !empty(
            $gw1[
                'data_checked'
            ]
            ?? false
        )
            ? 'Yes'
            : 'No'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * SNAPSHOT COVERAGE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: GW1 Snapshot Coverage<br>";
echo "============================================<br>";


$snapshotStatement =
    $connection
        ->prepare(
            "
                SELECT
                    COUNT(*) AS row_count,
                    COUNT(DISTINCT player_id) AS player_count,
                    SUM(
                        CASE
                            WHEN price IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS price_rows,
                    SUM(
                        CASE
                            WHEN selected_by_percent IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS ownership_rows
                FROM player_gameweek_snapshots
                WHERE gameweek_id = :gameweek_id
            "
        );


$snapshotStatement
    ->execute(
        [
            'gameweek_id' =>
                $gw1Id
        ]
    );


$snapshotSummary =
    $snapshotStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$snapshotRows =
    (int) (
        $snapshotSummary[
            'row_count'
        ]
        ?? 0
    );


$snapshotPlayers =
    (int) (
        $snapshotSummary[
            'player_count'
        ]
        ?? 0
    );


$snapshotPriceRows =
    (int) (
        $snapshotSummary[
            'price_rows'
        ]
        ?? 0
    );


$snapshotOwnershipRows =
    (int) (
        $snapshotSummary[
            'ownership_rows'
        ]
        ?? 0
    );


gw1MarketRecoveryCheck(
    'GW1 snapshot rows exist',
    $snapshotRows > 0
);


echo "Snapshot Rows: "
    . number_format(
        $snapshotRows
    )
    . "<br>";


echo "Snapshot Players: "
    . number_format(
        $snapshotPlayers
    )
    . "<br>";


echo "Snapshot Price Rows: "
    . number_format(
        $snapshotPriceRows
    )
    . "<br>";


echo "Snapshot Ownership Rows: "
    . number_format(
        $snapshotOwnershipRows
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * FIXTURE-HISTORY MARKET COVERAGE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: GW1 Fixture-History Market Coverage<br>";
echo "============================================<br>";


$historyStatement =
    $connection
        ->prepare(
            "
                SELECT
                    COUNT(*) AS row_count,
                    COUNT(DISTINCT player_id) AS player_count,

                    SUM(
                        CASE
                            WHEN price IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS price_rows,

                    SUM(
                        CASE
                            WHEN selected IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS selected_rows,

                    SUM(
                        CASE
                            WHEN transfers_in IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS transfers_in_rows,

                    SUM(
                        CASE
                            WHEN transfers_out IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS transfers_out_rows,

                    SUM(
                        CASE
                            WHEN transfers_balance IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS transfer_balance_rows

                FROM player_fixture_history
                WHERE gameweek_id = :gameweek_id
            "
        );


$historyStatement
    ->execute(
        [
            'gameweek_id' =>
                $gw1Id
        ]
    );


$historySummary =
    $historyStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$historyRows =
    (int) (
        $historySummary[
            'row_count'
        ]
        ?? 0
    );


$historyPlayers =
    (int) (
        $historySummary[
            'player_count'
        ]
        ?? 0
    );


$historyPriceRows =
    (int) (
        $historySummary[
            'price_rows'
        ]
        ?? 0
    );


$historySelectedRows =
    (int) (
        $historySummary[
            'selected_rows'
        ]
        ?? 0
    );


$historyTransfersInRows =
    (int) (
        $historySummary[
            'transfers_in_rows'
        ]
        ?? 0
    );


$historyTransfersOutRows =
    (int) (
        $historySummary[
            'transfers_out_rows'
        ]
        ?? 0
    );


$historyTransferBalanceRows =
    (int) (
        $historySummary[
            'transfer_balance_rows'
        ]
        ?? 0
    );


gw1MarketRecoveryCheck(
    'GW1 fixture-history rows exist',
    $historyRows > 0
);


gw1MarketRecoveryCheck(
    'GW1 fixture history contains recoverable price data',
    $historyPriceRows > 0
);


gw1MarketRecoveryCheck(
    'GW1 fixture history contains recoverable selected-count data',
    $historySelectedRows > 0
);


echo "History Rows: "
    . number_format(
        $historyRows
    )
    . "<br>";


echo "History Players: "
    . number_format(
        $historyPlayers
    )
    . "<br>";


echo "History Price Rows: "
    . number_format(
        $historyPriceRows
    )
    . "<br>";


echo "History Selected Rows: "
    . number_format(
        $historySelectedRows
    )
    . "<br>";


echo "History Transfers-In Rows: "
    . number_format(
        $historyTransfersInRows
    )
    . "<br>";


echo "History Transfers-Out Rows: "
    . number_format(
        $historyTransfersOutRows
    )
    . "<br>";


echo "History Transfer-Balance Rows: "
    . number_format(
        $historyTransferBalanceRows
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * PLAYER-BY-PLAYER RECOVERY COVERAGE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Player-by-Player Recovery Coverage<br>";
echo "============================================<br>";


$recoveryCoverageStatement =
    $connection
        ->prepare(
            "
                SELECT
                    COUNT(*) AS matched_rows,

                    SUM(
                        CASE
                            WHEN h.price IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS recoverable_price_rows,

                    SUM(
                        CASE
                            WHEN h.selected IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS recoverable_selected_rows,

                    SUM(
                        CASE
                            WHEN h.transfers_in IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS recoverable_transfers_in_rows,

                    SUM(
                        CASE
                            WHEN h.transfers_out IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS recoverable_transfers_out_rows,

                    SUM(
                        CASE
                            WHEN h.transfers_balance IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS recoverable_balance_rows

                FROM player_gameweek_snapshots s

                INNER JOIN player_fixture_history h
                    ON h.player_id = s.player_id
                    AND h.gameweek_id = s.gameweek_id

                WHERE
                    s.gameweek_id = :gameweek_id
            "
        );


$recoveryCoverageStatement
    ->execute(
        [
            'gameweek_id' =>
                $gw1Id
        ]
    );


$recoveryCoverage =
    $recoveryCoverageStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$matchedRows =
    (int) (
        $recoveryCoverage[
            'matched_rows'
        ]
        ?? 0
    );


$recoverablePriceRows =
    (int) (
        $recoveryCoverage[
            'recoverable_price_rows'
        ]
        ?? 0
    );


$recoverableSelectedRows =
    (int) (
        $recoveryCoverage[
            'recoverable_selected_rows'
        ]
        ?? 0
    );


$recoverableTransfersInRows =
    (int) (
        $recoveryCoverage[
            'recoverable_transfers_in_rows'
        ]
        ?? 0
    );


$recoverableTransfersOutRows =
    (int) (
        $recoveryCoverage[
            'recoverable_transfers_out_rows'
        ]
        ?? 0
    );


$recoverableBalanceRows =
    (int) (
        $recoveryCoverage[
            'recoverable_balance_rows'
        ]
        ?? 0
    );


gw1MarketRecoveryCheck(
    'GW1 snapshots match fixture-history rows',
    $matchedRows > 0
);


echo "Matched Snapshot/History Rows: "
    . number_format(
        $matchedRows
    )
    . "<br>";


echo "Recoverable Price Rows: "
    . number_format(
        $recoverablePriceRows
    )
    . "<br>";


echo "Recoverable Selected Rows: "
    . number_format(
        $recoverableSelectedRows
    )
    . "<br>";


echo "Recoverable Transfers-In Rows: "
    . number_format(
        $recoverableTransfersInRows
    )
    . "<br>";


echo "Recoverable Transfers-Out Rows: "
    . number_format(
        $recoverableTransfersOutRows
    )
    . "<br>";


echo "Recoverable Transfer-Balance Rows: "
    . number_format(
        $recoverableBalanceRows
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * PRICE DIFFERENCE DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Snapshot vs Historical Price Diagnostic<br>";
echo "============================================<br>";


$priceDifferenceStatement =
    $connection
        ->prepare(
            "
                SELECT
                    p.web_name,
                    s.price AS snapshot_price,
                    h.price AS historical_price,
                    (
                        s.price - h.price
                    ) AS price_difference

                FROM player_gameweek_snapshots s

                INNER JOIN player_fixture_history h
                    ON h.player_id = s.player_id
                    AND h.gameweek_id = s.gameweek_id

                INNER JOIN players p
                    ON p.id = s.player_id

                WHERE
                    s.gameweek_id = :gameweek_id
                    AND
                    s.price IS NOT NULL
                    AND
                    h.price IS NOT NULL
                    AND
                    s.price <> h.price

                ORDER BY
                    ABS(
                        s.price - h.price
                    ) DESC,
                    p.id ASC

                LIMIT 20
            "
        );


$priceDifferenceStatement
    ->execute(
        [
            'gameweek_id' =>
                $gw1Id
        ]
    );


$priceDifferences =
    $priceDifferenceStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


echo "Players With Snapshot/Historical Price Difference: "
    . number_format(
        count(
            $priceDifferences
        )
    )
    . "<br>";


if (
    empty(
        $priceDifferences
    )
) {

    echo "No sampled price differences detected.<br>";

} else {

    foreach (
        $priceDifferences
        as $row
    ) {

        $difference =
            (float) (
                $row[
                    'price_difference'
                ]
                ?? 0
            );


        echo htmlspecialchars(
            (string) (
                $row[
                    'web_name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " — Snapshot £"
        . number_format(
            (float) (
                $row[
                    'snapshot_price'
                ]
                ?? 0
            ),
            1
        )
        . "m — Historical £"
        . number_format(
            (float) (
                $row[
                    'historical_price'
                ]
                ?? 0
            ),
            1
        )
        . "m — Difference "
        . (
            $difference > 0
                ? '+'
                : ''
        )
        . number_format(
            $difference,
            1
        )
        . "m<br>";
    }
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * SELECTED-COUNT SAMPLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Historical Selected-Count Sample<br>";
echo "============================================<br>";


$selectedSampleStatement =
    $connection
        ->prepare(
            "
                SELECT
                    p.id AS player_id,
                    p.web_name,
                    h.price,
                    h.selected,
                    h.transfers_in,
                    h.transfers_out,
                    h.transfers_balance

                FROM player_fixture_history h

                INNER JOIN players p
                    ON p.id = h.player_id

                WHERE
                    h.gameweek_id = :gameweek_id
                    AND
                    h.selected IS NOT NULL

                ORDER BY
                    h.selected DESC,
                    p.id ASC

                LIMIT 10
            "
        );


$selectedSampleStatement
    ->execute(
        [
            'gameweek_id' =>
                $gw1Id
        ]
    );


$selectedSamples =
    $selectedSampleStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


gw1MarketRecoveryCheck(
    'Historical selected-count sample resolves',
    !empty(
        $selectedSamples
    )
);


foreach (
    $selectedSamples
    as $row
) {

    echo htmlspecialchars(
        (string) (
            $row[
                'web_name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " — Price £"
    . (
        is_numeric(
            $row[
                'price'
            ]
            ?? null
        )
            ? number_format(
                (float) $row[
                    'price'
                ],
                1
            )
            : '—'
    )
    . "m — Selected "
    . number_format(
        (int) (
            $row[
                'selected'
            ]
            ?? 0
        )
    )
    . " — In "
    . number_format(
        (int) (
            $row[
                'transfers_in'
            ]
            ?? 0
        )
    )
    . " — Out "
    . number_format(
        (int) (
            $row[
                'transfers_out'
            ]
            ?? 0
        )
    )
    . " — Balance "
    . number_format(
        (int) (
            $row[
                'transfers_balance'
            ]
            ?? 0
        )
    )
    . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * RECOVERY READINESS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: GW1 Recovery Readiness<br>";
echo "============================================<br>";


$priceRecoveryReady =
    $recoverablePriceRows > 0;


$selectedRecoveryReady =
    $recoverableSelectedRows > 0;


$transferRecoveryReady =
    $recoverableTransfersInRows > 0
    &&
    $recoverableTransfersOutRows > 0
    &&
    $recoverableBalanceRows > 0;


echo "GW1 Price Recovery: "
    . (
        $priceRecoveryReady
            ? 'Ready'
            : 'Not Available'
    )
    . "<br>";


echo "GW1 Selected-Count Recovery: "
    . (
        $selectedRecoveryReady
            ? 'Ready'
            : 'Not Available'
    )
    . "<br>";


echo "GW1 Transfer Field Recovery: "
    . (
        $transferRecoveryReady
            ? 'Ready'
            : 'Not Available'
    )
    . "<br>";


echo "GW1 Ownership Percentage Recovery: "
    . "Requires trustworthy GW1 total-player denominator"
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * RECOVERY DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: GW1 Recovery Diagnostic<br>";
echo "============================================<br><br>";


echo "Snapshot Rows: "
    . number_format(
        $snapshotRows
    )
    . "<br>";


echo "Fixture-History Rows: "
    . number_format(
        $historyRows
    )
    . "<br>";


echo "Matched Rows: "
    . number_format(
        $matchedRows
    )
    . "<br>";


echo "Recoverable Price Rows: "
    . number_format(
        $recoverablePriceRows
    )
    . "<br>";


echo "Recoverable Selected Rows: "
    . number_format(
        $recoverableSelectedRows
    )
    . "<br>";


echo "Recoverable Transfer Rows: "
    . number_format(
        min(
            $recoverableTransfersInRows,
            $recoverableTransfersOutRows,
            $recoverableBalanceRows
        )
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "GW1 Market Recovery Diagnostic Test Summary<br>";
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