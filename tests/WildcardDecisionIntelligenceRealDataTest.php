<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function wildcardDecisionRealCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        return;
    }


    $failed++;

    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';
}


function wildcardDecisionRealHeading(
    string $heading
): void {

    echo
        '<br>'
        . '============================================<br>'
        . htmlspecialchars(
            $heading,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>'
        . '============================================<br>';
}


/*
 * ============================================================
 * HEADER
 * ============================================================
 */

echo
    '============================================<br>';

echo
    'Wildcard Decision Intelligence Real Data Test<br>';

echo
    '============================================<br>';


/*
 * ============================================================
 * SCENARIO A: REAL SERVICE SETUP
 * ============================================================
 */

wildcardDecisionRealHeading(
    'Scenario A: Real Service Setup'
);


try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $playerRepository =
        new PlayerRepository(
            $db
        );


    $playerIntelligenceService =
        new PlayerIntelligenceService(
            $db
        );


    $squadHorizonIntelligence =
        new SquadHorizonIntelligence();


    $squadHorizonService =
        new SquadHorizonIntelligenceService(
            $playerRepository,
            $playerIntelligenceService,
            $squadHorizonIntelligence
        );


    $wildcardOptimizer =
        new WildcardOptimizer();


    $wildcardHorizonService =
        new WildcardHorizonIntelligenceService(
            $wildcardOptimizer,
            $squadHorizonService
        );


    $wildcardTimingIntelligence =
        new WildcardTimingIntelligence();


    $wildcardTimingService =
        new WildcardTimingIntelligenceService(
            $wildcardTimingIntelligence
        );


    $wildcardDecisionService =
        new WildcardDecisionIntelligenceService(
            $squadHorizonService,
            $wildcardHorizonService,
            $wildcardTimingService
        );

} catch (Throwable $exception) {

    echo
        'FAIL: Real Wildcard decision services could not be constructed<br>';

    echo
        'Message: '
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br><br>';

    echo
        '<strong>RESULT: TESTS FAILED ❌</strong><br>';

    exit;
}


wildcardDecisionRealCheck(
    'Database connection is available',
    $db
    instanceof
    PDO
);


wildcardDecisionRealCheck(
    'Wildcard Optimizer is the real production optimizer',
    $wildcardOptimizer
    instanceof
    WildcardOptimizer
);


wildcardDecisionRealCheck(
    'Wildcard Horizon service is the real production service',
    $wildcardHorizonService
    instanceof
    WildcardHorizonIntelligenceService
);


wildcardDecisionRealCheck(
    'Wildcard Timing service is the real production service',
    $wildcardTimingService
    instanceof
    WildcardTimingIntelligenceService
);


wildcardDecisionRealCheck(
    'Wildcard Decision service can be instantiated',
    $wildcardDecisionService
    instanceof
    WildcardDecisionIntelligenceService
);


/*
 * ============================================================
 * SCENARIO B: LOAD REAL PLAYER INTELLIGENCE
 * ============================================================
 */

wildcardDecisionRealHeading(
    'Scenario B: Load Real Player Intelligence'
);


$summaryStartedAt =
    microtime(
        true
    );


try {

    $playerSummaries =
        $playerIntelligenceService
            ->getAllPlayerSummaries();

} catch (Throwable $exception) {

    echo
        'FAIL: Real Player Intelligence could not be loaded<br>';

    echo
        'Message: '
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br><br>';

    echo
        '<strong>RESULT: TESTS FAILED ❌</strong><br>';

    exit;
}


$summaryRuntime =
    microtime(
        true
    )
    -
    $summaryStartedAt;


echo
    'Player Summaries: '
    . count(
        $playerSummaries
    )
    . '<br>';


echo
    'Player Summary Runtime: '
    . number_format(
        $summaryRuntime,
        4
    )
    . ' seconds<br>';


wildcardDecisionRealCheck(
    'Real Player Intelligence contains enough players for Wildcard evaluation',
    count(
        $playerSummaries
    )
    >=
    15
);


/*
 * ============================================================
 * SCENARIO C: BUILD REAL WILDCARD CANDIDATE POOL
 * ============================================================
 */

wildcardDecisionRealHeading(
    'Scenario C: Build Real Wildcard Candidate Pool'
);


$candidates =
    [];


$positionCandidateCounts = [

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


    $playerId =
        isset(
            $summary[
                'player_id'
            ]
        )
        &&
        is_numeric(
            $summary[
                'player_id'
            ]
        )
            ? (int) $summary[
                'player_id'
            ]
            : 0;


    $teamId =
        isset(
            $summary[
                'team_id'
            ]
        )
        &&
        is_numeric(
            $summary[
                'team_id'
            ]
        )
            ? (int) $summary[
                'team_id'
            ]
            : 0;


    $position =
        isset(
            $summary[
                'position'
            ]
        )
            ? strtoupper(
                trim(
                    (string) $summary[
                        'position'
                    ]
                )
            )
            : '';


    $price =
        $summary[
            'price'
        ]
        ??
        null;


    $intelligence =
        $summary[
            'intelligence_score'
        ]
        ??
        null;


    if (
        $playerId <= 0
        ||
        $teamId <= 0
        ||
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
        ||
        !is_numeric(
            $price
        )
        ||
        (float) $price <= 0.0
        ||
        !is_numeric(
            $intelligence
        )
    ) {

        continue;
    }


    $candidate = [

        'player_id' =>
            $playerId,

        'name' =>
            $summary[
                'name'
            ]
            ??
            (
                'Player '
                . $playerId
            ),

        'team_id' =>
            $teamId,

        'team_name' =>
            $summary[
                'team_name'
            ]
            ??
            (
                $summary[
                    'team_short_name'
                ]
                ??
                (
                    'Team '
                    . $teamId
                )
            ),

        'position' =>
            $position,

        'price' =>
            (float) $price,

        'intelligence_score' =>
            (float) $intelligence,

        'strength_rating' =>
            is_numeric(
                $summary[
                    'strength_rating'
                ]
                ??
                null
            )
                ? (float) $summary[
                    'strength_rating'
                ]
                : null,

        'value_rating' =>
            is_numeric(
                $summary[
                    'value_rating'
                ]
                ??
                null
            )
                ? (float) $summary[
                    'value_rating'
                ]
                : null,

        'fixture_rating' =>
            is_numeric(
                $summary[
                    'fixture_rating'
                ]
                ??
                null
            )
                ? (float) $summary[
                    'fixture_rating'
                ]
                : null,

        'availability_rating' =>
            is_numeric(
                $summary[
                    'availability_rating'
                ]
                ??
                null
            )
                ? (float) $summary[
                    'availability_rating'
                ]
                : null,

        'sample_confidence' =>
            is_numeric(
                $summary[
                    'sample_confidence'
                ]
                ??
                null
            )
                ? (float) $summary[
                    'sample_confidence'
                ]
                : null,

        'effective_confidence' =>
            is_numeric(
                $summary[
                    'effective_confidence'
                ]
                ??
                null
            )
                ? (float) $summary[
                    'effective_confidence'
                ]
                : null
    ];


    $candidates[] =
        $candidate;


    $positionCandidateCounts[
        $position
    ]++;
}


echo
    'Valid Wildcard Candidates: '
    . count(
        $candidates
    )
    . '<br>';


foreach (
    $positionCandidateCounts
    as $position => $count
) {

    echo
        $position
        . ': '
        . $count
        . '<br>';
}


wildcardDecisionRealCheck(
    'Real Wildcard candidate pool contains enough players for optimization',
    count(
        $candidates
    )
    >=
    15
);


wildcardDecisionRealCheck(
    'Real Wildcard candidate pool contains goalkeepers',
    $positionCandidateCounts[
        'GK'
    ]
    >=
    2
);


wildcardDecisionRealCheck(
    'Real Wildcard candidate pool contains defenders',
    $positionCandidateCounts[
        'DEF'
    ]
    >=
    5
);


wildcardDecisionRealCheck(
    'Real Wildcard candidate pool contains midfielders',
    $positionCandidateCounts[
        'MID'
    ]
    >=
    5
);


wildcardDecisionRealCheck(
    'Real Wildcard candidate pool contains forwards',
    $positionCandidateCounts[
        'FWD'
    ]
    >=
    3
);


/*
 * ============================================================
 * SCENARIO D: BUILD REAL CURRENT SQUAD
 * ============================================================
 *
 * This deliberately follows the same real-data approach used
 * by the existing chip decision tests.
 *
 * The test does not depend on one user's live FPL entry.
 * Instead it constructs a legal fifteen-player imported-squad
 * contract from genuine current database players.
 *
 * Required structure:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * Maximum three players from one club.
 * ============================================================
 */

wildcardDecisionRealHeading(
    'Scenario D: Build Real Current Squad'
);


$requiredPositions = [

    'GK' =>
        2,

    'DEF' =>
        5,

    'MID' =>
        5,

    'FWD' =>
        3
];


$selectedCurrentPlayers =
    [];


$selectedPositionCounts = [

    'GK' =>
        0,

    'DEF' =>
        0,

    'MID' =>
        0,

    'FWD' =>
        0
];


$selectedTeamCounts =
    [];


foreach (
    $candidates
    as $candidate
) {

    $position =
        $candidate[
            'position'
        ];


    if (
        $selectedPositionCounts[
            $position
        ]
        >=
        $requiredPositions[
            $position
        ]
    ) {

        continue;
    }


    $teamId =
        (int) $candidate[
            'team_id'
        ];


    if (
        (
            $selectedTeamCounts[
                $teamId
            ]
            ??
            0
        )
        >=
        3
    ) {

        continue;
    }


    $localPlayer =
        $playerRepository
            ->getById(
                (int) $candidate[
                    'player_id'
                ]
            );


    if (
        !is_array(
            $localPlayer
        )
    ) {

        continue;
    }


    $fplPlayerId =
        isset(
            $localPlayer[
                'fpl_player_id'
            ]
        )
        &&
        is_numeric(
            $localPlayer[
                'fpl_player_id'
            ]
        )
            ? (int) $localPlayer[
                'fpl_player_id'
            ]
            : 0;


    if (
        $fplPlayerId <= 0
    ) {

        continue;
    }


    $selectedCurrentPlayers[] = [

        'fpl_player_id' =>
            $fplPlayerId
    ];


    $selectedPositionCounts[
        $position
    ]++;


    $selectedTeamCounts[
        $teamId
    ] =
        (
            $selectedTeamCounts[
                $teamId
            ]
            ??
            0
        )
        +
        1;


    if (
        count(
            $selectedCurrentPlayers
        )
        ===
        15
    ) {

        break;
    }
}


$importedSquad = [

    'status' =>
        'success',

    'players' =>
        $selectedCurrentPlayers
];


wildcardDecisionRealCheck(
    'Real current squad contains exactly fifteen players',
    count(
        $selectedCurrentPlayers
    )
    ===
    15
);


wildcardDecisionRealCheck(
    'Real current squad contains two goalkeepers',
    $selectedPositionCounts[
        'GK'
    ]
    ===
    2
);


wildcardDecisionRealCheck(
    'Real current squad contains five defenders',
    $selectedPositionCounts[
        'DEF'
    ]
    ===
    5
);


wildcardDecisionRealCheck(
    'Real current squad contains five midfielders',
    $selectedPositionCounts[
        'MID'
    ]
    ===
    5
);


wildcardDecisionRealCheck(
    'Real current squad contains three forwards',
    $selectedPositionCounts[
        'FWD'
    ]
    ===
    3
);


wildcardDecisionRealCheck(
    'Real current squad respects the maximum-three-per-club rule',
    empty(
        array_filter(
            $selectedTeamCounts,
            static function (
                int $count
            ): bool {

                return
                    $count
                    >
                    3;
            }
        )
    )
);


/*
 * ============================================================
 * SCENARIO E: REAL WILDCARD DECISION PIPELINE
 * ============================================================
 */

wildcardDecisionRealHeading(
    'Scenario E: Real Wildcard Decision Pipeline'
);


$horizon =
    3;


$budget =
    100.0;


$decisionStartedAt =
    microtime(
        true
    );


try {

    $result =
        $wildcardDecisionService
            ->build(
                $importedSquad,
                $candidates,
                $budget,
                $horizon
            );

} catch (Throwable $exception) {

    echo
        'FAIL: Real Wildcard decision pipeline threw an exception<br>';

    echo
        'Message: '
        . htmlspecialchars(
            $exception->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br><br>';

    echo
        '<strong>RESULT: TESTS FAILED ❌</strong><br>';

    exit;
}


$decisionRuntime =
    microtime(
        true
    )
    -
    $decisionStartedAt;


echo
    'Decision Runtime: '
    . number_format(
        $decisionRuntime,
        4
    )
    . ' seconds<br>';


echo
    'Decision Status: '
    . htmlspecialchars(
        (string) (
            $result[
                'status'
            ]
            ??
            'Missing'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . '<br>';


wildcardDecisionRealCheck(
    'Real Wildcard decision pipeline returns Available',
    (
        $result[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


wildcardDecisionRealCheck(
    'Real Wildcard decision contains the current-squad horizon result',
    isset(
        $result[
            'current_horizon_result'
        ]
    )
    &&
    is_array(
        $result[
            'current_horizon_result'
        ]
    )
);


wildcardDecisionRealCheck(
    'Real Wildcard decision contains the Wildcard-squad horizon result',
    isset(
        $result[
            'wildcard_horizon_result'
        ]
    )
    &&
    is_array(
        $result[
            'wildcard_horizon_result'
        ]
    )
);


wildcardDecisionRealCheck(
    'Real Wildcard decision contains the timing result',
    isset(
        $result[
            'timing_result'
        ]
    )
    &&
    is_array(
        $result[
            'timing_result'
        ]
    )
);


/*
 * ============================================================
 * SCENARIO F: CURRENT-SQUAD HORIZON
 * ============================================================
 */

wildcardDecisionRealHeading(
    'Scenario F: Real Current-Squad Horizon'
);


$currentHorizonBuild =
    $result[
        'current_horizon_result'
    ]
    ??
    [];


$currentHorizon =
    $currentHorizonBuild[
        'horizon_result'
    ]
    ??
    [];


wildcardDecisionRealCheck(
    'Current-squad horizon is Available',
    (
        $currentHorizonBuild[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


wildcardDecisionRealCheck(
    'Current-squad horizon result is Available',
    (
        $currentHorizon[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


wildcardDecisionRealCheck(
    'Current-squad horizon preserves the requested horizon length',
    (
        $currentHorizon[
            'horizon'
        ]
        ??
        null
    )
    ===
    $horizon
);


wildcardDecisionRealCheck(
    'Current-squad horizon contains projected gameweeks',
    isset(
        $currentHorizon[
            'gameweeks'
        ]
    )
    &&
    is_array(
        $currentHorizon[
            'gameweeks'
        ]
    )
    &&
    count(
        $currentHorizon[
            'gameweeks'
        ]
    )
    >
    0
);


/*
 * ============================================================
 * SCENARIO G: REAL WILDCARD OPTIMIZATION AND HORIZON
 * ============================================================
 */

wildcardDecisionRealHeading(
    'Scenario G: Real Wildcard Optimization and Horizon'
);


$wildcardHorizonBuild =
    $result[
        'wildcard_horizon_result'
    ]
    ??
    [];


$optimizerResult =
    $wildcardHorizonBuild[
        'optimizer_result'
    ]
    ??
    [];


$wildcardHorizon =
    $wildcardHorizonBuild[
        'horizon_result'
    ]
    ??
    [];


wildcardDecisionRealCheck(
    'Wildcard horizon service returns Available',
    (
        $wildcardHorizonBuild[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


wildcardDecisionRealCheck(
    'Real Wildcard Optimizer returns success inside the decision pipeline',
    (
        $optimizerResult[
            'status'
        ]
        ??
        null
    )
    ===
    'success'
);


wildcardDecisionRealCheck(
    'Real Wildcard Optimizer produces exactly fifteen players',
    isset(
        $optimizerResult[
            'squad'
        ]
    )
    &&
    is_array(
        $optimizerResult[
            'squad'
        ]
    )
    &&
    count(
        $optimizerResult[
            'squad'
        ]
    )
    ===
    15
);


wildcardDecisionRealCheck(
    'Wildcard squad horizon result is Available',
    (
        $wildcardHorizon[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


wildcardDecisionRealCheck(
    'Wildcard squad horizon preserves the requested horizon length',
    (
        $wildcardHorizon[
            'horizon'
        ]
        ??
        null
    )
    ===
    $horizon
);


wildcardDecisionRealCheck(
    'Wildcard squad horizon contains projected gameweeks',
    isset(
        $wildcardHorizon[
            'gameweeks'
        ]
    )
    &&
    is_array(
        $wildcardHorizon[
            'gameweeks'
        ]
    )
    &&
    count(
        $wildcardHorizon[
            'gameweeks'
        ]
    )
    >
    0
);


/*
 * ============================================================
 * SCENARIO H: REAL WILDCARD TIMING DECISION
 * ============================================================
 */

wildcardDecisionRealHeading(
    'Scenario H: Real Wildcard Timing Decision'
);


$timingResult =
    $result[
        'timing_result'
    ]
    ??
    [];


wildcardDecisionRealCheck(
    'Wildcard timing analysis returns Available',
    (
        $timingResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
);


wildcardDecisionRealCheck(
    'Wildcard timing analysis exposes current-squad projected points',
    isset(
        $timingResult[
            'current_squad_projected_points'
        ]
    )
    &&
    is_numeric(
        $timingResult[
            'current_squad_projected_points'
        ]
    )
);


wildcardDecisionRealCheck(
    'Wildcard timing analysis exposes Wildcard-squad projected points',
    isset(
        $timingResult[
            'wildcard_squad_projected_points'
        ]
    )
    &&
    is_numeric(
        $timingResult[
            'wildcard_squad_projected_points'
        ]
    )
);


wildcardDecisionRealCheck(
    'Wildcard timing analysis exposes projected points gain',
    array_key_exists(
        'projected_points_gain',
        $timingResult
    )
    &&
    is_numeric(
        $timingResult[
            'projected_points_gain'
        ]
    )
);


wildcardDecisionRealCheck(
    'Wildcard timing analysis exposes future projected gain',
    array_key_exists(
        'future_projected_gain',
        $timingResult
    )
    &&
    is_numeric(
        $timingResult[
            'future_projected_gain'
        ]
    )
);


wildcardDecisionRealCheck(
    'Wildcard timing analysis exposes timing advantage',
    array_key_exists(
        'timing_advantage',
        $timingResult
    )
    &&
    is_numeric(
        $timingResult[
            'timing_advantage'
        ]
    )
);


wildcardDecisionRealCheck(
    'Wildcard timing analysis exposes a timing comparison',
    isset(
        $timingResult[
            'better_timing'
        ]
    )
    &&
    is_string(
        $timingResult[
            'better_timing'
        ]
    )
    &&
    trim(
        $timingResult[
            'better_timing'
        ]
    )
    !==
    ''
);


wildcardDecisionRealCheck(
    'Wildcard timing analysis exposes whether the Wildcard improves the squad',
    array_key_exists(
        'improves_squad',
        $timingResult
    )
    &&
    is_bool(
        $timingResult[
            'improves_squad'
        ]
    )
);


wildcardDecisionRealCheck(
    'Wildcard timing analysis exposes the final ChipDecision contract',
    isset(
        $timingResult[
            'decision'
        ]
    )
    &&
    $timingResult[
        'decision'
    ]
    instanceof
    ChipDecision
);


/*
 * ============================================================
 * SCENARIO I: DECISION PIPELINE INTEGRITY
 * ============================================================
 */

wildcardDecisionRealHeading(
    'Scenario I: Decision Pipeline Integrity'
);


wildcardDecisionRealCheck(
    'Decision current-squad horizon is the same production result used by timing intelligence',
    isset(
        $currentHorizon[
            'gameweeks'
        ]
    )
    &&
    is_array(
        $currentHorizon[
            'gameweeks'
        ]
    )
);


wildcardDecisionRealCheck(
    'Decision Wildcard horizon is the optimizer-backed production result used by timing intelligence',
    isset(
        $wildcardHorizonBuild[
            'optimizer_result'
        ]
    )
    &&
    isset(
        $wildcardHorizonBuild[
            'horizon_result'
        ]
    )
);


wildcardDecisionRealCheck(
    'Decision pipeline preserves the real optimizer result',
    $optimizerResult
    ===
    (
        $result[
            'wildcard_horizon_result'
        ][
            'optimizer_result'
        ]
        ??
        null
    )
);


wildcardDecisionRealCheck(
    'Decision pipeline reaches a complete timing decision without stubs',
    (
        $result[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
    &&
    (
        $timingResult[
            'status'
        ]
        ??
        null
    )
    ===
    'Available'
    &&
    isset(
        $timingResult[
            'decision'
        ]
    )
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo
    '<br>'
    . '============================================<br>'
    . 'Summary<br>'
    . '============================================<br><br>';


echo
    'Assertions passed: '
    . $passed
    . '<br>';


echo
    'Assertions failed: '
    . $failed
    . '<br><br>';


if (
    $failed === 0
) {

    echo
        '<strong>RESULT: TESTS PASSED ✅</strong><br>';

} else {

    echo
        '<strong>RESULT: TESTS FAILED ❌</strong><br>';
}