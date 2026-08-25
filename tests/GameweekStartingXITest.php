<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Starting XI Test<br>";
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

function gameweekStartingXICheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

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


/*
 * ============================================================
 * PLAYER HELPER
 * ============================================================
 */

function gameweekStartingXIPlayer(
    int $id,
    string $name,
    string $position,
    float $intelligence,
    float $strength,
    float $fixture,
    float $availability = 100.0,
    float $confidence = 100.0
): array {

    return [

        'player_id' =>
            $id,

        'name' =>
            $name,

        'position' =>
            $position,

        'intelligence_score' =>
            $intelligence,

        'strength_rating' =>
            $strength,

        'next_fixture_rating' =>
            $fixture,

        'availability_rating' =>
            $availability,

        'sample_confidence' =>
            $confidence
    ];
}


/*
 * ============================================================
 * BASE SQUAD
 * ============================================================
 */

$baseSquad = [

    gameweekStartingXIPlayer(
        1,
        'GK One',
        'GK',
        80,
        80,
        80
    ),

    gameweekStartingXIPlayer(
        2,
        'GK Two',
        'GK',
        60,
        60,
        60
    ),

    gameweekStartingXIPlayer(
        3,
        'DEF One',
        'DEF',
        90,
        90,
        90
    ),

    gameweekStartingXIPlayer(
        4,
        'DEF Two',
        'DEF',
        85,
        85,
        85
    ),

    gameweekStartingXIPlayer(
        5,
        'DEF Three',
        'DEF',
        80,
        80,
        80
    ),

    gameweekStartingXIPlayer(
        6,
        'DEF Four',
        'DEF',
        60,
        60,
        60
    ),

    gameweekStartingXIPlayer(
        7,
        'DEF Five',
        'DEF',
        50,
        50,
        50
    ),

    gameweekStartingXIPlayer(
        8,
        'MID One',
        'MID',
        95,
        95,
        95
    ),

    gameweekStartingXIPlayer(
        9,
        'MID Two',
        'MID',
        90,
        90,
        90
    ),

    gameweekStartingXIPlayer(
        10,
        'MID Three',
        'MID',
        85,
        85,
        85
    ),

    gameweekStartingXIPlayer(
        11,
        'MID Four',
        'MID',
        80,
        80,
        80
    ),

    gameweekStartingXIPlayer(
        12,
        'MID Five',
        'MID',
        70,
        70,
        70
    ),

    gameweekStartingXIPlayer(
        13,
        'FWD One',
        'FWD',
        94,
        94,
        94
    ),

    gameweekStartingXIPlayer(
        14,
        'FWD Two',
        'FWD',
        88,
        88,
        88
    ),

    gameweekStartingXIPlayer(
        15,
        'FWD Three',
        'FWD',
        82,
        82,
        82
    )
];


$optimizer =
    new GameweekStartingXI();


/*
 * ============================================================
 * SCENARIO A
 * VALID SQUAD
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Valid Squad<br>";
echo "============================================<br>";


$result =
    $optimizer
        ->optimize(
            $baseSquad
        );


gameweekStartingXICheck(
    'Gameweek Starting XI returns an array',
    is_array(
        $result
    )
);


gameweekStartingXICheck(
    'Gameweek Starting XI returns success',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


gameweekStartingXICheck(
    'Starting XI contains exactly 11 players',
    count(
        $result[
            'starting_xi'
        ]
        ?? []
    )
    === 11
);


gameweekStartingXICheck(
    'Bench contains exactly four players',
    count(
        $result[
            'bench'
        ]
        ?? []
    )
    === 4
);


gameweekStartingXICheck(
    'All eight legal formations are evaluated',
    (
        $result[
            'formation_count'
        ]
        ?? null
    )
    === 8
);


gameweekStartingXICheck(
    'Formation is returned',
    !empty(
        $result[
            'formation'
        ]
        ?? null
    )
);


echo "Formation: "
    . htmlspecialchars(
        (string) (
            $result[
                'formation'
            ]
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * PLAYER SCORE OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Player Score Output<br>";
echo "============================================<br>";


$allPlayers =
    array_merge(
        $result[
            'starting_xi'
        ]
        ?? [],
        $result[
            'bench'
        ]
        ?? []
    );


$allHaveGameweekScore =
    true;


$allHaveComponents =
    true;


foreach (
    $allPlayers
    as $player
) {

    if (
        !is_numeric(
            $player[
                'gameweek_score'
            ]
            ?? null
        )
    ) {

        $allHaveGameweekScore =
            false;
    }


    if (
        !isset(
            $player[
                'gameweek_components'
            ]
        )
        ||
        !is_array(
            $player[
                'gameweek_components'
            ]
        )
    ) {

        $allHaveComponents =
            false;
    }
}


gameweekStartingXICheck(
    'All players contain Gameweek Score',
    $allHaveGameweekScore
);


gameweekStartingXICheck(
    'All players contain Gameweek components',
    $allHaveComponents
);


/*
 * ============================================================
 * SCENARIO C
 * FIXTURE IMPACT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Fixture Impact<br>";
echo "============================================<br>";


$goodFixturePlayer =
    gameweekStartingXIPlayer(
        100,
        'Fixture Good',
        'MID',
        70,
        70,
        100
    );


$badFixturePlayer =
    gameweekStartingXIPlayer(
        101,
        'Fixture Bad',
        'MID',
        70,
        70,
        0
    );


$fixtureSquad =
    $baseSquad;


$fixtureSquad[
    10
] =
    $goodFixturePlayer;


$fixtureSquad[
    11
] =
    $badFixturePlayer;


$fixtureResult =
    $optimizer
        ->optimize(
            $fixtureSquad
        );


$fixturePlayers =
    array_merge(
        $fixtureResult[
            'starting_xi'
        ]
        ?? [],
        $fixtureResult[
            'bench'
        ]
        ?? []
    );


$goodFixtureScore =
    null;


$badFixtureScore =
    null;


foreach (
    $fixturePlayers
    as $player
) {

    if (
        (
            $player[
                'player_id'
            ]
            ?? null
        )
        === 100
    ) {

        $goodFixtureScore =
            $player[
                'gameweek_score'
            ]
            ?? null;
    }


    if (
        (
            $player[
                'player_id'
            ]
            ?? null
        )
        === 101
    ) {

        $badFixtureScore =
            $player[
                'gameweek_score'
            ]
            ?? null;
    }
}


gameweekStartingXICheck(
    'Better immediate fixture produces higher Gameweek Score',
    is_numeric(
        $goodFixtureScore
    )
    &&
    is_numeric(
        $badFixtureScore
    )
    &&
    $goodFixtureScore
    >
    $badFixtureScore
);


/*
 * ============================================================
 * SCENARIO D
 * AVAILABILITY IMPACT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Availability Impact<br>";
echo "============================================<br>";


$availablePlayer =
    gameweekStartingXIPlayer(
        200,
        'Available',
        'MID',
        80,
        80,
        80,
        100,
        100
    );


$unavailablePlayer =
    gameweekStartingXIPlayer(
        201,
        'Unavailable',
        'MID',
        80,
        80,
        80,
        0,
        100
    );


$availabilitySquad =
    $baseSquad;


$availabilitySquad[
    10
] =
    $availablePlayer;


$availabilitySquad[
    11
] =
    $unavailablePlayer;


$availabilityResult =
    $optimizer
        ->optimize(
            $availabilitySquad
        );


$availabilityPlayers =
    array_merge(
        $availabilityResult[
            'starting_xi'
        ]
        ?? [],
        $availabilityResult[
            'bench'
        ]
        ?? []
    );


$availableScore =
    null;


$unavailableScore =
    null;


foreach (
    $availabilityPlayers
    as $player
) {

    if (
        (
            $player[
                'player_id'
            ]
            ?? null
        )
        === 200
    ) {

        $availableScore =
            $player[
                'gameweek_score'
            ]
            ?? null;
    }


    if (
        (
            $player[
                'player_id'
            ]
            ?? null
        )
        === 201
    ) {

        $unavailableScore =
            $player[
                'gameweek_score'
            ]
            ?? null;
    }
}


gameweekStartingXICheck(
    'Low availability reduces Gameweek Score',
    is_numeric(
        $availableScore
    )
    &&
    is_numeric(
        $unavailableScore
    )
    &&
    $availableScore
    >
    $unavailableScore
);


/*
 * ============================================================
 * SCENARIO E
 * CONFIDENCE IMPACT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Confidence Impact<br>";
echo "============================================<br>";


$highConfidencePlayer =
    gameweekStartingXIPlayer(
        300,
        'High Confidence',
        'MID',
        80,
        80,
        80,
        100,
        100
    );


$lowConfidencePlayer =
    gameweekStartingXIPlayer(
        301,
        'Low Confidence',
        'MID',
        80,
        80,
        80,
        100,
        0
    );


$confidenceSquad =
    $baseSquad;


$confidenceSquad[
    10
] =
    $highConfidencePlayer;


$confidenceSquad[
    11
] =
    $lowConfidencePlayer;


$confidenceResult =
    $optimizer
        ->optimize(
            $confidenceSquad
        );


$confidencePlayers =
    array_merge(
        $confidenceResult[
            'starting_xi'
        ]
        ?? [],
        $confidenceResult[
            'bench'
        ]
        ?? []
    );


$highConfidenceScore =
    null;


$lowConfidenceScore =
    null;


foreach (
    $confidencePlayers
    as $player
) {

    if (
        (
            $player[
                'player_id'
            ]
            ?? null
        )
        === 300
    ) {

        $highConfidenceScore =
            $player[
                'gameweek_score'
            ]
            ?? null;
    }


    if (
        (
            $player[
                'player_id'
            ]
            ?? null
        )
        === 301
    ) {

        $lowConfidenceScore =
            $player[
                'gameweek_score'
            ]
            ?? null;
    }
}


gameweekStartingXICheck(
    'Low confidence reduces Gameweek Score',
    is_numeric(
        $highConfidenceScore
    )
    &&
    is_numeric(
        $lowConfidenceScore
    )
    &&
    $highConfidenceScore
    >
    $lowConfidenceScore
);


/*
 * ============================================================
 * SCENARIO F
 * BENCH ORDER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Bench Order<br>";
echo "============================================<br>";


$bench =
    $result[
        'bench'
    ]
    ?? [];


gameweekStartingXICheck(
    'Bench one exists',
    isset(
        $bench[
            0
        ]
    )
);


gameweekStartingXICheck(
    'Bench two exists',
    isset(
        $bench[
            1
        ]
    )
);


gameweekStartingXICheck(
    'Bench three exists',
    isset(
        $bench[
            2
        ]
    )
);


gameweekStartingXICheck(
    'Bench four exists',
    isset(
        $bench[
            3
        ]
    )
);


$benchOrderValid =
    true;


foreach (
    $bench
    as $index => $player
) {

    if (
        (
            $player[
                'bench_order'
            ]
            ?? null
        )
        !==
        (
            $index + 1
        )
    ) {

        $benchOrderValid =
            false;

        break;
    }
}


gameweekStartingXICheck(
    'Bench order is sequential from one to four',
    $benchOrderValid
);


gameweekStartingXICheck(
    'Bench one is the backup goalkeeper',
    isset(
        $bench[0]
    )
    &&
    (
        $bench[0][
            'position'
        ]
        ?? null
    )
    === 'GK'
    &&
    (
        $bench[0][
            'bench_order'
        ]
        ?? null
    )
    === 1
);

gameweekStartingXICheck(
    'Outfield substitutes occupy bench positions two to four',
    isset(
        $bench[1],
        $bench[2],
        $bench[3]
    )
    &&
    (
        $bench[1][
            'position'
        ]
        ?? null
    )
    !== 'GK'
    &&
    (
        $bench[2][
            'position'
        ]
        ?? null
    )
    !== 'GK'
    &&
    (
        $bench[3][
            'position'
        ]
        ?? null
    )
    !== 'GK'
);


/*
 * ============================================================
 * SCENARIO G
 * FORMATION STRUCTURE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Formation Structure<br>";
echo "============================================<br>";


$startingXI =
    $result[
        'starting_xi'
    ]
    ?? [];


$positionCounts = [

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
    $startingXI
    as $player
) {

    $position =
        $player[
            'position'
        ]
        ?? '';


    if (
        isset(
            $positionCounts[
                $position
            ]
        )
    ) {

        $positionCounts[
            $position
        ]++;
    }
}


gameweekStartingXICheck(
    'Starting XI contains one goalkeeper',
    $positionCounts[
        'GK'
    ]
    === 1
);


gameweekStartingXICheck(
    'Starting XI contains between three and five defenders',
    $positionCounts[
        'DEF'
    ]
    >= 3
    &&
    $positionCounts[
        'DEF'
    ]
    <= 5
);


gameweekStartingXICheck(
    'Starting XI contains between two and five midfielders',
    $positionCounts[
        'MID'
    ]
    >= 2
    &&
    $positionCounts[
        'MID'
    ]
    <= 5
);


gameweekStartingXICheck(
    'Starting XI contains between one and three forwards',
    $positionCounts[
        'FWD'
    ]
    >= 1
    &&
    $positionCounts[
        'FWD'
    ]
    <= 3
);


/*
 * ============================================================
 * SCENARIO H
 * INVALID SQUADS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Invalid Squads<br>";
echo "============================================<br>";


$shortSquad =
    array_slice(
        $baseSquad,
        0,
        14
    );


$shortResult =
    $optimizer
        ->optimize(
            $shortSquad
        );


gameweekStartingXICheck(
    'Incomplete squad is rejected',
    (
        $shortResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


$duplicateSquad =
    $baseSquad;


$duplicateSquad[
    14
] =
    $duplicateSquad[
        13
    ];


$duplicateResult =
    $optimizer
        ->optimize(
            $duplicateSquad
        );


gameweekStartingXICheck(
    'Duplicate players are rejected',
    (
        $duplicateResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


$badStructureSquad =
    $baseSquad;


$badStructureSquad[
    14
][
    'position'
] =
    'MID';


$badStructureResult =
    $optimizer
        ->optimize(
            $badStructureSquad
        );


gameweekStartingXICheck(
    'Invalid FPL squad position structure is rejected',
    (
        $badStructureResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


/*
 * ============================================================
 * SCENARIO I
 * SCORE BOUNDS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Score Bounds<br>";
echo "============================================<br>";


$scoresBounded =
    true;


foreach (
    $allPlayers
    as $player
) {

    $score =
        $player[
            'gameweek_score'
        ]
        ?? null;


    if (
        !is_numeric(
            $score
        )
        ||
        $score < 0
        ||
        $score > 100
    ) {

        $scoresBounded =
            false;

        break;
    }
}


gameweekStartingXICheck(
    'All Gameweek Scores remain between 0 and 100',
    $scoresBounded
);

/*
 * ============================================================
 * SCENARIO J
 * GAMEWEEK CALIBRATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario J: Gameweek Calibration<br>";
echo "============================================<br>";


/*
 * ------------------------------------------------------------
 * FIXTURE CALIBRATION
 * ------------------------------------------------------------
 *
 * Build three otherwise identical midfielders so we can verify:
 *
 * raw 100 -> calibrated 80
 * raw  50 -> calibrated 50
 * raw   0 -> calibrated 20
 */

$strongFixturePlayer =
    gameweekStartingXIPlayer(
        400,
        'Strong Fixture',
        'MID',
        70,
        70,
        100,
        100,
        100
    );


$neutralFixturePlayer =
    gameweekStartingXIPlayer(
        401,
        'Neutral Fixture',
        'MID',
        70,
        70,
        50,
        100,
        100
    );


$weakFixturePlayer =
    gameweekStartingXIPlayer(
        402,
        'Weak Fixture',
        'MID',
        70,
        70,
        0,
        100,
        100
    );


$calibrationSquad =
    $baseSquad;


/*
 * Replace three midfielders while preserving the legal
 * 2 GK / 5 DEF / 5 MID / 3 FWD squad structure.
 */

$calibrationSquad[
    9
] =
    $strongFixturePlayer;


$calibrationSquad[
    10
] =
    $neutralFixturePlayer;


$calibrationSquad[
    11
] =
    $weakFixturePlayer;


$calibrationResult =
    $optimizer
        ->optimize(
            $calibrationSquad
        );


$calibrationPlayers =
    array_merge(
        $calibrationResult[
            'starting_xi'
        ]
        ?? [],
        $calibrationResult[
            'bench'
        ]
        ?? []
    );


$strongFixtureComponents =
    null;


$neutralFixtureComponents =
    null;


$weakFixtureComponents =
    null;


foreach (
    $calibrationPlayers
    as $player
) {

    $playerId =
        (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );


    if (
        $playerId === 400
    ) {

        $strongFixtureComponents =
            $player[
                'gameweek_components'
            ]
            ?? null;
    }


    if (
        $playerId === 401
    ) {

        $neutralFixtureComponents =
            $player[
                'gameweek_components'
            ]
            ?? null;
    }


    if (
        $playerId === 402
    ) {

        $weakFixtureComponents =
            $player[
                'gameweek_components'
            ]
            ?? null;
    }
}


gameweekStartingXICheck(
    'Strong fixture player exposes raw fixture score',
    is_array(
        $strongFixtureComponents
    )
    &&
    array_key_exists(
        'raw_fixture',
        $strongFixtureComponents
    )
);


gameweekStartingXICheck(
    'Neutral fixture player exposes raw fixture score',
    is_array(
        $neutralFixtureComponents
    )
    &&
    array_key_exists(
        'raw_fixture',
        $neutralFixtureComponents
    )
);


gameweekStartingXICheck(
    'Weak fixture player exposes raw fixture score',
    is_array(
        $weakFixtureComponents
    )
    &&
    array_key_exists(
        'raw_fixture',
        $weakFixtureComponents
    )
);


gameweekStartingXICheck(
    'Raw 100 fixture is preserved',
    (
        (float) (
            $strongFixtureComponents[
                'raw_fixture'
            ]
            ?? -1
        )
    )
    === 100.0
);


gameweekStartingXICheck(
    'Raw 100 fixture is calibrated to 80',
    (
        (float) (
            $strongFixtureComponents[
                'fixture'
            ]
            ?? -1
        )
    )
    === 80.0
);


gameweekStartingXICheck(
    'Raw 50 fixture remains neutral at 50',
    (
        (float) (
            $neutralFixtureComponents[
                'fixture'
            ]
            ?? -1
        )
    )
    === 50.0
);


gameweekStartingXICheck(
    'Raw 0 fixture is preserved',
    (
        (float) (
            $weakFixtureComponents[
                'raw_fixture'
            ]
            ?? -1
        )
    )
    === 0.0
);


gameweekStartingXICheck(
    'Raw 0 fixture is calibrated to 20',
    (
        (float) (
            $weakFixtureComponents[
                'fixture'
            ]
            ?? -1
        )
    )
    === 20.0
);


gameweekStartingXICheck(
    'Strong fixture is not amplified beyond raw rating',
    (
        (float) (
            $strongFixtureComponents[
                'fixture'
            ]
            ?? 101
        )
    )
    <=
    (
        (float) (
            $strongFixtureComponents[
                'raw_fixture'
            ]
            ?? 100
        )
    )
);


gameweekStartingXICheck(
    'Weak fixture is softened rather than made harsher',
    (
        (float) (
            $weakFixtureComponents[
                'fixture'
            ]
            ?? -1
        )
    )
    >=
    (
        (float) (
            $weakFixtureComponents[
                'raw_fixture'
            ]
            ?? 0
        )
    )
);


/*
 * ------------------------------------------------------------
 * CONFIDENCE CALIBRATION
 * ------------------------------------------------------------
 */

$fullConfidencePlayer =
    gameweekStartingXIPlayer(
        410,
        'Full Confidence Calibration',
        'MID',
        70,
        70,
        50,
        100,
        100
    );


$halfConfidencePlayer =
    gameweekStartingXIPlayer(
        411,
        'Half Confidence Calibration',
        'MID',
        70,
        70,
        50,
        100,
        50
    );


$veryLowConfidencePlayer =
    gameweekStartingXIPlayer(
        412,
        'Low Confidence Calibration',
        'MID',
        70,
        70,
        50,
        100,
        10
    );


$confidenceCalibrationSquad =
    $baseSquad;


$confidenceCalibrationSquad[
    9
] =
    $fullConfidencePlayer;


$confidenceCalibrationSquad[
    10
] =
    $halfConfidencePlayer;


$confidenceCalibrationSquad[
    11
] =
    $veryLowConfidencePlayer;


$confidenceCalibrationResult =
    $optimizer
        ->optimize(
            $confidenceCalibrationSquad
        );


$confidenceCalibrationPlayers =
    array_merge(
        $confidenceCalibrationResult[
            'starting_xi'
        ]
        ?? [],
        $confidenceCalibrationResult[
            'bench'
        ]
        ?? []
    );


$fullConfidenceModifier =
    null;


$halfConfidenceModifier =
    null;


$veryLowConfidenceModifier =
    null;


foreach (
    $confidenceCalibrationPlayers
    as $player
) {

    $playerId =
        (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );


    $modifier =
        $player[
            'gameweek_components'
        ][
            'confidence_modifier'
        ]
        ?? null;


    if (
        $playerId === 410
    ) {

        $fullConfidenceModifier =
            $modifier;
    }


    if (
        $playerId === 411
    ) {

        $halfConfidenceModifier =
            $modifier;
    }


    if (
        $playerId === 412
    ) {

        $veryLowConfidenceModifier =
            $modifier;
    }
}


gameweekStartingXICheck(
    'Full confidence produces neutral modifier',
    is_numeric(
        $fullConfidenceModifier
    )
    &&
    (
        (float) $fullConfidenceModifier
    )
    === 1.0
);


gameweekStartingXICheck(
    '50 percent confidence produces calibrated modifier near 0.90',
    is_numeric(
        $halfConfidenceModifier
    )
    &&
    abs(
        (
            (float) $halfConfidenceModifier
        )
        -
        0.90
    )
    < 0.001
);


gameweekStartingXICheck(
    'Very low confidence produces materially reduced modifier',
    is_numeric(
        $veryLowConfidenceModifier
    )
    &&
    (
        (float) $veryLowConfidenceModifier
    )
    < 0.80
);


gameweekStartingXICheck(
    'Confidence modifier improves as sample confidence increases',
    is_numeric(
        $fullConfidenceModifier
    )
    &&
    is_numeric(
        $halfConfidenceModifier
    )
    &&
    is_numeric(
        $veryLowConfidenceModifier
    )
    &&
    $fullConfidenceModifier
        >
        $halfConfidenceModifier
    &&
    $halfConfidenceModifier
        >
        $veryLowConfidenceModifier
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Gameweek Starting XI Test Summary<br>";
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