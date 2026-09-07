<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Snapshot Selected Diagnostic<br>";
echo "============================================<br><br>";


$database =
    new Database();


$pdo =
    $database->getConnection();


/*
 * ============================================================
 * FIND LATEST COMPLETED GAMEWEEK
 * ============================================================
 */

$statement =
    $pdo->query(
        "
        SELECT
            id,
            fpl_gameweek_id,
            name,
            finished,
            data_checked
        FROM
            gameweeks
        WHERE
            finished = 1
        ORDER BY
            fpl_gameweek_id DESC
        LIMIT 1
        "
    );


$gameweek =
    $statement->fetch(
        PDO::FETCH_ASSOC
    );


if (
    !is_array(
        $gameweek
    )
) {

    echo "No completed gameweek found.<br>";

    exit;
}


$gameweekId =
    (int) $gameweek[
        'id'
    ];


echo "Gameweek: "
    . htmlspecialchars(
        (string) (
            $gameweek[
                'name'
            ]
            ?? ''
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Local Gameweek ID: "
    . $gameweekId
    . "<br>";


echo "FPL Gameweek ID: "
    . (int) (
        $gameweek[
            'fpl_gameweek_id'
        ]
        ?? 0
    )
    . "<br><br>";


/*
 * ============================================================
 * SNAPSHOT COVERAGE
 * ============================================================
 */

$statement =
    $pdo->prepare(
        "
        SELECT
            COUNT(*) AS total_snapshots,
            SUM(
                CASE
                    WHEN selected IS NULL
                    THEN 1
                    ELSE 0
                END
            ) AS null_selected,
            SUM(
                CASE
                    WHEN selected IS NOT NULL
                    THEN 1
                    ELSE 0
                END
            ) AS populated_selected
        FROM
            player_gameweek_snapshots
        WHERE
            gameweek_id = :gameweek_id
        "
    );


$statement->execute(
    [
        ':gameweek_id' =>
            $gameweekId
    ]
);


$coverage =
    $statement->fetch(
        PDO::FETCH_ASSOC
    );


echo "============================================<br>";
echo "Snapshot Selected Coverage<br>";
echo "============================================<br>";


echo "Total Snapshots: "
    . (int) (
        $coverage[
            'total_snapshots'
        ]
        ?? 0
    )
    . "<br>";


echo "Selected NULL: "
    . (int) (
        $coverage[
            'null_selected'
        ]
        ?? 0
    )
    . "<br>";


echo "Selected Populated: "
    . (int) (
        $coverage[
            'populated_selected'
        ]
        ?? 0
    )
    . "<br><br>";


/*
 * ============================================================
 * NULL SNAPSHOTS WITH AUTHORITATIVE HISTORY
 * ============================================================
 *
 * This is deliberately READ ONLY.
 *
 * We want to know:
 *
 * 1. Which historical snapshots have selected = NULL?
 * 2. Does player_fixture_history still contain the historical
 *    selected value for those exact players/gameweek?
 * 3. Are there multiple fixture-history rows for a player?
 * ============================================================
 */

$statement =
    $pdo->prepare(
        "
        SELECT
            pgs.id AS snapshot_id,
            pgs.player_id,
            pgs.fpl_player_id,
            p.first_name,
            p.second_name,
            pgs.selected AS snapshot_selected,
            COUNT(pfh.id) AS history_rows,
            MIN(pfh.selected) AS history_selected_min,
            MAX(pfh.selected) AS history_selected_max
        FROM
            player_gameweek_snapshots pgs
        INNER JOIN
            players p
                ON p.id = pgs.player_id
        LEFT JOIN
            player_fixture_history pfh
                ON pfh.player_id = pgs.player_id
                AND pfh.gameweek_id = pgs.gameweek_id
        WHERE
            pgs.gameweek_id = :gameweek_id
            AND
            pgs.selected IS NULL
        GROUP BY
            pgs.id,
            pgs.player_id,
            pgs.fpl_player_id,
            p.first_name,
            p.second_name,
            pgs.selected
        ORDER BY
            pgs.player_id ASC
        "
    );


$statement->execute(
    [
        ':gameweek_id' =>
            $gameweekId
    ]
);


$affectedRows =
    $statement->fetchAll(
        PDO::FETCH_ASSOC
    );


echo "============================================<br>";
echo "NULL Selected Snapshot Detail<br>";
echo "============================================<br>";


echo "Affected Snapshot Rows: "
    . count(
        $affectedRows
    )
    . "<br><br>";


foreach (
    $affectedRows
    as $row
) {

    $playerName =
        trim(
            (
                $row[
                    'first_name'
                ]
                ?? ''
            )
            . ' '
            . (
                $row[
                    'second_name'
                ]
                ?? ''
            )
        );


    echo "Snapshot ID: "
        . (int) $row[
            'snapshot_id'
        ];


    echo " | Player ID: "
        . (int) $row[
            'player_id'
        ];


    echo " | FPL ID: "
        . (int) $row[
            'fpl_player_id'
        ];


    echo " | Player: "
        . htmlspecialchars(
            $playerName,
            ENT_QUOTES,
            'UTF-8'
        );


    echo " | Snapshot Selected: NULL";


    echo " | History Rows: "
        . (int) (
            $row[
                'history_rows'
            ]
            ?? 0
        );


    echo " | History Selected Min: "
        . (
            $row[
                'history_selected_min'
            ]
            !==
            null
                ? number_format(
                    (int) $row[
                        'history_selected_min'
                    ]
                )
                : 'NULL'
        );


    echo " | History Selected Max: "
        . (
            $row[
                'history_selected_max'
            ]
            !==
            null
                ? number_format(
                    (int) $row[
                        'history_selected_max'
                    ]
                )
                : 'NULL'
        );


    echo "<br>";
}


echo "<br>";


/*
 * ============================================================
 * REPAIRABILITY SUMMARY
 * ============================================================
 */

$repairable =
    0;


$missingHistory =
    0;


$ambiguousHistory =
    0;


foreach (
    $affectedRows
    as $row
) {

    $historyRows =
        (int) (
            $row[
                'history_rows'
            ]
            ?? 0
        );


    $minimum =
        $row[
            'history_selected_min'
        ]
        ?? null;


    $maximum =
        $row[
            'history_selected_max'
        ]
        ?? null;


    if (
        $historyRows <= 0
        ||
        $minimum === null
        ||
        $maximum === null
    ) {

        $missingHistory++;

        continue;
    }


    if (
        (string) $minimum
        !==
        (string) $maximum
    ) {

        $ambiguousHistory++;

        continue;
    }


    $repairable++;
}


echo "============================================<br>";
echo "Repairability Summary<br>";
echo "============================================<br>";


echo "NULL Snapshot Rows: "
    . count(
        $affectedRows
    )
    . "<br>";


echo "Exact Historical Value Available: "
    . $repairable
    . "<br>";


echo "Missing Historical Value: "
    . $missingHistory
    . "<br>";


echo "Ambiguous Historical Value: "
    . $ambiguousHistory
    . "<br><br>";


/*
 * ============================================================
 * SAFETY CONFIRMATION
 * ============================================================
 */

echo "============================================<br>";
echo "Safety<br>";
echo "============================================<br>";

echo "READ ONLY: No INSERT, UPDATE or DELETE statements were executed.<br>";