<?php

require_once '../classes/autoload.php';


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

$passed = 0;
$failed = 0;


function testPass(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;

    if ($condition) {

        echo "PASS: {$description}<br>";

        $passed++;

    } else {

        echo "FAIL: {$description}<br>";

        $failed++;
    }
}


function section(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";
    echo $title . "<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * MODEL
 * ============================================================
 */

$finder =
    new TransferTargetFinder();


/*
 * ============================================================
 * TEST PLAYERS
 * ============================================================
 */

$currentPlayer = [

    'player_id' => 1,
    'fpl_player_id' => 1001,
    'team_id' => 1,

    'name' => 'Current Player',
    'position' => 'FWD',

    'price' => 6.0,

    'intelligence_score' => 70.00,
    'intelligence_label' => 'Strong',

    'strength_rating' => 70.00,
    'value_rating' => 70.00,
    'availability_rating' => 100.00,
    'fixture_rating' => 70.00
];


$strongReplacement = [

    'player_id' => 2,
    'fpl_player_id' => 1002,
    'team_id' => 2,

    'name' => 'Strong Replacement',
    'position' => 'FWD',

    'price' => 6.0,

    'intelligence_score' => 90.00,
    'intelligence_label' => 'Elite',

    'strength_rating' => 90.00,
    'value_rating' => 100.00,
    'availability_rating' => 100.00,
    'fixture_rating' => 95.00
];


$goodReplacement = [

    'player_id' => 3,
    'fpl_player_id' => 1003,
    'team_id' => 3,

    'name' => 'Good Replacement',
    'position' => 'FWD',

    'price' => 5.5,

    'intelligence_score' => 82.00,
    'intelligence_label' => 'Strong',

    'strength_rating' => 82.00,
    'value_rating' => 85.00,
    'availability_rating' => 100.00,
    'fixture_rating' => 85.00
];


$considerReplacement = [

    'player_id' => 4,
    'fpl_player_id' => 1004,
    'team_id' => 4,

    'name' => 'Consider Replacement',
    'position' => 'FWD',

    'price' => 5.0,

    'intelligence_score' => 75.00,
    'intelligence_label' => 'Strong',

    'strength_rating' => 75.00,
    'value_rating' => 80.00,
    'availability_rating' => 100.00,
    'fixture_rating' => 75.00
];


$defenderReplacement = [

    'player_id' => 5,
    'fpl_player_id' => 1005,
    'team_id' => 5,

    'name' => 'Defender Replacement',
    'position' => 'DEF',

    'price' => 5.0,

    'intelligence_score' => 95.00,
    'intelligence_label' => 'Elite',

    'strength_rating' => 95.00,
    'value_rating' => 95.00,
    'availability_rating' => 100.00,
    'fixture_rating' => 95.00
];


$expensiveReplacement = [

    'player_id' => 6,
    'fpl_player_id' => 1006,
    'team_id' => 6,

    'name' => 'Expensive Replacement',
    'position' => 'FWD',

    'price' => 7.5,

    'intelligence_score' => 99.00,
    'intelligence_label' => 'Elite',

    'strength_rating' => 99.00,
    'value_rating' => 99.00,
    'availability_rating' => 100.00,
    'fixture_rating' => 99.00
];


$unavailableReplacement = [

    'player_id' => 7,
    'fpl_player_id' => 1007,
    'team_id' => 7,

    'name' => 'Unavailable Replacement',
    'position' => 'FWD',

    'price' => 5.5,

    'intelligence_score' => 95.00,
    'intelligence_label' => 'Elite',

    'strength_rating' => 95.00,
    'value_rating' => 95.00,
    'availability_rating' => 0.00,
    'fixture_rating' => 95.00,

    'status' => 'i'
];


$missingIntelligence = [

    'player_id' => 8,
    'fpl_player_id' => 1008,
    'team_id' => 8,

    'name' => 'Missing Intelligence',
    'position' => 'FWD',

    'price' => 5.0,

    'intelligence_score' => null,

    'strength_rating' => 80.00,
    'value_rating' => 80.00,
    'availability_rating' => 100.00,
    'fixture_rating' => 80.00
];


$allPlayers = [

    $currentPlayer,
    $strongReplacement,
    $goodReplacement,
    $considerReplacement,
    $defenderReplacement,
    $expensiveReplacement,
    $unavailableReplacement,
    $missingIntelligence
];


/*
 * ============================================================
 * SCENARIO A
 * Basic Target Finder
 * ============================================================
 */

section(
    'Scenario A: Basic Target Finder'
);


$targets =
    $finder->findTargets(
        $currentPlayer,
        $allPlayers
    );


testPass(
    'Transfer targets are returned as an array',
    is_array($targets)
);


testPass(
    'Current player is excluded from targets',
    !in_array(
        1,
        array_column(
            $targets,
            'player_id'
        ),
        true
    )
);


/*
 * ============================================================
 * SCENARIO B
 * Position Filtering
 * ============================================================
 */

section(
    'Scenario B: Position Filtering'
);


$targetIds =
    array_column(
        $targets,
        'player_id'
    );


testPass(
    'Matching-position players are included',
    in_array(
        2,
        $targetIds,
        true
    )
);


testPass(
    'Different-position players are excluded',
    !in_array(
        5,
        $targetIds,
        true
    )
);


/*
 * ============================================================
 * SCENARIO C
 * Budget Filtering
 * ============================================================
 */

section(
    'Scenario C: Budget Filtering'
);


$budgetTargets =
    $finder->findTargets(
        $currentPlayer,
        $allPlayers,
        6.0
    );


$budgetTargetIds =
    array_column(
        $budgetTargets,
        'player_id'
    );


testPass(
    'Players within budget are included',
    in_array(
        2,
        $budgetTargetIds,
        true
    )
);


testPass(
    'Players above budget are excluded',
    !in_array(
        6,
        $budgetTargetIds,
        true
    )
);


/*
 * ============================================================
 * SCENARIO D
 * Intelligence Filtering
 * ============================================================
 */

section(
    'Scenario D: Intelligence Filtering'
);


testPass(
    'Players with intelligence scores are included',
    in_array(
        2,
        $targetIds,
        true
    )
);


testPass(
    'Players without intelligence scores are excluded',
    !in_array(
        8,
        $targetIds,
        true
    )
);


/*
 * ============================================================
 * SCENARIO E
 * Availability Filtering
 * ============================================================
 */

section(
    'Scenario E: Availability Filtering'
);


testPass(
    'Available replacement players are included',
    in_array(
        2,
        $targetIds,
        true
    )
);


testPass(
    'Unavailable players are excluded',
    !in_array(
        7,
        $targetIds,
        true
    )
);


/*
 * ============================================================
 * SCENARIO F
 * Transfer Recommendation Integration
 * ============================================================
 */

section(
    'Scenario F: Transfer Recommendation Integration'
);


$strongTarget =
    $targets[0] ?? null;


testPass(
    'Target contains transfer score',
    isset(
        $strongTarget['transfer_score']
    )
);


testPass(
    'Target contains transfer recommendation',
    isset(
        $strongTarget['recommendation']
    )
);


testPass(
    'Target contains transfer reason',
    isset(
        $strongTarget['reason']
    )
);


/*
 * ============================================================
 * SCENARIO G
 * Target Ordering
 * ============================================================
 */

section(
    'Scenario G: Target Ordering'
);


$previousScore =
    null;


$orderCorrect =
    true;


foreach ($targets as $target) {

    $score =
        $target['transfer_score']
        ?? null;


    if ($score === null) {
        continue;
    }


    if (
        $previousScore !== null
        &&
        $score > $previousScore
    ) {

        $orderCorrect =
            false;

        break;
    }


    $previousScore =
        $score;
}


testPass(
    'Targets are ordered from highest transfer score to lowest',
    $orderCorrect
);


/*
 * ============================================================
 * SCENARIO H
 * Top N Targets
 * ============================================================
 */

section(
    'Scenario H: Top N Targets'
);


$topThree =
    $finder->getTopTargets(
        $targets,
        3
    );


echo "Top 3 Count: "
    . count($topThree)
    . "<br>";


testPass(
    'Top 3 returns no more than three targets',
    count($topThree) <= 3
);


$topTen =
    $finder->getTopTargets(
        $targets,
        10
    );


testPass(
    'Top 10 returns all available targets when fewer than 10 exist',
    count($topTen) === count($targets)
);


$topZero =
    $finder->getTopTargets(
        $targets,
        0
    );


testPass(
    'Top 0 returns an empty array',
    count($topZero) === 0
);


$topNegative =
    $finder->getTopTargets(
        $targets,
        -5
    );


testPass(
    'Negative limit returns an empty array',
    count($topNegative) === 0
);


/*
 * ============================================================
 * SCENARIO I
 * Empty Candidate Pool
 * ============================================================
 */

section(
    'Scenario I: Empty Candidate Pool'
);


$emptyTargets =
    $finder->findTargets(
        $currentPlayer,
        []
    );


echo "Targets Found: "
    . count($emptyTargets)
    . "<br>";


testPass(
    'Empty player list produces empty targets',
    $emptyTargets === []
);


/*
 * ============================================================
 * SCENARIO J
 * Identity Preservation
 * ============================================================
 */

section(
    'Scenario J: Target Identity'
);


$topTarget =
    $targets[0] ?? [];


testPass(
    'Target player ID is preserved',
    isset(
        $topTarget['player_id']
    )
);


testPass(
    'Target player name is preserved',
    isset(
        $topTarget['name']
    )
);


testPass(
    'Target position is preserved',
    isset(
        $topTarget['position']
    )
);


/*
 * ============================================================
 * SCENARIO K
 * Complete Target Model
 * ============================================================
 */

section(
    'Scenario K: Complete Transfer Target Model'
);


testPass(
    'Target contains intelligence score',
    array_key_exists(
        'intelligence_score',
        $topTarget
    )
);


testPass(
    'Target contains strength rating',
    array_key_exists(
        'strength_rating',
        $topTarget
    )
);


testPass(
    'Target contains value rating',
    array_key_exists(
        'value_rating',
        $topTarget
    )
);


testPass(
    'Target contains availability rating',
    array_key_exists(
        'availability_rating',
        $topTarget
    )
);


testPass(
    'Target contains fixture rating',
    array_key_exists(
        'fixture_rating',
        $topTarget
    )
);

/*
 * ============================================================
 * SCENARIO L
 * PlayerIntelligenceEngine Summary Integration
 * ============================================================
 */

section(
    'Scenario L: Player Intelligence Engine Summary Integration'
);


$currentEngineProfile = [

    'player' => [
        'player_id' => 201,
        'fpl_player_id' => 2001,
        'team_id' => 1,
        'name' => 'Engine Current Forward',
        'position' => 'FWD'
    ],

    'summary' => [
        'player_id' => 201,
        'fpl_player_id' => 2001,
        'team_id' => 1,
        'name' => 'Engine Current Forward',
        'position' => 'FWD',
        'price' => 6.0,
        'strength_rating' => 70.00,
        'value_rating' => 70.00,
        'value_label' => 'Good',
        'availability_rating' => 100.00,
        'reliability_rating' => 95.00,
        'availability_label' => 'Available',
        'fixture_rating' => 70.00,
        'intelligence_score' => 70.00,
        'intelligence_label' => 'Strong'
    ]
];


$engineStrongReplacement = [

    'player' => [
        'player_id' => 202,
        'fpl_player_id' => 2002,
        'team_id' => 2,
        'name' => 'Engine Strong Replacement',
        'position' => 'FWD'
    ],

    'summary' => [
        'player_id' => 202,
        'fpl_player_id' => 2002,
        'team_id' => 2,
        'name' => 'Engine Strong Replacement',
        'position' => 'FWD',
        'price' => 6.0,
        'strength_rating' => 90.00,
        'value_rating' => 100.00,
        'value_label' => 'Exceptional',
        'availability_rating' => 100.00,
        'reliability_rating' => 98.00,
        'availability_label' => 'Available',
        'fixture_rating' => 95.00,
        'intelligence_score' => 90.00,
        'intelligence_label' => 'Elite'
    ]
];


$engineGoodReplacement = [

    'player' => [
        'player_id' => 203,
        'fpl_player_id' => 2003,
        'team_id' => 3,
        'name' => 'Engine Good Replacement',
        'position' => 'FWD'
    ],

    'summary' => [
        'player_id' => 203,
        'fpl_player_id' => 2003,
        'team_id' => 3,
        'name' => 'Engine Good Replacement',
        'position' => 'FWD',
        'price' => 5.5,
        'strength_rating' => 82.00,
        'value_rating' => 85.00,
        'value_label' => 'Excellent',
        'availability_rating' => 100.00,
        'reliability_rating' => 96.00,
        'availability_label' => 'Available',
        'fixture_rating' => 85.00,
        'intelligence_score' => 82.00,
        'intelligence_label' => 'Strong'
    ]
];


$engineDefender = [

    'player' => [
        'player_id' => 204,
        'fpl_player_id' => 2004,
        'team_id' => 4,
        'name' => 'Engine Defender',
        'position' => 'DEF'
    ],

    'summary' => [
        'player_id' => 204,
        'fpl_player_id' => 2004,
        'team_id' => 4,
        'name' => 'Engine Defender',
        'position' => 'DEF',
        'price' => 5.0,
        'strength_rating' => 95.00,
        'value_rating' => 95.00,
        'value_label' => 'Exceptional',
        'availability_rating' => 100.00,
        'reliability_rating' => 98.00,
        'availability_label' => 'Available',
        'fixture_rating' => 95.00,
        'intelligence_score' => 95.00,
        'intelligence_label' => 'Elite'
    ]
];


$engineExpensiveReplacement = [

    'player' => [
        'player_id' => 205,
        'fpl_player_id' => 2005,
        'team_id' => 5,
        'name' => 'Engine Expensive Replacement',
        'position' => 'FWD'
    ],

    'summary' => [
        'player_id' => 205,
        'fpl_player_id' => 2005,
        'team_id' => 5,
        'name' => 'Engine Expensive Replacement',
        'position' => 'FWD',
        'price' => 7.5,
        'strength_rating' => 99.00,
        'value_rating' => 99.00,
        'value_label' => 'Exceptional',
        'availability_rating' => 100.00,
        'reliability_rating' => 99.00,
        'availability_label' => 'Available',
        'fixture_rating' => 99.00,
        'intelligence_score' => 99.00,
        'intelligence_label' => 'Elite'
    ]
];


$enginePlayers = [

    $currentEngineProfile,
    $engineStrongReplacement,
    $engineGoodReplacement,
    $engineDefender,
    $engineExpensiveReplacement
];


$engineTargets =
    $finder->findTargets(
        $currentEngineProfile,
        $enginePlayers
    );


$engineTargetIds =
    array_column(
        $engineTargets,
        'player_id'
    );


echo "Engine Targets Found: "
    . count($engineTargets)
    . "<br>";


testPass(
    'Engine profiles produce transfer targets',
    is_array($engineTargets)
    &&
    count($engineTargets) > 0
);


testPass(
    'Engine current player is excluded',
    !in_array(
        201,
        $engineTargetIds,
        true
    )
);


testPass(
    'Engine same-position replacement is included',
    in_array(
        202,
        $engineTargetIds,
        true
    )
);


testPass(
    'Engine different-position player is excluded',
    !in_array(
        204,
        $engineTargetIds,
        true
    )
);


testPass(
    'Engine default budget excludes expensive replacement',
    !in_array(
        205,
        $engineTargetIds,
        true
    )
);


$topEngineTarget =
    $engineTargets[0] ?? [];


testPass(
    'Engine top target identity is preserved',
    ($topEngineTarget['player_id'] ?? null) === 202
);


testPass(
    'Engine top target name is preserved',
    ($topEngineTarget['name'] ?? null)
        === 'Engine Strong Replacement'
);


testPass(
    'Engine top target contains intelligence score',
    array_key_exists(
        'intelligence_score',
        $topEngineTarget
    )
);


testPass(
    'Engine top target contains transfer score',
    array_key_exists(
        'transfer_score',
        $topEngineTarget
    )
);


testPass(
    'Engine top target contains recommendation',
    array_key_exists(
        'recommendation',
        $topEngineTarget
    )
);


testPass(
    'Engine top target contains reason',
    array_key_exists(
        'reason',
        $topEngineTarget
    )
);


testPass(
    'Engine top target receives positive transfer score',
    isset($topEngineTarget['transfer_score'])
    &&
    $topEngineTarget['transfer_score'] > 0
);


testPass(
    'Engine targets are ordered by transfer score',
    count($engineTargets) < 2
    ||
    $engineTargets[0]['transfer_score']
        >=
       $engineTargets[1]['transfer_score']
);


$engineTopOne =
    $finder->getTopTargets(
        $engineTargets,
        1
    );


testPass(
    'Top target selection works with engine profiles',
    count($engineTopOne) === 1
);


testPass(
    'Top target selection returns correct engine player',
    $engineTopOne[0]['player_id'] === 202
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

section(
    'Front-End Friendly Transfer Targets'
);


echo "Current Player: "
    . $currentPlayer['name']
    . "<br>";


echo "Position: "
    . $currentPlayer['position']
    . "<br>";


echo "Current Price: £"
    . number_format(
        $currentPlayer['price'],
        1
    )
    . "m<br>";


echo "<br>";


foreach (
    $finder->getTopTargets(
        $targets,
        5
    )
    as $index => $target
) {

    echo "Rank "
        . ($index + 1)
        . ": "
        . $target['name']
        . "<br>";


    echo "Price: £"
        . number_format(
            $target['price'],
            1
        )
        . "m<br>";


    echo "Intelligence: "
        . number_format(
            $target['intelligence_score'],
            2
        )
        . " / 100<br>";


    echo "Transfer Score: "
        . number_format(
            $target['transfer_score'],
            2
        )
        . " / 100<br>";


    echo "Recommendation: "
        . $target['recommendation']
        . "<br>";


    echo "Reason: "
        . $target['reason']
        . "<br>";


    echo "<br>";
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

section(
    'Transfer Target Finder Test Summary'
);


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}