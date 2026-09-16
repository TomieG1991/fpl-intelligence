<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Fixture History Bulk Recent Retrieval Test<br>";
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

function bulkRecentHistoryCheck(
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


    $playerRepository =
        new PlayerRepository(
            $db
        );


    $historyRepository =
        new PlayerFixtureHistoryRepository(
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
    }


    if (empty($playerIds)) {

        throw new RuntimeException(
            'No valid players are available for bulk recent-history testing'
        );
    }

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
 * AUTHORITATIVE PER-PLAYER WINDOWS
 * ============================================================
 *
 * The existing repository methods remain the source of truth.
 *
 * We deliberately build the expected result through those
 * methods before exercising the new bulk boundary.
 * ============================================================
 */

$expectedFixtureHistory =
    [];

$expectedAppearanceHistory =
    [];


foreach (
    $playerIds
    as $playerId
) {

    $expectedFixtureHistory[
        $playerId
    ] =
        $historyRepository
            ->getRecentByPlayerId(
                $playerId,
                5
            );


    $expectedAppearanceHistory[
        $playerId
    ] =
        $historyRepository
            ->getRecentAppearancesByPlayerId(
                $playerId,
                5
            );
}


/*
 * ============================================================
 * BULK RECENT HISTORY
 * ============================================================
 *
 * This method intentionally does not exist yet.
 *
 * The target contract is:
 *
 * [
 *     'fixture_history' => [
 *         player_id => chronological rows
 *     ],
 *
 *     'appearance_history' => [
 *         player_id => chronological rows
 *     ]
 * ]
 *
 * Every requested valid player must have a key, including
 * players whose recent history is empty.
 * ============================================================
 */

$bulkHistory =
    $historyRepository
        ->getRecentForPlayerIds(
            $playerIds,
            5,
            5
        );


$bulkFixtureHistory =
    $bulkHistory[
        'fixture_history'
    ]
    ?? [];


$bulkAppearanceHistory =
    $bulkHistory[
        'appearance_history'
    ]
    ?? [];


/*
 * ============================================================
 * A. RESULT STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Result Structure<br>";
echo "============================================<br>";


bulkRecentHistoryCheck(
    'Bulk result exposes fixture-history pool.',
    array_key_exists(
        'fixture_history',
        $bulkHistory
    )
);


bulkRecentHistoryCheck(
    'Bulk result exposes appearance-history pool.',
    array_key_exists(
        'appearance_history',
        $bulkHistory
    )
);


bulkRecentHistoryCheck(
    'Bulk fixture pool contains one entry for every requested player.',
    count(
        $bulkFixtureHistory
    )
    ===
    count(
        $playerIds
    )
);


bulkRecentHistoryCheck(
    'Bulk appearance pool contains one entry for every requested player.',
    count(
        $bulkAppearanceHistory
    )
    ===
    count(
        $playerIds
    )
);


echo "<br>";


/*
 * ============================================================
 * B. COMPLETE FIXTURE-HISTORY EQUIVALENCE
 * ============================================================
 */

echo "============================================<br>";
echo "B. Fixture History Equivalence<br>";
echo "============================================<br>";


$fixtureKeysComplete =
    true;

$fixtureRowsEquivalent =
    true;


foreach (
    $playerIds
    as $playerId
) {

    if (
        !array_key_exists(
            $playerId,
            $bulkFixtureHistory
        )
    ) {

        $fixtureKeysComplete =
            false;

        $fixtureRowsEquivalent =
            false;

        break;
    }


    if (
        $bulkFixtureHistory[
            $playerId
        ]
        !==
        $expectedFixtureHistory[
            $playerId
        ]
    ) {

        $fixtureRowsEquivalent =
            false;

        break;
    }
}


bulkRecentHistoryCheck(
    'Bulk fixture pool preserves every requested player identity, including empty histories.',
    $fixtureKeysComplete
);


bulkRecentHistoryCheck(
    'Every bulk five-fixture window exactly matches authoritative per-player retrieval.',
    $fixtureRowsEquivalent
);


echo "<br>";


/*
 * ============================================================
 * C. COMPLETE APPEARANCE-HISTORY EQUIVALENCE
 * ============================================================
 */

echo "============================================<br>";
echo "C. Appearance History Equivalence<br>";
echo "============================================<br>";


$appearanceKeysComplete =
    true;

$appearanceRowsEquivalent =
    true;


foreach (
    $playerIds
    as $playerId
) {

    if (
        !array_key_exists(
            $playerId,
            $bulkAppearanceHistory
        )
    ) {

        $appearanceKeysComplete =
            false;

        $appearanceRowsEquivalent =
            false;

        break;
    }


    if (
        $bulkAppearanceHistory[
            $playerId
        ]
        !==
        $expectedAppearanceHistory[
            $playerId
        ]
    ) {

        $appearanceRowsEquivalent =
            false;

        break;
    }
}


bulkRecentHistoryCheck(
    'Bulk appearance pool preserves every requested player identity, including empty histories.',
    $appearanceKeysComplete
);


bulkRecentHistoryCheck(
    'Every bulk five-appearance window exactly matches authoritative per-player retrieval.',
    $appearanceRowsEquivalent
);


echo "<br>";


/*
 * ============================================================
 * D. WINDOW CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "D. Window Contract<br>";
echo "============================================<br>";


$fixtureLimitsPreserved =
    true;

$appearanceLimitsPreserved =
    true;

$appearancesExcludeZeroMinutes =
    true;


foreach (
    $playerIds
    as $playerId
) {

    $fixtureRows =
        $bulkFixtureHistory[
            $playerId
        ]
        ?? [];


    $appearanceRows =
        $bulkAppearanceHistory[
            $playerId
        ]
        ?? [];


    if (
        count(
            $fixtureRows
        )
        > 5
    ) {

        $fixtureLimitsPreserved =
            false;
    }


    if (
        count(
            $appearanceRows
        )
        > 5
    ) {

        $appearanceLimitsPreserved =
            false;
    }


    foreach (
        $appearanceRows
        as $row
    ) {

        if (
            (
                (int) (
                    $row[
                        'minutes'
                    ]
                    ?? 0
                )
            )
            <= 0
        ) {

            $appearancesExcludeZeroMinutes =
                false;

            break 2;
        }
    }
}


bulkRecentHistoryCheck(
    'Bulk fixture histories respect the five-row maximum window.',
    $fixtureLimitsPreserved
);


bulkRecentHistoryCheck(
    'Bulk appearance histories respect the five-row maximum window.',
    $appearanceLimitsPreserved
);


bulkRecentHistoryCheck(
    'Bulk appearance histories preserve the minutes-greater-than-zero contract.',
    $appearancesExcludeZeroMinutes
);


echo "<br>";


/*
 * ============================================================
 * E. INVALID / EMPTY INPUT
 * ============================================================
 */

echo "============================================<br>";
echo "E. Invalid and Empty Input<br>";
echo "============================================<br>";


$emptyBulkHistory =
    $historyRepository
        ->getRecentForPlayerIds(
            [],
            5,
            5
        );


bulkRecentHistoryCheck(
    'Empty player population returns predictable empty fixture pool.',
    (
        $emptyBulkHistory[
            'fixture_history'
        ]
        ?? null
    )
    === []
);


bulkRecentHistoryCheck(
    'Empty player population returns predictable empty appearance pool.',
    (
        $emptyBulkHistory[
            'appearance_history'
        ]
        ?? null
    )
    === []
);


$invalidBulkHistory =
    $historyRepository
        ->getRecentForPlayerIds(
            [
                0,
                -1
            ],
            5,
            5
        );


bulkRecentHistoryCheck(
    'Non-positive player identities do not create fixture-history entries.',
    (
        $invalidBulkHistory[
            'fixture_history'
        ]
        ?? null
    )
    === []
);


bulkRecentHistoryCheck(
    'Non-positive player identities do not create appearance-history entries.',
    (
        $invalidBulkHistory[
            'appearance_history'
        ]
        ?? null
    )
    === []
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

echo "Players compared: "
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