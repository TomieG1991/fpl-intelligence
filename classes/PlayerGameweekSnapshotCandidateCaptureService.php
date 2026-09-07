<?php

/**
 * PlayerGameweekSnapshotCandidateCaptureService
 *
 * Converts the current live player pool into pre-deadline
 * PlayerGameweekSnapshotCandidate objects.
 *
 * This service does not:
 *
 * - query fixture history
 * - reconstruct historical player state
 * - persist candidates
 * - promote candidates into immutable snapshots
 *
 * It only maps the supplied live player state into candidate
 * evidence for the supplied pre-deadline gameweek.
 */
class PlayerGameweekSnapshotCandidateCaptureService
{
    /**
     * Build one candidate for each valid player in the supplied
     * live player pool.
     *
     * Players without valid relational identities are skipped.
     *
     * @return PlayerGameweekSnapshotCandidate[]
     */
    public function buildCandidates(
        int $gameweekId,
        string $generatedAt,
        string $deadlineTime,
        array $players
    ): array {

        /*
         * ====================================================
         * VALIDATE GAMEWEEK
         * ====================================================
         */

        if (
            $gameweekId <= 0
        ) {

            throw new InvalidArgumentException(
                'Gameweek ID must be positive.'
            );
        }


        /*
         * ====================================================
         * VALIDATE PRE-DEADLINE LIFECYCLE
         * ====================================================
         *
         * Validate this once before processing the player pool.
         *
         * PlayerGameweekSnapshotCandidate also protects the
         * boundary for every individual candidate.
         */

        try {

            $generatedDate =
                new DateTimeImmutable(
                    $generatedAt
                );

        } catch (
            Throwable $exception
        ) {

            throw new InvalidArgumentException(
                'Generated timestamp must be a valid date/time.',
                0,
                $exception
            );
        }


        try {

            $deadlineDate =
                new DateTimeImmutable(
                    $deadlineTime
                );

        } catch (
            Throwable $exception
        ) {

            throw new InvalidArgumentException(
                'Deadline timestamp must be a valid date/time.',
                0,
                $exception
            );
        }


        if (
            $generatedDate
            >=
            $deadlineDate
        ) {

            throw new InvalidArgumentException(
                'Player snapshot candidates must be generated '
                . 'strictly before the gameweek deadline.'
            );
        }


        /*
         * ====================================================
         * BUILD CANDIDATES
         * ====================================================
         */

        $candidates =
            [];


        foreach (
            $players
            as $player
        ) {

            if (
                !is_array(
                    $player
                )
            ) {

                continue;
            }


            $playerId =
                (int) (
                    $player[
                        'id'
                    ]
                    ?? 0
                );


            $fplPlayerId =
                (int) (
                    $player[
                        'fpl_player_id'
                    ]
                    ?? 0
                );


            $teamId =
                (int) (
                    $player[
                        'team_id'
                    ]
                    ?? 0
                );


            /*
             * A candidate without valid relational identities
             * cannot later become a valid immutable snapshot.
             */
            if (
                $playerId <= 0
                ||
                $fplPlayerId <= 0
                ||
                $teamId <= 0
            ) {

                continue;
            }


            /*
             * =================================================
             * LIVE PLAYER STATE
             * =================================================
             *
             * Preserve only evidence genuinely available from
             * the live players table.
             *
             * Raw selected-manager count is deliberately null.
             * The live players table does not contain that field,
             * so it must not be reconstructed later from fixture
             * history or manufactured here.
             */

            $playerState = [

                'player_id' =>
                    $playerId,

                'fpl_player_id' =>
                    $fplPlayerId,

                'team_id' =>
                    $teamId,

                'position' =>
                    $player[
                        'position'
                    ]
                    ?? null,

                'price' =>
                    $this->numericOrNull(
                        $player[
                            'price'
                        ]
                        ?? null
                    ),

                'selected' =>
                    null,

                'selected_by_percent' =>
                    $this->numericOrNull(
                        $player[
                            'selected_by_percent'
                        ]
                        ?? null
                    ),

                'chance_of_playing' =>
                    $this->integerOrNull(
                        $player[
                            'chance_of_playing'
                        ]
                        ?? null
                    ),

                'status' =>
                    $player[
                        'status'
                    ]
                    ?? null,

                'news' =>
                    $player[
                        'news'
                    ]
                    ?? null,

                'minutes' =>
                    (int) (
                        $player[
                            'minutes'
                        ]
                        ?? 0
                    ),

                'goals' =>
                    (int) (
                        $player[
                            'goals'
                        ]
                        ?? 0
                    ),

                'assists' =>
                    (int) (
                        $player[
                            'assists'
                        ]
                        ?? 0
                    ),

                'clean_sheets' =>
                    (int) (
                        $player[
                            'clean_sheets'
                        ]
                        ?? 0
                    ),

                'bonus' =>
                    (int) (
                        $player[
                            'bonus'
                        ]
                        ?? 0
                    ),

                'bps' =>
                    (int) (
                        $player[
                            'bps'
                        ]
                        ?? 0
                    ),

                'ict_index' =>
                    $this->numericOrNull(
                        $player[
                            'ict_index'
                        ]
                        ?? null
                    ),

                'expected_goals' =>
                    $this->numericOrNull(
                        $player[
                            'expected_goals'
                        ]
                        ?? null
                    ),

                'expected_assists' =>
                    $this->numericOrNull(
                        $player[
                            'expected_assists'
                        ]
                        ?? null
                    ),

                'expected_goal_involvements' =>
                    $this->numericOrNull(
                        $player[
                            'expected_goal_involvements'
                        ]
                        ?? null
                    )
            ];


            $candidates[] =
                new PlayerGameweekSnapshotCandidate(
                    $gameweekId,
                    $generatedAt,
                    $deadlineTime,
                    $playerState
                );
        }


        return
            $candidates;
    }


    /*
     * ========================================================
     * NUMERIC OR NULL
     * ========================================================
     */

    private function numericOrNull(
        mixed $value
    ): ?float {

        if (
            $value === null
            ||
            $value === ''
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return
            (float) $value;
    }


    /*
     * ========================================================
     * INTEGER OR NULL
     * ========================================================
     */

    private function integerOrNull(
        mixed $value
    ): ?int {

        if (
            $value === null
            ||
            $value === ''
            ||
            !is_numeric(
                $value
            )
        ) {

            return null;
        }


        return
            (int) $value;
    }
}