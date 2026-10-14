<?php

echo "============================================<br>";
echo "Command Centre Attention Presentation Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function commandCentreAttentionPresentationCheck(
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
 * A. DASHBOARD SOURCE AND EVIDENCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Dashboard Source and Evidence<br>";
echo "============================================<br>";


commandCentreAttentionPresentationCheck(
    'Dashboard source is available',
    is_file($dashboardPath)
    && $dashboardSource !== ''
);


commandCentreAttentionPresentationCheck(
    'Attention Required uses Command Centre attention evidence',
    strpos($dashboardSource, '$commandCentreAttention') !== false
    && strpos($dashboardSource, "'squad_risks'") !== false
    && strpos($dashboardSource, "'transfer_advice'") !== false
);


/*
 * ============================================================
 * B. STRUCTURED SQUAD RISK PRESENTATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "B. Structured Squad Risk Presentation<br>";
echo "============================================<br>";


commandCentreAttentionPresentationCheck(
    'Dashboard extracts the nested authoritative risk rows',
    preg_match(
        '/\$attentionSquadRisks\s*\[\s*[\'\"]risks[\'\"]\s*\]/s',
        $dashboardSource
    ) === 1
);


commandCentreAttentionPresentationCheck(
    'Dashboard retains authoritative risk messages',
    strpos($dashboardSource, "'message'") !== false
    && strpos($dashboardSource, '$attentionRiskText') !== false
);


commandCentreAttentionPresentationCheck(
    'Dashboard escapes risk text before rendering',
    preg_match(
        '/htmlspecialchars\s*\(\s*\(string\)\s*\$attentionRiskText/s',
        $dashboardSource
    ) === 1
);


/*
 * ============================================================
 * C. STRUCTURED TRANSFER ADVICE PRESENTATION
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "C. Structured Transfer Advice Presentation<br>";
echo "============================================<br>";


commandCentreAttentionPresentationCheck(
    'Dashboard reads the authoritative transfer advice message',
    preg_match(
        '/\$attentionTransferAdvice\s*\[\s*[\'\"]message[\'\"]\s*\]/s',
        $dashboardSource
    ) === 1
);


commandCentreAttentionPresentationCheck(
    'Dashboard preserves the authoritative transfer action',
    preg_match(
        '/\$attentionTransferAdvice\s*\[\s*[\'\"]action[\'\"]\s*\]/s',
        $dashboardSource
    ) === 1
);


commandCentreAttentionPresentationCheck(
    'Dashboard escapes transfer advice before rendering',
    preg_match(
        '/htmlspecialchars\s*\(\s*\(string\)\s*\$attentionTransferText/s',
        $dashboardSource
    ) === 1
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Summary<br>";
echo "============================================<br>";


echo "Assertions passed: {$passed}<br>";
echo "Assertions failed: {$failed}<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}
