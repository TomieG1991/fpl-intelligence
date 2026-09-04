<?php

require_once __DIR__
    . '/../classes/autoload.php';


/*
 * ============================================================
 * ACTIVE NAVIGATION
 * ============================================================
 */

$activeNav =
    'chips';


/*
 * ============================================================
 * REQUEST
 * ============================================================
 */

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
            'preview'
        ],
        true
    );
    
$integrationMode =
    $previewInput ===
    'integration';
    
    
/*
 * ============================================================
 * FPL ENTRY REQUEST
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


/*
 * ============================================================
 * CHIP INTELLIGENCE PRODUCTION INTEGRATION
 * ============================================================
 */

$setupError =
    null;


$integrationError =
    null;


$integrationCards =
    [];


$playerIntelligenceService =
    null;


$playerRepository =
    null;


$squadHorizonIntelligence =
    null;


$squadHorizonIntelligenceService =
    null;


$wildcardDecisionIntelligenceService =
    null;


$freeHitDecisionIntelligenceService =
    null;


$benchBoostDecisionIntelligenceService =
    null;


$tripleCaptainDecisionIntelligenceService =
    null;
    
$fplSquadImporter =
    null;


/*
 * Production services are only required for the integration
 * path at this stage.
 *
 * The deterministic presentation preview remains deliberately
 * cheap and independent of the production calculation stack.
 */
if (
    $integrationMode
    ||
    (
        !$previewMode
        &&
        $entryId !== null
    )
) {

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


        $playerRepository =
            new PlayerRepository(
                $db
            );
            
            
        $fplSquadImporter =
            new FPLSquadImporter();


        /*
         * --------------------------------------------------------
         * SHARED SQUAD HORIZON
         * --------------------------------------------------------
         */

        $squadHorizonIntelligence =
            new SquadHorizonIntelligence();


        $squadHorizonIntelligenceService =
            new SquadHorizonIntelligenceService(
                $playerRepository,
                $playerIntelligenceService,
                $squadHorizonIntelligence
            );


        /*
         * --------------------------------------------------------
         * WILDCARD
         * --------------------------------------------------------
         */

        $wildcardOptimizer =
            new WildcardOptimizer();


        $wildcardHorizonIntelligenceService =
            new WildcardHorizonIntelligenceService(
                $wildcardOptimizer,
                $squadHorizonIntelligenceService
            );


        $wildcardTimingIntelligenceService =
            new WildcardTimingIntelligenceService(
                new WildcardTimingIntelligence()
            );


        $wildcardDecisionIntelligenceService =
            new WildcardDecisionIntelligenceService(
                $squadHorizonIntelligenceService,
                $wildcardHorizonIntelligenceService,
                $wildcardTimingIntelligenceService
            );


        /*
         * --------------------------------------------------------
         * FREE HIT
         * --------------------------------------------------------
         */

        $freeHitOptimizer =
            new FreeHitOptimizer();


        $freeHitIntelligenceService =
            new FreeHitIntelligenceService(
                $playerIntelligenceService,
                $freeHitOptimizer
            );


        $freeHitHorizonIntelligenceService =
            new FreeHitHorizonIntelligenceService(
                $freeHitIntelligenceService,
                $squadHorizonIntelligence
            );


        $freeHitDecisionIntelligenceService =
            new FreeHitDecisionIntelligenceService(
                $squadHorizonIntelligenceService,
                $freeHitHorizonIntelligenceService,
                new FreeHitDecisionIntelligence()
            );


        /*
         * --------------------------------------------------------
         * BENCH BOOST
         * --------------------------------------------------------
         */

        $benchBoostDecisionIntelligenceService =
            new BenchBoostDecisionIntelligenceService(
                $squadHorizonIntelligenceService,
                new BenchBoostIntelligence()
            );


        /*
         * --------------------------------------------------------
         * TRIPLE CAPTAIN
         * --------------------------------------------------------
         */

        $tripleCaptainDecisionIntelligenceService =
            new TripleCaptainDecisionIntelligenceService(
                $squadHorizonIntelligenceService,
                $playerIntelligenceService,
                new CaptainIntelligence(),
                new TripleCaptainIntelligence()
            );

    } catch (Throwable $exception) {

        $setupError =
            'Unable to initialise production Chip Intelligence.';
    }
}


/*
 * ============================================================
 * BUILD INTEGRATION SQUAD
 * ============================================================
 *
 * Build a legal fifteen-player squad from the current database.
 *
 * This is not a new squad-scoring algorithm.
 *
 * Its only purpose is to provide a stable imported-squad-shaped
 * fixture so the production chip services can be exercised
 * together without depending on a live external FPL entry.
 */

function buildChipIntegrationSquad(
    PlayerIntelligenceService $playerIntelligenceService,
    PlayerRepository $playerRepository
): ?array {

    $summaries =
        $playerIntelligenceService
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
     * --------------------------------------------------------
     * LOCAL PLAYER LOOKUP
     * --------------------------------------------------------
     */

    $localById =
        [];


    foreach (
        $localPlayers
        as $localPlayer
    ) {

        $localPlayerId =
            (int) (
                $localPlayer[
                    'id'
                ]
                ?? 0
            );


        if ($localPlayerId <= 0) {

            continue;
        }


        $localById[
            $localPlayerId
        ] =
            $localPlayer;
    }


    /*
     * --------------------------------------------------------
     * CANDIDATES BY POSITION
     * --------------------------------------------------------
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


    $playerPool =
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


        $fplPlayerId =
            (int) (
                $localPlayer[
                    'fpl_player_id'
                ]
                ?? 0
            );


        $teamId =
            (int) (
                $summary[
                    'team_id'
                ]
                ??
                (
                    $localPlayer[
                        'team_id'
                    ]
                    ?? 0
                )
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


        $intelligenceScore =
            $summary[
                'intelligence_score'
            ]
            ?? null;


        if (
            $fplPlayerId <= 0
            ||
            $teamId <= 0
            ||
            !isset(
                $candidatesByPosition[
                    $position
                ]
            )
            ||
            !is_numeric(
                $price
            )
            ||
            (float) $price <= 0
            ||
            !is_numeric(
                $intelligenceScore
            )
        ) {

            continue;
        }


        /*
         * Preserve the real Player Intelligence row for the
         * Wildcard and Free Hit candidate pool.
         */
        $playerPool[] =
            $summary;


        $candidatesByPosition[
            $position
        ][] = [

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
                $fplPlayerId,

            'team_id' =>
                $teamId,

            'position' =>
                $position,

            'price' =>
                (float) $price,

            'intelligence_score' =>
                (float) $intelligenceScore
        ];
    }


    /*
     * --------------------------------------------------------
     * ORDER INTEGRATION-SQUAD CANDIDATES
     * --------------------------------------------------------
     *
     * The integration squad only needs to be legal and affordable.
     * Prefer inexpensive players so changing FPL prices does not
     * make this development fixture exceed the standard budget.
     */

    foreach (
        $candidatesByPosition
        as &$positionCandidates
    ) {

        usort(
            $positionCandidates,
            static function (
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


                if ($priceComparison !== 0) {

                    return
                        $priceComparison;
                }


                return
                    (
                        $a[
                            'player_id'
                        ]
                        ?? 0
                    )
                    <=>
                    (
                        $b[
                            'player_id'
                        ]
                        ?? 0
                    );
            }
        );
    }


    unset(
        $positionCandidates
    );


    /*
     * --------------------------------------------------------
     * SELECT LEGAL FPL SQUAD
     * --------------------------------------------------------
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


    $selected =
        [];


    $clubCounts =
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
                    $clubCounts[
                        $teamId
                    ]
                    ?? 0
                )
                >= 3
            ) {

                continue;
            }


            $selected[] =
                $candidate;


            $clubCounts[
                $teamId
            ] =
                (
                    $clubCounts[
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


    if (
        count(
            $selected
        )
        !== 15
    ) {

        return null;
    }


    /*
     * --------------------------------------------------------
     * VALIDATE INTEGRATION BUDGET
     * --------------------------------------------------------
     */

    $squadCost =
        0.0;


    foreach (
        $selected
        as $candidate
    ) {

        $squadCost +=
            (float) (
                $candidate[
                    'price'
                ]
                ?? 0
            );
    }


    if ($squadCost > 100.0) {

        return null;
    }


    /*
     * --------------------------------------------------------
     * IMPORTER-SHAPED PLAYER ROWS
     * --------------------------------------------------------
     *
     * SquadHorizonIntelligenceService resolves official FPL IDs
     * back to local PlayerRepository records.
     */

    $importedPlayers =
        [];


    foreach (
        $selected
        as $index => $candidate
    ) {

        $importedPlayers[] = [

            'fpl_player_id' =>
                (int) $candidate[
                    'fpl_player_id'
                ],

            'squad_position' =>
                $index + 1,

            'multiplier' =>
                $index < 11
                    ? 1
                    : 0,

            'is_captain' =>
                false,

            'is_vice_captain' =>
                false
        ];
    }


    return [

        'imported_squad' => [

            'status' =>
                'success',

            'message' =>
                'Current database integration squad.',

            'entry' => [

                'entry_id' =>
                    0,

                'team_name' =>
                    'Chip Intelligence Integration',

                'manager_first_name' =>
                    'Development',

                'manager_last_name' =>
                    'Integration'
            ],

            'gameweek' =>
                null,

            'bank' =>
                round(
                    100.0
                    -
                    $squadCost,
                    1
                ),

            'team_value' =>
                round(
                    $squadCost,
                    1
                ),

            'player_count' =>
                15,

            'players' =>
                $importedPlayers
        ],

        'player_pool' =>
            $playerPool,

        'budget' =>
            100.0
    ];
}


/*
 * ============================================================
 * CHIP DECISION ADAPTER
 * ============================================================
 */

function chipDecisionPayload(
    mixed $decision
): array {

    if ($decision instanceof ChipDecision) {

        return
            $decision
                ->toArray();
    }


    return [

        'recommendation' =>
            'Unavailable',

        'confidence' =>
            0.0,

        'explanation' =>
            'The required production intelligence is currently unavailable.'
    ];
}


/*
 * ============================================================
 * FORMAT CHIP VALUE
 * ============================================================
 */

function formatChipPoints(
    mixed $value,
    bool $signed = false
): string {

    if (
        !is_numeric(
            $value
        )
    ) {

        return
            'Unavailable';
    }


    $numericValue =
        (float) $value;


    $prefix =
        $signed
        &&
        $numericValue > 0
            ? '+'
            : '';


    return
        $prefix
        .
        number_format(
            $numericValue,
            2
        )
        .
        ' pts';
}


function formatChipPercentage(
    mixed $value
): string {

    if (
        !is_numeric(
            $value
        )
    ) {

        return
            'Unavailable';
    }


    $numericValue =
        (float) $value;


    if (
        $numericValue >= 0
        &&
        $numericValue <= 1
    ) {

        $numericValue *=
            100;
    }


    return
        number_format(
            $numericValue,
            1
        )
        .
        '%';
}


/*
 * ============================================================
 * RUN PRODUCTION CHIP INTELLIGENCE
 * ============================================================
 */

if (
    (
        $integrationMode
        ||
        (
            !$previewMode
            &&
            $entryId !== null
        )
    )
    &&
    $setupError === null
    &&
    $playerIntelligenceService !== null
    &&
    $playerRepository !== null
    &&
    $wildcardDecisionIntelligenceService !== null
    &&
    $freeHitDecisionIntelligenceService !== null
    &&
    $benchBoostDecisionIntelligenceService !== null
    &&
    $tripleCaptainDecisionIntelligenceService !== null
) {

    try {

        /*
         * --------------------------------------------------------
         * RESOLVE PRODUCTION INPUT
         * --------------------------------------------------------
         */

        if ($integrationMode) {

            /*
             * Deterministic local integration source.
             */
            $integrationFixture =
                buildChipIntegrationSquad(
                    $playerIntelligenceService,
                    $playerRepository
                );


            if ($integrationFixture === null) {

                throw new RuntimeException(
                    'Unable to build a legal integration squad.'
                );
            }


            $importedSquad =
                $integrationFixture[
                    'imported_squad'
                ];


            $playerPool =
                $integrationFixture[
                    'player_pool'
                ];


            $budget =
                (float) $integrationFixture[
                    'budget'
                ];

        } else {

            /*
             * Real FPL entry source.
             */
            if ($fplSquadImporter === null) {

                throw new RuntimeException(
                    'FPL squad importer is unavailable.'
                );
            }


            $importedSquad =
                $fplSquadImporter
                    ->importSquad(
                        $entryId
                    );


            if (
                !is_array(
                    $importedSquad
                )
                ||
                (
                    $importedSquad[
                        'status'
                    ]
                    ?? null
                )
                !== 'success'
                ||
                count(
                    $importedSquad[
                        'players'
                    ]
                    ?? []
                )
                !== 15
            ) {

                throw new RuntimeException(
                    'A complete public FPL squad could not be imported for this entry.'
                );
            }


            /*
             * ----------------------------------------------------
             * BUILD CURRENT PLAYER POOL
             * ----------------------------------------------------
             *
             * Wildcard and Free Hit operate against the complete
             * current player universe, not merely the manager's
             * existing fifteen players.
             */

            $summaries =
                $playerIntelligenceService
                    ->getAllPlayerSummaries();


            $localPlayers =
                $playerRepository
                    ->getAll();


            $localById =
                [];


            foreach (
                $localPlayers
                as $localPlayer
            ) {

                $localPlayerId =
                    (int) (
                        $localPlayer[
                            'id'
                        ]
                        ?? 0
                    );


                if ($localPlayerId > 0) {

                    $localById[
                        $localPlayerId
                    ] =
                        $localPlayer;
                }
            }


            $playerPool =
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


                $teamId =
                    (int) (
                        $summary[
                            'team_id'
                        ]
                        ??
                        (
                            $localPlayer[
                                'team_id'
                            ]
                            ?? 0
                        )
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


                $intelligenceScore =
                    $summary[
                        'intelligence_score'
                    ]
                    ?? null;


                if (
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
                        $intelligenceScore
                    )
                ) {

                    continue;
                }


                $playerPool[] =
                    $summary;
            }


            if (empty($playerPool)) {

                throw new RuntimeException(
                    'No usable Player Intelligence candidate pool is available.'
                );
            }


            /*
             * ----------------------------------------------------
             * REAL AVAILABLE BUDGET
             * ----------------------------------------------------
             *
             * FPLSquadImporter exposes both values in millions.
             *
             * Example:
             *
             * team value  £99.6m
             * bank         £1.3m
             * -----------------
             * budget      £100.9m
             */

            $teamValue =
                $importedSquad[
                    'team_value'
                ]
                ?? null;


            $bank =
                $importedSquad[
                    'bank'
                ]
                ?? null;


            if (
                !is_numeric(
                    $teamValue
                )
                ||
                !is_numeric(
                    $bank
                )
            ) {

                throw new RuntimeException(
                    'The imported FPL squad does not expose usable team value and bank information.'
                );
            }


            $budget =
                round(
                    (float) $teamValue
                    +
                    (float) $bank,
                    1
                );


            if ($budget <= 0) {

                throw new RuntimeException(
                    'The imported FPL squad budget is invalid.'
                );
            }
        }


        /*
         * --------------------------------------------------------
         * RUN ALL FOUR EXISTING PRODUCTION DECISION PIPELINES
         * --------------------------------------------------------
         */

        $wildcardResult =
            $wildcardDecisionIntelligenceService
                ->build(
                    $importedSquad,
                    $playerPool,
                    $budget,
                    3
                );


        $freeHitResult =
            $freeHitDecisionIntelligenceService
                ->build(
                    $importedSquad,
                    $playerPool,
                    $budget
                );


        $benchBoostResult =
            $benchBoostDecisionIntelligenceService
                ->build(
                    $importedSquad
                );


        $tripleCaptainResult =
            $tripleCaptainDecisionIntelligenceService
                ->build(
                    $importedSquad
                );


        /*
         * --------------------------------------------------------
         * WILDCARD CARD
         * --------------------------------------------------------
         */

        $wildcardTiming =
            $wildcardResult[
                'timing_result'
            ]
            ?? [];


        $wildcardDecision =
            chipDecisionPayload(
                $wildcardTiming[
                    'decision'
                ]
                ?? null
            );


        $integrationCards[
            'wildcard'
        ] = [

            'name' =>
                'Wildcard',

            'recommendation' =>
                $wildcardDecision[
                    'recommendation'
                ],

            'confidence' =>
                $wildcardDecision[
                    'confidence'
                ],

            'metrics' => [

                [
                    'label' =>
                        'Current squad projected points',

                    'value' =>
                        formatChipPoints(
                            $wildcardTiming[
                                'current_squad_projected_points'
                            ]
                            ?? null
                        )
                ],

                [
                    'label' =>
                        'Wildcard squad projected points',

                    'value' =>
                        formatChipPoints(
                            $wildcardTiming[
                                'wildcard_squad_projected_points'
                            ]
                            ?? null
                        )
                ],

                [
                    'label' =>
                        'Projected points gain',

                    'value' =>
                        formatChipPoints(
                            $wildcardTiming[
                                'projected_points_gain'
                            ]
                            ?? null,
                            true
                        )
                ],

                [
                    'label' =>
                        'Future projected gain',

                    'value' =>
                        formatChipPoints(
                            $wildcardTiming[
                                'future_projected_gain'
                            ]
                            ?? null,
                            true
                        )
                ],

                [
                    'label' =>
                        'Better timing',

                    'value' =>
                        (string) (
                            $wildcardTiming[
                                'better_timing'
                            ]
                            ?? 'Unavailable'
                        )
                ]
            ],

            'explanation' =>
                (string) (
                    $wildcardDecision[
                        'explanation'
                    ]
                    ?? 'Wildcard intelligence is unavailable.'
                )
        ];


        /*
         * --------------------------------------------------------
         * FREE HIT CARD
         * --------------------------------------------------------
         */

        $freeHitDecision =
            chipDecisionPayload(
                $freeHitResult[
                    'decision'
                ]
                ?? null
            );


        $freeHitValue =
            $freeHitResult[
                'value_result'
            ]
            ?? [];


        $integrationCards[
            'free-hit'
        ] = [

            'name' =>
                'Free Hit',

            'recommendation' =>
                $freeHitDecision[
                    'recommendation'
                ],

            'confidence' =>
                $freeHitDecision[
                    'confidence'
                ],

            'metrics' => [

                [
                    'label' =>
                        'Current XI projected points',

                    'value' =>
                        formatChipPoints(
                            $freeHitValue[
                                'current_squad_projected_points'
                            ]
                            ?? null
                        )
                ],

                [
                    'label' =>
                        'Free Hit XI projected points',

                    'value' =>
                        formatChipPoints(
                            $freeHitValue[
                                'free_hit_projected_points'
                            ]
                            ?? null
                        )
                ],

                [
                    'label' =>
                        'Free Hit projected gain',

                    'value' =>
                        formatChipPoints(
                            $freeHitValue[
                                'projected_points_gain'
                            ]
                            ?? null,
                            true
                        )
                ]
            ],

            'explanation' =>
                (string) (
                    $freeHitDecision[
                        'explanation'
                    ]
                    ?? 'Free Hit intelligence is unavailable.'
                )
        ];


        /*
         * --------------------------------------------------------
         * BENCH BOOST CARD
         * --------------------------------------------------------
         */

        $benchBoostDecision =
            chipDecisionPayload(
                $benchBoostResult[
                    'decision'
                ]
                ?? null
            );


        $benchBoostAnalysis =
            $benchBoostResult[
                'analysis'
            ]
            ?? [];


        $fixtureQuality =
            $benchBoostAnalysis[
                'fixture_quality'
            ]
            ?? null;


        $integrationCards[
            'bench-boost'
        ] = [

            'name' =>
                'Bench Boost',

            'recommendation' =>
                $benchBoostDecision[
                    'recommendation'
                ],

            'confidence' =>
                $benchBoostDecision[
                    'confidence'
                ],

            'metrics' => [

                [
                    'label' =>
                        'Projected bench points',

                    'value' =>
                        formatChipPoints(
                            $benchBoostAnalysis[
                                'projected_bench_points'
                            ]
                            ?? null
                        )
                ],

                [
                    'label' =>
                        'Bench reliability',

                    'value' =>
                        formatChipPercentage(
                            $benchBoostAnalysis[
                                'bench_reliability'
                            ]
                            ?? null
                        )
                ],

                [
                    'label' =>
                        'Fixture quality',

                    'value' =>
                        is_numeric(
                            $fixtureQuality
                        )
                            ? number_format(
                                (float) $fixtureQuality,
                                2
                            )
                            : 'Unavailable'
                ],

                [
                    'label' =>
                        'Full-squad availability',

                    'value' =>
                        formatChipPercentage(
                            $benchBoostAnalysis[
                                'full_squad_availability'
                            ]
                            ?? null
                        )
                ]
            ],

            'explanation' =>
                (string) (
                    $benchBoostDecision[
                        'explanation'
                    ]
                    ?? 'Bench Boost intelligence is unavailable.'
                )
        ];


        /*
         * --------------------------------------------------------
         * TRIPLE CAPTAIN CARD
         * --------------------------------------------------------
         */

        $tripleCaptainDecision =
            chipDecisionPayload(
                $tripleCaptainResult[
                    'decision'
                ]
                ?? null
            );


        $tripleCaptainAnalysis =
            $tripleCaptainResult[
                'analysis'
            ]
            ?? [];


        $integrationCards[
            'triple-captain'
        ] = [

            'name' =>
                'Triple Captain',

            'recommendation' =>
                $tripleCaptainDecision[
                    'recommendation'
                ],

            'confidence' =>
                $tripleCaptainDecision[
                    'confidence'
                ],

            'metrics' => [

                [
                    'label' =>
                        'Captain',

                    'value' =>
                        (string) (
                            $tripleCaptainAnalysis[
                                'name'
                            ]
                            ?? 'Unavailable'
                        )
                ],

                [
                    'label' =>
                        'Projected captain points',

                    'value' =>
                        formatChipPoints(
                            $tripleCaptainAnalysis[
                                'projected_captain_points'
                            ]
                            ?? null
                        )
                ],

                [
                    'label' =>
                        'Captain Intelligence score',

                    'value' =>
                        is_numeric(
                            $tripleCaptainAnalysis[
                                'captain_score'
                            ]
                            ?? null
                        )
                            ? number_format(
                                (float) $tripleCaptainAnalysis[
                                    'captain_score'
                                ],
                                2
                            )
                            : 'Unavailable'
                ],

                [
                    'label' =>
                        'Schedule type',

                    'value' =>
                        (string) (
                            $tripleCaptainAnalysis[
                                'schedule_type'
                            ]
                            ?? 'Unavailable'
                        )
                ]
            ],

            'explanation' =>
                (string) (
                    $tripleCaptainDecision[
                        'explanation'
                    ]
                    ?? 'Triple Captain intelligence is unavailable.'
                )
        ];

    } catch (Throwable $exception) {

        $integrationError =
            $exception
                ->getMessage();
    }
}


/*
 * ============================================================
 * DEVELOPMENT PREVIEW
 * ============================================================
 *
 * The first Chip Intelligence page implementation establishes
 * the presentation contract only.
 *
 * These preview values are deliberately deterministic and do
 * not represent a real FPL recommendation.
 *
 * Real current-squad integration will call the existing:
 *
 * - WildcardDecisionIntelligenceService
 * - FreeHitDecisionIntelligenceService
 * - BenchBoostDecisionIntelligenceService
 * - TripleCaptainDecisionIntelligenceService
 *
 * The page must not introduce another chip-scoring model.
 */

$chipPreview =
    [

        'wildcard' => [

            'name' =>
                'Wildcard',

            'recommendation' =>
                'Hold',

            'confidence' =>
                74.0,

            'metrics' => [

                [
                    'label' =>
                        'Current squad projected points',

                    'value' =>
                        '52.4 xPts'
                ],

                [
                    'label' =>
                        'Wildcard squad projected points',

                    'value' =>
                        '57.2 xPts'
                ],

                [
                    'label' =>
                        'Projected points gain',

                    'value' =>
                        '+4.8 pts'
                ],

                [
                    'label' =>
                        'Future projected gain',

                    'value' =>
                        '+8.1 pts'
                ],

                [
                    'label' =>
                        'Better timing',

                    'value' =>
                        'Wait'
                ]
            ],

            'explanation' =>
                'The projected Wildcard improvement is positive, but waiting one gameweek currently projects a larger gain.'
        ],


        'free-hit' => [

            'name' =>
                'Free Hit',

            'recommendation' =>
                'Use',

            'confidence' =>
                73.0,

            'metrics' => [

                [
                    'label' =>
                        'Current XI projected points',

                    'value' =>
                        '48.3 xPts'
                ],

                [
                    'label' =>
                        'Free Hit XI projected points',

                    'value' =>
                        '61.7 xPts'
                ],

                [
                    'label' =>
                        'Free Hit projected gain',

                    'value' =>
                        '+13.4 pts'
                ]
            ],

            'explanation' =>
                'The Free Hit squad creates a strong one-gameweek projected advantage over the current Starting XI.'
        ],


        'bench-boost' => [

            'name' =>
                'Bench Boost',

            'recommendation' =>
                'Consider',

            'confidence' =>
                68.0,

            'metrics' => [

                [
                    'label' =>
                        'Projected bench points',

                    'value' =>
                        '12.7 xPts'
                ],

                [
                    'label' =>
                        'Bench reliability',

                    'value' =>
                        '74%'
                ],

                [
                    'label' =>
                        'Fixture quality',

                    'value' =>
                        'Good'
                ],

                [
                    'label' =>
                        'Full-squad availability',

                    'value' =>
                        '91%'
                ]
            ],

            'explanation' =>
                'The bench has useful projected value, although the opportunity is not yet strong enough for a clear Use recommendation.'
        ],


        'triple-captain' => [

            'name' =>
                'Triple Captain',

            'recommendation' =>
                'Hold',

            'confidence' =>
                60.0,

            'metrics' => [

                [
                    'label' =>
                        'Captain',

                    'value' =>
                        'Example Captain'
                ],

                [
                    'label' =>
                        'Projected captain points',

                    'value' =>
                        '8.4 xPts'
                ],

                [
                    'label' =>
                        'Captain Intelligence score',

                    'value' =>
                        '61.2'
                ],

                [
                    'label' =>
                        'Schedule type',

                    'value' =>
                        'Normal'
                ]
            ],

            'explanation' =>
                'The projected captain opportunity is not exceptional enough to justify using the Triple Captain chip.'
        ]
    ];


/*
 * ============================================================
 * RECOMMENDATION CLASS
 * ============================================================
 */

function chipRecommendationClass(
    string $recommendation
): string {

    switch (
        strtolower(
            trim(
                $recommendation
            )
        )
    ) {

        case 'use':

            return 'use';


        case 'consider':

            return 'consider';


        case 'hold':

            return 'hold';


        default:

            return '';
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
        content="Compare Wildcard, Free Hit, Bench Boost and Triple Captain intelligence in one FPL decision dashboard."
    >

    <title>
        Chip Intelligence | FPL Intelligence
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
                    Chip Decision Support
                </p>

                <h1>
                    Chip Intelligence
                </h1>

                <p class="topbar-subtitle">
                    Should I use a chip this week, and what does
                    the existing FPL Intelligence evidence say
                    about each available option?
                </p>

            </div>

        </header>


        <main class="dashboard chip-dashboard">


            <!-- ==============================================
                 INTRODUCTION
                 ============================================== -->

            <section class="dashboard-section">

                <div class="section-heading">

                    <p class="eyebrow">
                        Decision Dashboard
                    </p>

                    <h2>
                        Should I use a chip this week?
                    </h2>

                </div>


                <p>
                    Chip Intelligence brings the existing Wildcard,
                    Free Hit, Bench Boost and Triple Captain decision
                    systems into one view.
                </p>

                <p>
                    Each chip keeps its own established intelligence
                    model. This page presents those results together;
                    it does not combine them into a new cross-chip
                    scoring model or independently recalculate
                    projected points.
                </p>

            </section>


            <?php if ($previewMode): ?>

                <!-- ==============================================
                     DEVELOPMENT PREVIEW
                     ============================================== -->

                <section class="dashboard-section">

                    <div class="profile-panel">

                        <p class="eyebrow">
                            Development Preview Mode
                        </p>

                        <h2>
                            Example Chip Intelligence
                        </h2>

                        <p>
                            The values below are deterministic presentation
                            examples used to validate the Chip Intelligence
                            interface. They are not recommendations for a
                            real FPL squad.
                        </p>

                    </div>

                </section>


                <!-- ==============================================
                     CHIP DECISIONS
                     ============================================== -->

                <section class="dashboard-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Current Decision View
                        </p>

                        <h2>
                            Four Chip Opportunities
                        </h2>

                    </div>


                    <div class="chip-decision-grid">

                        <?php foreach (
                            $chipPreview
                            as $chipKey => $chip
                        ): ?>

                            <?php

                            $recommendation =
                                (string) (
                                    $chip[
                                        'recommendation'
                                    ]
                                    ?? 'Hold'
                                );


                            $confidence =
                                is_numeric(
                                    $chip[
                                        'confidence'
                                    ]
                                    ?? null
                                )
                                    ? (float) $chip[
                                        'confidence'
                                    ]
                                    : 0.0;

                            ?>


                            <article
                                class="profile-panel chip-decision-card"
                                data-chip="<?= htmlspecialchars(
                                    $chipKey,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                            >

                                <div class="chip-decision-header">

                                    <div>

                                        <p class="eyebrow">
                                            Chip Intelligence
                                        </p>

                                        <h2>
                                            <?= htmlspecialchars(
                                                (string) (
                                                    $chip[
                                                        'name'
                                                    ]
                                                    ?? ''
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>
                                        </h2>

                                    </div>


                                    <div
                                        class="chip-recommendation <?= htmlspecialchars(
                                            chipRecommendationClass(
                                                $recommendation
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                    >

                                        <?= htmlspecialchars(
                                            strtoupper(
                                                $recommendation
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </div>

                                </div>


                                <div class="chip-decision-summary">

                                    <div>

                                        <p class="eyebrow">
                                            Recommendation
                                        </p>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $recommendation,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>
                                        </strong>

                                    </div>


                                    <div>

                                        <p class="eyebrow">
                                            Confidence
                                        </p>

                                        <strong>
                                            <?= number_format(
                                                $confidence,
                                                1
                                            ); ?>%
                                        </strong>

                                    </div>

                                </div>


                                <div class="chip-metric-list">

                                    <?php foreach (
                                        $chip[
                                            'metrics'
                                        ]
                                        ?? []
                                        as $metric
                                    ): ?>

                                        <div class="chip-metric-row">

                                            <span>
                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $metric[
                                                            'label'
                                                        ]
                                                        ?? ''
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </span>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $metric[
                                                            'value'
                                                        ]
                                                        ?? ''
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </strong>

                                        </div>

                                    <?php endforeach; ?>

                                </div>


                                <div class="chip-explanation">

                                    <p class="eyebrow">
                                        Explanation
                                    </p>

                                    <p>
                                        <?= htmlspecialchars(
                                            (string) (
                                                $chip[
                                                    'explanation'
                                                ]
                                                ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </p>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                </section>


                <!-- ==============================================
                     RESPONSIBILITY NOTE
                     ============================================== -->

                <section class="dashboard-section">

                    <div class="profile-panel">

                        <p class="eyebrow">
                            Intelligence Boundary
                        </p>

                        <h2>
                            Existing Models Remain Authoritative
                        </h2>

                        <p>
                            Wildcard timing, Free Hit value, Bench Boost
                            opportunity and Triple Captain opportunity are
                            intentionally evaluated by their existing
                            decision engines.
                        </p>

                        <p>
                            If multiple chips return strong recommendations,
                            this dashboard will show those recommendations
                            honestly rather than creating an unsupported
                            cross-chip scoring formula.
                        </p>

                    </div>

                </section>


            <?php elseif ($integrationMode): ?>


                <!-- ==============================================
                     PRODUCTION INTEGRATION
                     ============================================== -->

                <section class="dashboard-section">

                    <div class="profile-panel">

                        <p class="eyebrow">
                            Production Integration Mode
                        </p>

                        <h2>
                            Current Database Intelligence
                        </h2>

                        <p>
                            This development mode builds a legal squad from
                            the current local FPL dataset and runs the real
                            Wildcard, Free Hit, Bench Boost and Triple Captain
                            decision pipelines.
                        </p>

                        <p>
                            No separate chip ranking or additional Expected
                            Points calculation is performed by this page.
                        </p>

                    </div>

                </section>


                <?php if ($setupError !== null): ?>

                    <section class="dashboard-section">

                        <div class="profile-panel">

                            <p class="eyebrow">
                                Integration Unavailable
                            </p>

                            <p>
                                <?= htmlspecialchars(
                                    $setupError,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </p>

                        </div>

                    </section>


                <?php elseif ($integrationError !== null): ?>

                    <section class="dashboard-section">

                        <div class="profile-panel">

                            <p class="eyebrow">
                                Integration Unavailable
                            </p>

                            <p>
                                <?= htmlspecialchars(
                                    $integrationError,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </p>

                        </div>

                    </section>


                <?php else: ?>


                    <section class="dashboard-section">

                        <div class="section-heading">

                            <p class="eyebrow">
                                Production Decisions
                            </p>

                            <h2>
                                Four Chip Opportunities
                            </h2>

                        </div>


                        <div class="chip-decision-grid">

                            <?php foreach (
                                $integrationCards
                                as $chipKey => $chip
                            ): ?>

                                <?php

                                $recommendation =
                                    (string) (
                                        $chip[
                                            'recommendation'
                                        ]
                                        ?? 'Unavailable'
                                    );


                                $confidence =
                                    is_numeric(
                                        $chip[
                                            'confidence'
                                        ]
                                        ?? null
                                    )
                                        ? (float) $chip[
                                            'confidence'
                                        ]
                                        : 0.0;

                                ?>


                                <article
                                    class="profile-panel chip-decision-card"
                                    data-chip="<?= htmlspecialchars(
                                        $chipKey,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                >

                                    <div class="chip-decision-header">

                                        <div>

                                            <p class="eyebrow">
                                                Chip Intelligence
                                            </p>

                                            <h2>
                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $chip[
                                                            'name'
                                                        ]
                                                        ?? ''
                                                    ),
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </h2>

                                        </div>


                                        <div
                                            class="chip-recommendation <?= htmlspecialchars(
                                                chipRecommendationClass(
                                                    $recommendation
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>"
                                        >

                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    $recommendation
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </div>

                                    </div>


                                    <div class="chip-decision-summary">

                                        <div>

                                            <p class="eyebrow">
                                                Recommendation
                                            </p>

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $recommendation,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>
                                            </strong>

                                        </div>


                                        <div>

                                            <p class="eyebrow">
                                                Confidence
                                            </p>

                                            <strong>
                                                <?= formatChipPercentage(
                                                    $confidence
                                                ); ?>
                                            </strong>

                                        </div>

                                    </div>


                                    <div class="chip-metric-list">

                                        <?php foreach (
                                            $chip[
                                                'metrics'
                                            ]
                                            ?? []
                                            as $metric
                                        ): ?>

                                            <div class="chip-metric-row">

                                                <span>
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $metric[
                                                                'label'
                                                            ]
                                                            ?? ''
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </span>

                                                <strong>
                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $metric[
                                                                'value'
                                                            ]
                                                            ?? ''
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ); ?>
                                                </strong>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>


                                    <div class="chip-explanation">

                                        <p class="eyebrow">
                                            Explanation
                                        </p>

                                        <p>
                                            <?= htmlspecialchars(
                                                (string) (
                                                    $chip[
                                                        'explanation'
                                                    ]
                                                    ?? ''
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>
                                        </p>

                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    </section>


                <?php endif; ?>


            <?php else: ?>


                <!-- ==============================================
                     FPL ENTRY
                     ============================================== -->

                <section class="dashboard-section">

                    <div class="section-heading">

                        <p class="eyebrow">
                            Your Squad
                        </p>

                        <h2>
                            Analyse Your Chips
                        </h2>

                    </div>


                    <div class="profile-panel">

                        <form
                            method="get"
                            action="chips.php"
                            class="chip-entry-form"
                        >

                            <div class="chip-entry-field">

                                <label for="entry_id">
                                    FPL Entry ID
                                </label>

                                <input
                                    type="number"
                                    id="entry_id"
                                    name="entry_id"
                                    min="1"
                                    step="1"
                                    inputmode="numeric"
                                    value="<?= $entryId !== null
                                        ? htmlspecialchars(
                                            (string) $entryId,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                        : ''; ?>"
                                    required
                                >

                            </div>


                            <button
                                type="submit"
                                class="chip-analyse-button"
                            >
                                Analyse Chips
                            </button>

                        </form>


                        <p>
                            Enter a positive FPL entry ID to import the current
                            squad and analyse its Wildcard, Free Hit, Bench Boost
                            and Triple Captain opportunities.
                        </p>

                    </div>

                </section>


<?php if ($entryId === null): ?>

    <!-- ==============================================
         NO ENTRY STATE
         ============================================== -->

    <section class="dashboard-section">

        <div class="profile-panel">

            <p class="eyebrow">
                Chip Intelligence
            </p>

            <h2>
                FPL Entry Required
            </h2>

            <p>
                An FPL entry is required before real Chip
                Intelligence recommendations can be generated.
            </p>

            <p>
                Once a valid entry is supplied, the current squad
                will be imported and evaluated by the existing
                chip decision systems.
            </p>

        </div>

    </section>


<?php else: ?>


    <?php if (
        $setupError !== null
        ||
        $integrationError !== null
    ): ?>

        <!-- ==============================================
             ANALYSIS UNAVAILABLE
             ============================================== -->

        <section class="dashboard-section">

            <div class="profile-panel">

                <p class="eyebrow">
                    Chip Intelligence Unavailable
                </p>

                <h2>
                    Entry <?= htmlspecialchars(
                        (string) $entryId,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </h2>

                <p>
                    <?= htmlspecialchars(
                        (string) (
                            $integrationError
                            ??
                            $setupError
                            ??
                            'Unable to analyse this FPL entry.'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </p>

            </div>

        </section>


    <?php else: ?>

        <!-- ==============================================
             REAL CHIP DECISIONS
             ============================================== -->

        <section class="dashboard-section">

            <div class="section-heading">

                <p class="eyebrow">
                    FPL Entry
                </p>

                <h2>
                    Entry <?= htmlspecialchars(
                        (string) $entryId,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                    Chip Decisions
                </h2>

            </div>


            <div class="chip-decision-grid">

                <?php foreach (
                    $integrationCards
                    as $chipKey => $chip
                ): ?>

                    <?php

                    $recommendation =
                        (string) (
                            $chip[
                                'recommendation'
                            ]
                            ?? 'Unavailable'
                        );


                    $confidence =
                        is_numeric(
                            $chip[
                                'confidence'
                            ]
                            ?? null
                        )
                            ? (float) $chip[
                                'confidence'
                            ]
                            : 0.0;

                    ?>


                    <article
                        class="profile-panel chip-decision-card"
                        data-chip="<?= htmlspecialchars(
                            $chipKey,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>"
                    >

                        <div class="chip-decision-header">

                            <div>

                                <p class="eyebrow">
                                    Chip Intelligence
                                </p>

                                <h2>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $chip[
                                                'name'
                                            ]
                                            ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </h2>

                            </div>


                            <div
                                class="chip-recommendation <?= htmlspecialchars(
                                    chipRecommendationClass(
                                        $recommendation
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                            >
                                <?= htmlspecialchars(
                                    strtoupper(
                                        $recommendation
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </div>

                        </div>


                        <div class="chip-decision-summary">

                            <div>

                                <p class="eyebrow">
                                    Recommendation
                                </p>

                                <strong>
                                    <?= htmlspecialchars(
                                        $recommendation,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </strong>

                            </div>


                            <div>

                                <p class="eyebrow">
                                    Confidence
                                </p>

                                <strong>
                                    <?= formatChipPercentage(
                                        $confidence
                                    ); ?>
                                </strong>

                            </div>

                        </div>


                        <div class="chip-metric-list">

                            <?php foreach (
                                $chip[
                                    'metrics'
                                ]
                                ?? []
                                as $metric
                            ): ?>

                                <div class="chip-metric-row">

                                    <span>
                                        <?= htmlspecialchars(
                                            (string) (
                                                $metric[
                                                    'label'
                                                ]
                                                ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </span>

                                    <strong>
                                        <?= htmlspecialchars(
                                            (string) (
                                                $metric[
                                                    'value'
                                                ]
                                                ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>


                        <div class="chip-explanation">

                            <p class="eyebrow">
                                Explanation
                            </p>

                            <p>
                                <?= htmlspecialchars(
                                    (string) (
                                        $chip[
                                            'explanation'
                                        ]
                                        ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </p>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>


    <?php endif; ?>


<?php endif; ?>


            <?php endif; ?>


        </main>

    </div>

</div>

</body>

</html>