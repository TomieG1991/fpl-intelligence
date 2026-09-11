<?php

class UpdateRunRepository
{

    private PDO $db;


    public function __construct(
        PDO $db
    ) {

        $this->db =
            $db;
    }


    /*
     * ========================================================
     * START RUN
     * ========================================================
     */

    public function start(
        string $updateType,
        string $startedAt
    ): int {

        $updateType =
            trim(
                $updateType
            );


        if ($updateType === '') {

            throw new InvalidArgumentException(
                'Update type cannot be empty.'
            );
        }


        $this->validateDateTime(
            $startedAt,
            'Start timestamp'
        );


        $statement =
            $this->db->prepare(
                "
                INSERT INTO update_runs (
                    update_type,
                    status,
                    started_at,
                    records_received,
                    records_updated,
                    records_skipped,
                    records_failed
                )
                VALUES (
                    :update_type,
                    'Running',
                    :started_at,
                    0,
                    0,
                    0,
                    0
                )
                "
            );


        $statement->execute(
            [
                'update_type' =>
                    $updateType,

                'started_at' =>
                    $startedAt
            ]
        );


        return (int) $this->db
            ->lastInsertId();
    }


    /*
     * ========================================================
     * COMPLETE RUN
     * ========================================================
     */

    public function complete(
        int $runId,
        string $status,
        string $completedAt,
        int $recordsReceived,
        int $recordsUpdated,
        int $recordsSkipped,
        int $recordsFailed,
        int $durationMs,
        ?string $errorMessage = null
    ): bool {

        if ($runId <= 0) {

            throw new InvalidArgumentException(
                'Run ID must be positive.'
            );
        }


        $allowedStatuses = [
            'Success',
            'Partial',
            'Failed'
        ];


        if (
            !in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            throw new InvalidArgumentException(
                'Unsupported completion status.'
            );
        }


        $this->validateDateTime(
            $completedAt,
            'Completion timestamp'
        );


        $this->validateNonNegative(
            $recordsReceived,
            'Records received'
        );


        $this->validateNonNegative(
            $recordsUpdated,
            'Records updated'
        );


        $this->validateNonNegative(
            $recordsSkipped,
            'Records skipped'
        );


        $this->validateNonNegative(
            $recordsFailed,
            'Records failed'
        );


        $this->validateNonNegative(
            $durationMs,
            'Duration'
        );


        $statement =
            $this->db->prepare(
                "
                UPDATE update_runs
                SET
                    status = :status,
                    completed_at = :completed_at,
                    records_received = :records_received,
                    records_updated = :records_updated,
                    records_skipped = :records_skipped,
                    records_failed = :records_failed,
                    duration_ms = :duration_ms,
                    error_message = :error_message
                WHERE id = :id
                "
            );


        $statement->execute(
            [
                'status' =>
                    $status,

                'completed_at' =>
                    $completedAt,

                'records_received' =>
                    $recordsReceived,

                'records_updated' =>
                    $recordsUpdated,

                'records_skipped' =>
                    $recordsSkipped,

                'records_failed' =>
                    $recordsFailed,

                'duration_ms' =>
                    $durationMs,

                'error_message' =>
                    $errorMessage,

                'id' =>
                    $runId
            ]
        );


        return
            $statement->rowCount()
            > 0;
    }


    /*
     * ========================================================
     * GET BY ID
     * ========================================================
     */

    public function getById(
        int $runId
    ): ?array {

        if ($runId <= 0) {

            throw new InvalidArgumentException(
                'Run ID must be positive.'
            );
        }


        $statement =
            $this->db->prepare(
                "
                SELECT
                    id,
                    update_type,
                    status,
                    started_at,
                    completed_at,
                    records_received,
                    records_updated,
                    records_skipped,
                    records_failed,
                    duration_ms,
                    error_message,
                    created_at
                FROM update_runs
                WHERE id = :id
                LIMIT 1
                "
            );


        $statement->execute(
            [
                'id' =>
                    $runId
            ]
        );


        $row =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if ($row === false) {

            return null;
        }


        return $this->hydrate(
            $row
        );
    }


    /*
     * ========================================================
     * GET LATEST BY TYPE
     * ========================================================
     */

    public function getLatestByType(
        string $updateType
    ): ?array {

        $updateType =
            trim(
                $updateType
            );


        if ($updateType === '') {

            throw new InvalidArgumentException(
                'Update type cannot be empty.'
            );
        }


        $statement =
            $this->db->prepare(
                "
                SELECT
                    id,
                    update_type,
                    status,
                    started_at,
                    completed_at,
                    records_received,
                    records_updated,
                    records_skipped,
                    records_failed,
                    duration_ms,
                    error_message,
                    created_at
                FROM update_runs
                WHERE update_type = :update_type
                ORDER BY
                    started_at DESC,
                    id DESC
                LIMIT 1
                "
            );


        $statement->execute(
            [
                'update_type' =>
                    $updateType
            ]
        );


        $row =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );


        if ($row === false) {

            return null;
        }


        return $this->hydrate(
            $row
        );
    }


    /*
     * ========================================================
     * GET RECENT BY TYPE
     * ========================================================
     */

    public function getRecentByType(
        string $updateType,
        int $limit = 10
    ): array {

        $updateType =
            trim(
                $updateType
            );


        if ($updateType === '') {

            throw new InvalidArgumentException(
                'Update type cannot be empty.'
            );
        }


        if ($limit <= 0) {

            throw new InvalidArgumentException(
                'Limit must be positive.'
            );
        }


        $statement =
            $this->db->prepare(
                "
                SELECT
                    id,
                    update_type,
                    status,
                    started_at,
                    completed_at,
                    records_received,
                    records_updated,
                    records_skipped,
                    records_failed,
                    duration_ms,
                    error_message,
                    created_at
                FROM update_runs
                WHERE update_type = :update_type
                ORDER BY
                    started_at DESC,
                    id DESC
                LIMIT :limit
                "
            );


        $statement->bindValue(
            ':update_type',
            $updateType,
            PDO::PARAM_STR
        );


        $statement->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );


        $statement->execute();


        $rows =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );


        return array_map(
            [
                $this,
                'hydrate'
            ],
            $rows
        );
    }


    /*
     * ========================================================
     * HYDRATE
     * ========================================================
     */

    private function hydrate(
        array $row
    ): array {

        return [
            'id' =>
                (int) $row[
                    'id'
                ],

            'update_type' =>
                (string) $row[
                    'update_type'
                ],

            'status' =>
                (string) $row[
                    'status'
                ],

            'started_at' =>
                (string) $row[
                    'started_at'
                ],

            'completed_at' =>
                $row[
                    'completed_at'
                ] !== null
                    ? (string) $row[
                        'completed_at'
                    ]
                    : null,

            'records_received' =>
                (int) $row[
                    'records_received'
                ],

            'records_updated' =>
                (int) $row[
                    'records_updated'
                ],

            'records_skipped' =>
                (int) $row[
                    'records_skipped'
                ],

            'records_failed' =>
                (int) $row[
                    'records_failed'
                ],

            'duration_ms' =>
                $row[
                    'duration_ms'
                ] !== null
                    ? (int) $row[
                        'duration_ms'
                    ]
                    : null,

            'error_message' =>
                $row[
                    'error_message'
                ] !== null
                    ? (string) $row[
                        'error_message'
                    ]
                    : null,

            'created_at' =>
                (string) $row[
                    'created_at'
                ]
        ];
    }


    /*
     * ========================================================
     * VALIDATE DATETIME
     * ========================================================
     */

    private function validateDateTime(
        string $value,
        string $field
    ): void {

        $date =
            DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $value
            );


        $errors =
            DateTimeImmutable::getLastErrors();


        $valid =
            $date !== false
            &&
            (
                $errors === false
                ||
                (
                    $errors[
                        'warning_count'
                    ] === 0
                    &&
                    $errors[
                        'error_count'
                    ] === 0
                )
            )
            &&
            $date->format(
                'Y-m-d H:i:s'
            )
            ===
            $value;


        if (!$valid) {

            throw new InvalidArgumentException(
                $field
                . ' must use Y-m-d H:i:s format.'
            );
        }
    }


    /*
     * ========================================================
     * VALIDATE NON-NEGATIVE INTEGER
     * ========================================================
     */

    private function validateNonNegative(
        int $value,
        string $field
    ): void {

        if ($value < 0) {

            throw new InvalidArgumentException(
                $field
                . ' cannot be negative.'
            );
        }
    }
}