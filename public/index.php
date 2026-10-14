<?php

require_once __DIR__ . '/../classes/autoload.php';


require_once __DIR__
    . '/includes/manager-context.php';


$config =
    require __DIR__
        . '/../config/config.php';


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

$updateHealth =
    [];


$commandCentre = [

    'manager' =>
        null,

    'gameweek' =>
        null,

    'decision' =>
        null,

    'starting_xi' =>
        null,

    'captaincy' =>
        null,

    'transfers' =>
        null,

    'squad_horizon' =>
        null,

    'chips' =>
        null,

    'attention' =>
        [],

    'data_health' =>
        null
];


$commandCentreManager =
    $commandCentre[
        'manager'
    ];


$commandCentreGameweek =
    $commandCentre[
        'gameweek'
    ];


$commandCentreDecision =
    $commandCentre[
        'decision'
    ];


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
     * UPDATE HEALTH
     * --------------------------------------------------------
     */

    $updateRunRepository =
        new UpdateRunRepository(
            $db
        );


    $updateHealthService =
        new UpdateHealthService(
            $updateRunRepository
        );


    $healthNow =
        date(
            'Y-m-d H:i:s'
        );


    $healthFreshnessSeconds =
        $config[
            'data_health'
        ][
            'freshness_seconds'
        ];


    $updateHealth = [

        'bootstrap' =>
            $updateHealthService
                ->evaluate(
                    'bootstrap',
                    $healthNow,
                    $healthFreshnessSeconds
                ),

        'fixtures' =>
            $updateHealthService
                ->evaluate(
                    'fixtures',
                    $healthNow,
                    $healthFreshnessSeconds
                ),

        'player_fixture_history' =>
            $updateHealthService
                ->evaluate(
                    'player_fixture_history',
                    $healthNow,
                    $healthFreshnessSeconds
                )
    ];


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


    /*
     * --------------------------------------------------------
     * GAMEWEEK COMMAND CENTRE
     * --------------------------------------------------------
     *
     * The Dashboard composes existing authoritative production
     * services only when a manager entry is active.
     *
     * CommandCentreService owns the shared result envelope and
     * must remain free of independent intelligence logic.
     */

    if (
        $activeManagerEntryId
        !==
        null
    ) {

        $fplSquadImporter =
            new FPLSquadImporter();


        $importedSquad =
            $fplSquadImporter
                ->importSquad(
                    $activeManagerEntryId
                );


        if (
            is_array(
                $importedSquad
            )
            &&
            (
                $importedSquad[
                    'status'
                ]
                ?? null
            )
            === 'success'
            &&
            count(
                $importedSquad[
                    'players'
                ]
                ?? []
            )
            === 15
        ) {

            $playerIntelligenceService =
                new PlayerIntelligenceService(
                    $db
                );


            $resolvedSquad =
                $playerIntelligenceService
                    ->buildSquadFromFPLImport(
                        $importedSquad
                    );


            if (
                is_array(
                    $resolvedSquad
                )
                &&
                (
                    $resolvedSquad[
                        'is_complete'
                    ]
                    ?? false
                )
                === true
                &&
                count(
                    $resolvedSquad[
                        'players'
                    ]
                    ?? []
                )
                === 15
            ) {

                $squadHorizonIntelligence =
                    new SquadHorizonIntelligence();


                $squadHorizonIntelligenceService =
                    new SquadHorizonIntelligenceService(
                        $playerRepository,
                        $playerIntelligenceService,
                        $squadHorizonIntelligence
                    );


                $gameweekRepository =
                    new GameweekRepository(
                        $db
                    );


                $actionableGameweekResolver =
                    new ActionableGameweekResolver(
                        $gameweekRepository
                    );


                $targetGameweek =
                    $actionableGameweekResolver
                        ->resolve(
                            gmdate(
                                'Y-m-d H:i:s'
                            )
                        );


                $playerPool =
                    $playerIntelligenceService
                        ->getAllPlayerSummaries();


                $bank =
                    is_numeric(
                        $importedSquad[
                            'bank'
                        ]
                        ?? null
                    )
                        ? (float) $importedSquad[
                            'bank'
                        ]
                        : 0.0;


                $teamValue =
                    is_numeric(
                        $importedSquad[
                            'team_value'
                        ]
                        ?? null
                    )
                        ? (float) $importedSquad[
                            'team_value'
                        ]
                        : 0.0;


                $budget =
                    round(
                        $teamValue
                        +
                        $bank,
                        1
                    );


                $managerContext =
                    is_array(
                        $importedSquad[
                            'entry'
                        ]
                        ?? null
                    )
                        ? $importedSquad[
                            'entry'
                        ]
                        : [
                            'entry_id' =>
                                $activeManagerEntryId
                        ];


                $gameweekContext = [

                    'target_gameweek' =>
                        $targetGameweek,

                    'imported_gameweek' =>
                        $importedSquad[
                            'gameweek'
                        ]
                        ?? null
                ];


                $commandCentreService =
                    new CommandCentreService(
                        $playerIntelligenceService,
                        $squadHorizonIntelligenceService
                    );


                $commandCentre =
                    $commandCentreService->build(
                        $resolvedSquad[
                            'players'
                        ],
                        $bank,
                        $importedSquad,
                        $targetGameweek,
                        $playerPool,
                        $budget,
                        $managerContext,
                        $gameweekContext,
                        $updateHealth
                    );


                $commandCentreManager =
                    $commandCentre[
                        'manager'
                    ];


                $commandCentreGameweek =
                    $commandCentre[
                        'gameweek'
                    ];


                $commandCentreDecision =
                    $commandCentre[
                        'decision'
                    ];
            }
        }
    }

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


/*
 * ============================================================
 * COMMAND CENTRE PRESENTATION CONTEXT
 * ============================================================
 */

$commandCentreManager =
    is_array(
        $commandCentre[
            'manager'
        ]
        ?? null
    )
        ? $commandCentre[
            'manager'
        ]
        : null;


$commandCentreGameweek =
    is_array(
        $commandCentre[
            'gameweek'
        ]
        ?? null
    )
        ? $commandCentre[
            'gameweek'
        ]
        : null;


$commandCentreDecision =
    is_array(
        $commandCentre[
            'decision'
        ]
        ?? null
    )
        ? $commandCentre[
            'decision'
        ]
        : null;


$commandCentreStartingXI =
    is_array(
        $commandCentre[
            'starting_xi'
        ]
        ?? null
    )
        ? $commandCentre[
            'starting_xi'
        ]
        : null;


$commandCentreCaptaincy =
    is_array(
        $commandCentre[
            'captaincy'
        ]
        ?? null
    )
        ? $commandCentre[
            'captaincy'
        ]
        : null;


$commandCentreTransfers =
    is_array(
        $commandCentre[
            'transfers'
        ]
        ?? null
    )
        ? $commandCentre[
            'transfers'
        ]
        : null;


$commandCentreSquadHorizon =
    is_array(
        $commandCentre[
            'squad_horizon'
        ]
        ?? null
    )
        ? $commandCentre[
            'squad_horizon'
        ]
        : null;


$commandCentreHorizonResult =
    is_array(
        $commandCentreSquadHorizon[
            'horizon_result'
        ]
        ?? null
    )
        ? $commandCentreSquadHorizon[
            'horizon_result'
        ]
        : null;


$commandCentreChips =
    is_array(
        $commandCentre[
            'chips'
        ]
        ?? null
    )
        ? $commandCentre[
            'chips'
        ]
        : null;


$commandCentreAttention =
    is_array(
        $commandCentre[
            'attention'
        ]
        ?? null
    )
        ? $commandCentre[
            'attention'
        ]
        : null;


$commandCentreDataHealth =
    is_array(
        $commandCentre[
            'data_health'
        ]
        ?? null
    )
        ? $commandCentre[
            'data_health'
        ]
        : null;


$commandCentreOverallAction =
    $commandCentreDecision[
        'overall_action'
    ]
    ?? null;


$commandCentreTransferAdvice =
    $commandCentreDecision[
        'transfer_advice'
    ]
    ?? null;


$commandCentreSquadRisks =
    $commandCentreDecision[
        'squad_risks'
    ]
    ?? null;


$commandCentreKeyInsights =
    is_array(
        $commandCentreDecision[
            'key_insights'
        ]
        ?? null
    )
        ? $commandCentreDecision[
            'key_insights'
        ]
        : [];


$commandCentreCaptainAlternatives =
    is_array(
        $commandCentreCaptaincy[
            'alternatives'
        ]
        ?? null
    )
        ? $commandCentreCaptaincy[
            'alternatives'
        ]
        : [];


$commandCentreTargetGameweek =
    is_numeric(
        $commandCentreGameweek[
            'target_gameweek'
        ]
        ?? null
    )
        ? (int) $commandCentreGameweek[
            'target_gameweek'
        ]
        : null;


$gameweekIntelligenceUrl =
    'gameweek.php';


$transferIntelligenceUrl =
    'transfers.php';


$squadIntelligenceUrl =
    'squad.php';


$chipIntelligenceUrl =
    'chips.php';


$dataHealthUrl =
    'health.php';


$fixtureIntelligenceUrl =
    'fixtures.php';


$playerIntelligenceUrl =
    'players.php';


$activeNav = 'dashboard';
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

        <?php
            require __DIR__
                . '/includes/sidebar.php';
            ?>


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

                    <span class="data-status" role="status">

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
                     GAMEWEEK COMMAND CENTRE
                     ========================================== -->

                <section
                    class="dashboard-card command-centre"
                    aria-labelledby="command-centre-title"
                >

                    <div class="card-header">

                        <div>

                            <p class="card-kicker">
                                Decision Support
                            </p>

                            <h2 id="command-centre-title">
                                Gameweek Command Centre
                            </h2>

                        </div>

                    </div>


                    <?php if ($activeManagerEntryId === null): ?>

                        <div class="empty-state">

                            <div class="empty-icon">
                                ◎
                            </div>

                            <h3>
                                Connect your FPL team
                            </h3>

                            <p>
                                Enter your FPL entry ID to load your
                                manager context and Gameweek Intelligence.
                            </p>

                            <form
                                method="post"
                                class="command-centre-entry-form"
                            >

                                <label for="command-centre-entry-id">
                                    FPL entry ID
                                </label>

                                <input
                                    id="command-centre-entry-id"
                                    name="entry_id"
                                    type="number"
                                    min="1"
                                    step="1"
                                    inputmode="numeric"
                                    required
                                >

                                <button
                                    type="submit"
                                    class="button-link"
                                >
                                    Load My Team
                                </button>

                            </form>

                        </div>

                    <?php elseif (
                        $commandCentreManager === null
                        ||
                        $commandCentreDecision === null
                    ): ?>

                        <div
                            class="empty-state"
                            role="status"
                        >

                            <div class="empty-icon">
                                !
                            </div>

                            <h3>
                                Command Centre unavailable
                            </h3>

                            <p>
                                Your selected FPL entry could not be
                                loaded into the Command Centre. Check
                                that the entry is available and try
                                again.
                            </p>

                            <form
                                method="post"
                                class="command-centre-entry-form"
                            >

                                <label for="command-centre-entry-id">
                                    FPL entry ID
                                </label>

                                <input
                                    id="command-centre-entry-id"
                                    name="entry_id"
                                    type="number"
                                    min="1"
                                    step="1"
                                    inputmode="numeric"
                                    value="<?= (int) $activeManagerEntryId; ?>"
                                    required
                                >

                                <button
                                    type="submit"
                                    class="button-link"
                                >
                                    Reload Team
                                </button>

                            </form>

                        </div>

                    <?php else: ?>

                        <div class="command-centre-context-row">

                            <article class="dashboard-card command-centre-manager-context">

                                <div class="card-header">

                                    <div>

                                        <p class="card-kicker">
                                            Manager Context
                                        </p>

                                        <h3>
                                            Manager Overview
                                        </h3>

                                    </div>

                                </div>

                                <div class="stat-value stat-value-small">

                                    <?= htmlspecialchars(
                                        (string) (
                                            $commandCentreManager[
                                                'name'
                                            ]
                                            ?? $commandCentreManager[
                                                'player_name'
                                            ]
                                            ?? (
                                                'Entry '
                                                . $activeManagerEntryId
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </div>

                                <?php if (
                                    isset(
                                        $commandCentreManager[
                                            'team_name'
                                        ]
                                    )
                                ): ?>

                                    <div class="stat-footer">

                                        <?= htmlspecialchars(
                                            (string) $commandCentreManager[
                                                'team_name'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </div>

                                <?php endif; ?>

                            </article>


                            <article class="dashboard-card command-centre-gameweek-context">

                                <div class="card-header">

                                    <div>

                                        <p class="card-kicker">
                                            Current Planning Window
                                        </p>

                                        <h3>
                                            Gameweek Overview
                                        </h3>

                                    </div>

                                </div>

                                <div class="stat-value stat-value-small">

                                    <?= $commandCentreTargetGameweek !== null
                                        ? 'GW'
                                            . $commandCentreTargetGameweek
                                        : 'Unavailable'; ?>

                                </div>

                                <div class="stat-footer">
                                    Actionable gameweek
                                </div>

                            </article>


                            </div>


                            <div class="command-centre-decision-row">

                            <article class="dashboard-card command-centre-primary-decision">

                                <div class="card-header">

                                    <div>

                                        <p class="card-kicker">
                                            Gameweek Intelligence
                                        </p>

                                        <h3>
                                            Primary Gameweek Decision
                                        </h3>

                                    </div>

                                </div>

                                <?php if (
                                    is_scalar(
                                        $commandCentreOverallAction
                                    )
                                ): ?>

                                    <div class="stat-value stat-value-small">

                                        <?= htmlspecialchars(
                                            (string) $commandCentreOverallAction,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </div>

                                <?php else: ?>

                                    <div class="stat-footer">
                                        Decision evidence available
                                    </div>

                                <?php endif; ?>


                                <?php if (
                                    is_scalar(
                                        $commandCentreTransferAdvice
                                    )
                                ): ?>

                                    <p>

                                        <?= htmlspecialchars(
                                            (string) $commandCentreTransferAdvice,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </p>

                                <?php endif; ?>


                                <?php if (
                                    is_array(
                                        $commandCentreSquadRisks
                                    )
                                    &&
                                    !empty(
                                        $commandCentreSquadRisks
                                    )
                                ): ?>

                                    <p>
                                        <?= count(
                                            $commandCentreSquadRisks
                                        ); ?>
                                        squad risk<?= count(
                                            $commandCentreSquadRisks
                                        ) === 1
                                            ? ''
                                            : 's'; ?>
                                        identified.
                                    </p>

                                <?php endif; ?>


                                <a
                                    href="<?= htmlspecialchars(
                                        $gameweekIntelligenceUrl,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    class="button-link"
                                >
                                    View Gameweek Intelligence
                                </a>

                            </article>

                            <section
                                class="dashboard-card command-centre-captaincy"
                                aria-labelledby="command-centre-captaincy-title"
                            >

                                <div class="card-header">

                                    <div>

                                        <p class="card-kicker">
                                            Armband Decision
                                        </p>

                                        <h3 id="command-centre-captaincy-title">Captaincy</h3>

                                    </div>

                                </div>


                                <?php
                                    $commandCentreCaptain =
                                        is_array(
                                            $commandCentreCaptaincy[
                                                'captain'
                                            ]
                                            ?? null
                                        )
                                            ? $commandCentreCaptaincy[
                                                'captain'
                                            ]
                                            : null;

                                    $commandCentreViceCaptain =
                                        is_array(
                                            $commandCentreCaptaincy[
                                                'vice_captain'
                                            ]
                                            ?? null
                                        )
                                            ? $commandCentreCaptaincy[
                                                'vice_captain'
                                            ]
                                            : null;
                                ?>


                                <?php if (
                                    $commandCentreCaptain !== null
                                ): ?>

                                    <div class="player-ranking">

                                        <div class="player-ranking-row">

                                            <div class="player-rank">
                                                C
                                            </div>

                                            <div class="player-details">

                                                <div class="player-name">

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $commandCentreCaptain[
                                                                'name'
                                                            ]
                                                            ?? 'Unknown Player'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </div>

                                                <div class="player-meta">

                                                    <span>
                                                        Captain
                                                    </span>

                                                    <?php if (
                                                        isset(
                                                            $commandCentreCaptain[
                                                                'captain_score'
                                                            ]
                                                        )
                                                    ): ?>

                                                        <span>

                                                            Score
                                                            <?= number_format(
                                                                (float)
                                                                    $commandCentreCaptain[
                                                                        'captain_score'
                                                                    ],
                                                                2
                                                            ); ?>

                                                        </span>

                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                        </div>


                                        <?php if (
                                            $commandCentreViceCaptain !== null
                                        ): ?>

                                            <div class="player-ranking-row">

                                                <div class="player-rank">
                                                    VC
                                                </div>

                                                <div class="player-details">

                                                    <div class="player-name">

                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $commandCentreViceCaptain[
                                                                    'name'
                                                                ]
                                                                ?? 'Unknown Player'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </div>

                                                    <div class="player-meta">

                                                        <span>
                                                            Vice-captain
                                                        </span>

                                                        <?php if (
                                                            isset(
                                                                $commandCentreViceCaptain[
                                                                    'captain_score'
                                                                ]
                                                            )
                                                        ): ?>

                                                            <span>

                                                                Score
                                                                <?= number_format(
                                                                    (float)
                                                                        $commandCentreViceCaptain[
                                                                            'captain_score'
                                                                        ],
                                                                    2
                                                                ); ?>

                                                            </span>

                                                        <?php endif; ?>

                                                    </div>

                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                <?php else: ?>

                                    <div
                                        class="empty-state"
                                        role="status"
                                    >

                                        <h4>
                                            Captaincy unavailable
                                        </h4>

                                        <p>
                                            Captaincy evidence is not
                                            available for this gameweek.
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </section>

                        </div>


                        <div class="dashboard-grid">

                            <section
                                class="dashboard-card command-centre-team-selection"
                                aria-labelledby="command-centre-team-selection-title"
                            >

                                <div class="card-header">

                                    <div>

                                        <p class="card-kicker">
                                            Team Selection
                                        </p>

                                        <h3 id="command-centre-team-selection-title">
                                            Starting XI &amp; Bench
                                        </h3>

                                    </div>


                                    <?php if (
                                        is_string(
                                            $commandCentreStartingXI[
                                                'formation'
                                            ]
                                            ?? null
                                        )
                                    ): ?>

                                        <span class="card-badge">

                                            <?= htmlspecialchars(
                                                $commandCentreStartingXI[
                                                    'formation'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <?php
                                    $commandCentreStarters =
                                        is_array(
                                            $commandCentreStartingXI[
                                                'starting_xi'
                                            ]
                                            ?? null
                                        )
                                            ? $commandCentreStartingXI[
                                                'starting_xi'
                                            ]
                                            : [];

                                    $commandCentreBench =
                                        is_array(
                                            $commandCentreStartingXI[
                                                'bench'
                                            ]
                                            ?? null
                                        )
                                            ? $commandCentreStartingXI[
                                                'bench'
                                            ]
                                            : [];
                                ?>


                                <?php if (
                                    !empty(
                                        $commandCentreStarters
                                    )
                                ): ?>

                                    <h4>
                                        Starting XI
                                    </h4>

                                    <div class="player-ranking command-centre-team-layout">

                                        <?php foreach (
                                            $commandCentreStarters
                                            as $player
                                        ): ?>

                                            <div class="player-ranking-row">

                                                <div class="player-details">

                                                    <div class="player-name">

                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $player[
                                                                    'name'
                                                                ]
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
                                                                    $player[
                                                                        'position'
                                                                    ]
                                                                    ?? ''
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                        </span>

                                                    </div>

                                                </div>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>


                                    <?php if (
                                        !empty(
                                            $commandCentreBench
                                        )
                                    ): ?>

                                        <h4>
                                            Bench
                                        </h4>

                                        <div class="player-ranking command-centre-bench-row">

                                            <?php foreach (
                                                $commandCentreBench
                                                as $player
                                            ): ?>

                                                <div class="player-ranking-row">

                                                    <div class="player-details">

                                                        <div class="player-name">

                                                            <?= htmlspecialchars(
                                                                (string) (
                                                                    $player[
                                                                        'name'
                                                                    ]
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
                                                                        $player[
                                                                            'position'
                                                                        ]
                                                                        ?? ''
                                                                    ),
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ); ?>

                                                            </span>

                                                        </div>

                                                        <?php if (!empty($recommendation['priority_label'])): ?>
                                                            <div class="stat-footer">
                                                                Priority: <?= htmlspecialchars((string) $recommendation['priority_label'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if (
                                                            !empty($recommendation['replacements'][0]['player'])
                                                            && !empty($recommendation['replacements'][0]['decision_type'])
                                                        ): ?>
                                                            <div class="stat-footer">
                                                                Option classification: <?= htmlspecialchars((string) $recommendation['replacements'][0]['decision_type'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </div>
                                                        <?php endif; ?>

                                                    </div>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <div
                                        class="empty-state"
                                        role="status"
                                    >

                                        <h4>
                                            Starting XI unavailable
                                        </h4>

                                        <p>
                                            Team-selection evidence is not
                                            available for this gameweek.
                                        </p>

                                    </div>

                                <?php endif; ?>


                                <a
                                    href="<?= htmlspecialchars(
                                        $gameweekIntelligenceUrl,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    class="button-link"
                                >
                                    View Gameweek Intelligence
                                </a>

                            </section>

                            <div class="command-centre-planning-row">

                            <section
                                class="dashboard-card command-centre-transfer-card"
                                aria-labelledby="command-centre-transfer-title"
                            >

                                <div class="card-header">

                                    <div>

                                        <p class="card-kicker">
                                            Squad Changes
                                        </p>

                                        <h3 id="command-centre-transfer-title">Transfer Intelligence</h3>

                                    </div>


                                    <?php if (
                                        is_scalar(
                                            $commandCentreTransfers[
                                                'priority'
                                            ]
                                            ?? null
                                        )
                                    ): ?>

                                        <span class="card-badge">

                                            <?= htmlspecialchars(
                                                (string) $commandCentreTransfers[
                                                    'priority'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>
                                            Priority

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <?php if (
                                    $commandCentreTransfers !== null
                                ): ?>

                                    <?php if (
                                        is_scalar(
                                            $commandCentreTransferAdvice
                                        )
                                    ): ?>

                                        <h4>
                                            Transfer Advice
                                        </h4>

                                        <p>

                                            <?= htmlspecialchars(
                                                (string) $commandCentreTransferAdvice,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </p>

                                    <?php endif; ?>


                                    <?php if (
                                        is_numeric(
                                            $commandCentreTransfers[
                                                'score'
                                            ]
                                            ?? null
                                        )
                                    ): ?>

                                        <div class="stat-footer">

                                            Transfer score:
                                            <?= number_format(
                                                (float) $commandCentreTransfers[
                                                    'score'
                                                ],
                                                2
                                            ); ?>

                                        </div>

                                    <?php endif; ?>


                                    <p class="stat-footer">
                                        These are options to explore, not a recommendation to make a transfer this gameweek.
                                    </p>

                                    <?php
                                        $commandCentreTransferRecommendations =
                                            is_array(
                                                $commandCentreTransfers['recommendations']['recommendations']
                                                ?? null
                                            )
                                                ? ($commandCentreTransfers['recommendations']['recommendations'] ?? [])
                                                : [];
                                    ?>


                                    <?php if (
                                        !empty(
                                            $commandCentreTransferRecommendations
                                        )
                                    ): ?>

                                        <div class="player-ranking">

                                            <?php foreach (
                                                array_slice(
                                                    $commandCentreTransferRecommendations,
                                                    0,
                                                    3
                                                )
                                                as $recommendation
                                            ): ?>

                                                <div class="player-ranking-row">

                                                    <div class="player-details">

                                                        <div class="player-name">

                                                            <?= htmlspecialchars(
                                                                (string) (
                                                                    trim((string) ($recommendation['outgoing']['name'] ?? '')) !== ''
                                                                                ? ((string) $recommendation['outgoing']['name']
                                                                                    . ' → '
                                                                                    . (string) ($recommendation['replacements'][0]['player']['name'] ?? 'No suitable replacement'))
                                                                                : 'Transfer option'
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

                                        <div class="stat-footer">
                                            No transfer recommendation
                                        </div>

                                    <?php endif; ?>


                                    <a
                                        href="<?= htmlspecialchars(
                                            $transferIntelligenceUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        class="button-link"
                                    >
                                        Explore Transfer Intelligence
                                    </a>

                                <?php else: ?>

                                    <div
                                        class="empty-state"
                                        role="status"
                                    >

                                        <h4>
                                            Transfer Intelligence unavailable
                                        </h4>

                                        <p>
                                            Transfer evidence is not
                                            available for this gameweek.
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </section>


                            <section
                                class="dashboard-card command-centre-horizon-card"
                                aria-labelledby="command-centre-squad-horizon-title"
                            >

                                <div class="card-header">

                                    <div>

                                        <p class="card-kicker">
                                            Planning Horizon
                                        </p>

                                        <h3 id="command-centre-squad-horizon-title">Multi-Gameweek Squad Outlook</h3>

                                    </div>


                                    <?php if (
                                        is_numeric(
                                            $commandCentreHorizonResult[
                                                'horizon'
                                            ]
                                            ?? null
                                        )
                                    ): ?>

                                        <span class="card-badge">

                                            <?= (int) $commandCentreHorizonResult[
                                                'horizon'
                                            ]; ?>
                                            GW Horizon

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <?php if (
                                    $commandCentreHorizonResult !== null
                                    &&
                                    (
                                        $commandCentreHorizonResult[
                                            'status'
                                        ]
                                        ?? null
                                    )
                                    === 'Available'
                                ): ?>

                                    <?php if (
                                        is_scalar(
                                            $commandCentreHorizonResult[
                                                'projection_confidence'
                                            ]
                                            ?? null
                                        )
                                    ): ?>

                                        <div class="stat-footer">

                                            Projection Confidence:
                                            <?= htmlspecialchars(
                                                (string) $commandCentreHorizonResult[
                                                    'projection_confidence'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </div>

                                    <?php endif; ?>


                                    <?php
                                        $commandCentreHorizonGameweeks =
                                            is_array(
                                                $commandCentreHorizonResult[
                                                    'gameweeks'
                                                ]
                                                ?? null
                                            )
                                                ? $commandCentreHorizonResult[
                                                    'gameweeks'
                                                ]
                                                : [];

                                        $commandCentreStructuralWeakness =
                                            is_array(
                                                $commandCentreHorizonResult[
                                                    'structural_weakness'
                                                ]
                                                ?? null
                                            )
                                                ? $commandCentreHorizonResult[
                                                    'structural_weakness'
                                                ]
                                                : [];
                                    ?>


                                    <?php if (
                                        !empty(
                                            $commandCentreHorizonGameweeks
                                        )
                                    ): ?>

                                        <div class="player-ranking">

                                            <?php foreach (
                                                $commandCentreHorizonGameweeks
                                                as $gameweekNumber => $gameweek
                                            ): ?>

                                                <?php
                                                    $displayGameweek =
                                                        is_numeric(
                                                            $gameweek[
                                                                'gameweek'
                                                            ]
                                                            ?? null
                                                        )
                                                            ? (int) $gameweek[
                                                                'gameweek'
                                                            ]
                                                            : (
                                                                is_numeric(
                                                                    $gameweekNumber
                                                                )
                                                                    ? (int) $gameweekNumber
                                                                    : null
                                                            );

                                                    $projectedPoints =
                                                        is_numeric(
                                                            $gameweek[
                                                                'starting_xi_projected_points'
                                                            ]
                                                            ?? null
                                                        )
                                                            ? (float) $gameweek[
                                                                'starting_xi_projected_points'
                                                            ]
                                                            : null;
                                                ?>

                                                <div class="player-ranking-row">

                                                    <div class="player-details">

                                                        <div class="player-name">

                                                            <?= $displayGameweek !== null
                                                                ? 'Gameweek '
                                                                    . $displayGameweek
                                                                : 'Gameweek'; ?>

                                                        </div>

                                                        <div class="player-meta">

                                                            <?php if (
                                                                $projectedPoints !== null
                                                            ): ?>

                                                                <span>

                                                                    Starting XI:
                                                                    <?= number_format(
                                                                        $projectedPoints,
                                                                        2
                                                                    ); ?>
                                                                    projected points

                                                                </span>

                                                            <?php endif; ?>

                                                        </div>

                                                    </div>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (
                                        is_numeric(
                                            $commandCentreStructuralWeakness[
                                                'gameweeks_with_problems'
                                            ]
                                            ?? null
                                        )
                                    ): ?>

                                        <div class="stat-footer">

                                            Structural issues:
                                            <?= (int) $commandCentreStructuralWeakness[
                                                'gameweeks_with_problems'
                                            ]; ?>
                                            gameweek<?= (
                                                (int) $commandCentreStructuralWeakness[
                                                    'gameweeks_with_problems'
                                                ]
                                                === 1
                                            )
                                                ? ''
                                                : 's'; ?>

                                        </div>

                                    <?php endif; ?>


                                    <a
                                        href="<?= htmlspecialchars(
                                            $squadIntelligenceUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        class="button-link"
                                    >
                                        Explore Squad Intelligence
                                    </a>

                                <?php else: ?>

                                    <div
                                        class="empty-state"
                                        role="status"
                                    >

                                        <h4>
                                            Squad outlook unavailable
                                        </h4>

                                        <p>
                                            Multi-gameweek squad evidence is
                                            not available for this manager.
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </section>


                            <section
                                class="dashboard-card command-centre-chip-card"
                                aria-labelledby="command-centre-chip-title"
                            >

                                <div class="card-header">

                                    <div>

                                        <p class="card-kicker">
                                            Chip Planning
                                        </p>

                                        <h3 id="command-centre-chip-title">Chip Intelligence</h3>

                                    </div>

                                </div>


                                <?php
                                    $commandCentreChipRows =
                                        [];

                                    if (
                                        $commandCentreChips !== null
                                    ) {

                                        $commandCentreWildcard =
                                            is_array(
                                                $commandCentreChips[
                                                    'wildcard'
                                                ]
                                                ?? null
                                            )
                                                ? $commandCentreChips[
                                                    'wildcard'
                                                ]
                                                : null;

                                        $commandCentreFreeHit =
                                            is_array(
                                                $commandCentreChips[
                                                    'free_hit'
                                                ]
                                                ?? null
                                            )
                                                ? $commandCentreChips[
                                                    'free_hit'
                                                ]
                                                : null;

                                        $commandCentreBenchBoost =
                                            is_array(
                                                $commandCentreChips[
                                                    'bench_boost'
                                                ]
                                                ?? null
                                            )
                                                ? $commandCentreChips[
                                                    'bench_boost'
                                                ]
                                                : null;

                                        $commandCentreTripleCaptain =
                                            is_array(
                                                $commandCentreChips[
                                                    'triple_captain'
                                                ]
                                                ?? null
                                            )
                                                ? $commandCentreChips[
                                                    'triple_captain'
                                                ]
                                                : null;


                                        $commandCentreWildcardTiming =
                                            is_array(
                                                $commandCentreWildcard[
                                                    'timing_result'
                                                ]
                                                ?? null
                                            )
                                                ? $commandCentreWildcard[
                                                    'timing_result'
                                                ]
                                                : null;


                                        $commandCentreChipSources = [

                                            'Wildcard' =>
                                                is_array(
                                                    $commandCentreWildcardTiming
                                                )
                                                    ? (
                                                        $commandCentreWildcardTiming[
                                                            'decision'
                                                        ]
                                                        ?? null
                                                    )
                                                    : null,

                                            'Free Hit' =>
                                                is_array(
                                                    $commandCentreFreeHit
                                                )
                                                    ? (
                                                        $commandCentreFreeHit[
                                                            'decision'
                                                        ]
                                                        ?? null
                                                    )
                                                    : null,

                                            'Bench Boost' =>
                                                is_array(
                                                    $commandCentreBenchBoost
                                                )
                                                    ? (
                                                        $commandCentreBenchBoost[
                                                            'decision'
                                                        ]
                                                        ?? null
                                                    )
                                                    : null,

                                            'Triple Captain' =>
                                                is_array(
                                                    $commandCentreTripleCaptain
                                                )
                                                    ? (
                                                        $commandCentreTripleCaptain[
                                                            'decision'
                                                        ]
                                                        ?? null
                                                    )
                                                    : null
                                        ];


                                        foreach (
                                            $commandCentreChipSources
                                            as $chipLabel => $chipDecision
                                        ) {

                                            if (
                                                !is_object(
                                                    $chipDecision
                                                )
                                                ||
                                                !method_exists(
                                                    $chipDecision,
                                                    'getRecommendation'
                                                )
                                                ||
                                                !method_exists(
                                                    $chipDecision,
                                                    'getConfidence'
                                                )
                                                ||
                                                !method_exists(
                                                    $chipDecision,
                                                    'getExplanation'
                                                )
                                            ) {

                                                continue;
                                            }


                                            $commandCentreChipRows[] = [

                                                'chip' =>
                                                    $chipLabel,

                                                'recommendation' =>
                                                    $chipDecision
                                                        ->getRecommendation(),

                                                'confidence' =>
                                                    $chipDecision
                                                        ->getConfidence(),

                                                'explanation' =>
                                                    $chipDecision
                                                        ->getExplanation()
                                            ];
                                        }
                                    }
                                ?>


                                <?php if (
                                    !empty(
                                        $commandCentreChipRows
                                    )
                                ): ?>

                                    <div class="player-ranking">

                                        <?php foreach (
                                            $commandCentreChipRows
                                            as $chipRow
                                        ): ?>

                                            <div class="player-ranking-row">

                                                <div class="player-details">

                                                    <div class="player-name">

                                                        <?= htmlspecialchars(
                                                            (string) $chipRow[
                                                                'chip'
                                                            ],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </div>

                                                    <div class="player-meta">

                                                        <span>

                                                            <?= htmlspecialchars(
                                                                (string) $chipRow[
                                                                    'recommendation'
                                                                ],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                        </span>


                                                        <?php if (
                                                            is_numeric(
                                                                $chipRow[
                                                                    'confidence'
                                                                ]
                                                                ?? null
                                                            )
                                                        ): ?>

                                                            <span>

                                                                Confidence:
                                                                <?= number_format(
                                                                    (
                                                                        (float) $chipRow[
                                                                            'confidence'
                                                                        ]
                                                                    )
                                                                    * 100,
                                                                    0
                                                                ); ?>%

                                                            </span>

                                                        <?php endif; ?>

                                                    </div>


                                                    <div class="stat-footer">

                                                        <?= htmlspecialchars(
                                                            (string) $chipRow[
                                                                'explanation'
                                                            ],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </div>

                                                </div>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>


                                    <a
                                        href="<?= htmlspecialchars(
                                            $chipIntelligenceUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        class="button-link"
                                    >
                                        Explore Chip Intelligence
                                    </a>

                                <?php else: ?>

                                    <div
                                        class="empty-state"
                                        role="status"
                                    >

                                        <h4>
                                            Chip Intelligence unavailable
                                        </h4>

                                        <p>
                                            No authoritative chip decision is
                                            available for this gameweek.
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </section>

                            </div>
                            <div class="command-centre-attention-row">

                            <section
                                class="dashboard-card"
                                aria-labelledby="command-centre-attention-title"
                            >

                                <div class="card-header">

                                    <div>

                                        <p class="card-kicker">
                                            Decision Watch
                                        </p>

                                        <h3 id="command-centre-attention-title">Attention Required</h3>

                                    </div>

                                </div>


                                <?php if (
                                    $commandCentreAttention !== null
                                ): ?>

                                    <?php
                                        $attentionSquadRisks =
                                            is_array(
                                                $commandCentreAttention[
                                                    'squad_risks'
                                                ]
                                                ?? null
                                            )
                                                ? $commandCentreAttention[
                                                    'squad_risks'
                                                ]
                                                : [];

                                        $attentionSquadRisks =
                                            is_array(
                                                $attentionSquadRisks['risks'] ?? null
                                            )
                                                ? $attentionSquadRisks['risks']
                                                : [];

                                        $attentionTransferAdvice =
                                            $commandCentreAttention[
                                                'transfer_advice'
                                            ]
                                            ?? null;

                                        $attentionTransferAction =
                                            is_array($attentionTransferAdvice)
                                                ? ($attentionTransferAdvice['action'] ?? null)
                                                : null;

                                        $attentionTransferText =
                                            is_array($attentionTransferAdvice)
                                                ? ($attentionTransferAdvice['message'] ?? null)
                                                : $attentionTransferAdvice;

                                        $hasAttentionTransferAdvice =
                                            (
                                                is_scalar($attentionTransferText)
                                                && trim((string) $attentionTransferText) !== ''
                                            )
                                            ||
                                            (
                                                is_scalar($attentionTransferAction)
                                                && trim((string) $attentionTransferAction) !== ''
                                            );

                                        $attentionChips =
                                            is_array(
                                                $commandCentreAttention[
                                                    'chips'
                                                ]
                                                ?? null
                                            )
                                                ? $commandCentreAttention[
                                                    'chips'
                                                ]
                                                : [];

                                        $attentionChipWatch =
                                            [];


                                        $attentionWildcardTiming =
                                            is_array(
                                                $attentionChips[
                                                    'wildcard'
                                                ][
                                                    'timing_result'
                                                ]
                                                ?? null
                                            )
                                                ? $attentionChips[
                                                    'wildcard'
                                                ][
                                                    'timing_result'
                                                ]
                                                : null;


                                        $attentionChipDecisions = [

                                            'Wildcard' =>
                                                is_array(
                                                    $attentionWildcardTiming
                                                )
                                                    ? (
                                                        $attentionWildcardTiming[
                                                            'decision'
                                                        ]
                                                        ?? null
                                                    )
                                                    : null,

                                            'Free Hit' =>
                                                $attentionChips[
                                                    'free_hit'
                                                ][
                                                    'decision'
                                                ]
                                                ?? null,

                                            'Bench Boost' =>
                                                $attentionChips[
                                                    'bench_boost'
                                                ][
                                                    'decision'
                                                ]
                                                ?? null,

                                            'Triple Captain' =>
                                                $attentionChips[
                                                    'triple_captain'
                                                ][
                                                    'decision'
                                                ]
                                                ?? null
                                        ];


                                        foreach (
                                            $attentionChipDecisions
                                            as $attentionChipLabel => $attentionChipDecision
                                        ) {

                                            if (
                                                !is_object(
                                                    $attentionChipDecision
                                                )
                                                ||
                                                !method_exists(
                                                    $attentionChipDecision,
                                                    'getRecommendation'
                                                )
                                            ) {

                                                continue;
                                            }


                                            $attentionChipRecommendation =
                                                $attentionChipDecision
                                                    ->getRecommendation();


                                            if (
                                                $attentionChipRecommendation
                                                === 'Use'
                                                ||
                                                $attentionChipRecommendation
                                                === 'Consider'
                                            ) {

                                                $attentionChipWatch[] = [

                                                    'chip' =>
                                                        $attentionChipLabel,

                                                    'recommendation' =>
                                                        $attentionChipRecommendation
                                                ];
                                            }
                                        }


                                        $hasAttentionItems =
                                            !empty(
                                                $attentionSquadRisks
                                            )
                                            ||
                                            $hasAttentionTransferAdvice
                                            ||
                                            !empty(
                                                $attentionChipWatch
                                            );
                                    ?>


                                    <div
                                        id="command-centre-attention-status"
                                        role="status"
                                    >

                                        <?php if (
                                            $hasAttentionItems
                                        ): ?>

                                            <?php if (
                                                !empty(
                                                    $attentionSquadRisks
                                                )
                                            ): ?>

                                                <div class="stat-footer">

                                                    <strong>
                                                        Squad Risks
                                                    </strong>

                                                </div>


                                                <div class="player-ranking">

                                                    <?php foreach (
                                                        $attentionSquadRisks
                                                        as $attentionRisk
                                                    ): ?>

                                                        <?php if (
                                                            is_scalar(
                                                                $attentionRisk
                                                            )
                                                        ): ?>

                                                            <div class="player-ranking-row">

                                                                <div class="player-details">

                                                                    <div class="player-meta">

                                                                        <span>

                                                                            <?= htmlspecialchars(
                                                                                (string) $attentionRisk,
                                                                                ENT_QUOTES,
                                                                                'UTF-8'
                                                                            ); ?>

                                                                        </span>

                                                                    </div>

                                                                </div>

                                                            </div>

                                                        <?php elseif (
                                                            is_array(
                                                                $attentionRisk
                                                            )
                                                        ): ?>

                                                            <?php
                                                                $attentionRiskText =
                                                                    $attentionRisk[
                                                                        'message'
                                                                    ]
                                                                    ??
                                                                    $attentionRisk[
                                                                        'reason'
                                                                    ]
                                                                    ??
                                                                    $attentionRisk[
                                                                        'name'
                                                                    ]
                                                                    ??
                                                                    null;
                                                            ?>

                                                            <?php if (
                                                                is_scalar(
                                                                    $attentionRiskText
                                                                )
                                                            ): ?>

                                                                <div class="player-ranking-row">

                                                                    <div class="player-details">

                                                                        <div class="player-meta">

                                                                            <span>

                                                                                <?= htmlspecialchars(
                                                                                    (string) $attentionRiskText,
                                                                                    ENT_QUOTES,
                                                                                    'UTF-8'
                                                                                ); ?>

                                                                            </span>

                                                                        </div>

                                                                    </div>

                                                                </div>

                                                            <?php endif; ?>

                                                        <?php endif; ?>

                                                    <?php endforeach; ?>

                                                </div>

                                            <?php endif; ?>


                                            <?php if (
                                                $hasAttentionTransferAdvice
                                            ): ?>

                                                <div class="stat-footer">

                                                    <strong>
                                                        Transfer Advice
                                                    </strong>

                                                    <br>

                                                    <?php if (
                                                        is_scalar($attentionTransferAction)
                                                        && trim((string) $attentionTransferAction) !== ''
                                                    ): ?>

                                                        <span class="player-name">
                                                            <?= htmlspecialchars(
                                                                (string) $attentionTransferAction,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>
                                                        </span>

                                                    <?php endif; ?>

                                                    <?php if (
                                                        is_scalar($attentionTransferText)
                                                        && trim((string) $attentionTransferText) !== ''
                                                    ): ?>

                                                        <span class="player-meta">
                                                            <?= htmlspecialchars(
                                                                (string) $attentionTransferText,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>
                                                        </span>

                                                    <?php endif; ?>

                                                </div>

                                            <?php endif; ?>


                                            <?php if (
                                                !empty(
                                                    $attentionChipWatch
                                                )
                                            ): ?>

                                                <div class="stat-footer">

                                                    <strong>
                                                        Chip Watch
                                                    </strong>

                                                </div>


                                                <div class="player-ranking">

                                                    <?php foreach (
                                                        $attentionChipWatch
                                                        as $attentionChip
                                                    ): ?>

                                                        <div class="player-ranking-row">

                                                            <div class="player-details">

                                                                <div class="player-name">

                                                                    <?= htmlspecialchars(
                                                                        (string) $attentionChip[
                                                                            'chip'
                                                                        ],
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ); ?>

                                                                </div>

                                                                <div class="player-meta">

                                                                    <span>

                                                                        <?= htmlspecialchars(
                                                                            (string) $attentionChip[
                                                                                'recommendation'
                                                                            ],
                                                                            ENT_QUOTES,
                                                                            'UTF-8'
                                                                        ); ?>

                                                                    </span>

                                                                </div>

                                                            </div>

                                                        </div>

                                                    <?php endforeach; ?>

                                                </div>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            <div class="empty-state">

                                                <h4>
                                                    No immediate attention items
                                                </h4>

                                                <p>
                                                    The current authoritative
                                                    decision evidence does not
                                                    surface an immediate squad,
                                                    transfer or chip item.
                                                </p>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <a
                                        href="<?= htmlspecialchars(
                                            $gameweekIntelligenceUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        class="button-link"
                                    >
                                        Review Gameweek Intelligence
                                    </a>

                                <?php else: ?>

                                    <div
                                        id="command-centre-attention-status"
                                        class="empty-state"
                                        role="status"
                                    >

                                        <h4>
                                            Attention evidence unavailable
                                        </h4>

                                        <p>
                                            No authoritative attention evidence
                                            is available for this manager.
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </section>


                            <section
                                class="dashboard-card"
                                aria-labelledby="command-centre-opportunities-title"
                            >

                                <div class="card-header">

                                    <div>

                                        <p class="card-kicker">
                                            Positive Decision Evidence
                                        </p>

                                        <h3 id="command-centre-opportunities-title">Opportunities</h3>

                                    </div>

                                </div>


                                <?php if (
                                    $commandCentreDecision !== null
                                ): ?>

                                    <?php
                                        $opportunityTransferOptions =
                                            is_array(
                                                $commandCentreTransfers['recommendations']['recommendations']
                                                ?? null
                                            )
                                                ? ($commandCentreTransfers['recommendations']['recommendations'] ?? [])
                                                : [];

                                        $opportunityChipRows =
                                            [];

                                        foreach (
                                            $commandCentreChipRows
                                            as $opportunityChipRow
                                        ) {

                                            $opportunityChipRecommendation =
                                                $opportunityChipRow[
                                                    'recommendation'
                                                ]
                                                ?? null;


                                            if (
                                                $opportunityChipRecommendation
                                                === 'Use'
                                                ||
                                                $opportunityChipRecommendation
                                                === 'Consider'
                                            ) {

                                                $opportunityChipRows[] =
                                                    $opportunityChipRow;
                                            }
                                        }


                                        $hasOpportunityEvidence =
                                            !empty(
                                                $commandCentreKeyInsights
                                            )
                                            ||
                                            !empty(
                                                $commandCentreCaptainAlternatives
                                            )
                                            ||
                                            !empty(
                                                $opportunityTransferOptions
                                            )
                                            ||
                                            !empty(
                                                $opportunityChipRows
                                            );
                                    ?>


                                    <div
                                        id="command-centre-opportunities-status"
                                        role="status"
                                    >

                                        <?php if (
                                            $hasOpportunityEvidence
                                        ): ?>

                                            <?php if (
                                                !empty(
                                                    $commandCentreKeyInsights
                                                )
                                            ): ?>

                                                <div class="stat-footer">

                                                    <strong>
                                                        Key Insights
                                                    </strong>

                                                </div>


                                                <div class="player-ranking">

                                                    <?php foreach (
                                                        array_slice(
                                                            $commandCentreKeyInsights,
                                                            0,
                                                            5
                                                        )
                                                        as $opportunityInsight
                                                    ): ?>

                                                        <?php if (
                                                            is_scalar(
                                                                $opportunityInsight
                                                            )
                                                        ): ?>

                                                            <div class="player-ranking-row">

                                                                <div class="player-details">

                                                                    <div class="player-meta">

                                                                        <span>

                                                                            <?= htmlspecialchars(
                                                                                (string) $opportunityInsight,
                                                                                ENT_QUOTES,
                                                                                'UTF-8'
                                                                            ); ?>

                                                                        </span>

                                                                    </div>

                                                                </div>

                                                            </div>

                                                        <?php endif; ?>

                                                    <?php endforeach; ?>

                                                </div>

                                            <?php endif; ?>


                                            <?php if (
                                                !empty(
                                                    $commandCentreCaptainAlternatives
                                                )
                                            ): ?>

                                                <div class="stat-footer">

                                                    <strong>
                                                        Captaincy Alternatives
                                                    </strong>

                                                </div>


                                                <div class="player-ranking">

                                                    <?php foreach (
                                                        array_slice(
                                                            $commandCentreCaptainAlternatives,
                                                            0,
                                                            3
                                                        )
                                                        as $captainAlternative
                                                    ): ?>

                                                        <?php if (
                                                            is_array(
                                                                $captainAlternative
                                                            )
                                                        ): ?>

                                                            <div class="player-ranking-row">

                                                                <div class="player-details">

                                                                    <div class="player-name">

                                                                        <?= htmlspecialchars(
                                                                            (string) (
                                                                                $captainAlternative[
                                                                                    'name'
                                                                                ]
                                                                                ?? 'Captaincy option'
                                                                            ),
                                                                            ENT_QUOTES,
                                                                            'UTF-8'
                                                                        ); ?>

                                                                    </div>


                                                                    <?php if (
                                                                        is_numeric(
                                                                            $captainAlternative[
                                                                                'captain_score'
                                                                            ]
                                                                            ?? null
                                                                        )
                                                                    ): ?>

                                                                        <div class="player-meta">

                                                                            <span>

                                                                                Captain Score
                                                                                <?= number_format(
                                                                                    (float) $captainAlternative[
                                                                                        'captain_score'
                                                                                    ],
                                                                                    2
                                                                                ); ?>

                                                                            </span>

                                                                        </div>

                                                                    <?php endif; ?>

                                                                </div>

                                                            </div>

                                                        <?php endif; ?>

                                                    <?php endforeach; ?>

                                                </div>

                                            <?php endif; ?>


                                            <?php if (
                                                !empty(
                                                    $opportunityTransferOptions
                                                )
                                            ): ?>

                                                <div class="stat-footer">

                                                    <strong>
                                                        Transfer Options
                                                    </strong>

                                                </div>


                                                <div class="player-ranking">

                                                    <?php foreach (
                                                        array_slice(
                                                            $opportunityTransferOptions,
                                                            0,
                                                            3
                                                        )
                                                        as $opportunityTransfer
                                                    ): ?>

                                                        <?php if (
                                                            is_array(
                                                                $opportunityTransfer
                                                            )
                                                        ): ?>

                                                            <div class="player-ranking-row">

                                                                <div class="player-details">

                                                                    <div class="player-name">

                                                                        <?= htmlspecialchars(
                                                                            (string) (
                                                                                trim((string) ($opportunityTransfer['outgoing']['name'] ?? '')) !== ''
                                                                                ? ((string) $opportunityTransfer['outgoing']['name']
                                                                                    . ' → '
                                                                                    . (string) ($opportunityTransfer['replacements'][0]['player']['name'] ?? 'No suitable replacement'))
                                                                                : 'Transfer option'
                                                                            ),
                                                                            ENT_QUOTES,
                                                                            'UTF-8'
                                                                        ); ?>

                                                                    </div>

                                                                </div>

                                                            </div>

                                                        <?php endif; ?>

                                                    <?php endforeach; ?>

                                                </div>

                                            <?php endif; ?>


                                            <?php if (
                                                !empty(
                                                    $opportunityChipRows
                                                )
                                            ): ?>

                                                <div class="stat-footer">

                                                    <strong>
                                                        Chip Opportunities
                                                    </strong>

                                                </div>


                                                <div class="player-ranking">

                                                    <?php foreach (
                                                        $opportunityChipRows
                                                        as $opportunityChip
                                                    ): ?>

                                                        <div class="player-ranking-row">

                                                            <div class="player-details">

                                                                <div class="player-name">

                                                                    <?= htmlspecialchars(
                                                                        (string) (
                                                                            $opportunityChip[
                                                                                'chip'
                                                                            ]
                                                                            ?? 'Chip'
                                                                        ),
                                                                        ENT_QUOTES,
                                                                        'UTF-8'
                                                                    ); ?>

                                                                </div>

                                                                <div class="player-meta">

                                                                    <span>

                                                                        <?= htmlspecialchars(
                                                                            (string) (
                                                                                $opportunityChip[
                                                                                    'recommendation'
                                                                                ]
                                                                                ?? ''
                                                                            ),
                                                                            ENT_QUOTES,
                                                                            'UTF-8'
                                                                        ); ?>

                                                                    </span>

                                                                </div>

                                                            </div>

                                                        </div>

                                                    <?php endforeach; ?>

                                                </div>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            <div class="empty-state">

                                                <h4>
                                                    No immediate opportunities surfaced
                                                </h4>

                                                <p>
                                                    The current authoritative
                                                    evidence does not surface
                                                    an additional gameweek
                                                    opportunity.
                                                </p>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <a
                                        href="<?= htmlspecialchars(
                                            $gameweekIntelligenceUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        class="button-link"
                                    >
                                        Explore Gameweek Opportunities
                                    </a>

                                <?php else: ?>

                                    <div
                                        id="command-centre-opportunities-status"
                                        class="empty-state"
                                        role="status"
                                    >

                                        <h4>
                                            Opportunity evidence unavailable
                                        </h4>

                                        <p>
                                            No authoritative opportunity
                                            evidence is available for this
                                            manager.
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </section>

                        </div>

                            <section
                                class="dashboard-card command-centre-data-health-card"
                                aria-labelledby="command-centre-data-health-title"
                            >

                                <div class="card-header">

                                    <div>

                                        <p class="card-kicker">
                                            Evidence Quality
                                        </p>

                                        <h3 id="command-centre-data-health-title">Data Health &amp; Confidence</h3>

                                    </div>

                                </div>


                                <?php if (
                                    $commandCentreDataHealth !== null
                                ): ?>

                                    <?php
                                        $commandCentreHealthLabels = [

                                            'bootstrap' =>
                                                'Bootstrap',

                                            'fixtures' =>
                                                'Fixtures',

                                            'player_fixture_history' =>
                                                'Player Fixture History'
                                        ];


                                        $commandCentreProjectionConfidence =
                                            $commandCentreHorizonResult[
                                                'projection_confidence'
                                            ]
                                            ?? null;
                                    ?>


                                    <div
                                        id="command-centre-data-health-status"
                                        role="status"
                                    >

                                        <div class="player-ranking">

                                            <?php foreach (
                                                $commandCentreHealthLabels
                                                as $healthType => $healthLabel
                                            ): ?>

                                                <?php
                                                    $healthEvidence =
                                                        is_array(
                                                            $commandCentreDataHealth[
                                                                $healthType
                                                            ]
                                                            ?? null
                                                        )
                                                            ? $commandCentreDataHealth[
                                                                $healthType
                                                            ]
                                                            : [];

                                                    $healthStatus =
                                                        is_scalar(
                                                            $healthEvidence[
                                                                'status'
                                                            ]
                                                            ?? null
                                                        )
                                                            ? (string) $healthEvidence[
                                                                'status'
                                                            ]
                                                            : 'Unavailable';

                                                    $healthReason =
                                                        is_scalar(
                                                            $healthEvidence[
                                                                'reason'
                                                            ]
                                                            ?? null
                                                        )
                                                            ? (string) $healthEvidence[
                                                                'reason'
                                                            ]
                                                            : 'No health information is available.';
                                                ?>

                                                <div class="player-ranking-row">

                                                    <div class="player-details">

                                                        <div class="player-name">

                                                            <?= htmlspecialchars(
                                                                $healthLabel,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                        </div>

                                                        <div class="player-meta">

                                                            <span>

                                                                <?= htmlspecialchars(
                                                                    $healthStatus,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ); ?>

                                                            </span>

                                                        </div>

                                                        <div class="stat-footer">

                                                            <?= htmlspecialchars(
                                                                $healthReason,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                        </div>

                                                    </div>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>


                                        <div class="stat-footer">

                                            <strong>
                                                Projection Confidence
                                            </strong>

                                            <br>

                                            <?php if (
                                                is_scalar(
                                                    $commandCentreProjectionConfidence
                                                )
                                            ): ?>

                                                <?= htmlspecialchars(
                                                    (string) $commandCentreProjectionConfidence,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            <?php else: ?>

                                                Projection Confidence unavailable

                                            <?php endif; ?>

                                        </div>

                                    </div>


                                    <a
                                        href="<?= htmlspecialchars(
                                            $dataHealthUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        class="button-link"
                                    >
                                        View Full Data Health
                                    </a>

                                <?php else: ?>

                                    <div
                                        id="command-centre-data-health-status"
                                        class="empty-state"
                                        role="status"
                                    >

                                        <h4>
                                            Data health evidence unavailable
                                        </h4>

                                        <p>
                                            Update-health evidence is not
                                            available for this manager context.
                                        </p>

                                        <p>
                                            Projection Confidence unavailable
                                        </p>

                                    </div>

                                <?php endif; ?>

                            </section>

                        </div>

                    <?php endif; ?>

                </section>

                <div class="dashboard-secondary-content">

                    <header class="dashboard-secondary-header">
                        <h2>Additional Intelligence</h2>
                    </header>

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
                     DATA HEALTH
                     ========================================== -->

                <?php
                    require __DIR__
                        . '/includes/update-health.php';
                ?>


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
                                    Upcoming Fixtures
                                </h2>

                            </div>

                        </div>


                        <div class="empty-state">

                            <div class="empty-icon">
                                ◈
                            </div>

                            <h3>
                                Explore the fixture schedule
                            </h3>

                            <p>
                                Review upcoming gameweeks,
                                opponents and official FPL
                                fixture difficulty ratings.
                            </p>

                            <a
                                href="<?= htmlspecialchars(
                                    $fixtureIntelligenceUrl,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                class="button-link"
                            >
                                View Fixtures
                            </a>

                        </div>

                    </article>


                    <!-- ======================================
                         VALUE PICKS
                         ====================================== -->

                    <article class="dashboard-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Player Intelligence
                                </p>

                                <h2>
                                    Player Analysis
                                </h2>

                            </div>

                        </div>


                        <div class="empty-state">

                            <div class="empty-icon">
                                £
                            </div>

                            <h3>
                                Explore player intelligence
                            </h3>

                            <p>
                                Compare player strength, value,
                                availability and fixture
                                intelligence.
                            </p>

                            <a
                                href="<?= htmlspecialchars(
                                    $playerIntelligenceUrl,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                class="button-link"
                            >
                                View Players
                            </a>

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
                                    Transfer Decisions
                                </h2>

                            </div>

                        </div>


                        <div class="empty-state">

                            <div class="empty-icon">
                                ⇄
                            </div>

                            <h3>
                                Explore transfer options
                            </h3>

                            <p>
                                Review transfer intelligence and
                                investigate potential squad
                                changes.
                            </p>

                            <a
                                href="<?= htmlspecialchars(
                                    $transferIntelligenceUrl,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                class="button-link"
                            >
                                View Transfers
                            </a>

                        </div>

                    </article>


                </section>

            </div>

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