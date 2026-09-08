<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * PLAYER PROJECTION BACKTEST INTEGRATION TEST
 * ============================================================
 *
 * v0.35.0 — Recommendation History & Backtesting
 *
 * Proves the real persistence-to-backtest path:
 *
 * recommendation_snapshots
 *     -> RecommendationSnapshotRepository
 *     -> historical player_projections
 *
 * player_fixture_history
 *     -> PlayerFixtureHistoryRepository
 *     -> PlayerGameweekOutcomeService
 *
 * both evidence streams
 *     -> PlayerProjectionBacktestService
 *
 * All synthetic database evidence is created inside one
 * transaction and rolled back after the test.
 */


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed =
    0;


$failed =
    0;


function playerProjectionBacktestIntegrationAssert(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


function playerProjectionBacktestIntegrationSection(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * DATABASE
 * ============================================================
 */

$database =
    new Database();


$db =
    $database
        ->getConnection();


$transactionStarted =
    false;


try {

    /*
     * ========================================================
     * BEGIN CONTROLLED TRANSACTION
     * ========================================================
     */

    $db->beginTransaction();

    $transactionStarted =
        true;


    /*
     * ========================================================
     * A. REAL PRODUCTION COMPONENTS
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'A. Real Production Components'
    );


    playerProjectionBacktestIntegrationAssert(
        $db instanceof PDO,
        'Real database connection is available.'
    );


    playerProjectionBacktestIntegrationAssert(
        class_exists(
            'RecommendationSnapshot'
        ),
        'RecommendationSnapshot is available.'
    );


    playerProjectionBacktestIntegrationAssert(
        class_exists(
            'RecommendationSnapshotRepository'
        ),
        'RecommendationSnapshotRepository is available.'
    );


    playerProjectionBacktestIntegrationAssert(
        class_exists(
            'PlayerFixtureHistoryRepository'
        ),
        'PlayerFixtureHistoryRepository is available.'
    );


    playerProjectionBacktestIntegrationAssert(
        class_exists(
            'PlayerGameweekOutcomeService'
        ),
        'PlayerGameweekOutcomeService is available.'
    );


    playerProjectionBacktestIntegrationAssert(
        class_exists(
            'PlayerProjectionBacktestService'
        ),
        'PlayerProjectionBacktestService is available.'
    );
    
    
    playerProjectionBacktestIntegrationAssert(
        class_exists(
            'IntelligenceScoreBacktestMetricsService'
        ),
        'IntelligenceScoreBacktestMetricsService is available.'
    );


    /*
     * ========================================================
     * SYNTHETIC IDENTIFIERS
     * ========================================================
     *
     * These deliberately high official FPL IDs keep the
     * synthetic integration evidence separate from real FPL
     * identities.
     */

    $entryId =
        935008001;


    $fplGameweekId =
        990101;


    $fplTeamOneId =
        990101;


    $fplTeamTwoId =
        990102;


    $fplPlayerOneId =
        990101;


    $fplPlayerTwoId =
        990102;


    $fplPlayerThreeId =
        990103;


    $fplFixtureOneId =
        990101;


    $fplFixtureTwoId =
        990102;


    /*
     * ========================================================
     * B. SYNTHETIC GAMEWEEK
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'B. Synthetic Completed Gameweek'
    );


    $statement =
        $db->prepare(
            "
                INSERT INTO gameweeks (
                    fpl_gameweek_id,
                    name,
                    deadline_time,
                    finished,
                    data_checked,
                    is_previous,
                    is_current,
                    is_next
                )
                VALUES (
                    :fpl_gameweek_id,
                    :name,
                    :deadline_time,
                    1,
                    1,
                    0,
                    0,
                    0
                )
            "
        );


    $statement->execute([

        ':fpl_gameweek_id' =>
            $fplGameweekId,

        ':name' =>
            'Backtest Integration Gameweek',

        ':deadline_time' =>
            '2026-09-01 17:30:00'
    ]);


    $gameweekId =
        (int) $db->lastInsertId();


    playerProjectionBacktestIntegrationAssert(
        $gameweekId > 0,
        'Synthetic completed gameweek was created.'
    );


    /*
     * ========================================================
     * C. SYNTHETIC TEAMS
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'C. Synthetic Teams'
    );


    $teamStatement =
        $db->prepare(
            "
                INSERT INTO teams (
                    fpl_team_id,
                    name,
                    short_name
                )
                VALUES (
                    :fpl_team_id,
                    :name,
                    :short_name
                )
            "
        );


    $teamStatement->execute([

        ':fpl_team_id' =>
            $fplTeamOneId,

        ':name' =>
            'Backtest Team One',

        ':short_name' =>
            'BT1'
    ]);


    $teamOneId =
        (int) $db->lastInsertId();


    $teamStatement->execute([

        ':fpl_team_id' =>
            $fplTeamTwoId,

        ':name' =>
            'Backtest Team Two',

        ':short_name' =>
            'BT2'
    ]);


    $teamTwoId =
        (int) $db->lastInsertId();


    playerProjectionBacktestIntegrationAssert(
        $teamOneId > 0
        &&
        $teamTwoId > 0,
        'Synthetic teams were created.'
    );


    /*
     * ========================================================
     * D. SYNTHETIC PLAYERS
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'D. Synthetic Players'
    );


    $playerStatement =
        $db->prepare(
            "
                INSERT INTO players (
                    fpl_player_id,
                    team_id,
                    position,
                    web_name
                )
                VALUES (
                    :fpl_player_id,
                    :team_id,
                    :position,
                    :web_name
                )
            "
        );


    $playerStatement->execute([

        ':fpl_player_id' =>
            $fplPlayerOneId,

        ':team_id' =>
            $teamOneId,

        ':position' =>
            'MID',

        ':web_name' =>
            'Backtest One'
    ]);


    $playerOneId =
        (int) $db->lastInsertId();


    $playerStatement->execute([

        ':fpl_player_id' =>
            $fplPlayerTwoId,

        ':team_id' =>
            $teamOneId,

        ':position' =>
            'FWD',

        ':web_name' =>
            'Backtest Two'
    ]);


    $playerTwoId =
        (int) $db->lastInsertId();


    $playerStatement->execute([

        ':fpl_player_id' =>
            $fplPlayerThreeId,

        ':team_id' =>
            $teamOneId,

        ':position' =>
            'DEF',

        ':web_name' =>
            'Backtest Three'
    ]);


    $playerThreeId =
        (int) $db->lastInsertId();


    playerProjectionBacktestIntegrationAssert(
        $playerOneId > 0
        &&
        $playerTwoId > 0
        &&
        $playerThreeId > 0,
        'Synthetic players were created.'
    );


    /*
     * ========================================================
     * E. SYNTHETIC FIXTURES
     * ========================================================
     *
     * Two fixtures allow Player One to have a Double Gameweek.
     */

    playerProjectionBacktestIntegrationSection(
        'E. Synthetic Fixtures'
    );


    $fixtureStatement =
        $db->prepare(
            "
                INSERT INTO fixtures (
                    fpl_fixture_id,
                    gameweek,
                    home_team_id,
                    away_team_id,
                    kickoff_time,
                    finished,
                    finished_provisional
                )
                VALUES (
                    :fpl_fixture_id,
                    :gameweek,
                    :home_team_id,
                    :away_team_id,
                    :kickoff_time,
                    1,
                    1
                )
            "
        );


    $fixtureStatement->execute([

        ':fpl_fixture_id' =>
            $fplFixtureOneId,

        ':gameweek' =>
            $fplGameweekId,

        ':home_team_id' =>
            $teamOneId,

        ':away_team_id' =>
            $teamTwoId,

        ':kickoff_time' =>
            '2026-09-02 18:00:00'
    ]);


    $fixtureOneId =
        (int) $db->lastInsertId();


    $fixtureStatement->execute([

        ':fpl_fixture_id' =>
            $fplFixtureTwoId,

        ':gameweek' =>
            $fplGameweekId,

        ':home_team_id' =>
            $teamOneId,

        ':away_team_id' =>
            $teamTwoId,

        ':kickoff_time' =>
            '2026-09-04 18:00:00'
    ]);


    $fixtureTwoId =
        (int) $db->lastInsertId();


    playerProjectionBacktestIntegrationAssert(
        $fixtureOneId > 0
        &&
        $fixtureTwoId > 0,
        'Synthetic completed fixtures were created.'
    );


    /*
     * ========================================================
     * F. IMMUTABLE HISTORICAL RECOMMENDATION SNAPSHOT
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'F. Immutable Historical Recommendation Snapshot'
    );


    $historicalPlayerProjections = [

        [
            'player_id' =>
                $playerOneId,

            'fpl_player_id' =>
                $fplPlayerOneId,

            'name' =>
                'Backtest One',

            'position' =>
                'MID',

            'team_id' =>
                $teamOneId,

            'price' =>
                8.0,

            'intelligence_score' =>
                82.0,

            'projected_points' =>
                7.5,

            'projected_minutes' =>
                90.0,

            'projection_confidence' =>
                0.88,

            'projection_confidence_percent' =>
                88.0,

            'projection_confidence_label' =>
                'High',

            'projected_points_components' => [

                'appearance' =>
                    2.0,

                'performance' =>
                    5.5
            ],

            'projected_points_inputs' => [

                'expected_minutes' =>
                    90.0
            ],

            'has_projected_points' =>
                true
        ],

        [
            'player_id' =>
                $playerTwoId,

            'fpl_player_id' =>
                $fplPlayerTwoId,

            'name' =>
                'Backtest Two',

            'position' =>
                'FWD',

            'team_id' =>
                $teamOneId,

            'price' =>
                7.0,

            'intelligence_score' =>
                70.0,

            'projected_points' =>
                5.0,

            'projected_minutes' =>
                75.0,

            'projection_confidence' =>
                0.72,

            'projection_confidence_percent' =>
                72.0,

            'projection_confidence_label' =>
                'Medium',

            'projected_points_components' =>
                [],

            'projected_points_inputs' =>
                [],

            'has_projected_points' =>
                true
        ],

        [
            'player_id' =>
                $playerThreeId,

            'fpl_player_id' =>
                $fplPlayerThreeId,

            'name' =>
                'Backtest Three',

            'position' =>
                'DEF',

            'team_id' =>
                $teamOneId,

            'price' =>
                5.0,

            'intelligence_score' =>
                61.0,

            'projected_points' =>
                null,

            'projected_minutes' =>
                35.0,

            'projection_confidence' =>
                0.40,

            'projection_confidence_percent' =>
                40.0,

            'projection_confidence_label' =>
                'Low',

            'projected_points_components' =>
                [],

            'projected_points_inputs' =>
                [],

            'has_projected_points' =>
                false
        ]
    ];


    $snapshot =
        new RecommendationSnapshot(
            $fplGameweekId,
            $entryId,
            '2026-09-01 17:15:00',
            '2026-09-01 17:30:00',
            [],
            $historicalPlayerProjections,
            [],
            [],
            [],
            [],
            []
        );


    $snapshotRepository =
        new RecommendationSnapshotRepository(
            $db
        );


    $snapshotInserted =
        $snapshotRepository
            ->insertIfAbsent(
                $gameweekId,
                $snapshot
            );


    playerProjectionBacktestIntegrationAssert(
        $snapshotInserted === true,
        'Immutable recommendation snapshot was inserted through the real repository.'
    );


    /*
     * ========================================================
     * G. READ HISTORICAL PROJECTIONS BACK FROM MYSQL
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'G. Persisted Historical Projection Evidence'
    );


    $persistedSnapshot =
        $snapshotRepository
            ->getByEntryAndGameweek(
                $entryId,
                $gameweekId
            );


    playerProjectionBacktestIntegrationAssert(
        is_array(
            $persistedSnapshot
        ),
        'Persisted recommendation snapshot was read through the real repository.'
    );


    playerProjectionBacktestIntegrationAssert(
        $persistedSnapshot[
            'player_projections'
        ]
        ===
        $historicalPlayerProjections,
        'Historical player projection evidence survives the real MySQL round trip unchanged.'
    );


    /*
     * ========================================================
     * H. REALISED FIXTURE HISTORY
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'H. Realised Fixture History'
    );


    $fixtureHistoryRepository =
        new PlayerFixtureHistoryRepository(
            $db
        );


    /*
     * Player One:
     *
     * DGW
     * Fixture 1 = 8 points / 90 minutes
     * Fixture 2 = 4 points / 72 minutes
     *
     * Actual GW total = 12 points / 162 minutes
     */

    $fixtureHistoryRepository
        ->upsert([
            'gameweek_id' =>
                $gameweekId,

            'player_id' =>
                $playerOneId,

            'fpl_player_id' =>
                $fplPlayerOneId,

            'fixture_id' =>
                $fixtureOneId,

            'fpl_fixture_id' =>
                $fplFixtureOneId,

            'team_id' =>
                $teamOneId,

            'opponent_team_id' =>
                $teamTwoId,

            'was_home' =>
                true,

            'total_points' =>
                8,

            'minutes' =>
                90,

            'starts' =>
                1,

            'goals' =>
                1,

            'assists' =>
                0,

            'clean_sheets' =>
                1,

            'bonus' =>
                2
        ]);


    $fixtureHistoryRepository
        ->upsert([
            'gameweek_id' =>
                $gameweekId,

            'player_id' =>
                $playerOneId,

            'fpl_player_id' =>
                $fplPlayerOneId,

            'fixture_id' =>
                $fixtureTwoId,

            'fpl_fixture_id' =>
                $fplFixtureTwoId,

            'team_id' =>
                $teamOneId,

            'opponent_team_id' =>
                $teamTwoId,

            'was_home' =>
                true,

            'total_points' =>
                4,

            'minutes' =>
                72,

            'starts' =>
                1,

            'goals' =>
                0,

            'assists' =>
                1,

            'clean_sheets' =>
                0,

            'bonus' =>
                0
        ]);


    /*
     * Player Two:
     *
     * Official zero-minute row with negative realised points.
     */

    $fixtureHistoryRepository
        ->upsert([
            'gameweek_id' =>
                $gameweekId,

            'player_id' =>
                $playerTwoId,

            'fpl_player_id' =>
                $fplPlayerTwoId,

            'fixture_id' =>
                $fixtureOneId,

            'fpl_fixture_id' =>
                $fplFixtureOneId,

            'team_id' =>
                $teamOneId,

            'opponent_team_id' =>
                $teamTwoId,

            'was_home' =>
                true,

            'total_points' =>
                -1,

            'minutes' =>
                0,

            'starts' =>
                0,

            'goals' =>
                0,

            'assists' =>
                0,

            'clean_sheets' =>
                0,

            'bonus' =>
                0
        ]);


    /*
     * Player Three deliberately has no fixture-history row.
     *
     * This proves missing actual evidence is not converted into
     * zero points.
     */


    $storedHistory =
        $fixtureHistoryRepository
            ->getByGameweekId(
                $gameweekId
            );


    playerProjectionBacktestIntegrationAssert(
        count(
            $storedHistory
        )
        ===
        3,
        'Three fixture-level realised history rows were stored through the real repository.'
    );


    /*
     * ========================================================
     * I. REAL GAMEWEEK OUTCOME AGGREGATION
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'I. Real Gameweek Outcome Aggregation'
    );


    $outcomeService =
        new PlayerGameweekOutcomeService(
            $fixtureHistoryRepository
        );


    $outcomes =
        $outcomeService
            ->getByGameweekId(
                $gameweekId
            );


    playerProjectionBacktestIntegrationAssert(
        count(
            $outcomes
        )
        ===
        2,
        'Real fixture history aggregates into two known player outcomes.'
    );


    playerProjectionBacktestIntegrationAssert(
        $outcomes[0][
            'player_id'
        ]
        ===
        $playerOneId,
        'First realised outcome belongs to Player One.'
    );


    playerProjectionBacktestIntegrationAssert(
        $outcomes[0][
            'fixture_count'
        ]
        ===
        2,
        'Player One is recognised as having two realised fixtures.'
    );


    playerProjectionBacktestIntegrationAssert(
        $outcomes[0][
            'total_points'
        ]
        ===
        12,
        'Player One Double Gameweek points aggregate to twelve.'
    );


    playerProjectionBacktestIntegrationAssert(
        $outcomes[0][
            'minutes'
        ]
        ===
        162,
        'Player One Double Gameweek minutes aggregate to 162.'
    );


    playerProjectionBacktestIntegrationAssert(
        $outcomes[1][
            'player_id'
        ]
        ===
        $playerTwoId,
        'Second realised outcome belongs to Player Two.'
    );


    playerProjectionBacktestIntegrationAssert(
        $outcomes[1][
            'total_points'
        ]
        ===
        -1,
        'Negative realised FPL points survive the real outcome pipeline.'
    );


    playerProjectionBacktestIntegrationAssert(
        $outcomes[1][
            'minutes'
        ]
        ===
        0,
        'Official zero-minute history survives the real outcome pipeline.'
    );


    /*
     * ========================================================
     * J. COMPLETE PROJECTION BACKTEST PIPELINE
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'J. Complete Projection Backtest Pipeline'
    );


    $backtestService =
        new PlayerProjectionBacktestService();


    $backtest =
        $backtestService
            ->evaluate(
                $gameweekId,
                $persistedSnapshot[
                    'player_projections'
                ],
                $outcomes
            );


    playerProjectionBacktestIntegrationAssert(
        count(
            $backtest
        )
        ===
        3,
        'All three historical projections remain represented in the backtest.'
    );


    playerProjectionBacktestIntegrationAssert(
        array_column(
            $backtest,
            'player_id'
        )
        ===
        [
            $playerOneId,
            $playerTwoId,
            $playerThreeId
        ],
        'Backtest preserves immutable historical projection order.'
    );


    /*
     * ========================================================
     * K. DOUBLE GAMEWEEK PROJECTION COMPARISON
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'K. Double Gameweek Projection Comparison'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[0][
            'projected_points'
        ]
        ===
        7.5,
        'Player One historical projected points came from the persisted snapshot.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[0][
            'actual_points'
        ]
        ===
        12,
        'Player One actual points came from aggregated real fixture history.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[0][
            'points_error'
        ]
        ===
        4.5,
        'Player One signed points error is calculated from persisted prediction and realised outcome.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[0][
            'absolute_points_error'
        ]
        ===
        4.5,
        'Player One absolute points error is correct.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[0][
            'fixture_count'
        ]
        ===
        2,
        'Player One backtest preserves realised Double Gameweek fixture count.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[0][
            'projected_minutes'
        ]
        ===
        90.0,
        'Player One historical projected minutes came from the persisted snapshot.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[0][
            'actual_minutes'
        ]
        ===
        162,
        'Player One actual minutes came from aggregated fixture history.'
    );


    /*
     * ========================================================
     * L. NEGATIVE REALISED RETURN
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'L. Negative Realised Return'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[1][
            'projected_points'
        ]
        ===
        5.0,
        'Player Two historical projection is preserved.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[1][
            'actual_points'
        ]
        ===
        -1,
        'Player Two negative realised return is preserved.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[1][
            'points_error'
        ]
        ===
        -6.0,
        'Player Two signed points error correctly records over-projection.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[1][
            'absolute_points_error'
        ]
        ===
        6.0,
        'Player Two absolute points error is correct.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[1][
            'actual_minutes'
        ]
        ===
        0,
        'Player Two genuine zero realised minutes remain zero.'
    );


    /*
     * ========================================================
     * M. MISSING PROJECTION AND OUTCOME EVIDENCE
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'M. Missing Projection And Outcome Evidence'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[2][
            'projected_points'
        ]
        ===
        null,
        'Player Three unavailable historical projection remains null.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[2][
            'actual_points'
        ]
        ===
        null,
        'Player Three missing realised outcome is not manufactured as zero.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[2][
            'points_error'
        ]
        ===
        null,
        'Player Three signed error remains null without comparable evidence.'
    );


    playerProjectionBacktestIntegrationAssert(
        $backtest[2][
            'absolute_points_error'
        ]
        ===
        null,
        'Player Three absolute error remains null without comparable evidence.'
    );


    /*
     * ========================================================
     * N. HISTORICAL SNAPSHOT REMAINS UNCHANGED
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'N. Immutable Historical Evidence'
    );


    $snapshotAfterBacktest =
        $snapshotRepository
            ->getByEntryAndGameweek(
                $entryId,
                $gameweekId
            );


    playerProjectionBacktestIntegrationAssert(
        $snapshotAfterBacktest[
            'player_projections'
        ]
        ===
        $historicalPlayerProjections,
        'Backtesting does not alter persisted historical projection evidence.'
    );
    
    
    /*
     * ========================================================
     * O. GAMEWEEK-LEVEL PROJECTION METRICS
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'O. Gameweek-Level Projection Metrics'
    );


    $metricsService =
        new PlayerProjectionBacktestMetricsService();


    $metrics =
        $metricsService
            ->summarise(
                $backtest
            );


    playerProjectionBacktestIntegrationAssert(
        $metrics[
            'total_players'
        ]
        ===
        3,
        'Metrics include all three historical player projections.'
    );


    playerProjectionBacktestIntegrationAssert(
        $metrics[
            'comparable_players'
        ]
        ===
        2,
        'Metrics identify two players with comparable projection and outcome evidence.'
    );


    playerProjectionBacktestIntegrationAssert(
        $metrics[
            'unavailable_players'
        ]
        ===
        1,
        'Metrics identify one player with unavailable comparison evidence.'
    );


    playerProjectionBacktestIntegrationAssert(
        $metrics[
            'mean_absolute_error'
        ]
        ===
        5.25,
        'Gameweek MAE is calculated from persisted historical projections and realised outcomes.'
    );
    
    /*
     * Projected-minutes evaluation uses the same preserved
     * historical projection evidence and realised gameweek
     * outcomes.
     *
     * Player One:
     *     projected 90
     *     actual 162
     *     absolute error 72
     *
     * Player Two:
     *     projected 75
     *     actual 0
     *     absolute error 75
     *
     * Player Three:
     *     projected 35
     *     no realised outcome
     *     unavailable
     *
     * Minutes MAE:
     *     (72 + 75) / 2 = 73.5
     */

    playerProjectionBacktestIntegrationAssert(
        $metrics[
            'minutes_comparable_players'
        ]
        ===
        2,
        'Metrics identify two players with comparable projected and realised minutes.'
    );


    playerProjectionBacktestIntegrationAssert(
        $metrics[
            'minutes_unavailable_players'
        ]
        ===
        1,
        'Metrics preserve one unavailable projected-minutes comparison.'
    );


    playerProjectionBacktestIntegrationAssert(
        $metrics[
            'mean_absolute_minutes_error'
        ]
        ===
        73.5,
        'Gameweek Mean Absolute Minutes Error is calculated from persisted projections and realised outcomes.'
    );
    
    
    /*
     * ========================================================
     * P. INTELLIGENCE SCORE CORRELATION METRICS
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'P. Intelligence Score Correlation Metrics'
    );


    /*
     * Historical Intelligence Score and realised-return evidence:
     *
     * Player One:
     *     Intelligence Score 82
     *     actual points 12
     *
     * Player Two:
     *     Intelligence Score 70
     *     actual points -1
     *
     * Player Three:
     *     Intelligence Score 61
     *     no realised outcome
     *     unavailable
     *
     * The two comparable observations form a perfect positive
     * linear relationship, so Pearson correlation is +1.
     */

    $intelligenceScoreMetricsService =
        new IntelligenceScoreBacktestMetricsService();


    $intelligenceScoreMetrics =
        $intelligenceScoreMetricsService
            ->summarise(
                $backtest
            );


    playerProjectionBacktestIntegrationAssert(
        $intelligenceScoreMetrics[
            'total_players'
        ]
        ===
        3,
        'Intelligence Score metrics include all three historical player projections.'
    );


    playerProjectionBacktestIntegrationAssert(
        $intelligenceScoreMetrics[
            'comparable_players'
        ]
        ===
        2,
        'Intelligence Score metrics identify two players with historical score and realised-return evidence.'
    );


    playerProjectionBacktestIntegrationAssert(
        $intelligenceScoreMetrics[
            'unavailable_players'
        ]
        ===
        1,
        'Intelligence Score metrics preserve one player with unavailable realised-return evidence.'
    );


    playerProjectionBacktestIntegrationAssert(
        $intelligenceScoreMetrics[
            'correlation'
        ]
        !==
        null
        &&
        abs(
            $intelligenceScoreMetrics[
                'correlation'
            ]
            -
            1.0
        )
        <
        0.000000001,
        'Intelligence Score correlation is calculated from persisted historical scores and realised returns.'
    );
    
    
    /*
     * ========================================================
     * Q. PRODUCTION EVALUATION ORCHESTRATION
     * ========================================================
     */

    playerProjectionBacktestIntegrationSection(
        'Q. Production Evaluation Orchestration'
    );


    $evaluationService =
        new PlayerProjectionBacktestEvaluationService(
            $snapshotRepository,
            $outcomeService,
            $backtestService,
            $metricsService,
            $intelligenceScoreMetricsService
        );


    $evaluation =
        $evaluationService
            ->evaluate(
                $entryId,
                $gameweekId
            );


    playerProjectionBacktestIntegrationAssert(
        is_array(
            $evaluation
        ),
        'Production evaluation service returns a complete historical backtest.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'entry_id'
        ]
        ===
        $entryId,
        'Production evaluation preserves the requested entry ID.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'gameweek_id'
        ]
        ===
        $gameweekId,
        'Production evaluation preserves the requested local gameweek ID.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'snapshot'
        ][
            'player_projections'
        ]
        ===
        $historicalPlayerProjections,
        'Production evaluation loads exact persisted historical projection evidence.'
    );


    playerProjectionBacktestIntegrationAssert(
        count(
            $evaluation[
                'player_outcomes'
            ]
        )
        ===
        2,
        'Production evaluation loads aggregated realised outcomes through the real outcome service.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'player_outcomes'
        ]
        ===
        $outcomes,
        'Production evaluation preserves the already-proven realised outcome evidence.'
    );


    playerProjectionBacktestIntegrationAssert(
        count(
            $evaluation[
                'player_backtest'
            ]
        )
        ===
        3,
        'Production evaluation backtests all three preserved historical player projections.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'player_backtest'
        ]
        ===
        $backtest,
        'Production evaluation produces the already-proven player-level backtest evidence.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'metrics'
        ][
            'total_players'
        ]
        ===
        3,
        'Production evaluation metrics include all historical projection evidence.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'metrics'
        ][
            'comparable_players'
        ]
        ===
        2,
        'Production evaluation metrics identify the two comparable players.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'metrics'
        ][
            'unavailable_players'
        ]
        ===
        1,
        'Production evaluation metrics preserve the unavailable comparison.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'metrics'
        ][
            'mean_absolute_error'
        ]
        ===
        5.25,
        'Production evaluation returns the expected gameweek Mean Absolute Error.'
    );
    
    
    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'metrics'
        ][
            'minutes_comparable_players'
        ]
        ===
        2,
        'Production evaluation identifies the two comparable projected-minutes players.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'metrics'
        ][
            'minutes_unavailable_players'
        ]
        ===
        1,
        'Production evaluation preserves the unavailable projected-minutes comparison.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'metrics'
        ][
            'mean_absolute_minutes_error'
        ]
        ===
        73.5,
        'Production evaluation returns the expected gameweek Mean Absolute Minutes Error.'
    );
    
    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'intelligence_score_metrics'
        ][
            'total_players'
        ]
        ===
        3,
        'Production evaluation Intelligence Score metrics include all historical player evidence.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'intelligence_score_metrics'
        ][
            'comparable_players'
        ]
        ===
        2,
        'Production evaluation identifies the two comparable Intelligence Score players.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'intelligence_score_metrics'
        ][
            'unavailable_players'
        ]
        ===
        1,
        'Production evaluation preserves the unavailable Intelligence Score comparison.'
    );


    playerProjectionBacktestIntegrationAssert(
        $evaluation[
            'intelligence_score_metrics'
        ][
            'correlation'
        ]
        !==
        null
        &&
        abs(
            $evaluation[
                'intelligence_score_metrics'
            ][
                'correlation'
            ]
            -
            1.0
        )
        <
        0.000000001,
        'Production evaluation returns the expected Intelligence Score correlation.'
    );


    $snapshotAfterEvaluation =
        $snapshotRepository
            ->getByEntryAndGameweek(
                $entryId,
                $gameweekId
            );


    playerProjectionBacktestIntegrationAssert(
        $snapshotAfterEvaluation[
            'player_projections'
        ]
        ===
        $historicalPlayerProjections,
        'Production evaluation leaves immutable historical projection evidence unchanged.'
    );


    /*
     * ========================================================
     * R. ROLLBACK
     * ========================================================
     */

    $db->rollBack();

    $transactionStarted =
        false;


    playerProjectionBacktestIntegrationSection(
        'R. Cleanup'
    );


    playerProjectionBacktestIntegrationAssert(
        true,
        'All synthetic integration evidence was rolled back.'
    );

} catch (
    Throwable $exception
) {

    if (
        $transactionStarted
        &&
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    echo "<br>";
    echo "ERROR: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    $failed++;
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Projection Backtest Integration Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}