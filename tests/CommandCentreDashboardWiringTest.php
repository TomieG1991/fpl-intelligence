<?php

echo "============================================<br>";
echo "Command Centre Dashboard Wiring Test<br>";
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

function commandCentreDashboardCheck(
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
 * A. SHARED MANAGER CONTEXT
 * ============================================================
 */

echo "============================================<br>";
echo "A. Shared Manager Context<br>";
echo "============================================<br>";


commandCentreDashboardCheck(
    'Dashboard loads the shared manager-context boundary',
    strpos(
        $dashboardSource,
        '/includes/manager-context.php'
    ) !== false
);


commandCentreDashboardCheck(
    'Dashboard consumes the resolved active manager entry',
    strpos(
        $dashboardSource,
        '$activeManagerEntryId'
    ) !== false
);


commandCentreDashboardCheck(
    'Dashboard does not independently validate entry_id with filter_input',
    !preg_match(
        '/filter_input\s*\(\s*INPUT_GET\s*,\s*[\'"]entry_id[\'"]\s*,\s*FILTER_VALIDATE_INT/s',
        $dashboardSource
    )
);


commandCentreDashboardCheck(
    'Dashboard does not duplicate ManagerContextService resolution',
    strpos(
        $dashboardSource,
        'new ManagerContextService'
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * B. COMMAND CENTRE ORCHESTRATION
 * ============================================================
 */

echo "============================================<br>";
echo "B. Command Centre Orchestration<br>";
echo "============================================<br>";


commandCentreDashboardCheck(
    'Dashboard uses CommandCentreService',
    strpos(
        $dashboardSource,
        'CommandCentreService'
    ) !== false
);


commandCentreDashboardCheck(
    'Dashboard constructs the Command Centre orchestration boundary',
    strpos(
        $dashboardSource,
        'new CommandCentreService'
    ) !== false
);


commandCentreDashboardCheck(
    'Dashboard builds the Command Centre result',
    preg_match(
        '/\$commandCentre\s*=\s*\$commandCentreService\s*->\s*build\s*\(/s',
        $dashboardSource
    )
    === 1
);


echo "<br>";


/*
 * ============================================================
 * C. AUTHORITATIVE INPUT BOUNDARIES
 * ============================================================
 */

echo "============================================<br>";
echo "C. Authoritative Input Boundaries<br>";
echo "============================================<br>";


commandCentreDashboardCheck(
    'Dashboard uses FPLSquadImporter for real manager squad context',
    strpos(
        $dashboardSource,
        'FPLSquadImporter'
    ) !== false
);


commandCentreDashboardCheck(
    'Dashboard uses SquadHorizonIntelligenceService',
    strpos(
        $dashboardSource,
        'SquadHorizonIntelligenceService'
    ) !== false
);


commandCentreDashboardCheck(
    'Dashboard does not run heavy chip recommendation orchestration on initial load',
    strpos(
        $dashboardSource,
        'ChipRecommendationProductionOrchestrator'
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * D. STABLE COMMAND CENTRE RESULT
 * ============================================================
 */

echo "============================================<br>";
echo "D. Stable Command Centre Result<br>";
echo "============================================<br>";


commandCentreDashboardCheck(
    'Dashboard initialises a stable Command Centre result',
    strpos(
        $dashboardSource,
        '$commandCentre'
    ) !== false
);


commandCentreDashboardCheck(
    'Dashboard consumes Command Centre manager context',
    preg_match(
        '/\$commandCentre\s*\[\s*[\'"]manager[\'"]\s*\]/s',
        $dashboardSource
    )
    === 1
);


commandCentreDashboardCheck(
    'Dashboard consumes Command Centre gameweek context',
    preg_match(
        '/\$commandCentre\s*\[\s*[\'"]gameweek[\'"]\s*\]/s',
        $dashboardSource
    )
    === 1
);


commandCentreDashboardCheck(
    'Dashboard consumes Command Centre decision evidence',
    preg_match(
        '/\$commandCentre\s*\[\s*[\'"]decision[\'"]\s*\]/s',
        $dashboardSource
    )
    === 1
);


echo "<br>";


/*
 * ============================================================
 * E. NO DUPLICATE GAMEWEEK DECISION CALCULATION
 * ============================================================
 */

echo "============================================<br>";
echo "E. No Duplicate Gameweek Decision Calculation<br>";
echo "============================================<br>";


commandCentreDashboardCheck(
    'Dashboard does not directly call getGameweekDecision',
    strpos(
        $dashboardSource,
        '->getGameweekDecision('
    ) === false
);


commandCentreDashboardCheck(
    'Dashboard does not directly call getGameweekStartingXI',
    strpos(
        $dashboardSource,
        '->getGameweekStartingXI('
    ) === false
);


commandCentreDashboardCheck(
    'Dashboard does not directly call getCaptainRecommendations',
    strpos(
        $dashboardSource,
        '->getCaptainRecommendations('
    ) === false
);


commandCentreDashboardCheck(
    'Dashboard does not directly call getSquadTransferRecommendations',
    strpos(
        $dashboardSource,
        '->getSquadTransferRecommendations('
    ) === false
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