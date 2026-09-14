<?php

class DataUpdateCoordinator
{
    private $bootstrapRunner;

    private $fixturesRunner;

    private $playerHistoryRunner;


    public function __construct(
        callable $bootstrapRunner,
        callable $fixturesRunner,
        callable $playerHistoryRunner
    ) {

        $this->bootstrapRunner =
            $bootstrapRunner;

        $this->fixturesRunner =
            $fixturesRunner;

        $this->playerHistoryRunner =
            $playerHistoryRunner;
    }


    public function run(): array
    {

        $steps =
            [];


        /*
         * ====================================================
         * BOOTSTRAP
         * ====================================================
         */

        try {

            $bootstrapResult =
                call_user_func(
                    $this->bootstrapRunner
                );

        } catch (
            Throwable $exception
        ) {

            $steps[
                'bootstrap'
            ] = [

                'status' =>
                    'Failed',

                'error_message' =>
                    $exception
                        ->getMessage()
            ];


            return [

                'status' =>
                    'Failed',

                'steps' =>
                    $steps
            ];
        }


        $steps[
            'bootstrap'
        ] =
            $bootstrapResult;


        $bootstrapStatus =
            $bootstrapResult[
                'status'
            ]
            ?? null;


        if (
            $bootstrapStatus
            !==
            'Success'
        ) {

            return [

                'status' =>
                    'Failed',

                'steps' =>
                    $steps
            ];
        }


        /*
         * ====================================================
         * FIXTURES
         * ====================================================
         */

        try {

            $fixturesResult =
                call_user_func(
                    $this->fixturesRunner
                );

        } catch (
            Throwable $exception
        ) {

            $steps[
                'fixtures'
            ] = [

                'status' =>
                    'Failed',

                'error_message' =>
                    $exception
                        ->getMessage()
            ];


            return [

                'status' =>
                    'Failed',

                'steps' =>
                    $steps
            ];
        }


        $steps[
            'fixtures'
        ] =
            $fixturesResult;


        $fixturesStatus =
            $fixturesResult[
                'status'
            ]
            ?? null;


        if (
            $fixturesStatus
            !==
            'Success'
        ) {

            return [

                'status' =>
                    'Failed',

                'steps' =>
                    $steps
            ];
        }


        /*
         * ====================================================
         * PLAYER FIXTURE HISTORY
         * ====================================================
         */

        try {

            $playerHistoryResult =
                call_user_func(
                    $this->playerHistoryRunner
                );

        } catch (
            Throwable $exception
        ) {

            $steps[
                'player_fixture_history'
            ] = [

                'status' =>
                    'Failed',

                'error_message' =>
                    $exception
                        ->getMessage()
            ];


            return [

                'status' =>
                    'Failed',

                'steps' =>
                    $steps
            ];
        }


        $steps[
            'player_fixture_history'
        ] =
            $playerHistoryResult;


        $playerHistoryStatus =
            $playerHistoryResult[
                'status'
            ]
            ?? null;


        if (
            $playerHistoryStatus
            ===
            'Failed'
        ) {

            return [

                'status' =>
                    'Failed',

                'steps' =>
                    $steps
            ];
        }


        if (
            $playerHistoryStatus
            ===
            'Partial'
        ) {

            return [

                'status' =>
                    'Partial',

                'steps' =>
                    $steps
            ];
        }


        if (
            $playerHistoryStatus
            !==
            'Success'
        ) {

            return [

                'status' =>
                    'Failed',

                'steps' =>
                    $steps
            ];
        }


        /*
         * ====================================================
         * SUCCESS
         * ====================================================
         */

        return [

            'status' =>
                'Success',

            'steps' =>
                $steps
        ];
    }
}