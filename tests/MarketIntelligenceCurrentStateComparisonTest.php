<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Market Intelligence Current State Comparison Test<br>";
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

function marketCurrentStateCheck(
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
 * CURRENT LIVE PLAYER FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Current Live Player Foundation<br>";
echo "============================================<br>";


$currentSummaryStatement =
    $connection
        ->query(
            "
                SELECT
                    COUNT(*) AS player_count,
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
                FROM players
            "
        );


$currentSummary =
    $currentSummaryStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$currentPlayerCount =
    (int) (
        $currentSummary[
            'player_count'
        ]
        ?? 0
    );


$currentPriceRows =
    (int) (
        $currentSummary[
            'price_rows'
        ]
        ?? 0
    );


$currentOwnershipRows =
    (int) (
        $currentSummary[
            'ownership_rows'
        ]
        ?? 0
    );


marketCurrentStateCheck(
    'Current players table contains live player rows',
    $currentPlayerCount > 0
);


marketCurrentStateCheck(
    'Current player prices are populated',
    $currentPriceRows > 0
);


marketCurrentStateCheck(
    'Current player ownership is populated',
    $currentOwnershipRows > 0
);


echo "Current Players: "
    . number_format(
        $currentPlayerCount
    )
    . "<br>";


echo "Current Price Rows: "
    . number_format(
        $currentPriceRows
    )
    . "<br>";


echo "Current Ownership Rows: "
    . number_format(
        $currentOwnershipRows
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * LATEST HISTORICAL SNAPSHOT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Latest Historical Snapshot<br>";
echo "============================================<br>";


$latestSnapshotGameweekStatement =
    $connection
        ->query(
            "
                SELECT
                    g.id AS gameweek_id,
                    g.fpl_gameweek_id,
                    g.name,
                    COUNT(s.id) AS snapshot_rows
                FROM gameweeks g
                INNER JOIN player_gameweek_snapshots s
                    ON s.gameweek_id = g.id
                GROUP BY
                    g.id,
                    g.fpl_gameweek_id,
                    g.name
                ORDER BY
                    g.fpl_gameweek_id DESC
                LIMIT 1
            "
        );


$latestSnapshotGameweek =
    $latestSnapshotGameweekStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


marketCurrentStateCheck(
    'Latest historical snapshot gameweek resolves',
    is_array(
        $latestSnapshotGameweek
    )
    &&
    !empty(
        $latestSnapshotGameweek
    )
);


$latestSnapshotGameweekId =
    (int) (
        $latestSnapshotGameweek[
            'gameweek_id'
        ]
        ?? 0
    );


echo "Snapshot Gameweek: GW"
    . (
        $latestSnapshotGameweek[
            'fpl_gameweek_id'
        ]
        ?? '—'
    )
    . " — "
    . htmlspecialchars(
        (string) (
            $latestSnapshotGameweek[
                'name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Snapshot Rows: "
    . number_format(
        (int) (
            $latestSnapshotGameweek[
                'snapshot_rows'
            ]
            ?? 0
        )
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * SNAPSHOT VS CURRENT COVERAGE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Snapshot vs Current Coverage<br>";
echo "============================================<br>";


$comparisonSummaryStatement =
    $connection
        ->prepare(
            "
                SELECT
                    COUNT(*) AS matched_players,
                    SUM(
                        CASE
                            WHEN s.price IS NOT NULL
                            AND p.price IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS comparable_price_rows,
                    SUM(
                        CASE
                            WHEN s.selected_by_percent IS NOT NULL
                            AND p.selected_by_percent IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS comparable_ownership_rows
                FROM player_gameweek_snapshots s
                INNER JOIN players p
                    ON p.id = s.player_id
                WHERE
                    s.gameweek_id = :gameweek_id
            "
        );


$comparisonSummaryStatement
    ->execute(
        [
            'gameweek_id' =>
                $latestSnapshotGameweekId
        ]
    );


$comparisonSummary =
    $comparisonSummaryStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$matchedPlayers =
    (int) (
        $comparisonSummary[
            'matched_players'
        ]
        ?? 0
    );


$comparablePriceRows =
    (int) (
        $comparisonSummary[
            'comparable_price_rows'
        ]
        ?? 0
    );


$comparableOwnershipRows =
    (int) (
        $comparisonSummary[
            'comparable_ownership_rows'
        ]
        ?? 0
    );


marketCurrentStateCheck(
    'Historical snapshot players match current player rows',
    $matchedPlayers > 0
);


marketCurrentStateCheck(
    'Price comparison rows are available',
    $comparablePriceRows > 0
);


marketCurrentStateCheck(
    'Ownership comparison rows are available',
    $comparableOwnershipRows > 0
);


echo "Matched Players: "
    . number_format(
        $matchedPlayers
    )
    . "<br>";


echo "Comparable Price Rows: "
    . number_format(
        $comparablePriceRows
    )
    . "<br>";


echo "Comparable Ownership Rows: "
    . number_format(
        $comparableOwnershipRows
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * PRICE MOVEMENT SINCE SNAPSHOT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Price Movement Since Snapshot<br>";
echo "============================================<br>";


$priceMovementStatement =
    $connection
        ->prepare(
            "
                SELECT
                    COUNT(*) AS changed_price_rows,

                    SUM(
                        CASE
                            WHEN p.price > s.price
                            THEN 1
                            ELSE 0
                        END
                    ) AS price_rises,

                    SUM(
                        CASE
                            WHEN p.price < s.price
                            THEN 1
                            ELSE 0
                        END
                    ) AS price_falls,

                    MAX(
                        p.price - s.price
                    ) AS maximum_price_rise,

                    MIN(
                        p.price - s.price
                    ) AS maximum_price_fall

                FROM player_gameweek_snapshots s

                INNER JOIN players p
                    ON p.id = s.player_id

                WHERE
                    s.gameweek_id = :gameweek_id
                    AND
                    s.price IS NOT NULL
                    AND
                    p.price IS NOT NULL
                    AND
                    p.price <> s.price
            "
        );


$priceMovementStatement
    ->execute(
        [
            'gameweek_id' =>
                $latestSnapshotGameweekId
        ]
    );


$priceMovement =
    $priceMovementStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$changedPriceRows =
    (int) (
        $priceMovement[
            'changed_price_rows'
        ]
        ?? 0
    );


$priceRises =
    (int) (
        $priceMovement[
            'price_rises'
        ]
        ?? 0
    );


$priceFalls =
    (int) (
        $priceMovement[
            'price_falls'
        ]
        ?? 0
    );


echo "Players With Price Change: "
    . number_format(
        $changedPriceRows
    )
    . "<br>";


echo "Price Rises: "
    . number_format(
        $priceRises
    )
    . "<br>";


echo "Price Falls: "
    . number_format(
        $priceFalls
    )
    . "<br>";


echo "Maximum Rise: "
    . (
        is_numeric(
            $priceMovement[
                'maximum_price_rise'
            ]
            ?? null
        )
            ? number_format(
                (float) $priceMovement[
                    'maximum_price_rise'
                ],
                1
            )
            : '0.0'
    )
    . "m<br>";


echo "Maximum Fall: "
    . (
        is_numeric(
            $priceMovement[
                'maximum_price_fall'
            ]
            ?? null
        )
            ? number_format(
                (float) $priceMovement[
                    'maximum_price_fall'
                ],
                1
            )
            : '0.0'
    )
    . "m<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * OWNERSHIP MOVEMENT SINCE SNAPSHOT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Ownership Movement Since Snapshot<br>";
echo "============================================<br>";


$ownershipMovementStatement =
    $connection
        ->prepare(
            "
                SELECT
                    COUNT(*) AS changed_ownership_rows,

                    SUM(
                        CASE
                            WHEN p.selected_by_percent
                                >
                                s.selected_by_percent
                            THEN 1
                            ELSE 0
                        END
                    ) AS ownership_rises,

                    SUM(
                        CASE
                            WHEN p.selected_by_percent
                                <
                                s.selected_by_percent
                            THEN 1
                            ELSE 0
                        END
                    ) AS ownership_falls,

                    MAX(
                        p.selected_by_percent
                        -
                        s.selected_by_percent
                    ) AS maximum_ownership_rise,

                    MIN(
                        p.selected_by_percent
                        -
                        s.selected_by_percent
                    ) AS maximum_ownership_fall

                FROM player_gameweek_snapshots s

                INNER JOIN players p
                    ON p.id = s.player_id

                WHERE
                    s.gameweek_id = :gameweek_id
                    AND
                    s.selected_by_percent IS NOT NULL
                    AND
                    p.selected_by_percent IS NOT NULL
                    AND
                    p.selected_by_percent
                        <>
                        s.selected_by_percent
            "
        );


$ownershipMovementStatement
    ->execute(
        [
            'gameweek_id' =>
                $latestSnapshotGameweekId
        ]
    );


$ownershipMovement =
    $ownershipMovementStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$changedOwnershipRows =
    (int) (
        $ownershipMovement[
            'changed_ownership_rows'
        ]
        ?? 0
    );


$ownershipRises =
    (int) (
        $ownershipMovement[
            'ownership_rises'
        ]
        ?? 0
    );


$ownershipFalls =
    (int) (
        $ownershipMovement[
            'ownership_falls'
        ]
        ?? 0
    );


echo "Players With Ownership Change: "
    . number_format(
        $changedOwnershipRows
    )
    . "<br>";


echo "Ownership Rises: "
    . number_format(
        $ownershipRises
    )
    . "<br>";


echo "Ownership Falls: "
    . number_format(
        $ownershipFalls
    )
    . "<br>";


echo "Maximum Ownership Rise: "
    . (
        is_numeric(
            $ownershipMovement[
                'maximum_ownership_rise'
            ]
            ?? null
        )
            ? number_format(
                (float) $ownershipMovement[
                    'maximum_ownership_rise'
                ],
                2
            )
            : '0.00'
    )
    . "%<br>";


echo "Maximum Ownership Fall: "
    . (
        is_numeric(
            $ownershipMovement[
                'maximum_ownership_fall'
            ]
            ?? null
        )
            ? number_format(
                (float) $ownershipMovement[
                    'maximum_ownership_fall'
                ],
                2
            )
            : '0.00'
    )
    . "%<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * LARGEST REAL PRICE MOVEMENTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Largest Real Price Movements<br>";
echo "============================================<br>";


$largestPriceMovementsStatement =
    $connection
        ->prepare(
            "
                SELECT
                    p.id AS player_id,
                    p.web_name,
                    s.price AS snapshot_price,
                    p.price AS current_price,
                    (
                        p.price
                        -
                        s.price
                    ) AS price_change

                FROM player_gameweek_snapshots s

                INNER JOIN players p
                    ON p.id = s.player_id

                WHERE
                    s.gameweek_id = :gameweek_id
                    AND
                    s.price IS NOT NULL
                    AND
                    p.price IS NOT NULL
                    AND
                    p.price <> s.price

                ORDER BY
                    ABS(
                        p.price
                        -
                        s.price
                    ) DESC,
                    p.id ASC

                LIMIT 10
            "
        );


$largestPriceMovementsStatement
    ->execute(
        [
            'gameweek_id' =>
                $latestSnapshotGameweekId
        ]
    );


$largestPriceMovements =
    $largestPriceMovementsStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


if (
    empty(
        $largestPriceMovements
    )
) {

    echo "No player price changes detected since the latest snapshot.<br>";

} else {

    foreach (
        $largestPriceMovements
        as $row
    ) {

        $change =
            (float) (
                $row[
                    'price_change'
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
        . " — £"
        . number_format(
            (float) (
                $row[
                    'snapshot_price'
                ]
                ?? 0
            ),
            1
        )
        . "m → £"
        . number_format(
            (float) (
                $row[
                    'current_price'
                ]
                ?? 0
            ),
            1
        )
        . "m — "
        . (
            $change > 0
                ? '+'
                : ''
        )
        . number_format(
            $change,
            1
        )
        . "m<br>";
    }
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * LARGEST REAL OWNERSHIP MOVEMENTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Largest Real Ownership Movements<br>";
echo "============================================<br>";


$largestOwnershipMovementsStatement =
    $connection
        ->prepare(
            "
                SELECT
                    p.id AS player_id,
                    p.web_name,
                    s.selected_by_percent
                        AS snapshot_ownership,
                    p.selected_by_percent
                        AS current_ownership,
                    (
                        p.selected_by_percent
                        -
                        s.selected_by_percent
                    ) AS ownership_change

                FROM player_gameweek_snapshots s

                INNER JOIN players p
                    ON p.id = s.player_id

                WHERE
                    s.gameweek_id = :gameweek_id
                    AND
                    s.selected_by_percent IS NOT NULL
                    AND
                    p.selected_by_percent IS NOT NULL
                    AND
                    p.selected_by_percent
                        <>
                        s.selected_by_percent

                ORDER BY
                    ABS(
                        p.selected_by_percent
                        -
                        s.selected_by_percent
                    ) DESC,
                    p.id ASC

                LIMIT 10
            "
        );


$largestOwnershipMovementsStatement
    ->execute(
        [
            'gameweek_id' =>
                $latestSnapshotGameweekId
        ]
    );


$largestOwnershipMovements =
    $largestOwnershipMovementsStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


if (
    empty(
        $largestOwnershipMovements
    )
) {

    echo "No ownership changes detected since the latest snapshot.<br>";

} else {

    foreach (
        $largestOwnershipMovements
        as $row
    ) {

        $change =
            (float) (
                $row[
                    'ownership_change'
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
        . " — "
        . number_format(
            (float) (
                $row[
                    'snapshot_ownership'
                ]
                ?? 0
            ),
            2
        )
        . "% → "
        . number_format(
            (float) (
                $row[
                    'current_ownership'
                ]
                ?? 0
            ),
            2
        )
        . "% — "
        . (
            $change > 0
                ? '+'
                : ''
        )
        . number_format(
            $change,
            2
        )
        . "%<br>";
    }
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * CURRENT-STATE MOVEMENT READINESS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Current-State Movement Readiness<br>";
echo "============================================<br>";


marketCurrentStateCheck(
    'Snapshot-to-current price comparison is structurally available',
    $comparablePriceRows > 0
);


marketCurrentStateCheck(
    'Snapshot-to-current ownership comparison is structurally available',
    $comparableOwnershipRows > 0
);


echo "Price Movement Evidence: "
    . (
        $changedPriceRows > 0
            ? 'Real Changes Detected'
            : 'No Changes Detected'
    )
    . "<br>";


echo "Ownership Movement Evidence: "
    . (
        $changedOwnershipRows > 0
            ? 'Real Changes Detected'
            : 'No Changes Detected'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO I
 * ARCHITECTURE DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Market Architecture Diagnostic<br>";
echo "============================================<br><br>";


echo "Historical Baseline:<br>";

echo "GW"
    . (
        $latestSnapshotGameweek[
            'fpl_gameweek_id'
        ]
        ?? '—'
    )
    . " player snapshot<br><br>";


echo "Current State:<br>";

echo "players table after latest FPL data refresh<br><br>";


echo "Comparable Players: "
    . number_format(
        $matchedPlayers
    )
    . "<br>";


echo "Price Changes Detected: "
    . number_format(
        $changedPriceRows
    )
    . "<br>";


echo "Ownership Changes Detected: "
    . number_format(
        $changedOwnershipRows
    )
    . "<br><br>";


echo "Candidate Architecture:<br>";

echo "Historical snapshots → completed gameweek market states<br>";

echo "Current players table → latest live market state<br>";

echo "Snapshot-to-current delta → current market movement<br>";

echo "Multiple historical snapshots → longer-term market trend<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Market Intelligence Current State Comparison Test Summary<br>";
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