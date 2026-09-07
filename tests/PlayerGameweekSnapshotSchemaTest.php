<?php

declare(strict_types=1);


/*
 * ============================================================
 * PLAYER GAMEWEEK SNAPSHOT SCHEMA TEST
 * ============================================================
 *
 * Protects the persistent database contract used by the
 * pre-deadline player snapshot candidate lifecycle.
 *
 * The schema file must describe both:
 *
 * 1. immutable player_gameweek_snapshots
 * 2. mutable player_gameweek_snapshot_candidates
 *
 * This test does not alter the database.
 */


echo "============================================<br>";
echo "Player Gameweek Snapshot Schema Test<br>";
echo "============================================<br>";


$passed =
    0;


$failed =
    0;


function snapshotSchemaCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


$schemaFile =
    __DIR__
    . '/../sql/schema.sql';


$schemaSource =
    is_file(
        $schemaFile
    )
        ? file_get_contents(
            $schemaFile
        )
        : false;


/*
 * ============================================================
 * SCENARIO A
 * SCHEMA FOUNDATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Schema Foundation<br>";
echo "============================================<br>";


snapshotSchemaCheck(
    'Database schema file exists',
    is_string(
        $schemaSource
    )
);


snapshotSchemaCheck(
    'Schema defines immutable player gameweek snapshots',
    is_string(
        $schemaSource
    )
    &&
    str_contains(
        $schemaSource,
        'CREATE TABLE IF NOT EXISTS `player_gameweek_snapshots`'
    )
);


/*
 * ============================================================
 * SCENARIO B
 * IMMUTABLE SNAPSHOT OWNERSHIP EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Immutable Snapshot Ownership Evidence<br>";
echo "============================================<br>";


$immutableSnapshotSection =
    '';


if (
    is_string(
        $schemaSource
    )
) {

    $immutableStart =
        strpos(
            $schemaSource,
            'CREATE TABLE IF NOT EXISTS `player_gameweek_snapshots`'
        );


    $immutableEnd =
        strpos(
            $schemaSource,
            'CREATE TABLE IF NOT EXISTS `recommendation_snapshots`'
        );


    if (
        $immutableStart !== false
        &&
        $immutableEnd !== false
        &&
        $immutableEnd > $immutableStart
    ) {

        $immutableSnapshotSection =
            substr(
                $schemaSource,
                $immutableStart,
                $immutableEnd - $immutableStart
            );
    }
}


snapshotSchemaCheck(
    'Immutable snapshot schema preserves selected ownership count',
    $immutableSnapshotSection !== ''
    &&
    str_contains(
        $immutableSnapshotSection,
        '`selected`'
    )
);


/*
 * ============================================================
 * SCENARIO C
 * MUTABLE CANDIDATE TABLE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Mutable Candidate Table<br>";
echo "============================================<br>";


snapshotSchemaCheck(
    'Schema defines player gameweek snapshot candidates',
    is_string(
        $schemaSource
    )
    &&
    str_contains(
        $schemaSource,
        'CREATE TABLE IF NOT EXISTS `player_gameweek_snapshot_candidates`'
    )
);


$candidateSection =
    '';


if (
    is_string(
        $schemaSource
    )
) {

    $candidateStart =
        strpos(
            $schemaSource,
            'CREATE TABLE IF NOT EXISTS `player_gameweek_snapshot_candidates`'
        );


    if (
        $candidateStart !== false
    ) {

        $candidateSection =
            substr(
                $schemaSource,
                $candidateStart
            );
    }
}


snapshotSchemaCheck(
    'Candidate schema preserves local gameweek identity',
    $candidateSection !== ''
    &&
    str_contains(
        $candidateSection,
        '`gameweek_id`'
    )
);


snapshotSchemaCheck(
    'Candidate schema preserves player identity',
    $candidateSection !== ''
    &&
    str_contains(
        $candidateSection,
        '`player_id`'
    )
    &&
    str_contains(
        $candidateSection,
        '`fpl_player_id`'
    )
);


snapshotSchemaCheck(
    'Candidate schema preserves generation and deadline timestamps',
    $candidateSection !== ''
    &&
    str_contains(
        $candidateSection,
        '`generated_at`'
    )
    &&
    str_contains(
        $candidateSection,
        '`deadline_time`'
    )
);


snapshotSchemaCheck(
    'Candidate schema preserves ownership evidence',
    $candidateSection !== ''
    &&
    str_contains(
        $candidateSection,
        '`selected`'
    )
    &&
    str_contains(
        $candidateSection,
        '`selected_by_percent`'
    )
);


snapshotSchemaCheck(
    'Candidate schema enforces one mutable row per player and gameweek',
    $candidateSection !== ''
    &&
    str_contains(
        $candidateSection,
        '(`gameweek_id`, `player_id`)'
    )
);


snapshotSchemaCheck(
    'Candidate schema references gameweeks',
    $candidateSection !== ''
    &&
    str_contains(
        $candidateSection,
        'REFERENCES `gameweeks` (`id`)'
    )
);


snapshotSchemaCheck(
    'Candidate schema references players',
    $candidateSection !== ''
    &&
    str_contains(
        $candidateSection,
        'REFERENCES `players` (`id`)'
    )
);


snapshotSchemaCheck(
    'Candidate schema references teams',
    $candidateSection !== ''
    &&
    str_contains(
        $candidateSection,
        'REFERENCES `teams` (`id`)'
    )
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Gameweek Snapshot Schema Test Summary<br>";
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

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}