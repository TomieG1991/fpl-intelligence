<?php

/**
 * Captures an immutable historical snapshot of recommendation
 * intelligence that has already been calculated elsewhere.
 *
 * This service is deliberately an orchestration layer only.
 *
 * It does not calculate:
 *
 * - Player Intelligence
 * - Expected Points
 * - Gameweek Intelligence
 * - Captain Intelligence
 * - Transfer Intelligence
 * - Chip Intelligence
 *
 * Its responsibility is to validate that the required existing
 * outputs are present, map those outputs into the historical
 * RecommendationSnapshot contract, and persist the snapshot.
 */
class RecommendationSnapshotCaptureService
{
    private RecommendationSnapshotRepository $repository;


    public function __construct(
        RecommendationSnapshotRepository $repository
    ) {

        $this->repository =
            $repository;
    }


    /**
     * Capture one immutable pre-deadline recommendation snapshot.
     */
    public function capture(
        int $gameweekId,
        int $entryId,
        string $capturedAt,
        string $deadlineTime,
        array $playerProjections,
        array $gameweekDecisionResult,
        array $chipRecommendations
    ): bool {

        /*
         * ========================================================
         * GAMEWEEK IDENTITY
         * ========================================================
         */

        if (
            $gameweekId <= 0
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot capture requires '
                . 'a positive local gameweek ID.'
            );
        }


        /*
         * ========================================================
         * PLAYER PROJECTION EVIDENCE
         * ========================================================
         */

        if (
            empty(
                $playerProjections
            )
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot capture requires '
                . 'player projection evidence.'
            );
        }


        /*
         * ========================================================
         * COMPLETE GAMEWEEK DECISION RESULT
         * ========================================================
         */

        if (
            (
                $gameweekDecisionResult[
                    'status'
                ]
                ?? null
            )
            !==
            'success'
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot capture requires '
                . 'a successful Gameweek Decision result.'
            );
        }


        /*
         * ========================================================
         * GAMEWEEK INTELLIGENCE
         * ========================================================
         */

        $gameweekResult =
            $gameweekDecisionResult[
                'gameweek'
            ]
            ?? null;


        if (
            !is_array(
                $gameweekResult
            )
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot capture requires '
                . 'Gameweek Intelligence output.'
            );
        }


        $startingXI =
            $gameweekResult[
                'starting_xi'
            ]
            ?? null;


        if (
            !is_array(
                $startingXI
            )
            ||
            empty(
                $startingXI
            )
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot capture requires '
                . 'a Starting XI.'
            );
        }


        /*
         * ========================================================
         * CAPTAIN INTELLIGENCE
         * ========================================================
         */

        $captainRecommendation =
            $gameweekDecisionResult[
                'captaincy'
            ]
            ?? null;


        if (
            !is_array(
                $captainRecommendation
            )
            ||
            empty(
                $captainRecommendation
            )
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot capture requires '
                . 'Captain Intelligence output.'
            );
        }


        /*
         * ========================================================
         * TRANSFER INTELLIGENCE
         * ========================================================
         */

        $transferRecommendations =
            $gameweekDecisionResult[
                'transfers'
            ]
            ?? null;


        if (
            !is_array(
                $transferRecommendations
            )
            ||
            empty(
                $transferRecommendations
            )
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot capture requires '
                . 'Transfer Intelligence output.'
            );
        }


        /*
         * ========================================================
         * GAMEWEEK DECISION
         * ========================================================
         */

        $gameweekDecision =
            $gameweekDecisionResult[
                'decision'
            ]
            ?? null;


        if (
            !is_array(
                $gameweekDecision
            )
            ||
            empty(
                $gameweekDecision
            )
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot capture requires '
                . 'Gameweek Decision output.'
            );
        }


        /*
         * ========================================================
         * CHIP INTELLIGENCE
         * ========================================================
         */

        if (
            empty(
                $chipRecommendations
            )
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot capture requires '
                . 'Chip Intelligence evidence.'
            );
        }


        /*
         * ========================================================
         * BUILD HISTORICAL SNAPSHOT
         * ========================================================
         *
         * RecommendationSnapshot owns the domain validation for:
         *
         * - gameweek number
         * - FPL entry ID
         * - capture timestamp
         * - deadline timestamp
         * - capture occurring before the deadline
         *
         * No recommendation values are recalculated here.
         */

        $snapshot =
            new RecommendationSnapshot(
                $gameweekId,
                $entryId,
                $capturedAt,
                $deadlineTime,
                $playerProjections,
                $startingXI,
                $captainRecommendation,
                $transferRecommendations,
                $gameweekDecision,
                $chipRecommendations
            );


        /*
         * ========================================================
         * IMMUTABLE PERSISTENCE
         * ========================================================
         *
         * RecommendationSnapshotRepository::insertIfAbsent()
         * owns the database-level immutability rule.
         *
         * A second capture for the same entry/gameweek therefore
         * returns false and leaves the original historical record
         * unchanged.
         */

        return
            $this->repository
                ->insertIfAbsent(
                    $gameweekId,
                    $snapshot
                );
    }
}