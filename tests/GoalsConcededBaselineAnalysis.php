<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Goals Conceded Baseline Analysis<br>";
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
 * LOAD COMPLETE GW1 APPEARANCE DATA
 * ============================================================
 */

$sql = "
    SELECT
        pfh.player_id,
        p.web_name,
        p.position,
        pfh.minutes,
        pfh.goals_conceded,
        pfh.expected_goals_conceded
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
        pfh.minutes > 0
        AND
        p.position IN ('GK', 'DEF')
    ORDER BY
        p.position ASC,
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


echo "GK / DEF Appearance Rows Loaded: "
    . count(
        $rows
    )
    . "<br><br>";


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function goalsConcededPercentile(
    array $values,
    float $percentile
): ?float {

    if (
        empty(
            $values
        )
    ) {

        return null;
    }


    sort(
        $values,
        SORT_NUMERIC
    );


    $count =
        count(
            $values
        );


    if (
        $count === 1
    ) {

        return (float) $values[0];
    }


    $index =
        (
            $count
            -
            1
        )
        *
        $percentile;


    $lower =
        (int) floor(
            $index
        );


    $upper =
        (int) ceil(
            $index
        );


    if (
        $lower === $upper
    ) {

        return (float) $values[
            $lower
        ];
    }


    $weight =
        $index
        -
        $lower;


    return (
        (float) $values[
            $lower
        ]
        *
        (
            1
            -
            $weight
        )
    )
    +
    (
        (float) $values[
            $upper
        ]
        *
        $weight
    );
}


function goalsConcededMean(
    array $values
): ?float {

    if (
        empty(
            $values
        )
    ) {

        return null;
    }


    return array_sum(
        $values
    )
    /
    count(
        $values
    );
}


function goalsConcededFormat(
    ?float $value,
    int $decimals = 2
): string {

    if (
        $value === null
    ) {

        return 'Unavailable';
    }


    return number_format(
        $value,
        $decimals
    );
}


/*
 * ============================================================
 * BUILD POSITION DATA
 * ============================================================
 */

$positionData = [

    'GK' => [],
    'DEF' => []
];


foreach (
    $rows
    as $row
) {

    $position =
        strtoupper(
            trim(
                (string) (
                    $row[
                        'position'
                    ]
                    ?? ''
                )
            )
        );


    if (
        !array_key_exists(
            $position,
            $positionData
        )
    ) {

        continue;
    }


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


    $actualGoalsConceded =
        max(
            0,
            (int) (
                $row[
                    'goals_conceded'
                ]
                ?? 0
            )
        );


    $xgc =
        is_numeric(
            $row[
                'expected_goals_conceded'
            ]
            ?? null
        )
            ? max(
                0.0,
                (float) $row[
                    'expected_goals_conceded'
                ]
            )
            : null;


    $xgcPer90 =
        (
            $xgc !== null
            &&
            $minutes > 0
        )
            ? (
                $xgc
                /
                $minutes
                *
                90
            )
            : null;


    $actualGcPer90 =
        $minutes > 0
            ? (
                $actualGoalsConceded
                /
                $minutes
                *
                90
            )
            : null;


    $positionData[
        $position
    ][] = [

        'player' =>
            $row[
                'web_name'
            ]
            ?? 'Unknown',

        'minutes' =>
            $minutes,

        'goals_conceded' =>
            $actualGoalsConceded,

        'actual_gc_per_90' =>
            $actualGcPer90,

        'expected_goals_conceded' =>
            $xgc,

        'xgc_per_90' =>
            $xgcPer90
    ];
}


/*
 * ============================================================
 * SCENARIO A
 * ALL APPEARANCE DISTRIBUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: All Appearance Distribution<br>";
echo "============================================<br><br>";


foreach (
    [
        'GK',
        'DEF'
    ]
    as $position
) {

    $xgcValues =
        [];


    $actualValues =
        [];


    foreach (
        $positionData[
            $position
        ]
        as $row
    ) {

        if (
            $row[
                'xgc_per_90'
            ]
            !== null
        ) {

            $xgcValues[] =
                $row[
                    'xgc_per_90'
                ];
        }


        if (
            $row[
                'actual_gc_per_90'
            ]
            !== null
        ) {

            $actualValues[] =
                $row[
                    'actual_gc_per_90'
                ];
        }
    }


    echo $position
        . " xGC / 90<br>";


    echo "Sample: "
        . count(
            $xgcValues
        )
        . "<br>";


    echo "Mean: "
        . goalsConcededFormat(
            goalsConcededMean(
                $xgcValues
            )
        )
        . "<br>";


    echo "P10: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.10
            )
        )
        . "<br>";


    echo "P25: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.25
            )
        )
        . "<br>";


    echo "Median: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.50
            )
        )
        . "<br>";


    echo "P75: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.75
            )
        )
        . "<br>";


    echo "P90: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.90
            )
        )
        . "<br>";


    echo "Actual GC / 90 Mean: "
        . goalsConcededFormat(
            goalsConcededMean(
                $actualValues
            )
        )
        . "<br><br>";
}


/*
 * ============================================================
 * SCENARIO B
 * 60+ MINUTE DISTRIBUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: 60+ Minute Distribution<br>";
echo "============================================<br><br>";


foreach (
    [
        'GK',
        'DEF'
    ]
    as $position
) {

    $xgcValues =
        [];


    $actualValues =
        [];


    foreach (
        $positionData[
            $position
        ]
        as $row
    ) {

        if (
            $row[
                'minutes'
            ]
            <
            60
        ) {

            continue;
        }


        if (
            $row[
                'xgc_per_90'
            ]
            !== null
        ) {

            $xgcValues[] =
                $row[
                    'xgc_per_90'
                ];
        }


        if (
            $row[
                'actual_gc_per_90'
            ]
            !== null
        ) {

            $actualValues[] =
                $row[
                    'actual_gc_per_90'
                ];
        }
    }


    echo $position
        . " xGC / 90<br>";


    echo "Sample: "
        . count(
            $xgcValues
        )
        . "<br>";


    echo "Mean: "
        . goalsConcededFormat(
            goalsConcededMean(
                $xgcValues
            )
        )
        . "<br>";


    echo "P10: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.10
            )
        )
        . "<br>";


    echo "P25: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.25
            )
        )
        . "<br>";


    echo "Median: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.50
            )
        )
        . "<br>";


    echo "P75: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.75
            )
        )
        . "<br>";


    echo "P90: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.90
            )
        )
        . "<br>";


    echo "Actual GC / 90 Mean: "
        . goalsConcededFormat(
            goalsConcededMean(
                $actualValues
            )
        )
        . "<br><br>";
}


/*
 * ============================================================
 * SCENARIO C
 * 75+ MINUTE DISTRIBUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: 75+ Minute Distribution<br>";
echo "============================================<br><br>";


foreach (
    [
        'GK',
        'DEF'
    ]
    as $position
) {

    $xgcValues =
        [];


    foreach (
        $positionData[
            $position
        ]
        as $row
    ) {

        if (
            $row[
                'minutes'
            ]
            <
            75
        ) {

            continue;
        }


        if (
            $row[
                'xgc_per_90'
            ]
            !== null
        ) {

            $xgcValues[] =
                $row[
                    'xgc_per_90'
                ];
        }
    }


    echo $position
        . " xGC / 90<br>";


    echo "Sample: "
        . count(
            $xgcValues
        )
        . "<br>";


    echo "Mean: "
        . goalsConcededFormat(
            goalsConcededMean(
                $xgcValues
            )
        )
        . "<br>";


    echo "Median: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.50
            )
        )
        . "<br>";


    echo "P75: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.75
            )
        )
        . "<br>";


    echo "P90: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $xgcValues,
                0.90
            )
        )
        . "<br><br>";
}


/*
 * ============================================================
 * SCENARIO D
 * HIGHEST XGC / 90
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Highest 60+ Minute xGC / 90<br>";
echo "============================================<br><br>";


foreach (
    [
        'GK',
        'DEF'
    ]
    as $position
) {

    $eligibleRows =
        array_values(
            array_filter(
                $positionData[
                    $position
                ],
                function (
                    array $row
                ): bool {

                    return (
                        $row[
                            'minutes'
                        ]
                        >=
                        60
                        &&
                        $row[
                            'xgc_per_90'
                        ]
                        !==
                        null
                    );
                }
            )
        );


    usort(
        $eligibleRows,
        function (
            array $a,
            array $b
        ): int {

            return (
                (float) $b[
                    'xgc_per_90'
                ]
            )
            <=>
            (
                (float) $a[
                    'xgc_per_90'
                ]
            );
        }
    );


    echo $position
        . "<br>";


    foreach (
        array_slice(
            $eligibleRows,
            0,
            10
        )
        as $index => $row
    ) {

        echo (
            $index
            +
            1
        )
            . '. '
            . htmlspecialchars(
                (string) $row[
                    'player'
                ],
                ENT_QUOTES,
                'UTF-8'
            )
            . ' — '
            . $row[
                'minutes'
            ]
            . ' mins'
            . ' — xGC '
            . goalsConcededFormat(
                $row[
                    'expected_goals_conceded'
                ],
                2
            )
            . ' — '
            . goalsConcededFormat(
                $row[
                    'xgc_per_90'
                ],
                2
            )
            . "/90<br>";
    }


    echo "<br>";
}


/*
 * ============================================================
 * SCENARIO E
 * LOWEST XGC / 90
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Lowest 60+ Minute xGC / 90<br>";
echo "============================================<br><br>";


foreach (
    [
        'GK',
        'DEF'
    ]
    as $position
) {

    $eligibleRows =
        array_values(
            array_filter(
                $positionData[
                    $position
                ],
                function (
                    array $row
                ): bool {

                    return (
                        $row[
                            'minutes'
                        ]
                        >=
                        60
                        &&
                        $row[
                            'xgc_per_90'
                        ]
                        !==
                        null
                    );
                }
            )
        );


    usort(
        $eligibleRows,
        function (
            array $a,
            array $b
        ): int {

            return (
                (float) $a[
                    'xgc_per_90'
                ]
            )
            <=>
            (
                (float) $b[
                    'xgc_per_90'
                ]
            );
        }
    );


    echo $position
        . "<br>";


    foreach (
        array_slice(
            $eligibleRows,
            0,
            10
        )
        as $index => $row
    ) {

        echo (
            $index
            +
            1
        )
            . '. '
            . htmlspecialchars(
                (string) $row[
                    'player'
                ],
                ENT_QUOTES,
                'UTF-8'
            )
            . ' — '
            . $row[
                'minutes'
            ]
            . ' mins'
            . ' — xGC '
            . goalsConcededFormat(
                $row[
                    'expected_goals_conceded'
                ],
                2
            )
            . ' — '
            . goalsConcededFormat(
                $row[
                    'xgc_per_90'
                ],
                2
            )
            . "/90<br>";
    }


    echo "<br>";
}


/*
 * ============================================================
 * SCENARIO F
 * BASELINE CANDIDATES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Baseline Candidates<br>";
echo "============================================<br><br>";


foreach (
    [
        'GK',
        'DEF'
    ]
    as $position
) {

    $sixtyPlus =
        [];


    $seventyFivePlus =
        [];


    foreach (
        $positionData[
            $position
        ]
        as $row
    ) {

        if (
            $row[
                'xgc_per_90'
            ]
            ===
            null
        ) {

            continue;
        }


        if (
            $row[
                'minutes'
            ]
            >=
            60
        ) {

            $sixtyPlus[] =
                $row[
                    'xgc_per_90'
                ];
        }


        if (
            $row[
                'minutes'
            ]
            >=
            75
        ) {

            $seventyFivePlus[] =
                $row[
                    'xgc_per_90'
                ];
        }
    }


    echo $position
        . "<br>";


    echo "60+ Mean: "
        . goalsConcededFormat(
            goalsConcededMean(
                $sixtyPlus
            )
        )
        . "<br>";


    echo "60+ Median: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $sixtyPlus,
                0.50
            )
        )
        . "<br>";


    echo "75+ Mean: "
        . goalsConcededFormat(
            goalsConcededMean(
                $seventyFivePlus
            )
        )
        . "<br>";


    echo "75+ Median: "
        . goalsConcededFormat(
            goalsConcededPercentile(
                $seventyFivePlus,
                0.50
            )
        )
        . "<br><br>";
}


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "============================================<br>";
echo "Goals Conceded Baseline Analysis Complete<br>";
echo "============================================<br>";