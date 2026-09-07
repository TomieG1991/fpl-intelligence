<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Gameweek Backtesting Evidence Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function backtestingEvidenceTestResult(
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
 * SERVICE AVAILABILITY
 * ============================================================
 */

backtestingEvidenceTestResult(
    class_exists(
        'GameweekBacktestingEvidenceService'
    ),
    'GameweekBacktestingEvidenceService exists.'
);


if (
    !class_exists(
        'GameweekBacktestingEvidenceService'
    )
) {

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


/*
 * ============================================================
 * CONTROLLED TEST DOUBLES
 * ============================================================
 */

class BacktestingEvidenceGameweekRepositoryDouble
{
    public ?array $gameweek =
        null;

    public int $calls =
        0;

    public ?int $requestedGameweekId =
        null;


    public function getById(
        int $gameweekId
    ): ?array {

        $this->calls++;

        $this->requestedGameweekId =
            $gameweekId;

        return
            $this->gameweek;
    }
}


class BacktestingEvidenceAvailabilityDouble
{
    public bool $available =
        false;

    public int $calls =
        0;

    public ?array $receivedGameweek =
        null;


    public function isAvailable(
        array $gameweek
    ): bool {

        $this->calls++;

        $this->receivedGameweek =
            $gameweek;

        return
            $this->available;
    }
}


class BacktestingEvidenceSnapshotRepositoryDouble
{
    public ?array $snapshot =
        null;

    public int $calls =
        0;

    public ?int $requestedEntryId =
        null;

    public ?int $requestedGameweekId =
        null;


    public function getByEntryAndGameweek(
        int $entryId,
        int $gameweekId
    ): ?array {

        $this->calls++;

        $this->requestedEntryId =
            $entryId;

        $this->requestedGameweekId =
            $gameweekId;

        return
            $this->snapshot;
    }
}


class BacktestingEvidenceOutcomeServiceDouble
{
    public array $outcomes =
        [];

    public int $calls =
        0;

    public ?int $requestedGameweekId =
        null;


    public function getByGameweekId(
        int $gameweekId
    ): array {

        $this->calls++;

        $this->requestedGameweekId =
            $gameweekId;

        return
            $this->outcomes;
    }
}


/*
 * ============================================================
 * FACTORY
 * ============================================================
 */

function createBacktestingEvidenceService(
    BacktestingEvidenceGameweekRepositoryDouble $gameweekRepository,
    BacktestingEvidenceAvailabilityDouble $availability,
    BacktestingEvidenceSnapshotRepositoryDouble $snapshotRepository,
    BacktestingEvidenceOutcomeServiceDouble $outcomeService
): GameweekBacktestingEvidenceService {

    return
        new GameweekBacktestingEvidenceService(
            $gameweekRepository,
            $availability,
            $snapshotRepository,
            $outcomeService
        );
}


/*
 * ============================================================
 * STANDARD EVIDENCE
 * ============================================================
 */

$standardGameweek = [
    'id' => 5,
    'fpl_gameweek_id' => 1,
    'name' => 'Gameweek 1',
    'deadline_time' => '2026-08-21 17:30:00',
    'finished' => 1,
    'data_checked' => 1
];


$standardSnapshot = [
    'id' => 1001,
    'gameweek_id' => 5,
    'entry_id' => 935009001,
    'captured_at' => '2026-08-21 17:29:00',
    'deadline_time' => '2026-08-21 17:30:00',

    'player_projections' => [
        [
            'player_id' => 101,
            'projected_points' => 7.5,
            'projected_minutes' => 90
        ],
        [
            'player_id' => 102,
            'projected_points' => 5.0,
            'projected_minutes' => 80
        ]
    ],

    'starting_xi' => [
        [
            'player_id' => 101
        ],
        [
            'player_id' => 102
        ]
    ],

    'captain_recommendation' => [
        'captain' => [
            'player_id' => 101
        ],
        'vice_captain' => [
            'player_id' => 102
        ]
    ],

    'transfer_recommendations' => [],

    'gameweek_decision' => [
        'overall_action' => 'Hold'
    ],

    'chip_recommendations' => []
];


$standardOutcomes = [
    [
        'gameweek_id' => 5,
        'player_id' => 101,
        'fixture_count' => 1,
        'total_points' => 8,
        'minutes' => 90,
        'starts' => 1,
        'goals' => 1,
        'assists' => 0,
        'clean_sheets' => 1,
        'bonus' => 2
    ],
    [
        'gameweek_id' => 5,
        'player_id' => 102,
        'fixture_count' => 1,
        'total_points' => 3,
        'minutes' => 72,
        'starts' => 1,
        'goals' => 0,
        'assists' => 0,
        'clean_sheets' => 0,
        'bonus' => 0
    ]
];


/*
 * ============================================================
 * SCENARIO A
 * INVALID ENTRY IDENTITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Invalid Entry Identity<br>";
echo "============================================<br>";


foreach (
    [
        0,
        -1
    ]
    as $invalidEntryId
) {

    $gameweekRepository =
        new BacktestingEvidenceGameweekRepositoryDouble();

    $availability =
        new BacktestingEvidenceAvailabilityDouble();

    $snapshotRepository =
        new BacktestingEvidenceSnapshotRepositoryDouble();

    $outcomeService =
        new BacktestingEvidenceOutcomeServiceDouble();


    $service =
        createBacktestingEvidenceService(
            $gameweekRepository,
            $availability,
            $snapshotRepository,
            $outcomeService
        );


    $exceptionThrown =
        false;


    try {

        $service->getEvidence(
            $invalidEntryId,
            5
        );

    } catch (
        InvalidArgumentException $exception
    ) {

        $exceptionThrown =
            true;
    }


    backtestingEvidenceTestResult(
        $exceptionThrown,
        'Entry ID '
            . $invalidEntryId
            . ' is rejected.'
    );


    backtestingEvidenceTestResult(
        $gameweekRepository->calls === 0,
        'Invalid entry ID does not query gameweek evidence.'
    );


    backtestingEvidenceTestResult(
        $snapshotRepository->calls === 0,
        'Invalid entry ID does not query recommendation history.'
    );


    backtestingEvidenceTestResult(
        $outcomeService->calls === 0,
        'Invalid entry ID does not query actual outcomes.'
    );
}


/*
 * ============================================================
 * SCENARIO B
 * INVALID GAMEWEEK IDENTITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Invalid Gameweek Identity<br>";
echo "============================================<br>";


foreach (
    [
        0,
        -1
    ]
    as $invalidGameweekId
) {

    $gameweekRepository =
        new BacktestingEvidenceGameweekRepositoryDouble();

    $availability =
        new BacktestingEvidenceAvailabilityDouble();

    $snapshotRepository =
        new BacktestingEvidenceSnapshotRepositoryDouble();

    $outcomeService =
        new BacktestingEvidenceOutcomeServiceDouble();


    $service =
        createBacktestingEvidenceService(
            $gameweekRepository,
            $availability,
            $snapshotRepository,
            $outcomeService
        );


    $exceptionThrown =
        false;


    try {

        $service->getEvidence(
            935009001,
            $invalidGameweekId
        );

    } catch (
        InvalidArgumentException $exception
    ) {

        $exceptionThrown =
            true;
    }


    backtestingEvidenceTestResult(
        $exceptionThrown,
        'Gameweek ID '
            . $invalidGameweekId
            . ' is rejected.'
    );


    backtestingEvidenceTestResult(
        $gameweekRepository->calls === 0,
        'Invalid gameweek ID does not query gameweek evidence.'
    );


    backtestingEvidenceTestResult(
        $snapshotRepository->calls === 0,
        'Invalid gameweek ID does not query recommendation history.'
    );


    backtestingEvidenceTestResult(
        $outcomeService->calls === 0,
        'Invalid gameweek ID does not query actual outcomes.'
    );
}


/*
 * ============================================================
 * SCENARIO C
 * GAMEWEEK DOES NOT EXIST
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Missing Gameweek<br>";
echo "============================================<br>";


$gameweekRepository =
    new BacktestingEvidenceGameweekRepositoryDouble();

$availability =
    new BacktestingEvidenceAvailabilityDouble();

$snapshotRepository =
    new BacktestingEvidenceSnapshotRepositoryDouble();

$outcomeService =
    new BacktestingEvidenceOutcomeServiceDouble();


$gameweekRepository->gameweek =
    null;


$service =
    createBacktestingEvidenceService(
        $gameweekRepository,
        $availability,
        $snapshotRepository,
        $outcomeService
    );


$result =
    $service->getEvidence(
        935009001,
        5
    );


backtestingEvidenceTestResult(
    $result === [
        'status' => 'Unavailable',
        'reason' => 'Gameweek does not exist',
        'entry_id' => 935009001,
        'gameweek_id' => 5,
        'gameweek' => null,
        'recommendation_snapshot' => null,
        'player_outcomes' => []
    ],
    'Missing gameweek returns the exact unavailable contract.'
);


backtestingEvidenceTestResult(
    $gameweekRepository->calls === 1,
    'Missing gameweek is queried exactly once.'
);


backtestingEvidenceTestResult(
    $gameweekRepository->requestedGameweekId === 5,
    'Requested local gameweek ID is passed to repository.'
);


backtestingEvidenceTestResult(
    $availability->calls === 0,
    'Missing gameweek is not passed to availability evaluation.'
);


backtestingEvidenceTestResult(
    $snapshotRepository->calls === 0,
    'Missing gameweek does not query recommendation history.'
);


backtestingEvidenceTestResult(
    $outcomeService->calls === 0,
    'Missing gameweek does not query actual outcomes.'
);


/*
 * ============================================================
 * SCENARIO D
 * GAMEWEEK NOT AUTHORITATIVE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Gameweek Not Authoritative<br>";
echo "============================================<br>";


$gameweekRepository =
    new BacktestingEvidenceGameweekRepositoryDouble();

$availability =
    new BacktestingEvidenceAvailabilityDouble();

$snapshotRepository =
    new BacktestingEvidenceSnapshotRepositoryDouble();

$outcomeService =
    new BacktestingEvidenceOutcomeServiceDouble();


$unfinishedGameweek =
    $standardGameweek;


$unfinishedGameweek[
    'finished'
] =
    0;


$gameweekRepository->gameweek =
    $unfinishedGameweek;


$availability->available =
    false;


$service =
    createBacktestingEvidenceService(
        $gameweekRepository,
        $availability,
        $snapshotRepository,
        $outcomeService
    );


$result =
    $service->getEvidence(
        935009001,
        5
    );


backtestingEvidenceTestResult(
    $result === [
        'status' => 'Unavailable',
        'reason' => 'Gameweek outcomes are not yet authoritative',
        'entry_id' => 935009001,
        'gameweek_id' => 5,
        'gameweek' => $unfinishedGameweek,
        'recommendation_snapshot' => null,
        'player_outcomes' => []
    ],
    'Non-authoritative gameweek returns the exact unavailable contract.'
);


backtestingEvidenceTestResult(
    $availability->calls === 1,
    'Existing gameweek is checked for outcome availability.'
);


backtestingEvidenceTestResult(
    $availability->receivedGameweek === $unfinishedGameweek,
    'Exact gameweek evidence is passed to availability service.'
);


backtestingEvidenceTestResult(
    $snapshotRepository->calls === 0,
    'Non-authoritative gameweek does not query recommendation history.'
);


backtestingEvidenceTestResult(
    $outcomeService->calls === 0,
    'Non-authoritative gameweek does not query partial actual outcomes.'
);


/*
 * ============================================================
 * SCENARIO E
 * AUTHORITATIVE GAMEWEEK WITHOUT SNAPSHOT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Missing Recommendation Snapshot<br>";
echo "============================================<br>";


$gameweekRepository =
    new BacktestingEvidenceGameweekRepositoryDouble();

$availability =
    new BacktestingEvidenceAvailabilityDouble();

$snapshotRepository =
    new BacktestingEvidenceSnapshotRepositoryDouble();

$outcomeService =
    new BacktestingEvidenceOutcomeServiceDouble();


$gameweekRepository->gameweek =
    $standardGameweek;


$availability->available =
    true;


$snapshotRepository->snapshot =
    null;


$service =
    createBacktestingEvidenceService(
        $gameweekRepository,
        $availability,
        $snapshotRepository,
        $outcomeService
    );


$result =
    $service->getEvidence(
        935009001,
        5
    );


backtestingEvidenceTestResult(
    $result === [
        'status' => 'Incomplete',
        'reason' => 'Recommendation snapshot is unavailable',
        'entry_id' => 935009001,
        'gameweek_id' => 5,
        'gameweek' => $standardGameweek,
        'recommendation_snapshot' => null,
        'player_outcomes' => []
    ],
    'Missing recommendation snapshot returns the exact incomplete contract.'
);


backtestingEvidenceTestResult(
    $snapshotRepository->calls === 1,
    'Authoritative gameweek queries recommendation history.'
);


backtestingEvidenceTestResult(
    $snapshotRepository->requestedEntryId === 935009001,
    'Correct entry ID is used for recommendation history.'
);


backtestingEvidenceTestResult(
    $snapshotRepository->requestedGameweekId === 5,
    'Correct local gameweek ID is used for recommendation history.'
);


backtestingEvidenceTestResult(
    $outcomeService->calls === 0,
    'Missing recommendation snapshot does not require actual outcome aggregation.'
);


/*
 * ============================================================
 * SCENARIO F
 * SNAPSHOT EXISTS BUT OUTCOMES ARE MISSING
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Missing Actual Outcomes<br>";
echo "============================================<br>";


$gameweekRepository =
    new BacktestingEvidenceGameweekRepositoryDouble();

$availability =
    new BacktestingEvidenceAvailabilityDouble();

$snapshotRepository =
    new BacktestingEvidenceSnapshotRepositoryDouble();

$outcomeService =
    new BacktestingEvidenceOutcomeServiceDouble();


$gameweekRepository->gameweek =
    $standardGameweek;


$availability->available =
    true;


$snapshotRepository->snapshot =
    $standardSnapshot;


$outcomeService->outcomes =
    [];


$service =
    createBacktestingEvidenceService(
        $gameweekRepository,
        $availability,
        $snapshotRepository,
        $outcomeService
    );


$result =
    $service->getEvidence(
        935009001,
        5
    );


backtestingEvidenceTestResult(
    $result === [
        'status' => 'Incomplete',
        'reason' => 'Player outcome evidence is unavailable',
        'entry_id' => 935009001,
        'gameweek_id' => 5,
        'gameweek' => $standardGameweek,
        'recommendation_snapshot' => $standardSnapshot,
        'player_outcomes' => []
    ],
    'Missing actual outcomes returns the exact incomplete contract.'
);


backtestingEvidenceTestResult(
    $outcomeService->calls === 1,
    'Actual outcomes are requested after recommendation history exists.'
);


backtestingEvidenceTestResult(
    $outcomeService->requestedGameweekId === 5,
    'Correct local gameweek ID is used for actual outcomes.'
);


/*
 * ============================================================
 * SCENARIO G
 * COMPLETE READY EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Complete Ready Evidence<br>";
echo "============================================<br>";


$gameweekRepository =
    new BacktestingEvidenceGameweekRepositoryDouble();

$availability =
    new BacktestingEvidenceAvailabilityDouble();

$snapshotRepository =
    new BacktestingEvidenceSnapshotRepositoryDouble();

$outcomeService =
    new BacktestingEvidenceOutcomeServiceDouble();


$gameweekRepository->gameweek =
    $standardGameweek;


$availability->available =
    true;


$snapshotRepository->snapshot =
    $standardSnapshot;


$outcomeService->outcomes =
    $standardOutcomes;


$service =
    createBacktestingEvidenceService(
        $gameweekRepository,
        $availability,
        $snapshotRepository,
        $outcomeService
    );


$result =
    $service->getEvidence(
        935009001,
        5
    );


$expectedReadyResult = [
    'status' => 'Ready',
    'reason' => null,
    'entry_id' => 935009001,
    'gameweek_id' => 5,
    'gameweek' => $standardGameweek,
    'recommendation_snapshot' => $standardSnapshot,
    'player_outcomes' => $standardOutcomes
];


backtestingEvidenceTestResult(
    $result === $expectedReadyResult,
    'Complete evidence returns the exact ready contract.'
);


backtestingEvidenceTestResult(
    $gameweekRepository->calls === 1,
    'Ready evidence queries gameweek exactly once.'
);


backtestingEvidenceTestResult(
    $availability->calls === 1,
    'Ready evidence checks availability exactly once.'
);


backtestingEvidenceTestResult(
    $snapshotRepository->calls === 1,
    'Ready evidence queries recommendation snapshot exactly once.'
);


backtestingEvidenceTestResult(
    $outcomeService->calls === 1,
    'Ready evidence queries actual outcomes exactly once.'
);


/*
 * ============================================================
 * SCENARIO H
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


$gameweekRepository =
    new BacktestingEvidenceGameweekRepositoryDouble();

$availability =
    new BacktestingEvidenceAvailabilityDouble();

$snapshotRepository =
    new BacktestingEvidenceSnapshotRepositoryDouble();

$outcomeService =
    new BacktestingEvidenceOutcomeServiceDouble();


$gameweekRepository->gameweek =
    $standardGameweek;


$availability->available =
    true;


$snapshotRepository->snapshot =
    $standardSnapshot;


$outcomeService->outcomes =
    $standardOutcomes;


$originalGameweek =
    $gameweekRepository->gameweek;


$originalSnapshot =
    $snapshotRepository->snapshot;


$originalOutcomes =
    $outcomeService->outcomes;


$service =
    createBacktestingEvidenceService(
        $gameweekRepository,
        $availability,
        $snapshotRepository,
        $outcomeService
    );


$service->getEvidence(
    935009001,
    5
);


backtestingEvidenceTestResult(
    $gameweekRepository->gameweek
        ===
        $originalGameweek,
    'Gameweek source evidence is not mutated.'
);


backtestingEvidenceTestResult(
    $snapshotRepository->snapshot
        ===
        $originalSnapshot,
    'Immutable recommendation evidence is not mutated.'
);


backtestingEvidenceTestResult(
    $outcomeService->outcomes
        ===
        $originalOutcomes,
    'Actual outcome evidence is not mutated.'
);


/*
 * ============================================================
 * SCENARIO I
 * NO BACKTESTING CALCULATIONS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Evidence Assembly Only<br>";
echo "============================================<br>";


backtestingEvidenceTestResult(
    !array_key_exists(
        'captain_result',
        $expectedReadyResult
    ),
    'Evidence service does not calculate captain success.'
);


backtestingEvidenceTestResult(
    !array_key_exists(
        'starting_xi_result',
        $expectedReadyResult
    ),
    'Evidence service does not calculate Starting XI success.'
);


backtestingEvidenceTestResult(
    !array_key_exists(
        'transfer_result',
        $expectedReadyResult
    ),
    'Evidence service does not calculate transfer success.'
);


backtestingEvidenceTestResult(
    !array_key_exists(
        'projection_error',
        $expectedReadyResult
    ),
    'Evidence service does not calculate projection accuracy.'
);


backtestingEvidenceTestResult(
    !array_key_exists(
        'score',
        $expectedReadyResult
    ),
    'Evidence service does not create a synthetic backtesting score.'
);


/*
 * ============================================================
 * SCENARIO J
 * IDENTITY IS PRESERVED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Evidence Identity<br>";
echo "============================================<br>";


backtestingEvidenceTestResult(
    $expectedReadyResult[
        'entry_id'
    ] === 935009001,
    'Ready evidence preserves requested entry identity.'
);


backtestingEvidenceTestResult(
    $expectedReadyResult[
        'gameweek_id'
    ] === 5,
    'Ready evidence preserves requested local gameweek identity.'
);


backtestingEvidenceTestResult(
    $expectedReadyResult[
        'gameweek'
    ][
        'fpl_gameweek_id'
    ] === 1,
    'Ready evidence preserves official FPL gameweek identity.'
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


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}