<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Bonus Points Baseline Analysis<br>";
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
 * LOAD COMPLETE PLAYER FIXTURE HISTORY
 * ============================================================
 *
 * Bonus is fixture-relative, so every row retains:
 *
 * - fixture identity
 * - player
 * - position
 * - minutes
 * - BPS
 * - awarded bonus
 */

$sql = "
    SELECT
        pfh.fixture_id,
        pfh.fpl_fixture_id,
        pfh.player_id,
        p.web_name,
        p.position,
        pfh.minutes,
        pfh.total_points,
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
    ORDER BY
        pfh.fixture_id ASC,
        pfh.bps DESC,
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


echo "GW1 History Rows Loaded: "
    . count(
        $rows
    )
    . "<br><br>";


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function bonusAverage(
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


function bonusPercentile(
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


function bonusFormat(
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


function bonusBuildStats(
    array $values
): array {

    return [

        'count' =>
            count(
                $values
            ),

        'mean' =>
            bonusAverage(
                $values
            ),

        'p25' =>
            bonusPercentile(
                $values,
                0.25
            ),

        'median' =>
            bonusPercentile(
                $values,
                0.50
            ),

        'p75' =>
            bonusPercentile(
                $values,
                0.75
            ),

        'p90' =>
            bonusPercentile(
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


function bonusPrintStats(
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
        . bonusFormat(
            $stats[
                'mean'
            ]
        )
        . "<br>";


    echo "P25: "
        . bonusFormat(
            $stats[
                'p25'
            ]
        )
        . "<br>";


    echo "Median: "
        . bonusFormat(
            $stats[
                'median'
            ]
        )
        . "<br>";


    echo "P75: "
        . bonusFormat(
            $stats[
                'p75'
            ]
        )
        . "<br>";


    echo "P90: "
        . bonusFormat(
            $stats[
                'p90'
            ]
        )
        . "<br>";


    echo "Maximum: "
        . bonusFormat(
            $stats[
                'maximum'
            ]
        )
        . "<br><br>";
}


/*
 * ============================================================
 * ORGANISE FIXTURE DATA
 * ============================================================
 */

$fixtures =
    [];


$positionBps = [

    'GK' =>
        [],

    'DEF' =>
        [],

    'MID' =>
        [],

    'FWD' =>
        []
];


$positionBps60 = [

    'GK' =>
        [],

    'DEF' =>
        [],

    'MID' =>
        [],

    'FWD' =>
        []
];


$positionBonus = [

    'GK' => [

        'players' =>
            0,

        'bonus_players' =>
            0,

        'bonus_points' =>
            0
    ],

    'DEF' => [

        'players' =>
            0,

        'bonus_players' =>
            0,

        'bonus_points' =>
            0
    ],

    'MID' => [

        'players' =>
            0,

        'bonus_players' =>
            0,

        'bonus_points' =>
            0
    ],

    'FWD' => [

        'players' =>
            0,

        'bonus_players' =>
            0,

        'bonus_points' =>
            0
    ]
];


$bonusBpsGroups = [

    0 =>
        [],

    1 =>
        [],

    2 =>
        [],

    3 =>
        []
];


foreach (
    $rows
    as $row
) {

    $fixtureId =
        (int) (
            $row[
                'fixture_id'
            ]
            ?? 0
        );


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


    $bps =
        (int) (
            $row[
                'bps'
            ]
            ?? 0
        );


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
        !isset(
            $fixtures[
                $fixtureId
            ]
        )
    ) {

        $fixtures[
            $fixtureId
        ] =
            [];
    }


    $fixtures[
        $fixtureId
    ][] = [

        'player_id' =>
            (int) (
                $row[
                    'player_id'
                ]
                ?? 0
            ),

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

        'bps' =>
            $bps,

        'bonus' =>
            $bonus,

        'total_points' =>
            (int) (
                $row[
                    'total_points'
                ]
                ?? 0
            )
    ];


    /*
     * Position distributions only use actual appearances.
     */

    if (
        $minutes > 0
        &&
        isset(
            $positionBps[
                $position
            ]
        )
    ) {

        $positionBps[
            $position
        ][] =
            $bps;


        if (
            $minutes >= 60
        ) {

            $positionBps60[
                $position
            ][] =
                (
                    $bps
                    /
                    $minutes
                )
                *
                90;
        }


        $positionBonus[
            $position
        ][
            'players'
        ]++;


        if (
            $bonus > 0
        ) {

            $positionBonus[
                $position
            ][
                'bonus_players'
            ]++;


            $positionBonus[
                $position
            ][
                'bonus_points'
            ] +=
                $bonus;
        }
    }


    $bonusBpsGroups[
        $bonus
    ][] =
        $bps;
}


/*
 * ============================================================
 * SCENARIO A
 * BASIC DATASET
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Fixture Dataset<br>";
echo "============================================<br>";


echo "Fixtures Represented: "
    . count(
        $fixtures
    )
    . "<br>";


echo "Player Fixture Rows: "
    . count(
        $rows
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * BPS BY POSITION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: BPS Distribution By Position<br>";
echo "============================================<br><br>";


foreach (
    [
        'GK',
        'DEF',
        'MID',
        'FWD'
    ]
    as $position
) {

    bonusPrintStats(
        $position
        . ' BPS — All Appearances',
        bonusBuildStats(
            $positionBps[
                $position
            ]
        )
    );
}


/*
 * ============================================================
 * SCENARIO C
 * BPS / 90 FOR 60+ MINUTE PLAYERS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: BPS / 90 — 60+ Minute Players<br>";
echo "============================================<br><br>";


foreach (
    [
        'GK',
        'DEF',
        'MID',
        'FWD'
    ]
    as $position
) {

    bonusPrintStats(
        $position
        . ' BPS / 90',
        bonusBuildStats(
            $positionBps60[
                $position
            ]
        )
    );
}


/*
 * ============================================================
 * SCENARIO D
 * BPS BY ACTUAL BONUS AWARD
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: BPS By Actual Bonus Award<br>";
echo "============================================<br><br>";


foreach (
    [
        0,
        1,
        2,
        3
    ]
    as $bonus
) {

    bonusPrintStats(
        $bonus
        . ' Bonus Point'
        . (
            $bonus === 1
                ? ''
                : 's'
        ),
        bonusBuildStats(
            $bonusBpsGroups[
                $bonus
            ]
        )
    );
}


/*
 * ============================================================
 * SCENARIO E
 * POSITION BONUS FREQUENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Bonus Frequency By Position<br>";
echo "============================================<br><br>";


foreach (
    [
        'GK',
        'DEF',
        'MID',
        'FWD'
    ]
    as $position
) {

    $players =
        $positionBonus[
            $position
        ][
            'players'
        ];


    $bonusPlayers =
        $positionBonus[
            $position
        ][
            'bonus_players'
        ];


    $bonusPoints =
        $positionBonus[
            $position
        ][
            'bonus_points'
        ];


    $hitRate =
        $players > 0
            ? (
                $bonusPlayers
                /
                $players
                *
                100
            )
            : 0;


    $bonusPerAppearance =
        $players > 0
            ? (
                $bonusPoints
                /
                $players
            )
            : 0;


    echo $position
        . "<br>";


    echo "Appearances: "
        . $players
        . "<br>";


    echo "Players Receiving Bonus: "
        . $bonusPlayers
        . "<br>";


    echo "Bonus Hit Rate: "
        . number_format(
            $hitRate,
            2
        )
        . "%<br>";


    echo "Total Bonus Points: "
        . $bonusPoints
        . "<br>";


    echo "Bonus / Appearance: "
        . number_format(
            $bonusPerAppearance,
            3
        )
        . "<br><br>";
}


/*
 * ============================================================
 * SCENARIO F
 * FIXTURE BONUS LEADERBOARDS
 * ============================================================
 *
 * This is the most important section.
 *
 * Bonus depends on BPS ranking inside each fixture.
 */

echo "============================================<br>";
echo "Scenario F: Fixture BPS Leaderboards<br>";
echo "============================================<br><br>";


$fixtureCutoffs =
    [];


foreach (
    $fixtures
    as $fixtureId => $players
) {

    usort(
        $players,
        function (
            array $a,
            array $b
        ): int {

            if (
                $a[
                    'bps'
                ]
                ===
                $b[
                    'bps'
                ]
            ) {

                return $b[
                    'bonus'
                ]
                <=>
                $a[
                    'bonus'
                ];
            }


            return $b[
                'bps'
            ]
            <=>
            $a[
                'bps'
            ];
        }
    );


    /*
     * Only players who actually appeared are useful when
     * inspecting competitive BPS rankings.
     */

    $appearancePlayers =
        array_values(
            array_filter(
                $players,
                function (
                    array $player
                ): bool {

                    return $player[
                        'minutes'
                    ]
                    >
                    0;
                }
            )
        );


    echo "Fixture "
        . $fixtureId
        . "<br>";


    $topPlayers =
        array_slice(
            $appearancePlayers,
            0,
            8
        );


    foreach (
        $topPlayers
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
            . ' ('
            . htmlspecialchars(
                $player[
                    'position'
                ],
                ENT_QUOTES,
                'UTF-8'
            )
            . ')'
            . ' — '
            . $player[
                'minutes'
            ]
            . ' mins'
            . ' — BPS '
            . $player[
                'bps'
            ]
            . ' — Bonus '
            . $player[
                'bonus'
            ]
            . "<br>";
    }


    /*
     * Capture actual BPS values attached to awarded
     * 3 / 2 / 1 bonus.
     */

    $bonus3Bps =
        [];


    $bonus2Bps =
        [];


    $bonus1Bps =
        [];


    foreach (
        $appearancePlayers
        as $player
    ) {

        if (
            $player[
                'bonus'
            ]
            ===
            3
        ) {

            $bonus3Bps[] =
                $player[
                    'bps'
                ];
        }


        if (
            $player[
                'bonus'
            ]
            ===
            2
        ) {

            $bonus2Bps[] =
                $player[
                    'bps'
                ];
        }


        if (
            $player[
                'bonus'
            ]
            ===
            1
        ) {

            $bonus1Bps[] =
                $player[
                    'bps'
                ];
        }
    }


    $fixtureCutoffs[] = [

        'fixture_id' =>
            $fixtureId,

        'bonus_3_bps' =>
            empty(
                $bonus3Bps
            )
                ? null
                : min(
                    $bonus3Bps
                ),

        'bonus_2_bps' =>
            empty(
                $bonus2Bps
            )
                ? null
                : min(
                    $bonus2Bps
                ),

        'bonus_1_bps' =>
            empty(
                $bonus1Bps
            )
                ? null
                : min(
                    $bonus1Bps
                )
    ];


    echo "<br>";
}


/*
 * ============================================================
 * SCENARIO G
 * BONUS BPS CUTOFFS BY FIXTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Actual Bonus BPS Cutoffs<br>";
echo "============================================<br><br>";


$threePointCutoffs =
    [];


$twoPointCutoffs =
    [];


$onePointCutoffs =
    [];


foreach (
    $fixtureCutoffs
    as $cutoff
) {

    echo "Fixture "
        . $cutoff[
            'fixture_id'
        ]
        . "<br>";


    echo "3 Bonus BPS: "
        . (
            $cutoff[
                'bonus_3_bps'
            ]
            !== null
                ? $cutoff[
                    'bonus_3_bps'
                ]
                : 'Unavailable'
        )
        . "<br>";


    echo "2 Bonus BPS: "
        . (
            $cutoff[
                'bonus_2_bps'
            ]
            !== null
                ? $cutoff[
                    'bonus_2_bps'
                ]
                : 'Unavailable'
        )
        . "<br>";


    echo "1 Bonus BPS: "
        . (
            $cutoff[
                'bonus_1_bps'
            ]
            !== null
                ? $cutoff[
                    'bonus_1_bps'
                ]
                : 'Unavailable'
        )
        . "<br><br>";


    if (
        $cutoff[
            'bonus_3_bps'
        ]
        !== null
    ) {

        $threePointCutoffs[] =
            $cutoff[
                'bonus_3_bps'
            ];
    }


    if (
        $cutoff[
            'bonus_2_bps'
        ]
        !== null
    ) {

        $twoPointCutoffs[] =
            $cutoff[
                'bonus_2_bps'
            ];
    }


    if (
        $cutoff[
            'bonus_1_bps'
        ]
        !== null
    ) {

        $onePointCutoffs[] =
            $cutoff[
                'bonus_1_bps'
            ];
    }
}


/*
 * ============================================================
 * SCENARIO H
 * CUTOFF SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Bonus Cutoff Summary<br>";
echo "============================================<br><br>";


bonusPrintStats(
    '3 Bonus BPS Cutoff',
    bonusBuildStats(
        $threePointCutoffs
    )
);


bonusPrintStats(
    '2 Bonus BPS Cutoff',
    bonusBuildStats(
        $twoPointCutoffs
    )
);


bonusPrintStats(
    '1 Bonus BPS Cutoff',
    bonusBuildStats(
        $onePointCutoffs
    )
);


/*
 * ============================================================
 * SCENARIO I
 * TIE ANALYSIS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Tie Analysis<br>";
echo "============================================<br><br>";


$tieFixtures =
    0;


foreach (
    $fixtures
    as $fixtureId => $players
) {

    $bpsCounts =
        [];


    foreach (
        $players
        as $player
    ) {

        if (
            $player[
                'minutes'
            ]
            <=
            0
        ) {

            continue;
        }


        $bps =
            $player[
                'bps'
            ];


        if (
            !isset(
                $bpsCounts[
                    $bps
                ]
            )
        ) {

            $bpsCounts[
                $bps
            ] =
                [];
        }


        $bpsCounts[
            $bps
        ][] =
            $player;
    }


    $fixtureHasTie =
        false;


    foreach (
        $bpsCounts
        as $bps => $tiedPlayers
    ) {

        if (
            count(
                $tiedPlayers
            )
            <
            2
        ) {

            continue;
        }


        $bonusPlayers =
            array_filter(
                $tiedPlayers,
                function (
                    array $player
                ): bool {

                    return $player[
                        'bonus'
                    ]
                    >
                    0;
                }
            );


        if (
            empty(
                $bonusPlayers
            )
        ) {

            continue;
        }


        if (
            !$fixtureHasTie
        ) {

            echo "Fixture "
                . $fixtureId
                . "<br>";


            $fixtureHasTie =
                true;


            $tieFixtures++;
        }


        echo "BPS "
            . $bps
            . " tie:<br>";


        foreach (
            $tiedPlayers
            as $player
        ) {

            echo htmlspecialchars(
                $player[
                    'web_name'
                ],
                ENT_QUOTES,
                'UTF-8'
            )
                . ' — Bonus '
                . $player[
                    'bonus'
                ]
                . "<br>";
        }


        echo "<br>";
    }
}


echo "Fixtures With Relevant Bonus Ties: "
    . $tieFixtures
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO J
 * ABSOLUTE BPS BANDS
 * ============================================================
 *
 * This helps us understand whether absolute BPS is predictive
 * enough for an empirical first-stage Expected Bonus model.
 */

echo "============================================<br>";
echo "Scenario J: Bonus By Absolute BPS Band<br>";
echo "============================================<br><br>";


$bands = [

    '0-9' => [
        0,
        9
    ],

    '10-14' => [
        10,
        14
    ],

    '15-19' => [
        15,
        19
    ],

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
    $bands
    as $label => $range
) {

    $sample =
        0;


    $bonusPlayers =
        0;


    $bonusPoints =
        0;


    foreach (
        $rows
        as $row
    ) {

        if (
            (
                (int) (
                    $row[
                        'minutes'
                    ]
                    ?? 0
                )
            )
            <=
            0
        ) {

            continue;
        }


        $bps =
            (int) (
                $row[
                    'bps'
                ]
                ?? 0
            );


        if (
            $bps
            <
            $range[0]
            ||
            $bps
            >
            $range[1]
        ) {

            continue;
        }


        $sample++;


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
    }


    $hitRate =
        $sample > 0
            ? (
                $bonusPlayers
                /
                $sample
                *
                100
            )
            : 0;


    $expectedBonus =
        $sample > 0
            ? (
                $bonusPoints
                /
                $sample
            )
            : 0;


    echo $label
        . "<br>";


    echo "Sample: "
        . $sample
        . "<br>";


    echo "Bonus Hit Rate: "
        . number_format(
            $hitRate,
            2
        )
        . "%<br>";


    echo "Actual Bonus / Appearance: "
        . number_format(
            $expectedBonus,
            3
        )
        . "<br><br>";
}


/*
 * ============================================================
 * SCENARIO K
 * TOP BONUS EARNERS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Highest BPS Bonus Earners<br>";
echo "============================================<br><br>";


$bonusEarners =
    array_values(
        array_filter(
            $rows,
            function (
                array $row
            ): bool {

                return (
                    (int) (
                        $row[
                            'bonus'
                        ]
                        ?? 0
                    )
                )
                >
                0;
            }
        )
    );


usort(
    $bonusEarners,
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


$bonusEarners =
    array_slice(
        $bonusEarners,
        0,
        30
    );


foreach (
    $bonusEarners
    as $index => $player
) {

    echo (
        $index
        +
        1
    )
        . '. '
        . htmlspecialchars(
            (string) (
                $player[
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
                $player[
                    'position'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . ')'
        . ' — BPS '
        . (int) (
            $player[
                'bps'
            ]
            ?? 0
        )
        . ' — Bonus '
        . (int) (
            $player[
                'bonus'
            ]
            ?? 0
        )
        . ' — '
        . (int) (
            $player[
                'minutes'
            ]
            ?? 0
        )
        . ' mins'
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * COMPLETE
 * ============================================================
 */

echo "============================================<br>";
echo "Bonus Baseline Analysis Complete<br>";
echo "============================================<br>";