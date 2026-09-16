<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Fixture History Bulk Query Boundary Test<br>";
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

function bulkQueryBoundaryCheck(
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
 * TEST REPOSITORY
 * ============================================================
 *
 * The population-level method must not delegate back to either
 * single-player retrieval method.
 *
 * Throwing here gives us a direct architectural boundary:
 *
 *     getRecentForPlayerIds()
 *
 * must perform population-level retrieval itself.
 * ============================================================
 */

class BulkQueryBoundaryHistoryRepository
    extends PlayerFixtureHistoryRepository
{
    public int $fixtureSinglePlayerCalls =
        0;

    public int $appearanceSinglePlayerCalls =
        0;


    public function getRecentByPlayerId(
        int $playerId,
        int $limit = 5
    ): array {

        $this->fixtureSinglePlayerCalls++;


        throw new RuntimeException(
            'Bulk retrieval delegated to getRecentByPlayerId()'
        );
    }


    public function getRecentAppearancesByPlayerId(
        int $playerId,
        int $limit = 5
    ): array {

        $this->appearanceSinglePlayerCalls++;


        throw new RuntimeException(
            'Bulk retrieval delegated to getRecentAppearancesByPlayerId()'
        );
    }
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


    $playerRepository =
        new PlayerRepository(
            $db
        );


    $players =
        $playerRepository
            ->getAll();


    $playerIds =
        [];


    foreach (
        $players
        as $player
    ) {

        $playerId =
            (int) (
                $player[
                    'id'
                ]
                ?? 0
            );


        if ($playerId <= 0) {

            continue;
        }


        $playerIds[] =
            $playerId;


        /*
         * A small real population is sufficient for this
         * architectural boundary.
         */
        if (
            count(
                $playerIds
            )
            >= 3
        ) {

            break;
        }
    }


    if (empty($playerIds)) {

        throw new RuntimeException(
            'No valid players are available for bulk query-boundary testing'
        );
    }


    $historyRepository =
        new BulkQueryBoundaryHistoryRepository(
            $db
        );

} catch (
    Throwable $exception
) {

    echo "SETUP FAILED ❌<br><br>";

    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );

    exit;
}


/*
 * ============================================================
 * A. POPULATION RETRIEVAL BOUNDARY
 * ============================================================
 */

echo "============================================<br>";
echo "A. Population Retrieval Boundary<br>";
echo "============================================<br>";


$bulkHistory =
    null;

$bulkException =
    null;


try {

    $bulkHistory =
        $historyRepository
            ->getRecentForPlayerIds(
                $playerIds,
                5,
                5
            );

} catch (
    Throwable $exception
) {

    $bulkException =
        $exception;
}


bulkQueryBoundaryCheck(
    'Bulk retrieval completes without delegating to a single-player history method.',
    $bulkException === null
);


bulkQueryBoundaryCheck(
    'Bulk retrieval makes zero fixture-history single-player calls.',
    $historyRepository
        ->fixtureSinglePlayerCalls
    === 0
);


bulkQueryBoundaryCheck(
    'Bulk retrieval makes zero appearance-history single-player calls.',
    $historyRepository
        ->appearanceSinglePlayerCalls
    === 0
);


echo "<br>";


/*
 * ============================================================
 * B. RESULT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "B. Result Contract<br>";
echo "============================================<br>";


bulkQueryBoundaryCheck(
    'Independent bulk retrieval exposes fixture-history pool.',
    is_array(
        $bulkHistory
    )
    &&
    array_key_exists(
        'fixture_history',
        $bulkHistory
    )
);


bulkQueryBoundaryCheck(
    'Independent bulk retrieval exposes appearance-history pool.',
    is_array(
        $bulkHistory
    )
    &&
    array_key_exists(
        'appearance_history',
        $bulkHistory
    )
);


$allPlayerKeysPresent =
    is_array(
        $bulkHistory
    );


if ($allPlayerKeysPresent) {

    foreach (
        $playerIds
        as $playerId
    ) {

        if (
            !array_key_exists(
                $playerId,
                $bulkHistory[
                    'fixture_history'
                ]
                ?? []
            )
            ||
            !array_key_exists(
                $playerId,
                $bulkHistory[
                    'appearance_history'
                ]
                ?? []
            )
        ) {

            $allPlayerKeysPresent =
                false;

            break;
        }
    }
}


bulkQueryBoundaryCheck(
    'Independent bulk retrieval preserves every requested player identity.',
    $allPlayerKeysPresent
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Summary<br>";
echo "============================================<br>";

echo "Players requested: "
    . count(
        $playerIds
    )
    . "<br>";

echo "Assertions passed: "
    . $passed
    . "<br>";

echo "Assertions failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TEST FAILED ❌";
}