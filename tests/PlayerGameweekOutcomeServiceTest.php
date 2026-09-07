<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Outcome Service Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function outcomeTestResult(
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


function outcomeTestThrows(
    callable $callback,
    string $expectedClass,
    string $message
): void {

    try {

        $callback();

    } catch (
        Throwable $exception
    ) {

        outcomeTestResult(
            $exception instanceof $expectedClass,
            $message
        );

        return;
    }


    outcomeTestResult(
        false,
        $message
    );
}


/*
 * ============================================================
 * CONTROLLED REPOSITORY
 * ============================================================
 */

class OutcomeTestFixtureHistoryRepository
{
    private array $rowsByGameweek;


    public array $requestedGameweekIds = [];


    public function __construct(
        array $rowsByGameweek = []
    ) {

        $this->rowsByGameweek =
            $rowsByGameweek;
    }


    public function getByGameweekId(
        int $gameweekId
    ): array {

        $this->requestedGameweekIds[] =
            $gameweekId;


        return $this->rowsByGameweek[
            $gameweekId
        ]
            ?? [];
    }
}


/*
 * ============================================================
 * FIXTURE FACTORY
 * ============================================================
 */

function outcomeFixtureRow(
    int $gameweekId,
    int $playerId,
    int $fixtureId,
    int $totalPoints,
    int $minutes,
    int $starts,
    int $goals = 0,
    int $assists = 0,
    int $cleanSheets = 0,
    int $bonus = 0
): array {

    return [

        'gameweek_id' =>
            $gameweekId,

        'player_id' =>
            $playerId,

        'fixture_id' =>
            $fixtureId,

        'total_points' =>
            $totalPoints,

        'minutes' =>
            $minutes,

        'starts' =>
            $starts,

        'goals' =>
            $goals,

        'assists' =>
            $assists,

        'clean_sheets' =>
            $cleanSheets,

        'bonus' =>
            $bonus
    ];
}


/*
 * ============================================================
 * SERVICE AVAILABILITY
 * ============================================================
 */

outcomeTestResult(
    class_exists(
        'PlayerGameweekOutcomeService'
    ),
    'PlayerGameweekOutcomeService exists.'
);


if (
    !class_exists(
        'PlayerGameweekOutcomeService'
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

    exit;
}


/*
 * ============================================================
 * SCENARIO A
 * INVALID GAMEWEEK IDENTITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Invalid Gameweek Identity<br>";
echo "============================================<br>";


$repository =
    new OutcomeTestFixtureHistoryRepository();


$service =
    new PlayerGameweekOutcomeService(
        $repository
    );


foreach (
    [
        0,
        -1,
        -999
    ]
    as $invalidGameweekId
) {

    outcomeTestThrows(
        static function () use (
            $service,
            $invalidGameweekId
        ): void {

            $service->getByGameweekId(
                $invalidGameweekId
            );
        },
        InvalidArgumentException::class,
        'Invalid gameweek ID '
            . $invalidGameweekId
            . ' is rejected.'
    );
}


outcomeTestResult(
    $repository->requestedGameweekIds === [],
    'Invalid gameweek identities do not query fixture history.'
);


/*
 * ============================================================
 * SCENARIO B
 * NO FIXTURE-HISTORY EVIDENCE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: No Fixture-History Evidence<br>";
echo "============================================<br>";


$repository =
    new OutcomeTestFixtureHistoryRepository([
        5 => []
    ]);


$service =
    new PlayerGameweekOutcomeService(
        $repository
    );


$result =
    $service->getByGameweekId(
        5
    );


outcomeTestResult(
    $result === [],
    'No fixture-history evidence returns an empty outcome set.'
);


outcomeTestResult(
    $repository->requestedGameweekIds === [
        5
    ],
    'The requested local gameweek ID is passed to fixture history.'
);


/*
 * ============================================================
 * SCENARIO C
 * SINGLE PLAYER / SINGLE FIXTURE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Single Player / Single Fixture<br>";
echo "============================================<br>";


$repository =
    new OutcomeTestFixtureHistoryRepository([

        5 => [

            outcomeFixtureRow(
                5,
                101,
                5001,
                8,
                90,
                1,
                1,
                0,
                1,
                2
            )
        ]
    ]);


$service =
    new PlayerGameweekOutcomeService(
        $repository
    );


$result =
    $service->getByGameweekId(
        5
    );


outcomeTestResult(
    count(
        $result
    ) === 1,
    'One player produces one gameweek outcome.'
);


$singleOutcome =
    $result[0]
    ?? [];


outcomeTestResult(
    $singleOutcome === [

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
    'Single-fixture evidence is exposed through the exact outcome contract.'
);


/*
 * ============================================================
 * SCENARIO D
 * MULTIPLE PLAYERS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Multiple Players<br>";
echo "============================================<br>";


$repository =
    new OutcomeTestFixtureHistoryRepository([

        6 => [

            outcomeFixtureRow(
                6,
                103,
                6003,
                2,
                90,
                1
            ),

            outcomeFixtureRow(
                6,
                101,
                6001,
                10,
                90,
                1,
                1,
                1,
                1,
                3
            ),

            outcomeFixtureRow(
                6,
                102,
                6002,
                6,
                72,
                1,
                0,
                1,
                0,
                1
            )
        ]
    ]);


$service =
    new PlayerGameweekOutcomeService(
        $repository
    );


$result =
    $service->getByGameweekId(
        6
    );


outcomeTestResult(
    count(
        $result
    ) === 3,
    'Multiple players produce one outcome each.'
);


outcomeTestResult(
    array_column(
        $result,
        'player_id'
    ) === [
        101,
        102,
        103
    ],
    'Multiple player outcomes are returned in player ID order.'
);


outcomeTestResult(
    (
        $result[0][
            'total_points'
        ]
        ?? null
    ) === 10,
    'First player points are preserved.'
);


outcomeTestResult(
    (
        $result[1][
            'total_points'
        ]
        ?? null
    ) === 6,
    'Second player points are preserved.'
);


outcomeTestResult(
    (
        $result[2][
            'total_points'
        ]
        ?? null
    ) === 2,
    'Third player points are preserved.'
);


/*
 * ============================================================
 * SCENARIO E
 * DOUBLE GAMEWEEK AGGREGATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Double Gameweek Aggregation<br>";
echo "============================================<br>";


$repository =
    new OutcomeTestFixtureHistoryRepository([

        7 => [

            outcomeFixtureRow(
                7,
                201,
                7001,
                6,
                90,
                1,
                1,
                0,
                1,
                1
            ),

            outcomeFixtureRow(
                7,
                201,
                7002,
                7,
                78,
                1,
                0,
                1,
                0,
                2
            )
        ]
    ]);


$service =
    new PlayerGameweekOutcomeService(
        $repository
    );


$result =
    $service->getByGameweekId(
        7
    );


outcomeTestResult(
    count(
        $result
    ) === 1,
    'Two fixture rows for one player produce one gameweek outcome.'
);


$doubleOutcome =
    $result[0]
    ?? [];


outcomeTestResult(
    (
        $doubleOutcome[
            'fixture_count'
        ]
        ?? null
    ) === 2,
    'Double Gameweek fixture count is two.'
);


outcomeTestResult(
    (
        $doubleOutcome[
            'total_points'
        ]
        ?? null
    ) === 13,
    'Double Gameweek points are summed.'
);


outcomeTestResult(
    (
        $doubleOutcome[
            'minutes'
        ]
        ?? null
    ) === 168,
    'Double Gameweek minutes are summed.'
);


outcomeTestResult(
    (
        $doubleOutcome[
            'starts'
        ]
        ?? null
    ) === 2,
    'Double Gameweek starts are summed.'
);


outcomeTestResult(
    (
        $doubleOutcome[
            'goals'
        ]
        ?? null
    ) === 1,
    'Double Gameweek goals are summed.'
);


outcomeTestResult(
    (
        $doubleOutcome[
            'assists'
        ]
        ?? null
    ) === 1,
    'Double Gameweek assists are summed.'
);


outcomeTestResult(
    (
        $doubleOutcome[
            'clean_sheets'
        ]
        ?? null
    ) === 1,
    'Double Gameweek clean sheets are summed.'
);


outcomeTestResult(
    (
        $doubleOutcome[
            'bonus'
        ]
        ?? null
    ) === 3,
    'Double Gameweek bonus is summed.'
);


/*
 * ============================================================
 * SCENARIO F
 * ZERO-MINUTE OFFICIAL HISTORY ROW
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Zero-Minute Official History Row<br>";
echo "============================================<br>";


$repository =
    new OutcomeTestFixtureHistoryRepository([

        8 => [

            outcomeFixtureRow(
                8,
                301,
                8001,
                0,
                0,
                0
            )
        ]
    ]);


$service =
    new PlayerGameweekOutcomeService(
        $repository
    );


$result =
    $service->getByGameweekId(
        8
    );


outcomeTestResult(
    count(
        $result
    ) === 1,
    'A zero-minute official history row remains known outcome evidence.'
);


outcomeTestResult(
    (
        $result[0][
            'fixture_count'
        ]
        ?? null
    ) === 1,
    'Zero-minute history still counts as an official fixture row.'
);


outcomeTestResult(
    (
        $result[0][
            'minutes'
        ]
        ?? null
    ) === 0,
    'Zero minutes are preserved.'
);


outcomeTestResult(
    (
        $result[0][
            'total_points'
        ]
        ?? null
    ) === 0,
    'Zero points are preserved.'
);


/*
 * ============================================================
 * SCENARIO G
 * NEGATIVE FPL POINTS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Negative FPL Points<br>";
echo "============================================<br>";

$repository =
    new OutcomeTestFixtureHistoryRepository([

        9 => [

            outcomeFixtureRow(
                9,
                401,
                9001,
                -2,
                90,
                1,
                0,
                0,
                0,
                0
            )
        ]
    ]);


$service =
    new PlayerGameweekOutcomeService(
        $repository
    );


$result =
    $service->getByGameweekId(
        9
    );


outcomeTestResult(
    (
        $result[0][
            'total_points'
        ]
        ?? null
    ) === -2,
    'Negative realised FPL points are preserved.'
);


/*
 * ============================================================
 * SCENARIO H
 * DETERMINISTIC PLAYER ORDERING
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Deterministic Player Ordering<br>";
echo "============================================<br>";


$repository =
    new OutcomeTestFixtureHistoryRepository([

        10 => [

            outcomeFixtureRow(
                10,
                900,
                10001,
                1,
                90,
                1
            ),

            outcomeFixtureRow(
                10,
                100,
                10002,
                2,
                90,
                1
            ),

            outcomeFixtureRow(
                10,
                500,
                10003,
                3,
                90,
                1
            ),

            outcomeFixtureRow(
                10,
                100,
                10004,
                4,
                90,
                1
            )
        ]
    ]);


$service =
    new PlayerGameweekOutcomeService(
        $repository
    );


$result =
    $service->getByGameweekId(
        10
    );


outcomeTestResult(
    array_column(
        $result,
        'player_id'
    ) === [
        100,
        500,
        900
    ],
    'Outcome ordering is deterministic regardless of fixture-row order.'
);


outcomeTestResult(
    (
        $result[0][
            'fixture_count'
        ]
        ?? null
    ) === 2,
    'Ordering does not interfere with multi-fixture aggregation.'
);


outcomeTestResult(
    (
        $result[0][
            'total_points'
        ]
        ?? null
    ) === 6,
    'Repeated player rows are aggregated before deterministic ordering.'
);


/*
 * ============================================================
 * SCENARIO I
 * SOURCE EVIDENCE REMAINS UNCHANGED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Source Evidence Remains Unchanged<br>";
echo "============================================<br>";


$sourceRows = [

    outcomeFixtureRow(
        11,
        601,
        11001,
        5,
        90,
        1,
        0,
        1,
        0,
        2
    ),

    outcomeFixtureRow(
        11,
        601,
        11002,
        3,
        45,
        0,
        0,
        0,
        0,
        0
    )
];


$originalRows =
    $sourceRows;


$repository =
    new OutcomeTestFixtureHistoryRepository([
        11 => $sourceRows
    ]);


$service =
    new PlayerGameweekOutcomeService(
        $repository
    );


$service->getByGameweekId(
    11
);


outcomeTestResult(
    $sourceRows === $originalRows,
    'Gameweek outcome aggregation does not mutate source fixture evidence.'
);


/*
 * ============================================================
 * SCENARIO J
 * EXACT OUTPUT CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Exact Output Contract<br>";
echo "============================================<br>";


$repository =
    new OutcomeTestFixtureHistoryRepository([

        12 => [

            array_merge(
                outcomeFixtureRow(
                    12,
                    701,
                    12001,
                    9,
                    90,
                    1,
                    1,
                    1,
                    1,
                    3
                ),
                [
                    'fpl_player_id' => 999701,
                    'team_id' => 55,
                    'opponent_team_id' => 66,
                    'was_home' => 1,
                    'price' => 7.5,
                    'selected' => 123456,
                    'expected_goals' => 0.75,
                    'ict_index' => 12.4
                ]
            )
        ]
    ]);


$service =
    new PlayerGameweekOutcomeService(
        $repository
    );


$result =
    $service->getByGameweekId(
        12
    );


$contractKeys =
    array_keys(
        $result[0]
        ?? []
    );


outcomeTestResult(
    $contractKeys === [
        'gameweek_id',
        'player_id',
        'fixture_count',
        'total_points',
        'minutes',
        'starts',
        'goals',
        'assists',
        'clean_sheets',
        'bonus'
    ],
    'Outcome exposes only the deliberately defined factual contract.'
);


outcomeTestResult(
    !array_key_exists(
        'price',
        $result[0]
        ?? []
    ),
    'Fixture-context price is not copied into aggregated outcome evidence.'
);


outcomeTestResult(
    !array_key_exists(
        'opponent_team_id',
        $result[0]
        ?? []
    ),
    'Fixture-specific opponent is not copied into aggregated outcome evidence.'
);


outcomeTestResult(
    !array_key_exists(
        'expected_goals',
        $result[0]
        ?? []
    ),
    'Additional performance metrics are not included before aggregation semantics are defined.'
);


/*
 * ============================================================
 * SUMMARY
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