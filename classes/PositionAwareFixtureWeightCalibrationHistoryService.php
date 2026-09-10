<?php

/**
 * PositionAwareFixtureWeightCalibrationHistoryService
 *
 * v0.36.0 — Model Calibration & Intelligence Quality
 *
 * Evaluates explicitly supplied position-aware Fixture
 * Intelligence weight candidates across all historically
 * eligible gameweeks for one FPL entry.
 *
 * Historical eligibility is delegated to:
 *
 * GameweekBacktestingEvidenceService
 *
 * This service does not:
 *
 * - infer historical eligibility itself
 * - reconstruct missing recommendation evidence
 * - query live Player Intelligence
 * - query live fixture data
 * - manufacture realised outcomes
 * - choose or rank a winning weight combination
 * - modify production Fixture Intelligence weights
 * - persist calibration results
 */
class PositionAwareFixtureWeightCalibrationHistoryService
{
    private object $gameweekRepository;

    private object $evidenceService;

    private object $evaluationService;

    private object $calibrationService;
    
    private ?object $historicalEvidenceService;


    public function __construct(
        object $gameweekRepository,
        object $evidenceService,
        object $evaluationService,
        object $calibrationService,
        ?object $historicalEvidenceService = null
    ) {

        $this->gameweekRepository =
            $gameweekRepository;


        $this->evidenceService =
            $evidenceService;


        $this->evaluationService =
            $evaluationService;


        $this->calibrationService =
            $calibrationService;


        $this->historicalEvidenceService =
            $historicalEvidenceService;
    }


    public function evaluate(
        int $entryId,
        array $weightCandidates
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
         *
         * GameweekRepository owns ordering.
         *
         * getAll() returns gameweeks in official FPL order.
         */

        $storedGameweeks =
            $this->gameweekRepository
                ->getAll();


        $gameweekResults =
            [];


        $historicalRows =
            [];


        $readyGameweeks =
            0;


        $totalGameweeks =
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
             * Ignore malformed repository rows.
             */

            if (!is_array($gameweek)) {

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
             * GameweekBacktestingEvidenceService is the only
             * authority used here to determine whether historical
             * evidence is usable.
             */

            $evidence =
                $this->evidenceService
                    ->getEvidence(
                        $entryId,
                        $gameweekId
                    );


            $status =
                is_array($evidence)
                    ? (
                        $evidence[
                            'status'
                        ]
                        ?? null
                    )
                    : null;


            $reason =
                is_array($evidence)
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
             * Only Ready evidence may proceed to calibration.
             */

            if ($status !== 'Ready') {

                $gameweekResults[] =
                    $gameweekRecord;

                continue;
            }


            $readyGameweeks++;


            /*
             * ====================================================
             * SHARED HISTORICAL CALIBRATION EVIDENCE
             * ====================================================
             *
             * Position-aware weight candidates must not pass
             * through the Strength / Fixture calibration evaluator.
             *
             * Reuse the shared historical evidence service for the
             * immutable recommendation snapshot + realised outcome
             * join.
             */

            if ($this->historicalEvidenceService === null) {

                throw new RuntimeException(
                    'Historical calibration evidence service is unavailable.'
                );
            }


            $historicalEvidence =
                $this->historicalEvidenceService
                    ->build(
                        $entryId,
                        $gameweekId
                    );


            /*
             * A Ready gameweek should normally produce historical
             * rows.
             *
             * If it does not, retain the authoritative Ready status
             * but contribute no rows.
             *
             * Missing historical evidence is never reconstructed.
             */

            $gameweekHistoricalRows =
                [];


            if (
                is_array($historicalEvidence)
                &&
                is_array(
                    $historicalEvidence[
                        'historical_rows'
                    ]
                    ?? null
                )
            ) {

                $gameweekHistoricalRows =
                    $historicalEvidence[
                        'historical_rows'
                    ];
            }


            /*
             * ====================================================
             * COMBINE HISTORICAL PLAYER OBSERVATIONS
             * ====================================================
             *
             * The same player appearing in multiple gameweeks
             * remains a separate player-gameweek observation.
             */

            foreach (
                $gameweekHistoricalRows
                as $row
            ) {

                if (!is_array($row)) {

                    continue;
                }


                /*
                 * Copy before adding gameweek identity so the
                 * single-gameweek source evidence remains immutable.
                 */

                $combinedRow =
                    $row;


                $combinedRow[
                    'gameweek_id'
                ] =
                    $gameweekId;


                $historicalRows[] =
                    $combinedRow;
            }


            $gameweekResults[] =
                $gameweekRecord;
        }


        /*
         * ========================================================
         * AGGREGATE POSITION-AWARE CALIBRATION
         * ========================================================
         *
         * Evaluate the supplied weight candidates once across the
         * complete pooled historical player-gameweek sample.
         *
         * Do not average separate gameweek correlations.
         */

        $calibration =
            $this->calibrationService
                ->evaluate(
                    $historicalRows,
                    $weightCandidates
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

            'historical_rows' =>
                $historicalRows,

            'calibration' =>
                $calibration
        ];
    }
}