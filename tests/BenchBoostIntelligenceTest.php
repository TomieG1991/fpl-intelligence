<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Bench Boost Intelligence Test<br>";
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

function benchBoostCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

        echo "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


function benchBoostHeading(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * TEST DATA BUILDER
 * ============================================================
 *
 * Build a complete one-gameweek Squad Horizon-style result.
 *
 * Bench Boost Intelligence must consume existing projection
 * evidence. It must not calculate Expected Points itself.
 */

function buildBenchBoostGameweek(
    float $benchProjectedPoints = 14.5,
    array $benchConfidences = [
        0.80,
        0.70,
        0.60,
        0.90
    ],
    array $benchFixtureOpportunities = [
        60.0,
        70.0,
        80.0,
        90.0
    ],
    array $playerAvailability = []
): array {

    $players =
        [];


    for (
        $playerNumber = 1;
        $playerNumber <= 15;
        $playerNumber++
    ) {

        $isBench =
            $playerNumber >= 12;


        $benchIndex =
            $playerNumber
            -
            12;


        $projectionConfidence =
            $isBench
            &&
            array_key_exists(
                $benchIndex,
                $benchConfidences
            )
                ? $benchConfidences[
                    $benchIndex
                ]
                : 0.80;


        $fixtureOpportunity =
            $isBench
            &&
            array_key_exists(
                $benchIndex,
                $benchFixtureOpportunities
            )
                ? $benchFixtureOpportunities[
                    $benchIndex
                ]
                : 50.0;


        $chanceOfPlaying =
            array_key_exists(
                $playerNumber,
                $playerAvailability
            )
                ? $playerAvailability[
                    $playerNumber
                ]
                : 100.0;


        $players[] = [

            'player_id' =>
                $playerNumber,

            'name' =>
                'Player '
                . $playerNumber,

            'position' =>
                match (true) {

                    $playerNumber <= 2 =>
                        'GK',

                    $playerNumber <= 7 =>
                        'DEF',

                    $playerNumber <= 12 =>
                        'MID',

                    default =>
                        'FWD'
                },

            'projected_points' =>
                $isBench
                    ? 3.0
                    : 5.0,

            'projection_confidence' =>
                $projectionConfidence,

            'fixtures' => [

                [

                    'fixture_id' =>
                        100
                        +
                        $playerNumber,

                    'gameweek' =>
                        3,

                    'status' =>
                        'Projected',

                    'projected_points' =>
                        $isBench
                            ? 3.0
                            : 5.0,

                    'projected_minutes' =>
                        80.0,

                    'fixture_opportunity' =>
                        $fixtureOpportunity,

                    'projection' => [

                        'chance_of_playing' =>
                            $chanceOfPlaying
                    ]
                ]
            ]
        ];
    }


    $bench =
        array_slice(
            $players,
            11,
            4
        );


    $startingXi =
        array_slice(
            $players,
            0,
            11
        );


    return [

        'gameweek' =>
            3,

        'player_count' =>
            15,

        'players' =>
            $players,

        'starting_xi' =>
            $startingXi,

        'bench' =>
            $bench,

        'bench_coverage' => [

            /*
             * Existing SquadHorizonIntelligence is the source of
             * truth for projected bench points.
             */
            'bench_player_count' =>
                4,

            'total_projected_points' =>
                $benchProjectedPoints
        ]
    ];
}


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

benchBoostHeading(
    'Scenario A: Class Contract'
);


$classExists =
    class_exists(
        'BenchBoostIntelligence'
    );


benchBoostCheck(
    'BenchBoostIntelligence class exists',
    $classExists
);


/*
 * Stop cleanly during the first RED stage.
 *
 * The complete future test contract is already written below,
 * but PHP must not attempt to instantiate a class that does not
 * exist yet.
 */
if (
    !$classExists
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Bench Boost Intelligence Test Summary<br>";
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


$analyseMethodExists =
    method_exists(
        'BenchBoostIntelligence',
        'analyse'
    );


$createDecisionMethodExists =
    method_exists(
        'BenchBoostIntelligence',
        'createDecision'
    );


benchBoostCheck(
    'BenchBoostIntelligence exposes analyse()',
    $analyseMethodExists
);


benchBoostCheck(
    'BenchBoostIntelligence exposes createDecision()',
    $createDecisionMethodExists
);


if (
    !$analyseMethodExists
    ||
    !$createDecisionMethodExists
) {

    echo "<br>";
    echo "============================================<br>";
    echo "Bench Boost Intelligence Test Summary<br>";
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


$model =
    new BenchBoostIntelligence();


/*
 * ============================================================
 * SCENARIO B
 * PROJECTED BENCH POINTS
 * ============================================================
 */

benchBoostHeading(
    'Scenario B: Projected Bench Points'
);


$gameweek =
    buildBenchBoostGameweek(
        14.5
    );


$analysis =
    $model->analyse(
        $gameweek
    );


benchBoostCheck(
    'Analysis returns an array',
    is_array(
        $analysis
    )
);


benchBoostCheck(
    'Projected bench points come directly from Squad Horizon bench coverage',
    isset(
        $analysis[
            'projected_bench_points'
        ]
    )
    &&
    is_numeric(
        $analysis[
            'projected_bench_points'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'projected_bench_points'
            ]
        )
        -
        14.5
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO C
 * BENCH RELIABILITY
 * ============================================================
 */

benchBoostHeading(
    'Scenario C: Bench Reliability'
);


$gameweek =
    buildBenchBoostGameweek(
        14.5,
        [
            0.80,
            0.70,
            0.60,
            0.90
        ]
    );


$analysis =
    $model->analyse(
        $gameweek
    );


benchBoostCheck(
    'Bench reliability averages existing projection confidence',
    isset(
        $analysis[
            'bench_reliability'
        ]
    )
    &&
    is_numeric(
        $analysis[
            'bench_reliability'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'bench_reliability'
            ]
        )
        -
        0.75
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO D
 * FIXTURE QUALITY
 * ============================================================
 */

benchBoostHeading(
    'Scenario D: Fixture Quality'
);


$gameweek =
    buildBenchBoostGameweek(
        14.5,
        [
            0.80,
            0.70,
            0.60,
            0.90
        ],
        [
            60.0,
            70.0,
            80.0,
            90.0
        ]
    );


$analysis =
    $model->analyse(
        $gameweek
    );


benchBoostCheck(
    'Fixture quality averages existing bench fixture opportunity',
    isset(
        $analysis[
            'fixture_quality'
        ]
    )
    &&
    is_numeric(
        $analysis[
            'fixture_quality'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'fixture_quality'
            ]
        )
        -
        75.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO E
 * FULL-SQUAD AVAILABILITY
 * ============================================================
 */

benchBoostHeading(
    'Scenario E: Full-Squad Availability'
);


$gameweek =
    buildBenchBoostGameweek(
        14.5,
        [
            0.80,
            0.70,
            0.60,
            0.90
        ],
        [
            60.0,
            70.0,
            80.0,
            90.0
        ],
        [
            15 =>
                75.0
        ]
    );


$analysis =
    $model->analyse(
        $gameweek
    );


$expectedAvailability =
    (
        (
            14
            *
            100.0
        )
        +
        75.0
    )
    /
    15
    /
    100;


benchBoostCheck(
    'Full-squad availability averages existing chance-of-playing evidence',
    isset(
        $analysis[
            'full_squad_availability'
        ]
    )
    &&
    is_numeric(
        $analysis[
            'full_squad_availability'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'full_squad_availability'
            ]
        )
        -
        $expectedAvailability
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO F
 * ZERO AVAILABILITY
 * ============================================================
 */

benchBoostHeading(
    'Scenario F: Zero Availability'
);


$gameweek =
    buildBenchBoostGameweek(
        14.5,
        [
            0.80,
            0.70,
            0.60,
            0.90
        ],
        [
            60.0,
            70.0,
            80.0,
            90.0
        ],
        [
            15 =>
                0.0
        ]
    );


$analysis =
    $model->analyse(
        $gameweek
    );


$expectedZeroAvailability =
    (
        14
        *
        100.0
    )
    /
    15
    /
    100;


benchBoostCheck(
    'Zero percent chance of playing remains real availability evidence',
    isset(
        $analysis[
            'full_squad_availability'
        ]
    )
    &&
    is_numeric(
        $analysis[
            'full_squad_availability'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'full_squad_availability'
            ]
        )
        -
        $expectedZeroAvailability
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO G
 * DOUBLE GAMEWEEK AVAILABILITY
 * ============================================================
 */

benchBoostHeading(
    'Scenario G: Double Gameweek Availability'
);


$gameweek =
    buildBenchBoostGameweek();


/*
 * Give Player 15 a second fixture.
 *
 * Availability belongs to the player, not the number of
 * fixtures. The player must therefore count once when measuring
 * full-squad availability.
 */
$gameweek[
    'players'
][14][
    'fixtures'
][] = [

    'fixture_id' =>
        999,

    'gameweek' =>
        3,

    'status' =>
        'Projected',

    'projected_points' =>
        3.0,

    'projected_minutes' =>
        80.0,

        
    'fixture_opportunity' =>
        70.0,

    'projection' => [

        'chance_of_playing' =>
            100.0
    ]
    
];


$analysis =
    $model->analyse(
        $gameweek
    );


benchBoostCheck(
    'Double Gameweek does not count one player twice for squad availability',
    isset(
        $analysis[
            'full_squad_availability'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'full_squad_availability'
            ]
        )
        -
        1.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO H
 * MISSING BENCH RELIABILITY
 * ============================================================
 */

benchBoostHeading(
    'Scenario H: Missing Bench Reliability'
);


$gameweek =
    buildBenchBoostGameweek();


$gameweek[
    'bench'
][2][
    'projection_confidence'
] =
    null;


$analysis =
    $model->analyse(
        $gameweek
    );


benchBoostCheck(
    'Incomplete bench confidence evidence produces null bench reliability',
    array_key_exists(
        'bench_reliability',
        $analysis
    )
    &&
    $analysis[
        'bench_reliability'
    ]
    ===
    null
);


/*
 * ============================================================
 * SCENARIO I
 * MISSING FIXTURE QUALITY
 * ============================================================
 */

benchBoostHeading(
    'Scenario I: Missing Fixture Quality'
);


$gameweek =
    buildBenchBoostGameweek();


foreach (
    $gameweek[
        'bench'
    ]
    as &$benchPlayer
) {

    $benchPlayer[
        'fixtures'
    ] =
        [];
}

unset(
    $benchPlayer
);


$analysis =
    $model->analyse(
        $gameweek
    );


benchBoostCheck(
    'Missing bench fixture-opportunity evidence produces null fixture quality',
    array_key_exists(
        'fixture_quality',
        $analysis
    )
    &&
    $analysis[
        'fixture_quality'
    ]
    ===
    null
);


/*
 * ============================================================
 * SCENARIO J
 * HOLD DECISION
 * ============================================================
 */

benchBoostHeading(
    'Scenario J: Hold Decision'
);


$gameweek =
    buildBenchBoostGameweek(
        8.0
    );


$analysis =
    $model->analyse(
        $gameweek
    );


$decision =
    $model->createDecision(
        $analysis
    );


benchBoostCheck(
    'Eight projected bench points recommends Hold',
    $decision instanceof ChipDecision
    &&
    $decision->getChip()
    ===
    'Bench Boost'
    &&
    $decision->getRecommendation()
    ===
    'Hold'
);


/*
 * ============================================================
 * SCENARIO K
 * CONSIDER DECISION
 * ============================================================
 */

benchBoostHeading(
    'Scenario K: Consider Decision'
);


$gameweek =
    buildBenchBoostGameweek(
        11.0
    );


$analysis =
    $model->analyse(
        $gameweek
    );


$decision =
    $model->createDecision(
        $analysis
    );


benchBoostCheck(
    'Eleven projected bench points recommends Consider',
    $decision instanceof ChipDecision
    &&
    $decision->getRecommendation()
    ===
    'Consider'
);


/*
 * ============================================================
 * SCENARIO L
 * USE DECISION
 * ============================================================
 */

benchBoostHeading(
    'Scenario L: Use Decision'
);


$gameweek =
    buildBenchBoostGameweek(
        15.0
    );


$analysis =
    $model->analyse(
        $gameweek
    );


$decision =
    $model->createDecision(
        $analysis
    );


benchBoostCheck(
    'Fifteen projected bench points recommends Use',
    $decision instanceof ChipDecision
    &&
    $decision->getRecommendation()
    ===
    'Use'
);


/*
 * ============================================================
 * SCENARIO M
 * DECISION CONFIDENCE
 * ============================================================
 */

benchBoostHeading(
    'Scenario M: Decision Confidence'
);


$gameweek =
    buildBenchBoostGameweek(
        15.0,
        [
            0.80,
            0.70,
            0.60,
            0.90
        ]
    );


$analysis =
    $model->analyse(
        $gameweek
    );


$decision =
    $model->createDecision(
        $analysis
    );


benchBoostCheck(
    'Decision confidence is capped by the weaker reliability evidence',
    abs(
        $decision->getConfidence()
        -
        min(
            (float) $analysis[
                'bench_reliability'
            ],
            (float) $analysis[
                'full_squad_availability'
            ]
        )
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO N
 * DECISION EXPLANATION
 * ============================================================
 */

benchBoostHeading(
    'Scenario N: Decision Explanation'
);


$decisionArray =
    $decision->toArray();


benchBoostCheck(
    'Bench Boost decision exposes non-empty explanation',
    isset(
        $decisionArray[
            'explanation'
        ]
    )
    &&
    is_string(
        $decisionArray[
            'explanation'
        ]
    )
    &&
    trim(
        $decisionArray[
            'explanation'
        ]
    )
    !==
    ''
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Bench Boost Intelligence Test Summary<br>";
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

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}