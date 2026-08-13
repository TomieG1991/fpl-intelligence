<?php

require_once __DIR__ . '/../classes/autoload.php';


/*
 * ============================================================
 * APPLICATION DATA
 * ============================================================
 */

$databaseConnected =
    false;

$teamCount =
    0;

$playerCount =
    0;

$fixtureCount =
    0;
    
$topPlayers = 
    [];


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


    $databaseConnected =
        true;


    /*
     * --------------------------------------------------------
     * REPOSITORIES
     * --------------------------------------------------------
     */

    $teamRepository =
        new TeamRepository(
            $db
        );


    $playerRepository =
        new PlayerRepository(
            $db
        );


    $fixtureRepository =
        new FixtureRepository(
            $db
        );


    /*
     * --------------------------------------------------------
     * APPLICATION COUNTS
     * --------------------------------------------------------
     */

    $teams =
        $teamRepository->getAll();


    $players =
        $playerRepository->getAll();


    $fixtures =
        $fixtureRepository->getAll();


    $teamCount =
        count(
            $teams
        );


    $playerCount =
        count(
            $players
        );


    $fixtureCount =
        count(
            $fixtures
        );
        
    
        /*
     * --------------------------------------------------------
     * TEAM STRENGTH BASELINES
     * --------------------------------------------------------
     */

    $teamStrength =
        new TeamStrength();


    $teamBaselines =
        $teamStrength
            ->calculateTeamStrengths(
                $teams
            );


    /*
     * --------------------------------------------------------
     * TEAM PERFORMANCE
     * --------------------------------------------------------
     */

    $teamPerformance =
        new TeamPerformance();


    $teamStrengthModel =
        new TeamStrengthModel();


    $completeTeamModels =
        [];


    foreach (
        $teamBaselines
        as $teamId => $baseline
    ) {

        $performance =
            $teamPerformance
                ->analyse(
                    $fixtures,
                    (int) $teamId
                );


        $completeTeamModels[$teamId] =
            $teamStrengthModel
                ->buildTeamModel(
                    $baseline,
                    $performance,
                    $teamPerformance
                );
    }


    /*
     * --------------------------------------------------------
     * FIXTURE INTELLIGENCE
     * --------------------------------------------------------
     */

    $fixtureIntelligence =
        new FixtureIntelligence();


    $teamFixtureRatings =
        [];


    foreach (
        $completeTeamModels
        as $teamId => $teamModel
    ) {

        /*
         * Only the next five unfinished fixtures
         * are required for the dashboard rating.
         */
        $upcomingFixtures =
            $fixtureRepository
                ->getUpcomingForTeam(
                    (int) $teamId,
                    5
                );


        $fixtureRun =
            $fixtureIntelligence
                ->analyseFixtureRun(
                    $upcomingFixtures,
                    $completeTeamModels,
                    (int) $teamId
                );


        $opportunityAverages =
            $fixtureIntelligence
                ->calculateOpportunityAverages(
                    $fixtureRun
                );


        $teamFixtureRatings[$teamId] =
            $opportunityAverages['next_5']
            ?? null;
    }


    /*
     * --------------------------------------------------------
     * PLAYER INTELLIGENCE ENGINE
     * --------------------------------------------------------
     */

    $playerPerformance =
        new PlayerPerformance();


    $playerStrength =
        new PlayerStrengthModel();


    $playerValue =
        new PlayerValue();


    $playerAvailability =
        new PlayerAvailability();


    $playerIntelligenceScore =
        new PlayerIntelligenceScore();


    $playerEngine =
        new PlayerIntelligenceEngine(
            $playerPerformance,
            $playerStrength,
            $playerValue,
            $playerAvailability,
            $playerIntelligenceScore
        );


    /*
     * --------------------------------------------------------
     * BUILD PLAYER INTELLIGENCE SUMMARIES
     * --------------------------------------------------------
     */

    $playerSummaries =
        [];


    foreach ($players as $player) {

        $teamId =
            (int) (
                $player['team_id']
                ?? 0
            );


        $fixtureRating =
            $teamFixtureRatings[$teamId]
            ?? null;


        try {

            $profile =
                $playerEngine
                    ->analysePlayer(
                        $player,
                        $fixtureRating
                    );


            $summary =
                $profile['summary']
                ?? null;
                
            if (is_array($summary)) {

                $summary['goals_rating'] =
                    $profile['performance']['goals_rating']
                    ?? null;

                $summary['assists_rating'] =
                    $profile['performance']['assists_rating']
                    ?? null;

                $summary['expected_goals_rating'] =
                    $profile['performance']['expected_goals_rating']
                    ?? null;

                $summary['expected_assists_rating'] =
                    $profile['performance']['expected_assists_rating']
                    ?? null;

                $summary['clean_sheets_rating'] =
                    $profile['performance']['clean_sheets_rating']
                    ?? null;

                $summary['bps_rating'] =
                    $profile['performance']['bps_rating']
                    ?? null;
                    
                $summary['minutes'] =
                    $profile['performance']['minutes']
                    ?? 0;

                $summary['raw_bps'] =
                    $profile['performance']['bps']
                    ?? 0;

                $summary['bps_per_90'] =
                    (
                        isset($profile['performance']['minutes'])
                        &&
                        (int) $profile['performance']['minutes'] > 0
                    )
                        ? round(
                            (
                                (float) (
                                    $profile['performance']['bps']
                                    ?? 0
                                )
                                /
                                (int) $profile['performance']['minutes']
                            )
                            * 90,
                            2
                        )
                        : null;
            }


            if (
                is_array($summary)
                &&
                isset(
                    $summary[
                        'intelligence_score'
                    ]
                )
                &&
                $summary[
                    'intelligence_score'
                ] !== null
            ) {

                $playerSummaries[] =
                    $summary;
            }

        } catch (Throwable $exception) {

            /*
             * One malformed player should not prevent
             * the entire dashboard from loading.
             */
            continue;
        }
    }
    
    /*
     * --------------------------------------------------------
     * POSITIONAL STRENGTH DIAGNOSTIC
     * --------------------------------------------------------
     */

    $topStrengthByPosition = [

        'GK' => [],
        'DEF' => [],
        'MID' => [],
        'FWD' => []

    ];


    foreach ($playerSummaries as $playerSummary) {

        $position =
            $playerSummary['position']
            ?? null;


        $strengthRating =
            $playerSummary['strength_rating']
            ?? null;


        if (
            !isset(
                $topStrengthByPosition[$position]
            )
            ||
            $strengthRating === null
        ) {

            continue;
        }


        $topStrengthByPosition[$position][] =
            $playerSummary;
    }


    foreach (
        $topStrengthByPosition
        as $position => $positionPlayers
    ) {

        usort(
            $positionPlayers,
            function (
                array $a,
                array $b
            ): int {

                return
                    ($b['strength_rating'] ?? 0)
                    <=>
                    ($a['strength_rating'] ?? 0);
            }
        );


        $topStrengthByPosition[$position] =
            array_slice(
                $positionPlayers,
                0,
                5
            );
    }


    /*
     * --------------------------------------------------------
     * PLAYER RANKING
     * --------------------------------------------------------
     */

    $playerRanking =
        new PlayerRanking();


    $topPlayers =
        $playerRanking
            ->getTopPlayers(
                $playerSummaries,
                10
            );

} catch (Throwable $exception) {

    /*
     * For now the dashboard simply reports that
     * the database is unavailable.
     *
     * Detailed error logging will be handled by
     * the application logging layer later.
     */

    $databaseConnected =
        false;
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

    <meta
        name="description"
        content="FPL Intelligence - Fantasy Premier League analytics and decision support."
    >

    <title>FPL Intelligence</title>

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
                        Analytics Platform
                    </div>

                </div>

            </div>


            <nav
                class="main-navigation"
                aria-label="Main navigation"
            >

                <a
                    href="#"
                    class="nav-link active"
                    aria-current="page"
                >
                    <span class="nav-icon">
                        ◫
                    </span>

                    Dashboard
                </a>


                <a
                    href="#"
                    class="nav-link"
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

                <span
                    class="status-dot <?= $databaseConnected
                        ? 'online'
                        : 'offline'; ?>"
                ></span>

                <?= $databaseConnected
                    ? 'System Online'
                    : 'System Offline'; ?>

            </div>

        </aside>


        <!-- ==================================================
             MAIN APPLICATION
             ================================================== -->

        <div class="app-content">


            <!-- ==============================================
                 HEADER
                 ============================================== -->

            <header class="topbar">

                <div>

                    <p class="eyebrow">
                        FPL Intelligence
                    </p>

                    <h1>
                        Dashboard
                    </h1>

                </div>


                <div class="topbar-actions">

                    <span class="data-status">

                        <span
                            class="status-dot <?= $databaseConnected
                                ? 'online'
                                : 'offline'; ?>"
                        ></span>

                        <?= $databaseConnected
                            ? 'Data available'
                            : 'Data unavailable'; ?>

                    </span>

                </div>

            </header>


            <!-- ==============================================
                 MAIN DASHBOARD
                 ============================================== -->

            <main class="dashboard">


                <!-- ==========================================
                     STATUS CARDS
                     ========================================== -->

                <section
                    class="stats-grid"
                    aria-label="FPL data overview"
                >

                    <article class="stat-card">

                        <div class="stat-label">
                            Premier League Teams
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $teamCount
                            ); ?>
                        </div>

                        <div class="stat-footer">
                            Current season
                        </div>

                    </article>


                    <article class="stat-card">

                        <div class="stat-label">
                            FPL Players
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $playerCount
                            ); ?>
                        </div>

                        <div class="stat-footer">
                            Intelligence-ready
                        </div>

                    </article>


                    <article class="stat-card">

                        <div class="stat-label">
                            Fixtures
                        </div>

                        <div class="stat-value">
                            <?= number_format(
                                $fixtureCount
                            ); ?>
                        </div>

                        <div class="stat-footer">
                            Full season schedule
                        </div>

                    </article>


                    <article class="stat-card">

                        <div class="stat-label">
                            Intelligence Engine
                        </div>

                        <div class="stat-value stat-value-small">

                            <?= $databaseConnected
                                ? 'Ready'
                                : 'Offline'; ?>

                        </div>

                        <div class="stat-footer">

                            <?= $databaseConnected
                                ? 'Models available'
                                : 'Database unavailable'; ?>

                        </div>

                    </article>

                </section>


                <!-- ==========================================
                     DASHBOARD GRID
                     ========================================== -->

                <section class="dashboard-grid">


                    <!-- ======================================
                         TOP PLAYERS
                         ====================================== -->

                    <article class="dashboard-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Player Intelligence
                                </p>

                                <h2>
                                    Top Players
                                </h2>

                            </div>


                            <span class="card-badge">
                                Top 10
                            </span>

                        </div>


                        <?php if (!empty($topPlayers)): ?>

                            <div class="player-ranking">

                                <?php foreach ($topPlayers as $player): ?>

                                    <div class="player-ranking-row">

                                        <div class="player-rank">

                                            <?= (int) (
                                                $player['rank']
                                                ?? 0
                                            ); ?>

                                        </div>


                                        <div class="player-details">

                                            <div class="player-name">

                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $player['name']
                                                        ?? 'Unknown Player'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </div>


                                            <div class="player-meta">

                                                <span>

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $player['position']
                                                            ?? 'N/A'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </span>


                                                <?php if (
                                                    isset($player['price'])
                                                    &&
                                                    $player['price'] !== null
                                                ): ?>

                                                    <span>
                                                        £<?= number_format(
                                                            (float)
                                                                $player['price'],
                                                            1
                                                        ); ?>m
                                                    </span>

                                                <?php endif; ?>


                                                <?php if (
                                                    isset(
                                                        $player[
                                                            'availability_label'
                                                        ]
                                                    )
                                                ): ?>

                                                    <span>

                                                        <?= htmlspecialchars(
                                                            (string)
                                                                $player[
                                                                    'availability_label'
                                                                ],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </span>

                                                <?php endif; ?>

                                            </div>
                                            <div class="player-components">
                                                <span>
                                                    STR
                                                    <strong>
                                                        <?= number_format(
                                                            (float) (
                                                                $player['strength_rating']
                                                                ?? 0
                                                            ),
                                                            1
                                                        ); ?>
                                                    </strong>
                                                </span>

                                                <span>
                                                    VAL
                                                    <strong>
                                                        <?= number_format(
                                                            (float) (
                                                                $player['value_rating']
                                                                ?? 0
                                                            ),
                                                            1
                                                        ); ?>
                                                    </strong>
                                                </span>

                                                <span>
                                                    AVL
                                                    <strong>
                                                        <?= number_format(
                                                            (float) (
                                                                $player['availability_rating']
                                                                ?? 0
                                                            ),
                                                            1
                                                        ); ?>
                                                    </strong>
                                                </span>

                                                <span>
                                                    FIX
                                                    <strong>
                                                        <?= number_format(
                                                            (float) (
                                                                $player['fixture_rating']
                                                                ?? 0
                                                            ),
                                                            1
                                                        ); ?>
                                                    </strong>
                                                </span>

                                            </div>

                                        </div>


                                        <div class="player-score">

                                            <div class="score-value">

                                                <?= number_format(
                                                    (float) (
                                                        $player[
                                                            'intelligence_score'
                                                        ]
                                                        ?? 0
                                                    ),
                                                    1
                                                ); ?>

                                            </div>

                                            <div class="score-label">

                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $player[
                                                            'intelligence_label'
                                                        ]
                                                        ?? 'Unknown'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </div>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div class="empty-state">

                                <div class="empty-icon">
                                    ★
                                </div>

                                <h3>
                                    No player intelligence available
                                </h3>

                                <p>
                                    Player rankings will appear when
                                    sufficient FPL data is available.
                                </p>

                            </div>

                        <?php endif; ?>
<!-- testhere -->
<section class="dashboard-card strength-diagnostic">

    <div class="card-header">

        <div>

            <p class="card-kicker">
                Calibration Diagnostic
            </p>

            <h2>
                Top Strength by Position
            </h2>

        </div>

    </div>


    <div class="position-strength-grid">

        <?php foreach (
            $topStrengthByPosition
            as $position => $positionPlayers
        ): ?>

            <div class="position-strength-group">

                <h3>
                    <?= htmlspecialchars(
                        $position,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </h3>


                <?php foreach (
                    $positionPlayers
                    as $index => $player
                ): ?>

                    <div class="position-strength-row">

                        <span class="position-strength-rank">

                            <?= $index + 1; ?>

                        </span>


                        <span class="position-strength-name">

                            <?= htmlspecialchars(
                                (string) (
                                    $player['name']
                                    ?? 'Unknown'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </span>


                        <strong>

                            <?= number_format(
                                (float) (
                                    $player[
                                        'strength_rating'
                                    ]
                                    ?? 0
                                ),
                                1
                            ); ?>

                        </strong>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endforeach; ?>

    </div>

</section>



                    </article>


                    <!-- ======================================
                         FIXTURE INTELLIGENCE
                         ====================================== -->

                    <article class="dashboard-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Fixture Intelligence
                                </p>

                                <h2>
                                    Best Fixture Runs
                                </h2>

                            </div>


                            <span class="card-badge">
                                Next phase
                            </span>

                        </div>


                        <div class="empty-state">

                            <div class="empty-icon">
                                ◈
                            </div>

                            <h3>
                                Fixture opportunities
                            </h3>

                            <p>
                                Teams with the strongest upcoming
                                fixture runs will be ranked here.
                            </p>

                        </div>

                    </article>


                    <!-- ======================================
                         VALUE PICKS
                         ====================================== -->

                    <article class="dashboard-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Player Value
                                </p>

                                <h2>
                                    Value Picks
                                </h2>

                            </div>

                        </div>


                        <div class="empty-state">

                            <div class="empty-icon">
                                £
                            </div>

                            <h3>
                                Best value players
                            </h3>

                            <p>
                                Strength-per-million rankings
                                will highlight the strongest
                                budget options.
                            </p>

                        </div>

                    </article>


                    <!-- ======================================
                         TRANSFER INTELLIGENCE
                         ====================================== -->

                    <article class="dashboard-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Transfer Intelligence
                                </p>

                                <h2>
                                    Transfer Targets
                                </h2>

                            </div>

                        </div>


                        <div class="empty-state">

                            <div class="empty-icon">
                                ⇄
                            </div>

                            <h3>
                                Transfer recommendations
                            </h3>

                            <p>
                                The strongest replacements and
                                transfer opportunities will
                                appear here.
                            </p>

                        </div>

                    </article>


                </section>


                <!-- ==========================================
                     DEVELOPMENT STATUS
                     ========================================== -->

                <section class="dashboard-card system-card">

                    <div class="card-header">

                        <div>

                            <p class="card-kicker">
                                Application
                            </p>

                            <h2>
                                System Status
                            </h2>

                        </div>

                    </div>


                    <div class="system-grid">

                        <div class="system-item">

                            <span>
                                Database
                            </span>

                            <strong class="<?= $databaseConnected
                                ? 'status-success'
                                : 'status-error'; ?>">

                                <?= $databaseConnected
                                    ? 'Connected'
                                    : 'Unavailable'; ?>

                            </strong>

                        </div>


                        <div class="system-item">

                            <span>
                                Team Models
                            </span>

                            <strong class="status-success">
                                Ready
                            </strong>

                        </div>


                        <div class="system-item">

                            <span>
                                Player Models
                            </span>

                            <strong class="status-success">
                                Ready
                            </strong>

                        </div>


                        <div class="system-item">

                            <span>
                                Fixture Intelligence
                            </span>

                            <strong class="status-success">
                                Ready
                            </strong>

                        </div>

                    </div>

                </section>


            </main>


            <!-- ==============================================
                 FOOTER
                 ============================================== -->

            <footer class="footer">

                <span>
                    FPL Intelligence
                </span>

                <span>
                    Built for smarter FPL decisions.
                </span>

            </footer>

        </div>

    </div>


    <script src="assets/js/app.js"></script>

</body>

</html>