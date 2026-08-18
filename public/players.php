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
 * PLAYER INTELLIGENCE
 * ============================================================
 */

$playerIntelligenceService =
    new PlayerIntelligenceService(
        $db
    );


try {

    $players =
        $playerIntelligenceService
            ->getAllPlayerSummaries();
            
    $rankedPlayers =
        $playerIntelligenceService
            ->getRankedPlayers();


    $topRatedPlayer =
        $rankedPlayers[0]
        ?? null;
            
    $rankedPlayerCount =
    count(
        $rankedPlayers
    );
    
    $intelligenceRanks =
        [];

    foreach (
        $rankedPlayers
        as $index => $rankedPlayer
    ) {

        $playerId =
            (int) (
                $rankedPlayer['player_id']
                ?? 0
            );


        if ($playerId <= 0) {
            continue;
        }


        $intelligenceRanks[$playerId] =
            $index + 1;
    }

} catch (Throwable $exception) {

    $players = [];

    $pageError =
        $exception->getMessage();
}


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function displayRating(
    mixed $rating
): string {

    if (
        $rating === null
        ||
        !is_numeric($rating)
    ) {
        return '—';
    }


    return number_format(
        (float) $rating,
        1
    );
}


function displayPrice(
    mixed $price
): string {

    if (
        $price === null
        ||
        !is_numeric($price)
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


function ratingClass(
    mixed $rating
): string {

    if (
        $rating === null
        ||
        !is_numeric($rating)
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
        content="Explore Fantasy Premier League players using FPL Intelligence ratings."
    >

    <title>
        Player Explorer | FPL Intelligence
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
             MAIN APPLICATION
             ================================================== -->

        <div class="app-content">


            <!-- ==============================================
                 HEADER
                 ============================================== -->

            <header class="topbar">

                <div>

                    <p class="eyebrow">
                        Player Intelligence
                    </p>

                    <h1>
                        Player Explorer
                    </h1>

                </div>


                <div class="topbar-actions">

                    <span class="data-status">

                        <span class="status-dot online"></span>

                        <?= $rankedPlayerCount; ?>
                        ranked players

                    </span>

                </div>

            </header>


            <!-- ==============================================
                 PLAYER EXPLORER
                 ============================================== -->

            <main class="dashboard">


                <p class="page-description">
                    Explore and compare players using
                    strength, value, fixture opportunity
                    and overall FPL intelligence.
                </p>


                <?php if (isset($pageError)): ?>

                    <section class="dashboard-card">

                        <strong>
                            Player data could not be loaded.
                        </strong>

                        <p>
                            <?= htmlspecialchars(
                                $pageError,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </p>

                    </section>

                <?php endif; ?>


                <!-- ==========================================
                     SUMMARY
                     ========================================== -->

                <section class="explorer-summary">

                    <article class="dashboard-card explorer-stat">

                        <span>
                            Ranked Players
                        </span>

                        <strong>
                            <?= $rankedPlayerCount; ?>
                        </strong>

                    </article>


                    <article class="dashboard-card explorer-stat">

                        <span>
                            Top Rated
                        </span>

                        <strong>

                            <?php if ($topRatedPlayer !== null): ?>

                                <?= htmlspecialchars(
                                    (string) (
                                        $topRatedPlayer['name']
                                        ?? '—'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>


                            <?php else: ?>

                                —

                            <?php endif; ?>

                        </strong>

                    </article>


                    <article class="dashboard-card explorer-stat">

                        <span>
                            Top Score
                        </span>

                        <strong>

                            <?= $topRatedPlayer !== null
                                ? displayRating(
                                    $topRatedPlayer[
                                        'intelligence_score'
                                    ]
                                    ?? null
                                )
                                : '—'; ?>

                        </strong>

                    </article>

                </section>


                <!-- ==========================================
                     PLAYER TABLE
                     ========================================== -->

                <section class="dashboard-card player-explorer-card">

                    <div class="card-header">  

                        <div>

                            <p class="card-kicker">
                                Rankings
                            </p>

                            <h2>
                                Player Rankings
                            </h2>

                        </div>


                        <span class="card-badge">

                            <?= count($players); ?>
                            Total Players

                        </span>

                    </div>
                    
                    <div class="player-filters">
                        
                        <div class="filter-group">

                            <label>
                                Player Pool
                            </label>

                            <div class="player-pool-filters">

                                <button
                                    type="button"
                                    class="player-pool-filter active"
                                    data-pool="ranked"
                                >
                                    Ranked
                                </button>

                                <button
                                    type="button"
                                    class="player-pool-filter"
                                    data-pool="all"
                                >
                                    All Players
                                </button>

                            </div>

                        </div>

                        <div class="filter-group filter-search">

                            <label for="player-search">
                                Search
                            </label>

                            <input
                                type="search"
                                id="player-search"
                                placeholder="Search players..."
                                autocomplete="off"
                            >

                        </div>


                        <div class="filter-group">

                            <label for="team-filter">
                                Team
                            </label>

                            <select id="team-filter">

                                <option value="">
                                    All Teams
                                </option>

                                <?php

                                $teamsForFilter = [];


                                foreach ($players as $player) {

                                    $teamName =
                                        $player['team_name']
                                        ?? null;


                                    $teamShortName =
                                        $player['team_short_name']
                                        ?? null;


                                    if ($teamName === null) {
                                        continue;
                                    }


                                    $teamsForFilter[$teamName] =
                                        $teamShortName
                                        ?? $teamName;
                                }


                                ksort(
                                    $teamsForFilter
                                );


                                foreach (
                                    $teamsForFilter
                                    as $teamName => $teamShortName
                                ):

                                ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $teamName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $teamName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="filter-group">

                            <label for="price-filter">
                                Max Price
                            </label>

                            <select id="price-filter">

                                <option value="">
                                    Any Price
                                </option>

                                <option value="4.5">
                                    £4.5m
                                </option>

                                <option value="5.0">
                                    £5.0m
                                </option>

                                <option value="5.5">
                                    £5.5m
                                </option>

                                <option value="6.0">
                                    £6.0m
                                </option>

                                <option value="6.5">
                                    £6.5m
                                </option>

                                <option value="7.0">
                                    £7.0m
                                </option>

                                <option value="7.5">
                                    £7.5m
                                </option>

                                <option value="8.0">
                                    £8.0m
                                </option>

                                <option value="10.0">
                                    £10.0m
                                </option>

                                <option value="12.0">
                                    £12.0m
                                </option>

                                <option value="15.5">
                                    £15.5m
                                </option>

                            </select>

                        </div>
                        
                        <div class="filter-group">

                            <label for="availability-filter">
                                Availability
                            </label>

                            <select id="availability-filter">

                                <option value="">
                                    All
                                </option>

                                <option value="available">
                                    Available
                                </option>

                                <option value="likely available">
                                    Likely Available
                                </option>

                                <option value="doubtful">
                                    Doubtful
                                </option>

                                <option value="unavailable">
                                    Unavailable
                                </option>

                                <option value="unknown">
                                    Unknown
                                </option>

                            </select>

                        </div>
                        
                        <div class="filter-group">

                            <label for="intelligence-filter">
                                Min Intelligence
                            </label>

                            <select id="intelligence-filter">

                                <option value="">
                                    Any
                                </option>

                                <option value="40">
                                    40+
                                </option>

                                <option value="50">
                                    50+
                                </option>

                                <option value="55">
                                    55+
                                </option>

                                <option value="60">
                                    60+
                                </option>

                                <option value="65">
                                    65+
                                </option>

                                <option value="70">
                                    70+
                                </option>

                                <option value="80">
                                    80+
                                </option>

                            </select>

                        </div>
                        
                        <div class="filter-group">

                            <label for="value-filter">
                                Min Value
                            </label>

                            <select id="value-filter">

                                <option value="">
                                    Any
                                </option>

                                <option value="40">
                                    40+
                                </option>

                                <option value="50">
                                    50+
                                </option>

                                <option value="60">
                                    60+
                                </option>

                                <option value="70">
                                    70+
                                </option>

                                <option value="80">
                                    80+
                                </option>

                                <option value="90">
                                    90+
                                </option>

                            </select>

                        </div>


                        <div class="filter-group filter-position">

                            <label>
                                Position
                            </label>

                            <div class="position-filters">

                                <button
                                    type="button"
                                    class="position-filter active"
                                    data-position=""
                                >
                                    All
                                </button>

                                <button
                                    type="button"
                                    class="position-filter"
                                    data-position="GK"
                                >
                                    GK
                                </button>

                                <button
                                    type="button"
                                    class="position-filter"
                                    data-position="DEF"
                                >
                                    DEF
                                </button>

                                <button
                                    type="button"
                                    class="position-filter"
                                    data-position="MID"
                                >
                                    MID
                                </button>

                                <button
                                    type="button"
                                    class="position-filter"
                                    data-position="FWD"
                                >
                                    FWD
                                </button>

                            </div>

                        </div>


                        <div class="filter-actions">

                            <button
                                type="button"
                                id="clear-player-filters"
                                class="filter-clear"
                            >
                                Clear
                            </button>

                        </div>

                    </div>

                    <div class="player-filter-summary">

                        Showing
                        <strong id="visible-player-count">
                            <?= $rankedPlayerCount; ?>
                        </strong>
                        of
                        <strong>
                            <?= count($players); ?>
                        </strong>
                        players

                    </div>


                    <div class="player-table-wrapper">

                        <table class="player-table">

                            <thead>

                                <tr>

                                    <th
                                        class="rank-column sortable-column"
                                        data-sort="rank"
                                    >
                                        #
                                    </th>

                                    <th
                                        class="sortable-column"
                                        data-sort="name"
                                    >
                                        Player
                                    </th>

                                    <th
                                        class="sortable-column"
                                        data-sort="team"
                                    >
                                        Team
                                    </th>

                                    <th
                                        class="sortable-column"
                                        data-sort="position"
                                    >
                                        Pos
                                    </th>

                                    <th
                                        class="sortable-column"
                                        data-sort="price"
                                    >
                                        Price
                                    </th>
                                    
                                    <th
                                        class="sortable-column"
                                        data-sort="availability"
                                    >
                                        Availability
                                    </th>

                                    <th
                                        class="sortable-column"
                                        data-sort="strength"
                                    >
                                        STR
                                    </th>

                                    <th
                                        class="sortable-column"
                                        data-sort="value"
                                    >
                                        VAL
                                    </th>

                                    <th
                                        class="sortable-column"
                                        data-sort="fixture"
                                    >
                                        FIX
                                    </th>

                                    <th
                                        class="sortable-column active-sort"
                                        data-sort="intelligence"
                                    >
                                        INT
                                    </th>

                                    <th
                                        class="sortable-column"
                                        data-sort="rating"
                                    >
                                        Rating
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                            <?php if (empty($players)): ?>

                                <tr>

                                    <td
                                        colspan="11"
                                        class="empty-table"
                                    >
                                        No players available.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach (
                                    $players
                                    as $index => $player
                                ): ?>
                                
                                <?php
                                    $playerId =
                                        (int) (
                                            $player['player_id']
                                            ?? 0
                                        );


                                    $intelligenceRank =
                                        $intelligenceRanks[$playerId]
                                        ?? null;

                                    ?>

                                    <tr class="player-row"

                                        data-rank="<?= $intelligenceRank
                                            ?? ''; ?>"

                                        data-ranked="<?= isset(
                                            $player['intelligence_score']
                                        )
                                        &&
                                        $player['intelligence_score'] !== null
                                            ? '1'
                                            : '0'; ?>"

                                        data-name="<?= htmlspecialchars(
                                            strtolower(
                                                (string) (
                                                    $player['name']
                                                    ?? ''
                                                )
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"

                                        data-team="<?= htmlspecialchars(
                                            (string) (
                                                $player['team_name']
                                                ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"

                                        data-position="<?= htmlspecialchars(
                                            (string) (
                                                $player['position']
                                                ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        
                                        data-price="<?= isset(
                                            $player['price']
                                        )
                                        && is_numeric(
                                            $player['price']
                                        )
                                            ? (float) $player['price']
                                            : ''; ?>"

                                        data-availability="<?= htmlspecialchars(
                                            strtolower(
                                                (string) (
                                                    $player[
                                                        'availability_label'
                                                    ]
                                                    ?? 'unknown'
                                                )
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"

                                        data-strength="<?= isset(
                                            $player['strength_rating']
                                        )
                                        && is_numeric(
                                            $player['strength_rating']
                                        )
                                            ? (float)
                                                $player['strength_rating']
                                            : ''; ?>"

                                        data-value="<?= isset(
                                            $player['value_rating']
                                        )
                                        && is_numeric(
                                            $player['value_rating']
                                        )
                                            ? (float)
                                                $player['value_rating']
                                            : ''; ?>"

                                        data-fixture="<?= isset(
                                            $player['fixture_rating']
                                        )
                                        && is_numeric(
                                            $player['fixture_rating']
                                        )
                                            ? (float)
                                                $player['fixture_rating']
                                            : ''; ?>"

                                        data-intelligence="<?= isset(
                                            $player['intelligence_score']
                                        )
                                        && is_numeric(
                                            $player['intelligence_score']
                                        )
                                            ? (float)
                                                $player['intelligence_score']
                                            : ''; ?>"

                                        data-rating="<?= htmlspecialchars(
                                            strtolower(
                                                (string) (
                                                    $player[
                                                        'intelligence_label'
                                                    ]
                                                    ?? ''
                                                )
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                    >

                                        <td class="rank-column">

                                            <?= $intelligenceRank
                                                ?? '—'; ?>

                                        </td>


                                        <td class="player-name-cell">

                                            <div class="player-name-actions">

                                                <?php if (
                                                    isset($player['player_id'])
                                                    &&
                                                    (int) $player['player_id'] > 0
                                                ): ?>

                                                    <a
                                                        href="player.php?id=<?= (int) $player['player_id']; ?>"
                                                        class="player-profile-link"
                                                    >

                                                        <strong>

                                                            <?= htmlspecialchars(
                                                                (string) (
                                                                    $player['name']
                                                                    ?? 'Unknown'
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                        </strong>

                                                    </a>


                                                    <a
                                                        href="compare.php?player1=<?= (int) $player['player_id']; ?>"
                                                        class="player-compare-link"
                                                        aria-label="Compare <?= htmlspecialchars(
                                                            (string) (
                                                                $player['name']
                                                                ?? 'player'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>"
                                                    >
                                                        Compare
                                                    </a>

                                                <?php else: ?>

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $player['name']
                                                                ?? 'Unknown'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </strong>

                                                <?php endif; ?>

                                            </div>

                                        </td>


                                        <td>

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
                                                    '—'
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>


                                        <td>

                                            <span class="position-badge">

                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $player[
                                                            'position'
                                                        ]
                                                        ?? '—'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= displayPrice(
                                                $player['price']
                                                ?? null
                                            ); ?>

                                        </td>
                                        
                                        <td>

                                            <?= htmlspecialchars(
                                                (string) (
                                                    $player[
                                                        'availability_label'
                                                    ]
                                                    ?? 'Unknown'
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>


                                        <td>

                                            <span class="<?= ratingClass(
                                                $player[
                                                    'strength_rating'
                                                ]
                                                ?? null
                                            ); ?>">

                                                <?= displayRating(
                                                    $player[
                                                        'strength_rating'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="<?= ratingClass(
                                                $player[
                                                    'value_rating'
                                                ]
                                                ?? null
                                            ); ?>">

                                                <?= displayRating(
                                                    $player[
                                                        'value_rating'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="<?= ratingClass(
                                                $player[
                                                    'fixture_rating'
                                                ]
                                                ?? null
                                            ); ?>">

                                                <?= displayRating(
                                                    $player[
                                                        'fixture_rating'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </span>

                                        </td>


                                        <td>

                                            <strong class="intelligence-score <?= ratingClass(
                                                $player[
                                                    'intelligence_score'
                                                ]
                                                ?? null
                                            ); ?>">

                                                <?= displayRating(
                                                    $player[
                                                        'intelligence_score'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                (string) (
                                                    $player[
                                                        'intelligence_label'
                                                    ]
                                                    ?? '—'
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                            </tbody>

                        </table>

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
                    Player Explorer
                </span>

            </footer>

        </div>

    </div>


    <script src="assets/js/app.js"></script>

</body>

</html>