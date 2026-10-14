<?php

echo "============================================<br>";
echo "Command Centre Attention Readability Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function assertCommandCentreAttentionReadability(
    bool $condition,
    string $message
): void {

    global $passed;
    global $failed;


    if ($condition) {

        $passed++;

        echo "PASS: {$message}<br>";

    } else {

        $failed++;

        echo "FAIL: {$message}<br>";
    }
}


/*
 * ============================================================
 * A. AUTHORITATIVE PRESENTATION SOURCES
 * ============================================================
 */

echo "============================================<br>";
echo "A. Authoritative Presentation Sources<br>";
echo "============================================<br>";


$cssPath =
    __DIR__
    . '/../public/assets/css/app.css';


$css =
    is_file($cssPath)
        ? file_get_contents($cssPath)
        : false;


assertCommandCentreAttentionReadability(
    is_string($css),
    'Dashboard stylesheet is available.'
);


/*
 * ============================================================
 * B. ATTENTION & OPPORTUNITIES READABILITY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "B. Attention & Opportunities Readability<br>";
echo "============================================<br>";


$css =
    is_string($css)
        ? $css
        : '';


function commandCentreReadabilityRule(
    string $css,
    string $selector
): string {

    $pattern =
        '/(?:^|\})\s*'
        . preg_quote($selector, '/')
        . '\s*\{([^}]*)\}/m';


    if (preg_match_all($pattern, $css, $matches) < 1) {

        return '';
    }


    return (string) end($matches[1]);
}


$attentionMeta =
    commandCentreReadabilityRule(
        $css,
        '.command-centre-attention-row .player-meta'
    );


$attentionRows =
    commandCentreReadabilityRule(
        $css,
        '.command-centre-attention-row .player-ranking-row'
    );


$attentionHeadings =
    commandCentreReadabilityRule(
        $css,
        '.command-centre-attention-row .stat-footer'
    );


assertCommandCentreAttentionReadability(
    preg_match('/font-size\s*:\s*(?:1[23456789]|[2-9][0-9])px\s*;/i', $attentionMeta) === 1,
    'Attention and Opportunities explanatory text is at least 12px.'
);


assertCommandCentreAttentionReadability(
    preg_match('/line-height\s*:\s*(?:1\.[4-9]|[2-9])\s*;/i', $attentionMeta) === 1,
    'Attention and Opportunities explanatory text has comfortable line spacing.'
);


assertCommandCentreAttentionReadability(
    preg_match('/color\s*:\s*#[0-9a-f]{6}\s*;/i', $attentionMeta) === 1,
    'Attention and Opportunities use an explicit readable secondary text colour.'
);


assertCommandCentreAttentionReadability(
    preg_match('/padding\s*:/i', $attentionRows) === 1,
    'Attention and Opportunities rows have explicit breathing room.'
);


assertCommandCentreAttentionReadability(
    preg_match('/margin-(?:top|bottom)\s*:/i', $attentionHeadings) === 1,
    'Attention and Opportunities group headings have explicit separation.'
);


/*
 * ============================================================
 * C. PRESERVED NATURAL CARD HEIGHT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "C. Preserved Natural Card Height<br>";
echo "============================================<br>";


$attentionCards =
    commandCentreReadabilityRule(
        $css,
        '.command-centre-attention-row > .dashboard-card'
    );


assertCommandCentreAttentionReadability(
    preg_match('/align-self\s*:\s*start\s*;/i', $attentionCards) === 1,
    'Attention and Opportunities retain natural independent card heights.'
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
