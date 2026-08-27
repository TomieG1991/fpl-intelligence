<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Import Integration Test<br>";
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

function playerSnapshotImportCheck(
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
 * SETUP
 * ============================================================
 */

try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $gameweekRepository =
        new GameweekRepository(
            $db
        );


    $snapshotRepository =
        new PlayerGameweekSnapshotRepository(
            $db
        );


    $currentGameweek =
        $gameweekRepository
            ->getCurrent();

} catch (
    Throwable $exception
) {

    echo "SETUP FAILED ❌<br>";


    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );


    exit;
}


/*
 * ============================================================
 * SCENARIO A
 * CURRENT GAMEWEEK
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Current Gameweek<br>";
echo "============================================<br>";


playerSnapshotImportCheck(
    'Current FPL gameweek is available',
    is_array(
        $currentGameweek
    )
);


$currentGameweekId =
    is_array(
        $currentGameweek
    )
        ? (int) (
            $currentGameweek[
                'id'
            ]
            ?? 0
        )
        : 0;


$currentFplGameweekId =
    is_array(
        $currentGameweek
    )
        ? (int) (
            $currentGameweek[
                'fpl_gameweek_id'
            ]
            ?? 0
        )
        : 0;


playerSnapshotImportCheck(
    'Current gameweek has valid local ID',
    $currentGameweekId > 0
);


playerSnapshotImportCheck(
    'Current gameweek has valid FPL ID',
    $currentFplGameweekId > 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * SNAPSHOT COVERAGE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Snapshot Coverage<br>";
echo "============================================<br>";


$currentSnapshots =
    $currentGameweekId > 0
        ? $snapshotRepository
            ->getByGameweekId(
                $currentGameweekId
            )
        : [];


$playerCount =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM players
            "
        )
        ->fetchColumn();


playerSnapshotImportCheck(
    'Current gameweek contains player snapshots',
    !empty(
        $currentSnapshots
    )
);


playerSnapshotImportCheck(
    'Current gameweek snapshot count matches player count',
    count(
        $currentSnapshots
    )
    ===
    $playerCount
);


echo "Current FPL Gameweek: "
    . $currentFplGameweekId
    . "<br>";


echo "Players: "
    . $playerCount
    . "<br>";


echo "Snapshots: "
    . count(
        $currentSnapshots
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * UNIQUE PLAYER SNAPSHOTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Snapshot Uniqueness<br>";
echo "============================================<br>";


$duplicateStatement =
    $db->prepare(
        "
        SELECT
            player_id,
            COUNT(*) AS duplicate_count
        FROM
            player_gameweek_snapshots
        WHERE
            gameweek_id = :gameweek_id
        GROUP BY
            player_id
        HAVING
            COUNT(*) > 1
        "
    );


$duplicateStatement
    ->execute([

        ':gameweek_id' =>
            $currentGameweekId
    ]);


$duplicateRows =
    $duplicateStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


playerSnapshotImportCheck(
    'Current gameweek contains no duplicate player snapshots',
    empty(
        $duplicateRows
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * FOREIGN KEY CONSISTENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Foreign Key Consistency<br>";
echo "============================================<br>";


$invalidPlayerLinks =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM
                player_gameweek_snapshots pgs
            LEFT JOIN
                players p
                    ON p.id = pgs.player_id
            WHERE
                p.id IS NULL
            "
        )
        ->fetchColumn();


$invalidTeamLinks =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM
                player_gameweek_snapshots pgs
            LEFT JOIN
                teams t
                    ON t.id = pgs.team_id
            WHERE
                t.id IS NULL
            "
        )
        ->fetchColumn();


$invalidGameweekLinks =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM
                player_gameweek_snapshots pgs
            LEFT JOIN
                gameweeks g
                    ON g.id = pgs.gameweek_id
            WHERE
                g.id IS NULL
            "
        )
        ->fetchColumn();


playerSnapshotImportCheck(
    'All snapshots reference valid players',
    $invalidPlayerLinks === 0
);


playerSnapshotImportCheck(
    'All snapshots reference valid teams',
    $invalidTeamLinks === 0
);


playerSnapshotImportCheck(
    'All snapshots reference valid gameweeks',
    $invalidGameweekLinks === 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * PLAYER ID CONSISTENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Player Identity Consistency<br>";
echo "============================================<br>";


$identityMismatchStatement =
    $db->prepare(
        "
        SELECT COUNT(*)
        FROM
            player_gameweek_snapshots pgs
        INNER JOIN
            players p
                ON p.id = pgs.player_id
        WHERE
            pgs.gameweek_id = :gameweek_id
            AND
            pgs.fpl_player_id <> p.fpl_player_id
        "
    );


$identityMismatchStatement
    ->execute([

        ':gameweek_id' =>
            $currentGameweekId
    ]);


$identityMismatchCount =
    (int) $identityMismatchStatement
        ->fetchColumn();


playerSnapshotImportCheck(
    'Snapshot FPL player IDs match current player records',
    $identityMismatchCount === 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * TEAM / POSITION CONSISTENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Team and Position Consistency<br>";
echo "============================================<br>";


$teamMismatchStatement =
    $db->prepare(
        "
        SELECT COUNT(*)
        FROM
            player_gameweek_snapshots pgs
        INNER JOIN
            players p
                ON p.id = pgs.player_id
        WHERE
            pgs.gameweek_id = :gameweek_id
            AND
            pgs.team_id <> p.team_id
        "
    );


$teamMismatchStatement
    ->execute([

        ':gameweek_id' =>
            $currentGameweekId
    ]);


$teamMismatchCount =
    (int) $teamMismatchStatement
        ->fetchColumn();


$positionMismatchStatement =
    $db->prepare(
        "
        SELECT COUNT(*)
        FROM
            player_gameweek_snapshots pgs
        INNER JOIN
            players p
                ON p.id = pgs.player_id
        WHERE
            pgs.gameweek_id = :gameweek_id
            AND
            (
                pgs.position <> p.position
                OR
                (
                    pgs.position IS NULL
                    AND
                    p.position IS NOT NULL
                )
                OR
                (
                    pgs.position IS NOT NULL
                    AND
                    p.position IS NULL
                )
            )
        "
    );


$positionMismatchStatement
    ->execute([

        ':gameweek_id' =>
            $currentGameweekId
    ]);


$positionMismatchCount =
    (int) $positionMismatchStatement
        ->fetchColumn();


playerSnapshotImportCheck(
    'Current snapshot team IDs match current player records',
    $teamMismatchCount === 0
);


playerSnapshotImportCheck(
    'Current snapshot positions match current player records',
    $positionMismatchCount === 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * SNAPSHOT DATA INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Snapshot Data Integrity<br>";
echo "============================================<br>";


$allSnapshotsValid =
    true;


foreach (
    $currentSnapshots
    as $snapshot
) {

    $playerId =
        (int) (
            $snapshot[
                'player_id'
            ]
            ?? 0
        );


    $fplPlayerId =
        (int) (
            $snapshot[
                'fpl_player_id'
            ]
            ?? 0
        );


    $teamId =
        (int) (
            $snapshot[
                'team_id'
            ]
            ?? 0
        );


    $position =
        $snapshot[
            'position'
        ]
        ?? null;


    $price =
        $snapshot[
            'price'
        ]
        ?? null;


    $minutes =
        $snapshot[
            'minutes'
        ]
        ?? null;


    if (
        $playerId <= 0
        ||
        $fplPlayerId <= 0
        ||
        $teamId <= 0
        ||
        !in_array(
            $position,
            [
                'GK',
                'DEF',
                'MID',
                'FWD'
            ],
            true
        )
        ||
        (
            $price !== null
            &&
            (
                !is_numeric(
                    $price
                )
                ||
                (float) $price <= 0
            )
        )
        ||
        !is_numeric(
            $minutes
        )
        ||
        (int) $minutes < 0
    ) {

        $allSnapshotsValid =
            false;

        break;
    }
}


playerSnapshotImportCheck(
    'All current snapshots contain valid core data',
    $allSnapshotsValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * HISTORICAL SNAPSHOT INTEGRITY
 * ============================================================
 *
 * Completed-gameweek snapshots are immutable historical
 * records.
 *
 * They are not required to remain equal to the live players
 * table after prices, ownership or cumulative statistics move.
 *
 * Where trustworthy player_fixture_history exists for the
 * completed gameweek, historical price and selected-manager
 * count should agree with that historical evidence.
 */

echo "============================================<br>";
echo "Scenario H: Historical Snapshot Integrity<br>";
echo "============================================<br>";


$currentGameweekFinished =
    !empty(
        $currentGameweek[
            'finished'
        ]
        ?? false
    );


$currentGameweekDataChecked =
    !empty(
        $currentGameweek[
            'data_checked'
        ]
        ?? false
    );


$snapshotEligible =
    $currentGameweekFinished
    &&
    $currentGameweekDataChecked;


echo "Gameweek Finished: "
    . (
        $currentGameweekFinished
            ? 'Yes'
            : 'No'
    )
    . "<br>";


echo "Gameweek Data Checked: "
    . (
        $currentGameweekDataChecked
            ? 'Yes'
            : 'No'
    )
    . "<br>";


echo "Historical Snapshot State: "
    . (
        $snapshotEligible
            ? 'Completed / Immutable'
            : 'Not Yet Historical'
    )
    . "<br><br>";


if (
    $snapshotEligible
) {

    /*
     * --------------------------------------------------------
     * HISTORICAL PRICE PARITY
     * --------------------------------------------------------
     */

    $historicalPriceStatement =
        $db->prepare(
            "
                SELECT
                    COUNT(DISTINCT pgs.player_id)
                        AS comparable_players,

                    COUNT(
                        DISTINCT CASE
                            WHEN
                                pgs.price = pfh.price
                                OR
                                (
                                    pgs.price IS NULL
                                    AND
                                    pfh.price IS NULL
                                )
                            THEN pgs.player_id
                            ELSE NULL
                        END
                    ) AS matching_price_players

                FROM
                    player_gameweek_snapshots pgs

                INNER JOIN
                    player_fixture_history pfh
                        ON pfh.player_id = pgs.player_id
                        AND pfh.gameweek_id = pgs.gameweek_id

                WHERE
                    pgs.gameweek_id = :gameweek_id
            "
        );


    $historicalPriceStatement
        ->execute(
            [
                ':gameweek_id' =>
                    $currentGameweekId
            ]
        );


    $historicalPriceResult =
        $historicalPriceStatement
            ->fetch(
                PDO::FETCH_ASSOC
            );


    $comparableHistoricalPlayers =
        (int) (
            $historicalPriceResult[
                'comparable_players'
            ]
            ?? 0
        );


    $matchingHistoricalPrices =
        (int) (
            $historicalPriceResult[
                'matching_price_players'
            ]
            ?? 0
        );


    echo "Players With Historical Evidence: "
        . $comparableHistoricalPlayers
        . "<br>";


    echo "Players Matching Historical Price: "
        . $matchingHistoricalPrices
        . "<br>";


    playerSnapshotImportCheck(
        'Completed snapshots match trustworthy historical prices',
        $comparableHistoricalPlayers > 0
        &&
        $matchingHistoricalPrices
            ===
            $comparableHistoricalPlayers
    );


    /*
     * --------------------------------------------------------
     * HISTORICAL SELECTED-COUNT PARITY
     * --------------------------------------------------------
     *
     * Raw selected-manager count is exact historical market
     * evidence. Only players with a populated historical value
     * are considered comparable.
     */

    $historicalSelectedStatement =
        $db->prepare(
            "
                SELECT
                    COUNT(DISTINCT pgs.player_id)
                        AS comparable_players,

                    COUNT(
                        DISTINCT CASE
                            WHEN
                                pgs.selected = pfh.selected
                            THEN pgs.player_id
                            ELSE NULL
                        END
                    ) AS matching_selected_players

                FROM
                    player_gameweek_snapshots pgs

                INNER JOIN
                    player_fixture_history pfh
                        ON pfh.player_id = pgs.player_id
                        AND pfh.gameweek_id = pgs.gameweek_id

                WHERE
                    pgs.gameweek_id = :gameweek_id
                    AND
                    pfh.selected IS NOT NULL
            "
        );


    $historicalSelectedStatement
        ->execute(
            [
                ':gameweek_id' =>
                    $currentGameweekId
            ]
        );


    $historicalSelectedResult =
        $historicalSelectedStatement
            ->fetch(
                PDO::FETCH_ASSOC
            );


    $comparableSelectedPlayers =
        (int) (
            $historicalSelectedResult[
                'comparable_players'
            ]
            ?? 0
        );


    $matchingHistoricalSelected =
        (int) (
            $historicalSelectedResult[
                'matching_selected_players'
            ]
            ?? 0
        );


    echo "Players With Historical Selected Count: "
        . $comparableSelectedPlayers
        . "<br>";


    echo "Players Matching Historical Selected Count: "
        . $matchingHistoricalSelected
        . "<br>";


    playerSnapshotImportCheck(
        'Completed snapshots match trustworthy historical selected counts',
        $comparableSelectedPlayers > 0
        &&
        $matchingHistoricalSelected
            ===
            $comparableSelectedPlayers
    );


    /*
     * --------------------------------------------------------
     * UNSUPPORTED HISTORICAL SELECTED VALUES
     * --------------------------------------------------------
     *
     * A player without fixture-history evidence for this
     * gameweek must not be given an invented selected count.
     */

    $unsupportedSelectedStatement =
        $db->prepare(
            "
                SELECT
                    COUNT(*)

                FROM
                    player_gameweek_snapshots pgs

                LEFT JOIN
                    player_fixture_history pfh
                        ON pfh.player_id = pgs.player_id
                        AND pfh.gameweek_id = pgs.gameweek_id

                WHERE
                    pgs.gameweek_id = :gameweek_id
                    AND
                    pfh.id IS NULL
                    AND
                    pgs.selected IS NOT NULL
            "
        );


    $unsupportedSelectedStatement
        ->execute(
            [
                ':gameweek_id' =>
                    $currentGameweekId
            ]
        );


    $unsupportedSelectedCount =
        (int)
        $unsupportedSelectedStatement
            ->fetchColumn();


    echo "Unsupported Historical Selected Values: "
        . $unsupportedSelectedCount
        . "<br>";


    playerSnapshotImportCheck(
        'Snapshots do not invent selected counts without historical evidence',
        $unsupportedSelectedCount === 0
    );


    /*
     * --------------------------------------------------------
     * LIVE DIVERGENCE DIAGNOSTIC
     * --------------------------------------------------------
     *
     * Historical snapshots are allowed to differ from current
     * live player state. Report that divergence diagnostically
     * rather than treating it as a failure.
     */

    $livePriceDifferenceStatement =
        $db->prepare(
            "
                SELECT
                    COUNT(*)

                FROM
                    player_gameweek_snapshots pgs

                INNER JOIN
                    players p
                        ON p.id = pgs.player_id

                WHERE
                    pgs.gameweek_id = :gameweek_id
                    AND
                    (
                        pgs.price <> p.price
                        OR
                        (
                            pgs.price IS NULL
                            AND
                            p.price IS NOT NULL
                        )
                        OR
                        (
                            pgs.price IS NOT NULL
                            AND
                            p.price IS NULL
                        )
                    )
            "
        );


    $livePriceDifferenceStatement
        ->execute(
            [
                ':gameweek_id' =>
                    $currentGameweekId
            ]
        );


    $livePriceDifferenceCount =
        (int)
        $livePriceDifferenceStatement
            ->fetchColumn();


    echo "Historical/Live Price Differences: "
        . $livePriceDifferenceCount
        . "<br>";


    echo "Live-State Parity Required: No<br>";


} else {

    /*
     * If this test is ever run before the current gameweek has
     * completed, historical fixture evidence is not yet the
     * correct comparison source.
     *
     * At that point this scenario validates only that the
     * snapshot set remains structurally available.
     */

    playerSnapshotImportCheck(
        'Non-completed gameweek snapshot set remains structurally available',
        !empty(
            $currentSnapshots
        )
    );


    echo "Historical Evidence Comparison: Not Yet Applicable<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * GAMEWEEK DISTRIBUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Snapshot Gameweek Distribution<br>";
echo "============================================<br>";


$distributionStatement =
    $db->query(
        "
        SELECT
            gameweek_id,
            COUNT(*) AS snapshot_count
        FROM
            player_gameweek_snapshots
        GROUP BY
            gameweek_id
        ORDER BY
            gameweek_id ASC
        "
    );


$distribution =
    $distributionStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


$currentDistributionFound =
    false;


foreach (
    $distribution
    as $row
) {

    if (
        (
            (int) (
                $row[
                    'gameweek_id'
                ]
                ?? 0
            )
        )
        ===
        $currentGameweekId
    ) {

        $currentDistributionFound =
            (
                (int) (
                    $row[
                        'snapshot_count'
                    ]
                    ?? 0
                )
            )
            ===
            $playerCount;

        break;
    }
}


playerSnapshotImportCheck(
    'Current gameweek distribution contains complete snapshot set',
    $currentDistributionFound
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * IDEMPOTENCY CONTRACT
 * ============================================================
 *
 * updateFPLData.php has already been executed repeatedly.
 *
 * There must still be only one row per current
 * gameweek/player pair.
 */

echo "============================================<br>";
echo "Scenario J: Idempotency Contract<br>";
echo "============================================<br>";


$totalSnapshotCount =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM player_gameweek_snapshots
            "
        )
        ->fetchColumn();


$distinctSnapshotCount =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM (
                SELECT
                    gameweek_id,
                    player_id
                FROM
                    player_gameweek_snapshots
                GROUP BY
                    gameweek_id,
                    player_id
            ) AS unique_snapshots
            "
        )
        ->fetchColumn();


playerSnapshotImportCheck(
    'Snapshot rows remain unique after repeated updater runs',
    $totalSnapshotCount
    ===
    $distinctSnapshotCount
);


echo "Total Historical Snapshots: "
    . $totalSnapshotCount
    . "<br><br>";


/*
 * ============================================================
 * CURRENT STATE DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Current Snapshot State<br>";
echo "============================================<br>";


echo "Current Gameweek: "
    . htmlspecialchars(
        (string) (
            $currentGameweek[
                'name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "FPL Gameweek ID: "
    . $currentFplGameweekId
    . "<br>";


echo "Players: "
    . $playerCount
    . "<br>";


echo "Current Snapshots: "
    . count(
        $currentSnapshots
    )
    . "<br>";


echo "Total Historical Snapshots: "
    . $totalSnapshotCount
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Gameweek Snapshot Import Integration Test Summary<br>";
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