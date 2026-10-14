<?php

echo "============================================<br>";
echo "Command Centre Dashboard Attention Required Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentreAttentionCheck(
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
 * A. AUTHORITATIVE ATTENTION EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Authoritative Attention Evidence<br>";
echo "============================================<br>";


commandCentreAttentionCheck(
    'Dashboard consumes Command Centre attention evidence',
    preg_match(
        '/\$commandCentre\s*\[\s*[\'"]attention[\'"]\s*\]/s',
        $dashboardSource
    )
    === 1
);


commandCentreAttentionCheck(
    'Attention section consumes authoritative squad risks',
    strpos(
        $dashboardSource,
        "'squad_risks'"
    ) !== false
);


commandCentreAttentionCheck(
    'Attention section consumes authoritative transfer advice',
    strpos(
        $dashboardSource,
        "'transfer_advice'"
    ) !== false
);


commandCentreAttentionCheck(
    'Attention section consumes authoritative chip evidence',
    strpos(
        $dashboardSource,
        "'chips'"
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * B. PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "B. Attention Required Presentation<br>";
echo "============================================<br>";


commandCentreAttentionCheck(
    'Command Centre presents Attention Required',
    strpos(
        $dashboardSource,
        '>Attention Required<'
    ) !== false
);


commandCentreAttentionCheck(
    'Attention section labels Squad Risks',
    strpos(
        $dashboardSource,
        'Squad Risks'
    ) !== false
);


commandCentreAttentionCheck(
    'Attention section labels Transfer Advice',
    strpos(
        $dashboardSource,
        'Transfer Advice'
    ) !== false
);


commandCentreAttentionCheck(
    'Attention section labels Chip Watch',
    strpos(
        $dashboardSource,
        'Chip Watch'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * C. NO DUPLICATE INTELLIGENCE
 * ============================================================
 */

echo "============================================<br>";
echo "C. No Duplicate Intelligence<br>";
echo "============================================<br>";


commandCentreAttentionCheck(
    'Dashboard does not directly calculate transfer recommendations',
    strpos(
        $dashboardSource,
        '->getSquadTransferRecommendations('
    ) === false
);


commandCentreAttentionCheck(
    'Dashboard does not directly calculate captain recommendations',
    strpos(
        $dashboardSource,
        '->getCaptainRecommendations('
    ) === false
);


commandCentreAttentionCheck(
    'Dashboard does not directly calculate Starting XI',
    strpos(
        $dashboardSource,
        '->getGameweekStartingXI('
    ) === false
);


commandCentreAttentionCheck(
    'Dashboard does not directly calculate Gameweek Decision',
    strpos(
        $dashboardSource,
        '->getGameweekDecision('
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * D. CHIP WATCH
 * ============================================================
 */

echo "============================================<br>";
echo "D. Chip Watch<br>";
echo "============================================<br>";


commandCentreAttentionCheck(
    'Chip Watch recognises authoritative Use recommendations',
    strpos(
        $dashboardSource,
        "=== 'Use'"
    ) !== false
);


commandCentreAttentionCheck(
    'Chip Watch recognises authoritative Consider recommendations',
    strpos(
        $dashboardSource,
        "=== 'Consider'"
    ) !== false
);


commandCentreAttentionCheck(
    'Chip Watch does not introduce new chip scoring thresholds',
    strpos(
        $dashboardSource,
        'ATTENTION_CHIP'
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * E. EMPTY / CLEAR STATE
 * ============================================================
 */

echo "============================================<br>";
echo "E. Empty / Clear State<br>";
echo "============================================<br>";


commandCentreAttentionCheck(
    'Attention section has a clear-state message',
    strpos(
        $dashboardSource,
        'No immediate attention items'
    ) !== false
);


commandCentreAttentionCheck(
    'Attention section has an unavailable state',
    strpos(
        $dashboardSource,
        'Attention evidence unavailable'
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


commandCentreAttentionCheck(
    'Attention section links to Gameweek Intelligence',
    strpos(
        $dashboardSource,
        'Review Gameweek Intelligence'
    ) !== false
);


commandCentreAttentionCheck(
    'Attention drill-down uses existing manager-aware Gameweek URL',
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


commandCentreAttentionCheck(
    'Attention section has a labelled heading',
    strpos(
        $dashboardSource,
        'id="command-centre-attention-title"'
    ) !== false
);


commandCentreAttentionCheck(
    'Attention section references its heading',
    strpos(
        $dashboardSource,
        'aria-labelledby="command-centre-attention-title"'
    ) !== false
);


commandCentreAttentionCheck(
    'Attention state is exposed as status information',
    strpos(
        $dashboardSource,
        'id="command-centre-attention-status"'
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