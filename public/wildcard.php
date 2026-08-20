<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * ACTIVE NAVIGATION
 * ============================================================
 */

$activeNav =
    'wildcard';


/*
 * ============================================================
 * PAGE STATE
 * ============================================================
 */

$setupError =
    null;


$wildcardResult =
    null;
    
   
$playerPool =
    [];
    
    
$generateWildcard =
    isset(
        $_GET[
            'generate'
        ]
    )
    &&
    $_GET[
        'generate'
    ]
    === '1';    


try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $playerIntelligenceService =
        new PlayerIntelligenceService(
            $db
        );


    $wildcardOptimizer =
        new WildcardOptimizer();

} catch (
    Throwable $exception
) {

    $setupError =
        $exception
            ->getMessage();
}


/*
 * ============================================================
 * BUILD WILDCARD PLAYER POOL
 * ============================================================
 */

if (
    $setupError === null
    &&
    $generateWildcard
) {

    try {

        $allPlayerSummaries =
            $playerIntelligenceService
                ->getAllPlayerSummaries();


        foreach (
            $allPlayerSummaries
            as $summary
        ) {

            $playerId =
                (int) (
                    $summary[
                        'player_id'
                    ]
                    ?? 0
                );


            $teamId =
                (int) (
                    $summary[
                        'team_id'
                    ]
                    ?? 0
                );


            $position =
                strtoupper(
                    trim(
                        (string) (
                            $summary[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                );


            $price =
                $summary[
                    'price'
                ]
                ?? null;


            $intelligence =
                $summary[
                    'intelligence_score'
                ]
                ?? null;


            if (
                $playerId <= 0
                ||
                $teamId <= 0
                ||
                !in_array(
                    $position,
                    [
                        'GK',
                        'DEF',
                        'MID',
                        'FWD'
                    ],
                    true
                )
                ||
                !is_numeric(
                    $price
                )
                ||
                (float) $price <= 0
                ||
                !is_numeric(
                    $intelligence
                )
            ) {

                continue;
            }


            $playerPool[] =
                $summary;
        }

    } catch (
        Throwable $exception
    ) {

        $setupError =
            $exception
                ->getMessage();
    }
}

/*
 * ============================================================
 * RUN WILDCARD OPTIMIZER
 * ============================================================
 */

if (
    $setupError === null
    &&
    $generateWildcard
    &&
    !empty(
        $playerPool
    )
) {

    try {

        $wildcardResult =
            $wildcardOptimizer
                ->optimize(
                    $playerPool,
                    100.0
                );

    } catch (
        Throwable $exception
    ) {

        $setupError =
            $exception
                ->getMessage();
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

    <meta
        name="description"
        content="Generate and analyse an optimised FPL wildcard squad using FPL Intelligence."
    >

    <title>
        Wildcard Intelligence | FPL Intelligence
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
                    Full Squad Optimisation
                </p>

                <h1>
                    Wildcard Intelligence
                </h1>

                <p class="topbar-subtitle">
                    Build an optimised 15-player FPL squad using
                    Player Intelligence, squad structure, reliability
                    and budget efficiency.
                </p>

            </div>

        </header>


        <main class="dashboard wildcard-dashboard">


            <!-- ==============================================
                 INTRODUCTION
                 ============================================== -->

            <section class="dashboard-section">

                <div class="section-heading">

                    <p class="eyebrow">
                        Wildcard Optimizer
                    </p>

                    <h2>
                        Build Your Best £100m Squad
                    </h2>

                </div>


                <p class="wildcard-intro">
                    Wildcard Intelligence analyses the complete Player Intelligence
                    pool and builds a legal 15-player FPL squad while balancing
                    starting quality, value, goalkeeper reliability and bench depth.
                </p>

            </section>
            
            <?php if ($setupError !== null): ?>

                <section class="dashboard-section">

                    <div class="profile-panel">

                        <p>
                            <?= htmlspecialchars(
                                $setupError,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </p>

                    </div>

                </section>

            <?php endif; ?>


            <!-- ==============================================
                 DEVELOPMENT PLACEHOLDER / Recommended Wildcard Squad
                 ============================================== -->

            <section class="dashboard-section">

                <div class="section-heading">

                    <p class="eyebrow">
                        Optimisation
                    </p>

                    <h2>
                        Recommended Wildcard Squad
                    </h2>

                </div>


                <?php

                $wildcardStatus =
                    $wildcardResult[
                        'status'
                    ]
                    ?? null;

                ?>


                <?php if ($wildcardStatus === 'success'): ?>
                
                    <div class="wildcard-result-actions">

                        <a
                            class="wildcard-regenerate-button"
                            href="wildcard.php?generate=1"
                        >
                            Regenerate Squad
                        </a>

                    </div>

                    <div class="wildcard-summary-grid">


                        <div class="wildcard-summary-card">

                            <p class="eyebrow">
                                Squad Cost
                            </p>

                            <h3>
                                £<?= number_format(
                                    (float) (
                                        $wildcardResult[
                                            'cost'
                                        ]
                                        ?? 0
                                    ),
                                    1
                                ); ?>m
                            </h3>

                        </div>


                        <div class="wildcard-summary-card">

                            <p class="eyebrow">
                                Bank
                            </p>

                            <h3>
                                £<?= number_format(
                                    (float) (
                                        $wildcardResult[
                                            'bank'
                                        ]
                                        ?? 0
                                    ),
                                    1
                                ); ?>m
                            </h3>

                        </div>


                        <div class="wildcard-summary-card">

                            <p class="eyebrow">
                                Formation
                            </p>

                            <h3>
                                <?= htmlspecialchars(
                                    (string) (
                                        $wildcardResult[
                                            'formation'
                                        ]
                                        ?? 'N/A'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </h3>

                        </div>


                        <div class="wildcard-summary-card">

                            <p class="eyebrow">
                                Starting XI Score
                            </p>

                            <h3>
                                <?= number_format(
                                    (float) (
                                        $wildcardResult[
                                            'starting_xi_score'
                                        ]
                                        ?? 0
                                    ),
                                    2
                                ); ?>
                            </h3>

                        </div>


                        <div class="wildcard-summary-card">

                            <p class="eyebrow">
                                Structure Score
                            </p>

                            <h3>
                                <?= number_format(
                                    (float) (
                                        $wildcardResult[
                                            'structure_score'
                                        ]
                                        ?? 0
                                    ),
                                    2
                                ); ?>
                            </h3>

                        </div>


                    </div>

                <?php elseif ($wildcardStatus !== null): ?>

                    <div class="profile-panel">

                        <p>
                            <?= htmlspecialchars(
                                (string) (
                                    $wildcardResult[
                                        'message'
                                    ]
                                    ?? 'Wildcard optimisation could not be completed.'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </p>

                    </div>

                <?php else: ?>

                    <div class="wildcard-generate-panel">

                        <div>

                            <p class="eyebrow">
                                Ready to Optimise
                            </p>

                            <h3>
                                Generate Your Wildcard Squad
                            </h3>

                            <p class="wildcard-generate-description">
                                Run the full Wildcard Optimizer against the current
                                Player Intelligence dataset and build the strongest
                                legal £100m squad.
                            </p>

                        </div>


                        <a
                            class="wildcard-generate-button"
                            href="wildcard.php?generate=1"
                        >
                            Generate Wildcard Squad
                        </a>

                    </div>

                <?php endif; ?>

            </section>
            
            <?php if ($wildcardStatus === 'success'): ?>

                <section class="dashboard-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Starting XI
                        </p>

                        <h2>
                            Best Starting Formation
                        </h2>

                    </div>


                    <?php

                    $startingXI =
                        $wildcardResult[
                            'starting_xi'
                        ]
                        ?? [];


                    $startingByPosition = [

                        'GK' =>
                            [],

                        'DEF' =>
                            [],

                        'MID' =>
                            [],

                        'FWD' =>
                            []
                    ];


                    foreach (
                        $startingXI
                        as $player
                    ) {

                        $position =
                            $player[
                                'position'
                            ]
                            ?? null;


                        if (
                            isset(
                                $startingByPosition[
                                    $position
                                ]
                            )
                        ) {

                            $startingByPosition[
                                $position
                            ][] =
                                $player;
                        }
                    }

                    ?>


                    <div class="wildcard-pitch">


                        <?php

                        $pitchPositions = [

                            'GK' =>
                                'Goalkeeper',

                            'DEF' =>
                                'Defenders',

                            'MID' =>
                                'Midfielders',

                            'FWD' =>
                                'Forwards'
                        ];

                        ?>


                        <?php foreach (
                            $pitchPositions
                            as $position => $label
                        ): ?>

                            <?php if (
                                !empty(
                                    $startingByPosition[
                                        $position
                                    ]
                                )
                            ): ?>

                                <div
                                    class="wildcard-pitch-row wildcard-pitch-row-<?= strtolower(
                                        $position
                                    ); ?>"
                                >

                                    <p class="wildcard-pitch-label">
                                        <?= htmlspecialchars(
                                            $label,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </p>


                                    <div class="wildcard-pitch-players">


                                        <?php foreach (
                                            $startingByPosition[
                                                $position
                                            ]
                                            as $player
                                        ): ?>

                                            <a
                                                class="wildcard-pitch-player"
                                                href="player.php?id=<?= (int) (
                                                    $player[
                                                        'player_id'
                                                    ]
                                                    ?? 0
                                                ); ?>"
                                            >

                                                <div class="wildcard-pitch-player-name">
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


                                                <div class="wildcard-pitch-player-meta">

                                                    <span>
                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $player[
                                                                    'team_name'
                                                                ]
                                                                ?? (
                                                                    $player[
                                                                        'team_short_name'
                                                                    ]
                                                                    ?? ''
                                                                )
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>
                                                    </span>

                                                    <span>
                                                        £<?= number_format(
                                                            (float) (
                                                                $player[
                                                                    'price'
                                                                ]
                                                                ?? 0
                                                            ),
                                                            1
                                                        ); ?>m
                                                    </span>

                                                </div>


                                                <div class="wildcard-pitch-player-score">

                                                    <span>
                                                        Starter
                                                    </span>

                                                    <strong>
                                                        <?= number_format(
                                                            (float) (
                                                                $player[
                                                                    'starter_score'
                                                                ]
                                                                ?? 0
                                                            ),
                                                            2
                                                        ); ?>
                                                    </strong>

                                                </div>

                                            </a>

                                        <?php endforeach; ?>


                                    </div>

                                </div>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </div>

                </section>

            <?php endif; ?>
            
            <?php if ($wildcardStatus === 'success'): ?>

                <section class="dashboard-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Substitutes
                        </p>

                        <h2>
                            Ordered Bench
                        </h2>

                    </div>


                    <?php

                    $bench =
                        $wildcardResult[
                            'bench'
                        ]
                        ?? [];

                    ?>


                    <div class="wildcard-bench-grid">


                        <?php foreach (
                            $bench
                            as $player
                        ): ?>

                            <?php

                            $benchOrder =
                                (int) (
                                    $player[
                                        'bench_order'
                                    ]
                                    ?? 0
                                );


                            $confidence =
                                $player[
                                    'sample_confidence'
                                ]
                                ?? null;


                            $isLowConfidence =
                                is_numeric(
                                    $confidence
                                )
                                &&
                                (float) $confidence
                                <
                                25.0;

                            ?>


                            <a
                                class="wildcard-bench-card<?= $isLowConfidence
                                    ? ' wildcard-bench-card-risk'
                                    : ''; ?>"
                                href="player.php?id=<?= (int) (
                                    $player[
                                        'player_id'
                                    ]
                                    ?? 0
                                ); ?>"
                            >

                                <div class="wildcard-bench-order">
                                    Bench <?= $benchOrder; ?>
                                </div>


                                <div class="wildcard-player-card-top">

                                    <span class="wildcard-player-name">
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
                                    </span>

                                    <span class="wildcard-position-badge">
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


                                <div class="wildcard-player-meta">

                                    <span>
                                        <?= htmlspecialchars(
                                            (string) (
                                                $player[
                                                    'team_name'
                                                ]
                                                ?? (
                                                    $player[
                                                        'team_short_name'
                                                    ]
                                                    ?? ''
                                                )
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </span>

                                    <span>
                                        £<?= number_format(
                                            (float) (
                                                $player[
                                                    'price'
                                                ]
                                                ?? 0
                                            ),
                                            1
                                        ); ?>m
                                    </span>

                                </div>


                                <div class="wildcard-bench-metrics">

                                    <div>

                                        <span>
                                            Value
                                        </span>

                                        <strong>
                                            <?= number_format(
                                                (float) (
                                                    $player[
                                                        'squad_value_score'
                                                    ]
                                                    ?? 0
                                                ),
                                                2
                                            ); ?>
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Confidence
                                        </span>

                                        <strong>
                                            <?= is_numeric(
                                                $confidence
                                            )
                                                ? number_format(
                                                    (float) $confidence,
                                                    1
                                                ) . '%'
                                                : 'N/A'; ?>
                                        </strong>

                                    </div>

                                </div>


                                <?php if ($isLowConfidence): ?>

                                    <div class="wildcard-bench-warning">
                                        Low reliability
                                    </div>

                                <?php endif; ?>

                            </a>

                        <?php endforeach; ?>


                    </div>

                </section>

            <?php endif; ?>
            
            <?php if ($wildcardStatus === 'success'): ?>

                <section class="dashboard-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Squad Intelligence
                        </p>

                        <h2>
                            Structure &amp; Reliability
                        </h2>

                        <p class="wildcard-section-description">
                            A breakdown of the squad's overall optimisation,
                            bench reliability and goalkeeper quality requirements.
                        </p>

                    </div>


                    <div class="wildcard-intelligence-grid">


                        <div class="wildcard-intelligence-card">

                            <span class="wildcard-intelligence-label">
                                Wildcard Score
                            </span>

                            <strong class="wildcard-intelligence-value">
                                <?= number_format(
                                    (float) (
                                        $wildcardResult[
                                            'wildcard_score'
                                        ]
                                        ?? 0
                                    ),
                                    2
                                ); ?>
                            </strong>

                            <span class="wildcard-intelligence-note">
                                Overall full-squad optimisation score
                            </span>

                        </div>


                        <div class="wildcard-intelligence-card">

                            <span class="wildcard-intelligence-label">
                                Raw Bench Score
                            </span>

                            <strong class="wildcard-intelligence-value">
                                <?= number_format(
                                    (float) (
                                        $wildcardResult[
                                            'raw_bench_score'
                                        ]
                                        ?? 0
                                    ),
                                    2
                                ); ?>
                            </strong>

                            <span class="wildcard-intelligence-note">
                                Bench quality before reliability adjustment
                            </span>

                        </div>


                        <div class="wildcard-intelligence-card">

                            <span class="wildcard-intelligence-label">
                                Adjusted Bench Score
                            </span>

                            <strong class="wildcard-intelligence-value">
                                <?= number_format(
                                    (float) (
                                        $wildcardResult[
                                            'bench_score'
                                        ]
                                        ?? 0
                                    ),
                                    2
                                ); ?>
                            </strong>

                            <span class="wildcard-intelligence-note">
                                Bench quality after reliability adjustment
                            </span>

                        </div>


                        <div class="wildcard-intelligence-card">

                            <span class="wildcard-intelligence-label">
                                Reliability Penalty
                            </span>

                            <strong class="wildcard-intelligence-value">
                                <?= number_format(
                                    (float) (
                                        $wildcardResult[
                                            'bench_reliability_penalty'
                                        ]
                                        ?? 0
                                    ),
                                    2
                                ); ?>
                            </strong>

                            <span class="wildcard-intelligence-note">
                                Penalty caused by uncertain bench options
                            </span>

                        </div>


                        <div class="wildcard-intelligence-card">

                            <span class="wildcard-intelligence-label">
                                GK Min Confidence
                            </span>

                            <strong class="wildcard-intelligence-value">
                                <?= number_format(
                                    (float) (
                                        $wildcardResult[
                                            'search'
                                        ][
                                            'gk_starter_min_confidence'
                                        ]
                                        ?? 0
                                    ),
                                    1
                                ); ?>%
                            </strong>

                            <span class="wildcard-intelligence-note">
                                Minimum confidence required to start
                            </span>

                        </div>


                        <div class="wildcard-intelligence-card">

                            <span class="wildcard-intelligence-label">
                                GK Quality Floor
                            </span>

                            <strong class="wildcard-intelligence-value">
                                <?= number_format(
                                    (float) (
                                        $wildcardResult[
                                            'search'
                                        ][
                                            'gk_starter_score_floor'
                                        ]
                                        ?? 0
                                    ),
                                    2
                                ); ?>
                            </strong>

                            <span class="wildcard-intelligence-note">
                                Minimum Starter Score for the starting GK
                            </span>

                        </div>


                    </div>

                </section>

            <?php endif; ?>
            
            <?php

                $wildcardInsights =
                    [];


                if (
                    $wildcardStatus === 'success'
                ) {

                    $startingXI =
                        $wildcardResult[
                            'starting_xi'
                        ]
                        ?? [];


                    $bench =
                        $wildcardResult[
                            'bench'
                        ]
                        ?? [];


                    $formation =
                        (string) (
                            $wildcardResult[
                                'formation'
                            ]
                            ?? 'N/A'
                        );


                    $bank =
                        (float) (
                            $wildcardResult[
                                'bank'
                            ]
                            ?? 0
                        );


                    $startingGk =
                        null;


                    foreach (
                        $startingXI
                        as $player
                    ) {

                        if (
                            (
                                $player[
                                    'position'
                                ]
                                ?? null
                            )
                            === 'GK'
                        ) {

                            $startingGk =
                                $player;

                            break;
                        }
                    }


                    /*
                     * --------------------------------------------------------
                     * FORMATION INSIGHT
                     * --------------------------------------------------------
                     */

                    $wildcardInsights[] = [

                        'title' =>
                            'Best Formation',

                        'value' =>
                            $formation,

                        'text' =>
                            'This formation produced the strongest legal Starting XI from the generated 15-player squad.'
                    ];


                    /*
                     * --------------------------------------------------------
                     * GOALKEEPER RELIABILITY
                     * --------------------------------------------------------
                     */

                    if (
                        is_array(
                            $startingGk
                        )
                    ) {

                        $gkName =
                            (string) (
                                $startingGk[
                                    'name'
                                ]
                                ?? 'Starting goalkeeper'
                            );


                        $gkConfidence =
                            (float) (
                                $startingGk[
                                    'sample_confidence'
                                ]
                                ?? 0
                            );


                        $gkStarterScore =
                            (float) (
                                $startingGk[
                                    'starter_score'
                                ]
                                ?? 0
                            );


                        $gkFloor =
                            (float) (
                                $wildcardResult[
                                    'search'
                                ][
                                    'gk_starter_score_floor'
                                ]
                                ?? 0
                            );


                        $wildcardInsights[] = [

                            'title' =>
                                'Reliable Goalkeeper',

                            'value' =>
                                $gkName,

                            'text' =>
                                $gkName
                                . ' starts with '
                                . number_format(
                                    $gkConfidence,
                                    1
                                )
                                . '% confidence and a Starter Score of '
                                . number_format(
                                    $gkStarterScore,
                                    2
                                )
                                . ', above the required '
                                . number_format(
                                    $gkFloor,
                                    2
                                )
                                . ' goalkeeper quality floor.'
                        ];
                    }


                    /*
                     * --------------------------------------------------------
                     * PREMIUM STARTERS
                     * --------------------------------------------------------
                     */

                    $premiumStarters =
                        [];


                    foreach (
                        $startingXI
                        as $player
                    ) {

                        $price =
                            (float) (
                                $player[
                                    'price'
                                ]
                                ?? 0
                            );


                        if (
                            $price >= 9.0
                        ) {

                            $premiumStarters[] =
                                (string) (
                                    $player[
                                        'name'
                                    ]
                                    ?? ''
                                );
                        }
                    }


                    if (
                        !empty(
                            $premiumStarters
                        )
                    ) {

                        $wildcardInsights[] = [

                            'title' =>
                                'Premium Core',

                            'value' =>
                                count(
                                    $premiumStarters
                                )
                                . ' premium starters',

                            'text' =>
                                'The squad concentrates budget in '
                                . implode(
                                    ', ',
                                    $premiumStarters
                                )
                                . ', while using lower-cost players elsewhere to keep the squad within budget.'
                        ];
                    }


                    /*
                     * --------------------------------------------------------
                     * BENCH RELIABILITY
                     * --------------------------------------------------------
                     */

                    $lowConfidenceBench =
                        [];


                    foreach (
                        $bench
                        as $player
                    ) {

                        $confidence =
                            $player[
                                'sample_confidence'
                            ]
                            ?? null;


                        if (
                            is_numeric(
                                $confidence
                            )
                            &&
                            (float) $confidence
                            <
                            25.0
                        ) {

                            $lowConfidenceBench[] =
                                (string) (
                                    $player[
                                        'name'
                                    ]
                                    ?? ''
                                );
                        }
                    }


                    if (
                        !empty(
                            $lowConfidenceBench
                        )
                    ) {

                        $wildcardInsights[] = [

                            'title' =>
                                'Bench Risk',

                            'value' =>
                                count(
                                    $lowConfidenceBench
                                )
                                . ' low-confidence options',

                            'text' =>
                                implode(
                                    ', ',
                                    $lowConfidenceBench
                                )
                                . ' currently carry low sample confidence. They remain legal budget enablers but reduce the reliability-adjusted bench score.'
                        ];

                    } else {

                        $wildcardInsights[] = [

                            'title' =>
                                'Bench Reliability',

                            'value' =>
                                'Strong',

                            'text' =>
                                'All selected bench players currently meet the preferred confidence level for supporting squad roles.'
                        ];
                    }


                    /*
                     * --------------------------------------------------------
                     * BUDGET USE
                     * --------------------------------------------------------
                     */

                    $wildcardInsights[] = [

                        'title' =>
                            'Budget Use',

                        'value' =>
                            '£'
                            . number_format(
                                $bank,
                                1
                            )
                            . 'm remaining',

                        'text' =>
                            $bank <= 0.0
                                ? 'The optimiser has used the full £100.0m budget to maximise squad quality.'
                                : 'The optimiser retains some budget rather than forcing spending where it does not improve the final squad structure.'
                    ];
                }

                ?>
                
                <?php if (
                    $wildcardStatus === 'success'
                    &&
                    !empty(
                        $wildcardInsights
                    )
                ): ?>

                    <section class="dashboard-section">

                        <div class="section-heading">

                            <p class="eyebrow">
                                Decision Support
                            </p>

                            <h2>
                                Why This Squad?
                            </h2>

                            <p class="wildcard-section-description">
                                Key reasons behind the current wildcard recommendation.
                            </p>

                        </div>


                        <div class="wildcard-insight-grid">


                            <?php foreach (
                                $wildcardInsights
                                as $insight
                            ): ?>

                                <div class="wildcard-insight-card">

                                    <span class="wildcard-insight-label">
                                        <?= htmlspecialchars(
                                            (string) (
                                                $insight[
                                                    'title'
                                                ]
                                                ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </span>


                                    <strong class="wildcard-insight-value">
                                        <?= htmlspecialchars(
                                            (string) (
                                                $insight[
                                                    'value'
                                                ]
                                                ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </strong>


                                    <p class="wildcard-insight-text">
                                        <?= htmlspecialchars(
                                            (string) (
                                                $insight[
                                                    'text'
                                                ]
                                                ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </p>

                                </div>

                            <?php endforeach; ?>


                        </div>

                    </section>

                <?php endif; ?>

        </main>

    </div>

</div>

</body>

</html>