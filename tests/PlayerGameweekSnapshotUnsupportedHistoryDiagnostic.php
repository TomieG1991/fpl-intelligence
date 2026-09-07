<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Unsupported Historical Snapshot Diagnostic<br>";
echo "============================================<br><br>";


$database =
    new Database();


$pdo =
    $database->getConnection();


/*
 * ============================================================
 * FIND SNAPSHOTS WITHOUT SAME-GAMEWEEK HISTORY
 * ============================================================
 */

$statement =
    $pdo->query(
        "
        SELECT
            pgs.id AS snapshot_id,
            pgs.gameweek_id AS snapshot_gameweek_id,
            g.fpl_gameweek_id AS snapshot_fpl_gameweek_id,
            pgs.player_id,
            pgs.fpl_player_id,
            p.first_name,
            p.second_name,
            pgs.price,
            pgs.selected
        FROM
            player_gameweek_snapshots pgs
        INNER JOIN
            gameweeks g
                ON g.id = pgs.gameweek_id
        INNER JOIN
            players p
                ON p.id = pgs.player_id
        WHERE
            NOT EXISTS (
                SELECT
                    1
                FROM
                    player_fixture_history pfh
                WHERE
                    pfh.gameweek_id = pgs.gameweek_id
                    AND pfh.player_id = pgs.player_id
            )
        ORDER BY
            g.fpl_gameweek_id ASC,
            pgs.player_id ASC
        "
    );


$unsupportedSnapshots =
    $statement->fetchAll(
        PDO::FETCH_ASSOC
    );


echo "Unsupported Snapshot Rows: "
    . count(
        $unsupportedSnapshots
    )
    . "<br><br>";


/*
 * ============================================================
 * PLAYER HISTORY RANGE LOOKUP
 * ============================================================
 */

$historyRangeStatement =
    $pdo->prepare(
        "
        SELECT
            COUNT(*) AS history_rows,
            MIN(g.fpl_gameweek_id) AS first_fpl_gameweek,
            MAX(g.fpl_gameweek_id) AS last_fpl_gameweek
        FROM
            player_fixture_history pfh
        INNER JOIN
            gameweeks g
                ON g.id = pfh.gameweek_id
        WHERE
            pfh.player_id = :player_id
        "
    );


/*
 * ============================================================
 * ANALYSE UNSUPPORTED ROWS
 * ============================================================
 */

$firstHistoryLater =
    0;


$firstHistorySameOrEarlier =
    0;


$noHistoryAnywhere =
    0;


foreach (
    $unsupportedSnapshots
    as $snapshot
) {

    $playerId =
        (int) (
            $snapshot[
                'player_id'
            ]
            ?? 0
        );


    $snapshotGameweek =
        (int) (
            $snapshot[
                'snapshot_fpl_gameweek_id'
            ]
            ?? 0
        );


    $playerName =
        trim(
            (
                $snapshot[
                    'first_name'
                ]
                ?? ''
            )
            . ' '
            . (
                $snapshot[
                    'second_name'
                ]
                ?? ''
            )
        );


    $historyRangeStatement->execute(
        [
            ':player_id' =>
                $playerId
        ]
    );


    $historyRange =
        $historyRangeStatement->fetch(
            PDO::FETCH_ASSOC
        );


    $historyRows =
        (int) (
            $historyRange[
                'history_rows'
            ]
            ?? 0
        );


    $firstHistoryGameweek =
        isset(
            $historyRange[
                'first_fpl_gameweek'
            ]
        )
        &&
        $historyRange[
            'first_fpl_gameweek'
        ]
        !==
        null
            ? (int) $historyRange[
                'first_fpl_gameweek'
            ]
            : null;


    $lastHistoryGameweek =
        isset(
            $historyRange[
                'last_fpl_gameweek'
            ]
        )
        &&
        $historyRange[
            'last_fpl_gameweek'
        ]
        !==
        null
            ? (int) $historyRange[
                'last_fpl_gameweek'
            ]
            : null;


    if (
        $historyRows === 0
    ) {

        $classification =
            'NO HISTORY ANYWHERE';


        $noHistoryAnywhere++;

    } elseif (
        $firstHistoryGameweek !== null
        &&
        $firstHistoryGameweek
        >
        $snapshotGameweek
    ) {

        $classification =
            'FIRST HISTORY IS LATER';


        $firstHistoryLater++;

    } else {

        $classification =
            'HISTORY EXISTS SAME/EARLIER';


        $firstHistorySameOrEarlier++;
    }


    echo "Snapshot ID: "
        . (int) (
            $snapshot[
                'snapshot_id'
            ]
            ?? 0
        );


    echo " | Snapshot GW: "
        . $snapshotGameweek;


    echo " | Player ID: "
        . $playerId;


    echo " | FPL ID: "
        . (int) (
            $snapshot[
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


    echo " | History Rows: "
        . $historyRows;


    echo " | First History GW: "
        . (
            $firstHistoryGameweek
            !==
            null
                ? $firstHistoryGameweek
                : 'NONE'
        );


    echo " | Last History GW: "
        . (
            $lastHistoryGameweek
            !==
            null
                ? $lastHistoryGameweek
                : 'NONE'
        );


    echo " | Classification: "
        . $classification;


    echo "<br>";
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";

echo "============================================<br>";
echo "Classification Summary<br>";
echo "============================================<br>";


echo "Unsupported Snapshot Rows: "
    . count(
        $unsupportedSnapshots
    )
    . "<br>";


echo "First Historical Evidence Is Later: "
    . $firstHistoryLater
    . "<br>";


echo "No Historical Evidence Anywhere: "
    . $noHistoryAnywhere
    . "<br>";


echo "Historical Evidence Same Or Earlier: "
    . $firstHistorySameOrEarlier
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