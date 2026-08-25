<?php

class WildcardOptimizer
{
    /*
     * ============================================================
     * FPL SQUAD RULES
     * ============================================================
     */

    private const POSITION_REQUIREMENTS = [

        'GK' =>
            2,

        'DEF' =>
            5,

        'MID' =>
            5,

        'FWD' =>
            3
    ];


    private const MAX_PLAYERS_PER_CLUB =
        3;


    /*
     * A wildcard squad may contain a speculative backup goalkeeper,
     * but the goalkeeper relied upon in the Starting XI must have a
     * meaningful evidence sample.
     */
    private const GK_STARTER_MIN_CONFIDENCE =
        50.0;
        
    /*
     * A goalkeeper must also be within this proportion of the
     * strongest reliable goalkeeper's Starter Score to be trusted
     * as the wildcard Starting XI goalkeeper.
     *
     * 0.85 = at least 85% of the best reliable GK Starter Score.
     */
    private const GK_STARTER_QUALITY_RATIO =
        0.85;
        
    
    /*
     * ============================================================
     * BENCH RELIABILITY SETTINGS
     * ============================================================
     *
     * Low-confidence bench players remain selectable.
     *
     * Their contribution is simply reduced according to how weak
     * the supporting evidence is.
     */

    private const BENCH_RELIABILITY_PENALTY_HIGH =
        0.00;

    private const BENCH_RELIABILITY_PENALTY_MEDIUM =
        0.02;

    private const BENCH_RELIABILITY_PENALTY_LOW =
        0.05;

    private const BENCH_RELIABILITY_PENALTY_VERY_LOW =
        0.10;

    private const BENCH_RELIABILITY_PENALTY_EXTREME =
        0.18;


    /*
     * ============================================================
     * SEARCH PERFORMANCE SETTINGS
     * ============================================================
     */

    private const POSITION_STARTER_LIMIT =
        16;


    private const POSITION_VALUE_LIMIT =
        10;


    private const POSITION_CHEAP_LIMIT =
        6;


    private const BEAM_WIDTH =
        120;


    /*
     * ============================================================
     * ROLE-AWARE SEARCH WEIGHTS
     * ============================================================
     *
     * These weights influence BEAM SEARCH ONLY.
     *
     * They do not determine the final formation.
     *
     * WildcardSquadStructure remains responsible for evaluating
     * every legal FPL starting formation once a complete squad
     * has been generated.
     */

    private const ROLE_WEIGHTS = [

        'GK' => [

            1.00,
            0.05
        ],

        'DEF' => [

            1.00,
            1.00,
            1.00,
            0.65,
            0.20
        ],

        'MID' => [

            1.00,
            1.00,
            1.00,
            0.90,
            0.25
        ],

        'FWD' => [

            1.00,
            1.00,
            0.55
        ]
    ];


    /*
     * ============================================================
     * ROLE-AWARE BENCH COST PENALTIES
     * ============================================================
     *
     * These values penalise unnecessary spend in lower-impact
     * squad slots during BEAM SEARCH ONLY.
     *
     * They are expressed as score points deducted for each £1.0m
     * spent above the cheapest retained candidate at that position.
     *
     * The strongest likely starters carry no cost penalty.
     */

    private const ROLE_COST_PENALTIES = [

        'GK' => [

            0.00,
            1.50
        ],

        'DEF' => [

            0.00,
            0.00,
            0.00,
            0.10,
            0.75
        ],

        'MID' => [

            0.00,
            0.00,
            0.00,
            0.10,
            0.85
        ],

        'FWD' => [

            0.00,
            0.00,
            0.20
        ]
    ];


    /*
     * ============================================================
     * PUBLIC API
     * ============================================================
     */

    public function optimize(
        array $players,
        float $budget = 100.0
    ): array {

        /*
         * --------------------------------------------------------
         * BASIC INPUT VALIDATION
         * --------------------------------------------------------
         */

        if (
            empty(
                $players
            )
            ||
            $budget <= 0
        ) {

            return $this->invalidResult(
                'Invalid player pool or budget.'
            );
        }


        /*
         * --------------------------------------------------------
         * NORMALISE PLAYER POOL
         * --------------------------------------------------------
         */

        $candidatePool =
            [];


        foreach (
            $players
            as $player
        ) {

            $candidate =
                $this->normalisePlayer(
                    $player
                );


            if ($candidate === null) {
                continue;
            }


            /*
             * ----------------------------------------------------
             * WILDCARD SCORING v2
             * ----------------------------------------------------
             *
             * wildcard_score:
             *     retained general-purpose score for diagnostics
             *     and backwards compatibility.
             *
             * starter_score:
             *     prioritises raw quality, fixtures and availability.
             *     Price/value does not directly suppress premium
             *     starters.
             *
             * squad_value_score:
             *     gives value much more influence and is used to
             *     judge lower-impact squad / bench roles.
             */

            $candidate[
                'wildcard_score'
            ] =
                $this->calculateWildcardScore(
                    $candidate
                );


            $candidate[
                'starter_score'
            ] =
                $this->calculateStarterScore(
                    $candidate
                );


            $candidate[
                'squad_value_score'
            ] =
                $this->calculateSquadValueScore(
                    $candidate
                );


            $candidatePool[] =
                $candidate;
        }


        if (
            empty(
                $candidatePool
            )
        ) {

            return $this->invalidResult(
                'No valid wildcard candidates are available.'
            );
        }


        $candidatePool =
            $this->deduplicateCandidates(
                $candidatePool
            );
            
        /*
         * ============================================================
         * GOALKEEPER STARTER QUALITY FLOOR
         * ============================================================
         *
         * Find the strongest goalkeeper who already meets the minimum
         * confidence requirement.
         *
         * Other goalkeepers must then score at least 85% of this Starter
         * Score to qualify as a reliable Starting XI goalkeeper.
         */

        $gkStarterScoreFloor =
            $this->calculateGoalkeeperStarterScoreFloor(
                $candidatePool
            );


        if (
            $gkStarterScoreFloor === null
        ) {

            return $this->invalidResult(
                'No goalkeeper meets the minimum wildcard starter reliability requirements.'
            );
        }


        /*
         * --------------------------------------------------------
         * SPLIT PLAYERS BY POSITION
         * --------------------------------------------------------
         */

        $byPosition = [

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
            $candidatePool
            as $candidate
        ) {

            $position =
                $candidate[
                    'position'
                ];


            if (
                isset(
                    $byPosition[
                        $position
                    ]
                )
            ) {

                $byPosition[
                    $position
                ][] =
                    $candidate;
            }
        }


        /*
         * --------------------------------------------------------
         * POSITION POOL VALIDATION
         * --------------------------------------------------------
         */

        foreach (
            self::POSITION_REQUIREMENTS
            as $position => $required
        ) {

            if (
                count(
                    $byPosition[
                        $position
                    ]
                    ?? []
                )
                <
                $required
            ) {

                return $this->invalidResult(
                    'Not enough valid '
                    . $position
                    . ' candidates.'
                );
            }
        }


        /*
         * ========================================================
         * BUILD PRUNED SEARCH POOLS
         * ========================================================
         */

        foreach (
            $byPosition
            as $position => $positionPlayers
        ) {

            $byPosition[
                $position
            ] =
                $this->buildSearchPool(
                    $positionPlayers
                );
        }


        foreach (
            self::POSITION_REQUIREMENTS
            as $position => $required
        ) {

            if (
                count(
                    $byPosition[
                        $position
                    ]
                    ?? []
                )
                <
                $required
            ) {

                return $this->invalidResult(
                    'Wildcard candidate pruning left insufficient '
                    . $position
                    . ' options.'
                );
            }
        }


        /*
         * ========================================================
         * PRE-COMPUTE PRICE-SORTED POOLS
         * ========================================================
         */

        $byPositionPrice =
            [];


        foreach (
            $byPosition
            as $position => $positionPlayers
        ) {

            $pricePlayers =
                $positionPlayers;


            usort(
                $pricePlayers,
                static function (
                    array $a,
                    array $b
                ): int {

                    $priceA =
                        (float) (
                            $a[
                                'price'
                            ]
                            ?? 999
                        );


                    $priceB =
                        (float) (
                            $b[
                                'price'
                            ]
                            ?? 999
                        );


                    if (
                        $priceA
                        !==
                        $priceB
                    ) {

                        return $priceA
                            <=>
                            $priceB;
                    }


                    return (
                        (float) (
                            $b[
                                'wildcard_score'
                            ]
                            ?? 0
                        )
                    )
                    <=>
                    (
                        (float) (
                            $a[
                                'wildcard_score'
                            ]
                            ?? 0
                        )
                    );
                }
            );


            $byPositionPrice[
                $position
            ] =
                $pricePlayers;
        }


        /*
         * ========================================================
         * CHEAPEST RETAINED PRICE BY POSITION
         * ========================================================
         *
         * Used only to measure overspend in likely bench slots.
         */

        $minimumPriceByPosition =
            [];


        foreach (
            $byPositionPrice
            as $position => $pricePlayers
        ) {

            $minimumPriceByPosition[
                $position
            ] =
                isset(
                    $pricePlayers[0][
                        'price'
                    ]
                )
                &&
                is_numeric(
                    $pricePlayers[0][
                        'price'
                    ]
                )
                    ? (float) $pricePlayers[0][
                        'price'
                    ]
                    : 0.0;
        }


        /*
         * ========================================================
         * SEARCH SLOT ORDER
         * ========================================================
         *
         * We continue to explore attacking positions first.
         *
         * The new role-aware search score means fifth midfielders,
         * third forwards, fifth defenders and backup goalkeepers
         * no longer have the same importance as likely starters.
         */

        $slotSequence = [

            'MID',
            'MID',
            'MID',
            'MID',
            'MID',

            'FWD',
            'FWD',
            'FWD',

            'DEF',
            'DEF',
            'DEF',
            'DEF',
            'DEF',

            'GK',
            'GK'
        ];


        /*
         * ========================================================
         * PRE-COMPUTE REMAINING REQUIREMENTS
         * ========================================================
         */

        $remainingRequirementsByDepth =
            [];


        $totalSlots =
            count(
                $slotSequence
            );


        for (
            $depth = 0;
            $depth < $totalSlots;
            $depth++
        ) {

            $remainingCounts = [

                'GK' =>
                    0,

                'DEF' =>
                    0,

                'MID' =>
                    0,

                'FWD' =>
                    0
            ];


            for (
                $futureIndex =
                    $depth + 1;
                $futureIndex <
                    $totalSlots;
                $futureIndex++
            ) {

                $futurePosition =
                    $slotSequence[
                        $futureIndex
                    ];


                $remainingCounts[
                    $futurePosition
                ]++;
            }


            $remainingRequirementsByDepth[
                $depth
            ] =
                $remainingCounts;
        }


        /*
         * ========================================================
         * INITIAL SEARCH STATE
         * ========================================================
         */

        $states = [

            [

                'squad' =>
                    [],

                'selected_ids' =>
                    [],

                'team_counts' =>
                    [],

                'position_counts' => [

                    'GK' =>
                        0,

                    'DEF' =>
                        0,

                    'MID' =>
                        0,

                    'FWD' =>
                        0
                ],

                'cost' =>
                    0.0,

                'score_total' =>
                    0.0,

                'search_score' =>
                    0.0,

                'role_score' =>
                    0.0,

                'bench_cost_penalty' =>
                    0.0
            ]
        ];


        /*
         * ========================================================
         * BEAM SEARCH
         * ========================================================
         */

        foreach (
            $slotSequence
            as $depth => $position
        ) {

            $nextStates =
                [];


            $remainingRequirements =
                $remainingRequirementsByDepth[
                    $depth
                ];


            $remainingSlotCount =
                array_sum(
                    $remainingRequirements
                );


            foreach (
                $states
                as $state
            ) {

                foreach (
                    $byPosition[
                        $position
                    ]
                    as $candidate
                ) {

                    $playerId =
                        (int) $candidate[
                            'player_id'
                        ];


                    $teamId =
                        (int) $candidate[
                            'team_id'
                        ];


                    $price =
                        (float) $candidate[
                            'price'
                        ];


                    /*
                     * --------------------------------------------
                     * DUPLICATE PLAYER
                     * --------------------------------------------
                     */

                    if (
                        isset(
                            $state[
                                'selected_ids'
                            ][
                                $playerId
                            ]
                        )
                    ) {

                        continue;
                    }


                    /*
                     * --------------------------------------------
                     * THREE-PER-CLUB
                     * --------------------------------------------
                     */

                    $currentClubCount =
                        $state[
                            'team_counts'
                        ][
                            $teamId
                        ]
                        ?? 0;


                    if (
                        $currentClubCount
                        >=
                        self::MAX_PLAYERS_PER_CLUB
                    ) {

                        continue;
                    }


                    /*
                     * --------------------------------------------
                     * IMMEDIATE BUDGET
                     * --------------------------------------------
                     */

                    $newCost =
                        (
                            (float) $state[
                                'cost'
                            ]
                        )
                        +
                        $price;


                    if (
                        $newCost
                        >
                        $budget
                    ) {

                        continue;
                    }


                    /*
                     * --------------------------------------------
                     * PROVISIONAL SELECTION
                     * --------------------------------------------
                     */

                    $newSelectedIds =
                        $state[
                            'selected_ids'
                        ];


                    $newSelectedIds[
                        $playerId
                    ] =
                        true;


                    /*
                     * --------------------------------------------
                     * MINIMUM REMAINING COST
                     * --------------------------------------------
                     */

                    $minimumRemainingCost =
                        $this->calculateFastMinimumRemainingCost(
                            $remainingRequirements,
                            $byPositionPrice,
                            $newSelectedIds,
                            $state[
                                'squad'
                            ],
                            $candidate,
                            $gkStarterScoreFloor
                        );


                    if (
                        $minimumRemainingCost === null
                    ) {

                        continue;
                    }


                    if (
                        (
                            $newCost
                            +
                            $minimumRemainingCost
                        )
                        >
                        $budget
                    ) {

                        continue;
                    }


                    /*
                     * --------------------------------------------
                     * CREATE NEXT STATE
                     * --------------------------------------------
                     */

                    $newState =
                        $state;


                    $newState[
                        'squad'
                    ][] =
                        $candidate;


                    $newState[
                        'selected_ids'
                    ] =
                        $newSelectedIds;


                    $newState[
                        'team_counts'
                    ][
                        $teamId
                    ] =
                        $currentClubCount
                        +
                        1;


                    $newState[
                        'position_counts'
                    ][
                        $position
                    ] =
                        (
                            $newState[
                                'position_counts'
                            ][
                                $position
                            ]
                            ?? 0
                        )
                        +
                        1;
                        
                        
                    /*
                     * --------------------------------------------
                     * GOALKEEPER STARTER RELIABILITY
                     * --------------------------------------------
                     *
                     * Only evaluate goalkeeper reliability once the
                     * second goalkeeper has actually been added to
                     * the provisional squad.
                     *
                     * One GK may still be a cheap / low-confidence
                     * backup, but the pair must contain at least one
                     * goalkeeper who passes both the confidence and
                     * Starter Score quality requirements.
                     */

                    if (
                        (
                            $newState[
                                'position_counts'
                            ]['GK']
                            ?? 0
                        )
                        === 2
                    ) {

                        if (
                            !$this->hasReliableStartingGoalkeeper(
                                $newState[
                                    'squad'
                                ],
                                $gkStarterScoreFloor
                            )
                        ) {

                            continue;
                        }
                    }


                    $newState[
                        'cost'
                    ] =
                        $newCost;


                    $newState[
                        'score_total'
                    ] =
                        (
                            (float) $state[
                                'score_total'
                            ]
                        )
                        +
                        (
                            (float) (
                                $candidate[
                                    'wildcard_score'
                                ]
                                ?? 0
                            )
                        );


                    /*
                     * ============================================
                     * ROLE-AWARE PARTIAL SQUAD SCORE
                     * ============================================
                     */

                    $newState[
                        'role_score'
                    ] =
                        $this->calculateRoleAwarePartialScore(
                            $newState[
                                'squad'
                            ]
                        );


                    /*
                     * ============================================
                     * LIKELY BENCH OVERSPEND
                     * ============================================
                     *
                     * Cheap bench players are not rewarded merely
                     * for being cheap. Instead, expensive low-impact
                     * slots are penalised when the extra spend does
                     * not improve a likely starter.
                     */

                    $newState[
                        'bench_cost_penalty'
                    ] =
                        $this->calculateBenchCostPenalty(
                            $newState[
                                'squad'
                            ],
                            $minimumPriceByPosition
                        );


                    /*
                     * ============================================
                     * FINAL BEAM SEARCH SCORE
                     * ============================================
                     */

                    $newState[
                        'search_score'
                    ] =
                        $this->calculateSearchScore(
                            $newState
                        );


                    $nextStates[] =
                        $newState;
                }
            }


            if (
                empty(
                    $nextStates
                )
            ) {

                return $this->invalidResult(
                    'Unable to build a legal wildcard squad within budget.'
                );
            }


            /*
             * ----------------------------------------------------
             * DEDUPLICATE PARTIAL STATES
             * ----------------------------------------------------
             */

            $nextStates =
                $this->deduplicateStates(
                    $nextStates
                );


            /*
             * ----------------------------------------------------
             * RANK PARTIAL STATES
             * ----------------------------------------------------
             */

            usort(
                $nextStates,
                static function (
                    array $a,
                    array $b
                ): int {

                    $searchA =
                        (float) (
                            $a[
                                'search_score'
                            ]
                            ?? 0
                        );


                    $searchB =
                        (float) (
                            $b[
                                'search_score'
                            ]
                            ?? 0
                        );


                    if (
                        $searchA
                        !==
                        $searchB
                    ) {

                        return $searchB
                            <=>
                            $searchA;
                    }


                    /*
                     * Role-aware quality is the first tie-break.
                     */

                    $roleA =
                        (float) (
                            $a[
                                'role_score'
                            ]
                            ?? 0
                        );


                    $roleB =
                        (float) (
                            $b[
                                'role_score'
                            ]
                            ?? 0
                        );


                    if (
                        $roleA
                        !==
                        $roleB
                    ) {

                        return $roleB
                            <=>
                            $roleA;
                    }


                    /*
                     * Then raw squad quality.
                     */

                    $scoreA =
                        (float) (
                            $a[
                                'score_total'
                            ]
                            ?? 0
                        );


                    $scoreB =
                        (float) (
                            $b[
                                'score_total'
                            ]
                            ?? 0
                        );


                    if (
                        $scoreA
                        !==
                        $scoreB
                    ) {

                        return $scoreB
                            <=>
                            $scoreA;
                    }


                    /*
                     * Preserve budget on exact tie.
                     */

                    return (
                        (float) (
                            $a[
                                'cost'
                            ]
                            ?? 999
                        )
                    )
                    <=>
                    (
                        (float) (
                            $b[
                                'cost'
                            ]
                            ?? 999
                        )
                    );
                }
            );


            if (
                count(
                    $nextStates
                )
                >
                self::BEAM_WIDTH
            ) {

                $nextStates =
                    array_slice(
                        $nextStates,
                        0,
                        self::BEAM_WIDTH
                    );
            }


            $states =
                $nextStates;
        }


        /*
         * ========================================================
         * FINAL SQUAD STRUCTURE ANALYSIS
         * ========================================================
         */

        $structureAnalyzer =
            new WildcardSquadStructure();


        $validStates =
            [];


        foreach (
            $states
            as $state
        ) {

            $squad =
                $state[
                    'squad'
                ]
                ?? [];


            if (
                count(
                    $squad
                )
                !== 15
            ) {

                continue;
            }


            $validation =
                $this->validateSquad(
                    $squad,
                    $budget
                );


            if (
                (
                    $validation[
                        'is_valid'
                    ]
                    ?? false
                )
                !== true
            ) {

                continue;
            }


            /*
             * Raw all-15 score.
             */

            $wildcardScore =
                $this->calculateSquadScore(
                    $squad
                );


            /*
             * Actual best legal XI + bench.
             */

            $structure =
                $this->analyzeStarterStructure(
                    $squad,
                    $structureAnalyzer,
                    $gkStarterScoreFloor
                );


            if (
                (
                    $structure[
                        'status'
                    ]
                    ?? null
                )
                !==
                'success'
            ) {

                continue;
            }
            
            
            /*
             * ----------------------------------------------------
             * BENCH RELIABILITY
             * ----------------------------------------------------
             *
             * WildcardSquadStructure has now determined the actual
             * Starting XI and ordered bench.
             *
             * We can therefore apply reliability penalties only to
             * genuine substitute roles.
             */

            $benchReliability =
                $this->calculateBenchReliabilityScore(
                    $structure[
                        'bench'
                    ]
                    ?? []
                );


            $state[
                'validation'
            ] =
                $validation;


            $state[
                'wildcard_score'
            ] =
                $wildcardScore;


            $state[
                'structure'
            ] =
                $structure;


            /*
             * Rebuild the structure score using the reliability-adjusted
             * bench score.
             *
             * Starting XI remains 85% of the squad structure rating.
             * Bench remains 15%.
             */

            $state[
                'structure_score'
            ] =
                round(
                    (
                        (
                            (float) (
                                $structure[
                                    'starting_xi_score'
                                ]
                                ?? 0
                            )
                        )
                        *
                        0.85
                    )
                    +
                    (
                        (
                            (float) (
                                $benchReliability[
                                    'adjusted_score'
                                ]
                                ?? 0
                            )
                        )
                        *
                        0.15
                    ),
                    2
                );


            $state[
                'starting_xi_score'
            ] =
                (float) (
                    $structure[
                        'starting_xi_score'
                    ]
                    ?? 0
                );


            $state[
                'bench_score'
            ] =
                (float) (
                    $benchReliability[
                        'adjusted_score'
                    ]
                    ?? 0
                );


            $state[
                'raw_bench_score'
            ] =
                (float) (
                    $benchReliability[
                        'raw_score'
                    ]
                    ?? 0
                );


            $state[
                'bench_reliability_penalty'
            ] =
                (float) (
                    $benchReliability[
                        'penalty'
                    ]
                    ?? 0
                );


            $validStates[] =
                $state;
        }


        if (
            empty(
                $validStates
            )
        ) {

            return $this->invalidResult(
                'Unable to generate a complete legal wildcard squad.'
            );
        }


        /*
         * ========================================================
         * FINAL STRUCTURE-AWARE RANKING
         * ========================================================
         */

        usort(
            $validStates,
            static function (
                array $a,
                array $b
            ): int {

                /*
                 * 1. Complete structure score.
                 */

                $structureA =
                    (float) (
                        $a[
                            'structure_score'
                        ]
                        ?? 0
                    );


                $structureB =
                    (float) (
                        $b[
                            'structure_score'
                        ]
                        ?? 0
                    );


                if (
                    $structureA
                    !==
                    $structureB
                ) {

                    return $structureB
                        <=>
                        $structureA;
                }


                /*
                 * 2. Starting XI.
                 */

                $xiA =
                    (float) (
                        $a[
                            'starting_xi_score'
                        ]
                        ?? 0
                    );


                $xiB =
                    (float) (
                        $b[
                            'starting_xi_score'
                        ]
                        ?? 0
                    );


                if (
                    $xiA
                    !==
                    $xiB
                ) {

                    return $xiB
                        <=>
                        $xiA;
                }


                /*
                 * 3. Raw 15-player average.
                 */

                $wildcardA =
                    (float) (
                        $a[
                            'wildcard_score'
                        ]
                        ?? 0
                    );


                $wildcardB =
                    (float) (
                        $b[
                            'wildcard_score'
                        ]
                        ?? 0
                    );


                if (
                    $wildcardA
                    !==
                    $wildcardB
                ) {

                    return $wildcardB
                        <=>
                        $wildcardA;
                }


                /*
                 * 4. Preserve budget on exact tie.
                 */

                return (
                    (float) (
                        $a[
                            'cost'
                        ]
                        ?? 999
                    )
                )
                <=>
                (
                    (float) (
                        $b[
                            'cost'
                        ]
                        ?? 999
                    )
                );
            }
        );


        $bestState =
            $validStates[0];


        $selected =
            $this->sortSquadByPosition(
                $bestState[
                    'squad'
                ]
            );


        $totalCost =
            round(
                (float) (
                    $bestState[
                        'cost'
                    ]
                    ?? 0
                ),
                1
            );


        $structure =
            $bestState[
                'structure'
            ];


        /*
         * ========================================================
         * SUCCESS RESULT
         * ========================================================
         */

        return [

            'status' =>
                'success',

            'message' =>
                'Legal wildcard squad generated successfully.',

            'budget' =>
                round(
                    $budget,
                    1
                ),

            'cost' =>
                $totalCost,

            'bank' =>
                round(
                    $budget
                    -
                    $totalCost,
                    1
                ),

            /*
             * Raw score across all 15 players.
             */

            'wildcard_score' =>
                round(
                    (float) (
                        $bestState[
                            'wildcard_score'
                        ]
                        ?? 0
                    ),
                    2
                ),

            /*
             * Role-aware beam score of the final candidate.
             *
             * Diagnostic only.
             */

            'role_score' =>
                round(
                    (float) (
                        $bestState[
                            'role_score'
                        ]
                        ?? 0
                    ),
                    2
                ),

            'bench_cost_penalty' =>
                round(
                    (float) (
                        $bestState[
                            'bench_cost_penalty'
                        ]
                        ?? 0
                    ),
                    2
                ),

            /*
             * Exact structure analysis.
             */

            'structure_score' =>
                round(
                    (float) (
                        $structure[
                            'structure_score'
                        ]
                        ?? 0
                    ),
                    2
                ),

            'starting_xi_score' =>
                round(
                    (float) (
                        $structure[
                            'starting_xi_score'
                        ]
                        ?? 0
                    ),
                    2
                ),

            'bench_score' =>
                round(
                    (float) (
                        $structure[
                            'bench_score'
                        ]
                        ?? 0
                    ),
                    2
                ),
                
            'raw_bench_score' =>
                round(
                    (float) (
                        $bestState[
                            'raw_bench_score'
                        ]
                        ?? 0
                    ),
                    2
                ),

            'bench_reliability_penalty' =>
                round(
                    (float) (
                        $bestState[
                            'bench_reliability_penalty'
                        ]
                        ?? 0
                    ),
                    2
                ),

            'formation' =>
                $structure[
                    'formation'
                ]
                ?? null,

            'starting_xi' =>
                $structure[
                    'starting_xi'
                ]
                ?? [],

            'bench' =>
                $structure[
                    'bench'
                ]
                ?? [],

            'squad' =>
                $selected,

            'validation' =>
                $this->validateSquad(
                    $selected,
                    $budget
                ),

            'search' => [

                'beam_width' =>
                    self::BEAM_WIDTH,

                /*
                 * Backwards-compatible alias retained for the
                 * existing real-data diagnostic.
                 */
                'position_score_limit' =>
                    self::POSITION_STARTER_LIMIT,

                'position_starter_limit' =>
                    self::POSITION_STARTER_LIMIT,

                'position_value_limit' =>
                    self::POSITION_VALUE_LIMIT,

                'position_cheap_limit' =>
                    self::POSITION_CHEAP_LIMIT,

                'role_aware' =>
                    true,

                'gk_starter_min_confidence' =>
                    self::GK_STARTER_MIN_CONFIDENCE,
                    
                'gk_starter_quality_ratio' =>
                    self::GK_STARTER_QUALITY_RATIO,

                'gk_starter_score_floor' =>
                    $gkStarterScoreFloor,

                'final_states_considered' =>
                    count(
                        $validStates
                    )
            ]
        ];
    }


    /*
     * ============================================================
     * ROLE-AWARE PARTIAL SQUAD SCORE
     * ============================================================
     *
     * Players are grouped by position and ranked by Wildcard Score.
     *
     * Likely starters receive high importance.
     * Lower-impact squad slots receive reduced importance.
     *
     * Cost is deliberately NOT included in this score. Cost handling
     * is performed separately by calculateBenchCostPenalty().
     */

    /*
     * ============================================================
     * STARTER-BASED FINAL STRUCTURE ANALYSIS
     * ============================================================
     *
     * WildcardSquadStructure intentionally remains unchanged.
     *
     * For final formation evaluation we provide it a temporary copy
     * of the squad where wildcard_score represents Starter Score.
     *
     * Once the best formation / bench order is known, the returned
     * players are mapped back to the original optimizer player arrays
     * so all diagnostic scores remain available.
     */

    private function analyzeStarterStructure(
        array $squad,
        WildcardSquadStructure $structureAnalyzer,
        float $gkStarterScoreFloor
    ): array {

        $structureInput =
            [];


        $originalById =
            [];


        foreach (
            $squad
            as $player
        ) {

            $playerId =
                (int) (
                    $player[
                        'player_id'
                    ]
                    ?? 0
                );


            $originalById[
                $playerId
            ] =
                $player;


            $structurePlayer =
                $player;


            $structurePlayer[
                'wildcard_score'
            ] =
                (float) (
                    $player[
                        'starter_score'
                    ]
                    ?? (
                        $player[
                            'wildcard_score'
                        ]
                        ?? 0
                    )
                );


            /*
             * WildcardSquadStructure can now distinguish between
             * squad eligibility and starting eligibility.
             *
             * Only goalkeeper reliability is enforced at this stage.
             * Outfield confidence remains score-based rather than a
             * hard eligibility rule.
             */
            $structurePlayer[
                'starter_eligible'
            ] =
                (
                    $player[
                        'position'
                    ]
                    ?? null
                )
                !== 'GK'
                ||
                (
                    (
                        (float) (
                            $player[
                                'reliability_confidence'
                            ]
                            ?? 0
                        )
                        >=
                        self::GK_STARTER_MIN_CONFIDENCE
                    )
                    &&
                    (
                        (float) (
                            $player[
                                'starter_score'
                            ]
                            ?? 0
                        )
                        >=
                        $gkStarterScoreFloor
                    )
                );


            $structureInput[] =
                $structurePlayer;
        }


        $structure =
            $structureAnalyzer
                ->analyze(
                    $structureInput
                );


        if (
            (
                $structure[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            return $structure;
        }


        /*
         * Map Starting XI back to complete optimizer records.
         */

        $mappedStartingXI =
            [];


        foreach (
            $structure[
                'starting_xi'
            ]
            ?? []
            as $player
        ) {

            $playerId =
                (int) (
                    $player[
                        'player_id'
                    ]
                    ?? 0
                );


            if (
                isset(
                    $originalById[
                        $playerId
                    ]
                )
            ) {

                $mappedStartingXI[] =
                    $originalById[
                        $playerId
                    ];
            }
        }


        /*
         * Preserve bench order while mapping back.
         */

        $mappedBench =
            [];


        foreach (
            $structure[
                'bench'
            ]
            ?? []
            as $player
        ) {

            $playerId =
                (int) (
                    $player[
                        'player_id'
                    ]
                    ?? 0
                );


            if (
                !isset(
                    $originalById[
                        $playerId
                    ]
                )
            ) {

                continue;
            }


            $mapped =
                $originalById[
                    $playerId
                ];


            $mapped[
                'bench_order'
            ] =
                $player[
                    'bench_order'
                ]
                ?? null;


            $mappedBench[] =
                $mapped;
        }


        $structure[
            'starting_xi'
        ] =
            $mappedStartingXI;


        $structure[
            'bench'
        ] =
            $mappedBench;


        return $structure;
    }


    private function calculateRoleAwarePartialScore(
        array $squad
    ): float {

        if (
            empty(
                $squad
            )
        ) {

            return 0.0;
        }


        $byPosition =
            $this->groupAndRankByPosition(
                $squad
            );


        $weightedScore =
            0.0;


        $weightTotal =
            0.0;


        foreach (
            $byPosition
            as $position => $positionPlayers
        ) {

            $roleWeights =
                self::ROLE_WEIGHTS[
                    $position
                ]
                ?? [];


            foreach (
                $positionPlayers
                as $index => $player
            ) {

                /*
                 * roleWeight describes how likely this squad slot
                 * is to matter to the starting XI.
                 *
                 * High-impact slots lean heavily toward Starter
                 * Score. Lower-impact slots lean toward Squad Value.
                 */

                $roleWeight =
                    (float) (
                        $roleWeights[
                            $index
                        ]
                        ?? 0.10
                    );


                $roleWeight =
                    max(
                        0,
                        min(
                            1,
                            $roleWeight
                        )
                    );


                $starterScore =
                    (float) (
                        $player[
                            'starter_score'
                        ]
                        ?? (
                            $player[
                                'wildcard_score'
                            ]
                            ?? 0
                        )
                    );


                $valueScore =
                    (float) (
                        $player[
                            'squad_value_score'
                        ]
                        ?? (
                            $player[
                                'wildcard_score'
                            ]
                            ?? 0
                        )
                    );


                $effectiveScore =
                    (
                        $starterScore
                        *
                        $roleWeight
                    )
                    +
                    (
                        $valueScore
                        *
                        (
                            1
                            -
                            $roleWeight
                        )
                    );


                /*
                 * Bench slots still matter, but much less than
                 * probable starters.
                 */

                $importance =
                    max(
                        0.15,
                        $roleWeight
                    );


                $weightedScore +=
                    $effectiveScore
                    *
                    $importance;


                $weightTotal +=
                    $importance;
            }
        }


        if (
            $weightTotal <= 0
        ) {

            return 0.0;
        }


        return round(
            $weightedScore
            /
            $weightTotal,
            4
        );
    }
    
    /*
     * ============================================================
     * BENCH RELIABILITY PENALTY
     * ============================================================
     *
     * Converts player confidence into a proportional reduction to
     * their bench contribution.
     *
     * This is deliberately softer than the goalkeeper starter rule.
     * Low-confidence players remain legal squad selections.
     */

    private function calculateBenchReliabilityPenalty(
        array $player
    ): float {

        $confidence =
            $player[
                'reliability_confidence'
            ]
            ?? null;


        if (
            $confidence === null
            ||
            !is_numeric(
                $confidence
            )
        ) {

            /*
             * Missing confidence is treated cautiously.
             */

            return self::BENCH_RELIABILITY_PENALTY_VERY_LOW;
        }


        $confidence =
            (float) $confidence;


        if (
            $confidence >= 75.0
        ) {

            return self::BENCH_RELIABILITY_PENALTY_HIGH;
        }


        if (
            $confidence >= 50.0
        ) {

            return self::BENCH_RELIABILITY_PENALTY_MEDIUM;
        }


        if (
            $confidence >= 25.0
        ) {

            return self::BENCH_RELIABILITY_PENALTY_LOW;
        }


        if (
            $confidence >= 10.0
        ) {

            return self::BENCH_RELIABILITY_PENALTY_VERY_LOW;
        }


        return self::BENCH_RELIABILITY_PENALTY_EXTREME;
    }
    
    /*
     * ============================================================
     * BENCH RELIABILITY SCORE
     * ============================================================
     *
     * Applies confidence penalties to the ordered four-player bench.
     *
     * Bench weighting:
     *
     * 1st substitute = 50%
     * 2nd substitute = 30%
     * 3rd substitute = 15%
     * Backup GK      = 5%
     */

    private function calculateBenchReliabilityScore(
        array $bench
    ): array {

        if (
            empty(
                $bench
            )
        ) {

            return [

                'adjusted_score' =>
                    0.0,

                'raw_score' =>
                    0.0,

                'penalty' =>
                    0.0
            ];
        }


        $weights = [

            0.50,
            0.30,
            0.15,
            0.05
        ];


        $rawWeighted =
            0.0;


        $adjustedWeighted =
            0.0;


        $weightTotal =
            0.0;


        foreach (
            $bench
            as $index => $player
        ) {

            $weight =
                (float) (
                    $weights[
                        $index
                    ]
                    ?? 0
                );


            if (
                $weight <= 0
            ) {

                continue;
            }


            /*
             * Bench players are judged primarily by squad value,
             * falling back to Wildcard Score if necessary.
             */

            $score =
                (float) (
                    $player[
                        'squad_value_score'
                    ]
                    ?? (
                        $player[
                            'wildcard_score'
                        ]
                        ?? 0
                    )
                );


            $reliabilityPenalty =
                $this->calculateBenchReliabilityPenalty(
                    $player
                );


            $adjustedScore =
                $score
                *
                (
                    1.0
                    -
                    $reliabilityPenalty
                );


            $rawWeighted +=
                $score
                *
                $weight;


            $adjustedWeighted +=
                $adjustedScore
                *
                $weight;


            $weightTotal +=
                $weight;
        }


        if (
            $weightTotal <= 0
        ) {

            return [

                'adjusted_score' =>
                    0.0,

                'raw_score' =>
                    0.0,

                'penalty' =>
                    0.0
            ];
        }


        $rawScore =
            $rawWeighted
            /
            $weightTotal;


        $adjustedScore =
            $adjustedWeighted
            /
            $weightTotal;


        return [

            'adjusted_score' =>
                round(
                    $adjustedScore,
                    2
                ),

            'raw_score' =>
                round(
                    $rawScore,
                    2
                ),

            /*
             * Express penalty in score points rather than percentage.
             */

            'penalty' =>
                round(
                    max(
                        0,
                        $rawScore
                        -
                        $adjustedScore
                    ),
                    2
                )
        ];
    }


    /*
     * ============================================================
     * LIKELY BENCH COST PENALTY
     * ============================================================
     *
     * This is the main Role-Aware Search v2 change.
     *
     * We no longer reward unused money.
     *
     * Instead, we penalise extra spend specifically where the
     * player currently occupies a lower-impact squad role.
     *
     * Example:
     *
     * A £5.5m backup goalkeeper is compared with the cheapest
     * retained goalkeeper option. Because goalkeeper slot two has
     * a strong cost coefficient, unnecessary spend there is costly.
     *
     * A first-choice goalkeeper has a zero coefficient and therefore
     * receives no price penalty.
     */

    private function calculateBenchCostPenalty(
        array $squad,
        array $minimumPriceByPosition
    ): float {

        if (
            empty(
                $squad
            )
        ) {

            return 0.0;
        }


        $byPosition =
            $this->groupAndRankByPosition(
                $squad
            );


        $penalty =
            0.0;


        foreach (
            $byPosition
            as $position => $positionPlayers
        ) {

            $costPenalties =
                self::ROLE_COST_PENALTIES[
                    $position
                ]
                ?? [];


            $minimumPrice =
                (float) (
                    $minimumPriceByPosition[
                        $position
                    ]
                    ?? 0
                );


            foreach (
                $positionPlayers
                as $index => $player
            ) {

                $coefficient =
                    (float) (
                        $costPenalties[
                            $index
                        ]
                        ?? 0.0
                    );


                if (
                    $coefficient <= 0
                ) {

                    continue;
                }


                $price =
                    (float) (
                        $player[
                            'price'
                        ]
                        ?? 0
                    );


                $premium =
                    max(
                        0,
                        $price
                        -
                        $minimumPrice
                    );


                $penalty +=
                    $premium
                    *
                    $coefficient;
            }
        }


        return round(
            $penalty,
            4
        );
    }


    /*
     * ============================================================
     * GROUP + RANK SQUAD BY POSITION
     * ============================================================
     */

    private function groupAndRankByPosition(
        array $squad
    ): array {

        $byPosition = [

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
            $squad
            as $player
        ) {

            $position =
                $player[
                    'position'
                ]
                ?? null;


            if (
                isset(
                    $byPosition[
                        $position
                    ]
                )
            ) {

                $byPosition[
                    $position
                ][] =
                    $player;
            }
        }


        foreach (
            $byPosition
            as &$positionPlayers
        ) {

            usort(
                $positionPlayers,
                static function (
                    array $a,
                    array $b
                ): int {

                    /*
                     * Determine likely starter order using the new
                     * Starter Score rather than Value-heavy
                     * Wildcard Score.
                     */

                    $scoreA =
                        (float) (
                            $a[
                                'starter_score'
                            ]
                            ?? (
                                $a[
                                    'wildcard_score'
                                ]
                                ?? 0
                            )
                        );


                    $scoreB =
                        (float) (
                            $b[
                                'starter_score'
                            ]
                            ?? (
                                $b[
                                    'wildcard_score'
                                ]
                                ?? 0
                            )
                        );


                    if (
                        $scoreA
                        !==
                        $scoreB
                    ) {

                        return $scoreB
                            <=>
                            $scoreA;
                    }


                    return (
                        (float) (
                            $a[
                                'price'
                            ]
                            ?? 999
                        )
                    )
                    <=>
                    (
                        (float) (
                            $b[
                                'price'
                            ]
                            ?? 999
                        )
                    );
                }
            );
        }


        unset(
            $positionPlayers
        );


        return $byPosition;
    }


    /*
     * ============================================================
     * PARTIAL SEARCH SCORE
     * ============================================================
     *
     * v1 rewarded discretionary budget while squad slots remained.
     *
     * That caused the optimizer to preserve cash for its own sake
     * and produced an £8.0m bank in the real-data diagnostic.
     *
     * v2 removes that reward entirely.
     *
     * Search score now means:
     *
     *     role-aware player quality
     *     minus
     *     unnecessary spend in likely bench slots
     *
     * Money has no positive value until it is used to improve the
     * selected players.
     */

    private function calculateSearchScore(
        array $state
    ): float {

        $roleScore =
            (float) (
                $state[
                    'role_score'
                ]
                ?? 0
            );


        $benchCostPenalty =
            (float) (
                $state[
                    'bench_cost_penalty'
                ]
                ?? 0
            );


        return round(
            $roleScore
            -
            $benchCostPenalty,
            4
        );
    }


    /*
     * ============================================================
     * GOALKEEPER STARTER SCORE FLOOR
     * ============================================================
     *
     * Finds the strongest goalkeeper who already satisfies the
     * minimum confidence requirement.
     *
     * The eligibility floor is then:
     *
     * best reliable GK Starter Score
     * × GK_STARTER_QUALITY_RATIO
     */

    private function calculateGoalkeeperStarterScoreFloor(
        array $players
    ): ?float {

        $bestReliableStarterScore =
            null;


        foreach (
            $players
            as $player
        ) {

            if (
                (
                    $player[
                        'position'
                    ]
                    ?? null
                )
                !== 'GK'
            ) {

                continue;
            }


            $confidence =
                $player[
                    'reliability_confidence'
                ]
                ?? null;


            $starterScore =
                $player[
                    'starter_score'
                ]
                ?? null;


            if (
                !is_numeric(
                    $confidence
                )
                ||
                !is_numeric(
                    $starterScore
                )
            ) {

                continue;
            }


            if (
                (float) $confidence
                <
                self::GK_STARTER_MIN_CONFIDENCE
            ) {

                continue;
            }


            if (
                $bestReliableStarterScore === null
                ||
                (float) $starterScore
                >
                $bestReliableStarterScore
            ) {

                $bestReliableStarterScore =
                    (float) $starterScore;
            }
        }


        if (
            $bestReliableStarterScore === null
        ) {

            return null;
        }


        return round(
            $bestReliableStarterScore
            *
            self::GK_STARTER_QUALITY_RATIO,
            2
        );
    }
    
    /*
     * ============================================================
     * INDIVIDUAL GOALKEEPER STARTER ELIGIBILITY
     * ============================================================
     */

    private function isReliableStartingGoalkeeper(
        array $player,
        float $starterScoreFloor
    ): bool {

        if (
            (
                $player[
                    'position'
                ]
                ?? null
            )
            !== 'GK'
        ) {

            return false;
        }


        $confidence =
            $player[
                'reliability_confidence'
            ]
            ?? null;


        $starterScore =
            $player[
                'starter_score'
            ]
            ?? null;


        if (
            !is_numeric(
                $confidence
            )
            ||
            !is_numeric(
                $starterScore
            )
        ) {

            return false;
        }


        return (
            (float) $confidence
            >=
            self::GK_STARTER_MIN_CONFIDENCE
        )
        &&
        (
            (float) $starterScore
            >=
            $starterScoreFloor
        );
    }

    /*
     * ============================================================
     * GOALKEEPER STARTER RELIABILITY
     * ============================================================
     */

    private function hasReliableStartingGoalkeeper(
        array $squad,
        float $starterScoreFloor
    ): bool {

        foreach (
            $squad
            as $player
        ) {

            if (
                $this->isReliableStartingGoalkeeper(
                    $player,
                    $starterScoreFloor
                )
            ) {

                return true;
            }
        }


        return false;
    }


    /*
     * ============================================================
     * PLAYER NORMALISATION
     * ============================================================
     */

    private function normalisePlayer(
        array $player
    ): ?array {

        $playerId =
            isset(
                $player[
                    'player_id'
                ]
            )
                ? (int) $player[
                    'player_id'
                ]
                : 0;


        $teamId =
            isset(
                $player[
                    'team_id'
                ]
            )
                ? (int) $player[
                    'team_id'
                ]
                : 0;


        $position =
            strtoupper(
                trim(
                    (string) (
                        $player[
                            'position'
                        ]
                        ?? ''
                    )
                )
            );


        $price =
            isset(
                $player[
                    'price'
                ]
            )
            &&
            is_numeric(
                $player[
                    'price'
                ]
            )
                ? (float) $player[
                    'price'
                ]
                : null;


        $intelligence =
            isset(
                $player[
                    'intelligence_score'
                ]
            )
            &&
            is_numeric(
                $player[
                    'intelligence_score'
                ]
            )
                ? (float) $player[
                    'intelligence_score'
                ]
                : null;


        if (
            $playerId <= 0
            ||
            $teamId <= 0
            ||
            !array_key_exists(
                $position,
                self::POSITION_REQUIREMENTS
            )
            ||
            $price === null
            ||
            $price <= 0
            ||
            $intelligence === null
        ) {

            return null;
        }


        return [

            'player_id' =>
                $playerId,

            'name' =>
                $player[
                    'name'
                ]
                ?? (
                    $player[
                        'web_name'
                    ]
                    ?? (
                        'Player '
                        . $playerId
                    )
                ),

            'team_id' =>
                $teamId,

            'team_name' =>
                $player[
                    'team_name'
                ]
                ?? null,

            'position' =>
                $position,

            'price' =>
                round(
                    $price,
                    1
                ),

            'intelligence_score' =>
                $intelligence,

            'strength_rating' =>
                $this->numericOrNull(
                    $player[
                        'strength_rating'
                    ]
                    ?? null
                ),

            'value_rating' =>
                $this->numericOrNull(
                    $player[
                        'value_rating'
                    ]
                    ?? null
                ),

            'fixture_rating' =>
                $this->numericOrNull(
                    $player[
                        'fixture_rating'
                    ]
                    ?? null
                ),

            'availability_rating' =>
                $this->normalisePercentage(
                    $player[
                        'availability_rating'
                    ]
                    ?? null
                ),

            'sample_confidence' =>
                $this->normalisePercentage(
                    $player[
                        'sample_confidence'
                    ]
                    ?? null
                ),

            'effective_confidence' =>
                $this->normalisePercentage(
                    $player[
                        'effective_confidence'
                    ]
                    ?? null
                ),

            'reliability_confidence' =>
                $this->normalisePercentage(
                    $player[
                        'effective_confidence'
                    ]
                    ?? (
                        $player[
                            'sample_confidence'
                        ]
                        ?? null
                    )
                )
        ];
    }


    /*
     * ============================================================
     * PLAYER WILDCARD SCORE
     * ============================================================
     */

    private function calculateWildcardScore(
        array $player
    ): float {

        $weightedTotal =
            0.0;


        $weightTotal =
            0.0;


        /*
         * Intelligence - 45%
         */

        $weightedTotal +=
            (
                (float) $player[
                    'intelligence_score'
                ]
            )
            *
            0.45;


        $weightTotal +=
            0.45;


        /*
         * Strength - 20%
         */

        if (
            $player[
                'strength_rating'
            ]
            !== null
        ) {

            $weightedTotal +=
                (
                    (float) $player[
                        'strength_rating'
                    ]
                )
                *
                0.20;


            $weightTotal +=
                0.20;
        }


        /*
         * Value - 20%
         */

        if (
            $player[
                'value_rating'
            ]
            !== null
        ) {

            $weightedTotal +=
                (
                    (float) $player[
                        'value_rating'
                    ]
                )
                *
                0.20;


            $weightTotal +=
                0.20;
        }


        /*
         * Fixtures - 10%
         */

        if (
            $player[
                'fixture_rating'
            ]
            !== null
        ) {

            $weightedTotal +=
                (
                    (float) $player[
                        'fixture_rating'
                    ]
                )
                *
                0.10;


            $weightTotal +=
                0.10;
        }


        /*
         * Availability - 5%
         */

        if (
            $player[
                'availability_rating'
            ]
            !== null
        ) {

            $weightedTotal +=
                (
                    (float) $player[
                        'availability_rating'
                    ]
                )
                *
                0.05;


            $weightTotal +=
                0.05;
        }


        if (
            $weightTotal <= 0
        ) {

            return 0.0;
        }


        $score =
            $weightedTotal
            /
            $weightTotal;


        /*
         * Mild confidence modifier.
         */

        $confidence =
            $player[
                'reliability_confidence'
            ]
            ?? null;


        if ($confidence !== null) {

            $confidenceMultiplier =
                0.85
                +
                (
                    0.15
                    *
                    (
                        $confidence
                        /
                        100
                    )
                );


            $score *=
                $confidenceMultiplier;
        }


        return round(
            max(
                0,
                min(
                    100,
                    $score
                )
            ),
            2
        );
    }

    /*
     * ============================================================
     * STARTER SCORE
     * ============================================================
     *
     * Starter quality deliberately excludes Value Rating.
     *
     * Premium players should not be suppressed merely because they
     * are expensive when the question is:
     *
     *     "How strong is this player as a likely starter?"
     *
     * Current calibration:
     *
     * Intelligence   50%
     * Strength       25%
     * Fixtures       15%
     * Availability   10%
     */

    private function calculateStarterScore(
        array $player
    ): float {

        return $this->calculateWeightedPlayerScore(
            $player,
            [

                'intelligence_score' =>
                    0.50,

                'strength_rating' =>
                    0.25,

                'fixture_rating' =>
                    0.15,

                'availability_rating' =>
                    0.10
            ]
        );
    }


    /*
     * ============================================================
     * SQUAD VALUE SCORE
     * ============================================================
     *
     * Lower-impact squad slots need to provide useful cover without
     * consuming money that could improve the Starting XI.
     *
     * Current calibration:
     *
     * Intelligence   35%
     * Strength       15%
     * Value          35%
     * Fixtures       10%
     * Availability    5%
     */

    private function calculateSquadValueScore(
        array $player
    ): float {

        return $this->calculateWeightedPlayerScore(
            $player,
            [

                'intelligence_score' =>
                    0.35,

                'strength_rating' =>
                    0.15,

                'value_rating' =>
                    0.35,

                'fixture_rating' =>
                    0.10,

                'availability_rating' =>
                    0.05
            ]
        );
    }


    /*
     * ============================================================
     * GENERIC WEIGHTED PLAYER SCORE
     * ============================================================
     */

    private function calculateWeightedPlayerScore(
        array $player,
        array $weights
    ): float {

        $weightedTotal =
            0.0;


        $weightTotal =
            0.0;


        foreach (
            $weights
            as $field => $weight
        ) {

            $value =
                $player[
                    $field
                ]
                ?? null;


            if (
                $value === null
                ||
                !is_numeric(
                    $value
                )
            ) {

                continue;
            }


            $weightedTotal +=
                (
                    (float) $value
                )
                *
                $weight;


            $weightTotal +=
                $weight;
        }


        if (
            $weightTotal <= 0
        ) {

            return 0.0;
        }


        $score =
            $weightedTotal
            /
            $weightTotal;


        /*
         * Keep evidence confidence consistent with the existing
         * Wildcard Score behaviour.
         */

        $confidence =
            $player[
                'reliability_confidence'
            ]
            ?? null;


        if (
            $confidence !== null
            &&
            is_numeric(
                $confidence
            )
        ) {

            $confidenceMultiplier =
                0.85
                +
                (
                    0.15
                    *
                    (
                        (float) $confidence
                        /
                        100
                    )
                );


            $score *=
                $confidenceMultiplier;
        }


        return round(
            max(
                0,
                min(
                    100,
                    $score
                )
            ),
            2
        );
    }


    /*
     * ============================================================
     * SEARCH POOL CONSTRUCTION
     * ============================================================
     */

    private function buildSearchPool(
        array $players
    ): array {

        /*
         * ========================================================
         * TOP STARTER CANDIDATES
         * ========================================================
         */

        $byStarter =
            $players;


        usort(
            $byStarter,
            static function (
                array $a,
                array $b
            ): int {

                $scoreA =
                    (float) (
                        $a[
                            'starter_score'
                        ]
                        ?? 0
                    );


                $scoreB =
                    (float) (
                        $b[
                            'starter_score'
                        ]
                        ?? 0
                    );


                if (
                    $scoreA
                    !==
                    $scoreB
                ) {

                    return $scoreB
                        <=>
                        $scoreA;
                }


                return (
                    (float) (
                        $a[
                            'price'
                        ]
                        ?? 999
                    )
                )
                <=>
                (
                    (float) (
                        $b[
                            'price'
                        ]
                        ?? 999
                    )
                );
            }
        );


        $starterCandidates =
            array_slice(
                $byStarter,
                0,
                self::POSITION_STARTER_LIMIT
            );


        /*
         * ========================================================
         * TOP SQUAD-VALUE CANDIDATES
         * ========================================================
         */

        $byValue =
            $players;


        usort(
            $byValue,
            static function (
                array $a,
                array $b
            ): int {

                $scoreA =
                    (float) (
                        $a[
                            'squad_value_score'
                        ]
                        ?? 0
                    );


                $scoreB =
                    (float) (
                        $b[
                            'squad_value_score'
                        ]
                        ?? 0
                    );


                if (
                    $scoreA
                    !==
                    $scoreB
                ) {

                    return $scoreB
                        <=>
                        $scoreA;
                }


                return (
                    (float) (
                        $a[
                            'price'
                        ]
                        ?? 999
                    )
                )
                <=>
                (
                    (float) (
                        $b[
                            'price'
                        ]
                        ?? 999
                    )
                );
            }
        );


        $valueCandidates =
            array_slice(
                $byValue,
                0,
                self::POSITION_VALUE_LIMIT
            );


        /*
         * ========================================================
         * CHEAPEST BUDGET ENABLERS
         * ========================================================
         */

        $byPrice =
            $players;


        usort(
            $byPrice,
            static function (
                array $a,
                array $b
            ): int {

                $priceA =
                    (float) (
                        $a[
                            'price'
                        ]
                        ?? 999
                    );


                $priceB =
                    (float) (
                        $b[
                            'price'
                        ]
                        ?? 999
                    );


                if (
                    $priceA
                    !==
                    $priceB
                ) {

                    return $priceA
                        <=>
                        $priceB;
                }


                return (
                    (float) (
                        $b[
                            'squad_value_score'
                        ]
                        ?? 0
                    )
                )
                <=>
                (
                    (float) (
                        $a[
                            'squad_value_score'
                        ]
                        ?? 0
                    )
                );
            }
        );


        $cheapest =
            array_slice(
                $byPrice,
                0,
                self::POSITION_CHEAP_LIMIT
            );


        /*
         * Premium starters, value options and cheap enablers all
         * survive pruning independently.
         */

        return $this->deduplicateCandidates(
            array_merge(
                $starterCandidates,
                $valueCandidates,
                $cheapest
            )
        );
    }


    /*
     * ============================================================
     * FAST MINIMUM REMAINING COST
     * ============================================================
     */

    private function calculateFastMinimumRemainingCost(
        array $remainingRequirements,
        array $priceSortedPools,
        array $selectedIds,
        array $currentSquad,
        array $candidate,
        float $gkStarterScoreFloor
    ): ?float {

        /*
         * ============================================================
         * BUILD PROVISIONAL SQUAD
         * ============================================================
         *
         * The candidate has not yet been added to $currentSquad at the
         * point this helper runs, so include it temporarily.
         */

        $provisionalSquad =
            $currentSquad;


        $provisionalSquad[] =
            $candidate;


        /*
         * Determine whether a reliable starting goalkeeper has already
         * been selected.
         */

        $reliableGoalkeeperAlreadySelected =
            $this->hasReliableStartingGoalkeeper(
                $provisionalSquad,
                $gkStarterScoreFloor
            );


        $minimumCost =
            0.0;


        foreach (
            $remainingRequirements
            as $position => $required
        ) {

            if ($required <= 0) {
                continue;
            }


            /*
             * ========================================================
             * GOALKEEPER SPECIAL CASE
             * ========================================================
             *
             * If no reliable GK has yet been selected and goalkeeper
             * slots remain, at least ONE of those remaining slots must
             * contain a GK who passes:
             *
             * - minimum confidence
             * - minimum Starter Score quality
             *
             * The other remaining goalkeeper may still simply be the
             * cheapest available backup.
             */

            if (
                $position === 'GK'
                &&
                !$reliableGoalkeeperAlreadySelected
            ) {

                $availableGoalkeepers =
                    [];


                foreach (
                    $priceSortedPools[
                        'GK'
                    ]
                    ?? []
                    as $goalkeeper
                ) {

                    $playerId =
                        (int) (
                            $goalkeeper[
                                'player_id'
                            ]
                            ?? 0
                        );


                    if (
                        isset(
                            $selectedIds[
                                $playerId
                            ]
                        )
                    ) {

                        continue;
                    }


                    $availableGoalkeepers[] =
                        $goalkeeper;
                }


                if (
                    count(
                        $availableGoalkeepers
                    )
                    <
                    $required
                ) {

                    return null;
                }


                /*
                 * Find the cheapest remaining goalkeeper who is
                 * genuinely starter-eligible.
                 */

                $cheapestReliableGoalkeeper =
                    null;


                foreach (
                    $availableGoalkeepers
                    as $goalkeeper
                ) {

                    if (
                        !$this->isReliableStartingGoalkeeper(
                            $goalkeeper,
                            $gkStarterScoreFloor
                        )
                    ) {

                        continue;
                    }


                    $cheapestReliableGoalkeeper =
                        $goalkeeper;

                    break;
                }


                if (
                    $cheapestReliableGoalkeeper === null
                ) {

                    /*
                     * No valid route remains because the squad still
                     * needs a starting goalkeeper but none of the
                     * remaining candidates can fulfil that role.
                     */

                    return null;
                }


                /*
                 * Reserve the reliable goalkeeper first.
                 */

                $minimumCost +=
                    (float) (
                        $cheapestReliableGoalkeeper[
                            'price'
                        ]
                        ?? 0
                    );


                $reliablePlayerId =
                    (int) (
                        $cheapestReliableGoalkeeper[
                            'player_id'
                        ]
                        ?? 0
                    );


                /*
                 * If more than one GK slot still remains, fill the rest
                 * using the cheapest remaining goalkeeper(s).
                 */

                $additionalRequired =
                    $required - 1;


                if (
                    $additionalRequired > 0
                ) {

                    $found =
                        0;


                    foreach (
                        $availableGoalkeepers
                        as $goalkeeper
                    ) {

                        $playerId =
                            (int) (
                                $goalkeeper[
                                    'player_id'
                                ]
                                ?? 0
                            );


                        if (
                            $playerId
                            ===
                            $reliablePlayerId
                        ) {

                            continue;
                        }


                        $minimumCost +=
                            (float) (
                                $goalkeeper[
                                    'price'
                                ]
                                ?? 0
                            );


                        $found++;


                        if (
                            $found
                            >=
                            $additionalRequired
                        ) {

                            break;
                        }
                    }


                    if (
                        $found
                        <
                        $additionalRequired
                    ) {

                        return null;
                    }
                }


                continue;
            }


            /*
             * ========================================================
             * NORMAL POSITION LOWER BOUND
             * ========================================================
             */

            $found =
                0;


            foreach (
                $priceSortedPools[
                    $position
                ]
                ?? []
                as $remainingCandidate
            ) {

                $playerId =
                    (int) (
                        $remainingCandidate[
                            'player_id'
                        ]
                        ?? 0
                    );


                if (
                    isset(
                        $selectedIds[
                            $playerId
                        ]
                    )
                ) {

                    continue;
                }


                $minimumCost +=
                    (float) (
                        $remainingCandidate[
                            'price'
                        ]
                        ?? 0
                    );


                $found++;


                if (
                    $found
                    >=
                    $required
                ) {

                    break;
                }
            }


            if (
                $found
                <
                $required
            ) {

                return null;
            }
        }


        return $minimumCost;
    }


    /*
     * ============================================================
     * CANDIDATE DEDUPLICATION
     * ============================================================
     */

    private function deduplicateCandidates(
        array $players
    ): array {

        $unique =
            [];


        foreach (
            $players
            as $player
        ) {

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


            if (
                !isset(
                    $unique[
                        $playerId
                    ]
                )
            ) {

                $unique[
                    $playerId
                ] =
                    $player;

                continue;
            }


            $existingScore =
                (float) (
                    $unique[
                        $playerId
                    ][
                        'wildcard_score'
                    ]
                    ?? 0
                );


            $newScore =
                (float) (
                    $player[
                        'wildcard_score'
                    ]
                    ?? 0
                );


            if (
                $newScore
                >
                $existingScore
            ) {

                $unique[
                    $playerId
                ] =
                    $player;
            }
        }


        return array_values(
            $unique
        );
    }


    /*
     * ============================================================
     * SEARCH STATE DEDUPLICATION
     * ============================================================
     */

    private function deduplicateStates(
        array $states
    ): array {

        $unique =
            [];


        foreach (
            $states
            as $state
        ) {

            $ids =
                array_keys(
                    $state[
                        'selected_ids'
                    ]
                    ?? []
                );


            sort(
                $ids,
                SORT_NUMERIC
            );


            $key =
                implode(
                    '-',
                    $ids
                );


            if (
                !isset(
                    $unique[
                        $key
                    ]
                )
            ) {

                $unique[
                    $key
                ] =
                    $state;

                continue;
            }


            $existingScore =
                (float) (
                    $unique[
                        $key
                    ][
                        'search_score'
                    ]
                    ?? 0
                );


            $newScore =
                (float) (
                    $state[
                        'search_score'
                    ]
                    ?? 0
                );


            if (
                $newScore
                >
                $existingScore
            ) {

                $unique[
                    $key
                ] =
                    $state;
            }
        }


        return array_values(
            $unique
        );
    }


    /*
     * ============================================================
     * FINAL SQUAD VALIDATION
     * ============================================================
     */

    public function validateSquad(
        array $squad,
        float $budget = 100.0
    ): array {

        $issues =
            [];


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


        $playerIds =
            [];


        $cost =
            0.0;


        foreach (
            $squad
            as $player
        ) {

            $playerId =
                (int) (
                    $player[
                        'player_id'
                    ]
                    ?? 0
                );


            $teamId =
                (int) (
                    $player[
                        'team_id'
                    ]
                    ?? 0
                );


            $position =
                strtoupper(
                    trim(
                        (string) (
                            $player[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                );


            $price =
                is_numeric(
                    $player[
                        'price'
                    ]
                    ?? null
                )
                    ? (float) $player[
                        'price'
                    ]
                    : 0.0;


            $cost +=
                $price;


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

            } else {

                $issues[] =
                    'Invalid player position detected.';
            }


            if ($teamId > 0) {

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


            if ($playerId > 0) {

                if (
                    isset(
                        $playerIds[
                            $playerId
                        ]
                    )
                ) {

                    $issues[] =
                        'Duplicate player detected: '
                        . $playerId;

                } else {

                    $playerIds[
                        $playerId
                    ] =
                        true;
                }
            }
        }


        if (
            count(
                $squad
            )
            !== 15
        ) {

            $issues[] =
                'Squad must contain exactly 15 players.';
        }


        foreach (
            self::POSITION_REQUIREMENTS
            as $position => $required
        ) {

            if (
                (
                    $positionCounts[
                        $position
                    ]
                    ?? 0
                )
                !==
                $required
            ) {

                $issues[] =
                    $position
                    . ' count must be '
                    . $required
                    . '.';
            }
        }


        foreach (
            $teamCounts
            as $teamId => $count
        ) {

            if (
                $count
                >
                self::MAX_PLAYERS_PER_CLUB
            ) {

                $issues[] =
                    'Team '
                    . $teamId
                    . ' exceeds the three-player limit.';
            }
        }


        if (
            $cost
            >
            $budget
        ) {

            $issues[] =
                'Squad exceeds the available budget.';
        }


        return [

            'is_valid' =>
                empty(
                    $issues
                ),

            'player_count' =>
                count(
                    $squad
                ),

            'position_counts' =>
                $positionCounts,

            'team_counts' =>
                $teamCounts,

            'cost' =>
                round(
                    $cost,
                    1
                ),

            'budget' =>
                round(
                    $budget,
                    1
                ),

            'issues' =>
                $issues
        ];
    }


    /*
     * ============================================================
     * RAW 15-PLAYER SCORE
     * ============================================================
     */

    private function calculateSquadScore(
        array $squad
    ): float {

        if (
            empty(
                $squad
            )
        ) {

            return 0.0;
        }


        $total =
            0.0;


        foreach (
            $squad
            as $player
        ) {

            $total +=
                (float) (
                    $player[
                        'wildcard_score'
                    ]
                    ?? 0
                );
        }


        return round(
            $total
            /
            count(
                $squad
            ),
            2
        );
    }


    /*
     * ============================================================
     * FINAL SQUAD ORDER
     * ============================================================
     */

    private function sortSquadByPosition(
        array $squad
    ): array {

        $positionOrder = [

            'GK' =>
                1,

            'DEF' =>
                2,

            'MID' =>
                3,

            'FWD' =>
                4
        ];


        usort(
            $squad,
            static function (
                array $a,
                array $b
            ) use (
                $positionOrder
            ): int {

                $orderA =
                    $positionOrder[
                        $a[
                            'position'
                        ]
                        ?? ''
                    ]
                    ?? 999;


                $orderB =
                    $positionOrder[
                        $b[
                            'position'
                        ]
                        ?? ''
                    ]
                    ?? 999;


                if (
                    $orderA
                    !==
                    $orderB
                ) {

                    return $orderA
                        <=>
                        $orderB;
                }


                return (
                    (float) (
                        $b[
                            'wildcard_score'
                        ]
                        ?? 0
                    )
                )
                <=>
                (
                    (float) (
                        $a[
                            'wildcard_score'
                        ]
                        ?? 0
                    )
                );
            }
        );


        return $squad;
    }


    /*
     * ============================================================
     * GENERIC HELPERS
     * ============================================================
     */

    private function normalisePercentage(
        mixed $value
    ): ?float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        $value =
            (float) $value;


        if (
            $value >= 0
            &&
            $value <= 1
        ) {

            $value *=
                100;
        }


        return max(
            0,
            min(
                100,
                $value
            )
        );
    }


    private function numericOrNull(
        mixed $value
    ): ?float {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return (float) $value;
    }


    /*
     * ============================================================
     * INVALID RESULT
     * ============================================================
     */

    private function invalidResult(
        string $message
    ): array {

        return [

            'status' =>
                'invalid',

            'message' =>
                $message,

            'budget' =>
                null,

            'cost' =>
                null,

            'bank' =>
                null,

            'wildcard_score' =>
                null,

            'role_score' =>
                null,

            'bench_cost_penalty' =>
                null,

            'structure_score' =>
                null,

            'starting_xi_score' =>
                null,

            'bench_score' =>
                null,

            'formation' =>
                null,

            'starting_xi' =>
                [],

            'bench' =>
                [],

            'squad' =>
                [],

            'validation' => [

                'is_valid' =>
                    false,

                'player_count' =>
                    0,

                'position_counts' => [

                    'GK' =>
                        0,

                    'DEF' =>
                        0,

                    'MID' =>
                        0,

                    'FWD' =>
                        0
                ],

                'team_counts' =>
                    [],

                'cost' =>
                    0.0,

                'budget' =>
                    null,

                'issues' => [

                    $message
                ]
            ]
        ];
    }
}