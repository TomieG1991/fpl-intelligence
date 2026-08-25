<?php

class PlayerFixtureHistoryRepository
{
    private PDO $db;


    /**
     * Initialise the repository.
     */
    public function __construct(
        PDO $db
    ) {

        $this->db =
            $db;
    }


    /**
     * Insert or update one player's historical
     * performance for one fixture.
     *
     * The database unique constraint on:
     *
     *     player_id + fixture_id
     *
     * guarantees that repeated imports update the
     * existing historical row rather than creating
     * duplicates.
     */
    public function upsert(
        array $history
    ): void {

        $gameweekId =
            (int) (
                $history[
                    'gameweek_id'
                ]
                ?? 0
            );


        $playerId =
            (int) (
                $history[
                    'player_id'
                ]
                ?? 0
            );


        $fplPlayerId =
            (int) (
                $history[
                    'fpl_player_id'
                ]
                ?? 0
            );


        $fixtureId =
            (int) (
                $history[
                    'fixture_id'
                ]
                ?? 0
            );


        $fplFixtureId =
            (int) (
                $history[
                    'fpl_fixture_id'
                ]
                ?? 0
            );


        $teamId =
            (int) (
                $history[
                    'team_id'
                ]
                ?? 0
            );


        $opponentTeamId =
            (int) (
                $history[
                    'opponent_team_id'
                ]
                ?? 0
            );


        /*
         * These fields represent required relational
         * identity. Silently inserting zero here would
         * either violate foreign keys or create invalid
         * historical data.
         */
        if (
            $gameweekId <= 0
            ||
            $playerId <= 0
            ||
            $fplPlayerId <= 0
            ||
            $fixtureId <= 0
            ||
            $fplFixtureId <= 0
            ||
            $teamId <= 0
            ||
            $opponentTeamId <= 0
        ) {

            throw new InvalidArgumentException(
                'Player fixture history requires valid gameweek, player, fixture, team and opponent identifiers'
            );
        }


        $statement =
            $this->db
                ->prepare(
                    "
                    INSERT INTO
                        player_fixture_history
                    (
                        gameweek_id,
                        player_id,
                        fpl_player_id,
                        fixture_id,
                        fpl_fixture_id,
                        team_id,
                        opponent_team_id,
                        was_home,
                        total_points,
                        minutes,
                        starts,
                        goals,
                        assists,
                        expected_goals,
                        expected_assists,
                        expected_goal_involvements,
                        clean_sheets,
                        goals_conceded,
                        expected_goals_conceded,
                        saves,
                        penalties_saved,
                        clearances_blocks_interceptions,
                        recoveries,
                        tackles,
                        defensive_contribution,
                        own_goals,
                        penalties_missed,
                        yellow_cards,
                        red_cards,
                        bonus,
                        bps,
                        influence,
                        creativity,
                        threat,
                        ict_index,
                        price,
                        selected,
                        transfers_balance,
                        transfers_in,
                        transfers_out
                    )
                    VALUES
                    (
                        :gameweek_id,
                        :player_id,
                        :fpl_player_id,
                        :fixture_id,
                        :fpl_fixture_id,
                        :team_id,
                        :opponent_team_id,
                        :was_home,
                        :total_points,
                        :minutes,
                        :starts,
                        :goals,
                        :assists,
                        :expected_goals,
                        :expected_assists,
                        :expected_goal_involvements,
                        :clean_sheets,
                        :goals_conceded,
                        :expected_goals_conceded,
                        :saves,
                        :penalties_saved,
                        :clearances_blocks_interceptions,
                        :recoveries,
                        :tackles,
                        :defensive_contribution,
                        :own_goals,
                        :penalties_missed,
                        :yellow_cards,
                        :red_cards,
                        :bonus,
                        :bps,
                        :influence,
                        :creativity,
                        :threat,
                        :ict_index,
                        :price,
                        :selected,
                        :transfers_balance,
                        :transfers_in,
                        :transfers_out
                    )
                    ON DUPLICATE KEY UPDATE
                        gameweek_id =
                            VALUES(gameweek_id),

                        fpl_player_id =
                            VALUES(fpl_player_id),

                        fpl_fixture_id =
                            VALUES(fpl_fixture_id),

                        team_id =
                            VALUES(team_id),

                        opponent_team_id =
                            VALUES(opponent_team_id),

                        was_home =
                            VALUES(was_home),

                        total_points =
                            VALUES(total_points),

                        minutes =
                            VALUES(minutes),

                        starts =
                            VALUES(starts),

                        goals =
                            VALUES(goals),

                        assists =
                            VALUES(assists),

                        expected_goals =
                            VALUES(expected_goals),

                        expected_assists =
                            VALUES(expected_assists),

                        expected_goal_involvements =
                            VALUES(expected_goal_involvements),

                        clean_sheets =
                            VALUES(clean_sheets),

                        goals_conceded =
                            VALUES(goals_conceded),

                        expected_goals_conceded =
                            VALUES(expected_goals_conceded),

                        saves =
                            VALUES(saves),

                        penalties_saved =
                            VALUES(penalties_saved),

                        clearances_blocks_interceptions =
                            VALUES(clearances_blocks_interceptions),

                        recoveries =
                            VALUES(recoveries),

                        tackles =
                            VALUES(tackles),

                        defensive_contribution =
                            VALUES(defensive_contribution),

                        own_goals =
                            VALUES(own_goals),

                        penalties_missed =
                            VALUES(penalties_missed),

                        yellow_cards =
                            VALUES(yellow_cards),

                        red_cards =
                            VALUES(red_cards),

                        bonus =
                            VALUES(bonus),

                        bps =
                            VALUES(bps),

                        influence =
                            VALUES(influence),

                        creativity =
                            VALUES(creativity),

                        threat =
                            VALUES(threat),

                        ict_index =
                            VALUES(ict_index),

                        price =
                            VALUES(price),

                        selected =
                            VALUES(selected),

                        transfers_balance =
                            VALUES(transfers_balance),

                        transfers_in =
                            VALUES(transfers_in),

                        transfers_out =
                            VALUES(transfers_out)
                    "
                );


        $statement
            ->execute([

                ':gameweek_id' =>
                    $gameweekId,

                ':player_id' =>
                    $playerId,

                ':fpl_player_id' =>
                    $fplPlayerId,

                ':fixture_id' =>
                    $fixtureId,

                ':fpl_fixture_id' =>
                    $fplFixtureId,

                ':team_id' =>
                    $teamId,

                ':opponent_team_id' =>
                    $opponentTeamId,

                ':was_home' =>
                    !empty(
                        $history[
                            'was_home'
                        ]
                    )
                        ? 1
                        : 0,

                ':total_points' =>
                    (int) (
                        $history[
                            'total_points'
                        ]
                        ?? 0
                    ),

                ':minutes' =>
                    (int) (
                        $history[
                            'minutes'
                        ]
                        ?? 0
                    ),

                ':starts' =>
                    (int) (
                        $history[
                            'starts'
                        ]
                        ?? 0
                    ),

                ':goals' =>
                    (int) (
                        $history[
                            'goals'
                        ]
                        ?? 0
                    ),

                ':assists' =>
                    (int) (
                        $history[
                            'assists'
                        ]
                        ?? 0
                    ),

                ':expected_goals' =>
                    $this->nullableFloat(
                        $history[
                            'expected_goals'
                        ]
                        ?? null
                    ),

                ':expected_assists' =>
                    $this->nullableFloat(
                        $history[
                            'expected_assists'
                        ]
                        ?? null
                    ),

                ':expected_goal_involvements' =>
                    $this->nullableFloat(
                        $history[
                            'expected_goal_involvements'
                        ]
                        ?? null
                    ),

                ':clean_sheets' =>
                    (int) (
                        $history[
                            'clean_sheets'
                        ]
                        ?? 0
                    ),

                ':goals_conceded' =>
                    (int) (
                        $history[
                            'goals_conceded'
                        ]
                        ?? 0
                    ),

                ':expected_goals_conceded' =>
                    $this->nullableFloat(
                        $history[
                            'expected_goals_conceded'
                        ]
                        ?? null
                    ),

                ':saves' =>
                    (int) (
                        $history[
                            'saves'
                        ]
                        ?? 0
                    ),

                ':penalties_saved' =>
                    (int) (
                        $history[
                            'penalties_saved'
                        ]
                        ?? 0
                    ),

                ':clearances_blocks_interceptions' =>
                    (int) (
                        $history[
                            'clearances_blocks_interceptions'
                        ]
                        ?? 0
                    ),

                ':recoveries' =>
                    (int) (
                        $history[
                            'recoveries'
                        ]
                        ?? 0
                    ),

                ':tackles' =>
                    (int) (
                        $history[
                            'tackles'
                        ]
                        ?? 0
                    ),

                ':defensive_contribution' =>
                    (int) (
                        $history[
                            'defensive_contribution'
                        ]
                        ?? 0
                    ),

                ':own_goals' =>
                    (int) (
                        $history[
                            'own_goals'
                        ]
                        ?? 0
                    ),

                ':penalties_missed' =>
                    (int) (
                        $history[
                            'penalties_missed'
                        ]
                        ?? 0
                    ),

                ':yellow_cards' =>
                    (int) (
                        $history[
                            'yellow_cards'
                        ]
                        ?? 0
                    ),

                ':red_cards' =>
                    (int) (
                        $history[
                            'red_cards'
                        ]
                        ?? 0
                    ),

                ':bonus' =>
                    (int) (
                        $history[
                            'bonus'
                        ]
                        ?? 0
                    ),

                ':bps' =>
                    (int) (
                        $history[
                            'bps'
                        ]
                        ?? 0
                    ),

                ':influence' =>
                    $this->nullableFloat(
                        $history[
                            'influence'
                        ]
                        ?? null
                    ),

                ':creativity' =>
                    $this->nullableFloat(
                        $history[
                            'creativity'
                        ]
                        ?? null
                    ),

                ':threat' =>
                    $this->nullableFloat(
                        $history[
                            'threat'
                        ]
                        ?? null
                    ),

                ':ict_index' =>
                    $this->nullableFloat(
                        $history[
                            'ict_index'
                        ]
                        ?? null
                    ),

                ':price' =>
                    $this->nullableFloat(
                        $history[
                            'price'
                        ]
                        ?? null
                    ),

                ':selected' =>
                    $this->nullableInt(
                        $history[
                            'selected'
                        ]
                        ?? null
                    ),

                ':transfers_balance' =>
                    $this->nullableInt(
                        $history[
                            'transfers_balance'
                        ]
                        ?? null
                    ),

                ':transfers_in' =>
                    $this->nullableInt(
                        $history[
                            'transfers_in'
                        ]
                        ?? null
                    ),

                ':transfers_out' =>
                    $this->nullableInt(
                        $history[
                            'transfers_out'
                        ]
                        ?? null
                    )
            ]);
    }


    /**
     * Return one historical player/fixture row.
     */
    public function getByPlayerAndFixture(
        int $playerId,
        int $fixtureId
    ): ?array {

        if (
            $playerId <= 0
            ||
            $fixtureId <= 0
        ) {

            return null;
        }


        $statement =
            $this->db
                ->prepare(
                    "
                    SELECT
                        *
                    FROM
                        player_fixture_history
                    WHERE
                        player_id = :player_id
                        AND
                        fixture_id = :fixture_id
                    LIMIT 1
                    "
                );


        $statement
            ->execute([

                ':player_id' =>
                    $playerId,

                ':fixture_id' =>
                    $fixtureId
            ]);


        $row =
            $statement
                ->fetch(
                    PDO::FETCH_ASSOC
                );


        return is_array(
            $row
        )
            ? $row
            : null;
    }


    /**
     * Return all fixture history for one player.
     *
     * Ordering by gameweek and fixture makes this safe for
     * double gameweeks where a player can have more than one
     * fixture in the same gameweek.
     */
    public function getByPlayerId(
        int $playerId
    ): array {

        if ($playerId <= 0) {

            return [];
        }


        $statement =
            $this->db
                ->prepare(
                    "
                    SELECT
                        pfh.*
                    FROM
                        player_fixture_history pfh
                    INNER JOIN
                        gameweeks g
                            ON g.id = pfh.gameweek_id
                    INNER JOIN
                        fixtures f
                            ON f.id = pfh.fixture_id
                    WHERE
                        pfh.player_id = :player_id
                    ORDER BY
                        g.fpl_gameweek_id ASC,
                        f.kickoff_time ASC,
                        pfh.id ASC
                    "
                );


        $statement
            ->execute([

                ':player_id' =>
                    $playerId
            ]);


        return $statement
            ->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    /**
     * Return all player history rows for one local fixture.
     */
    public function getByFixtureId(
        int $fixtureId
    ): array {

        if ($fixtureId <= 0) {

            return [];
        }


        $statement =
            $this->db
                ->prepare(
                    "
                    SELECT
                        *
                    FROM
                        player_fixture_history
                    WHERE
                        fixture_id = :fixture_id
                    ORDER BY
                        player_id ASC
                    "
                );


        $statement
            ->execute([

                ':fixture_id' =>
                    $fixtureId
            ]);


        return $statement
            ->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    /**
     * Return all player fixture-history rows belonging
     * to one local gameweek.
     *
     * A player may appear more than once during a double
     * gameweek, so this intentionally returns fixture rows,
     * not one row per player.
     */
    public function getByGameweekId(
        int $gameweekId
    ): array {

        if ($gameweekId <= 0) {

            return [];
        }


        $statement =
            $this->db
                ->prepare(
                    "
                    SELECT
                        pfh.*
                    FROM
                        player_fixture_history pfh
                    INNER JOIN
                        fixtures f
                            ON f.id = pfh.fixture_id
                    WHERE
                        pfh.gameweek_id = :gameweek_id
                    ORDER BY
                        f.kickoff_time ASC,
                        pfh.player_id ASC
                    "
                );


        $statement
            ->execute([

                ':gameweek_id' =>
                    $gameweekId
            ]);


        return $statement
            ->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    /**
     * Return the number of stored fixture-history rows.
     */
    public function count(): int
    {
        return (int) $this->db
            ->query(
                "
                SELECT COUNT(*)
                FROM player_fixture_history
                "
            )
            ->fetchColumn();
    }


    /**
     * Convert a numeric value to float while preserving NULL.
     */
    private function nullableFloat(
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


        return (float) $value;
    }


    /**
     * Convert a numeric value to integer while preserving NULL.
     */
    private function nullableInt(
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


        return (int) $value;
    }
}