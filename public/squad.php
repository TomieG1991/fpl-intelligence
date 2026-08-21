<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * ACTIVE NAVIGATION
 * ============================================================
 */

$activeNav =
    'squad';


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
    
$playerRepository = 
    null;


try {

    $database =
        new Database();


    $service =
        new PlayerIntelligenceService(
            $database->getConnection()
        );


    $importer =
        new FPLSquadImporter();
        
        
    $playerRepository =
    new PlayerRepository(
        $database->getConnection()
    );

} catch (Throwable $exception) {

    $setupError =
        'Unable to initialise Squad Intelligence.';
}


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


function buildSquadPreview(
    PlayerIntelligenceService $service,
    PlayerRepository $playerRepository
): ?array {

    /*
     * ========================================================
     * MANUAL PREVIEW SQUAD
     * ========================================================
     *
     * Temporary real-world development squad for checking
     * Squad Intelligence against the current GW1 selection.
     */

    $previewSquad = [

        'GK' => [

            [
                'name' =>
                    'Verbruggen',

                'aliases' => [
                    'Verbruggen'
                ]
            ],

            [
                'name' =>
                    'Kinsky',

                'aliases' => [
                    'Kinsky',
                    'Kinský'
                ]
            ]
        ],


        'DEF' => [

            [
                'name' =>
                    'Maguire',

                'aliases' => [
                    'Maguire'
                ]
            ],

            [
                'name' =>
                    'Calafiori',

                'aliases' => [
                    'Calafiori'
                ]
            ],

            [
                'name' =>
                    'De Cuyper',

                'aliases' => [
                    'De Cuyper',
                    'Decuyper',
                    'DeCuyper'
                ]
            ],

            [
                'name' =>
                    'Mitchell',

                'aliases' => [
                    'Mitchell',
                    'Mitchel'
                ]
            ],

            [
                'name' =>
                    'Thomas',

                'aliases' => [
                    'Thomas'
                ]
            ]
        ],


        'MID' => [

            [
                'name' =>
                    'Tzolis',

                'aliases' => [
                    'Tzolis'
                ]
            ],

            [
                'name' =>
                    'Mbeumo',

                'aliases' => [
                    'Mbeumo'
                ]
            ],

            [
                'name' =>
                    'Anderson',

                'aliases' => [
                    'Anderson'
                ]
            ],

            [
                'name' =>
                    'Gibbs-White',

                'aliases' => [
                    'Gibbs-White',
                    'Gibbs White'
                ]
            ],

            [
                'name' =>
                    'Rogers',

                'aliases' => [
                    'Rogers'
                ]
            ]
        ],


        'FWD' => [

            [
                'name' =>
                    'Joao Pedro',

                'aliases' => [
                    'Joao Pedro',
                    'João Pedro',
                    'J.Pedro'
                ]
            ],

            [
                'name' =>
                    'Haaland',

                'aliases' => [
                    'Haaland'
                ]
            ],

            [
                'name' =>
                    'Brobbey',

                'aliases' => [
                    'Brobbey'
                ]
            ]
        ]
    ];


    /*
     * ========================================================
     * NORMALISE PLAYER NAMES
     * ========================================================
     */

    $normaliseName =
        static function (
            string $name
        ): string {

            $name =
                trim(
                    $name
                );


            /*
             * Convert accented characters where possible.
             */

            if (
                function_exists(
                    'iconv'
                )
            ) {

                $converted =
                    @iconv(
                        'UTF-8',
                        'ASCII//TRANSLIT//IGNORE',
                        $name
                    );


                if (
                    is_string(
                        $converted
                    )
                    &&
                    $converted !== ''
                ) {

                    $name =
                        $converted;
                }
            }


            $name =
                strtolower(
                    $name
                );


            /*
             * Remove spaces, punctuation and separators so:
             *
             * De Cuyper
             * DeCuyper
             * de-cuyper
             *
             * all resolve consistently.
             */

            $name =
                preg_replace(
                    '/[^a-z0-9]/',
                    '',
                    $name
                )
                ?? '';


            return $name;
        };


    /*
     * ========================================================
     * LOAD CURRENT DATA
     * ========================================================
     */

    $summaries =
        $service
            ->getAllPlayerSummaries();


    $localPlayers =
        $playerRepository
            ->getAll();


    /*
     * ========================================================
     * BUILD PLAYER LOOKUPS
     * ========================================================
     */

    $localById =
        [];


    $localByName =
        [];


    foreach (
        $localPlayers
        as $localPlayer
    ) {

        $playerId =
            (int) (
                $localPlayer[
                    'id'
                ]
                ?? 0
            );


        if (
            $playerId <= 0
        ) {

            continue;
        }


        $localById[
            $playerId
        ] =
            $localPlayer;


        $possibleNames = [

            $localPlayer[
                'web_name'
            ]
            ?? '',

            $localPlayer[
                'first_name'
            ]
            ?? '',

            $localPlayer[
                'second_name'
            ]
            ?? ''
        ];


        foreach (
            $possibleNames
            as $possibleName
        ) {

            $normalised =
                $normaliseName(
                    (string) $possibleName
                );


            if (
                $normalised !== ''
            ) {

                $localByName[
                    $normalised
                ] =
                    $localPlayer;
            }
        }


        /*
         * Also index the complete first + second name.
         */

        $fullName =
            trim(
                (
                    $localPlayer[
                        'first_name'
                    ]
                    ?? ''
                )
                . ' '
                . (
                    $localPlayer[
                        'second_name'
                    ]
                    ?? ''
                )
            );


        if (
            $fullName !== ''
        ) {

            $localByName[
                $normaliseName(
                    $fullName
                )
            ] =
                $localPlayer;
        }
    }


    $summaryByPlayerId =
        [];


    $summaryByName =
        [];


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


        if (
            $playerId > 0
        ) {

            $summaryByPlayerId[
                $playerId
            ] =
                $summary;
        }


        $summaryName =
            (string) (
                $summary[
                    'name'
                ]
                ?? ''
            );


        if (
            $summaryName !== ''
        ) {

            $summaryByName[
                $normaliseName(
                    $summaryName
                )
            ] =
                $summary;
        }
    }


    /*
     * ========================================================
     * RESOLVE MANUAL SQUAD
     * ========================================================
     */

    $players =
        [];


    $missingPlayers =
        [];


    foreach (
        $previewSquad
        as $requiredPosition => $requestedPlayers
    ) {

        foreach (
            $requestedPlayers
            as $requestedPlayer
        ) {

            $aliases =
                $requestedPlayer[
                    'aliases'
                ]
                ?? [];


            $localPlayer =
                null;


            $summary =
                null;


            /*
             * ------------------------------------------------
             * TRY LOCAL PLAYER LOOKUP
             * ------------------------------------------------
             */

            foreach (
                $aliases
                as $alias
            ) {

                $normalisedAlias =
                    $normaliseName(
                        $alias
                    );


                if (
                    isset(
                        $localByName[
                            $normalisedAlias
                        ]
                    )
                ) {

                    $localPlayer =
                        $localByName[
                            $normalisedAlias
                        ];


                    break;
                }
            }


            /*
             * ------------------------------------------------
             * TRY SUMMARY LOOKUP
             * ------------------------------------------------
             */

            if (
                $localPlayer !== null
            ) {

                $localPlayerId =
                    (int) (
                        $localPlayer[
                            'id'
                        ]
                        ?? 0
                    );


                $summary =
                    $summaryByPlayerId[
                        $localPlayerId
                    ]
                    ?? null;
            }


            /*
             * If the repository-name lookup missed, try the
             * Player Intelligence display name directly.
             */

            if (
                $summary === null
            ) {

                foreach (
                    $aliases
                    as $alias
                ) {

                    $normalisedAlias =
                        $normaliseName(
                            $alias
                        );


                    if (
                        isset(
                            $summaryByName[
                                $normalisedAlias
                            ]
                        )
                    ) {

                        $summary =
                            $summaryByName[
                                $normalisedAlias
                            ];


                        $summaryPlayerId =
                            (int) (
                                $summary[
                                    'player_id'
                                ]
                                ?? 0
                            );


                        $localPlayer =
                            $localById[
                                $summaryPlayerId
                            ]
                            ?? null;


                        break;
                    }
                }
            }


            if (
                $localPlayer === null
                ||
                $summary === null
            ) {

                $missingPlayers[] =
                    $requestedPlayer[
                        'name'
                    ]
                    ?? 'Unknown';


                continue;
            }


            /*
             * ------------------------------------------------
             * VALIDATE POSITION
             * ------------------------------------------------
             */

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
                $position
                !==
                $requiredPosition
            ) {

                $missingPlayers[] =
                    (
                        $requestedPlayer[
                            'name'
                        ]
                        ?? 'Unknown'
                    )
                    . ' (expected '
                    . $requiredPosition
                    . ', found '
                    . (
                        $position !== ''
                            ? $position
                            : 'unknown'
                    )
                    . ')';


                continue;
            }


            /*
             * ------------------------------------------------
             * CONFIDENCE NORMALISATION
             * ------------------------------------------------
             */

            $confidence =
                $summary[
                    'sample_confidence'
                ]
                ?? null;


            if (
                $confidence !== null
                &&
                is_numeric(
                    $confidence
                )
            ) {

                $confidence =
                    (float) $confidence;


                if (
                    $confidence > 1
                ) {

                    $confidence /=
                        100;
                }
            }


            /*
             * ------------------------------------------------
             * BUILD STANDARD SQUAD PLAYER
             * ------------------------------------------------
             */

            $players[] = [

                'player_id' =>
                    (int) (
                        $summary[
                            'player_id'
                        ]
                        ?? 0
                    ),

                'fpl_player_id' =>
                    isset(
                        $localPlayer[
                            'fpl_player_id'
                        ]
                    )
                        ? (int) $localPlayer[
                            'fpl_player_id'
                        ]
                        : null,

                'name' =>
                    $summary[
                        'name'
                    ]
                    ?? (
                        $localPlayer[
                            'web_name'
                        ]
                        ?? null
                    ),

                'team_id' =>
                    (int) (
                        $localPlayer[
                            'team_id'
                        ]
                        ?? 0
                    ),

                'team_name' =>
                    $summary[
                        'team_name'
                    ]
                    ?? null,

                'position' =>
                    $position,

                'price' =>
                    $summary[
                        'price'
                    ]
                    ?? null,

                'intelligence_score' =>
                    is_numeric(
                        $summary[
                            'intelligence_score'
                        ]
                        ?? null
                    )
                        ? (float) $summary[
                            'intelligence_score'
                        ]
                        : null,

                'strength_rating' =>
                    $summary[
                        'strength_rating'
                    ]
                    ?? null,

                'value_rating' =>
                    $summary[
                        'value_rating'
                    ]
                    ?? null,

                'fixture_rating' =>
                    $summary[
                        'fixture_rating'
                    ]
                    ?? null,

                'availability_rating' =>
                    $summary[
                        'availability_rating'
                    ]
                    ?? null,

                'sample_confidence' =>
                    $confidence,

                'verdict' =>
                    $summary[
                        'assessment_verdict'
                    ]
                    ?? null
            ];
        }
    }


    /*
     * ========================================================
     * REQUIRE ALL 15 PLAYERS
     * ========================================================
     */

    if (
        !empty(
            $missingPlayers
        )
        ||
        count(
            $players
        )
        !== 15
    ) {

        throw new RuntimeException(
            'Unable to resolve manual preview players: '
            . implode(
                ', ',
                $missingPlayers
            )
        );
    }


    /*
     * ========================================================
     * VALIDATE FPL POSITION COUNTS
     * ========================================================
     */

    $positionCounts = [

        'GK' =>
            0,

        'DEF' =>
            0,

        'MID' =>
            0,

        'FWD' =>
            0
    ];


    $teamCounts =
        [];


    foreach (
        $players
        as $player
    ) {

        $position =
            $player[
                'position'
            ]
            ?? '';


        if (
            isset(
                $positionCounts[
                    $position
                ]
            )
        ) {

            $positionCounts[
                $position
            ]++;
        }


        $teamId =
            (int) (
                $player[
                    'team_id'
                ]
                ?? 0
            );


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
    }


    if (
        $positionCounts[
            'GK'
        ]
        !== 2
        ||
        $positionCounts[
            'DEF'
        ]
        !== 5
        ||
        $positionCounts[
            'MID'
        ]
        !== 5
        ||
        $positionCounts[
            'FWD'
        ]
        !== 3
    ) {

        throw new RuntimeException(
            'Manual preview squad does not contain a valid FPL positional structure.'
        );
    }


    foreach (
        $teamCounts
        as $count
    ) {

        if (
            $count > 3
        ) {

            throw new RuntimeException(
                'Manual preview squad exceeds the three-player-per-club limit.'
            );
        }
    }


    /*
     * ========================================================
     * TEAM VALUE + BANK
     * ========================================================
     *
     * Treat £100.0m as the total FPL starting budget and
     * calculate the actual remaining bank from current prices.
     */

    $totalBudget =
        100.0;


    $teamValue =
        0.0;


    foreach (
        $players
        as $player
    ) {

        $teamValue +=
            (float) (
                $player[
                    'price'
                ]
                ?? 0
            );
    }


    $teamValue =
        round(
            $teamValue,
            1
        );


    $bank =
        round(
            $totalBudget
            -
            $teamValue,
            1
        );


    /*
     * ========================================================
     * COMPLETE PREVIEW IMPORT
     * ========================================================
     */

    return [

        'is_complete' =>
            true,

        'entry' => [

            'entry_id' =>
                0,

            'team_name' =>
                'GW1 Real Squad Preview',

            'manager_name' =>
                'Preview Mode'
        ],

        'gameweek' =>
            1,

        'team_value' =>
            $teamValue,

        'bank' =>
            $bank,

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
 * IMPORT SQUAD
 * ============================================================
 */

$importResult =
    null;


$mappedSquad =
    null;
    
    
$squadAnalysis =
    null;


$singleTransferResult =
    null;
    
    
$captainRecommendations =
    null;


$doubleTransferResult =
    null;


$pageError =
    null;

if (
    $previewMode
    &&
    $setupError === null
    &&
    $service !== null
    &&
    $playerRepository !== null
) {

    try {

        $mappedSquad =
            buildSquadPreview(
                $service,
                $playerRepository
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
                'Unable to build the development preview squad.';

        } else {

            $squadIntelligence =
                new SquadTransferIntelligence();


            $squadAnalysis =
                $squadIntelligence
                    ->analyzeSquad(
                        $mappedSquad[
                            'players'
                        ]
                        ?? [],
                        (float) (
                            $mappedSquad[
                                'bank'
                            ]
                            ?? 0
                        )
                    );
                    
            $singleTransferResult =
                $service
                    ->getSquadTransferRecommendations(
                        $mappedSquad[
                            'players'
                        ]
                        ?? [],
                        (float) (
                            $mappedSquad[
                                'bank'
                            ]
                            ?? 0
                        ),
                        3,
                        3
                    );
            
            $doubleTransferResult =
                $service
                    ->getSquadDoubleTransferRecommendations(
                        $mappedSquad[
                            'players'
                        ]
                        ?? [],
                        (float) (
                            $mappedSquad[
                                'bank'
                            ]
                            ?? 0
                        ),
                        5,
                        5
                    );
            
        }

    } catch (Throwable $exception) {

        $pageError =
            'Unable to build the development preview squad.';
    }
}


if (
    !$previewMode
    &&
    $entryId !== null
    &&
    $setupError === null
    &&
    $importer !== null
    &&
    $service !== null
) {

    try {

        $importResult =
            $importer
                ->importSquad(
                    $entryId
                );


        if ($importResult === null) {

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
            'success'
        ) {

            $mappedSquad =
                $service
                    ->buildSquadFromFPLImport(
                        $importResult
                    );
                    
            if (
                $mappedSquad !== null
                &&
                (
                    $mappedSquad[
                        'is_complete'
                    ]
                    ?? false
                )
            ) {

                $squadIntelligence =
                    new SquadTransferIntelligence();


                $squadAnalysis =
                    $squadIntelligence
                        ->analyzeSquad(
                            $mappedSquad[
                                'players'
                            ]
                            ?? [],
                            (float) (
                                $mappedSquad[
                                    'bank'
                                ]
                                ?? 0
                            )
                        );
                        
                $singleTransferResult =
                    $service
                        ->getSquadTransferRecommendations(
                            $mappedSquad[
                                'players'
                            ]
                            ?? [],
                            (float) (
                                $mappedSquad[
                                    'bank'
                                ]
                                ?? 0
                            ),
                            3,
                            3
                        );
                 

                $doubleTransferResult =
                    $service
                        ->getSquadDoubleTransferRecommendations(
                            $mappedSquad[
                                'players'
                            ]
                            ?? [],
                            (float) (
                                $mappedSquad[
                                    'bank'
                                ]
                                ?? 0
                            ),
                            5,
                            5
                        );
            }
        }

    } catch (Throwable $exception) {

        $pageError =
            'Unable to import this FPL squad at the moment.';
    }
}

/*
 * ============================================================
 * CAPTAIN INTELLIGENCE
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

        $captainRecommendations =
            $service
                ->getCaptainRecommendations(
                    $mappedSquad[
                        'players'
                    ]
                    ?? [],
                    5
                );


        if (
            (
                $captainRecommendations[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            $captainRecommendations =
                null;
        }

    } catch (Throwable $exception) {

        $captainRecommendations =
            null;
    }
}


/*
 * ============================================================
 * DISPLAY HELPERS
 * ============================================================
 */

function squadPagePrice(
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


function squadPageRating(
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

function squadPageSigned(
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


    $value =
        (float) $value;


    return (
        $value > 0
            ? '+'
            : ''
    )
    . number_format(
        $value,
        $decimals
    );
}

function squadMovementClass(
    mixed $value
): string {

    if (
        $value === null
        ||
        !is_numeric(
            $value
        )
    ) {

        return 'movement-neutral';
    }


    $value =
        (float) $value;


    if ($value > 0) {

        return 'movement-positive';
    }


    if ($value < 0) {

        return 'movement-negative';
    }


    return 'movement-neutral';
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
        content="Import and analyse your FPL squad using Squad Intelligence."
    >

    <title>
        Squad Intelligence | FPL Intelligence
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
                    Squad Decision Support
                </p>

                <h1>
                    Squad Intelligence
                </h1>

                <p class="topbar-subtitle">
                    Import your FPL squad and identify weaknesses,
                    transfer priorities and upgrade opportunities.
                </p>

            </div>

        </header>
        
        <main class="dashboard squad-dashboard">


            <!-- ==============================================
                 IMPORT PANEL
                 ============================================== -->

            <section class="dashboard-section">

                <div class="section-heading">

                    <p class="eyebrow">
                        Import Squad
                    </p>

                    <h2>
                        Load Your FPL Team
                    </h2>

                </div>


                <div class="squad-import-card">

                    <p class="squad-import-description">
                        Enter your FPL Entry ID and FPL Intelligence will
                        automatically load your squad for analysis.
                    </p>


                    <form
                        class="squad-import-form"
                        method="get"
                        action="squad.php"
                    >

                        <div class="squad-import-field">

                            <label for="entry_id">
                                FPL Entry ID
                            </label>

                            <input
                                type="number"
                                id="entry_id"
                                name="entry_id"
                                min="1"
                                step="1"
                                value="<?= htmlspecialchars(
                                    (string) (
                                        $entryId
                                        ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                placeholder="e.g. 2702264"
                                required
                            >

                        </div>


                        <button
                            class="squad-import-button"
                            type="submit"
                        >
                            Import Squad
                        </button>

                    </form>


                    <a
                        href="squad.php?preview=1"
                        class="squad-preview-button"
                    >
                        Preview Squad Intelligence
                    </a>


                    <p class="squad-import-help">
                        Your FPL Entry ID is the number shown in your
                        Fantasy Premier League team URL.
                    </p>

                </div>

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


            <?php if ($pageError !== null): ?>

                <section class="dashboard-section">

                    <div class="profile-panel">

                        <p>
                            <?= htmlspecialchars(
                                $pageError,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </p>

                    </div>

                </section>

            <?php endif; ?>


            <?php

            $importStatus =
                $importResult[
                    'status'
                ]
                ?? null;

            ?>


            <?php if (
                $importResult !== null
                &&
                $importStatus === 'no_public_squad'
            ): ?>

                <section class="dashboard-section">

                    <div class="profile-panel">

                        <p class="eyebrow">
                            Squad Not Public Yet
                        </p>

                        <h2>
                            FPL Entry Found
                        </h2>

                        <p>

                            <?= htmlspecialchars(
                                (string) (
                                    $importResult[
                                        'message'
                                    ]
                                    ??
                                    'This FPL squad is not publicly available yet.'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </p>

                        <?php

                        $entry =
                            $importResult[
                                'entry'
                            ]
                            ?? [];

                        ?>

                        <?php if (!empty($entry)): ?>

                            <p>

                                <strong>
                                    Team:
                                </strong>

                                <?= htmlspecialchars(
                                    (string) (
                                        $entry[
                                            'team_name'
                                        ]
                                        ?? 'Unknown'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </p>

                        <?php endif; ?>

                    </div>

                </section>

            <?php endif; ?>


            <?php if (
                $mappedSquad !== null
                &&
                (
                    $mappedSquad[
                        'is_complete'
                    ]
                    ?? false
                )
            ): ?>

                <?php

                $entry =
                    $mappedSquad[
                        'entry'
                    ]
                    ?? [];


                $players =
                    $mappedSquad[
                        'players'
                    ]
                    ?? [];

                ?>
                
                
                <?php if ($previewMode): ?>

                <div class="squad-preview-banner">
                    Development Preview Mode — this squad is generated from
                    current real FPL player data for interface testing and is
                    not an imported manager squad.
                </div>

            <?php endif; ?>


                <!-- ==============================================
                     SQUAD SUMMARY
                     ============================================== -->

                <section class="dashboard-section">

                    <div class="section-heading">

                        <div>

                            <p class="eyebrow">
                                Imported Squad
                            </p>

                            <h2>
                                <?= htmlspecialchars(
                                    (string) (
                                        $entry[
                                            'team_name'
                                        ]
                                        ?? 'FPL Squad'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </h2>

                        </div>

                    </div>


                    <div class="summary-card-grid">

                        <article class="summary-card">

                            <span class="summary-label">
                                Gameweek
                            </span>

                            <strong class="summary-value">

                                <?= htmlspecialchars(
                                    (string) (
                                        $mappedSquad[
                                            'gameweek'
                                        ]
                                        ?? '—'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </strong>

                        </article>


                        <article class="summary-card">

                            <span class="summary-label">
                                Squad Value
                            </span>

                            <strong class="summary-value">

                                <?= squadPagePrice(
                                    $mappedSquad[
                                        'team_value'
                                    ]
                                    ?? null
                                ); ?>

                            </strong>

                        </article>


                        <article class="summary-card">

                            <span class="summary-label">
                                Bank
                            </span>

                            <strong class="summary-value">

                                <?= squadPagePrice(
                                    $mappedSquad[
                                        'bank'
                                    ]
                                    ?? null
                                ); ?>

                            </strong>

                        </article>


                        <article class="summary-card">

                            <span class="summary-label">
                                Players
                            </span>

                            <strong class="summary-value">

                                <?= count(
                                    $players
                                ); ?>

                                / 15

                            </strong>

                        </article>

                    </div>

                </section>
                
                <?php if (
                    $captainRecommendations !== null
                    &&
                    (
                        $captainRecommendations[
                            'status'
                        ]
                        ?? null
                    )
                    ===
                    'success'
                ): ?>

                    <?php

                    $recommendedCaptain =
                        $captainRecommendations[
                            'captain'
                        ]
                        ?? null;


                    $recommendedViceCaptain =
                        $captainRecommendations[
                            'vice_captain'
                        ]
                        ?? null;


                    $captainAlternatives =
                        $captainRecommendations[
                            'alternatives'
                        ]
                        ?? [];

                    ?>


                    <!-- ==============================================
                         CAPTAIN INTELLIGENCE
                         ============================================== -->

                    <section class="dashboard-section captain-intelligence-section">

                        <div class="section-heading">

                            <div>

                                <p class="eyebrow">
                                    Captain Intelligence
                                </p>

                                <h2>
                                    Captain Recommendations
                                </h2>

                                <p class="section-description">
                                    Ranked captaincy options using the immediate fixture,
                                    player strength, attacking threat, confidence and
                                    availability.
                                </p>

                            </div>

                        </div>


                        <div class="captain-primary-grid">

                            <?php foreach (
                                [
                                    [
                                        'player' =>
                                            $recommendedCaptain,

                                        'label' =>
                                            'Captain',

                                        'rank' =>
                                            1,

                                        'class' =>
                                            'captain-primary-card-main'
                                    ],

                                    [
                                        'player' =>
                                            $recommendedViceCaptain,

                                        'label' =>
                                            'Vice-Captain',

                                        'rank' =>
                                            2,

                                        'class' =>
                                            ''
                                    ]
                                ]
                                as $captainCard
                            ): ?>

                                <?php

                                $captainPlayer =
                                    $captainCard[
                                        'player'
                                    ];


                                if (
                                    !is_array(
                                        $captainPlayer
                                    )
                                ) {

                                    continue;
                                }


                                $components =
                                    $captainPlayer[
                                        'components'
                                    ]
                                    ?? [];

                                ?>

                                <a
                                    class="captain-primary-card <?= htmlspecialchars(
                                        $captainCard[
                                            'class'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                    href="player.php?id=<?= (int) (
                                        $captainPlayer[
                                            'player_id'
                                        ]
                                        ?? 0
                                    ); ?>"
                                >

                                    <div class="captain-card-top">

                                        <span class="captain-role-badge">
                                            <?= htmlspecialchars(
                                                $captainCard[
                                                    'label'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>
                                        </span>

                                        <span class="captain-rank">
                                            #<?= (int) $captainCard[
                                                'rank'
                                            ]; ?>
                                        </span>

                                    </div>


                                    <strong class="captain-player-name">
                                        <?= htmlspecialchars(
                                            (string) (
                                                $captainPlayer[
                                                    'name'
                                                ]
                                                ?? 'Unknown'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </strong>


                                    <span class="captain-player-meta">

                                        <?= htmlspecialchars(
                                            (string) (
                                                $captainPlayer[
                                                    'team_name'
                                                ]
                                                ?? 'Unknown'
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        ·

                                        <?= htmlspecialchars(
                                            (string) (
                                                $captainPlayer[
                                                    'position'
                                                ]
                                                ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </span>


                                    <div class="captain-score-row">

                                        <div>

                                            <span>
                                                Captain Score
                                            </span>

                                            <strong>
                                                <?= squadPageRating(
                                                    $captainPlayer[
                                                        'captain_score'
                                                    ]
                                                    ?? null
                                                ); ?>
                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Level
                                            </span>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $captainPlayer[
                                                            'classification'
                                                        ]
                                                        ?? '—'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Fixture
                                            </span>

                                            <strong>
                                                <?= squadPageRating(
                                                    $components[
                                                        'fixture'
                                                    ]
                                                    ?? null
                                                ); ?>
                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Threat
                                            </span>

                                            <strong>
                                                <?= squadPageRating(
                                                    $components[
                                                        'attacking_threat'
                                                    ]
                                                    ?? null
                                                ); ?>
                                            </strong>

                                        </div>

                                    </div>


                                    <?php if (
                                        (
                                            $captainPlayer[
                                                'current_is_captain'
                                            ]
                                            ?? false
                                        )
                                        === true
                                    ): ?>

                                        <span class="captain-current-note">
                                            Current FPL Captain
                                        </span>

                                    <?php elseif (
                                        (
                                            $captainPlayer[
                                                'current_is_vice_captain'
                                            ]
                                            ?? false
                                        )
                                        === true
                                    ): ?>

                                        <span class="captain-current-note">
                                            Current FPL Vice-Captain
                                        </span>

                                    <?php endif; ?>

                                </a>

                            <?php endforeach; ?>

                        </div>


                        <?php if (
                            !empty(
                                $captainAlternatives
                            )
                        ): ?>

                            <div class="captain-alternatives">

                                <p class="captain-alternatives-heading">
                                    Other Captaincy Options
                                </p>


                                <div class="captain-alternatives-grid">

                                    <?php foreach (
                                        $captainAlternatives
                                        as $alternative
                                    ): ?>

                                        <a
                                            class="captain-alternative-card"
                                            href="player.php?id=<?= (int) (
                                                $alternative[
                                                    'player_id'
                                                ]
                                                ?? 0
                                            ); ?>"
                                        >

                                            <span class="captain-alternative-rank">
                                                #<?= (int) (
                                                    $alternative[
                                                        'rank'
                                                    ]
                                                    ?? 0
                                                ); ?>
                                            </span>


                                            <strong>
                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $alternative[
                                                            'name'
                                                        ]
                                                        ?? 'Unknown'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </strong>


                                            <span>
                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $alternative[
                                                            'team_name'
                                                        ]
                                                        ?? 'Unknown'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                                ·

                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $alternative[
                                                            'position'
                                                        ]
                                                        ?? ''
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </span>


                                            <span>
                                                Captain
                                                <?= squadPageRating(
                                                    $alternative[
                                                        'captain_score'
                                                    ]
                                                    ?? null
                                                ); ?>

                                                ·

                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $alternative[
                                                            'classification'
                                                        ]
                                                        ?? '—'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </span>

                                        </a>

                                    <?php endforeach; ?>

                                </div>

                            </div>

                        <?php endif; ?>

                    </section>

                <?php endif; ?>
                
                <?php if (
                    $squadAnalysis !== null
                    &&
                    (
                        $squadAnalysis[
                            'validation'
                        ]['is_valid']
                        ?? false
                    )
                ): ?>

                    <?php

                    $analysisSummary =
                        $squadAnalysis[
                            'summary'
                        ]
                        ?? [];


                    $priorityRanking =
                        $squadAnalysis[
                            'ranking'
                        ]
                        ?? [];


                    $highestPriority =
                        $priorityRanking[0]
                        ?? [];

                    ?>


                    <!-- ==============================================
                         SQUAD INTELLIGENCE SUMMARY
                         ============================================== -->

                    <section class="dashboard-section">

                        <div class="section-heading">

                            <div>

                                <p class="eyebrow">
                                    Squad Analysis
                                </p>

                                <h2>
                                    Squad Intelligence Summary
                                </h2>

                            </div>

                        </div>


                        <div class="squad-summary-grid">

                            <article class="squad-summary-card">

                                <span class="squad-summary-label">
                                    Average Intelligence
                                </span>

                                <strong class="squad-summary-value">

                                    <?= squadPageRating(
                                        $analysisSummary[
                                            'average_intelligence'
                                        ]
                                        ?? null
                                    ); ?>

                                </strong>

                                <span class="squad-summary-note">
                                    Overall squad quality
                                </span>

                            </article>


                            <article class="squad-summary-card">

                                <span class="squad-summary-label">
                                    Weakest Position
                                </span>

                                <strong class="squad-summary-value">

                                    <?= htmlspecialchars(
                                        (string) (
                                            $analysisSummary[
                                                'weakest_position'
                                            ]
                                            ?? '—'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>

                                <span class="squad-summary-note">
                                    Lowest positional average
                                </span>

                            </article>


                            <article class="squad-summary-card">

                                <span class="squad-summary-label">
                                    Highest Priority
                                </span>

                                <strong class="squad-summary-value">

                                    <?= htmlspecialchars(
                                        (string) (
                                            $highestPriority[
                                                'name'
                                            ]
                                            ?? '—'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>

                                <span class="squad-summary-note">

                                    Priority

                                    <?= squadPageRating(
                                        $highestPriority[
                                            'transfer_priority'
                                        ]
                                        ?? null
                                    ); ?>

                                </span>

                            </article>


                            <article class="squad-summary-card">

                                <span class="squad-summary-label">
                                    Bank
                                </span>

                                <strong class="squad-summary-value">

                                    <?= squadPagePrice(
                                        $squadAnalysis[
                                            'bank'
                                        ]
                                        ?? null
                                    ); ?>

                                </strong>

                                <span class="squad-summary-note">
                                    Available transfer funds
                                </span>

                            </article>

                        </div>

                    </section>


                    <!-- ==============================================
                         POSITION INTELLIGENCE
                         ============================================== -->

                    <section class="dashboard-section">

                        <div class="section-heading">

                            <div>

                                <p class="eyebrow">
                                    Position Analysis
                                </p>

                                <h2>
                                    Intelligence by Position
                                </h2>

                            </div>

                        </div>


                        <div class="squad-position-grid">

                            <?php

                            $positionAverages =
                                $analysisSummary[
                                    'position_averages'
                                ]
                                ?? [];

                            ?>


                            <?php foreach (
                                [
                                    'GK' =>
                                        'Goalkeepers',

                                    'DEF' =>
                                        'Defenders',

                                    'MID' =>
                                        'Midfielders',

                                    'FWD' =>
                                        'Forwards'
                                ]
                                as $positionCode => $positionLabel
                            ): ?>

                                <article class="squad-position-card">

                                    <span class="squad-position-code">
                                        <?= $positionCode; ?>
                                    </span>

                                    <div>

                                        <span class="squad-position-label">

                                            <?= htmlspecialchars(
                                                $positionLabel,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                        <strong class="squad-position-value">

                                            <?= squadPageRating(
                                                $positionAverages[
                                                    $positionCode
                                                ]
                                                ?? null
                                            ); ?>

                                        </strong>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    </section>


                    <!-- ==============================================
                         TRANSFER PRIORITIES
                         ============================================== -->

                    <section class="dashboard-section">

                        <div class="section-heading">

                            <div>

                                <p class="eyebrow">
                                    Transfer Priorities
                                </p>

                                <h2>
                                    Players to Review
                                </h2>

                            </div>

                        </div>


                        <div class="profile-panel">

                            <div class="table-wrapper">

                                <table class="player-table">

                                    <thead>

                                        <tr>

                                            <th>
                                                Rank
                                            </th>

                                            <th>
                                                Player
                                            </th>

                                            <th>
                                                Pos
                                            </th>

                                            <th>
                                                Price
                                            </th>

                                            <th>
                                                Priority
                                            </th>

                                            <th>
                                                Level
                                            </th>

                                            <th>
                                                Intelligence
                                            </th>

                                            <th>
                                                Reason
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>

                                        <?php foreach (
                                            array_slice(
                                                $priorityRanking,
                                                0,
                                                10
                                            )
                                            as $priorityPlayer
                                        ): ?>

                                            <tr>

                                                <td>

                                                    #<?= (int) (
                                                        $priorityPlayer[
                                                            'squad_rank'
                                                        ]
                                                        ?? 0
                                                    ); ?>

                                                </td>


                                                <td>

                                                    <a
                                                        href="player.php?id=<?= (int) (
                                                            $priorityPlayer[
                                                                'player_id'
                                                            ]
                                                            ?? 0
                                                        ); ?>"
                                                    >

                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $priorityPlayer[
                                                                    'name'
                                                                ]
                                                                ?? 'Unknown'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </a>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $priorityPlayer[
                                                                'position'
                                                            ]
                                                            ?? '—'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </td>


                                                <td>

                                                    <?= squadPagePrice(
                                                        $priorityPlayer[
                                                            'price'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                </td>


                                                <td>

                                                    <strong>

                                                        <?= squadPageRating(
                                                            $priorityPlayer[
                                                                'transfer_priority'
                                                            ]
                                                            ?? null
                                                        ); ?>

                                                    </strong>

                                                </td>


                                                <td>

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $priorityPlayer[
                                                                'priority_label'
                                                            ]
                                                            ?? '—'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </td>


                                                <td>

                                                    <?= squadPageRating(
                                                        $priorityPlayer[
                                                            'intelligence_score'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                </td>


                                                <td>

                                                    <?php

                                                    $reasons =
                                                        $priorityPlayer[
                                                            'priority_reasons'
                                                        ]
                                                        ?? [];

                                                    ?>

                                                    <?= htmlspecialchars(
                                                        !empty($reasons)
                                                            ? implode(
                                                                '; ',
                                                                $reasons
                                                            )
                                                            : 'No major concerns',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </section>
                    
                    <!-- ==============================================
                         SINGLE TRANSFER RECOMMENDATIONS
                         ============================================== -->

                    <?php

                    $singleRecommendations =
                        $singleTransferResult[
                            'recommendations'
                        ]['recommendations']
                        ?? [];

                    ?>


                    <?php if (!empty($singleRecommendations)): ?>

                        <section class="dashboard-section squad-action-section squad-action-section-single">

                            <div class="section-heading">

                                <div>

                                    <p class="eyebrow">
                                        Recommended Transfers
                                    </p>

                                    <h2>
                                        Best Single Moves
                                    </h2>

                                    <p class="section-description">
                                        Ranked replacements based on your squad weaknesses,
                                        available budget and transfer intelligence.
                                    </p>

                                </div>

                            </div>


                            <div class="squad-transfer-list">

                                <?php foreach (
                                    $singleRecommendations
                                    as $recommendation
                                ): ?>

                                    <?php

                                    $outgoing =
                                        $recommendation[
                                            'outgoing'
                                        ]
                                        ?? [];


                                    $replacements =
                                        $recommendation[
                                            'replacements'
                                        ]
                                        ?? [];

                                    ?>


                                    <article class="squad-transfer-card">

                                        <div class="squad-transfer-card-header">

                                            <div>

                                                <span class="squad-transfer-priority">

                                                    Priority #

                                                    <?= (int) (
                                                        $outgoing[
                                                            'squad_rank'
                                                        ]
                                                        ?? 0
                                                    ); ?>

                                                </span>


                                                <h3>

                                                    <a
                                                        href="player.php?id=<?= (int) (
                                                            $outgoing[
                                                                'player_id'
                                                            ]
                                                            ?? 0
                                                        ); ?>"
                                                    >

                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $outgoing[
                                                                    'name'
                                                                ]
                                                                ?? 'Unknown'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </a>

                                                </h3>


                                                <p class="squad-transfer-outgoing-meta">

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $outgoing[
                                                                'position'
                                                            ]
                                                            ?? '—'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                    ·

                                                    <?= squadPagePrice(
                                                        $outgoing[
                                                            'price'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                    · Priority

                                                    <?= squadPageRating(
                                                        $recommendation[
                                                            'transfer_priority'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                </p>

                                            </div>


                                            <span class="squad-transfer-level">

                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $recommendation[
                                                            'priority_label'
                                                        ]
                                                        ?? '—'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </span>

                                        </div>


                                        <div class="squad-replacement-list">

                                            <?php foreach (
                                                $replacements
                                                as $replacement
                                            ): ?>

                                                <?php

                                                $incoming =
                                                    $replacement[
                                                        'player'
                                                    ]
                                                    ?? [];


                                                $decision =
                                                    $replacement[
                                                        'decision'
                                                    ]
                                                    ?? [];


                                                $movements =
                                                    $decision[
                                                        'movements'
                                                    ]
                                                    ?? [];

                                                ?>


                                                <div class="squad-replacement-row">

                                                    <div class="squad-replacement-rank">

                                                        #<?= (int) (
                                                            $replacement[
                                                                'rank'
                                                            ]
                                                            ?? 0
                                                        ); ?>

                                                    </div>


                                                    <div class="squad-replacement-player">

                                                        <a
                                                            href="player.php?id=<?= (int) (
                                                                $incoming[
                                                                    'player_id'
                                                                ]
                                                                ?? 0
                                                            ); ?>"
                                                        >

                                                            <?= htmlspecialchars(
                                                                (string) (
                                                                    $incoming[
                                                                        'name'
                                                                    ]
                                                                    ?? 'Unknown'
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                        </a>


                                                        <span>

                                                            <?= htmlspecialchars(
                                                                (string) (
                                                                    $incoming[
                                                                        'team_name'
                                                                    ]
                                                                    ?? '—'
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                            ·

                                                            <?= squadPagePrice(
                                                                $incoming[
                                                                    'price'
                                                                ]
                                                                ?? null
                                                            ); ?>

                                                        </span>

                                                    </div>


                                                    <div class="squad-replacement-decision">

                                                        <span class="squad-decision-type">

                                                            <?= htmlspecialchars(
                                                                (string) (
                                                                    $replacement[
                                                                        'decision_type'
                                                                    ]
                                                                    ?? '—'
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>

                                                        </span>


                                                        <strong>

                                                            <?= squadPageRating(
                                                                $replacement[
                                                                    'decision_score'
                                                                ]
                                                                ?? null
                                                            ); ?>

                                                        </strong>

                                                    </div>


                                                    <div class="squad-replacement-movements">

                                                        <span>
                                                            INT
                                                            <strong
                                                                class="<?= squadMovementClass(
                                                                    $movements[
                                                                        'intelligence'
                                                                    ]
                                                                    ?? null
                                                                ); ?>"
                                                            >
                                                                <?= squadPageSigned(
                                                                    $movements[
                                                                        'intelligence'
                                                                    ]
                                                                    ?? null
                                                                ); ?>
                                                            </strong>
                                                        </span>

                                                        <span>
                                                            STR
                                                            <strong
                                                                class="<?= squadMovementClass(
                                                                    $movements[
                                                                        'strength'
                                                                    ]
                                                                    ?? null
                                                                ); ?>"
                                                            >
                                                                <?= squadPageSigned(
                                                                    $movements[
                                                                        'strength'
                                                                    ]
                                                                    ?? null
                                                                ); ?>
                                                            </strong>
                                                        </span>

                                                        <span>
                                                            FIX
                                                            <strong
                                                                class="<?= squadMovementClass(
                                                                    $movements[
                                                                        'fixtures'
                                                                    ]
                                                                    ?? null
                                                                ); ?>"
                                                            >
                                                                <?= squadPageSigned(
                                                                    $movements[
                                                                        'fixtures'
                                                                    ]
                                                                    ?? null
                                                                ); ?>
                                                            </strong>
                                                        </span>

                                                    </div>


                                                    <div class="squad-replacement-budget">

                                                        <span>
                                                            Bank After
                                                        </span>

                                                        <strong>

                                                            <?= squadPagePrice(
                                                                $replacement[
                                                                    'budget_after'
                                                                ]
                                                                ?? null
                                                            ); ?>

                                                        </strong>

                                                    </div>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>

                                    </article>

                                <?php endforeach; ?>

                            </div>

                        </section>

                    <?php endif; ?>
                    
                    <!-- ==============================================
                         DOUBLE TRANSFER RECOMMENDATIONS
                         ============================================== -->

                    <?php

                    $doubleRecommendations =
                        $doubleTransferResult[
                            'recommendations'
                        ]['combinations']
                        ?? [];

                    ?>


                    <?php if (!empty($doubleRecommendations)): ?>

                        <section class="dashboard-section squad-action-section squad-action-section-double">

                            <div class="section-heading">

                                <div>

                                    <p class="eyebrow">
                                        Squad Restructures
                                    </p>

                                    <h2>
                                        Best Double Transfers
                                    </h2>

                                    <p class="section-description">
                                        Two-transfer plans ranked using combination quality,
                                        squad weaknesses and available budget.
                                    </p>

                                </div>

                            </div>


                            <div class="squad-double-transfer-list">

                                <?php foreach (
                                    $doubleRecommendations
                                    as $combination
                                ): ?>

                                    <?php

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


                                    $currentA =
                                        $transferA[
                                            'current_player'
                                        ]
                                        ?? [];


                                    $incomingA =
                                        $transferA[
                                            'replacement'
                                        ]
                                        ?? [];


                                    $currentB =
                                        $transferB[
                                            'current_player'
                                        ]
                                        ?? [];


                                    $incomingB =
                                        $transferB[
                                            'replacement'
                                        ]
                                        ?? [];


                                    $movements =
                                        $combination[
                                            'combined_movements'
                                        ]
                                        ?? [];


                                    $squadOptimizer =
                                        $combination[
                                            'squad_optimizer'
                                        ]
                                        ?? [];

                                    ?>


                                    <article class="squad-double-transfer-card">


                                        <!-- ======================================
                                             HEADER
                                             ====================================== -->

                                        <div class="squad-double-header">

                                            <div>

                                                <span class="squad-double-rank">

                                                    Plan #

                                                    <?= (int) (
                                                        $squadOptimizer[
                                                            'rank'
                                                        ]
                                                        ?? 0
                                                    ); ?>

                                                </span>


                                                <h3>

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $combination[
                                                                'classification'
                                                            ]
                                                            ?? 'Transfer Plan'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                </h3>

                                            </div>


                                            <div class="squad-double-score">

                                                <span>
                                                    Squad Score
                                                </span>

                                                <strong>

                                                    <?= squadPageRating(
                                                        $squadOptimizer[
                                                            'squad_score'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                </strong>

                                            </div>

                                        </div>


                                        <!-- ======================================
                                             TRANSFERS
                                             ====================================== -->

                                        <div class="squad-double-transfers">


                                            <div class="squad-double-move">

                                                <span class="squad-double-label">
                                                    Transfer A
                                                </span>


                                                <div class="squad-double-player-line">

                                                    <a
                                                        href="player.php?id=<?= (int) (
                                                            $currentA[
                                                                'player_id'
                                                            ]
                                                            ?? 0
                                                        ); ?>"
                                                    >

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

                                                    </a>


                                                    <span class="squad-double-arrow">
                                                        →
                                                    </span>


                                                    <a
                                                        href="player.php?id=<?= (int) (
                                                            $incomingA[
                                                                'player_id'
                                                            ]
                                                            ?? 0
                                                        ); ?>"
                                                    >

                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $incomingA[
                                                                    'name'
                                                                ]
                                                                ?? 'Unknown'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </a>

                                                </div>


                                                <p>

                                                    <?= squadPagePrice(
                                                        $currentA[
                                                            'price'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                    →

                                                    <?= squadPagePrice(
                                                        $incomingA[
                                                            'price'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                    ·

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $transferA[
                                                                'decision_type'
                                                            ]
                                                            ?? '—'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                    · Score

                                                    <?= squadPageRating(
                                                        $transferA[
                                                            'decision_score'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                </p>

                                            </div>


                                            <div class="squad-double-move">

                                                <span class="squad-double-label">
                                                    Transfer B
                                                </span>


                                                <div class="squad-double-player-line">

                                                    <a
                                                        href="player.php?id=<?= (int) (
                                                            $currentB[
                                                                'player_id'
                                                            ]
                                                            ?? 0
                                                        ); ?>"
                                                    >

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

                                                    </a>


                                                    <span class="squad-double-arrow">
                                                        →
                                                    </span>


                                                    <a
                                                        href="player.php?id=<?= (int) (
                                                            $incomingB[
                                                                'player_id'
                                                            ]
                                                            ?? 0
                                                        ); ?>"
                                                    >

                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $incomingB[
                                                                    'name'
                                                                ]
                                                                ?? 'Unknown'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </a>

                                                </div>


                                                <p>

                                                    <?= squadPagePrice(
                                                        $currentB[
                                                            'price'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                    →

                                                    <?= squadPagePrice(
                                                        $incomingB[
                                                            'price'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                    ·

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $transferB[
                                                                'decision_type'
                                                            ]
                                                            ?? '—'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>

                                                    · Score

                                                    <?= squadPageRating(
                                                        $transferB[
                                                            'decision_score'
                                                        ]
                                                        ?? null
                                                    ); ?>

                                                </p>

                                            </div>

                                        </div>


                                        <!-- ======================================
                                             MOVEMENTS
                                             ====================================== -->

                                        <div class="squad-double-metrics">

                                            <div>

                                                <span>
                                                    Intelligence
                                                </span>

                                                <strong
                                                    class="<?= squadMovementClass(
                                                        $movements[
                                                            'intelligence'
                                                        ]
                                                        ?? null
                                                    ); ?>"
                                                >
                                                    <?= squadPageSigned(
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
                                                
                                                <strong
                                                    class="<?= squadMovementClass(
                                                        $movements[
                                                            'strength'
                                                        ]
                                                        ?? null
                                                    ); ?>"
                                                >
                                                    <?= squadPageSigned(
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

                                                <strong
                                                    class="<?= squadMovementClass(
                                                        $movements[
                                                            'value'
                                                        ]
                                                        ?? null
                                                    ); ?>"
                                                >
                                                    <?= squadPageSigned(
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

                                                <strong
                                                    class="<?= squadMovementClass(
                                                        $movements[
                                                            'fixtures'
                                                        ]
                                                        ?? null
                                                    ); ?>"
                                                >
                                                    <?= squadPageSigned(
                                                        $movements[
                                                            'fixtures'
                                                        ]
                                                        ?? null
                                                    ); ?>
                                                </strong>

                                            </div>


                                            <div>

                                                <span>
                                                    Bank After
                                                </span>

                                                <strong>

                                                    <?= squadPagePrice(
                                                        $combination[
                                                            'optimizer'
                                                        ]['budget_after']
                                                        ?? null
                                                    ); ?>

                                                </strong>

                                            </div>

                                        </div>


                                        <!-- ======================================
                                             SUMMARY
                                             ====================================== -->

                                        <?php if (
                                            !empty(
                                                $squadOptimizer[
                                                    'summary'
                                                ]
                                                ?? null
                                            )
                                        ): ?>

                                            <div class="squad-double-summary">

                                                <?= htmlspecialchars(
                                                    (string) $squadOptimizer[
                                                        'summary'
                                                    ],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </div>

                                        <?php endif; ?>


                                    </article>

                                <?php endforeach; ?>

                            </div>

                        </section>

                    <?php endif; ?>

                <?php endif; ?>


                <!-- ==============================================
                     IMPORTED PLAYERS
                     ============================================== -->

                <section class="dashboard-section">

                    <div class="section-heading">

                        <div>

                            <p class="eyebrow">
                                Current Squad
                            </p>

                            <h2>
                                Imported Players
                            </h2>

                        </div>

                    </div>
                    
                    <button
                        type="button"
                        class="squad-collapse-toggle"
                        data-squad-toggle="current-squad"
                        aria-expanded="false"
                    >
                        <span>Show Current Squad</span>
                        <span class="squad-collapse-icon">+</span>
                    </button>

                    <div
                        class="squad-collapsible"
                        data-squad-panel="current-squad"
                        hidden
                    >


                    <div class="profile-panel">

                        <div class="table-wrapper">

                            <table class="player-table">

                                <thead>

                                    <tr>

                                        <th>
                                            Player
                                        </th>

                                        <th>
                                            Team
                                        </th>

                                        <th>
                                            Pos
                                        </th>

                                        <th>
                                            Price
                                        </th>

                                        <th>
                                            Intelligence
                                        </th>

                                        <th>
                                            Strength
                                        </th>

                                        <th>
                                            Value
                                        </th>

                                        <th>
                                            Fixtures
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $players
                                        as $player
                                    ): ?>

                                        <tr>

                                            <td>

                                                <a
                                                    href="player.php?id=<?= (int) (
                                                        $player[
                                                            'player_id'
                                                        ]
                                                        ?? 0
                                                    ); ?>"
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

                                                </a>

                                            </td>


                                            <td>

                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $player[
                                                            'team_name'
                                                        ]
                                                        ?? '—'
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </td>


                                            <td>

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

                                            </td>


                                            <td>

                                                <?= squadPagePrice(
                                                    $player[
                                                        'price'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </td>


                                            <td>

                                                <?= squadPageRating(
                                                    $player[
                                                        'intelligence_score'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </td>


                                            <td>

                                                <?= squadPageRating(
                                                    $player[
                                                        'strength_rating'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </td>


                                            <td>

                                                <?= squadPageRating(
                                                    $player[
                                                        'value_rating'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </td>


                                            <td>

                                                <?= squadPageRating(
                                                    $player[
                                                        'fixture_rating'
                                                    ]
                                                    ?? null
                                                ); ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </section>


            <?php elseif (
                $mappedSquad !== null
                &&
                !(
                    $mappedSquad[
                        'is_complete'
                    ]
                    ?? false
                )
            ): ?>

                <section class="dashboard-section">

                    <div class="profile-panel">

                        <p class="eyebrow">
                            Import Incomplete
                        </p>

                        <h2>
                            Some Players Could Not Be Mapped
                        </h2>

                        <p>

                            Imported:

                            <?= (int) (
                                $mappedSquad[
                                    'imported_count'
                                ]
                                ?? 0
                            ); ?>

                            · Mapped:

                            <?= (int) (
                                $mappedSquad[
                                    'mapped_count'
                                ]
                                ?? 0
                            ); ?>

                            · Unmapped:

                            <?= (int) (
                                $mappedSquad[
                                    'unmapped_count'
                                ]
                                ?? 0
                            ); ?>

                        </p>

                    </div>

                </section>

            <?php endif; ?>

        </main>
        
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const toggles =
        document.querySelectorAll(
            '[data-squad-toggle]'
        );

    toggles.forEach(function (toggle) {

        toggle.addEventListener(
            'click',
            function () {

                const panelName =
                    toggle.dataset.squadToggle;

                const panel =
                    document.querySelector(
                        '[data-squad-panel="' +
                        panelName +
                        '"]'
                    );

                if (!panel) {
                    return;
                }

                const isOpen =
                    toggle.getAttribute(
                        'aria-expanded'
                    ) === 'true';


                toggle.setAttribute(
                    'aria-expanded',
                    isOpen
                        ? 'false'
                        : 'true'
                );


                panel.hidden =
                    isOpen;


                const label =
                    toggle.querySelector(
                        'span:first-child'
                    );

                const icon =
                    toggle.querySelector(
                        '.squad-collapse-icon'
                    );


                if (label) {

                    label.textContent =
                        isOpen
                            ? 'Show Current Squad'
                            : 'Hide Current Squad';

                }


                if (icon) {

                    icon.textContent =
                        isOpen
                            ? '+'
                            : '−';

                }

            }
        );

    });

});
</script>

</body>

</html>