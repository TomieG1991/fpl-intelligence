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

function checkNestedTransfer(
    string $label,
    bool $ok
): void {

    global $passed;
    global $failed;


    if ($ok) {

        $passed++;

        echo 'PASS: ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '<br>';

    } else {

        $failed++;

        echo 'FAIL: ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '<br>';
    }
}


/*
 * ============================================================
 * A. AUTHORITATIVE NESTED TRANSFER FIXTURE
 * ============================================================
 */

echo "============================================<br>";
echo "Command Centre Transfer Nested Contract Test<br>";
echo "============================================<br><br>";

echo "============================================<br>";
echo "A. Authoritative Nested Transfer Fixture<br>";
echo "============================================<br>";


$fixture = [

    'recommendations' => [

        'status' =>
            'success',

        'recommendations' => [

            [
                'outgoing' => [
                    'name' => 'Outgoing Fixture'
                ],
                'replacements' => [
                    [
                        'player' => [
                            'name' => 'Incoming Fixture'
                        ]
                    ]
                ]
            ]
        ]
    ]
];


$rows =
    $fixture['recommendations']['recommendations'] ?? [];


checkNestedTransfer(
    'Authoritative nested fixture resolves one transfer row',
    count($rows) === 1
);


checkNestedTransfer(
    'Fixture resolves outgoing and incoming player names',
    ($rows[0]['outgoing']['name'] ?? '') === 'Outgoing Fixture'
    && ($rows[0]['replacements'][0]['player']['name'] ?? '') === 'Incoming Fixture'
);


/*
 * ============================================================
 * B. DASHBOARD NESTED TRANSFER CONTRACT
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "B. Dashboard Nested Transfer Contract<br>";
echo "============================================<br>";


foreach (
    ['commandCentreTransferRecommendations', 'opportunityTransferOptions']
    as $variable
) {

    $pattern =
        '/\$' . preg_quote($variable, '/')
        . '\s*=\s*is_array\s*\(\s*\$commandCentreTransfers\s*\[\s*[\'\"]recommendations[\'\"]\s*\]\s*\[\s*[\'\"]recommendations[\'\"]\s*\]/s';

    checkNestedTransfer(
        $variable . ' consumes optimizer recommendation rows rather than wrapper',
        preg_match($pattern, $source) === 1
    );
}


checkNestedTransfer(
    'Both panels use the actual outgoing player',
    strpos($source, "\$recommendation['outgoing']['name']") !== false
    && strpos($source, "\$opportunityTransfer['outgoing']['name']") !== false
);


checkNestedTransfer(
    'Both panels use the highest-ranked replacement',
    strpos($source, "\$recommendation['replacements'][0]['player']['name']") !== false
    && strpos($source, "\$opportunityTransfer['replacements'][0]['player']['name']") !== false
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
