<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Multi-Gameweek Expected Points Real Data Test<br>";
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

function multiGwRealCheck(
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
 * DATABASE / REPOSITORIES
 * ============================================================
 */

$database =
    new Database();


$connection =
    $database
        ->getConnection();


$playerRepository =
    new PlayerRepository(
        $connection
    );


$teamRepository =
    new TeamRepository(
        $connection
    );


$fixtureRepository =
    new FixtureRepository(
        $connection
    );


$historyRepository =
    new PlayerFixtureHistoryRepository(
        $connection
    );


/*
 * ============================================================
 * FORM INTELLIGENCE
 * ============================================================
 */

$formHistory =
    new PlayerFormHistory(
        $historyRepository
    );


$playerForm =
    new PlayerForm(
        $formHistory
    );


/*
 * ============================================================
 * EXPECTED POINTS STACK
 * ============================================================
 */

$playerExpectedPoints =
    new PlayerExpectedPoints(
        new ExpectedMinutes(),
        new ExpectedPointsInputs(),
        new ExpectedPoints(),
        new ProjectionConfidence()
    );


$multiGameweekExpectedPoints =
    new MultiGameweekExpectedPoints(
        $playerExpectedPoints
    );


/*
 * ============================================================
 * LOAD REAL DATA
 * ============================================================
 */

$players =
    $playerRepository
        ->getAll();


$teams =
    $teamRepository
        ->getAll();


$teamNames =
    [];


foreach (
    $teams
    as $team
) {

    $teamId =
        (int) (
            $team[
                'id'
            ]
            ?? 0
        );


    if (
        $teamId <= 0
    ) {

        continue;
    }


    $teamNames[
        $teamId
    ] =
        $team[
            'name'
        ]
        ??
        $team[
            'team_name'
        ]
        ??
        'Unknown';
}


echo "Players Loaded: "
    . count(
        $players
    )
    . "<br>";


echo "Teams Loaded: "
    . count(
        $teams
    )
    . "<br><br>";


/*
 * ============================================================
 * FIXTURE CONTEXT HELPERS
 * ============================================================
 *
 * This test intentionally derives fixture context from the real
 * stored FPL difficulty value.
 *
 * It is NOT the final production multi-gameweek context builder.
 *
 * Production integration will later reuse Team Intelligence and
 * Position-Aware Fixture Intelligence.
 */

function multiGwDifficultyToOpportunity(
    ?int $difficulty
): ?float {

    return match (
        $difficulty
    ) {

        1 =>
            100.0,

        2 =>
            80.0,

        3 =>
            60.0,

        4 =>
            40.0,

        5 =>
            20.0,

        default =>
            null
    };
}


function multiGwOpponentAttackFromDifficulty(
    ?int $difficulty
): ?float {

    return match (
        $difficulty
    ) {

        1 =>
            20.0,

        2 =>
            40.0,

        3 =>
            60.0,

        4 =>
            80.0,

        5 =>
            100.0,

        default =>
            null
    };
}


/*
 * ============================================================
 * RESOLVE A SUITABLE REAL PLAYER
 * ============================================================
 *
 * Prefer an attacking player because fixture opportunity should
 * visibly affect expected attacking returns.
 *
 * Requirements:
 *
 * - MID or FWD
 * - real historical appearance evidence
 * - at least six upcoming fixtures
 * - stored difficulty available
 * - at least two distinct difficulty values in the first six
 */

$selectedPlayer =
    null;


$selectedForm =
    null;


$selectedFixtures =
    [];


$selectedContexts =
    [];


foreach (
    $players
    as $player
) {

    $position =
        strtoupper(
            trim(
                (string) (
                    $player[
                        'position'
                    ]
                    ?? ''
                )
            )
        );


    if (
        !in_array(
            $position,
            [
                'MID',
                'FWD'
            ],
            true
        )
    ) {

        continue;
    }


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


    $form =
        $playerForm
            ->buildModel(
                $playerId,
                $position
            );


    $appearanceSampleSize =
        (int) (
            $form[
                'appearance_sample_size'
            ]
            ?? 0
        );


    if (
        $appearanceSampleSize <= 0
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


    $contexts =
        [];


    $difficultyValues =
        [];


    $allContextsAvailable =
        true;


    foreach (
        $upcomingFixtures
        as $fixture
    ) {

        $fixtureId =
            (int) (
                $fixture[
                    'id'
                ]
                ?? 0
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


        $difficulty =
            null;


        if (
    $homeTeamId ===
        $teamId
    ) {

        $difficulty =
            isset(
                $fixture[
                    'home_difficulty'
                ]
            )
            &&
            is_numeric(
                $fixture[
                    'home_difficulty'
                ]
            )
                ? (int) $fixture[
                    'home_difficulty'
                ]
                : null;

    } elseif (
        $awayTeamId ===
        $teamId
    ) {

        $difficulty =
            isset(
                $fixture[
                    'away_difficulty'
                ]
            )
            &&
            is_numeric(
                $fixture[
                    'away_difficulty'
                ]
            )
                ? (int) $fixture[
                    'away_difficulty'
                ]
                : null;
    }


        $fixtureOpportunity =
            multiGwDifficultyToOpportunity(
                $difficulty
            );


        $opponentAttackRating =
            multiGwOpponentAttackFromDifficulty(
                $difficulty
            );


        if (
            $fixtureId <= 0
            ||
            $fixtureOpportunity === null
            ||
            $opponentAttackRating === null
        ) {

            $allContextsAvailable =
                false;

            break;
        }


        $contexts[
            'fixture:'
            . $fixtureId
        ] = [

            'fixture_opportunity' =>
                $fixtureOpportunity,

            'opponent_attack_rating' =>
                $opponentAttackRating
        ];


        $difficultyValues[] =
            $difficulty;
    }


    if (
        !$allContextsAvailable
    ) {

        continue;
    }


    $difficultyValues =
        array_values(
            array_unique(
                $difficultyValues
            )
        );


    if (
        count(
            $difficultyValues
        )
        <
        2
    ) {

        continue;
    }


    $selectedPlayer =
        $player;


    $selectedForm =
        $form;


    $selectedFixtures =
        $upcomingFixtures;


    $selectedContexts =
        $contexts;


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


multiGwRealCheck(
    'A real player suitable for multi-gameweek projection resolves',
    is_array(
        $selectedPlayer
    )
);


multiGwRealCheck(
    'Selected player has real Form evidence',
    is_array(
        $selectedForm
    )
    &&
    (
        (int) (
            $selectedForm[
                'appearance_sample_size'
            ]
            ?? 0
        )
    )
    >
    0
);


multiGwRealCheck(
    'Selected player has at least six real upcoming fixtures',
    count(
        $selectedFixtures
    )
    >=
    6
);


if (
    $selectedPlayer === null
    ||
    $selectedForm === null
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";
    exit;
}


$selectedPlayerId =
    (int) (
        $selectedPlayer[
            'id'
        ]
        ?? 0
    );


$selectedTeamId =
    (int) (
        $selectedPlayer[
            'team_id'
        ]
        ?? 0
    );


$selectedPlayerName =
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


$selectedPosition =
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
        $selectedPlayerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Position: "
    . htmlspecialchars(
        $selectedPosition,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Team: "
    . htmlspecialchars(
        (string) (
            $teamNames[
                $selectedTeamId
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Player ID: "
    . $selectedPlayerId
    . "<br>";


echo "Form Appearance Sample: "
    . (int) (
        $selectedForm[
            'appearance_sample_size'
        ]
        ?? 0
    )
    . "<br><br>";


/*
 * ============================================================
 * RUN REAL MULTI-GAMEWEEK PROJECTION
 * ============================================================
 */

$result =
    $multiGameweekExpectedPoints
        ->projectFixtures(
            $selectedPlayer,
            $selectedForm,
            $selectedFixtures,
            $selectedContexts
        );


/*
 * ============================================================
 * SCENARIO B
 * FIXTURE PROJECTION CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Real Fixture Projection Contract<br>";
echo "============================================<br>";


$fixtureProjections =
    $result[
        'fixtures'
    ]
    ?? [];


multiGwRealCheck(
    'Six real fixtures produce six fixture projection rows',
    count(
        $fixtureProjections
    )
    ===
    6
);


multiGwRealCheck(
    'All six real fixtures receive projections',
    (
        (int) (
            $result[
                'fixture_projection_count'
            ]
            ?? 0
        )
    )
    ===
    6
);


$allProjected =
    true;


$allPointsNumeric =
    true;


$allMinutesValid =
    true;


foreach (
    $fixtureProjections
    as $projection
) {

    if (
        (
            $projection[
                'status'
            ]
            ?? null
        )
        !==
        'Projected'
    ) {

        $allProjected =
            false;
    }


    if (
        !is_numeric(
            $projection[
                'projected_points'
            ]
            ?? null
        )
    ) {

        $allPointsNumeric =
            false;
    }


    $projectedMinutes =
        $projection[
            'projected_minutes'
        ]
        ?? null;


    if (
        !is_numeric(
            $projectedMinutes
        )
        ||
        (float) $projectedMinutes
        <
        0
        ||
        (float) $projectedMinutes
        >
        90
    ) {

        $allMinutesValid =
            false;
    }
}


multiGwRealCheck(
    'Every real fixture status is Projected',
    $allProjected
);


multiGwRealCheck(
    'Every real fixture has numeric Projected Points',
    $allPointsNumeric
);


multiGwRealCheck(
    'Every real fixture has valid Projected Minutes',
    $allMinutesValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * CHRONOLOGICAL ORDER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Chronological Order<br>";
echo "============================================<br>";


$chronological =
    true;


$previousGameweek =
    null;


$previousKickoff =
    null;


foreach (
    $fixtureProjections
    as $projection
) {

    $gameweek =
        isset(
            $projection[
                'gameweek'
            ]
        )
            ? (int) $projection[
                'gameweek'
            ]
            : null;


    $kickoff =
        (string) (
            $projection[
                'kickoff_time'
            ]
            ?? ''
        );


    if (
        $previousGameweek !== null
    ) {

        if (
            $gameweek <
            $previousGameweek
        ) {

            $chronological =
                false;

        } elseif (
            $gameweek ===
            $previousGameweek
            &&
            $kickoff !== ''
            &&
            $previousKickoff !== ''
            &&
            strcmp(
                $kickoff,
                $previousKickoff
            )
            <
            0
        ) {

            $chronological =
                false;
        }
    }


    $previousGameweek =
        $gameweek;


    $previousKickoff =
        $kickoff;
}


multiGwRealCheck(
    'Real fixture projections remain chronological',
    $chronological
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * FIXTURE CONTEXT EFFECT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Fixture Context Effect<br>";
echo "============================================<br>";


$opportunities =
    [];


$projectedPointsValues =
    [];


foreach (
    $fixtureProjections
    as $projection
) {

    $fixtureOpportunity =
        $projection[
            'inputs'
        ][
            'evidence'
        ][
            'fixture_opportunity'
        ]
        ?? null;


    if (
        is_numeric(
            $fixtureOpportunity
        )
    ) {

        $opportunities[] =
            round(
                (float) $fixtureOpportunity,
                4
            );
    }


    if (
        is_numeric(
            $projection[
                'projected_points'
            ]
            ?? null
        )
    ) {

        $projectedPointsValues[] =
            round(
                (float) $projection[
                    'projected_points'
                ],
                4
            );
    }
}


$distinctOpportunities =
    array_values(
        array_unique(
            $opportunities
        )
    );


$distinctProjectedPoints =
    array_values(
        array_unique(
            $projectedPointsValues
        )
    );


multiGwRealCheck(
    'Real fixture run contains multiple fixture opportunity values',
    count(
        $distinctOpportunities
    )
    >=
    2
);


multiGwRealCheck(
    'Fixture-specific context produces multiple Projected Points values',
    count(
        $distinctProjectedPoints
    )
    >=
    2
);


echo "<br>";


/*
 * ============================================================
 * HORIZON TOTAL HELPER
 * ============================================================
 */

function multiGwRealExpectedHorizonTotal(
    array $gameweeks,
    int $horizon
): ?float {

    if (
        empty(
            $gameweeks
        )
        ||
        $horizon <= 0
    ) {

        return null;
    }


    $gameweekNumbers =
        array_map(
            'intval',
            array_keys(
                $gameweeks
            )
        );


    sort(
        $gameweekNumbers,
        SORT_NUMERIC
    );


    $firstGameweek =
        $gameweekNumbers[
            0
        ]
        ?? null;


    if (
        $firstGameweek === null
    ) {

        return null;
    }


    $lastGameweek =
        $firstGameweek
        +
        $horizon
        -
        1;


    $total =
        0.0;


    $hasProjection =
        false;


    for (
        $gameweek =
            $firstGameweek;
        $gameweek <=
            $lastGameweek;
        $gameweek++
    ) {

        if (
            !isset(
                $gameweeks[
                    $gameweek
                ]
            )
        ) {

            continue;
        }


        $points =
            $gameweeks[
                $gameweek
            ][
                'projected_points'
            ]
            ?? null;


        if (
            !is_numeric(
                $points
            )
        ) {

            continue;
        }


        $total +=
            (float) $points;


        $hasProjection =
            true;
    }


    return $hasProjection
        ? round(
            $total,
            2
        )
        : null;
}


/*
 * ============================================================
 * SCENARIO E
 * GAMEWEEK AGGREGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Gameweek Aggregation<br>";
echo "============================================<br>";


$gameweekProjections =
    $result[
        'gameweeks'
    ]
    ?? [];


multiGwRealCheck(
    'Real projections produce grouped gameweek summaries',
    !empty(
        $gameweekProjections
    )
);


$fixtureCountFromGameweeks =
    0;


foreach (
    $gameweekProjections
    as $gameweekSummary
) {

    $fixtureCountFromGameweeks +=
        (int) (
            $gameweekSummary[
                'fixture_count'
            ]
            ?? 0
        );
}


multiGwRealCheck(
    'Grouped gameweeks preserve all projected fixtures',
    $fixtureCountFromGameweeks
    ===
    6
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * HORIZON TOTALS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Horizon Totals<br>";
echo "============================================<br>";


foreach (
    [
        3,
        5,
        6
    ]
    as $horizon
) {

    $key =
        'next_'
        . $horizon;


    $expectedTotal =
        multiGwRealExpectedHorizonTotal(
            $gameweekProjections,
            $horizon
        );


    $actualTotal =
        $result[
            'totals'
        ][
            $key
        ]
        ?? null;


    multiGwRealCheck(
        'Real '
        . $key
        . ' total matches grouped gameweek sum',
        (
            $expectedTotal === null
            &&
            $actualTotal === null
        )
        ||
        (
            is_numeric(
                $expectedTotal
            )
            &&
            is_numeric(
                $actualTotal
            )
            &&
            abs(
                (float) $expectedTotal
                -
                (float) $actualTotal
            )
            <
            0.011
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * REAL MULTI-GAMEWEEK DIAGNOSTIC
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Real Multi-Gameweek Diagnostic<br>";
echo "============================================<br>";


foreach (
    $fixtureProjections
    as $index => $projection
) {

    $sourceFixture =
        $selectedFixtures[
            $index
        ]
        ?? [];


    $homeTeamId =
        (int) (
            $sourceFixture[
                'home_team_id'
            ]
            ?? 0
        );


    $awayTeamId =
        (int) (
            $sourceFixture[
                'away_team_id'
            ]
            ?? 0
        );


    $isHome =
        $homeTeamId ===
        $selectedTeamId;


    $opponentTeamId =
        $isHome
            ? $awayTeamId
            : $homeTeamId;


    $difficulty =
        $isHome
            ? (
                isset(
                    $sourceFixture[
                        'home_difficulty'
                    ]
                )
                    ? (int) $sourceFixture[
                        'home_difficulty'
                    ]
                    : null
            )
            : (
                isset(
                    $sourceFixture[
                        'away_difficulty'
                    ]
                )
                    ? (int) $sourceFixture[
                        'away_difficulty'
                    ]
                    : null
            );


    $opponentName =
        $teamNames[
            $opponentTeamId
        ]
        ?? 'Unknown';


    echo "GW"
        . (
            $projection[
                'gameweek'
            ]
            ?? '—'
        )
        . " — "
        . htmlspecialchars(
            (string) $opponentName,
            ENT_QUOTES,
            'UTF-8'
        )
        . " ("
        . (
            $isHome
                ? 'H'
                : 'A'
        )
        . ")"
        . " — Difficulty "
        . (
            $difficulty
            ?? '—'
        )
        . " — Opportunity "
        . number_format(
            (float) (
                $projection[
                    'inputs'
                ][
                    'evidence'
                ][
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
            $result[
                'totals'
            ][
                'next_3'
            ]
            ?? null
        )
            ? number_format(
                (float) $result[
                    'totals'
                ][
                    'next_3'
                ],
                2
            )
            : 'Unavailable'
    )
    . "<br>";


echo "Next 5: "
    . (
        is_numeric(
            $result[
                'totals'
            ][
                'next_5'
            ]
            ?? null
        )
            ? number_format(
                (float) $result[
                    'totals'
                ][
                    'next_5'
                ],
                2
            )
            : 'Unavailable'
    )
    . "<br>";


echo "Next 6: "
    . (
        is_numeric(
            $result[
                'totals'
            ][
                'next_6'
            ]
            ?? null
        )
            ? number_format(
                (float) $result[
                    'totals'
                ][
                    'next_6'
                ],
                2
            )
            : 'Unavailable'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * EXPLAINABILITY CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Explainability Contract<br>";
echo "============================================<br>";


$firstProjection =
    $fixtureProjections[
        0
    ]
    ?? [];


multiGwRealCheck(
    'Real fixture projection retains Expected Points components',
    !empty(
        $firstProjection[
            'components'
        ]
        ?? []
    )
);


multiGwRealCheck(
    'Real fixture projection retains Expected Points inputs',
    !empty(
        $firstProjection[
            'inputs'
        ]
        ?? []
    )
);


multiGwRealCheck(
    'Real fixture projection exposes Projection Confidence',
    is_numeric(
        $firstProjection[
            'projection_confidence_percent'
        ]
        ?? null
    )
);


multiGwRealCheck(
    'Real fixture projection exposes Projection Confidence label',
    is_string(
        $firstProjection[
            'projection_confidence_label'
        ]
        ?? null
    )
    &&
    trim(
        (string) $firstProjection[
            'projection_confidence_label'
        ]
    )
    !==
    ''
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Multi-Gameweek Expected Points Real Data Test Summary<br>";
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