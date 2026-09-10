<?php

/**
 * PlayerCalibrationHistoricalEvidenceService
 *
 * Builds reusable historical player calibration evidence from:
 *
 * - immutable recommendation-time Player Ranking evidence
 * - already-aggregated realised gameweek outcomes
 *
 * This service deliberately contains no calibration model.
 *
 * It does not:
 *
 * - calculate candidate scores
 * - select calibration weights
 * - query live Player Intelligence
 * - query fixture history directly
 * - reconstruct missing recommendation evidence
 * - manufacture missing realised outcomes
 * - persist results
 */
class PlayerCalibrationHistoricalEvidenceService
{
    private object $snapshotRepository;

    private object $outcomeService;


    public function __construct(
        object $snapshotRepository,
        object $outcomeService
    ) {

        $this->snapshotRepository =
            $snapshotRepository;


        $this->outcomeService =
            $outcomeService;
    }


    /**
     * Build one gameweek of historical calibration evidence.
     */
    public function build(
        int $entryId,
        int $gameweekId
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
         * LOAD IMMUTABLE RECOMMENDATION SNAPSHOT
         * ========================================================
         */

        $snapshot =
            $this->snapshotRepository
                ->getByEntryAndGameweek(
                    $entryId,
                    $gameweekId
                );


        /*
         * Missing immutable recommendation evidence means there is
         * no legitimate historical calibration sample.
         *
         * Do not query outcomes or reconstruct anything from current
         * application state.
         */

        if ($snapshot === null) {

            return null;
        }


        /*
         * ========================================================
         * PRESERVED PLAYER RANKING EVIDENCE
         * ========================================================
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
         * PlayerGameweekOutcomeService owns factual outcome
         * aggregation.
         */

        $playerOutcomes =
            $this->outcomeService
                ->getByGameweekId(
                    $gameweekId
                );


        /*
         * ========================================================
         * INDEX OUTCOMES BY LOCAL PLAYER ID
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
         * Ranking membership and order come entirely from the
         * immutable recommendation snapshot.
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
                ?? null;


            /*
             * Missing realised outcome remains null.
             *
             * Genuine zero and negative FPL returns remain numeric.
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
            
            
            $actualMinutes =
                null;


            if (
                is_array($outcome)
                &&
                array_key_exists(
                    'minutes',
                    $outcome
                )
                &&
                $outcome[
                    'minutes'
                ] !== null
                &&
                is_numeric(
                    $outcome[
                        'minutes'
                    ]
                )
            ) {

                $actualMinutes =
                    $outcome[
                        'minutes'
                    ] + 0;
            }
            
            $actualFixtureCount =
                null;


            if (
                is_array($outcome)
                &&
                array_key_exists(
                    'fixture_count',
                    $outcome
                )
                &&
                $outcome[
                    'fixture_count'
                ] !== null
                &&
                is_numeric(
                    $outcome[
                        'fixture_count'
                    ]
                )
            ) {

                $actualFixtureCount =
                    $outcome[
                        'fixture_count'
                    ] + 0;
            }


            /*
             * ====================================================
             * REUSABLE HISTORICAL CALIBRATION EVIDENCE
             * ====================================================
             *
             * Preserve recommendation-time values exactly as
             * captured.
             *
             * Missing fields remain null.
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

                /*
                 * Player Intelligence Strength / Fixture
                 * calibration evidence.
                 */

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

                'availability_multiplier' =>
                    $ranking[
                        'availability_multiplier'
                    ]
                    ?? null,
                    
                /*
                 * Effective Confidence calibration evidence.
                 *
                 * Preserve the raw recommendation-time inputs needed
                 * to replay alternative confidence blends historically.
                 */

                'sample_confidence' =>
                    $ranking[
                        'sample_confidence'
                    ]
                    ?? null,

                'participation_rate' =>
                    $ranking[
                        'participation_rate'
                    ]
                    ?? null,

                /*
                 * Position-aware immediate Fixture calibration
                 * evidence.
                 */

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

                /*
                 * Realised completed-gameweek outcome.
                 */

                'actual_points' =>
                    $actualPoints,

                'actual_minutes' =>
                    $actualMinutes,

                'actual_fixture_count' =>
                    $actualFixtureCount
            ];
        }


        /*
         * ========================================================
         * STABLE HISTORICAL EVIDENCE CONTRACT
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
                $historicalRows
        ];
    }
}