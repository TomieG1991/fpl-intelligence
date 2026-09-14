<?php

class DataUpdateProcessRunner
{
    private UpdateRunRepository $repository;

    private $executor;


    public function __construct(
        UpdateRunRepository $repository,
        callable $executor
    ) {

        $this->repository =
            $repository;


        $this->executor =
            $executor;
    }


    /*
     * ========================================================
     * RUN
     * ========================================================
     */

    public function run(
        string $updateType,
        string $scriptPath,
        array $arguments = []
    ): array {

        /*
         * Capture the latest persisted run before launching the
         * updater.
         *
         * This prevents an older successful run being mistaken
         * for the result of the process we are about to execute.
         */
        $previousRun =
            $this->repository
                ->getLatestByType(
                    $updateType
                );


        $previousRunId =
            $previousRun[
                'id'
            ]
            ?? null;


        /*
         * Execute the updater through the supplied process
         * executor.
         *
         * Production will eventually supply a real subprocess
         * executor. Tests supply a synthetic executor.
         */
        try {

            $processResult =
                ($this->executor)(
                    $scriptPath,
                    $arguments
                );

        } catch (
            Throwable $exception
        ) {

            return $this->failedResult(
                $updateType,
                $exception->getMessage()
            );
        }


        /*
         * Read the lifecycle record again after the process has
         * completed.
         */
        $latestRun =
            $this->repository
                ->getLatestByType(
                    $updateType
                );


        $latestRunId =
            $latestRun[
                'id'
            ]
            ?? null;


        /*
         * No new lifecycle row was created.
         *
         * If the process itself failed, stderr is the most useful
         * available diagnostic.
         */
        if (
            $latestRun === null
            ||
            $latestRunId
            ===
            $previousRunId
        ) {

            $exitCode =
                $processResult[
                    'exit_code'
                ]
                ?? 0;


            $stderr =
                trim(
                    (string) (
                        $processResult[
                            'stderr'
                        ]
                        ?? ''
                    )
                );


            if (
                $exitCode !== 0
                &&
                $stderr !== ''
            ) {

                return $this->failedResult(
                    $updateType,
                    $stderr
                );
            }


            return $this->failedResult(
                $updateType,
                'Updater process completed without recording a new update run.'
            );
        }


        /*
         * A subprocess ending while its lifecycle row is still
         * Running means the updater did not finish cleanly.
         */
        if (
            (
                $latestRun[
                    'status'
                ]
                ?? null
            )
            ===
            'Running'
        ) {

            return $this->failedResult(
                $updateType,
                'Updater process ended before its update run was completed.'
            );
        }


        /*
         * Completed lifecycle records are authoritative.
         *
         * Success, Partial and Failed are all meaningful updater
         * outcomes and are returned unchanged.
         */
        if (
            in_array(
                $latestRun[
                    'status'
                ]
                ?? null,
                [
                    'Success',
                    'Partial',
                    'Failed'
                ],
                true
            )
        ) {

            return $latestRun;
        }


        /*
         * Defensive fallback for an unsupported persisted status.
         */
        return $this->failedResult(
            $updateType,
            'Updater process recorded an unsupported update run status.'
        );
    }


    /*
     * ========================================================
     * FAILED RESULT
     * ========================================================
     */

    private function failedResult(
        string $updateType,
        string $errorMessage
    ): array {

        return [

            'update_type' =>
                $updateType,

            'status' =>
                'Failed',

            'error_message' =>
                $errorMessage
        ];
    }
}