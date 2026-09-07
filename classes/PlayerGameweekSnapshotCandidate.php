<?php

/**
 * Represents one player's latest pre-deadline
 * gameweek snapshot candidate.
 *
 * A candidate is mutable staging evidence.
 *
 * It preserves live player state before the relevant
 * gameweek deadline without requiring fixture history.
 *
 * Promotion into player_gameweek_snapshots happens
 * separately.
 */
class PlayerGameweekSnapshotCandidate
{
    private int $gameweekId;

    private string $generatedAt;

    private string $deadlineTime;

    private array $playerState;


    public function __construct(
        int $gameweekId,
        string $generatedAt,
        string $deadlineTime,
        array $playerState
    ) {

        /*
         * ========================================================
         * GAMEWEEK
         * ========================================================
         */

        if (
            $gameweekId <= 0
        ) {

            throw new InvalidArgumentException(
                'Player gameweek snapshot candidate requires '
                . 'a positive gameweek ID.'
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
                'Player gameweek snapshot candidate requires '
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
                'Player gameweek snapshot candidate requires '
                . 'a valid deadline timestamp.'
            );
        }


        /*
         * ========================================================
         * PRE-DEADLINE REQUIREMENT
         * ========================================================
         */

        if (
            $generatedTimestamp
            >=
            $deadlineTimestamp
        ) {

            throw new InvalidArgumentException(
                'Player gameweek snapshot candidate must be '
                . 'generated before the gameweek deadline.'
            );
        }


        /*
         * ========================================================
         * PLAYER IDENTITY
         * ========================================================
         */

        $playerId =
            (int) (
                $playerState[
                    'player_id'
                ]
                ?? 0
            );


        if (
            $playerId <= 0
        ) {

            throw new InvalidArgumentException(
                'Player gameweek snapshot candidate requires '
                . 'a positive local player ID.'
            );
        }


        $fplPlayerId =
            (int) (
                $playerState[
                    'fpl_player_id'
                ]
                ?? 0
            );


        if (
            $fplPlayerId <= 0
        ) {

            throw new InvalidArgumentException(
                'Player gameweek snapshot candidate requires '
                . 'a positive FPL player ID.'
            );
        }


        $teamId =
            (int) (
                $playerState[
                    'team_id'
                ]
                ?? 0
            );


        if (
            $teamId <= 0
        ) {

            throw new InvalidArgumentException(
                'Player gameweek snapshot candidate requires '
                . 'a positive team ID.'
            );
        }


        /*
         * ========================================================
         * PRESERVE EVIDENCE
         * ========================================================
         */

        $this->gameweekId =
            $gameweekId;


        $this->generatedAt =
            $generatedAt;


        $this->deadlineTime =
            $deadlineTime;


        $this->playerState =
            $playerState;
    }


    /*
     * ============================================================
     * GETTERS
     * ============================================================
     */

    public function getGameweekId(): int
    {
        return
            $this->gameweekId;
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


    public function getPlayerId(): int
    {
        return
            (int) $this->playerState[
                'player_id'
            ];
    }


    public function getFplPlayerId(): int
    {
        return
            (int) $this->playerState[
                'fpl_player_id'
            ];
    }


    public function getTeamId(): int
    {
        return
            (int) $this->playerState[
                'team_id'
            ];
    }


    public function getPlayerState(): array
    {
        return
            $this->playerState;
    }


    /*
     * ============================================================
     * EXPORT
     * ============================================================
     */

    public function toArray(): array
    {
        return [

            'gameweek_id' =>
                $this->gameweekId,

            'generated_at' =>
                $this->generatedAt,

            'deadline_time' =>
                $this->deadlineTime,

            'player_state' =>
                $this->playerState
        ];
    }
}