<?php

/*
 * ============================================================
 * Player Gameweek Snapshot Market Fields Test
 * ============================================================
 *
 * Purpose:
 *
 * Validate that player_gameweek_snapshots contains the
 * historical market fields required by Market Intelligence.
 *
 * This test is intentionally read-only.
 *
 * It does NOT:
 *
 * - alter the database
 * - repair GW1
 * - overwrite snapshot data
 * - fetch external ownership data
 *
 * We first establish the current schema/state before making
 * any database changes.
 * ============================================================
 */


require_once __DIR__ . '/../classes/Database.php';


echo '<pre>';

echo "============================================\n";
echo "Player Gameweek Snapshot Market Fields Test\n";
echo "============================================\n\n";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function passTest(
    string $message
): void {

    global $passed;

    $passed++;

    echo "PASS: {$message}\n";
}


function failTest(
    string $message
): void {

    global $failed;

    $failed++;

    echo "FAIL: {$message}\n";
}


function section(
    string $title
): void {

    echo "============================================\n";
    echo $title . "\n";
    echo "============================================\n";
}


/*
 * ============================================================
 * DATABASE CONNECTION
 * ============================================================
 */

try {

    $database =
        new Database();


    $pdo =
        $database->getConnection();

} catch (
    Throwable $exception
) {

    echo "DATABASE ERROR\n";
    echo $exception->getMessage() . "\n";

    echo '</pre>';

    exit;
}


/*
 * ============================================================
 * Scenario A
 * Snapshot Table Exists
 * ============================================================
 */

section(
    'Scenario A: Snapshot Table Exists'
);


$tableStatement =
    $pdo->query(
        "
        SHOW TABLES
        LIKE 'player_gameweek_snapshots'
        "
    );


$tableExists =
    $tableStatement->fetchColumn();


if (
    $tableExists !== false
) {

    passTest(
        'player_gameweek_snapshots table exists'
    );

} else {

    failTest(
        'player_gameweek_snapshots table does not exist'
    );


    echo "\n";
    echo "Further market-field diagnostics cannot run.\n\n";


    section(
        'Player Gameweek Snapshot Market Fields Test Summary'
    );


    echo "Passed: {$passed}\n";
    echo "Failed: {$failed}\n\n";


    if (
        $failed === 0
    ) {

        echo "RESULT: TESTS PASSED ✅\n";

    } else {

        echo "RESULT: TESTS FAILED ❌\n";
    }


    echo '</pre>';

    exit;
}


echo "\n";


/*
 * ============================================================
 * Scenario B
 * Current Snapshot Columns
 * ============================================================
 */

section(
    'Scenario B: Current Snapshot Columns'
);


$columnStatement =
    $pdo->query(
        "
        SHOW COLUMNS
        FROM player_gameweek_snapshots
        "
    );


$columns =
    $columnStatement->fetchAll(
        PDO::FETCH_ASSOC
    );


$columnNames =
    [];


foreach (
    $columns as $column
) {

    $columnName =
        $column['Field'] ?? null;


    if (
        $columnName === null
    ) {

        continue;
    }


    $columnNames[] =
        $columnName;


    echo $columnName . "\n";
}


echo "\n";


if (
    !empty(
        $columnNames
    )
) {

    passTest(
        'snapshot schema can be inspected'
    );

} else {

    failTest(
        'snapshot schema returned no columns'
    );
}


echo "\n";


/*
 * ============================================================
 * Scenario C
 * Required Core Snapshot Fields
 * ============================================================
 */

section(
    'Scenario C: Required Core Snapshot Fields'
);


$coreRequiredFields =
    [
        'player_id',
        'gameweek_id',
        'price',
        'selected_by_percent'
    ];


$coreMissingFields =
    [];


foreach (
    $coreRequiredFields as $field
) {

    if (
        in_array(
            $field,
            $columnNames,
            true
        )
    ) {

        echo "FOUND: {$field}\n";

    } else {

        echo "MISSING: {$field}\n";

        $coreMissingFields[] =
            $field;
    }
}


echo "\n";


if (
    empty(
        $coreMissingFields
    )
) {

    passTest(
        'all core historical snapshot fields exist'
    );

} else {

    failTest(
        'one or more core historical snapshot fields are missing'
    );
}


echo "\n";


/*
 * ============================================================
 * Scenario D
 * Raw Selected Ownership Count
 * ============================================================
 */

section(
    'Scenario D: Raw Selected Ownership Count'
);


$hasSelected =
    in_array(
        'selected',
        $columnNames,
        true
    );


if (
    $hasSelected
) {

    echo "Column: selected\n";
    echo "Status: Available\n\n";


    passTest(
        'raw selected manager count is stored historically'
    );

} else {

    echo "Column: selected\n";
    echo "Status: Missing\n\n";


    failTest(
        'raw selected manager count is not stored historically'
    );
}


echo "\n";


/*
 * ============================================================
 * Scenario E
 * Transfer Market Fields
 * ============================================================
 */

section(
    'Scenario E: Transfer Market Fields'
);


$transferFieldCandidates =
    [
        'transfers_in',
        'transfers_out',
        'transfers_in_event',
        'transfers_out_event'
    ];


$availableTransferFields =
    [];


$missingTransferFields =
    [];


foreach (
    $transferFieldCandidates as $field
) {

    if (
        in_array(
            $field,
            $columnNames,
            true
        )
    ) {

        $availableTransferFields[] =
            $field;

        echo "FOUND: {$field}\n";

    } else {

        $missingTransferFields[] =
            $field;

        echo "MISSING: {$field}\n";
    }
}


echo "\n";


echo
    'Available Transfer Fields: '
    . count(
        $availableTransferFields
    )
    . "\n";


echo
    'Missing Transfer Fields: '
    . count(
        $missingTransferFields
    )
    . "\n\n";


echo
    'Available Transfer Fields: '
    . count(
        $availableTransferFields
    )
    . "\n";


echo
    'Missing Transfer Fields: '
    . count(
        $missingTransferFields
    )
    . "\n\n";


echo "Transfer Market Source: player_fixture_history\n";
echo "Snapshot Transfer Columns Required: No\n";


echo "\n";


/*
 * ============================================================
 * Scenario F
 * GW1 Resolution
 * ============================================================
 */

section(
    'Scenario F: Resolve GW1'
);


$gameweekStatement =
    $pdo->prepare(
        "
        SELECT
            id
        FROM
            gameweeks
        WHERE
            fpl_gameweek_id = :gameweek
        LIMIT 1
        "
    );


$gameweekStatement->execute(
    [
        ':gameweek' => 1
    ]
);


$gw1Id =
    $gameweekStatement->fetchColumn();


if (
    $gw1Id !== false
) {

    echo "Local GW1 ID: {$gw1Id}\n\n";


    passTest(
        'GW1 resolves from local gameweek storage'
    );

} else {

    echo "Local GW1 ID: Not Found\n\n";


    failTest(
        'GW1 could not be resolved from local gameweek storage'
    );
}


echo "\n";


/*
 * ============================================================
 * Scenario G
 * GW1 Snapshot Population
 * ============================================================
 */

section(
    'Scenario G: GW1 Snapshot Population'
);


$gw1SnapshotCount =
    0;


if (
    $gw1Id !== false
) {

    $snapshotCountStatement =
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


    $snapshotCountStatement->execute(
        [
            ':gameweek_id' => $gw1Id
        ]
    );


    $gw1SnapshotCount =
        (int)
        $snapshotCountStatement->fetchColumn();


    echo
        "GW1 Snapshot Rows: "
        . number_format(
            $gw1SnapshotCount
        )
        . "\n\n";


    if (
        $gw1SnapshotCount > 0
    ) {

        passTest(
            'GW1 contains historical snapshot rows'
        );

    } else {

        failTest(
            'GW1 contains no historical snapshot rows'
        );
    }

} else {

    echo "GW1 Snapshot Rows: Unable To Check\n\n";


    failTest(
        'GW1 snapshot population cannot be checked'
    );
}


echo "\n";


/*
 * ============================================================
 * Scenario H
 * GW1 Selected Field Population
 * ============================================================
 */

section(
    'Scenario H: GW1 Selected Field Population'
);


if (
    $gw1Id === false
) {

    echo "GW1 Selected Population: Unable To Check\n\n";


    failTest(
        'selected population cannot be checked because GW1 is unavailable'
    );

} elseif (
    !$hasSelected
) {

    echo "GW1 Selected Population: Column Missing\n\n";


    failTest(
        'selected population cannot be checked because the column does not exist'
    );

} else {

    /*
     * Only players with genuine GW1 historical evidence are
     * required to contain a selected-manager count.
     *
     * Players added to FPL after GW1 may legitimately exist in
     * the snapshot table without a corresponding GW1 history
     * row. Their selected value must not be invented.
     */

    $selectedPopulationStatement =
        $pdo->prepare(
            "
                SELECT
                    COUNT(*) AS snapshot_rows,

                    SUM(
                        CASE
                            WHEN h.id IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS historical_rows,

                    SUM(
                        CASE
                            WHEN
                                h.id IS NOT NULL
                                AND s.selected IS NOT NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS historical_selected_populated,

                    SUM(
                        CASE
                            WHEN
                                h.id IS NOT NULL
                                AND s.selected IS NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS historical_selected_missing,

                    SUM(
                        CASE
                            WHEN h.id IS NULL
                            THEN 1
                            ELSE 0
                        END
                    ) AS rows_without_history

                FROM
                    player_gameweek_snapshots s

                LEFT JOIN
                    player_fixture_history h
                        ON h.player_id = s.player_id
                        AND h.gameweek_id = s.gameweek_id

                WHERE
                    s.gameweek_id = :gameweek_id
            "
        );


    $selectedPopulationStatement->execute(
        [
            ':gameweek_id' =>
                $gw1Id
        ]
    );


    $selectedPopulation =
        $selectedPopulationStatement->fetch(
            PDO::FETCH_ASSOC
        );


    $snapshotRows =
        (int) (
            $selectedPopulation[
                'snapshot_rows'
            ]
            ?? 0
        );


    $historicalRows =
        (int) (
            $selectedPopulation[
                'historical_rows'
            ]
            ?? 0
        );


    $historicalSelectedPopulated =
        (int) (
            $selectedPopulation[
                'historical_selected_populated'
            ]
            ?? 0
        );


    $historicalSelectedMissing =
        (int) (
            $selectedPopulation[
                'historical_selected_missing'
            ]
            ?? 0
        );


    $rowsWithoutHistory =
        (int) (
            $selectedPopulation[
                'rows_without_history'
            ]
            ?? 0
        );


    echo
        "GW1 Snapshot Rows: "
        . number_format(
            $snapshotRows
        )
        . "\n";


    echo
        "GW1 Rows With Historical Evidence: "
        . number_format(
            $historicalRows
        )
        . "\n";


    echo
        "GW1 Historical Selected Populated: "
        . number_format(
            $historicalSelectedPopulated
        )
        . "\n";


    echo
        "GW1 Historical Selected Missing: "
        . number_format(
            $historicalSelectedMissing
        )
        . "\n";


    echo
        "GW1 Rows Without Historical Evidence: "
        . number_format(
            $rowsWithoutHistory
        )
        . "\n\n";


    if (
        $historicalRows > 0
        &&
        $historicalSelectedPopulated
            ===
            $historicalRows
        &&
        $historicalSelectedMissing === 0
    ) {

        passTest(
            'all GW1 players with historical evidence have selected counts'
        );

    } else {

        failTest(
            'one or more genuine GW1 historical players are missing selected counts'
        );
    }


    /*
     * Verify that we have not manufactured selected counts for
     * players without genuine GW1 history.
     */

    $unsupportedSelectedStatement =
        $pdo->prepare(
            "
                SELECT
                    COUNT(*)

                FROM
                    player_gameweek_snapshots s

                LEFT JOIN
                    player_fixture_history h
                        ON h.player_id = s.player_id
                        AND h.gameweek_id = s.gameweek_id

                WHERE
                    s.gameweek_id = :gameweek_id
                    AND h.id IS NULL
                    AND s.selected IS NOT NULL
            "
        );


    $unsupportedSelectedStatement->execute(
        [
            ':gameweek_id' =>
                $gw1Id
        ]
    );


    $unsupportedSelectedRows =
        (int)
        $unsupportedSelectedStatement
            ->fetchColumn();


    echo
        "Unsupported Selected Values: "
        . number_format(
            $unsupportedSelectedRows
        )
        . "\n\n";


    if (
        $unsupportedSelectedRows === 0
    ) {

        passTest(
            'players without GW1 historical evidence retain no invented selected count'
        );

    } else {

        failTest(
            'selected counts exist without supporting GW1 historical evidence'
        );
    }
}


echo "\n";


/*
 * ============================================================
 * Scenario I
 * Market Intelligence Readiness
 * ============================================================
 */

section(
    'Scenario I: Market Intelligence Readiness'
);


$marketFields =
    [
        'price',
        'selected',
        'selected_by_percent',
        'transfers_in',
        'transfers_out',
        'transfers_in_event',
        'transfers_out_event'
    ];


foreach (
    $marketFields as $field
) {

    $status =
        in_array(
            $field,
            $columnNames,
            true
        )
        ? 'Available'
        : 'Missing';


    echo
        str_pad(
            $field,
            24
        )
        . ': '
        . $status
        . "\n";
}


echo "\n";


$marketReady =
    in_array(
        'price',
        $columnNames,
        true
    )
    &&
    in_array(
        'selected',
        $columnNames,
        true
    )
    &&
    in_array(
        'selected_by_percent',
        $columnNames,
        true
    );


if (
    $marketReady
) {

    passTest(
        'snapshot schema contains the minimum historical market fields'
    );

} else {

    failTest(
        'snapshot schema is missing one or more minimum historical market fields'
    );
}


echo "\n";


/*
 * ============================================================
 * Scenario J
 * Recommended Schema Change
 * ============================================================
 */

section(
    'Scenario J: Recommended Schema Change'
);


if (
    !$hasSelected
) {

    echo "Required Change:\n";
    echo "Add BIGINT UNSIGNED NULL column: selected\n\n";

    echo "Reason:\n";
    echo "Historical ownership percentages cannot always be reconstructed\n";
    echo "safely, while the raw selected-manager count is available from\n";
    echo "historical FPL player history and gives us an exact ownership\n";
    echo "signal for market analysis.\n";

} else {

    echo "Required Change: None for selected field\n";
    echo "The raw selected-manager count already exists.\n";
}


echo "\n";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

section(
    'Player Gameweek Snapshot Market Fields Test Summary'
);


echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n\n";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅\n";

} else {

    echo "RESULT: TESTS FAILED ❌\n";
}


echo '</pre>';