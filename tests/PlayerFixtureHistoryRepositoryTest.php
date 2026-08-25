<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Fixture History Repository Test<br>";
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

function playerFixtureHistoryRepositoryCheck(
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


    $historyRepository =
        new PlayerFixtureHistoryRepository(
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
     * Use an existing real player and real fixture so all
     * foreign-key relationships remain valid.
     *
     * The inserted history row itself is synthetic and will
     * be rolled back when the test finishes.
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
            'No player is available for fixture-history testing'
        );
    }


    $fixtureStatement =
        $db->prepare(
            "
            SELECT
                id,
                fpl_fixture_id,
                gameweek,
                home_team_id,
                away_team_id
            FROM
                fixtures
            WHERE
                home_team_id = :home_team_id
                OR away_team_id = :away_team_id
            ORDER BY
                gameweek ASC,
                kickoff_time ASC,
                id ASC
            LIMIT 1
            "
        );


    $fixtureStatement
        ->execute([

            ':home_team_id' =>
                (int) $player[
                    'team_id'
                ],

            ':away_team_id' =>
                (int) $player[
                    'team_id'
                ]
        ]);


    $fixture =
        $fixtureStatement
            ->fetch(
                PDO::FETCH_ASSOC
            );


    if ($fixture === false) {

        throw new RuntimeException(
            'No valid fixture could be found for the selected player'
        );
    }


    $fplGameweekId =
        (int) (
            $fixture[
                'gameweek'
            ]
            ?? 0
        );


    $gameweek =
        $gameweekRepository
            ->getByFplGameweekId(
                $fplGameweekId
            );


    if (
        !is_array(
            $gameweek
        )
    ) {

        throw new RuntimeException(
            'Fixture gameweek could not be resolved'
        );
    }


    $teamId =
        (int) $player[
            'team_id'
        ];


    $homeTeamId =
        (int) $fixture[
            'home_team_id'
        ];


    $awayTeamId =
        (int) $fixture[
            'away_team_id'
        ];


    $wasHome =
        $teamId === $homeTeamId;


    $opponentTeamId =
        $wasHome
            ? $awayTeamId
            : $homeTeamId;


    $history = [

        'gameweek_id' =>
            (int) $gameweek[
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

        'fixture_id' =>
            (int) $fixture[
                'id'
            ],

        'fpl_fixture_id' =>
            (int) $fixture[
                'fpl_fixture_id'
            ],

        'team_id' =>
            $teamId,

        'opponent_team_id' =>
            $opponentTeamId,

        'was_home' =>
            $wasHome,

        'total_points' =>
            8,

        'minutes' =>
            90,

        'starts' =>
            1,

        'goals' =>
            1,

        'assists' =>
            1,

        'expected_goals' =>
            0.75,

        'expected_assists' =>
            0.40,

        'expected_goal_involvements' =>
            1.15,

        'clean_sheets' =>
            1,

        'goals_conceded' =>
            0,

        'expected_goals_conceded' =>
            0.85,

        'saves' =>
            3,

        'penalties_saved' =>
            0,

        'clearances_blocks_interceptions' =>
            4,

        'recoveries' =>
            6,

        'tackles' =>
            2,

        'defensive_contribution' =>
            5,

        'own_goals' =>
            0,

        'penalties_missed' =>
            0,

        'yellow_cards' =>
            0,

        'red_cards' =>
            0,

        'bonus' =>
            3,

        'bps' =>
            36,

        'influence' =>
            42.5,

        'creativity' =>
            31.2,

        'threat' =>
            48.8,

        'ict_index' =>
            12.3,

        'price' =>
            5.5,

        'selected' =>
            123456,

        'transfers_balance' =>
            2500,

        'transfers_in' =>
            5000,

        'transfers_out' =>
            2500
    ];


    /*
     * ========================================================
     * SCENARIO A
     * INSERT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario A: Fixture History Insert<br>";
    echo "============================================<br>";


    $historyRepository
        ->upsert(
            $history
        );


    $storedHistory =
        $historyRepository
            ->getByPlayerAndFixture(
                (int) $player[
                    'id'
                ],
                (int) $fixture[
                    'id'
                ]
            );


    playerFixtureHistoryRepositoryCheck(
        'Fixture history row is inserted',
        is_array(
            $storedHistory
        )
    );


    playerFixtureHistoryRepositoryCheck(
        'Fixture history stores correct player',
        (
            (int) (
                $storedHistory[
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


    playerFixtureHistoryRepositoryCheck(
        'Fixture history stores correct fixture',
        (
            (int) (
                $storedHistory[
                    'fixture_id'
                ]
                ?? 0
            )
        )
        ===
        (int) $fixture[
            'id'
        ]
    );


    playerFixtureHistoryRepositoryCheck(
        'Fixture history stores correct gameweek',
        (
            (int) (
                $storedHistory[
                    'gameweek_id'
                ]
                ?? 0
            )
        )
        ===
        (int) $gameweek[
            'id'
        ]
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO B
     * IDENTITY / FIXTURE CONTEXT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario B: Identity and Fixture Context<br>";
    echo "============================================<br>";


    playerFixtureHistoryRepositoryCheck(
        'FPL player ID is stored correctly',
        (
            (int) (
                $storedHistory[
                    'fpl_player_id'
                ]
                ?? 0
            )
        )
        ===
        (int) $player[
            'fpl_player_id'
        ]
    );


    playerFixtureHistoryRepositoryCheck(
        'FPL fixture ID is stored correctly',
        (
            (int) (
                $storedHistory[
                    'fpl_fixture_id'
                ]
                ?? 0
            )
        )
        ===
        (int) $fixture[
            'fpl_fixture_id'
        ]
    );


    playerFixtureHistoryRepositoryCheck(
        'Team ID is stored correctly',
        (
            (int) (
                $storedHistory[
                    'team_id'
                ]
                ?? 0
            )
        )
        ===
        $teamId
    );


    playerFixtureHistoryRepositoryCheck(
        'Opponent team ID is stored correctly',
        (
            (int) (
                $storedHistory[
                    'opponent_team_id'
                ]
                ?? 0
            )
        )
        ===
        $opponentTeamId
    );


    playerFixtureHistoryRepositoryCheck(
        'Home/away state is stored correctly',
        (
            (int) (
                $storedHistory[
                    'was_home'
                ]
                ?? -1
            )
        )
        ===
        (
            $wasHome
                ? 1
                : 0
        )
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO C
     * CORE MATCH PERFORMANCE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario C: Core Match Performance<br>";
    echo "============================================<br>";


    playerFixtureHistoryRepositoryCheck(
        'Total points are stored correctly',
        (
            (int) (
                $storedHistory[
                    'total_points'
                ]
                ?? 0
            )
        )
        === 8
    );


    playerFixtureHistoryRepositoryCheck(
        'Minutes are stored correctly',
        (
            (int) (
                $storedHistory[
                    'minutes'
                ]
                ?? 0
            )
        )
        === 90
    );


    playerFixtureHistoryRepositoryCheck(
        'Starts are stored correctly',
        (
            (int) (
                $storedHistory[
                    'starts'
                ]
                ?? 0
            )
        )
        === 1
    );


    playerFixtureHistoryRepositoryCheck(
        'Goals are stored correctly',
        (
            (int) (
                $storedHistory[
                    'goals'
                ]
                ?? 0
            )
        )
        === 1
    );


    playerFixtureHistoryRepositoryCheck(
        'Assists are stored correctly',
        (
            (int) (
                $storedHistory[
                    'assists'
                ]
                ?? 0
            )
        )
        === 1
    );


    playerFixtureHistoryRepositoryCheck(
        'Clean sheets are stored correctly',
        (
            (int) (
                $storedHistory[
                    'clean_sheets'
                ]
                ?? 0
            )
        )
        === 1
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO D
     * EXPECTED / ADVANCED METRICS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario D: Advanced Metrics<br>";
    echo "============================================<br>";


    playerFixtureHistoryRepositoryCheck(
        'Expected goals are stored correctly',
        abs(
            (float) (
                $storedHistory[
                    'expected_goals'
                ]
                ?? 0
            )
            -
            0.75
        )
        < 0.001
    );


    playerFixtureHistoryRepositoryCheck(
        'Expected assists are stored correctly',
        abs(
            (float) (
                $storedHistory[
                    'expected_assists'
                ]
                ?? 0
            )
            -
            0.40
        )
        < 0.001
    );


    playerFixtureHistoryRepositoryCheck(
        'Expected goal involvements are stored correctly',
        abs(
            (float) (
                $storedHistory[
                    'expected_goal_involvements'
                ]
                ?? 0
            )
            -
            1.15
        )
        < 0.001
    );


    playerFixtureHistoryRepositoryCheck(
        'Expected goals conceded are stored correctly',
        abs(
            (float) (
                $storedHistory[
                    'expected_goals_conceded'
                ]
                ?? 0
            )
            -
            0.85
        )
        < 0.001
    );


    playerFixtureHistoryRepositoryCheck(
        'ICT Index is stored correctly',
        abs(
            (float) (
                $storedHistory[
                    'ict_index'
                ]
                ?? 0
            )
            -
            12.3
        )
        < 0.001
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO E
     * DEFENSIVE CONTRIBUTION
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario E: Defensive Contribution<br>";
    echo "============================================<br>";


    playerFixtureHistoryRepositoryCheck(
        'CBI is stored correctly',
        (
            (int) (
                $storedHistory[
                    'clearances_blocks_interceptions'
                ]
                ?? 0
            )
        )
        === 4
    );


    playerFixtureHistoryRepositoryCheck(
        'Recoveries are stored correctly',
        (
            (int) (
                $storedHistory[
                    'recoveries'
                ]
                ?? 0
            )
        )
        === 6
    );


    playerFixtureHistoryRepositoryCheck(
        'Tackles are stored correctly',
        (
            (int) (
                $storedHistory[
                    'tackles'
                ]
                ?? 0
            )
        )
        === 2
    );


    playerFixtureHistoryRepositoryCheck(
        'Defensive contribution is stored correctly',
        (
            (int) (
                $storedHistory[
                    'defensive_contribution'
                ]
                ?? 0
            )
        )
        === 5
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO F
     * MARKET STATE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario F: Market State<br>";
    echo "============================================<br>";


    playerFixtureHistoryRepositoryCheck(
        'Price is stored correctly',
        abs(
            (float) (
                $storedHistory[
                    'price'
                ]
                ?? 0
            )
            -
            5.5
        )
        < 0.001
    );


    playerFixtureHistoryRepositoryCheck(
        'Selected count is stored correctly',
        (
            (int) (
                $storedHistory[
                    'selected'
                ]
                ?? 0
            )
        )
        === 123456
    );


    playerFixtureHistoryRepositoryCheck(
        'Transfers balance is stored correctly',
        (
            (int) (
                $storedHistory[
                    'transfers_balance'
                ]
                ?? 0
            )
        )
        === 2500
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO G
     * COLLECTION LOOKUPS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario G: Collection Lookups<br>";
    echo "============================================<br>";


    $playerHistory =
        $historyRepository
            ->getByPlayerId(
                (int) $player[
                    'id'
                ]
            );


    $fixtureHistory =
        $historyRepository
            ->getByFixtureId(
                (int) $fixture[
                    'id'
                ]
            );


    $gameweekHistory =
        $historyRepository
            ->getByGameweekId(
                (int) $gameweek[
                    'id'
                ]
            );


    playerFixtureHistoryRepositoryCheck(
        'Player history lookup contains synthetic row',
        !empty(
            $playerHistory
        )
    );


    playerFixtureHistoryRepositoryCheck(
        'Fixture history lookup contains synthetic row',
        !empty(
            $fixtureHistory
        )
    );


    playerFixtureHistoryRepositoryCheck(
        'Gameweek history lookup contains synthetic row',
        !empty(
            $gameweekHistory
        )
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO H
     * IDEMPOTENT UPSERT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario H: Idempotent Upsert<br>";
    echo "============================================<br>";


    $updatedHistory =
        $history;


    $updatedHistory[
        'total_points'
    ] =
        12;


    $updatedHistory[
        'minutes'
    ] =
        88;


    $updatedHistory[
        'goals'
    ] =
        2;


    $updatedHistory[
        'price'
    ] =
        5.6;


    $historyRepository
        ->upsert(
            $updatedHistory
        );


    $updatedStoredHistory =
        $historyRepository
            ->getByPlayerAndFixture(
                (int) $player[
                    'id'
                ],
                (int) $fixture[
                    'id'
                ]
            );


    $duplicateStatement =
        $db->prepare(
            "
            SELECT COUNT(*)
            FROM player_fixture_history
            WHERE
                player_id = :player_id
                AND fixture_id = :fixture_id
            "
        );


    $duplicateStatement
        ->execute([

            ':player_id' =>
                (int) $player[
                    'id'
                ],

            ':fixture_id' =>
                (int) $fixture[
                    'id'
                ]
        ]);


    $duplicateCount =
        (int) $duplicateStatement
            ->fetchColumn();


    playerFixtureHistoryRepositoryCheck(
        'Repeated upsert does not create duplicate history row',
        $duplicateCount === 1
    );


    playerFixtureHistoryRepositoryCheck(
        'Repeated upsert updates total points',
        (
            (int) (
                $updatedStoredHistory[
                    'total_points'
                ]
                ?? 0
            )
        )
        === 12
    );


    playerFixtureHistoryRepositoryCheck(
        'Repeated upsert updates goals',
        (
            (int) (
                $updatedStoredHistory[
                    'goals'
                ]
                ?? 0
            )
        )
        === 2
    );


    playerFixtureHistoryRepositoryCheck(
        'Repeated upsert updates price',
        abs(
            (float) (
                $updatedStoredHistory[
                    'price'
                ]
                ?? 0
            )
            -
            5.6
        )
        < 0.001
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO I
     * NULLABLE ANALYTICAL FIELDS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario I: Nullable Analytical Fields<br>";
    echo "============================================<br>";


    $nullableHistory =
        $history;


    $nullableHistory[
        'expected_goals'
    ] =
        null;


    $nullableHistory[
        'expected_assists'
    ] =
        null;


    $nullableHistory[
        'expected_goal_involvements'
    ] =
        null;


    $nullableHistory[
        'expected_goals_conceded'
    ] =
        null;


    $nullableHistory[
        'influence'
    ] =
        null;


    $nullableHistory[
        'creativity'
    ] =
        null;


    $nullableHistory[
        'threat'
    ] =
        null;


    $nullableHistory[
        'ict_index'
    ] =
        null;


    $nullableHistory[
        'selected'
    ] =
        null;


    $historyRepository
        ->upsert(
            $nullableHistory
        );


    $storedNullableHistory =
        $historyRepository
            ->getByPlayerAndFixture(
                (int) $player[
                    'id'
                ],
                (int) $fixture[
                    'id'
                ]
            );


    playerFixtureHistoryRepositoryCheck(
        'Nullable expected goals remains null',
        (
            $storedNullableHistory[
                'expected_goals'
            ]
            ?? null
        )
        === null
    );


    playerFixtureHistoryRepositoryCheck(
        'Nullable ICT Index remains null',
        (
            $storedNullableHistory[
                'ict_index'
            ]
            ?? null
        )
        === null
    );


    playerFixtureHistoryRepositoryCheck(
        'Nullable selected count remains null',
        (
            $storedNullableHistory[
                'selected'
            ]
            ?? null
        )
        === null
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO J
     * INVALID IDENTITY PROTECTION
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario J: Invalid Identity Protection<br>";
    echo "============================================<br>";


    $invalidRejected =
        false;


    try {

        $invalidHistory =
            $history;


        $invalidHistory[
            'player_id'
        ] =
            0;


        $historyRepository
            ->upsert(
                $invalidHistory
            );

    } catch (
        InvalidArgumentException $exception
    ) {

        $invalidRejected =
            true;
    }


    playerFixtureHistoryRepositoryCheck(
        'Invalid required identity is rejected',
        $invalidRejected
    );


    playerFixtureHistoryRepositoryCheck(
        'Invalid player lookup returns null',
        $historyRepository
            ->getByPlayerAndFixture(
                0,
                (int) $fixture[
                    'id'
                ]
            )
        === null
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO K
     * REPOSITORY COUNT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario K: Repository Count<br>";
    echo "============================================<br>";


    playerFixtureHistoryRepositoryCheck(
        'Repository count includes inserted synthetic row',
        $historyRepository
            ->count()
        >= 1
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
     * All synthetic fixture-history data is removed.
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
echo "Player Fixture History Repository Test Summary<br>";
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