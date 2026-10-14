<?php

$source =
    file_get_contents(
        __DIR__ . '/../public/index.php'
    );


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * ASSERTION HELPER
 * ============================================================
 */

function checkTransferNames(
    string $label,
    bool $ok
): void {

    global $passed;
    global $failed;


    if ($ok) {

        $passed++;

        echo 'PASS: ' . htmlspecialchars($label) . '<br>';

    } else {

        $failed++;

        echo 'FAIL: ' . htmlspecialchars($label) . '<br>';
    }
}


/*
 * ============================================================
 * A. TRANSFER NAME PRESENTATION
 * ============================================================
 */

echo "============================================<br>";
echo "Command Centre Transfer Names Presentation Test<br>";
echo "============================================<br><br>";

echo "============================================<br>";
echo "A. Transfer Name Presentation<br>";
echo "============================================<br>";


checkTransferNames(
    'Primary transfer uses outgoing player name',
    strpos($source, "\$recommendation['outgoing']['name']") !== false
);


checkTransferNames(
    'Primary transfer uses highest-ranked incoming name',
    strpos($source, "\$recommendation['replacements'][0]['player']['name']") !== false
);


checkTransferNames(
    'Opportunities uses outgoing player name',
    strpos($source, "\$opportunityTransfer['outgoing']['name']") !== false
);


checkTransferNames(
    'Opportunities uses highest-ranked incoming name',
    strpos($source, "\$opportunityTransfer['replacements'][0]['player']['name']") !== false
);


checkTransferNames(
    'Both sections retain safe HTML escaping',
    substr_count($source, 'htmlspecialchars(') >= 2
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
