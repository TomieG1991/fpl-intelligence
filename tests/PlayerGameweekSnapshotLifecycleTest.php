<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Lifecycle Test<br>";
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

function snapshotLifecycleCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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
 * DATABASE / REPOSITORIES
 * ============================================================
 */

$database =
    new Database();


$connection =
    $database
        ->getConnection();


$gameweekRepository =
    new GameweekRepository(
        $connection
    );


$snapshotRepository =
    new PlayerGameweekSnapshotRepository(
        $connection
    );


/*
 * ============================================================
 * SCENARIO A
 * LIVE UPDATER ISOLATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Live Updater Isolation<br>";
echo "============================================<br>";


$updateFile =
    __DIR__
    . '/../cron/updateFPLData.php';


$updateSource =
    is_file(
        $updateFile
    )
        ? file_get_contents(
            $updateFile
        )
        : false;


snapshotLifecycleCheck(
    'Live FPL updater exists',
    is_string(
        $updateSource
    )
);


if (
    !is_string(
        $updateSource
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


snapshotLifecycleCheck(
    'Live updater does not construct PlayerGameweekSnapshotRepository',
    !str_contains(
        $updateSource,
        'new PlayerGameweekSnapshotRepository'
    )
);


snapshotLifecycleCheck(
    'Live updater does not write player gameweek snapshots',
    !str_contains(
        $updateSource,
        'playerGameweekSnapshotRepository'
    )
);


snapshotLifecycleCheck(
    'Live updater no longer tracks snapshot import counts',
    !str_contains(
        $updateSource,
        'playerSnapshotsImported'
    )
);


snapshotLifecycleCheck(
    'Live updater still updates current players',
    str_contains(
        $updateSource,
        'INSERT INTO players'
    )
    &&
    str_contains(
        $updateSource,
        'ON DUPLICATE KEY UPDATE'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * GAMEWEEK LIFECYCLE FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Gameweek Lifecycle Foundation<br>";
echo "============================================<br>";


$currentGameweek =
    $gameweekRepository
        ->getCurrent();


$previousGameweek =
    $gameweekRepository
        ->getPrevious();


snapshotLifecycleCheck(
    'Gameweek repository exposes current-gameweek state',
    method_exists(
        $gameweekRepository,
        'getCurrent'
    )
);


snapshotLifecycleCheck(
    'Gameweek repository exposes previous-gameweek state',
    method_exists(
        $gameweekRepository,
        'getPrevious'
    )
);


snapshotLifecycleCheck(
    'Stored gameweek records expose finished state',
    count(
        array_filter(
            $gameweekRepository
                ->getAll(),
            static function (
                array $gameweek
            ): bool {

                return array_key_exists(
                    'finished',
                    $gameweek
                );
            }
        )
    )
    ===
    count(
        $gameweekRepository
            ->getAll()
    )
);


if (
    is_array(
        $currentGameweek
    )
) {

    echo "Current Gameweek: GW"
        . (
            $currentGameweek[
                'fpl_gameweek_id'
            ]
            ?? '—'
        )
        . " — Finished "
        . (
            !empty(
                $currentGameweek[
                    'finished'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        )
        . "<br>";
}


if (
    is_array(
        $previousGameweek
    )
) {

    echo "Previous Gameweek: GW"
        . (
            $previousGameweek[
                'fpl_gameweek_id'
            ]
            ?? '—'
        )
        . " — Finished "
        . (
            !empty(
                $previousGameweek[
                    'finished'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * EXISTING SNAPSHOT FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Existing Snapshot Foundation<br>";
echo "============================================<br>";


$snapshotSummaryStatement =
    $connection
        ->query(
            "
                SELECT
                    COUNT(*) AS row_count,
                    COUNT(DISTINCT gameweek_id) AS gameweek_count,
                    COUNT(DISTINCT player_id) AS player_count
                FROM player_gameweek_snapshots
            "
        );


$snapshotSummary =
    $snapshotSummaryStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$snapshotRows =
    (int) (
        $snapshotSummary[
            'row_count'
        ]
        ?? 0
    );


$snapshotGameweeks =
    (int) (
        $snapshotSummary[
            'gameweek_count'
        ]
        ?? 0
    );


$snapshotPlayers =
    (int) (
        $snapshotSummary[
            'player_count'
        ]
        ?? 0
    );


snapshotLifecycleCheck(
    'Historical snapshot storage exists',
    $snapshotRows > 0
);


snapshotLifecycleCheck(
    'Historical snapshots retain gameweek identity',
    $snapshotGameweeks > 0
);


snapshotLifecycleCheck(
    'Historical snapshots retain player identity',
    $snapshotPlayers > 0
);


echo "Snapshot Rows: "
    . number_format(
        $snapshotRows
    )
    . "<br>";


echo "Snapshot Gameweeks: "
    . number_format(
        $snapshotGameweeks
    )
    . "<br>";


echo "Snapshot Players: "
    . number_format(
        $snapshotPlayers
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * IMMUTABLE WRITE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Immutable Snapshot Write Contract<br>";
echo "============================================<br>";


$immutableMethodExists =
    method_exists(
        $snapshotRepository,
        'insertIfAbsent'
    );


snapshotLifecycleCheck(
    'Snapshot repository exposes immutable insertIfAbsent contract',
    $immutableMethodExists
);


if (
    !$immutableMethodExists
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "PlayerGameweekSnapshotRepository::insertIfAbsent()<br>";
    echo "<br>";

    echo "The new method should:<br>";
    echo "- insert a snapshot when player/gameweek does not exist<br>";
    echo "- leave an existing snapshot unchanged<br>";
    echo "- return true when inserted<br>";
    echo "- return false when the snapshot already exists<br><br>";


    echo "============================================<br>";
    echo "Player Gameweek Snapshot Lifecycle Test Summary<br>";
    echo "============================================<br>";


    echo "Passed: "
        . $passed
        . "<br>";


    echo "Failed: "
        . $failed
        . "<br><br>";


    echo "RESULT: TESTS FAILED ❌";

    exit;
}


/*
 * ============================================================
 * SCENARIO E
 * EXISTING SNAPSHOT IMMUTABILITY
 * ============================================================
 *
 * Use an existing real snapshot inside a transaction.
 *
 * The transaction is always rolled back so this test cannot
 * permanently modify historical data.
 */

echo "============================================<br>";
echo "Scenario E: Existing Snapshot Immutability<br>";
echo "============================================<br>";


$existingSnapshotStatement =
    $connection
        ->query(
            "
                SELECT *
                FROM player_gameweek_snapshots
                ORDER BY
                    gameweek_id ASC,
                    player_id ASC
                LIMIT 1
            "
        );


$existingSnapshot =
    $existingSnapshotStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


snapshotLifecycleCheck(
    'A real existing snapshot resolves for immutability testing',
    is_array(
        $existingSnapshot
    )
);


if (
    !is_array(
        $existingSnapshot
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$originalPrice =
    $existingSnapshot[
        'price'
    ];


$originalOwnership =
    $existingSnapshot[
        'selected_by_percent'
    ];


$replacementSnapshot =
    [

        'gameweek_id' =>
            (int) $existingSnapshot[
                'gameweek_id'
            ],

        'player_id' =>
            (int) $existingSnapshot[
                'player_id'
            ],

        'fpl_player_id' =>
            (int) $existingSnapshot[
                'fpl_player_id'
            ],

        'team_id' =>
            (int) $existingSnapshot[
                'team_id'
            ],

        'position' =>
            $existingSnapshot[
                'position'
            ],

        /*
         * Deliberately different values.
         *
         * These MUST NOT replace the stored snapshot.
         */
        'price' =>
            is_numeric(
                $originalPrice
            )
                ? (float) $originalPrice
                    + 1.0
                : 99.0,

        'selected_by_percent' =>
            is_numeric(
                $originalOwnership
            )
                ? min(
                    100.0,
                    (float) $originalOwnership
                    + 10.0
                )
                : 99.0,

        'chance_of_playing' =>
            $existingSnapshot[
                'chance_of_playing'
            ],

        'status' =>
            $existingSnapshot[
                'status'
            ],

        'news' =>
            $existingSnapshot[
                'news'
            ],

        'minutes' =>
            (int) $existingSnapshot[
                'minutes'
            ],

        'goals' =>
            (int) $existingSnapshot[
                'goals'
            ],

        'assists' =>
            (int) $existingSnapshot[
                'assists'
            ],

        'clean_sheets' =>
            (int) $existingSnapshot[
                'clean_sheets'
            ],

        'bonus' =>
            (int) $existingSnapshot[
                'bonus'
            ],

        'bps' =>
            (int) $existingSnapshot[
                'bps'
            ],

        'ict_index' =>
            $existingSnapshot[
                'ict_index'
            ],

        'expected_goals' =>
            $existingSnapshot[
                'expected_goals'
            ],

        'expected_assists' =>
            $existingSnapshot[
                'expected_assists'
            ],

        'expected_goal_involvements' =>
            $existingSnapshot[
                'expected_goal_involvements'
            ]
    ];


$connection
    ->beginTransaction();


try {

    $inserted =
        $snapshotRepository
            ->insertIfAbsent(
                $replacementSnapshot
            );


    $afterAttempt =
        $snapshotRepository
            ->getByPlayerAndGameweek(
                (int) $existingSnapshot[
                    'player_id'
                ],
                (int) $existingSnapshot[
                    'gameweek_id'
                ]
            );


    snapshotLifecycleCheck(
        'Duplicate immutable snapshot insert reports not inserted',
        $inserted === false
    );


    snapshotLifecycleCheck(
        'Duplicate immutable insert preserves historical price',
        is_array(
            $afterAttempt
        )
        &&
        (
            $afterAttempt[
                'price'
            ]
            ?? null
        )
        ==
        $originalPrice
    );


    snapshotLifecycleCheck(
        'Duplicate immutable insert preserves historical ownership',
        is_array(
            $afterAttempt
        )
        &&
        (
            $afterAttempt[
                'selected_by_percent'
            ]
            ?? null
        )
        ==
        $originalOwnership
    );


} finally {

    if (
        $connection
            ->inTransaction()
    ) {

        $connection
            ->rollBack();
    }
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * SNAPSHOT LIFECYCLE DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Snapshot Lifecycle Diagnostic<br>";
echo "============================================<br><br>";


echo "Live players table:<br>";
echo "Refreshable current FPL state<br><br>";


echo "Historical snapshot table:<br>";
echo "Immutable player/gameweek state once captured<br><br>";


echo "Live updater snapshot writes: "
    . (
        !str_contains(
            $updateSource,
            'PlayerGameweekSnapshotRepository'
        )
            ? 'Disabled'
            : 'Still Present'
    )
    . "<br>";


echo "Immutable repository method: "
    . (
        $immutableMethodExists
            ? 'Available'
            : 'Missing'
    )
    . "<br>";


echo "Historical Snapshot Rows: "
    . number_format(
        $snapshotRows
    )
    . "<br>";


echo "Historical Snapshot Gameweeks: "
    . number_format(
        $snapshotGameweeks
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Gameweek Snapshot Lifecycle Test Summary<br>";
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