<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Captain Intelligence Real Data Diagnostic<br>";
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

function captainRealCheck(
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

function captainRealFirstNumeric(
    array $player,
    array $keys,
    ?float $default = null
): ?float {

    foreach (
        $keys
        as $key
    ) {

        if (
            array_key_exists(
                $key,
                $player
            )
            &&
            is_numeric(
                $player[
                    $key
                ]
            )
        ) {

            return (float) $player[
                $key
            ];
        }
    }


    return $default;
}


function captainRealFirstString(
    array $player,
    array $keys,
    string $default = ''
): string {

    foreach (
        $keys
        as $key
    ) {

        if (
            !array_key_exists(
                $key,
                $player
            )
        ) {

            continue;
        }


        $value =
            trim(
                (string) $player[
                    $key
                ]
            );


        if (
            $value !== ''
        ) {

            return $value;
        }
    }


    return $default;
}


function captainRealHasNumericField(
    array $players,
    array $keys
): bool {

    foreach (
        $players
        as $player
    ) {

        if (
            !is_array(
                $player
            )
        ) {

            continue;
        }


        foreach (
            $keys
            as $key
        ) {

            if (
                array_key_exists(
                    $key,
                    $player
                )
                &&
                is_numeric(
                    $player[
                        $key
                    ]
                )
            ) {

                return true;
            }
        }
    }


    return false;
}


/*
 * ============================================================
 * DATABASE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Real Player Data<br>";
echo "============================================<br>";


$database =
    new Database();


$db =
    $database
        ->getConnection();


$playerRepository =
    new PlayerRepository(
        $db
    );


$allPlayers =
    $playerRepository
        ->getAll();


captainRealCheck(
    'Real player repository returns players',
    is_array(
        $allPlayers
    )
    &&
    count(
        $allPlayers
    )
    > 0
);


echo "Repository Players: "
    . count(
        $allPlayers
    )
    . "<br><br>";


/*
 * ============================================================
 * PLAYER INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Player Intelligence Summaries<br>";
echo "============================================<br>";


$playerIntelligenceService =
    new PlayerIntelligenceService(
        $db
    );


$startedAt =
    microtime(
        true
    );


$playerSummaries =
    $playerIntelligenceService
        ->getAllPlayerSummaries();


$summaryRuntime =
    microtime(
        true
    )
    -
    $startedAt;


captainRealCheck(
    'Player Intelligence returns summaries',
    is_array(
        $playerSummaries
    )
    &&
    count(
        $playerSummaries
    )
    > 0
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
 * FIELD DISCOVERY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Captain Field Discovery<br>";
echo "============================================<br>";


$strengthKeys = [

    'strength_rating',
    'strength_score',
    'strength',
    'player_strength_score'
];


$fixtureKeys = [

    'next_fixture_rating'
];


$confidenceKeys = [

    'sample_confidence',
    'confidence',
    'confidence_score'
];


$availabilityKeys = [

    'availability_rating',
    'availability',
    'availability_score'
];


$goalsKeys = [

    'goals_rating'
];


$assistsKeys = [

    'assists_rating'
];


$expectedGoalsKeys = [

    'expected_goals_rating'
];


$expectedAssistsKeys = [

    'expected_assists_rating'
];


$fieldGroups = [

    'Strength' =>
        $strengthKeys,

    'Next Fixture' =>
        $fixtureKeys,

    'Confidence' =>
        $confidenceKeys,

    'Availability' =>
        $availabilityKeys,

    'Goals Rating' =>
        $goalsKeys,

    'Assists Rating' =>
        $assistsKeys,

    'Expected Goals Rating' =>
        $expectedGoalsKeys,

    'Expected Assists Rating' =>
        $expectedAssistsKeys
    ];


foreach (
    $fieldGroups
    as $label => $keys
) {

    $foundKeys =
        [];


    foreach (
        $keys
        as $key
    ) {

        if (
            captainRealHasNumericField(
                $playerSummaries,
                [
                    $key
                ]
            )
        ) {

            $foundKeys[] =
                $key;
        }
    }


    echo htmlspecialchars(
        $label,
        ENT_QUOTES,
        'UTF-8'
    )
    . ": "
    . (
        count(
            $foundKeys
        )
        > 0
            ? implode(
                ', ',
                $foundKeys
            )
            : 'NOT FOUND'
    )
    . "<br>";
}


echo "<br>";

/*
 * ============================================================
 * NEXT-FIXTURE TEAM DISTRIBUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C2: Next-Fixture Team Distribution<br>";
echo "============================================<br>";


$teamNextFixtureRatings =
    [];


foreach (
    $playerSummaries
    as $player
) {

    if (
        !is_array(
            $player
        )
    ) {

        continue;
    }


    $teamName =
        captainRealFirstString(
            $player,
            [
                'team_name',
                'team'
            ]
        );


    $nextFixtureRating =
        $player[
            'next_fixture_rating'
        ]
        ?? null;


    if (
        $teamName === ''
        ||
        !is_numeric(
            $nextFixtureRating
        )
    ) {

        continue;
    }


    /*
     * All players from one club should share the same
     * immediate fixture opportunity.
     */

    $teamNextFixtureRatings[
        $teamName
    ] =
        (float) $nextFixtureRating;
}


/*
 * Highest opportunity first.
 */

arsort(
    $teamNextFixtureRatings,
    SORT_NUMERIC
);


foreach (
    $teamNextFixtureRatings
    as $teamName => $rating
) {

    echo htmlspecialchars(
        $teamName,
        ENT_QUOTES,
        'UTF-8'
    )
    . " | Next Fixture "
    . number_format(
        $rating,
        1
    )
    . "<br>";
}


$perfectFixtureRatings =
    count(
        array_filter(
            $teamNextFixtureRatings,
            function (
                float $rating
            ): bool {

                return $rating >= 99.9;
            }
        )
    );


echo "<br>Teams with 100.0 rating: "
    . $perfectFixtureRatings
    . "<br>";


echo "Teams with fixture rating: "
    . count(
        $teamNextFixtureRatings
    )
    . "<br><br>";


/*
 * ============================================================
 * REQUIRED BASE FIELDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Required Captain Inputs<br>";
echo "============================================<br>";


$hasStrength =
    captainRealHasNumericField(
        $playerSummaries,
        $strengthKeys
    );


$hasFixture =
    captainRealHasNumericField(
        $playerSummaries,
        $fixtureKeys
    );


$hasConfidence =
    captainRealHasNumericField(
        $playerSummaries,
        $confidenceKeys
    );


$hasAvailability =
    captainRealHasNumericField(
        $playerSummaries,
        $availabilityKeys
    );


captainRealCheck(
    'Real summaries expose player strength',
    $hasStrength
);


captainRealCheck(
    'Real summaries expose next-fixture opportunity',
    $hasFixture
);


captainRealCheck(
    'Real summaries expose sample confidence',
    $hasConfidence
);


captainRealCheck(
    'Real summaries expose availability',
    $hasAvailability
);


echo "<br>";


/*
 * ============================================================
 * BUILD CAPTAIN INPUTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Captain Candidate Mapping<br>";
echo "============================================<br>";


$captainIntelligence =
    new CaptainIntelligence();


$captainResults =
    [];


$invalidResults =
    0;


foreach (
    $playerSummaries
    as $player
) {

    if (
        !is_array(
            $player
        )
    ) {

        continue;
    }


    $position =
        strtoupper(
            captainRealFirstString(
                $player,
                [
                    'position',
                    'position_short',
                    'element_type_name'
                ]
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
                captainRealFirstNumeric(
                    $player,
                    [
                        'player_id',
                        'id',
                        'element'
                    ],
                    0.0
                )
                ?? 0
            ),

        'name' =>
            captainRealFirstString(
                $player,
                [
                    'web_name',
                    'name',
                    'player_name'
                ],
                'Unknown Player'
            ),

        'position' =>
            $position,

        'strength_score' =>
            captainRealFirstNumeric(
                $player,
                $strengthKeys
            ),

        'fixture_score' =>
            captainRealFirstNumeric(
                $player,
                $fixtureKeys
            ),

        'goals_rating' =>
            captainRealFirstNumeric(
                $player,
                $goalsKeys
            ),

        'assists_rating' =>
            captainRealFirstNumeric(
                $player,
                $assistsKeys
            ),

        'expected_goals_rating' =>
            captainRealFirstNumeric(
                $player,
                $expectedGoalsKeys
            ),

        'expected_assists_rating' =>
            captainRealFirstNumeric(
                $player,
                $expectedAssistsKeys
            ),

        'sample_confidence' =>
            captainRealFirstNumeric(
                $player,
                $confidenceKeys
            ),

        'availability' =>
            captainRealFirstNumeric(
                $player,
                $availabilityKeys
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
        !== 'success'
    ) {

        $invalidResults++;

        continue;
    }


    $result[
        'team'
    ] =
        captainRealFirstString(
            $player,
            [
                'team_name',
                'team'
            ],
            'Unknown'
        );


    $result[
        'price'
    ] =
        captainRealFirstNumeric(
            $player,
            [
                'price',
                'now_cost'
            ],
            0.0
        )
        ?? 0.0;


    $captainResults[] =
        $result;
}


captainRealCheck(
    'Real players can be evaluated for captaincy',
    count(
        $captainResults
    )
    > 0
);


echo "Valid Captain Results: "
    . count(
        $captainResults
    )
    . "<br>";


echo "Invalid Captain Results: "
    . $invalidResults
    . "<br><br>";


/*
 * ============================================================
 * SORT CAPTAIN RANKINGS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Captain Rankings<br>";
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


captainRealCheck(
    'Captain rankings contain candidates',
    count(
        $topCaptains
    )
    > 0
);


$rank =
    1;


foreach (
    $topCaptains
    as $captain
) {

    echo "<strong>#"
        . $rank
        . " "
        . htmlspecialchars(
            (string) (
                $captain[
                    'name'
                ]
                ?? 'Unknown'
            ),
            ENT_QUOTES,
            'UTF-8'
        )
        . "</strong><br>";


    echo htmlspecialchars(
        (string) (
            $captain[
                'team'
            ]
            ?? 'Unknown'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " | "
    . htmlspecialchars(
        (string) (
            $captain[
                'position'
            ]
            ?? ''
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . " | Captain "
    . number_format(
        (float) (
            $captain[
                'captain_score'
            ]
            ?? 0
        ),
        2
    )
    . " | "
    . htmlspecialchars(
        (string) (
            $captain[
                'classification'
            ]
            ?? ''
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


    $components =
        $captain[
            'components'
        ]
        ?? [];


    echo "STR "
        . number_format(
            (float) (
                $components[
                    'strength'
                ]
                ?? 0
            ),
            1
        )
        . " | RAW FIX "
        . number_format(
            (float) (
                $components[
                    'raw_fixture'
                ]
                ?? 0
            ),
            1
        )
        . " | FIX "
        . number_format(
            (float) (
                $components[
                    'fixture'
                ]
                ?? 0
            ),
            1
        )
        . " | THREAT "
        . number_format(
            (float) (
                $components[
                    'attacking_threat'
                ]
                ?? 0
            ),
            1
        )
        . " | CORE "
        . number_format(
            (float) (
                $components[
                    'core_score'
                ]
                ?? 0
            ),
            2
        )
        . "<br>";


    echo "CONF "
        . number_format(
            (float) (
                $components[
                    'confidence'
                ]
                ?? 0
            ),
            1
        )
        . "% | CONF MOD "
        . number_format(
            (float) (
                $components[
                    'confidence_modifier'
                ]
                ?? 0
            ),
            3
        )
        . " | AVAIL "
        . number_format(
            (float) (
                $components[
                    'availability'
                ]
                ?? 0
            ),
            1
        )
        . "% | AVAIL MOD "
        . number_format(
            (float) (
                $components[
                    'availability_modifier'
                ]
                ?? 0
            ),
            3
        )
        . "<br><br>";


    $rank++;
}


/*
 * ============================================================
 * POSITION DISTRIBUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Top Captain Position Distribution<br>";
echo "============================================<br>";


$positionCounts = [

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
            $positionCounts[
                $position
            ]
        )
    ) {

        $positionCounts[
            $position
        ]++;
    }
}


foreach (
    $positionCounts
    as $position => $count
) {

    echo $position
        . ": "
        . $count
        . "<br>";
}


echo "<br>";


captainRealCheck(
    'Top captain rankings are not dominated by goalkeepers',
    (
        $positionCounts[
            'GK'
        ]
        ?? 0
    )
    <= 2
);


captainRealCheck(
    'Top captain rankings contain an attacking player',
    (
        (
            $positionCounts[
                'MID'
            ]
            ?? 0
        )
        +
        (
            $positionCounts[
                'FWD'
            ]
            ?? 0
        )
    )
    > 0
);


echo "<br>";


/*
 * ============================================================
 * SCORE INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Score Integrity<br>";
echo "============================================<br>";


$allScoresNumeric =
    true;


$allScoresBounded =
    true;


$allComponentsPresent =
    true;


foreach (
    $captainResults
    as $captain
) {

    $score =
        $captain[
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
        $captain[
            'components'
        ]
        ?? [];


    foreach (
        [
            'strength',
            'fixture',
            'attacking_threat',
            'confidence',
            'availability'
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
}


captainRealCheck(
    'All Captain Scores are numeric',
    $allScoresNumeric
);


captainRealCheck(
    'All Captain Scores remain between 0 and 100',
    $allScoresBounded
);


captainRealCheck(
    'All captain component scores are returned',
    $allComponentsPresent
);


echo "<br>";


/*
 * ============================================================
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Performance<br>";
echo "============================================<br>";


captainRealCheck(
    'Player Intelligence summaries complete within 10 seconds',
    $summaryRuntime
    <= 10.0
);


echo "Measured Summary Runtime: "
    . number_format(
        $summaryRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Captain Intelligence Real Data Test Summary<br>";
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