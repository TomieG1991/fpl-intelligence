<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Actionable Gameweek Resolver Test<br>";
echo "v0.37.0 — Actionable Gameweek Resolution<br>";
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

function actionableGameweekCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo
            'PASS: '
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . '<br>';

        $passed++;

        return;
    }


    echo
        'FAIL: '
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . '<br>';

    $failed++;
}


function actionableGameweekHeading(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "============================================<br>";
}


/*
 * ============================================================
 * SCENARIO A: CLASS CONTRACT
 * ============================================================
 */

actionableGameweekHeading(
    'Scenario A: Resolver Availability'
);


$classExists =
    class_exists(
        'ActionableGameweekResolver'
    );


actionableGameweekCheck(
    'ActionableGameweekResolver class exists',
    $classExists
);


/*
 * ============================================================
 * EXPECTED INITIAL RED
 * ============================================================
 *
 * The production resolver intentionally does not exist yet.
 *
 * Stop here rather than attempting to instantiate a missing
 * class and causing a fatal PHP error.
 */

if (
    !$classExists
) {

    echo "<br>";
    echo "============================================<br>";
    echo "TEST SUMMARY<br>";
    echo "============================================<br>";

    echo
        'Passed: '
        . $passed
        . '<br>';

    echo
        'Failed: '
        . $failed
        . '<br><br>';

    echo
        'RESULT: TESTS FAILED ❌';

    exit;
}


/*
 * ============================================================
 * TEST DOUBLE — GAMEWEEK REPOSITORY
 * ============================================================
 *
 * The resolver is allowed to use only the existing
 * getNextDeadlineAfter() repository contract.
 */

class ActionableGameweekRepositoryStub
{
    public int
        $lookupCalls =
            0;


    public ?string
        $lastTimestamp =
            null;


    private ?array
        $result;


    public function __construct(
        ?array $result
    ) {

        $this->result =
            $result;
    }


    public function getNextDeadlineAfter(
        string $timestamp
    ): ?array {

        $this->lookupCalls++;


        $this->lastTimestamp =
            $timestamp;


        return
            $this->result;
    }
}


/*
 * ============================================================
 * SCENARIO B: NEXT DEADLINE RESOLVES FPL GAMEWEEK
 * ============================================================
 */

actionableGameweekHeading(
    'Scenario B: Next Deadline Resolves FPL Gameweek'
);


$generatedAt =
    '2026-09-15 16:00:00';


$repository =
    new ActionableGameweekRepositoryStub(
        [

            'id' =>
                9,

            'fpl_gameweek_id' =>
                5,

            'name' =>
                'Gameweek 5',

            'deadline_time' =>
                '2026-09-18 17:30:00',

            'is_current' =>
                0,

            'is_next' =>
                1
        ]
    );


$resolver =
    new ActionableGameweekResolver(
        $repository
    );


$result =
    $resolver->resolve(
        $generatedAt
    );


actionableGameweekCheck(
    'Repository deadline lookup is performed exactly once',
    $repository->lookupCalls
    ===
    1
);


actionableGameweekCheck(
    'Supplied timestamp is passed unchanged to repository',
    $repository->lastTimestamp
    ===
    $generatedAt
);


actionableGameweekCheck(
    'Resolver returns FPL gameweek ID rather than local database ID',
    $result
    ===
    5
);


/*
 * ============================================================
 * SCENARIO C: NO FUTURE DEADLINE
 * ============================================================
 */

actionableGameweekHeading(
    'Scenario C: No Future Deadline'
);


$noFutureRepository =
    new ActionableGameweekRepositoryStub(
        null
    );


$noFutureResolver =
    new ActionableGameweekResolver(
        $noFutureRepository
    );


$noFutureResult =
    $noFutureResolver->resolve(
        $generatedAt
    );


actionableGameweekCheck(
    'No future deadline returns null',
    $noFutureResult
    ===
    null
);


actionableGameweekCheck(
    'No future deadline still performs one repository lookup',
    $noFutureRepository->lookupCalls
    ===
    1
);


/*
 * ============================================================
 * SCENARIO D: INVALID FPL GAMEWEEK ID
 * ============================================================
 */

actionableGameweekHeading(
    'Scenario D: Invalid FPL Gameweek Identity'
);


$invalidGameweekRepository =
    new ActionableGameweekRepositoryStub(
        [

            'id' =>
                9,

            'fpl_gameweek_id' =>
                0,

            'deadline_time' =>
                '2026-09-18 17:30:00'
        ]
    );


$invalidGameweekResolver =
    new ActionableGameweekResolver(
        $invalidGameweekRepository
    );


$invalidGameweekRejected =
    false;


try {

    $invalidGameweekResolver->resolve(
        $generatedAt
    );

} catch (
    RuntimeException $exception
) {

    $invalidGameweekRejected =
        true;
}


actionableGameweekCheck(
    'Invalid FPL gameweek ID is rejected',
    $invalidGameweekRejected
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
        'RESULT: ALL TESTS PASSED ✅';

} else {

    echo
        'RESULT: TESTS FAILED ❌';
}