<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * ACTIVE NAVIGATION
 * ============================================================
 */

$activeNav =
    'teams';


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

$service =
    null;


$teams =
    [];


$pageError =
    null;


try {

    $database =
        new Database();


    $service =
        new PlayerIntelligenceService(
            $database->getConnection()
        );


    $teams =
        $service
            ->getAllTeamIntelligenceSummaries();

} catch (
    Throwable $exception
) {

    $pageError =
        'Unable to load Team Intelligence at the moment.';
}


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function teamsPageEscape(
    mixed $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function teamsPageRating(
    mixed $value,
    int $decimals = 1
): string {

    if (
        $value === null
        ||
        !is_numeric(
            $value
        )
    ) {

        return '—';
    }


    return number_format(
        (float) $value,
        $decimals
    );
}


function teamsPageForm(
    mixed $form
): string {

    if (
        !is_array(
            $form
        )
        ||
        empty(
            $form
        )
    ) {

        return '—';
    }


    return implode(
        '',
        array_slice(
            $form,
            -5
        )
    );
}


function teamsPageLabelClass(
    mixed $label
): string {

    $label =
        strtolower(
            trim(
                (string) $label
            )
        );


    return match ($label) {

        'elite' =>
            'elite',

        'strong' =>
            'strong',

        'average' =>
            'average',

        'weak' =>
            'weak',

        'poor' =>
            'poor',

        default =>
            'neutral'
    };
}


function teamsPageFixtureClass(
    mixed $label
): string {

    $label =
        strtolower(
            trim(
                (string) $label
            )
        );


    return match ($label) {

        'excellent' =>
            'excellent',

        'good' =>
            'good',

        'average' =>
            'average',

        'difficult' =>
            'difficult',

        'very difficult' =>
            'very-difficult',

        default =>
            'neutral'
    };
}


/*
 * ============================================================
 * PAGE COUNTS
 * ============================================================
 */

$totalTeams =
    count(
        $teams
    );


$eliteTeams =
    0;


$strongTeams =
    0;


$averageIntelligence =
    null;


$averageFixture =
    null;


if (
    !empty(
        $teams
    )
) {

    $intelligenceTotal =
        0.0;


    $intelligenceCount =
        0;


    $fixtureTotal =
        0.0;


    $fixtureCount =
        0;


    foreach (
        $teams
        as $team
    ) {

        $label =
            $team[
                'intelligence_label'
            ]
            ?? null;


        if (
            $label ===
            'Elite'
        ) {

            $eliteTeams++;
        }


        if (
            $label ===
            'Strong'
        ) {

            $strongTeams++;
        }


        if (
            is_numeric(
                $team[
                    'intelligence_score'
                ]
                ?? null
            )
        ) {

            $intelligenceTotal +=
                (float) $team[
                    'intelligence_score'
                ];


            $intelligenceCount++;
        }


        if (
            is_numeric(
                $team[
                    'fixture_rating'
                ]
                ?? null
            )
        ) {

            $fixtureTotal +=
                (float) $team[
                    'fixture_rating'
                ];


            $fixtureCount++;
        }
    }


    if (
        $intelligenceCount > 0
    ) {

        $averageIntelligence =
            $intelligenceTotal
            /
            $intelligenceCount;
    }


    if (
        $fixtureCount > 0
    ) {

        $averageFixture =
            $fixtureTotal
            /
            $fixtureCount;
    }
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

    <title>
        Team Intelligence | FPL Intelligence
    </title>

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


        <div class="app-content">


            <!-- ==============================================
                 TOP BAR
                 ============================================== -->

            <header class="topbar">

                <div>

                    <p class="eyebrow">
                        Premier League Analysis
                    </p>

                    <h1>
                        Team Intelligence
                    </h1>

                    <p class="topbar-subtitle">
                        Compare all 20 Premier League teams using current
                        strength, fixture opportunity and performance-adjusted
                        Team Intelligence.
                    </p>

                </div>

            </header>


            <main class="dashboard team-dashboard">


                <?php if (
                    $pageError !== null
                ): ?>

                    <div class="alert alert-error">

                        <?= teamsPageEscape(
                            $pageError
                        ); ?>

                    </div>

                <?php elseif (
                    empty(
                        $teams
                    )
                ): ?>

                    <div class="alert">

                        No Team Intelligence data is currently available.

                    </div>

                <?php else: ?>


                    <!-- ==============================================
                         SUMMARY
                         ============================================== -->

                    <section class="dashboard-section team-summary-section">

                        <div class="section-heading">

                            <p class="eyebrow">
                                League Overview
                            </p>

                            <h2>
                                Team Intelligence Summary
                            </h2>

                        </div>


                        <div class="team-summary-grid">

                            <div class="team-summary-card">

                                <span>
                                    Teams Analysed
                                </span>

                                <strong>
                                    <?= $totalTeams; ?>
                                </strong>

                            </div>


                            <div class="team-summary-card">

                                <span>
                                    Elite Teams
                                </span>

                                <strong>
                                    <?= $eliteTeams; ?>
                                </strong>

                            </div>


                            <div class="team-summary-card">

                                <span>
                                    Strong Teams
                                </span>

                                <strong>
                                    <?= $strongTeams; ?>
                                </strong>

                            </div>


                            <div class="team-summary-card">

                                <span>
                                    Avg Intelligence
                                </span>

                                <strong>
                                    <?= teamsPageRating(
                                        $averageIntelligence,
                                        1
                                    ); ?>
                                </strong>

                            </div>


                            <div class="team-summary-card">

                                <span>
                                    Avg Fixture Rating
                                </span>

                                <strong>
                                    <?= teamsPageRating(
                                        $averageFixture,
                                        1
                                    ); ?>
                                </strong>

                            </div>

                        </div>

                    </section>


                    <!-- ==============================================
                         TEAM RANKINGS
                         ============================================== -->

                    <section class="dashboard-section team-ranking-section">

                        <div class="section-heading">

                            <p class="eyebrow">
                                Team Rankings
                            </p>

                            <h2>
                                Premier League Team Intelligence
                            </h2>

                        </div>


                        <p class="team-section-description">
                            Teams are ranked by overall Team Intelligence,
                            combining current performance-adjusted strength
                            with upcoming fixture opportunity.
                        </p>


                        <div class="team-ranking-table-wrapper">

                            <table class="team-ranking-table">

                                <thead>

                                    <tr>

                                        <th class="team-rank-column">
                                            #
                                        </th>

                                        <th>
                                            Team
                                        </th>

                                        <th>
                                            Intelligence
                                        </th>

                                        <th>
                                            Level
                                        </th>

                                        <th>
                                            Overall
                                        </th>

                                        <th>
                                            Home
                                        </th>

                                        <th>
                                            Away
                                        </th>

                                        <th>
                                            Next 5
                                        </th>

                                        <th>
                                            Fixtures
                                        </th>

                                        <th>
                                            Trend
                                        </th>

                                        <th>
                                            Form
                                        </th>

                                        <th>
                                            W-D-L
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $teams
                                        as $index => $team
                                    ): ?>

                                        <?php

                                        $intelligenceLabel =
                                            $team[
                                                'intelligence_label'
                                            ]
                                            ?? 'Unknown';


                                        $fixtureLabel =
                                            $team[
                                                'fixture_label'
                                            ]
                                            ?? 'Unknown';


                                        $recentForm =
                                            teamsPageForm(
                                                $team[
                                                    'recent_form'
                                                ]
                                                ?? []
                                            );

                                        ?>

                                        <tr>

                                            <td class="team-rank-column">

                                                <span class="team-rank-number">
                                                    <?= $index + 1; ?>
                                                </span>

                                            </td>


                                            <td class="team-name-cell">

                                                <a
                                                    href="team.php?id=<?=
                                                        (int) (
                                                            $team[
                                                                'team_id'
                                                            ]
                                                            ?? 0
                                                        );
                                                    ?>"
                                                    class="team-name-link"
                                                >

                                                    <strong>
                                                        <?= teamsPageEscape(
                                                            $team[
                                                                'name'
                                                            ]
                                                            ?? 'Unknown Team'
                                                        ); ?>
                                                    </strong>

                                                    <span>
                                                        <?= teamsPageEscape(
                                                            $team[
                                                                'short_name'
                                                            ]
                                                            ?? ''
                                                        ); ?>
                                                    </span>

                                                </a>

                                            </td>


                                            <td>

                                                <strong class="team-table-score">
                                                    <?= teamsPageRating(
                                                        $team[
                                                            'intelligence_score'
                                                        ]
                                                        ?? null,
                                                        2
                                                    ); ?>
                                                </strong>

                                            </td>


                                            <td>

                                                <span
                                                    class="team-intelligence-badge team-intelligence-badge-<?=
                                                        teamsPageLabelClass(
                                                            $intelligenceLabel
                                                        );
                                                    ?>"
                                                >
                                                    <?= teamsPageEscape(
                                                        $intelligenceLabel
                                                    ); ?>
                                                </span>

                                            </td>


                                            <td>
                                                <?= teamsPageRating(
                                                    $team[
                                                        'strength_overall'
                                                    ]
                                                    ?? null
                                                ); ?>
                                            </td>


                                            <td>
                                                <?= teamsPageRating(
                                                    $team[
                                                        'strength_home'
                                                    ]
                                                    ?? null
                                                ); ?>
                                            </td>


                                            <td>
                                                <?= teamsPageRating(
                                                    $team[
                                                        'strength_away'
                                                    ]
                                                    ?? null
                                                ); ?>
                                            </td>


                                            <td>

                                                <strong>
                                                    <?= teamsPageRating(
                                                        $team[
                                                            'fixture_rating'
                                                        ]
                                                        ?? null
                                                    ); ?>
                                                </strong>

                                            </td>


                                            <td>

                                                <span
                                                    class="team-fixture-badge team-fixture-badge-<?=
                                                        teamsPageFixtureClass(
                                                            $fixtureLabel
                                                        );
                                                    ?>"
                                                >
                                                    <?= teamsPageEscape(
                                                        $fixtureLabel
                                                    ); ?>
                                                </span>

                                            </td>


                                            <td class="team-trend-cell">

                                                <?= teamsPageEscape(
                                                    $team[
                                                        'fixture_trend'
                                                    ]
                                                    ?? '—'
                                                ); ?>

                                            </td>


                                            <td>

                                                <span class="team-form-value">

                                                    <?= teamsPageEscape(
                                                        $recentForm
                                                    ); ?>

                                                </span>

                                            </td>


                                            <td class="team-record-cell">

                                                <?= (int) (
                                                    $team[
                                                        'wins'
                                                    ]
                                                    ?? 0
                                                ); ?>
                                                -
                                                <?= (int) (
                                                    $team[
                                                        'draws'
                                                    ]
                                                    ?? 0
                                                ); ?>
                                                -
                                                <?= (int) (
                                                    $team[
                                                        'losses'
                                                    ]
                                                    ?? 0
                                                ); ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>


                        <p class="team-ranking-note">
                            Recent form and W-D-L reflect completed Premier League
                            fixtures currently stored in the project database.
                        </p>

                    </section>


                    <!-- ==============================================
                         MODEL EXPLANATION
                         ============================================== -->

                    <section class="dashboard-section team-explanation-section">

                        <div class="section-heading">

                            <p class="eyebrow">
                                How It Works
                            </p>

                            <h2>
                                Understanding Team Intelligence
                            </h2>

                        </div>


                        <div class="team-explanation-grid">

                            <div class="team-explanation-card">

                                <h3>
                                    Current Strength
                                </h3>

                                <p>
                                    Home, away and overall strength are
                                    performance-adjusted ratings. Early in
                                    the season the FPL baseline carries
                                    more influence, with actual results
                                    becoming progressively more important.
                                </p>

                            </div>


                            <div class="team-explanation-card">

                                <h3>
                                    Fixture Opportunity
                                </h3>

                                <p>
                                    Upcoming fixtures are evaluated against
                                    opponent strength to identify favourable
                                    and difficult short-term schedules.
                                </p>

                            </div>


                            <div class="team-explanation-card">

                                <h3>
                                    Team Intelligence
                                </h3>

                                <p>
                                    The overall Team Intelligence Score
                                    combines current overall strength with
                                    the team's upcoming fixture opportunity.
                                </p>

                            </div>

                        </div>

                    </section>


                <?php endif; ?>

        </main>
        
        </div>

    </div>

</body>

</html>