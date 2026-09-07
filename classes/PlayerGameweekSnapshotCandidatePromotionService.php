<?php

/**
 * PlayerGameweekSnapshotCandidatePromotionService
 *
 * Promotes one mutable pre-deadline player snapshot candidate
 * into immutable player/gameweek snapshot history.
 *
 * This service does not:
 *
 * - query current live player data
 * - query fixture history
 * - reconstruct historical state
 * - recalculate player evidence
 * - update existing immutable snapshots
 * - delete candidate staging evidence
 *
 * Its responsibility is only to preserve the candidate's
 * captured player state in PlayerGameweekSnapshotRepository.
 */
class PlayerGameweekSnapshotCandidatePromotionService
{
    private object $candidateRepository;


    private object $snapshotRepository;


    public function __construct(
        object $candidateRepository,
        object $snapshotRepository
    ) {

        $this->candidateRepository =
            $candidateRepository;


        $this->snapshotRepository =
            $snapshotRepository;
    }


    /**
     * Promote one player's latest candidate for one gameweek.
     *
     * Returns:
     *
     * true
     *     A new immutable snapshot was inserted.
     *
     * false
     *     No candidate exists, an immutable snapshot already
     *     exists, or insertIfAbsent() prevented a duplicate.
     */
    public function promote(
        int $playerId,
        int $gameweekId
    ): bool {

        /*
         * ========================================================
         * VALIDATE IDENTITIES
         * ========================================================
         */

        if (
            $playerId <= 0
        ) {

            throw new InvalidArgumentException(
                'Player ID must be positive.'
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
         * ========================================================
         * PROTECT EXISTING IMMUTABLE HISTORY
         * ========================================================
         *
         * Avoid doing any candidate work when this player/gameweek
         * already has immutable historical evidence.
         *
         * insertIfAbsent() remains the final safeguard against a
         * race between this lookup and the eventual insert.
         */

        $existingSnapshot =
            $this
                ->snapshotRepository
                ->getByPlayerAndGameweek(
                    $playerId,
                    $gameweekId
                );


        if (
            $existingSnapshot !== null
        ) {

            return false;
        }


        /*
         * ========================================================
         * LOAD PRE-DEADLINE CANDIDATE
         * ========================================================
         */

        $candidate =
            $this
                ->candidateRepository
                ->getByPlayerAndGameweek(
                    $playerId,
                    $gameweekId
                );


        if (
            $candidate === null
        ) {

            return false;
        }


        /*
         * ========================================================
         * PROTECT CANDIDATE IDENTITY
         * ========================================================
         *
         * The repository query should already guarantee these
         * identities, but historical evidence should never be
         * written from a mismatched candidate.
         */

        $candidatePlayerId =
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


        if (
            $candidatePlayerId
            !==
            $playerId
        ) {

            throw new RuntimeException(
                'Player snapshot candidate player identity does '
                . 'not match the requested player.'
            );
        }


        $candidateGameweekId =
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
            $candidateGameweekId
            !==
            $gameweekId
        ) {

            throw new RuntimeException(
                'Player snapshot candidate gameweek identity does '
                . 'not match the requested gameweek.'
            );
        }


        /*
         * ========================================================
         * LOAD CAPTURED PLAYER STATE
         * ========================================================
         */

        $playerState =
            $candidate[
                'player_state'
            ]
            ?? null;


        if (
            !is_array(
                $playerState
            )
        ) {

            throw new RuntimeException(
                'Player snapshot candidate does not contain valid '
                . 'player-state evidence.'
            );
        }


        /*
         * Protect the identity contained inside the historical
         * evidence as well as the candidate's top-level identity.
         */

        $statePlayerId =
            is_numeric(
                $playerState[
                    'player_id'
                ]
                ?? null
            )
                ? (int) $playerState[
                    'player_id'
                ]
                : 0;


        if (
            $statePlayerId
            !==
            $playerId
        ) {

            throw new RuntimeException(
                'Player snapshot candidate state does not match '
                . 'the requested player.'
            );
        }


        /*
         * ========================================================
         * BUILD EXISTING SNAPSHOT CONTRACT
         * ========================================================
         *
         * player_gameweek_snapshots does not contain candidate
         * lifecycle metadata.
         *
         * Therefore:
         *
         * - generated_at is not copied
         * - deadline_time is not copied
         * - candidate created_at is not copied
         * - candidate updated_at is not copied
         *
         * The candidate's captured player state is preserved
         * unchanged and only the local gameweek identity is added.
         */

        $snapshot =
            $playerState;


        $snapshot[
            'gameweek_id'
        ] =
            $gameweekId;


        /*
         * ========================================================
         * IMMUTABLE INSERT
         * ========================================================
         *
         * Never call upsert() here.
         *
         * insertIfAbsent() is the final historical immutability
         * boundary. If another process inserted the same snapshot
         * after our initial lookup, that original row wins.
         */

        return
            $this
                ->snapshotRepository
                ->insertIfAbsent(
                    $snapshot
                );
    }
}