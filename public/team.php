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
 * INPUT
 * ============================================================
 */

$teamId =
    isset(
        $_GET[
            'id'
        ]
    )
    &&
    is_numeric(
        $_GET[
            'id'
        ]
    )
        ? (int) $_GET[
            'id'
        ]
        : 0;


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

$profile =
    null;


$pageError =
    null;


try {

    $database =
        new Database();


    $service =
        new PlayerIntelligenceService(
            $database->getConnection()
        );


    if (
        $teamId <= 0
    ) {

        $pageError =
            'A valid team must be selected.';

    } else {

        $profile =
            $service
                ->getTeamIntelligenceProfile(
                    $teamId
                );


        if (
            (
                $profile[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            $pageError =
                $profile[
                    'message'
                ]
                ?? 'Unable to load this Team Intelligence profile.';
        }
    }

} catch (
    Throwable $exception
) {

    $pageError =
        'Unable to load this Team Intelligence profile at the moment.';
}


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function teamProfileEscape(
    mixed $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function teamProfileRating(
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


function teamProfilePrice(
    mixed $value
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


    return '£'
        . number_format(
            (float) $value,
            1
        )
        . 'm';
}


function teamProfileClass(
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


function teamProfileFixtureClass(
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


function teamProfileForm(
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


/*
 * ============================================================
 * PROFILE DATA
 * ============================================================
 */

$team =
    is_array(
        $profile
    )
        ? (
            $profile[
                'team'
            ]
            ?? []
        )
        : [];


$ranking =
    is_array(
        $profile
    )
        ? (
            $profile[
                'ranking'
            ]
            ?? []
        )
        : [];


$strength =
    is_array(
        $profile
    )
        ? (
            $profile[
                'strength'
            ]
            ?? []
        )
        : [];


$fixtures =
    is_array(
        $profile
    )
        ? (
            $profile[
                'fixtures'
            ]
            ?? []
        )
        : [];


$form =
    is_array(
        $profile
    )
        ? (
            $profile[
                'form'
            ]
            ?? []
        )
        : [];


$players =
    is_array(
        $profile
    )
        ? (
            $profile[
                'players'
            ]
            ?? []
        )
        : [];


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
        <?= teamProfileEscape(
            $team[
                'name'
            ]
            ?? 'Team Intelligence'
        ); ?>
        | FPL Intelligence
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
                    Team Intelligence Profile
                </p>

                <h1>
                    <?= teamProfileEscape(
                        $team[
                            'name'
                        ]
                        ?? 'Team Intelligence'
                    ); ?>
                </h1>

                <p class="topbar-subtitle">
                    Detailed team strength, fixture opportunity,
                    current form and FPL player intelligence.
                </p>

            </div>

        </header>


        <main class="dashboard team-profile-dashboard">


            <a
                href="teams.php"
                class="team-profile-back-link"
            >
                ← Back to Team Intelligence
            </a>


            <?php if (
                $pageError !== null
            ): ?>

                <div class="alert alert-error">

                    <?= teamProfileEscape(
                        $pageError
                    ); ?>

                </div>

            <?php else: ?>


                <!-- ==============================================
                     PROFILE HERO
                     ============================================== -->

                <section class="dashboard-section team-profile-hero-section">

                    <div class="team-profile-hero">

                        <div>

                            <p class="eyebrow">
                                League Rank
                            </p>

                            <h2>
                                #<?= (int) (
                                    $ranking[
                                        'rank'
                                    ]
                                    ?? 0
                                ); ?>
                                <?= teamProfileEscape(
                                    $team[
                                        'name'
                                    ]
                                    ?? ''
                                ); ?>
                            </h2>

                            <p class="team-profile-short-name">
                                <?= teamProfileEscape(
                                    $team[
                                        'short_name'
                                    ]
                                    ?? ''
                                ); ?>
                            </p>

                        </div>


                        <div class="team-profile-hero-badges">

                            <div class="team-profile-score">

                                <span>
                                    Team Intelligence
                                </span>

                                <strong>
                                    <?= teamProfileRating(
                                        $ranking[
                                            'intelligence_score'
                                        ]
                                        ?? null,
                                        2
                                    ); ?>
                                </strong>

                            </div>


                            <span
                                class="team-intelligence-badge team-intelligence-badge-<?=
                                    teamProfileClass(
                                        $ranking[
                                            'intelligence_label'
                                        ]
                                        ?? ''
                                    );
                                ?>"
                            >
                                <?= teamProfileEscape(
                                    $ranking[
                                        'intelligence_label'
                                    ]
                                    ?? 'Unknown'
                                ); ?>
                            </span>

                        </div>

                    </div>

                </section>


                <!-- ==============================================
                     STRENGTH SUMMARY
                     ============================================== -->

                <section class="dashboard-section team-profile-strength-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Team Strength
                        </p>

                        <h2>
                            Current Strength Profile
                        </h2>

                    </div>


                    <div class="team-profile-summary-grid">

                        <div class="team-profile-summary-card">

                            <span>
                                Overall
                            </span>

                            <strong>
                                <?= teamProfileRating(
                                    $strength[
                                        'overall'
                                    ]
                                    ?? null
                                ); ?>
                            </strong>

                        </div>


                        <div class="team-profile-summary-card">

                            <span>
                                Home
                            </span>

                            <strong>
                                <?= teamProfileRating(
                                    $strength[
                                        'home'
                                    ]
                                    ?? null
                                ); ?>
                            </strong>

                        </div>


                        <div class="team-profile-summary-card">

                            <span>
                                Away
                            </span>

                            <strong>
                                <?= teamProfileRating(
                                    $strength[
                                        'away'
                                    ]
                                    ?? null
                                ); ?>
                            </strong>

                        </div>


                        <div class="team-profile-summary-card">

                            <span>
                                Fixture Rating
                            </span>

                            <strong>
                                <?= teamProfileRating(
                                    $fixtures[
                                        'rating'
                                    ]
                                    ?? null
                                ); ?>
                            </strong>

                        </div>


                        <div class="team-profile-summary-card">

                            <span>
                                Fixture Trend
                            </span>

                            <strong>
                                <?= teamProfileEscape(
                                    $fixtures[
                                        'trend'
                                    ]
                                    ?? '—'
                                ); ?>
                            </strong>

                        </div>

                    </div>

                </section>


                <!-- ==============================================
                     FIXTURE INTELLIGENCE
                     ============================================== -->

                <section class="dashboard-section team-profile-fixtures-section">

                    <div class="section-heading team-profile-fixture-heading">

                        <div>

                            <p class="eyebrow">
                                Fixture Intelligence
                            </p>

                            <h2>
                                Upcoming Fixture Opportunity
                            </h2>

                        </div>


                        <span
                            class="team-fixture-badge team-fixture-badge-<?=
                                teamProfileFixtureClass(
                                    $fixtures[
                                        'label'
                                    ]
                                    ?? ''
                                );
                            ?>"
                        >
                            <?= teamProfileEscape(
                                $fixtures[
                                    'label'
                                ]
                                ?? 'Unknown'
                            ); ?>
                        </span>

                    </div>


                    <div class="team-profile-fixture-summary">

                        <div>

                            <span>
                                Next 5 Rating
                            </span>

                            <strong>
                                <?= teamProfileRating(
                                    $fixtures[
                                        'next_5'
                                    ]
                                    ?? $fixtures[
                                        'rating'
                                    ]
                                    ?? null
                                ); ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Next 6
                            </span>

                            <strong>
                                <?= teamProfileRating(
                                    $fixtures[
                                        'next_6'
                                    ]
                                    ?? null
                                ); ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Next 8
                            </span>

                            <strong>
                                <?= teamProfileRating(
                                    $fixtures[
                                        'next_8'
                                    ]
                                    ?? null
                                ); ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Next 10
                            </span>

                            <strong>
                                <?= teamProfileRating(
                                    $fixtures[
                                        'next_10'
                                    ]
                                    ?? null
                                ); ?>
                            </strong>

                        </div>

                    </div>


                    <?php

                    $upcomingFixtures =
                        $fixtures[
                            'upcoming'
                        ]
                        ?? [];

                    ?>


                    <?php if (
                        empty(
                            $upcomingFixtures
                        )
                    ): ?>

                        <div class="empty-state">

                            <h3>
                                No upcoming fixtures available
                            </h3>

                            <p>
                                Upcoming Premier League fixtures have not
                                been loaded for this team yet.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="team-profile-fixture-list">

                            <?php foreach (
                                $upcomingFixtures
                                as $index => $fixture
                            ): ?>

                                <div class="team-profile-fixture-card">

                                    <div class="team-profile-fixture-number">
                                        <?= $index + 1; ?>
                                    </div>


                                    <div>

                                        <strong>
                                            <?= teamProfileEscape(
                                                $fixture[
                                                    'opponent_name'
                                                ]
                                                ?? 'Unknown'
                                            ); ?>
                                        </strong>

                                        <span>
                                            <?= teamProfileEscape(
                                                $fixture[
                                                    'venue'
                                                ]
                                                ?? '—'
                                            ); ?>
                                        </span>

                                    </div>


                                    <div class="team-profile-fixture-gameweek">

                                        GW
                                        <?= teamProfileEscape(
                                            $fixture[
                                                'gameweek'
                                            ]
                                            ?? '—'
                                        ); ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </section>


                <!-- ==============================================
                     CURRENT FORM
                     ============================================== -->

                <section class="dashboard-section team-profile-form-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Current Form
                        </p>

                        <h2>
                            Premier League Performance
                        </h2>
                        
                        <p class="team-profile-performance-description">
                            Attack and Defence Ratings use completed Premier League
                            matches only. Ratings remain unavailable until the team has
                            played at least one league fixture.
                        </p>

                    </div>


                    <div class="team-profile-form-grid">

                        <div>

                            <span>
                                Recent Form
                            </span>

                            <strong>
                                <?= teamProfileEscape(
                                    teamProfileForm(
                                        $form[
                                            'recent_form'
                                        ]
                                        ?? []
                                    )
                                ); ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Played
                            </span>

                            <strong>
                                <?= (int) (
                                    $form[
                                        'played'
                                    ]
                                    ?? 0
                                ); ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Wins
                            </span>

                            <strong>
                                <?= (int) (
                                    $form[
                                        'wins'
                                    ]
                                    ?? 0
                                ); ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Draws
                            </span>

                            <strong>
                                <?= (int) (
                                    $form[
                                        'draws'
                                    ]
                                    ?? 0
                                ); ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Losses
                            </span>

                            <strong>
                                <?= (int) (
                                    $form[
                                        'losses'
                                    ]
                                    ?? 0
                                ); ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Goal Difference
                            </span>

                            <strong>
                                <?php

                                $goalDifference =
                                    (int) (
                                        $form[
                                            'goal_difference'
                                        ]
                                        ?? 0
                                    );


                                echo $goalDifference > 0
                                    ? '+'
                                    : '';


                                echo $goalDifference;

                                ?>
                            </strong>

                        </div>
                        
                        <div class="team-profile-form-rating">

                            <span>
                                Attack Rating
                            </span>

                            <strong>
                                <?= teamProfileRating(
                                    $form[
                                        'attack_rating'
                                    ]
                                    ?? null
                                ); ?>
                            </strong>

                            <small>
                                Goals scored per game
                            </small>

                        </div>


                        <div class="team-profile-form-rating">

                            <span>
                                Defence Rating
                            </span>

                            <strong>
                                <?= teamProfileRating(
                                    $form[
                                        'defence_rating'
                                    ]
                                    ?? null
                                ); ?>
                            </strong>

                            <small>
                                Goals conceded per game
                            </small>

                        </div>

                    </div>

                </section>


                <!-- ==============================================
                     FPL PLAYERS
                     ============================================== -->

                <section class="dashboard-section team-profile-players-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            FPL Players
                        </p>

                        <h2>
                            Current Player Intelligence
                        </h2>

                    </div>


                    <p class="team-section-description">
                        Current FPL players for
                        <?= teamProfileEscape(
                            $team[
                                'name'
                            ]
                            ?? ''
                        ); ?>
                        ranked by Player Intelligence.
                    </p>


                    <div class="team-profile-player-table-wrapper">

                        <table class="team-profile-player-table">

                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        Player
                                    </th>

                                    <th>
                                        Pos
                                    </th>

                                    <th>
                                        Price
                                    </th>

                                    <th>
                                        Intelligence
                                    </th>

                                    <th>
                                        Strength
                                    </th>

                                    <th>
                                        Value
                                    </th>

                                    <th>
                                        Fixtures
                                    </th>

                                    <th>
                                        Availability
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach (
                                    $players
                                    as $index => $player
                                ): ?>

                                    <tr>

                                        <td>
                                            <?= $index + 1; ?>
                                        </td>


                                        <td>

                                            <a
                                                href="player.php?id=<?=
                                                    (int) (
                                                        $player[
                                                            'player_id'
                                                        ]
                                                        ?? 0
                                                    );
                                                ?>"
                                                class="team-profile-player-link"
                                            >
                                                <?= teamProfileEscape(
                                                    $player[
                                                        'name'
                                                    ]
                                                    ?? 'Unknown Player'
                                                ); ?>
                                            </a>

                                        </td>


                                        <td>
                                            <?= teamProfileEscape(
                                                $player[
                                                    'position'
                                                ]
                                                ?? '—'
                                            ); ?>
                                        </td>


                                        <td>
                                            <?= teamProfilePrice(
                                                $player[
                                                    'price'
                                                ]
                                                ?? null
                                            ); ?>
                                        </td>


                                        <td>
                                            <strong>
                                                <?= teamProfileRating(
                                                    $player[
                                                        'intelligence_score'
                                                    ]
                                                    ?? null,
                                                    2
                                                ); ?>
                                            </strong>
                                        </td>


                                        <td>
                                            <?= teamProfileRating(
                                                $player[
                                                    'strength_rating'
                                                ]
                                                ?? null
                                            ); ?>
                                        </td>


                                        <td>
                                            <?= teamProfileRating(
                                                $player[
                                                    'value_rating'
                                                ]
                                                ?? null
                                            ); ?>
                                        </td>


                                        <td>
                                            <?= teamProfileRating(
                                                $player[
                                                    'fixture_rating'
                                                ]
                                                ?? null
                                            ); ?>
                                        </td>


                                        <td>
                                            <?= teamProfileRating(
                                                $player[
                                                    'availability_rating'
                                                ]
                                                ?? null
                                            ); ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </section>


            <?php endif; ?>


        </main>

    </div>

</div>

</body>

</html>