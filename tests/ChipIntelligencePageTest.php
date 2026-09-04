<?php

echo "============================================<br>";
echo "Chip Intelligence Page Test<br>";
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

function chipPageTest(
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
 * PAGE REQUEST HELPER
 * ============================================================
 */

function fetchChipIntelligencePage(
    string $url
): array {

    /*
     * Prefer cURL when available.
     */
    if (
        function_exists(
            'curl_init'
        )
    ) {

        $curl =
            curl_init();


        curl_setopt_array(
            $curl,
            [

                CURLOPT_URL =>
                    $url,

                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_FOLLOWLOCATION =>
                    true,

                CURLOPT_CONNECTTIMEOUT =>
                    5,

                CURLOPT_TIMEOUT =>
                    60,

                CURLOPT_HEADER =>
                    false
            ]
        );


        $body =
            curl_exec(
                $curl
            );


        $error =
            curl_error(
                $curl
            );


        $statusCode =
            (int) curl_getinfo(
                $curl,
                CURLINFO_HTTP_CODE
            );


        curl_close(
            $curl
        );


        return [

            'success' =>
                $body !== false
                &&
                $statusCode >= 200
                &&
                $statusCode < 400,

            'status_code' =>
                $statusCode,

            'body' =>
                $body !== false
                    ? (string) $body
                    : '',

            'error' =>
                $error
        ];
    }


    /*
     * Fallback when cURL is unavailable.
     */
    $context =
        stream_context_create(
            [

                'http' => [

                    'method' =>
                        'GET',

                    'timeout' =>
                        60,

                    'ignore_errors' =>
                        true
                ]
            ]
        );


    $body =
        @file_get_contents(
            $url,
            false,
            $context
        );


    $statusCode =
        0;


    if (
        isset(
            $http_response_header
        )
        &&
        is_array(
            $http_response_header
        )
        &&
        isset(
            $http_response_header[0]
        )
    ) {

        if (
            preg_match(
                '/\s(\d{3})\s/',
                $http_response_header[0],
                $matches
            )
        ) {

            $statusCode =
                (int) (
                    $matches[1]
                    ?? 0
                );
        }
    }


    return [

        'success' =>
            $body !== false
            &&
            (
                $statusCode === 0
                ||
                (
                    $statusCode >= 200
                    &&
                    $statusCode < 400
                )
            ),

        'status_code' =>
            $statusCode,

        'body' =>
            $body !== false
                ? (string) $body
                : '',

        'error' =>
            $body === false
                ? 'Unable to retrieve page'
                : ''
    ];
}


/*
 * ============================================================
 * ACTIVE NAVIGATION HELPER
 * ============================================================
 */

function chipPageHasActiveNavigation(
    string $html
): bool {

    return
        preg_match(
            '/<a[^>]*href=["\']chips\.php["\'][^>]*class=["\'][^"\']*nav-link[^"\']*active[^"\']*["\'][^>]*>.*?Chip Intelligence.*?<\/a>/is',
            $html
        )
        ===
        1
        ||
        preg_match(
            '/<a[^>]*class=["\'][^"\']*nav-link[^"\']*active[^"\']*["\'][^>]*href=["\']chips\.php["\'][^>]*>.*?Chip Intelligence.*?<\/a>/is',
            $html
        )
        ===
        1;
}


/*
 * ============================================================
 * DETERMINE APPLICATION URL
 * ============================================================
 */

$host =
    $_SERVER[
        'HTTP_HOST'
    ]
    ?? 'localhost:8008';


$isHttps =
    (
        isset(
            $_SERVER[
                'HTTPS'
            ]
        )
        &&
        $_SERVER[
            'HTTPS'
        ]
        !== ''
        &&
        strtolower(
            (string) $_SERVER[
                'HTTPS'
            ]
        )
        !== 'off'
    );


$scheme =
    $isHttps
        ? 'https'
        : 'http';


$documentRoot =
    realpath(
        (string) (
            $_SERVER[
                'DOCUMENT_ROOT'
            ]
            ?? ''
        )
    );


$projectRoot =
    realpath(
        __DIR__
        . '/..'
    );


$projectWebPath =
    '';


if (
    $documentRoot !== false
    &&
    $projectRoot !== false
) {

    $documentRoot =
        str_replace(
            '\\',
            '/',
            $documentRoot
        );


    $projectRoot =
        str_replace(
            '\\',
            '/',
            $projectRoot
        );


    if (
        str_starts_with(
            strtolower(
                $projectRoot
            ),
            strtolower(
                $documentRoot
            )
        )
    ) {

        $projectWebPath =
            substr(
                $projectRoot,
                strlen(
                    $documentRoot
                )
            );


        $projectWebPath =
            '/'
            . ltrim(
                $projectWebPath,
                '/'
            );
    }
}


/*
 * Fallback for the current local project layout.
 */
if ($projectWebPath === '') {

    $projectWebPath =
        '/fpl-intelligence';
}


$pageUrl =
    $scheme
    . '://'
    . $host
    . $projectWebPath
    . '/public/chips.php?preview=1';
    
$normalPageUrl =
    $scheme
    . '://'
    . $host
    . $projectWebPath
    . '/public/chips.php';
    
$integrationPageUrl =
    $scheme
    . '://'
    . $host
    . $projectWebPath
    . '/public/chips.php?preview=integration';


/*
 * ============================================================
 * FETCH PAGE
 * ============================================================
 */

$startedAt =
    microtime(true);


$response =
    fetchChipIntelligencePage(
        $pageUrl
    );


$runtime =
    microtime(true)
    -
    $startedAt;


$html =
    $response[
        'body'
    ]
    ?? '';
    
$normalResponse =
    fetchChipIntelligencePage(
        $normalPageUrl
    );


$normalHtml =
    $normalResponse[
        'body'
    ]
    ?? '';
    

$integrationStartedAt =
    microtime(true);


$integrationResponse =
    fetchChipIntelligencePage(
        $integrationPageUrl
    );


$integrationRuntime =
    microtime(true)
    -
    $integrationStartedAt;


$integrationHtml =
    $integrationResponse[
        'body'
    ]
    ?? '';


$normalisedHtml =
    preg_replace(
        '/\s+/',
        ' ',
        $html
    );


$normalisedHtml =
    is_string(
        $normalisedHtml
    )
        ? $normalisedHtml
        : '';


/*
 * ============================================================
 * SCENARIO A
 * PAGE REQUEST
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario A: Page Request<br>";
echo "============================================<br>";


chipPageTest(
    'Chip Intelligence preview page request succeeds',
    (
        $response[
            'success'
        ]
        ?? false
    )
    === true
);


chipPageTest(
    'Chip Intelligence preview page returns HTML',
    trim(
        $html
    )
    !== ''
);


chipPageTest(
    'Chip Intelligence page contains a document structure',
    stripos(
        $html,
        '<!DOCTYPE html>'
    )
    !== false
    &&
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
    . " seconds<br>";


echo "HTTP Status: "
    . (
        $response[
            'status_code'
        ]
        ?? 0
    )
    . "<br>";


if (
    !empty(
        $response[
            'error'
        ]
        ?? ''
    )
) {

    echo "Request Error: "
        . htmlspecialchars(
            (string) $response[
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
 * SCENARIO B
 * APPLICATION SHELL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Application Shell<br>";
echo "============================================<br>";


chipPageTest(
    'Page contains Chip Intelligence title',
    stripos(
        $html,
        'Chip Intelligence'
    )
    !== false
);


chipPageTest(
    'Application content wrapper is present',
    stripos(
        $html,
        'class="app-content"'
    )
    !== false
);


chipPageTest(
    'Chip Intelligence dashboard wrapper is present',
    stripos(
        $html,
        'class="dashboard chip-dashboard"'
    )
    !== false
);


chipPageTest(
    'Chip Intelligence navigation link is present',
    stripos(
        $html,
        'href="chips.php"'
    )
    !== false
    &&
    stripos(
        $html,
        'Chip Intelligence'
    )
    !== false
);


chipPageTest(
    'Chip Intelligence navigation is active',
    chipPageHasActiveNavigation(
        $html
    )
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO C
 * DEVELOPMENT PREVIEW
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Development Preview<br>";
echo "============================================<br>";


chipPageTest(
    'Development preview mode is identified',
    stripos(
        $html,
        'Development Preview Mode'
    )
    !== false
);


chipPageTest(
    'Page explains the chip decision purpose',
    stripos(
        $html,
        'Should I use a chip this week'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO D
 * FOUR CHIP CARDS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Four Chip Cards<br>";
echo "============================================<br>";


$requiredChips = [

    'wildcard' =>
        'Wildcard',

    'free-hit' =>
        'Free Hit',

    'bench-boost' =>
        'Bench Boost',

    'triple-captain' =>
        'Triple Captain'
];


foreach (
    $requiredChips
    as $chipKey => $chipName
) {

    chipPageTest(
        $chipName
        . ' card is rendered',
        stripos(
            $html,
            'data-chip="'
            . $chipKey
            . '"'
        )
        !== false
    );


    chipPageTest(
        $chipName
        . ' name is visible',
        stripos(
            $html,
            $chipName
        )
        !== false
    );
}


echo "<br>";


/*
 * ============================================================
 * SCENARIO E
 * COMMON DECISION CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Common Decision Contract<br>";
echo "============================================<br>";


chipPageTest(
    'Page exposes recommendation labels',
    substr_count(
        strtolower(
            $html
        ),
        'recommendation'
    )
    >= 4
);


chipPageTest(
    'Page exposes confidence labels',
    substr_count(
        strtolower(
            $html
        ),
        'confidence'
    )
    >= 4
);


chipPageTest(
    'Preview exposes supported recommendation language',
    stripos(
        $html,
        'Use'
    )
    !== false
    ||
    stripos(
        $html,
        'Consider'
    )
    !== false
    ||
    stripos(
        $html,
        'Hold'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO F
 * WILDCARD INTELLIGENCE OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Wildcard Intelligence Output<br>";
echo "============================================<br>";


chipPageTest(
    'Wildcard exposes current squad projected points',
    stripos(
        $html,
        'Current squad projected points'
    )
    !== false
);


chipPageTest(
    'Wildcard exposes projected points gain',
    stripos(
        $html,
        'Projected points gain'
    )
    !== false
);


chipPageTest(
    'Wildcard exposes future projected gain',
    stripos(
        $html,
        'Future projected gain'
    )
    !== false
);


chipPageTest(
    'Wildcard exposes timing comparison',
    stripos(
        $html,
        'Better timing'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO G
 * FREE HIT INTELLIGENCE OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Free Hit Intelligence Output<br>";
echo "============================================<br>";


chipPageTest(
    'Free Hit exposes current Starting XI projection',
    stripos(
        $html,
        'Current XI projected points'
    )
    !== false
);


chipPageTest(
    'Free Hit exposes Free Hit Starting XI projection',
    stripos(
        $html,
        'Free Hit XI projected points'
    )
    !== false
);


chipPageTest(
    'Free Hit exposes projected gain',
    stripos(
        $html,
        'Free Hit projected gain'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO H
 * BENCH BOOST INTELLIGENCE OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Bench Boost Intelligence Output<br>";
echo "============================================<br>";


chipPageTest(
    'Bench Boost exposes projected bench points',
    stripos(
        $html,
        'Projected bench points'
    )
    !== false
);


chipPageTest(
    'Bench Boost exposes bench reliability',
    stripos(
        $html,
        'Bench reliability'
    )
    !== false
);


chipPageTest(
    'Bench Boost exposes fixture quality',
    stripos(
        $html,
        'Fixture quality'
    )
    !== false
);


chipPageTest(
    'Bench Boost exposes full-squad availability',
    stripos(
        $normalisedHtml,
        'Full-squad availability'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO I
 * TRIPLE CAPTAIN INTELLIGENCE OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: Triple Captain Intelligence Output<br>";
echo "============================================<br>";


chipPageTest(
    'Triple Captain exposes captain name',
    stripos(
        $html,
        'Captain'
    )
    !== false
);


chipPageTest(
    'Triple Captain exposes projected captain points',
    stripos(
        $html,
        'Projected captain points'
    )
    !== false
);


chipPageTest(
    'Triple Captain exposes Captain Intelligence score',
    stripos(
        $html,
        'Captain Intelligence score'
    )
    !== false
);


chipPageTest(
    'Triple Captain exposes schedule type',
    stripos(
        $html,
        'Schedule type'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO J
 * EXPLANATION CONTRACT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario J: Explanation Contract<br>";
echo "============================================<br>";


chipPageTest(
    'Wildcard exposes an explanation area',
    stripos(
        $html,
        'data-chip="wildcard"'
    )
    !== false
    &&
    stripos(
        $html,
        'chip-explanation'
    )
    !== false
);


chipPageTest(
    'Decision cards expose explanation content',
    substr_count(
        $html,
        'chip-explanation'
    )
    >= 4
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO K
 * PAGE RESPONSIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario K: Page Responsibility<br>";
echo "============================================<br>";


chipPageTest(
    'Page does not claim a synthetic overall chip score',
    stripos(
        $html,
        'Overall Chip Score'
    )
    === false
);


chipPageTest(
    'Page does not claim an invented best-chip ranking',
    stripos(
        $html,
        'Best Chip Ranking'
    )
    === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO L
 * REAL ENTRY MODE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario L: Real Entry Mode<br>";
echo "============================================<br>";


chipPageTest(
    'Normal Chip Intelligence page request succeeds',
    (
        $normalResponse[
            'success'
        ]
        ?? false
    )
    === true
);


chipPageTest(
    'Normal page contains Chip Intelligence title',
    stripos(
        $normalHtml,
        'Chip Intelligence'
    )
    !== false
);


chipPageTest(
    'Normal page does not identify itself as Development Preview Mode',
    stripos(
        $normalHtml,
        'Development Preview Mode'
    )
    === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO M
 * FPL ENTRY FORM
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario M: FPL Entry Form<br>";
echo "============================================<br>";


chipPageTest(
    'Normal page contains an FPL entry form',
    stripos(
        $normalHtml,
        '<form'
    )
    !== false
    &&
    stripos(
        $normalHtml,
        'entry_id'
    )
    !== false
);


chipPageTest(
    'Entry form submits using GET',
    preg_match(
        '/<form[^>]*method=["\']get["\']/i',
        $normalHtml
    )
    === 1
);


chipPageTest(
    'Entry form targets Chip Intelligence',
    preg_match(
        '/<form[^>]*action=["\']chips\.php["\']/i',
        $normalHtml
    )
    === 1
);


chipPageTest(
    'Entry ID field accepts positive numeric FPL entry IDs',
    preg_match(
        '/<input[^>]*name=["\']entry_id["\'][^>]*>/i',
        $normalHtml
    )
    === 1
);


chipPageTest(
    'Page provides an action to analyse the squad',
    stripos(
        $normalHtml,
        'Analyse Chips'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO N
 * NO-SQUAD STATE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario N: No-Squad State<br>";
echo "============================================<br>";


chipPageTest(
    'Page explains that an FPL entry is required',
    stripos(
        $normalHtml,
        'FPL entry'
    )
    !== false
);


chipPageTest(
    'Normal no-entry state does not render development example decisions',
    stripos(
        $normalHtml,
        'Example Captain'
    )
    === false
    &&
    stripos(
        $normalHtml,
        '+13.4 pts'
    )
    === false
);


chipPageTest(
    'Normal no-entry state does not pretend a real recommendation is available',
    stripos(
        $normalHtml,
        'data-chip="wildcard"'
    )
    === false
    &&
    stripos(
        $normalHtml,
        'data-chip="free-hit"'
    )
    === false
    &&
    stripos(
        $normalHtml,
        'data-chip="bench-boost"'
    )
    === false
    &&
    stripos(
        $normalHtml,
        'data-chip="triple-captain"'
    )
    === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO O
 * PRODUCTION SERVICE INTEGRATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario O: Production Service Integration<br>";
echo "============================================<br>";


chipPageTest(
    'Integration page request succeeds',
    (
        $integrationResponse[
            'success'
        ]
        ?? false
    )
    === true
);


chipPageTest(
    'Integration mode is identified',
    stripos(
        $integrationHtml,
        'Production Integration Mode'
    )
    !== false
);


chipPageTest(
    'Integration mode uses current database intelligence',
    stripos(
        $integrationHtml,
        'Current Database Intelligence'
    )
    !== false
);


chipPageTest(
    'Integration mode does not render deterministic example values',
    stripos(
        $integrationHtml,
        'Example Captain'
    )
    === false
    &&
    stripos(
        $integrationHtml,
        '+13.4 pts'
    )
    === false
);


echo "Runtime: "
    . number_format(
        $integrationRuntime,
        4
    )
    . " seconds<br><br>";


/*
 * ============================================================
 * SCENARIO P
 * PRODUCTION CHIP PIPELINES
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario P: Production Chip Pipelines<br>";
echo "============================================<br>";


foreach (
    [
        'wildcard' =>
            'Wildcard',

        'free-hit' =>
            'Free Hit',

        'bench-boost' =>
            'Bench Boost',

        'triple-captain' =>
            'Triple Captain'
    ]
    as $chipKey => $chipName
) {

    chipPageTest(
        $chipName
        . ' production card is rendered',
        stripos(
            $integrationHtml,
            'data-chip="'
            . $chipKey
            . '"'
        )
        !== false
    );
}


chipPageTest(
    'Integration page exposes four recommendation fields',
    substr_count(
        strtolower(
            $integrationHtml
        ),
        'recommendation'
    )
    >= 4
);


chipPageTest(
    'Integration page exposes four confidence fields',
    substr_count(
        strtolower(
            $integrationHtml
        ),
        'confidence'
    )
    >= 4
);


chipPageTest(
    'Integration page exposes four explanation areas',
    substr_count(
        $integrationHtml,
        'chip-explanation'
    )
    >= 4
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO Q
 * PRODUCTION RESULT CONTRACTS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario Q: Production Result Contracts<br>";
echo "============================================<br>";


chipPageTest(
    'Wildcard production output exposes timing intelligence',
    stripos(
        $integrationHtml,
        'Projected points gain'
    )
    !== false
    &&
    stripos(
        $integrationHtml,
        'Future projected gain'
    )
    !== false
    &&
    stripos(
        $integrationHtml,
        'Better timing'
    )
    !== false
);


chipPageTest(
    'Free Hit production output exposes one-gameweek value',
    stripos(
        $integrationHtml,
        'Current XI projected points'
    )
    !== false
    &&
    stripos(
        $integrationHtml,
        'Free Hit XI projected points'
    )
    !== false
    &&
    stripos(
        $integrationHtml,
        'Free Hit projected gain'
    )
    !== false
);


chipPageTest(
    'Bench Boost production output exposes bench opportunity',
    stripos(
        $integrationHtml,
        'Projected bench points'
    )
    !== false
    &&
    stripos(
        $integrationHtml,
        'Bench reliability'
    )
    !== false
    &&
    stripos(
        $integrationHtml,
        'Full-squad availability'
    )
    !== false
);


chipPageTest(
    'Triple Captain production output exposes captain opportunity',
    stripos(
        $integrationHtml,
        'Projected captain points'
    )
    !== false
    &&
    stripos(
        $integrationHtml,
        'Captain Intelligence score'
    )
    !== false
    &&
    stripos(
        $integrationHtml,
        'Schedule type'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO R
 * INTEGRATION RESPONSIBILITY
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario R: Integration Responsibility<br>";
echo "============================================<br>";


chipPageTest(
    'Production integration does not introduce a synthetic overall chip score',
    stripos(
        $integrationHtml,
        'Overall Chip Score'
    )
    === false
);


chipPageTest(
    'Production integration does not introduce a best-chip ranking',
    stripos(
        $integrationHtml,
        'Best Chip Ranking'
    )
    === false
);


echo "<br>";


/*
 * ============================================================
 * SCENARIO — RECOMMENDATION HISTORY INTEGRATION CONTRACT
 * ============================================================
 *
 * v0.35.0 Recommendation History must reuse the dedicated
 * production-capture boundary rather than implementing
 * historical persistence directly inside chips.php.
 *
 * This is deliberately a source-level page integration test.
 *
 * Preview and deterministic integration requests must remain
 * non-persistent development paths.
 */

echo "============================================<br>";
echo "Scenario: Recommendation History Integration Contract<br>";
echo "============================================<br>";


$chipsPagePath =
    realpath(
        __DIR__
        . '/../public/chips.php'
    );


$chipsPageSource =
    $chipsPagePath !== false
        ? file_get_contents(
            $chipsPagePath
        )
        : false;


$chipsPageSource =
    is_string(
        $chipsPageSource
    )
        ? $chipsPageSource
        : '';


chipPageTest(
    'Chip Intelligence page uses RecommendationCandidateProductionCapture',
    strpos(
        $chipsPageSource,
        'new RecommendationCandidateProductionCapture'
    )
    !== false
);


chipPageTest(
    'Chip Intelligence page uses RecommendationCandidateProductionService',
    strpos(
        $chipsPageSource,
        'new RecommendationCandidateProductionService'
    )
    !== false
);


chipPageTest(
    'Chip Intelligence page uses RecommendationCandidateCaptureService',
    strpos(
        $chipsPageSource,
        'new RecommendationCandidateCaptureService'
    )
    !== false
);


chipPageTest(
    'Chip Intelligence page uses RecommendationCandidateRepository',
    strpos(
        $chipsPageSource,
        'new RecommendationCandidateRepository'
    )
    !== false
);


chipPageTest(
    'Chip Intelligence page uses GameweekRepository for recommendation deadline resolution',
    strpos(
        $chipsPageSource,
        'new GameweekRepository'
    )
    !== false
);


chipPageTest(
    'Chip Intelligence page does not write recommendation_candidates with page-level SQL',
    stripos(
        $chipsPageSource,
        'INSERT INTO recommendation_candidates'
    )
    === false
    &&
    stripos(
        $chipsPageSource,
        'UPDATE recommendation_candidates'
    )
    === false
);


chipPageTest(
    'Chip Intelligence page does not write recommendation_snapshots with page-level SQL',
    stripos(
        $chipsPageSource,
        'INSERT INTO recommendation_snapshots'
    )
    === false
    &&
    stripos(
        $chipsPageSource,
        'UPDATE recommendation_snapshots'
    )
    === false
);


chipPageTest(
    'Development preview request still succeeds after recommendation-history integration',
    (
        $response[
            'success'
        ]
        ?? false
    )
    === true
);


chipPageTest(
    'Deterministic integration request still succeeds after recommendation-history integration',
    (
        $integrationResponse[
            'success'
        ]
        ?? false
    )
    === true
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Chip Intelligence Page Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅<br>";

} else {

    echo "RESULT: TESTS FAILED ❌<br>";
}