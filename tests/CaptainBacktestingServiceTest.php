<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Captain Backtesting Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function captainBacktestingTestResult(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: "
            . htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        return;
    }


    $failed++;

    echo "FAIL: "
        . htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


/*
 * ============================================================
 * CLASS AVAILABILITY
 * ============================================================
 */

$classExists =
    class_exists(
        'CaptainBacktestingService'
    );


captainBacktestingTestResult(
    $classExists,
    'CaptainBacktestingService exists.'
);


if (!$classExists) {

    echo "<br>";
    echo "============================================<br>";
    echo "TEST SUMMARY<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br>";

    echo "RESULT: TESTS FAILED ❌<br>";

    exit;
}


$service =
    new CaptainBacktestingService();


captainBacktestingTestResult(
    $service instanceof CaptainBacktestingService,
    'CaptainBacktestingService can be constructed.'
);


/*
 * ============================================================
 * PRESERVED CAPTAIN INTELLIGENCE RANKINGS
 * ============================================================
 *
 * These represent the historical Captain Intelligence candidate
 * universe preserved before the deadline.
 *
 * The ordering deliberately resembles production:
 *
 * - rank 1 = recommended captain
 * - rank 2 = recommended vice-captain
 * - remaining rows = other evaluated captain candidates
 *
 * Player 114 is deliberately NOT part of the conceptual
 * recommended Starting XI used by our earlier test design.
 *
 * However, Captain Intelligence considered player 114, so
 * historical Captain Backtesting must retain that player as a
 * legitimate comparison candidate.
 * ============================================================
 */

$captainRankings =
    [
        [
            'player_id' => 108,
            'name' => 'Player 108',
            'rank' => 1,
            'captain_score' => 90.0
        ],
        [
            'player_id' => 105,
            'name' => 'Player 105',
            'rank' => 2,
            'captain_score' => 88.0
        ],
        [
            'player_id' => 109,
            'name' => 'Player 109',
            'rank' => 3,
            'captain_score' => 84.0
        ],
        [
            'player_id' => 106,
            'name' => 'Player 106',
            'rank' => 4,
            'captain_score' => 82.0
        ],
        [
            'player_id' => 103,
            'name' => 'Player 103',
            'rank' => 5,
            'captain_score' => 80.0
        ],
        [
            'player_id' => 101,
            'name' => 'Player 101',
            'rank' => 6,
            'captain_score' => 78.0
        ],
        [
            'player_id' => 102,
            'name' => 'Player 102',
            'rank' => 7,
            'captain_score' => 76.0
        ],
        [
            'player_id' => 104,
            'name' => 'Player 104',
            'rank' => 8,
            'captain_score' => 74.0
        ],
        [
            'player_id' => 107,
            'name' => 'Player 107',
            'rank' => 9,
            'captain_score' => 72.0
        ],
        [
            'player_id' => 110,
            'name' => 'Player 110',
            'rank' => 10,
            'captain_score' => 70.0
        ],
        [
            'player_id' => 111,
            'name' => 'Player 111',
            'rank' => 11,
            'captain_score' => 68.0
        ],
        [
            'player_id' => 112,
            'name' => 'Player 112',
            'rank' => 12,
            'captain_score' => 66.0
        ],
        [
            'player_id' => 113,
            'name' => 'Player 113',
            'rank' => 13,
            'captain_score' => 64.0
        ],
        [
            'player_id' => 114,
            'name' => 'Player 114',
            'rank' => 14,
            'captain_score' => 62.0
        ],
        [
            'player_id' => 115,
            'name' => 'Player 115',
            'rank' => 15,
            'captain_score' => 60.0
        ]
    ];


/*
 * ============================================================
 * CONCEPTUAL RECOMMENDED STARTING XI
 * ============================================================
 *
 * This is deliberately NOT supplied to CaptainBacktestingService.
 *
 * It exists only to prove Scenario D:
 *
 * Player 114 was outside the recommended Starting XI but was
 * still part of the preserved Captain Intelligence rankings.
 * ============================================================
 */

$recommendedStartingXIPlayerIds =
    [
        101,
        102,
        103,
        104,
        105,
        106,
        107,
        108,
        109,
        110,
        111
    ];


/*
 * ============================================================
 * AUTHORITATIVE COMPLETED-GAMEWEEK OUTCOMES
 * ============================================================
 */

$playerOutcomes =
    [
        [
            'player_id' => 101,
            'total_points' => 2,
            'minutes' => 90
        ],
        [
            'player_id' => 102,
            'total_points' => 4,
            'minutes' => 90
        ],
        [
            'player_id' => 103,
            'total_points' => 5,
            'minutes' => 90
        ],
        [
            'player_id' => 104,
            'total_points' => 3,
            'minutes' => 90
        ],
        [
            'player_id' => 105,
            'total_points' => 12,
            'minutes' => 90
        ],
        [
            'player_id' => 106,
            'total_points' => 6,
            'minutes' => 90
        ],
        [
            'player_id' => 107,
            'total_points' => 1,
            'minutes' => 90
        ],
        [
            'player_id' => 108,
            'total_points' => 8,
            'minutes' => 90
        ],
        [
            'player_id' => 109,
            'total_points' => 7,
            'minutes' => 90
        ],
        [
            'player_id' => 110,
            'total_points' => 0,
            'minutes' => 0
        ],
        [
            'player_id' => 111,
            'total_points' => -1,
            'minutes' => 90
        ],
        [
            'player_id' => 112,
            'total_points' => 4,
            'minutes' => 90
        ],
        [
            'player_id' => 113,
            'total_points' => 3,
            'minutes' => 90
        ],
        [
            'player_id' => 114,
            'total_points' => 15,
            'minutes' => 90
        ],
        [
            'player_id' => 115,
            'total_points' => 2,
            'minutes' => 90
        ]
    ];


/*
 * ============================================================
 * PRESERVED RECOMMENDED CAPTAIN
 * ============================================================
 */

$captain =
    [
        'player_id' => 108,
        'name' => 'Player 108',
        'rank' => 1,
        'captain_score' => 90.0
    ];


/*
 * ============================================================
 * SCENARIO A
 * EMPTY EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Empty Evidence<br>";
echo "============================================<br>";


captainBacktestingTestResult(
    $service->evaluate(
        [],
        $captainRankings,
        $playerOutcomes
    )
    ===
    [],
    'Empty captain evidence returns no captain evaluation.'
);


captainBacktestingTestResult(
    $service->evaluate(
        $captain,
        [],
        $playerOutcomes
    )
    ===
    [],
    'Empty Captain Intelligence rankings return no captain evaluation.'
);


captainBacktestingTestResult(
    $service->evaluate(
        $captain,
        $captainRankings,
        []
    )
    ===
    [],
    'Empty outcome evidence returns no captain evaluation.'
);


/*
 * ============================================================
 * SCENARIO B
 * RECOMMENDED CAPTAIN REALISED RETURN
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Recommended Captain Realised Return<br>";
echo "============================================<br>";


$result =
    $service->evaluate(
        $captain,
        $captainRankings,
        $playerOutcomes
    );


captainBacktestingTestResult(
    (
        $result[
            'captain_player_id'
        ]
        ?? null
    )
    ===
    108,
    'Recommended captain identity is preserved.'
);


captainBacktestingTestResult(
    (
        $result[
            'captain_actual_points'
        ]
        ?? null
    )
    ===
    8,
    'Recommended captain realised points use authoritative outcome evidence.'
);


captainBacktestingTestResult(
    (
        $result[
            'captain_actual_minutes'
        ]
        ?? null
    )
    ===
    90,
    'Recommended captain realised minutes use authoritative outcome evidence.'
);


/*
 * ============================================================
 * SCENARIO C
 * BEST REALISED CAPTAIN-INTELLIGENCE ALTERNATIVE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Best Realised Captain Intelligence Alternative<br>";
echo "============================================<br>";


captainBacktestingTestResult(
    (
        $result[
            'best_alternative_player_id'
        ]
        ?? null
    )
    ===
    114,
    'Best realised alternative is selected from preserved Captain Intelligence rankings.'
);


captainBacktestingTestResult(
    (
        $result[
            'best_alternative_actual_points'
        ]
        ?? null
    )
    ===
    15,
    'Best realised Captain Intelligence alternative retains authoritative actual points.'
);


/*
 * ============================================================
 * SCENARIO D
 * ALTERNATIVE MAY SIT OUTSIDE RECOMMENDED STARTING XI
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Alternative Outside Recommended Starting XI<br>";
echo "============================================<br>";


captainBacktestingTestResult(
    !in_array(
        114,
        $recommendedStartingXIPlayerIds,
        true
    ),
    'Synthetic best alternative is deliberately outside the conceptual recommended Starting XI.'
);


captainBacktestingTestResult(
    (
        $result[
            'best_alternative_player_id'
        ]
        ?? null
    )
    ===
    114,
    'Captain Backtesting retains a Captain Intelligence candidate even when outside the recommended Starting XI.'
);


/*
 * ============================================================
 * SCENARIO E
 * CAPTAIN POINTS LOST
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Captain Points Lost<br>";
echo "============================================<br>";


captainBacktestingTestResult(
    (
        $result[
            'captain_points_lost'
        ]
        ?? null
    )
    ===
    7,
    'Captain points lost equals best Captain Intelligence alternative points minus captain points.'
);


/*
 * ============================================================
 * SCENARIO F
 * PERFECT CAPTAIN RECOMMENDATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Perfect Captain Recommendation<br>";
echo "============================================<br>";


$perfectCaptain =
    [
        'player_id' => 114,
        'name' => 'Player 114',
        'rank' => 14,
        'captain_score' => 62.0
    ];


$perfectResult =
    $service->evaluate(
        $perfectCaptain,
        $captainRankings,
        $playerOutcomes
    );


captainBacktestingTestResult(
    (
        $perfectResult[
            'captain_points_lost'
        ]
        ?? null
    )
    ===
    0,
    'Highest realised Captain Intelligence candidate records zero captain points lost.'
);


/*
 * ============================================================
 * SCENARIO G
 * CAPTAIN MUST BELONG TO PRESERVED CAPTAIN RANKINGS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Captain Must Belong To Preserved Rankings<br>";
echo "============================================<br>";


$outsideCaptain =
    [
        'player_id' => 999,
        'name' => 'Outside Player'
    ];


captainBacktestingTestResult(
    $service->evaluate(
        $outsideCaptain,
        $captainRankings,
        $playerOutcomes
    )
    ===
    [],
    'Captain outside preserved Captain Intelligence rankings prevents captain comparison.'
);


/*
 * ============================================================
 * SCENARIO H
 * CAPTAIN OUTCOME REQUIRED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Captain Outcome Required<br>";
echo "============================================<br>";


$outcomesWithoutCaptain =
    array_values(
        array_filter(
            $playerOutcomes,
            static function (
                array $outcome
            ): bool {

                return (
                    $outcome[
                        'player_id'
                    ]
                    ?? null
                )
                !==
                108;
            }
        )
    );


captainBacktestingTestResult(
    $service->evaluate(
        $captain,
        $captainRankings,
        $outcomesWithoutCaptain
    )
    ===
    [],
    'Missing captain outcome prevents captain comparison.'
);


/*
 * ============================================================
 * SCENARIO I
 * COMPLETE CAPTAIN-RANKING OUTCOME EVIDENCE REQUIRED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Complete Captain Ranking Outcome Evidence<br>";
echo "============================================<br>";


$outcomesWithoutAlternative =
    array_values(
        array_filter(
            $playerOutcomes,
            static function (
                array $outcome
            ): bool {

                return (
                    $outcome[
                        'player_id'
                    ]
                    ?? null
                )
                !==
                114;
            }
        )
    );


captainBacktestingTestResult(
    $service->evaluate(
        $captain,
        $captainRankings,
        $outcomesWithoutAlternative
    )
    ===
    [],
    'Incomplete Captain Intelligence ranking outcomes prevent fair captain comparison.'
);


/*
 * ============================================================
 * SCENARIO J
 * ZERO-MINUTE OUTCOME REMAINS VALID
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Zero-Minute Captain Outcome<br>";
echo "============================================<br>";


$zeroMinuteCaptain =
    [
        'player_id' => 110,
        'name' => 'Player 110'
    ];


$zeroMinuteResult =
    $service->evaluate(
        $zeroMinuteCaptain,
        $captainRankings,
        $playerOutcomes
    );


captainBacktestingTestResult(
    (
        $zeroMinuteResult[
            'captain_actual_points'
        ]
        ?? null
    )
    ===
    0,
    'Zero-minute captain remains valid realised captain evidence.'
);


captainBacktestingTestResult(
    (
        $zeroMinuteResult[
            'captain_actual_minutes'
        ]
        ?? null
    )
    ===
    0,
    'Zero realised captain minutes are preserved.'
);


captainBacktestingTestResult(
    (
        $zeroMinuteResult[
            'captain_points_lost'
        ]
        ?? null
    )
    ===
    15,
    'Zero-minute captain is compared against the full preserved Captain Intelligence candidate universe.'
);


/*
 * ============================================================
 * SCENARIO K
 * NEGATIVE POINTS REMAIN VALID
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Negative Captain Points<br>";
echo "============================================<br>";


$negativeCaptain =
    [
        'player_id' => 111,
        'name' => 'Player 111'
    ];


$negativeResult =
    $service->evaluate(
        $negativeCaptain,
        $captainRankings,
        $playerOutcomes
    );


captainBacktestingTestResult(
    (
        $negativeResult[
            'captain_actual_points'
        ]
        ?? null
    )
    ===
    -1,
    'Negative captain points remain valid realised evidence.'
);


captainBacktestingTestResult(
    (
        $negativeResult[
            'captain_points_lost'
        ]
        ?? null
    )
    ===
    16,
    'Captain points lost correctly handles negative realised captain points.'
);


/*
 * ============================================================
 * SCENARIO L
 * DUPLICATE CAPTAIN-RANKING IDENTITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario L: Duplicate Captain Ranking Identity<br>";
echo "============================================<br>";


$duplicateRankings =
    $captainRankings;


$duplicateRankings[
    14
] =
    $captainRankings[
        0
    ];


captainBacktestingTestResult(
    $service->evaluate(
        $captain,
        $duplicateRankings,
        $playerOutcomes
    )
    ===
    [],
    'Duplicate Captain Intelligence ranking identity prevents captain comparison.'
);


/*
 * ============================================================
 * SCENARIO M
 * RANKING UNIVERSE DOES NOT REQUIRE EXACTLY FIFTEEN PLAYERS
 * ============================================================
 *
 * Production Captain Intelligence can reject individual players
 * while still producing a successful recommendation when enough
 * usable captain candidates remain.
 *
 * Backtesting should therefore evaluate the preserved candidate
 * universe exactly as captured rather than manufacture missing
 * players or require a hard-coded 15.
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario M: Preserved Ranking Universe Size<br>";
echo "============================================<br>";


$partialRankings =
    [
        $captainRankings[
            0
        ],
        $captainRankings[
            1
        ],
        $captainRankings[
            13
        ]
    ];


$partialResult =
    $service->evaluate(
        $captain,
        $partialRankings,
        $playerOutcomes
    );


captainBacktestingTestResult(
    !empty(
        $partialResult
    ),
    'A preserved Captain Intelligence ranking universe smaller than fifteen remains evaluable.'
);


captainBacktestingTestResult(
    (
        $partialResult[
            'best_alternative_player_id'
        ]
        ?? null
    )
    ===
    114,
    'Partial preserved rankings still identify the strongest realised candidate actually preserved.'
);


/*
 * ============================================================
 * SCENARIO N
 * AT LEAST ONE ALTERNATIVE REQUIRED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario N: At Least One Captain Alternative Required<br>";
echo "============================================<br>";


$captainOnlyRankings =
    [
        $captainRankings[
            0
        ]
    ];


captainBacktestingTestResult(
    $service->evaluate(
        $captain,
        $captainOnlyRankings,
        $playerOutcomes
    )
    ===
    [],
    'Captain comparison requires at least one preserved alternative candidate.'
);


/*
 * ============================================================
 * SCENARIO O
 * INVALID RANKING PLAYER ID
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario O: Invalid Captain Ranking Player ID<br>";
echo "============================================<br>";


$invalidRankings =
    $captainRankings;


$invalidRankings[
    14
][
    'player_id'
] =
    0;


captainBacktestingTestResult(
    $service->evaluate(
        $captain,
        $invalidRankings,
        $playerOutcomes
    )
    ===
    [],
    'Invalid preserved Captain Intelligence player identity prevents captain comparison.'
);


/*
 * ============================================================
 * SCENARIO P
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario P: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


$originalCaptain =
    $captain;


$originalCaptainRankings =
    $captainRankings;


$originalPlayerOutcomes =
    $playerOutcomes;


$service->evaluate(
    $captain,
    $captainRankings,
    $playerOutcomes
);


captainBacktestingTestResult(
    $captain
        ===
        $originalCaptain,
    'Preserved captain evidence is not mutated.'
);


captainBacktestingTestResult(
    $captainRankings
        ===
        $originalCaptainRankings,
    'Preserved Captain Intelligence rankings are not mutated.'
);


captainBacktestingTestResult(
    $playerOutcomes
        ===
        $originalPlayerOutcomes,
    'Authoritative outcome evidence is not mutated.'
);


/*
 * ============================================================
 * SCENARIO Q
 * CAPTAIN BACKTESTING BOUNDARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario Q: Captain Backtesting Boundary<br>";
echo "============================================<br>";


captainBacktestingTestResult(
    !array_key_exists(
        'accuracy_score',
        $result
    ),
    'Captain evaluator does not manufacture an accuracy score.'
);


captainBacktestingTestResult(
    !array_key_exists(
        'doubled_captain_points',
        $result
    ),
    'Captain evaluator does not manufacture doubled FPL points.'
);


captainBacktestingTestResult(
    !array_key_exists(
        'vice_captain_result',
        $result
    ),
    'Captain evaluator does not yet evaluate vice-captain fallback.'
);


captainBacktestingTestResult(
    !array_key_exists(
        'transfer_result',
        $result
    ),
    'Captain evaluator does not evaluate transfer recommendations.'
);


captainBacktestingTestResult(
    !array_key_exists(
        'overall_score',
        $result
    ),
    'Captain evaluator does not manufacture overall backtesting score.'
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "TEST SUMMARY<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br>";


if (
    $failed === 0
) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}