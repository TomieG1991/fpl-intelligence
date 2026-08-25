<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Captain Intelligence Regression Test<br>";
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

function captainRegressionCheck(
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
 * VALUE HELPERS
 * ============================================================
 */

function captainRegressionNumeric(
    array $player,
    string $key
): ?float {

    if (
        !array_key_exists(
            $key,
            $player
        )
        ||
        !is_numeric(
            $player[
                $key
            ]
        )
    ) {

        return null;
    }


    return (float) $player[
        $key
    ];
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Real Player Summaries<br>";
echo "============================================<br>";


$database =
    new Database();


$db =
    $database
        ->getConnection();


$service =
    new PlayerIntelligenceService(
        $db
    );


$captainIntelligence =
    new CaptainIntelligence();


$startedAt =
    microtime(
        true
    );


$playerSummaries =
    $service
        ->getAllPlayerSummaries();


$summaryRuntime =
    microtime(
        true
    )
    -
    $startedAt;


captainRegressionCheck(
    'Player summaries return an array',
    is_array(
        $playerSummaries
    )
);


captainRegressionCheck(
    'Real player summaries are not empty',
    !empty(
        $playerSummaries
    )
);


captainRegressionCheck(
    'Real player pool contains at least 300 players',
    count(
        $playerSummaries
    )
    >= 300
);


echo "Player Summaries: "
    . count(
        $playerSummaries
    )
    . "<br>";


echo "Summary Runtime: "
    . number_format(
        $summaryRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * REQUIRED CAPTAIN SUMMARY FIELDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Required Captain Summary Fields<br>";
echo "============================================<br>";


$requiredFields = [

    'player_id',
    'name',
    'position',
    'strength_rating',
    'next_fixture_rating',
    'sample_confidence',
    'effective_confidence',
    'availability_rating',
    'goals_rating',
    'assists_rating',
    'expected_goals_rating',
    'expected_assists_rating',
    'adjusted_goals_rating',
    'adjusted_assists_rating',
    'adjusted_expected_goals_rating',
    'adjusted_expected_assists_rating'
];


$summaryWithCaptainFields =
    null;


foreach (
    $playerSummaries
    as $summary
) {

    if (
        !is_array(
            $summary
        )
    ) {

        continue;
    }


    $allFieldsExist =
        true;


    foreach (
        $requiredFields
        as $field
    ) {

        if (
            !array_key_exists(
                $field,
                $summary
            )
        ) {

            $allFieldsExist =
                false;

            break;
        }
    }


    if ($allFieldsExist) {

        $summaryWithCaptainFields =
            $summary;

        break;
    }
}


captainRegressionCheck(
    'At least one summary exposes all Captain Intelligence fields',
    $summaryWithCaptainFields
    !== null
);


if (
    $summaryWithCaptainFields
    !== null
) {

    foreach (
        $requiredFields
        as $field
    ) {

        captainRegressionCheck(
            'Captain summary field exists: '
            . $field,
            array_key_exists(
                $field,
                $summaryWithCaptainFields
            )
        );
    }
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * BUILD REAL CAPTAIN CANDIDATES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Real Captain Candidates<br>";
echo "============================================<br>";


$captainResults =
    [];


$invalidResults =
    0;


foreach (
    $playerSummaries
    as $summary
) {

    if (
        !is_array(
            $summary
        )
    ) {

        continue;
    }


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


    if (
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
    ) {

        continue;
    }


    $captainInput = [

        'player_id' =>
            (int) (
                $summary[
                    'player_id'
                ]
                ?? 0
            ),

        'name' =>
            (string) (
                $summary[
                    'name'
                ]
                ?? ''
            ),

        'position' =>
            $position,

        'strength_score' =>
            captainRegressionNumeric(
                $summary,
                'strength_rating'
            ),

        'fixture_score' =>
            captainRegressionNumeric(
                $summary,
                'next_fixture_rating'
            ),

        'sample_confidence' =>
            captainRegressionNumeric(
                $summary,
                'sample_confidence'
            ),
            
        'effective_confidence' =>
        captainRegressionNumeric(
            $summary,
            'effective_confidence'
        ),    

        'availability' =>
            captainRegressionNumeric(
                $summary,
                'availability_rating'
            ),

        'goals_rating' =>
            captainRegressionNumeric(
                $summary,
                'goals_rating'
            ),

        'assists_rating' =>
            captainRegressionNumeric(
                $summary,
                'assists_rating'
            ),

        'expected_goals_rating' =>
            captainRegressionNumeric(
                $summary,
                'expected_goals_rating'
            ),

        'expected_assists_rating' =>
            captainRegressionNumeric(
                $summary,
                'expected_assists_rating'
            ),
            
        'adjusted_goals_rating' =>
            captainRegressionNumeric(
                $summary,
                'adjusted_goals_rating'
            ),

        'adjusted_assists_rating' =>
            captainRegressionNumeric(
                $summary,
                'adjusted_assists_rating'
            ),

        'adjusted_expected_goals_rating' =>
            captainRegressionNumeric(
                $summary,
                'adjusted_expected_goals_rating'
            ),

        'adjusted_expected_assists_rating' =>
            captainRegressionNumeric(
                $summary,
                'adjusted_expected_assists_rating'
            )
    ];


    $result =
        $captainIntelligence
            ->evaluate(
                $captainInput
            );


    if (
        (
            $result[
                'status'
            ]
            ?? null
        )
        !==
        'success'
    ) {

        $invalidResults++;

        continue;
    }


    $result[
        'team_name'
    ] =
        $summary[
            'team_name'
        ]
        ?? null;


    $captainResults[] =
        $result;
}


captainRegressionCheck(
    'Real captain candidates are generated',
    !empty(
        $captainResults
    )
);


$evaluatedCaptainCount =
    count(
        $captainResults
    )
    +
    $invalidResults;


captainRegressionCheck(
    'Real captain evaluation accounts for the current player pool',
    $evaluatedCaptainCount
    ===
    count(
        $playerSummaries
    )
);


captainRegressionCheck(
    'Real dataset contains a substantial usable captain candidate pool',
    count(
        $captainResults
    )
    >=
    (
        count(
            $playerSummaries
        )
        *
        0.40
    )
);


echo "Valid Captain Candidates: "
    . count(
        $captainResults
    )
    . "<br>";


echo "Rejected Candidates: "
    . $invalidResults
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * SCORE INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Captain Score Integrity<br>";
echo "============================================<br>";


$allScoresNumeric =
    true;


$allScoresBounded =
    true;


$allComponentsPresent =
    true;


$validClassifications = [

    'Elite Captain',
    'Strong Captain',
    'Good Option',
    'Differential',
    'Avoid'
];


$allClassificationsValid =
    true;


foreach (
    $captainResults
    as $result
) {

    $score =
        $result[
            'captain_score'
        ]
        ?? null;


    if (
        !is_numeric(
            $score
        )
    ) {

        $allScoresNumeric =
            false;
    }


    if (
        !is_numeric(
            $score
        )
        ||
        $score < 0
        ||
        $score > 100
    ) {

        $allScoresBounded =
            false;
    }


    $components =
        $result[
            'components'
        ]
        ?? [];


    foreach (
        [
            'strength',
            'raw_fixture',
            'fixture',
            'attacking_threat',
            'core_score',
            'confidence',
            'confidence_modifier',
            'availability',
            'availability_modifier'
        ]
        as $component
    ) {

        if (
            !array_key_exists(
                $component,
                $components
            )
        ) {

            $allComponentsPresent =
                false;

            break;
        }
    }


    if (
        !in_array(
            $result[
                'classification'
            ]
            ?? '',
            $validClassifications,
            true
        )
    ) {

        $allClassificationsValid =
            false;
    }
}


captainRegressionCheck(
    'All Captain Scores are numeric',
    $allScoresNumeric
);


captainRegressionCheck(
    'All Captain Scores remain between 0 and 100',
    $allScoresBounded
);


captainRegressionCheck(
    'All Captain Intelligence components are returned',
    $allComponentsPresent
);


captainRegressionCheck(
    'All Captain classifications are valid',
    $allClassificationsValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * FIXTURE CALIBRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Fixture Calibration<br>";
echo "============================================<br>";


$fixtureCalibrationValid =
    true;


$highFixturesCompressed =
    true;


$lowFixturesLifted =
    true;


foreach (
    $captainResults
    as $result
) {

    $components =
        $result[
            'components'
        ]
        ?? [];


    $rawFixture =
        $components[
            'raw_fixture'
        ]
        ?? null;


    $fixture =
        $components[
            'fixture'
        ]
        ?? null;


    if (
        !is_numeric(
            $rawFixture
        )
        ||
        !is_numeric(
            $fixture
        )
    ) {

        $fixtureCalibrationValid =
            false;

        continue;
    }


    if (
        $fixture < 0
        ||
        $fixture > 100
    ) {

        $fixtureCalibrationValid =
            false;
    }


    if (
        $rawFixture > 50
        &&
        $fixture > $rawFixture
    ) {

        $highFixturesCompressed =
            false;
    }


    if (
        $rawFixture < 50
        &&
        $fixture < $rawFixture
    ) {

        $lowFixturesLifted =
            false;
    }
}


captainRegressionCheck(
    'Calibrated fixture values remain valid',
    $fixtureCalibrationValid
);


captainRegressionCheck(
    'Strong raw fixtures are not amplified',
    $highFixturesCompressed
);


captainRegressionCheck(
    'Weak raw fixtures are not made harsher',
    $lowFixturesLifted
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * RISK MODIFIERS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Risk Modifiers<br>";
echo "============================================<br>";


$confidenceModifiersValid =
    true;


$availabilityModifiersValid =
    true;


$lowConfidencePenaltyObserved =
    false;


foreach (
    $captainResults
    as $result
) {

    $components =
        $result[
            'components'
        ]
        ?? [];


    $confidence =
        $components[
            'confidence'
        ]
        ?? null;


    $confidenceModifier =
        $components[
            'confidence_modifier'
        ]
        ?? null;


    $availabilityModifier =
        $components[
            'availability_modifier'
        ]
        ?? null;


    if (
        !is_numeric(
            $confidenceModifier
        )
        ||
        $confidenceModifier < 0
        ||
        $confidenceModifier > 1
    ) {

        $confidenceModifiersValid =
            false;
    }


    if (
        !is_numeric(
            $availabilityModifier
        )
        ||
        $availabilityModifier < 0
        ||
        $availabilityModifier > 1
    ) {

        $availabilityModifiersValid =
            false;
    }


    if (
        is_numeric(
            $confidence
        )
        &&
        $confidence < 50
        &&
        is_numeric(
            $confidenceModifier
        )
        &&
        $confidenceModifier < 0.90
    ) {

        $lowConfidencePenaltyObserved =
            true;
    }
}


captainRegressionCheck(
    'Confidence modifiers remain between 0 and 1',
    $confidenceModifiersValid
);


captainRegressionCheck(
    'Availability modifiers remain between 0 and 1',
    $availabilityModifiersValid
);


captainRegressionCheck(
    'Low-confidence real players receive a meaningful penalty',
    $lowConfidencePenaltyObserved
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * CAPTAIN RANKING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Captain Ranking<br>";
echo "============================================<br>";


usort(
    $captainResults,
    function (
        array $a,
        array $b
    ): int {

        return (
            $b[
                'captain_score'
            ]
            ?? 0
        )
        <=>
        (
            $a[
                'captain_score'
            ]
            ?? 0
        );
    }
);


$topCaptains =
    array_slice(
        $captainResults,
        0,
        20
    );


captainRegressionCheck(
    'Top-20 captain ranking contains twenty players',
    count(
        $topCaptains
    )
    === 20
);


$topPositionCounts = [

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
    $topCaptains
    as $captain
) {

    $position =
        $captain[
            'position'
        ]
        ?? '';


    if (
        isset(
            $topPositionCounts[
                $position
            ]
        )
    ) {

        $topPositionCounts[
            $position
        ]++;
    }
}


captainRegressionCheck(
    'Top captain ranking is not goalkeeper dominated',
    (
        $topPositionCounts[
            'GK'
        ]
        ?? 0
    )
    <= 2
);


captainRegressionCheck(
    'Top captain ranking contains midfielders or forwards',
    (
        (
            $topPositionCounts[
                'MID'
            ]
            ?? 0
        )
        +
        (
            $topPositionCounts[
                'FWD'
            ]
            ?? 0
        )
    )
    >= 5
);


captainRegressionCheck(
    'Top captain candidate has a usable classification',
    isset(
        $topCaptains[
            0
        ][
            'classification'
        ]
    )
    &&
    in_array(
        $topCaptains[
            0
        ][
            'classification'
        ],
        $validClassifications,
        true
    )
);


echo "Top Captain Score: "
    . number_format(
        (float) (
            $topCaptains[
                0
            ][
                'captain_score'
            ]
            ?? 0
        ),
        2
    )
    . "<br>";


echo "Top 20 GK: "
    . $topPositionCounts[
        'GK'
    ]
    . "<br>";


echo "Top 20 DEF: "
    . $topPositionCounts[
        'DEF'
    ]
    . "<br>";


echo "Top 20 MID: "
    . $topPositionCounts[
        'MID'
    ]
    . "<br>";


echo "Top 20 FWD: "
    . $topPositionCounts[
        'FWD'
    ]
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * CLASSIFICATION BOUNDARIES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Classification Distribution<br>";
echo "============================================<br>";


$classificationCounts = [

    'Elite Captain' =>
        0,

    'Strong Captain' =>
        0,

    'Good Option' =>
        0,

    'Differential' =>
        0,

    'Avoid' =>
        0
];


foreach (
    $captainResults
    as $result
) {

    $classification =
        $result[
            'classification'
        ]
        ?? '';


    if (
        isset(
            $classificationCounts[
                $classification
            ]
        )
    ) {

        $classificationCounts[
            $classification
        ]++;
    }
}


captainRegressionCheck(
    'Captain classifications are distributed across multiple levels',
    count(
        array_filter(
            $classificationCounts,
            function (
                int $count
            ): bool {

                return $count > 0;
            }
        )
    )
    >= 3
);


foreach (
    $classificationCounts
    as $classification => $count
) {

    echo htmlspecialchars(
        $classification,
        ENT_QUOTES,
        'UTF-8'
    )
    . ": "
    . $count
    . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Performance<br>";
echo "============================================<br>";


captainRegressionCheck(
    'Real-data Captain Intelligence completes within 10 seconds',
    $summaryRuntime
    <= 10.0
);


echo "Measured Runtime: "
    . number_format(
        $summaryRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Captain Intelligence Regression Test Summary<br>";
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