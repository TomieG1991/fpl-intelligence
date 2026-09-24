<?php

/**
 * HistoricalEvidenceLifecycleLauncher
 *
 * Maps the historical-evidence lifecycle onto the existing
 * operational entry points.
 *
 * This launcher deliberately remains separate from the live
 * DataUpdateCoordinator and DataUpdateProcessRunner.
 *
 * Recommendation candidate capture is deliberately excluded
 * because it requires genuine manager-specific production
 * recommendation evidence.
 */
class HistoricalEvidenceLifecycleLauncher
{
    private string $projectRoot;

    private $runner;


    /*
     * ============================================================
     * CONSTRUCTOR
     * ============================================================
     */

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
     * ============================================================
     * RUN
     * ============================================================
     */

    public function run(): array
    {

        $runner =
            $this->runner;


        $projectRoot =
            $this->projectRoot;


        $coordinator =
            new HistoricalEvidenceLifecycleCoordinator(

                /*
                 * ------------------------------------------------
                 * RECOMMENDATION CANDIDATE PROMOTION
                 * ------------------------------------------------
                 */

                static function () use (
                    $runner,
                    $projectRoot
                ): array {

                    return $runner(
                        'recommendation_promotion',
                        $projectRoot
                        . DIRECTORY_SEPARATOR
                        . 'cron'
                        . DIRECTORY_SEPARATOR
                        . 'promoteRecommendationCandidates.php'
                    );
                },


                /*
                 * ------------------------------------------------
                 * PLAYER SNAPSHOT CANDIDATE PROMOTION
                 * ------------------------------------------------
                 */

                static function () use (
                    $runner,
                    $projectRoot
                ): array {

                    return $runner(
                        'player_snapshot_promotion',
                        $projectRoot
                        . DIRECTORY_SEPARATOR
                        . 'cron'
                        . DIRECTORY_SEPARATOR
                        . 'promotePlayerGameweekSnapshotCandidates.php'
                    );
                },


                /*
                 * ------------------------------------------------
                 * PLAYER SNAPSHOT CANDIDATE CAPTURE
                 * ------------------------------------------------
                 */

                static function () use (
                    $runner,
                    $projectRoot
                ): array {

                    return $runner(
                        'player_snapshot_capture',
                        $projectRoot
                        . DIRECTORY_SEPARATOR
                        . 'cron'
                        . DIRECTORY_SEPARATOR
                        . 'capturePlayerGameweekSnapshotCandidates.php'
                    );
                }
            );


        return $coordinator->run();
    }
}