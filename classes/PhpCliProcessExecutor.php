<?php

class PhpCliProcessExecutor
{
    private string $phpExecutable;


    public function __construct(
        string $phpExecutable
    ) {

        $phpExecutable =
            trim(
                $phpExecutable
            );


        if (
            $phpExecutable === ''
            ||
            !is_file(
                $phpExecutable
            )
        ) {

            throw new InvalidArgumentException(
                'PHP executable could not be found.'
            );
        }


        $this->phpExecutable =
            $phpExecutable;
    }


    /*
     * ========================================================
     * INVOKE
     * ========================================================
     */

    public function __invoke(
        string $scriptPath,
        array $arguments = []
    ): array {

        $scriptPath =
            trim(
                $scriptPath
            );


        if (
            $scriptPath === ''
            ||
            !is_file(
                $scriptPath
            )
        ) {

            throw new InvalidArgumentException(
                'PHP script could not be found.'
            );
        }


        /*
         * Build a safely escaped command.
         */
        $command =
            escapeshellarg(
                $this->phpExecutable
            )
            . ' '
            . escapeshellarg(
                $scriptPath
            );


        foreach (
            $arguments
            as $argument
        ) {

            $command .=
                ' '
                . escapeshellarg(
                    (string) $argument
                );
        }


        /*
         * Match the established subprocess pattern already used
         * by runAllTests.php.
         */
        $descriptorSpec = [

            0 => [
                'pipe',
                'r'
            ],

            1 => [
                'pipe',
                'w'
            ],

            2 => [
                'pipe',
                'w'
            ]
        ];


        $process =
            proc_open(
                $command,
                $descriptorSpec,
                $pipes,
                dirname(
                    $scriptPath
                )
            );


        if (!is_resource($process)) {

            throw new RuntimeException(
                'Unable to start PHP CLI process.'
            );
        }


        /*
         * No stdin is required.
         */
        fclose(
            $pipes[0]
        );


        /*
         * Capture normal output.
         */
        $stdout =
            stream_get_contents(
                $pipes[1]
            );


        fclose(
            $pipes[1]
        );


        /*
         * Capture error output separately.
         */
        $stderr =
            stream_get_contents(
                $pipes[2]
            );


        fclose(
            $pipes[2]
        );


        /*
         * proc_close() provides the final subprocess exit code.
         */
        $exitCode =
            proc_close(
                $process
            );


        return [

            'exit_code' =>
                $exitCode,

            'stdout' =>
                $stdout !== false
                    ? $stdout
                    : '',

            'stderr' =>
                $stderr !== false
                    ? $stderr
                    : ''
        ];
    }
}