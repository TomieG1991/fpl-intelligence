<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Team Attack & Defence Intelligence Test<br>";
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

function teamAttackDefenceCheck(
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


    $teamPerformance =
        new TeamPerformance();


    teamAttackDefenceCheck(
        'Database connection is available',
        $db instanceof PDO
    );


    teamAttackDefenceCheck(
        'Player Intelligence Service is available',
        $service instanceof PlayerIntelligenceService
    );


    teamAttackDefenceCheck(
        'Team Performance model is available',
        $teamPerformance instanceof TeamPerformance
    );

} catch (
    Throwable $exception
) {

    teamAttackDefenceCheck(
        'Database connection is available',
        false
    );


    teamAttackDefenceCheck(
        'Player Intelligence Service is available',
        false
    );


    teamAttackDefenceCheck(
        'Team Performance model is available',
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
    echo "Team Attack & Defence Intelligence Test Summary<br>";
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
 * EXISTING MODEL CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Existing Attack & Defence Model Contract<br>";
echo "============================================<br>";


teamAttackDefenceCheck(
    'Team Performance exposes calculateAttackRating()',
    method_exists(
        $teamPerformance,
        'calculateAttackRating'
    )
);


teamAttackDefenceCheck(
    'Team Performance exposes calculateDefenceRating()',
    method_exists(
        $teamPerformance,
        'calculateDefenceRating'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * ATTACK RATING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Attack Rating<br>";
echo "============================================<br>";


$eliteAttackPerformance = [

    'played' =>
        10,

    'goals_for' =>
        30,

    'goals_against' =>
        10
];


$averageAttackPerformance = [

    'played' =>
        10,

    'goals_for' =>
        15,

    'goals_against' =>
        10
];


$poorAttackPerformance = [

    'played' =>
        10,

    'goals_for' =>
        0,

    'goals_against' =>
        10
];


$eliteAttackRating =
    $teamPerformance
        ->calculateAttackRating(
            $eliteAttackPerformance
        );


$averageAttackRating =
    $teamPerformance
        ->calculateAttackRating(
            $averageAttackPerformance
        );


$poorAttackRating =
    $teamPerformance
        ->calculateAttackRating(
            $poorAttackPerformance
        );


teamAttackDefenceCheck(
    'Elite attacking performance returns numeric rating',
    is_numeric(
        $eliteAttackRating
    )
);


teamAttackDefenceCheck(
    'Three goals per game produces maximum Attack Rating',
    abs(
        (float) $eliteAttackRating
        -
        100.0
    )
    < 0.01
);


teamAttackDefenceCheck(
    'One and a half goals per game produces neutral Attack Rating',
    abs(
        (float) $averageAttackRating
        -
        50.0
    )
    < 0.01
);


teamAttackDefenceCheck(
    'Zero goals produces minimum Attack Rating',
    abs(
        (float) $poorAttackRating
        -
        0.0
    )
    < 0.01
);


teamAttackDefenceCheck(
    'Stronger attacking performance produces higher Attack Rating',
    $eliteAttackRating
    >
    $averageAttackRating
    &&
    $averageAttackRating
    >
    $poorAttackRating
);


echo "Elite Attack Rating: "
    . number_format(
        (float) $eliteAttackRating,
        2
    )
    . "<br>";


echo "Average Attack Rating: "
    . number_format(
        (float) $averageAttackRating,
        2
    )
    . "<br>";


echo "Poor Attack Rating: "
    . number_format(
        (float) $poorAttackRating,
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * DEFENCE RATING
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Defence Rating<br>";
echo "============================================<br>";


$eliteDefencePerformance = [

    'played' =>
        10,

    'goals_for' =>
        15,

    'goals_against' =>
        0
];


$averageDefencePerformance = [

    'played' =>
        10,

    'goals_for' =>
        15,

    'goals_against' =>
        15
];


$poorDefencePerformance = [

    'played' =>
        10,

    'goals_for' =>
        15,

    'goals_against' =>
        30
];


$eliteDefenceRating =
    $teamPerformance
        ->calculateDefenceRating(
            $eliteDefencePerformance
        );


$averageDefenceRating =
    $teamPerformance
        ->calculateDefenceRating(
            $averageDefencePerformance
        );


$poorDefenceRating =
    $teamPerformance
        ->calculateDefenceRating(
            $poorDefencePerformance
        );


teamAttackDefenceCheck(
    'Elite defensive performance returns numeric rating',
    is_numeric(
        $eliteDefenceRating
    )
);


teamAttackDefenceCheck(
    'Zero goals conceded produces maximum Defence Rating',
    abs(
        (float) $eliteDefenceRating
        -
        100.0
    )
    < 0.01
);


teamAttackDefenceCheck(
    'One and a half goals conceded per game produces neutral Defence Rating',
    abs(
        (float) $averageDefenceRating
        -
        50.0
    )
    < 0.01
);


teamAttackDefenceCheck(
    'Three goals conceded per game produces minimum Defence Rating',
    abs(
        (float) $poorDefenceRating
        -
        0.0
    )
    < 0.01
);


teamAttackDefenceCheck(
    'Fewer goals conceded produces higher Defence Rating',
    $eliteDefenceRating
    >
    $averageDefenceRating
    &&
    $averageDefenceRating
    >
    $poorDefenceRating
);


echo "Elite Defence Rating: "
    . number_format(
        (float) $eliteDefenceRating,
        2
    )
    . "<br>";


echo "Average Defence Rating: "
    . number_format(
        (float) $averageDefenceRating,
        2
    )
    . "<br>";


echo "Poor Defence Rating: "
    . number_format(
        (float) $poorDefenceRating,
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * SCORE BOUNDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Score Bounds<br>";
echo "============================================<br>";


$extremeAttackPerformance = [

    'played' =>
        1,

    'goals_for' =>
        10,

    'goals_against' =>
        0
];


$extremeDefencePerformance = [

    'played' =>
        1,

    'goals_for' =>
        0,

    'goals_against' =>
        10
];


$extremeAttackRating =
    $teamPerformance
        ->calculateAttackRating(
            $extremeAttackPerformance
        );


$extremeDefenceRating =
    $teamPerformance
        ->calculateDefenceRating(
            $extremeDefencePerformance
        );


teamAttackDefenceCheck(
    'Attack Rating is capped at 100',
    is_numeric(
        $extremeAttackRating
    )
    &&
    (float) $extremeAttackRating
    === 100.0
);


teamAttackDefenceCheck(
    'Defence Rating is floored at 0',
    is_numeric(
        $extremeDefenceRating
    )
    &&
    (float) $extremeDefenceRating
    === 0.0
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * NO MATCH EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: No Match Evidence<br>";
echo "============================================<br>";


$noMatchPerformance = [

    'played' =>
        0,

    'goals_for' =>
        0,

    'goals_against' =>
        0
];


$noMatchAttackRating =
    $teamPerformance
        ->calculateAttackRating(
            $noMatchPerformance
        );


$noMatchDefenceRating =
    $teamPerformance
        ->calculateDefenceRating(
            $noMatchPerformance
        );


teamAttackDefenceCheck(
    'Attack Rating returns null when no matches have been played',
    $noMatchAttackRating
    ===
    null
);


teamAttackDefenceCheck(
    'Defence Rating returns null when no matches have been played',
    $noMatchDefenceRating
    ===
    null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * TEAM PERFORMANCE ANALYSIS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Completed Fixture Analysis<br>";
echo "============================================<br>";


$syntheticFixtures = [

    [
        'finished' =>
            1,

        'gameweek' =>
            1,

        'home_team_id' =>
            1,

        'away_team_id' =>
            2,

        'home_score' =>
            3,

        'away_score' =>
            0
    ],

    [
        'finished' =>
            1,

        'gameweek' =>
            2,

        'home_team_id' =>
            3,

        'away_team_id' =>
            1,

        'home_score' =>
            1,

        'away_score' =>
            2
    ],

    [
        'finished' =>
            1,

        'gameweek' =>
            3,

        'home_team_id' =>
            1,

        'away_team_id' =>
            4,

        'home_score' =>
            1,

        'away_score' =>
            1
    ]
];


$syntheticPerformance =
    $teamPerformance
        ->analyse(
            $syntheticFixtures,
            1
        );


teamAttackDefenceCheck(
    'Synthetic performance counts completed matches',
    (
        $syntheticPerformance[
            'played'
        ]
        ?? null
    )
    ===
    3
);


teamAttackDefenceCheck(
    'Synthetic performance counts goals scored',
    (
        $syntheticPerformance[
            'goals_for'
        ]
        ?? null
    )
    ===
    6
);


teamAttackDefenceCheck(
    'Synthetic performance counts goals conceded',
    (
        $syntheticPerformance[
            'goals_against'
        ]
        ?? null
    )
    ===
    2
);


$syntheticAttackRating =
    $teamPerformance
        ->calculateAttackRating(
            $syntheticPerformance
        );


$syntheticDefenceRating =
    $teamPerformance
        ->calculateDefenceRating(
            $syntheticPerformance
        );


teamAttackDefenceCheck(
    'Completed fixture analysis produces numeric Attack Rating',
    is_numeric(
        $syntheticAttackRating
    )
);


teamAttackDefenceCheck(
    'Completed fixture analysis produces numeric Defence Rating',
    is_numeric(
        $syntheticDefenceRating
    )
);


teamAttackDefenceCheck(
    'Synthetic Attack Rating remains between 0 and 100',
    (float) $syntheticAttackRating >= 0
    &&
    (float) $syntheticAttackRating <= 100
);


teamAttackDefenceCheck(
    'Synthetic Defence Rating remains between 0 and 100',
    (float) $syntheticDefenceRating >= 0
    &&
    (float) $syntheticDefenceRating <= 100
);


echo "Synthetic Attack Rating: "
    . number_format(
        (float) $syntheticAttackRating,
        2
    )
    . "<br>";


echo "Synthetic Defence Rating: "
    . number_format(
        (float) $syntheticDefenceRating,
        2
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * TEAM INTELLIGENCE SUMMARY INTEGRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Team Intelligence Summary Integration<br>";
echo "============================================<br>";


$teamSummaries =
    $service
        ->getAllTeamIntelligenceSummaries();


teamAttackDefenceCheck(
    'Team Intelligence summaries are returned',
    is_array(
        $teamSummaries
    )
    &&
    !empty(
        $teamSummaries
    )
);


teamAttackDefenceCheck(
    'Team Intelligence summaries contain 20 teams',
    count(
        $teamSummaries
    )
    === 20
);


$firstSummary =
    $teamSummaries[
        0
    ]
    ?? [];


$summaryHasAttackRating =
    array_key_exists(
        'attack_rating',
        $firstSummary
    );


$summaryHasDefenceRating =
    array_key_exists(
        'defence_rating',
        $firstSummary
    );


teamAttackDefenceCheck(
    'Team Intelligence summary exposes attack_rating',
    $summaryHasAttackRating
);


teamAttackDefenceCheck(
    'Team Intelligence summary exposes defence_rating',
    $summaryHasDefenceRating
);


/*
 * Only validate values if the service contract already exists.
 */

if (
    $summaryHasAttackRating
) {

    $allAttackRatingsValid =
        true;


    foreach (
        $teamSummaries
        as $summary
    ) {

        $rating =
            $summary[
                'attack_rating'
            ]
            ?? null;


        /*
         * Null is valid before a team has completed a league match.
         */

        if (
            $rating === null
        ) {

            continue;
        }


        if (
            !is_numeric(
                $rating
            )
            ||
            (float) $rating < 0
            ||
            (float) $rating > 100
        ) {

            $allAttackRatingsValid =
                false;

            break;
        }
    }


    teamAttackDefenceCheck(
        'All available summary Attack Ratings remain between 0 and 100',
        $allAttackRatingsValid
    );
}


if (
    $summaryHasDefenceRating
) {

    $allDefenceRatingsValid =
        true;


    foreach (
        $teamSummaries
        as $summary
    ) {

        $rating =
            $summary[
                'defence_rating'
            ]
            ?? null;


        if (
            $rating === null
        ) {

            continue;
        }


        if (
            !is_numeric(
                $rating
            )
            ||
            (float) $rating < 0
            ||
            (float) $rating > 100
        ) {

            $allDefenceRatingsValid =
                false;

            break;
        }
    }


    teamAttackDefenceCheck(
        'All available summary Defence Ratings remain between 0 and 100',
        $allDefenceRatingsValid
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * PRE-SEASON / NO-EVIDENCE BEHAVIOUR
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: No-Evidence Service Behaviour<br>";
echo "============================================<br>";


if (
    $summaryHasAttackRating
    &&
    $summaryHasDefenceRating
) {

    $nullAttackCount =
        0;


    $nullDefenceCount =
        0;


    $numericAttackCount =
        0;


    $numericDefenceCount =
        0;


    foreach (
        $teamSummaries
        as $summary
    ) {

        if (
            (
                $summary[
                    'attack_rating'
                ]
                ?? null
            )
            ===
            null
        ) {

            $nullAttackCount++;

        } elseif (
            is_numeric(
                $summary[
                    'attack_rating'
                ]
            )
        ) {

            $numericAttackCount++;
        }


        if (
            (
                $summary[
                    'defence_rating'
                ]
                ?? null
            )
            ===
            null
        ) {

            $nullDefenceCount++;

        } elseif (
            is_numeric(
                $summary[
                    'defence_rating'
                ]
            )
        ) {

            $numericDefenceCount++;
        }
    }


    teamAttackDefenceCheck(
        'Every team has either null or numeric Attack Rating',
        (
            $nullAttackCount
            +
            $numericAttackCount
        )
        ===
        count(
            $teamSummaries
        )
    );


    teamAttackDefenceCheck(
        'Every team has either null or numeric Defence Rating',
        (
            $nullDefenceCount
            +
            $numericDefenceCount
        )
        ===
        count(
            $teamSummaries
        )
    );


    echo "Null Attack Ratings: "
        . $nullAttackCount
        . "<br>";


    echo "Numeric Attack Ratings: "
        . $numericAttackCount
        . "<br>";


    echo "Null Defence Ratings: "
        . $nullDefenceCount
        . "<br>";


    echo "Numeric Defence Ratings: "
        . $numericDefenceCount
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * TEAM PROFILE INTEGRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Team Profile Integration<br>";
echo "============================================<br>";


$selectedTeamId =
    (int) (
        $firstSummary[
            'team_id'
        ]
        ?? 0
    );


$profile =
    $selectedTeamId > 0
        ? $service
            ->getTeamIntelligenceProfile(
                $selectedTeamId
            )
        : [];


teamAttackDefenceCheck(
    'Team Intelligence profile can be generated',
    (
        $profile[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


$profileHasAttackRating =
    array_key_exists(
        'attack_rating',
        $profile[
            'performance'
        ]
        ??
        $profile[
            'form'
        ]
        ??
        []
    );


$profileHasDefenceRating =
    array_key_exists(
        'defence_rating',
        $profile[
            'performance'
        ]
        ??
        $profile[
            'form'
        ]
        ??
        []
    );


teamAttackDefenceCheck(
    'Team Intelligence profile exposes attack_rating',
    $profileHasAttackRating
);


teamAttackDefenceCheck(
    'Team Intelligence profile exposes defence_rating',
    $profileHasDefenceRating
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * PROFILE / SUMMARY CONSISTENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Profile & Summary Consistency<br>";
echo "============================================<br>";


if (
    $summaryHasAttackRating
    &&
    $profileHasAttackRating
) {

    $profilePerformance =
        $profile[
            'performance'
        ]
        ??
        $profile[
            'form'
        ]
        ??
        [];


    $summaryAttack =
        $firstSummary[
            'attack_rating'
        ]
        ?? null;


    $profileAttack =
        $profilePerformance[
            'attack_rating'
        ]
        ?? null;


    $attackMatches =
        (
            $summaryAttack === null
            &&
            $profileAttack === null
        )
        ||
        (
            is_numeric(
                $summaryAttack
            )
            &&
            is_numeric(
                $profileAttack
            )
            &&
            abs(
                (float) $summaryAttack
                -
                (float) $profileAttack
            )
            < 0.01
        );


    teamAttackDefenceCheck(
        'Profile Attack Rating matches Team Intelligence summary',
        $attackMatches
    );
}


if (
    $summaryHasDefenceRating
    &&
    $profileHasDefenceRating
) {

    $profilePerformance =
        $profile[
            'performance'
        ]
        ??
        $profile[
            'form'
        ]
        ??
        [];


    $summaryDefence =
        $firstSummary[
            'defence_rating'
        ]
        ?? null;


    $profileDefence =
        $profilePerformance[
            'defence_rating'
        ]
        ?? null;


    $defenceMatches =
        (
            $summaryDefence === null
            &&
            $profileDefence === null
        )
        ||
        (
            is_numeric(
                $summaryDefence
            )
            &&
            is_numeric(
                $profileDefence
            )
            &&
            abs(
                (float) $summaryDefence
                -
                (float) $profileDefence
            )
            < 0.01
        );


    teamAttackDefenceCheck(
        'Profile Defence Rating matches Team Intelligence summary',
        $defenceMatches
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Performance<br>";
echo "============================================<br>";


$startedAt =
    microtime(
        true
    );


$performanceSummaries =
    $service
        ->getAllTeamIntelligenceSummaries();


$runtime =
    microtime(
        true
    )
    -
    $startedAt;


teamAttackDefenceCheck(
    'Team Attack & Defence integration completes within 10 seconds',
    $runtime <= 10.0
);


teamAttackDefenceCheck(
    'Performance run still returns all 20 teams',
    count(
        $performanceSummaries
    )
    === 20
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
echo "Team Attack & Defence Intelligence Test Summary<br>";
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