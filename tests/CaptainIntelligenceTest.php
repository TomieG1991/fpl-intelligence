<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Captain Intelligence Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function captainTest(
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


$captainIntelligence =
    new CaptainIntelligence();


/*
 * ============================================================
 * SCENARIO A: STRONG CAPTAIN
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Strong Captain<br>";
echo "============================================<br>";


$strongPlayer = [

    'player_id' =>
        1,

    'name' =>
        'Elite Forward',

    'position' =>
        'FWD',

    'strength_score' =>
        85.0,

    'fixture_score' =>
        90.0,

    'goals_rating' =>
        90.0,

    'assists_rating' =>
        75.0,

    'expected_goals_rating' =>
        88.0,

    'expected_assists_rating' =>
        80.0,

    'sample_confidence' =>
        100.0,

    'availability' =>
        100.0
];


$strongResult =
    $captainIntelligence
        ->evaluate(
            $strongPlayer
        );


captainTest(
    'Strong player returns success',
    (
        $strongResult[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


captainTest(
    'Captain Score is numeric',
    is_numeric(
        $strongResult[
            'captain_score'
        ]
        ?? null
    )
);


captainTest(
    'Strong player produces high Captain Score',
    (
        $strongResult[
            'captain_score'
        ]
        ?? 0
    )
    >= 80.0
);


captainTest(
    'Strong player is classified Elite Captain',
    (
        $strongResult[
            'classification'
        ]
        ?? null
    )
    ===
    'Elite Captain'
);

$classificationMethod =
    new ReflectionMethod(
        CaptainIntelligence::class,
        'classify'
    );


$classificationMethod
    ->setAccessible(
        true
    );


captainTest(
    '65 Captain Score is classified Elite Captain',
    $classificationMethod
        ->invoke(
            $captainIntelligence,
            65.0
        )
    ===
    'Elite Captain'
);


captainTest(
    '60 Captain Score is classified Strong Captain',
    $classificationMethod
        ->invoke(
            $captainIntelligence,
            60.0
        )
    ===
    'Strong Captain'
);


captainTest(
    '55 Captain Score is classified Good Option',
    $classificationMethod
        ->invoke(
            $captainIntelligence,
            55.0
        )
    ===
    'Good Option'
);


captainTest(
    '50 Captain Score is classified Differential',
    $classificationMethod
        ->invoke(
            $captainIntelligence,
            50.0
        )
    ===
    'Differential'
);


captainTest(
    'Captain Score below 50 is classified Avoid',
    $classificationMethod
        ->invoke(
            $captainIntelligence,
            49.99
        )
    ===
    'Avoid'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO B: FIXTURE IMPACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Fixture Impact<br>";
echo "============================================<br>";


$goodFixturePlayer =
    $strongPlayer;


$goodFixturePlayer[
    'player_id'
] =
    2;


$goodFixturePlayer[
    'fixture_score'
] =
    90.0;


$badFixturePlayer =
    $goodFixturePlayer;


$badFixturePlayer[
    'player_id'
] =
    3;


$badFixturePlayer[
    'fixture_score'
] =
    30.0;


$goodFixtureResult =
    $captainIntelligence
        ->evaluate(
            $goodFixturePlayer
        );


$badFixtureResult =
    $captainIntelligence
        ->evaluate(
            $badFixturePlayer
        );


captainTest(
    'Better fixture produces higher Captain Score',
    (
        $goodFixtureResult[
            'captain_score'
        ]
        ?? 0
    )
    >
    (
        $badFixtureResult[
            'captain_score'
        ]
        ?? 0
    )
);

captainTest(
    'Raw fixture score is preserved',
    (
        $goodFixtureResult[
            'components'
        ][
            'raw_fixture'
        ]
        ?? null
    )
    === 90.0
);


captainTest(
    'Captain fixture score is compressed toward neutral',
    (
        $goodFixtureResult[
            'components'
        ][
            'fixture'
        ]
        ?? 100
    )
    <
    (
        $goodFixtureResult[
            'components'
        ][
            'raw_fixture'
        ]
        ?? 0
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C: ATTACKING THREAT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Attacking Threat<br>";
echo "============================================<br>";


$highThreat =
    $strongPlayer;


$highThreat[
    'player_id'
] =
    4;


$highThreat[
    'goals_rating'
] =
    90.0;


$highThreat[
    'assists_rating'
] =
    90.0;


$highThreat[
    'expected_goals_rating'
] =
    90.0;


$highThreat[
    'expected_assists_rating'
] =
    90.0;


$lowThreat =
    $highThreat;


$lowThreat[
    'player_id'
] =
    5;


$lowThreat[
    'goals_rating'
] =
    20.0;


$lowThreat[
    'assists_rating'
] =
    20.0;


$lowThreat[
    'expected_goals_rating'
] =
    20.0;


$lowThreat[
    'expected_assists_rating'
] =
    20.0;


$highThreatResult =
    $captainIntelligence
        ->evaluate(
            $highThreat
        );


$lowThreatResult =
    $captainIntelligence
        ->evaluate(
            $lowThreat
        );


captainTest(
    'Higher attacking threat produces higher Captain Score',
    (
        $highThreatResult[
            'captain_score'
        ]
        ?? 0
    )
    >
    (
        $lowThreatResult[
            'captain_score'
        ]
        ?? 0
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D: CONFIDENCE NORMALISATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Confidence Normalisation<br>";
echo "============================================<br>";


$confidencePlayer =
    $strongPlayer;


$confidencePlayer[
    'sample_confidence'
] =
    1.0;


$confidenceResult =
    $captainIntelligence
        ->evaluate(
            $confidencePlayer
        );


captainTest(
    '0-1 confidence is normalised to percentage',
    (
        $confidenceResult[
            'components'
        ][
            'confidence'
        ]
        ?? 0
    )
    === 100.0
);

captainTest(
    'Full confidence produces neutral confidence modifier',
    (
        $confidenceResult[
            'components'
        ][
            'confidence_modifier'
        ]
        ?? null
    )
    === 1.0
);

$lowConfidencePlayer =
    $strongPlayer;


$lowConfidencePlayer[
    'player_id'
] =
    10;


$lowConfidencePlayer[
    'sample_confidence'
] =
    20.0;


$lowConfidenceResult =
    $captainIntelligence
        ->evaluate(
            $lowConfidencePlayer
        );


captainTest(
    'Low confidence reduces Captain Score',
    (
        $lowConfidenceResult[
            'captain_score'
        ]
        ?? 0
    )
    <
    (
        $strongResult[
            'captain_score'
        ]
        ?? 0
    )
);


captainTest(
    'Low confidence produces a modifier below one',
    (
        $lowConfidenceResult[
            'components'
        ][
            'confidence_modifier'
        ]
        ?? 1
    )
    <
    1.0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E: AVAILABILITY IMPACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Availability Impact<br>";
echo "============================================<br>";


$fitPlayer =
    $strongPlayer;


$fitPlayer[
    'availability'
] =
    100.0;


$injuryRisk =
    $fitPlayer;


$injuryRisk[
    'player_id'
] =
    6;


$injuryRisk[
    'availability'
] =
    25.0;


$fitResult =
    $captainIntelligence
        ->evaluate(
            $fitPlayer
        );


$injuryResult =
    $captainIntelligence
        ->evaluate(
            $injuryRisk
        );


captainTest(
    'Lower availability reduces Captain Score',
    (
        $injuryResult[
            'captain_score'
        ]
        ?? 0
    )
    <
    (
        $fitResult[
            'captain_score'
        ]
        ?? 0
    )
);

captainTest(
    'Full availability produces neutral availability modifier',
    (
        $fitResult[
            'components'
        ][
            'availability_modifier'
        ]
        ?? null
    )
    === 1.0
);


captainTest(
    'Availability concern produces modifier below one',
    (
        $injuryResult[
            'components'
        ][
            'availability_modifier'
        ]
        ?? 1
    )
    <
    1.0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F: POSITION HANDLING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Position Handling<br>";
echo "============================================<br>";


$forward =
    $strongPlayer;


$forward[
    'player_id'
] =
    7;


$forward[
    'position'
] =
    'FWD';


$defender =
    $forward;


$defender[
    'player_id'
] =
    8;


$defender[
    'position'
] =
    'DEF';


$goalkeeper =
    $forward;


$goalkeeper[
    'player_id'
] =
    9;


$goalkeeper[
    'position'
] =
    'GK';


$forwardResult =
    $captainIntelligence
        ->evaluate(
            $forward
        );


$defenderResult =
    $captainIntelligence
        ->evaluate(
            $defender
        );


$goalkeeperResult =
    $captainIntelligence
        ->evaluate(
            $goalkeeper
        );


captainTest(
    'Forward returns valid attacking threat',
    is_numeric(
        $forwardResult[
            'components'
        ][
            'attacking_threat'
        ]
        ?? null
    )
);


captainTest(
    'Defender returns valid attacking threat',
    is_numeric(
        $defenderResult[
            'components'
        ][
            'attacking_threat'
        ]
        ?? null
    )
);


captainTest(
    'Goalkeeper attacking threat is lower than forward threat',
    (
        $goalkeeperResult[
            'components'
        ][
            'attacking_threat'
        ]
        ?? 0
    )
    <
    (
        $forwardResult[
            'components'
        ][
            'attacking_threat'
        ]
        ?? 0
    )
);


/*
 * ============================================================
 * SCENARIO G: INVALID INPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Invalid Input<br>";
echo "============================================<br>";


$invalidPlayer =
    $strongPlayer;


unset(
    $invalidPlayer[
        'strength_score'
    ]
);


$invalidResult =
    $captainIntelligence
        ->evaluate(
            $invalidPlayer
        );


captainTest(
    'Missing strength returns invalid result',
    (
        $invalidResult[
            'status'
        ]
        ?? null
    )
    ===
    'invalid'
);


captainTest(
    'Invalid result has no Captain Score',
    (
        $invalidResult[
            'captain_score'
        ]
        ?? null
    )
    === null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H: SCORE BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Score Bounds<br>";
echo "============================================<br>";


$extremePlayer =
    $strongPlayer;


$extremePlayer[
    'strength_score'
] =
    500.0;


$extremePlayer[
    'fixture_score'
] =
    500.0;


$extremePlayer[
    'goals_rating'
] =
    500.0;


$extremePlayer[
    'assists_rating'
] =
    500.0;


$extremePlayer[
    'expected_goals_rating'
] =
    500.0;


$extremePlayer[
    'expected_assists_rating'
] =
    500.0;


$extremePlayer[
    'sample_confidence'
] =
    500.0;


$extremePlayer[
    'availability'
] =
    500.0;


$extremeResult =
    $captainIntelligence
        ->evaluate(
            $extremePlayer
        );


captainTest(
    'Captain Score is capped at 100',
    (
        $extremeResult[
            'captain_score'
        ]
        ?? 0
    )
    <= 100.0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I: COMPONENT OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Component Output<br>";
echo "============================================<br>";


captainTest(
    'Strength component is returned',
    isset(
        $strongResult[
            'components'
        ][
            'strength'
        ]
    )
);


captainTest(
    'Fixture component is returned',
    isset(
        $strongResult[
            'components'
        ][
            'fixture'
        ]
    )
);


captainTest(
    'Attacking Threat component is returned',
    isset(
        $strongResult[
            'components'
        ][
            'attacking_threat'
        ]
    )
);


captainTest(
    'Confidence component is returned',
    isset(
        $strongResult[
            'components'
        ][
            'confidence'
        ]
    )
);


captainTest(
    'Availability component is returned',
    isset(
        $strongResult[
            'components'
        ][
            'availability'
        ]
    )
);


captainTest(
    'Captain summary is returned',
    trim(
        (string) (
            $strongResult[
                'summary'
            ]
            ?? ''
        )
    )
    !== ''
);

captainTest(
    'Core Captain Score component is returned',
    isset(
        $strongResult[
            'components'
        ][
            'core_score'
        ]
    )
);


captainTest(
    'Confidence Modifier component is returned',
    isset(
        $strongResult[
            'components'
        ][
            'confidence_modifier'
        ]
    )
);


captainTest(
    'Availability Modifier component is returned',
    isset(
        $strongResult[
            'components'
        ][
            'availability_modifier'
        ]
    )
);

captainTest(
    'Raw Fixture component is returned',
    isset(
        $strongResult[
            'components'
        ][
            'raw_fixture'
        ]
    )
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Captain Intelligence Test Summary<br>";
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