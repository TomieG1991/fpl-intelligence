<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Fixture History Single Player Import Test<br>";
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

function singlePlayerHistoryCheck(
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


    $fplApi =
        new FPLApi();


    $gameweekRepository =
        new GameweekRepository(
            $db
        );


    $historyRepository =
        new PlayerFixtureHistoryRepository(
            $db
        );

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
 * SCENARIO A
 * SELECT REAL PLAYER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Real Player<br>";
echo "============================================<br>";


/*
 * FPL Player ID 1 is Raya in the current 2026/27 dataset.
 *
 * We deliberately resolve him through the local database
 * rather than creating any synthetic player identity.
 */

$playerStatement =
    $db->prepare(
        "
        SELECT
            id,
            fpl_player_id,
            web_name,
            first_name,
            second_name,
            position,
            team_id
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


singlePlayerHistoryCheck(
    'Raya exists in the local player database',
    is_array(
        $player
    )
);


if (
    !is_array(
        $player
    )
) {

    echo "<br>RESULT: TESTS FAILED ❌";

    exit;
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


singlePlayerHistoryCheck(
    'Raya has valid local player ID',
    $playerId > 0
);


singlePlayerHistoryCheck(
    'Raya has valid FPL player ID',
    $fplPlayerId === 1
);


echo "Player: "
    . htmlspecialchars(
        (string) (
            $player[
                'web_name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Local Player ID: "
    . $playerId
    . "<br>";


echo "FPL Player ID: "
    . $fplPlayerId
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * FETCH LIVE FPL HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Live FPL History<br>";
echo "============================================<br>";


try {

    $summary =
        $fplApi
            ->getPlayerSummary(
                $fplPlayerId
            );


    $history =
        (
            isset(
                $summary[
                    'history'
                ]
            )
            &&
            is_array(
                $summary[
                    'history'
                ]
            )
        )
            ? $summary[
                'history'
            ]
            : [];

} catch (
    Throwable $exception
) {

    echo "LIVE API FAILED ❌<br>";


    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );


    exit;
}


singlePlayerHistoryCheck(
    'Player summary contains current-season history',
    !empty(
        $history
    )
);


if (
    empty(
        $history
    )
) {

    echo "<br>RESULT: TESTS FAILED ❌";

    exit;
}


/*
 * Use the first available real history row.
 *
 * At the current point in the season this is Raya's GW1
 * history. No match statistics are hard-coded.
 */

$historyRow =
    $history[0];


singlePlayerHistoryCheck(
    'First FPL history row is an array',
    is_array(
        $historyRow
    )
);


$fplFixtureId =
    (int) (
        $historyRow[
            'fixture'
        ]
        ?? 0
    );


$fplGameweekId =
    (int) (
        $historyRow[
            'round'
        ]
        ?? 0
    );


singlePlayerHistoryCheck(
    'History row contains valid FPL fixture ID',
    $fplFixtureId > 0
);


singlePlayerHistoryCheck(
    'History row contains valid FPL gameweek ID',
    $fplGameweekId > 0
);


echo "History Rows: "
    . count(
        $history
    )
    . "<br>";


echo "FPL Gameweek: "
    . $fplGameweekId
    . "<br>";


echo "FPL Fixture ID: "
    . $fplFixtureId
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * RESOLVE LOCAL GAMEWEEK
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Local Gameweek Resolution<br>";
echo "============================================<br>";


$gameweek =
    $gameweekRepository
        ->getByFplGameweekId(
            $fplGameweekId
        );


singlePlayerHistoryCheck(
    'FPL history gameweek resolves to local gameweek',
    is_array(
        $gameweek
    )
);


if (
    !is_array(
        $gameweek
    )
) {

    echo "<br>RESULT: TESTS FAILED ❌";

    exit;
}


$gameweekId =
    (int) (
        $gameweek[
            'id'
        ]
        ?? 0
    );


singlePlayerHistoryCheck(
    'Resolved gameweek has valid local ID',
    $gameweekId > 0
);


echo "Local Gameweek ID: "
    . $gameweekId
    . "<br>";


echo "Gameweek: "
    . htmlspecialchars(
        (string) (
            $gameweek[
                'name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * RESOLVE LOCAL FIXTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Local Fixture Resolution<br>";
echo "============================================<br>";


$fixtureStatement =
    $db->prepare(
        "
        SELECT
            id,
            fpl_fixture_id,
            gameweek,
            home_team_id,
            away_team_id,
            kickoff_time
        FROM
            fixtures
        WHERE
            fpl_fixture_id = :fpl_fixture_id
        LIMIT 1
        "
    );


$fixtureStatement
    ->execute([

        ':fpl_fixture_id' =>
            $fplFixtureId
    ]);


$fixture =
    $fixtureStatement
        ->fetch(
            PDO::FETCH_ASSOC
        );


singlePlayerHistoryCheck(
    'FPL history fixture resolves to local fixture',
    is_array(
        $fixture
    )
);


if (
    !is_array(
        $fixture
    )
) {

    echo "<br>RESULT: TESTS FAILED ❌";

    exit;
}


$fixtureId =
    (int) (
        $fixture[
            'id'
        ]
        ?? 0
    );


singlePlayerHistoryCheck(
    'Resolved fixture has valid local ID',
    $fixtureId > 0
);


singlePlayerHistoryCheck(
    'Resolved fixture belongs to expected gameweek',
    (
        (int) (
            $fixture[
                'gameweek'
            ]
            ?? 0
        )
    )
    ===
    $fplGameweekId
);


echo "Local Fixture ID: "
    . $fixtureId
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * RESOLVE TEAM / OPPONENT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Historical Team Context<br>";
echo "============================================<br>";


$wasHome =
    !empty(
        $historyRow[
            'was_home'
        ]
        ?? false
    );


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


/*
 * Historical team identity is resolved from the fixture,
 * not from players.team_id.
 *
 * This remains correct if a player later transfers clubs.
 */

$teamId =
    $wasHome
        ? $homeTeamId
        : $awayTeamId;


$opponentTeamId =
    $wasHome
        ? $awayTeamId
        : $homeTeamId;


singlePlayerHistoryCheck(
    'Historical player team resolves from fixture',
    $teamId > 0
);


singlePlayerHistoryCheck(
    'Historical opponent resolves from fixture',
    $opponentTeamId > 0
);


singlePlayerHistoryCheck(
    'Historical team and opponent are different',
    $teamId !== $opponentTeamId
);


/*
 * Cross-check the resolved opponent with the official
 * opponent_team value supplied by the FPL history row.
 */

$fplOpponentTeamId =
    (int) (
        $historyRow[
            'opponent_team'
        ]
        ?? 0
    );


$opponentStatement =
    $db->prepare(
        "
        SELECT
            id
        FROM
            teams
        WHERE
            fpl_team_id = :fpl_team_id
        LIMIT 1
        "
    );


$opponentStatement
    ->execute([

        ':fpl_team_id' =>
            $fplOpponentTeamId
    ]);


$expectedOpponentTeamId =
    $opponentStatement
        ->fetchColumn();


singlePlayerHistoryCheck(
    'Official FPL opponent resolves locally',
    $expectedOpponentTeamId !== false
);


singlePlayerHistoryCheck(
    'Fixture-derived opponent matches FPL opponent',
    $expectedOpponentTeamId !== false
    &&
    $opponentTeamId
    ===
    (int) $expectedOpponentTeamId
);


echo "Was Home: "
    . (
        $wasHome
            ? 'Yes'
            : 'No'
    )
    . "<br>";


echo "Local Team ID: "
    . $teamId
    . "<br>";


echo "Local Opponent ID: "
    . $opponentTeamId
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * BUILD REAL HISTORY RECORD
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Build Historical Record<br>";
echo "============================================<br>";


/*
 * FPL stores value in tenths.
 *
 * Example:
 * 55 = £5.5m
 */

$price =
    is_numeric(
        $historyRow[
            'value'
        ]
        ?? null
    )
        ? (
            (float) $historyRow[
                'value'
            ]
        )
        / 10
        : null;


$historyRecord = [

    'gameweek_id' =>
        $gameweekId,

    'player_id' =>
        $playerId,

    'fpl_player_id' =>
        $fplPlayerId,

    'fixture_id' =>
        $fixtureId,

    'fpl_fixture_id' =>
        $fplFixtureId,

    'team_id' =>
        $teamId,

    'opponent_team_id' =>
        $opponentTeamId,

    'was_home' =>
        $wasHome,

    'total_points' =>
        (int) (
            $historyRow[
                'total_points'
            ]
            ?? 0
        ),

    'minutes' =>
        (int) (
            $historyRow[
                'minutes'
            ]
            ?? 0
        ),

    'starts' =>
        (int) (
            $historyRow[
                'starts'
            ]
            ?? 0
        ),

    'goals' =>
        (int) (
            $historyRow[
                'goals_scored'
            ]
            ?? 0
        ),

    'assists' =>
        (int) (
            $historyRow[
                'assists'
            ]
            ?? 0
        ),

    'expected_goals' =>
        $historyRow[
            'expected_goals'
        ]
        ?? null,

    'expected_assists' =>
        $historyRow[
            'expected_assists'
        ]
        ?? null,

    'expected_goal_involvements' =>
        $historyRow[
            'expected_goal_involvements'
        ]
        ?? null,

    'clean_sheets' =>
        (int) (
            $historyRow[
                'clean_sheets'
            ]
            ?? 0
        ),

    'goals_conceded' =>
        (int) (
            $historyRow[
                'goals_conceded'
            ]
            ?? 0
        ),

    'expected_goals_conceded' =>
        $historyRow[
            'expected_goals_conceded'
        ]
        ?? null,

    'saves' =>
        (int) (
            $historyRow[
                'saves'
            ]
            ?? 0
        ),

    'penalties_saved' =>
        (int) (
            $historyRow[
                'penalties_saved'
            ]
            ?? 0
        ),

    'clearances_blocks_interceptions' =>
        (int) (
            $historyRow[
                'clearances_blocks_interceptions'
            ]
            ?? 0
        ),

    'recoveries' =>
        (int) (
            $historyRow[
                'recoveries'
            ]
            ?? 0
        ),

    'tackles' =>
        (int) (
            $historyRow[
                'tackles'
            ]
            ?? 0
        ),

    'defensive_contribution' =>
        (int) (
            $historyRow[
                'defensive_contribution'
            ]
            ?? 0
        ),

    'own_goals' =>
        (int) (
            $historyRow[
                'own_goals'
            ]
            ?? 0
        ),

    'penalties_missed' =>
        (int) (
            $historyRow[
                'penalties_missed'
            ]
            ?? 0
        ),

    'yellow_cards' =>
        (int) (
            $historyRow[
                'yellow_cards'
            ]
            ?? 0
        ),

    'red_cards' =>
        (int) (
            $historyRow[
                'red_cards'
            ]
            ?? 0
        ),

    'bonus' =>
        (int) (
            $historyRow[
                'bonus'
            ]
            ?? 0
        ),

    'bps' =>
        (int) (
            $historyRow[
                'bps'
            ]
            ?? 0
        ),

    'influence' =>
        $historyRow[
            'influence'
        ]
        ?? null,

    'creativity' =>
        $historyRow[
            'creativity'
        ]
        ?? null,

    'threat' =>
        $historyRow[
            'threat'
        ]
        ?? null,

    'ict_index' =>
        $historyRow[
            'ict_index'
        ]
        ?? null,

    'price' =>
        $price,

    'selected' =>
        $historyRow[
            'selected'
        ]
        ?? null,

    'transfers_balance' =>
        $historyRow[
            'transfers_balance'
        ]
        ?? null,

    'transfers_in' =>
        $historyRow[
            'transfers_in'
        ]
        ?? null,

    'transfers_out' =>
        $historyRow[
            'transfers_out'
        ]
        ?? null
];


singlePlayerHistoryCheck(
    'Historical record has valid gameweek identity',
    (
        $historyRecord[
            'gameweek_id'
        ]
        ?? 0
    )
    > 0
);


singlePlayerHistoryCheck(
    'Historical record has valid fixture identity',
    (
        $historyRecord[
            'fixture_id'
        ]
        ?? 0
    )
    > 0
);


singlePlayerHistoryCheck(
    'Historical price is normalised to pounds',
    $price === null
    ||
    (
        is_numeric(
            $price
        )
        &&
        $price > 0
    )
);


echo "FPL Points: "
    . $historyRecord[
        'total_points'
    ]
    . "<br>";


echo "Minutes: "
    . $historyRecord[
        'minutes'
    ]
    . "<br>";


echo "Price: £"
    . (
        is_numeric(
            $price
        )
            ? number_format(
                (float) $price,
                1
            )
            : 'N/A'
    )
    . "m<br><br>";


/*
 * ============================================================
 * SCENARIO G
 * PERSIST REAL HISTORY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Persist Real History<br>";
echo "============================================<br>";


try {

    $historyRepository
        ->upsert(
            $historyRecord
        );


    $persistSucceeded =
        true;

} catch (
    Throwable $exception
) {

    $persistSucceeded =
        false;


    echo "Persist Error: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


singlePlayerHistoryCheck(
    'Real FPL fixture history persists successfully',
    $persistSucceeded
);


$storedHistory =
    $historyRepository
        ->getByPlayerAndFixture(
            $playerId,
            $fixtureId
        );


singlePlayerHistoryCheck(
    'Persisted real history can be retrieved',
    is_array(
        $storedHistory
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * STORED DATA VALIDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Stored Data Validation<br>";
echo "============================================<br>";


singlePlayerHistoryCheck(
    'Stored FPL player identity matches source',
    (
        (int) (
            $storedHistory[
                'fpl_player_id'
            ]
            ?? 0
        )
    )
    ===
    $fplPlayerId
);


singlePlayerHistoryCheck(
    'Stored FPL fixture identity matches source',
    (
        (int) (
            $storedHistory[
                'fpl_fixture_id'
            ]
            ?? 0
        )
    )
    ===
    $fplFixtureId
);


singlePlayerHistoryCheck(
    'Stored gameweek matches source',
    (
        (int) (
            $storedHistory[
                'gameweek_id'
            ]
            ?? 0
        )
    )
    ===
    $gameweekId
);


singlePlayerHistoryCheck(
    'Stored minutes match FPL source',
    (
        (int) (
            $storedHistory[
                'minutes'
            ]
            ?? -1
        )
    )
    ===
    (int) (
        $historyRow[
            'minutes'
        ]
        ?? 0
    )
);


singlePlayerHistoryCheck(
    'Stored total points match FPL source',
    (
        (int) (
            $storedHistory[
                'total_points'
            ]
            ?? 0
        )
    )
    ===
    (int) (
        $historyRow[
            'total_points'
        ]
        ?? 0
    )
);


singlePlayerHistoryCheck(
    'Stored team matches resolved historical team',
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


singlePlayerHistoryCheck(
    'Stored opponent matches resolved historical opponent',
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


if (
    $price !== null
) {

    singlePlayerHistoryCheck(
        'Stored price matches normalised FPL price',
        abs(
            (float) (
                $storedHistory[
                    'price'
                ]
                ?? 0
            )
            -
            $price
        )
        < 0.001
    );

} else {

    singlePlayerHistoryCheck(
        'Missing FPL price remains nullable',
        (
            $storedHistory[
                'price'
            ]
            ?? null
        )
        === null
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * LIVE IDEMPOTENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Live Idempotency<br>";
echo "============================================<br>";


/*
 * Intentionally perform the same real upsert a second time.
 */

$historyRepository
    ->upsert(
        $historyRecord
    );


$duplicateStatement =
    $db->prepare(
        "
        SELECT COUNT(*)
        FROM
            player_fixture_history
        WHERE
            player_id = :player_id
            AND
            fixture_id = :fixture_id
        "
    );


$duplicateStatement
    ->execute([

        ':player_id' =>
            $playerId,

        ':fixture_id' =>
            $fixtureId
    ]);


$duplicateCount =
    (int) $duplicateStatement
        ->fetchColumn();


singlePlayerHistoryCheck(
    'Repeated real import leaves one player/fixture row',
    $duplicateCount === 1
);


echo "<br>";


/*
 * ============================================================
 * CURRENT DATABASE STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Current Fixture History State<br>";
echo "============================================<br>";


$totalHistoryRows =
    $historyRepository
        ->count();


echo "Stored Fixture History Rows: "
    . $totalHistoryRows
    . "<br>";


echo "Imported Player: "
    . htmlspecialchars(
        (string) (
            $player[
                'web_name'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Imported Gameweek: "
    . $fplGameweekId
    . "<br>";


echo "Imported FPL Fixture: "
    . $fplFixtureId
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Single Player Fixture History Import Test Summary<br>";
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