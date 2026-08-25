<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "Starting Player Fixture History Update...\n";


/*
 * ============================================================
 * IMPORT CONFIGURATION
 * ============================================================
 *
 * Supported browser examples:
 *
 * Small diagnostic batch:
 * ?limit=25&offset=0
 *
 * Resume later in the player pool:
 * ?limit=25&offset=100
 *
 * Process the entire current player pool:
 * ?full=1
 *
 * A small delay is applied between live FPL player-summary
 * requests to avoid sending hundreds of requests back-to-back.
 */

$fullImport =
    isset(
        $_GET[
            'full'
        ]
    )
    &&
    (
        (string) $_GET[
            'full'
        ]
    )
    ===
    '1';


$limit =
    isset(
        $_GET[
            'limit'
        ]
    )
    &&
    is_numeric(
        $_GET[
            'limit'
        ]
    )
        ? (int) $_GET[
            'limit'
        ]
        : 25;


$offset =
    isset(
        $_GET[
            'offset'
        ]
    )
    &&
    is_numeric(
        $_GET[
            'offset'
        ]
    )
        ? (int) $_GET[
            'offset'
        ]
        : 0;


/*
 * Normal manual batches stay capped at 100.
 *
 * Full mode ignores the manual batch limit and resolves the
 * entire player pool after the database connection is ready.
 */
$limit =
    max(
        1,
        min(
            100,
            $limit
        )
    );


$offset =
    max(
        0,
        $offset
    );


/*
 * Microseconds between API requests.
 *
 * 100000 = 0.1 seconds.
 */
$requestDelayMicroseconds =
    100000;


echo "Import mode: "
    . (
        $fullImport
            ? 'FULL'
            : 'BATCH'
    )
    . "\n";


if (
    !$fullImport
) {

    echo "Batch limit: "
        . $limit
        . "\n";


    echo "Batch offset: "
        . $offset
        . "\n";
}


echo "API request delay: "
    . number_format(
        $requestDelayMicroseconds
        /
        1000000,
        2
    )
    . " seconds\n";


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


    echo "Database connection successful\n";


    $fplApi =
        new FPLApi();


    echo "FPL API connection successful\n";


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

    echo "SETUP FAILED ❌\n";


    echo $exception
        ->getMessage();


    exit;
}


/*
 * ============================================================
 * LOAD PLAYER POOL
 * ============================================================
 */

$totalPlayerCount =
    (int) $db
        ->query(
            "
            SELECT COUNT(*)
            FROM players
            WHERE fpl_player_id > 0
            "
        )
        ->fetchColumn();


if (
    $fullImport
) {

    $playerStatement =
        $db->prepare(
            "
            SELECT
                id,
                fpl_player_id,
                web_name
            FROM
                players
            WHERE
                fpl_player_id > 0
            ORDER BY
                id ASC
            "
        );


    $playerStatement
        ->execute();

} else {

    $playerStatement =
        $db->prepare(
            "
            SELECT
                id,
                fpl_player_id,
                web_name
            FROM
                players
            WHERE
                fpl_player_id > 0
            ORDER BY
                id ASC
            LIMIT :limit
            OFFSET :offset
            "
        );


    $playerStatement
        ->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );


    $playerStatement
        ->bindValue(
            ':offset',
            $offset,
            PDO::PARAM_INT
        );


    $playerStatement
        ->execute();
}


$players =
    $playerStatement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


echo "Total eligible players: "
    . $totalPlayerCount
    . "\n";


echo "Players selected for this run: "
    . count(
        $players
    )
    . "\n";


if (
    empty(
        $players
    )
) {

    echo "No players found for this import.\n";

    exit;
}


/*
 * ============================================================
 * PREPARE LOCAL FIXTURE LOOKUP
 * ============================================================
 */

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
            fpl_fixture_id = :fpl_fixture_id
        LIMIT 1
        "
    );


/*
 * ============================================================
 * PREPARE OPPONENT LOOKUP
 * ============================================================
 */

$teamStatement =
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


/*
 * ============================================================
 * COUNTERS
 * ============================================================
 */

$playersProcessed =
    0;


$playersFailed =
    0;


$historyRowsFound =
    0;


$historyRowsImported =
    0;


$historyRowsSkipped =
    0;


/*
 * ============================================================
 * PROCESS PLAYERS
 * ============================================================
 */

foreach (
    $players
    as $index => $player
) {

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


    $playerName =
        (string) (
            $player[
                'web_name'
            ]
            ?? (
                'Player '
                . $fplPlayerId
            )
        );


    if (
        $playerId <= 0
        ||
        $fplPlayerId <= 0
    ) {

        $playersFailed++;

        echo "PLAYER SKIPPED: Invalid player identity\n";

        continue;
    }
    
    $runPosition =
        $index
        +
        1;


    echo "\n";


    echo "["
        . $runPosition
        . "/"
        . count(
            $players
        )
        . "] ";


    echo "Processing: "
        . $playerName
        . " (FPL "
        . $fplPlayerId
        . ")\n";


    /*
     * --------------------------------------------------------
     * FETCH LIVE PLAYER HISTORY
     * --------------------------------------------------------
     */

    try {

        $summary =
            $fplApi
                ->getPlayerSummary(
                    $fplPlayerId
                );

    } catch (
        Throwable $exception
    ) {

        $playersFailed++;


        echo "  API FAILED: "
            . $exception
                ->getMessage()
            . "\n";


        /*
         * Respect the normal request delay even after
         * a failed live API request.
         */
        if (
            $runPosition
            <
            count(
                $players
            )
        ) {

            usleep(
                $requestDelayMicroseconds
            );
        }


        continue;
    }


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


    $playersProcessed++;


    echo "  History rows: "
        . count(
            $history
        )
        . "\n";


    /*
     * --------------------------------------------------------
     * PROCESS PLAYER HISTORY
     * --------------------------------------------------------
     */

    foreach (
        $history
        as $historyRow
    ) {

        if (
            !is_array(
                $historyRow
            )
        ) {

            $historyRowsSkipped++;

            continue;
        }


        $historyRowsFound++;


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


        if (
            $fplFixtureId <= 0
            ||
            $fplGameweekId <= 0
        ) {

            $historyRowsSkipped++;

            echo "  HISTORY SKIPPED: Invalid fixture/gameweek identity\n";

            continue;
        }


        /*
         * ----------------------------------------------------
         * RESOLVE LOCAL GAMEWEEK
         * ----------------------------------------------------
         */

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

            $historyRowsSkipped++;


            echo "  HISTORY SKIPPED: GW"
                . $fplGameweekId
                . " is not stored locally\n";


            continue;
        }


        $gameweekId =
            (int) (
                $gameweek[
                    'id'
                ]
                ?? 0
            );


        if ($gameweekId <= 0) {

            $historyRowsSkipped++;

            continue;
        }


        /*
         * ----------------------------------------------------
         * RESOLVE LOCAL FIXTURE
         * ----------------------------------------------------
         */

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


        if (
            !is_array(
                $fixture
            )
        ) {

            $historyRowsSkipped++;


            echo "  HISTORY SKIPPED: FPL fixture "
                . $fplFixtureId
                . " is not stored locally\n";


            continue;
        }


        $fixtureId =
            (int) (
                $fixture[
                    'id'
                ]
                ?? 0
            );


        $fixtureGameweek =
            (int) (
                $fixture[
                    'gameweek'
                ]
                ?? 0
            );


        if (
            $fixtureId <= 0
            ||
            $fixtureGameweek !== $fplGameweekId
        ) {

            $historyRowsSkipped++;


            echo "  HISTORY SKIPPED: Fixture/gameweek mismatch\n";


            continue;
        }


        /*
         * ----------------------------------------------------
         * HISTORICAL TEAM CONTEXT
         * ----------------------------------------------------
         *
         * Use was_home + the actual fixture to determine
         * which club this player represented in this match.
         *
         * Do not use players.team_id because the player may
         * later transfer clubs.
         */

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


        $teamId =
            $wasHome
                ? $homeTeamId
                : $awayTeamId;


        $opponentTeamId =
            $wasHome
                ? $awayTeamId
                : $homeTeamId;


        if (
            $teamId <= 0
            ||
            $opponentTeamId <= 0
            ||
            $teamId === $opponentTeamId
        ) {

            $historyRowsSkipped++;


            echo "  HISTORY SKIPPED: Invalid team context\n";


            continue;
        }


        /*
         * ----------------------------------------------------
         * CROSS-CHECK OFFICIAL FPL OPPONENT
         * ----------------------------------------------------
         */

        $fplOpponentTeamId =
            (int) (
                $historyRow[
                    'opponent_team'
                ]
                ?? 0
            );


        if ($fplOpponentTeamId > 0) {

            $teamStatement
                ->execute([

                    ':fpl_team_id' =>
                        $fplOpponentTeamId
                ]);


            $expectedOpponentTeamId =
                $teamStatement
                    ->fetchColumn();


            if (
                $expectedOpponentTeamId === false
                ||
                (int) $expectedOpponentTeamId
                !==
                $opponentTeamId
            ) {

                $historyRowsSkipped++;


                echo "  HISTORY SKIPPED: Opponent mismatch\n";


                continue;
            }
        }


        /*
         * ----------------------------------------------------
         * NORMALISE PRICE
         * ----------------------------------------------------
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


        /*
         * ----------------------------------------------------
         * BUILD HISTORY RECORD
         * ----------------------------------------------------
         */

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


        /*
         * ----------------------------------------------------
         * PERSIST HISTORY
         * ----------------------------------------------------
         */

        try {

            $historyRepository
                ->upsert(
                    $historyRecord
                );


            $historyRowsImported++;

        } catch (
            Throwable $exception
        ) {

            $historyRowsSkipped++;


            echo "  HISTORY FAILED: "
                . $exception
                    ->getMessage()
                . "\n";
        }
    }
    
    /*
     * --------------------------------------------------------
     * REQUEST THROTTLING
     * --------------------------------------------------------
     *
     * Avoid firing the next live player-summary request
     * immediately after the previous one.
     */

    if (
        $runPosition
        <
        count(
            $players
        )
    ) {

        usleep(
            $requestDelayMicroseconds
        );
    }
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "\n";
echo "============================================\n";
echo "Player Fixture History Update Summary\n";
echo "============================================\n";


echo "Players processed: "
    . $playersProcessed
    . "\n";
    
    
echo "Players selected: "
    . count(
        $players
    )
    . "\n";


echo "Players failed: "
    . $playersFailed
    . "\n";


echo "History rows found: "
    . $historyRowsFound
    . "\n";


echo "History rows imported: "
    . $historyRowsImported
    . "\n";


echo "History rows skipped: "
    . $historyRowsSkipped
    . "\n";


echo "Stored history rows: "
    . $historyRepository
        ->count()
    . "\n";
    
echo "Import mode: "
    . (
        $fullImport
            ? 'FULL'
            : 'BATCH'
    )
    . "\n";


echo "Update complete\n";