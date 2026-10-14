<?php

echo "============================================<br>";
echo "Command Centre Dashboard Presentation Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function commandCentrePresentationCheck(
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
 * DASHBOARD SOURCE
 * ============================================================
 */

$dashboardPath =
    __DIR__
    . '/../public/index.php';


$dashboardSource =
    is_file(
        $dashboardPath
    )
        ? file_get_contents(
            $dashboardPath
        )
        : false;


$dashboardSource =
    is_string(
        $dashboardSource
    )
        ? $dashboardSource
        : '';


/*
 * ============================================================
 * A. COMMAND CENTRE LANDMARK
 * ============================================================
 */

echo "============================================<br>";
echo "A. Command Centre Landmark<br>";
echo "============================================<br>";


commandCentrePresentationCheck(
    'Dashboard presents the Gameweek Command Centre',
    strpos(
        $dashboardSource,
        'Gameweek Command Centre'
    ) !== false
);


commandCentrePresentationCheck(
    'Command Centre is exposed as a labelled section',
    strpos(
        $dashboardSource,
        'aria-labelledby="command-centre-title"'
    ) !== false
);


commandCentrePresentationCheck(
    'Command Centre exposes its title through command-centre-title',
    strpos(
        $dashboardSource,
        'id="command-centre-title"'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * B. MANAGER CONTEXT PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "B. Manager Context Presentation<br>";
echo "============================================<br>";


commandCentrePresentationCheck(
    'Dashboard presents a Manager Overview',
    strpos(
        $dashboardSource,
        'Manager Overview'
    ) !== false
);


commandCentrePresentationCheck(
    'Manager presentation consumes Command Centre manager evidence',
    strpos(
        $dashboardSource,
        '$commandCentreManager'
    ) !== false
);


commandCentrePresentationCheck(
    'Dashboard exposes a session-backed manager entry form',
    preg_match(
        '/<form[^>]*method="post"[^>]*>/s',
        $dashboardSource
    )
    === 1
);


commandCentrePresentationCheck(
    'Manager entry form uses the shared entry_id parameter',
    preg_match(
        '/name="entry_id"/s',
        $dashboardSource
    )
    === 1
);


commandCentrePresentationCheck(
    'Manager entry field accepts positive integer entry IDs',
    preg_match(
        '/name="entry_id"[^>]*min="1"/s',
        $dashboardSource
    )
    === 1
);


echo "<br>";


/*
 * ============================================================
 * C. GAMEWEEK OVERVIEW
 * ============================================================
 */

echo "============================================<br>";
echo "C. Gameweek Overview<br>";
echo "============================================<br>";


commandCentrePresentationCheck(
    'Dashboard presents a Gameweek Overview',
    strpos(
        $dashboardSource,
        'Gameweek Overview'
    ) !== false
);


commandCentrePresentationCheck(
    'Gameweek presentation consumes Command Centre gameweek evidence',
    strpos(
        $dashboardSource,
        '$commandCentreGameweek'
    ) !== false
);


commandCentrePresentationCheck(
    'Dashboard presents the actionable gameweek when available',
    strpos(
        $dashboardSource,
        'target_gameweek'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * D. PRIMARY GAMEWEEK DECISION
 * ============================================================
 */

echo "============================================<br>";
echo "D. Primary Gameweek Decision<br>";
echo "============================================<br>";


commandCentrePresentationCheck(
    'Dashboard presents the Primary Gameweek Decision',
    strpos(
        $dashboardSource,
        'Primary Gameweek Decision'
    ) !== false
);


commandCentrePresentationCheck(
    'Primary decision consumes Command Centre decision evidence',
    strpos(
        $dashboardSource,
        '$commandCentreDecision'
    ) !== false
);


commandCentrePresentationCheck(
    'Dashboard consumes the authoritative overall action',
    strpos(
        $dashboardSource,
        'overall_action'
    ) !== false
);


commandCentrePresentationCheck(
    'Dashboard consumes authoritative transfer advice',
    strpos(
        $dashboardSource,
        'transfer_advice'
    ) !== false
);


commandCentrePresentationCheck(
    'Dashboard consumes authoritative squad risks',
    strpos(
        $dashboardSource,
        'squad_risks'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * E. EMPTY AND UNAVAILABLE STATES
 * ============================================================
 */

echo "============================================<br>";
echo "E. Empty and Unavailable States<br>";
echo "============================================<br>";


commandCentrePresentationCheck(
    'Dashboard distinguishes the no-manager state',
    strpos(
        $dashboardSource,
        '$activeManagerEntryId === null'
    ) !== false
);


commandCentrePresentationCheck(
    'No-manager state explains how to start the Command Centre',
    strpos(
        $dashboardSource,
        'Enter your FPL entry ID'
    ) !== false
);


commandCentrePresentationCheck(
    'Dashboard provides a Command Centre unavailable state',
    strpos(
        $dashboardSource,
        'Command Centre unavailable'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * F. SPECIALIST DRILL-DOWN
 * ============================================================
 */

echo "============================================<br>";
echo "F. Specialist Drill-down<br>";
echo "============================================<br>";


commandCentrePresentationCheck(
    'Primary decision links to Gameweek Intelligence',
    strpos(
        $dashboardSource,
        'View Gameweek Intelligence'
    ) !== false
);


commandCentrePresentationCheck(
    'Gameweek drill-down uses the clean session-backed URL',
    strpos(
        $dashboardSource,
        "'gameweek.php'"
    ) !== false
    &&
    strpos(
        $dashboardSource,
        '$managerContextService->buildUrl('
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * G. ACCESSIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "G. Accessibility<br>";
echo "============================================<br>";


commandCentrePresentationCheck(
    'Manager entry field has an explicit label',
    preg_match(
        '/<label[^>]*for="command-centre-entry-id"[^>]*>/s',
        $dashboardSource
    )
    === 1
);


commandCentrePresentationCheck(
    'Manager entry field uses the labelled input ID',
    preg_match(
        '/<input[^>]*id="command-centre-entry-id"[^>]*>/s',
        $dashboardSource
    )
    === 1
);


commandCentrePresentationCheck(
    'Command Centre unavailable message exposes status semantics',
    strpos(
        $dashboardSource,
        'role="status"'
    ) !== false
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


echo "Assertions passed: "
    . $passed
    . "<br>";


echo "Assertions failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}