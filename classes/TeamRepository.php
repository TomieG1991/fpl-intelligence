<?php

class TeamRepository
{
    private PDO $db;


    public function __construct(
        PDO $db
    ) {

        $this->db = $db;
    }


    /**
     * Return all teams.
     */
    public function getAll(): array
    {
        $stmt =
            $this->db->query("
                SELECT *
                FROM teams
                ORDER BY name ASC
            ");


        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    /**
     * Find a team by the local database ID.
     */
    public function getById(
        int $teamId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT *
                FROM teams
                WHERE id = :team_id
                LIMIT 1
            ");


        $stmt->bindValue(
            ':team_id',
            $teamId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $team =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $team !== false
                ? $team
                : null;
    }


    /**
     * Find a complete team record by its
     * official FPL team ID.
     */
    public function getByFplTeamId(
        int $fplTeamId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT *
                FROM teams
                WHERE fpl_team_id = :fpl_team_id
                LIMIT 1
            ");


        $stmt->bindValue(
            ':fpl_team_id',
            $fplTeamId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $team =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $team !== false
                ? $team
                : null;
    }


    /**
     * Return the local database team ID
     * for an official FPL team ID.
     *
     * Retained for importer and relationship
     * lookup compatibility.
     */
    public function getTeamIdByFplId(
        int $fplTeamId
    ): ?int {

        $stmt =
            $this->db->prepare("
                SELECT id
                FROM teams
                WHERE fpl_team_id = :fpl_team_id
                LIMIT 1
            ");


        $stmt->bindValue(
            ':fpl_team_id',
            $fplTeamId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $result =
            $stmt->fetchColumn();


        return
            $result !== false
                ? (int) $result
                : null;
    }
}