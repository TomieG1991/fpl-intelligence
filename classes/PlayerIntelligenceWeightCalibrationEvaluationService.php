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


    public function __construct(
        object $snapshotRepository,
        object $outcomeService,
        object $calibrationService
    ) {

        $this->snapshotRepository =
            $snapshotRepository;


        $this->outcomeService =
            $outcomeService;


        $this->calibrationService =
            $calibrationService;
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
         * LOAD IMMUTABLE HISTORICAL SNAPSHOT
         * ========================================================
         */

        $snapshot =
            $this->snapshotRepository
                ->getByEntryAndGameweek(
                    $entryId,
                    $gameweekId
                );


        /*
         * No immutable historical recommendation means there is
         * nothing legitimate to calibrate.
         *
         * Do not reconstruct from current application state.
         */

        if ($snapshot === null) {

            return null;
        }


        /*
         * ========================================================
         * PRESERVED PLAYER RANKING EVIDENCE
         * ========================================================
         *
         * Legacy snapshots may legitimately contain no historical
         * ranking evidence.
         *
         * Missing ranking evidence remains an empty historical
         * sample rather than being reconstructed.
         */

        $playerRankings =
            is_array(
                $snapshot[
                    'player_rankings'
                ]
                ?? null
            )
                ? $snapshot[
                    'player_rankings'
                ]
                : [];


        /*
         * ========================================================
         * REALISED GAMEWEEK OUTCOMES
         * ========================================================
         *
         * PlayerGameweekOutcomeService owns aggregation of factual
         * fixture-history evidence.
         *
         * This orchestration layer must not query or aggregate
         * fixture history directly.
         */

        $playerOutcomes =
            $this->outcomeService
                ->getByGameweekId(
                    $gameweekId
                );


        /*
         * ========================================================
         * INDEX REALISED OUTCOMES BY LOCAL PLAYER ID
         * ========================================================
         */

        $outcomesByPlayerId =
            [];


        foreach (
            $playerOutcomes
            as $outcome
        ) {

            if (!is_array($outcome)) {

                continue;
            }


            $playerId =
                (int) (
                    $outcome[
                        'player_id'
                    ]
                    ?? 0
                );


            if ($playerId <= 0) {

                continue;
            }


            $outcomesByPlayerId[
                $playerId
            ] =
                $outcome;
        }


        /*
         * ========================================================
         * BUILD HISTORICAL CALIBRATION ROWS
         * ========================================================
         *
         * Historical ranking membership and ordering come from the
         * immutable recommendation snapshot.
         *
         * Realised outcomes enrich those rows by local player ID.
         */

        $historicalRows =
            [];


        foreach (
            $playerRankings
            as $ranking
        ) {

            if (!is_array($ranking)) {

                continue;
            }


            $playerId =
                (int) (
                    $ranking[
                        'player_id'
                    ]
                    ?? 0
                );


            if ($playerId <= 0) {

                continue;
            }


            $outcome =
                $outcomesByPlayerId[
                    $playerId
                ]
                ??
                null;


            /*
             * Missing realised outcome remains null.
             *
             * Genuine historical zero points remain zero.
             */

            $actualPoints =
                null;


            if (
                is_array($outcome)
                &&
                array_key_exists(
                    'total_points',
                    $outcome
                )
                &&
                $outcome[
                    'total_points'
                ] !== null
                &&
                is_numeric(
                    $outcome[
                        'total_points'
                    ]
                )
            ) {

                $actualPoints =
                    $outcome[
                        'total_points'
                    ] + 0;
            }


            /*
             * ====================================================
             * HISTORICAL CALIBRATION EVIDENCE
             * ====================================================
             *
             * Preserve only recommendation-time evidence that was
             * actually captured.
             *
             * Missing component evidence remains null.
             */

            $historicalRows[] = [

                'player_id' =>
                    $playerId,

                'fpl_player_id' =>
                    $ranking[
                        'fpl_player_id'
                    ]
                    ?? null,

                'name' =>
                    $ranking[
                        'name'
                    ]
                    ?? null,

                'position' =>
                    $ranking[
                        'position'
                    ]
                    ?? null,

                'strength_rating' =>
                    $ranking[
                        'strength_rating'
                    ]
                    ?? null,

                'fixture_rating' =>
                    $ranking[
                        'fixture_rating'
                    ]
                    ?? null,

                'next_fixture_rating' =>
                    $ranking[
                        'next_fixture_rating'
                    ]
                    ?? null,

                'base_next_fixture_rating' =>
                    $ranking[
                        'base_next_fixture_rating'
                    ]
                    ?? null,

                'next_opponent_attack_rating' =>
                    $ranking[
                        'next_opponent_attack_rating'
                    ]
                    ?? null,

                'next_opponent_defence_rating' =>
                    $ranking[
                        'next_opponent_defence_rating'
                    ]
                    ?? null,

                'availability_multiplier' =>
                    $ranking[
                        'availability_multiplier'
                    ]
                    ?? null,

                'actual_points' =>
                    $actualPoints
            ];
        }


        /*
         * ========================================================
         * RUN CALIBRATION
         * ========================================================
         *
         * Candidate score calculation and objective metrics remain
         * the responsibility of PlayerIntelligenceWeightCalibrationService.
         */

        $calibration =
            $this->calibrationService
                ->evaluate(
                    $historicalRows,
                    $weightCandidates
                );


        /*
         * ========================================================
         * STABLE INITIAL ORCHESTRATION CONTRACT
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