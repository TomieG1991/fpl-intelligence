<?php

echo "============================================<br>";
echo "Players Accessibility Test<br>";
echo "============================================<br><br>";


$passed =
    0;

$failed =
    0;


function playersAccessibilityCheck(
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


/*
 * ============================================================
 * SOURCE
 * ============================================================
 */

$playersPath =
    __DIR__
    . '/../public/players.php';


$appJsPath =
    __DIR__
    . '/../public/assets/js/app.js';


$playersSource =
    is_file(
        $playersPath
    )
        ? file_get_contents(
            $playersPath
        )
        : false;


$appJsSource =
    is_file(
        $appJsPath
    )
        ? file_get_contents(
            $appJsPath
        )
        : false;


$playersSource =
    is_string(
        $playersSource
    )
        ? $playersSource
        : '';


$appJsSource =
    is_string(
        $appJsSource
    )
        ? $appJsSource
        : '';


/*
 * ============================================================
 * SCENARIO A
 * FILTER GROUP SEMANTICS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Filter Group Semantics<br>";
echo "============================================<br>";


playersAccessibilityCheck(
    'Player Pool controls expose a labelled button group.',
    preg_match(
        '/class=["\']player-pool-filters["\'][^>]*'
        . 'role=["\']group["\'][^>]*'
        . 'aria-label=["\']Player Pool["\']/s',
        $playersSource
    )
    === 1
);


playersAccessibilityCheck(
    'Position controls expose a labelled button group.',
    preg_match(
        '/class=["\']position-filters["\'][^>]*'
        . 'role=["\']group["\'][^>]*'
        . 'aria-label=["\']Position["\']/s',
        $playersSource
    )
    === 1
);


/*
 * ============================================================
 * SCENARIO B
 * INITIAL PRESSED STATE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario B: Initial Pressed State<br>";
echo "============================================<br>";


playersAccessibilityCheck(
    'Ranked Player Pool button exposes its initial selected state.',
    preg_match(
        '/class=["\']player-pool-filter active["\']'
        . '[^>]*aria-pressed=["\']true["\']/s',
        $playersSource
    )
    === 1
);


playersAccessibilityCheck(
    'All Players button exposes its initial unselected state.',
    preg_match(
        '/class=["\']player-pool-filter["\']'
        . '[^>]*aria-pressed=["\']false["\']/s',
        $playersSource
    )
    === 1
);


playersAccessibilityCheck(
    'All Position button exposes its initial selected state.',
    preg_match(
        '/class=["\']position-filter active["\']'
        . '[^>]*aria-pressed=["\']true["\']/s',
        $playersSource
    )
    === 1
);


/*
 * ============================================================
 * SCENARIO C
 * DYNAMIC PRESSED STATE
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario C: Dynamic Pressed State<br>";
echo "============================================<br>";


playersAccessibilityCheck(
    'Player filter JavaScript updates aria-pressed state.',
    strpos(
        $appJsSource,
        "'aria-pressed'"
    )
    !== false
);


/*
 * ============================================================
 * SCENARIO D
 * TABLE COLUMN HEADERS
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Scenario D: Table Column Headers<br>";
echo "============================================<br>";


preg_match_all(
    '/<th\b[^>]*>/i',
    $playersSource,
    $playerTableHeaders
);


$scopedPlayerTableHeaders =
    array_filter(
        $playerTableHeaders[0],
        static function (
            string $header
        ): bool {

            return preg_match(
                '/\bscope=["\']col["\']/i',
                $header
            )
            === 1;
        }
    );


playersAccessibilityCheck(
    'Every Player Rankings table header is identified as a column header.',
    count(
        $playerTableHeaders[0]
    ) === 11
    &&
    count(
        $scopedPlayerTableHeaders
    ) === 11
);


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Players Accessibility Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: ALL TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}