<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Promotion<br>";
echo "============================================<br><br>";


try {

    /*
     * ========================================================
     * RUNNER
     * ========================================================
     *
     * Automated tests may inject a controlled runner so this
     * entry point can be exercised without touching real player
     * snapshot candidates or immutable historical snapshots.
     *
     * Normal production execution does not define this variable
     * and therefore constructs the real dependency chain below.
     */

    if (
        !isset(
            $playerSnapshotPromotionCronRunner
        )
    ) {

        /*
         * ====================================================
         * DATABASE
         * ====================================================
         */

        $database =
            new Database();


        $connection =
            $database
                ->getConnection();


        echo "Database connection successful<br><br>";


        /*
         * ====================================================
         * REPOSITORIES
         * ====================================================
         */

        $candidateRepository =
            new PlayerGameweekSnapshotCandidateRepository(
                $connection
            );


        $snapshotRepository =
            new PlayerGameweekSnapshotRepository(
                $connection
            );


        /*
         * ====================================================
         * PROMOTION SERVICE
         * ====================================================
         */

        $promotionService =
            new PlayerGameweekSnapshotCandidatePromotionService(
                $candidateRepository,
                $snapshotRepository
            );


        /*
         * ====================================================
         * PROMOTION RUNNER
         * ====================================================
         */

        $playerSnapshotPromotionCronRunner =
            new PlayerGameweekSnapshotCandidatePromotionRunner(
                $candidateRepository,
                $promotionService
            );
    }


    /*
     * ========================================================
     * EXECUTION TIME
     * ========================================================
     *
     * Candidate deadlines are persisted using UTC-compatible
     * MySQL DATETIME values.
     */

    if (
        !isset(
            $playerSnapshotPromotionCronTimestamp
        )
    ) {

        $playerSnapshotPromotionCronTimestamp =
            gmdate(
                'Y-m-d H:i:s'
            );
    }


    /*
     * ========================================================
     * RUN PROMOTION
     * ========================================================
     */

    $result =
        $playerSnapshotPromotionCronRunner
            ->run(
                $playerSnapshotPromotionCronTimestamp
            );


    /*
     * ========================================================
     * RESULT
     * ========================================================
     */

    $status =
        (string) (
            $result[
                'status'
            ]
            ?? 'Unavailable'
        );


    if (
        $status !== 'Complete'
    ) {

        throw new RuntimeException(
            'Player snapshot promotion did not complete successfully.'
        );
    }


    $ready =
        (int) (
            $result[
                'ready'
            ]
            ?? 0
        );


    $promoted =
        (int) (
            $result[
                'promoted'
            ]
            ?? 0
        );


    $unchanged =
        (int) (
            $result[
                'unchanged'
            ]
            ?? 0
        );


    echo "Candidates Ready: "
        . number_format(
            $ready
        )
        . "<br>";


    echo "Snapshots Promoted: "
        . number_format(
            $promoted
        )
        . "<br>";


    echo "Candidates Unchanged: "
        . number_format(
            $unchanged
        )
        . "<br><br>";


    /*
     * ========================================================
     * ACCOUNTING VALIDATION
     * ========================================================
     *
     * Every ready candidate must result in exactly one of:
     *
     * - promoted
     * - unchanged
     */

    if (
        $ready
        !==
        (
            $promoted
            +
            $unchanged
        )
    ) {

        throw new RuntimeException(
            'Player snapshot promotion accounting does not '
            . 'match ready candidates.'
        );
    }


    echo "RESULT: PLAYER SNAPSHOT PROMOTION COMPLETE";


} catch (
    Throwable $exception
) {

    echo "ERROR: "
        . htmlspecialchars(
            $exception
                ->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "RESULT: PLAYER SNAPSHOT PROMOTION FAILED";


    exit(1);
}