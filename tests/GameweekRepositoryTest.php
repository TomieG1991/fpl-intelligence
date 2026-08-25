<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Repository Test<br>";
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

function gameweekRepositoryCheck(
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


    /*
     * All synthetic database changes made by this test
     * are rolled back at the end.
     */
    $db->beginTransaction();

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
 * SYNTHETIC GAMEWEEKS
 * ============================================================
 *
 * Deliberately use IDs far outside the real FPL season range
 * so the test can never clash with normal GW1-GW38 records.
 */

$previousGameweek = [

    'id' =>
        9001,

    'name' =>
        'Test Previous Gameweek',

    'deadline_time' =>
        '2026-08-01T10:00:00Z',

    'finished' =>
        true,

    'data_checked' =>
        true,

    'is_previous' =>
        true,

    'is_current' =>
        false,

    'is_next' =>
        false
];


$currentGameweek = [

    'id' =>
        9002,

    'name' =>
        'Test Current Gameweek',

    'deadline_time' =>
        '2026-08-08T10:00:00Z',

    'finished' =>
        false,

    'data_checked' =>
        false,

    'is_previous' =>
        false,

    'is_current' =>
        true,

    'is_next' =>
        false
];


$nextGameweek = [

    'id' =>
        9003,

    'name' =>
        'Test Next Gameweek',

    'deadline_time' =>
        '2026-08-15T10:00:00Z',

    'finished' =>
        false,

    'data_checked' =>
        false,

    'is_previous' =>
        false,

    'is_current' =>
        false,

    'is_next' =>
        true
];


try {

    /*
     * --------------------------------------------------------
     * Protect current-state lookup tests from any real
     * gameweek rows that may already exist in the database.
     *
     * These changes occur inside the test transaction and
     * are rolled back afterwards.
     * --------------------------------------------------------
     */

    $db->exec(
        "
        UPDATE gameweeks
        SET
            is_previous = 0,
            is_current = 0,
            is_next = 0
        "
    );


    /*
     * ========================================================
     * SCENARIO A
     * INSERT / UPSERT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario A: Gameweek Upsert<br>";
    echo "============================================<br>";


    $repository
        ->upsert(
            $previousGameweek
        );


    $repository
        ->upsert(
            $currentGameweek
        );


    $repository
        ->upsert(
            $nextGameweek
        );


    $storedPrevious =
        $repository
            ->getByFplGameweekId(
                9001
            );


    $storedCurrent =
        $repository
            ->getByFplGameweekId(
                9002
            );


    $storedNext =
        $repository
            ->getByFplGameweekId(
                9003
            );


    gameweekRepositoryCheck(
        'Previous gameweek is inserted',
        is_array(
            $storedPrevious
        )
    );


    gameweekRepositoryCheck(
        'Current gameweek is inserted',
        is_array(
            $storedCurrent
        )
    );


    gameweekRepositoryCheck(
        'Next gameweek is inserted',
        is_array(
            $storedNext
        )
    );


    gameweekRepositoryCheck(
        'Stored FPL gameweek ID is correct',
        (
            (int) (
                $storedCurrent[
                    'fpl_gameweek_id'
                ]
                ?? 0
            )
        )
        === 9002
    );


    gameweekRepositoryCheck(
        'Stored gameweek name is correct',
        (
            $storedCurrent[
                'name'
            ]
            ?? null
        )
        ===
        'Test Current Gameweek'
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO B
     * DEADLINE CONVERSION
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario B: Deadline Conversion<br>";
    echo "============================================<br>";


    gameweekRepositoryCheck(
        'ISO deadline is converted to database DATETIME',
        (
            $storedCurrent[
                'deadline_time'
            ]
            ?? null
        )
        ===
        '2026-08-08 10:00:00'
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO C
     * GAMEWEEK STATE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario C: Gameweek State<br>";
    echo "============================================<br>";


    gameweekRepositoryCheck(
        'Finished state is stored',
        (
            (int) (
                $storedPrevious[
                    'finished'
                ]
                ?? 0
            )
        )
        === 1
    );


    gameweekRepositoryCheck(
        'Data checked state is stored',
        (
            (int) (
                $storedPrevious[
                    'data_checked'
                ]
                ?? 0
            )
        )
        === 1
    );


    gameweekRepositoryCheck(
        'Unfinished state is stored',
        (
            (int) (
                $storedCurrent[
                    'finished'
                ]
                ?? 1
            )
        )
        === 0
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO D
     * CURRENT / PREVIOUS / NEXT LOOKUPS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario D: State Lookups<br>";
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


    gameweekRepositoryCheck(
        'Previous gameweek lookup returns correct event',
        (
            (int) (
                $previous[
                    'fpl_gameweek_id'
                ]
                ?? 0
            )
        )
        === 9001
    );


    gameweekRepositoryCheck(
        'Current gameweek lookup returns correct event',
        (
            (int) (
                $current[
                    'fpl_gameweek_id'
                ]
                ?? 0
            )
        )
        === 9002
    );


    gameweekRepositoryCheck(
        'Next gameweek lookup returns correct event',
        (
            (int) (
                $next[
                    'fpl_gameweek_id'
                ]
                ?? 0
            )
        )
        === 9003
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO E
     * LOCAL ID LOOKUP
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario E: Local ID Lookup<br>";
    echo "============================================<br>";


    $currentLocalId =
        (int) (
            $storedCurrent[
                'id'
            ]
            ?? 0
        );


    $byLocalId =
        $repository
            ->getById(
                $currentLocalId
            );


    gameweekRepositoryCheck(
        'Gameweek can be retrieved by local database ID',
        is_array(
            $byLocalId
        )
        &&
        (
            (int) (
                $byLocalId[
                    'fpl_gameweek_id'
                ]
                ?? 0
            )
        )
        === 9002
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO F
     * IDEMPOTENT UPSERT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario F: Idempotent Upsert<br>";
    echo "============================================<br>";


    $updatedCurrent =
        $currentGameweek;


    $updatedCurrent[
        'name'
    ] =
        'Updated Current Gameweek';


    $updatedCurrent[
        'finished'
    ] =
        true;


    $updatedCurrent[
        'data_checked'
    ] =
        true;


    $repository
        ->upsert(
            $updatedCurrent
        );


    $updatedStoredCurrent =
        $repository
            ->getByFplGameweekId(
                9002
            );


    $duplicateCountStatement =
        $db->prepare(
            "
            SELECT COUNT(*)
            FROM gameweeks
            WHERE fpl_gameweek_id = :fpl_gameweek_id
            "
        );


    $duplicateCountStatement
        ->execute([

            ':fpl_gameweek_id' =>
                9002
        ]);


    $duplicateCount =
        (int) $duplicateCountStatement
            ->fetchColumn();


    gameweekRepositoryCheck(
        'Repeated upsert does not create duplicate gameweek',
        $duplicateCount === 1
    );


    gameweekRepositoryCheck(
        'Repeated upsert updates existing name',
        (
            $updatedStoredCurrent[
                'name'
            ]
            ?? null
        )
        ===
        'Updated Current Gameweek'
    );


    gameweekRepositoryCheck(
        'Repeated upsert updates finished state',
        (
            (int) (
                $updatedStoredCurrent[
                    'finished'
                ]
                ?? 0
            )
        )
        === 1
    );


    gameweekRepositoryCheck(
        'Repeated upsert updates data checked state',
        (
            (int) (
                $updatedStoredCurrent[
                    'data_checked'
                ]
                ?? 0
            )
        )
        === 1
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO G
     * GET ALL
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario G: Get All<br>";
    echo "============================================<br>";


    $allGameweeks =
        $repository
            ->getAll();


    $syntheticIds =
        [];


    foreach (
        $allGameweeks
        as $gameweek
    ) {

        $fplGameweekId =
            (int) (
                $gameweek[
                    'fpl_gameweek_id'
                ]
                ?? 0
            );


        if (
            in_array(
                $fplGameweekId,
                [
                    9001,
                    9002,
                    9003
                ],
                true
            )
        ) {

            $syntheticIds[] =
                $fplGameweekId;
        }
    }


    gameweekRepositoryCheck(
        'Get all includes all synthetic gameweeks',
        $syntheticIds
        ===
        [
            9001,
            9002,
            9003
        ]
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO H
     * MISSING RECORDS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario H: Missing Records<br>";
    echo "============================================<br>";


    gameweekRepositoryCheck(
        'Unknown FPL gameweek returns null',
        $repository
            ->getByFplGameweekId(
                999999
            )
        === null
    );


    gameweekRepositoryCheck(
        'Unknown local gameweek returns null',
        $repository
            ->getById(
                999999
            )
        === null
    );


    echo "<br>";


} catch (
    Throwable $exception
) {

    echo "TEST ERROR ❌<br>";


    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );


    $failed++;

} finally {

    /*
     * ========================================================
     * CLEANUP
     * ========================================================
     *
     * No synthetic gameweek data survives this test.
     */

    if (
        $db instanceof PDO
        &&
        $db->inTransaction()
    ) {

        $db->rollBack();
    }
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Gameweek Repository Test Summary<br>";
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