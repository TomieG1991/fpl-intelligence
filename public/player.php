<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * DATABASE
 * ============================================================
 */

try {

    $database =
        new Database();


    $db =
        $database->getConnection();

} catch (Throwable $exception) {

    http_response_code(500);

    die(
        'Unable to connect to the database.'
    );
}


/*
 * ============================================================
 * PLAYER ID
 * ============================================================
 */

$playerId =
    filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );


/*
 * ============================================================
 * PLAYER PROFILE
 * ============================================================
 */

$profile =
    null;


$pageError =
    null;


if (
    $playerId !== false
    &&
    $playerId !== null
    &&
    $playerId > 0
) {

    try {

        $playerIntelligenceService =
            new PlayerIntelligenceService(
                $db
            );


        $profile =
            $playerIntelligenceService
                ->getPlayerProfile(
                    $playerId
                );

    } catch (Throwable $exception) {

        $pageError =
            $exception->getMessage();
    }
}


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function profileDisplayRating(
    mixed $rating
): string {

    if (
        $rating === null
        ||
        !is_numeric(
            $rating
        )
    ) {

        return '—';
    }


    return number_format(
        (float) $rating,
        1
    );
}


function profileDisplayPrice(
    mixed $price
): string {

    if (
        $price === null
        ||
        !is_numeric(
            $price
        )
    ) {

        return '—';
    }


    return '£'
        . number_format(
            (float) $price,
            1
        )
        . 'm';
}

function profileDisplayNumber(
    mixed $value,
    int $decimals = 2
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


function profileDisplayInteger(
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


    return number_format(
        (int) $value
    );
}


function profileRatingClass(
    mixed $rating
): string {

    if (
        $rating === null
        ||
        !is_numeric(
            $rating
        )
    ) {

        return 'rating-none';
    }


    $rating =
        (float) $rating;


    if ($rating >= 70) {
        return 'rating-high';
    }


    if ($rating >= 55) {
        return 'rating-medium';
    }


    return 'rating-low';
}

function profileFixtureClass(
    mixed $score
): string {

    if (
        $score === null
        ||
        !is_numeric(
            $score
        )
    ) {

        return 'fixture-neutral';
    }


    $score =
        (float) $score;


    if ($score >= 70) {
        return 'fixture-good';
    }


    if ($score >= 55) {
        return 'fixture-average';
    }


    if ($score >= 40) {
        return 'fixture-difficult';
    }


    return 'fixture-very-difficult';
}


/*
 * ============================================================
 * PROFILE DATA
 * ============================================================
 */

$player =
    $profile['player']
    ?? [];


$team =
    $profile['team']
    ?? [];


$summary =
    $profile['summary']
    ?? [];


$availability =
    $profile['availability']
    ?? [];
    
$performance =
    $profile['performance']
    ?? [];


$playerName =
    $player['name']
    ?? 'Player';


$position =
    $player['position']
    ?? '—';


$teamName =
    $team['name']
    ?? 'Unknown Team';


$price =
    $summary['price']
    ?? null;


$strengthRating =
    $summary['strength_rating']
    ?? null;


$valueRating =
    $summary['value_rating']
    ?? null;


$fixtureRating =
    $summary['fixture_rating']
    ?? null;


$availabilityRating =
    $summary['availability_rating']
    ??
    $availability[
        'availability_rating'
    ]
    ??
    null;


$intelligenceScore =
    $summary['intelligence_score']
    ?? null;


$intelligenceLabel =
    $summary['intelligence_label']
    ?? 'Unknown';


$availabilityLabel =
    $summary['availability_label']
    ??
    $availability[
        'availability_label'
    ]
    ??
    'Unknown';
    
$fixtureProfile =
    $profile['fixtures']
    ?? [];


$upcomingFixtures =
    $fixtureProfile['upcoming']
    ?? [];


$fixtureAverages =
    $fixtureProfile['rolling_averages']
    ?? [];


$fixtureCount =
    (int) (
        $fixtureProfile['fixture_count']
        ?? 0
    );
    
$fixtureBestRun =
    $fixtureProfile['best_run']
    ?? null;


$fixtureWorstRun =
    $fixtureProfile['worst_run']
    ?? null;


$fixtureTrend =
    $fixtureProfile['trend']
    ?? 'Insufficient Data';    

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Detailed FPL Intelligence player profile."
    >

    <title>
        <?= htmlspecialchars(
            $playerName,
            ENT_QUOTES,
            'UTF-8'
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


        <!-- ==================================================
             SIDEBAR
             ================================================== -->

        <aside class="sidebar">

            <div class="brand">

                <div class="brand-mark">
                    FI
                </div>

                <div>

                    <div class="brand-name">
                        FPL Intelligence
                    </div>

                    <div class="brand-version">
                        v0.13.0
                    </div>

                </div>

            </div>


            <nav
                class="main-navigation"
                aria-label="Main navigation"
            >

                <a
                    href="index.php"
                    class="nav-link"
                >
                    <span class="nav-icon">
                        ◫
                    </span>

                    Dashboard
                </a>


                <a
                    href="players.php"
                    class="nav-link active"
                >
                    <span class="nav-icon">
                        👤
                    </span>

                    Players
                </a>


                <a
                    href="#"
                    class="nav-link"
                >
                    <span class="nav-icon">
                        ⚽
                    </span>

                    Teams
                </a>


                <a
                    href="#"
                    class="nav-link"
                >
                    <span class="nav-icon">
                        ◈
                    </span>

                    Fixtures
                </a>


                <a
                    href="#"
                    class="nav-link"
                >
                    <span class="nav-icon">
                        ⇄
                    </span>

                    Transfers
                </a>


                <a
                    href="#"
                    class="nav-link"
                >
                    <span class="nav-icon">
                        ★
                    </span>

                    Squad Builder
                </a>

            </nav>


            <div class="sidebar-footer">

                <span class="status-dot online"></span>

                System Online

            </div>

        </aside>


        <!-- ==================================================
             APPLICATION CONTENT
             ================================================== -->

        <div class="app-content">


            <!-- ==============================================
                 TOP BAR
                 ============================================== -->

            <header class="topbar">

                <div>

                    <p class="eyebrow">
                        Player Intelligence
                    </p>

                    <h1>
                        Player Profile
                    </h1>

                </div>


                <div class="topbar-actions">

                    <a
                        href="players.php"
                        class="profile-back-link"
                    >
                        ← Player Explorer
                    </a>

                </div>

            </header>


            <!-- ==============================================
                 PROFILE
                 ============================================== -->

            <main class="dashboard">


                <?php if (
                    $profile === null
                ): ?>


                    <!-- ======================================
                         PLAYER NOT FOUND
                         ====================================== -->

                    <section class="dashboard-card">

                        <div class="profile-not-found">

                            <div class="empty-icon">
                                ?
                            </div>


                            <h2>
                                Player Not Found
                            </h2>


                            <p>

                                The requested player could not
                                be found or the player ID was
                                invalid.

                            </p>


                            <?php if (
                                $pageError !== null
                            ): ?>

                                <p class="profile-error-detail">

                                    <?= htmlspecialchars(
                                        $pageError,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </p>

                            <?php endif; ?>


                            <a
                                href="players.php"
                                class="profile-primary-link"
                            >
                                Return to Player Explorer
                            </a>

                        </div>

                    </section>


                <?php else: ?>


                    <!-- ======================================
                         PLAYER HERO
                         ====================================== -->

                    <section class="dashboard-card player-profile-hero">

                        <div class="player-profile-identity">

                            <div class="player-profile-position">

                                <?= htmlspecialchars(
                                    $position,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </div>


                            <div>

                                <p class="card-kicker">
                                    Player Profile
                                </p>


                                <h2 class="player-profile-name">

                                    <?= htmlspecialchars(
                                        $playerName,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </h2>


                                <div class="player-profile-meta">

                                    <span>

                                        <?= htmlspecialchars(
                                            $teamName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </span>


                                    <span>
                                        <?= htmlspecialchars(
                                            $position,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </span>


                                    <span>
                                        <?= profileDisplayPrice(
                                            $price
                                        ); ?>
                                    </span>


                                    <span>

                                        <?= htmlspecialchars(
                                            $availabilityLabel,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </span>

                                </div>

                            </div>

                        </div>


                        <div class="player-profile-overall">

                            <span class="profile-score-caption">
                                Overall Intelligence
                            </span>


                            <strong class="profile-overall-score <?= profileRatingClass(
                                $intelligenceScore
                            ); ?>">

                                <?= profileDisplayRating(
                                    $intelligenceScore
                                ); ?>

                            </strong>


                            <span class="profile-overall-label">

                                <?= htmlspecialchars(
                                    $intelligenceLabel,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>

                        </div>

                    </section>


                    <!-- ======================================
                         CORE RATINGS
                         ====================================== -->

                    <section class="player-profile-ratings">


                        <article class="dashboard-card profile-rating-card">

                            <span class="profile-rating-label">
                                Strength
                            </span>


                            <strong class="<?= profileRatingClass(
                                $strengthRating
                            ); ?>">

                                <?= profileDisplayRating(
                                    $strengthRating
                                ); ?>

                            </strong>


                            <div class="profile-rating-bar">

                                <span
                                    style="width: <?= is_numeric(
                                        $strengthRating
                                    )
                                        ? min(
                                            100,
                                            max(
                                                0,
                                                (float) $strengthRating
                                            )
                                        )
                                        : 0; ?>%;"
                                ></span>

                            </div>

                        </article>


                        <article class="dashboard-card profile-rating-card">

                            <span class="profile-rating-label">
                                Value
                            </span>


                            <strong class="<?= profileRatingClass(
                                $valueRating
                            ); ?>">

                                <?= profileDisplayRating(
                                    $valueRating
                                ); ?>

                            </strong>


                            <div class="profile-rating-bar">

                                <span
                                    style="width: <?= is_numeric(
                                        $valueRating
                                    )
                                        ? min(
                                            100,
                                            max(
                                                0,
                                                (float) $valueRating
                                            )
                                        )
                                        : 0; ?>%;"
                                ></span>

                            </div>

                        </article>


                        <article class="dashboard-card profile-rating-card">

                            <span class="profile-rating-label">
                                Fixtures
                            </span>


                            <strong class="<?= profileRatingClass(
                                $fixtureRating
                            ); ?>">

                                <?= profileDisplayRating(
                                    $fixtureRating
                                ); ?>

                            </strong>


                            <div class="profile-rating-bar">

                                <span
                                    style="width: <?= is_numeric(
                                        $fixtureRating
                                    )
                                        ? min(
                                            100,
                                            max(
                                                0,
                                                (float) $fixtureRating
                                            )
                                        )
                                        : 0; ?>%;"
                                ></span>

                            </div>

                        </article>


                        <article class="dashboard-card profile-rating-card">

                            <span class="profile-rating-label">
                                Availability
                            </span>


                            <strong class="<?= profileRatingClass(
                                $availabilityRating
                            ); ?>">

                                <?= profileDisplayRating(
                                    $availabilityRating
                                ); ?>

                            </strong>
                            
                            <span class="profile-rating-status">

                                <?= htmlspecialchars(
                                    $availabilityLabel,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>


                            <div class="profile-rating-bar">

                                <span
                                    style="width: <?= is_numeric(
                                        $availabilityRating
                                    )
                                        ? min(
                                            100,
                                            max(
                                                0,
                                                (float) $availabilityRating
                                            )
                                        )
                                        : 0; ?>%;"
                                ></span>

                            </div>

                        </article>


                    </section>


                    <!-- ======================================
                         PERFORMANCE INTELLIGENCE
                         ====================================== -->

                    <section class="dashboard-card player-performance-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Performance Intelligence
                                </p>

                                <h2>
                                    Player Performance
                                </h2>

                            </div>


                            <span class="card-badge">

                                <?= profileDisplayInteger(
                                    $performance['minutes']
                                    ?? null
                                ); ?>
                                Minutes

                            </span>

                        </div>


                        <!-- ==================================
                             RAW PERFORMANCE TOTALS
                             ================================== -->

                        <div class="performance-summary-grid">

                            <div class="performance-stat">

                                <span>
                                    Goals
                                </span>

                                <strong>
                                    <?= profileDisplayInteger(
                                        $performance['goals']
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-stat">

                                <span>
                                    Assists
                                </span>

                                <strong>
                                    <?= profileDisplayInteger(
                                        $performance['assists']
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-stat">

                                <span>
                                    Clean Sheets
                                </span>

                                <strong>
                                    <?= profileDisplayInteger(
                                        $performance['clean_sheets']
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-stat">

                                <span>
                                    Bonus
                                </span>

                                <strong>
                                    <?= profileDisplayInteger(
                                        $performance['bonus']
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-stat">

                                <span>
                                    BPS
                                </span>

                                <strong>
                                    <?= profileDisplayInteger(
                                        $performance['bps']
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-stat">

                                <span>
                                    ICT Index
                                </span>

                                <strong>
                                    <?= profileDisplayNumber(
                                        $performance['ict_index']
                                        ?? null,
                                        1
                                    ); ?>
                                </strong>

                            </div>

                        </div>


                        <!-- ==================================
                             PER 90 METRICS
                             ================================== -->

                        <div class="performance-section-heading">

                            <div>

                                <p class="card-kicker">
                                    Rate Statistics
                                </p>

                                <h3>
                                    Per 90 Minutes
                                </h3>

                            </div>

                        </div>


                        <div class="performance-metric-grid">

                            <div class="performance-metric">

                                <span>
                                    Goals / 90
                                </span>

                                <strong>
                                    <?= profileDisplayNumber(
                                        $performance[
                                            'goals_per_90'
                                        ]
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-metric">

                                <span>
                                    Assists / 90
                                </span>

                                <strong>
                                    <?= profileDisplayNumber(
                                        $performance[
                                            'assists_per_90'
                                        ]
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-metric">

                                <span>
                                    xG / 90
                                </span>

                                <strong>
                                    <?= profileDisplayNumber(
                                        $performance[
                                            'expected_goals_per_90'
                                        ]
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-metric">

                                <span>
                                    xA / 90
                                </span>

                                <strong>
                                    <?= profileDisplayNumber(
                                        $performance[
                                            'expected_assists_per_90'
                                        ]
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-metric">

                                <span>
                                    xGI / 90
                                </span>

                                <strong>
                                    <?= profileDisplayNumber(
                                        $performance[
                                            'expected_goal_involvements_per_90'
                                        ]
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-metric">

                                <span>
                                    CS / 90
                                </span>

                                <strong>
                                    <?= profileDisplayNumber(
                                        $performance[
                                            'clean_sheets_per_90'
                                        ]
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-metric">

                                <span>
                                    BPS / 90
                                </span>

                                <strong>
                                    <?= profileDisplayNumber(
                                        $performance[
                                            'bps_per_90'
                                        ]
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>

                        </div>


                        <!-- ==================================
                             UNDERLYING EXPECTED METRICS
                             ================================== -->

                        <div class="performance-section-heading">

                            <div>

                                <p class="card-kicker">
                                    Underlying Data
                                </p>

                                <h3>
                                    Expected Performance
                                </h3>

                            </div>

                        </div>


                        <div class="performance-metric-grid performance-metric-grid-small">

                            <div class="performance-metric">

                                <span>
                                    Expected Goals
                                </span>

                                <strong>
                                    <?= profileDisplayNumber(
                                        $performance[
                                            'expected_goals'
                                        ]
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-metric">

                                <span>
                                    Expected Assists
                                </span>

                                <strong>
                                    <?= profileDisplayNumber(
                                        $performance[
                                            'expected_assists'
                                        ]
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>


                            <div class="performance-metric">

                                <span>
                                    Expected GI
                                </span>

                                <strong>
                                    <?= profileDisplayNumber(
                                        $performance[
                                            'expected_goal_involvements'
                                        ]
                                        ?? null
                                    ); ?>
                                </strong>

                            </div>

                        </div>

                    </section>


                    <!-- ======================================
                         PERFORMANCE RATINGS
                         ====================================== -->

                    <section class="dashboard-card player-performance-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Strength Components
                                </p>

                                <h2>
                                    Performance Ratings
                                </h2>

                            </div>


                            <span class="card-badge">

                                <?= profileDisplayRating(
                                    (
                                        isset(
                                            $performance[
                                                'sample_confidence'
                                            ]
                                        )
                                        &&
                                        is_numeric(
                                            $performance[
                                                'sample_confidence'
                                            ]
                                        )
                                    )
                                        ? (
                                            $performance[
                                                'sample_confidence'
                                            ]
                                            * 100
                                        )
                                        : null
                                ); ?>%
                                Confidence

                            </span>

                        </div>


                        <p class="performance-explanation">

                            These ratings are adjusted for the player's
                            minutes sample before contributing to Player
                            Strength. Smaller samples are pulled towards
                            a neutral 50 rating.

                        </p>


                        <div class="performance-rating-grid">

                            <?php

                            $performanceRatings = [

                                [
                                    'label' => 'Goals',
                                    'rating' =>
                                        $performance[
                                            'adjusted_goals_rating'
                                        ]
                                        ?? null
                                ],

                                [
                                    'label' => 'Assists',
                                    'rating' =>
                                        $performance[
                                            'adjusted_assists_rating'
                                        ]
                                        ?? null
                                ],

                                [
                                    'label' => 'Expected Goals',
                                    'rating' =>
                                        $performance[
                                            'adjusted_expected_goals_rating'
                                        ]
                                        ?? null
                                ],

                                [
                                    'label' => 'Expected Assists',
                                    'rating' =>
                                        $performance[
                                            'adjusted_expected_assists_rating'
                                        ]
                                        ?? null
                                ],

                                [
                                    'label' => 'Clean Sheets',
                                    'rating' =>
                                        $performance[
                                            'adjusted_clean_sheets_rating'
                                        ]
                                        ?? null
                                ],

                                [
                                    'label' => 'BPS',
                                    'rating' =>
                                        $performance[
                                            'adjusted_bps_rating'
                                        ]
                                        ?? null
                                ]
                            ];

                            ?>


                            <?php foreach (
                                $performanceRatings
                                as $performanceRating
                            ): ?>

                                <?php

                                $componentRating =
                                    $performanceRating[
                                        'rating'
                                    ];

                                ?>

                                <div class="performance-rating-item">

                                    <div class="performance-rating-header">

                                        <span>

                                            <?= htmlspecialchars(
                                                $performanceRating[
                                                    'label'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>


                                        <strong class="<?= profileRatingClass(
                                            $componentRating
                                        ); ?>">

                                            <?= profileDisplayRating(
                                                $componentRating
                                            ); ?>

                                        </strong>

                                    </div>


                                    <div class="profile-rating-bar">

                                        <span
                                            style="width: <?= is_numeric(
                                                $componentRating
                                            )
                                                ? min(
                                                    100,
                                                    max(
                                                        0,
                                                        (float) $componentRating
                                                    )
                                                )
                                                : 0; ?>%;"
                                        ></span>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </section>
                    
                    <!-- ======================================
                         FIXTURE OUTLOOK
                         ====================================== -->

                    <section class="dashboard-card player-fixture-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Fixture Intelligence
                                </p>

                                <h2>
                                    Fixture Outlook
                                </h2>

                            </div>


                            <span class="card-badge">

                                <?= $fixtureCount; ?>
                                Upcoming Fixtures

                            </span>

                        </div>


                        <p class="fixture-explanation">

                            Opportunity measures the quality of the
                            opposition from the player's perspective.
                            Higher scores indicate weaker opposition and
                            therefore greater short-term FPL opportunity.

                        </p>


                        <!-- ==================================
                             ROLLING OPPORTUNITY
                             ================================== -->

                        <div class="fixture-average-grid">

                            <?php

                            $fixtureAverageCards = [

                                [
                                    'label' => 'Next 5',
                                    'value' =>
                                        $fixtureAverages[
                                            'next_5'
                                        ]
                                        ?? null
                                ],

                                [
                                    'label' => 'Next 6',
                                    'value' =>
                                        $fixtureAverages[
                                            'next_6'
                                        ]
                                        ?? null
                                ],

                                [
                                    'label' => 'Next 8',
                                    'value' =>
                                        $fixtureAverages[
                                            'next_8'
                                        ]
                                        ?? null
                                ],

                                [
                                    'label' => 'Next 10',
                                    'value' =>
                                        $fixtureAverages[
                                            'next_10'
                                        ]
                                        ?? null
                                ]
                            ];

                            ?>


                            <?php foreach (
                                $fixtureAverageCards
                                as $averageCard
                            ): ?>

                                <div class="fixture-average-card">

                                    <span>
                                        <?= htmlspecialchars(
                                            $averageCard['label'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </span>


                                    <strong class="<?= profileFixtureClass(
                                        $averageCard['value']
                                    ); ?>">

                                        <?= profileDisplayRating(
                                            $averageCard['value']
                                        ); ?>

                                    </strong>


                                    <div class="profile-rating-bar">

                                        <span
                                            style="width: <?= is_numeric(
                                                $averageCard['value']
                                            )
                                                ? min(
                                                    100,
                                                    max(
                                                        0,
                                                        (float)
                                                            $averageCard[
                                                                'value'
                                                            ]
                                                    )
                                                )
                                                : 0; ?>%;"
                                        ></span>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>
                        
                        <div class="fixture-insight-grid">

                            <div class="fixture-insight-card">

                                <span class="fixture-insight-label">
                                    Fixture Trend
                                </span>

                                <strong>

                                    <?= htmlspecialchars(
                                        $fixtureTrend,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>

                            </div>


                            <div class="fixture-insight-card">

                                <span class="fixture-insight-label">
                                    Best 5-Fixture Run
                                </span>

                                <?php if ($fixtureBestRun !== null): ?>

                                    <strong>

                                        GW<?= (int) (
                                            $fixtureBestRun[
                                                'start_gameweek'
                                            ]
                                            ?? 0
                                        ); ?>
                                        -
                                        GW<?= (int) (
                                            $fixtureBestRun[
                                                'end_gameweek'
                                            ]
                                            ?? 0
                                        ); ?>

                                    </strong>

                                    <small>

                                        <?= profileDisplayRating(
                                            $fixtureBestRun[
                                                'average_score'
                                            ]
                                            ?? null
                                        ); ?>

                                        average opportunity

                                    </small>

                                <?php else: ?>

                                    <strong>
                                        —
                                    </strong>

                                <?php endif; ?>

                            </div>


                            <div class="fixture-insight-card">

                                <span class="fixture-insight-label">
                                    Toughest 5-Fixture Run
                                </span>

                                <?php if ($fixtureWorstRun !== null): ?>

                                    <strong>

                                        GW<?= (int) (
                                            $fixtureWorstRun[
                                                'start_gameweek'
                                            ]
                                            ?? 0
                                        ); ?>
                                        -
                                        GW<?= (int) (
                                            $fixtureWorstRun[
                                                'end_gameweek'
                                            ]
                                            ?? 0
                                        ); ?>

                                    </strong>

                                    <small>

                                        <?= profileDisplayRating(
                                            $fixtureWorstRun[
                                                'average_score'
                                            ]
                                            ?? null
                                        ); ?>

                                        average opportunity

                                    </small>

                                <?php else: ?>

                                    <strong>
                                        —
                                    </strong>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- ==================================
                             UPCOMING FIXTURE RUN
                             ================================== -->

                        <div class="fixture-section-heading">

                            <div>

                                <p class="card-kicker">
                                    Upcoming Run
                                </p>

                                <h3>
                                    Next Fixtures
                                </h3>

                            </div>

                        </div>


                        <?php if (
                            empty(
                                $upcomingFixtures
                            )
                        ): ?>

                            <div class="empty-state">

                                <div class="empty-icon">
                                    ◈
                                </div>

                                <h3>
                                    No Upcoming Fixtures
                                </h3>

                                <p>

                                    There are currently no upcoming
                                    fixtures available for this player.

                                </p>

                            </div>

                        <?php else: ?>

                            <div class="fixture-table-wrapper">

                                <table class="fixture-outlook-table">

                                    <thead>

                                        <tr>

                                            <th>
                                                GW
                                            </th>

                                            <th>
                                                Opponent
                                            </th>

                                            <th>
                                                Venue
                                            </th>

                                            <th>
                                                Opportunity
                                            </th>

                                            <th>
                                                Difficulty
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                    <?php foreach (
                                        $upcomingFixtures
                                        as $fixture
                                    ): ?>

                                        <?php

                                        $isHome =
                                            (bool) (
                                                $fixture['is_home']
                                                ?? false
                                            );


                                        $opponentName =
                                            $isHome
                                                ? (
                                                    $fixture[
                                                        'away_team'
                                                    ]
                                                    ?? 'Unknown'
                                                )
                                                : (
                                                    $fixture[
                                                        'home_team'
                                                    ]
                                                    ?? 'Unknown'
                                                );


                                        $opportunityScore =
                                            $fixture[
                                                'opportunity_score'
                                            ]
                                            ?? null;


                                        $difficulty =
                                            $fixture[
                                                'difficulty'
                                            ]
                                            ?? null;


                                        $difficultyLabel =
                                            $fixture[
                                                'difficulty_label'
                                            ]
                                            ?? 'Unknown';

                                        ?>

                                        <tr>

                                            <td class="fixture-gameweek">

                                                <?= $fixture['gameweek']
                                                    !== null
                                                    ? 'GW'
                                                        . (int)
                                                            $fixture[
                                                                'gameweek'
                                                            ]
                                                    : 'TBC'; ?>

                                            </td>


                                            <td class="fixture-opponent">

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $opponentName,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </strong>

                                            </td>


                                            <td>

                                                <span class="fixture-venue">

                                                    <?= htmlspecialchars(
                                                        $fixture['venue']
                                                        ?? '—',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </span>

                                            </td>


                                            <td>

                                                <strong class="<?= profileFixtureClass(
                                                    $opportunityScore
                                                ); ?>">

                                                    <?= profileDisplayRating(
                                                        $opportunityScore
                                                    ); ?>

                                                </strong>

                                            </td>


                                            <td>

                                                <span class="fixture-difficulty">

                                                    <?php if (
                                                        is_numeric(
                                                            $difficulty
                                                        )
                                                    ): ?>

                                                        <strong>
                                                            <?= (int) $difficulty; ?>
                                                        </strong>

                                                    <?php endif; ?>


                                                    <?= htmlspecialchars(
                                                        $difficultyLabel,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </span>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        <?php endif; ?>


                        <!-- ==================================
                             VISUAL FIXTURE RUN
                             ================================== -->

                        <?php if (
                            !empty(
                                $upcomingFixtures
                            )
                        ): ?>

                            <div class="fixture-section-heading">

                                <div>

                                    <p class="card-kicker">
                                        Quick View
                                    </p>

                                    <h3>
                                        Fixture Run
                                    </h3>

                                </div>

                            </div>


                            <div class="fixture-run-strip">

                                <?php foreach (
                                    $upcomingFixtures
                                    as $fixture
                                ): ?>

                                    <?php

                                    $isHome =
                                        (bool) (
                                            $fixture['is_home']
                                            ?? false
                                        );


                                    $opponentName =
                                        $isHome
                                            ? (
                                                $fixture[
                                                    'away_team'
                                                ]
                                                ?? 'Unknown'
                                            )
                                            : (
                                                $fixture[
                                                    'home_team'
                                                ]
                                                ?? 'Unknown'
                                            );


                                    $opportunityScore =
                                        $fixture[
                                            'opportunity_score'
                                        ]
                                        ?? null;

                                    ?>

                                    <div class="fixture-run-item">

                                        <span class="fixture-run-gw">

                                            <?= $fixture['gameweek']
                                                !== null
                                                ? 'GW'
                                                    . (int)
                                                        $fixture[
                                                            'gameweek'
                                                        ]
                                                : 'TBC'; ?>

                                        </span>


                                        <strong>

                                            <?= htmlspecialchars(
                                                $opponentName,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </strong>


                                        <span class="fixture-run-venue">

                                            <?= $isHome
                                                ? 'H'
                                                : 'A'; ?>

                                        </span>


                                        <span class="fixture-run-score <?= profileFixtureClass(
                                            $opportunityScore
                                        ); ?>">

                                            <?= profileDisplayRating(
                                                $opportunityScore
                                            ); ?>

                                        </span>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </section>


                <?php endif; ?>


            </main>


            <!-- ==============================================
                 FOOTER
                 ============================================== -->

            <footer class="footer">

                <span>
                    FPL Intelligence
                </span>

                <span>
                    Player Intelligence Profile
                </span>

            </footer>


        </div>

    </div>


    <script src="assets/js/app.js"></script>

</body>

</html>