<?php

echo "============================================<br>";
echo "Command Centre Dashboard Transfer Intelligence Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentreTransferCheck(
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
 * A. AUTHORITATIVE TRANSFER EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Authoritative Transfer Evidence<br>";
echo "============================================<br>";


commandCentreTransferCheck(
    'Dashboard consumes Command Centre transfer evidence',
    preg_match(
        '/\$commandCentre\s*\[\s*[\'"]transfers[\'"]\s*\]/s',
        $dashboardSource
    )
    === 1
);


commandCentreTransferCheck(
    'Dashboard does not directly calculate transfer recommendations',
    strpos(
        $dashboardSource,
        '->getSquadTransferRecommendations('
    ) === false
);


commandCentreTransferCheck(
    'Dashboard does not directly evaluate player transfers',
    strpos(
        $dashboardSource,
        '->evaluatePlayerTransfer('
    ) === false
);


commandCentreTransferCheck(
    'Dashboard does not directly run transfer optimization',
    strpos(
        $dashboardSource,
        '->optimizeTransferCombination('
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * B. TRANSFER INTELLIGENCE PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "B. Transfer Intelligence Presentation<br>";
echo "============================================<br>";


commandCentreTransferCheck(
    'Command Centre presents Transfer Intelligence',
    strpos(
        $dashboardSource,
        '>Transfer Intelligence<'
    ) !== false
);


commandCentreTransferCheck(
    'Transfer presentation consumes authoritative priority',
    strpos(
        $dashboardSource,
        "'priority'"
    ) !== false
);


commandCentreTransferCheck(
    'Transfer presentation consumes authoritative score',
    strpos(
        $dashboardSource,
        "'score'"
    ) !== false
);


commandCentreTransferCheck(
    'Transfer presentation consumes authoritative recommendations',
    strpos(
        $dashboardSource,
        "'recommendations'"
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * C. PRIMARY TRANSFER ADVICE
 * ============================================================
 */

echo "============================================<br>";
echo "C. Primary Transfer Advice<br>";
echo "============================================<br>";


commandCentreTransferCheck(
    'Transfer section consumes decision-level transfer advice',
    strpos(
        $dashboardSource,
        '$commandCentreTransferAdvice'
    ) !== false
);


commandCentreTransferCheck(
    'Transfer section labels the authoritative advice',
    strpos(
        $dashboardSource,
        'Transfer Advice'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * D. RECOMMENDATION PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "D. Recommendation Presentation<br>";
echo "============================================<br>";


commandCentreTransferCheck(
    'Transfer recommendations expose player names',
    strpos(
        $dashboardSource,
        "'name'"
    ) !== false
);


commandCentreTransferCheck(
    'Transfer section has a no-recommendation state',
    strpos(
        $dashboardSource,
        'No transfer recommendation'
    ) !== false
);


commandCentreTransferCheck(
    'Transfer section has an unavailable state',
    strpos(
        $dashboardSource,
        'Transfer Intelligence unavailable'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * E. SPECIALIST DRILL-DOWN
 * ============================================================
 */

echo "============================================<br>";
echo "E. Specialist Drill-down<br>";
echo "============================================<br>";


commandCentreTransferCheck(
    'Transfer section links to specialist Transfer Intelligence',
    strpos(
        $dashboardSource,
        'Explore Transfer Intelligence'
    ) !== false
);


commandCentreTransferCheck(
    'Transfer drill-down uses the shared specialist URL variable',
    strpos(
        $dashboardSource,
        '$transferIntelligenceUrl'
    ) !== false
);


commandCentreTransferCheck(
    'Transfer specialist URL uses the clean session-based destination',
    preg_match(
        '/\$transferIntelligenceUrl\s*=\s*[\'"]transfers\.php[\'"]\s*;/s',
        $dashboardSource
    ) === 1
);


commandCentreTransferCheck(
    'Transfer specialist URL does not expose manager ID in the link',
    preg_match(
        '/\$transferIntelligenceUrl\s*=\s*[\'"][^\'"]*entry_id=/s',
        $dashboardSource
    ) !== 1
    &&
    preg_match(
        '/\$transferIntelligenceUrl\s*=\s*\$managerContextService\s*->\s*buildUrl\s*\(/s',
        $dashboardSource
    ) !== 1
);


echo "<br>";


/*
 * ============================================================
 * F. ACCESSIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "F. Accessibility<br>";
echo "============================================<br>";


commandCentreTransferCheck(
    'Transfer section has a labelled heading',
    strpos(
        $dashboardSource,
        'id="command-centre-transfer-title"'
    ) !== false
);


commandCentreTransferCheck(
    'Transfer section references its heading',
    strpos(
        $dashboardSource,
        'aria-labelledby="command-centre-transfer-title"'
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