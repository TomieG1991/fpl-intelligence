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


/*
 * ============================================================
 * MODEL
 * ============================================================
 */

$ranking =
    new PlayerRanking();


/*
 * ============================================================
 * TEST DATA
 * ============================================================
 */

$players = [

    [
        'player_id' => 1,
        'name' => 'Player Alpha',
        'position' => 'FWD',
        'intelligence_score' => 94.50,
        'intelligence_label' => 'Elite',
        'strength_rating' => 90.00,
        'value_rating' => 100.00,
        'value_label' => 'Exceptional',
        'availability_rating' => 100.00,
        'fixture_rating' => 90.00
    ],

    [
        'player_id' => 2,
        'name' => 'Player Bravo',
        'position' => 'MID',
        'intelligence_score' => 82.00,
        'intelligence_label' => 'Strong',
        'strength_rating' => 85.00,
        'value_rating' => 80.00,
        'value_label' => 'Excellent',
        'availability_rating' => 95.00,
        'fixture_rating' => 70.00
    ],

    [
        'player_id' => 3,
        'name' => 'Player Charlie',
        'position' => 'DEF',
        'intelligence_score' => 76.50,
        'intelligence_label' => 'Strong',
        'strength_rating' => 78.00,
        'value_rating' => 75.00,
        'value_label' => 'Excellent',
        'availability_rating' => 90.00,
        'fixture_rating' => 65.00
    ],

    [
        'player_id' => 4,
        'name' => 'Player Delta',
        'position' => 'MID',
        'intelligence_score' => 61.00,
        'intelligence_label' => 'Average',
        'strength_rating' => 65.00,
        'value_rating' => 60.00,
        'value_label' => 'Good',
        'availability_rating' => 80.00,
        'fixture_rating' => 50.00
    ],

    [
        'player_id' => 5,
        'name' => 'Player Echo',
        'position' => 'GK',
        'intelligence_score' => 45.00,
        'intelligence_label' => 'Below Average',
        'strength_rating' => 50.00,
        'value_rating' => 45.00,
        'value_label' => 'Average',
        'availability_rating' => 70.00,
        'fixture_rating' => 40.00
    ],

    [
        'player_id' => 6,
        'name' => 'Player Foxtrot',
        'position' => 'FWD',
        'intelligence_score' => null,
        'intelligence_label' => 'Unknown',
        'strength_rating' => null,
        'value_rating' => null,
        'value_label' => 'N/A',
        'availability_rating' => 50.00,
        'fixture_rating' => null
    ]
];


/*
 * ============================================================
 * SCENARIO A
 * Basic Ranking
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Basic Player Ranking<br>";
echo "============================================<br>";


$rankedPlayers =
    $ranking->rankPlayers(
        $players
    );


echo "Rank 1: "
    . $rankedPlayers[0]['name']
    . "<br>";

echo "Rank 2: "
    . $rankedPlayers[1]['name']
    . "<br>";

echo "Rank 3: "
    . $rankedPlayers[2]['name']
    . "<br>";


testPass(
    'Players are returned as an array',
    is_array($rankedPlayers)
);

testPass(
    'Highest intelligence player is ranked first',
    $rankedPlayers[0]['player_id'] === 1
);

testPass(
    'Second highest intelligence player is ranked second',
    $rankedPlayers[1]['player_id'] === 2
);

testPass(
    'Third highest intelligence player is ranked third',
    $rankedPlayers[2]['player_id'] === 3
);


/*
 * ============================================================
 * SCENARIO B
 * Ranking Numbers
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Ranking Numbers<br>";
echo "============================================<br>";


echo "Player Alpha Rank: "
    . $rankedPlayers[0]['rank']
    . "<br>";

echo "Player Bravo Rank: "
    . $rankedPlayers[1]['rank']
    . "<br>";

echo "Player Echo Rank: "
    . $rankedPlayers[4]['rank']
    . "<br>";


testPass(
    'Player Alpha receives rank 1',
    $rankedPlayers[0]['rank'] === 1
);

testPass(
    'Player Bravo receives rank 2',
    $rankedPlayers[1]['rank'] === 2
);

testPass(
    'Player Echo receives rank 5',
    $rankedPlayers[4]['rank'] === 5
);


/*
 * ============================================================
 * SCENARIO C
 * Ranking Order
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Ranking Order<br>";
echo "============================================<br>";


$previousScore = 101;

$orderCorrect = true;


foreach (
    $rankedPlayers
    as $player
) {

    echo $player['name']
        . ": "
        . number_format(
            $player['intelligence_score'],
            2
        )
        . "<br>";


    if (
        $player['intelligence_score']
        > $previousScore
    ) {

        $orderCorrect = false;
    }


    $previousScore =
        $player['intelligence_score'];
}


testPass(
    'Players are ordered from highest to lowest intelligence score',
    $orderCorrect
);


/*
 * ============================================================
 * SCENARIO D
 * Missing Intelligence Scores
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Missing Intelligence Scores<br>";
echo "============================================<br>";


$rankedCount =
    count(
        $rankedPlayers
    );


echo "Rankable Players: "
    . $rankedCount
    . "<br>";


testPass(
    'Players without intelligence scores are excluded',
    $rankedCount === 5
);

testPass(
    'Player with missing score is not ranked',
    $ranking->getPlayerRank(
        $players,
        6
    ) === null
);


/*
 * ============================================================
 * SCENARIO E
 * Player Rank Lookup
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Player Rank Lookup<br>";
echo "============================================<br>";


$alphaRank =
    $ranking->getPlayerRank(
        $players,
        1
    );


$charlieRank =
    $ranking->getPlayerRank(
        $players,
        3
    );


$unknownRank =
    $ranking->getPlayerRank(
        $players,
        999
    );


echo "Player Alpha Rank: "
    . $alphaRank
    . "<br>";

echo "Player Charlie Rank: "
    . $charlieRank
    . "<br>";

echo "Unknown Player Rank: "
    . (
        $unknownRank === null
            ? 'NULL'
            : $unknownRank
    )
    . "<br>";


testPass(
    'Player Alpha rank lookup returns 1',
    $alphaRank === 1
);

testPass(
    'Player Charlie rank lookup returns 3',
    $charlieRank === 3
);

testPass(
    'Unknown player returns null rank',
    $unknownRank === null
);


/*
 * ============================================================
 * SCENARIO F
 * Top N Players
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Top N Players<br>";
echo "============================================<br>";


$topThree =
    $ranking->getTopPlayers(
        $players,
        3
    );


echo "Top 3 Count: "
    . count($topThree)
    . "<br>";


foreach (
    $topThree
    as $player
) {

    echo $player['rank']
        . ". "
        . $player['name']
        . " - "
        . number_format(
            $player['intelligence_score'],
            2
        )
        . "<br>";
}


testPass(
    'Top 3 returns exactly three players',
    count($topThree) === 3
);

testPass(
    'Top 3 first player is Player Alpha',
    $topThree[0]['player_id'] === 1
);

testPass(
    'Top 3 last player is Player Charlie',
    $topThree[2]['player_id'] === 3
);


/*
 * ============================================================
 * SCENARIO G
 * Top N Limits
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Top N Limits<br>";
echo "============================================<br>";


$topTen =
    $ranking->getTopPlayers(
        $players,
        10
    );


$topZero =
    $ranking->getTopPlayers(
        $players,
        0
    );


$topNegative =
    $ranking->getTopPlayers(
        $players,
        -5
    );


echo "Top 10 Count: "
    . count($topTen)
    . "<br>";

echo "Top 0 Count: "
    . count($topZero)
    . "<br>";

echo "Top -5 Count: "
    . count($topNegative)
    . "<br>";


testPass(
    'Top 10 returns all available ranked players',
    count($topTen) === 5
);

testPass(
    'Top 0 returns an empty array',
    count($topZero) === 0
);

testPass(
    'Negative limit returns an empty array',
    count($topNegative) === 0
);


/*
 * ============================================================
 * SCENARIO H
 * Tie Breaking
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Intelligence Score Tie Breaking<br>";
echo "============================================<br>";


$tiedPlayers = [

    [
        'player_id' => 10,
        'name' => 'Tie Player A',
        'position' => 'MID',
        'intelligence_score' => 80.00,
        'strength_rating' => 70.00
    ],

    [
        'player_id' => 11,
        'name' => 'Tie Player B',
        'position' => 'MID',
        'intelligence_score' => 80.00,
        'strength_rating' => 85.00
    ]
];


$tiedRankings =
    $ranking->rankPlayers(
        $tiedPlayers
    );


echo "Tie Player A Rank: "
    . $tiedRankings[0]['rank']
    . "<br>";

echo "Tie Player B Rank: "
    . $tiedRankings[1]['rank']
    . "<br>";


testPass(
    'Higher strength rating wins an intelligence score tie',
    $tiedRankings[0]['player_id'] === 11
);

testPass(
    'Lower strength rating follows in intelligence score tie',
    $tiedRankings[1]['player_id'] === 10
);


/*
 * ============================================================
 * SCENARIO I
 * Empty Ranking
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Empty Ranking<br>";
echo "============================================<br>";


$emptyRanking =
    $ranking->rankPlayers(
        []
    );


$emptyTopPlayers =
    $ranking->getTopPlayers(
        [],
        10
    );


$emptyCount =
    $ranking->getRankedPlayerCount(
        []
    );


echo "Ranked Players: "
    . count($emptyRanking)
    . "<br>";

echo "Top Players: "
    . count($emptyTopPlayers)
    . "<br>";

echo "Ranked Player Count: "
    . $emptyCount
    . "<br>";


testPass(
    'Empty player list produces empty ranking',
    count($emptyRanking) === 0
);

testPass(
    'Empty player list produces empty top-player list',
    count($emptyTopPlayers) === 0
);

testPass(
    'Empty player list produces zero ranked players',
    $emptyCount === 0
);


/*
 * ============================================================
 * SCENARIO J
 * Ranked Player Count
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Ranked Player Count<br>";
echo "============================================<br>";


$count =
    $ranking->getRankedPlayerCount(
        $players
    );


echo "Ranked Player Count: "
    . $count
    . "<br>";


testPass(
    'Ranked player count returns five rankable players',
    $count === 5
);

/*
 * ============================================================
 * SCENARIO K
 * PlayerIntelligenceEngine Summary Integration
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Player Intelligence Engine Summary Integration<br>";
echo "============================================<br>";


$enginePlayers = [

    [
        'player' => [
            'player_id' => 101,
            'fpl_player_id' => 1001,
            'team_id' => 1,
            'name' => 'Engine Player Alpha',
            'position' => 'FWD'
        ],

        'summary' => [
            'player_id' => 101,
            'fpl_player_id' => 1001,
            'team_id' => 1,
            'name' => 'Engine Player Alpha',
            'position' => 'FWD',
            'price' => 12.0,
            'strength_rating' => 92.00,
            'value_rating' => 88.00,
            'value_label' => 'Excellent',
            'availability_rating' => 100.00,
            'reliability_rating' => 98.00,
            'availability_label' => 'Available',
            'fixture_rating' => 90.00,
            'intelligence_score' => 94.00,
            'intelligence_label' => 'Elite'
        ]
    ],

    [
        'player' => [
            'player_id' => 102,
            'fpl_player_id' => 1002,
            'team_id' => 2,
            'name' => 'Engine Player Bravo',
            'position' => 'MID'
        ],

        'summary' => [
            'player_id' => 102,
            'fpl_player_id' => 1002,
            'team_id' => 2,
            'name' => 'Engine Player Bravo',
            'position' => 'MID',
            'price' => 8.0,
            'strength_rating' => 85.00,
            'value_rating' => 82.00,
            'value_label' => 'Excellent',
            'availability_rating' => 95.00,
            'reliability_rating' => 92.00,
            'availability_label' => 'Available',
            'fixture_rating' => 75.00,
            'intelligence_score' => 84.00,
            'intelligence_label' => 'Strong'
        ]
    ],

    [
        'player' => [
            'player_id' => 103,
            'fpl_player_id' => 1003,
            'team_id' => 3,
            'name' => 'Engine Player Charlie',
            'position' => 'DEF'
        ],

        'summary' => [
            'player_id' => 103,
            'fpl_player_id' => 1003,
            'team_id' => 3,
            'name' => 'Engine Player Charlie',
            'position' => 'DEF',
            'price' => 5.5,
            'strength_rating' => 78.00,
            'value_rating' => 80.00,
            'value_label' => 'Excellent',
            'availability_rating' => 90.00,
            'reliability_rating' => 88.00,
            'availability_label' => 'Available',
            'fixture_rating' => 65.00,
            'intelligence_score' => 76.00,
            'intelligence_label' => 'Strong'
        ]
    ]
];


$engineRankings =
    $ranking->rankPlayers(
        $enginePlayers
    );


echo "Engine Rank 1: "
    . $engineRankings[0]['name']
    . "<br>";

echo "Engine Rank 2: "
    . $engineRankings[1]['name']
    . "<br>";

echo "Engine Rank 3: "
    . $engineRankings[2]['name']
    . "<br>";


testPass(
    'Engine profiles can be ranked',
    count($engineRankings) === 3
);


testPass(
    'Engine summary is extracted automatically',
    $engineRankings[0]['player_id'] === 101
);


testPass(
    'Highest engine intelligence score ranks first',
    $engineRankings[0]['name']
        === 'Engine Player Alpha'
);


testPass(
    'Second engine intelligence score ranks second',
    $engineRankings[1]['name']
        === 'Engine Player Bravo'
);


testPass(
    'Third engine intelligence score ranks third',
    $engineRankings[2]['name']
        === 'Engine Player Charlie'
);


testPass(
    'Engine summary rank is added correctly',
    $engineRankings[0]['rank'] === 1
);


testPass(
    'Engine summary intelligence score is preserved',
    $engineRankings[0]['intelligence_score'] === 94.00
);


testPass(
    'Engine summary strength rating is preserved',
    $engineRankings[0]['strength_rating'] === 92.00
);


testPass(
    'Engine summary value rating is preserved',
    $engineRankings[0]['value_rating'] === 88.00
);


testPass(
    'Engine summary availability rating is preserved',
    $engineRankings[0]['availability_rating'] === 100.00
);


testPass(
    'Engine summary fixture rating is preserved',
    $engineRankings[0]['fixture_rating'] === 90.00
);


$enginePlayerRank =
    $ranking->getPlayerRank(
        $enginePlayers,
        102
    );


testPass(
    'Engine profile player rank lookup works',
    $enginePlayerRank === 2
);


$engineTopTwo =
    $ranking->getTopPlayers(
        $enginePlayers,
        2
    );


testPass(
    'Top-player selection works with engine profiles',
    count($engineTopTwo) === 2
);


testPass(
    'Top-player selection returns correct engine player',
    $engineTopTwo[0]['player_id'] === 101
);


/*
 * ============================================================
 * FRONT-END FRIENDLY OUTPUT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Front-End Friendly Player Ranking<br>";
echo "============================================<br>";


echo "Top Ranked Player: "
    . $rankedPlayers[0]['name']
    . "<br>";

echo "Position: "
    . $rankedPlayers[0]['position']
    . "<br>";

echo "Rank: "
    . $rankedPlayers[0]['rank']
    . "<br>";

echo "Intelligence Score: "
    . number_format(
        $rankedPlayers[0]['intelligence_score'],
        2
    )
    . " / 100<br>";

echo "Intelligence Label: "
    . $rankedPlayers[0]['intelligence_label']
    . "<br>";

echo "Strength Rating: "
    . number_format(
        $rankedPlayers[0]['strength_rating'],
        2
    )
    . " / 100<br>";

echo "Value Rating: "
    . number_format(
        $rankedPlayers[0]['value_rating'],
        2
    )
    . " / 100<br>";

echo "Value Label: "
    . $rankedPlayers[0]['value_label']
    . "<br>";

echo "Availability: "
    . number_format(
        $rankedPlayers[0]['availability_rating'],
        2
    )
    . " / 100<br>";

echo "Fixture Rating: "
    . number_format(
        $rankedPlayers[0]['fixture_rating'],
        2
    )
    . " / 100<br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Ranking Test Summary<br>";
echo "============================================<br>";


echo "Passed: {$passed}<br>";
echo "Failed: {$failed}<br>";


if ($failed === 0) {

    echo "<br>RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "<br>RESULT: TESTS FAILED ❌<br>";
}