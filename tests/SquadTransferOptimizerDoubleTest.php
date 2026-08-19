<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Squad Transfer Optimizer Double Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function testPass(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . $description
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . $description
        . "<br>";

    $failed++;
}


/*
 * ============================================================
 * PLAYER FACTORY
 * ============================================================
 */

function makePlayer(
    int $playerId,
    string $name,
    int $teamId,
    string $position,
    float $price,
    float $intelligence,
    float $strength,
    float $value,
    float $fixtures,
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
            $confidence,

        'verdict' =>
            'Synthetic test player'
    ];
}


/*
 * ============================================================
 * BUILD VALID SQUAD
 * ============================================================
 */

$squad = [

    makePlayer(
        1, 'Goalkeeper A', 1, 'GK',
        5.0, 65.0, 65.0, 65.0, 65.0
    ),

    makePlayer(
        2, 'Goalkeeper B', 2, 'GK',
        4.0, 55.0, 55.0, 65.0, 55.0
    ),


    makePlayer(
        3, 'Defender Weak', 3, 'DEF',
        5.0, 35.0, 35.0, 40.0, 40.0
    ),

    makePlayer(
        4, 'Defender B', 4, 'DEF',
        5.0, 60.0, 60.0, 60.0, 60.0
    ),

    makePlayer(
        5, 'Defender C', 5, 'DEF',
        5.0, 62.0, 62.0, 62.0, 62.0
    ),

    makePlayer(
        6, 'Defender D', 6, 'DEF',
        4.5, 58.0, 58.0, 62.0, 58.0
    ),

    makePlayer(
        7, 'Defender E', 7, 'DEF',
        4.5, 57.0, 57.0, 63.0, 57.0
    ),


    makePlayer(
        8, 'Midfielder Weak', 8, 'MID',
        6.0, 34.0, 34.0, 38.0, 40.0
    ),

    makePlayer(
        9, 'Midfielder B', 9, 'MID',
        7.0, 64.0, 64.0, 60.0, 64.0
    ),

    makePlayer(
        10, 'Midfielder C', 10, 'MID',
        7.0, 63.0, 63.0, 59.0, 63.0
    ),

    makePlayer(
        11, 'Midfielder D', 11, 'MID',
        6.5, 61.0, 61.0, 62.0, 61.0
    ),

    makePlayer(
        12, 'Midfielder E', 12, 'MID',
        6.5, 60.0, 60.0, 61.0, 60.0
    ),


    makePlayer(
        13, 'Forward A', 13, 'FWD',
        9.0, 67.0, 67.0, 58.0, 67.0
    ),

    makePlayer(
        14, 'Forward B', 14, 'FWD',
        7.0, 61.0, 61.0, 61.0, 61.0
    ),

    makePlayer(
        15, 'Forward C', 15, 'FWD',
        6.0, 58.0, 58.0, 63.0, 58.0
    )
];


/*
 * ============================================================
 * BUILD SQUAD ANALYSIS
 * ============================================================
 */

$squadIntelligence =
    new SquadTransferIntelligence();


$squadAnalysis =
    $squadIntelligence
        ->analyzeSquad(
            $squad,
            1.0
        );


echo "============================================<br>";
echo "Scenario A: Squad Setup<br>";
echo "============================================<br>";


testPass(
    'Synthetic squad is valid',
    (
        $squadAnalysis[
            'validation'
        ]['is_valid']
        ?? false
    )
    === true
);


testPass(
    'Synthetic squad contains 15 players',
    count(
        $squad
    )
    === 15
);


testPass(
    'Squad bank is £1.0m',
    abs(
        (
            (float) (
                $squadAnalysis[
                    'bank'
                ]
                ?? -1
            )
        )
        -
        1.0
    )
    < 0.001
);


echo "<br>";


/*
 * ============================================================
 * REPLACEMENT CANDIDATES
 * ============================================================
 *
 * IDs 101+ are not members of the existing squad.
 */

$replacementPlayers = [

    makePlayer(
        101, 'Defender Upgrade A', 16, 'DEF',
        5.5, 72.0, 72.0, 70.0, 75.0
    ),

    makePlayer(
        102, 'Defender Upgrade B', 17, 'DEF',
        5.0, 69.0, 69.0, 72.0, 70.0
    ),

    makePlayer(
        103, 'Defender Budget', 18, 'DEF',
        4.5, 63.0, 63.0, 78.0, 65.0
    ),


    makePlayer(
        104, 'Midfielder Upgrade A', 19, 'MID',
        6.5, 74.0, 74.0, 72.0, 76.0
    ),

    makePlayer(
        105, 'Midfielder Upgrade B', 20, 'MID',
        6.0, 70.0, 70.0, 74.0, 72.0
    ),

    makePlayer(
        106, 'Midfielder Budget', 21, 'MID',
        5.5, 65.0, 65.0, 80.0, 66.0
    ),


    makePlayer(
        107, 'Forward Upgrade', 22, 'FWD',
        7.0, 70.0, 70.0, 70.0, 70.0
    ),


    makePlayer(
        108, 'Goalkeeper Upgrade', 23, 'GK',
        5.0, 70.0, 70.0, 70.0, 70.0
    )
];


/*
 * TransferOptimizer should receive the complete player pool.
 */
$allPlayers =
    array_merge(
        $squad,
        $replacementPlayers
    );


/*
 * ============================================================
 * RUN DOUBLE OPTIMIZER
 * ============================================================
 */

$optimizer =
    new SquadTransferOptimizer();


$result =
    $optimizer
        ->findBestDoubleTransfers(
            $squadAnalysis,
            $allPlayers,
            5,
            10
        );


echo "============================================<br>";
echo "Scenario B: Double Transfer Result<br>";
echo "============================================<br>";


testPass(
    'Double optimizer returns an array',
    is_array(
        $result
    )
);


testPass(
    'Double optimizer returns success',
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
    'Five priority players are considered',
    (
        $result[
            'priority_players_considered'
        ]
        ?? 0
    )
    === 5
);


testPass(
    'Ten outgoing pairs are considered',
    (
        $result[
            'outgoing_pairs_considered'
        ]
        ?? 0
    )
    === 10
);


testPass(
    'At least one legal combination is found',
    (
        $result[
            'total_found'
        ]
        ?? 0
    )
    > 0
);


testPass(
    'Result limit is respected',
    count(
        $result[
            'combinations'
        ]
        ?? []
    )
    <= 10
);


echo "<br>";


/*
 * ============================================================
 * VALIDATE RETURNED COMBINATIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Combination Legality<br>";
echo "============================================<br>";


$ownedIds =
    [];


foreach (
    $squad
    as $player
) {

    $ownedIds[
        (int) $player[
            'player_id'
        ]
    ] =
        true;
}


$allLegal =
    true;


$positionsPreserved =
    true;


$uniqueIncoming =
    true;


$budgetLegal =
    true;


$ranksCorrect =
    true;


foreach (
    $result[
        'combinations'
    ]
    ?? []
    as $index => $combination
) {

    $transferA =
        $combination[
            'transfer_a'
        ]
        ?? [];


    $transferB =
        $combination[
            'transfer_b'
        ]
        ?? [];


    $outgoingA =
        $transferA[
            'current_player'
        ]
        ?? [];


    $outgoingB =
        $transferB[
            'current_player'
        ]
        ?? [];


    $incomingA =
        $transferA[
            'replacement'
        ]
        ?? [];


    $incomingB =
        $transferB[
            'replacement'
        ]
        ?? [];


    $incomingIdA =
        (int) (
            $incomingA[
                'player_id'
            ]
            ?? 0
        );


    $incomingIdB =
        (int) (
            $incomingB[
                'player_id'
            ]
            ?? 0
        );


    if (
        $incomingIdA <= 0
        ||
        $incomingIdB <= 0
    ) {

        $allLegal =
            false;
    }


    if (
        $incomingIdA
        ===
        $incomingIdB
    ) {

        $uniqueIncoming =
            false;
    }


    /*
     * The replacement cannot be an existing squad member
     * unless that exact player is one of the two being sold.
     */

    $outgoingIdA =
        (int) (
            $outgoingA[
                'player_id'
            ]
            ?? 0
        );


    $outgoingIdB =
        (int) (
            $outgoingB[
                'player_id'
            ]
            ?? 0
        );


    foreach (
        [
            $incomingIdA,
            $incomingIdB
        ]
        as $incomingId
    ) {

        if (
            isset(
                $ownedIds[
                    $incomingId
                ]
            )
            &&
            $incomingId !== $outgoingIdA
            &&
            $incomingId !== $outgoingIdB
        ) {

            $allLegal =
                false;
        }
    }


    if (
        strtoupper(
            (string) (
                $outgoingA[
                    'position'
                ]
                ?? ''
            )
        )
        !==
        strtoupper(
            (string) (
                $incomingA[
                    'position'
                ]
                ?? ''
            )
        )
    ) {

        $positionsPreserved =
            false;
    }


    if (
        strtoupper(
            (string) (
                $outgoingB[
                    'position'
                ]
                ?? ''
            )
        )
        !==
        strtoupper(
            (string) (
                $incomingB[
                    'position'
                ]
                ?? ''
            )
        )
    ) {

        $positionsPreserved =
            false;
    }


    $budgetAfter =
        (float) (
            $combination[
                'optimizer'
            ]['budget_after']
            ?? -999
        );


    if ($budgetAfter < -0.001) {

        $budgetLegal =
            false;
    }


    if (
        (
            $combination[
                'squad_optimizer'
            ]['rank']
            ?? null
        )
        !==
        (
            $index + 1
        )
    ) {

        $ranksCorrect =
            false;
    }
}


testPass(
    'Returned combinations contain legal players',
    $allLegal
);


testPass(
    'Incoming players are unique within each combination',
    $uniqueIncoming
);


testPass(
    'Transfer positions are preserved',
    $positionsPreserved
);


testPass(
    'Returned combinations remain affordable',
    $budgetLegal
);


testPass(
    'Squad optimizer ranks are sequential',
    $ranksCorrect
);


echo "<br>";


/*
 * ============================================================
 * VERIFY WEAK PLAYERS ARE TARGETED
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Squad Awareness<br>";
echo "============================================<br>";


$weakDefenderId =
    3;


$weakMidfielderId =
    8;


$weakPairFound =
    false;


foreach (
    $result[
        'combinations'
    ]
    ?? []
    as $combination
) {

    $outgoingIds =
        [

            (int) (
                $combination[
                    'transfer_a'
                ]['current_player']['player_id']
                ?? 0
            ),

            (int) (
                $combination[
                    'transfer_b'
                ]['current_player']['player_id']
                ?? 0
            )
        ];


    sort(
        $outgoingIds
    );


    $expectedIds =
        [
            $weakDefenderId,
            $weakMidfielderId
        ];


    sort(
        $expectedIds
    );


    if (
        $outgoingIds
        ===
        $expectedIds
    ) {

        $weakPairFound =
            true;

        break;
    }
}


testPass(
    'Weak defender and weak midfielder pair is evaluated successfully',
    $weakPairFound
);


echo "<br>";


/*
 * ============================================================
 * INVALID INPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Invalid Input<br>";
echo "============================================<br>";


$invalidAnalysis =
    $squadAnalysis;


$invalidAnalysis[
    'validation'
]['is_valid'] =
    false;


$invalidResult =
    $optimizer
        ->findBestDoubleTransfers(
            $invalidAnalysis,
            $allPlayers,
            5,
            10
        );


testPass(
    'Invalid squad analysis is rejected',
    (
        $invalidResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


$emptyPlayersResult =
    $optimizer
        ->findBestDoubleTransfers(
            $squadAnalysis,
            [],
            5,
            10
        );


testPass(
    'Empty player pool is rejected',
    (
        $emptyPlayersResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


$badOutgoingLimitResult =
    $optimizer
        ->findBestDoubleTransfers(
            $squadAnalysis,
            $allPlayers,
            1,
            10
        );


testPass(
    'Outgoing limit below two is rejected',
    (
        $badOutgoingLimitResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


$badResultLimitResult =
    $optimizer
        ->findBestDoubleTransfers(
            $squadAnalysis,
            $allPlayers,
            5,
            0
        );


testPass(
    'Zero result limit is rejected',
    (
        $badResultLimitResult[
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
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Squad Transfer Optimizer Double Test Summary<br>";
echo "============================================<br>";

echo "Passed: "
    . $passed
    . "<br>";

echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}