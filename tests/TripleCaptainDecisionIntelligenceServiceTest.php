<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Triple Captain Decision Intelligence Service Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function tripleCaptainServiceCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if (
        $condition
    ) {

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


function tripleCaptainServiceHeading(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    echo "<br>";
    echo "============================================<br>";
}


function tripleCaptainServiceFinish(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Triple Captain Decision Intelligence Service Test Summary<br>";
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
}


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

/*
 * ------------------------------------------------------------
 * SQUAD HORIZON SERVICE STUB
 * ------------------------------------------------------------
 */

class TripleCaptainSquadHorizonServiceStub
    extends SquadHorizonIntelligenceService
{
    private array $result;


    public function __construct(
        array $result
    ) {

        $this->result =
            $result;
    }


    public function buildForImportedSquad(
        array $importedSquad,
        int $horizon = 3
    ): array {

        return
            $this->result;
    }
}


/*
 * ------------------------------------------------------------
 * PLAYER INTELLIGENCE SERVICE STUB
 * ------------------------------------------------------------
 */

class TripleCaptainPlayerIntelligenceServiceStub
    extends PlayerIntelligenceService
{
    private array $playerSummaries;


    public function __construct(
        array $playerSummaries
    ) {

        $this->playerSummaries =
            $playerSummaries;
    }


    public function getAllPlayerSummaries(): array
    {

        return
            $this->playerSummaries;
    }
}


/*
 * ------------------------------------------------------------
 * CAPTAIN INTELLIGENCE STUB
 * ------------------------------------------------------------
 */

class TripleCaptainCaptainIntelligenceStub
    extends CaptainIntelligence
{
    private array $result;


    public function __construct(
        array $result
    ) {

        $this->result =
            $result;
    }


    public function evaluate(
        array $player
    ): array {

        return
            $this->result;
    }
}


/*
 * ============================================================
 * TEST DATA BUILDERS
 * ============================================================
 */

function buildTripleCaptainImportedSquad(): array {

    return [

        'status' =>
            'success',

        'players' => [

            [
                'fpl_player_id' =>
                    1001
            ]
        ]
    ];
}


function buildTripleCaptainHorizonResult(
    int $captainPlayerId = 8,
    float $projectedPoints = 12.0,
    ?float $projectionConfidence = 0.82,
    int $fixtureCount = 1,
    string $scheduleType = 'Normal'
): array {

    $captain = [

        'player_id' =>
            $captainPlayerId,

        'name' =>
            'Projected Captain',

        'position' =>
            'FWD',

        'projected_points' =>
            $projectedPoints,

        'projection_confidence' =>
            $projectionConfidence,

        'fixture_count' =>
            $fixtureCount,

        'schedule_type' =>
            $scheduleType
    ];


    return [

        'status' =>
            'Available',

        'horizon_result' => [

            'status' =>
                'Available',

            'gameweeks' => [

                3 => [

                    'gameweek' =>
                        3,

                    'players' => [

                        $captain
                    ],

                    'starting_xi' => [

                        $captain
                    ],

                    'captain' =>
                        $captain
                ]
            ]
        ]
    ];
}


function buildTripleCaptainPlayerSummaries(
    int $captainPlayerId = 8
): array {

    return [

        [
            'player_id' =>
                $captainPlayerId,

            'name' =>
                'Projected Captain',

            'position' =>
                'FWD',

            /*
             * Existing CaptainIntelligence input contract.
             */
            'strength_rating' =>
                80.0,

            'next_fixture_rating' =>
                75.0,

            'sample_confidence' =>
                80.0,

            'effective_confidence' =>
                78.0,

            'availability_rating' =>
                100.0,

            /*
             * Existing attacking-performance evidence.
             */
            'adjusted_goals_rating' =>
                70.0,

            'adjusted_assists_rating' =>
                60.0,

            'adjusted_expected_goals_rating' =>
                80.0,

            'adjusted_expected_assists_rating' =>
                65.0
        ]
    ];
}


function buildTripleCaptainCaptainResult(
    int $playerId = 8,
    float $captainScore = 67.0,
    float $captainConfidencePercent = 78.0
): array {

    return [

        'status' =>
            'success',

        'player_id' =>
            $playerId,

        'name' =>
            'Projected Captain',

        'position' =>
            'FWD',

        'captain_score' =>
            $captainScore,

        'classification' =>
            'Elite Captain',

        'components' => [

            /*
             * CaptainIntelligence currently exposes its normalised
             * confidence here on the 0–100 scale.
             */
            'confidence' =>
                $captainConfidencePercent
        ],

        'summary' =>
            'Projected Captain is classified as elite captain.'
    ];
}


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario A: Class Contract'
);


$classExists =
    class_exists(
        'TripleCaptainDecisionIntelligenceService'
    );


tripleCaptainServiceCheck(
    'TripleCaptainDecisionIntelligenceService class exists',
    $classExists
);


if (
    !$classExists
) {

    tripleCaptainServiceFinish();

    exit;
}


$buildMethodExists =
    method_exists(
        'TripleCaptainDecisionIntelligenceService',
        'build'
    );


tripleCaptainServiceCheck(
    'TripleCaptainDecisionIntelligenceService exposes build()',
    $buildMethodExists
);


if (
    !$buildMethodExists
) {

    tripleCaptainServiceFinish();

    exit;
}


/*
 * ============================================================
 * SCENARIO B
 * AVAILABLE PIPELINE
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario B: Available Pipeline'
);


$horizonService =
    new TripleCaptainSquadHorizonServiceStub(
        buildTripleCaptainHorizonResult()
    );


$playerService =
    new TripleCaptainPlayerIntelligenceServiceStub(
        buildTripleCaptainPlayerSummaries()
    );


$captainIntelligence =
    new TripleCaptainCaptainIntelligenceStub(
        buildTripleCaptainCaptainResult()
    );


$tripleCaptainIntelligence =
    new TripleCaptainIntelligence();


$service =
    new TripleCaptainDecisionIntelligenceService(
        $horizonService,
        $playerService,
        $captainIntelligence,
        $tripleCaptainIntelligence
    );


$result =
    $service->build(
        buildTripleCaptainImportedSquad()
    );


tripleCaptainServiceCheck(
    'Available pipeline returns an array',
    is_array(
        $result
    )
);


tripleCaptainServiceCheck(
    'Available pipeline returns Available status',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


tripleCaptainServiceCheck(
    'Available pipeline preserves current horizon result',
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


tripleCaptainServiceCheck(
    'Available pipeline exposes Triple Captain analysis',
    isset(
        $result[
            'analysis'
        ]
    )
    &&
    is_array(
        $result[
            'analysis'
        ]
    )
);


tripleCaptainServiceCheck(
    'Available pipeline exposes ChipDecision',
    (
        $result[
            'decision'
        ]
        ?? null
    )
    instanceof ChipDecision
);


/*
 * ============================================================
 * SCENARIO C
 * HORIZON CAPTAIN IS AUTHORITATIVE
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario C: Horizon Captain Is Authoritative'
);


$analysis =
    $result[
        'analysis'
    ]
    ?? [];


tripleCaptainServiceCheck(
    'Analysis uses the captain selected by Squad Horizon',
    (
        $analysis[
            'player_id'
        ]
        ?? null
    )
    ===
    8
);


tripleCaptainServiceCheck(
    'Analysis preserves Squad Horizon projected captain points',
    isset(
        $analysis[
            'projected_captain_points'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'projected_captain_points'
            ]
        )
        -
        12.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO D
 * CAPTAIN INTELLIGENCE INTEGRATION
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario D: Captain Intelligence Integration'
);


tripleCaptainServiceCheck(
    'Analysis preserves Captain Intelligence score',
    isset(
        $analysis[
            'captain_score'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'captain_score'
            ]
        )
        -
        67.0
    )
    <
    0.001
);


tripleCaptainServiceCheck(
    'Captain Intelligence confidence is normalised to zero-to-one',
    isset(
        $analysis[
            'captain_confidence'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'captain_confidence'
            ]
        )
        -
        0.78
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO E
 * DECISION
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario E: Triple Captain Decision'
);


$decision =
    $result[
        'decision'
    ]
    ?? null;


tripleCaptainServiceCheck(
    'Decision identifies Triple Captain chip',
    $decision instanceof ChipDecision
    &&
    $decision->getChip()
    ===
    'Triple Captain'
);


tripleCaptainServiceCheck(
    'Exceptional synthetic opportunity recommends Use',
    $decision instanceof ChipDecision
    &&
    $decision->getRecommendation()
    ===
    'Use'
);


tripleCaptainServiceCheck(
    'Decision confidence uses weaker evidence',
    $decision instanceof ChipDecision
    &&
    abs(
        $decision->getConfidence()
        -
        0.78
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO F
 * DOUBLE GAMEWEEK PROPAGATION
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario F: Double Gameweek Propagation'
);


$service =
    new TripleCaptainDecisionIntelligenceService(

        new TripleCaptainSquadHorizonServiceStub(
            buildTripleCaptainHorizonResult(
                8,
                14.0,
                0.84,
                2,
                'Double'
            )
        ),

        new TripleCaptainPlayerIntelligenceServiceStub(
            buildTripleCaptainPlayerSummaries()
        ),

        new TripleCaptainCaptainIntelligenceStub(
            buildTripleCaptainCaptainResult(
                8,
                68.0,
                80.0
            )
        ),

        new TripleCaptainIntelligence()
    );


$result =
    $service->build(
        buildTripleCaptainImportedSquad()
    );


$analysis =
    $result[
        'analysis'
    ]
    ?? [];


tripleCaptainServiceCheck(
    'Double Gameweek fixture count is preserved',
    (
        $analysis[
            'fixture_count'
        ]
        ?? null
    )
    ===
    2
);


tripleCaptainServiceCheck(
    'Double Gameweek schedule type is preserved',
    (
        $analysis[
            'schedule_type'
        ]
        ?? null
    )
    ===
    'Double'
);


tripleCaptainServiceCheck(
    'Double Gameweek projected points are not multiplied again',
    isset(
        $analysis[
            'projected_captain_points'
        ]
    )
    &&
    abs(
        (
            (float) $analysis[
                'projected_captain_points'
            ]
        )
        -
        14.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO G
 * UNAVAILABLE CURRENT HORIZON
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario G: Unavailable Current Horizon'
);


$service =
    new TripleCaptainDecisionIntelligenceService(

        new TripleCaptainSquadHorizonServiceStub(
            [
                'status' =>
                    'Unavailable',

                'horizon_result' =>
                    null
            ]
        ),

        new TripleCaptainPlayerIntelligenceServiceStub(
            buildTripleCaptainPlayerSummaries()
        ),

        new TripleCaptainCaptainIntelligenceStub(
            buildTripleCaptainCaptainResult()
        ),

        new TripleCaptainIntelligence()
    );


$result =
    $service->build(
        buildTripleCaptainImportedSquad()
    );


tripleCaptainServiceCheck(
    'Unavailable current horizon returns Unavailable status',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


tripleCaptainServiceCheck(
    'Unavailable current horizon produces null analysis',
    array_key_exists(
        'analysis',
        $result
    )
    &&
    $result[
        'analysis'
    ]
    ===
    null
);


tripleCaptainServiceCheck(
    'Unavailable current horizon produces null decision',
    array_key_exists(
        'decision',
        $result
    )
    &&
    $result[
        'decision'
    ]
    ===
    null
);


/*
 * ============================================================
 * SCENARIO H
 * MISSING CAPTAIN
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario H: Missing Horizon Captain'
);


$missingCaptainHorizon =
    buildTripleCaptainHorizonResult();


$missingCaptainHorizon[
    'horizon_result'
][
    'gameweeks'
][
    3
][
    'captain'
] =
    null;


$service =
    new TripleCaptainDecisionIntelligenceService(

        new TripleCaptainSquadHorizonServiceStub(
            $missingCaptainHorizon
        ),

        new TripleCaptainPlayerIntelligenceServiceStub(
            buildTripleCaptainPlayerSummaries()
        ),

        new TripleCaptainCaptainIntelligenceStub(
            buildTripleCaptainCaptainResult()
        ),

        new TripleCaptainIntelligence()
    );


$result =
    $service->build(
        buildTripleCaptainImportedSquad()
    );


tripleCaptainServiceCheck(
    'Missing Squad Horizon captain returns Unavailable',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * ============================================================
 * SCENARIO I
 * EMPTY PLAYER SUMMARY COLLECTION
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario I: Empty Player Summary Collection'
);


$service =
    new TripleCaptainDecisionIntelligenceService(

        new TripleCaptainSquadHorizonServiceStub(
            buildTripleCaptainHorizonResult()
        ),

        new TripleCaptainPlayerIntelligenceServiceStub(
            []
        ),

        new TripleCaptainCaptainIntelligenceStub(
            buildTripleCaptainCaptainResult()
        ),

        new TripleCaptainIntelligence()
    );


$result =
    $service->build(
        buildTripleCaptainImportedSquad()
    );


tripleCaptainServiceCheck(
    'Empty Player Intelligence summaries return Unavailable',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * ============================================================
 * SCENARIO J
 * HORIZON CAPTAIN CANNOT BE MAPPED
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario J: Captain Mapping Failure'
);


$service =
    new TripleCaptainDecisionIntelligenceService(

        new TripleCaptainSquadHorizonServiceStub(
            buildTripleCaptainHorizonResult(
                999
            )
        ),

        new TripleCaptainPlayerIntelligenceServiceStub(
            buildTripleCaptainPlayerSummaries(
                8
            )
        ),

        new TripleCaptainCaptainIntelligenceStub(
            buildTripleCaptainCaptainResult()
        ),

        new TripleCaptainIntelligence()
    );


$result =
    $service->build(
        buildTripleCaptainImportedSquad()
    );


tripleCaptainServiceCheck(
    'Unmapped horizon captain returns Unavailable',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * ============================================================
 * SCENARIO K
 * CAPTAIN INTELLIGENCE FAILURE
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario K: Captain Intelligence Failure'
);


$service =
    new TripleCaptainDecisionIntelligenceService(

        new TripleCaptainSquadHorizonServiceStub(
            buildTripleCaptainHorizonResult()
        ),

        new TripleCaptainPlayerIntelligenceServiceStub(
            buildTripleCaptainPlayerSummaries()
        ),

        new TripleCaptainCaptainIntelligenceStub(
            [
                'status' =>
                    'invalid',

                'captain_score' =>
                    null,

                'components' =>
                    []
            ]
        ),

        new TripleCaptainIntelligence()
    );


$result =
    $service->build(
        buildTripleCaptainImportedSquad()
    );


tripleCaptainServiceCheck(
    'Invalid Captain Intelligence result returns Unavailable',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Unavailable'
);


/*
 * ============================================================
 * SCENARIO L
 * MISSING CAPTAIN CONFIDENCE
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario L: Missing Captain Confidence'
);


$captainResult =
    buildTripleCaptainCaptainResult();


$captainResult[
    'components'
][
    'confidence'
] =
    null;


$service =
    new TripleCaptainDecisionIntelligenceService(

        new TripleCaptainSquadHorizonServiceStub(
            buildTripleCaptainHorizonResult()
        ),

        new TripleCaptainPlayerIntelligenceServiceStub(
            buildTripleCaptainPlayerSummaries()
        ),

        new TripleCaptainCaptainIntelligenceStub(
            $captainResult
        ),

        new TripleCaptainIntelligence()
    );


$result =
    $service->build(
        buildTripleCaptainImportedSquad()
    );


$decision =
    $result[
        'decision'
    ]
    ?? null;


tripleCaptainServiceCheck(
    'Missing Captain Intelligence confidence still permits analysis',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


tripleCaptainServiceCheck(
    'Missing Captain Intelligence confidence produces zero decision confidence',
    $decision instanceof ChipDecision
    &&
    abs(
        $decision->getConfidence()
        -
        0.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SCENARIO M
 * MISSING PROJECTION CONFIDENCE
 * ============================================================
 */

tripleCaptainServiceHeading(
    'Scenario M: Missing Projection Confidence'
);


$service =
    new TripleCaptainDecisionIntelligenceService(

        new TripleCaptainSquadHorizonServiceStub(
            buildTripleCaptainHorizonResult(
                8,
                12.0,
                null
            )
        ),

        new TripleCaptainPlayerIntelligenceServiceStub(
            buildTripleCaptainPlayerSummaries()
        ),

        new TripleCaptainCaptainIntelligenceStub(
            buildTripleCaptainCaptainResult()
        ),

        new TripleCaptainIntelligence()
    );


$result =
    $service->build(
        buildTripleCaptainImportedSquad()
    );


$decision =
    $result[
        'decision'
    ]
    ?? null;


tripleCaptainServiceCheck(
    'Missing projection confidence still permits analysis',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Available'
);


tripleCaptainServiceCheck(
    'Missing projection confidence produces zero decision confidence',
    $decision instanceof ChipDecision
    &&
    abs(
        $decision->getConfidence()
        -
        0.0
    )
    <
    0.001
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

tripleCaptainServiceFinish();