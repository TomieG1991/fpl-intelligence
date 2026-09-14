<?php

class UpdateRunLifecycleService
{
    private UpdateRunRepository $repository;


    public function __construct(
        UpdateRunRepository $repository
    ) {

        $this->repository =
            $repository;
    }


    public function start(
        string $updateType,
        string $startedAt
    ): int {

        return $this->repository
            ->start(
                $updateType,
                $startedAt
            );
    }


    public function succeed(
        int $runId,
        string $completedAt,
        int $recordsReceived,
        int $recordsUpdated,
        int $recordsSkipped,
        int $durationMs
    ): bool {

        return $this->repository
            ->complete(
                $runId,
                'Success',
                $completedAt,
                $recordsReceived,
                $recordsUpdated,
                $recordsSkipped,
                0,
                $durationMs,
                null
            );
    }


    public function fail(
        int $runId,
        string $completedAt,
        int $recordsReceived,
        int $recordsUpdated,
        int $recordsSkipped,
        int $recordsFailed,
        int $durationMs,
        string $errorMessage
    ): bool {

        return $this->repository
            ->complete(
                $runId,
                'Failed',
                $completedAt,
                $recordsReceived,
                $recordsUpdated,
                $recordsSkipped,
                $recordsFailed,
                $durationMs,
                $errorMessage
            );
    }
}