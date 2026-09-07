<?php

/**
 * PlayerGameweekSnapshotCandidateProductionCapture
 *
 * Orchestrates pre-deadline player snapshot candidate capture.
 *
 * Responsibilities:
 *
 * - validate the generation timestamp
 * - resolve the nearest future gameweek deadline
 * - load the current live player pool
 * - delegate candidate construction
 * - persist the latest candidate for each player/gameweek
 *
 * This class deliberately does not:
 *
 * - reconstruct completed historical gameweeks
 * - inspect player fixture history
 * - calculate Player Intelligence
 * - promote candidates into immutable snapshots
 */
class PlayerGameweekSnapshotCandidateProductionCapture
{
    private object $gameweekRepository;


    private object $playerRepository;


    private object $candidateBuilder;


    private object $candidateRepository;


    public function __construct(
        object $gameweekRepository,
        object $playerRepository,
        object $candidateBuilder,
        object $candidateRepository
    ) {

        $this->gameweekRepository =
            $gameweekRepository;


        $this->playerRepository =
            $playerRepository;


        $this->candidateBuilder =
            $candidateBuilder;


        $this->candidateRepository =
            $candidateRepository;
    }


    /**
     * Capture the latest live player-state candidates for the
     * first gameweek deadline strictly after generatedAt.
     */
    public function capture(
        string $generatedAt
    ): array {

        /*
         * ========================================================
         * VALIDATE GENERATION TIMESTAMP
         * ========================================================
         */

        try {

            new DateTimeImmutable(
                $generatedAt
            );

        } catch (
            Throwable $exception
        ) {

            throw new InvalidArgumentException(
                'A valid player snapshot generation timestamp is required.',
                0,
                $exception
            );
        }


        /*
         * ========================================================
         * RESOLVE TARGET GAMEWEEK
         * ========================================================
         *
         * Do not depend on current/next flags.
         *
         * The candidate belongs to the first deadline strictly
         * after the generation timestamp.
         */

        $gameweek =
            $this
                ->gameweekRepository
                ->getNextDeadlineAfter(
                    $generatedAt
                );


        /*
         * End of season, or otherwise no future deadline.
         *
         * This is a valid lifecycle state rather than an error.
         */
        if (
            $gameweek === null
        ) {

            return [

                'status' =>
                    'Unavailable',

                'gameweek_id' =>
                    null,

                'fpl_gameweek_id' =>
                    null,

                'players_considered' =>
                    0,

                'candidates_built' =>
                    0,

                'saved' =>
                    0,

                'unchanged' =>
                    0
            ];
        }


        /*
         * ========================================================
         * VALIDATE LOCAL GAMEWEEK IDENTITY
         * ========================================================
         */

        $gameweekId =
            is_numeric(
                $gameweek[
                    'id'
                ]
                ?? null
            )
                ? (int) $gameweek[
                    'id'
                ]
                : 0;


        if (
            $gameweekId <= 0
        ) {

            throw new RuntimeException(
                'Resolved player snapshot gameweek does not '
                . 'contain a valid local gameweek ID.'
            );
        }


        /*
         * ========================================================
         * VALIDATE FPL GAMEWEEK IDENTITY
         * ========================================================
         */

        $fplGameweekId =
            is_numeric(
                $gameweek[
                    'fpl_gameweek_id'
                ]
                ?? null
            )
                ? (int) $gameweek[
                    'fpl_gameweek_id'
                ]
                : 0;


        if (
            $fplGameweekId <= 0
        ) {

            throw new RuntimeException(
                'Resolved player snapshot gameweek does not '
                . 'contain a valid FPL gameweek ID.'
            );
        }


        /*
         * ========================================================
         * VALIDATE DEADLINE
         * ========================================================
         */

        $deadlineTime =
            trim(
                (string) (
                    $gameweek[
                        'deadline_time'
                    ]
                    ?? ''
                )
            );


        if (
            $deadlineTime === ''
        ) {

            throw new RuntimeException(
                'Resolved player snapshot gameweek does not '
                . 'contain a deadline.'
            );
        }


        try {

            $deadline =
                new DateTimeImmutable(
                    $deadlineTime
                );

        } catch (
            Throwable $exception
        ) {

            throw new RuntimeException(
                'Resolved player snapshot gameweek contains '
                . 'an invalid deadline.',
                0,
                $exception
            );
        }


        /*
         * The repository query should already guarantee this,
         * but keep the historical boundary protected here too.
         */
        $generated =
            new DateTimeImmutable(
                $generatedAt
            );


        if (
            $generated
            >=
            $deadline
        ) {

            throw new RuntimeException(
                'Resolved player snapshot gameweek deadline '
                . 'must be after the generation timestamp.'
            );
        }


        /*
         * ========================================================
         * LOAD CURRENT LIVE PLAYER POOL
         * ========================================================
         */

        $players =
            $this
                ->playerRepository
                ->getAll();


        if (
            !is_array(
                $players
            )
        ) {

            throw new RuntimeException(
                'Current live player pool is unavailable.'
            );
        }


        /*
         * ========================================================
         * BUILD PRE-DEADLINE CANDIDATES
         * ========================================================
         *
         * Candidate construction owns the mapping from current
         * player rows into historical player-state evidence.
         */

        $candidates =
            $this
                ->candidateBuilder
                ->buildCandidates(
                    $gameweekId,
                    $generatedAt,
                    $deadlineTime,
                    $players
                );


        if (
            !is_array(
                $candidates
            )
        ) {

            throw new RuntimeException(
                'Player snapshot candidate builder did not '
                . 'return a candidate collection.'
            );
        }


        /*
         * ========================================================
         * PERSIST LATEST CANDIDATES
         * ========================================================
         */

        $saved =
            0;


        $unchanged =
            0;


        foreach (
            $candidates
            as $candidate
        ) {

            if (
                !(
                    $candidate
                    instanceof
                    PlayerGameweekSnapshotCandidate
                )
            ) {

                throw new RuntimeException(
                    'Player snapshot candidate builder returned '
                    . 'an invalid candidate.'
                );
            }


            $wasSaved =
                $this
                    ->candidateRepository
                    ->saveLatest(
                        $gameweekId,
                        $candidate
                    );


            if (
                $wasSaved
            ) {

                $saved++;

            } else {

                $unchanged++;
            }
        }


        /*
         * ========================================================
         * RESULT
         * ========================================================
         */

        return [

            'status' =>
                'Captured',

            'gameweek_id' =>
                $gameweekId,

            'fpl_gameweek_id' =>
                $fplGameweekId,

            'players_considered' =>
                count(
                    $players
                ),

            'candidates_built' =>
                count(
                    $candidates
                ),

            'saved' =>
                $saved,

            'unchanged' =>
                $unchanged
        ];
    }
}