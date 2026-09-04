<?php

/**
 * RecommendationCandidateCaptureService
 *
 * Captures existing production recommendation intelligence as
 * the latest mutable pre-deadline RecommendationCandidate.
 *
 * This service does not calculate, recalculate or interpret:
 *
 * - Expected Points
 * - Starting XI intelligence
 * - Captain Intelligence
 * - Transfer Intelligence
 * - Gameweek Decision intelligence
 * - Chip Intelligence
 *
 * It only validates that the required existing evidence is
 * present, extracts the relevant recommendation sections and
 * delegates candidate persistence to
 * RecommendationCandidateRepository.
 *
 * Unlike RecommendationSnapshotCaptureService, candidate
 * persistence is mutable before the deadline:
 *
 * - first recommendation is stored
 * - strictly newer recommendation replaces existing evidence
 * - older recommendation is rejected
 * - equal-time recommendation is rejected
 */
class RecommendationCandidateCaptureService
{
    private RecommendationCandidateRepository $repository;


    public function __construct(
        RecommendationCandidateRepository $repository
    ) {

        $this->repository =
            $repository;
    }


    /**
     * Capture existing production intelligence as the latest
     * recommendation candidate for one entry/gameweek.
     */
    public function capture(
        int $gameweekId,
        int $entryId,
        string $generatedAt,
        string $deadlineTime,
        array $playerProjections,
        array $gameweekDecisionResult,
        array $chipRecommendations
    ): bool {

        if (
            $gameweekId <= 0
        ) {

            throw new InvalidArgumentException(
                'Gameweek ID must be positive.'
            );
        }


        if (
            empty(
                $playerProjections
            )
        ) {

            throw new InvalidArgumentException(
                'Player projection evidence is required.'
            );
        }


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
                'A successful Gameweek Decision result is required.'
            );
        }


        /*
         * The Gameweek Intelligence result owns Starting XI
         * construction and formation validity.
         *
         * This capture boundary only requires that the existing
         * Starting XI evidence is present.
         */
        $gameweek =
            $gameweekDecisionResult[
                'gameweek'
            ]
            ??
            null;


        if (
            !is_array(
                $gameweek
            )
            ||
            empty(
                $gameweek[
                    'starting_xi'
                ]
                ??
                []
            )
        ) {

            throw new InvalidArgumentException(
                'Starting XI evidence is required.'
            );
        }


        $captainRecommendation =
            $gameweekDecisionResult[
                'captaincy'
            ]
            ??
            null;


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
                'Captain Intelligence evidence is required.'
            );
        }


        $transferRecommendations =
            $gameweekDecisionResult[
                'transfers'
            ]
            ??
            null;


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
                'Transfer Intelligence evidence is required.'
            );
        }


        $gameweekDecision =
            $gameweekDecisionResult[
                'decision'
            ]
            ??
            null;


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
                'Gameweek Decision evidence is required.'
            );
        }


        if (
            empty(
                $chipRecommendations
            )
        ) {

            throw new InvalidArgumentException(
                'Chip Intelligence evidence is required.'
            );
        }


        /*
         * Preserve the existing recommendation evidence exactly.
         *
         * No intelligence is recalculated or interpreted here.
         */
        $candidate =
            new RecommendationCandidate(
                $gameweekId,
                $entryId,
                $generatedAt,
                $deadlineTime,
                $playerProjections,
                $gameweek[
                    'starting_xi'
                ],
                $captainRecommendation,
                $transferRecommendations,
                $gameweekDecision,
                $chipRecommendations
            );


        /*
         * RecommendationCandidateRepository owns the mutable
         * staging semantics.
         *
         * It returns true when:
         *
         * - this is the first candidate
         * - this candidate is strictly newer
         *
         * It returns false when:
         *
         * - an existing candidate is newer
         * - the timestamps are equal
         */
        return
            $this->repository
                ->saveLatest(
                    $gameweekId,
                    $candidate
                );
    }
}