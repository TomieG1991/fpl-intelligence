<?php


class SquadTransferOptimizer
{

    private TransferDecision $transferDecision;


    public function __construct()
    {

        $this->transferDecision =
            new TransferDecision();
    }


    /**
     * Find the strongest legal single-transfer options
     * for the highest-priority players in a squad.
     */
    public function findBestSingleTransfers(
        array $squadAnalysis,
        array $allPlayers,
        int $priorityLimit = 5,
        int $replacementLimit = 5
    ): array {

        /*
         * ====================================================
         * VALIDATE INPUT
         * ====================================================
         */

        if (
            (
                $squadAnalysis[
                    'validation'
                ]['is_valid']
                ?? false
            )
            !== true
        ) {

            return $this->emptyResult(
                'Squad analysis is invalid.'
            );
        }


        if (
            empty(
                $squadAnalysis[
                    'ranking'
                ]
                ?? []
            )
        ) {

            return $this->emptyResult(
                'Squad contains no transfer-priority ranking.'
            );
        }


        if (empty($allPlayers)) {

            return $this->emptyResult(
                'No replacement players were supplied.'
            );
        }


        if (
            $priorityLimit <= 0
            ||
            $replacementLimit <= 0
        ) {

            return $this->emptyResult(
                'Transfer limits must be greater than zero.'
            );
        }


        /*
         * ====================================================
         * SQUAD CONTEXT
         * ====================================================
         */

        $squad =
            $squadAnalysis[
                'squad'
            ]
            ?? [];


        $bank =
            (float) (
                $squadAnalysis[
                    'bank'
                ]
                ?? 0
            );


        $squadPlayerIds =
            [];


        $squadPlayersById =
            [];


        $teamCounts =
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


            if ($playerId > 0) {

                $squadPlayerIds[
                    $playerId
                ] =
                    true;


                $squadPlayersById[
                    $playerId
                ] =
                    $player;
            }


            $teamId =
                (int) (
                    $player[
                        'team_id'
                    ]
                    ?? 0
                );


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
        }


        /*
         * ====================================================
         * HIGHEST PRIORITY OUTGOING PLAYERS
         * ====================================================
         */

        $priorityPlayers =
            array_slice(
                $squadAnalysis[
                    'ranking'
                ],
                0,
                $priorityLimit
            );


        $recommendations =
            [];


        foreach (
            $priorityPlayers
            as $outgoing
        ) {

            $outgoingPlayerId =
                (int) (
                    $outgoing[
                        'player_id'
                    ]
                    ?? 0
                );
                
            $outgoingPlayer =
                $squadPlayersById[
                    $outgoingPlayerId
                ]
                ?? $outgoing;


            $outgoingPosition =
                strtoupper(
                    trim(
                        (string) (
                            $outgoingPlayer[
                                'position'
                            ]
                            ?? ''
                        )
                    )
                );


            $outgoingPrice =
                (float) (
                    $outgoingPlayer[
                        'price'
                    ]
                    ?? 0
                );


            $outgoingTeamId =
                (int) (
                    $outgoingPlayer[
                        'team_id'
                    ]
                    ?? 0
                );


            if (
                $outgoingPlayerId <= 0
                ||
                $outgoingPosition === ''
            ) {

                continue;
            }


            /*
             * For now, use current price as sale value.
             */
            $availableBudget =
                $outgoingPrice
                +
                $bank;


            $evaluatedCandidates =
                [];


            foreach (
                $allPlayers
                as $candidate
            ) {

                $candidatePlayerId =
                    (int) (
                        $candidate[
                            'player_id'
                        ]
                        ?? 0
                    );


                if ($candidatePlayerId <= 0) {
                    continue;
                }


                /*
                 * Cannot transfer to the same player.
                 */
                if (
                    $candidatePlayerId
                    ===
                    $outgoingPlayerId
                ) {

                    continue;
                }


                /*
                 * Incoming player cannot already be owned.
                 */
                if (
                    isset(
                        $squadPlayerIds[
                            $candidatePlayerId
                        ]
                    )
                ) {

                    continue;
                }


                $candidatePosition =
                    strtoupper(
                        trim(
                            (string) (
                                $candidate[
                                    'position'
                                ]
                                ?? ''
                            )
                        )
                    );


                /*
                 * Must preserve position.
                 */
                if (
                    $candidatePosition
                    !==
                    $outgoingPosition
                ) {

                    continue;
                }


                $candidatePrice =
                    $candidate[
                        'price'
                    ]
                    ?? null;


                if (
                    $candidatePrice === null
                    ||
                    !is_numeric(
                        $candidatePrice
                    )
                ) {

                    continue;
                }


                $candidatePrice =
                    (float) $candidatePrice;


                /*
                 * Must be affordable.
                 */
                if (
                    $candidatePrice
                    >
                    $availableBudget
                ) {

                    continue;
                }


                $candidateTeamId =
                    (int) (
                        $candidate[
                            'team_id'
                        ]
                        ?? 0
                    );


                if ($candidateTeamId <= 0) {
                    continue;
                }


                /*
                 * =================================================
                 * CLUB LIMIT AFTER TRANSFER
                 * =================================================
                 */

                $candidateTeamCount =
                    $teamCounts[
                        $candidateTeamId
                    ]
                    ?? 0;


                /*
                 * If outgoing and incoming players are from
                 * the same club, the outgoing player leaves
                 * before the incoming one is counted.
                 */
                if (
                    $candidateTeamId
                    ===
                    $outgoingTeamId
                ) {

                    $candidateTeamCount--;
                }


                $candidateTeamCount++;


                if (
                    $candidateTeamCount > 3
                ) {

                    continue;
                }


                /*
                 * =================================================
                 * TRANSFER INTELLIGENCE
                 * =================================================
                 */

                $decision =
                    $this->transferDecision
                        ->evaluateTransfer(
                            $outgoingPlayer,
                            $candidate
                        );


                $evaluatedCandidates[] = [

                    'player' =>
                        $candidate,

                    'decision' =>
                        $decision,

                    'decision_score' =>
                        $decision[
                            'decision_score'
                        ]
                        ?? null,

                    'decision_type' =>
                        $decision[
                            'decision_type'
                        ]
                        ?? null,

                    'budget_after' =>
                        round(
                            $availableBudget
                            -
                            $candidatePrice,
                            1
                        )
                ];
            }


            /*
             * =================================================
             * RANK REPLACEMENTS
             * =================================================
             */

            usort(
                $evaluatedCandidates,
                function (
                    array $a,
                    array $b
                ): int {

                    /*
                     * -----------------------------------------
                     * 1. DECISION TYPE
                     * -----------------------------------------
                     *
                     * Prefer reliable recommendation types
                     * before considering the raw decision score.
                     */

                    $typeWeightA =
                        $this->decisionTypeWeight(
                            $a[
                                'decision_type'
                            ]
                            ?? null
                        );


                    $typeWeightB =
                        $this->decisionTypeWeight(
                            $b[
                                'decision_type'
                            ]
                            ?? null
                        );


                    if (
                        $typeWeightA
                        !==
                        $typeWeightB
                    ) {

                        return $typeWeightB
                            <=>
                            $typeWeightA;
                    }


                    /*
                     * -----------------------------------------
                     * 2. DECISION SCORE
                     * -----------------------------------------
                     */

                    $scoreA =
                        is_numeric(
                            $a[
                                'decision_score'
                            ]
                            ?? null
                        )
                            ? (float) $a[
                                'decision_score'
                            ]
                            : -999999.0;


                    $scoreB =
                        is_numeric(
                            $b[
                                'decision_score'
                            ]
                            ?? null
                        )
                            ? (float) $b[
                                'decision_score'
                            ]
                            : -999999.0;


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
                     * -----------------------------------------
                     * 3. INCOMING INTELLIGENCE
                     * -----------------------------------------
                     */

                    $intelligenceA =
                        is_numeric(
                            $a[
                                'player'
                            ]['intelligence_score']
                            ?? null
                        )
                            ? (float) $a[
                                'player'
                            ]['intelligence_score']
                            : -999999.0;


                    $intelligenceB =
                        is_numeric(
                            $b[
                                'player'
                            ]['intelligence_score']
                            ?? null
                        )
                            ? (float) $b[
                                'player'
                            ]['intelligence_score']
                            : -999999.0;


                    if (
                        $intelligenceA
                        !==
                        $intelligenceB
                    ) {

                        return $intelligenceB
                            <=>
                            $intelligenceA;
                    }


                    /*
                     * -----------------------------------------
                     * 4. BUDGET REMAINING
                     * -----------------------------------------
                     */

                    return (
                        (float) (
                            $b[
                                'budget_after'
                            ]
                            ?? 0
                        )
                    )
                    <=>
                    (
                        (float) (
                            $a[
                                'budget_after'
                            ]
                            ?? 0
                        )
                    );
                }
            );


            $legalCandidateCount =
                count(
                    $evaluatedCandidates
                );


            $rankedReplacements =
                array_slice(
                    $evaluatedCandidates,
                    0,
                    $replacementLimit
                );


            foreach (
                $rankedReplacements
                as $index => &$replacement
            ) {

                $replacement[
                    'rank'
                ] =
                    $index + 1;
            }


            unset(
                $replacement
            );


            $recommendations[] = [

                'outgoing' =>
                    $outgoing,

                'transfer_priority' =>
                    $outgoing[
                        'transfer_priority'
                    ]
                    ?? null,

                'priority_label' =>
                    $outgoing[
                        'priority_label'
                    ]
                    ?? null,

                'available_budget' =>
                    round(
                        $availableBudget,
                        1
                    ),

                'legal_candidate_count' =>
                    $legalCandidateCount,

                'replacement_count' =>
                    count(
                        $rankedReplacements
                    ),

                'replacements' =>
                    $rankedReplacements
            ];
        }


        /*
         * ====================================================
         * RESULT
         * ====================================================
         */

        return [

            'status' =>
                'success',

            'bank' =>
                round(
                    $bank,
                    1
                ),

            'priority_limit' =>
                $priorityLimit,

            'replacement_limit' =>
                $replacementLimit,

            'players_considered' =>
                count(
                    $recommendations
                ),

            'recommendations' =>
                $recommendations
        ];
    }
    
    /**
     * Find the strongest legal two-transfer restructures
     * from the highest-priority players in a squad.
     */
    public function findBestDoubleTransfers(
        array $squadAnalysis,
        array $allPlayers,
        int $outgoingLimit = 5,
        int $resultLimit = 10
    ): array {

        /*
         * ========================================================
         * VALIDATE INPUT
         * ========================================================
         */

        if (
            (
                $squadAnalysis[
                    'validation'
                ]['is_valid']
                ?? false
            )
            !== true
        ) {

            return $this->emptyDoubleResult(
                'Squad analysis is invalid.'
            );
        }


        if (
            empty(
                $squadAnalysis[
                    'ranking'
                ]
                ?? []
            )
        ) {

            return $this->emptyDoubleResult(
                'Squad contains no transfer-priority ranking.'
            );
        }


        if (empty($allPlayers)) {

            return $this->emptyDoubleResult(
                'No replacement players were supplied.'
            );
        }


        if (
            $outgoingLimit < 2
            ||
            $resultLimit <= 0
        ) {

            return $this->emptyDoubleResult(
                'Outgoing limit must be at least two and result limit must be greater than zero.'
            );
        }


        /*
         * ========================================================
         * SQUAD CONTEXT
         * ========================================================
         */

        $squad =
            $squadAnalysis[
                'squad'
            ]
            ?? [];


        $bank =
            (float) (
                $squadAnalysis[
                    'bank'
                ]
                ?? 0
            );


        $squadPlayerIds =
            [];


        $squadPlayersById =
            [];


        $baseTeamCounts =
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


            if ($playerId > 0) {

                $squadPlayerIds[
                    $playerId
                ] =
                    true;


                $squadPlayersById[
                    $playerId
                ] =
                    $player;
            }


            $teamId =
                (int) (
                    $player[
                        'team_id'
                    ]
                    ?? 0
                );


            if ($teamId > 0) {

                $baseTeamCounts[
                    $teamId
                ] =
                    (
                        $baseTeamCounts[
                            $teamId
                        ]
                        ?? 0
                    )
                    +
                    1;
            }
        }


        /*
         * ========================================================
         * OUTGOING PLAYER POOL
         * ========================================================
         */

        $priorityPlayers =
            array_slice(
                $squadAnalysis[
                    'ranking'
                ],
                0,
                $outgoingLimit
            );


        $priorityCount =
            count(
                $priorityPlayers
            );


        if ($priorityCount < 2) {

            return $this->emptyDoubleResult(
                'Not enough priority players are available.'
            );
        }


        /*
         * ========================================================
         * TRANSFER OPTIMIZER
         * ========================================================
         */

        $transferOptimizer =
            new TransferOptimizer();


        $allCombinations =
            [];


        $outgoingPairsConsidered =
            0;


        /*
         * ========================================================
         * GENERATE OUTGOING PAIRS
         * ========================================================
         */

        for (
            $i = 0;
            $i < $priorityCount - 1;
            $i++
        ) {

            for (
                $j = $i + 1;
                $j < $priorityCount;
                $j++
            ) {

                $priorityA =
                    $priorityPlayers[
                        $i
                    ];


                $priorityB =
                    $priorityPlayers[
                        $j
                    ];


                $playerIdA =
                    (int) (
                        $priorityA[
                            'player_id'
                        ]
                        ?? 0
                    );


                $playerIdB =
                    (int) (
                        $priorityB[
                            'player_id'
                        ]
                        ?? 0
                    );


                if (
                    $playerIdA <= 0
                    ||
                    $playerIdB <= 0
                    ||
                    $playerIdA === $playerIdB
                ) {

                    continue;
                }


                $currentPlayerA =
                    $squadPlayersById[
                        $playerIdA
                    ]
                    ?? null;


                $currentPlayerB =
                    $squadPlayersById[
                        $playerIdB
                    ]
                    ?? null;


                if (
                    $currentPlayerA === null
                    ||
                    $currentPlayerB === null
                ) {

                    continue;
                }


                $positionA =
                    strtoupper(
                        trim(
                            (string) (
                                $currentPlayerA[
                                    'position'
                                ]
                                ?? ''
                            )
                        )
                    );


                $positionB =
                    strtoupper(
                        trim(
                            (string) (
                                $currentPlayerB[
                                    'position'
                                ]
                                ?? ''
                            )
                        )
                    );


                if (
                    $positionA === ''
                    ||
                    $positionB === ''
                ) {

                    continue;
                }


                $outgoingPairsConsidered++;


                /*
                 * =================================================
                 * BUILD TEMPORARY TEAM COUNTS AFTER SALES
                 * =================================================
                 */

                $teamCountsAfterSales =
                    $baseTeamCounts;


                $outgoingTeamIdA =
                    (int) (
                        $currentPlayerA[
                            'team_id'
                        ]
                        ?? 0
                    );


                $outgoingTeamIdB =
                    (int) (
                        $currentPlayerB[
                            'team_id'
                        ]
                        ?? 0
                    );


                if (
                    $outgoingTeamIdA > 0
                    &&
                    isset(
                        $teamCountsAfterSales[
                            $outgoingTeamIdA
                        ]
                    )
                ) {

                    $teamCountsAfterSales[
                        $outgoingTeamIdA
                    ]--;
                }


                if (
                    $outgoingTeamIdB > 0
                    &&
                    isset(
                        $teamCountsAfterSales[
                            $outgoingTeamIdB
                        ]
                    )
                ) {

                    $teamCountsAfterSales[
                        $outgoingTeamIdB
                    ]--;
                }


                /*
                 * =================================================
                 * BUILD LEGAL CANDIDATE POOLS
                 * =================================================
                 */

                $candidatePoolA =
                    [];


                $candidatePoolB =
                    [];


                foreach (
                    $allPlayers
                    as $candidate
                ) {

                    $candidateId =
                        (int) (
                            $candidate[
                                'player_id'
                            ]
                            ?? 0
                        );


                    if ($candidateId <= 0) {
                        continue;
                    }


                    /*
                     * Incoming players cannot already be owned,
                     * except for the two outgoing players who are
                     * leaving the squad.
                     */
                    if (
                        isset(
                            $squadPlayerIds[
                                $candidateId
                            ]
                        )
                        
                    ) {

                        continue;
                    }


                    $candidatePosition =
                        strtoupper(
                            trim(
                                (string) (
                                    $candidate[
                                        'position'
                                    ]
                                    ?? ''
                                )
                            )
                        );


                    if ($candidatePosition === '') {
                        continue;
                    }


                    $candidateTeamId =
                        (int) (
                            $candidate[
                                'team_id'
                            ]
                            ?? 0
                        );


                    if ($candidateTeamId <= 0) {
                        continue;
                    }


                    /*
                     * Initial individual club legality.
                     *
                     * Final combined club legality is checked again
                     * after TransferOptimizer returns candidate pairs.
                     */
                    $currentTeamCount =
                        $teamCountsAfterSales[
                            $candidateTeamId
                        ]
                        ?? 0;


                    if ($currentTeamCount >= 3) {
                        continue;
                    }


                    if (
                        $candidatePosition
                        ===
                        $positionA
                    ) {

                        $candidatePoolA[] =
                            $candidate;
                    }


                    if (
                        $candidatePosition
                        ===
                        $positionB
                    ) {

                        $candidatePoolB[] =
                            $candidate;
                    }
                }


                if (
                    empty(
                        $candidatePoolA
                    )
                    ||
                    empty(
                        $candidatePoolB
                    )
                ) {

                    continue;
                }
                
                /*
                 * =================================================
                 * PRUNE LARGE CANDIDATE POOLS
                 * =================================================
                 *
                 * Only the strongest individual options need to
                 * reach the expensive two-player combination stage.
                 */

                $candidatePoolA =
                    $this->pruneDoubleCandidatePool(
                        $currentPlayerA,
                        $candidatePoolA,
                        15
                    );


                $candidatePoolB =
                    $this->pruneDoubleCandidatePool(
                        $currentPlayerB,
                        $candidatePoolB,
                        15
                    );


                if (
                    empty(
                        $candidatePoolA
                    )
                    ||
                    empty(
                        $candidatePoolB
                    )
                ) {

                    continue;
                }


                /*
                 * =================================================
                 * OPTIMIZE PAIR
                 * =================================================
                 */

                $pairResult =
                    $transferOptimizer
                        ->optimize(
                            $currentPlayerA,
                            $currentPlayerB,
                            $candidatePoolA,
                            $candidatePoolB,
                            $bank,
                            $resultLimit
                        );


                foreach (
                    $pairResult[
                        'combinations'
                    ]
                    ?? []
                    as $combination
                ) {

                    $incomingA =
                        $combination[
                            'transfer_a'
                        ]['replacement']
                        ?? [];


                    $incomingB =
                        $combination[
                            'transfer_b'
                        ]['replacement']
                        ?? [];


                    $incomingIdA =
                        (int) (
                            $incomingA[
                                'player_id'
                            ]
                            ?? 0
                        );


                    $incomingIdB =
                        (int) (
                            $incomingB[
                                'player_id'
                            ]
                            ?? 0
                        );


                    if (
                        $incomingIdA <= 0
                        ||
                        $incomingIdB <= 0
                        ||
                        $incomingIdA === $incomingIdB
                    ) {

                        continue;
                    }


                    /*
                     * =================================================
                     * FINAL CLUB LIMIT CHECK
                     * =================================================
                     */

                    $finalTeamCounts =
                        $teamCountsAfterSales;


                    $incomingTeamIdA =
                        $this->findPlayerTeamId(
                            $incomingIdA,
                            $allPlayers
                        );


                    $incomingTeamIdB =
                        $this->findPlayerTeamId(
                            $incomingIdB,
                            $allPlayers
                        );


                    if (
                        $incomingTeamIdA <= 0
                        ||
                        $incomingTeamIdB <= 0
                    ) {

                        continue;
                    }


                    $finalTeamCounts[
                        $incomingTeamIdA
                    ] =
                        (
                            $finalTeamCounts[
                                $incomingTeamIdA
                            ]
                            ?? 0
                        )
                        +
                        1;


                    $finalTeamCounts[
                        $incomingTeamIdB
                    ] =
                        (
                            $finalTeamCounts[
                                $incomingTeamIdB
                            ]
                            ?? 0
                        )
                        +
                        1;


                    $clubLimitExceeded =
                        false;


                    foreach (
                        $finalTeamCounts
                        as $teamCount
                    ) {

                        if ($teamCount > 3) {

                            $clubLimitExceeded =
                                true;

                            break;
                        }
                    }


                    if ($clubLimitExceeded) {
                        continue;
                    }


                    /*
                     * =================================================
                     * ATTACH SQUAD CONTEXT
                     * =================================================
                     */

                    $budgetAfter =
                        $combination[
                            'optimizer'
                        ]['budget_after']
                        ?? null;


                    $classification =
                        $combination[
                            'classification'
                        ]
                        ?? 'Unknown';


                    $combinedIntelligence =
                        $combination[
                            'combined_movements'
                        ]['intelligence']
                        ?? null;


                    $currentNameA =
                        $combination[
                            'transfer_a'
                        ]['current_player']['name']
                        ?? 'Unknown';


                    $replacementNameA =
                        $combination[
                            'transfer_a'
                        ]['replacement']['name']
                        ?? 'Unknown';


                    $currentNameB =
                        $combination[
                            'transfer_b'
                        ]['current_player']['name']
                        ?? 'Unknown';


                    $replacementNameB =
                        $combination[
                            'transfer_b'
                        ]['replacement']['name']
                        ?? 'Unknown';


                    $squadSummary =
                        $currentNameA
                        . ' → '
                        . $replacementNameA
                        . ' plus '
                        . $currentNameB
                        . ' → '
                        . $replacementNameB
                        . ' is classified as '
                        . strtolower(
                            $classification
                        )
                        . '.';


                    if (
                        $combinedIntelligence !== null
                        &&
                        is_numeric(
                            $combinedIntelligence
                        )
                    ) {

                        $combinedIntelligence =
                            (float) $combinedIntelligence;


                        if ($combinedIntelligence > 0) {

                            $squadSummary .=
                                ' Combined Intelligence improves by '
                                . number_format(
                                    $combinedIntelligence,
                                    1
                                )
                                . ' points.';

                        } elseif ($combinedIntelligence < 0) {

                            $squadSummary .=
                                ' Combined Intelligence falls by '
                                . number_format(
                                    abs(
                                        $combinedIntelligence
                                    ),
                                    1
                                )
                                . ' points.';
                        }
                    }


                    if (
                        $budgetAfter !== null
                        &&
                        is_numeric(
                            $budgetAfter
                        )
                    ) {

                        $squadSummary .=
                            ' The resulting squad has £'
                            . number_format(
                                (float) $budgetAfter,
                                1
                            )
                            . 'm in the bank.';
                    }
                    
                    /*
                     * =================================================
                     * SQUAD-AWARE SCORE
                     * =================================================
                     *
                     * Diagnostic score only for now.
                     *
                     * Combination quality remains dominant, while the
                     * combined urgency of the two outgoing players adds
                     * a small squad-awareness bonus.
                     */

                    $combinationScore =
                        is_numeric(
                            $combination[
                                'combination_score'
                            ]
                            ?? null
                        )
                            ? (float) $combination[
                                'combination_score'
                            ]
                            : null;


                    $outgoingPriorityTotal =
                        (
                            is_numeric(
                                $priorityA[
                                    'transfer_priority'
                                ]
                                ?? null
                            )
                                ? (float) $priorityA[
                                    'transfer_priority'
                                ]
                                : 0.0
                        )
                        +
                        (
                            is_numeric(
                                $priorityB[
                                    'transfer_priority'
                                ]
                                ?? null
                            )
                                ? (float) $priorityB[
                                    'transfer_priority'
                                ]
                                : 0.0
                        );


                    $squadPriorityBonus =
                        (
                            $outgoingPriorityTotal
                            /
                            100
                        )
                        *
                        5;


                    $squadScore =
                        $combinationScore === null
                            ? null
                            : round(
                                $combinationScore
                                +
                                $squadPriorityBonus,
                                2
                            );


                    $combination[
                        'squad_optimizer'
                    ] = [

                        'outgoing_priority_a' =>
                            $priorityA[
                                'transfer_priority'
                            ]
                            ?? null,

                        'outgoing_priority_b' =>
                            $priorityB[
                                'transfer_priority'
                            ]
                            ?? null,

                        'outgoing_priority_total' =>
                            round(
                                $outgoingPriorityTotal,
                                1
                            ),

                        'squad_priority_bonus' =>
                            round(
                                $squadPriorityBonus,
                                2
                            ),

                        'squad_score' =>
                            $squadScore,

                        'outgoing_rank_a' =>
                            $priorityA[
                                'squad_rank'
                            ]
                            ?? (
                                $i + 1
                            ),

                        'outgoing_rank_b' =>
                            $priorityB[
                                'squad_rank'
                            ]
                            ?? (
                                $j + 1
                            ),

                        'summary' =>
                            $squadSummary
                    ];


                    $allCombinations[] =
                        $combination;
                }
            }
        }


        /*
         * ========================================================
         * RANK ALL SQUAD COMBINATIONS
         * ========================================================
         */

        usort(
            $allCombinations,
            function (
                array $a,
                array $b
            ): int {

                /*
                 * -----------------------------------------
                 * 1. COMBINATION CLASSIFICATION
                 * -----------------------------------------
                 */

                $classificationA =
                    $this->combinationClassificationWeight(
                        $a[
                            'classification'
                        ]
                        ?? null
                    );


                $classificationB =
                    $this->combinationClassificationWeight(
                        $b[
                            'classification'
                        ]
                        ?? null
                    );


                if (
                    $classificationA
                    !==
                    $classificationB
                ) {

                    return $classificationB
                        <=>
                        $classificationA;
                }


                /*
                 * -----------------------------------------
                 * 2. SQUAD SCORE
                 * -----------------------------------------
                 *
                 * Squad Score combines the underlying
                 * TransferCombination score with a small
                 * bonus for addressing higher-priority
                 * squad weaknesses.
                 */

                $squadScoreA =
                    is_numeric(
                        $a[
                            'squad_optimizer'
                        ]['squad_score']
                        ?? null
                    )
                        ? (float) $a[
                            'squad_optimizer'
                        ]['squad_score']
                        : -999999.0;


                $squadScoreB =
                    is_numeric(
                        $b[
                            'squad_optimizer'
                        ]['squad_score']
                        ?? null
                    )
                        ? (float) $b[
                            'squad_optimizer'
                        ]['squad_score']
                        : -999999.0;


                if (
                    $squadScoreA
                    !==
                    $squadScoreB
                ) {

                    return $squadScoreB
                        <=>
                        $squadScoreA;
                }


                /*
                 * -----------------------------------------
                 * 3. COMBINED INTELLIGENCE IMPROVEMENT
                 * -----------------------------------------
                 */

                $intelligenceA =
                    is_numeric(
                        $a[
                            'combined_movements'
                        ]['intelligence']
                        ?? null
                    )
                        ? (float) $a[
                            'combined_movements'
                        ]['intelligence']
                        : -999999.0;


                $intelligenceB =
                    is_numeric(
                        $b[
                            'combined_movements'
                        ]['intelligence']
                        ?? null
                    )
                        ? (float) $b[
                            'combined_movements'
                        ]['intelligence']
                        : -999999.0;


                if (
                    $intelligenceA
                    !==
                    $intelligenceB
                ) {

                    return $intelligenceB
                        <=>
                        $intelligenceA;
                }


                /*
                 * -----------------------------------------
                 * 4. REMAINING BUDGET
                 * -----------------------------------------
                 */

                return (
                    (float) (
                        $b[
                            'optimizer'
                        ]['budget_after']
                        ?? 0
                    )
                )
                <=>
                (
                    (float) (
                        $a[
                            'optimizer'
                        ]['budget_after']
                        ?? 0
                    )
                );
            }
        );


        /*
         * ========================================================
         * LIMIT FINAL RESULT
         * ========================================================
         */

        $totalFound =
            count(
                $allCombinations
            );


        $ranked =
            array_slice(
                $allCombinations,
                0,
                $resultLimit
            );


        foreach (
            $ranked
            as $index => &$combination
        ) {

            $combination[
                'squad_optimizer'
            ]['rank'] =
                $index + 1;
        }


        unset(
            $combination
        );


        return [

            'status' =>
                'success',

            'bank' =>
                round(
                    $bank,
                    1
                ),

            'outgoing_limit' =>
                $outgoingLimit,

            'result_limit' =>
                $resultLimit,

            'priority_players_considered' =>
                $priorityCount,

            'outgoing_pairs_considered' =>
                $outgoingPairsConsidered,

            'total_found' =>
                $totalFound,

            'count' =>
                count(
                    $ranked
                ),

            'combinations' =>
                $ranked
        ];
    }
    
    /*
     * ========================================================
     * DECISION TYPE WEIGHT
     * ========================================================
     *
     * Squad recommendations should favour dependable
     * transfer classifications over high-scoring risky
     * punts.
     */

    private function decisionTypeWeight(
        ?string $decisionType
    ): int {

        return match (
            strtolower(
                trim(
                    (string) $decisionType
                )
            )
        ) {

            'upgrade' =>
                6,

            'budget enabler' =>
                5,

            'strategic sidegrade' =>
                4,

            'sidegrade' =>
                3,

            'risky punt' =>
                2,

            'downgrade' =>
                1,

            'insufficient data' =>
                0,

            default =>
                0
        };
    }
    
    /*
     * ========================================================
     * PRUNE DOUBLE TRANSFER CANDIDATE POOL
     * ========================================================
     *
     * Double-transfer optimisation can become extremely large
     * when full position pools are combined.
     *
     * Rank candidates individually first and keep only the most
     * promising options before generating transfer pairs.
     */

    private function pruneDoubleCandidatePool(
        array $currentPlayer,
        array $candidatePool,
        int $limit = 15
    ): array {

        if (
            empty($candidatePool)
            ||
            $limit <= 0
        ) {

            return [];
        }


        $evaluated =
            [];


        foreach (
            $candidatePool
            as $candidate
        ) {

            $decision =
                $this->transferDecision
                    ->evaluateTransfer(
                        $currentPlayer,
                        $candidate
                    );


            $evaluated[] = [

                'player' =>
                    $candidate,

                'decision_type' =>
                    $decision[
                        'decision_type'
                    ]
                    ?? null,

                'decision_score' =>
                    $decision[
                        'decision_score'
                    ]
                    ?? null,

                'intelligence_score' =>
                    $candidate[
                        'intelligence_score'
                    ]
                    ?? null
            ];
        }


        usort(
            $evaluated,
            function (
                array $a,
                array $b
            ): int {

                /*
                 * Prefer reliable transfer classifications first.
                 */

                $typeWeightA =
                    $this->decisionTypeWeight(
                        $a[
                            'decision_type'
                        ]
                        ?? null
                    );


                $typeWeightB =
                    $this->decisionTypeWeight(
                        $b[
                            'decision_type'
                        ]
                        ?? null
                    );


                if (
                    $typeWeightA
                    !==
                    $typeWeightB
                ) {

                    return $typeWeightB
                        <=>
                        $typeWeightA;
                }


                /*
                 * Then individual TransferDecision score.
                 */

                $scoreA =
                    is_numeric(
                        $a[
                            'decision_score'
                        ]
                        ?? null
                    )
                        ? (float) $a[
                            'decision_score'
                        ]
                        : -999999.0;


                $scoreB =
                    is_numeric(
                        $b[
                            'decision_score'
                        ]
                        ?? null
                    )
                        ? (float) $b[
                            'decision_score'
                        ]
                        : -999999.0;


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
                 * Final tie-breaker:
                 * stronger incoming Intelligence.
                 */

                return (
                    (float) (
                        $b[
                            'intelligence_score'
                        ]
                        ?? -999999
                    )
                )
                <=>
                (
                    (float) (
                        $a[
                            'intelligence_score'
                        ]
                        ?? -999999
                    )
                );
            }
        );


        $evaluated =
            array_slice(
                $evaluated,
                0,
                $limit
            );


        return array_map(
            static function (
                array $item
            ): array {

                return $item[
                    'player'
                ];
            },
            $evaluated
        );
    }
    
    /*
     * ========================================================
     * FIND PLAYER TEAM
     * ========================================================
     */

    private function findPlayerTeamId(
        int $playerId,
        array $allPlayers
    ): int {

        foreach (
            $allPlayers
            as $player
        ) {

            if (
                (
                    (int) (
                        $player[
                            'player_id'
                        ]
                        ?? 0
                    )
                )
                ===
                $playerId
            ) {

                return (int) (
                    $player[
                        'team_id'
                    ]
                    ?? 0
                );
            }
        }


        return 0;
    }


    
    
    private function combinationClassificationWeight(
        ?string $classification
    ): int {

        return match (
            strtolower(
                trim(
                    (string) $classification
                )
            )
        ) {

            'strong improvement' =>
                6,

            'improvement' =>
                5,

            'balanced restructure' =>
                4,

            'neutral restructure' =>
                3,

            'risky restructure' =>
                2,

            'downgrade' =>
                1,

            'unaffordable' =>
                0,

            'insufficient data' =>
                -1,

            default =>
                0
        };
    }


    private function emptyDoubleResult(
        string $message
    ): array {

        return [

            'status' =>
                'invalid',

            'message' =>
                $message,

            'bank' =>
                null,

            'outgoing_limit' =>
                0,

            'result_limit' =>
                0,

            'priority_players_considered' =>
                0,

            'outgoing_pairs_considered' =>
                0,

            'total_found' =>
                0,

            'count' =>
                0,

            'combinations' =>
                []
        ];
    }
    
    /*
     * ========================================================
     * EMPTY RESULT
     * ========================================================
     */

    private function emptyResult(
        string $message
    ): array {

        return [

            'status' =>
                'invalid',

            'message' =>
                $message,

            'bank' =>
                null,

            'priority_limit' =>
                0,

            'replacement_limit' =>
                0,

            'players_considered' =>
                0,

            'recommendations' =>
                []
        ];
    }
}