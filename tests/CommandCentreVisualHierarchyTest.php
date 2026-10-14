<?php

echo "============================================<br>";
echo "Command Centre Visual Hierarchy Test<br>";
echo "============================================<br><br>";


$passed = 0;
$failed = 0;


function commandCentreHierarchyCheck(
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


$dashboardSource =
    is_file($dashboardPath)
        ? file_get_contents($dashboardPath)
        : false;


$dashboardSource =
    is_string($dashboardSource)
        ? $dashboardSource
        : '';


echo "============================================<br>";
echo "A. Manager Context<br>";
echo "============================================<br>";


commandCentreHierarchyCheck(
    'Manager and gameweek context use a dedicated compact context row',
    strpos(
        $dashboardSource,
        'command-centre-context-row'
    ) !== false
);


commandCentreHierarchyCheck(
    'Manager Overview is marked as compact context',
    strpos(
        $dashboardSource,
        'command-centre-manager-context'
    ) !== false
);


commandCentreHierarchyCheck(
    'Gameweek Overview is marked as compact context',
    strpos(
        $dashboardSource,
        'command-centre-gameweek-context'
    ) !== false
);


echo "<br>";
echo "============================================<br>";
echo "B. Decision Hierarchy<br>";
echo "============================================<br>";


commandCentreHierarchyCheck(
    'Command Centre exposes a dedicated decision row',
    strpos(
        $dashboardSource,
        'command-centre-decision-row'
    ) !== false
);


commandCentreHierarchyCheck(
    'Primary Gameweek Decision is marked as the primary decision surface',
    strpos(
        $dashboardSource,
        'class="dashboard-card command-centre-primary-decision"'
    ) !== false
);


commandCentreHierarchyCheck(
    'Captaincy is marked as the companion decision surface',
    strpos(
        $dashboardSource,
        'class="dashboard-card command-centre-captaincy"'
    ) !== false
);


$decisionRowPosition =
    strpos(
        $dashboardSource,
        'command-centre-decision-row'
    );

$primaryDecisionPosition =
    strpos(
        $dashboardSource,
        'command-centre-primary-decision'
    );

$captaincyPosition =
    strpos(
        $dashboardSource,
        'command-centre-captaincy'
    );

$teamSelectionPosition =
    strpos(
        $dashboardSource,
        'command-centre-team-selection'
    );


commandCentreHierarchyCheck(
    'Primary Decision and Captaincy both appear inside the decision row before Team Selection',
    $decisionRowPosition !== false
    &&
    $primaryDecisionPosition !== false
    &&
    $captaincyPosition !== false
    &&
    $teamSelectionPosition !== false
    &&
    $decisionRowPosition < $primaryDecisionPosition
    &&
    $primaryDecisionPosition < $captaincyPosition
    &&
    $captaincyPosition < $teamSelectionPosition
);


echo "<br>";
echo "============================================<br>";
echo "C. Team Selection<br>";
echo "============================================<br>";


commandCentreHierarchyCheck(
    'Starting XI has its own full-width team-selection surface',
    strpos(
        $dashboardSource,
        'class="dashboard-card command-centre-team-selection"'
    ) !== false
);


commandCentreHierarchyCheck(
    'Starting XI is presented through a dedicated team layout',
    strpos(
        $dashboardSource,
        'command-centre-team-layout'
    ) !== false
);


commandCentreHierarchyCheck(
    'Bench has a dedicated row beneath the starting team',
    strpos(
        $dashboardSource,
        'command-centre-bench-row'
    ) !== false
);


echo "<br>";
echo "============================================<br>";
echo "D. Planning Surfaces<br>";
echo "============================================<br>";


commandCentreHierarchyCheck(
    'Transfer, squad outlook and chip planning share a compact planning row',
    strpos(
        $dashboardSource,
        'command-centre-planning-row'
    ) !== false
);


commandCentreHierarchyCheck(
    'Transfer Intelligence is marked as a planning card',
    strpos(
        $dashboardSource,
        'command-centre-transfer-card'
    ) !== false
);


commandCentreHierarchyCheck(
    'Squad Outlook is marked as a planning card',
    strpos(
        $dashboardSource,
        'command-centre-horizon-card'
    ) !== false
);


commandCentreHierarchyCheck(
    'Chip Intelligence is marked as a planning card',
    strpos(
        $dashboardSource,
        'command-centre-chip-card'
    ) !== false
);


$planningRowPosition =
    strpos(
        $dashboardSource,
        'command-centre-planning-row'
    );

$transferPosition =
    strpos(
        $dashboardSource,
        'command-centre-transfer-card'
    );

$horizonPosition =
    strpos(
        $dashboardSource,
        'command-centre-horizon-card'
    );

$chipPosition =
    strpos(
        $dashboardSource,
        'command-centre-chip-card'
    );

$attentionRowPosition =
    strpos(
        $dashboardSource,
        'command-centre-attention-row'
    );


commandCentreHierarchyCheck(
    'Planning row contains Transfers, Squad Outlook and Chips before Attention',
    $planningRowPosition !== false
    &&
    $transferPosition !== false
    &&
    $horizonPosition !== false
    &&
    $chipPosition !== false
    &&
    $attentionRowPosition !== false
    &&
    $planningRowPosition < $transferPosition
    &&
    $transferPosition < $horizonPosition
    &&
    $horizonPosition < $chipPosition
    &&
    $chipPosition < $attentionRowPosition
);


echo "<br>";
echo "============================================<br>";
echo "E. Attention & Health<br>";
echo "============================================<br>";


commandCentreHierarchyCheck(
    'Attention and opportunities share a dedicated row',
    strpos(
        $dashboardSource,
        'command-centre-attention-row'
    ) !== false
);


commandCentreHierarchyCheck(
    'Data Health is marked as a full-width Command Centre surface',
    strpos(
        $dashboardSource,
        'command-centre-data-health-card'
    ) !== false
);


echo "<br>";
echo "============================================<br>";
echo "F. Legacy Dashboard Demotion<br>";
echo "============================================<br>";


commandCentreHierarchyCheck(
    'Legacy dashboard content has an explicit secondary-content boundary',
    strpos(
        $dashboardSource,
        'dashboard-secondary-content'
    ) !== false
);


$contextRowPosition =
    strpos(
        $dashboardSource,
        'command-centre-context-row'
    );

$dataHealthPosition =
    strpos(
        $dashboardSource,
        'command-centre-data-health-card'
    );

$secondaryContentPosition =
    strpos(
        $dashboardSource,
        'dashboard-secondary-content'
    );


commandCentreHierarchyCheck(
    'Command Centre surfaces appear in the intended scan order',
    $contextRowPosition !== false
    &&
    $decisionRowPosition !== false
    &&
    $primaryDecisionPosition !== false
    &&
    $captaincyPosition !== false
    &&
    $teamSelectionPosition !== false
    &&
    $planningRowPosition !== false
    &&
    $attentionRowPosition !== false
    &&
    $dataHealthPosition !== false
    &&
    $secondaryContentPosition !== false
    &&
    $contextRowPosition < $decisionRowPosition
    &&
    $decisionRowPosition < $primaryDecisionPosition
    &&
    $primaryDecisionPosition < $captaincyPosition
    &&
    $captaincyPosition < $teamSelectionPosition
    &&
    $teamSelectionPosition < $planningRowPosition
    &&
    $planningRowPosition < $attentionRowPosition
    &&
    $attentionRowPosition < $dataHealthPosition
    &&
    $dataHealthPosition < $secondaryContentPosition
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