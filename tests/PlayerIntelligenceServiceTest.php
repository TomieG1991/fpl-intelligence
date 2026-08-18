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


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

echo "============================================<br>";
echo "Player Intelligence Service Test<br>";
echo "============================================<br><br>";


try {

    $database =
        new Database();


    $service =
        new PlayerIntelligenceService(
            $database->getConnection()
        );


    $players =
        $service
            ->getAllPlayerSummaries();


} catch (Throwable $exception) {

    echo "SETUP FAILED ❌<br>";

    echo htmlspecialchars(
        $exception->getMessage()
    );

    exit;
}


/*
 * ============================================================
 * SCENARIO A
 * PLAYER SUMMARIES
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Player Summaries<br>";
echo "============================================<br>";


$summaries =
    $service
        ->getAllPlayerSummaries();


testPass(
    'Player summaries return an array',
    is_array(
        $summaries
    )
);


testPass(
    'Player summaries are not empty',
    !empty(
        $summaries
    )
);


/*
 * Find a valid player for the remaining tests.
 */

$testPlayer =
    null;


foreach ($summaries as $summary) {

    if (
        isset(
            $summary['player_id']
        )
        &&
        (int) $summary['player_id'] > 0
    ) {

        $testPlayer =
            $summary;

        break;
    }
}


testPass(
    'A valid test player was found',
    $testPlayer !== null
);


/*
 * ============================================================
 * SUMMARY ASSESSMENT DATA
 * ============================================================
 */

if ($testPlayer !== null) {

    testPass(
        'Player summary assessment verdict exists',
        array_key_exists(
            'assessment_verdict',
            $testPlayer
        )
    );


    testPass(
        'Player summary assessment verdict key exists',
        array_key_exists(
            'assessment_verdict_key',
            $testPlayer
        )
    );
}

if ($testPlayer === null) {
    echo "<br>Unable to continue without a valid player.<br>";
    
    exit;
}

$playerId =
    (int) $testPlayer['player_id'];


/*
 * ============================================================
 * SCENARIO B
 * COMPLETE PLAYER PROFILE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Complete Player Profile<br>";
echo "============================================<br>";


$profile =
    $service->getPlayerProfile(
        $playerId
    );


testPass(
    'Player profile returns an array',
    is_array(
        $profile
    )
);


testPass(
    'Player section exists',
    isset(
        $profile['player']
    )
);


testPass(
    'Team section exists',
    isset(
        $profile['team']
    )
);


testPass(
    'Performance section exists',
    isset(
        $profile['performance']
    )
);


testPass(
    'Strength section exists',
    isset(
        $profile['strength']
    )
);


testPass(
    'Value section exists',
    isset(
        $profile['value']
    )
);


testPass(
    'Availability section exists',
    isset(
        $profile['availability']
    )
);


testPass(
    'Intelligence section exists',
    isset(
        $profile['intelligence']
    )
);


testPass(
    'Summary section exists',
    isset(
        $profile['summary']
    )
);


testPass(
    'Fixtures section exists',
    isset(
        $profile['fixtures']
    )
);

testPass(
    'Assessment section exists',
    isset(
        $profile['assessment']
    )
);


/*
 * ============================================================
 * SCENARIO C
 * PLAYER IDENTITY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Player Identity<br>";
echo "============================================<br>";


testPass(
    'Player ID matches requested player',
    (
        (int) (
            $profile[
                'player'
            ]['player_id']
            ?? 0
        )
    )
    ===
    $playerId
);


testPass(
    'Player name exists',
    !empty(
        $profile[
            'player'
        ]['name']
        ?? null
    )
);


testPass(
    'Player position exists',
    !empty(
        $profile[
            'player'
        ]['position']
        ?? null
    )
);


/*
 * ============================================================
 * SCENARIO D
 * TEAM INFORMATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Team Information<br>";
echo "============================================<br>";


testPass(
    'Team ID exists',
    (
        (int) (
            $profile[
                'team'
            ]['team_id']
            ?? 0
        )
    )
    > 0
);


testPass(
    'Team name exists',
    !empty(
        $profile[
            'team'
        ]['name']
        ?? null
    )
);


/*
 * ============================================================
 * SCENARIO E
 * INTELLIGENCE INFORMATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Intelligence Information<br>";
echo "============================================<br>";


testPass(
    'Summary strength rating exists when available',
    array_key_exists(
        'strength_rating',
        $profile['summary']
    )
);


testPass(
    'Summary fixture rating exists',
    array_key_exists(
        'fixture_rating',
        $profile['summary']
    )
);


testPass(
    'Summary intelligence score exists',
    array_key_exists(
        'intelligence_score',
        $profile['summary']
    )
);


testPass(
    'Summary intelligence label exists',
    array_key_exists(
        'intelligence_label',
        $profile['summary']
    )
);


/*
 * ============================================================
 * SCENARIO F
 * FIXTURE PROFILE
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Fixture Profile<br>";
echo "============================================<br>";


testPass(
    'Fixture rating field exists',
    array_key_exists(
        'rating',
        $profile['fixtures']
    )
);


testPass(
    'Rolling averages exist',
    isset(
        $profile[
            'fixtures'
        ]['rolling_averages']
    )
);


testPass(
    'Upcoming fixtures exist',
    isset(
        $profile[
            'fixtures'
        ]['upcoming']
    )
    &&
    is_array(
        $profile[
            'fixtures'
        ]['upcoming']
    )
);


testPass(
    'Fixture count matches upcoming fixture array',
    (
        (int) (
            $profile[
                'fixtures'
            ]['fixture_count']
            ?? -1
        )
    )
    ===
    count(
        $profile[
            'fixtures'
        ]['upcoming']
    )
);

/*
 * ============================================================
 * SCENARIO G
 * PLAYER ASSESSMENT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario G: Player Assessment<br>";
echo "============================================<br>";


testPass(
    'Assessment verdict exists',
    array_key_exists(
        'verdict',
        $profile['assessment']
    )
);


testPass(
    'Assessment verdict key exists',
    array_key_exists(
        'verdict_key',
        $profile['assessment']
    )
);


testPass(
    'Assessment summary exists',
    array_key_exists(
        'summary',
        $profile['assessment']
    )
);


testPass(
    'Assessment strengths exist',
    isset(
        $profile[
            'assessment'
        ]['strengths']
    )
    &&
    is_array(
        $profile[
            'assessment'
        ]['strengths']
    )
);


testPass(
    'Assessment concerns exist',
    isset(
        $profile[
            'assessment'
        ]['concerns']
    )
    &&
    is_array(
        $profile[
            'assessment'
        ]['concerns']
    )
);


testPass(
    'Assessment components exist',
    isset(
        $profile[
            'assessment'
        ]['components']
    )
    &&
    is_array(
        $profile[
            'assessment'
        ]['components']
    )
);

/*
 * ============================================================
 * SCENARIO H
 * PLAYER COMPARISON
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario H: Player Comparison<br>";
echo "============================================<br>";


$comparisonPlayers =
    $service
        ->getRankedPlayers(
            2
        );


testPass(
    'At least two ranked players are available for comparison',
    count(
        $comparisonPlayers
    )
    >= 2
);


if (
    count(
        $comparisonPlayers
    )
    >= 2
) {

    $comparisonPlayerIdA =
        (int) (
            $comparisonPlayers[
                0
            ]['player_id']
            ?? 0
        );


    $comparisonPlayerIdB =
        (int) (
            $comparisonPlayers[
                1
            ]['player_id']
            ?? 0
        );


    $playerComparison =
        $service
            ->comparePlayers(
                $comparisonPlayerIdA,
                $comparisonPlayerIdB
            );


    testPass(
        'Player comparison returns an array',
        is_array(
            $playerComparison
        )
    );


    testPass(
        'Comparison player A exists',
        isset(
            $playerComparison[
                'player_a'
            ]
        )
    );


    testPass(
        'Comparison player B exists',
        isset(
            $playerComparison[
                'player_b'
            ]
        )
    );


    testPass(
        'Comparison metrics exist',
        isset(
            $playerComparison[
                'metrics'
            ]
        )
        &&
        is_array(
            $playerComparison[
                'metrics'
            ]
        )
    );


    testPass(
        'Comparison intelligence metric exists',
        isset(
            $playerComparison[
                'metrics'
            ]['intelligence']
        )
    );


    testPass(
        'Comparison strength metric exists',
        isset(
            $playerComparison[
                'metrics'
            ]['strength']
        )
    );


    testPass(
        'Comparison value metric exists',
        isset(
            $playerComparison[
                'metrics'
            ]['value']
        )
    );


    testPass(
        'Comparison fixture metric exists',
        isset(
            $playerComparison[
                'metrics'
            ]['fixtures']
        )
    );


    testPass(
        'Comparison availability metric exists',
        isset(
            $playerComparison[
                'metrics'
            ]['availability']
        )
    );


    testPass(
        'Comparison sample confidence metric exists',
        isset(
            $playerComparison[
                'metrics'
            ]['sample_confidence']
        )
    );


    testPass(
        'Comparison metric win counts exist',
        isset(
            $playerComparison[
                'metric_wins'
            ]
        )
    );


    testPass(
        'Comparison overall winner exists',
        array_key_exists(
            'overall_winner',
            $playerComparison
        )
    );


    testPass(
        'Comparison overall difference exists',
        array_key_exists(
            'overall_difference',
            $playerComparison
        )
    );


    testPass(
        'Comparison preserves player A ID',
        (
            (int) (
                $playerComparison[
                    'player_a'
                ]['player_id']
                ?? 0
            )
        )
        ===
        $comparisonPlayerIdA
    );


    testPass(
        'Comparison preserves player B ID',
        (
            (int) (
                $playerComparison[
                    'player_b'
                ]['player_id']
                ?? 0
            )
        )
        ===
        $comparisonPlayerIdB
    );
}

/*
 * ============================================================
 * SCENARIO I
 * PLAYER REPLACEMENTS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Player Replacements<br>";
echo "============================================<br>";


$replacementBudget =
    isset(
        $profile[
            'summary'
        ]['price']
    )
    &&
    is_numeric(
        $profile[
            'summary'
        ]['price']
    )
        ? (float)
            $profile[
                'summary'
            ]['price']
        : 15.0;


$replacementResult =
    $service
        ->findPlayerReplacements(
            $playerId,
            $replacementBudget,
            5
        );


testPass(
    'Replacement search returns an array',
    is_array(
        $replacementResult
    )
);


testPass(
    'Replacement current player exists',
    isset(
        $replacementResult[
            'current_player'
        ]
    )
);


testPass(
    'Replacement search preserves current player ID',
    (
        (int) (
            $replacementResult[
                'current_player'
            ]['player_id']
            ?? 0
        )
    )
    ===
    $playerId
);


testPass(
    'Replacement max price is preserved',
    (
        (float) (
            $replacementResult[
                'max_price'
            ]
            ?? -1
        )
    )
    ===
    round(
        $replacementBudget,
        2
    )
);


testPass(
    'Replacement limit is preserved',
    (
        (int) (
            $replacementResult[
                'limit'
            ]
            ?? 0
        )
    )
    === 5
);


testPass(
    'Replacement list exists',
    isset(
        $replacementResult[
            'replacements'
        ]
    )
    &&
    is_array(
        $replacementResult[
            'replacements'
        ]
    )
);


testPass(
    'Replacement count matches result array',
    (
        (int) (
            $replacementResult[
                'replacement_count'
            ]
            ?? -1
        )
    )
    ===
    count(
        $replacementResult[
            'replacements'
        ]
    )
);


testPass(
    'Replacement result respects requested limit',
    count(
        $replacementResult[
            'replacements'
        ]
    )
    <= 5
);


foreach (
    $replacementResult[
        'replacements'
    ]
    as $replacementCandidate
) {

    testPass(
        'Replacement candidate preserves player ID',
        (
            (int) (
                $replacementCandidate[
                    'player_id'
                ]
                ?? 0
            )
        )
        > 0
    );


    testPass(
        'Replacement candidate matches current position',
        (
            $replacementCandidate[
                'position'
            ]
            ?? null
        )
        ===
        (
            $replacementResult[
                'current_player'
            ]['position']
            ?? null
        )
    );


    testPass(
        'Replacement candidate respects max price',
        (
            (float) (
                $replacementCandidate[
                    'price'
                ]
                ?? PHP_FLOAT_MAX
            )
        )
        <=
        $replacementBudget
    );


    testPass(
        'Replacement candidate is not current player',
        (
            (int) (
                $replacementCandidate[
                    'player_id'
                ]
                ?? 0
            )
        )
        !==
        $playerId
    );


    testPass(
        'Replacement type exists',
        array_key_exists(
            'replacement_type',
            $replacementCandidate
        )
    );


    testPass(
        'Replacement summary exists',
        array_key_exists(
            'replacement_summary',
            $replacementCandidate
        )
    );
}


/*
 * ============================================================
 * SCENARIO I
 * INVALID PLAYER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario I: Invalid Player Handling<br>";
echo "============================================<br>";


testPass(
    'Zero player ID returns null',
    $service->getPlayerProfile(
        0
    )
    === null
);


testPass(
    'Negative player ID returns null',
    $service->getPlayerProfile(
        -1
    )
    === null
);


testPass(
    'Unknown player ID returns null',
    $service->getPlayerProfile(
        999999999
    )
    === null
);

testPass(
    'Comparison rejects zero first player ID',
    $service->comparePlayers(
        0,
        1
    )
    === null
);


testPass(
    'Comparison rejects zero second player ID',
    $service->comparePlayers(
        1,
        0
    )
    === null
);


testPass(
    'Comparison rejects negative player IDs',
    $service->comparePlayers(
        -1,
        -2
    )
    === null
);


testPass(
    'Comparison rejects identical player IDs',
    $service->comparePlayers(
        $playerId,
        $playerId
    )
    === null
);


testPass(
    'Comparison returns null when first player is unknown',
    $service->comparePlayers(
        999999999,
        $playerId
    )
    === null
);


testPass(
    'Comparison returns null when second player is unknown',
    $service->comparePlayers(
        $playerId,
        999999999
    )
    === null
);

testPass(
    'Replacement search rejects zero player ID',
    $service
        ->findPlayerReplacements(
            0,
            10.0,
            5
        )
    === null
);


testPass(
    'Replacement search rejects negative player ID',
    $service
        ->findPlayerReplacements(
            -1,
            10.0,
            5
        )
    === null
);


testPass(
    'Replacement search rejects unknown player',
    $service
        ->findPlayerReplacements(
            999999999,
            10.0,
            5
        )
    === null
);


testPass(
    'Replacement search rejects negative budget',
    $service
        ->findPlayerReplacements(
            $playerId,
            -1,
            5
        )
    === null
);


testPass(
    'Replacement search rejects zero limit',
    $service
        ->findPlayerReplacements(
            $playerId,
            10.0,
            0
        )
    === null
);




/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Player Intelligence Service Test Summary<br>";
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