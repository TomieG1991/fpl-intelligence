<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Recommendation Candidate Promotion<br>";
echo "============================================<br><br>";


try {

    /*
     * ========================================================
     * RUNNER
     * ========================================================
     *
     * Automated tests may inject a controlled runner so the
     * cron can be exercised without touching real historical
     * recommendation data.
     *
     * Normal production execution does not provide this
     * variable and therefore constructs the real dependency
     * chain below.
     */

    if (
        !isset(
            $recommendationPromotionCronRunner
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
            new RecommendationCandidateRepository(
                $connection
            );


        $snapshotRepository =
            new RecommendationSnapshotRepository(
                $connection
            );


        /*
         * ====================================================
         * PROMOTION SERVICE
         * ====================================================
         */

        $promotionService =
            new RecommendationCandidatePromotionService(
                $candidateRepository,
                $snapshotRepository
            );


        /*
         * ====================================================
         * PROMOTION RUNNER
         * ====================================================
         */

        $recommendationPromotionCronRunner =
            new RecommendationCandidatePromotionRunner(
                $candidateRepository,
                $promotionService
            );
    }


    /*
     * ========================================================
     * EXECUTION TIME
     * ========================================================
     *
     * Recommendation deadlines are stored and compared using
     * UTC-compatible MySQL DATETIME values.
     */

    if (
        !isset(
            $recommendationPromotionCronTimestamp
        )
    ) {

        $recommendationPromotionCronTimestamp =
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
        $recommendationPromotionCronRunner
            ->run(
                $recommendationPromotionCronTimestamp
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
            'Recommendation promotion did not complete successfully.'
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
            'Recommendation promotion accounting does not match ready candidates.'
        );
    }


    echo "RESULT: RECOMMENDATION PROMOTION COMPLETE";


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


    echo "RESULT: RECOMMENDATION PROMOTION FAILED";

    exit(1);
}