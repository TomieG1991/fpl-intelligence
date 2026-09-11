<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Calibration Historical Evidence Service Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


/*
 * ============================================================
 * HELPERS
 * ============================================================
 */

function playerCalibrationHistoricalEvidenceCheck(
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


function playerCalibrationHistoricalEvidenceSummary(): void {

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Player Calibration Historical Evidence Service Test Summary<br>";
    echo "============================================<br>";

    echo "Passed: "
        . $passed
        . "<br>";

    echo "Failed: "
        . $failed
        . "<br><br>";


    if ($failed === 0) {

        echo "RESULT: ALL TESTS PASSED ✅";

    } else {

        echo "RESULT: TESTS FAILED ❌";
    }
}


/*
 * ============================================================
 * TEST DOUBLES
 * ============================================================
 */

class PlayerCalibrationHistoricalEvidenceSnapshotRepositoryStub
{
    public mixed $snapshot;

    public array $calls =
        [];


    public function __construct(
        mixed $snapshot
    ) {

        $this->snapshot =
            $snapshot;
    }


    public function getByEntryAndGameweek(
        int $entryId,
        int $gameweekId
    ): mixed {

        $this->calls[] = [
            'entry_id' => $entryId,
            'gameweek_id' => $gameweekId
        ];


        return $this->snapshot;
    }
}


class PlayerCalibrationHistoricalEvidenceOutcomeServiceStub
{
    public array $outcomes;

    public array $calls =
        [];


    public function __construct(
        array $outcomes
    ) {

        $this->outcomes =
            $outcomes;
    }


    public function getByGameweekId(
        int $gameweekId
    ): array {

        $this->calls[] =
            $gameweekId;


        return $this->outcomes;
    }
}


/*
 * ============================================================
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Class Contract<br>";
echo "============================================<br>";


playerCalibrationHistoricalEvidenceCheck(
    'PlayerCalibrationHistoricalEvidenceService class exists',
    class_exists(
        'PlayerCalibrationHistoricalEvidenceService'
    )
);


if (
    class_exists(
        'PlayerCalibrationHistoricalEvidenceService'
    )
) {

    $reflection =
        new ReflectionClass(
            'PlayerCalibrationHistoricalEvidenceService'
        );


    playerCalibrationHistoricalEvidenceCheck(
        'PlayerCalibrationHistoricalEvidenceService exposes build()',
        $reflection->hasMethod(
            'build'
        )
    );

} else {

    playerCalibrationHistoricalEvidenceCheck(
        'PlayerCalibrationHistoricalEvidenceService exposes build()',
        false
    );
}


echo "<br>";


/*
 * ============================================================
 * STOP UNTIL PRODUCTION CLASS EXISTS
 * ============================================================
 */

if (
    !class_exists(
        'PlayerCalibrationHistoricalEvidenceService'
    )
) {

    playerCalibrationHistoricalEvidenceSummary();

    exit;
}


/*
 * ============================================================
 * CONTROLLED HISTORICAL DATA
 * ============================================================
 */

$snapshot = [

    'id' => 10,
    'gameweek_id' => 5,
    'entry_id' => 2702264,

    'player_rankings' => [

        [
            'player_id' => 101,
            'fpl_player_id' => 1001,
            'name' => 'Player One',
            'position' => 'DEF',

            'strength_rating' => 86.0,
            'value_rating' => 78.0,
            'availability_rating' => 90.0,
            'fixture_rating' => 76.0,

            'next_fixture_rating' => 82.0,
            'base_next_fixture_rating' => 78.0,
            'next_opponent_attack_rating' => 35.0,
            'next_opponent_defence_rating' => 28.0,

            'availability_multiplier' => 0.95,

            'sample_confidence' => 0.40,
            'participation_rate' => 0.90
        ],

        [
            'player_id' => 102,
            'fpl_player_id' => 1002,
            'name' => 'Player Two',
            'position' => 'MID',

            'strength_rating' => 79.0,
            'value_rating' => 72.0,
            'availability_rating' => 75.0,
            'fixture_rating' => 70.0,

            'next_fixture_rating' => 68.0,
            'base_next_fixture_rating' => 72.0,
            'next_opponent_attack_rating' => 48.0,
            'next_opponent_defence_rating' => 56.0,

            'availability_multiplier' => 0.85,

            'sample_confidence' => 0.30,
            'participation_rate' => 0.65
        ],

        [
            'player_id' => 103,
            'fpl_player_id' => 1003,
            'name' => 'Player Three',
            'position' => 'FWD',

            'strength_rating' => null,
            'value_rating' => null,
            'availability_rating' => null,
            'fixture_rating' => 65.0,

            'next_fixture_rating' => null,
            'base_next_fixture_rating' => null,
            'next_opponent_attack_rating' => null,
            'next_opponent_defence_rating' => null,

            'availability_multiplier' => 1.00,

            'sample_confidence' => null,
            'participation_rate' => null
        ]
    ]
];


$outcomes = [

    [
        'player_id' => 101,
        'total_points' => 10,
        'minutes' => 150,
        'fixture_count' => 2
    ],

    [
        'player_id' => 102,
        'total_points' => 0,
        'minutes' => 0,
        'fixture_count' => 1
    ],

    /*
     * Player 103 deliberately has no realised outcome.
     */
];


$snapshotRepository =
    new PlayerCalibrationHistoricalEvidenceSnapshotRepositoryStub(
        $snapshot
    );


$outcomeService =
    new PlayerCalibrationHistoricalEvidenceOutcomeServiceStub(
        $outcomes
    );


$service =
    new PlayerCalibrationHistoricalEvidenceService(
        $snapshotRepository,
        $outcomeService
    );


/*
 * ============================================================
 * SCENARIO B
 * INVALID IDENTIFIERS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Invalid Identifiers<br>";
echo "============================================<br>";


$invalidEntryThrew =
    false;


try {

    $service->build(
        0,
        5
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidEntryThrew =
        true;
}


playerCalibrationHistoricalEvidenceCheck(
    'Non-positive entry ID is rejected',
    $invalidEntryThrew
);


$invalidGameweekThrew =
    false;


try {

    $service->build(
        2702264,
        0
    );

} catch (
    InvalidArgumentException $exception
) {

    $invalidGameweekThrew =
        true;
}


playerCalibrationHistoricalEvidenceCheck(
    'Non-positive gameweek ID is rejected',
    $invalidGameweekThrew
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * HISTORICAL SOURCE LOOKUP
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Historical Source Lookup<br>";
echo "============================================<br>";


$result =
    $service->build(
        2702264,
        5
    );


playerCalibrationHistoricalEvidenceCheck(
    'Immutable recommendation snapshot is requested by entry and gameweek',
    $snapshotRepository->calls === [
        [
            'entry_id' => 2702264,
            'gameweek_id' => 5
        ]
    ]
);


playerCalibrationHistoricalEvidenceCheck(
    'Realised outcomes are requested for the historical gameweek',
    $outcomeService->calls === [
        5
    ]
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * RESULT CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Result Contract<br>";
echo "============================================<br>";


playerCalibrationHistoricalEvidenceCheck(
    'Entry ID is preserved',
    ($result['entry_id'] ?? null) === 2702264
);


playerCalibrationHistoricalEvidenceCheck(
    'Gameweek ID is preserved',
    ($result['gameweek_id'] ?? null) === 5
);


playerCalibrationHistoricalEvidenceCheck(
    'Immutable snapshot is returned unchanged',
    ($result['snapshot'] ?? null) === $snapshot
);


playerCalibrationHistoricalEvidenceCheck(
    'Realised player outcomes are returned unchanged',
    ($result['player_outcomes'] ?? null) === $outcomes
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * HISTORICAL ROW ASSEMBLY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Historical Row Assembly<br>";
echo "============================================<br>";


$historicalRows =
    $result[
        'historical_rows'
    ]
    ?? [];


playerCalibrationHistoricalEvidenceCheck(
    'Historical ranking membership is preserved',
    count(
        $historicalRows
    )
    === 3
);


playerCalibrationHistoricalEvidenceCheck(
    'Historical ranking order is preserved',
    array_column(
        $historicalRows,
        'player_id'
    )
    === [
        101,
        102,
        103
    ]
);


playerCalibrationHistoricalEvidenceCheck(
    'Realised points are joined by local player ID',
    ($historicalRows[0]['actual_points'] ?? null) === 10
);


playerCalibrationHistoricalEvidenceCheck(
    'Genuine zero realised points are preserved',
    array_key_exists(
        'actual_points',
        $historicalRows[1] ?? []
    )
    &&
    $historicalRows[1]['actual_points'] === 0
);


playerCalibrationHistoricalEvidenceCheck(
    'Missing realised outcome remains null',
    array_key_exists(
        'actual_points',
        $historicalRows[2] ?? []
    )
    &&
    $historicalRows[2]['actual_points'] === null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * STRENGTH / FIXTURE CALIBRATION EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Strength / Fixture Evidence<br>";
echo "============================================<br>";


playerCalibrationHistoricalEvidenceCheck(
    'Strength rating is preserved',
    ($historicalRows[0]['strength_rating'] ?? null) === 86.0
);

playerCalibrationHistoricalEvidenceCheck(
    'Value rating is preserved',
    ($historicalRows[0]['value_rating'] ?? null) === 78.0
);


playerCalibrationHistoricalEvidenceCheck(
    'Availability rating is preserved',
    ($historicalRows[0]['availability_rating'] ?? null) === 90.0
);


playerCalibrationHistoricalEvidenceCheck(
    'Fixture rating is preserved',
    ($historicalRows[0]['fixture_rating'] ?? null) === 76.0
);


playerCalibrationHistoricalEvidenceCheck(
    'Availability multiplier is preserved',
    ($historicalRows[0]['availability_multiplier'] ?? null) === 0.95
);

playerCalibrationHistoricalEvidenceCheck(
    'Unavailable Transfer calibration ratings remain explicitly null',
    array_key_exists(
        'value_rating',
        $historicalRows[2] ?? []
    )
    &&
    $historicalRows[2]['value_rating'] === null
    &&
    array_key_exists(
        'availability_rating',
        $historicalRows[2] ?? []
    )
    &&
    $historicalRows[2]['availability_rating'] === null
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * POSITION-AWARE FIXTURE CALIBRATION EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Position-Aware Fixture Evidence<br>";
echo "============================================<br>";


playerCalibrationHistoricalEvidenceCheck(
    'Historical player position is preserved',
    ($historicalRows[0]['position'] ?? null) === 'DEF'
);


playerCalibrationHistoricalEvidenceCheck(
    'Position-aware next fixture rating is preserved',
    ($historicalRows[0]['next_fixture_rating'] ?? null) === 82.0
);


playerCalibrationHistoricalEvidenceCheck(
    'Base next fixture rating is preserved',
    ($historicalRows[0]['base_next_fixture_rating'] ?? null) === 78.0
);


playerCalibrationHistoricalEvidenceCheck(
    'Opponent Attack rating is preserved',
    ($historicalRows[0]['next_opponent_attack_rating'] ?? null) === 35.0
);


playerCalibrationHistoricalEvidenceCheck(
    'Opponent Defence rating is preserved',
    ($historicalRows[0]['next_opponent_defence_rating'] ?? null) === 28.0
);


playerCalibrationHistoricalEvidenceCheck(
    'Unavailable position-aware evidence remains explicitly null',
    array_key_exists(
        'base_next_fixture_rating',
        $historicalRows[2] ?? []
    )
    &&
    $historicalRows[2]['base_next_fixture_rating'] === null
    &&
    array_key_exists(
        'next_opponent_attack_rating',
        $historicalRows[2] ?? []
    )
    &&
    $historicalRows[2]['next_opponent_attack_rating'] === null
    &&
    array_key_exists(
        'next_opponent_defence_rating',
        $historicalRows[2] ?? []
    )
    &&
    $historicalRows[2]['next_opponent_defence_rating'] === null
);


echo "<br>";


/*
 * ============================================================
 * EFFECTIVE CONFIDENCE CALIBRATION EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Effective Confidence Calibration Evidence<br>";
echo "============================================<br>";


playerCalibrationHistoricalEvidenceCheck(
    'Recommendation-time Sample Confidence is preserved',
    (
        $historicalRows[
            0
        ]['sample_confidence']
        ??
        null
    )
    ===
    0.40
);


playerCalibrationHistoricalEvidenceCheck(
    'Recommendation-time Participation Rate is preserved',
    (
        $historicalRows[
            0
        ]['participation_rate']
        ??
        null
    )
    ===
    0.90
);


playerCalibrationHistoricalEvidenceCheck(
    'Unavailable Effective Confidence inputs remain explicitly null',
    array_key_exists(
        'sample_confidence',
        $historicalRows[
            2
        ]
        ??
        []
    )
    &&
    $historicalRows[
        2
    ]['sample_confidence']
    ===
    null
    &&
    array_key_exists(
        'participation_rate',
        $historicalRows[
            2
        ]
        ??
        []
    )
    &&
    $historicalRows[
        2
    ]['participation_rate']
    ===
    null
);


playerCalibrationHistoricalEvidenceCheck(
    'Realised minutes are joined by local player ID',
    (
        $historicalRows[
            0
        ]['actual_minutes']
        ??
        null
    )
    ===
    150
);

playerCalibrationHistoricalEvidenceCheck(
    'Realised fixture count is joined by local player ID',
    (
        $historicalRows[
            0
        ]['actual_fixture_count']
        ??
        null
    )
    ===
    2
);


playerCalibrationHistoricalEvidenceCheck(
    'Single-fixture realised fixture count is preserved',
    (
        $historicalRows[
            1
        ]['actual_fixture_count']
        ??
        null
    )
    ===
    1
);


playerCalibrationHistoricalEvidenceCheck(
    'Missing realised outcome leaves realised fixture count null',
    array_key_exists(
        'actual_fixture_count',
        $historicalRows[
            2
        ]
        ??
        []
    )
    &&
    $historicalRows[
        2
    ]['actual_fixture_count']
    ===
    null
);

playerCalibrationHistoricalEvidenceCheck(
    'Genuine zero realised minutes are preserved',
    array_key_exists(
        'actual_minutes',
        $historicalRows[
            1
        ]
        ??
        []
    )
    &&
    $historicalRows[
        1
    ]['actual_minutes']
    ===
    0
);


playerCalibrationHistoricalEvidenceCheck(
    'Missing realised outcome leaves realised minutes null',
    array_key_exists(
        'actual_minutes',
        $historicalRows[
            2
        ]
        ??
        []
    )
    &&
    $historicalRows[
        2
    ]['actual_minutes']
    ===
    null
);


echo "<br>";

/*
 * ============================================================
 * SCENARIO H
 * HISTORICAL IDENTITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Historical Identity<br>";
echo "============================================<br>";


playerCalibrationHistoricalEvidenceCheck(
    'FPL player ID is preserved',
    ($historicalRows[0]['fpl_player_id'] ?? null) === 1001
);


playerCalibrationHistoricalEvidenceCheck(
    'Historical player name is preserved',
    ($historicalRows[0]['name'] ?? null) === 'Player One'
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * MISSING SNAPSHOT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Missing Snapshot<br>";
echo "============================================<br>";


$missingSnapshotOutcomeService =
    new PlayerCalibrationHistoricalEvidenceOutcomeServiceStub(
        $outcomes
    );


$missingSnapshotService =
    new PlayerCalibrationHistoricalEvidenceService(
        new PlayerCalibrationHistoricalEvidenceSnapshotRepositoryStub(
            null
        ),
        $missingSnapshotOutcomeService
    );


$missingSnapshotResult =
    $missingSnapshotService->build(
        2702264,
        5
    );


playerCalibrationHistoricalEvidenceCheck(
    'Missing immutable snapshot returns null',
    $missingSnapshotResult === null
);


playerCalibrationHistoricalEvidenceCheck(
    'Missing snapshot prevents realised outcome lookup',
    $missingSnapshotOutcomeService->calls === []
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * MISSING / MALFORMED RANKING EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Missing Ranking Evidence<br>";
echo "============================================<br>";


$emptyRankingSnapshot =
    $snapshot;


$emptyRankingSnapshot[
    'player_rankings'
] =
    [];


$emptyRankingResult =
    (
        new PlayerCalibrationHistoricalEvidenceService(
            new PlayerCalibrationHistoricalEvidenceSnapshotRepositoryStub(
                $emptyRankingSnapshot
            ),
            new PlayerCalibrationHistoricalEvidenceOutcomeServiceStub(
                $outcomes
            )
        )
    )
        ->build(
            2702264,
            5
        );


playerCalibrationHistoricalEvidenceCheck(
    'Empty historical ranking evidence remains an empty sample',
    ($emptyRankingResult['historical_rows'] ?? null) === []
);


$malformedRankingSnapshot =
    $snapshot;


$malformedRankingSnapshot[
    'player_rankings'
][] =
    'not-an-array';


$malformedRankingSnapshot[
    'player_rankings'
][] = [
    'player_id' => 0
];


$malformedRankingResult =
    (
        new PlayerCalibrationHistoricalEvidenceService(
            new PlayerCalibrationHistoricalEvidenceSnapshotRepositoryStub(
                $malformedRankingSnapshot
            ),
            new PlayerCalibrationHistoricalEvidenceOutcomeServiceStub(
                $outcomes
            )
        )
    )
        ->build(
            2702264,
            5
        );


playerCalibrationHistoricalEvidenceCheck(
    'Malformed ranking rows and non-positive player IDs are ignored',
    count(
        $malformedRankingResult[
            'historical_rows'
        ]
        ?? []
    )
    === 3
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * SOURCE IMMUTABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Source Evidence Immutability<br>";
echo "============================================<br>";


$sourceSnapshot =
    $snapshot;


$sourceOutcomes =
    $outcomes;


$immutabilitySnapshotRepository =
    new PlayerCalibrationHistoricalEvidenceSnapshotRepositoryStub(
        $snapshot
    );


$immutabilityOutcomeService =
    new PlayerCalibrationHistoricalEvidenceOutcomeServiceStub(
        $outcomes
    );


$immutabilityService =
    new PlayerCalibrationHistoricalEvidenceService(
        $immutabilitySnapshotRepository,
        $immutabilityOutcomeService
    );


$immutabilityService->build(
    2702264,
    5
);


playerCalibrationHistoricalEvidenceCheck(
    'Historical evidence assembly does not mutate the recommendation snapshot',
    $immutabilitySnapshotRepository->snapshot
    === $sourceSnapshot
);


playerCalibrationHistoricalEvidenceCheck(
    'Historical evidence assembly does not mutate realised outcomes',
    $immutabilityOutcomeService->outcomes
    === $sourceOutcomes
);


echo "<br>";


playerCalibrationHistoricalEvidenceSummary();