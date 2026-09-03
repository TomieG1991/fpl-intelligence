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

function freeHitDecisionRealCheck(
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


function freeHitDecisionRealHeading(
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
    'Free Hit Decision Intelligence Real Data Test<br>';

echo
    '============================================<br>';


/*
 * ============================================================
 * SCENARIO A: REAL SERVICE SETUP
 * ============================================================
 */

freeHitDecisionRealHeading(
    'Scenario A: Real Service Setup'
);


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


$freeHitOptimizer =
    new FreeHitOptimizer();


$freeHitIntelligenceService =
    new FreeHitIntelligenceService(
        $playerIntelligenceService,
        $freeHitOptimizer
    );


$freeHitHorizonService =
    new FreeHitHorizonIntelligenceService(
        $freeHitIntelligenceService,
        $squadHorizonIntelligence
    );


$freeHitDecisionIntelligence =
    new FreeHitDecisionIntelligence();


$freeHitDecisionService =
    new FreeHitDecisionIntelligenceService(
        $squadHorizonService,
        $freeHitHorizonService,
        $freeHitDecisionIntelligence
    );


freeHitDecisionRealCheck(
    'Database connection is available',
    $db
    instanceof
    PDO
);


freeHitDecisionRealCheck(
    'Free Hit decision service can be instantiated',
    $freeHitDecisionService
    instanceof
    FreeHitDecisionIntelligenceService
);


/*
 * ============================================================
 * SCENARIO B: BUILD REAL CANDIDATE POOL
 * ============================================================
 */

freeHitDecisionRealHeading(
    'Scenario B: Build Real Candidate Pool'
);


$summaryStart =
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
    $summaryStart;


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


$candidates =
    [];


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
        isset(
            $summary[
                'price'
            ]
        )
        &&
        is_numeric(
            $summary[
                'price'
            ]
        )
            ? (float) $summary[
                'price'
            ]
            : 0.0;


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
        $price <= 0.0
    ) {

        continue;
    }


    $candidates[] = [

        'player_id' =>
            $playerId,

        'name' =>
            $summary[
                'name'
            ]
            ??
            'Unknown',

        'team_id' =>
            $teamId,

        'team_name' =>
            $summary[
                'team_name'
            ]
            ??
            null,

        'position' =>
            $position,

        'price' =>
            $price
    ];
}


echo
    'Valid Free Hit Candidates: '
    . count(
        $candidates
    )
    . '<br>';


freeHitDecisionRealCheck(
    'Real candidate pool contains enough players for optimization',
    count(
        $candidates
    )
    >=
    15
);


/*
 * ============================================================
 * SCENARIO C: BUILD REAL CURRENT SQUAD
 * ============================================================
 */

freeHitDecisionRealHeading(
    'Scenario C: Build Real Current Squad'
);


/*
 * The current-squad side deliberately uses real local players.
 *
 * We select:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * and resolve each local record's official FPL player ID so the
 * fixture matches the real FPLSquadImporter contract.
 */

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


freeHitDecisionRealCheck(
    'Real current squad contains exactly fifteen players',
    count(
        $selectedCurrentPlayers
    )
    ===
    15
);


freeHitDecisionRealCheck(
    'Real current squad contains two goalkeepers',
    $selectedPositionCounts[
        'GK'
    ]
    ===
    2
);


freeHitDecisionRealCheck(
    'Real current squad contains five defenders',
    $selectedPositionCounts[
        'DEF'
    ]
    ===
    5
);


freeHitDecisionRealCheck(
    'Real current squad contains five midfielders',
    $selectedPositionCounts[
        'MID'
    ]
    ===
    5
);


freeHitDecisionRealCheck(
    'Real current squad contains three forwards',
    $selectedPositionCounts[
        'FWD'
    ]
    ===
    3
);


/*
 * ============================================================
 * SCENARIO D: REAL FREE HIT DECISION PIPELINE
 * ============================================================
 */

freeHitDecisionRealHeading(
    'Scenario D: Real Free Hit Decision Pipeline'
);


$decisionStart =
    microtime(
        true
    );


$result =
    $freeHitDecisionService
        ->build(
            $importedSquad,
            $candidates,
            100.0
        );


$decisionRuntime =
    microtime(
        true
    )
    -
    $decisionStart;


echo
    'Decision Runtime: '
    . number_format(
        $decisionRuntime,
        4
    )
    . ' seconds<br>';


freeHitDecisionRealCheck(
    'Real Free Hit decision pipeline returns Available',
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


$currentHorizon =
    $result[
        'current_horizon_result'
    ][
        'horizon_result'
    ]
    ??
    [];


$freeHitHorizon =
    $result[
        'free_hit_horizon_result'
    ][
        'horizon_result'
    ]
    ??
    [];


$currentGameweeks =
    $currentHorizon[
        'gameweeks'
    ]
    ??
    [];


$freeHitGameweeks =
    $freeHitHorizon[
        'gameweeks'
    ]
    ??
    [];


$currentGameweekProjection =
    !empty(
        $currentGameweeks
    )
        ? reset(
            $currentGameweeks
        )
        : [];


$freeHitGameweekProjection =
    !empty(
        $freeHitGameweeks
    )
        ? reset(
            $freeHitGameweeks
        )
        : [];


$currentGameweek =
    isset(
        $currentGameweekProjection[
            'gameweek'
        ]
    )
        ? (int) $currentGameweekProjection[
            'gameweek'
        ]
        : 0;


$freeHitGameweek =
    isset(
        $freeHitGameweekProjection[
            'gameweek'
        ]
    )
        ? (int) $freeHitGameweekProjection[
            'gameweek'
        ]
        : 0;


freeHitDecisionRealCheck(
    'Current squad and Free Hit represent the same gameweek',
    $currentGameweek > 0
    &&
    $currentGameweek
    ===
    $freeHitGameweek
);


$currentProjectedPoints =
    $currentGameweekProjection[
        'starting_xi_projected_points'
    ]
    ??
    null;


$freeHitProjectedPoints =
    $freeHitGameweekProjection[
        'starting_xi_projected_points'
    ]
    ??
    null;


freeHitDecisionRealCheck(
    'Current squad exposes numeric Starting XI projected points',
    is_numeric(
        $currentProjectedPoints
    )
);


freeHitDecisionRealCheck(
    'Free Hit exposes numeric Starting XI projected points',
    is_numeric(
        $freeHitProjectedPoints
    )
);


$valueResult =
    $result[
        'value_result'
    ]
    ??
    [];


$projectedGain =
    $valueResult[
        'projected_points_gain'
    ]
    ??
    null;


freeHitDecisionRealCheck(
    'Real comparison exposes numeric projected points gain',
    is_numeric(
        $projectedGain
    )
);


if (
    is_numeric(
        $currentProjectedPoints
    )
    &&
    is_numeric(
        $freeHitProjectedPoints
    )
    &&
    is_numeric(
        $projectedGain
    )
) {

    freeHitDecisionRealCheck(
        'Projected points gain equals Free Hit XI minus current XI',
        abs(
            (
                (float) $freeHitProjectedPoints
                -
                (float) $currentProjectedPoints
            )
            -
            (float) $projectedGain
        )
        <
        0.0001
    );

} else {

    freeHitDecisionRealCheck(
        'Projected points gain equals Free Hit XI minus current XI',
        false
    );
}


$decision =
    $result[
        'decision'
    ]
    ??
    null;


freeHitDecisionRealCheck(
    'Real pipeline returns a ChipDecision',
    $decision
    instanceof
    ChipDecision
);


if (
    $decision
    instanceof
    ChipDecision
) {

    freeHitDecisionRealCheck(
        'Real decision belongs to Free Hit',
        $decision
            ->getChip()
        ===
        'Free Hit'
    );


    freeHitDecisionRealCheck(
        'Real decision recommendation is Use, Consider or Hold',
        in_array(
            $decision
                ->getRecommendation(),
            [
                'Use',
                'Consider',
                'Hold'
            ],
            true
        )
    );


    freeHitDecisionRealCheck(
        'Real decision confidence is between zero and one',
        $decision
            ->getConfidence()
        >=
        0.0
        &&
        $decision
            ->getConfidence()
        <=
        1.0
    );


    freeHitDecisionRealCheck(
        'Real decision includes an explanation',
        trim(
            $decision
                ->getExplanation()
        )
        !==
        ''
    );
}


/*
 * ============================================================
 * DIAGNOSTIC OUTPUT
 * ============================================================
 */

freeHitDecisionRealHeading(
    'Real Free Hit Decision'
);


echo
    'Gameweek: '
    . $currentGameweek
    . '<br>';


echo
    'Current Starting XI xPts: '
    . (
        is_numeric(
            $currentProjectedPoints
        )
            ? number_format(
                (float) $currentProjectedPoints,
                2
            )
            : 'N/A'
    )
    . '<br>';


echo
    'Free Hit Starting XI xPts: '
    . (
        is_numeric(
            $freeHitProjectedPoints
        )
            ? number_format(
                (float) $freeHitProjectedPoints,
                2
            )
            : 'N/A'
    )
    . '<br>';


echo
    'Projected Gain: '
    . (
        is_numeric(
            $projectedGain
        )
            ? number_format(
                (float) $projectedGain,
                2
            )
            : 'N/A'
    )
    . '<br>';


if (
    $decision
    instanceof
    ChipDecision
) {

    echo
        'Recommendation: '
        . htmlspecialchars(
            $decision
                ->getRecommendation(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';


    echo
        'Confidence: '
        . number_format(
            $decision
                ->getConfidence()
            *
            100,
            2
        )
        . '%<br>';


    echo
        'Explanation: '
        . htmlspecialchars(
            $decision
                ->getExplanation(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';
}


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

echo
    '<br>'
    . '============================================<br>';

echo
    'TEST SUMMARY<br>';

echo
    '============================================<br>';


echo
    'Passed: '
    . $passed
    . '<br>';


echo
    'Failed: '
    . $failed
    . '<br>';


echo
    $failed === 0
        ? 'RESULT: ALL TESTS PASSED ✅<br>'
        : 'RESULT: TESTS FAILED ❌<br>';