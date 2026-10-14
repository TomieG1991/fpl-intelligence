<?php

echo "============================================<br>";
echo "Command Centre Transfer Card Height Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function transferHeightCheck(
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


$cssPath =
    __DIR__
    . '/../public/assets/css/app.css';


$css =
    is_file($cssPath)
        ? file_get_contents($cssPath)
        : false;


/*
 * ============================================================
 * A. CSS AVAILABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "A. CSS Availability<br>";
echo "============================================<br>";


transferHeightCheck(
    'Dashboard CSS is available',
    is_string($css)
);


echo "<br>";


$css =
    is_string($css)
        ? $css
        : '';


preg_match_all(
    '~\.command-centre-planning-row\s*>\s*\.command-centre-transfer-card\s*\{([^}]*)\}~s',
    $css,
    $matches
);


$rules =
    implode(' ', $matches[1] ?? []);


/*
 * ============================================================
 * B. TRANSFER CARD ALIGNMENT
 * ============================================================
 */

echo "============================================<br>";
echo "B. Transfer Card Alignment<br>";
echo "============================================<br>";


transferHeightCheck(
    'Transfer card uses natural height rather than grid stretching',
    preg_match(
        '~\balign-self\s*:\s*start\s*;~',
        $rules
    ) === 1
);


echo "<br>";


/*
 * ============================================================
 * C. PLANNING GRID PRESERVATION
 * ============================================================
 */

echo "============================================<br>";
echo "C. Planning Grid Preservation<br>";
echo "============================================<br>";


transferHeightCheck(
    'Transfer card remains in the three-column planning grid',
    strpos(
        $css,
        '.command-centre-planning-row {'
    ) !== false
    && strpos(
        $css,
        'repeat(3, minmax(0, 1fr))'
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
