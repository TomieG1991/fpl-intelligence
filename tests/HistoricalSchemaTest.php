<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Historical Schema Test<br>";
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

function historicalSchemaCheck(
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

try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();

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
 * HISTORICAL TABLES EXIST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Historical Tables<br>";
echo "============================================<br>";


$tableStatement =
    $db->query(
        "SHOW TABLES"
    );


$tables =
    $tableStatement
        ->fetchAll(
            PDO::FETCH_COLUMN
        );


historicalSchemaCheck(
    'Gameweeks table exists',
    in_array(
        'gameweeks',
        $tables,
        true
    )
);


historicalSchemaCheck(
    'Player gameweek snapshots table exists',
    in_array(
        'player_gameweek_snapshots',
        $tables,
        true
    )
);


historicalSchemaCheck(
    'Player fixture history table exists',
    in_array(
        'player_fixture_history',
        $tables,
        true
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * GAMEWEEK COLUMNS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Gameweek Columns<br>";
echo "============================================<br>";


$gameweekColumnsStatement =
    $db->query(
        "SHOW COLUMNS FROM gameweeks"
    );


$gameweekColumnsRaw =
    $gameweekColumnsStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


$gameweekColumns =
    [];


foreach (
    $gameweekColumnsRaw
    as $column
) {

    $field =
        $column[
            'Field'
        ]
        ?? null;


    if (
        is_string(
            $field
        )
    ) {

        $gameweekColumns[] =
            $field;
    }
}


$requiredGameweekColumns = [

    'id',
    'fpl_gameweek_id',
    'name',
    'deadline_time',
    'finished',
    'data_checked',
    'is_previous',
    'is_current',
    'is_next',
    'created_at',
    'updated_at'
];


foreach (
    $requiredGameweekColumns
    as $column
) {

    historicalSchemaCheck(
        'Gameweeks exposes column: '
        . $column,
        in_array(
            $column,
            $gameweekColumns,
            true
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * SNAPSHOT COLUMNS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Snapshot Columns<br>";
echo "============================================<br>";


$snapshotColumnsStatement =
    $db->query(
        "SHOW COLUMNS FROM player_gameweek_snapshots"
    );


$snapshotColumnsRaw =
    $snapshotColumnsStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


$snapshotColumns =
    [];


foreach (
    $snapshotColumnsRaw
    as $column
) {

    $field =
        $column[
            'Field'
        ]
        ?? null;


    if (
        is_string(
            $field
        )
    ) {

        $snapshotColumns[] =
            $field;
    }
}


$requiredSnapshotColumns = [

    'id',
    'gameweek_id',
    'player_id',
    'fpl_player_id',
    'team_id',
    'position',
    'price',
    'selected_by_percent',
    'chance_of_playing',
    'status',
    'news',
    'minutes',
    'goals',
    'assists',
    'clean_sheets',
    'bonus',
    'bps',
    'ict_index',
    'expected_goals',
    'expected_assists',
    'expected_goal_involvements',
    'created_at',
    'updated_at'
];


foreach (
    $requiredSnapshotColumns
    as $column
) {

    historicalSchemaCheck(
        'Snapshot table exposes column: '
        . $column,
        in_array(
            $column,
            $snapshotColumns,
            true
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * FIXTURE HISTORY COLUMNS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Fixture History Columns<br>";
echo "============================================<br>";


$fixtureHistoryColumnsStatement =
    $db->query(
        "SHOW COLUMNS FROM player_fixture_history"
    );


$fixtureHistoryColumnsRaw =
    $fixtureHistoryColumnsStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


$fixtureHistoryColumns =
    [];


foreach (
    $fixtureHistoryColumnsRaw
    as $column
) {

    $field =
        $column[
            'Field'
        ]
        ?? null;


    if (
        is_string(
            $field
        )
    ) {

        $fixtureHistoryColumns[] =
            $field;
    }
}


$requiredFixtureHistoryColumns = [

    'id',
    'gameweek_id',
    'player_id',
    'fpl_player_id',
    'fixture_id',
    'fpl_fixture_id',
    'team_id',
    'opponent_team_id',
    'was_home',
    'total_points',
    'minutes',
    'starts',
    'goals',
    'assists',
    'expected_goals',
    'expected_assists',
    'expected_goal_involvements',
    'clean_sheets',
    'goals_conceded',
    'expected_goals_conceded',
    'saves',
    'penalties_saved',
    'clearances_blocks_interceptions',
    'recoveries',
    'tackles',
    'defensive_contribution',
    'own_goals',
    'penalties_missed',
    'yellow_cards',
    'red_cards',
    'bonus',
    'bps',
    'influence',
    'creativity',
    'threat',
    'ict_index',
    'price',
    'selected',
    'transfers_balance',
    'transfers_in',
    'transfers_out',
    'created_at',
    'updated_at'
];


foreach (
    $requiredFixtureHistoryColumns
    as $column
) {

    historicalSchemaCheck(
        'Fixture history exposes column: '
        . $column,
        in_array(
            $column,
            $fixtureHistoryColumns,
            true
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * UNIQUE KEYS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Unique Keys<br>";
echo "============================================<br>";


$gameweekIndexes =
    $db->query(
        "SHOW INDEX FROM gameweeks"
    )
    ->fetchAll(
        PDO::FETCH_ASSOC
    );


$snapshotIndexes =
    $db->query(
        "SHOW INDEX FROM player_gameweek_snapshots"
    )
    ->fetchAll(
        PDO::FETCH_ASSOC
    );


$fixtureHistoryIndexes =
    $db->query(
        "SHOW INDEX FROM player_fixture_history"
    )
    ->fetchAll(
        PDO::FETCH_ASSOC
    );


function historicalIndexExists(
    array $indexes,
    string $indexName
): bool {

    foreach (
        $indexes
        as $index
    ) {

        if (
            (
                $index[
                    'Key_name'
                ]
                ?? null
            )
            ===
            $indexName
        ) {

            return true;
        }
    }


    return false;
}


historicalSchemaCheck(
    'Gameweeks has unique FPL gameweek index',
    historicalIndexExists(
        $gameweekIndexes,
        'unique_fpl_gameweek'
    )
);


historicalSchemaCheck(
    'Snapshots have unique player/gameweek index',
    historicalIndexExists(
        $snapshotIndexes,
        'unique_player_gameweek_snapshot'
    )
);


historicalSchemaCheck(
    'Fixture history has unique player/fixture index',
    historicalIndexExists(
        $fixtureHistoryIndexes,
        'unique_player_fixture_history'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * FOREIGN KEYS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Foreign Keys<br>";
echo "============================================<br>";


$foreignKeyStatement =
    $db->prepare(
        "
        SELECT
            TABLE_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME
        FROM
            information_schema.KEY_COLUMN_USAGE
        WHERE
            TABLE_SCHEMA = DATABASE()
            AND
            REFERENCED_TABLE_NAME IS NOT NULL
            AND
            TABLE_NAME IN (
                'player_gameweek_snapshots',
                'player_fixture_history'
            )
        "
    );


$foreignKeyStatement
    ->execute();


$foreignKeys =
    $foreignKeyStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


$foreignKeyMap =
    [];


foreach (
    $foreignKeys
    as $foreignKey
) {

    $constraint =
        $foreignKey[
            'CONSTRAINT_NAME'
        ]
        ?? null;


    $referencedTable =
        $foreignKey[
            'REFERENCED_TABLE_NAME'
        ]
        ?? null;


    if (
        is_string(
            $constraint
        )
        &&
        is_string(
            $referencedTable
        )
    ) {

        $foreignKeyMap[
            $constraint
        ] =
            $referencedTable;
    }
}


$expectedForeignKeys = [

    'fk_snapshot_gameweek' =>
        'gameweeks',

    'fk_snapshot_player' =>
        'players',

    'fk_snapshot_team' =>
        'teams',

    'fk_fixture_history_gameweek' =>
        'gameweeks',

    'fk_fixture_history_player' =>
        'players',

    'fk_fixture_history_fixture' =>
        'fixtures',

    'fk_fixture_history_team' =>
        'teams',

    'fk_fixture_history_opponent' =>
        'teams'
];


foreach (
    $expectedForeignKeys
    as $constraint => $referencedTable
) {

    historicalSchemaCheck(
        $constraint
        . ' references '
        . $referencedTable,
        (
            $foreignKeyMap[
                $constraint
            ]
            ?? null
        )
        ===
        $referencedTable
    );
}


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Historical Schema Test Summary<br>";
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