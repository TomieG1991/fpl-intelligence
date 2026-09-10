<?php

/**
 * EffectiveConfidenceWeightCalibrationHistoryService
 *
 * v0.36.0 — Model Calibration & Intelligence Quality
 *
 * Evaluates explicitly supplied Effective Confidence weight
 * candidates across all historically eligible gameweeks for one
 * FPL entry.
 *
 * Historical eligibility is delegated to:
 *
 * GameweekBacktestingEvidenceService
 *
 * Historical player rows are supplied by:
 *
 * PlayerCalibrationHistoricalEvidenceService
 *
 * This service does not:
 *
 * - infer historical eligibility itself
 * - reconstruct missing recommendation evidence
 * - query live Player Intelligence
 * - query live fixture data
 * - manufacture realised outcomes
 * - compare confidence against FPL points
 * - choose or rank a winning weight combination
 * - modify production Effective Confidence weights
 * - persist calibration results
 */
class EffectiveConfidenceWeightCalibrationHistoryService
{
    private object $gameweekRepository;

    private object $evidenceService;

    private object $historicalEvidenceService;

    private object $calibrationService;


    public function __construct(
        object $gameweekRepository,
        object $evidenceService,
        object $historicalEvidenceService,
        object $calibrationService
    ) {

        $this->gameweekRepository =
            $gameweekRepository;


        $this->evidenceService =
            $evidenceService;


        $this->historicalEvidenceService =
            $historicalEvidenceService;


        $this->calibrationService =
            $calibrationService;
    }


    /**
     * Evaluate Effective Confidence weight candidates across all
     * historically eligible gameweeks for one FPL entry.
     */
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
             * Malformed repository rows are ignored.
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
             * GameweekBacktestingEvidenceService remains the only
             * authority for deciding whether a gameweek may
             * contribute historical calibration evidence.
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
             * GAMEWEEK AUDIT RECORD
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
             * Only authoritative Ready gameweeks may contribute
             * historical rows.
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
             * Effective Confidence calibration must use the shared
             * immutable recommendation snapshot + realised outcome
             * evidence service directly.
             *
             * It must not route through the Strength / Fixture
             * calibration evaluator.
             */

            $historicalEvidence =
                $this->historicalEvidenceService
                    ->build(
                        $entryId,
                        $gameweekId
                    );


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
             * COMBINE HISTORICAL PLAYER-GAMEWEEK OBSERVATIONS
             * ====================================================
             */

            foreach (
                $gameweekHistoricalRows
                as $row
            ) {

                if (!is_array($row)) {

                    continue;
                }


                /*
                 * Copy the row before adding local gameweek
                 * identity so the source evidence is not mutated.
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
         * AGGREGATE EFFECTIVE CONFIDENCE CALIBRATION
         * ========================================================
         *
         * Evaluate candidates once across the pooled historical
         * player-gameweek sample.
         *
         * Do not calculate separate per-gameweek winners or average
         * per-gameweek correlations.
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