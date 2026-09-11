<?php

/**
 * GameweekWeightCalibrationHistoryService
 *
 * Builds multi-gameweek Gameweek Starting XI weight calibration
 * history from authoritative historical recommendation evidence.
 *
 * This service does not:
 *
 * - query live Player Intelligence
 * - recalculate historical recommendation evidence from live state
 * - reconstruct missing recommendation evidence
 * - reconstruct missing player outcomes
 * - change production Gameweek weights
 * - select a preferred calibration candidate
 * - persist calibration results
 *
 * GameweekBacktestingEvidenceService remains the authority for
 * deciding whether a stored gameweek is historically Ready.
 *
 * For Ready gameweeks, this service combines the preserved
 * Starting XI and bench into the original 15-player squad and
 * maps authoritative realised player points onto that preserved
 * recommendation-time universe.
 *
 * GameweekWeightCalibrationService then owns alternative-weight
 * replay and realised Starting XI evaluation.
 */
class GameweekWeightCalibrationHistoryService
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

        if (
            $entryId <= 0
        ) {

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


        if (
            !is_array(
                $storedGameweeks
            )
        ) {

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
         * EVALUATE HISTORICAL ELIGIBILITY
         * --------------------------------------------------------
         */

        foreach (
            $storedGameweeks
            as $storedGameweek
        ) {

            /*
             * Malformed repository rows are not valid stored
             * gameweek evidence.
             */
            if (
                !is_array(
                    $storedGameweek
                )
            ) {

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


            /*
             * Historical calibration is keyed by the positive
             * local gameweek identity.
             */
            if (
                $gameweekId <= 0
            ) {

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
                ??
                null;


            /*
             * ----------------------------------------------------
             * AUTHORITATIVE HISTORICAL EVIDENCE
             * ----------------------------------------------------
             */

            $evidence =
                $this
                    ->evidenceService
                    ->getEvidence(
                        $entryId,
                        $gameweekId
                    );


            if (
                !is_array(
                    $evidence
                )
            ) {

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
                ??
                'Unavailable';


            $reason =
                $evidence[
                    'reason'
                ]
                ??
                null;


            /*
             * Every valid stored gameweek remains visible in the
             * audit even when it cannot contribute calibration
             * evidence.
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


            /*
             * Only authoritative Ready gameweeks may contribute.
             */
            if (
                $status !== 'Ready'
            ) {

                continue;
            }


            $readyGameweeks++;


            /*
             * ----------------------------------------------------
             * PRESERVED RECOMMENDATION SNAPSHOT
             * ----------------------------------------------------
             */

            $snapshot =
                $evidence[
                    'recommendation_snapshot'
                ]
                ??
                null;


            if (
                !is_array(
                    $snapshot
                )
            ) {

                continue;
            }


            $startingXI =
                $snapshot[
                    'starting_xi'
                ]
                ??
                [];


            $bench =
                $snapshot[
                    'bench'
                ]
                ??
                [];


            if (
                !is_array(
                    $startingXI
                )
            ) {

                $startingXI =
                    [];
            }


            if (
                !is_array(
                    $bench
                )
            ) {

                $bench =
                    [];
            }


            /*
             * Historical Gameweek calibration requires the
             * preserved recommendation-time squad.
             *
             * Starting XI remains first and bench remains second
             * so the original recommendation evidence ordering is
             * retained exactly.
             */
            $preservedSquad =
                array_merge(
                    $startingXI,
                    $bench
                );


            if (
                empty(
                    $preservedSquad
                )
            ) {

                continue;
            }


            /*
             * ----------------------------------------------------
             * AUTHORITATIVE PLAYER OUTCOMES
             * ----------------------------------------------------
             */

            $playerOutcomes =
                $evidence[
                    'player_outcomes'
                ]
                ??
                [];


            if (
                !is_array(
                    $playerOutcomes
                )
            ) {

                $playerOutcomes =
                    [];
            }


            $outcomeLookup =
                [];


            foreach (
                $playerOutcomes
                as $playerOutcome
            ) {

                if (
                    !is_array(
                        $playerOutcome
                    )
                ) {

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


                if (
                    $outcomePlayerId <= 0
                ) {

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
                     * Preserve genuine zero and negative FPL
                     * scores exactly.
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
             * BUILD PRESERVED GAMEWEEK CALIBRATION UNIVERSE
             * ----------------------------------------------------
             */

            $players =
                [];


            foreach (
                $preservedSquad
                as $squadPlayer
            ) {

                /*
                 * Malformed recommendation entries cannot support
                 * historical Gameweek replay.
                 */
                if (
                    !is_array(
                        $squadPlayer
                    )
                ) {

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
                ) {

                    continue;
                }


                $position =
                    isset(
                        $squadPlayer[
                            'position'
                        ]
                    )
                        ? strtoupper(
                            trim(
                                (string) $squadPlayer[
                                    'position'
                                ]
                            )
                        )
                        : '';


                $gameweekComponents =
                    $squadPlayer[
                        'gameweek_components'
                    ]
                    ??
                    [];


                if (
                    !is_array(
                        $gameweekComponents
                    )
                ) {

                    $gameweekComponents =
                        [];
                }


                /*
                 * Preserve only the recommendation-time components
                 * required by GameweekWeightCalibrationService.
                 *
                 * In particular, fixture is the already-compressed
                 * Gameweek fixture component. We deliberately do
                 * not recalibrate fixture compression here.
                 */
                $components = [

                    'intelligence' =>
                        $this->preserveNumericOrNull(
                            $gameweekComponents[
                                'intelligence'
                            ]
                            ??
                            null
                        ),

                    'strength' =>
                        $this->preserveNumericOrNull(
                            $gameweekComponents[
                                'strength'
                            ]
                            ??
                            null
                        ),

                    'fixture' =>
                        $this->preserveNumericOrNull(
                            $gameweekComponents[
                                'fixture'
                            ]
                            ??
                            null
                        ),

                    'confidence_modifier' =>
                        $this->preserveNumericOrNull(
                            $gameweekComponents[
                                'confidence_modifier'
                            ]
                            ??
                            null
                        ),

                    'availability_modifier' =>
                        $this->preserveNumericOrNull(
                            $gameweekComponents[
                                'availability_modifier'
                            ]
                            ??
                            null
                        )
                ];


                $actualPoints =
                    array_key_exists(
                        $playerId,
                        $outcomeLookup
                    )
                        ? $outcomeLookup[
                            $playerId
                        ]
                        : null;


                $players[] = [

                    'player_id' =>
                        $playerId,

                    'position' =>
                        $position,

                    'components' =>
                        $components,

                    'actual_points' =>
                        $actualPoints
                ];
            }


            /*
             * A Ready gameweek with no usable preserved player
             * identities cannot form a calibration universe.
             */
            if (
                empty(
                    $players
                )
            ) {

                continue;
            }


            $historicalGameweeks[] = [

                'gameweek_id' =>
                    $gameweekId,

                'players' =>
                    $players
            ];
        }


        /*
         * --------------------------------------------------------
         * RUN GAMEWEEK CALIBRATION ONCE
         * --------------------------------------------------------
         *
         * The pure calibrator owns alternative-weight replay and
         * legal Starting XI selection.
         *
         * History orchestration calls it exactly once across the
         * complete eligible historical collection.
         */

        $calibration =
            $this
                ->calibrationService
                ->evaluate(
                    $historicalGameweeks,
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

            'historical_gameweeks' =>
                $historicalGameweeks,

            'calibration' =>
                $calibration
        ];
    }


    /*
     * ============================================================
     * NUMERIC EVIDENCE HELPER
     * ============================================================
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