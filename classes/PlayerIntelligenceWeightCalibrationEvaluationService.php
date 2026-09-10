<?php

/**
 * PlayerIntelligenceWeightCalibrationEvaluationService
 *
 * Production orchestration boundary for historical
 * Player Intelligence weight calibration.
 *
 * This service connects:
 *
 * - immutable recommendation-time Player Ranking evidence
 * - already-aggregated realised gameweek outcomes
 * - PlayerIntelligenceWeightCalibrationService
 *
 * It does not:
 *
 * - reconstruct historical Player Intelligence
 * - query live Player Intelligence
 * - calculate candidate Intelligence Scores itself
 * - query fixture history directly
 * - manufacture missing realised outcomes
 * - choose or rank a winning weight combination
 * - modify production model weights
 * - persist calibration results
 */
class PlayerIntelligenceWeightCalibrationEvaluationService
{
    private object $snapshotRepository;

    private object $outcomeService;

    private object $calibrationService;
    
    private object $historicalEvidenceService;


    public function __construct(
        object $snapshotRepository,
        object $outcomeService,
        object $calibrationService,
        ?object $historicalEvidenceService = null
    ) {

        $this->snapshotRepository =
            $snapshotRepository;


        $this->outcomeService =
            $outcomeService;


        $this->calibrationService =
            $calibrationService;


        /*
         * Preserve compatibility with all existing callers while
         * allowing the historical evidence boundary to be injected
         * directly for testing and alternative calibration workflows.
         */
        $this->historicalEvidenceService =
            $historicalEvidenceService
            ??
            new PlayerCalibrationHistoricalEvidenceService(
                $snapshotRepository,
                $outcomeService
            );
    }


    /**
     * Evaluate one immutable historical recommendation snapshot
     * against realised completed-gameweek evidence using explicitly
     * supplied Strength / Fixture weight candidates.
     */
    public function evaluate(
        int $entryId,
        int $gameweekId,
        array $weightCandidates
    ): ?array {

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
         * VALIDATE GAMEWEEK
         * ========================================================
         */

        if ($gameweekId <= 0) {

            throw new InvalidArgumentException(
                'Gameweek ID must be a positive integer.'
            );
        }


        /*
         * ========================================================
         * BUILD REUSABLE HISTORICAL EVIDENCE
         * ========================================================
         *
         * Snapshot loading, realised-outcome lookup and historical
         * row assembly are owned by the shared historical evidence
         * service.
         *
         * This evaluator now owns only Strength / Fixture
         * calibration orchestration.
         */

        $historicalEvidence =
            $this->historicalEvidenceService
                ->build(
                    $entryId,
                    $gameweekId
                );


        /*
         * Missing immutable recommendation evidence remains
         * unavailable.
         *
         * Do not reconstruct historical evidence.
         */

        if ($historicalEvidence === null) {

            return null;
        }


        $snapshot =
            $historicalEvidence[
                'snapshot'
            ]
            ?? null;


        $playerOutcomes =
            is_array(
                $historicalEvidence[
                    'player_outcomes'
                ]
                ?? null
            )
                ? $historicalEvidence[
                    'player_outcomes'
                ]
                : [];


        $historicalRows =
            is_array(
                $historicalEvidence[
                    'historical_rows'
                ]
                ?? null
            )
                ? $historicalEvidence[
                    'historical_rows'
                ]
                : [];


        /*
         * ========================================================
         * RUN STRENGTH / FIXTURE CALIBRATION
         * ========================================================
         */

        $calibration =
            $this->calibrationService
                ->evaluate(
                    $historicalRows,
                    $weightCandidates
                );


        /*
         * ========================================================
         * PRESERVE EXISTING PUBLIC RESULT CONTRACT
         * ========================================================
         */

        return [

            'entry_id' =>
                $entryId,

            'gameweek_id' =>
                $gameweekId,

            'snapshot' =>
                $snapshot,

            'player_outcomes' =>
                $playerOutcomes,

            'historical_rows' =>
                $historicalRows,

            'calibration' =>
                $calibration
        ];
    }
}