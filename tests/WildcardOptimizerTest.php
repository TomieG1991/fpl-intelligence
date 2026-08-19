<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Wildcard Optimizer Test<br>";
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

function wildcardTest(
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
 * PLAYER FACTORY
 * ============================================================
 */

function wildcardPlayer(
    int $playerId,
    string $name,
    int $teamId,
    string $position,
    float $price,
    float $intelligence,
    float $strength = 60.0,
    float $value = 60.0,
    float $fixtures = 60.0,
    float $availability = 100.0,
    float $confidence = 1.0
): array {

    return [

        'player_id' =>
            $playerId,

        'name' =>
            $name,

        'team_id' =>
            $teamId,

        'team_name' =>
            'Team '
            . $teamId,

        'position' =>
            $position,

        'price' =>
            $price,

        'intelligence_score' =>
            $intelligence,

        'strength_rating' =>
            $strength,

        'value_rating' =>
            $value,

        'fixture_rating' =>
            $fixtures,

        'availability_rating' =>
            $availability,

        'sample_confidence' =>
            $confidence
    ];
}


/*
 * ============================================================
 * SYNTHETIC PLAYER POOL
 * ============================================================
 */

$players = [

    /*
     * Goalkeepers
     */

    wildcardPlayer(
        1,
        'Goalkeeper A',
        1,
        'GK',
        5.0,
        72.0,
        72.0,
        68.0,
        70.0
    ),

    wildcardPlayer(
        2,
        'Goalkeeper B',
        2,
        'GK',
        4.5,
        65.0,
        65.0,
        72.0,
        65.0
    ),

    wildcardPlayer(
        3,
        'Goalkeeper C',
        3,
        'GK',
        4.0,
        55.0,
        55.0,
        80.0,
        58.0
    ),


    /*
     * Defenders
     */

    wildcardPlayer(
        10,
        'Defender A',
        1,
        'DEF',
        7.0,
        78.0,
        78.0,
        55.0,
        75.0
    ),

    wildcardPlayer(
        11,
        'Defender B',
        4,
        'DEF',
        6.0,
        74.0,
        74.0,
        60.0,
        72.0
    ),

    wildcardPlayer(
        12,
        'Defender C',
        5,
        'DEF',
        5.5,
        70.0,
        70.0,
        65.0,
        68.0
    ),

    wildcardPlayer(
        13,
        'Defender D',
        6,
        'DEF',
        5.0,
        66.0,
        66.0,
        70.0,
        66.0
    ),

    wildcardPlayer(
        14,
        'Defender E',
        7,
        'DEF',
        4.5,
        62.0,
        62.0,
        76.0,
        64.0
    ),

    wildcardPlayer(
        15,
        'Defender F',
        8,
        'DEF',
        4.0,
        58.0,
        58.0,
        82.0,
        60.0
    ),

    wildcardPlayer(
        16,
        'Defender G',
        1,
        'DEF',
        4.0,
        57.0,
        57.0,
        84.0,
        60.0
    ),


    /*
     * Midfielders
     */

    wildcardPlayer(
        20,
        'Midfielder A',
        2,
        'MID',
        12.0,
        82.0,
        82.0,
        45.0,
        78.0
    ),

    wildcardPlayer(
        21,
        'Midfielder B',
        3,
        'MID',
        9.0,
        77.0,
        77.0,
        58.0,
        74.0
    ),

    wildcardPlayer(
        22,
        'Midfielder C',
        4,
        'MID',
        7.5,
        73.0,
        73.0,
        65.0,
        72.0
    ),

    wildcardPlayer(
        23,
        'Midfielder D',
        5,
        'MID',
        6.5,
        68.0,
        68.0,
        72.0,
        68.0
    ),

    wildcardPlayer(
        24,
        'Midfielder E',
        6,
        'MID',
        5.5,
        64.0,
        64.0,
        78.0,
        66.0
    ),

    wildcardPlayer(
        25,
        'Midfielder F',
        7,
        'MID',
        5.0,
        60.0,
        60.0,
        84.0,
        62.0
    ),

    wildcardPlayer(
        26,
        'Midfielder G',
        2,
        'MID',
        4.5,
        55.0,
        55.0,
        88.0,
        60.0
    ),


    /*
     * Forwards
     */

    wildcardPlayer(
        30,
        'Forward A',
        8,
        'FWD',
        14.0,
        84.0,
        84.0,
        40.0,
        76.0
    ),

    wildcardPlayer(
        31,
        'Forward B',
        9,
        'FWD',
        9.0,
        76.0,
        76.0,
        58.0,
        72.0
    ),

    wildcardPlayer(
        32,
        'Forward C',
        10,
        'FWD',
        7.0,
        70.0,
        70.0,
        66.0,
        68.0
    ),

    wildcardPlayer(
        33,
        'Forward D',
        1,
        'FWD',
        5.5,
        63.0,
        63.0,
        76.0,
        64.0
    ),

    wildcardPlayer(
        34,
        'Forward E',
        11,
        'FWD',
        4.5,
        57.0,
        57.0,
        84.0,
        60.0
    )
];


$optimizer =
    new WildcardOptimizer();


/*
 * ============================================================
 * SCENARIO A
 * VALID WILDCARD SQUAD
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Valid Wildcard Squad<br>";
echo "============================================<br>";


$result =
    $optimizer
        ->optimize(
            $players,
            100.0
        );
        

wildcardTest(
    'Optimizer returns an array',
    is_array(
        $result
    )
);


wildcardTest(
    'Optimizer returns success',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


wildcardTest(
    'Generated squad contains 15 players',
    count(
        $result[
            'squad'
        ]
        ?? []
    )
    === 15
);


wildcardTest(
    'Generated squad is valid',
    (
        $result[
            'validation'
        ]['is_valid']
        ?? false
    )
    === true
);


wildcardTest(
    'Squad cost does not exceed £100m',
    (
        (float) (
            $result[
                'cost'
            ]
            ?? 999
        )
    )
    <= 100.0
);


wildcardTest(
    'Remaining bank is not negative',
    (
        (float) (
            $result[
                'bank'
            ]
            ?? -1
        )
    )
    >= 0
);


wildcardTest(
    'Wildcard score is returned',
    is_numeric(
        $result[
            'wildcard_score'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * POSITION STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Position Structure<br>";
echo "============================================<br>";


$positionCounts =
    $result[
        'validation'
    ]['position_counts']
    ?? [];


wildcardTest(
    'Squad contains two goalkeepers',
    (
        $positionCounts[
            'GK'
        ]
        ?? 0
    )
    === 2
);


wildcardTest(
    'Squad contains five defenders',
    (
        $positionCounts[
            'DEF'
        ]
        ?? 0
    )
    === 5
);


wildcardTest(
    'Squad contains five midfielders',
    (
        $positionCounts[
            'MID'
        ]
        ?? 0
    )
    === 5
);


wildcardTest(
    'Squad contains three forwards',
    (
        $positionCounts[
            'FWD'
        ]
        ?? 0
    )
    === 3
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * CLUB LIMIT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Club Limit<br>";
echo "============================================<br>";


$teamCounts =
    $result[
        'validation'
    ]['team_counts']
    ?? [];


$clubLimitRespected =
    true;


foreach (
    $teamCounts
    as $count
) {

    if (
        $count > 3
    ) {

        $clubLimitRespected =
            false;

        break;
    }
}


wildcardTest(
    'No club contributes more than three players',
    $clubLimitRespected
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * DUPLICATES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Duplicate Protection<br>";
echo "============================================<br>";


$selectedIds =
    array_map(
        static function (
            array $player
        ): int {

            return (int) (
                $player[
                    'player_id'
                ]
                ?? 0
            );
        },
        $result[
            'squad'
        ]
        ?? []
    );


wildcardTest(
    'Generated squad contains no duplicate players',
    count(
        $selectedIds
    )
    ===
    count(
        array_unique(
            $selectedIds
        )
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * VALIDATION OF ILLEGAL SQUAD
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Invalid Squad Validation<br>";
echo "============================================<br>";


$illegalSquad =
    $result[
        'squad'
    ]
    ?? [];


if (
    isset(
        $illegalSquad[14]
    )
) {

    $illegalSquad[14] =
        $illegalSquad[0];
}


$illegalValidation =
    $optimizer
        ->validateSquad(
            $illegalSquad,
            100.0
        );


wildcardTest(
    'Duplicate player makes squad invalid',
    (
        $illegalValidation[
            'is_valid'
        ]
        ?? true
    )
    === false
);


$duplicateIssueFound =
    false;


foreach (
    $illegalValidation[
        'issues'
    ]
    ?? []
    as $issue
) {

    if (
        stripos(
            $issue,
            'Duplicate player'
        )
        !== false
    ) {

        $duplicateIssueFound =
            true;

        break;
    }
}


wildcardTest(
    'Duplicate-player validation issue is reported',
    $duplicateIssueFound
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * OVER-BUDGET VALIDATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Over-Budget Validation<br>";
echo "============================================<br>";


$overBudgetSquad =
    $result[
        'squad'
    ]
    ?? [];


foreach (
    $overBudgetSquad
    as &$player
) {

    $player[
        'price'
    ] =
        10.0;
}


unset(
    $player
);


$overBudgetValidation =
    $optimizer
        ->validateSquad(
            $overBudgetSquad,
            100.0
        );


wildcardTest(
    'Over-budget squad is invalid',
    (
        $overBudgetValidation[
            'is_valid'
        ]
        ?? true
    )
    === false
);


wildcardTest(
    'Over-budget squad reports cost above £100m',
    (
        (float) (
            $overBudgetValidation[
                'cost'
            ]
            ?? 0
        )
    )
    > 100.0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * EMPTY INPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Empty Input<br>";
echo "============================================<br>";


$emptyResult =
    $optimizer
        ->optimize(
            [],
            100.0
        );


wildcardTest(
    'Empty player pool is rejected',
    (
        $emptyResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


wildcardTest(
    'Empty player pool returns no squad',
    empty(
        $emptyResult[
            'squad'
        ]
        ?? []
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * INVALID BUDGET
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Invalid Budget<br>";
echo "============================================<br>";


$invalidBudgetResult =
    $optimizer
        ->optimize(
            $players,
            0.0
        );


wildcardTest(
    'Zero budget is rejected',
    (
        $invalidBudgetResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * INSUFFICIENT POSITION POOL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Insufficient Position Pool<br>";
echo "============================================<br>";


$insufficientPlayers =
    array_values(
        array_filter(
            $players,
            static function (
                array $player
            ): bool {

                return (
                    $player[
                        'position'
                    ]
                    ?? null
                )
                !==
                'GK'
                ||
                (
                    $player[
                        'player_id'
                    ]
                    ?? 0
                )
                === 1;
            }
        )
    );


$insufficientResult =
    $optimizer
        ->optimize(
            $insufficientPlayers,
            100.0
        );


wildcardTest(
    'Insufficient goalkeeper pool is rejected',
    (
        $insufficientResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


wildcardTest(
    'Insufficient goalkeeper result returns no squad',
    empty(
        $insufficientResult[
            'squad'
        ]
        ?? []
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * CONFIDENCE NORMALISATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Confidence Normalisation<br>";
echo "============================================<br>";


$confidencePlayers =
    $players;


$confidencePlayers[0][
    'sample_confidence'
] =
    0.75;


$confidenceResult =
    $optimizer
        ->optimize(
            $confidencePlayers,
            100.0
        );


$confidencePlayer =
    null;


foreach (
    $confidenceResult[
        'squad'
    ]
    ?? []
    as $selectedPlayer
) {

    if (
        (
            $selectedPlayer[
                'player_id'
            ]
            ?? null
        )
        ===
        1
    ) {

        $confidencePlayer =
            $selectedPlayer;

        break;
    }
}


wildcardTest(
    '0-1 confidence is normalised to percentage',
    $confidencePlayer !== null
    &&
    (
        (float) (
            $confidencePlayer[
                'sample_confidence'
            ]
            ?? 0
        )
    )
    === 75.0
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Wildcard Optimizer Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}