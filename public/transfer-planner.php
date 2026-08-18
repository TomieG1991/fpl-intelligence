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
        'Unable to initialise Transfer Planner.'
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

$currentPlayerIdA =
    filter_input(
        INPUT_GET,
        'current_a',
        FILTER_VALIDATE_INT
    );


$replacementPlayerIdA =
    filter_input(
        INPUT_GET,
        'replacement_a',
        FILTER_VALIDATE_INT
    );


$currentPlayerIdB =
    filter_input(
        INPUT_GET,
        'current_b',
        FILTER_VALIDATE_INT
    );


$replacementPlayerIdB =
    filter_input(
        INPUT_GET,
        'replacement_b',
        FILTER_VALIDATE_INT
    );


/*
 * ============================================================
 * RUN COMBINATION
 * ============================================================
 */

$combinationResult =
    null;


$plannerError =
    null;


$allPlayersSelected =
    (
        $currentPlayerIdA !== false
        &&
        $currentPlayerIdA !== null
        &&
        $replacementPlayerIdA !== false
        &&
        $replacementPlayerIdA !== null
        &&
        $currentPlayerIdB !== false
        &&
        $currentPlayerIdB !== null
        &&
        $replacementPlayerIdB !== false
        &&
        $replacementPlayerIdB !== null
    );


if ($allPlayersSelected) {

    try {

        $combinationResult =
            $service
                ->evaluateTransferCombination(
                    (int) $currentPlayerIdA,
                    (int) $replacementPlayerIdA,
                    (int) $currentPlayerIdB,
                    (int) $replacementPlayerIdB
                );


        if ($combinationResult === null) {

            $plannerError =
                'The selected transfer combination could not be evaluated. '
                . 'Check that each transfer keeps the same position and that '
                . 'the same player is not being used twice.';
        }

    } catch (Throwable $exception) {

        $plannerError =
            'Unable to evaluate this transfer combination.';
    }
}


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function plannerDisplayRating(
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


function plannerDisplayScore(
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


function plannerDisplayPrice(
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


function plannerDisplaySigned(
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


function plannerDisplayBudget(
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


function plannerDecisionClass(
    ?string $decision
): string {

    return match (
        strtolower(
            (string) $decision
        )
    ) {

        'upgrade' =>
            'planner-decision-upgrade',

        'budget enabler' =>
            'planner-decision-budget',

        'strategic sidegrade' =>
            'planner-decision-strategic',

        'sidegrade' =>
            'planner-decision-sidegrade',

        'risky punt' =>
            'planner-decision-risky',

        'downgrade' =>
            'planner-decision-downgrade',

        default =>
            'planner-decision-neutral'
    };
}


function plannerClassificationClass(
    ?string $classification
): string {

    return match (
        strtolower(
            (string) $classification
        )
    ) {

        'strong improvement' =>
            'planner-classification-strong',

        'improvement' =>
            'planner-classification-improvement',

        'balanced restructure' =>
            'planner-classification-balanced',

        'neutral restructure' =>
            'planner-classification-neutral',

        'risky restructure' =>
            'planner-classification-risky',

        'downgrade' =>
            'planner-classification-downgrade',

        'unaffordable' =>
            'planner-classification-unaffordable',

        default =>
            'planner-classification-neutral'
    };
}


function plannerRenderPlayerOptions(
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


        $name =
            (string) (
                $player[
                    'name'
                ]
                ?? 'Unknown'
            );


        $position =
            (string) (
                $player[
                    'position'
                ]
                ?? ''
            );


        $price =
            plannerDisplayPrice(
                $player[
                    'price'
                ]
                ?? null
            );


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
                $name,
                ENT_QUOTES,
                'UTF-8'
            ); ?>
            —
            <?= htmlspecialchars(
                $position,
                ENT_QUOTES,
                'UTF-8'
            ); ?>
            —
            <?= htmlspecialchars(
                $price,
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </option>

        <?php
    }
}
$activeNav = 'transfer-planner';
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
        content="Evaluate linked two-player FPL transfer combinations."
    >

    <title>
        Transfer Planner | FPL Intelligence
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
                        Squad Transfer Intelligence
                    </p>

                    <h1>
                        Transfer Planner
                    </h1>

                </div>


                <div class="topbar-actions">

                    <a
                        href="transfers.php"
                        class="profile-back-link"
                    >
                        ← Replacement Finder
                    </a>

                </div>

            </header>


            <!-- ==============================================
                 MAIN
                 ============================================== -->

            <main class="dashboard">


                <!-- ==========================================
                     PLANNER CONTROLS
                     ========================================== -->

                <section class="dashboard-card transfer-planner-form-card">

                    <div class="card-header">

                        <div>

                            <p class="card-kicker">
                                Two-Transfer Strategy
                            </p>

                            <h2>
                                Build Transfer Combination
                            </h2>

                        </div>

                    </div>


                    <p class="transfer-planner-explanation">

                        Select two outgoing players and their two
                        replacements. Each direct transfer is evaluated
                        independently before FPL Intelligence judges the
                        combined budget and squad impact.

                    </p>


                    <form
                        method="get"
                        action="transfer-planner.php"
                        class="transfer-planner-form"
                    >


                        <!-- ==================================
                             TRANSFER A
                             ================================== -->

                        <fieldset class="transfer-planner-fieldset">

                            <legend>
                                Transfer A
                            </legend>


                            <div class="transfer-planner-field">

                                <label for="current_a">
                                    Outgoing Player
                                </label>

                                <select
                                    name="current_a"
                                    id="current_a"
                                    required
                                >

                                    <option value="">
                                        Select outgoing player
                                    </option>

                                    <?php

                                    plannerRenderPlayerOptions(
                                        $players,
                                        $currentPlayerIdA
                                    );

                                    ?>

                                </select>

                            </div>


                            <div class="transfer-planner-arrow">
                                →
                            </div>


                            <div class="transfer-planner-field">

                                <label for="replacement_a">
                                    Incoming Player
                                </label>

                                <select
                                    name="replacement_a"
                                    id="replacement_a"
                                    required
                                >

                                    <option value="">
                                        Select incoming player
                                    </option>

                                    <?php

                                    plannerRenderPlayerOptions(
                                        $players,
                                        $replacementPlayerIdA
                                    );

                                    ?>

                                </select>

                            </div>

                        </fieldset>


                        <!-- ==================================
                             TRANSFER B
                             ================================== -->

                        <fieldset class="transfer-planner-fieldset">

                            <legend>
                                Transfer B
                            </legend>


                            <div class="transfer-planner-field">

                                <label for="current_b">
                                    Outgoing Player
                                </label>

                                <select
                                    name="current_b"
                                    id="current_b"
                                    required
                                >

                                    <option value="">
                                        Select outgoing player
                                    </option>

                                    <?php

                                    plannerRenderPlayerOptions(
                                        $players,
                                        $currentPlayerIdB
                                    );

                                    ?>

                                </select>

                            </div>


                            <div class="transfer-planner-arrow">
                                →
                            </div>


                            <div class="transfer-planner-field">

                                <label for="replacement_b">
                                    Incoming Player
                                </label>

                                <select
                                    name="replacement_b"
                                    id="replacement_b"
                                    required
                                >

                                    <option value="">
                                        Select incoming player
                                    </option>

                                    <?php

                                    plannerRenderPlayerOptions(
                                        $players,
                                        $replacementPlayerIdB
                                    );

                                    ?>

                                </select>

                            </div>

                        </fieldset>


                        <button
                            type="submit"
                            class="transfer-planner-submit"
                        >
                            Evaluate Combination
                        </button>

                    </form>


                    <?php if (
                        $plannerError !== null
                    ): ?>

                        <div class="transfer-error">

                            <?= htmlspecialchars(
                                $plannerError,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </div>

                    <?php endif; ?>

                </section>


                <?php if (
                    $combinationResult !== null
                ): ?>

                    <?php

                    $transferA =
                        $combinationResult[
                            'transfer_a'
                        ]
                        ?? [];


                    $transferB =
                        $combinationResult[
                            'transfer_b'
                        ]
                        ?? [];


                    $movements =
                        $combinationResult[
                            'combined_movements'
                        ]
                        ?? [];


                    $classification =
                        (string) (
                            $combinationResult[
                                'classification'
                            ]
                            ?? 'Unknown'
                        );


                    $combinationScore =
                        $combinationResult[
                            'combination_score'
                        ]
                        ?? null;


                    $isAffordable =
                        (bool) (
                            $combinationResult[
                                'is_affordable'
                            ]
                            ?? false
                        );

                    ?>


                    <!-- ======================================
                         COMBINATION RESULT
                         ====================================== -->

                    <section class="dashboard-card transfer-planner-result-card">

                        <div class="transfer-planner-result-heading">

                            <div>

                                <p class="card-kicker">
                                    Combination Decision
                                </p>

                                <h2>
                                    <?= htmlspecialchars(
                                        $classification,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </h2>

                            </div>


                            <span class="transfer-planner-classification <?= plannerClassificationClass(
                                $classification
                            ); ?>">

                                Score

                                <strong> 

                                    <?= plannerDisplayScore(
                                        $combinationScore
                                    ); ?>

                                </strong>

                            </span>

                        </div>


                        <div class="transfer-planner-result-grid">

                            <div class="transfer-planner-result-stat">

                                <span>
                                    Combination Score
                                </span>

                                <strong>

                                    <?= plannerDisplayScore(
                                        $combinationScore
                                    ); ?>

                                </strong>

                            </div>


                            <div class="transfer-planner-result-stat">

                                <span>
                                    Combined Intelligence
                                </span>

                                <strong>

                                    <?= plannerDisplaySigned(
                                        $movements[
                                            'intelligence'
                                        ]
                                        ?? null
                                    ); ?>

                                </strong>

                            </div>


                            <div class="transfer-planner-result-stat">

                                <span>
                                    Budget Remaining
                                </span>

                                <strong>

                                    <?= plannerDisplayBudget(
                                        $movements[
                                            'budget'
                                        ]
                                        ?? null
                                    ); ?>

                                </strong>

                            </div>


                            <div class="transfer-planner-result-stat">

                                <span>
                                    Affordable
                                </span>

                                <strong>

                                    <?= $isAffordable
                                        ? 'Yes'
                                        : 'No'; ?>

                                </strong>

                            </div>

                        </div>


                        <p class="transfer-planner-summary">

                            <?= htmlspecialchars(
                                (string) (
                                    $combinationResult[
                                        'summary'
                                    ]
                                    ?? ''
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </p>

                    </section>


                    <!-- ======================================
                         INDIVIDUAL TRANSFERS
                         ====================================== -->

                    <section class="transfer-planner-transfer-grid">


                        <?php foreach (
                            [
                                'A' => $transferA,
                                'B' => $transferB
                            ]
                            as $transferLabel => $transfer
                        ): ?>

                            <?php

                            $current =
                                $transfer[
                                    'current_player'
                                ]
                                ?? [];


                            $replacement =
                                $transfer[
                                    'replacement'
                                ]
                                ?? [];


                            $decisionType =
                                (string) (
                                    $transfer[
                                        'decision_type'
                                    ]
                                    ?? 'Unknown'
                                );


                            $decisionScore =
                                $transfer[
                                    'decision_score'
                                ]
                                ?? null;


                            $transferMovements =
                                $transfer[
                                    'movements'
                                ]
                                ?? [];

                            ?>

                            <article class="dashboard-card transfer-planner-transfer-card">

                                <div class="transfer-planner-transfer-header">

                                    <div>

                                        <p class="card-kicker">
                                            Transfer <?= $transferLabel; ?>
                                        </p>

                                        <h2>

                                            <?= htmlspecialchars(
                                                (string) (
                                                    $current[
                                                        'name'
                                                    ]
                                                    ?? 'Unknown'
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                            <span>
                                                →
                                            </span>

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

                                        </h2>

                                    </div>


                                    <span class="transfer-planner-decision <?= plannerDecisionClass(
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

                                </div>


                                <div class="transfer-planner-player-grid">

                                    <div class="transfer-planner-player">

                                        <span class="transfer-planner-player-label">
                                            Outgoing
                                        </span>

                                        <strong>

                                            <?= htmlspecialchars(
                                                (string) (
                                                    $current[
                                                        'name'
                                                    ]
                                                    ?? 'Unknown'
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </strong>

                                        <small>

                                            <?= plannerDisplayPrice(
                                                $current[
                                                    'price'
                                                ]
                                                ?? null
                                            ); ?>

                                            · INT

                                            <?= plannerDisplayRating(
                                                $current[
                                                    'intelligence_score'
                                                ]
                                                ?? null
                                            ); ?>

                                        </small>

                                    </div>


                                    <div class="transfer-planner-player-arrow">
                                        →
                                    </div>


                                    <div class="transfer-planner-player">

                                        <span class="transfer-planner-player-label">
                                            Incoming
                                        </span>

                                        <strong>

                                            <?php if (
                                                (
                                                    $replacement[
                                                        'player_id'
                                                    ]
                                                    ?? 0
                                                )
                                                > 0
                                            ): ?>

                                                <a
                                                    href="player.php?id=<?= (int) $replacement[
                                                        'player_id'
                                                    ]; ?>"
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

                                        </strong>

                                        <small>

                                            <?= plannerDisplayPrice(
                                                $replacement[
                                                    'price'
                                                ]
                                                ?? null
                                            ); ?>

                                            · INT

                                            <?= plannerDisplayRating(
                                                $replacement[
                                                    'intelligence_score'
                                                ]
                                                ?? null
                                            ); ?>

                                        </small>

                                    </div>

                                </div>


                                <div class="transfer-planner-transfer-metrics">

                                    <div>

                                        <span>
                                            INT
                                        </span>

                                        <strong>

                                            <?= plannerDisplaySigned(
                                                $transferMovements[
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

                                            <?= plannerDisplaySigned(
                                                $transferMovements[
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

                                            <?= plannerDisplaySigned(
                                                $transferMovements[
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

                                            <?= plannerDisplaySigned(
                                                $transferMovements[
                                                    'fixtures'
                                                ]
                                                ?? null
                                            ); ?>

                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Budget
                                        </span>

                                        <strong>

                                            <?= plannerDisplayBudget(
                                                $transferMovements[
                                                    'budget'
                                                ]
                                                ?? null
                                            ); ?>

                                        </strong>

                                    </div>

                                </div>


                                <p class="transfer-planner-transfer-summary">

                                    <?= htmlspecialchars(
                                        (string) (
                                            $transfer[
                                                'summary'
                                            ]
                                            ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </p>


                                <?php if (
                                    (
                                        $current[
                                            'player_id'
                                        ]
                                        ?? 0
                                    )
                                    > 0
                                    &&
                                    (
                                        $replacement[
                                            'player_id'
                                        ]
                                        ?? 0
                                    )
                                    > 0
                                ): ?>

                                    <div class="transfer-planner-transfer-actions">

                                        <a
                                            href="compare.php?player1=<?= (int) $current[
                                                'player_id'
                                            ]; ?>&player2=<?= (int) $replacement[
                                                'player_id'
                                            ]; ?>"
                                            class="transfer-primary-action"
                                        >
                                            Compare Players
                                        </a>

                                    </div>

                                <?php endif; ?>

                            </article>

                        <?php endforeach; ?>

                    </section>


                    <!-- ======================================
                         COMBINED MOVEMENT BREAKDOWN
                         ====================================== -->

                    <section class="dashboard-card transfer-planner-movement-card">

                        <div class="card-header">

                            <div>

                                <p class="card-kicker">
                                    Combined Intelligence
                                </p>

                                <h2>
                                    Movement Breakdown
                                </h2>

                            </div>

                        </div>


                        <div class="transfer-planner-movement-grid">

                            <div>

                                <span>
                                    Intelligence
                                </span>

                                <strong>

                                    <?= plannerDisplaySigned(
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

                                    <?= plannerDisplaySigned(
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

                                    <?= plannerDisplaySigned(
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

                                    <?= plannerDisplaySigned(
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

                                    <?= plannerDisplaySigned(
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
                                    Budget
                                </span>

                                <strong>

                                    <?= plannerDisplayBudget(
                                        $movements[
                                            'budget'
                                        ]
                                        ?? null
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
                    Transfer Planner
                </span>

            </footer>


        </div>

    </div>


    <script src="assets/js/app.js"></script>

</body>

</html>