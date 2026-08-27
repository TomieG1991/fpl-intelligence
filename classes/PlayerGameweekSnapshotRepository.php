<?php

class PlayerGameweekSnapshotRepository
{
    private PDO $db;


    public function __construct(
        PDO $db
    ) {

        $this->db =
            $db;
    }


    /**
     * Return all snapshots for one gameweek.
     */
    public function getByGameweekId(
        int $gameweekId
    ): array {

        $stmt =
            $this->db->prepare(
                "
                SELECT *
                FROM player_gameweek_snapshots
                WHERE gameweek_id = :gameweek_id
                ORDER BY player_id ASC
                "
            );


        $stmt->bindValue(
            ':gameweek_id',
            $gameweekId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    /**
     * Return all snapshots for one player.
     */
    public function getByPlayerId(
        int $playerId
    ): array {

        $stmt =
            $this->db->prepare(
                "
                SELECT *
                FROM player_gameweek_snapshots
                WHERE player_id = :player_id
                ORDER BY gameweek_id ASC
                "
            );


        $stmt->bindValue(
            ':player_id',
            $playerId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    /**
     * Return one player snapshot for one gameweek.
     */
    public function getByPlayerAndGameweek(
        int $playerId,
        int $gameweekId
    ): ?array {

        $stmt =
            $this->db->prepare(
                "
                SELECT *
                FROM player_gameweek_snapshots
                WHERE
                    player_id = :player_id
                    AND gameweek_id = :gameweek_id
                LIMIT 1
                "
            );


        $stmt->bindValue(
            ':player_id',
            $playerId,
            PDO::PARAM_INT
        );


        $stmt->bindValue(
            ':gameweek_id',
            $gameweekId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $snapshot =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $snapshot !== false
                ? $snapshot
                : null;
    }
    
    
    /**
     * Insert one immutable player/gameweek snapshot.
     *
     * If the same player/gameweek snapshot already exists,
     * leave the historical row unchanged.
     *
     * Returns:
     * true  = snapshot inserted
     * false = snapshot already existed
     */
    public function insertIfAbsent(
        array $snapshot
    ): bool {

        $stmt =
            $this->db->prepare(
                "
                INSERT IGNORE INTO player_gameweek_snapshots (
                    gameweek_id,
                    player_id,
                    fpl_player_id,
                    team_id,
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
                VALUES (
                    :gameweek_id,
                    :player_id,
                    :fpl_player_id,
                    :team_id,
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


        $stmt->execute([

            ':gameweek_id' =>
                (int) (
                    $snapshot[
                        'gameweek_id'
                    ]
                    ?? 0
                ),

            ':player_id' =>
                (int) (
                    $snapshot[
                        'player_id'
                    ]
                    ?? 0
                ),

            ':fpl_player_id' =>
                (int) (
                    $snapshot[
                        'fpl_player_id'
                    ]
                    ?? 0
                ),

            ':team_id' =>
                (int) (
                    $snapshot[
                        'team_id'
                    ]
                    ?? 0
                ),

            ':position' =>
                $snapshot[
                    'position'
                ]
                ?? null,

            ':price' =>
                is_numeric(
                    $snapshot[
                        'price'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'price'
                    ]
                    : null,
                    
            ':selected' =>
                is_numeric(
                    $snapshot[
                        'selected'
                    ]
                    ?? null
                )
                    ? (int) $snapshot[
                        'selected'
                    ]
                    : null,

            ':selected_by_percent' =>
                is_numeric(
                    $snapshot[
                        'selected_by_percent'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'selected_by_percent'
                    ]
                    : null,

            ':chance_of_playing' =>
                is_numeric(
                    $snapshot[
                        'chance_of_playing'
                    ]
                    ?? null
                )
                    ? (int) $snapshot[
                        'chance_of_playing'
                    ]
                    : null,

            ':status' =>
                $snapshot[
                    'status'
                ]
                ?? null,

            ':news' =>
                $snapshot[
                    'news'
                ]
                ?? null,

            ':minutes' =>
                (int) (
                    $snapshot[
                        'minutes'
                    ]
                    ?? 0
                ),

            ':goals' =>
                (int) (
                    $snapshot[
                        'goals'
                    ]
                    ?? 0
                ),

            ':assists' =>
                (int) (
                    $snapshot[
                        'assists'
                    ]
                    ?? 0
                ),

            ':clean_sheets' =>
                (int) (
                    $snapshot[
                        'clean_sheets'
                    ]
                    ?? 0
                ),

            ':bonus' =>
                (int) (
                    $snapshot[
                        'bonus'
                    ]
                    ?? 0
                ),

            ':bps' =>
                (int) (
                    $snapshot[
                        'bps'
                    ]
                    ?? 0
                ),

            ':ict_index' =>
                is_numeric(
                    $snapshot[
                        'ict_index'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'ict_index'
                    ]
                    : null,

            ':expected_goals' =>
                is_numeric(
                    $snapshot[
                        'expected_goals'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'expected_goals'
                    ]
                    : null,

            ':expected_assists' =>
                is_numeric(
                    $snapshot[
                        'expected_assists'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'expected_assists'
                    ]
                    : null,

            ':expected_goal_involvements' =>
                is_numeric(
                    $snapshot[
                        'expected_goal_involvements'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'expected_goal_involvements'
                    ]
                    : null
        ]);


        return
            $stmt->rowCount()
            ===
            1;
    }


    /**
     * Insert or update one player snapshot.
     *
     * Re-running a snapshot import for the same
     * player and gameweek updates the existing row.
     */
    public function upsert(
        array $snapshot
    ): void {

        $stmt =
            $this->db->prepare(
                "
                INSERT INTO player_gameweek_snapshots (
                    gameweek_id,
                    player_id,
                    fpl_player_id,
                    team_id,
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
                VALUES (
                    :gameweek_id,
                    :player_id,
                    :fpl_player_id,
                    :team_id,
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
                ON DUPLICATE KEY UPDATE
                    fpl_player_id = VALUES(fpl_player_id),
                    team_id = VALUES(team_id),
                    position = VALUES(position),
                    price = VALUES(price),
                    selected = VALUES(selected),
                    selected_by_percent = VALUES(selected_by_percent),
                    chance_of_playing = VALUES(chance_of_playing),
                    status = VALUES(status),
                    news = VALUES(news),
                    minutes = VALUES(minutes),
                    goals = VALUES(goals),
                    assists = VALUES(assists),
                    clean_sheets = VALUES(clean_sheets),
                    bonus = VALUES(bonus),
                    bps = VALUES(bps),
                    ict_index = VALUES(ict_index),
                    expected_goals = VALUES(expected_goals),
                    expected_assists = VALUES(expected_assists),
                    expected_goal_involvements =
                        VALUES(expected_goal_involvements),
                    updated_at = CURRENT_TIMESTAMP
                "
            );


        $stmt->execute([

            ':gameweek_id' =>
                (int) (
                    $snapshot[
                        'gameweek_id'
                    ]
                    ?? 0
                ),

            ':player_id' =>
                (int) (
                    $snapshot[
                        'player_id'
                    ]
                    ?? 0
                ),

            ':fpl_player_id' =>
                (int) (
                    $snapshot[
                        'fpl_player_id'
                    ]
                    ?? 0
                ),

            ':team_id' =>
                (int) (
                    $snapshot[
                        'team_id'
                    ]
                    ?? 0
                ),

            ':position' =>
                $snapshot[
                    'position'
                ]
                ?? null,

            ':price' =>
                is_numeric(
                    $snapshot[
                        'price'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'price'
                    ]
                    : null,
                    
            ':selected' =>
                is_numeric(
                    $snapshot[
                        'selected'
                    ]
                    ?? null
                )
                    ? (int) $snapshot[
                        'selected'
                    ]
                    : null,

            ':selected_by_percent' =>
                is_numeric(
                    $snapshot[
                        'selected_by_percent'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'selected_by_percent'
                    ]
                    : null,

            ':chance_of_playing' =>
                is_numeric(
                    $snapshot[
                        'chance_of_playing'
                    ]
                    ?? null
                )
                    ? (int) $snapshot[
                        'chance_of_playing'
                    ]
                    : null,

            ':status' =>
                $snapshot[
                    'status'
                ]
                ?? null,

            ':news' =>
                $snapshot[
                    'news'
                ]
                ?? null,

            ':minutes' =>
                (int) (
                    $snapshot[
                        'minutes'
                    ]
                    ?? 0
                ),

            ':goals' =>
                (int) (
                    $snapshot[
                        'goals'
                    ]
                    ?? 0
                ),

            ':assists' =>
                (int) (
                    $snapshot[
                        'assists'
                    ]
                    ?? 0
                ),

            ':clean_sheets' =>
                (int) (
                    $snapshot[
                        'clean_sheets'
                    ]
                    ?? 0
                ),

            ':bonus' =>
                (int) (
                    $snapshot[
                        'bonus'
                    ]
                    ?? 0
                ),

            ':bps' =>
                (int) (
                    $snapshot[
                        'bps'
                    ]
                    ?? 0
                ),

            ':ict_index' =>
                is_numeric(
                    $snapshot[
                        'ict_index'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'ict_index'
                    ]
                    : null,

            ':expected_goals' =>
                is_numeric(
                    $snapshot[
                        'expected_goals'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'expected_goals'
                    ]
                    : null,

            ':expected_assists' =>
                is_numeric(
                    $snapshot[
                        'expected_assists'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'expected_assists'
                    ]
                    : null,

            ':expected_goal_involvements' =>
                is_numeric(
                    $snapshot[
                        'expected_goal_involvements'
                    ]
                    ?? null
                )
                    ? (float) $snapshot[
                        'expected_goal_involvements'
                    ]
                    : null
        ]);
    }
}