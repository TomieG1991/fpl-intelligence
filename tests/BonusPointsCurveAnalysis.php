<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Bonus Points Curve Analysis<br>";
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
 *
 * Only actual appearances are included.
 *
 * We retain position so the overall empirical curve can also
 * be compared against positional behaviour.
 */

$sql = "
    SELECT
        pfh.fixture_id,
        pfh.player_id,
        p.web_name,
        p.position,
        pfh.minutes,
        pfh.bps,
        pfh.bonus
    FROM
        player_fixture_history pfh
    INNER JOIN
        players p
            ON p.id = pfh.player_id
    WHERE
        pfh.gameweek_id IN (
            SELECT
                id
            FROM
                gameweeks
            WHERE
                fpl_gameweek_id = 1
        )
        AND pfh.minutes > 0
    ORDER BY
        pfh.bps ASC,
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


echo "GW1 Appearance Rows Loaded: "
    . count(
        $rows
    )
    . "<br><br>";


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function bonusCurveAverage(
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


function bonusCurveFormat(
    ?float $value,
    int $decimals = 3
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
 * Return all rows whose BPS lies within the requested
 * inclusive range.
 */

function bonusCurveRowsBetween(
    array $rows,
    int $minimum,
    int $maximum
): array {

    return array_values(
        array_filter(
            $rows,
            function (
                array $row
            ) use (
                $minimum,
                $maximum
            ): bool {

                $bps =
                    (int) (
                        $row[
                            'bps'
                        ]
                        ?? 0
                    );


                return (
                    $bps
                    >=
                    $minimum
                    &&
                    $bps
                    <=
                    $maximum
                );
            }
        )
    );
}


/*
 * Build empirical bonus statistics from a collection
 * of player-fixture rows.
 */

function bonusCurveStats(
    array $rows
): array {

    $sample =
        count(
            $rows
        );


    $bonusPlayers =
        0;


    $bonusPoints =
        0;


    $bonusValues =
        [];


    foreach (
        $rows
        as $row
    ) {

        $bonus =
            max(
                0,
                min(
                    3,
                    (int) (
                        $row[
                            'bonus'
                        ]
                        ?? 0
                    )
                )
            );


        if (
            $bonus > 0
        ) {

            $bonusPlayers++;
        }


        $bonusPoints +=
            $bonus;


        $bonusValues[] =
            $bonus;
    }


    return [

        'sample' =>
            $sample,

        'bonus_players' =>
            $bonusPlayers,

        'bonus_points' =>
            $bonusPoints,

        'hit_rate' =>
            $sample > 0
                ? (
                    $bonusPlayers
                    /
                    $sample
                    *
                    100
                )
                : null,

        'average_bonus' =>
            bonusCurveAverage(
                $bonusValues
            )
    ];
}


/*
 * ============================================================
 * BUILD EXACT BPS GROUPS
 * ============================================================
 */

$exactGroups =
    [];


foreach (
    $rows
    as $row
) {

    $bps =
        (int) (
            $row[
                'bps'
            ]
            ?? 0
        );


    if (
        !isset(
            $exactGroups[
                $bps
            ]
        )
    ) {

        $exactGroups[
            $bps
        ] =
            [];
    }


    $exactGroups[
        $bps
    ][] =
        $row;
}


ksort(
    $exactGroups,
    SORT_NUMERIC
);


/*
 * ============================================================
 * SCENARIO A
 * EXACT BPS CURVE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Exact BPS To Bonus Curve<br>";
echo "============================================<br><br>";


echo "BPS | Sample | Bonus Players | Hit Rate | Avg Bonus<br>";
echo "---------------------------------------------------<br>";


foreach (
    $exactGroups
    as $bps => $groupRows
) {

    /*
     * Focus the printed curve around the area relevant to
     * bonus competition.
     */

    if (
        $bps < 15
    ) {

        continue;
    }


    $stats =
        bonusCurveStats(
            $groupRows
        );


    echo $bps
        . ' | '
        . $stats[
            'sample'
        ]
        . ' | '
        . $stats[
            'bonus_players'
        ]
        . ' | '
        . bonusCurveFormat(
            $stats[
                'hit_rate'
            ],
            2
        )
        . '% | '
        . bonusCurveFormat(
            $stats[
                'average_bonus'
            ]
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * FIVE-BPS LOCAL NEIGHBOURHOOD
 * ============================================================
 *
 * Exact BPS samples are very small after one gameweek.
 *
 * We therefore calculate a centred ±2 BPS neighbourhood:
 *
 * projected BPS 30
 * -> empirical rows from BPS 28 through 32
 *
 * This gives us a first smoothed estimate without imposing
 * an arbitrary mathematical curve.
 */

echo "============================================<br>";
echo "Scenario B: Smoothed Five-BPS Neighbourhood<br>";
echo "============================================<br><br>";


echo "Centre BPS | Range | Sample | Hit Rate | Avg Bonus<br>";
echo "---------------------------------------------------<br>";


for (
    $centre = 20;
    $centre <= 50;
    $centre++
) {

    $minimum =
        $centre
        -
        2;


    $maximum =
        $centre
        +
        2;


    $neighbourRows =
        bonusCurveRowsBetween(
            $rows,
            $minimum,
            $maximum
        );


    $stats =
        bonusCurveStats(
            $neighbourRows
        );


    echo $centre
        . ' | '
        . $minimum
        . '-'
        . $maximum
        . ' | '
        . $stats[
            'sample'
        ]
        . ' | '
        . bonusCurveFormat(
            $stats[
                'hit_rate'
            ],
            2
        )
        . '% | '
        . bonusCurveFormat(
            $stats[
                'average_bonus'
            ]
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * WIDER SEVEN-BPS NEIGHBOURHOOD
 * ============================================================
 *
 * A ±3 BPS window gives us a second view with a larger sample.
 *
 * Comparing this with Scenario B will tell us how noisy the
 * current one-gameweek curve is.
 */

echo "============================================<br>";
echo "Scenario C: Smoothed Seven-BPS Neighbourhood<br>";
echo "============================================<br><br>";


echo "Centre BPS | Range | Sample | Hit Rate | Avg Bonus<br>";
echo "---------------------------------------------------<br>";


for (
    $centre = 20;
    $centre <= 50;
    $centre++
) {

    $minimum =
        $centre
        -
        3;


    $maximum =
        $centre
        +
        3;


    $neighbourRows =
        bonusCurveRowsBetween(
            $rows,
            $minimum,
            $maximum
        );


    $stats =
        bonusCurveStats(
            $neighbourRows
        );


    echo $centre
        . ' | '
        . $minimum
        . '-'
        . $maximum
        . ' | '
        . $stats[
            'sample'
        ]
        . ' | '
        . bonusCurveFormat(
            $stats[
                'hit_rate'
            ],
            2
        )
        . '% | '
        . bonusCurveFormat(
            $stats[
                'average_bonus'
            ]
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * POSITION-SPECIFIC CURVES
 * ============================================================
 *
 * We do not necessarily want position-specific final bonus
 * curves because every position competes in the same fixture.
 *
 * This diagnostic checks whether position is nevertheless
 * carrying important information not captured by BPS alone.
 */

echo "============================================<br>";
echo "Scenario D: Position-Specific BPS Bands<br>";
echo "============================================<br><br>";


$positionBands = [

    '20-24' => [
        20,
        24
    ],

    '25-29' => [
        25,
        29
    ],

    '30-34' => [
        30,
        34
    ],

    '35-39' => [
        35,
        39
    ],

    '40+' => [
        40,
        PHP_INT_MAX
    ]
];


foreach (
    [
        'GK',
        'DEF',
        'MID',
        'FWD'
    ]
    as $position
) {

    echo $position
        . "<br>";


    $positionRows =
        array_values(
            array_filter(
                $rows,
                function (
                    array $row
                ) use (
                    $position
                ): bool {

                    return (
                        strtoupper(
                            trim(
                                (string) (
                                    $row[
                                        'position'
                                    ]
                                    ?? ''
                                )
                            )
                        )
                        ===
                        $position
                    );
                }
            )
        );


    foreach (
        $positionBands
        as $label => $range
    ) {

        $bandRows =
            bonusCurveRowsBetween(
                $positionRows,
                $range[0],
                $range[1]
            );


        $stats =
            bonusCurveStats(
                $bandRows
            );


        echo $label
            . ' — Sample '
            . $stats[
                'sample'
            ]
            . ' — Hit '
            . bonusCurveFormat(
                $stats[
                    'hit_rate'
                ],
                2
            )
            . '% — Avg Bonus '
            . bonusCurveFormat(
                $stats[
                    'average_bonus'
                ]
            )
            . "<br>";
    }


    echo "<br>";
}


/*
 * ============================================================
 * SCENARIO E
 * CANDIDATE MODEL ANCHORS
 * ============================================================
 *
 * These are NOT production values.
 *
 * We inspect selected BPS centres using both the ±2 and ±3
 * neighbourhoods. This should make the eventual anchor choice
 * much easier to justify.
 */

echo "============================================<br>";
echo "Scenario E: Candidate Curve Anchors<br>";
echo "============================================<br><br>";


$candidateCentres = [

    20,
    22,
    24,
    25,
    27,
    30,
    32,
    35,
    37,
    40,
    42,
    45,
    50
];


foreach (
    $candidateCentres
    as $centre
) {

    $fiveRows =
        bonusCurveRowsBetween(
            $rows,
            $centre - 2,
            $centre + 2
        );


    $sevenRows =
        bonusCurveRowsBetween(
            $rows,
            $centre - 3,
            $centre + 3
        );


    $fiveStats =
        bonusCurveStats(
            $fiveRows
        );


    $sevenStats =
        bonusCurveStats(
            $sevenRows
        );


    echo "BPS "
        . $centre
        . "<br>";


    echo "±2 Sample: "
        . $fiveStats[
            'sample'
        ]
        . "<br>";


    echo "±2 Hit Rate: "
        . bonusCurveFormat(
            $fiveStats[
                'hit_rate'
            ],
            2
        )
        . "%<br>";


    echo "±2 Avg Bonus: "
        . bonusCurveFormat(
            $fiveStats[
                'average_bonus'
            ]
        )
        . "<br>";


    echo "±3 Sample: "
        . $sevenStats[
            'sample'
        ]
        . "<br>";


    echo "±3 Hit Rate: "
        . bonusCurveFormat(
            $sevenStats[
                'hit_rate'
            ],
            2
        )
        . "%<br>";


    echo "±3 Avg Bonus: "
        . bonusCurveFormat(
            $sevenStats[
                'average_bonus'
            ]
        )
        . "<br><br>";
}


/*
 * ============================================================
 * SCENARIO F
 * HIGH-BPS FIXTURE CONTEXT
 * ============================================================
 *
 * Look specifically at every player reaching at least 28 BPS.
 * This shows how the same or similar BPS totals translated
 * into different awards depending on fixture competition.
 */

echo "============================================<br>";
echo "Scenario F: High-BPS Fixture Context<br>";
echo "============================================<br><br>";


$highBpsRows =
    array_values(
        array_filter(
            $rows,
            function (
                array $row
            ): bool {

                return (
                    (int) (
                        $row[
                            'bps'
                        ]
                        ?? 0
                    )
                )
                >=
                28;
            }
        )
    );


usort(
    $highBpsRows,
    function (
        array $a,
        array $b
    ): int {

        $bpsComparison =
            (
                (int) $a[
                    'bps'
                ]
            )
            <=>
            (
                (int) $b[
                    'bps'
                ]
            );


        if (
            $bpsComparison !== 0
        ) {

            return $bpsComparison;
        }


        return (
            (int) $a[
                'fixture_id'
            ]
        )
        <=>
        (
            (int) $b[
                'fixture_id'
            ]
        );
    }
);


foreach (
    $highBpsRows
    as $row
) {

    echo "BPS "
        . (int) $row[
            'bps'
        ]
        . ' — Fixture '
        . (int) $row[
            'fixture_id'
        ]
        . ' — '
        . htmlspecialchars(
            (string) (
                $row[
                    'web_name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . ' ('
        . htmlspecialchars(
            (string) (
                $row[
                    'position'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . ')'
        . ' — Bonus '
        . (int) (
            $row[
                'bonus'
            ]
            ?? 0
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * ZERO-BONUS HIGH-BPS CASES
 * ============================================================
 *
 * These are especially useful because they demonstrate the
 * danger of treating BPS as an automatic bonus threshold.
 */

echo "============================================<br>";
echo "Scenario G: High BPS Without Bonus<br>";
echo "============================================<br><br>";


$highBpsNoBonus =
    array_values(
        array_filter(
            $rows,
            function (
                array $row
            ): bool {

                return (
                    (
                        (int) (
                            $row[
                                'bps'
                            ]
                            ?? 0
                        )
                    )
                    >=
                    25
                    &&
                    (
                        (int) (
                            $row[
                                'bonus'
                            ]
                            ?? 0
                        )
                    )
                    ===
                    0
                );
            }
        )
    );


usort(
    $highBpsNoBonus,
    function (
        array $a,
        array $b
    ): int {

        return (
            (int) (
                $b[
                    'bps'
                ]
                ?? 0
            )
        )
        <=>
        (
            (int) (
                $a[
                    'bps'
                ]
                ?? 0
            )
        );
    }
);


foreach (
    $highBpsNoBonus
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
        . htmlspecialchars(
            (string) (
                $row[
                    'position'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . ' — Fixture '
        . (int) (
            $row[
                'fixture_id'
            ]
            ?? 0
        )
        . ' — BPS '
        . (int) (
            $row[
                'bps'
            ]
            ?? 0
        )
        . ' — Bonus 0'
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "============================================<br>";
echo "Bonus Curve Analysis Complete<br>";
echo "============================================<br>";