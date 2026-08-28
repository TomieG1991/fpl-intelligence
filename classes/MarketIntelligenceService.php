<?php


class MarketIntelligenceService
{
    private PDO $db;

    private PlayerRepository $playerRepository;

    private PlayerGameweekSnapshotRepository $snapshotRepository;

    private PlayerFixtureHistoryRepository $fixtureHistoryRepository;


    public function __construct(
        PDO $db
    ) {

        $this->db =
            $db;


        $this->playerRepository =
            new PlayerRepository(
                $db
            );


        $this->snapshotRepository =
            new PlayerGameweekSnapshotRepository(
                $db
            );


        $this->fixtureHistoryRepository =
            new PlayerFixtureHistoryRepository(
                $db
            );
    }
    
    
    public function getPlayerMarketSummary(
        int $playerId
    ): array {

        $result =
            $this->getPlayerMarketIntelligence(
                $playerId
            );


        if (
            (
                $result[
                    'status'
                ]
                ?? null
            )
            !==
            'Available'
        ) {

            return [

                'status' =>
                    'Unavailable',

                'player_id' =>
                    $playerId,

                'classification' =>
                    'Unavailable',

                'evidence_count' =>
                    0,

                'evidence' => [

                    'price' => [

                        'status' =>
                            'Unavailable',

                        'direction' =>
                            'Unavailable'
                    ],

                    'ownership' => [

                        'status' =>
                            'Unavailable',

                        'direction' =>
                            'Unavailable'
                    ],

                    'transfers' => [

                        'status' =>
                            'Unavailable',

                        'direction' =>
                            'Unavailable'
                    ]
                ]
            ];
        }


        $priceMovement =
            $result[
                'price_movement'
            ]
            ?? [];


        $ownershipMovement =
            $result[
                'ownership_movement'
            ]
            ?? [];


        $transferMomentum =
            $result[
                'transfer_momentum'
            ]
            ?? [];


        $combinedSignal =
            $result[
                'combined_market_signal'
            ]
            ?? [];
            
        
        $valueTrend =
            $result[
                'value_trend'
            ]
            ?? [];


        return [

            'status' =>
                'Available',

            'player_id' =>
                $playerId,

            'classification' =>
                $combinedSignal[
                    'classification'
                ]
                ?? 'Insufficient Evidence',

            'evidence_count' =>
                (int) (
                    $combinedSignal[
                        'available_signals'
                    ]
                    ?? 0
                ),

            'evidence' => [

                'price' => [

                    'status' =>
                        $priceMovement[
                            'status'
                        ]
                        ?? 'Unavailable',

                    'direction' =>
                        $priceMovement[
                            'direction'
                        ]
                        ?? 'Unavailable'
                ],

                'ownership' => [

                    'status' =>
                        $ownershipMovement[
                            'status'
                        ]
                        ?? 'Unavailable',

                    'direction' =>
                        $ownershipMovement[
                            'direction'
                        ]
                        ?? 'Unavailable'
                ],

                'transfers' => [

                    'status' =>
                        $transferMomentum[
                            'status'
                        ]
                        ?? 'Unavailable',

                    'direction' =>
                        $transferMomentum[
                            'direction'
                        ]
                        ?? 'Unavailable'
                ]
            ],
            'value_trend' => [

                'status' =>
                    $valueTrend[
                        'status'
                    ]
                    ?? 'Unavailable',

                'classification' =>
                    $valueTrend[
                        'classification'
                    ]
                    ?? 'Insufficient Evidence'
            ]
        ];
    }


    /*
     * ============================================================
     * PLAYER MARKET INTELLIGENCE
     * ============================================================
     */

    public function getPlayerMarketIntelligence(
        int $playerId
    ): array {

        if (
            $playerId <= 0
        ) {

            return
                $this->unavailableResult(
                    $playerId,
                    'Invalid player ID'
                );
        }


        $player =
            $this->playerRepository
                ->getById(
                    $playerId
                );


        if (
            !is_array(
                $player
            )
        ) {

            return
                $this->unavailableResult(
                    $playerId,
                    'Player not found'
                );
        }


        /*
         * ========================================================
         * CURRENT MARKET STATE
         * ========================================================
         */

        $current =
            [

                'price' =>
                    $this->numericOrNull(
                        $player[
                            'price'
                        ]
                        ?? null
                    ),

                'selected_by_percent' =>
                    $this->numericOrNull(
                        $player[
                            'selected_by_percent'
                        ]
                        ?? null
                    )
            ];


        /*
         * ========================================================
         * HISTORICAL SNAPSHOT STATE
         * ========================================================
         */

        $history =
            $this->getPlayerSnapshotHistory(
                $playerId
            );


        /*
         * ========================================================
         * MOVEMENT MODELS
         * ========================================================
         */

        $priceMovement =
            $this->buildPriceMovement(
                $history
            );


        $ownershipMovement =
            $this->buildOwnershipMovement(
                $history
            );


        $transferMomentum =
            $this->buildTransferMomentum(
                $playerId
            );
            
        $combinedMarketSignal =
            $this->buildCombinedMarketSignal(
                $priceMovement,
                $ownershipMovement,
                $transferMomentum
            );
            
            
        /*
         * ========================================================
         * VALUE TREND
         * ========================================================
         *
         * Value Trend combines the player's existing Player
         * Intelligence value rating with the independent Market
         * Intelligence classification.
         *
         * The existing PlayerValue model remains the source of truth
         * for current FPL value. Market Intelligence must not
         * recalculate or redefine that value model.
         */

        $playerIntelligenceService =
            new PlayerIntelligenceService(
                $this->db
            );


        $playerProfile =
            $playerIntelligenceService
                ->getPlayerProfile(
                    $playerId
                );


        $valueRating =
            $this->numericOrNull(
                $playerProfile[
                    'summary'
                ][
                    'value_rating'
                ]
                ?? null
            );


        $valueTrend =
            $this->buildValueTrend(
                [
                    'value_rating' =>
                        $valueRating
                ],
                [
                    'classification' =>
                        $combinedMarketSignal[
                            'classification'
                        ]
                        ?? 'Insufficient Evidence'
                ]
            );


        return [

            'status' =>
                'Available',

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
                $this->integerOrNull(
                    $player[
                        'fpl_player_id'
                    ]
                    ?? null
                ),

            'name' =>
                $player[
                    'web_name'
                ]
                ?? null,

            'team_id' =>
                $this->integerOrNull(
                    $player[
                        'team_id'
                    ]
                    ?? null
                ),

            'position' =>
                $player[
                    'position'
                ]
                ?? null,

            'current' =>
                $current,

            'history' =>
                $history,

            'price_movement' =>
                $priceMovement,

            'ownership_movement' =>
                $ownershipMovement,

            'transfer_momentum' =>
                $transferMomentum,

            'combined_market_signal' =>
                $combinedMarketSignal,

            'value_trend' =>
                $valueTrend
        ];
    }


    /*
     * ============================================================
     * SNAPSHOT HISTORY
     * ============================================================
     */

    private function getPlayerSnapshotHistory(
        int $playerId
    ): array {

        $statement =
            $this->db
                ->prepare(
                    "
                        SELECT
                            pgs.id,
                            pgs.gameweek_id,
                            g.fpl_gameweek_id,
                            g.name AS gameweek_name,
                            pgs.price,
                            pgs.selected,
                            pgs.selected_by_percent,
                            pgs.created_at

                        FROM
                            player_gameweek_snapshots pgs

                        INNER JOIN
                            gameweeks g
                                ON g.id = pgs.gameweek_id

                        WHERE
                            pgs.player_id = :player_id

                        ORDER BY
                            g.fpl_gameweek_id ASC,
                            pgs.id ASC
                    "
                );


        $statement
            ->execute(
                [
                    ':player_id' =>
                        $playerId
                ]
            );


        $rows =
            $statement
                ->fetchAll(
                    PDO::FETCH_ASSOC
                );


        $history =
            [];


        foreach (
            $rows
            as $row
        ) {

            $history[] = [

                'snapshot_id' =>
                    $this->integerOrNull(
                        $row[
                            'id'
                        ]
                        ?? null
                    ),

                'gameweek_id' =>
                    $this->integerOrNull(
                        $row[
                            'gameweek_id'
                        ]
                        ?? null
                    ),

                'fpl_gameweek_id' =>
                    $this->integerOrNull(
                        $row[
                            'fpl_gameweek_id'
                        ]
                        ?? null
                    ),

                'gameweek_name' =>
                    $row[
                        'gameweek_name'
                    ]
                    ?? null,

                'price' =>
                    $this->numericOrNull(
                        $row[
                            'price'
                        ]
                        ?? null
                    ),

                'selected' =>
                    $this->integerOrNull(
                        $row[
                            'selected'
                        ]
                        ?? null
                    ),

                /*
                 * This field may legitimately be NULL or may
                 * contain a known-corrupted historical percentage
                 * in legacy GW1 data.
                 *
                 * The service exposes the stored evidence but does
                 * not manufacture replacements.
                 */
                'selected_by_percent' =>
                    $this->numericOrNull(
                        $row[
                            'selected_by_percent'
                        ]
                        ?? null
                    ),

                'created_at' =>
                    $row[
                        'created_at'
                    ]
                    ?? null
            ];
        }


        return
            $history;
    }


    /*
     * ============================================================
     * PRICE MOVEMENT
     * ============================================================
     */

    private function buildPriceMovement(
        array $history
    ): array {

        $states =
            [];


        foreach (
            $history
            as $row
        ) {

            if (
                !is_numeric(
                    $row[
                        'price'
                    ]
                    ?? null
                )
            ) {

                continue;
            }


            $gameweekId =
                (int) (
                    $row[
                        'fpl_gameweek_id'
                    ]
                    ?? 0
                );


            if (
                $gameweekId <= 0
            ) {

                continue;
            }


            $states[
                $gameweekId
            ] =
                (float) $row[
                    'price'
                ];
        }


        if (
            count(
                $states
            )
            <
            2
        ) {

            return [

                'status' =>
                    'Insufficient Historical Data',

                'gameweek_count' =>
                    count(
                        $states
                    ),

                'start_price' =>
                    null,

                'latest_price' =>
                    null,

                'change' =>
                    null,

                'direction' =>
                    'Unavailable'
            ];
        }


        ksort(
            $states
        );


        $prices =
            array_values(
                $states
            );


        $startPrice =
            (float) reset(
                $prices
            );


        $latestPrice =
            (float) end(
                $prices
            );


        $change =
            round(
                $latestPrice
                -
                $startPrice,
                1
            );


        return [

            'status' =>
                'Available',

            'gameweek_count' =>
                count(
                    $states
                ),

            'start_price' =>
                $startPrice,

            'latest_price' =>
                $latestPrice,

            'change' =>
                $change,

            'direction' =>
                $this->movementDirection(
                    $change
                )
        ];
    }


    /*
     * ============================================================
     * OWNERSHIP MOVEMENT
     * ============================================================
     *
     * Raw selected-manager count is preferred because it is exact.
     *
     * We deliberately DO NOT derive historical ownership
     * percentages from selected counts because the historical
     * total-manager denominator is not currently stored.
     */

    private function buildOwnershipMovement(
        array $history
    ): array {

        $states =
            [];


        foreach (
            $history
            as $row
        ) {

            if (
                !is_numeric(
                    $row[
                        'selected'
                    ]
                    ?? null
                )
            ) {

                continue;
            }


            $gameweekId =
                (int) (
                    $row[
                        'fpl_gameweek_id'
                    ]
                    ?? 0
                );


            if (
                $gameweekId <= 0
            ) {

                continue;
            }


            $states[
                $gameweekId
            ] =
                (int) $row[
                    'selected'
                ];
        }


        if (
            count(
                $states
            )
            <
            2
        ) {

            return [

                'status' =>
                    'Insufficient Historical Data',

                'gameweek_count' =>
                    count(
                        $states
                    ),

                'start_selected' =>
                    null,

                'latest_selected' =>
                    null,

                'change' =>
                    null,

                'direction' =>
                    'Unavailable'
            ];
        }


        ksort(
            $states
        );


        $selectedStates =
            array_values(
                $states
            );


        $startSelected =
            (int) reset(
                $selectedStates
            );


        $latestSelected =
            (int) end(
                $selectedStates
            );


        $change =
            $latestSelected
            -
            $startSelected;


        return [

            'status' =>
                'Available',

            'gameweek_count' =>
                count(
                    $states
                ),

            'start_selected' =>
                $startSelected,

            'latest_selected' =>
                $latestSelected,

            'change' =>
                $change,

            'direction' =>
                $this->movementDirection(
                    $change
                )
        ];
    }


    /*
     * ============================================================
     * TRANSFER MOMENTUM
     * ============================================================
     */

    private function buildTransferMomentum(
        int $playerId
    ): array {

        $statement =
            $this->db
                ->prepare(
                    "
                        SELECT
                            pfh.gameweek_id,
                            g.fpl_gameweek_id,
                            pfh.transfers_in,
                            pfh.transfers_out,
                            pfh.transfers_balance

                        FROM
                            player_fixture_history pfh

                        INNER JOIN
                            gameweeks g
                                ON g.id = pfh.gameweek_id

                        WHERE
                            pfh.player_id = :player_id

                        ORDER BY
                            g.fpl_gameweek_id ASC,
                            pfh.id ASC
                    "
                );


        $statement
            ->execute(
                [
                    ':player_id' =>
                        $playerId
                ]
            );


        $rows =
            $statement
                ->fetchAll(
                    PDO::FETCH_ASSOC
                );


        /*
         * One market state per gameweek.
         *
         * If a Double Gameweek eventually produces more than one
         * fixture-history row for the player, retain the first
         * market-state row for that gameweek rather than double
         * counting transfers.
         */

        $states =
            [];


        foreach (
            $rows
            as $row
        ) {

            $gameweekId =
                (int) (
                    $row[
                        'fpl_gameweek_id'
                    ]
                    ?? 0
                );


            if (
                $gameweekId <= 0
                ||
                isset(
                    $states[
                        $gameweekId
                    ]
                )
            ) {

                continue;
            }


            $transfersIn =
                $this->integerOrNull(
                    $row[
                        'transfers_in'
                    ]
                    ?? null
                );


            $transfersOut =
                $this->integerOrNull(
                    $row[
                        'transfers_out'
                    ]
                    ?? null
                );


            $balance =
                $this->integerOrNull(
                    $row[
                        'transfers_balance'
                    ]
                    ?? null
                );


            if (
                $balance === null
                &&
                $transfersIn !== null
                &&
                $transfersOut !== null
            ) {

                $balance =
                    $transfersIn
                    -
                    $transfersOut;
            }


            $states[
                $gameweekId
            ] = [

                'transfers_in' =>
                    $transfersIn,

                'transfers_out' =>
                    $transfersOut,

                'balance' =>
                    $balance
            ];
        }


        if (
            count(
                $states
            )
            <
            2
        ) {

            return [

                'status' =>
                    'Insufficient Historical Data',

                'gameweek_count' =>
                    count(
                        $states
                    ),

                'latest_transfers_in' =>
                    null,

                'latest_transfers_out' =>
                    null,

                'latest_balance' =>
                    null,

                'direction' =>
                    'Unavailable'
            ];
        }


        ksort(
            $states
        );


        $latest =
            end(
                $states
            );


        $latestBalance =
            $this->integerOrNull(
                $latest[
                    'balance'
                ]
                ?? null
            );


        return [

            'status' =>
                'Available',

            'gameweek_count' =>
                count(
                    $states
                ),

            'latest_transfers_in' =>
                $this->integerOrNull(
                    $latest[
                        'transfers_in'
                    ]
                    ?? null
                ),

            'latest_transfers_out' =>
                $this->integerOrNull(
                    $latest[
                        'transfers_out'
                    ]
                    ?? null
                ),

            'latest_balance' =>
                $latestBalance,

            'direction' =>
                $latestBalance !== null
                    ? $this->movementDirection(
                        $latestBalance
                    )
                    : 'Unavailable'
        ];
    }


    /*
     * ============================================================
     * MOVEMENT DIRECTION
     * ============================================================
     */

    private function movementDirection(
        int|float $change
    ): string {

        if (
            $change > 0
        ) {

            return
                'Rising';
        }


        if (
            $change < 0
        ) {

            return
                'Falling';
        }


        return
            'Stable';
    }


    /*
     * ============================================================
     * UNAVAILABLE RESULT
     * ============================================================
     */

    private function unavailableResult(
        int $playerId,
        string $message
    ): array {

        return [

            'status' =>
                'Unavailable',

            'message' =>
                $message,

            'player_id' =>
                $playerId,

            'current' =>
                [],

            'history' =>
                [],

            'price_movement' => [

                'status' =>
                    'Unavailable'
            ],

            'ownership_movement' => [

                'status' =>
                    'Unavailable'
            ],

            'transfer_momentum' => [

                'status' =>
                    'Unavailable'
            ]
        ];
    }
    
    private function buildCombinedMarketSignal(
        array $priceMovement,
        array $ownershipMovement,
        array $transferMomentum
    ): array {

        $signals =
            [
                $priceMovement,
                $ownershipMovement,
                $transferMomentum
            ];


        $availableSignals =
            0;


        $risingSignals =
            0;


        $fallingSignals =
            0;


        $stableSignals =
            0;


        foreach (
            $signals
            as $signal
        ) {

            if (
                (
                    $signal[
                        'status'
                    ]
                    ?? null
                )
                !==
                'Available'
            ) {

                continue;
            }


            $direction =
                $signal[
                    'direction'
                ]
                ?? null;


            if (
                !in_array(
                    $direction,
                    [
                        'Rising',
                        'Falling',
                        'Stable'
                    ],
                    true
                )
            ) {

                continue;
            }


            $availableSignals++;


            if (
                $direction
                ===
                'Rising'
            ) {

                $risingSignals++;

                continue;
            }


            if (
                $direction
                ===
                'Falling'
            ) {

                $fallingSignals++;

                continue;
            }


            $stableSignals++;
        }


        /*
         * ========================================================
         * MINIMUM EVIDENCE
         * ========================================================
         *
         * One component signal alone is not enough to classify
         * overall market direction.
         */

        if (
            $availableSignals
            <
            2
        ) {

            return [

                'classification' =>
                    'Insufficient Evidence',

                'available_signals' =>
                    $availableSignals,

                'rising_signals' =>
                    $risingSignals,

                'falling_signals' =>
                    $fallingSignals,

                'stable_signals' =>
                    $stableSignals
            ];
        }


        /*
         * ========================================================
         * CONFLICTING DIRECTIONAL EVIDENCE
         * ========================================================
         */

        if (
            $risingSignals > 0
            &&
            $fallingSignals > 0
        ) {

            return [

                'classification' =>
                    'Mixed',

                'available_signals' =>
                    $availableSignals,

                'rising_signals' =>
                    $risingSignals,

                'falling_signals' =>
                    $fallingSignals,

                'stable_signals' =>
                    $stableSignals
            ];
        }


        /*
         * ========================================================
         * STRONG CONSENSUS
         * ========================================================
         */

        if (
            $availableSignals === 3
            &&
            $risingSignals === 3
        ) {

            $classification =
                'Strong Rising';

        } elseif (
            $availableSignals === 3
            &&
            $fallingSignals === 3
        ) {

            $classification =
                'Strong Falling';

        } elseif (
            $risingSignals > 0
            &&
            $fallingSignals === 0
        ) {

            $classification =
                'Rising';

        } elseif (
            $fallingSignals > 0
            &&
            $risingSignals === 0
        ) {

            $classification =
                'Falling';

        } else {

            $classification =
                'Stable';
        }


        return [

            'classification' =>
                $classification,

            'available_signals' =>
                $availableSignals,

            'rising_signals' =>
                $risingSignals,

            'falling_signals' =>
                $fallingSignals,

            'stable_signals' =>
                $stableSignals
        ];
    }


    /*
     * ============================================================
     * VALUE HELPERS
     * ============================================================
     */

    private function numericOrNull(
        mixed $value
    ): ?float {

        if (
            !is_numeric(
                $value
            )
        ) {

            return
                null;
        }


        return
            (float) $value;
    }


    private function integerOrNull(
        mixed $value
    ): ?int {

        if (
            !is_numeric(
                $value
            )
        ) {

            return
                null;
        }


        return
            (int) $value;
    }
    
    
    public function buildValueTrend(
    array $valueEvidence,
    array $marketEvidence
): array {

    $valueRating =
        $valueEvidence[
            'value_rating'
        ]
        ?? null;


    $marketClassification =
        $marketEvidence[
            'classification'
        ]
        ?? null;


    /*
     * ========================================================
     * VALIDATE VALUE EVIDENCE
     * ========================================================
     */

    if (
        $valueRating === null
        ||
        !is_numeric(
            $valueRating
        )
    ) {

        return [

            'status' =>
                'Insufficient Historical Data',

            'classification' =>
                'Insufficient Evidence',

            'value_rating' =>
                null,

            'market_classification' =>
                $marketClassification
        ];
    }


    $valueRating =
        (float) $valueRating;


    if (
        $valueRating < 0
        ||
        $valueRating > 100
    ) {

        return [

            'status' =>
                'Insufficient Historical Data',

            'classification' =>
                'Insufficient Evidence',

            'value_rating' =>
                $valueRating,

            'market_classification' =>
                $marketClassification
        ];
    }


    /*
     * ========================================================
     * VALIDATE MARKET EVIDENCE
     * ========================================================
     */

    $validMarketClassifications =
        [
            'Strong Rising',
            'Rising',
            'Stable',
            'Falling',
            'Strong Falling',
            'Mixed'
        ];


    if (
        !in_array(
            $marketClassification,
            $validMarketClassifications,
            true
        )
    ) {

        return [

            'status' =>
                'Insufficient Historical Data',

            'classification' =>
                'Insufficient Evidence',

            'value_rating' =>
                $valueRating,

            'market_classification' =>
                $marketClassification
        ];
    }


    /*
     * ========================================================
     * VALUE BAND
     * ========================================================
     *
     * Existing PlayerValue thresholds:
     *
     * 75+  = Excellent / Exceptional
     * 40+  = Average / Good
     * <40  = Poor / Very Poor
     */

    if (
        $valueRating >= 75
    ) {

        $valueBand =
            'Strong';

    } elseif (
        $valueRating >= 40
    ) {

        $valueBand =
            'Neutral';

    } else {

        $valueBand =
            'Weak';
    }


    /*
     * ========================================================
     * VALUE TREND CLASSIFICATION
     * ========================================================
     */

    $classification =
        'Mixed Value Signal';


    if (
        $valueBand === 'Strong'
    ) {

        if (
            in_array(
                $marketClassification,
                [
                    'Strong Rising',
                    'Rising'
                ],
                true
            )
        ) {

            $classification =
                'Improving Value';

        } elseif (
            $marketClassification
            ===
            'Stable'
        ) {

            $classification =
                'Stable Value';

        } elseif (
            in_array(
                $marketClassification,
                [
                    'Falling',
                    'Strong Falling',
                    'Mixed'
                ],
                true
            )
        ) {

            $classification =
                'Mixed Value Signal';
        }

    } elseif (
        $valueBand === 'Weak'
    ) {

        if (
            in_array(
                $marketClassification,
                [
                    'Falling',
                    'Strong Falling'
                ],
                true
            )
        ) {

            $classification =
                'Deteriorating Value';

        } elseif (
            in_array(
                $marketClassification,
                [
                    'Rising',
                    'Strong Rising',
                    'Mixed'
                ],
                true
            )
        ) {

            $classification =
                'Mixed Value Signal';

        } elseif (
            $marketClassification
            ===
            'Stable'
        ) {

            $classification =
                'Stable Value';
        }

    } else {

        /*
         * Neutral current value.
         *
         * Market direction alone must not promote a player to
         * Improving Value or Deteriorating Value.
         */

        $classification =
            $marketClassification
            ===
            'Stable'
                ? 'Stable Value'
                : 'Mixed Value Signal';
    }


    return [

        'status' =>
            'Available',

        'classification' =>
            $classification,

        'value_rating' =>
            $valueRating,

        'market_classification' =>
            $marketClassification
    ];
}
    
}