<?php

/**
 * HistoricalEvidenceProcessRunner
 *
 * Adapts the shared PHP CLI process executor to the
 * historical-evidence lifecycle contract.
 *
 * Historical-evidence operations deliberately do not use the
 * live-data update_runs lifecycle, so this runner determines
 * success directly from the subprocess exit code.
 */
class HistoricalEvidenceProcessRunner
{
    private $executor;


    /*
     * ============================================================
     * CONSTRUCTOR
     * ============================================================
     */

    public function __construct(
        callable $executor
    ) {

        $this->executor =
            $executor;
    }


    /*
     * ============================================================
     * RUN
     * ============================================================
     */

    public function run(
        string $stepName,
        string $scriptPath,
        array $arguments = []
    ): array {

        try {

            $processResult =
                call_user_func(
                    $this->executor,
                    $scriptPath,
                    $arguments
                );

        } catch (
            Throwable $exception
        ) {

            return $this->failedResult(
                $stepName,
                $exception->getMessage()
            );
        }


        /*
         * ========================================================
         * PROCESS RESULT VALIDATION
         * ========================================================
         */

        if (
            !is_array(
                $processResult
            )
            ||
            !array_key_exists(
                'exit_code',
                $processResult
            )
            ||
            !is_int(
                $processResult[
                    'exit_code'
                ]
            )
        ) {

            return $this->failedResult(
                $stepName,
                'Historical evidence process did not return a valid exit code.'
            );
        }


        $exitCode =
            $processResult[
                'exit_code'
            ];


        $stdout =
            (string) (
                $processResult[
                    'stdout'
                ]
                ?? ''
            );


        $stderr =
            trim(
                (string) (
                    $processResult[
                        'stderr'
                    ]
                    ?? ''
                )
            );


        /*
         * ========================================================
         * FAILED PROCESS
         * ========================================================
         */

        if ($exitCode !== 0) {

            $errorMessage =
                $stderr !== ''
                    ? $stderr
                    : 'Historical evidence process exited with code '
                        . $exitCode
                        . '.';


            return $this->failedResult(
                $stepName,
                $errorMessage,
                $stdout
            );
        }


        /*
         * ========================================================
         * SUCCESS
         * ========================================================
         */

        return [
            'step_name' =>
                $stepName,

            'status' =>
                'Success',

            'stdout' =>
                $stdout,

            'stderr' =>
                $stderr,

            'exit_code' =>
                $exitCode
        ];
    }


    /*
     * ============================================================
     * FAILED RESULT
     * ============================================================
     */

    private function failedResult(
        string $stepName,
        string $errorMessage,
        string $stdout = ''
    ): array {

        return [
            'step_name' =>
                $stepName,

            'status' =>
                'Failed',

            'error_message' =>
                $errorMessage,

            'stdout' =>
                $stdout
        ];
    }
}