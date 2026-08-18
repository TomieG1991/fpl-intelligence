<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

try {

    $database =
        new Database();


    $service =
        new PlayerIntelligenceService(
            $database->getConnection()
        );


    $players =
        $service
            ->getAllPlayerSummaries();

} catch (Throwable $exception) {

    http_response_code(500);

    die(
        'Unable to initialise Transfer Intelligence.'
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
 * REQUEST VALUES
 * ============================================================
 */

$playerId =
    filter_input(
        INPUT_GET,
        'player',
        FILTER_VALIDATE_INT
    );


$maxPriceRaw =
    filter_input(
        INPUT_GET,
        'max_price',
        FILTER_VALIDATE_FLOAT
    );


$limitRaw =
    filter_input(
        INPUT_GET,
        'limit',
        FILTER_VALIDATE_INT
    );


$maxPrice =
    (
        $maxPriceRaw !== false
        &&
        $maxPriceRaw !== null
    )
        ? (float) $maxPriceRaw
        : null;


$limit =
    (
        $limitRaw !== false
        &&
        $limitRaw !== null
    )
        ? max(
            1,
            min(
                20,
                (int) $limitRaw
            )
        )
        : 10;


/*
 * ============================================================
 * DEFAULT BUDGET FROM SELECTED PLAYER
 * ============================================================
 */

$selectedPlayerSummary =
    null;


if (
    $playerId !== false
    &&
    $playerId !== null
) {

    foreach ($players as $player) {

        if (
            (int) (
                $player['player_id']
                ?? 0
            )
            ===
            (int) $playerId
        ) {

            $selectedPlayerSummary =
                $player;

            break;
        }
    }


    if (
        $maxPrice === null
        &&
        $selectedPlayerSummary !== null
        &&
        isset(
            $selectedPlayerSummary[
                'price'
            ]
        )
        &&
        is_numeric(
            $selectedPlayerSummary[
                'price'
            ]
        )
    ) {

        $maxPrice =
            (float) $selectedPlayerSummary[
                'price'
            ];
    }
}


/*
 * ============================================================
 * RUN REPLACEMENT SEARCH
 * ============================================================
 */

$replacementResult =
    null;


$transferError =
    null;


if (
    $playerId !== false
    &&
    $playerId !== null
    &&
    $maxPrice !== null
) {

    if ($maxPrice < 0) {

        $transferError =
            'Maximum replacement price must be zero or higher.';

    } else {

        try {

            $replacementResult =
                $service
                    ->findPlayerReplacements(
                        (int) $playerId,
                        (float) $maxPrice,
                        $limit
                    );


            if ($replacementResult === null) {

                $transferError =
                    'Unable to find replacement candidates for the selected player.';
            }

        } catch (Throwable $exception) {

            $transferError =
                'Unable to complete the replacement search.';
        }
    }
}


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function transferDisplayRating(
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


function transferDisplayPrice(
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


function transferDisplaySigned(
    mixed $value,
    string $suffix = ''
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


    $number =
        (float) $value;


    return (
        $number >= 0
            ? '+'
            : ''
    )
    . number_format(
        $number,
        1
    )
    . $suffix;
}


function transferTypeClass(
    ?string $type
): string {

    return match (
        strtolower(
            (string) $type
        )
    ) {

        'upgrade' =>
            'transfer-type-upgrade',

        'sidegrade' =>
            'transfer-type-sidegrade',

        'downgrade' =>
            'transfer-type-downgrade',

        default =>
            'transfer-type-neutral'
    };
}

function transferDecisionClass(
    ?string $decisionType
): string {

    return match (
        strtolower(
            (string) $decisionType
        )
    ) {

        'upgrade' =>
            'transfer-decision-upgrade',

        'budget enabler' =>
            'transfer-decision-budget',

        'strategic sidegrade' =>
            'transfer-decision-strategic',

        'sidegrade' =>
            'transfer-decision-sidegrade',

        'risky punt' =>
            'transfer-decision-risky',

        'downgrade' =>
            'transfer-decision-downgrade',

        default =>
            'transfer-decision-neutral'
    };
}


function transferVerdictClass(
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
        return 'transfer-verdict-excellent';
    }


    if (
        str_contains(
            $verdict,
            'strong'
        )
    ) {
        return 'transfer-verdict-strong';
    }


    if (
        str_contains(
            $verdict,
            'consider'
        )
    ) {
        return 'transfer-verdict-consider';
    }


    if (
        str_contains(
            $verdict,
            'watchlist'
        )
    ) {
        return 'transfer-verdict-watchlist';
    }


    if (
        str_contains(
            $verdict,
            'avoid'
        )
    ) {
        return 'transfer-verdict-avoid';
    }


    return 'transfer-verdict-neutral';
}


function transferConfidenceLabel(
    mixed $confidence
): string {

    if (
        $confidence === null
        ||
        !is_numeric(
            $confidence
        )
    ) {

        return 'Unknown';
    }


    $confidence =
        (float) $confidence;


    if ($confidence >= 1) {
        return 'Full';
    }


    if ($confidence >= 0.75) {
        return 'High';
    }


    if ($confidence >= 0.50) {
        return 'Moderate';
    }


    if ($confidence >= 0.25) {
        return 'Low';
    }


    return 'Very Low';
}
$activeNav = 'transfers';
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
        content="Find FPL replacement candidates using FPL Intelligence."
    >

    <title>
        Transfer Intelligence | FPL Intelligence
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
                        Decision Support
                    </p>

                    <h1>
                        Transfer Intelligence
                    </h1>

                </div>


                <div class="topbar-actions">

                    <a
                        href="transfer-planner.php"
                        class="profile-compare-link"
                    >
                        ⤢ Transfer Planner
                    </a>

                </div>

            </header>


            <!-- ==============================================
                 MAIN
                 ============================================== -->

            <main class="dashboard">


                <!-- ==========================================
                     SEARCH CONTROLS
                     ========================================== -->

                <section class="dashboard-card transfer-search-card">

                    <div class="card-header">

                        <div>

                            <p class="card-kicker">
                                Replacement Finder
                            </p>

                            <h2>
                                Find Player Replacements
                            </h2>

                        </div>

                    </div>


                    <p class="transfer-search-explanation">

                        Select the player you want to sell and set
                        the maximum replacement price. Candidates
                        are filtered to the same position and ranked
                        by Player Intelligence.

                    </p>


                    <form
                        method="get"
                        action="transfers.php"
                        class="transfer-search-form"
                    >

                        <div class="transfer-search-field">

                            <label for="player">
                                Player to Sell
                            </label>

                            <select
                                name="player"
                                id="player"
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
                                            (int) $playerId
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
                                                    'position'
                                                ]
                                                ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        —
                                        <?= transferDisplayPrice(
                                            $player[
                                                'price'
                                            ]
                                            ?? null
                                        ); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="transfer-search-field">

                            <label for="max_price">
                                Max Replacement Price
                            </label>

                            <input
                                type="number"
                                name="max_price"
                                id="max_price"
                                min="0"
                                max="20"
                                step="0.1"
                                value="<?= htmlspecialchars(
                                    $maxPrice !== null
                                        ? number_format(
                                            $maxPrice,
                                            1,
                                            '.',
                                            ''
                                        )
                                        : '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                placeholder="e.g. 8.5"
                                required
                            >

                        </div>


                        <div class="transfer-search-field">

                            <label for="limit">
                                Results
                            </label>

                            <select
                                name="limit"
                                id="limit"
                            >

                                <?php foreach (
                                    [
                                        5,
                                        10,
                                        15,
                                        20
                                    ]
                                    as $resultLimit
                                ): ?>

                                    <option
                                        value="<?= $resultLimit; ?>"
                                        <?= (
                                            $limit
                                            ===
                                            $resultLimit
                                        )
                                            ? 'selected'
                                            : ''; ?>
                                    >
                                        <?= $resultLimit; ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <button
                            type="submit"
                            class="transfer-search-submit"
                        >
                            Find Replacements
                        </button>

                    </form>


                    <?php if (
                        $transferError !== null
                    ): ?>

                        <div class="transfer-error">

                            <?= htmlspecialchars(
                                $transferError,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </div>

                    <?php endif; ?>

                </section>


                <?php if (
                    $replacementResult !== null
                ): ?>


                    <?php

                    $currentPlayer =
                        $replacementResult[
                            'current_player'
                        ]
                        ?? [];


                    $replacements =
                        $replacementResult[
                            'replacements'
                        ]
                        ?? [];

                    ?>


                    <!-- ======================================
                         CURRENT PLAYER
                         ====================================== -->

                    <section class="dashboard-card transfer-current-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Selling
                                </p>

                                <h2>

                                    <?= htmlspecialchars(
                                        (string) (
                                            $currentPlayer[
                                                'name'
                                            ]
                                            ?? 'Unknown Player'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </h2>

                            </div>


                            <span class="transfer-current-price">

                                <?= transferDisplayPrice(
                                    $currentPlayer[
                                        'price'
                                    ]
                                    ?? null
                                ); ?>

                            </span>

                        </div>


                        <div class="transfer-current-grid">

                            <div class="transfer-current-stat">

                                <span>
                                    Position
                                </span>

                                <strong>

                                    <?= htmlspecialchars(
                                        (string) (
                                            $currentPlayer[
                                                'position'
                                            ]
                                            ?? '—'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>

                            </div>


                            <div class="transfer-current-stat">

                                <span>
                                    Intelligence
                                </span>

                                <strong>

                                    <?= transferDisplayRating(
                                        $currentPlayer[
                                            'intelligence_score'
                                        ]
                                        ?? null
                                    ); ?>

                                </strong>

                            </div>


                            <div class="transfer-current-stat">

                                <span>
                                    Strength
                                </span>

                                <strong>

                                    <?= transferDisplayRating(
                                        $currentPlayer[
                                            'strength_rating'
                                        ]
                                        ?? null
                                    ); ?>

                                </strong>

                            </div>


                            <div class="transfer-current-stat">

                                <span>
                                    Value
                                </span>

                                <strong>

                                    <?= transferDisplayRating(
                                        $currentPlayer[
                                            'value_rating'
                                        ]
                                        ?? null
                                    ); ?>

                                </strong>

                            </div>


                            <div class="transfer-current-stat">

                                <span>
                                    Fixtures
                                </span>

                                <strong>

                                    <?= transferDisplayRating(
                                        $currentPlayer[
                                            'fixture_rating'
                                        ]
                                        ?? null
                                    ); ?>

                                </strong>

                            </div>


                            <div class="transfer-current-stat">

                                <span>
                                    Max Replacement
                                </span>

                                <strong>

                                    <?= transferDisplayPrice(
                                        $replacementResult[
                                            'max_price'
                                        ]
                                        ?? null
                                    ); ?>

                                </strong>

                            </div>

                        </div>


                        <div class="transfer-current-footer">

                            <span>
                                Assessment
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    (string) (
                                        $currentPlayer[
                                            'verdict'
                                        ]
                                        ?? 'Unknown'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </strong>

                        </div>

                    </section>
                    
                    <?php

                    $recommendations =
                        $replacementResult[
                            'recommendations'
                        ]
                        ?? [];


                    $recommendationCategories = [

                        'best_overall' => [
                            'label' => 'Best Overall',
                            'description' => 'Highest overall Player Intelligence.'
                        ],

                        'best_value' => [
                            'label' => 'Best Value',
                            'description' => 'Best value among sufficiently proven candidates.'
                        ],

                        'best_fixtures' => [
                            'label' => 'Best Fixtures',
                            'description' => 'Strongest upcoming fixture opportunity.'
                        ],

                        'safest_pick' => [
                            'label' => 'Safest Pick',
                            'description' => 'Best balance of sample confidence, availability and Intelligence.'
                        ],

                        'high_upside' => [
                            'label' => 'High Upside',
                            'description' => 'Promising candidate with upside and a less-established sample.'
                        ]
                    ];

                    ?>


                    <!-- ======================================
                         RECOMMENDATION INTELLIGENCE
                         ====================================== -->

                    <section class="dashboard-card transfer-recommendation-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Recommendation Intelligence
                                </p>

                                <h2>
                                    Best Replacement Profiles
                                </h2>

                            </div>

                        </div>


                        <p class="transfer-recommendation-explanation">

                            These recommendations interpret the ranked replacement
                            list in different ways. Best Overall follows Player
                            Intelligence, while the other categories highlight
                            value, fixtures, safety and upside.

                        </p>


                        <div class="transfer-recommendation-grid">

                            <?php foreach (
                                $recommendationCategories
                                as $recommendationKey => $category
                            ): ?>

                                <?php

                                $recommendation =
                                    $recommendations[
                                        $recommendationKey
                                    ]
                                    ?? null;

                                ?>


                                <article class="transfer-recommendation-item">

                                    <div class="transfer-recommendation-heading">

                                        <span class="transfer-recommendation-label">

                                            <?= htmlspecialchars(
                                                $category['label'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </div>


                                    <?php if (
                                        $recommendation === null
                                    ): ?>

                                        <div class="transfer-recommendation-empty">

                                            <strong>
                                                No suitable candidate
                                            </strong>

                                            <p>

                                                <?= htmlspecialchars(
                                                    $category['description'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </p>

                                        </div>

                                    <?php else: ?>

                                        <?php

                                        $recommendationId =
                                            (int) (
                                                $recommendation[
                                                    'player_id'
                                                ]
                                                ?? 0
                                            );

                                        ?>

                                        <h3>

                                            <?php if (
                                                $recommendationId > 0
                                            ): ?>

                                                <a
                                                    href="player.php?id=<?= $recommendationId; ?>"
                                                >

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $recommendation[
                                                                'name'
                                                            ]
                                                            ?? 'Unknown'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </a>

                                            <?php else: ?>

                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $recommendation[
                                                            'name'
                                                        ]
                                                        ?? 'Unknown'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            <?php endif; ?>

                                        </h3>


                                        <div class="transfer-recommendation-meta">

                                            <span>

                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $recommendation[
                                                            'team_name'
                                                        ]
                                                        ?? 'Unknown'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </span>


                                            <span>

                                                <?= transferDisplayPrice(
                                                    $recommendation[
                                                        'price'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </span>

                                        </div>


                                        <div class="transfer-recommendation-intelligence">

                                            <span>
                                                Intelligence
                                            </span>

                                            <strong>

                                                <?= transferDisplayRating(
                                                    $recommendation[
                                                        'intelligence_score'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </strong>

                                        </div>


                                        <div class="transfer-recommendation-details">

                                            <div>

                                                <span>
                                                    Value
                                                </span>

                                                <strong>

                                                    <?= transferDisplayRating(
                                                        $recommendation[
                                                            'value_rating'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                </strong>

                                            </div>


                                            <div>

                                                <span>
                                                    Fixtures
                                                </span>

                                                <strong>

                                                    <?= transferDisplayRating(
                                                        $recommendation[
                                                            'fixture_rating'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                </strong>

                                            </div>


                                            <div>

                                                <span>
                                                    Sample
                                                </span>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        transferConfidenceLabel(
                                                            $recommendation[
                                                                'sample_confidence'
                                                            ]
                                                            ?? null
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </strong>

                                            </div>

                                        </div>


                                        <span class="transfer-verdict-badge <?= transferVerdictClass(
                                            $recommendation[
                                                'verdict'
                                            ]
                                            ?? null
                                        ); ?>">

                                            <?= htmlspecialchars(
                                                (string) (
                                                    $recommendation[
                                                        'verdict'
                                                    ]
                                                    ?? 'Unknown'
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>


                                        <p class="transfer-recommendation-description">

                                            <?= htmlspecialchars(
                                                $category['description'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </p>


                                        <?php if (
                                            $recommendationId > 0
                                        ): ?>

                                            <div class="transfer-recommendation-actions">

                                                <a
                                                    href="player.php?id=<?= $recommendationId; ?>"
                                                    class="transfer-secondary-action"
                                                >
                                                    View Profile
                                                </a>


                                                <a
                                                    href="compare.php?player1=<?= (int) (
                                                        $currentPlayer[
                                                            'player_id'
                                                        ]
                                                        ?? 0
                                                    ); ?>&player2=<?= $recommendationId; ?>"
                                                    class="transfer-primary-action"
                                                >
                                                    Compare
                                                </a>

                                            </div>

                                        <?php endif; ?>

                                    <?php endif; ?>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    </section>


                    <!-- ======================================
                         REPLACEMENT RESULTS
                         ====================================== -->

                    <section class="dashboard-card transfer-results-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Recommended Candidates
                                </p>

                                <h2>
                                    Replacement Rankings
                                </h2>

                            </div>


                            <span class="card-badge">

                                <?= (int) (
                                    $replacementResult[
                                        'replacement_count'
                                    ]
                                    ?? 0
                                ); ?>

                                Candidates

                            </span>

                        </div>


                        <?php if (
                            empty(
                                $replacements
                            )
                        ): ?>

                            <div class="transfer-empty">

                                No eligible replacement candidates
                                were found within the selected budget.

                            </div>

                        <?php else: ?>

                            <div class="transfer-result-list">


                                <?php foreach (
                                    $replacements
                                    as $index => $replacement
                                ): ?>

                                    <?php

                                        $replacementType =
                                            (string) (
                                                $replacement[
                                                    'replacement_type'
                                                ]
                                                ?? 'Unknown'
                                            );


                                        $replacementId =
                                            (int) (
                                                $replacement[
                                                    'player_id'
                                                ]
                                                ?? 0
                                            );


                                        $transferDecision =
                                            $replacement[
                                                'transfer_decision'
                                            ]
                                            ?? [];


                                        $decisionType =
                                            (string) (
                                                $transferDecision[
                                                    'decision_type'
                                                ]
                                                ?? 'Unknown'
                                            );


                                        $decisionScore =
                                            $transferDecision[
                                                'decision_score'
                                            ]
                                            ?? null;


                                        $decisionMovements =
                                            $transferDecision[
                                                'movements'
                                            ]
                                            ?? [];

                                        ?>

                                    <article class="transfer-result-card">

                                        <div class="transfer-result-rank">

                                            <?= $index + 1; ?>

                                        </div>


                                        <div class="transfer-result-main">

                                            <div class="transfer-result-heading">

                                                <div>

                                                    <h3>

                                                        <?php if (
                                                            $replacementId > 0
                                                        ): ?>

                                                            <a
                                                                href="player.php?id=<?= $replacementId; ?>"
                                                            >

                                                                <?= htmlspecialchars(
                                                                    (string) (
                                                                        $replacement[
                                                                            'name'
                                                                        ]
                                                                        ?? 'Unknown'
                                                                    ),
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ); ?>

                                                            </a>

                                                        <?php else: ?>

                                                            <?= htmlspecialchars(
                                                                (string) (
                                                                    $replacement[
                                                                        'name'
                                                                    ]
                                                                    ?? 'Unknown'
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                        <?php endif; ?>

                                                    </h3>


                                                    <div class="transfer-result-meta">

                                                        <span>

                                                            <?= htmlspecialchars(
                                                                (string) (
                                                                    $replacement[
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
                                                                    $replacement[
                                                                        'position'
                                                                    ]
                                                                    ?? '—'
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                        </span>


                                                        <span>

                                                            <?= transferDisplayPrice(
                                                                $replacement[
                                                                    'price'
                                                                ]
                                                                ?? null
                                                            ); ?>

                                                        </span>

                                                    </div>

                                                </div>


                                                <div class="transfer-result-badges">

                                                    <span class="transfer-decision-badge <?= transferDecisionClass(
                                                        $decisionType
                                                    ); ?>">

                                                        <?= htmlspecialchars(
                                                            $decisionType,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                        <?php if (
                                                            $decisionScore !== null
                                                            &&
                                                            is_numeric(
                                                                $decisionScore
                                                            )
                                                        ): ?>

                                                            <strong>
                                                                Score <?= number_format(
                                                                    (float) $decisionScore,
                                                                    1
                                                                ); ?>
                                                            </strong>

                                                        <?php endif; ?>

                                                    </span>

                                                    <span class="transfer-verdict-badge <?= transferVerdictClass(
                                                        $replacement[
                                                            'verdict'
                                                        ]
                                                        ?? null
                                                    ); ?>">

                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $replacement[
                                                                    'verdict'
                                                                ]
                                                                ?? 'Unknown'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </span>

                                                </div>

                                            </div>


                                            <div class="transfer-result-intelligence">

                                                <div>

                                                    <span>
                                                        Intelligence
                                                    </span>

                                                    <strong>

                                                        <?= transferDisplayRating(
                                                            $replacement[
                                                                'intelligence_score'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                    </strong>

                                                </div>


                                                <div>

                                                    <span>
                                                        INT Movement
                                                    </span>

                                                    <strong>

                                                        <?= transferDisplaySigned(
                                                            $decisionMovements[
                                                                'intelligence'
                                                            ]
                                                            ??
                                                            $replacement[
                                                                'intelligence_gain'
                                                            ]
                                                            ??
                                                            null
                                                        ); ?>

                                                    </strong>

                                                </div>


                                                <div>

                                                    <span>
                                                        Budget Released
                                                    </span>

                                                    <strong>

                                                        <?php

                                                        $budgetMovement =
                                                            $decisionMovements[
                                                                'budget'
                                                            ]
                                                            ?? null;

                                                        ?>

                                                        <?php if (
                                                            $budgetMovement !== null
                                                            &&
                                                            is_numeric(
                                                                $budgetMovement
                                                            )
                                                        ): ?>

                                                            <?php if (
                                                                (float) $budgetMovement > 0
                                                            ): ?>

                                                                +£<?= number_format(
                                                                    (float) $budgetMovement,
                                                                    1
                                                                ); ?>m

                                                            <?php elseif (
                                                                (float) $budgetMovement < 0
                                                            ): ?>

                                                                -£<?= number_format(
                                                                    abs(
                                                                        (float) $budgetMovement
                                                                    ),
                                                                    1
                                                                ); ?>m

                                                            <?php else: ?>

                                                                £0.0m

                                                            <?php endif; ?>

                                                        <?php else: ?>

                                                            —

                                                        <?php endif; ?>

                                                    </strong>

                                                </div>

                                            </div>


                                            <div class="transfer-result-metrics">

                                                <div>

                                                    <span>
                                                        Strength
                                                    </span>

                                                    <strong>

                                                        <?= transferDisplayRating(
                                                            $replacement[
                                                                'strength_rating'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                    </strong>

                                                </div>


                                                <div>

                                                    <span>
                                                        Value
                                                    </span>

                                                    <strong>

                                                        <?= transferDisplayRating(
                                                            $replacement[
                                                                'value_rating'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                    </strong>

                                                </div>


                                                <div>

                                                    <span>
                                                        Fixtures
                                                    </span>

                                                    <strong>

                                                        <?= transferDisplayRating(
                                                            $replacement[
                                                                'fixture_rating'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                    </strong>

                                                </div>


                                                <div>

                                                    <span>
                                                        Availability
                                                    </span>

                                                    <strong>

                                                        <?= transferDisplayRating(
                                                            $replacement[
                                                                'availability_rating'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                    </strong>

                                                </div>


                                                <div>

                                                    <span>
                                                        Sample
                                                    </span>

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            transferConfidenceLabel(
                                                                $replacement[
                                                                    'sample_confidence'
                                                                ]
                                                                ?? null
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </strong>

                                                </div>

                                            </div>


                                            <p class="transfer-result-summary">

                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $transferDecision[
                                                            'summary'
                                                        ]
                                                        ??
                                                        $replacement[
                                                            'replacement_summary'
                                                        ]
                                                        ??
                                                        ''
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </p>


                                            <div class="transfer-result-actions">

                                                <?php if (
                                                    $replacementId > 0
                                                ): ?>

                                                    <a
                                                        href="player.php?id=<?= $replacementId; ?>"
                                                        class="transfer-secondary-action"
                                                    >
                                                        View Profile
                                                    </a>


                                                    <a
                                                        href="compare.php?player1=<?= (int) (
                                                            $currentPlayer[
                                                                'player_id'
                                                            ]
                                                            ?? 0
                                                        ); ?>&player2=<?= $replacementId; ?>"
                                                        class="transfer-primary-action"
                                                    >
                                                        Compare
                                                    </a>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </article>

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
                    Transfer Intelligence
                </span>

            </footer>


        </div>

    </div>


    <script src="assets/js/app.js"></script>

</body>

</html>