<?php

/**
 * Represents the latest recommendation evidence generated
 * for an FPL entry before a gameweek deadline.
 *
 * A RecommendationCandidate is not yet immutable historical
 * evidence.
 *
 * A later recommendation calculation may replace the candidate
 * before the deadline.
 *
 * Once the deadline capture process selects the latest candidate,
 * RecommendationSnapshot becomes the immutable historical record.
 *
 * This object preserves recommendation evidence only.
 *
 * It does not calculate or interpret:
 *
 * - Player projections
 * - Starting XI
 * - Captain Intelligence
 * - Transfer Intelligence
 * - Gameweek Decision
 * - Chip Intelligence
 */
class RecommendationCandidate
{
    private int $gameweek;

    private int $entryId;

    private string $generatedAt;

    private string $deadlineTime;

    private array $playerProjections;

    private array $startingXI;

    private array $captainRecommendation;

    private array $transferRecommendations;

    private array $gameweekDecision;

    private array $chipRecommendations;


    public function __construct(
        int $gameweek,
        int $entryId,
        string $generatedAt,
        string $deadlineTime,
        array $playerProjections,
        array $startingXI,
        array $captainRecommendation,
        array $transferRecommendations,
        array $gameweekDecision,
        array $chipRecommendations
    ) {

        /*
         * ========================================================
         * GAMEWEEK
         * ========================================================
         */

        if (
            $gameweek <= 0
        ) {

            throw new InvalidArgumentException(
                'Recommendation candidate requires '
                . 'a positive gameweek ID.'
            );
        }


        /*
         * ========================================================
         * ENTRY
         * ========================================================
         */

        if (
            $entryId <= 0
        ) {

            throw new InvalidArgumentException(
                'Recommendation candidate requires '
                . 'a positive FPL entry ID.'
            );
        }


        /*
         * ========================================================
         * GENERATED TIMESTAMP
         * ========================================================
         */

        $generatedTimestamp =
            strtotime(
                $generatedAt
            );


        if (
            $generatedTimestamp === false
        ) {

            throw new InvalidArgumentException(
                'Recommendation candidate requires '
                . 'a valid generated timestamp.'
            );
        }


        /*
         * ========================================================
         * DEADLINE TIMESTAMP
         * ========================================================
         */

        $deadlineTimestamp =
            strtotime(
                $deadlineTime
            );


        if (
            $deadlineTimestamp === false
        ) {

            throw new InvalidArgumentException(
                'Recommendation candidate requires '
                . 'a valid deadline timestamp.'
            );
        }


        /*
         * ========================================================
         * PRE-DEADLINE REQUIREMENT
         * ========================================================
         *
         * A recommendation candidate is meaningful only when it
         * represents intelligence generated before the relevant
         * gameweek deadline.
         *
         * Generation at or after the deadline is rejected.
         */

        if (
            $generatedTimestamp
            >=
            $deadlineTimestamp
        ) {

            throw new InvalidArgumentException(
                'Recommendation candidate must be '
                . 'generated before the gameweek deadline.'
            );
        }


        /*
         * ========================================================
         * PRESERVE EVIDENCE
         * ========================================================
         *
         * Recommendation evidence is accepted exactly as produced
         * by the existing intelligence architecture.
         *
         * This domain object deliberately does not validate,
         * recalculate or reinterpret those recommendation models.
         */

        $this->gameweek =
            $gameweek;


        $this->entryId =
            $entryId;


        $this->generatedAt =
            $generatedAt;


        $this->deadlineTime =
            $deadlineTime;


        $this->playerProjections =
            $playerProjections;


        $this->startingXI =
            $startingXI;


        $this->captainRecommendation =
            $captainRecommendation;


        $this->transferRecommendations =
            $transferRecommendations;


        $this->gameweekDecision =
            $gameweekDecision;


        $this->chipRecommendations =
            $chipRecommendations;
    }


    /*
     * ============================================================
     * GETTERS
     * ============================================================
     */

    public function getGameweek(): int
    {
        return
            $this->gameweek;
    }


    public function getEntryId(): int
    {
        return
            $this->entryId;
    }


    public function getGeneratedAt(): string
    {
        return
            $this->generatedAt;
    }


    public function getDeadlineTime(): string
    {
        return
            $this->deadlineTime;
    }


    public function getPlayerProjections(): array
    {
        return
            $this->playerProjections;
    }


    public function getStartingXI(): array
    {
        return
            $this->startingXI;
    }


    public function getCaptainRecommendation(): array
    {
        return
            $this->captainRecommendation;
    }


    public function getTransferRecommendations(): array
    {
        return
            $this->transferRecommendations;
    }


    public function getGameweekDecision(): array
    {
        return
            $this->gameweekDecision;
    }


    public function getChipRecommendations(): array
    {
        return
            $this->chipRecommendations;
    }


    /*
     * ============================================================
     * EXPORT
     * ============================================================
     */

    public function toArray(): array
    {
        return [

            'gameweek' =>
                $this->gameweek,

            'entry_id' =>
                $this->entryId,

            'generated_at' =>
                $this->generatedAt,

            'deadline_time' =>
                $this->deadlineTime,

            'player_projections' =>
                $this->playerProjections,

            'starting_xi' =>
                $this->startingXI,

            'captain_recommendation' =>
                $this->captainRecommendation,

            'transfer_recommendations' =>
                $this->transferRecommendations,

            'gameweek_decision' =>
                $this->gameweekDecision,

            'chip_recommendations' =>
                $this->chipRecommendations
        ];
    }
}