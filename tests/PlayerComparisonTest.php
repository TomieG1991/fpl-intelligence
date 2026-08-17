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


$comparison =
    new PlayerComparison();


echo "============================================<br>";
echo "Player Comparison Test<br>";
echo "============================================<br>";


/*
 * ============================================================
 * SCENARIO A
 * METRIC COMPARISON
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Metric Comparison<br>";
echo "============================================<br>";


$metric =
    $comparison
        ->compareMetric(
            70,
            60
        );


testPass(
    'Higher first value wins',
    $metric['winner']
    === 'a'
);


testPass(
    'Metric difference is calculated',
    $metric['difference']
    === 10.00
);


$metric =
    $comparison
        ->compareMetric(
            50,
            75
        );


testPass(
    'Higher second value wins',
    $metric['winner']
    === 'b'
);


$metric =
    $comparison
        ->compareMetric(
            65,
            65
        );


testPass(
    'Equal values produce a tie',
    $metric['winner']
    === 'tie'
);


/*
 * ============================================================
 * SCENARIO B
 * MISSING VALUES
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Missing Values<br>";
echo "============================================<br>";


$metric =
    $comparison
        ->compareMetric(
            null,
            60
        );


testPass(
    'Available value beats missing value',
    $metric['winner']
    === 'b'
);


$metric =
    $comparison
        ->compareMetric(
            60,
            null
        );


testPass(
    'First available value beats missing second value',
    $metric['winner']
    === 'a'
);


$metric =
    $comparison
        ->compareMetric(
            null,
            null
        );


testPass(
    'Two missing values produce tie',
    $metric['winner']
    === 'tie'
);


/*
 * ============================================================
 * SCENARIO C
 * COMPLETE PROFILE COMPARISON
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Complete Player Comparison<br>";
echo "============================================<br>";


$playerA = [

    'player' => [

        'player_id' => 1,

        'name' => 'Player A',

        'position' => 'MID'
    ],

    'team' => [

        'name' => 'Arsenal'
    ],

    'summary' => [

        'price' => 7.5,

        'strength_rating' => 70.00,

        'value_rating' => 65.00,

        'fixture_rating' => 80.00,

        'availability_rating' => 100.00,

        'intelligence_score' => 72.00
    ],

    'performance' => [

        'sample_confidence' => 1.00
    ],

    'assessment' => [

        'verdict' => 'Strong FPL Option'
    ]
];


$playerB = [

    'player' => [

        'player_id' => 2,

        'name' => 'Player B',

        'position' => 'MID'
    ],

    'team' => [

        'name' => 'Liverpool'
    ],

    'summary' => [

        'price' => 8.0,

        'strength_rating' => 75.00,

        'value_rating' => 55.00,

        'fixture_rating' => 60.00,

        'availability_rating' => 100.00,

        'intelligence_score' => 68.00
    ],

    'performance' => [

        'sample_confidence' => 0.80
    ],

    'assessment' => [

        'verdict' => 'Strong FPL Option'
    ]
];


$result =
    $comparison->compare(
        $playerA,
        $playerB
    );


testPass(
    'Comparison returns an array',
    is_array(
        $result
    )
);


testPass(
    'Player A identity exists',
    isset(
        $result['player_a']
    )
);


testPass(
    'Player B identity exists',
    isset(
        $result['player_b']
    )
);


testPass(
    'Metric comparisons exist',
    isset(
        $result['metrics']
    )
);


testPass(
    'Intelligence comparison exists',
    isset(
        $result[
            'metrics'
        ]['intelligence']
    )
);


testPass(
    'Player A wins intelligence comparison',
    $result[
        'metrics'
    ]['intelligence']['winner']
    === 'a'
);


testPass(
    'Player B wins strength comparison',
    $result[
        'metrics'
    ]['strength']['winner']
    === 'b'
);


testPass(
    'Player A wins value comparison',
    $result[
        'metrics'
    ]['value']['winner']
    === 'a'
);


testPass(
    'Player A wins fixture comparison',
    $result[
        'metrics'
    ]['fixtures']['winner']
    === 'a'
);


testPass(
    'Availability is tied',
    $result[
        'metrics'
    ]['availability']['winner']
    === 'tie'
);


testPass(
    'Player A wins sample confidence comparison',
    $result[
        'metrics'
    ]['sample_confidence']['winner']
    === 'a'
);


testPass(
    'Overall winner follows intelligence score',
    $result['overall_winner']
    === 'a'
);


testPass(
    'Overall intelligence difference is four',
    $result[
        'overall_difference'
    ]
    === 4.00
);


testPass(
    'Player A verdict is preserved',
    $result[
        'player_a_verdict'
    ]
    === 'Strong FPL Option'
);


testPass(
    'Player B verdict is preserved',
    $result[
        'player_b_verdict'
    ]
    === 'Strong FPL Option'
);


/*
 * ============================================================
 * SCENARIO D
 * METRIC WIN COUNTS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Metric Wins<br>";
echo "============================================<br>";


testPass(
    'Player A metric wins are counted',
    $result[
        'metric_wins'
    ]['player_a']
    === 4
);


testPass(
    'Player B metric wins are counted',
    $result[
        'metric_wins'
    ]['player_b']
    === 1
);


testPass(
    'Metric ties are counted',
    $result[
        'metric_wins'
    ]['ties']
    === 1
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Player Comparison Test Summary<br>";
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