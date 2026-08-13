<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed = 0;
$failed = 0;


function testPass(
    string $description,
    bool $condition
): void {

    global $passed, $failed;

    if ($condition) {

        echo "PASS: {$description}<br>";
        $passed++;

    } else {

        echo "FAIL: {$description}<br>";
        $failed++;
    }
}


function section(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";
    echo "{$title}<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * SCENARIO A
 * Database Object
 * ============================================================
 */

section(
    'Scenario A: Database Object'
);


try {

    $database =
        new Database();


    testPass(
        'Database object can be created',
        $database instanceof Database
    );

} catch (Throwable $exception) {

    testPass(
        'Database object can be created',
        false
    );


    echo "ERROR: "
        . $exception->getMessage()
        . "<br>";


    echo "<br>";
    echo "============================================<br>";
    echo "Database Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: {$passed}<br>";
    echo "Failed: {$failed}<br>";

    echo "<br>RESULT: TESTS FAILED ❌<br>";

    exit;
}


/*
 * ============================================================
 * SCENARIO B
 * PDO Connection
 * ============================================================
 */

section(
    'Scenario B: PDO Connection'
);


$connection =
    $database->getConnection();


testPass(
    'getConnection returns PDO',
    $connection instanceof PDO
);


/*
 * ============================================================
 * SCENARIO C
 * PDO Error Handling
 * ============================================================
 */

section(
    'Scenario C: PDO Error Handling'
);


$errorMode =
    $connection->getAttribute(
        PDO::ATTR_ERRMODE
    );


testPass(
    'PDO exception error mode is enabled',
    $errorMode === PDO::ERRMODE_EXCEPTION
);


/*
 * ============================================================
 * SCENARIO D
 * Native Prepared Statements
 * ============================================================
 */

section(
    'Scenario D: Native Prepared Statements'
);


$emulatedPrepares =
    $connection->getAttribute(
        PDO::ATTR_EMULATE_PREPARES
    );


testPass(
    'PDO emulated prepares are disabled',
    $emulatedPrepares === false
    ||
    $emulatedPrepares === 0
);


/*
 * ============================================================
 * SCENARIO E
 * Default Fetch Mode
 * ============================================================
 */

section(
    'Scenario E: Default Fetch Mode'
);


$stmt =
    $connection->query(
        'SELECT 1 AS test_value'
    );


$result =
    $stmt->fetch();


testPass(
    'Default fetch returns an array',
    is_array($result)
);


testPass(
    'Default fetch mode provides associative key',
    array_key_exists(
        'test_value',
        $result
    )
);


testPass(
    'Default fetch mode does not provide numeric key',
    !array_key_exists(
        0,
        $result
    )
);


/*
 * ============================================================
 * SCENARIO F
 * Database Query
 * ============================================================
 */

section(
    'Scenario F: Database Query'
);


$stmt =
    $connection->query(
        'SELECT 1 AS connection_test'
    );


$result =
    $stmt->fetch();


testPass(
    'Database can execute a query',
    is_array($result)
);


testPass(
    'Database query returns expected result',
    isset($result['connection_test'])
    &&
    (int) $result['connection_test'] === 1
);


/*
 * ============================================================
 * SCENARIO G
 * UTF-8 Character Set
 * ============================================================
 */

section(
    'Scenario G: UTF-8 Character Set'
);


$stmt =
    $connection->query(
        "SHOW VARIABLES LIKE 'character_set_connection'"
    );


$characterSet =
    $stmt->fetch();


echo "Connection Character Set: "
    . (
        $characterSet['Value']
        ?? 'Unknown'
    )
    . "<br>";


testPass(
    'Database connection uses utf8mb4',
    isset($characterSet['Value'])
    &&
    strtolower(
        $characterSet['Value']
    ) === 'utf8mb4'
);


/*
 * ============================================================
 * SCENARIO H
 * Application Tables
 * ============================================================
 */

section(
    'Scenario H: Application Tables'
);


$requiredTables = [

    'teams',
    'players',
    'fixtures'
];


foreach (
    $requiredTables
    as $table
) {

    $stmt =
        $connection->query(
            "SHOW TABLES LIKE "
            . $connection->quote(
                $table
            )
        );


    $tableExists =
        $stmt->fetchColumn()
        !== false;


    testPass(
        "{$table} table exists",
        $tableExists
    );
}


/*
 * ============================================================
 * SCENARIO I
 * Repository Compatibility
 * ============================================================
 */

section(
    'Scenario I: Repository Compatibility'
);


$playerRepository =
    new PlayerRepository(
        $connection
    );


$teamRepository =
    new TeamRepository(
        $connection
    );


$fixtureRepository =
    new FixtureRepository(
        $connection
    );


testPass(
    'PDO connection can be used by PlayerRepository',
    $playerRepository instanceof PlayerRepository
);


testPass(
    'PDO connection can be used by TeamRepository',
    $teamRepository instanceof TeamRepository
);


testPass(
    'PDO connection can be used by FixtureRepository',
    $fixtureRepository instanceof FixtureRepository
);

/*
 * ============================================================
 * SCENARIO J
 * Storage Engine
 * ============================================================
 */

section(
    'Scenario J: Storage Engine'
);


$tablesToCheck = [
    'teams',
    'players',
    'fixtures'
];


foreach ($tablesToCheck as $table) {

    $stmt =
        $connection->prepare("
            SELECT ENGINE
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table_name
        ");


    $stmt->execute([
        ':table_name' => $table
    ]);


    $engine =
        $stmt->fetchColumn();


    echo "{$table} Engine: "
        . (
            $engine
            ?? 'Unknown'
        )
        . "<br>";


    testPass(
        "{$table} table uses InnoDB",
        strtoupper(
            (string) $engine
        ) === 'INNODB'
    );
}


/*
 * ============================================================
 * SCENARIO K
 * Table Character Set
 * ============================================================
 */

section(
    'Scenario K: Table Character Set'
);


foreach ($tablesToCheck as $table) {

    $stmt =
        $connection->prepare("
            SELECT TABLE_COLLATION
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = :table_name
        ");


    $stmt->execute([
        ':table_name' => $table
    ]);


    $collation =
        $stmt->fetchColumn();


    echo "{$table} Collation: "
        . (
            $collation
            ?? 'Unknown'
        )
        . "<br>";


    testPass(
        "{$table} table uses utf8mb4",
        is_string($collation)
        &&
        str_starts_with(
            strtolower($collation),
            'utf8mb4_'
        )
    );
}


/*
 * ============================================================
 * SCENARIO L
 * Foreign Keys
 * ============================================================
 */

section(
    'Scenario L: Foreign Key Relationships'
);


$stmt =
    $connection->query("
        SELECT
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");


$foreignKeys =
    $stmt->fetchAll();


$foreignKeyNames =
    array_column(
        $foreignKeys,
        'CONSTRAINT_NAME'
    );


testPass(
    'Players team foreign key exists',
    in_array(
        'fk_players_team',
        $foreignKeyNames,
        true
    )
);


testPass(
    'Fixture home-team foreign key exists',
    in_array(
        'fk_fixtures_home_team',
        $foreignKeyNames,
        true
    )
);


testPass(
    'Fixture away-team foreign key exists',
    in_array(
        'fk_fixtures_away_team',
        $foreignKeyNames,
        true
    )
);


/*
 * ============================================================
 * SCENARIO M
 * Nullable Fixture Gameweek
 * ============================================================
 */

section(
    'Scenario M: Fixture Gameweek Contract'
);


$stmt =
    $connection->query("
        SELECT IS_NULLABLE
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'fixtures'
        AND COLUMN_NAME = 'gameweek'
    ");


$gameweekNullable =
    $stmt->fetchColumn();


testPass(
    'Fixture gameweek allows null values',
    $gameweekNullable === 'YES'
);


/*
 * ============================================================
 * SCENARIO N
 * Player Position Column
 * ============================================================
 */

section(
    'Scenario N: Player Position Column'
);


$stmt =
    $connection->query("
        SELECT
            DATA_TYPE,
            CHARACTER_MAXIMUM_LENGTH
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'players'
        AND COLUMN_NAME = 'position'
    ");


$positionColumn =
    $stmt->fetch();


testPass(
    'Player position uses varchar',
    isset($positionColumn['DATA_TYPE'])
    &&
    strtolower(
        $positionColumn['DATA_TYPE']
    ) === 'varchar'
);


testPass(
    'Player position column supports canonical position codes',
    isset(
        $positionColumn['CHARACTER_MAXIMUM_LENGTH']
    )
    &&
    (int)
        $positionColumn[
            'CHARACTER_MAXIMUM_LENGTH'
        ]
        >= 3
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

section(
    'Database Configuration'
);


echo "PDO Driver: "
    . $connection->getAttribute(
        PDO::ATTR_DRIVER_NAME
    )
    . "<br>";


echo "Server Version: "
    . $connection->getAttribute(
        PDO::ATTR_SERVER_VERSION
    )
    . "<br>";


echo "Character Set: "
    . (
        $characterSet['Value']
        ?? 'Unknown'
    )
    . "<br>";


echo "Native Prepared Statements: "
    . (
        $emulatedPrepares
            ? 'No'
            : 'Yes'
    )
    . "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

section(
    'Database Test Summary'
);


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}