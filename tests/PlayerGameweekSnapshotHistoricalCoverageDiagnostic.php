<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Historical Coverage Diagnostic<br>";
echo "============================================<br><br>";


$database =
    new Database();


$pdo =
    $database->getConnection();


/*
 * ============================================================
 * HISTORICAL SNAPSHOT GAMEWEEKS
 * ============================================================
 */

$statement =
    $pdo->query(
        "
        SELECT DISTINCT
            g.id,
            g.fpl_gameweek_id,
            g.name,
            g.finished,
            g.data_checked
        FROM
            gameweeks g
        INNER JOIN
            player_gameweek_snapshots pgs
                ON pgs.gameweek_id = g.id
        ORDER BY
            g.fpl_gameweek_id ASC
        "
    );


$gameweeks =
    $statement->fetchAll(
        PDO::FETCH_ASSOC
    );


echo "Snapshot Gameweeks Found: "
    . count(
        $gameweeks
    )
    . "<br><br>";


/*
 * ============================================================
 * PREPARE COVERAGE QUERIES
 * ============================================================
 */

$totalStatement =
    $pdo->prepare(
        "
        SELECT
            COUNT(*)
        FROM
            player_gameweek_snapshots
        WHERE
            gameweek_id = :gameweek_id
        "
    );


$backedStatement =
    $pdo->prepare(
        "
        SELECT
            COUNT(*)
        FROM
            player_gameweek_snapshots pgs
        WHERE
            pgs.gameweek_id = :gameweek_id
            AND EXISTS (
                SELECT
                    1
                FROM
                    player_fixture_history pfh
                WHERE
                    pfh.gameweek_id = pgs.gameweek_id
                    AND pfh.player_id = pgs.player_id
            )
        "
    );


$unsupportedStatement =
    $pdo->prepare(
        "
        SELECT
            COUNT(*)
        FROM
            player_gameweek_snapshots pgs
        WHERE
            pgs.gameweek_id = :gameweek_id
            AND NOT EXISTS (
                SELECT
                    1
                FROM
                    player_fixture_history pfh
                WHERE
                    pfh.gameweek_id = pgs.gameweek_id
                    AND pfh.player_id = pgs.player_id
            )
        "
    );


$nullSelectedStatement =
    $pdo->prepare(
        "
        SELECT
            COUNT(*)
        FROM
            player_gameweek_snapshots
        WHERE
            gameweek_id = :gameweek_id
            AND selected IS NULL
        "
    );


$unsupportedDetailStatement =
    $pdo->prepare(
        "
        SELECT
            pgs.id AS snapshot_id,
            pgs.player_id,
            pgs.fpl_player_id,
            p.first_name,
            p.second_name,
            pgs.price,
            pgs.selected
        FROM
            player_gameweek_snapshots pgs
        INNER JOIN
            players p
                ON p.id = pgs.player_id
        WHERE
            pgs.gameweek_id = :gameweek_id
            AND NOT EXISTS (
                SELECT
                    1
                FROM
                    player_fixture_history pfh
                WHERE
                    pfh.gameweek_id = pgs.gameweek_id
                    AND pfh.player_id = pgs.player_id
            )
        ORDER BY
            pgs.player_id ASC
        "
    );


/*
 * ============================================================
 * ANALYSE EACH HISTORICAL GAMEWEEK
 * ============================================================
 */

$totalSnapshots =
    0;


$totalBacked =
    0;


$totalUnsupported =
    0;


$totalNullSelected =
    0;


foreach (
    $gameweeks
    as $gameweek
) {

    $gameweekId =
        (int) (
            $gameweek[
                'id'
            ]
            ?? 0
        );


    $fplGameweekId =
        (int) (
            $gameweek[
                'fpl_gameweek_id'
            ]
            ?? 0
        );


    $totalStatement->execute(
        [
            ':gameweek_id' =>
                $gameweekId
        ]
    );


    $snapshotCount =
        (int) $totalStatement
            ->fetchColumn();


    $backedStatement->execute(
        [
            ':gameweek_id' =>
                $gameweekId
        ]
    );


    $backedCount =
        (int) $backedStatement
            ->fetchColumn();


    $unsupportedStatement->execute(
        [
            ':gameweek_id' =>
                $gameweekId
        ]
    );


    $unsupportedCount =
        (int) $unsupportedStatement
            ->fetchColumn();


    $nullSelectedStatement->execute(
        [
            ':gameweek_id' =>
                $gameweekId
        ]
    );


    $nullSelectedCount =
        (int) $nullSelectedStatement
            ->fetchColumn();


    $totalSnapshots +=
        $snapshotCount;


    $totalBacked +=
        $backedCount;


    $totalUnsupported +=
        $unsupportedCount;


    $totalNullSelected +=
        $nullSelectedCount;


    echo "============================================<br>";

    echo "GW"
        . $fplGameweekId
        . " Historical Coverage<br>";

    echo "============================================<br>";


    echo "Local Gameweek ID: "
        . $gameweekId
        . "<br>";


    echo "Finished: "
        . (
            !empty(
                $gameweek[
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
                $gameweek[
                    'data_checked'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        )
        . "<br>";


    echo "Total Snapshots: "
        . number_format(
            $snapshotCount
        )
        . "<br>";


    echo "Backed By Fixture History: "
        . number_format(
            $backedCount
        )
        . "<br>";


    echo "Without Fixture History: "
        . number_format(
            $unsupportedCount
        )
        . "<br>";


    echo "Selected NULL: "
        . number_format(
            $nullSelectedCount
        )
        . "<br><br>";


    /*
     * Show the exact unsupported rows.
     */

    if (
        $unsupportedCount > 0
    ) {

        $unsupportedDetailStatement->execute(
            [
                ':gameweek_id' =>
                    $gameweekId
            ]
        );


        $unsupportedRows =
            $unsupportedDetailStatement
                ->fetchAll(
                    PDO::FETCH_ASSOC
                );


        echo "Unsupported Snapshot Detail:<br>";


        foreach (
            $unsupportedRows
            as $row
        ) {

            $playerName =
                trim(
                    (
                        $row[
                            'first_name'
                        ]
                        ?? ''
                    )
                    . ' '
                    . (
                        $row[
                            'second_name'
                        ]
                        ?? ''
                    )
                );


            echo "Snapshot ID: "
                . (int) (
                    $row[
                        'snapshot_id'
                    ]
                    ?? 0
                );


            echo " | Player ID: "
                . (int) (
                    $row[
                        'player_id'
                    ]
                    ?? 0
                );


            echo " | FPL ID: "
                . (int) (
                    $row[
                        'fpl_player_id'
                    ]
                    ?? 0
                );


            echo " | Player: "
                . htmlspecialchars(
                    $playerName,
                    ENT_QUOTES,
                    'UTF-8'
                );


            echo " | Price: "
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
                        : 'NULL'
                );


            echo " | Selected: "
                . (
                    $row[
                        'selected'
                    ]
                    !==
                    null
                        ? number_format(
                            (int) $row[
                                'selected'
                            ]
                        )
                        : 'NULL'
                );


            echo "<br>";
        }


        echo "<br>";
    }
}


/*
 * ============================================================
 * OVERALL SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Historical Coverage Summary<br>";
echo "============================================<br>";


echo "Snapshot Gameweeks: "
    . count(
        $gameweeks
    )
    . "<br>";


echo "Total Historical Snapshots: "
    . number_format(
        $totalSnapshots
    )
    . "<br>";


echo "Backed By Fixture History: "
    . number_format(
        $totalBacked
    )
    . "<br>";


echo "Without Fixture History: "
    . number_format(
        $totalUnsupported
    )
    . "<br>";


echo "Selected NULL: "
    . number_format(
        $totalNullSelected
    )
    . "<br><br>";


/*
 * ============================================================
 * CONSISTENCY CHECK
 * ============================================================
 */

echo "============================================<br>";
echo "Consistency<br>";
echo "============================================<br>";


echo "Backed + Unsupported: "
    . number_format(
        $totalBacked
        +
        $totalUnsupported
    )
    . "<br>";


echo "Total Snapshots: "
    . number_format(
        $totalSnapshots
    )
    . "<br>";


echo "Coverage Accounting: "
    . (
        (
            $totalBacked
            +
            $totalUnsupported
        )
        ===
        $totalSnapshots
            ? 'MATCH'
            : 'MISMATCH'
    )
    . "<br><br>";


/*
 * ============================================================
 * SAFETY
 * ============================================================
 */

echo "============================================<br>";
echo "Safety<br>";
echo "============================================<br>";

echo "READ ONLY: No INSERT, UPDATE or DELETE statements were executed.<br>";