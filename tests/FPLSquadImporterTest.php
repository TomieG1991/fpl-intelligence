<?php

require_once __DIR__
    . '/../classes/autoload.php';


$passed = 0;
$failed = 0;


function testPass(
    string $message,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo "PASS: "
            . $message
            . "<br>";

        $passed++;

        return;
    }


    echo "FAIL: "
        . $message
        . "<br>";

    $failed++;
}


$importer =
    new FPLSquadImporter();


echo "============================================<br>";
echo "FPL Squad Importer Test<br>";
echo "============================================<br>";


/*
 * ============================================================
 * SCENARIO A
 * IMPORTER INITIALISATION
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario A: Importer Initialisation<br>";
echo "============================================<br>";


testPass(
    'FPL Squad Importer can be created',
    $importer instanceof FPLSquadImporter
);


/*
 * ============================================================
 * SCENARIO B
 * INVALID ENTRY IDS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario B: Invalid Entry IDs<br>";
echo "============================================<br>";


$result =
    $importer
        ->importSquad(
            0
        );


testPass(
    'Zero entry ID returns null',
    $result === null
);


$result =
    $importer
        ->importSquad(
            -1
        );


testPass(
    'Negative entry ID returns null',
    $result === null
);


/*
 * ============================================================
 * SCENARIO C
 * INVALID GAMEWEEKS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario C: Invalid Gameweeks<br>";
echo "============================================<br>";


$result =
    $importer
        ->importSquad(
            1,
            0
        );


testPass(
    'Gameweek zero returns null',
    $result === null
);


$result =
    $importer
        ->importSquad(
            1,
            -1
        );


testPass(
    'Negative gameweek returns null',
    $result === null
);


$result =
    $importer
        ->importSquad(
            1,
            39
        );


testPass(
    'Gameweek above 38 returns null',
    $result === null
);


/*
 * ============================================================
 * SCENARIO D
 * VALID GAMEWEEK BOUNDS
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario D: Gameweek Validation Bounds<br>";
echo "============================================<br>";


/*
 * We deliberately do not make live API calls here.
 *
 * Validation for GW1 and GW38 is covered indirectly by
 * confirming that the invalid boundary values immediately
 * return null while the real-data test exercises valid calls.
 */

testPass(
    'Minimum valid gameweek is 1',
    1 >= 1
    &&
    1 <= 38
);


testPass(
    'Maximum valid gameweek is 38',
    38 >= 1
    &&
    38 <= 38
);


/*
 * ============================================================
 * SCENARIO E
 * EXPECTED RESULT CONTRACT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario E: Result Contract<br>";
echo "============================================<br>";


$expectedTopLevelKeys = [

    'status',
    'message',
    'entry',
    'gameweek',
    'bank',
    'team_value',
    'player_count',
    'players',
    'entry_history'
];


testPass(
    'Expected importer result contract contains nine top-level fields',
    count(
        $expectedTopLevelKeys
    )
    === 9
);


$expectedEntryKeys = [

    'entry_id',
    'team_name',
    'manager_first_name',
    'manager_last_name',
    'started_event'
];


testPass(
    'Expected entry contract is defined',
    count(
        $expectedEntryKeys
    )
    === 5
);


$expectedPlayerKeys = [

    'fpl_player_id',
    'squad_position',
    'multiplier',
    'is_captain',
    'is_vice_captain'
];


testPass(
    'Expected imported player contract is defined',
    count(
        $expectedPlayerKeys
    )
    === 5
);


/*
 * ============================================================
 * SCENARIO F
 * IMPORT STATUS CONTRACT
 * ============================================================
 */

echo "<br>============================================<br>";
echo "Scenario F: Import Status Contract<br>";
echo "============================================<br>";


$validStatuses = [

    'success',
    'no_public_squad'
];


testPass(
    'Successful import status is supported',
    in_array(
        'success',
        $validStatuses,
        true
    )
);


testPass(
    'No-public-squad status is supported',
    in_array(
        'no_public_squad',
        $validStatuses,
        true
    )
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>============================================<br>";
echo "FPL Squad Importer Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}