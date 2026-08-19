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


$intelligence =
    new SquadTransferIntelligence();


echo "============================================<br>";
echo "Squad Transfer Intelligence Test<br>";
echo "============================================<br>";


/*
 * ============================================================
 * TEST DATA
 * ============================================================
 */

function squadTestPlayer(
    int $playerId,
    int $teamId,
    string $position,
    float $intelligenceScore,
    float $valueRating,
    float $fixtureRating,
    float $availabilityRating,
    float $sampleConfidence,
    ?string $name = null
): array {

    return [

        'player_id' =>
            $playerId,

        'fpl_player_id' =>
            1000 + $playerId,

        'name' =>
            $name
            ?? 'Player '
            . $playerId,

        'team_id' =>
            $teamId,

        'team_name' =>
            'Team '
            . $teamId,

        'position' =>
            $position,

        'price' =>
            5.0,

        'intelligence_score' =>
            $intelligenceScore,

        'value_rating' =>
            $valueRating,

        'fixture_rating' =>
            $fixtureRating,

        'availability_rating' =>
            $availabilityRating,

        'sample_confidence' =>
            $sampleConfidence
    ];
}


/*
 * Build a structurally valid 15-player squad.
 */

$validSquad = [

    squadTestPlayer(
        1,
        1,
        'GK',
        70,
        65,
        65,
        100,
        100,
        'Goalkeeper A'
    ),

    squadTestPlayer(
        2,
        2,
        'GK',
        60,
        55,
        60,
        100,
        100,
        'Goalkeeper B'
    ),


    squadTestPlayer(
        3,
        1,
        'DEF',
        72,
        68,
        70,
        100,
        100,
        'Defender A'
    ),

    squadTestPlayer(
        4,
        2,
        'DEF',
        66,
        65,
        65,
        100,
        100,
        'Defender B'
    ),

    squadTestPlayer(
        5,
        3,
        'DEF',
        62,
        58,
        55,
        100,
        100,
        'Defender C'
    ),

    squadTestPlayer(
        6,
        4,
        'DEF',
        58,
        52,
        45,
        100,
        100,
        'Defender D'
    ),

    squadTestPlayer(
        7,
        5,
        'DEF',
        54,
        44,
        40,
        100,
        100,
        'Defender E'
    ),


    squadTestPlayer(
        8,
        1,
        'MID',
        75,
        70,
        72,
        100,
        100,
        'Midfielder A'
    ),

    squadTestPlayer(
        9,
        2,
        'MID',
        69,
        62,
        65,
        100,
        100,
        'Midfielder B'
    ),

    squadTestPlayer(
        10,
        3,
        'MID',
        63,
        58,
        58,
        100,
        100,
        'Midfielder C'
    ),

    squadTestPlayer(
        11,
        4,
        'MID',
        57,
        42,
        45,
        100,
        100,
        'Midfielder D'
    ),

    squadTestPlayer(
        12,
        5,
        'MID',
        48,
        35,
        35,
        70,
        25,
        'Midfielder E'
    ),


    squadTestPlayer(
        13,
        3,
        'FWD',
        71,
        64,
        68,
        100,
        100,
        'Forward A'
    ),

    squadTestPlayer(
        14,
        4,
        'FWD',
        61,
        55,
        55,
        100,
        100,
        'Forward B'
    ),

    squadTestPlayer(
        15,
        5,
        'FWD',
        56,
        48,
        50,
        100,
        100,
        'Forward C'
    )
];


/*
 * ============================================================
 * SCENARIO A
 * VALID SQUAD
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Valid Squad<br>";
echo "============================================<br>";


$result =
    $intelligence
        ->analyzeSquad(
            $validSquad,
            1.5
        );


testPass(
    'Squad analysis returns an array',
    is_array(
        $result
    )
);


testPass(
    'Valid squad is marked valid',
    (
        $result[
            'validation'
        ]['is_valid']
        ?? false
    )
    === true
);


testPass(
    'Squad contains 15 players',
    (
        $result[
            'validation'
        ]['player_count']
        ?? 0
    )
    === 15
);


testPass(
    'Squad contains two goalkeepers',
    (
        $result[
            'validation'
        ]['position_counts']['GK']
        ?? 0
    )
    === 2
);


testPass(
    'Squad contains five defenders',
    (
        $result[
            'validation'
        ]['position_counts']['DEF']
        ?? 0
    )
    === 5
);


testPass(
    'Squad contains five midfielders',
    (
        $result[
            'validation'
        ]['position_counts']['MID']
        ?? 0
    )
    === 5
);


testPass(
    'Squad contains three forwards',
    (
        $result[
            'validation'
        ]['position_counts']['FWD']
        ?? 0
    )
    === 3
);


testPass(
    'Bank is preserved',
    (
        $result[
            'bank'
        ]
        ?? null
    )
    === 1.5
);


testPass(
    'Valid squad contains no validation issues',
    empty(
        $result[
            'validation'
        ]['issues']
        ?? []
    )
);


/*
 * ============================================================
 * SCENARIO B
 * TRANSFER PRIORITY RANKING
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Transfer Priority Ranking<br>";
echo "============================================<br>";


$ranking =
    $result[
        'ranking'
    ]
    ?? [];


testPass(
    'Ranking contains all 15 players',
    count(
        $ranking
    )
    === 15
);


testPass(
    'Highest priority player is Midfielder E',
    (
        $ranking[0][
            'name'
        ]
        ?? null
    )
    ===
    'Midfielder E'
);


testPass(
    'Highest priority player has squad rank 1',
    (
        $ranking[0][
            'squad_rank'
        ]
        ?? null
    )
    === 1
);


testPass(
    'Highest priority player has Moderate priority label',
    (
        $ranking[0][
            'priority_label'
        ]
        ?? null
    )
    ===
    'Moderate'
);


$rankingIsDescending =
    true;


for (
    $i = 1;
    $i < count(
        $ranking
    );
    $i++
) {

    $previous =
        (float) (
            $ranking[
                $i - 1
            ]['transfer_priority']
            ?? 0
        );


    $current =
        (float) (
            $ranking[
                $i
            ]['transfer_priority']
            ?? 0
        );


    if (
        $current
        >
        $previous
    ) {

        $rankingIsDescending =
            false;

        break;
    }
}


testPass(
    'Transfer priority ranking is descending',
    $rankingIsDescending
);


/*
 * ============================================================
 * SCENARIO C
 * PRIORITY REASONS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Priority Reasons<br>";
echo "============================================<br>";


$highestPriority =
    $ranking[0]
    ?? [];


$priorityReasons =
    $highestPriority[
        'priority_reasons'
    ]
    ?? [];


testPass(
    'Highest priority player includes reasons',
    !empty(
        $priorityReasons
    )
);


testPass(
    'Low Intelligence reason is detected',
    in_array(
        'Low Intelligence score',
        $priorityReasons,
        true
    )
);


testPass(
    'Poor value reason is detected',
    in_array(
        'Poor value',
        $priorityReasons,
        true
    )
);


testPass(
    'Difficult fixture reason is detected',
    in_array(
        'Difficult upcoming fixtures',
        $priorityReasons,
        true
    )
);


testPass(
    'Availability concern is detected',
    in_array(
        'Availability concern',
        $priorityReasons,
        true
    )
);


testPass(
    'Low sample confidence is not treated as a transfer reason',
    !in_array(
        'Low sample confidence',
        $priorityReasons,
        true
    )
);

testPass(
    'Sample confidence is still preserved on ranked player',
    array_key_exists(
        'sample_confidence',
        $highestPriority
    )
);

/*
 * ============================================================
 * SCENARIO D
 * WEAKEST PLAYERS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Weakest Players<br>";
echo "============================================<br>";


$weakestPlayers =
    $result[
        'weakest_players'
    ]
    ?? [];


testPass(
    'Weakest player list contains five players',
    count(
        $weakestPlayers
    )
    === 5
);


testPass(
    'Weakest player list begins with highest priority player',
    (
        $weakestPlayers[0][
            'player_id'
        ]
        ?? null
    )
    ===
    (
        $ranking[0][
            'player_id'
        ]
        ?? null
    )
);


/*
 * ============================================================
 * SCENARIO E
 * SQUAD SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Squad Summary<br>";
echo "============================================<br>";


$summary =
    $result[
        'summary'
    ]
    ?? [];


testPass(
    'Average squad Intelligence exists',
    isset(
        $summary[
            'average_intelligence'
        ]
    )
    &&
    is_numeric(
        $summary[
            'average_intelligence'
        ]
    )
);


testPass(
    'Position averages exist',
    isset(
        $summary[
            'position_averages'
        ]
    )
    &&
    is_array(
        $summary[
            'position_averages'
        ]
    )
);


testPass(
    'Weakest position exists',
    !empty(
        $summary[
            'weakest_position'
        ]
        ?? null
    )
);


testPass(
    'Highest priority player ID matches ranking',
    (
        $summary[
            'highest_priority_player_id'
        ]
        ?? null
    )
    ===
    (
        $ranking[0][
            'player_id'
        ]
        ?? null
    )
);


testPass(
    'Highest transfer priority matches ranking',
    (
        $summary[
            'highest_transfer_priority'
        ]
        ?? null
    )
    ===
    (
        $ranking[0][
            'transfer_priority'
        ]
        ?? null
    )
);


/*
 * ============================================================
 * SCENARIO F
 * INVALID POSITION COUNTS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Invalid Position Counts<br>";
echo "============================================<br>";


$invalidPositionSquad =
    $validSquad;


/*
 * Turn one defender into a midfielder.
 *
 * Result:
 * DEF = 4
 * MID = 6
 */
$invalidPositionSquad[6][
    'position'
] =
    'MID';


$invalidPositionResult =
    $intelligence
        ->analyzeSquad(
            $invalidPositionSquad
        );


testPass(
    'Incorrect position structure is invalid',
    (
        $invalidPositionResult[
            'validation'
        ]['is_valid']
        ?? true
    )
    === false
);


$invalidPositionIssues =
    $invalidPositionResult[
        'validation'
    ]['issues']
    ?? [];


testPass(
    'Incorrect defender count is reported',
    in_array(
        'DEF count must be 5.',
        $invalidPositionIssues,
        true
    )
);


testPass(
    'Incorrect midfielder count is reported',
    in_array(
        'MID count must be 5.',
        $invalidPositionIssues,
        true
    )
);


/*
 * ============================================================
 * SCENARIO G
 * CLUB LIMIT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Club Limit<br>";
echo "============================================<br>";


$clubLimitSquad =
    $validSquad;


/*
 * Force four players onto Team 1.
 */
$clubLimitSquad[1][
    'team_id'
] =
    1;


$clubLimitSquad[3][
    'team_id'
] =
    1;


$clubLimitResult =
    $intelligence
        ->analyzeSquad(
            $clubLimitSquad
        );


testPass(
    'Four players from one club invalidates squad',
    (
        $clubLimitResult[
            'validation'
        ]['is_valid']
        ?? true
    )
    === false
);


$clubLimitIssueFound =
    false;


foreach (
    $clubLimitResult[
        'validation'
    ]['issues']
    ?? []
    as $issue
) {

    if (
        str_contains(
            $issue,
            'exceeds the maximum of 3 players'
        )
    ) {

        $clubLimitIssueFound =
            true;

        break;
    }
}


testPass(
    'Club limit issue is reported',
    $clubLimitIssueFound
);


/*
 * ============================================================
 * SCENARIO H
 * DUPLICATE PLAYER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Duplicate Player<br>";
echo "============================================<br>";


$duplicateSquad =
    $validSquad;


/*
 * Duplicate player 1 into player 2's ID.
 */
$duplicateSquad[1][
    'player_id'
] =
    1;


$duplicateResult =
    $intelligence
        ->analyzeSquad(
            $duplicateSquad
        );


testPass(
    'Duplicate player invalidates squad',
    (
        $duplicateResult[
            'validation'
        ]['is_valid']
        ?? true
    )
    === false
);


$duplicateIssueFound =
    false;


foreach (
    $duplicateResult[
        'validation'
    ]['issues']
    ?? []
    as $issue
) {

    if (
        str_contains(
            $issue,
            'duplicate player ID'
        )
    ) {

        $duplicateIssueFound =
            true;

        break;
    }
}


testPass(
    'Duplicate player issue is reported',
    $duplicateIssueFound
);


/*
 * ============================================================
 * SCENARIO I
 * NEGATIVE BANK
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Negative Bank<br>";
echo "============================================<br>";


$negativeBankResult =
    $intelligence
        ->analyzeSquad(
            $validSquad,
            -1.0
        );


testPass(
    'Negative bank invalidates analysis',
    (
        $negativeBankResult[
            'validation'
        ]['is_valid']
        ?? true
    )
    === false
);


testPass(
    'Negative bank returns no ranking',
    empty(
        $negativeBankResult[
            'ranking'
        ]
        ?? []
    )
);


/*
 * ============================================================
 * SCENARIO J
 * EMPTY SQUAD
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario J: Empty Squad<br>";
echo "============================================<br>";


$emptySquadResult =
    $intelligence
        ->analyzeSquad(
            []
        );


testPass(
    'Empty squad is invalid',
    (
        $emptySquadResult[
            'validation'
        ]['is_valid']
        ?? true
    )
    === false
);


testPass(
    'Empty squad contains zero players',
    (
        $emptySquadResult[
            'validation'
        ]['player_count']
        ?? -1
    )
    === 0
);


testPass(
    'Empty squad returns no ranking',
    empty(
        $emptySquadResult[
            'ranking'
        ]
        ?? []
    )
);


/*
 * ============================================================
 * SCENARIO K
 * CONFIDENCE NORMALISATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario K: Confidence Normalisation<br>";
echo "============================================<br>";


$confidenceSquad =
    $validSquad;


/*
 * Existing application data may contain 0-1 confidence.
 */
$confidenceSquad[0][
    'sample_confidence'
] =
    0.75;


$confidenceResult =
    $intelligence
        ->analyzeSquad(
            $confidenceSquad
        );


$normalizedConfidence =
    null;


foreach (
    $confidenceResult[
        'ranking'
    ]
    ?? []
    as $player
) {

    if (
        (
            $player[
                'player_id'
            ]
            ?? null
        )
        === 1
    ) {

        $normalizedConfidence =
            $player[
                'sample_confidence'
            ]
            ?? null;

        break;
    }
}


testPass(
    '0-1 confidence is normalised to percentage',
    $normalizedConfidence
    === 75.0
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Squad Transfer Intelligence Test Summary<br>";
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