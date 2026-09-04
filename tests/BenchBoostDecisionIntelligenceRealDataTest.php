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

function benchBoostRealCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


function benchBoostRealHeading(
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
    'Bench Boost Decision Intelligence Real Data Test<br>';

echo
    '============================================<br>';


/*
 * ============================================================
 * SCENARIO A: REAL SERVICE SETUP
 * ============================================================
 */

benchBoostRealHeading(
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


$benchBoostIntelligence =
    new BenchBoostIntelligence();


$benchBoostDecisionService =
    new BenchBoostDecisionIntelligenceService(
        $squadHorizonService,
        $benchBoostIntelligence
    );


benchBoostRealCheck(
    'Database connection is available',
    $db
    instanceof
    PDO
);


benchBoostRealCheck(
    'Bench Boost decision service can be instantiated',
    $benchBoostDecisionService
    instanceof
    BenchBoostDecisionIntelligenceService
);


/*
 * ============================================================
 * SCENARIO B: LOAD REAL PLAYER DATA
 * ============================================================
 */

benchBoostRealHeading(
    'Scenario B: Load Real Player Data'
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


benchBoostRealCheck(
    'Real player summaries are available',
    count(
        $playerSummaries
    )
    >=
    15
);


/*
 * ============================================================
 * SCENARIO C: BUILD REAL CURRENT SQUAD
 * ============================================================
 *
 * This is deliberately not the user's real FPL team.
 *
 * The test constructs a structurally valid fifteen-player squad
 * from real database players so the complete production pipeline
 * can be exercised deterministically.
 *
 * We require:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * maximum three players from one club.
 */

benchBoostRealHeading(
    'Scenario C: Build Real Current Squad'
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


    if (
        $playerId <= 0
        ||
        $teamId <= 0
        ||
        !array_key_exists(
            $position,
            $requiredPositions
        )
    ) {

        continue;
    }


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
                $playerId
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


benchBoostRealCheck(
    'Real current squad contains exactly fifteen players',
    count(
        $selectedCurrentPlayers
    )
    ===
    15
);


benchBoostRealCheck(
    'Real current squad contains two goalkeepers',
    $selectedPositionCounts[
        'GK'
    ]
    ===
    2
);


benchBoostRealCheck(
    'Real current squad contains five defenders',
    $selectedPositionCounts[
        'DEF'
    ]
    ===
    5
);


benchBoostRealCheck(
    'Real current squad contains five midfielders',
    $selectedPositionCounts[
        'MID'
    ]
    ===
    5
);


benchBoostRealCheck(
    'Real current squad contains three forwards',
    $selectedPositionCounts[
        'FWD'
    ]
    ===
    3
);


/*
 * ============================================================
 * SCENARIO D: REAL BENCH BOOST PIPELINE
 * ============================================================
 */

benchBoostRealHeading(
    'Scenario D: Real Bench Boost Decision Pipeline'
);


$decisionStart =
    microtime(
        true
    );


$result =
    $benchBoostDecisionService
        ->build(
            $importedSquad
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


benchBoostRealCheck(
    'Real Bench Boost decision pipeline returns Available',
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


/*
 * ============================================================
 * SCENARIO E: REAL ONE-GAMEWEEK HORIZON
 * ============================================================
 */

benchBoostRealHeading(
    'Scenario E: Real One-Gameweek Horizon'
);


$currentHorizon =
    $result[
        'current_horizon_result'
    ][
        'horizon_result'
    ]
    ??
    [];


$gameweeks =
    $currentHorizon[
        'gameweeks'
    ]
    ??
    [];


benchBoostRealCheck(
    'Real Bench Boost horizon contains exactly one gameweek',
    count(
        $gameweeks
    )
    ===
    1
);


$gameweek =
    !empty(
        $gameweeks
    )
        ? reset(
            $gameweeks
        )
        : [];


$gameweekNumber =
    isset(
        $gameweek[
            'gameweek'
        ]
    )
    &&
    is_numeric(
        $gameweek[
            'gameweek'
        ]
    )
        ? (int) $gameweek[
            'gameweek'
        ]
        : 0;


echo
    'Gameweek: '
    . $gameweekNumber
    . '<br>';


benchBoostRealCheck(
    'Real Bench Boost gameweek is positive',
    $gameweekNumber > 0
);


benchBoostRealCheck(
    'Real gameweek contains fifteen players',
    count(
        $gameweek[
            'players'
        ]
        ??
        []
    )
    ===
    15
);


benchBoostRealCheck(
    'Real gameweek contains eleven starters',
    count(
        $gameweek[
            'starting_xi'
        ]
        ??
        []
    )
    ===
    11
);


benchBoostRealCheck(
    'Real gameweek contains four bench players',
    count(
        $gameweek[
            'bench'
        ]
        ??
        []
    )
    ===
    4
);


/*
 * ============================================================
 * SCENARIO F: REAL BENCH BOOST MEASUREMENTS
 * ============================================================
 */

benchBoostRealHeading(
    'Scenario F: Real Bench Boost Measurements'
);


$analysis =
    $result[
        'analysis'
    ]
    ??
    [];


$projectedBenchPoints =
    $analysis[
        'projected_bench_points'
    ]
    ??
    null;


$benchReliability =
    $analysis[
        'bench_reliability'
    ]
    ??
    null;


$fixtureQuality =
    $analysis[
        'fixture_quality'
    ]
    ??
    null;


$fullSquadAvailability =
    $analysis[
        'full_squad_availability'
    ]
    ??
    null;


echo
    'Projected Bench Points: '
    . (
        is_numeric(
            $projectedBenchPoints
        )
            ? number_format(
                (float) $projectedBenchPoints,
                2
            )
            : 'N/A'
    )
    . '<br>';


echo
    'Bench Reliability: '
    . (
        is_numeric(
            $benchReliability
        )
            ? number_format(
                (float) $benchReliability
                *
                100,
                2
            )
                . '%'
            : 'N/A'
    )
    . '<br>';


echo
    'Fixture Quality: '
    . (
        is_numeric(
            $fixtureQuality
        )
            ? number_format(
                (float) $fixtureQuality,
                2
            )
            : 'N/A'
    )
    . '<br>';


echo
    'Full-Squad Availability: '
    . (
        is_numeric(
            $fullSquadAvailability
        )
            ? number_format(
                (float) $fullSquadAvailability
                *
                100,
                2
            )
                . '%'
            : 'N/A'
    )
    . '<br>';


benchBoostRealCheck(
    'Real analysis exposes numeric projected bench points',
    is_numeric(
        $projectedBenchPoints
    )
);


benchBoostRealCheck(
    'Real analysis exposes numeric bench reliability',
    is_numeric(
        $benchReliability
    )
);


benchBoostRealCheck(
    'Real analysis exposes numeric fixture quality',
    is_numeric(
        $fixtureQuality
    )
);


benchBoostRealCheck(
    'Real analysis exposes numeric full-squad availability',
    is_numeric(
        $fullSquadAvailability
    )
);


benchBoostRealCheck(
    'Real bench reliability is between zero and one',
    is_numeric(
        $benchReliability
    )
    &&
    (float) $benchReliability
    >=
    0.0
    &&
    (float) $benchReliability
    <=
    1.0
);


benchBoostRealCheck(
    'Real fixture quality is between zero and one hundred',
    is_numeric(
        $fixtureQuality
    )
    &&
    (float) $fixtureQuality
    >=
    0.0
    &&
    (float) $fixtureQuality
    <=
    100.0
);


benchBoostRealCheck(
    'Real full-squad availability is between zero and one',
    is_numeric(
        $fullSquadAvailability
    )
    &&
    (float) $fullSquadAvailability
    >=
    0.0
    &&
    (float) $fullSquadAvailability
    <=
    1.0
);


/*
 * ============================================================
 * SCENARIO G: PROJECTED BENCH POINTS SOURCE
 * ============================================================
 */

benchBoostRealHeading(
    'Scenario G: Projected Bench Points Source'
);


$benchCoveragePoints =
    $gameweek[
        'bench_coverage'
    ][
        'total_projected_points'
    ]
    ??
    null;


benchBoostRealCheck(
    'Squad Horizon exposes numeric bench projected points',
    is_numeric(
        $benchCoveragePoints
    )
);


if (
    is_numeric(
        $benchCoveragePoints
    )
    &&
    is_numeric(
        $projectedBenchPoints
    )
) {

    benchBoostRealCheck(
        'Bench Boost projected points exactly preserve Squad Horizon bench coverage',
        abs(
            (float) $projectedBenchPoints
            -
            (float) $benchCoveragePoints
        )
        <
        0.0001
    );

} else {

    benchBoostRealCheck(
        'Bench Boost projected points exactly preserve Squad Horizon bench coverage',
        false
    );
}


/*
 * ============================================================
 * SCENARIO H: REAL CHIP DECISION
 * ============================================================
 */

benchBoostRealHeading(
    'Scenario H: Real Chip Decision'
);


$decision =
    $result[
        'decision'
    ]
    ??
    null;


benchBoostRealCheck(
    'Real Bench Boost decision is a ChipDecision',
    $decision
    instanceof
    ChipDecision
);


benchBoostRealCheck(
    'Real decision identifies Bench Boost chip',
    $decision
    instanceof
    ChipDecision
    &&
    $decision->getChip()
    ===
    'Bench Boost'
);


benchBoostRealCheck(
    'Real recommendation uses supported chip decision state',
    $decision
    instanceof
    ChipDecision
    &&
    in_array(
        $decision->getRecommendation(),
        [
            'Use',
            'Consider',
            'Hold'
        ],
        true
    )
);


if (
    $decision
    instanceof
    ChipDecision
) {

    echo
        'Recommendation: '
        . htmlspecialchars(
            $decision->getRecommendation(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';


    echo
        'Confidence: '
        . number_format(
            $decision->getConfidence()
            *
            100,
            2
        )
        . '%<br>';


    echo
        'Explanation: '
        . htmlspecialchars(
            $decision->getExplanation(),
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';
}


benchBoostRealCheck(
    'Real decision confidence is between zero and one',
    $decision
    instanceof
    ChipDecision
    &&
    $decision->getConfidence()
    >=
    0.0
    &&
    $decision->getConfidence()
    <=
    1.0
);


benchBoostRealCheck(
    'Real decision exposes non-empty explanation',
    $decision
    instanceof
    ChipDecision
    &&
    trim(
        $decision->getExplanation()
    )
    !==
    ''
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo
    '<br>'
    . '============================================<br>'
    . 'Bench Boost Decision Intelligence Real Data Test Summary<br>'
    . '============================================<br>';


echo
    'Passed: '
    . $passed
    . '<br>';


echo
    'Failed: '
    . $failed
    . '<br><br>';


if (
    $failed === 0
) {

    echo
        'RESULT: TESTS PASSED ✅';

} else {

    echo
        'RESULT: TESTS FAILED ❌';
}