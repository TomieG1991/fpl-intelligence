<?php

echo "============================================<br>";
echo "Squad Intelligence Page Test<br>";
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

function squadPageTest(
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

function fetchSquadPreviewPage(
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
                    30,

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
                        30,

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
    . '/public/squad.php?preview=1';


/*
 * ============================================================
 * FETCH PAGE
 * ============================================================
 */

$startedAt =
    microtime(true);


$response =
    fetchSquadPreviewPage(
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


echo "============================================<br>";
echo "Scenario A: Page Request<br>";
echo "============================================<br>";


squadPageTest(
    'Squad preview page request succeeds',
    (
        $response[
            'success'
        ]
        ?? false
    )
    === true
);


squadPageTest(
    'Squad preview page returns HTML',
    trim(
        $html
    )
    !== ''
);


squadPageTest(
    'Squad preview page contains a document structure',
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
 * PAGE SHELL
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario B: Page Shell<br>";
echo "============================================<br>";


squadPageTest(
    'Page contains Squad Intelligence title',
    stripos(
        $html,
        'Squad Intelligence'
    )
    !== false
);


squadPageTest(
    'Squad Intelligence navigation is present',
    stripos(
        $html,
        'Squad Intelligence'
    )
    !== false
    &&
    stripos(
        $html,
        'nav-link active'
    )
    !== false
);


squadPageTest(
    'Application content wrapper is present',
    stripos(
        $html,
        'class="app-content"'
    )
    !== false
);


squadPageTest(
    'Squad dashboard wrapper is present',
    stripos(
        $html,
        'class="dashboard squad-dashboard"'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * DEVELOPMENT PREVIEW
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario C: Development Preview<br>";
echo "============================================<br>";


squadPageTest(
    'Development preview banner is rendered',
    stripos(
        $html,
        'Development Preview Mode'
    )
    !== false
);


squadPageTest(
    'Development preview squad is rendered',
    stripos(
        $html,
        'GW1 Real Squad Preview'
    )
    !== false
);


squadPageTest(
    'Preview contains fifteen-player squad indicator',
    stripos(
        preg_replace(
            '/\s+/',
            ' ',
            $html
        ),
        '15 / 15'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SQUAD INTELLIGENCE SECTIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario D: Squad Intelligence Sections<br>";
echo "============================================<br>";


squadPageTest(
    'Squad Intelligence Summary is rendered',
    stripos(
        $html,
        'Squad Intelligence Summary'
    )
    !== false
);


squadPageTest(
    'Average Intelligence is rendered',
    stripos(
        $html,
        'Average Intelligence'
    )
    !== false
);


squadPageTest(
    'Weakest Position is rendered',
    stripos(
        $html,
        'Weakest Position'
    )
    !== false
);


squadPageTest(
    'Position Intelligence is rendered',
    stripos(
        $html,
        'Intelligence by Position'
    )
    !== false
);


squadPageTest(
    'Transfer priorities are rendered',
    stripos(
        $html,
        'Players to Review'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * SINGLE TRANSFER RECOMMENDATIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario E: Single Transfer Recommendations<br>";
echo "============================================<br>";


squadPageTest(
    'Best Single Moves section is rendered',
    stripos(
        $html,
        'Best Single Moves'
    )
    !== false
);


squadPageTest(
    'Single transfer cards are rendered',
    stripos(
        $html,
        'squad-transfer-card'
    )
    !== false
);


squadPageTest(
    'Single recommendations contain transfer priority information',
    stripos(
        $html,
        'squad-transfer-priority'
    )
    !== false
);


squadPageTest(
    'Single recommendations contain replacement rows',
    stripos(
        $html,
        'squad-replacement-row'
    )
    !== false
);


squadPageTest(
    'Single recommendations contain bank-after information',
    stripos(
        $html,
        'Bank After'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * DOUBLE TRANSFER RECOMMENDATIONS
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario F: Double Transfer Recommendations<br>";
echo "============================================<br>";


squadPageTest(
    'Best Double Transfers section is rendered',
    stripos(
        $html,
        'Best Double Transfers'
    )
    !== false
);


squadPageTest(
    'Double transfer cards are rendered',
    stripos(
        $html,
        'squad-double-transfer-card'
    )
    !== false
);


squadPageTest(
    'Double transfer plans contain Squad Score',
    stripos(
        $html,
        'Squad Score'
    )
    !== false
);


squadPageTest(
    'Double transfer plans contain Transfer A',
    stripos(
        $html,
        'Transfer A'
    )
    !== false
);


squadPageTest(
    'Double transfer plans contain Transfer B',
    stripos(
        $html,
        'Transfer B'
    )
    !== false
);


echo "<br>";


/*
 * ============================================================
 * CURRENT SQUAD COLLAPSIBLE
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario G: Current Squad Control<br>";
echo "============================================<br>";


squadPageTest(
    'Current Squad section is rendered',
    stripos(
        $html,
        'Imported Players'
    )
    !== false
);


squadPageTest(
    'Current Squad toggle is rendered',
    stripos(
        $html,
        'Show Current Squad'
    )
    !== false
);


squadPageTest(
    'Current Squad toggle contains accessibility state',
    stripos(
        $html,
        'aria-expanded="false"'
    )
    !== false
);


squadPageTest(
    'Current Squad panel starts hidden',
    stripos(
        $html,
        'data-squad-panel="current-squad"'
    )
    !== false
    &&
    preg_match(
        '/data-squad-panel=["\']current-squad["\'][^>]*hidden|hidden[^>]*data-squad-panel=["\']current-squad["\']/i',
        $html
    )
    === 1
);


echo "<br>";


/*
 * ============================================================
 * PLAYER NAVIGATION
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario H: Player Navigation<br>";
echo "============================================<br>";


squadPageTest(
    'Page contains player profile links',
    preg_match(
        '/player\.php\?id=\d+/i',
        $html
    )
    === 1
);


squadPageTest(
    'Page contains multiple player profile links',
    preg_match_all(
        '/player\.php\?id=\d+/i',
        $html,
        $playerLinkMatches
    )
    >= 10
);


echo "<br>";


/*
 * ============================================================
 * ERROR OUTPUT
 * ============================================================
 */

echo "============================================<br>";
echo "Scenario I: PHP Error Detection<br>";
echo "============================================<br>";


$lowerHtml =
    strtolower(
        $html
    );


$hasFatalError =
    str_contains(
        $lowerHtml,
        'fatal error'
    );


$hasParseError =
    str_contains(
        $lowerHtml,
        'parse error'
    );


$hasUncaughtError =
    str_contains(
        $lowerHtml,
        'uncaught error'
    );


$hasWarning =
    preg_match(
        '/(?:<br\s*\/?>|\n|\r|^)\s*(?:<[^>]+>\s*)*warning\s*:/i',
        $html
    )
    === 1;


$hasNotice =
    preg_match(
        '/(?:<br\s*\/?>|\n|\r|^)\s*(?:<[^>]+>\s*)*notice\s*:/i',
        $html
    )
    === 1;


squadPageTest(
    'Page contains no PHP fatal error',
    !$hasFatalError
);


squadPageTest(
    'Page contains no PHP parse error',
    !$hasParseError
);


squadPageTest(
    'Page contains no uncaught PHP error',
    !$hasUncaughtError
);


squadPageTest(
    'Page contains no PHP warning output',
    !$hasWarning
);


squadPageTest(
    'Page contains no PHP notice output',
    !$hasNotice
);


echo "<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "============================================<br>";
echo "Squad Intelligence Page Test Summary<br>";
echo "============================================<br>";


echo "Passed: "
    . $passed
    . "<br>";


echo "Failed: "
    . $failed
    . "<br><br>";


if ($failed === 0) {

    echo "RESULT: TESTS PASSED ✅";

} else {

    echo "RESULT: TESTS FAILED ❌";
}