<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * ACTIVE NAVIGATION
 * ============================================================
 */

$activeNav =
    'gameweek';


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

$service =
    null;


$importer =
    null;


$setupError =
    null;


/*
 * ============================================================
 * REQUEST
 * ============================================================
 */

$entryIdInput =
    filter_input(
        INPUT_GET,
        'entry_id',
        FILTER_VALIDATE_INT
    );


$entryId =
    (
        $entryIdInput !== false
        &&
        $entryIdInput !== null
        &&
        $entryIdInput > 0
    )
        ? (int) $entryIdInput
        : null;


$previewMode =
    filter_input(
        INPUT_GET,
        'preview',
        FILTER_VALIDATE_BOOLEAN
    )
        ? true
        : false;


/*
 * ============================================================
 * PAGE STATE
 * ============================================================
 */

$importResult =
    null;


$mappedSquad =
    null;


$gameweekResult =
    null;


$decisionResult =
    null;


$pageError =
    null;


try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $service =
        new PlayerIntelligenceService(
            $db
        );


    $importer =
        new FPLSquadImporter();

} catch (
    Throwable $exception
) {

    $setupError =
        'Unable to initialise Gameweek Intelligence.';
}


/*
 * ============================================================
 * DEVELOPMENT PREVIEW SQUAD
 * ============================================================
 *
 * Build a legal 15-player squad from current Player Intelligence
 * data so the Gameweek UI remains testable before a manager's
 * live FPL squad is publicly available.
 */

function buildGameweekPreviewSquad(
    PlayerIntelligenceService $service
): ?array {

    $summaries =
        $service
            ->getAllPlayerSummaries();


    if (
        empty(
            $summaries
        )
    ) {

        return null;
    }


    $positionRequirements = [

        'GK' =>
            2,

        'DEF' =>
            5,

        'MID' =>
            5,

        'FWD' =>
            3
    ];


    /*
     * Build candidate pools from players that have the core
     * information required for meaningful Gameweek evaluation.
     */

    $candidatePools = [

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
        $summaries
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


        if (
            $playerId <= 0
            ||
            $teamId <= 0
            ||
            !isset(
                $candidatePools[
                    $position
                ]
            )
            ||
            !is_numeric(
                $summary[
                    'intelligence_score'
                ]
                ?? null
            )
        ) {

            continue;
        }


        $candidatePools[
            $position
        ][] =
            $summary;
    }


    /*
     * Stronger Player Intelligence first.
     */

    foreach (
        $candidatePools
        as &$positionPlayers
    ) {

        usort(
            $positionPlayers,
            static function (
                array $a,
                array $b
            ): int {

                return (
                    (float) (
                        $b[
                            'intelligence_score'
                        ]
                        ?? 0
                    )
                )
                <=>
                (
                    (float) (
                        $a[
                            'intelligence_score'
                        ]
                        ?? 0
                    )
                );
            }
        );
    }


    unset(
        $positionPlayers
    );


    /*
     * Select across the candidate pool rather than simply taking
     * the top 15 players. This produces a more useful development
     * squad containing a mixture of strong and weaker options,
     * allowing Starting XI / bench decisions to be visible.
     */

    $percentiles = [

        0.00,
        0.20,
        0.40,
        0.60,
        0.80,
        1.00
    ];


    $players =
        [];


    $playerIds =
        [];


    $teamCounts =
        [];


    foreach (
        $positionRequirements
        as $position => $requiredCount
    ) {

        $pool =
            $candidatePools[
                $position
            ]
            ?? [];


        $selectedCount =
            0;


        /*
         * First try representative players across the quality
         * distribution.
         */

        foreach (
            $percentiles
            as $percentile
        ) {

            if (
                $selectedCount
                >=
                $requiredCount
            ) {

                break;
            }


            if (
                empty(
                    $pool
                )
            ) {

                break;
            }


            $index =
                (int) round(
                    (
                        count(
                            $pool
                        )
                        -
                        1
                    )
                    *
                    $percentile
                );


            $candidate =
                $pool[
                    $index
                ]
                ?? null;


            if (
                $candidate === null
            ) {

                continue;
            }


            $playerId =
                (int) (
                    $candidate[
                        'player_id'
                    ]
                    ?? 0
                );


            $teamId =
                (int) (
                    $candidate[
                        'team_id'
                    ]
                    ?? 0
                );


            if (
                $playerId <= 0
                ||
                $teamId <= 0
                ||
                in_array(
                    $playerId,
                    $playerIds,
                    true
                )
                ||
                (
                    $teamCounts[
                        $teamId
                    ]
                    ?? 0
                )
                >= 3
            ) {

                continue;
            }


            $players[] =
                buildGameweekPreviewPlayer(
                    $candidate,
                    count(
                        $players
                    )
                    + 1
                );


            $playerIds[] =
                $playerId;


            $teamCounts[
                $teamId
            ] =
                (
                    $teamCounts[
                        $teamId
                    ]
                    ?? 0
                )
                +
                1;


            $selectedCount++;
        }


        /*
         * Fill any remaining positional slots sequentially.
         */

        if (
            $selectedCount
            <
            $requiredCount
        ) {

            foreach (
                $pool
                as $candidate
            ) {

                if (
                    $selectedCount
                    >=
                    $requiredCount
                ) {

                    break;
                }


                $playerId =
                    (int) (
                        $candidate[
                            'player_id'
                        ]
                        ?? 0
                    );


                $teamId =
                    (int) (
                        $candidate[
                            'team_id'
                        ]
                        ?? 0
                    );


                if (
                    $playerId <= 0
                    ||
                    $teamId <= 0
                    ||
                    in_array(
                        $playerId,
                        $playerIds,
                        true
                    )
                    ||
                    (
                        $teamCounts[
                            $teamId
                        ]
                        ?? 0
                    )
                    >= 3
                ) {

                    continue;
                }


                $players[] =
                    buildGameweekPreviewPlayer(
                        $candidate,
                        count(
                            $players
                        )
                        + 1
                    );


                $playerIds[] =
                    $playerId;


                $teamCounts[
                    $teamId
                ] =
                    (
                        $teamCounts[
                            $teamId
                        ]
                        ?? 0
                    )
                    +
                    1;


                $selectedCount++;
            }
        }


        if (
            $selectedCount
            !==
            $requiredCount
        ) {

            return null;
        }
    }


    return [

        'is_complete' =>
            count(
                $players
            )
            === 15,

        'entry' => [

            'entry_id' =>
                0,

            'team_name' =>
                'Development Preview Squad',

            'manager_name' =>
                'Preview Mode'
        ],

        'gameweek' =>
            1,

        'bank' =>
            null,

        'team_value' =>
            null,

        'imported_count' =>
            15,

        'mapped_count' =>
            15,

        'unmapped_count' =>
            0,

        'players' =>
            $players
    ];
}


/*
 * ============================================================
 * PREVIEW PLAYER BUILDER
 * ============================================================
 */

function buildGameweekPreviewPlayer(
    array $summary,
    int $squadPosition
): array {

    return [

        'player_id' =>
            (int) (
                $summary[
                    'player_id'
                ]
                ?? 0
            ),

        'fpl_player_id' =>
            isset(
                $summary[
                    'fpl_player_id'
                ]
            )
                ? (int) $summary[
                    'fpl_player_id'
                ]
                : null,

        'name' =>
            $summary[
                'name'
            ]
            ?? null,

        'position' =>
            strtoupper(
                trim(
                    (string) (
                        $summary[
                            'position'
                        ]
                        ?? ''
                    )
                )
            ),

        'team_id' =>
            isset(
                $summary[
                    'team_id'
                ]
            )
                ? (int) $summary[
                    'team_id'
                ]
                : null,

        'team_name' =>
            $summary[
                'team_name'
            ]
            ?? null,

        'price' =>
            $summary[
                'price'
            ]
            ?? null,

        'intelligence_score' =>
            $summary[
                'intelligence_score'
            ]
            ?? null,

        'strength_rating' =>
            $summary[
                'strength_rating'
            ]
            ?? null,

        'fixture_rating' =>
            $summary[
                'fixture_rating'
            ]
            ?? null,

        'next_fixture_rating' =>
            $summary[
                'next_fixture_rating'
            ]
            ?? null,

        'availability_rating' =>
            $summary[
                'availability_rating'
            ]
            ?? null,

        'sample_confidence' =>
            $summary[
                'sample_confidence'
            ]
            ?? null,
            
        'effective_confidence' =>
            array_key_exists(
                'effective_confidence',
                $summary
            )
                ? $summary[
                    'effective_confidence'
                ]
                : null,

        'team_available_minutes' =>
            $summary[
                'team_available_minutes'
            ]
            ?? null,

        'participation_rate' =>
            $summary[
                'participation_rate'
            ]
            ?? null,

        'squad_position' =>
            $squadPosition,

        'multiplier' =>
            1,

        'is_captain' =>
            false,

        'is_vice_captain' =>
            false
    ];
}


/*
 * ============================================================
 * DEVELOPMENT PREVIEW
 * ============================================================
 */

if (
    $previewMode
    &&
    $setupError === null
    &&
    $service !== null
) {

    try {

        $mappedSquad =
            buildGameweekPreviewSquad(
                $service
            );


        if (
            $mappedSquad === null
            ||
            !(
                $mappedSquad[
                    'is_complete'
                ]
                ?? false
            )
        ) {

            $pageError =
                'Unable to build the Gameweek Intelligence preview squad.';
        }

    } catch (
        Throwable $exception
    ) {

        $pageError =
            'Unable to build the Gameweek Intelligence preview squad.';
    }
}


/*
 * ============================================================
 * REAL FPL SQUAD IMPORT
 * ============================================================
 */

if (
    !$previewMode
    &&
    $entryId !== null
    &&
    $setupError === null
    &&
    $service !== null
    &&
    $importer !== null
) {

    try {

        $importResult =
            $importer
                ->importSquad(
                    $entryId
                );


        if (
            $importResult === null
        ) {

            $pageError =
                'The FPL entry could not be found or imported.';

        } elseif (
            (
                $importResult[
                    'status'
                ]
                ?? null
            )
            ===
            'no_public_squad'
        ) {

            $pageError =
                'This FPL entry does not currently have a publicly available gameweek squad.';

        } elseif (
            (
                $importResult[
                    'status'
                ]
                ?? null
            )
            ===
            'success'
        ) {

            $mappedSquad =
                $service
                    ->buildSquadFromFPLImport(
                        $importResult
                    );


            if (
                $mappedSquad === null
                ||
                !(
                    $mappedSquad[
                        'is_complete'
                    ]
                    ?? false
                )
            ) {

                $pageError =
                    'The imported FPL squad could not be mapped completely.';
            }

        } else {

            $pageError =
                $importResult[
                    'message'
                ]
                ?? 'The FPL squad could not be imported.';
        }

    } catch (
        Throwable $exception
    ) {

        $pageError =
            'Unable to import this FPL squad at the moment.';
    }
}


/*
 * ============================================================
 * COMPLETE GAMEWEEK DECISION INTELLIGENCE
 * ============================================================
 */

if (
    $mappedSquad !== null
    &&
    (
        $mappedSquad[
            'is_complete'
        ]
        ?? false
    )
    &&
    $service !== null
) {

    try {

        $bank =
            is_numeric(
                $mappedSquad[
                    'bank'
                ]
                ?? null
            )
                ? (float) $mappedSquad[
                    'bank'
                ]
                : 0.0;


        $decisionResult =
            $service
                ->getGameweekDecision(
                    $mappedSquad[
                        'players'
                    ]
                    ?? [],
                    $bank
                );


        if (
            (
                $decisionResult[
                    'status'
                ]
                ?? null
            )
            ===
            'success'
        ) {

            /*
             * Preserve the existing page contract.
             *
             * The rest of gameweek.php already expects
             * $gameweekResult, so use the Gameweek output
             * produced by the complete decision pipeline.
             */

            $gameweekResult =
                $decisionResult[
                    'gameweek'
                ]
                ?? null;

        } else {

            $pageError =
                $decisionResult[
                    'message'
                ]
                ?? 'Gameweek Decision Intelligence could not be generated.';
        }

    } catch (
        Throwable $exception
    ) {

        $pageError =
            'Unable to generate Gameweek Decision Intelligence at the moment.';
    }
}


/*
 * ============================================================
 * DISPLAY HELPERS
 * ============================================================
 */

function gameweekPageRating(
    mixed $value,
    int $decimals = 1
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


function gameweekPageEscape(
    mixed $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
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
        content="Build the strongest legal Starting XI and bench for the immediate FPL gameweek."
    >

    <title>
        Gameweek Intelligence | FPL Intelligence
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
                    Weekly Decision Support
                </p>

                <h1>
                    Gameweek Intelligence
                </h1>

                <p class="topbar-subtitle">
                    Select the strongest legal Starting XI for the immediate
                    gameweek using Player Intelligence, fixture opportunity,
                    confidence and availability.
                </p>

            </div>

        </header>


        <main class="dashboard gameweek-dashboard">


            <!-- ==============================================
                 SQUAD INPUT
                 ============================================== -->

            <section class="dashboard-section">

                <div class="section-heading">

                    <p class="eyebrow">
                        Squad
                    </p>

                    <h2>
                        Analyse Your Gameweek
                    </h2>

                </div>


                <form
                    method="get"
                    action="gameweek.php"
                    class="squad-import-form"
                >

                    <label for="entry_id">
                        FPL Entry ID
                    </label>

                    <div class="squad-import-controls">

                        <input
                            type="number"
                            id="entry_id"
                            name="entry_id"
                            min="1"
                            step="1"
                            value="<?= $entryId !== null
                                ? (int) $entryId
                                : ''; ?>"
                            placeholder="Enter FPL Entry ID"
                        >

                        <button type="submit">
                            Analyse Gameweek
                        </button>

                    </div>

                </form>


                <p class="gameweek-preview-link">

                    Development mode:

                    <a href="gameweek.php?preview=1">
                        Load preview squad
                    </a>

                </p>

            </section>


            <?php if (
                $setupError !== null
                ||
                $pageError !== null
            ): ?>

                <section class="dashboard-section">

                    <div class="profile-panel">

                        <p>
                            <?= gameweekPageEscape(
                                $setupError
                                ??
                                $pageError
                            ); ?>
                        </p>

                    </div>

                </section>

            <?php endif; ?>


            <?php if (
                $gameweekResult !== null
                &&
                (
                    $gameweekResult[
                        'status'
                    ]
                    ?? null
                )
                ===
                'success'
            ): ?>

                <?php

                $startingXI =
                    $gameweekResult[
                        'starting_xi'
                    ]
                    ?? [];


                $bench =
                    $gameweekResult[
                        'bench'
                    ]
                    ?? [];


                $formations =
                    $gameweekResult[
                        'formations'
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


                $entry =
                    $mappedSquad[
                        'entry'
                    ]
                    ?? [];
                    
                
                /*
                 * ============================================================
                 * GAMEWEEK DECISION INTELLIGENCE
                 * ============================================================
                 */

                $decision =
                    $decisionResult[
                        'decision'
                    ]
                    ?? [];


                $captaincy =
                    $decisionResult[
                        'captaincy'
                    ]
                    ?? [];


                $transfers =
                    $decisionResult[
                        'transfers'
                    ]
                    ?? [];


                $overallAction =
                    $decisionResult[
                        'overall_action'
                    ]
                    ?? 'Review';


                $captain =
                    $captaincy[
                        'captain'
                    ]
                    ?? [];


                $viceCaptain =
                    $captaincy[
                        'vice_captain'
                    ]
                    ?? [];


                $squadRisks =
                    $decision[
                        'squad_risks'
                    ]
                    ?? [];


                $transferAdvice =
                    $decision[
                        'transfer_advice'
                    ]
                    ?? [];


                /*
                 * Current single-transfer optimizer structure:
                 *
                 * transfers
                 *   -> recommendations
                 *      -> recommendations
                 *         -> first outgoing player
                 */

                $transferGroups =
                    $transfers[
                        'recommendations'
                    ][
                        'recommendations'
                    ]
                    ?? [];


                $topTransferGroup =
                    $transferGroups[
                        0
                    ]
                    ?? [];


                $topOutgoing =
                    $topTransferGroup[
                        'outgoing'
                    ]
                    ?? [];


                $topReplacement =
                    $topTransferGroup[
                        'replacements'
                    ][
                        0
                    ]
                    ?? [];


                $topIncoming =
                    $topReplacement[
                        'player'
                    ]
                    ?? [];


                $keyInsights =
                    $decision[
                        'key_insights'
                    ]
                    ?? [];

                ?>
                
                <!-- ==============================================
                     GAMEWEEK DECISION
                     ============================================== -->

                <section class="dashboard-section gameweek-decision-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Decision Intelligence
                        </p>

                        <h2>
                            What Should You Do?
                        </h2>

                    </div>


                    <div
                        class="gameweek-decision-hero gameweek-decision-hero-<?=
                            strtolower(
                                str_replace(
                                    ' ',
                                    '-',
                                    $overallAction
                                )
                            );
                        ?>"
                    >

                        <div class="gameweek-decision-hero-content">

                            <p class="eyebrow">
                                Overall Action
                            </p>

                            <h3>
                                <?= gameweekPageEscape(
                                    $overallAction
                                ); ?>
                            </h3>


                            <p class="gameweek-decision-message">

                                <?php if (
                                    $overallAction ===
                                    'Urgent Action'
                                ): ?>

                                    Your squad contains an issue that requires
                                    immediate attention before the deadline.

                                <?php elseif (
                                    $overallAction ===
                                    'Make Transfer'
                                ): ?>

                                    Transfer Intelligence has identified a
                                    high-priority move that is strong enough
                                    to justify using a transfer.

                                <?php elseif (
                                    $overallAction ===
                                    'Consider Transfer'
                                ): ?>

                                    There are squad issues or transfer opportunities
                                    worth reviewing before finalising your team.

                                <?php else: ?>

                                    No current issue is strong enough to justify
                                    forcing a transfer. Your squad can be held.

                                <?php endif; ?>

                            </p>

                        </div>


                        <div class="gameweek-decision-risk-summary">

                            <span>
                                Squad Risks
                            </span>

                            <strong>
                                <?= (int) (
                                    $squadRisks[
                                        'count'
                                    ]
                                    ?? 0
                                ); ?>
                            </strong>

                        </div>

                    </div>


                    <div class="gameweek-decision-grid">


                        <!-- CAPTAIN -->

                        <div class="gameweek-decision-card">

                            <p class="eyebrow">
                                Captain
                            </p>

                            <h3>
                                <?= gameweekPageEscape(
                                    $captain[
                                        'name'
                                    ]
                                    ?? 'N/A'
                                ); ?>
                            </h3>

                            <div class="gameweek-decision-card-meta">

                                <span>
                                    Captain Score
                                </span>

                                <strong>
                                    <?= gameweekPageRating(
                                        $captain[
                                            'captain_score'
                                        ]
                                        ?? null,
                                        2
                                    ); ?>
                                </strong>

                            </div>

                            <p>
                                <?= gameweekPageEscape(
                                    $captain[
                                        'classification'
                                    ]
                                    ?? 'No classification'
                                ); ?>
                            </p>

                        </div>


                        <!-- VICE CAPTAIN -->

                        <div class="gameweek-decision-card">

                            <p class="eyebrow">
                                Vice-Captain
                            </p>

                            <h3>
                                <?= gameweekPageEscape(
                                    $viceCaptain[
                                        'name'
                                    ]
                                    ?? 'N/A'
                                ); ?>
                            </h3>

                            <div class="gameweek-decision-card-meta">

                                <span>
                                    Captain Score
                                </span>

                                <strong>
                                    <?= gameweekPageRating(
                                        $viceCaptain[
                                            'captain_score'
                                        ]
                                        ?? null,
                                        2
                                    ); ?>
                                </strong>

                            </div>

                            <p>
                                <?= gameweekPageEscape(
                                    $viceCaptain[
                                        'classification'
                                    ]
                                    ?? 'No classification'
                                ); ?>
                            </p>

                        </div>


                        <!-- FORMATION -->

                        <div class="gameweek-decision-card">

                            <p class="eyebrow">
                                Formation
                            </p>

                            <h3>
                                <?= gameweekPageEscape(
                                    $gameweekResult[
                                        'formation'
                                    ]
                                    ?? 'N/A'
                                ); ?>
                            </h3>

                            <div class="gameweek-decision-card-meta">

                                <span>
                                    Starting XI Score
                                </span>

                                <strong>
                                    <?= gameweekPageRating(
                                        $gameweekResult[
                                            'starting_xi_score'
                                        ]
                                        ?? null,
                                        2
                                    ); ?>
                                </strong>

                            </div>

                            <p>
                                Highest-rated legal formation
                            </p>

                        </div>


                        <!-- TRANSFER -->

                        <div class="gameweek-decision-card">

                            <p class="eyebrow">
                                Transfer
                            </p>

                            <h3>
                                <?= gameweekPageEscape(
                                    $transferAdvice[
                                        'action'
                                    ]
                                    ?? 'Review'
                                ); ?>
                            </h3>

                            <div class="gameweek-decision-card-meta">

                                <span>
                                    Priority
                                </span>

                                <strong>
                                    <?= gameweekPageEscape(
                                        $transferAdvice[
                                            'priority'
                                        ]
                                        ?? 'Unknown'
                                    ); ?>
                                </strong>

                            </div>

                            <p>

                                <?php if (
                                    !empty(
                                        $topOutgoing[
                                            'name'
                                        ]
                                        ?? null
                                    )
                                    &&
                                    !empty(
                                        $topIncoming[
                                            'name'
                                        ]
                                        ?? null
                                    )
                                ): ?>

                                    <?= gameweekPageEscape(
                                        $topOutgoing[
                                            'name'
                                        ]
                                    ); ?>

                                    →

                                    <?= gameweekPageEscape(
                                        $topIncoming[
                                            'name'
                                        ]
                                    ); ?>

                                <?php else: ?>

                                    No specific transfer recommendation available.

                                <?php endif; ?>

                            </p>

                        </div>

                    </div>

                </section>


                <!-- ==============================================
                     SUMMARY
                     ============================================== -->

                <section class="dashboard-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Recommendation
                        </p>

                        <h2>
                            Gameweek Summary
                        </h2>

                    </div>


                    <div class="wildcard-summary-grid">

                        <div class="wildcard-summary-card">

                            <p class="eyebrow">
                                Formation
                            </p>

                            <h3>
                                <?= gameweekPageEscape(
                                    $gameweekResult[
                                        'formation'
                                    ]
                                    ?? 'N/A'
                                ); ?>
                            </h3>

                        </div>


                        <div class="wildcard-summary-card">

                            <p class="eyebrow">
                                Starting XI Score
                            </p>

                            <h3>
                                <?= gameweekPageRating(
                                    $gameweekResult[
                                        'starting_xi_score'
                                    ]
                                    ?? null,
                                    2
                                ); ?>
                            </h3>

                        </div>


                        <div class="wildcard-summary-card">

                            <p class="eyebrow">
                                Bench Score
                            </p>

                            <h3>
                                <?= gameweekPageRating(
                                    $gameweekResult[
                                        'bench_score'
                                    ]
                                    ?? null,
                                    2
                                ); ?>
                            </h3>

                        </div>


                        <div class="wildcard-summary-card">

                            <p class="eyebrow">
                                Gameweek
                            </p>

                            <h3>
                                <?= gameweekPageEscape(
                                    $mappedSquad[
                                        'gameweek'
                                    ]
                                    ?? '—'
                                ); ?>
                            </h3>

                        </div>


                        <div class="wildcard-summary-card">

                            <p class="eyebrow">
                                Squad
                            </p>

                            <h3>
                                <?= gameweekPageEscape(
                                    $entry[
                                        'team_name'
                                    ]
                                    ?? 'FPL Squad'
                                ); ?>
                            </h3>

                        </div>

                    </div>

                </section>
                
                <!-- ==============================================
                     SQUAD RISKS
                     ============================================== -->

                <section class="dashboard-section gameweek-risks-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Squad Reliability
                        </p>

                        <h2>
                            Squad Risks
                        </h2>

                    </div>


                    <p class="gameweek-section-description">
                        Availability and confidence issues that could affect your
                        gameweek plan.
                    </p>


                    <?php

                    $riskList =
                        $squadRisks[
                            'risks'
                        ]
                        ?? [];


                    $displayRisks =
                        array_slice(
                            $riskList,
                            0,
                            5
                        );

                    ?>


                    <div class="gameweek-risk-summary-grid">

                        <div class="gameweek-risk-summary-card">

                            <span>
                                Total Risks
                            </span>

                            <strong>
                                <?= (int) (
                                    $squadRisks[
                                        'count'
                                    ]
                                    ?? 0
                                ); ?>
                            </strong>

                        </div>


                        <div class="gameweek-risk-summary-card">

                            <span>
                                Critical
                            </span>

                            <strong>
                                <?= (int) (
                                    $squadRisks[
                                        'critical_count'
                                    ]
                                    ?? 0
                                ); ?>
                            </strong>

                        </div>


                        <div class="gameweek-risk-summary-card">

                            <span>
                                High
                            </span>

                            <strong>
                                <?= (int) (
                                    $squadRisks[
                                        'high_count'
                                    ]
                                    ?? 0
                                ); ?>
                            </strong>

                        </div>


                        <div class="gameweek-risk-summary-card">

                            <span>
                                Starting XI Risks
                            </span>

                            <strong>
                                <?= (int) (
                                    $squadRisks[
                                        'starting_xi_risk_count'
                                    ]
                                    ?? 0
                                ); ?>
                            </strong>

                        </div>

                    </div>


                    <?php if (
                        empty(
                            $displayRisks
                        )
                    ): ?>

                        <div class="gameweek-risk-empty">

                            <strong>
                                No material squad risks detected.
                            </strong>

                            <p>
                                Your recommended squad structure currently has no
                                significant availability or confidence concerns.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="gameweek-risk-list">

                            <?php foreach (
                                $displayRisks
                                as $risk
                            ): ?>

                                <?php

                                $severity =
                                    strtolower(
                                        trim(
                                            (string) (
                                                $risk[
                                                    'severity'
                                                ]
                                                ?? 'medium'
                                            )
                                        )
                                    );

                                ?>

                                <div
                                    class="gameweek-risk-card gameweek-risk-card-<?=
                                        gameweekPageEscape(
                                            $severity
                                        );
                                    ?>"
                                >

                                    <div class="gameweek-risk-card-main">

                                        <div>

                                            <span class="gameweek-risk-severity">
                                                <?= gameweekPageEscape(
                                                    ucfirst(
                                                        $severity
                                                    )
                                                ); ?>
                                            </span>

                                            <h3>
                                                <?= gameweekPageEscape(
                                                    $risk[
                                                        'name'
                                                    ]
                                                    ?? 'Unknown Player'
                                                ); ?>
                                            </h3>

                                        </div>


                                        <div class="gameweek-risk-value">

                                            <?= gameweekPageRating(
                                                $risk[
                                                    'value'
                                                ]
                                                ?? null,
                                                1
                                            ); ?>

                                            <?php if (
                                                (
                                                    $risk[
                                                        'type'
                                                    ]
                                                    ?? null
                                                )
                                                ===
                                                'confidence'
                                                ||
                                                (
                                                    $risk[
                                                        'type'
                                                    ]
                                                    ?? null
                                                )
                                                ===
                                                'availability'
                                            ): ?>

                                                %

                                            <?php endif; ?>

                                        </div>

                                    </div>


                                    <div class="gameweek-risk-meta">

                                        <span>
                                            <?= gameweekPageEscape(
                                                ucfirst(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $risk[
                                                            'location'
                                                        ]
                                                        ?? ''
                                                    )
                                                )
                                            ); ?>
                                        </span>

                                        <span>
                                            <?= gameweekPageEscape(
                                                ucfirst(
                                                    $risk[
                                                        'type'
                                                    ]
                                                    ?? ''
                                                )
                                            ); ?>
                                        </span>

                                    </div>


                                    <p>
                                        <?= gameweekPageEscape(
                                            $risk[
                                                'message'
                                            ]
                                            ?? ''
                                        ); ?>
                                    </p>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <?php if (
                            count(
                                $riskList
                            )
                            >
                            count(
                                $displayRisks
                            )
                        ): ?>

                            <p class="gameweek-risk-more">

                                Showing the 5 highest-priority risks from
                                <?= count(
                                    $riskList
                                ); ?>
                                detected issues.

                            </p>

                        <?php endif; ?>

                    <?php endif; ?>

                </section>
                
                <!-- ==============================================
                     KEY INSIGHTS
                     ============================================== -->

                <section class="dashboard-section gameweek-insights-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Decision Explanation
                        </p>

                        <h2>
                            Key Insights
                        </h2>

                    </div>


                    <p class="gameweek-section-description">
                        The main reasons behind the current Gameweek Intelligence
                        recommendation.
                    </p>


                    <?php if (
                        empty(
                            $keyInsights
                        )
                    ): ?>

                        <div class="gameweek-insight-empty">

                            <p>
                                No additional Gameweek Decision insights are available.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="gameweek-insight-list">

                            <?php foreach (
                                $keyInsights
                                as $index => $insight
                            ): ?>

                                <div class="gameweek-insight-card">

                                    <div class="gameweek-insight-number">
                                        <?= $index + 1; ?>
                                    </div>

                                    <p>
                                        <?= gameweekPageEscape(
                                            $insight
                                        ); ?>
                                    </p>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </section>


                <!-- ==============================================
                     STARTING XI
                     ============================================== -->

                <section class="dashboard-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Starting XI
                        </p>

                        <h2>
                            Recommended Line-up
                        </h2>

                    </div>


                    <p class="gameweek-section-description">
                        The strongest legal formation for the immediate
                        gameweek. Gameweek Score combines current Player
                        Intelligence, underlying strength, the next fixture
                        and reliability.
                    </p>


                    <div class="wildcard-pitch">

                        <?php

                        $pitchRows = [

                            'GK' =>
                                'Goalkeeper',

                            'DEF' =>
                                'Defence',

                            'MID' =>
                                'Midfield',

                            'FWD' =>
                                'Forwards'
                        ];

                        ?>


                        <?php foreach (
                            $pitchRows
                            as $position => $label
                        ): ?>

                            <div
                                class="wildcard-pitch-row wildcard-pitch-row-<?= strtolower(
                                    $position
                                ); ?>"
                            >

                                <p class="wildcard-pitch-label">
                                    <?= gameweekPageEscape(
                                        $label
                                    ); ?>
                                </p>


                                <div class="wildcard-pitch-players">

                                    <?php foreach (
                                        $startingByPosition[
                                            $position
                                        ]
                                        ?? []
                                        as $player
                                    ): ?>

                                        <?php

                                        $components =
                                            $player[
                                                'gameweek_components'
                                            ]
                                            ?? [];

                                        ?>

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

                                                <?= gameweekPageEscape(
                                                    $player[
                                                        'name'
                                                    ]
                                                    ?? 'Unknown'
                                                ); ?>

                                            </div>


                                            <div class="wildcard-pitch-player-meta">

                                                <span>
                                                    <?= gameweekPageEscape(
                                                        $player[
                                                            'team_name'
                                                        ]
                                                        ?? 'Unknown'
                                                    ); ?>
                                                </span>

                                                <span>
                                                    <?= gameweekPageEscape(
                                                        $player[
                                                            'position'
                                                        ]
                                                        ?? ''
                                                    ); ?>
                                                </span>

                                            </div>


                                            <div class="wildcard-pitch-player-score">

                                                <span>
                                                    GW
                                                </span>

                                                <strong>
                                                    <?= gameweekPageRating(
                                                        $player[
                                                            'gameweek_score'
                                                        ]
                                                        ?? null,
                                                        1
                                                    ); ?>
                                                </strong>

                                                <span>
                                                    Fixture
                                                </span>

                                                <strong>
                                                    <?= gameweekPageRating(
                                                        $components[
                                                            'fixture'
                                                        ]
                                                        ?? null,
                                                        1
                                                    ); ?>
                                                </strong>

                                            </div>

                                        </a>

                                    <?php endforeach; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </section>


                <!-- ==============================================
                     ORDERED BENCH
                     ============================================== -->

                <section class="dashboard-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Substitutes
                        </p>

                        <h2>
                            Ordered Bench
                        </h2>

                    </div>


                    <p class="gameweek-section-description">
                        Outfield substitutes are ordered by Gameweek Score.
                        The backup goalkeeper remains Bench 4.
                    </p>


                    <div class="wildcard-bench-grid">

                        <?php foreach (
                            $bench
                            as $player
                        ): ?>

                            <?php

                            $components =
                                $player[
                                    'gameweek_components'
                                ]
                                ?? [];

                            ?>

                            <a
                                class="wildcard-bench-card"
                                href="player.php?id=<?= (int) (
                                    $player[
                                        'player_id'
                                    ]
                                    ?? 0
                                ); ?>"
                            >

                                <div class="wildcard-bench-order">

                                    Bench
                                    <?= (int) (
                                        $player[
                                            'bench_order'
                                        ]
                                        ?? 0
                                    ); ?>

                                </div>


                                <div class="wildcard-player-name">

                                    <?= gameweekPageEscape(
                                        $player[
                                            'name'
                                        ]
                                        ?? 'Unknown'
                                    ); ?>

                                </div>


                                <div class="wildcard-player-meta">

                                    <span>
                                        <?= gameweekPageEscape(
                                            $player[
                                                'team_name'
                                            ]
                                            ?? 'Unknown'
                                        ); ?>
                                    </span>

                                    <span>
                                        <?= gameweekPageEscape(
                                            $player[
                                                'position'
                                            ]
                                            ?? ''
                                        ); ?>
                                    </span>

                                </div>


                                <div class="wildcard-bench-metrics">

                                    <div>

                                        <span>
                                            Gameweek Score
                                        </span>

                                        <strong>
                                            <?= gameweekPageRating(
                                                $player[
                                                    'gameweek_score'
                                                ]
                                                ?? null,
                                                1
                                            ); ?>
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Fixture
                                        </span>

                                        <strong>
                                            <?= gameweekPageRating(
                                                $components[
                                                    'fixture'
                                                ]
                                                ?? null,
                                                1
                                            ); ?>
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Confidence
                                        </span>

                                        <strong>
                                            <?= gameweekPageRating(
                                                $components[
                                                    'confidence'
                                                ]
                                                ?? null,
                                                1
                                            ); ?>%
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Availability
                                        </span>

                                        <strong>
                                            <?= gameweekPageRating(
                                                $components[
                                                    'availability'
                                                ]
                                                ?? null,
                                                1
                                            ); ?>%
                                        </strong>

                                    </div>

                                </div>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </section>


                <!-- ==============================================
                     FORMATION COMPARISON
                     ============================================== -->

                <section class="dashboard-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Formation Intelligence
                        </p>

                        <h2>
                            Formation Comparison
                        </h2>

                    </div>


                    <p class="gameweek-section-description">
                        Every legal FPL formation is evaluated against the
                        same 15-player squad. The highest Starting XI Score
                        becomes the recommended formation.
                    </p>


                    <div class="gameweek-formation-grid">

                        <?php foreach (
                            $formations
                            as $index => $formation
                        ): ?>

                            <div
                                class="gameweek-formation-card<?= $index === 0
                                    ? ' gameweek-formation-card-best'
                                    : ''; ?>"
                            >

                                <div class="gameweek-formation-rank">

                                    #<?= $index + 1; ?>

                                </div>


                                <strong class="gameweek-formation-name">

                                    <?= gameweekPageEscape(
                                        $formation[
                                            'formation'
                                        ]
                                        ?? 'Unknown'
                                    ); ?>

                                </strong>


                                <div class="gameweek-formation-metrics">

                                    <div>

                                        <span>
                                            Starting XI
                                        </span>

                                        <strong>
                                            <?= gameweekPageRating(
                                                $formation[
                                                    'starting_xi_score'
                                                ]
                                                ?? null,
                                                2
                                            ); ?>
                                        </strong>

                                    </div>


                                    <div>

                                        <span>
                                            Bench
                                        </span>

                                        <strong>
                                            <?= gameweekPageRating(
                                                $formation[
                                                    'bench_score'
                                                ]
                                                ?? null,
                                                2
                                            ); ?>
                                        </strong>

                                    </div>

                                </div>

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