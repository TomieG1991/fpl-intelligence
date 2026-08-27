<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Capture<br>";
echo "============================================<br><br>";


try {

    /*
     * ========================================================
     * DATABASE
     * ========================================================
     */

    $database =
        new Database();


    $connection =
        $database
            ->getConnection();


    echo "Database connection successful<br><br>";


    /*
     * ========================================================
     * CAPTURE SERVICE
     * ========================================================
     */

    $capture =
        new PlayerGameweekSnapshotCapture(
            $connection
        );


    $result =
        $capture
            ->captureLatestCompletedGameweek();


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

        echo "Capture Status: "
            . htmlspecialchars(
                $status,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";


        echo "Reason: "
            . htmlspecialchars(
                (string) (
                    $result[
                        'reason'
                    ]
                    ?? 'Unknown'
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br><br>";


        echo "RESULT: SNAPSHOT CAPTURE NOT AVAILABLE";

        exit;
    }


    echo "Gameweek: GW"
        . htmlspecialchars(
            (string) (
                $result[
                    'fpl_gameweek_id'
                ]
                ?? '—'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";


    echo "Finished: "
        . (
            !empty(
                $result[
                    'finished'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        )
        . "<br>";


    echo "Data Checked: "
        . (
            !empty(
                $result[
                    'data_checked'
                ]
                ?? false
            )
                ? 'Yes'
                : 'No'
        )
        . "<br><br>";


    echo "Players Considered: "
        . number_format(
            (int) (
                $result[
                    'players_considered'
                ]
                ?? 0
            )
        )
        . "<br>";


    echo "Snapshots Inserted: "
        . number_format(
            (int) (
                $result[
                    'inserted'
                ]
                ?? 0
            )
        )
        . "<br>";


    echo "Snapshots Already Present: "
        . number_format(
            (int) (
                $result[
                    'existing'
                ]
                ?? 0
            )
        )
        . "<br>";


    echo "Snapshots Skipped: "
        . number_format(
            (int) (
                $result[
                    'skipped'
                ]
                ?? 0
            )
        )
        . "<br><br>";


    /*
     * ========================================================
     * ACCOUNTING VALIDATION
     * ========================================================
     */

    $playersConsidered =
        (int) (
            $result[
                'players_considered'
            ]
            ?? 0
        );


    $inserted =
        (int) (
            $result[
                'inserted'
            ]
            ?? 0
        );


    $existing =
        (int) (
            $result[
                'existing'
            ]
            ?? 0
        );


    $skipped =
        (int) (
            $result[
                'skipped'
            ]
            ?? 0
        );


    if (
        $playersConsidered
        !==
        (
            $inserted
            +
            $existing
            +
            $skipped
        )
    ) {

        throw new RuntimeException(
            'Snapshot capture accounting does not match players considered'
        );
    }


    echo "RESULT: SNAPSHOT CAPTURE COMPLETE";


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


    echo "RESULT: SNAPSHOT CAPTURE FAILED";

    exit(1);
}