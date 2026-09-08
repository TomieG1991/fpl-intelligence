<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Outcome Service Integration Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function outcomeIntegrationResult(
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


$transactionStarted = false;


try {

    /*
     * ========================================================
     * BEGIN CONTROLLED TRANSACTION
     * ========================================================
     */

    $db->beginTransaction();

    $transactionStarted = true;


    echo "<br>";
    echo "============================================<br>";
    echo "A. Database And Services<br>";
    echo "============================================<br>";


    outcomeIntegrationResult(
        $db instanceof PDO,
        'Database connection is available.'
    );


    outcomeIntegrationResult(
        class_exists(
            'PlayerFixtureHistoryRepository'
        )
        &&
        class_exists(
            'PlayerGameweekOutcomeService'
        ),
        'Real fixture history repository and outcome service are available.'
    );


    /*
     * ========================================================
     * SYNTHETIC IDENTIFIERS
     * ========================================================
     *
     * Use deliberately high FPL IDs so they cannot collide
     * with imported production data.
     */

    $fplGameweekId =
        990001;


    $fplTeamOneId =
        990001;


    $fplTeamTwoId =
        990002;


    $fplPlayerOneId =
        990001;


    $fplPlayerTwoId =
        990002;


    $fplFixtureOneId =
        990001;


    $fplFixtureTwoId =
        990002;


    /*
     * ========================================================
     * GAMEWEEK
     * ========================================================
     */

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
            'Integration Outcome Test',

        ':deadline_time' =>
            '2026-09-01 17:30:00'
    ]);


    $gameweekId =
        (int) $db->lastInsertId();


    /*
     * ========================================================
     * TEAMS
     * ========================================================
     */

    foreach (
        [
            [
                $fplTeamOneId,
                'Integration Team One'
            ],
            [
                $fplTeamTwoId,
                'Integration Team Two'
            ]
        ]
        as $team
    ) {

        $statement =
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


        $statement->execute([
            ':fpl_team_id' =>
                $team[0],

            ':name' =>
                $team[1],

            ':short_name' =>
                'INT'
        ]);
    }


    $teamOneId =
        (int) $db->query(
            "
                SELECT id
                FROM teams
                WHERE fpl_team_id = {$fplTeamOneId}
            "
        )->fetchColumn();


    $teamTwoId =
        (int) $db->query(
            "
                SELECT id
                FROM teams
                WHERE fpl_team_id = {$fplTeamTwoId}
            "
        )->fetchColumn();


    /*
     * ========================================================
     * PLAYERS
     * ========================================================
     */

    foreach (
        [
            [
                $fplPlayerOneId,
                $teamOneId,
                'Outcome Player One'
            ],
            [
                $fplPlayerTwoId,
                $teamOneId,
                'Outcome Player Two'
            ]
        ]
        as $player
    ) {

        $statement =
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
                        'MID',
                        :web_name
                    )
                "
            );


        $statement->execute([
            ':fpl_player_id' =>
                $player[0],

            ':team_id' =>
                $player[1],

            ':web_name' =>
                $player[2]
        ]);
    }


    $playerOneId =
        (int) $db->query(
            "
                SELECT id
                FROM players
                WHERE fpl_player_id = {$fplPlayerOneId}
            "
        )->fetchColumn();


    $playerTwoId =
        (int) $db->query(
            "
                SELECT id
                FROM players
                WHERE fpl_player_id = {$fplPlayerTwoId}
            "
        )->fetchColumn();


    /*
     * ========================================================
     * FIXTURES
     * ========================================================
     */

    foreach (
        [
            $fplFixtureOneId,
            $fplFixtureTwoId
        ]
        as $fixtureFplId
    ) {

        $statement =
            $db->prepare(
                "
                    INSERT INTO fixtures (
                        fpl_fixture_id,
                        gameweek,
                        home_team_id,
                        away_team_id,
                        finished,
                        finished_provisional
                    )
                    VALUES (
                        :fpl_fixture_id,
                        :gameweek,
                        :home_team_id,
                        :away_team_id,
                        1,
                        1
                    )
                "
            );


        $statement->execute([
            ':fpl_fixture_id' =>
                $fixtureFplId,

            ':gameweek' =>
                $fplGameweekId,

            ':home_team_id' =>
                $teamOneId,

            ':away_team_id' =>
                $teamTwoId
        ]);
    }


    $fixtureOneId =
        (int) $db->query(
            "
                SELECT id
                FROM fixtures
                WHERE fpl_fixture_id = {$fplFixtureOneId}
            "
        )->fetchColumn();


    $fixtureTwoId =
        (int) $db->query(
            "
                SELECT id
                FROM fixtures
                WHERE fpl_fixture_id = {$fplFixtureTwoId}
            "
        )->fetchColumn();


    /*
     * ========================================================
     * FIXTURE HISTORY
     * ========================================================
     */

    $historyRows = [

        [
            $playerOneId,
            $fplPlayerOneId,
            $fixtureOneId,
            $fplFixtureOneId,
            6,
            90,
            1,
            1,
            0,
            1,
            1
        ],

        [
            $playerOneId,
            $fplPlayerOneId,
            $fixtureTwoId,
            $fplFixtureTwoId,
            7,
            78,
            1,
            0,
            1,
            0,
            2
        ],

        [
            $playerTwoId,
            $fplPlayerTwoId,
            $fixtureOneId,
            $fplFixtureOneId,
            -2,
            0,
            0,
            0,
            0,
            0,
            0
        ]
    ];


    foreach (
        $historyRows
        as $row
    ) {

        $statement =
            $db->prepare(
                "
                    INSERT INTO player_fixture_history (
                        gameweek_id,
                        player_id,
                        fpl_player_id,
                        fixture_id,
                        fpl_fixture_id,
                        team_id,
                        opponent_team_id,
                        was_home,
                        total_points,
                        minutes,
                        starts,
                        goals,
                        assists,
                        clean_sheets,
                        bonus
                    )
                    VALUES (
                        :gameweek_id,
                        :player_id,
                        :fpl_player_id,
                        :fixture_id,
                        :fpl_fixture_id,
                        :team_id,
                        :opponent_team_id,
                        1,
                        :total_points,
                        :minutes,
                        :starts,
                        :goals,
                        :assists,
                        :clean_sheets,
                        :bonus
                    )
                "
            );


        $statement->execute([
            ':gameweek_id' =>
                $gameweekId,

            ':player_id' =>
                $row[0],

            ':fpl_player_id' =>
                $row[1],

            ':fixture_id' =>
                $row[2],

            ':fpl_fixture_id' =>
                $row[3],

            ':team_id' =>
                $teamOneId,

            ':opponent_team_id' =>
                $teamTwoId,

            ':total_points' =>
                $row[4],

            ':minutes' =>
                $row[5],

            ':starts' =>
                $row[6],

            ':goals' =>
                $row[7],

            ':assists' =>
                $row[8],

            ':clean_sheets' =>
                $row[9],

            ':bonus' =>
                $row[10]
        ]);
    }


    /*
     * ========================================================
     * REAL REPOSITORY + SERVICE
     * ========================================================
     */

    $repository =
        new PlayerFixtureHistoryRepository(
            $db
        );


    $service =
        new PlayerGameweekOutcomeService(
            $repository
        );


    $result =
        $service->getByGameweekId(
            $gameweekId
        );


    /*
     * ========================================================
     * ASSERTIONS
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "B. Real MySQL Aggregation<br>";
    echo "============================================<br>";


    outcomeIntegrationResult(
        count($result) === 2,
        'Real fixture history rows aggregate into two player outcomes.'
    );


    outcomeIntegrationResult(
        array_column(
            $result,
            'player_id'
        ) === [
            $playerOneId,
            $playerTwoId
        ],
        'Real MySQL outcomes are ordered by local player ID.'
    );


    $playerOneOutcome =
        $result[0];


    $playerTwoOutcome =
        $result[1];


    outcomeIntegrationResult(
        $playerOneOutcome['fixture_count'] === 2,
        'Real Double Gameweek fixture count is two.'
    );


    outcomeIntegrationResult(
        $playerOneOutcome['total_points'] === 13,
        'Real Double Gameweek points are summed.'
    );


    outcomeIntegrationResult(
        $playerOneOutcome['minutes'] === 168,
        'Real Double Gameweek minutes are summed.'
    );


    outcomeIntegrationResult(
        $playerOneOutcome['starts'] === 2,
        'Real Double Gameweek starts are summed.'
    );


    outcomeIntegrationResult(
        $playerOneOutcome['goals'] === 1,
        'Real Double Gameweek goals are summed.'
    );


    outcomeIntegrationResult(
        $playerOneOutcome['assists'] === 1,
        'Real Double Gameweek assists are summed.'
    );


    outcomeIntegrationResult(
        $playerOneOutcome['clean_sheets'] === 1,
        'Real Double Gameweek clean sheets are summed.'
    );


    outcomeIntegrationResult(
        $playerOneOutcome['bonus'] === 3,
        'Real Double Gameweek bonus is summed.'
    );


    outcomeIntegrationResult(
        $playerTwoOutcome['fixture_count'] === 1,
        'Zero-minute official history row remains one known fixture.'
    );


    outcomeIntegrationResult(
        $playerTwoOutcome['minutes'] === 0,
        'Zero-minute official history preserves zero minutes.'
    );


    outcomeIntegrationResult(
        $playerTwoOutcome['total_points'] === -2,
        'Negative realised FPL points survive the real database path.'
    );


    /*
     * ========================================================
     * ROLLBACK
     * ========================================================
     */

    $db->rollBack();

    $transactionStarted = false;


    echo "<br>";
    echo "============================================<br>";
    echo "C. Cleanup<br>";
    echo "============================================<br>";


    outcomeIntegrationResult(
        true,
        'Controlled synthetic database evidence was rolled back.'
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
echo "Player Gameweek Outcome Service Integration Test Summary<br>";
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