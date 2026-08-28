<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Fixture Clashes Test<br>";
echo "v0.32.0 — Squad Horizon & Rotation Intelligence<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function fixtureClashCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        $passed++;

        return;
    }


    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';

    $failed++;
}


function fixtureClashHeading(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "============================================<br>";
}


/**
 * Build one player with three gameweek projections.
 *
 * Each gameweek deliberately carries:
 *
 * - team_id
 * - opponent_team_id
 *
 * Those fields allow the squad-horizon layer to identify
 * Starting XI players who directly oppose each other.
 */
function buildFixtureClashPlayer(
    int $playerId,
    string $name,
    string $position,
    float $projectedPoints,
    array $fixtures
): array {

    $gameweeks =
        [];


    foreach (
        $fixtures
        as $gameweek => $fixture
    ) {

        $gameweeks[
            $gameweek
        ] = [
            'gameweek' =>
                $gameweek,

            'projected_points' =>
                $projectedPoints,

            'team_id' =>
                $fixture[
                    'team_id'
                ],

            'opponent_team_id' =>
                $fixture[
                    'opponent_team_id'
                ]
        ];
    }


    return [
        'player_id' =>
            $playerId,

        'name' =>
            $name,

        'position' =>
            $position,

        'gameweeks' =>
            $gameweeks
    ];
}


/*
 * ============================================================
 * SYNTHETIC SQUAD
 * ============================================================
 *
 * The projected XI will be:
 *
 * GK
 *   1
 *
 * DEF
 *   3, 4, 5
 *
 * MID
 *   8, 9, 10, 11, 12
 *
 * FWD
 *   13, 14
 *
 * Formation:
 *
 * 3-5-2
 *
 *
 * ------------------------------------------------------------
 * GW2
 * ------------------------------------------------------------
 *
 * Fixture clash A:
 *
 * Defender A       Team 101 vs Team 102
 * Forward A        Team 102 vs Team 101
 *
 * Fixture clash B:
 *
 * Defender B       Team 103 vs Team 104
 * Midfielder A     Team 104 vs Team 103
 *
 * Expected clashes = 2
 *
 *
 * ------------------------------------------------------------
 * GW3
 * ------------------------------------------------------------
 *
 * Every Starting XI player has a different opponent pairing.
 *
 * Expected clashes = 0
 *
 *
 * ------------------------------------------------------------
 * GW4
 * ------------------------------------------------------------
 *
 * Fixture clash:
 *
 * Goalkeeper A     Team 105 vs Team 106
 * Forward B        Team 106 vs Team 105
 *
 * Expected clashes = 1
 *
 *
 * Horizon totals:
 *
 * total clashes              = 3
 * gameweeks containing clash = 2
 * worst clash gameweek       = GW2
 * ============================================================
 */


$squad = [

    /*
     * --------------------------------------------------------
     * GOALKEEPERS
     * --------------------------------------------------------
     */

    buildFixtureClashPlayer(
        1,
        'Goalkeeper A',
        'GK',
        5.0,
        [
            2 => [
                'team_id' => 105,
                'opponent_team_id' => 120
            ],
            3 => [
                'team_id' => 105,
                'opponent_team_id' => 119
            ],
            4 => [
                'team_id' => 105,
                'opponent_team_id' => 106
            ]
        ]
    ),

    buildFixtureClashPlayer(
        2,
        'Goalkeeper B',
        'GK',
        3.0,
        [
            2 => [
                'team_id' => 121,
                'opponent_team_id' => 122
            ],
            3 => [
                'team_id' => 121,
                'opponent_team_id' => 123
            ],
            4 => [
                'team_id' => 121,
                'opponent_team_id' => 124
            ]
        ]
    ),


    /*
     * --------------------------------------------------------
     * DEFENDERS
     * --------------------------------------------------------
     */

    buildFixtureClashPlayer(
        3,
        'Defender A',
        'DEF',
        6.0,
        [
            2 => [
                'team_id' => 101,
                'opponent_team_id' => 102
            ],
            3 => [
                'team_id' => 101,
                'opponent_team_id' => 125
            ],
            4 => [
                'team_id' => 101,
                'opponent_team_id' => 126
            ]
        ]
    ),

    buildFixtureClashPlayer(
        4,
        'Defender B',
        'DEF',
        5.5,
        [
            2 => [
                'team_id' => 103,
                'opponent_team_id' => 104
            ],
            3 => [
                'team_id' => 103,
                'opponent_team_id' => 127
            ],
            4 => [
                'team_id' => 103,
                'opponent_team_id' => 128
            ]
        ]
    ),

    buildFixtureClashPlayer(
        5,
        'Defender C',
        'DEF',
        5.0,
        [
            2 => [
                'team_id' => 107,
                'opponent_team_id' => 108
            ],
            3 => [
                'team_id' => 107,
                'opponent_team_id' => 129
            ],
            4 => [
                'team_id' => 107,
                'opponent_team_id' => 130
            ]
        ]
    ),

    buildFixtureClashPlayer(
        6,
        'Defender D',
        'DEF',
        2.0,
        [
            2 => [
                'team_id' => 109,
                'opponent_team_id' => 110
            ],
            3 => [
                'team_id' => 109,
                'opponent_team_id' => 131
            ],
            4 => [
                'team_id' => 109,
                'opponent_team_id' => 132
            ]
        ]
    ),

    buildFixtureClashPlayer(
        7,
        'Defender E',
        'DEF',
        1.5,
        [
            2 => [
                'team_id' => 111,
                'opponent_team_id' => 112
            ],
            3 => [
                'team_id' => 111,
                'opponent_team_id' => 133
            ],
            4 => [
                'team_id' => 111,
                'opponent_team_id' => 134
            ]
        ]
    ),


    /*
     * --------------------------------------------------------
     * MIDFIELDERS
     * --------------------------------------------------------
     */

    buildFixtureClashPlayer(
        8,
        'Midfielder A',
        'MID',
        8.0,
        [
            2 => [
                'team_id' => 104,
                'opponent_team_id' => 103
            ],
            3 => [
                'team_id' => 104,
                'opponent_team_id' => 135
            ],
            4 => [
                'team_id' => 104,
                'opponent_team_id' => 136
            ]
        ]
    ),

    buildFixtureClashPlayer(
        9,
        'Midfielder B',
        'MID',
        7.5,
        [
            2 => [
                'team_id' => 113,
                'opponent_team_id' => 114
            ],
            3 => [
                'team_id' => 113,
                'opponent_team_id' => 137
            ],
            4 => [
                'team_id' => 113,
                'opponent_team_id' => 138
            ]
        ]
    ),

    buildFixtureClashPlayer(
        10,
        'Midfielder C',
        'MID',
        7.0,
        [
            2 => [
                'team_id' => 115,
                'opponent_team_id' => 116
            ],
            3 => [
                'team_id' => 115,
                'opponent_team_id' => 139
            ],
            4 => [
                'team_id' => 115,
                'opponent_team_id' => 140
            ]
        ]
    ),

    buildFixtureClashPlayer(
        11,
        'Midfielder D',
        'MID',
        6.5,
        [
            2 => [
                'team_id' => 117,
                'opponent_team_id' => 118
            ],
            3 => [
                'team_id' => 117,
                'opponent_team_id' => 141
            ],
            4 => [
                'team_id' => 117,
                'opponent_team_id' => 142
            ]
        ]
    ),

    buildFixtureClashPlayer(
        12,
        'Midfielder E',
        'MID',
        6.0,
        [
            2 => [
                'team_id' => 143,
                'opponent_team_id' => 144
            ],
            3 => [
                'team_id' => 143,
                'opponent_team_id' => 145
            ],
            4 => [
                'team_id' => 143,
                'opponent_team_id' => 146
            ]
        ]
    ),


    /*
     * --------------------------------------------------------
     * FORWARDS
     * --------------------------------------------------------
     */

    buildFixtureClashPlayer(
        13,
        'Forward A',
        'FWD',
        9.0,
        [
            2 => [
                'team_id' => 102,
                'opponent_team_id' => 101
            ],
            3 => [
                'team_id' => 102,
                'opponent_team_id' => 147
            ],
            4 => [
                'team_id' => 102,
                'opponent_team_id' => 148
            ]
        ]
    ),

    buildFixtureClashPlayer(
        14,
        'Forward B',
        'FWD',
        8.5,
        [
            2 => [
                'team_id' => 149,
                'opponent_team_id' => 150
            ],
            3 => [
                'team_id' => 149,
                'opponent_team_id' => 151
            ],
            4 => [
                'team_id' => 106,
                'opponent_team_id' => 105
            ]
        ]
    ),

    buildFixtureClashPlayer(
        15,
        'Forward C',
        'FWD',
        4.0,
        [
            2 => [
                'team_id' => 152,
                'opponent_team_id' => 153
            ],
            3 => [
                'team_id' => 152,
                'opponent_team_id' => 154
            ],
            4 => [
                'team_id' => 152,
                'opponent_team_id' => 155
            ]
        ]
    )
];


/*
 * ============================================================
 * BUILD HORIZON
 * ============================================================
 */

$model =
    new SquadHorizonIntelligence();


$result =
    $model->buildHorizon(
        $squad,
        3
    );


$fixtureClashes =
    $result[
        'fixture_clashes'
    ]
    ?? [];


/*
 * ============================================================
 * SCENARIO A
 * FIXTURE CLASH STRUCTURE
 * ============================================================
 */

fixtureClashHeading(
    'Scenario A: Fixture Clash Structure'
);


fixtureClashCheck(
    'Horizon exposes fixture clash intelligence',
    isset(
        $result[
            'fixture_clashes'
        ]
    )
    &&
    is_array(
        $fixtureClashes
    )
);


fixtureClashCheck(
    'Fixture clash intelligence covers three gameweeks',
    (
        $fixtureClashes[
            'gameweek_count'
        ]
        ?? null
    )
    ===
    3
);


fixtureClashCheck(
    'Fixture clash intelligence exposes gameweek detail',
    isset(
        $fixtureClashes[
            'gameweeks'
        ]
    )
    &&
    is_array(
        $fixtureClashes[
            'gameweeks'
        ]
    )
    &&
    count(
        $fixtureClashes[
            'gameweeks'
        ]
    )
    ===
    3
);


/*
 * ============================================================
 * SCENARIO B
 * CLASH COUNTS BY GAMEWEEK
 * ============================================================
 */

fixtureClashHeading(
    'Scenario B: Clash Counts By Gameweek'
);


$clashGameweeks =
    $fixtureClashes[
        'gameweeks'
    ]
    ?? [];


fixtureClashCheck(
    'GW2 contains two Starting XI fixture clashes',
    (
        $clashGameweeks[
            2
        ][
            'clash_count'
        ]
        ?? null
    )
    ===
    2
);


fixtureClashCheck(
    'GW3 contains no Starting XI fixture clashes',
    (
        $clashGameweeks[
            3
        ][
            'clash_count'
        ]
        ?? null
    )
    ===
    0
);


fixtureClashCheck(
    'GW4 contains one Starting XI fixture clash',
    (
        $clashGameweeks[
            4
        ][
            'clash_count'
        ]
        ?? null
    )
    ===
    1
);


/*
 * ============================================================
 * SCENARIO C
 * ACTUAL CLASH PAIRS
 * ============================================================
 */

fixtureClashHeading(
    'Scenario C: Actual Clash Pairs'
);


$gw2Clashes =
    $clashGameweeks[
        2
    ][
        'clashes'
    ]
    ?? [];


$gw4Clashes =
    $clashGameweeks[
        4
    ][
        'clashes'
    ]
    ?? [];


$gw2PairIds =
    [];


foreach (
    $gw2Clashes
    as $clash
) {

    $playerIds =
        $clash[
            'player_ids'
        ]
        ?? [];


    sort(
        $playerIds,
        SORT_NUMERIC
    );


    $gw2PairIds[] =
        $playerIds;
}


sort(
    $gw2PairIds
);


fixtureClashCheck(
    'GW2 identifies Defender A versus Forward A',
    in_array(
        [
            3,
            13
        ],
        $gw2PairIds,
        true
    )
);


fixtureClashCheck(
    'GW2 identifies Defender B versus Midfielder A',
    in_array(
        [
            4,
            8
        ],
        $gw2PairIds,
        true
    )
);


$gw4PairIds =
    $gw4Clashes[
        0
    ][
        'player_ids'
    ]
    ?? [];


sort(
    $gw4PairIds,
    SORT_NUMERIC
);


fixtureClashCheck(
    'GW4 identifies Goalkeeper A versus Forward B',
    $gw4PairIds
    ===
    [
        1,
        14
    ]
);


/*
 * ============================================================
 * SCENARIO D
 * HORIZON SUMMARY
 * ============================================================
 */

fixtureClashHeading(
    'Scenario D: Horizon Clash Summary'
);


fixtureClashCheck(
    'Horizon contains three total fixture clashes',
    (
        $fixtureClashes[
            'total_clash_count'
        ]
        ?? null
    )
    ===
    3
);


fixtureClashCheck(
    'Two gameweeks contain at least one fixture clash',
    (
        $fixtureClashes[
            'gameweeks_with_clashes'
        ]
        ?? null
    )
    ===
    2
);


fixtureClashCheck(
    'GW2 is identified as the worst clash gameweek',
    (
        $fixtureClashes[
            'worst_gameweek'
        ]
        ?? null
    )
    ===
    2
);


fixtureClashCheck(
    'Maximum single-gameweek clash count equals two',
    (
        $fixtureClashes[
            'max_clash_count'
        ]
        ?? null
    )
    ===
    2
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "TEST SUMMARY<br>";
echo "============================================<br>";

echo
    'Passed: '
    . $passed
    . '<br>';

echo
    'Failed: '
    . $failed
    . '<br><br>';


if (
    $failed === 0
) {

    echo
        'RESULT: ALL TESTS PASSED ✅';

} else {

    echo
        'RESULT: TESTS FAILED ❌';
}