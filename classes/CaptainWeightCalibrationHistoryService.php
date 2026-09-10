<?php

/**
 * CaptainWeightCalibrationHistoryService
 *
 * Builds multi-gameweek Captain calibration history from
 * authoritative historical backtesting evidence.
 *
 * This service does not:
 *
 * - query live Player Intelligence
 * - recalculate Captain Intelligence
 * - reconstruct missing recommendation evidence
 * - reconstruct missing player outcomes
 * - change production Captain weights
 * - select a preferred calibration candidate
 * - persist calibration results
 *
 * GameweekBacktestingEvidenceService remains the authority for
 * deciding whether a stored gameweek is historically Ready.
 *
 * For Ready gameweeks, this service extracts the preserved
 * Captain Intelligence ranking universe and maps authoritative
 * realised player points onto those preserved candidates.
 *
 * CaptainWeightCalibrationService then owns alternative-weight
 * replay and realised captain-selection evaluation.
 */
class CaptainWeightCalibrationHistoryService
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
                ?? null;


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

                $evidence =
                    [
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
                ?? null;


            /*
             * Every valid stored gameweek remains visible in the
             * audit regardless of Captain calibration usability.
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
             * Only authoritative Ready gameweeks may contribute
             * recommendation-time Captain calibration evidence.
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
                ?? null;


            if (
                !is_array(
                    $snapshot
                )
            ) {

                continue;
            }


            $captainRecommendation =
                $snapshot[
                    'captain_recommendation'
                ]
                ?? null;


            if (
                !is_array(
                    $captainRecommendation
                )
            ) {

                continue;
            }


            $captainRankings =
                $captainRecommendation[
                    'rankings'
                ]
                ?? null;


            if (
                !is_array(
                    $captainRankings
                )
                ||
                empty(
                    $captainRankings
                )
            ) {

                /*
                 * The gameweek remains Ready in the authoritative
                 * audit, but it cannot support Captain weight
                 * calibration without the preserved ranking
                 * universe.
                 */
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
                ?? [];


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
                    ]
                    !== null
                    &&
                    is_numeric(
                        $playerOutcome[
                            'total_points'
                        ]
                    )
                ) {

                    /*
                     * Preserve genuine zero and negative FPL
                     * scores exactly as factual evidence.
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
             * BUILD PRESERVED CAPTAIN CALIBRATION UNIVERSE
             * ----------------------------------------------------
             */

            $players =
                [];


            foreach (
                $captainRankings
                as $captainRanking
            ) {

                /*
                 * Malformed ranking entries are not usable
                 * recommendation evidence.
                 */
                if (
                    !is_array(
                        $captainRanking
                    )
                ) {

                    continue;
                }


                $playerId =
                    isset(
                        $captainRanking[
                            'player_id'
                        ]
                    )
                    &&
                    is_numeric(
                        $captainRanking[
                            'player_id'
                        ]
                    )
                        ? (int) $captainRanking[
                            'player_id'
                        ]
                        : 0;


                if (
                    $playerId <= 0
                ) {

                    continue;
                }


                $components =
                    $captainRanking[
                        'components'
                    ]
                    ?? [];


                if (
                    !is_array(
                        $components
                    )
                ) {

                    $components =
                        [];
                }


                /*
                 * Preserve exactly the recommendation-time
                 * components required for Captain core replay.
                 *
                 * Missing values remain null. They are not
                 * reconstructed from live state.
                 */
                $preservedComponents = [

                    'strength' =>
                        $this->preserveNumericOrNull(
                            $components[
                                'strength'
                            ]
                            ?? null
                        ),

                    'fixture' =>
                        $this->preserveNumericOrNull(
                            $components[
                                'fixture'
                            ]
                            ?? null
                        ),

                    'attacking_threat' =>
                        $this->preserveNumericOrNull(
                            $components[
                                'attacking_threat'
                            ]
                            ?? null
                        ),

                    'confidence_modifier' =>
                        $this->preserveNumericOrNull(
                            $components[
                                'confidence_modifier'
                            ]
                            ?? null
                        ),

                    'availability_modifier' =>
                        $this->preserveNumericOrNull(
                            $components[
                                'availability_modifier'
                            ]
                            ?? null
                        )
                ];


                /*
                 * Only outcomes for players in the preserved
                 * Captain Intelligence universe are mapped.
                 *
                 * Missing authoritative outcome remains null.
                 */
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

                    'components' =>
                        $preservedComponents,

                    'actual_points' =>
                        $actualPoints
                ];
            }


            /*
             * A Ready gameweek with no usable positive player
             * identities cannot form a Captain calibration
             * universe.
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
         * RUN CAPTAIN CALIBRATION ONCE
         * --------------------------------------------------------
         *
         * The pure calibrator owns alternative-weight replay.
         *
         * History orchestration deliberately calls it once across
         * the complete eligible historical collection rather than
         * performing separate per-gameweek calibrations.
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