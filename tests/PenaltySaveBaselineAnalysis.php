<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Penalty Save Baseline Analysis<br>";
echo "============================================<br><br>";


/*
 * ============================================================
 * DATABASE
 * ============================================================
 */

$database =
    new Database();


$connection =
    $database
        ->getConnection();


/*
 * ============================================================
 * LOAD COMPLETE GW1 GOALKEEPER APPEARANCES
 * ============================================================
 */

$sql = "
    SELECT
        pfh.player_id,
        p.web_name,
        pfh.minutes,
        pfh.penalties_saved
    FROM
        player_fixture_history pfh
    INNER JOIN
        players p
            ON p.id = pfh.player_id
    INNER JOIN
        gameweeks g
            ON g.id = pfh.gameweek_id
    WHERE
        g.fpl_gameweek_id = 1
        AND
        p.position = 'GK'
        AND
        pfh.minutes > 0
    ORDER BY
        pfh.player_id ASC
";


$statement =
    $connection
        ->prepare(
            $sql
        );


$statement
    ->execute();


$rows =
    $statement
        ->fetchAll(
            PDO::FETCH_ASSOC
        );


echo "GK Appearance Rows Loaded: "
    . count(
        $rows
    )
    . "<br><br>";


/*
 * ============================================================
 * AGGREGATES
 * ============================================================
 */

$totalAppearances =
    count(
        $rows
    );


$totalMinutes =
    0;


$totalPenaltySaves =
    0;


$goalkeepersWithPenaltySave =
    0;


foreach (
    $rows
    as $row
) {

    $minutes =
        max(
            0,
            (int) (
                $row[
                    'minutes'
                ]
                ?? 0
            )
        );


    $penaltySaves =
        max(
            0,
            (int) (
                $row[
                    'penalties_saved'
                ]
                ?? 0
            )
        );


    $totalMinutes +=
        $minutes;


    $totalPenaltySaves +=
        $penaltySaves;


    if (
        $penaltySaves > 0
    ) {

        $goalkeepersWithPenaltySave++;
    }
}


/*
 * ============================================================
 * SCENARIO A
 * RAW FREQUENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Raw Penalty Save Frequency<br>";
echo "============================================<br><br>";


echo "Goalkeeper Appearances: "
    . $totalAppearances
    . "<br>";


echo "Total Goalkeeper Minutes: "
    . $totalMinutes
    . "<br>";


echo "Total Penalties Saved: "
    . $totalPenaltySaves
    . "<br>";


echo "Goalkeepers With A Penalty Save: "
    . $goalkeepersWithPenaltySave
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * APPEARANCE RATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Penalty Saves Per Appearance<br>";
echo "============================================<br><br>";


$penaltySavesPerAppearance =
    $totalAppearances > 0
        ? (
            $totalPenaltySaves
            /
            $totalAppearances
        )
        : 0.0;


$appearanceHitRate =
    $totalAppearances > 0
        ? (
            $goalkeepersWithPenaltySave
            /
            $totalAppearances
            *
            100
        )
        : 0.0;


echo "Penalty Saves / Appearance: "
    . number_format(
        $penaltySavesPerAppearance,
        4
    )
    . "<br>";


echo "Appearance Hit Rate: "
    . number_format(
        $appearanceHitRate,
        2
    )
    . "%<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * PER-90 RATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Penalty Saves Per 90<br>";
echo "============================================<br><br>";


$penaltySavesPer90 =
    $totalMinutes > 0
        ? (
            $totalPenaltySaves
            /
            $totalMinutes
            *
            90
        )
        : 0.0;


echo "Penalty Saves / 90: "
    . number_format(
        $penaltySavesPer90,
        4
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * NAIVE EXPECTED FPL VALUE
 * ============================================================
 *
 * Official FPL scoring:
 *
 * +5 points for a penalty saved.
 *
 * This is deliberately only a naive baseline diagnostic.
 * It is NOT yet a production model.
 */

echo "============================================<br>";
echo "Scenario D: Naive Expected FPL Value<br>";
echo "============================================<br><br>";


$expectedPenaltySavePointsPerAppearance =
    $penaltySavesPerAppearance
    *
    5;


$expectedPenaltySavePointsPer90 =
    $penaltySavesPer90
    *
    5;


echo "Naive Expected Points / Appearance: "
    . number_format(
        $expectedPenaltySavePointsPerAppearance,
        4
    )
    . "<br>";


echo "Naive Expected Points / 90: "
    . number_format(
        $expectedPenaltySavePointsPer90,
        4
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * INDIVIDUAL PENALTY SAVES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Goalkeepers With Penalty Saves<br>";
echo "============================================<br><br>";


$penaltySaveRows =
    array_values(
        array_filter(
            $rows,
            function (
                array $row
            ): bool {

                return (
                    (int) (
                        $row[
                            'penalties_saved'
                        ]
                        ?? 0
                    )
                )
                >
                0;
            }
        )
    );


if (
    empty(
        $penaltySaveRows
    )
) {

    echo "No GW1 goalkeeper penalty saves recorded.<br>";

} else {

    foreach (
        $penaltySaveRows
        as $row
    ) {

        echo htmlspecialchars(
            (string) (
                $row[
                    'web_name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
            . ' — '
            . (int) (
                $row[
                    'minutes'
                ]
                ?? 0
            )
            . ' mins'
            . ' — '
            . (int) (
                $row[
                    'penalties_saved'
                ]
                ?? 0
            )
            . ' saved'
            . "<br>";
    }
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * CONSERVATIVE PRIOR CANDIDATES
 * ============================================================
 *
 * We compare a few capped candidate priors against the naive
 * GW1 estimate.
 */

echo "============================================<br>";
echo "Scenario F: Conservative Prior Candidates<br>";
echo "============================================<br><br>";


$naive =
    $expectedPenaltySavePointsPer90;


$candidates = [
    0.00,
    0.05,
    0.10,
    0.15,
    0.20
];


echo "Naive GW1 Expected Points / 90: "
    . number_format(
        $naive,
        4
    )
    . "<br><br>";


foreach (
    $candidates
    as $candidate
) {

    echo "Candidate Prior: "
        . number_format(
            $candidate,
            2
        )
        . " xP / 90";

    if (
        $candidate <= $naive
    ) {

        echo " — conservative versus GW1";

    } else {

        echo " — above GW1 naive rate";
    }


    echo "<br>";
}


echo "<br>";


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "============================================<br>";
echo "Penalty Save Baseline Analysis Complete<br>";
echo "============================================<br>";