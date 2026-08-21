<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Team Intelligence Profile Test<br>";
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

function teamProfileCheck(
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


    teamProfileCheck(
        'Database connection is available',
        $db instanceof PDO
    );


    teamProfileCheck(
        'Player Intelligence Service is available',
        $service instanceof PlayerIntelligenceService
    );

} catch (
    Throwable $exception
) {

    teamProfileCheck(
        'Database connection is available',
        false
    );


    teamProfileCheck(
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
    echo "Team Intelligence Profile Test Summary<br>";
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
echo "Scenario B: Team Profile Service Contract<br>";
echo "============================================<br>";


$methodAvailable =
    method_exists(
        $service,
        'getTeamIntelligenceProfile'
    );


teamProfileCheck(
    'Player Intelligence Service exposes getTeamIntelligenceProfile()',
    $methodAvailable
);


if (
    !$methodAvailable
) {

    echo "<br>";
    echo "Team Intelligence profile service has not been implemented yet.<br>";
    echo "The remaining scenarios will run after the service method is added.<br><br>";


    echo "============================================<br>";
    echo "Team Intelligence Profile Test Summary<br>";
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
 * SELECT REAL TEAM
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Real Team Selection<br>";
echo "============================================<br>";


$teamSummaries =
    $service
        ->getAllTeamIntelligenceSummaries();


teamProfileCheck(
    'Team Intelligence summaries are available',
    is_array(
        $teamSummaries
    )
    &&
    !empty(
        $teamSummaries
    )
);


$selectedSummary =
    $teamSummaries[
        0
    ]
    ?? null;


$selectedTeamId =
    (int) (
        $selectedSummary[
            'team_id'
        ]
        ?? 0
    );


teamProfileCheck(
    'Real Team Intelligence team can be selected',
    is_array(
        $selectedSummary
    )
    &&
    $selectedTeamId > 0
);


echo "Selected Team: "
    . htmlspecialchars(
        (string) (
            $selectedSummary[
                'name'
            ]
            ?? 'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Selected Team ID: "
    . $selectedTeamId
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO D
 * PROFILE RESULT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Team Profile Result<br>";
echo "============================================<br>";


$startedAt =
    microtime(
        true
    );


$profile =
    $service
        ->getTeamIntelligenceProfile(
            $selectedTeamId
        );


$runtime =
    microtime(
        true
    )
    -
    $startedAt;


teamProfileCheck(
    'Team profile service returns an array',
    is_array(
        $profile
    )
);


teamProfileCheck(
    'Valid Team Intelligence profile returns success',
    (
        $profile[
            'status'
        ]
        ?? null
    )
    ===
    'success'
);


teamProfileCheck(
    'Team profile message is returned',
    !empty(
        $profile[
            'message'
        ]
        ?? null
    )
);


echo "Profile Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * RESULT STRUCTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Profile Result Structure<br>";
echo "============================================<br>";


$requiredTopLevelFields = [

    'status',
    'message',
    'team',
    'ranking',
    'strength',
    'fixtures',
    'form',
    'players'
];


foreach (
    $requiredTopLevelFields
    as $field
) {

    teamProfileCheck(
        'Team profile contains field: '
        . $field,
        array_key_exists(
            $field,
            $profile
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * TEAM IDENTITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Team Identity<br>";
echo "============================================<br>";


$team =
    $profile[
        'team'
    ]
    ?? [];


teamProfileCheck(
    'Team profile preserves requested team ID',
    (
        (int) (
            $team[
                'team_id'
            ]
            ?? 0
        )
    )
    ===
    $selectedTeamId
);


teamProfileCheck(
    'Team profile contains FPL team ID',
    is_numeric(
        $team[
            'fpl_team_id'
        ]
        ?? null
    )
);


teamProfileCheck(
    'Team profile contains team name',
    !empty(
        $team[
            'name'
        ]
        ?? null
    )
);


teamProfileCheck(
    'Team profile contains team short name',
    !empty(
        $team[
            'short_name'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * RANKING INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Ranking Intelligence<br>";
echo "============================================<br>";


$ranking =
    $profile[
        'ranking'
    ]
    ?? [];


teamProfileCheck(
    'Team profile contains numeric league rank',
    is_numeric(
        $ranking[
            'rank'
        ]
        ?? null
    )
);


teamProfileCheck(
    'Team league rank remains between 1 and 20',
    (
        (int) (
            $ranking[
                'rank'
            ]
            ?? 0
        )
    )
    >= 1
    &&
    (
        (int) (
            $ranking[
                'rank'
            ]
            ?? 0
        )
    )
    <= 20
);


teamProfileCheck(
    'Team profile contains numeric Team Intelligence Score',
    is_numeric(
        $ranking[
            'intelligence_score'
        ]
        ?? null
    )
);


teamProfileCheck(
    'Team Intelligence Score remains between 0 and 100',
    is_numeric(
        $ranking[
            'intelligence_score'
        ]
        ?? null
    )
    &&
    (float) $ranking[
        'intelligence_score'
    ] >= 0
    &&
    (float) $ranking[
        'intelligence_score'
    ] <= 100
);


$validLabels = [

    'Elite',
    'Strong',
    'Average',
    'Weak',
    'Poor'
];


teamProfileCheck(
    'Team Intelligence classification is valid',
    in_array(
        $ranking[
            'intelligence_label'
        ]
        ?? null,
        $validLabels,
        true
    )
);


echo "League Rank: "
    . (
        $ranking[
            'rank'
        ]
        ?? 'N/A'
    )
    . "<br>";


echo "Team Intelligence Score: "
    . (
        is_numeric(
            $ranking[
                'intelligence_score'
            ]
            ?? null
        )
            ? number_format(
                (float) $ranking[
                    'intelligence_score'
                ],
                2
            )
            : 'N/A'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * STRENGTH
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Team Strength<br>";
echo "============================================<br>";


$strength =
    $profile[
        'strength'
    ]
    ?? [];


foreach (
    [
        'overall',
        'home',
        'away'
    ]
    as $field
) {

    teamProfileCheck(
        'Team strength contains numeric '
        . $field
        . ' rating',
        is_numeric(
            $strength[
                $field
            ]
            ?? null
        )
    );


    teamProfileCheck(
        ucfirst(
            $field
        )
        . ' strength remains between 0 and 100',
        is_numeric(
            $strength[
                $field
            ]
            ?? null
        )
        &&
        (float) $strength[
            $field
        ] >= 0
        &&
        (float) $strength[
            $field
        ] <= 100
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * FIXTURE PROFILE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Fixture Profile<br>";
echo "============================================<br>";


$fixtures =
    $profile[
        'fixtures'
    ]
    ?? [];


teamProfileCheck(
    'Team profile contains numeric fixture rating',
    is_numeric(
        $fixtures[
            'rating'
        ]
        ?? null
    )
);


teamProfileCheck(
    'Fixture rating remains between 0 and 100',
    is_numeric(
        $fixtures[
            'rating'
        ]
        ?? null
    )
    &&
    (float) $fixtures[
        'rating'
    ] >= 0
    &&
    (float) $fixtures[
        'rating'
    ] <= 100
);


$validFixtureLabels = [

    'Excellent',
    'Good',
    'Average',
    'Difficult',
    'Very Difficult'
];


teamProfileCheck(
    'Team fixture classification is valid',
    in_array(
        $fixtures[
            'label'
        ]
        ?? null,
        $validFixtureLabels,
        true
    )
);


teamProfileCheck(
    'Team fixture trend is returned',
    trim(
        (string) (
            $fixtures[
                'trend'
            ]
            ?? ''
        )
    )
    !== ''
);


teamProfileCheck(
    'Upcoming fixtures are returned as an array',
    is_array(
        $fixtures[
            'upcoming'
        ]
        ?? null
    )
);


echo "Fixture Rating: "
    . (
        is_numeric(
            $fixtures[
                'rating'
            ]
            ?? null
        )
            ? number_format(
                (float) $fixtures[
                    'rating'
                ],
                1
            )
            : 'N/A'
    )
    . "<br>";


echo "Upcoming Fixtures: "
    . count(
        $fixtures[
            'upcoming'
        ]
        ?? []
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO J
 * FORM
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Team Form<br>";
echo "============================================<br>";


$form =
    $profile[
        'form'
    ]
    ?? [];


$requiredFormFields = [

    'recent_form',
    'played',
    'wins',
    'draws',
    'losses',
    'goals_for',
    'goals_against',
    'goal_difference'
];


foreach (
    $requiredFormFields
    as $field
) {

    teamProfileCheck(
        'Team form contains field: '
        . $field,
        array_key_exists(
            $field,
            $form
        )
    );
}


teamProfileCheck(
    'Recent form is returned as an array',
    is_array(
        $form[
            'recent_form'
        ]
        ?? null
    )
);


foreach (
    [
        'played',
        'wins',
        'draws',
        'losses',
        'goals_for',
        'goals_against',
        'goal_difference'
    ]
    as $field
) {

    teamProfileCheck(
        'Team form field '
        . $field
        . ' is numeric',
        is_numeric(
            $form[
                $field
            ]
            ?? null
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * TEAM PLAYERS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Team Players<br>";
echo "============================================<br>";


$players =
    $profile[
        'players'
    ]
    ?? [];


teamProfileCheck(
    'Team players are returned as an array',
    is_array(
        $players
    )
);


teamProfileCheck(
    'Team profile contains current players',
    !empty(
        $players
    )
);


$allPlayersMatchTeam =
    true;


$allPlayerIdsValid =
    true;


$allPlayerNamesPresent =
    true;


foreach (
    $players
    as $player
) {

    if (
        (
            (int) (
                $player[
                    'team_id'
                ]
                ?? 0
            )
        )
        !==
        $selectedTeamId
    ) {

        $allPlayersMatchTeam =
            false;
    }


    if (
        !is_numeric(
            $player[
                'player_id'
            ]
            ?? null
        )
        ||
        (int) $player[
            'player_id'
        ] <= 0
    ) {

        $allPlayerIdsValid =
            false;
    }


    if (
        trim(
            (string) (
                $player[
                    'name'
                ]
                ?? ''
            )
        )
        === ''
    ) {

        $allPlayerNamesPresent =
            false;
    }
}


teamProfileCheck(
    'All returned players belong to the selected team',
    $allPlayersMatchTeam
);


teamProfileCheck(
    'All returned players have valid player IDs',
    $allPlayerIdsValid
);


teamProfileCheck(
    'All returned players have names',
    $allPlayerNamesPresent
);


echo "Team Players: "
    . count(
        $players
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO L
 * SUMMARY CONSISTENCY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Summary Consistency<br>";
echo "============================================<br>";


teamProfileCheck(
    'Profile Team Intelligence Score matches ranked summary',
    is_numeric(
        $ranking[
            'intelligence_score'
        ]
        ?? null
    )
    &&
    is_numeric(
        $selectedSummary[
            'intelligence_score'
        ]
        ?? null
    )
    &&
    abs(
        (float) $ranking[
            'intelligence_score'
        ]
        -
        (float) $selectedSummary[
            'intelligence_score'
        ]
    )
    < 0.01
);


teamProfileCheck(
    'Profile intelligence classification matches ranked summary',
    (
        $ranking[
            'intelligence_label'
        ]
        ?? null
    )
    ===
    (
        $selectedSummary[
            'intelligence_label'
        ]
        ?? null
    )
);


teamProfileCheck(
    'Profile overall strength matches ranked summary',
    is_numeric(
        $strength[
            'overall'
        ]
        ?? null
    )
    &&
    is_numeric(
        $selectedSummary[
            'strength_overall'
        ]
        ?? null
    )
    &&
    abs(
        (float) $strength[
            'overall'
        ]
        -
        (float) $selectedSummary[
            'strength_overall'
        ]
    )
    < 0.01
);


teamProfileCheck(
    'Profile fixture rating matches ranked summary',
    is_numeric(
        $fixtures[
            'rating'
        ]
        ?? null
    )
    &&
    is_numeric(
        $selectedSummary[
            'fixture_rating'
        ]
        ?? null
    )
    &&
    abs(
        (float) $fixtures[
            'rating'
        ]
        -
        (float) $selectedSummary[
            'fixture_rating'
        ]
    )
    < 0.01
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO M
 * INVALID TEAM
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario M: Invalid Team<br>";
echo "============================================<br>";


$invalidProfile =
    $service
        ->getTeamIntelligenceProfile(
            999999
        );


teamProfileCheck(
    'Invalid team request returns an array',
    is_array(
        $invalidProfile
    )
);


teamProfileCheck(
    'Invalid team request is rejected',
    (
        $invalidProfile[
            'status'
        ]
        ?? null
    )
    !==
    'success'
);


teamProfileCheck(
    'Invalid team request returns a message',
    !empty(
        $invalidProfile[
            'message'
        ]
        ?? null
    )
);


teamProfileCheck(
    'Invalid team profile contains no team data',
    empty(
        $invalidProfile[
            'team'
        ]
        ?? null
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO N
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario N: Performance<br>";
echo "============================================<br>";


teamProfileCheck(
    'Team Intelligence profile completes within 10 seconds',
    $runtime <= 10.0
);


echo "Measured Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Team Intelligence Profile Test Summary<br>";
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