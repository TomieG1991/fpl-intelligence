<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Team Intelligence Service Test<br>";
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

function teamIntelligenceServiceCheck(
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
 * SETUP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Setup<br>";
echo "============================================<br>";


try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $service =
        new PlayerIntelligenceService(
            $db
        );


    teamIntelligenceServiceCheck(
        'Database connection is available',
        $db instanceof PDO
    );


    teamIntelligenceServiceCheck(
        'Player Intelligence Service is available',
        $service instanceof PlayerIntelligenceService
    );

} catch (
    Throwable $exception
) {

    teamIntelligenceServiceCheck(
        'Database connection is available',
        false
    );


    teamIntelligenceServiceCheck(
        'Player Intelligence Service is available',
        false
    );


    echo "<br>";
    echo "Setup Error: "
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br><br>";


    echo "============================================<br>";
    echo "Team Intelligence Service Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br><br>";

    echo "RESULT: TESTS FAILED ❌";

    exit;
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * SERVICE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Team Intelligence Service Contract<br>";
echo "============================================<br>";


$methodAvailable =
    method_exists(
        $service,
        'getAllTeamIntelligenceSummaries'
    );


teamIntelligenceServiceCheck(
    'Player Intelligence Service exposes getAllTeamIntelligenceSummaries()',
    $methodAvailable
);


/*
 * Test-first behaviour:
 *
 * The service method does not exist yet.
 *
 * Stop cleanly here so the first run gives one intentional
 * contract failure rather than a PHP fatal error.
 */

if (
    !$methodAvailable
) {

    echo "<br>";
    echo "Team Intelligence summary service has not been implemented yet.<br>";
    echo "The remaining scenarios will run after the service method is added.<br><br>";


    echo "============================================<br>";
    echo "Team Intelligence Service Test Summary<br>";
    echo "============================================<br>";


    echo "Passed: "
        . $passed
        . "<br>";


    echo "Failed: "
        . $failed
        . "<br><br>";


    echo "RESULT: TESTS FAILED ❌";

    exit;
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * TEAM SUMMARY COLLECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Team Summary Collection<br>";
echo "============================================<br>";


$startedAt =
    microtime(
        true
    );


$teams =
    $service
        ->getAllTeamIntelligenceSummaries();


$runtime =
    microtime(
        true
    )
    -
    $startedAt;


teamIntelligenceServiceCheck(
    'Team Intelligence service returns an array',
    is_array(
        $teams
    )
);


teamIntelligenceServiceCheck(
    'Team Intelligence service returns teams',
    !empty(
        $teams
    )
);


teamIntelligenceServiceCheck(
    'Team Intelligence service returns exactly 20 Premier League teams',
    count(
        $teams
    )
    === 20
);


echo "Team Summaries: "
    . count(
        $teams
    )
    . "<br>";


echo "Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * REQUIRED SUMMARY FIELDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Required Team Summary Fields<br>";
echo "============================================<br>";


$requiredFields = [

    'team_id',
    'fpl_team_id',
    'name',
    'short_name',
    'strength_home',
    'strength_away',
    'strength_overall',
    'intelligence_score',
    'intelligence_label',
    'fixture_rating',
    'fixture_label',
    'fixture_trend'
];


$firstTeam =
    $teams[
        0
    ]
    ?? [];


foreach (
    $requiredFields
    as $field
) {

    teamIntelligenceServiceCheck(
        'Team summary field exists: '
        . $field,
        array_key_exists(
            $field,
            $firstTeam
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * TEAM IDENTITY INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Team Identity Integrity<br>";
echo "============================================<br>";


$allIdsValid =
    true;


$allFplIdsValid =
    true;


$allNamesPresent =
    true;


$allShortNamesPresent =
    true;


$teamIds =
    [];


$fplTeamIds =
    [];


foreach (
    $teams
    as $team
) {

    $teamId =
        $team[
            'team_id'
        ]
        ?? null;


    $fplTeamId =
        $team[
            'fpl_team_id'
        ]
        ?? null;


    $name =
        trim(
            (string) (
                $team[
                    'name'
                ]
                ?? ''
            )
        );


    $shortName =
        trim(
            (string) (
                $team[
                    'short_name'
                ]
                ?? ''
            )
        );


    if (
        !is_numeric(
            $teamId
        )
        ||
        (int) $teamId <= 0
    ) {

        $allIdsValid =
            false;
    }


    if (
        !is_numeric(
            $fplTeamId
        )
        ||
        (int) $fplTeamId <= 0
    ) {

        $allFplIdsValid =
            false;
    }


    if (
        $name === ''
    ) {

        $allNamesPresent =
            false;
    }


    if (
        $shortName === ''
    ) {

        $allShortNamesPresent =
            false;
    }


    $teamIds[] =
        (int) $teamId;


    $fplTeamIds[] =
        (int) $fplTeamId;
}


teamIntelligenceServiceCheck(
    'All team IDs are valid',
    $allIdsValid
);


teamIntelligenceServiceCheck(
    'All FPL team IDs are valid',
    $allFplIdsValid
);


teamIntelligenceServiceCheck(
    'All team names are present',
    $allNamesPresent
);


teamIntelligenceServiceCheck(
    'All team short names are present',
    $allShortNamesPresent
);


teamIntelligenceServiceCheck(
    'All local team IDs are unique',
    count(
        array_unique(
            $teamIds
        )
    )
    ===
    count(
        $teamIds
    )
);


teamIntelligenceServiceCheck(
    'All FPL team IDs are unique',
    count(
        array_unique(
            $fplTeamIds
        )
    )
    ===
    count(
        $fplTeamIds
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * TEAM STRENGTH INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Team Strength Integrity<br>";
echo "============================================<br>";


$allStrengthValuesNumeric =
    true;


$allStrengthValuesBounded =
    true;


foreach (
    $teams
    as $team
) {

    foreach (
        [
            'strength_home',
            'strength_away',
            'strength_overall'
        ]
        as $field
    ) {

        $value =
            $team[
                $field
            ]
            ?? null;


        if (
            !is_numeric(
                $value
            )
        ) {

            $allStrengthValuesNumeric =
                false;

            continue;
        }


        $value =
            (float) $value;


        if (
            $value < 0
            ||
            $value > 100
        ) {

            $allStrengthValuesBounded =
                false;
        }
    }
}


teamIntelligenceServiceCheck(
    'All team strength values are numeric',
    $allStrengthValuesNumeric
);


teamIntelligenceServiceCheck(
    'All team strength values remain between 0 and 100',
    $allStrengthValuesBounded
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * TEAM INTELLIGENCE SCORE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Team Intelligence Score<br>";
echo "============================================<br>";


$allIntelligenceScoresNumeric =
    true;


$allIntelligenceScoresBounded =
    true;


$validIntelligenceLabels = [

    'Elite',
    'Strong',
    'Average',
    'Weak',
    'Poor'
];


$allIntelligenceLabelsValid =
    true;


foreach (
    $teams
    as $team
) {

    $score =
        $team[
            'intelligence_score'
        ]
        ?? null;


    $label =
        $team[
            'intelligence_label'
        ]
        ?? null;


    if (
        !is_numeric(
            $score
        )
    ) {

        $allIntelligenceScoresNumeric =
            false;

    } else {

        $score =
            (float) $score;


        if (
            $score < 0
            ||
            $score > 100
        ) {

            $allIntelligenceScoresBounded =
                false;
        }
    }


    if (
        !in_array(
            $label,
            $validIntelligenceLabels,
            true
        )
    ) {

        $allIntelligenceLabelsValid =
            false;
    }
}


teamIntelligenceServiceCheck(
    'All Team Intelligence Scores are numeric',
    $allIntelligenceScoresNumeric
);


teamIntelligenceServiceCheck(
    'All Team Intelligence Scores remain between 0 and 100',
    $allIntelligenceScoresBounded
);


teamIntelligenceServiceCheck(
    'All Team Intelligence labels are valid',
    $allIntelligenceLabelsValid
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * FIXTURE INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Fixture Intelligence<br>";
echo "============================================<br>";


$validFixtureLabels = [

    'Excellent',
    'Good',
    'Average',
    'Difficult',
    'Very Difficult'
];


$allFixtureRatingsNumeric =
    true;


$allFixtureRatingsBounded =
    true;


$allFixtureLabelsValid =
    true;


$allFixtureTrendsPresent =
    true;


foreach (
    $teams
    as $team
) {

    $fixtureRating =
        $team[
            'fixture_rating'
        ]
        ?? null;


    $fixtureLabel =
        $team[
            'fixture_label'
        ]
        ?? null;


    $fixtureTrend =
        trim(
            (string) (
                $team[
                    'fixture_trend'
                ]
                ?? ''
            )
        );


    if (
        !is_numeric(
            $fixtureRating
        )
    ) {

        $allFixtureRatingsNumeric =
            false;

    } else {

        $fixtureRating =
            (float) $fixtureRating;


        if (
            $fixtureRating < 0
            ||
            $fixtureRating > 100
        ) {

            $allFixtureRatingsBounded =
                false;
        }
    }


    if (
        !in_array(
            $fixtureLabel,
            $validFixtureLabels,
            true
        )
    ) {

        $allFixtureLabelsValid =
            false;
    }


    if (
        $fixtureTrend === ''
    ) {

        $allFixtureTrendsPresent =
            false;
    }
}


teamIntelligenceServiceCheck(
    'All fixture ratings are numeric',
    $allFixtureRatingsNumeric
);


teamIntelligenceServiceCheck(
    'All fixture ratings remain between 0 and 100',
    $allFixtureRatingsBounded
);


teamIntelligenceServiceCheck(
    'All fixture labels are valid',
    $allFixtureLabelsValid
);


teamIntelligenceServiceCheck(
    'All teams expose fixture trend information',
    $allFixtureTrendsPresent
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * RANKING INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Ranking Integrity<br>";
echo "============================================<br>";


$rankingOrdered =
    true;


for (
    $index = 1;
    $index < count(
        $teams
    );
    $index++
) {

    $previousScore =
        $teams[
            $index - 1
        ][
            'intelligence_score'
        ]
        ?? null;


    $currentScore =
        $teams[
            $index
        ][
            'intelligence_score'
        ]
        ?? null;


    if (
        !is_numeric(
            $previousScore
        )
        ||
        !is_numeric(
            $currentScore
        )
    ) {

        $rankingOrdered =
            false;

        break;
    }


    if (
        (float) $previousScore
        <
        (float) $currentScore
    ) {

        $rankingOrdered =
            false;

        break;
    }
}


teamIntelligenceServiceCheck(
    'Team summaries are ordered by Team Intelligence Score',
    $rankingOrdered
);


$topTeam =
    $teams[
        0
    ]
    ?? [];


teamIntelligenceServiceCheck(
    'Top-ranked team has a valid name',
    !empty(
        $topTeam[
            'name'
        ]
        ?? null
    )
);


teamIntelligenceServiceCheck(
    'Top-ranked team has numeric Team Intelligence Score',
    is_numeric(
        $topTeam[
            'intelligence_score'
        ]
        ?? null
    )
);


echo "Top Team: "
    . htmlspecialchars(
        (string) (
            $topTeam[
                'name'
            ]
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Top Team Intelligence Score: "
    . (
        is_numeric(
            $topTeam[
                'intelligence_score'
            ]
            ?? null
        )
            ? number_format(
                (float) $topTeam[
                    'intelligence_score'
                ],
                2
            )
            : 'N/A'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO J
 * TEAM DISTRIBUTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Team Distribution<br>";
echo "============================================<br>";


$labelDistribution =
    [];


foreach (
    $teams
    as $team
) {

    $label =
        (string) (
            $team[
                'intelligence_label'
            ]
            ?? 'Unknown'
        );


    if (
        !isset(
            $labelDistribution[
                $label
            ]
        )
    ) {

        $labelDistribution[
            $label
        ] =
            0;
    }


    $labelDistribution[
        $label
    ]++;
}


foreach (
    $labelDistribution
    as $label => $count
) {

    echo htmlspecialchars(
        $label,
        ENT_QUOTES,
        'UTF-8'
    )
    . ": "
    . $count
    . "<br>";
}


teamIntelligenceServiceCheck(
    'Team Intelligence produces at least two classification levels',
    count(
        $labelDistribution
    )
    >= 2
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Performance<br>";
echo "============================================<br>";


teamIntelligenceServiceCheck(
    'Team Intelligence summaries complete within 10 seconds',
    $runtime
    <= 10.0
);


echo "Measured Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Team Intelligence Service Test Summary<br>";
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