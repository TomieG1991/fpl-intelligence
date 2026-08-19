<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function testPass(
    string $message,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . $message
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . $message
        . "<br>";

    $failed++;
}


$optimizer =
    new SquadTransferOptimizer();


echo "============================================<br>";
echo "Squad Transfer Optimizer Test<br>";
echo "============================================<br>";


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function squadOptimizerPlayer(
    int $playerId,
    int $teamId,
    string $position,
    float $price,
    float $intelligence,
    float $strength,
    float $value,
    float $fixtures,
    float $confidence,
    string $name
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

        /*
         * TransferDecision expects 0-1 confidence.
         */
        'sample_confidence' =>
            $confidence,

        'verdict' =>
            'Test Player'
    ];
}


/*
 * ============================================================
 * VALID SQUAD
 * ============================================================
 */

$squad = [

    squadOptimizerPlayer(
        1,
        1,
        'GK',
        5.0,
        65,
        62,
        60,
        65,
        1.0,
        'GK A'
    ),

    squadOptimizerPlayer(
        2,
        2,
        'GK',
        4.0,
        55,
        52,
        65,
        60,
        1.0,
        'GK B'
    ),


    squadOptimizerPlayer(
        3,
        1,
        'DEF',
        6.0,
        68,
        66,
        60,
        70,
        1.0,
        'DEF A'
    ),

    squadOptimizerPlayer(
        4,
        2,
        'DEF',
        5.0,
        62,
        60,
        58,
        65,
        1.0,
        'DEF B'
    ),

    squadOptimizerPlayer(
        5,
        3,
        'DEF',
        4.5,
        58,
        55,
        60,
        60,
        1.0,
        'DEF C'
    ),

    squadOptimizerPlayer(
        6,
        4,
        'DEF',
        4.5,
        50,
        48,
        55,
        50,
        1.0,
        'DEF D'
    ),

    squadOptimizerPlayer(
        7,
        5,
        'DEF',
        4.0,
        45,
        45,
        60,
        45,
        1.0,
        'DEF E'
    ),


    squadOptimizerPlayer(
        8,
        1,
        'MID',
        10.0,
        70,
        68,
        50,
        70,
        1.0,
        'MID A'
    ),

    squadOptimizerPlayer(
        9,
        2,
        'MID',
        8.0,
        66,
        64,
        58,
        65,
        1.0,
        'MID B'
    ),

    squadOptimizerPlayer(
        10,
        3,
        'MID',
        6.0,
        60,
        58,
        62,
        60,
        1.0,
        'MID C'
    ),

    squadOptimizerPlayer(
        11,
        4,
        'MID',
        5.5,
        52,
        50,
        55,
        50,
        1.0,
        'MID D'
    ),

    squadOptimizerPlayer(
        12,
        5,
        'MID',
        5.0,
        42,
        45,
        40,
        45,
        1.0,
        'MID E'
    ),


    squadOptimizerPlayer(
        13,
        3,
        'FWD',
        12.0,
        72,
        70,
        45,
        68,
        1.0,
        'FWD A'
    ),

    squadOptimizerPlayer(
        14,
        4,
        'FWD',
        7.0,
        62,
        60,
        55,
        60,
        1.0,
        'FWD B'
    ),

    squadOptimizerPlayer(
        15,
        5,
        'FWD',
        5.5,
        50,
        50,
        58,
        50,
        1.0,
        'FWD C'
    )
];


/*
 * ============================================================
 * SQUAD ANALYSIS
 * ============================================================
 */

$squadAnalysis = [

    'squad' =>
        $squad,

    'bank' =>
        1.0,

    'validation' => [

        'is_valid' =>
            true
    ],

    /*
     * Highest transfer priority first.
     */
    'ranking' => [

        array_merge(
            $squad[11],
            [
                'transfer_priority' =>
                    58.0,

                'priority_label' =>
                    'Moderate'
            ]
        ),

        array_merge(
            $squad[6],
            [
                'transfer_priority' =>
                    54.0,

                'priority_label' =>
                    'Moderate'
            ]
        ),

        array_merge(
            $squad[10],
            [
                'transfer_priority' =>
                    48.0,

                'priority_label' =>
                    'Moderate'
            ]
        ),

        array_merge(
            $squad[14],
            [
                'transfer_priority' =>
                    44.0,

                'priority_label' =>
                    'Moderate'
            ]
        ),

        array_merge(
            $squad[1],
            [
                'transfer_priority' =>
                    40.0,

                'priority_label' =>
                    'Moderate'
            ]
        )
    ]
];


/*
 * ============================================================
 * REPLACEMENT POOL
 * ============================================================
 */

$allPlayers = [

    /*
     * Strong MID replacement.
     */
    squadOptimizerPlayer(
        20,
        6,
        'MID',
        5.5,
        65,
        64,
        70,
        68,
        1.0,
        'MID Upgrade'
    ),

    /*
     * Cheaper MID replacement.
     */
    squadOptimizerPlayer(
        21,
        7,
        'MID',
        4.5,
        60,
        58,
        78,
        62,
        1.0,
        'MID Value'
    ),

    /*
     * Unaffordable MID.
     *
     * MID E has £5.0m + £1.0m bank = £6.0m.
     */
    squadOptimizerPlayer(
        22,
        8,
        'MID',
        7.0,
        75,
        72,
        60,
        72,
        1.0,
        'MID Too Expensive'
    ),

    /*
     * Wrong position.
     */
    squadOptimizerPlayer(
        23,
        9,
        'FWD',
        5.0,
        80,
        80,
        80,
        80,
        1.0,
        'Wrong Position'
    ),

    /*
     * Strong DEF replacement.
     */
    squadOptimizerPlayer(
        24,
        6,
        'DEF',
        4.5,
        62,
        60,
        72,
        68,
        1.0,
        'DEF Upgrade'
    ),

    /*
     * Strong FWD replacement.
     */
    squadOptimizerPlayer(
        25,
        6,
        'FWD',
        6.0,
        64,
        62,
        68,
        65,
        1.0,
        'FWD Upgrade'
    ),

    /*
     * Strong GK replacement.
     */
    squadOptimizerPlayer(
        26,
        6,
        'GK',
        4.5,
        62,
        60,
        70,
        68,
        1.0,
        'GK Upgrade'
    ),

    /*
     * Already-owned MID.
     */
    $squad[9],

    /*
     * Team 1 currently already has:
     *
     * GK A
     * DEF A
     * MID A
     *
     * therefore a fourth Team 1 player is illegal unless
     * outgoing player is also from Team 1.
     */
    squadOptimizerPlayer(
        27,
        1,
        'MID',
        5.0,
        80,
        78,
        80,
        80,
        1.0,
        'Club Limit MID'
    )
];


/*
 * ============================================================
 * SCENARIO A
 * STANDARD SINGLE-TRANSFER OPTIMIZATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Standard Optimization<br>";
echo "============================================<br>";


$result =
    $optimizer
        ->findBestSingleTransfers(
            $squadAnalysis,
            $allPlayers,
            5,
            5
        );


testPass(
    'Squad optimizer returns an array',
    is_array(
        $result
    )
);


testPass(
    'Squad optimizer returns success status',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


testPass(
    'Bank is preserved',
    (
        $result[
            'bank'
        ]
        ?? null
    )
    === 1.0
);


testPass(
    'Priority limit is preserved',
    (
        $result[
            'priority_limit'
        ]
        ?? null
    )
    === 5
);


testPass(
    'Replacement limit is preserved',
    (
        $result[
            'replacement_limit'
        ]
        ?? null
    )
    === 5
);


testPass(
    'Recommendations array exists',
    isset(
        $result[
            'recommendations'
        ]
    )
    &&
    is_array(
        $result[
            'recommendations'
        ]
    )
);


testPass(
    'Five priority players are considered',
    (
        $result[
            'players_considered'
        ]
        ?? 0
    )
    === 5
);


/*
 * ============================================================
 * SCENARIO B
 * HIGHEST-PRIORITY PLAYER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Highest Priority Player<br>";
echo "============================================<br>";


$firstRecommendation =
    $result[
        'recommendations'
    ][0]
    ?? [];


testPass(
    'Highest-priority outgoing player is MID E',
    (
        $firstRecommendation[
            'outgoing'
        ]['player_id']
        ?? null
    )
    === 12
);


testPass(
    'Outgoing transfer priority is preserved',
    (
        $firstRecommendation[
            'transfer_priority'
        ]
        ?? null
    )
    === 58.0
);


testPass(
    'Available budget includes bank',
    (
        $firstRecommendation[
            'available_budget'
        ]
        ?? null
    )
    === 6.0
);


/*
 * ============================================================
 * SCENARIO C
 * LEGAL REPLACEMENT FILTERING
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Legal Replacement Filtering<br>";
echo "============================================<br>";


$firstReplacements =
    $firstRecommendation[
        'replacements'
    ]
    ?? [];


testPass(
    'Highest-priority player has legal replacements',
    !empty(
        $firstReplacements
    )
);


$illegalCandidateFound =
    false;


foreach (
    $firstReplacements
    as $replacement
) {

    $player =
        $replacement[
            'player'
        ]
        ?? [];


    $playerId =
        (int) (
            $player[
                'player_id'
            ]
            ?? 0
        );


    $position =
        $player[
            'position'
        ]
        ?? null;


    $price =
        (float) (
            $player[
                'price'
            ]
            ?? 0
        );


    if (
        $position !== 'MID'
        ||
        $price > 6.0
        ||
        in_array(
            $playerId,
            [
                1,
                2,
                3,
                4,
                5,
                6,
                7,
                8,
                9,
                10,
                11,
                12,
                13,
                14,
                15
            ],
            true
        )
        ||
        $playerId === 27
    ) {

        $illegalCandidateFound =
            true;

        break;
    }
}


testPass(
    'Wrong-position, unaffordable, owned and club-limit players are excluded',
    $illegalCandidateFound
    === false
);


/*
 * ============================================================
 * SCENARIO D
 * REPLACEMENT DECISION DATA
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Transfer Decision Data<br>";
echo "============================================<br>";


$topReplacement =
    $firstReplacements[0]
    ?? [];


testPass(
    'Top replacement contains player data',
    isset(
        $topReplacement[
            'player'
        ]
    )
    &&
    is_array(
        $topReplacement[
            'player'
        ]
    )
);


testPass(
    'Top replacement contains TransferDecision result',
    isset(
        $topReplacement[
            'decision'
        ]
    )
    &&
    is_array(
        $topReplacement[
            'decision'
        ]
    )
);


testPass(
    'Decision score exists',
    array_key_exists(
        'decision_score',
        $topReplacement
    )
);


testPass(
    'Decision type exists',
    !empty(
        $topReplacement[
            'decision_type'
        ]
        ?? null
    )
);


testPass(
    'Budget after transfer exists',
    array_key_exists(
        'budget_after',
        $topReplacement
    )
);


testPass(
    'Replacement rank begins at one',
    (
        $topReplacement[
            'rank'
        ]
        ?? null
    )
    === 1
);


/*
 * ============================================================
 * SCENARIO E
 * REPLACEMENT RANKING
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Replacement Ranking<br>";
echo "============================================<br>";


$replacementRankingValid =
    true;


for (
    $i = 1;
    $i < count(
        $firstReplacements
    );
    $i++
) {

    $previousScore =
        (float) (
            $firstReplacements[
                $i - 1
            ]['decision_score']
            ?? -999999
        );


    $currentScore =
        (float) (
            $firstReplacements[
                $i
            ]['decision_score']
            ?? -999999
        );


    if (
        $currentScore
        >
        $previousScore
    ) {

        $replacementRankingValid =
            false;

        break;
    }
}


testPass(
    'Replacement recommendations are ranked by decision score',
    $replacementRankingValid
);


/*
 * ============================================================
 * SCENARIO F
 * BANK SUPPORT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Bank Support<br>";
echo "============================================<br>";


$noBankAnalysis =
    $squadAnalysis;


$noBankAnalysis[
    'bank'
] =
    0.0;


$noBankResult =
    $optimizer
        ->findBestSingleTransfers(
            $noBankAnalysis,
            $allPlayers,
            1,
            10
        );


$withBankResult =
    $optimizer
        ->findBestSingleTransfers(
            $squadAnalysis,
            $allPlayers,
            1,
            10
        );


$noBankCandidateIds =
    [];


foreach (
    $noBankResult[
        'recommendations'
    ][0]['replacements']
    ?? []
    as $replacement
) {

    $noBankCandidateIds[] =
        (int) (
            $replacement[
                'player'
            ]['player_id']
            ?? 0
        );
}


$withBankCandidateIds =
    [];


foreach (
    $withBankResult[
        'recommendations'
    ][0]['replacements']
    ?? []
    as $replacement
) {

    $withBankCandidateIds[] =
        (int) (
            $replacement[
                'player'
            ]['player_id']
            ?? 0
        );
}


testPass(
    '£5.5m MID is unavailable without bank',
    !in_array(
        20,
        $noBankCandidateIds,
        true
    )
);


testPass(
    '£5.5m MID becomes affordable with £1.0m bank',
    in_array(
        20,
        $withBankCandidateIds,
        true
    )
);


/*
 * ============================================================
 * SCENARIO G
 * REPLACEMENT LIMIT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Replacement Limit<br>";
echo "============================================<br>";


$limitedResult =
    $optimizer
        ->findBestSingleTransfers(
            $squadAnalysis,
            $allPlayers,
            1,
            1
        );


testPass(
    'Replacement limit is respected',
    count(
        $limitedResult[
            'recommendations'
        ][0]['replacements']
        ?? []
    )
    <= 1
);


/*
 * ============================================================
 * SCENARIO H
 * PRIORITY LIMIT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Priority Limit<br>";
echo "============================================<br>";


$priorityLimited =
    $optimizer
        ->findBestSingleTransfers(
            $squadAnalysis,
            $allPlayers,
            2,
            5
        );


testPass(
    'Priority limit is respected',
    (
        $priorityLimited[
            'players_considered'
        ]
        ?? 0
    )
    === 2
);


/*
 * ============================================================
 * SCENARIO I
 * INVALID SQUAD
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Invalid Squad<br>";
echo "============================================<br>";


$invalidAnalysis =
    $squadAnalysis;


$invalidAnalysis[
    'validation'
]['is_valid'] =
    false;


$invalidResult =
    $optimizer
        ->findBestSingleTransfers(
            $invalidAnalysis,
            $allPlayers
        );


testPass(
    'Invalid squad returns invalid status',
    (
        $invalidResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


testPass(
    'Invalid squad returns no recommendations',
    empty(
        $invalidResult[
            'recommendations'
        ]
        ?? []
    )
);


/*
 * ============================================================
 * SCENARIO J
 * EMPTY PLAYER POOL
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario J: Empty Player Pool<br>";
echo "============================================<br>";


$emptyPoolResult =
    $optimizer
        ->findBestSingleTransfers(
            $squadAnalysis,
            []
        );


testPass(
    'Empty replacement pool returns invalid status',
    (
        $emptyPoolResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


/*
 * ============================================================
 * SCENARIO K
 * INVALID LIMITS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario K: Invalid Limits<br>";
echo "============================================<br>";


$zeroPriority =
    $optimizer
        ->findBestSingleTransfers(
            $squadAnalysis,
            $allPlayers,
            0,
            5
        );


testPass(
    'Zero priority limit returns invalid status',
    (
        $zeroPriority[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


$zeroReplacement =
    $optimizer
        ->findBestSingleTransfers(
            $squadAnalysis,
            $allPlayers,
            5,
            0
        );


testPass(
    'Zero replacement limit returns invalid status',
    (
        $zeroReplacement[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Squad Transfer Optimizer Test Summary<br>";
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