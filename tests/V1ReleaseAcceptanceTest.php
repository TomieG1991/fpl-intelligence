<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "v1.0 Release Acceptance Test<br>";
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

function v1ReleaseAcceptanceCheck(
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
 * HEADING HELPER
 * ============================================================
 */

function v1ReleaseAcceptanceHeading(
    string $heading
): void {

    echo "============================================<br>";
    echo htmlspecialchars(
        $heading,
        ENT_QUOTES,
        'UTF-8'
    );
    echo "<br>";
    echo "============================================<br>";
}


/*
 * ============================================================
 * PROJECT PATHS
 * ============================================================
 */

$projectRoot =
    realpath(
        __DIR__
        . '/..'
    );


$classesPath =
    $projectRoot !== false
        ? $projectRoot
            . DIRECTORY_SEPARATOR
            . 'classes'
        : '';


$publicPath =
    $projectRoot !== false
        ? $projectRoot
            . DIRECTORY_SEPARATOR
            . 'public'
        : '';


$testsPath =
    $projectRoot !== false
        ? $projectRoot
            . DIRECTORY_SEPARATOR
            . 'tests'
        : '';


/*
 * ============================================================
 * SCENARIO A
 * CORE INTELLIGENCE FOUNDATION
 * ============================================================
 */

echo "<br>";

v1ReleaseAcceptanceHeading(
    'Scenario A: Core Intelligence Foundation'
);


$coreClasses = [

    'PlayerIntelligenceService.php' =>
        'Player Intelligence application service',

    'PlayerExpectedPoints.php' =>
        'Player Expected Points',

    'MultiGameweekExpectedPoints.php' =>
        'Multi-gameweek Expected Points',

    'FixtureIntelligence.php' =>
        'Fixture Intelligence',

    'TeamIntelligence.php' =>
        'Team Intelligence',

    'TeamStrengthModel.php' =>
        'Team Strength model'
];


foreach (
    $coreClasses
    as $filename => $description
) {

    v1ReleaseAcceptanceCheck(
        $description
        . ' production boundary exists',
        is_file(
            $classesPath
            . DIRECTORY_SEPARATOR
            . $filename
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO B
 * MANAGER SQUAD JOURNEY
 * ============================================================
 */

v1ReleaseAcceptanceHeading(
    'Scenario B: Manager Squad Journey'
);


v1ReleaseAcceptanceCheck(
    'FPL squad import production boundary exists',
    is_file(
        $classesPath
        . DIRECTORY_SEPARATOR
        . 'FPLSquadImporter.php'
    )
);


v1ReleaseAcceptanceCheck(
    'Player Intelligence exposes imported-squad mapping',
    method_exists(
        'PlayerIntelligenceService',
        'buildSquadFromFPLImport'
    )
);


v1ReleaseAcceptanceCheck(
    'Player Intelligence exposes Starting XI orchestration',
    method_exists(
        'PlayerIntelligenceService',
        'getGameweekStartingXI'
    )
);


v1ReleaseAcceptanceCheck(
    'Player Intelligence exposes captaincy orchestration',
    method_exists(
        'PlayerIntelligenceService',
        'getCaptainRecommendations'
    )
);


v1ReleaseAcceptanceCheck(
    'Player Intelligence exposes manager-level Gameweek Decision orchestration',
    method_exists(
        'PlayerIntelligenceService',
        'getGameweekDecision'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * TRANSFER INTELLIGENCE
 * ============================================================
 */

v1ReleaseAcceptanceHeading(
    'Scenario C: Transfer Intelligence'
);


v1ReleaseAcceptanceCheck(
    'Player Intelligence exposes squad-aware single-transfer recommendations',
    method_exists(
        'PlayerIntelligenceService',
        'getSquadTransferRecommendations'
    )
);


v1ReleaseAcceptanceCheck(
    'Player Intelligence exposes squad-aware double-transfer recommendations',
    method_exists(
        'PlayerIntelligenceService',
        'getSquadDoubleTransferRecommendations'
    )
);


v1ReleaseAcceptanceCheck(
    'Transfer Optimizer production boundary exists',
    is_file(
        $classesPath
        . DIRECTORY_SEPARATOR
        . 'TransferOptimizer.php'
    )
);


v1ReleaseAcceptanceCheck(
    'Squad Transfer Optimizer production boundary exists',
    is_file(
        $classesPath
        . DIRECTORY_SEPARATOR
        . 'SquadTransferOptimizer.php'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * MULTI-GAMEWEEK SQUAD PLANNING
 * ============================================================
 */

v1ReleaseAcceptanceHeading(
    'Scenario D: Multi-Gameweek Squad Planning'
);


v1ReleaseAcceptanceCheck(
    'Player Intelligence exposes multi-gameweek Expected Points',
    method_exists(
        'PlayerIntelligenceService',
        'getPlayerMultiGameweekExpectedPoints'
    )
);


v1ReleaseAcceptanceCheck(
    'Squad Horizon application service exists',
    class_exists(
        'SquadHorizonIntelligenceService'
    )
);


v1ReleaseAcceptanceCheck(
    'Squad Horizon accepts an imported FPL squad',
    method_exists(
        'SquadHorizonIntelligenceService',
        'buildForImportedSquad'
    )
);


v1ReleaseAcceptanceCheck(
    'Squad Horizon accepts an already-resolved squad',
    method_exists(
        'SquadHorizonIntelligenceService',
        'buildForResolvedSquad'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * CHIP DECISION INTELLIGENCE
 * ============================================================
 */

v1ReleaseAcceptanceHeading(
    'Scenario E: Chip Decision Intelligence'
);


$chipServices = [

    'WildcardDecisionIntelligenceService' =>
        'Wildcard',

    'FreeHitDecisionIntelligenceService' =>
        'Free Hit',

    'BenchBoostDecisionIntelligenceService' =>
        'Bench Boost',

    'TripleCaptainDecisionIntelligenceService' =>
        'Triple Captain'
];


foreach (
    $chipServices
    as $className => $description
) {

    v1ReleaseAcceptanceCheck(
        $description
        . ' decision-intelligence service exists',
        class_exists(
            $className
        )
    );


    v1ReleaseAcceptanceCheck(
        $description
        . ' decision-intelligence service exposes build()',
        method_exists(
            $className,
            'build'
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * HISTORICAL EVIDENCE LOOP
 * ============================================================
 */

v1ReleaseAcceptanceHeading(
    'Scenario F: Historical Evidence Loop'
);


$historicalClasses = [

    'RecommendationCandidateProductionCapture.php' =>
        'Recommendation candidate production capture',

    'RecommendationCandidateProductionService.php' =>
        'Recommendation candidate production service',

    'RecommendationCandidateRepository.php' =>
        'Recommendation candidate repository',

    'RecommendationSnapshotRepository.php' =>
        'Recommendation snapshot repository',

    'PlayerGameweekOutcomeService.php' =>
        'Player gameweek outcome service',

    'PlayerProjectionBacktestingService.php' =>
        'Projection backtesting service',

    'PlayerRankingBacktestingService.php' =>
        'Ranking backtesting service',

    'StartingXIBacktestingService.php' =>
        'Starting XI backtesting service',

    'CaptainBacktestingService.php' =>
        'Captain backtesting service',

    'TransferBacktestingService.php' =>
        'Transfer backtesting service'
];


foreach (
    $historicalClasses
    as $filename => $description
) {

    v1ReleaseAcceptanceCheck(
        $description
        . ' exists',
        is_file(
            $classesPath
            . DIRECTORY_SEPARATOR
            . $filename
        )
    );
}


v1ReleaseAcceptanceCheck(
    'Recommendation production capture exposes capture()',
    method_exists(
        'RecommendationCandidateProductionCapture',
        'capture'
    )
);


v1ReleaseAcceptanceCheck(
    'Player gameweek outcome service exposes historical gameweek retrieval',
    method_exists(
        'PlayerGameweekOutcomeService',
        'getByGameweekId'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * OPERATIONAL DATA LIFECYCLE
 * ============================================================
 */

v1ReleaseAcceptanceHeading(
    'Scenario G: Operational Data Lifecycle'
);


v1ReleaseAcceptanceCheck(
    'Data Update Coordinator exists',
    class_exists(
        'DataUpdateCoordinator'
    )
);


v1ReleaseAcceptanceCheck(
    'Data Update Coordinator exposes run()',
    method_exists(
        'DataUpdateCoordinator',
        'run'
    )
);


v1ReleaseAcceptanceCheck(
    'Update Health Service exists',
    class_exists(
        'UpdateHealthService'
    )
);


v1ReleaseAcceptanceCheck(
    'Update Health Service exposes evaluate()',
    method_exists(
        'UpdateHealthService',
        'evaluate'
    )
);


v1ReleaseAcceptanceCheck(
    'Shared public data-health evaluation exists',
    is_file(
        $publicPath
        . DIRECTORY_SEPARATOR
        . 'includes'
        . DIRECTORY_SEPARATOR
        . 'data-health.php'
    )
);


v1ReleaseAcceptanceCheck(
    'Shared public stale-data warning exists',
    is_file(
        $publicPath
        . DIRECTORY_SEPARATOR
        . 'includes'
        . DIRECTORY_SEPARATOR
        . 'data-health-warning.php'
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * PUBLIC APPLICATION CONTRACT
 * ============================================================
 */

v1ReleaseAcceptanceHeading(
    'Scenario H: Public Application Contract'
);


$publicPages = [

    'index.php' =>
        'Dashboard',

    'fixtures.php' =>
        'Fixtures',

    'players.php' =>
        'Players',

    'player.php' =>
        'Player Profile',

    'teams.php' =>
        'Teams',

    'team.php' =>
        'Team Profile',

    'squad.php' =>
        'Squad',

    'gameweek.php' =>
        'Gameweek Intelligence',

    'transfers.php' =>
        'Transfer Intelligence',

    'transfer-planner.php' =>
        'Transfer Planner',

    'transfer-optimizer.php' =>
        'Transfer Optimizer',

    'wildcard.php' =>
        'Wildcard Intelligence',

    'chips.php' =>
        'Chip Intelligence',

    'compare.php' =>
        'Player Comparison'
];


foreach (
    $publicPages
    as $filename => $description
) {

    v1ReleaseAcceptanceCheck(
        $description
        . ' public page exists',
        is_file(
            $publicPath
            . DIRECTORY_SEPARATOR
            . $filename
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * SEASON-STATE ACCEPTANCE EVIDENCE
 * ============================================================
 */

v1ReleaseAcceptanceHeading(
    'Scenario I: Season-State Acceptance Evidence'
);


/*
 * These tests remain the authoritative detailed behavioural
 * evidence for season-state handling.
 *
 * This release-acceptance test deliberately verifies that the
 * specialised permanent coverage remains part of the project
 * rather than duplicating its calculations here.
 */

$seasonStateTests = [
    'GameweekScheduleIntelligenceTest.php' =>
        'Normal, Blank and Double Gameweek schedule semantics',

    'GameweekScheduleIntelligenceEdgeCasesTest.php' =>
        'Schedule edge cases',

    'GameweekScheduleIntelligenceRealDataTest.php' =>
        'Real-data schedule intelligence',

    'EffectiveConfidenceServiceIntegrationTest.php' =>
        'Early-season confidence integration',

    'SquadHorizonBlankDoubleStartingXITest.php' =>
        'Blank and Double Gameweek Starting XI behaviour',

    'SquadHorizonBlankDoubleCaptaincyTest.php' =>
        'Blank and Double Gameweek captaincy behaviour',

    'SquadHorizonBlankDoubleTransferEvaluationTest.php' =>
        'Blank and Double Gameweek transfer behaviour',

    'SquadHorizonDoubleGameweekFixtureClashTest.php' =>
        'Double Gameweek fixture-clash behaviour',

    'SquadHorizonMixedScheduleRegressionTest.php' =>
        'Mixed schedule regression',

    'ActionableGameweekResolverTest.php' =>
        'Preseason and future-deadline actionable Gameweek resolution',

    'PlayerGameweekOutcomeAvailabilityTest.php' =>
        'Completed versus in-progress Gameweek outcome availability'
];

foreach (
    $seasonStateTests
    as $filename => $description
) {

    v1ReleaseAcceptanceCheck(
        $description
        . ' has permanent regression coverage',
        is_file(
            $testsPath
            . DIRECTORY_SEPARATOR
            . $filename
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * END-TO-END ACCEPTANCE EVIDENCE
 * ============================================================
 */

v1ReleaseAcceptanceHeading(
    'Scenario J: End-to-End Acceptance Evidence'
);


$integrationTests = [

    'GameweekDecisionRealDataTest.php' =>
        'Real-data manager-level Gameweek Decision',

    'SquadHorizonRealDataIntegrationTest.php' =>
        'Real-data Squad Horizon integration',

    'RecommendationCandidateProductionCaptureIntegrationTest.php' =>
        'Production recommendation capture integration',

    'PlayerGameweekOutcomeServiceIntegrationTest.php' =>
        'Historical player outcome integration',

    'PlayerProjectionBacktestIntegrationTest.php' =>
        'Projection backtesting integration',

    'StartingXIBacktestingServiceIntegrationTest.php' =>
        'Starting XI backtesting integration',

    'CaptainBacktestingServiceIntegrationTest.php' =>
        'Captain backtesting integration',

    'TransferBacktestingServiceIntegrationTest.php' =>
        'Transfer backtesting integration',

    'ChipIntelligencePageTest.php' =>
        'Public Chip Intelligence integration',

    'DataHealthPageIntegrationTest.php' =>
        'Public data-health integration'
];


foreach (
    $integrationTests
    as $filename => $description
) {

    v1ReleaseAcceptanceCheck(
        $description
        . ' has permanent integration coverage',
        is_file(
            $testsPath
            . DIRECTORY_SEPARATOR
            . $filename
        )
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * RELEASE CONTRACT
 * ============================================================
 */

v1ReleaseAcceptanceHeading(
    'Scenario K: v1.0 Release Contract'
);


v1ReleaseAcceptanceCheck(
    'Central Player Intelligence application service remains available',
    class_exists(
        'PlayerIntelligenceService'
    )
);


v1ReleaseAcceptanceCheck(
    'Manager-level Gameweek Decision engine remains available',
    class_exists(
        'GameweekDecisionEngine'
    )
);


v1ReleaseAcceptanceCheck(
    'Squad Horizon intelligence remains available',
    class_exists(
        'SquadHorizonIntelligence'
    )
);


v1ReleaseAcceptanceCheck(
    'Recommendation evidence capture remains available',
    class_exists(
        'RecommendationCandidateProductionCapture'
    )
);


v1ReleaseAcceptanceCheck(
    'Historical outcome evaluation remains available',
    class_exists(
        'PlayerGameweekOutcomeService'
    )
);


v1ReleaseAcceptanceCheck(
    'Operational update coordination remains available',
    class_exists(
        'DataUpdateCoordinator'
    )
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Summary<br>";
echo "============================================<br><br>";


echo "Assertions passed: "
    . $passed
    . "<br>";


echo "Assertions failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "<strong>RESULT: TESTS PASSED ✅</strong><br>";

} else {

    echo "<strong>RESULT: TESTS FAILED ❌</strong><br>";
}