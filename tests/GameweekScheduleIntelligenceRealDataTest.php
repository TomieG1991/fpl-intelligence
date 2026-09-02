<?php

require_once __DIR__
    . '/../classes/autoload.php';

require_once __DIR__
    . '/../config/config.php';


echo
    '============================================<br>';

echo
    'Gameweek Schedule Intelligence Real Data Test<br>';

echo
    'v0.33.0 — Blank & Double Gameweek Intelligence<br>';

echo
    '============================================<br><br>';


$passed =
    0;


$failed =
    0;


function gameweekScheduleRealCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';


        $passed++;


        return;
    }


    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';


    $failed++;
}


/*
 * ============================================================
 * DATABASE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario A: Database Setup<br>';

echo
    '============================================<br>';


try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $fixtureRepository =
        new FixtureRepository(
            $db
        );


    $teamRepository =
        new TeamRepository(
            $db
        );


    gameweekScheduleRealCheck(
        'Database connection is available',
        $db instanceof PDO
    );


    gameweekScheduleRealCheck(
        'FixtureRepository can be created',
        $fixtureRepository
        instanceof
        FixtureRepository
    );


    gameweekScheduleRealCheck(
        'TeamRepository can be created',
        $teamRepository
        instanceof
        TeamRepository
    );

} catch (
    Throwable $exception
) {

    gameweekScheduleRealCheck(
        'Database setup completes without exception',
        false
    );


    echo
        'ERROR: '
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br><br>';


    echo
        '============================================<br>';

    echo
        'TEST SUMMARY<br>';

    echo
        '============================================<br>';


    echo
        'Passed: '
        . $passed
        . '<br>';


    echo
        'Failed: '
        . $failed
        . '<br><br>';


    echo
        'RESULT: TESTS FAILED ❌';


    exit;
}


echo
    '<br>';


/*
 * ============================================================
 * LOAD REAL DATA
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario B: Real Fixture and Team Data<br>';

echo
    '============================================<br>';


$fixtures =
    $fixtureRepository
        ->getAll();


$teams =
    $teamRepository
        ->getAll();


echo
    'Fixtures Found: '
    . count(
        $fixtures
    )
    . '<br>';


echo
    'Teams Found: '
    . count(
        $teams
    )
    . '<br>';


gameweekScheduleRealCheck(
    'Fixture repository returns an array',
    is_array(
        $fixtures
    )
);


gameweekScheduleRealCheck(
    'Fixture database contains fixtures',
    !empty(
        $fixtures
    )
);


gameweekScheduleRealCheck(
    'Team repository returns an array',
    is_array(
        $teams
    )
);


gameweekScheduleRealCheck(
    'Team database contains teams',
    !empty(
        $teams
    )
);


$teamIds =
    [];


foreach (
    $teams
    as $team
) {

    if (
        !is_array(
            $team
        )
    ) {

        continue;
    }


    $teamId =
        isset(
            $team[
                'id'
            ]
        )
        &&
        is_numeric(
            $team[
                'id'
            ]
        )
            ? (int) $team[
                'id'
            ]
            : 0;


    if (
        $teamId <= 0
    ) {

        continue;
    }


    $teamIds[] =
        $teamId;
}


sort(
    $teamIds,
    SORT_NUMERIC
);


gameweekScheduleRealCheck(
    'All database teams expose valid local IDs',
    count(
        $teamIds
    )
    ===
    count(
        $teams
    )
);


echo
    '<br>';


/*
 * ============================================================
 * DISCOVER GAMEWEEKS
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario C: Discover Real Gameweeks<br>';

echo
    '============================================<br>';


$availableGameweeks =
    [];


foreach (
    $fixtures
    as $fixture
) {

    if (
        !is_array(
            $fixture
        )
    ) {

        continue;
    }


    $gameweek =
        isset(
            $fixture[
                'gameweek'
            ]
        )
        &&
        is_numeric(
            $fixture[
                'gameweek'
            ]
        )
            ? (int) $fixture[
                'gameweek'
            ]
            : 0;


    if (
        $gameweek <= 0
    ) {

        continue;
    }


    $availableGameweeks[
        $gameweek
    ] =
        $gameweek;
}


$availableGameweeks =
    array_values(
        $availableGameweeks
    );


sort(
    $availableGameweeks,
    SORT_NUMERIC
);


gameweekScheduleRealCheck(
    'Real fixture data exposes scheduled gameweeks',
    !empty(
        $availableGameweeks
    )
);


$firstGameweek =
    $availableGameweeks[
        0
    ]
    ?? null;


$lastGameweek =
    !empty(
        $availableGameweeks
    )
        ? end(
            $availableGameweeks
        )
        : null;


echo
    'First Scheduled GW: '
    . (
        $firstGameweek
        ?? 'None'
    )
    . '<br>';


echo
    'Last Scheduled GW: '
    . (
        $lastGameweek
        ?? 'None'
    )
    . '<br>';


gameweekScheduleRealCheck(
    'First discovered gameweek is valid',
    is_int(
        $firstGameweek
    )
    &&
    $firstGameweek > 0
);


gameweekScheduleRealCheck(
    'Last discovered gameweek is valid',
    is_int(
        $lastGameweek
    )
    &&
    $lastGameweek >= $firstGameweek
);


echo
    '<br>';


/*
 * ============================================================
 * CONTROLLED REAL-DATA HORIZON
 * ============================================================
 *
 * Use the first three scheduled gameweeks currently stored
 * in the database.
 *
 * This keeps the test independent of the current live date
 * while still validating genuine imported FPL fixture data.
 */

echo
    '============================================<br>';

echo
    'Scenario D: Controlled Real-Data Horizon<br>';

echo
    '============================================<br>';


$testGameweeks =
    array_slice(
        $availableGameweeks,
        0,
        3
    );


echo
    'Test Gameweeks: '
    . implode(
        ', ',
        $testGameweeks
    )
    . '<br>';


gameweekScheduleRealCheck(
    'Controlled horizon contains three gameweeks',
    count(
        $testGameweeks
    )
    ===
    3
);


$model =
    new GameweekScheduleIntelligence();


$result =
    $model
        ->analyse(
            $fixtures,
            $teamIds,
            $testGameweeks
        );


gameweekScheduleRealCheck(
    'Real fixture analysis returns an array',
    is_array(
        $result
    )
);


gameweekScheduleRealCheck(
    'Real fixture analysis exposes gameweeks',
    isset(
        $result[
            'gameweeks'
        ]
    )
    &&
    is_array(
        $result[
            'gameweeks'
        ]
    )
);


gameweekScheduleRealCheck(
    'Real fixture analysis returns every requested gameweek',
    count(
        $result[
            'gameweeks'
        ]
        ?? []
    )
    ===
    count(
        $testGameweeks
    )
);


echo
    '<br>';


/*
 * ============================================================
 * TEAM COVERAGE
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario E: Team Coverage<br>';

echo
    '============================================<br>';


$allGameweeksContainAllTeams =
    true;


foreach (
    $testGameweeks
    as $gameweek
) {

    $scheduleTeams =
        $result[
            'gameweeks'
        ][
            $gameweek
        ][
            'teams'
        ]
        ?? [];


    if (
        count(
            $scheduleTeams
        )
        !==
        count(
            $teamIds
        )
    ) {

        $allGameweeksContainAllTeams =
            false;

        break;
    }
}


gameweekScheduleRealCheck(
    'Every analysed gameweek contains every database team',
    $allGameweeksContainAllTeams
);


echo
    '<br>';


/*
 * ============================================================
 * FIXTURE COUNT RECONCILIATION
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario F: Fixture Count Reconciliation<br>';

echo
    '============================================<br>';


$fixtureCountMatches =
    true;


$comparisonCount =
    0;


foreach (
    $testGameweeks
    as $gameweek
) {

    foreach (
        $teamIds
        as $teamId
    ) {

        $expectedFixtureCount =
            0;


        foreach (
            $fixtures
            as $fixture
        ) {

            if (
                !is_array(
                    $fixture
                )
            ) {

                continue;
            }


            $fixtureGameweek =
                isset(
                    $fixture[
                        'gameweek'
                    ]
                )
                &&
                is_numeric(
                    $fixture[
                        'gameweek'
                    ]
                )
                    ? (int) $fixture[
                        'gameweek'
                    ]
                    : 0;


            if (
                $fixtureGameweek
                !==
                $gameweek
            ) {

                continue;
            }


            $homeTeamId =
                isset(
                    $fixture[
                        'home_team_id'
                    ]
                )
                &&
                is_numeric(
                    $fixture[
                        'home_team_id'
                    ]
                )
                    ? (int) $fixture[
                        'home_team_id'
                    ]
                    : 0;


            $awayTeamId =
                isset(
                    $fixture[
                        'away_team_id'
                    ]
                )
                &&
                is_numeric(
                    $fixture[
                        'away_team_id'
                    ]
                )
                    ? (int) $fixture[
                        'away_team_id'
                    ]
                    : 0;


            if (
                $homeTeamId === $teamId
                ||
                $awayTeamId === $teamId
            ) {

                $expectedFixtureCount++;
            }
        }


        $actualFixtureCount =
            (int) (
                $result[
                    'gameweeks'
                ][
                    $gameweek
                ][
                    'teams'
                ][
                    $teamId
                ][
                    'fixture_count'
                ]
                ?? -1
            );


        $comparisonCount++;


        if (
            $actualFixtureCount
            !==
            $expectedFixtureCount
        ) {

            $fixtureCountMatches =
                false;

            break 2;
        }
    }
}


echo
    'Team/Gameweek Comparisons: '
    . $comparisonCount
    . '<br>';


gameweekScheduleRealCheck(
    'Schedule fixture counts match raw fixture repository data',
    $fixtureCountMatches
);


echo
    '<br>';


/*
 * ============================================================
 * SCHEDULE CLASSIFICATION RECONCILIATION
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario G: Schedule Classification<br>';

echo
    '============================================<br>';


$classificationsMatch =
    true;


$blankCount =
    0;


$normalCount =
    0;


$doubleCount =
    0;


foreach (
    $testGameweeks
    as $gameweek
) {

    foreach (
        $teamIds
        as $teamId
    ) {

        $teamSchedule =
            $result[
                'gameweeks'
            ][
                $gameweek
            ][
                'teams'
            ][
                $teamId
            ]
            ?? [];


        $fixtureCount =
            (int) (
                $teamSchedule[
                    'fixture_count'
                ]
                ?? -1
            );


        $scheduleType =
            $teamSchedule[
                'schedule_type'
            ]
            ?? null;


        if (
            $fixtureCount === 0
        ) {

            $expectedType =
                'Blank';

            $blankCount++;

        } elseif (
            $fixtureCount === 1
        ) {

            $expectedType =
                'Normal';

            $normalCount++;

        } else {

            $expectedType =
                'Double';

            $doubleCount++;
        }


        if (
            $scheduleType
            !==
            $expectedType
        ) {

            $classificationsMatch =
                false;

            break 2;
        }
    }
}


echo
    'Blank Team/Gameweeks: '
    . $blankCount
    . '<br>';


echo
    'Normal Team/Gameweeks: '
    . $normalCount
    . '<br>';


echo
    'Double Team/Gameweeks: '
    . $doubleCount
    . '<br>';


gameweekScheduleRealCheck(
    'Schedule classification matches fixture count semantics',
    $classificationsMatch
);


echo
    '<br>';


/*
 * ============================================================
 * FIXTURE IDENTITY PRESERVATION
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Scenario H: Fixture Identity Preservation<br>';

echo
    '============================================<br>';


$fixtureIdentityPreserved =
    true;


foreach (
    $testGameweeks
    as $gameweek
) {

    foreach (
        $teamIds
        as $teamId
    ) {

        $scheduledFixtures =
            $result[
                'gameweeks'
            ][
                $gameweek
            ][
                'teams'
            ][
                $teamId
            ][
                'fixtures'
            ]
            ?? [];


        foreach (
            $scheduledFixtures
            as $scheduledFixture
        ) {

            if (
                !is_array(
                    $scheduledFixture
                )
            ) {

                $fixtureIdentityPreserved =
                    false;

                break 3;
            }


            $fixtureId =
                isset(
                    $scheduledFixture[
                        'id'
                    ]
                )
                &&
                is_numeric(
                    $scheduledFixture[
                        'id'
                    ]
                )
                    ? (int) $scheduledFixture[
                        'id'
                    ]
                    : 0;


            if (
                $fixtureId <= 0
            ) {

                $fixtureIdentityPreserved =
                    false;

                break 3;
            }


            $matchingFixtureFound =
                false;


            foreach (
                $fixtures
                as $rawFixture
            ) {

                if (
                    !is_array(
                        $rawFixture
                    )
                ) {

                    continue;
                }


                if (
                    isset(
                        $rawFixture[
                            'id'
                        ]
                    )
                    &&
                    is_numeric(
                        $rawFixture[
                            'id'
                        ]
                    )
                    &&
                    (int) $rawFixture[
                        'id'
                    ]
                    ===
                    $fixtureId
                ) {

                    $matchingFixtureFound =
                        true;

                    break;
                }
            }


            if (
                !$matchingFixtureFound
            ) {

                $fixtureIdentityPreserved =
                    false;

                break 3;
            }
        }
    }
}


gameweekScheduleRealCheck(
    'Every scheduled fixture preserves a real repository fixture ID',
    $fixtureIdentityPreserved
);


echo
    '<br>';


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'TEST SUMMARY<br>';

echo
    '============================================<br>';


echo
    'Passed: '
    . $passed
    . '<br>';


echo
    'Failed: '
    . $failed
    . '<br><br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅';

} else {

    echo
        'RESULT: TESTS FAILED ❌';
}