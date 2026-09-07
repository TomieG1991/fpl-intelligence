<?php

declare(strict_types=1);

require_once __DIR__
    . '/../../classes/autoload.php';


echo "============================================<br>";
echo "Synthetic Player Gameweek Snapshot Cleanup<br>";
echo "============================================<br>";


$db =
    new Database();


$pdo =
    $db->getConnection();


/*
 * ============================================================
 * IDENTIFY SYNTHETIC RETROSPECTIVE SNAPSHOTS
 * ============================================================
 *
 * Historical snapshots created by the retired retrospective
 * capture architecture may contain players whose snapshot
 * gameweek predates their first authoritative stored FPL
 * history evidence.
 *
 * Cleanup eligibility is deliberately:
 *
 *     snapshot FPL gameweek
 *         <
 *     player's first authoritative historical FPL gameweek
 *
 * Absence of same-gameweek fixture history alone is NOT used,
 * because that would not be safe for Blank Gameweeks.
 */


$countSql =
    '
        SELECT
            COUNT(*)

        FROM player_gameweek_snapshots pgs

        INNER JOIN gameweeks snapshot_gw
            ON snapshot_gw.id = pgs.gameweek_id

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
    ';


$eligibleBefore =
    (int) $pdo
        ->query(
            $countSql
        )
        ->fetchColumn();


echo "Cleanup-eligible rows before: "
    . number_format(
        $eligibleBefore
    )
    . "<br>";


/*
 * ============================================================
 * SAFETY CONTRACT
 * ============================================================
 *
 * This migration repairs the 32 rows established by the
 * historical coverage diagnostics and transactional cleanup
 * regression.
 *
 * Refuse to mutate the database if the evidence set is not
 * exactly the expected set.
 */


if (
    $eligibleBefore !== 32
) {

    echo "<br>";
    echo "Cleanup aborted.<br>";
    echo "Expected exactly 32 cleanup-eligible rows.<br>";
    echo "No database changes were made.<br><br>";
    echo "RESULT: CLEANUP ABORTED ❌<br>";

    exit;
}


/*
 * ============================================================
 * PERMANENT CLEANUP
 * ============================================================
 */


$pdo->beginTransaction();


try {

    $deleteStatement =
        $pdo->prepare(
            '
                DELETE pgs

                FROM player_gameweek_snapshots pgs

                INNER JOIN gameweeks snapshot_gw
                    ON snapshot_gw.id = pgs.gameweek_id

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
            '
        );


    $deleteStatement->execute();


    $deletedRows =
        $deleteStatement->rowCount();


    if (
        $deletedRows !== 32
    ) {

        throw new RuntimeException(
            'Cleanup deleted an unexpected number of rows: '
            . $deletedRows
        );
    }


    $eligibleAfter =
        (int) $pdo
            ->query(
                $countSql
            )
            ->fetchColumn();


    if (
        $eligibleAfter !== 0
    ) {

        throw new RuntimeException(
            'Synthetic retrospective snapshots remain after cleanup.'
        );
    }


    $remainingRows =
        (int) $pdo
            ->query(
                '
                    SELECT COUNT(*)
                    FROM player_gameweek_snapshots
                '
            )
            ->fetchColumn();


    if (
        $remainingRows !== 1236
    ) {

        throw new RuntimeException(
            'Unexpected historical snapshot total after cleanup: '
            . $remainingRows
        );
    }


    $pdo->commit();


    echo "Rows deleted: "
        . number_format(
            $deletedRows
        )
        . "<br>";


    echo "Cleanup-eligible rows after: "
        . number_format(
            $eligibleAfter
        )
        . "<br>";


    echo "Historical snapshot rows after: "
        . number_format(
            $remainingRows
        )
        . "<br><br>";


    echo "RESULT: CLEANUP COMPLETE ✅<br>";


} catch (
    Throwable $exception
) {

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    echo "<br>";
    echo "Cleanup failed.<br>";


    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );


    echo "<br>";
    echo "Database changes rolled back.<br><br>";
    echo "RESULT: CLEANUP FAILED ❌<br>";
}