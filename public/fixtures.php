<?php

require_once __DIR__
    . '/../classes/autoload.php';


require_once __DIR__
    . '/includes/data-health.php';


$config =
    require __DIR__
        . '/../config/config.php';


/*
 * ============================================================
 * ACTIVE NAVIGATION
 * ============================================================
 */

$activeNav =
    'fixtures';


/*
 * ============================================================
 * PAGE STATE
 * ============================================================
 */

$fixtures =
    [];


$teams =
    [];


$teamLookup =
    [];


$fixturesByGameweek =
    [];


$availableGameweeks =
    [];


$firstUpcomingGameweek =
    null;


$selectedGameweek =
    null;


$selectedFixtures =
    [];


$previousGameweek =
    null;


$nextGameweek =
    null;


$pageError =
    null;


$dataHealth =
    [];


/*
 * ============================================================
 * DATA
 * ============================================================
 */

try {

    $database =
        new Database();


        $db =
        $database
            ->getConnection();


    $dataHealth =
        evaluateDataHealth(
            $db,
            [
                'bootstrap',
                'fixtures'
            ],
            date(
                'Y-m-d H:i:s'
            ),
            $config[
                'data_health'
            ][
                'freshness_seconds'
            ]
        );


    $fixtureRepository =
        new FixtureRepository(
            $db
        );


    $teamRepository =
        new TeamRepository(
            $db
        );


    $fixtures =
        $fixtureRepository
            ->getAll();


    $teams =
        $teamRepository
            ->getAll();

} catch (
    Throwable $exception
) {

    $pageError =
        'Unable to load fixtures at the moment.';
}


/*
 * ============================================================
 * TEAM LOOKUP
 * ============================================================
 */

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


    $teamLookup[
        $teamId
    ] =
        $team;
}


/*
 * ============================================================
 * UPCOMING FIXTURES BY GAMEWEEK
 * ============================================================
 *
 * Completed fixtures remain available to the underlying
 * repository and intelligence models.
 *
 * This public schedule concentrates on future FPL decision
 * context.
 */

foreach (
    $fixtures
    as $fixture
) {

    $finished =
        !empty(
            $fixture[
                'finished'
            ]
        );


    $finishedProvisional =
        !empty(
            $fixture[
                'finished_provisional'
            ]
        );


    if (
        $finished
        ||
        $finishedProvisional
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
        $fixture[
            'gameweek'
        ] !== null
            ? (int) $fixture[
                'gameweek'
            ]
            : 0;


    if (
        $gameweek <= 0
    ) {

        continue;
    }


    $fixturesByGameweek[
        $gameweek
    ][] =
        $fixture;
}


/*
 * ============================================================
 * AVAILABLE GAMEWEEKS
 * ============================================================
 */

$availableGameweeks =
    array_keys(
        $fixturesByGameweek
    );


sort(
    $availableGameweeks,
    SORT_NUMERIC
);


if (
    !empty(
        $availableGameweeks
    )
) {

    $firstUpcomingGameweek =
        (int) $availableGameweeks[
            0
        ];
}


/*
 * ============================================================
 * SELECTED GAMEWEEK
 * ============================================================
 *
 * The first upcoming gameweek is the default.
 *
 * A requested gameweek is accepted only when it exists in the
 * current upcoming fixture collection. Invalid, completed or
 * unavailable gameweeks safely fall back to the first upcoming
 * gameweek.
 */

$requestedGameweek =
    isset(
        $_GET['gameweek']
    )
        ? filter_var(
            $_GET['gameweek'],
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1
                ]
            ]
        )
        : false;


if (
    $requestedGameweek !== false
    &&
    in_array(
        (int) $requestedGameweek,
        $availableGameweeks,
        true
    )
) {

    $selectedGameweek =
        (int) $requestedGameweek;

} else {

    $selectedGameweek =
        $firstUpcomingGameweek;
}


if (
    $selectedGameweek !== null
) {

    $selectedFixtures =
        $fixturesByGameweek[
            $selectedGameweek
        ]
        ?? [];
}


/*
 * ============================================================
 * PREVIOUS / NEXT GAMEWEEK
 * ============================================================
 */

if (
    $selectedGameweek !== null
) {

    $selectedGameweekIndex =
        array_search(
            $selectedGameweek,
            $availableGameweeks,
            true
        );


    if (
        $selectedGameweekIndex !== false
    ) {

        if (
            $selectedGameweekIndex > 0
        ) {

            $previousGameweek =
                (int) $availableGameweeks[
                    $selectedGameweekIndex - 1
                ];
        }


        if (
            $selectedGameweekIndex
            <
            count(
                $availableGameweeks
            ) - 1
        ) {

            $nextGameweek =
                (int) $availableGameweeks[
                    $selectedGameweekIndex + 1
                ];
        }
    }
}


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function fixturesPageEscape(
    mixed $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function fixturesPageTeamName(
    array $teamLookup,
    int $teamId
): string {

    return (string) (
        $teamLookup[
            $teamId
        ][
            'name'
        ]
        ?? 'Unknown'
    );
}


function fixturesPageKickoff(
    mixed $kickoffTime
): string {

    if (
        $kickoffTime === null
        ||
        trim(
            (string) $kickoffTime
        ) === ''
    ) {

        return 'Kickoff TBC';
    }


    try {

        $date =
            new DateTime(
                (string) $kickoffTime
            );


        return $date
            ->format(
                'D j M, H:i'
            );

    } catch (
        Throwable $exception
    ) {

        return 'Kickoff TBC';
    }
}


function fixturesPageDifficulty(
    mixed $difficulty
): string {

    if (
        $difficulty === null
        ||
        !is_numeric(
            $difficulty
        )
    ) {

        return '—';
    }


    return (string) (
        (int) $difficulty
    );
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Fixtures | FPL Intelligence</title>

    <link
        rel="stylesheet"
        href="assets/css/app.css"
    >

</head>

<body>

<div class="app-shell">

    <?php

    require __DIR__
        . '/includes/sidebar.php';

    ?>


    <main class="app-content">

        <!-- ==============================================
             TOPBAR
             ============================================== -->

        <header class="topbar">

            <div>

                <p class="eyebrow">
                    Premier League Schedule
                </p>

                <h1>
                    Fixtures
                </h1>

                <p>
                    Upcoming Premier League fixtures grouped by
                    FPL gameweek.
                </p>

            </div>

        </header>


        <div class="dashboard">

            <?php

            /*
             * ================================================
             * DATA HEALTH
             * ================================================
             */

            if (
                !empty(
                    $dataHealth
                )
            ) {

                require __DIR__
                    . '/includes/data-health-warning.php';
            }

            ?>


            <!-- ==============================================
                 PAGE INTRODUCTION
                 ============================================== -->

            <section class="dashboard-section">

                <div class="section-heading">

                    <p class="eyebrow">
                        Fixture Schedule
                    </p>

                    <h2>
                        Upcoming Fixtures
                    </h2>

                </div>


                <p>
                    Review the upcoming Premier League schedule,
                    kickoff times and official FPL fixture difficulty.
                    Select a team to open its full Team Intelligence
                    profile and longer-term fixture analysis.
                </p>

            </section>


            <?php if (
                $pageError !== null
            ): ?>

                <!-- ==========================================
                     ERROR STATE
                     ========================================== -->

                <section class="dashboard-section">

                    <div class="empty-state">

                        <h2>
                            Unable to load fixtures
                        </h2>

                        <p>
                            Fixture data could not be loaded at the
                            moment. Check the application data update
                            status and try again.
                        </p>

                    </div>

                </section>


            <?php elseif (
                empty(
                    $availableGameweeks
                )
            ): ?>

                <!-- ==========================================
                     EMPTY STATE
                     ========================================== -->

                <section class="dashboard-section">

                    <div class="empty-state">

                        <h2>
                            No fixtures available
                        </h2>

                        <p>
                            There are currently no upcoming Premier
                            League fixtures available in the local
                            fixture data.
                        </p>

                    </div>

                </section>


            <?php else: ?>

                <!-- ==========================================
                     GAMEWEEK CONTROLS
                     ========================================== -->

                <section class="dashboard-section fixture-gameweek-controls">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Selected Gameweek
                        </p>

                        <h2>
                            Gameweek
                            <?= fixturesPageEscape(
                                $selectedGameweek
                            ); ?>
                        </h2>

                    </div>


                    <div class="fixture-gameweek-navigation">

                        <div class="fixture-gameweek-navigation-side">

                            <?php if (
                                $previousGameweek !== null
                            ): ?>

                                <a
                                    class="fixture-gameweek-link"
                                    href="fixtures.php?gameweek=<?=
                                        $previousGameweek;
                                    ?>"
                                >
                                    ← GW
                                    <?= fixturesPageEscape(
                                        $previousGameweek
                                    ); ?>
                                </a>

                            <?php endif; ?>

                        </div>


                        <form
                            class="fixture-gameweek-form"
                            action="fixtures.php"
                            method="get"
                        >

                            <label for="fixture-gameweek-select">
                                Gameweek
                            </label>

                            <select
                                id="fixture-gameweek-select"
                                name="gameweek"
                                onchange="this.form.submit()"
                            >

                                <?php foreach (
                                    $availableGameweeks
                                    as $gameweek
                                ): ?>

                                    <option
                                        value="<?=
                                            fixturesPageEscape(
                                                $gameweek
                                            );
                                        ?>"
                                        <?=
                                            $gameweek
                                            ===
                                            $selectedGameweek
                                                ? 'selected'
                                                : '';
                                        ?>
                                    >
                                        Gameweek
                                        <?= fixturesPageEscape(
                                            $gameweek
                                        ); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <noscript>

                                <button type="submit">
                                    View
                                </button>

                            </noscript>

                        </form>


                        <div class="fixture-gameweek-navigation-side fixture-gameweek-navigation-next">

                            <?php if (
                                $nextGameweek !== null
                            ): ?>

                                <a
                                    class="fixture-gameweek-link"
                                    href="fixtures.php?gameweek=<?=
                                        $nextGameweek;
                                    ?>"
                                >
                                    GW
                                    <?= fixturesPageEscape(
                                        $nextGameweek
                                    ); ?>
                                    →
                                </a>

                            <?php endif; ?>

                        </div>

                    </div>

                </section>


                <!-- ==========================================
                     SELECTED GAMEWEEK
                     ========================================== -->

                <section class="dashboard-section fixture-gameweek-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            FPL Schedule
                        </p>

                        <h2>
                            Gameweek
                            <?= fixturesPageEscape(
                                $selectedGameweek
                            ); ?>
                            Fixtures
                        </h2>

                        <p>
                            <?= count(
                                $selectedFixtures
                            ); ?>
                            upcoming
                            <?= count(
                                $selectedFixtures
                            ) === 1
                                ? 'fixture'
                                : 'fixtures'; ?>
                            in this gameweek.
                        </p>

                    </div>


                    <?php if (
                        empty(
                            $selectedFixtures
                        )
                    ): ?>

                        <div class="empty-state">

                            <h2>
                                No fixtures available
                            </h2>

                            <p>
                                There are no upcoming fixtures available
                                for the selected gameweek.
                            </p>

                        </div>


                    <?php else: ?>

                        <p
                            class="fixture-table-scroll-hint"
                            aria-hidden="true"
                        >
                            Swipe or scroll to see away team and FDR →
                        </p>

                        <div class="team-ranking-table-wrapper">

                            <table
                                class="team-ranking-table fixture-table"
                                aria-label="Fixture schedule"
                            >

                                <thead>

                                    <tr>

                                        <th scope="col">
                                            Kickoff
                                        </th>

                                        <th scope="col">
                                            Home
                                        </th>

                                        <th scope="col">
                                            FDR
                                        </th>

                                        <th scope="col">
                                            Away
                                        </th>

                                        <th scope="col">
                                            FDR
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $selectedFixtures
                                        as $fixture
                                    ): ?>

                                        <?php

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

                                        ?>

                                        <tr>

                                            <td>

                                                <?= fixturesPageEscape(
                                                    fixturesPageKickoff(
                                                        $fixture[
                                                            'kickoff_time'
                                                        ]
                                                        ?? null
                                                    )
                                                ); ?>

                                            </td>


                                            <td>

                                                <?php if (
                                                    $homeTeamId > 0
                                                ): ?>

                                                    <a
                                                        href="team.php?id=<?=
                                                            $homeTeamId;
                                                        ?>"
                                                    >
                                                        <?= fixturesPageEscape(
                                                            fixturesPageTeamName(
                                                                $teamLookup,
                                                                $homeTeamId
                                                            )
                                                        ); ?>
                                                    </a>

                                                <?php else: ?>

                                                    Unknown

                                                <?php endif; ?>

                                            </td>


                                            <td>

                                                <span
                                                    class="fixture-fdr fixture-fdr-<?=
                                                        fixturesPageEscape(
                                                            fixturesPageDifficulty(
                                                                $fixture[
                                                                    'home_difficulty'
                                                                ]
                                                                ?? null
                                                            )
                                                        );
                                                    ?>"
                                                >
                                                    <?= fixturesPageEscape(
                                                        fixturesPageDifficulty(
                                                            $fixture[
                                                                'home_difficulty'
                                                            ]
                                                            ?? null
                                                        )
                                                    ); ?>
                                                </span>

                                            </td>


                                            <td>

                                                <?php if (
                                                    $awayTeamId > 0
                                                ): ?>

                                                    <a
                                                        href="team.php?id=<?=
                                                            $awayTeamId;
                                                        ?>"
                                                    >
                                                        <?= fixturesPageEscape(
                                                            fixturesPageTeamName(
                                                                $teamLookup,
                                                                $awayTeamId
                                                            )
                                                        ); ?>
                                                    </a>

                                                <?php else: ?>

                                                    Unknown

                                                <?php endif; ?>

                                            </td>


                                            <td>

                                                <span
                                                    class="fixture-fdr fixture-fdr-<?=
                                                        fixturesPageEscape(
                                                            fixturesPageDifficulty(
                                                                $fixture[
                                                                    'away_difficulty'
                                                                ]
                                                                ?? null
                                                            )
                                                        );
                                                    ?>"
                                                >
                                                    <?= fixturesPageEscape(
                                                        fixturesPageDifficulty(
                                                            $fixture[
                                                                'away_difficulty'
                                                            ]
                                                            ?? null
                                                        )
                                                    ); ?>
                                                </span>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </section>

            <?php endif; ?>

        </div>

    </main>

</div>


<script src="assets/js/app.js"></script>

</body>

</html>