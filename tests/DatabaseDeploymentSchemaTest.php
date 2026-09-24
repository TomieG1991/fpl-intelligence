<?php

echo "============================================<br>";
echo "Database Deployment Schema Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function databaseDeploymentSchemaCheck(
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
 * A. SCHEMA SOURCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Schema Source<br>";
echo "============================================<br>";


$schemaPath =
    __DIR__
    . '/../sql/schema.sql';


$schemaExists =
    is_file(
        $schemaPath
    );


databaseDeploymentSchemaCheck(
    'Deployment schema exists.',
    $schemaExists
);


$schemaSource =
    $schemaExists
        ? file_get_contents(
            $schemaPath
        )
        : false;


databaseDeploymentSchemaCheck(
    'Deployment schema source can be read.',
    is_string(
        $schemaSource
    )
);


$schemaSource =
    is_string(
        $schemaSource
    )
        ? $schemaSource
        : '';


/*
 * ============================================================
 * B. DATABASE BOOTSTRAP
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "B. Database Bootstrap<br>";
echo "============================================<br>";


databaseDeploymentSchemaCheck(
    'Deployment schema does not create a hard-coded database.',
    preg_match(
        '/CREATE\s+DATABASE/i',
        $schemaSource
    )
    !== 1
);


databaseDeploymentSchemaCheck(
    'Deployment schema does not select a hard-coded database.',
    preg_match(
        '/\bUSE\s+`?fpl_intelligence`?\s*;/i',
        $schemaSource
    )
    !== 1
);


databaseDeploymentSchemaCheck(
    'Schema declares utf8mb4 character handling.',
    str_contains(
        $schemaSource,
        'utf8mb4'
    )
);


/*
 * ============================================================
 * C. FRESH DEPLOYMENT SAFETY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "C. Fresh Deployment Safety<br>";
echo "============================================<br>";


databaseDeploymentSchemaCheck(
    'Schema contains no invalid DEFAULT NOT NULL column definitions.',
    preg_match(
        '/\bDEFAULT\s+NOT\s+NULL\b/i',
        $schemaSource
    )
    !== 1
);


databaseDeploymentSchemaCheck(
    'Recommendation snapshot bench evidence remains required.',
    preg_match(
        '/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`recommendation_snapshots`.*?`bench`\s+LONGTEXT\s+NOT\s+NULL/is',
        $schemaSource
    )
    === 1
);


databaseDeploymentSchemaCheck(
    'Recommendation candidate bench evidence remains required.',
    preg_match(
        '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?recommendation_candidates`?.*?\bbench\s+LONGTEXT\s+NOT\s+NULL/is',
        $schemaSource
    )
    === 1
);


/*
 * ============================================================
 * D. IDEMPOTENT DEPLOYMENT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "D. Idempotent Deployment<br>";
echo "============================================<br>";


$expectedTables = [
    'teams',
    'players',
    'fixtures',
    'gameweeks',
    'player_gameweek_snapshots',
    'player_gameweek_snapshot_candidates',
    'recommendation_snapshots',
    'player_fixture_history',
    'recommendation_candidates',
    'update_runs'
];


foreach (
    $expectedTables
    as $tableName
) {

    databaseDeploymentSchemaCheck(
        $tableName
            . ' can be created safely when the table already exists.',
        preg_match(
            '/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?'
                . preg_quote(
                    $tableName,
                    '/'
                )
                . '`?\s*\(/i',
            $schemaSource
        )
        === 1
    );
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Database Deployment Schema Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}