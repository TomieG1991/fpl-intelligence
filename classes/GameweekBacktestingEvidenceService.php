<?php

class GameweekBacktestingEvidenceService
{
    private object $gameweekRepository;

    private object $availability;

    private object $snapshotRepository;

    private object $outcomeService;


    public function __construct(
        object $gameweekRepository,
        object $availability,
        object $snapshotRepository,
        object $outcomeService
    ) {

        $this->gameweekRepository =
            $gameweekRepository;


        $this->availability =
            $availability;


        $this->snapshotRepository =
            $snapshotRepository;


        $this->outcomeService =
            $outcomeService;
    }


    public function getEvidence(
        int $entryId,
        int $gameweekId
    ): array {

        /*
         * ====================================================
         * VALIDATE REQUEST IDENTITY
         * ====================================================
         */

        if ($entryId <= 0) {

            throw new InvalidArgumentException(
                'Entry ID must be a positive integer.'
            );
        }


        if ($gameweekId <= 0) {

            throw new InvalidArgumentException(
                'Gameweek ID must be a positive integer.'
            );
        }


        /*
         * ====================================================
         * LOAD GAMEWEEK
         * ====================================================
         */

        $gameweek =
            $this->gameweekRepository
                ->getById(
                    $gameweekId
                );


        if ($gameweek === null) {

            return [

                'status' =>
                    'Unavailable',

                'reason' =>
                    'Gameweek does not exist',

                'entry_id' =>
                    $entryId,

                'gameweek_id' =>
                    $gameweekId,

                'gameweek' =>
                    null,

                'recommendation_snapshot' =>
                    null,

                'player_outcomes' =>
                    []
            ];
        }


        /*
         * ====================================================
         * CHECK OUTCOME AVAILABILITY
         * ====================================================
         */

        if (
            !$this->availability
                ->isAvailable(
                    $gameweek
                )
        ) {

            return [

                'status' =>
                    'Unavailable',

                'reason' =>
                    'Gameweek outcomes are not yet authoritative',

                'entry_id' =>
                    $entryId,

                'gameweek_id' =>
                    $gameweekId,

                'gameweek' =>
                    $gameweek,

                'recommendation_snapshot' =>
                    null,

                'player_outcomes' =>
                    []
            ];
        }


        /*
         * ====================================================
         * LOAD IMMUTABLE RECOMMENDATION SNAPSHOT
         * ====================================================
         */

        $snapshot =
            $this->snapshotRepository
                ->getByEntryAndGameweek(
                    $entryId,
                    $gameweekId
                );


        if ($snapshot === null) {

            return [

                'status' =>
                    'Incomplete',

                'reason' =>
                    'Recommendation snapshot is unavailable',

                'entry_id' =>
                    $entryId,

                'gameweek_id' =>
                    $gameweekId,

                'gameweek' =>
                    $gameweek,

                'recommendation_snapshot' =>
                    null,

                'player_outcomes' =>
                    []
            ];
        }


        /*
         * ====================================================
         * LOAD REALISED PLAYER OUTCOMES
         * ====================================================
         */

        $playerOutcomes =
            $this->outcomeService
                ->getByGameweekId(
                    $gameweekId
                );


        if (empty($playerOutcomes)) {

            return [

                'status' =>
                    'Incomplete',

                'reason' =>
                    'Player outcome evidence is unavailable',

                'entry_id' =>
                    $entryId,

                'gameweek_id' =>
                    $gameweekId,

                'gameweek' =>
                    $gameweek,

                'recommendation_snapshot' =>
                    $snapshot,

                'player_outcomes' =>
                    []
            ];
        }


        /*
         * ====================================================
         * READY FOR BACKTESTING
         * ====================================================
         *
         * This service only assembles authoritative evidence.
         *
         * It does not calculate recommendation success,
         * projection accuracy, captain performance,
         * transfer performance or any synthetic score.
         */

        return [

            'status' =>
                'Ready',

            'reason' =>
                null,

            'entry_id' =>
                $entryId,

            'gameweek_id' =>
                $gameweekId,

            'gameweek' =>
                $gameweek,

            'recommendation_snapshot' =>
                $snapshot,

            'player_outcomes' =>
                $playerOutcomes
        ];
    }
}