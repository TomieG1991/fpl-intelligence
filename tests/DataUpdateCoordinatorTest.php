<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Data Update Coordinator Test<br>";
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

function dataUpdateCoordinatorCheck(
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


function dataUpdateCoordinatorSection(
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


function dataUpdateCoordinatorSummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Data Update Coordinator Test Summary<br>";
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
 * SCENARIO A
 * CLASS CONTRACT
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario A: Class Contract'
);


dataUpdateCoordinatorCheck(
    'DataUpdateCoordinator exists.',
    class_exists(
        'DataUpdateCoordinator'
    )
);


if (
    !class_exists(
        'DataUpdateCoordinator'
    )
) {

    dataUpdateCoordinatorSummary();

    exit;
}


/*
 * ============================================================
 * SCENARIO B
 * ALL STEPS SUCCEED
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario B: All Steps Succeed'
);


$executionOrder =
    [];


$bootstrapResult = [

    'status' =>
        'Success',

    'records_updated' =>
        630
];


$fixturesResult = [

    'status' =>
        'Success',

    'records_updated' =>
        380
];


$historyResult = [

    'status' =>
        'Success',

    'records_updated' =>
        1250
];


$coordinator =
    new DataUpdateCoordinator(

        static function () use (
            &$executionOrder,
            $bootstrapResult
        ): array {

            $executionOrder[] =
                'bootstrap';

            return $bootstrapResult;
        },

        static function () use (
            &$executionOrder,
            $fixturesResult
        ): array {

            $executionOrder[] =
                'fixtures';

            return $fixturesResult;
        },

        static function () use (
            &$executionOrder,
            $historyResult
        ): array {

            $executionOrder[] =
                'player_fixture_history';

            return $historyResult;
        }
    );


$result =
    $coordinator
        ->run();


dataUpdateCoordinatorCheck(
    'Successful coordinator executes all steps in order.',
    $executionOrder
    ===
    [
        'bootstrap',
        'fixtures',
        'player_fixture_history'
    ]
);


dataUpdateCoordinatorCheck(
    'Successful coordinator returns Success status.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Success'
);


dataUpdateCoordinatorCheck(
    'Successful coordinator preserves bootstrap result.',
    (
        $result[
            'steps'
        ][
            'bootstrap'
        ]
        ?? null
    )
    ===
    $bootstrapResult
);


dataUpdateCoordinatorCheck(
    'Successful coordinator preserves fixtures result.',
    (
        $result[
            'steps'
        ][
            'fixtures'
        ]
        ?? null
    )
    ===
    $fixturesResult
);


dataUpdateCoordinatorCheck(
    'Successful coordinator preserves player history result.',
    (
        $result[
            'steps'
        ][
            'player_fixture_history'
        ]
        ?? null
    )
    ===
    $historyResult
);


/*
 * ============================================================
 * SCENARIO C
 * BOOTSTRAP FAILURE STOPS PIPELINE
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario C: Bootstrap Failure Stops Pipeline'
);


$executionOrder =
    [];


$bootstrapFailure = [

    'status' =>
        'Failed',

    'error_message' =>
        'Bootstrap update failed.'
];


$coordinator =
    new DataUpdateCoordinator(

        static function () use (
            &$executionOrder,
            $bootstrapFailure
        ): array {

            $executionOrder[] =
                'bootstrap';

            return $bootstrapFailure;
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'fixtures';

            return [
                'status' =>
                    'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_fixture_history';

            return [
                'status' =>
                    'Success'
            ];
        }
    );


$result =
    $coordinator
        ->run();


dataUpdateCoordinatorCheck(
    'Bootstrap failure stops later update steps.',
    $executionOrder
    ===
    [
        'bootstrap'
    ]
);


dataUpdateCoordinatorCheck(
    'Bootstrap failure returns Failed coordinator status.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataUpdateCoordinatorCheck(
    'Bootstrap failure result is preserved.',
    (
        $result[
            'steps'
        ][
            'bootstrap'
        ]
        ?? null
    )
    ===
    $bootstrapFailure
);


dataUpdateCoordinatorCheck(
    'Fixtures result is absent after bootstrap failure.',
    !array_key_exists(
        'fixtures',
        $result[
            'steps'
        ]
        ?? []
    )
);


dataUpdateCoordinatorCheck(
    'Player history result is absent after bootstrap failure.',
    !array_key_exists(
        'player_fixture_history',
        $result[
            'steps'
        ]
        ?? []
    )
);


/*
 * ============================================================
 * SCENARIO D
 * FIXTURE FAILURE STOPS PIPELINE
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario D: Fixture Failure Stops Pipeline'
);


$executionOrder =
    [];


$fixtureFailure = [

    'status' =>
        'Failed',

    'error_message' =>
        'Fixture update failed.'
];


$coordinator =
    new DataUpdateCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'bootstrap';

            return [
                'status' =>
                    'Success'
            ];
        },

        static function () use (
            &$executionOrder,
            $fixtureFailure
        ): array {

            $executionOrder[] =
                'fixtures';

            return $fixtureFailure;
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_fixture_history';

            return [
                'status' =>
                    'Success'
            ];
        }
    );


$result =
    $coordinator
        ->run();


dataUpdateCoordinatorCheck(
    'Fixture failure runs bootstrap then fixtures only.',
    $executionOrder
    ===
    [
        'bootstrap',
        'fixtures'
    ]
);


dataUpdateCoordinatorCheck(
    'Fixture failure returns Failed coordinator status.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataUpdateCoordinatorCheck(
    'Fixture failure result is preserved.',
    (
        $result[
            'steps'
        ][
            'fixtures'
        ]
        ?? null
    )
    ===
    $fixtureFailure
);


dataUpdateCoordinatorCheck(
    'Player history is not executed after fixture failure.',
    !array_key_exists(
        'player_fixture_history',
        $result[
            'steps'
        ]
        ?? []
    )
);


/*
 * ============================================================
 * SCENARIO E
 * PLAYER HISTORY PARTIAL
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario E: Player History Partial'
);


$executionOrder =
    [];


$partialHistoryResult = [

    'status' =>
        'Partial',

    'records_failed' =>
        2,

    'error_message' =>
        '2 players failed during processing.'
];


$coordinator =
    new DataUpdateCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'bootstrap';

            return [
                'status' =>
                    'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'fixtures';

            return [
                'status' =>
                    'Success'
            ];
        },

        static function () use (
            &$executionOrder,
            $partialHistoryResult
        ): array {

            $executionOrder[] =
                'player_fixture_history';

            return $partialHistoryResult;
        }
    );


$result =
    $coordinator
        ->run();


dataUpdateCoordinatorCheck(
    'Partial player history still executes all update steps.',
    $executionOrder
    ===
    [
        'bootstrap',
        'fixtures',
        'player_fixture_history'
    ]
);


dataUpdateCoordinatorCheck(
    'Partial player history returns Partial coordinator status.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Partial'
);


dataUpdateCoordinatorCheck(
    'Partial player history result is preserved.',
    (
        $result[
            'steps'
        ][
            'player_fixture_history'
        ]
        ?? null
    )
    ===
    $partialHistoryResult
);


/*
 * ============================================================
 * SCENARIO F
 * PLAYER HISTORY FAILURE
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario F: Player History Failure'
);


$executionOrder =
    [];


$historyFailure = [

    'status' =>
        'Failed',

    'error_message' =>
        'Player fixture history update failed.'
];


$coordinator =
    new DataUpdateCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'bootstrap';

            return [
                'status' =>
                    'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'fixtures';

            return [
                'status' =>
                    'Success'
            ];
        },

        static function () use (
            &$executionOrder,
            $historyFailure
        ): array {

            $executionOrder[] =
                'player_fixture_history';

            return $historyFailure;
        }
    );


$result =
    $coordinator
        ->run();


dataUpdateCoordinatorCheck(
    'Player history failure occurs after earlier successful steps.',
    $executionOrder
    ===
    [
        'bootstrap',
        'fixtures',
        'player_fixture_history'
    ]
);


dataUpdateCoordinatorCheck(
    'Player history failure returns Failed coordinator status.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataUpdateCoordinatorCheck(
    'Player history failure result is preserved.',
    (
        $result[
            'steps'
        ][
            'player_fixture_history'
        ]
        ?? null
    )
    ===
    $historyFailure
);


/*
 * ============================================================
 * SCENARIO G
 * INVALID BOOTSTRAP STATUS
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario G: Invalid Bootstrap Status'
);


$executionOrder =
    [];


$coordinator =
    new DataUpdateCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'bootstrap';

            return [
                'status' =>
                    'Unexpected'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'fixtures';

            return [
                'status' =>
                    'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_fixture_history';

            return [
                'status' =>
                    'Success'
            ];
        }
    );


$result =
    $coordinator
        ->run();


dataUpdateCoordinatorCheck(
    'Invalid bootstrap status stops the pipeline.',
    $executionOrder
    ===
    [
        'bootstrap'
    ]
);


dataUpdateCoordinatorCheck(
    'Invalid bootstrap status returns Failed coordinator status.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


/*
 * ============================================================
 * SCENARIO H
 * MISSING FIXTURE STATUS
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario H: Missing Fixture Status'
);


$executionOrder =
    [];


$coordinator =
    new DataUpdateCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'bootstrap';

            return [
                'status' =>
                    'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'fixtures';

            return [];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_fixture_history';

            return [
                'status' =>
                    'Success'
            ];
        }
    );


$result =
    $coordinator
        ->run();


dataUpdateCoordinatorCheck(
    'Missing fixture status stops the pipeline.',
    $executionOrder
    ===
    [
        'bootstrap',
        'fixtures'
    ]
);


dataUpdateCoordinatorCheck(
    'Missing fixture status returns Failed coordinator status.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


/*
 * ============================================================
 * SCENARIO I
 * INVALID PLAYER HISTORY STATUS
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario I: Invalid Player History Status'
);


$coordinator =
    new DataUpdateCoordinator(

        static function (): array {

            return [
                'status' =>
                    'Success'
            ];
        },

        static function (): array {

            return [
                'status' =>
                    'Success'
            ];
        },

        static function (): array {

            return [
                'status' =>
                    'Unexpected'
            ];
        }
    );


$result =
    $coordinator
        ->run();


dataUpdateCoordinatorCheck(
    'Invalid player history status returns Failed coordinator status.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


/*
 * ============================================================
 * SCENARIO J
 * BOOTSTRAP RUNNER THROWS
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario J: Bootstrap Runner Throws'
);


$executionOrder =
    [];


$coordinator =
    new DataUpdateCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'bootstrap';

            throw new RuntimeException(
                'Synthetic bootstrap failure.'
            );
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'fixtures';

            return [
                'status' =>
                    'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_fixture_history';

            return [
                'status' =>
                    'Success'
            ];
        }
    );


$result =
    $coordinator
        ->run();


dataUpdateCoordinatorCheck(
    'Bootstrap exception stops the pipeline.',
    $executionOrder
    ===
    [
        'bootstrap'
    ]
);


dataUpdateCoordinatorCheck(
    'Bootstrap exception returns Failed coordinator status.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataUpdateCoordinatorCheck(
    'Bootstrap exception message is preserved.',
    (
        $result[
            'steps'
        ][
            'bootstrap'
        ][
            'error_message'
        ]
        ?? null
    )
    ===
    'Synthetic bootstrap failure.'
);


/*
 * ============================================================
 * SCENARIO K
 * FIXTURE RUNNER THROWS
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario K: Fixture Runner Throws'
);


$executionOrder =
    [];


$coordinator =
    new DataUpdateCoordinator(

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'bootstrap';

            return [
                'status' =>
                    'Success'
            ];
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'fixtures';

            throw new RuntimeException(
                'Synthetic fixture failure.'
            );
        },

        static function () use (
            &$executionOrder
        ): array {

            $executionOrder[] =
                'player_fixture_history';

            return [
                'status' =>
                    'Success'
            ];
        }
    );


$result =
    $coordinator
        ->run();


dataUpdateCoordinatorCheck(
    'Fixture exception stops the pipeline.',
    $executionOrder
    ===
    [
        'bootstrap',
        'fixtures'
    ]
);


dataUpdateCoordinatorCheck(
    'Fixture exception returns Failed coordinator status.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataUpdateCoordinatorCheck(
    'Fixture exception message is preserved.',
    (
        $result[
            'steps'
        ][
            'fixtures'
        ][
            'error_message'
        ]
        ?? null
    )
    ===
    'Synthetic fixture failure.'
);


/*
 * ============================================================
 * SCENARIO L
 * PLAYER HISTORY RUNNER THROWS
 * ============================================================
 */

dataUpdateCoordinatorSection(
    'Scenario L: Player History Runner Throws'
);


$coordinator =
    new DataUpdateCoordinator(

        static function (): array {

            return [
                'status' =>
                    'Success'
            ];
        },

        static function (): array {

            return [
                'status' =>
                    'Success'
            ];
        },

        static function (): array {

            throw new RuntimeException(
                'Synthetic player history failure.'
            );
        }
    );


$result =
    $coordinator
        ->run();


dataUpdateCoordinatorCheck(
    'Player history exception returns Failed coordinator status.',
    (
        $result[
            'status'
        ]
        ?? null
    )
    ===
    'Failed'
);


dataUpdateCoordinatorCheck(
    'Player history exception message is preserved.',
    (
        $result[
            'steps'
        ][
            'player_fixture_history'
        ][
            'error_message'
        ]
        ?? null
    )
    ===
    'Synthetic player history failure.'
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

dataUpdateCoordinatorSummary();