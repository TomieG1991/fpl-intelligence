<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Form Profile Page Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


function playerFormProfileCheck(
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
 * CONFIGURATION
 * ============================================================
 */

$baseUrl =
    'http://localhost:8008/fpl-intelligence/public/player.php?id=1';


echo "Profile URL: "
    . htmlspecialchars(
        $baseUrl,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * REQUEST
 * ============================================================
 */

$startedAt =
    microtime(
        true
    );


$context =
    stream_context_create([

        'http' => [

            'ignore_errors' =>
                true,

            'timeout' =>
                30
        ]
    ]);


$html =
    @file_get_contents(
        $baseUrl,
        false,
        $context
    );


$runtime =
    microtime(
        true
    )
    -
    $startedAt;


$html =
    is_string(
        $html
    )
        ? $html
        : '';


/*
 * ============================================================
 * SCENARIO A
 * PAGE REQUEST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Player Profile Request<br>";
echo "============================================<br>";


playerFormProfileCheck(
    'Player profile request succeeds',
    $html !== ''
);


playerFormProfileCheck(
    'Player profile returns HTML',
    stripos(
        $html,
        '<html'
    )
    !== false
);


echo "Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * RECENT FORM SECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Recent Form Section<br>";
echo "============================================<br>";


playerFormProfileCheck(
    'Recent Form section is rendered',
    strpos(
        $html,
        'data-player-form-intelligence'
    )
    !== false
);


playerFormProfileCheck(
    'Historical Intelligence eyebrow is rendered',
    strpos(
        $html,
        'Historical Intelligence'
    )
    !== false
);


playerFormProfileCheck(
    'Recent Form heading is rendered',
    preg_match(
        '/>\s*Recent Form\s*</i',
        $html
    )
    === 1
);


playerFormProfileCheck(
    'Recent Form explanation is rendered',
    strpos(
        $html,
        'stored per-fixture FPL history'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * FORM RATINGS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Form Ratings<br>";
echo "============================================<br>";


playerFormProfileCheck(
    'Form Rating is rendered',
    strpos(
        $html,
        'data-form-rating'
    )
    !== false
);


playerFormProfileCheck(
    'Performance Rating is rendered',
    strpos(
        $html,
        'data-performance-rating'
    )
    !== false
);


playerFormProfileCheck(
    'Form Participation Rate is rendered',
    strpos(
        $html,
        'data-form-participation-rate'
    )
    !== false
);


playerFormProfileCheck(
    'Form Rating label is rendered',
    strpos(
        $html,
        'Form Rating'
    )
    !== false
);


playerFormProfileCheck(
    'Performance Rating label is rendered',
    strpos(
        $html,
        'Performance Rating'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * TREND OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Trend Output<br>";
echo "============================================<br>";


playerFormProfileCheck(
    'Performance Trend is rendered',
    strpos(
        $html,
        'data-form-trend'
    )
    !== false
);


playerFormProfileCheck(
    'Participation Trend is rendered',
    strpos(
        $html,
        'data-participation-trend'
    )
    !== false
);


playerFormProfileCheck(
    'Minutes Trend is rendered',
    strpos(
        $html,
        'data-minutes-trend'
    )
    !== false
);


playerFormProfileCheck(
    'Performance Trend label is rendered',
    strpos(
        $html,
        'Performance Trend'
    )
    !== false
);


playerFormProfileCheck(
    'Participation Trend label is rendered',
    strpos(
        $html,
        'Participation Trend'
    )
    !== false
);


playerFormProfileCheck(
    'Minutes Trend label is rendered',
    strpos(
        $html,
        'Minutes Trend'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * CURRENT EARLY-SEASON STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Early-Season Trend State<br>";
echo "============================================<br>";


$insufficientDataCount =
    preg_match_all(
        '/Insufficient Data/i',
        $html,
        $matches
    );


playerFormProfileCheck(
    'Current early-season profile exposes insufficient trend evidence',
    $insufficientDataCount >= 3
);


echo "Insufficient Data Occurrences: "
    . (int) $insufficientDataCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * HISTORICAL SAMPLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Historical Sample<br>";
echo "============================================<br>";


playerFormProfileCheck(
    'Fixture sample output is rendered',
    strpos(
        $html,
        'data-form-fixture-sample'
    )
    !== false
);


playerFormProfileCheck(
    'Appearance sample output is rendered',
    strpos(
        $html,
        'data-form-appearance-sample'
    )
    !== false
);


playerFormProfileCheck(
    'Historical Sample label is rendered',
    strpos(
        $html,
        'Historical Sample'
    )
    !== false
);


playerFormProfileCheck(
    'Recent fixtures sample label is rendered',
    strpos(
        $html,
        'recent fixtures'
    )
    !== false
);


playerFormProfileCheck(
    'Appearances sample label is rendered',
    strpos(
        $html,
        'appearances'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * PHP ERROR DETECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: PHP Error Detection<br>";
echo "============================================<br>";


foreach (
    [
        'Fatal error',
        'Parse error',
        'Uncaught',
        'Warning:',
        'Notice:',
        'Undefined variable'
    ]
    as $errorText
) {

    playerFormProfileCheck(
        'Profile contains no '
        . $errorText,
        stripos(
            $html,
            $errorText
        )
        === false
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Performance<br>";
echo "============================================<br>";


playerFormProfileCheck(
    'Player Form profile page loads within 10 seconds',
    $runtime < 10.0
);


echo "Measured Runtime: "
    . number_format(
        $runtime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Player Form Profile Page Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}