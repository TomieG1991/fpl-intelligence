<?php

/**
 * RecommendationCandidateProductionCapture
 *
 * Resolves the recommendation target gameweek from the
 * generation timestamp and delegates the already-calculated
 * production evidence to RecommendationCandidateProductionService.
 *
 * This class deliberately does not calculate any intelligence.
 */
class RecommendationCandidateProductionCapture
{
    private object $gameweekRepository;


    private object $productionService;


    public function __construct(
        object $gameweekRepository,
        object $productionService
    ) {

        $this->gameweekRepository =
            $gameweekRepository;


        $this->productionService =
            $productionService;
    }


    /**
     * Resolve the first future deadline and capture the latest
     * recommendation candidate for that gameweek.
     */
    public function capture(
        int $entryId,
        array $importedSquad,
        array $wildcardResult,
        array $freeHitResult,
        array $benchBoostResult,
        array $tripleCaptainResult,
        string $generatedAt
    ): bool {

        /*
         * ========================================================
         * VALIDATE ENTRY
         * ========================================================
         *
         * Preview and integration flows use entry ID zero.
         *
         * Historical recommendation evidence must only be written
         * for genuine positive FPL entry IDs.
         */

        if ($entryId <= 0) {

            throw new InvalidArgumentException(
                'A positive FPL entry ID is required.'
            );
        }


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
            Exception $exception
        ) {

            throw new InvalidArgumentException(
                'A valid recommendation generation timestamp is required.',
                0,
                $exception
            );
        }


        /*
         * ========================================================
         * RESOLVE TARGET GAMEWEEK
         * ========================================================
         *
         * The repository owns the deadline query.
         *
         * This coordinator does not depend on FPL's current / next
         * flags. The recommendation belongs to the nearest deadline
         * strictly after the generation timestamp.
         */

        $gameweek =
            $this
                ->gameweekRepository
                ->getNextDeadlineAfter(
                    $generatedAt
                );


        /*
         * End-of-season or otherwise no future deadline.
         *
         * This is a valid state rather than an application error.
         */
        if ($gameweek === null) {

            return
                false;
        }


        /*
         * ========================================================
         * VALIDATE RESOLVED GAMEWEEK
         * ========================================================
         */

        $gameweekId =
            is_numeric(
                $gameweek[
                    'id'
                ]
                ??
                null
            )
                ? (int) $gameweek[
                    'id'
                ]
                : 0;


        if ($gameweekId <= 0) {

            throw new RuntimeException(
                'Resolved recommendation gameweek does not contain a valid local gameweek ID.'
            );
        }


        $deadlineTime =
            trim(
                (string) (
                    $gameweek[
                        'deadline_time'
                    ]
                    ??
                    ''
                )
            );


        if ($deadlineTime === '') {

            throw new RuntimeException(
                'Resolved recommendation gameweek does not contain a deadline.'
            );
        }


        try {

            new DateTimeImmutable(
                $deadlineTime
            );

        } catch (
            Exception $exception
        ) {

            throw new RuntimeException(
                'Resolved recommendation gameweek contains an invalid deadline.',
                0,
                $exception
            );
        }


        /*
         * ========================================================
         * DELEGATE PRODUCTION EVIDENCE
         * ========================================================
         *
         * All intelligence has already been calculated by existing
         * production services.
         *
         * This coordinator only supplies historical gameweek
         * identity and delegates the unchanged evidence.
         */

        return
            $this
                ->productionService
                ->capture(
                    $gameweekId,
                    $entryId,
                    $generatedAt,
                    $deadlineTime,
                    $importedSquad,
                    $wildcardResult,
                    $freeHitResult,
                    $benchBoostResult,
                    $tripleCaptainResult
                );
    }
}