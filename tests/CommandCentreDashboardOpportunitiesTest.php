<?php

echo "============================================<br>";
echo "Command Centre Dashboard Opportunities Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentreOpportunityCheck(
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
 * A. AUTHORITATIVE OPPORTUNITY EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Authoritative Opportunity Evidence<br>";
echo "============================================<br>";


commandCentreOpportunityCheck(
    'Opportunities consume authoritative Gameweek key insights',
    strpos(
        $dashboardSource,
        "'key_insights'"
    ) !== false
);


commandCentreOpportunityCheck(
    'Opportunities consume authoritative captaincy alternatives',
    strpos(
        $dashboardSource,
        "'alternatives'"
    ) !== false
);


commandCentreOpportunityCheck(
    'Opportunities consume authoritative transfer recommendations',
    strpos(
        $dashboardSource,
        "'recommendations'"
    ) !== false
);


commandCentreOpportunityCheck(
    'Opportunities consume authoritative chip decisions',
    strpos(
        $dashboardSource,
        '$commandCentreChipRows'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * B. PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "B. Opportunities Presentation<br>";
echo "============================================<br>";


commandCentreOpportunityCheck(
    'Command Centre presents Opportunities',
    strpos(
        $dashboardSource,
        '>Opportunities<'
    ) !== false
);


commandCentreOpportunityCheck(
    'Opportunities present Key Insights',
    strpos(
        $dashboardSource,
        'Key Insights'
    ) !== false
);


commandCentreOpportunityCheck(
    'Opportunities present Captaincy Alternatives',
    strpos(
        $dashboardSource,
        'Captaincy Alternatives'
    ) !== false
);


commandCentreOpportunityCheck(
    'Opportunities present Transfer Options',
    strpos(
        $dashboardSource,
        'Transfer Options'
    ) !== false
);


commandCentreOpportunityCheck(
    'Opportunities present Chip Opportunities',
    strpos(
        $dashboardSource,
        'Chip Opportunities'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * C. NO NEW INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "C. No New Intelligence<br>";
echo "============================================<br>";


commandCentreOpportunityCheck(
    'Dashboard does not directly calculate captain recommendations',
    strpos(
        $dashboardSource,
        '->getCaptainRecommendations('
    ) === false
);


commandCentreOpportunityCheck(
    'Dashboard does not directly calculate transfer recommendations',
    strpos(
        $dashboardSource,
        '->getSquadTransferRecommendations('
    ) === false
);


commandCentreOpportunityCheck(
    'Dashboard does not directly calculate Gameweek Decision',
    strpos(
        $dashboardSource,
        '->getGameweekDecision('
    ) === false
);


commandCentreOpportunityCheck(
    'Opportunities introduce no opportunity score',
    strpos(
        $dashboardSource,
        'opportunity_score'
    ) === false
);


commandCentreOpportunityCheck(
    'Opportunities introduce no opportunity threshold',
    strpos(
        $dashboardSource,
        'OPPORTUNITY_'
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * D. AUTHORITATIVE CHIP SEMANTICS
 * ============================================================
 */

echo "============================================<br>";
echo "D. Authoritative Chip Semantics<br>";
echo "============================================<br>";


commandCentreOpportunityCheck(
    'Chip Opportunities reuse authoritative Use recommendations',
    strpos(
        $dashboardSource,
        "=== 'Use'"
    ) !== false
);


commandCentreOpportunityCheck(
    'Chip Opportunities reuse authoritative Consider recommendations',
    strpos(
        $dashboardSource,
        "=== 'Consider'"
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * E. EMPTY / UNAVAILABLE STATE
 * ============================================================
 */

echo "============================================<br>";
echo "E. Empty / Unavailable State<br>";
echo "============================================<br>";


commandCentreOpportunityCheck(
    'Opportunities have a no-opportunity state',
    strpos(
        $dashboardSource,
        'No immediate opportunities surfaced'
    ) !== false
);


commandCentreOpportunityCheck(
    'Opportunities have an unavailable state',
    strpos(
        $dashboardSource,
        'Opportunity evidence unavailable'
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


commandCentreOpportunityCheck(
    'Opportunities link to Gameweek Intelligence',
    strpos(
        $dashboardSource,
        'Explore Gameweek Opportunities'
    ) !== false
);


commandCentreOpportunityCheck(
    'Opportunities reuse manager-aware Gameweek navigation',
    strpos(
        $dashboardSource,
        '$gameweekIntelligenceUrl'
    ) !== false
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


commandCentreOpportunityCheck(
    'Opportunities section has a labelled heading',
    strpos(
        $dashboardSource,
        'id="command-centre-opportunities-title"'
    ) !== false
);


commandCentreOpportunityCheck(
    'Opportunities section references its heading',
    strpos(
        $dashboardSource,
        'aria-labelledby="command-centre-opportunities-title"'
    ) !== false
);


commandCentreOpportunityCheck(
    'Opportunity state is exposed as status information',
    strpos(
        $dashboardSource,
        'id="command-centre-opportunities-status"'
    ) !== false
    &&
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
