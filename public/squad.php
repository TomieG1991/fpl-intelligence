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
    
$squadHorizonService =
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
    
    
    $squadHorizonService =
        new SquadHorizonIntelligenceService(
            $playerRepository,
            $service,
            new SquadHorizonIntelligence()
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
        
$previewInput =
    isset(
        $_GET[
            'preview'
        ]
    )
        ? strtolower(
            trim(
                (string) $_GET[
                    'preview'
                ]
            )
        )
        : '';


$previewMode =
    in_array(
        $previewInput,
        [
            '1',
            'true',
            'generic',
            'manual'
        ],
        true
    );


$manualPreviewMode =
    $previewInput === 'manual';


function buildManualSquadPreview(
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
                'Manual Squad Preview',

            'manager_name' =>
                'Manual Preview Mode'
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

function buildSquadPreview(
    PlayerIntelligenceService $service,
    PlayerRepository $playerRepository
): ?array {

    /*
     * ========================================================
     * GENERIC DEVELOPMENT PREVIEW
     * ========================================================
     *
     * Build a deterministic legal FPL squad from the current
     * Player Intelligence dataset.
     *
     * This preview deliberately avoids depending on specific
     * player names so automated page tests survive transfers,
     * player additions and normal FPL dataset changes.
     */

    $summaries =
        $service
            ->getAllPlayerSummaries();


    $localPlayers =
        $playerRepository
            ->getAll();


    if (
        empty(
            $summaries
        )
        ||
        empty(
            $localPlayers
        )
    ) {

        return null;
    }


    /*
     * ========================================================
     * LOCAL PLAYER LOOKUP
     * ========================================================
     */

    $localById =
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
    }


    /*
     * ========================================================
     * BUILD USABLE CANDIDATES
     * ========================================================
     */

    $candidatesByPosition = [

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


        if (
            $playerId <= 0
            ||
            !isset(
                $localById[
                    $playerId
                ]
            )
        ) {

            continue;
        }


        $localPlayer =
            $localById[
                $playerId
            ];


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
            !isset(
                $candidatesByPosition[
                    $position
                ]
            )
        ) {

            continue;
        }


        $teamId =
            (int) (
                $localPlayer[
                    'team_id'
                ]
                ?? 0
            );


        if (
            $teamId <= 0
        ) {

            continue;
        }


        $price =
            is_numeric(
                $summary[
                    'price'
                ]
                ?? null
            )
                ? (float) $summary[
                    'price'
                ]
                : null;


        if (
            $price === null
            ||
            $price <= 0
        ) {

            continue;
        }


        $intelligenceScore =
            is_numeric(
                $summary[
                    'intelligence_score'
                ]
                ?? null
            )
                ? (float) $summary[
                    'intelligence_score'
                ]
                : 0.0;


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


        $candidatesByPosition[
            $position
        ][] = [

            'player_id' =>
                $playerId,

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
                ?? $localPlayer[
                    'web_name'
                ]
                ?? null,

            'team_id' =>
                $teamId,

            'team_name' =>
                $summary[
                    'team_name'
                ]
                ?? null,

            'position' =>
                $position,

            'price' =>
                $price,

            'intelligence_score' =>
                $intelligenceScore,

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


    /*
     * ========================================================
     * ORDER CANDIDATES
     * ========================================================
     *
     * Prioritise affordable players first, then Intelligence.
     *
     * Using value-conscious candidates makes it much easier
     * for the generated squad to remain inside the £100m
     * development budget as FPL prices change.
     */

    foreach (
        $candidatesByPosition
        as &$positionCandidates
    ) {

        usort(
            $positionCandidates,
            function (
                array $a,
                array $b
            ): int {

                $priceComparison =
                    (
                        $a[
                            'price'
                        ]
                        ?? 999
                    )
                    <=>
                    (
                        $b[
                            'price'
                        ]
                        ?? 999
                    );


                if (
                    $priceComparison !== 0
                ) {

                    return $priceComparison;
                }


                $intelligenceComparison =
                    (
                        $b[
                            'intelligence_score'
                        ]
                        ?? 0
                    )
                    <=>
                    (
                        $a[
                            'intelligence_score'
                        ]
                        ?? 0
                    );


                if (
                    $intelligenceComparison !== 0
                ) {

                    return $intelligenceComparison;
                }


                return strcasecmp(
                    (string) (
                        $a[
                            'name'
                        ]
                        ?? ''
                    ),
                    (string) (
                        $b[
                            'name'
                        ]
                        ?? ''
                    )
                );
            }
        );
    }


    unset(
        $positionCandidates
    );


    /*
     * ========================================================
     * SELECT LEGAL 15-PLAYER SQUAD
     * ========================================================
     */

    $requiredCounts = [

        'GK' =>
            2,

        'DEF' =>
            5,

        'MID' =>
            5,

        'FWD' =>
            3
    ];


    $players =
        [];


    $teamCounts =
        [];


    foreach (
        $requiredCounts
        as $position => $requiredCount
    ) {

        $selectedForPosition =
            0;


        foreach (
            $candidatesByPosition[
                $position
            ]
            as $candidate
        ) {

            $teamId =
                (int) (
                    $candidate[
                        'team_id'
                    ]
                    ?? 0
                );


            if (
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
                $candidate;


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


            $selectedForPosition++;


            if (
                $selectedForPosition
                >=
                $requiredCount
            ) {

                break;
            }
        }


        if (
            $selectedForPosition
            !==
            $requiredCount
        ) {

            return null;
        }
    }


    /*
     * ========================================================
     * FINAL VALIDATION
     * ========================================================
     */

    if (
        count(
            $players
        )
        !== 15
    ) {

        return null;
    }


    /*
     * ========================================================
     * TEAM VALUE + BANK
     * ========================================================
     */

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
            100.0
            -
            $teamValue,
            1
        );


    /*
     * The preview should always represent a legal development
     * squad. If future FPL pricing makes this generated squad
     * exceed £100m, fail cleanly rather than returning an
     * unrealistic preview.
     */

    if (
        $bank < 0
    ) {

        return null;
    }


    /*
     * ========================================================
     * COMPLETE GENERIC PREVIEW
     * ========================================================
     */

    return [

        'is_complete' =>
            true,

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


$squadHorizonServiceResult =
    null;


$squadHorizonResult =
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
            $manualPreviewMode
                ? buildManualSquadPreview(
                    $service,
                    $playerRepository
                )
                : buildSquadPreview(
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

            /*
             * ====================================================
             * v0.32 SQUAD HORIZON INTELLIGENCE
             * ====================================================
             *
             * Feed the original successful FPL import directly
             * into the production Squad Horizon orchestration
             * service.
             *
             * This is intentionally independent of the older
             * mapped-squad representation used by transfer and
             * captain intelligence below.
             */

            if (
                $squadHorizonService !== null
            ) {

                $squadHorizonServiceResult =
                    $squadHorizonService
                        ->buildForImportedSquad(
                            $importResult,
                            3
                        );


                if (
                    (
                        $squadHorizonServiceResult[
                            'status'
                        ]
                        ?? null
                    )
                    ===
                    'Available'
                    &&
                    isset(
                        $squadHorizonServiceResult[
                            'horizon_result'
                        ]
                    )
                    &&
                    is_array(
                        $squadHorizonServiceResult[
                            'horizon_result'
                        ]
                    )
                ) {

                    $squadHorizonResult =
                        $squadHorizonServiceResult[
                            'horizon_result'
                        ];
                }
            }


            /*
             * Existing Squad Intelligence mapping remains
             * unchanged.
             */

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


function squadHorizonSeverityClass(
    mixed $severity
): string {

    $severity =
        strtolower(
            trim(
                (string) $severity
            )
        );


    return match (
        $severity
    ) {

        'severe' =>
            'squad-horizon-severity-severe',

        'high' =>
            'squad-horizon-severity-high',

        'moderate' =>
            'squad-horizon-severity-moderate',

        'low' =>
            'squad-horizon-severity-low',

        'none' =>
            'squad-horizon-severity-none',

        default =>
            'squad-horizon-severity-neutral'
    };
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
                    $squadHorizonResult !== null
                    &&
                    (
                        $squadHorizonResult[
                            'status'
                        ]
                        ?? null
                    )
                    ===
                    'Available'
                ): ?>

                    <?php

                    $horizonGameweeks =
                        $squadHorizonResult[
                            'gameweeks'
                        ]
                        ?? [];


                    $horizonFixtureClashes =
                        $squadHorizonResult[
                            'fixture_clashes'
                        ][
                            'gameweeks'
                        ]
                        ?? [];


                    $horizonWeakFixtures =
                        $squadHorizonResult[
                            'weak_fixture_clusters'
                        ][
                            'gameweeks'
                        ]
                        ?? [];


                    $horizonPositionDepth =
                        $squadHorizonResult[
                            'position_depth'
                        ][
                            'gameweeks'
                        ]
                        ?? [];


                    $horizonStructuralWeakness =
                        $squadHorizonResult[
                            'structural_weakness'
                        ][
                            'gameweeks'
                        ]
                        ?? [];


                    $horizonMaximumSeverity =
                        $squadHorizonResult[
                            'structural_weakness'
                        ][
                            'max_severity'
                        ]
                        ?? 'None';
                        
                    $horizonGoalkeeperRotation =
                        $squadHorizonResult[
                            'goalkeeper_rotation'
                        ]
                        ?? [];


                    $horizonPreferredGoalkeeperIds =
                        $horizonGoalkeeperRotation[
                            'preferred_goalkeeper_ids'
                        ]
                        ?? [];


                    $horizonGoalkeeperAlternations =
                        (int) (
                            $horizonGoalkeeperRotation[
                                'alternation_count'
                            ]
                            ?? 0
                        );


                    $horizonRotatingGoalkeeperPoints =
                        $horizonGoalkeeperRotation[
                            'rotating_projected_points'
                        ]
                        ?? null;


                    $horizonBestSingleGoalkeeper =
                        $horizonGoalkeeperRotation[
                            'best_single_goalkeeper'
                        ]
                        ?? null;


                    $horizonGoalkeeperRotationGain =
                        $horizonGoalkeeperRotation[
                            'rotation_gain'
                        ]
                        ?? null;
                        
                    $horizonDefensiveRotation =
                        $squadHorizonResult[
                            'defensive_rotation'
                        ]
                        ?? [];


                    $horizonDefensiveRotationGameweeks =
                        $horizonDefensiveRotation[
                            'gameweeks'
                        ]
                        ?? [];


                    $horizonDefensiveRotationPairs =
                        $horizonDefensiveRotation[
                            'rotation_pairs'
                        ]
                        ?? [];
                        
                        $horizonRepeatedBenching =
                            $squadHorizonResult[
                                'repeated_benching'
                            ]
                            ?? [];


                        $horizonRepeatedBenchingPlayers =
                            $horizonRepeatedBenching[
                                'players'
                            ]
                            ?? [];


                        $horizonRepeatedlyBenchedPlayerCount =
                            (int) (
                                $horizonRepeatedBenching[
                                    'repeatedly_benched_player_count'
                                ]
                                ?? 0
                            );


                        $horizonMeaningfulRepeatedBenchingPlayerCount =
                            (int) (
                                $horizonRepeatedBenching[
                                    'meaningful_repeated_benching_player_count'
                                ]
                                ?? 0
                            );
                            
                        $horizonStructuralSummary =
                            $squadHorizonResult[
                                'structural_weakness'
                            ]
                            ?? [];


                        $horizonStructuralProblemGameweeks =
                            (int) (
                                $horizonStructuralSummary[
                                    'gameweeks_with_problems'
                                ]
                                ?? 0
                            );


                        $horizonStructuralWorstGameweek =
                            $horizonStructuralSummary[
                                'worst_gameweek'
                            ]
                            ?? null;


                        $horizonStructuralMaxProblemCount =
                            (int) (
                                $horizonStructuralSummary[
                                    'max_problem_count'
                                ]
                                ?? 0
                            );

                    ?>


                    <!-- ==============================================
                         SQUAD HORIZON INTELLIGENCE
                         ============================================== -->

                    <section class="dashboard-section squad-horizon-section">

                        <div class="section-heading">

                            <div>

                                <p class="eyebrow">
                                    Squad Horizon
                                </p>

                                <h2>
                                    Next 3 Gameweeks
                                </h2>

                                <p class="section-description">
                                    Projected squad strength, structural weaknesses
                                    and selection pressure across the upcoming
                                    three-gameweek horizon.
                                </p>

                            </div>

                        </div>


                        <div class="squad-horizon-overview">

                            <article class="squad-horizon-health-card">

                                <span class="squad-horizon-label">
                                    Maximum Structural Risk
                                </span>

                                <strong class="squad-horizon-health-value <?= htmlspecialchars(
                                    squadHorizonSeverityClass(
                                        $horizonMaximumSeverity
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>">

                                    <?= htmlspecialchars(
                                        (string) $horizonMaximumSeverity,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </strong>

                                <span class="squad-horizon-note">
                                    Worst level found across the projected horizon
                                </span>

                            </article>

                        </div>


                        <div class="squad-horizon-gameweek-grid">

                            <?php foreach (
                                $horizonGameweeks
                                as $gameweekNumber => $gameweek
                            ): ?>

                                <?php

                                $startingXI =
                                    $gameweek[
                                        'starting_xi'
                                    ]
                                    ?? [];


                                $formationCounts = [

                                    'DEF' =>
                                        0,

                                    'MID' =>
                                        0,

                                    'FWD' =>
                                        0
                                ];


                                foreach (
                                    $startingXI
                                    as $startingPlayer
                                ) {

                                    $startingPosition =
                                        $startingPlayer[
                                            'position'
                                        ]
                                        ?? null;


                                    if (
                                        isset(
                                            $formationCounts[
                                                $startingPosition
                                            ]
                                        )
                                    ) {

                                        $formationCounts[
                                            $startingPosition
                                        ]++;
                                    }
                                }


                                $formation =
                                    (
                                        count(
                                            $startingXI
                                        )
                                        ===
                                        11
                                    )
                                        ? (
                                            $formationCounts[
                                                'DEF'
                                            ]
                                            . '-'
                                            . $formationCounts[
                                                'MID'
                                            ]
                                            . '-'
                                            . $formationCounts[
                                                'FWD'
                                            ]
                                        )
                                        : '—';


                                $startingProjectedPoints =
                                    $gameweek[
                                        'starting_xi_projected_points'
                                    ]
                                    ?? null;


                                $weakFixtureData =
                                    $horizonWeakFixtures[
                                        $gameweekNumber
                                    ]
                                    ?? [];


                                $weakStarterCount =
                                    (int) (
                                        $weakFixtureData[
                                            'weak_player_count'
                                        ]
                                        ?? 0
                                    );


                                $fixtureClashData =
                                    $horizonFixtureClashes[
                                        $gameweekNumber
                                    ]
                                    ?? [];


                                $fixtureClashCount =
                                    (int) (
                                        $fixtureClashData[
                                            'clash_count'
                                        ]
                                        ?? 0
                                    );


                                $positionDepthData =
                                    $horizonPositionDepth[
                                        $gameweekNumber
                                    ]
                                    ?? [];


                                $weakDepthPositions =
                                    $positionDepthData[
                                        'weak_depth_positions'
                                    ]
                                    ?? [];


                                $structuralData =
                                    $horizonStructuralWeakness[
                                        $gameweekNumber
                                    ]
                                    ?? [];


                                $structuralSeverity =
                                    $structuralData[
                                        'severity'
                                    ]
                                    ?? 'None';

                                ?>

                                <article class="squad-horizon-gameweek-card">

                                    <div class="squad-horizon-gameweek-top">

                                        <span class="squad-horizon-gameweek">
                                            GW<?= (int) $gameweekNumber; ?>
                                        </span>

                                        <span class="squad-horizon-severity <?= htmlspecialchars(
                                            squadHorizonSeverityClass(
                                                $structuralSeverity
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>">

                                            <?= htmlspecialchars(
                                                (string) $structuralSeverity,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </div>


                                    <div class="squad-horizon-primary-stat">

                                        <span>
                                            Starting XI xP
                                        </span>

                                        <strong>
                                            <?= is_numeric(
                                                $startingProjectedPoints
                                            )
                                                ? number_format(
                                                    (float) $startingProjectedPoints,
                                                    2
                                                )
                                                : '—'; ?>
                                        </strong>

                                    </div>


                                    <div class="squad-horizon-stat-grid">

                                        <div>

                                            <span>
                                                Formation
                                            </span>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    (string) $formation,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Weak Starters
                                            </span>

                                            <strong>
                                                <?= $weakStarterCount; ?>
                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Player Clashes
                                            </span>

                                            <strong>
                                                <?= $fixtureClashCount; ?>
                                            </strong>

                                        </div>


                                        <div>

                                            <span>
                                                Weak Depth
                                            </span>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    !empty(
                                                        $weakDepthPositions
                                                    )
                                                        ? implode(
                                                            ', ',
                                                            $weakDepthPositions
                                                        )
                                                        : 'None',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </strong>

                                        </div>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>


                        <!-- ==========================================
                             BENCH COVERAGE
                             ========================================== -->

                        <div class="squad-horizon-subsection">

                            <div class="squad-horizon-subsection-heading">

                                <div>

                                    <p class="eyebrow">
                                        Bench Coverage
                                    </p>

                                    <h3>
                                        Replacement Strength
                                    </h3>

                                </div>


                                <p>
                                    Projected bench strength and the
                                    immediate outfield replacement available
                                    behind the selected Starting XI.
                                </p>

                            </div>


                            <div class="squad-horizon-bench-grid">

                                <?php foreach (
                                    $horizonGameweeks
                                    as $gameweekNumber => $gameweek
                                ): ?>

                                    <?php

                                    $benchCoverage =
                                        $gameweek[
                                            'bench_coverage'
                                        ]
                                        ?? [];


                                    $benchPlayerCount =
                                        (int) (
                                            $benchCoverage[
                                                'bench_player_count'
                                            ]
                                            ?? 0
                                        );


                                    $benchProjectedPoints =
                                        $benchCoverage[
                                            'total_projected_points'
                                        ]
                                        ?? null;


                                    $firstOutfieldSubstitute =
                                        $benchCoverage[
                                            'first_outfield_substitute'
                                        ]
                                        ?? null;


                                    $weakestOutfieldStarter =
                                        $benchCoverage[
                                            'weakest_outfield_starter'
                                        ]
                                        ?? null;


                                    $coverageGap =
                                        $benchCoverage[
                                            'coverage_gap'
                                        ]
                                        ?? null;


                                    $firstSubstituteName =
                                        is_array(
                                            $firstOutfieldSubstitute
                                        )
                                            ? (
                                                $firstOutfieldSubstitute[
                                                    'name'
                                                ]
                                                ?? 'Unknown'
                                            )
                                            : 'Unavailable';


                                    $firstSubstitutePoints =
                                        is_array(
                                            $firstOutfieldSubstitute
                                        )
                                            ? (
                                                $firstOutfieldSubstitute[
                                                    'projected_points'
                                                ]
                                                ?? null
                                            )
                                            : null;


                                    $weakestStarterName =
                                        is_array(
                                            $weakestOutfieldStarter
                                        )
                                            ? (
                                                $weakestOutfieldStarter[
                                                    'name'
                                                ]
                                                ?? 'Unknown'
                                            )
                                            : 'Unavailable';


                                    $weakestStarterPoints =
                                        is_array(
                                            $weakestOutfieldStarter
                                        )
                                            ? (
                                                $weakestOutfieldStarter[
                                                    'projected_points'
                                                ]
                                                ?? null
                                            )
                                            : null;

                                    ?>

                                    <article class="squad-horizon-bench-card">

                                        <div class="squad-horizon-bench-top">

                                            <span class="squad-horizon-gameweek">
                                                GW<?= (int) $gameweekNumber; ?>
                                            </span>

                                            <span class="squad-horizon-bench-count">

                                                <?= $benchPlayerCount; ?>

                                                bench players

                                            </span>

                                        </div>


                                        <div class="squad-horizon-bench-primary">

                                            <span>
                                                Total Bench xP
                                            </span>

                                            <strong>
                                                <?= is_numeric(
                                                    $benchProjectedPoints
                                                )
                                                    ? number_format(
                                                        (float) $benchProjectedPoints,
                                                        2
                                                    )
                                                    : '—'; ?>
                                            </strong>

                                        </div>


                                        <div class="squad-horizon-bench-details">

                                            <div>

                                                <span>
                                                    Best Outfield Substitute
                                                </span>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        (string) $firstSubstituteName,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </strong>

                                                <small>

                                                    <?= is_numeric(
                                                        $firstSubstitutePoints
                                                    )
                                                        ? number_format(
                                                            (float) $firstSubstitutePoints,
                                                            2
                                                        ) . ' xP'
                                                        : 'Projection unavailable'; ?>

                                                </small>

                                            </div>


                                            <div>

                                                <span>
                                                    Weakest Outfield Starter
                                                </span>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        (string) $weakestStarterName,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </strong>

                                                <small>

                                                    <?= is_numeric(
                                                        $weakestStarterPoints
                                                    )
                                                        ? number_format(
                                                            (float) $weakestStarterPoints,
                                                            2
                                                        ) . ' xP'
                                                        : 'Projection unavailable'; ?>

                                                </small>

                                            </div>


                                            <div>

                                                <span>
                                                    Coverage Gap
                                                </span>

                                                <strong>

                                                    <?= is_numeric(
                                                        $coverageGap
                                                    )
                                                        ? number_format(
                                                            (float) $coverageGap,
                                                            2
                                                        )
                                                        : '—'; ?>

                                                </strong>

                                                <small>
                                                    Starter xP minus best substitute xP
                                                </small>

                                            </div>

                                        </div>

                                    </article>

                                <?php endforeach; ?>

                            </div>

                        </div>


                        <!-- ==========================================
                             GOALKEEPER ROTATION
                             ========================================== -->

                        <div class="squad-horizon-subsection">

                            <div class="squad-horizon-subsection-heading">

                                <div>

                                    <p class="eyebrow">
                                        Goalkeeper Rotation
                                    </p>

                                    <h3>
                                        Starting Goalkeeper Plan
                                    </h3>

                                </div>


                                <p>
                                    Compares rotating your goalkeepers each
                                    gameweek with simply starting the strongest
                                    single goalkeeper throughout the horizon.
                                </p>

                            </div>


                            <div class="squad-horizon-goalkeeper-summary">

                                <article class="squad-horizon-goalkeeper-summary-card">

                                    <span>
                                        Best Single Goalkeeper
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            is_array(
                                                $horizonBestSingleGoalkeeper
                                            )
                                                ? (string) (
                                                    $horizonBestSingleGoalkeeper[
                                                        'name'
                                                    ]
                                                    ?? 'Unavailable'
                                                )
                                                : 'Unavailable',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </strong>

                                    <small>

                                        <?php if (
                                            is_array(
                                                $horizonBestSingleGoalkeeper
                                            )
                                            &&
                                            is_numeric(
                                                $horizonBestSingleGoalkeeper[
                                                    'projected_points'
                                                ]
                                                ?? null
                                            )
                                        ): ?>

                                            <?= number_format(
                                                (float) $horizonBestSingleGoalkeeper[
                                                    'projected_points'
                                                ],
                                                2
                                            ); ?> xP across horizon

                                        <?php else: ?>

                                            Projection unavailable

                                        <?php endif; ?>

                                    </small>

                                </article>


                                <article class="squad-horizon-goalkeeper-summary-card">

                                    <span>
                                        Rotating Goalkeeper xP
                                    </span>

                                    <strong>

                                        <?= is_numeric(
                                            $horizonRotatingGoalkeeperPoints
                                        )
                                            ? number_format(
                                                (float) $horizonRotatingGoalkeeperPoints,
                                                2
                                            )
                                            : '—'; ?>

                                    </strong>

                                    <small>
                                        Best available goalkeeper each gameweek
                                    </small>

                                </article>


                                <article class="squad-horizon-goalkeeper-summary-card">

                                    <span>
                                        Rotation Gain
                                    </span>

                                    <strong>

                                        <?= is_numeric(
                                            $horizonGoalkeeperRotationGain
                                        )
                                            ? number_format(
                                                (float) $horizonGoalkeeperRotationGain,
                                                2
                                            )
                                            : '—'; ?>

                                    </strong>

                                    <small>
                                        xP gained versus the best single goalkeeper
                                    </small>

                                </article>


                                <article class="squad-horizon-goalkeeper-summary-card">

                                    <span>
                                        Preference Changes
                                    </span>

                                    <strong>
                                        <?= $horizonGoalkeeperAlternations; ?>
                                    </strong>

                                    <small>
                                        Changes in preferred goalkeeper
                                    </small>

                                </article>

                            </div>


                            <div class="squad-horizon-goalkeeper-plan">

                                <?php foreach (
                                    $horizonGameweeks
                                    as $gameweekNumber => $gameweek
                                ): ?>

                                    <?php

                                    $gameweekIndex =
                                        array_search(
                                            (int) $gameweekNumber,
                                            array_keys(
                                                $horizonGameweeks
                                            ),
                                            true
                                        );


                                    $preferredGoalkeeperId =
                                        $gameweekIndex !== false
                                            ? (
                                                $horizonPreferredGoalkeeperIds[
                                                    $gameweekIndex
                                                ]
                                                ?? null
                                            )
                                            : null;


                                    $preferredGoalkeeper =
                                        null;


                                    foreach (
                                        $gameweek[
                                            'players'
                                        ]
                                        ?? []
                                        as $gameweekPlayer
                                    ) {

                                        if (
                                            (
                                                $gameweekPlayer[
                                                    'position'
                                                ]
                                                ?? null
                                            )
                                            !==
                                            'GK'
                                        ) {

                                            continue;
                                        }


                                        if (
                                            !is_numeric(
                                                $preferredGoalkeeperId
                                            )
                                            ||
                                            (
                                                (int) (
                                                    $gameweekPlayer[
                                                        'player_id'
                                                    ]
                                                    ?? 0
                                                )
                                            )
                                            !==
                                            (int) $preferredGoalkeeperId
                                        ) {

                                            continue;
                                        }


                                        $preferredGoalkeeper =
                                            $gameweekPlayer;

                                        break;
                                    }

                                    ?>

                                    <article class="squad-horizon-goalkeeper-card">

                                        <span class="squad-horizon-gameweek">
                                            GW<?= (int) $gameweekNumber; ?>
                                        </span>


                                        <span class="squad-horizon-goalkeeper-card-label">
                                            Preferred Goalkeeper
                                        </span>


                                        <strong>

                                            <?= htmlspecialchars(
                                                is_array(
                                                    $preferredGoalkeeper
                                                )
                                                    ? (string) (
                                                        $preferredGoalkeeper[
                                                            'name'
                                                        ]
                                                        ?? 'Unavailable'
                                                    )
                                                    : 'Unavailable',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </strong>


                                        <small>

                                            <?php if (
                                                is_array(
                                                    $preferredGoalkeeper
                                                )
                                                &&
                                                is_numeric(
                                                    $preferredGoalkeeper[
                                                        'projected_points'
                                                    ]
                                                    ?? null
                                                )
                                            ): ?>

                                                <?= number_format(
                                                    (float) $preferredGoalkeeper[
                                                        'projected_points'
                                                    ],
                                                    2
                                                ); ?> xP

                                            <?php else: ?>

                                                Projection unavailable

                                            <?php endif; ?>

                                        </small>

                                    </article>

                                <?php endforeach; ?>

                            </div>

                        </div>


                        <!-- ==========================================
                             DEFENSIVE ROTATION
                             ========================================== -->

                        <div class="squad-horizon-subsection">

                            <div class="squad-horizon-subsection-heading">

                                <div>

                                    <p class="eyebrow">
                                        Defensive Rotation
                                    </p>

                                    <h3>
                                        Defender Rotation Opportunities
                                    </h3>

                                </div>


                                <p>
                                    Identifies defender pairs whose projected
                                    preference changes across the horizon,
                                    highlighting where rotation may improve
                                    weekly selection.
                                </p>

                            </div>


                            <?php if (
                                !empty(
                                    $horizonDefensiveRotationPairs
                                )
                            ): ?>

                                <div class="squad-horizon-defender-pairs">

                                    <?php foreach (
                                        $horizonDefensiveRotationPairs
                                        as $rotationPair
                                    ): ?>

                                        <?php

                                        $rotationPlayerIds =
                                            $rotationPair[
                                                'player_ids'
                                            ]
                                            ?? [];


                                        $rotationPreferredPlayerIds =
                                            $rotationPair[
                                                'preferred_player_ids'
                                            ]
                                            ?? [];


                                        $rotationAlternationCount =
                                            (int) (
                                                $rotationPair[
                                                    'alternation_count'
                                                ]
                                                ?? 0
                                            );


                                        $firstDefenderId =
                                            isset(
                                                $rotationPlayerIds[
                                                    0
                                                ]
                                            )
                                                ? (int) $rotationPlayerIds[
                                                    0
                                                ]
                                                : null;


                                        $secondDefenderId =
                                            isset(
                                                $rotationPlayerIds[
                                                    1
                                                ]
                                            )
                                                ? (int) $rotationPlayerIds[
                                                    1
                                                ]
                                                : null;


                                        $defenderNames =
                                            [];


                                        foreach (
                                            $horizonGameweeks
                                            as $rotationGameweek
                                        ) {

                                            foreach (
                                                $rotationGameweek[
                                                    'players'
                                                ]
                                                ?? []
                                                as $rotationPlayer
                                            ) {

                                                $rotationPlayerId =
                                                    isset(
                                                        $rotationPlayer[
                                                            'player_id'
                                                        ]
                                                    )
                                                        ? (int) $rotationPlayer[
                                                            'player_id'
                                                        ]
                                                        : null;


                                                if (
                                                    $rotationPlayerId === null
                                                ) {

                                                    continue;
                                                }


                                                if (
                                                    $rotationPlayerId
                                                    !==
                                                    $firstDefenderId
                                                    &&
                                                    $rotationPlayerId
                                                    !==
                                                    $secondDefenderId
                                                ) {

                                                    continue;
                                                }


                                                if (
                                                    !isset(
                                                        $defenderNames[
                                                            $rotationPlayerId
                                                        ]
                                                    )
                                                ) {

                                                    $defenderNames[
                                                        $rotationPlayerId
                                                    ] =
                                                        $rotationPlayer[
                                                            'name'
                                                        ]
                                                        ?? 'Unknown';
                                                }
                                            }
                                        }


                                        $firstDefenderName =
                                            $firstDefenderId !== null
                                                ? (
                                                    $defenderNames[
                                                        $firstDefenderId
                                                    ]
                                                    ?? 'Unknown'
                                                )
                                                : 'Unknown';


                                        $secondDefenderName =
                                            $secondDefenderId !== null
                                                ? (
                                                    $defenderNames[
                                                        $secondDefenderId
                                                    ]
                                                    ?? 'Unknown'
                                                )
                                                : 'Unknown';

                                        ?>

                                        <article class="squad-horizon-defender-pair-card">

                                            <div class="squad-horizon-defender-pair-top">

                                                <div>

                                                    <span>
                                                        Rotation Pair
                                                    </span>

                                                    <strong>
                                                        <?= htmlspecialchars(
                                                            (string) $firstDefenderName,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                        <span aria-hidden="true">
                                                            ↔
                                                        </span>

                                                        <?= htmlspecialchars(
                                                            (string) $secondDefenderName,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>
                                                    </strong>

                                                </div>


                                                <span class="squad-horizon-defender-switch-count">

                                                    <?= $rotationAlternationCount; ?>

                                                    <?= $rotationAlternationCount === 1
                                                        ? 'switch'
                                                        : 'switches'; ?>

                                                </span>

                                            </div>


                                            <div class="squad-horizon-defender-plan">

                                                <?php foreach (
                                                    $horizonGameweeks
                                                    as $rotationGameweekNumber => $rotationGameweek
                                                ): ?>

                                                    <?php

                                                    $rotationGameweekKeys =
                                                        array_keys(
                                                            $horizonGameweeks
                                                        );


                                                    $rotationGameweekIndex =
                                                        array_search(
                                                            (int) $rotationGameweekNumber,
                                                            $rotationGameweekKeys,
                                                            true
                                                        );


                                                    $preferredDefenderId =
                                                        $rotationGameweekIndex !== false
                                                            ? (
                                                                $rotationPreferredPlayerIds[
                                                                    $rotationGameweekIndex
                                                                ]
                                                                ?? null
                                                            )
                                                            : null;


                                                    $preferredDefenderName =
                                                        is_numeric(
                                                            $preferredDefenderId
                                                        )
                                                            ? (
                                                                $defenderNames[
                                                                    (int) $preferredDefenderId
                                                                ]
                                                                ?? 'Unknown'
                                                            )
                                                            : 'Unavailable';


                                                    $preferredDefenderPoints =
                                                        null;


                                                    foreach (
                                                        $rotationGameweek[
                                                            'players'
                                                        ]
                                                        ?? []
                                                        as $rotationGameweekPlayer
                                                    ) {

                                                        if (
                                                            !is_numeric(
                                                                $preferredDefenderId
                                                            )
                                                        ) {

                                                            break;
                                                        }


                                                        if (
                                                            (
                                                                (int) (
                                                                    $rotationGameweekPlayer[
                                                                        'player_id'
                                                                    ]
                                                                    ?? 0
                                                                )
                                                            )
                                                            !==
                                                            (int) $preferredDefenderId
                                                        ) {

                                                            continue;
                                                        }


                                                        $preferredDefenderPoints =
                                                            $rotationGameweekPlayer[
                                                                'projected_points'
                                                            ]
                                                            ?? null;

                                                        break;
                                                    }

                                                    ?>

                                                    <div class="squad-horizon-defender-gameweek">

                                                        <span class="squad-horizon-gameweek">
                                                            GW<?= (int) $rotationGameweekNumber; ?>
                                                        </span>


                                                        <strong>
                                                            <?= htmlspecialchars(
                                                                (string) $preferredDefenderName,
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ); ?>
                                                        </strong>


                                                        <small>

                                                            <?= is_numeric(
                                                                $preferredDefenderPoints
                                                            )
                                                                ? number_format(
                                                                    (float) $preferredDefenderPoints,
                                                                    2
                                                                ) . ' xP'
                                                                : 'Projection unavailable'; ?>

                                                        </small>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        </article>

                                    <?php endforeach; ?>

                                </div>

                            <?php else: ?>

                                <div class="squad-horizon-defender-empty">

                                    <strong>
                                        No meaningful defender rotation found
                                    </strong>

                                    <p>
                                        The projected preference between your
                                        defenders does not switch across this
                                        three-gameweek horizon.
                                    </p>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- ==========================================
                             REPEATED BENCHING
                             ========================================== -->

                        <div class="squad-horizon-subsection">

                            <div class="squad-horizon-subsection-heading">

                                <div>

                                    <p class="eyebrow">
                                        Repeated Benching
                                    </p>

                                    <h3>
                                        Bench Utilisation
                                    </h3>

                                </div>


                                <p>
                                    Identifies players repeatedly left outside
                                    the projected optimal Starting XI and
                                    highlights when strong projected output is
                                    being left on the bench.
                                </p>

                            </div>


                            <div class="squad-horizon-benching-summary">

                                <article class="squad-horizon-benching-summary-card">

                                    <span>
                                        Repeatedly Benched
                                    </span>

                                    <strong>
                                        <?= $horizonRepeatedlyBenchedPlayerCount; ?>
                                    </strong>

                                    <small>
                                        Players benched in at least two gameweeks
                                    </small>

                                </article>


                                <article class="squad-horizon-benching-summary-card">

                                    <span>
                                        Meaningful Benching
                                    </span>

                                    <strong>
                                        <?= $horizonMeaningfulRepeatedBenchingPlayerCount; ?>
                                    </strong>

                                    <small>
                                        Repeatedly benched with average bench xP of 3.0+
                                    </small>

                                </article>

                            </div>


                            <?php

                            $displayRepeatedBenchingPlayers =
                                array_values(
                                    array_filter(
                                        $horizonRepeatedBenchingPlayers,
                                        static function (
                                            array $player
                                        ): bool {

                                            return
                                                (
                                                    $player[
                                                        'is_repeatedly_benched'
                                                    ]
                                                    ?? false
                                                )
                                                ===
                                                true;
                                        }
                                    )
                                );


                            usort(
                                $displayRepeatedBenchingPlayers,
                                static function (
                                    array $playerA,
                                    array $playerB
                                ): int {

                                    $benchCountA =
                                        (int) (
                                            $playerA[
                                                'bench_count'
                                            ]
                                            ?? 0
                                        );


                                    $benchCountB =
                                        (int) (
                                            $playerB[
                                                'bench_count'
                                            ]
                                            ?? 0
                                        );


                                    if (
                                        $benchCountA
                                        !==
                                        $benchCountB
                                    ) {

                                        return
                                            $benchCountB
                                            <=>
                                            $benchCountA;
                                    }


                                    $averageA =
                                        is_numeric(
                                            $playerA[
                                                'average_benched_projected_points'
                                            ]
                                            ?? null
                                        )
                                            ? (float) $playerA[
                                                'average_benched_projected_points'
                                            ]
                                            : -1.0;


                                    $averageB =
                                        is_numeric(
                                            $playerB[
                                                'average_benched_projected_points'
                                            ]
                                            ?? null
                                        )
                                            ? (float) $playerB[
                                                'average_benched_projected_points'
                                            ]
                                            : -1.0;


                                    return
                                        $averageB
                                        <=>
                                        $averageA;
                                }
                            );

                            ?>


                            <?php if (
                                !empty(
                                    $displayRepeatedBenchingPlayers
                                )
                            ): ?>

                                <div class="squad-horizon-benching-grid">

                                    <?php foreach (
                                        $displayRepeatedBenchingPlayers
                                        as $benchedPlayer
                                    ): ?>

                                        <?php

                                        $benchedGameweeks =
                                            $benchedPlayer[
                                                'benched_gameweeks'
                                            ]
                                            ?? [];


                                        $benchCount =
                                            (int) (
                                                $benchedPlayer[
                                                    'bench_count'
                                                ]
                                                ?? 0
                                            );


                                        $startCount =
                                            (int) (
                                                $benchedPlayer[
                                                    'start_count'
                                                ]
                                                ?? 0
                                            );


                                        $averageBenchedPoints =
                                            $benchedPlayer[
                                                'average_benched_projected_points'
                                            ]
                                            ?? null;


                                        $isMeaningfulBenching =
                                            (
                                                $benchedPlayer[
                                                    'is_meaningful_repeated_benching'
                                                ]
                                                ?? false
                                            )
                                            ===
                                            true;

                                        ?>

                                        <article class="squad-horizon-benching-card">

                                            <div class="squad-horizon-benching-card-top">

                                                <div>

                                                    <span class="squad-horizon-benching-position">
                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $benchedPlayer[
                                                                    'position'
                                                                ]
                                                                ?? '—'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>
                                                    </span>


                                                    <strong>
                                                        <?= htmlspecialchars(
                                                            (string) (
                                                                $benchedPlayer[
                                                                    'name'
                                                                ]
                                                                ?? 'Unknown'
                                                            ),
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>
                                                    </strong>

                                                </div>


                                                <?php if (
                                                    $isMeaningfulBenching
                                                ): ?>

                                                    <span class="squad-horizon-benching-badge">
                                                        Meaningful
                                                    </span>

                                                <?php endif; ?>

                                            </div>


                                            <div class="squad-horizon-benching-metrics">

                                                <div>

                                                    <span>
                                                        Benched
                                                    </span>

                                                    <strong>
                                                        <?= $benchCount; ?>
                                                    </strong>

                                                </div>


                                                <div>

                                                    <span>
                                                        Started
                                                    </span>

                                                    <strong>
                                                        <?= $startCount; ?>
                                                    </strong>

                                                </div>


                                                <div>

                                                    <span>
                                                        Avg Bench xP
                                                    </span>

                                                    <strong>

                                                        <?= is_numeric(
                                                            $averageBenchedPoints
                                                        )
                                                            ? number_format(
                                                                (float) $averageBenchedPoints,
                                                                2
                                                            )
                                                            : '—'; ?>

                                                    </strong>

                                                </div>

                                            </div>


                                            <div class="squad-horizon-benching-gameweeks">

                                                <span>
                                                    Benched Gameweeks
                                                </span>


                                                <div>

                                                    <?php foreach (
                                                        $benchedGameweeks
                                                        as $benchedGameweek
                                                    ): ?>

                                                        <span>
                                                            GW<?= (int) $benchedGameweek; ?>
                                                        </span>

                                                    <?php endforeach; ?>

                                                </div>

                                            </div>

                                        </article>

                                    <?php endforeach; ?>

                                </div>

                            <?php else: ?>

                                <div class="squad-horizon-defender-empty">

                                    <strong>
                                        No repeated benching found
                                    </strong>

                                    <p>
                                        No squad player is projected to be
                                        benched in at least two gameweeks
                                        across this horizon.
                                    </p>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- ==========================================
                             STRUCTURAL WEAKNESS DETAIL
                             ========================================== -->

                        <div class="squad-horizon-subsection">

                            <div class="squad-horizon-subsection-heading">

                                <div>

                                    <p class="eyebrow">
                                        Structural Weakness
                                    </p>

                                    <h3>
                                        Horizon Risk Explanation
                                    </h3>

                                </div>


                                <p>
                                    Explains which squad-structure problems
                                    combine to produce each gameweek's
                                    structural severity.
                                </p>

                            </div>


                            <div class="squad-horizon-structural-summary">

                                <article class="squad-horizon-structural-summary-card">

                                    <span>
                                        Gameweeks With Problems
                                    </span>

                                    <strong>
                                        <?= $horizonStructuralProblemGameweeks; ?>
                                    </strong>

                                    <small>
                                        Across the current horizon
                                    </small>

                                </article>


                                <article class="squad-horizon-structural-summary-card">

                                    <span>
                                        Worst Gameweek
                                    </span>

                                    <strong>

                                        <?= is_numeric(
                                            $horizonStructuralWorstGameweek
                                        )
                                            ? 'GW'
                                                . (int) $horizonStructuralWorstGameweek
                                            : '—'; ?>

                                    </strong>

                                    <small>
                                        Highest structural problem count
                                    </small>

                                </article>


                                <article class="squad-horizon-structural-summary-card">

                                    <span>
                                        Maximum Problems
                                    </span>

                                    <strong>
                                        <?= $horizonStructuralMaxProblemCount; ?>
                                    </strong>

                                    <small>
                                        Out of four structural checks
                                    </small>

                                </article>


                                <article class="squad-horizon-structural-summary-card">

                                    <span>
                                        Maximum Severity
                                    </span>

                                    <strong class="<?= htmlspecialchars(
                                        squadHorizonSeverityClass(
                                            $horizonMaximumSeverity
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>">

                                        <?= htmlspecialchars(
                                            (string) $horizonMaximumSeverity,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </strong>

                                    <small>
                                        Worst level across the horizon
                                    </small>

                                </article>

                            </div>


                            <div class="squad-horizon-structural-grid">

                                <?php foreach (
                                    $horizonGameweeks
                                    as $gameweekNumber => $gameweek
                                ): ?>

                                    <?php

                                    $structuralGameweek =
                                        $horizonStructuralWeakness[
                                            $gameweekNumber
                                        ]
                                        ?? [];


                                    $structuralProblems =
                                        $structuralGameweek[
                                            'problems'
                                        ]
                                        ?? [];


                                    $structuralProblemCount =
                                        (int) (
                                            $structuralGameweek[
                                                'problem_count'
                                            ]
                                            ?? 0
                                        );


                                    $structuralSeverity =
                                        $structuralGameweek[
                                            'severity'
                                        ]
                                        ?? 'None';


                                    $structuralProblemChecks = [

                                        'Weak Fixture Cluster' =>
                                            (
                                                $structuralGameweek[
                                                    'has_weak_fixture_cluster'
                                                ]
                                                ?? false
                                            )
                                            ===
                                            true,

                                        'Position Depth Weakness' =>
                                            (
                                                $structuralGameweek[
                                                    'has_position_depth_weakness'
                                                ]
                                                ?? false
                                            )
                                            ===
                                            true,

                                        'Uncovered Weak XI' =>
                                            (
                                                $structuralGameweek[
                                                    'has_uncovered_weak_xi'
                                                ]
                                                ?? false
                                            )
                                            ===
                                            true,

                                        'Fixture Clash' =>
                                            (
                                                $structuralGameweek[
                                                    'has_fixture_clashes'
                                                ]
                                                ?? false
                                            )
                                            ===
                                            true
                                    ];

                                    ?>

                                    <article class="squad-horizon-structural-card">

                                        <div class="squad-horizon-structural-card-top">

                                            <span class="squad-horizon-gameweek">
                                                GW<?= (int) $gameweekNumber; ?>
                                            </span>


                                            <span class="squad-horizon-severity <?= htmlspecialchars(
                                                squadHorizonSeverityClass(
                                                    $structuralSeverity
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>">

                                                <?= htmlspecialchars(
                                                    (string) $structuralSeverity,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </span>

                                        </div>


                                        <div class="squad-horizon-structural-count">

                                            <span>
                                                Structural Problems
                                            </span>

                                            <strong>
                                                <?= $structuralProblemCount; ?>
                                                / 4
                                            </strong>

                                        </div>


                                        <div class="squad-horizon-structural-checks">

                                            <?php foreach (
                                                $structuralProblemChecks
                                                as $problemLabel => $hasProblem
                                            ): ?>

                                                <div class="<?= $hasProblem
                                                    ? 'squad-horizon-structural-check squad-horizon-structural-check-active'
                                                    : 'squad-horizon-structural-check'; ?>">

                                                    <span class="squad-horizon-structural-indicator">
                                                        <?= $hasProblem
                                                            ? '!'
                                                            : '✓'; ?>
                                                    </span>


                                                    <span>
                                                        <?= htmlspecialchars(
                                                            (string) $problemLabel,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>
                                                    </span>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>


                                        <?php if (
                                            !empty(
                                                $structuralProblems
                                            )
                                        ): ?>

                                            <div class="squad-horizon-structural-explanation">

                                                <span>
                                                    Active Problems
                                                </span>

                                                <p>
                                                    <?= htmlspecialchars(
                                                        implode(
                                                            ', ',
                                                            $structuralProblems
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </p>

                                            </div>

                                        <?php else: ?>

                                            <div class="squad-horizon-structural-explanation">

                                                <span>
                                                    Active Problems
                                                </span>

                                                <p>
                                                    No structural problems detected.
                                                </p>

                                            </div>

                                        <?php endif; ?>

                                    </article>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    </section>

                <?php endif; ?>
                
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