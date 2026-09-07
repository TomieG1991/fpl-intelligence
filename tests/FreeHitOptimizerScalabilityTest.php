<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Free Hit Optimizer Scalability Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


function testResult(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


/*
 * ============================================================
 * CONTROLLED LARGE PLAYER POOL
 * ============================================================
 *
 * This deliberately resembles the size of the live FPL player
 * pool without depending on live database or API data.
 *
 * The highest projected players are deliberately expensive so
 * that the initial Free Hit squad exceeds the £100m budget and
 * exercises the affordability optimizer.
 */

$players =
    [];

$playerId =
    1;


$positionCounts = [
    'GK' =>
        70,

    'DEF' =>
        220,

    'MID' =>
        220,

    'FWD' =>
        150
];


foreach (
    $positionCounts
    as $position =>
        $count
) {

    for (
        $i = 0;
        $i < $count;
        $i++
    ) {

        /*
         * Spread players deterministically across 20 clubs.
         */
        $teamId =
            ($i % 20)
            +
            1;


        /*
         * Stronger players are deliberately more expensive.
         *
         * This means the naive highest-projected-points squad
         * will require budget optimization.
         */
        $rankFraction =
            $count > 1
                ? $i
                    /
                    ($count - 1)
                : 0.0;


        $projectedPoints =
            12.0
            -
            (
                $rankFraction
                *
                8.0
            );


        switch (
            $position
        ) {

            case 'GK':

                $minimumPrice =
                    4.0;

                $maximumPrice =
                    7.0;

                break;


            case 'DEF':

                $minimumPrice =
                    4.0;

                $maximumPrice =
                    8.0;

                break;


            case 'MID':

                $minimumPrice =
                    4.5;

                $maximumPrice =
                    13.0;

                break;


            case 'FWD':

                $minimumPrice =
                    4.5;

                $maximumPrice =
                    15.0;

                break;


            default:

                $minimumPrice =
                    4.0;

                $maximumPrice =
                    10.0;

                break;
        }


        $price =
            $maximumPrice
            -
            (
                $rankFraction
                *
                (
                    $maximumPrice
                    -
                    $minimumPrice
                )
            );


        $players[] = [
            'player_id' =>
                $playerId,

            'name' =>
                $position
                . ' Player '
                . $playerId,

            'team_id' =>
                $teamId,

            'team_name' =>
                'Team '
                . $teamId,

            'position' =>
                $position,

            'price' =>
                round(
                    $price,
                    1
                ),

            'projected_points' =>
                round(
                    $projectedPoints,
                    3
                )
        ];


        $playerId++;
    }
}


echo "Synthetic Player Pool: "
    . count(
        $players
    )
    . "<br>";

echo "Budget: £100.0m<br><br>";


/*
 * ============================================================
 * SCENARIO A: LARGE POOL FOUNDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Large Pool Foundation<br>";
echo "============================================<br>";


testResult(
    count(
        $players
    )
    ===
    660,
    'Synthetic player pool contains 660 players'
);


$positionTotals =
    [
        'GK' =>
            0,

        'DEF' =>
            0,

        'MID' =>
            0,

        'FWD' =>
            0
    ];


foreach (
    $players
    as $player
) {

    $position =
        $player[
            'position'
        ];


    if (
        isset(
            $positionTotals[
                $position
            ]
        )
    ) {

        $positionTotals[
            $position
        ]++;
    }
}


testResult(
    $positionTotals[
        'GK'
    ]
    ===
    70,
    'Synthetic pool contains 70 goalkeepers'
);


testResult(
    $positionTotals[
        'DEF'
    ]
    ===
    220,
    'Synthetic pool contains 220 defenders'
);


testResult(
    $positionTotals[
        'MID'
    ]
    ===
    220,
    'Synthetic pool contains 220 midfielders'
);


testResult(
    $positionTotals[
        'FWD'
    ]
    ===
    150,
    'Synthetic pool contains 150 forwards'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B: LARGE-POOL OPTIMIZATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Large-Pool Optimization<br>";
echo "============================================<br>";


$optimizer =
    new FreeHitOptimizer();


$startTime =
    microtime(
        true
    );


$result =
    $optimizer->optimize(
        $players,
        100.0
    );
    
/*
 * ============================================================
 * LARGE-POOL RESULT DIAGNOSTIC
 * ============================================================
 */

echo "Optimizer Status: ";


echo htmlspecialchars(
    (string) (
        $result[
            'status'
        ]
        ??
        'MISSING'
    ),
    ENT_QUOTES,
    'UTF-8'
);


echo "<br>";


echo "Optimizer Message: ";


echo htmlspecialchars(
    (string) (
        $result[
            'message'
        ]
        ??
        'NONE'
    ),
    ENT_QUOTES,
    'UTF-8'
);


echo "<br>";    


$runtime =
    microtime(
        true
    )
    -
    $startTime;


testResult(
    is_array(
        $result
    ),
    'Optimizer returns an array for a 660-player pool'
);


testResult(
    (
        $result[
            'status'
        ]
        ??
        null
    )
    ===
    'success',
    'Optimizer successfully handles a 660-player pool'
);


$squad =
    $result[
        'squad'
    ]
    ??
    [];


testResult(
    count(
        $squad
    )
    ===
    15,
    'Large-pool optimization returns exactly 15 players'
);


$totalPrice =
    0.0;


foreach (
    $squad
    as $player
) {

    $totalPrice +=
        (float) (
            $player[
                'price'
            ]
            ??
            0.0
        );
}


testResult(
    $totalPrice
    <=
    100.0,
    'Large-pool optimized squad respects the £100m budget'
);


$teamCounts =
    [];


foreach (
    $squad
    as $player
) {

    $teamId =
        (int) (
            $player[
                'team_id'
            ]
            ??
            0
        );


    $teamCounts[
        $teamId
    ] =
        (
            $teamCounts[
                $teamId
            ]
            ??
            0
        )
        +
        1;
}


$clubLimitValid =
    true;


foreach (
    $teamCounts
    as $teamCount
) {

    if (
        $teamCount
        >
        3
    ) {

        $clubLimitValid =
            false;

        break;
    }
}


testResult(
    $clubLimitValid,
    'Large-pool optimized squad respects the three-player club limit'
);


testResult(
    isset(
        $result[
            'starting_xi_projected_points'
        ]
    )
    &&
    is_numeric(
        $result[
            'starting_xi_projected_points'
        ]
    ),
    'Large-pool optimization preserves Starting XI projected points'
);


echo "Optimized Squad Price: £"
    . number_format(
        $totalPrice,
        1
    )
    . "m<br>";

echo "Starting XI Projected Points: "
    . (
        isset(
            $result[
                'starting_xi_projected_points'
            ]
        )
        &&
        is_numeric(
            $result[
                'starting_xi_projected_points'
            ]
        )
            ? number_format(
                (float) $result[
                    'starting_xi_projected_points'
                ],
                3
            )
            : 'N/A'
    )
    . "<br>";

echo "Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br>";


echo "<br>";


/*
 * ============================================================
 * SCENARIO C: STARTING XI REMAINS THE OPTIMIZATION OBJECTIVE
 * ============================================================
 *
 * The scalability fix must not change the meaning of Free Hit
 * optimization.
 *
 * Bench projected points are not part of the optimization
 * objective. Budget should be sacrificed on a non-starting
 * goalkeeper when doing so funds a stronger Starting XI.
 */

echo "============================================<br>";
echo "Scenario C: Starting XI Remains Optimization Objective<br>";
echo "============================================<br>";


$objectivePlayers = [];


/*
 * Goalkeepers.
 *
 * Player 1 is the clear starter.
 *
 * Player 2 is a stronger but expensive backup.
 * Player 3 is a weak cheap backup.
 */
$objectivePlayers[] = [
    'player_id' =>
        1001,

    'name' =>
        'Starting Goalkeeper',

    'position' =>
        'GK',

    'team_id' =>
        1,

    'team_name' =>
        'Team 1',

    'price' =>
        5.0,

    'projected_points' =>
        8.0
];


$objectivePlayers[] = [
    'player_id' =>
        1002,

    'name' =>
        'Expensive Backup Goalkeeper',

    'position' =>
        'GK',

    'team_id' =>
        2,

    'team_name' =>
        'Team 2',

    'price' =>
        6.0,

    'projected_points' =>
        6.0
];


$objectivePlayers[] = [
    'player_id' =>
        1003,

    'name' =>
        'Cheap Backup Goalkeeper',

    'position' =>
        'GK',

    'team_id' =>
        3,

    'team_name' =>
        'Team 3',

    'price' =>
        4.0,

    'projected_points' =>
        1.0
];


/*
 * Five fixed defenders.
 */
for (
    $i = 0;
    $i < 5;
    $i++
) {

    $objectivePlayers[] = [
        'player_id' =>
            1010
            +
            $i,

        'name' =>
            'Objective Defender '
            . ($i + 1),

        'position' =>
            'DEF',

        'team_id' =>
            4
            +
            $i,

        'team_name' =>
            'Team '
            . (
                4
                +
                $i
            ),

        'price' =>
            5.0,

        'projected_points' =>
            6.0
    ];
}


/*
 * Four fixed strong midfielders.
 */
for (
    $i = 0;
    $i < 4;
    $i++
) {

    $objectivePlayers[] = [
        'player_id' =>
            1020
            +
            $i,

        'name' =>
            'Objective Midfielder '
            . ($i + 1),

        'position' =>
            'MID',

        'team_id' =>
            9
            +
            $i,

        'team_name' =>
            'Team '
            . (
                9
                +
                $i
            ),

        'price' =>
            6.0,

        'projected_points' =>
            9.0
    ];
}


/*
 * Expensive fifth midfielder.
 *
 * This is the player we want in the Starting XI.
 */
$objectivePlayers[] = [
    'player_id' =>
        1024,

    'name' =>
        'Strong Starting Midfielder',

    'position' =>
        'MID',

    'team_id' =>
        13,

    'team_name' =>
        'Team 13',

    'price' =>
        8.0,

    'projected_points' =>
        12.0
];


/*
 * Cheaper fifth midfielder.
 */
$objectivePlayers[] = [
    'player_id' =>
        1025,

    'name' =>
        'Weaker Starting Midfielder',

    'position' =>
        'MID',

    'team_id' =>
        14,

    'team_name' =>
        'Team 14',

    'price' =>
        6.0,

    'projected_points' =>
        8.0
];


/*
 * Three fixed forwards.
 */
for (
    $i = 0;
    $i < 3;
    $i++
) {

    $objectivePlayers[] = [
        'player_id' =>
            1030
            +
            $i,

        'name' =>
            'Objective Forward '
            . ($i + 1),

        'position' =>
            'FWD',

        'team_id' =>
            15
            +
            $i,

        'team_name' =>
            'Team '
            . (
                15
                +
                $i
            ),

        'price' =>
            7.0,

        'projected_points' =>
            7.0
    ];
}


/*
 * At this budget the optimizer must choose between:
 *
 * - spending more on the backup goalkeeper, or
 * - spending more on the fifth midfielder.
 *
 * Since only the strongest legal Starting XI matters,
 * the cheap backup goalkeeper should be selected so the
 * stronger midfielder can be retained.
 */
$objectiveResult =
    $optimizer->optimize(
        $objectivePlayers,
        88.0
    );


testResult(
    (
        $objectiveResult[
            'status'
        ]
        ??
        null
    )
    ===
    'success',
    'Starting-XI objective scenario returns success'
);


$objectiveSelectedIds =
    [];


foreach (
    $objectiveResult[
        'squad'
    ]
    ??
    []
    as $player
) {

    $objectiveSelectedIds[] =
        (int) (
            $player[
                'player_id'
            ]
            ??
            0
        );
}


testResult(
    in_array(
        1003,
        $objectiveSelectedIds,
        true
    ),
    'Cheap backup goalkeeper is preferred when it funds the stronger Starting XI'
);


testResult(
    !in_array(
        1002,
        $objectiveSelectedIds,
        true
    ),
    'Expensive backup goalkeeper is excluded when its points do not improve the Starting XI'
);


testResult(
    in_array(
        1024,
        $objectiveSelectedIds,
        true
    ),
    'Higher projected Starting XI midfielder is retained'
);


testResult(
    !in_array(
        1025,
        $objectiveSelectedIds,
        true
    ),
    'Weaker midfielder is excluded when budget can instead be saved on the bench'
);


echo "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Free Hit Optimizer Scalability Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}