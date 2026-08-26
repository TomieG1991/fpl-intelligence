<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Profile Multi-Gameweek Test<br>";
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

function playerProfileMultiGameweekCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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
 * DATABASE / SERVICE
 * ============================================================
 */

$database =
    new Database();


$connection =
    $database
        ->getConnection();


$service =
    new PlayerIntelligenceService(
        $connection
    );


$playerRepository =
    new PlayerRepository(
        $connection
    );


$fixtureRepository =
    new FixtureRepository(
        $connection
    );


$players =
    $playerRepository
        ->getAll();


echo "Players Loaded: "
    . count(
        $players
    )
    . "<br><br>";


/*
 * ============================================================
 * RESOLVE REAL PLAYER
 * ============================================================
 *
 * Select a real player whose team currently has at least six
 * upcoming fixtures.
 *
 * This avoids hard-coding a player and keeps the test valid as
 * the season progresses.
 */

$selectedPlayer =
    null;


foreach (
    $players
    as $player
) {

    $playerId =
        (int) (
            $player[
                'id'
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


    if (
        $playerId <= 0
        ||
        $teamId <= 0
    ) {

        continue;
    }


    $upcomingFixtures =
        $fixtureRepository
            ->getUpcomingForTeam(
                $teamId,
                6
            );


    if (
        count(
            $upcomingFixtures
        )
        <
        6
    ) {

        continue;
    }


    $selectedPlayer =
        $player;

    break;
}


/*
 * ============================================================
 * SCENARIO A
 * REAL PLAYER RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Real Player Resolution<br>";
echo "============================================<br>";


playerProfileMultiGameweekCheck(
    'A real player with six upcoming fixtures resolves',
    is_array(
        $selectedPlayer
    )
);


if (
    $selectedPlayer === null
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit;
}


$playerId =
    (int) (
        $selectedPlayer[
            'id'
        ]
        ?? 0
    );


$teamId =
    (int) (
        $selectedPlayer[
            'team_id'
        ]
        ?? 0
    );


$playerName =
    trim(
        (string) (
            $selectedPlayer[
                'web_name'
            ]
            ??
            $selectedPlayer[
                'player_name'
            ]
            ??
            $selectedPlayer[
                'name'
            ]
            ??
            'Unknown'
        )
    );


$position =
    strtoupper(
        trim(
            (string) (
                $selectedPlayer[
                    'position'
                ]
                ?? ''
            )
        )
    );


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Player ID: "
    . $playerId
    . "<br>";


echo "Team ID: "
    . $teamId
    . "<br>";


echo "Position: "
    . htmlspecialchars(
        $position,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * BUILD EXISTING PLAYER PROFILE
 * ============================================================
 */

$profile =
    $service
        ->getPlayerProfile(
            $playerId
        );


/*
 * ============================================================
 * SCENARIO B
 * EXISTING PROFILE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Existing Profile Contract<br>";
echo "============================================<br>";


playerProfileMultiGameweekCheck(
    'Player profile still resolves',
    is_array(
        $profile
    )
);


playerProfileMultiGameweekCheck(
    'Player profile preserves player ID',
    (
        (int) (
            $profile[
                'player'
            ][
                'player_id'
            ]
            ?? 0
        )
    )
    ===
    $playerId
);


playerProfileMultiGameweekCheck(
    'Existing Projected Points remains available',
    is_numeric(
        $profile[
            'summary'
        ][
            'projected_points'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * MULTI-GAMEWEEK PROFILE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Multi-Gameweek Profile Contract<br>";
echo "============================================<br>";


$multiGameweek =
    $profile[
        'multi_gameweek_expected_points'
    ]
    ?? null;


playerProfileMultiGameweekCheck(
    'Player profile exposes multi-gameweek Expected Points',
    is_array(
        $multiGameweek
    )
);


if (
    !is_array(
        $multiGameweek
    )
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "Attach getPlayerMultiGameweekExpectedPoints() to getPlayerProfile().<br><br>";


    echo "============================================<br>";
    echo "Player Profile Multi-Gameweek Test Summary<br>";
    echo "============================================<br>";


    echo "Passed: "
        . $passed
        . "<br>";


    echo "Failed: "
        . $failed
        . "<br><br>";


    echo "RESULT: TESTS FAILED ❌";

    exit;
}


playerProfileMultiGameweekCheck(
    'Multi-gameweek projection is explicitly available',
    (
        $multiGameweek[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


playerProfileMultiGameweekCheck(
    'Multi-gameweek block preserves player ID',
    (
        (int) (
            $multiGameweek[
                'player_id'
            ]
            ?? 0
        )
    )
    ===
    $playerId
);


playerProfileMultiGameweekCheck(
    'Multi-gameweek block preserves team ID',
    (
        (int) (
            $multiGameweek[
                'team_id'
            ]
            ?? 0
        )
    )
    ===
    $teamId
);


playerProfileMultiGameweekCheck(
    'Multi-gameweek block preserves position',
    (
        $multiGameweek[
            'position'
        ]
        ?? null
    )
    ===
    $position
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * FIXTURE PROJECTIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Fixture Projections<br>";
echo "============================================<br>";


$fixtureRows =
    $multiGameweek[
        'fixtures'
    ]
    ?? [];


playerProfileMultiGameweekCheck(
    'Profile contains six future fixture projections',
    is_array(
        $fixtureRows
    )
    &&
    count(
        $fixtureRows
    )
    ===
    6
);


$allProjected =
    count(
        $fixtureRows
    )
    ===
    6;


$projectedPoints =
    [];


$gameweeks =
    [];


foreach (
    $fixtureRows
    as $fixtureRow
) {

    $projection =
        $fixtureRow[
            'projection'
        ]
        ?? null;


    if (
        !is_array(
            $projection
        )
        ||
        !is_numeric(
            $projection[
                'projected_points'
            ]
            ?? null
        )
    ) {

        $allProjected =
            false;

        continue;
    }


    $projectedPoints[] =
        (float) $projection[
            'projected_points'
        ];


    $gameweeks[] =
        (int) (
            $fixtureRow[
                'gameweek'
            ]
            ?? 0
        );
}


playerProfileMultiGameweekCheck(
    'All six future fixtures have numeric Projected Points',
    $allProjected
    &&
    count(
        $projectedPoints
    )
    ===
    6
);


playerProfileMultiGameweekCheck(
    'All six future fixtures expose opponent names',
    count(
        array_filter(
            $fixtureRows,
            static function (
                array $fixtureRow
            ): bool {

                return trim(
                    (string) (
                        $fixtureRow[
                            'opponent_name'
                        ]
                        ?? ''
                    )
                )
                !== '';
            }
        )
    )
    ===
    count(
        $fixtureRows
    )
);


$sortedGameweeks =
    $gameweeks;


sort(
    $sortedGameweeks
);


playerProfileMultiGameweekCheck(
    'Future fixtures remain in chronological gameweek order',
    $gameweeks
    ===
    $sortedGameweeks
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * PLANNING HORIZONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Planning Horizons<br>";
echo "============================================<br>";


$next3 =
    $multiGameweek[
        'next_3'
    ]
    ?? null;


$next5 =
    $multiGameweek[
        'next_5'
    ]
    ?? null;


$next6 =
    $multiGameweek[
        'next_6'
    ]
    ?? null;


playerProfileMultiGameweekCheck(
    'Next 3 is numeric',
    is_numeric(
        $next3
    )
);


playerProfileMultiGameweekCheck(
    'Next 5 is numeric',
    is_numeric(
        $next5
    )
);


playerProfileMultiGameweekCheck(
    'Next 6 is numeric',
    is_numeric(
        $next6
    )
);


$calculatedNext3 =
    array_sum(
        array_slice(
            $projectedPoints,
            0,
            3
        )
    );


$calculatedNext5 =
    array_sum(
        array_slice(
            $projectedPoints,
            0,
            5
        )
    );


$calculatedNext6 =
    array_sum(
        array_slice(
            $projectedPoints,
            0,
            6
        )
    );


playerProfileMultiGameweekCheck(
    'Next 3 matches first three fixture projections',
    is_numeric(
        $next3
    )
    &&
    abs(
        (float) $next3
        -
        $calculatedNext3
    )
    <
    0.02
);


playerProfileMultiGameweekCheck(
    'Next 5 matches first five fixture projections',
    is_numeric(
        $next5
    )
    &&
    abs(
        (float) $next5
        -
        $calculatedNext5
    )
    <
    0.02
);


playerProfileMultiGameweekCheck(
    'Next 6 matches all six fixture projections',
    is_numeric(
        $next6
    )
    &&
    abs(
        (float) $next6
        -
        $calculatedNext6
    )
    <
    0.02
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * EXISTING NEXT-FIXTURE PROJECTION PRESERVED
 * ============================================================
 *
 * Adding planning intelligence must not replace or mutate the
 * existing immediate Expected Points projection.
 */

echo "============================================<br>";
echo "Scenario F: Immediate Projection Preservation<br>";
echo "============================================<br>";


$immediateProjectedPoints =
    $profile[
        'summary'
    ][
        'projected_points'
    ]
    ?? null;


$firstFutureProjection =
    $fixtureRows[
        0
    ][
        'projection'
    ]
    ?? [];


playerProfileMultiGameweekCheck(
    'Immediate Expected Points remains numeric',
    is_numeric(
        $immediateProjectedPoints
    )
);


playerProfileMultiGameweekCheck(
    'First multi-gameweek fixture remains numeric',
    is_numeric(
        $firstFutureProjection[
            'projected_points'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * PROFILE DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Player Profile Planning Diagnostic<br>";
echo "============================================<br>";


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Position: "
    . htmlspecialchars(
        $position,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


echo "Immediate Next Fixture xP: "
    . (
        is_numeric(
            $immediateProjectedPoints
        )
            ? number_format(
                (float) $immediateProjectedPoints,
                2
            )
            : 'Unavailable'
    )
    . "<br><br>";


foreach (
    $fixtureRows
    as $fixtureRow
) {

    $projection =
        $fixtureRow[
            'projection'
        ]
        ?? [];


    $components =
        $projection[
            'components'
        ]
        ?? [];


    $inputs =
        $projection[
            'inputs'
        ]
        ?? [];


    echo "--------------------------------------------<br>";


    echo "GW"
        . (
            $fixtureRow[
                'gameweek'
            ]
            ?? '—'
        )
        . " — Fixture "
        . (
            $fixtureRow[
                'fixture_id'
            ]
            ?? '—'
        )
        . " — "
        . (
            (
                $fixtureRow[
                    'is_home'
                ]
                ?? false
            )
                ? 'H'
                : 'A'
        )
        . "<br>";
        
    
    echo "Base Fixture Opportunity: "
        . (
            is_numeric(
                $fixtureRow[
                    'base_fixture_opportunity'
                ]
                ?? null
            )
                ? number_format(
                    (float) $fixtureRow[
                        'base_fixture_opportunity'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Fixture Opportunity: "
        . number_format(
            (float) (
                $fixtureRow[
                    'fixture_opportunity'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Opponent Attack Rating: "
        . (
            is_numeric(
                $fixtureRow[
                    'opponent_attack_rating'
                ]
                ?? null
            )
                ? number_format(
                    (float) $fixtureRow[
                        'opponent_attack_rating'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Opponent Defence Rating: "
        . (
            is_numeric(
                $fixtureRow[
                    'opponent_defence_rating'
                ]
                ?? null
            )
                ? number_format(
                    (float) $fixtureRow[
                        'opponent_defence_rating'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Projected Minutes: "
        . (
            is_numeric(
                $projection[
                    'projected_minutes'
                ]
                ?? null
            )
                ? number_format(
                    (float) $projection[
                        'projected_minutes'
                    ],
                    2
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Expected Goals: "
        . (
            is_numeric(
                $inputs[
                    'expected_goals'
                ]
                ?? null
            )
                ? number_format(
                    (float) $inputs[
                        'expected_goals'
                    ],
                    4
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Expected Assists: "
        . (
            is_numeric(
                $inputs[
                    'expected_assists'
                ]
                ?? null
            )
                ? number_format(
                    (float) $inputs[
                        'expected_assists'
                    ],
                    4
                )
                : 'Unavailable'
        )
        . "<br>";


    echo "Clean Sheet Probability: "
        . (
            is_numeric(
                $inputs[
                    'clean_sheet_probability'
                ]
                ?? null
            )
                ? number_format(
                    (float) $inputs[
                        'clean_sheet_probability'
                    ],
                    2
                )
                    . '%'
                : 'Unavailable'
        )
        . "<br>";


    echo "Expected Saves: "
        . (
            is_numeric(
                $inputs[
                    'expected_saves'
                ]
                ?? null
            )
                ? number_format(
                    (float) $inputs[
                        'expected_saves'
                    ],
                    4
                )
                : 'Unavailable'
        )
        . "<br><br>";


    echo "Appearance: "
        . number_format(
            (float) (
                $components[
                    'appearance'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Goals: "
        . number_format(
            (float) (
                $components[
                    'goals'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Assists: "
        . number_format(
            (float) (
                $components[
                    'assists'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Clean Sheet: "
        . number_format(
            (float) (
                $components[
                    'clean_sheet'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Goals Conceded: "
        . number_format(
            (float) (
                $components[
                    'goals_conceded'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Saves: "
        . number_format(
            (float) (
                $components[
                    'saves'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Bonus: "
        . number_format(
            (float) (
                $components[
                    'bonus'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "Defensive Contributions: "
        . number_format(
            (float) (
                $components[
                    'defensive_contributions'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "TOTAL: "
        . number_format(
            (float) (
                $projection[
                    'projected_points'
                ]
                ?? 0
            ),
            2
        )
        . " xP<br><br>";
}


echo "<br>";


echo "Next 3: "
    . (
        is_numeric(
            $next3
        )
            ? number_format(
                (float) $next3,
                2
            )
            : 'Unavailable'
    )
    . "<br>";


echo "Next 5: "
    . (
        is_numeric(
            $next5
        )
            ? number_format(
                (float) $next5,
                2
            )
            : 'Unavailable'
    )
    . "<br>";


echo "Next 6: "
    . (
        is_numeric(
            $next6
        )
            ? number_format(
                (float) $next6,
                2
            )
            : 'Unavailable'
    )
    . "<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Profile Multi-Gameweek Test Summary<br>";
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