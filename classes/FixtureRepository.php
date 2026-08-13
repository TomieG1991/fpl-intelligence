<?php

class FixtureRepository
{
    private PDO $db;


    public function __construct(
        PDO $db
    ) {

        $this->db = $db;
    }


    /**
     * Insert or update a fixture received from
     * the official FPL API.
     */
    public function upsert(
        array $fixture,
        int $homeTeamId,
        int $awayTeamId
    ): void {

        $stmt =
            $this->db->prepare("
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

            ':fpl_fixture_id' =>
                (int) $fixture['id'],

            ':gameweek' =>
                isset($fixture['event'])
                    ? (int) $fixture['event']
                    : null,

            ':home_team_id' =>
                $homeTeamId,

            ':away_team_id' =>
                $awayTeamId,

            ':kickoff_time' =>
                $this->formatKickoffTime(
                    $fixture['kickoff_time']
                    ?? null
                ),

            ':finished' =>
                !empty($fixture['finished'])
                    ? 1
                    : 0,

            ':finished_provisional' =>
                !empty(
                    $fixture['finished_provisional']
                )
                    ? 1
                    : 0,

            ':home_score' =>
                isset($fixture['team_h_score'])
                    ? (int) $fixture['team_h_score']
                    : null,

            ':away_score' =>
                isset($fixture['team_a_score'])
                    ? (int) $fixture['team_a_score']
                    : null,

            ':home_difficulty' =>
                isset($fixture['team_h_difficulty'])
                    ? (int) $fixture['team_h_difficulty']
                    : null,

            ':away_difficulty' =>
                isset($fixture['team_a_difficulty'])
                    ? (int) $fixture['team_a_difficulty']
                    : null
        ]);
    }


    /**
     * Return all fixtures in chronological order.
     */
    public function getAll(): array
    {
        $stmt =
            $this->db->query("
                SELECT *
                FROM fixtures
                ORDER BY
                    gameweek ASC,
                    kickoff_time ASC,
                    id ASC
            ");


        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    /**
     * Find a fixture by its local database ID.
     */
    public function getById(
        int $fixtureId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT *
                FROM fixtures
                WHERE id = :fixture_id
                LIMIT 1
            ");


        $stmt->bindValue(
            ':fixture_id',
            $fixtureId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $fixture =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $fixture !== false
                ? $fixture
                : null;
    }


    /**
     * Find a fixture using the official FPL fixture ID.
     */
    public function getByFplFixtureId(
        int $fplFixtureId
    ): ?array {

        $stmt =
            $this->db->prepare("
                SELECT *
                FROM fixtures
                WHERE fpl_fixture_id = :fpl_fixture_id
                LIMIT 1
            ");


        $stmt->bindValue(
            ':fpl_fixture_id',
            $fplFixtureId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $fixture =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $fixture !== false
                ? $fixture
                : null;
    }


    /**
     * Return upcoming fixtures for a team.
     */
    public function getUpcomingForTeam(
        int $teamId,
        int $limit = 5
    ): array {

        if ($limit <= 0) {
            return [];
        }


        $stmt =
            $this->db->prepare("
                SELECT *
                FROM fixtures
                WHERE (
                    home_team_id = :home_team_id
                    OR away_team_id = :away_team_id
                )
                AND finished = 0
                ORDER BY
                    gameweek ASC,
                    kickoff_time ASC,
                    id ASC
                LIMIT :limit
            ");


        $stmt->bindValue(
            ':home_team_id',
            $teamId,
            PDO::PARAM_INT
        );


        $stmt->bindValue(
            ':away_team_id',
            $teamId,
            PDO::PARAM_INT
        );


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


    /**
     * Return all completed fixtures.
     */
    public function getFinishedFixtures(): array
    {
        $stmt =
            $this->db->query("
                SELECT *
                FROM fixtures
                WHERE finished = 1
                ORDER BY
                    gameweek ASC,
                    kickoff_time ASC,
                    id ASC
            ");


        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    /**
     * Return completed fixtures involving
     * a specific team.
     */
    public function getFinishedForTeam(
        int $teamId
    ): array {

        $stmt =
            $this->db->prepare("
                SELECT *
                FROM fixtures
                WHERE (
                    home_team_id = :home_team_id
                    OR away_team_id = :away_team_id
                )
                AND finished = 1
                ORDER BY
                    gameweek ASC,
                    kickoff_time ASC,
                    id ASC
            ");


        $stmt->bindValue(
            ':home_team_id',
            $teamId,
            PDO::PARAM_INT
        );


        $stmt->bindValue(
            ':away_team_id',
            $teamId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    /**
     * Convert the FPL API ISO kickoff timestamp
     * into the database DATETIME format.
     */
    private function formatKickoffTime(
        ?string $kickoffTime
    ): ?string {

        if (
            $kickoffTime === null
            ||
            trim($kickoffTime) === ''
        ) {

            return null;
        }


        $date =
            new DateTime(
                $kickoffTime
            );


        return
            $date->format(
                'Y-m-d H:i:s'
            );
    }
}