<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Form Trend Test<br>";
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

function playerFormTrendCheck(
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


    $playerForm =
        new PlayerForm(
            $formHistory
        );


    $formTrend =
        new PlayerFormTrend(
            $playerForm
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
            'Raya could not be resolved for PlayerFormTrend testing'
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
     * Four synthetic future fixtures + real GW1 creates
     * a five-fixture trend sample.
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
            'Four future fixtures could not be resolved'
        );
    }


    /*
     * Controlled pattern:
     *
     * Real GW1:
     * known historical row
     *
     * GW2:
     * 90 mins / low performance
     *
     * GW3:
     * 90 mins / improving
     *
     * GW4:
     * 90 mins / strong
     *
     * GW5:
     * 90 mins / very strong
     *
     * This should make the short window outperform
     * the longer five-match baseline.
     */

    $minutes = [
        90,
        90,
        90,
        90
    ];


    $points = [
        2,
        4,
        8,
        10
    ];


    $bps = [
        10,
        18,
        30,
        38
    ];


    $expectedGoalsConceded = [
        2.20,
        1.50,
        0.70,
        0.30
    ];


    $cleanSheets = [
        0,
        0,
        1,
        1
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
                    $wasHome
                        ? $awayTeamId
                        : $homeTeamId,

                'was_home' =>
                    $wasHome,

                'total_points' =>
                    $points[
                        $index
                    ],

                'minutes' =>
                    $minutes[
                        $index
                    ],

                'starts' =>
                    1,

                'goals' =>
                    0,

                'assists' =>
                    0,

                'expected_goals' =>
                    0.00,

                'expected_assists' =>
                    0.02,

                'expected_goal_involvements' =>
                    0.02,

                'clean_sheets' =>
                    $cleanSheets[
                        $index
                    ],

                'goals_conceded' =>
                    0,

                'expected_goals_conceded' =>
                    $expectedGoalsConceded[
                        $index
                    ],

                'saves' =>
                    3,

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
                    $bps[
                        $index
                    ],

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
     * MODEL STRUCTURE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario A: Trend Model Structure<br>";
    echo "============================================<br>";


    $model =
        $formTrend
            ->buildModel(
                $playerId,
                'GK'
            );


    playerFormTrendCheck(
        'Player Form Trend returns an array',
        is_array(
            $model
        )
    );


    playerFormTrendCheck(
        'Trend model preserves player ID',
        (
            (int) (
                $model[
                    'player_id'
                ]
                ?? 0
            )
        )
        ===
        $playerId
    );


    playerFormTrendCheck(
        'Trend model preserves position',
        (
            $model[
                'position'
            ]
            ?? null
        )
        ===
        'GK'
    );


    playerFormTrendCheck(
        'Short Form Rating is numeric',
        is_numeric(
            $model[
                'short_form_rating'
            ]
            ?? null
        )
    );


    playerFormTrendCheck(
        'Long Form Rating is numeric',
        is_numeric(
            $model[
                'long_form_rating'
            ]
            ?? null
        )
    );


    echo "Short Form Rating: "
        . number_format(
            (float) (
                $model[
                    'short_form_rating'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Long Form Rating: "
        . number_format(
            (float) (
                $model[
                    'long_form_rating'
                ]
                ?? 0
            ),
            2
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO B
     * FORM TREND
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario B: Performance Form Trend<br>";
    echo "============================================<br>";


    playerFormTrendCheck(
        'Controlled improving history is classified as Improving',
        (
            $model[
                'form_trend'
            ]
            ?? null
        )
        ===
        'Improving'
    );


    playerFormTrendCheck(
        'Improving history produces positive Form difference',
        (
            (float) (
                $model[
                    'form_difference'
                ]
                ?? 0
            )
        )
        >= 5.0
    );


    echo "Form Difference: "
        . number_format(
            (float) (
                $model[
                    'form_difference'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Form Trend: "
        . htmlspecialchars(
            (string) (
                $model[
                    'form_trend'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO C
     * PARTICIPATION TREND
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario C: Participation Trend<br>";
    echo "============================================<br>";


    /*
     * All five fixtures contain appearances, therefore short
     * and long participation should both be 100%.
     */

    playerFormTrendCheck(
        'Full participation remains Stable',
        (
            $model[
                'participation_trend'
            ]
            ?? null
        )
        ===
        'Stable'
    );


    playerFormTrendCheck(
        'Participation difference is zero',
        abs(
            (float) (
                $model[
                    'participation_difference'
                ]
                ?? 0
            )
        )
        < 0.001
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO D
     * MINUTES TREND
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario D: Minutes Trend<br>";
    echo "============================================<br>";


    playerFormTrendCheck(
        'Consistent 90-minute participation remains Stable',
        (
            $model[
                'minutes_trend'
            ]
            ?? null
        )
        ===
        'Stable'
    );


    echo "Minutes Trend: "
        . htmlspecialchars(
            (string) (
                $model[
                    'minutes_trend'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO E
     * SAMPLE CONTRACT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario E: Trend Sample Contract<br>";
    echo "============================================<br>";


    playerFormTrendCheck(
        'Short fixture window contains three rows',
        (
            (int) (
                $model[
                    'short_fixture_sample_size'
                ]
                ?? 0
            )
        )
        === 3
    );


    playerFormTrendCheck(
        'Long fixture window contains five rows',
        (
            (int) (
                $model[
                    'long_fixture_sample_size'
                ]
                ?? 0
            )
        )
        === 5
    );


    playerFormTrendCheck(
        'Full trend sample is recognised',
        (
            $model[
                'has_full_trend_sample'
            ]
            ?? false
        )
        === true
    );


    playerFormTrendCheck(
        'Performance trend sample is recognised',
        (
            $model[
                'has_performance_trend_sample'
            ]
            ?? false
        )
        === true
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO F
     * CLASSIFICATION THRESHOLDS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario F: Classification Thresholds<br>";
    echo "============================================<br>";


    playerFormTrendCheck(
        '+5.0 is Improving',
        $formTrend
            ->classifyDifference(
                5.0,
                5.0
            )
        ===
        'Improving'
    );


    playerFormTrendCheck(
        '+4.99 remains Stable',
        $formTrend
            ->classifyDifference(
                4.99,
                5.0
            )
        ===
        'Stable'
    );


    playerFormTrendCheck(
        '-5.0 is Declining',
        $formTrend
            ->classifyDifference(
                -5.0,
                5.0
            )
        ===
        'Declining'
    );


    playerFormTrendCheck(
        '-4.99 remains Stable',
        $formTrend
            ->classifyDifference(
                -4.99,
                5.0
            )
        ===
        'Stable'
    );


    playerFormTrendCheck(
        'Missing difference returns Insufficient Data',
        $formTrend
            ->classifyDifference(
                null,
                5.0
            )
        ===
        'Insufficient Data'
    );


    echo "<br>";
    
    
    /*
     * ========================================================
     * SCENARIO G
     * DECLINING PARTICIPATION WITH STRONG PERFORMANCE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario G: Declining Participation With Strong Performance<br>";
    echo "============================================<br>";


    /*
     * Rewrite the four synthetic fixtures to represent a player
     * whose recent playing time is deteriorating:
     *
     * GW2: 90 mins
     * GW3: 60 mins
     * GW4: 30 mins
     * GW5: 0 mins
     *
     * At the same time, the player performs strongly whenever
     * they are actually on the pitch.
     *
     * This should prove that:
     *
     * performance form
     *     and
     * participation/minutes trend
     *
     * can move in different directions.
     */

    $decliningMinutes = [
        90,
        60,
        30,
        0
    ];


    $strongPoints = [
        2,
        8,
        12,
        0
    ];


    $strongBps = [
        10,
        30,
        40,
        0
    ];


    $strongExpectedGoalsConceded = [
        2.20,
        0.80,
        0.30,
        0.00
    ];


    $strongCleanSheets = [
        0,
        1,
        1,
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
                    $wasHome
                        ? $awayTeamId
                        : $homeTeamId,

                'was_home' =>
                    $wasHome,

                'total_points' =>
                    $strongPoints[
                        $index
                    ],

                'minutes' =>
                    $decliningMinutes[
                        $index
                    ],

                'starts' =>
                    $decliningMinutes[
                        $index
                    ] >= 60
                        ? 1
                        : 0,

                'goals' =>
                    0,

                'assists' =>
                    0,

                'expected_goals' =>
                    0.00,

                'expected_assists' =>
                    0.02,

                'expected_goal_involvements' =>
                    0.02,

                'clean_sheets' =>
                    $strongCleanSheets[
                        $index
                    ],

                'goals_conceded' =>
                    0,

                'expected_goals_conceded' =>
                    $strongExpectedGoalsConceded[
                        $index
                    ],

                'saves' =>
                    $decliningMinutes[
                        $index
                    ] > 0
                        ? 3
                        : 0,

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
                    $strongBps[
                        $index
                    ],

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
     * Synthetic fixture history was rewritten after earlier
     * Form calculations, so discard the previous request-level
     * history cache before rebuilding the model.
     */
    $formHistory
        ->clearCache(
            $playerId
        );


    $decliningParticipationModel =
        $formTrend
            ->buildModel(
                $playerId,
                'GK'
            );


    playerFormTrendCheck(
        'Recent participation is classified as Declining',
        (
            $decliningParticipationModel[
                'participation_trend'
            ]
            ?? null
        )
        ===
        'Declining'
    );


    playerFormTrendCheck(
        'Recent minutes are classified as Declining',
        (
            $decliningParticipationModel[
                'minutes_trend'
            ]
            ?? null
        )
        ===
        'Declining'
    );


    playerFormTrendCheck(
        'Participation difference is meaningfully negative',
        (
            (float) (
                $decliningParticipationModel[
                    'participation_difference'
                ]
                ?? 0
            )
        )
        <= -10.0
    );


    playerFormTrendCheck(
        'Minutes difference is meaningfully negative',
        (
            (float) (
                $decliningParticipationModel[
                    'minutes_difference'
                ]
                ?? 0
            )
        )
        <= -10.0
    );


    /*
     * Performance history excludes the zero-minute GW5 row.
     *
     * Therefore the player can still show strong recent
     * on-pitch form while their selection/minutes deteriorate.
     */

    playerFormTrendCheck(
        'Performance Form remains independently classifiable',
        in_array(
            (
                $decliningParticipationModel[
                    'form_trend'
                ]
                ?? null
            ),
            [
                'Improving',
                'Stable'
            ],
            true
        )
    );


    playerFormTrendCheck(
        'Performance Form is not automatically marked Declining because minutes fell',
        (
            $decliningParticipationModel[
                'form_trend'
            ]
            ?? null
        )
        !==
        'Declining'
    );
    
    
    echo "Short Holistic Form Rating: "
        . number_format(
            (float) (
                $decliningParticipationModel[
                    'short_form_rating'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Long Holistic Form Rating: "
        . number_format(
            (float) (
                $decliningParticipationModel[
                    'long_form_rating'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Short Performance Rating: "
        . number_format(
            (float) (
                $decliningParticipationModel[
                    'short_performance_rating'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Long Performance Rating: "
        . number_format(
            (float) (
                $decliningParticipationModel[
                    'long_performance_rating'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Form Trend: "
        . htmlspecialchars(
            (string) (
                $decliningParticipationModel[
                    'form_trend'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Participation Trend: "
        . htmlspecialchars(
            (string) (
                $decliningParticipationModel[
                    'participation_trend'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Minutes Trend: "
        . htmlspecialchars(
            (string) (
                $decliningParticipationModel[
                    'minutes_trend'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Form Difference: "
        . number_format(
            (float) (
                $decliningParticipationModel[
                    'form_difference'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Participation Difference: "
        . number_format(
            (float) (
                $decliningParticipationModel[
                    'participation_difference'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Minutes Difference: "
        . number_format(
            (float) (
                $decliningParticipationModel[
                    'minutes_difference'
                ]
                ?? 0
            ),
            2
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO H
     * CURRENT REAL EARLY-SEASON DATA
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario H: Current Real Early-Season Data<br>";
    echo "============================================<br>";


    /*
     * Temporarily roll back the synthetic rows before testing
     * Raya's actual stored GW1-only history.
     */

    if (
        $db->inTransaction()
    ) {

        $db->rollBack();
    }
    
    /*
     * The synthetic history has now been rolled back, so discard
     * cached synthetic windows before reading the real database
     * state again.
     */
    $formHistory
        ->clearCache(
            $playerId
        );


    $realModel =
        $formTrend
            ->buildModel(
                $playerId,
                'GK'
            );


    playerFormTrendCheck(
        'GW1-only Form Trend reports Insufficient Data',
        (
            $realModel[
                'form_trend'
            ]
            ?? null
        )
        ===
        'Insufficient Data'
    );


    playerFormTrendCheck(
        'GW1-only Participation Trend reports Insufficient Data',
        (
            $realModel[
                'participation_trend'
            ]
            ?? null
        )
        ===
        'Insufficient Data'
    );


    playerFormTrendCheck(
        'GW1-only Minutes Trend reports Insufficient Data',
        (
            $realModel[
                'minutes_trend'
            ]
            ?? null
        )
        ===
        'Insufficient Data'
    );


    playerFormTrendCheck(
        'GW1-only data does not claim a full trend sample',
        (
            $realModel[
                'has_full_trend_sample'
            ]
            ?? true
        )
        ===
        false
    );


    echo "Current Real Form Trend: "
        . htmlspecialchars(
            (string) (
                $realModel[
                    'form_trend'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
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
     * If an exception occurred before Scenario G performed
     * its rollback, ensure synthetic history is removed.
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
echo "Player Form Trend Test Summary<br>";
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