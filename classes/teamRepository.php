<?php

class TeamRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getTeamIdByFplId(int $fplTeamId): ?int
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM teams
            WHERE fpl_team_id = :fpl_team_id
        ");

        $stmt->execute([
            ':fpl_team_id' => $fplTeamId
        ]);

        $result = $stmt->fetchColumn();

        return $result !== false ? (int) $result : null;
    }
}