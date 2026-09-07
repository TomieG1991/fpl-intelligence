<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Gameweek Outcome Availability Test<br>";
echo "============================================<br>";


$passed = 0;
$failed = 0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function outcomeAvailabilityTestResult(
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

outcomeAvailabilityTestResult(
    class_exists(
        'PlayerGameweekOutcomeAvailability'
    ),
    'PlayerGameweekOutcomeAvailability exists.'
);


if (
    !class_exists(
        'PlayerGameweekOutcomeAvailability'
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
 * SETUP
 * ============================================================
 */

$availability =
    new PlayerGameweekOutcomeAvailability();


outcomeAvailabilityTestResult(
    $availability
        instanceof PlayerGameweekOutcomeAvailability,
    'PlayerGameweekOutcomeAvailability can be constructed.'
);


/*
 * ============================================================
 * SCENARIO A
 * GAMEWEEK NOT FINISHED OR DATA CHECKED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario A: Unfinished and Unchecked Gameweek<br>";
echo "============================================<br>";


$result =
    $availability
        ->isAvailable([
            'id' => 5,
            'fpl_gameweek_id' => 1,
            'finished' => false,
            'data_checked' => false
        ]);


outcomeAvailabilityTestResult(
    $result === false,
    'Unfinished and unchecked gameweek is unavailable.'
);


/*
 * ============================================================
 * SCENARIO B
 * FINISHED BUT NOT DATA CHECKED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Finished but Unchecked Gameweek<br>";
echo "============================================<br>";


$result =
    $availability
        ->isAvailable([
            'id' => 6,
            'fpl_gameweek_id' => 2,
            'finished' => true,
            'data_checked' => false
        ]);


outcomeAvailabilityTestResult(
    $result === false,
    'Finished gameweek without data check is unavailable.'
);


/*
 * ============================================================
 * SCENARIO C
 * DATA CHECKED BUT NOT FINISHED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Checked but Unfinished Gameweek<br>";
echo "============================================<br>";


$result =
    $availability
        ->isAvailable([
            'id' => 7,
            'fpl_gameweek_id' => 3,
            'finished' => false,
            'data_checked' => true
        ]);


outcomeAvailabilityTestResult(
    $result === false,
    'Data-checked gameweek that is not finished is unavailable.'
);


/*
 * ============================================================
 * SCENARIO D
 * FINISHED AND DATA CHECKED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Finished and Checked Gameweek<br>";
echo "============================================<br>";


$result =
    $availability
        ->isAvailable([
            'id' => 8,
            'fpl_gameweek_id' => 4,
            'finished' => true,
            'data_checked' => true
        ]);


outcomeAvailabilityTestResult(
    $result === true,
    'Finished and data-checked gameweek is available.'
);


/*
 * ============================================================
 * SCENARIO E
 * DATABASE-STYLE TRUTHY VALUES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario E: Database-Style Truthy Values<br>";
echo "============================================<br>";


$result =
    $availability
        ->isAvailable([
            'id' => 9,
            'fpl_gameweek_id' => 5,
            'finished' => 1,
            'data_checked' => 1
        ]);


outcomeAvailabilityTestResult(
    $result === true,
    'Integer database flags are accepted as available.'
);


$result =
    $availability
        ->isAvailable([
            'id' => 9,
            'fpl_gameweek_id' => 5,
            'finished' => '1',
            'data_checked' => '1'
        ]);


outcomeAvailabilityTestResult(
    $result === true,
    'String database flags are accepted as available.'
);


/*
 * ============================================================
 * SCENARIO F
 * DATABASE-STYLE FALSE VALUES
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario F: Database-Style False Values<br>";
echo "============================================<br>";


$result =
    $availability
        ->isAvailable([
            'id' => 10,
            'fpl_gameweek_id' => 6,
            'finished' => 0,
            'data_checked' => 1
        ]);


outcomeAvailabilityTestResult(
    $result === false,
    'Integer zero finished flag remains unavailable.'
);


$result =
    $availability
        ->isAvailable([
            'id' => 10,
            'fpl_gameweek_id' => 6,
            'finished' => 1,
            'data_checked' => '0'
        ]);


outcomeAvailabilityTestResult(
    $result === false,
    'String zero data-checked flag remains unavailable.'
);


/*
 * ============================================================
 * SCENARIO G
 * MISSING COMPLETION FLAGS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario G: Missing Completion Flags<br>";
echo "============================================<br>";


$result =
    $availability
        ->isAvailable([
            'id' => 11,
            'fpl_gameweek_id' => 7
        ]);


outcomeAvailabilityTestResult(
    $result === false,
    'Gameweek without completion flags is unavailable.'
);


$result =
    $availability
        ->isAvailable([
            'id' => 11,
            'fpl_gameweek_id' => 7,
            'finished' => true
        ]);


outcomeAvailabilityTestResult(
    $result === false,
    'Gameweek without data-checked evidence is unavailable.'
);


$result =
    $availability
        ->isAvailable([
            'id' => 11,
            'fpl_gameweek_id' => 7,
            'data_checked' => true
        ]);


outcomeAvailabilityTestResult(
    $result === false,
    'Gameweek without finished evidence is unavailable.'
);


/*
 * ============================================================
 * SCENARIO H
 * INVALID LOCAL GAMEWEEK IDENTITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario H: Invalid Local Gameweek Identity<br>";
echo "============================================<br>";


foreach (
    [
        0,
        -1
    ]
    as $invalidGameweekId
) {

    $result =
        $availability
            ->isAvailable([
                'id' =>
                    $invalidGameweekId,

                'fpl_gameweek_id' =>
                    8,

                'finished' =>
                    true,

                'data_checked' =>
                    true
            ]);


    outcomeAvailabilityTestResult(
        $result === false,
        'Invalid local gameweek ID '
            . $invalidGameweekId
            . ' is unavailable.'
    );
}


$result =
    $availability
        ->isAvailable([
            'fpl_gameweek_id' => 8,
            'finished' => true,
            'data_checked' => true
        ]);


outcomeAvailabilityTestResult(
    $result === false,
    'Missing local gameweek ID is unavailable.'
);


/*
 * ============================================================
 * SCENARIO I
 * INVALID FPL GAMEWEEK IDENTITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario I: Invalid FPL Gameweek Identity<br>";
echo "============================================<br>";


foreach (
    [
        0,
        -1
    ]
    as $invalidFplGameweekId
) {

    $result =
        $availability
            ->isAvailable([
                'id' =>
                    12,

                'fpl_gameweek_id' =>
                    $invalidFplGameweekId,

                'finished' =>
                    true,

                'data_checked' =>
                    true
            ]);


    outcomeAvailabilityTestResult(
        $result === false,
        'Invalid FPL gameweek ID '
            . $invalidFplGameweekId
            . ' is unavailable.'
    );
}


$result =
    $availability
        ->isAvailable([
            'id' => 12,
            'finished' => true,
            'data_checked' => true
        ]);


outcomeAvailabilityTestResult(
    $result === false,
    'Missing FPL gameweek ID is unavailable.'
);


/*
 * ============================================================
 * SCENARIO J
 * COMPLETION FLAGS ALONE CANNOT CREATE AVAILABILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario J: Identity and Completion Boundary<br>";
echo "============================================<br>";


$result =
    $availability
        ->isAvailable([
            'id' => null,
            'fpl_gameweek_id' => null,
            'finished' => true,
            'data_checked' => true
        ]);


outcomeAvailabilityTestResult(
    $result === false,
    'Completion flags alone cannot make unidentified gameweek evidence available.'
);


/*
 * ============================================================
 * SCENARIO K
 * ADDITIONAL GAMEWEEK DATA DOES NOT CHANGE THE RULE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario K: Additional Gameweek Evidence<br>";
echo "============================================<br>";


$result =
    $availability
        ->isAvailable([
            'id' => 13,
            'fpl_gameweek_id' => 9,
            'name' => 'Gameweek 9',
            'deadline_time' => '2026-10-17 13:30:00',
            'is_previous' => 1,
            'is_current' => 0,
            'is_next' => 0,
            'finished' => 1,
            'data_checked' => 1
        ]);


outcomeAvailabilityTestResult(
    $result === true,
    'Additional gameweek fields do not interfere with availability.'
);


/*
 * ============================================================
 * SCENARIO L
 * NO OUTCOME OR RECOMMENDATION EVIDENCE IS REQUIRED
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario L: Narrow Availability Responsibility<br>";
echo "============================================<br>";


$gameweek = [
    'id' => 14,
    'fpl_gameweek_id' => 10,
    'finished' => true,
    'data_checked' => true
];


$originalGameweek =
    $gameweek;


$result =
    $availability
        ->isAvailable(
            $gameweek
        );


outcomeAvailabilityTestResult(
    $result === true,
    'Availability depends only on valid gameweek completion evidence.'
);


outcomeAvailabilityTestResult(
    $gameweek === $originalGameweek,
    'Availability check does not mutate supplied gameweek evidence.'
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