<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Decision Engine Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function gameweekDecisionTest(
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


function buildDecisionPlayer(
    int $playerId,
    string $name,
    string $position,
    float $gameweekScore,
    float $confidence = 100.0,
    float $availability = 100.0
): array {

    return [

        'player_id' =>
            $playerId,

        'name' =>
            $name,

        'position' =>
            $position,

        'gameweek_score' =>
            $gameweekScore,

        'sample_confidence' =>
            $confidence,

        'availability_rating' =>
            $availability,

        'components' => [

            'confidence' =>
                $confidence,

            'availability' =>
                $availability
        ]
    ];
}


$engine =
    new GameweekDecisionEngine();


/*
 * ============================================================
 * BASE VALID GAMEWEEK RESULT
 * ============================================================
 */

$startingXI = [

    buildDecisionPlayer(
        1,
        'Goalkeeper A',
        'GK',
        60.0
    ),

    buildDecisionPlayer(
        2,
        'Defender A',
        'DEF',
        58.0
    ),

    buildDecisionPlayer(
        3,
        'Defender B',
        'DEF',
        57.0
    ),

    buildDecisionPlayer(
        4,
        'Defender C',
        'DEF',
        56.0
    ),

    buildDecisionPlayer(
        5,
        'Defender D',
        'DEF',
        55.0
    ),

    buildDecisionPlayer(
        6,
        'Midfielder A',
        'MID',
        75.0
    ),

    buildDecisionPlayer(
        7,
        'Midfielder B',
        'MID',
        70.0
    ),

    buildDecisionPlayer(
        8,
        'Midfielder C',
        'MID',
        65.0
    ),

    buildDecisionPlayer(
        9,
        'Forward A',
        'FWD',
        78.0
    ),

    buildDecisionPlayer(
        10,
        'Forward B',
        'FWD',
        68.0
    ),

    buildDecisionPlayer(
        11,
        'Forward C',
        'FWD',
        62.0
    )
];


$bench = [

    buildDecisionPlayer(
        12,
        'Bench Midfielder',
        'MID',
        45.0
    ),

    buildDecisionPlayer(
        13,
        'Bench Defender A',
        'DEF',
        42.0
    ),

    buildDecisionPlayer(
        14,
        'Bench Defender B',
        'DEF',
        40.0
    ),

    buildDecisionPlayer(
        15,
        'Backup Goalkeeper',
        'GK',
        38.0
    )
];


$gameweekResult = [

    'status' =>
        'success',

    'message' =>
        'Gameweek Starting XI generated successfully.',

    'formation' =>
        '4-3-3',

    'starting_xi_score' =>
        64.00,

    'bench_score' =>
        41.25,

    'starting_xi' =>
        $startingXI,

    'bench' =>
        $bench,

    'formations' =>
        []
];


$captainResult = [

    'status' =>
        'success',

    'message' =>
        'Captain recommendations generated successfully.',

    'captain' => [

        'player_id' =>
            9,

        'name' =>
            'Forward A',

        'position' =>
            'FWD',

        'captain_score' =>
            72.50,

        'classification' =>
            'Elite Captain'
    ],

    'vice_captain' => [

        'player_id' =>
            6,

        'name' =>
            'Midfielder A',

        'position' =>
            'MID',

        'captain_score' =>
            68.00,

        'classification' =>
            'Elite Captain'
    ],

    'alternatives' =>
        [],

    'rankings' =>
        []
];


$lowTransferResult = [

    'status' =>
        'success',

    'priority' =>
        'Low',

    'score' =>
        40.0,

    'recommendations' => [

        [
            'player_id' =>
                20,

            'name' =>
                'Possible Replacement'
        ]
    ]
];


$mediumTransferResult = [

    'status' =>
        'success',

    'priority' =>
        'Medium',

    'score' =>
        60.0,

    'recommendations' =>
        []
];


$highTransferResult = [

    'status' =>
        'success',

    'priority' =>
        'High',

    'score' =>
        80.0,

    'recommendations' =>
        []
];


/*
 * ============================================================
 * SCENARIO A: VALID DECISION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Valid Gameweek Decision<br>";
echo "============================================<br>";


$result =
    $engine->evaluate(
        $gameweekResult,
        $captainResult,
        $lowTransferResult
    );


gameweekDecisionTest(
    'Gameweek Decision Engine returns an array',
    is_array(
        $result
    )
);


gameweekDecisionTest(
    'Valid decision returns success',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


gameweekDecisionTest(
    'Decision message is returned',
    !empty(
        $result[
            'message'
        ]
        ?? null
    )
);


gameweekDecisionTest(
    'Overall action is returned',
    !empty(
        $result[
            'overall_action'
        ]
        ?? null
    )
);


gameweekDecisionTest(
    'Low-risk squad recommends Hold',
    (
        $result[
            'overall_action'
        ]
        ?? null
    )
    ===
    'Hold'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B: GAMEWEEK INTELLIGENCE PRESERVATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Gameweek Intelligence<br>";
echo "============================================<br>";


gameweekDecisionTest(
    'Recommended formation is preserved',
    (
        $result[
            'formation'
        ]
        ?? null
    )
    ===
    '4-3-3'
);


gameweekDecisionTest(
    'Starting XI Score is preserved',
    (
        $result[
            'starting_xi_score'
        ]
        ?? null
    )
    ===
    64.00
);


gameweekDecisionTest(
    'Bench Score is preserved',
    (
        $result[
            'bench_score'
        ]
        ?? null
    )
    ===
    41.25
);


gameweekDecisionTest(
    'Starting XI contains eleven players',
    count(
        $result[
            'starting_xi'
        ]
        ?? []
    )
    ===
    11
);


gameweekDecisionTest(
    'Bench contains four players',
    count(
        $result[
            'bench'
        ]
        ?? []
    )
    ===
    4
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C: CAPTAIN INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Captain Intelligence<br>";
echo "============================================<br>";


gameweekDecisionTest(
    'Recommended captain is preserved',
    (
        $result[
            'captain'
        ][
            'player_id'
        ]
        ?? null
    )
    ===
    9
);


gameweekDecisionTest(
    'Recommended vice-captain is preserved',
    (
        $result[
            'vice_captain'
        ][
            'player_id'
        ]
        ?? null
    )
    ===
    6
);


gameweekDecisionTest(
    'Captain Score is preserved',
    (
        $result[
            'captain'
        ][
            'captain_score'
        ]
        ?? null
    )
    ===
    72.50
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D: CLEAN SQUAD RISK ANALYSIS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Clean Squad Risk Analysis<br>";
echo "============================================<br>";


gameweekDecisionTest(
    'Squad risk analysis is returned',
    isset(
        $result[
            'squad_risks'
        ]
    )
);


gameweekDecisionTest(
    'Fully available squad has no detected risks',
    (
        $result[
            'squad_risks'
        ][
            'count'
        ]
        ?? null
    )
    ===
    0
);


gameweekDecisionTest(
    'Clean squad has no critical risk',
    (
        $result[
            'squad_risks'
        ][
            'has_critical_risk'
        ]
        ?? true
    )
    ===
    false
);


gameweekDecisionTest(
    'Clean squad has no high risk',
    (
        $result[
            'squad_risks'
        ][
            'has_high_risk'
        ]
        ?? true
    )
    ===
    false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E: AVAILABILITY RISK
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Availability Risk<br>";
echo "============================================<br>";


$riskStartingXI =
    $startingXI;


$riskStartingXI[5][
    'availability_rating'
] =
    0.0;


$riskStartingXI[5][
    'components'
][
    'availability'
] =
    0.0;


$riskGameweekResult =
    $gameweekResult;


$riskGameweekResult[
    'starting_xi'
] =
    $riskStartingXI;


$riskResult =
    $engine->evaluate(
        $riskGameweekResult,
        $captainResult,
        $lowTransferResult
    );


gameweekDecisionTest(
    'Unavailable starter creates squad risk',
    (
        $riskResult[
            'squad_risks'
        ][
            'count'
        ]
        ?? 0
    )
    > 0
);


gameweekDecisionTest(
    'Unavailable starter creates critical risk',
    (
        $riskResult[
            'squad_risks'
        ][
            'has_critical_risk'
        ]
        ?? false
    )
    ===
    true
);


gameweekDecisionTest(
    'Critical starter availability produces Urgent Action',
    (
        $riskResult[
            'overall_action'
        ]
        ?? null
    )
    ===
    'Urgent Action'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F: CONFIDENCE RISK
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Confidence Risk<br>";
echo "============================================<br>";


$confidenceStartingXI =
    $startingXI;


$confidenceStartingXI[6][
    'sample_confidence'
] =
    15.0;


$confidenceStartingXI[6][
    'components'
][
    'confidence'
] =
    15.0;


$confidenceGameweekResult =
    $gameweekResult;


$confidenceGameweekResult[
    'starting_xi'
] =
    $confidenceStartingXI;


$confidenceResult =
    $engine->evaluate(
        $confidenceGameweekResult,
        $captainResult,
        $lowTransferResult
    );


gameweekDecisionTest(
    'Very low-confidence starter creates squad risk',
    (
        $confidenceResult[
            'squad_risks'
        ][
            'count'
        ]
        ?? 0
    )
    > 0
);


gameweekDecisionTest(
    'Very low-confidence starter creates high risk',
    (
        $confidenceResult[
            'squad_risks'
        ][
            'has_high_risk'
        ]
        ?? false
    )
    ===
    true
);


gameweekDecisionTest(
    'High confidence risk produces Consider Transfer',
    (
        $confidenceResult[
            'overall_action'
        ]
        ?? null
    )
    ===
    'Consider Transfer'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G: TRANSFER ADVICE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Transfer Advice<br>";
echo "============================================<br>";


$mediumTransferDecision =
    $engine->evaluate(
        $gameweekResult,
        $captainResult,
        $mediumTransferResult
    );


$highTransferDecision =
    $engine->evaluate(
        $gameweekResult,
        $captainResult,
        $highTransferResult
    );


gameweekDecisionTest(
    'Transfer advice is returned',
    isset(
        $result[
            'transfer_advice'
        ]
    )
);


gameweekDecisionTest(
    'Low transfer priority recommends Hold',
    (
        $result[
            'transfer_advice'
        ][
            'action'
        ]
        ?? null
    )
    ===
    'Hold'
);


gameweekDecisionTest(
    'Medium transfer priority recommends consideration',
    (
        $mediumTransferDecision[
            'overall_action'
        ]
        ?? null
    )
    ===
    'Consider Transfer'
);


gameweekDecisionTest(
    'High transfer priority recommends transfer',
    (
        $highTransferDecision[
            'overall_action'
        ]
        ?? null
    )
    ===
    'Make Transfer'
);


gameweekDecisionTest(
    'Transfer recommendations are preserved',
    count(
        $result[
            'transfer_advice'
        ][
            'recommendations'
        ]
        ?? []
    )
    ===
    1
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H: NUMERIC TRANSFER PRIORITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Numeric Transfer Priority<br>";
echo "============================================<br>";


$numericTransferResult = [

    'status' =>
        'success',

    'score' =>
        75.0,

    'recommendations' =>
        []
];


$numericDecision =
    $engine->evaluate(
        $gameweekResult,
        $captainResult,
        $numericTransferResult
    );


gameweekDecisionTest(
    'High numeric transfer score produces high priority',
    (
        $numericDecision[
            'transfer_advice'
        ][
            'priority'
        ]
        ?? null
    )
    ===
    'High'
);


gameweekDecisionTest(
    'High numeric transfer score recommends transfer',
    (
        $numericDecision[
            'overall_action'
        ]
        ?? null
    )
    ===
    'Make Transfer'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I: NO TRANSFER DATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: No Transfer Data<br>";
echo "============================================<br>";


$noTransferDecision =
    $engine->evaluate(
        $gameweekResult,
        $captainResult
    );


gameweekDecisionTest(
    'Decision succeeds without transfer data',
    (
        $noTransferDecision[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


gameweekDecisionTest(
    'Missing transfer data is reported',
    (
        $noTransferDecision[
            'transfer_advice'
        ][
            'action'
        ]
        ?? null
    )
    ===
    'No Transfer Data'
);


gameweekDecisionTest(
    'Healthy squad can still recommend Hold without transfer data',
    (
        $noTransferDecision[
            'overall_action'
        ]
        ?? null
    )
    ===
    'Hold'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J: KEY INSIGHTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Key Insights<br>";
echo "============================================<br>";


gameweekDecisionTest(
    'Key insights are returned',
    isset(
        $result[
            'key_insights'
        ]
    )
    &&
    is_array(
        $result[
            'key_insights'
        ]
    )
);


gameweekDecisionTest(
    'Gameweek decision returns multiple insights',
    count(
        $result[
            'key_insights'
        ]
        ?? []
    )
    >= 4
);


$combinedInsights =
    implode(
        ' ',
        $result[
            'key_insights'
        ]
        ?? []
    );


gameweekDecisionTest(
    'Insights mention recommended formation',
    str_contains(
        $combinedInsights,
        '4-3-3'
    )
);


gameweekDecisionTest(
    'Insights mention recommended captain',
    str_contains(
        $combinedInsights,
        'Forward A'
    )
);


gameweekDecisionTest(
    'Insights contain overall recommendation',
    str_contains(
        $combinedInsights,
        'Overall gameweek recommendation'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K: PERCENTAGE NORMALISATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Percentage Normalisation<br>";
echo "============================================<br>";


$normalisedStartingXI =
    $startingXI;


$normalisedStartingXI[0][
    'sample_confidence'
] =
    0.90;


$normalisedStartingXI[0][
    'components'
][
    'confidence'
] =
    0.90;


$normalisedGameweekResult =
    $gameweekResult;


$normalisedGameweekResult[
    'starting_xi'
] =
    $normalisedStartingXI;


$normalisedResult =
    $engine->evaluate(
        $normalisedGameweekResult,
        $captainResult,
        $lowTransferResult
    );


$normalisedPlayerRiskFound =
    false;


foreach (
    $normalisedResult[
        'squad_risks'
    ][
        'risks'
    ]
    ?? []
    as $risk
) {

    if (
        (
            $risk[
                'player_id'
            ]
            ?? null
        )
        ===
        1
        &&
        (
            $risk[
                'type'
            ]
            ?? null
        )
        ===
        'confidence'
    ) {

        $normalisedPlayerRiskFound =
            true;
    }
}


gameweekDecisionTest(
    '0-1 confidence is normalised before risk analysis',
    $normalisedPlayerRiskFound
    ===
    false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L: INVALID GAMEWEEK INPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Invalid Gameweek Input<br>";
echo "============================================<br>";


$invalidGameweek =
    $gameweekResult;


$invalidGameweek[
    'status'
] =
    'invalid';


$invalidGameweekResult =
    $engine->evaluate(
        $invalidGameweek,
        $captainResult,
        $lowTransferResult
    );


gameweekDecisionTest(
    'Invalid Gameweek Intelligence is rejected',
    (
        $invalidGameweekResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


gameweekDecisionTest(
    'Invalid Gameweek result has no overall action',
    array_key_exists(
        'overall_action',
        $invalidGameweekResult
    )
    &&
    $invalidGameweekResult[
        'overall_action'
    ]
    ===
    null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO M: INCOMPLETE STARTING XI
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario M: Incomplete Gameweek Structure<br>";
echo "============================================<br>";


$incompleteGameweek =
    $gameweekResult;


$incompleteGameweek[
    'starting_xi'
] =
    array_slice(
        $startingXI,
        0,
        10
    );


$incompleteResult =
    $engine->evaluate(
        $incompleteGameweek,
        $captainResult,
        $lowTransferResult
    );


gameweekDecisionTest(
    'Incomplete Starting XI is rejected',
    (
        $incompleteResult[
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
 * SCENARIO N: INVALID CAPTAIN INPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario N: Invalid Captain Intelligence<br>";
echo "============================================<br>";


$invalidCaptain =
    $captainResult;


$invalidCaptain[
    'status'
] =
    'invalid';


$invalidCaptainResult =
    $engine->evaluate(
        $gameweekResult,
        $invalidCaptain,
        $lowTransferResult
    );


gameweekDecisionTest(
    'Invalid Captain Intelligence is rejected',
    (
        $invalidCaptainResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


gameweekDecisionTest(
    'Invalid Captain result has no captain',
    array_key_exists(
        'captain',
        $invalidCaptainResult
    )
    &&
    $invalidCaptainResult[
        'captain'
    ]
    ===
    null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO O: RESULT STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario O: Result Structure<br>";
echo "============================================<br>";


$requiredKeys = [

    'status',
    'message',
    'overall_action',
    'formation',
    'starting_xi_score',
    'bench_score',
    'starting_xi',
    'bench',
    'captain',
    'vice_captain',
    'transfer_advice',
    'squad_risks',
    'key_insights'
];


foreach (
    $requiredKeys
    as $requiredKey
) {

    gameweekDecisionTest(
        'Decision result contains field: '
        . $requiredKey,
        array_key_exists(
            $requiredKey,
            $result
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Gameweek Decision Engine Test Summary<br>";
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

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}