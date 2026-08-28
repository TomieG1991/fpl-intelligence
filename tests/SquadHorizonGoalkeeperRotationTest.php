<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Horizon Goalkeeper Rotation Test<br>";
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

function goalkeeperRotationCheck(
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


function goalkeeperRotationHeading(
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
 * Goalkeeper projections:
 *
 *                GW2    GW3    GW4    TOTAL
 *
 * Goalkeeper A   5.0    2.0    5.0    12.0
 * Goalkeeper B   2.0    5.0    2.0     9.0
 *
 * Preferred:
 *
 *                A      B      A
 *
 * Rotating total:
 *
 * 5.0 + 5.0 + 5.0 = 15.0
 *
 * Best single goalkeeper:
 *
 * Goalkeeper A = 12.0
 *
 * Rotation gain:
 *
 * 15.0 - 12.0 = 3.0
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
                'projected_points' => 2.0
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
                'projected_points' => 2.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 5.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 2.0
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
        'player_id' => 4,
        'name' => 'Defender B',
        'position' => 'DEF',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 5.5
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 5.5
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 5.5
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
        'player_id' => 6,
        'name' => 'Defender D',
        'position' => 'DEF',

        'gameweeks' => [
            2 => [
                'gameweek' => 2,
                'projected_points' => 2.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 2.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 2.0
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
                'projected_points' => 1.5
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 1.5
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 1.5
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
        'player_id' => 12,
        'name' => 'Midfielder E',
        'position' => 'MID',

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
                'projected_points' => 4.0
            ],
            3 => [
                'gameweek' => 3,
                'projected_points' => 4.0
            ],
            4 => [
                'gameweek' => 4,
                'projected_points' => 4.0
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


$goalkeeperRotation =
    $result[
        'goalkeeper_rotation'
    ]
    ?? [];


/*
 * ============================================================
 * SCENARIO A
 * GOALKEEPER ROTATION STRUCTURE
 * ============================================================
 */

goalkeeperRotationHeading(
    'Scenario A: Goalkeeper Rotation Structure'
);


goalkeeperRotationCheck(
    'Horizon exposes goalkeeper rotation intelligence',
    isset(
        $result[
            'goalkeeper_rotation'
        ]
    )
    &&
    is_array(
        $goalkeeperRotation
    )
);


goalkeeperRotationCheck(
    'Goalkeeper rotation covers three gameweeks',
    (
        $goalkeeperRotation[
            'gameweek_count'
        ]
        ?? null
    )
    ===
    3
);


goalkeeperRotationCheck(
    'Goalkeeper rotation exposes two goalkeepers',
    (
        $goalkeeperRotation[
            'goalkeeper_count'
        ]
        ?? null
    )
    ===
    2
);


/*
 * ============================================================
 * SCENARIO B
 * PREFERRED GOALKEEPER SEQUENCE
 * ============================================================
 */

goalkeeperRotationHeading(
    'Scenario B: Preferred Goalkeeper Sequence'
);


$preferredGoalkeeperIds =
    $goalkeeperRotation[
        'preferred_goalkeeper_ids'
    ]
    ?? [];


goalkeeperRotationCheck(
    'Goalkeeper rotation records one preference per gameweek',
    count(
        $preferredGoalkeeperIds
    )
    ===
    3
);


goalkeeperRotationCheck(
    'Preferred goalkeeper sequence is A, B, A',
    $preferredGoalkeeperIds
    ===
    [
        1,
        2,
        1
    ]
);


goalkeeperRotationCheck(
    'Goalkeeper preference changes twice',
    (
        $goalkeeperRotation[
            'alternation_count'
        ]
        ?? null
    )
    ===
    2
);


/*
 * ============================================================
 * SCENARIO C
 * ROTATING PROJECTED POINTS
 * ============================================================
 */

goalkeeperRotationHeading(
    'Scenario C: Rotating Projected Points'
);


$rotatingProjectedPoints =
    $goalkeeperRotation[
        'rotating_projected_points'
    ]
    ?? null;


goalkeeperRotationCheck(
    'Goalkeeper rotation exposes rotating projected points',
    is_numeric(
        $rotatingProjectedPoints
    )
);


goalkeeperRotationCheck(
    'Rotating goalkeeper total equals 15.0',
    is_numeric(
        $rotatingProjectedPoints
    )
    &&
    abs(
        (float) $rotatingProjectedPoints
        -
        15.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO D
 * BEST SINGLE GOALKEEPER
 * ============================================================
 */

goalkeeperRotationHeading(
    'Scenario D: Best Single Goalkeeper'
);


$bestSingleGoalkeeper =
    $goalkeeperRotation[
        'best_single_goalkeeper'
    ]
    ?? null;


goalkeeperRotationCheck(
    'Goalkeeper rotation exposes best single goalkeeper',
    is_array(
        $bestSingleGoalkeeper
    )
);


goalkeeperRotationCheck(
    'Goalkeeper A is the best single goalkeeper',
    (
        $bestSingleGoalkeeper[
            'player_id'
        ]
        ?? null
    )
    ===
    1
);


goalkeeperRotationCheck(
    'Best single goalkeeper total equals 12.0',
    is_numeric(
        $bestSingleGoalkeeper[
            'projected_points'
        ]
        ?? null
    )
    &&
    abs(
        (float) $bestSingleGoalkeeper[
            'projected_points'
        ]
        -
        12.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO E
 * ROTATION GAIN
 * ============================================================
 */

goalkeeperRotationHeading(
    'Scenario E: Rotation Gain'
);


$rotationGain =
    $goalkeeperRotation[
        'rotation_gain'
    ]
    ?? null;


goalkeeperRotationCheck(
    'Goalkeeper rotation exposes rotation gain',
    is_numeric(
        $rotationGain
    )
);


goalkeeperRotationCheck(
    'Goalkeeper rotation gain equals 3.0',
    is_numeric(
        $rotationGain
    )
    &&
    abs(
        (float) $rotationGain
        -
        3.0
    )
    <
    0.001
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