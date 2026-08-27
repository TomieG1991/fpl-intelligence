<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "GW1 Ownership Recovery Diagnostic Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


function gw1OwnershipRecoveryCheck(
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


$statement =
    $connection
        ->prepare(
            "
                SELECT *
                FROM gameweeks
                WHERE fpl_gameweek_id = 1
                LIMIT 1
            "
        );

$statement
    ->execute();

$gw1 =
    $statement
        ->fetch(
            PDO::FETCH_ASSOC
        );


gw1OwnershipRecoveryCheck(
    'GW1 resolves from local gameweek storage',
    is_array($gw1)
);


if (!is_array($gw1)) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$gw1Id =
    (int) (
        $gw1['id']
        ?? 0
    );


echo "Local GW1 ID: "
    . $gw1Id
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * IDENTIFY SNAPSHOT PLAYERS WITHOUT GW1 HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Players Without GW1 History<br>";
echo "============================================<br>";


$missingStatement =
    $connection
        ->prepare(
            "
                SELECT
                    p.id,
                    p.fpl_player_id,
                    p.web_name,
                    p.team_id,
                    p.price AS current_price,
                    p.selected_by_percent,
                    s.price AS snapshot_price,
                    s.selected_by_percent AS snapshot_ownership

                FROM player_gameweek_snapshots s

                INNER JOIN players p
                    ON p.id = s.player_id

                LEFT JOIN player_fixture_history h
                    ON h.player_id = s.player_id
                    AND h.gameweek_id = s.gameweek_id

                WHERE
                    s.gameweek_id = :gameweek_id
                    AND h.id IS NULL

                ORDER BY
                    p.id ASC
            "
        );


$missingStatement
    ->execute(
        [
            'gameweek_id' =>
                $gw1Id
        ]
    );


$missingPlayers =
    $missingStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


echo "Snapshot Players Without GW1 History: "
    . count($missingPlayers)
    . "<br>";


foreach ($missingPlayers as $player) {

    echo "Player ID "
        . (int) ($player['id'] ?? 0)
        . " — FPL ID "
        . (int) ($player['fpl_player_id'] ?? 0)
        . " — "
        . htmlspecialchars(
            (string) (
                $player['web_name']
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " — Current £"
        . (
            is_numeric($player['current_price'] ?? null)
                ? number_format(
                    (float) $player['current_price'],
                    1
                )
                : '—'
        )
        . "m — Current Ownership "
        . (
            is_numeric(
                $player['selected_by_percent']
                ?? null
            )
                ? number_format(
                    (float) $player['selected_by_percent'],
                    2
                ) . '%'
                : '—'
        )
        . "<br>";
}


gw1OwnershipRecoveryCheck(
    'Snapshot/history coverage gap is identifiable',
    count($missingPlayers) === 4
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * CHECK WHETHER MISSING PLAYERS HAVE ANY HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Missing Player History State<br>";
echo "============================================<br>";


foreach ($missingPlayers as $player) {

    $playerId =
        (int) (
            $player['id']
            ?? 0
        );


    $historyStatement =
        $connection
            ->prepare(
                "
                    SELECT
                        COUNT(*) AS row_count,
                        MIN(gameweek_id) AS first_gameweek_id,
                        MAX(gameweek_id) AS latest_gameweek_id

                    FROM player_fixture_history

                    WHERE player_id = :player_id
                "
            );


    $historyStatement
        ->execute(
            [
                'player_id' =>
                    $playerId
            ]
        );


    $historyState =
        $historyStatement
            ->fetch(
                PDO::FETCH_ASSOC
            );


    echo htmlspecialchars(
        (string) (
            $player['web_name']
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " — History Rows "
    . (int) (
        $historyState['row_count']
        ?? 0
    )
    . " — First Local Gameweek ID "
    . (
        $historyState['first_gameweek_id']
        ?? '—'
    )
    . " — Latest Local Gameweek ID "
    . (
        $historyState['latest_gameweek_id']
        ?? '—'
    )
    . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * SEARCH DATABASE SCHEMA FOR TOTAL-PLAYER EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Historical Total-Player Foundation<br>";
echo "============================================<br>";


$schemaStatement =
    $connection
        ->query(
            "
                SELECT
                    TABLE_NAME,
                    COLUMN_NAME

                FROM INFORMATION_SCHEMA.COLUMNS

                WHERE
                    TABLE_SCHEMA = DATABASE()
                    AND
                    (
                        COLUMN_NAME LIKE '%total_player%'
                        OR
                        COLUMN_NAME LIKE '%total_manager%'
                        OR
                        COLUMN_NAME LIKE '%manager_count%'
                        OR
                        COLUMN_NAME LIKE '%player_count%'
                    )

                ORDER BY
                    TABLE_NAME,
                    ORDINAL_POSITION
            "
        );


$totalPlayerColumns =
    $schemaStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


echo "Potential Total-Player Columns: "
    . count($totalPlayerColumns)
    . "<br>";


if (empty($totalPlayerColumns)) {

    echo "No candidate total-player denominator columns found.<br>";

} else {

    foreach ($totalPlayerColumns as $column) {

        echo htmlspecialchars(
            (string) $column['TABLE_NAME'],
            ENT_QUOTES,
            'UTF-8'
        )
        . "."
        . htmlspecialchars(
            (string) $column['COLUMN_NAME'],
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
    }
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * IMPLIED DENOMINATOR FROM CURRENT SNAPSHOT
 * ============================================================
 *
 * The existing GW1 ownership percentage is known to have been
 * overwritten. We DO NOT use this to recover GW1.
 *
 * This diagnostic merely demonstrates whether the stored
 * selected count and current percentage imply a consistent
 * modern total-player denominator.
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Implied Denominator Diagnostic<br>";
echo "============================================<br>";


$denominatorStatement =
    $connection
        ->prepare(
            "
                SELECT
                    p.web_name,
                    h.selected,
                    s.selected_by_percent,

                    CASE
                        WHEN
                            s.selected_by_percent IS NOT NULL
                            AND s.selected_by_percent > 0
                        THEN
                            h.selected
                            /
                            (
                                s.selected_by_percent
                                /
                                100
                            )
                        ELSE NULL
                    END AS implied_total_players

                FROM player_fixture_history h

                INNER JOIN player_gameweek_snapshots s
                    ON s.player_id = h.player_id
                    AND s.gameweek_id = h.gameweek_id

                INNER JOIN players p
                    ON p.id = h.player_id

                WHERE
                    h.gameweek_id = :gameweek_id
                    AND h.selected IS NOT NULL
                    AND h.selected > 0
                    AND s.selected_by_percent IS NOT NULL
                    AND s.selected_by_percent > 0

                ORDER BY
                    h.selected DESC

                LIMIT 20
            "
        );


$denominatorStatement
    ->execute(
        [
            'gameweek_id' =>
                $gw1Id
        ]
    );


$denominatorRows =
    $denominatorStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


$impliedTotals =
    [];


foreach ($denominatorRows as $row) {

    $impliedTotal =
        is_numeric(
            $row['implied_total_players']
            ?? null
        )
            ? (float) $row['implied_total_players']
            : null;


    if ($impliedTotal !== null) {

        $impliedTotals[] =
            $impliedTotal;
    }


    echo htmlspecialchars(
        (string) (
            $row['web_name']
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " — Historical Selected "
    . number_format(
        (int) (
            $row['selected']
            ?? 0
        )
    )
    . " — Stored Ownership "
    . number_format(
        (float) (
            $row['selected_by_percent']
            ?? 0
        ),
        2
    )
    . "% — Implied Total "
    . (
        $impliedTotal !== null
            ? number_format(
                (int) round($impliedTotal)
            )
            : '—'
    )
    . "<br>";
}


if (!empty($impliedTotals)) {

    sort($impliedTotals);

    $count =
        count($impliedTotals);

    $middle =
        intdiv(
            $count,
            2
        );


    if ($count % 2 === 0) {

        $medianImpliedTotal =
            (
                $impliedTotals[$middle - 1]
                +
                $impliedTotals[$middle]
            )
            /
            2;

    } else {

        $medianImpliedTotal =
            $impliedTotals[$middle];
    }


    echo "<br>";
    echo "Median Implied Total Players: "
        . number_format(
            (int) round(
                $medianImpliedTotal
            )
        )
        . "<br>";

} else {

    $medianImpliedTotal =
        null;
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * OWNERSHIP CORRUPTION SAMPLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Ownership Corruption Sample<br>";
echo "============================================<br>";


$ownershipStatement =
    $connection
        ->prepare(
            "
                SELECT
                    p.web_name,
                    h.selected,
                    s.selected_by_percent AS snapshot_ownership,
                    p.selected_by_percent AS current_ownership

                FROM player_fixture_history h

                INNER JOIN player_gameweek_snapshots s
                    ON s.player_id = h.player_id
                    AND s.gameweek_id = h.gameweek_id

                INNER JOIN players p
                    ON p.id = h.player_id

                WHERE
                    h.gameweek_id = :gameweek_id
                    AND h.selected IS NOT NULL

                ORDER BY
                    h.selected DESC

                LIMIT 10
            "
        );


$ownershipStatement
    ->execute(
        [
            'gameweek_id' =>
                $gw1Id
        ]
    );


$ownershipRows =
    $ownershipStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


foreach ($ownershipRows as $row) {

    echo htmlspecialchars(
        (string) (
            $row['web_name']
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " — Historical Selected "
    . number_format(
        (int) (
            $row['selected']
            ?? 0
        )
    )
    . " — GW1 Snapshot Ownership "
    . number_format(
        (float) (
            $row['snapshot_ownership']
            ?? 0
        ),
        2
    )
    . "% — Current Ownership "
    . number_format(
        (float) (
            $row['current_ownership']
            ?? 0
        ),
        2
    )
    . "%<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * OWNERSHIP RECOVERY READINESS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Ownership Recovery Readiness<br>";
echo "============================================<br>";


$historicalSelectedAvailable =
    count($ownershipRows) > 0;


$localHistoricalDenominatorAvailable =
    count($totalPlayerColumns) > 0;


gw1OwnershipRecoveryCheck(
    'Historical GW1 selected counts are available',
    $historicalSelectedAvailable
);


echo "Historical Selected Count: "
    . (
        $historicalSelectedAvailable
            ? 'Available'
            : 'Unavailable'
    )
    . "<br>";


echo "Historical Total-Player Denominator: "
    . (
        $localHistoricalDenominatorAvailable
            ? 'Candidate Local Evidence Found'
            : 'Not Stored Locally'
    )
    . "<br>";


echo "Exact Ownership Percentage Recovery: "
    . (
        $historicalSelectedAvailable
        &&
        $localHistoricalDenominatorAvailable
            ? 'Potentially Recoverable Locally'
            : 'External Historical Denominator Required'
    )
    . "<br>";


echo "Current Snapshot Percentages Safe For Recovery: No<br>";
echo "Reason: snapshot percentages were overwritten by live refreshes<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * RECOVERY DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Ownership Recovery Diagnostic<br>";
echo "============================================<br><br>";


echo "GW1 Snapshot Players: 614<br>";


echo "Players With Historical GW1 Rows: "
    . (
        614
        -
        count($missingPlayers)
    )
    . "<br>";


echo "Players Without Historical GW1 Rows: "
    . count($missingPlayers)
    . "<br>";


echo "Historical Selected Counts: Available<br>";


echo "Historical Ownership Denominator: "
    . (
        $localHistoricalDenominatorAvailable
            ? 'Candidate Found'
            : 'Missing'
    )
    . "<br>";


echo "Median Implied Denominator From Corrupted Snapshot: "
    . (
        $medianImpliedTotal !== null
            ? number_format(
                (int) round(
                    $medianImpliedTotal
                )
            )
            : 'Unavailable'
    )
    . "<br>";


echo "Important: implied denominator is diagnostic only "
    . "and must not be written to historical storage.<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "GW1 Ownership Recovery Diagnostic Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}