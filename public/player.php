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

function profileDisplayPercent(
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
        max(
            0,
            min(
                100,
                (float) $value
            )
        ),
        1
    )
    . '%';
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

$assessment =
    $profile['assessment']
    ?? [];


$assessmentVerdict =
    $assessment['verdict']
    ?? 'Insufficient Data';


$assessmentVerdictKey =
    $assessment['verdict_key']
    ?? 'insufficient_data';


$assessmentSummary =
    $assessment['summary']
    ?? '';


$assessmentStrengths =
    $assessment['strengths']
    ?? [];


$assessmentConcerns =
    $assessment['concerns']
    ?? [];


$assessmentComponents =
    $assessment['components']
    ?? []; 

$profileSummary =
    $profile['summary']
    ?? [];


$summaryPrice =
    $profileSummary['price']
    ?? null;


$summaryIntelligence =
    $profileSummary['intelligence_score']
    ?? null;


$summaryStrength =
    $profileSummary['strength_rating']
    ?? null;


$summaryValue =
    $profileSummary['value_rating']
    ?? null;


$summaryAvailability =
    $assessmentComponents['availability']
    ?? 'Unknown';


$summarySampleConfidence =
    $assessmentComponents['sample_confidence']
    ?? 'Unknown';


$summaryFixtureTrend =
    $assessmentComponents['fixture_trend']
    ?? 'Unknown';


$summaryNextFive =
    $fixtureProfile[
        'rolling_averages'
    ]['next_5']
    ?? null; 

/*
 * ============================================================
 * PLAYER FORM INTELLIGENCE
 * ============================================================
 *
 * Historical Form Intelligence is currently diagnostic only.
 *
 * These values do not yet alter the main Player Intelligence
 * Score or downstream FPL recommendations.
 */

$formRating =
    $profileSummary[
        'form_rating'
    ]
    ?? null;


$performanceRating =
    $profileSummary[
        'performance_rating'
    ]
    ?? null;


$formTrend =
    $profileSummary[
        'form_trend'
    ]
    ?? 'Insufficient Data';


$formParticipationTrend =
    $profileSummary[
        'participation_trend'
    ]
    ?? 'Insufficient Data';


$formMinutesTrend =
    $profileSummary[
        'minutes_trend'
    ]
    ?? 'Insufficient Data';


$formFixtureSampleSize =
    (int) (
        $profileSummary[
            'form_fixture_sample_size'
        ]
        ?? 0
    );


$formAppearanceSampleSize =
    (int) (
        $profileSummary[
            'form_appearance_sample_size'
        ]
        ?? 0
    );


$formZeroMinuteRows =
    (int) (
        $profileSummary[
            'form_zero_minute_rows'
        ]
        ?? 0
    );


$formParticipationRate =
    $profileSummary[
        'form_participation_rate'
    ]
    ?? null;

/*
 * ============================================================
 * EXPECTED POINTS INTELLIGENCE
 * ============================================================
 */

$projectedPoints =
    $profileSummary[
        'projected_points'
    ]
    ?? null;


$projectedMinutes =
    $profileSummary[
        'projected_minutes'
    ]
    ?? null;


$projectionConfidencePercent =
    $profileSummary[
        'projection_confidence_percent'
    ]
    ?? null;


$projectionConfidenceLabel =
    $profileSummary[
        'projection_confidence_label'
    ]
    ?? 'Unavailable';


$projectedPointsComponents =
    is_array(
        $profileSummary[
            'projected_points_components'
        ]
        ?? null
    )
        ? $profileSummary[
            'projected_points_components'
        ]
        : [];


$projectedPointsInputs =
    is_array(
        $profileSummary[
            'projected_points_inputs'
        ]
        ?? null
    )
        ? $profileSummary[
            'projected_points_inputs'
        ]
        : [];


$projectedCleanSheetProbability =
    $projectedPointsInputs[
        'clean_sheet_probability'
    ]
    ?? null;


$projectedExpectedGoals =
    $projectedPointsInputs[
        'expected_goals'
    ]
    ?? null;


$projectedExpectedAssists =
    $projectedPointsInputs[
        'expected_assists'
    ]
    ?? null;

/*
 * ============================================================
 * MULTI-GAMEWEEK EXPECTED POINTS
 * ============================================================
 */

$multiGameweekExpectedPoints =
    is_array(
        $profile[
            'multi_gameweek_expected_points'
        ]
        ?? null
    )
        ? $profile[
            'multi_gameweek_expected_points'
        ]
        : [];


$multiGameweekStatus =
    $multiGameweekExpectedPoints[
        'status'
    ]
    ?? 'Unavailable';


$multiGameweekFixtures =
    is_array(
        $multiGameweekExpectedPoints[
            'fixtures'
        ]
        ?? null
    )
        ? $multiGameweekExpectedPoints[
            'fixtures'
        ]
        : [];


$multiGameweekNext3 =
    $multiGameweekExpectedPoints[
        'next_3'
    ]
    ?? null;


$multiGameweekNext5 =
    $multiGameweekExpectedPoints[
        'next_5'
    ]
    ?? null;


$multiGameweekNext6 =
    $multiGameweekExpectedPoints[
        'next_6'
    ]
    ?? null;    

$activeNav = 'players';

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

        <?php
            require __DIR__
                . '/includes/sidebar.php';
            ?>


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

                    <?php if (
                        $profile !== null
                        &&
                        $playerId !== false
                        &&
                        $playerId !== null
                        &&
                        $playerId > 0
                    ): ?>

                        <a
                            href="compare.php?player1=<?= (int) $playerId; ?>"
                            class="profile-compare-link"
                        >
                            ⇄ Compare Player
                        </a>

                    <?php endif; ?>


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
                         PLAYER PROFILE SUMMARY
                         ====================================== -->

                    <section class="dashboard-card player-summary-card">

                        <div class="player-summary-heading">

                            <div>

                                <p class="card-kicker">
                                    Player Summary
                                </p>

                                <h2>
                                    FPL Decision Snapshot
                                </h2>

                            </div>


                            <span class="assessment-verdict assessment-verdict-<?= htmlspecialchars(
                                $assessmentVerdictKey,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>">

                                <?= htmlspecialchars(
                                    $assessmentVerdict,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>

                        </div>


                        <div class="player-summary-primary">

                            <div class="player-summary-score">

                                <span class="player-summary-score-label">
                                    Intelligence
                                </span>

                                <strong>
                                    <?= profileDisplayRating(
                                        $summaryIntelligence
                                    ); ?>
                                </strong>

                                <small>
                                    / 100
                                </small>

                            </div>


                            <div class="player-summary-metrics">

                                <div class="player-summary-metric">

                                    <span>
                                        Price
                                    </span>

                                    <strong>

                                        <?php if (
                                            $summaryPrice !== null
                                            &&
                                            is_numeric(
                                                $summaryPrice
                                            )
                                        ): ?>

                                            £<?= number_format(
                                                (float) $summaryPrice,
                                                1
                                            ); ?>m

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </strong>

                                </div>


                                <div class="player-summary-metric">

                                    <span>
                                        Strength
                                    </span>

                                    <strong>
                                        <?= profileDisplayRating(
                                            $summaryStrength
                                        ); ?>
                                    </strong>

                                </div>


                                <div class="player-summary-metric">

                                    <span>
                                        Value
                                    </span>

                                    <strong>
                                        <?= profileDisplayRating(
                                            $summaryValue
                                        ); ?>
                                    </strong>

                                </div>


                                <div class="player-summary-metric">

                                    <span>
                                        Next 5
                                    </span>

                                    <strong>
                                        <?= profileDisplayRating(
                                            $summaryNextFive
                                        ); ?>
                                    </strong>

                                </div>


                                <div class="player-summary-metric">

                                    <span>
                                        Availability
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $summaryAvailability,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </strong>

                                </div>


                                <div class="player-summary-metric">

                                    <span>
                                        Fixture Trend
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $summaryFixtureTrend,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </strong>

                                </div>

                            </div>

                        </div>


                        <div class="player-summary-footer">

                            <span>
                                Performance Sample
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $summarySampleConfidence,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </strong>

                            <span>
                                confidence
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
                         EXPECTED POINTS INTELLIGENCE
                         ====================================== -->

                    <section
                        class="dashboard-card player-expected-points-card"
                        data-player-expected-points
                    >

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Next Gameweek Intelligence
                                </p>

                                <h2>
                                    Projected Points
                                </h2>

                            </div>


                            <span class="card-badge">

                                <?= htmlspecialchars(
                                    $projectionConfidenceLabel,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                                Confidence

                            </span>

                        </div>


                        <p class="assessment-summary">

                            Projected Points estimates the player's expected FPL return
                            for the next fixture using projected minutes, recent
                            performance, fixture context and position-specific FPL
                            scoring.

                        </p>


                        <!-- ==================================
                             PRIMARY PROJECTION
                             ================================== -->

                        <div class="player-form-rating-grid">

                            <div class="player-form-rating-card">

                                <span class="player-form-rating-label">
                                    Projected Points
                                </span>

                                <strong>

                                    <?= profileDisplayNumber(
                                        $projectedPoints,
                                        2
                                    ); ?>

                                </strong>

                                <small>
                                    Expected FPL points
                                </small>

                            </div>


                            <div class="player-form-rating-card">

                                <span class="player-form-rating-label">
                                    Projected Minutes
                                </span>

                                <strong>

                                    <?= profileDisplayNumber(
                                        $projectedMinutes,
                                        0
                                    ); ?>

                                </strong>

                                <small>
                                    Expected playing time
                                </small>

                            </div>


                            <div class="player-form-rating-card">

                                <span class="player-form-rating-label">
                                    Projection Confidence
                                </span>

                                <strong>

                                    <?= profileDisplayPercent(
                                        $projectionConfidencePercent
                                    ); ?>

                                </strong>

                                <small>

                                    <?= htmlspecialchars(
                                        $projectionConfidenceLabel,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                    evidence confidence

                                </small>

                            </div>

                        </div>


                        <!-- ==================================
                             EXPECTED OUTCOMES
                             ================================== -->

                        <div class="performance-section-heading">

                            <div>

                                <p class="card-kicker">
                                    Projection Inputs
                                </p>

                                <h3>
                                    Expected Outcomes
                                </h3>

                            </div>

                        </div>


                        <div class="assessment-component-grid projected-outcomes-grid">

                            <div class="assessment-component">

                                <span>
                                    Expected Goals
                                </span>

                                <strong>

                                    <?= profileDisplayNumber(
                                        $projectedExpectedGoals,
                                        2
                                    ); ?>

                                </strong>

                            </div>


                            <div class="assessment-component">

                                <span>
                                    Expected Assists
                                </span>

                                <strong>

                                    <?= profileDisplayNumber(
                                        $projectedExpectedAssists,
                                        2
                                    ); ?>

                                </strong>

                            </div>


                            <div class="assessment-component">

                                <span>
                                    Clean Sheet Probability
                                </span>

                                <strong>

                                    <?= profileDisplayPercent(
                                        $projectedCleanSheetProbability
                                    ); ?>

                                </strong>

                            </div>

                        </div>


                        <!-- ==================================
                             FPL POINTS BREAKDOWN
                             ================================== -->

                        <div class="performance-section-heading">

                            <div>

                                <p class="card-kicker">
                                    Explainability
                                </p>

                                <h3>
                                    Points Breakdown
                                </h3>

                            </div>

                        </div>


                        <?php

                        $projectedComponentLabels = [

                            'appearance' =>
                                'Appearance',

                            'goals' =>
                                'Goals',

                            'assists' =>
                                'Assists',

                            'clean_sheet' =>
                                'Clean Sheet',

                            'goals_conceded' =>
                                'Goals Conceded',

                            'saves' =>
                                'Saves',

                            'bonus' =>
                                'Bonus',

                            'defensive_contributions' =>
                                'Defensive Contributions'
                        ];

                        ?>


                        <div class="assessment-component-grid">

                            <?php foreach (
                                $projectedComponentLabels
                                as $componentKey => $componentLabel
                            ): ?>

                                <div
                                    class="assessment-component<?= $componentKey === 'goals_conceded'
                                        ? ' projected-component-negative'
                                        : ''; ?>"
                                >

                                    <span>

                                        <?= htmlspecialchars(
                                            $componentLabel,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </span>

                                    <strong>

                                        <?= profileDisplayNumber(
                                            $projectedPointsComponents[
                                                $componentKey
                                            ]
                                            ?? 0,
                                            2
                                        ); ?>

                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <div class="player-summary-footer">

                            <span>
                                Projection
                            </span>

                            <strong>

                                <?= profileDisplayNumber(
                                    $projectedPoints,
                                    2
                                ); ?>

                            </strong>

                            <span>
                                points over
                            </span>

                            <strong>

                                <?= profileDisplayNumber(
                                    $projectedMinutes,
                                    0
                                ); ?>

                            </strong>

                            <span>
                                expected minutes
                            </span>

                        </div>

                    </section>
                    
                    
                    <section
                        class="dashboard-card player-multi-gameweek-card"
                        data-player-multi-gameweek
                    >

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Planning Intelligence
                                </p>

                                <h2>
                                    Multi-Gameweek Planning
                                </h2>

                            </div>


                            <span class="card-badge">

                                <?= htmlspecialchars(
                                    $multiGameweekStatus,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>

                        </div>


                        <p class="assessment-summary">

                            Multi-Gameweek Planning estimates this player's expected FPL
                            returns across the upcoming fixture horizon using projected
                            minutes, current form, fixture context and position-specific
                            scoring.

                        </p>


                        <?php if (
                            $multiGameweekStatus === 'Available'
                            &&
                            !empty(
                                $multiGameweekFixtures
                            )
                        ): ?>


                            <!-- ==================================
                                 PLANNING HORIZONS
                                 ================================== -->

                            <div class="performance-section-heading">

                                <div>

                                    <p class="card-kicker">
                                        Planning Horizons
                                    </p>

                                    <h3>
                                        Expected Points (xP)
                                    </h3>

                                </div>

                            </div>


                            <div class="player-multi-gameweek-horizons">

                                <div class="player-multi-gameweek-horizon">

                                    <span>
                                        Next 3
                                    </span>

                                    <strong>

                                        <?= profileDisplayNumber(
                                            $multiGameweekNext3,
                                            2
                                        ); ?>

                                    </strong>

                                    <small>
                                        Expected points (xP)
                                    </small>

                                </div>


                                <div class="player-multi-gameweek-horizon">

                                    <span>
                                        Next 5
                                    </span>

                                    <strong>

                                        <?= profileDisplayNumber(
                                            $multiGameweekNext5,
                                            2
                                        ); ?>

                                    </strong>

                                    <small>
                                        Expected points (xP)
                                    </small>

                                </div>


                                <div class="player-multi-gameweek-horizon">

                                    <span>
                                        Next 6
                                    </span>

                                    <strong>

                                        <?= profileDisplayNumber(
                                            $multiGameweekNext6,
                                            2
                                        ); ?>

                                    </strong>

                                    <small>
                                        Expected points (xP)
                                    </small>

                                </div>

                            </div>


                            <!-- ==================================
                                 UPCOMING PROJECTIONS
                                 ================================== -->

                            <div class="performance-section-heading">

                                <div>

                                    <p class="card-kicker">
                                        Fixture Forecast
                                    </p>

                                    <h3>
                                        Upcoming Projections
                                    </h3>

                                </div>

                            </div>


                            <div class="player-multi-gameweek-fixtures">

                                <?php foreach (
                                    $multiGameweekFixtures
                                    as $multiFixture
                                ): ?>


                                    <?php

                                    $multiProjection =
                                        is_array(
                                            $multiFixture[
                                                'projection'
                                            ]
                                            ?? null
                                        )
                                            ? $multiFixture[
                                                'projection'
                                            ]
                                            : [];


                                    $multiOpponentName =
                                        trim(
                                            (string) (
                                                $multiFixture[
                                                    'opponent_name'
                                                ]
                                                ?? ''
                                            )
                                        );


                                    $multiOpponentName =
                                        $multiOpponentName !== ''
                                            ? $multiOpponentName
                                            : 'Unknown Opponent';


                                    $multiIsHome =
                                        $multiFixture[
                                            'is_home'
                                        ]
                                        ?? null;


                                    $multiVenue =
                                        $multiIsHome === true
                                            ? 'H'
                                            : (
                                                $multiIsHome === false
                                                    ? 'A'
                                                    : '—'
                                            );


                                    $multiFixtureOpportunity =
                                        $multiFixture[
                                            'fixture_opportunity'
                                        ]
                                        ?? null;


                                    $multiProjectedPoints =
                                        $multiProjection[
                                            'projected_points'
                                        ]
                                        ?? null;


                                    $multiProjectedMinutes =
                                        $multiProjection[
                                            'projected_minutes'
                                        ]
                                        ?? null;


                                    $multiProjectionConfidence =
                                        $multiProjection[
                                            'projection_confidence_percent'
                                        ]
                                        ?? null;


                                    $multiProjectionConfidenceLabel =
                                        $multiProjection[
                                            'projection_confidence_label'
                                        ]
                                        ?? 'Unavailable';


                                    $multiGameweek =
                                        $multiFixture[
                                            'gameweek'
                                        ]
                                        ?? null;

                                    ?>


                                    <div class="player-multi-gameweek-fixture">

                                        <div class="player-multi-gameweek-fixture-main">

                                            <div class="player-multi-gameweek-fixture-gw">

                                                GW<?= htmlspecialchars(
                                                    (string) (
                                                        $multiGameweek
                                                        ?? '—'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </div>


                                            <div class="player-multi-gameweek-fixture-opponent">

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $multiOpponentName,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </strong>


                                                <span>

                                                    <?= htmlspecialchars(
                                                        $multiVenue,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </span>

                                            </div>

                                        </div>


                                        <div class="player-multi-gameweek-fixture-context">

                                            <span>

                                                Opportunity
                                                <strong>

                                                    <?= profileDisplayNumber(
                                                        $multiFixtureOpportunity,
                                                        1
                                                    ); ?>

                                                </strong>

                                            </span>


                                            <span>

                                                Minutes
                                                <strong>

                                                    <?= profileDisplayNumber(
                                                        $multiProjectedMinutes,
                                                        0
                                                    ); ?>

                                                </strong>

                                            </span>


                                            <span>

                                                Confidence
                                                <strong>

                                                    <?= profileDisplayPercent(
                                                        $multiProjectionConfidence
                                                    ); ?>

                                                </strong>

                                            </span>

                                        </div>


                                        <div class="player-multi-gameweek-fixture-points">

                                            <strong>

                                                <?= profileDisplayNumber(
                                                    $multiProjectedPoints,
                                                    2
                                                ); ?>

                                            </strong>

                                            <span>
                                                xP
                                            </span>

                                        </div>

                                    </div>


                                <?php endforeach; ?>

                            </div>


                            <div class="player-summary-footer">

                                <span>
                                    Projection confidence
                                </span>

                                <strong>

                                    <?= htmlspecialchars(
                                        (string) (
                                            $multiGameweekFixtures[
                                                0
                                            ][
                                                'projection'
                                            ][
                                                'projection_confidence_label'
                                            ]
                                            ?? 'Unavailable'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>

                                <span>
                                    across the upcoming planning horizon
                                </span>

                            </div>


                        <?php else: ?>


                            <div class="profile-not-found">

                                <div class="empty-icon">
                                    —
                                </div>

                                <h3>
                                    Planning Projection Unavailable
                                </h3>

                                <p>
                                    There is not yet enough upcoming fixture or player evidence
                                    to build a multi-gameweek projection.
                                </p>

                            </div>


                        <?php endif; ?>

                    </section>
                    
                    
                    <!-- ======================================
                         RECENT FORM INTELLIGENCE
                         ====================================== -->

                    <section
                        class="dashboard-card player-form-card"
                        data-player-form-intelligence
                    >

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Historical Intelligence
                                </p>

                                <h2>
                                    Recent Form
                                </h2>

                            </div>

                        </div>


                        <p class="assessment-summary">

                            Recent Form uses stored per-fixture FPL history
                            to separate overall recent form, on-pitch
                            performance and playing-time direction.

                        </p>


                        <!-- ==================================
                             FORM RATINGS
                             ================================== -->

                        <div class="player-form-rating-grid">

                            <div
                                class="player-form-rating-card"
                                data-form-rating
                            >

                                <span class="player-form-rating-label">
                                    Form Rating
                                </span>

                                <strong class="<?= profileRatingClass(
                                    $formRating
                                ); ?>">

                                    <?= profileDisplayRating(
                                        $formRating
                                    ); ?>

                                </strong>

                                <div class="player-form-rating-bar">

                                    <span
                                        style="width: <?= is_numeric(
                                            $formRating
                                        )
                                            ? min(
                                                100,
                                                max(
                                                    0,
                                                    (float) $formRating
                                                )
                                            )
                                            : 0; ?>%;"
                                    ></span>

                                </div>

                                <small>
                                    Holistic recent form
                                </small>

                            </div>


                            <div
                                class="player-form-rating-card"
                                data-performance-rating
                            >

                                <span class="player-form-rating-label">
                                    Performance Rating
                                </span>

                                <strong class="<?= profileRatingClass(
                                    $performanceRating
                                ); ?>">

                                    <?= profileDisplayRating(
                                        $performanceRating
                                    ); ?>

                                </strong>

                                <div class="player-form-rating-bar">

                                    <span
                                        style="width: <?= is_numeric(
                                            $performanceRating
                                        )
                                            ? min(
                                                100,
                                                max(
                                                    0,
                                                    (float) $performanceRating
                                                )
                                            )
                                            : 0; ?>%;"
                                    ></span>

                                </div>

                                <small>
                                    On-pitch performance
                                </small>

                            </div>


                            <div
                                class="player-form-rating-card"
                                data-form-participation-rate
                            >

                                <span class="player-form-rating-label">
                                    Participation
                                </span>

                                <strong>

                                    <?= profileDisplayPercent(
                                        $formParticipationRate
                                    ); ?>

                                </strong>

                                <div class="player-form-rating-bar">

                                    <span
                                        style="width: <?= is_numeric(
                                            $formParticipationRate
                                        )
                                            ? min(
                                                100,
                                                max(
                                                    0,
                                                    (float) $formParticipationRate
                                                )
                                            )
                                            : 0; ?>%;"
                                    ></span>

                                </div>

                                <small>
                                    Recent fixture involvement
                                </small>

                            </div>

                        </div>


                        <!-- ==================================
                             FORM TRENDS
                             ================================== -->

                        <div class="assessment-component-grid">

                            <div
                                class="assessment-component"
                                data-form-trend
                            >

                                <span>
                                    Performance Trend
                                </span>

                                <strong>

                                    <?= htmlspecialchars(
                                        $formTrend,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>

                            </div>


                            <div
                                class="assessment-component"
                                data-participation-trend
                            >

                                <span>
                                    Participation Trend
                                </span>

                                <strong>

                                    <?= htmlspecialchars(
                                        $formParticipationTrend,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>

                            </div>


                            <div
                                class="assessment-component"
                                data-minutes-trend
                            >

                                <span>
                                    Minutes Trend
                                </span>

                                <strong>

                                    <?= htmlspecialchars(
                                        $formMinutesTrend,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>

                            </div>

                        </div>


                        <!-- ==================================
                             HISTORICAL SAMPLE
                             ================================== -->

                        <div class="player-summary-footer">

                            <span>
                                Historical Sample
                            </span>


                            <strong data-form-fixture-sample>

                                <?= number_format(
                                    $formFixtureSampleSize
                                ); ?>

                            </strong>

                            <span>
                                recent fixtures
                            </span>


                            <strong data-form-appearance-sample>

                                <?= number_format(
                                    $formAppearanceSampleSize
                                ); ?>

                            </strong>

                            <span>
                                appearances
                            </span>


                            <?php if (
                                $formZeroMinuteRows > 0
                            ): ?>

                                <strong data-form-zero-minute-rows>

                                    <?= number_format(
                                        $formZeroMinuteRows
                                    ); ?>

                                </strong>

                                <span>
                                    zero-minute fixture<?= $formZeroMinuteRows === 1
                                        ? ''
                                        : 's'; ?>
                                </span>

                            <?php endif; ?>

                        </div>

                    </section>
                    
                    
                    <!-- ======================================
                         FPL ASSESSMENT
                         ====================================== -->

                    <section class="dashboard-card player-assessment-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Decision Support
                                </p>

                                <h2>
                                    FPL Assessment
                                </h2>

                            </div>


                            <span class="assessment-verdict assessment-verdict-<?= htmlspecialchars(
                                $assessmentVerdictKey,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>">

                                <?= htmlspecialchars(
                                    $assessmentVerdict,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>

                        </div>


                        <?php if (
                            $assessmentSummary !== ''
                        ): ?>

                            <p class="assessment-summary">

                                <?= htmlspecialchars(
                                    $assessmentSummary,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </p>

                        <?php endif; ?>


                        <!-- ==================================
                             COMPONENT INTERPRETATION
                             ================================== -->

                        <div class="assessment-component-grid">

                            <?php

                            $componentLabels = [

                                'strength' =>
                                    'Strength',

                                'value' =>
                                    'Value',

                                'fixtures' =>
                                    'Fixtures',

                                'availability' =>
                                    'Availability',

                                'sample_confidence' =>
                                    'Sample Confidence',

                                'fixture_trend' =>
                                    'Fixture Trend'
                            ];

                            ?>


                            <?php foreach (
                                $componentLabels
                                as $componentKey => $componentLabel
                            ): ?>

                                <div class="assessment-component">

                                    <span>

                                        <?= htmlspecialchars(
                                            $componentLabel,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </span>


                                    <strong>

                                        <?= htmlspecialchars(
                                            (string) (
                                                $assessmentComponents[
                                                    $componentKey
                                                ]
                                                ?? 'Unknown'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <!-- ==================================
                             STRENGTHS / CONCERNS
                             ================================== -->

                        <div class="assessment-insight-grid">


                            <div class="assessment-insight-column">

                                <div class="assessment-insight-heading">

                                    <span class="assessment-indicator assessment-indicator-positive"></span>

                                    <h3>
                                        Strengths
                                    </h3>

                                </div>


                                <?php if (
                                    empty(
                                        $assessmentStrengths
                                    )
                                ): ?>

                                    <p class="assessment-empty">
                                        No standout strengths identified.
                                    </p>

                                <?php else: ?>

                                    <ul class="assessment-list">

                                        <?php foreach (
                                            $assessmentStrengths
                                            as $strength
                                        ): ?>

                                            <li>

                                                <?= htmlspecialchars(
                                                    (string) $strength,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </li>

                                        <?php endforeach; ?>

                                    </ul>

                                <?php endif; ?>

                            </div>


                            <div class="assessment-insight-column">

                                <div class="assessment-insight-heading">

                                    <span class="assessment-indicator assessment-indicator-warning"></span>

                                    <h3>
                                        Watchpoints
                                    </h3>

                                </div>


                                <?php if (
                                    empty(
                                        $assessmentConcerns
                                    )
                                ): ?>

                                    <p class="assessment-empty">
                                        No major concerns identified.
                                    </p>

                                <?php else: ?>

                                    <ul class="assessment-list">

                                        <?php foreach (
                                            $assessmentConcerns
                                            as $concern
                                        ): ?>

                                            <li>

                                                <?= htmlspecialchars(
                                                    (string) $concern,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </li>

                                        <?php endforeach; ?>

                                    </ul>

                                <?php endif; ?>

                            </div>

                        </div>

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