<?php


/**
 * TransferPriorityWeightCalibrationHistoryService
 *
 * Builds multi-gameweek historical evidence for calibration of
 * SquadTransferIntelligence outgoing-player priority weights.
 *
 * This service deliberately does not:
 *
 * - query live player state
 * - reconstruct missing recommendation evidence
 * - alter production transfer-priority weights
 * - calibrate TransferDecision incoming weights
 * - use only the preserved top transfer recommendation
 * - persist calibration results
 * - choose a preferred calibration candidate
 *
 * For every historically Ready gameweek it:
 *
 * 1. reconstructs the exact preserved 15-player squad
 * 2. reads recommendation-time player ranking evidence
 * 3. reads the recommendation-time bank
 * 4. reconstructs the legal replacement universe separately
 *    for every possible outgoing squad player
 * 5. maps authoritative realised FPL points
 * 6. records each squad player's best realised legal transfer
 *    opportunity
 *
 * TransferPriorityWeightCalibrationService then owns alternative
 * outgoing-priority weight replay.
 */
class TransferPriorityWeightCalibrationHistoryService
{
    private const MAX_PLAYERS_PER_TEAM =
        3;


    private object $gameweekRepository;


    private object $evidenceService;


    private object $calibrationService;


    /*
     * ============================================================
     * CONSTRUCTOR
     * ============================================================
     */

    public function __construct(
        object $gameweekRepository,
        object $evidenceService,
        object $calibrationService
    ) {

        $this->gameweekRepository =
            $gameweekRepository;


        $this->evidenceService =
            $evidenceService;


        $this->calibrationService =
            $calibrationService;
    }


    /*
     * ============================================================
     * PUBLIC API
     * ============================================================
     */

    public function evaluate(
        int $entryId,
        array $weightCandidates
    ): array {

        /*
         * --------------------------------------------------------
         * VALIDATE ENTRY
         * --------------------------------------------------------
         */

        if ($entryId <= 0) {

            throw new InvalidArgumentException(
                'FPL entry ID must be positive.'
            );
        }


        /*
         * --------------------------------------------------------
         * DISCOVER STORED GAMEWEEKS
         * --------------------------------------------------------
         */

        $storedGameweeks =
            $this
                ->gameweekRepository
                ->getAll();


        if (!is_array($storedGameweeks)) {

            $storedGameweeks =
                [];
        }


        $gameweekAudit =
            [];


        $historicalGameweeks =
            [];


        $totalGameweeks =
            0;


        $readyGameweeks =
            0;


        /*
         * --------------------------------------------------------
         * PROCESS STORED GAMEWEEKS
         * --------------------------------------------------------
         */

        foreach (
            $storedGameweeks
            as $storedGameweek
        ) {

            /*
             * Malformed repository rows do not represent stored
             * historical gameweeks.
             */
            if (!is_array($storedGameweek)) {

                continue;
            }


            $gameweekId =
                isset(
                    $storedGameweek[
                        'id'
                    ]
                )
                &&
                is_numeric(
                    $storedGameweek[
                        'id'
                    ]
                )
                    ? (int) $storedGameweek[
                        'id'
                    ]
                    : 0;


            if ($gameweekId <= 0) {

                continue;
            }


            $totalGameweeks++;


            $fplGameweekId =
                isset(
                    $storedGameweek[
                        'fpl_gameweek_id'
                    ]
                )
                &&
                is_numeric(
                    $storedGameweek[
                        'fpl_gameweek_id'
                    ]
                )
                    ? (int) $storedGameweek[
                        'fpl_gameweek_id'
                    ]
                    : null;


            $gameweekName =
                $storedGameweek[
                    'name'
                ]
                ?? null;


            /*
             * ----------------------------------------------------
             * AUTHORITATIVE HISTORICAL ELIGIBILITY
             * ----------------------------------------------------
             */

            $evidence =
                $this
                    ->evidenceService
                    ->getEvidence(
                        $entryId,
                        $gameweekId
                    );


            if (!is_array($evidence)) {

                $evidence = [

                    'status' =>
                        'Unavailable',

                    'reason' =>
                        'Historical backtesting evidence is unavailable.'
                ];
            }


            $status =
                $evidence[
                    'status'
                ]
                ?? 'Unavailable';


            $reason =
                $evidence[
                    'reason'
                ]
                ?? null;


            /*
             * Keep every valid stored gameweek auditable even when
             * it cannot enter calibration.
             */
            $gameweekAudit[] = [

                'gameweek_id' =>
                    $gameweekId,

                'fpl_gameweek_id' =>
                    $fplGameweekId,

                'name' =>
                    $gameweekName,

                'status' =>
                    $status,

                'reason' =>
                    $reason
            ];


            if ($status !== 'Ready') {

                continue;
            }


            $readyGameweeks++;


            /*
             * ----------------------------------------------------
             * IMMUTABLE RECOMMENDATION SNAPSHOT
             * ----------------------------------------------------
             */

            $snapshot =
                $evidence[
                    'recommendation_snapshot'
                ]
                ?? null;


            if (!is_array($snapshot)) {

                continue;
            }


            $startingXI =
                $snapshot[
                    'starting_xi'
                ]
                ?? [];


            $bench =
                $snapshot[
                    'bench'
                ]
                ?? [];


            $playerRankings =
                $snapshot[
                    'player_rankings'
                ]
                ?? [];


            $transferRecommendations =
                $snapshot[
                    'transfer_recommendations'
                ]
                ?? null;


            if (!is_array($startingXI)) {

                $startingXI =
                    [];
            }


            if (!is_array($bench)) {

                $bench =
                    [];
            }


            if (!is_array($playerRankings)) {

                $playerRankings =
                    [];
            }


            /*
             * Bank is recommendation-time transfer evidence.
             *
             * Without that preserved structure affordability
             * cannot be reconstructed honestly.
             */
            if (!is_array($transferRecommendations)) {

                continue;
            }


            /*
             * ----------------------------------------------------
             * RECOMMENDATION-TIME BANK
             * ----------------------------------------------------
             */

            $bank =
                $this->preserveNumericOrNull(
                    $transferRecommendations[
                        'bank'
                    ]
                    ?? null
                );


            if ($bank === null) {

                $bank =
                    0.0;
            }


            /*
             * ----------------------------------------------------
             * PRESERVED 15-PLAYER SQUAD
             * ----------------------------------------------------
             */

            $preservedSquad =
                array_merge(
                    $startingXI,
                    $bench
                );


            if (empty($preservedSquad)) {

                continue;
            }


            /*
             * Preserve squad order from snapshot.
             */
            $squadPlayerIds =
                [];


            $squadPlayerIdLookup =
                [];


            foreach (
                $preservedSquad
                as $squadPlayer
            ) {

                if (!is_array($squadPlayer)) {

                    continue;
                }


                $playerId =
                    isset(
                        $squadPlayer[
                            'player_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $squadPlayer[
                            'player_id'
                        ]
                    )
                        ? (int) $squadPlayer[
                            'player_id'
                        ]
                        : 0;


                if (
                    $playerId <= 0
                    ||
                    isset(
                        $squadPlayerIdLookup[
                            $playerId
                        ]
                    )
                ) {

                    continue;
                }


                $squadPlayerIds[] =
                    $playerId;


                $squadPlayerIdLookup[
                    $playerId
                ] =
                    true;
            }


            if (empty($squadPlayerIds)) {

                continue;
            }


            /*
             * ----------------------------------------------------
             * PRESERVED RANKING LOOKUP
             * ----------------------------------------------------
             *
             * First recommendation-time row wins.
             *
             * Duplicate rows must not mutate historical evidence.
             */

            $rankingLookup =
                [];


            foreach (
                $playerRankings
                as $rankingRow
            ) {

                if (!is_array($rankingRow)) {

                    continue;
                }


                $rankingPlayerId =
                    isset(
                        $rankingRow[
                            'player_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $rankingRow[
                            'player_id'
                        ]
                    )
                        ? (int) $rankingRow[
                            'player_id'
                        ]
                        : 0;


                if ($rankingPlayerId <= 0) {

                    continue;
                }


                if (
                    !array_key_exists(
                        $rankingPlayerId,
                        $rankingLookup
                    )
                ) {

                    $rankingLookup[
                        $rankingPlayerId
                    ] =
                        $rankingRow;
                }
            }


            /*
             * ----------------------------------------------------
             * AUTHORITATIVE REALISED OUTCOMES
             * ----------------------------------------------------
             */

            $playerOutcomes =
                $evidence[
                    'player_outcomes'
                ]
                ?? [];


            if (!is_array($playerOutcomes)) {

                $playerOutcomes =
                    [];
            }


            $outcomeLookup =
                [];


            foreach (
                $playerOutcomes
                as $playerOutcome
            ) {

                if (!is_array($playerOutcome)) {

                    continue;
                }


                $outcomePlayerId =
                    isset(
                        $playerOutcome[
                            'player_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $playerOutcome[
                            'player_id'
                        ]
                    )
                        ? (int) $playerOutcome[
                            'player_id'
                        ]
                        : 0;


                if ($outcomePlayerId <= 0) {

                    continue;
                }


                $actualPoints =
                    null;


                if (
                    array_key_exists(
                        'total_points',
                        $playerOutcome
                    )
                    &&
                    $playerOutcome[
                        'total_points'
                    ] !== null
                    &&
                    is_numeric(
                        $playerOutcome[
                            'total_points'
                        ]
                    )
                ) {

                    /*
                     * Genuine zero and negative points remain
                     * historical evidence.
                     */
                    $actualPoints =
                        $playerOutcome[
                            'total_points'
                        ]
                        + 0;
                }


                $outcomeLookup[
                    $outcomePlayerId
                ] =
                    $actualPoints;
            }


            /*
             * ----------------------------------------------------
             * HISTORICAL SQUAD CLUB COUNTS
             * ----------------------------------------------------
             *
             * Build the recommendation-time starting club counts
             * once.
             *
             * Each possible outgoing player will independently
             * remove itself before an incoming player is counted.
             */

            $teamCounts =
                [];


            foreach (
                $squadPlayerIds
                as $squadPlayerId
            ) {

                $rankingRow =
                    $rankingLookup[
                        $squadPlayerId
                    ]
                    ?? null;


                if (!is_array($rankingRow)) {

                    continue;
                }


                $teamId =
                    isset(
                        $rankingRow[
                            'team_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $rankingRow[
                            'team_id'
                        ]
                    )
                        ? (int) $rankingRow[
                            'team_id'
                        ]
                        : 0;


                if ($teamId <= 0) {

                    continue;
                }


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


            /*
             * ----------------------------------------------------
             * BUILD ALL POSSIBLE OUTGOING PLAYERS
             * ----------------------------------------------------
             */

            $calibrationPlayers =
                [];


            foreach (
                $squadPlayerIds
                as $outgoingPlayerId
            ) {

                $outgoingRanking =
                    $rankingLookup[
                        $outgoingPlayerId
                    ]
                    ?? null;


                if (!is_array($outgoingRanking)) {

                    continue;
                }


                $outgoing =
                    $this->buildHistoricalOutgoingPlayer(
                        $outgoingRanking,
                        $outcomeLookup[
                            $outgoingPlayerId
                        ]
                        ?? null
                    );


                if ($outgoing === null) {

                    continue;
                }


                $outgoingPosition =
                    $outgoing[
                        'position'
                    ];


                $outgoingTeamId =
                    $outgoing[
                        'team_id'
                    ];


                $outgoingPrice =
                    $outgoing[
                        'price'
                    ];


                /*
                 * Without recommendation-time position, club or
                 * price we cannot honestly reconstruct legality.
                 */
                if (
                    $outgoingPosition === ''
                    ||
                    $outgoingTeamId <= 0
                    ||
                    $outgoingPrice === null
                ) {

                    continue;
                }


                $availableBudget =
                    $outgoingPrice
                    +
                    $bank;


                /*
                 * ------------------------------------------------
                 * COMPLETE LEGAL REPLACEMENT UNIVERSE
                 * ------------------------------------------------
                 */

                $legalReplacementPlayerIds =
                    [];


                $bestReplacementPlayerId =
                    null;


                $bestReplacementActualPoints =
                    null;


                /*
                 * rankingLookup deliberately contains one immutable
                 * recommendation-time row per player identity.
                 */
                foreach (
                    $rankingLookup
                    as $candidatePlayerId => $candidateRanking
                ) {

                    /*
                     * Cannot replace a player with itself.
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
                            $squadPlayerIdLookup[
                                $candidatePlayerId
                            ]
                        )
                    ) {

                        continue;
                    }


                    if (!is_array($candidateRanking)) {

                        continue;
                    }


                    /*
                     * Position must match the outgoing player.
                     */
                    $candidatePosition =
                        strtoupper(
                            trim(
                                (string) (
                                    $candidateRanking[
                                        'position'
                                    ]
                                    ?? ''
                                )
                            )
                        );


                    if (
                        $candidatePosition
                        !==
                        $outgoingPosition
                    ) {

                        continue;
                    }


                    /*
                     * Price must be recommendation-time numeric
                     * evidence and affordable.
                     */
                    $candidatePrice =
                        $this->preserveNumericOrNull(
                            $candidateRanking[
                                'price'
                            ]
                            ?? null
                        );


                    if ($candidatePrice === null) {

                        continue;
                    }


                    if (
                        $candidatePrice
                        >
                        $availableBudget
                    ) {

                        continue;
                    }


                    /*
                     * Incoming club must be valid.
                     */
                    $candidateTeamId =
                        isset(
                            $candidateRanking[
                                'team_id'
                            ]
                        )
                        &&
                        is_numeric(
                            $candidateRanking[
                                'team_id'
                            ]
                        )
                            ? (int) $candidateRanking[
                                'team_id'
                            ]
                            : 0;


                    if ($candidateTeamId <= 0) {

                        continue;
                    }


                    /*
                     * Reproduce SquadTransferOptimizer club-limit
                     * legality.
                     *
                     * Start from the recommendation-time squad.
                     *
                     * Remove this specific outgoing player first
                     * if the incoming player belongs to the same
                     * club.
                     */
                    $candidateClubCount =
                        $teamCounts[
                            $candidateTeamId
                        ]
                        ?? 0;


                    if (
                        $candidateTeamId
                        ===
                        $outgoingTeamId
                    ) {

                        $candidateClubCount--;
                    }


                    $candidateClubCount++;


                    if (
                        $candidateClubCount
                        >
                        self::MAX_PLAYERS_PER_TEAM
                    ) {

                        continue;
                    }


                    /*
                     * Candidate is recommendation-time legal.
                     */
                    $legalReplacementPlayerIds[] =
                        $candidatePlayerId;


                    /*
                     * Legal identity is useful even if the eventual
                     * realised outcome is unavailable.
                     *
                     * Best realised replacement selection requires
                     * authoritative numeric outcome evidence.
                     */
                    $candidateActualPoints =
                        $outcomeLookup[
                            $candidatePlayerId
                        ]
                        ?? null;


                    if (!is_numeric($candidateActualPoints)) {

                        continue;
                    }


                    if (
                        $bestReplacementActualPoints === null
                        ||
                        $candidateActualPoints
                        >
                        $bestReplacementActualPoints
                        ||
                        (
                            $candidateActualPoints
                            ===
                            $bestReplacementActualPoints
                            &&
                            (
                                $bestReplacementPlayerId === null
                                ||
                                $candidatePlayerId
                                <
                                $bestReplacementPlayerId
                            )
                        )
                    ) {

                        $bestReplacementPlayerId =
                            $candidatePlayerId;


                        $bestReplacementActualPoints =
                            $candidateActualPoints;
                    }
                }


                /*
                 * ------------------------------------------------
                 * REALISED TRANSFER OPPORTUNITY
                 * ------------------------------------------------
                 */

                $actualPoints =
                    $outgoing[
                        'actual_points'
                    ];


                $bestRealisedTransferGain =
                    null;


                if (
                    is_numeric($actualPoints)
                    &&
                    is_numeric(
                        $bestReplacementActualPoints
                    )
                ) {

                    $bestRealisedTransferGain =
                        $this->preserveDerivedNumber(
                            $bestReplacementActualPoints
                            -
                            $actualPoints
                        );
                }


                $outgoing[
                    'available_budget'
                ] =
                    $availableBudget;


                $outgoing[
                    'legal_replacement_player_ids'
                ] =
                    $legalReplacementPlayerIds;


                $outgoing[
                    'best_replacement_player_id'
                ] =
                    $bestReplacementPlayerId;


                $outgoing[
                    'best_replacement_actual_points'
                ] =
                    $bestReplacementActualPoints;


                $outgoing[
                    'best_realised_transfer_gain'
                ] =
                    $bestRealisedTransferGain;


                $calibrationPlayers[] =
                    $outgoing;
            }


            /*
             * A Ready gameweek only becomes useful outgoing
             * calibration history if some preserved squad evidence
             * survived reconstruction.
             */
            if (empty($calibrationPlayers)) {

                continue;
            }


            $historicalGameweeks[] = [

                'gameweek_id' =>
                    $gameweekId,

                'bank' =>
                    $bank,

                'players' =>
                    $calibrationPlayers
            ];
        }


        /*
         * --------------------------------------------------------
         * DELEGATE PURE CALIBRATION
         * --------------------------------------------------------
         *
         * Delegate exactly once, including the empty-history case.
         */

        $calibration =
            $this
                ->calibrationService
                ->evaluate(
                    $historicalGameweeks,
                    $weightCandidates
                );


        return [

            'entry_id' =>
                $entryId,

            'total_gameweeks' =>
                $totalGameweeks,

            'ready_gameweeks' =>
                $readyGameweeks,

            'gameweeks' =>
                $gameweekAudit,

            'historical_gameweeks' =>
                $historicalGameweeks,

            'calibration' =>
                $calibration
        ];
    }


    /*
     * ============================================================
     * HISTORICAL OUTGOING PLAYER
     * ============================================================
     */

    private function buildHistoricalOutgoingPlayer(
        array $rankingRow,
        int|float|null $actualPoints
    ): ?array {

        $playerId =
            isset(
                $rankingRow[
                    'player_id'
                ]
            )
            &&
            is_numeric(
                $rankingRow[
                    'player_id'
                ]
            )
                ? (int) $rankingRow[
                    'player_id'
                ]
                : 0;


        if ($playerId <= 0) {

            return null;
        }


        $fplPlayerId =
            isset(
                $rankingRow[
                    'fpl_player_id'
                ]
            )
            &&
            is_numeric(
                $rankingRow[
                    'fpl_player_id'
                ]
            )
                ? (int) $rankingRow[
                    'fpl_player_id'
                ]
                : null;


        $position =
            strtoupper(
                trim(
                    (string) (
                        $rankingRow[
                            'position'
                        ]
                        ?? ''
                    )
                )
            );


        $teamId =
            isset(
                $rankingRow[
                    'team_id'
                ]
            )
            &&
            is_numeric(
                $rankingRow[
                    'team_id'
                ]
            )
                ? (int) $rankingRow[
                    'team_id'
                ]
                : 0;


        $price =
            $this->preserveNumericOrNull(
                $rankingRow[
                    'price'
                ]
                ?? null
            );


        return [

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
                $fplPlayerId,

            'name' =>
                $rankingRow[
                    'name'
                ]
                ?? null,

            'position' =>
                $position,

            'team_id' =>
                $teamId,

            'price' =>
                $price,

            'intelligence_score' =>
                $this->preserveNumericOrNull(
                    $rankingRow[
                        'intelligence_score'
                    ]
                    ?? null
                ),

            'value_rating' =>
                $this->preserveNumericOrNull(
                    $rankingRow[
                        'value_rating'
                    ]
                    ?? null
                ),

            'fixture_rating' =>
                $this->preserveNumericOrNull(
                    $rankingRow[
                        'fixture_rating'
                    ]
                    ?? null
                ),

            'availability_rating' =>
                $this->preserveNumericOrNull(
                    $rankingRow[
                        'availability_rating'
                    ]
                    ?? null
                ),

            'actual_points' =>
                $actualPoints
        ];
    }


    /*
     * ============================================================
     * NUMERIC EVIDENCE
     * ============================================================
     */

    private function preserveNumericOrNull(
        mixed $value
    ): int|float|null {

        if (
            $value === null
            ||
            !is_numeric($value)
        ) {

            return null;
        }


        /*
         * Preserve integer/float character from historical evidence
         * where PHP naturally permits it.
         */
        return
            $value + 0;
    }


    /*
     * ============================================================
     * DERIVED NUMBER
     * ============================================================
     */

    private function preserveDerivedNumber(
        int|float $value
    ): int|float {

        if (
            abs(
                (float) $value
                -
                round(
                    (float) $value
                )
            )
            <=
            0.000001
        ) {

            return
                (int) round(
                    (float) $value
                );
        }


        return
            (float) $value;
    }
}