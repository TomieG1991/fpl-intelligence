<?php

/**
 * PlayerIntelligenceWeightCalibrationHistoryService
 *
 * v0.36.0 — Model Calibration & Intelligence Quality
 *
 * Evaluates explicitly supplied Player Intelligence
 * Strength / Fixture weight candidates across all historically
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
 * - query fixture history directly
 * - manufacture realised outcomes
 * - choose or rank a winning weight combination
 * - modify production weights
 * - persist calibration results
 */
class PlayerIntelligenceWeightCalibrationHistoryService
{
    private object $gameweekRepository;

    private object $evidenceService;

    private object $evaluationService;

    private object $calibrationService;


    public function __construct(
        object $gameweekRepository,
        object $evidenceService,
        object $evaluationService,
        object $calibrationService
    ) {

        $this->gameweekRepository =
            $gameweekRepository;


        $this->evidenceService =
            $evidenceService;


        $this->evaluationService =
            $evaluationService;


        $this->calibrationService =
            $calibrationService;
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
                        ??
                        null
                    )
                    : null;


            $reason =
                is_array($evidence)
                    ? (
                        $evidence[
                            'reason'
                        ]
                        ??
                        null
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
             * SINGLE-GAMEWEEK CALIBRATION EVALUATION
             * ====================================================
             *
             * Reuse the established orchestration service rather
             * than duplicating the ranking/outcome join.
             */

            $evaluation =
                $this->evaluationService
                    ->evaluate(
                        $entryId,
                        $gameweekId,
                        $weightCandidates
                    );


            /*
             * A Ready gameweek should normally produce an
             * evaluation result.
             *
             * If it does not, preserve the authoritative Ready
             * status but simply contribute no historical rows.
             *
             * Do not reconstruct missing data.
             */

            $gameweekHistoricalRows =
                [];


            if (
                is_array($evaluation)
                &&
                is_array(
                    $evaluation[
                        'historical_rows'
                    ]
                    ?? null
                )
            ) {

                $gameweekHistoricalRows =
                    $evaluation[
                        'historical_rows'
                    ];
            }


            /*
             * ====================================================
             * COMBINE HISTORICAL PLAYER OBSERVATIONS
             * ====================================================
             *
             * The same player appearing in multiple gameweeks is
             * intentionally preserved as multiple observations.
             */

            foreach (
                $gameweekHistoricalRows
                as $row
            ) {

                if (!is_array($row)) {

                    continue;
                }


                /*
                 * Add source gameweek identity.
                 *
                 * Do not overwrite the source row itself.
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
         * AGGREGATE CALIBRATION
         * ========================================================
         *
         * Evaluate candidate weights once across the combined
         * historical sample.
         *
         * This service does not select or rank a winner.
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