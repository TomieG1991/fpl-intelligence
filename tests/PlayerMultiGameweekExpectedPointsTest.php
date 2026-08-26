<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Multi-Gameweek Expected Points Test<br>";
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

function playerMultiGameweekCheck(
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
 * RESOLVE A REAL PLAYER
 * ============================================================
 *
 * Select the first real player whose team has at least six
 * upcoming fixtures.
 *
 * This keeps the test independent of any hard-coded player ID.
 */

$selectedPlayer =
    null;


$selectedFixtures =
    [];


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


    $selectedFixtures =
        $upcomingFixtures;


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


playerMultiGameweekCheck(
    'A real player with six upcoming fixtures resolves',
    is_array(
        $selectedPlayer
    )
    &&
    count(
        $selectedFixtures
    )
    >=
    6
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
 * SCENARIO B
 * SERVICE CONTRACT AVAILABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Service Contract Availability<br>";
echo "============================================<br>";


$methodExists =
    method_exists(
        $service,
        'getPlayerMultiGameweekExpectedPoints'
    );


playerMultiGameweekCheck(
    'Player Intelligence Service exposes multi-gameweek Expected Points',
    $methodExists
);


if (
    !$methodExists
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "PlayerIntelligenceService::getPlayerMultiGameweekExpectedPoints()<br><br>";


    echo "============================================<br>";
    echo "Player Multi-Gameweek Expected Points Test Summary<br>";
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

echo "<br>";


/*
 * ============================================================
 * RUN REAL SERVICE PROJECTION
 * ============================================================
 */

$result =
    $service
        ->getPlayerMultiGameweekExpectedPoints(
            $playerId,
            6
        );


/*
 * ============================================================
 * SCENARIO C
 * TOP-LEVEL CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Top-Level Contract<br>";
echo "============================================<br>";


playerMultiGameweekCheck(
    'Service returns an array',
    is_array(
        $result
    )
);


playerMultiGameweekCheck(
    'Projection is explicitly available',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


playerMultiGameweekCheck(
    'Result preserves requested player ID',
    (
        (int) (
            $result[
                'player_id'
            ]
            ?? 0
        )
    )
    ===
    $playerId
);


playerMultiGameweekCheck(
    'Result preserves team ID',
    (
        (int) (
            $result[
                'team_id'
            ]
            ?? 0
        )
    )
    ===
    $teamId
);


playerMultiGameweekCheck(
    'Result preserves player position',
    (
        $result[
            'position'
        ]
        ?? null
    )
    ===
    $position
);


playerMultiGameweekCheck(
    'Result exposes fixture projections',
    is_array(
        $result[
            'fixtures'
        ]
        ?? null
    )
);


playerMultiGameweekCheck(
    'Result contains six fixture projections',
    count(
        $result[
            'fixtures'
        ]
        ?? []
    )
    ===
    6
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * REAL FIXTURE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Real Fixture Contract<br>";
echo "============================================<br>";


$fixtureRows =
    $result[
        'fixtures'
    ]
    ?? [];


$allFixturesValid =
    count(
        $fixtureRows
    )
    ===
    6;


$gameweeks =
    [];


$projectedPoints =
    [];


$fixtureOpportunities =
    [];


foreach (
    $fixtureRows
    as $fixtureRow
) {

    $fixtureId =
        (int) (
            $fixtureRow[
                'fixture_id'
            ]
            ?? 0
        );


    $gameweek =
        (int) (
            $fixtureRow[
                'gameweek'
            ]
            ?? 0
        );


    $opponentTeamId =
        (int) (
            $fixtureRow[
                'opponent_team_id'
            ]
            ?? 0
        );


    $isHome =
        $fixtureRow[
            'is_home'
        ]
        ?? null;


    $fixtureOpportunity =
        $fixtureRow[
            'fixture_opportunity'
        ]
        ?? null;


    $opponentAttackRating =
        $fixtureRow[
            'opponent_attack_rating'
        ]
        ?? null;


    $opponentDefenceRating =
        $fixtureRow[
            'opponent_defence_rating'
        ]
        ?? null;


    $projection =
        $fixtureRow[
            'projection'
        ]
        ?? null;


    $rowValid =
        $fixtureId > 0
        &&
        $gameweek > 0
        &&
        $opponentTeamId > 0
        &&
        is_bool(
            $isHome
        )
        &&
        is_numeric(
            $fixtureOpportunity
        )
        &&
        is_array(
            $projection
        )
        &&
        is_numeric(
            $projection[
                'projected_points'
            ]
            ?? null
        );


    if (
        !$rowValid
    ) {

        $allFixturesValid =
            false;
    }


    $gameweeks[] =
        $gameweek;


    if (
        is_numeric(
            $fixtureOpportunity
        )
    ) {

        $fixtureOpportunities[] =
            round(
                (float) $fixtureOpportunity,
                4
            );
    }


    if (
        is_array(
            $projection
        )
        &&
        is_numeric(
            $projection[
                'projected_points'
            ]
            ?? null
        )
    ) {

        $projectedPoints[] =
            (float) $projection[
                'projected_points'
            ];
    }


    /*
     * Opponent ratings may legitimately be unavailable while the
     * season sample is immature, so we only require them to be
     * either numeric or null.
     */
    if (
        $opponentAttackRating !== null
        &&
        !is_numeric(
            $opponentAttackRating
        )
    ) {

        $allFixturesValid =
            false;
    }


    if (
        $opponentDefenceRating !== null
        &&
        !is_numeric(
            $opponentDefenceRating
        )
    ) {

        $allFixturesValid =
            false;
    }
}


playerMultiGameweekCheck(
    'Every fixture exposes real fixture context and a projection',
    $allFixturesValid
);

playerMultiGameweekCheck(
    'Every fixture exposes the opponent name',
    count(
        array_filter(
            $result[
                'fixtures'
            ]
            ?? [],
            static function (
                array $fixture
            ): bool {

                return trim(
                    (string) (
                        $fixture[
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
        $result[
            'fixtures'
        ]
        ?? []
    )
);


$sortedGameweeks =
    $gameweeks;


sort(
    $sortedGameweeks
);


playerMultiGameweekCheck(
    'Fixture projections remain in chronological gameweek order',
    $gameweeks
    ===
    $sortedGameweeks
);


playerMultiGameweekCheck(
    'All six fixtures produce numeric Projected Points',
    count(
        $projectedPoints
    )
    ===
    6
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * AGGREGATED HORIZONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Aggregated Horizons<br>";
echo "============================================<br>";


$next3 =
    $result[
        'next_3'
    ]
    ?? null;


$next5 =
    $result[
        'next_5'
    ]
    ?? null;


$next6 =
    $result[
        'next_6'
    ]
    ?? null;


playerMultiGameweekCheck(
    'Next 3 aggregate is numeric',
    is_numeric(
        $next3
    )
);


playerMultiGameweekCheck(
    'Next 5 aggregate is numeric',
    is_numeric(
        $next5
    )
);


playerMultiGameweekCheck(
    'Next 6 aggregate is numeric',
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


playerMultiGameweekCheck(
    'Next 3 equals the first three fixture projections',
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


playerMultiGameweekCheck(
    'Next 5 equals the first five fixture projections',
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


playerMultiGameweekCheck(
    'Next 6 equals all six fixture projections',
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
 * PROJECTION EXPLAINABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Projection Explainability<br>";
echo "============================================<br>";


$allExplainable =
    true;


foreach (
    $fixtureRows
    as $fixtureRow
) {

    $projection =
        $fixtureRow[
            'projection'
        ]
        ?? [];


    if (
        !is_array(
            $projection[
                'components'
            ]
            ?? null
        )
        ||
        !is_array(
            $projection[
                'inputs'
            ]
            ?? null
        )
    ) {

        $allExplainable =
            false;

        break;
    }
}


playerMultiGameweekCheck(
    'Every fixture retains Expected Points components and inputs',
    $allExplainable
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * REAL SERVICE DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Real Service Diagnostic<br>";
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


foreach (
    $fixtureRows
    as $fixtureRow
) {

    $projection =
        $fixtureRow[
            'projection'
        ]
        ?? [];


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
        . " — Opponent Team "
        . (
            $fixtureRow[
                'opponent_team_id'
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
        . " — Opportunity "
        . number_format(
            (float) (
                $fixtureRow[
                    'fixture_opportunity'
                ]
                ?? 0
            ),
            2
        )
        . " — "
        . number_format(
            (float) (
                $projection[
                    'projected_points'
                ]
                ?? 0
            ),
            2
        )
        . " xP"
        . "<br>";
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
 * SCENARIO H
 * INVALID PLAYER CONTRACT
 * ============================================================
 *
 * The eventual service method should fail explicitly rather
 * than inventing a projection for an unknown player.
 */

echo "============================================<br>";
echo "Scenario H: Invalid Player Contract<br>";
echo "============================================<br>";


$invalidResult =
    $service
        ->getPlayerMultiGameweekExpectedPoints(
            999999999,
            6
        );


playerMultiGameweekCheck(
    'Unknown player returns an array',
    is_array(
        $invalidResult
    )
);


playerMultiGameweekCheck(
    'Unknown player is explicitly unavailable',
    (
        $invalidResult[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


playerMultiGameweekCheck(
    'Unknown player does not invent fixture projections',
    (
        $invalidResult[
            'fixtures'
        ]
        ?? []
    )
    ===
    []
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Multi-Gameweek Expected Points Test Summary<br>";
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