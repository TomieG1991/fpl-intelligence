<?php

class FixtureRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function upsert(array $fixture, int $homeTeamId, int $awayTeamId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO fixtures (
                fpl_fixture_id,
                gameweek,
                home_team_id,
                away_team_id,
                kickoff_time,
                finished,
                finished_provisional,
                home_score,
                away_score,
                home_difficulty,
                away_difficulty
            )
            VALUES (
                :fpl_fixture_id,
                :gameweek,
                :home_team_id,
                :away_team_id,
                :kickoff_time,
                :finished,
                :finished_provisional,
                :home_score,
                :away_score,
                :home_difficulty,
                :away_difficulty
            )
            ON DUPLICATE KEY UPDATE
                gameweek = VALUES(gameweek),
                home_team_id = VALUES(home_team_id),
                away_team_id = VALUES(away_team_id),
                kickoff_time = VALUES(kickoff_time),
                finished = VALUES(finished),
                finished_provisional = VALUES(finished_provisional),
                home_score = VALUES(home_score),
                away_score = VALUES(away_score),
                home_difficulty = VALUES(home_difficulty),
                away_difficulty = VALUES(away_difficulty)
        ");

        $stmt->execute([
            ':fpl_fixture_id' => $fixture['id'],
            ':gameweek' => $fixture['event'],
            ':home_team_id' => $homeTeamId,
            ':away_team_id' => $awayTeamId,
            ':kickoff_time' => $this->formatKickoffTime(
                $fixture['kickoff_time'] ?? null
            ),
            ':finished' => !empty($fixture['finished']) ? 1 : 0,
            ':finished_provisional' => !empty(
                $fixture['finished_provisional']
            ) ? 1 : 0,
            ':home_score' => $fixture['team_h_score'],
            ':away_score' => $fixture['team_a_score'],
            ':home_difficulty' => $fixture['team_h_difficulty'],
            ':away_difficulty' => $fixture['team_a_difficulty']
        ]);
    }
    
    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM fixtures
            ORDER BY kickoff_time
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUpcomingForTeam(
        int $teamId,
        int $limit = 5
    ): array {

        $stmt = $this->db->prepare("
            SELECT *
            FROM fixtures
            WHERE (
                home_team_id = :team_id
                OR away_team_id = :team_id
            )
            AND finished = 0
            ORDER BY gameweek ASC, kickoff_time ASC
            LIMIT :limit
        ");

        $stmt->bindValue(
            ':team_id',
            $teamId,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFinishedFixtures(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM fixtures
            WHERE finished = 1
            ORDER BY gameweek ASC, kickoff_time ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function formatKickoffTime(?string $kickoffTime): ?string
    {
        if ($kickoffTime === null) {
            return null;
        }

        $date = new DateTime($kickoffTime);

        return $date->format('Y-m-d H:i:s');
    }
    
    
}