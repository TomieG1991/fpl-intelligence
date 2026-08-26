<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Defensive Contribution Baseline Analysis<br>";
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
 * LOAD CURRENT FIXTURE HISTORY
 * ============================================================
 *
 * We analyse underlying defensive actions rather than awarded
 * defensive-contribution points.
 *
 * DEF:
 * CBIT =
 * clearances_blocks_interceptions + tackles
 *
 * MID / FWD:
 * CBIRT =
 * clearances_blocks_interceptions + tackles + recoveries
 *
 * Only genuine appearances are considered.
 */

$sql = "
    SELECT
        p.id AS player_id,
        p.web_name,
        p.position,
        pfh.minutes,
        pfh.clearances_blocks_interceptions,
        pfh.tackles,
        pfh.recoveries
    FROM
        player_fixture_history pfh
    INNER JOIN
        players p
            ON p.id = pfh.player_id
    WHERE
        pfh.minutes > 0
    ORDER BY
        p.position ASC,
        p.web_name ASC
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


echo "Appearance Rows Loaded: "
    . count(
        $rows
    )
    . "<br><br>";


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function dcPercentile(
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


    $fraction =
        $index
        -
        $lower;


    return (
        (float) $values[
            $lower
        ]
    )
    +
    (
        (
            (float) $values[
                $upper
            ]
            -
            (float) $values[
                $lower
            ]
        )
        *
        $fraction
    );
}


function dcAverage(
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


function dcFormat(
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


function dcBuildStats(
    array $values
): array {

    return [

        'count' =>
            count(
                $values
            ),

        'mean' =>
            dcAverage(
                $values
            ),

        'p10' =>
            dcPercentile(
                $values,
                0.10
            ),

        'p25' =>
            dcPercentile(
                $values,
                0.25
            ),

        'median' =>
            dcPercentile(
                $values,
                0.50
            ),

        'p75' =>
            dcPercentile(
                $values,
                0.75
            ),

        'p90' =>
            dcPercentile(
                $values,
                0.90
            ),

        'maximum' =>
            empty(
                $values
            )
                ? null
                : max(
                    $values
                )
    ];
}


function dcPrintStats(
    string $label,
    array $stats
): void {

    echo $label
        . "<br>";


    echo "Sample: "
        . $stats[
            'count'
        ]
        . "<br>";


    echo "Mean: "
        . dcFormat(
            $stats[
                'mean'
            ]
        )
        . "<br>";


    echo "P10: "
        . dcFormat(
            $stats[
                'p10'
            ]
        )
        . "<br>";


    echo "P25: "
        . dcFormat(
            $stats[
                'p25'
            ]
        )
        . "<br>";


    echo "Median: "
        . dcFormat(
            $stats[
                'median'
            ]
        )
        . "<br>";


    echo "P75: "
        . dcFormat(
            $stats[
                'p75'
            ]
        )
        . "<br>";


    echo "P90: "
        . dcFormat(
            $stats[
                'p90'
            ]
        )
        . "<br>";


    echo "Maximum: "
        . dcFormat(
            $stats[
                'maximum'
            ]
        )
        . "<br><br>";
}


/*
 * ============================================================
 * BUILD PER-90 DATASETS
 * ============================================================
 */

$datasets = [

    'DEF' => [

        'all' =>
            [],

        '60_plus' =>
            [],

        '75_plus' =>
            []
    ],

    'MID' => [

        'all' =>
            [],

        '60_plus' =>
            [],

        '75_plus' =>
            []
    ],

    'FWD' => [

        'all' =>
            [],

        '60_plus' =>
            [],

        '75_plus' =>
            []
    ]
];


$playerDiagnostics =
    [];


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
        !isset(
            $datasets[
                $position
            ]
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


    if (
        $minutes <= 0
    ) {

        continue;
    }


    $cbi =
        max(
            0,
            (int) (
                $row[
                    'clearances_blocks_interceptions'
                ]
                ?? 0
            )
        );


    $tackles =
        max(
            0,
            (int) (
                $row[
                    'tackles'
                ]
                ?? 0
            )
        );


    $recoveries =
        max(
            0,
            (int) (
                $row[
                    'recoveries'
                ]
                ?? 0
            )
        );


    $cbit =
        $cbi
        +
        $tackles;


    $cbirt =
        $cbit
        +
        $recoveries;


    $actions =
        $position === 'DEF'
            ? $cbit
            : $cbirt;


    $per90 =
        (
            $actions
            /
            $minutes
        )
        *
        90;


    $datasets[
        $position
    ][
        'all'
    ][] =
        $per90;


    if (
        $minutes >= 60
    ) {

        $datasets[
            $position
        ][
            '60_plus'
        ][] =
            $per90;
    }


    if (
        $minutes >= 75
    ) {

        $datasets[
            $position
        ][
            '75_plus'
        ][] =
            $per90;
    }


    $playerDiagnostics[] = [

        'player_id' =>
            (int) $row[
                'player_id'
            ],

        'web_name' =>
            (string) (
                $row[
                    'web_name'
                ]
                ?? 'Unknown'
            ),

        'position' =>
            $position,

        'minutes' =>
            $minutes,

        'actions' =>
            $actions,

        'per90' =>
            $per90
    ];
}


/*
 * ============================================================
 * SCENARIO A
 * POSITION DISTRIBUTIONS — ALL APPEARANCES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: All Appearance Distribution<br>";
echo "============================================<br><br>";


foreach (
    [
        'DEF',
        'MID',
        'FWD'
    ]
    as $position
) {

    $metric =
        $position === 'DEF'
            ? 'CBIT / 90'
            : 'CBIRT / 90';


    dcPrintStats(
        $position
        . ' '
        . $metric,
        dcBuildStats(
            $datasets[
                $position
            ][
                'all'
            ]
        )
    );
}


/*
 * ============================================================
 * SCENARIO B
 * 60+ MINUTE DISTRIBUTION
 * ============================================================
 *
 * This should be more useful for baseline selection because
 * very short substitute appearances can create extreme per-90
 * values.
 */

echo "============================================<br>";
echo "Scenario B: 60+ Minute Distribution<br>";
echo "============================================<br><br>";


foreach (
    [
        'DEF',
        'MID',
        'FWD'
    ]
    as $position
) {

    $metric =
        $position === 'DEF'
            ? 'CBIT / 90'
            : 'CBIRT / 90';


    dcPrintStats(
        $position
        . ' '
        . $metric,
        dcBuildStats(
            $datasets[
                $position
            ][
                '60_plus'
            ]
        )
    );
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
        'DEF',
        'MID',
        'FWD'
    ]
    as $position
) {

    $metric =
        $position === 'DEF'
            ? 'CBIT / 90'
            : 'CBIRT / 90';


    dcPrintStats(
        $position
        . ' '
        . $metric,
        dcBuildStats(
            $datasets[
                $position
            ][
                '75_plus'
            ]
        )
    );
}


/*
 * ============================================================
 * SCENARIO D
 * THRESHOLD FREQUENCY
 * ============================================================
 *
 * How often did players who played at least 60 minutes
 * actually reach the FPL threshold?
 */

echo "============================================<br>";
echo "Scenario D: Actual Threshold Frequency<br>";
echo "============================================<br><br>";


foreach (
    [
        'DEF',
        'MID',
        'FWD'
    ]
    as $position
) {

    $threshold =
        $position === 'DEF'
            ? 10
            : 12;


    $eligible =
        0;


    $thresholdHits =
        0;


    foreach (
        $playerDiagnostics
        as $player
    ) {

        if (
            $player[
                'position'
            ]
            !==
            $position
            ||
            $player[
                'minutes'
            ]
            < 60
        ) {

            continue;
        }


        $eligible++;


        if (
            $player[
                'actions'
            ]
            >=
            $threshold
        ) {

            $thresholdHits++;
        }
    }


    $hitRate =
        $eligible > 0
            ? (
                $thresholdHits
                /
                $eligible
                *
                100
            )
            : 0;


    echo $position
        . "<br>";


    echo "Threshold: "
        . $threshold
        . "<br>";


    echo "60+ Minute Players: "
        . $eligible
        . "<br>";


    echo "Threshold Hits: "
        . $thresholdHits
        . "<br>";


    echo "Observed Hit Rate: "
        . number_format(
            $hitRate,
            2
        )
        . "%<br><br>";
}


/*
 * ============================================================
 * SCENARIO E
 * TOP REAL PERFORMERS
 * ============================================================
 *
 * Useful for spotting obvious outliers or data problems.
 */

echo "============================================<br>";
echo "Scenario E: Highest 60+ Minute Rates<br>";
echo "============================================<br><br>";


foreach (
    [
        'DEF',
        'MID',
        'FWD'
    ]
    as $position
) {

    $positionPlayers =
        array_values(
            array_filter(
                $playerDiagnostics,
                function (
                    array $player
                ) use (
                    $position
                ): bool {

                    return (
                        $player[
                            'position'
                        ]
                        ===
                        $position
                        &&
                        $player[
                            'minutes'
                        ]
                        >=
                        60
                    );
                }
            )
        );


    usort(
        $positionPlayers,
        function (
            array $a,
            array $b
        ): int {

            return $b[
                'per90'
            ]
            <=>
            $a[
                'per90'
            ];
        }
    );


    $positionPlayers =
        array_slice(
            $positionPlayers,
            0,
            10
        );


    echo $position
        . "<br>";


    foreach (
        $positionPlayers
        as $index => $player
    ) {

        echo (
            $index
            +
            1
        )
            . '. '
            . htmlspecialchars(
                $player[
                    'web_name'
                ],
                ENT_QUOTES,
                'UTF-8'
            )
            . ' — '
            . $player[
                'minutes'
            ]
            . ' mins — '
            . $player[
                'actions'
            ]
            . ' actions — '
            . number_format(
                $player[
                    'per90'
                ],
                2
            )
            . '/90'
            . "<br>";
    }


    echo "<br>";
}


/*
 * ============================================================
 * SCENARIO F
 * ACHEAMPONG CHECK
 * ============================================================
 *
 * Reproduce the player that exposed the calibration issue.
 */

echo "============================================<br>";
echo "Scenario F: Acheampong Baseline Context<br>";
echo "============================================<br>";


$acheampong =
    null;


foreach (
    $playerDiagnostics
    as $player
) {

    if (
        strcasecmp(
            $player[
                'web_name'
            ],
            'Acheampong'
        )
        === 0
    ) {

        $acheampong =
            $player;

        break;
    }
}


if (
    $acheampong === null
) {

    echo "Acheampong: Not found<br>";

} else {

    echo "Minutes: "
        . $acheampong[
            'minutes'
        ]
        . "<br>";


    echo "CBIT: "
        . $acheampong[
            'actions'
        ]
        . "<br>";


    echo "CBIT / 90: "
        . number_format(
            $acheampong[
                'per90'
            ],
            2
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * BASELINE CANDIDATES
 * ============================================================
 *
 * We do NOT automatically choose a prior here.
 *
 * This simply surfaces the 60+ and 75+ minute mean/median so
 * we can make the calibration decision after inspecting the
 * real distribution.
 */

echo "============================================<br>";
echo "Scenario G: Baseline Candidates<br>";
echo "============================================<br><br>";


foreach (
    [
        'DEF',
        'MID',
        'FWD'
    ]
    as $position
) {

    $stats60 =
        dcBuildStats(
            $datasets[
                $position
            ][
                '60_plus'
            ]
        );


    $stats75 =
        dcBuildStats(
            $datasets[
                $position
            ][
                '75_plus'
            ]
        );


    echo $position
        . "<br>";


    echo "60+ Mean: "
        . dcFormat(
            $stats60[
                'mean'
            ]
        )
        . "<br>";


    echo "60+ Median: "
        . dcFormat(
            $stats60[
                'median'
            ]
        )
        . "<br>";


    echo "75+ Mean: "
        . dcFormat(
            $stats75[
                'mean'
            ]
        )
        . "<br>";


    echo "75+ Median: "
        . dcFormat(
            $stats75[
                'median'
            ]
        )
        . "<br><br>";
}


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "============================================<br>";
echo "Baseline Analysis Complete<br>";
echo "============================================<br>";