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


    $playerIntelligenceService =
        new PlayerIntelligenceService(
            $db
        );


    $players =
        $playerIntelligenceService
            ->getAllPlayerSummaries();

} catch (Throwable $exception) {

    http_response_code(500);

    die(
        'Unable to initialise player comparison.'
    );
}


/*
 * ============================================================
 * PLAYER OPTIONS
 * ============================================================
 */

usort(
    $players,
    function (
        array $a,
        array $b
    ): int {

        return strcasecmp(
            (string) (
                $a['name']
                ?? ''
            ),
            (string) (
                $b['name']
                ?? ''
            )
        );
    }
);


/*
 * ============================================================
 * REQUESTED PLAYERS
 * ============================================================
 */

$playerIdA =
    filter_input(
        INPUT_GET,
        'player1',
        FILTER_VALIDATE_INT
    );


$playerIdB =
    filter_input(
        INPUT_GET,
        'player2',
        FILTER_VALIDATE_INT
    );


$comparison =
    null;


$comparisonError =
    null;


if (
    $playerIdA !== false
    &&
    $playerIdA !== null
    &&
    $playerIdB !== false
    &&
    $playerIdB !== null
) {

    if ($playerIdA === $playerIdB) {

        $comparisonError =
            'Please select two different players.';

    } else {

        try {

            $comparison =
                $playerIntelligenceService
                    ->comparePlayers(
                        (int) $playerIdA,
                        (int) $playerIdB
                    );


            if ($comparison === null) {

                $comparisonError =
                    'The selected players could not be compared.';
            }

        } catch (Throwable $exception) {

            $comparisonError =
                'Unable to complete the player comparison.';
        }
    }
}


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function comparisonDisplayRating(
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
        (float) $value,
        1
    );
}


function comparisonDisplayPrice(
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


function comparisonMetricWinnerClass(
    string $winner,
    string $side
): string {

    if ($winner === $side) {
        return 'comparison-metric-winner';
    }


    if ($winner === 'tie') {
        return 'comparison-metric-tie';
    }


    return '';
}


function comparisonOverallName(
    array $comparison
): string {

    $winner =
        $comparison[
            'overall_winner'
        ]
        ?? 'tie';


    if ($winner === 'a') {

        return (string) (
            $comparison[
                'player_a'
            ]['name']
            ?? 'Player A'
        );
    }


    if ($winner === 'b') {

        return (string) (
            $comparison[
                'player_b'
            ]['name']
            ?? 'Player B'
        );
    }


    return 'Tie';
}


function comparisonVerdictClass(
    ?string $verdict
): string {

    $verdict =
        strtolower(
            (string) $verdict
        );


    if (
        str_contains(
            $verdict,
            'excellent'
        )
    ) {
        return 'comparison-verdict-excellent';
    }


    if (
        str_contains(
            $verdict,
            'strong'
        )
    ) {
        return 'comparison-verdict-strong';
    }


    if (
        str_contains(
            $verdict,
            'consider'
        )
    ) {
        return 'comparison-verdict-consider';
    }


    if (
        str_contains(
            $verdict,
            'watchlist'
        )
    ) {
        return 'comparison-verdict-watchlist';
    }


    if (
        str_contains(
            $verdict,
            'avoid'
        )
    ) {
        return 'comparison-verdict-avoid';
    }


    return 'comparison-verdict-neutral';
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
        content="Compare FPL players using FPL Intelligence."
    >

    <title>
        Player Comparison | FPL Intelligence
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
                    class="nav-link"
                >
                    <span class="nav-icon">
                        👤
                    </span>

                    Players
                </a>


                <a
                    href="compare.php"
                    class="nav-link active"
                >
                    <span class="nav-icon">
                        ⇄
                    </span>

                    Compare
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
                    href="transfers.php"
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
                        Player Comparison
                    </h1>

                </div>

            </header>


            <!-- ==============================================
                 MAIN
                 ============================================== -->

            <main class="dashboard">


                <!-- ==========================================
                     PLAYER SELECTORS
                     ========================================== -->

                <section class="dashboard-card comparison-selector-card">

                    <div class="card-header">

                        <div>

                            <p class="card-kicker">
                                Comparison
                            </p>

                            <h2>
                                Compare Players
                            </h2>

                        </div>

                    </div>


                    <form
                        method="get"
                        action="compare.php"
                        class="comparison-form"
                    >

                        <div class="comparison-selector">

                            <label for="player1">
                                Player One
                            </label>

                            <select
                                name="player1"
                                id="player1"
                                required
                            >

                                <option value="">
                                    Select player
                                </option>

                                <?php foreach (
                                    $players
                                    as $player
                                ): ?>

                                    <?php

                                    $optionPlayerId =
                                        (int) (
                                            $player[
                                                'player_id'
                                            ]
                                            ?? 0
                                        );


                                    if (
                                        $optionPlayerId <= 0
                                    ) {
                                        continue;
                                    }

                                    ?>

                                    <option
                                        value="<?= $optionPlayerId; ?>"
                                        <?= (
                                            (int) $playerIdA
                                            ===
                                            $optionPlayerId
                                        )
                                            ? 'selected'
                                            : ''; ?>
                                    >

                                        <?= htmlspecialchars(
                                            (string) (
                                                $player['name']
                                                ?? 'Unknown'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        —
                                        <?= htmlspecialchars(
                                            (string) (
                                                $player[
                                                    'team_short_name'
                                                ]
                                                ??
                                                $player[
                                                    'team_name'
                                                ]
                                                ??
                                                ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        —
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

                                        —
                                        <?= comparisonDisplayPrice(
                                            $player['price']
                                            ?? null
                                        ); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="comparison-versus">
                            VS
                        </div>


                        <div class="comparison-selector">

                            <label for="player2">
                                Player Two
                            </label>

                            <select
                                name="player2"
                                id="player2"
                                required
                            >

                                <option value="">
                                    Select player
                                </option>

                                <?php foreach (
                                    $players
                                    as $player
                                ): ?>

                                    <?php

                                    $optionPlayerId =
                                        (int) (
                                            $player[
                                                'player_id'
                                            ]
                                            ?? 0
                                        );


                                    if (
                                        $optionPlayerId <= 0
                                    ) {
                                        continue;
                                    }

                                    ?>

                                    <option
                                        value="<?= $optionPlayerId; ?>"
                                        <?= (
                                            (int) $playerIdB
                                            ===
                                            $optionPlayerId
                                        )
                                            ? 'selected'
                                            : ''; ?>
                                    >

                                        <?= htmlspecialchars(
                                            (string) (
                                                $player['name']
                                                ?? 'Unknown'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        —
                                        <?= htmlspecialchars(
                                            (string) (
                                                $player[
                                                    'team_short_name'
                                                ]
                                                ??
                                                $player[
                                                    'team_name'
                                                ]
                                                ??
                                                ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        —
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

                                        —
                                        <?= comparisonDisplayPrice(
                                            $player['price']
                                            ?? null
                                        ); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <button
                            type="submit"
                            class="comparison-submit"
                        >
                            Compare Players
                        </button>

                    </form>


                    <?php if (
                        $comparisonError !== null
                    ): ?>

                        <div class="comparison-error">

                            <?= htmlspecialchars(
                                $comparisonError,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </div>

                    <?php endif; ?>

                </section>


                <?php if (
                    $comparison !== null
                ): ?>


                    <?php

                    $playerA =
                        $comparison[
                            'player_a'
                        ];


                    $playerB =
                        $comparison[
                            'player_b'
                        ];


                    $overallWinner =
                        comparisonOverallName(
                            $comparison
                        );

                    ?>


                    <!-- ======================================
                         PLAYER HEADERS
                         ====================================== -->

                    <section class="comparison-player-grid">

                        <article class="dashboard-card comparison-player-card">

                            <span class="comparison-player-number">
                                Player One
                            </span>


                            <h2>

                                <a
                                    href="player.php?id=<?= (int) (
                                        $playerA[
                                            'player_id'
                                        ]
                                        ?? 0
                                    ); ?>"
                                >

                                    <?= htmlspecialchars(
                                        (string) (
                                            $playerA['name']
                                            ?? 'Unknown'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </a>

                            </h2>


                            <div class="comparison-player-meta">

                                <span>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $playerA[
                                                'team_name'
                                            ]
                                            ?? 'Unknown'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </span>

                                <span>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $playerA[
                                                'position'
                                            ]
                                            ?? '—'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </span>

                                <span>
                                    <?= comparisonDisplayPrice(
                                        $playerA['price']
                                        ?? null
                                    ); ?>
                                </span>

                            </div>


                            <span class="comparison-verdict <?= comparisonVerdictClass(
                                $comparison[
                                    'player_a_verdict'
                                ]
                                ?? null
                            ); ?>">

                                <?= htmlspecialchars(
                                    (string) (
                                        $comparison[
                                            'player_a_verdict'
                                        ]
                                        ?? 'Unknown'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>


                            <strong class="comparison-player-intelligence">

                                <?= comparisonDisplayRating(
                                    $playerA[
                                        'intelligence_score'
                                    ]
                                    ?? null
                                ); ?>

                                <small>
                                    INT
                                </small>

                            </strong>

                        </article>


                        <article class="dashboard-card comparison-player-card">

                            <span class="comparison-player-number">
                                Player Two
                            </span>


                            <h2>

                                <a
                                    href="player.php?id=<?= (int) (
                                        $playerB[
                                            'player_id'
                                        ]
                                        ?? 0
                                    ); ?>"
                                >

                                    <?= htmlspecialchars(
                                        (string) (
                                            $playerB['name']
                                            ?? 'Unknown'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </a>

                            </h2>


                            <div class="comparison-player-meta">

                                <span>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $playerB[
                                                'team_name'
                                            ]
                                            ?? 'Unknown'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </span>

                                <span>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $playerB[
                                                'position'
                                            ]
                                            ?? '—'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </span>

                                <span>
                                    <?= comparisonDisplayPrice(
                                        $playerB['price']
                                        ?? null
                                    ); ?>
                                </span>

                            </div>


                            <span class="comparison-verdict <?= comparisonVerdictClass(
                                $comparison[
                                    'player_b_verdict'
                                ]
                                ?? null
                            ); ?>">

                                <?= htmlspecialchars(
                                    (string) (
                                        $comparison[
                                            'player_b_verdict'
                                        ]
                                        ?? 'Unknown'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </span>


                            <strong class="comparison-player-intelligence">

                                <?= comparisonDisplayRating(
                                    $playerB[
                                        'intelligence_score'
                                    ]
                                    ?? null
                                ); ?>

                                <small>
                                    INT
                                </small>

                            </strong>

                        </article>

                    </section>


                    <!-- ======================================
                         OVERALL RESULT
                         ====================================== -->

                    <section class="dashboard-card comparison-result-card">

                        <p class="card-kicker">
                            Overall Comparison
                        </p>


                        <?php if (
                            (
                                $comparison[
                                    'overall_winner'
                                ]
                                ?? 'tie'
                            )
                            === 'tie'
                        ): ?>

                            <h2>
                                Players are tied
                            </h2>

                        <?php else: ?>

                            <h2>

                                <?= htmlspecialchars(
                                    $overallWinner,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                                leads overall

                            </h2>

                        <?php endif; ?>


                        <p>

                            Overall comparison is determined by
                            Player Intelligence Score.

                            <?php if (
                                isset(
                                    $comparison[
                                        'overall_difference'
                                    ]
                                )
                                &&
                                is_numeric(
                                    $comparison[
                                        'overall_difference'
                                    ]
                                )
                            ): ?>

                                Intelligence difference:

                                <strong>

                                    <?= comparisonDisplayRating(
                                        $comparison[
                                            'overall_difference'
                                        ]
                                    ); ?>

                                </strong>

                                points.

                            <?php endif; ?>

                        </p>

                    </section>


                    <!-- ======================================
                         METRIC COMPARISON
                         ====================================== -->

                    <section class="dashboard-card comparison-metrics-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Intelligence Breakdown
                                </p>

                                <h2>
                                    Metric Comparison
                                </h2>

                            </div>

                        </div>


                        <div class="comparison-metric-table">

                            <div class="comparison-metric-row comparison-metric-header">

                                <div>
                                    Metric
                                </div>

                                <div>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $playerA['name']
                                            ?? 'Player One'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </div>

                                <div>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $playerB['name']
                                            ?? 'Player Two'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </div>

                                <div>
                                    Difference
                                </div>

                            </div>


                            <?php foreach (
                                $comparison[
                                    'metrics'
                                ]
                                as $metric
                            ): ?>

                                <?php

                                $winner =
                                    (string) (
                                        $metric['winner']
                                        ?? 'tie'
                                    );

                                ?>

                                <div class="comparison-metric-row">

                                    <div class="comparison-metric-label">

                                        <?= htmlspecialchars(
                                            (string) (
                                                $metric['label']
                                                ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </div>


                                    <div class="<?= comparisonMetricWinnerClass(
                                        $winner,
                                        'a'
                                    ); ?>">

                                        <?= comparisonDisplayRating(
                                            $metric[
                                                'player_a'
                                            ]
                                            ?? null
                                        ); ?>

                                    </div>


                                    <div class="<?= comparisonMetricWinnerClass(
                                        $winner,
                                        'b'
                                    ); ?>">

                                        <?= comparisonDisplayRating(
                                            $metric[
                                                'player_b'
                                            ]
                                            ?? null
                                        ); ?>

                                    </div>


                                    <div class="comparison-metric-difference">

                                        <?php if (
                                            isset(
                                                $metric[
                                                    'difference'
                                                ]
                                            )
                                            &&
                                            is_numeric(
                                                $metric[
                                                    'difference'
                                                ]
                                            )
                                        ): ?>

                                            <?= comparisonDisplayRating(
                                                $metric[
                                                    'difference'
                                                ]
                                            ); ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </section>


                    <!-- ======================================
                         METRIC WINS
                         ====================================== -->

                    <section class="dashboard-card comparison-win-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Supporting Context
                                </p>

                                <h2>
                                    Metric Wins
                                </h2>

                            </div>

                        </div>


                        <p class="comparison-win-explanation">

                            Metric wins provide supporting context
                            only. They do not determine the overall
                            winner.

                        </p>


                        <div class="comparison-win-grid">

                            <div class="comparison-win-item">

                                <span>

                                    <?= htmlspecialchars(
                                        (string) (
                                            $playerA['name']
                                            ?? 'Player One'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </span>

                                <strong>

                                    <?= (int) (
                                        $comparison[
                                            'metric_wins'
                                        ]['player_a']
                                        ?? 0
                                    ); ?>

                                </strong>

                            </div>


                            <div class="comparison-win-item comparison-win-ties">

                                <span>
                                    Ties
                                </span>

                                <strong>

                                    <?= (int) (
                                        $comparison[
                                            'metric_wins'
                                        ]['ties']
                                        ?? 0
                                    ); ?>

                                </strong>

                            </div>


                            <div class="comparison-win-item">

                                <span>

                                    <?= htmlspecialchars(
                                        (string) (
                                            $playerB['name']
                                            ?? 'Player Two'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </span>

                                <strong>

                                    <?= (int) (
                                        $comparison[
                                            'metric_wins'
                                        ]['player_b']
                                        ?? 0
                                    ); ?>

                                </strong>

                            </div>

                        </div>

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
                    Player Comparison
                </span>

            </footer>


        </div>

    </div>


    <script src="assets/js/app.js"></script>

</body>

</html>