<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Defensive Rotation Test<br>";
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

function defensiveRotationCheck(
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


function defensiveRotationHeading(
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


/*
 * ============================================================
 * SYNTHETIC SQUAD
 * ============================================================
 *
 * The first three defenders are strong enough to start every
 * gameweek.
 *
 * Defender D and Defender E deliberately alternate:
 *
 *                GW2    GW3    GW4
 *
 * Defender D     4.5    1.5    5.0
 * Defender E     2.0    5.0    2.0
 *
 * Expected preferred defender:
 *
 * GW2 = Defender D
 * GW3 = Defender E
 * GW4 = Defender D
 *
 * Midfield and forward projections are configured so four
 * defenders are required in the optimal Starting XI.
 */


$squad = [

    /*
     * --------------------------------------------------------
     * GOALKEEPERS
     * --------------------------------------------------------
     */

    [
        'player_id' => 1,
        'name' => 'Goalkeeper A',
        'position' => 'GK',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 5.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 5.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 5.0
            ]
        ]
    ],

    [
        'player_id' => 2,
        'name' => 'Goalkeeper B',
        'position' => 'GK',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 3.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 3.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 3.0
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * DEFENDERS
     * --------------------------------------------------------
     */

    [
        'player_id' => 3,
        'name' => 'Defender A',
        'position' => 'DEF',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 7.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 7.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 7.0
            ]
        ]
    ],

    [
        'player_id' => 4,
        'name' => 'Defender B',
        'position' => 'DEF',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 6.5
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 6.5
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 6.5
            ]
        ]
    ],

    [
        'player_id' => 5,
        'name' => 'Defender C',
        'position' => 'DEF',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 6.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 6.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 6.0
            ]
        ]
    ],

    [
        'player_id' => 6,
        'name' => 'Defender D',
        'position' => 'DEF',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 4.5
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 0.25
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 5.0
            ]
        ]
    ],

    [
        'player_id' => 7,
        'name' => 'Defender E',
        'position' => 'DEF',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 0.25
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 5.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 0.25
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * MIDFIELDERS
     * --------------------------------------------------------
     */

    [
        'player_id' => 8,
        'name' => 'Midfielder A',
        'position' => 'MID',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 8.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 8.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 8.0
            ]
        ]
    ],

    [
        'player_id' => 9,
        'name' => 'Midfielder B',
        'position' => 'MID',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 7.5
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 7.5
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 7.5
            ]
        ]
    ],

    [
        'player_id' => 10,
        'name' => 'Midfielder C',
        'position' => 'MID',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 7.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 7.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 7.0
            ]
        ]
    ],

    [
        'player_id' => 11,
        'name' => 'Midfielder D',
        'position' => 'MID',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 1.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 1.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 1.0
            ]
        ]
    ],

    [
        'player_id' => 12,
        'name' => 'Midfielder E',
        'position' => 'MID',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 0.5
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 0.5
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 0.5
            ]
        ]
    ],


    /*
     * --------------------------------------------------------
     * FORWARDS
     * --------------------------------------------------------
     */

    [
        'player_id' => 13,
        'name' => 'Forward A',
        'position' => 'FWD',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 9.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 9.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 9.0
            ]
        ]
    ],

    [
        'player_id' => 14,
        'name' => 'Forward B',
        'position' => 'FWD',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 8.5
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 8.5
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 8.5
            ]
        ]
    ],

    [
        'player_id' => 15,
        'name' => 'Forward C',
        'position' => 'FWD',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 1.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 1.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 1.0
            ]
        ]
    ]
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


/*
 * ============================================================
 * SCENARIO A
 * DEFENSIVE ROTATION STRUCTURE
 * ============================================================
 */

defensiveRotationHeading(
    'Scenario A: Defensive Rotation Structure'
);


$defensiveRotation =
    $result[
        'defensive_rotation'
    ]
    ?? [];


defensiveRotationCheck(
    'Horizon exposes defensive rotation intelligence',
    isset(
        $result[
            'defensive_rotation'
        ]
    )
    &&
    is_array(
        $defensiveRotation
    )
);


defensiveRotationCheck(
    'Defensive rotation covers three gameweeks',
    (
        $defensiveRotation[
            'gameweek_count'
        ]
        ?? null
    )
    ===
    3
);


defensiveRotationCheck(
    'Defensive rotation exposes gameweek selections',
    isset(
        $defensiveRotation[
            'gameweeks'
        ]
    )
    &&
    is_array(
        $defensiveRotation[
            'gameweeks'
        ]
    )
    &&
    count(
        $defensiveRotation[
            'gameweeks'
        ]
    )
    ===
    3
);


/*
 * ============================================================
 * SCENARIO B
 * STARTING DEFENDERS BY GAMEWEEK
 * ============================================================
 */

defensiveRotationHeading(
    'Scenario B: Starting Defenders By Gameweek'
);


$rotationGameweeks =
    $defensiveRotation[
        'gameweeks'
    ]
    ?? [];


$gw2 =
    $rotationGameweeks[
        2
    ]
    ?? [];


$gw3 =
    $rotationGameweeks[
        3
    ]
    ?? [];


$gw4 =
    $rotationGameweeks[
        4
    ]
    ?? [];


defensiveRotationCheck(
    'GW2 starts Defender D and benches Defender E',
    in_array(
        6,
        $gw2[
            'starting_defender_ids'
        ]
        ?? [],
        true
    )
    &&
    in_array(
        7,
        $gw2[
            'benched_defender_ids'
        ]
        ?? [],
        true
    )
);


defensiveRotationCheck(
    'GW3 starts Defender E and benches Defender D',
    in_array(
        7,
        $gw3[
            'starting_defender_ids'
        ]
        ?? [],
        true
    )
    &&
    in_array(
        6,
        $gw3[
            'benched_defender_ids'
        ]
        ?? [],
        true
    )
);


defensiveRotationCheck(
    'GW4 starts Defender D and benches Defender E',
    in_array(
        6,
        $gw4[
            'starting_defender_ids'
        ]
        ?? [],
        true
    )
    &&
    in_array(
        7,
        $gw4[
            'benched_defender_ids'
        ]
        ?? [],
        true
    )
);


/*
 * ============================================================
 * SCENARIO C
 * ROTATION PAIR
 * ============================================================
 */

defensiveRotationHeading(
    'Scenario C: Rotation Pair'
);


$rotationPairs =
    $defensiveRotation[
        'rotation_pairs'
    ]
    ?? [];


defensiveRotationCheck(
    'Defensive rotation exposes rotation pairs',
    is_array(
        $rotationPairs
    )
);


$expectedPair =
    null;


foreach (
    $rotationPairs
    as $pair
) {

    $playerIds =
        $pair[
            'player_ids'
        ]
        ?? [];


    sort(
        $playerIds,
        SORT_NUMERIC
    );


    if (
        $playerIds
        ===
        [
            6,
            7
        ]
    ) {

        $expectedPair =
            $pair;

        break;
    }
}


defensiveRotationCheck(
    'Defender D and Defender E are identified as a rotation pair',
    is_array(
        $expectedPair
    )
);


defensiveRotationCheck(
    'Rotation pair alternates preferred defender across the horizon',
    (
        $expectedPair[
            'alternation_count'
        ]
        ?? null
    )
    ===
    2
);


/*
 * ============================================================
 * SCENARIO D
 * PREFERRED DEFENDER SEQUENCE
 * ============================================================
 */

defensiveRotationHeading(
    'Scenario D: Preferred Defender Sequence'
);


$preferredSequence =
    $expectedPair[
        'preferred_player_ids'
    ]
    ?? [];


defensiveRotationCheck(
    'Rotation pair records one preferred defender per gameweek',
    count(
        $preferredSequence
    )
    ===
    3
);


defensiveRotationCheck(
    'Preferred defender sequence is D, E, D',
    $preferredSequence
    ===
    [
        6,
        7,
        6
    ]
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