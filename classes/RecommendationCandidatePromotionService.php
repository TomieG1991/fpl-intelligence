<?php

/**
 * RecommendationCandidatePromotionService
 *
 * Promotes the latest mutable RecommendationCandidate into the
 * immutable RecommendationSnapshot history.
 *
 * This service does not calculate, recalculate or interpret any
 * recommendation intelligence.
 *
 * Its responsibility is only to:
 *
 * - retrieve the latest candidate for an entry/gameweek
 * - preserve its recommendation evidence exactly
 * - convert the candidate generation time into the historical
 *   snapshot capture time
 * - delegate immutable persistence to
 *   RecommendationSnapshotRepository
 *
 * The candidate remains available after promotion.
 */
class RecommendationCandidatePromotionService
{
    private RecommendationCandidateRepository $candidateRepository;

    private RecommendationSnapshotRepository $snapshotRepository;


    public function __construct(
        RecommendationCandidateRepository $candidateRepository,
        RecommendationSnapshotRepository $snapshotRepository
    ) {

        $this->candidateRepository =
            $candidateRepository;


        $this->snapshotRepository =
            $snapshotRepository;
    }


    /**
     * Promote the latest recommendation candidate for one
     * entry/gameweek into immutable recommendation history.
     *
     * Returns true only when a new snapshot is inserted.
     *
     * Returns false when:
     *
     * - no candidate exists
     * - a snapshot already exists for the entry/gameweek
     */
    public function promote(
        int $entryId,
        int $gameweekId
    ): bool {

        if (
            $entryId <= 0
        ) {

            throw new InvalidArgumentException(
                'FPL entry ID must be positive.'
            );
        }


        if (
            $gameweekId <= 0
        ) {

            throw new InvalidArgumentException(
                'Gameweek ID must be positive.'
            );
        }


        /*
         * RecommendationSnapshotRepository owns the immutable
         * historical boundary.
         *
         * Avoid constructing another snapshot when one already
         * exists, while insertIfAbsent() remains the final
         * persistence safeguard.
         */
        $existingSnapshot =
            $this->snapshotRepository
                ->getByEntryAndGameweek(
                    $entryId,
                    $gameweekId
                );


        if (
            $existingSnapshot !== null
        ) {

            return false;
        }


        $candidate =
            $this->candidateRepository
                ->getByEntryAndGameweek(
                    $entryId,
                    $gameweekId
                );


        if (
            $candidate === null
        ) {

            return false;
        }


        /*
         * The snapshot capture timestamp is deliberately the
         * candidate generation timestamp.
         *
         * Promotion may happen later, but the historical
         * recommendation was genuinely generated at this time.
         */
        $snapshot =
            new RecommendationSnapshot(
                $gameweekId,
                $entryId,
                (string) $candidate[
                    'generated_at'
                ],
                (string) $candidate[
                    'deadline_time'
                ],
                $candidate[
                    'player_projections'
                ],
                $candidate[
                    'starting_xi'
                ],
                $candidate[
                    'captain_recommendation'
                ],
                $candidate[
                    'transfer_recommendations'
                ],
                $candidate[
                    'gameweek_decision'
                ],
                $candidate[
                    'chip_recommendations'
                ]
            );


        /*
         * insertIfAbsent() is the final immutability safeguard.
         *
         * Even if another process inserted the snapshot between
         * our initial lookup and this point, existing historical
         * evidence must remain unchanged.
         */
        return
            $this->snapshotRepository
                ->insertIfAbsent(
                    $gameweekId,
                    $snapshot
                );
    }
}