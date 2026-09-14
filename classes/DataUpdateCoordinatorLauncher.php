<?php

class DataUpdateCoordinatorLauncher
{
    private string $projectRoot;

    private $runner;


    public function __construct(
        string $projectRoot,
        callable $runner
    ) {

        $this->projectRoot =
            rtrim(
                $projectRoot,
                '\\/'
            );


        $this->runner =
            $runner;
    }


    /*
     * ========================================================
     * RUN
     * ========================================================
     */

    public function run(): array
    {

        $runner =
            $this->runner;


        $projectRoot =
            $this->projectRoot;


        $coordinator =
            new DataUpdateCoordinator(

                /*
                 * ------------------------------------------------
                 * BOOTSTRAP
                 * ------------------------------------------------
                 */
                static function () use (
                    $runner,
                    $projectRoot
                ): array {

                    return $runner(
                        'bootstrap',
                        $projectRoot
                        . DIRECTORY_SEPARATOR
                        . 'cron'
                        . DIRECTORY_SEPARATOR
                        . 'updateFPLData.php'
                    );
                },


                /*
                 * ------------------------------------------------
                 * FIXTURES
                 * ------------------------------------------------
                 */
                static function () use (
                    $runner,
                    $projectRoot
                ): array {

                    return $runner(
                        'fixtures',
                        $projectRoot
                        . DIRECTORY_SEPARATOR
                        . 'cron'
                        . DIRECTORY_SEPARATOR
                        . 'updateFixtures.php'
                    );
                },


                /*
                 * ------------------------------------------------
                 * PLAYER FIXTURE HISTORY
                 * ------------------------------------------------
                 */
                static function () use (
                    $runner,
                    $projectRoot
                ): array {

                    return $runner(
                        'player_fixture_history',
                        $projectRoot
                        . DIRECTORY_SEPARATOR
                        . 'cron'
                        . DIRECTORY_SEPARATOR
                        . 'updatePlayerFixtureHistory.php',
                        [
                            '--full'
                        ]
                    );
                }
            );


        return $coordinator->run();
    }
}