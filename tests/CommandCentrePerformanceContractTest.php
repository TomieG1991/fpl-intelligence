<?php

echo "============================================<br>";
echo "Command Centre Performance Contract Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentrePerformanceCheck(
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


$dashboardPath =
    __DIR__
    . '/../public/index.php';


$servicePath =
    __DIR__
    . '/../classes/CommandCentreService.php';


$dashboardSource =
    is_file(
        $dashboardPath
    )
        ? file_get_contents(
            $dashboardPath
        )
        : false;


$serviceSource =
    is_file(
        $servicePath
    )
        ? file_get_contents(
            $servicePath
        )
        : false;


$dashboardSource =
    is_string(
        $dashboardSource
    )
        ? $dashboardSource
        : '';


$serviceSource =
    is_string(
        $serviceSource
    )
        ? $serviceSource
        : '';


echo "============================================<br>";
echo "A. Command Centre Execution Boundary<br>";
echo "============================================<br>";


commandCentrePerformanceCheck(
    'Command Centre production build is executed once by the Dashboard',
    substr_count(
        $dashboardSource,
        '$commandCentreService->build('
    )
    === 1
);


commandCentrePerformanceCheck(
    'Command Centre only runs when a manager entry is active',
    preg_match(
        '/if\s*\(\s*'
        . '\$activeManagerEntryId[\s\S]{0,80}?'
        . '!==[\s\S]{0,40}?null[\s\S]*?'
        . '\$commandCentreService\s*->\s*build\s*\(/s',
        $dashboardSource
    )
    === 1
);


commandCentrePerformanceCheck(
    'Dashboard resolves the imported FPL squad once',
    substr_count(
        $dashboardSource,
        '->importSquad('
    )
    === 1
);


commandCentrePerformanceCheck(
    'Dashboard maps the imported squad once',
    substr_count(
        $dashboardSource,
        '->buildSquadFromFPLImport('
    )
    === 1
);


echo "<br>";


echo "============================================<br>";
echo "B. Authoritative Gameweek Reuse<br>";
echo "============================================<br>";


commandCentrePerformanceCheck(
    'Dashboard does not call getGameweekDecision directly',
    strpos(
        $dashboardSource,
        '->getGameweekDecision('
    )
    === false
);


commandCentrePerformanceCheck(
    'Dashboard does not call getGameweekStartingXI directly',
    strpos(
        $dashboardSource,
        '->getGameweekStartingXI('
    )
    === false
);


commandCentrePerformanceCheck(
    'Dashboard does not call getCaptainRecommendations directly',
    strpos(
        $dashboardSource,
        '->getCaptainRecommendations('
    )
    === false
);


commandCentrePerformanceCheck(
    'Dashboard does not call getSquadTransferRecommendations directly',
    strpos(
        $dashboardSource,
        '->getSquadTransferRecommendations('
    )
    === false
);


commandCentrePerformanceCheck(
    'CommandCentreService requests the authoritative gameweek decision once',
    substr_count(
        $serviceSource,
        '->getGameweekDecision('
    )
    === 1
);


echo "<br>";


echo "============================================<br>";
echo "C. Squad Horizon Reuse<br>";
echo "============================================<br>";


commandCentrePerformanceCheck(
    'Dashboard does not execute Squad Horizon directly',
    strpos(
        $dashboardSource,
        '->buildForImportedSquad('
    )
    === false
);


commandCentrePerformanceCheck(
    'CommandCentreService requests Squad Horizon once',
    substr_count(
        $serviceSource,
        '->buildForImportedSquad('
    )
    === 1
);


echo "<br>";


echo "============================================<br>";
echo "D. Chip Orchestration Reuse<br>";
echo "============================================<br>";


commandCentrePerformanceCheck(
    'Dashboard does not call individual Wildcard decision evaluation methods',
    preg_match(
        '/\$wildcardDecisionIntelligenceService\s*->/',
        $dashboardSource
    )
    === 0
);


commandCentrePerformanceCheck(
    'Dashboard does not call individual Free Hit decision evaluation methods',
    preg_match(
        '/\$freeHitDecisionIntelligenceService\s*->/',
        $dashboardSource
    )
    === 0
);


commandCentrePerformanceCheck(
    'Dashboard does not call individual Bench Boost decision evaluation methods',
    preg_match(
        '/\$benchBoostDecisionIntelligenceService\s*->/',
        $dashboardSource
    )
    === 0
);


commandCentrePerformanceCheck(
    'Dashboard does not call individual Triple Captain decision evaluation methods',
    preg_match(
        '/\$tripleCaptainDecisionIntelligenceService\s*->/',
        $dashboardSource
    )
    === 0
);


commandCentrePerformanceCheck(
    'CommandCentreService invokes the chip production orchestrator once',
    preg_match_all(
        '/\\$this\\s*'
        . '->\\s*chipRecommendationProductionOrchestrator\\s*'
        . '->\\s*build\\s*\\(/s',
        $serviceSource
    )
    === 1
);


echo "<br>";


echo "============================================<br>";
echo "E. Shared Expensive Inputs<br>";
echo "============================================<br>";


commandCentrePerformanceCheck(
    'Dashboard builds the full Player Intelligence pool once',
    substr_count(
        $dashboardSource,
        '->getAllPlayerSummaries('
    )
    === 1
);


commandCentrePerformanceCheck(
    'Dashboard resolves the actionable gameweek once',
    substr_count(
        $dashboardSource,
        '$actionableGameweekResolver'
    )
    >= 2
    &&
    substr_count(
        $dashboardSource,
        '$actionableGameweekResolver'
    )
    <= 3
    &&
    substr_count(
        $dashboardSource,
        '->resolve('
    )
    === 1
);


commandCentrePerformanceCheck(
    'Dashboard constructs one CommandCentreService instance',
    substr_count(
        $dashboardSource,
        'new CommandCentreService('
    )
    === 1
);


echo "<br>";


echo "============================================<br>";
echo "F. No Runtime Timing Threshold<br>";
echo "============================================<br>";


commandCentrePerformanceCheck(
    'Performance contract avoids brittle microtime timing gates',
    strpos(
        $dashboardSource,
        'microtime('
    )
    === false
    &&
    strpos(
        $serviceSource,
        'microtime('
    )
    === false
);


commandCentrePerformanceCheck(
    'Performance contract avoids sleep-based runtime behaviour',
    strpos(
        $dashboardSource,
        'sleep('
    )
    === false
    &&
    strpos(
        $serviceSource,
        'sleep('
    )
    === false
);


echo "<br>";


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
