<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Manager Context Service Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * ASSERTION HELPER
 * ============================================================
 */

function managerContextTest(
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


/*
 * ============================================================
 * A. SERVICE CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "A. Service Contract<br>";
echo "============================================<br>";


managerContextTest(
    'ManagerContextService exists',
    class_exists(
        'ManagerContextService'
    )
);


managerContextTest(
    'ManagerContextService exposes resolveEntryId()',
    class_exists(
        'ManagerContextService'
    )
    &&
    method_exists(
        'ManagerContextService',
        'resolveEntryId'
    )
);


managerContextTest(
    'ManagerContextService exposes buildUrl()',
    class_exists(
        'ManagerContextService'
    )
    &&
    method_exists(
        'ManagerContextService',
        'buildUrl'
    )
);


echo "<br>";


/*
 * ============================================================
 * STOP UNTIL PRODUCTION CONTRACT EXISTS
 * ============================================================
 *
 * The remaining tests deliberately depend on the service
 * contract above.
 *
 * During the initial RED stage the class does not yet exist,
 * so stop here rather than causing a PHP fatal error.
 */

if (
    !class_exists(
        'ManagerContextService'
    )
) {

    echo "============================================<br>";
    echo "Summary<br>";
    echo "============================================<br>";

    echo "Assertions passed: {$passed}<br>";
    echo "Assertions failed: {$failed}<br><br>";

    echo "RESULT: TESTS FAILED ❌<br>";

    return;
}


/*
 * ============================================================
 * B. EXPLICIT ENTRY ID
 * ============================================================
 */

echo "============================================<br>";
echo "B. Explicit Entry ID<br>";
echo "============================================<br>";


$session =
    [];


$service =
    new ManagerContextService();


$entryId =
    $service
        ->resolveEntryId(
            2702264,
            $session
        );


managerContextTest(
    'A valid explicit entry ID becomes the active manager entry',
    $entryId === 2702264
);


managerContextTest(
    'A valid explicit entry ID is preserved for later requests',
    (
        $session[
            'fpl_entry_id'
        ]
        ?? null
    )
    === 2702264
);


echo "<br>";


/*
 * ============================================================
 * C. PRESERVED ENTRY ID
 * ============================================================
 */

echo "============================================<br>";
echo "C. Preserved Entry ID<br>";
echo "============================================<br>";


$session = [

    'fpl_entry_id' =>
        2702264
];


$entryId =
    $service
        ->resolveEntryId(
            null,
            $session
        );


managerContextTest(
    'The preserved manager entry is reused when no explicit entry ID is supplied',
    $entryId === 2702264
);


managerContextTest(
    'Reusing preserved context does not alter the stored entry ID',
    (
        $session[
            'fpl_entry_id'
        ]
        ?? null
    )
    === 2702264
);


echo "<br>";


/*
 * ============================================================
 * D. EXPLICIT ENTRY REPLACEMENT
 * ============================================================
 */

echo "============================================<br>";
echo "D. Explicit Entry Replacement<br>";
echo "============================================<br>";


$session = [

    'fpl_entry_id' =>
        1111111
];


$entryId =
    $service
        ->resolveEntryId(
            2702264,
            $session
        );


managerContextTest(
    'A new valid explicit entry ID replaces the previously preserved manager entry',
    $entryId === 2702264
);


managerContextTest(
    'The replacement entry ID becomes the preserved manager context',
    (
        $session[
            'fpl_entry_id'
        ]
        ?? null
    )
    === 2702264
);


echo "<br>";


/*
 * ============================================================
 * E. INVALID EXPLICIT ENTRY
 * ============================================================
 */

echo "============================================<br>";
echo "E. Invalid Explicit Entry<br>";
echo "============================================<br>";


$session = [

    'fpl_entry_id' =>
        2702264
];


$entryId =
    $service
        ->resolveEntryId(
            0,
            $session
        );


managerContextTest(
    'An invalid explicit entry ID does not replace valid preserved context',
    $entryId === 2702264
);


managerContextTest(
    'Invalid explicit input leaves the preserved manager entry unchanged',
    (
        $session[
            'fpl_entry_id'
        ]
        ?? null
    )
    === 2702264
);


echo "<br>";


/*
 * ============================================================
 * F. NO MANAGER CONTEXT
 * ============================================================
 */

echo "============================================<br>";
echo "F. No Manager Context<br>";
echo "============================================<br>";


$session =
    [];


$entryId =
    $service
        ->resolveEntryId(
            null,
            $session
        );


managerContextTest(
    'No entry ID is returned when neither request nor preserved context provides one',
    $entryId === null
);


managerContextTest(
    'No synthetic manager entry is stored when context is unavailable',
    !isset(
        $session[
            'fpl_entry_id'
        ]
    )
);


echo "<br>";


/*
 * ============================================================
 * G. CONTEXT-AWARE URL
 * ============================================================
 */

echo "============================================<br>";
echo "G. Context-Aware URL<br>";
echo "============================================<br>";


$url =
    $service
        ->buildUrl(
            'gameweek.php',
            2702264
        );


managerContextTest(
    'A manager-aware page URL carries the active entry ID',
    $url ===
        'gameweek.php?entry_id=2702264'
);


$url =
    $service
        ->buildUrl(
            'squad.php',
            null
        );


managerContextTest(
    'A page URL remains clean when no manager context exists',
    $url ===
        'squad.php'
);


$url =
    $service
        ->buildUrl(
            'chips.php?preview=1',
            2702264
        );


managerContextTest(
    'Manager context is appended correctly when a URL already contains a query string',
    $url ===
        'chips.php?preview=1&entry_id=2702264'
);


echo "<br>";


/*
 * ============================================================
 * H. EXISTING ENTRY QUERY
 * ============================================================
 */

echo "============================================<br>";
echo "H. Existing Entry Query<br>";
echo "============================================<br>";


$url =
    $service
        ->buildUrl(
            'gameweek.php?entry_id=1234567',
            2702264
        );


managerContextTest(
    'An existing entry_id query parameter is replaced rather than duplicated',
    $url ===
        'gameweek.php?entry_id=2702264'
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Summary<br>";
echo "============================================<br>";


echo "Assertions passed: {$passed}<br>";
echo "Assertions failed: {$failed}<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}