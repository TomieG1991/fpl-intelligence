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
 * SYNTHETIC TEST IDENTITY
 * ============================================================
 *
 * These FPL identities are deliberately outside the normal
 * live FPL identity range.
 *
 * The database primary keys themselves remain genuine local
 * gameweek/player/fixture/team identities so all existing
 * foreign-key constraints remain respected.
 */

$syntheticFplPlayerId =
    935008001;


$syntheticFplFixtureIdOne =
    935008101;


$syntheticFplFixtureIdTwo =
    935008102;


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Real Production Stack<br>";
echo "============================================<br>";


$database =
    new Database();


$db =
    $database
        ->getConnection();


outcomeIntegrationResult(
    $db instanceof PDO,
    'Real database connection is available.'
);


$historyRepository =
    new PlayerFixtureHistoryRepository(
        $db
    );


$service =
    new PlayerGameweekOutcomeService(
        $historyRepository
    );


outcomeIntegrationResult(
    $historyRepository
        instanceof PlayerFixtureHistoryRepository,
    'Real PlayerFixtureHistoryRepository is available.'
);


outcomeIntegrationResult(
    $service
        instanceof PlayerGameweekOutcomeService,
    'Real PlayerGameweekOutcomeService is available.'
);


/*
 * ============================================================
 * RESOLVE EXISTING PARENT IDENTITIES
 * ============================================================
 *
 * We reuse genuine local parent rows.
 *
 * Only player_fixture_history rows created by this test are
 * synthetic and therefore require cleanup.
 */

$gameweekStatement =
    $db->query(
        "
            SELECT
                id,
                fpl_gameweek_id
            FROM
                gameweeks
            ORDER BY
                fpl_gameweek_id ASC
            LIMIT 1
        "
    );


$gameweek =
    $gameweekStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


$playerStatement =
    $db->query(
        "
            SELECT
                id,
                fpl_player_id
            FROM
                players
            WHERE
                fpl_player_id > 0
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


$fixtureStatement =
    $db->query(
        "
            SELECT
                id,
                fpl_fixture_id,
                home_team_id,
                away_team_id
            FROM
                fixtures
            WHERE
                home_team_id > 0
                AND away_team_id > 0
                AND home_team_id <> away_team_id
            ORDER BY
                id ASC
            LIMIT 2
        "
    );


$fixtures =
    $fixtureStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


outcomeIntegrationResult(
    is_array(
        $gameweek
    )
    &&
    (int) (
        $gameweek[
            'id'
        ]
        ?? 0
    ) > 0,
    'A genuine local gameweek is available for the test.'
);


outcomeIntegrationResult(
    is_array(
        $player
    )
    &&
    (int) (
        $player[
            'id'
        ]
        ?? 0
    ) > 0,
    'A genuine local player is available for the test.'
);


outcomeIntegrationResult(
    count(
        $fixtures
    ) === 2,
    'Two genuine local fixtures are available for the test.'
);


if (
    !is_array(
        $gameweek
    )
    ||
    !is_array(
        $player
    )
    ||
    count(
        $fixtures
    ) !== 2
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌<br>";

    exit;
}


$gameweekId =
    (int) $gameweek[
        'id'
    ];


$playerId =
    (int) $player[
        'id'
    ];


/*
 * ============================================================
 * IMPORTANT TEST ISOLATION
 * ============================================================
 *
 * We cannot insert another history row for the genuine player
 * and genuine fixture if that (player_id, fixture_id) pair
 * already exists.
 *
 * Therefore find two fixture identities for which this player
 * does not currently have fixture-history evidence.
 */

$availableFixtureStatement =
    $db->prepare(
        "
            SELECT
                f.id,
                f.fpl_fixture_id,
                f.home_team_id,
                f.away_team_id
            FROM
                fixtures f
            LEFT JOIN
                player_fixture_history pfh
                    ON pfh.fixture_id = f.id
                    AND pfh.player_id = :player_id
            WHERE
                pfh.id IS NULL
                AND f.home_team_id > 0
                AND f.away_team_id > 0
                AND f.home_team_id <> f.away_team_id
            ORDER BY
                f.id ASC
            LIMIT 2
        "
    );


$availableFixtureStatement
    ->execute([

        ':player_id' =>
            $playerId
    ]);


$availableFixtures =
    $availableFixtureStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


outcomeIntegrationResult(
    count(
        $availableFixtures
    ) === 2,
    'Two unused genuine fixture identities are available for synthetic history.'
);


if (
    count(
        $availableFixtures
    ) !== 2
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌<br>";

    exit;
}


$fixtureOne =
    $availableFixtures[0];


$fixtureTwo =
    $availableFixtures[1];


$fixtureOneId =
    (int) $fixtureOne[
        'id'
    ];


$fixtureTwoId =
    (int) $fixtureTwo[
        'id'
    ];


/*
 * ============================================================
 * CLEANUP HELPER
 * ============================================================
 */

$cleanupStatement =
    $db->prepare(
        "
            DELETE FROM
                player_fixture_history
            WHERE
                player_id = :player_id
                AND fixture_id IN (
                    :fixture_one,
                    :fixture_two
                )
        "
    );


$cleanup =
    static function () use (
        $cleanupStatement,
        $playerId,
        $fixtureOneId,
        $fixtureTwoId
    ): void {

        $cleanupStatement
            ->execute([

                ':player_id' =>
                    $playerId,

                ':fixture_one' =>
                    $fixtureOneId,

                ':fixture_two' =>
                    $fixtureTwoId
            ]);
    };


/*
 * Remove remnants from an interrupted previous execution.
 */

$cleanup();


try {

    /*
     * ========================================================
     * SCENARIO B
     * SINGLE FIXTURE THROUGH REAL REPOSITORY
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario B: Single Fixture Persistence<br>";
    echo "============================================<br>";


    $teamOneId =
        (int) $fixtureOne[
            'home_team_id'
        ];


    $opponentOneId =
        (int) $fixtureOne[
            'away_team_id'
        ];


    $historyRepository
        ->upsert([

            'gameweek_id' =>
                $gameweekId,

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
                $syntheticFplPlayerId,

            'fixture_id' =>
                $fixtureOneId,

            'fpl_fixture_id' =>
                $syntheticFplFixtureIdOne,

            'team_id' =>
                $teamOneId,

            'opponent_team_id' =>
                $opponentOneId,

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


    $storedOne =
        $historyRepository
            ->getByPlayerAndFixture(
                $playerId,
                $fixtureOneId
            );


    outcomeIntegrationResult(
        is_array(
            $storedOne
        ),
        'Synthetic fixture history is persisted through the real repository.'
    );


    outcomeIntegrationResult(
        (
            (int) (
                $storedOne[
                    'total_points'
                ]
                ?? 0
            )
        ) === 8,
        'Real repository preserves synthetic realised points.'
    );


    outcomeIntegrationResult(
        (
            (int) (
                $storedOne[
                    'minutes'
                ]
                ?? 0
            )
        ) === 90,
        'Real repository preserves synthetic realised minutes.'
    );


    /*
     * ========================================================
     * SCENARIO C
     * SERVICE READS REAL DATABASE EVIDENCE
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario C: Real Database Outcome Aggregation<br>";
    echo "============================================<br>";


    $outcomes =
        $service
            ->getByGameweekId(
                $gameweekId
            );


    $playerOutcome =
        null;


    foreach (
        $outcomes
        as $outcome
    ) {

        if (
            (int) (
                $outcome[
                    'player_id'
                ]
                ?? 0
            )
            ===
            $playerId
        ) {

            $playerOutcome =
                $outcome;

            break;
        }
    }


    outcomeIntegrationResult(
        is_array(
            $playerOutcome
        ),
        'Outcome service reads evidence through the real repository.'
    );


    /*
     * ========================================================
     * SCENARIO D
     * SECOND FIXTURE / DGW AGGREGATION
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario D: Multi-Fixture Aggregation<br>";
    echo "============================================<br>";


    $teamTwoId =
        (int) $fixtureTwo[
            'home_team_id'
        ];


    $opponentTwoId =
        (int) $fixtureTwo[
            'away_team_id'
        ];


    $historyRepository
        ->upsert([

            'gameweek_id' =>
                $gameweekId,

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
                $syntheticFplPlayerId,

            'fixture_id' =>
                $fixtureTwoId,

            'fpl_fixture_id' =>
                $syntheticFplFixtureIdTwo,

            'team_id' =>
                $teamTwoId,

            'opponent_team_id' =>
                $opponentTwoId,

            'was_home' =>
                true,

            'total_points' =>
                5,

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
                1
        ]);


    $outcomes =
        $service
            ->getByGameweekId(
                $gameweekId
            );


    $playerOutcome =
        null;


    foreach (
        $outcomes
        as $outcome
    ) {

        if (
            (int) (
                $outcome[
                    'player_id'
                ]
                ?? 0
            )
            ===
            $playerId
        ) {

            $playerOutcome =
                $outcome;

            break;
        }
    }


    outcomeIntegrationResult(
        is_array(
            $playerOutcome
        ),
        'Player outcome remains available after second fixture evidence is stored.'
    );


    /*
     * We deliberately do not assert fixture_count = 2 here.
     *
     * This is a genuine existing player/gameweek identity and
     * may already contain legitimate history rows for other
     * fixtures in this gameweek.
     *
     * Instead verify that the two synthetic rows themselves
     * are represented in the real repository.
     */

    $storedTwo =
        $historyRepository
            ->getByPlayerAndFixture(
                $playerId,
                $fixtureTwoId
            );


    outcomeIntegrationResult(
        is_array(
            $storedTwo
        ),
        'Second synthetic fixture row is persisted through the real repository.'
    );


    outcomeIntegrationResult(
        (
            (int) (
                $storedTwo[
                    'total_points'
                ]
                ?? 0
            )
        ) === 5,
        'Second fixture realised points are preserved.'
    );


    outcomeIntegrationResult(
        (
            (int) (
                $storedTwo[
                    'minutes'
                ]
                ?? 0
            )
        ) === 72,
        'Second fixture realised minutes are preserved.'
    );


    /*
     * ========================================================
     * SCENARIO E
     * ZERO-MINUTE AND NEGATIVE EVIDENCE
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario E: Zero-Minute and Negative Evidence<br>";
    echo "============================================<br>";


    $historyRepository
        ->upsert([

            'gameweek_id' =>
                $gameweekId,

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
                $syntheticFplPlayerId,

            'fixture_id' =>
                $fixtureTwoId,

            'fpl_fixture_id' =>
                $syntheticFplFixtureIdTwo,

            'team_id' =>
                $teamTwoId,

            'opponent_team_id' =>
                $opponentTwoId,

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


    $zeroMinuteRow =
        $historyRepository
            ->getByPlayerAndFixture(
                $playerId,
                $fixtureTwoId
            );


    outcomeIntegrationResult(
        (int) (
            $zeroMinuteRow[
                'minutes'
            ]
            ?? -1
        ) === 0,
        'Real repository preserves an official zero-minute outcome.'
    );


    outcomeIntegrationResult(
        (int) (
            $zeroMinuteRow[
                'total_points'
            ]
            ?? 0
        ) === -1,
        'Real repository preserves negative realised FPL points.'
    );


    /*
     * ========================================================
     * SCENARIO F
     * CLEANUP
     * ========================================================
     */

    echo "<br>";
    echo "============================================<br>";
    echo "Scenario F: Synthetic Evidence Cleanup<br>";
    echo "============================================<br>";


    $cleanup();


    $remainingOne =
        $historyRepository
            ->getByPlayerAndFixture(
                $playerId,
                $fixtureOneId
            );


    $remainingTwo =
        $historyRepository
            ->getByPlayerAndFixture(
                $playerId,
                $fixtureTwoId
            );


    outcomeIntegrationResult(
        $remainingOne === null,
        'First synthetic fixture-history row is removed.'
    );


    outcomeIntegrationResult(
        $remainingTwo === null,
        'Second synthetic fixture-history row is removed.'
    );

} catch (
    Throwable $exception
) {

    /*
     * Always attempt cleanup before reporting an unexpected
     * integration failure.
     */

    try {

        $cleanup();

    } catch (
        Throwable $cleanupException
    ) {

        echo "CLEANUP ERROR: "
            . htmlspecialchars(
                $cleanupException
                    ->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }


    outcomeIntegrationResult(
        false,
        'Unexpected integration exception: '
            . $exception
                ->getMessage()
    );
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "TEST SUMMARY<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}