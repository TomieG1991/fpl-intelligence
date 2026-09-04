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

function tripleCaptainRealCheck(
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


function tripleCaptainRealHeading(
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
    'Triple Captain Decision Intelligence Real Data Test<br>';

echo
    '============================================<br>';


/*
 * ============================================================
 * SCENARIO A
 * REAL SERVICE SETUP
 * ============================================================
 */

tripleCaptainRealHeading(
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


$captainIntelligence =
    new CaptainIntelligence();


$tripleCaptainIntelligence =
    new TripleCaptainIntelligence();


$tripleCaptainDecisionService =
    new TripleCaptainDecisionIntelligenceService(
        $squadHorizonService,
        $playerIntelligenceService,
        $captainIntelligence,
        $tripleCaptainIntelligence
    );


tripleCaptainRealCheck(
    'Database connection is available',
    $db
    instanceof
    PDO
);


tripleCaptainRealCheck(
    'Triple Captain decision service can be instantiated',
    $tripleCaptainDecisionService
    instanceof
    TripleCaptainDecisionIntelligenceService
);


/*
 * ============================================================
 * SCENARIO B
 * LOAD REAL PLAYER DATA
 * ============================================================
 */

tripleCaptainRealHeading(
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


tripleCaptainRealCheck(
    'Real player summaries are available',
    count(
        $playerSummaries
    )
    >=
    15
);


/*
 * ============================================================
 * SCENARIO C
 * BUILD REAL CURRENT SQUAD
 * ============================================================
 *
 * This is deliberately NOT the user's real FPL team.
 *
 * We build a structurally valid squad from real database players
 * so the complete production pipeline can be tested against real
 * projections and real Captain Intelligence evidence.
 *
 * Required structure:
 *
 * 2 GK
 * 5 DEF
 * 5 MID
 * 3 FWD
 *
 * Maximum three players per club.
 */

tripleCaptainRealHeading(
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


tripleCaptainRealCheck(
    'Real current squad contains exactly fifteen players',
    count(
        $selectedCurrentPlayers
    )
    ===
    15
);


tripleCaptainRealCheck(
    'Real current squad contains two goalkeepers',
    $selectedPositionCounts[
        'GK'
    ]
    ===
    2
);


tripleCaptainRealCheck(
    'Real current squad contains five defenders',
    $selectedPositionCounts[
        'DEF'
    ]
    ===
    5
);


tripleCaptainRealCheck(
    'Real current squad contains five midfielders',
    $selectedPositionCounts[
        'MID'
    ]
    ===
    5
);


tripleCaptainRealCheck(
    'Real current squad contains three forwards',
    $selectedPositionCounts[
        'FWD'
    ]
    ===
    3
);


/*
 * ============================================================
 * SCENARIO D
 * REAL TRIPLE CAPTAIN PIPELINE
 * ============================================================
 */

tripleCaptainRealHeading(
    'Scenario D: Real Triple Captain Decision Pipeline'
);


$decisionStart =
    microtime(
        true
    );


$result =
    $tripleCaptainDecisionService
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


tripleCaptainRealCheck(
    'Real Triple Captain decision pipeline returns Available',
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
 * SCENARIO E
 * REAL ONE-GAMEWEEK HORIZON
 * ============================================================
 */

tripleCaptainRealHeading(
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


tripleCaptainRealCheck(
    'Real Triple Captain horizon contains exactly one gameweek',
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


tripleCaptainRealCheck(
    'Real Triple Captain gameweek is positive',
    $gameweekNumber
    >
    0
);


tripleCaptainRealCheck(
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


tripleCaptainRealCheck(
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


$horizonCaptain =
    $gameweek[
        'captain'
    ]
    ??
    null;


tripleCaptainRealCheck(
    'Squad Horizon selects a captain',
    is_array(
        $horizonCaptain
    )
);


/*
 * ============================================================
 * SCENARIO F
 * REAL CAPTAIN OPPORTUNITY
 * ============================================================
 */

tripleCaptainRealHeading(
    'Scenario F: Real Captain Opportunity'
);


$analysis =
    $result[
        'analysis'
    ]
    ??
    [];


$captainPlayerId =
    $analysis[
        'player_id'
    ]
    ??
    null;


$captainName =
    $analysis[
        'name'
    ]
    ??
    'Unknown';


$projectedCaptainPoints =
    $analysis[
        'projected_captain_points'
    ]
    ??
    null;


$captainScore =
    $analysis[
        'captain_score'
    ]
    ??
    null;


$projectionConfidence =
    $analysis[
        'projection_confidence'
    ]
    ??
    null;


$captainConfidence =
    $analysis[
        'captain_confidence'
    ]
    ??
    null;


$fixtureCount =
    $analysis[
        'fixture_count'
    ]
    ??
    null;


$scheduleType =
    $analysis[
        'schedule_type'
    ]
    ??
    null;


echo
    'Captain: '
    . htmlspecialchars(
        (string) $captainName,
        ENT_QUOTES,
        'UTF-8'
    )
    . '<br>';


echo
    'Projected Captain Points: '
    . (
        is_numeric(
            $projectedCaptainPoints
        )
            ? number_format(
                (float) $projectedCaptainPoints,
                2
            )
            : 'N/A'
    )
    . '<br>';


echo
    'Captain Intelligence Score: '
    . (
        is_numeric(
            $captainScore
        )
            ? number_format(
                (float) $captainScore,
                2
            )
            : 'N/A'
    )
    . '<br>';


echo
    'Projection Confidence: '
    . (
        is_numeric(
            $projectionConfidence
        )
            ? number_format(
                (float) $projectionConfidence
                *
                100,
                2
            )
                . '%'
            : 'N/A'
    )
    . '<br>';


echo
    'Captain Confidence: '
    . (
        is_numeric(
            $captainConfidence
        )
            ? number_format(
                (float) $captainConfidence
                *
                100,
                2
            )
                . '%'
            : 'N/A'
    )
    . '<br>';


echo
    'Fixture Count: '
    . (
        is_numeric(
            $fixtureCount
        )
            ? (int) $fixtureCount
            : 'N/A'
    )
    . '<br>';


echo
    'Schedule Type: '
    . htmlspecialchars(
        (string) (
            $scheduleType
            ??
            'N/A'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . '<br>';


tripleCaptainRealCheck(
    'Real analysis exposes a positive captain player ID',
    is_numeric(
        $captainPlayerId
    )
    &&
    (int) $captainPlayerId
    >
    0
);


tripleCaptainRealCheck(
    'Real analysis exposes numeric projected captain points',
    is_numeric(
        $projectedCaptainPoints
    )
);


tripleCaptainRealCheck(
    'Real projected captain points are non-negative',
    is_numeric(
        $projectedCaptainPoints
    )
    &&
    (float) $projectedCaptainPoints
    >=
    0.0
);


tripleCaptainRealCheck(
    'Real analysis exposes numeric Captain Intelligence score',
    is_numeric(
        $captainScore
    )
);


tripleCaptainRealCheck(
    'Real Captain Intelligence score is between zero and one hundred',
    is_numeric(
        $captainScore
    )
    &&
    (float) $captainScore
    >=
    0.0
    &&
    (float) $captainScore
    <=
    100.0
);


tripleCaptainRealCheck(
    'Real fixture count is positive',
    is_numeric(
        $fixtureCount
    )
    &&
    (int) $fixtureCount
    >
    0
);


tripleCaptainRealCheck(
    'Real schedule type is present',
    is_string(
        $scheduleType
    )
    &&
    trim(
        $scheduleType
    )
    !==
    ''
);


/*
 * ============================================================
 * SCENARIO G
 * HORIZON CAPTAIN CONSISTENCY
 * ============================================================
 */

tripleCaptainRealHeading(
    'Scenario G: Horizon Captain Consistency'
);


$horizonCaptainPlayerId =
    is_array(
        $horizonCaptain
    )
        ? (
            $horizonCaptain[
                'player_id'
            ]
            ??
            null
        )
        : null;


$horizonCaptainProjectedPoints =
    is_array(
        $horizonCaptain
    )
        ? (
            $horizonCaptain[
                'projected_points'
            ]
            ??
            null
        )
        : null;


tripleCaptainRealCheck(
    'Triple Captain analysis uses the Squad Horizon captain',
    is_numeric(
        $captainPlayerId
    )
    &&
    is_numeric(
        $horizonCaptainPlayerId
    )
    &&
    (int) $captainPlayerId
    ===
    (int) $horizonCaptainPlayerId
);


tripleCaptainRealCheck(
    'Triple Captain analysis preserves Squad Horizon projected points',
    is_numeric(
        $projectedCaptainPoints
    )
    &&
    is_numeric(
        $horizonCaptainProjectedPoints
    )
    &&
    abs(
        (float) $projectedCaptainPoints
        -
        (float) $horizonCaptainProjectedPoints
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO H
 * REAL CONFIDENCE
 * ============================================================
 */

tripleCaptainRealHeading(
    'Scenario H: Real Confidence'
);


tripleCaptainRealCheck(
    'Real projection confidence is null or between zero and one',
    $projectionConfidence === null
    ||
    (
        is_numeric(
            $projectionConfidence
        )
        &&
        (float) $projectionConfidence
        >=
        0.0
        &&
        (float) $projectionConfidence
        <=
        1.0
    )
);


tripleCaptainRealCheck(
    'Real Captain Intelligence confidence is null or between zero and one',
    $captainConfidence === null
    ||
    (
        is_numeric(
            $captainConfidence
        )
        &&
        (float) $captainConfidence
        >=
        0.0
        &&
        (float) $captainConfidence
        <=
        1.0
    )
);


/*
 * ============================================================
 * SCENARIO I
 * REAL CHIP DECISION
 * ============================================================
 */

tripleCaptainRealHeading(
    'Scenario I: Real Triple Captain Decision'
);


$decision =
    $result[
        'decision'
    ]
    ??
    null;


tripleCaptainRealCheck(
    'Real Triple Captain pipeline returns ChipDecision',
    $decision
    instanceof
    ChipDecision
);


tripleCaptainRealCheck(
    'Real decision identifies Triple Captain chip',
    $decision
    instanceof
    ChipDecision
    &&
    $decision
        ->getChip()
    ===
    'Triple Captain'
);


tripleCaptainRealCheck(
    'Real recommendation is Use, Consider or Hold',
    $decision
    instanceof
    ChipDecision
    &&
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


tripleCaptainRealCheck(
    'Real decision confidence is between zero and one',
    $decision
    instanceof
    ChipDecision
    &&
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


tripleCaptainRealCheck(
    'Real decision explanation is non-empty',
    $decision
    instanceof
    ChipDecision
    &&
    trim(
        $decision
            ->getExplanation()
    )
    !==
    ''
);


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
        'Decision Confidence: '
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
 * SCENARIO J
 * DECISION CONFIDENCE CONSISTENCY
 * ============================================================
 */

tripleCaptainRealHeading(
    'Scenario J: Decision Confidence Consistency'
);


if (
    $decision
    instanceof
    ChipDecision
) {

    $expectedDecisionConfidence =
        (
            is_numeric(
                $projectionConfidence
            )
            &&
            is_numeric(
                $captainConfidence
            )
        )
            ? min(
                (float) $projectionConfidence,
                (float) $captainConfidence
            )
            : 0.0;


    tripleCaptainRealCheck(
        'Real decision confidence uses weaker available evidence',
        abs(
            $decision
                ->getConfidence()
            -
            $expectedDecisionConfidence
        )
        <
        0.001
    );

} else {

    tripleCaptainRealCheck(
        'Real decision confidence uses weaker available evidence',
        false
    );
}


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo
    '<br>'
    . '============================================<br>'
    . 'Triple Captain Decision Intelligence Real Data Test Summary<br>'
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
    $failed ===
    0
) {

    echo
        'RESULT: TESTS PASSED ✅';

} else {

    echo
        'RESULT: TESTS FAILED ❌';
}