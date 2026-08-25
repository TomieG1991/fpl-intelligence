<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Repository Test<br>";
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

function playerSnapshotRepositoryCheck(
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


    $snapshotRepository =
        new PlayerGameweekSnapshotRepository(
            $db
        );


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
 * TEST DATA
 * ============================================================
 */

try {

    /*
     * Use an existing real player/team so foreign keys
     * remain valid, but all inserted history is rolled back.
     */

    $playerStatement =
        $db->query(
            "
            SELECT
                id,
                fpl_player_id,
                team_id,
                position
            FROM
                players
            ORDER BY
                id ASC
            LIMIT 1
            "
        );


    $player =
        $playerStatement
            ->fetch(
                PDO::FETCH_ASSOC
            );


    if ($player === false) {

        throw new RuntimeException(
            'No player is available for snapshot testing'
        );
    }


    $gameweekRepository
        ->upsert([

            'id' =>
                9001,

            'name' =>
                'Snapshot Test Gameweek',

            'deadline_time' =>
                '2026-08-01T10:00:00Z',

            'finished' =>
                false,

            'data_checked' =>
                false,

            'is_previous' =>
                false,

            'is_current' =>
                false,

            'is_next' =>
                false
        ]);


    $testGameweek =
        $gameweekRepository
            ->getByFplGameweekId(
                9001
            );


    if (
        !is_array(
            $testGameweek
        )
    ) {

        throw new RuntimeException(
            'Synthetic gameweek could not be created'
        );
    }


    $snapshot = [

        'gameweek_id' =>
            (int) $testGameweek[
                'id'
            ],

        'player_id' =>
            (int) $player[
                'id'
            ],

        'fpl_player_id' =>
            (int) $player[
                'fpl_player_id'
            ],

        'team_id' =>
            (int) $player[
                'team_id'
            ],

        'position' =>
            $player[
                'position'
            ],

        'price' =>
            7.5,

        'selected_by_percent' =>
            12.34,

        'chance_of_playing' =>
            75,

        'status' =>
            'd',

        'news' =>
            'Synthetic test news',

        'minutes' =>
            180,

        'goals' =>
            2,

        'assists' =>
            1,

        'clean_sheets' =>
            1,

        'bonus' =>
            4,

        'bps' =>
            50,

        'ict_index' =>
            22.5,

        'expected_goals' =>
            1.25,

        'expected_assists' =>
            0.55,

        'expected_goal_involvements' =>
            1.80
    ];


    /*
     * ========================================================
     * SCENARIO A
     * INSERT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario A: Snapshot Insert<br>";
    echo "============================================<br>";


    $snapshotRepository
        ->upsert(
            $snapshot
        );


    $storedSnapshot =
        $snapshotRepository
            ->getByPlayerAndGameweek(
                (int) $player[
                    'id'
                ],
                (int) $testGameweek[
                    'id'
                ]
            );


    playerSnapshotRepositoryCheck(
        'Snapshot is inserted',
        is_array(
            $storedSnapshot
        )
    );


    playerSnapshotRepositoryCheck(
        'Snapshot stores correct player',
        (
            (int) (
                $storedSnapshot[
                    'player_id'
                ]
                ?? 0
            )
        )
        ===
        (int) $player[
            'id'
        ]
    );


    playerSnapshotRepositoryCheck(
        'Snapshot stores correct gameweek',
        (
            (int) (
                $storedSnapshot[
                    'gameweek_id'
                ]
                ?? 0
            )
        )
        ===
        (int) $testGameweek[
            'id'
        ]
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO B
     * MARKET / AVAILABILITY STATE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario B: Market and Availability<br>";
    echo "============================================<br>";


    playerSnapshotRepositoryCheck(
        'Price is stored correctly',
        abs(
            (float) (
                $storedSnapshot[
                    'price'
                ]
                ?? 0
            )
            -
            7.5
        )
        < 0.001
    );


    playerSnapshotRepositoryCheck(
        'Ownership is stored correctly',
        abs(
            (float) (
                $storedSnapshot[
                    'selected_by_percent'
                ]
                ?? 0
            )
            -
            12.34
        )
        < 0.001
    );


    playerSnapshotRepositoryCheck(
        'Chance of playing is stored correctly',
        (
            (int) (
                $storedSnapshot[
                    'chance_of_playing'
                ]
                ?? 0
            )
        )
        === 75
    );


    playerSnapshotRepositoryCheck(
        'Status is stored correctly',
        (
            $storedSnapshot[
                'status'
            ]
            ?? null
        )
        === 'd'
    );


    playerSnapshotRepositoryCheck(
        'News is stored correctly',
        (
            $storedSnapshot[
                'news'
            ]
            ?? null
        )
        ===
        'Synthetic test news'
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO C
     * CUMULATIVE PERFORMANCE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario C: Cumulative Performance<br>";
    echo "============================================<br>";


    playerSnapshotRepositoryCheck(
        'Minutes are stored correctly',
        (
            (int) (
                $storedSnapshot[
                    'minutes'
                ]
                ?? 0
            )
        )
        === 180
    );


    playerSnapshotRepositoryCheck(
        'Goals are stored correctly',
        (
            (int) (
                $storedSnapshot[
                    'goals'
                ]
                ?? 0
            )
        )
        === 2
    );


    playerSnapshotRepositoryCheck(
        'Assists are stored correctly',
        (
            (int) (
                $storedSnapshot[
                    'assists'
                ]
                ?? 0
            )
        )
        === 1
    );


    playerSnapshotRepositoryCheck(
        'Expected goals are stored correctly',
        abs(
            (float) (
                $storedSnapshot[
                    'expected_goals'
                ]
                ?? 0
            )
            -
            1.25
        )
        < 0.001
    );


    playerSnapshotRepositoryCheck(
        'Expected assists are stored correctly',
        abs(
            (float) (
                $storedSnapshot[
                    'expected_assists'
                ]
                ?? 0
            )
            -
            0.55
        )
        < 0.001
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO D
     * IDEMPOTENT UPSERT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario D: Idempotent Upsert<br>";
    echo "============================================<br>";


    $updatedSnapshot =
        $snapshot;


    $updatedSnapshot[
        'price'
    ] =
        7.6;


    $updatedSnapshot[
        'minutes'
    ] =
        270;


    $updatedSnapshot[
        'goals'
    ] =
        3;


    $snapshotRepository
        ->upsert(
            $updatedSnapshot
        );


    $updatedStoredSnapshot =
        $snapshotRepository
            ->getByPlayerAndGameweek(
                (int) $player[
                    'id'
                ],
                (int) $testGameweek[
                    'id'
                ]
            );


    $duplicateStatement =
        $db->prepare(
            "
            SELECT COUNT(*)
            FROM player_gameweek_snapshots
            WHERE
                player_id = :player_id
                AND gameweek_id = :gameweek_id
            "
        );


    $duplicateStatement
        ->execute([

            ':player_id' =>
                (int) $player[
                    'id'
                ],

            ':gameweek_id' =>
                (int) $testGameweek[
                    'id'
                ]
        ]);


    $duplicateCount =
        (int) $duplicateStatement
            ->fetchColumn();


    playerSnapshotRepositoryCheck(
        'Repeated upsert does not create duplicate snapshot',
        $duplicateCount === 1
    );


    playerSnapshotRepositoryCheck(
        'Repeated upsert updates price',
        abs(
            (float) (
                $updatedStoredSnapshot[
                    'price'
                ]
                ?? 0
            )
            -
            7.6
        )
        < 0.001
    );


    playerSnapshotRepositoryCheck(
        'Repeated upsert updates minutes',
        (
            (int) (
                $updatedStoredSnapshot[
                    'minutes'
                ]
                ?? 0
            )
        )
        === 270
    );


    playerSnapshotRepositoryCheck(
        'Repeated upsert updates goals',
        (
            (int) (
                $updatedStoredSnapshot[
                    'goals'
                ]
                ?? 0
            )
        )
        === 3
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO E
     * PLAYER / GAMEWEEK COLLECTION LOOKUPS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario E: Collection Lookups<br>";
    echo "============================================<br>";


    $gameweekSnapshots =
        $snapshotRepository
            ->getByGameweekId(
                (int) $testGameweek[
                    'id'
                ]
            );


    $playerSnapshots =
        $snapshotRepository
            ->getByPlayerId(
                (int) $player[
                    'id'
                ]
            );


    playerSnapshotRepositoryCheck(
        'Gameweek lookup returns synthetic snapshot',
        count(
            $gameweekSnapshots
        )
        >= 1
    );


    $playerSnapshotFound =
        false;


    foreach (
        $playerSnapshots
        as $candidate
    ) {

        if (
            (
                (int) (
                    $candidate[
                        'gameweek_id'
                    ]
                    ?? 0
                )
            )
            ===
            (int) $testGameweek[
                'id'
            ]
        ) {

            $playerSnapshotFound =
                true;

            break;
        }
    }


    playerSnapshotRepositoryCheck(
        'Player lookup returns synthetic snapshot',
        $playerSnapshotFound
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO F
     * NULLABLE STATE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario F: Nullable State<br>";
    echo "============================================<br>";


    $nullSnapshot =
        $snapshot;


    $nullSnapshot[
        'selected_by_percent'
    ] =
        null;


    $nullSnapshot[
        'chance_of_playing'
    ] =
        null;


    $nullSnapshot[
        'ict_index'
    ] =
        null;


    $nullSnapshot[
        'expected_goals'
    ] =
        null;


    $nullSnapshot[
        'expected_assists'
    ] =
        null;


    $snapshotRepository
        ->upsert(
            $nullSnapshot
        );


    $storedNullSnapshot =
        $snapshotRepository
            ->getByPlayerAndGameweek(
                (int) $player[
                    'id'
                ],
                (int) $testGameweek[
                    'id'
                ]
            );


    playerSnapshotRepositoryCheck(
        'Nullable ownership remains null',
        (
            $storedNullSnapshot[
                'selected_by_percent'
            ]
            ?? null
        )
        === null
    );


    playerSnapshotRepositoryCheck(
        'Nullable chance of playing remains null',
        (
            $storedNullSnapshot[
                'chance_of_playing'
            ]
            ?? null
        )
        === null
    );


    playerSnapshotRepositoryCheck(
        'Nullable expected goals remains null',
        (
            $storedNullSnapshot[
                'expected_goals'
            ]
            ?? null
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
echo "Player Gameweek Snapshot Repository Test Summary<br>";
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