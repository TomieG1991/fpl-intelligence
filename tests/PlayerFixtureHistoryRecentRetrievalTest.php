<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Fixture History Recent Retrieval Test<br>";
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

function recentHistoryCheck(
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


    $historyRepository =
        new PlayerFixtureHistoryRepository(
            $db
        );


    /*
     * All synthetic history added by this test is temporary.
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
 * BUILD CONTROLLED HISTORY
 * ============================================================
 */

try {

    /*
     * Use Raya because he already has a genuine GW1 history
     * record and belongs to Arsenal.
     */

    $playerStatement =
        $db->prepare(
            "
            SELECT
                id,
                fpl_player_id,
                team_id,
                web_name
            FROM
                players
            WHERE
                fpl_player_id = :fpl_player_id
            LIMIT 1
            "
        );


    $playerStatement
        ->execute([

            ':fpl_player_id' =>
                1
        ]);


    $player =
        $playerStatement
            ->fetch(
                PDO::FETCH_ASSOC
            );


    if (
        !is_array(
            $player
        )
    ) {

        throw new RuntimeException(
            'Raya could not be resolved for recent-history testing'
        );
    }


    $playerId =
        (int) $player[
            'id'
        ];


    $fplPlayerId =
        (int) $player[
            'fpl_player_id'
        ];


    $currentTeamId =
        (int) $player[
            'team_id'
        ];


    /*
     * Select three real future Arsenal fixtures.
     *
     * These provide valid foreign-key identities while the
     * synthetic player-history rows remain inside the test
     * transaction and are rolled back afterwards.
     */

    $fixtureStatement =
        $db->prepare(
            "
            SELECT
                f.id,
                f.fpl_fixture_id,
                f.gameweek,
                f.home_team_id,
                f.away_team_id,
                f.kickoff_time,
                g.id AS gameweek_id
            FROM
                fixtures f
            INNER JOIN
                gameweeks g
                    ON g.fpl_gameweek_id = f.gameweek
            WHERE
                (
                    f.home_team_id = :home_team_id
                    OR
                    f.away_team_id = :away_team_id
                )
                AND
                f.gameweek > 1
            ORDER BY
                f.gameweek ASC,
                f.kickoff_time ASC,
                f.id ASC
            LIMIT 3
            "
        );


    $fixtureStatement
        ->execute([

            ':home_team_id' =>
                $currentTeamId,

            ':away_team_id' =>
                $currentTeamId
        ]);


    $fixtures =
        $fixtureStatement
            ->fetchAll(
                PDO::FETCH_ASSOC
            );


    if (
        count(
            $fixtures
        )
        !== 3
    ) {

        throw new RuntimeException(
            'Three future fixtures could not be resolved for recent-history testing'
        );
    }


    /*
     * Build three controlled rows:
     *
     * Fixture 1: 90-minute appearance
     * Fixture 2: zero minutes
     * Fixture 3: 30-minute appearance
     *
     * Combined with Raya's genuine GW1 history this gives us
     * enough evidence to test fixture history versus actual
     * appearance history.
     */

    $syntheticMinutes = [
        90,
        0,
        30
    ];


    $syntheticPoints = [
        8,
        0,
        3
    ];


    foreach (
        $fixtures
        as $index => $fixture
    ) {

        $homeTeamId =
            (int) (
                $fixture[
                    'home_team_id'
                ]
                ?? 0
            );


        $awayTeamId =
            (int) (
                $fixture[
                    'away_team_id'
                ]
                ?? 0
            );


        $wasHome =
            $homeTeamId
            ===
            $currentTeamId;


        $teamId =
            $wasHome
                ? $homeTeamId
                : $awayTeamId;


        $opponentTeamId =
            $wasHome
                ? $awayTeamId
                : $homeTeamId;


        $minutes =
            $syntheticMinutes[
                $index
            ];


        $points =
            $syntheticPoints[
                $index
            ];


        $historyRepository
            ->upsert([

                'gameweek_id' =>
                    (int) $fixture[
                        'gameweek_id'
                    ],

                'player_id' =>
                    $playerId,

                'fpl_player_id' =>
                    $fplPlayerId,

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
                    $points,

                'minutes' =>
                    $minutes,

                'starts' =>
                    $minutes >= 60
                        ? 1
                        : 0,

                'goals' =>
                    0,

                'assists' =>
                    0,

                'expected_goals' =>
                    0.10,

                'expected_assists' =>
                    0.05,

                'expected_goal_involvements' =>
                    0.15,

                'clean_sheets' =>
                    0,

                'goals_conceded' =>
                    0,

                'expected_goals_conceded' =>
                    1.00,

                'saves' =>
                    2,

                'penalties_saved' =>
                    0,

                'clearances_blocks_interceptions' =>
                    0,

                'recoveries' =>
                    0,

                'tackles' =>
                    0,

                'defensive_contribution' =>
                    0,

                'own_goals' =>
                    0,

                'penalties_missed' =>
                    0,

                'yellow_cards' =>
                    0,

                'red_cards' =>
                    0,

                'bonus' =>
                    0,

                'bps' =>
                    20,

                'influence' =>
                    10.0,

                'creativity' =>
                    5.0,

                'threat' =>
                    0.0,

                'ict_index' =>
                    1.5,

                'price' =>
                    6.0,

                'selected' =>
                    100000,

                'transfers_balance' =>
                    0,

                'transfers_in' =>
                    0,

                'transfers_out' =>
                    0
            ]);
    }


    /*
     * ========================================================
     * SCENARIO A
     * RECENT FIXTURE HISTORY
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario A: Recent Fixture History<br>";
    echo "============================================<br>";


    $recentThree =
        $historyRepository
            ->getRecentByPlayerId(
                $playerId,
                3
            );


    recentHistoryCheck(
        'Recent fixture history returns requested limit',
        count(
            $recentThree
        )
        === 3
    );


    $recentThreeGameweeks =
        array_map(
            static function (
                array $row
            ) use (
                $db
            ): int {

                $statement =
                    $db->prepare(
                        "
                        SELECT
                            fpl_gameweek_id
                        FROM
                            gameweeks
                        WHERE
                            id = :gameweek_id
                        LIMIT 1
                        "
                    );


                $statement
                    ->execute([

                        ':gameweek_id' =>
                            (int) (
                                $row[
                                    'gameweek_id'
                                ]
                                ?? 0
                            )
                    ]);


                return (int) $statement
                    ->fetchColumn();
            },
            $recentThree
        );


    recentHistoryCheck(
        'Recent fixture history is returned chronologically',
        $recentThreeGameweeks
        ===
        [
            (int) $fixtures[0]['gameweek'],
            (int) $fixtures[1]['gameweek'],
            (int) $fixtures[2]['gameweek']
        ]
    );


    recentHistoryCheck(
        'Recent fixture history includes zero-minute row',
        (
            (int) (
                $recentThree[
                    1
                ]['minutes']
                ?? -1
            )
        )
        === 0
    );


    echo "Recent Fixture Minutes: "
        . implode(
            ', ',
            array_map(
                static fn (
                    array $row
                ): int =>
                    (int) (
                        $row[
                            'minutes'
                        ]
                        ?? 0
                    ),
                $recentThree
            )
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO B
     * RECENT APPEARANCES
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario B: Recent Appearances<br>";
    echo "============================================<br>";


    $recentAppearances =
        $historyRepository
            ->getRecentAppearancesByPlayerId(
                $playerId,
                3
            );


    recentHistoryCheck(
        'Recent appearances return requested available sample',
        count(
            $recentAppearances
        )
        === 3
    );


    $allAppearancesHaveMinutes =
        true;


    foreach (
        $recentAppearances
        as $appearance
    ) {

        if (
            (
                (int) (
                    $appearance[
                        'minutes'
                    ]
                    ?? 0
                )
            )
            <= 0
        ) {

            $allAppearancesHaveMinutes =
                false;

            break;
        }
    }


    recentHistoryCheck(
        'Recent appearances exclude zero-minute rows',
        $allAppearancesHaveMinutes
    );


    /*
     * Raya's real GW1 appearance should be required to fill
     * the three-appearance window because one of the synthetic
     * later fixtures contains zero minutes.
     */

    $appearanceGameweeks =
        [];


    foreach (
        $recentAppearances
        as $appearance
    ) {

        $gameweekLookup =
            $db->prepare(
                "
                SELECT
                    fpl_gameweek_id
                FROM
                    gameweeks
                WHERE
                    id = :gameweek_id
                LIMIT 1
                "
            );


        $gameweekLookup
            ->execute([

                ':gameweek_id' =>
                    (int) (
                        $appearance[
                            'gameweek_id'
                        ]
                        ?? 0
                    )
            ]);


        $appearanceGameweeks[] =
            (int) $gameweekLookup
                ->fetchColumn();
    }


    recentHistoryCheck(
        'Recent appearances can reach further back than fixture window',
        in_array(
            1,
            $appearanceGameweeks,
            true
        )
    );


    recentHistoryCheck(
        'Recent appearances remain chronological',
        $appearanceGameweeks
        ===
        array_values(
            array_unique(
                array_merge(
                    [
                        1
                    ],
                    [
                        (int) $fixtures[0]['gameweek'],
                        (int) $fixtures[2]['gameweek']
                    ]
                )
            )
        )
    );


    echo "Recent Appearance Gameweeks: "
        . implode(
            ', ',
            $appearanceGameweeks
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO C
     * LIMIT CONTRACT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario C: Limit Contract<br>";
    echo "============================================<br>";


    $recentOne =
        $historyRepository
            ->getRecentByPlayerId(
                $playerId,
                1
            );


    recentHistoryCheck(
        'Limit of one returns one fixture row',
        count(
            $recentOne
        )
        === 1
    );


    recentHistoryCheck(
        'Limit of one returns newest fixture',
        (
            (int) (
                $recentOne[
                    0
                ]['fixture_id']
                ?? 0
            )
        )
        ===
        (int) $fixtures[
            2
        ]['id']
    );


    $largeWindow =
        $historyRepository
            ->getRecentByPlayerId(
                $playerId,
                100
            );


    recentHistoryCheck(
        'Oversized history limit is safely capped',
        count(
            $largeWindow
        )
        <= 20
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO D
     * INVALID INPUT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario D: Invalid Input<br>";
    echo "============================================<br>";


    recentHistoryCheck(
        'Invalid player ID returns empty fixture history',
        $historyRepository
            ->getRecentByPlayerId(
                0,
                5
            )
        === []
    );


    recentHistoryCheck(
        'Invalid player ID returns empty appearance history',
        $historyRepository
            ->getRecentAppearancesByPlayerId(
                0,
                5
            )
        === []
    );


    recentHistoryCheck(
        'Zero fixture-history limit returns empty result',
        $historyRepository
            ->getRecentByPlayerId(
                $playerId,
                0
            )
        === []
    );


    recentHistoryCheck(
        'Negative appearance limit returns empty result',
        $historyRepository
            ->getRecentAppearancesByPlayerId(
                $playerId,
                -1
            )
        === []
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO E
     * EXISTING FULL HISTORY CONTRACT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario E: Existing History Compatibility<br>";
    echo "============================================<br>";


    $completeHistory =
        $historyRepository
            ->getByPlayerId(
                $playerId
            );


    recentHistoryCheck(
        'Existing full-history method remains available',
        count(
            $completeHistory
        )
        >= 4
    );


    recentHistoryCheck(
        'Recent fixture history does not replace full history',
        count(
            $completeHistory
        )
        >
        count(
            $recentThree
        )
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
     * Future synthetic history must never remain in the real
     * historical database after this test.
     */

    if (
        isset(
            $db
        )
        &&
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
echo "Player Fixture History Recent Retrieval Test Summary<br>";
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