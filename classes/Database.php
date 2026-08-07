<?php

class Database
{
    private PDO $connection;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';

        try {

            $this->connection = new PDO(
                "mysql:host={$config['database']['host']};dbname={$config['database']['name']}",
                $config['database']['username'],
                $config['database']['password']
            );

            $this->connection->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

        } catch(PDOException $e) {

            die(
                "Database connection failed: " . 
                $e->getMessage()
            );

        }
    }


    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function getTeamIdByFplId(int $fplTeamId): ?int
    {

        $stmt = $this->connection->prepare(
            "SELECT id FROM teams WHERE fpl_team_id = :id"
        );


        $stmt->execute([
            ':id' => $fplTeamId
        ]);


        $result = $stmt->fetch(PDO::FETCH_ASSOC);


        return $result['id'] ?? null;

    }
    
}