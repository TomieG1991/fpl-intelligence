<?php

require_once __DIR__
    . '/../classes/autoload.php';


echo "============================================<br>";
echo "Player Market Value Trend Integration Test<br>";
echo "============================================<br><br>";


$passed =
    0;


$failed =
    0;


/*
 * ============================================================
 * TEST HELPERS
 * ============================================================
 */

function valueTrendPageCheck(
    string $description,
    bool $condition
): void {

    global $passed;
    global $failed;


    if ($condition) {

        echo
            "PASS: "
            . htmlspecialchars(
                $description,
                ENT_QUOTES,
                'UTF-8'
            )
            . "<br>";

        $passed++;

        return;
    }


    echo
        "FAIL: "
        . htmlspecialchars(
            $description,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    $failed++;
}


function valueTrendPageHeading(
    string $title
): void {

    echo "<br>";
    echo "============================================<br>";

    echo
        htmlspecialchars(
            $title,
            ENT_QUOTES,
            'UTF-8'
        )
        . "<br>";

    echo "============================================<br>";
}


/*
 * ============================================================
 * SETUP
 * ============================================================
 */

try {

    $database =
        new Database();


    $db =
        $database
            ->getConnection();


    $marketService =
        new MarketIntelligenceService(
            $db
        );


    $playerRepository =
        new PlayerRepository(
            $db
        );


} catch (
    Throwable $exception
) {

    echo "SETUP FAILED ❌<br>";

    echo htmlspecialchars(
        $exception->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );

    exit(
        1
    );
}


/*
 * ============================================================
 * SCENARIO A
 * SERVICE FOUNDATION
 * ============================================================
 */

valueTrendPageHeading(
    'Scenario A: Service Foundation'
);


valueTrendPageCheck(
    'Market Intelligence service can be created',
    $marketService
        instanceof
        MarketIntelligenceService
);


valueTrendPageCheck(
    'Market summary method is available',
    method_exists(
        $marketService,
        'getPlayerMarketSummary'
    )
);


/*
 * ============================================================
 * SCENARIO B
 * REAL PLAYER RESOLUTION
 * ============================================================
 */

valueTrendPageHeading(
    'Scenario B: Real Player Resolution'
);


$players =
    $playerRepository
        ->getAll();


$testPlayer =
    null;


$marketSummary =
    null;


foreach (
    $players
    as $player
) {

    $playerId =
        (int) (
            $player[
                'id'
            ]
            ?? 0
        );


    if (
        $playerId <= 0
    ) {

        continue;
    }


    $candidateSummary =
        $marketService
            ->getPlayerMarketSummary(
                $playerId
            );


    if (
        (
            $candidateSummary[
                'status'
            ]
            ?? null
        )
        !==
        'Available'
    ) {

        continue;
    }


    if (
        !isset(
            $candidateSummary[
                'value_trend'
            ]
        )
        ||
        !is_array(
            $candidateSummary[
                'value_trend'
            ]
        )
    ) {

        continue;
    }


    $testPlayer =
        $player;


    $marketSummary =
        $candidateSummary;


    break;
}


valueTrendPageCheck(
    'A real player with Value Trend resolves',
    is_array(
        $testPlayer
    )
    &&
    is_array(
        $marketSummary
    )
);


if (
    !is_array(
        $testPlayer
    )
    ||
    !is_array(
        $marketSummary
    )
) {

    echo "<br>";
    echo "RESULT: TESTS FAILED ❌";

    exit(
        1
    );
}


$playerId =
    (int) (
        $testPlayer[
            'id'
        ]
        ?? 0
    );


$playerName =
    (string) (
        $testPlayer[
            'web_name'
        ]
        ?? 'Unknown'
    );


$valueTrend =
    $marketSummary[
        'value_trend'
    ];


$valueTrendClassification =
    (string) (
        $valueTrend[
            'classification'
        ]
        ?? 'Insufficient Evidence'
    );


$valueTrendStatus =
    (string) (
        $valueTrend[
            'status'
        ]
        ?? 'Unavailable'
    );


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Player ID: "
    . $playerId
    . "<br>";


echo "Value Trend: "
    . htmlspecialchars(
        $valueTrendClassification,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br><br>";


/*
 * ============================================================
 * SCENARIO C
 * PLAYER PAGE REQUEST
 * ============================================================
 */

valueTrendPageHeading(
    'Scenario C: Player Page Request'
);


/*
 * Use a real HTTP request rather than including player.php
 * directly under CLI/browser test execution.
 *
 * This preserves normal page bootstrap behaviour.
 */

$playerUrl =
    'http://localhost:8008/fpl-intelligence/public/player.php?id='
    . $playerId;


$context =
    stream_context_create(
        [
            'http' => [
                'ignore_errors' =>
                    true,

                'timeout' =>
                    15
            ]
        ]
    );


$requestStart =
    microtime(
        true
    );


$pageHtml =
    @file_get_contents(
        $playerUrl,
        false,
        $context
    );


$pageRuntime =
    microtime(
        true
    )
    -
    $requestStart;


$httpStatus =
    null;


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

            $httpStatus =
                (int) $matches[1];

            break;
        }
    }
}


valueTrendPageCheck(
    'Player page request succeeds',
    $httpStatus === 200
);


valueTrendPageCheck(
    'Player page renders HTML',
    is_string(
        $pageHtml
    )
    &&
    trim(
        $pageHtml
    )
    !== ''
);


valueTrendPageCheck(
    'Player page renders selected player name',
    is_string(
        $pageHtml
    )
    &&
    strpos(
        $pageHtml,
        $playerName
    )
    !== false
);


echo "HTTP Status: "
    . (
        $httpStatus
        ??
        'Unknown'
    )
    . "<br>";


echo "Page Runtime: "
    . number_format(
        $pageRuntime,
        4
    )
    . " seconds<br>";


if (
    !is_string(
        $pageHtml
    )
) {

    $pageHtml =
        '';
}


/*
 * ============================================================
 * SCENARIO D
 * EXISTING MARKET INTELLIGENCE
 * ============================================================
 */

valueTrendPageHeading(
    'Scenario D: Existing Market Intelligence'
);


valueTrendPageCheck(
    'Player page still renders Market Intelligence heading',
    stripos(
        $pageHtml,
        'Market Intelligence'
    )
    !== false
);


valueTrendPageCheck(
    'Player page still renders Market Movement',
    stripos(
        $pageHtml,
        'Market Movement'
    )
    !== false
);


valueTrendPageCheck(
    'Player page still renders Price Movement evidence',
    stripos(
        $pageHtml,
        'Price Movement'
    )
    !== false
);


valueTrendPageCheck(
    'Player page still renders Ownership Movement evidence',
    stripos(
        $pageHtml,
        'Ownership Movement'
    )
    !== false
);


valueTrendPageCheck(
    'Player page still renders Transfer Momentum evidence',
    stripos(
        $pageHtml,
        'Transfer Momentum'
    )
    !== false
);


/*
 * ============================================================
 * SCENARIO E
 * VALUE TREND PRESENTATION
 * ============================================================
 */

valueTrendPageHeading(
    'Scenario E: Value Trend Presentation'
);


valueTrendPageCheck(
    'Player page renders Value Trend label',
    stripos(
        $pageHtml,
        'Value Trend'
    )
    !== false
);


valueTrendPageCheck(
    'Player page renders Value Trend classification',
    strpos(
        $pageHtml,
        $valueTrendClassification
    )
    !== false
);


/*
 * ============================================================
 * STOP CLEANLY AT EXPECTED RED STATE
 * ============================================================
 */

$valueTrendLabelRendered =
    stripos(
        $pageHtml,
        'Value Trend'
    )
    !== false;


if (
    !$valueTrendLabelRendered
) {

    echo "<br>";
    echo "Expected next implementation step:<br>";
    echo "Render Market Summary value_trend on player.php<br><br>";

    echo "Required presentation:<br>";
    echo "- Value Trend label<br>";
    echo "- Value Trend classification<br>";
    echo "- controlled insufficient-evidence explanation<br><br>";

    echo "Internal fields must remain hidden:<br>";
    echo "- value_rating<br>";
    echo "- market_classification<br>";


    echo "<br>";
    echo "============================================<br>";
    echo "Player Market Value Trend Integration Test Summary<br>";
    echo "============================================<br>";

    echo
        "Passed: "
        . $passed
        . "<br>";

    echo
        "Failed: "
        . $failed
        . "<br><br>";

    echo "RESULT: TESTS FAILED ❌";


    exit(
        1
    );
}


/*
 * ============================================================
 * SCENARIO F
 * EARLY-SEASON PRESENTATION
 * ============================================================
 */

valueTrendPageHeading(
    'Scenario F: Early-Season Presentation'
);


if (
    $valueTrendClassification
    ===
    'Insufficient Evidence'
) {

    valueTrendPageCheck(
        'Player page renders Insufficient Evidence Value Trend',
        strpos(
            $pageHtml,
            'Insufficient Evidence'
        )
        !== false
    );


    valueTrendPageCheck(
        'Value Trend retains insufficient historical state',
        $valueTrendStatus
        ===
        'Insufficient Historical Data'
    );


    valueTrendPageCheck(
        'Player page explains limited historical Value Trend evidence',
        (
            stripos(
                $pageHtml,
                'historical'
            )
            !== false
        )
        ||
        (
            stripos(
                $pageHtml,
                'history'
            )
            !== false
        )
        ||
        (
            stripos(
                $pageHtml,
                'evidence'
            )
            !== false
        )
    );

} else {

    valueTrendPageCheck(
        'Available Value Trend classification renders',
        strpos(
            $pageHtml,
            $valueTrendClassification
        )
        !== false
    );
}


/*
 * ============================================================
 * SCENARIO G
 * PUBLIC SUMMARY BOUNDARY
 * ============================================================
 */

valueTrendPageHeading(
    'Scenario G: Public Summary Boundary'
);


valueTrendPageCheck(
    'Value Trend summary does not expose value_rating',
    !array_key_exists(
        'value_rating',
        $valueTrend
    )
);


valueTrendPageCheck(
    'Value Trend summary does not expose market_classification',
    !array_key_exists(
        'market_classification',
        $valueTrend
    )
);


/*
 * Do not search the entire HTML for phrases such as
 * "value_rating", because source code, CSS or unrelated page
 * content could legitimately contain implementation wording.
 *
 * The service contract above is the important boundary.
 */


/*
 * ============================================================
 * SCENARIO H
 * PHP ERROR DETECTION
 * ============================================================
 */

valueTrendPageHeading(
    'Scenario H: PHP Error Detection'
);


$errorMarkers =
    [
        'Fatal error',
        'Parse error',
        'Uncaught',
        'Warning:',
        'Notice:',
        'Undefined variable'
    ];


foreach (
    $errorMarkers
    as $errorMarker
) {

    valueTrendPageCheck(
        "Player page contains no {$errorMarker}",
        stripos(
            $pageHtml,
            $errorMarker
        )
        === false
    );
}


/*
 * ============================================================
 * SCENARIO I
 * PERFORMANCE
 * ============================================================
 */

valueTrendPageHeading(
    'Scenario I: Performance'
);


valueTrendPageCheck(
    'Player page loads within 10 seconds',
    $pageRuntime < 10
);


echo "Measured Runtime: "
    . number_format(
        $pageRuntime,
        4
    )
    . " seconds<br>";


/*
 * ============================================================
 * SCENARIO J
 * DIAGNOSTIC
 * ============================================================
 */

valueTrendPageHeading(
    'Scenario J: Player Value Trend Diagnostic'
);


echo "<br>";


echo "Player: "
    . htmlspecialchars(
        $playerName,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Market Classification: "
    . htmlspecialchars(
        (string) (
            $marketSummary[
                'classification'
            ]
            ??
            'Unavailable'
        ),
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Value Trend Classification: "
    . htmlspecialchars(
        $valueTrendClassification,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Value Trend Status: "
    . htmlspecialchars(
        $valueTrendStatus,
        ENT_QUOTES,
        'UTF-8'
    )
    . "<br>";


echo "Rendered Page Length: "
    . strlen(
        $pageHtml
    )
    . " characters<br>";


/*
 * ============================================================
 * SUMMARY
 * ============================================================
 */

echo "<br>";
echo "============================================<br>";
echo "Player Market Value Trend Integration Test Summary<br>";
echo "============================================<br>";


echo
    "Passed: "
    . $passed
    . "<br>";


echo
    "Failed: "
    . $failed
    . "<br><br>";


if (
    $failed === 0
) {

    echo "RESULT: TESTS PASSED ✅";

    exit(
        0
    );
}


echo "RESULT: TESTS FAILED ❌";


exit(
    1
);