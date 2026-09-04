<?php

class RecommendationSnapshot
{
    /*
     * ============================================================
     * SNAPSHOT IDENTITY
     * ============================================================
     */

    private int $gameweek;

    private int $entryId;

    private string $capturedAt;

    private string $deadlineTime;


    /*
     * ============================================================
     * PRESERVED RECOMMENDATION EVIDENCE
     * ============================================================
     *
     * These arrays preserve the outputs produced by the existing
     * FPL Intelligence systems at snapshot time.
     *
     * RecommendationSnapshot does not recalculate, reinterpret or
     * normalise their contents.
     */

    private array $playerProjections;

    private array $startingXI;

    private array $captainRecommendation;

    private array $transferRecommendations;

    private array $gameweekDecision;

    private array $chipRecommendations;


    /*
     * ============================================================
     * CONSTRUCTOR
     * ============================================================
     */

    public function __construct(
        int $gameweek,
        int $entryId,
        string $capturedAt,
        string $deadlineTime,
        array $playerProjections,
        array $startingXI,
        array $captainRecommendation,
        array $transferRecommendations,
        array $gameweekDecision,
        array $chipRecommendations
    ) {

        /*
         * --------------------------------------------------------
         * VALIDATE SNAPSHOT IDENTITY
         * --------------------------------------------------------
         */

        if (
            $gameweek <= 0
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot gameweek must be positive.'
            );
        }


        if (
            $entryId <= 0
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot FPL entry ID must be positive.'
            );
        }


        /*
         * --------------------------------------------------------
         * VALIDATE CAPTURE TIME
         * --------------------------------------------------------
         */

        $captureTimestamp =
            $this->parseTimestamp(
                $capturedAt,
                'capture timestamp'
            );


        /*
         * --------------------------------------------------------
         * VALIDATE DEADLINE TIME
         * --------------------------------------------------------
         */

        $deadlineTimestamp =
            $this->parseTimestamp(
                $deadlineTime,
                'deadline timestamp'
            );


        /*
         * --------------------------------------------------------
         * VALIDATE HISTORICAL BOUNDARY
         * --------------------------------------------------------
         *
         * A recommendation snapshot represents intelligence that
         * existed before the FPL deadline.
         *
         * Capturing at or after the deadline would no longer be a
         * valid pre-deadline recommendation snapshot.
         */

        if (
            $captureTimestamp
            >=
            $deadlineTimestamp
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot must be captured before the gameweek deadline.'
            );
        }


        /*
         * --------------------------------------------------------
         * PRESERVE SNAPSHOT STATE
         * --------------------------------------------------------
         */

        $this->gameweek =
            $gameweek;


        $this->entryId =
            $entryId;


        $this->capturedAt =
            $capturedAt;


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
     * SNAPSHOT IDENTITY
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


    public function getCapturedAt(): string
    {
        return
            $this->capturedAt;
    }


    public function getDeadlineTime(): string
    {
        return
            $this->deadlineTime;
    }


    /*
     * ============================================================
     * PRESERVED RECOMMENDATION EVIDENCE
     * ============================================================
     */

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

            'captured_at' =>
                $this->capturedAt,

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


    /*
     * ============================================================
     * TIMESTAMP VALIDATION
     * ============================================================
     */

    private function parseTimestamp(
        string $value,
        string $fieldName
    ): int {

        $value =
            trim(
                $value
            );


        if (
            $value === ''
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot '
                . $fieldName
                . ' is required.'
            );
        }


        $timestamp =
            strtotime(
                $value
            );


        if (
            $timestamp === false
        ) {

            throw new InvalidArgumentException(
                'Recommendation snapshot '
                . $fieldName
                . ' is invalid.'
            );
        }


        return
            $timestamp;
    }
}