<?php

class PlayerRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM players
            ORDER BY web_name
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getMostExpensive(int $limit = 10): array
    {
    $stmt = $this->db->prepare("
        SELECT
            web_name,
            price,
            goals,
            assists
        FROM players
        ORDER BY price DESC, web_name ASC
        LIMIT :limit
    ");

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}