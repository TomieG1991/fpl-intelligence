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
    'Replacement recommendations exist',
    isset(
        $replacementResult[
            'recommendations'
        ]
    )
    &&
    is_array(
        $replacementResult[
            'recommendations'
        ]
    )
);


testPass(
    'Best overall recommendation exists',
    array_key_exists(
        'best_overall',
        $replacementResult[
            'recommendations'
        ]
    )
);


testPass(
    'Best value recommendation exists',
    array_key_exists(
        'best_value',
        $replacementResult[
            'recommendations'
        ]
    )
);


testPass(
    'Best fixtures recommendation exists',
    array_key_exists(
        'best_fixtures',
        $replacementResult[
            'recommendations'
        ]
    )
);


testPass(
    'Safest pick recommendation exists',
    array_key_exists(
        'safest_pick',
        $replacementResult[
            'recommendations'
        ]
    )
);


testPass(
    'High-upside recommendation exists',
    array_key_exists(
        'high_upside',
        $replacementResult[
            'recommendations'
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
    
    testPass(
        'Replacement transfer decision exists',
        isset(
            $replacementCandidate[
                'transfer_decision'
            ]
        )
        &&
        is_array(
            $replacementCandidate[
                'transfer_decision'
            ]
        )
    );


    testPass(
        'Replacement transfer decision type exists',
        array_key_exists(
            'decision_type',
            $replacementCandidate[
                'transfer_decision'
            ]
        )
    );


    testPass(
        'Replacement transfer decision score exists',
        array_key_exists(
            'decision_score',
            $replacementCandidate[
                'transfer_decision'
            ]
        )
    );


    testPass(
        'Replacement transfer decision movements exist',
        isset(
            $replacementCandidate[
                'transfer_decision'
            ]['movements']
        )
        &&
        is_array(
            $replacementCandidate[
                'transfer_decision'
            ]['movements']
        )
    );
}

if (
    !empty(
        $replacementResult[
            'replacements'
        ]
    )
) {

    testPass(
        'Best overall recommendation matches top ranked replacement',
        (
            $replacementResult[
                'recommendations'
            ]['best_overall']['player_id']
            ?? null
        )
        ===
        (
            $replacementResult[
                'replacements'
            ][0]['player_id']
            ?? null
        )
    );
}

/*
 * ============================================================
 * SCENARIO J
 * TRANSFER DECISION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario J: Transfer Decision<br>";
echo "============================================<br>";


$transferCandidates =
    $service
        ->getRankedPlayers(
            2
        );


testPass(
    'At least two ranked players are available for transfer evaluation',
    count(
        $transferCandidates
    )
    >= 2
);


if (
    count(
        $transferCandidates
    )
    >= 2
) {

    $transferPlayerIdA =
        (int) (
            $transferCandidates[
                0
            ]['player_id']
            ?? 0
        );


    $transferPlayerIdB =
        (int) (
            $transferCandidates[
                1
            ]['player_id']
            ?? 0
        );


    $transferDecisionResult =
        $service
            ->evaluatePlayerTransfer(
                $transferPlayerIdA,
                $transferPlayerIdB
            );


    testPass(
        'Transfer decision returns an array',
        is_array(
            $transferDecisionResult
        )
    );


    testPass(
        'Transfer decision current player exists',
        isset(
            $transferDecisionResult[
                'current_player'
            ]
        )
    );


    testPass(
        'Transfer decision replacement exists',
        isset(
            $transferDecisionResult[
                'replacement'
            ]
        )
    );


    testPass(
        'Transfer decision movements exist',
        isset(
            $transferDecisionResult[
                'movements'
            ]
        )
        &&
        is_array(
            $transferDecisionResult[
                'movements'
            ]
        )
    );


    testPass(
        'Transfer intelligence movement exists',
        array_key_exists(
            'intelligence',
            $transferDecisionResult[
                'movements'
            ]
        )
    );


    testPass(
        'Transfer strength movement exists',
        array_key_exists(
            'strength',
            $transferDecisionResult[
                'movements'
            ]
        )
    );


    testPass(
        'Transfer value movement exists',
        array_key_exists(
            'value',
            $transferDecisionResult[
                'movements'
            ]
        )
    );


    testPass(
        'Transfer fixture movement exists',
        array_key_exists(
            'fixtures',
            $transferDecisionResult[
                'movements'
            ]
        )
    );


    testPass(
        'Transfer confidence movement exists',
        array_key_exists(
            'sample_confidence',
            $transferDecisionResult[
                'movements'
            ]
        )
    );


    testPass(
        'Transfer budget movement exists',
        array_key_exists(
            'budget',
            $transferDecisionResult[
                'movements'
            ]
        )
    );


    testPass(
        'Transfer decision score exists',
        array_key_exists(
            'decision_score',
            $transferDecisionResult
        )
    );


    testPass(
        'Transfer decision type exists',
        array_key_exists(
            'decision_type',
            $transferDecisionResult
        )
    );


    testPass(
        'Transfer decision summary exists',
        array_key_exists(
            'summary',
            $transferDecisionResult
        )
    );


    testPass(
        'Transfer decision preserves current player ID',
        (
            (int) (
                $transferDecisionResult[
                    'current_player'
                ]['player_id']
                ?? 0
            )
        )
        ===
        $transferPlayerIdA
    );


    testPass(
        'Transfer decision preserves replacement player ID',
        (
            (int) (
                $transferDecisionResult[
                    'replacement'
                ]['player_id']
                ?? 0
            )
        )
        ===
        $transferPlayerIdB
    );


    testPass(
        'Transfer decision type is not empty',
        !empty(
            $transferDecisionResult[
                'decision_type'
            ]
            ?? null
        )
    );
}

/*
 * ============================================================
 * SCENARIO K
 * TRANSFER COMBINATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario K: Transfer Combination<br>";
echo "============================================<br>";


/*
 * Find two valid same-position transfer pairs.
 *
 * We use player summaries so this test does not depend on
 * specific real-world player names or IDs.
 */

$combinationPlayers =
    $service
        ->getAllPlayerSummaries();


$transferPairA =
    null;


$transferPairB =
    null;


foreach (
    $combinationPlayers
    as $currentCandidate
) {

    $currentId =
        (int) (
            $currentCandidate[
                'player_id'
            ]
            ?? 0
        );


    $currentPosition =
        $currentCandidate[
            'position'
        ]
        ?? null;


    if (
        $currentId <= 0
        ||
        empty(
            $currentPosition
        )
    ) {
        continue;
    }


    foreach (
        $combinationPlayers
        as $replacementCandidate
    ) {

        $replacementId =
            (int) (
                $replacementCandidate[
                    'player_id'
                ]
                ?? 0
            );


        if (
            $replacementId <= 0
            ||
            $replacementId === $currentId
            ||
            (
                $replacementCandidate[
                    'position'
                ]
                ?? null
            )
            !==
            $currentPosition
        ) {
            continue;
        }


        if ($transferPairA === null) {

            $transferPairA = [

                'current' =>
                    $currentId,

                'replacement' =>
                    $replacementId
            ];

            break;
        }


        if (
            $currentId
            !==
            $transferPairA[
                'current'
            ]
            &&
            $currentId
            !==
            $transferPairA[
                'replacement'
            ]
            &&
            $replacementId
            !==
            $transferPairA[
                'current'
            ]
            &&
            $replacementId
            !==
            $transferPairA[
                'replacement'
            ]
        ) {

            $transferPairB = [

                'current' =>
                    $currentId,

                'replacement' =>
                    $replacementId
            ];

            break 2;
        }
    }
}


testPass(
    'Two valid transfer pairs are available',
    $transferPairA !== null
    &&
    $transferPairB !== null
);


if (
    $transferPairA !== null
    &&
    $transferPairB !== null
) {

    $combinationResult =
        $service
            ->evaluateTransferCombination(
                $transferPairA[
                    'current'
                ],
                $transferPairA[
                    'replacement'
                ],
                $transferPairB[
                    'current'
                ],
                $transferPairB[
                    'replacement'
                ]
            );


    testPass(
        'Transfer combination returns an array',
        is_array(
            $combinationResult
        )
    );


    testPass(
        'Combination transfer A exists',
        isset(
            $combinationResult[
                'transfer_a'
            ]
        )
    );


    testPass(
        'Combination transfer B exists',
        isset(
            $combinationResult[
                'transfer_b'
            ]
        )
    );


    testPass(
        'Combined movement data exists',
        isset(
            $combinationResult[
                'combined_movements'
            ]
        )
        &&
        is_array(
            $combinationResult[
                'combined_movements'
            ]
        )
    );


    testPass(
        'Combined Intelligence movement exists',
        array_key_exists(
            'intelligence',
            $combinationResult[
                'combined_movements'
            ]
        )
    );


    testPass(
        'Combined strength movement exists',
        array_key_exists(
            'strength',
            $combinationResult[
                'combined_movements'
            ]
        )
    );


    testPass(
        'Combined value movement exists',
        array_key_exists(
            'value',
            $combinationResult[
                'combined_movements'
            ]
        )
    );


    testPass(
        'Combined fixture movement exists',
        array_key_exists(
            'fixtures',
            $combinationResult[
                'combined_movements'
            ]
        )
    );


    testPass(
        'Combined confidence movement exists',
        array_key_exists(
            'sample_confidence',
            $combinationResult[
                'combined_movements'
            ]
        )
    );


    testPass(
        'Combined budget movement exists',
        array_key_exists(
            'budget',
            $combinationResult[
                'combined_movements'
            ]
        )
    );


    testPass(
        'Combination affordability exists',
        array_key_exists(
            'is_affordable',
            $combinationResult
        )
    );


    testPass(
        'Combination score exists',
        array_key_exists(
            'combination_score',
            $combinationResult
        )
    );


    testPass(
        'Combination classification exists',
        array_key_exists(
            'classification',
            $combinationResult
        )
    );


    testPass(
        'Combination summary exists',
        array_key_exists(
            'summary',
            $combinationResult
        )
    );


    testPass(
        'Combination classification is not empty',
        !empty(
            $combinationResult[
                'classification'
            ]
            ?? null
        )
    );


    testPass(
        'Transfer A preserves outgoing player ID',
        (
            (int) (
                $combinationResult[
                    'transfer_a'
                ]['current_player']['player_id']
                ?? 0
            )
        )
        ===
        (
            (int)
            $transferPairA[
                'current'
            ]
        )
    );


    testPass(
        'Transfer A preserves incoming player ID',
        (
            (int) (
                $combinationResult[
                    'transfer_a'
                ]['replacement']['player_id']
                ?? 0
            )
        )
        ===
        (
            (int)
            $transferPairA[
                'replacement'
            ]
        )
    );


    testPass(
        'Transfer B preserves outgoing player ID',
        (
            (int) (
                $combinationResult[
                    'transfer_b'
                ]['current_player']['player_id']
                ?? 0
            )
        )
        ===
        (
            (int)
            $transferPairB[
                'current'
            ]
        )
    );


    testPass(
        'Transfer B preserves incoming player ID',
        (
            (int) (
                $combinationResult[
                    'transfer_b'
                ]['replacement']['player_id']
                ?? 0
            )
        )
        ===
        (
            (int)
            $transferPairB[
                'replacement'
            ]
        )
    );
}

/*
 * ============================================================
 * TRANSFER OPTIMIZER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario L: Transfer Optimizer<br>";
echo "============================================<br>";


$optimizerResult =
    $service
        ->optimizeTransferCombination(
            $transferPairA[
                'current'
            ],
            $transferPairB[
                'current'
            ],
            0.0,
            5
        );


testPass(
    'Transfer optimizer returns an array',
    is_array(
        $optimizerResult
    )
);


testPass(
    'Transfer optimizer combinations exist',
    isset(
        $optimizerResult[
            'combinations'
        ]
    )
    &&
    is_array(
        $optimizerResult[
            'combinations'
        ]
    )
);


testPass(
    'Transfer optimizer count exists',
    array_key_exists(
        'count',
        $optimizerResult
    )
);


testPass(
    'Transfer optimizer total found exists',
    array_key_exists(
        'total_found',
        $optimizerResult
    )
);


testPass(
    'Transfer optimizer respects result limit',
    (
        $optimizerResult[
            'count'
        ]
        ?? 0
    )
    <= 5
);


foreach (
    $optimizerResult[
        'combinations'
    ]
    as $optimizerCombination
) {

    testPass(
        'Optimized combination has rank',
        isset(
            $optimizerCombination[
                'optimizer'
            ]['rank']
        )
    );


    testPass(
        'Optimized combination has budget after',
        array_key_exists(
            'budget_after',
            $optimizerCombination[
                'optimizer'
            ]
        )
    );


    testPass(
        'Optimized combination is affordable',
        (
            $optimizerCombination[
                'optimizer'
            ]['budget_after']
            ?? -1
        )
        >= 0
    );


    testPass(
        'Optimized combination classification exists',
        !empty(
            $optimizerCombination[
                'classification'
            ]
            ?? null
        )
    );
}

/*
 * ============================================================
 * SCENARIO M
 * INVALID PLAYER
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario M: Invalid Player Handling<br>";
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

testPass(
    'Transfer decision rejects zero current player ID',
    $service
        ->evaluatePlayerTransfer(
            0,
            $playerId
        )
    === null
);


testPass(
    'Transfer decision rejects zero replacement player ID',
    $service
        ->evaluatePlayerTransfer(
            $playerId,
            0
        )
    === null
);


testPass(
    'Transfer decision rejects negative player IDs',
    $service
        ->evaluatePlayerTransfer(
            -1,
            -2
        )
    === null
);


testPass(
    'Transfer decision rejects identical players',
    $service
        ->evaluatePlayerTransfer(
            $playerId,
            $playerId
        )
    === null
);


testPass(
    'Transfer decision rejects unknown current player',
    $service
        ->evaluatePlayerTransfer(
            999999999,
            $playerId
        )
    === null
);


testPass(
    'Transfer decision rejects unknown replacement player',
    $service
        ->evaluatePlayerTransfer(
            $playerId,
            999999999
        )
    === null
);

testPass(
    'Transfer combination rejects zero player ID',
    $service
        ->evaluateTransferCombination(
            0,
            $playerId,
            $playerId,
            1
        )
    === null
);


testPass(
    'Transfer combination rejects negative player ID',
    $service
        ->evaluateTransferCombination(
            -1,
            $playerId,
            $playerId,
            1
        )
    === null
);


testPass(
    'Transfer combination rejects identical first transfer',
    $service
        ->evaluateTransferCombination(
            $playerId,
            $playerId,
            1,
            2
        )
    === null
);


testPass(
    'Transfer combination rejects duplicate outgoing players',
    $service
        ->evaluateTransferCombination(
            $playerId,
            1,
            $playerId,
            2
        )
    === null
);


testPass(
    'Transfer combination rejects duplicate incoming players',
    $service
        ->evaluateTransferCombination(
            1,
            $playerId,
            2,
            $playerId
        )
    === null
);


testPass(
    'Transfer combination rejects unknown player',
    $service
        ->evaluateTransferCombination(
            999999999,
            $playerId,
            1,
            2
        )
    === null
);

testPass(
    'Transfer optimizer rejects zero player ID',
    $service
        ->optimizeTransferCombination(
            0,
            $playerId,
            0.0,
            5
        )
    === null
);


testPass(
    'Transfer optimizer rejects duplicate outgoing players',
    $service
        ->optimizeTransferCombination(
            $playerId,
            $playerId,
            0.0,
            5
        )
    === null
);


testPass(
    'Transfer optimizer rejects negative bank',
    $service
        ->optimizeTransferCombination(
            $transferPairA[
                'current'
            ],
            $transferPairB[
                'current'
            ],
            -1.0,
            5
        )
    === null
);


testPass(
    'Transfer optimizer rejects zero limit',
    $service
        ->optimizeTransferCombination(
            $transferPairA[
                'current'
            ],
            $transferPairB[
                'current'
            ],
            0.0,
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