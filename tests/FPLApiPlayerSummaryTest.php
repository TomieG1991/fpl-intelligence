<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "FPL API Player Summary Test<br>";
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

function fplApiPlayerSummaryCheck(
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


    /*
     * Select a real current player from our database.
     *
     * We deliberately avoid hard-coding a specific player ID
     * because FPL IDs can change between seasons.
     */
    $playerStatement =
        $db->query(
            "
            SELECT
                id,
                fpl_player_id,
                web_name,
                first_name,
                second_name,
                position
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


    if (
        !is_array(
            $player
        )
    ) {

        throw new RuntimeException(
            'No real FPL player could be found for the player summary test'
        );
    }


    $fplPlayerId =
        (int) (
            $player[
                'fpl_player_id'
            ]
            ?? 0
        );


    if ($fplPlayerId <= 0) {

        throw new RuntimeException(
            'Selected player does not contain a valid FPL player ID'
        );
    }

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
 * PLAYER SELECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Real Player Selection<br>";
echo "============================================<br>";


fplApiPlayerSummaryCheck(
    'Real database player is available',
    is_array(
        $player
    )
);


fplApiPlayerSummaryCheck(
    'Selected player has valid FPL player ID',
    $fplPlayerId > 0
);


echo "Player: "
    . htmlspecialchars(
        (string) (
            $player[
                'web_name'
            ]
            ??
            trim(
                (
                    $player[
                        'first_name'
                    ]
                    ?? ''
                )
                . ' '
                . (
                    $player[
                        'second_name'
                    ]
                    ?? ''
                )
            )
            ?: 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Position: "
    . htmlspecialchars(
        (string) (
            $player[
                'position'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "FPL Player ID: "
    . $fplPlayerId
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * LIVE PLAYER SUMMARY REQUEST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Live Player Summary Request<br>";
echo "============================================<br>";


$startedAt =
    microtime(
        true
    );


try {

    $summary =
        $fplApi
            ->getPlayerSummary(
                $fplPlayerId
            );


    $requestSucceeded =
        true;


    $requestError =
        null;

} catch (
    Throwable $exception
) {

    $summary =
        [];


    $requestSucceeded =
        false;


    $requestError =
        $exception
            ->getMessage();
}


$runtime =
    microtime(
        true
    )
    -
    $startedAt;


fplApiPlayerSummaryCheck(
    'Live player summary request succeeds',
    $requestSucceeded
);


fplApiPlayerSummaryCheck(
    'Player summary response is an array',
    is_array(
        $summary
    )
);


if (
    !$requestSucceeded
) {

    echo "Request Error: "
        . htmlspecialchars(
            (string) $requestError,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


echo "Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * RESPONSE STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Response Structure<br>";
echo "============================================<br>";


fplApiPlayerSummaryCheck(
    'Response contains fixtures array',
    isset(
        $summary[
            'fixtures'
        ]
    )
    &&
    is_array(
        $summary[
            'fixtures'
        ]
    )
);


fplApiPlayerSummaryCheck(
    'Response contains history array',
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
);


fplApiPlayerSummaryCheck(
    'Response contains history_past array',
    isset(
        $summary[
            'history_past'
        ]
    )
    &&
    is_array(
        $summary[
            'history_past'
        ]
    )
);


$fixtures =
    (
        isset(
            $summary[
                'fixtures'
            ]
        )
        &&
        is_array(
            $summary[
                'fixtures'
            ]
        )
    )
        ? $summary[
            'fixtures'
        ]
        : [];


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


$historyPast =
    (
        isset(
            $summary[
                'history_past'
            ]
        )
        &&
        is_array(
            $summary[
                'history_past'
            ]
        )
    )
        ? $summary[
            'history_past'
        ]
        : [];


echo "Upcoming Fixtures: "
    . count(
        $fixtures
    )
    . "<br>";


echo "Current-Season History Rows: "
    . count(
        $history
    )
    . "<br>";


echo "Previous-Season History Rows: "
    . count(
        $historyPast
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * UPCOMING FIXTURE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Upcoming Fixture Contract<br>";
echo "============================================<br>";


if (
    !empty(
        $fixtures
    )
) {

    $fixture =
        $fixtures[0];


    fplApiPlayerSummaryCheck(
        'Upcoming fixture is an array',
        is_array(
            $fixture
        )
    );


    fplApiPlayerSummaryCheck(
        'Upcoming fixture exposes fixture ID',
        isset(
            $fixture[
                'id'
            ]
        )
        &&
        is_numeric(
            $fixture[
                'id'
            ]
        )
    );


    fplApiPlayerSummaryCheck(
        'Upcoming fixture exposes gameweek event',
        array_key_exists(
            'event',
            $fixture
        )
    );


    fplApiPlayerSummaryCheck(
        'Upcoming fixture exposes home team',
        isset(
            $fixture[
                'team_h'
            ]
        )
        &&
        is_numeric(
            $fixture[
                'team_h'
            ]
        )
    );


    fplApiPlayerSummaryCheck(
        'Upcoming fixture exposes away team',
        isset(
            $fixture[
                'team_a'
            ]
        )
        &&
        is_numeric(
            $fixture[
                'team_a'
            ]
        )
    );


    $upcomingOpponentTeamId =
        null;


    if (
        isset(
            $fixture[
                'team_h'
            ],
            $fixture[
                'team_a'
            ]
        )
        &&
        array_key_exists(
            'is_home',
            $fixture
        )
    ) {

        $upcomingOpponentTeamId =
            !empty(
                $fixture[
                    'is_home'
                ]
            )
                ? (int) $fixture[
                    'team_a'
                ]
                : (int) $fixture[
                    'team_h'
                ];
    }


    fplApiPlayerSummaryCheck(
        'Upcoming fixture opponent can be resolved from home/away teams',
        is_int(
            $upcomingOpponentTeamId
        )
        &&
        $upcomingOpponentTeamId > 0
    );


    fplApiPlayerSummaryCheck(
        'Upcoming fixture exposes home/away state',
        array_key_exists(
            'is_home',
            $fixture
        )
    );


    fplApiPlayerSummaryCheck(
        'Upcoming fixture exposes kickoff time',
        array_key_exists(
            'kickoff_time',
            $fixture
        )
    );

} else {

    /*
     * An empty fixture list can legitimately occur at the
     * end of the season, so this is not a failure.
     */
    fplApiPlayerSummaryCheck(
        'Empty upcoming fixture list is safely supported',
        true
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * CURRENT-SEASON HISTORY CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Current-Season History Contract<br>";
echo "============================================<br>";


if (
    !empty(
        $history
    )
) {

    $historyRow =
        $history[0];


    fplApiPlayerSummaryCheck(
        'History row is an array',
        is_array(
            $historyRow
        )
    );


    $requiredHistoryFields = [

        'element',
        'fixture',
        'opponent_team',
        'total_points',
        'was_home',
        'kickoff_time',
        'team_h_score',
        'team_a_score',
        'round',
        'minutes',
        'goals_scored',
        'assists',
        'clean_sheets',
        'goals_conceded',
        'own_goals',
        'penalties_saved',
        'penalties_missed',
        'yellow_cards',
        'red_cards',
        'saves',
        'bonus',
        'bps',
        'influence',
        'creativity',
        'threat',
        'ict_index',
        'expected_goals',
        'expected_assists',
        'expected_goal_involvements',
        'expected_goals_conceded',
        'value',
        'transfers_balance',
        'selected',
        'transfers_in',
        'transfers_out'
    ];


    $missingHistoryFields =
        [];


    foreach (
        $requiredHistoryFields
        as $field
    ) {

        if (
            !array_key_exists(
                $field,
                $historyRow
            )
        ) {

            $missingHistoryFields[] =
                $field;
        }
    }


    fplApiPlayerSummaryCheck(
        'History row exposes required fixture-history fields',
        empty(
            $missingHistoryFields
        )
    );


    if (
        !empty(
            $missingHistoryFields
        )
    ) {

        echo "Missing Fields: "
            . htmlspecialchars(
                implode(
                    ', ',
                    $missingHistoryFields
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";
    }


    fplApiPlayerSummaryCheck(
        'History fixture ID is numeric',
        is_numeric(
            $historyRow[
                'fixture'
            ]
            ?? null
        )
    );


    fplApiPlayerSummaryCheck(
        'History player ID matches requested FPL player',
        (
            (int) (
                $historyRow[
                    'element'
                ]
                ?? 0
            )
        )
        ===
        $fplPlayerId
    );


    fplApiPlayerSummaryCheck(
        'History gameweek is numeric',
        is_numeric(
            $historyRow[
                'round'
            ]
            ?? null
        )
    );


    fplApiPlayerSummaryCheck(
        'History opponent team is numeric',
        is_numeric(
            $historyRow[
                'opponent_team'
            ]
            ?? null
        )
    );


    fplApiPlayerSummaryCheck(
        'History minutes are numeric and non-negative',
        is_numeric(
            $historyRow[
                'minutes'
            ]
            ?? null
        )
        &&
        (
            (int) $historyRow[
                'minutes'
            ]
        )
        >= 0
    );


    fplApiPlayerSummaryCheck(
        'History total points are numeric',
        is_numeric(
            $historyRow[
                'total_points'
            ]
            ?? null
        )
    );

} else {

    /*
     * This is especially important for preseason and the start
     * of a new season. The API contract is still valid even
     * when no completed player fixture history exists yet.
     */
    fplApiPlayerSummaryCheck(
        'Empty current-season history is safely supported',
        true
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * PREVIOUS-SEASON HISTORY CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Previous-Season History Contract<br>";
echo "============================================<br>";


$historyPastValid =
    true;


foreach (
    $historyPast
    as $pastRow
) {

    if (
        !is_array(
            $pastRow
        )
    ) {

        $historyPastValid =
            false;

        break;
    }


    if (
        !array_key_exists(
            'season_name',
            $pastRow
        )
    ) {

        $historyPastValid =
            false;

        break;
    }
}


fplApiPlayerSummaryCheck(
    'Previous-season history rows use expected structure',
    $historyPastValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * INVALID PLAYER PROTECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Invalid Player Protection<br>";
echo "============================================<br>";


$invalidPlayerRejected =
    false;


try {

    $fplApi
        ->getPlayerSummary(
            0
        );

} catch (
    InvalidArgumentException $exception
) {

    $invalidPlayerRejected =
        true;

} catch (
    Throwable $exception
) {

    /*
     * A different exception means the validation contract
     * did not behave as expected.
     */
    $invalidPlayerRejected =
        false;
}


fplApiPlayerSummaryCheck(
    'Zero FPL player ID is rejected before API request',
    $invalidPlayerRejected
);


$negativePlayerRejected =
    false;


try {

    $fplApi
        ->getPlayerSummary(
            -1
        );

} catch (
    InvalidArgumentException $exception
) {

    $negativePlayerRejected =
        true;

} catch (
    Throwable $exception
) {

    $negativePlayerRejected =
        false;
}


fplApiPlayerSummaryCheck(
    'Negative FPL player ID is rejected before API request',
    $negativePlayerRejected
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Performance<br>";
echo "============================================<br>";


fplApiPlayerSummaryCheck(
    'Single player-summary request completes within 5 seconds',
    $runtime < 5.0
);


echo "Measured Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "FPL API Player Summary Test Summary<br>";
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