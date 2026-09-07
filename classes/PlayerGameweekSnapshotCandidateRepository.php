<?php

/**
 * PlayerGameweekSnapshotCandidateRepository
 *
 * Persists the latest pre-deadline player-state candidate
 * for each player/gameweek.
 *
 * Player snapshot candidates are mutable staging evidence.
 *
 * For one player/gameweek:
 *
 * - the first candidate is inserted
 * - a strictly newer candidate replaces the existing candidate
 * - an older candidate cannot replace newer evidence
 * - an equal-timestamp candidate cannot replace existing evidence
 *
 * This is deliberately different from player_gameweek_snapshots,
 * which represents immutable historical player-state evidence.
 *
 * Fixture history is not used to determine candidate eligibility.
 */
class PlayerGameweekSnapshotCandidateRepository
{
    private PDO $db;


    public function __construct(
        PDO $db
    ) {

        $this->db =
            $db;
    }


    /*
     * ============================================================
     * GET BY PLAYER AND GAMEWEEK
     * ============================================================
     */

    public function getByPlayerAndGameweek(
        int $playerId,
        int $gameweekId
    ): ?array {

        $statement =
            $this->db
                ->prepare(
                    "
                    SELECT
                        *
                    FROM
                        player_gameweek_snapshot_candidates
                    WHERE
                        player_id = :player_id
                        AND
                        gameweek_id = :gameweek_id
                    LIMIT 1
                    "
                );


        $statement
            ->execute(
                [
                    'player_id' =>
                        $playerId,

                    'gameweek_id' =>
                        $gameweekId
                ]
            );


        $row =
            $statement
                ->fetch(
                    PDO::FETCH_ASSOC
                );


        if (
            $row === false
        ) {

            return null;
        }


        return
            $this->hydrateRow(
                $row
            );
    }


    /*
     * ============================================================
     * GET CANDIDATES READY FOR PROMOTION
     * ============================================================
     *
     * Promotion readiness is determined from the deadline
     * preserved with the candidate.
     *
     * Current gameweek state and fixture history are deliberately
     * not consulted here.
     */

    public function getReadyForPromotion(
        string $timestamp
    ): array {

        try {

            $readyAt =
                new DateTimeImmutable(
                    $timestamp
                );

        } catch (
            Throwable $exception
        ) {

            throw new InvalidArgumentException(
                'Promotion readiness timestamp must be a valid date/time.',
                0,
                $exception
            );
        }


        $normalisedTimestamp =
            $readyAt
                ->format(
                    'Y-m-d H:i:s'
                );


        $statement =
            $this->db
                ->prepare(
                    "
                    SELECT
                        *
                    FROM
                        player_gameweek_snapshot_candidates
                    WHERE
                        deadline_time <= :ready_at
                    ORDER BY
                        deadline_time ASC,
                        gameweek_id ASC,
                        player_id ASC
                    "
                );


        $statement
            ->bindValue(
                ':ready_at',
                $normalisedTimestamp,
                PDO::PARAM_STR
            );


        $statement
            ->execute();


        $rows =
            $statement
                ->fetchAll(
                    PDO::FETCH_ASSOC
                );


        return
            array_map(
                function (
                    array $row
                ): array {

                    return
                        $this->hydrateRow(
                            $row
                        );
                },
                $rows
            );
    }


    /*
     * ============================================================
     * SAVE LATEST
     * ============================================================
     */

    public function saveLatest(
        int $gameweekId,
        PlayerGameweekSnapshotCandidate $candidate
    ): bool {

        if (
            $gameweekId <= 0
        ) {

            throw new InvalidArgumentException(
                'Gameweek ID must be positive.'
            );
        }


        if (
            $candidate->getGameweekId()
            !==
            $gameweekId
        ) {

            throw new InvalidArgumentException(
                'Candidate gameweek does not match '
                . 'repository gameweek ID.'
            );
        }


        $existing =
            $this->getByPlayerAndGameweek(
                $candidate->getPlayerId(),
                $gameweekId
            );


        /*
         * --------------------------------------------------------
         * FIRST CANDIDATE
         * --------------------------------------------------------
         */

        if (
            $existing === null
        ) {

            return
                $this->insert(
                    $gameweekId,
                    $candidate
                );
        }


        /*
         * --------------------------------------------------------
         * COMPARE GENERATED TIMESTAMPS
         * --------------------------------------------------------
         */

        $existingGeneratedTimestamp =
            strtotime(
                (string) $existing[
                    'generated_at'
                ]
            );


        $candidateGeneratedTimestamp =
            strtotime(
                $candidate->getGeneratedAt()
            );


        if (
            $existingGeneratedTimestamp === false
            ||
            $candidateGeneratedTimestamp === false
        ) {

            throw new RuntimeException(
                'Player gameweek snapshot candidate '
                . 'generated timestamp could not be compared.'
            );
        }


        /*
         * Only strictly newer evidence may replace the
         * existing candidate.
         *
         * Older and equal timestamps leave the current
         * candidate untouched.
         */

        if (
            $candidateGeneratedTimestamp
            <=
            $existingGeneratedTimestamp
        ) {

            return false;
        }


        return
            $this->replace(
                $gameweekId,
                $candidate
            );
    }


    /*
     * ============================================================
     * INSERT
     * ============================================================
     */

    private function insert(
        int $gameweekId,
        PlayerGameweekSnapshotCandidate $candidate
    ): bool {

        $parameters =
            $this->buildPersistenceParameters(
                $gameweekId,
                $candidate
            );


        $statement =
            $this->db
                ->prepare(
                    "
                    INSERT INTO
                        player_gameweek_snapshot_candidates
                    (
                        gameweek_id,
                        player_id,
                        fpl_player_id,
                        team_id,
                        generated_at,
                        deadline_time,
                        position,
                        price,
                        selected,
                        selected_by_percent,
                        chance_of_playing,
                        status,
                        news,
                        minutes,
                        goals,
                        assists,
                        clean_sheets,
                        bonus,
                        bps,
                        ict_index,
                        expected_goals,
                        expected_assists,
                        expected_goal_involvements
                    )
                    VALUES
                    (
                        :gameweek_id,
                        :player_id,
                        :fpl_player_id,
                        :team_id,
                        :generated_at,
                        :deadline_time,
                        :position,
                        :price,
                        :selected,
                        :selected_by_percent,
                        :chance_of_playing,
                        :status,
                        :news,
                        :minutes,
                        :goals,
                        :assists,
                        :clean_sheets,
                        :bonus,
                        :bps,
                        :ict_index,
                        :expected_goals,
                        :expected_assists,
                        :expected_goal_involvements
                    )
                    "
                );


        $statement
            ->execute(
                $parameters
            );


        return
            $statement->rowCount()
            ===
            1;
    }


    /*
     * ============================================================
     * REPLACE
     * ============================================================
     */

    private function replace(
        int $gameweekId,
        PlayerGameweekSnapshotCandidate $candidate
    ): bool {

        $parameters =
            $this->buildPersistenceParameters(
                $gameweekId,
                $candidate
            );


        $parameters[
            'existing_player_id'
        ] =
            $candidate->getPlayerId();


        $parameters[
            'existing_gameweek_id'
        ] =
            $gameweekId;


        /*
         * The UPDATE query does not use these identity
         * placeholders from the INSERT parameter set.
         */

        unset(
            $parameters[
                'gameweek_id'
            ],
            $parameters[
                'player_id'
            ]
        );


        $statement =
            $this->db
                ->prepare(
                    "
                    UPDATE
                        player_gameweek_snapshot_candidates
                    SET
                        fpl_player_id =
                            :fpl_player_id,

                        team_id =
                            :team_id,

                        generated_at =
                            :generated_at,

                        deadline_time =
                            :deadline_time,

                        position =
                            :position,

                        price =
                            :price,

                        selected =
                            :selected,

                        selected_by_percent =
                            :selected_by_percent,

                        chance_of_playing =
                            :chance_of_playing,

                        status =
                            :status,

                        news =
                            :news,

                        minutes =
                            :minutes,

                        goals =
                            :goals,

                        assists =
                            :assists,

                        clean_sheets =
                            :clean_sheets,

                        bonus =
                            :bonus,

                        bps =
                            :bps,

                        ict_index =
                            :ict_index,

                        expected_goals =
                            :expected_goals,

                        expected_assists =
                            :expected_assists,

                        expected_goal_involvements =
                            :expected_goal_involvements

                    WHERE
                        player_id =
                            :existing_player_id

                        AND

                        gameweek_id =
                            :existing_gameweek_id
                    "
                );


        $statement
            ->execute(
                $parameters
            );


        return
            $statement->rowCount()
            ===
            1;
    }


    /*
     * ============================================================
     * BUILD PERSISTENCE PARAMETERS
     * ============================================================
     */

    private function buildPersistenceParameters(
        int $gameweekId,
        PlayerGameweekSnapshotCandidate $candidate
    ): array {

        $state =
            $candidate->getPlayerState();


        return [

            'gameweek_id' =>
                $gameweekId,

            'player_id' =>
                $candidate->getPlayerId(),

            'fpl_player_id' =>
                $candidate->getFplPlayerId(),

            'team_id' =>
                $candidate->getTeamId(),

            'generated_at' =>
                $candidate->getGeneratedAt(),

            'deadline_time' =>
                $candidate->getDeadlineTime(),

            'position' =>
                $state[
                    'position'
                ]
                ?? null,

            'price' =>
                $state[
                    'price'
                ]
                ?? null,

            'selected' =>
                $state[
                    'selected'
                ]
                ?? null,

            'selected_by_percent' =>
                $state[
                    'selected_by_percent'
                ]
                ?? null,

            'chance_of_playing' =>
                $state[
                    'chance_of_playing'
                ]
                ?? null,

            'status' =>
                $state[
                    'status'
                ]
                ?? null,

            'news' =>
                $state[
                    'news'
                ]
                ?? null,

            'minutes' =>
                $state[
                    'minutes'
                ]
                ?? 0,

            'goals' =>
                $state[
                    'goals'
                ]
                ?? 0,

            'assists' =>
                $state[
                    'assists'
                ]
                ?? 0,

            'clean_sheets' =>
                $state[
                    'clean_sheets'
                ]
                ?? 0,

            'bonus' =>
                $state[
                    'bonus'
                ]
                ?? 0,

            'bps' =>
                $state[
                    'bps'
                ]
                ?? 0,

            'ict_index' =>
                $state[
                    'ict_index'
                ]
                ?? null,

            'expected_goals' =>
                $state[
                    'expected_goals'
                ]
                ?? null,

            'expected_assists' =>
                $state[
                    'expected_assists'
                ]
                ?? null,

            'expected_goal_involvements' =>
                $state[
                    'expected_goal_involvements'
                ]
                ?? null
        ];
    }


    /*
     * ============================================================
     * HYDRATE ROW
     * ============================================================
     */

    private function hydrateRow(
        array $row
    ): array {

        $playerState = [

            'player_id' =>
                (int) $row[
                    'player_id'
                ],

            'fpl_player_id' =>
                (int) $row[
                    'fpl_player_id'
                ],

            'team_id' =>
                (int) $row[
                    'team_id'
                ],

            'position' =>
                $row[
                    'position'
                ],

            'price' =>
                $row[
                    'price'
                ] !== null
                    ? (float) $row[
                        'price'
                    ]
                    : null,

            'selected' =>
                $row[
                    'selected'
                ] !== null
                    ? (int) $row[
                        'selected'
                    ]
                    : null,

            'selected_by_percent' =>
                $row[
                    'selected_by_percent'
                ] !== null
                    ? (float) $row[
                        'selected_by_percent'
                    ]
                    : null,

            'chance_of_playing' =>
                $row[
                    'chance_of_playing'
                ] !== null
                    ? (int) $row[
                        'chance_of_playing'
                    ]
                    : null,

            'status' =>
                $row[
                    'status'
                ],

            'news' =>
                $row[
                    'news'
                ],

            'minutes' =>
                (int) $row[
                    'minutes'
                ],

            'goals' =>
                (int) $row[
                    'goals'
                ],

            'assists' =>
                (int) $row[
                    'assists'
                ],

            'clean_sheets' =>
                (int) $row[
                    'clean_sheets'
                ],

            'bonus' =>
                (int) $row[
                    'bonus'
                ],

            'bps' =>
                (int) $row[
                    'bps'
                ],

            'ict_index' =>
                $row[
                    'ict_index'
                ] !== null
                    ? (float) $row[
                        'ict_index'
                    ]
                    : null,

            'expected_goals' =>
                $row[
                    'expected_goals'
                ] !== null
                    ? (float) $row[
                        'expected_goals'
                    ]
                    : null,

            'expected_assists' =>
                $row[
                    'expected_assists'
                ] !== null
                    ? (float) $row[
                        'expected_assists'
                    ]
                    : null,

            'expected_goal_involvements' =>
                $row[
                    'expected_goal_involvements'
                ] !== null
                    ? (float) $row[
                        'expected_goal_involvements'
                    ]
                    : null
        ];


        return [

            'id' =>
                (int) $row[
                    'id'
                ],

            'gameweek_id' =>
                (int) $row[
                    'gameweek_id'
                ],

            'player_id' =>
                (int) $row[
                    'player_id'
                ],

            'fpl_player_id' =>
                (int) $row[
                    'fpl_player_id'
                ],

            'team_id' =>
                (int) $row[
                    'team_id'
                ],

            'generated_at' =>
                (string) $row[
                    'generated_at'
                ],

            'deadline_time' =>
                (string) $row[
                    'deadline_time'
                ],

            'player_state' =>
                $playerState,

            'created_at' =>
                (string) $row[
                    'created_at'
                ],

            'updated_at' =>
                (string) $row[
                    'updated_at'
                ]
        ];
    }
}