<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Fixture History Update Options Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


function playerFixtureHistoryOptionsCheck(
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


function playerFixtureHistoryOptionsSection(
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


function playerFixtureHistoryOptionsSummary(): void
{

    global $passed;
    global $failed;


    echo "<br>";
    echo "============================================<br>";
    echo "Player Fixture History Update Options Test Summary<br>";
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

playerFixtureHistoryOptionsSection(
    'Scenario A: Class Contract'
);


playerFixtureHistoryOptionsCheck(
    'PlayerFixtureHistoryUpdateOptions exists.',
    class_exists(
        'PlayerFixtureHistoryUpdateOptions'
    )
);


/*
 * ============================================================
 * SCENARIO B
 * DEFAULT OPTIONS
 * ============================================================
 */

playerFixtureHistoryOptionsSection(
    'Scenario B: Default Options'
);


$options =
    PlayerFixtureHistoryUpdateOptions::resolve(
        [],
        []
    );


playerFixtureHistoryOptionsCheck(
    'Default mode is not full import.',
    (
        $options[
            'full'
        ]
        ?? null
    )
    ===
    false
);


playerFixtureHistoryOptionsCheck(
    'Default limit is 25.',
    (
        $options[
            'limit'
        ]
        ?? null
    )
    ===
    25
);


playerFixtureHistoryOptionsCheck(
    'Default offset is 0.',
    (
        $options[
            'offset'
        ]
        ?? null
    )
    ===
    0
);


/*
 * ============================================================
 * SCENARIO C
 * BROWSER FULL MODE
 * ============================================================
 */

playerFixtureHistoryOptionsSection(
    'Scenario C: Browser Full Mode'
);


$options =
    PlayerFixtureHistoryUpdateOptions::resolve(
        [
            'full' =>
                '1'
        ],
        []
    );


playerFixtureHistoryOptionsCheck(
    'Browser full=1 enables full import.',
    (
        $options[
            'full'
        ]
        ?? null
    )
    ===
    true
);


/*
 * ============================================================
 * SCENARIO D
 * CLI FULL MODE
 * ============================================================
 */

playerFixtureHistoryOptionsSection(
    'Scenario D: CLI Full Mode'
);


$options =
    PlayerFixtureHistoryUpdateOptions::resolve(
        [],
        [
            'updatePlayerFixtureHistory.php',
            '--full'
        ]
    );


playerFixtureHistoryOptionsCheck(
    'CLI --full enables full import.',
    (
        $options[
            'full'
        ]
        ?? null
    )
    ===
    true
);


/*
 * ============================================================
 * SCENARIO E
 * EXPLICIT BROWSER LIMIT AND OFFSET
 * ============================================================
 */

playerFixtureHistoryOptionsSection(
    'Scenario E: Explicit Browser Limit And Offset'
);


$options =
    PlayerFixtureHistoryUpdateOptions::resolve(
        [
            'limit' =>
                '40',

            'offset' =>
                '75'
        ],
        []
    );


playerFixtureHistoryOptionsCheck(
    'Browser limit is preserved.',
    (
        $options[
            'limit'
        ]
        ?? null
    )
    ===
    40
);


playerFixtureHistoryOptionsCheck(
    'Browser offset is preserved.',
    (
        $options[
            'offset'
        ]
        ?? null
    )
    ===
    75
);


/*
 * ============================================================
 * SCENARIO F
 * LIMIT CAP
 * ============================================================
 */

playerFixtureHistoryOptionsSection(
    'Scenario F: Limit Cap'
);


$options =
    PlayerFixtureHistoryUpdateOptions::resolve(
        [
            'limit' =>
                '250'
        ],
        []
    );


playerFixtureHistoryOptionsCheck(
    'Limit is capped at 100.',
    (
        $options[
            'limit'
        ]
        ?? null
    )
    ===
    100
);


/*
 * ============================================================
 * SCENARIO G
 * LIMIT MINIMUM
 * ============================================================
 */

playerFixtureHistoryOptionsSection(
    'Scenario G: Limit Minimum'
);


$options =
    PlayerFixtureHistoryUpdateOptions::resolve(
        [
            'limit' =>
                '0'
        ],
        []
    );


playerFixtureHistoryOptionsCheck(
    'Limit cannot fall below 1.',
    (
        $options[
            'limit'
        ]
        ?? null
    )
    ===
    1
);


/*
 * ============================================================
 * SCENARIO H
 * OFFSET MINIMUM
 * ============================================================
 */

playerFixtureHistoryOptionsSection(
    'Scenario H: Offset Minimum'
);


$options =
    PlayerFixtureHistoryUpdateOptions::resolve(
        [
            'offset' =>
                '-50'
        ],
        []
    );


playerFixtureHistoryOptionsCheck(
    'Offset cannot fall below 0.',
    (
        $options[
            'offset'
        ]
        ?? null
    )
    ===
    0
);


/*
 * ============================================================
 * SCENARIO I
 * CLI LIMIT AND OFFSET
 * ============================================================
 */

playerFixtureHistoryOptionsSection(
    'Scenario I: CLI Limit And Offset'
);


$options =
    PlayerFixtureHistoryUpdateOptions::resolve(
        [],
        [
            'updatePlayerFixtureHistory.php',
            '--limit=60',
            '--offset=120'
        ]
    );


playerFixtureHistoryOptionsCheck(
    'CLI limit is parsed.',
    (
        $options[
            'limit'
        ]
        ?? null
    )
    ===
    60
);


playerFixtureHistoryOptionsCheck(
    'CLI offset is parsed.',
    (
        $options[
            'offset'
        ]
        ?? null
    )
    ===
    120
);


/*
 * ============================================================
 * SCENARIO J
 * FULL MODE PRESERVES NORMAL DEFAULTS
 * ============================================================
 */

playerFixtureHistoryOptionsSection(
    'Scenario J: Full Mode Preserves Normal Defaults'
);


$options =
    PlayerFixtureHistoryUpdateOptions::resolve(
        [],
        [
            'updatePlayerFixtureHistory.php',
            '--full'
        ]
    );


playerFixtureHistoryOptionsCheck(
    'Full mode still returns default limit.',
    (
        $options[
            'limit'
        ]
        ?? null
    )
    ===
    25
);


playerFixtureHistoryOptionsCheck(
    'Full mode still returns default offset.',
    (
        $options[
            'offset'
        ]
        ?? null
    )
    ===
    0
);


/*
 * ============================================================
 * TEST SUMMARY
 * ============================================================
 */

playerFixtureHistoryOptionsSummary();