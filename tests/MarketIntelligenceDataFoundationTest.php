<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence Data Foundation Test<br>";
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

function marketDataFoundationCheck(
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
 * SNAPSHOT FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Gameweek Snapshot Foundation<br>";
echo "============================================<br>";


$snapshotSummaryStatement =
    $connection
        ->query(
            "
                SELECT
                    COUNT(*) AS row_count,
                    COUNT(DISTINCT gameweek_id) AS gameweek_count,
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
            "
        );


$snapshotSummary =
    $snapshotSummaryStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$snapshotRowCount =
    (int) (
        $snapshotSummary[
            'row_count'
        ]
        ?? 0
    );


$snapshotGameweekCount =
    (int) (
        $snapshotSummary[
            'gameweek_count'
        ]
        ?? 0
    );


$snapshotPlayerCount =
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


marketDataFoundationCheck(
    'Player gameweek snapshots contain historical rows',
    $snapshotRowCount > 0
);


marketDataFoundationCheck(
    'Snapshot history contains at least one gameweek',
    $snapshotGameweekCount > 0
);


marketDataFoundationCheck(
    'Snapshot price evidence is populated',
    $snapshotPriceRows > 0
);


marketDataFoundationCheck(
    'Snapshot ownership evidence is populated',
    $snapshotOwnershipRows > 0
);


echo "Snapshot Rows: "
    . number_format(
        $snapshotRowCount
    )
    . "<br>";


echo "Snapshot Gameweeks: "
    . number_format(
        $snapshotGameweekCount
    )
    . "<br>";


echo "Snapshot Players: "
    . number_format(
        $snapshotPlayerCount
    )
    . "<br>";


echo "Rows With Price: "
    . number_format(
        $snapshotPriceRows
    )
    . "<br>";


echo "Rows With Ownership: "
    . number_format(
        $snapshotOwnershipRows
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * SNAPSHOT GAMEWEEK DISTRIBUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Snapshot Gameweek Distribution<br>";
echo "============================================<br>";


$snapshotGameweeksStatement =
    $connection
        ->query(
            "
                SELECT
                    g.fpl_gameweek_id,
                    g.name,
                    COUNT(s.id) AS snapshot_rows,
                    COUNT(DISTINCT s.player_id) AS players,
                    SUM(
                        CASE
                            WHEN s.price IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS price_rows,
                    SUM(
                        CASE
                            WHEN s.selected_by_percent IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS ownership_rows
                FROM player_gameweek_snapshots s
                INNER JOIN gameweeks g
                    ON g.id = s.gameweek_id
                GROUP BY
                    g.id,
                    g.fpl_gameweek_id,
                    g.name
                ORDER BY
                    g.fpl_gameweek_id ASC
            "
        );


$snapshotGameweeks =
    $snapshotGameweeksStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


marketDataFoundationCheck(
    'Snapshot gameweek distribution resolves',
    is_array(
        $snapshotGameweeks
    )
    &&
    !empty(
        $snapshotGameweeks
    )
);


foreach (
    $snapshotGameweeks
    as $snapshotGameweek
) {

    echo "GW"
        . (
            $snapshotGameweek[
                'fpl_gameweek_id'
            ]
            ?? '—'
        )
        . " — "
        . htmlspecialchars(
            (string) (
                $snapshotGameweek[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " — Rows "
        . number_format(
            (int) (
                $snapshotGameweek[
                    'snapshot_rows'
                ]
                ?? 0
            )
        )
        . " — Players "
        . number_format(
            (int) (
                $snapshotGameweek[
                    'players'
                ]
                ?? 0
            )
        )
        . " — Price "
        . number_format(
            (int) (
                $snapshotGameweek[
                    'price_rows'
                ]
                ?? 0
            )
        )
        . " — Ownership "
        . number_format(
            (int) (
                $snapshotGameweek[
                    'ownership_rows'
                ]
                ?? 0
            )
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * FIXTURE-HISTORY MARKET FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Fixture-History Market Foundation<br>";
echo "============================================<br>";


$fixtureMarketStatement =
    $connection
        ->query(
            "
                SELECT
                    COUNT(*) AS row_count,
                    COUNT(DISTINCT gameweek_id) AS gameweek_count,
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
                    ) AS transfers_balance_rows
                FROM player_fixture_history
            "
        );


$fixtureMarketSummary =
    $fixtureMarketStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$fixtureHistoryRowCount =
    (int) (
        $fixtureMarketSummary[
            'row_count'
        ]
        ?? 0
    );


$fixtureHistoryGameweekCount =
    (int) (
        $fixtureMarketSummary[
            'gameweek_count'
        ]
        ?? 0
    );


$fixtureHistoryPlayerCount =
    (int) (
        $fixtureMarketSummary[
            'player_count'
        ]
        ?? 0
    );


$fixturePriceRows =
    (int) (
        $fixtureMarketSummary[
            'price_rows'
        ]
        ?? 0
    );


$fixtureSelectedRows =
    (int) (
        $fixtureMarketSummary[
            'selected_rows'
        ]
        ?? 0
    );


$fixtureTransfersInRows =
    (int) (
        $fixtureMarketSummary[
            'transfers_in_rows'
        ]
        ?? 0
    );


$fixtureTransfersOutRows =
    (int) (
        $fixtureMarketSummary[
            'transfers_out_rows'
        ]
        ?? 0
    );


$fixtureTransfersBalanceRows =
    (int) (
        $fixtureMarketSummary[
            'transfers_balance_rows'
        ]
        ?? 0
    );


marketDataFoundationCheck(
    'Fixture history contains real rows',
    $fixtureHistoryRowCount > 0
);


marketDataFoundationCheck(
    'Fixture-history price evidence is populated',
    $fixturePriceRows > 0
);


marketDataFoundationCheck(
    'Fixture-history selected-count evidence is populated',
    $fixtureSelectedRows > 0
);


marketDataFoundationCheck(
    'Fixture-history transfers-in evidence is populated',
    $fixtureTransfersInRows > 0
);


marketDataFoundationCheck(
    'Fixture-history transfers-out evidence is populated',
    $fixtureTransfersOutRows > 0
);


marketDataFoundationCheck(
    'Fixture-history transfer-balance evidence is populated',
    $fixtureTransfersBalanceRows > 0
);


echo "Fixture-History Rows: "
    . number_format(
        $fixtureHistoryRowCount
    )
    . "<br>";


echo "Fixture-History Gameweeks: "
    . number_format(
        $fixtureHistoryGameweekCount
    )
    . "<br>";


echo "Fixture-History Players: "
    . number_format(
        $fixtureHistoryPlayerCount
    )
    . "<br>";


echo "Rows With Price: "
    . number_format(
        $fixturePriceRows
    )
    . "<br>";


echo "Rows With Selected Count: "
    . number_format(
        $fixtureSelectedRows
    )
    . "<br>";


echo "Rows With Transfers In: "
    . number_format(
        $fixtureTransfersInRows
    )
    . "<br>";


echo "Rows With Transfers Out: "
    . number_format(
        $fixtureTransfersOutRows
    )
    . "<br>";


echo "Rows With Transfer Balance: "
    . number_format(
        $fixtureTransfersBalanceRows
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * NON-ZERO TRANSFER EVIDENCE
 * ============================================================
 *
 * A populated column is not enough. We also want to know
 * whether actual transfer activity exists in the stored data.
 */

echo "============================================<br>";
echo "Scenario D: Non-Zero Transfer Evidence<br>";
echo "============================================<br>";


$transferActivityStatement =
    $connection
        ->query(
            "
                SELECT
                    SUM(
                        CASE
                            WHEN COALESCE(transfers_in, 0) <> 0
                            THEN 1
                            ELSE 0
                        END
                    ) AS non_zero_in,
                    SUM(
                        CASE
                            WHEN COALESCE(transfers_out, 0) <> 0
                            THEN 1
                            ELSE 0
                        END
                    ) AS non_zero_out,
                    SUM(
                        CASE
                            WHEN COALESCE(transfers_balance, 0) <> 0
                            THEN 1
                            ELSE 0
                        END
                    ) AS non_zero_balance,
                    MAX(
                        COALESCE(
                            transfers_in,
                            0
                        )
                    ) AS max_transfers_in,
                    MAX(
                        COALESCE(
                            transfers_out,
                            0
                        )
                    ) AS max_transfers_out,
                    MAX(
                        ABS(
                            COALESCE(
                                transfers_balance,
                                0
                            )
                        )
                    ) AS max_absolute_balance
                FROM player_fixture_history
            "
        );


$transferActivity =
    $transferActivityStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$nonZeroTransfersIn =
    (int) (
        $transferActivity[
            'non_zero_in'
        ]
        ?? 0
    );


$nonZeroTransfersOut =
    (int) (
        $transferActivity[
            'non_zero_out'
        ]
        ?? 0
    );


$nonZeroTransferBalance =
    (int) (
        $transferActivity[
            'non_zero_balance'
        ]
        ?? 0
    );


echo "Transfers-In Activity: "
    . (
        $nonZeroTransfersIn > 0
            ? 'Available'
            : 'Not Yet Available'
    )
    . "<br>";


echo "Transfers-Out Activity: "
    . (
        $nonZeroTransfersOut > 0
            ? 'Available'
            : 'Not Yet Available'
    )
    . "<br>";


echo "Transfer-Balance Activity: "
    . (
        $nonZeroTransferBalance > 0
            ? 'Available'
            : 'Not Yet Available'
    )
    . "<br>";


echo "Non-Zero Transfers-In Rows: "
    . number_format(
        $nonZeroTransfersIn
    )
    . "<br>";


echo "Non-Zero Transfers-Out Rows: "
    . number_format(
        $nonZeroTransfersOut
    )
    . "<br>";


echo "Non-Zero Balance Rows: "
    . number_format(
        $nonZeroTransferBalance
    )
    . "<br>";


echo "Maximum Transfers In: "
    . number_format(
        (int) (
            $transferActivity[
                'max_transfers_in'
            ]
            ?? 0
        )
    )
    . "<br>";


echo "Maximum Transfers Out: "
    . number_format(
        (int) (
            $transferActivity[
                'max_transfers_out'
            ]
            ?? 0
        )
    )
    . "<br>";


echo "Maximum Absolute Balance: "
    . number_format(
        (int) (
            $transferActivity[
                'max_absolute_balance'
            ]
            ?? 0
        )
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * PRICE / OWNERSHIP MOVEMENT CAPABILITY
 * ============================================================
 *
 * Movement requires at least two historical gameweek states.
 *
 * At the start of the season, insufficient history is a valid
 * data-readiness state rather than a test failure.
 */

echo "============================================<br>";
echo "Scenario E: Historical Movement Capability<br>";
echo "============================================<br>";


echo "Price Movement Readiness: "
    . (
        $snapshotGameweekCount >= 2
            ? 'Ready'
            : 'Insufficient Historical Gameweeks'
    )
    . "<br>";


echo "Ownership Movement Readiness: "
    . (
        $snapshotGameweekCount >= 2
            ? 'Ready'
            : 'Insufficient Historical Gameweeks'
    )
    . "<br>";


echo "Transfer Momentum Readiness: "
    . (
        $fixtureHistoryGameweekCount >= 2
            ? 'Ready'
            : 'Insufficient Historical Gameweeks'
    )
    . "<br>";


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * REAL PLAYER MARKET SAMPLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Real Player Market Sample<br>";
echo "============================================<br>";


$playerSampleStatement =
    $connection
        ->query(
            "
                SELECT
                    p.id AS player_id,
                    p.web_name,
                    COUNT(DISTINCT s.gameweek_id) AS snapshot_gameweeks,
                    MIN(s.price) AS minimum_price,
                    MAX(s.price) AS maximum_price,
                    MIN(s.selected_by_percent) AS minimum_ownership,
                    MAX(s.selected_by_percent) AS maximum_ownership
                FROM players p
                INNER JOIN player_gameweek_snapshots s
                    ON s.player_id = p.id
                GROUP BY
                    p.id,
                    p.web_name
                ORDER BY
                    snapshot_gameweeks DESC,
                    p.id ASC
                LIMIT 1
            "
        );


$playerSample =
    $playerSampleStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


marketDataFoundationCheck(
    'A real player market-history sample resolves',
    is_array(
        $playerSample
    )
    &&
    !empty(
        $playerSample
    )
);


if (
    is_array(
        $playerSample
    )
) {

    echo "Player: "
        . htmlspecialchars(
            (string) (
                $playerSample[
                    'web_name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Player ID: "
        . (
            $playerSample[
                'player_id'
            ]
            ?? '—'
        )
        . "<br>";


    echo "Snapshot Gameweeks: "
        . (
            $playerSample[
                'snapshot_gameweeks'
            ]
            ?? 0
        )
        . "<br>";


    echo "Price Range: "
        . (
            $playerSample[
                'minimum_price'
            ]
            ?? '—'
        )
        . " → "
        . (
            $playerSample[
                'maximum_price'
            ]
            ?? '—'
        )
        . "<br>";


    echo "Ownership Range: "
        . (
            $playerSample[
                'minimum_ownership'
            ]
            ?? '—'
        )
        . "% → "
        . (
            $playerSample[
                'maximum_ownership'
            ]
            ?? '—'
        )
        . "%<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * FOUNDATION DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Market Foundation Diagnostic<br>";
echo "============================================<br><br>";


echo "GAMEWEEK SNAPSHOTS<br>";

echo "Rows: "
    . number_format(
        $snapshotRowCount
    )
    . "<br>";

echo "Distinct Gameweeks: "
    . number_format(
        $snapshotGameweekCount
    )
    . "<br>";

echo "Price Evidence: "
    . number_format(
        $snapshotPriceRows
    )
    . "<br>";

echo "Ownership Evidence: "
    . number_format(
        $snapshotOwnershipRows
    )
    . "<br><br>";


echo "FIXTURE HISTORY<br>";

echo "Rows: "
    . number_format(
        $fixtureHistoryRowCount
    )
    . "<br>";

echo "Distinct Gameweeks: "
    . number_format(
        $fixtureHistoryGameweekCount
    )
    . "<br>";

echo "Transfer-In Evidence: "
    . number_format(
        $fixtureTransfersInRows
    )
    . "<br>";

echo "Transfer-Out Evidence: "
    . number_format(
        $fixtureTransfersOutRows
    )
    . "<br>";

echo "Transfer-Balance Evidence: "
    . number_format(
        $fixtureTransfersBalanceRows
    )
    . "<br>";

echo "Non-Zero Transfer-In Rows: "
    . number_format(
        $nonZeroTransfersIn
    )
    . "<br>";

echo "Non-Zero Transfer-Out Rows: "
    . number_format(
        $nonZeroTransfersOut
    )
    . "<br>";

echo "Non-Zero Transfer-Balance Rows: "
    . number_format(
        $nonZeroTransferBalance
    )
    . "<br><br>";


echo "MOVEMENT READINESS<br>";

echo "Price / Ownership Trend: "
    . (
        $snapshotGameweekCount >= 2
            ? 'Ready'
            : 'Insufficient Historical Gameweeks'
    )
    . "<br>";


echo "Transfer Momentum: "
    . (
        $fixtureHistoryGameweekCount >= 2
            ? 'Ready'
            : 'Insufficient Historical Gameweeks'
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Market Intelligence Data Foundation Test Summary<br>";
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