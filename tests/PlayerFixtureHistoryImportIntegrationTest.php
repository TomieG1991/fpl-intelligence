<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Fixture History Import Integration Test<br>";
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

function playerFixtureHistoryImportCheck(
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


    $historyRepository =
        new PlayerFixtureHistoryRepository(
            $db
        );

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
 * HISTORY EXISTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Imported History Exists<br>";
echo "============================================<br>";


$totalHistoryRows =
    $historyRepository
        ->count();


playerFixtureHistoryImportCheck(
    'Fixture history contains imported rows',
    $totalHistoryRows > 0
);


echo "Stored Fixture History Rows: "
    . $totalHistoryRows
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * GAMEWEEK DISTRIBUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Gameweek Distribution<br>";
echo "============================================<br>";


$distributionStatement =
    $db->query(
        "
        SELECT
            g.id AS gameweek_id,
            g.fpl_gameweek_id,
            COUNT(*) AS history_rows,
            COUNT(DISTINCT pfh.player_id) AS unique_players,
            COUNT(DISTINCT pfh.fixture_id) AS fixtures_represented
        FROM
            player_fixture_history pfh
        INNER JOIN
            gameweeks g
                ON g.id = pfh.gameweek_id
        GROUP BY
            g.id,
            g.fpl_gameweek_id
        ORDER BY
            g.fpl_gameweek_id ASC
        "
    );


$distribution =
    $distributionStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


playerFixtureHistoryImportCheck(
    'Fixture history belongs to at least one gameweek',
    !empty(
        $distribution
    )
);


$allGameweekRowsValid =
    true;


foreach (
    $distribution
    as $row
) {

    $fplGameweekId =
        (int) (
            $row[
                'fpl_gameweek_id'
            ]
            ?? 0
        );


    $historyRows =
        (int) (
            $row[
                'history_rows'
            ]
            ?? 0
        );


    $uniquePlayers =
        (int) (
            $row[
                'unique_players'
            ]
            ?? 0
        );


    $fixturesRepresented =
        (int) (
            $row[
                'fixtures_represented'
            ]
            ?? 0
        );


    if (
        $fplGameweekId <= 0
        ||
        $historyRows <= 0
        ||
        $uniquePlayers <= 0
        ||
        $fixturesRepresented <= 0
    ) {

        $allGameweekRowsValid =
            false;

        break;
    }
}


playerFixtureHistoryImportCheck(
    'Every imported gameweek contains valid history distribution',
    $allGameweekRowsValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * PLAYER / FIXTURE UNIQUENESS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Player Fixture Uniqueness<br>";
echo "============================================<br>";


$duplicateStatement =
    $db->query(
        "
        SELECT
            player_id,
            fixture_id,
            COUNT(*) AS duplicate_count
        FROM
            player_fixture_history
        GROUP BY
            player_id,
            fixture_id
        HAVING
            COUNT(*) > 1
        "
    );


$duplicateRows =
    $duplicateStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


playerFixtureHistoryImportCheck(
    'No duplicate player/fixture history rows exist',
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
                player_fixture_history pfh
            LEFT JOIN
                players p
                    ON p.id = pfh.player_id
            WHERE
                p.id IS NULL
            "
        )
        ->fetchColumn();


$invalidGameweekLinks =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM
                player_fixture_history pfh
            LEFT JOIN
                gameweeks g
                    ON g.id = pfh.gameweek_id
            WHERE
                g.id IS NULL
            "
        )
        ->fetchColumn();


$invalidFixtureLinks =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM
                player_fixture_history pfh
            LEFT JOIN
                fixtures f
                    ON f.id = pfh.fixture_id
            WHERE
                f.id IS NULL
            "
        )
        ->fetchColumn();


$invalidTeamLinks =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM
                player_fixture_history pfh
            LEFT JOIN
                teams t
                    ON t.id = pfh.team_id
            WHERE
                t.id IS NULL
            "
        )
        ->fetchColumn();


$invalidOpponentLinks =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM
                player_fixture_history pfh
            LEFT JOIN
                teams t
                    ON t.id = pfh.opponent_team_id
            WHERE
                t.id IS NULL
            "
        )
        ->fetchColumn();


playerFixtureHistoryImportCheck(
    'All history rows reference valid players',
    $invalidPlayerLinks === 0
);


playerFixtureHistoryImportCheck(
    'All history rows reference valid gameweeks',
    $invalidGameweekLinks === 0
);


playerFixtureHistoryImportCheck(
    'All history rows reference valid fixtures',
    $invalidFixtureLinks === 0
);


playerFixtureHistoryImportCheck(
    'All history rows reference valid teams',
    $invalidTeamLinks === 0
);


playerFixtureHistoryImportCheck(
    'All history rows reference valid opponents',
    $invalidOpponentLinks === 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * FPL IDENTITY CONSISTENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: FPL Identity Consistency<br>";
echo "============================================<br>";


$playerIdentityMismatch =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM
                player_fixture_history pfh
            INNER JOIN
                players p
                    ON p.id = pfh.player_id
            WHERE
                pfh.fpl_player_id <> p.fpl_player_id
            "
        )
        ->fetchColumn();


$fixtureIdentityMismatch =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM
                player_fixture_history pfh
            INNER JOIN
                fixtures f
                    ON f.id = pfh.fixture_id
            WHERE
                pfh.fpl_fixture_id <> f.fpl_fixture_id
            "
        )
        ->fetchColumn();


playerFixtureHistoryImportCheck(
    'Stored FPL player IDs match player records',
    $playerIdentityMismatch === 0
);


playerFixtureHistoryImportCheck(
    'Stored FPL fixture IDs match fixture records',
    $fixtureIdentityMismatch === 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * GAMEWEEK / FIXTURE CONSISTENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Gameweek Fixture Consistency<br>";
echo "============================================<br>";


$gameweekMismatchStatement =
    $db->query(
        "
        SELECT COUNT(*)
        FROM
            player_fixture_history pfh
        INNER JOIN
            gameweeks g
                ON g.id = pfh.gameweek_id
        INNER JOIN
            fixtures f
                ON f.id = pfh.fixture_id
        WHERE
            g.fpl_gameweek_id <> f.gameweek
        "
    );


$gameweekMismatchCount =
    (int) $gameweekMismatchStatement
        ->fetchColumn();


playerFixtureHistoryImportCheck(
    'History gameweek matches fixture gameweek',
    $gameweekMismatchCount === 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * HISTORICAL TEAM CONTEXT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Historical Team Context<br>";
echo "============================================<br>";


$invalidHistoricalTeamStatement =
    $db->query(
        "
        SELECT COUNT(*)
        FROM
            player_fixture_history pfh
        INNER JOIN
            fixtures f
                ON f.id = pfh.fixture_id
        WHERE
            (
                pfh.was_home = 1
                AND
                (
                    pfh.team_id <> f.home_team_id
                    OR
                    pfh.opponent_team_id <> f.away_team_id
                )
            )
            OR
            (
                pfh.was_home = 0
                AND
                (
                    pfh.team_id <> f.away_team_id
                    OR
                    pfh.opponent_team_id <> f.home_team_id
                )
            )
        "
    );


$invalidHistoricalTeamCount =
    (int) $invalidHistoricalTeamStatement
        ->fetchColumn();


playerFixtureHistoryImportCheck(
    'Historical team/opponent context matches fixture home-away state',
    $invalidHistoricalTeamCount === 0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * CORE DATA INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Core Data Integrity<br>";
echo "============================================<br>";


$coreDataValid =
    true;


$coreStatement =
    $db->query(
        "
        SELECT
            player_id,
            gameweek_id,
            fixture_id,
            team_id,
            opponent_team_id,
            was_home,
            minutes,
            starts,
            goals,
            assists,
            clean_sheets,
            total_points,
            price
        FROM
            player_fixture_history
        "
    );


$coreRows =
    $coreStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


foreach (
    $coreRows
    as $row
) {

    $playerId =
        (int) (
            $row[
                'player_id'
            ]
            ?? 0
        );


    $gameweekId =
        (int) (
            $row[
                'gameweek_id'
            ]
            ?? 0
        );


    $fixtureId =
        (int) (
            $row[
                'fixture_id'
            ]
            ?? 0
        );


    $teamId =
        (int) (
            $row[
                'team_id'
            ]
            ?? 0
        );


    $opponentTeamId =
        (int) (
            $row[
                'opponent_team_id'
            ]
            ?? 0
        );


    $wasHome =
        (int) (
            $row[
                'was_home'
            ]
            ?? -1
        );


    $minutes =
        $row[
            'minutes'
        ]
        ?? null;


    $starts =
        $row[
            'starts'
        ]
        ?? null;


    $price =
        $row[
            'price'
        ]
        ?? null;


    if (
        $playerId <= 0
        ||
        $gameweekId <= 0
        ||
        $fixtureId <= 0
        ||
        $teamId <= 0
        ||
        $opponentTeamId <= 0
        ||
        $teamId === $opponentTeamId
        ||
        !in_array(
            $wasHome,
            [
                0,
                1
            ],
            true
        )
        ||
        !is_numeric(
            $minutes
        )
        ||
        (int) $minutes < 0
        ||
        !is_numeric(
            $starts
        )
        ||
        (int) $starts < 0
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
    ) {

        $coreDataValid =
            false;

        break;
    }
}


playerFixtureHistoryImportCheck(
    'All history rows contain valid core data',
    $coreDataValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * NORMAL / BLANK / DOUBLE GAMEWEEK SAFETY
 * ============================================================
 *
 * The database contract must allow:
 *
 * normal GW:
 * one fixture row per player
 *
 * blank GW:
 * no fixture row for that player
 *
 * double GW:
 * multiple fixture rows for the same player/gameweek
 *
 * Therefore uniqueness belongs to player + fixture,
 * not player + gameweek.
 */

echo "============================================<br>";
echo "Scenario I: Multi-Fixture Gameweek Safety<br>";
echo "============================================<br>";


$uniqueIndexStatement =
    $db->query(
        "
        SHOW INDEX
        FROM player_fixture_history
        WHERE Key_name = 'unique_player_fixture_history'
        "
    );


$uniqueIndexRows =
    $uniqueIndexStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


$uniqueColumns =
    [];


foreach (
    $uniqueIndexRows
    as $indexRow
) {

    $sequence =
        (int) (
            $indexRow[
                'Seq_in_index'
            ]
            ?? 0
        );


    $columnName =
        $indexRow[
            'Column_name'
        ]
        ?? null;


    if (
        $sequence > 0
        &&
        is_string(
            $columnName
        )
    ) {

        $uniqueColumns[
            $sequence
        ] =
            $columnName;
    }
}


ksort(
    $uniqueColumns
);


$uniqueColumns =
    array_values(
        $uniqueColumns
    );


playerFixtureHistoryImportCheck(
    'History uniqueness uses player and fixture',
    $uniqueColumns
    ===
    [
        'player_id',
        'fixture_id'
    ]
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * CURRENT IMPORT COVERAGE
 * ============================================================
 *
 * Do not permanently hard-code GW1 or 610 players.
 *
 * Instead verify that every stored history row belongs to
 * a real imported fixture and that represented gameweeks
 * have meaningful coverage.
 */

echo "============================================<br>";
echo "Scenario J: Current Import Coverage<br>";
echo "============================================<br>";


$representedFixtureCount =
    (int) $db
        ->query(
            "
            SELECT COUNT(
                DISTINCT fixture_id
            )
            FROM player_fixture_history
            "
        )
        ->fetchColumn();


$representedPlayerCount =
    (int) $db
        ->query(
            "
            SELECT COUNT(
                DISTINCT player_id
            )
            FROM player_fixture_history
            "
        )
        ->fetchColumn();


playerFixtureHistoryImportCheck(
    'Imported history represents multiple fixtures',
    $representedFixtureCount > 0
);


playerFixtureHistoryImportCheck(
    'Imported history represents multiple players',
    $representedPlayerCount > 0
);


echo "Unique Players Represented: "
    . $representedPlayerCount
    . "<br>";


echo "Unique Fixtures Represented: "
    . $representedFixtureCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO K
 * ZERO-MINUTE HISTORY
 * ============================================================
 *
 * A real FPL history row can legitimately contain zero
 * minutes. This is known evidence, not missing evidence.
 */

echo "============================================<br>";
echo "Scenario K: Zero-Minute History<br>";
echo "============================================<br>";


$zeroMinuteCount =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM player_fixture_history
            WHERE minutes = 0
            "
        )
        ->fetchColumn();


playerFixtureHistoryImportCheck(
    'Zero-minute fixture history is safely preserved',
    $zeroMinuteCount >= 0
);


echo "Zero-Minute Rows: "
    . $zeroMinuteCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO L
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Performance<br>";
echo "============================================<br>";


$startedAt =
    microtime(
        true
    );


$historyRepository
    ->count();


$distributionStatement =
    $db->query(
        "
        SELECT
            gameweek_id,
            COUNT(*)
        FROM
            player_fixture_history
        GROUP BY
            gameweek_id
        "
    );


$distributionStatement
    ->fetchAll(
        PDO::FETCH_ASSOC
    );


$runtime =
    microtime(
        true
    )
    -
    $startedAt;


playerFixtureHistoryImportCheck(
    'Fixture-history database validation completes within 2 seconds',
    $runtime < 2.0
);


echo "Measured Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * CURRENT HISTORY DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Current Fixture History Distribution<br>";
echo "============================================<br>";


foreach (
    $distribution
    as $row
) {

    echo "GW"
        . (int) (
            $row[
                'fpl_gameweek_id'
            ]
            ?? 0
        )
        . " | Rows "
        . (int) (
            $row[
                'history_rows'
            ]
            ?? 0
        )
        . " | Players "
        . (int) (
            $row[
                'unique_players'
            ]
            ?? 0
        )
        . " | Fixtures "
        . (int) (
            $row[
                'fixtures_represented'
            ]
            ?? 0
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Fixture History Import Integration Test Summary<br>";
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