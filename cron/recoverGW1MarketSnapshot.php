<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "GW1 Market Snapshot Recovery<br>";
echo "============================================<br><br>";


/*
 * ============================================================
 * RECOVERY MODE
 * ============================================================
 *
 * Default behaviour is DRY RUN.
 *
 * Browser examples:
 *
 * Dry run:
 * recoverGW1MarketSnapshot.php
 *
 * Apply:
 * recoverGW1MarketSnapshot.php?apply=1
 *
 * No database writes occur unless apply=1.
 */

$apply =
    isset(
        $_GET[
            'apply'
        ]
    )
    &&
    (
        (string) $_GET[
            'apply'
        ]
    )
    ===
    '1';


echo "Mode: "
    . (
        $apply
            ? 'APPLY'
            : 'DRY RUN'
    )
    . "<br><br>";


try {

    /*
     * ========================================================
     * DATABASE
     * ========================================================
     */

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    echo "Database connection successful<br><br>";


    /*
     * ========================================================
     * RESOLVE GW1
     * ========================================================
     */

    $gameweekStatement =
        $db->prepare(
            "
                SELECT
                    *
                FROM
                    gameweeks
                WHERE
                    fpl_gameweek_id = :fpl_gameweek_id
                LIMIT 1
            "
        );


    $gameweekStatement
        ->execute(
            [
                ':fpl_gameweek_id' =>
                    1
            ]
        );


    $gameweek =
        $gameweekStatement
            ->fetch(
                PDO::FETCH_ASSOC
            );


    if (
        !is_array(
            $gameweek
        )
    ) {

        throw new RuntimeException(
            'GW1 could not be resolved from local gameweek storage'
        );
    }


    $gameweekId =
        (int) (
            $gameweek[
                'id'
            ]
            ?? 0
        );


    if (
        $gameweekId <= 0
    ) {

        throw new RuntimeException(
            'GW1 does not contain a valid local gameweek ID'
        );
    }


    echo "Gameweek: GW1<br>";
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
        . "<br><br>";


    /*
     * ========================================================
     * SAFETY CHECK
     * ========================================================
     */

    if (
        empty(
            $gameweek[
                'finished'
            ]
            ?? false
        )
        ||
        empty(
            $gameweek[
                'data_checked'
            ]
            ?? false
        )
    ) {

        throw new RuntimeException(
            'GW1 is not both finished and data checked'
        );
    }


    /*
     * ========================================================
     * LOAD GW1 SNAPSHOTS
     * ========================================================
     */

    $snapshotStatement =
        $db->prepare(
            "
                SELECT
                    s.*,
                    p.web_name
                FROM
                    player_gameweek_snapshots s

                INNER JOIN
                    players p
                        ON p.id = s.player_id

                WHERE
                    s.gameweek_id = :gameweek_id

                ORDER BY
                    s.player_id ASC
            "
        );


    $snapshotStatement
        ->execute(
            [
                ':gameweek_id' =>
                    $gameweekId
            ]
        );


    $snapshots =
        $snapshotStatement
            ->fetchAll(
                PDO::FETCH_ASSOC
            );


    echo "GW1 Snapshot Rows: "
        . number_format(
            count(
                $snapshots
            )
        )
        . "<br>";


    if (
        empty(
            $snapshots
        )
    ) {

        throw new RuntimeException(
            'No GW1 snapshot rows exist'
        );
    }


    /*
     * ========================================================
     * LOAD TRUSTWORTHY GW1 HISTORY
     * ========================================================
     */

    $historyRepository =
        new PlayerFixtureHistoryRepository(
            $db
        );


    $historyRows =
        $historyRepository
            ->getByGameweekId(
                $gameweekId
            );


    echo "GW1 Fixture-History Rows: "
        . number_format(
            count(
                $historyRows
            )
        )
        . "<br><br>";


    if (
        empty(
            $historyRows
        )
    ) {

        throw new RuntimeException(
            'No trustworthy GW1 fixture-history rows are available'
        );
    }


    /*
     * ========================================================
     * BUILD ONE HISTORY RECORD PER PLAYER
     * ========================================================
     *
     * GW1 is a normal single-fixture gameweek.
     *
     * We still guard against duplicate player rows rather than
     * silently choosing between conflicting historical records.
     */

    $historyByPlayerId =
        [];


    $duplicateHistoryPlayers =
        [];


    foreach (
        $historyRows
        as $historyRow
    ) {

        $playerId =
            (int) (
                $historyRow[
                    'player_id'
                ]
                ?? 0
            );


        if (
            $playerId <= 0
        ) {

            continue;
        }


        if (
            isset(
                $historyByPlayerId[
                    $playerId
                ]
            )
        ) {

            $duplicateHistoryPlayers[
                $playerId
            ] =
                true;

            continue;
        }


        $historyByPlayerId[
            $playerId
        ] =
            $historyRow;
    }


    if (
        !empty(
            $duplicateHistoryPlayers
        )
    ) {

        throw new RuntimeException(
            'Duplicate GW1 fixture-history rows were detected for one or more players'
        );
    }


    /*
     * ========================================================
     * COMPARE SNAPSHOT AGAINST HISTORICAL EVIDENCE
     * ========================================================
     */

    $recoverable =
        [];


    $withoutHistory =
        [];


    $unchanged =
        [];


    $priceChanges =
        0;


    $selectedChanges =
        0;


    foreach (
        $snapshots
        as $snapshot
    ) {

        $playerId =
            (int) (
                $snapshot[
                    'player_id'
                ]
                ?? 0
            );


        $playerName =
            (string) (
                $snapshot[
                    'web_name'
                ]
                ?? (
                    'Player '
                    . $playerId
                )
            );


        $historyRow =
            $historyByPlayerId[
                $playerId
            ]
            ?? null;


        if (
            !is_array(
                $historyRow
            )
        ) {

            $withoutHistory[] = [

                'player_id' =>
                    $playerId,

                'name' =>
                    $playerName,

                'snapshot_price' =>
                    $snapshot[
                        'price'
                    ]
                    ?? null,

                'snapshot_selected' =>
                    $snapshot[
                        'selected'
                    ]
                    ?? null
            ];


            continue;
        }


        $historicalPrice =
            is_numeric(
                $historyRow[
                    'price'
                ]
                ?? null
            )
                ? (float) $historyRow[
                    'price'
                ]
                : null;


        $historicalSelected =
            is_numeric(
                $historyRow[
                    'selected'
                ]
                ?? null
            )
                ? (int) $historyRow[
                    'selected'
                ]
                : null;


        /*
         * We require both fields before treating a row as
         * recoverable.
         */
        if (
            $historicalPrice === null
            ||
            $historicalSelected === null
        ) {

            $withoutHistory[] = [

                'player_id' =>
                    $playerId,

                'name' =>
                    $playerName,

                'snapshot_price' =>
                    $snapshot[
                        'price'
                    ]
                    ?? null,

                'snapshot_selected' =>
                    $snapshot[
                        'selected'
                    ]
                    ?? null
            ];


            continue;
        }


        $snapshotPrice =
            is_numeric(
                $snapshot[
                    'price'
                ]
                ?? null
            )
                ? (float) $snapshot[
                    'price'
                ]
                : null;


        $snapshotSelected =
            is_numeric(
                $snapshot[
                    'selected'
                ]
                ?? null
            )
                ? (int) $snapshot[
                    'selected'
                ]
                : null;


        $priceDifferent =
            $snapshotPrice === null
            ||
            abs(
                $snapshotPrice
                -
                $historicalPrice
            )
            >
            0.0001;


        $selectedDifferent =
            $snapshotSelected === null
            ||
            $snapshotSelected
            !==
            $historicalSelected;


        if (
            $priceDifferent
        ) {

            $priceChanges++;
        }


        if (
            $selectedDifferent
        ) {

            $selectedChanges++;
        }


        if (
            !$priceDifferent
            &&
            !$selectedDifferent
        ) {

            $unchanged[] =
                $playerId;

            continue;
        }


        $recoverable[] = [

            'snapshot_id' =>
                (int) (
                    $snapshot[
                        'id'
                    ]
                    ?? 0
                ),

            'player_id' =>
                $playerId,

            'name' =>
                $playerName,

            'old_price' =>
                $snapshotPrice,

            'new_price' =>
                $historicalPrice,

            'old_selected' =>
                $snapshotSelected,

            'new_selected' =>
                $historicalSelected,

            'price_changed' =>
                $priceDifferent,

            'selected_changed' =>
                $selectedDifferent
        ];
    }


    /*
     * ========================================================
     * DIAGNOSTIC SUMMARY
     * ========================================================
     */

    echo "============================================<br>";
    echo "Recovery Diagnostic<br>";
    echo "============================================<br><br>";


    echo "Snapshot Rows: "
        . number_format(
            count(
                $snapshots
            )
        )
        . "<br>";


    echo "Historical Players: "
        . number_format(
            count(
                $historyByPlayerId
            )
        )
        . "<br>";


    echo "Rows Requiring Recovery: "
        . number_format(
            count(
                $recoverable
            )
        )
        . "<br>";


    echo "Rows Already Correct: "
        . number_format(
            count(
                $unchanged
            )
        )
        . "<br>";


    echo "Rows Without Trustworthy GW1 History: "
        . number_format(
            count(
                $withoutHistory
            )
        )
        . "<br>";


    echo "Price Corrections Required: "
        . number_format(
            $priceChanges
        )
        . "<br>";


    echo "Selected Corrections Required: "
        . number_format(
            $selectedChanges
        )
        . "<br><br>";


    /*
     * ========================================================
     * PRICE CHANGE SAMPLE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Price Corrections<br>";
    echo "============================================<br><br>";


    $priceRowsShown =
        0;


    foreach (
        $recoverable
        as $row
    ) {

        if (
            empty(
                $row[
                    'price_changed'
                ]
            )
        ) {

            continue;
        }


        echo htmlspecialchars(
            $row[
                'name'
            ],
            ENT_QUOTES,
            'UTF-8'
        )
        . " — Snapshot £"
        . (
            $row[
                'old_price'
            ]
            !==
            null
                ? number_format(
                    $row[
                        'old_price'
                    ],
                    1
                )
                : 'NULL'
        )
        . "m → Historical £"
        . number_format(
            $row[
                'new_price'
            ],
            1
        )
        . "m<br>";


        $priceRowsShown++;


        if (
            $priceRowsShown >= 25
        ) {

            break;
        }
    }


    if (
        $priceRowsShown === 0
    ) {

        echo "No price corrections required.<br>";
    }


    echo "<br>";


    /*
     * ========================================================
     * PLAYERS WITHOUT GW1 HISTORY
     * ========================================================
     */

    echo "============================================<br>";
    echo "Rows Without GW1 Historical Evidence<br>";
    echo "============================================<br><br>";


    if (
        empty(
            $withoutHistory
        )
    ) {

        echo "None<br>";

    } else {

        foreach (
            $withoutHistory
            as $row
        ) {

            echo htmlspecialchars(
                $row[
                    'name'
                ],
                ENT_QUOTES,
                'UTF-8'
            )
            . " — Player ID "
            . $row[
                'player_id'
            ]
            . "<br>";
        }
    }


    echo "<br>";


    /*
     * ========================================================
     * DRY RUN EXIT
     * ========================================================
     */

    if (
        !$apply
    ) {

        echo "============================================<br>";
        echo "DRY RUN COMPLETE<br>";
        echo "============================================<br><br>";


        echo "No database rows were changed.<br><br>";


        echo "To apply this recovery, run:<br>";
        echo "<strong>"
            . htmlspecialchars(
                basename(
                    __FILE__
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "?apply=1"
            . "</strong><br><br>";


        echo "Fields that would be repaired:<br>";
        echo "- price<br>";
        echo "- selected<br><br>";


        echo "Fields deliberately NOT changed:<br>";
        echo "- selected_by_percent<br>";
        echo "- performance statistics<br>";
        echo "- availability<br>";
        echo "- any other snapshot field<br><br>";


        echo "RESULT: RECOVERY DRY RUN COMPLETE";

        exit;
    }


    /*
     * ========================================================
     * APPLY RECOVERY
     * ========================================================
     */

    echo "============================================<br>";
    echo "Applying Recovery<br>";
    echo "============================================<br><br>";


    $updateStatement =
        $db->prepare(
            "
                UPDATE
                    player_gameweek_snapshots

                SET
                    price = :price,
                    selected = :selected

                WHERE
                    id = :snapshot_id
                    AND
                    gameweek_id = :gameweek_id
                    AND
                    player_id = :player_id
            "
        );


    $db->beginTransaction();


    $updatedRows =
        0;


    foreach (
        $recoverable
        as $row
    ) {

        $snapshotId =
            (int) (
                $row[
                    'snapshot_id'
                ]
                ?? 0
            );


        $playerId =
            (int) (
                $row[
                    'player_id'
                ]
                ?? 0
            );


        if (
            $snapshotId <= 0
            ||
            $playerId <= 0
        ) {

            throw new RuntimeException(
                'Recovery encountered an invalid snapshot identity'
            );
        }


        $updateStatement
            ->execute(
                [
                    ':price' =>
                        $row[
                            'new_price'
                        ],

                    ':selected' =>
                        $row[
                            'new_selected'
                        ],

                    ':snapshot_id' =>
                        $snapshotId,

                    ':gameweek_id' =>
                        $gameweekId,

                    ':player_id' =>
                        $playerId
                ]
            );


        $updatedRows +=
            $updateStatement
                ->rowCount();
    }


    /*
     * ========================================================
     * POST-WRITE VALIDATION
     * ========================================================
     */

    $validationStatement =
        $db->prepare(
            "
                SELECT
                    COUNT(*) AS matched_rows,

                    SUM(
                        CASE
                            WHEN
                                s.price = h.price
                            THEN 1
                            ELSE 0
                        END
                    ) AS matching_price_rows,

                    SUM(
                        CASE
                            WHEN
                                s.selected = h.selected
                            THEN 1
                            ELSE 0
                        END
                    ) AS matching_selected_rows

                FROM
                    player_gameweek_snapshots s

                INNER JOIN
                    player_fixture_history h
                        ON h.player_id = s.player_id
                        AND h.gameweek_id = s.gameweek_id

                WHERE
                    s.gameweek_id = :gameweek_id
            "
        );


    $validationStatement
        ->execute(
            [
                ':gameweek_id' =>
                    $gameweekId
            ]
        );


    $validation =
        $validationStatement
            ->fetch(
                PDO::FETCH_ASSOC
            );


    $matchedRows =
        (int) (
            $validation[
                'matched_rows'
            ]
            ?? 0
        );


    $matchingPriceRows =
        (int) (
            $validation[
                'matching_price_rows'
            ]
            ?? 0
        );


    $matchingSelectedRows =
        (int) (
            $validation[
                'matching_selected_rows'
            ]
            ?? 0
        );


    /*
     * GW1 currently contains one history row per historical
     * player. Therefore these should all agree exactly.
     */
    if (
        $matchedRows <= 0
        ||
        $matchingPriceRows !== $matchedRows
        ||
        $matchingSelectedRows !== $matchedRows
    ) {

        throw new RuntimeException(
            'Post-recovery validation failed; transaction will be rolled back'
        );
    }


    $db->commit();


    /*
     * ========================================================
     * RESULT
     * ========================================================
     */

    echo "Rows Updated: "
        . number_format(
            $updatedRows
        )
        . "<br>";


    echo "Validated Historical Rows: "
        . number_format(
            $matchedRows
        )
        . "<br>";


    echo "Matching Historical Prices: "
        . number_format(
            $matchingPriceRows
        )
        . "<br>";


    echo "Matching Historical Selected Counts: "
        . number_format(
            $matchingSelectedRows
        )
        . "<br>";


    echo "Rows Left Unchanged Due To Missing GW1 History: "
        . number_format(
            count(
                $withoutHistory
            )
        )
        . "<br><br>";


    echo "RESULT: GW1 MARKET SNAPSHOT RECOVERY COMPLETE ✅";


} catch (
    Throwable $exception
) {

    if (
        isset(
            $db
        )
        &&
        $db instanceof PDO
        &&
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    echo "ERROR: "
        . htmlspecialchars(
            $exception
                ->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br><br>";


    echo "RESULT: GW1 MARKET SNAPSHOT RECOVERY FAILED ❌";

    exit(1);
}