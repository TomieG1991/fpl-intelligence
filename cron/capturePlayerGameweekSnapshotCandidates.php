<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Candidate Capture<br>";
echo "============================================<br><br>";


try {

    /*
     * ========================================================
     * PRODUCTION CAPTURE SERVICE
     * ========================================================
     *
     * Automated tests may inject a controlled capture service
     * so this operational entry point can be exercised without
     * writing real player snapshot candidates.
     *
     * Normal production execution does not define this variable
     * and therefore constructs the real dependency chain below.
     */

    if (
        !isset(
            $playerSnapshotCaptureCronService
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
         * CURRENT-STATE REPOSITORIES
         * ====================================================
         */

        $gameweekRepository =
            new GameweekRepository(
                $connection
            );


        $playerRepository =
            new PlayerRepository(
                $connection
            );


        /*
         * ====================================================
         * CANDIDATE CAPTURE
         * ====================================================
         */

        $candidateCaptureService =
            new PlayerGameweekSnapshotCandidateCaptureService();


        $candidateRepository =
            new PlayerGameweekSnapshotCandidateRepository(
                $connection
            );


        /*
         * ====================================================
         * PRODUCTION ORCHESTRATION
         * ====================================================
         */

        $playerSnapshotCaptureCronService =
            new PlayerGameweekSnapshotCandidateProductionCapture(
                $gameweekRepository,
                $playerRepository,
                $candidateCaptureService,
                $candidateRepository
            );
    }


    /*
     * ========================================================
     * CAPTURE TIME
     * ========================================================
     *
     * The normal operational path captures current UTC time.
     *
     * Tests may provide a controlled timestamp so the entry
     * point can be exercised deterministically.
     */

    if (
        !isset(
            $playerSnapshotCaptureCronTimestamp
        )
    ) {

        $playerSnapshotCaptureCronTimestamp =
            gmdate(
                'Y-m-d H:i:s'
            );
    }


    /*
     * ========================================================
     * CAPTURE LATEST PRE-DEADLINE PLAYER STATE
     * ========================================================
     */

    $result =
        $playerSnapshotCaptureCronService
            ->capture(
                $playerSnapshotCaptureCronTimestamp
            );


    if (
        !is_array(
            $result
        )
    ) {

        throw new RuntimeException(
            'Player snapshot candidate capture did not return '
            . 'a valid result.'
        );
    }


    /*
     * ========================================================
     * RESULT STATUS
     * ========================================================
     */

    $status =
        (string) (
            $result[
                'status'
            ]
            ?? ''
        );


    /*
     * ========================================================
     * UNAVAILABLE
     * ========================================================
     *
     * No future FPL deadline is a valid lifecycle state.
     *
     * It is not an operational failure.
     */

    if (
        $status === 'Unavailable'
    ) {

        echo "No future player snapshot deadline is currently available."
            . "<br><br>";


        echo "RESULT: PLAYER SNAPSHOT CANDIDATE CAPTURE UNAVAILABLE";

        return;
    }


    if (
        $status !== 'Captured'
    ) {

        throw new RuntimeException(
            'Player snapshot candidate capture did not complete '
            . 'successfully.'
        );
    }


    /*
     * ========================================================
     * CAPTURE ACCOUNTING
     * ========================================================
     */

    $gameweekId =
        (int) (
            $result[
                'gameweek_id'
            ]
            ?? 0
        );


    $fplGameweekId =
        (int) (
            $result[
                'fpl_gameweek_id'
            ]
            ?? 0
        );


    $playersConsidered =
        (int) (
            $result[
                'players_considered'
            ]
            ?? 0
        );


    $candidatesBuilt =
        (int) (
            $result[
                'candidates_built'
            ]
            ?? 0
        );


    $saved =
        (int) (
            $result[
                'saved'
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


    /*
     * ========================================================
     * IDENTITY VALIDATION
     * ========================================================
     */

    if (
        $gameweekId <= 0
    ) {

        throw new RuntimeException(
            'Captured player snapshot candidates do not contain '
            . 'a valid local gameweek ID.'
        );
    }


    if (
        $fplGameweekId <= 0
    ) {

        throw new RuntimeException(
            'Captured player snapshot candidates do not contain '
            . 'a valid FPL gameweek ID.'
        );
    }


    /*
     * ========================================================
     * ACCOUNTING VALIDATION
     * ========================================================
     *
     * Every successfully built candidate must result in one of:
     *
     * - saved
     * - unchanged
     *
     * players_considered may be larger than candidates_built
     * because the capture service deliberately skips invalid
     * player identities rather than manufacturing evidence.
     */

    if (
        $candidatesBuilt
        !==
        (
            $saved
            +
            $unchanged
        )
    ) {

        throw new RuntimeException(
            'Player snapshot candidate capture accounting does '
            . 'not match candidates built.'
        );
    }


    if (
        $candidatesBuilt
        >
        $playersConsidered
    ) {

        throw new RuntimeException(
            'Player snapshot candidate count cannot exceed '
            . 'players considered.'
        );
    }


    /*
     * ========================================================
     * DISPLAY RESULT
     * ========================================================
     */

    echo "Local Gameweek ID: "
        . number_format(
            $gameweekId
        )
        . "<br>";


    echo "FPL Gameweek: "
        . number_format(
            $fplGameweekId
        )
        . "<br>";


    echo "Players Considered: "
        . number_format(
            $playersConsidered
        )
        . "<br>";


    echo "Candidates Built: "
        . number_format(
            $candidatesBuilt
        )
        . "<br>";


    echo "Candidates Saved: "
        . number_format(
            $saved
        )
        . "<br>";


    echo "Candidates Unchanged: "
        . number_format(
            $unchanged
        )
        . "<br><br>";


    echo "RESULT: PLAYER SNAPSHOT CANDIDATE CAPTURE COMPLETE";


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


    echo "RESULT: PLAYER SNAPSHOT CANDIDATE CAPTURE FAILED";


    exit(1);
}