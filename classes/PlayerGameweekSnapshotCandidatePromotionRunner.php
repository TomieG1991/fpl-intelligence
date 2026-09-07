<?php

/**
 * PlayerGameweekSnapshotCandidatePromotionRunner
 *
 * Coordinates automatic promotion of player snapshot candidates
 * whose preserved deadline has been reached.
 *
 * Responsibilities:
 *
 * - validate and normalise the promotion run timestamp
 * - discover ready player snapshot candidates
 * - preserve repository processing order
 * - pass player/gameweek identity to the promotion service
 * - report promotion accounting
 *
 * This class deliberately does not:
 *
 * - inspect current live player data
 * - inspect fixture history
 * - reconstruct historical player state
 * - modify candidate evidence
 * - write immutable snapshots directly
 * - decide whether an existing snapshot may be replaced
 */
class PlayerGameweekSnapshotCandidatePromotionRunner
{
    private object $candidateRepository;


    private object $promotionService;


    public function __construct(
        object $candidateRepository,
        object $promotionService
    ) {

        $this->candidateRepository =
            $candidateRepository;


        $this->promotionService =
            $promotionService;
    }


    /**
     * Promote all player snapshot candidates whose preserved
     * deadline has been reached at the supplied timestamp.
     */
    public function run(
        string $timestamp
    ): array {

        /*
         * ========================================================
         * VALIDATE AND NORMALISE TIMESTAMP
         * ========================================================
         */

        try {

            $runAt =
                new DateTimeImmutable(
                    $timestamp
                );

        } catch (
            Throwable $exception
        ) {

            throw new InvalidArgumentException(
                'Player snapshot promotion run timestamp must '
                . 'be a valid date/time.',
                0,
                $exception
            );
        }


        $normalisedTimestamp =
            $runAt
                ->format(
                    'Y-m-d H:i:s'
                );


        /*
         * ========================================================
         * DISCOVER READY CANDIDATES
         * ========================================================
         *
         * Readiness belongs to the candidate repository because
         * the candidate itself preserves the target deadline.
         *
         * No fixture-history evidence is consulted here.
         */

        $readyCandidates =
            $this
                ->candidateRepository
                ->getReadyForPromotion(
                    $normalisedTimestamp
                );


        if (
            !is_array(
                $readyCandidates
            )
        ) {

            throw new RuntimeException(
                'Player snapshot candidate repository did not '
                . 'return a candidate collection.'
            );
        }


        $ready =
            count(
                $readyCandidates
            );


        $promoted =
            0;


        $unchanged =
            0;


        /*
         * ========================================================
         * PROMOTE READY CANDIDATES
         * ========================================================
         *
         * The repository already provides deterministic ordering.
         *
         * The runner deliberately passes only player/gameweek
         * identity to the promotion service.
         *
         * The promotion service remains responsible for retrieving
         * the persisted candidate evidence and performing the
         * immutable insert.
         */

        foreach (
            $readyCandidates
            as $candidate
        ) {

            if (
                !is_array(
                    $candidate
                )
            ) {

                throw new RuntimeException(
                    'Promotion-ready player snapshot candidate '
                    . 'must be an array.'
                );
            }


            $playerId =
                is_numeric(
                    $candidate[
                        'player_id'
                    ]
                    ?? null
                )
                    ? (int) $candidate[
                        'player_id'
                    ]
                    : 0;


            $gameweekId =
                is_numeric(
                    $candidate[
                        'gameweek_id'
                    ]
                    ?? null
                )
                    ? (int) $candidate[
                        'gameweek_id'
                    ]
                    : 0;


            if (
                $playerId <= 0
            ) {

                throw new RuntimeException(
                    'Promotion-ready player snapshot candidate '
                    . 'must have a positive player ID.'
                );
            }


            if (
                $gameweekId <= 0
            ) {

                throw new RuntimeException(
                    'Promotion-ready player snapshot candidate '
                    . 'must have a positive gameweek ID.'
                );
            }


            $promotionResult =
                $this
                    ->promotionService
                    ->promote(
                        $playerId,
                        $gameweekId
                    );


            /*
             * A true result means a new immutable historical
             * snapshot was inserted.
             */
            if (
                $promotionResult === true
            ) {

                $promoted++;

                continue;
            }


            /*
             * False is a valid idempotent result.
             *
             * Examples:
             *
             * - the immutable snapshot already exists
             * - no candidate can ultimately be promoted
             * - insertIfAbsent() protected against a race
             */
            $unchanged++;
        }


        /*
         * ========================================================
         * RESULT
         * ========================================================
         */

        return [

            'status' =>
                'Complete',

            'ready' =>
                $ready,

            'promoted' =>
                $promoted,

            'unchanged' =>
                $unchanged
        ];
    }
}