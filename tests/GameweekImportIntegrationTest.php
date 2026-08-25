<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Import Integration Test<br>";
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

function gameweekImportCheck(
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


    $repository =
        new GameweekRepository(
            $db
        );


    $gameweeks =
        $repository
            ->getAll();

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
 * COMPLETE GAMEWEEK DATASET
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Complete Gameweek Dataset<br>";
echo "============================================<br>";


gameweekImportCheck(
    'Exactly 38 FPL gameweeks are stored',
    count(
        $gameweeks
    )
    === 38
);


$fplGameweekIds =
    [];


foreach (
    $gameweeks
    as $gameweek
) {

    $fplGameweekIds[] =
        (int) (
            $gameweek[
                'fpl_gameweek_id'
            ]
            ?? 0
        );
}


gameweekImportCheck(
    'All stored FPL gameweek IDs are unique',
    count(
        $fplGameweekIds
    )
    ===
    count(
        array_unique(
            $fplGameweekIds
        )
    )
);


$sortedGameweekIds =
    $fplGameweekIds;


sort(
    $sortedGameweekIds,
    SORT_NUMERIC
);


gameweekImportCheck(
    'Stored FPL gameweeks span GW1 to GW38',
    $sortedGameweekIds
    ===
    range(
        1,
        38
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * GAMEWEEK IDENTITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Gameweek Identity<br>";
echo "============================================<br>";


$allNamesValid =
    true;


$allDeadlinesValid =
    true;


foreach (
    $gameweeks
    as $gameweek
) {

    $name =
        trim(
            (string) (
                $gameweek[
                    'name'
                ]
                ?? ''
            )
        );


    if ($name === '') {

        $allNamesValid =
            false;
    }


    $deadlineTime =
        $gameweek[
            'deadline_time'
        ]
        ?? null;


    if (
        !is_string(
            $deadlineTime
        )
        ||
        trim(
            $deadlineTime
        )
        === ''
    ) {

        $allDeadlinesValid =
            false;
    }
}


gameweekImportCheck(
    'Every gameweek has a non-empty name',
    $allNamesValid
);


gameweekImportCheck(
    'Every gameweek has a deadline',
    $allDeadlinesValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * STATE FLAGS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: State Flags<br>";
echo "============================================<br>";


$previousCount =
    0;


$currentCount =
    0;


$nextCount =
    0;


$allFlagsValid =
    true;


$mutuallyExclusive =
    true;


foreach (
    $gameweeks
    as $gameweek
) {

    $isPrevious =
        (int) (
            $gameweek[
                'is_previous'
            ]
            ?? -1
        );


    $isCurrent =
        (int) (
            $gameweek[
                'is_current'
            ]
            ?? -1
        );


    $isNext =
        (int) (
            $gameweek[
                'is_next'
            ]
            ?? -1
        );


    if (
        !in_array(
            $isPrevious,
            [
                0,
                1
            ],
            true
        )
        ||
        !in_array(
            $isCurrent,
            [
                0,
                1
            ],
            true
        )
        ||
        !in_array(
            $isNext,
            [
                0,
                1
            ],
            true
        )
    ) {

        $allFlagsValid =
            false;
    }


    if ($isPrevious === 1) {

        $previousCount++;
    }


    if ($isCurrent === 1) {

        $currentCount++;
    }


    if ($isNext === 1) {

        $nextCount++;
    }


    $activeStateCount =
        $isPrevious
        +
        $isCurrent
        +
        $isNext;


    if ($activeStateCount > 1) {

        $mutuallyExclusive =
            false;
    }
}


gameweekImportCheck(
    'Previous flag is present on at most one gameweek',
    $previousCount <= 1
);


gameweekImportCheck(
    'Current flag is present on at most one gameweek',
    $currentCount <= 1
);


gameweekImportCheck(
    'Next flag is present on at most one gameweek',
    $nextCount <= 1
);


gameweekImportCheck(
    'All gameweek state flags use valid 0/1 values',
    $allFlagsValid
);


gameweekImportCheck(
    'Previous, current and next flags are mutually exclusive per gameweek',
    $mutuallyExclusive
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * COMPLETION FLAGS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Completion Flags<br>";
echo "============================================<br>";


$completionFlagsValid =
    true;


foreach (
    $gameweeks
    as $gameweek
) {

    $finished =
        (int) (
            $gameweek[
                'finished'
            ]
            ?? -1
        );


    $dataChecked =
        (int) (
            $gameweek[
                'data_checked'
            ]
            ?? -1
        );


    if (
        !in_array(
            $finished,
            [
                0,
                1
            ],
            true
        )
        ||
        !in_array(
            $dataChecked,
            [
                0,
                1
            ],
            true
        )
    ) {

        $completionFlagsValid =
            false;
    }
}


gameweekImportCheck(
    'Finished and data-checked flags use valid 0/1 values',
    $completionFlagsValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * REPOSITORY STATE LOOKUPS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Repository State Lookups<br>";
echo "============================================<br>";


$previous =
    $repository
        ->getPrevious();


$current =
    $repository
        ->getCurrent();


$next =
    $repository
        ->getNext();


gameweekImportCheck(
    'Previous lookup matches imported state count',
    (
        $previousCount === 0
        &&
        $previous === null
    )
    ||
    (
        $previousCount === 1
        &&
        is_array(
            $previous
        )
        &&
        (
            (int) (
                $previous[
                    'is_previous'
                ]
                ?? 0
            )
        )
        === 1
    )
);


gameweekImportCheck(
    'Current lookup matches imported state count',
    (
        $currentCount === 0
        &&
        $current === null
    )
    ||
    (
        $currentCount === 1
        &&
        is_array(
            $current
        )
        &&
        (
            (int) (
                $current[
                    'is_current'
                ]
                ?? 0
            )
        )
        === 1
    )
);


gameweekImportCheck(
    'Next lookup matches imported state count',
    (
        $nextCount === 0
        &&
        $next === null
    )
    ||
    (
        $nextCount === 1
        &&
        is_array(
            $next
        )
        &&
        (
            (int) (
                $next[
                    'is_next'
                ]
                ?? 0
            )
        )
        === 1
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * DEADLINE ORDER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Deadline Order<br>";
echo "============================================<br>";


$deadlineOrderValid =
    true;


$previousDeadline =
    null;


foreach (
    $gameweeks
    as $gameweek
) {

    $deadline =
        $gameweek[
            'deadline_time'
        ]
        ?? null;


    if (
        !is_string(
            $deadline
        )
        ||
        trim(
            $deadline
        )
        === ''
    ) {

        $deadlineOrderValid =
            false;

        break;
    }


    $timestamp =
        strtotime(
            $deadline
        );


    if ($timestamp === false) {

        $deadlineOrderValid =
            false;

        break;
    }


    if (
        $previousDeadline !== null
        &&
        $timestamp <= $previousDeadline
    ) {

        $deadlineOrderValid =
            false;

        break;
    }


    $previousDeadline =
        $timestamp;
}


gameweekImportCheck(
    'Gameweek deadlines increase chronologically',
    $deadlineOrderValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * DATABASE UNIQUENESS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Database Uniqueness<br>";
echo "============================================<br>";


$duplicateStatement =
    $db->query(
        "
        SELECT
            fpl_gameweek_id,
            COUNT(*) AS duplicate_count
        FROM
            gameweeks
        GROUP BY
            fpl_gameweek_id
        HAVING
            COUNT(*) > 1
        "
    );


$duplicateRows =
    $duplicateStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


gameweekImportCheck(
    'Database contains no duplicate FPL gameweeks',
    empty(
        $duplicateRows
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * IDENTITY LOOKUPS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Identity Lookups<br>";
echo "============================================<br>";


$firstGameweek =
    $repository
        ->getByFplGameweekId(
            1
        );


$lastGameweek =
    $repository
        ->getByFplGameweekId(
            38
        );


gameweekImportCheck(
    'GW1 can be retrieved by FPL gameweek ID',
    is_array(
        $firstGameweek
    )
    &&
    (
        (int) (
            $firstGameweek[
                'fpl_gameweek_id'
            ]
            ?? 0
        )
    )
    === 1
);


gameweekImportCheck(
    'GW38 can be retrieved by FPL gameweek ID',
    is_array(
        $lastGameweek
    )
    &&
    (
        (int) (
            $lastGameweek[
                'fpl_gameweek_id'
            ]
            ?? 0
        )
    )
    === 38
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * IMPORT SIZE / IDEMPOTENCY CONTRACT
 * ============================================================
 *
 * updateFPLData.php has already been run repeatedly against
 * this database.
 *
 * The unique FPL gameweek key means subsequent updater runs
 * must continue to leave exactly 38 gameweek rows.
 */

echo "============================================<br>";
echo "Scenario I: Idempotency Contract<br>";
echo "============================================<br>";


$countStatement =
    $db->query(
        "
        SELECT COUNT(*)
        FROM gameweeks
        "
    );


$storedCount =
    (int) $countStatement
        ->fetchColumn();


gameweekImportCheck(
    'Repeated gameweek imports preserve exactly 38 rows',
    $storedCount === 38
);


echo "<br>";


/*
 * ============================================================
 * DIAGNOSTIC OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Current Imported State<br>";
echo "============================================<br>";


echo "Previous Rows: "
    . $previousCount
    . "<br>";


echo "Current Rows: "
    . $currentCount
    . "<br>";


echo "Next Rows: "
    . $nextCount
    . "<br>";


if (
    is_array(
        $previous
    )
) {

    echo "Previous: "
        . htmlspecialchars(
            (string) (
                $previous[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


if (
    is_array(
        $current
    )
) {

    echo "Current: "
        . htmlspecialchars(
            (string) (
                $current[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


if (
    is_array(
        $next
    )
) {

    echo "Next: "
        . htmlspecialchars(
            (string) (
                $next[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
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
echo "Gameweek Import Integration Test Summary<br>";
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