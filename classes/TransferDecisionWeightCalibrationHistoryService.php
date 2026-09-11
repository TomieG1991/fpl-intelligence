<?php


/**
 * TransferDecisionWeightCalibrationHistoryService
 *
 * Builds multi-gameweek TransferDecision weight calibration
 * history from authoritative immutable recommendation evidence.
 *
 * This service deliberately does not:
 *
 * - query live Player Intelligence
 * - reconstruct missing historical recommendation evidence
 * - recalculate outgoing transfer priority
 * - use current player prices, clubs or ratings
 * - use only the production top-N replacement recommendations
 * - alter production TransferDecision weights
 * - select a preferred calibration candidate
 * - persist calibration results
 *
 * GameweekBacktestingEvidenceService remains authoritative for
 * deciding whether a stored gameweek is historically Ready.
 *
 * For each Ready gameweek this service:
 *
 * 1. reconstructs the preserved 15-player squad
 * 2. fixes the highest-priority preserved outgoing player
 * 3. reads the recommendation-time bank
 * 4. reconstructs the complete legal replacement universe from
 *    preserved player ranking evidence
 * 5. maps authoritative realised FPL points
 *
 * TransferDecisionWeightCalibrationService then owns alternative
 * weight replay and realised replacement evaluation.
 */
class TransferDecisionWeightCalibrationHistoryService
{
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


        $historicalTransfers =
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
             * Malformed repository rows are not stored gameweek
             * evidence.
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
             * Every valid stored gameweek remains auditable,
             * regardless of calibration eligibility.
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


            if (!is_array($transferRecommendations)) {

                continue;
            }


            /*
             * ----------------------------------------------------
             * PRESERVED 15-PLAYER SQUAD IDENTITIES
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


            $squadPlayerIds =
                [];


            foreach (
                $preservedSquad
                as $squadPlayer
            ) {

                if (!is_array($squadPlayer)) {

                    continue;
                }


                $squadPlayerId =
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


                if ($squadPlayerId <= 0) {

                    continue;
                }


                $squadPlayerIds[
                    $squadPlayerId
                ] =
                    true;
            }


            if (empty($squadPlayerIds)) {

                continue;
            }


            /*
             * ----------------------------------------------------
             * PRESERVED PLAYER RANKING LOOKUP
             * ----------------------------------------------------
             *
             * Player rankings contain the recommendation-time
             * transfer attributes required for both the squad and
             * the complete incoming-player universe.
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


                /*
                 * Preserve the first recommendation-time row for a
                 * player identity.
                 *
                 * Duplicate ranking rows cannot create duplicate
                 * incoming candidates or mutate squad evidence.
                 */
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
             * FIXED OUTGOING PLAYER
             * ----------------------------------------------------
             *
             * This first TransferDecision calibration layer holds
             * outgoing selection constant.
             *
             * We therefore use only the highest-priority preserved
             * recommendation.
             */

            $recommendations =
                $transferRecommendations[
                    'recommendations'
                ]
                ?? [];


            if (
                !is_array($recommendations)
                ||
                empty($recommendations)
            ) {

                continue;
            }


            $firstRecommendation =
                $recommendations[
                    0
                ]
                ?? null;


            if (!is_array($firstRecommendation)) {

                continue;
            }


            $preservedOutgoing =
                $firstRecommendation[
                    'outgoing'
                ]
                ?? null;


            if (!is_array($preservedOutgoing)) {

                continue;
            }


            $outgoingPlayerId =
                isset(
                    $preservedOutgoing[
                        'player_id'
                    ]
                )
                &&
                is_numeric(
                    $preservedOutgoing[
                        'player_id'
                    ]
                )
                    ? (int) $preservedOutgoing[
                        'player_id'
                    ]
                    : 0;


            if (
                $outgoingPlayerId <= 0
                ||
                !isset(
                    $squadPlayerIds[
                        $outgoingPlayerId
                    ]
                )
                ||
                !isset(
                    $rankingLookup[
                        $outgoingPlayerId
                    ]
                )
            ) {

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
                     * Genuine zero and negative FPL points remain
                     * valid historical evidence.
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
             * NORMALISE OUTGOING CALIBRATION EVIDENCE
             * ----------------------------------------------------
             */

            $outgoing =
                $this->buildCalibrationPlayer(
                    $rankingLookup[
                        $outgoingPlayerId
                    ],
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


            $outgoingPrice =
                $outgoing[
                    'price'
                ];


            $outgoingTeamId =
                $outgoing[
                    'team_id'
                ];


            if (
                $outgoingPosition === ''
                ||
                $outgoingPrice === null
                ||
                $outgoingTeamId <= 0
            ) {

                continue;
            }


            /*
             * ----------------------------------------------------
             * HISTORICAL SQUAD CLUB COUNTS
             * ----------------------------------------------------
             */

            $teamCounts =
                [];


            foreach (
                array_keys(
                    $squadPlayerIds
                )
                as $squadPlayerId
            ) {

                $squadRanking =
                    $rankingLookup[
                        $squadPlayerId
                    ]
                    ?? null;


                if (!is_array($squadRanking)) {

                    continue;
                }


                $teamId =
                    isset(
                        $squadRanking[
                            'team_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $squadRanking[
                            'team_id'
                        ]
                    )
                        ? (int) $squadRanking[
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
             * COMPLETE LEGAL REPLACEMENT UNIVERSE
             * ----------------------------------------------------
             *
             * Reproduce SquadTransferOptimizer legality only.
             *
             * Do not use the preserved top-N replacement result;
             * that would prevent alternative weights from being
             * evaluated against the full recommendation-time
             * candidate universe.
             */

            $availableBudget =
                $outgoingPrice
                +
                $bank;


            $replacements =
                [];


            $seenReplacementIds =
                [];


            foreach (
                $playerRankings
                as $rankingRow
            ) {

                if (!is_array($rankingRow)) {

                    continue;
                }


                $candidatePlayerId =
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


                if ($candidatePlayerId <= 0) {

                    continue;
                }


                /*
                 * Duplicate ranking evidence cannot create a second
                 * incoming candidate.
                 */
                if (
                    isset(
                        $seenReplacementIds[
                            $candidatePlayerId
                        ]
                    )
                ) {

                    continue;
                }


                $seenReplacementIds[
                    $candidatePlayerId
                ] =
                    true;


                /*
                 * Cannot replace a player with himself.
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
                                $rankingRow[
                                    'position'
                                ]
                                ?? ''
                            )
                        )
                    );


                /*
                 * FPL transfers preserve position.
                 */
                if (
                    $candidatePosition
                    !==
                    $outgoingPosition
                ) {

                    continue;
                }


                $candidatePrice =
                    $this->preserveNumericOrNull(
                        $rankingRow[
                            'price'
                        ]
                        ?? null
                    );


                if ($candidatePrice === null) {

                    continue;
                }


                /*
                 * Recommendation-time affordability.
                 */
                if (
                    $candidatePrice
                    >
                    $availableBudget
                ) {

                    continue;
                }


                $candidateTeamId =
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


                if ($candidateTeamId <= 0) {

                    continue;
                }


                /*
                 * ------------------------------------------------
                 * MAX THREE PLAYERS PER CLUB AFTER TRANSFER
                 * ------------------------------------------------
                 */

                $candidateTeamCount =
                    $teamCounts[
                        $candidateTeamId
                    ]
                    ?? 0;


                /*
                 * The outgoing player leaves before the incoming
                 * player is counted.
                 */
                if (
                    $candidateTeamId
                    ===
                    $outgoingTeamId
                ) {

                    $candidateTeamCount--;
                }


                $candidateTeamCount++;


                if ($candidateTeamCount > 3) {

                    continue;
                }


                $replacement =
                    $this->buildCalibrationPlayer(
                        $rankingRow,
                        $outcomeLookup[
                            $candidatePlayerId
                        ]
                        ?? null
                    );


                if ($replacement === null) {

                    continue;
                }


                $replacements[] =
                    $replacement;
            }


            /*
             * ----------------------------------------------------
             * HISTORICAL TRANSFER CALIBRATION UNIVERSE
             * ----------------------------------------------------
             */

            $historicalTransfers[] = [

                'gameweek_id' =>
                    $gameweekId,

                'bank' =>
                    $bank,

                'outgoing' =>
                    $outgoing,

                'replacements' =>
                    $replacements
            ];
        }


        /*
         * --------------------------------------------------------
         * RUN PURE TRANSFER DECISION CALIBRATION ONCE
         * --------------------------------------------------------
         */

        $calibration =
            $this
                ->calibrationService
                ->evaluate(
                    $historicalTransfers,
                    $weightCandidates
                );


        /*
         * --------------------------------------------------------
         * RESULT CONTRACT
         * --------------------------------------------------------
         */

        return [

            'entry_id' =>
                $entryId,

            'total_gameweeks' =>
                $totalGameweeks,

            'ready_gameweeks' =>
                $readyGameweeks,

            'gameweeks' =>
                $gameweekAudit,

            'historical_transfers' =>
                $historicalTransfers,

            'calibration' =>
                $calibration
        ];
    }


    /*
     * ============================================================
     * CALIBRATION PLAYER
     * ============================================================
     */

    private function buildCalibrationPlayer(
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


        return [

            'player_id' =>
                $playerId,

            'fpl_player_id' =>
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
                    : null,

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
                $this->preserveNumericOrNull(
                    $rankingRow[
                        'price'
                    ]
                    ?? null
                ),

            'intelligence_score' =>
                $this->preserveNumericOrNull(
                    $rankingRow[
                        'intelligence_score'
                    ]
                    ?? null
                ),

            'strength_rating' =>
                $this->preserveNumericOrNull(
                    $rankingRow[
                        'strength_rating'
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

            'sample_confidence' =>
                $this->preserveNumericOrNull(
                    $rankingRow[
                        'sample_confidence'
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
     *
     * Preserve numeric historical evidence without forcing genuine
     * integers such as realised FPL points into floats.
     */

    private function preserveNumericOrNull(
        mixed $value
    ): int|float|null {

        if (
            $value === null
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return
            $value + 0;
    }
}