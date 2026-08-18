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
        'Unable to initialise Transfer Optimizer.'
    );
}


/*
 * ============================================================
 * ACTIVE NAVIGATION
 * ============================================================
 */

$activeNav =
    'transfer-optimizer';


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

$currentPlayerIdA =
    filter_input(
        INPUT_GET,
        'player_a',
        FILTER_VALIDATE_INT
    );


$currentPlayerIdB =
    filter_input(
        INPUT_GET,
        'player_b',
        FILTER_VALIDATE_INT
    );


$bankInput =
    filter_input(
        INPUT_GET,
        'bank',
        FILTER_VALIDATE_FLOAT
    );


$limitInput =
    filter_input(
        INPUT_GET,
        'limit',
        FILTER_VALIDATE_INT
    );


$bank =
    (
        $bankInput !== false
        &&
        $bankInput !== null
    )
        ? max(
            0.0,
            (float) $bankInput
        )
        : 0.0;


$limit =
    (
        $limitInput !== false
        &&
        $limitInput !== null
        &&
        $limitInput > 0
    )
        ? min(
            25,
            (int) $limitInput
        )
        : 10;


/*
 * ============================================================
 * RUN OPTIMIZER
 * ============================================================
 */

$optimizerResult =
    null;


$optimizerError =
    null;


$hasSelection =
    (
        $currentPlayerIdA !== false
        &&
        $currentPlayerIdA !== null
        &&
        $currentPlayerIdB !== false
        &&
        $currentPlayerIdB !== null
    );


if ($hasSelection) {

    try {

        $optimizerResult =
            $service
                ->optimizeTransferCombination(
                    (int) $currentPlayerIdA,
                    (int) $currentPlayerIdB,
                    $bank,
                    $limit
                );


        if ($optimizerResult === null) {

            $optimizerError =
                'The selected players could not be optimised. '
                . 'Check that two different valid players are selected.';
        }

    } catch (Throwable $exception) {

        $optimizerError =
            'Unable to optimise this transfer combination.';
    }
}


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function optimizerPageDisplayRating(
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


function optimizerPageDisplayScore(
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


function optimizerPageDisplayPrice(
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


function optimizerPageDisplaySigned(
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


    $value =
        (float) $value;


    return (
        $value >= 0
            ? '+'
            : ''
    )
    . number_format(
        $value,
        1
    )
    . $suffix;
}


function optimizerPageDisplayBudget(
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


    $value =
        (float) $value;


    if ($value > 0) {

        return '+£'
            . number_format(
                $value,
                1
            )
            . 'm';
    }


    if ($value < 0) {

        return '-£'
            . number_format(
                abs(
                    $value
                ),
                1
            )
            . 'm';
    }


    return '£0.0m';
}


function optimizerPageClassificationClass(
    ?string $classification
): string {

    return match (
        strtolower(
            trim(
                (string) $classification
            )
        )
    ) {

        'strong improvement' =>
            'optimizer-classification-strong',

        'improvement' =>
            'optimizer-classification-improvement',

        'balanced restructure' =>
            'optimizer-classification-balanced',

        'neutral restructure' =>
            'optimizer-classification-neutral',

        'risky restructure' =>
            'optimizer-classification-risky',

        'downgrade' =>
            'optimizer-classification-downgrade',

        default =>
            'optimizer-classification-neutral'
    };
}


function optimizerPageDecisionClass(
    ?string $decision
): string {

    return match (
        strtolower(
            trim(
                (string) $decision
            )
        )
    ) {

        'upgrade' =>
            'optimizer-decision-upgrade',

        'budget enabler' =>
            'optimizer-decision-budget',

        'strategic sidegrade' =>
            'optimizer-decision-strategic',

        'sidegrade' =>
            'optimizer-decision-sidegrade',

        'risky punt' =>
            'optimizer-decision-risky',

        'downgrade' =>
            'optimizer-decision-downgrade',

        default =>
            'optimizer-decision-neutral'
    };
}


function optimizerPageRenderPlayerOptions(
    array $players,
    mixed $selectedId
): void {

    foreach ($players as $player) {

        $playerId =
            (int) (
                $player[
                    'player_id'
                ]
                ?? 0
            );


        if ($playerId <= 0) {
            continue;
        }


        ?>

        <option
            value="<?= $playerId; ?>"
            <?= (
                (int) $selectedId
                ===
                $playerId
            )
                ? 'selected'
                : ''; ?>
        >
            <?= htmlspecialchars(
                (string) (
                    $player[
                        'name'
                    ]
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

            <?= optimizerPageDisplayPrice(
                $player[
                    'price'
                ]
                ?? null
            ); ?>
        </option>

        <?php
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
        content="Automatically find the best two-player FPL transfer combinations."
    >

    <title>
        Transfer Optimizer | FPL Intelligence
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
                        Automated Transfer Intelligence
                    </p>

                    <h1>
                        Transfer Optimizer
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


            <main class="dashboard">


                <!-- ==========================================
                     OPTIMIZER CONTROLS
                     ========================================== -->

                <section class="dashboard-card transfer-optimizer-form-card">

                    <div class="card-header">

                        <div>

                            <p class="card-kicker">
                                Automatic Restructure Search
                            </p>

                            <h2>
                                Find Best Transfer Combination
                            </h2>

                        </div>

                    </div>


                    <p class="transfer-optimizer-explanation">

                        Select two players you want to sell.
                        FPL Intelligence will search every valid
                        same-position replacement pairing, apply
                        your available bank, evaluate each strategy
                        and rank the strongest affordable combinations.

                    </p>


                    <form
                        method="get"
                        action="transfer-optimizer.php"
                        class="transfer-optimizer-form"
                    >

                        <div class="transfer-optimizer-player-grid">

                            <div class="transfer-optimizer-field">

                                <label for="player_a">
                                    Outgoing Player A
                                </label>

                                <select
                                    name="player_a"
                                    id="player_a"
                                    required
                                >

                                    <option value="">
                                        Select player
                                    </option>

                                    <?php

                                    optimizerPageRenderPlayerOptions(
                                        $players,
                                        $currentPlayerIdA
                                    );

                                    ?>

                                </select>

                            </div>


                            <div class="transfer-optimizer-field">

                                <label for="player_b">
                                    Outgoing Player B
                                </label>

                                <select
                                    name="player_b"
                                    id="player_b"
                                    required
                                >

                                    <option value="">
                                        Select player
                                    </option>

                                    <?php

                                    optimizerPageRenderPlayerOptions(
                                        $players,
                                        $currentPlayerIdB
                                    );

                                    ?>

                                </select>

                            </div>

                        </div>


                        <div class="transfer-optimizer-settings-grid">

                            <div class="transfer-optimizer-field">

                                <label for="bank">
                                    Money in Bank
                                </label>

                                <div class="transfer-optimizer-input-wrap">

                                    <span>
                                        £
                                    </span>

                                    <input
                                        type="number"
                                        id="bank"
                                        name="bank"
                                        min="0"
                                        max="20"
                                        step="0.1"
                                        value="<?= htmlspecialchars(
                                            number_format(
                                                $bank,
                                                1,
                                                '.',
                                                ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                    >

                                    <small>
                                        m
                                    </small>

                                </div>

                            </div>


                            <div class="transfer-optimizer-field">

                                <label for="limit">
                                    Recommendations
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
                                            20,
                                            25
                                        ]
                                        as $limitOption
                                    ): ?>

                                        <option
                                            value="<?= $limitOption; ?>"
                                            <?= $limit === $limitOption
                                                ? 'selected'
                                                : ''; ?>
                                        >
                                            Top <?= $limitOption; ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="transfer-optimizer-submit"
                        >
                            Find Best Transfers
                        </button>

                    </form>


                    <?php if (
                        $optimizerError !== null
                    ): ?>

                        <div class="transfer-error">

                            <?= htmlspecialchars(
                                $optimizerError,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </div>

                    <?php endif; ?>

                </section>


                <?php if (
                    $optimizerResult !== null
                ): ?>

                    <?php

                    $currentA =
                        $optimizerResult[
                            'current_player_a'
                        ]
                        ?? [];


                    $currentB =
                        $optimizerResult[
                            'current_player_b'
                        ]
                        ?? [];


                    $combinations =
                        $optimizerResult[
                            'combinations'
                        ]
                        ?? [];


                    $totalFound =
                        (int) (
                            $optimizerResult[
                                'total_found'
                            ]
                            ?? 0
                        );


                    $returnedCount =
                        (int) (
                            $optimizerResult[
                                'count'
                            ]
                            ?? 0
                        );

                    ?>


                    <!-- ======================================
                         SEARCH SUMMARY
                         ====================================== -->

                    <section class="dashboard-card transfer-optimizer-summary-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Optimisation Complete
                                </p>

                                <h2>
                                    Best Transfer Strategies
                                </h2>

                            </div>

                        </div>


                        <div class="transfer-optimizer-summary-grid">

                            <div>

                                <span>
                                    Selling
                                </span>

                                <strong>

                                    <?= htmlspecialchars(
                                        (string) (
                                            $currentA[
                                                'name'
                                            ]
                                            ?? 'Unknown'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                    +

                                    <?= htmlspecialchars(
                                        (string) (
                                            $currentB[
                                                'name'
                                            ]
                                            ?? 'Unknown'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>

                            </div>


                            <div>

                                <span>
                                    Combined Sale Value
                                </span>

                                <strong>

                                    <?= optimizerPageDisplayPrice(
                                        (
                                            (float) (
                                                $currentA[
                                                    'price'
                                                ]
                                                ?? 0
                                            )
                                            +
                                            (float) (
                                                $currentB[
                                                    'price'
                                                ]
                                                ?? 0
                                            )
                                        )
                                    ); ?>

                                </strong>

                            </div>


                            <div>

                                <span>
                                    Money in Bank
                                </span>

                                <strong>

                                    <?= optimizerPageDisplayPrice(
                                        $optimizerResult[
                                            'bank'
                                        ]
                                        ?? 0
                                    ); ?>

                                </strong>

                            </div>


                            <div>

                                <span>
                                    Affordable Strategies
                                </span>

                                <strong>
                                    <?= number_format(
                                        $totalFound
                                    ); ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    Recommendations Shown
                                </span>

                                <strong>
                                    <?= $returnedCount; ?>
                                </strong>

                            </div>

                        </div>

                    </section>


                    <?php if (
                        empty(
                            $combinations
                        )
                    ): ?>

                        <section class="dashboard-card">

                            <p class="transfer-optimizer-empty">

                                No affordable transfer combinations
                                were found for this selection.

                            </p>

                        </section>

                    <?php else: ?>


                        <!-- ==================================
                             RANKED COMBINATIONS
                             ================================== -->

                        <section class="transfer-optimizer-results">

                            <?php foreach (
                                $combinations
                                as $combination
                            ): ?>

                                <?php

                                $rank =
                                    (int) (
                                        $combination[
                                            'optimizer'
                                        ]['rank']
                                        ?? 0
                                    );


                                $transferA =
                                    $combination[
                                        'transfer_a'
                                    ]
                                    ?? [];


                                $transferB =
                                    $combination[
                                        'transfer_b'
                                    ]
                                    ?? [];


                                $replacementA =
                                    $transferA[
                                        'replacement'
                                    ]
                                    ?? [];


                                $replacementB =
                                    $transferB[
                                        'replacement'
                                    ]
                                    ?? [];


                                $classification =
                                    (string) (
                                        $combination[
                                            'classification'
                                        ]
                                        ?? 'Unknown'
                                    );


                                $combinationScore =
                                    $combination[
                                        'combination_score'
                                    ]
                                    ?? null;


                                $movements =
                                    $combination[
                                        'combined_movements'
                                    ]
                                    ?? [];


                                $budgetAfter =
                                    $combination[
                                        'optimizer'
                                    ]['budget_after']
                                    ?? null;


                                $currentAId =
                                    (int) (
                                        $transferA[
                                            'current_player'
                                        ]['player_id']
                                        ?? 0
                                    );


                                $replacementAId =
                                    (int) (
                                        $replacementA[
                                            'player_id'
                                        ]
                                        ?? 0
                                    );


                                $currentBId =
                                    (int) (
                                        $transferB[
                                            'current_player'
                                        ]['player_id']
                                        ?? 0
                                    );


                                $replacementBId =
                                    (int) (
                                        $replacementB[
                                            'player_id'
                                        ]
                                        ?? 0
                                    );

                                ?>

                                <article class="dashboard-card transfer-optimizer-result-card">

                                    <div class="transfer-optimizer-result-header">

                                        <div class="transfer-optimizer-rank">

                                            #<?= $rank; ?>

                                        </div>


                                        <div class="transfer-optimizer-result-title">

                                            <p class="card-kicker">
                                                Recommended Strategy
                                            </p>

                                            <h2>

                                                <?= htmlspecialchars(
                                                    $classification,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </h2>

                                        </div>


                                        <div class="transfer-optimizer-result-badges">

                                            <span class="transfer-optimizer-classification <?= optimizerPageClassificationClass(
                                                $classification
                                            ); ?>">

                                                <?= htmlspecialchars(
                                                    $classification,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </span>


                                            <span class="transfer-optimizer-score">

                                                Score

                                                <strong>

                                                    <?= optimizerPageDisplayScore(
                                                        $combinationScore
                                                    ); ?>

                                                </strong>

                                            </span>

                                        </div>

                                    </div>


                                    <div class="transfer-optimizer-transfer-grid">


                                        <!-- ==================
                                             TRANSFER A
                                             ================== -->

                                        <div class="transfer-optimizer-transfer">

                                            <div class="transfer-optimizer-transfer-top">

                                                <span>
                                                    Transfer A
                                                </span>

                                                <span class="transfer-optimizer-decision <?= optimizerPageDecisionClass(
                                                    $transferA[
                                                        'decision_type'
                                                    ]
                                                    ?? null
                                                ); ?>">

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $transferA[
                                                                'decision_type'
                                                            ]
                                                            ?? 'Unknown'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                    <strong>

                                                        Score

                                                        <?= optimizerPageDisplayScore(
                                                            $transferA[
                                                                'decision_score'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                    </strong>

                                                </span>

                                            </div>


                                            <div class="transfer-optimizer-player-change">

                                                <div>

                                                    <small>
                                                        Outgoing
                                                    </small>

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $transferA[
                                                                    'current_player'
                                                                ]['name']
                                                                ?? 'Unknown'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </strong>

                                                    <span>

                                                        <?= optimizerPageDisplayPrice(
                                                            $transferA[
                                                                'current_player'
                                                            ]['price']
                                                            ?? null
                                                        ); ?>

                                                        · INT

                                                        <?= optimizerPageDisplayRating(
                                                            $transferA[
                                                                'current_player'
                                                            ]['intelligence_score']
                                                            ?? null
                                                        ); ?>

                                                    </span>

                                                </div>


                                                <div class="transfer-optimizer-arrow">
                                                    →
                                                </div>


                                                <div>

                                                    <small>
                                                        Incoming
                                                    </small>

                                                    <strong>

                                                        <a
                                                            href="player.php?id=<?= $replacementAId; ?>"
                                                        >

                                                            <?= htmlspecialchars(
                                                                (string) (
                                                                    $replacementA[
                                                                        'name'
                                                                    ]
                                                                    ?? 'Unknown'
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                        </a>

                                                    </strong>

                                                    <span>

                                                        <?= optimizerPageDisplayPrice(
                                                            $replacementA[
                                                                'price'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                        · INT

                                                        <?= optimizerPageDisplayRating(
                                                            $replacementA[
                                                                'intelligence_score'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                    </span>

                                                </div>

                                            </div>

                                        </div>


                                        <!-- ==================
                                             TRANSFER B
                                             ================== -->

                                        <div class="transfer-optimizer-transfer">

                                            <div class="transfer-optimizer-transfer-top">

                                                <span>
                                                    Transfer B
                                                </span>

                                                <span class="transfer-optimizer-decision <?= optimizerPageDecisionClass(
                                                    $transferB[
                                                        'decision_type'
                                                    ]
                                                    ?? null
                                                ); ?>">

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $transferB[
                                                                'decision_type'
                                                            ]
                                                            ?? 'Unknown'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                    <strong>

                                                        Score

                                                        <?= optimizerPageDisplayScore(
                                                            $transferB[
                                                                'decision_score'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                    </strong>

                                                </span>

                                            </div>


                                            <div class="transfer-optimizer-player-change">

                                                <div>

                                                    <small>
                                                        Outgoing
                                                    </small>

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $transferB[
                                                                    'current_player'
                                                                ]['name']
                                                                ?? 'Unknown'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </strong>

                                                    <span>

                                                        <?= optimizerPageDisplayPrice(
                                                            $transferB[
                                                                'current_player'
                                                            ]['price']
                                                            ?? null
                                                        ); ?>

                                                        · INT

                                                        <?= optimizerPageDisplayRating(
                                                            $transferB[
                                                                'current_player'
                                                            ]['intelligence_score']
                                                            ?? null
                                                        ); ?>

                                                    </span>

                                                </div>


                                                <div class="transfer-optimizer-arrow">
                                                    →
                                                </div>


                                                <div>

                                                    <small>
                                                        Incoming
                                                    </small>

                                                    <strong>

                                                        <a
                                                            href="player.php?id=<?= $replacementBId; ?>"
                                                        >

                                                            <?= htmlspecialchars(
                                                                (string) (
                                                                    $replacementB[
                                                                        'name'
                                                                    ]
                                                                    ?? 'Unknown'
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                        </a>

                                                    </strong>

                                                    <span>

                                                        <?= optimizerPageDisplayPrice(
                                                            $replacementB[
                                                                'price'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                        · INT

                                                        <?= optimizerPageDisplayRating(
                                                            $replacementB[
                                                                'intelligence_score'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                    </span>

                                                </div>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="transfer-optimizer-movement-grid">

                                        <div>

                                            <span>
                                                Intelligence
                                            </span>

                                            <strong>

                                                <?= optimizerPageDisplaySigned(
                                                    $movements[
                                                        'intelligence'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Strength
                                            </span>

                                            <strong>

                                                <?= optimizerPageDisplaySigned(
                                                    $movements[
                                                        'strength'
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

                                                <?= optimizerPageDisplaySigned(
                                                    $movements[
                                                        'value'
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

                                                <?= optimizerPageDisplaySigned(
                                                    $movements[
                                                        'fixtures'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Confidence
                                            </span>

                                            <strong>

                                                <?= optimizerPageDisplaySigned(
                                                    $movements[
                                                        'sample_confidence'
                                                    ]
                                                    ?? null,
                                                    'pp'
                                                ); ?>

                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Budget Remaining
                                            </span>

                                            <strong>

                                                <?= optimizerPageDisplayBudget(
                                                    $budgetAfter
                                                ); ?>

                                            </strong>

                                        </div>

                                    </div>


                                    <div class="transfer-optimizer-actions">

                                        <?php if (
                                            $currentAId > 0
                                            &&
                                            $replacementAId > 0
                                            &&
                                            $currentBId > 0
                                            &&
                                            $replacementBId > 0
                                        ): ?>

                                            <a
                                                href="transfer-planner.php?current_a=<?= $currentAId; ?>&replacement_a=<?= $replacementAId; ?>&current_b=<?= $currentBId; ?>&replacement_b=<?= $replacementBId; ?>"
                                                class="transfer-primary-action"
                                            >
                                                View Combination
                                            </a>


                                            <a
                                                href="compare.php?player1=<?= $currentAId; ?>&player2=<?= $replacementAId; ?>"
                                                class="transfer-secondary-action"
                                            >
                                                Compare Transfer A
                                            </a>


                                            <a
                                                href="compare.php?player1=<?= $currentBId; ?>&player2=<?= $replacementBId; ?>"
                                                class="transfer-secondary-action"
                                            >
                                                Compare Transfer B
                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </section>

                    <?php endif; ?>

                <?php endif; ?>


            </main>


            <footer class="footer">

                <span>
                    FPL Intelligence
                </span>

                <span>
                    Transfer Optimizer
                </span>

            </footer>


        </div>

    </div>


    <script src="assets/js/app.js"></script>

</body>

</html>