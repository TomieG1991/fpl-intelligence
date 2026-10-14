<?php

echo "============================================<br>";
echo "Command Centre Deferred Heavy Intelligence Test<br>";
echo "============================================<br><br>";

$passed = 0;
$failed = 0;

function deferredHeavyCheck(
    string $description,
    bool $condition
): void {

    global $passed, $failed;

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

$dashboardSource =
    is_file($dashboardPath)
        ? file_get_contents($dashboardPath)
        : false;

$dashboardSource =
    is_string($dashboardSource)
        ? $dashboardSource
        : '';


echo "============================================<br>";
echo "A. Dashboard Critical Path<br>";
echo "============================================<br>";

deferredHeavyCheck(
    'Dashboard does not construct Wildcard decision intelligence',
    strpos(
        $dashboardSource,
        'new WildcardDecisionIntelligenceService('
    ) === false
);

deferredHeavyCheck(
    'Dashboard does not construct Free Hit decision intelligence',
    strpos(
        $dashboardSource,
        'new FreeHitDecisionIntelligenceService('
    ) === false
);

deferredHeavyCheck(
    'Dashboard does not construct Bench Boost decision intelligence',
    strpos(
        $dashboardSource,
        'new BenchBoostDecisionIntelligenceService('
    ) === false
);

deferredHeavyCheck(
    'Dashboard does not construct Triple Captain decision intelligence',
    strpos(
        $dashboardSource,
        'new TripleCaptainDecisionIntelligenceService('
    ) === false
);

deferredHeavyCheck(
    'Dashboard does not construct the chip production orchestrator',
    strpos(
        $dashboardSource,
        'new ChipRecommendationProductionOrchestrator('
    ) === false
);

echo "<br>";


echo "============================================<br>";
echo "B. Specialist Ownership<br>";
echo "============================================<br>";

deferredHeavyCheck(
    'Dashboard retains a clean Chip Intelligence specialist destination',
    strpos(
        $dashboardSource,
        "'chips.php'"
    ) !== false
    ||
    strpos(
        $dashboardSource,
        '"chips.php"'
    ) !== false
);

deferredHeavyCheck(
    'Dashboard retains the Explore Chip Intelligence drill-down',
    strpos(
        $dashboardSource,
        'Explore Chip Intelligence'
    ) !== false
);

echo "<br>";


echo "============================================<br>";
echo "C. No Duplicate Heavy Chip Evaluation<br>";
echo "============================================<br>";

deferredHeavyCheck(
    'Dashboard does not invoke chip orchestration directly',
    strpos(
        $dashboardSource,
        '->chipRecommendationProductionOrchestrator'
    ) === false
    &&
    strpos(
        $dashboardSource,
        '$chipRecommendationProductionOrchestrator->build('
    ) === false
);

deferredHeavyCheck(
    'Dashboard does not invoke Wildcard evaluation directly',
    preg_match(
        '/\$wildcardDecisionIntelligenceService\s*->/',
        $dashboardSource
    ) === 0
);

deferredHeavyCheck(
    'Dashboard does not invoke Free Hit evaluation directly',
    preg_match(
        '/\$freeHitDecisionIntelligenceService\s*->/',
        $dashboardSource
    ) === 0
);

deferredHeavyCheck(
    'Dashboard does not invoke Bench Boost evaluation directly',
    preg_match(
        '/\$benchBoostDecisionIntelligenceService\s*->/',
        $dashboardSource
    ) === 0
);

deferredHeavyCheck(
    'Dashboard does not invoke Triple Captain evaluation directly',
    preg_match(
        '/\$tripleCaptainDecisionIntelligenceService\s*->/',
        $dashboardSource
    ) === 0
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
