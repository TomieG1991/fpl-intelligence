<?php

echo "============================================<br>";
echo "Wildcard Intelligence Page Test<br>";
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

function wildcardPageCheck(
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

function wildcardPageRequest(
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

/*
 * ============================================================
 * URLS
 * ============================================================
 */

$baseUrl =
    'http://localhost:8008/fpl-intelligence/public/wildcard.php';


$generatedUrl =
    $baseUrl
    . '?generate=1';


echo "Base URL: "
    . htmlspecialchars(
        $baseUrl,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Generated URL: "
    . htmlspecialchars(
        $generatedUrl,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO A: INITIAL PAGE REQUEST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Initial Page Request<br>";
echo "============================================<br>";


$initialRequest =
    wildcardPageRequest(
        $baseUrl
    );


$initialHtml =
    $initialRequest[
        'html'
    ];


wildcardPageCheck(
    'Initial Wildcard page request succeeds',
    (
        $initialRequest[
            'status'
        ]
        ?? 0
    )
    === 200
);


wildcardPageCheck(
    'Initial Wildcard page returns HTML',
    trim(
        $initialHtml
    )
    !== ''
);


wildcardPageCheck(
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
    . "<br>";


if (
    (
        $initialRequest[
            'error'
        ]
        ?? ''
    )
    !== ''
) {

    echo "Request Error: "
        . htmlspecialchars(
            $initialRequest[
                'error'
            ],
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO B: PAGE SHELL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Page Shell<br>";
echo "============================================<br>";


wildcardPageCheck(
    'Page contains Wildcard Intelligence title',
    stripos(
        $initialHtml,
        'Wildcard Intelligence'
    )
    !== false
);


wildcardPageCheck(
    'Wildcard Intelligence navigation is present',
    stripos(
        $initialHtml,
        'wildcard.php'
    )
    !== false
    &&
    stripos(
        $initialHtml,
        'Wildcard Intelligence'
    )
    !== false
);


wildcardPageCheck(
    'Application content wrapper is present',
    stripos(
        $initialHtml,
        'class="app-content"'
    )
    !== false
);


wildcardPageCheck(
    'Wildcard dashboard wrapper is present',
    stripos(
        $initialHtml,
        'wildcard-dashboard'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C: IDLE / GENERATE STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Generate State<br>";
echo "============================================<br>";


wildcardPageCheck(
    'Generate Wildcard Squad button is rendered',
    stripos(
        $initialHtml,
        'Generate Wildcard Squad'
    )
    !== false
);


wildcardPageCheck(
    'Generate action points to generated mode',
    stripos(
        $initialHtml,
        'wildcard.php?generate=1'
    )
    !== false
);


wildcardPageCheck(
    'Idle page does not render Starting XI pitch',
    stripos(
        $initialHtml,
        'wildcard-pitch'
    )
    === false
);


wildcardPageCheck(
    'Idle page does not render ordered bench',
    stripos(
        $initialHtml,
        'wildcard-bench-grid'
    )
    === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D: GENERATED PAGE REQUEST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Generated Page Request<br>";
echo "============================================<br>";


$generatedRequest =
    wildcardPageRequest(
        $generatedUrl
    );


$generatedHtml =
    $generatedRequest[
        'html'
    ];


wildcardPageCheck(
    'Generated Wildcard page request succeeds',
    (
        $generatedRequest[
            'status'
        ]
        ?? 0
    )
    === 200
);


wildcardPageCheck(
    'Generated Wildcard page returns HTML',
    trim(
        $generatedHtml
    )
    !== ''
);


wildcardPageCheck(
    'Generated page contains document structure',
    stripos(
        $generatedHtml,
        '<html'
    )
    !== false
    &&
    stripos(
        $generatedHtml,
        '</html>'
    )
    !== false
);


echo "Runtime: "
    . number_format(
        (float) (
            $generatedRequest[
                'runtime'
            ]
            ?? 0
        ),
        4
    )
    . " seconds<br>";


echo "HTTP Status: "
    . (
        $generatedRequest[
            'status'
        ]
        ?? 0
    )
    . "<br>";


if (
    (
        $generatedRequest[
            'error'
        ]
        ?? ''
    )
    !== ''
) {

    echo "Request Error: "
        . htmlspecialchars(
            $generatedRequest[
                'error'
            ],
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E: SUMMARY CARDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Wildcard Summary<br>";
echo "============================================<br>";


wildcardPageCheck(
    'Recommended Wildcard Squad section is rendered',
    stripos(
        $generatedHtml,
        'Recommended Wildcard Squad'
    )
    !== false
);


wildcardPageCheck(
    'Squad Cost summary is rendered',
    stripos(
        $generatedHtml,
        'Squad Cost'
    )
    !== false
);


wildcardPageCheck(
    'Bank summary is rendered',
    preg_match(
        '/<p\s+class="eyebrow">\s*Bank\s*<\/p>/i',
        $generatedHtml
    )
    === 1
);


wildcardPageCheck(
    'Formation summary is rendered',
    preg_match(
        '/<p\s+class="eyebrow">\s*Formation\s*<\/p>/i',
        $generatedHtml
    )
    === 1
);


wildcardPageCheck(
    'Starting XI Score summary is rendered',
    stripos(
        $generatedHtml,
        'Starting XI Score'
    )
    !== false
);


wildcardPageCheck(
    'Structure Score summary is rendered',
    stripos(
        $generatedHtml,
        'Structure Score'
    )
    !== false
);


wildcardPageCheck(
    'Regenerate Squad action is rendered',
    stripos(
        $generatedHtml,
        'Regenerate Squad'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F: STARTING XI PITCH
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Starting XI Pitch<br>";
echo "============================================<br>";


wildcardPageCheck(
    'Starting XI section is rendered',
    stripos(
        $generatedHtml,
        'Best Starting Formation'
    )
    !== false
);


wildcardPageCheck(
    'Pitch wrapper is rendered',
    stripos(
        $generatedHtml,
        'class="wildcard-pitch"'
    )
    !== false
);


wildcardPageCheck(
    'Goalkeeper pitch row is rendered',
    stripos(
        $generatedHtml,
        'wildcard-pitch-row-gk'
    )
    !== false
);


wildcardPageCheck(
    'Defender pitch row is rendered',
    stripos(
        $generatedHtml,
        'wildcard-pitch-row-def'
    )
    !== false
);


wildcardPageCheck(
    'Midfielder pitch row is rendered',
    stripos(
        $generatedHtml,
        'wildcard-pitch-row-mid'
    )
    !== false
);


wildcardPageCheck(
    'Forward pitch row is rendered',
    stripos(
        $generatedHtml,
        'wildcard-pitch-row-fwd'
    )
    !== false
);


/*
 * Count player cards specifically inside generated HTML.
 *
 * There should be exactly 11 Starting XI player links.
 */

$startingPlayerCardCount =
    substr_count(
        $generatedHtml,
        'class="wildcard-pitch-player"'
    );


wildcardPageCheck(
    'Pitch contains exactly eleven Starting XI player cards',
    $startingPlayerCardCount
    === 11
);


wildcardPageCheck(
    'Starting XI player cards link to player profiles',
    stripos(
        $generatedHtml,
        'href="player.php?id='
    )
    !== false
);


echo "Starting XI Player Cards: "
    . $startingPlayerCardCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO G: ORDERED BENCH
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Ordered Bench<br>";
echo "============================================<br>";


wildcardPageCheck(
    'Ordered Bench section is rendered',
    stripos(
        $generatedHtml,
        'Ordered Bench'
    )
    !== false
);


wildcardPageCheck(
    'Bench grid is rendered',
    stripos(
        $generatedHtml,
        'class="wildcard-bench-grid"'
    )
    !== false
);


$benchCardCount =
    substr_count(
        $generatedHtml,
        'wildcard-bench-card'
    );


/*
 * Each actual bench card class contains the base class name.
 *
 * Low-reliability cards also add an additional risk class, so counting
 * every occurrence of the text "wildcard-bench-card" would count those
 * twice.
 *
 * Count card opening tags instead.
 */

preg_match_all(
    '/<a\s+class="wildcard-bench-card(?:\s+wildcard-bench-card-risk)?"/i',
    $generatedHtml,
    $benchCardMatches
);


$benchCardCount =
    count(
        $benchCardMatches[
            0
        ]
        ?? []
    );


wildcardPageCheck(
    'Bench contains exactly four player cards',
    $benchCardCount
    === 4
);


wildcardPageCheck(
    'Bench one is rendered',
    stripos(
        $generatedHtml,
        'Bench 1'
    )
    !== false
);


wildcardPageCheck(
    'Bench two is rendered',
    stripos(
        $generatedHtml,
        'Bench 2'
    )
    !== false
);


wildcardPageCheck(
    'Bench three is rendered',
    stripos(
        $generatedHtml,
        'Bench 3'
    )
    !== false
);


wildcardPageCheck(
    'Bench four is rendered',
    stripos(
        $generatedHtml,
        'Bench 4'
    )
    !== false
);


wildcardPageCheck(
    'Bench confidence information is rendered',
    stripos(
        $generatedHtml,
        'Confidence'
    )
    !== false
);


echo "Bench Player Cards: "
    . $benchCardCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO H: STRUCTURE AND RELIABILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Structure & Reliability<br>";
echo "============================================<br>";


wildcardPageCheck(
    'Structure and Reliability section is rendered',
    stripos(
        $generatedHtml,
        'Structure &amp; Reliability'
    )
    !== false
    ||
    stripos(
        $generatedHtml,
        'Structure & Reliability'
    )
    !== false
);


wildcardPageCheck(
    'Wildcard Score is rendered',
    stripos(
        $generatedHtml,
        'Wildcard Score'
    )
    !== false
);


wildcardPageCheck(
    'Raw Bench Score is rendered',
    stripos(
        $generatedHtml,
        'Raw Bench Score'
    )
    !== false
);


wildcardPageCheck(
    'Adjusted Bench Score is rendered',
    stripos(
        $generatedHtml,
        'Adjusted Bench Score'
    )
    !== false
);


wildcardPageCheck(
    'Reliability Penalty is rendered',
    stripos(
        $generatedHtml,
        'Reliability Penalty'
    )
    !== false
);


wildcardPageCheck(
    'GK minimum confidence is rendered',
    stripos(
        $generatedHtml,
        'GK Min Confidence'
    )
    !== false
);


wildcardPageCheck(
    'GK quality floor is rendered',
    stripos(
        $generatedHtml,
        'GK Quality Floor'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I: WHY THIS SQUAD
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Why This Squad<br>";
echo "============================================<br>";


wildcardPageCheck(
    'Why This Squad section is rendered',
    stripos(
        $generatedHtml,
        'Why This Squad?'
    )
    !== false
);


wildcardPageCheck(
    'Best Formation insight is rendered',
    stripos(
        $generatedHtml,
        'Best Formation'
    )
    !== false
);


wildcardPageCheck(
    'Reliable Goalkeeper insight is rendered',
    stripos(
        $generatedHtml,
        'Reliable Goalkeeper'
    )
    !== false
);


wildcardPageCheck(
    'Budget Use insight is rendered',
    stripos(
        $generatedHtml,
        'Budget Use'
    )
    !== false
);


/*
 * Either Bench Risk or Bench Reliability is valid depending
 * on the generated squad.
 */

wildcardPageCheck(
    'Bench reliability insight is rendered',
    stripos(
        $generatedHtml,
        'Bench Risk'
    )
    !== false
    ||
    stripos(
        $generatedHtml,
        'Bench Reliability'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J: PLAYER NAVIGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Player Navigation<br>";
echo "============================================<br>";


$playerProfileLinkCount =
    substr_count(
        $generatedHtml,
        'href="player.php?id='
    );


wildcardPageCheck(
    'Generated page contains player profile links',
    $playerProfileLinkCount
    > 0
);


wildcardPageCheck(
    'Generated page contains links for the complete squad',
    $playerProfileLinkCount
    >= 15
);


echo "Player Profile Links: "
    . $playerProfileLinkCount
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO K: PHP ERROR DETECTION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: PHP Error Detection<br>";
echo "============================================<br>";


$combinedHtml =
    $initialHtml
    . "\n"
    . $generatedHtml;


wildcardPageCheck(
    'Page contains no PHP fatal error',
    stripos(
        $combinedHtml,
        'Fatal error'
    )
    === false
);


wildcardPageCheck(
    'Page contains no PHP parse error',
    stripos(
        $combinedHtml,
        'Parse error'
    )
    === false
);


wildcardPageCheck(
    'Page contains no uncaught PHP error',
    stripos(
        $combinedHtml,
        'Uncaught'
    )
    === false
);


wildcardPageCheck(
    'Page contains no PHP warning output',
    stripos(
        $combinedHtml,
        'Warning:'
    )
    === false
);


wildcardPageCheck(
    'Page contains no PHP notice output',
    stripos(
        $combinedHtml,
        'Notice:'
    )
    === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L: PERFORMANCE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Performance<br>";
echo "============================================<br>";


wildcardPageCheck(
    'Initial Wildcard page loads within 2 seconds',
    (
        $initialRequest[
            'runtime'
        ]
        ?? 999
    )
    <
    2.0
);


wildcardPageCheck(
    'Generated Wildcard page loads within 15 seconds',
    (
        $generatedRequest[
            'runtime'
        ]
        ?? 999
    )
    <
    15.0
);


echo "Initial Runtime: "
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


echo "Generated Runtime: "
    . number_format(
        (float) (
            $generatedRequest[
                'runtime'
            ]
            ?? 0
        ),
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Wildcard Intelligence Page Test Summary<br>";
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