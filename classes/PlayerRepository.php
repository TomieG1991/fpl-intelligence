<?php

class PlayerRepository
{
    private PDO $db;


    public function __construct(
        PDO $db
    ) {

        $this->db = $db;
    }


    /**
     * Return all players.
     *
     * Results contain the complete player dataset required
     * by the player intelligence models.
     */
    public function getAll(): array
    {
        $stmt =
            $this->db->query("
                SELECT *
                FROM players
                ORDER BY web_name ASC
            ");


        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    /**
     * Find a player by the local database ID.
     */
    public function getById(
        int $playerId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT *
                FROM players
                WHERE id = :player_id
                LIMIT 1
            ");


        $stmt->bindValue(
            ':player_id',
            $playerId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $player =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $player !== false
                ? $player
                : null;
    }


    /**
     * Find a player by their official FPL player ID.
     */
    public function getByFplPlayerId(
        int $fplPlayerId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT *
                FROM players
                WHERE fpl_player_id = :fpl_player_id
                LIMIT 1
            ");


        $stmt->bindValue(
            ':fpl_player_id',
            $fplPlayerId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $player =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $player !== false
                ? $player
                : null;
    }


    /**
     * Return the most expensive players.
     *
     * Complete player rows are returned so the results
     * can be passed directly into the intelligence models.
     */
    public function getMostExpensive(
        int $limit = 10
    ): array {

        if ($limit <= 0) {
            return [];
        }


        $stmt =
            $this->db->prepare("
                SELECT *
                FROM players
                ORDER BY price DESC, web_name ASC
                LIMIT :limit
            ");


        $stmt->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );


        $stmt->execute();


        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }
}