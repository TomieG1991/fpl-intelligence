<?php

echo "============================================<br>";
echo "Manager Context Page Wiring Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPER
 * ============================================================
 */

function managerPageWiringTest(
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
 * MANAGER-FACING PAGES
 * ============================================================
 */

$managerPages = [

    'gameweek.php' =>
        __DIR__
        . '/../public/gameweek.php',

    'squad.php' =>
        __DIR__
        . '/../public/squad.php',

    'chips.php' =>
        __DIR__
        . '/../public/chips.php'
];


/*
 * ============================================================
 * A. SHARED MANAGER CONTEXT
 * ============================================================
 */

echo "============================================<br>";
echo "A. Shared Manager Context<br>";
echo "============================================<br>";


foreach (
    $managerPages
    as $pageName => $pagePath
) {

    $source =
        is_file(
            $pagePath
        )
            ? file_get_contents(
                $pagePath
            )
            : false;


    $source =
        is_string(
            $source
        )
            ? $source
            : '';


    managerPageWiringTest(
        $pageName
            . ' loads the shared manager-context boundary',
        strpos(
            $source,
            '/includes/manager-context.php'
        )
        !== false
    );


    managerPageWiringTest(
        $pageName
            . ' consumes the resolved active manager entry',
        strpos(
            $source,
            '$activeManagerEntryId'
        )
        !== false
    );
}


echo "<br>";


/*
 * ============================================================
 * B. NO DUPLICATE ENTRY RESOLUTION
 * ============================================================
 */

echo "============================================<br>";
echo "B. No Duplicate Entry Resolution<br>";
echo "============================================<br>";


foreach (
    $managerPages
    as $pageName => $pagePath
) {

    $source =
        file_get_contents(
            $pagePath
        );


    $source =
        is_string(
            $source
        )
            ? $source
            : '';


    managerPageWiringTest(
        $pageName
            . ' no longer independently validates entry_id with filter_input',
        preg_match(
            '/filter_input\s*\(\s*INPUT_GET\s*,\s*[\'"]entry_id[\'"]\s*,\s*FILTER_VALIDATE_INT\s*\)/s',
            $source
        )
        !==
        1
    );


    managerPageWiringTest(
        $pageName
            . ' does not duplicate ManagerContextService resolution',
        strpos(
            $source,
            'new ManagerContextService'
        )
        === false
    );
}


echo "<br>";


/*
 * ============================================================
 * C. EXISTING PAGE ENTRY CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "C. Existing Page Entry Contract<br>";
echo "============================================<br>";


foreach (
    $managerPages
    as $pageName => $pagePath
) {

    $source =
        file_get_contents(
            $pagePath
        );


    $source =
        is_string(
            $source
        )
            ? $source
            : '';


    managerPageWiringTest(
        $pageName
            . ' still exposes the local entryId variable expected by existing page logic',
        strpos(
            $source,
            '$entryId'
        )
        !== false
    );


    managerPageWiringTest(
        $pageName
            . ' assigns local entryId from shared manager context',
        preg_match(
            '/\$entryId\s*=\s*\$activeManagerEntryId\s*;/',
            $source
        )
        ===
        1
    );
}


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


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}