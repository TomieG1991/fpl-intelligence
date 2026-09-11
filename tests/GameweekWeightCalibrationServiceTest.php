<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Weight Calibration Service Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function gameweekWeightCalibrationCheck(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

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
 * PLAYER FACTORY
 * ============================================================
 */

function gameweekWeightCalibrationPlayer(
    int $playerId,
    string $position,
    float $intelligence,
    float $strength,
    float $fixture,
    float $confidenceModifier,
    float $availabilityModifier,
    ?int $actualPoints
): array {

    return [

        'player_id' =>
            $playerId,

        'position' =>
            $position,

        'components' => [

            'intelligence' =>
                $intelligence,

            'strength' =>
                $strength,

            'fixture' =>
                $fixture,

            'confidence_modifier' =>
                $confidenceModifier,

            'availability_modifier' =>
                $availabilityModifier
        ],

        'actual_points' =>
            $actualPoints
    ];
}


/*
 * ============================================================
 * CONTROLLED HISTORICAL GAMEWEEK EVIDENCE
 * ============================================================
 *
 * Every usable historical gameweek contains the complete
 * preserved 15-player squad that existed at recommendation time.
 *
 * The calibration service must:
 *
 * - replay explicitly supplied Gameweek core weights
 * - use the already-preserved compressed fixture component
 * - preserve historical confidence modifiers
 * - preserve historical availability modifiers
 * - rank players using candidate Gameweek Scores
 * - select the strongest legal FPL Starting XI
 * - compare that selected XI with the best legal realised XI
 *   from the same preserved 15-player squad
 * - measure Starting XI selection points lost
 *
 * The service must NOT:
 *
 * - recalculate Player Intelligence
 * - recalculate fixture compression
 * - recalculate Effective Confidence
 * - recalculate availability
 * - use live player data
 * - reconstruct missing historical evidence
 */


/*
 * ============================================================
 * GAMEWEEK 1
 * ============================================================
 *
 * Legal squad:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * The current production-style 45 / 25 / 30 candidate should
 * prefer Player 105 over Player 106 for the final defensive
 * Starting XI place.
 *
 * The realised result favours Player 106.
 *
 * This lets us prove that candidate weights can alter selection
 * and that realised selection loss is measured independently
 * from candidate Gameweek Score.
 */

$gameweek1Players = [

    /* Goalkeepers */
    gameweekWeightCalibrationPlayer(
        101,
        'GK',
        90.0,
        90.0,
        80.0,
        1.00,
        1.00,
        6
    ),

    gameweekWeightCalibrationPlayer(
        102,
        'GK',
        60.0,
        60.0,
        60.0,
        1.00,
        1.00,
        2
    ),

    /* Defenders */
    gameweekWeightCalibrationPlayer(
        103,
        'DEF',
        90.0,
        90.0,
        90.0,
        1.00,
        1.00,
        8
    ),

    gameweekWeightCalibrationPlayer(
        104,
        'DEF',
        88.0,
        88.0,
        88.0,
        1.00,
        1.00,
        7
    ),

    gameweekWeightCalibrationPlayer(
        105,
        'DEF',
        92.0,
        90.0,
        55.0,
        1.00,
        1.00,
        2
    ),

    gameweekWeightCalibrationPlayer(
        106,
        'DEF',
        60.0,
        60.0,
        100.0,
        1.00,
        1.00,
        10
    ),

    gameweekWeightCalibrationPlayer(
        107,
        'DEF',
        40.0,
        40.0,
        40.0,
        1.00,
        1.00,
        1
    ),

    /* Midfielders */
    gameweekWeightCalibrationPlayer(
        108,
        'MID',
        95.0,
        95.0,
        90.0,
        1.00,
        1.00,
        12
    ),

    gameweekWeightCalibrationPlayer(
        109,
        'MID',
        90.0,
        90.0,
        90.0,
        1.00,
        1.00,
        9
    ),

    gameweekWeightCalibrationPlayer(
        110,
        'MID',
        85.0,
        85.0,
        85.0,
        1.00,
        1.00,
        8
    ),

    gameweekWeightCalibrationPlayer(
        111,
        'MID',
        80.0,
        80.0,
        80.0,
        1.00,
        1.00,
        7
    ),

    gameweekWeightCalibrationPlayer(
        112,
        'MID',
        50.0,
        50.0,
        50.0,
        1.00,
        1.00,
        0
    ),


    /* Forwards */
    gameweekWeightCalibrationPlayer(
        113,
        'FWD',
        92.0,
        92.0,
        90.0,
        1.00,
        1.00,
        11
    ),

    gameweekWeightCalibrationPlayer(
        114,
        'FWD',
        86.0,
        86.0,
        86.0,
        1.00,
        1.00,
        9
    ),

    gameweekWeightCalibrationPlayer(
        115,
        'FWD',
        78.0,
        78.0,
        78.0,
        1.00,
        1.00,
        2
    )
];


/*
 * ============================================================
 * GAMEWEEK 2
 * ============================================================
 *
 * This gameweek proves that preserved historical confidence and
 * availability modifiers remain downstream multiplicative
 * modifiers during candidate replay.
 */

$gameweek2Players = [

    gameweekWeightCalibrationPlayer(
        201,
        'GK',
        90.0,
        90.0,
        90.0,
        1.00,
        1.00,
        6
    ),

    gameweekWeightCalibrationPlayer(
        202,
        'GK',
        50.0,
        50.0,
        50.0,
        1.00,
        1.00,
        1
    ),

    gameweekWeightCalibrationPlayer(
        203,
        'DEF',
        95.0,
        95.0,
        95.0,
        0.40,
        1.00,
        2
    ),

    gameweekWeightCalibrationPlayer(
        204,
        'DEF',
        82.0,
        82.0,
        82.0,
        1.00,
        1.00,
        8
    ),

    gameweekWeightCalibrationPlayer(
        205,
        'DEF',
        80.0,
        80.0,
        80.0,
        1.00,
        1.00,
        7
    ),

    gameweekWeightCalibrationPlayer(
        206,
        'DEF',
        78.0,
        78.0,
        78.0,
        1.00,
        1.00,
        6
    ),

    gameweekWeightCalibrationPlayer(
        207,
        'DEF',
        90.0,
        90.0,
        90.0,
        1.00,
        0.40,
        1
    ),

    gameweekWeightCalibrationPlayer(
        208,
        'MID',
        92.0,
        92.0,
        92.0,
        1.00,
        1.00,
        10
    ),

    gameweekWeightCalibrationPlayer(
        209,
        'MID',
        88.0,
        88.0,
        88.0,
        1.00,
        1.00,
        9
    ),

    gameweekWeightCalibrationPlayer(
        210,
        'MID',
        84.0,
        84.0,
        84.0,
        1.00,
        1.00,
        8
    ),

    gameweekWeightCalibrationPlayer(
        211,
        'MID',
        80.0,
        80.0,
        80.0,
        1.00,
        1.00,
        7
    ),

    gameweekWeightCalibrationPlayer(
        212,
        'MID',
        40.0,
        40.0,
        40.0,
        1.00,
        1.00,
        1
    ),

    gameweekWeightCalibrationPlayer(
        213,
        'FWD',
        94.0,
        94.0,
        94.0,
        1.00,
        1.00,
        12
    ),

    gameweekWeightCalibrationPlayer(
        214,
        'FWD',
        86.0,
        86.0,
        86.0,
        1.00,
        1.00,
        9
    ),

    gameweekWeightCalibrationPlayer(
        215,
        'FWD',
        45.0,
        45.0,
        45.0,
        1.00,
        1.00,
        2
    )
];


/*
 * ============================================================
 * GAMEWEEK 3
 * ============================================================
 *
 * Zero and negative realised points are genuine outcomes and
 * must remain valid historical evidence.
 */

$gameweek3Players = [

    gameweekWeightCalibrationPlayer(
        301,
        'GK',
        80.0,
        80.0,
        80.0,
        1.00,
        1.00,
        0
    ),

    gameweekWeightCalibrationPlayer(
        302,
        'GK',
        40.0,
        40.0,
        40.0,
        1.00,
        1.00,
        -1
    ),

    gameweekWeightCalibrationPlayer(
        303,
        'DEF',
        90.0,
        90.0,
        90.0,
        1.00,
        1.00,
        0
    ),

    gameweekWeightCalibrationPlayer(
        304,
        'DEF',
        85.0,
        85.0,
        85.0,
        1.00,
        1.00,
        -1
    ),

    gameweekWeightCalibrationPlayer(
        305,
        'DEF',
        80.0,
        80.0,
        80.0,
        1.00,
        1.00,
        2
    ),

    gameweekWeightCalibrationPlayer(
        306,
        'DEF',
        70.0,
        70.0,
        70.0,
        1.00,
        1.00,
        3
    ),

    gameweekWeightCalibrationPlayer(
        307,
        'DEF',
        40.0,
        40.0,
        40.0,
        1.00,
        1.00,
        -1
    ),

    gameweekWeightCalibrationPlayer(
        308,
        'MID',
        90.0,
        90.0,
        90.0,
        1.00,
        1.00,
        0
    ),

    gameweekWeightCalibrationPlayer(
        309,
        'MID',
        85.0,
        85.0,
        85.0,
        1.00,
        1.00,
        4
    ),

    gameweekWeightCalibrationPlayer(
        310,
        'MID',
        80.0,
        80.0,
        80.0,
        1.00,
        1.00,
        3
    ),

    gameweekWeightCalibrationPlayer(
        311,
        'MID',
        75.0,
        75.0,
        75.0,
        1.00,
        1.00,
        2
    ),

    gameweekWeightCalibrationPlayer(
        312,
        'MID',
        40.0,
        40.0,
        40.0,
        1.00,
        1.00,
        -1
    ),

    gameweekWeightCalibrationPlayer(
        313,
        'FWD',
        90.0,
        90.0,
        90.0,
        1.00,
        1.00,
        0
    ),

    gameweekWeightCalibrationPlayer(
        314,
        'FWD',
        80.0,
        80.0,
        80.0,
        1.00,
        1.00,
        5
    ),

    gameweekWeightCalibrationPlayer(
        315,
        'FWD',
        40.0,
        40.0,
        40.0,
        1.00,
        1.00,
        -1
    )
];


/*
 * ============================================================
 * GAMEWEEK 4
 * ============================================================
 *
 * Candidate Gameweek Scores can still be replayed when realised
 * outcome evidence is incomplete.
 *
 * However, a fair best-realised-XI comparison is impossible if
 * even one player in the preserved 15-player universe has an
 * unknown realised score.
 */

$gameweek4Players =
    $gameweek1Players;


$gameweek4Players[14][
    'player_id'
] =
    415;


$gameweek4Players[14][
    'actual_points'
] =
    null;


$historicalGameweeks = [

    [
        'gameweek_id' => 1,
        'players' => $gameweek1Players
    ],

    [
        'gameweek_id' => 2,
        'players' => $gameweek2Players
    ],

    [
        'gameweek_id' => 3,
        'players' => $gameweek3Players
    ],

    [
        'gameweek_id' => 4,
        'players' => $gameweek4Players
    ],

    /*
     * Malformed historical rows must be ignored.
     */
    'malformed gameweek'
];


/*
 * ============================================================
 * WEIGHT CANDIDATES
 * ============================================================
 */

$weightCandidates = [

    /*
     * Current production weighting.
     */
    [
        'intelligence_weight' => 0.45,
        'strength_weight' => 0.25,
        'fixture_weight' => 0.30
    ],

    /*
     * Fixture-heavy candidate.
     */
    [
        'intelligence_weight' => 0.10,
        'strength_weight' => 0.10,
        'fixture_weight' => 0.80
    ],

    /*
     * Intelligence-only boundary.
     */
    [
        'intelligence_weight' => 1.00,
        'strength_weight' => 0.00,
        'fixture_weight' => 0.00
    ]
];


/*
 * ============================================================
 * SCENARIO A: CLASS CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Class Contract<br>";
echo "============================================<br>";


$classExists =
    class_exists(
        'GameweekWeightCalibrationService'
    );


gameweekWeightCalibrationCheck(
    $classExists,
    'GameweekWeightCalibrationService class exists.'
);


gameweekWeightCalibrationCheck(
    $classExists
    &&
    method_exists(
        'GameweekWeightCalibrationService',
        'evaluate'
    ),
    'GameweekWeightCalibrationService exposes evaluate().'
);


if (!$classExists) {

    echo "<br>";
    echo "============================================<br>";
    echo "Gameweek Weight Calibration Service Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br><br>";

    echo "RESULT: TESTS FAILED ❌";

    exit;
}


/*
 * ============================================================
 * CREATE SERVICE
 * ============================================================
 */

$service =
    new GameweekWeightCalibrationService();


/*
 * ============================================================
 * SCENARIO B: CANDIDATE EVALUATION CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Candidate Evaluation Contract<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $historicalGameweeks,
        $weightCandidates
    );


gameweekWeightCalibrationCheck(
    is_array(
        $result
    ),
    'evaluate() returns an array.'
);


gameweekWeightCalibrationCheck(
    isset(
        $result[
            'evaluations'
        ]
    )
    &&
    is_array(
        $result[
            'evaluations'
        ]
    ),
    'Result exposes evaluations array.'
);


gameweekWeightCalibrationCheck(
    count(
        $result[
            'evaluations'
        ]
    )
    ===
    3,
    'Every supplied Gameweek weight candidate is evaluated.'
);


/*
 * ============================================================
 * SCENARIO C: CURRENT PRODUCTION CANDIDATE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Current Production Candidate<br>";
echo "============================================<br>";


$current =
    $result[
        'evaluations'
    ][
        0
    ];


gameweekWeightCalibrationCheck(
    (
        $current[
            'intelligence_weight'
        ]
        ??
        null
    )
    ===
    0.45,
    'Player Intelligence weight is preserved.'
);


gameweekWeightCalibrationCheck(
    (
        $current[
            'strength_weight'
        ]
        ??
        null
    )
    ===
    0.25,
    'Player Strength weight is preserved.'
);


gameweekWeightCalibrationCheck(
    (
        $current[
            'fixture_weight'
        ]
        ??
        null
    )
    ===
    0.30,
    'Immediate Fixture weight is preserved.'
);


gameweekWeightCalibrationCheck(
    isset(
        $current[
            'gameweeks'
        ]
    )
    &&
    is_array(
        $current[
            'gameweeks'
        ]
    ),
    'Candidate exposes per-gameweek evaluations.'
);


gameweekWeightCalibrationCheck(
    count(
        $current[
            'gameweeks'
        ]
    )
    ===
    4,
    'Only valid historical gameweek rows are evaluated.'
);


/*
 * ============================================================
 * SCENARIO D: GAMEWEEK 1 XI REPLAY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Gameweek 1 XI Replay<br>";
echo "============================================<br>";


$gw1 =
    $current[
        'gameweeks'
    ][
        0
    ];


gameweekWeightCalibrationCheck(
    (
        $gw1[
            'gameweek_id'
        ]
        ??
        null
    )
    ===
    1,
    'Gameweek identity is preserved.'
);


gameweekWeightCalibrationCheck(
    isset(
        $gw1[
            'selected_xi'
        ]
    )
    &&
    is_array(
        $gw1[
            'selected_xi'
        ]
    ),
    'Candidate evaluation exposes selected XI.'
);


gameweekWeightCalibrationCheck(
    count(
        $gw1[
            'selected_xi'
        ]
    )
    ===
    11,
    'Candidate evaluation selects exactly 11 players.'
);


$gw1SelectedIds =
    array_column(
        $gw1[
            'selected_xi'
        ],
        'player_id'
    );
    
 
gameweekWeightCalibrationCheck(
    in_array(
        105,
        $gw1SelectedIds,
        true
    ),
    'Current weighting selects Player 105.'
);


gameweekWeightCalibrationCheck(
    !in_array(
        106,
        $gw1SelectedIds,
        true
    ),
    'Current weighting benches Player 106.'
);


gameweekWeightCalibrationCheck(
    isset(
        $gw1[
            'selected_xi_score'
        ]
    )
    &&
    is_numeric(
        $gw1[
            'selected_xi_score'
        ]
    ),
    'Selected XI exposes candidate Gameweek Score.'
);


gameweekWeightCalibrationCheck(
    (
        $gw1[
            'selected_actual_points'
        ]
        ??
        null
    )
    ===
    81,
    'Selected XI realised points are calculated correctly.'
);


gameweekWeightCalibrationCheck(
    (
        $gw1[
            'best_actual_points'
        ]
        ??
        null
    )
    ===
    89,
    'Best legal realised XI points are calculated correctly.'
);


gameweekWeightCalibrationCheck(
    (
        $gw1[
            'selection_points_lost'
        ]
        ??
        null
    )
    ===
    8,
    'Gameweek 1 Starting XI selection points lost is calculated correctly.'
);


/*
 * ============================================================
 * SCENARIO E: FIXTURE-HEAVY CANDIDATE CHANGES SELECTION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Alternative Weighting Changes XI<br>";
echo "============================================<br>";


$fixtureHeavy =
    $result[
        'evaluations'
    ][
        1
    ];


$fixtureHeavyGw1 =
    $fixtureHeavy[
        'gameweeks'
    ][
        0
    ];


$fixtureHeavySelectedIds =
    array_column(
        $fixtureHeavyGw1[
            'selected_xi'
        ],
        'player_id'
    );


gameweekWeightCalibrationCheck(
    in_array(
        106,
        $fixtureHeavySelectedIds,
        true
    ),
    'Fixture-heavy weighting can select Player 106.'
);


gameweekWeightCalibrationCheck(
    !in_array(
        105,
        $fixtureHeavySelectedIds,
        true
    ),
    'Fixture-heavy weighting can bench Player 105.'
);


gameweekWeightCalibrationCheck(
    (
        $fixtureHeavyGw1[
            'selection_points_lost'
        ]
        ??
        null
    )
    ===
    0,
    'Fixture-heavy weighting achieves the best legal realised XI in Gameweek 1.'
);


/*
 * ============================================================
 * SCENARIO F: PRESERVED RISK MODIFIERS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Preserved Risk Modifiers<br>";
echo "============================================<br>";


$gw2 =
    $current[
        'gameweeks'
    ][
        1
    ];


$gw2SelectedIds =
    array_column(
        $gw2[
            'selected_xi'
        ],
        'player_id'
    );


gameweekWeightCalibrationCheck(
    !in_array(
        203,
        $gw2SelectedIds,
        true
    ),
    'Preserved confidence modifier can reduce Starting XI priority.'
);


gameweekWeightCalibrationCheck(
    !in_array(
        207,
        $gw2SelectedIds,
        true
    ),
    'Preserved availability modifier can reduce Starting XI priority.'
);


/*
 * ============================================================
 * SCENARIO G: PLAYER SCORE EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Player Score Evidence<br>";
echo "============================================<br>";


gameweekWeightCalibrationCheck(
    isset(
        $gw1[
            'player_scores'
        ]
    )
    &&
    is_array(
        $gw1[
            'player_scores'
        ]
    ),
    'Gameweek evaluation exposes player score evidence.'
);


gameweekWeightCalibrationCheck(
    count(
        $gw1[
            'player_scores'
        ]
    )
    ===
    15,
    'All valid preserved squad players are represented.'
);


$player105 =
    null;


foreach (
    $gw1[
        'player_scores'
    ]
    as $playerScore
) {

    if (
        (
            $playerScore[
                'player_id'
            ]
            ??
            null
        )
        !==
        105
    ) {

        continue;
    }


    $player105 =
        $playerScore;

    break;
}


gameweekWeightCalibrationCheck(
    is_array(
        $player105
    ),
    'Player 105 candidate score evidence is preserved.'
);


$expectedPlayer105Core =
    (
        92.0
        *
        0.45
    )
    +
    (
        90.0
        *
        0.25
    )
    +
    (
        55.0
        *
        0.30
    );


gameweekWeightCalibrationCheck(
    is_array(
        $player105
    )
    &&
    abs(
        (
            $player105[
                'core_gameweek_score'
            ]
            ??
            0
        )
        -
        $expectedPlayer105Core
    )
    <
    0.000001,
    'Candidate Gameweek core uses explicitly supplied weights.'
);


gameweekWeightCalibrationCheck(
    is_array(
        $player105
    )
    &&
    abs(
        (
            $player105[
                'gameweek_score'
            ]
            ??
            0
        )
        -
        $expectedPlayer105Core
    )
    <
    0.000001,
    'Candidate Gameweek Score applies preserved downstream modifiers.'
);


/*
 * ============================================================
 * SCENARIO H: ZERO AND NEGATIVE REALISED POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Zero And Negative Outcomes<br>";
echo "============================================<br>";


$gw3 =
    $current[
        'gameweeks'
    ][
        2
    ];


gameweekWeightCalibrationCheck(
    $gw3[
        'selected_actual_points'
    ]
    !==
    null,
    'Zero and negative realised points do not invalidate a complete historical gameweek.'
);


gameweekWeightCalibrationCheck(
    $gw3[
        'best_actual_points'
    ]
    !==
    null,
    'Best realised XI remains measurable when outcomes include zero or negative points.'
);


gameweekWeightCalibrationCheck(
    $gw3[
        'selection_points_lost'
    ]
    !==
    null,
    'Selection loss remains measurable with genuine zero and negative outcomes.'
);


/*
 * ============================================================
 * SCENARIO I: INCOMPLETE REALISED EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Incomplete Realised Evidence<br>";
echo "============================================<br>";


$gw4 =
    $current[
        'gameweeks'
    ][
        3
    ];


gameweekWeightCalibrationCheck(
    count(
        $gw4[
            'player_scores'
        ]
        ??
        []
    )
    ===
    15,
    'Candidate Gameweek Scores can still be produced when realised evidence is incomplete.'
);


gameweekWeightCalibrationCheck(
    (
        $gw4[
            'selected_actual_points'
        ]
        ??
        null
    )
    ===
    null,
    'Selected XI realised points are unavailable when the preserved squad outcome universe is incomplete.'
);


gameweekWeightCalibrationCheck(
    (
        $gw4[
            'best_actual_points'
        ]
        ??
        null
    )
    ===
    null,
    'Best legal realised XI is unavailable when one preserved squad outcome is missing.'
);


gameweekWeightCalibrationCheck(
    (
        $gw4[
            'selection_points_lost'
        ]
        ??
        null
    )
    ===
    null,
    'Selection points lost is unavailable rather than reconstructed.'
);


/*
 * ============================================================
 * SCENARIO J: AGGREGATE METRICS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Aggregate Metrics<br>";
echo "============================================<br>";


$metrics =
    $current[
        'metrics'
    ]
    ??
    [];

gameweekWeightCalibrationCheck(
    (
        $metrics[
            'total_gameweeks'
        ]
        ??
        null
    )
    ===
    4,
    'Metrics count all valid historical gameweek rows.'
);


gameweekWeightCalibrationCheck(
    (
        $metrics[
            'comparable_gameweeks'
        ]
        ??
        null
    )
    ===
    3,
    'Only complete historical outcome universes are comparable.'
);


gameweekWeightCalibrationCheck(
    (
        $metrics[
            'unavailable_gameweeks'
        ]
        ??
        null
    )
    ===
    1,
    'Incomplete realised evidence is counted as unavailable.'
);


gameweekWeightCalibrationCheck(
    isset(
        $metrics[
            'total_selection_points_lost'
        ]
    )
    &&
    is_numeric(
        $metrics[
            'total_selection_points_lost'
        ]
    ),
    'Metrics expose total Starting XI selection points lost.'
);


gameweekWeightCalibrationCheck(
    array_key_exists(
        'mean_selection_points_lost',
        $metrics
    ),
    'Metrics expose mean Starting XI selection points lost.'
);


gameweekWeightCalibrationCheck(
    isset(
        $metrics[
            'optimal_xi_selections'
        ]
    )
    &&
    is_numeric(
        $metrics[
            'optimal_xi_selections'
        ]
    ),
    'Metrics expose optimal Starting XI selection count.'
);


/*
 * ============================================================
 * SCENARIO K: MISSING COMPONENT EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Missing Component Evidence<br>";
echo "============================================<br>";


$missingComponentGameweek = [

    [
        'gameweek_id' => 20,

        'players' => [

            gameweekWeightCalibrationPlayer(
                2001,
                'GK',
                80.0,
                80.0,
                80.0,
                1.00,
                1.00,
                5
            )
        ]
    ]
];


unset(
    $missingComponentGameweek[
        0
    ][
        'players'
    ][
        0
    ][
        'components'
    ][
        'fixture'
    ]
);


$missingComponentResult =
    $service->evaluate(
        $missingComponentGameweek,
        [
            $weightCandidates[0]
        ]
    );


$missingPlayer =
    $missingComponentResult[
        'evaluations'
    ][
        0
    ][
        'gameweeks'
    ][
        0
    ][
        'player_scores'
    ][
        0
    ]
    ??
    [];


gameweekWeightCalibrationCheck(
    array_key_exists(
        'gameweek_score',
        $missingPlayer
    )
    &&
    $missingPlayer[
        'gameweek_score'
    ]
    ===
    null,
    'Missing required calibration component produces unavailable candidate Gameweek Score.'
);


/*
 * ============================================================
 * SCENARIO L: MALFORMED PLAYER ROWS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario L: Malformed Player Rows<br>";
echo "============================================<br>";


$malformedPlayerResult =
    $service->evaluate(
        [
            [
                'gameweek_id' => 30,

                'players' => [

                    'malformed player',

                    gameweekWeightCalibrationPlayer(
                        3001,
                        'GK',
                        80.0,
                        80.0,
                        80.0,
                        1.00,
                        1.00,
                        5
                    )
                ]
            ]
        ],
        [
            $weightCandidates[0]
        ]
    );


gameweekWeightCalibrationCheck(
    count(
        $malformedPlayerResult[
            'evaluations'
        ][
            0
        ][
            'gameweeks'
        ][
            0
        ][
            'player_scores'
        ]
        ??
        []
    )
    ===
    1,
    'Malformed player rows are ignored.'
);


/*
 * ============================================================
 * SCENARIO M: INVALID WEIGHT CANDIDATES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario M: Invalid Weight Candidates<br>";
echo "============================================<br>";


$invalidCandidates = [

    'not an array',

    [
        'intelligence_weight' => 0.45,
        'strength_weight' => 0.25
    ],

    [
        'intelligence_weight' => 'invalid',
        'strength_weight' => 0.25,
        'fixture_weight' => 0.75
    ],

    [
        'intelligence_weight' => -0.10,
        'strength_weight' => 0.50,
        'fixture_weight' => 0.60
    ],

    [
        'intelligence_weight' => 1.10,
        'strength_weight' => 0.00,
        'fixture_weight' => -0.10
    ],

    [
        'intelligence_weight' => 0.40,
        'strength_weight' => 0.30,
        'fixture_weight' => 0.20
    ]
];


foreach (
    $invalidCandidates
    as $index => $invalidCandidate
) {

    $threw =
        false;


    try {

        $service->evaluate(
            [],
            [
                $invalidCandidate
            ]
        );

    } catch (
        InvalidArgumentException $exception
    ) {

        $threw =
            true;
    }


    gameweekWeightCalibrationCheck(
        $threw,
        'Invalid weight candidate '
        . ($index + 1)
        . ' is rejected.'
    );
}


/*
 * ============================================================
 * SCENARIO N: FLOATING-POINT WEIGHT TOLERANCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario N: Floating Point Weight Tolerance<br>";
echo "============================================<br>";


$toleranceAccepted =
    true;


try {

    $service->evaluate(
        [],
        [
            [
                'intelligence_weight' => 0.1,
                'strength_weight' => 0.2,
                'fixture_weight' => 0.7
            ]
        ]
    );

} catch (
    InvalidArgumentException $exception
) {

    $toleranceAccepted =
        false;
}


gameweekWeightCalibrationCheck(
    $toleranceAccepted,
    'Valid floating-point weight sum is accepted.'
);


/*
 * ============================================================
 * SCENARIO O: EMPTY HISTORICAL EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario O: Empty Historical Evidence<br>";
echo "============================================<br>";


$emptyResult =
    $service->evaluate(
        [],
        [
            $weightCandidates[0]
        ]
    );


gameweekWeightCalibrationCheck(
    count(
        $emptyResult[
            'evaluations'
        ]
        ??
        []
    )
    ===
    1,
    'Weight candidate is still evaluated when historical evidence is empty.'
);


$emptyMetrics =
    $emptyResult[
        'evaluations'
    ][
        0
    ][
        'metrics'
    ]
    ??
    [];


gameweekWeightCalibrationCheck(
    (
        $emptyMetrics[
            'total_gameweeks'
        ]
        ??
        null
    )
    ===
    0,
    'Empty history reports zero total gameweeks.'
);


gameweekWeightCalibrationCheck(
    (
        $emptyMetrics[
            'comparable_gameweeks'
        ]
        ??
        null
    )
    ===
    0,
    'Empty history reports zero comparable gameweeks.'
);


gameweekWeightCalibrationCheck(
    (
        $emptyMetrics[
            'unavailable_gameweeks'
        ]
        ??
        null
    )
    ===
    0,
    'Empty history reports zero unavailable gameweeks.'
);


gameweekWeightCalibrationCheck(
    (
        $emptyMetrics[
            'total_selection_points_lost'
        ]
        ??
        null
    )
    ===
    0,
    'Empty history reports zero total selection points lost.'
);


gameweekWeightCalibrationCheck(
    (
        $emptyMetrics[
            'mean_selection_points_lost'
        ]
        ??
        null
    )
    ===
    null,
    'Empty history has no mean selection loss.'
);


gameweekWeightCalibrationCheck(
    (
        $emptyMetrics[
            'optimal_xi_selections'
        ]
        ??
        null
    )
    ===
    0,
    'Empty history reports zero optimal XI selections.'
);


/*
 * ============================================================
 * FINAL SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Gameweek Weight Calibration Service Test Summary<br>";
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