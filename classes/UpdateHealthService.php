<?php

class UpdateHealthService
{

    private const RUNNING_TIMEOUT_SECONDS =
        1800;


    private UpdateRunRepository $repository;


    public function __construct(
        UpdateRunRepository $repository
    ) {

        $this->repository =
            $repository;
    }


    /*
     * ========================================================
     * EVALUATE
     * ========================================================
     */

    public function evaluate(
        string $updateType,
        string $now,
        int $freshnessSeconds
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


        $this->validateDateTime(
            $now,
            'Current timestamp'
        );


        if ($freshnessSeconds <= 0) {

            throw new InvalidArgumentException(
                'Freshness threshold must be positive.'
            );
        }


        $currentTime =
            new DateTimeImmutable(
                $now
            );


        $latestRun =
            $this->repository
                ->getLatestByType(
                    $updateType
                );


        $latestSuccessfulRun =
            $this->repository
                ->getLatestSuccessfulByType(
                    $updateType
                );


        /*
         * No update attempt has ever been recorded.
         */
        if ($latestRun === null) {

            return $this->buildResult(
                $updateType,
                'Unavailable',
                'No update history is available.',
                null,
                null,
                null,
                null
            );
        }


        $lastSuccessAt =
            null;


        $ageSeconds =
            null;


        if (
            $latestSuccessfulRun !== null
            &&
            $latestSuccessfulRun[
                'completed_at'
            ] !== null
        ) {

            $lastSuccessAt =
                $latestSuccessfulRun[
                    'completed_at'
                ];


            $successTime =
                new DateTimeImmutable(
                    $lastSuccessAt
                );


            $ageSeconds =
                max(
                    0,
                    $currentTime->getTimestamp()
                    -
                    $successTime->getTimestamp()
                );
        }


        /*
         * ====================================================
         * RUNNING
         * ====================================================
         *
         * A recently started run is genuinely active.
         *
         * An old Running record is interpreted as a process
         * which probably terminated before completion could be
         * recorded.
         *
         * This interpretation does not modify persistence.
         */

        if (
            $latestRun[
                'status'
            ]
            ===
            'Running'
        ) {

            $startedAt =
                new DateTimeImmutable(
                    $latestRun[
                        'started_at'
                    ]
                );


            $runningSeconds =
                max(
                    0,
                    $currentTime->getTimestamp()
                    -
                    $startedAt->getTimestamp()
                );


            if (
                $runningSeconds
                >
                self::RUNNING_TIMEOUT_SECONDS
            ) {

                return $this->buildResult(
                    $updateType,
                    'Failed',
                    'Update run appears stuck or incomplete.',
                    $latestRun,
                    $latestSuccessfulRun,
                    $lastSuccessAt,
                    $ageSeconds
                );
            }


            return $this->buildResult(
                $updateType,
                'Running',
                'Update is currently running.',
                $latestRun,
                $latestSuccessfulRun,
                $lastSuccessAt,
                $ageSeconds
            );
        }


        /*
         * ====================================================
         * FAILED
         * ====================================================
         */

        if (
            $latestRun[
                'status'
            ]
            ===
            'Failed'
        ) {

            return $this->buildResult(
                $updateType,
                'Failed',
                'Latest update failed.',
                $latestRun,
                $latestSuccessfulRun,
                $lastSuccessAt,
                $ageSeconds
            );
        }


        /*
         * ====================================================
         * PARTIAL
         * ====================================================
         */

        if (
            $latestRun[
                'status'
            ]
            ===
            'Partial'
        ) {

            return $this->buildResult(
                $updateType,
                'Partial',
                'Latest update completed only partially.',
                $latestRun,
                $latestSuccessfulRun,
                $lastSuccessAt,
                $ageSeconds
            );
        }


        /*
         * ====================================================
         * SUCCESS
         * ====================================================
         */

        if (
            $latestRun[
                'status'
            ]
            ===
            'Success'
        ) {

            if (
                $ageSeconds !== null
                &&
                $ageSeconds
                >
                $freshnessSeconds
            ) {

                return $this->buildResult(
                    $updateType,
                    'Stale',
                    'Latest successful update is stale.',
                    $latestRun,
                    $latestSuccessfulRun,
                    $lastSuccessAt,
                    $ageSeconds
                );
            }


            return $this->buildResult(
                $updateType,
                'Healthy',
                'Latest update completed successfully.',
                $latestRun,
                $latestSuccessfulRun,
                $lastSuccessAt,
                $ageSeconds
            );
        }


        /*
         * A persisted status outside the repository contract
         * should never normally occur.
         *
         * Fail safely rather than presenting unknown data as
         * healthy.
         */

        return $this->buildResult(
            $updateType,
            'Failed',
            'Latest update has an unsupported status.',
            $latestRun,
            $latestSuccessfulRun,
            $lastSuccessAt,
            $ageSeconds
        );
    }


    /*
     * ========================================================
     * BUILD RESULT
     * ========================================================
     */

    private function buildResult(
        string $updateType,
        string $status,
        string $reason,
        ?array $latestRun,
        ?array $latestSuccessfulRun,
        ?string $lastSuccessAt,
        ?int $ageSeconds
    ): array {

        return [
            'update_type' =>
                $updateType,

            'status' =>
                $status,

            'reason' =>
                $reason,

            'latest_run' =>
                $latestRun,

            'latest_successful_run' =>
                $latestSuccessfulRun,

            'last_success_at' =>
                $lastSuccessAt,

            'age_seconds' =>
                $ageSeconds,

            'records_received' =>
                $latestRun[
                    'records_received'
                ]
                ?? null,

            'records_updated' =>
                $latestRun[
                    'records_updated'
                ]
                ?? null,

            'records_skipped' =>
                $latestRun[
                    'records_skipped'
                ]
                ?? null,

            'records_failed' =>
                $latestRun[
                    'records_failed'
                ]
                ?? null,

            'duration_ms' =>
                $latestRun[
                    'duration_ms'
                ]
                ?? null,

            'error_message' =>
                $latestRun[
                    'error_message'
                ]
                ?? null
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
}