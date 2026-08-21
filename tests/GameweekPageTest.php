<?php

echo "============================================<br>";
echo "Gameweek Intelligence Page Test<br>";
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

function gameweekPageCheck(
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
 * REQUEST HELPER
 * ============================================================
 */

function gameweekPageRequest(
    string $url
): array {

    $context =
        stream_context_create(
            [
                'http' => [

                    'method' =>
                        'GET',

                    'timeout' =>
                        30,

                    'ignore_errors' =>
                        true
                ]
            ]
        );


    $startedAt =
        microtime(
            true
        );


    $html =
        @file_get_contents(
            $url,
            false,
            $context
        );


    $runtime =
        microtime(
            true
        )
        -
        $startedAt;


    $status =
        0;


    if (
        isset(
            $http_response_header
        )
        &&
        is_array(
            $http_response_header
        )
    ) {

        foreach (
            $http_response_header
            as $header
        ) {

            if (
                preg_match(
                    '/^HTTP\/\S+\s+(\d{3})/',
                    $header,
                    $matches
                )
            ) {

                $status =
                    (int) (
                        $matches[
                            1
                        ]
                        ?? 0
                    );

                break;
            }
        }
    }


    return [

        'html' =>
            is_string(
                $html
            )
                ? $html
                : '',

        'status' =>
            $status,

        'error' =>
            $html === false
                ? 'Unable to request page.'
                : '',

        'runtime' =>
            $runtime
    ];
}


/*
 * ============================================================
 * URLS
 * ============================================================
 */

$baseUrl =
    'http://localhost:8008/fpl-intelligence/public/gameweek.php';


$previewUrl =
    $baseUrl
    . '?preview=1';


echo "Base URL: "
    . htmlspecialchars(
        $baseUrl,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Preview URL: "
    . htmlspecialchars(
        $previewUrl,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO A
 * INITIAL PAGE REQUEST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Initial Page Request<br>";
echo "============================================<br>";


$initialRequest =
    gameweekPageRequest(
        $baseUrl
    );


$initialHtml =
    $initialRequest[
        'html'
    ];


gameweekPageCheck(
    'Initial Gameweek page request succeeds',
    (
        $initialRequest[
            'status'
        ]
        ?? 0
    )
    === 200
);


gameweekPageCheck(
    'Initial Gameweek page returns HTML',
    trim(
        $initialHtml
    )
    !== ''
);


gameweekPageCheck(
    'Initial page contains document structure',
    stripos(
        $initialHtml,
        '<html'
    )
    !== false
    &&
    stripos(
        $initialHtml,
        '</html>'
    )
    !== false
);


echo "Runtime: "
    . number_format(
        (float) (
            $initialRequest[
                'runtime'
            ]
            ?? 0
        ),
        4
    )
    . " seconds<br>";


echo "HTTP Status: "
    . (
        $initialRequest[
            'status'
        ]
        ?? 0
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO B
 * PAGE SHELL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Page Shell<br>";
echo "============================================<br>";


gameweekPageCheck(
    'Page contains Gameweek Intelligence title',
    stripos(
        $initialHtml,
        'Gameweek Intelligence'
    )
    !== false
);


gameweekPageCheck(
    'Gameweek Intelligence navigation is present',
    stripos(
        $initialHtml,
        'gameweek.php'
    )
    !== false
    &&
    stripos(
        $initialHtml,
        'Gameweek Intelligence'
    )
    !== false
);


gameweekPageCheck(
    'Gameweek Intelligence navigation is active',
    preg_match(
        '/href="gameweek\.php"\s+class="nav-link\s+active"/i',
        $initialHtml
    )
    === 1
);


gameweekPageCheck(
    'Application content wrapper is present',
    stripos(
        $initialHtml,
        'class="app-content"'
    )
    !== false
);


gameweekPageCheck(
    'Gameweek dashboard wrapper is present',
    stripos(
        $initialHtml,
        'gameweek-dashboard'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * SQUAD INPUT STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Squad Input State<br>";
echo "============================================<br>";


gameweekPageCheck(
    'Analyse Your Gameweek section is rendered',
    stripos(
        $initialHtml,
        'Analyse Your Gameweek'
    )
    !== false
);


gameweekPageCheck(
    'FPL Entry ID input is rendered',
    stripos(
        $initialHtml,
        'name="entry_id"'
    )
    !== false
);


gameweekPageCheck(
    'Analyse Gameweek action is rendered',
    stripos(
        $initialHtml,
        'Analyse Gameweek'
    )
    !== false
);


gameweekPageCheck(
    'Development preview link is rendered',
    stripos(
        $initialHtml,
        'gameweek.php?preview=1'
    )
    !== false
);


gameweekPageCheck(
    'Idle page does not render Starting XI pitch',
    stripos(
        $initialHtml,
        'class="wildcard-pitch"'
    )
    === false
);


gameweekPageCheck(
    'Idle page does not render ordered bench',
    stripos(
        $initialHtml,
        'class="wildcard-bench-grid"'
    )
    === false
);


gameweekPageCheck(
    'Idle page does not render formation comparison grid',
    stripos(
        $initialHtml,
        'class="gameweek-formation-grid"'
    )
    === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * PREVIEW PAGE REQUEST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Preview Page Request<br>";
echo "============================================<br>";


$previewRequest =
    gameweekPageRequest(
        $previewUrl
    );


$previewHtml =
    $previewRequest[
        'html'
    ];


gameweekPageCheck(
    'Preview Gameweek page request succeeds',
    (
        $previewRequest[
            'status'
        ]
        ?? 0
    )
    === 200
);


gameweekPageCheck(
    'Preview Gameweek page returns HTML',
    trim(
        $previewHtml
    )
    !== ''
);


gameweekPageCheck(
    'Preview page contains document structure',
    stripos(
        $previewHtml,
        '<html'
    )
    !== false
    &&
    stripos(
        $previewHtml,
        '</html>'
    )
    !== false
);


gameweekPageCheck(
    'Development preview squad is rendered',
    stripos(
        $previewHtml,
        'Development Preview Squad'
    )
    !== false
);


echo "Runtime: "
    . number_format(
        (float) (
            $previewRequest[
                'runtime'
            ]
            ?? 0
        ),
        4
    )
    . " seconds<br>";


echo "HTTP Status: "
    . (
        $previewRequest[
            'status'
        ]
        ?? 0
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO E
 * GAMEWEEK SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Gameweek Summary<br>";
echo "============================================<br>";


gameweekPageCheck(
    'Gameweek Summary section is rendered',
    stripos(
        $previewHtml,
        'Gameweek Summary'
    )
    !== false
);


gameweekPageCheck(
    'Formation summary is rendered',
    preg_match(
        '/<p\s+class="eyebrow">\s*Formation\s*<\/p>/i',
        $previewHtml
    )
    === 1
);


gameweekPageCheck(
    'Starting XI Score summary is rendered',
    stripos(
        $previewHtml,
        'Starting XI Score'
    )
    !== false
);


gameweekPageCheck(
    'Bench Score summary is rendered',
    stripos(
        $previewHtml,
        'Bench Score'
    )
    !== false
);


gameweekPageCheck(
    'Gameweek summary is rendered',
    preg_match(
        '/<p\s+class="eyebrow">\s*Gameweek\s*<\/p>/i',
        $previewHtml
    )
    === 1
);


gameweekPageCheck(
    'Squad summary is rendered',
    preg_match(
        '/<p\s+class="eyebrow">\s*Squad\s*<\/p>/i',
        $previewHtml
    )
    === 1
);


$summaryCardCount =
    substr_count(
        $previewHtml,
        'class="wildcard-summary-card"'
    );


gameweekPageCheck(
    'Gameweek Summary contains exactly five summary cards',
    $summaryCardCount
    === 5
);


echo "Summary Cards: "
    . $summaryCardCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO F
 * STARTING XI PITCH
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Starting XI Pitch<br>";
echo "============================================<br>";


gameweekPageCheck(
    'Recommended Line-up section is rendered',
    stripos(
        $previewHtml,
        'Recommended Line-up'
    )
    !== false
);


gameweekPageCheck(
    'Starting XI eyebrow is rendered',
    preg_match(
        '/<p\s+class="eyebrow">\s*Starting XI\s*<\/p>/i',
        $previewHtml
    )
    === 1
);


gameweekPageCheck(
    'Pitch wrapper is rendered',
    stripos(
        $previewHtml,
        'class="wildcard-pitch"'
    )
    !== false
);


gameweekPageCheck(
    'Goalkeeper pitch row is rendered',
    stripos(
        $previewHtml,
        'wildcard-pitch-row-gk'
    )
    !== false
);


gameweekPageCheck(
    'Defender pitch row is rendered',
    stripos(
        $previewHtml,
        'wildcard-pitch-row-def'
    )
    !== false
);


gameweekPageCheck(
    'Midfielder pitch row is rendered',
    stripos(
        $previewHtml,
        'wildcard-pitch-row-mid'
    )
    !== false
);


gameweekPageCheck(
    'Forward pitch row is rendered',
    stripos(
        $previewHtml,
        'wildcard-pitch-row-fwd'
    )
    !== false
);


$startingPlayerCardCount =
    substr_count(
        $previewHtml,
        'class="wildcard-pitch-player"'
    );


gameweekPageCheck(
    'Pitch contains exactly eleven Starting XI player cards',
    $startingPlayerCardCount
    === 11
);


gameweekPageCheck(
    'Starting XI cards display Gameweek Score',
    preg_match(
        '/>\s*GW\s*</i',
        $previewHtml
    )
    === 1
);


gameweekPageCheck(
    'Starting XI cards display fixture information',
    stripos(
        $previewHtml,
        'Fixture'
    )
    !== false
);


echo "Starting XI Player Cards: "
    . $startingPlayerCardCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO G
 * ORDERED BENCH
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Ordered Bench<br>";
echo "============================================<br>";


gameweekPageCheck(
    'Substitutes eyebrow is rendered',
    preg_match(
        '/<p\s+class="eyebrow">\s*Substitutes\s*<\/p>/i',
        $previewHtml
    )
    === 1
);


gameweekPageCheck(
    'Ordered Bench section is rendered',
    stripos(
        $previewHtml,
        'Ordered Bench'
    )
    !== false
);


gameweekPageCheck(
    'Bench grid is rendered',
    stripos(
        $previewHtml,
        'class="wildcard-bench-grid"'
    )
    !== false
);


preg_match_all(
    '/<a\s+class="wildcard-bench-card"/i',
    $previewHtml,
    $benchCardMatches
);


$benchCardCount =
    count(
        $benchCardMatches[
            0
        ]
        ?? []
    );


gameweekPageCheck(
    'Bench contains exactly four player cards',
    $benchCardCount
    === 4
);


gameweekPageCheck(
    'Bench one is rendered',
    preg_match(
        '/class="wildcard-bench-order">\s*Bench\s*1\s*</is',
        $previewHtml
    )
    === 1
);


gameweekPageCheck(
    'Bench two is rendered',
    preg_match(
        '/class="wildcard-bench-order">\s*Bench\s*2\s*</is',
        $previewHtml
    )
    === 1
);


gameweekPageCheck(
    'Bench three is rendered',
    preg_match(
        '/class="wildcard-bench-order">\s*Bench\s*3\s*</is',
        $previewHtml
    )
    === 1
);


gameweekPageCheck(
    'Bench four is rendered',
    preg_match(
        '/class="wildcard-bench-order">\s*Bench\s*4\s*</is',
        $previewHtml
    )
    === 1
);


/*
 * Capture the Bench 4 card and make sure the displayed
 * position is GK.
 */

$benchFourIsGoalkeeper =
    preg_match(
        '/Bench\s*4.*?<div\s+class="wildcard-player-meta">.*?<span>.*?<\/span>.*?<span>\s*GK\s*<\/span>/is',
        $previewHtml
    )
    === 1;


gameweekPageCheck(
    'Bench four is the backup goalkeeper',
    $benchFourIsGoalkeeper
);


gameweekPageCheck(
    'Bench cards display Gameweek Score',
    stripos(
        $previewHtml,
        'Gameweek Score'
    )
    !== false
);


gameweekPageCheck(
    'Bench cards display Confidence',
    stripos(
        $previewHtml,
        'Confidence'
    )
    !== false
);


gameweekPageCheck(
    'Bench cards display Availability',
    stripos(
        $previewHtml,
        'Availability'
    )
    !== false
);


echo "Bench Player Cards: "
    . $benchCardCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H
 * FORMATION COMPARISON
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Formation Comparison<br>";
echo "============================================<br>";


gameweekPageCheck(
    'Formation Intelligence eyebrow is rendered',
    preg_match(
        '/<p\s+class="eyebrow">\s*Formation Intelligence\s*<\/p>/i',
        $previewHtml
    )
    === 1
);


gameweekPageCheck(
    'Formation Comparison section is rendered',
    stripos(
        $previewHtml,
        'Formation Comparison'
    )
    !== false
);


gameweekPageCheck(
    'Formation comparison grid is rendered',
    stripos(
        $previewHtml,
        'class="gameweek-formation-grid"'
    )
    !== false
);


preg_match_all(
    '/class="gameweek-formation-card(?:\s+gameweek-formation-card-best)?"/i',
    $previewHtml,
    $formationCardMatches
);


$formationCardCount =
    count(
        $formationCardMatches[
            0
        ]
        ?? []
    );


gameweekPageCheck(
    'Formation comparison contains exactly eight formation cards',
    $formationCardCount
    === 8
);


$recommendedFormationCount =
    substr_count(
        $previewHtml,
        'gameweek-formation-card-best'
    );


gameweekPageCheck(
    'Exactly one formation is marked as recommended',
    $recommendedFormationCount
    === 1
);


gameweekPageCheck(
    'Formation rankings begin at number one',
    preg_match(
        '/class="gameweek-formation-rank">\s*#1\s*</i',
        $previewHtml
    )
    === 1
);


gameweekPageCheck(
    'Formation rankings include number eight',
    preg_match(
        '/class="gameweek-formation-rank">\s*#8\s*</i',
        $previewHtml
    )
    === 1
);


gameweekPageCheck(
    'Formation cards display Starting XI metrics',
    stripos(
        $previewHtml,
        'Starting XI'
    )
    !== false
);


gameweekPageCheck(
    'Formation cards display Bench metrics',
    preg_match(
        '/<span>\s*Bench\s*<\/span>/i',
        $previewHtml
    )
    === 1
);


echo "Formation Cards: "
    . $formationCardCount
    . "<br>";


echo "Recommended Formation Cards: "
    . $recommendedFormationCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO I
 * PLAYER NAVIGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Player Navigation<br>";
echo "============================================<br>";


preg_match_all(
    '/href="player\.php\?id=\d+"/i',
    $previewHtml,
    $playerLinkMatches
);


$playerProfileLinkCount =
    count(
        $playerLinkMatches[
            0
        ]
        ?? []
    );


gameweekPageCheck(
    'Preview page contains player profile links',
    $playerProfileLinkCount
    > 0
);


gameweekPageCheck(
    'Preview page contains links for the complete 15-player squad',
    $playerProfileLinkCount
    === 15
);


echo "Player Profile Links: "
    . $playerProfileLinkCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO J
 * PAGE CONTENT INTEGRITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Page Content Integrity<br>";
echo "============================================<br>";


gameweekPageCheck(
    'Page explains immediate gameweek selection',
    preg_match(
        '/immediate\s+gameweek/i',
        $previewHtml
    )
    === 1
);

gameweekPageCheck(
    'Page explains reliability influence',
    stripos(
        $previewHtml,
        'reliability'
    )
    !== false
);


gameweekPageCheck(
    'Page explains formation comparison',
    stripos(
        $previewHtml,
        'Every legal FPL formation'
    )
    !== false
);


gameweekPageCheck(
    'Page explains backup goalkeeper bench rule',
    stripos(
        $previewHtml,
        'backup goalkeeper remains Bench 4'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * PHP ERROR DETECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: PHP Error Detection<br>";
echo "============================================<br>";


$combinedHtml =
    $initialHtml
    . "\n"
    . $previewHtml;


gameweekPageCheck(
    'Page contains no PHP fatal error',
    stripos(
        $combinedHtml,
        'Fatal error'
    )
    === false
);


gameweekPageCheck(
    'Page contains no PHP parse error',
    stripos(
        $combinedHtml,
        'Parse error'
    )
    === false
);


gameweekPageCheck(
    'Page contains no uncaught PHP error',
    stripos(
        $combinedHtml,
        'Uncaught Error'
    )
    === false
);


gameweekPageCheck(
    'Page contains no PHP warning output',
    stripos(
        $combinedHtml,
        'Warning:'
    )
    === false
);


gameweekPageCheck(
    'Page contains no PHP notice output',
    stripos(
        $combinedHtml,
        'Notice:'
    )
    === false
);


gameweekPageCheck(
    'Page contains no undefined variable output',
    stripos(
        $combinedHtml,
        'Undefined variable'
    )
    === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Performance<br>";
echo "============================================<br>";


$initialRuntime =
    (float) (
        $initialRequest[
            'runtime'
        ]
        ?? 999
    );


$previewRuntime =
    (float) (
        $previewRequest[
            'runtime'
        ]
        ?? 999
    );


gameweekPageCheck(
    'Initial Gameweek page loads within 2 seconds',
    $initialRuntime
    <= 2.0
);


gameweekPageCheck(
    'Preview Gameweek page loads within 15 seconds',
    $previewRuntime
    <= 15.0
);


echo "Initial Runtime: "
    . number_format(
        $initialRuntime,
        4
    )
    . " seconds<br>";


echo "Preview Runtime: "
    . number_format(
        $previewRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Gameweek Intelligence Page Test Summary<br>";
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