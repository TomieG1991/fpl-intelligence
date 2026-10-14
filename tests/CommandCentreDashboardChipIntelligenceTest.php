<?php

echo "============================================<br>";
echo "Command Centre Dashboard Chip Intelligence Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentreChipCheck(
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
 * A. AUTHORITATIVE CHIP EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Authoritative Chip Evidence<br>";
echo "============================================<br>";


commandCentreChipCheck(
    'Dashboard consumes Command Centre chip evidence',
    preg_match(
        '/\$commandCentre\s*\[\s*[\'"]chips[\'"]\s*\]/s',
        $dashboardSource
    )
    === 1
);


commandCentreChipCheck(
    'Dashboard does not directly invoke Wildcard decision intelligence',
    strpos(
        $dashboardSource,
        '->evaluateWildcard'
    ) === false
);


commandCentreChipCheck(
    'Dashboard does not directly invoke Free Hit decision intelligence',
    strpos(
        $dashboardSource,
        '->evaluateFreeHit'
    ) === false
);


commandCentreChipCheck(
    'Dashboard does not directly invoke Bench Boost decision intelligence',
    strpos(
        $dashboardSource,
        '->evaluateBenchBoost'
    ) === false
);


commandCentreChipCheck(
    'Dashboard does not directly invoke Triple Captain decision intelligence',
    strpos(
        $dashboardSource,
        '->evaluateTripleCaptain'
    ) === false
);


echo "<br>";


/*
 * ============================================================
 * B. CHIP INTELLIGENCE PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "B. Chip Intelligence Presentation<br>";
echo "============================================<br>";


commandCentreChipCheck(
    'Command Centre presents Chip Intelligence',
    strpos(
        $dashboardSource,
        '>Chip Intelligence<'
    ) !== false
);


foreach (
    [
        'wildcard' =>
            'Wildcard',

        'free_hit' =>
            'Free Hit',

        'bench_boost' =>
            'Bench Boost',

        'triple_captain' =>
            'Triple Captain'
    ]
    as $chipKey => $chipLabel
) {

    commandCentreChipCheck(
        'Chip presentation consumes '
            . $chipLabel
            . ' evidence',
        strpos(
            $dashboardSource,
            "'"
                . $chipKey
                . "'"
        ) !== false
    );
}


echo "<br>";


/*
 * ============================================================
 * C. GENUINE CHIP DECISION CONTRACTS
 * ============================================================
 */

echo "============================================<br>";
echo "C. Genuine Chip Decision Contracts<br>";
echo "============================================<br>";


commandCentreChipCheck(
    'Wildcard presentation consumes authoritative timing result',
    strpos(
        $dashboardSource,
        "'timing_result'"
    ) !== false
);


commandCentreChipCheck(
    'Chip presentation consumes authoritative decision evidence',
    strpos(
        $dashboardSource,
        "'decision'"
    ) !== false
);


commandCentreChipCheck(
    'Chip decisions use the ChipDecision recommendation getter',
    strpos(
        $dashboardSource,
        '->getRecommendation()'
    ) !== false
);


commandCentreChipCheck(
    'Chip decisions use the ChipDecision confidence getter',
    strpos(
        $dashboardSource,
        '->getConfidence()'
    ) !== false
);


commandCentreChipCheck(
    'Chip decisions use the ChipDecision explanation getter',
    strpos(
        $dashboardSource,
        '->getExplanation()'
    ) !== false
);


commandCentreChipCheck(
    'Dashboard labels Wildcard',
    strpos(
        $dashboardSource,
        'Wildcard'
    ) !== false
);


commandCentreChipCheck(
    'Dashboard labels Free Hit',
    strpos(
        $dashboardSource,
        'Free Hit'
    ) !== false
);


commandCentreChipCheck(
    'Dashboard labels Bench Boost',
    strpos(
        $dashboardSource,
        'Bench Boost'
    ) !== false
);


commandCentreChipCheck(
    'Dashboard labels Triple Captain',
    strpos(
        $dashboardSource,
        'Triple Captain'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * D. FAILURE STATE
 * ============================================================
 */

echo "============================================<br>";
echo "D. Failure State<br>";
echo "============================================<br>";


commandCentreChipCheck(
    'Chip Intelligence has an unavailable state',
    strpos(
        $dashboardSource,
        'Chip Intelligence unavailable'
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


commandCentreChipCheck(
    'Chip section links to specialist Chip Intelligence',
    strpos(
        $dashboardSource,
        'Explore Chip Intelligence'
    ) !== false
);


commandCentreChipCheck(
    'Chip drill-down uses the shared specialist URL variable',
    strpos(
        $dashboardSource,
        '$chipIntelligenceUrl'
    ) !== false
);


commandCentreChipCheck(
    'Chip specialist URL uses the clean session-based destination',
    preg_match(
        '/\$chipIntelligenceUrl\s*=\s*[\'"]chips\.php[\'"]\s*;/s',
        $dashboardSource
    ) === 1
);


commandCentreChipCheck(
    'Chip specialist URL does not expose manager ID in the link',
    preg_match(
        '/\$chipIntelligenceUrl\s*=\s*[\'"][^\'"]*entry_id=/s',
        $dashboardSource
    ) !== 1
    &&
    preg_match(
        '/\$chipIntelligenceUrl\s*=\s*\$managerContextService\s*->\s*buildUrl\s*\(/s',
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


commandCentreChipCheck(
    'Chip Intelligence section has a labelled heading',
    strpos(
        $dashboardSource,
        'id="command-centre-chip-title"'
    ) !== false
);


commandCentreChipCheck(
    'Chip Intelligence section references its heading',
    strpos(
        $dashboardSource,
        'aria-labelledby="command-centre-chip-title"'
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
