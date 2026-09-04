<?php

class GameweekRepository
{
    private PDO $db;


    public function __construct(
        PDO $db
    ) {

        $this->db =
            $db;
    }


    /**
     * Return every stored gameweek in FPL order.
     */
    public function getAll(): array
    {
        $stmt =
            $this->db->query(
                "
                SELECT *
                FROM gameweeks
                ORDER BY
                    fpl_gameweek_id ASC
                "
            );


        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    /**
     * Find a gameweek by its local database ID.
     */
    public function getById(
        int $gameweekId
    ): ?array {

        $stmt =
            $this->db->prepare(
                "
                SELECT *
                FROM gameweeks
                WHERE id = :gameweek_id
                LIMIT 1
                "
            );


        $stmt->bindValue(
            ':gameweek_id',
            $gameweekId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $gameweek =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $gameweek !== false
                ? $gameweek
                : null;
    }


    /**
     * Find a gameweek using the official FPL event ID.
     */
    public function getByFplGameweekId(
        int $fplGameweekId
    ): ?array {

        $stmt =
            $this->db->prepare(
                "
                SELECT *
                FROM gameweeks
                WHERE fpl_gameweek_id = :fpl_gameweek_id
                LIMIT 1
                "
            );


        $stmt->bindValue(
            ':fpl_gameweek_id',
            $fplGameweekId,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $gameweek =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $gameweek !== false
                ? $gameweek
                : null;
    }


    /**
     * Return the gameweek currently marked by FPL
     * as the current event.
     */
    public function getCurrent(): ?array
    {
        $stmt =
            $this->db->query(
                "
                SELECT *
                FROM gameweeks
                WHERE is_current = 1
                ORDER BY fpl_gameweek_id ASC
                LIMIT 1
                "
            );


        $gameweek =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $gameweek !== false
                ? $gameweek
                : null;
    }


    /**
     * Return the previous FPL gameweek.
     */
    public function getPrevious(): ?array
    {
        $stmt =
            $this->db->query(
                "
                SELECT *
                FROM gameweeks
                WHERE is_previous = 1
                ORDER BY fpl_gameweek_id ASC
                LIMIT 1
                "
            );


        $gameweek =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $gameweek !== false
                ? $gameweek
                : null;
    }


    /**
     * Return the next FPL gameweek.
     */
    public function getNext(): ?array
    {
        $stmt =
            $this->db->query(
                "
                SELECT *
                FROM gameweeks
                WHERE is_next = 1
                ORDER BY fpl_gameweek_id ASC
                LIMIT 1
                "
            );


        $gameweek =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $gameweek !== false
                ? $gameweek
                : null;
    }
    
    
        /**
         * Return the gameweek with the earliest deadline
         * strictly after the supplied timestamp.
         *
         * Recommendation history uses the deadline itself
         * rather than FPL's current / next event flags so
         * pre-deadline recommendation evidence is assigned
         * to the gameweek it was actually targeting.
         */
        public function getNextDeadlineAfter(
            string $timestamp
        ): ?array {

            /*
             * Validate the timestamp before passing it to
             * the database.
             */
            try {

                $date =
                    new DateTimeImmutable(
                        $timestamp
                    );

            } catch (
                Exception $exception
            ) {

                throw new InvalidArgumentException(
                    'A valid timestamp is required.',
                    0,
                    $exception
                );
            }


            $formattedTimestamp =
                $date->format(
                    'Y-m-d H:i:s'
                );


            $stmt =
                $this->db->prepare(
                    "
                    SELECT *
                    FROM gameweeks
                    WHERE
                        deadline_time IS NOT NULL
                        AND
                        deadline_time > :timestamp
                    ORDER BY
                        deadline_time ASC
                    LIMIT 1
                    "
                );


            $stmt->bindValue(
                ':timestamp',
                $formattedTimestamp,
                PDO::PARAM_STR
            );


            $stmt->execute();


            $gameweek =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            return
                $gameweek !== false
                    ? $gameweek
                    : null;
        }


    /**
     * Insert or update one gameweek received from
     * FPL bootstrap event data.
     *
     * Re-running an import updates the same gameweek
     * rather than creating a duplicate row.
     */
    public function upsert(
        array $gameweek
    ): void {

        $stmt =
            $this->db->prepare(
                "
                INSERT INTO gameweeks (
                    fpl_gameweek_id,
                    name,
                    deadline_time,
                    finished,
                    data_checked,
                    is_previous,
                    is_current,
                    is_next
                )
                VALUES (
                    :fpl_gameweek_id,
                    :name,
                    :deadline_time,
                    :finished,
                    :data_checked,
                    :is_previous,
                    :is_current,
                    :is_next
                )
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    deadline_time = VALUES(deadline_time),
                    finished = VALUES(finished),
                    data_checked = VALUES(data_checked),
                    is_previous = VALUES(is_previous),
                    is_current = VALUES(is_current),
                    is_next = VALUES(is_next),
                    updated_at = CURRENT_TIMESTAMP
                "
            );


        $stmt->execute([

            ':fpl_gameweek_id' =>
                (int) (
                    $gameweek[
                        'id'
                    ]
                    ?? 0
                ),

            ':name' =>
                (string) (
                    $gameweek[
                        'name'
                    ]
                    ?? ''
                ),

            ':deadline_time' =>
                $this->formatDeadlineTime(
                    $gameweek[
                        'deadline_time'
                    ]
                    ?? null
                ),

            ':finished' =>
                !empty(
                    $gameweek[
                        'finished'
                    ]
                    ?? false
                )
                    ? 1
                    : 0,

            ':data_checked' =>
                !empty(
                    $gameweek[
                        'data_checked'
                    ]
                    ?? false
                )
                    ? 1
                    : 0,

            ':is_previous' =>
                !empty(
                    $gameweek[
                        'is_previous'
                    ]
                    ?? false
                )
                    ? 1
                    : 0,

            ':is_current' =>
                !empty(
                    $gameweek[
                        'is_current'
                    ]
                    ?? false
                )
                    ? 1
                    : 0,

            ':is_next' =>
                !empty(
                    $gameweek[
                        'is_next'
                    ]
                    ?? false
                )
                    ? 1
                    : 0
        ]);
    }


    /**
     * Convert the FPL ISO deadline timestamp into the
     * database DATETIME format used by this project.
     */
    private function formatDeadlineTime(
        ?string $deadlineTime
    ): ?string {

        if (
            $deadlineTime === null
            ||
            trim(
                $deadlineTime
            )
            === ''
        ) {

            return null;
        }


        $date =
            new DateTime(
                $deadlineTime
            );


        return
            $date->format(
                'Y-m-d H:i:s'
            );
    }
}