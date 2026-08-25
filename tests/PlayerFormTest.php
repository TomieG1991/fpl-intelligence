<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Form Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function playerFormCheck(
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


try {

    /*
     * ========================================================
     * CONTROLLED PLAYER
     * ========================================================
     */

    $playerStatement =
        $db->prepare(
            "
            SELECT
                id,
                fpl_player_id,
                team_id,
                web_name,
                position
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
            'Raya could not be resolved for PlayerForm testing'
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


    $teamId =
        (int) $player[
            'team_id'
        ];


    /*
     * Select four real future Arsenal fixtures.
     * Together with Raya's real GW1 row this creates
     * a five-fixture recent-form sample.
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
            'Four future fixtures could not be resolved'
        );
    }


    /*
     * Controlled post-GW1 sequence:
     *
     * GW2: 90 mins, 8 points
     * GW3: 90 mins, 7 points
     * GW4: 0 mins, 0 points
     * GW5: 45 mins, 3 points
     */

    $minutes = [
        90,
        90,
        0,
        45
    ];


    $points = [
        8,
        7,
        0,
        3
    ];


    $bps = [
        32,
        28,
        0,
        15
    ];


    $xg = [
        0.00,
        0.00,
        0.00,
        0.00
    ];


    $xa = [
        0.05,
        0.02,
        0.00,
        0.03
    ];


    $xgi = [
        0.05,
        0.02,
        0.00,
        0.03
    ];


    $cleanSheets = [
        1,
        1,
        0,
        0
    ];


    $xgc = [
        0.60,
        0.80,
        0.00,
        0.70
    ];


    foreach (
        $fixtures
        as $index => $fixture
    ) {

        $homeTeamId =
            (int) $fixture[
                'home_team_id'
            ];


        $awayTeamId =
            (int) $fixture[
                'away_team_id'
            ];


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
                    $minutes[
                        $index
                    ] >= 60
                        ? 1
                        : 0,

                'goals' =>
                    0,

                'assists' =>
                    0,

                'expected_goals' =>
                    $xg[
                        $index
                    ],

                'expected_assists' =>
                    $xa[
                        $index
                    ],

                'expected_goal_involvements' =>
                    $xgi[
                        $index
                    ],

                'clean_sheets' =>
                    $cleanSheets[
                        $index
                    ],

                'goals_conceded' =>
                    0,

                'expected_goals_conceded' =>
                    $xgc[
                        $index
                    ],

                'saves' =>
                    $minutes[
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
    echo "Scenario A: Form Model Structure<br>";
    echo "============================================<br>";


    $model =
        $playerForm
            ->buildModel(
                $playerId,
                'GK'
            );


    playerFormCheck(
        'Player Form returns an array',
        is_array(
            $model
        )
    );


    playerFormCheck(
        'Player Form preserves player ID',
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


    playerFormCheck(
        'Player Form preserves position',
        (
            $model[
                'position'
            ]
            ?? null
        )
        ===
        'GK'
    );


    playerFormCheck(
        'Form Rating is numeric',
        is_numeric(
            $model[
                'form_rating'
            ]
            ?? null
        )
    );
    
    
    playerFormCheck(
        'Performance Rating is numeric',
        is_numeric(
            $model[
                'performance_rating'
            ]
            ?? null
        )
    );


    playerFormCheck(
        'Performance Rating remains between 0 and 100',
        (
            (float) (
                $model[
                    'performance_rating'
                ]
                ?? -1
            )
        )
        >= 0
        &&
        (
            (float) (
                $model[
                    'performance_rating'
                ]
                ?? 101
            )
        )
        <= 100
    );


    playerFormCheck(
        'Form Rating remains between 0 and 100',
        (
            (float) (
                $model[
                    'form_rating'
                ]
                ?? -1
            )
        )
        >= 0
        &&
        (
            (float) (
                $model[
                    'form_rating'
                ]
                ?? 101
            )
        )
        <= 100
    );


    echo "Form Rating: "
        . number_format(
            (float) $model[
                'form_rating'
            ],
            2
        )
        . "<br><br>";
        
    echo "Performance Rating: "
        . number_format(
            (float) (
                $model[
                    'performance_rating'
                ]
                ?? 0
            ),
            2
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO B
     * HISTORY SAMPLE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario B: Historical Sample<br>";
    echo "============================================<br>";


    playerFormCheck(
        'Five recent fixtures are represented',
        (
            (int) (
                $model[
                    'fixture_sample_size'
                ]
                ?? 0
            )
        )
        === 5
    );


    playerFormCheck(
        'Four actual appearances are represented',
        (
            (int) (
                $model[
                    'appearance_sample_size'
                ]
                ?? 0
            )
        )
        === 4
    );


    playerFormCheck(
        'One zero-minute row is identified',
        (
            (int) (
                $model[
                    'zero_minute_rows'
                ]
                ?? -1
            )
        )
        === 1
    );


    playerFormCheck(
        'Participation rate is 80 percent',
        abs(
            (
                (float) (
                    $model[
                        'participation_rate'
                    ]
                    ?? 0
                )
            )
            -
            80.0
        )
        < 0.001
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO C
     * RAW METRICS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario C: Raw Form Metrics<br>";
    echo "============================================<br>";


    $raw =
        $model[
            'raw_metrics'
        ]
        ?? [];


    playerFormCheck(
        'Raw metrics are returned',
        is_array(
            $raw
        )
    );


    playerFormCheck(
        'Points per appearance is numeric',
        is_numeric(
            $raw[
                'points_per_appearance'
            ]
            ?? null
        )
    );


    playerFormCheck(
        'Average appearance minutes is numeric',
        is_numeric(
            $raw[
                'average_appearance_minutes'
            ]
            ?? null
        )
    );


    playerFormCheck(
        'BPS per 90 is numeric',
        is_numeric(
            $raw[
                'bps_per_90'
            ]
            ?? null
        )
    );


    playerFormCheck(
        'Clean-sheet rate is numeric for goalkeeper sample',
        is_numeric(
            $raw[
                'clean_sheet_rate'
            ]
            ?? null
        )
    );


    echo "Points / Appearance: "
        . number_format(
            (float) (
                $raw[
                    'points_per_appearance'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "BPS / 90: "
        . number_format(
            (float) (
                $raw[
                    'bps_per_90'
                ]
                ?? 0
            ),
            2
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO D
     * COMPONENT RATINGS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario D: Component Ratings<br>";
    echo "============================================<br>";


    $components =
        $model[
            'component_ratings'
        ]
        ?? [];


    foreach (
        [
            'points_rating',
            'minutes_rating',
            'bps_rating',
            'defensive_rating'
        ]
        as $component
    ) {

        playerFormCheck(
            $component
            . ' is numeric and bounded',
            is_numeric(
                $components[
                    $component
                ]
                ?? null
            )
            &&
            (
                (float) $components[
                    $component
                ]
            )
            >= 0
            &&
            (
                (float) $components[
                    $component
                ]
            )
            <= 100
        );
    }


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO E
     * POSITION-AWARE WEIGHTS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario E: Position-Aware Form Weights<br>";
    echo "============================================<br>";


    $gkWeights =
        $playerForm
            ->getWeights(
                'GK'
            );


    $midWeights =
        $playerForm
            ->getWeights(
                'MID'
            );


    $fwdWeights =
        $playerForm
            ->getWeights(
                'FWD'
            );


    playerFormCheck(
        'Goalkeeper Form uses defensive weighting',
        (
            (float) (
                $gkWeights[
                    'defensive'
                ]
                ?? 0
            )
        )
        > 0
    );


    playerFormCheck(
        'Midfielder Form uses attacking xGI weighting',
        (
            (float) (
                $midWeights[
                    'xgi'
                ]
                ?? 0
            )
        )
        > 0
    );


    playerFormCheck(
        'Forward xGI weighting exceeds midfielder xGI weighting',
        (
            (float) (
                $fwdWeights[
                    'xgi'
                ]
                ?? 0
            )
        )
        >
        (
            (float) (
                $midWeights[
                    'xgi'
                ]
                ?? 0
            )
        )
    );


    playerFormCheck(
        'Midfielder does not use defensive component',
        (
            (float) (
                $midWeights[
                    'defensive'
                ]
                ?? -1
            )
        )
        === 0.0
    );


    echo "<br>";


    /*
     * ========================================================
     * SCENARIO F
     * ZERO-MINUTE PARTICIPATION EFFECT
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario F: Participation Effect<br>";
    echo "============================================<br>";


    $minutesRating =
        (float) (
            $components[
                'minutes_rating'
            ]
            ?? 0
        );


    playerFormCheck(
        'Zero-minute fixture prevents perfect Minutes Rating',
        $minutesRating < 100
    );


    playerFormCheck(
        'Minutes Rating remains positive',
        $minutesRating > 0
    );


    echo "Minutes Rating: "
        . number_format(
            $minutesRating,
            2
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO G
     * POSITION OUTPUT DIFFERENCE
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario G: Position-Aware Output<br>";
    echo "============================================<br>";


    $midModel =
        $playerForm
            ->buildModel(
                $playerId,
                'MID'
            );


    playerFormCheck(
        'Same history can produce different position-aware Form Rating',
        abs(
            (
                (float) (
                    $model[
                        'form_rating'
                    ]
                    ?? 0
                )
            )
            -
            (
                (float) (
                    $midModel[
                        'form_rating'
                    ]
                    ?? 0
                )
            )
        )
        > 0.001
    );


    echo "GK Form Rating: "
        . number_format(
            (float) $model[
                'form_rating'
            ],
            2
        )
        . "<br>";


    echo "MID Form Rating: "
        . number_format(
            (float) $midModel[
                'form_rating'
            ],
            2
        )
        . "<br><br>";


    /*
     * ========================================================
     * SCENARIO H
     * INVALID PLAYER
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario H: Invalid Player<br>";
    echo "============================================<br>";


    $invalidModel =
        $playerForm
            ->buildModel(
                0,
                'MID'
            );


    playerFormCheck(
        'Invalid player has no Form Rating',
        (
            $invalidModel[
                'form_rating'
            ]
            ?? null
        )
        === null
    );


    playerFormCheck(
        'Invalid player has zero fixture sample',
        (
            (int) (
                $invalidModel[
                    'fixture_sample_size'
                ]
                ?? -1
            )
        )
        === 0
    );


    playerFormCheck(
        'Invalid player has zero appearance sample',
        (
            (int) (
                $invalidModel[
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
     * SCENARIO I
     * EXTREME VALUE BOUNDS
     * ========================================================
     */

    echo "============================================<br>";
    echo "Scenario I: Rating Bounds<br>";
    echo "============================================<br>";


    foreach (
        $components
        as $rating
    ) {

        if ($rating === null) {

            continue;
        }


        playerFormCheck(
            'Component remains within 0-100 scale',
            (float) $rating >= 0
            &&
            (float) $rating <= 100
        );
    }


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


echo "============================================<br>";
echo "Player Form Test Summary<br>";
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