<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Wildcard Optimizer Regression Test<br>";
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

function wildcardRegressionCheck(
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
 * LOAD REAL PLAYER DATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Real Player Pool<br>";
echo "============================================<br>";


try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $playerRepository =
        new PlayerRepository(
            $db
        );


    $intelligenceService =
        new PlayerIntelligenceService(
            $db
        );


    $allPlayers =
        $playerRepository
            ->getAll();

} catch (
    Throwable $exception
) {

    echo "SETUP FAILED ❌<br>";


    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );


    exit;
}


wildcardRegressionCheck(
    'Real player repository returns players',
    !empty(
        $allPlayers
    )
);


/*
 * ============================================================
 * BUILD WILDCARD PLAYER POOL
 * ============================================================
 */

$playerPoolStartedAt =
    microtime(
        true
    );


try {

    $allSummaries =
        $intelligenceService
            ->getAllPlayerSummaries();

} catch (
    Throwable $exception
) {

    echo "PLAYER POOL FAILED ❌<br>";


    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );


    exit;
}


$playerPool =
    [];


/*
 * The optimizer will perform its own final normalisation.
 *
 * Here we only reject summaries that clearly cannot participate
 * in wildcard optimisation.
 */

foreach (
    $allSummaries
    as $summary
) {

    $playerId =
        (int) (
            $summary[
                'player_id'
            ]
            ?? 0
        );


    $teamId =
        (int) (
            $summary[
                'team_id'
            ]
            ?? 0
        );


    $position =
        strtoupper(
            trim(
                (string) (
                    $summary[
                        'position'
                    ]
                    ?? ''
                )
            )
        );


    $price =
        $summary[
            'price'
        ]
        ?? null;


    $intelligence =
        $summary[
            'intelligence_score'
        ]
        ?? null;


    if (
        $playerId <= 0
        ||
        $teamId <= 0
        ||
        !in_array(
            $position,
            [
                'GK',
                'DEF',
                'MID',
                'FWD'
            ],
            true
        )
        ||
        !is_numeric(
            $price
        )
        ||
        (float) $price <= 0
        ||
        !is_numeric(
            $intelligence
        )
    ) {

        continue;
    }


    $playerPool[] =
        $summary;
}


$playerPoolRuntime =
    microtime(
        true
    )
    -
    $playerPoolStartedAt;


wildcardRegressionCheck(
    'Real player pool contains wildcard candidates',
    count(
        $playerPool
    )
    >= 15
);


echo "All Player Summaries: "
    . count(
        $allSummaries
    )
    . "<br>";


echo "Player Pool Runtime: "
    . number_format(
        $playerPoolRuntime,
        4
    )
    . " seconds<br>";


echo "Valid Wildcard Candidates: "
    . count(
        $playerPool
    )
    . "<br><br>";


/*
 * ============================================================
 * RUN OPTIMIZER
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Wildcard Optimization<br>";
echo "============================================<br>";


$optimizer =
    new WildcardOptimizer();


$startedAt =
    microtime(
        true
    );


$result =
    $optimizer
        ->optimize(
            $playerPool,
            100.0
        );


$runtime =
    microtime(
        true
    )
    -
    $startedAt;


wildcardRegressionCheck(
    'Wildcard optimizer returns an array',
    is_array(
        $result
    )
);


wildcardRegressionCheck(
    'Wildcard optimizer returns success',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


echo "Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br>";


echo "Status: "
    . htmlspecialchars(
        (string) (
            $result[
                'status'
            ]
            ?? 'unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Message: "
    . htmlspecialchars(
        (string) (
            $result[
                'message'
            ]
            ?? ''
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * COMPLETE SQUAD
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Complete Squad<br>";
echo "============================================<br>";


$squad =
    $result[
        'squad'
    ]
    ?? [];


$validation =
    $result[
        'validation'
    ]
    ?? [];


wildcardRegressionCheck(
    'Generated squad contains exactly 15 players',
    count(
        $squad
    )
    === 15
);


wildcardRegressionCheck(
    'Generated squad passes optimizer validation',
    (
        $validation[
            'is_valid'
        ]
        ?? false
    )
    === true
);


wildcardRegressionCheck(
    'Squad contains two goalkeepers',
    (
        $validation[
            'position_counts'
        ]['GK']
        ?? 0
    )
    === 2
);


wildcardRegressionCheck(
    'Squad contains five defenders',
    (
        $validation[
            'position_counts'
        ]['DEF']
        ?? 0
    )
    === 5
);


wildcardRegressionCheck(
    'Squad contains five midfielders',
    (
        $validation[
            'position_counts'
        ]['MID']
        ?? 0
    )
    === 5
);


wildcardRegressionCheck(
    'Squad contains three forwards',
    (
        $validation[
            'position_counts'
        ]['FWD']
        ?? 0
    )
    === 3
);


echo "<br>";


/*
 * ============================================================
 * BUDGET
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Budget<br>";
echo "============================================<br>";


$cost =
    $result[
        'cost'
    ]
    ?? null;


$bank =
    $result[
        'bank'
    ]
    ?? null;


wildcardRegressionCheck(
    'Squad cost is numeric',
    is_numeric(
        $cost
    )
);


wildcardRegressionCheck(
    'Squad remains within £100m',
    is_numeric(
        $cost
    )
    &&
    (float) $cost
    <= 100.0
);


wildcardRegressionCheck(
    'Remaining bank is numeric',
    is_numeric(
        $bank
    )
);


wildcardRegressionCheck(
    'Remaining bank is not negative',
    is_numeric(
        $bank
    )
    &&
    (float) $bank
    >= 0
);


wildcardRegressionCheck(
    'Squad uses a meaningful proportion of the wildcard budget',
    is_numeric(
        $cost
    )
    &&
    (float) $cost
    >= 90.0
);


echo "Squad Cost: £"
    . (
        is_numeric(
            $cost
        )
            ? number_format(
                (float) $cost,
                1
            )
            : 'N/A'
    )
    . "m<br>";


echo "Bank: £"
    . (
        is_numeric(
            $bank
        )
            ? number_format(
                (float) $bank,
                1
            )
            : 'N/A'
    )
    . "m<br><br>";


/*
 * ============================================================
 * DUPLICATE / CLUB LIMIT PROTECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Squad Legality<br>";
echo "============================================<br>";


$playerIds =
    [];


$clubCounts =
    [];


foreach (
    $squad
    as $player
) {

    $playerId =
        (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );


    if ($playerId > 0) {

        $playerIds[] =
            $playerId;
    }


    $teamId =
        (int) (
            $player[
                'team_id'
            ]
            ?? 0
        );


    if ($teamId > 0) {

        $clubCounts[
            $teamId
        ] =
            (
                $clubCounts[
                    $teamId
                ]
                ?? 0
            )
            +
            1;
    }
}


wildcardRegressionCheck(
    'Generated squad contains no duplicate players',
    count(
        $playerIds
    )
    ===
    count(
        array_unique(
            $playerIds
        )
    )
);


$clubLimitValid =
    true;


foreach (
    $clubCounts
    as $count
) {

    if ($count > 3) {

        $clubLimitValid =
            false;

        break;
    }
}


wildcardRegressionCheck(
    'No club contributes more than three players',
    $clubLimitValid
);


echo "<br>";


/*
 * ============================================================
 * STARTING XI STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Starting XI Structure<br>";
echo "============================================<br>";


$startingXI =
    $result[
        'starting_xi'
    ]
    ?? [];


$bench =
    $result[
        'bench'
    ]
    ?? [];


$formation =
    $result[
        'formation'
    ]
    ?? null;


wildcardRegressionCheck(
    'Starting XI contains exactly 11 players',
    count(
        $startingXI
    )
    === 11
);


wildcardRegressionCheck(
    'Bench contains exactly four players',
    count(
        $bench
    )
    === 4
);


wildcardRegressionCheck(
    'Best formation is returned',
    is_string(
        $formation
    )
    &&
    $formation !== ''
);


$startingPositionCounts = [

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
        ?? null;


    if (
        isset(
            $startingPositionCounts[
                $position
            ]
        )
    ) {

        $startingPositionCounts[
            $position
        ]++;
    }
}


wildcardRegressionCheck(
    'Starting XI contains one goalkeeper',
    $startingPositionCounts[
        'GK'
    ]
    === 1
);


wildcardRegressionCheck(
    'Starting XI contains between three and five defenders',
    $startingPositionCounts[
        'DEF'
    ]
    >= 3
    &&
    $startingPositionCounts[
        'DEF'
    ]
    <= 5
);


wildcardRegressionCheck(
    'Starting XI contains between two and five midfielders',
    $startingPositionCounts[
        'MID'
    ]
    >= 2
    &&
    $startingPositionCounts[
        'MID'
    ]
    <= 5
);


wildcardRegressionCheck(
    'Starting XI contains between one and three forwards',
    $startingPositionCounts[
        'FWD'
    ]
    >= 1
    &&
    $startingPositionCounts[
        'FWD'
    ]
    <= 3
);


echo "Formation: "
    . htmlspecialchars(
        (string) (
            $formation
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * GOALKEEPER RELIABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Goalkeeper Reliability<br>";
echo "============================================<br>";


$search =
    $result[
        'search'
    ]
    ?? [];


$gkMinConfidence =
    $search[
        'gk_starter_min_confidence'
    ]
    ?? null;


$gkScoreFloor =
    $search[
        'gk_starter_score_floor'
    ]
    ?? null;


$startingGoalkeeper =
    null;


foreach (
    $startingXI
    as $player
) {

    if (
        (
            $player[
                'position'
            ]
            ?? null
        )
        ===
        'GK'
    ) {

        $startingGoalkeeper =
            $player;

        break;
    }
}


wildcardRegressionCheck(
    'Starting goalkeeper is identified',
    is_array(
        $startingGoalkeeper
    )
);


wildcardRegressionCheck(
    'GK confidence threshold is returned',
    is_numeric(
        $gkMinConfidence
    )
);


wildcardRegressionCheck(
    'GK Starter Score floor is returned',
    is_numeric(
        $gkScoreFloor
    )
);


$startingGkConfidence =
    is_array(
        $startingGoalkeeper
    )
        ? (
            $startingGoalkeeper[
                'sample_confidence'
            ]
            ?? null
        )
        : null;


$startingGkScore =
    is_array(
        $startingGoalkeeper
    )
        ? (
            $startingGoalkeeper[
                'starter_score'
            ]
            ?? null
        )
        : null;


wildcardRegressionCheck(
    'Starting goalkeeper meets confidence requirement',
    is_numeric(
        $startingGkConfidence
    )
    &&
    is_numeric(
        $gkMinConfidence
    )
    &&
    (float) $startingGkConfidence
    >=
    (float) $gkMinConfidence
);


wildcardRegressionCheck(
    'Starting goalkeeper meets Starter Score quality floor',
    is_numeric(
        $startingGkScore
    )
    &&
    is_numeric(
        $gkScoreFloor
    )
    &&
    (float) $startingGkScore
    >=
    (float) $gkScoreFloor
);


echo "GK Min Confidence: "
    . (
        is_numeric(
            $gkMinConfidence
        )
            ? number_format(
                (float) $gkMinConfidence,
                1
            )
            : 'N/A'
    )
    . "%<br>";


echo "GK Starter Score Floor: "
    . (
        is_numeric(
            $gkScoreFloor
        )
            ? number_format(
                (float) $gkScoreFloor,
                2
            )
            : 'N/A'
    )
    . "<br><br>";


/*
 * ============================================================
 * PLAYER SCORE OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Player Score Integrity<br>";
echo "============================================<br>";


$starterScoresValid =
    true;


$valueScoresValid =
    true;


$wildcardScoresValid =
    true;


foreach (
    $squad
    as $player
) {

    if (
        !is_numeric(
            $player[
                'starter_score'
            ]
            ?? null
        )
    ) {

        $starterScoresValid =
            false;
    }


    if (
        !is_numeric(
            $player[
                'squad_value_score'
            ]
            ?? null
        )
    ) {

        $valueScoresValid =
            false;
    }


    if (
        !is_numeric(
            $player[
                'wildcard_score'
            ]
            ?? null
        )
    ) {

        $wildcardScoresValid =
            false;
    }
}


wildcardRegressionCheck(
    'All squad players contain Starter Score',
    $starterScoresValid
);


wildcardRegressionCheck(
    'All squad players contain Squad Value Score',
    $valueScoresValid
);


wildcardRegressionCheck(
    'All squad players contain Wildcard Score',
    $wildcardScoresValid
);


echo "<br>";


/*
 * ============================================================
 * BENCH RELIABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Bench Reliability<br>";
echo "============================================<br>";


$rawBenchScore =
    $result[
        'raw_bench_score'
    ]
    ?? null;


$adjustedBenchScore =
    $result[
        'bench_score'
    ]
    ?? null;


$benchReliabilityPenalty =
    $result[
        'bench_reliability_penalty'
    ]
    ?? null;


wildcardRegressionCheck(
    'Raw bench score is numeric',
    is_numeric(
        $rawBenchScore
    )
);


wildcardRegressionCheck(
    'Reliability-adjusted bench score is numeric',
    is_numeric(
        $adjustedBenchScore
    )
);


wildcardRegressionCheck(
    'Bench reliability penalty is numeric',
    is_numeric(
        $benchReliabilityPenalty
    )
);


wildcardRegressionCheck(
    'Bench reliability penalty is not negative',
    is_numeric(
        $benchReliabilityPenalty
    )
    &&
    (float) $benchReliabilityPenalty
    >= 0
);


wildcardRegressionCheck(
    'Adjusted bench score does not exceed raw bench score',
    is_numeric(
        $rawBenchScore
    )
    &&
    is_numeric(
        $adjustedBenchScore
    )
    &&
    (float) $adjustedBenchScore
    <=
    (float) $rawBenchScore
);


$benchOrders =
    [];


foreach (
    $bench
    as $player
) {

    if (
        isset(
            $player[
                'bench_order'
            ]
        )
    ) {

        $benchOrders[] =
            (int) $player[
                'bench_order'
            ];
    }
}


sort(
    $benchOrders,
    SORT_NUMERIC
);


wildcardRegressionCheck(
    'Bench order contains positions one to four',
    $benchOrders
    ===
    [
        1,
        2,
        3,
        4
    ]
);


echo "Raw Bench Score: "
    . (
        is_numeric(
            $rawBenchScore
        )
            ? number_format(
                (float) $rawBenchScore,
                2
            )
            : 'N/A'
    )
    . "<br>";


echo "Adjusted Bench Score: "
    . (
        is_numeric(
            $adjustedBenchScore
        )
            ? number_format(
                (float) $adjustedBenchScore,
                2
            )
            : 'N/A'
    )
    . "<br>";


echo "Bench Reliability Penalty: "
    . (
        is_numeric(
            $benchReliabilityPenalty
        )
            ? number_format(
                (float) $benchReliabilityPenalty,
                2
            )
            : 'N/A'
    )
    . "<br><br>";


/*
 * ============================================================
 * COMPLETE SQUAD SCORES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Squad Score Integrity<br>";
echo "============================================<br>";


wildcardRegressionCheck(
    'Wildcard Score is numeric',
    is_numeric(
        $result[
            'wildcard_score'
        ]
        ?? null
    )
);


wildcardRegressionCheck(
    'Structure Score is numeric',
    is_numeric(
        $result[
            'structure_score'
        ]
        ?? null
    )
);


wildcardRegressionCheck(
    'Starting XI Score is numeric',
    is_numeric(
        $result[
            'starting_xi_score'
        ]
        ?? null
    )
);


wildcardRegressionCheck(
    'Bench Score is numeric',
    is_numeric(
        $result[
            'bench_score'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * SEARCH METADATA
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Search Metadata<br>";
echo "============================================<br>";


wildcardRegressionCheck(
    'Beam width is returned',
    is_numeric(
        $search[
            'beam_width'
        ]
        ?? null
    )
);


wildcardRegressionCheck(
    'Position starter limit is returned',
    is_numeric(
        $search[
            'position_score_limit'
        ]
        ?? null
    )
);


wildcardRegressionCheck(
    'Position cheap limit is returned',
    is_numeric(
        $search[
            'position_cheap_limit'
        ]
        ?? null
    )
);


wildcardRegressionCheck(
    'Final states considered is returned',
    is_numeric(
        $search[
            'final_states_considered'
        ]
        ?? null
    )
    &&
    (
        (int) $search[
            'final_states_considered'
        ]
    )
    > 0
);


echo "<br>";


/*
 * ============================================================
 * PERFORMANCE REGRESSION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Performance<br>";
echo "============================================<br>";


/*
 * Current real-data runtime is approximately six seconds.
 *
 * A 15-second ceiling gives us meaningful regression protection
 * without making normal local variance cause false failures.
 */

wildcardRegressionCheck(
    'Real-data wildcard optimization completes within 15 seconds',
    $runtime
    <
    15.0
);


echo "Measured Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Wildcard Optimizer Regression Test Summary<br>";
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