<?php

/**
 * ProjectionCalibrationDiagnosticsHistoryService
 *
 * v0.36.0 — Model Calibration & Intelligence Quality
 *
 * Pools historical player-level projection backtesting evidence
 * across all authoritative Ready gameweeks for one FPL entry and
 * delegates aggregate diagnostic analysis to:
 *
 * ProjectionCalibrationDiagnosticsService
 *
 * Historical gameweek eligibility remains the responsibility of:
 *
 * GameweekBacktestingEvidenceService
 *
 * Player-level projection comparisons remain the responsibility of:
 *
 * GameweekProjectionBacktestingService
 *
 * This service does not:
 *
 * - infer historical eligibility itself
 * - reconstruct missing recommendation evidence
 * - query live Player Intelligence
 * - query live fixture data
 * - recalculate Expected Points
 * - recalculate Expected Minutes
 * - recalculate Projection Confidence
 * - recommend projection parameter changes
 * - choose a preferred projection model
 * - create a synthetic model score
 * - persist diagnostic results
 */
class ProjectionCalibrationDiagnosticsHistoryService
{
    private object $gameweekRepository;

    private object $evidenceService;

    private object $projectionBacktestingService;

    private object $diagnosticsService;


    /**
     * Constructor.
     */
    public function __construct(
        object $gameweekRepository,
        object $evidenceService,
        object $projectionBacktestingService,
        object $diagnosticsService
    ) {

        $this->gameweekRepository =
            $gameweekRepository;


        $this->evidenceService =
            $evidenceService;


        $this->projectionBacktestingService =
            $projectionBacktestingService;


        $this->diagnosticsService =
            $diagnosticsService;
    }


    /**
     * Evaluate pooled historical projection diagnostics for one
     * FPL entry.
     */
    public function evaluate(
        int $entryId
    ): array {

        /*
         * ========================================================
         * VALIDATE ENTRY
         * ========================================================
         */

        if ($entryId <= 0) {

            throw new InvalidArgumentException(
                'Entry ID must be a positive integer.'
            );
        }


        /*
         * ========================================================
         * LOAD STORED GAMEWEEKS
         * ========================================================
         */

        $storedGameweeks =
            $this
                ->gameweekRepository
                ->getAll();


        $gameweekResults =
            [];


        $playerEvaluations =
            [];


        $totalGameweeks =
            0;


        $readyGameweeks =
            0;


        /*
         * ========================================================
         * CHECK EACH STORED GAMEWEEK THROUGH AUTHORITATIVE
         * BACKTESTING EVIDENCE
         * ========================================================
         */

        foreach (
            $storedGameweeks
            as $gameweek
        ) {

            /*
             * Malformed repository rows are not valid stored
             * gameweeks for historical diagnostics.
             */
            if (
                !is_array(
                    $gameweek
                )
            ) {

                continue;
            }


            $gameweekId =
                (int) (
                    $gameweek[
                        'id'
                    ]
                    ?? 0
                );


            if ($gameweekId <= 0) {

                continue;
            }


            $totalGameweeks++;


            /*
             * GameweekBacktestingEvidenceService is the authority
             * for deciding whether this gameweek may contribute
             * historical projection evidence.
             */
            $evidence =
                $this
                    ->evidenceService
                    ->getEvidence(
                        $entryId,
                        $gameweekId
                    );


            $status =
                is_array(
                    $evidence
                )
                    ? (
                        $evidence[
                            'status'
                        ]
                        ?? null
                    )
                    : null;


            $reason =
                is_array(
                    $evidence
                )
                    ? (
                        $evidence[
                            'reason'
                        ]
                        ?? null
                    )
                    : null;


            /*
             * ====================================================
             * AUDIT RECORD
             * ====================================================
             */

            $gameweekRecord = [

                'gameweek_id' =>
                    $gameweekId,

                'fpl_gameweek_id' =>
                    $gameweek[
                        'fpl_gameweek_id'
                    ]
                    ?? null,

                'name' =>
                    $gameweek[
                        'name'
                    ]
                    ?? null,

                'status' =>
                    $status,

                'reason' =>
                    $reason
            ];


            /*
             * Only authoritative Ready evidence may reach the
             * projection backtesting pipeline.
             */
            if ($status !== 'Ready') {

                $gameweekResults[] =
                    $gameweekRecord;

                continue;
            }


            $readyGameweeks++;


            /*
             * ====================================================
             * GAMEWEEK PROJECTION BACKTESTING
             * ====================================================
             *
             * The complete immutable backtesting evidence is
             * delegated directly. Do not rebuild projections or
             * outcomes here.
             */

            $projectionResult =
                $this
                    ->projectionBacktestingService
                    ->evaluate(
                        $evidence
                    );


            $gameweekPlayerEvaluations =
                [];


            if (
                is_array(
                    $projectionResult
                )
                &&
                is_array(
                    $projectionResult[
                        'player_evaluations'
                    ]
                    ?? null
                )
            ) {

                $gameweekPlayerEvaluations =
                    $projectionResult[
                        'player_evaluations'
                    ];
            }


            /*
             * ====================================================
             * POOL PLAYER-GAMEWEEK EVIDENCE
             * ====================================================
             */

            foreach (
                $gameweekPlayerEvaluations
                as $playerEvaluation
            ) {

                if (
                    !is_array(
                        $playerEvaluation
                    )
                ) {

                    continue;
                }


                /*
                 * Work on a copy so recommendation-time and
                 * backtesting evidence are never mutated.
                 */
                $combinedEvaluation =
                    $playerEvaluation;


                $combinedEvaluation[
                    'gameweek_id'
                ] =
                    $gameweekId;


                $playerEvaluations[] =
                    $combinedEvaluation;
            }


            $gameweekResults[] =
                $gameweekRecord;
        }


        /*
         * ========================================================
         * POOLED PROJECTION DIAGNOSTICS
         * ========================================================
         *
         * Analyse the complete historical player-gameweek sample
         * once.
         *
         * Do not calculate separate per-gameweek winners or average
         * per-gameweek diagnostics.
         */

        $diagnostics =
            $this
                ->diagnosticsService
                ->evaluate(
                    $playerEvaluations
                );


        /*
         * ========================================================
         * AUDITABLE HISTORY RESULT
         * ========================================================
         */

        return [

            'entry_id' =>
                $entryId,

            'total_gameweeks' =>
                $totalGameweeks,

            'ready_gameweeks' =>
                $readyGameweeks,

            'gameweeks' =>
                $gameweekResults,

            'player_evaluations' =>
                $playerEvaluations,

            'diagnostics' =>
                $diagnostics
        ];
    }
}