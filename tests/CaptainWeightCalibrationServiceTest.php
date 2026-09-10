<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Captain Weight Calibration Service Test<br>";
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

function captainWeightCalibrationCheck(
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
 * CONTROLLED HISTORICAL CAPTAIN EVIDENCE
 * ============================================================
 *
 * Each gameweek contains the complete preserved Captain
 * Intelligence universe plus realised FPL points.
 *
 * The service must:
 *
 * - replay alternative Captain core weightings
 * - apply the preserved historical confidence modifier
 * - apply the preserved historical availability modifier
 * - rank candidates within each gameweek
 * - select the highest candidate Captain Score
 * - compare the selected captain with the best realised captain
 * - calculate captain points lost
 *
 * No live intelligence may be reconstructed.
 */

$historicalGameweeks = [

    /*
     * ========================================================
     * GAMEWEEK 1
     * ========================================================
     *
     * Under the current 35 / 35 / 30 style of balance,
     * Player 101 should be selected.
     *
     * Under a very fixture-heavy candidate,
     * Player 102 should be selected.
     */

    [
        'gameweek_id' => 1,

        'players' => [

            [
                'player_id' => 101,

                'components' => [
                    'strength' => 90.0,
                    'fixture' => 60.0,
                    'attacking_threat' => 85.0,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => 12
            ],

            [
                'player_id' => 102,

                'components' => [
                    'strength' => 70.0,
                    'fixture' => 95.0,
                    'attacking_threat' => 60.0,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => 6
            ],

            [
                'player_id' => 103,

                'components' => [
                    'strength' => 80.0,
                    'fixture' => 70.0,
                    'attacking_threat' => 75.0,
                    'confidence_modifier' => 0.90,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => 15
            ]
        ]
    ],


    /*
     * ========================================================
     * GAMEWEEK 2
     * ========================================================
     *
     * This gameweek is designed to prove that preserved
     * confidence and availability modifiers remain part of
     * candidate score replay.
     */

    [
        'gameweek_id' => 2,

        'players' => [

            [
                'player_id' => 201,

                'components' => [
                    'strength' => 95.0,
                    'fixture' => 90.0,
                    'attacking_threat' => 95.0,
                    'confidence_modifier' => 0.70,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => 2
            ],

            [
                'player_id' => 202,

                'components' => [
                    'strength' => 82.0,
                    'fixture' => 82.0,
                    'attacking_threat' => 82.0,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => 10
            ],

            [
                'player_id' => 203,

                'components' => [
                    'strength' => 88.0,
                    'fixture' => 78.0,
                    'attacking_threat' => 90.0,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 0.80
                ],

                'actual_points' => 14
            ]
        ]
    ],


    /*
     * ========================================================
     * GAMEWEEK 3
     * ========================================================
     *
     * Zero realised points are valid.
     *
     * Negative realised points are also valid.
     */

    [
        'gameweek_id' => 3,

        'players' => [

            [
                'player_id' => 301,

                'components' => [
                    'strength' => 75.0,
                    'fixture' => 75.0,
                    'attacking_threat' => 75.0,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => 0
            ],

            [
                'player_id' => 302,

                'components' => [
                    'strength' => 65.0,
                    'fixture' => 80.0,
                    'attacking_threat' => 70.0,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => -1
            ],

            [
                'player_id' => 303,

                'components' => [
                    'strength' => 60.0,
                    'fixture' => 60.0,
                    'attacking_threat' => 60.0,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => 5
            ]
        ]
    ],


    /*
     * ========================================================
     * GAMEWEEK 4
     * ========================================================
     *
     * One player is missing realised points.
     *
     * Candidate scoring should still be possible for all valid
     * component rows, but this gameweek cannot produce a fair
     * captain-points-lost comparison.
     */

    [
        'gameweek_id' => 4,

        'players' => [

            [
                'player_id' => 401,

                'components' => [
                    'strength' => 85.0,
                    'fixture' => 85.0,
                    'attacking_threat' => 85.0,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => 8
            ],

            [
                'player_id' => 402,

                'components' => [
                    'strength' => 80.0,
                    'fixture' => 80.0,
                    'attacking_threat' => 80.0,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => null
            ]
        ]
    ],


    /*
     * Malformed gameweek rows must be ignored.
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
        'strength_weight' => 0.35,
        'fixture_weight' => 0.35,
        'attacking_threat_weight' => 0.30
    ],

    /*
     * Fixture-heavy.
     */
    [
        'strength_weight' => 0.10,
        'fixture_weight' => 0.80,
        'attacking_threat_weight' => 0.10
    ],

    /*
     * Strength-only boundary.
     */
    [
        'strength_weight' => 1.00,
        'fixture_weight' => 0.00,
        'attacking_threat_weight' => 0.00
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
        'CaptainWeightCalibrationService'
    );


captainWeightCalibrationCheck(
    $classExists,
    'CaptainWeightCalibrationService class exists.'
);


captainWeightCalibrationCheck(
    $classExists
    &&
    method_exists(
        'CaptainWeightCalibrationService',
        'evaluate'
    ),
    'CaptainWeightCalibrationService exposes evaluate().'
);


if (!$classExists) {

    echo "<br>";
    echo "============================================<br>";
    echo "Captain Weight Calibration Service Test Summary<br>";
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
    new CaptainWeightCalibrationService();


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


captainWeightCalibrationCheck(
    is_array(
        $result
    ),
    'evaluate() returns an array.'
);


captainWeightCalibrationCheck(
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


captainWeightCalibrationCheck(
    count(
        $result[
            'evaluations'
        ]
    )
    ===
    3,
    'Every supplied Captain weight candidate is evaluated.'
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


captainWeightCalibrationCheck(
    (
        $current[
            'strength_weight'
        ]
        ??
        null
    )
    ===
    0.35,
    'Strength weight is preserved.'
);


captainWeightCalibrationCheck(
    (
        $current[
            'fixture_weight'
        ]
        ??
        null
    )
    ===
    0.35,
    'Fixture weight is preserved.'
);


captainWeightCalibrationCheck(
    (
        $current[
            'attacking_threat_weight'
        ]
        ??
        null
    )
    ===
    0.30,
    'Attacking Threat weight is preserved.'
);


captainWeightCalibrationCheck(
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


captainWeightCalibrationCheck(
    count(
        $current[
            'gameweeks'
        ]
    )
    ===
    4,
    'Only valid gameweek rows are evaluated.'
);


/*
 * ============================================================
 * SCENARIO D: GAMEWEEK 1 REPLAY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Gameweek 1 Replay<br>";
echo "============================================<br>";


$gw1 =
    $current[
        'gameweeks'
    ][
        0
    ];


captainWeightCalibrationCheck(
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


captainWeightCalibrationCheck(
    (
        $gw1[
            'selected_player_id'
        ]
        ??
        null
    )
    ===
    101,
    'Current weighting selects Player 101 in Gameweek 1.'
);


captainWeightCalibrationCheck(
    abs(
        (
            $gw1[
                'selected_captain_score'
            ]
            ??
            0
        )
        -
        78.0
    )
    <
    0.000001,
    'Current weighting calculates the expected Gameweek 1 Captain Score.'
);


captainWeightCalibrationCheck(
    (
        $gw1[
            'selected_actual_points'
        ]
        ??
        null
    )
    ===
    12,
    'Selected captain realised points are preserved.'
);


captainWeightCalibrationCheck(
    (
        $gw1[
            'best_actual_player_id'
        ]
        ??
        null
    )
    ===
    103,
    'Best realised Gameweek 1 captain is identified.'
);


captainWeightCalibrationCheck(
    (
        $gw1[
            'best_actual_points'
        ]
        ??
        null
    )
    ===
    15,
    'Best realised Gameweek 1 captain points are preserved.'
);


captainWeightCalibrationCheck(
    (
        $gw1[
            'captain_points_lost'
        ]
        ??
        null
    )
    ===
    3,
    'Gameweek 1 captain points lost is calculated correctly.'
);


/*
 * ============================================================
 * SCENARIO E: PRESERVED RISK MODIFIERS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Preserved Risk Modifiers<br>";
echo "============================================<br>";


$gw2 =
    $current[
        'gameweeks'
    ][
        1
    ];


captainWeightCalibrationCheck(
    (
        $gw2[
            'selected_player_id'
        ]
        ??
        null
    )
    ===
    202,
    'Preserved confidence and availability modifiers affect captain selection.'
);


captainWeightCalibrationCheck(
    (
        $gw2[
            'best_actual_player_id'
        ]
        ??
        null
    )
    ===
    203,
    'Best realised Gameweek 2 captain is identified independently of model selection.'
);


captainWeightCalibrationCheck(
    (
        $gw2[
            'captain_points_lost'
        ]
        ??
        null
    )
    ===
    4,
    'Gameweek 2 captain points lost is calculated correctly.'
);


/*
 * ============================================================
 * SCENARIO F: ZERO AND NEGATIVE ACTUAL POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Zero And Negative Actual Points<br>";
echo "============================================<br>";


$gw3 =
    $current[
        'gameweeks'
    ][
        2
    ];


captainWeightCalibrationCheck(
    (
        $gw3[
            'selected_player_id'
        ]
        ??
        null
    )
    ===
    301,
    'Current weighting selects Player 301 in Gameweek 3.'
);


captainWeightCalibrationCheck(
    (
        $gw3[
            'selected_actual_points'
        ]
        ??
        null
    )
    ===
    0,
    'Zero realised captain points remain valid.'
);


captainWeightCalibrationCheck(
    (
        $gw3[
            'best_actual_player_id'
        ]
        ??
        null
    )
    ===
    303,
    'Positive realised alternative is identified when other players scored zero or negative.'
);


captainWeightCalibrationCheck(
    (
        $gw3[
            'captain_points_lost'
        ]
        ??
        null
    )
    ===
    5,
    'Captain points lost is valid when selected captain scores zero.'
);


/*
 * ============================================================
 * SCENARIO G: INCOMPLETE REALISED OUTCOME EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Incomplete Realised Outcome Evidence<br>";
echo "============================================<br>";


$gw4 =
    $current[
        'gameweeks'
    ][
        3
    ];


captainWeightCalibrationCheck(
    (
        $gw4[
            'selected_player_id'
        ]
        ??
        null
    )
    ===
    401,
    'Candidate scoring still selects a captain when one realised outcome is missing.'
);


captainWeightCalibrationCheck(
    array_key_exists(
        'selected_actual_points',
        $gw4
    )
    &&
    $gw4[
        'selected_actual_points'
    ]
    ===
    8,
    'Selected captain realised points remain available when known.'
);


captainWeightCalibrationCheck(
    array_key_exists(
        'best_actual_player_id',
        $gw4
    )
    &&
    $gw4[
        'best_actual_player_id'
    ]
    ===
    null,
    'Best realised captain is unavailable when the gameweek comparison universe has incomplete outcomes.'
);


captainWeightCalibrationCheck(
    array_key_exists(
        'best_actual_points',
        $gw4
    )
    &&
    $gw4[
        'best_actual_points'
    ]
    ===
    null,
    'Best realised captain points are unavailable when comparison evidence is incomplete.'
);


captainWeightCalibrationCheck(
    array_key_exists(
        'captain_points_lost',
        $gw4
    )
    &&
    $gw4[
        'captain_points_lost'
    ]
    ===
    null,
    'Captain points lost is unavailable rather than manufactured from incomplete outcomes.'
);


/*
 * ============================================================
 * SCENARIO H: AGGREGATE METRICS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Aggregate Metrics<br>";
echo "============================================<br>";


$metrics =
    $current[
        'metrics'
    ]
    ??
    [];


captainWeightCalibrationCheck(
    (
        $metrics[
            'total_gameweeks'
        ]
        ??
        null
    )
    ===
    4,
    'Aggregate metrics count valid historical gameweeks.'
);


captainWeightCalibrationCheck(
    (
        $metrics[
            'comparable_gameweeks'
        ]
        ??
        null
    )
    ===
    3,
    'Aggregate metrics count only gameweeks with complete realised captain comparison evidence.'
);


captainWeightCalibrationCheck(
    (
        $metrics[
            'unavailable_gameweeks'
        ]
        ??
        null
    )
    ===
    1,
    'Aggregate metrics identify gameweeks unavailable for fair realised comparison.'
);


captainWeightCalibrationCheck(
    (
        $metrics[
            'total_captain_points_lost'
        ]
        ??
        null
    )
    ===
    12,
    'Total captain points lost is aggregated across comparable gameweeks.'
);


captainWeightCalibrationCheck(
    abs(
        (
            $metrics[
                'mean_captain_points_lost'
            ]
            ??
            0
        )
        -
        4.0
    )
    <
    0.000001,
    'Mean captain points lost is calculated correctly.'
);


captainWeightCalibrationCheck(
    (
        $metrics[
            'optimal_captain_selections'
        ]
        ??
        null
    )
    ===
    0,
    'Optimal captain selections count exact zero-loss gameweeks.'
);


/*
 * ============================================================
 * SCENARIO I: ALTERNATIVE WEIGHTS CHANGE SELECTION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Alternative Weights Change Selection<br>";
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


captainWeightCalibrationCheck(
    (
        $fixtureHeavyGw1[
            'selected_player_id'
        ]
        ??
        null
    )
    ===
    102,
    'Fixture-heavy weighting changes the Gameweek 1 captain selection.'
);


captainWeightCalibrationCheck(
    (
        $fixtureHeavyGw1[
            'captain_points_lost'
        ]
        ??
        null
    )
    ===
    9,
    'Fixture-heavy weighting produces the expected Gameweek 1 captain points lost.'
);


/*
 * ============================================================
 * SCENARIO J: PLAYER SCORE DETAIL
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Player Score Detail<br>";
echo "============================================<br>";


$gw1PlayerScores =
    $gw1[
        'player_scores'
    ]
    ??
    [];


captainWeightCalibrationCheck(
    count(
        $gw1PlayerScores
    )
    ===
    3,
    'Per-gameweek result preserves every valid Captain Intelligence candidate.'
);


captainWeightCalibrationCheck(
    (
        $gw1PlayerScores[
            0
        ][
            'player_id'
        ]
        ??
        null
    )
    ===
    101,
    'Player identity is preserved in candidate score detail.'
);


captainWeightCalibrationCheck(
    abs(
        (
            $gw1PlayerScores[
                0
            ][
                'core_captain_score'
            ]
            ??
            0
        )
        -
        78.0
    )
    <
    0.000001,
    'Candidate core Captain Score is exposed.'
);


captainWeightCalibrationCheck(
    abs(
        (
            $gw1PlayerScores[
                0
            ][
                'captain_score'
            ]
            ??
            0
        )
        -
        78.0
    )
    <
    0.000001,
    'Candidate final Captain Score is exposed.'
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


$missingComponentHistory = [

    [
        'gameweek_id' => 10,

        'players' => [

            [
                'player_id' => 1001,

                'components' => [
                    'strength' => 80.0,
                    'fixture' => 80.0,
                    'attacking_threat' => null,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => 10
            ],

            [
                'player_id' => 1002,

                'components' => [
                    'strength' => 70.0,
                    'fixture' => 70.0,
                    'attacking_threat' => 70.0,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => 5
            ]
        ]
    ]
];


$missingComponentResult =
    $service->evaluate(
        $missingComponentHistory,
        [
            [
                'strength_weight' => 0.35,
                'fixture_weight' => 0.35,
                'attacking_threat_weight' => 0.30
            ]
        ]
    );


$missingComponentGw =
    $missingComponentResult[
        'evaluations'
    ][
        0
    ][
        'gameweeks'
    ][
        0
    ];


captainWeightCalibrationCheck(
    (
        $missingComponentGw[
            'selected_player_id'
        ]
        ??
        null
    )
    ===
    1002,
    'Player missing required Captain component evidence is not selected from an invented score.'
);


captainWeightCalibrationCheck(
    count(
        $missingComponentGw[
            'player_scores'
        ]
        ??
        []
    )
    ===
    2,
    'Malformed scoring evidence remains visible in player score detail.'
);


captainWeightCalibrationCheck(
    array_key_exists(
        'captain_score',
        $missingComponentGw[
            'player_scores'
        ][
            0
        ]
    )
    &&
    $missingComponentGw[
        'player_scores'
    ][
        0
    ][
        'captain_score'
    ]
    ===
    null,
    'Missing required component evidence produces null candidate Captain Score.'
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


$malformedPlayerHistory = [

    [
        'gameweek_id' => 11,

        'players' => [

            'malformed player',

            [
                'player_id' => 1101,

                'components' => [
                    'strength' => 75.0,
                    'fixture' => 75.0,
                    'attacking_threat' => 75.0,
                    'confidence_modifier' => 1.00,
                    'availability_modifier' => 1.00
                ],

                'actual_points' => 7
            ]
        ]
    ]
];


$malformedPlayerResult =
    $service->evaluate(
        $malformedPlayerHistory,
        [
            [
                'strength_weight' => 1.00,
                'fixture_weight' => 0.00,
                'attacking_threat_weight' => 0.00
            ]
        ]
    );


$malformedPlayerGw =
    $malformedPlayerResult[
        'evaluations'
    ][
        0
    ][
        'gameweeks'
    ][
        0
    ];


captainWeightCalibrationCheck(
    count(
        $malformedPlayerGw[
            'player_scores'
        ]
        ??
        []
    )
    ===
    1,
    'Malformed non-array player rows are ignored.'
);


/*
 * ============================================================
 * SCENARIO M: INVALID CANDIDATES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario M: Invalid Candidates<br>";
echo "============================================<br>";


$invalidCandidates = [

    'not an array',

    [
        'fixture_weight' => 0.50,
        'attacking_threat_weight' => 0.50
    ],

    [
        'strength_weight' => 'invalid',
        'fixture_weight' => 0.50,
        'attacking_threat_weight' => 0.50
    ],

    [
        'strength_weight' => -0.10,
        'fixture_weight' => 0.60,
        'attacking_threat_weight' => 0.50
    ],

    [
        'strength_weight' => 1.10,
        'fixture_weight' => 0.00,
        'attacking_threat_weight' => -0.10
    ],

    [
        'strength_weight' => 0.40,
        'fixture_weight' => 0.40,
        'attacking_threat_weight' => 0.40
    ]
];


foreach (
    $invalidCandidates
    as $invalidCandidate
) {

    $exceptionThrown =
        false;


    try {

        $service->evaluate(
            $historicalGameweeks,
            [
                $invalidCandidate
            ]
        );

    } catch (
        InvalidArgumentException $exception
    ) {

        $exceptionThrown =
            true;
    }


    captainWeightCalibrationCheck(
        $exceptionThrown,
        'Invalid Captain weight candidate is rejected.'
    );
}


/*
 * ============================================================
 * SCENARIO N: FLOATING-POINT WEIGHT SUM TOLERANCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario N: Floating-Point Weight Sum Tolerance<br>";
echo "============================================<br>";


$floatingResult =
    $service->evaluate(
        [],
        [
            [
                'strength_weight' => 0.1,
                'fixture_weight' => 0.2,
                'attacking_threat_weight' => 0.7
            ]
        ]
    );


captainWeightCalibrationCheck(
    count(
        $floatingResult[
            'evaluations'
        ]
        ??
        []
    )
    ===
    1,
    'Valid floating-point Captain weight sum is accepted.'
);


/*
 * ============================================================
 * SCENARIO O: EMPTY HISTORY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario O: Empty History<br>";
echo "============================================<br>";


$emptyResult =
    $service->evaluate(
        [],
        $weightCandidates
    );


captainWeightCalibrationCheck(
    count(
        $emptyResult[
            'evaluations'
        ]
        ??
        []
    )
    ===
    3,
    'Empty historical sample still evaluates every supplied candidate.'
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


captainWeightCalibrationCheck(
    (
        $emptyMetrics[
            'total_gameweeks'
        ]
        ??
        null
    )
    ===
    0,
    'Empty historical sample reports zero total gameweeks.'
);


captainWeightCalibrationCheck(
    (
        $emptyMetrics[
            'comparable_gameweeks'
        ]
        ??
        null
    )
    ===
    0,
    'Empty historical sample reports zero comparable gameweeks.'
);


captainWeightCalibrationCheck(
    (
        $emptyMetrics[
            'unavailable_gameweeks'
        ]
        ??
        null
    )
    ===
    0,
    'Empty historical sample reports zero unavailable gameweeks.'
);


captainWeightCalibrationCheck(
    (
        $emptyMetrics[
            'total_captain_points_lost'
        ]
        ??
        null
    )
    ===
    0,
    'Empty historical sample reports zero total captain points lost.'
);


captainWeightCalibrationCheck(
    array_key_exists(
        'mean_captain_points_lost',
        $emptyMetrics
    )
    &&
    $emptyMetrics[
        'mean_captain_points_lost'
    ]
    ===
    null,
    'Empty historical sample has no mean captain points lost.'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Captain Weight Calibration Service Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}