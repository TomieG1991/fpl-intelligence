<?php

declare(strict_types=1);

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Cleanup Eligibility Test<br>";
echo "============================================<br>";


$passed =
    0;


$failed =
    0;


function cleanupEligibilityCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


$db =
    new Database();


$pdo =
    $db->getConnection();


/*
 * ============================================================
 * SCENARIO A
 * HISTORICAL SNAPSHOT FOUNDATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Historical Snapshot Foundation<br>";
echo "============================================<br>";


$totalSnapshotRows =
    (int) $pdo
        ->query(
            '
                SELECT COUNT(*)
                FROM player_gameweek_snapshots
            '
        )
        ->fetchColumn();


cleanupEligibilityCheck(
    'Historical snapshot rows exist',
    $totalSnapshotRows > 0
);


echo "Historical Snapshot Rows: "
    . number_format(
        $totalSnapshotRows
    )
    . "<br>";


/*
 * ============================================================
 * SCENARIO B
 * FIRST AUTHORITATIVE HISTORICAL EVIDENCE
 * ============================================================
 *
 * Determine the first FPL gameweek for which each player has
 * authoritative stored player_fixture_history evidence.
 *
 * This is deliberately based on gameweek identity rather than
 * local database insertion order.
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: First Authoritative Historical Evidence<br>";
echo "============================================<br>";


$firstEvidenceSql =
    '
        SELECT
            pfh.player_id,
            MIN(
                gw.fpl_gameweek_id
            ) AS first_history_gameweek

        FROM player_fixture_history pfh

        INNER JOIN gameweeks gw
            ON gw.id = pfh.gameweek_id

        GROUP BY
            pfh.player_id
    ';


$firstEvidenceRows =
    $pdo
        ->query(
            $firstEvidenceSql
        )
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


cleanupEligibilityCheck(
    'Authoritative player fixture-history evidence exists',
    !empty(
        $firstEvidenceRows
    )
);


echo "Players With Historical Evidence: "
    . number_format(
        count(
            $firstEvidenceRows
        )
    )
    . "<br>";


/*
 * ============================================================
 * SCENARIO C
 * SYNTHETIC RETROSPECTIVE SNAPSHOT IDENTIFICATION
 * ============================================================
 *
 * A historical snapshot is cleanup-eligible only when:
 *
 *     snapshot FPL gameweek
 *         <
 *     player's first authoritative historical FPL gameweek
 *
 * This deliberately does NOT classify a snapshot merely
 * because same-gameweek fixture history is absent.
 *
 * That distinction matters for Blank Gameweeks.
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Synthetic Retrospective Snapshot Identification<br>";
echo "============================================<br>";


$cleanupSql =
    '
        SELECT
            pgs.id AS snapshot_id,
            pgs.player_id,
            pgs.fpl_player_id,
            p.web_name,
            snapshot_gw.fpl_gameweek_id
                AS snapshot_gameweek,
            first_history.first_history_gameweek

        FROM player_gameweek_snapshots pgs

        INNER JOIN gameweeks snapshot_gw
            ON snapshot_gw.id = pgs.gameweek_id

        INNER JOIN players p
            ON p.id = pgs.player_id

        INNER JOIN (
            SELECT
                pfh.player_id,
                MIN(
                    history_gw.fpl_gameweek_id
                ) AS first_history_gameweek

            FROM player_fixture_history pfh

            INNER JOIN gameweeks history_gw
                ON history_gw.id = pfh.gameweek_id

            GROUP BY
                pfh.player_id
        ) first_history
            ON first_history.player_id = pgs.player_id

        WHERE
            snapshot_gw.fpl_gameweek_id
                <
            first_history.first_history_gameweek

        ORDER BY
            snapshot_gw.fpl_gameweek_id ASC,
            pgs.fpl_player_id ASC
    ';


$cleanupRows =
    $pdo
        ->query(
            $cleanupSql
        )
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


$cleanupCount =
    count(
        $cleanupRows
    );


cleanupEligibilityCheck(
    'No historical snapshot predates the player\'s first authoritative evidence',
    $cleanupCount === 0
);


echo "Cleanup-Eligible Snapshot Rows: "
    . number_format(
        $cleanupCount
    )
    . "<br>";


/*
 * ============================================================
 * SCENARIO D
 * CLEAN HISTORICAL STATE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Clean Historical State<br>";
echo "============================================<br>";


$cleanupByGameweek =
    [];


foreach (
    $cleanupRows
    as $row
) {

    $gameweek =
        (int) (
            $row[
                'snapshot_gameweek'
            ]
            ?? 0
        );


    if (
        !isset(
            $cleanupByGameweek[
                $gameweek
            ]
        )
    ) {

        $cleanupByGameweek[
            $gameweek
        ] =
            0;
    }


    $cleanupByGameweek[
        $gameweek
    ]++;
}


cleanupEligibilityCheck(
    'GW1 contains no snapshot predating first authoritative evidence',
    (
        $cleanupByGameweek[
            1
        ]
        ?? 0
    ) === 0
);


cleanupEligibilityCheck(
    'GW2 contains no snapshot predating first authoritative evidence',
    (
        $cleanupByGameweek[
            2
        ]
        ?? 0
    ) === 0
);


echo "GW1 Invalid Snapshot Rows: "
    . number_format(
        $cleanupByGameweek[
            1
        ]
        ?? 0
    )
    . "<br>";


echo "GW2 Invalid Snapshot Rows: "
    . number_format(
        $cleanupByGameweek[
            2
        ]
        ?? 0
    )
    . "<br>";


/*
 * ============================================================
 * SCENARIO E
 * HISTORICAL INTEGRITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Historical Integrity<br>";
echo "============================================<br>";


$invalidHistoricalRows =
    0;


foreach (
    $cleanupRows
    as $row
) {

    $snapshotGameweek =
        (int) (
            $row[
                'snapshot_gameweek'
            ]
            ?? 0
        );


    $firstHistoryGameweek =
        (int) (
            $row[
                'first_history_gameweek'
            ]
            ?? 0
        );


    if (
        $snapshotGameweek > 0
        &&
        $firstHistoryGameweek > 0
        &&
        $snapshotGameweek < $firstHistoryGameweek
    ) {

        $invalidHistoricalRows++;
    }
}


cleanupEligibilityCheck(
    'Historical snapshot integrity contains zero retrospective synthetic rows',
    $invalidHistoricalRows === 0
);


echo "Invalid Historical Snapshot Rows: "
    . number_format(
        $invalidHistoricalRows
    )
    . "<br>";


/*
 * ============================================================
 * SCENARIO F
 * READ-ONLY SAFETY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Read-Only Safety<br>";
echo "============================================<br>";


$totalSnapshotRowsAfter =
    (int) $pdo
        ->query(
            '
                SELECT COUNT(*)
                FROM player_gameweek_snapshots
            '
        )
        ->fetchColumn();


cleanupEligibilityCheck(
    'Eligibility test does not alter historical snapshot rows',
    $totalSnapshotRowsAfter
        ===
    $totalSnapshotRows
);


echo "Historical Snapshot Rows After Test: "
    . number_format(
        $totalSnapshotRowsAfter
    )
    . "<br>";


/*
 * ============================================================
 * DIAGNOSTIC
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Cleanup Eligibility Diagnostic<br>";
echo "============================================<br>";


foreach (
    $cleanupRows
    as $row
) {

    echo "Snapshot ID "
        . (int) $row[
            'snapshot_id'
        ]
        . " | "
        . htmlspecialchars(
            (string) (
                $row[
                    'web_name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . " | FPL Player "
        . (int) $row[
            'fpl_player_id'
        ]
        . " | Snapshot GW"
        . (int) $row[
            'snapshot_gameweek'
        ]
        . " | First Evidence GW"
        . (int) $row[
            'first_history_gameweek'
        ]
        . "<br>";
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Gameweek Snapshot Cleanup Eligibility Test Summary<br>";
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

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}