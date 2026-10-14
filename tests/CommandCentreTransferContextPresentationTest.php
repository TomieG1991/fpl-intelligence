<?php

echo "============================================<br>";
echo "Command Centre Transfer Context Presentation Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function checkTransferContext(
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


$source =
    is_file($dashboardPath)
        ? file_get_contents($dashboardPath)
        : false;


/*
 * ============================================================
 * A. DASHBOARD SOURCE
 * ============================================================
 */

echo "============================================<br>";
echo "A. Dashboard Source<br>";
echo "============================================<br>";


checkTransferContext(
    'Dashboard file readable',
    is_string($source)
);


echo "<br>";


$source =
    is_string($source)
        ? $source
        : '';


/*
 * ============================================================
 * B. AUTHORITATIVE TRANSFER CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "B. Authoritative Transfer Contract<br>";
echo "============================================<br>";


checkTransferContext(
    'Transfer rows preserve nested authoritative optimizer contract',
    strpos(
        $source,
        "\$commandCentreTransfers['recommendations']['recommendations']"
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * C. RECOMMENDATION CONTEXT
 * ============================================================
 */

echo "============================================<br>";
echo "C. Recommendation Context<br>";
echo "============================================<br>";


checkTransferContext(
    'Transfer card explicitly distinguishes options from overall decision',
    strpos(
        $source,
        'These are options to explore, not a recommendation to make a transfer this gameweek.'
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * D. EXISTING INTELLIGENCE METADATA
 * ============================================================
 */

echo "============================================<br>";
echo "D. Existing Intelligence Metadata<br>";
echo "============================================<br>";


checkTransferContext(
    'Outgoing priority label uses optimizer field',
    strpos(
        $source,
        "\$recommendation['priority_label']"
    ) !== false
);

checkTransferContext(
    'Replacement classification uses optimizer decision type',
    strpos(
        $source,
        "\$recommendation['replacements'][0]['decision_type']"
    ) !== false
);

checkTransferContext(
    'Replacement metadata only shown for existing replacement',
    strpos(
        $source,
        "!empty(\$recommendation['replacements'][0]['player'])"
    ) !== false
);


echo "<br>";


/*
 * ============================================================
 * E. PLAYER LABELS
 * ============================================================
 */

echo "============================================<br>";
echo "E. Player Labels<br>";
echo "============================================<br>";


checkTransferContext(
    'Outgoing and incoming player labels remain in place',
    strpos(
        $source,
        "\$recommendation['outgoing']['name']"
    ) !== false
    && strpos(
        $source,
        "\$recommendation['replacements'][0]['player']['name']"
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
