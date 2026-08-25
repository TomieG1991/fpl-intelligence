<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Form History Test<br>";
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

function playerFormHistoryCheck(
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


    $formHistory =
        new PlayerFormHistory(
            $historyRepository
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
 * CONTROLLED HISTORY
 * ============================================================
 */

try {

    /*
     * Use Raya because his genuine GW1 row is already present.
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
            'Raya could not be resolved for PlayerFormHistory testing'
        );
    }


    $playerId =
        (int) (
            $player[
                'id'
            ]
            ?? 0
        );


    $fplPlayerId =
        (int) (
            $player[
                'fpl_player_id'
            ]
            ?? 0
        );


    $teamId =
        (int) (
            $player[
                'team_id'
            ]
            ?? 0
        );


    /*
     * Select four future fixtures.
     *
     * Combined with the genuine GW1 history row, this gives
     * a five-fixture controlled sample.
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
            LIMIT 4
            "
        );


    $fixtureStatement
        ->execute([

            ':home_team_id' =>
                $teamId,

            ':away_team_id' =>
                $teamId
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
        !== 4
    ) {

        throw new RuntimeException(
            'Four future fixtures could not be resolved for PlayerFormHistory testing'
        );
    }


    /*
     * Synthetic sequence after genuine GW1:
     *
     * GW2: 90
     * GW3: 0
     * GW4: 45
     * GW5: 0
     *
     * Combined expected last-five fixture minutes:
     *
     * GW1 real Raya row = 90
     * + 90
     * + 0
     * + 45
     * + 0
     *
     * Total = 225
     *
     * Appearance sample:
     *
     * 90
     * 90
     * 45
     *
     * Total = 225
     */

    $minutesByFixture = [
        90,
        0,
        45,
        0
    ];


    $pointsByFixture = [
        7,
        0,
        4,
        0
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
            $teamId;


        $historicalTeamId =
            $wasHome
                ? $homeTeamId
                : $awayTeamId;


        $opponentTeamId =
            $wasHome
                ? $awayTeamId
                : $homeTeamId;


        $minutes =
            $minutesByFixture[
                $index
            ];


        $historyRepository
            ->upsert([

                'gameweek_id' =>
                    (int) (
                        $fixture[
                            'gameweek_id'
                        ]
                        ?? 0
                    ),

                'player_id' =>
                    $playerId,

                'fpl_player_id' =>
                    $fplPlayerId,

                'fixture_id' =>
                    (int) (
                        $fixture[
                            'id'
                        ]
                        ?? 0
                    ),

                'fpl_fixture_id' =>
                    (int) (
                        $fixture[
                            'fpl_fixture_id'
                        ]
                        ?? 0
                    ),

                'team_id' =>
                    $historicalTeamId,

                'opponent_team_id' =>
                    $opponentTeamId,

                'was_home' =>
                    $wasHome,

                'total_points' =>
                    $pointsByFixture[
                        $index
                    ],

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
     * DEFAULT HISTORY
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario A: Default Form History<br>";
    echo "============================================<br>";


    $history =
        $formHistory
            ->buildDefaultHistory(
                $playerId
            );


    playerFormHistoryCheck(
        'Default history returns an array',
        is_array(
            $history
        )
    );


    playerFormHistoryCheck(
        'Default fixture limit is five',
        (
            (int) (
                $history[
                    'fixture_limit'
                ]
                ?? 0
            )
        )
        === 5
    );


    playerFormHistoryCheck(
        'Default appearance limit is five',
        (
            (int) (
                $history[
                    'appearance_limit'
                ]
                ?? 0
            )
        )
        === 5
    );


    playerFormHistoryCheck(
        'Default fixture sample contains five rows',
        (
            (int) (
                $history[
                    'fixture_sample_size'
                ]
                ?? 0
            )
        )
        === 5
    );


    playerFormHistoryCheck(
        'Default appearance sample contains three rows',
        (
            (int) (
                $history[
                    'appearance_sample_size'
                ]
                ?? 0
            )
        )
        === 3
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO B
     * ZERO-MINUTE EVIDENCE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario B: Zero-Minute Evidence<br>";
    echo "============================================<br>";


    playerFormHistoryCheck(
        'Two zero-minute rows are identified',
        (
            (int) (
                $history[
                    'zero_minute_rows'
                ]
                ?? -1
            )
        )
        === 2
    );


    $fixtureWindow =
        $history[
            'fixture_window'
        ]
        ?? [];


    $appearanceWindow =
        $history[
            'appearance_window'
        ]
        ?? [];


    $fixtureContainsZero =
        false;


    foreach (
        $fixtureWindow
        as $row
    ) {

        if (
            (
                (int) (
                    $row[
                        'minutes'
                    ]
                    ?? -1
                )
            )
            === 0
        ) {

            $fixtureContainsZero =
                true;

            break;
        }
    }


    playerFormHistoryCheck(
        'Fixture window preserves zero-minute rows',
        $fixtureContainsZero
    );


    $appearanceContainsZero =
        false;


    foreach (
        $appearanceWindow
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

            $appearanceContainsZero =
                true;

            break;
        }
    }


    playerFormHistoryCheck(
        'Appearance window excludes zero-minute rows',
        !$appearanceContainsZero
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO C
     * MINUTES SUMMARY
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario C: Minutes Summary<br>";
    echo "============================================<br>";


    playerFormHistoryCheck(
        'Fixture minutes are summed correctly',
        (
            (int) (
                $history[
                    'fixture_minutes'
                ]
                ?? 0
            )
        )
        === 225
    );


    playerFormHistoryCheck(
        'Appearance minutes are summed correctly',
        (
            (int) (
                $history[
                    'appearance_minutes'
                ]
                ?? 0
            )
        )
        === 225
    );


    echo "Fixture Minutes: "
        . (int) (
            $history[
                'fixture_minutes'
            ]
            ?? 0
        )
        . "<br>";


    echo "Appearance Minutes: "
        . (int) (
            $history[
                'appearance_minutes'
            ]
            ?? 0
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO D
     * PARTICIPATION RATE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario D: Participation Rate<br>";
    echo "============================================<br>";


    /*
     * Three of the five recent team fixtures contain
     * an appearance.
     *
     * 3 / 5 = 60%
     */

    playerFormHistoryCheck(
        'Participation rate is calculated correctly',
        abs(
            (
                (float) (
                    $history[
                        'participation_rate'
                    ]
                    ?? 0
                )
            )
            -
            60.0
        )
        < 0.001
    );


    echo "Participation Rate: "
        . number_format(
            (float) (
                $history[
                    'participation_rate'
                ]
                ?? 0
            ),
            2
        )
        . "%<br><br>";


    /*
     * ========================================================
     * SCENARIO E
     * HISTORY FLAGS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario E: History Availability<br>";
    echo "============================================<br>";


    playerFormHistoryCheck(
        'Fixture-history availability flag is true',
        (
            $history[
                'has_fixture_history'
            ]
            ?? false
        )
        === true
    );


    playerFormHistoryCheck(
        'Appearance-history availability flag is true',
        (
            $history[
                'has_appearance_history'
            ]
            ?? false
        )
        === true
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO F
     * SHORT HISTORY
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario F: Short History Window<br>";
    echo "============================================<br>";


    $shortHistory =
        $formHistory
            ->buildShortHistory(
                $playerId
            );


    playerFormHistoryCheck(
        'Short fixture history uses limit three',
        (
            (int) (
                $shortHistory[
                    'fixture_limit'
                ]
                ?? 0
            )
        )
        === 3
    );


    playerFormHistoryCheck(
        'Short fixture window contains three rows',
        count(
            $shortHistory[
                'fixture_window'
            ]
            ?? []
        )
        === 3
    );


    playerFormHistoryCheck(
        'Short appearance window contains three rows',
        count(
            $shortHistory[
                'appearance_window'
            ]
            ?? []
        )
        === 3
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO G
     * CUSTOM LIMITS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario G: Custom Limits<br>";
    echo "============================================<br>";


    $customHistory =
        $formHistory
            ->buildHistory(
                $playerId,
                4,
                2
            );


    playerFormHistoryCheck(
        'Custom fixture window respects requested limit',
        count(
            $customHistory[
                'fixture_window'
            ]
            ?? []
        )
        === 4
    );


    playerFormHistoryCheck(
        'Custom appearance window respects requested limit',
        count(
            $customHistory[
                'appearance_window'
            ]
            ?? []
        )
        === 2
    );


    $cappedHistory =
        $formHistory
            ->buildHistory(
                $playerId,
                100,
                100
            );


    playerFormHistoryCheck(
        'Fixture limit is capped at twenty',
        (
            (int) (
                $cappedHistory[
                    'fixture_limit'
                ]
                ?? 0
            )
        )
        === 20
    );


    playerFormHistoryCheck(
        'Appearance limit is capped at twenty',
        (
            (int) (
                $cappedHistory[
                    'appearance_limit'
                ]
                ?? 0
            )
        )
        === 20
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO H
     * INVALID PLAYER
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario H: Invalid Player<br>";
    echo "============================================<br>";


    $invalidHistory =
        $formHistory
            ->buildDefaultHistory(
                0
            );


    playerFormHistoryCheck(
        'Invalid player returns empty fixture window',
        (
            $invalidHistory[
                'fixture_window'
            ]
            ?? null
        )
        === []
    );


    playerFormHistoryCheck(
        'Invalid player returns empty appearance window',
        (
            $invalidHistory[
                'appearance_window'
            ]
            ?? null
        )
        === []
    );


    playerFormHistoryCheck(
        'Invalid player reports no fixture history',
        (
            $invalidHistory[
                'has_fixture_history'
            ]
            ?? true
        )
        === false
    );


    playerFormHistoryCheck(
        'Invalid player reports no appearance history',
        (
            $invalidHistory[
                'has_appearance_history'
            ]
            ?? true
        )
        === false
    );


    playerFormHistoryCheck(
        'Invalid player participation rate remains unavailable',
        (
            $invalidHistory[
                'participation_rate'
            ]
            ?? null
        )
        === null
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO I
     * ZERO LIMITS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario I: Zero Limits<br>";
    echo "============================================<br>";


    $zeroHistory =
        $formHistory
            ->buildHistory(
                $playerId,
                0,
                0
            );


    playerFormHistoryCheck(
        'Zero fixture limit returns empty fixture window',
        (
            $zeroHistory[
                'fixture_window'
            ]
            ?? null
        )
        === []
    );


    playerFormHistoryCheck(
        'Zero appearance limit returns empty appearance window',
        (
            $zeroHistory[
                'appearance_window'
            ]
            ?? null
        )
        === []
    );


    playerFormHistoryCheck(
        'Zero-limit sample sizes remain zero',
        (
            (int) (
                $zeroHistory[
                    'fixture_sample_size'
                ]
                ?? -1
            )
        )
        === 0
        &&
        (
            (int) (
                $zeroHistory[
                    'appearance_sample_size'
                ]
                ?? -1
            )
        )
        === 0
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO J
     * CHRONOLOGICAL WINDOW
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario J: Chronological Window<br>";
    echo "============================================<br>";


    $gameweekNumbers =
        [];


    foreach (
        $fixtureWindow
        as $row
    ) {

        $gameweekStatement =
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


        $gameweekStatement
            ->execute([

                ':gameweek_id' =>
                    (int) (
                        $row[
                            'gameweek_id'
                        ]
                        ?? 0
                    )
            ]);


        $gameweekNumbers[] =
            (int) $gameweekStatement
                ->fetchColumn();
    }


    $sortedGameweeks =
        $gameweekNumbers;


    sort(
        $sortedGameweeks
    );


    playerFormHistoryCheck(
        'Fixture window is chronological',
        $gameweekNumbers
        ===
        $sortedGameweeks
    );


    echo "Fixture Window Gameweeks: "
        . implode(
            ', ',
            $gameweekNumbers
        )
        . "<br><br>";


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
echo "Player Form History Test Summary<br>";
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