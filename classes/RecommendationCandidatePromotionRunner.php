<?php

/**
 * RecommendationCandidatePromotionRunner
 *
 * Coordinates automatic promotion of recommendation candidates
 * whose preserved deadline has been reached.
 *
 * Responsibilities:
 *
 * - validate and normalise the promotion run timestamp
 * - discover ready candidates through the candidate repository
 * - preserve repository processing order
 * - pass candidate identity to the existing promotion service
 * - report promotion accounting
 *
 * This class deliberately does not:
 *
 * - calculate recommendation intelligence
 * - reconstruct recommendation evidence
 * - modify recommendation candidates
 * - write recommendation snapshots directly
 * - decide whether an existing snapshot may be replaced
 */
class RecommendationCandidatePromotionRunner
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
     * Promote all recommendation candidates whose preserved
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
                'Promotion run timestamp must be a valid date/time.',
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
         */

        $readyCandidates =
            $this->candidateRepository
                ->getReadyForPromotion(
                    $normalisedTimestamp
                );


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
         * The runner deliberately passes only candidate identity
         * to RecommendationCandidatePromotionService. The service
         * remains responsible for retrieving the persisted
         * evidence and creating the immutable snapshot.
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
                    'Promotion-ready candidate must be an array.'
                );
            }


            $entryId =
                (int) (
                    $candidate[
                        'entry_id'
                    ]
                    ?? 0
                );


            $gameweekId =
                (int) (
                    $candidate[
                        'gameweek_id'
                    ]
                    ?? 0
                );


            if (
                $entryId <= 0
            ) {

                throw new RuntimeException(
                    'Promotion-ready candidate must have a positive entry ID.'
                );
            }


            if (
                $gameweekId <= 0
            ) {

                throw new RuntimeException(
                    'Promotion-ready candidate must have a positive gameweek ID.'
                );
            }


            $promotionResult =
                $this->promotionService
                    ->promote(
                        $entryId,
                        $gameweekId
                    );


            if (
                $promotionResult === true
            ) {

                $promoted++;

                continue;
            }


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